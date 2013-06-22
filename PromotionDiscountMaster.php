<?php
/**
* @package      PromotionDiscountMaster model
* @access       Private
* @version      1.0
* @Owner        SHOCK E-COMMERCE
* @Details      This class will contain all the model functions related to promotional rules/discounts module. 
**/
class Default_Model_PromotionDiscountMaster extends Zend_Db_table
{
    protected $_name = TABLE_PROMOTION_DISCOUNT_MASTER;
	
	/**
	* getAllRecords function
	* fetch value of fields passed in the fieldArray according to options to show in the promotion grid
	*
	* @param $fieldArray Array 
	* @param $optionArray Array
	* @return all matching rows
	*/
	function getAllRecords($fieldArray=array(), $optionArray=array())
	{
		try 
		{
			$select = $this->select();
			$select->from(array('pdm'=>TABLE_PROMOTION_DISCOUNT_MASTER),$fieldArray);
			
			if(!empty($optionArray['searchColumns']) && !empty($optionArray['searchKey'])){
				$where = '';
				$first = true;
				foreach($optionArray['searchColumns'] as $searchColumn){
					if($first){
						$where.= $searchColumn." like ".$this->_db->quote("%".$optionArray['searchKey']."%");
						$first = false;
					}else{
						$where.= " OR ".$searchColumn." like ".$this->_db->quote("%".$optionArray['searchKey']."%");
					}
				}
				$select->where($where);	
			}
			
			if(!empty($optionArray['sortBy'])){
				$select->order($optionArray['sortBy']." ".$optionArray['orderBy']);
			}
			$result = $this->fetchAll($select);
			return $result;
		} catch(Exception $e) {
			$initLogger = Zend_Registry::get('initLogger');
			$initLogger->logErrors($e);
			
			$redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
			$redirector->gotoUrl('/error.php');
		}
	}

	/**
	* insert record in columns as defined in dataArray
	*
	* @return last inserted id
	*/
	function create($dataArray){
		try{
			$row = $this->insert($dataArray);
			return $row;
		}catch(Exception $e){
			//create an entry into error log
			$initLogger = Zend_Registry::get('initLogger');
			$initLogger->logErrors($e);
			$redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
			$redirector->gotoUrl('/error.php');
		}
	}//end of function create

	/**
	* update record in columns as defined in dataArray as per condtion
	*
	* @return  int if update any row.
	*/
	function updateRow($dataArray, $whereArray){
		$where='';
		foreach($whereArray as $columnName=>$value){
			$where.= $columnName."=".$this->_db->quote($value);
		}
		try{
			$result = $this->update($dataArray,$where);
			return $result;
		}catch(Exception $e){
			//create an entry into error log
			$initLogger = Zend_Registry::get('initLogger');
			$initLogger->logErrors($e);
			$redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
			$redirector->gotoUrl('/error.php');
		}
	}//end of function update

	/**
	* delete record as per condtion
	* @param Array
	* @return  int if deleted any row.
	*/
	function deleteRow($whereArray){
		$where='';
		foreach($whereArray as $columnName=>$value){
			$where.= $columnName."=".$this->_db->quote($value);
		}
		try{
			$result = $this->delete($where);
			return $result;
		}catch(Exception $e){
			//create an entry into error log
			$initLogger = Zend_Registry::get('initLogger');
			$initLogger->logErrors($e);
			$redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
			$redirector->gotoUrl('/error.php');
		}
	}//end of function deleteRow

	/**
	* getRecord function
	* fetch value of fields passed in the fieldArray as per where condition
	*
	* @param $whereArray Array 
	* @param $fieldArray Array
	* @return Array : all matching rows
	*/
	function getRecord($whereArray = array(),$fieldArray = array(),$type=0) {
		$where='';
		foreach($whereArray as $columnName=>$value){
			$where.= $columnName."=".$this->_db->quote($value);
		}
		try {
			$select = $this->select()
					->from(array('pdm'=>TABLE_PROMOTION_DISCOUNT_MASTER),$fieldArray);
			if($type ==1) {
				$select->setIntegrityCheck(false)
						->joinleft(array('PM' => TABLE_PRODUCT_MASTER),'pdm.product_id = PM.product_id',array('sku'));
			}
			$select->where($where);
			$result = $this->fetchRow($select);
			return $result;
		} catch(Exception $e) {
			//create an entry into error log
			$initLogger = Zend_Registry::get('initLogger');
			$initLogger->logErrors($e);
			$redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
			$redirector->gotoUrl('/error.php');
		}
	}

	/**
	* getAllActivePromotions function
	* fetch all the active promotions
	*
	* @param $fieldArray Array
	* @param $currentTime String
	* @param $flag Int
	* @return Array : all matching rows
	*/
	function getAllActivePromotions($fieldArray = array(), $currentTime, $flag = 1) {
		$where = "start_time <= ".$this->_db->quote($currentTime)." and finish_time>=".$this->_db->quote($currentTime)." and status = 1";
		if($flag == 1){
			$where .= ' and type IN (1,2,3,4)';
		} else if($flag == 2) {
			$where .= ' and type = 4 and is_gift_vouchers_included = 1';
		}
		try {
			$select = $this->select()
			->from(array('PDM'=>TABLE_PROMOTION_DISCOUNT_MASTER),$fieldArray);
			$select->where($where);
			$result = $this->fetchAll($select);
			return $result;
		} catch(Exception $e) {
			//create an entry into error log
			$initLogger = Zend_Registry::get('initLogger');
			$initLogger->logErrors($e,0);
		}
	}

	/**
	* validateCartPromotion function
	* check if cart discount is valid
	*
	* @param $fieldArray Array
	* @param $currentTime String
	* @param $whereCode String
	* @return Array : matching row
	*/
	function validateCartPromotion($fieldArray = array(), $currentTime, $whereCode = array()) {
		$where = "start_time <= ".$this->_db->quote($currentTime)." and finish_time>=".$this->_db->quote($currentTime)." and status = 1 and type IN(5, 7) and code = ".$this->_db->quote($whereCode['code']);
		try {
			$select = $this->select()
			->from(array('PDM'=>TABLE_PROMOTION_DISCOUNT_MASTER),$fieldArray);
			$select->where($where);
			$result = $this->fetchRow($select);
			return $result;
		} catch(Exception $e) {
			//create an entry into error log
			$initLogger = Zend_Registry::get('initLogger');
			$initLogger->logErrors($e,0);
		}
	}

	/**
	* getInactivePromotionRecord function
	* fetch record as per id if it is not active
	*
	* @param $fieldArray Array
	* @param $whereArray Array
	* @return Array
	*/
	function getInactivePromotionRecord($fieldArray = array(), $whereArray) {
		$where = "(".$this->_db->quote($whereArray['currentTime'])." NOT between PDM.start_time and PDM.finish_time) AND promotion_discount_id=".$whereArray['promotion_discount_id']. " AND PDM.finish_time < ".$this->_db->quote($whereArray['currentTime']);
		try {
			$select = $this->select()
			->from(array('PDM'=>TABLE_PROMOTION_DISCOUNT_MASTER),$fieldArray);
			$select->where($where);
			$result = $this->fetchRow($select);
			return $result;
		} catch(Exception $e) {
			//create an entry into error log
			$initLogger = Zend_Registry::get('initLogger');
			$initLogger->logErrors($e,0);
		}
	}

	/**
	* checkCurrentPromotionStatus function
	* function will track if any promotion strats or ends
	*
	* @param $fieldArray Array
	* @return Array : all matching rows
	*/
	function checkCurrentPromotionStatus($fieldArray = array('*'), $endTime, $startTime) {
		$where = "(PDM.start_time between ".$this->_db->quote($startTime)." and ".$this->_db->quote($endTime).") OR (PDM.finish_time between ".$this->_db->quote($startTime)." and ".$this->_db->quote($endTime).") and PDM.status = 1 and PDM.type IN (1,2,3,4)";
		try {
			$select = $this->select()
					->from(array('PDM'=>TABLE_PROMOTION_DISCOUNT_MASTER),$fieldArray);
			$select->where($where);
			$result = $this->fetchAll($select);
			return $result;
		} catch(Exception $e) {
			//create an entry into error log
			$initLogger = Zend_Registry::get('initLogger');
			$initLogger->logErrors($e,0);
		}
	}
}
