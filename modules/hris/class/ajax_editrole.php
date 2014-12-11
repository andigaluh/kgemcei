<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_editrole.php                //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_ROLEAJAX_DEFINED') ) {
   define('HRIS_ROLEAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");

class _hris_class_EditRoleAjax extends AjaxListener {
   
   function _hris_class_EditRoleAjax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/hris/class/ajax_editrole.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_Delete","app_editRole","app_saveRole");
   }
   
   function app_saveRole($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $role_id = $args[0];
      $arr = parseForm($args[1]);
      foreach($arr as $k=>$v) {
         $$k = addslashes(trim($v));
      }
      if($role_nm=="") {
         $role_nm = "noname";
      }
      if($role_id=="new") {
         $sql = "SELECT MAX(role_id) FROM ".XOCP_PREFIX."role";
         $result = $db->query($sql);
         list($role_idx)=$db->fetchRow($result);
         $role_id = $role_idx+1;
         $user_id = getUserID();
         $sql = "INSERT INTO ".XOCP_PREFIX."role (role_id,role_nm,created_dttm,created_user_id)"
              . " VALUES('$role_id','$role_nm',now(),'$user_id')";
         $db->query($sql);
      } else {
         $sql = "UPDATE ".XOCP_PREFIX."role SET role_nm = '$role_nm'"
              . " WHERE role_id = '$role_id'";
         $db->query($sql);
      }
      
      $ret = "<table border='0' class='ilist'>"
           . "<colgroup><col width='80'/><col/></colgroup>"
           . "<tbody><tr>"
           . "<td>$role_id</td>"
           . "<td><span id='sp_${role_id}' class='xlnk' onclick='edit_role(\"$role_id\",this,event);'>".htmlentities(stripslashes($role_nm))."</span></td>"
           . "</tr></tbody></table>";
      
      return array("tdclass_${role_id}",$ret);
   }
   
   function app_editRole($args) {
      $db=&Database::getInstance();
      $role_id = $args[0];
      if($role_id=="new") {
      } else {
         $sql = "SELECT role_nm"
              . " FROM ".XOCP_PREFIX."role"
              . " WHERE role_id = '$role_id'";
         $result = $db->query($sql);
         list($role_nm)=$db->fetchRow($result);
         $role_nm = htmlentities($role_nm,ENT_QUOTES);
      }
      $ret = "<form id='frm'><table class='xxfrm' style='width:100%;'><tbody>"
           . "<tr><td>Role Name</td><td><input type='text' value=\"$role_nm\" id='inp_role_nm' name='role_nm' style='width:90%;'/></td></tr>"
           . "<tr><td colspan='2'><input onclick='save_class();' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input onclick='cancel_edit();' type='button' value='"._CANCEL."'/>&nbsp;&nbsp;"
           . ($role_id!="new"?"<input onclick='delete_class();' type='button' value='"._DELETE."'/>":"")
           . "</td></tr>"
           . "</tbody></table></form>";
      return $ret;
   }
   
   function app_Delete($args) {
      $db=&Database::getInstance();
      $role_id = $args[0];
      $user_id = getUserID();
      ///$sql = "DELETE FROM ".XOCP_PREFIX."role WHERE role_id = '$role_id'";
      $sql = "UPDATE ".XOCP_PREFIX."role SET status_cd = 'nullified', nullified_dttm = now(), nullified_user_id = '$user_id'"
           . " WHERE role_id = '$role_id'";
      $db->query($sql);
   }
   
}

} /// HRIS_ROLEAJAX_DEFINED
?>