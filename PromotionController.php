<?php
/**
* @package      Admin section - Promotion Controller
* @access       Private
* @version      1.0
* @Owner        Anylinuxwork
* @Details      This is Admin Promotion discount section. Controller is containing actions for Add/edit/delete 
*				operations for various types of promotion discounts.
**/
class Admin_PromotionController extends Zend_Controller_Action
{
	public $auth;
	/**
	* Set layout
	* if admin not logged in then redirect to admin login screen
	**/
	public function init()
	{
		$this->_helper->layout->setLayout('admin');
		$this->auth = Zend_Registry::get('auth');
		if(!isset($this->auth->user_id)){ 
			$this->_redirect('/admin');
		}
	}
	
	/**
	* indexAction
	*
	* Action will be responsible to show the grid for created promotional rules
	**/
	public function indexAction()
	{
		//Show success message
		if(isset($this->auth->success)){
		   $this->view->success = $this->auth->success;
		   unset($this->auth->success);
		  }
		$promotion = new Promotion();
		$gridObj = new Grid();
		//set default options for grid
		$page = $this->_getParam('page');
		$sortBy=$this->_getParam('sort','last_modified');
		$orderBy=$this->_getParam('order','DESC');
		$searchKey = $this->_getParam('search');
		
		if(isset($this->auth->privileges) && $this->auth->privileges!="All"){
			$privileges=unserialize($this->auth->privileges);
			if(!in_array('select-promotion-type,single-product-promotion,product-list-promotion,product-type-promotion,site-wide-promotion,cart-promotion,shipping-promotion,is-promotion-name-unique,sku-suggestions,get-metadata-options',$privileges[$this->_request->getControllerName()]) && in_array('edit,single-product-promotion,product-list-promotion,product-type-promotion,site-wide-promotion,cart-promotion,shipping-promotion,is-promotion-name-unique,sku-suggestions,get-metadata-options,select-promotion-type',$privileges[$this->_request->getControllerName()])){
				$this->view->hide_add_button=0;
			}else{
				$this->view->hide_add_button=1;
			}
		}else{
			$this->view->hide_add_button=1;
		}

		$optionArray = array('searchKey'=>$searchKey,'sortBy'=>$sortBy,'orderBy'=>$orderBy);
		$promotion->setOptions($optionArray);
		
		$this->view->grid = $gridObj->getRecordList($promotion,$orderBy,$searchKey,$page,PROMOTION_TABLE,true,$sortBy);
	}
	
	/**
	* selectPromotionTypeAction
	*
	* Action for admin to select type of promotion discount
	**/
	public function selectPromotionTypeAction()
	{
		if(isset($this->auth->privileges) && $this->auth->privileges!="All"){
			$privileges=unserialize($this->auth->privileges);
			if(!in_array('select-promotion-type,single-product-promotion,product-list-promotion,product-type-promotion,site-wide-promotion,cart-promotion,shipping-promotion,is-promotion-name-unique,sku-suggestions,get-metadata-options',$privileges[$this->_request->getControllerName()]) && in_array('edit,single-product-promotion,product-list-promotion,product-type-promotion,site-wide-promotion,cart-promotion,shipping-promotion,is-promotion-name-unique,sku-suggestions,get-metadata-options,select-promotion-type',$privileges[$this->_request->getControllerName()]) && !(int)$this->_request->getParam('id')) {
				$this->_redirect('/admin/error/error/');	
			}
		}
		//create object of promotion class
		$promotion = new Promotion();
		//get array for choosing promotion type
		$this->view->getPromotionTypesArray = $promotion->getPromotionTypes();
	}//end of selectPromotionTypeAction

	/**
	* singleProductPromotionAction
	*
	* Action for admin Add and Edit single product promotion discount
	**/
	public function singleProductPromotionAction() {
		if(isset($this->auth->privileges) && $this->auth->privileges!="All"){
			$privileges=unserialize($this->auth->privileges);
			if(!in_array('select-promotion-type,single-product-promotion,product-list-promotion,product-type-promotion,site-wide-promotion,cart-promotion,shipping-promotion,is-promotion-name-unique,sku-suggestions,get-metadata-options',$privileges[$this->_request->getControllerName()]) && in_array('edit,single-product-promotion,product-list-promotion,product-type-promotion,site-wide-promotion,cart-promotion,shipping-promotion,is-promotion-name-unique,sku-suggestions,get-metadata-options,select-promotion-type',$privileges[$this->_request->getControllerName()]) && !(int)$this->_request->getParam('id')) {
				$this->_redirect('/admin/error/error/');	
			}
		}
		//create form object
		$promotionDiscountId = (int)$this->_request->getParam('id');
		$singleProductPromotionForm = new Application_Form_AdminSingleProductPromotionForm($promotionDiscountId);
		$this->view->singleProductPromotionForm = $singleProductPromotionForm;
		$promotion = new Promotion();
		$promotionRecord = array();
		$whereArray = array();
		//if we will get id in the parameter then fetch row to edit
		if($promotionDiscountId) {
			$fieldArray = array("promotion_discount_id","name","url","banner_image_path","carousel_image_path","intro as description","DATE_FORMAT(start_time,'%m/%d/%Y %H:%i') as start_time","DATE_FORMAT(finish_time,'%m/%d/%Y %H:%i') as expiry_time","fixed_discount","percentage_discount","minimum_value as min_value","status");
			$promotionRecord = $promotion->getRecords($promotionDiscountId, $fieldArray, 1);
			$promotionRecord = $promotionRecord->toArray();
			$this->view->promotionRecord = $promotionRecord;
			$singleProductPromotionForm->populate($promotionRecord);
		}
		//assigning promotionDiscountId to view to identify Edit or Add case in View file.
		$this->view->promotionDiscountId = $promotionDiscountId;
		if($this->_request->isPost()){
			$formData = $this->_request->getPost();
			//check if form fields are valid or not.
			if($singleProductPromotionForm->isValid($formData)){
				//check if entered promotion name is unique or not
				$isUniqueName = $promotion->isPromotionNameUnique($formData['name']);
				$productId = $promotion->isSkuExist($formData['sku']);
				//assume fields are valid
				$isvalidfields = 1;
				if(($isUniqueName!=0) && ($isUniqueName!=$formData['promotion_discount_id'])) {
					$this->view->uniqueErrorMsg = PROMOTION_UNIQUE_NAME_ERROR;
					$isvalidfields = 0;
				}
				if(!$productId) {
					$this->view->validSkuErrorMsg = PROMOTION_INVALID_SKU_ERROR;
					$isvalidfields = 0;
				}
				if(!$isvalidfields) {
					$singleProductPromotionForm->populate($formData);
				} else {
					//upload images for promotion discount.
					$imageNames = $promotion->uploadPromotionImages($formData['name'], $singleProductPromotionForm);
					$bannerImage = $imageNames['banner_image_path'];
					$carouselImage = $imageNames['carousel_image_path'];

					$url = $promotion->getPromotionDiscountUrl($formData['name']);
					$startTime =$promotion->getStandardTimeStampFormat($formData['start_time'], (!$promotionDiscountId));
					$expiryTime = $promotion->getStandardTimeStampFormat($formData['expiry_time'], (!$promotionDiscountId));
					//we have mentioned product_id =1 for now only because we don't have products yet.
					$dataArray = array(
									"name" => $formData['name'],
									"type" => 1,
									"url" => $url,
									"banner_image_path" => $bannerImage,
									"carousel_image_path" => $carouselImage,
									"intro" => $formData['description'],
									"start_time" => $startTime,
									"finish_time" => $expiryTime,
									"fixed_discount" => (float)$formData['fixed_discount'],
									"percentage_discount" => (float)$formData['percentage_discount'],
									"product_id" => (int)$productId,
									"minimum_value" => (float)$formData['min_value'],
									"status" => $formData['status'],
									"last_updated_by" => $this->auth->user_id
								);
					if(!$formData['promotion_discount_id']) {
						$promotionDiscountId = $promotion->savePromotionDiscount($dataArray);
						$this->auth->success =1; //for showing sucess msg
					}
					else if($formData['promotion_discount_id']) {
						/*check if file uploaded or not in case of edit record. If not uploaded any new record then remove 'banner_image_path' key from data array so the it will not update its previous value*/
						if(!$bannerImage)
							unset($dataArray['banner_image_path']);
						else
							$promotion->deleteOldServerImages($promotionRecord['banner_image_path'], 2);
						if(!$carouselImage)
							unset($dataArray['carousel_image_path']);
						else
							$promotion->deleteOldServerImages($promotionRecord['carousel_image_path'], 1);
						$whereArray['promotion_discount_id'] = $formData['promotion_discount_id'];
						$promotion->updatePromotionDiscount($dataArray, $whereArray, $promotionRecord);
						$this->auth->success = 2;
					}
					$this->_redirect('/admin/promotion');
				}
			} else {
				$singleProductPromotionForm->populate($formData);
			}
		}
	}//end of singleProductPromotionAction

	/**
	* isPromotionNameUniqueAction
	*
	* Action is resposible to check that whether entered name is unique or not.
	**/
	public function isPromotionNameUniqueAction() {
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		//create promotion class object
		$promotion = new Promotion();
		$promotionName = trim($this->_request->getPost('name'));
		echo $promotion->isPromotionNameUnique($promotionName);
	}//end of isPromotionNameUniqueAction

	/**
	* skuSuggestionsAction
	*
	* Action is resposible to auto suggest values for sku field on key press event on
	* createSingleProductPromotionAction
	**/
	public function skuSuggestionsAction() {
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		//create promotion class object
		$productListobj = new ProductList();
		$promotion = new Promotion();
		$queryString = trim($this->_request->getParam('query'));
		$skuSuggestions = $productListobj->getSkuSuggestions($queryString);
		if(sizeof($skuSuggestions)){
			foreach($skuSuggestions as $ps){
				$skuSugg[] = $ps->{ATTR_NAME}." [".$ps->sku."]";
			}
		}
		echo "{";
		echo "query:'$queryString',";
		echo "suggestions:".Zend_Json::encode($skuSugg);
		echo "}";
	}//end of skuSuggestionsAction

	/**
	* viewAction
	*
	* Action for admin to view promotion discount
	**/
	public function viewAction()
	{
		//create object of promotion class
		$promotion = new Promotion();
		$promotionDiscountId = $this->_request->getParam('id');
		$promotionInfo = $promotion->viewRecord($promotionDiscountId);
		if(!count($promotionInfo)) {
			$this->auth->success = 6;
			$this->_redirect('/admin/promotion');
		}
		//get Name of promotion Type as per typeId from database and assign array to view
		$this->view->typeHtml = $promotion->showPromotionTypeHtml($promotionInfo->type);
		$this->view->promotionInfo = $promotionInfo;
		if($promotionInfo->type == 2) {
			//get all the list from database anf pass it to form
			$productListArray = $promotion->getProductList();
			foreach($productListArray as $list) {
				$listArray[$list->list_id] = $list->name;
			}
			$selectedListIds = explode(',',$promotionInfo->product_list_ids);
			foreach($selectedListIds as $selectedlist) {
				$tempArray[] = $listArray[$selectedlist];
			}
			$this->view->productList = implode(', ',$tempArray);
		} else if($promotionInfo->type == 3) {
			$metaDataRecord = $promotion->getMetaDataRecords($promotionDiscountId);
			$this->view->metaDataRecord = $metaDataRecord;
		}
	}//end of viewAction

	/**
	* editAction
	*
	* Action for admin to edit promotion discount
	**/
	public function editAction()
	{
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		//create object of promotion class
		$promotion = new Promotion();
		$promotionDiscountId = $this->_request->getParam('id');
		$fieldarray = array("type", "finish_time");
		$promotionInfo = $promotion->getRecords($promotionDiscountId,$fieldarray);
		if(!count($promotionInfo)) {
			$this->auth->success = 6;
			$this->_redirect('/admin/promotion');
		}
		$currentTime = $promotion->getStandardTimeStampFormat();
		if($promotionInfo->finish_time < $currentTime) {
			$this->auth->success = 5;
			$this->_redirect('/admin/promotion/');
		}
		$editScreen ='';
		switch($promotionInfo->type){
		case '1':
			$editScreen = 'single-product-promotion';
			break;
		case '2':
			$editScreen = 'product-list-promotion';
			break;
		case '3':
			$editScreen = 'product-type-promotion';
			break;
		case '4':
			$editScreen = 'site-wide-promotion';
			break;
		case '5':
			$editScreen = 'cart-promotion';
			break;
		case '6':
			$editScreen = 'shipping-promotion';
			break;
		case '7':
			$editScreen = 'single-product-cart-promotion';
			break;
		default:
			break;
		}
		$request = clone $this->getRequest();
		$request->setActionName($editScreen)
		->setParams(array('id' => $promotionDiscountId));
		$this->_helper->actionStack($request); 
		$this->render($editScreen);
	}//end of editAction

	/**
	* deleteAction
	*
	* Action for admin to delete promotion discount
	**/
	public function deleteAction()
	{
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		//create object of promotion class
		$promotion = new Promotion();
		$promotionDiscountId = (int)$this->_request->getParam('id');
		$whereArray['promotion_discount_id'] = $promotionDiscountId;
		//first get promotion record information to delete image from server
		$fieldArray = array("type","banner_image_path","carousel_image_path");
		$promotionRecord = $promotion->getInactivePromotionRecord($promotionDiscountId, $fieldArray);
		//if promotion record means current promotion is not active hence delete it.
		if(count($promotionRecord)) {
			//if image name exist then delete that image from server
			if($promotionRecord->banner_image_path!='') {
				$promotion->deleteOldServerImages($promotionRecord->banner_image_path, 2);
			}
			if($promotionRecord->carousel_image_path!='') {
				$promotion->deleteOldServerImages($promotionRecord->carousel_image_path, 1);
			}
			/*if promotion type is "Product Type Promotion" then delete entries for meta data options as well from discount_metadata_options table*/
			if($promotionRecord->type == 3) {
				//delete old meta-data options
				$promotion->deleteMetaDataOptions($whereArray);
			}
			$promotionInfo = $promotion->deletePromotionDiscount($whereArray);
			$this->auth->success = 3;
		} else {
			$this->auth->success = 4;
		}
		$this->_redirect('/admin/promotion/');
	}//end of deleteAction

	/**
	* productListPromotionAction
	*
	* Action for admin to Add and edit product list promotion discount
	**/
	public function productListPromotionAction() {
		if(isset($this->auth->privileges) && $this->auth->privileges!="All"){
			$privileges=unserialize($this->auth->privileges);
			if(!in_array('select-promotion-type,single-product-promotion,product-list-promotion,product-type-promotion,site-wide-promotion,cart-promotion,shipping-promotion,is-promotion-name-unique,sku-suggestions,get-metadata-options',$privileges[$this->_request->getControllerName()]) && in_array('edit,single-product-promotion,product-list-promotion,product-type-promotion,site-wide-promotion,cart-promotion,shipping-promotion,is-promotion-name-unique,sku-suggestions,get-metadata-options,select-promotion-type',$privileges[$this->_request->getControllerName()]) && !(int)$this->_request->getParam('id')) {
				$this->_redirect('/admin/error/error/');	
			}
		}
		$promotionDiscountId = (int)$this->_request->getParam('id');
		$promotion = new Promotion();
		//get all the list from database anf pass it to form
		//create form object
		$productListArray = $promotion->getProductList();
		$productListPromotionForm = new Application_Form_AdminProductListPromotionForm($promotionDiscountId,$productListArray);
		$this->view->productListPromotionForm = $productListPromotionForm;
		$promotionRecord = array();
		$selectedList = array();
		$whereArray = array();
		//if we will get id in the parameter then fetch row to edit
		if($promotionDiscountId) {
			$fieldArray = array("promotion_discount_id","name","url","banner_image_path","carousel_image_path","intro as description","DATE_FORMAT(start_time,'%m/%d/%Y %H:%i') as start_time","DATE_FORMAT(finish_time,'%m/%d/%Y %H:%i') as expiry_time","fixed_discount","percentage_discount","product_list_ids","minimum_value as min_value","status");
			$promotionRecord = $promotion->getRecords($promotionDiscountId, $fieldArray);
			$promotionRecord = $promotionRecord->toArray();
			$selectedList = explode(',',$promotionRecord['product_list_ids']);
			$promotionRecord['product_list'] = $selectedList;
			$this->view->promotionRecord = $promotionRecord;			
			$productListPromotionForm->populate($promotionRecord);
		}
		//assigning promotionDiscountId to view to identify Edit or Add case in View file.
		$this->view->promotionDiscountId = $promotionDiscountId;
		if($this->_request->isPost()){
			$formData = $this->_request->getPost();
			//check if form fields are valid or not.
			if($productListPromotionForm->isValid($formData)){
				//check if entered promotion name is unique or not
				$isUniqueName = $promotion->isPromotionNameUnique($formData['name']);
				if(($isUniqueName!=0) && ($isUniqueName!=$formData['promotion_discount_id'])) {
					$this->view->uniqueErrorMsg = PROMOTION_UNIQUE_NAME_ERROR;
					$productListPromotionForm->populate($formData);
				} else {
					//upload images for promotion discount.
					$imageNames = $promotion->uploadPromotionImages($formData['name'], $productListPromotionForm);
					$bannerImage = $imageNames['banner_image_path'];
					$carouselImage = $imageNames['carousel_image_path'];

					$url = $promotion->getPromotionDiscountUrl($formData['name']);
					$startTime = $promotion->getStandardTimeStampFormat($formData['start_time']);
					$expiryTime = $promotion->getStandardTimeStampFormat($formData['expiry_time'], (!$promotionDiscountId));
					//implode array to make comma separated list of product list ids
					$product_list_ids = implode(',',$formData['product_list']);
					$dataArray = array(
									"name" => $formData['name'],
									"type" => 2,
									"url" => $url,
									"banner_image_path" => $bannerImage,
									"carousel_image_path" => $carouselImage,
									"intro" => $formData['description'],
									"start_time" => $startTime,
									"finish_time" => $expiryTime,
									"fixed_discount" => (float)$formData['fixed_discount'],
									"percentage_discount" => (float)$formData['percentage_discount'],
									"product_list_ids" => $product_list_ids,
									"minimum_value" => (float)$formData['min_value'],
									"status" => $formData['status'],
									"last_updated_by" => $this->auth->user_id
								);
					if(!$formData['promotion_discount_id']) {
						$promotionDiscountId = $promotion->savePromotionDiscount($dataArray);
						$this->auth->success =1;
					} else if($formData['promotion_discount_id']) {
						/*check if file uploaded or not in case of edit record. If not uploaded any new record then remove 'banner_image_path' key from data array so the it will not update its previous value*/
						if(!$bannerImage)
							unset($dataArray['banner_image_path']);
						else
							$promotion->deleteOldServerImages($promotionRecord['banner_image_path'], 2);
						if(!$carouselImage)
							unset($dataArray['carousel_image_path']);
						else
							$promotion->deleteOldServerImages($promotionRecord['carousel_image_path'], 1);
						$whereArray['promotion_discount_id'] = $formData['promotion_discount_id'];
						$promotion->updatePromotionDiscount($dataArray, $whereArray, $promotionRecord);
						$this->auth->success = 2;
					}
					$this->_redirect('/admin/promotion');
				}
			} else {
				$productListPromotionForm->populate($formData);
			}
		}//end of if post
	}//end of productListPromotionAction

	/**
	* productTypePromotionAction
	*
	* Action for admin to create product type promotion discount
	**/
	public function productTypePromotionAction() {
		if(isset($this->auth->privileges) && $this->auth->privileges!="All"){
			$privileges=unserialize($this->auth->privileges);
			if(!in_array('select-promotion-type,single-product-promotion,product-list-promotion,product-type-promotion,site-wide-promotion,cart-promotion,shipping-promotion,is-promotion-name-unique,sku-suggestions,get-metadata-options',$privileges[$this->_request->getControllerName()]) && in_array('edit,single-product-promotion,product-list-promotion,product-type-promotion,site-wide-promotion,cart-promotion,shipping-promotion,is-promotion-name-unique,sku-suggestions,get-metadata-options,select-promotion-type',$privileges[$this->_request->getControllerName()]) && !(int)$this->_request->getParam('id')) {
				$this->_redirect('/admin/error/error/');	
			}
		}
		$promotionDiscountId = (int)$this->_request->getParam('id');
		//create form object
		$promotion = new Promotion();	
		$productAttribArray = array();
		//fetch type of attributes which are enabled for creating product_type_promotion
		$productAttribArray = $promotion->getProductAttribute();
		$this->view->productAttribArray = $productAttribArray;
		$productTypePromotionForm = new Application_Form_AdminProductTypePromotionForm($promotionDiscountId, $productAttribArray);
		$this->view->productTypePromotionForm = $productTypePromotionForm;
		$promotionRecord = array();
		$whereArray = array();
		//if we will get id in the parameter then fetch row to edit
		if($promotionDiscountId) {
			$fieldArray = array("promotion_discount_id","name","url","banner_image_path","carousel_image_path","intro as description","DATE_FORMAT(start_time,'%m/%d/%Y %H:%i') as start_time","DATE_FORMAT(finish_time,'%m/%d/%Y %H:%i') as expiry_time","fixed_discount","percentage_discount","minimum_value as min_value","status");
			$promotionRecord = $promotion->getRecords($promotionDiscountId, $fieldArray);
			$promotionRecord = $promotionRecord->toArray();
			$metaDataRecord = $promotion->getMetaDataRecords($promotionDiscountId);
			$this->view->metaDataRecord = $metaDataRecord;
			$promotionRecord['attributes'] = $metaDataRecord['attributes'];
			$this->view->promotionRecord = $promotionRecord;
			$productTypePromotionForm->populate($promotionRecord);
		}
		//assigning promotionDiscountId to view to identify Edit or Add case in View file.
		$this->view->promotionDiscountId = $promotionDiscountId;
		if($this->_request->isPost()){
			$formData = $this->_request->getPost();
			//check if form fields are valid or not.
			if($productTypePromotionForm->isValid($formData)){
				//check if entered promotion name is unique or not
				$isUniqueName = $promotion->isPromotionNameUnique($formData['name']);
				if(($isUniqueName!=0) && ($isUniqueName!=$formData['promotion_discount_id'])) {
					$this->view->uniqueErrorMsg = PROMOTION_UNIQUE_NAME_ERROR;
					$productTypePromotionForm->populate($formData);
				} else {
					//upload images for promotion discount.
					$imageNames = $promotion->uploadPromotionImages($formData['name'], $productTypePromotionForm);
					$bannerImage = $imageNames['banner_image_path'];
					$carouselImage = $imageNames['carousel_image_path'];

					$url = $promotion->getPromotionDiscountUrl($formData['name']);
					$startTime =$promotion->getStandardTimeStampFormat($formData['start_time'], (!$promotionDiscountId));
					$expiryTime = $promotion->getStandardTimeStampFormat($formData['expiry_time'], (!$promotionDiscountId));
					$dataArray = array(
									"name" => $formData['name'],
									"type" => 3,
									"url" => $url,
									"banner_image_path" => $bannerImage,
									"carousel_image_path" => $carouselImage,
									"intro" => $formData['description'],
									"start_time" => $startTime,
									"finish_time" => $expiryTime,
									"fixed_discount" => (float)$formData['fixed_discount'],
									"percentage_discount" => (float)$formData['percentage_discount'],
									"minimum_value" => (float)$formData['min_value'],
									"status" => $formData['status'],
									"last_updated_by" => $this->auth->user_id
								);
					if(!$formData['promotion_discount_id']) {
						$newPromotionDiscountId = $promotion->savePromotionDiscount($dataArray);
						$promotion->saveMetaDataOptions($formData, $newPromotionDiscountId);
						$this->auth->success =1;
					}else if($formData['promotion_discount_id']) {
						/*check if file uploaded or not in case of edit record. If not uploaded any new record then remove 'banner_image_path' key from data array so the it will not update its previous value*/
						if(!$bannerImage)
							unset($dataArray['banner_image_path']);
						else
							$promotion->deleteOldServerImages($promotionRecord['banner_image_path'], 2);
						if(!$carouselImage)
							unset($dataArray['carousel_image_path']);
						else
							$promotion->deleteOldServerImages($promotionRecord['carousel_image_path'], 1);
						$whereArray['promotion_discount_id'] = $formData['promotion_discount_id'];
						$promotion->updatePromotionDiscount($dataArray, $whereArray, $promotionRecord);
						//delete old meta-data options
						$promotion->deleteMetaDataOptions($whereArray);
						//assigning promotion id to create entry for options.
						$promotion->saveMetaDataOptions($formData, $formData['promotion_discount_id']);				
						$this->auth->success = 2; //to show sucess msg
					}
					$this->_redirect('/admin/promotion');
				}
			} else {
				$productTypePromotionForm->populate($formData);
			}
		}
	}//end of productTypePromotionAction

	public function getMetadataOptionsAction() {
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		//create promotion class object
		$promotion = new Promotion();
		$queryString = trim($this->_request->getParam('tag'));		
		$attributeId = trim($this->_request->getParam('attrib'));
		$optionsSuggestions = $promotion->getOptionsSuggestions($queryString,$attributeId);
		$jsonOutput ='';
		if(count($optionsSuggestions)) {
			foreach($optionsSuggestions as $option) {
				$jsonOutput .= "{";
				$jsonOutput .= '"key":'.Zend_Json::encode($option->id).", ";
				$jsonOutput .= '"value":'.Zend_Json::encode($option->value);
				$jsonOutput .= "}, ";
			}
			$jsonOutput = substr($jsonOutput, 0 , -2);
			echo "[".$jsonOutput."]";
		}else {
			echo '["key":'.Zend_Json::encode($queryString).', "value":'.Zend_Json::encode($queryString).']';
		}
	}


	/**
	* siteWidePromotionAction
	*
	* Action for admin to create site wide promotion discount
	**/
	public function siteWidePromotionAction() {
		if(isset($this->auth->privileges) && $this->auth->privileges!="All"){
			$privileges=unserialize($this->auth->privileges);
			if(!in_array('select-promotion-type,single-product-promotion,product-list-promotion,product-type-promotion,site-wide-promotion,cart-promotion,shipping-promotion,is-promotion-name-unique,sku-suggestions,get-metadata-options',$privileges[$this->_request->getControllerName()]) && in_array('edit,single-product-promotion,product-list-promotion,product-type-promotion,site-wide-promotion,cart-promotion,shipping-promotion,is-promotion-name-unique,sku-suggestions,get-metadata-options,select-promotion-type',$privileges[$this->_request->getControllerName()]) && !(int)$this->_request->getParam('id')) {
				$this->_redirect('/admin/error/error/');	
			}
		}
		//create form object
		$promotionDiscountId = (int)$this->_request->getParam('id');
		$siteWidePromotionForm = new Application_Form_AdminSiteWidePromotionForm($promotionDiscountId);
		$this->view->siteWidePromotionForm = $siteWidePromotionForm;
		$promotion = new Promotion();
		$promotionRecord = array();
		$whereArray = array();
		//if we will get id in the parameter then fetch row to edit
		if($promotionDiscountId) {
			$fieldArray = array("promotion_discount_id","name","url","banner_image_path","carousel_image_path","intro as description","DATE_FORMAT(start_time,'%m/%d/%Y %H:%i') as start_time","DATE_FORMAT(finish_time,'%m/%d/%Y %H:%i') as expiry_time","fixed_discount","percentage_discount","is_gift_vouchers_included as gift_voucher_flag","minimum_value as min_value","status");
			$promotionRecord = $promotion->getRecords($promotionDiscountId, $fieldArray);
			$promotionRecord = $promotionRecord->toArray();
			$this->view->promotionRecord = $promotionRecord;
			$siteWidePromotionForm->populate($promotionRecord);
		}
		//assigning promotionDiscountId to view to identify Edit or Add case in View file.
		$this->view->promotionDiscountId = $promotionDiscountId;
		if($this->_request->isPost()){
			$formData = $this->_request->getPost();
			//check if form fields are valid or not.
			if($siteWidePromotionForm->isValid($formData)){
				//check if entered promotion name is unique or not
				$isUniqueName = $promotion->isPromotionNameUnique($formData['name']);
				if(($isUniqueName!=0) && ($isUniqueName!=$formData['promotion_discount_id'])) {
					$this->view->uniqueErrorMsg = PROMOTION_UNIQUE_NAME_ERROR;
					$siteWidePromotionForm->populate($formData);
				} else {
					//upload images for promotion discount.
					$imageNames = $promotion->uploadPromotionImages($formData['name'], $siteWidePromotionForm);
					$bannerImage = $imageNames['banner_image_path'];
					$carouselImage = $imageNames['carousel_image_path'];

					$url = $promotion->getPromotionDiscountUrl($formData['name']);
					$startTime =$promotion->getStandardTimeStampFormat($formData['start_time'], (!$promotionDiscountId));
					$expiryTime = $promotion->getStandardTimeStampFormat($formData['expiry_time'], (!$promotionDiscountId));
					$dataArray = array(
									"name" => $formData['name'],
									"type" => 4,
									"url" => $url,
									"banner_image_path" => $bannerImage,
									"carousel_image_path" => $carouselImage,
									"intro" => $formData['description'],
									"start_time" => $startTime,
									"finish_time" => $expiryTime,
									"fixed_discount" => (float)$formData['fixed_discount'],
									"percentage_discount" => (float)$formData['percentage_discount'],
									"is_gift_vouchers_included" => (int)$formData['gift_voucher_flag'],
									"minimum_value" => (float)$formData['min_value'],
									"status" => $formData['status'],
									"last_updated_by" => $this->auth->user_id
								);
					if(!$formData['promotion_discount_id']) {
						$promotionDiscountId = $promotion->savePromotionDiscount($dataArray);
						$this->auth->success = 1;
					} else if($formData['promotion_discount_id']) {
						/*check if file uploaded or not in case of edit record. If not uploaded any new record then remove 'banner_image_path' key from data array so the it will not update its previous value*/
						if(!$bannerImage)
							unset($dataArray['banner_image_path']);
						else
							$promotion->deleteOldServerImages($promotionRecord['banner_image_path'], 2);
						if(!$carouselImage)
							unset($dataArray['carousel_image_path']);
						else
							$promotion->deleteOldServerImages($promotionRecord['carousel_image_path'], 1);
						$whereArray['promotion_discount_id'] = $formData['promotion_discount_id'];
						$promotion->updatePromotionDiscount($dataArray, $whereArray, $promotionRecord);
						$this->auth->success = 2;
					}
					$this->_redirect('/admin/promotion');
				}
			} else {
				$siteWidePromotionForm->populate($formData);
			}
		}
	}//end of siteWidePromotionAction

	/**
	* cartPromotionAction
	*
	* Action for admin to create cart promotion discount
	**/
	public function cartPromotionAction() {
		if(isset($this->auth->privileges) && $this->auth->privileges!="All"){
			$privileges=unserialize($this->auth->privileges);
			if(!in_array('select-promotion-type,single-product-promotion,product-list-promotion,product-type-promotion,site-wide-promotion,cart-promotion,shipping-promotion,is-promotion-name-unique,sku-suggestions,get-metadata-options',$privileges[$this->_request->getControllerName()]) && in_array('edit,single-product-promotion,product-list-promotion,product-type-promotion,site-wide-promotion,cart-promotion,shipping-promotion,is-promotion-name-unique,sku-suggestions,get-metadata-options,select-promotion-type',$privileges[$this->_request->getControllerName()]) && !(int)$this->_request->getParam('id')) {
				$this->_redirect('/admin/error/error/');	
			}
		}
		$promotionDiscountId = (int)$this->_request->getParam('id');
		//create form object
		$cartPromotionForm = new Application_Form_AdminCartPromotionForm($promotionDiscountId);
		$this->view->cartPromotionForm = $cartPromotionForm;
		$promotion = new Promotion();
		$promotionRecord = array();
		$whereArray = array();
		//if we will get id in the parameter then fetch row to edit
		if($promotionDiscountId) {
			$fieldArray = array("promotion_discount_id","name","url","banner_image_path","carousel_image_path","intro as description","DATE_FORMAT(start_time,'%m/%d/%Y %H:%i') as start_time","DATE_FORMAT(finish_time,'%m/%d/%Y %H:%i') as expiry_time","fixed_discount","percentage_discount","code","minimum_value as min_value","status");
			$promotionRecord = $promotion->getRecords($promotionDiscountId, $fieldArray);
			$promotionRecord = $promotionRecord->toArray();
			$this->view->promotionRecord = $promotionRecord;
			$cartPromotionForm->populate($promotionRecord);
		}
		//assigning promotionDiscountId to view to identify Edit or Add case in View file.
		$this->view->promotionDiscountId = $promotionDiscountId;
		if($this->_request->isPost()){
			$formData = $this->_request->getPost();
			//check if form fields are valid or not.
			if($cartPromotionForm->isValid($formData)){
				//check if entered promotion name is unique or not
				$isUniqueName = $promotion->isPromotionNameUnique($formData['name']);
				$isCartDiscUnique = $promotion->isCardDiscountCodeUnique($formData['code']);
				//assume fields are valid
				$isvalidfields = 1;
				if(($isUniqueName!=0) && ($isUniqueName!=$formData['promotion_discount_id'])) {
					$this->view->uniqueErrorMsg = PROMOTION_UNIQUE_NAME_ERROR;
					$isvalidfields = 0;
				}
				if(($isCartDiscUnique!=0 && ($isCartDiscUnique!=$formData['promotion_discount_id'])) || $isCartDiscUnique == -1) {
					$this->view->uniqueCartDiscErrorMsg = 'Please enter unique code for cart discount';
					$isvalidfields = 0;
				}
				if(!$isvalidfields) {
					$cartPromotionForm->populate($formData);
				} else {
					//upload images for promotion discount.
					$imageNames = $promotion->uploadPromotionImages($formData['name'], $cartPromotionForm);
					$bannerImage = $imageNames['banner_image_path'];
					$carouselImage = $imageNames['carousel_image_path'];

					$url = $promotion->getPromotionDiscountUrl($formData['name']);
					$startTime =$promotion->getStandardTimeStampFormat($formData['start_time'], (!$promotionDiscountId));
					$expiryTime = $promotion->getStandardTimeStampFormat($formData['expiry_time'], (!$promotionDiscountId));
					$dataArray = array(
									"name" => $formData['name'],
									"type" => 5,
									"url" => $url,
									"banner_image_path" => $bannerImage,
									"carousel_image_path" => $carouselImage,
									"intro" => $formData['description'],
									"start_time" => $startTime,
									"finish_time" => $expiryTime,
									"fixed_discount" => (float)$formData['fixed_discount'],
									"percentage_discount" => (float)$formData['percentage_discount'],
									"code" => $formData['code'],
									"minimum_value" => (float)$formData['min_value'],
									"status" => $formData['status'],
									"last_updated_by" => $this->auth->user_id
								);
					if(!$formData['promotion_discount_id']) {
						$promotion->savePromotionDiscount($dataArray);
						$this->auth->success =1;
					}else if($formData['promotion_discount_id']) {
						/*check if file uploaded or not in case of edit record. If not uploaded any new record then remove 'banner_image_path' key from data array so the it will not update its previous value*/
						if(!$bannerImage)
							unset($dataArray['banner_image_path']);
						else
							$promotion->deleteOldServerImages($promotionRecord['banner_image_path'], 2);
						if(!$carouselImage)
							unset($dataArray['carousel_image_path']);
						else
							$promotion->deleteOldServerImages($promotionRecord['carousel_image_path'], 1);
						$whereArray['promotion_discount_id'] = $formData['promotion_discount_id'];
						$promotion->updatePromotionDiscount($dataArray, $whereArray);
						$this->auth->success = 2;
					}
					$this->_redirect('/admin/promotion');
				}
			} else {
				$cartPromotionForm->populate($formData);
			}
		}
	}//end of cartPromotionAction

	/**
	* shippingPromotionAction
	*
	* Action for admin to create shipping promotion discount
	**/
	public function shippingPromotionAction() {
		if(isset($this->auth->privileges) && $this->auth->privileges!="All"){
			$privileges=unserialize($this->auth->privileges);
			if(!in_array('select-promotion-type,single-product-promotion,product-list-promotion,product-type-promotion,site-wide-promotion,cart-promotion,shipping-promotion,is-promotion-name-unique,sku-suggestions,get-metadata-options',$privileges[$this->_request->getControllerName()]) && in_array('edit,single-product-promotion,product-list-promotion,product-type-promotion,site-wide-promotion,cart-promotion,shipping-promotion,is-promotion-name-unique,sku-suggestions,get-metadata-options,select-promotion-type',$privileges[$this->_request->getControllerName()]) && !(int)$this->_request->getParam('id')) {
				$this->_redirect('/admin/error/error/');	
			}
		}
		$promotionDiscountId = (int)$this->_request->getParam('id');
		//create form object
		$shippingPromotionForm = new Application_Form_AdminShippingPromotionForm($promotionDiscountId);
		$this->view->shippingPromotionForm = $shippingPromotionForm;
		$promotion = new Promotion();
		$promotionRecord = array();
		$whereArray = array();
		//if we will get id in the parameter then fetch row to edit
		if($promotionDiscountId) {
			$fieldArray = array("promotion_discount_id","name","url","banner_image_path","carousel_image_path","intro as description","DATE_FORMAT(start_time,'%m/%d/%Y %H:%i') as start_time","DATE_FORMAT(finish_time,'%m/%d/%Y %H:%i') as expiry_time","fixed_discount","percentage_discount","minimum_value as min_value","status");
			$promotionRecord = $promotion->getRecords($promotionDiscountId, $fieldArray);
			$promotionRecord = $promotionRecord->toArray();
			$this->view->promotionRecord = $promotionRecord;
			$shippingPromotionForm->populate($promotionRecord);
		}
		//assigning promotionDiscountId to view to identify Edit or Add case in View file.
		$this->view->promotionDiscountId = $promotionDiscountId;
		if($this->_request->isPost()){
			$formData = $this->_request->getPost();
			//check if form fields are valid or not.
			if($shippingPromotionForm->isValid($formData)){
				//check if entered promotion name is unique or not
				$isUniqueName = $promotion->isPromotionNameUnique($formData['name']);
				if(($isUniqueName!=0) && ($isUniqueName!=$formData['promotion_discount_id'])) {
					$this->view->uniqueErrorMsg = PROMOTION_UNIQUE_NAME_ERROR;
					$shippingPromotionForm->populate($formData);
				} else {
					//upload images for promotion discount.
					$imageNames = $promotion->uploadPromotionImages($formData['name'], $shippingPromotionForm);
					$bannerImage = $imageNames['banner_image_path'];
					$carouselImage = $imageNames['carousel_image_path'];

					$url = $promotion->getPromotionDiscountUrl($formData['name']);
					$startTime =$promotion->getStandardTimeStampFormat($formData['start_time'], (!$promotionDiscountId));
					$expiryTime = $promotion->getStandardTimeStampFormat($formData['expiry_time'], (!$promotionDiscountId));
					$dataArray = array(
									"name" => $formData['name'],
									"type" => 6,
									"url" => $url,
									"banner_image_path" => $bannerImage,
									"carousel_image_path" => $carouselImage,
									"intro" => $formData['description'],
									"start_time" => $startTime,
									"finish_time" => $expiryTime,
									"fixed_discount" => (float)$formData['fixed_discount'],
									"percentage_discount" => (float)$formData['percentage_discount'],
									"minimum_value" => (float)$formData['min_value'],
									"status" => $formData['status'],
									"last_updated_by" => $this->auth->user_id
								);
					if(!$formData['promotion_discount_id']) {
						$promotion->savePromotionDiscount($dataArray);
						$this->auth->success = 1;
					} else if($formData['promotion_discount_id']) {
						/*check if file uploaded or not in case of edit record. If not uploaded any new record then remove 'banner_image_path' key from data array so the it will not update its previous value*/
						if(!$bannerImage)
							unset($dataArray['banner_image_path']);
						else
							$promotion->deleteOldServerImages($promotionRecord['banner_image_path'], 2);
						if(!$carouselImage)
							unset($dataArray['carousel_image_path']);
						else
							$promotion->deleteOldServerImages($promotionRecord['carousel_image_path'], 1);
						$whereArray['promotion_discount_id'] = $formData['promotion_discount_id'];
						$promotion->updatePromotionDiscount($dataArray, $whereArray);
						$this->auth->success = 2;
					}
					$this->_redirect('/admin/promotion');
				}
			} else {
				$shippingPromotionForm->populate($formData);
			}
		}
	}//end of shippingPromotionAction

	/**
	* singleProductCartPromotionAction
	*
	* Action for admin Add and Edit single product promotion discount
	**/
	public function singleProductCartPromotionAction() {
		/*if(isset($this->auth->privileges) && $this->auth->privileges!="All"){
			$privileges=unserialize($this->auth->privileges);
			if(!in_array('select-promotion-type,single-product-promotion,product-list-promotion,product-type-promotion,site-wide-promotion,cart-promotion,shipping-promotion,is-promotion-name-unique,sku-suggestions,get-metadata-options',$privileges[$this->_request->getControllerName()]) && in_array('edit,single-product-promotion,product-list-promotion,product-type-promotion,site-wide-promotion,cart-promotion,shipping-promotion,is-promotion-name-unique,sku-suggestions,get-metadata-options,select-promotion-type',$privileges[$this->_request->getControllerName()]) && !(int)$this->_request->getParam('id')) {
				$this->_redirect('/admin/error/error/');	
			}
		}*/
		//create form object
		$promotionDiscountId = (int)$this->_request->getParam('id');
		$singleProductPromotionForm = new Application_Form_AdminSingleProductCartPromotionForm($promotionDiscountId);
		$this->view->singleProductPromotionForm = $singleProductPromotionForm;
		$promotion = new Promotion();
		$promotionRecord = array();
		$whereArray = array();
		//if we will get id in the parameter then fetch row to edit
		if($promotionDiscountId) {
			$fieldArray = array("promotion_discount_id","name","code","url","banner_image_path","carousel_image_path","intro as description","DATE_FORMAT(start_time,'%m/%d/%Y %H:%i') as start_time","DATE_FORMAT(finish_time,'%m/%d/%Y %H:%i') as expiry_time","fixed_discount","percentage_discount","minimum_value as min_value","status");
			$promotionRecord = $promotion->getRecords($promotionDiscountId, $fieldArray, 1);
			$promotionRecord = $promotionRecord->toArray();
			$this->view->promotionRecord = $promotionRecord;
			$singleProductPromotionForm->populate($promotionRecord);
		}
		//assigning promotionDiscountId to view to identify Edit or Add case in View file.
		$this->view->promotionDiscountId = $promotionDiscountId;
		if($this->_request->isPost()){
			$formData = $this->_request->getPost();
			//check if form fields are valid or not.
			if($singleProductPromotionForm->isValid($formData)){
				//check if entered promotion name is unique or not
				$isUniqueName = $promotion->isPromotionNameUnique($formData['name']);
				//check if sku exists
				$productId = $promotion->isSkuExist($formData['sku']);
				//check if cart disc code is unique
				$isCartDiscUnique = $promotion->isCardDiscountCodeUnique($formData['code']);
				//assume fields are valid
				$isvalidfields = 1;
				if(($isUniqueName!=0) && ($isUniqueName!=$formData['promotion_discount_id'])) {
					$this->view->uniqueErrorMsg = PROMOTION_UNIQUE_NAME_ERROR;
					$isvalidfields = 0;
				}
				if(!$productId) {
					$this->view->validSkuErrorMsg = PROMOTION_INVALID_SKU_ERROR;
					$isvalidfields = 0;
				}
				if(($isCartDiscUnique!=0 && ($isCartDiscUnique!=$formData['promotion_discount_id'])) || $isCartDiscUnique == -1) {
					$this->view->uniqueCartDiscErrorMsg = 'Please enter unique code for cart discount';
					$isvalidfields = 0;
				}
				if(!$isvalidfields) {
					$singleProductPromotionForm->populate($formData);
				} else {
					//upload images for promotion discount.
					$imageNames = $promotion->uploadPromotionImages($formData['name'], $singleProductPromotionForm);
					$bannerImage = $imageNames['banner_image_path'];
					$carouselImage = $imageNames['carousel_image_path'];

					$url = $promotion->getPromotionDiscountUrl($formData['name']);
					$startTime =$promotion->getStandardTimeStampFormat($formData['start_time'], (!$promotionDiscountId));
					$expiryTime = $promotion->getStandardTimeStampFormat($formData['expiry_time'], (!$promotionDiscountId));
					//we have mentioned product_id =1 for now only because we don't have products yet.
					$dataArray = array(
									"name" => $formData['name'],
									"type" => 7,
									"code" => $formData['code'],
									"url" => $url,
									"banner_image_path" => $bannerImage,
									"carousel_image_path" => $carouselImage,
									"intro" => $formData['description'],
									"start_time" => $startTime,
									"finish_time" => $expiryTime,
									"fixed_discount" => (float)$formData['fixed_discount'],
									"percentage_discount" => (float)$formData['percentage_discount'],
									"product_id" => (int)$productId,
									"minimum_value" => (float)$formData['min_value'],
									"status" => $formData['status'],
									"last_updated_by" => $this->auth->user_id
								);
					if(!$formData['promotion_discount_id']) {
						$promotionDiscountId = $promotion->savePromotionDiscount($dataArray);
						$this->auth->success =1; //for showing sucess msg
					}
					else if($formData['promotion_discount_id']) {
						/*check if file uploaded or not in case of edit record. If not uploaded any new record then remove 'banner_image_path' key from data array so the it will not update its previous value*/
						if(!$bannerImage)
							unset($dataArray['banner_image_path']);
						else
							$promotion->deleteOldServerImages($promotionRecord['banner_image_path'], 2);
						if(!$carouselImage)
							unset($dataArray['carousel_image_path']);
						else
							$promotion->deleteOldServerImages($promotionRecord['carousel_image_path'], 1);
						$whereArray['promotion_discount_id'] = $formData['promotion_discount_id'];
						$promotion->updatePromotionDiscount($dataArray, $whereArray, $promotionRecord);
						$this->auth->success = 2;
					}
					$this->_redirect('/admin/promotion');
				}
			} else {
				$singleProductPromotionForm->populate($formData);
			}
		}
	}//end of singleProductPromotionAction

}//end of class Admin_DashboardController
