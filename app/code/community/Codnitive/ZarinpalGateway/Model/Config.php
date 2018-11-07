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

class Codnitive_ZarinpalGateway_Model_Config
{

	protected $_cgiUrl = 'https://www.zarinpal.com/pg/StartPay/';

	const PATH_NAMESPACE      = 'payment';
	const EXTENSION_NAMESPACE = 'zarinpalgateway';

	const EXTENSION_NAME    = 'Zarinpal Online Payment';
	const EXTENSION_VERSION = '1.0.00';
	const EXTENSION_EDITION = 'Basic';

	public static function getNamespace()
	{
		return self::PATH_NAMESPACE . '/' . self::EXTENSION_NAMESPACE . '/';
	}

	public function getExtensionName()
	{
		return self::EXTENSION_NAME;
	}

	public function getExtensionVersion()
	{
		return self::EXTENSION_VERSION;
	}

	public function getExtensionEdition()
	{
		return self::EXTENSION_EDITION;
	}

	public function getCgiUrl()
	{
		return $this->_cgiUrl;
	}

	public function getMerchantId()
	{
		return Mage::getStoreConfig(self::getNamespace().'merchant_id');
	}

	public function getStatus()
	{
		$helper = Mage::helper('zarinpalgateway');
		return array(
				-1   => $helper->getStatus(-1),
				-2   => $helper->getStatus(-2),
				-3   => $helper->getStatus(-3),
				-4   => $helper->getStatus(-4),
				-11   => $helper->getStatus(-11),
				-21   => $helper->getStatus(-21),
				-22   => $helper->getStatus(-22),
				-33   => $helper->getStatus(-33),
				-34   => $helper->getStatus(-34),
				-40   => $helper->getStatus(-40),
				-41   => $helper->getStatus(-41),
				-54   => $helper->getStatus(-54),
				100   => $helper->getStatus(100),
				101   => $helper->getStatus(101)
			);
	}

}
