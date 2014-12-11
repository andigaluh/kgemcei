<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_editcompgroup.php              //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_COMPGROUPAJAX_DEFINED') ) {
   define('HRIS_COMPGROUPAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");

class _hris_class_EditCompetencyGroupAjax extends AjaxListener {
   
   function _hris_class_EditCompetencyGroupAjax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/hris/class/ajax_editcompgroup.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_Delete","app_editCompetencyGroup","app_saveCompetencyGroup");
   }
   
   function app_saveCompetencyGroup($args) {
      $db=&Database::getInstance();
      $compgroup_id = $args[0];
      $compgroup_nm = addslashes(trim(urldecode($args[1])));
      $description = addslashes(trim(urldecode($args[2])));
      $compgroup_cd = $args[3];
      if($compgroup_nm=="") {
         $compgroup_nm = "noname";
      }
      if($compgroup_id=="new") {
         $sql = "SELECT MAX(compgroup_id) FROM ".XOCP_PREFIX."compgroup";
         $result = $db->query($sql);
         list($compgroup_idx)=$db->fetchRow($result);
         $compgroup_id = $compgroup_idx+1;
         $user_id = getUserID();
         $sql = "INSERT INTO ".XOCP_PREFIX."compgroup (compgroup_id,compgroup_nm,description,created_user_id)"
              . " VALUES('$compgroup_id','$compgroup_nm','$description','$user_id')";
         $db->query($sql);
         _debuglog($sql);
      } else {
         $sql = "UPDATE ".XOCP_PREFIX."compgroup SET compgroup_nm = '$compgroup_nm', description = '$description'"
              . " WHERE compgroup_id = '$compgroup_id'";
         $db->query($sql);
      }

      $sql = "SELECT a.compgroup_id,a.compgroup_nm,a.description"
           . " FROM ".XOCP_PREFIX."compgroup a"
           . " WHERE a.compgroup_id = '$compgroup_id'";
      
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($compgroup_id,$compgroup_nm,$description,$org_nm,$org_class_nm)=$db->fetchRow($result);
      } else {
         return "EMPTY";
      }
      $ret = "<table style='border:0px;width:100%;'>"
                  . "<tr>"
                      . "<td><span id='sp_${compgroup_id}' class='xlnk' onclick='edit_compgroup(\"$compgroup_id\",this,event);'>".htmlentities(stripslashes($compgroup_nm))."</span></td>"
                  . "</tr></tbody></table>";
      
      return array("dvcompgroup_${compgroup_id}",$ret);
   }
   
   function app_editCompetencyGroup($args) {
      $db=&Database::getInstance();
      $compgroup_id = $args[0];
      if($compgroup_id=="new") {
      } else {
         $sql = "SELECT description,compgroup_nm"
              . " FROM ".XOCP_PREFIX."compgroup"
              . " WHERE compgroup_id = '$compgroup_id'";
         $result = $db->query($sql);
         list($desc,$compgroup_nm)=$db->fetchRow($result);
         $compgroup_nm = htmlentities($compgroup_nm,ENT_QUOTES);
         $desc = htmlentities($desc,ENT_QUOTES);
      }
      
      $ret = "<form id='frm'><table class='xxfrm' style='width:100%;'><tbody>"
           . "<tr><td>Competency Group</td><td><input type='text' value=\"$compgroup_nm\" id='inp_compgroup_nm' name='compgroup_nm' style='width:90%;'/></td></tr>"
           . "<tr><td>Description</td><td><textarea id='description' style='width:50%;'>$desc</textarea></td></tr>"
           . "<tr><td colspan='2'><input onclick='save_compgroup();' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input onclick='cancel_edit();' type='button' value='"._CANCEL."'/>&nbsp;&nbsp;"
           . ($compgroup_id!="new"?"<input onclick='delete_compgroup();' type='button' value='"._DELETE."'/>":"")
           . "</td></tr>"
           . "</tbody></table></form>";
      return $ret;
   }
   
   function app_Delete($args) {
      $db=&Database::getInstance();
      $compgroup_id = $args[0];
      $sql = "DELETE FROM ".XOCP_PREFIX."compgroup WHERE compgroup_id = '$compgroup_id'";
      $db->query($sql);
   }
   
}

} /// HRIS_COMPGROUPAJAX_DEFINED
?>