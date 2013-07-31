<?php
/**
 * Zero Step Checkout observer model
 *
 * @category    FI
 * @package     FI_Checkout
 * @author      Sergiy Stotskiy <sergiy.stotskiy@freaksidea.com>
 */
class FI_Checkout_Model_Observer
{
    /**
     * Restore shipping method from session or auto assign if there is only one available method.
     * Listen event sales_quote_collect_totals_before
     *
     * @param Varien_Event_Observer $observer
     * @return FI_Checkout_Model_Observer
     */
    public function collectTotalsBefore(Varien_Event_Observer $observer)
    {
        $quote   = $observer->getEvent()->getQuote();
        $address = $quote->getShippingAddress();

        Mage::helper('fi_checkout')->buildSessionOrder()
            ->exportAddressTo($address)
            ->exportAddressTo($quote->getBillingAddress())
            ->detectShippingMethodFor($address)
            ->exportAddressTo($address)
            ->exportPaymentTo($quote->getPayment())
            ;
        return $this;
    }

    /**
     * Add user comment to order.
     * Listen event checkout_type_onepage_save_order
     *
     * @param Varien_Event_Observer $observer
     * @return FI_Checkout_Model_Observer
     */
    public function addOrderComment(Varien_Event_Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $request = Mage::app()->getRequest();

        $data = $request->getParam('user');
        if (!empty($data['note'])) {
            $comment = strip_tags($data['note']);
            if (!empty($comment)) {
                $order->setCustomerNote($comment);
            }
        }
        return $this;
    }

    /**
     * Create invoice if possible to process zero sub total
     * Listen event checkout_submit_all_after
     *
     * @param Varien_Event_Observer $observer
     * @return FI_Checkout_Model_Observer
     */
    public function tryToProcessOrder(Varien_Event_Observer $observer)
    {
        $helper  = Mage::helper("payment");
        $order   = $observer->getEvent()->getOrder();
        $storeId = $order->getStoreId();
        $zeroSubTotalPaymentAction = $helper->getZeroSubTotalPaymentAutomaticInvoice($storeId);

        if ($helper->isZeroSubTotal($storeId)
            && $order->getGrandTotal() == 0
            && $zeroSubTotalPaymentAction == Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE
            && $helper->getZeroSubTotalOrderStatus($storeId) == 'pending'
        ) {
            $invoice = $this->_buildInvoiceFor($order);
            $invoice->getOrder()->setIsInProcess(true);
            $invoice->save();
        }
        return $this;
    }

    /**
     * Create invoice
     *
     * @param  Mage_Sales_Model_Order $order
     * @return Mage_Sales_Model_Order_Invoice
     */
    protected function _buildInvoiceFor($order)
    {
        $items = array();
        foreach ($order->getAllItems() as $item) {
            $items[$item->getId()] = $item->getQtyOrdered();
        }
        $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice($items);
        $invoice->setEmailSent(true)->register();

        return $invoice;
    }
}
