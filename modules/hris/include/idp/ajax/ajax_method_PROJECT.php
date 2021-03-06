<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/include/idp/ajax/ajax_method_PROJECT.php   //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2010-03-23                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_IDPMETHODPROJECTAJAX_DEFINED') ) {
   define('HRIS_IDPMETHODPROJECTAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");
require_once(XOCP_DOC_ROOT."/modules/hris/include/idp/idp.php");
require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");

class _idp_class_method_PROJECT_ajax extends AjaxListener {
   
   function _idp_class_method_PROJECT_ajax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/hris/include/idp/ajax/ajax_method_PROJECT.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_saveReport","app_submitReport","app_approval1Report",
                                             "app_approval2Report","app_newIdea","app_deleteIdea",
                                             "app_sectionManagerReturnReport","app_divisionManagerReturnReport",
                                             "app_saveActivityReport","app_beforeFinishActivity",
                                             "app_finishActivity");
      $this->setReqPOST();
   }
   
   function app_finishActivity($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $project_id = $args[1];
      $activity_id = $args[2];
      
      $sql = "UPDATE ".XOCP_PREFIX."idp_project_activities SET "
           . "status_cd = 'finish'"
           . " WHERE request_id = '$request_id' AND project_id = '$project_id'"
           . " and activity_id = '$activity_id'";
      $db->query($sql);
      
      $sql = "SELECT experience FROM ".XOCP_PREFIX."idp_project_activities"
           . " WHERE request_id = '$request_id' AND project_id = '$project_id'"
           . " and activity_id = '$activity_id'";
      $result = $db->query($sql);
      list($experience)=$db->fetchRow($result);
      return array($activity_id,$experience);
   
   }
   
   function app_beforeFinishActivity($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $project_id = $args[1];
      $activity_id = $args[2];
      $text = addslashes(trim(urldecode($args[3])));
      
      $sql = "UPDATE ".XOCP_PREFIX."idp_project_activities SET "
           . "experience = '$text'"
           . " WHERE request_id = '$request_id' AND project_id = '$project_id'"
           . " and activity_id = '$activity_id'";
      $db->query($sql);
      return $activity_id;
      
   }
   
   function app_saveActivityReport($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $project_id = $args[1];
      $activity_id = $args[2];
      $text = addslashes(trim(urldecode($args[3])));
      
      $sql = "UPDATE ".XOCP_PREFIX."idp_project_activities SET "
           . "experience = '$text'"
           . " WHERE request_id = '$request_id' AND project_id = '$project_id'"
           . " and activity_id = '$activity_id'"
           . " and status_cd = 'normal'";
      $db->query($sql);
      return $activity_id;
      
   }
   
   
   function app_divisionManagerReturnReport($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $request_id = $args[0];
      $project_id = $args[1];
      $report_return_note = addslashes(trim(urldecode($args[2])));
      $sql = "UPDATE ".XOCP_PREFIX."idp_project SET status_cd = 'approval1', report_return_note = '$report_return_note'"
           . " WHERE request_id = '$request_id'"
           . " AND project_id = '$project_id'";
      $db->query($sql);
      $sql = "SELECT section_mgr_emp_id,request_id,actionplan_id FROM ".XOCP_PREFIX."idp_report_PROJECT"
           . " WHERE request_id = '$request_id'"
           . " AND project_id = '$project_id'";
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
      $request_id = $args[0];
      $project_id = $args[1];
      $report_return_note = addslashes(trim(urldecode($args[2])));
      $sql = "UPDATE ".XOCP_PREFIX."idp_project SET report_status_cd = 'prepared', report_return_note = '$report_return_note'"
           . " WHERE request_id = '$request_id'"
           . " AND project_id = '$project_id'";
      $db->query($sql);
      $sql = "SELECT employee_id FROM ".XOCP_PREFIX."idp_request"
           . " WHERE request_id = '$request_id'";
      $result = $db->query($sql);
      list($employee_id)=$db->fetchRow($result);
      
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
      
      _idp_send_notification($user_idx,$request_id,"_IDP_YOURREPORTRETURNED","\$report_request_id=${request_id};\$report_project_id=${project_id};","SMRETURNREPORT",$user_id);
      
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
      
      return array($request_id,$project_id);
   }
   
   function app_deleteIdea($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $project_id = $args[1];
      $idea_id = $args[2];
      $sql = "DELETE FROM ".XOCP_PREFIX."idp_report_PROJECT_ideas"
           . " WHERE request_id = '$request_id' AND project_id = '$project_id'"
           . " and idea_id = '$idea_id'";
      $db->query($sql);
   }
   
   function app_newIdea($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $project_id = $args[1];
      $sql = "SELECT MAX(idea_id) FROM ".XOCP_PREFIX."idp_report_PROJECT_ideas WHERE request_id = '$request_id' AND project_id = '$project_id'";
      $result = $db->query($sql);
      $idea_id = 0;
      if($db->getRowsNum($result)>0) {
         list($idea_id)=$db->fetchRow($result);
      }
      $idea_id++;
      $idea_start_dttm = getSQLDate();
      $idea_stop_dttm = getSQLDate();
      $sql = "INSERT INTO ".XOCP_PREFIX."idp_report_PROJECT_ideas (request_id,project_id,idea_id,start_dttm,stop_dttm) VALUES ('$request_id','$project_id','$idea_id','$idea_start_dttm','$idea_stop_dttm')";
      $db->query($sql);
      
      $text = "<input type='text' style='width:80%;' id='textareaidea_${idea_id}' name='textareaidea_${idea_id}'/>"
            . "&nbsp;[<span class='xlnk'' onclick='delete_idea(\"$idea_id\",this,event);'>delete</span>]";
      
      return array($idea_id,$text);
   }
   
   function app_approval2Report($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $request_id = $args[0];
      $project_id = $args[1];
      
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
   
      
      $sql = "UPDATE ".XOCP_PREFIX."idp_project SET status_cd = 'completed', division_mgr_approve_dttm = now()"
           . " WHERE request_id = '$request_id'"
           . " AND project_id = '$project_id'";
      $db->query($sql);
      
      $sql = "SELECT request_id,actionplan_id FROM ".XOCP_PREFIX."idp_report_PROJECT"
           . " WHERE request_id = '$request_id'"
           . " AND project_id = '$project_id'";
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
      $request_id = $args[0];
      $project_id = $args[1];
      
      $sql = "SELECT employee_id FROM ".XOCP_PREFIX."idp_request"
           . " WHERE request_id = '$request_id'";
      $result = $db->query($sql);
      list($employee_id)=$db->fetchRow($result);
      
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
   
      $sql = "SELECT report_division_mgr_emp_id FROM ".XOCP_PREFIX."idp_project"
           . " WHERE request_id = '$request_id'"
           . " AND project_id = '$project_id'";
      $result = $db->query($sql);
      list($$division_mgr_emp_id)=$db->fetchRow($result);
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
      
      $doubleapproval = 0;
         
      if($doubleapproval==1) {
         $sql = "UPDATE ".XOCP_PREFIX."idp_project SET report_status_cd = 'approval2', report_section_mgr_approve_dttm = now(), report_return_note = ''"
              . " WHERE request_id = '$request_id'"
              . " AND project_id = '$project_id'";
         $db->query($sql);
         
         _idp_send_notification($division_mgr_user_idx,$request_id,"_IDP_YOUHAVEAPPROVAL2REPORT","\$report_request_id=${request_id};\$report_project_id=${project_id};","SMAPPROVAL1REPORT",$user_id);
      
      } else {
         $sql = "UPDATE ".XOCP_PREFIX."idp_project SET report_status_cd = 'completed', report_section_mgr_approve_dttm = now(), report_return_note = ''"
              . " WHERE request_id = '$request_id'"
              . " AND project_id = '$project_id'";
         $db->query($sql);
         
         $sql = "UPDATE ".XOCP_PREFIX."idp_request_actionplan SET status_cd = 'completed', completed_dttm = now()"
              . " WHERE request_id = '$request_id'"
              . " AND project_id = '$project_id'";
         $db->query($sql);
         
         ///_idp_send_notification($division_mgr_user_idx,$request_id,"_IDP_YOUHAVEDMREPORTNOTIFICATION","\$report_request_id=${request_id};\$report_project_id=${project_id};","SMAPPROVAL1REPORT",$user_id);
         _idp_send_notification($user_idx,$request_id,"_IDP_YOURREPORTHASBEENCOMPLETED","\$report_request_id=${request_id};\$report_project_id=${project_id};","SMAPPROVAL1REPORT",$user_id);
         
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
      $request_id = $args[0];
      $project_id = $args[1];
      $sql = "UPDATE ".XOCP_PREFIX."idp_project SET report_return_note = '', report_status_cd = 'approval1', report_submit_dttm = now()"
           . " WHERE request_id = '$request_id'"
           . " AND project_id = '$project_id'";
      $db->query($sql);
      $sql = "SELECT report_section_mgr_emp_id FROM ".XOCP_PREFIX."idp_project"
           . " WHERE request_id = '$request_id'"
           . " AND project_id = '$project_id'";
      $result = $db->query($sql);
      list($section_mgr_emp_id)=$db->fetchRow($result);
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
      
      
      _idp_send_notification($section_mgr_user_idx,$request_id,"_IDP_YOUHAVEAPPROVAL1REPORT","\$report_request_id=${request_id};\$report_project_id=${project_id};","EMPLOYEESUBMITREPORT",$user_id);
      
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
      $request_id = $args[0];
      $project_id = $args[1];
      $report_cost = _bctrim(bcadd($args[2],0));
      $varsidea = _parseForm($args[3]);
      $varsactivities = _parseForm($args[4]);
      $achievement = addslashes(trim(urldecode($args[5])));
      
      $sql = "UPDATE ".XOCP_PREFIX."idp_project SET "
           . "report_cost = '$report_cost',"
           . "achievement = '$achievement'"
           . " WHERE request_id = '$request_id'"
           . " AND project_id = '$project_id'";
      $db->query($sql);
   
      if(isset($varsidea)&&count($varsidea)>0) {
         $sql = "SELECT idea_id FROM ".XOCP_PREFIX."idp_report_PROJECT_ideas WHERE request_id = '$request_id' AND project_id = '$project_id'";
         $result = $db->query($sql);
         if($db->getRowsNum($result)>0) {
            while(list($idea_id)=$db->fetchRow($result)) {
               $text = addslashes(trim($varsidea["textareaidea_${idea_id}"]));
               $start_dttm = $varsidea["idea_start_dttm_${idea_id}"];
               $stop_dttm = $varsidea["idea_stop_dttm_${idea_id}"];
               $sql = "UPDATE ".XOCP_PREFIX."idp_report_PROJECT_ideas SET "
                    . "idea_txt = '$text',"
                    . "start_dttm = '$start_dttm',"
                    . "stop_dttm = '$stop_dttm'"
                    . " WHERE request_id = '$request_id' AND project_id = '$project_id'"
                    . " and idea_id = '$idea_id'";
               $db->query($sql);
            }
         }
      }
      
      if(isset($varsactivities)&&count($varsactivities)>0) {
         $sql = "SELECT activity_id FROM ".XOCP_PREFIX."idp_project_activities WHERE request_id = '$request_id' AND project_id = '$project_id' AND status_cd = 'normal'";
         $result = $db->query($sql);
         if($db->getRowsNum($result)>0) {
            while(list($activity_id)=$db->fetchRow($result)) {
               $text = addslashes(trim($varsactivities["inp_ex_${activity_id}"]));
               $sql = "UPDATE ".XOCP_PREFIX."idp_project_activities SET "
                    . "experience = '$text'"
                    . " WHERE request_id = '$request_id' AND project_id = '$project_id'"
                    . " and activity_id = '$activity_id'";
               $db->query($sql);
            }
         }
      }
      
      
   }
   
}

} /// HRIS_IDPMETHODPROJECTAJAX_DEFINED
?>