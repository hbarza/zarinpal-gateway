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

class Codnitive_ZarinpalGateway_Block_Transaction_Success extends Mage_Core_Block_Abstract
{

    protected function _toHtml()
    {
        $successUrl = Mage::getUrl('*/*/success', array('_nosid' => true));
        
        $html =   '<!DOCTYPE html>'
                . '<html>'
                . '<head>'
                . '<meta http-equiv="refresh" content="0; URL=' . $successUrl . '">'
                . '</head>'
                . '<body>'
                . '<p>' . $this->__('Your payment has been successfully processed by our shop system.') . '</p>'
                . '<p>' . $this->__("Please click <a href='%s'>here</a> if you are not redirected automatically.", $successUrl) . '</p>'
                . '</body></html>';

        return $html;
    }

}