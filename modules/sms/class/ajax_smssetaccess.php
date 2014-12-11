<?php
//--------------------------------------------------------------------//
// Filename : modules/sms/class/ajax_smssetaccess.php            //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2013-12-12                                              //                                                                    //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_SMSSETACCESSJAX_DEFINED') ) {
   define('HRIS_SMSSETACCESSJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");
require_once(XOCP_DOC_ROOT."/modules/sms/modconsts.php");


class _sms_class_SMSSetAccessAjax extends AjaxListener {
   
   function __construct($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/sms/class/ajax_smssetaccess.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_setAccess");
   }
   
   function app_setAccess() {
      $db=&Database::getInstance();

      $pgroup_id = $_SESSION["xocp_user"]->getVar("pgroup_id");

      $sql = "SELECT a.employee_id,b.user_id,a.gradeval,c.pgroup_id" 
           . " FROM hris_employee_job a"
           . " LEFT JOIN hris_users b ON b.person_id = a.employee_id"
           . " LEFT JOIN hris_user_pgroup c ON c.user_id = b.user_id"
           . " WHERE a.gradeval > 5";

      $result = $db->query($sql);
      while (list($employee_id,$user_id)=$db->fetchRow($result)) {
        $sql = "INSERT INTO hris_user_pgroup (user_id, pgroup_id, is_dirty) VALUES ('$user_id', '$pgroup_id', 'n')";
        $db->query($sql);
      }
   }
   
}

} /// HRIS_ASSESSMENTREFPERSPEKTIVEAJAX_DEFINED
?>