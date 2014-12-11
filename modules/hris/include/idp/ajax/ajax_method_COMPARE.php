<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/include/idp/ajax/ajax_method_COMPARE.php     //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2010-07-28                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_IDPMETHODCOMPAREAJAX_DEFINED') ) {
   define('HRIS_IDPMETHODCOMPAREAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");
require_once(XOCP_DOC_ROOT."/modules/hris/include/idp/idp.php");
require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");

class _idp_class_method_COMPARE_ajax extends AjaxListener {
   
   function _idp_class_method_COMPARE_ajax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/hris/include/idp/ajax/ajax_method_COMPARE.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_saveReport","app_submitReport","app_approval1Report",
                                             "app_approval2Report","app_newIdea","app_deleteIdea",
                                             "app_return1Report","app_divisionManagerReturnReport",
                                             "app_saveActivityReport","app_beforeFinishActivity",
                                             "app_finishActivity","app_newSubject","app_deleteSubject",
                                             "app_saveReportSubject","app_reeditSubject");
      $this->setReqPOST();
   }
   
   function app_reeditSubject($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $actionplan_id = $args[1];
      $subject_id = $args[2];
      
      $sql = "UPDATE ".XOCP_PREFIX."idp_report_COMPARE_subject SET "
           . "read_only = '0'"
           . " WHERE request_id = '$request_id' AND actionplan_id = '$actionplan_id'"
           . " AND subject_id = '$subject_id'";
      $db->query($sql);
      
      return array($subject_id,$this->renderSubjectEdit($request_id,$actionplan_id,$subject_id));
   }
   
   function renderSubjectEdit($request_id,$actionplan_id,$subject_id) {
      $db=&Database::getInstance();
      $sql = "SELECT subject_txt,subject_dttm"
           . " FROM ".XOCP_PREFIX."idp_report_COMPARE_subject"
           . " WHERE request_id = '$request_id'"
           . " AND actionplan_id = '$actionplan_id'"
           . " AND subject_id = '$subject_id'";
      $result = $db->query($sql);
      $ret = "";
      if($db->getRowsNum($result)>0) {
         list($subject_txt,$subject_dttm)=$db->fetchRow($result);
         if(1) {
            $ret .= "<div style='border:1px solid #bbb;border-bottom:0px solid #bbb;text-align:center;background-color:#ccc;padding:2px;'>Subject No. : $subject_id</div>"
            
            
                       . "<table class='xxfrm' style='width:100%;'>"
                       . "<colgroup><col width='100'/><col/></colgroup>"
                       . "<tbody>"
                       . "<tr><td>Coaching Time</td><td>"
                       . "<span id='sp_subjectdttm_${subject_id}' class='xlnk' onclick='_changedatetime(\"sp_subjectdttm_${subject_id}\",\"inp_subject_dttm_${subject_id}\",\"datetime\",false,false);'>".sql2ind($subject_dttm)."</span>"
                       . "<input type='hidden' id='inp_subject_dttm_${subject_id}' name='inp_subject_dttm_${subject_id}' value='$subject_dttm'/>"
                       . "</td></tr>"
                       . "<tr><td>Subject</td><td>"
                       . "<textarea id='textareasubject_${subject_id}' style='height:100px;width:95%;'>$subject_txt</textarea>"
                       . "</td></tr>"
                       . "</tbody></table>"
                       . "<div><div style='border:1px solid #bbb;border-top:0;border-bottom:0;text-align:right;background-color:#ddd;padding:5px;'>"
                       . "<span id='ideaprogress_${subject_id}'></span>&nbsp;<input type='button' value='Add Follow Up' onclick='new_idea(\"$subject_id\",this,event);'/></div>"
                       . "<table class='xxlist' style='border:1px solid #bbb;width:100%;'>"
                       . "<colgroup><col width='50%'/><col/></colgroup>"
                       . "<thead><tr><td>Follow Up</td><td>Schedule</td></tr></thead>"
                       . "<tbody id='tbodyidea_${subject_id}'>";
            ////////////////////////////////////////////////////////////////////////////////////////////////////
            
            $sql = "SELECT idea_id,idea_txt,start_dttm,stop_dttm"
                 . " FROM ".XOCP_PREFIX."idp_report_COMPARE_ideas"
                 . " WHERE request_id = '$request_id'"
                 . " AND actionplan_id = '$actionplan_id'"
                 . " AND subject_id = '$subject_id'"
                 . " ORDER BY idea_id";
            $result_idea = $db->query($sql);
            $ideas = "";
            if($db->getRowsNum($result_idea)>0) {
               while(list($idea_id,$idea_txt,$idea_start_dttm,$idea_stop_dttm)=$db->fetchRow($result_idea)) {
                  if(1) {
                     $ret .= "<tr id='tridea_${idea_id}_${subject_id}'><td>"
                             . "<input type='text' style='width:95%;' id='textareaidea_${idea_id}_${subject_id}' name='textareaidea_${idea_id}_${subject_id}' value='$idea_txt'/>"
                             . "<div style='padding:2px;'><input class='sbtn' type='button' onclick='delete_idea(\"$subject_id\",\"$idea_id\",this,event);' value='Delete Follow Up'/></div>"
                             . "</td>"
                             . "<td valign='top'><span class='xlnk' id='sp_ideastart_${idea_id}_${subject_id}' onclick='_changedatetime(\"sp_ideastart_${idea_id}_${subject_id}\",\"idea_start_dttm_${idea_id}_${subject_id}\",\"date\",true,false)'>".sql2ind($idea_start_dttm,"date")."</span> - "
                             . "<span class='xlnk' id='sp_ideastop_${idea_id}_${subject_id}' onclick='_changedatetime(\"sp_ideastop_${idea_id}_${subject_id}\",\"idea_stop_dttm_${idea_id}_${subject_id}\",\"date\",true,false);'>".sql2ind($idea_stop_dttm,"date")."</span>"
                             . "<input type='hidden' id='idea_start_dttm_${idea_id}_${subject_id}' name='idea_start_dttm_${idea_id}_${subject_id}' value='$idea_start_dttm'/>"
                             . "<input type='hidden' id='idea_stop_dttm_${idea_id}_${subject_id}' name='idea_stop_dttm_${idea_id}_${subject_id}' value='$idea_stop_dttm'/>"
                             . "</td></tr>";
                  } else {
                     $ret .= "<tr id='tridea_${idea_id}_${subject_id}'><td>"
                             . "$idea_txt"
                             . "</td>"
                             . "<td>".sql2ind($idea_start_dttm,"date")." - "
                             . sql2ind($idea_stop_dttm,"date")
                             . "</td></tr>";
                  }
               }
               $ret .= "<tr style='display:none;' id='trempty_idea_${subject_id}'><td style='text-align:center;font-style:italic;' colspan='2'>"
                          . _EMPTY
                          . "</td></tr>";
            } else {
               $ret .= "<tr id='trempty_idea_${subject_id}'><td style='text-align:center;font-style:italic;' colspan='2'>"
                          . _EMPTY
                          . "</td></tr>";
            }
         
            
            ////////////////////////////////////////////////////////////////////////////////////////////////////
            $ret .= "</tbody></table></div>"
                       . "<div id='btndelsubject_${subject_id}' style='padding:5px;background-color:#ccc;border:1px solid #bbb;border-top:0;text-align:right;'>"
                       . "<span id='progress_subject_${subject_id}'></span>"
                       . "&nbsp;<input type='button' onclick='save_subject(\"$subject_id\",0,this,event);' value='Save Subject'/>"
                       . "&nbsp;<input type='button' onclick='save_subject(\"$subject_id\",1,this,event);' value='Finish'/>"
                       . "&nbsp;&nbsp;<input type='button' onclick='delete_subject(\"$subject_id\",this,event);' value='Delete Subject'/>"
                       . "</div>";
         }
      }
      return $ret;
   }
   
   function renderSubjectReadOnly($request_id,$actionplan_id,$subject_id) {
      $db=&Database::getInstance();
      $sql = "SELECT subject_txt,subject_dttm"
           . " FROM ".XOCP_PREFIX."idp_report_COMPARE_subject"
           . " WHERE request_id = '$request_id'"
           . " AND actionplan_id = '$actionplan_id'"
           . " AND subject_id = '$subject_id'";
      $result = $db->query($sql);
      $ret = "";
      if($db->getRowsNum($result)>0) {
         list($subject_txt,$subject_dttm)=$db->fetchRow($result);
         $ret = "<div style='border:1px solid #bbb;border-bottom:0px solid #bbb;text-align:center;background-color:#ccc;padding:2px;'>Subject No. : $subject_id</div>"
              . "<table class='xxfrm' style='width:100%;'><colgroup><col width='100'/><col/></colgroup><tbody>"
              . "<tr><td>Coaching Time</td><td>".sql2ind($subject_dttm)."</td></tr>"
              . "<tr><td>Subject</td><td>"
              . "<div id='textareasubject_${subject_id}' style='white-space:pre;padding:5px;'>$subject_txt</div>"
              . "</td></tr>"
              . "</tbody></table>"
              . "<div>"
              
              . "<table class='xxlist' style='border:1px solid #bbb;width:100%;'>"
              . "<colgroup><col width='50%'/><col/></colgroup>"
              . "<thead><tr><td>Follow Up</td><td>Schedule</td></tr></thead>"
              . "<tbody id='tbodyidea_${subject_id}'>";
         
         
         
            $sql = "SELECT idea_id,idea_txt,start_dttm,stop_dttm"
                 . " FROM ".XOCP_PREFIX."idp_report_COMPARE_ideas"
                 . " WHERE request_id = '$request_id'"
                 . " AND actionplan_id = '$actionplan_id'"
                 . " AND subject_id = '$subject_id'"
                 . " ORDER BY idea_id";
            $result_idea = $db->query($sql);
            $ideas = "";
            if($db->getRowsNum($result_idea)>0) {
               while(list($idea_id,$idea_txt,$idea_start_dttm,$idea_stop_dttm)=$db->fetchRow($result_idea)) {
                  $ret .= "<tr id='tridea_${idea_id}_${subject_id}'><td>"
                        . "$idea_txt"
                       . "</td>"
                       . "<td valign='top'>"
                       . sql2ind($idea_start_dttm,"date")." - "
                       . sql2ind($idea_stop_dttm,"date")
                       . "</td></tr>";
               }
               $ret .= "<tr style='display:none;' id='trempty_idea_${subject_id}'><td style='text-align:center;font-style:italic;' colspan='2'>"
                          . _EMPTY
                          . "</td></tr>";
            } else {
               $ret .= "<tr id='trempty_idea_${subject_id}'><td style='text-align:center;font-style:italic;' colspan='2'>"
                          . _EMPTY
                          . "</td></tr>";
            }
         
         
         
         
         $ret .= "</tbody></table></div>"
              . "<div id='btndelsubject_${subject_id}' style='padding:5px;background-color:#ccc;border:1px solid #bbb;border-top:0;text-align:right;'>"
              . "<span id='progress_subject_${subject_id}'></span>"
              . "&nbsp;<input type='button' onclick='reedit_subject(\"$subject_id\",this,event);' value='Edit Subject'/>"
              . "</div>";
      }
      
      return $ret;
   }
   
   function app_saveReportSubject($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $actionplan_id = $args[1];
      $subject_id = $args[2];
      $subject = $args[3];
      $idea = _parseForm($args[4]);
      $read_only = $args[5]+0;
      $subject_dttm = $args[6];
      
      if(isset($idea)&&count($idea)>0) {
         $sql = "SELECT idea_id FROM ".XOCP_PREFIX."idp_report_COMPARE_ideas WHERE request_id = '$request_id' AND actionplan_id = '$actionplan_id'";
         $result = $db->query($sql);
         if($db->getRowsNum($result)>0) {
            while(list($idea_id)=$db->fetchRow($result)) {
               $text = addslashes(trim($idea["textareaidea_${idea_id}_${subject_id}"]));
               $start_dttm = $idea["idea_start_dttm_${idea_id}_${subject_id}"];
               $stop_dttm = $idea["idea_stop_dttm_${idea_id}_${subject_id}"];
               $sql = "UPDATE ".XOCP_PREFIX."idp_report_COMPARE_ideas SET "
                    . "idea_txt = '$text',"
                    . "start_dttm = '$start_dttm',"
                    . "stop_dttm = '$stop_dttm'"
                    . " WHERE request_id = '$request_id' AND actionplan_id = '$actionplan_id'"
                    . " AND subject_id = '$subject_id'"
                    . " AND idea_id = '$idea_id'";
               $db->query($sql);
            }
         }
      }
      
      $subject_txt = addslashes(urldecode($subject));
      $sql = "UPDATE ".XOCP_PREFIX."idp_report_COMPARE_subject SET "
           . "subject_txt = '$subject_txt',"
           . "subject_dttm = '$subject_dttm',"
           . "read_only = '$read_only'"
           . " WHERE request_id = '$request_id' AND actionplan_id = '$actionplan_id'"
           . " AND subject_id = '$subject_id'";
      $db->query($sql);
      
      return array($subject_id,$this->renderSubjectReadOnly($request_id,$actionplan_id,$subject_id),$read_only);
      
   }
   
   
   
   
   function app_deleteSubject($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $actionplan_id = $args[1];
      $subject_id = $args[2];
      $sql = "DELETE FROM ".XOCP_PREFIX."idp_report_COMPARE_subject"
           . " WHERE request_id = '$request_id' AND actionplan_id = '$actionplan_id'"
           . " and subject_id = '$subject_id'";
      $db->query($sql);
      $sql = "DELETE FROM ".XOCP_PREFIX."idp_report_COMPARE_ideas"
           . " WHERE request_id = '$request_id' AND actionplan_id = '$actionplan_id'"
           . " and subject_id = '$subject_id'";
      $db->query($sql);
   }
   
   function app_newSubject($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $actionplan_id = $args[1];
      $sql = "SELECT MAX(subject_id) FROM ".XOCP_PREFIX."idp_report_COMPARE_subject WHERE request_id = '$request_id' AND actionplan_id = '$actionplan_id'";
      $result = $db->query($sql);
      $subject_id = 0;
      if($db->getRowsNum($result)>0) {
         list($subject_id)=$db->fetchRow($result);
      }
      $subject_id++;
      $sql = "INSERT INTO ".XOCP_PREFIX."idp_report_COMPARE_subject (request_id,actionplan_id,subject_id,subject_dttm) VALUES ('$request_id','$actionplan_id','$subject_id',now())";
      $db->query($sql);
      /*
      $text = "<div style='text-align:center;background-color:#eee;padding:2px;'>Subject No. : $subject_id</div>"
            . "<div id='textareasubject_${subject_id}' style='height:200px;'></div>"
            . "<div style='padding:2px;text-align:right;'>[<span class='xlnk' onclick='delete_subject(\"$subject_id\",this,event);'>delete subject</span>]</div>";
      */
      
      $text = $this->renderSubjectEdit($request_id,$actionplan_id,$subject_id);
      
      return array($subject_id,$text);
   }
   
   function app_divisionManagerReturnReport($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $request_id = $args[0];
      $actionplan_id = $args[1];
      $return_note = addslashes(trim(urldecode($args[2])));
      $sql = "UPDATE ".XOCP_PREFIX."idp_report_COMPARE SET status_cd = 'approval1', return_note = '$return_note'"
           . " WHERE request_id = '$request_id'"
           . " AND actionplan_id = '$actionplan_id'";
      $db->query($sql);
      $sql = "SELECT approval1_emp_id,request_id,actionplan_id FROM ".XOCP_PREFIX."idp_report_COMPARE"
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
      $sql = "UPDATE ".XOCP_PREFIX."idp_report_COMPARE SET status_cd = 'prepared', return_note = '$return_note'"
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
      $sql = "DELETE FROM ".XOCP_PREFIX."idp_report_COMPARE_ideas"
           . " WHERE request_id = '$request_id' AND actionplan_id = '$actionplan_id'"
           . " and idea_id = '$idea_id'";
      $db->query($sql);
   }
   
   function app_newIdea($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $actionplan_id = $args[1];
      $sql = "SELECT MAX(idea_id) FROM ".XOCP_PREFIX."idp_report_COMPARE_ideas WHERE request_id = '$request_id' AND actionplan_id = '$actionplan_id'";
      $result = $db->query($sql);
      $idea_id = 0;
      if($db->getRowsNum($result)>0) {
         list($idea_id)=$db->fetchRow($result);
      }
      $idea_id++;
      $sql = "INSERT INTO ".XOCP_PREFIX."idp_report_COMPARE_ideas (request_id,actionplan_id,idea_id) VALUES ('$request_id','$actionplan_id','$idea_id')";
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
   
      
      $sql = "UPDATE ".XOCP_PREFIX."idp_report_COMPARE SET status_cd = 'completed', approval2_dttm = now()"
           . " WHERE request_id = '$request_id'"
           . " AND actionplan_id = '$actionplan_id'";
      $db->query($sql);
      
      $sql = "SELECT request_id,actionplan_id FROM ".XOCP_PREFIX."idp_report_COMPARE"
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
   
      $sql = "SELECT approval2_emp_id FROM ".XOCP_PREFIX."idp_report_COMPARE"
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
         $sql = "UPDATE ".XOCP_PREFIX."idp_report_COMPARE SET status_cd = 'approval2', approval1_dttm = now(), return_note = ''"
              . " WHERE request_id = '$request_id'"
              . " AND actionplan_id = '$actionplan_id'";
         $db->query($sql);
         
         _idp_send_notification($approval2_user_idx,$request_id,"_IDP_YOUHAVEAPPROVAL2REPORT","\$report_request_id=${request_id};\$report_actionplan_id=${actionplan_id};","SMAPPROVAL1REPORT",$user_id);
      
      } else {
         $sql = "UPDATE ".XOCP_PREFIX."idp_report_COMPARE SET status_cd = 'completed', approval1_dttm = now(), return_note = ''"
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
      $sql = "UPDATE ".XOCP_PREFIX."idp_report_COMPARE SET return_note = '', status_cd = 'approval1', submit_dttm = now()"
           . " WHERE request_id = '$request_id'"
           . " AND actionplan_id = '$actionplan_id'";
      $db->query($sql);
      $sql = "SELECT approval1_emp_id FROM ".XOCP_PREFIX."idp_report_COMPARE"
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
      $company_profile = addslashes(urldecode($args[2]));
      $industry = addslashes(urldecode($args[3]));
      $comparative_item = addslashes(urldecode($args[4]));
      $finding = addslashes(urldecode($args[5]));
      $idea = _parseForm($args[6]);
      
      $sql = "UPDATE ".XOCP_PREFIX."idp_report_COMPARE SET "
           . "company_profile = '$company_profile',"
           . "industry = '$industry',"
           . "comparative_item = '$comparative_item',"
           . "finding = '$finding'"
           . " WHERE request_id = '$request_id'"
           . " AND actionplan_id = '$actionplan_id'";
      $db->query($sql);
      
      if(isset($idea)&&count($idea)>0) {
         $sql = "SELECT idea_id FROM ".XOCP_PREFIX."idp_report_COMPARE_ideas WHERE request_id = '$request_id' AND actionplan_id = '$actionplan_id'";
         $result = $db->query($sql);
         if($db->getRowsNum($result)>0) {
            while(list($idea_id)=$db->fetchRow($result)) {
               $text = addslashes(trim($idea["textareaidea_${idea_id}"]));
               $start_dttm = $idea["idea_start_dttm_${idea_id}"];
               $stop_dttm = $idea["idea_stop_dttm_${idea_id}"];
               $sql = "UPDATE ".XOCP_PREFIX."idp_report_COMPARE_ideas SET "
                    . "idea_txt = '$text'"
                    . " WHERE request_id = '$request_id' AND actionplan_id = '$actionplan_id'"
                    . " and idea_id = '$idea_id'";
               $db->query($sql);
            }
         }
      }
      
   }
   
}

} /// HRIS_IDPMETHODCOMPAREAJAX_DEFINED
?>