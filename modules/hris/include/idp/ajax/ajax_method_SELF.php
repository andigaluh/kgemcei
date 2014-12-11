<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/include/idp/ajax/ajax_method_SELF.php   //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2010-03-23                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_IDPMETHODSELFAJAX_DEFINED') ) {
   define('HRIS_IDPMETHODSELFAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");
require_once(XOCP_DOC_ROOT."/modules/hris/include/idp/idp.php");
require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");

class _idp_class_method_SELF_ajax extends AjaxListener {
   
   function _idp_class_method_SELF_ajax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/hris/include/idp/ajax/ajax_method_SELF.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_saveReport","app_submitReport","app_approval1Report",
                                             "app_approval2Report","app_newIdea","app_deleteIdea",
                                             "app_return1Report","app_divisionManagerReturnReport",
                                             "app_saveActivityReport","app_beforeFinishActivity",
                                             "app_finishActivity","app_newChapter","app_deleteChapter");
      $this->setReqPOST();
   }
   
   function app_deleteChapter($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $actionplan_id = $args[1];
      $chapter_id = $args[2];
      $sql = "DELETE FROM ".XOCP_PREFIX."idp_report_SELF_1_chapters"
           . " WHERE request_id = '$request_id' AND actionplan_id = '$actionplan_id'"
           . " and chapter_id = '$chapter_id'";
      $db->query($sql);
   }
   
   function app_newChapter($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $actionplan_id = $args[1];
      $sql = "SELECT MAX(chapter_id) FROM ".XOCP_PREFIX."idp_report_SELF_1_chapters WHERE request_id = '$request_id' AND actionplan_id = '$actionplan_id'";
      $result = $db->query($sql);
      $chapter_id = 0;
      if($db->getRowsNum($result)>0) {
         list($chapter_id)=$db->fetchRow($result);
      }
      $chapter_id++;
      $sql = "INSERT INTO ".XOCP_PREFIX."idp_report_SELF_1_chapters (request_id,actionplan_id,chapter_id) VALUES ('$request_id','$actionplan_id','$chapter_id')";
      $db->query($sql);
      
      $text = "<div style='text-align:center;background-color:#eee;padding:2px;'>Chapter No. : $chapter_id</div>"
            . "<div id='textareachapter_${chapter_id}' style='height:200px;'></div>"
            . "<div style='padding:2px;text-align:right;'>[<span class='xlnk' onclick='delete_chapter(\"$chapter_id\",this,event);'>delete chapter</span>]</div>";
      
      return array($chapter_id,$text);
   }
   
   function app_divisionManagerReturnReport($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $request_id = $args[0];
      $actionplan_id = $args[1];
      $return_note = addslashes(trim(urldecode($args[2])));
      $sql = "UPDATE ".XOCP_PREFIX."idp_report_SELF_1 SET status_cd = 'approval1', return_note = '$return_note'"
           . " WHERE request_id = '$request_id'"
           . " AND actionplan_id = '$actionplan_id'";
      $db->query($sql);
      $sql = "SELECT approval1_emp_id,request_id,actionplan_id FROM ".XOCP_PREFIX."idp_report_SELF_1"
           . " WHERE request_id = '$request_id'"
           . " AND actionplan_id = '$actionplan_id'";
      $result = $db->query($sql);
      list($approval1_emp_id,$request_id,$actionplan_id)=$db->fetchRow($result);
      
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
                 $sm_next_assessor_job_id)=_hris_getinfobyemployeeid($approval1_emp_id);
      
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
   
   function app_return1Report($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $request_id = $args[0];
      $actionplan_id = $args[1];
      $return_note = addslashes(trim(urldecode($args[2])));
      $sql = "UPDATE ".XOCP_PREFIX."idp_report_SELF_1 SET status_cd = 'prepared', return_note = '$return_note'"
           . " WHERE request_id = '$request_id'"
           . " AND actionplan_id = '$actionplan_id'";
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
      $request_id = $args[0];
      $actionplan_id = $args[1];
      $idea_id = $args[2];
      $sql = "DELETE FROM ".XOCP_PREFIX."idp_report_SELF_1_ideas"
           . " WHERE request_id = '$request_id' AND actionplan_id = '$actionplan_id'"
           . " and idea_id = '$idea_id'";
      $db->query($sql);
   }
   
   function app_newIdea($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $actionplan_id = $args[1];
      $sql = "SELECT MAX(idea_id) FROM ".XOCP_PREFIX."idp_report_SELF_1_ideas WHERE request_id = '$request_id' AND actionplan_id = '$actionplan_id'";
      $result = $db->query($sql);
      $idea_id = 0;
      if($db->getRowsNum($result)>0) {
         list($idea_id)=$db->fetchRow($result);
      }
      $idea_id++;
      $sql = "INSERT INTO ".XOCP_PREFIX."idp_report_SELF_1_ideas (request_id,actionplan_id,idea_id) VALUES ('$request_id','$actionplan_id','$idea_id')";
      $db->query($sql);
      
      $text = "<input type='text' style='width:80%;' id='textareaidea_${idea_id}' name='textareaidea_${idea_id}'/>"
            . "&nbsp;[<span class='xlnk'' onclick='delete_idea(\"$idea_id\",this,event);'>delete</span>]";
      
      return array($idea_id,$text);
   }
   
   function app_approval2Report($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $request_id = $args[0];
      $actionplan_id = $args[1];
      
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
   
      
      $sql = "UPDATE ".XOCP_PREFIX."idp_report_SELF_1 SET status_cd = 'completed', approval2_dttm = now()"
           . " WHERE request_id = '$request_id'"
           . " AND actionplan_id = '$actionplan_id'";
      $db->query($sql);
      
      $sql = "SELECT request_id,actionplan_id FROM ".XOCP_PREFIX."idp_report_SELF_1"
           . " WHERE request_id = '$request_id'"
           . " AND actionplan_id = '$actionplan_id'";
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
      $actionplan_id = $args[1];
      
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
   
      $sql = "SELECT approval2_emp_id FROM ".XOCP_PREFIX."idp_report_SELF_1"
           . " WHERE request_id = '$request_id'"
           . " AND actionplan_id = '$actionplan_id'";
      $result = $db->query($sql);
      list($approval2_emp_id)=$db->fetchRow($result);
      list($approval2_job_id,
           $approval2_employee_idx,
           $approval2_job_nm,
           $approval2_nm,
           $approval2_nip,
           $approval2_gender,
           $approval2_jobstart,
           $approval2_entrance_dttm,
           $approval2_jobage,
           $approval2_job_summary,
           $approval2_person_id,
           $approval2_user_idx,
           $approval2_first_assessor_job_id,
           $approval2_next_assessor_job_id)=_hris_getinfobyemployeeid($approval2_emp_id);
      if($employee_job_level=="management") {
         $doubleapproval = 1;
      } else {
         $doubleapproval = 0;
      }
      
      $doubleapproval = 0;
         
      if($doubleapproval==1) {
         $sql = "UPDATE ".XOCP_PREFIX."idp_report_SELF_1 SET status_cd = 'approval2', approval1_dttm = now(), return_note = ''"
              . " WHERE request_id = '$request_id'"
              . " AND actionplan_id = '$actionplan_id'";
         $db->query($sql);
         
         _idp_send_notification($approval2_user_idx,$request_id,"_IDP_YOUHAVEAPPROVAL2REPORT","\$report_request_id=${request_id};\$report_actionplan_id=${actionplan_id};","SMAPPROVAL1REPORT",$user_id);
      
      } else {
         $sql = "UPDATE ".XOCP_PREFIX."idp_report_SELF_1 SET status_cd = 'completed', approval1_dttm = now(), return_note = ''"
              . " WHERE request_id = '$request_id'"
              . " AND actionplan_id = '$actionplan_id'";
         $db->query($sql);
         
         $sql = "UPDATE ".XOCP_PREFIX."idp_request_actionplan SET status_cd = 'completed', completed_dttm = now()"
              . " WHERE request_id = '$request_id'"
              . " AND actionplan_id = '$actionplan_id'";
         $db->query($sql);
         
         //_idp_send_notification($approval2_user_idx,$request_id,"_IDP_YOUHAVEDMREPORTNOTIFICATION","\$report_request_id=${request_id};\$report_actionplan_id=${actionplan_id};","SMAPPROVAL1REPORT",$user_id);
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
      $request_id = $args[0];
      $actionplan_id = $args[1];
      $sql = "UPDATE ".XOCP_PREFIX."idp_report_SELF_1 SET return_note = '', status_cd = 'approval1', submit_dttm = now()"
           . " WHERE request_id = '$request_id'"
           . " AND actionplan_id = '$actionplan_id'";
      $db->query($sql);
      $sql = "SELECT approval1_emp_id FROM ".XOCP_PREFIX."idp_report_SELF_1"
           . " WHERE request_id = '$request_id'"
           . " AND actionplan_id = '$actionplan_id'";
      $result = $db->query($sql);
      list($approval1_emp_id)=$db->fetchRow($result);
            list($approval1_job_id,
                 $approval1_employee_idx,
                 $approval1_job_nm,
                 $approval1_nm,
                 $approval1_nip,
                 $approval1_gender,
                 $approval1_jobstart,
                 $approval1_entrance_dttm,
                 $approval1_jobage,
                 $approval1_job_summary,
                 $approval1_person_id,
                 $approval1_user_idx,
                 $approval1_first_assessor_job_id,
                 $approval1_next_assessor_job_id)=_hris_getinfobyemployeeid($approval1_emp_id);
      
      
      _idp_send_notification($approval1_user_idx,$request_id,"_IDP_YOUHAVEAPPROVAL1REPORT","\$report_request_id=${request_id};\$report_actionplan_id=${actionplan_id};","EMPLOYEESUBMITREPORT",$user_id);
      
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
      $actionplan_id = $args[1];
      $chapter = _parseForm($args[2]);
      $rating = _parseForm($args[3]);
      $idea = _parseForm($args[4]);
      
      if(isset($rating)&&is_array($rating)) {
         for($i=1;$i<=2;$i++) {
            $v = "r_0${i}";
            $w = "remark_r_0${i}";
            if(isset($rating[$v])) {
               $$v = $rating[$v];
            } else {
               $$v = 0;
            }
            if(isset($rating[$w])) {
               $$w = addslashes(trim($rating[$w]));
            } else {
               $$w = "";
            }
         }
      }
      $sql = "UPDATE ".XOCP_PREFIX."idp_report_SELF_1 SET "
           . "r_01 = '$r_01',"
           . "r_02 = '$r_02',"
           . "remark_r_01 = '$remark_r_01',"
           . "remark_r_02 = '$remark_r_02'"
           . " WHERE request_id = '$request_id'"
           . " AND actionplan_id = '$actionplan_id'";
      $db->query($sql);
   
      if(isset($idea)&&count($idea)>0) {
         $sql = "SELECT idea_id FROM ".XOCP_PREFIX."idp_report_SELF_1_ideas WHERE request_id = '$request_id' AND actionplan_id = '$actionplan_id'";
         $result = $db->query($sql);
         if($db->getRowsNum($result)>0) {
            while(list($idea_id)=$db->fetchRow($result)) {
               $text = addslashes(trim($idea["textareaidea_${idea_id}"]));
               $start_dttm = $idea["idea_start_dttm_${idea_id}"];
               $stop_dttm = $idea["idea_stop_dttm_${idea_id}"];
               $sql = "UPDATE ".XOCP_PREFIX."idp_report_SELF_1_ideas SET "
                    . "idea_txt = '$text'"
                    . " WHERE request_id = '$request_id' AND actionplan_id = '$actionplan_id'"
                    . " and idea_id = '$idea_id'";
               $db->query($sql);
            }
         }
      }
      
      if(isset($chapter)&&count($chapter)>0) {
         $sql = "SELECT chapter_id FROM ".XOCP_PREFIX."idp_report_SELF_1_chapters WHERE request_id = '$request_id' AND actionplan_id = '$actionplan_id'";
         $result = $db->query($sql);
         if($db->getRowsNum($result)>0) {
            while(list($chapter_id)=$db->fetchRow($result)) {
               $text = addslashes(trim($chapter["textareachapter_${chapter_id}"]));
               $sql = "UPDATE ".XOCP_PREFIX."idp_report_SELF_1_chapters SET "
                    . "chapter_txt = '$text'"
                    . " WHERE request_id = '$request_id' AND actionplan_id = '$actionplan_id'"
                    . " and chapter_id = '$chapter_id'";
               $db->query($sql);
            }
         }
      }
      
      
   }
   
}

} /// HRIS_IDPMETHODSELFAJAX_DEFINED
?>