<?php
require_once ("db_connect.php");
require_once ('ticket_op_logs.grid.inc.php');
require_once ('include/xajaxGrid.inc.php');
require_once ('include/astercrm.class.php');
require_once ('include/common.class.php');
require_once ("ticket_op_logs.common.php");

/**
*  initialize page elements
*
*/

function init(){
	global $locate;

	$objResponse = new xajaxResponse();
	$objResponse->addAssign("divNav","innerHTML",common::generateManageNav($skin,$_SESSION['curuser']['country'],$_SESSION['curuser']['language']));
	$objResponse->addAssign("btnTicketCategory","value",$locate->Translate("ticketcategory_manage"));
	$objResponse->addAssign("btnTicket","value",$locate->Translate("ticket_manage"));
	$_SESSION['ParentID'] = '';
	$objResponse->addAssign("divCopyright","innerHTML",common::generateCopyright($skin));
	$objResponse->addScript("xajax_showGrid(0,".ROWSXPAGE.",'','','')");
	
	return $objResponse;
}

/**
*  show grid HTML code
*  @param	start		int			record start
*  @param	limit		int			how many records need
*  @param	filter		string		the field need to search
*  @param	content		string		the contect want to match
*  @param	divName		string		which div grid want to be put
*  @param	order		string		data order
*  @return	objResponse	object		xajax response object
*/

function showGrid($start = 0, $limit = 1,$filter = null, $content = null, $order = null, $divName = "grid", $ordering = ""){
	$html .= createGrid($start, $limit,$filter, $content, $order, $divName, $ordering);
	
	$objResponse = new xajaxResponse();
	$objResponse->addClear("msgZone", "innerHTML");
	$objResponse->addAssign($divName, "innerHTML", $html);

	return $objResponse;
}


/**
*  generate grid HTML code
*  @param	start		int			record start
*  @param	limit		int			how many records need
*  @param	filter		string		the field need to search
*  @param	content		string		the contect want to match
*  @param	divName		string		which div grid want to be put
*  @param	order		string		data order
*  @return	html		string		grid HTML code
*/

function createGrid($start = 0, $limit = 1, $filter = null, $content = null, $order = null, $divName = "grid", $ordering = "",$stype=array()){
	global $locate;
	$_SESSION['ordering'] = $ordering;
	
	if($filter == null or $content == null or $content == 'Array' or $filter == 'Array'){
		$numRows =& Customer::getNumRows();
		$arreglo =& Customer::getAllRecords($start,$limit,$order);
		$content = null;
		$filter = null;
	}else{
		foreach($content as $value){
			if(trim($value) != ""){  //搜索内容有值
				$flag = "1";
				break;
			}
		}
		foreach($filter as $value){
			if(trim($value) != ""){  //搜索条件有值
				$flag2 = "1";
				break;
			}
		}
		foreach($stype as $value){
			if(trim($value) != ""){  //搜索方式有值
				$flag3 = "1";
				break;
			}
		}
		if($flag != "1" || $flag2 != "1"){  //无值
			$numRows =& Customer::getNumRows($_SESSION['curuser']['groupid']);
			$arreglo =& Customer::getAllRecords($start,$limit,$order,$_SESSION['curuser']['groupid']);
		}elseif($flag3 != 1){
			$order = "id";
			$numRows =& Customer::getNumRowsMore($filter, $content);
			$arreglo =& Customer::getRecordsFilteredMore($start, $limit, $filter, $content, $order);
		}else{
			$order = "id";
			$numRows =& Customer::getNumRowsMorewithstype($filter, $content,$stype,$table);
			$arreglo =& Customer::getRecordsFilteredMorewithstype($start, $limit, $filter, $content, $stype,$order,$table);
		}
	}

	// Select Box: type table.
	$typeFromSearch = array();
	$typeFromSearch[] = 'like';
	$typeFromSearch[] = 'equal';
	$typeFromSearch[] = 'more';
	$typeFromSearch[] = 'less';

	// Selecct Box: Labels showed on searchtype select box.
	$typeFromSearchShowAs = array();
	$typeFromSearchShowAs[] = $locate->Translate("like");
	$typeFromSearchShowAs[] = '=';
	$typeFromSearchShowAs[] = '>';
	$typeFromSearchShowAs[] = '<';

	// Editable zone

	// Databse Table: fields
	$fields = array();
	$fields[] = 'operate';
	$fields[] = 'op_field';
	$fields[] = 'op_ori_value';
	$fields[] = 'op_new_value';
	$fields[] = 'curOwner';
	$fields[] = 'operator';
	$fields[] = 'groupname';
	$fields[] = 'optime';

	// HTML table: Headers showed
	$headers = array();
	$headers[] = $locate->Translate("ALL")."<input type='checkbox' onclick=\"ckbAllOnClick(this);\"><BR \>";//"select all for delete";
	$headers[] = $locate->Translate("Operate");
	$headers[] = $locate->Translate("Operate field");
	$headers[] = $locate->Translate("Operate Originality Value");
	$headers[] = $locate->Translate("Operate New Value");
	$headers[] = $locate->Translate("Current Owner");
	$headers[] = $locate->Translate("Operator");
	$headers[] = $locate->Translate("Groupname");
	$headers[] = $locate->Translate("Operate Time");

	// HTML table: hearders attributes
	$attribsHeader = array();
	$attribsHeader[] = 'width="5%"';
	$attribsHeader[] = 'width=""';
	$attribsHeader[] = 'width=""';
	$attribsHeader[] = 'width=""';
	$attribsHeader[] = 'width=""';
	$attribsHeader[] = 'width=""';
	$attribsHeader[] = 'width=""';
	$attribsHeader[] = 'width=""';

	// HTML Table: columns attributes
	$attribsCols = array();

	$attribsCols[] = 'style="text-align: left"';
	$attribsCols[] = 'style="text-align: left"';
	$attribsCols[] = 'style="text-align: left"';
	$attribsCols[] = 'style="text-align: left"';
	$attribsCols[] = 'style="text-align: left"';
	$attribsCols[] = 'style="text-align: left"';
	$attribsCols[] = 'style="text-align: left"';

	// HTML Table: If you want ascendent and descendent ordering, set the Header Events.
	$eventHeader = array();
	$eventHeader[]= '';
	$eventHeader[]= 'onClick=\'xajax_showGrid(0,'.$limit.',"'.$filter.'","'.$content.'","operate","'.$divName.'","ORDERING");return false;\'';
	$eventHeader[]= 'onClick=\'xajax_showGrid(0,'.$limit.',"'.$filter.'","'.$content.'","op_field","'.$divName.'","ORDERING");return false;\'';
	$eventHeader[]= 'onClick=\'xajax_showGrid(0,'.$limit.',"'.$filter.'","'.$content.'","op_ori_value","'.$divName.'","ORDERING");return false;\'';
	$eventHeader[]= 'onClick=\'xajax_showGrid(0,'.$limit.',"'.$filter.'","'.$content.'","op_new_value","'.$divName.'","ORDERING");return false;\'';
	$eventHeader[]= 'onClick=\'xajax_showGrid(0,'.$limit.',"'.$filter.'","'.$content.'","curOwner","'.$divName.'","ORDERING");return false;\'';
	$eventHeader[]= 'onClick=\'xajax_showGrid(0,'.$limit.',"'.$filter.'","'.$content.'","operator","'.$divName.'","ORDERING");return false;\'';
	$eventHeader[]= 'onClick=\'xajax_showGrid(0,'.$limit.',"'.$filter.'","'.$content.'","groupname","'.$divName.'","ORDERING");return false;\'';
	$eventHeader[]= 'onClick=\'xajax_showGrid(0,'.$limit.',"'.$filter.'","'.$content.'","optime","'.$divName.'","ORDERING");return false;\'';

	// Select Box: fields table.
	$fieldsFromSearch = array();
	$fieldsFromSearch[] = 'operate';
	$fieldsFromSearch[] = 'op_field';
	$fieldsFromSearch[] = 'op_ori_value';
	$fieldsFromSearch[] = 'op_new_value';
	$fieldsFromSearch[] = 'curOwner';
	$fieldsFromSearch[] = 'operator';
	$fieldsFromSearch[] = 'groupname';
	$fieldsFromSearch[] = 'optime';

	// Selecct Box: Labels showed on search select box.
	$fieldsFromSearchShowAs = array();
	$fieldsFromSearchShowAs[] = $locate->Translate("Operate");
	$fieldsFromSearchShowAs[] = $locate->Translate("Operate field");
	$fieldsFromSearchShowAs[] = $locate->Translate("Operate Originality Value");
	$fieldsFromSearchShowAs[] = $locate->Translate("Operate New Value");
	$fieldsFromSearchShowAs[] = $locate->Translate("Current Owner");
	$fieldsFromSearchShowAs[] = $locate->Translate("Operator");
	$fieldsFromSearchShowAs[] = $locate->Translate("Groupname");
	$fieldsFromSearchShowAs[] = $locate->Translate("Operate Time");

	// Create object whit 5 cols and all data arrays set before.
	$table = new ScrollTable(7,$start,$limit,$filter,$numRows,$content,$order);
	
	$table->exportFlag = '2';//对导出标记进行赋值
	$table->deleteFlag = '1';//对删除标记进行赋值
	$table->ordering = $ordering;

	$editFlag = 0;
	$deleteFlag = 1;
	$deleteBtnFlag = 1;
	if($_SESSION['curuser']['usertype'] != 'admin' && $_SESSION['curuser']['usertype'] != 'groupadmin') {
		if($_SESSION['curuser']['privileges']['ticket_details']['delete']) {
			$deleteFlag = 1;
			$table->deleteFlag = '1';
			$deleteBtnFlag = 1;
		} else {
			$deleteFlag = 0;
			$table->deleteFlag = '0';
			$deleteBtnFlag = 0;
		}
		if($_SESSION['curuser']['privileges']['ticket_details']['edit']) {
			$editFlag = 0;
		}else {
			$editFlag = 0;
		}
	}

	$table->setHeader('title',$headers,$attribsHeader,$eventHeader,$editFlag,$deleteFlag,0);
	$table->setAttribsCols($attribsCols);
	$table->addRowSearchMore("ticket_op_logs",$fieldsFromSearch,$fieldsFromSearchShowAs,$filter,$content,$start,$limit,1,$deleteBtnFlag,$typeFromSearch,$typeFromSearchShowAs,$stype);
	
	while ($arreglo->fetchInto($row)) {
	// Change here by the name of fields of its database table
		$rowc = array();
		$rowc[] = $row['id'];
		$rowc['select_id'] = $row['id'];
		$rowc[] = $row['operate'];
		$rowc[] = $row['op_field'];
		$rowc[] = $row['op_ori_value'];
		$rowc[] = $row['curOwner'];
		$rowc[] = $row['customer'];
		$rowc[] = $row['operator'];
		$rowc[] = $row['groupname'];
		$rowc[] = $row['optime'];
		$table->addRow("ticket_op_logs",$rowc,$editFlag,$deleteFlag,0,$divName,$fields);
 	}
 	
 	// End Editable Zone
 	
 	$html = $table->render('delGrid');
 	
 	return $html;
}


function searchFormSubmit($searchFormValue,$numRows = null,$limit = null,$id = null,$type = null){
	global $locate,$db;
	$objResponse = new xajaxResponse();
	$searchField = array();
	$searchContent = array();
	$optionFlag = $searchFormValue['optionFlag'];
	$searchContent = $searchFormValue['searchContent'];  //搜索内容 数组
	$searchField = $searchFormValue['searchField'];      //搜索条件 数组
	$searchType =  $searchFormValue['searchType'];
	$ordering = $searchFormValue['ordering'];
	$order = $searchFormValue['order'];
	$divName = "grid";
	if($optionFlag == "export"  || $optionFlag == "exportcsv"){
		$joinstr = Customer::createSqlWithStype($searchField,$searchContent,$searchType,'ticket_op_logs'); //得到要导出的sql语句
		$joinstr=ltrim($joinstr,'AND');
		$sql = "SELECT * from ticket_op_logs ";
		if($joinstr != '') $sql .= " WHERE ".$joinstr;
		$_SESSION['export_sql'] = $sql.'';
		
		$objResponse->addAssign("hidSql", "value", $sql); //赋值隐含域
		$objResponse->addAssign("maintable", "value", 'ticket_details'); //赋值隐含域
		$objResponse->addAssign("exporttype", "value", $optionFlag);
		$objResponse->addScript("document.getElementById('exportForm').submit();");
	}elseif($optionFlag == "delete"){
		astercrm::deletefromsearch($searchContent,$searchField,$searchType,'ticket_op_logs');
		$html = createGrid($searchFormValue['numRows'], $searchFormValue['limit'],'','',$order,$divName,$ordering,'');
		$objResponse->addClear("msgZone", "innerHTML");
		$objResponse->addAssign($divName, "innerHTML", $html);
	}elseif($type == "delete"){
		$res = Customer::deleteRecord($id,'ticket_op_logs');
		if ($res){
			$html = createGrid($searchFormValue['numRows'], $searchFormValue['limit'],$searchField, $searchContent, $order, $divName, $ordering,$searchType);
			$objResponse = new xajaxResponse();
			$objResponse->addAssign("msgZone", "innerHTML", $locate->Translate("delete_rec")); 
			$objResponse->addAssign($divName, "innerHTML", $html);
		}else{
			$objResponse->addAssign("msgZone", "innerHTML", $locate->Translate("rec_cannot_delete")); 
		}
	}else{
		$html = createGrid($numRows, $limit,$searchField, $searchContent, $order, $divName, $ordering,$searchType);
		$objResponse->addClear("msgZone", "innerHTML");
		$objResponse->addAssign($divName, "innerHTML", $html);
	}
	
	return $objResponse->getXML();
}

function deleteByButton($f,$searchFormValue){
	$objResponse = new xajaxResponse();
	if(is_array($f['ckb'])){
		foreach($f['ckb'] as $vaule){
			$res_customer = astercrm::deleteRecord($vaule,'ticket_op_logs');
		}
	}
	$searchContent = $searchFormValue['searchContent'];  //搜索内容 数组
	$searchField = $searchFormValue['searchField'];      //搜索条件 数组
	$numRows = $searchFormValue['numRows'];
	$limit = $searchFormValue['limit'];     
	$html = createGrid($numRows, $limit,$searchField, $searchContent, $order,'grid');
	$objResponse->addAssign('grid', "innerHTML", $html);
	return $objResponse->getXML();
}

/**
*  update ticketcategory record
*  @param	ticketid	int			ticket id
*  @return	objResponse	object		xajax response object
*/

function delete($ticketid = null){
	global $locate;		
	
	$res = Customer::deleteRecord($ticketid,'ticket_op_logs');
	if ($res){
		$html = createGrid(0,ROWSXPAGE);
		$objResponse = new xajaxResponse();
		$objResponse->addAssign("grid", "innerHTML", $html);
		$objResponse->addAssign("msgZone", "innerHTML", $locate->Translate("delete_rec")); 
	}else{
		$objResponse->addAssign("msgZone", "innerHTML", $locate->Translate("rec_cannot_delete")); 
	}
	return $objResponse->getXML();
}

$xajax->processRequests();
?>