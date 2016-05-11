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

        $invoice->setSubtotal($invoice->getSubtotal() +
            $invoice->getPaymentCharge()
        );
        $invoice->setBaseSubtotal($invoice->getBaseSubtotal() +
            $invoice->getBasePaymentCharge()
        );

        $invoice->setSubtotalInclTax($invoice->getSubtotalInclTax() +
            $invoice->getPaymentCharge()
        );
        $invoice->setBaseSubtotalInclTax($invoice->getBaseSubtotalInclTax() +
            $invoice->getBasePaymentCharge()
        );

        $invoice->setGrandTotal($invoice->getGrandTotal() +
            $invoice->getPaymentCharge()
        );
        $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() +
            $invoice->getBasePaymentCharge()
        );

        return $this;
    }

}
