<?php

class Enterpay_LaskuYritykselle_Model_Sales_Order_Creditmemo_Total_Paymentcharge
    extends Mage_Sales_Model_Order_Creditmemo_Total_Abstract
{

    public function collect(Mage_Sales_Model_Order_Creditmemo $creditmemo) {
        $helper = Mage::helper("laskuyritykselle");
        $creditmemo->setPaymentCharge(0);
        $creditmemo->setBasePaymentCharge(0);

        $amount = $creditmemo->getOrder()->getPaymentCharge();
        $creditmemo->setPaymentCharge($amount);

        $amount = $creditmemo->getOrder()->getBasePaymentCharge();
        $creditmemo->setBasePaymentCharge($amount);

        $creditmemo->setGrandTotal($creditmemo->getGrandTotal() +
            $creditmemo->getPaymentCharge() + $helper->getPaymentChargeTaxAmount($creditmemo->getPaymentCharge(), 
            $helper->getPaymentChargeTaxRate())
        );
        $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() +
            $creditmemo->getBasePaymentCharge() + $helper->getPaymentChargeTaxAmount($creditmemo->getPaymentCharge(), 
            $helper->getPaymentChargeTaxRate())
        );

        return $this;
    }

}
