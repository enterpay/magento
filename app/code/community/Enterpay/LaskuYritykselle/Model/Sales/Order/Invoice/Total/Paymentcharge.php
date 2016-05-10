<?php

class Enterpay_LaskuYritykselle_Model_Sales_Order_Invoice_Total_Paymentcharge
    extends Mage_Sales_Model_Order_Invoice_Total_Abstract
{

    public function collect(Mage_Sales_Model_Order_Invoice $invoice) {
        $helper = Mage::helper("laskuyritykselle");
        $amount = $invoice->getOrder()->getPaymentCharge();
        $invoice->setPaymentCharge($amount);

        $amount = $invoice->getOrder()->getBasePaymentCharge();
        $invoice->setBasePaymentCharge($amount);

        $invoice->setGrandTotal($invoice->getGrandTotal() +
            $invoice->getPaymentCharge() + $helper->getPaymentChargeTaxAmount($invoice->getPaymentCharge(), 
            $helper->getPaymentChargeTaxRate())
        );
        $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() +
            $invoice->getBasePaymentCharge()+ $helper->getPaymentChargeTaxAmount($invoice->getPaymentCharge(), 
            $helper->getPaymentChargeTaxRate())
        );

        return $this;
    }

}
