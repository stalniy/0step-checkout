<?php
class FI_Checkout_Model_Source_PaymentMethods
{
    /**
     * Return enabled payment methods
     *
     * @return array
     */
    public function toOptionArray()
    {
        $methods = array(array('value'=>'', 'label'=>''));
        $payments = Mage::getSingleton('payment/config')->getActiveMethods();
        foreach ($payments as $paymentCode=>$paymentModel) {
           $paymentTitle = Mage::getStoreConfig('payment/'.$paymentCode.'/title');
           $methods[$paymentCode] = array(
               'label'   => $paymentTitle,
               'value' => $paymentCode,
           );
        }
        return $methods;
    }
}