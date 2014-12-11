<?php
//--------------------------------------------------------------------//
// Filename : modules/antrain/class/ajax_antrainbudget.php                  //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-12-17                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_ANTRAINIMPORTIDPAJAX_DEFINED') ) {
   define('HRIS_ANTRAINIMPORTIDPAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");
require_once(XOCP_DOC_ROOT."/modules/antrain/modconsts.php");


class _antrain_class_ANTRAINImportIDPAjax extends AjaxListener {
   
   function __construct($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/antrain/class/ajax_antrainimportidp.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_importidp");
   }
   
   function app_importidp($args) {
       $db=&Database::getInstance();

       $id_created = getUserID();
       $date_created = getSQLDate();
       $id_session = "2014";

       $sqlreqid = "SELECT DISTINCT a.request_id FROM hris_idp_request_actionplan a LEFT JOIN hris_idp_request b ON  b.request_id = a.request_id WHERE b.created_dttm >= '2014-01-01' AND b.created_dttm <= '2014-10-14' AND (a.method_t = 'TRN_EX' OR a.method_t = 'TRN_IN') AND (b.status_cd = 'approval2' OR b.status_cd = 'approval3' OR b.status_cd = 'implementation' OR b.status_cd = 'completed') ORDER BY b.created_dttm ASC";
       $resultreqid = $db->query($sqlreqid);
       while (list($request_id)=$db->fetchRow($resultreqid)) {
	      $sql = "SELECT employee_id FROM hris_idp_request WHERE request_id = '$request_id'";
	       $result = $db->query($sql);
	       list($employee_id)=$db->fetchRow($result);
	      $sql = "SELECT b.org_id FROM hris_employee_job a LEFT JOIN hris_jobs b ON b.job_id = a.job_id WHERE a.employee_id = $employee_id";
	      $result = $db->query($sql);
	      list($org_id)=$db->fetchRow($result);
	      $sql = "SELECT a.person_nm"
	           . " FROM hris_persons a LEFT JOIN hris_employee b ON b.person_id = a.person_id"
	           . " WHERE b.employee_id = '$employee_id'";
	      $result = $db->query($sql);
	      list($person_nm)=$db->fetchRow($result);

	       $sql = "SELECT rupiah FROM antrain_exc_rate WHERE status_cd = 'normal' AND id_global_session = $id_session";
	       $result = $db->query($sql);
	       list($rupiah) = $db->fetchRow($result);

	      $sqlses = "SELECT psid FROM antrain_sessionss a LEFT JOIN antrain_budget b ON b.id = a.id_hris_budget WHERE b.id_global_session = '$id_session' AND b.org_id = '$org_id'";
	      $resultses = $db->query($sqlses);
	      list($psid)=$db->fetchRow($resultses);
	       
	      $sqljobx = "SELECT a.job_id,b.job_class_id FROM hris_employee_job a LEFT JOIN hris_jobs b ON b.job_id = a.job_id WHERE a.employee_id ='$employee_id'";
	      $resultx = $db->query($sqljobx);
	      list($job_idx,$job_class_idx)=$db->fetchRow($resultx);

	      $sqly = "SELECT actionplan_id,competency_id,method_t,method_subject,other_institute_nm,plan_start_dttm,plan_stop_dttm,cost_estimate FROM hris_idp_request_actionplan WHERE request_id = '$request_id'";
	      $resulty = $db->query($sqly);
	      while (list($actionplan_id,$competency_id,$method_t,$method_subject,$other_institute_nm,$plan_start_dttm,$plan_stop_dttm,$cost_estimate)=$db->fetchRow($resulty)) {
	        $to_dollar = $cost_estimate / $rupiah;
	        if ($method_t == "TRN_EX") {
	          $inst = "ext";
	          $sql = "INSERT INTO antrain_plan_specific (id_antrain_session,request_id,actionplan_id,competency_id,employee_id,name,inst,id_job_class1,subject,schedule_start,schedule_end,institution,cost,create_user_id,create_date)"
	           . " VALUES ('$psid','$request_id','$actionplan_id','$competency_id','$employee_id','$person_nm','$inst','$job_class_idx','$method_subject','$plan_start_dttm','$plan_stop_dttm','$other_institute_nm','$to_dollar','$id_created','$date_created')";
	        $db->query($sql);
	        }elseif ($method_t == "TRN_IN") {
	          $inst = "int";
	          $sql = "INSERT INTO antrain_plan_specific (id_antrain_session,request_id,actionplan_id,competency_id,employee_id,name,inst,id_job_class1,subject,schedule_start,schedule_end,institution,cost,create_user_id,create_date)"
	           . " VALUES ('$psid','$request_id','$actionplan_id','$competency_id','$employee_id','$person_nm','$inst','$job_class_idx','$method_subject','$plan_start_dttm','$plan_stop_dttm','$other_institute_nm','$to_dollar','$id_created','$date_created')";
	        $db->query($sql);
	        }
	       }
       } /// eo while $request_id

   }
   
   
   
}

} /// HRIS_ASSESSMENTSESSIONAJAX_DEFINED
?>