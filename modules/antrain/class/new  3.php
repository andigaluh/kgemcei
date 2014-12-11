<?php
//--------------------------------------------------------------------//
// Filename : modules/antrain/class/ajax_antrainsession.php                  //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-12-17                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_ASSESSMENTSESSIONAJAX_DEFINED') ) {
   define('HRIS_ASSESSMENTSESSIONAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");
require_once(XOCP_DOC_ROOT."/modules/antrain/modconsts.php");


class _antrain_class_ANTRAINSessionAjax extends AjaxListener {
   
   function __construct($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/antrain/class/ajax_antrainsession.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_Delete","app_editSession","app_saveSession",
                                             "app_setANTRAINSession","app_newSession");
   }
   
   function app_setANTRAINSession($args) {
      $_SESSION["antrain_psid"] = $args[0];
   }
   
   function app_newSession($args) {
      $db=&Database::getInstance();
      $sql = "SELECT p.psid, p.year, p.status_cd, pk.org_nm, pk.org_class_id FROM antrain_session p"
			  . " LEFT JOIN hris_orgs pk ON p.org_id = pk.org_id"
			  . " WHERE psid = ( SELECT MAX(psid) FROM antrain_session ) ORDER BY year DESC " ;
	  
      $result = $db->query($sql);
      list($psidx)=$db->fetchRow($result);
      $psid = $psidx+1;
      $user_id = getUserID();
      $year = date("Y");
      $start = getSQLDate();
      $stop = getSQLDate();
      $closing = getSQLDate();
	  $org_id = addslashes(trim($vars["org_id"]));
	  
	  $sql = "INSERT INTO antrain_session (psid,year,created_user_id,year,start_dttm,stop_dttm,closing_dttm)"
           . " VALUES('$psid','$year','$user_id','$start','$stop','$closing')";
      $db->query($sql);

	 
     $hdr = "<table><colgroup><col width='60'/><col/></colgroup><tbody><tr>"
          
           . "<td width=300><span id='sp_${psid}' class='xlnk' onclick='edit_session(\"$psid\",this,event);' >$year</span></td>"
		   . "<td id='td_org_nm_${psid}' width=275 style='text-align: left;'>$org_id</td>"
		   . "</tr></tbody></table>";
      return array($psid,$hdr);
   }
   
   function app_saveSession($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $psid = $args[0];
      $vars = parseForm($args[1]);
      $year = _bctrim(bcadd(0,$vars["year"]));
      $start = getSQLDate($vars["start_dttm"]);
      $stop = getSQLDate($vars["stop_dttm"]);
      $closing_dttm = getSQLDate($vars["closing_dttm"]);
	  $org_id = addslashes(trim($vars["org_id"]));
	  //$org_nm = addslashes(trim($vars["org_nm"]));
	  $sqlorgnm =  "SELECT pk.org_nm FROM hris_orgs pk where org_id = ".$org_id;
			 
         $resultx = $db->query($sqlorgnm);
		 list($org_nmx)=$db->fetchRow($resultx);
		 $org_nm = $org_nmx;
	  
      if($psid=="new") {
         $sql =  "SELECT p.psid, pk.org_nm FROM antrain_session p"
			  . " LEFT JOIN hris_orgs pk ON p.org_id = pk.org_id"
			  . " WHERE psid = ( SELECT MAX(psid) FROM antrain_session ) ORDER BY year DESC " ;
         $result = $db->query($sql);
         list($psidx,$org_nmx)=$db->fetchRow($result);
         $psid = $psidx+1;
         $user_id = getUserID();
		// $org_nm = $org_nmx;
         $sql = "INSERT INTO antrain_session (psid,year,created_user_id,start_dttm,stop_dttm,closing_dttm,org_id)"
              . " VALUES('$psid','$year','$user_id','$start','$stop','$closing_dttm',$org_id)";
         $db->query($sql);
      } else {
         $sql = "UPDATE antrain_session SET "
              . "year = '$year', start_dttm = '$start', stop_dttm = '$stop',"
              . "closing_dttm = '$closing_dttm' ,"
			  . "org_id = '$org_id'"
              . " WHERE psid = '$psid'";
         $db->query($sql);
		 
      }
      
      return array($psid,$year,$year,$org_nm);
   }
   
   function app_editSession($args) {
      $db=&Database::getInstance();
      $psid = $args[0];
      if($psid=="new") {
         $start = $stop = $closing = getSQLDate();
         $generate = "";
      } else {
         
         $sql = "SELECT year,start_dttm,stop_dttm,closing_dttm,org_id"
              . " FROM antrain_session"
              . " WHERE psid = '$psid'";
		
		$sql2 = "SELECT org_id,org_nm,org_class_id"
			  . " FROM hris_orgs"
			  . " WHERE org_class_id = 3 or org_class_id = 4";
         $result = $db->query($sql);
         $result2 = $db->query($sql2);
		 
       
	   list($year,$start,$stop,$closing,$org_idb)=$db->fetchRow($result);
         $year = htmlentities($year,ENT_QUOTES);
		 $org_idxx = $org_idb;
		 
	 //  list($org_id,$org_nm,$org_class_id)=$db->fetchRow($result2);
     //    $org_nm = htmlentities($org_nm,ENT_QUOTES);		
      }
      $ret = "<form id='frm'><table class='xxfrm' style='width:100%;'><colgroup><col width='160'/><col/></colgroup><tbody>"
         
           . "<tr><td>Year</td><td><input type='text' value=\"$year\" id='inp_year' name='year' style='width:50px;'/></td></tr>"
		   . "<tr><td>Div/Section</td><td><select name='org_id'>";
		    while(list($org_id,$org_nm,$org_class_id)=$db->fetchRow($result2)) {
			$selected = '';
			if($org_id == $org_idxx) {
			$selected = 'selected';
			}
			else {
			$selected = ' ';
			} 
          $ret.= "<option value=$org_id $selected>$org_nm</option>" ; } 
          //$ret.= "<option value='1'>test</option>"; } 
		 
           //. "<tr><td>Start Datetime</td><td><span class='xlnk' onclick='_changedatetime(\"start_dttm_txt\",\"start_dttm\",\"datetime\",false,false)' id='start_dttm_txt'>".sql2ind($start)."</span>"
           //. "<input type='hidden' name='start_dttm' id='start_dttm' value='$start'/></td></tr>"
           //. "<tr><td>Stop Datetime</td><td><span class='xlnk' onclick='_changedatetime(\"stop_dttm_txt\",\"stop_dttm\",\"datetime\",false,false)' id='stop_dttm_txt'>".sql2ind($stop)."</span>"
           //. "<input type='hidden' name='stop_dttm' id='stop_dttm' value='$stop'/></td></tr>"
           
           //. "<tr><td>Closing Datetime</td><td><span class='xlnk' onclick='_changedatetime(\"closing_dttm_txt\",\"closing_dttm\",\"datetime\",false,false)' id='closing_dttm_txt'>".sql2ind($closing)."</span>"
           //. "<input type='hidden' name='closing_dttm' id='closing_dttm' value='$closing'/></td></tr>"
           
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
      $sql = "UPDATE antrain_session SET status_cd = 'nullified', nullified_user_id = '$user_id', nullified_dttm = now() WHERE psid = '$psid'";
      $db->query($sql);
   }
   
}

} /// HRIS_ASSESSMENTSESSIONAJAX_DEFINED
?>