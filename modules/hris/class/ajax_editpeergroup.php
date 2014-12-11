<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_editpeergroup.php                //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_PEERGROUPAJAX_DEFINED') ) {
   define('HRIS_PEERGROUPAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");

class _hris_class_EditPeerGroupAjax extends AjaxListener {
   
   function _hris_class_EditPeerGroupAjax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/hris/class/ajax_editpeergroup.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_Delete","app_editGroup","app_saveGroup");
   }
   
   function app_saveGroup($args) {
      $db=&Database::getInstance();
      $peer_group_id = $args[0];
      $arr = parseForm($args[1]);
      foreach($arr as $k=>$v) {
         $$k = addslashes(trim($v));
      }
      if($peer_group_nm=="") {
         $peer_group_nm = "noname";
      }
      if($peer_group_id=="new") {
         $sql = "SELECT MAX(peer_group_id) FROM ".XOCP_PREFIX."peer_group";
         $result = $db->query($sql);
         list($peer_group_idx)=$db->fetchRow($result);
         $peer_group_id = $peer_group_idx+1;
         $user_id = getUserID();
         $sql = "INSERT INTO ".XOCP_PREFIX."peer_group (peer_group_id,peer_group_nm)"
              . " VALUES('$peer_group_id','$peer_group_nm')";
         $db->query($sql);
      } else {
         $sql = "UPDATE ".XOCP_PREFIX."peer_group SET peer_group_nm = '$peer_group_nm'"
              . " WHERE peer_group_id = '$peer_group_id'";
         $db->query($sql);
      }
      
      $ret = "<table border='0' class='ilist'>"
           . "<colgroup><col width='80'/><col/></colgroup>"
           . "<tbody><tr>"
           . "<td>$peer_group_id</td>"
           . "<td><span id='sp_${peer_group_id}' class='xlnk' onclick='edit_group(\"$peer_group_id\",this,event);'>".htmlentities(stripslashes($peer_group_nm))."</span></td>"
           . "</tr></tbody></table>";
      
      return array("tdclass_${peer_group_id}",$ret);
   }
   
   function app_editGroup($args) {
      $db=&Database::getInstance();
      $peer_group_id = $args[0];
      if($peer_group_id=="new") {
      } else {
         $sql = "SELECT peer_group_nm"
              . " FROM ".XOCP_PREFIX."peer_group"
              . " WHERE peer_group_id = '$peer_group_id'";
         $result = $db->query($sql);
         list($peer_group_nm)=$db->fetchRow($result);
         $peer_group_nm = htmlentities($peer_group_nm,ENT_QUOTES);
      }
      $ret = "<form id='frm'><table class='xxfrm' style='width:100%;'><tbody>"
           . "<tr><td>Peer Group Name</td><td><input type='text' value=\"$peer_group_nm\" id='inp_peer_group_nm' name='peer_group_nm' style='width:90%;'/></td></tr>"
           . "<tr><td colspan='2'><input onclick='save_class();' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input onclick='cancel_edit();' type='button' value='"._CANCEL."'/>&nbsp;&nbsp;"
           . ($peer_group_id!="new"?"<input onclick='delete_class();' type='button' value='"._DELETE."'/>":"")
           . "</td></tr>"
           . "</tbody></table></form>";
      return $ret;
   }
   
   function app_Delete($args) {
      $db=&Database::getInstance();
      $peer_group_id = $args[0];
      $sql = "DELETE FROM ".XOCP_PREFIX."peer_group WHERE peer_group_id = '$peer_group_id'";
      $db->query($sql);
   }
   
}

} /// HRIS_PEERGROUPAJAX_DEFINED
?>