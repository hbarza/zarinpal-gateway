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

class Codnitive_ZarinpalGateway_Model_Transaction extends Mage_Core_Model_Abstract
{

	const SOAP_CLIENT_URL = 'https://ir.zarinpal.com/pg/services/WebGate/wsdl';

	protected $_config;

	protected function _construct()
	{
		$this->_init('zarinpalgateway/transaction');
		$this->_config = Mage::getModel('zarinpalgateway/config');
	}

	public function loadByOrderId($orderId)
	{
		$this->_getResource()->loadByOrderId($this, $orderId);
		return $this;
	}

	public function loadByAuthority($authority)
	{
		$this->_getResource()->loadByAuthority($this, $authority);
		return $this;
	}

	protected function _connect()
	{
		try {
			$client = new SoapClient(self::SOAP_CLIENT_URL, array('trace' => 1, 'encoding' => 'UTF-8'));

			if (!$client) {
                $error = 'SoapClient connect error';
				Mage::log($error, null, 'codnitive_' . Mage::getModel('zarinpalgateway/zpgw')->getCode() . '_payment_zpgw.log', true);
				Mage::throwException($error);
				return false;
			}

			return $client;
		}
		catch (Mage_Core_Exception $e) {
			Mage::log($e->getMessage(), null, 'codnitive_' . Mage::getModel('zarinpalgateway/zpgw')->getCode() . '_payment_zpgw.log', true);
			return false;
		}
		catch (Exception $e){
			Mage::log($e->getMessage(), null, 'codnitive_' . Mage::getModel('zarinpalgateway/zpgw')->getCode() . '_payment_zpgw.log', true);
			Mage::logException($e);
			return false;
		}
	}

	public function paymentRequest($order)
	{
		$client = $this->_connect();
		if (!$client) {
			return false;
		}
        
        $args = array(
            'MerchantID'  => (string) $this->_config->getMerchantId(),
            'Amount'      => (int) number_format($order->getGrandTotal(), 0, '', ''),
            'Description' => Mage::helper('zarinpalgateway')->__('Order #: %s', $order->getIncrementId()),
            'CallbackURL' => (string) Mage::getModel('zarinpalgateway/zpgw')->getResponseUrl()
        );
        
        $result = $client->PaymentRequest($args);
        
		$this->setOrderId($order->getId())
				->setOrderRealId($order->getIncrementId())
				->setAuthority($result->Authority)
				->setRequestStatus($result->Status)
				->save();

		return $result;
	}

	public function verifyRequest($response, $grandTotal)
	{
		$client = $this->_connect();
		if (!$client) {
			return false;
		}
        
        $args = array(
            'MerchantID' => (string) $this->_config->getMerchantId(),
            'Authority'  => (string) $response['Authority'],
            'Amount'     => (int) number_format($grandTotal, 0, '', ''),
        );
        
        $result = $client->PaymentVerification($args);
        
		$this->loadByAuthority($response['Authority']);
		if (!$this->getId()) {
			return false;
		}

		$this->setVerificationStatus($result->Status)
            ->setTransactionReferenceId($result->RefID)
			->save();

		return $result;
	}

}