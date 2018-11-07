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

class Codnitive_ZarinpalGateway_Block_Payment_Info extends Mage_Payment_Block_Info
{
    
    protected $_helper;
    
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('codnitive/zarinpalgateway/payment/info.phtml');
    }
    
    public function getPaymentInfo($orderId)
    {
        return Mage::getModel('zarinpalgateway/transaction')->loadByOrderId($orderId);
    }
    
    public function getPaymentDescription($orderId)
    {
        $paymentInfo  = $this->getPaymentInfo($orderId);
        $description  = array();
        $authority    = $paymentInfo->getAuthority();
        $reqStatus    = $paymentInfo->getRequestStatus();
        $transStatus  = $paymentInfo->getTransactionStatus();
        $verfiyStatus = $paymentInfo->getVerificationStatus();
        $transRefId   = $paymentInfo->getTransactionReferenceId();
        
        if (is_null($authority)) {
            $description['msg'] = 'Payment has not been processed yet.';
        }
        else if ((int)$reqStatus != 100) {
            $description['msg'] = $this->getMageHelper()->getStatus($reqStatus);
        }
        else if ($transStatus == 'NOK') {
            $description['msg'] = 'Payment canceled by customer.';
        }
        else if ($verfiyStatus != 100) {
            $description['msg'] = $this->getMageHelper()->getStatus($verfiyStatus);
        }
        else if (is_null($transRefId)) {
            $description['msg'] = 'Transaction Reference ID not received.';
        }
        else {
            $description['msg'] = 'Payment was successful.';
        }
        
        return $description;
    }

    public function toPdf()
    {
        $this->setTemplate('codnitive/zarinpalgateway/payment/pdf/info.phtml');
        return $this->toHtml();
    }
    
    public function getMageHelper()
    {
        if (!$this->_helper) {
            $this->_helper = Mage::helper('zarinpalgateway');
        }
        return $this->_helper;
    }
    
}