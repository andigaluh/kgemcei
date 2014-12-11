<?php
//--------------------------------------------------------------------//
// Filename : modules/sms/class/ajax_smsrefperspektive.php            //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2013-10-16                                              //                                                                    //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_SMSREFPERSPEKTIVEAJAX_DEFINED') ) {
   define('HRIS_SMSREFPERSPEKTIVEAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");
require_once(XOCP_DOC_ROOT."/modules/sms/modconsts.php");


class _sms_class_SMSRefPerspektiveAjax extends AjaxListener {
   
   function __construct($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/sms/class/ajax_smsrefperspektive.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_Delete","app_editRefPerspektive","app_saveRefPerspektive",
                                             "app_setSMSRefPerspektive","app_newRefPerspektive");
   }
   
   function app_setSMSRefPerspektive($args) {
      $_SESSION["sms_id"] = $args[0];
   }
   
   function app_newRefPerspektive($args) {
      $db=&Database::getInstance();
      $sql = "SELECT MAX(id) FROM sms_ref_perspektive";
      $result = $db->query($sql);
      list($idx)=$db->fetchRow($result);
      $id = $idx+1;
      $create_user_id = getUserID();
      $create_date = getSQLDate();
      $sql = "INSERT INTO sms_ref_perspektive (id,code,title,create_user_id,create_date)"
           . " VALUES('$id','$code_refperspektive','$title_refperspektive','$create_user_id','$create_date')";
      $db->query($sql);
      
      $hdr = "<table><colgroup><col width='60'/><col/></colgroup><tbody><tr>"
           . "<td id='td_code_${id}'>$code_refperspektive</td>"
           . "<td><span id='sp_${id}' class='xlnk' onclick='edit_refperspektive(\"$id\",this,event);'>"._EMPTY."</span></td>"
           . "</tr></tbody></table>";
      return array($id,$hdr);
   }
   
   function app_saveRefPerspektive($args) {
      $db=&Database::getInstance();
      $create_user_id = getUserID();
      $id = $args[0];
      $vars = parseForm($args[1]);
      $title_refperspektive = addslashes(trim($vars["title_refperspektive"]));
      $code_refperspektive = addslashes(trim($vars["code_refperspektive"]));
     
	 if($title_refperspektive=="") {
         $title_refperspektive = "noname";
      }
      if($id=="new") {
         $sql = "SELECT MAX(id) FROM sms_ref_perspektive";
         $result = $db->query($sql);
         list($idx)=$db->fetchRow($result);
         $id = $idx+1;
         $create_user_id = getUserID();
         $sql = "INSERT INTO sms_ref_perspektive (id,code,title,create_user_id)"
              . " VALUES('$id','$code_refperspektive','$title_refperspektive','$create_user_id')";
         $db->query($sql);
      } else {
         $sql = "UPDATE sms_ref_perspektive SET title = '$title_refperspektive',"
              . "code = '$code_refperspektive' "
              . " WHERE id = '$id'";
         $db->query($sql);
      }
      
      return array($id,$title_refperspektive,$code_refperspektive);
   }
   
   function app_editRefPerspektive($args) {
      $db=&Database::getInstance();
      $id = $args[0];
      if($id=="new") {
         $generate = "";
      } else {
         
         $sql = "SELECT title,code"
              . " FROM sms_ref_perspektive"
              . " WHERE id = '$id'";
         $result = $db->query($sql);
         
         list($title_refperspektive,$code_refperspektive)=$db->fetchRow($result);
         $title_refperspektive = htmlentities($title_refperspektive,ENT_QUOTES);
      }
      $ret = "<form id='frm'><table class='xxfrm' style='width:100%;'><colgroup><col width='160'/><col/></colgroup><tbody>"
           . "<tr><td>Code Ref Perspektive</td><td><input type='text' value=\"$code_refperspektive\" id='inp_refperspektive_code' name='code_refperspektive' style='width:60%;'/></td></tr>"
           . "<tr><td>Title Ref Perspektive</td><td><input type='text' value=\"$title_refperspektive\" id='inp_refperspektive_title' name='title_refperspektive' style='width:60%;'/></td></tr>"
           
           . "<tr><td colspan='2'><span id='progress'></span>&nbsp;"
           . "<input id='btn_save_refperspektive' onclick='save_refperspektive();' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input id='btn_cancel_edit' onclick='cancel_edit();' type='button' value='"._CANCEL."'/>&nbsp;&nbsp;"
           . ($id!="new"?"<input id='btn_delete_refperspektive' onclick='delete_refperspektive();' type='button' value='"._DELETE."'/>":"")
           . "</td></tr>"
           . "</tbody></table></form>";
      return $ret;
   }
   
   function app_Delete($args) {
      $db=&Database::getInstance();
      $id = $args[0];
      $sql = "DELETE FROM sms_ref_perspektive WHERE id = '$id'";
      $db->query($sql);
   }
   
}

} /// HRIS_ASSESSMENTREFPERSPEKTIVEAJAX_DEFINED
?>