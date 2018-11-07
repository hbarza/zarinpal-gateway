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

$installer = $this;

$installer->startSetup();

$table = $installer->getConnection()
    ->newTable($installer->getTable('zarinpalgateway/transaction'))
    ->addColumn('value_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity' => true,
        'unsigned' => true,
        'nullable' => false,
        'primary'  => true,
        ), 'Entity Id')
    ->addColumn('order_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        ), 'Order ID')
    ->addColumn('order_real_id', Varien_Db_Ddl_Table::TYPE_TEXT, 50, array(
        'NULLABLE'  => true,
        ), 'Order Increment ID as Transaction Reservation Number')
    ->addColumn('authority', Varien_Db_Ddl_Table::TYPE_TEXT, 36, array(
        'NULLABLE'  => true,
        ), 'Transaction Authority')
    ->addColumn('request_status', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'NULLABLE'  => true,
        ), 'Payment Request Status Code')
    ->addColumn('transaction_status', Varien_Db_Ddl_Table::TYPE_TEXT, 4, array(
        'NULLABLE'  => true,
        ), 'Transaction Payment Status')
    ->addColumn('verification_status', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'NULLABLE'  => true,
        ), 'Payment Verification Status Code')
    ->addColumn('transaction_reference_id', Varien_Db_Ddl_Table::TYPE_TEXT, 32, array(
        'NULLABLE'  => true,
        ), 'Transaction Reference ID')
    ->addIndex(
        $installer->getIdxName('zarinpalgateway/transaction',
            array('authority'),
            Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
        ),
        array('authority'),
        array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE))
    ->addIndex($installer->getIdxName('zarinpalgateway/transaction', array('order_id')),
        array('order_id'))
    ->addForeignKey($installer->getFkName('zarinpalgateway/transaction', 'order_id', 'sales/order', 'entity_id'),
        'order_id', $installer->getTable('sales/order'), 'entity_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
    ->setComment('Zarinpal Gateway Payment Inforamtion');
$installer->getConnection()->createTable($table);

$installer->endSetup();