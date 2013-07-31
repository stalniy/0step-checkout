<?php
/**
 * Zero Step Checkout Counrty/region helper model
 *
 * @category    FI
 * @package     FI_Checkout
 * @author      Sergiy Stotskiy <sergiy.stotskiy@freaksidea.com>
 */
class FI_Checkout_Model_Resource_Countries extends Mage_Directory_Model_Mysql4_Country_Collection
{
    protected
        /**
         * Table name of region locale names
         *
         * @var string
         */
        $_regionNameTable,

        /**
         * Table name of region
         *
         * @var string
         */
        $_regionTable;

    /**
     * Constructor. Assign table names to properties
     */
    public function __construct()
    {
        parent::__construct();

        $r = Mage::getSingleton('core/resource');
        $this->_countryTable    = $r->getTableName('directory/country');
        $this->_regionTable     = $r->getTableName('directory/country_region');
        $this->_regionNameTable = $r->getTableName('directory/country_region_name');

        $this->setItemObjectClass('Varien_Object');
    }

    /**
     * Add region names to collection
     *
     * @return FI_Checkout_Model_Resource_Countries
     */
    public function addRegionNames()
    {
        $this->getSelect()
            ->joinLeft(
                array('r' => $this->_regionTable),
                'country.country_id = r.country_id',
                array('default_name')
            )
            ->joinInner(
                array('rn' => $this->_regionNameTable),
                $this->getSelect()->getAdapter()->quote('rn.region_id = r.region_id AND locale = ?', Mage::app()->getLocale()->getLocaleCode()),
                array('name')
            );

        return $this;
    }

    /**
     * Filter collection of country/regions by specific value.
     * Uses for autocomplete.
     *
     * @param string $location
     * @param int $limit
     * @return array
     */
    public function search($location, $limit = 15)
    {
        list($countryName, $regionName, $cityName) = $location;

        if (empty($countryName)) {
            return array();
        }

        if (empty($regionName)) {
            $term = $this->_downcase($countryName);
            return $this->_searchOverCountries($term, $limit);
        }

        if (empty($cityName)) {
            return $this->_searchOverRegions($regionName, $countryName, $limit);
        }

        // no autocomplete for city
        return array();
    }

    /**
     * Get region id by name
     *
     * @param string $name
     * @param string $countryId
     * @return int|null
     */
    public function getRegionIdByName($name, $countryId = null)
    {
        $adapter = $this->getSelect()->getAdapter();
        $select  = $adapter->select()
            ->from(array('r' => $this->_regionTable), array('default_name', 'region_id'))
            ->joinLeft(
                array('rn' => $this->_regionNameTable),
                $adapter->quote('rn.region_id = r.region_id AND locale = ?', Mage::app()->getLocale()->getLocaleCode()),
                array('name')
            )
            ->where('name = ? OR default_name = ?', $name, $name);

        if ($countryId) {
            $select->where('r.country_id = ?', $countryId);
        }

        $row = $adapter->fetchRow($select);
        return empty($row['region_id']) ? null : $row['region_id'];
    }

    public function getCountryCodeByName($countryName)
    {
        $countries = array_flip(array_map(array($this, '_downcase'), $this->_getAllCountries()));

        $countryName = $this->_downcase($countryName);
        if (isset($countries[$countryName])) {
            return $countries[$countryName];
        } else {
            return Mage::helper('fi_checkout')->getDefaultCountryId();
        }
    }

    protected function _searchOverCountries($term, $limit)
    {
        $countryNames = array();
        $helper = Mage::helper('core/string');

        $i = 0;
        foreach ($this->_getAllCountries() as $code => $name) {
            $title = $this->_downcase($name);
            if ($helper->strpos($title, $term) !== false) {
                $countryNames[] = $name;
                $i++;
                if ($i == $limit) {
                    break;
                }
            }
        }
        return $countryNames;
    }

    protected function _searchOverRegions($term, $countryName, $limit)
    {
        $countryCode = $this->getCountryCodeByName($countryName);

        $adapter = $this->getSelect()->getAdapter();
        $select  = $adapter->select()
            ->from(array('r' => $this->_regionTable), 'default_name')
            ->joinLeft(
                array('rn' => $this->_regionNameTable),
                $adapter->quote('rn.region_id = r.region_id AND locale = ?', Mage::app()->getLocale()->getLocaleCode()),
                array('name')
            )
            ->where('r.country_id = ?', $countryCode)
            ->where('IF(name, name, default_name) LIKE ?', '%' . $term . '%')
            ->limit($limit);

        $regions = array();
        $result = $adapter->fetchAll($select);
        foreach ($result as $row) {
            $regions[] = $row['name'] ? $row['name'] : $row['default_name'];
        }
        return $regions;
    }

    protected function _getAllCountries()
    {
        return Mage::app()->getLocale()->getCountryTranslationList();
    }

    private function _downcase($string)
    {
        return Mage::helper('fi_checkout')->lowerCase($string);
    }
}
