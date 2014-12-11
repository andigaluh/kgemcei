<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_editcalendarevent.php        //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2011-06-15                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_CALENDAREVENTAJAX_DEFINED') ) {
   define('HRIS_CALENDAREVENTAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");

class _hris_class_EditCalendarEventAjax extends AjaxListener {
   
   function __construct($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/hris/class/ajax_editcalendarevent.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_Delete","app_editFlag","app_saveFlag","app_previousYear",
                                             "app_nextYear","app_currentYear");
   }
   
   function app_currentYear($args) {
      $db=&Database::getInstance();
      $_SESSION["cal_event_year"] = date("Y");
      return uniqid("u");
   }
   
   function app_nextYear($args) {
      $db=&Database::getInstance();
      $_SESSION["cal_event_year"]++;
      return uniqid("u");
   }
   
   function app_previousYear($args) {
      $db=&Database::getInstance();
      $_SESSION["cal_event_year"]--;
      return uniqid("u");
   }
   
   function app_saveFlag($args) {
      $db=&Database::getInstance();
      $flag_type = $args[0];
      $arr = _parseForm($args[1]);
      foreach($arr as $k=>$v) {
         $$k = addslashes(trim($v));
      }
      if($flag_type_nm=="") {
         $flag_type_nm = "noname";
      }
      if($flag_type=="new") {
         $sql = "SELECT MAX(flag_type) FROM ".XOCP_PREFIX."calendar_flag_type";
         $result = $db->query($sql);
         list($flag_typex)=$db->fetchRow($result);
         $flag_type = $flag_typex+1;
         $user_id = getUserID();
         $sql = "INSERT INTO ".XOCP_PREFIX."calendar_flag_type (flag_type,flag_type_nm,leave_ind,deduct_leave)"
              . " VALUES('$flag_type','$flag_type_nm','$leave_ind','$deduct_leave')";
         $db->query($sql);
      } else {
         $sql = "UPDATE ".XOCP_PREFIX."calendar_flag_type SET flag_type_nm = '$flag_type_nm', leave_ind = '$leave_ind', deduct_leave = '$deduct_leave'"
              . " WHERE flag_type = '$flag_type'";
         $db->query($sql);
      }
      
      $ret = "<table border='0' class='ilist'>"
           . "<colgroup><col width='80'/><col/></colgroup>"
           . "<tbody><tr>"
           . "<td>$flag_type</td>"
           . "<td><span id='sp_${flag_type}' class='xlnk' onclick='edit_flag(\"$flag_type\",this,event);'>".htmlentities(stripslashes($flag_type_nm))."</span></td>"
           . "</tr></tbody></table>";
      
      return array("tdclass_${flag_type}",$ret);
   }
   
   function app_editFlag($args) {
      $db=&Database::getInstance();
      $flag_type = $args[0];
      if($flag_type=="new") {
         $flag_type_desc = "";
         $leave_ind = 0;
         $deduct_leave = 0;
      } else {
         $sql = "SELECT flag_type_nm,flag_type_desc,leave_ind,deduct_leave"
              . " FROM ".XOCP_PREFIX."calendar_flag_type"
              . " WHERE flag_type = '$flag_type'";
         $result = $db->query($sql);
         list($flag_type_nm,$flag_type_desc,$leave_ind,$deduct_leave)=$db->fetchRow($result);
         $flag_type_nm = htmlentities($flag_type_nm,ENT_QUOTES);
      }
      $ret = "<div id='frm'><table class='xxfrm' style='width:100%;'><tbody>"
           
           . "<tr><td>Flag Type Name</td><td><input type='text' value=\"$flag_type_nm\" id='inp_flag_type_nm' name='flag_type_nm' style='width:90%;'/></td></tr>"
           
           . "<tr><td>Holiday/Leave</td><td>"
               . "<input type='radio' name='leave_ind' id='leave_ind_1' value='1' ".($leave_ind==1?"checked='1'":"")."/> <label for='leave_ind_1' class='xlnk'>Yes</label>&nbsp;&nbsp;"
               . "<input type='radio' name='leave_ind' id='leave_ind_0' value='0' ".($leave_ind!=1?"checked='1'":"")."/> <label for='leave_ind_0' class='xlnk'>No</label>"
           . "</td></tr>"
           
           . "<tr><td>Deduct Leave</td><td>"
               . "<input type='radio' name='deduct_leave' id='deduct_leave_1' value='1' ".($deduct_leave==1?"checked='1'":"")."/> <label for='deduct_leave_1' class='xlnk'>Yes</label>&nbsp;&nbsp;"
               . "<input type='radio' name='deduct_leave' id='deduct_leave_0' value='0' ".($deduct_leave!=1?"checked='1'":"")."/> <label for='deduct_leave_0' class='xlnk'>No</label>"
           . "</td></tr>"
           
           . "<tr><td colspan='2'><input onclick='save_class();' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input onclick='cancel_edit();' type='button' value='"._CANCEL."'/>&nbsp;&nbsp;"
           . ($flag_type!="new"?"<input onclick='delete_class();' type='button' value='"._DELETE."'/>":"")
           . "</td></tr>"
           . "</tbody></table></div>";
      return $ret;
   }
   
   function app_Delete($args) {
      $db=&Database::getInstance();
      $flag_type = $args[0];
      $sql = "DELETE FROM ".XOCP_PREFIX."calendar_flag_type WHERE flag_type = '$flag_type'";
      $db->query($sql);
   }
   
}

} /// HRIS_CALENDAREVENTAJAX_DEFINED
?>