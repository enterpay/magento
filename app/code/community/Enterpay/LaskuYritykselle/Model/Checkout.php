<?php

class Enterpay_LaskuYritykselle_Model_Checkout
    extends Mage_Payment_Model_Method_Abstract
{

    protected $_code = 'laskuyritykselle';
    protected $_formBlockType = 'laskuyritykselle/info'; // Info block imitates form since we don't have an actual form.
    protected $_paymentMethod = 'shared';

    public function _construct() {
        if (!function_exists('mb_substr')) {
            Mage::throwException(__('Mbstring support required for LaskuYritykselle payment.'
                )
            );
        }
    }

    public function getCheckout() {
        return Mage::getSingleton('checkout/session');
    }

    public function getOrderPlaceRedirectUrl() {
        return Mage::getUrl('laskuyritykselle/redirect');
    }

    // Return payment url to be used for connecting to the payment system
    public function getUrl() {
        $production = $this->getConfigData('production');
        if ($production == 1) {
            return 'https://laskuyritykselle.fi/api/payment/start';
        } else {
            return 'https://test.laskuyritykselle.fi/api/payment/start';
        }
    }

    //get order
    public function getQuote() {
        $orderIncrementId = $this->getCheckout()->getLastRealOrderId();
        $order =
            Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);

        return $order;
    }

    public function capture(Varien_Object $payment, $amount) {
        if ($payment->getStatus() != self::STATUS_APPROVED) {
            $payment->setStatus(self::STATUS_APPROVED)
                ->setTransactionId($this->getTransactionId())
                ->setIsTransactionClosed(0);
        }

        return $this;
    }

    //get HTML form data
    public function getFormFields() {
        $order_id = $this->getCheckout()->getLastRealOrderId();
        $order = $this->getQuote();
        $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true)
            ->save();
        $shipping_address = $order->getShippingAddress();
        $billing_address = $order->getBillingAddress();
        $order_gross_amount = round($order->getGrandTotal() * 100);
        $order_vat_amount = round($order->getTaxAmount(), 2) * 100;
        $order_net_amount = $order_gross_amount - $order_vat_amount;
        $currency_code = 'EUR';  // only euro supported
        $order_timestamp = gmdate("Y-m-d H:i:s");

        // initialize fields to send

        $fields = array(
            'version' => '1', // API Version used
            'merchant' => $this->getConfigData('merchant_agreement_code'),
            'identifier_merchant' => $order_id,
            'locale' => Mage::app()->getLocale()->getLocaleCode(),
            'currency' => $currency_code,
            'reference' => $this->_add_checksum($this->getConfigData('reference_number_head'
                ) . $order_id
            ),
            'url_return' => Mage::getUrl('laskuyritykselle/redirect/return',
                array('_secure' => true)
            ),
            'key_version' => $this->getConfigData('key_version')
        );

        // totals

        if ($this->getConfigData('tax_included') == 1) {
            $fields['total_price_including_tax'] = $order_gross_amount;
        } else {
            $fields['total_price_excluding_tax'] = $order_net_amount;
        }

        if (is_object($billing_address)) {
            if (strlen($billing_address->getStreet2()) == 0) {
                $billing_address_street =
                    $this->_sanitise($billing_address->getStreet1(), 100);
            } else {
                $billing_address_street =
                    $this->_sanitise($billing_address->getStreet1() . ' ' .
                        $billing_address->getStreet2(), 100
                    );
            }
            $fields['billing_address[street]'] = $billing_address_street;
            $fields['billing_address[postalCode]'] =
                $this->_sanitise($billing_address->getPostcode(), 10);
            $fields['billing_address[city]'] =
                $this->_sanitise($billing_address->getCity(), 100);
        }

        if (is_object($shipping_address)) {
            if (strlen($shipping_address->getStreet2()) == 0) {
                $shipping_address_street =
                    $this->_sanitise($shipping_address->getStreet1(), 100);
            } else {
                $shipping_address_street =
                    $this->_sanitise($shipping_address->getStreet1() . ' ' .
                        $billing_address->getStreet2(), 100
                    );
            }
            $fields['delivery_address[street]'] = $shipping_address_street;
            $fields['delivery_address[postalCode]'] =
                $this->_sanitise($shipping_address->getPostcode(), 10);
            $fields['delivery_address[city]'] =
                $this->_sanitise($shipping_address->getCity(), 100);
        }

        // cart items
        $items_tax = 0;
        $items_net_price = 0;
        $items_gross_price = 0;
        $items = $this->getQuote()->getAllVisibleItems();
        $basket_item_count = 0;
        if ($items) {
            foreach ($items as $item) {
                if ($item->getParentItem()) {
                    continue;
                }
				$quantity = 0;
				if ($item->getProduct()->getTypeID() == 'bundle') {
					$quantity = 1;
				}
				else {
					$quantity = $item->getQtyToInvoice();
				}
                $fields["cart_items[$basket_item_count][identifier]"] =
                    $item->getSku();
                $fields["cart_items[$basket_item_count][name]"] =
                    $this->_sanitise($item->getName(), 200);
				
                $fields["cart_items[$basket_item_count][quantity]"] =
                    $quantity;
                $items_gross_price += round($item->getPriceInclTax() * 100 *
                    $quantity, 0
                );
                $items_net_price += round($item->getPrice() * 100 *
                    $quantity, 0
                );
                if ($this->getConfigData('tax_included') == 1) {
                    $fields["cart_items[$basket_item_count][unit_price_including_tax]"] =
                        round($item->getPriceInclTax() * 100, 0);
                } else {
                    $fields["cart_items[$basket_item_count][unit_price_excluding_tax]"] =
                        round($item->getPrice() * 100, 0);
                }
				if ($item->getProduct()->getTypeID() == 'bundle') {
					$fields["cart_items[$basket_item_count][tax_rate]"] = 
						round(($item->getPriceInclTax() - $item->getPrice()) / $item->getPrice(), 2);
				}
				else {
					$fields["cart_items[$basket_item_count][tax_rate]"] =
						round($item->getTaxPercent() / 100, 2);
				}
                
                $basket_item_count++;
            }
        }
        // shipping
        if ($order->getShippingAmount() != 0) {
            $shipping_tax = $order->getShippingTaxAmount();
            $fields["cart_items[$basket_item_count][identifier]"] = '0';
            $fields["cart_items[$basket_item_count][name]"] =
                $this->_sanitise($order->getShippingDescription(), 200);
            $fields["cart_items[$basket_item_count][quantity]"] = '1';
            $items_gross_price += round($order->getShippingInclTax() * 100, 0);
            $items_net_price += round($order->getShippingAmount() * 100, 0);
            if ($this->getConfigData('tax_included') == 1) {
                $fields["cart_items[$basket_item_count][unit_price_including_tax]"] =
                    round($order->getShippingInclTax() * 100, 0);
            } else {
                $fields["cart_items[$basket_item_count][unit_price_excluding_tax]"] =
                    round($order->getShippingAmount() * 100, 0);
            }
            $fields["cart_items[$basket_item_count][tax_rate]"] =
                round((($order->getShippingInclTax() -
                            $order->getShippingAmount()) /
                        $order->getShippingAmount()), 2
                );
            $basket_item_count++;
        }
        // payment charge
        if ($order->getPaymentCharge() != 0) {

            $helper = Mage::helper("laskuyritykselle");

            $paymentCharge = $order->getPaymentCharge();

            $taxRate = $helper->getPaymentChargeTaxRate();
            $taxAmount = $helper->getPaymentChargeTaxAmount($paymentCharge,
                $taxRate
            );

            $paymentChargeExclTax = $paymentCharge - $taxAmount;

            $fields["cart_items[$basket_item_count][identifier]"] = '0';
            $fields["cart_items[$basket_item_count][name]"] =
                __('Payment Charge');
            $fields["cart_items[$basket_item_count][quantity]"] = '1';
            $fields["cart_items[$basket_item_count][tax_rate]"] =
                $taxRate / 100;

            if ($this->getConfigData('tax_included') == 1) {
                $fields["cart_items[$basket_item_count][unit_price_including_tax]"] =
                    round($paymentCharge * 100, 0);
            } else {
                $fields["cart_items[$basket_item_count][unit_price_excluding_tax]"] =
                    round($paymentChargeExclTax * 100, 0);
            }

            $items_gross_price += round($paymentCharge * 100, 0);
            $items_net_price += round($paymentChargeExclTax * 100, 0);

            $basket_item_count++;

        }
        // discount if sum of the cart items and total doesn't match
        if (abs($order_gross_amount - $items_gross_price) > 1) {
            $fields["cart_items[$basket_item_count][identifier]"] = '0';
            $fields["cart_items[$basket_item_count][name]"] = __('Discount');
            $fields["cart_items[$basket_item_count][quantity]"] = '1';
            if ($this->getConfigData('tax_included') == 1) {
                $fields["cart_items[$basket_item_count][unit_price_including_tax]"] =
                    round($order_gross_amount - $items_gross_price, 0);
            } else {
                $fields["cart_items[$basket_item_count][unit_price_excluding_tax]"] =
                    round($order_net_amount - $items_net_price, 0);
            }
            $fields["cart_items[$basket_item_count][tax_rate]"] =
                round(((int)$items_gross_price - (int)$items_net_price) /
                    (int)$items_net_price, 2
                );
            $basket_item_count++;
        }

        // debug
        if ('1' == $this->getConfigData('debug')) {
            $fields['debug'] = '1';
        }
        // hmac
        ksort($fields);
        $hmac_params = array();
        foreach ($fields as $k => $v) {
            if ($v !== null && $v !== '' && $k !== 'debug') {
                $hmac_params[$k] = urlencode($k) . '=' . urlencode($v);
            }
        }
        $str = implode('&', $hmac_params);
        $hmac = hash_hmac('sha512', $str, $this->getConfigData('secret'));
        $fields['hmac'] = $hmac;

        return $fields;
    }

    // prepare data to be used for POST
    protected function _sanitise($data, $maxlen, $reverse = false) {
        $data = str_replace('"', '', str_replace('\\', '', $data));
        if (!$reverse) {
            return mb_substr($data, 0, $maxlen);
        } else {
            return mb_substr($data, 0 - $maxlen);
        }
    }

    // calculate checksum for reference
    protected function _add_checksum($n) {
        if (!ctype_digit($n)) {
            Mage::throwException(__('Reference number contains non-numeric characters.'
                )
            );
        }
        $n = strval($n);
        if (strlen($n) > 19) {
            Mage::throwException(__('Reference number too long.'));
        }
        $weights = array(7, 3, 1);
        $sum = 0;
        for ($i = strlen($n) - 1, $j = 0; $i >= 0; $i--, $j++) {
            $sum += (int)$n[$i] * (int)$weights[$j % 3];
        }
        $checksum = (10 - ($sum % 10)) % 10;

        return $n . $checksum;
    }
}
