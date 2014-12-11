<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_idpinstituteclass.php              //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_IDPDEVLIBAJAX_DEFINED') ) {
   define('HRIS_IDPDEVLIBAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");

class _hris_class_IDPInstitutesAjax extends AjaxListener {
   
   function _hris_class_IDPInstitutesAjax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/hris/class/ajax_idpinstitutes.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_deleteInstitute","app_editInstitute","app_saveInstitute",
                            "app_resetSuperior","app_browseCompetency","app_selectCompgroup",
                            "app_addCompetency","app_deleteCompetencyRel");
   }
   
   function app_saveInstitute($args) {
      $db=&Database::getInstance();
      $institute_t = $_SESSION["hris_institute_t"];
      $institute_id = $args[0];
      $institute_nm = addslashes(trim($args[1]));
      $institute_addr = addslashes(trim($args[2]));
      $institute_phone = addslashes(trim($args[3]));
      $institute_web = addslashes(trim($args[4]));
      $institute_contact = addslashes(trim($args[5]));
      
      $user_id = getUserID();
      
      if($institute_id=="new") {
         $sql = "INSERT INTO ".XOCP_PREFIX."idp_institutes (institute_nm,created_user_id)"
              . " VALUES('$institute_nm','$user_id')";
         $db->query($sql);
         $institute_id = $db->getInsertId();
      } 
      $sql = "UPDATE ".XOCP_PREFIX."idp_institutes SET "
           . "institute_nm = '$institute_nm', institute_addr = '$institute_addr',"
           . "institute_phone = '$institute_phone', institute_web = '$institute_web',"
           . "institute_contact = '$institute_contact'"
           . " WHERE institute_id = '$institute_id'";
      $db->query($sql);
      
      $ret .= "<table border='0' class='ilist'>"
            . "<colgroup><col/></colgroup>"
            . "<tbody><tr>"
            . "<td><span onclick='edit_institute(\"$institute_id\",this,event);' class='xlnk'>".htmlentities(stripslashes($institute_nm))."</span></td>"
            . "</tr></tbody></table>";
      
      return array("tdclass_${institute_id}",$ret);
   }
   
   function app_editInstitute($args) {
      $db=&Database::getInstance();
      $institute_id = $args[0];
      if($institute_id=="new") {
         $institute_id_val = "";
      } else {
         $sql = "SELECT institute_id,institute_nm,institute_addr,institute_phone,institute_web,institute_contact"
              . " FROM ".XOCP_PREFIX."idp_institutes"
              . " WHERE institute_id = '$institute_id'";
         $result = $db->query($sql);
         list($institute_id,$institute_nm,$institute_addr,$institute_phone,$institute_web,$institute_contact)=$db->fetchRow($result);
         $institute_nm = htmlentities($institute_nm,ENT_QUOTES);
         $institute_addr = htmlentities($institute_addr,ENT_QUOTES);
         $institute_phone = htmlentities($institute_phone,ENT_QUOTES);
         $institute_web = htmlentities($institute_web,ENT_QUOTES);
         $institute_contact = htmlentities($institute_contact,ENT_QUOTES);
         $institute_id_val = $institute_id;
      }
      
      $ret = "<form id='frm'><table class='xxfrm' style='width:100%;'>"
           . "<colgroup><col width='220'/><col/></colgroup>"
           . "<tbody>"
           . "<tr><td>Institute Name</td><td><input type='text' value=\"$institute_nm\" id='inp_institute_nm' name='institute_nm' style='width:90%;'/></td></tr>"
           . "<tr><td>Address</td><td><input type='text' value=\"$institute_addr\" id='inp_institute_addr' name='institute_addr' style='width:90%;'/></td></tr>"
           . "<tr><td>Phone</td><td><input type='text' value=\"$institute_phone\" id='inp_institute_phone' name='institute_phone' style='width:90%;'/></td></tr>"
           . "<tr><td>Website</td><td><input type='text' value=\"$institute_web\" id='inp_institute_web' name='institute_web' style='width:90%;'/></td></tr>"
           . "<tr><td>Contact Person</td><td><input type='text' value=\"$institute_contact\" id='inp_institute_contact' name='institute_contact' style='width:90%;'/></td></tr>"
           . "<tr><td colspan='2'><input onclick='save_institute();' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input onclick='cancel_edit();' type='button' value='"._CANCEL."'/>"
           . "&nbsp;&nbsp;" . ($institute_id!="new"?"<input onclick='delete_action();' type='button' value='"._DELETE."'/>":"")
           . "</td></tr>"
           . "</tbody></table></form>";
      return $ret;
   }
   
   function app_deleteInstitute($args) {
      $db=&Database::getInstance();
      $institute_id = $args[0];
      $user_id = getUserID();
      $sql = "UPDATE ".XOCP_PREFIX."idp_institutes SET status_cd = 'nullified', nullified_dttm = now(), nullified_user_id = '$user_id' WHERE institute_id = '$institute_id'";
      $db->query($sql);
   }
   
}

} /// HRIS_IDPDEVLIBAJAX_DEFINED
?>