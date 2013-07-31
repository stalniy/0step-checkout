<?php
/**
 * Zero Step Checkout Newsletter block
 *
 * @category    FI
 * @package     FI_Checkout
 * @author      Sergiy Stotskiy <sergiy.stotskiy@freaksidea.com>
 * @copyright   Copyright (c) 2012 Sergiy Stotskiy (http://freaksidea.com)
 */
class FI_Checkout_Block_Newsletter extends Mage_Core_Block_Template
{
    /**
     * Convert block to html sting.
     * Checks is possible to show newsletter checkbox
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->helper('fi_checkout')->isVisibleNewsletter()
            || Mage::helper('fi_checkout')->isCustomerSubscribed()
        ) {
            return '';
        }

        return parent::_toHtml();
    }
}
