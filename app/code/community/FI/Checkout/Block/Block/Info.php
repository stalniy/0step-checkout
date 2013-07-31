<?php
/**
 * Zero Step Checkout user information block
 *
 * @category    FI
 * @package     FI_Checkout
 * @author      Sergiy Stotskiy <sergiy.stotskiy@freaksidea.com>
 */
class FI_Checkout_Block_Block_Info extends Mage_Checkout_Block_Onepage_Abstract
{
    /**
     * Customer shipping address
     *
     * @var Mage_Sales_Model_Quote_Address
     */
    protected $_address;

    /**
     * Customer virtual order. Saved in session
     *
     * @var FI_Checkout_Model_Order
     */
    protected $_order;

    /**
     * Constructor. Set block template.
     * Set user values to block. Update addresses
     */
    protected function _construct()
    {
        $this->_order = $this->helper('fi_checkout')->buildSessionOrder();
        $this->setTemplate('freaks/checkout/block/info.phtml');

        if ($this->isCustomerLoggedIn()) {
            $this->_fillInWithCustomer();
        }
        $this->_fillInWithSession();
        $this->_syncQuoteAddresses();
    }

    /**
     * Fill block values with logged in customer info
     *
     * @return void
     */
    protected function _fillInWithCustomer()
    {
        $customer = $this->getCustomer();
        $address = $this->getAddress();

        $this->addData(array(
            'address_id'      => $address->getCustomerAddressId(),
            'customer_email'  => $customer->getEmail(),
            'customer_region' => $address->getRegion(),
            'customer_city'   => $address->getCity(),
            'customer_building' => $address->getStreet(2),
            'customer_room'   => $address->getStreet(3),
            'customer_phone'  => $address->getTelephone(),
            'customer_zip'    => $address->getPostcode()
        ));
    }

    /**
     * Fill block values with customer session information
     *
     * @return void
     */
    protected function _fillInWithSession()
    {
        $customer = $this->_order->getCustomer();
        $address = $this->_order->getAddress();

        $this->addData(array_filter(array(
            'customer_email'  => $customer->getEmail(),
            'customer_region' => $address->getRegion(),
            'customer_city'   => $address->getCity(),
            'customer_building' => $address->getStreet(1),
            'customer_room'   => $address->getStreet(2),
            'customer_phone'  => $address->getTelephone(),
            'customer_zip'    => $address->getPostcode(),
            'customer_note'   => $customer->getNote()
        )));
    }

    /**
     * Gets customer address if customer is logged in,
     * otherwise creates new one
     *
     * @return Mage_Sales_Model_Quote_Address
     */
    protected function _getOrCreateAddress()
    {
        if ($this->isCustomerLoggedIn()) {
            return $this->getQuote()->getShippingAddress();
        } else {
            return Mage::getModel('sales/quote_address');
        }
    }

    protected function _importCustomerAddressTo(Mage_Sales_Model_Quote_Address $address)
    {
        $customerAddress = $this->getCustomer()->getPrimaryShippingAddress();
        if ($customerAddress) {
            $address->importCustomerAddress($customerAddress)
                ->setSaveInAddressBook(0);
        }
    }

    protected function _syncQuoteAddresses()
    {
        $billingAddress = $this->getQuote()->getBillingAddress();
        $this->_order->exportAddressTo($billingAddress);
        $billingAddress->implodeStreetAddress();

        $this->_order->exportAddressTo($this->getAddress());
        $this->getAddress()->implodeStreetAddress();
    }

    /**
     * Return shipping address
     *
     * @return Mage_Sales_Model_Quote_Address
     */
    public function getAddress()
    {
        if (!$this->_address) {
            $this->_address = $this->_getOrCreateAddress();
            $this->isCustomerLoggedIn() && $this->_importCustomerAddressTo($this->_address);
        }

        return $this->_address;
    }

    /**
     * Return customer full name
     *
     * @return string
     */
    public function getCustomerName()
    {
        return $this->isCustomerLoggedIn()
            ? $this->getFirstname() . ' ' . $this->getLastname()
            : $this->_order->getCustomerName();
    }

    /**
     * Return Customer Address First Name
     * If Sales Quote Address First Name is not defined - return Customer First Name
     *
     * @return string
     */
    public function getFirstname()
    {
        $firstname = $this->getAddress()->getFirstname();
        if (empty($firstname) && $this->getCustomer()) {
            $firstname = $this->getCustomer()->getFirstname();
        }
        return $firstname;
    }

    /**
     * Return Customer Address Last Name
     * If Sales Quote Address Last Name is not defined - return Customer Last Name
     *
     * @return string
     */
    public function getLastname()
    {
        $lastname = $this->getAddress()->getLastname();
        if (empty($lastname) && $this->getCustomer()) {
            return $this->getCustomer()->getLastname();
        }
        return $lastname;
    }

    /**
     * Return address street
     *
     * @return string
     */
    public function getStreet()
    {
        if ($this->isCustomerLoggedIn()) {
            $street = $this->getAddress()->getStreet(1);
        } else {
            $street = $this->_order->getAddress()->getStreet();
            if (is_array($street)) {
                $street = reset($street);
            }
        }

        return $street;
    }

    /**
     * Return customer location (country, region, city)
     *
     * @return string
     */
    public function getCustomerLocation()
    {
        $location = $this->_collectCustomerLocation();
        return join(', ', array_filter($location));
    }

    protected function _collectCustomerLocation()
    {
        $location = array();
        $address = $this->getAddress();

        if (!$this->helper('fi_checkout')->useOnlyDefaultCountry()) {
            $location[] = $address->getCountryModel()->getName();
        }
        if ($address->getRegionId()) {
            $location[] = $address->getRegionModel()->getName();
        } elseif ($address->getRegion()) {
            $location[] = $address->getRegion();
        }
        $location[] = $address->getCity();
        return $location;
    }

    /**
     * Return location help message based on configuration
     *
     * @see FI_Checkout_Helper_Data::useOnlyDefaultCountry
     * @return string
     */
    public function getLocationHelp()
    {
        if ($this->helper('fi_checkout')->useOnlyDefaultCountry()) {
            return $this->__('City');
        } else {
            return $this->__('Country, Region, City (e.g. Ukraine, Kyiv, Kyiv)');
        }
    }

    /**
     * Return country select box block
     *
     * @param string $type
     * @return Mage_Core_Block_Html_Select
     */
    public function getCountryBlock($type = 'shipping')
    {
        $countryId = $this->getAddress()->getCountryId();
        if (!$countryId) {
            $countryId = $this->helper('fi_checkout')->getDefaultCountryId();
        }
        $select = $this->getLayout()->createBlock('core/html_select')
            ->setTitle($this->helper('checkout')->__('Country'))
            ->setClass('validate-select non-storable')
            ->setValue($countryId)
            ->setOptions($this->getCountryOptions());

        return $select;
    }

    /**
     * Return region select box block
     *
     * @param string $type
     * @return Mage_Core_Block_Html_Select
     */
    public function getRegionBlock()
    {
        $select = $this->getLayout()->createBlock('core/html_select')
            ->setTitle($this->helper('checkout')->__('State/Province'))
            ->setClass('required-entry validate-state non-storable')
            ->setValue($this->getAddress()->getRegionId())
            ->setOptions($this->getRegionCollection()->toOptionArray());

        return $select;
    }

    public function getSecureUrl($path)
    {
        return $this->getUrl($path, array('_secure' => true));
    }
}
