<?php
$installer = $this;
$setup = new Mage_Eav_Model_Entity_Setup('core_setup');
$installer->startSetup();
/**
 * Adding Different Attributes
 */

// adding attribute group

$group_id = $setup->getDefaultAttributeGroupId('catalog_product');
// the attribute added will be displayed under the group/tab Special Attributes in product edit page
$setup->addAttribute('catalog_product', 'in_flash_sale', array(
    'label'             => 'Include In Flash Sale',
    'type'              => 'varchar',
    'input'             => 'boolean',
    'backend'           => 'eav/entity_attribute_backend_array',
    'frontend'          => '',
    'source'            => 'eav/entity_attribute_source_boolean',
    'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible'           => true,
    'required'          => true,
    'user_defined'      => true,
    'searchable'        => false,
    'filterable'        => false,
    'comparable'        => false,
    'visible_on_front'  => false,
    'visible_in_advanced_search' => false,
    'unique'            => false
))->addAttributeToGroup('4', '4',$group_id,$setup->getAttributeId('4','in_flash_sale'), null);
$setup->addAttribute('catalog_product', 'flashsale_price', array(
    'label'             => 'Price For Sale',
    'type'              => 'decimal',
    'input'             => 'price',
    'backend'           => 'catalog/product_attribute_backend_price',
    'frontend'          => '',
    'source'            => '',
    'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible'           => true,
    'required'          => false,
    'user_defined'      => true,
    'searchable'        => false,
    'filterable'        => false,
    'comparable'        => false,
    'visible_on_front'  => false,
    'visible_in_advanced_search' => false,
    'unique'            => false
))->addAttributeToGroup('4', '4',$group_id,$setup->getAttributeId('4','flashsale_price'), null);
