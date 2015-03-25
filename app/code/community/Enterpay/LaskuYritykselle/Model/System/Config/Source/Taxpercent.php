<?php

class Enterpay_LaskuYritykselle_Model_System_Config_Source_Taxpercent
{

    /**
     *  Configuration values for store product tax percent (VAT).
     */

    public function toOptionArray() {

        /**
         *  Return array of options.
         */

        return array(
            array('value' => '10', 'label' => '10 %'),
            array('value' => '14', 'label' => '14 %'),
            array('value' => '24', 'label' => '24 %'),
            array('value' => 'custom', 'label' => 'Custom percent')
        );

    }

}

?>
