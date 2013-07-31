<?php
/**
 * Zero Step Checkout install script
 *
 * @category    FI
 * @package     FI_Checkout
 * @author      Sergiy Stotskiy <sergiy.stotskiy@freaksidea.com>
 */
$installer = $this;

$installer->startSetup();
foreach (array('customer', 'customer_address') as $entity) {
    $attribute = Mage::getModel('eav/config')
        ->getAttribute($entity, 'lastname');

    $attribute->setData('validate_rules', '')
        ->setIsRequired(false)
        ->save();
}

$attribute = Mage::getModel('eav/config')
    ->getAttribute('customer_address', 'postcode');
$attribute->setData('validate_rules', '')
    ->setIsRequired(false)
    ->save();


$installer->endSetup();
