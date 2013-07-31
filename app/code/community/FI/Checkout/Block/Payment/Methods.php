<?php
/**
 * Zero Step Checkout payment methods block
 *
 * @category    FI
 * @package     FI_Checkout
 * @author      Sergiy Stotskiy <sergiy.stotskiy@freaksidea.com>
 */
class FI_Checkout_Block_Payment_Methods extends Mage_Checkout_Block_Onepage_Payment_Methods
{
    /**
     * Checks if 3D secure enabled for payment method
     *
     * @param Mage_Payment_Model_Method_Abstract $method
     * @return bool
     */
    public function isVerificationRequired(Mage_Payment_Model_Method_Abstract $method)
    {
        $result = $this->helper('fi_checkout')->isCentinelValidationRequired($method);
        $this->setIsCentinelValidationRequired($result);
        return $result;
    }

    /**
     * Return verifier url
     *
     * @param Mage_Payment_Model_Method_Abstract $method
     * @return string
     */
    public function getVerifyCcUrl(Mage_Payment_Model_Method_Abstract $method)
    {
        $verifyUrl = '';
        if ($this->isVerificationRequired($method)) {
            $verifyUrl = $this->getUrl('fi_order/checkout/verify');
        }
        return $verifyUrl;
    }
}
