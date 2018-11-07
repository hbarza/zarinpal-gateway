<?php
/**
 * CODNITIVE
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE_EULA.html.
 * It is also available through the world-wide-web at this URL:
 * http://www.codnitive.com/en/terms-of-service-softwares/
 * http://www.codnitive.com/fa/terms-of-service-softwares/
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade to newer
 * versions in the future.
 *
 * @category   Codnitive
 * @package    Codnitive_ZarinpalGateway
 * @author     Hassan Barza <support@codnitive.com>
 * @copyright  Copyright (c) 2012 CODNITIVE Co. (http://www.codnitive.com)
 * @license    http://www.codnitive.com/en/terms-of-service-softwares/ End User License Agreement (EULA 1.0)
 */

/**
 * Zarinpal Bank Online Payment Controller
 *
 * @category   Codnitive
 * @package    Codnitive_ZarinpalGateway
 * @author     Hassan Barza <support@codnitive.com>
 */

class Codnitive_ZarinpalGateway_ProcessingController extends Mage_Core_Controller_Front_Action
{

    protected $_successBlockType     = 'zarinpalgateway/transaction_success';
    protected $_failureBlockType     = 'zarinpalgateway/transaction_failure';
    protected $_cancelBlockType      = 'zarinpalgateway/transaction_cancel';

    protected $_order;
    protected $_transaction;
    protected $_paymentInst;
    protected $_orderState;
    protected $_orderStatus;
    
    protected $_helper;

    public function _construct()
    {
        parent::_construct();
        $this->_helper = Mage::helper('zarinpalgateway');
    }

    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    protected function _getPendingPaymentStatus()
    {
        return $this->_getHelper()->getPendingPaymentStatus();
    }
    
    protected function _getHelper()
    {
        if (is_null($this->_helper)) {
            $this->_helper = Mage::helper('zarinpalgateway');
        }
        return $this->_helper;
    }

    protected function _expireAjax()
    {
        if (!$this->_getCheckout()->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1', '403 Session Expired');
            exit;
        }
    }

    public function redirectAction()
    {
        try {
            $session = $this->_getCheckout();
            $order = Mage::getModel('sales/order');

            $order->loadByIncrementId($session->getLastRealOrderId());

            if (!$order->getId()) {
                Mage::throwException('No order for processing found');
                return;
            }
            
            $transactionCheck = Mage::getModel('zarinpalgateway/transaction');
            $orderTransaction = $transactionCheck->loadByOrderId($order->getId());
            if ($orderTransaction->getId() || $order->hasInvoices()) {
                $session->addError($this->_getHelper()->__('This order has payment info'));
                $this->_redirect('checkout/cart');
                return;
            }
            
            $transaction = Mage::getModel('zarinpalgateway/transaction');
            $result      = $transaction->paymentRequest($order);
            if ($result === false) {
                for ($i = 0; $i < 2; $i++) {
                    sleep(5);
                    $result = $transaction->paymentRequest($order);
                    if ($result !== false) {
                        break;
                    }
                }
                if ($result === false) {
                    $result = 'Payment was canceled because couldn\'t connect to Zarinpal gateway to get Authority.';
                    $this->_authorityFailure($order, $result);
                    return;
                }
                elseif (!$result->Authority || strlen($result->Authority) !== 36) {
                    $result = 'Invalid Authority Code';
                    $this->_authorityFailure($order, $result);
                    return;
                }
                elseif ($result->Status != 100) {
                    $this->_authorityFailure($order, $result);
                    return;
                }
            }
            
            $order->setState(
                    Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, 
                    $this->_getPendingPaymentStatus(), 
                    $this->_getHelper()->__('Customer was redirected to Zarinpal Gateway.')
            )->save();
            
            if ($session->getQuoteId() && $session->getLastSuccessQuoteId()) {
                $session->setZarinpalGwQuoteId($session->getQuoteId());
                $session->setZarinpalGwSuccessQuoteId($session->getLastSuccessQuoteId());
                $session->setZarinpalGwRealOrderId($session->getLastRealOrderId());
                $session->setZarinpalGwAuthority($result->Authority);
                $session->getQuote()->setIsActive(false)->save();
                $session->clear();
            }

            $this->loadLayout();
            $this->renderLayout();
            return;
        }
        catch (Mage_Core_Exception $e) {
            $err = $e->getMessage();
            $this->_authorityFailure($order, $err);
        }
        catch (Exception $e) {
            $err = 'An error occurred before redirection to Zarinpal gateway.';
            $this->_catchMessages(null, null, $e);
            $this->_authorityFailure($order, $err);
        }
        $this->_redirect('checkout/cart');
    }
    
    protected function _authorityFailure($order, $result)
    {
        if (! is_null($result->Status)) {
            $message = $this->_getHelper()
                ->__('Payment was canceled because couldn\'t get proper Authority from Zarinpal gateway.<li>Status: %s</li>',
                        $this->_getHelper()->getStatus($result->Status)
                );
        }
        else {
            $message = $result;
        }
                
        $order->cancel();
        $order->setState(
                Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, 
                $this->_getPendingPaymentStatus(), 
                $this->_getHelper()->__('Payment is pending because of authority failure.')
        );
        $order->addStatusToHistory(
                Mage_Sales_Model_Order::STATE_CANCELED, 
                $this->_getHelper()->__($message)
        );

        $order->save();
        
        $session = $this->_getCheckout();
        if ($quoteId = $session->getLastQuoteId()) {
            $quote = Mage::getModel('sales/quote')->load($quoteId);
            if ($quote->getId()) {
                $quote->setIsActive(true)->save();
                $session->setQuoteId($quoteId);
            }
        }
        $this->_catchMessages($message);
        $this->_redirect('checkout/cart');
    }

    public function responseAction()
    {
        try {
            $response = $this->_checkResponse();
            
            if ($this->_order->getState() == Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) {
                $this->_getNewOrderStatus();
                $this->_order->setState(
                        $this->_orderState, $this->_orderStatus, $this->_getHelper()->__('Customer back from Zarinpal gateway.'), false
                );
            }
            
            if ($response['Status'] == 'NOK') {
                $this->_processCancel($response);
                return;
            }
            
            $this->_responseValidation($response);
        }
        catch (Mage_Core_Exception $e) {
            $this->_catchMessages('Transaction response check: An error occurred in transaction.');
            $this->_failureBlock();
        }
        catch (Exception $e) {
            $this->_catchMessages('Transaction response: An unknown error occurred in transaction.');
            $this->_failureBlock();
        }
    }

    protected function _checkResponse()
    {
        if (!$this->getRequest()->isGet()) {
            Mage::throwException('Wrong request type.');
        }

        $request = $this->getRequest()->getParams();
        if (empty($request)) {
            Mage::throwException('Request doesn\'t contain any parameters.');
        }

        if (!isset($request['Authority'])) {
            Mage::throwException('Transaction Authority doesn\'t set.');
        }

        if (!isset($request['Status'])) {
            Mage::throwException('Transaction Status doesn\'t set.');
        }
        
        $transaction = Mage::getModel('zarinpalgateway/transaction')->loadByAuthority($request['Authority']);
        if (!$transaction->getId()) {
            Mage::throwException('No transaction information found for Authority: .' . $request['Authority']);
        }

        $this->_order = Mage::getModel('sales/order')->load($transaction->getOrderId());
        if (!$this->_order->getId() 
            || ($this->_order->getIncrementId() != $transaction->getOrderRealId())) {
            Mage::throwException('Order not found');
        }

        $this->_paymentInst = $this->_order->getPayment()->getMethodInstance();

        return $request;
    }

    public function successAction()
    {
        try {
            $session = $this->_getCheckout();
            $session->setQuoteId($session->getZarinpalGwQuoteId(true));
            $session->setLastSuccessQuoteId($session->getZarinpalGwSuccessQuoteId(true));
            $session->unsZarinpalGwRealOrderId();
            $session->unsetZarinpalGwQuoteId();
            $session->unsetZarinpalGwSuccessQuoteId();
            $session->unsetZarinpalGwRealOrderId();
            $session->unsetZarinpalGwAuthority();
            $this->_redirect('checkout/onepage/success');
            return;
        }
        catch (Mage_Core_Exception $e) {
            $this->_catchMessages($e->getMessage());
        }
        catch (Exception $e) {
            $this->_catchMessages(null, null, $e);
        }
        $this->_redirect('checkout/cart');
    }

    public function cancelAction()
    {
        $session = $this->_getCheckout();
        if ($quoteId = $session->getZarinpalGwQuoteId()) {
            $quote = Mage::getModel('sales/quote')->load($quoteId);
            if ($quote->getId()) {
                $quote->setIsActive(true)->save();
                $session->setQuoteId($quoteId);
            }
        }
        $session->unsZarinpalGwRealOrderId();
        $session->unsetZarinpalGwQuoteId();
        $session->unsetZarinpalGwSuccessQuoteId();
        $session->unsetZarinpalGwRealOrderId();
        $session->unsetZarinpalGwAuthority();
        $session->addError($this->_getHelper()->__('The order has been canceled.'));
        $this->_redirect('checkout/cart');
    }
    
    protected function _responseValidation($response)
    {
        $transaction = Mage::getModel('zarinpalgateway/transaction')->loadByAuthority($response['Authority']);

        if (!$transaction->getId()) {
            $error = $this->_getHelper()->__('Transaction for Zarinpal payment is not valid.');
            $this->_getCheckout()->addError($error);
            Mage::throwException('Order #: ' . $this->_order->getRealOrderId() . '. Transaction Validation: there is not any transaction for this authority: ' .  $response['Authority'] . '.');
        }

        $transaction->setTransactionStatus($response['Status'])
                ->save();
        
        if ($response['Status'] == 'OK') {
            $this->_transactionVerification($response);
            return;
        }
        
        $error = $this->_getHelper()->__('Transaction was not successful. Status: %s', $this->_getHelper()->getStatus($response['Status']));
        $this->_getCheckout()->addError($error);
        Mage::throwException('Order #: ' . $this->_order->getRealOrderId() . '. Transaction Error: transaction with authority ' .  $response['Authority'] . ' was not successful. Status Code: ' . $response['Status']);
    }
    
    protected function _transactionVerification($response)
    {
        $transaction = Mage::getModel('zarinpalgateway/transaction');
        $result      = $transaction->verifyRequest($response, $this->_order->getGrandTotal());

        if ($result === false) {
            for ($i = 0; $i < 3; $i++) {
                sleep(5);
                $result = $transaction->verifyRequest($response, $this->_order->getGrandTotal());
                if ($result !== false) {
                    break;
                }
            }
            if (!$result) {
                $error = $this->_getHelper()->__('Order was canceled because couldn\'t connect to Zarinpal gateway to verify transaction.');
                $this->_getCheckout()->addError($error);
                Mage::throwException('Order #: ' . $this->_order->getRealOrderId() . '. Transaction Verification: error occurred on verification SOAP connection for authority: ' .  $response['Authority'] . '.');
            }
        }
        
        if ($result->Status != 100 || !$result->RefID) {
            $error = $this->_getHelper()->__('Error in payment transaction verification. Status: %s', $this->_helper->getStatus($result->Status));
            $this->_getCheckout()->addError($error);
            Mage::throwException('Order #: ' . $this->_order->getRealOrderId() . '. Transaction Verification: payment transaction verification is not valid. Status code: ' . $result->Status);
        }
        
        $this->_processSale();
    }

    protected function _processSale()
    {
        if ($this->_order->getState() == Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) {
            $this->_getNewOrderStatus();
            $this->_order->setState(
                    $this->_orderState, $this->_orderStatus, $this->_getHelper()->__('Customer payment was successful.'), false
            );
        }

        $this->_order->sendNewOrderEmail();
        $this->_order->setEmailSent(true);

        $this->_order->save();

        $this->getResponse()->setBody(
            $this->getLayout()
                ->createBlock($this->_successBlockType)
                ->setOrder($this->_order)
                ->toHtml()
        );
    }

    protected function _processCancel($response)
    {
        if ($this->_order->canCancel()) {
            $this->_order->cancel();
            $this->_order->addStatusToHistory(
                    Mage_Sales_Model_Order::STATE_CANCELED, $this->_getHelper()->__('Payment was canceled.')
            );

            $this->_order->save();
        }
            
        $transaction = Mage::getModel('zarinpalgateway/transaction')->load($response['Authority'], 'authority');
        if ($transaction->getId()) {
            $transaction->setTransactionStatus($response['Status'])
                ->save();
        }

        $this->getResponse()->setBody(
            $this->getLayout()
                ->createBlock($this->_cancelBlockType)
                ->setOrder($this->_order)
                ->toHtml()
        );
    }
    
    protected function _catchMessages($sessionMessage = null, $debugMessage = null, $logE = null)
    {
        if (!is_null($sessionMessage)) {
            $this->_getCheckout()->addError($this->_getHelper()->__($sessionMessage));
        }
        
        if (!is_null($debugMessage)) {
            $this->_debug($debugMessage);
        }
        
        if (!is_null($logE)) {
            Mage::logException($logE);
        }
    }

    protected function _failureBlock()
    {
        $this->getResponse()->setBody(
                $this->getLayout()
                ->createBlock($this->_failureBlockType)
                ->toHtml()
        );
    }

    protected function _getNewOrderStatus()
    {
        $newOrderStatus = $this->_paymentInst->getConfigData('order_status');
        switch ($newOrderStatus) {
            case 'pending':
                $this->_orderState = Mage_Sales_Model_Order::STATE_NEW;
                $this->_orderStatus = 'pending';
                break;
            case 'processing':
                $this->_orderState = Mage_Sales_Model_Order::STATE_PROCESSING;
                $this->_orderStatus = 'processing';
                break;
            case 'complete':
                $this->_orderState = Mage_Sales_Model_Order::STATE_PROCESSING;
                $this->_orderStatus = 'complete';
                break;
            case 'closed':
                $this->_orderState = Mage_Sales_Model_Order::STATE_PROCESSING;
                $this->_orderStatus = 'processing';
                break;
            case 'canceled':
                $this->_orderState = Mage_Sales_Model_Order::STATE_PROCESSING;
                $this->_orderStatus = 'processing';
                break;
            case 'holded':
                $this->_orderState = Mage_Sales_Model_Order::STATE_HOLDED;
                $this->_orderStatus = 'holded';
                break;
            default:
                $this->_orderState = Mage_Sales_Model_Order::STATE_NEW;
                $this->_orderStatus = 'pending';
        }
    }

}