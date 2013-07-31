<?php
/**
 * Zero Step Checkout source model
 *
 * @category    FI
 * @package     FI_Checkout
 * @author      Sergiy Stotskiy <sergiy.stotskiy@freaksidea.com>
 */
class FI_Checkout_Model_Source
{
    /**
     * Constants for password types
     */
    const PASSWORD_FIELD    = 'field';
    const PASSWORD_GENERATE = 'generate';
    const PASSWORD_PHONE    = 'phone';

    /**
     * Constants for location types
     */
    const LOCATION_ONE = 'one';
    const LOCATION_FEW = 'few';

    /**
     * Constants for checkbox types
     */
    const CHECKBOX_UNVISIBLE = 'unvisible';
    const CHECKBOX_UNCHECKED = 'unchecked';
    const CHECKBOX_CHECKED   = 'checked';

    /**
     * Constants for dependent sections
     */
    const SECTION_NONE     = "";
    const SECTION_TOTALS   = 'totals';
    const SECTION_SHIPPING = 'shipping';
    const SECTION_PAYMENT  = 'payment';

    /**
     * Return a list of password types
     *
     * @return array
     */
    public function getPasswordTypes()
    {
        return array(
            self::PASSWORD_FIELD     => Mage::helper('fi_checkout')->__('Password Field on Checkout'),
            self::PASSWORD_PHONE     => Mage::helper('fi_checkout')->__('Password as Telephone Field'),
            self::PASSWORD_GENERATE  => Mage::helper('fi_checkout')->__('Auto Generate Password')
        );
    }

    /**
     * Return a list of location types
     *
     * @return array
     */
    public function getLocationTypes()
    {
        return array(
            self::LOCATION_ONE => Mage::helper('fi_checkout')->__('Country, Region, City as One Field'),
            self::LOCATION_FEW => Mage::helper('fi_checkout')->__('Country, Region, City as Different Fields')
        );
    }

    /**
     * Return a list of checkbox types
     *
     * @return array
     */
    public function getCheckboxTypes()
    {
        return array(
            self::CHECKBOX_UNVISIBLE => Mage::helper('fi_checkout')->__('Not Visible'),
            self::CHECKBOX_UNCHECKED => Mage::helper('fi_checkout')->__('Visible, Unchecked'),
            self::CHECKBOX_CHECKED   => Mage::helper('fi_checkout')->__('Visible, Checked')
        );
    }

    /**
     * Return dependent sections of shipping method
     *
     * @return array
     */
    public function getShippingDependentSections()
    {
        return array(
            self::SECTION_NONE       => Mage::helper('fi_checkout')->__('Nothing'),
            self::SECTION_TOTALS     => Mage::helper('fi_checkout')->__('Totals'),
            self::SECTION_TOTALS . ',' . self::SECTION_PAYMENT => Mage::helper('fi_checkout')->__('Payment Methods, Totals')
        );
    }

    /**
     * Return dependent sections of payment method
     *
     * @return array
     */
    public function getPaymentDependentSections()
    {
        return array(
            self::SECTION_NONE       => Mage::helper('fi_checkout')->__('Nothing'),
            self::SECTION_TOTALS     => Mage::helper('fi_checkout')->__('Totals'),
            self::SECTION_TOTALS . ',' . self::SECTION_SHIPPING => Mage::helper('fi_checkout')->__('Shipping Methods, Totals')
        );
    }
}
