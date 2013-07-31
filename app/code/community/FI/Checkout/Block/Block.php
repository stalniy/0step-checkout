<?php
/**
 * Zero Step Checkout main block wrapper
 *
 * @category    FI
 * @package     FI_Checkout
 * @author      Sergiy Stotskiy <sergiy.stotskiy@freaksidea.com>
 */
class FI_Checkout_Block_Block extends Mage_Checkout_Block_Onepage_Abstract
{
    protected
        /**
         * Messages block
         *
         * @var Mage_Core_Block_Messages
         */
        $_loginMessagesBlock;

    /**
     * Constructor. Set block template
     */
    protected function _construct()
    {
        $this->setTemplate('freaks/checkout/block.phtml');
    }

    /**
     * Return login url
     *
     * @return string
     */
    public function getLoginUrl()
    {
        return $this->getUrl('fi_order/checkout/login');
    }

    /**
     * Return login messages block
     *
     * @return Mage_Core_Block_Messages
     */
    public function getLoginMessagesBlock()
    {
        if (!$this->_loginMessagesBlock) {
            $this->_loginMessagesBlock = $this->getLayout()->createBlock('core/messages');
            $this->_loginMessagesBlock->addMessages(Mage::getSingleton('customer/session')->getMessages(true));
        }
        return $this->_loginMessagesBlock;
    }

    /**
     * Convert block to html.
     * Does not display if quote items count equals 0
     *
     * @return string
     */
    protected function _toHtml()
    {
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        if (!$quote->getItemsCount()) {
            return '';
        }

        return parent::_toHtml();
    }
}
