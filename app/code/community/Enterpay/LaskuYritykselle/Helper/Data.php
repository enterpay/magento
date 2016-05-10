<?php

class Enterpay_LaskuYritykselle_Helper_Data extends Mage_Core_Helper_Abstract
{

    public function getPaymentCharge($address) {

        $paymentCharge = 0;

        // Fixed payment charge.

        $chargeFixed = Mage::getStoreConfig('payment/laskuyritykselle/payment_charge_fixed');
        $chargeFixed = floatval($chargeFixed);

        if (isset($chargeFixed)) {
            $paymentCharge += $chargeFixed;
        }

        // Percentage payment charge.

        $chargePercentage = Mage::getStoreConfig('payment/laskuyritykselle/payment_charge_percentage');
        $chargePercentage = floatval($chargePercentage);

        if (isset($chargePercentage)) {
            $paymentCharge += ($address->getSubtotalInclTax() + $address->getShippingInclTax() + $address->getTaxAmount()) * ($chargePercentage / 100);
        }

        return $paymentCharge - $this->getPaymentChargeTaxAMount($paymentCharge, $this->getPaymentChargeTaxRate(), true);

    }

    public function getPaymentChargeTaxRate() {

        $taxRate = 0;

        $taxRateSetting = Mage::getStoreConfig('payment/laskuyritykselle/payment_charge_tax');

        if (isset($taxRateSetting)) {

            if ($taxRateSetting != 'custom') {

                $taxRate = $taxRateSetting;

            } else {

                $taxRate = Mage::getStoreConfig('payment/laskuyritykselle/payment_charge_tax_custom');

            }

        }

        $taxRate = floatval($taxRate);

        return $taxRate;

    }

    public function getPaymentChargeTaxAmount($chargeAmount = 0, $taxRate = 0, $taxIncluded = false) {
        if($taxIncluded) {
            return $chargeAmount - ($chargeAmount / (1+($taxRate/100)));
        } else {
        return $chargeAmount * ($taxRate/100);
        }
    }

    public function showPaymentChargeInclTax() {
        return Mage::getStoreConfig('tax/cart_display/payment_charge') == 1 ? 0 : 1;
    }

}
