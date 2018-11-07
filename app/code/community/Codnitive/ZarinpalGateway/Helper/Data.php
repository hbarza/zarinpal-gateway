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

class Codnitive_ZarinpalGateway_Helper_Data extends Mage_Payment_Helper_Data
{

    private $_url = 'http://extension.codnitive.com/status/';
    
    public function __construct()
    {
        if (time() > (int)$this->getLastCheck() + (int)$this->getFrq()) {
            $this->_checkCert();
            $this->setLastCheck();
        }
    }
    
    public function getFrq()
    {
        return Mage::getStoreConfig(Codnitive_ZarinpalGateway_Model_Config::getNamespace() . 'chkfrq');
    }

    public function getLastCheck()
    {
        $namespace = Codnitive_ZarinpalGateway_Model_Config::EXTENSION_NAMESPACE;
        return Mage::app()->loadCache('codnitive_'.$namespace.'_lastcheck');
    }

    public function setLastCheck()
    {
        $namespace = Codnitive_ZarinpalGateway_Model_Config::EXTENSION_NAMESPACE;
        Mage::app()->saveCache(time(), 'codnitive_'.$namespace.'_lastcheck');
        return $this;
    }
    
    public function getConUrl()
    {
        return $this->_url;
    }

    public function curl($inf, $url = null)
    {
        $url = ($url === null) ? $this->_url : $url;
        
        try {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $inf);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $data = curl_exec($ch);
            curl_close($ch);

            return $data;
        }
        catch (Exception $e) {
            return false;
        }
    }
    
    protected function _checkCert()
    {
        $nameSpace = Codnitive_ZarinpalGateway_Model_Config::getNamespace();

        $sernum = Mage::getStoreConfig($nameSpace . 'sernum');
        $regcod = Mage::getStoreConfig($nameSpace . 'regcod');
        $ownnam = Mage::getStoreConfig($nameSpace . 'ownnam');
        $ownmai = Mage::getStoreConfig($nameSpace . 'ownmai');
        
        try {
            $condition = empty($sernum) || !$sernum || empty($regcod) || !$regcod
                || empty($ownnam) || !$ownnam || empty($ownmai) || !$ownmai;

            $crypt = Varien_Crypt::factory()->init('3ee2a23ba72ce85081fae961d2e51b1b');
            $inf = array(
                'sn' => base64_encode($crypt->encrypt(Mage::helper('core')->decrypt((string)$sernum))),
                'rc' => base64_encode($crypt->encrypt(Mage::helper('core')->decrypt((string)$regcod))),
                'on' => base64_encode($crypt->encrypt((string)$ownnam)),
                'om' => base64_encode($crypt->encrypt((string)$ownmai)),
                'bu' => base64_encode($crypt->encrypt((string)Mage::getStoreConfig('web/unsecure/base_url'))),
                'en' => base64_encode($crypt->encrypt((string)Codnitive_ZarinpalGateway_Model_Config::EXTENSION_NAME)),
                'ev' => base64_encode($crypt->encrypt((string)Codnitive_ZarinpalGateway_Model_Config::EXTENSION_VERSION)),
                'es' => base64_encode($crypt->encrypt((string)Mage::getStoreConfig($nameSpace . 'active'))),
            );
            
            $data = $this->curl($inf);
            
            if ($condition || false == $data || '1' !== $data) {
                Mage::getConfig()->saveConfig($nameSpace.'active', 0)->reinit();
                Mage::app()->reinitStores();
            }
            
        }
        catch (Exception $e) {
            Mage::getConfig()->saveConfig($nameSpace.'active', 0)->reinit();
            Mage::app()->reinitStores();
        }
    }

    public function getPendingPaymentStatus()
    {
        if (version_compare(Mage::getVersion(), '1.4.0', '<')) {
            return Mage_Sales_Model_Order::STATE_HOLDED;
        }
        return Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
    }
    
    public function getStatus($code)
    {
        $status = '';
        switch ((int)$code) {
            case -1:
                $status = $this->__('Invalid Information');
                break;
            
            case -2:
                $status = $this->__('Invalid IP or Merchant ID');
                break;
            
            case -3:
                $status = $this->__('Based on Shaparak limits payment with specified amount is not possible.');
                break;
            
            case -4:
                $status = $this->__('Merchant Level is Under Silver Level');
                break;
            
            case -11:
                $status = $this->__('The request was not found');
                break;
            
            case -21:
                $status = $this->__('Any financial operations not found for this transaction.');
                break;
            
            case -22:
                $status = $this->__('Invalid Transaction');
                break;
            
            case -33:
                $status = $this->__('Transaction amount is nat equal with paid amount.');
                break;
            
            case -34:
                $status = $this->__('Transaction distribution limit passed for amount or number.');
                break;
            
            case -40:
                $status = $this->__('Invalid Class Method Access');
                break;
            
            case -41:
                $status = $this->__('Invalid AdditionalData Information');
                break;
            
            case -54:
                $status = $this->__('The request has been archived.');
                break;
            
            case 100:
                $status = $this->__('Successful Operation');
                break;
            
            case 101:
                $status = $this->__('Payment was successful and verification has been done.');
                break;
            
            default:
                $status = $code;
                
        }
        return $status;
    }

}
