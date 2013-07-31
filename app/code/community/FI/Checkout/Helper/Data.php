<?php
/**
 * Zero Step Checkout helper
 *
 * @category    FI
 * @package     FI_Checkout
 * @author      Sergiy Stotskiy <sergiy.stotskiy@freaksidea.com>
 * @copyright   Copyright (c) 2012 Sergiy Stotskiy (http://freaksidea.com)
 */
class FI_Checkout_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Xml configuration pathes
     */
    const XML_PATH_DEFAULT_SHIPPING_METHOD = 'checkout/easyco/default_shipping_method';
    const XML_PATH_DEFAULT_PAYMENT_METHOD  = 'checkout/easyco/default_payment_method';
    const XML_PATH_DEFAULT_COUNTRY = 'checkout/easyco/default_country';
    const XML_PATH_DEFAULT_REGION  = 'checkout/easyco/default_region';
    const XML_PATH_DEFAULT_CITY    = 'checkout/easyco/default_city';
    const XML_PATH_ALLOW_GUEST_CO  = 'checkout/easyco/allow_guest_checkout';
    const XML_PATH_USE_DEFAULT_COUNTRY = 'checkout/easyco/use_default_country';
    const XML_PATH_PASSWORD_FIELD_TYPE = 'checkout/easyco/password_type';
    const XML_PATH_LOCATION_FIELD_TYPE = 'checkout/easyco/location_type';
    const XML_PATH_SHOW_BUILD_FIELDS   = 'checkout/easyco/show_build_fields';
    const XML_PATH_MAY_SUBSCRIBE_GUEST ='newsletter/subscription/allow_guest_subscribe';
    const XML_PATH_SHOW_NEWSLETTER     = 'checkout/easyco/show_newsletter';
    const XML_PATH_NEWSLETTER_SEND_SUCCESS_EMAIL = 'checkout/easyco/newsletter_send_success';
    const XML_PATH_NEWSLETTER_SEND_REQUEST_EMAIL = 'checkout/easyco/newsletter_send_request';
    const XML_PATH_PAYMENT_DEPENDENT_SECTIONS  = 'checkout/easyco/payment_dependent_sections';
    const XML_PATH_SHIPPING_DEPENDENT_SECTIONS = 'checkout/easyco/shipping_dependent_sections';

    /**
     * Return attribute options
     *
     * @param string $entityType
     * @param string $attrCode
     * @param bool   $withEmpty
     * @return array
     */
    public function getAttributeOptions($entityType, $attrCode, $withEmpty = false)
    {
        $data = Mage::getModel('eav/entity_attribute')
            ->loadByCode($entityType, $attrCode)
            ->setSourceModel('eav/entity_attribute_source_table')
            ->getSource()
            ->getAllOptions($withEmpty);

        $set = array();
        foreach ($data as $row) {
            $set[$row['value']] = $row['label'];
        }

        return $set;
    }

    /**
     * Return default shipping address
     *
     * @return array
     */
    public function getDefaultShippingAddress()
    {
        $address = array(
            'country_id' => $this->getDefaultCountryId(),
            'region'     => $this->getDefaultRegion(),
            'region_id'  => null,
            'city'       => $this->getDefaultCity()
        );

        $address['location'] = join(', ', array($address['country_id'], $address['region'], $address['city']));
        return $this->parseLocationOf(new Varien_Object($address));
    }

    /**
     * Lower case text using mb_string
     *
     * @param string $text
     * @return string
     */
    public function lowerCase($text)
    {
        if (function_exists('mb_convert_case')) {
            $text = mb_convert_case($text, MB_CASE_LOWER, Mage_Core_Helper_String::ICONV_CHARSET);
        }
        return $text;
    }

    /**
     * Return default city
     *
     * @return string
     */
    public function getDefaultCity()
    {
        return Mage::getStoreConfig(self::XML_PATH_DEFAULT_CITY);
    }

    /**
     * Return default region
     *
     * @return string
     */
    public function getDefaultRegion()
    {
        return Mage::getStoreConfig(self::XML_PATH_DEFAULT_REGION);
    }

    /**
     * Return default country code
     *
     * @return string
     */
    public function getDefaultCountryId()
    {
        return Mage::getStoreConfig(self::XML_PATH_DEFAULT_COUNTRY);
    }

    /**
     * Return choosen location type
     *
     * @return string
     */
    public function getLocationType()
    {
        return Mage::getStoreConfig(self::XML_PATH_LOCATION_FIELD_TYPE);
    }

    /**
     * Return choosen password type
     *
     * @return string
     */
    public function getPasswordType()
    {
        return Mage::getStoreConfig(self::XML_PATH_PASSWORD_FIELD_TYPE);
    }

    /**
     * Check possibility to show password field on checkout form
     *
     * @return bool
     */
    public function canShowPasswordField()
    {
        return $this->getPasswordType() == FI_Checkout_Model_Source::PASSWORD_FIELD;
    }

    /**
     * Checks possibility to use telephone field like password
     *
     * @return bool
     */
    public function isPasswordAsTelephone()
    {
        return $this->getPasswordType() == FI_Checkout_Model_Source::PASSWORD_PHONE;
    }

    /**
     * Checks possibility to automatically generate password
     *
     * @return bool
     */
    public function isPasswordAuto()
    {
        return $this->getPasswordType() == FI_Checkout_Model_Source::PASSWORD_GENERATE;
    }

    /**
     * Check is checkout only for one default country
     *
     * @return bool
     */
    public function useOnlyDefaultCountry()
    {
        return Mage::getStoreConfig(self::XML_PATH_USE_DEFAULT_COUNTRY);
    }

    /**
     * Check is country/region/city in one field
     *
     * @return bool
     */
    public function isLocationAsOneField()
    {
        return $this->getLocationType() == FI_Checkout_Model_Source::LOCATION_ONE;
    }

    /**
     * Check posibility to place order
     *
     * return bool
     */
    public function canPlaceOrder()
    {
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        return $quote->validateMinimumAmount();
    }

    /**
     * Generate random string
     *
     * @param int $len
     * return string
     */
    public function generateRandomKey($len = 20){
        $string = '';
        $pool   = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        for ($i = 1; $i <= $len; $i++)
          $string .= substr($pool, rand(0, 61), 1);

        return $string;
    }

    /**
     * Return password based on choosen type
     *
     * @param Varien_Object $customer
     * @return string
     */
    public function getPasswordFor(Varien_Object $customer)
    {
        $password = '';
        switch ($this->getPasswordType()) {
            case FI_Checkout_Model_Source::PASSWORD_FIELD:
                $password = $customer->getPassword();
                break;
            case FI_Checkout_Model_Source::PASSWORD_PHONE:
                $password = $customer->getAddress()->getTelephone();
                $size = strlen($password);
                if ($size < 6) {
                    $password .= $this->generateRandomKey(6 - $size);
                }
                break;
        }

        return empty($password) ? $this->generateRandomKey(8) : $password;
    }

    /**
     * Check is 3D secure validation required
     *
     * @param Mage_Payment_Model_Method_Abstract $paymentMethod
     * @return bool
     */
    public function isCentinelValidationRequired(Mage_Payment_Model_Method_Abstract $paymentMethod)
    {
        $result = false;
        if ($paymentMethod->getIsCentinelValidationEnabled()) {
            $centinel = $paymentMethod->getCentinelValidator();
            $result   = is_object($centinel);
        }
        return $result;
    }

    /**
     * Check is room and building fields enabled
     *
     * @return bool
     */
    public function showBuildRoomFields()
    {
        return Mage::getStoreConfig(self::XML_PATH_SHOW_BUILD_FIELDS);
    }

    /**
     * Check is guest checkout allowed
     * for future usage
     *
     * @return bool
     */
    public function isGuestCheckoutAllowed()
    {
        return Mage::getStoreConfig(self::XML_PATH_ALLOW_GUEST_CO);
    }

    /**
     * Return newsletter type
     *
     * @return string
     */
    public function getNewsletterType()
    {
        return Mage::getStoreConfig(self::XML_PATH_SHOW_NEWSLETTER);
    }

    /**
     * Check is newsletter visible on checkout page
     *
     * @return bool
     */
    public function isVisibleNewsletter()
    {
        return $this->getNewsletterType() != FI_Checkout_Model_Source::CHECKBOX_UNVISIBLE;
    }

    /**
     * Check is newsletter checkbox checked
     *
     * @return bool
     */
    public function isNewsletterChecked()
    {
        return $this->getNewsletterType() == FI_Checkout_Model_Source::CHECKBOX_CHECKED;
    }

    /**
     * Check is guest may subscribe to newsletter
     *
     * @return bool
     */
    public function maySubscribeGuest()
    {
        return Mage::getStoreConfig(self::XML_PATH_MAY_SUBSCRIBE_GUEST);
    }

    /**
     * Check is customer subscribed
     *
     * @return bool
     */
    public function isCustomerSubscribed()
    {
        $session = Mage::getSingleton('customer/session');
        if (!$this->isVisibleNewsletter()
            || !$session->isLoggedIn()
            && !$this->maySubscribeGuest()
        ) {
            return false;
        }

        $subscriber = Mage::getModel('newsletter/subscriber')->loadByCustomer($session->getCustomer());
        return $subscriber->isSubscribed();
    }

    /**
     * Check is need to send email to customer about subscription
     *
     * @return bool
     */
    public function isNeedSendNewsletterEmail($type)
    {
        switch ($type) {
            case 'request':
                return Mage::getStoreConfig(self::XML_PATH_NEWSLETTER_SEND_REQUEST_EMAIL);
            case 'success':
                return Mage::getStoreConfig(self::XML_PATH_NEWSLETTER_SEND_SUCCESS_EMAIL);
        }
        return true;
    }

    public function explodeLocation($location)
    {
        if ($this->useOnlyDefaultCountry()) {
            $location = $this->getDefaultCountryId() . ',' . $location;
        }
        $location = array_map('trim', explode(',', $location));
        return $location + array_fill(0, 3, null);
    }

    /**
     * Parse address information according to the config
     *
     * @param Varien_Object $address
     * @return array
     */
    public function parseLocationOf(Varien_Object $address)
    {
        $earth  = Mage::getResourceModel('fi_checkout/countries');
        $data   = array(
            'country_id' => null,
            'region'     => null,
            'region_id'  => null,
            'city'       => null,
            'postcode'   => $address->getData('postcode')
        );

        if ($this->isLocationAsOneField()) {
            list($country, $region, $city) = $this->explodeLocation($address->getData('location'));

            if (!empty($country)) {
                $data['country_id'] = $earth->getCountryCodeByName($country);

                if (!empty($region)) {
                    $data['city'] = $data['region'] = $region;
                }

                if (!empty($city)) {
                    $data['city'] = $city;
                }
            }
        } else {
            $data['country_id'] = $address->getData('country_id');
            $data['region'] = $address->getData('region');
            $data['region_id'] = $address->getData('region_id');
            $data['city']   = $address->getData('city');
        }

        if ($this->useOnlyDefaultCountry() || empty($data['country_id'])) {
            $data['country_id'] = $this->getDefaultCountryId();
        }

        if (!empty($data['region']) && empty($data['region_id'])) {
            $data['region_id'] = $earth->getRegionIdByName(trim($data['region']), $data['country_id']);
            if ($data['region_id']) {
                $data['region'] = null;
            }
        }

        return $data;
    }

    /**
     * Prepare address information from request
     *
     * @param FI_Checkout_Model_Order $order
     * @return array
     */
    public function extractAddressFrom(FI_Checkout_Model_Order $order)
    {
        $address = $order->assembleAddress();

        if (!$order->getCustomer()->getIsLoggedIn()) {
            $password = $this->getPasswordFor($order->getCustomer());
            $address['customer_password'] = $password;
            $address['confirm_password']  = $password;
        }
        return $address;
    }

    public function getDefaultShippingMethod()
    {
        return Mage::getStoreConfig(self::XML_PATH_DEFAULT_SHIPPING_METHOD);
    }

    public function getDefaultPaymentMethod()
    {
        return Mage::getStoreConfig(self::XML_PATH_DEFAULT_PAYMENT_METHOD);
    }

    public function paymentDependentSections()
    {
        return Mage::getStoreConfig(self::XML_PATH_PAYMENT_DEPENDENT_SECTIONS);
    }

    public function shippingDependentSections()
    {
        return Mage::getStoreConfig(self::XML_PATH_SHIPPING_DEPENDENT_SECTIONS);
    }

    public function buildSessionOrder()
    {
        $session = Mage::getSingleton('customer/session');
        $order   = Mage::getModel('fi_checkout/order')->setSession($session);
        if ($order->getAddress()->isEmpty()) {
            if ($session->isLoggedIn() && $session->getCustomer()->getPrimaryShippingAddress()) {
                $order->setAddress($session->getCustomer()->getPrimaryShippingAddress()->getData());
            } else {
                $order->setAddress($this->getDefaultShippingAddress());
            }
        }

        if (!$order->getPayment()->getMethod()) {
            $order->setPaymentMethod($this->getDefaultPaymentMethod());
        }
        if (!$order->getShippingMethod()) {
            $order->setShippingMethod($this->getDefaultShippingMethod());
        }
        return $order;
    }

    public function getActivePaymentMethods()
    {
        $methods = array();
        foreach (Mage::getStoreConfig('payment') as $code => $config) {
            if (Mage::getStoreConfigFlag('payment/' . $code . '/active')) {
                $methods[$code] = $config['title'];
            }
        }
        return $methods;
    }

    public function getUpdateUrl() {
      return $this->_getUrl('fi_order/checkout/update', array('_secure' => true));
    }

    public function getPlaceUrl() {
      return $this->_getUrl('fi_order/checkout/place', array('_secure' => true));
    }
}
