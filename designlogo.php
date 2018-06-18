<?php	
	//$currentStore = Mage::app()->getStore()->getCode();	
	$facebook = Mage::helper('configuration')->getFacebookAPIKey();
	$flickr = Mage::helper('configuration')->getFlickrAPIKey();
	$instagram = Mage::helper('configuration')->getInstagramAPIKey();
	$imageDPI = Mage::helper('web2print')->getImageDPI();
	$product = $this->getCurrentProduct();
    $category = $this->getCurrentCategory();
    
    $productId = $this->getProductId();
    $categoryId = $this->getCategoryId($productId);
    $designData = array('design_id' => NULL, 'design_name' => NULL);
    $cart_id = '';
    $QTY = $this->getQty();
	
	$webpath = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);

	$cartUrl = Mage::getUrl('checkout/cart/add');	
	$jspath = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_JS).'html5/';	
	$pageDataAry = array();
	if(Mage::app()->getRequest()->getParam('templateId') != ''){
		$templateId = Mage::app()->getRequest()->getParam('templateId');
		$templateData = Mage::getModel('designnbuy_templates/producttemplate')
            ->setStoreId(Mage::app()->getStore()->getId());

		if ($templateId) {
			$templateData->load($templateId);
		}
		
		// $templateData = Mage::getModel('producttemplatecreator/producttemplatecreator')->load($templateId);
			
		$savedSvg = explode(",", $templateData->getSvg());
		foreach($savedSvg as $svg){
			$svgImagePath = Mage::getBaseDir(). DS .'media' . DS .'templates'. DS . 'svg'. DS . $svg; 
			if(file_exists($svgImagePath)){
				$svgfileContents = file_get_contents($svgImagePath);
				$doc = new DOMDocument();
				$doc->preserveWhiteSpace = False;
				$doc->loadXML($svgfileContents);
				foreach ($doc->getElementsByTagNameNS('http://www.w3.org/2000/svg', 'svg') as $element):
					foreach ($element->getElementsByTagName("*") as $tags):
						if($tags->localName=='pattern' && $tags->getAttribute('id')=='gridpattern') {
							$tags->parentNode->removeChild( $tags );
						}
						if($tags->localName=='image' && $tags->getAttribute('xlink:href')!=''):	
							$imageUrl = $tags->getAttribute('xlink:href');													
							$uploadedImage = explode('media/',$imageUrl);		
							if($uploadedImage[0]!='' && $uploadedImage[0] != $webpath):	
							
								$tags->setAttribute('xlink:href',Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).$uploadedImage[1]);
								$tags->setAttribute('templateSrc',Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).$uploadedImage[1]);
							endif;
						endif;
					endforeach;
				endforeach;
				$pageDataAry[] = $doc->saveXML();
				//$pageDataAry[] = file_get_contents($svgImagePath);
			}
		}
		/* foreach($savedSvg as $svg){
			$svgImagePath = Mage::getBaseDir(). DS .'media' . DS .'templates'. DS . 'svg'. DS . $svg; 
			if(file_exists($svgImagePath)){
				$pageDataAry[] = file_get_contents($svgImagePath);
			}
		} */
	}
	
	if(Mage::app()->getRequest()->getParam('design_id') != '')
	{
		$svgImagePath = Mage::getBaseDir(). DS .'media' . DS .'saveimg'. DS;
		$design_id = Mage::app()->getRequest()->getParam('design_id');
		$savedDesign = Mage::getModel('web2print/savedesign')->load($design_id);
		$designData['design_id'] = $savedDesign->getDesignId();
		$designData['design_name'] = $savedDesign->getDesignName();
		$savedSvg = explode(",", $savedDesign->getSaveString());
		foreach($savedSvg as $svg){
			if(file_exists($svgImagePath.$svg)){
				$pageDataAry[] = file_get_contents($svgImagePath.$svg);
			}
		}

		/* VDP Start */
		$savedVdpData = array();
		$savedVdpData['csv_file_data'] = '';
		$savedVdpData['csv_file_header_data'] = '';
		$savedOptions = $savedDesign->getOptions();
		if($savedOptions!= NULL){
			$decodedOptions = Mage::helper('core')->jsonDecode($savedOptions);
			$vdpFile = $decodedOptions['vdp_file'];
			$svgFile = $decodedOptions['svg_file'];
			$vdpFileData = file_get_contents($svgImagePath.$vdpFile);
			$vdpData = array();
			if($vdpFileData !='' && $vdpFileData !='undefined') {
				$savedVdpData['csv_file_data'] = Mage::helper('core')->jsonDecode($vdpFileData);
			}

			$svgFileData = file_get_contents($svgImagePath.$svgFile);
			$svgData = array();
			if($svgFileData !='' && $svgFileData !='undefined') {
				$savedVdpData['csv_file_header_data'] = Mage::helper('core')->jsonDecode($svgFileData);
			}
		}
		/* VDP End */
	}
	if(Mage::app()->getRequest()->getParam('cart_id') != '')
	{
		$cart_id = Mage::app()->getRequest()->getParam('cart_id');
		$item = Mage::getModel('sales/quote_item')->load($cart_id);
		$savedSvg = explode(",", $item->getSavestr());
		$svgPath = Mage::getBaseDir(). DS .'media' . DS .'cartimages'. DS;
		foreach($savedSvg as $svg){
			$svgImagePath = $svgPath . $svg;
			if(file_exists($svgImagePath)){
				$pageDataAry[] = file_get_contents($svgImagePath);
			}
		}

		/* VDP Start */
		/* @var $cart Mage_Checkout_Model_Cart */
		$cart = Mage::helper( 'checkout/cart' )->getCart();

		/* @var $item Mage_Sales_Model_Quote_Item */
		$item = $cart->getQuote()->getItemById( $cart_id );
		$savedVdpData = array();
		$savedVdpData['csv_file_data'] = '';
		$savedVdpData['csv_file_header_data'] = '';
		$vdpFile = $item->getBuyRequest()->getVdpFile();
		$svgFile = $item->getBuyRequest()->getSvgFile();

		if($vdpFile != NULL && $svgFile != NULL){
			$vdpFileData = file_get_contents($svgPath.$vdpFile);
			$vdpData = array();
			if($vdpFileData !='' && $vdpFileData !='undefined') {
				$savedVdpData['csv_file_data'] = Mage::helper('core')->jsonDecode($vdpFileData);
			}

			$svgFileData = file_get_contents($svgPath.$svgFile);
			$svgData = array();
			if($svgFileData !='' && $svgFileData !='undefined') {
				$savedVdpData['csv_file_header_data'] = Mage::helper('core')->jsonDecode($svgFileData);
			}
		}
		/* VDP End */
	}

    /*For Corporate**/
    $jobId = '';
    if(Mage::app()->getRequest()->getParam('jobId') != '')
    {
        $jobId = Mage::app()->getRequest()->getParam('jobId');
        $savedDesign = Mage::getModel('designnbuy_corporatejob/corporatejob')->load($jobId);

        $svgImageDir = Mage::helper('designnbuy_corporatejob/corporatejob')->getDesignImagePath();
        $savedSvg = explode(",", $savedDesign->getSvgData());

        foreach($savedSvg as $svg){
            $svgImagePath = $svgImageDir. DS . $svg;
            if(file_exists($svgImagePath)){
                $pageDataAry[] = file_get_contents($svgImagePath);
            }
        }

		/* VDP Start */
		$savedVdpData = array();
		$savedVdpData['csv_file_data'] = '';
		$savedVdpData['csv_file_header_data'] = '';
		$savedOptions = $savedDesign->getDesignData();

		if($savedOptions!= NULL){
			$decodedOptions = Mage::helper('core')->jsonDecode($savedOptions);

			$vdpFile = $decodedOptions['vdp_file'];
			$svgFile = $decodedOptions['svg_file'];
			$vdpFileData = file_get_contents($svgImageDir.$vdpFile);
			$vdpData = array();
			if($vdpFileData !='' && $vdpFileData !='undefined') {
				$savedVdpData['csv_file_data'] = Mage::helper('core')->jsonDecode($vdpFileData);
			}

			$svgFileData = file_get_contents($svgImageDir.$svgFile);
			$svgData = array();
			if($svgFileData !='' && $svgFileData !='undefined') {
				$savedVdpData['csv_file_header_data'] = Mage::helper('core')->jsonDecode($svgFileData);
			}
		}

		/* VDP End */
    }
	if(Mage::app()->getRequest()->getParam('quote_id') != '')
	{
		$svgImagePath = Mage::getBaseDir(). DS .'media' . DS .'cartimages'. DS;
		$quoteId = Mage::app()->getRequest()->getParam('quote_id');
		$quoteProducts = Mage::getModel('quotations/proproduct')->load($quoteId);
		$QrOptions = unserialize($quoteProducts['options']);

		$savedSvg = explode(",",$QrOptions['savestr']);
		foreach($savedSvg as $svg){
			if(file_exists($svgImagePath.$svg)){
				$pageDataAry[] = file_get_contents($svgImagePath.$svg);
			}
		}

		/* VDP Start */
		$savedVdpData = array();
		$savedVdpData['csv_file_data'] = '';
		$savedVdpData['csv_file_header_data'] = '';
		if($QrOptions['vdp_file'] != NULL && $QrOptions['svg_file'] != NULL){
			$vdpFile = $QrOptions['vdp_file'];
			$svgFile = $QrOptions['svg_file'];
			$vdpFileData = file_get_contents($svgImagePath.$vdpFile);
			$vdpData = array();
			if($vdpFileData !='' && $vdpFileData !='undefined') {
				$savedVdpData['csv_file_data'] = Mage::helper('core')->jsonDecode($vdpFileData);
			}

			$svgFileData = file_get_contents($svgImagePath.$svgFile);
			$svgData = array();
			if($svgFileData !='' && $svgFileData !='undefined') {
				$savedVdpData['csv_file_header_data'] = Mage::helper('core')->jsonDecode($svgFileData);
			}
		}
		/* VDP End */
	}
	$editPanelLabel = $this->__('Edit Panel');
	$imageGalleryPanelLabel = $this->__('Image Gallery');	
	
	$productData = $this->getProductData();		
	$productName = $productData['name'];
	$productShortDescription = $productData['shortDescription'];
	$productDescription = $productData['description'];

	$productData['name'] = '';
	$productData['shortDescription'] = '';
	$productData['description'] = '';
	
    $productOptions = $this->getProductOptions();	
	$selectedOptions = $productOptions['selectedOptions'];
	$productSize = strtolower($productOptions['size']);		
	$productQty = $productOptions['qty'];
	$dimension = explode('x',$productSize);
	$productWidth = $dimension[0];
	$productHeight = $dimension[1];	
	$noOfSides = $productOptions['noOfSides'];
	$bgColorArray = $productOptions['bgcolor'];
	$bgId = $productOptions['BgId'];
	$bgColorArray = explode('#',$productOptions['bgcolor']);
	$bgcolor = $bgColorArray[1];	
	$colorCollection = Mage::helper('designnbuy_printcolormanagement')->getPrintableColorData($productId,$front=1);
	$clipartCategoryArray = Mage::helper('clipartmanagement')->getClipartCategoryData($front=1);
	$backgroundCategoryCollection = Mage::helper('w2phtml5background/w2phtml5backgroundcategory')->getBackgroundCategory($productId,$front=1);
	$backgroundImagesCollection = Mage::helper('w2phtml5background/w2phtml5background')->backgroundImages($productId);	
	$firstclipartcatid = '';
	$userImages = Mage::helper('web2print')->getUserImages();	
	$pickcolor_multi = '';
?>
  <?php
$uniqueid = $_SESSION['uniqueid'] = md5(rand());
$locale = Mage::app()->getLocale()->getLocaleCode();

$lang_id = '';
if($locale == 'de_CH')	$lang_id = 'lang=de';
else if($locale == 'en_US')	$lang_id = 'lang=en';
else	$lang_id = '';
$customer_id = Mage::getSingleton('customer/session')->getCustomer()->getId();
?>
    <?php /* For Corporate*/?>
    <?php
$isDefaultWebsite = 0;
$customerTypeId = '';
if (Mage::helper('core')->isModuleEnabled('Designnbuy_Customermanagement')) {
    if(Mage::helper('configuration')->isDefaultWebsite()){
        $isDefaultWebsite = Mage::helper('configuration')->isDefaultWebsite();
    }
    $customerTypeOptions = json_encode(Mage::helper('configuration')->getCustomerTypeOptions());

    $customerTypeId = Mage::getSingleton('customer/session')->getCustomer()->getCustomerType();
}
?>
      <script type="text/javascript" src="<?php echo $jspath.'jquery.js'; ?>"></script>
      <script>
	var isFront = 1;
	var user = '';
	function trace(str){
		//console.log(str);
	}
	var formKey = "<?php echo Mage::getSingleton('core/session')->getFormKey();?>";
	var pageDataAry = [];	
	var currentStore = '<?php echo $locale;?>';
	var jspath = '<?php echo $jspath;?>';	
	var facebookAppId = '<?php echo $facebook;?>';
	var flickrAppId = '<?php echo $flickr;?>';
	var instagramAppId = '<?php echo $instagram;?>';
	var imageDPI = '<?php echo $imageDPI;?>';	
	var basepath = '<?php echo Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB); ?>';
	var mediapath = '<?php echo Mage::getBaseUrl('media'); ?>';
	var qrCodePath = '<?php echo Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).'uploadedImage/'; ?>';
	var qrCodeLib = '<?php echo $webpath.'designtool/phpqrcode/index.php' ?>';
	var deleteQrCodeUrl = '<?php echo Mage::getUrl('web2print/index/deleteqrcode');?>';
	var relatedSubCategoryUrl = '<?php echo Mage::getUrl('design/index/getrelatedproducttype');?>';
	var relatedProductUrl = '<?php echo Mage::getUrl('design/index/getrelatedproduct');?>';
	//var relatedClipartUrl = '<?php //echo Mage::getUrl('web2print/index/getrelatedclipart');?>';
    var relatedClipartUrl = '<?php echo Mage::getUrl('clipartmanagement/index/getclipart');?>';
	var relatedDesignIdeaUrl = '<?php echo Mage::getUrl('design/index/getrelateddesignidea');?>';
	var productPriceUrl = '<?php echo Mage::getUrl('web2print/index/productPricing');?>';
	var crossOriginalUploadUrl = '<?php echo Mage::getUrl('web2print/index/downloadImage');?>';
	var instagramChannelUrl = '<?php echo Mage::getBaseUrl().'designtool/instagram-channel.html';?>';
	var loginUrl = '<?php echo Mage::getUrl('customer/account/loginFromTool');?>';
	var registrationUrl = '<?php echo Mage::getUrl('customer/account/createNewPost');?>';
	var loginCheckUrl = '<?php echo Mage::getUrl('customer/account/loginCheck');?>';
	var saveBase64Url = '<?php echo Mage::getUrl('web2print/index/savebase64onserver');?>';
	var saveDesignUrl = '<?php echo Mage::getUrl('web2print/index/savemydesign');?>';
	var shareDesignUrl = '<?php echo Mage::getUrl('web2print/index/sharemydesign');?>';
	var generatePreviewPngUrl = '<?php echo Mage::getUrl('web2print/index/generatePreviewPng');?>';
	var generateRootPngUrl = '<?php echo Mage::getUrl('threed/index/generateRootPng');?>';//for 3d preivew
	var previewPdfUrl = '<?php echo Mage::getUrl('web2print/index/previewPdf');?>';
	var backgroundImageUrl = '<?php echo Mage::getUrl('w2phtml5background/W2phtml5background/background/');?>';
	var relatedLayoutUrl = '<?php echo Mage::getUrl('web2print/index/getProductLayouts');?>';
	var addToCartUrl = '<?php echo Mage::getUrl('checkout/cart/add');?>';
	var cartUrl = '<?php echo Mage::getUrl('checkout/cart');?>';
	var userImagesUrl = '<?php echo Mage::getUrl('web2print/index/getUserImages');?>';
	var welcomeMessageUrl = '<?php echo Mage::getUrl('web2print/index/welcomeMessage');?>';
	var updateTopLinksUrl = '<?php echo Mage::getUrl('web2print/index/updateTopLinks');?>';
	var topCartsUrl = '<?php echo Mage::getUrl('web2print/index/topCarts');?>';
	var FBredirectURI = '<?php echo Mage::getBaseUrl().'designtool/FBredirectURI.html';?>';
	var removeuserImagesUrl = '<?php echo Mage::getUrl('web2print/index/removeUserImages');?>';
  var uploadImageURL = '<?php echo Mage::getUrl('web2print/index/uploadFiles/')?>';
	var uniqueid = '<?php echo $uniqueid;?>';	
	var productData = <?php echo json_encode($productData);?>;	
	//var jsonProductData =  $j.parseJSON(productData);
	var jsonProductData =  productData;
	var productId = jsonProductData.id;
	var bleedMargin = jsonProductData.bleedMargin;
	var safeMargin = jsonProductData.safeMargin;
	var baseUnit = jsonProductData.baseUnit;	
	customColorList = jsonProductData.backgroundColor;
	var productWidth = '<?php echo $productWidth;?>';
	var productHeight = '<?php echo $productHeight;?>';
	// var productOptions = new Array();
	var productOptions = <?php echo json_encode($productOptions);?>;
	
	// var selectedOptions = new Array();
	var selectedOptions = <?php echo json_encode($selectedOptions);?>;
	var fontUrl = '<?php echo Mage::getUrl('designnbuy_fontmanagement/index/font');?>';
	var clipartCategorylist = <?php echo json_encode($clipartCategoryArray);?>;
	//jsonclipartCategoryList = $j.parseJSON(clipartCategorylist);
	jsonclipartCategoryList = clipartCategorylist;
	// var fontlist = <?php //echo $fontArray;?>;
	//jsonfontList = $j.parseJSON(fontlist);
	// jsonfontList = fontlist;
	var colorlist = <?php echo $colorCollection;?>;
	//printableColorList = $j.parseJSON(colorlist);	
	printableColorList = colorlist;	
	var backgroundCategorylist = '<?php echo $backgroundCategoryCollection;?>';
	jsonbackgroundCategorylist = $j.parseJSON(backgroundCategorylist);
	var backgroundImageslist = '<?php echo $backgroundImagesCollection;?>';
	jsonbackgroundImageslist = $j.parseJSON(backgroundImageslist);
	var BgId = [];
	if($j.parseJSON('<?php echo $bgId; ?>')){
		BgId = $j.parseJSON('<?php echo $bgId; ?>');
		for(var bgIndex = 0; bgIndex < BgId.length; bgIndex++){
			if(BgId[bgIndex] == "undefined"){
				BgId[bgIndex] = "";
			}
		}
	}
	var no_of_side = '<?php echo $noOfSides;?>';
	var first_font;
	var png_data;	
	var filearray = new Array();	
	var customer_id = '<?php echo $customer_id;?>';
	var productid = '<?php echo $productId;?>';
	var added_images = '';
	var fonts_used = '';
	var configtotalprice = '<?php echo $this->__('Total Price');?>';
	var configselectedsize = '<?php echo $this->__('Selected Size');?>';
	var configqtymessage = '<?php echo $this->__('Please choose appropriate Quantity.');?>';
	var clipartsloaded = 0;
	var designIdeasloaded = 0;
	var curr_side_id = 1;
	var datauri;
	var productImageCan;
	var customizationCan;
	var priceInterval;
	var action;
	var shareType;
	var sideNameAry = ['Front','Back','Left','Right'];
	var toolType = "web2print"; //possible values: "producttool", "web2print"
	var colorPicker = jsonProductData.colorPickerMode;		
	var pickerMode = colorPicker.toLowerCase();  //possible values: "Full Color Picker", "Printable Colors",	
	var bgcolorPicker = jsonProductData.bgcolorPickerMode;
	var bgpickerMode = bgcolorPicker.toLowerCase(); //possible values: "Full Color Picker", "Custom Options", "Printable Colors"	
	var qty = '<?php echo $productQty; ?>';	
	var bgColor;
	var borderColor;
	if(pickerMode=="printable"){
		firstColor = printableColorList[0].colorCode;		
		borderColor = firstColor.substring(1);		
	}else{
		borderColor = "000";
	}
	if(bgpickerMode=="custom"){
		bgColor = "<?php echo $bgcolor; ?>";
	}else if(bgpickerMode=="printable"){
		firstColor = printableColorList[0].colorCode;		
		bgColor = firstColor.substring(1);		
	}else{
		bgColor = "FFF";
	}	
	
	if(<?php echo count($pageDataAry) ?>){
		var pageDataAry = <?php echo json_encode($pageDataAry);?>;
	}else{
		for(var i = 0; i<no_of_side; i++){
			pageDataAry[i] = "";
		}
	}
	
	var cartId = '<?php echo $cart_id; ?>';
	var designData = {};
	designData = $j.parseJSON('<?php echo json_encode($designData); ?>');
	/*VDP Start*/
	var savedVdpData = <?php echo json_encode($savedVdpData); ?>;
	/*VDP End*/
	var extensionArray = [ '../DO_NOT_UPLOAD/extensions/ext-grid.js','../DO_NOT_UPLOAD/extensions/ext-multiColor.js',/*'../DO_NOT_UPLOAD/extensions/ext-curveText.js',*/'../DO_NOT_UPLOAD/extensions/ext-objectPanel.js','../DO_NOT_UPLOAD/extensions/ext-objectLock.js','../DO_NOT_UPLOAD/extensions/ext-LayerPanel.js'/*,'../DO_NOT_UPLOAD/extensions/ext-photoCollage.js'*/,'../DO_NOT_UPLOAD/extensions/ext-textArea.js','../DO_NOT_UPLOAD/extensions/ext-TextQuickEdit.js','../DO_NOT_UPLOAD/extensions/ext-textShape.js','../DO_NOT_UPLOAD/extensions/ext-recentColors.js','../DO_NOT_UPLOAD/extensions/ext-pickDesignColor.js','../DO_NOT_UPLOAD/extensions/ext-fliptools.js','../DO_NOT_UPLOAD/extensions/ext-imageEffect.js','../DO_NOT_UPLOAD/extensions/ext-vdp.js','../DO_NOT_UPLOAD/extensions/ext-undoRedo.js','../DO_NOT_UPLOAD/extensions/ext-corporate.js','../DO_NOT_UPLOAD/extensions/ext-ShowObjectSize.js','../DO_NOT_UPLOAD/extensions/ext-guidelines.js','../DO_NOT_UPLOAD/extensions/ext-getquote.js','../DO_NOT_UPLOAD/extensions/ext-threedpreview.js'];
    /*For Corporate*/
    var formkey = "<?php echo Mage::getSingleton('core/session')->getFormKey();?>";
    var jobId = '<?php echo $jobId;?>';
    var customerTypeOptions = '<?php echo $customerTypeOptions;?>';
    customerTypeOptions = $j.parseJSON(customerTypeOptions);
    var customerTypeId = '<?php echo $customerTypeId; ?>';
    var isDefaultWebsite = '<?php echo $isDefaultWebsite; ?>';
    var sendJobRequestUrl = '<?php echo Mage::getUrl('designnbuy_corporatejob/corporatejob/sendJobRequest');?>';
	var getQuoteUrl = '<?php echo Mage::getUrl('quotations/index/addItem');?>';
	var quoteCartUrl = '<?php echo Mage::getUrl('quotations/index');?>';
	var quoteId = '<?php echo $quoteId;?>';
</script>

      <link rel="stylesheet" href="<?php echo $jspath.'picasa/gallery.css'; ?>">
      <link rel="stylesheet" href="<?php echo $jspath.'css/jquery.jscrollpane.css'; ?>">
      <link rel="stylesheet" href="<?php echo $jspath.'css/pick-a-color-1.1.8.min.css'; ?>">
      <link rel="stylesheet" type="text/css" href="<?php echo $jspath; ?>fancybox/jquery.fancybox.css" media="screen" />
      <link rel="stylesheet" type="text/css" href="<?php echo $jspath; ?>fancybox/helpers/jquery.fancybox-buttons.css?v=1.0.5"
      />
      <link rel="stylesheet" href="<?php echo $jspath ?>plupload/jquery.plupload.queue.css" type="text/css" />


      <link rel="stylesheet" href="<?php echo $jspath.'plupload/jquery.plupload.queue.css'; ?>" type="text/css" />
      <link rel="stylesheet" href="<?php echo $jspath.'css/font-awesome.css'; ?>" type="text/css" />
      <link rel="stylesheet" href="<?php echo $jspath.'css/web2print.css'; ?>" type="text/css" />
      <!--<link rel="stylesheet" href="<?php echo $jspath.'css/jPicker.css'; ?>" type="text/css" />
<link rel="stylesheet" href="<?php echo $jspath.'css/jgraduate.css'; ?>" type="text/css" />-->
      <link rel="stylesheet" href="<?php echo $jspath.'css/plugins.css'; ?>" type="text/css" />


      <?php
$langCss='';
if($locale == "de_DE"){
	$langCss = 'css/web2print_german.css';
}
if($locale == "fr_FR"){
	$langCss = 'css/web2print_french.css';
}
if($langCss != ""){
?>
        <link rel="stylesheet" href="<?php echo $jspath.$langCss; ?>">
        <?php
}
?>
          <script type="text/javascript" src="<?php echo $jspath.'js-hotkeys/jquery.hotkeys.min.js'; ?>"></script>
          <script type="text/javascript" src="<?php echo $jspath.'jquerybbq/jquery.bbq.min.js'; ?>"></script>
          <script type="text/javascript" src="<?php echo $jspath.'svgicons/jquery.svgicons.js'; ?>"></script>
          <script type="text/javascript" src="<?php echo $jspath.'jquery-ui/jquery-ui.min.js'; ?>"></script>
          <script type="text/javascript" src="<?php echo $jspath.'jgraduate/jpicker.js'; ?>"></script>
          <script type="text/javascript" src="<?php echo $jspath.'jgraduate/jquery.jgraduate.min.js'; ?>"></script>
          <script type="text/javascript" src="<?php echo $jspath.'spinbtn/JQuerySpinBtn.js'; ?>"></script>
          <script type="text/javascript" src="<?php echo $jspath.'contextmenu/jquery.contextMenu.js'; ?>"></script>
          <!--Added By Ajay for font cached in window local storage start-->
          <script type="text/javascript" src="<?php echo $jspath.'bagjs/bag.js'; ?>"></script>
          <!--Added By Ajay for font cached in window local storage end-->
          <?php if($_SERVER['HTTP_HOST'] == "192.168.0.222" || $_SERVER['HTTP_HOST'] == "192.168.0.139" || $_SERVER['HTTP_HOST'] == "192.168.0.46") { ?>

          <script type="text/javascript" src="<?php echo $jspath.'DO_NOT_UPLOAD/pathseg.js'; ?>"></script>
          <script type="text/javascript" src="<?php echo $jspath.'DO_NOT_UPLOAD/touch.js'; ?>"></script>
          <script type="text/javascript" src="<?php echo $jspath.'DO_NOT_UPLOAD/browser.js'; ?>"></script>
          <script type="text/javascript" src="<?php echo $jspath.'DO_NOT_UPLOAD/svgtransformlist.js'; ?>"></script>
          <script type="text/javascript" src="<?php echo $jspath.'DO_NOT_UPLOAD/math.js'; ?>"></script>
          <script type="text/javascript" src="<?php echo $jspath.'DO_NOT_UPLOAD/units.js'; ?>"></script>
          <script type="text/javascript" src="<?php echo $jspath.'DO_NOT_UPLOAD/svgutils.js'; ?>"></script>
          <script type="text/javascript" src="<?php echo $jspath.'DO_NOT_UPLOAD/sanitize.js'; ?>"></script>
          <script type="text/javascript" src="<?php echo $jspath.'DO_NOT_UPLOAD/select.js'; ?>"></script>
          <script type="text/javascript" src="<?php echo $jspath.'DO_NOT_UPLOAD/history.js'; ?>"></script>
          <script type="text/javascript" src="<?php echo $jspath.'DO_NOT_UPLOAD/draw.js'; ?>"></script>
          <script type="text/javascript" src="<?php echo $jspath.'DO_NOT_UPLOAD/path.js'; ?>"></script>
          <script type="text/javascript" src="<?php echo $jspath.'DO_NOT_UPLOAD/md5-min.js'; ?>"></script>
          <script type="text/javascript" src="<?php echo $jspath.'DO_NOT_UPLOAD/dnb/DNBBaseObject.js'; ?>"></script>
          <script type="text/javascript" src="<?php echo $jspath.'DO_NOT_UPLOAD/Margin.js'; ?>"></script>
          <script type="text/javascript" src="<?php echo $jspath.'DO_NOT_UPLOAD/web2print-src.js'; ?>"></script>
          <script type="text/javascript" src="<?php echo $jspath.'DO_NOT_UPLOAD/svgcanvas.js'; ?>"></script>
          <script type="text/javascript" src="<?php echo $jspath.'DO_NOT_UPLOAD/svg-editor.js'; ?>"></script>
          <script type="text/javascript" src="<?php echo $jspath.'DO_NOT_UPLOAD/locale/locale.js'; ?>"></script>
          <script type="text/javascript" src="<?php echo $jspath.'DO_NOT_UPLOAD/contextmenu.js'; ?>"></script>
          <script type="text/javascript" src="<?php echo $jspath.'DO_NOT_UPLOAD/extensions/ext-navigation.js'; ?>"></script>
          <script type="text/javascript" src="<?php echo $jspath.'DO_NOT_UPLOAD/extensions/ext-web2print.js'; ?>"></script>
          <script type="text/javascript" src="<?php echo $jspath.'DO_NOT_UPLOAD/font_jsapi.js'; ?>"></script>
          <script type="text/javascript" src="<?php echo $jspath.'DO_NOT_UPLOAD/font-selector.js';?>"></script>
          <script type="text/javascript" src="<?php echo $jspath.'DO_NOT_UPLOAD/raphael.js'; ?>"></script>
          <script type="text/javascript" src="<?php echo $jspath.'DO_NOT_UPLOAD/plupload/moxie.js'; ?>"></script>
          <script type="text/javascript" src="<?php echo $jspath.'DO_NOT_UPLOAD/plupload/plupload.dev.js'; ?>"></script>
          <script type="text/javascript" src="<?php echo $jspath.'DO_NOT_UPLOAD/plupload/jquery.ui.plupload.js'; ?>"></script>
          <script type="text/javascript" src="<?php echo $jspath.'DO_NOT_UPLOAD/plupload/imageuploader.js'; ?>"></script>
          <script type="text/javascript" src="<?php echo $jspath.'DO_NOT_UPLOAD/jquery.jscrollpane.js'; ?>"></script>
          <script type="text/javascript" src="<?php echo $jspath.'DO_NOT_UPLOAD/jquery.simple-color.js'; ?>"></script>
		   <script type="text/javascript" src="<?php echo $jspath.'color-thief.js'; ?>"></script>
          <script type="text/javascript" src="<?php echo $jspath.'jquery-ui.js'; ?>"></script>
         
          <!-- For custom options Start-->
          <script type="text/javascript" src="<?php echo $jspath.'DO_NOT_UPLOAD/dependentoptions.js'; ?>"></script>
          <!-- For custom options End -->
          <?php } else { ?>
			 <script type="text/javascript" src="<?php echo $jspath.'color-thief.js'; ?>"></script>
          <script type="text/javascript" src="<?php echo $jspath.'jquery-ui.js'; ?>"></script>
          <script type="text/javascript" src="<?php echo $jspath.'built.canvas.min.js'; ?>"></script>
          <script type="text/javascript">
extensionArray = [];
</script>

          <?php } ?>

          <script src="<?php echo $jspath.'threejs/build/three.min.js'; ?>"></script>
          <script src="<?php echo $jspath.'threejs/build/OBJLoader.js'; ?>"></script>
          <script src="<?php echo $jspath.'threejs/build/OrbitControls.js'; ?>"></script>
          <script src="<?php echo $jspath.'threejs/build/Detector.js'; ?>"></script>
          <script src="<?php echo $jspath.'threejs/build/Projector.js'; ?>"></script>
          <script src="<?php echo $jspath.'threejs/build/CanvasRenderer.js'; ?>"></script>
          <script src="<?php echo $jspath.'threejs/build/stats.min.js'; ?>"></script>

          <script type="text/javascript" src="<?php echo $jspath.'plupload/i18n/'.$locale.'.js'; ?>"></script>
          <script type='text/javascript' src='<?php echo $jspath.' picasa/picasa.js '; ?>'></script>
          <script type="text/javascript" src="<?php echo $jspath.'flickr/jquery.flickr.js'; ?>"></script>

          <script type="text/javascript">//google.load("webfont", "1");</script>
          <!--<script>$j(function(){Smm.init('tool_font_family');});</script>-->
          <!-- Font Loader End -->
          <!-- Add fancyBox main JS and CSS files -->
          <script type="text/javascript" src="<?php echo $jspath; ?>fancybox/jquery.fancybox.js"></script>

          <!-- Add Button helper (this is optional) -->
          <script type="text/javascript" src="<?php echo $jspath; ?>fancybox/helpers/jquery.fancybox-buttons.js?v=1.0.5"></script>
          <!-- Image Upload Start-->
          <script type="text/javascript" src="<?php echo $jspath ?>jquery-ui/jquery.ui.touch-punch.js"></script>
          <!-- Image Upload End-->
          <!--
<script type="text/javascript">
  WebFontConfig = {
    google: { families: [ 'Lato::latin' ] }
  };
  (function() {
    var wf = document.createElement('script');
    wf.src = ('https:' == document.location.protocol ? 'https' : 'http') +
      '://ajax.googleapis.com/ajax/libs/webfont/1/webfont.js';
    wf.type = 'text/javascript';
    wf.async = 'true';
    var s = document.getElementsByTagName('script')[0];
    s.parentNode.insertBefore(wf, s);
  })(); </script>-->
          <div id="container_dt">
            <div id="loaderImage-overlay" style="display:none;"></div>
            <div id="loaderImage" style="left: 281px;">
              <!--<div id="loaderImage-loader"></div>-->
              <img src="<?php echo $jspath.'images/loader.gif' ?>" />
            </div>
            <div class="wraper_dt">
              <!-- Control Panel & Canvas Area-->
              <div>
                <div class="main_dt">
                  <div class="align_heading_div">
                    <!--<div class="canvas-heading"><span><?php echo $this->__('PREVIEW');?></span></div>-->
                  </div>
                  <section id="white">
                    <div class="left-share-panel">
                      <div class="button-area">
                        <ul>

                          <li>
                            <button id="info" title="Product Info">
                <div>
				<span class="d_info"><i class="fa fa-info"></i></span>
				<span id="productInfoCaption" class="caption">info</span>
				</div>
                </button>
                          </li>
                          <li>
                            <button id="zoomButton" title="Set Zoom level">
                <div>
				<span class="d_zoom"><i class="fa fa-search-plus"></i></span>
				<span id="productZoomCaption" class="caption">Zoom</span>
				</div>
                </button>
                          </li>
                          <li>
                            <button id="preview" title="Preview">
                <div>
				<span class="d_preview"><i class="fa fa-eye"></i></span>
				<span id="productPreviewCaption" class="caption">Preview</span>
				</div>
                </button>
                          </li>
                          <li>
                            <button id="save_image" title="Save Design" onclick="javascript:action='save'; save_share('save');">
                <div>
				<span class="d_savenote"><i class="fa fa-floppy-o"></i></span>
				<span id="productSaveCaption" class="caption">Save</span>
				</div>
                </button>
                          </li>
                        </ul>
                      </div>
                    </div>
                    <section class="center-share-panel">
                      <div id="pageNav"> <span class="firstPage"></span> <span class="prevPage"></span> <span class="gotoPage">
            <input type="numeric" id="gotoPageTxt" name="gotoPageTxt" maxlength="2" />
            </span> <span class="nextPage"></span> <span class="lastPage"></span> </div>
                    </section>
                    <section class="right-share-panel">
                      <div class="button-area">
                        <ul>
                          <!--<li>
                <button id="facebook_share" title="Share on Facebook" onclick="javascript:save_share('facebook');">
                <div>
				<span class="fb"><i class="fa fa-facebook"></i></span>		
				<span id="fbShareCaption" class="caption">Share</span>
				
				</div>
                </button>
              </li>-->
              <li> Want us to do the design work? <a href="https://www.logomatology.com/custom-artist-design-logo-mats.html"  target="_blank">CLICK HERE.</a></li>
                          <li>
                            <button id="twitter_share" title="Tweet on Twitter" onclick="javascript:save_share('twitter');">
                <div>
				<span class="tw"><i class="fa fa-twitter"></i></span>
				<span id="twitterShareCaption" class="caption">Tweet</span>
				</div>
                </button>
                          </li>
                          <li>
                            <button id="pinterest_share" title="Pin design on Pinterest" onclick="javascript:save_share('pinterest');">
                <div>
				<span class="pnt"><i class="fa fa-pinterest"></i></span>
				<span id="pinitShareCaption" class="caption">Pin</span>
				</div>
                </button>
                          </li>
                          <!--<li><button id="addtocart_btn" title="Add To Cart"><div><span class="crt">&nbsp;</span></div></button></li>-->
                        </ul>
                      </div>
                    </section>
                  </section>
                  <section class="left-panel">
                    <div class="button-area">
                      <ul>
                        <!-- <li>
              <button id="fullScreen" title="Full Screen">
              <div><i class="lt_layerPanel">&nbsp;</i></div>
              </button>
            </li>-->
                        <li>
                          <button id="layerPanel" title="Layer Panel">
              <div><i class="lt_layerPanel">&nbsp;</i></div>
              </button>
                        </li>
                        <li>
                          <button id="alignPanel" class="disabled" title="Align Panel">
              <div><i class="lt_alignPanel">&nbsp;</i></div>
              </button>
                        </li>
                        <li>
                          <!--<div id="multiselected_panel">-->
                          <button id="tool_group" class="disabled" title="Group">
              <div><i class="lt_tool_group">&nbsp;</i></div>
              </button>
                          <!--</div>
							<div id="g_panel">-->
                          <button id="tool_ungroup" title="Ungroup" class="disabled" style="display:none">
              <div><i class="lt_tool_ungroup">&nbsp;</i></div>
              </button>
                          <!--</div>-->

                        </li>

                        <!--<li><button id="addNote"  title="Add Note"><div><i class="lt_addNote">&nbsp;</i></div></button></li>-->
                        <!--<li><button id="tool_move_b" title="Send to Back"><div><span class="icon-5">&nbsp;</span></div></button></li>
				<li><button id="tool_move_t" title="Bring to Front"><div><span class="icon-6">&nbsp;</span></div></button></li>-->

                        <li>
                          <button id="tool_undo_custom" title="Undo">
              <div><i class="lt_tool_undo">&nbsp;</i></div>
              </button>
                        </li>
                        <li>
                          <button id="tool_redo_custom" title="Redo">
              <div><i class="lt_tool_redo">&nbsp;</i></div>
              </button>
                        </li>
                        <li>
                          <button id="tool_flipHoriz" title="Flip Horizontal" class="disabled">
			  <div><i class="lt_tool_flipH">&nbsp;</i></div>
			  </button>
                        </li>
                        <li>
                          <button id="tool_flipVert" title="Flip Vertical" class="disabled">
			  <div><i class="lt_tool_flipV">&nbsp;</i></div>
			  </button>
                        </li>
                        <!--<li>
                                <div class="push_button" id="tool_clone" title="Duplicate"></div>
                            </li>
                            <li>
                                <div class="color_tool" id="tool_opacity_delete" title="Delete">
                                    <button title="Delete Element" id="tool_delete"><div><span class="icon-10">&nbsp;</span></div></button>
                                </div>
                            </li>-->

                        <li>
                          <button id="tool_delete" class="disabled" title="Delete">
              <div><i class="lt_tool_delete">&nbsp;</i></div>
              </button>
                        </li>
                        <li>
                          <button id="tool_clone_side" title="Duplicate Side">
              <div><i class="lt_page_clone">&nbsp;</i></div>
              </button>
                        </li>
                        <li>
                          <button id="tool_clone" title="Duplicate" class="disabled">
              <div><i class="lt_tool_clone">&nbsp;</i></div>
              </button>
                        </li>
                        <li>
                          <button id="tool_cut" title="Cut" class="disabled">
              <div><i class="lt_tool_cut">&nbsp;</i></div>
              </button>
                        </li>
                        <li>
                          <button id="tool_copy" title="Copy" class="disabled">
              <div><i class="lt_tool_copy">&nbsp;</i></div>
              </button>
                        </li>
                        <li>
                          <button id="tool_paste" title="Paste" class="disabled">
              <div><i class="lt_tool_paste">&nbsp;</i></div>
              </button>
                        </li>
                        <li>
                          <button id="tool_clear" title="Delete All">
              <div><i class="lt_tool_clear">&nbsp;</i></div>
              </button>
                        </li>
                        <li>
                          <button title="Studio Help" id="Help">
              <div><i class="lt_tool_help">&nbsp;</i></div>
              </button>
                        </li>
                      </ul>
                    </div>
                    <!-- Object align Panel
					<div id="objectLayer" class="object-layer" align="center">
                    	<ul>           
							<li><span class="layer_top"></span></li>
							<li><span class="layer_up"></span></li>
							<li><span class="layer_down"></span></li>                      
							<li><span class="layer_bottom"></span></li>                        
                        </ul>
                    </div> -->
                  </section>
                  <!--<section class="left-panel">
					<div class="button-area">
						<ul>
							<li><button id="alignPanel" title="Align Panel"><div class="disabled"><i class="lt_alignPanel">&nbsp;</i></div></button></li>
							<li>
							<button id="tool_group" class="disabled" title="Group"></button>
							<button id="tool_ungroup" title="Ungroup" class="disabled" style="display:none"></button>
							</li>
							<li><button id="tool_undo" title="Undo"><div><span class="tool_undo">&nbsp;</span></div></button></li>
							<li><button id="tool_redo" title="Redo"><div><span class="tool_redo">&nbsp;</span></div></button></li>
							<li><button id="tool_cut" title="Cut"><div><span class="tool_cut">&nbsp;</span></div></button></li>
							<li><button id="tool_copy" title="Copy"><div><span class="tool_copy">&nbsp;</span></div></button></li>
							<li><button id="tool_paste" title="Paste"><div><span class="tool_paste">&nbsp;</span></div></button></li>
							<li><button id="tool_clear" title="Delete All"><div><span class="icon-10">&nbsp;</span></div></button></li>
						</ul>
					</div>
					<div id="objectLayer" class="object-layer" align="center">
                    	<ul>           
							<li><span class="layer_top">&nbsp;</span></li>
							<li><span class="layer_up">&nbsp;</span></li>
							<li><div class="layer_sign">&nbsp;</div></li>
							<li><span class="layer_down">&nbsp;</span></li>                      
							<li><span class="layer_bottom">&nbsp;</span></li>                        
                        </ul>
                    </div>
				</section>-->
                  <div id="zoomOptions" style="display:none">
                    <input class="inputBoxMedium" id="zoom" size="3" value="100" type="text" style="display:none" />
                    <ul>
                      <li>400%</li>
                      <li>200%</li>
                      <li>100%</li>
                      <li>50%</li>
                      <li>25%</li>
                      <li id="fit_to_canvas" data-val="canvas">Fit to canvas</li>
                      <li id="fit_to_sel" data-val="selection">Fit to selection</li>
                      <!--<li id="fit_to_layer_content" data-val="layer">Fit to layer content</li>-->
                      <li id="fit_to_all" data-val="content">Fit to screen</li>
                    </ul>
                  </div>

                  <!--<section class="bottom-share-panel" style="display:none;">
					<div class="button-area">
						<ul><li><button id="tool_group" title="Group"></button></li></ul>
					</div>
				</section>-->
                  <section class="product-area">
                    <!-- SVG Editor -->
                    <form id="manage_side" method="post">
                      <input type="hidden" value="2" id="number_of_pages" name="number_of_pages" autocomplete="off">
                      <input type="hidden" value="1" id="current_page" name="current_page" autocomplete="off">
                      <!--<div style="display:none;" title="" id="pages_data"> <?php //echo $text_area; ?> </div>-->
                      <textarea id="svg_source_textarea" style="display:none;" spellcheck="false"></textarea>
                    </form>
                    <div id="svg_editor">
                      <div id="rulers" style="display:none">
                        <!-- Ruler will display if showRulers is true in svgEditor.setConfig   -->
                        <div id="ruler_corner"></div>
                        <div id="ruler_x">
                          <div>
                            <canvas height="15"></canvas>
                          </div>
                        </div>
                        <div id="ruler_y">
                          <div>
                            <canvas width="15"></canvas>
                          </div>
                        </div>
                      </div>
                      <div id="workarea">
                        <style id="styleoverrides" type="text/css" media="screen" scoped="scoped"></style>
                        <div id="svgcanvas" style="position:relative"> </div>
                      </div>
                    </div>
                  </section>
                  <section class="right-panel">
                    <button id="productSettingTab" class="top-right-corner">
        <div><span class="icon-1"></span> </div>
        <div class="caption" id="productsettingCaption">Product</div>
        </button>
                    <button id="addtext">
        <div><span class="icon-4"></span> </div>
        <div class="caption" id="addTextCaption">Text</div>
        </button>
                    <button id="addart">
        <div><span class="icon-3"></span> </div>
        <div class="caption" id="chooseArtCaption">Art</div>
        </button>
                    <button id="addtimage">
        <div><span class="icon-5"></span> </div>
        <div class="caption" id="addImageCaption">Upload</div>
        </button>
                    <button id="addLayout" style="display:none;">
        <div><span class="icon-6"></span> </div>
        <div class="caption" id="addShapesCaption">Layouts</div>
        </button>
                    <!--<button id="addshape">
        <div><span class="icon-6"></span> </div>
        <div class="caption" id="addShapesCaption">Shapes</div>
        </button>-->
                    <!--<button id="qrCode">
        <div><span class="icon-2"></span> </div>
        <div class="caption" id="qrCodeCaption">QR</div>
        </button>-->
                    <!--<button id="desingidea" class="top-bottom-corner"><div><span class="icon-7"></span><?php echo $this->__('Design Ideas'); ?></div></button>-->
                  </section>
                  <!--<div id="transformPanel">
					<div class="move-object-panel dragablePanel">
						<div class="mop-heading"><span class="mop-close">x</span></div>
							<div class="ranges-content">
								<div class="moving-knob">
									<input id="knobAngle" class="knob" data-step="1" data-displayprevious=true data-min="0" data-max="360" data-width="65" data-cursor=true  value="0">
								</div>
								<div class="joystic-button">
									<div class="toward-up"><button id="upMove"><span>&nbsp;</span></button></div>
									<div class="toward-left"><button id="leftMove"><span>&nbsp;</span></button></div>
									<div class="toward-down"><button id="downMove"><span>&nbsp;</span></button></div>
									<div class="toward-right"><button id="rightMove"><span>&nbsp;</span></button></div>
								</div>
							</div>
					</div>
				</div>-->
                </div>
              </div>
              <!-- Slider Panel -->
              <section class="right-panel-property">
                <div id="color_picker"></div>
                <ul id="cmenu_background" class="contextMenu">
                  <li id="repeatEach">Repeat on each side</li>
                  <li id="onlyThis">Use only for this side</li>
                </ul>
                <nav class="cbp-spmenu cbp-spmenu-vertical cbp-spmenu-right" id="productSettingPanel">
                  <div class="box-outer">
                    <div class="aproduct-data">
                      <label class="label"><?php echo $this->__('Product Settings');?></label>
                      <button id="tool_choose_prod_close"></button>
                      <div class="showhidegridnruler range-blocks">
                        <label class="sgrid_icon">&nbsp;</label>
                        <div class="onoffswitch" id="grid-switch">
                          <input type="checkbox" name="onoffswitch" class="onoffswitch-checkbox" id="grid_switch">
                          <label class="onoffswitch-label" for="grid_switch">
              <div class="onoffswitch-inner"></div>
              <div class="onoffswitch-switch"></div>
              </label>
                        </div>
                        <!--<input type="checkbox" value="show_rulers" id="show_rulers">-->
                        <label class="sruler_icon">&nbsp;</label>
                        <div class="onoffswitch" id="ruler-switchs">
                          <input type="checkbox" name="onoffswitch" class="onoffswitch-checkbox" id="show_rulers" checked>
                          <label class="onoffswitch-label" for="show_rulers">
              <div class="onoffswitch-inner"></div>
              <div class="onoffswitch-switch"></div>
              </label>
                        </div>
                      </div>

                      <div class="range-blocks" style="display:none;">
                        <div class="caption-section">
                          <div id="borderCaption" class="caption">
                            <?php echo $this->__('BORDER'); ?>
                          </div>
                        </div>
                        <div class="clrnbg-section">
                          <div id="sizeCaption" class="captionSmall">SIZE</div>
                          <input id="borderWidthValueLabel" class="slider_itag" title="" size="2" type="text" data-attr="Border Width" disabled="disabled"
                          />
                          <span class="unitSpan inch"><?php echo $productData['baseUnit']; ?></span>
                          <div class="psright">
                            <div class="left">
                              <label class="stroke_tool no-padding no-margin">
					<select id="border_stroke_style" title="Change border dash style" onchange="updateBorder();">
					  <option selected="selected" value="none">&mdash;</option>
					  <option value="2,2">...</option>
					  <option value="5,5">- -</option>
					  <option value="5,2,2,2">- .</option>
					  <option value="5,2,2,2,2,2">- ..</option>
					</select>
				  </label>
                            </div>
                            <div class="color_tool" id="tool_border_color">
                              <div class="color_block">
                                <div id="border_stroke_bg"></div>
                                <div id="border_color" class="color_block" title="Change Border color"></div>
                              </div>
                            </div>
                          </div>

                        </div>
                        <div class="ranger-area">
                          <!--<input type="range" name="borderWidthSlider" id="borderWidthSlider" value="1" min="0" max="10" data-highlight="true" data-theme="a" data-track-theme="b" oninput="updateBorder()" onchange="updateBorder()"/>-->
                          <div id="borderWidthSlider"></div>
                        </div>
                        <div class="range-blocks">
                          <div class="caption-section">
                            <div id="marginCaption" class="caption">
                              <?php echo $this->__('MARGIN'); ?>
                            </div>
                            <input id="borderMarginValueLabel" class="slider_itag" size="3" value="100" type="text" disabled="disabled" />
                            <span class="unitSpan inch"><?php echo $productData['baseUnit']; ?></span>
                          </div>
                          <div class="ranger-area">
                            <div id="borderMarginSlider"></div>
                          </div>
                        </div>
                      </div>

                      <div class="range-blocks" style="display:none;">
                        <div class="caption-section">
                          <div id="backgroundCaption" class="caption">BACKGROUND</div>
                        </div>
                        <div class="clrnbg-section">
                          <div id="colorCaption" class="caption">COLOR</div>
                          <div class="left">
                            <div class="color_tool" id="tool_bgcolor_color">
                              <div class="color_block">
                                <div id="bgcolor_stroke_bg"></div>
                                <div id="bgcolor_color" class="color_block" title="Change Background color"></div>
                              </div>
                            </div>
                          </div>
                          <!--<input type="text" value="222" name="border-color" class="pick-a-color">-->
                          <div class="left" style="display:none;">
                            <div id="imageCaption" class="caption">IMAGE</div>
                            <input type="button" id="bgImageBtn" name="bgImageBtn" value="Background Image" title="Add Background Image" />
                            <input type="button" id="removeImageBtn" name="bgImageBtn" value="Remove Background Image" title="Remove Background Image"
                            />
                          </div>
                        </div>

                      </div>
                      <div>
                        <!--<div id="productoptionsCaption" class="caption">ORder Details</div>-->
                        <div id="prodOptions" class="caption-section">
                          <!--<br class="clearer">-->
                          <div class="customOptionDiv">
                            <form name="customOptionFormProdSettings" id="customOptionFormProdSettings" name="customOptionForm" action="" method="POST">
                              <div id="customOptioonConatinerProdSettings"></div>
                            </form>
                          </div>
                        </div>
                      </div>
                      <!--<div class="caption-section">
            <div class="object-inputs">
              <div class="ajax-loader" id="priceAjaxLoader" style="display: none;"><img src="http://192.168.0.222/dnb_products/Test/web2printhtml5/js/html5/images/ajax-loader.gif"> </div>
              <label id="qtyCaption" class="captionSmall">Quantity</label>
              <input type="text" value="<?php echo $productQty; ?>" id="quantityBox" onkeyup="livePrice();" class="inputBoxMedium" autocomplete="off">
            </div>
            <div class="clear"></div>
          </div>-->
                    </div>

                    <!--	<div class="tshirt-product scroll-area">
                    	<div class="scroll-area-chooseprd">
                    		
                        </div>
                   </div>-->
                  </div>
                </nav>
                <nav class="cbp-spmenu cbp-spmenu-vertical cbp-spmenu-right cbp-spmenu-open" id="layer-panel">
                  <div>
                    <div class="caption-section">
                      <div id="manageLayersCaption" class="caption">Manage Layers</div>
                    </div>
                    <div class="input-area">
                      <div class="scroll-area">
                        <div id="sortableLayerPanel" class="layer-object"></div>
                      </div>
                    </div>
                  </div>
                </nav>
                <nav class="cbp-spmenu cbp-spmenu-vertical cbp-spmenu-right" id="addtocart-panel">
                  <div>
                    <label class="label no-display"><?php echo Mage::helper('design')->__('Add to Cart');?></label>
                    <div class="inst" id="inst">i</div>
                    <div style="display:none;" id="design_tooltip" class="tooltip"> 1.
                      <?php echo Mage::helper('design')->__('Ungroup does one level layering, so to do further layering, ungroup again until you can select the element you want to change color for. ');?><br>
                      <br> 2.
                      <?php echo Mage::helper('design')->__('Select any layer to change color of complete layer.'); ?> </div>
                    <button id="tool_add_art_close"></button>
                    <div class="caption-section">
                      <div class="caption">
                        <?php echo Mage::helper('design')->__('Add to Cart');?>
                      </div>
                    </div>
                    <div class="search">
                      <div class="message" id="addtocartmessage"></div>
                      <!-- Added for custom options start -->
                      <div id="customOptionContainer"></div>
                      <!-- Added for custom options start -->
                      <!-- Commented for custom options start -->
                      <!--<form name="customOptionForm" id="customOptionForm" action="" method="POST">
            <div id="customOptioonConatiner">
              <div class="optionRow">
                <label id="qtyCaption" class="captionSmall">Quantity</label>
                <div class="qtyPriceArea">
                  <div id="qtyBox">
                    <input type="text" value="<?php //echo $productQty; ?>" id="quantityBox" onKeyDown="numeric_validation(event);" onchange="livePrice();" class="inputBoxMedium" autocomplete="off">
                  </div>
                </div>
              </div>
            </div>
          </form>-->
                      <!-- Commented for custom options end -->
                      <div class="caption-section">
                        <div class="caption" id="addNoteCaption">Add Note</div>
                      </div>
                      <div class="clear">
                        <textarea id="addnote" name="addnote"></textarea>
                      </div>



                      <div class="costing">
                        <div class="errornotinstock" id="errornotinstock"></div>
                        <!--<div class="rate" id="price">0.00</div>-->
                        <div class="buttons">
                          <button class="cobutton proceedto prcadcart" style="display:none;" type="button" id="addcart" onclick="addtocart();">Proceed</button>
                          <button class="cobutton proceedto prcadcart" style="display:none;" type="button" id="getquote">Get Quote</button>
                          <button class="cobutton proceedto prcadcart" style="display:none;" type="button" id="sendJob">Submit Request</button>
                        </div>
                        <div class="clear"></div>
                      </div>
                      <!-- <div class="proceedbtn">
            <button class="cobutton proceedto prcadcart" type="button" id="cartProceed" onclick="addtocart();">Proceed</button>
          </div>-->
                    </div>
                  </div>
                </nav>
                <nav class="cbp-spmenu cbp-spmenu-vertical cbp-spmenu-right" id="addart-panel">
                  <div class="box-outer">
                    <label class="label"><?php echo $this->__('Add Art');?></label>
                    <div class="inst" id="inst">i</div>
                    <div style="display:none;" id="design_tooltip" class="tooltip"> 1.
                      <?php echo $this->__('Ungroup does one level layering, so to do further layering, ungroup again until you can select the element you want to change color for. ');?><br>
                      <br> 2.
                      <?php echo $this->__('Select any layer to change color of complete layer.'); ?> </div>
                    <button id="tool_add_art_close"></button>
                    <div class="input-area">
                      <!--adding shape glyout-->
                      <div id="tools_left" class="tools_panel">
                        <div class="tool_button" id="tool_fhpath" title="Pencil Tool"></div>
                        <div class="tool_button" id="tool_line" title="Line Tool"></div>
                        <div class="tool_button flyout_current" id="tools_rect_show" title="Square/Rect Tool">
                          <div class="flyout_arrow_horiz"></div>
                        </div>
                        <div class="tool_button flyout_current" id="tools_ellipse_show" title="Ellipse/Circle Tool">
                          <div class="flyout_arrow_horiz"></div>
                        </div>
                        <div style="display: none; position:relative; border:1px solid">
                          <div id="tool_rect" title="Rectangle"></div>
                          <div id="tool_square" title="Square"></div>
                          <div id="tool_fhrect" title="Free-Hand Rectangle"></div>
                          <div id="tool_ellipse" title="Ellipse"></div>
                          <div id="tool_circle" title="Circle"></div>
                          <div id="tool_fhellipse" title="Free-Hand Ellipse"></div>
                        </div>
                      </div>
                      <!--adding clipart panel-->
                      <div class="field-raw">
                        <select name="clipart" id="clipartopt" class="select-main">
            </select>
                        <input type="hidden" id="firstclipartcat" name="firstclipartcat" value="<?php echo $firstclipartcatid; ?> " />
                      </div>
                      <div class="scroll-area-chooseart">
                        <div class="add-art-product">
                          <ul id="clipartcontainer" class="art-list">
                          </ul>
                        </div>
                        <div id="container_panel">
                          <div class="tool_sep"></div>
                          <label id="group_title" title="Group identification label" style="display:none;"> <span><?php echo $this->__('label');?>:</span>
                <input id="g_title" data-attr="title" size="10" type="text"/>
              </label>
                        </div>
                      </div>
                    </div>
                  </div>
                </nav>
                <nav class="cbp-spmenu cbp-spmenu-vertical cbp-spmenu-right" id="addLayout-panel">
                  <div class="box-outer">
                    <label class="label"><?php echo $this->__('Add Layout');?></label>
                    <div class="input-area">
                      <div class="scroll-area-chooseart">
                        <div class="add-art-product">
                          <ul id="layoutcontainer" class="art-list">
                          </ul>
                        </div>
                      </div>
                    </div>
                  </div>
                </nav>
                <nav class="cbp-spmenu cbp-spmenu-vertical cbp-spmenu-right" id="addtext-panel">
                  <div class="box-outer">
                    <label class="label"><?php echo $this->__('Place your text'); ?></label>
                    <div class="inst" id="inst_text">i</div>
                    <div style="display:none;" id="inst_text_tooltip" class="tooltip"> 1.
                      <?php echo $this->__('Select the text element and edit it further.'); ?> </div>
                    <button id="tool_place_text_close"></button>
                    <div id="text_panel">
                      <div>
                        <!--<input id="text" type="text" size="25"/>-->
                        <textarea id="text"></textarea>
                        <button id="btnAddText" title="Add Text">Add Text</button>
                      </div>
                    </div>
                  </div>
                </nav>
                <nav class="cbp-spmenu cbp-spmenu-vertical cbp-spmenu-right" id="addtimage-panel">
                  <div class="box-outer">
                    <label class="label"><?php echo Mage::helper('design')->__('ADD IMAGE'); ?></label>

                    <div style="display:none;" id="inst_image_tooltip" class="tooltip"> 1.
                      <?php echo Mage::helper('design')->__('Upload images from your local storage.');?><br>
                      <br> 2.
                      <?php echo Mage::helper('design')->__('Choose and click the image you want to use in design.');?><br>
                      <br> 3.
                      <?php echo Mage::helper('design')->__('You can resize and rotate the image further using editing controls.'); ?> </div>
                    <button id="tool_add_image_close"></button>
                    <div class="upload-permission" align="center">
                      <input type="checkbox" id="ihavetheright">
                      <label id="rightsCaption">I have the rights to use these images.</label>
                      <div class="inst_image" id="inst_image">i</div>
                    </div>
                    <div class="buttons">
                      <a class="uploadimage-btn-gallery" id="upload_img_show_gallery">Gallery</a>
                      <div class="social-media-import-section">
                        <div class="uploadimage-btn">
                          <button id="upload_img_show" type="button" class="disable_img">
				<div id="uploadImageCaption"></div>
              </button>
                        </div>
                        <!--<a class="import_fb">Facebook</a>--><a class="import_flickr">Flickr</a> <a class="import_picasa">Picasa</a> <a class="import_instagram">Instagram</a>                        <a id="qrCode" class="add_Qrcode">QRCode</a> </div>
                      <?php /*?>
                      <div align="center" class="uploadimage-btn-gallery">
                        <button id="upload_img_show_gallery" type="button" class="disable_img"><div id="imageGalleryCaption"><?php echo Mage::helper('design')->__('Images Gallery'); ?></div></button>
                      </div>
                      <?php */?>
                      <div id="images_loaded" class="range-blocks" style="display:none;">
                        <div class="range-blocks">
                          <div class="caption-section">
                            <div class="caption" id="imageGallerCaption">Image Gallery</div>
                            <div class="inst_image" id="inst_image_hd">i</div>
                          </div>
                        </div>
                        <div class="listing scroll-area-uploadimage">
                          <div class="tabcntent">
                            <ul id="flickerresult">
                            </ul>
                          </div>
                        </div>
                        <!--<div class="horLine"></div>-->
                      </div>

                    </div>
                    <div id="image_panel">
                      <div class="toolset_image" style="display:none;">
                        <label><span id="iwidthLabel" class="icon_label"></span>
              <input id="image_width" class="attr_changer" title="Change image width" size="3" data-attr="width"/>
            </label>
                        <label><span id="iheightLabel" class="icon_label"></span>
              <input id="image_height" class="attr_changer" title="Change image height" size="3" data-attr="height"/>
            </label>
                      </div>
                    </div>
                  </div>
                </nav>
                <nav class="cbp-spmenu cbp-spmenu-vertical cbp-spmenu-right" id="addshape-panel">
                  <div class="box-outer">
                    <label class="label"><?php echo $this->__('ADD SHAPES');?></label>
                    <div class="inst" id="inst_shape">i</div>
                    <div style="display:none;" id="inst_shape_tooltip" class="tooltip"> 1.
                      <?php echo $this->__('Click any control to get more options.');?><br>
                      <br> 2.
                      <?php  echo $this->__('Please click the select pointer in left panel to exit shape mode.');?>
                    </div>
                    <button id="tool_add_shape_close"></button>
                    <div class="note-background">
                      <!--<div id="tools_left" class="tools_panel">
            <div class="tool_button" id="tool_fhpath" title="Pencil Tool"></div>
            <div class="tool_button" id="tool_line" title="Line Tool"></div>
            <div class="tool_button flyout_current" id="tools_rect_show" title="Square/Rect Tool">
              <div class="flyout_arrow_horiz"></div>
            </div>
            <div class="tool_button flyout_current" id="tools_ellipse_show" title="Ellipse/Circle Tool">
              <div class="flyout_arrow_horiz"></div>
            </div>
            <div style="display: none; position:relative; border:1px solid">
              <div id="tool_rect" title="Rectangle"></div>
              <div id="tool_square" title="Square"></div>
              <div id="tool_fhrect" title="Free-Hand Rectangle"></div>
              <div id="tool_ellipse" title="Ellipse"></div>
              <div id="tool_circle" title="Circle"></div>
              <div id="tool_fhellipse" title="Free-Hand Ellipse"></div>
            </div>
          </div>-->
                    </div>
                    <br class="clear" />
                    <br class="clear" />
                  </div>
                </nav>
                <nav class="cbp-spmenu cbp-spmenu-vertical cbp-spmenu-right" id="qrcode-panel">
                  <div class="box-outer">
                    <label class="label"><?php echo $this->__('Qr Code'); ?></label>
                    <div class="inst" id="inst_qrcode">i</div>
                    <div style="display:none;" id="inst_qrcode_tooltip" class="tooltip">
                      <!--1. <?php //echo $this->__('Click any control to get more options.');?><br><br>2. <?php  //echo $this->__('Please click the select pointer in left panel to exit shape mode.');?>-->
                      <?php echo $this->__("To place QR Code on your design, choose the type of information you wish to input. Fill in the information and then click the 'Generate' button. To place it on canvas, simply click on the generated QR Code image.");?>
                      <button onclick="$j('#inst_qrcode_tooltip').hide();" class="closebuttonclass">x</button>
                    </div>
                    <div class="scroll-area">
                      <div class="qrcode-section">
                        <div class="caption-section">
                          <form id="qrcodeform" name="qrcodeform" method="POST">
                            <ul id="qrcodecontainer" class="t-shirt-list">
                              <label id="qrcodeDataTypeCaption" class="caption">Select Data Type</label>
                              <select id="qrdatatype" name="qrdatatype" onchange="changeqrform()">
                    <option id="websiteUrl" selected="selected" value="1"><?php echo $this->__('Website URL'); ?></option>
                    <option id="youtubeVideo" value="2"><?php echo $this->__('YouTube Video'); ?></option>
                    <option id="plaintext" value="3"><?php echo $this->__('Plain Text'); ?></option>
                    <option id="emailAdd" value="4"><?php echo $this->__('Email Address'); ?></option>
                    <!--<option value="5"><?php echo $this->__('Contatct Details (Vcard)'); ?></option>-->
                    <option id="telephone" value="6"><?php echo $this->__('Telephone Number'); ?></option>
                    <option id="emailMsg" value="7"><?php echo $this->__('Email Message'); ?></option>
                    <option id="socialMedia" value="8"><?php echo $this->__('Social Media'); ?></option>
                    <option id="gmap" value="9"><?php echo $this->__('Google Maps Location'); ?></option>
                  </select>
                              <div id="qr1">
                                <div class="inputfield">
                                  <label id="websiteCaption" class="caption"><?php echo $this->__('Website URL:'); ?></label>
                                  <input class="required-entry validate-url" type="text" name="websiteurl" id="websiteurl" />
                                </div>
                              </div>
                              <div id="qr2">
                                <div class="inputfield">
                                  <label id="videoIdCaption" class="caption"><?php echo $this->__('Video ID:'); ?></label>
                                  <input class="validate-group  validate-number" type="text" name="youtube_video_id" id="youtube_video_id" />
                                </div>
                                <div class="inputfield">
                                  <label id="videoUrlCaption" class="caption"><?php echo $this->__('Video URL:'); ?></label>
                                  <input class="validate-group validate-url" type="text" name="youtube_video_url" id="youtube_video_url" />
                                </div>
                              </div>
                              <div id="qr3">
                                <div class="inputfield">
                                  <label id="textCaption" class="caption"><?php echo $this->__('TEXT:'); ?></label>
                                  <textarea class="required-entry" type="text" name="plaintextdata" id="plaintextdata"></textarea>
                                </div>
                              </div>
                              <div id="qr4">
                                <div class="inputfield">
                                  <label id="emailaddreCaption" class="caption"><?php echo $this->__('Email Address:'); ?></label>
                                  <input class="required-entry validate-email" type="text" name="email_address" id="emailaddress4" />
                                </div>
                              </div>
                              <!--<div id="qr5">
									<div class="inputfield">
										<label><?php echo $this->__('First Name:'); ?></label>
										<input class="required-entry" type="text" name="first_name" id="firstname5"/>
									</div>
									<div class="inputfield">
										<label><?php echo $this->__('Last Name:'); ?></label>
										<input class="required-entry" type="text" name="last_name" id="lasttname5"/>
									</div>
									<div class="inputfield">
										<label><?php echo $this->__('Job Title:'); ?></label>
										<input  type="text" name="job_title" id="jobtitle5"/>
									</div>
									<div class="inputfield">
										<label><?php echo $this->__('Telephone no:'); ?></label>
										<input class="validate-phoneStrict" type="text" name="telephone_no" id="telephoneno5"/>
									</div>
									<div class="inputfield">
										<label><?php echo $this->__('Cell phone:'); ?></label>
										<input class="validate-phoneStrict" type="text" name="job_title" id="jobtitle5"/>
									</div>
									<div class="inputfield">
										<label><?php echo $this->__('Fax Number:'); ?></label>
										<input class="validate-fax" type="text" name="fax_number" id="faxnumber5"/>
									</div>
									<div class="inputfield">
										<label><?php echo $this->__('Email Address:'); ?></label>
										<input class="validate-email" type="text" name="email_address" id="emailaddress5"/>
									</div>
									<div class="inputfield">
										<label><?php echo $this->__('Website URL:'); ?></label>
										<input class="validate-clean-url" type="text" name="website_url" id="vcardwebsiteurl5"/>
									</div>
									<div class="inputfield">
										<label><?php echo $this->__('Organization:'); ?></label>
										<input type="text" name="organization" id="organization5"/>
									</div>
									<div class="inputfield">
										<label><?php echo $this->__('Street Address:'); ?></label>
										<input type="text" name="street" id="street5"/>
									</div>	
									<div class="inputfield">
										<label><?php echo $this->__('City:'); ?></label>
										<input type="text" name="city" id="city5"/>
									</div>
									<div class="inputfield">
										<label><?php echo $this->__('State:'); ?></label>
										<input type="text" name="State" id="State5"/>
									</div>
									<div class="inputfield">
										<label><?php echo $this->__('Zip/Postcode:'); ?></label>
										<input class="validate-zip" type="text" name="zip" id="zip5"/>
									</div>
									<div class="inputfield">
										<label><?php echo $this->__('Country:'); ?></label>
										<input type="text" name="country" id="country5"/>
									</div>									
								 </div>-->
                              <div id="qr6">
                                <div class="inputfield">
                                  <label id="telephoneCaption" class="caption"><?php echo $this->__('Telephone Number:'); ?></label>
                                  <input class="required-entry validate-phoneStrict" type="text" name="telephone_no" id="telephoneno6" />
                                </div>
                              </div>
                              <div id="qr7">
                                <div class="inputfield">
                                  <label id="subjectCaption" class="caption"><?php echo $this->__('Subject:'); ?></label>
                                  <input class="required-entry" type="text" name="subject" id="subject7" />
                                </div>
                                <div class="inputfield">
                                  <label id="bodyCaption" class="caption"><?php echo $this->__('Body:'); ?></label>
                                  <textarea class="required-entry" name="message" type="text" id="body7"></textarea>
                                </div>
                              </div>
                              <div id="qr8">
                                <div class="inputfield">
                                  <label id="socialMediaCaption" class="caption"><?php echo $this->__('Social Media:'); ?></label>
                                  <select name="socialmedia8" id="socialmedia8" onchange="changetheprofilename()">
                        <option id="twitterProfileCaption" value="sm1"><?php echo $this->__('Twitter Profile'); ?></option>
                        <option id="facebookProfileCaption" value="sm2"><?php echo $this->__('Facebook Profile'); ?></option>
                        <option id="myspaceProfileCaption" value="sm3"><?php echo $this->__('Myspace Profile'); ?></option>
                        <option id="linkedlinProfileCaption" value="sm4"><?php echo $this->__('LinkedIn Profile'); ?></option>
                      </select>
                                </div>
                                <div class="inputfield">
                                  <label id="profileCaption" class="caption"><?php echo $this->__('Profile:'); ?></label>
                                  <input class="required-entry" type="text" name="Twitter Profile" id="profile8" />
                                </div>
                              </div>
                              <div id="qr9">
                                <div class="inputfield">
                                  <label id="gmaplocationCaption" class="caption"><?php echo $this->__('Google Maps Location:'); ?></label>
                                  <input type="text" class="required-entry" name="google_map_location" id="googlemaplocation9" />
                                </div>
                              </div>
                            </ul>
                          </form>
                          <div class="inputfield">
                            <label id="rqcodeColorCaption" class="caption"><?php echo $this->__('Color Code:'); ?></label>
                            <input type="text" name="qrcolorcode" id="qrcolorcode" class="color" value="#000000">
                            <div class="color_tool" id="tool_qr_color">
                              <div class="color_block">
                                <div id="qr_stroke_bg"></div>
                                <div id="qr_color" class="color_block" title="Change Qr color"></div>
                              </div>
                            </div>
                          </div>
                        </div>
                        <button id="tool_qrCode" onclick="qrcodeFormSubmit();" type="button" class="generalbutton">Generate</button>
                        <div>
                          <ul id="QRcodeImage">


                            <!--data will come from ajax-->
                          </ul>
                        </div>
                      </div>
                    </div>
                    <!--<div class="tips"> 
						<h4>Designer Tips!</h4>
						To place QR code on your design just choose the type of information you wisht to input and fill the information and then click on generate button.to place it on canvas to left just  click on generated qrcode image.
					</div>-->
                  </div>
                </nav>

                <!-- common property panel -->
                <nav class="cbp-spmenu-vertical cbp-spmenu-right" id="edit-panel">
                  <div class="box-outer">
                    <label class="label"><?php echo $this->__('Edit Panel');?></label>
                    <div class="inst" id="inst_edit_panel">i</div>
                    <div style="display:none;" id="design_tooltip_edit_panel" class="tooltip"> 1.
                      <?php echo $this->__('Ungroup does one level layering, so to do further layering, ungroup again until you can select the element you want to change color for. ');?><br>
                      <br> 2.
                      <?php echo $this->__('Select any layer to change color of complete layer.'); ?> </div>
                    <button id="tool_edit_close"></button>

                    <!--<div id="images_loaded"  class="range-blocks listing scroll-area-uploadimage">
					<div class="range-blocks">
						<div class="caption-section">
							<div class="caption" id="borderCaption">Image Gallery</div>
						</div>
					</div>
					<div class="listing scroll-area-uploadimage"
						><div class="tabcntent">
							<ul id="flickerresult"></ul>
						</div>
					</div>
				</div>-->

                  </div>
                </nav>
                <!-- /common property panel -->
                <!-- common property -->

                <nav class="ranger-panel cbp-spmenu-right" id="common-panel">
                  <div class="box-outer">
                    <div class="range-blocks" style="overflow:hidden">
                      <div class="co-tw-dl">
                        <ul>
                          <li id="tool_fill"> <span id="objectColorCaption" class="label">color</span>
                            <div class="color_tool">
                              <div class="color_block singleColor">
                                <div id="fill_bg"></div>
                                <div id="fill_color" class="color_block" title="Change fill color"></div>
                              </div>
                              <div class="multiColorBlock"></div>
                            </div>
                          </li>
                          <!--<div id="color_picker"></div>-->
                          <!-- <li>
                                <span id="twinCaption" class="label">twin</span>                                
                                <div class="push_button" id="tool_clone" title="Twin"></div>
                            </li>
                            <li>
                                <span id="deleteCaption" class="label">delete</span>                                
                                <div class="color_tool" id="tool_opacity_delete" title="Delete">
                                    <button title="Delete Element" id="tool_delete"><div><span class="icon-10">&nbsp;</span></div></button>
                                </div>

                            </li>-->
                        </ul>
                      </div>
                    </div>
                    <div class="range-blocks image_quality">
                      <div class="caption-section">
                        <div id="inst_image_format" class="inst_image">i</div>
                        <div id="printQualityCaption" class="caption">Print Quality</div>
                      </div>
                      <!--<div class="caption-section image_quality_tabs">
        <div id="inst_image_format" class="inst_image">i</div>
		<div id="printQualityCaption" class="caption-tabs active">Print Quality</div>
		<div id="imageEffectCaption" class="caption-tabs">Image Effect</div>
      </div>-->
                      <div class="upload-mathod">
                        <ul class="active">
                          <li id="printQualityPoorCaption" class="not-reco active"><i></i> Poor</li>
                          <li id="printQualityFairCaption" class="fair"><i></i> Fair</li>
                          <li id="printQualityGoodCaption" class="luk-gud"><i></i> Good</li>
                        </ul>
                      </div>
                    </div>
                    <div class="range-blocks image_effect">
                      <div class="caption-section">
                        <div id="imageEffectCaption" class="caption">Image Effect</div>
                      </div>
                    </div>
                    <div id="fontsizeDiv" class="range-blocks no-margin">
                      <div class="caption-section">
                        <div id="fontSizeCaption" class="caption">FONT SIZE</div>
                      </div>
                      <div class="font-size">
                        <select id="font_size_dropdown"> </select>
                      </div>
                      <div class="vertical-align-text right">
                        <div class="left">
                          <div class="tool_button" id="text_align_top" title="Align top"></div>
                        </div>
                        <div class="left">
                          <div class="tool_button" id="text_align_middle" title="Align middle"></div>
                        </div>
                        <div class="left">
                          <div class="tool_button" id="text_align_bottom" title="Align bottom"></div>
                        </div>
                      </div>
                    </div>
                    <div id="sizeDiv" class="range-blocks no-margin">
                      <div class="caption-section">
                        <div id="objectSizeCaption" class="caption">POSITION / SIZE</div>
                        <!--<input id="scaleValue" class="sliderValue" size="3" value="100" type="text" disabled="disabled"/>-->
                        <!--<div class="caption-icon">
                            <div class="default-icon">&nbsp;</div>
                            <div class="opacity-icon">&nbsp;</div>
                        </div>-->
                      </div>
                      <div id="sizeSlider"></div>
                    </div>
                    <div id="sizeDivPc" style="display:none;" class="range-blocks">
                      <div class="caption-section">
                        <div class="caption">SIZE</div>
                        <!-- <input id="scaleValuePc" class="sliderValue" size="3" value="100" type="text" disabled="disabled"/>-->
                      </div>
                      <div class="ranger-area">
                        <div id="sizeSliderPc"></div>
                      </div>

                    </div>
                    <div class="range-blocks" id="border_box">
                      <div class="caption-section">
                        <div id="objectborderCaption" class="caption">BORDER</div>
                        <input id="stroke_width" class="slider_itag" size="2" value="5" type="text" data-attr="Stroke Width" disabled="disabled"
                        />
                        <div class="psright">
                          <div class="left">
                            <label class="stroke_tool no-padding">
				<select id="stroke_style" title="Change stroke dash style">
				  <option selected="selected" value="none">&mdash;</option>
				  <option value="2,2">...</option>
				  <option value="5,5">- -</option>
				  <option value="5,2,2,2">- .</option>
				  <option value="5,2,2,2,2,2">- ..</option>
				</select>
			  </label>
                          </div>
                          <div class="color_tool" id="tool_stroke">
                            <!--<label class="icon_label no-padding" title="Change stroke color" ></label>-->
                            <div class="color_block">
                              <div id="stroke_bg"></div>
                              <div id="stroke_color" class="color_block" title="Change stroke color"></div>
                            </div>
                            <div id="toggle_stroke_tools" title="Show/hide more stroke tools" style="display:none;"></div>
                          </div>
                        </div>
                      </div>
                      <div class="ranger-area">
                        <div id="stroke_slider"></div>
                      </div>
                    </div>
                    <div id="rotationDiv" class="range-blocks">
                      <div class="caption-section">
                        <div id="objectRotateCaption" class="caption">ROTATE</div>
                        <input id="rotateAngle" class="sliderValue" size="3" value="100" type="text" disabled="disabled" />
                      </div>
                      <div class="ranger-area">
                        <div id="rotationSlider"></div>
                      </div>
                    </div>
                    <div id="rotationDivPc" style="display:none;" class="range-blocks">
                      <div class="caption-section">
                        <div class="caption">ROTATE</div>
                        <input id="rotateAnglePc" class="sliderValue" size="3" value="100" type="text" disabled="disabled" />
                      </div>
                      <div class="ranger-area">
                        <div id="rotationSliderPc"></div>
                      </div>
                    </div>
                    <!--<div class="range-blocks" style="display:none">
                    <div class="caption-section">
                        <div id="opacityCaption" class="caption">OPACITY</div>	
						<input id="group_opacity" class="slider_itag" size="3" value="100" type="text" disabled="disabled"/>
                        <div class="caption-icon">
                            <div class="default-icon">&nbsp;</div>
                            <div class="opacity-icon">&nbsp;</div>
                        </div>                        
                    </div>					
                    <div class="border-box opacity-ranger">
                    		<div class="toolset_global">
								<div class="size-global-area">
                                <div id="opacity_dropdown" class="dropdown">
                                    <div id="opac_slider"></div>
                                </div>
                            </div>
                            </div>
                    </div>
                </div>-->
                    <!--<div class="range-blocks" id="tool_blur"  style="display:none">
                    <div class="caption-section">
                        <div id="blurCaption" class="caption">BLUR</div>						
						<input id="blur" class="slider_itag" size="2" value="0" type="text" disabled="disabled"/>
                        <div class="caption-icon">
                            <div class="default-icon">&nbsp;</div>
                            <div class="blur-icon">&nbsp;</div>
                        </div>                        
                    </div>
                    <div class="border-box opacity-ranger">
                    <div class="toolset_global">
                        <div class="size-global-area">
                        <div id="blur_slider"></div>
                      </div>
                      </div>
                  </div>
                </div>-->
                    <div class="range-blocks" id="tool_curve">
                      <div class="caption-section">
                        <div id="objectCurveCaption" class="caption">CURVE</div>
                        <input id="curveAngle" class="sliderValue" size="2" value="0" type="text" disabled="disabled" />
                        <div id="objectCurveReverseCaption" class="caption">REVERSE</div>
                        <input id="curveInvert" class="" size="2" value="0" type="checkbox" />
                        <!--<div class="caption-icon">
                            <div class="default-icon">&nbsp;</div>
                            <div class="blur-icon">&nbsp;</div>
                        </div>-->
                      </div>
                      <div class="ranger-area">
                        <div id="curve_slider"></div>
                      </div>
                    </div>
                    <div id="textShapeDiv" class="range-blocks" style="overflow:none;">
                      <div class="caption-section">
                        <div class="caption" id="objectTextShapeCaption">TEXT SHAPES</div>
                        <input type="text" disabled="disabled" value="100" size="3" class="sliderValue" id="shapeValue" autocomplete="off">
                        <div class="curve-division">
                          <div class="curve-property"></div>
                          <div id="textShapeDD" class="wrapper-dropdown-2" style="clear: left;position: relative;width: 105px;">
                            <span id="textShapeSelected"><img src="<?php echo $jspath.'images/shape-none.jpg'; ?> " /></span>
                          </div>
                        </div>
                      </div>
                      <div class="ranger-area">
                        <div id="textShapeSlider"></div>
                      </div>
                    </div>
                    <div id="lineSpaceDiv" class="range-blocks">
                      <div class="caption-section">
                        <div id="objectLineSpaceCaptioon" class="caption">Line Spacing</div>
                        <input id="lineHeight" class="sliderValue" size="3" value="100" type="text" disabled="disabled" />
                      </div>
                      <div class="ranger-area">
                        <div id="lineSpaceSlider"></div>
                      </div>
                    </div>
                    <div class="range-blocks" id="tool_Move">
                      <div class="caption-section">
                        <div id="objectMoveCaption" class="caption">Move</div>
                      </div>
                      <div class="border-box opacity-ranger">
                        <div class="size-global-area">
                          <div class="joystic-button">
                            <div class="toward-up">
                              <button id="upMove"><i class="fa fa-caret-up"></i></button>
                            </div>
                            <div class="toward-down">
                              <button id="downMove"><i class="fa fa-caret-down"></i></button>
                            </div>

                            <div class="toward-left">
                              <button id="leftMove"><i class="fa fa-caret-left"></i></button>
                            </div>
                            <div class="toward-right">
                              <button id="rightMove"><i class="fa fa-caret-right"></i></button>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div id="VDPProp" class="range-blocks" style="display:none;">
                      <div class="caption-section">
                        <div class="caption">VDP</div>
                      </div>
                      <div class="content">
                        <div class="status">status:</div>
                      </div>
                    </div>
                  </div>
            </div>
            </nav>
            <div id="editable-color-block" class="ranger-panel cbp-spmenu-right ui-draggable">
              <h2>Edit Printable Colors</h2>
              </br>
              <p>Our color recommendation have been pre-selected to help you get started.</p>
              </br>
              <h3 style="text-align: center;">Select Image Color(s)</h3>
              <div id="printableColorList"></div>
              <div style="text-align: center; margin-top: 15px;"><button id="confirm_color" class="button">Confirm Colors</button></div>
            </div>
            <!-- /common property -->
            <div class="align_icons" style="display:none;">
              <ul class="optcols3">
                <li class="push_button" id="tool_posleft" title="Align Left"></li>
                <li class="push_button" id="tool_poscenter" title="Align Center"></li>
                <li class="push_button" id="tool_posright" title="Align Right"></li>
                <li class="push_button" id="tool_postop" title="Align Top"></li>
                <li class="push_button" id="tool_posmiddle" title="Align Middle"></li>
                <li class="push_button" id="tool_posbottom" title="Align Bottom"></li>
              </ul>
              <div id="tool_align_relative" style="margin: 0px 2px 0px 5px;"> <span id="relativeToLabel">relative to:</span>
                <select title="Align relative to ..." id="align_relative_to" autocomplete="off">
          <option value="selected" id="selected_objects">selected objects</option>
          <option value="largest" id="largest_object">largest object</option>
          <option value="smallest" id="smallest_object">smallest object</option>
          <option value="page" id="page">page</option>
          <option value="page_cutMargin" id="page_cutMargin">Cut Margin</option>
          <option value="page_safeMargin" id="page_safeMargin">Safe Margin</option>
        </select>
              </div>
            </div>
            <div class="border-box" id="text_size_box" style="display:none">
              <div class="size-bold-italic">
                <div class="left">
                  <div class="tool_button" id="tool_bold" title="Bold Text"></div>
                </div>
                <div class="right">
                  <div class="tool_button" id="tool_italic" title="Italic Text"></div>
                </div>
              </div>
              <div class="text-align">
                <div class="left">
                  <div class="tool_button" id="text_align_left" title="Align Left"></div>
                </div>
                <div class="left">
                  <div class="tool_button" id="text_align_center" title="Align Center"></div>
                </div>
                <div class="right">
                  <div class="tool_button" id="text_align_right" title="Align Right"></div>
                </div>
              </div>
            </div>
            <div class="toolset_fontfamily" id="tool_font_family" style="display:none">
              <!-- Font family -->
              <label id="chooseFontCaption">CHOOSE FONT</label>
              <div tabindex="1" class="wrapper-dropdown-1" id="dd"> <span id="selectedFont"><?php echo $this->__('CHOOSE');?> <strong><?php echo $this->__('FONT'); ?></strong></span>                </div>
            </div>
            <div class="priceTag">
              <div class="rate" id="price"></div>
              <div id="priceAjaxLoader" class="ajax-loader"><img src="<?php echo $jspath. 'images/ajax-loader.gif' ?>" /> </div>
              <button id="addtocart_btn" title="Add To Cart">
			<div><i class="fa fa-shopping-cart"></i></div>
		</button>
            </div>
            </section>
            <div class="clear"></div>
          </div>
          <div class="footer-corner">
            <div>
              <div>&nbsp;</div>
            </div>
          </div>
          <div id="productName">
            <?php echo $productName; ?>
          </div>
          <!--<div id="cartarea" class="quantitysize">
		<div class="border-area">
			<div class="object-inputs">
				<div id="priceAjaxLoader" class="ajax-loader"><img src="<?php echo $jspath.'images/ajax-loader.gif' ?>"/> </div>
				<label class="caption"><?php echo $this->__('Quantity');?>: </label><input type="text" class="inputBoxMedium" onkeyup="livePrice();" id="quantityBox" value="<?php echo $productQty; ?>" /></div>
		   <div class="costing">
			<div class="errornotinstock" id="errornotinstock"></div>
			  <div class="rate" id="price">0.00</div>
			  <div class="buttons"><button id="addcart"><div><?php echo $this->__('ADD TO CART');?></div></button></div>
			   <div class="clear"></div>
		   </div><div class="clear"></div>
		</div>
	</div>-->
          <div id="use_panel" style="display:none">
            <div class="push_button" id="tool_unlink_use" title="Break link to reference element (make unique)"></div>
          </div>
          <!--<div id="svg_prefs">
		<div id="svg_prefs_overlay"></div>
		<div id="svg_prefs_container">
			<div id="tool_prefs_back" class="toolbar_button">
				<button id="tool_prefs_save"><?php echo $this->__('OK'); ?></button>
				<button id="tool_prefs_cancel"><?php echo $this->__('Cancel'); ?></button>
			</div>
			<fieldset>
				<legend id="svginfo_editor_prefs"><?php echo $this->__('Editor Preferences'); ?></legend>
				<fieldset id="change_background">
					<legend id="svginfo_change_background"><?php echo $this->__('Editor Background'); ?></legend>
					<div id="bg_blocks"></div>
					<label><span id="svginfo_bg_url"><?php echo $this->__('URL');?>:</span> <input type="text" id="canvas_bg_url"/></label>
					<p id="svginfo_bg_note"><?php echo $this->__('Note: Background will not be saved with image.');?></p>
				</fieldset>
				<fieldset id="change_grid">
					<legend id="svginfo_grid_settings"><?php echo $this->__('Grid'); ?></legend>
					<label><span id="svginfo_snap_onoff">Snapping on/off</span><input type="checkbox" value="snapping_on" id="grid_snapping_on"></label>
					<label><span id="svginfo_snap_step">Snapping Step-Size:</span> <input type="text" id="grid_snapping_step" size="3" value="10"/></label>
				</fieldset>
				<fieldset id="units_rulers">
					<legend id="svginfo_units_rulers">Units & Rulers</legend>
					<label><span id="svginfo_rulers_onoff">Show rulers</span><input type="checkbox" value="show_rulers" id="show_rulers"></label>
					<label>
						<span id="svginfo_unit">Base Unit:</span>
						<select id="base_unit">
							<option value="px">Pixels</option><option value="cm">Centimeters</option><option value="mm">Millimeters</option>
							<option value="in">Inches</option><option value="pt">Points</option><option value="pc">Picas</option>
							<option value="em">Ems</option><option value="ex">Exs</option>
						</select>
					</label>
				</fieldset>
			</fieldset>
		</div>
	</div>-->
          <div id="dialog_box">
            <div class="global_overlay"></div>
            <div id="dialog_container" class="global_popup_box" style="width: 380px;height: 175px;">
              <div class="save-icon"><i class="fa fa-hand-paper-o"></i></div>
              <div id="dialog_content" align="center"></div>
              <div id="dialog_buttons" align="center"></div>
            </div>
          </div>
          <!--<ul id="cmenu_canvas" class="contextMenu">
		<li><a href="#cut"><?php echo $this->__('Cut');?></a></li>
		<li><a href="#copy"><?php echo $this->__('Copy'); ?></a></li>
		<li><a href="#paste"><?php echo $this->__('Paste');?></a></li>
		<li><a href="#paste_in_place"><?php echo $this->__('Paste in Place');?></a></li>
		<li class="separator"><a href="#delete"><?php echo $this->__('Delete');?></a></li>
		<li class="separator"><a href="#group"><?php echo $this->__('Group');?></a></li>
		<li><a href="#ungroup"><?php echo $this->__('Ungroup');?></a></li>
		<li class="separator"><a href="#move_front"><?php echo $this->__('Bring to Front');?></a></li>
		<li><a href="#move_up"><?php echo $this->__('Bring Forward');?></a></li>
		<li><a href="#move_down"><?php echo $this->__('Send Backward');?></a></li>
		<li><a href="#move_back"><?php echo $this->__('Send to Back');?></a></li>
	</ul>-->
          <div id="svg_docprops" style="display:none;">
            <div class="global_overlay">&nbsp;</div>
            <div id="svg_docprops_container" class="global_popup_box" style="width:500px;height:350px;">
              <div id="tool_docprops_back">
                <button id="tool_docprops_cancel" class="close-window-positoin"></button>
              </div>
              <div class="save-icon">
                <i class="fa fa-info"></i>
              </div>
              <p id="detailInfoCaption" class="new-heading pop-heading-line">Detail Information</p>
              <div class="proimage">
                <div align="center" style="margin:auto; overflow:hidden; width:260px;">
                  <ul class="colorpalet">
                    <?php echo $pickcolor_multi; ?>
                  </ul>
                </div>
              </div>
              <div class="prodescription">
                <p class="cotxt"><strong id="productNameCaption">Name :</strong> <span><?php echo $productName ?></span></p>
                <p class="cotxt"><strong id="productShortDesc">Short Description :</strong><span><?php echo $productShortDescription ?></span></p>
                <p class="cotxt"><strong id="productDesc">Long Description :</strong><span><?php echo $productDescription ?></span></p>
              </div>
            </div>
          </div>
          <div id="svg_image_upload" style="display:none;">
            <div class="global_overlay"></div>
            <div class="svg_image_upload_container global_popup_box" style="width:520px; height:430px;">
              <div id="tool_image_upload_back" class="toolbar_button">
                <button onclick="closeMe(this);" class="close-window-positoin"></button>
              </div>
              <p id="addImagePopupcaption" class="new-heading pop-heading-line">ADD IMAGE</p>
              <div id="uploader">
                <div id="filelist" class="panel">No runtime found.</div>
                <p>Your browser doesn't have Flash, Silverlight or HTML5 support.</p>
              </div>
            </div>
          </div>
          <!--<div id="svg_add_note">
		<div id="svg_add_note_overlay"></div>
		<div id="svg_add_note_container" class="">
			<div id="tool_addnote_back" class="toolbar_button"><button id="tool_addnote_cancel"></button></div>
			<p class="headingtwo"><?php //echo $this->__('Add Note');?></p>
			<fieldset>
				<textarea name="addnote" id="addnote"></textarea>
				<input type="hidden" name="addnotehidden" id="addnotehidden"/>
				<button type="button" class="cobutton addnotebutton right" onclick="addnote()"><span><?php echo $this->__('Add Note'); ?></span></button>
				<span id="addnoteresults"></span>
			</fieldset>
		</div>
	</div>-->

          <!--<div id="svg_beforeaddtocart">
		<div id="svg_beforeaddtocart_overlay"></div>
		<div id="svg_beforeaddtocart_container" class="">
			<div id="tool_beforeaddtocart_back" class="toolbar_button"><button id="tool_beforeaddtocart_cancel"></button></div>
            <p id="addToCartCaption" class="headingtwo"><?php //echo $this->__('ADD TO CART');?></p>
			<div class="search">
				<div class="message" id="addtocartmessage"></div>
				<form name="customOptionForm" id="customOptionForm" action="" method="POST">
					<div id="customOptioonConatiner"></div>
				</form>
				<div class="clear">
					<p id="addNoteCaption" class="add_Note">Add Note</p>
					<textarea name="addnote" id="addnote"></textarea>
				</div>
				<div class="proceedbtn">					
					<button class="cobutton proceedto prcadcart" type="button" id="cartProceed" onclick="$j('#svg_beforeaddtocart').hide();addtocart();">Proceed</button>
				</div>
			</div>
        </div>
	</div>-->

          <div id="duplicate_side_window" style="display:none;">
            <div class="global_overlay"></div>
            <div class="global_popup_box" style="width:450px;height:260px;">
              <div class="toolbar_button">
                <button onclick="closeMe(this);" class="close-window-positoin"></button>
              </div>
              <p class="new-heading pop-heading-line" id="duplicateSideCaption">Duplicate Current Page To</p>
              <div>
                <div id="sideCloneOptions">
                  <!--content goes here-->
                </div>
              </div>
            </div>
          </div>
          <div id="imageEffect_window" style="display:none;">
            <div class="global_overlay"></div>
            <div class="global_popup_box" style="width:852px; max-height:660px;">
              <div class="toolbar_button">
                <button onClick="closeMe(this);" class="close-window-positoin"></button>
              </div>
              <p class="new-heading pop-heading-line" id="imageEffectPopupCaption">Image Effect</p>
              <div>
                <div>
                  <div id="ie_preview"></div>
                  <div class="filters-button">
                    <div class="tabCon"> <span id="tabShapes">Mask</span> <span id="tabFilters">Effects</span> <span id="tabColors">Custom Effects</span>                      </div>
                    <div class="clearer">&nbsp;</div>
                    <div>
                      <div id="ie_shapes" class="photo_filters_option"></div>
                      <div id="ie_filters" class="photo_filters_option" style="display:none;padding-top: 10px;padding-bottom: 10px;"></div>
                      <div id="ie_colorChanger" class="photo_filters_option" style="display:none;">
                        <div class="colorCon"> </div>
                      </div>
                    </div>
                  </div>
                  <div class="buttonHolder"> </div>
                </div>
              </div>
            </div>
          </div>

          <div id="pickDesignColor_window" style="display:none;">
            <div class="global_overlay" style="z-index:21;"></div>
            <div class="global_popup_box" style="width: 700px; height:556px; z-index:21;">
              <div class="toolbar_button">
                <button onClick="closeMe(this);" class="close-window-positoin"></button>
              </div>
              <p class="new-heading pop-heading-line" id="pickDesignColorCaption">Pick Color From Design</p>
              <div>
                <div style="text-align:center;">
                  <canvas id="pickDesignColorCanvas" width="500" height="400"></canvas>
                </div>
                <div class="pickDesignColorContainer">
                  <div><span id="colorUnderCursor">Color Under Cursor:</span><span class="colorSwatchMouseMove"></span></div>
                  <div><span id="selectedColor">Selected Color:</span><span class="colorSwatch"></span></div>
                  <div>
                    <input id="pickDesignColorButton" type="button" class="" value="Pick Color" />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div id="image_quality_window" style="display:none;">
            <div class="global_overlay"></div>
            <div class="global_popup_box" style="width:520px; height:255px;">
              <div class="toolbar_button">
                <button onclick="closeMe(this);" class="close-window-positoin"></button>
              </div>
              <div class="save-icon">
                <i class="fa fa-image"></i>
              </div>
              <p class="new-heading pop-heading-line" id="imageQualityInfoCaption">About Your Image Print Quality</p>
              <div class="window_content">
                <div id="imageQualityInfoContent">
                  <!--content goes here-->
                  The image quality indicator lets you know if the image you have loaded has the right resolution for printing. If your image
                  quality is poor we recommend that you upload a new image with higher resolution.Our graphics team checks
                  every image submitted for resolution and quality requirements </div>
              </div>
            </div>
          </div>

          <div id="upload_info_hd_window" style="display:none;">
            <div class="global_overlay"></div>
            <div class="global_popup_box" style="width: 550px;height: 435px;">
              <div class="toolbar_button">
                <button class="close-window-positoin" onclick="closeMe(this);"></button>
              </div>
              <div class="save-icon">
                <i class="fa fa-info"></i>
              </div>
              <p class="new-heading pop-heading-line" id="imageGalleryPopupInfoCaption">Shows images uploaded by user</p>
              <div class="window_content">
                <div id="imageGalleryPopupInfoContent">
                  <!--content goes here-->
                  <B>For anonymous users:</B> The list is maintained for current session of design studio only.<br />
                  <br />
                  <B>For registered users after login:</B> It maintains the list of all images uploaded by user in several logged-in sessions. User can also attach
                  a high resolution vector source file in allowed formats CDR/PSD/AI/PDF/EPS along with the raster image
                  by clicking on “HD” button. You can see a right symbol with “Upload image” button to indicate images who
                  already have the high resolution image uploaded, which can be further replaced is needed. The high resolution
                  source file is sent to the admin once an order is placed for reference.<br /><br /> PLEASE LOG-IN TO MAINTAIN
                  YOUR IMAGE GALLERY AND ATTACH HIGH RESOLUTION SOURCE FILES WITH UPLOADED IMAGES. </div>
              </div>
            </div>
          </div>

          <div id="upload_info_window" style="display:none;">
            <div class="global_overlay"></div>
            <div class="global_popup_box" style="width:520px; height:335px;">
              <div class="image-upload-image" align="left"></div>
              <div class="toolbar_button">
                <button onClick="closeMe(this);" class="close-window-positoin"></button>
              </div>
              <p class="new-heading pop-heading-line a-left" id="uploadImageInfoPopupCaption">Upload Info</p>
              <div class="window_content">
                <div>
                  <!--content goes here-->
                  <div class="buttons">
                    <div class="image-upload-section" align="left">
                      <div class="image-upload-block">
                        <div id="supportedFormatCaption"> <b class="image-instruction-heading">Supported formats</b> 1) jpeg 2) jpg 3) png </div>
                      </div>
                      <div class="image-upload-block">
                        <div id="optimalResolutionCaption"> <b class="image-instruction-heading">Optimal resolution</b> 1500 x 1500 Pixel </div>
                      </div>
                      <div class="image-upload-block">
                        <div id="recommendedSizeCaption"> <b class="image-instruction-heading">Recommended size</b> Less than 5 mb </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>


          <div id="svg_login_window">
            <div id="svg_login_global_overlay" class="global_overlay"></div>
            <div id="svg_login_window_container" class="global_popup_box" style="width:700px;height:350px;">
              <div>
                <button id="tool_login_window_cancel" class="close-window-positoin"></button>
              </div>
              <p class="new-heading pop-heading-line" id="loginRegisterPopupCaption">Login/Register</p>
              <div id="error_msg"></div>
              <div class="login-table">
                <div class="login" style="width:48%; float:left;">
                  <label id="loginCaption" class="table-hd-cap">Log In</label>
                  <form>
                    <fieldset>
                      <div class="div-table">
                        <div class="div-table-row">
                          <div class="div-table-cell">
                            <label id="lEmailCaption">E-mail Address</label>
                          </div>
                          <div class="div-table-cell">
                            <input type="text" name="email_id" id="email_id" autocomplete="on" />
                          </div>
                        </div>
                        <div class="div-table-row">
                          <div class="div-table-cell">
                            <label id="lPasswordCaption">Password</label>
                          </div>
                          <div class="div-table-cell">
                            <input type="password" name="password" id="password" />
                          </div>
                        </div>
                        <div class="div-table-row">
                          <div class="div-table-cell">
                            <a id="forgetPasswordCaption" href="<?php echo Mage::getUrl('customer/account/forgotpassword/'); ?>" target="_blank">Forget Password?</a>
                          </div>
                        </div>
                        <div class="div-table-row">
                          <div class="div-table-cell">&nbsp;</div>
                          <div class="div-table-cell">
                            <div class="proceedbtn">
                              <input type="submit" class="cobutton login-button" id="btn_login" name="btn_login" value="Login" />
                              <input type="button" class="cobutton login-button" id="btn_cancel" name="btn_cancel" value="Cancel" />
                            </div>
                          </div>
                        </div>
                      </div>
                    </fieldset>
                  </form>
                </div>
                <div class="register" style="width:48%; float:left;">
                  <label id="registerCaption" class="table-hd-cap">Create an account</label>
                  <form>
                    <fieldset>
                      <div class="div-table">
                        <div class="div-table-row">
                          <div class="div-table-cell">
                            <label id="fNameCaption">First Name</label>
                          </div>
                          <div class="div-table-cell">
                            <input type="text" name="first_name" id="first_name" autocomplete="on" />
                          </div>
                        </div>
                        <div class="div-table-row">
                          <div class="div-table-cell">
                            <label id="lNameCaption">Last Name</label>
                          </div>
                          <div class="div-table-cell">
                            <input type="text" name="last_name" id="last_name" autocomplete="on" />
                          </div>
                        </div>
                        <div class="div-table-row">
                          <div class="div-table-cell">
                            <label id="rEmailCaption">E-mail Address</label>
                          </div>
                          <div class="div-table-cell">
                            <input type="text" name="reg_email_id" id="reg_email_id" autocomplete="on" />
                          </div>
                        </div>
                        <div class="div-table-row">
                          <div class="div-table-cell">
                            <label id="rPasswordCaption">Password</label>
                          </div>
                          <div class="div-table-cell">
                            <input type="password" name="reg_password" id="reg_password" />
                          </div>
                        </div>
                        <div class="div-table-row">
                          <div class="div-table-cell">
                            <label id="rConfirmPasswordCaption">Confirm Password</label>
                          </div>
                          <div class="div-table-cell">
                            <input type="password" name="conf_password" id="conf_password" />
                          </div>
                        </div>
                        <div class="div-table-row">
                          <div class="div-table-cell">&nbsp;</div>
                          <div class="div-table-cell">
                            <div class="proceedbtn">
                              <input type="submit" id="btn_submit" name="btn_submit" value="Submit" class="login-button" />
                            </div>
                          </div>
                        </div>
                      </div>
                    </fieldset>
                  </form>
                </div>
              </div>
            </div>
          </div>
          <div id="svg_save_design_window">
            <div class="global_overlay"></div>
            <!--<div id="svg_save_design_window_container" class="">-->
            <div class="global_popup_box" style="width:340px; height:270px;">
              <div class="toolbar_button">
                <button onclick="closeMe(this);" class="close-window-positoin"></button>
              </div>
              <div class="save-icon"><i class="fa fa-heart-o"></i></div>
              <p id="saveDesignCaption" class="new-heading">Save Your Design</p>
              <div class="save-design-table">
                <div class="save-design">
                  <label id="designDetailsCaption" class="table-hd-cap">Design Details</label>
                  <form>
                    <fieldset>
                      <div class="div-table">
                        <div class="div-table-row">
                          <div class="div-table-cell">
                            <label id="designNameCaption">Design Name</label>
                          </div>
                          <div class="div-table-cell">
                            <input type="text" name="design_name" id="design_name" />
                          </div>
                        </div>
                      </div>
                      <div class="div-table-row">
                        <div class="div-table-cell"><label style="color:#fff;width:118px;">Design </label></div>
                        <div class="div-table-cell">
                          <div class="proceedbtn">
                            <button class="generalbutton" name="btn_save_design" type="submit" id="btn_save_design">Submit</button>
                          </div>
                        </div>
                      </div>
                    </fieldset>
                  </form>
                </div>
              </div>
            </div>
          </div>
          <div id="facebook_window">
            <div id="facebook_global_overlay"></div>
            <div id="facebook_window_container" class="">
              <div class="toolbar_button">
                <button onclick="closeMe(this);" class="close-window-positoin"></button>
              </div>
              <p id="facebookUploadCaption" class="headingtwo">Upload your facebook photos</p>
              <div class="save-design-table">
                <select id="facebook_select">
      </select>
                <div class="fb_holder"></div>
              </div>
            </div>
          </div>
          <div id="flickr_window">
            <div class="global_overlay"></div>
            <div id="flickr_window_container" class="global_popup_box" style="width: 625px; height: 554px;">
              <div class="toolbar_button">
                <button onclick="closeMe(this);" class="close-window-positoin"></button>
              </div>
              <p id="flickrUploadCaption" class="new-heading pop-heading-line">Upload your flickr photos</p>
              <div class="save-design-table">
                <input type="text" size="25" id="flicker_import" autocomplete="on">
                <input type="button" value="GO!" class="enter" id="flicker_go">
                <!--<div id="import_error"></div>-->
                <div id="flickr_pager"></div>
                <div class="flickr_holder"></div>
              </div>
            </div>
          </div>
          <div id="picasa_window">
            <div class="global_overlay"></div>
            <div id="picasa_window_container" class="global_popup_box" style="width:600px; height:500px;">
              <div class="toolbar_button">
                <button onclick="closeMe(this);" class="close-window-positoin"></button>
              </div>
              <p id="picasaUploadCaption" class="new-heading pop-heading-line">Upload your picasa photos</p>
              <div class="save-design-table">
                <input type="text" size="25" id="picasa_import" autocomplete="on">
                <input type="button" value="GO!" class="enter" id="picasa_go">
                <!--<div id="import_error"></div>-->
                <div class="picasa_holder"></div>
              </div>
            </div>
          </div>

          <div id="instagram_window" style="display:none">
            <div id="instagram_global_overlay"></div>
            <div id="instagram_window_container" class="">
              <div class="toolbar_button">
                <button onclick="closeMe(this);" class="close-window-positoin"></button>
              </div>
              <p id="instagramUploadCaption" class="headingtwo">Upload your Instagram photos</p>
              <div class="save-design-table">
                <!--<a class="prev srbutton2">prev</a>
					<a class="next srbutton2">next</a>-->
                <div class="pager"> <a class="prev start-page">&larr; prev</a> <a class="next end-page">next &rarr;</a> </div>
                <!--<div id="import_error"></div>-->
                <div class="instagram_holder"></div>
              </div>
            </div>
          </div>
          <div id="preview_window">
            <div id="preview_window_overlay" class="global_overlay"></div>
            <div id="preview_window_container" class="global_popup_box" style="width:700px;height:640px;">
              <div class="toolbar_button">
                <!--<a href="#" onclick="javascript:downloadPreview(); void(0);" id="downloadPreviewCaption" class="download-preview">Download Preview </a>-->
                <button onclick="closeMe(this);" class="close-window-positoin"></button>
              </div>
              <p id="previewPopupCaption" class="new-heading pop-heading-line">Preview</p>
              <div class="save-design-table">
                <div class="preview_holder"></div>
              </div>
            </div>
          </div>
          <div id="bgImage_window">
            <div class="global_overlay">&nbsp;</div>
            <div id="bgImage_window_container" class="global_popup_box" style="width: 590px;height: 506px;">
              <div class="toolbar_button">
                <button onclick="closeMe(this);" class="close-window-positoin"></button>
              </div>
              <p id="backgroundImageCaption" class="new-heading pop-heading-line">Background Image</p>
              <div class="save-design-table">
                <div>
                  <div id="bgImage_category"></div>
                  <div class="bgImage_holder"></div>
                </div>
              </div>
            </div>
          </div>

          <script>
	showLoader(true);
	$j(document).ready(function(){
		svgEditor.ready(function() {
			init();
		});	
	});
	var qrcodeform =  new VarienForm('qrcodeform', false);
	
	svgEditor.setConfig({
		dimensions: [productWidth, productHeight],
		//position: [parseFloat(pos_x[1]),parseFloat(pos_y[1])],
		position: [0,0],
		canvas_expansion: 1.1,
		showRulers: true,
		initFill: {color:'000000'},
		initBg: {color:bgColor},
		initBorder: {color:borderColor},
		initStroke: {width: 0, color:'000000'},
		bkgd_color:'none',
		baseUnit: baseUnit,
		no_save_warning:true,
		//comment following js if you use built.min.js
		extensions:extensionArray,
		show_outside_canvas:0,
		lang:currentStore
	});
</script>
          <!-- load facebook plugin -->
          <!--<script src="//connect.facebook.net/en_US/all.js"></script> -->
          <!--<script type="text/javascript" src="//connect.facebook.net/<?php //echo $locale; ?>/all.js"></script>
<script type="text/javascript" src="<?php //echo $jspath.'facebook.js'; ?>"></script>-->
