<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_orgclass.php                    //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_ORGCLASSAJAX_DEFINED') ) {
   define('HRIS_ORGCLASSAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");

class _hris_class_OrgClassAjax extends AjaxListener {
   
   function _hris_class_OrgClassAjax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/hris/class/ajax_orgclass.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_Delete","app_editClass","app_saveClass");
   }
   
   function app_saveClass($args) {
      $db=&Database::getInstance();
      $org_class_id = $args[0];
      $org_class_nm = addslashes(trim(urldecode($args[1])));
      $description = addslashes(trim(urldecode($args[2])));
      $order_no = _bctrim(bcadd(0,$args[3]));
      if($org_class_nm=="") {
         $org_class_nm = "noname";
      }
      if($org_class_id=="new") {
         $sql = "SELECT MAX(org_class_id) FROM ".XOCP_PREFIX."org_class";
         $result = $db->query($sql);
         list($org_class_idx)=$db->fetchRow($result);
         $org_class_id = $org_class_idx+1;
         $user_id = getUserID();
         $sql = "INSERT INTO ".XOCP_PREFIX."org_class (org_class_id,org_class_nm,description,created_user_id,order_no)"
              . " VALUES('$org_class_id','$org_class_nm','$description','$user_id','$order_no')";
         $db->query($sql);
      } else {
         $sql = "UPDATE ".XOCP_PREFIX."org_class SET org_class_nm = '$org_class_nm', description = '$description',"
              . "order_no = '$order_no'"
              . " WHERE org_class_id = '$org_class_id'";
         $db->query($sql);
      }
      $ret = "<span style='padding-right:8px;'>$order_no</span>&nbsp;<span id='sp_${org_class_id}' class='xlnk' onclick='edit_class(\"$org_class_id\",this,event);'>".htmlentities(stripslashes($org_class_nm))."</span>";

      return array("tdclass_${org_class_id}",$ret);
   }
   
   function app_editClass($args) {
      $db=&Database::getInstance();
      $org_class_id = $args[0];
      if($org_class_id=="new") {
      } else {
         $sql = "SELECT description,org_class_nm,order_no FROM ".XOCP_PREFIX."org_class"
              . " WHERE org_class_id = '$org_class_id'";
         $result = $db->query($sql);
         list($desc,$org_class_nm,$order_no)=$db->fetchRow($result);
         $org_class_nm = htmlentities($org_class_nm,ENT_QUOTES);
         $desc = htmlentities($desc,ENT_QUOTES);
      }
      $ret = "<form id='frm'><table class='xxfrm' style='width:100%;'><tbody>"
           . "<tr><td>Class Name</td><td><input type='text' value=\"$org_class_nm\" id='inp_org_class_nm' name='org_class_nm' style='width:90%;'/></td></tr>"
           . "<tr><td>Description</td><td><textarea id='description' style='width:50%;'>$desc</textarea></td></tr>"
           . "<tr><td>Level Order</td><td><input type='text' value=\"$order_no\" id='inp_order_no' name='order_no' style='width:30px;'/></td></tr>"
           . "<tr><td colspan='2'><input onclick='save_class();' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input onclick='cancel_edit();' type='button' value='"._CANCEL."'/>&nbsp;&nbsp;"
           . ($org_class_id!="new"?"<input onclick='delete_class();' type='button' value='"._DELETE."'/>":"")
           . "</td></tr>"
           . "</tbody></table></form>";
      return $ret;
   }
   
   function app_Delete($args) {
      $db=&Database::getInstance();
      $org_class_id = $args[0];
      $sql = "DELETE FROM ".XOCP_PREFIX."org_class WHERE org_class_id = '$org_class_id'";
      $db->query($sql);
   }
   
}

} /// HRIS_ORGCLASSAJAX_DEFINED
?>