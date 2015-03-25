<?php

class Enterpay_LaskuYritykselle_Block_Info extends Mage_Payment_Block_Info
{

    public function _construct() {
        parent::_construct();
        $this->setTemplate('enterpay/laskuyritykselle/info.phtml');
    }

    public function getMethodCode() {
        return $this->getInfo()->getMethodInstance()->getCode();
    }

}
