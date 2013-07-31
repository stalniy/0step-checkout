<?php
class FI_Checkout_Model_Source_ShippingMethods extends Mage_Adminhtml_Model_System_Config_Source_Shipping_Allmethods
{
    /**
     * Return enabled shipping methods
     *
     * @return array
     */
    public function toOptionArray($showActive = false)
    {
        return parent::toOptionArray(true);
    }

}