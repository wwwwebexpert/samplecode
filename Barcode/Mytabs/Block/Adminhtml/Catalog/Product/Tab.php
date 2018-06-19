<?php
 
class Barcode_Mytabs_Block_Adminhtml_Catalog_Product_Tab 
extends Mage_Adminhtml_Block_Template
implements Mage_Adminhtml_Block_Widget_Tab_Interface {
 
	/**
	 * Set the template for the block
	 *
	 */
	public function _construct()
	{
		parent::_construct();
		
		$this->setTemplate('mytabs/catalog/product/tab.phtml');
	}
	
	/**
	 * Retrieve the label used for the tab relating to this block
	 *
	 * @return string
	 */
    public function getTabLabel()
    {
    	return $this->__('Product Barcode');
    }
    
    /**
     * Retrieve the title used by this tab
     *
     * @return string
     */
    public function getTabTitle()
    {
    	return $this->__('Click here to view your my tab content');
    }
    
	/**
	 * Determines whether to display the tab
	 * Add logic here to decide whether you want the tab to display
	 *
	 * @return bool
	 */
    public function canShowTab()
    {
		return true;
    }
    
    /**
     * Stops the tab being hidden
     *
     * @return bool
     */
    public function isHidden()
    {
    	return false;
    }
}
?>