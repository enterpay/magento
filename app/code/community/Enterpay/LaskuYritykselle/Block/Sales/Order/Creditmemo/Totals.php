<?php

class Enterpay_LaskuYritykselle_Block_Sales_order_Creditmemo_Totals
    extends Enterpay_LaskuYritykselle_Block_Sales_Order_Totals
{

    protected $_creditmemo = null;

    public function getCreditmemo() {
        if ($this->_creditmemo === null) {
            if ($this->hasData('creditmemo')) {
                $this->_creditmemo = $this->_getData('creditmemo');
            } elseif (Mage::registry('current_creditmemo')) {
                $this->_creditmemo = Mage::registry('current_creditmemo');
            } elseif ($this->getParentBlock()->getCreditmemo()) {
                $this->_creditmemo = $this->getParentBlock()->getCreditmemo();
            }
        }

        return $this->_creditmemo;
    }

    public function setCreditmemo($creditmemo) {
        $this->_creditmemo = $creditmemo;

        return $this;
    }

    public function getSource() {
        return $this->getCreditmemo();
    }

    protected function _initTotals() {
        parent::_initTotals();

        $this->removeTotal('base_grandtotal');

        if ((float)$this->getSource()->getAdjustmentPositive()) {
            $total = new Varien_Object(array(
                    'code' => 'adjustment_positive',
                    'value' => $this->getSource()->getAdjustmentPositive(),
                    'label' => $this->__('Adjustment Refund')
                )
            );
            $this->addTotal($total);
        }

        if ((float)$this->getSource()->getAdjustmentNegative()) {
            $total = new Varien_Object(array(
                    'code' => 'adjustment_negative',
                    'value' => $this->getSource()->getAdjustmentNegative(),
                    'label' => $this->__('Adjustment Fee')
                )
            );
            $this->addTotal($total);
        }

        return $this;
    }

}
