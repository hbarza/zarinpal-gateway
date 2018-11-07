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

class Codnitive_ZarinpalGateway_Model_Resource_Transaction extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('zarinpalgateway/transaction', 'value_id');
    }

    public function loadByOrderId(Codnitive_ZarinpalGateway_Model_Transaction $transaction, $orderId)
    {
        $adapter = $this->_getReadAdapter();
        $bind    = array('order_id' => $orderId);
        $select  = $adapter->select()
            ->from($this->getTable('zarinpalgateway/transaction'))
            ->where('order_id = ?',$orderId);

        $transactionId = $adapter->fetchOne($select, $bind);
        if ($transactionId) {
            $this->load($transaction, $transactionId);
        } else {
            $transaction->setData(array());
        }

        return $this;
    }
    
    public function loadByAuthority(Codnitive_ZarinpalGateway_Model_Transaction $transaction, $authority)
    {
        $adapter = $this->_getReadAdapter();
        $bind    = array('authority' => $authority);
        $select  = $adapter->select()
            ->from($this->getTable('zarinpalgateway/transaction'))
            ->where('authority = ?',$authority);

        $transactionId = $adapter->fetchOne($select, $bind);
        if ($transactionId) {
            $this->load($transaction, $transactionId);
        } else {
            $transaction->setData(array());
        }

        return $this;
    }
}