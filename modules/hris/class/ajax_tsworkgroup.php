<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_tsworkgroup.php                //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_TSWORKGROUPAJAX_DEFINED') ) {
   define('HRIS_TSWORKGROUPAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");

class _hris_class_TSWorkGroupAjax extends AjaxListener {
   
   function _hris_class_TSWorkGroupAjax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/hris/class/ajax_tsworkgroup.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_Delete","app_editWorkGroup","app_saveWorkGroup");
   }
   
   function app_saveWorkGroup($args) {
      $db=&Database::getInstance();
      $ts_group_id = $args[0];
      $vars = _parseForm($args[1]);
      
      if($ts_group_id=="new") {
         $sql = "SELECT MAX(ts_group_id) FROM ".XOCP_PREFIX."ts_group";
         $result = $db->query($sql);
         list($ts_group_idx)=$db->fetchRow($result);
         $ts_group_id = $ts_group_idx+1;
         $user_id = getUserID();
         $sql = "INSERT INTO ".XOCP_PREFIX."ts_group (ts_group_id,ts_group_nm,ts_group_abbr)"
              . " VALUES('$ts_group_id','".addslashes($vars["ts_group_nm"])."','".addslashes($vars["ts_group_abbr"])."')";
         $db->query($sql);
      }
      
      $sql = "UPDATE ".XOCP_PREFIX."ts_group SET "
           . "ts_group_nm = '".addslashes($vars["ts_group_nm"])."',"
           . "ts_group_abbr = '".addslashes($vars["ts_group_abbr"])."',"
           . "description = '".addslashes($vars["description"])."'"
           . " WHERE ts_group_id = '$ts_group_id'";
      $db->query($sql);
      
      $ret = "<table border='0' class='ilist'>"
           . "<colgroup><col width='40'/><col/></colgroup>"
           . "<tbody><tr>"
           . "<td>".$vars["ts_group_abbr"]."</td>"
           . "<td><span id='sp_${ts_group_id}' class='xlnk' onclick='edit_tsgroup(\"$ts_group_id\",this,event);'>".htmlentities($vars["ts_group_nm"])."</span></td>"
           . "</tr></tbody></table>";
      return array("tdtsgroup_${ts_group_id}",$ret);
   }
   
   function app_editWorkGroup($args) {
      $db=&Database::getInstance();
      $ts_group_id = $args[0];
      if($ts_group_id=="new") {
      } else {
         $sql = "SELECT description,ts_group_nm,ts_group_abbr"
              . " FROM ".XOCP_PREFIX."ts_group"
              . " WHERE ts_group_id = '$ts_group_id'";
         $result = $db->query($sql);
         list($desc,$ts_group_nm,$ts_group_abbr)=$db->fetchRow($result);
         $ts_group_nm = htmlentities($ts_group_nm,ENT_QUOTES);
         $ts_group_abbr = htmlentities($ts_group_abbr,ENT_QUOTES);
         $desc = htmlentities($desc,ENT_QUOTES);
      }
      
      $ret = "<form id='frm'><table class='xxfrm'><tbody>"
           . "<tr><td>Work Group Name</td><td><input type='text' value=\"$ts_group_nm\" id='inp_tsgroup_nm' name='ts_group_nm' style='width:400px;'/></td></tr>"
           . "<tr><td>Abbreviation</td><td><input type='text' value=\"$ts_group_abbr\" id='inp_tsgroup_abbr' name='ts_group_abbr' style='width:70px;'/></td></tr>"
           
           . "<tr><td>Description</td><td><textarea id='description' name='description' style='width:400px;'>$desc</textarea></td></tr>"
           . "<tr><td colspan='2'><input onclick='save_tsgroup();' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input onclick='cancel_edit();' type='button' value='"._CANCEL."'/>&nbsp;&nbsp;"
           . ($ts_group_id!="new"?"<input onclick='delete_tsgroup();' type='button' value='"._DELETE."'/>":"")
           . "</td></tr>"
           . "</tbody></table></form>";
      return $ret;
   }
   
   function app_Delete($args) {
      $db=&Database::getInstance();
      $ts_group_id = $args[0];
      $sql = "DELETE FROM ".XOCP_PREFIX."ts_group WHERE ts_group_id = '$ts_group_id'";
      $db->query($sql);
   }
   
}

} /// HRIS_TSWORKGROUPAJAX_DEFINED
?>