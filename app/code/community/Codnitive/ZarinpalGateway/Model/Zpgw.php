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
 * Zarinpal Bank Online payment method model
 *
 * @category   Codnitive
 * @package    Codnitive_ZarinpalGateway
 * @author     Hassan Barza <support@codnitive.com>
 */
class Codnitive_ZarinpalGateway_Model_Zpgw extends Mage_Payment_Model_Method_Abstract
{

    protected $_code           = 'zarinpalgateway';
    protected $_formBlockType  = 'zarinpalgateway/payment_checkout_form';
    protected $_infoBlockType  = 'zarinpalgateway/payment_info';

    protected $_isGateway               = true;
    protected $_canAuthorize            = false;
    protected $_canCapture              = false;
    protected $_canCapturePartial       = false;
    protected $_canRefund               = false;
    protected $_canRefundInvoicePartial = false;
    protected $_canVoid                 = false;
    protected $_canUseInternal          = false;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = false;
    protected $_isInitializeNeeded      = false;
    protected $_order;
    
    protected $_canOrder                    = false;
    protected $_canFetchTransactionInfo     = false;
    protected $_canReviewPayment            = false;
    protected $_canCreateBillingAgreement   = false;
    protected $_canManageRecurringProfiles  = false;
    
    protected $_config;
    
    public function __construct()
    {
        parent::__construct();
        $this->_config = $this->getConfig();
    }
    
    public function getOrder()
    {
        if (!$this->_order) {
            $this->_order = $this->getInfoInstance()->getOrder();
        }
        return $this->_order;
    }

    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('zarinpalgateway/processing/redirect', array('_secure' => true));
    }

    protected function _getResponseUrl()
    {
        return Mage::getUrl('zarinpalgateway/processing/response');
    }
    
    public function getResponseUrl()
    {
        return $this->_getResponseUrl();
    }

    public function getUrl()
    {
        return $this->_config->getCgiUrl() . $this->_getAuthority();
    }

    protected function _getAuthority()
    {
        $transaction = Mage::getModel('zarinpalgateway/transaction');
        return $transaction->loadByOrderId($this->getOrder()->getId())->getAuthority();
    }

    public function getConfig()
    {
        if (is_null($this->_config)) {
            $this->_config = Mage::getModel('zarinpalgateway/config');
        }
        return $this->_config;
    }

}
