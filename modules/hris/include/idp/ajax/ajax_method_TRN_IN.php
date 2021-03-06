<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/include/idp/ajax/ajax_method_TRN_IN.php   //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2010-03-23                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_IDPMETHODTRN_INAJAX_DEFINED') ) {
   define('HRIS_IDPMETHODTRN_INAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");
require_once(XOCP_DOC_ROOT."/modules/hris/include/idp/idp.php");
require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");

class _idp_class_method_TRN_IN_ajax extends AjaxListener {
   
   function _idp_class_method_TRN_IN_ajax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/hris/include/idp/ajax/ajax_method_TRN_IN.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_saveReport","app_submitReport","app_approval1Report",
                                             "app_approval2Report","app_newIdea","app_deleteIdea",
                                             "app_sectionManagerReturnReport","app_divisionManagerReturnReport");
      $this->setReqPOST();
   }
   
   function app_divisionManagerReturnReport($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $event_id = $args[0];
      $employee_id = $args[1];
      $return_note = addslashes(trim(urldecode($args[2])));
      $sql = "UPDATE ".XOCP_PREFIX."idp_report_TRN_IN SET status_cd = 'approval1', return_note = '$return_note'"
           . " WHERE event_id = '$event_id'"
           . " AND employee_id = '$employee_id'";
      $db->query($sql);
      $sql = "SELECT section_mgr_emp_id,request_id,actionplan_id FROM ".XOCP_PREFIX."idp_report_TRN_IN"
           . " WHERE event_id = '$event_id'"
           . " AND employee_id = '$employee_id'";
      $result = $db->query($sql);
      list($section_mgr_emp_id,$request_id,$actionplan_id)=$db->fetchRow($result);
      
            list($sm_job_id,
                 $sm_employee_idx,
                 $sm_job_nm,
                 $sm_nm,
                 $sm_nip,
                 $sm_gender,
                 $sm_jobstart,
                 $sm_entrance_dttm,
                 $sm_jobage,
                 $sm_job_summary,
                 $sm_person_id,
                 $sm_user_idx,
                 $sm_first_assessor_job_id,
                 $sm_next_assessor_job_id)=_hris_getinfobyemployeeid($section_mgr_emp_id);
      
            list($emp_job_id,
                 $emp_employee_idx,
                 $emp_job_nm,
                 $emp_nm,
                 $emp_nip,
                 $emp_gender,
                 $emp_jobstart,
                 $emp_entrance_dttm,
                 $emp_jobage,
                 $emp_job_summary,
                 $emp_person_id,
                 $emp_user_idx,
                 $emp_first_assessor_job_id,
                 $emp_next_assessor_job_id)=_hris_getinfobyemployeeid($employee_id);
      
      _idp_send_notification($sm_user_idx,$request_id,"_IDP_YOURAPPROVAL1REPORTRETURNED","\$report_request_id=${request_id};\$report_actionplan_id=${actionplan_id};","DMRETURNREPORT",$user_id);
      _idp_send_notification($emp_user_idx,$request_id,"_IDP_YOURREPORTRETURNEDBYDM","\$report_request_id=${request_id};\$report_actionplan_id=${actionplan_id};","DMRETURNREPORT",$user_id);
      
      //// hide follow notification
      $sql = "SELECT notification_id"
           . " FROM ".XOCP_PREFIX."idp_notifications"
           . " WHERE request_id = '$request_id'"
           . " AND message_id = '_IDP_YOUHAVEAPPROVAL2REPORT'"
           . " AND source_app = 'SMAPPROVAL1REPORT'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($notification_id)=$db->fetchRow($result)) {
            _idp_notification_followed($notification_id);
         }
      }
      
      return array($request_id,$actionplan_id);
   }
   
   function app_sectionManagerReturnReport($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $event_id = $args[0];
      $employee_id = $args[1];
      $return_note = addslashes(trim(urldecode($args[2])));
      $sql = "UPDATE ".XOCP_PREFIX."idp_report_TRN_IN SET status_cd = 'prepared', return_note = '$return_note'"
           . " WHERE event_id = '$event_id'"
           . " AND employee_id = '$employee_id'";
      $db->query($sql);
      $sql = "SELECT request_id,actionplan_id FROM ".XOCP_PREFIX."idp_report_TRN_IN"
           . " WHERE event_id = '$event_id'"
           . " AND employee_id = '$employee_id'";
      $result = $db->query($sql);
      list($request_id,$actionplan_id)=$db->fetchRow($result);
      
            list($job_id,
                 $employee_idx,
                 $job_nm,
                 $nm,
                 $nip,
                 $gender,
                 $jobstart,
                 $entrance_dttm,
                 $jobage,
                 $job_summary,
                 $person_id,
                 $user_idx,
                 $first_assessor_job_id,
                 $next_assessor_job_id)=_hris_getinfobyemployeeid($employee_id);
      
      _idp_send_notification($user_idx,$request_id,"_IDP_YOURREPORTRETURNED","\$report_request_id=${request_id};\$report_actionplan_id=${actionplan_id};","SMRETURNREPORT",$user_id);
      
      //// hide follow notification
      $sql = "SELECT notification_id"
           . " FROM ".XOCP_PREFIX."idp_notifications"
           . " WHERE request_id = '$request_id'"
           . " AND message_id = '_IDP_YOURREPORTRETURNEDBYDM'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($notification_id)=$db->fetchRow($result)) {
            _idp_notification_followed($notification_id);
         }
      }
      
      //// hide follow notification
      $sql = "SELECT notification_id"
           . " FROM ".XOCP_PREFIX."idp_notifications"
           . " WHERE request_id = '$request_id'"
           . " AND message_id = '_IDP_YOUHAVEAPPROVAL1REPORT'"
           . " AND source_app = 'EMPLOYEESUBMITREPORT'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($notification_id)=$db->fetchRow($result)) {
            _idp_notification_followed($notification_id);
         }
      }
      
      //// hide follow notification
      $sql = "SELECT notification_id"
           . " FROM ".XOCP_PREFIX."idp_notifications"
           . " WHERE request_id = '$request_id'"
           . " AND message_id = '_IDP_YOURAPPROVAL1REPORTRETURNED'"
           . " AND source_app = 'DMRETURNREPORT'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($notification_id)=$db->fetchRow($result)) {
            _idp_notification_followed($notification_id);
         }
      }
      
      return array($request_id,$actionplan_id);
   }
   
   function app_deleteIdea($args) {
      $db=&Database::getInstance();
      $event_id = $args[0];
      $employee_id = $args[1];
      $idea_id = $args[2];
      $sql = "DELETE FROM ".XOCP_PREFIX."idp_report_TRN_IN_ideas"
           . " WHERE event_id = '$event_id' AND employee_id = '$employee_id'"
           . " and idea_id = '$idea_id'";
      $db->query($sql);
   }
   
   function app_newIdea($args) {
      $db=&Database::getInstance();
      $event_id = $args[0];
      $employee_id = $args[1];
      $sql = "SELECT MAX(idea_id) FROM ".XOCP_PREFIX."idp_report_TRN_IN_ideas WHERE event_id = '$event_id' AND employee_id = '$employee_id'";
      $result = $db->query($sql);
      $idea_id = 0;
      if($db->getRowsNum($result)>0) {
         list($idea_id)=$db->fetchRow($result);
      }
      $idea_id++;
      $sql = "INSERT INTO ".XOCP_PREFIX."idp_report_TRN_IN_ideas (event_id,employee_id,idea_id) VALUES ('$event_id','$employee_id','$idea_id')";
      $db->query($sql);
      
      $idea_start_dttm = "0000-00-00 00:00:00";
      $idea_stop_dttm = "0000-00-00 00:00:00";
      
      $text = "<input type='text' style='width:95%;' id='textareaidea_${idea_id}' name='textareaidea_${idea_id}'/>"
            . "<div style='padding:2px;'>[<span class='ylnk'' onclick='delete_idea(\"$idea_id\",this,event);'>delete</span>]</div>";
      
      $dttm = "<span class='xlnk' id='sp_ideastart_${idea_id}' onclick='_changedatetime(\"sp_ideastart_${idea_id}\",\"idea_start_dttm_${idea_id}\",\"date\",true,false)'>".sql2ind($idea_start_dttm,"date")."</span> - "
            . "<span class='xlnk' id='sp_ideastop_${idea_id}' onclick='_changedatetime(\"sp_ideastop_${idea_id}\",\"idea_stop_dttm_${idea_id}\",\"date\",true,false);'>".sql2ind($idea_stop_dttm,"date")."</span>"
            . "<input type='hidden' id='idea_start_dttm_${idea_id}' name='idea_start_dttm_${idea_id}' value='$idea_start_dttm'/>"
            . "<input type='hidden' id='idea_stop_dttm_${idea_id}' name='idea_stop_dttm_${idea_id}' value='$idea_stop_dttm'/>";
      
      return array($idea_id,$text,$dttm);
   }
   
   function app_approval2Report($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $event_id = $args[0];
      $employee_id = $args[1];
      
      list($job_id,
           $employee_idx,
           $job_nm,
           $nm,
           $nip,
           $gender,
           $jobstart,
           $entrance_dttm,
           $jobage,
           $job_summary,
           $person_id,
           $user_idx,
           $first_assessor_job_id,
           $next_assessor_job_id)=_hris_getinfobyemployeeid($employee_id);
   
      
      $sql = "UPDATE ".XOCP_PREFIX."idp_report_TRN_IN SET status_cd = 'completed', division_mgr_approve_dttm = now()"
           . " WHERE event_id = '$event_id'"
           . " AND employee_id = '$employee_id'";
      $db->query($sql);
      
      $sql = "SELECT request_id,actionplan_id FROM ".XOCP_PREFIX."idp_report_TRN_IN"
           . " WHERE event_id = '$event_id'"
           . " AND employee_id = '$employee_id'";
      $result = $db->query($sql);
      list($request_id,$actionplan_id)=$db->fetchRow($result);
      
      $sql = "UPDATE ".XOCP_PREFIX."idp_request_actionplan SET status_cd = 'completed' WHERE request_id = '$request_id' AND actionplan_id = '$actionplan_id'";
      $db->query($sql);
      
      //// hide follow notification
      $sql = "SELECT notification_id"
           . " FROM ".XOCP_PREFIX."idp_notifications"
           . " WHERE request_id = '$request_id'"
           . " AND message_id = '_IDP_YOUHAVEAPPROVAL2REPORT'"
           . " AND source_app = 'SMAPPROVAL1REPORT'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($notification_id)=$db->fetchRow($result)) {
            _idp_notification_followed($notification_id);
         }
      }
      
      _idp_send_notification($user_idx,$request_id,"_IDP_YOURREPORTHASBEENCOMPLETED","\$report_request_id=${request_id};\$report_actionplan_id=${actionplan_id};","DMAPPROVAL2REPORT",$user_id);
      
   }
   
   function app_approval1Report($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $event_id = $args[0];
      $employee_id = $args[1];
      
      list($job_id,
           $employee_idx,
           $job_nm,
           $nm,
           $nip,
           $gender,
           $jobstart,
           $entrance_dttm,
           $jobage,
           $job_summary,
           $person_id,
           $user_idx,
           $first_assessor_job_id,
           $next_assessor_job_id)=_hris_getinfobyemployeeid($employee_id);
   
      $sql = "SELECT b.job_level FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
           . " WHERE a.job_id = '$job_id'";
      $result = $db->query($sql);
      list($employee_job_level)=$db->fetchRow($result);
   
      $sql = "SELECT request_id,actionplan_id,division_mgr_emp_id FROM ".XOCP_PREFIX."idp_report_TRN_IN"
           . " WHERE event_id = '$event_id'"
           . " AND employee_id = '$employee_id'";
      $result = $db->query($sql);
      list($request_id,$actionplan_id,$division_mgr_emp_id)=$db->fetchRow($result);
      list($division_mgr_job_id,
           $division_mgr_employee_idx,
           $division_mgr_job_nm,
           $division_mgr_nm,
           $division_mgr_nip,
           $division_mgr_gender,
           $division_mgr_jobstart,
           $division_mgr_entrance_dttm,
           $division_mgr_jobage,
           $division_mgr_job_summary,
           $division_mgr_person_id,
           $division_mgr_user_idx,
           $division_mgr_first_assessor_job_id,
           $division_mgr_next_assessor_job_id)=_hris_getinfobyemployeeid($division_mgr_emp_id);
         
      if($employee_job_level=="management") {
         $doubleapproval = 1;
      } else {
         $doubleapproval = 0;
      }
         
      if($doubleapproval==1) {
         $sql = "UPDATE ".XOCP_PREFIX."idp_report_TRN_IN SET status_cd = 'approval2', section_mgr_approve_dttm = now(), return_note = ''"
              . " WHERE event_id = '$event_id'"
              . " AND employee_id = '$employee_id'";
         $db->query($sql);
         
         _idp_send_notification($division_mgr_user_idx,$request_id,"_IDP_YOUHAVEAPPROVAL2REPORT","\$report_request_id=${request_id};\$report_actionplan_id=${actionplan_id};","SMAPPROVAL1REPORT",$user_id);
      
      } else {
         $sql = "UPDATE ".XOCP_PREFIX."idp_report_TRN_IN SET status_cd = 'completed', section_mgr_approve_dttm = now(), return_note = ''"
              . " WHERE event_id = '$event_id'"
              . " AND employee_id = '$employee_id'";
         $db->query($sql);
         
         ///_idp_send_notification($division_mgr_user_idx,$request_id,"_IDP_YOUHAVEDMREPORTNOTIFICATION","\$report_request_id=${request_id};\$report_actionplan_id=${actionplan_id};","SMAPPROVAL1REPORT",$user_id);
         _idp_send_notification($user_idx,$request_id,"_IDP_YOURREPORTHASBEENCOMPLETED","\$report_request_id=${request_id};\$report_actionplan_id=${actionplan_id};","SMAPPROVAL1REPORT",$user_id);
         
      }
      
      //// hide follow notification
      $sql = "SELECT notification_id"
           . " FROM ".XOCP_PREFIX."idp_notifications"
           . " WHERE request_id = '$request_id'"
           . " AND message_id = '_IDP_YOURREPORTRETURNEDBYDM'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($notification_id)=$db->fetchRow($result)) {
            _idp_notification_followed($notification_id);
         }
      }
      
      //// hide follow notification
      $sql = "SELECT notification_id"
           . " FROM ".XOCP_PREFIX."idp_notifications"
           . " WHERE request_id = '$request_id'"
           . " AND message_id = '_IDP_YOUHAVEAPPROVAL1REPORT'"
           . " AND source_app = 'EMPLOYEESUBMITREPORT'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($notification_id)=$db->fetchRow($result)) {
            _idp_notification_followed($notification_id);
         }
      }
      
      //// hide follow notification
      $sql = "SELECT notification_id"
           . " FROM ".XOCP_PREFIX."idp_notifications"
           . " WHERE request_id = '$request_id'"
           . " AND message_id = '_IDP_YOURAPPROVAL1REPORTRETURNED'"
           . " AND source_app = 'DMRETURNREPORT'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($notification_id)=$db->fetchRow($result)) {
            _idp_notification_followed($notification_id);
         }
      }
      
   }
   
   function app_submitReport($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $event_id = $args[0];
      $employee_id = $args[1];
      $sql = "UPDATE ".XOCP_PREFIX."idp_report_TRN_IN SET return_note = '', status_cd = 'approval1', submit_dttm = now()"
           . " WHERE event_id = '$event_id'"
           . " AND employee_id = '$employee_id'";
      $db->query($sql);
      $sql = "SELECT request_id,actionplan_id,section_mgr_emp_id FROM ".XOCP_PREFIX."idp_report_TRN_IN"
           . " WHERE event_id = '$event_id'"
           . " AND employee_id = '$employee_id'";
      $result = $db->query($sql);
      list($request_id,$actionplan_id,$section_mgr_emp_id)=$db->fetchRow($result);
            list($section_mgr_job_id,
                 $section_mgr_employee_idx,
                 $section_mgr_job_nm,
                 $section_mgr_nm,
                 $section_mgr_nip,
                 $section_mgr_gender,
                 $section_mgr_jobstart,
                 $section_mgr_entrance_dttm,
                 $section_mgr_jobage,
                 $section_mgr_job_summary,
                 $section_mgr_person_id,
                 $section_mgr_user_idx,
                 $section_mgr_first_assessor_job_id,
                 $section_mgr_next_assessor_job_id)=_hris_getinfobyemployeeid($section_mgr_emp_id);
      
      _idp_send_notification($section_mgr_user_idx,$request_id,"_IDP_YOUHAVEAPPROVAL1REPORT","\$report_request_id=${request_id};\$report_actionplan_id=${actionplan_id};","EMPLOYEESUBMITREPORT",$user_id);
      
      //// hide follow notification
      $sql = "SELECT notification_id"
           . " FROM ".XOCP_PREFIX."idp_notifications"
           . " WHERE request_id = '$request_id'"
           . " AND message_id = '_IDP_YOURREPORTRETURNED'"
           . " AND source_app = 'SMRETURNREPORT'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($notification_id)=$db->fetchRow($result)) {
            _idp_notification_followed($notification_id);
         }
      }
      
   }
   
   function app_saveReport($args) {
      $db=&Database::getInstance();
      $event_id = $args[0];
      $employee_id = $args[1];
      $conclusion = addslashes(trim(urldecode($args[2])));
      $my_advantage = addslashes(trim(urldecode($args[3])));
      $company_advantage = addslashes(trim(urldecode($args[4])));
      $vars = _parseForm($args[5]);
      $varsidea = _parseForm($args[6]);
      
      if(isset($vars)&&is_array($vars)) {
         for($i=1;$i<=7;$i++) {
            $v = "r_0${i}";
            $w = "remark_r_0${i}";
            if(isset($vars[$v])) {
               $$v = $vars[$v];
            } else {
               $$v = 0;
            }
            if(isset($vars[$w])) {
               $$w = addslashes(trim($vars[$w]));
            } else {
               $$w = "";
            }
         }
      }
      $sql = "UPDATE ".XOCP_PREFIX."idp_report_TRN_IN SET "
           . "conclusion = '$conclusion',"
           . "my_advantage = '$my_advantage',"
           . "company_advantage = '$company_advantage',"
           . "r_01 = '$r_01',"
           . "r_02 = '$r_02',"
           . "r_03 = '$r_03',"
           . "r_04 = '$r_04',"
           . "r_05 = '$r_05',"
           . "r_06 = '$r_06',"
           . "r_07 = '$r_07',"
           . "remark_r_01 = '$remark_r_01',"
           . "remark_r_02 = '$remark_r_02',"
           . "remark_r_03 = '$remark_r_03',"
           . "remark_r_04 = '$remark_r_04',"
           . "remark_r_05 = '$remark_r_05',"
           . "remark_r_06 = '$remark_r_06',"
           . "remark_r_07 = '$remark_r_07'"
           . " WHERE event_id = '$event_id'"
           . " AND employee_id = '$employee_id'";
      $db->query($sql);
   
      if(isset($varsidea)&&count($varsidea)>0) {
         $sql = "SELECT idea_id FROM ".XOCP_PREFIX."idp_report_TRN_IN_ideas WHERE event_id = '$event_id' AND employee_id = '$employee_id'";
         $result = $db->query($sql);
         if($db->getRowsNum($result)>0) {
            while(list($idea_id)=$db->fetchRow($result)) {
               $text = addslashes(trim($varsidea["textareaidea_${idea_id}"]));
               $start_dttm = $varsidea["idea_start_dttm_${idea_id}"];
               $stop_dttm = $varsidea["idea_stop_dttm_${idea_id}"];
               $sql = "UPDATE ".XOCP_PREFIX."idp_report_TRN_IN_ideas SET "
                    . "idea_txt = '$text',"
                    . "start_dttm = '$start_dttm',"
                    . "stop_dttm = '$stop_dttm'"
                    . " WHERE event_id = '$event_id' AND employee_id = '$employee_id'"
                    . " and idea_id = '$idea_id'";
               $db->query($sql);
            }
         }
      }
   }
   
}

} /// HRIS_IDPMETHODTRN_INAJAX_DEFINED
?>