<?php
/*
 * This controller includes all api for the product inventory related services 
 * @category   Restapi
 * @controller Product controller
 * @package    Restapi_Api
 * @author     Betasoft Team 
*/
 
class Restapi_Api_ProductController extends Mage_Core_Controller_Front_Action{    


    /**
     * Retrieve customer session model object
     *
     * @return Mage_Customer_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('customer/session');

    } // end function


    
    /**
     * Action predispatch
     *
     * Check customer authentication for some actions
     */
    public function preDispatch(){

        // a brute-force protection here would be nice

        parent::preDispatch();      

        if (!$this->getRequest()->isDispatched()) {
            return;
        }


    } // end function


    /**
     * Action postdispatch
     *
     * Remove No-referer flag from customer session after each action
     */
    public function postDispatch()
    {
        parent::postDispatch();

    } // end function


    /**
    * Action attributeList
    * List of attribute 
    * @param - auth_key (auth key send during login)
    * @param - attribute (name of attribute to fetch all option available)
    */
    public function attributeListAction(){

        //Receive the RAW post data via the php://input IO stream.        
        $postData = file_get_contents("php://input");
        if(!empty($postData)){
            $postData = json_decode($postData);
        }

        $keyAuth = trim($postData->auth_key);
        $attribute = trim($postData->attribute);

        if (!empty($keyAuth) && !empty($attribute)) {

            $customerID = convert_uudecode(base64_decode($keyAuth)); 

            // get customer details by customer id
            $customerData = Mage::getModel('customer/customer')->load($customerID);         
            $email = $customerData->getData('email'); 

            if(!empty($email)){
                $attributes = array();

                // fetch all attribute option in magento store
                $attribute = Mage::getSingleton('eav/config')
                    ->getAttribute(Mage_Catalog_Model_Product::ENTITY,$attribute);

                if ($attribute->usesSource()) {
                    $options = $attribute->getSource()->getAllOptions(false);
                    if(!empty($options)){
                        for($i=0; $i<count($options); $i++){
                            $attributes[$i]['id']    = $options[$i]['value'];
                            $attributes[$i]['name']  = $options[$i]['label'];
                        }
                    }
                }
                if(!empty($attributes)){
                    $response = array("success"=>1,"message"=>"".ucwords($attribute)." List.","data"=>$attributes);
                }else{
                    $response = array("success"=>0,"message"=>"No ".strtolower($attribute)." option found.","data"=>array());
                }
            }else{
                $response = array("success"=>0,"message"=>"Invalid user.","data"=>array());
            }
        }else{
            $response = array("success"=>0,"message"=>"Please send auth key and attribute both.","data"=>array());
        }    
        echo json_encode(array("response"=>$response)); // return response  

    } // end function


    /**
    * Action categoryListAction
    * list of category 
    * @param - auth_key (auth key send during login)
    */
    public function categoryListAction(){

        //Receive the RAW post data via the php://input IO stream.        
        $postData = file_get_contents("php://input");
        if(!empty($postData)){
            $postData = json_decode($postData);
        }

        $keyAuth = trim($postData->auth_key);

        $category = array();

        if (!empty($keyAuth)) {

            $customerID = convert_uudecode(base64_decode($keyAuth)); 

            // get customer details by customer id
            $customerData = Mage::getModel('customer/customer')->load($customerID);         
            $email = $customerData->getData('email'); 

            if(!empty($email)){

                // fetch list of category in website
                $_categories = Mage::getModel('catalog/category')
                    ->getCollection()
                    ->addAttributeToSelect('*')
                    ->addIsActiveFilter()
                    ->addLevelFilter(2)
                    ->addOrderField('name');                

                if (count($_categories) > 0){
                    $i=0;
                    foreach($_categories as $_category){

                        $category[$i]['id'] = $_category->getId(); 
                        $category[$i]['name'] = $_category->getName();
                        $i++;
                    }
                    $response = array("success"=>1,"message"=>"Category list.","data"=>$category);
                }else{
                    $response = array("success"=>0,"message"=>"No Category found.","data"=>array());
                }
            }else{
                $response = array("success"=>0,"message"=>"Invalid user.","data"=>array());
            }
        }else{
            $response = array("success"=>0,"message"=>"Please send auth key.","data"=>array());
        }    

        echo json_encode(array("response"=>$response)); // return response  
        die();

    } // end function


    /**
    * Action subCategoryList
    * list of sub category related to category id
    * @param - category (category id)
    * @param - auth_key (auth key send during login)
    */
    public function subCategoryListAction(){

        //Receive the RAW post data via the php://input IO stream.        
        $postData = file_get_contents("php://input");
        if(!empty($postData)){
            $postData = json_decode($postData);
        }

        $keyAuth = trim($postData->auth_key);
        $categoryID = trim($postData->category);
        $category = array();

        if (!empty($keyAuth) && !empty($categoryID)) {

            $customerID = convert_uudecode(base64_decode($keyAuth));             
                
            // get customer details by customer id
            $customerData = Mage::getModel('customer/customer')->load($customerID);         
            $email = $customerData->getData('email'); 

            if(!empty($email)){

                // fetch sub category related to category id
                $_subcategories = Mage::getModel('catalog/category')
                            ->getCollection()->addFieldToFilter('parent_id', $categoryID)
                            ->addAttributeToSort('name', 'ASC');
                
                $i = 0; 
                if (count($_subcategories) > 0){                   
                    foreach($_subcategories as $_subcategory){
                        $category[$i]['id'] = $_subcategory->getId(); 
                        $category[$i]['name'] = $_subcategory->getName();   
                        $i++;
                    }
                    $response = array("success"=>1,"message"=>"Sub Category list.","data"=>$category);
                }else{         
                    $response = array("success"=>0,"message"=>"No category belong to this category.","data"=>array());
                }
            }else{
                $response = array("success"=>0,"message"=>"Invalid user.","data"=>array());
            }

        }else{
            $response = array("success"=>0,"message"=>"Please send auth key and category id both.","data"=>array());
        }    

        echo json_encode(array("response"=>$response)); // return response  

    } // end function

    /**
    * Action productList
    * List of products with first product in separate array 
    * @param - product_name (search by product name or sku code)
    * @param - auth_key (auth key send during login)
    * @param - product_color (filter by color id)
    * @param - product_size (filter by product size id) 
    * @param - limit (number of product shown in one hit)
    * @param - page (number of pages calculate as per number of product/limit)
    * @param - category (show only selected category product)
    */
    public function productListAction(){
        //Receive the RAW post data via the php://input IO stream.        
        $postData = file_get_contents("php://input");
        if(!empty($postData)){
            $postData = json_decode($postData);
        }

        $productName = $productColor = $productSize = $productCategory = "";
        $limit = 10; // limit number of product
        $page = 1;  // page number for pagination

        $keyAuth     = trim($postData->auth_key);

        // searchable keyword in api
        if(isset($postData->product_name)){
            $productName = trim($postData->product_name);
        }
        if(isset($postData->product_color)){
            $productColor = trim($postData->product_color);
        }
        if(isset($postData->product_size)){
            $productSize = trim($postData->product_size);
        }
        if(isset($postData->category)){
            $productCategory = trim($postData->category);
        }
        if(isset($postData->limit)){
            $limit = trim($postData->limit);
        }
        if(isset($postData->page)){
            $page = trim($postData->page);
        }
        

        if (!empty($keyAuth)) {

            $customerID = convert_uudecode(base64_decode($keyAuth)); 

            // get customer details by customer id
            $customerData = Mage::getModel('customer/customer')->load($customerID);         
            $email = $customerData->getData('email'); 


            /********************************* Fetch wishlist for customer ID start here  **********************************/
            $arrProductIds = array();
            $wishList = Mage::getSingleton('wishlist/wishlist')->loadByCustomer($customerID);

            $wishListItemCollection = $wishList->getItemCollection();

            if (count($wishListItemCollection)) {
                
                foreach ($wishListItemCollection as $item) {
                    $product = $item->getProduct();
                    $arrProductIds[] = $product->getId();
                }
            }
            /********************************* Fetch wishlist for customer ID end here  **********************************/

            $productArr = array();

            if(!empty($email)){

                /********************************** retrieve product data from website start here *********************/

                $productCollection = Mage::getModel('catalog/product');

                if(!empty($productCategory)){ // category search in product

                    $productCategory = explode(',',$productCategory);
                    $productCategory = array_unique(array_filter($productCategory));

                    $collection = $productCollection
                    ->getCollection()
                    ->joinField('category_id', 'catalog/category_product', 'category_id', 'product_id = entity_id', null, 'left')
                    ->addAttributeToFilter('category_id', array('in' => $productCategory));

                }else{
                    $collection = $productCollection
                    ->getCollection();
                }
                        
                    $collection->AddAttributeToSelect('name')
                    ->addAttributeToSelect('price')
                    ->addAttributeToSelect('color')
                    ->addAttributeToSelect('size')
                    ->addAttributeToSelect('expected_delivery_date')
                    ->addFinalPrice()
                    ->addAttributeToSelect('small_image')
                    ->addAttributeToSelect('image')
                    ->addAttributeToSelect('thumbnail')
                    ->addAttributeToSelect('short_description')
                    ->addAttributeToSelect('pack')
                    ->addAttributeToSort('sku', 'ASC')
                    ->setPageSize($limit)
                    ->setCurPage($page);

            

                /*********************************** serach filter condition start here *************************************/

                if(!empty($productName)){
                    $productName = trim($productName);
                    $collection->addAttributeToFilter(
                        array(
                            array('attribute'=>'name', 'like' => '%'.$productName.'%'),
                            array('attribute'=>'sku','like' => '%'.$productName.'%')
                        )
                    );

                }

                $productIDS = $ids = array();
                $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
                $collectionAttr = $productCollection->getCollection();
                
                if(!empty($productColor)){ // product color search in product
                    $productColor = explode(',',$productColor);
                    $productColor = array_unique(array_filter($productColor));
                    $collectionAttr->addAttributeToFilter('color', array(‘in’ => $productColor));  
                }

                if(!empty($productSize)){ // product size search in product
                    $productSize = explode(',',$productSize);
                    $productSize = array_unique(array_filter($productSize));
                    $collectionAttr->addAttributeToFilter('size', array(‘in’ => $productSize));  
                }

                $stop = 0;

                if(!empty($productColor) || !empty($productSize)){
                    foreach($collectionAttr as $product) {
                        $ids[] = $product->getId();
                    }

                       
                    if(!empty($ids)){
                        $ids = array_filter(array_unique($ids));
                        $ids = implode(',',$ids);
                        $results = $readConnection->fetchAll("select parent_product_id from catalog_product_bundle_selection where product_id IN ($ids)");
                        if(!empty($results)){
                            foreach ($results as $key => $value) {
                                $productIDS[] = $value['parent_product_id'];
                            }
                        }
                        if(!empty($productIDS)){
                            $productIDS = array_filter(array_unique($productIDS));
                            $collection->addAttributeToFilter('entity_id', array('in' => $productIDS));
                        }else{
                            $stop = 1;
                        }
                    }else{
                        $stop = 1;
                    }
                }else{
                    /***************************** just to check if product having bundle product or not start here *************/
                    $productBundle = array();
                    $resultBundle = $readConnection->fetchAll("select parent_product_id from catalog_product_bundle_selection");
                    if(!empty($resultBundle)){
                        foreach ($resultBundle as $keyBundle => $valueBundle) {
                            $productBundle[] = $valueBundle['parent_product_id'];
                        }
                    }
                    if(!empty($productBundle)){
                        $productBundleIDS = array_filter(array_unique($productBundle));
                        $collection->addAttributeToFilter('entity_id', array('in' => $productBundleIDS));
                    }
                    /***************************** just to check if product having bundle product or not end here *************/
                }
                $collection->addAttributeToFilter('visibility', 4);      

                /*********************************** serach filter condition end here *************************************/
                
                // get distint products
                $collection->getSelect()->group('e.entity_id');

                // collection count                
                $count = $collection->getSize();

                if($count>0 && $stop==0){

                    $i=0;

                    foreach ($collection as $product) {
                        $is_whishlist = 0;
                        $delivery_date   = $bundleSize = $pack_id = $pack_name = "";
                        $productCategory = $productImages = $optionArray = $packArr = array();
                        $productId = $product->getId(); // product id
                        $productType = $product->getTypeId(); // product type

                       
                        // check if product is in whishlist or not
                        if(!empty($arrProductIds) && in_array($productId,$arrProductIds)){
                            $is_whishlist = 1;
                        }

                        /********************************* get data for product bundle type start here *********************************/
                        if($productType=="bundle"){ 
                            $productModel = $productCollection->load($productId);
                            /********************************* fetch product option start here ****************************************/
                            $selectionCollection = $product->getTypeInstance(true)
                                ->getSelectionsCollection(
                                    $product->getTypeInstance(true)->getOptionsIds($product),
                                    $product
                                );

                            foreach ($selectionCollection as $proselection) {
                                    $productLoad=$productCollection->load($proselection->getId());
                                    $selectionArray                                = [];
                                    $selectionArray['option_id']                   = $proselection->getOptionId();
                                    $selectionArray['selection_id']                = $proselection->getSelectionId();
                                    $selectionArray['product_id']                  = $proselection->getProductId();
                                    $selectionArray['product_name']                = $proselection->getName();
                                    $selectionArray['product_price']               = number_format($proselection->getPrice(),2);
                                    $selectionArray['special_price']               = number_format($proselection->getSpecialPrice(),2);
                                    $selectionArray['product_quantity']            = (int) $proselection->getSelectionQty();
                                    $selectionArray['color_id']                    = $productLoad->getColor(); 
                                    $selectionArray['color_label']                 = $productLoad->getAttributeText('color');
                                    $selectionArray['size_id']                     = $productLoad->getSize(); 
                                    $selectionArray['size_label']                  = $productLoad->getAttributeText('size');
                                    $productsArray[$proselection->getOptionId()][] = $selectionArray;
                            }

                            //get all bundle product options of product
                            $optionsCollection = $product->getTypeInstance(true)
                                ->getOptionsCollection($product);

                            if(!empty($optionsCollection)){
                                $bundleSizeArr = array();
                                $optionCount = 0;
                                foreach ($optionsCollection as $options) {
                                    $optionArray[$optionCount]['option_title']    = $options->getDefaultTitle();
                                    $optionArray[$optionCount]['option_type']     = $options->getType();
                                    $optionArray[$optionCount]['product_options'] = $productsArray[$options->getOptionId()];
                                    
                                    /************************** get price option start here *******************/
                                    $optionPrices = $finalOptionPrice = 0;
                                    
                                    foreach($productsArray[$options->getOptionId()] as $optionprices){
                                        $bundleSizeArr[] =  $optionprices['size_label'];
                                        if($optionprices['special_price']>0){
                                            $optionPrices += $optionprices['product_quantity']*$optionprices['special_price'];
                                        }else{
                                            $optionPrices += $optionprices['product_quantity']*$optionprices['product_price'];
                                        }
                                    }
                                    $finalOptionPrice = number_format($optionPrices,2);
                                    /************************** get price option end here *******************/

                                    $optionArray[$optionCount]['option_price'] = $finalOptionPrice;
                                    $optionCount++;                                 
                                }
                                if(!empty($bundleSizeArr)){
                                    $bundleSizeArr = array_unique(array_filter($bundleSizeArr));
                                    $bundleSize  = implode('/',$bundleSizeArr);
                                }
                            }      
                            /********************************* fetch product option start here ****************************************/

                            /***************************************** get pack details start here ***********************************/
                            $pack_id = $product->getData('pack');
                            if(!empty($pack_id)){
                                $attrPack = $productModel->getResource()->getAttribute('pack'); 
                                if ($attrPack->usesSource()) {
                                    $pack_name       = $attrPack->getSource()->getOptionText($pack_id); 
                                }
                            }
                            /***************************************** get pack details start here ***********************************/

                            /***************** fetch category name and id of respective product start here *************/
                                                 
                            $categoryIds = $product->getCategoryIds();

                            if(!empty($categoryIds)){
                                $categoryCollection = Mage::getModel('catalog/category')->getCollection()
                                    ->setStoreId(Mage::app()->getStore()->getId())
                                    ->addAttributeToSelect('name')
                                    ->addIdFilter($categoryIds)
                                    ->addAttributeToFilter('is_active', 1)//get only active categories
                                    ->addAttributeToSort('position', 'desc'); //sort by position

                                $c=0;
                                foreach($categoryCollection as $category) {
                                    $productCategory[$c]['id']   =  $category->getId();
                                    $productCategory[$c]['name'] =  $category->getName();
                                    $c++;
                                }
                            }
                             
                            /***************** fetch category name and id of respective product end here *************/


                            /***************** fetch all images for respective product start here *************/
                            $images = Mage::getModel('catalog/product')->load($productId)->getMediaGalleryImages();
                            $im = 0;

                            // get image path url
                            $productMediaConfig = Mage::getModel('catalog/product_media_config');              

                            foreach ($images as $image){ //will load all gallery images in loop
                                 $productImages[$im]  = $productMediaConfig->getMediaUrl($image->getFile());
                                 $im++;
                            }
                            /***************** fetch all images for respective product end here *************/

                        }

                        $productArr[$i] = array(
                                'id'                    => $product->getId(),
                                'name'                  => $product->getName(),
                                'code'                  => $product->getData('sku'),
                                'description'           => $product->getShortDescription(),
                                'price'                 => $product->getPrice(), 
                                'thumb'                 => (string)Mage::helper('catalog/image')->init($product, 'thumbnail'),
                                'category'              => $productCategory,
                                'image'                 => $productImages,                           
                                'final_price'           => number_format($product->getFinalPrice(),2),
                                'is_whislist'           => $is_whishlist,
                                'currency_code'         => Mage::app()->getLocale()->currency( $currency_code )->getSymbol(),
                                'pack_id'               => $pack_id,
                                'pack_name'             => $pack_name,
                                'bundle_size'           => $bundleSize,
                                'product_options'       => $optionArray
                            );
                        $i++;
                    }

                    $response = array("success"=>1,"message"=>"Product list.","count"=>$count,"data"=>$productArr);
                }else{
                    $response = array("success"=>0,"message"=>"No product found.","count"=>0,"data"=>array());
                }

                /********************************** retrieve product data from website end here *********************/
                
            }else{
                $response = array("success"=>0,"message"=>"Invalid user.","count"=>0,"data"=>array());
            }
        }else{
            $response = array("success"=>0,"message"=>"Please send auth key.","count"=>0,"data"=>array());
        }    
        echo json_encode(array("response"=>$response)); // return response  

    } // end function



    /**
    * Action productDetailAction
    * List of products detail 
    * @param - product_id (product id)
    * @param - auth_key (auth key send during login)  
    */
    public function productDetailAction(){

        //Receive the RAW post data via the php://input IO stream.        
        $postData = file_get_contents("php://input");
        if(!empty($postData)){
            $postData = json_decode($postData);
        }

        $keyAuth     = trim($postData->auth_key);
        $productId     = trim($postData->product_id);
          

        if (!empty($keyAuth) && !empty($productId)) {

            $customerID = convert_uudecode(base64_decode($keyAuth)); 

            // get customer details by customer id
            $customerData = Mage::getModel('customer/customer')->load($customerID);         
            $email = $customerData->getData('email'); 


            /********************************* Fetch wishlist for customer ID start here  **********************************/
            $arrProductIds = array();
            $wishList = Mage::getSingleton('wishlist/wishlist')->loadByCustomer($customerID);

            $wishListItemCollection = $wishList->getItemCollection();

            if (count($wishListItemCollection)) {
                
                foreach ($wishListItemCollection as $item) {
                    $product = $item->getProduct();
                    $arrProductIds[] = $product->getId();
                }
            }
            /********************************* Fetch wishlist for customer ID end here  **********************************/

            $productArr = array();

            if(!empty($email)){

                /********************************** retrieve product data from website start here *********************/

                $productCollection = Mage::getModel('catalog/product');

                
                    $collection = Mage::getModel('catalog/product')
                        ->getCollection()
                        ->AddAttributeToSelect('name')
                        ->addAttributeToSelect('price')
                        ->addAttributeToSelect('color')
                        ->addAttributeToSelect('size')
                        ->addAttributeToSelect('expected_delivery_date')
                        ->addFinalPrice()
                        ->addAttributeToSelect('small_image')
                        ->addAttributeToSelect('image')
                        ->addAttributeToSelect('thumbnail')
                        ->addAttributeToSelect('pack')
                        ->addAttributeToSelect('short_description')
                        ->addAttributeToSort('name', 'ASC');

                    $collection->addAttributeToFilter('entity_id',array('in'=>$productId));

                
                    // collection count
                    $count = $collection->getSize();

                    if($count>0){

                        $i=0;

                        foreach ($collection as $product) {
                            $is_whishlist = 0;
                            $delivery_date   = $bundleSize = $pack_id = $pack_name = "";
                            $productCategory = $productImages = $optionArray = $packArr = array();
                            $productId = $product->getId(); // product id
                            $productType = $product->getTypeId(); // product type

                            // check if product is in whishlist or not
                            if(!empty($arrProductIds) && in_array($productId,$arrProductIds)){
                                $is_whishlist = 1;
                            }

                            $productModel = $productCollection->load($productId);


                            /********************************* get data for product bundle type start here *********************************/
                            if($productType=="bundle"){ 

                                /********************************* fetch product option start here ****************************************/
                                $selectionCollection = $product->getTypeInstance(true)
                                    ->getSelectionsCollection(
                                        $product->getTypeInstance(true)->getOptionsIds($product),
                                        $product
                                    );
                                if(!empty($selectionCollection)){
                                    foreach ($selectionCollection as $proselection) {
                                        $productLoad=$productCollection->load($proselection->getId());
                                        $selectionArray                                = [];
                                        $selectionArray['option_id']                   = $proselection->getOptionId();
                                        $selectionArray['selection_id']                   = $proselection->getSelectionId();
                                        $selectionArray['product_id']                  = $proselection->getProductId();
                                        $selectionArray['product_name']                = $proselection->getName();
                                        $selectionArray['product_price']               = number_format($proselection->getPrice(),2);
                                        $selectionArray['special_price']               = number_format($proselection->getSpecialPrice(),2);
                                        $selectionArray['product_quantity']            = (int) $proselection->getSelectionQty();
                                        $selectionArray['color_id']                    = $productLoad->getColor(); 
                                        $selectionArray['color_label']                 = $productLoad->getAttributeText('color');
                                        $selectionArray['size_id']                     = $productLoad->getSize(); 
                                        $selectionArray['size_label']                  = $productLoad->getAttributeText('size');
                                        $productsArray[$proselection->getOptionId()][] = $selectionArray;
                                    }
                                }
                         
                                //get all bundle product options of product
                                $optionsCollection = $product->getTypeInstance(true)
                                    ->getOptionsCollection($product);

                                if(!empty($optionsCollection)){
                                    $bundleSizeArr = array();
                                    $optionCount = 0;
                                    foreach ($optionsCollection as $options) {
                                        $optionArray[$optionCount]['option_title']    = $options->getDefaultTitle();
                                        $optionArray[$optionCount]['option_type']     = $options->getType();
                                        $optionArray[$optionCount]['product_options'] = $productsArray[$options->getOptionId()];
                                        
                                        /************************** get price option start here *******************/
                                        $optionPrices = $finalOptionPrice = 0;
                                        
                                        foreach($productsArray[$options->getOptionId()] as $optionprices){
                                            $bundleSizeArr[] =  $optionprices['size_label'];
                                            if($optionprices['special_price']>0){
                                                $optionPrices += $optionprices['product_quantity']*$optionprices['special_price'];
                                            }else{
                                                $optionPrices += $optionprices['product_quantity']*$optionprices['product_price'];
                                            }
                                        }
                                        $finalOptionPrice = number_format($optionPrices,2);
                                        /************************** get price option end here *******************/

                                        $optionArray[$optionCount]['option_price'] = $finalOptionPrice;
                                        $optionCount++;                                 
                                    }
                                    if(!empty($bundleSizeArr)){
                                        $bundleSizeArr = array_unique(array_filter($bundleSizeArr));
                                        $bundleSize  = implode('/',$bundleSizeArr);
                                    }

                                }       
                                /************************ fetch product option start here ************************************/

                                /***************************************** get pack details start here ***********************************/
                                $pack_id = $product->getData('pack');
                                if(!empty($pack_id)){
                                    $attrPack = $productModel->getResource()->getAttribute('pack'); 
                                    if ($attrPack->usesSource()) {
                                        $pack_name       = $attrPack->getSource()->getOptionText($pack_id); 
                                    }
                                }
                                /***************************************** get pack details start here ***********************************/

                            }
                            /******************************** get price for product end here ***********************************/


                           


                            /***************** fetch category name and id of respective product start here *************/
                                                 
                            $categoryIds = $product->getCategoryIds();

                            if(!empty($categoryIds)){
                                $categoryCollection = Mage::getModel('catalog/category')->getCollection()
                                    ->setStoreId(Mage::app()->getStore()->getId())
                                    ->addAttributeToSelect('name')
                                    ->addIdFilter($categoryIds)
                                    ->addAttributeToFilter('is_active', 1)//get only active categories
                                    ->addAttributeToSort('position', 'desc'); //sort by position

                                $c=0;
                                foreach($categoryCollection as $category) {
                                    $productCategory[$c]['id']   =  $category->getId();
                                    $productCategory[$c]['name'] =  $category->getName();
                                    $c++;
                                }
                            }
                             
                            /***************** fetch category name and id of respective product end here *************/


                            /***************** fetch all images for respective product start here *************/
                            $images = Mage::getModel('catalog/product')->load($productId)->getMediaGalleryImages();
                            $im = 0;

                            // get image path url
                            $productMediaConfig = Mage::getModel('catalog/product_media_config');              

                            foreach ($images as $image){ //will load all gallery images in loop
                                 $productImages[$im]  = $productMediaConfig->getMediaUrl($image->getFile());
                                 $im++;
                            }
                            /***************** fetch all images for respective product end here *************/

                            $productArr = array(
                                'id'                    => $product->getId(),
                                'name'                  => $product->getName(),
                                'code'                  => $product->getData('sku'),
                                'description'           => $product->getShortDescription(),
                                'price'                 => $product->getPrice(), 
                                'thumb'                 => (string)Mage::helper('catalog/image')->init($product, 'thumbnail'),
                                'category'              => $productCategory,
                                'image'                 => $productImages,                           
                                'final_price'           => number_format($product->getFinalPrice(),2),
                                'is_whislist'           => $is_whishlist,
                                'currency_code'         => Mage::app()->getLocale()->currency( $currency_code )->getSymbol(),
                                'pack_id'               => $pack_id,
                                'pack_name'             => $pack_name,
                                'bundle_size'           => $bundleSize,
                                'product_options'       => $optionArray
                            );
                            $i++;
                        }

                        $response = array("success"=>1,"message"=>"Product list.","data"=>$productArr);
                    }else{
                        $response = array("success"=>0,"message"=>"No product found.","data"=>array());
                    }

                /********************************** retrieve product data from website end here *********************/
                
            }else{
                $response = array("success"=>0,"message"=>"Invalid user.","data"=>array());
            }
        }else{
            $response = array("success"=>0,"message"=>"Please send auth key and product id both.","data"=>array());
        }    
        echo json_encode(array("response"=>$response)); // return response  

    } // end function

    /**
    * Action addProductInWishlist
    * Add product to wishlist
    * @param - product_id (product id)
    * @param - auth_key (auth key send during login)
    */
    public function addProductInWishlistAction(){

        //Receive the RAW post data via the php://input IO stream.        
        $postData = file_get_contents("php://input");
        if(!empty($postData)){
            $postData = json_decode($postData);
        }

        $productId     = trim($postData->product_id);
        $keyAuth     = trim($postData->auth_key);

        if (!empty($keyAuth) && !empty($productId)) {

            $customerID = convert_uudecode(base64_decode($keyAuth)); 

            // get customer details by customer id
            $customerData = Mage::getModel('customer/customer')->load($customerID);         
            $email = $customerData->getData('email'); 

            if(!empty($email)){
                $productAdded = 0;
                // check if product id valid or not
                $productId = explode(',',$productId);
                foreach($productId as $productID){
                    $product = Mage::getModel('catalog/product')->load($productID);
                    if($product->getId()){

                        // check if product already added in wishlist or not
                        $alreadyAdded = 0;
                        $itemCollection = Mage::getModel('wishlist/item')->getCollection()->addCustomerIdFilter($customerID);
                        foreach($itemCollection as $item) {
                             if($item->getProduct()->getId() == $productID){
                                $alreadyAdded++;
                             }
                        }

                        //if($alreadyAdded==0){
                            /********************************* add product to wishlist for respective customer id ****************/
                            
                            $wishlist = Mage::getModel('wishlist/wishlist')->loadByCustomer($customerID, true);               

                            $buyRequest = new Varien_Object(array()); // any possible options that are configurable and you want to save with the product

                            $result = $wishlist->addNewItem($product, $buyRequest);
                            if($wishlist->save()){
                                $productAdded++;
                                //$response = array("success"=>1,"message"=>"Product added to wishlist sucsessfully.","data"=>array());
                            }else{
                                //$response = array("success"=>0,"message"=>"Not able to add product to wishlist.","data"=>array());
                            }

                        //}else{
                            //$response = array("success"=>0,"message"=>"Product already in wishlist.","data"=>array());
                        //}
                    }else{
                            //$response = array("success"=>0,"message"=>"Incorrect product id.","data"=>array());
                    }
                }

                if($productAdded>0){
                    $response = array("success"=>1,"message"=>"Product added to wishlist sucsessfully.","data"=>array());
                }else{
                    $response = array("success"=>0,"message"=>"Not able to add product to wishlist.","data"=>array());
                }


            }else{
                $response = array("success"=>0,"message"=>"Invalid user.","data"=>array());
            }
        }else{
            $response = array("success"=>0,"message"=>"Please send auth key and product id both.","data"=>array());
        }    
        echo json_encode(array("response"=>$response)); // return response  

    } // end function 

    
    /**
    * Action customerWishlistItem
    * list of product added in wishlist by customer
    * @param - auth_key (auth key send during login)
    */
    public function customerWishlistItemAction(){

        //Receive the RAW post data via the php://input IO stream.        
        $postData = file_get_contents("php://input");
        if(!empty($postData)){
            $postData = json_decode($postData);
        }

        $limit = 10; // limit number of product
        $page = 1;  // page number for pagination

        $keyAuth     = trim($postData->auth_key);

        if(isset($postData->limit)){
            $limit = trim($postData->limit);
        }
        if(isset($postData->page)){
            $page = trim($postData->page);
        }

        if (!empty($keyAuth)) {

            $customerID = convert_uudecode(base64_decode($keyAuth)); 

            // get customer details by customer id
            $customerData = Mage::getModel('customer/customer')->load($customerID);         
            $email = $customerData->getData('email'); 

            if(!empty($email)){

                /********************* just to check if product having bundle product or not start here *************/
                $productBundleIDS = array();
                $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
                $productBundle = array();
                $arrProductIds = array();
                $resultBundle = $readConnection->fetchAll("select parent_product_id from catalog_product_bundle_selection");
                if(!empty($resultBundle)){
                    foreach ($resultBundle as $keyBundle => $valueBundle) {
                        $productBundle[] = $valueBundle['parent_product_id'];
                    }
                }
                if(!empty($productBundle)){
                    $productBundleIDS = array_filter(array_unique($productBundle));  

                    $wishList = Mage::getSingleton('wishlist/wishlist')->loadByCustomer($customerID);

                    $wishListItemCollection = $wishList->getItemCollection();
                    // get current currency
                    $currency_code = Mage::app()->getStore()->getCurrentCurrencyCode(); 
                    if (count($wishListItemCollection)) {
                        
                        foreach ($wishListItemCollection as $item) {
                            $product = $item->getProduct();
                            $proId   = $product->getId();
                            if(in_array($proId,$productBundleIDS)){
                                $arrProductIds[] = $product->getId();
                            }
                        }
                    }                 
                }
                /********************** just to check if product having bundle product or not end here *************/

                
                

                if(!empty($arrProductIds)){

                    $productCollection = Mage::getModel('catalog/product');

                    $collection = Mage::getModel('catalog/product')
                        ->getCollection()
                        ->AddAttributeToSelect('name')
                        ->addAttributeToSelect('price')
                        ->addAttributeToSelect('color')
                        ->addAttributeToSelect('size')
                        ->addAttributeToSelect('expected_delivery_date')
                        ->addFinalPrice()
                        ->addAttributeToSelect('small_image')
                        ->addAttributeToSelect('image')
                        ->addAttributeToSelect('thumbnail')
                        ->addAttributeToSelect('short_description')
                        ->addAttributeToSelect('pack')
                        ->addAttributeToSort('sku', 'ASC')
                        ->setPageSize($limit)
                        ->setCurPage($page);

                    $collection->addAttributeToFilter('entity_id',array('in'=>$arrProductIds));

                    // get distint products
                    $collection->getSelect()->group('e.entity_id');

                    // collection count
                    $count = $collection->getSize();

                    if($count>0){

                        $i=0;

                        foreach ($collection as $product) {
                            $is_whishlist = 0;
                            $delivery_date   = $bundleSize = $pack_id = $pack_name = "";
                            $productCategory = $productImages = $optionArray = $packArr = array();
                            $productId = $product->getId(); // product id
                            $productType = $product->getTypeId(); // product type

                           
                            // check if product is in whishlist or not
                            if(!empty($arrProductIds) && in_array($productId,$arrProductIds)){
                                $is_whishlist = 1;
                            }

                            /********************************* get data for product bundle type start here *********************************/
                            if($productType=="bundle"){ 
                                $productModel = $productCollection->load($productId);
                                /********************************* fetch product option start here ****************************************/
                                $selectionCollection = $product->getTypeInstance(true)
                                    ->getSelectionsCollection(
                                        $product->getTypeInstance(true)->getOptionsIds($product),
                                        $product
                                    );

                                foreach ($selectionCollection as $proselection) {
                                        $productLoad=$productCollection->load($proselection->getId());
                                        $selectionArray                                = [];
                                        $selectionArray['option_id']                   = $proselection->getOptionId();
                                        $selectionArray['selection_id']                = $proselection->getSelectionId();
                                        $selectionArray['product_id']                  = $proselection->getProductId();
                                        $selectionArray['product_name']                = $proselection->getName();
                                        $selectionArray['product_price']               = number_format($proselection->getPrice(),2);
                                        $selectionArray['special_price']               = number_format($proselection->getSpecialPrice(),2);
                                        $selectionArray['product_quantity']            = (int) $proselection->getSelectionQty();
                                        $selectionArray['color_id']                    = $productLoad->getColor(); 
                                        $selectionArray['color_label']                 = $productLoad->getAttributeText('color');
                                        $selectionArray['size_id']                     = $productLoad->getSize(); 
                                        $selectionArray['size_label']                  = $productLoad->getAttributeText('size');
                                        $productsArray[$proselection->getOptionId()][] = $selectionArray;
                                }

                                //get all bundle product options of product
                                $optionsCollection = $product->getTypeInstance(true)
                                    ->getOptionsCollection($product);

                                if(!empty($optionsCollection)){
                                    $bundleSizeArr = array();
                                    $optionCount = 0;
                                    foreach ($optionsCollection as $options) {
                                        $optionArray[$optionCount]['option_title']    = $options->getDefaultTitle();
                                        $optionArray[$optionCount]['option_type']     = $options->getType();
                                        $optionArray[$optionCount]['product_options'] = $productsArray[$options->getOptionId()];
                                        
                                        /************************** get price option start here *******************/
                                        $optionPrices = $finalOptionPrice = 0;
                                        
                                        foreach($productsArray[$options->getOptionId()] as $optionprices){
                                            $bundleSizeArr[] =  $optionprices['size_label'];
                                            if($optionprices['special_price']>0){
                                                $optionPrices += $optionprices['product_quantity']*$optionprices['special_price'];
                                            }else{
                                                $optionPrices += $optionprices['product_quantity']*$optionprices['product_price'];
                                            }
                                        }
                                        $finalOptionPrice = number_format($optionPrices,2);
                                        /************************** get price option end here *******************/

                                        $optionArray[$optionCount]['option_price'] = $finalOptionPrice;
                                        $optionCount++;                                 
                                    }
                                    if(!empty($bundleSizeArr)){
                                        $bundleSizeArr = array_unique(array_filter($bundleSizeArr));
                                        $bundleSize  = implode('/',$bundleSizeArr);
                                    }
                                }      
                                /********************************* fetch product option start here ****************************************/

                                /***************************************** get pack details start here ***********************************/
                                $pack_id = $product->getData('pack');
                                if(!empty($pack_id)){
                                    $attrPack = $productModel->getResource()->getAttribute('pack'); 
                                    if ($attrPack->usesSource()) {
                                        $pack_name       = $attrPack->getSource()->getOptionText($pack_id); 
                                    }
                                }
                                /***************************************** get pack details start here ***********************************/

                                /***************** fetch category name and id of respective product start here *************/
                                                     
                                $categoryIds = $product->getCategoryIds();

                                if(!empty($categoryIds)){
                                    $categoryCollection = Mage::getModel('catalog/category')->getCollection()
                                        ->setStoreId(Mage::app()->getStore()->getId())
                                        ->addAttributeToSelect('name')
                                        ->addIdFilter($categoryIds)
                                        ->addAttributeToFilter('is_active', 1)//get only active categories
                                        ->addAttributeToSort('position', 'desc'); //sort by position

                                    $c=0;
                                    foreach($categoryCollection as $category) {
                                        $productCategory[$c]['id']   =  $category->getId();
                                        $productCategory[$c]['name'] =  $category->getName();
                                        $c++;
                                    }
                                }
                                 
                                /***************** fetch category name and id of respective product end here *************/


                                /***************** fetch all images for respective product start here *************/
                                $images = Mage::getModel('catalog/product')->load($productId)->getMediaGalleryImages();
                                $im = 0;

                                // get image path url
                                $productMediaConfig = Mage::getModel('catalog/product_media_config');              

                                foreach ($images as $image){ //will load all gallery images in loop
                                     $productImages[$im]  = $productMediaConfig->getMediaUrl($image->getFile());
                                     $im++;
                                }
                                /***************** fetch all images for respective product end here *************/

                            }

                            $productArr[$i] = array(
                                    'id'                    => $product->getId(),
                                    'name'                  => $product->getName(),
                                    'code'                  => $product->getData('sku'),
                                    'description'           => $product->getShortDescription(),
                                    'price'                 => $product->getPrice(), 
                                    'thumb'                 => (string)Mage::helper('catalog/image')->init($product, 'thumbnail'),
                                    'category'              => $productCategory,
                                    'image'                 => $productImages,                           
                                    'final_price'           => number_format($product->getFinalPrice(),2),
                                    'is_whislist'           => $is_whishlist,
                                    'currency_code'         => Mage::app()->getLocale()->currency( $currency_code )->getSymbol(),
                                    'pack_id'               => $pack_id,
                                    'pack_name'             => $pack_name,
                                    'bundle_size'           => $bundleSize,
                                    'product_options'       => $optionArray
                                );
                            $i++;
                        }
                        $response = array("success"=>1,"message"=>"Product in wishlist.","count"=>$count,"data"=>$productArr);
                    }else{
                        $response = array("success"=>0,"message"=>"No product added in wishlist.","count"=>0,"data"=>array());
                    }
                }else{
                    $response = array("success"=>0,"message"=>"No product added in wishlist.","count"=>0,"data"=>array());
                }
            }else{
                $response = array("success"=>0,"message"=>"Invalid user.","count"=>0,"data"=>array());
            }
        }else{
            $response = array("success"=>0,"message"=>"Please send auth key.","count"=>0,"data"=>array());
        }    
        echo json_encode(array("response"=>$response)); // return response  

    } // end function 


    /**
    * Action removeWishlist
    * Remove product from whishlist
    * @param - auth_key (auth key send during login)
    * @param - product_id (product id to be removed from whishlist)
    */
    public function removeWishlistAction(){   

        //Receive the RAW post data via the php://input IO stream.        
        $postData = file_get_contents("php://input");
        if(!empty($postData)){
            $postData = json_decode($postData);
        }

        $keyAuth     = trim($postData->auth_key);
        $productId    = trim($postData->product_id);

        if (!empty($keyAuth) && !empty($productId)) {

            $productId = explode(',',$productId);

            $customerID = convert_uudecode(base64_decode($keyAuth)); 

            // get customer details by customer id
            $customerData = Mage::getModel('customer/customer')->load($customerID);         
            $email = $customerData->getData('email'); 

            $removeWhishlist = 0;

            if(!empty($email)){

                // fetch whishlist product
                $itemCollection = Mage::getModel('wishlist/item')->getCollection()->addCustomerIdFilter($customerID);

                foreach($itemCollection as $item) {
                    $itemProductId = $item->getProductId();
                    if (in_array($itemProductId,$productId)){
                        $removeWhishlist++;
                        $item->delete(); // remove product from wishlist
                    }
                }

                if($removeWhishlist>0){
                    $response = array("success"=>1,"message"=>"Product remove from wishlist successfully.","data"=>array());
                }else{
                    $response = array("success"=>0,"message"=>"Unable to remove product from wishlist.","data"=>array());
                }

            }else{
                $response = array("success"=>0,"message"=>"Invalid user.","data"=>array());
            }
        }else{
            $response = array("success"=>0,"message"=>"Please send auth key and product both.","data"=>array());
        }    
        echo json_encode(array("response"=>$response)); // return response

    } // end function


    /**
    * Action linesheetListAction
    * list of category 
    * @param - auth_key (auth key send during login)
    */
    public function linesheetListAction(){

        //Receive the RAW post data via the php://input IO stream.        
        $postData = file_get_contents("php://input");
        if(!empty($postData)){
            $postData = json_decode($postData);
        }

        $keyAuth = trim($postData->auth_key);
        $keyword = trim($postData->keyword);

        $category = array();

        if (!empty($keyAuth)) {

            $customerID = convert_uudecode(base64_decode($keyAuth)); 

            // get customer details by customer id
            $customerData = Mage::getModel('customer/customer')->load($customerID);         
            $email = $customerData->getData('email'); 

            if(!empty($email)){

                $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
                $productBundle = array();
                $resultBundle = $readConnection->fetchAll("select parent_product_id from catalog_product_bundle_selection");
                if(!empty($resultBundle)){
                    foreach ($resultBundle as $keyBundle => $valueBundle) {
                        $productBundle[] = $valueBundle['parent_product_id'];
                    }
                }

                $productCollection = Mage::getModel('catalog/product');

                // fetch list of category in website
                $_categories = Mage::getModel('catalog/category')
                    ->getCollection()
                    ->addAttributeToSelect('*')
                    ->addIsActiveFilter()
                    ->addLevelFilter(2)
                    ->addOrderField('name')
                    ->addAttributeToFilter('name',array(array('like' => '%'. $keyword.'%')));               

                if (count($_categories) > 0){
                    $i=0;
                    foreach($_categories as $_category){

                        $category[$i]['id'] = $_category->getId(); 
                        $category[$i]['name'] = $_category->getName();

                        $productCategory   = array();
                        $productCategory[] =  $_category->getId();

                        if(!empty($productBundle)){
                            $collection = $productCollection
                                ->getCollection()
                                ->joinField('category_id', 'catalog/category_product', 'category_id', 'product_id = entity_id', null, 'left')
                                ->addAttributeToFilter('category_id', array('in' => $productCategory));

                            $collection->AddAttributeToSelect('name')
                            ->addAttributeToSelect('thumbnail')
                            ->setPageSize(1)
                            ->setCurPage(1);

                            $productBundleIDS = array_filter(array_unique($productBundle));
                            $collection->addAttributeToFilter('entity_id', array('in' => $productBundleIDS));
                            $collection->addAttributeToFilter('visibility', 4);
                            // get distint products
                            $collection->getSelect()->group('e.entity_id');

                            // collection count                
                            $count = $collection->getSize();

                            if($count>0){
                                foreach ($collection as $product) {
                                    $category[$i]['image']  = (string)Mage::helper('catalog/image')->init($product, 'thumbnail');
                                }
                            }else{
                                $category[$i]['image'] = "";
                            }
                        }else{
                            $category[$i]['image'] = "";
                        }
                        $i++;
                    }
                    $response = array("success"=>1,"message"=>"Category list.","data"=>$category);
                }else{
                    $response = array("success"=>0,"message"=>"No Category found.","data"=>array());
                }
            }else{
                $response = array("success"=>0,"message"=>"Invalid user.","data"=>array());
            }
        }else{
            $response = array("success"=>0,"message"=>"Please send auth key.","data"=>array());
        }    

        echo json_encode(array("response"=>$response)); // return response  
        die();

    } // end function

   
}
?>
