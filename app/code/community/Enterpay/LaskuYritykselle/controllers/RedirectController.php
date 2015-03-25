<?php

class Enterpay_LaskuYritykselle_RedirectController
    extends Mage_Core_Controller_Front_Action
{

    public function getCheckout() {
        return Mage::getSingleton('checkout/session');
    }

    protected function _expireAjax() {
        if (!Mage::getSingleton('checkout/session')->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1', '403 Session Expired');
            exit;
        }
    }

    public function indexAction() {
        $this->getResponse()
            ->setHeader('Content-type', 'text/html; charset=utf8')
            ->setBody($this->getLayout()
                    ->createBlock('laskuyritykselle/redirect')->toHtml()
            );
    }

    public function returnAction() {
        $secret = Mage::getModel('laskuyritykselle/checkout')
            ->getConfigData('secret');

        $parameters = $this->getRequest()->getParams();

        $hmac_get = null;
        if (isset($parameters['hmac'])) {
            $hmac_get = $parameters['hmac'];
        }

        $params = array(
            "version" => $parameters['version'],
            "key_version" => $parameters['key_version'],
            "status" => $parameters['status'],
            "identifier_valuebuy" => $parameters['identifier_valuebuy'],
            "identifier_merchant" => $parameters['identifier_merchant'],
            "pending_reasons" => $parameters['pending_reasons']
        );

        ksort($params);
        $hmac_params = array();

        foreach ($params as $k => $v) {
            if ($v !== null && $v !== '') {
                $hmac_params[$k] = urlencode($k) . '=' . urlencode($v);
            }
        }

        $hmac_calc = hash_hmac('sha512', implode('&', $hmac_params), $secret);
        if ($hmac_get != $hmac_calc) {
            Mage::throwException($this->__('Payment verification failed: security error.'));
        }

        // response has been authenticated by now
        switch ($_GET['status']) {
            case 'successful':
                $this->_success();
                break;
            case 'pending':
                $this->_pending();
                break;
            case 'canceled':
                $this->_cancel();
                break;
            case 'failed':
                $this->_failed();
                break;
            default:
                Mage::throwException($this->__('Unknown payment response.'));
        }
    }

    function get_error() {
        return false;
    }

    protected function defaultAction() {
        $this->_redirect(Mage::getModel('laskuyritykselle/checkout')
                ->getConfigData('default_route')
        );
    }

    // handle order
    protected function _success() {
        Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)
            ->save(); // empty cart
        $order = Mage::getModel('sales/order')
            ->loadByIncrementId((int)$_GET['identifier_merchant']);

        if ($order->getState() != Mage_Sales_Model_Order::STATE_PROCESSING) {
            $order->sendNewOrderEmail();
            $order->save();
            $this->_saveInvoice($order);
        }

        $this->_redirect(Mage::getModel('laskuyritykselle/checkout')
                ->getConfigData('success_route')
        );
    }

    protected function _pending() {
        if (!isset($_SESSION['pending_handled'][(int)$_GET['identifier_merchant']])) {
            $_SESSION['pending_handled'][(int)$_GET['identifier_merchant']] =
                true;
            Mage::getSingleton('checkout/session')->getQuote()
                ->setIsActive(false)->save(); // empty cart
            $order = Mage::getModel('sales/order')
                ->loadByIncrementId((int)$_GET['identifier_merchant']);
            $order->addStatusToHistory(
                $order->getStatus(),
                __('Pending payment from LaskuYritykselle. Pending reason:') .
                $_GET['pending_reasons'],
                false
            );
            $order->save();
            $order->sendNewOrderEmail();
        }

        $this->_redirect(Mage::getModel('laskuyritykselle/checkout')
                ->getConfigData('success_route')
        );
    }

    protected function _cancel() {
        $this->_cancelOrder();
        $this->defaultAction();
    }

    protected function _failed() {
        $this->_cancelOrder();
        $this->defaultAction();
    }

    protected function _cancelOrder() {
        $order = Mage::getModel('sales/order');

        if (!$order) {
            return;
        }

        $order->load(Mage::getSingleton('checkout/session')->getLastOrderId());
        $order->cancel();
        $history = $this->__('Payment was canceled.');
        $order->addStatusToHistory($order->getStatus(), $history);
        $order->save();
    }

    protected function _saveInvoice(Mage_Sales_Model_Order $order) {
        if ($order->canInvoice()) {
            $invoice = $order->prepareInvoice();
            $invoice->register()->capture();
            Mage::getModel('core/resource_transaction')->addObject($invoice)
                ->addObject($invoice->getOrder())->save();

            return true;
        }

        return false;
    }

}
