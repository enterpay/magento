<?php

class Enterpay_LaskuYritykselle_Model_System_Config_Source_Paymentcharge
{

    /**
     *  Type of (optional) payment method charge.
     */

    public function toOptionArray() {

        /**
         *  Return array of options.
         */

        return array(
            array('value' => 'none', 'label' => 'None'),
            array('value' => 'fixed', 'label' => 'Fixed'),
            array('value' => 'percentage', 'label' => 'Percentage')
        );

    }

}
