<?php
//--------------------------------------------------------------------//
// Filename : modules/antrain/class/ajax_antrainsession.php                  //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-12-17                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_ASSESSMENTSESSIONSSAJAX_DEFINED') ) {
   define('HRIS_ASSESSMENTSESSIONSSAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");
require_once(XOCP_DOC_ROOT."/modules/antrain/modconsts.php");


class _antrain_class_ANTRAINSessionSSAjax extends AjaxListener {
   
   function __construct($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/antrain/class/ajax_antrainsessionss.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_Delete","app_editSession","app_saveSession",
                                             "app_setANTRAINSessionss","app_newSession");
   }
   
   function app_setANTRAINReqsession($args) {
      $_SESSION["antrain_psid"] = $args[0];
   }
   
   function app_newSession($args) {
      $db=&Database::getInstance();
      // $sql = "SELECT MAX(psid) FROM antrain_sessionss ORDER BY year DESC " ;
	  $sql = "SELECT p.psid, p.year, p.budget, p.remark, p.status_cd, p.org_id, pk.org_nm, pk.org_class_id FROM antrain_sessionss p"
			  . " LEFT JOIN hris_orgs pk ON p.org_id = pk.org_id"
			  . " WHERE psid = ( SELECT MAX(psid) FROM antrain_sessionss ) ORDER BY year DESC " ;
     $result = $db->query($sql);
      list($psidx,$year,$budget,$remark,$status_cd,$org_id)=$db->fetchRow($result);
      $psid = $psidx+1;
      $year = date("Y");
	  $budget = addslashes(trim($vars["budget"]));
	  $remark = addslashes(trim($vars["remark"]));	  
	  $org_id = addslashes(trim($vars["org_id"]));
	  $id_created = getUserID();
	  $date_created = getSQLDate();
	  $sql = "INSERT INTO antrain_sessionss (psid,year,budget,remark,id_created,date_created,org_id)"
           . " VALUES('$psid','$year','$budget','$remark','$id_created','$date_created','$org_id')";
      $db->query($sql);
	
     $hdr = "<table><colgroup><col width='60'/><col/></colgroup><tbody><tr>"
			  . "<td width=70><span id='sp_${psid}' class='xlnk' onclick='edit_session(\"$psid\",this,event);' >$year</span></td>"
			  . "<td id='td_org_nm_${psid}' width=125 style='text-align: left;'>$org_nm</td>"
			  . "<td id='td_remark_${psid}' width=200 style='text-align: left;'>$remark</td>"
			  . "</tr></tbody></table>";
      return array($psid,$hdr);
   }
   
   function app_saveSession($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $psid = $args[0];
      $vars = parseForm($args[1]);
      $year = _bctrim(bcadd(0,$vars["year"]));
	  $budget = _bctrim(bcadd(0,$vars["budget"]));
	  $org_id = _bctrim(bcadd(0,$vars["org_id"]));
	  $org_id_sec = _bctrim(bcadd(0,$vars["org_id_sec"]));
	  $remark = addslashes(trim($vars["remark"]));
	  $id_created = getUserID();
	  $id_modified = getUserID();
	  $date_created = getSQLDate($vars["date_created"]);
	  $date_modified =  getSQLDate($vars["date_modified"]);
	
	  //$org_nm = addslashes(trim($vars["org_nm"]));
	  
      if($psid=="new") {
 
		$sql = "SELECT p.psid, p.year, p.budget, p.remark, p.status_cd, p.date_created, p.date_modified, pk.org_nm, pk.org_class_id FROM antrain_sessionss p"
			  . " LEFT JOIN hris_orgs pk ON p.org_id = pk.org_id"
			  . " WHERE psid = ( SELECT MAX(psid) FROM antrain_sessionss ) ORDER BY year DESC " ;
	         
	     //$sql =  "SELECT MAX(psid) FROM antrain_sessionss ORDER BY year DESC " ;
         $result = $db->query($sql);
         list($psidx)=$db->fetchRow($result);
         $psid = $psidx+1;
         // $user_id = getUserID();
		 // $date_created = getSQLDate();
		// $org_nm = $org_nmx;
         $sql = "INSERT INTO antrain_sessionss (psid,year,budget,remark,id_created,date_created)"
              . " VALUES('$psid','$year','$budget','$remark','$id_created','$date_created')";
         $db->query($sql);
      } else {
         $sql = "UPDATE antrain_sessionss SET "
              . "year = '$year', budget = '$budget', org_id = '$org_id',org_id_sec = '$org_id_sec' , remark = '$remark', id_modified = '$id_modified', date_modified = '$date_modified'"			  
              . " WHERE psid = '$psid'";
         $db->query($sql);
		 
      $sqlnm = "SELECT org_nm FROM hris_orgs WHERE org_id= '$org_id'";
	  $resultnm = $db->query($sqlnm);
	  list($org_nm)=$db->fetchRow($resultnm);
	  
	  $sqlnmsec = "SELECT org_nm FROM hris_orgs WHERE org_id= '$org_id_sec'";
	  $resultnmsec = $db->query($sqlnmsec);
	  list($org_nm_sec)=$db->fetchRow($resultnmsec);
	  
	  }
      
	  
	  
      return array($psid,$year,$org_nm,$remark,$org_nm_sec);
   }
   
   function app_editSession($args) {
      $db=&Database::getInstance();
      $psid = $args[0];
      if($psid=="new") {
         $date_created  = getSQLDate();
		 $id_created = getUserID();
         $generate = "";
      } else {
        $id_modified = getUserID();
		$date_modified  = getSQLDate();
/* 	$sql = "SELECT year,budget,remark,date_created"
             . " FROM antrain_sessionss"
             . " WHERE psid = '$psid'"; */
		  $sql =  "SELECT p.year, p.budget, p.remark,p.org_id, pk.org_nm FROM antrain_sessionss p"
			  . " LEFT JOIN hris_orgs pk ON p.org_id = pk.org_id"
			  . " WHERE psid = '$psid' ORDER BY year DESC " ;
	
         $result = $db->query($sql);
    	 
       
	   list($year,$budget,$remark,$org_id,$org_nm)=$db->fetchRow($result);
        $year = htmlentities($year,ENT_QUOTES);
		
	$sqldd = "SELECT org_id, org_nm FROM hris_orgs WHERE org_class_id =  '3'";
	$resultdd = $db->query($sqldd);
	
		
	$sqlds = "SELECT org_id, org_nm FROM hris_orgs WHERE org_class_id =  '4'";
	$resultds = $db->query($sqlds);
		
	 //  list($org_id,$org_nm,$org_class_id)=$db->fetchRow($result2);
     //    $org_nm = htmlentities($org_nm,ENT_QUOTES);		
      }
	  
	  $sqlselected = "SELECT  org_id,org_id_sec FROM antrain_sessionss WHERE psid = $psid";
	  $resultselect = $db->query($sqlselected);
	  list($sel_org,$sel_org_sec)=$db->fetchRow($resultselect);
	  
      $ret = "<form id='frm'><table class='xxfrm' style='width:100%;'><colgroup><col width='160'/><col/></colgroup><tbody>"
         
           . "<tr><td>Year</td><td><input type='text' value=\"$year\" id='inp_year' name='year' style='width:50px;'/></td></tr>" 
		   . "<tr><td>Budget</td><td><input type='text' value=\"$budget\" id='inp_budget' name='budget' style='width:50px;'/></td></tr>" 
		   . "<tr><td>Division</td><td><select id='inp_org_id' name='org_id'>";
	
	while(list($org_id,$org_nm)=$db->fetchRow($resultdd)){
		
		if($org_id == $sel_org )
		{
			$selected1 = 'selected';
		}
		else
		{
			$selected1 = ''; 
		}
		$ret .= "<option value=\"$org_id\" $selected1>$org_nm</option>";
	
	}
	
		$ret .= "</select></td></tr>" 
	
		. "<tr><td>Section</td><td><select id='inp_org_id_sec' name='org_id_sec'>";

	while(list($org_id_sec,$org_nm_sec)=$db->fetchRow($resultds)){
		
			 
		if($org_id_sec == $sel_org_sec )
		{
			$selected2= 'selected';
		}
		else
		{
			$selected2 = ''; 
		}
	
	
	
		$ret .= "<option value=\"$org_id_sec\" $selected2>$org_nm_sec</option>";
	
	}
		   
	
		$ret .= "</select></td></tr>" 
		   . "<tr><td>Remark</td><td><input type='text' value=\"$remark\" id='inp_remark' name='remark' style='width:95%;'/></td></tr>"; 
          
		$ret .= "<tr><td colspan='2'><span id='progress'></span>&nbsp;"
           . "<input id='btn_save_session' onclick='save_session();' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input id='btn_cancel_edit' onclick='cancel_edit();' type='button' value='"._CANCEL."'/>&nbsp;&nbsp;"
           . ($psid!="new"?"<input id='btn_delete_session' onclick='delete_session();' type='button' value='"._DELETE."'/>":"")
           . "</td></tr>"
           . "</tbody></table></form>";
      return $ret;
   }
   
   function app_Delete($args) {
      $db=&Database::getInstance();
      $psid = $args[0];
      $user_id = getUserID();
      $sql = "UPDATE antrain_sessionss SET status_cd = 'nullified', nullified_user_id = '$user_id' WHERE psid = '$psid'";
      $db->query($sql);
   }
   
}

} /// HRIS_ASSESSMENTSESSIONAJAX_DEFINED
?>