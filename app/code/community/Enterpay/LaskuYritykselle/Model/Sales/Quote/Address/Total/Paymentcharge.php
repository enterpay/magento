<?php

class Enterpay_LaskuYritykselle_Model_Sales_Quote_Address_Total_Paymentcharge
    extends Mage_Sales_Model_Quote_Address_Total_Abstract
{

    public function _construct() {
        $this->setCode('payment_charge');
    }

    public function collect(Mage_Sales_Model_Quote_Address $address) {

        parent::collect($address);

        $this->_setBaseAmount(0)->_setAmount(0); // clear existing amount.
        $address->setBasePaymentCharge(0)->setPaymentCharge(0);

        // No payment charge if no items.
        $items = $address->getAllItems();
        if (!count($items)) {
            return $this;
        }

        // Only calculate payment charge if correct payment method.
        if (strtolower($address->getQuote()->getPayment()->getMethod()) ==
            "laskuyritykselle"
        ) {

            $helper = Mage::helper("laskuyritykselle");

            $paymentChargeTaxRate = $helper->getPaymentChargeTaxRate();
            $paymentCharge = $helper->getPaymentCharge($address);

            // Set payment charge.

            $this->_setBaseAmount($paymentCharge)
                ->_setAmount($address->getQuote()->getStore()
                        ->convertPrice($paymentCharge, false)
                );

            $address->setBasePaymentCharge($paymentCharge)
                ->setPaymentCharge($address->getQuote()->getStore()
                        ->convertPrice($paymentCharge, false)
                );

            $address->getQuote()->setBasePaymentCharge($paymentCharge)
                ->setPaymentCharge($address->getQuote()->getStore()
                        ->convertPrice($paymentCharge, false)
                );

            // Update taxes.

            $taxAmount = $helper->getPaymentChargeTaxAmount($paymentCharge,
                $paymentChargeTaxRate
            );

            $address->setBaseTaxAmount($address->getBaseTaxAmount() +
                $taxAmount
            );
            $address->setTaxAmount($address->getTaxAmount() + $taxAmount);

        }

        return $this;

    }

    public function fetch(Mage_Sales_Model_Quote_Address $address) {

        parent::fetch($address);

        if (Mage::getStoreConfig("payment/laskuyritykselle/active") == 1) {

            $paymentCharge = $address->getPaymentCharge();

            $helper = Mage::helper("laskuyritykselle");

            // Hide taxes from amount if set so.

            if (!$helper->showPaymentChargeInclTax()) {
                $paymentChargeTaxRate = $helper->getPaymentChargeTaxRate();
                $taxAmount = $helper->getPaymentChargeTaxAmount($paymentCharge,
                    $paymentChargeTaxRate
                );

                $paymentCharge -= $taxAmount;
            }

            if (isset($paymentCharge) && $paymentCharge != 0) {

                $address->addTotal(array(
                        'code' => $this->getCode(),
                        'title' => Mage::helper('sales')->__('Payment Charge'),
                        'value' => $paymentCharge
                    )
                );

            }

        }

        return $this;

    }

}
