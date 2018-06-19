<?php
 
class Barcode_Mytabs_Model_Observer
{
	/**
	 * Flag to stop observer executing more than once
	 *
	 * @var static bool
	 */
	static protected $_singletonFlag = false;
 
	/**
	 * This method will run when the product is saved from the Magento Admin
	 * Use this function to update the product model, process the 
	 * data or anything you like
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function saveProductTabData(Varien_Event_Observer $observer)
	{
		if (!self::$_singletonFlag) {
			self::$_singletonFlag = true;
			
			$product = $observer->getEvent()->getProduct();
		
			try {
				
				$myFieldValue =  $this->_getRequest()->getPost('my_field');
				$product->save();
			}
			catch (Exception $e) {
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
			}
		}
	}
     
	/**
	 * Retrieve the product model
	 *
	 * @return Mage_Catalog_Model_Product $product
	 */
	public function getProduct()
	{
		return Mage::registry('product');
	}
	
    /**
     * Shortcut to getRequest
     *
     */
    protected function _getRequest()
    {
        return Mage::app()->getRequest();
    }
}
?>