<?php

class Enterpay_LaskuYritykselle_Block_Redirect extends Mage_Core_Block_Abstract
{

    protected function _toHtml() {
        $model = Mage::getModel('laskuyritykselle/checkout');
        $action = $model->getUrl();

        if (!$action) {
            Mage::throwException($this->__("Payment system not available."));
        }

        // Varien_Data_Form adds fields to the form, so we render it ourselves
        $html = '<html><body><form method="POST" id="pay" action="' . $action .
            '">';
        $html .= $this->__('Redirecting to Lasku yritykselle.');

        $hmac = '';

        foreach ($model->getFormFields() as $field => $value) {
            if ($field == 'hmac') {
                $hmac = $value;
                continue;
            }
            $html .= "<input type=\"hidden\" name=\"{$field}\" value=\"" .
                htmlentities($value, ENT_COMPAT, "UTF-8") . "\" />\n";
        }

        $html .= "<input type=\"hidden\" name=\"hmac\" value=\"" . $hmac .
            "\" /><br />";

        if (Mage::getModel('laskuyritykselle/checkout')
                ->getConfigData('debug') == 0
        ) {
            // if debug mode, do not auto-submit
            $html .= '<script type="text/javascript">document.getElementById("pay").submit();</script>';
        }

        $html .= '<input type="submit" />';
        $html .= '</form></body></html>';

        return $html;
    }

}
