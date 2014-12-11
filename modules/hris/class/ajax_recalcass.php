<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_recalcass.php              //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_RECALCASSAJAX_DEFINED') ) {
   define('HRIS_RECALCASSAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");
require_once(XOCP_DOC_ROOT."/modules/hris/include/assessment.php");

class _hris_class_RecalculateAssessmentAjax extends AjaxListener {
   
   function __construct($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/hris/class/ajax_recalcass.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_recalculate","app_detailUnfinished");
   }
   
   function app_detailUnfinished($args) {
      $db=&Database::getInstance();
      
   }
   
   function app_recalculate($args) {
      ss_timing_start("recalcass");
      
      $asid = $_SESSION["hris_assessment_asid"];
      
      ////////////////////////////////////////////////////////////////////////////////////////
      ////////////////////////////////////////////////////////////////////////////////////////
      
      $db=&Database::getInstance();
      
      $sql = "SELECT job_id FROM ".XOCP_PREFIX."jobs WHERE status_cd = 'normal'";
      $result = $db->query($sql);
      $jobs = array();
      if($db->getRowsNum($result)>0) {
         while(list($job_id)=$db->fetchRow($result)) {
            $jobs[$job_id] = 1;
         }
      }
      
      foreach($jobs as $job_idx=>$v) {
         $sql = "SELECT a.rcl,a.itj,b.competency_id,b.competency_nm,b.competency_abbr,b.desc_en,b.desc_id"
              . " FROM ".XOCP_PREFIX."job_competency a"
              . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
              . " WHERE a.job_id = '$job_idx'"
              . " ORDER BY b.competency_abbr";
         $result = $db->query($sql);
         if($db->getRowsNum($result)>0) {
            while(list($rcl,$itj,$competency_id,$competency_nm,$competency_abbr,$desc_en,$desc_id)=$db->fetchRow($result)) {
               if(!isset($job_ttl_rcl[$job_idx])) {
                  $job_ttl_rcl[$job_idx] = 0;
               }
               
               $sql = "SELECT compgroup_id FROM ".XOCP_PREFIX."competency WHERE competency_id = '$competency_id'";
               $rgroup = $db->query($sql);
               list($compgroup_id)=$db->fetchRow($rgroup);
               
               if($asid>=10) {
                  $answer_t = "grade";
               } else {
                  if($compgroup_id==3) {
                     $answer_t = "grade";
                  } else {
                     $answer_t = "yesno";
                  }
               }
               
               $job_data[$job_idx][$competency_id] = array($rcl,$itj,$answer_t,$compgroup_id,$competency_abbr,$competency_nm,$competency_id,$desc_en,$desc_id);
            }
         }
      }
      
      $ck_emp = 100;
      $ck_ass = 103;
      
      $cntrec = 0;
      
      foreach($jobs as $job_idx=>$v) { //// each job
         $sql = "SELECT a.employee_id,b.employee_ext_id,c.person_nm"
              . " FROM ".XOCP_PREFIX."assessment_session_job a"
              . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
              . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
              . " WHERE a.job_id = '$job_idx'"
              . " AND a.asid = '$asid'"
              . " AND b.status_cd = 'normal'"
              . " ORDER BY c.person_nm";
         $remp = $db->query($sql);
         
         
         if($db->getRowsNum($remp)>0) {
            while(list($employee_id,$nip,$employee_nm)=$db->fetchRow($remp)) { //// each employee
               $sql = "SELECT assessor_id,assessor_t FROM hris_assessor_360 WHERE asid = '$asid' AND employee_id = '$employee_id' AND status_cd = 'active'";
               $ras = $db->query($sql);
               if($db->getRowsNum($ras)>0) {
                  while(list($assessor_id,$assessor_t)=$db->fetchRow($ras)) {
                     foreach($job_data[$job_idx]  as $competency_id=>$v) {
                        $sql = "SELECT competency_nm FROM hris_competency WHERE competency_id = '$competency_id'";
                        $rcomp = $db->query($sql);
                        list($competency_nm)=$db->fetchRow($rcomp);
                        list($rcl,$itj,$answer_t,$compgroup_id,$competency_abbr,$competency_nm,$competency_id,$desc_en,$desc_id)=$v;
                        if($assessor_t!="superior"&&$compgroup_id==3) continue;
                        
                        if($assessor_t=="superior") {
                           
                           /// $sql = "SELECT ccl FROM hris_employee_competency WHERE employee_id = '$employee_id' AND competency_id = '$competency_id' AND asid_update = '$asid'"; /// commented on 2012-01-17
                           $sql = "SELECT ccl FROM hris_employee_competency_session WHERE employee_id = '$employee_id' AND competency_id = '$competency_id' AND asid = '$asid'";
                           $rc = $db->query($sql);
                           if($db->getRowsNum($rc)>0) {
                              
                              for($lvl=1;$lvl<5;$lvl++) {
                                 
                                 $sql = "SELECT COUNT(*) FROM ".XOCP_PREFIX."compbehaviour_qa WHERE competency_id = '$competency_id' AND proficiency_lvl = '$lvl'";
                                 $rq = $db->query($sql);
                                 list($qcount)=$db->fetchRow($rq);
                                 
                                 $y_ans = 0;
                                 $n_ans = 0;
                                 $e_ans = 0;
                                 $ttl_ans = 0;
                                 
                                 $sql = "SELECT answer, ca_id, behaviour_id, proficiency_lvl, answer_t, update_dttm"
                                      . " FROM hris_employee_ca_detail_session"
                                      . " WHERE employee_id = '$employee_id'"
                                      . " AND competency_id = '$competency_id'"
                                      . " AND proficiency_lvl = '$lvl'"
                                      . " AND asid = '$asid'"
                                      . " ORDER BY behaviour_id, ca_id";
                                 $rans = $db->query($sql);
                                 if($db->getRowsNum($rans)>0) {
                                    while(list($answer,$ca_id,$behaviour_id,$proficiency_lvl,$answer_tx,$update_dttmx)=$db->fetchRow($rans)) {
                                       
                                       $xans = $answer;
                                       if($answer_t=="grade") {
                                          if($asid>=10) {
                                             if($xans==5) {
                                                $y_ans++;
                                                $ttl_ans += 1;
                                             } else if($xans==4) {
                                                $y_ans++;
                                                $ttl_ans += 0.75;
                                             } else if($xans==3) {
                                                $n_ans++;
                                                $ttl_ans += 0.5;
                                             } else if($xans==2) {
                                                $n_ans++;
                                                $ttl_ans += 0.25;
                                             } else if($xans==1) {
                                                $n_ans++;
                                                $ttl_ans += 0;
                                             } else {
                                                $e_ans++;
                                             }
                                          } else {
                                             if($xans==5) {
                                                $y_ans++;
                                                $ttl_ans += 1;
                                             } else if($xans==4) {
                                                $y_ans++;
                                                $ttl_ans += 0.8;
                                             } else if($xans==3) {
                                                $n_ans++;
                                                $ttl_ans += 0.6;
                                             } else if($xans==2) {
                                                $n_ans++;
                                                $ttl_ans += 0.2;
                                             } else if($xans==1) {
                                                $n_ans++;
                                                $ttl_ans += 0;
                                             } else {
                                                $e_ans++;
                                             }
                                          }
                                       } else {
                                          if($xans==2) {
                                             $y_ans++;
                                             $ttl_ans += 1;
                                          } else if($xans==1) {
                                             $n_ans++;
                                             $ttl_ans += 0;
                                          } else {
                                             $e_ans++;
                                          }
                                       }
                                       
                                    }
                                    
                                    if(($y_ans+$n_ans)>0) {
                                       $level_value = _bctrim(bcdiv($ttl_ans,($y_ans+$n_ans)));
                                    } else {
                                       $level_value = 0;
                                    }
                                    
                                    if($y_ans>=$qcount) {   /// level fulfilled
                                       $fulfilled = 1;
                                    } elseif($e_ans>0) {                /// level unfinished
                                       $fulfilled = 0;
                                    } else {                /// level unfilfilled
                                       $fulfilled = -1;
                                    }
                                    
                                 } else {
                                    
                                    $level_value = 0;
                                    $fulfilled = 0;
                                 
                                 }
                                 
                                 $sql = "REPLACE INTO ".XOCP_PREFIX."employee_level (employee_id,competency_id,proficiency_lvl,fulfilled,assessor_id,update_dttm,update_user_id,level_value)"
                                      . " VALUES ('$employee_id','$competency_id','$lvl','$fulfilled','$assessor_id',now(),'$user_id','$level_value')";
                                 $db->query($sql);
                                 
                                 $sql = "REPLACE INTO ".XOCP_PREFIX."employee_level_session (asid,employee_id,competency_id,proficiency_lvl,fulfilled,assessor_id,update_dttm,update_user_id,level_value)"
                                      . " VALUES ('$asid','$employee_id','$competency_id','$lvl','$fulfilled','$assessor_id',now(),'$user_id','$level_value')";
                                 $db->query($sql);
                                 
                                 $sql = "SELECT fulfilled,level_value FROM hris_employee_level_session"
                                      . " WHERE employee_id = '$employee_id'"
                                      . " AND competency_id = '$competency_id'"
                                      . " AND proficiency_lvl = '$lvl'"
                                      . " AND assessor_id = '$assessor_id'"
                                      . " AND asid = '$asid'";
                                 $rck = $db->query($sql);
                                 if($db->getRowsNum($rck)==1) {
                                    list($ck_fulfilled,$ck_level_value)=$db->fetchRow($rck);
                                    if($fulfilled!=$ck_fulfilled) {
                                       $cntrec++;
                                       ///echo "$cntrec WRONG RECORD FOUND: $fulfilled vs $ck_fulfilled\n$sql\n\n";
                                    } else {
                                       //$cntrec++;
                                       //echo "$cntrec ------------- RECORD FOUND OK: $fulfilled\n$sql\n\n";
                                    }
                                 } else {
                                    //$cntrec++;
                                    //echo "$cntrec ------------- NO RECORD FOUND: $answer_t : $fulfilled $level_value\n$sql\n\n";
                                 
                                 }
                                 
                                 if($employee_id==$ck_emp) {
                                    ///echo "$sql\n";
                                    ///echo "Level $lvl fulfilled: $fulfilled\n";
                                 }
                                 
                                 if($fulfilled!=1) {
                                    
                                    $sql = "DELETE FROM ".XOCP_PREFIX."employee_level"
                                         . " WHERE employee_id = '$employee_id'"
                                         . " AND assessor_id = '$assessor_id'"
                                         . " AND competency_id = '$competency_id'"
                                         . " AND proficiency_lvl > '$lvl'";
                                    $db->query($sql);
                                    
                                    $sql = "DELETE FROM ".XOCP_PREFIX."employee_level_session"
                                         . " WHERE employee_id = '$employee_id'"
                                         . " AND assessor_id = '$assessor_id'"
                                         . " AND competency_id = '$competency_id'"
                                         . " AND proficiency_lvl > '$lvl'"
                                         . " AND asid = '$asid'";
                                    $db->query($sql);
                                    
                                    if($employee_id==$ck_emp) {
                                       ///echo "$sql\n";
                                    }
                                    
                                    break;
                                 }
                                 
                                 
                              }
                              
                           }
                           
                           
                           
                           list($ccl,$rcl,$itj,$gap,$last_level,$ttl_level_value)=$this->fixCalcResult($employee_id,$job_idx,$competency_id,$assessor_id);
                           
                           $sql = "UPDATE ".XOCP_PREFIX."employee_competency SET ccl = '$ccl', assessor_id = '$assessor_id', asid_update = '$asid', last_level = '$last_level', cclxxx = '$ttl_level_value'"
                                . " WHERE employee_id = '$employee_id' AND competency_id = '$competency_id'";
                           $db->query($sql);
                           
                           $sql = "UPDATE ".XOCP_PREFIX."employee_competency_session SET ccl = '$ccl', assessor_id = '$assessor_id', asid_update = '$asid', last_level = '$last_level', cclxxx = '$ttl_level_value'"
                                . " WHERE asid = '$asid' AND employee_id = '$employee_id' AND competency_id = '$competency_id' AND assessor_id = '$assessor_id'";
                           $db->query($sql);
                           
                           
                        } else { ///// 360
                           
                           
                           
                           /// $sql = "SELECT ccl FROM hris_employee_competency360 WHERE employee_id = '$employee_id' AND assessor_id = '$assessor_id' AND competency_id = '$competency_id' AND asid_update = '$asid'";
                           $sql = "SELECT ccl FROM hris_employee_competency360_session WHERE asid = '$asid' AND employee_id = '$employee_id' AND assessor_id = '$assessor_id' AND competency_id = '$competency_id'";
                           $rc = $db->query($sql);
                           if($db->getRowsNum($rc)>0) {
                              
                              for($lvl=1;$lvl<5;$lvl++) {
                                 
                                 $sql = "SELECT COUNT(*) FROM ".XOCP_PREFIX."compbehaviour_qa WHERE competency_id = '$competency_id' AND proficiency_lvl = '$lvl'";
                                 $rq = $db->query($sql);
                                 list($qcount)=$db->fetchRow($rq);
                                 
                                 $y_ans = 0;
                                 $n_ans = 0;
                                 $e_ans = 0;
                                 $ttl_ans = 0;
                                 
                                 $sql = "SELECT answer, ca_id, behaviour_id, proficiency_lvl, answer_t, update_dttm"
                                      . " FROM hris_employee_ca360_detail_session"
                                      . " WHERE employee_id = '$employee_id'"
                                      . " AND competency_id = '$competency_id'"
                                      . " AND proficiency_lvl = '$lvl'"
                                      . " AND assessor_id = '$assessor_id'"
                                      . " AND asid = '$asid'"
                                      . " ORDER BY behaviour_id, ca_id";
                                 $rans = $db->query($sql);
                                 if($db->getRowsNum($rans)>0) {
                                    while(list($answer,$ca_id,$behaviour_id,$proficiency_lvl,$answer_tx,$update_dttmx)=$db->fetchRow($rans)) {
                                       
                                       $xans = $answer;
                                       if($answer_t=="grade") {
                                          if($xans==5) {
                                             $y_ans++;
                                             $ttl_ans += 1;
                                          } else if($xans==4) {
                                             $y_ans++;
                                             $ttl_ans += 0.75;
                                          } else if($xans==3) {
                                             $n_ans++;
                                             $ttl_ans += 0.5;
                                          } else if($xans==2) {
                                             $n_ans++;
                                             $ttl_ans += 0.25;
                                          } else if($xans==1) {
                                             $n_ans++;
                                             $ttl_ans += 0;
                                          } else {
                                             $e_ans++;
                                          }
                                       } else {
                                          if($xans==2) {
                                             $y_ans++;
                                             $ttl_ans += 1;
                                          } else if($xans==1) {
                                             $n_ans++;
                                             $ttl_ans += 0;
                                          } else {
                                             $e_ans++;
                                          }
                                       }
                                    }
                                    
                                    if(($y_ans+$n_ans)>0) {
                                       $level_value = _bctrim(bcdiv($ttl_ans,($y_ans+$n_ans)));
                                    } else {
                                       $level_value = 0;
                                    }
                                    
                                    if($y_ans>=$qcount) {   /// level fulfilled
                                       $fulfilled = 1;
                                    } elseif($e_ans>0) {                /// level unfinished
                                       $fulfilled = 0;
                                    } else {                /// level unfilfilled
                                       $fulfilled = -1;
                                    }
                                 
                                    
                                    
                                 } else {
                                    
                                    $level_value = 0;
                                    $fulfilled = 0;
                                 
                                 }
                                 
                                 $sql = "REPLACE INTO ".XOCP_PREFIX."employee_level360 (employee_id,competency_id,proficiency_lvl,fulfilled,assessor_id,update_dttm,update_user_id,level_value)"
                                      . " VALUES ('$employee_id','$competency_id','$lvl','$fulfilled','$assessor_id',now(),'$user_id','$level_value')";
                                 $db->query($sql);
                                 
                                 $sql = "REPLACE INTO ".XOCP_PREFIX."employee_level360_session (asid,employee_id,competency_id,proficiency_lvl,fulfilled,assessor_id,update_dttm,update_user_id,level_value)"
                                      . " VALUES ('$asid','$employee_id','$competency_id','$lvl','$fulfilled','$assessor_id',now(),'$user_id','$level_value')";
                                 $db->query($sql);
                                 
                                 $sql = "SELECT fulfilled,level_value FROM hris_employee_level360"
                                      . " WHERE employee_id = '$employee_id'"
                                      . " AND competency_id = '$competency_id'"
                                      . " AND proficiency_lvl = '$lvl'"
                                      . " AND assessor_id = '$assessor_id'";
                                 $rck = $db->query($sql);
                                 if($db->getRowsNum($rck)==1) {
                                    list($ck_fulfilled,$ck_level_value)=$db->fetchRow($rck);
                                    if($fulfilled!=$ck_fulfilled) {
                                       $cntrec++;
                                       ///echo "$cntrec WRONG RECORD FOUND: $fulfilled vs $ck_fulfilled\n$sql\n\n";
                                    } else {
                                       //$cntrec++;
                                       //echo "$cntrec ------------- RECORD FOUND OK: $fulfilled\n$sql\n\n";
                                    }
                                 } else {
                                    //$cntrec++;
                                    //echo "$cntrec ------------- NO RECORD FOUND: $answer_t : $fulfilled $level_value\n$sql\n\n";
                                 
                                 }
                                 
                                 if($fulfilled!=1) {
                                    
                                    $sql = "DELETE FROM ".XOCP_PREFIX."employee_level360"
                                         . " WHERE employee_id = '$employee_id'"
                                         . " AND assessor_id = '$assessor_id'"
                                         . " AND competency_id = '$competency_id'"
                                         . " AND proficiency_lvl > '$lvl'";
                                    $db->query($sql);
                                    
                                    $sql = "DELETE FROM ".XOCP_PREFIX."employee_level360_session"
                                         . " WHERE employee_id = '$employee_id'"
                                         . " AND assessor_id = '$assessor_id'"
                                         . " AND competency_id = '$competency_id'"
                                         . " AND proficiency_lvl > '$lvl'"
                                         . " AND asid = '$asid'";
                                    $db->query($sql);
                                    
                                    break;
                                 }
                                 
                                 
                              }
                              
                           }
                           
                           list($ccl,$rcl,$itj,$gap,$last_level,$ttl_level_value)=$this->fixCalcResult($employee_id,$job_idx,$competency_id,$assessor_id,1);
                           
                           $sql = "UPDATE ".XOCP_PREFIX."employee_competency360 SET ccl = '$ccl', asid_update = '$asid', last_level = '$last_level', cclxxx = '$ttl_level_value'"
                                . " WHERE employee_id = '$employee_id' AND competency_id = '$competency_id' AND assessor_id = '$assessor_id'";
                           $db->query($sql);
                           
                           $sql = "UPDATE ".XOCP_PREFIX."employee_competency360_session SET ccl = '$ccl', asid_update = '$asid', last_level = '$last_level', cclxxx = '$ttl_level_value'"
                                . " WHERE asid = '$asid' AND employee_id = '$employee_id' AND competency_id = '$competency_id' AND assessor_id = '$assessor_id'";
                           $db->query($sql);
                           
                        }
                     }
                  }
               }
               
               _calculate_competency($asid,$employee_id,$job_idx);
            }
         }
      }

      
      ss_timing_stop("recalcass");
      
      $ret = ss_timing_current("recalcass");
      
      return $ret;
      
      ////////////////////////////////////////////////////////////////////////////////////////
      ////////////////////////////////////////////////////////////////////////////////////////
      
   }
   
   

   function fixCalcResult($employee_id,$job_id,$competency_id,$assessor_id,$is_360=0) {
      $db=&Database::getInstance();
      $asid = $_SESSION["hris_assessment_asid"];
      $rcl = $itj = $gap = 0;
      $ccl = 0;
      $last_level = 0;
      $ttl_level_value = 0;
      
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
      for($i=1;$i<5;$i++) {
         if($i>$max_ccl) {
            
            /*
            if($_SESSION["assessor360"]==1) {
               $sql = "DELETE FROM ".XOCP_PREFIX."employee_level360"
                    . " WHERE employee_id = '$employee_id'"
                    . " AND competency_id = '$competency_id'"
                    . " AND proficiency_lvl = '$i'"
                    . " AND assessor_id = '$assessor_id'";
            } else {
               $sql = "DELETE FROM ".XOCP_PREFIX."employee_level"
                    . " WHERE employee_id = '$employee_id'"
                    . " AND competency_id = '$competency_id'"
                    . " AND proficiency_lvl = '$i'";
            }
            $db->query($sql);
            */
            
            continue;
         }
         if($is_360==1) {
            $sql = "SELECT fulfilled,level_value FROM ".XOCP_PREFIX."employee_level360_session"
                 . " WHERE employee_id = '$employee_id'"
                 . " AND competency_id = '$competency_id'"
                 . " AND proficiency_lvl = '$i'"
                 . " AND assessor_id = '$assessor_id'"
                 . " AND asid = '$asid'";
         } else {
            $sql = "SELECT fulfilled,level_value FROM ".XOCP_PREFIX."employee_level_session"
                 . " WHERE employee_id = '$employee_id'"
                 . " AND competency_id = '$competency_id'"
                 . " AND proficiency_lvl = '$i'"
                 . " AND assessor_id = '$assessor_id'"
                 . " AND asid = '$asid'";
         }
         $result = $db->query($sql);
         if($db->getRowsNum($result)==1) {
            list($fulfilled,$level_value)=$db->fetchRow($result);
            $ttl_level_value = _bctrim(bcadd($ttl_level_value,$level_value));
            if($fulfilled=="1") {
               $ccl = $i;
               $last_level = $i;
            } else if($fulfilled=="-1") {
               $last_level = $i;
               if($answer_t=="grade") {
                  $ccl = _bctrim(bcadd($ccl,$level_value));
               }
            } else {
               if($last_level>0) {
                  $last_level--;
               }
               break;
            }
         } else {
            break;
         }
      }
      
      $gap = $itj * ($ccl - $rcl);
      
      return array($ccl,$rcl,$itj,$gap,$last_level,$ttl_level_value);
   }
   
   
   
   
   
}

} /// HRIS_RECALCASSAJAX_DEFINED
?>