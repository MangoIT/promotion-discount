<?php
/**
* @package      Promotion class
* @access       Private
* @version      1.0
* @Owner        Anylinuxwork
* @Details      This class will contain all the functions related to promotional rules/discounts. this class is
*				extending discount class
**/
class Promotion extends Discount {

	protected $_options;
	protected $_promotionDiscountMasterObj;
	protected $_keyField; #primary key in user_master table

	/**
	* Constructor will set protected variables of class
	*/
	public function __construct(){
		$this->_promotionDiscountMasterObj = new Default_Model_PromotionDiscountMaster();
		$this->_keyField = 'promotion_discount_id';
	}

	/**
	* setOptions function
	*
	* @param Array
	* This function will set search, sort and page parameters to the class variable via passing array from
	* promotionController.
	*/
	public function setOptions($options){
		$options['searchColumns'] = $this->getSearchColumns();
		$this->_options = $options;
	}

	/**
	* getSearchColumns function
	* @return Array
	* function will retutnsArray of columns on which searching is to be applied in promotion grid
	*/
	public function getSearchColumns() {
		$searchColumns = array();
		foreach($this->fieldsConfig() as $columnConfig){
			if($columnConfig['searching']){
				$searchColumns[] = $columnConfig['fieldName'];
			}
		}
		return $searchColumns;
	}

	/**
	* fieldsConfig function
	* @return Array
	* configuration array of fields for admin promotion grid
	*/
	public function fieldsConfig() {
		$fieldsConfigArray = array(
			array(
				'fieldName'=>'promotion_discount_id',
				'label'=>'',
				'visible'=>0,
				'sorting'=>0,
				'searching'=>0,
				'functionName'=>'',
			),
			array(
				'fieldName'=>'name',
				'label'=>'Name',
				'visible'=>1,
				'sorting'=>1,
				'searching'=>1,
				'functionName'=>'',
			),
			array(
				'fieldName'=>'type',
				'label'=>'Promotion Type',
				'visible'=>1,
				'sorting'=>1,
				'searching'=>1,
				'functionName'=>'showPromotionTypeHtml',
			),
			array(
				'fieldName'=>'start_time',
				'label'=>'Start Time',
				'visible'=>1,
				'sorting'=>1,
				'searching'=>0,
				'functionName'=>'showPromotionTimeHtml',
			),
			array(
				'fieldName'=>'finish_time',
				'label'=>'Expiry Time',
				'visible'=>1,
				'sorting'=>1,
				'searching'=>0,
				'functionName'=>'showPromotionTimeHtml',
			),
			array(
				'fieldName'=>'fixed_discount',
				'label'=>'Fixed Discount (in USD)',
				'visible'=>1,
				'sorting'=>1,
				'searching'=>1,
				'functionName'=>'showFormattedDiscount',
			),
			array(
				'fieldName'=>'percentage_discount',
				'label'=>'Percentage Discount (in %)',
				'visible'=>1,
				'sorting'=>1,
				'searching'=>1,
				'functionName'=>'',
			),
			array(
				'fieldName'=>'status',
				'label'=>'Status',
				'visible'=>1,
				'sorting'=>1,
				'searching'=>0,
				'functionName'=>'showStatusHtml',
			),
			array(
				'fieldName'=>'last_modified',
				'label'=>'Last Modified',
				'visible'=>1,
				'sorting'=>1,
				'searching'=>0,
				'functionName'=>'showPromotionTimeHtml',
			)
		);
		return $fieldsConfigArray;
	}

	/**
	* getFieldsArray function
	* @return Array
	* generate fields array to be fetched from promotion_discount_master to use in promotion grid
	*/
	public function getFieldsArray(){
		$fieldsArray = array();
		foreach($this->fieldsConfig() as $columnConfig){
			$fieldsArray[] = $columnConfig['fieldName'];
		}
		return $fieldsArray;
	}

	/**
	* getVisibleFields function
	* @return Array
	* generate visible fields array to be shown as column in promotion grid
	*/
	public function getVisibleFields(){
		foreach($this->fieldsConfig() as $columnConfig){
			if($columnConfig['visible']){
				$visibleFields[$columnConfig['fieldName']] = $columnConfig['label'];
			}
		}
		return $visibleFields;
	}

	/**
	* getSortColumns function
	* @return Array
	* retutnsArray of columns on which sorting is to be applied in admin promotion grid
	*/
	public function getSortColumns() {
		$sortColumns = array();
		foreach($this->fieldsConfig() as $columnConfig){
			if($columnConfig['sorting']){
				$sortColumns[] = $columnConfig['fieldName'];
			}
		}
		return $sortColumns;
	}
	
	/**
	* getActions function
	* @return Array
	* actions to show in admin promotion grid
	*/
	public function getActions() {
		$actions = array(
			'View'=>array(
				'href'=>'admin/promotion/view/id/',
				'class'=>'grid-links'
			),
			'Edit'=>array(
				'href'=>'admin/promotion/edit/id/',
				'class'=>'grid-links'
			),
			'Delete'=>array(
				'href'=>'admin/promotion/delete/id/',
				'onclick'=>'confirmDeletion(this);',
				'class'=>'grid-links'
			)
		);
		return $actions;
	}

	/**
	* getAssociatedFunctions function
	* @return Array
	* actions to show in admin promotion grid
	*/
	public function getAssociatedFunctions() {
		$functionColumns = array();
		foreach($this->fieldsConfig() as $columnConfig){
			if($columnConfig['functionName']!=''){
				$functionColumns[$columnConfig['fieldName']] = $columnConfig['functionName'];
			}
		}
		return $functionColumns;
	}
	

	/**
	* getRecords function
	*
	* @param $promotionDiscountId int : optional param to fetch record according to id
	* @return Array
	* function will fetch records from database.
	*/

	public function getRecords($promotionDiscountId='',$fieldArray = array(),$type=0) {
		if($promotionDiscountId=='')
		{
			$dataArray = $this->_promotionDiscountMasterObj->getAllRecords($this->getFieldsArray(),$this->_options);
			
			$returnArray = array(
				'keyField'=>$this->_keyField,
				'visibleFields'=>$this->getVisibleFields(),
				'dataArray'=>$dataArray,
				'sortColumns'=>$this->getSortColumns(),
				'actions'=>$this->getActions(),
				'functions'=>$this->getAssociatedFunctions());
		}else{
			#Columns to fetch from database
			//if $fieldArray not defined then fetch following columns
			if(!count($fieldArray))
				$fieldArray = array("promotion_discount_id","name","type","code","url","banner_image_path","carousel_image_path","intro","DATE_FORMAT(start_time,'%m/%d/%Y %H:%i') as start_time","DATE_FORMAT(finish_time,'%m/%d/%Y %H:%i') as finish_time","fixed_discount","percentage_discount","product_list_ids","product_id","minimum_value","is_gift_vouchers_included","status","last_modified");
			
			$whereArray = array('promotion_discount_id'=>$promotionDiscountId);
			$returnArray = $this->_promotionDiscountMasterObj->getRecord($whereArray, $fieldArray,$type);
		}
		return $returnArray;
	}
	
	/**
	* getInactivePromotionRecord function
	*
	* @param $promotionDiscountId int : param to fetch record according to id
	* @param $fieldArray Array : fields to fetch
	* @return Array
	* function will fetch records from database.
	*/

	public function getInactivePromotionRecord($promotionDiscountId='',$fieldArray = array()) {
		$whereArray['currentTime'] = $this->getStandardTimeStampFormat();
		$whereArray['promotion_discount_id'] = $promotionDiscountId;
		$returnArray = $this->_promotionDiscountMasterObj->getInactivePromotionRecord($fieldArray, $whereArray);
		return $returnArray;
	}
	
	/**
	* getPromotionTypes function
	*
	* @return Array
	* function will array for different types of promotion discounts.
	* types are: 1 =Single product, 2=Product List, 3=Meta-Data, 4=Site Wide, 5=Cart Discount, 6=Shipping Discount
	*/

	public function getPromotionTypes(){
		$typeArray = array( "1" => SINGLE_PRODUCT_DISCOUNT,
							"2" => PRODUCT_LIST_DISCOUNT,
							"3" => PRODUCT_TYPE_DISCOUNT,
							"4" => SITE_WIDE_DISCOUNT,
							"5" => CART_DISCOUNT,
							"6" => SHIPPING_DISCOUNT,
							"7" => SINGLE_PRODUCT_CART_DISCOUNT);
		return $typeArray;
	}

	/**
	* getSkuSuggestions function
	*
	* @param String : search string to match sku
	* @return Array
	* function will search for sku from product_entity table and return array for matching SKUs.
	*/

	public function getSkuSuggestions($skuString){
		//call model of product_entity model and search for provided sku
		//currently implemented with static sku because we do not have live products
		
		$product = new Product();
		$whereArray['value'] =  $skuString;
		$fieldArray= array('sku','product_id');
		$skuArray = array();
		$attributes = new Attributes();
		$textAttr = array(ATTR_NAME);
		$tagAttr = array();
		$attributesResult = $attributes->getAttributeIds($textAttr, $tagAttr);
		$result = $product->getProductSkuSuggestionsData($whereArray,$fieldArray,$attributesResult['textAttr']);		
		return $result;
		
		/*$product = new Product();
		$whereArray['sku'] =  $skuString;
		$fieldArray= array('sku');
		$skuArray = array();
		$result = $product->getProductSkuSuggestions($whereArray , $fieldArray);
		if(count($result)) {
			foreach($result as $skus) {
				$skuArray[] = $skus->sku;
			}
		}
		return $skuArray;*/
	}

	/**
	* getOptionsSuggestions function
	*
	* @param String : search string to match tagString
	* @return Array
	* function will search for attribute values from tag_group_values table and return array for matching values.
	*/

	public function getOptionsSuggestions($tagString, $attributeId=0){
		//call model of product_entity model and search for provided tag
		$attributeMaster = new Default_Model_AttributeMaster();
		$whereArray['attribute_id'] =  $attributeId;
		$whereArray['value'] =  $tagString;
		$fieldArray= array();
		$fieldArray['attribute_master'] = array("attribute_id");
		$fieldArray['tag_group_values'] = array("id","value");
		$result = $attributeMaster->getAtributeOptionsForPromotionRule($fieldArray, $whereArray);
		return $result;
	}

	/**
	* getPromotionDiscountUrl function
	*
	* @param String : promotion name
	* @return String
	* function will generate url for created promotion according to name of the promotion.
	*/

	public function getPromotionDiscountUrl($name){
		//we will perform routing for identifying this url in frontend
		$product = new Product();
		$url = $product->getSeoFriendlyUrl(strtolower($name));
		if(!$url)
			$url = time();
		return 'promotion-'.$url;
	}

	/**
	* getStandardTimeStampFormat function
	*
	* @param String : pass string with any date and time format
	* @return String
	* function will generate standard time stamp to store in the database in format "YYYY-MM-dd HH:mm:ss".
	*/
	public function getStandardTimeStampFormat($dateTime='', $checkDate=0) {
		global $locale;
		if($dateTime!='') {
			$dateTime = strtotime($dateTime);
			if($checkDate && ($dateTime < time())) {
				$dateTime = time();
			}
		}
		else
		$dateTime = time();
		$date = new Zend_Date($dateTime, false, $locale);
		return $date->toString("YYYY-MM-dd HH:mm:ss");
	}

	/**
	* savePromotionDiscount function
	*
	* @param Array : array to store values into the database
	* @return int : new created promotionDiscountId
	* function will call model to store value in the database.
	*/

	public function savePromotionDiscount($dataArray = array()){
		//add last_modified field for all the promotions
		//$dataArray['last_modified'] = $this->getStandardTimeStampFormat();
		//call object of model and pass dataArray to it.
		return $promotionId = $this->_promotionDiscountMasterObj->create($dataArray);
	}

	/**
	* saveMetaDataOptions function
	*
	* @param Array : array to store values into the database
	* @return int : new created promotionDiscountId
	* function will call model to store value in the database.
	*/

	public function saveMetaDataOptions($formData = array(), $newPromotionDiscountId){
		//extract options and its values to create array for prepared statement
		$optionsArray = array(); $optionForCache = array();
		foreach($formData['attributes'] as $attributeId) {
			$valueIndex = 'value'.$attributeId;
			$optionForCache[$attributeId] = $formData[$valueIndex];
			$valuesArray = array_unique($formData[$valueIndex]);
			foreach($valuesArray as $selectedVal) {
				$optionsArray[] = $newPromotionDiscountId;
				$optionsArray[] = $attributeId;
				$optionsArray[] = $selectedVal;
			}
		}
		//call object of model and pass dataArray to it.
		$discountMetaDataOptions = new Default_Model_DiscountMetaDataOptions();
		//values will be used in prepares statement query.
		$values = implode(',',array_fill(0,(count($optionsArray)/3),'(?, ?, ?)'));
		$discountMetaDataOptions->create($values, $optionsArray);
		return $optionForCache;
	}
	
	/**
	* getMetaDataRecords function
	*
	* @param $promotionDiscountId int :  param to fetch record according to id
	* @return Array
	* function will fetch records from database.
	*/

	public function getMetaDataRecords($promotionDiscountId='') {
		$discountMetaDataOptions = new Default_Model_DiscountMetaDataOptions();
		#Columns to fetch from database
		$fieldArray = array(); $resultSet = array();
		$fieldArray['attribute_master'] = array("attribute_name");
		$fieldArray['discount_metadata_options'] = array("attribute_id","value_id");
		$fieldArray['tag_group_values'] = array("value");
		$whereArray = array('promotion_discount_id'=>$promotionDiscountId);
		$returnArray = $discountMetaDataOptions->getAllRecords($whereArray, $fieldArray);
		$resultSet['attributes'] = array();
		if(count($returnArray)) {
			foreach($returnArray as $valArray) {
				$resultSet['attributes'][] = $valArray->attribute_id;
				$resultSet['label'][$valArray->attribute_id] = $valArray->attribute_name;
				$resultSet['value'.$valArray->attribute_id]['value'][] = $valArray->value;
				$resultSet['value'.$valArray->attribute_id]['id'][] = $valArray->value_id;
			}
		}
		$resultSet['attributes'] = array_unique($resultSet['attributes']);
		return $resultSet;
	}

	/**
	* updatePromotionDiscount function
	*
	* @param Array : array to store values into the database
	* @param Array : whereArray array to specify condition to update record in the database
	* @return int : return 1 if updated any of the record
	* function will call model to update record in the database as per condition.
	*/

	public function updatePromotionDiscount($dataArray = array(), $whereArray = array(), $promotionInfo = array()){
		//add last_modified field for all the promotions
		$dataArray['last_modified'] = $this->getStandardTimeStampFormat();
		//check if promotion is currently active then change global cache key
		$this->updateCacheOnPromotionUpdate($dataArray, $promotionInfo);
		//call object of model and pass dataArray to it.
		return $promotionId = $this->_promotionDiscountMasterObj->updateRow($dataArray,$whereArray);
	}

	/**
	* updateCacheOnPromotionUpdate function
	*
	* @param Array 
	* @param Array 
	* function will call model to update record in the database as per condition.
	*/

	public function updateCacheOnPromotionUpdate($dataArray = array(), $promotionInfo = array()){
		//check if promotion is currently active then change global cache key
		if(in_array($dataArray['type'], array(1, 2, 3, 4))) {
			$currentTime = strtotime($this->getStandardTimeStampFormat());
			if((strtotime($promotionInfo['start_time'])<= $currentTime) && (strtotime($promotionInfo['expiry_time']) >= $currentTime)) {
				$this->updateGlobalCacheKey();
			}
		}
	}

	/**
	* updateGlobalCacheKey function
	*
	* function will update global cache key to invalidate cache.
	*/

	public function updateGlobalCacheKey(){
		$strandsRecommendations = new StrandsRecommendations();
		$strandsRecommendations->updateGlobalCacheKey();
	}

	/**
	* deletePromotionDiscount function
	*
	* @param Array : whereArray array to specify condition to delete record from the database
	* @return int : return 1 if deleted any of the record
	* function will call model to delete record from the database as per condition.
	*/

	public function deletePromotionDiscount($whereArray = array()){
		//call object of model and pass dataArray to it.
		return $promotionId = $this->_promotionDiscountMasterObj->deleteRow($whereArray);
	}

	/**
	* deleteMetaDataOptions function
	*
	* @param Array : whereArray array to specify condition to delete record from the database
	* @return int : return 1 if deleted any of the record
	* function will call model of discount meta data options table to delete record from the database.
	*/

	public function deleteMetaDataOptions($whereArray = array()){
		//call object of model and pass whereArray to it.
		$discountMetaDataOptions = new Default_Model_DiscountMetaDataOptions();
		return $result = $discountMetaDataOptions->deleteRow($whereArray);
	}

	/**
	* isPromotionNameUnique function
	*
	* @param String : name of promotion to check whether it is unique or not
	* @return int
	* function will check that given name is unique or not. If not unique then return id of promotion discount which
	* was assigned to the given name value
	*/

	public function isPromotionNameUnique($name){
		$whereArray['name'] = $name;
		$fieldArray = array('promotion_discount_id');
		//create object of model and pass Array to it.
		$resultRow = $this->_promotionDiscountMasterObj->getRecord($whereArray,$fieldArray);
		//if name is not unique then pass the id of promotion_dicount which is assigned the given 'name' value
		if(count($resultRow))
			return $resultRow->promotion_discount_id;
		else
			return 0;
	}

	/**
	* isSkuExist function
	*
	* @param String : name of promotion to check whether it is unique or not
	* @return Boolean
	* function will check that given sku is valid and exist in database or not
	*/

	public function isSkuExist($sku){
		$whereArray['sku'] = $sku;
		$fieldArray = array('product_id');
		//create object of model and pass Array to it.
		$product = new Product();
		$result = $product->getProductRecord($whereArray , $fieldArray);
		if(count($result)) {
			$attributes = new Attributes();
			$textAttr = array(ATTR_NAME, ATTR_IMAGE, ATTR_NO_OF_DISCS);
			$tagAttr = array(ATTR_FORMAT);
			$attributesResult = $attributes->getAttributeIds($textAttr, $tagAttr);
			$productResult = $product->getAllproductsAttribute(array($result->product_id), $attributesResult['textAttr'], $attributesResult['tagAttr']);
			if(count($productResult))
				return $result->product_id;
			else
				return 0;
		}
		else
			return 0;
	}

	public function isCardDiscountCodeUnique($code) {
		$whereArray['code'] = $code;
		$fieldArray = array('promotion_discount_id');
		//create object of model and pass Array to it.
		$resultRow = $this->_promotionDiscountMasterObj->getRecord($whereArray,$fieldArray);
		$voucherMaster = new Admin_Model_VoucherMaster();
		$voucherRow = $voucherMaster->getVoucherInfo($whereArray, array('voucher_id'));
		if(!sizeof($resultRow) && !sizeof($voucherRow))
			return 0;
		else if(sizeof($resultRow))
			return $resultRow->promotion_discount_id;
		else
			return -1;
	}

	/**
	* viewRecord function
	*
	* @param Int : promotion discount Id
	* @return Array : result set from database
	* function will call getRecords function to fetch specific record from database
	*/
	public function viewRecord($promotion_discount_id) {
		return $this->getRecords($promotion_discount_id,array(),1);
	}

	/**
	* showPromotionTypeHtml function
	*
	* @param int : type id of promotion type.
	* @return String
	* function will return label for promotion discount as per given types. this is used in grid
	*/
	public function showPromotionTypeHtml($typeId) {
		$typesArray = $this->getPromotionTypes();
		return $typesArray[$typeId];
	}

	/**
	* showPromotionTimeHtml function
	*
	* @param string : start_time/expiry_time
	* @return String
	* function will change format of time.
	*/
	public function showPromotionTimeHtml($dateTime='') {
		global $locale;
		if($dateTime!='')
			$dateTime = strtotime($dateTime);
		else
			$dateTime = time();
		$date = new Zend_Date($dateTime, false, $locale);
		return $date->toString("MM/dd/YYYY HH:mm");
	}

	/**
	* showFormattedDiscount function
	*
	* @param string : fixed discount
	* @return String
	* function will change format of time.
	*/
	public function showFormattedDiscount($fixedDiscount) {
		return Zend_Registry::get('Zend_Currency')->getSymbol().$fixedDiscount;
	}
	
	

	/**
	* showStatusHtml function
	*
	* @param Boolean
	* @return String
	* function will return text for status of promotion discount as per given values
	*/
	public function showStatusHtml($status) {
		return (int)$status?"Enabled":"Disabled";
	}

	/**
	* getProductList function
	*
	* @return Array
	* function will return array of record set of product list
	*/
	public function getProductList() {
		//create object of product class
		$product = new Product();
		//create fieldArray to fetch values
		$fieldArray = array("list_id","name");
		return $product->getProductList(false,$fieldArray);
	}

	/**
	* getProductAttribute function
	*
	* @return Array
	* function will return array of record set of product list
	*/
	public function getProductAttribute() {
		//create object of attribute_master model
		$attributeMaster = new Default_Model_AttributeMaster();
		//create fieldArray to fetch values
		$fieldArray = array(); $resultSet = array();
		$fieldArray = array("attribute_id","attribute_name");
		//where condition if 'is_applied_promotion_rules' = 1 then fetch that attribute
		$whereArray['is_applied_promotion_rules'] = 1;
		$resultSet = $attributeMaster->getAllRecords($whereArray, $fieldArray);
		return $resultSet;
	}//end of function

	/**
	* getAllActivePromotions function
	* function will fetch all active promotion discount and return array
	*
	*/
	public function getAllActivePromotions($fieldArray=array(), $flag = 1) {
		$currentTime	= $this->getStandardTimeStampFormat();
		if(!empty($fieldArray)){
			$dataArray	= $this->_promotionDiscountMasterObj->getAllActivePromotions($fieldArray,$currentTime,$flag);
		}
		return $dataArray;
	}
	
	/**
	* validateCartPromotion function
	* @param $code String
	* @return Array Discount data
	*
	* function will check if given promotion code is active then it will return data for promotion.
	*/
	public function validateCartPromotion($code = '') {
		$currentTime = $this->getStandardTimeStampFormat();
		$fieldArray = array("promotion_discount_id","product_id", "name", "type", "finish_time", "fixed_discount", "percentage_discount", "minimum_value");
		$whereCode['code'] = $code;
		$dataArray	= $this->_promotionDiscountMasterObj->validateCartPromotion($fieldArray,$currentTime,$whereCode);
		return $dataArray;
	}

	/**
	* checkPromotionStatus function
	* function will fetch all active promotion discount and return array
	*
	*/
	public function checkPromotionStatus($endTime, $startTime) {
		$fieldArray = array("promotion_discount_id");
		$result	= $this->_promotionDiscountMasterObj->checkCurrentPromotionStatus($fieldArray, $endTime, $startTime);
		return $result;
	}

	/**
	* checkMetaDetaOptionUsed function
	* function will check if given attribute is used by any of the active or non started promotion. This function is used when admin edits any attribute. [AttributesController]
	*
	*/
	public function checkMetaDetaOptionUsed($attributeId) {
		$discountMetaDataOptions = new Default_Model_DiscountMetaDataOptions();
		$whereArray['currentTime'] = $this->getStandardTimeStampFormat();
		$whereArray['attribute_id'] = $attributeId;
		$fieldArray = array("promotion_discount_id", "name");
		$result	= $discountMetaDataOptions->checkMetaDetaOptionUsed($fieldArray, $whereArray)->toArray();
		$promotionsResult ='';
		if(count($result)) {
			foreach($result as $promotionsResult) {
				$tempArr[] = $promotionsResult['name'];
			}
			$promotionsResult = implode(', ',$tempArr);
		}
		return $promotionsResult;
	}

	/**
	* uploadPromotionImages function
	* @param String $promotionName 
	* @parma Object $formObject
	* function will upload banner image and carousel images for promotoin rules
	*
	*/
	public function uploadPromotionImages($promotionName, $formObject) {
		//loop twice to upload two images banner_image amd carousel_image
		$imageNames = array();
		$upload = new Zend_File_Transfer_Adapter_Http();
		$i=1;
		foreach ($upload->getFileInfo() as $files) {
			if($files['name'] <> '') {
				$product = new Product();
				$promotionName = $product->getSeoFriendlyUrl(strtolower($promotionName));
				if(!$promotionName)
					$promotionName = time();
				if($i == 1) {
					$uploadDestination = Zend_Registry::get('relativePathFull').PROMOTION_CAROUSEL_IMAGE_UPLOAD_PATH;
					$originalFilename = pathinfo($formObject->carousel_image->getFileName(null,false));
					$newFilename = $promotionName . '-carouselimage-' . uniqid() . '.' . $originalFilename['extension'];
					$upload->addFilter('Rename',array('target' => $uploadDestination.$newFilename,'overwrite'=> true));
					$imageNames['carousel_image_path'] = $newFilename;
				} else if($i == 2) {
					$uploadDestination = Zend_Registry::get('relativePathFull').PROMOTION_BANNER_IMAGE_UPLOAD_PATH;
					$originalFilename = pathinfo($formObject->banner_image->getFileName(null,false));
					$newFilename = $promotionName . '-bannerimage-' . uniqid() . '.' . $originalFilename['extension'];
					$upload->addFilter('Rename',array('target' => $uploadDestination.$newFilename,'overwrite'=> true));
					$imageNames['banner_image_path'] = $newFilename;
				}
				//if uploaded file then upload it to server
				if(!$upload->receive($files['name'])){
					//create an entry into error log
					$initLogger = Zend_Registry::get('initLogger');
					$initLogger->logErrors($upload,1);
				}
			} else {
				if($i == 1)
					$imageNames['carousel_image_path'] = '';
				else if($i == 2)					
					$imageNames['banner_image_path'] = '';
			}
			$i++;
		}//end of for loop
		return $imageNames;
	}

	/**
	* deleteOldServerImages function
	* @param String $promotionName 
	* @parma String $type
	* function will unlink old uploaded images
	*
	*/
	public function deleteOldServerImages($imageName = '', $type) {
		//1= carousel image, 2 = banner image
		$path = '';
		if($type == 1) {
			$path = Zend_Registry::get('relativePathFull').PROMOTION_CAROUSEL_IMAGE_UPLOAD_PATH;
		} else if($type == 2) {
			$path = Zend_Registry::get('relativePathFull').PROMOTION_BANNER_IMAGE_UPLOAD_PATH;
		}
		$validate = new Zend_Validate_File_Exists();
		$validate->setDirectory($path);
		if($validate->isValid($imageName)) {
			unlink($path.$imageName);
		}
	}
	
	/**
	* getVoucherDiscount function
	* @return Array
	* function will calculate discount for a gift voucher
	*
	*/
	public function getVoucherDiscount($amount = 0) {
		$result = 0;
		$fieldArray = array("promotion_discount_id", "name", "type", "fixed_discount", "percentage_discount", "minimum_value","is_gift_vouchers_included");
		$promotionRecords = $this->getAllActivePromotions($fieldArray, 2);
		if(sizeof($promotionRecords)) {
			//if amount is specified then calculate the discount.
			if($amount) {
				$discountArray = array(); $promotionInfo = array();
				foreach($promotionRecords as $promotion) {
					if($amount >= $promotion->minimum_value) {
						$price = $discountArray[] = $amount - ($amount * ($promotion->percentage_discount/100)) - $promotion->fixed_discount;
						$promotionInfo['promotion_discount_id'][$price] = $promotion->promotion_discount_id;
					}
				}//end of foreach
				if(count($discountArray)) {
					sort($discountArray);
					$discountedPrice = $discountArray[0];
					$promotionDiscountId = $promotionInfo['promotion_discount_id'][$discountedPrice];
					$result = round($discountedPrice, 2).','.$promotionDiscountId;
				}//end of if
			} else
				$result = 1;
		} //end of if
		return $result;
	}
	public function getAllProductCartDiscountIds() {
		$currentTime	= $this->getStandardTimeStampFormat();
		$result = array();
		$fieldArray = array("promotion_discount_id","type");
		$dataArray	= $this->_promotionDiscountMasterObj->getAllActivePromotions($fieldArray,$currentTime,3);
		if(sizeof($dataArray)) {
			foreach($dataArray as $data) {
				if($data->type == 7)
				$result[] = $data->promotion_discount_id;
			}
		}
		return $result;		
	}
}
