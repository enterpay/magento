<?php

class Enterpay_LaskuYritykselle_Model_Observer extends Mage_Core_Model_Abstract
{

    public function sales_model_service_quote_submit_after(Varien_Event_Observer $observer) {

        $payment = $observer->getQuote()->getPayment();
        if (strtolower($payment['method']) == 'laskuyritykselle' && Mage::getStoreConfig('persistent/options/enabled') != 1) {
            // Activate the quote
            $observer->getQuote()->setIsActive(true);
        }

    }
}