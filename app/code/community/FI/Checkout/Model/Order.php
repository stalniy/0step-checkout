<?php

class FI_Checkout_Model_Order
{
    protected
        $_scopeName = 'fi_order',
        $_storage,
        $_customer,
        $_address,
        $_payment,
        $_session;

    public function __construct() {
        $this->_address  = new Varien_Object();
        $this->_customer = new Varien_Object();
        $this->_payment  = new Varien_Object();
        $this->_storage  = new Varien_Object();
    }

    protected function _getStorage()
    {
        return $this->_storage->setData($this->_getResource()->getData($this->_scopeName));
    }

    protected function _getResource()
    {
        return $this->_session;
    }

    public function setSession(Mage_Customer_Model_Session $session)
    {
        $this->_session = $session;
        return $this;
    }

    public function __call($method, $args)
    {
        $storage = $this->_getStorage();
        $result = call_user_func_array(array($storage, $method), $args);
        $this->_getResource()->setData($this->_scopeName, $storage->getData());
        return $result;
    }

    public function setData($key, $value = null)
    {
        $storage = $this->_getStorage();
        if (is_array($key)) {
            $storage->addData($key);
        } else {
            $storage->setData($key, $value);
        }
        $this->_getResource()->setData($this->_scopeName, $storage->getData());
        return $this;
    }

    public function getData($key, $index = null)
    {
        return $this->_getStorage()->getData($key, $index);
    }

    public function getAddress()
    {
        if ($this->_address->getData() != $this->getData('user/address')) {
            $this->_address->addData($this->getData('user/address'));
        }
        return $this->_address;
    }

    public function setAddress($address)
    {
        $fields = array('country_id', 'region', 'region_id', 'city', 'postcode', 'street', 'telephone');
        $address = array_intersect_key($address, array_flip($fields));

        $customer = $this->getCustomer()->setAddress($address)->getData();
        $this->setData('user', $customer);
        return $this;
    }

    public function getCustomer()
    {
        if ($this->_customer->getData() != $this->getData('user')) {
            $this->_customer->addData($this->getData('user'));
        }
        $this->_customer->setIsLoggedIn($this->_getResource()->isLoggedIn())
            ->setAddress($this->getAddress());
        return $this->_customer;
    }

    public function getPayment()
    {
        if ($this->_payment->getData() != $this->getData('payment')) {
            $this->_payment->addData($this->getData('payment'));
        }
        return $this->_payment;
    }

    public function setPaymentMethod($method)
    {
        $payment = $this->getPayment()->setMethod($method)->getData();
        $this->setData('payment', $payment);
        return $this;
    }

    public function getCustomerName()
    {
        return $this->getCustomer()->getName();
    }

    public function drop()
    {
        $this->_customer = null;
        $this->_address  = null;
        $this->_payment  = null;
        $this->_storage  = null;
        $this->_getResource()->unsetData($this->_scopeName);
    }

    /**
     * Update Shipping Address based on session and default config values.
     *
     * @param  Mage_Sales_Model_Quote_Address $address
     * @return FI_Checkout_Model_Order
     */
    public function exportAddressTo(Mage_Sales_Model_Quote_Address $address)
    {
        $shippingMethod = $this->getShippingMethod();
        if ($shippingMethod) {
            $address->setShippingMethod($shippingMethod)
                ->setCollectShippingRates(true);
        }

        $orderAddress = $this->getData('user/address');
        if (is_array($orderAddress)) {
            unset($orderAddress['location']);
            $address->addData($orderAddress);
        }
        return $this;
    }

    /**
     * Auto assign shipping method if there is only one available method
     *
     * @param Mage_Sales_Model_Quote_Address $address
     * @return FI_Checkout_Model_Order
     */
    public function detectShippingMethodFor(Mage_Sales_Model_Quote_Address $address)
    {
        $rates = $address->getAllShippingRates();
        if (count($rates) == 1) {
            $rate = reset($rates);
            $this->setShippingMethod($rate->getCode());
        }
        return $this;
    }

    public function updateAddressWith($data)
    {
        $newAddress = $this->getAddress()->addData($data)->getData();
        $this->setAddress($newAddress);
        return $this;
    }

    public function assembleAddress()
    {
        $address = $this->getData('user/address');
        $address['id'] = $this->getAddress()->getId();
        unset($address['location']);

        $fullName = trim($this->getCustomerName());
        $fullName = array_filter(explode(' ', $fullName, 2), 'trim') + array_fill(0, 2, null);
        list($address['firstname'], $address['lastname']) = $fullName;

        $address['email'] = $this->getCustomer()->getEmail();
        $address['use_for_shipping']  = true;
        return $address;
    }

    public function exportPaymentTo(Mage_Sales_Model_Quote_Payment $payment)
    {
        $resource = $this->getPayment();
        if (!$resource->getMethod()) {
            return $this;
        }

        $countryId = $this->getAddress()->getCountryId();
        $payment->setMethod($resource->getMethod());
        $method = $payment->getMethodInstance();
        if (!$method->isAvailable($payment->getQuote()) || !$method->canUseForCountry($countryId)) {
            $payment->unsMethod();
        }
        return $this;
    }
}
