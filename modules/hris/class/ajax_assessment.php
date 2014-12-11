<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_assessment.php                  //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-08-31                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_ASSESSMENTAJAX_DEFINED') ) {
   define('HRIS_ASSESSMENTAJAX_DEFINED', TRUE);

define("ACL_DELTA",1);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");
require_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
require_once(XOCP_DOC_ROOT."/modules/hris/include/assessment.php");
require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");

class _hris_class_AssessmentAjax extends AjaxListener {
   
   function _hris_class_AssessmentAjax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/hris/class/ajax_assessment.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_getNextQuestions","app_getPreviousQuestions","app_saveAssessment",
                            "app_loadMatrix","app_saveNotes","app_deleteNotes","app_getACLHistory");
   }
   
   function app_getACLHistory($args) {
      $db=&Database::getInstance();
      $competency_id = $args[0];
      $acl = $args[1];
      $behaviour_id = $args[2];
      $ca_id = $args[3];
      
      $asid = $_SESSION["hris_assessment_asid"];
      $employee_id = $_SESSION["assessment_employee_id"];
      $assessor_id = $_SESSION["self_employee_id"];
      
      $sql = "SELECT a.asid,b.session_nm,a.answer"
           . " FROM ".XOCP_PREFIX."employee_ca_detail_session a"
           . " LEFT JOIN ".XOCP_PREFIX."assessment_session b USING(asid)"
           . " WHERE a.asid < '$asid'"
           . " AND a.employee_id = '$employee_id'"
           . " AND a.assessor_id = '$assessor_id'"
           . " AND a.competency_id = '$competency_id'"
           . " AND a.proficiency_lvl = '$acl'"
           . " AND a.behaviour_id = '$behaviour_id'"
           . " AND a.ca_id = '$ca_id'"
           . " ORDER BY a.asid";
      $result = $db->query($sql);
      _debuglog($sql);
      $ret = "<table class='xxlist' style='width:100%;'><thead><tr><td>Assessment Session</td><td style='text-align:center;'>Answer</td></thead><tbody><tr>";
      if($db->getRowsNum($result)>0) {
         while(list($asid,$session_nm,$answer)=$db->fetchRow($result)) {
            $answer_txt = $answer-1;
            $ret .= "<tr><td>$session_nm</td><td style='text-align:center;'>$answer_txt</td></tr>";
         }
      } else {
         $ret .= "<tr><td>No data.</td></tr>";
      }
      $ret .= "</tbody></table>";
      return $ret;
   }
   
   function app_deleteNotes($args) {
      $db=&Database::getInstance();
      $note_id = $args[0];
      $competency_id = $args[1];
      $acl = $args[2];
      $behaviour_id = $args[3];
      $ca_id = $args[4];
      
      $asid = $_SESSION["hris_assessment_asid"];
      $employee_id = $_SESSION["assessment_employee_id"];
      $assessor_id = $_SESSION["self_employee_id"];
      
      if($note_id>0) {
         $sql = "DELETE FROM ".XOCP_PREFIX."employee_ca_notes"
              . " WHERE note_id = '$note_id'";
         $db->query($sql);
      }
      $note_id = 0;
      return array($note_id,$acl,$behaviour_id,$ca_id,$competency_id);
   }
   
   function app_saveNotes($args) {
      $db=&Database::getInstance();
      $note_id = $args[0];
      $competency_id = $args[1];
      $acl = $args[2];
      $behaviour_id = $args[3];
      $ca_id = $args[4];
      $note_txt = addslashes(trim(urldecode($args[5])));
      
      $asid = $_SESSION["hris_assessment_asid"];
      $employee_id = $_SESSION["assessment_employee_id"];
      $assessor_id = $_SESSION["self_employee_id"];
      
      if($note_id>0) {
         $sql = "UPDATE ".XOCP_PREFIX."employee_ca_notes SET "
              . "note_txt = '$note_txt',"
              . "update_dttm = now()"
              . " WHERE note_id = '$note_id'";
         $db->query($sql);
      } else {
         $sql = "INSERT INTO ".XOCP_PREFIX."employee_ca_notes (note_txt,asid,employee_id,assessor_id,competency_id,proficiency_lvl,behaviour_id,ca_id,created_user_id,update_dttm)"
              . " VALUES ('$note_txt','$asid','$employee_id','$assessor_id','$competency_id','$acl','$behaviour_id','$ca_id','$user_id',now())";
         $db->query($sql);
         $note_id = $db->getInsertId();
      }
      return array($note_id,$acl,$behaviour_id,$ca_id,$competency_id);
   }
   
   function app_loadMatrix($args) {
      $db=&Database::getInstance();
      $emps = explode("-",$args[0]);
      $self_employee_id = $_SESSION["self_employee_id"];
      $asid = $_SESSION["hris_assessment_asid"];
      $jobs = array();
      if(count($emps)>0) {
         foreach($emps as $emp) {
            list($employee_id,$job_id)=explode(".",$emp);
            $jobs[$job_id] = 1;
            $empjob[$employee_id][$job_id] = 1;
         }
      }
      
      ksort($jobs);
      
      if($_SESSION["assessment_page"]=="assessment_idp") {
         $arr_idp_comp = array();
         $sql = "SELECT idp_request_id FROM ".XOCP_PREFIX."assessment_session WHERE asid = '$asid'";
         $rreq = $db->query($sql);
         list($idp_request_id)=$db->fetchRow($rreq);
         $sql = "SELECT competency_id FROM ".XOCP_PREFIX."idp_request_competency WHERE request_id = '$idp_request_id'";
         $rreqc = $db->query($sql);
         if($db->getRowsNum($rreqc)>0) {
            while(list($idp_competency_id)=$db->fetchRow($rreqc)) {
               $arr_idp_comp[$idp_competency_id] = 1;
            }
         }
      } else {
         $arr_idp_comp = NULL;
      }
      
      if(count($jobs)>0) {
         foreach($jobs as $job_id=>$v) {
            $sql = "SELECT a.rcl,a.itj,a.competency_id,b.compgroup_id,b.competency_cd,"
                 . "b.competency_abbr,b.competency_nm,b.competency_class,b.desc_en,b.desc_id,(b.competency_class+0) as urcl"
                 . " FROM ".XOCP_PREFIX."job_competency a"
                 . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
                 . " WHERE a.job_id = '$job_id'"
                 . " ORDER BY b.compgroup_id,urcl,b.competency_id";
            $resrcl = $db->query($sql);
            if($db->getRowsNum($resrcl)>0) {
               while(list($rcl,$itj,$competency_id,$compgroup_id,$competency_cd,
                          $competency_abbr,$competency_nm,$competency_class,$desc_en,$desc_id)=$db->fetchRow($resrcl)) {
                  
                  if($_SESSION["assessment_page"]=="assessment_idp") {
                      if(!isset($arr_idp_comp[$competency_id])) {
                          continue;
                      }
                  }
                  
                  $arr_xttl_rcl[$job_id] += ($rcl*$itj);
                  if($competency_abbr=="") {
                     $competency_abbr = "-";
                  }
                  $arr_comp[$competency_id] = array($competency_id,$compgroup_id,$competency_class,$competency_nm,$competency_abbr,$desc_en,$desc_id);
                  $arr_job_competency[$job_id][$competency_id] = array($job_id,$competency_id,$rcl,$itj);
                  $sql = "SELECT behaviour_id,proficiency_lvl,behaviour_en_txt,behaviour_id_txt"
                       . " FROM ".XOCP_PREFIX."compbehaviour"
                       . " WHERE competency_id = '$competency_id'"
                       . " ORDER BY proficiency_lvl";
                  $rbh = $db->query($sql);
                  if($db->getRowsNum($rbh)>0) {
                     while(list($behaviour_id,$lvl,$xen,$xid)=$db->fetchRow($rbh)) {
                        $arr_desclvl[$competency_id][$lvl] = array($xen,$xid);
                     }
                  }
               }
            }
         }
         
         $arr_ccl = array();
         foreach($empjob as $employee_id=>$v) {
            foreach($v as $job_id=>$w) {
               if($_SESSION["assessor360"]==1) {
                  $sql = "SELECT e.fulfilled,a.last_level,a.ccl,a.competency_id,(TO_DAYS(now())-TO_DAYS(a.update_dttm)) as la,a.update_dttm,a.cclxxx"
                       . " FROM ".XOCP_PREFIX."employee_competency360_session a"
                       . " LEFT JOIN ".XOCP_PREFIX."employee_level360_session e ON e.asid = a.asid AND e.employee_id = a.employee_id AND e.competency_id = a.competency_id AND e.assessor_id = a.assessor_id AND e.proficiency_lvl = CONVERT(FLOOR(a.ccl),CHAR)"
                       . " WHERE a.employee_id = '$employee_id'"
                       . " AND a.assessor_id = '$self_employee_id'"
                       . " AND a.asid = '$asid'";
               } else {
                  $sql = "SELECT e.fulfilled,a.last_level,a.ccl,a.competency_id,(TO_DAYS(now())-TO_DAYS(a.update_dttm)) as la,a.update_dttm,a.cclxxx"
                       . " FROM ".XOCP_PREFIX."employee_competency_session a"
                       . " LEFT JOIN ".XOCP_PREFIX."employee_level_session e ON e.asid = a.asid AND e.employee_id = a.employee_id AND e.competency_id = a.competency_id AND e.assessor_id = a.assessor_id AND e.proficiency_lvl = CONVERT(FLOOR(a.ccl),CHAR)"
                       . " WHERE a.employee_id = '$employee_id' AND a.asid_update = '$asid'"
                       . " AND a.assessor_id = '$self_employee_id'"
                       . " AND a.asid = '$asid'";
               }
               $resccl = $db->query($sql);
               $ttl_ccl = 0;
               $ttl_rcl = 0;
               if($db->getRowsNum($resccl)>0) {
                  while(list($fulfilled,$last_level,$ccl,$competency_idx,$last_assess,$update_dttm,$cclxxx)=$db->fetchRow($resccl)) {
                     if($ccl>=1&&$fulfilled==0) continue;
                     $real_ccl = $cclxxx;
                     $ccl = floor($ccl);
                     list($job_idx,$competency_idy,$rcl,$itj)=$arr_job_competency[$job_id][$competency_idx];
                     $gap = number_format(($real_ccl-$rcl)*$itj,2,".","");
                     if($update_dttm=="0000-00-00 00:00:00") {
                        $last_assess = -1;
                     }
                     
                     if($ccl>($rcl+1)) {
                        $ccl = ($rcl+1);
                     }
                     $arr_ccl[$employee_id][$job_id][$competency_idx] = array($employee_id,$job_id,$competency_idx,$ccl,$rcl,$itj,$gap,$last_assess,$update_dttm,$last_level,number_format($real_ccl,2,".",""));
                  }
               }
            }
         }
         
         if(count($arr_comp)<=0) {
            return "NOCOMPETENCYDEFINED";
         }
         
         $ret_comp = array();
         foreach($arr_comp as $k=>$v) {
            $ret_comp[] = $v;
         }
         
         $ret_ccl = array();
         foreach($arr_ccl as $employee_id=>$v) {
            foreach($v as $job_id=>$w) {
               foreach($w as $competency_id=>$x) {
                  $ret_ccl[] = $x;
               }
            }
         }
         
         $ret_job = array();
         foreach($arr_job_competency as $job_id=>$v) {
            foreach($v as $competency_id=>$w) {
               $ret_job[] = $w;
            }
         }
         
         return array($ret_comp,$ret_ccl,$ret_job);
      }
      return "EMPTY";
   }
   
   function app_getNextQuestions($args) {
      $db=&Database::getInstance();
      $competency_id = $args[0];
      $asid = $_SESSION["hris_assessment_asid"];
      $self_employee_id = $_SESSION["self_employee_id"];
      $employee_id = $_SESSION["assessment_employee_id"];
      global $proficiency_level_name;
      
      if($_SESSION["assessor360"]==1) {
         $sql = "SELECT acl FROM ".XOCP_PREFIX."employee_competency360_session"
              . " WHERE employee_id = '$employee_id'"
              . " AND competency_id = '$competency_id'"
              . " AND assessor_id = '$self_employee_id'"
              . " AND asid = '$asid'";
      } else {
         $sql = "SELECT acl FROM ".XOCP_PREFIX."employee_competency_session"
              . " WHERE employee_id = '$employee_id'"
              . " AND competency_id = '$competency_id'"
              . " AND assessor_id = '$self_employee_id'"
              . " AND asid = '$asid'";
      }
      $result = $db->query($sql);
      list($acl)=$db->fetchRow($result);
      
      if($acl<4) {
         $acl++;
      }
      if($_SESSION["assessor360"]==1) {
         $sql = "UPDATE ".XOCP_PREFIX."employee_competency360 SET acl = '$acl'"
              . " WHERE employee_id = '$employee_id'"
              . " AND competency_id = '$competency_id'"
              . " AND assessor_id = '$self_employee_id'";
         $db->query($sql);
         $sql = "UPDATE ".XOCP_PREFIX."employee_competency360_session SET acl = '$acl'"
              . " WHERE employee_id = '$employee_id'"
              . " AND competency_id = '$competency_id'"
              . " AND assessor_id = '$self_employee_id'"
              . " AND asid = '$asid'";
         $db->query($sql);
      } else {
         $sql = "UPDATE ".XOCP_PREFIX."employee_competency SET acl = '$acl'"
              . " WHERE employee_id = '$employee_id'"
              . " AND competency_id = '$competency_id'";
         $db->query($sql);
         $sql = "UPDATE ".XOCP_PREFIX."employee_competency_session SET acl = '$acl'"
              . " WHERE employee_id = '$employee_id'"
              . " AND competency_id = '$competency_id'"
              . " AND assessor_id = '$self_employee_id'"
              . " AND asid = '$asid'";
         $db->query($sql);
      }
      
      return array($this->getQuestions($employee_id,$competency_id,$acl),
                   $proficiency_level_name[$acl]." ($acl)",$acl);
      
   }
   
   function app_getPreviousQuestions($args) {
      $db=&Database::getInstance();
      $competency_id = $args[0];
      $employee_id = $_SESSION["assessment_employee_id"];
      $asid = $_SESSION["hris_assessment_asid"];
      $self_employee_id = $_SESSION["self_employee_id"];
      global $proficiency_level_name;
      
      if($_SESSION["assessor360"]==1) {
         $sql = "SELECT acl FROM ".XOCP_PREFIX."employee_competency360_session"
              . " WHERE employee_id = '$employee_id'"
              . " AND competency_id = '$competency_id'"
              . " AND assessor_id = '$self_employee_id'"
              . " AND asid = '$asid'";
      } else {
         $sql = "SELECT acl FROM ".XOCP_PREFIX."employee_competency_session"
              . " WHERE employee_id = '$employee_id'"
              . " AND competency_id = '$competency_id'"
              . " AND assessor_id = '$self_employee_id'"
              . " AND asid = '$asid'";
      }
      $result = $db->query($sql);
      list($acl)=$db->fetchRow($result);
      if($acl>1) {
         $acl--;
      }
      if($_SESSION["assessor360"]==1) {
         $sql = "UPDATE ".XOCP_PREFIX."employee_competency360 SET acl = '$acl'"
              . " WHERE employee_id = '$employee_id'"
              . " AND competency_id = '$competency_id'"
              . " AND assessor_id = '$self_employee_id'";
         $db->query($sql);
         $sql = "UPDATE ".XOCP_PREFIX."employee_competency360_session SET acl = '$acl'"
              . " WHERE employee_id = '$employee_id'"
              . " AND competency_id = '$competency_id'"
              . " AND assessor_id = '$self_employee_id'"
              . " AND asid = '$asid'";
         $db->query($sql);
      } else {
         $sql = "UPDATE ".XOCP_PREFIX."employee_competency SET acl = '$acl'"
              . " WHERE employee_id = '$employee_id'"
              . " AND competency_id = '$competency_id'"
              . " AND assessor_id = '$self_employee_id'";
         $db->query($sql);
         $sql = "UPDATE ".XOCP_PREFIX."employee_competency_session SET acl = '$acl'"
              . " WHERE employee_id = '$employee_id'"
              . " AND competency_id = '$competency_id'"
              . " AND assessor_id = '$self_employee_id'"
              . " AND asid = '$asid'";
         $db->query($sql);
      }
      
      return array($this->getQuestions($employee_id,$competency_id,$acl),
                   $proficiency_level_name[$acl]. " ($acl)",$acl);
      
   }
   
   function calcResult($employee_id,$job_id,$competency_id) {
      $db=&Database::getInstance();
      $asid = $_SESSION["hris_assessment_asid"];
      $self_employee_id = $_SESSION["self_employee_id"];
      $rcl = $itj = $gap = 0;
      $ccl = 0;
      $last_level = 0;
      
      $sql = "SELECT compgroup_id FROM ".XOCP_PREFIX."competency WHERE competency_id = '$competency_id'";
      $result = $db->query($sql);
      list($compgroup_id)=$db->fetchRow($result);
      
      if($compgroup_id==3) {
         $answer_t = "grade";
      } else {
         $answer_t = "yesno";
      }
      
      $answer_t = "grade";
      
      $sql = "SELECT rcl,itj FROM ".XOCP_PREFIX."job_competency"
           . " WHERE job_id = '$job_id'"
           . " AND competency_id = '$competency_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($rcl,$itj)=$db->fetchRow($result);
      }
      $max_ccl = $rcl+1;
      if($max_ccl<=1) $max_ccl = 2;
      if($max_ccl>4) $max_ccl=4;
      $ttl_level_value = 0;
      for($i=1;$i<5;$i++) {
         if($i>$max_ccl) {
            
            if($_SESSION["assessor360"]==1) {
               $sql = "DELETE FROM ".XOCP_PREFIX."employee_level360"
                    . " WHERE employee_id = '$employee_id'"
                    . " AND competency_id = '$competency_id'"
                    . " AND proficiency_lvl = '$i'"
                    . " AND assessor_id = '$self_employee_id'";
               $db->query($sql);
               $sql = "DELETE FROM ".XOCP_PREFIX."employee_level360_session"
                    . " WHERE employee_id = '$employee_id'"
                    . " AND competency_id = '$competency_id'"
                    . " AND proficiency_lvl = '$i'"
                    . " AND assessor_id = '$self_employee_id'"
                    . " AND asid = '$asid'";
               $db->query($sql);
            } else {
               $sql = "DELETE FROM ".XOCP_PREFIX."employee_level"
                    . " WHERE employee_id = '$employee_id'"
                    . " AND competency_id = '$competency_id'"
                    . " AND proficiency_lvl = '$i'";
               $db->query($sql);
               $sql = "DELETE FROM ".XOCP_PREFIX."employee_level_session"
                    . " WHERE employee_id = '$employee_id'"
                    . " AND competency_id = '$competency_id'"
                    . " AND proficiency_lvl = '$i'"
                    . " AND assessor_id = '$self_employee_id'"
                    . " AND asid = '$asid'";
               $db->query($sql);
            }
            
            continue;
         }
         if($_SESSION["assessor360"]==1) {
            $sql = "SELECT fulfilled,level_value FROM ".XOCP_PREFIX."employee_level360_session"
                 . " WHERE employee_id = '$employee_id'"
                 . " AND competency_id = '$competency_id'"
                 . " AND proficiency_lvl = '$i'"
                 . " AND assessor_id = '$self_employee_id'"
                 . " AND asid = '$asid'";
         } else {
            $sql = "SELECT fulfilled,level_value FROM ".XOCP_PREFIX."employee_level_session"
                 . " WHERE employee_id = '$employee_id'"
                 . " AND competency_id = '$competency_id'"
                 . " AND proficiency_lvl = '$i'"
                 . " AND assessor_id = '$self_employee_id'" /// added 2012-01-02 12:19
                 . " AND asid = '$asid'";
         }
         $result = $db->query($sql);
         if($db->getRowsNum($result)==1) {
            list($fulfilled,$level_value)=$db->fetchRow($result);
            $ttl_level_value = _bctrim(bcadd($ttl_level_value,$level_value));
            if($fulfilled=="1") {
               $ccl = $i;
            } else if($fulfilled=="-1") {
               $last_level = $i;
               $ccl = _bctrim(bcadd($ccl,0));
            } else {
               break;
            }
         } else {
            break;
         }
      }
      
      $gap = $itj * ($ccl - $rcl);
      
      return array($ccl,$rcl,$itj,$gap,$last_level,$ttl_level_value);
   }
   
   function app_saveAssessment($args) {
      global $proficiency_level_name;
      $self_employee_id = $_SESSION["self_employee_id"];
      $db=&Database::getInstance();
      $arr = parseForm($args[0]);
      $arr_answer = array();
      $user_id = getUserID();
      $asid = $_SESSION["hris_assessment_asid"];
      $competency_idx = 0;
      foreach($arr as $k=>$v) {
         if(substr($k,0,6)=="answer") {
            list($competency_id,$acl,$behaviour_id,$ca_id,$answer)=explode("|",$v);
            $arr_answer[] = array($competency_id,$acl,$behaviour_id,$ca_id,$answer);
            $competency_idx = $competency_id;
         }
         $$k = addslashes(trim($v));
      }
      
      
      $sql = "SELECT compgroup_id FROM ".XOCP_PREFIX."competency WHERE competency_id = '$competency_idx'";
      $result = $db->query($sql);
      list($compgroup_id)=$db->fetchRow($result);
      
      if($compgroup_id==3) {
         $answer_t = "grade";
      } else {
         $answer_t = "yesno";
      }
      
      /// override 2011-12-09
      $answer_t = "grade";
      
      $employee_id = $_SESSION["assessment_employee_id"];
      $sql = "SELECT COUNT(*) FROM ".XOCP_PREFIX."compbehaviour_qa WHERE competency_id = '$competency_id' AND proficiency_lvl = '$acl'";
      $result = $db->query($sql);
      list($qcount)=$db->fetchRow($result);
      
      $sql = "SELECT competency_nm,competency_abbr FROM ".XOCP_PREFIX."competency WHERE competency_id = '$competency_idx'";
      $result = $db->query($sql);
      list($competency_nm,$competency_abbr)=$db->fetchRow($result);
      
      
      //////////////////// log /////////////////////////////////////////////////////////////////////////////
      list($emp_job_id,
           $emp_employee_id,
           $emp_job_nm,
           $emp_nm,
           $emp_nip,
           $emp_gender,
           $emp_jobstart,
           $emp_entrance_dttm,
           $emp_jobage,
           $emp_job_summary,
           $emp_person_id,
           $emp_user_id)=_hris_getinfobyemployeeid($employee_id);
      
      list($ass_job_id,
           $ass_employee_id,
           $ass_job_nm,
           $ass_nm,
           $ass_nip,
           $ass_gender,
           $ass_jobstart,
           $ass_entrance_dttm,
           $ass_jobage,
           $ass_job_summary,
           $ass_person_id,
           $ass_user_id)=_hris_getinfobyemployeeid($self_employee_id);
      
      $y_ans = 0;
      $n_ans = 0;
      $e_ans = 0;
      $o_ans = 0;
      $ttl_ans = 0;
      foreach($arr_answer as $ans) {
         list($competency_id,$acl,$behaviour_id,$ca_id,$answer)=$ans;
         
         $xans = $answer;
         
         if($xans==5) { //// grade = 4
            $y_ans++;
            $ttl_ans += 1;
         } else if($xans==4) { //// grade = 3
            $y_ans++;
            $ttl_ans += 0.75; /// original 0.8
         } else if($xans==3) { //// grade = 2
            $n_ans++;
            $ttl_ans += 0.5;  /// original 0.6
         } else if($xans==2) { //// grade = 1
            $n_ans++;
            $ttl_ans += 0.25; /// original 0.2
         } else if($xans==1) { //// grade = 0
            $n_ans++;
            $o_ans++;
            $ttl_ans += 0;
         } else { //// grade = empty
            $e_ans++;
         }
         
         if($_SESSION["assessor360"]==1) {
            $sql = "REPLACE INTO ".XOCP_PREFIX."employee_ca360_detail (employee_id,competency_id,proficiency_lvl,behaviour_id,ca_id,answer,assessor_id,update_dttm,update_user_id,answer_t)"
                 . " VALUES ('$employee_id','$competency_id','$acl','$behaviour_id','$ca_id','$xans','$self_employee_id',now(),'$user_id','$answer_t')";
            $db->query($sql);
            $sql = "REPLACE INTO ".XOCP_PREFIX."employee_ca360_detail_session (asid,employee_id,competency_id,proficiency_lvl,behaviour_id,ca_id,answer,assessor_id,update_dttm,update_user_id,answer_t)"
                 . " VALUES ('$asid','$employee_id','$competency_id','$acl','$behaviour_id','$ca_id','$xans','$self_employee_id',now(),'$user_id','$answer_t')";
            $db->query($sql);
         } else {
            $sql = "REPLACE INTO ".XOCP_PREFIX."employee_ca_detail (employee_id,competency_id,proficiency_lvl,behaviour_id,ca_id,answer,assessor_id,update_dttm,update_user_id,answer_t)"
                 . " VALUES ('$employee_id','$competency_id','$acl','$behaviour_id','$ca_id','$xans','$self_employee_id',now(),'$user_id','$answer_t')";
            $db->query($sql);
            $sql = "REPLACE INTO ".XOCP_PREFIX."employee_ca_detail_session (asid,employee_id,competency_id,proficiency_lvl,behaviour_id,ca_id,answer,assessor_id,update_dttm,update_user_id,answer_t)"
                 . " VALUES ('$asid','$employee_id','$competency_id','$acl','$behaviour_id','$ca_id','$xans','$self_employee_id',now(),'$user_id','$answer_t')";
            $db->query($sql);
         }
         
         _activitylog("ASSESSMENT",0,"Save answer $competency_nm\nemployee: $emp_nm\nassessor: $ass_nm\ncompetency_id: $competency_id\nacl: $acl\nbehaviour_id: $behaviour_id\nca_id: $ca_id\nanswer: $xans");
         
         //////////////////////////////////////////////////////////////////////////////////////////////////////
         
      }
      
      if(($y_ans+$n_ans)>0) {
         $level_value = _bctrim(bcdiv($ttl_ans,($y_ans+$n_ans)));
      } else {
         $level_value = 0;
      }
      
      ///if($y_ans==$qcount) {   /// level fulfilled ---> old behaviour
      if($e_ans==0&&$n_ans==0&&$y_ans>=0) {   /// level fulfilled
         if($_SESSION["assessor360"]==1) {
            $sql = "REPLACE INTO ".XOCP_PREFIX."employee_level360 (employee_id,competency_id,proficiency_lvl,fulfilled,assessor_id,update_dttm,update_user_id,level_value)"
                 . " VALUES ('$employee_id','$competency_id','$acl','1','$self_employee_id',now(),'$user_id','$level_value')";
            $db->query($sql);
            $sql = "REPLACE INTO ".XOCP_PREFIX."employee_level360_session (asid,employee_id,competency_id,proficiency_lvl,fulfilled,assessor_id,update_dttm,update_user_id,level_value)"
                 . " VALUES ('$asid','$employee_id','$competency_id','$acl','1','$self_employee_id',now(),'$user_id','$level_value')";
            $db->query($sql);
         } else {
            $sql = "REPLACE INTO ".XOCP_PREFIX."employee_level (employee_id,competency_id,proficiency_lvl,fulfilled,assessor_id,update_dttm,update_user_id,level_value)"
                 . " VALUES ('$employee_id','$competency_id','$acl','1','$self_employee_id',now(),'$user_id','$level_value')";
            $db->query($sql);
            $sql = "REPLACE INTO ".XOCP_PREFIX."employee_level_session (asid,employee_id,competency_id,proficiency_lvl,fulfilled,assessor_id,update_dttm,update_user_id,level_value)"
                 . " VALUES ('$asid','$employee_id','$competency_id','$acl','1','$self_employee_id',now(),'$user_id','$level_value')";
            $db->query($sql);
         }
         $fulfilled = 1;
      } elseif($e_ans>0) {                /// level unfinished
         if($_SESSION["assessor360"]==1) {
            $sql = "REPLACE INTO ".XOCP_PREFIX."employee_level360 (employee_id,competency_id,proficiency_lvl,fulfilled,assessor_id,update_dttm,update_user_id,level_value)"
                 . " VALUES ('$employee_id','$competency_id','$acl','0','$self_employee_id',now(),'$user_id','$level_value')";
            $db->query($sql);
            $sql = "REPLACE INTO ".XOCP_PREFIX."employee_level360_session (asid,employee_id,competency_id,proficiency_lvl,fulfilled,assessor_id,update_dttm,update_user_id,level_value)"
                 . " VALUES ('$asid','$employee_id','$competency_id','$acl','0','$self_employee_id',now(),'$user_id','$level_value')";
            $db->query($sql);
         } else {
            $sql = "REPLACE INTO ".XOCP_PREFIX."employee_level (employee_id,competency_id,proficiency_lvl,fulfilled,assessor_id,update_dttm,update_user_id,level_value)"
                 . " VALUES ('$employee_id','$competency_id','$acl','0','$self_employee_id',now(),'$user_id','$level_value')";
            $db->query($sql);
            $sql = "REPLACE INTO ".XOCP_PREFIX."employee_level_session (asid,employee_id,competency_id,proficiency_lvl,fulfilled,assessor_id,update_dttm,update_user_id,level_value)"
                 . " VALUES ('$asid','$employee_id','$competency_id','$acl','0','$self_employee_id',now(),'$user_id','$level_value')";
            $db->query($sql);
         }
         $fulfilled = 0;
      } else {                /// level unfilfilled
         if($_SESSION["assessor360"]==1) {
            $sql = "REPLACE INTO ".XOCP_PREFIX."employee_level360 (employee_id,competency_id,proficiency_lvl,fulfilled,assessor_id,update_dttm,update_user_id,level_value)"
                 . " VALUES ('$employee_id','$competency_id','$acl','-1','$self_employee_id',now(),'$user_id','$level_value')";
            $db->query($sql);
            $sql = "REPLACE INTO ".XOCP_PREFIX."employee_level360_session (asid,employee_id,competency_id,proficiency_lvl,fulfilled,assessor_id,update_dttm,update_user_id,level_value)"
                 . " VALUES ('$asid','$employee_id','$competency_id','$acl','-1','$self_employee_id',now(),'$user_id','$level_value')";
            $db->query($sql);
         } else {
            $sql = "REPLACE INTO ".XOCP_PREFIX."employee_level (employee_id,competency_id,proficiency_lvl,fulfilled,assessor_id,update_dttm,update_user_id,level_value)"
                 . " VALUES ('$employee_id','$competency_id','$acl','-1','$self_employee_id',now(),'$user_id','$level_value')";
            $db->query($sql);
            $sql = "REPLACE INTO ".XOCP_PREFIX."employee_level_session (asid,employee_id,competency_id,proficiency_lvl,fulfilled,assessor_id,update_dttm,update_user_id,level_value)"
                 . " VALUES ('$asid','$employee_id','$competency_id','$acl','-1','$self_employee_id',now(),'$user_id','$level_value')";
            $db->query($sql);
         }
         
         $fulfilled = -1;
         
         $sql = "DELETE FROM ".XOCP_PREFIX."employee_level360"
              . " WHERE employee_id = '$employee_id'"
              . " AND assessor_id = '$self_employee_id'"
              . " AND competency_id = '$competency_id'"
              . " AND proficiency_lvl > '$acl'";
         $db->query($sql);
         $sql = "DELETE FROM ".XOCP_PREFIX."employee_level360_session"
              . " WHERE asid = '$asid'"
              . " AND employee_id = '$employee_id'"
              . " AND assessor_id = '$self_employee_id'"
              . " AND competency_id = '$competency_id'"
              . " AND proficiency_lvl > '$acl'";
         $db->query($sql);
         $sql = "DELETE FROM ".XOCP_PREFIX."employee_level"
              . " WHERE employee_id = '$employee_id'"
              //. " AND assessor_id = '$self_employee_id'" //// commented 2012-01-02 12:00
              . " AND competency_id = '$competency_id'"
              . " AND proficiency_lvl > '$acl'";
         $db->query($sql);
         $sql = "DELETE FROM ".XOCP_PREFIX."employee_level_session"
              . " WHERE asid = '$asid'"
              . " AND employee_id = '$employee_id'"
              . " AND assessor_id = '$self_employee_id'"
              . " AND competency_id = '$competency_id'"
              . " AND proficiency_lvl > '$acl'";
         $db->query($sql);
         
      }
      
      list($ccl,$rcl,$itj,$gap,$last_level,$ttl_level_value)=$this->calcResult($employee_id,$_SESSION["empl_job_id"],$competency_id);
      _activitylog("ASSESSMENT",0,"Save level $competency_nm\nemployee: $emp_nm\nassessor: $ass_nm\nccl: $ccl\nrcl: $rcl\nlevel_value = $level_value");
      
      if($_SESSION["assessor360"]==1) {
         $sql = "UPDATE ".XOCP_PREFIX."employee_competency360 SET ccl = '$ccl', update_dttm = now(), asid_update = '$asid', last_level = '$last_level', cclxxx = '$ttl_level_value'"
              . " WHERE employee_id = '$employee_id' AND competency_id = '$competency_id' AND assessor_id = '$self_employee_id'";
         $db->query($sql);
         $sql = "UPDATE ".XOCP_PREFIX."employee_competency360_session SET ccl = '$ccl', update_dttm = now(), asid_update = '$asid', last_level = '$last_level', cclxxx = '$ttl_level_value'"
              . " WHERE asid = '$asid' AND employee_id = '$employee_id' AND competency_id = '$competency_id' AND assessor_id = '$self_employee_id'";
         $db->query($sql);
      } else {
         $sql = "UPDATE ".XOCP_PREFIX."employee_competency SET ccl = '$ccl', update_dttm = now(), assessor_id = '$self_employee_id', asid_update = '$asid', last_level = '$last_level', cclxxx = '$ttl_level_value'"
              . " WHERE employee_id = '$employee_id' AND competency_id = '$competency_id'";
         $db->query($sql);
         $sql = "UPDATE ".XOCP_PREFIX."employee_competency_session SET ccl = '$ccl', update_dttm = now(), assessor_id = '$self_employee_id', asid_update = '$asid', last_level = '$last_level', cclxxx = '$ttl_level_value'"
              . " WHERE asid = '$asid' AND employee_id = '$employee_id' AND competency_id = '$competency_id' AND assessor_id = '$self_employee_id'";
         $db->query($sql);
      }
      
      $gapxxx = ($ttl_level_value*$itj)-($rcl*$itj);
      
      $title = "<div style='padding:4px;'>Current Assessment Level : <span style='color:black;font-weight:bold;'>$acl (".$proficiency_level_name[$acl].")</span></div>";
      $calc = "<span style='font-weight:bold;color:black;'>CCL : ".number_format($ttl_level_value,2,".","")." / RCL : $rcl / ITJ : $itj / GAP : ".number_format($gapxxx,2,".","")."</span>";
      
      _calculate_competency($asid,$employee_id,$_SESSION["empl_job_id"]);
      
      $delta = $acl - $rcl;
      if($fulfilled==1) {
         $nextacl = $acl+1;
         if($nextacl<=4) {
            $gonext = "<div style='text-align:center;'>${title}Result : "
                    . ($ccl>=$acl?"<span style='color:blue;'>Level fulfilled.</span>":"<span style='color:red;'>Level unfulfilled.</span>")
                    . "<br/><br/>$calc<br/><br/>"
                    . "<input style='width:100px;' ".($acl==1?"disabled='1'":"")." type='button' value='Previous Level' onclick='new_prev(this,event);'/>&nbsp;"
                    . "<input style='width:80px;' type='button' value='Review' onclick='review(this,event);'/>&nbsp;"
                    . "<input style='width:100px;' ".($delta<ACL_DELTA?"":"disabled='1'")." type='button' value='Next Level' onclick='new_next(this,event);'/>"
                    . "<br/><br/>"
                    . ($gap<0?"<input type='button' value='Continue Later' onclick='confirmnotfinish(this,event);'/>&nbsp;":"")
                    . "<input style='width:130px;' type='button' ".($delta<0?"disabled='1'":"")." value='Finish' onclick='"
                    . ($delta<0?"confirmnotfinish(this,event);":($delta<ACL_DELTA?"confirmfinish(this,event);":"summarypage();"))
                    ."'/></div>";
         } else {
            $gonext = "<div style='text-align:center;'>${title}Result : <span style='color:blue;'>Highest level has been achieved.</span>"
                    . "<br/><br/>$calc<br/><br/>"
                    . "<input style='width:100px;' ".($acl==1?"disabled='1'":"")." type='button' value='Previous Level' onclick='new_prev(this,event);'/>&nbsp;"
                    . "<input style='width:80px;' type='button' value='Review' onclick='review(this,event);'/>&nbsp;"
                    . "<input style='width:100px;' disabled='1' type='button' value='Next Level' onclick='new_next(this,event);'/>"
                    . "<br/><br/>"
                    . "<input style='width:130px;' type='button' value='Finish' onclick='summarypage();'/></div>";
         
         }
      } else {
         if($e_ans>0) {
            $warn = "<div style='text-align:center;padding:10px;color:red;font-style:italic;'>There are still empty answer. Please review.</div>";
         } else {
            $warn = "<div style='text-align:center;padding:10px;color:red;'>&nbsp;</div>";
         }
         $gonext = "<div style='text-align:center;'>${title}Result : <span style='color:red;'>Level unfulfilled.</span>"
                 . "<br/>$warn$calc<br/><br/>"
                 . "<input style='width:100px;' ".($acl==1?"disabled='1'":"")." type='button' value='Previous Level' onclick='new_prev(this,event);'/>&nbsp;"
                 . "<input style='width:80px;' type='button' value='Review' onclick='review(this,event);'/>&nbsp;"
                 . "<input style='width:100px;' disabled='1' type='button' value='Next Level' onclick='new_next(this,event);'/>"
                 . "<br/><br/>"
                 . ($gap<0&&$e_ans>0?"<input type='button' ".($e_ans==$qcount?"":"disabled='1'")." value='Continue Later' onclick='confirmnotfinish(this,event);'/>&nbsp;":"")
                 . "<input style='width:130px;' type='button' value='Finish' onclick='summarypage();' ".($gap<0&&$e_ans>0?"disabled='1'":"")."/></div>";
      
      }
      return array($proficiency_level_name[$ccl]." ($ccl)",$ccl,$fulfilled,$gonext,$gap);
   }
   
   function getBehaviourTitle($competency_id,$proficiency_lvl,$behaviour_id) {
      $db=&Database::getInstance();
      $sql = "SELECT behaviour_en_txt FROM ".XOCP_PREFIX."compbehaviour"
           . " WHERE competency_id = '$competency_id'"
           . " AND proficiency_lvl = '$proficiency_lvl'"
           . " AND behaviour_id = ' $behaviour_id'";
      $result = $db->query($sql);
      list($behaviour_title)=$db->fetchRow($result);
      return $behaviour_title;
   }
   
   function getCurrentAssessment($employee_id,$competency_id) {
      $db=&Database::getInstance();
      $job_id = $_SESSION["empl_job_id"];
      $self_employee_id = $_SESSION["self_employee_id"];
      $asid = $_SESSION["hris_assessment_asid"];
      
      list($ccl,$rcl,$itj,$gap,$last_level)=$this->calcResult($employee_id,$job_id,$competency_id);
      
      if($_SESSION["assessor360"]==1) {
         $sql = "SELECT acl,ccl,asid_update FROM ".XOCP_PREFIX."employee_competency360_session"
              . " WHERE asid = '$asid' AND employee_id = '$employee_id' AND competency_id = '$competency_id' AND assessor_id = '$self_employee_id'";
      } else {
         $sql = "SELECT acl,ccl,asid_update FROM ".XOCP_PREFIX."employee_competency_session"
              . " WHERE asid = '$asid' AND employee_id = '$employee_id' AND competency_id = '$competency_id' AND assessor_id = '$self_employee_id'";
      }
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($acl,$ccl,$asid_update)=$db->fetchRow($result);
         if($asid_update!=$asid) $acl = 0;
         
         if($acl>($rcl+1)) {
            $acl = $rcl+1;
         }
         if($acl>4) $acl = 4;
         
         if($acl==0) {
            $acl = 1;
            if($_SESSION["assessor360"]==1) {
               $sql = "UPDATE ".XOCP_PREFIX."employee_competency360 SET acl = '$acl'"
                    . " WHERE employee_id = '$employee_id' AND competency_id = '$competency_id' AND assessor_id = '$self_employee_id'";
               $db->query($sql);
               $sql = "UPDATE ".XOCP_PREFIX."employee_competency360_session SET acl = '$acl'"
                    . " WHERE asid = '$asid' AND employee_id = '$employee_id' AND competency_id = '$competency_id' AND assessor_id = '$self_employee_id'";
               $db->query($sql);
            } else {
               $sql = "UPDATE ".XOCP_PREFIX."employee_competency SET acl = '$acl'"
                    . " WHERE employee_id = '$employee_id' AND competency_id = '$competency_id'";
               $db->query($sql);
               $sql = "UPDATE ".XOCP_PREFIX."employee_competency_session SET acl = '$acl'"
                    . " WHERE asid = '$asid' AND employee_id = '$employee_id' AND competency_id = '$competency_id' AND assessor_id = '$self_employee_id'";
               $db->query($sql);
            }
         }
      } else {
         $acl = 1;
         $ccl = 0;
         if($_SESSION["assessor360"]==1) {
            $sql = "INSERT INTO ".XOCP_PREFIX."employee_competency360 (employee_id,competency_id,acl,assessor_id)"
                 . " VALUES ('$employee_id','$competency_id','$acl','$self_employee_id')";
            $db->query($sql);
            $sql = "INSERT INTO ".XOCP_PREFIX."employee_competency360_session (asid,employee_id,competency_id,acl,assessor_id)"
                 . " VALUES ('$asid','$employee_id','$competency_id','$acl','$self_employee_id')";
            $db->query($sql);
         } else {
            $sql = "INSERT INTO ".XOCP_PREFIX."employee_competency (employee_id,competency_id,acl,assessor_id)"
                 . " VALUES ('$employee_id','$competency_id','$acl','$self_employee_id')";
            $db->query($sql);
            $sql = "INSERT INTO ".XOCP_PREFIX."employee_competency_session (asid,employee_id,competency_id,acl,assessor_id)"
                 . " VALUES ('$asid','$employee_id','$competency_id','$acl','$self_employee_id')";
            $db->query($sql);
         }
      }
      
      if($ccl==0) {
         $acl = 1;
      }
      
      return array($acl,$ccl,$rcl,$itj,$gap);
   }
   
   function getBehaviourDesc($behaviour_id,$lvl,$competency_id) {
      $db=&Database::getInstance();
      $sql = "SELECT behaviour_en_txt,behaviour_id_txt"
           . " FROM ".XOCP_PREFIX."compbehaviour"
           . " WHERE competency_id = '$competency_id'"
           . " AND proficiency_lvl = '$lvl'"
           . " AND behaviour_id = '$behaviour_id'";
      $result = $db->query($sql);
      list($en,$id)=$db->fetchRow($result);
      $_desc = "<table border='0' width='100%' style='border:0px;'>"
             . "<colgroup><col width='120'/><colgroup/></colgroup>"
             . "<tr><td style='border:0px;'>Behaviour Indicator:</td>"
             . "<td style='border:0px;'>$en"
             . "<hr noshade='1' size='1' color='#bbbbbb'/>"
             . "<span style='font-style:italic;'>$id</span></td></tr></table>";
      return $_desc;
   }
   
   function getLevelDescription($competency_id,$lvl) {
      $db=&Database::getInstance();
      $sql = "SELECT desc_en_level_${lvl},desc_id_level_${lvl}"
           . " FROM ".XOCP_PREFIX."competency"
           . " WHERE competency_id = '$competency_id'";
      $result = $db->query($sql);
      list($en,$id)=$db->fetchRow($result);
      $level_desc = "<table border='0' width='100%' style='border:0px;'>"
                  . "<colgroup><col width='120'/><colgroup/></colgroup>"
                  . "<tr><td style='border:0px;'>Level Description:</td>"
                  . "<td style='border:0px;'>$en"
                  . "<hr noshade='1' size='1' color='#bbbbbb'/>"
                  . "<span style='font-style:italic;'>$id</span></td></tr></table>";
      return $level_desc;
   }
   
   
   function getNextBehaviour($employee_id,$competency_id) {
      $db=&Database::getInstance();
      $self_employee_id = $_SESSION["self_employee_id"];
      $asid = $_SESSION["hris_assessment_asid"];
      
      if($_SESSION["assessor360"]==1) {
         $sql = "SELECT acl,ccl,behaviour_id,ca_id FROM ".XOCP_PREFIX."employee_competency360_session"
              . " WHERE asid = '$asid' AND employee_id = '$employee_id' AND competency_id = '$competency_id' AND assessor_id = '$self_employee_id'";
      } else {
         $sql = "SELECT acl,ccl,behaviour_id,ca_id FROM ".XOCP_PREFIX."employee_competency_session"
              . " WHERE asid = '$asid' AND employee_id = '$employee_id' AND competency_id = '$competency_id' AND assessor_id = '$self_employee_id'";
      }
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($acl,$ccl,$behaviour_id,$ca_id)=$db->fetchRow($result);
      } else {
         $acl = 0;
         $ccl = 1;
         $behaviour_id = 0;
         $ca_id = 0;
         if($_SESSION["assessor360"]==1) {
            $sql = "INSERT INTO ".XOCP_PREFIX."employee_competency360 (employee_id,competency_id,assessor_id)"
                 . " VALUES ('$employee_id','$competency_id','$self_employee_id')";
            $db->query($sql);
            $sql = "INSERT INTO ".XOCP_PREFIX."employee_competency360_session (asid,employee_id,competency_id,assessor_id)"
                 . " VALUES ('$asid','$employee_id','$competency_id','$self_employee_id')";
            $db->query($sql);
         } else {
            $sql = "INSERT INTO ".XOCP_PREFIX."employee_competency (employee_id,competency_id,assessor_id)"
                 . " VALUES ('$employee_id','$competency_id','$self_employee_id')";
            $db->query($sql);
            $sql = "INSERT INTO ".XOCP_PREFIX."employee_competency_session (asid,employee_id,competency_id,assessor_id)"
                 . " VALUES ('$asid','$employee_id','$competency_id','$self_employee_id')";
            $db->query($sql);
         }
      }

      if($ccl>0&&$behaviour_id>0) {
         $sql = "SELECT proficiency_lvl,behaviour_id,ca_id FROM ".XOCP_PREFIX."compbehaviour_qa"
              . " WHERE competency_id = '$competency_id'"
              . " AND proficiency_lvl >= '$acl'"
              . " AND behaviour_id > '$behaviour_id'"
              . " ORDER BY proficiency_lvl,behaviour_id,ca_id"
              . " LIMIT 1";
         $result = $db->query($sql);
         if($db->getRowsNum($result)>0) {
            list($next_proficiency_lvl,$next_behaviour_id,$next_ca_id)=$db->fetchRow($result);
            return array($next_proficiency_lvl,$ccl,$next_behaviour_id,$next_ca_id);
         } else {
            return array(0,0,0,0);
         }
      } else {
         $sql = "SELECT proficiency_lvl,behaviour_id,ca_id FROM ".XOCP_PREFIX."compbehaviour_qa"
              . " WHERE competency_id = '$competency_id'"
              . " AND proficiency_lvl >= '$acl'"
              . " ORDER BY proficiency_lvl,behaviour_id,ca_id"
              . " LIMIT 1";
         $result = $db->query($sql);
         if($db->getRowsNum($result)>0) {
            list($next_proficiency_lvl,$next_behaviour_id,$next_ca_id)=$db->fetchRow($result);
            return array($next_proficiency_lvl,$ccl,$next_behaviour_id,$next_ca_id);
         } else {
            return array(0,0,0,0);
         }
      }
   }
   
   function getQuestions($employee_id,$competency_id,$acl) {
      global $proficiency_level_name;
      $user_id = getUserID();
      $db=&Database::getInstance();
      $self_employee_id = $_SESSION["self_employee_id"];
      $job_id = $_SESSION["empl_job_id"];
      $emp_ca = $arr_ca = $arr_bh = array();
      $asid = $_SESSION["hris_assessment_asid"];
      
      $sql = "SELECT compgroup_id,competency_class FROM ".XOCP_PREFIX."competency WHERE competency_id = '$competency_id'";
      $result = $db->query($sql);
      list($compgroup_id,$competency_class)=$db->fetchRow($result);
      
      if($compgroup_id==3) {
         $answer_t = "grade";
      } else {
         $answer_t = "yesno";
      }
      
      $answer_t = "grade"; ///// override untuk semua level 2011-12-09
      
      $ret = "<div style='font-weight:bold;font-size:+1.2em;text-align:center;padding:8px;border-bottom:0px solid #999999;background-color:#ddd;color:black;'>"
           . "Assessment Level : ".$proficiency_level_name[$acl]." ($acl)</div>";
      
      $sql = "SELECT a.ca_id,a.q_en_txt,a.q_id_txt,a.a_en_txt,a.a_id_txt,a.qa_method,a.behaviour_id,"
           . "b.behaviour_en_txt,b.behaviour_id_txt"
           . " FROM ".XOCP_PREFIX."compbehaviour_qa a"
           . " LEFT JOIN ".XOCP_PREFIX."compbehaviour b USING(competency_id,behaviour_id,proficiency_lvl)"
           . " WHERE a.competency_id = '$competency_id'"
           . " AND a.proficiency_lvl = '$acl'"
           . " ORDER BY a.behaviour_id";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($ca_idx,$q_en_txt,$q_id_txt,$a_en_txt,$a_id_txt,$qa_method,$behaviour_id,
                    $behaviour_en_txt,$behaviour_id_txt)=$db->fetchRow($result)) {
            $emp_ca[$behaviour_id][$ca_idx] = 0;
            $arr_ca[$behaviour_id][$ca_idx] = array($q_en_txt,$q_id_txt,$a_en_txt,$a_id_txt,$qa_method,$behaviour_en_txt,$behaviour_id_txt);
            $arr_bh[$behaviour_id] = array($behaviour_en_txt,$behaviour_id_txt);
         }
      }
      
      if($_SESSION["assessor360"]==1) {
         $sql = "SELECT ca_id,answer,behaviour_id,answer_t,update_dttm FROM ".XOCP_PREFIX."employee_ca360_detail"
              . " WHERE employee_id = '$employee_id'"
              . " AND assessor_id = '$self_employee_id'"
              . " AND competency_id = '$competency_id'"
              . " AND proficiency_lvl = '$acl'";
      } else {
         $sql = "SELECT ca_id,answer,behaviour_id,answer_t,update_dttm FROM ".XOCP_PREFIX."employee_ca_detail"
              . " WHERE employee_id = '$employee_id'"
              . " AND assessor_id = '$self_employee_id'"
              . " AND competency_id = '$competency_id'"
              . " AND proficiency_lvl = '$acl'";
      }
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($ca_id,$answer,$behaviour_id,$answer_tx,$update_dttmx)=$db->fetchRow($result)) {
            if($update_dttmx<="2011-11-01 00:00:00") { /// transition date is November 2011
               if($compgroup_id==3) {
               } else {
                  if($answer_tx=="yesno") {
                     if($answer==2) {
                        $answer = 4;
                     } else if($answer==1) {
                        $answer = 1;
                     } else {
                        $answer = 0;
                     }
                  }
               }
            }
            $emp_ca[$behaviour_id][$ca_id] = $answer;
         }
      }
      
      
      if(count($arr_bh)>0) {
         $no = 0;
         $nob = 0;
         foreach($arr_bh as $behaviour_id=>$bh) {
            list($behaviour_en_txt,$behaviour_id_txt)=$bh;
            $nob++;
            $ret .= "<table class='asmfrm'>"
                 . "<colgroup><col width='50'/><col width='40%'/><col/><col width='80'/><col width='180'/></colgroup>"
                 . "<thead>"
                    . "<tr><td style='text-align:center;font-weight:bold;border-bottom:1px solid transparent;'>$nob</td>"
                        . "<td colspan='5' style='padding:10px;border-right:0px;border-bottom:1px solid #777;'>"
                       . "<table border='0' width='100%' style='color:black;border:0px;'>"
                       . "<colgroup><col width='150'/><col/>   </colgroup>"
                       . "<tbody>"
                       . "<tr>"
                           . "<td style='border:0px;'>Behaviour Indicator:</td>"
                           . "<td style='border:0px;'>$behaviour_en_txt<hr noshade='1' size='1' color='#bbbbbb'/><span style='font-style:italic;'>$behaviour_id_txt</span></td>"
                       . "</tr></tbody></table>"
                    . "</td></tr>"
                    . "<tr id='headfrm'>"
                        . "<td style='border-bottom:0px solid transparent;background-color:transparent;'>&nbsp;</td>"
                        . "<td style='border-bottom:0px solid #777;'>Question</td>"
                        . "<td style='border-bottom:0px solid #777;'>Evidence Guide</td>"
                        . "<td style='border-bottom:0px solid #777;'>Method</td>"
                        . "<td style='border-bottom:0px solid #777;'>Answer</td>"
                     . "</tr>"
                 . "</thead>"
                 . "<tbody>";
            
            $nox = 1;
            foreach($arr_ca[$behaviour_id] as $ca_id=>$ca) {
               /// initialize
               for($i=0;$i<6;$i++) {
                  $ckvar = "ck_$i";
                  $$ckvar = "";
               }
               list($q_en_txt,$q_id_txt,$a_en_txt,$a_id_txt,$qa_method)=$ca;
               $no++;
               $answer = $emp_ca[$behaviour_id][$ca_id];
               $cls = "${answer}_${answer_t}";
               $ckvar = "ck_${answer}";
               $$ckvar = "checked='1'";
               
               if($emp_ca[$behaviour_id][$ca_id]==2) {
                  $ckyes = "checked='1'";
                  $ckno = "";
                  $ckempty = "";
               } elseif($emp_ca[$behaviour_id][$ca_id]==1) {
                  $ckyes = "";
                  $ckno = "checked='1'";
                  $ckempty = "";
               } else {
                  $ckno = "";
                  $ckyes = "";
                  $ckempty = "checked='1'";
               }
               
               ///// notes
               $sql = "SELECT note_txt,note_id FROM ".XOCP_PREFIX."employee_ca_notes"
                    . " WHERE asid = '$asid'"
                    . " AND employee_id = '$employee_id'"
                    . " AND assessor_id = '$self_employee_id'"
                    . " AND competency_id = '$competency_id'"
                    . " AND proficiency_lvl = '$acl'"
                    . " AND behaviour_id = '$behaviour_id'"
                    . " AND ca_id = '$ca_id'"
                    . " AND status_cd = 'normal'";
               $rnote = $db->query($sql);
               if($db->getRowsNum($rnote)>0) {
                  list($note_txt,$note_id)=$db->fetchRow($rnote);
               } else {
                  $note_txt = "";
                  $note_id = 0;
               }

               $ret .= "<tr id='question_${no}' class='trasm_${cls}'>"
                     . "<td style='text-align:center;background-color:transparent;'>&nbsp;</td>"
                     . "<td style='border-top:1px solid #777;padding:4px;'>$q_en_txt<hr noshade='1' size='1' color='#dddddd'/><span style='font-style:italic;'>$q_id_txt</span></td>"
                     . "<td style='border-top:1px solid #777;padding:4px;'>$a_en_txt<hr noshade='1' size='1' color='#dddddd'/><span style='font-style:italic;'>$a_id_txt</span></td>"
                     . "<td style='border-top:1px solid #777;text-align:center;'>$qa_method</td>"
                     . "<td style='border-top:1px solid #777;text-align:left;border-right:0px;'>";
               
               switch($competency_class) {
                  case "soft":
                     $title_1 = "Never";
                     $title_2 = "Seldom";
                     $title_3 = "Usual";
                     $title_4 = "Frequent";
                     $title_5 = "More Frequent";
                     break;
                  case "technical":
                     $title_1 = "No Competency";
                     $title_2 = "Follow";
                     $title_3 = "Under Control";
                     $title_4 = "Self Control";
                     $title_5 = "Pro";
                     break;
                  default:
                     $title_1 = "";
                     $title_2 = "";
                     $title_3 = "";
                     $title_4 = "";
                     $title_5 = "";
                     break;
               }
               
               if($answer_t=="grade") {
                  $ret .= "<span onmousemove='ckrad_mouse_move(\"$title_1\",this,event);' onmouseout='ckrad_mouse_out(this,event);'><input onclick='ckrad(\"$no\",\"1\",\"$answer_t\",this,event);' type='radio' name='answer_${behaviour_id}_${ca_id}' id='answer_1_${behaviour_id}_${ca_id}' value='$competency_id|$acl|$behaviour_id|$ca_id|1' $ck_1/><label class='xlnk' for='answer_1_${behaviour_id}_${ca_id}'>0</label></span>&nbsp;"
                        . "<span onmousemove='ckrad_mouse_move(\"$title_2\",this,event);' onmouseout='ckrad_mouse_out(this,event);'><input onclick='ckrad(\"$no\",\"2\",\"$answer_t\",this,event);' type='radio' name='answer_${behaviour_id}_${ca_id}' id='answer_2_${behaviour_id}_${ca_id}' value='$competency_id|$acl|$behaviour_id|$ca_id|2' $ck_2/><label class='xlnk' for='answer_2_${behaviour_id}_${ca_id}'>1</label></span>&nbsp;"
                        . "<span onmousemove='ckrad_mouse_move(\"$title_3\",this,event);' onmouseout='ckrad_mouse_out(this,event);'><input onclick='ckrad(\"$no\",\"3\",\"$answer_t\",this,event);' type='radio' name='answer_${behaviour_id}_${ca_id}' id='answer_3_${behaviour_id}_${ca_id}' value='$competency_id|$acl|$behaviour_id|$ca_id|3' $ck_3/><label class='xlnk' for='answer_3_${behaviour_id}_${ca_id}'>2</label></span>&nbsp;"
                        . "<span onmousemove='ckrad_mouse_move(\"$title_4\",this,event);' onmouseout='ckrad_mouse_out(this,event);'><input onclick='ckrad(\"$no\",\"4\",\"$answer_t\",this,event);' type='radio' name='answer_${behaviour_id}_${ca_id}' id='answer_4_${behaviour_id}_${ca_id}' value='$competency_id|$acl|$behaviour_id|$ca_id|4' $ck_4/><label class='xlnk' for='answer_4_${behaviour_id}_${ca_id}'>3</label></span>&nbsp;"
                        . "<span onmousemove='ckrad_mouse_move(\"$title_5\",this,event);' onmouseout='ckrad_mouse_out(this,event);'><input onclick='ckrad(\"$no\",\"5\",\"$answer_t\",this,event);' type='radio' name='answer_${behaviour_id}_${ca_id}' id='answer_5_${behaviour_id}_${ca_id}' value='$competency_id|$acl|$behaviour_id|$ca_id|5' $ck_5/><label class='xlnk' for='answer_5_${behaviour_id}_${ca_id}'>4</label></span>&nbsp;"
                        . "<br/>"
                        . "<input onclick='ckrad(\"$no\",\"0\",\"$answer_t\",this,event);' type='radio' name='answer_${behaviour_id}_${ca_id}' id='answer_empty_${behaviour_id}_${ca_id}'  value='$competency_id|$acl|$behaviour_id|$ca_id|0' $ck_0/><label class='xlnk' for='answer_empty_${behaviour_id}_${ca_id}'>Empty</label>";
               
               } else {
                  $ret .= "<input onclick='ckrad(\"$no\",\"2\",\"$answer_t\",this,event);' type='radio' name='answer_${behaviour_id}_${ca_id}' id='answer_yes_${behaviour_id}_${ca_id}' value='$competency_id|$acl|$behaviour_id|$ca_id|2' $ck_2/><label class='xlnk' for='answer_yes_${behaviour_id}_${ca_id}'>Yes</label>&nbsp;"
                        . "<input onclick='ckrad(\"$no\",\"1\",\"$answer_t\",this,event);' type='radio' name='answer_${behaviour_id}_${ca_id}' id='answer_no_${behaviour_id}_${ca_id}'  value='$competency_id|$acl|$behaviour_id|$ca_id|1' $ck_1/><label class='xlnk' for='answer_no_${behaviour_id}_${ca_id}'>No</label>&nbsp;"
                        . "<br/>"
                        . "<input onclick='ckrad(\"$no\",\"0\",\"$answer_t\",this,event);' type='radio' name='answer_${behaviour_id}_${ca_id}' id='answer_empty_${behaviour_id}_${ca_id}'  value='$competency_id|$acl|$behaviour_id|$ca_id|0' $ck_0/><label class='xlnk' for='answer_empty_${behaviour_id}_${ca_id}'>Empty</label>";
               }
               
               $ret .= (1==1?"<div style='font-size:0.9em;text-align:left;'>"
                        . "[<span id='spnote_${acl}_${behaviour_id}_${ca_id}' class='xlnk' onclick='edit_notes(\"$note_id\",\"$competency_id\",\"$acl\",\"${behaviour_id}\",\"$ca_id\",this,event);'>notes</span>]"
                        // . "[<span class='xlnk' onclick='view_history(\"$competency_id\",\"$acl\",\"${behaviour_id}\",\"$ca_id\",this,event);'>history</span>]"
                        . "</div>":"")
                        
                        . "<div id='dvnote_${acl}_${behaviour_id}_${ca_id}' style='".($note_id>0?"":"display:none;")."white-space:pre-wrap;text-align:left;font-family:courier;padding:2px;color:blue;'>$note_txt</div>"
                     . "</td></tr>";

               
               
               $nox++;
            }
            
            $ret .= "</tbody></table>";
            
         }
         
         /* /// commented on 2012-01-17
         list($ccl,$rcl,$itj,$gap) = $this->calcResult($employee_id,$job_id,$competency_id);
         $delta = $acl - $rcl;
         */
         
         $ret .= "<div style='text-align:right;margin:5px;'>"
           . "&nbsp;<input type='button' value='Save' onclick='save_form(this,event);'/>"
           . "</div>";
         
         
      } else {
         $ret .= "<div style='text-align:right;margin:5px;'>"
           . "&nbsp;<input type='button' value='Finish' onclick='save_form(this,event);'/>"
           . "</div>";
      
      }
      
      /// update state
      if($_SESSION["assessor360"]==1) {
         $sql = "SELECT employee_id FROM ".XOCP_PREFIX."employee_competency360_session"
              . " WHERE asid = '$asid' AND employee_id = '$employee_id' AND competency_id = '$competency_id' AND assessor_id = '$self_employee_id'";
      } else {
         $sql = "SELECT employee_id FROM ".XOCP_PREFIX."employee_competency_session"
              . " WHERE asid = '$asid' AND employee_id = '$employee_id' AND competency_id = '$competency_id' AND assessor_id = '$self_employee_id'";
      }
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         if($_SESSION["assessor360"]==1) {
            $sql = "UPDATE ".XOCP_PREFIX."employee_competency360 SET "
                 . "acl = '$acl'"
                 . " WHERE employee_id = '$employee_id'"
                 . " AND competency_id = '$competency_id'"
                 . " AND assessor_id = '$self_employee_id'";
            $db->query($sql);
            $sql = "UPDATE ".XOCP_PREFIX."employee_competency360_session SET "
                 . "acl = '$acl'"
                 . " WHERE employee_id = '$employee_id'"
                 . " AND competency_id = '$competency_id'"
                 . " AND assessor_id = '$self_employee_id'"
                 . " AND asid = '$asid'";
            $db->query($sql);
         } else {
            $sql = "UPDATE ".XOCP_PREFIX."employee_competency SET "
                 . "acl = '$acl'"
                 . " WHERE employee_id = '$employee_id'"
                 . " AND competency_id = '$competency_id'"
                 . " AND assessor_id = '$self_employee_id'";
            $db->query($sql);
            $sql = "UPDATE ".XOCP_PREFIX."employee_competency_session SET "
                 . "acl = '$acl'"
                 . " WHERE employee_id = '$employee_id'"
                 . " AND competency_id = '$competency_id'"
                 . " AND assessor_id = '$self_employee_id'"
                 . " AND asid = '$asid'";
            $db->query($sql);
         }
      } else {
         if($_SESSION["assessor360"]==1) {
            $sql = "INSERT INTO ".XOCP_PREFIX."employee_competency360 (employee_id,competency_id,acl,assessor_id)"
                 . " VALUES ('$employee_id','$competency_id','$acl','$self_employee_id')";
            $db->query($sql);
            $sql = "INSERT INTO ".XOCP_PREFIX."employee_competency360_session (asid,employee_id,competency_id,acl,assessor_id)"
                 . " VALUES ('$asid','$employee_id','$competency_id','$acl','$self_employee_id')";
            $db->query($sql);
         } else {
            $sql = "INSERT INTO ".XOCP_PREFIX."employee_competency (employee_id,competency_id,acl,assessor_id)"
                 . " VALUES ('$employee_id','$competency_id','$acl','$self_employee_id')";
            $db->query($sql);
            $sql = "INSERT INTO ".XOCP_PREFIX."employee_competency_session (asid,employee_id,competency_id,acl,assessor_id)"
                 . " VALUES ('$asid','$employee_id','$competency_id','$acl','$self_employee_id')";
            $db->query($sql);
         }
      }
      
      return $ret;
      
      
   }
   
   
}

} /// HRIS_ASSESSMENTAJAX_DEFINED
?>