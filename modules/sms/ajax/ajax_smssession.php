<?php
//--------------------------------------------------------------------//
// Filename : modules/pms/class/ajax_pmssession.php                  //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-12-17                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_SMSSESSIONAJAX_DEFINED') ) {
   define('HRIS_SMSSESSIONAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");
require_once(XOCP_DOC_ROOT."/modules/sms/modconsts.php");


class _sms_class_SMSSessionAjax extends AjaxListener {
   
   function __construct($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/sms/class/ajax_smssession.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_Delete","app_editSession","app_saveSession",
                                             "app_setSMSSession","app_newSession");
   }
   
   function app_setSMSSession($args) {
      $_SESSION["sms_psid"] = $args[0];
   }
   
   function app_newSession($args) {
      $db=&Database::getInstance();
      $sql = "SELECT MAX(psid) FROM sms_session";
      $result = $db->query($sql);
      list($psidx)=$db->fetchRow($result);
      $psid = $psidx+1;
      $user_id = getUserID();
      $session_periode = date("Y");
      $start = getSQLDate();
      $stop = getSQLDate();
      $closing = getSQLDate();
      $sql = "INSERT INTO sms_session (psid,session_nm,created_user_id,session_periode,start_dttm,stop_dttm,closing_dttm)"
           . " VALUES('$psid','$session_nm','$user_id','$session_periode','$start','$stop','$closing')";
      $db->query($sql);
      
      $hdr = "<table><colgroup><col width='60'/><col/></colgroup><tbody><tr>"
           . "<td id='td_periode_${psid}'>$session_periode</td>"
           . "<td><span id='sp_${psid}' class='xlnk' onclick='edit_session(\"$psid\",this,event);'>"._EMPTY."</span></td>"
           . "</tr></tbody></table>";
      return array($psid,$hdr);
   }
   
   function app_saveSession($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $psid = $args[0];
      $vars = parseForm($args[1]);
      $session_nm = addslashes(trim($vars["session_nm"]));
      $session_periode = _bctrim(bcadd(0,$vars["session_periode"]));
      $start = getSQLDate($vars["start_dttm"]);
      $stop = getSQLDate($vars["stop_dttm"]);
      $closing_dttm = getSQLDate($vars["closing_dttm"]);
      if($session_nm=="") {
         $session_nm = "noname";
      }
      if($psid=="new") {
         $sql = "SELECT MAX(psid) FROM sms_session";
         $result = $db->query($sql);
         list($psidx)=$db->fetchRow($result);
         $psid = $psidx+1;
         $user_id = getUserID();
         $sql = "INSERT INTO sms_session (psid,session_nm,created_user_id,session_periode,start_dttm,stop_dttm,closing_dttm)"
              . " VALUES('$psid','$session_nm','$user_id','$session_periode','$start','$stop','$closing_dttm')";
         $db->query($sql);
      } else {
         $sql = "UPDATE sms_session SET session_nm = '$session_nm',"
              . "session_periode = '$session_periode', start_dttm = '$start', stop_dttm = '$stop',"
              . "closing_dttm = '$closing_dttm'"
              . " WHERE psid = '$psid'";
         $db->query($sql);
      }
      
      return array($psid,$session_nm,$session_periode);
   }
   
   function app_editSession($args) {
      $db=&Database::getInstance();
      $psid = $args[0];
      if($psid=="new") {
         $start = $stop = $closing = getSQLDate();
         $generate = "";
      } else {
         
         $sql = "SELECT session_nm,session_periode,start_dttm,stop_dttm,closing_dttm"
              . " FROM sms_session"
              . " WHERE psid = '$psid'";
         $result = $db->query($sql);
         
         list($session_nm,$session_periode,$start,$stop,$closing)=$db->fetchRow($result);
         $session_nm = htmlentities($session_nm,ENT_QUOTES);
      }
      $ret = "<form id='frm'><table class='xxfrm' style='width:100%;'><colgroup><col width='160'/><col/></colgroup><tbody>"
           . "<tr><td>Session Name</td><td><input type='text' value=\"$session_nm\" id='inp_session_nm' name='session_nm' style='width:90%;'/></td></tr>"
           . "<tr><td>Year</td><td><input type='text' value=\"$session_periode\" id='inp_session_periode' name='session_periode' style='width:50px;'/></td></tr>"
           . "<tr><td>Start Datetime</td><td><span class='xlnk' onclick='_changedatetime(\"start_dttm_txt\",\"start_dttm\",\"datetime\",false,false)' id='start_dttm_txt'>".sql2ind($start)."</span>"
           . "<input type='hidden' name='start_dttm' id='start_dttm' value='$start'/></td></tr>"
           . "<tr><td>Stop Datetime</td><td><span class='xlnk' onclick='_changedatetime(\"stop_dttm_txt\",\"stop_dttm\",\"datetime\",false,false)' id='stop_dttm_txt'>".sql2ind($stop)."</span>"
           . "<input type='hidden' name='stop_dttm' id='stop_dttm' value='$stop'/></td></tr>"
           
           . "<tr><td>Closing Datetime</td><td><span class='xlnk' onclick='_changedatetime(\"closing_dttm_txt\",\"closing_dttm\",\"datetime\",false,false)' id='closing_dttm_txt'>".sql2ind($closing)."</span>"
           . "<input type='hidden' name='closing_dttm' id='closing_dttm' value='$closing'/></td></tr>"
           
           . "<tr><td colspan='2'><span id='progress'></span>&nbsp;"
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
      $sql = "UPDATE sms_session SET status_cd = 'nullified', nullified_user_id = '$user_id', nullified_dttm = now() WHERE psid = '$psid'";
      $db->query($sql);
   }
   
}

} /// HRIS_ASSESSMENTSESSIONAJAX_DEFINED
?>