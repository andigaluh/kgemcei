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

class _hris_class_AssessmentAjax extends AjaxListener {
   
   function _hris_class_AssessmentAjax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/hris/class/ajax_assessment.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_getNextQuestions","app_getPreviousQuestions","app_saveAssessment",
                            "app_loadMatrix");
   }
   
   function app_loadMatrix($args) {
      $db=&Database::getInstance();
      $emps = explode("-",$args[0]);
      $self_employee_id = $_SESSION["self_employee_id"];
      $jobs = array();
      if(count($emps)>0) {
         foreach($emps as $emp) {
            list($employee_id,$job_id)=explode(".",$emp);
            $jobs[$job_id] = 1;
            $empjob[$employee_id][$job_id] = 1;
         }
      }
      
      ksort($jobs);
      
      if(count($jobs)>0) {
         foreach($jobs as $job_id=>$v) {
            $sql = "SELECT a.rcl,a.itj,a.competency_id,b.compgroup_id,b.competency_cd,"
                 . "b.competency_abbr,b.competency_nm,b.competency_class,b.desc_en,b.desc_id"
                 . " FROM ".XOCP_PREFIX."job_competency a"
                 . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
                 . " WHERE a.job_id = '$job_id'"
                 . " ORDER BY b.compgroup_id,b.competency_class,b.competency_abbr";
            $resrcl = $db->query($sql);
            if($db->getRowsNum($resrcl)>0) {
               while(list($rcl,$itj,$competency_id,$compgroup_id,$competency_cd,
                          $competency_abbr,$competency_nm,$competency_class,$desc_en,$desc_id)=$db->fetchRow($resrcl)) {
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
                  $sql = "SELECT a.ccl,a.competency_id,(TO_DAYS(now())-TO_DAYS(a.update_dttm)) as la,a.update_dttm"
                       . " FROM ".XOCP_PREFIX."employee_competency360 a"
                       . " WHERE a.employee_id = '$employee_id'"
                       . " AND a.assessor_id = '$self_employee_id'";
               } else {
                  $sql = "SELECT a.ccl,a.competency_id,(TO_DAYS(now())-TO_DAYS(a.update_dttm)) as la,a.update_dttm"
                       . " FROM ".XOCP_PREFIX."employee_competency a"
                       . " WHERE a.employee_id = '$employee_id'";
               }
               $resccl = $db->query($sql);
               $ttl_ccl = 0;
               $ttl_rcl = 0;
               if($db->getRowsNum($resccl)>0) {
                  while(list($ccl,$competency_idx,$last_assess,$update_dttm)=$db->fetchRow($resccl)) {
                     list($job_idx,$competency_idy,$rcl,$itj)=$arr_job_competency[$job_id][$competency_idx];
                     $gap = ($ccl-$rcl)*$itj;
                     if($update_dttm=="0000-00-00 00:00:00") {
                        $last_assess = -1;
                     }
                     $arr_ccl[$employee_id][$job_id][$competency_idx] = array($employee_id,$job_id,$competency_idx,$ccl,$rcl,$itj,$gap,$last_assess,$update_dttm);
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
      $self_employee_id = $_SESSION["self_employee_id"];
      $employee_id = $_SESSION["assessment_employee_id"];
      global $proficiency_level_name;
      
      if($_SESSION["assessor360"]==1) {
         $sql = "SELECT acl FROM ".XOCP_PREFIX."employee_competency360"
              . " WHERE employee_id = '$employee_id'"
              . " AND competency_id = '$competency_id'"
              . " AND assessor_id = '$self_employee_id'";
      } else {
         $sql = "SELECT acl FROM ".XOCP_PREFIX."employee_competency"
              . " WHERE employee_id = '$employee_id'"
              . " AND competency_id = '$competency_id'";
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
      } else {
         $sql = "UPDATE ".XOCP_PREFIX."employee_competency SET acl = '$acl'"
              . " WHERE employee_id = '$employee_id'"
              . " AND competency_id = '$competency_id'";
      }
      $result = $db->query($sql);
      
      return array($this->getQuestions($employee_id,$competency_id,$acl),
                   $proficiency_level_name[$acl]." ($acl)",$acl);
      
   }
   
   function app_getPreviousQuestions($args) {
      $db=&Database::getInstance();
      $competency_id = $args[0];
      $employee_id = $_SESSION["assessment_employee_id"];
      $self_employee_id = $_SESSION["self_employee_id"];
      global $proficiency_level_name;
      
      if($_SESSION["assessor360"]==1) {
         $sql = "SELECT acl FROM ".XOCP_PREFIX."employee_competency360"
              . " WHERE employee_id = '$employee_id'"
              . " AND competency_id = '$competency_id'"
              . " AND assessor_id = '$self_employee_id'";
      } else {
         $sql = "SELECT acl FROM ".XOCP_PREFIX."employee_competency"
              . " WHERE employee_id = '$employee_id'"
              . " AND competency_id = '$competency_id'";
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
      } else {
         $sql = "UPDATE ".XOCP_PREFIX."employee_competency SET acl = '$acl'"
              . " WHERE employee_id = '$employee_id'"
              . " AND competency_id = '$competency_id'";
      }
      $result = $db->query($sql);
      
      return array($this->getQuestions($employee_id,$competency_id,$acl),
                   $proficiency_level_name[$acl]. " ($acl)",$acl);
      
   }
   
   function calcResult($employee_id,$job_id,$competency_id) {
      $db=&Database::getInstance();
      $self_employee_id = $_SESSION["self_employee_id"];
      $rcl = $itj = $gap = 0;
      $ccl = 0;
      $sql = "SELECT rcl,itj FROM ".XOCP_PREFIX."job_competency"
           . " WHERE job_id = '$job_id'"
           . " AND competency_id = '$competency_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($rcl,$itj)=$db->fetchRow($result);
      }
      for($i=1;$i<5;$i++) {
         if($_SESSION["assessor360"]==1) {
            $sql = "SELECT fulfilled FROM ".XOCP_PREFIX."employee_level360"
                 . " WHERE employee_id = '$employee_id'"
                 . " AND competency_id = '$competency_id'"
                 . " AND proficiency_lvl = '$i'"
                 . " AND assessor_id = '$self_employee_id'";
         } else {
            $sql = "SELECT fulfilled FROM ".XOCP_PREFIX."employee_level"
                 . " WHERE employee_id = '$employee_id'"
                 . " AND competency_id = '$competency_id'"
                 . " AND proficiency_lvl = '$i'";
         }
         $result = $db->query($sql);
         if($db->getRowsNum($result)==1) {
            list($fulfilled)=$db->fetchRow($result);
            if($fulfilled=="1") {
               $ccl = $i;
            } else {
               break;
            }
         } else {
            break;
         }
      }
      $gap = $itj * ($ccl - $rcl);
      // _debuglog("result :\nccl = $ccl\nrcl = $rcl\nitj = $itj\ngap = $gap\n");
      return array($ccl,$rcl,$itj,$gap);
   }
   
   function app_saveAssessment($args) {
      global $proficiency_level_name;
      $self_employee_id = $_SESSION["self_employee_id"];
      $db=&Database::getInstance();
      $arr = parseForm($args[0]);
      $arr_answer = array();
      $user_id = getUserID();
      $asid = $_SESSION["hris_assessment_asid"];
      foreach($arr as $k=>$v) {
         if(substr($k,0,6)=="answer") {
            list($competency_id,$acl,$behaviour_id,$ca_id,$answer)=explode("|",$v);
            $arr_answer[] = array($competency_id,$acl,$behaviour_id,$ca_id,$answer);
         }
         $$k = addslashes(trim($v));
      }
      
      $employee_id = $_SESSION["assessment_employee_id"];
      $sql = "SELECT COUNT(*) FROM ".XOCP_PREFIX."compbehaviour_qa WHERE competency_id = '$competency_id' AND proficiency_lvl = '$acl'";
      $result = $db->query($sql);
      list($qcount)=$db->fetchRow($result);
      
      $y_ans = 0;
      $n_ans = 0;
      $e_ans = 0;
      foreach($arr_answer as $ans) {
         list($competency_id,$acl,$behaviour_id,$ca_id,$answer)=$ans;
         if($answer=="yes") {
            $xans = 2;
            $y_ans++;
         } else if($answer=="no") {
            $xans = 1;
            $n_ans++;
         } else {
            $xans = 0;
            $e_ans++;
         }
         if($_SESSION["assessor360"]==1) {
            $sql = "REPLACE INTO ".XOCP_PREFIX."employee_ca360_detail (employee_id,competency_id,proficiency_lvl,behaviour_id,ca_id,answer,assessor_id,update_dttm,update_user_id)"
                 . " VALUES ('$employee_id','$competency_id','$acl','$behaviour_id','$ca_id','$xans','$self_employee_id',now(),'$user_id')";
         } else {
            $sql = "REPLACE INTO ".XOCP_PREFIX."employee_ca_detail (employee_id,competency_id,proficiency_lvl,behaviour_id,ca_id,answer,assessor_id,update_dttm,update_user_id)"
                 . " VALUES ('$employee_id','$competency_id','$acl','$behaviour_id','$ca_id','$xans','$self_employee_id',now(),'$user_id')";
         }
         $db->query($sql);
      }
      
      if($y_ans==$qcount) {   /// level fulfilled
         if($_SESSION["assessor360"]==1) {
            $sql = "REPLACE INTO ".XOCP_PREFIX."employee_level360 (employee_id,competency_id,proficiency_lvl,fulfilled,assessor_id,update_dttm,update_user_id)"
                 . " VALUES ('$employee_id','$competency_id','$acl','1','$self_employee_id',now(),'$user_id')";
         } else {
            $sql = "REPLACE INTO ".XOCP_PREFIX."employee_level (employee_id,competency_id,proficiency_lvl,fulfilled,assessor_id,update_dttm,update_user_id)"
                 . " VALUES ('$employee_id','$competency_id','$acl','1','$self_employee_id',now(),'$user_id')";
         }
         $db->query($sql);
         $fulfilled = 1;
      } else {                /// level unfilfilled
         if($_SESSION["assessor360"]==1) {
            $sql = "REPLACE INTO ".XOCP_PREFIX."employee_level360 (employee_id,competency_id,proficiency_lvl,fulfilled,assessor_id,update_dttm,update_user_id)"
                 . " VALUES ('$employee_id','$competency_id','$acl','0','$self_employee_id',now(),'$user_id')";
         } else {
            $sql = "REPLACE INTO ".XOCP_PREFIX."employee_level (employee_id,competency_id,proficiency_lvl,fulfilled,assessor_id,update_dttm,update_user_id)"
                 . " VALUES ('$employee_id','$competency_id','$acl','0','$self_employee_id',now(),'$user_id')";
         }
         $db->query($sql);
         $fulfilled = 0;
      }
      
      list($ccl,$rcl,$itj,$gap)=$this->calcResult($employee_id,$_SESSION["empl_job_id"],$competency_id);
      
      if($_SESSION["assessor360"]==1) {
         $sql = "UPDATE ".XOCP_PREFIX."employee_competency360 SET ccl = '$ccl', update_dttm = now(), asid_update = '$asid'"
              . " WHERE employee_id = '$employee_id' AND competency_id = '$competency_id' AND assessor_id = '$self_employee_id'";
      } else {
         $sql = "UPDATE ".XOCP_PREFIX."employee_competency SET ccl = '$ccl', update_dttm = now(), assessor_id = '$self_employee_id', asid_update = '$asid'"
              . " WHERE employee_id = '$employee_id' AND competency_id = '$competency_id'";
      }
      $db->query($sql);
      
      $title = "<div style='padding:4px;'>Assessment Level : ".$proficiency_level_name[$acl]." ($acl)</div>";
      $calc = "<span style='font-weight:bold;color:black;'>CCL : $ccl / RCL : $rcl / ITJ : $itj / GAP : $gap</span>";
      
      $delta = $acl - $rcl;
      if($fulfilled==1) {
         $nextacl = $acl+1;
         if($nextacl<=4) {
            $gonext = "<div style='text-align:center;'>${title}Result : <span style='color:blue;'>Level fulfilled.</span>"
                    . ($delta<ACL_DELTA?"<br/>Continue to level <span style='font-weight:bold;'>".$proficiency_level_name[$nextacl]." ($nextacl)</span>?":"")
                    . "<br/>$calc<br/><br/>"
                    . ($delta<ACL_DELTA?"<input style='width:80px;' type='button' value='Continue' onclick='confirmgonext(this,event);'/>&nbsp;&nbsp;":"")
                    . "<input style='width:80px;' type='button' value='Review' onclick='review(this,event);'/>&nbsp;&nbsp;"
                    . "<input style='width:80px;' type='button' value='Finish' onclick='summarypage();'/></div>";
         } else {
            $gonext = "<div style='text-align:center;'>${title}Result : <span style='color:blue;'>Highest level has been achieved.</span>"
                    . "<br/>$calc<br/><br/>"
                    . "<input style='width:80px;' type='button' value='Review' onclick='review(this,event);'/>&nbsp;&nbsp;"
                    . "<input style='width:80px;' type='button' value='Finish' onclick='summarypage();'/></div>";
         
         }
      } else {
         if($e_ans>0) {
            $warn = "<div style='text-align:center;padding:4px;color:red;'>There are still empty answer. Please review.</div>";
         } else {
            $warn = "";
         }
         $gonext = "$warn<div style='text-align:center;'>${title}Result : <span style='color:red;'>Level unfulfilled.</span>"
                 . "<br/>$calc<br/><br/>"
                 . "<input style='width:80px;' type='button' value='Review' onclick='review(this,event);'/>&nbsp;&nbsp;"
                 . "<input style='width:80px;' type='button' value='Finish' onclick='summarypage();'/></div>";
      
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
      if($_SESSION["assessor360"]==1) {
         $sql = "SELECT acl,ccl FROM ".XOCP_PREFIX."employee_competency360"
              . " WHERE employee_id = '$employee_id' AND competency_id = '$competency_id' AND assessor_id = '$self_employee_id'";
      } else {
         $sql = "SELECT acl,ccl FROM ".XOCP_PREFIX."employee_competency"
              . " WHERE employee_id = '$employee_id' AND competency_id = '$competency_id'";
      }
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($acl,$ccl)=$db->fetchRow($result);
         if($acl==0) {
            $acl = 1;
            if($_SESSION["assessor360"]==1) {
               $sql = "UPDATE ".XOCP_PREFIX."employee_competency360 SET acl = '$acl'"
                    . " WHERE employee_id = '$employee_id' AND competency_id = '$competency_id' AND assessor_id = '$self_employee_id'";
            } else {
               $sql = "UPDATE ".XOCP_PREFIX."employee_competency SET acl = '$acl'"
                    . " WHERE employee_id = '$employee_id' AND competency_id = '$competency_id'";
            }
            $db->query($sql);
         }
      } else {
         $acl = 1;
         $ccl = 0;
         if($_SESSION["assessor360"]==1) {
            $sql = "INSERT INTO ".XOCP_PREFIX."employee_competency360 (employee_id,competency_id,acl,assessor_id)"
                 . " VALUES ('$employee_id','$competency_id','$acl','$self_employee_id')";
         } else {
            $sql = "INSERT INTO ".XOCP_PREFIX."employee_competency (employee_id,competency_id,acl,assessor_id)"
                 . " VALUES ('$employee_id','$competency_id','$acl','$self_employee_id')";
         }
         $db->query($sql);
      }
      list($ccl,$rcl,$itj,$gap)=$this->calcResult($employee_id,$job_id,$competency_id);
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
      if($_SESSION["assessor360"]==1) {
         $sql = "SELECT acl,ccl,behaviour_id,ca_id FROM ".XOCP_PREFIX."employee_competency360"
              . " WHERE employee_id = '$employee_id' AND competency_id = '$competency_id' AND assessor_id = '$self_employee_id'";
      } else {
         $sql = "SELECT acl,ccl,behaviour_id,ca_id FROM ".XOCP_PREFIX."employee_competency"
              . " WHERE employee_id = '$employee_id' AND competency_id = '$competency_id'";
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
         } else {
            $sql = "INSERT INTO ".XOCP_PREFIX."employee_competency (employee_id,competency_id,assessor_id)"
                 . " VALUES ('$employee_id','$competency_id','$self_employee_id')";
         }
         $db->query($sql);
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
      $db=&Database::getInstance();
      $self_employee_id = $_SESSION["self_employee_id"];
      $job_id = $_SESSION["empl_job_id"];
      $emp_ca = $arr_ca = $arr_bh = array();
      
      $ret = "<div style='font-weight:bold;font-size:+1.2em;text-align:center;padding:8px;border-bottom:1px solid #999999;'>"
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
         $sql = "SELECT ca_id,answer,behaviour_id FROM ".XOCP_PREFIX."employee_ca360_detail"
              . " WHERE employee_id = '$employee_id'"
              . " AND assessor_id = '$self_employee_id'"
              . " AND competency_id = '$competency_id'"
              . " AND proficiency_lvl = '$acl'";
      } else {
         $sql = "SELECT ca_id,answer,behaviour_id FROM ".XOCP_PREFIX."employee_ca_detail"
              . " WHERE employee_id = '$employee_id'"
              . " AND competency_id = '$competency_id'"
              . " AND proficiency_lvl = '$acl'";
      }
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($ca_id,$answer,$behaviour_id)=$db->fetchRow($result)) {
            $emp_ca[$behaviour_id][$ca_id] = $answer;
         }
      }
      
      if(count($arr_bh)>0) {
         $no = 0;
         foreach($arr_bh as $behaviour_id=>$bh) {
            list($behaviour_en_txt,$behaviour_id_txt)=$bh;
            $ret .= "<table class='asmfrm'>"
                 . "<colgroup><col/><col width='40%'/><col/><col width='80'/><col width='180'/></colgroup>"
                 . "<thead><tr><td colspan='5' style='padding:10px;border-right:0px;'>"
                 . "<table border='0' width='100%' style='border:0px;'>"
                 . "<colgroup><col width='120'/></colgroup>"
                 . "<tr><td style='border:0px;'>Behaviour Indicator:</td>"
                 . "<td style='border:0px;'>$behaviour_en_txt"
                 . "<hr noshade='1' size='1' color='#bbbbbb'/>"
                 . "<span style='font-style:italic;'>$behaviour_id_txt</span></td></tr></table>"
                 . "</td></tr>"
                 . "<tr id='headfrm'><td colspan='2'>Question</td><td>Evidence Guide</td><td>Method</td><td>Answer</td></tr></thead>"
                 . "<tbody>";
           
            foreach($arr_ca[$behaviour_id] as $ca_id=>$ca) {
               list($q_en_txt,$q_id_txt,$a_en_txt,$a_id_txt,$qa_method)=$ca;
               $no++;
               if($emp_ca[$behaviour_id][$ca_id]==2) {
                  $ckyes = "checked='1'";
                  $ckno = "";
                  $ckempty = "";
                  $cls = "2";
               } elseif($emp_ca[$behaviour_id][$ca_id]==1) {
                  $ckyes = "";
                  $ckno = "checked='1'";
                  $ckempty = "";
                  $cls = "1";
               } else {
                  $ckno = "";
                  $ckyes = "";
                  $ckempty = "checked='1'";
                  $cls = "0";
               }

               $ret .= "<tr id='question_${no}' class='trasm_${cls}'>"
                     . "<td style='text-align:center;'>$no</td>"
                     . "<td style='padding:4px;'>$q_en_txt<hr noshade='1' size='1' color='#dddddd'/><span style='font-style:italic;'>$q_id_txt</span></td>"
                     . "<td style='padding:4px;'>$a_en_txt<hr noshade='1' size='1' color='#dddddd'/><span style='font-style:italic;'>$a_id_txt</span></td>"
                     . "<td style='text-align:center;'>$qa_method</td>"
                     . "<td style='text-align:center;border-right:0px;'>"
                        . "<input onclick='ckrad(\"$no\",\"2\",this,event);' type='radio' name='answer_${behaviour_id}_${ca_id}' id='answer_yes_${behaviour_id}_${ca_id}' value='$competency_id|$acl|$behaviour_id|$ca_id|yes' $ckyes/><label class='xlnk' for='answer_yes_${behaviour_id}_${ca_id}'>Yes</label>&nbsp;"
                        . "<input onclick='ckrad(\"$no\",\"1\",this,event);' type='radio' name='answer_${behaviour_id}_${ca_id}' id='answer_no_${behaviour_id}_${ca_id}'  value='$competency_id|$acl|$behaviour_id|$ca_id|no' $ckno/><label class='xlnk' for='answer_no_${behaviour_id}_${ca_id}'>No</label>&nbsp;"
                        . "<input onclick='ckrad(\"$no\",\"0\",this,event);' type='radio' name='answer_${behaviour_id}_${ca_id}' id='answer_empty_${behaviour_id}_${ca_id}'  value='$competency_id|$acl|$behaviour_id|$ca_id|empty' $ckempty/><label class='xlnk' for='answer_empty_${behaviour_id}_${ca_id}'>Empty</label>"
                     . "</td></tr>";


            }
            
            $ret .= "</tbody></table>";
            
         }
         
         list($ccl,$rcl,$itj,$gap) = $this->calcResult($employee_id,$job_id,$competency_id);
         
         $delta = $acl - $rcl;
         
         $ret .= "<div style='text-align:right;margin:5px;'>"
           . "&nbsp;<input type='button' value='Save' onclick='save_form(this,event);'/>&nbsp;&nbsp;"
           . "&nbsp;<input type='button' value='Previous' onclick='goprev(this,event);' ".($acl<=1?" style='color:#999999;' disabled='1'":"")."/>"
           . "&nbsp;<input type='button' value='Next' onclick='gonext(this,event);' ".($acl>=4?" style='color:#999999;' disabled='1'":($delta<ACL_DELTA?"":" style='color:#999999;' disabled='1'"))."/>"
           . "</div>";
         
         
      } else {
         $ret .= "<div style='text-align:right;margin:5px;'>"
           . "&nbsp;<input type='button' value='Finish' onclick='save_form(this,event);'/>&nbsp;&nbsp;"
           . "&nbsp;<input type='button' value='Previous' onclick='goprev(this,event);' ".($acl<=1?" style='color:#999999;' disabled='1'":"")."/>"
           //. "&nbsp;<input type='button' value='Next' onclick='gonext(this,event);' ".($acl>=4?" style='color:#999999;' disabled='1'":($delta<ACL_DELTA?"":" style='color:#999999;' disabled='1'"))."/>"
           . "</div>";
      
      }
      
      /// update state
      if($_SESSION["assessor360"]==1) {
         $sql = "SELECT employee_id FROM ".XOCP_PREFIX."employee_competency360"
              . " WHERE employee_id = '$employee_id' AND competency_id = '$competency_id' AND assessor_id = '$self_employee_id'";
      } else {
         $sql = "SELECT employee_id FROM ".XOCP_PREFIX."employee_competency"
              . " WHERE employee_id = '$employee_id' AND competency_id = '$competency_id'";
      }
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         if($_SESSION["assessor360"]==1) {
            $sql = "UPDATE ".XOCP_PREFIX."employee_competency360 SET "
                 . "acl = '$acl'"
                 . " WHERE employee_id = '$employee_id'"
                 . " AND competency_id = '$competency_id'"
                 . " AND assessor_id = '$self_employee_id'";
         } else {
            $sql = "UPDATE ".XOCP_PREFIX."employee_competency SET "
                 . "acl = '$acl'"
                 . " WHERE employee_id = '$employee_id'"
                 . " AND competency_id = '$competency_id'";
         }
      } else {
         if($_SESSION["assessor360"]==1) {
            $sql = "INSERT INTO ".XOCP_PREFIX."employee_competency360 (employee_id,competency_id,acl,assessor_id)"
                 . " VALUES ('$employee_id','$competency_id','$acl','$self_employee_id')";
         } else {
            $sql = "INSERT INTO ".XOCP_PREFIX."employee_competency (employee_id,competency_id,acl)"
                 . " VALUES ('$employee_id','$competency_id','$acl')";
         }
      }
      $db->query($sql);
      
      return $ret;
      
      
   }
   
   
}

} /// HRIS_ASSESSMENTAJAX_DEFINED
?>