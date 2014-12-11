<?php
//--------------------------------------------------------------------//
// Filename : modules/sms/class/ajax_smsrefcorporatevision.php                  //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2013-10-16                                              //                                                     //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_SMSREFCORPORATEVISIONAJAX_DEFINED') ) {
   define('HRIS_SMSREFCORPORATEVISIONAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");
require_once(XOCP_DOC_ROOT."/modules/sms/modconsts.php");


class _sms_class_SMSRefCorporateVisionAjax extends AjaxListener {
   
   function __construct($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/sms/class/ajax_smsrefcorporatevision.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_Delete","app_editRefCorporateVision","app_saveRefCorporateVision",
                                             "app_setSMSRefCorporateVision","app_newRefCorporateVision");
   }
   
   function app_setSMSRefCorporateVision($args) {
      $_SESSION["sms_id"] = $args[0];
   }
   
   function app_newRefCorporateVision($args) {
      $db=&Database::getInstance();
      $sql = "SELECT MAX(id) FROM sms_ref_corporate_vision";
      $result = $db->query($sql);
      list($idx)=$db->fetchRow($result);
      $id = $idx+1;
      $year_refcorporatevision = date("Y");
      $sql = "INSERT INTO sms_ref_corporate_vision (id,year,title)"
           . " VALUES('$id','$year_refcorporatevision','$title_refcorporatevision')";
      $db->query($sql);
      
      $hdr = "<table><colgroup><col width='60'/><col/></colgroup><tbody><tr>"
           . "<td id='td_year_${id}'>$year_refcorporatevision</td>"
           . "<td><span id='sp_${id}' class='xlnk' onclick='edit_refcorporatevision(\"$id\",this,event);'>"._EMPTY."</span></td>"
           . "</tr></tbody></table>";
      return array($id,$hdr);
   }
   
   function app_saveRefCorporateVision($args) {
      $db=&Database::getInstance();
      $id = $args[0];
      $vars = parseForm($args[1]);
      $title_refcorporatevision = addslashes(trim($vars["title_refcorporatevision"]));
      $year_refcorporatevision = _bctrim(bcadd(0,$vars["year_refcorporatevision"]));
     
   if($title_refcorporatevision=="") {
         $title_refcorporatevision = "noname";
      }
      if($id=="new") {
         $sql = "SELECT MAX(id) FROM sms_ref_corporate_vision";
         $result = $db->query($sql);
         list($idx)=$db->fetchRow($result);
         $id = $idx+1;
         $sql = "INSERT INTO sms_ref_corporate_vision (id,year,title)"
              . " VALUES('$id','$year_refcorporatevision','$title_refcorporatevision')";
         $db->query($sql);
      } else {
         $sql = "UPDATE sms_ref_corporate_vision SET title = '$title_refcorporatevision',"
              . "year = '$year_refcorporatevision' "
              . " WHERE id = '$id'";
         $db->query($sql);
      }
      
      return array($id,$title_refcorporatevision,$year_refcorporatevision);
   }
   
   function app_editRefCorporateVision($args) {
      $db=&Database::getInstance();
      $id = $args[0];
      if($id=="new") {
         $generate = "";
      } else {
         
         $sql = "SELECT title,year"
              . " FROM sms_ref_corporate_vision"
              . " WHERE id = '$id'";
         $result = $db->query($sql);
         
         list($title_refcorporatevision,$year_refcorporatevision)=$db->fetchRow($result);
         $title_refcorporatevision = htmlentities($title_refcorporatevision,ENT_QUOTES);
      }
      $ret = "<form id='frm'><table class='xxfrm' style='width:100%;'><colgroup><col width='160'/><col/></colgroup><tbody>"
           . "<tr><td>Year Ref Corporate Vision</td><td><input type='text' value=\"$year_refcorporatevision\" id='inp_refcorporatevision_year' name='year_refcorporatevision' style='width:60%;'/></td></tr>"
           . "<tr><td>Title Ref Corporate Vision</td><td><input type='text' value=\"$title_refcorporatevision\" id='inp_refcorporatevision_title' name='title_refcorporatevision' style='width:60%;'/></td></tr>"
           
           . "<tr><td colspan='2'><span id='progress'></span>&nbsp;"
           . "<input id='btn_save_refcorporatevision' onclick='save_refcorporatevision();' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input id='btn_cancel_edit' onclick='cancel_edit();' type='button' value='"._CANCEL."'/>&nbsp;&nbsp;"
           . ($id!="new"?"<input id='btn_delete_refcorporatevision' onclick='delete_refcorporatevision();' type='button' value='"._DELETE."'/>":"")
           . "</td></tr>"
           . "</tbody></table></form>";
      return $ret;
   }
   
   function app_Delete($args) {
      $db=&Database::getInstance();
      $id = $args[0];
      $sql = "DELETE FROM sms_ref_corporate_vision WHERE id = '$id'";
      $db->query($sql);
   }
   
}

} /// HRIS_ASSESSMENTREFCORPORATEVISIONAJAX_DEFINED
?>