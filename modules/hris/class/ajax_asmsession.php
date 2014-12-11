<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_asmsession.php                  //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-12-17                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_ASSESSMENTSESSIONAJAX_DEFINED') ) {
   define('HRIS_ASSESSMENTSESSIONAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");
require_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");

$_SESSION["inactive_assessor"] = array("8"=>"Group Leader",
                                       "17"=>"Senior Clerk",
                                       "9"=>"Operator",
                                       "10"=>"Technician",
                                       "12"=>"Clerk",
                                       "11"=>"Analyst",
                                       "13"=>"Worker",
                                       "19"=>"Deputy Division Manager",
                                       "14"=>"President Director");


function assessor_sort($a,$b) {
   if($a[0]==$b[0]) {
      if($a[2]==$b[2]) {
         return ($a[6] < $b[6] ? -1 : 1);
      }
      return ($a[2] < $b[2] ? -1 : 1);
   }
   return ($a[0] < $b[0] ? -1 : 1);
}

function assessor_rsort($a,$b) {
   if($a[0]==$b[0]) {
      if($a[2]==$b[2]) {
         return ($a[6] > $b[6] ? -1 : 1);
      }
      return ($a[2] > $b[2] ? -1 : 1);
   }
   return ($a[0] > $b[0] ? -1 : 1);
}


class _hris_class_AssessmentSessionAjax extends AjaxListener {
   
   function _hris_class_AssessmentSessionAjax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/hris/class/ajax_asmsession.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_Delete","app_editSession","app_saveSession",
                            "app_generate360","app_generateSuperior","app_getPersonInfo",
                            "app_resetSessionPass","app_setAssessor","app_newSession",
                            "app_findAssessor","app_getDivisionList","app_addSchedule",
                            "app_saveSchedule","app_editSchedule","app_deleteSchedule",
                            "app_setAssessmentSession");
   }
   
   function balance_customer($asid) {
   
   }
   
   function app_setAssessmentSession($args) {
      $_SESSION["hris_assessment_asid"] = $args[0];
   }
   
   function balance_assessor($asid,$assessor_t) {
      $db=&Database::getInstance();
      
      //// select all by type of assessor, active / inactive
      $sql = "SELECT employee_id,assessor_id,status_cd FROM ".XOCP_PREFIX."assessor_360 WHERE asid = '$asid' AND assessor_t = '$assessor_t'";
      $result = $db->query($sql);
      $assessor = array();
      $assessor_active = array();
      $assessor_inactive = array();
      if($db->getRowsNum($result)>0) {
         while(list($employee_id,$assessor_id,$status_cd)=$db->fetchRow($result)) {
            
            /// check if there are other active type of assessor for the same pair
            /// if exists, then skip this pair
            $sql = "SELECT assessor_t FROM ".XOCP_PREFIX."assessor_360"
                 . " WHERE asid = '$asid' AND employee_id = '$employee_id' AND assessor_id = '$assessor_id' AND status_cd = 'active' AND assessor_t != '$assessor_t'";
            $rac = $db->query($sql);
            if($db->getRowsNum($rac)>0) continue;
            
            /// put in the respective variables
            $assessor[$employee_id][$assessor_id] = $assessor_id;
            if($status_cd=="active") {
               $assessor_active[$employee_id][$assessor_id] = $assessor_id;
            } else {
               $assessor_inactive[$employee_id][$assessor_id] = $assessor_id;
            }
            
         }
      }
      
      $arr_assessor = array();
      
      foreach($assessor as $employee_id=>$v) {
         $active_cnt = count($assessor_active[$employee_id]);
         $ttl_cnt = count($assessor[$employee_id]);
         $percent = 100*$active_cnt/$ttl_cnt;
         $arr_assessor[$employee_id] = array($percent,$ttl_cnt,$active_cnt,$assessor[$employee_id],$assessor_active[$employee_id],$assessor_inactive[$employee_id],$employee_id);
      }
      
      $arr_tmp = $arr_assessor;
      
      uasort($arr_assessor,"assessor_sort");
      uasort($arr_tmp,"assessor_rsort");
      
      $no=1;
      foreach($arr_assessor as $employee_id=>$v) {
         list($percent,$ttl_cnt,$active_cnt,$assessor,$assessor_active,$assessor_inactive)=$v;
         $no++;
      }
      
      
      for($i=0;$i<30;$i++) {
         uasort($arr_assessor,"assessor_sort");
         $arr_tmp = $arr_assessor;
         uasort($arr_tmp,"assessor_rsort");
         
         $swap = array();
         
         foreach($arr_assessor as $employee_id=>$v) {
            list($percent,$ttl_cnt,$active_cnt,$assessor,$assessor_active,$assessor_inactive)=$arr_assessor[$employee_id];
            $current_percent = $percent;
            $current_active_cnt = $active_cnt;
            $limit = $this->calc_limit($ttl_cnt,$assessor_t);
            
            $limit_percent = toMoney(100*$limit/$ttl_cnt);
            
            if($active_cnt>0&&$active_cnt>=($limit-1)) continue;
            
            foreach($arr_tmp as $more_employee_id=>$vv) {
               list($more_percent,$more_ttl_cnt,$more_active_cnt,$more_assessor,$more_assessor_active,$more_assessor_inactive)=$arr_assessor[$more_employee_id];
               $more_limit = $this->calc_limit($more_ttl_cnt,$assessor_t);
               
               if($more_active_cnt<=1) continue;
               
               if($more_active_cnt<=$active_cnt) continue;
               
               if($active_cnt<$limit&&$percent<$more_percent) {
                  foreach($assessor_inactive as $assessor_id=>$p) {
                     //if($swap[$more_employee_id][$assessor_id]==1) continue;
                     if($percent<$limit_percent&&$percent<$more_percent) {
                        if(isset($more_assessor_active[$assessor_id])) {
                           
                           //// swaping
                           unset($more_assessor_active[$assessor_id]);
                           $more_assessor_inactive[$assessor_id] = $assessor_id;
                           unset($assessor_inactive[$assessor_id]);
                           $assessor_active[$assessor_id] = $assessor_id;
                           
                           $swap[$employee_id][$assessor_id] = 1;
                           
                           $active_cnt = count($assessor_active);
                           $percent = 100*$active_cnt/$ttl_cnt;
                           
                           $more_active_cnt = count($more_assessor_active);
                           $more_percent = 100*$more_active_cnt/$more_ttl_cnt;
                           
                           unset($more_assessor_active[$assessor_id]);
                           
                           $arr_assessor[$more_employee_id] = array($more_percent,$more_ttl_cnt,$more_active_cnt,$more_assessor,$more_assessor_active,$more_assessor_inactive,$more_employee_id);
                           $arr_assessor[$employee_id] = array($percent,$ttl_cnt,$active_cnt,$assessor,$assessor_active,$assessor_inactive,$employee_id);
                        }
                     }
                  }
               }
            }
         }
      }
      
      uasort($arr_assessor,"assessor_sort");
      $no = 1;
      foreach($arr_assessor as $employee_id=>$v) {
         list($percent,$ttl_cnt,$active_cnt,$assessor,$assessor_active,$assessor_inactive)=$v;
         $no++;
         $sql = "UPDATE ".XOCP_PREFIX."assessor_360 SET status_cd = 'inactive' WHERE asid = '$asid' AND employee_id = '$employee_id' AND assessor_t = '$assessor_t'";
         $db->query($sql);
         foreach($assessor_active as $assessor_id) {
            $sql = "UPDATE ".XOCP_PREFIX."assessor_360 SET status_cd = 'active' WHERE asid = '$asid' AND employee_id = '$employee_id' AND assessor_id = '$assessor_id' AND assessor_t = '$assessor_t'";
            $db->query($sql);
         }
      }
      
   }
   
   function reduce_assessi($asid) {
      $db=&Database::getInstance();
      
      $sql = "SELECT assessi_max_superior, assessi_max_peer, assessi_max_customer FROM ".XOCP_PREFIX."assessment_session WHERE asid = '$asid'";
      $result = $db->query($sql);
      list($assessi_max_superior,$assessi_max_peer,$assessi_max_customer)=$db->fetchRow($result);
      
      $new_assessor = array();
      
      $sql = "SELECT assessor_id,assessor_t,COUNT(*) FROM ".XOCP_PREFIX."assessor_360 WHERE asid = '$asid' AND status_cd = 'active' GROUP BY assessor_id,assessor_t";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($assessor_id,$assessor_t,$cnt)=$db->fetchRow($result)) {
            switch($assessor_t) {
               case "peer": /// var : $assessi_max_peer
                  if($cnt>$assessi_max_peer) {
                     _debuglog("$assessor_id = $cnt > $assessi_max_peer");
                  }
                  break;
               case "subordinat": /// var : $assessi_max_superior
                  if($cnt>$assessi_max_superior) {
                     _debuglog("$assessor_id = $cnt > $assessi_max_superior");
                  }
                  break;
               case "customer": /// var : $assessi_max_customer
                  if($cnt>$assessi_max_customer) {
                     _debuglog("$assessor_id = $cnt > $assessi_max_superior");
                  }
                  break;
               default:
                  break;
            }
         }
      }
   }
   
   function app_deleteSchedule($args) {
      $db=&Database::getInstance();
      $schedule_id = $args[0];
      $user_id = getUserID();
      $sql = "UPDATE ".XOCP_PREFIX."assessment_schedule SET status_cd = 'nullified', nullified_user_id = '$user_id' WHERE schedule_id = '$schedule_id'";
      $db->query($sql);
   }
   
   function app_editSchedule($args) {
      $db=&Database::getInstance();
      $schedule_id = $args[0];
      $sql = "SELECT start_dttm,stop_dttm,assessment_superior,assessment_subordinate,assessment_peer,assessment_customer"
           . " FROM ".XOCP_PREFIX."assessment_schedule WHERE schedule_id = '$schedule_id'";
      $result = $db->query($sql);
      list($start_dttm,$stop_dttm,$assessment_superior,$assessment_subordinate,$assessment_peer,$assessment_customer)=$db->fetchRow($result);
      $ret = "<div style='padding:10px;' id='frmschedule'>"
           . "<table class='xxfrm' style='width:100%;border:1px solid #777;-moz-box-shadow:1px 1px 3px #000;'><tbody>"
           . "<tr>"
           . "<td>Start : </td>"
           . "<td>"
           . "<span class='xlnk' id='spstart_${schedule_id}' onclick='_changedatetime(\"spstart_${schedule_id}\",\"start_dttm_${schedule_id}\",\"datetime\",false,false)'>".sql2ind($start_dttm)."</span>"
           . "<input type='hidden' id='start_dttm_${schedule_id}' name='start_dttm' value='$start_dttm'/>"
           . "</td>"
           . "<td>Stop : </td>"
           . "<td>"
           . "<span class='xlnk' id='spstop_${schedule_id}' onclick='_changedatetime(\"spstop_${schedule_id}\",\"stop_dttm_${schedule_id}\",\"datetime\",false,false)'>".sql2ind($stop_dttm)."</span>"
           . "<input type='hidden' id='stop_dttm_${schedule_id}' name='stop_dttm' value='$stop_dttm'/>"
           . "</td>"
           . "</tr>"
           
           . "<tr><td colspan='3' style='text-align:left;padding-left:15px;'>"
               . "<div><input name='assessment_superior' type='checkbox' id='ckb_superior' value='1' ".($assessment_superior==1?"checked='checked'":"")."/> <label class='xlnk' for='ckb_superior'>Superior Assessment</label></div>"
               . "<div><input name='assessment_subordinate' type='checkbox' id='ckb_subordinate' value='1' ".($assessment_subordinate==1?"checked='checked'":"")."/> <label class='xlnk' for='ckb_subordinate'>Subordinate Assessment</label></div>"
               . "<div><input name='assessment_peer' type='checkbox' id='ckb_peer' value='1' ".($assessment_peer==1?"checked='checked'":"")."/> <label class='xlnk' for='ckb_peer'>Peer Assessment</label></div>"
               . "<div><input name='assessment_customer' type='checkbox' id='ckb_customer' value='1' ".($assessment_customer==1?"checked='checked'":"")."/> <label class='xlnk' for='ckb_customer'>Customer Assessment</label></div>"
           . "</td></tr>"
           
           . "<tr><td colspan='4' style='padding:5px;'>"
           . "<input type='button' value='"._SAVE."' onclick='save_schedule(\"$schedule_id\",this,event)'/>&nbsp;"
           . "<input type='button' value='"._CANCEL."' onclick='cancel_edit_schedule();'/>&nbsp;&nbsp;"
           . "<input type='button' value='"._DELETE."' onclick='delete_schedule(\"$schedule_id\",this,event);'/></td></tr>"
           . "</tbody></table>"
           . "</div>";
      return $ret;
   }
   
   function app_saveSchedule($args) {
      $db=&Database::getInstance();
      $schedule_id = $args[0];
      $vars = _parseForm($args[1]);
      $start_dttm = getSQLDate($vars["start_dttm"]);
      $stop_dttm = getSQLDate($vars["stop_dttm"]);
      $assessment_superior = $vars["assessment_superior"]+0;
      $assessment_subordinate = $vars["assessment_subordinate"]+0;
      $assessment_peer = $vars["assessment_peer"]+0;
      $assessment_customer = $vars["assessment_customer"]+0;
      
      $sql = "UPDATE ".XOCP_PREFIX."assessment_schedule SET start_dttm = '$start_dttm', stop_dttm = '$stop_dttm',"
           . "assessment_superior = '$assessment_superior',"
           . "assessment_subordinate = '$assessment_subordinate',"
           . "assessment_peer = '$assessment_peer',"
           . "assessment_customer = '$assessment_customer'"
           . " WHERE schedule_id = '$schedule_id'";
      $db->query($sql);
      return array($schedule_id,sql2ind($start_dttm),sql2ind($stop_dttm));
   }
   
   function app_addSchedule($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $asid = $args[0];
      $org_id = $args[1];
      $schedule_id = 0;
      $sql = "SELECT MAX(schedule_id) FROM ".XOCP_PREFIX."assessment_schedule";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($schedule_id)=$db->fetchRow($result);
      }
      $schedule_id++;
      
      $sql = "SELECT now(),DATE_ADD(now(), INTERVAL 1 WEEK)";
      $result = $db->query($sql);
      list($start_dttm,$stop_dttm)=$db->fetchRow($result);
      
      
      $sql = "INSERT INTO ".XOCP_PREFIX."assessment_schedule (asid,schedule_id,org_id,start_dttm,stop_dttm,created_user_id) VALUES ('$asid','$schedule_id','$org_id','$start_dttm','$stop_dttm','$user_id')";
      $db->query($sql);
      
      $sql = "SELECT a.org_nm,b.org_class_nm FROM ".XOCP_PREFIX."orgs a LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id) WHERE a.org_id = '$org_id'";
      $result = $db->query($sql);
      list($org_nm,$org_class_nm)=$db->fetchRow($result);
      
      
      $org = "<span class='xlnk' onclick='edit_schedule(\"$asid\",\"$schedule_id\",this,event);'>$org_nm $org_class_nm</span>";
      $start = "<span id='sptxtstart_${schedule_id}'>".sql2ind($start_dttm)."</span>";
      $stop = "<span id='sptxtstop_${schedule_id}'>".sql2ind($stop_dttm)."</span>";
      
      /*
      $start = "<span class='xlnk' id='spstart_${schedule_id}' onclick='_changedatetime(\"spstart_${schedule_id}\",\"start_dttm_${schedule_id}\",\"datetime\",false,false)'>".sql2ind($start_dttm)."</span>"
             . "<input type='hidden' id='start_dttm_${schedule_id}' value='$start_dttm'/>";
      $stop = "<span class='xlnk' id='spstop_${schedule_id}' onclick='_changedatetime(\"spstop_${schedule_id}\",\"stop_dttm_${schedule_id}\",\"datetime\",false,false)'>".sql2ind($stop_dttm)."</span>"
            . "<input type='hidden' id='stop_dttm_${schedule_id}' value='$stop_dttm'/>";
      */
      
      return array($schedule_id,$org,$start,$stop);
      
   }
   
   function app_getDivisionList($args) {
      $db=&Database::getInstance();
      $asid = $args[0];
      $ret = "<div style='padding:2px;text-align:left;max-height:200px;overflow:auto;'>";
      
      $ret .= "<div style='border:1px solid #bbb;background-color:#ddd;padding:3px;text-align:center;'>Company</div><div>";
      $sql = "SELECT org_id,org_nm FROM ".XOCP_PREFIX."orgs WHERE org_class_id = '1' ORDER BY order_no";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($org_id,$org_nm)=$db->fetchRow($result)) {
            $ret .= "<div class='cb' onclick='add_org(\"$asid\",\"$org_id\",this,event);' style='padding:3px;border-bottom:1px solid #ddd;'>$org_nm</div>";
         }
      }
      $ret .= "</div>";
      
      $ret .= "<div style='border:1px solid #bbb;background-color:#ddd;padding:3px;text-align:center;'>Department</div><div>";
      $sql = "SELECT org_id,org_nm FROM ".XOCP_PREFIX."orgs WHERE org_class_id = '2' ORDER BY order_no";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($org_id,$org_nm)=$db->fetchRow($result)) {
            $ret .= "<div class='cb' onclick='add_org(\"$asid\",\"$org_id\",this,event);' style='padding:3px;border-bottom:1px solid #ddd;'>$org_nm</div>";
         }
      }
      $ret .= "</div>";
      
      $ret .= "<div style='border:1px solid #bbb;background-color:#ddd;padding:3px;text-align:center;'>Division</div><div>";
      $sql = "SELECT org_id,org_nm FROM ".XOCP_PREFIX."orgs WHERE org_class_id = '3' ORDER BY order_no";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($org_id,$org_nm)=$db->fetchRow($result)) {
            $ret .= "<div class='cb' onclick='add_org(\"$asid\",\"$org_id\",this,event);' style='padding:3px;border-bottom:1px solid #ddd;'>$org_nm</div>";
         }
      }
      $ret .= "</div>";
      
      $ret .= "<div style='border:1px solid #bbb;background-color:#ddd;padding:3px;text-align:center;'>Section</div><div>";
      $sql = "SELECT org_id,org_nm FROM ".XOCP_PREFIX."orgs WHERE org_class_id = '4' ORDER BY order_no";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($org_id,$org_nm)=$db->fetchRow($result)) {
            $ret .= "<div class='cb' onclick='add_org(\"$asid\",\"$org_id\",this,event);' style='padding:3px;border-bottom:1px solid #ddd;'>$org_nm</div>";
         }
      }
      $ret .= "</div>";
      
      
      $ret .= "</div>";
      return $ret;
   }
   
   function app_setAssessor($args) {
      $db=&Database::getInstance();
      $asid = $args[0];
      $employee_id = $args[1];
      $assessor_id = $args[2];
      $assessor_t = $args[3];
      $id = $args[4];
      $user_id = getUserID();
      $sql = "SELECT status_cd FROM ".XOCP_PREFIX."assessor_360"
           . " WHERE asid = '$asid'"
           . " AND employee_id = '$employee_id'"
           . " AND assessor_id = '$assessor_id'"
           . " AND assessor_t = '$assessor_t'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($status_cd)=$db->fetchRow($result);
         
         $new_status = ($status_cd!="active"?"active":"inactive");
         
         
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
              $emp_user_id,
              $first_assessor_job_id,
              $next_assessor_job_id)=_hris_getinfobyemployeeid($employee_id);
         
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
              $ass_user_id,
              $first_assessor_job_id,
              $next_assessor_job_id)=_hris_getinfobyemployeeid($assessor_id);
         
         _activitylog("ASSESSMENT_SETUP",0,"Change $assessor_t assessor status to $new_status for employee = $emp_nm, assessor = $ass_nm and asid = $asid.");
         
         _dumpvar($args);
         
         $sql = "UPDATE ".XOCP_PREFIX."assessor_360 SET status_cd = '".($status_cd!="active"?"active":"inactive")."', override_dttm = now(), override_user_id = '$user_id'"
              . " WHERE asid = '$asid'"
              . " AND employee_id = '$employee_id'"
              . " AND assessor_id = '$assessor_id'"
              . " AND assessor_t = '$assessor_t'";
         $result = $db->query($sql);
         $sql = "SELECT status_cd FROM ".XOCP_PREFIX."assessor_360"
              . " WHERE asid = '$asid'"
              . " AND employee_id = '$employee_id'"
              . " AND assessor_id = '$assessor_id'"
              . " AND assessor_t = '$assessor_t'";
         $result = $db->query($sql);
         if($db->getRowsNum($result)==1) {
            list($status_cd)=$db->fetchRow($result);
            
            
            if($assessor_id>0&&$new_status=="active") {
               $sql = "SELECT a.employee_ext_id,a.person_id,b.user_id FROM ".XOCP_PREFIX."employee a"
                    . " LEFT JOIN ".XOCP_PREFIX."users b USING(person_id)"
                    . " WHERE a.employee_id = '$assessor_id'";
               $result = $db->query($sql);
               list($nip,$person_id,$user_idx)=$db->fetchRow($result);
               if($user_idx==0) {
                  $sql = "INSERT INTO ".XOCP_PREFIX."users (person_id,user_nm,status_cd,startpage,pgroup_id,last_page_id)"
                       . " VALUES ('$person_id','$nip','active','assessment','4','assessment')";
                  $db->query($sql);
                  $user_idx = $db->getInsertId();
                  $sql = "INSERT INTO ".XOCP_PREFIX."user_pgroup (user_id,pgroup_id,last_page_id,last_menuitem_id)"
                       . " VALUES ('$user_idx','4','assessment','32')";
                  $db->query($sql);
               }
               
               $sql = "UPDATE ".XOCP_PREFIX."users SET pgroup_id = '4', last_page_id = 'assessment', startpage = 'assessment'"
                    . " WHERE user_id = '$user_idx'";
               $db->query($sql);
               $sql = "UPDATE ".XOCP_PREFIX."user_pgroup SET last_page_id = 'assessment', last_menuitem_id = '32'"
                    . " WHERE user_id = '$user_idx'";
               $db->query($sql);
                
               $pass = substr(md5(uniqid(rand())), 2, 5);
               $sql = "INSERT INTO ".XOCP_PREFIX."assessor_pass (asid,employee_id,pwd0,pwd1,generate_user_id)"
                    . " VALUES ('$asid','$assessor_id',md5('$pass'),'$pass','$user_id')";
               $db->query($sql);
            }
            return array($id,$status_cd,($status_cd=="active"?"Set Inactive":"Set Active"));
         } else {
            return "FAIL";
         }  
      }
      return "FAIL";
   }
   
   function app_resetSessionPass($args) {
      $db=&Database::getInstance();
      $asid = $args[0];
      $sql = "SELECT assessor_id FROM ".XOCP_PREFIX."assessor_360"
           . " WHERE asid = '$asid' AND status_cd = 'active' GROUP BY assessor_id";
      $result = $db->query($sql);
      $assessors = array();
      if($db->getRowsNum($result)>0) {
         while(list($assessor_id)=$db->fetchRow($result)) {
            $assessors[$assessor_id] = 1;
         }
      }
      
      
      $sql = "SELECT b.employee_id FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job b ON b.job_id = a.assessor_job_id"
           . " GROUP BY b.employee_id";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($assessor_id)=$db->fetchRow($result)) {
            $assessors[$assessor_id] = 1;
         }
      }
      
      $sql = "DELETE FROM ".XOCP_PREFIX."assessor_pass WHERE asid = '$asid'";
      $db->query($sql);
      $user_id = getUserID();
      foreach($assessors as $employee_id=>$v) {
         if($employee_id>0) {
            /*
            $sql = "SELECT b.job_class_id FROM ".XOCP_PREFIX."employee_job a LEFT JOIN ".XOCP_PREFIX."jobs b USING(job_id) WHERE a.employee_id = '$employee_id'";
            $rtrap = $db->query($sql);
            if($db->getRowsNum($rtrap)>0) {
               list($assessor_job_class_id)=$db->fetchRow($rtrap);
               if(isset($_SESSION["inactive_assessor"][$assessor_job_class_id])) {
                  continue;
               }
            }
            */
            
            
            $sql = "SELECT a.employee_ext_id,a.person_id,b.user_id FROM ".XOCP_PREFIX."employee a"
                 . " LEFT JOIN ".XOCP_PREFIX."users b USING(person_id)"
                 . " WHERE a.employee_id = '$employee_id'";
            $result = $db->query($sql);
            list($nip,$person_id,$user_idx)=$db->fetchRow($result);
            if($user_idx==0) {
               $sql = "INSERT INTO ".XOCP_PREFIX."users (person_id,user_nm,status_cd,startpage,pgroup_id,last_page_id)"
                    . " VALUES ('$person_id','$nip','active','assessment','4','assessment')";
               $db->query($sql);
               $user_idx = $db->getInsertId();
               $sql = "INSERT INTO ".XOCP_PREFIX."user_pgroup (user_id,pgroup_id,last_page_id,last_menuitem_id)"
                    . " VALUES ('$user_idx','4','assessment','32')";
               $db->query($sql);
            }
            
            $sql = "UPDATE ".XOCP_PREFIX."users SET pgroup_id = '4', last_page_id = 'assessment', startpage = 'assessment'"
                 . " WHERE user_id = '$user_idx'";
            $db->query($sql);
            $sql = "UPDATE ".XOCP_PREFIX."user_pgroup SET last_page_id = 'assessment', last_menuitem_id = '32'"
                 . " WHERE user_id = '$user_idx'";
            $db->query($sql);
             
            $pass = substr(md5(uniqid(rand())), 2, 5);
            $sql = "INSERT INTO ".XOCP_PREFIX."assessor_pass (asid,employee_id,pwd0,pwd1,generate_user_id)"
                 . " VALUES ('$asid','$employee_id',md5('$pass'),'$pass','$user_id')";
            $db->query($sql);
         }
      }
      return "OK";
   }
   
   
   function app_getPersonInfo($args) {
      $db=&Database::getInstance();
      $assessor_id = $args[0];
      $employee_id = $args[1];
      $assessor_t = $args[2];
      $asid = $_SESSION["hris_check_asid"];
      $id = $args[3];
      $sql = "SELECT b.employee_ext_id,c.person_nm,a.gradeval,c.birth_dttm,c.birthplace,"
           . "c.adm_gender_cd,c.addr_txt,c.cell_phone,c.home_phone,c.marital_st,"
           . "b.entrance_dttm,a.start_dttm,a.stop_dttm,(TO_DAYS(now())-TO_DAYS(b.entrance_dttm)) as jobage,"
           . "a.job_id,d.job_cd,d.job_nm,c.person_id,o.org_nm,p.org_class_nm"
           . " FROM ".XOCP_PREFIX."employee_job a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " LEFT JOIN ".XOCP_PREFIX."jobs d ON d.job_id = a.job_id"
           . " LEFT JOIN ".XOCP_PREFIX."orgs o ON o.org_id = d.org_id"
           . " LEFT JOIN ".XOCP_PREFIX."org_class p ON p.org_class_id = o.org_class_id"
           . " WHERE a.employee_id = '$assessor_id'";
      $res2 = $db->query($sql);
      list($nip,$employee_nm,$gradeval,$dob,$pob,$gender,$addr,$cellphone,$phone,$marital,
           $entrance_dttm,$jobstart,$jobstop,$jobage,$job_id,$job_cd,$job_nm,$person_id,$org_nm,$org_class_nm)=$db->fetchRow($res2);
      $assessor_tx = $assessor_st = "";
      $btn = "";
      switch($assessor_t) {
         case "superior":
         case "subordinat":
         case "peer":
         case "customer":
            $sql = "SELECT assessor_t,status_cd FROM ".XOCP_PREFIX."assessor_360 WHERE asid = '$asid'"
                 . " AND employee_id = '$employee_id'"
                 . " AND assessor_id = '$assessor_id'"
                 . " AND assessor_t = '$assessor_t'";
            $result = $db->query($sql);
            if($db->getRowsNum($result)==1) {
               list($assessor_tx,$assessor_st)=$db->fetchRow($result);
            }
            if($assessor_t==$assessor_tx&&$assessor_st=="active") {
               $btn = "<input type='button' value='Set Inactive' onclick='assessor_set(\"$asid\",\"$employee_id\",\"$assessor_id\",\"$assessor_t\",\"$id\",this,event);'/>";
            } else {
               $btn = "<input type='button' value='Set Active' onclick='assessor_set(\"$asid\",\"$employee_id\",\"$assessor_id\",\"$assessor_t\",\"$id\",this,event);'/>";
            }
            break;
         default:
            break;
      }
      
      $person_info = "<table style='width:100%;'><tbody><tr><td style='vertical-align:top;'><img src='".XOCP_SERVER_SUBDIR."/modules/hris/thumb.php?pid=$person_id' height='80' width='60'/></td>"
                . "<td style='vertical-align:top;'><table class='emp_info' style='margin-top:0px;width:390px;'>"
                . "<colgroup><col width='80'/><col/></colgroup>"
                . "<tbody>"
                . "<tr><td>Employee ID :</td><td>$nip</td></tr>"
                . "<tr><td>Entrance Date :</td><td>".sql2ind($entrance_dttm,"date")." ("._bctrim(toMoney($jobage/365.25))." year)</td></tr>"
                . "<tr><td>Job Title :</td><td>$job_nm</td></tr>"
                . "<tr><td>$org_class_nm :</td><td>$org_nm</td></tr>"
                . "<tr><td>Gender :</td><td>".($gender=="f"?"Female":"Male")."</td></tr>"
                . "<tr><td colspan='2'>$btn</td></tr>"
                . "</tbody></table></td></tr>"
                . "</tbody></table>";
      return $person_info;
   }
   
   
   
   function getOrgsUp($org_id) {
      $db=&Database::getInstance();
      $sql = "SELECT parent_id FROM ".XOCP_PREFIX."orgs WHERE org_id = '$org_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($parent_id)=$db->fetchRow($result);
         if($parent_id>0) {
            $_SESSION["hris_org_parents"][] = $parent_id;
            $this->getOrgsUp($parent_id);
         }
      }
   }
   
   function generateSuperiorAssessor($job_id) {
      $db=&Database::getInstance();
      $_SESSION["hris_org_parents"] = array();
      $sql = "SELECT a.org_id,a.job_class_id,a.assessor_job_id,"
           . "b.job_class_level"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
           . " WHERE a.job_id = '$job_id'";
      $result = $db->query($sql);
      list($org_id,$job_class_id,$assessor_job_id,$job_class_level)=$db->fetchRow($result);
      $_SESSION["hris_org_parents"][] = $org_id;
      $this->getOrgsUp($org_id);
      
      $opt_assessor = "";
      if(count($_SESSION["hris_org_parents"])>0) {
         $no = 0;
         foreach($_SESSION["hris_org_parents"] as $org_idx) {
            $sql = "SELECT a.job_id,a.job_cd,a.job_nm,b.job_class_nm,a.job_class_id,b.job_class_level"
                 . " FROM ".XOCP_PREFIX."jobs a"
                 . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
                 . " WHERE a.org_id = '$org_idx'"
                 . " ORDER BY b.job_class_level DESC,a.job_class_id,a.job_nm";
            $result = $db->query($sql);
            if($db->getRowsNum($result)>0) {
               while(list($job_idx,$job_cdx,$job_nmx,$job_class_nmx,$job_class_idx,$job_class_levelx)=$db->fetchRow($result)) {
                  if($job_class_levelx>=$job_class_level) continue;        /// minimum adalah 1 level diatas
                  if($job_class_levelx>_HRIS_MAX_ASSESSOR_LEVEL) continue; /// batas assessor terendah adalah supervisor (job_class_level = 70)
                  $sassessor = "";
                  
                  /// check non empty job
                  $sql = "SELECT employee_id FROM ".XOCP_PREFIX."employee_job WHERE job_id = '$job_idx'";
                  $rem = $db->query($sql);
                  if($db->getRowsNum($rem)==0) {
                     //// $job_nmx is empty;
                     continue;
                  }
                  
                  
                  //// update default non empty job assessor
                  $sql = "UPDATE ".XOCP_PREFIX."jobs SET assessor_job_id = '$job_idx' WHERE job_id = '$job_id'";
                  $db->query($sql);
                  return;
                  
                  $no++;
               }
            }
         }
      }
   }
   
   function app_generateSuperior($args) {
      $db=&Database::getInstance();
      
      $sql = "SELECT a.job_id"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " WHERE a.status_cd = 'normal'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($job_id)=$db->fetchRow($result)) {
            $this->generateSuperiorAssessor($job_id);
         }
      }
   }
   
   function drop_subordinate(&$arr) {
      $db=&Database::getInstance();
      if(is_array($arr)&&count($arr)>0) {
      
      }
   }
   
   function activate_peer($asid) {
      $db=&Database::getInstance();
      $sql = "SELECT job_id,job_nm FROM ".XOCP_PREFIX."jobs WHERE upper_job_id = '0'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($job_id,$job_nm)=$db->fetchRow($result);
         $_SESSION["job_emp"] = array();
         $arr["$job_id|$job_nm"] = $this->recurse_activate_peer($asid,$job_id);
      }
   }
   
   function recurse_activate_peer($asid,$job_id) {
      $db=&Database::getInstance();
      global $assessi_max_superior,$assessi_max_peer,$assessi_max_customer;
      
      $sub_job = array();
      
      /// select sub job
      $sql = "SELECT job_id,job_nm FROM ".XOCP_PREFIX."jobs WHERE upper_job_id = '$job_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($sub_job_id,$sub_job_nm)=$db->fetchRow($result)) {
            $this->recurse_activate_peer($asid,$sub_job_id);
            $candidate = array();
            
            $sql = "SELECT employee_id FROM ".XOCP_PREFIX."employee_job WHERE job_id = '$sub_job_id'";
            $remp = $db->query($sql);
            if($db->getRowsNum($remp)>0) {
               while(list($employee_id)=$db->fetchRow($remp)) {
                  
                  $sql = "SELECT assessor_id,status_cd FROM ".XOCP_PREFIX."assessor_360"
                       . " WHERE asid = '$asid' AND employee_id = '$employee_id' AND assessor_t = 'peer'";
                  $rpeer = $db->query($sql);
                  $peers = array();
                  if($db->getRowsNum($rpeer)>0) {
                     while(list($assessor_idx)=$db->fetchRow($rpeer)) {
                        $sql = "SELECT assessor_t FROM ".XOCP_PREFIX."assessor_360"
                             . " WHERE asid = '$asid' AND employee_id = '$employee_id' AND assessor_id = '$assessor_idx' AND status_cd = 'active'";
                        $rac = $db->query($sql);
                        if($db->getRowsNum($rac)>0) continue;
                        $peers[] = $assessor_idx;
                     }
                  }
                  $active = array();
                  $inactive = array();
                  foreach($peers as $assessor_id) {
                     $sql = "SELECT 'ACTIVATE PEER: $assessor_id',assessor_id,assessor_t,status_cd FROM ".XOCP_PREFIX."assessor_360"
                          . " WHERE asid = '$asid' AND assessor_id = '$assessor_id' GROUP BY status_cd";
                     $ras = $db->query($sql);
                     if($db->getRowsNum($ras)>0) {
                        while(list($unu,$assessor_idx,$assessor_tx,$status_cdx)=$db->fetchRow($ras)) {
                           
                           /// limit assessi
                           $sql = "SELECT COUNT(*) FROM ".XOCP_PREFIX."assessor_360 WHERE asid = '$asid' AND assessor_id = '$assessor_idx' AND assessor_t = 'peer' AND status_cd = 'active'";
                           $rc = $db->query($sql);
                           list($cnt)=$db->fetchRow($rc);
                           ///
                           
                           if($status_cdx=="active") {
                              if($cnt<$assessi_max_peer) {
                                 $active[$assessor_idx] = $assessor_idx;
                                 unset($inactive[$assessor_idx]);
                              } else {
                                 unset($active[$assessor_idx]);
                                 unset($inactive[$assessor_idx]);
                              }
                           } else {
                              if($cnt<$assessi_max_peer&&!isset($active[$assessor_idx])) {
                                 $inactive[$assessor_idx] = $assessor_idx;
                              }
                           }
                        }
                     }
                  }
                  
                  $count = count($active)+count($inactive);
                  
                  $limit = $this->calc_limit($count,"peer");
                  if(count($active)<$limit) {
                     $n = $limit - count($active);
                     shuffle($inactive);
                     for($i=0;$i<$n;$i++) {
                        $ck_inactive_assessor_id = array_shift($inactive);
                        ///// trap for unwanted assessor ...... 2011-01-03
                        $sql = "SELECT b.job_class_id FROM ".XOCP_PREFIX."employee_job a LEFT JOIN ".XOCP_PREFIX."jobs b USING(job_id) WHERE a.employee_id = '$ck_inactive_assessor_id'";
                        $rtrap = $db->query($sql);
                        if($db->getRowsNum($rtrap)>0) {
                           list($assessor_job_class_id)=$db->fetchRow($rtrap);
                           if(isset($_SESSION["inactive_assessor"][$assessor_job_class_id])) {
                              $i--;
                              continue;
                           }
                        }
                        $active[] = $ck_inactive_assessor_id;
                        ///////////////////////////////////////
                        /*
                        $active[] = $ck_inactive_assessor_id = array_shift($inactive);
                        //_debuglog("Unwanted inactive assessor : $ck_inactive_assessor_id");
                        ///// trap for unwanted assessor ...... 2011-01-03
                        $sql = "SELECT b.job_class_id FROM ".XOCP_PREFIX."employee_job a LEFT JOIN ".XOCP_PREFIX."jobs b USING(job_id) WHERE a.employee_id = '$ck_inactive_assessor_id'";
                        $rtrap = $db->query($sql);
                        if($db->getRowsNum($rtrap)>0) {
                           list($assessor_job_class_id)=$db->fetchRow($rtrap);
                           if(isset($_SESSION["inactive_assessor"][$assessor_job_class_id])) {
                              continue;
                           }
                        }
                        ///////////////////////////////////////
                        */
                        
                     }
                  } else {
                     $n = count($active);
                     shuffle($active);
                     for($i=0;$i<$n;$i++) {
                        if($i>($limit-1)) {
                           $inactive[] = array_shift($active);
                        }
                     }
                  }
                  
                  foreach($active as $assessor_id) {
                     $sql = "UPDATE ".XOCP_PREFIX."assessor_360 SET status_cd = 'active'"
                          . " WHERE asid = '$asid'"
                          . " AND employee_id = '$employee_id'"
                          . " AND assessor_id = '$assessor_id'"
                          . " AND assessor_t = 'peer'";
                     $db->query($sql);
                  }
               }
            }
            
            
            
         }
      }
   }
   
   
   //////////////////////////////////////////////////////////////////////////////////////
   
   function activate_customer($asid) {
      $db=&Database::getInstance();
      
      //$this->recurse_activate_customer($asid);
      $this->prioritized_activate_customer($asid);
   }
   
   function prioritized_activate_customer($asid) {
      $db=&Database::getInstance();
      global $assessi_max_superior,$assessi_max_peer,$assessi_max_customer;
      
      $arr_employee = array();
      $arr_assessor = array();
      
      $sql = "SELECT assessor_id,employee_id FROM ".XOCP_PREFIX."assessor_360 WHERE asid = '$asid' AND assessor_t = 'customer'";
      $remp = $db->query($sql);
      if($db->getRowsNum($remp)>0) {
         while(list($assessor_idx,$employee_idx)=$db->fetchRow($remp)) {
            $sql = "SELECT job_id FROM ".XOCP_PREFIX."employee_job WHERE employee_id = '$employee_idx'";
            $rcc = $db->query($sql);
            list($employee_job_id)=$db->fetchRow($rcc);
            $sql = "SELECT job_id FROM ".XOCP_PREFIX."employee_job WHERE employee_id = '$assessor_idx'";
            $rcc = $db->query($sql);
            list($assessor_job_id)=$db->fetchRow($rcc);
            
            $sql = "SELECT priority_no FROM ".XOCP_PREFIX."customer_matrix WHERE provider_job_id = '$employee_job_id' AND customer_job_id = '$assessor_job_id'";
            $rcc = $db->query($sql);
            if($db->getRowsNum($rcc)>0) {
               list($priority_no)=$db->fetchRow($rcc);
            } else {
               $priority_no = 50;
            }
            
            $arr_employee[$employee_idx][$priority_no][$assessor_idx] = 1;
            $arr_assessor[$assessor_idx][$priority_no][$employee_idx] = 1;
         }
      }
      
      $arr_active = array();
      
      ksort($arr_employee);
      foreach($arr_employee as $employee_id=>$v) {
         ksort($v);
         $ttl_assessor_cnt = 0;
         foreach($v as $priority_no=>$vv) {
            foreach($vv as $assessor_idx=>$vvv) {
               $ttl_assessor_cnt++;
            }
         }
         $cnt = 0;
         $limit = $this->calc_limit($ttl_assessor_cnt,"customer");
         foreach($v as $priority_no=>$vv) {
            ksort($vv);
            foreach($vv as $assessor_id=>$vvv) {
               
               /// don't add if already assessor for the same employee
               $sql = "SELECT assessor_t FROM ".XOCP_PREFIX."assessor_360"
                    . " WHERE asid = '$asid' AND employee_id = '$employee_id' AND assessor_id = '$assessor_id' AND status_cd = 'active'";
               $rac = $db->query($sql);
               if($db->getRowsNum($rac)>0) continue;
               
               /// check if assessor already reach the limit of assessi count, then skip
               $sql = "SELECT COUNT(*) FROM ".XOCP_PREFIX."assessor_360"
                    . " WHERE asid = '$asid' AND assessor_id = '$assessor_id' AND assessor_t = 'customer' AND status_cd = 'active'";
               $rac = $db->query($sql);
               list($assessi_cnt)=$db->fetchRow($rac);
               if($assessi_cnt>=$assessi_max_customer) continue;
               
               if($cnt<$limit) {
                  $arr_active[$employee_id][$assessor_id]=1;
                  $sql = "UPDATE ".XOCP_PREFIX."assessor_360 SET status_cd = 'active'"
                       . " WHERE asid = '$asid'"
                       . " AND employee_id = '$employee_id'"
                       . " AND assessor_id = '$assessor_id'"
                       . " AND assessor_t = 'customer'";
                  $db->query($sql);
                  $cnt++;
               }
               
               
            }
         }
      }
      
      /*
      ksort($arr_assessor);
      foreach($arr_assessor as $assessor_id=>$v) {
         ksort($v);
         $cnt = 0;
         if($assessor_id==184) _dumpvar($v);
         foreach($v as $priority_no=>$vv) {
            ksort($vv);
            foreach($vv as $employee_id=>$vvv) {
               if($assessor_id==184) _debuglog("$assessor_id [$priority_no] $employee_id");
               
               /// don't add if already assessor for the same employee
               $sql = "SELECT assessor_t FROM ".XOCP_PREFIX."assessor_360"
                    . " WHERE asid = '$asid' AND employee_id = '$employee_id' AND assessor_id = '$assessor_id' AND status_cd = 'active'";
               $rac = $db->query($sql);
               if($db->getRowsNum($rac)>0) continue;
               
               if($cnt<$assessi_max_customer) {
                  $sql = "UPDATE ".XOCP_PREFIX."assessor_360 SET status_cd = 'active'"
                       . " WHERE asid = '$asid'"
                       . " AND employee_id = '$employee_id'"
                       . " AND assessor_id = '$assessor_id'"
                       . " AND assessor_t = 'customer'";
                  $db->query($sql);
                  if($assessor_id==184) _debuglog("------------- $assessor_id [$priority_no] $employee_id");
                  $cnt++;
               }
               
               
            }
         }
      }
      */
      
      
   
   }
   
   function recurse_activate_customer($asid,$employee_id=NULL) {
      $db=&Database::getInstance();
      global $assessi_max_superior,$assessi_max_peer,$assessi_max_customer;
      
      $arr_employee = array();
      if($employee_id==NULL) {
         $sql = "SELECT employee_id FROM ".XOCP_PREFIX."assessor_360 WHERE asid = '$asid' AND assessor_t = 'customer' GROUP BY employee_id";
         $remp = $db->query($sql);
         if($db->getRowsNum($remp)>0) {
            while(list($employee_idx)=$db->fetchRow($remp)) {
               $arr_employee[$employee_idx] = $employee_idx;
            }
         }
      } else {
         $arr_employee[$employee_id] = $employee_id;
      }
      
      foreach($arr_employee as $employee_id) {
         $sql = "SELECT job_id FROM ".XOCP_PREFIX."employee_job WHERE employee_id = '$employee_id'";
         $rcc = $db->query($sql);
         list($employee_job_id)=$db->fetchRow($rcc);
         
         $sql = "SELECT assessor_id,status_cd FROM ".XOCP_PREFIX."assessor_360"
              . " WHERE asid = '$asid' AND employee_id = '$employee_id' AND assessor_t = 'customer'";
         $rcustomer = $db->query($sql);
         $customers = array();
         $prioritized_customer = array();
         if($db->getRowsNum($rcustomer)>0) {
            while(list($assessor_idx)=$db->fetchRow($rcustomer)) {
               
               $sql = "SELECT job_id FROM ".XOCP_PREFIX."employee_job WHERE employee_id = '$assessor_idx'";
               $rcc = $db->query($sql);
               list($assessor_job_id)=$db->fetchRow($rcc);
               
               $sql = "SELECT priority_no FROM ".XOCP_PREFIX."customer_matrix WHERE provider_job_id = '$employee_job_id' AND customer_job_id = '$assessor_job_id'";
               $rcc = $db->query($sql);
               if($db->getRowsNum($rcc)>0) {
                  list($priority_no)=$db->fetchRow($rcc);
               } else {
                  $priority_no = 50;
               }
         
               $sql = "SELECT assessor_t FROM ".XOCP_PREFIX."assessor_360"
                    . " WHERE asid = '$asid' AND employee_id = '$employee_id' AND assessor_id = '$assessor_idx' AND status_cd = 'active'";
               $rac = $db->query($sql);
               if($db->getRowsNum($rac)>0) continue;
               
               
               $customers[] = $assessor_idx;
               $prioritized_customer[] = array($priority_no,$assessor_idx,$employee_job_id,$assessor_job_id);
            }
         }
         $active = array();
         $inactive = array();
         sort($prioritized_customer);
         if($employee_id==391) _dumpvar($prioritized_customer);
         foreach($prioritized_customer as $v) {
            list($p,$assessor_id)=$v;
            $sql = "SELECT 'ACTIVATE CUSTOMER: $assessor_id',assessor_id,assessor_t,status_cd FROM ".XOCP_PREFIX."assessor_360"
                 . " WHERE asid = '$asid' AND assessor_id = '$assessor_id' GROUP BY status_cd";
            $ras = $db->query($sql);
            if($db->getRowsNum($ras)>0) {
               while(list($unu,$assessor_idx,$assessor_tx,$status_cdx)=$db->fetchRow($ras)) {
                  
                  /// limit assessi
                  $sql = "SELECT COUNT(*) FROM ".XOCP_PREFIX."assessor_360 WHERE asid = '$asid' AND assessor_id = '$assessor_idx' AND assessor_t = 'customer' AND status_cd = 'active'";
                  $rc = $db->query($sql);
                  list($cnt)=$db->fetchRow($rc);
                  ///
                  
                  if($status_cdx=="active") {
                     if($cnt<$assessi_max_customer) {
                        $active[$assessor_idx] = $assessor_idx;
                        unset($inactive[$assessor_idx]);
                     } else {
                        unset($active[$assessor_idx]);
                        unset($inactive[$assessor_idx]);
                     }
                  } else {
                     if($cnt<$assessi_max_customer&&!isset($active[$assessor_idx])) {
                        $inactive[$assessor_idx] = $assessor_idx;
                     }
                  }
               }
            }
         }
         
         $count = count($active)+count($inactive);
         
         $limit = $this->calc_limit($count,"customer");
         if(count($active)<$limit) {
            $n = $limit - count($active);
            
            //shuffle($inactive); //// don't shuffle because already prioritized
            
            for($i=0;$i<$n;$i++) {
               $ck_inactive_assessor_id = array_shift($inactive);
               ///// trap for unwanted assessor ...... 2011-01-03
               $sql = "SELECT b.job_class_id FROM ".XOCP_PREFIX."employee_job a LEFT JOIN ".XOCP_PREFIX."jobs b USING(job_id) WHERE a.employee_id = '$ck_inactive_assessor_id'";
               $rtrap = $db->query($sql);
               if($db->getRowsNum($rtrap)>0) {
                  list($assessor_job_class_id)=$db->fetchRow($rtrap);
                  if(isset($_SESSION["inactive_assessor"][$assessor_job_class_id])) {
                     $i--;
                     continue;
                  }
               }
               $active[] = $ck_inactive_assessor_id;
               ///////////////////////////////////////
            }
         } else {
            $n = count($active);
            shuffle($active);
            for($i=0;$i<$n;$i++) {
               if($i>($limit-1)) {
                  $inactive[] = array_shift($active);
               }
            }
         }
         
         foreach($active as $assessor_id) {
            $sql = "UPDATE ".XOCP_PREFIX."assessor_360 SET status_cd = 'active'"
                 . " WHERE asid = '$asid'"
                 . " AND employee_id = '$employee_id'"
                 . " AND assessor_id = '$assessor_id'"
                 . " AND assessor_t = 'customer'";
            $db->query($sql);
         }
      }            
   }
   
   
   //////////////////////////////////////////////////////////////////////////////////////
   
   function activate_subordinate($asid) {
      $db=&Database::getInstance();
      $sql = "SELECT job_id,job_nm FROM ".XOCP_PREFIX."jobs WHERE upper_job_id = '0'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($job_id,$job_nm)=$db->fetchRow($result);
         $_SESSION["job_emp"] = array();
         $arr["$job_id|$job_nm"] = $this->recurse_activate_subordinate($asid,$job_id);
      }
   }
   
   function recurse_activate_subordinate($asid,$job_id) {
      $db=&Database::getInstance();
      global $assessi_max_superior,$assessi_max_peer,$assessi_max_customer;
      
      $sub_job = array();
      
      /// select sub job
      $sql = "SELECT job_id,job_nm FROM ".XOCP_PREFIX."jobs WHERE upper_job_id = '$job_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($sub_job_id,$sub_job_nm)=$db->fetchRow($result)) {
            $this->recurse_activate_subordinate($asid,$sub_job_id);
            $candidate = array();
            $sql = "SELECT employee_id FROM ".XOCP_PREFIX."employee_job WHERE job_id = '$sub_job_id'";
            $remp = $db->query($sql);
            if($db->getRowsNum($remp)>0) {
               while(list($employee_id)=$db->fetchRow($remp)) {
                  
                  $sub_emp = $this->recurse_get_subordinate($asid,$sub_job_id);
                  $active = array();
                  $inactive = array();
                  foreach($sub_emp as $assessor_id) {
                     
                     
                     $sql = "SELECT 'ACTIVATE SUBORDINATE: $assessor_id',assessor_id,assessor_t,status_cd FROM ".XOCP_PREFIX."assessor_360"
                          . " WHERE asid = '$asid' AND assessor_id = '$assessor_id' GROUP BY status_cd";
                     $ras = $db->query($sql);
                     if($db->getRowsNum($ras)>0) {
                        while(list($unu,$assessor_idx,$assessor_tx,$status_cdx)=$db->fetchRow($ras)) {
                           
                           /// limit assessi
                           $sql = "SELECT COUNT(*) FROM ".XOCP_PREFIX."assessor_360 WHERE asid = '$asid' AND assessor_id = '$assessor_idx' AND assessor_t = 'subordinat' AND status_cd = 'active'";
                           $rc = $db->query($sql);
                           list($cnt)=$db->fetchRow($rc);
                           ///
                           
                           if($status_cdx=="active") {
                              if($cnt<$assessi_max_superior) {
                                 $active[$assessor_idx] = $assessor_idx;
                                 unset($inactive[$assessor_idx]);
                              } else {
                                 unset($active[$assessor_idx]);
                                 unset($inactive[$assessor_idx]);
                              }
                           } else {
                              if($cnt<$assessi_max_superior&&!isset($active[$assessor_idx])) {
                                 $inactive[$assessor_idx] = $assessor_idx;
                              }
                           }
                        }
                     }
                  }
                  
                  $count = count($active)+count($inactive);
                  
                  $limit = $this->calc_limit($count,"subordinate");
                  $new_active = array();
                  if(count($active)<$limit) {
                     $n = $limit - count($active);
                     shuffle($inactive);
                     for($i=0;$i<$n;$i++) {
                        $ck_inactive_assessor_id = array_shift($inactive);
                        ///// trap for unwanted assessor ...... 2011-01-03
                        $sql = "SELECT b.job_class_id FROM ".XOCP_PREFIX."employee_job a LEFT JOIN ".XOCP_PREFIX."jobs b USING(job_id) WHERE a.employee_id = '$ck_inactive_assessor_id'";
                        $rtrap = $db->query($sql);
                        if($db->getRowsNum($rtrap)>0) {
                           list($assessor_job_class_id)=$db->fetchRow($rtrap);
                           if(isset($_SESSION["inactive_assessor"][$assessor_job_class_id])) {
                              $i--;
                              continue;
                           }
                        }
                        $active[] = $ck_inactive_assessor_id;
                        ///////////////////////////////////////
                     }
                  } else {
                     $n = count($active);
                     shuffle($active);
                     for($i=0;$i<$n;$i++) {
                        if($i>=$limit) {
                           $inactive[] = array_shift($active);
                        }
                     }
                  }
                  
                  foreach($active as $assessor_id) {
                     $sql = "UPDATE ".XOCP_PREFIX."assessor_360 SET status_cd = 'active'"
                          . " WHERE asid = '$asid'"
                          . " AND employee_id = '$employee_id'"
                          . " AND assessor_id = '$assessor_id'"
                          . " AND assessor_t = 'subordinat'";
                     $db->query($sql);
                  }
               }
            }
         }
      }
   }
   
   function recurse_get_subordinate($asid,$job_id) {
      $db=&Database::getInstance();
      
      $ttl_sub_emp = array();
      $ttl_emp = array();
      $sql = "SELECT job_id,job_nm FROM ".XOCP_PREFIX."jobs WHERE upper_job_id = '$job_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($sub_job_id,$sub_job_nm)=$db->fetchRow($result)) {
            $sub_emp = $this->recurse_get_subordinate($asid,$sub_job_id);
            $sql = "SELECT a.employee_id FROM ".XOCP_PREFIX."employee_job a LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id) WHERE a.job_id = '$sub_job_id' AND b.status_cd = 'normal'";
            $remp = $db->query($sql);
            $emp = array();
            if($db->getRowsNum($remp)>0) {
               while(list($employee_id)=$db->fetchRow($remp)) {
                  $emp[] = $employee_id;
               }
            }
            $ttl_emp = array_merge($emp,$ttl_emp);
            $ttl_sub_emp = array_merge($sub_emp,$ttl_sub_emp);
         }
         return array_merge($ttl_emp,$ttl_sub_emp);
      } else {
         return $ttl_emp;
      }
   }
   
   function app_generate360($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      
      global $assessi_max_superior,$assessi_max_peer,$assessi_max_customer;
      $asid = $args[0];
      _activitylog("ASSESSMENT_SETUP",0,"Setup session, start generate assessor 360 degree for asid = $asid.");
      ss_timing_start("generate360");
      
      
      //// untuk menyimpan job employee pada waktu sebelum assessment
      $sql = "SELECT employee_id,job_id FROM ".XOCP_PREFIX."employee_job"
           . " WHERE stop_dttm = '0000-00-00 00:00:00'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($employee_id,$job_id)=$db->fetchRow($result)) {
            $sql = "REPLACE INTO ".XOCP_PREFIX."assessment_session_job (asid,employee_id,job_id,updated_user_id)"
                 . " VALUES ('$asid','$employee_id','$job_id','$user_id')";
            $db->query($sql);
         }
      }
      
      /////////////////////////////////////////////
      
      $sql = "SELECT assessi_max_superior, assessi_max_peer, assessi_max_customer FROM ".XOCP_PREFIX."assessment_session WHERE asid = '$asid'";
      $result = $db->query($sql);
      list($assessi_max_superior,$assessi_max_peer,$assessi_max_customer)=$db->fetchRow($result);
      $sql = "SELECT min_peer,max_peer,min_subordinat,max_subordinat,min_customer,max_customer"
           . " FROM ".XOCP_PREFIX."assessment_session"
           . " WHERE asid = '$asid'";
      $result = $db->query($sql);
      list($min_peer,$max_peer,$min_subordinat,$max_subordinat,$min_customer,$max_customer)=$db->fetchRow($result);
      $_SESSION["hris_assessor_limit"] = array($min_peer,$max_peer,$min_subordinat,$max_subordinat,$min_customer,$max_customer);
      
      $sql = "DELETE FROM ".XOCP_PREFIX."assessor_360 WHERE asid = '$asid'";
      $db->query($sql);
      $sql = "DELETE FROM ".XOCP_PREFIX."assessor_tmp WHERE asid = '$asid'";
      $db->query($sql);
      $sql = "SELECT employee_id FROM ".XOCP_PREFIX."employee WHERE status_cd = 'normal'";
      $result = $db->query($sql);
      $arr = array();
      if($db->getRowsNum($result)>0) {
         while(list($employee_id)=$db->fetchRow($result)) {
            $arr[$employee_id] = $employee_id;
            $this->find_superior($asid,$employee_id);
            $this->find_subordinate($asid,$employee_id);
            $this->find_peer($asid,$employee_id);
            $this->find_customer($asid,$employee_id);
         }
      }
      
      $this->activate_subordinate($asid);
      $this->activate_peer($asid);
      $this->activate_customer($asid);
      
      
      $this->balance_assessor($asid,"peer");
      $this->balance_assessor($asid,"customer");
      
      /////////////////////////////////////////////
      
      ss_timing_stop("generate360");
      
      $ret = ss_timing_current("generate360");
      
      $sql = "DELETE FROM ".XOCP_PREFIX."assessment_session_job_competency WHERE asid = '$asid'";
      $db->query($sql);
      $sql = "INSERT INTO ".XOCP_PREFIX."assessment_session_job_competency SELECT '$asid',a.* FROM ".XOCP_PREFIX."job_competency a";
      $db->query($sql);
      
      _activitylog("ASSESSMENT_SETUP",0,"Setup session, finish generate assessor 360 degree for asid = '$asid' in $ret.");
      
      return $ret;
   }
   
   function app_findAssessor($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $asid = $args[0];
      $employee_id = $args[1];
      $sql = "DELETE FROM ".XOCP_PREFIX."assessor_360 WHERE asid = '$asid' AND employee_id = '$employee_id'";
      //$db->query($sql);
      $this->find_superior($asid,$employee_id);
      $this->find_subordinate($asid,$employee_id);
      $this->find_peer($asid,$employee_id);
      $this->find_customer($asid,$employee_id);
      $pass = substr(md5(uniqid(rand())), 2, 5);
      $sql = "INSERT INTO ".XOCP_PREFIX."assessor_pass (asid,employee_id,pwd0,pwd1,generate_user_id)"
           . " VALUES ('$asid','$employee_id',md5('$pass'),'$pass','$user_id')";
      $db->query($sql);
   }
   
   function find_superior($asid,$employee_id) { /// find superior assessor: employee_id is assessi
      $db=&Database::getInstance();
      $user_id = getUserID();
      
      
      $sql = "SELECT a.assessor_job_id,a.assessor_employee_id,b.job_class_id FROM ".XOCP_PREFIX."employee_job a LEFT JOIN ".XOCP_PREFIX."jobs b ON b.job_id=a.assessor_job_id WHERE a.employee_id = '$employee_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($assessor_job_id,$assessor_id,$job_class_id)=$db->fetchRow($result);
         if($assessor_id==0) {
            $sql = "SELECT job_id FROM ".XOCP_PREFIX."employee_job"
                 . " WHERE employee_id = '$employee_id'"
                 . " ORDER BY start_dttm DESC";
            $result = $db->query($sql);
            if($db->getRowsNum($result)>0) {
               list($job_id)=$db->fetchRow($result);
               $sql = "SELECT assessor_job_id FROM ".XOCP_PREFIX."jobs WHERE job_id = '$job_id'";
               $result = $db->query($sql);
               if($db->getRowsNum($result)>0) {
                  list($assessor_job_id)=$db->fetchRow($result);
                  $sql = "SELECT a.employee_id,b.status_cd,c.job_class_id FROM ".XOCP_PREFIX."employee_job a"
                       . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
                       . " LEFT JOIN ".XOCP_PREFIX."jobs c ON c.job_id = a.job_id"
                       . " WHERE a.job_id = '$assessor_job_id'"
                       . " AND b.status_cd = 'normal'";
                  $result = $db->query($sql);
                  if($db->getRowsNum($result)>0) {
                     list($assessor_id,$status_cd,$job_class_idx)=$db->fetchRow($result);
                     if($status_cd=="nullified") continue;
                     if(isset($_SESSION["inactive_assessor"][$job_class_idx])) {
                        $assessor_status = "inactive";
                     } else {
                        $assessor_status = "active";
                     }
                     $sql = "INSERT INTO ".XOCP_PREFIX."assessor_360 (asid,employee_id,assessor_id,assessor_t,status_cd,generate_user_id)"
                          . " VALUES ('$asid','$employee_id','$assessor_id','superior','$assessor_status','$user_id')";
                     $db->query($sql);
                     
                     $sql = "INSERT INTO ".XOCP_PREFIX."assessor_tmp (asid,employee_id,assessor_id,assessor_t)"
                          . " VALUES ('$asid','$employee_id','$assessor_id','superior')";
                     $db->query($sql);
                     $sql = "UPDATE ".XOCP_PREFIX."assessor_360 SET assessor_t = 'superior' WHERE asid = '$asid' AND employee_id = '$employee_id' AND assessor_id = '$assessor_id'";
                     $db->query($sql);
                  }
               }
            }
         } else {
            if(isset($_SESSION["inactive_assessor"][$job_class_id])) {
               $assessor_status = "inactive";
            } else {
               $assessor_status = "active";
            }
            $sql = "INSERT INTO ".XOCP_PREFIX."assessor_360 (asid,employee_id,assessor_id,assessor_t,status_cd,generate_user_id)"
                 . " VALUES ('$asid','$employee_id','$assessor_id','superior','$assessor_status','$user_id')";
            $db->query($sql);
            $sql = "INSERT INTO ".XOCP_PREFIX."assessor_tmp (asid,employee_id,assessor_id,assessor_t)"
                 . " VALUES ('$asid','$employee_id','$assessor_id','superior')";
            $db->query($sql);
            $sql = "UPDATE ".XOCP_PREFIX."assessor_360 SET assessor_t = 'superior' WHERE asid = '$asid' AND employee_id = '$employee_id' AND assessor_id = '$assessor_id'";
            $db->query($sql);
         }
      }
      
      
   }
   
   
   function find_subordinate($asid,$employee_id) { /// $employee_id is assessi
      $db=&Database::getInstance();
      $user_id = getUserID();
      $sql = "SELECT a.job_id,c.assessment_by_subordinate,emp.status_cd FROM ".XOCP_PREFIX."employee_job a"
           . " LEFT JOIN ".XOCP_PREFIX."jobs b USING(job_id)"
           . " LEFT JOIN ".XOCP_PREFIX."job_class c USING(job_class_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee emp ON emp.employee_id = a.employee_id"
           . " WHERE a.employee_id = '$employee_id'"
           . " AND c.assessment_by_subordinate = '1'"
           . " AND emp.status_cd = 'normal'"
           . " ORDER BY a.start_dttm DESC";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($job_id,$assessment_by_subordinate,$status_cd)=$db->fetchRow($result);
         if($status_cd=="nullified") continue;
         $emp = $this->recurse_get_subordinate($asid,$job_id);
         foreach($emp as $assessor_id) {
            $sql = "INSERT INTO ".XOCP_PREFIX."assessor_360 (asid,employee_id,assessor_id,assessor_t,status_cd,generate_user_id)"
                 . " VALUES ('$asid','$employee_id','$assessor_id','subordinat','inactive','$user_id')";
            $db->query($sql);
            $sql = "INSERT INTO ".XOCP_PREFIX."assessor_tmp (asid,employee_id,assessor_id,assessor_t)"
                 . " VALUES ('$asid','$employee_id','$assessor_id','subordinat')";
            $db->query($sql);
            $sql = "UPDATE ".XOCP_PREFIX."assessor_360 SET assessor_t = 'subordinat' WHERE asid = '$asid' AND employee_id = '$employee_id' AND assessor_id = '$assessor_id'";
            $db->query($sql);
         }
      }
   }
   
   function find_peer($asid,$employee_id) { //// employee_id is assessi
      $db=&Database::getInstance();
      $user_id = getUserID();
      $peers = $peer1 = $peer2 = array();
      $sql = "SELECT a.job_id,b.job_class_id,b.org_id,c.job_class_level,e.person_nm,b.peer_group_id,d.status_cd"
           . " FROM ".XOCP_PREFIX."employee_job a"
           . " LEFT JOIN ".XOCP_PREFIX."jobs b USING(job_id)"
           . " LEFT JOIN ".XOCP_PREFIX."job_class c USING(job_class_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee d ON d.employee_id = a.employee_id"
           . " LEFT JOIN ".XOCP_PREFIX."persons e ON e.person_id = d.person_id"
           . " WHERE a.employee_id = '$employee_id' AND d.status_cd = 'normal'"
           . " AND c.assessment_by_peer = '1'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($job_id,$job_class_id,$org_id,$job_class_level,$emp_nm,$peer_group_id)=$db->fetchRow($result)) {
            /// same job
            $sql = "SELECT a.employee_id,c.person_nm"
                 . " FROM ".XOCP_PREFIX."employee_job a"
                 . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
                 . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
                 . " WHERE a.job_id = '$job_id'"
                 . " AND b.status_cd = 'normal'"
                 . " ORDER BY a.employee_id";
            $res1 = $db->query($sql);
            if($db->getRowsNum($res1)>0) {
               while(list($employee_idx,$asse_nm)=$db->fetchRow($res1)) {
                  if($employee_idx==$employee_id) continue;
                  $peers[] = array($employee_idx,$asse_nm);
               }
            }
            
            /// same org_id
            $sql = "SELECT peer_job_id1 FROM ".XOCP_PREFIX."peer_matrix WHERE peer_job_id0 = '$job_id'";
            $rjob = $db->query($sql);
            if($db->getRowsNum($rjob)>0) {
               while(list($peer_job_id)=$db->fetchRow($rjob)) {
                  $sql = "SELECT a.employee_id,c.person_nm"
                       . " FROM ".XOCP_PREFIX."employee_job a"
                       . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
                       . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
                       . " WHERE a.job_id = '$peer_job_id'"
                       . " AND b.status_cd = 'normal'"
                       . " ORDER BY a.employee_id";
                  $res1 = $db->query($sql);
                  if($db->getRowsNum($res1)>0) {
                     while(list($employee_idx,$asse_nm)=$db->fetchRow($res1)) {
                        if($employee_idx==$employee_id) continue;
                        $peers[] = array($employee_idx,$asse_nm);
                     }
                  }
                  
               }
            }
            
         }
         
         if($employee_id==166) {
            _dumpvar($peers);
         }
         
         foreach($peers as $v) {
            list($assessor_idx,$assessor_nm)=$v;
            $sql = "INSERT INTO ".XOCP_PREFIX."assessor_360 (asid,employee_id,assessor_id,assessor_t,status_cd,generate_user_id) VALUES ('$asid','$employee_id','$assessor_idx','peer','inactive','$user_id')";
            $db->query($sql);
         }
      }
   }
   
   function find_peer_old_20111214($asid,$employee_id) { //// employee_id is assessi
      $db=&Database::getInstance();
      $user_id = getUserID();
      $peers = $peer1 = $peer2 = array();
      $sql = "SELECT a.job_id,b.job_class_id,b.org_id,c.job_class_level,e.person_nm,b.peer_group_id,d.status_cd"
           . " FROM ".XOCP_PREFIX."employee_job a"
           . " LEFT JOIN ".XOCP_PREFIX."jobs b USING(job_id)"
           . " LEFT JOIN ".XOCP_PREFIX."job_class c USING(job_class_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee d ON d.employee_id = a.employee_id"
           . " LEFT JOIN ".XOCP_PREFIX."persons e ON e.person_id = d.person_id"
           . " WHERE a.employee_id = '$employee_id' AND d.status_cd = 'normal'"
           . " AND c.assessment_by_peer = '1'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($job_id,$job_class_id,$org_id,$job_class_level,$emp_nm,$peer_group_id)=$db->fetchRow($result)) {
            /// same org_id
            if($peer_group_id>0) {
               $sql = "SELECT a.job_id"
                    . " FROM ".XOCP_PREFIX."jobs a"
                    . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
                    . " WHERE a.peer_group_id = '$peer_group_id'"
                    . " AND a.org_id = '$org_id'"
                    //. " AND b.assessment_by_peer = '1'"
                    . " ORDER BY a.job_id";
               $res0 = $db->query($sql);
               if($db->getRowsNum($res0)>0) {
                  while(list($job_idx)=$db->fetchRow($res0)) {
                     $sql = "SELECT a.employee_id,c.person_nm"
                          . " FROM ".XOCP_PREFIX."employee_job a"
                          . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
                          . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
                          . " WHERE a.job_id = '$job_idx'"
                          . " AND b.status_cd = 'normal'"
                          . " ORDER BY a.employee_id";
                     $res1 = $db->query($sql);
                     if($db->getRowsNum($res1)>0) {
                        while(list($employee_idx,$asse_nm)=$db->fetchRow($res1)) {
                           if($employee_idx==$employee_id) continue;
                           $peer1[] = array($employee_idx,$asse_nm);
                        }
                     }
                  }
               }
               shuffle($peer1);
            
               //// sibling org
               $sibling_org = $this->getSibling($org_id,$job_class_id);
               foreach($sibling_org as $org_idx=>$v) {
                  if($org_idx==$org_id) continue; //// the same org is already generated above
                  $sql = "SELECT a.job_id"
                       . " FROM ".XOCP_PREFIX."jobs a"
                       . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
                       . " WHERE a.peer_group_id = '$peer_group_id'"
                       . " AND a.org_id = '$org_idx'"
                       //. " AND b.assessment_by_peer = '1'"
                       . " ORDER BY a.job_id";
                  $res0 = $db->query($sql);
                  if($db->getRowsNum($res0)>0) {
                     while(list($job_idx)=$db->fetchRow($res0)) {
                        $sql = "SELECT a.employee_id,c.person_nm"
                             . " FROM ".XOCP_PREFIX."employee_job a"
                             . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
                             . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
                             . " WHERE a.job_id = '$job_idx'"
                             . " AND b.status_cd = 'normal'"
                             . " ORDER BY a.employee_id";
                        $res1 = $db->query($sql);
                        if($db->getRowsNum($res1)>0) {
                           while(list($employee_idx,$asse_nm)=$db->fetchRow($res1)) {
                              if($employee_idx==$employee_id) continue;
                              $peer2[] = array($employee_idx,$asse_nm);
                           }
                        }
                     }
                  }
               }
            }
            $peers = array_merge($peer1,$peer2);
         }
         
         foreach($peers as $v) {
            list($assessor_idx,$assessor_nm)=$v;
            $sql = "INSERT INTO ".XOCP_PREFIX."assessor_360 (asid,employee_id,assessor_id,assessor_t,status_cd,generate_user_id) VALUES ('$asid','$employee_id','$assessor_idx','peer','inactive','$user_id')";
            $db->query($sql);
         }
         
      }
   }
   
   
   function find_customer($asid,$employee_id) { //// employee_id is assessi
      $db=&Database::getInstance();
      $user_id = getUserID();
      $_SESSION["hris_org_customer"] = array();
      $sql = "SELECT a.job_id,b.job_class_id,b.org_id,c.job_class_level,e.person_nm,b.job_nm,c.job_class_nm"
           . " FROM ".XOCP_PREFIX."employee_job a"
           . " LEFT JOIN ".XOCP_PREFIX."jobs b USING(job_id)"
           . " LEFT JOIN ".XOCP_PREFIX."job_class c USING(job_class_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee d ON d.employee_id = a.employee_id"
           . " LEFT JOIN ".XOCP_PREFIX."persons e ON e.person_id = d.person_id"
           . " WHERE a.employee_id = '$employee_id'"
           . " AND d.status_cd = 'normal'"
           . " AND c.assessment_by_customer = '1'";
           //. " AND a.stop_dttm > now()";
      $result = $db->query($sql);
      $filled = 0;
      if($db->getRowsNum($result)>0) {
         while(list($job_id,$job_class_id,$org_id,$job_class_level,$emp_nm,$job_nm,$job_class_nm)=$db->fetchRow($result)) {
            
            $sql = "SELECT customer_job_id FROM ".XOCP_PREFIX."customer_matrix WHERE provider_job_id = '$job_id'";
            $rjob = $db->query($sql);
            if($db->getRowsNum($rjob)>0) {
               while(list($customer_job_id)=$db->fetchRow($rjob)) {
                  $sql = "SELECT a.employee_id,c.person_nm"
                       . " FROM ".XOCP_PREFIX."employee_job a"
                       . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
                       . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
                       . " WHERE a.job_id = '$customer_job_id'"
                       . " AND b.status_cd = 'normal'"
                       . " ORDER BY a.employee_id";
                  $res1 = $db->query($sql);
                  if($db->getRowsNum($res1)>0) {
                     while(list($employee_idx,$asse_nm)=$db->fetchRow($res1)) {
                        if($employee_idx==$employee_id) continue;
                        $customer[] = array($employee_idx,$asse_nm);
                     }
                  }
               }
            }
            
            shuffle($customer);
            foreach($customer as $v) {
               list($assessor_idx,$assessor_nm)=$v;
               $sql = "INSERT INTO ".XOCP_PREFIX."assessor_360 (asid,employee_id,assessor_id,assessor_t,status_cd,generate_user_id) VALUES ('$asid','$employee_id','$assessor_idx','customer','inactive','$user_id')";
               if($assessor_idx==184) {
                  _debuglog($sql);
                  _debuglog(mysql_error());
               }
               $db->query($sql);
            }
         }
      }
   }
   
   function find_customer_old_20111214($asid,$employee_id) { //// employee_id is assessi
      $db=&Database::getInstance();
      $user_id = getUserID();
      $_SESSION["hris_org_customer"] = array();
      $sql = "SELECT a.job_id,b.job_class_id,b.org_id,c.job_class_level,e.person_nm,b.job_nm,c.job_class_nm"
           . " FROM ".XOCP_PREFIX."employee_job a"
           . " LEFT JOIN ".XOCP_PREFIX."jobs b USING(job_id)"
           . " LEFT JOIN ".XOCP_PREFIX."job_class c USING(job_class_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee d ON d.employee_id = a.employee_id"
           . " LEFT JOIN ".XOCP_PREFIX."persons e ON e.person_id = d.person_id"
           . " WHERE a.employee_id = '$employee_id'"
           . " AND d.status_cd = 'normal'"
           . " AND c.assessment_by_customer = '1'";
           //. " AND a.stop_dttm > now()";
      $result = $db->query($sql);
      $filled = 0;
      if($db->getRowsNum($result)>0) {
         while(list($job_id,$job_class_id,$org_id,$job_class_level,$emp_nm,$job_nm,$job_class_nm)=$db->fetchRow($result)) {
            $sql = "SELECT org_id1 FROM ".XOCP_PREFIX."org_rel WHERE org_id0 = '$org_id' AND rel_type = 'customer'";
            $rcust = $db->query($sql);
            while(list($org_idx)=$db->fetchRow($rcust)) {
               $_SESSION["hris_org_customer"][] = $org_idx;
            }
            
            /// iterasi seluruh organisasi customer ...
            foreach($_SESSION["hris_org_customer"] as $k=>$org_idx) {
               $customer = array();
               $customer_curlevel = $customer_prevlevel = $customer_nextlevel = array();
               $prevlevel_job_id = 0;
               $curlevel_job_id = 0;
               $nextlevel_job_id = 0;
               $sql = "SELECT a.job_id,a.job_nm,b.job_class_nm,b.job_class_level"
                    . " FROM ".XOCP_PREFIX."jobs a"
                    . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
                    . " WHERE a.org_id = '$org_idx'"
                    . " ORDER BY b.job_class_level ASC";
               $res0 = $db->query($sql);
               if($db->getRowsNum($res0)>0) {
                  while(list($job_idx,$job_nmx,$job_class_nmx,$job_class_levelx)=$db->fetchRow($res0)) {
                     if($job_class_levelx<$job_class_level) {
                        $prevlevel_job_id = $job_idx;
                     }
                     if($job_class_levelx==$job_class_level) {
                        $curlevel_job_id = $job_idx;
                     }
                     if($job_class_levelx>$job_class_level) {
                        $nextlevel_job_id = $job_idx;
                        break;
                     }
                  }
               }
            
               if($curlevel_job_id>0) {
                  $sql = "SELECT a.employee_id,c.person_nm"
                       . " FROM ".XOCP_PREFIX."employee_job a"
                       . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
                       . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
                       . " WHERE a.job_id = '$curlevel_job_id'"
                       . " AND b.status_cd = 'normal'"
                       . " ORDER BY a.employee_id";
                  $res1 = $db->query($sql);
                  if($db->getRowsNum($res1)>0) {
                     while(list($employee_idx,$asse_nm)=$db->fetchRow($res1)) {
                        if($employee_idx==$employee_id) continue;
                        $customer[] = array($employee_idx,$asse_nm);
                        $customer_curlevel[] = array($employee_idx,$asse_nm);
                     }
                  }
               }

               if($prevlevel_job_id>0) {
                  $sql = "SELECT a.employee_id,c.person_nm"
                       . " FROM ".XOCP_PREFIX."employee_job a"
                       . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
                       . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
                       . " WHERE a.job_id = '$prevlevel_job_id'"
                       . " AND b.status_cd = 'normal'"
                       . " ORDER BY a.employee_id";
                  $res1 = $db->query($sql);
                  if($db->getRowsNum($res1)>0) {
                     while(list($employee_idx,$asse_nm)=$db->fetchRow($res1)) {
                        if($employee_idx==$employee_id) continue;
                        $customer[] = array($employee_idx,$asse_nm);
                        $customer_prevlevel[] = array($employee_idx,$asse_nm);
                     }
                  }
               }
               
               if($nextlevel_job_id>0) {
                  $sql = "SELECT a.employee_id,c.person_nm"
                       . " FROM ".XOCP_PREFIX."employee_job a"
                       . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
                       . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
                       . " WHERE a.job_id = '$nextlevel_job_id'"
                       . " AND b.status_cd = 'normal'"
                       . " ORDER BY a.employee_id";
                  $res1 = $db->query($sql);
                  if($db->getRowsNum($res1)>0) {
                     while(list($employee_idx,$asse_nm)=$db->fetchRow($res1)) {
                        if($employee_idx==$employee_id) continue;
                        $customer[] = array($employee_idx,$asse_nm);
                        $customer_nextlevel[] = array($employee_idx,$asse_nm);
                     }
                  }
               }
               
               //$customer = array_merge($customer_curlevel,$customer_nextlevel,$customer_prevlevel);
               
               //shuffle($customer);
               foreach($customer as $v) {
                  list($employee_idx,$assessor_nm)=$v;
                  $sql = "INSERT INTO ".XOCP_PREFIX."assessor_360 (asid,employee_id,assessor_id,assessor_t,status_cd,generate_user_id) VALUES ('$asid','$employee_id','$employee_idx','customer','inactive','$user_id')";
                  $db->query($sql);
               }
            }
         }
      }
   }
   
   
   function calc_limit($count,$type) {
      list($min_peer,$max_peer,$min_subordinat,$max_subordinat,$min_customer,$max_customer)=$_SESSION["hris_assessor_limit"];
      
      switch($type) {
         case "peer":
            $max_x = $max_peer;
            $min_x = $min_peer;
            break;
         case "customer":
            $max_x = $max_customer;
            $min_x = $min_customer;
            break;
         case "subordinate":
            $max_x = $max_subordinat;
            $min_x = $min_subordinat;
            break;
         default:
            $max_x = $max_subordinat;
            $min_x = $min_subordinat;
            break;
      }
      $min = $min_x;
      $pos = strpos($max_x,"%");
      if(!$pos) {
         $max = $max_x;
      } else {
         $num = _bctrim(bcadd(0,substr($max_x,0,$pos)));
         $max = ceil(($num/100)*$count);
      }
      
      $pos = strpos($min_x,"%");
      
      if(!$pos) {
         $min = $min_x;
      } else {
         $num = _bctrim(bcadd(0,substr($min_x,0,$pos)));
         $min = ceil(($num/100)*$count);
      }
      
      if($min>$max) {
         $limit = $min;
      } else {
         $limit = $max;
      }
      
      return $limit;
   }
   
   
   
   
   /////////////////////////////////////////////////////// BELOW IS OLD //////////////////////////////////////////////////
   
   /*
   //// old version
   function app_generate360($args) {
      $db=&Database::getInstance();
      $asid = $args[0];
      $sql = "SELECT min_peer,max_peer,min_subordinat,max_subordinat,min_customer,max_customer"
           . " FROM ".XOCP_PREFIX."assessment_session"
           . " WHERE asid = '$asid'";
      $result = $db->query($sql);
      list($min_peer,$max_peer,$min_subordinat,$max_subordinat,$min_customer,$max_customer)=$db->fetchRow($result);
      $_SESSION["hris_assessor_limit"] = array($min_peer,$max_peer,$min_subordinat,$max_subordinat,$min_customer,$max_customer);
      
      $sql = "DELETE FROM ".XOCP_PREFIX."assessor_360 WHERE asid = '$asid'";
      $db->query($sql);
      $sql = "SELECT employee_id FROM ".XOCP_PREFIX."employee WHERE status_cd = 'normal'";
      $result = $db->query($sql);
      $arr = array();
      if($db->getRowsNum($result)>0) {
         while(list($employee_id)=$db->fetchRow($result)) {
            $arr[$employee_id] = $employee_id;
            $this->find_subordinat($asid,$employee_id);
            $this->find_peer($asid,$employee_id);
            $this->find_customer($asid,$employee_id);
         }
      }
      
      //// shuffle subordinate assessor
      $sql = "SELECT employee_id FROM ".XOCP_PREFIX."assessor_360 WHERE asid = '$asid' AND assessor_t = 'subordinat' GROUP BY employee_id";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($employee_idx)=$db->fetchRow($result)) {
            $sql = "SELECT assessor_id FROM ".XOCP_PREFIX."assessor_360 WHERE employee_id = '$employee_idx' AND assessor_t = 'subordinat' AND asid = '$asid'";
            $r0 = $db->query($sql);
            $arr = array();
            while(list($assessor_id)=$db->fetchRow($r0)) {
               $arr[] = $assessor_id;
            }
            shuffle($arr);
            
            $pos = strpos($max_subordinat,"%");
            if(!$pos) {
               $max = $max_subordinat;
            } else {
               $num = _bctrim(bcadd(0,substr($max_subordinat,0,$pos)));
               $max = ceil(($num/100)*count($arr));
            }
            $pos = strpos($min_subordinat,"%");
            if(!$pos) {
               $min = $min_subordinat;
            } else {
               $num = _bctrim(bcadd(0,substr($min_subordinat,0,$pos)));
               $min = ceil(($num/100)*count($arr));
            }
            if($min>$max) {
               $limit = $min;
            } else {
               $limit = $max;
            }
            $no = 1;
            foreach($arr as $assessor_id) {
               if($no>$limit) {
                  $sql = "UPDATE ".XOCP_PREFIX."assessor_360 SET status_cd = 'inactive' WHERE asid = '$asid' AND employee_id = '$employee_idx' AND assessor_id = '$assessor_id' AND assessor_t = 'subordinat'";
                  $db->query($sql);
               }
               $no++;
            }
         }
      }
      return;
   }
   */
   
   function getDivision($org_id) {
      $division_class = 3;
      $db=&Database::getInstance();
      $sql = "SELECT a.parent_id,b.org_class_id,b.org_nm"
           . " FROM ".XOCP_PREFIX."orgs a"
           . " LEFT JOIN ".XOCP_PREFIX."orgs b ON b.org_id = a.parent_id"
           . " WHERE a.org_id = '$org_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($parent_id,$org_class_id,$parent_nm)=$db->fetchRow($result);
         if($parent_nm!="") {
            if($org_class_id <= $division_class) {
               return $parent_id;
            } else {
               return $this->getDivision($parent_id);
            }
         }
      }
      return 0;
   }
   
   function getSibling($org_id,$job_class_id=0) {
      $_SESSION["hris_all_sibling"] = array();
      if($job_class_id==2||$job_class_id==1) {
         $div_org_id = 1;
      } else {
         $div_org_id = $this->getDivision($org_id);
      }
      $this->getAllSibling($div_org_id);
      return $_SESSION["hris_all_sibling"];
   }
   
   function getAllSibling($parent_id) {
      $db=&Database::getInstance();
      $_SESSION["hris_all_sibling"][$parent_id] = 1;
      $sql = "SELECT org_id FROM ".XOCP_PREFIX."orgs WHERE parent_id = '$parent_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($org_idx)=$db->fetchRow($result)) {
            $this->getAllSibling($org_idx);
            $_SESSION["hris_all_sibling"][$org_idx] = 1;
         }
      }
   }
   
   
   function find_peer_old($asid,$employee_id) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      list($min_peer,$max_peer,$min_subordinat,$max_subordinat,$min_customer,$max_customer)=$_SESSION["hris_assessor_limit"];
      $peer1 = $peer2 = array();
      $sql = "SELECT a.job_id,b.job_class_id,b.org_id,c.job_class_level,e.person_nm,b.peer_group_id"
           . " FROM ".XOCP_PREFIX."employee_job a"
           . " LEFT JOIN ".XOCP_PREFIX."jobs b USING(job_id)"
           . " LEFT JOIN ".XOCP_PREFIX."job_class c USING(job_class_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee d ON d.employee_id = a.employee_id"
           . " LEFT JOIN ".XOCP_PREFIX."persons e ON e.person_id = d.person_id"
           . " WHERE a.employee_id = '$employee_id'";
           //. " AND a.stop_dttm > now()";
      $result = $db->query($sql);
      $cursub = array();
      if($db->getRowsNum($result)>0) {
         while(list($job_id,$job_class_id,$org_id,$job_class_level,$emp_nm,$peer_group_id)=$db->fetchRow($result)) {
            $sql = "SELECT job_id FROM ".XOCP_PREFIX."jobs WHERE assessor_job_id = '$job_id'";
            $ras = $db->query($sql);
            if($db->getRowsNum($ras)>0) {
               while(list($subjob_id)=$db->fetchRow($ras)) {
                  $cursub[$subjob_id] = 1;
               }
            }
            if($peer_group_id==0) {
               return;
            }
            /// same org_id
            $sql = "SELECT a.job_id"
                 . " FROM ".XOCP_PREFIX."jobs a"
                 . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
                 . " WHERE a.peer_group_id = '$peer_group_id'"
                 . " AND a.org_id = '$org_id'"
                 . " AND b.assessment_by_peer = '1'"
                 . " ORDER BY a.job_id";
            $res0 = $db->query($sql);
            if($db->getRowsNum($res0)>0) {
               while(list($job_idx)=$db->fetchRow($res0)) {
                  if($cursub[$job_idx]==1) {
                     continue;
                  }
                  $sql = "SELECT a.employee_id,c.person_nm"
                       . " FROM ".XOCP_PREFIX."employee_job a"
                       . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
                       . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
                       . " WHERE a.job_id = '$job_idx'"
                       . " ORDER BY a.employee_id";
                  $res1 = $db->query($sql);
                  if($db->getRowsNum($res1)>0) {
                     while(list($employee_idx,$asse_nm)=$db->fetchRow($res1)) {
                        if($employee_idx==$employee_id) continue;
                        $peer1[] = array($employee_idx,$asse_nm);
                     }
                  }
               }
            }
            shuffle($peer1);
            
            //// sibling org
            $sibling_org = $this->getSibling($org_id);
            foreach($sibling_org as $org_idx=>$v) {
               if($org_idx==$org_id) continue;
               $sql = "SELECT a.job_id"
                    . " FROM ".XOCP_PREFIX."jobs a"
                    . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
                    . " WHERE a.peer_group_id = '$peer_group_id'"
                    . " AND a.org_id = '$org_idx'"
                    . " AND b.assessment_by_peer = '1'"
                    . " ORDER BY a.job_id";
               $res0 = $db->query($sql);
               if($db->getRowsNum($res0)>0) {
                  while(list($job_idx)=$db->fetchRow($res0)) {
                     if($cursub[$job_idx]==1) continue;
                     $sql = "SELECT a.employee_id,c.person_nm"
                          . " FROM ".XOCP_PREFIX."employee_job a"
                          . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
                          . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
                          . " WHERE a.job_id = '$job_idx'"
                          . " ORDER BY a.employee_id";
                     $res1 = $db->query($sql);
                     if($db->getRowsNum($res1)>0) {
                        while(list($employee_idx,$asse_nm)=$db->fetchRow($res1)) {
                           if($employee_idx==$employee_id) continue;
                           $peer2[] = array($employee_idx,$asse_nm);
                        }
                     }
                  }
               }
            }
            
            shuffle($peer2);
            $peers = array_merge($peer1,$peer2);
            
            $this->drop_subordinate($peers);
            
         }
         
         $min = $min_peer;
         $pos = strpos($max_peer,"%");
         if(!$pos) {
            $max = $max_peer;
         } else {
            $num = _bctrim(bcadd(0,substr($max_peer,0,$pos)));
            $max = ceil(($num/100)*count($peers));
         }
         $pos = strpos($min_peer,"%");
         if(!$pos) {
            $min = $min_peer;
         } else {
            $num = _bctrim(bcadd(0,substr($min_peer,0,$pos)));
            $min = ceil(($num/100)*count($peers));
         }
         if($min>$max) {
            $limit = $min;
         } else {
            $limit = $max;
         }
         
         $no = 1;
         foreach($peers as $v) {
            list($assessor_idx,$assessor_nm)=$v;
            if($no>$limit) {
               $status_cd = "inactive";
            } else {
               $status_cd = "active";
            }
            $sql = "INSERT INTO ".XOCP_PREFIX."assessor_360 (asid,employee_id,assessor_id,assessor_t,status_cd,generate_user_id) VALUES ('$asid','$employee_id','$assessor_idx','peer','$status_cd','$user_id')";
            $db->query($sql);
            $no++;
         }
      }
   }
   
   
   
   function getOrgsDown($org_id) {
      $db=&Database::getInstance();
      $sql = "SELECT org_id FROM ".XOCP_PREFIX."orgs WHERE parent_id = '$org_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($sub_org_id)=$db->fetchRow($result);
         $_SESSION["hris_org_sub"][] = $sub_org_id;
         $this->getOrgsDown($sub_org_id);
      }
   }
   
   
   
   function getDirectSuperior($job_id) {
      $db=&Database::getInstance();
      $_SESSION["hris_org_parents"] = array();
      $sql = "SELECT a.org_id,a.job_class_id,a.assessor_job_id,"
           . "b.job_class_level"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
           . " WHERE a.job_id = '$job_id'";
      $result = $db->query($sql);
      list($org_id,$job_class_id,$assessor_job_id,$job_class_level)=$db->fetchRow($result);
      $_SESSION["hris_org_parents"][] = $org_id;
      $this->getOrgsUp($org_id);
      
      $opt_assessor = "";
      if(count($_SESSION["hris_org_parents"])>0) {
         $no = 0;
         foreach($_SESSION["hris_org_parents"] as $org_idx) {
            $sql = "SELECT a.job_id,a.job_cd,a.job_nm,b.job_class_nm,a.job_class_id,b.job_class_level"
                 . " FROM ".XOCP_PREFIX."jobs a"
                 . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
                 . " WHERE a.org_id = '$org_idx'"
                 . " AND b.assessment_by_subordinate = '1'"
                 . " ORDER BY b.job_class_level ASC,a.job_class_id,a.job_nm";
            $result = $db->query($sql);
            if($db->getRowsNum($result)>0) {
               while(list($job_idx,$job_cdx,$job_nmx,$job_class_nmx,$job_class_idx,$job_class_levelx)=$db->fetchRow($result)) {
                  if($job_class_levelx>=$job_class_level) continue;        /// minimum adalah 1 level diatas
                  $sassessor = "";
                  //if($job_class_levelx<_HRIS_MAX_SUPERIOR_LEVEL) continue; //// override by assessment_by_subordinate = 1
                  
                  
                  /// check non empty job
                  $sql = "SELECT employee_id FROM ".XOCP_PREFIX."employee_job WHERE job_id = '$job_idx'";
                  $rem = $db->query($sql);
                  if($db->getRowsNum($rem)==0) { /// empty job
                     continue;
                  } else {
                     list($employee_idx)=$db->fetchRow($rem);
                     return $employee_idx;
                  }
                  $no++;
               }
            }
         }
         return 0;
      }
   }
   
   function getAllSuperior($job_id) {
      $db=&Database::getInstance();
      $_SESSION["hris_org_parents"] = array();
      $_SESSION["hris_all_superior"] = array();
      $sql = "SELECT a.org_id,a.job_class_id,a.assessor_job_id,"
           . "b.job_class_level"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
           . " WHERE a.job_id = '$job_id'";
      $result = $db->query($sql);
      list($org_id,$job_class_id,$assessor_job_id,$job_class_level)=$db->fetchRow($result);
      $_SESSION["hris_org_parents"][] = $org_id;
      $this->getOrgsUp($org_id);
      
      $opt_assessor = "";
      if(count($_SESSION["hris_org_parents"])>0) {
         $no = 0;
         foreach($_SESSION["hris_org_parents"] as $org_idx) {
            $sql = "SELECT a.job_id,a.job_cd,a.job_nm,b.job_class_nm,a.job_class_id,b.job_class_level"
                 . " FROM ".XOCP_PREFIX."jobs a"
                 . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
                 . " WHERE a.org_id = '$org_idx'"
                 . " AND b.assessment_by_subordinate = '1'"
                 . " ORDER BY b.job_class_level ASC,a.job_class_id,a.job_nm";
            $result = $db->query($sql);
            if($db->getRowsNum($result)>0) {
               while(list($job_idx,$job_cdx,$job_nmx,$job_class_nmx,$job_class_idx,$job_class_levelx)=$db->fetchRow($result)) {
                  if($job_class_levelx>=$job_class_level) continue;        /// minimum adalah 1 level diatas
                  $sassessor = "";
                  //if($job_class_levelx<_HRIS_MAX_SUPERIOR_LEVEL) continue; //// override by assessment_by_subordinate = 1
                  
                  
                  /// check non empty job
                  $sql = "SELECT employee_id FROM ".XOCP_PREFIX."employee_job WHERE job_id = '$job_idx'";
                  $rem = $db->query($sql);
                  if($db->getRowsNum($rem)==0) { /// empty job
                     continue;
                  } else {
                     list($employee_idx)=$db->fetchRow($rem);
                     $_SESSION["hris_all_superior"][] = $employee_idx;
                     // return $employee_idx;
                  }
                  $no++;
               }
            }
         }
         return 0;
      }
   }
   
   
   
   function find_subordinat_old($asid,$employee_id) { /// $employee_id is assessor
      $db=&Database::getInstance();
      $user_id = getUserID();
      list($min_peer,$max_peer,$min_subordinat,$max_subordinat,$min_customer,$max_customer)=$_SESSION["hris_assessor_limit"];
      $subordinat = array();
      $_SESSION["hris_org_sub"] = array();
      $sql = "SELECT a.job_id,b.job_class_id,b.org_id,c.job_class_level,e.person_nm,b.job_nm,c.job_class_nm"
           . " FROM ".XOCP_PREFIX."employee_job a"
           . " LEFT JOIN ".XOCP_PREFIX."jobs b USING(job_id)"
           . " LEFT JOIN ".XOCP_PREFIX."job_class c USING(job_class_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee d ON d.employee_id = a.employee_id"
           . " LEFT JOIN ".XOCP_PREFIX."persons e ON e.person_id = d.person_id"
           . " WHERE a.employee_id = '$employee_id'";
           //. " AND a.stop_dttm > now()";
      $result = $db->query($sql);
      $filled = 0;
      if($db->getRowsNum($result)>0) {
         while(list($job_id,$job_class_id,$org_id,$job_class_level,$emp_nm,$job_nm,$job_class_nm)=$db->fetchRow($result)) {
            $superior_employee_id = $this->getDirectSuperior($job_id);
            $this->getAllSuperior($job_id);
            if(count($_SESSION["hris_all_superior"])>0) {
               foreach($_SESSION["hris_all_superior"] as $superior_employee_id) {
                  $sql = "INSERT INTO ".XOCP_PREFIX."assessor_360 (asid,employee_id,assessor_id,assessor_t,generate_user_id) VALUES ('$asid','$superior_employee_id','$employee_id','subordinat','$user_id')";
                  $db->query($sql);
               }
            }
         }
      }
   }
   
   function find_customer_old($asid,$employee_id) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      list($min_peer,$max_peer,$min_subordinat,$max_subordinat,$min_customer,$max_customer)=$_SESSION["hris_assessor_limit"];
      $_SESSION["hris_org_customer"] = array();
      $sql = "SELECT a.job_id,b.job_class_id,b.org_id,c.job_class_level,e.person_nm,b.job_nm,c.job_class_nm"
           . " FROM ".XOCP_PREFIX."employee_job a"
           . " LEFT JOIN ".XOCP_PREFIX."jobs b USING(job_id)"
           . " LEFT JOIN ".XOCP_PREFIX."job_class c USING(job_class_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee d ON d.employee_id = a.employee_id"
           . " LEFT JOIN ".XOCP_PREFIX."persons e ON e.person_id = d.person_id"
           . " WHERE a.employee_id = '$employee_id'"
           . " AND c.assessment_by_customer = '1'";
           //. " AND a.stop_dttm > now()";
      $result = $db->query($sql);
      $filled = 0;
      if($db->getRowsNum($result)>0) {
         while(list($job_id,$job_class_id,$org_id,$job_class_level,$emp_nm,$job_nm,$job_class_nm)=$db->fetchRow($result)) {
            $sql = "SELECT org_id1 FROM ".XOCP_PREFIX."org_rel WHERE org_id0 = '$org_id' AND rel_type = 'customer'";
            $rcust = $db->query($sql);
            while(list($org_idx)=$db->fetchRow($rcust)) {
               $_SESSION["hris_org_customer"][] = $org_idx;
            }
            
            /// iterasi seluruh organisasi customer ...
            foreach($_SESSION["hris_org_customer"] as $k=>$org_idx) {
               $customer = array();
               $customer_curlevel = $customer_prevlevel = $customer_nextlevel = array();
               $prevlevel_job_id = 0;
               $curlevel_job_id = 0;
               $nextlevel_job_id = 0;
               $sql = "SELECT a.job_id,a.job_nm,b.job_class_nm,b.job_class_level"
                    . " FROM ".XOCP_PREFIX."jobs a"
                    . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
                    . " WHERE a.org_id = '$org_idx'"
                    . " ORDER BY b.job_class_level ASC";
               $res0 = $db->query($sql);
               if($db->getRowsNum($res0)>0) {
                  while(list($job_idx,$job_nmx,$job_class_nmx,$job_class_levelx)=$db->fetchRow($res0)) {
                     if($job_class_levelx<$job_class_level) {
                        $prevlevel_job_id = $job_idx;
                     }
                     if($job_class_levelx==$job_class_level) {
                        $curlevel_job_id = $job_idx;
                     }
                     if($job_class_levelx>$job_class_level) {
                        $nextlevel_job_id = $job_idx;
                        break;
                     }
                  }
               }
            
               if($curlevel_job_id>0) {
                  $sql = "SELECT a.employee_id,c.person_nm"
                       . " FROM ".XOCP_PREFIX."employee_job a"
                       . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
                       . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
                       . " WHERE a.job_id = '$curlevel_job_id'"
                       . " ORDER BY a.employee_id";
                  $res1 = $db->query($sql);
                  if($db->getRowsNum($res1)>0) {
                     while(list($employee_idx,$asse_nm)=$db->fetchRow($res1)) {
                        if($employee_idx==$employee_id) continue;
                        $customer[] = array($employee_idx,$asse_nm);
                        $customer_curlevel[] = array($employee_idx,$asse_nm);
                     }
                  }
               }

               if($prevlevel_job_id>0) {
                  $sql = "SELECT a.employee_id,c.person_nm"
                       . " FROM ".XOCP_PREFIX."employee_job a"
                       . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
                       . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
                       . " WHERE a.job_id = '$prevlevel_job_id'"
                       . " ORDER BY a.employee_id";
                  $res1 = $db->query($sql);
                  if($db->getRowsNum($res1)>0) {
                     while(list($employee_idx,$asse_nm)=$db->fetchRow($res1)) {
                        if($employee_idx==$employee_id) continue;
                        $customer[] = array($employee_idx,$asse_nm);
                        $customer_prevlevel[] = array($employee_idx,$asse_nm);
                     }
                  }
               }
               
               if($nextlevel_job_id>0) {
                  $sql = "SELECT a.employee_id,c.person_nm"
                       . " FROM ".XOCP_PREFIX."employee_job a"
                       . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
                       . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
                       . " WHERE a.job_id = '$nextlevel_job_id'"
                       . " ORDER BY a.employee_id";
                  $res1 = $db->query($sql);
                  if($db->getRowsNum($res1)>0) {
                     while(list($employee_idx,$asse_nm)=$db->fetchRow($res1)) {
                        if($employee_idx==$employee_id) continue;
                        $customer[] = array($employee_idx,$asse_nm);
                        $customer_nextlevel[] = array($employee_idx,$asse_nm);
                     }
                  }
               }
               
               if(count($customer_curlevel)>0) {
                  $customer = $customer_curlevel;
               } else {
                  $customer = array_merge($customer_nextlevel,$customer_prevlevel);
               }
               
               shuffle($customer);
               $min = $min_customer;
               $pos = strpos($max_customer,"%");
               if(!$pos) {
                  $max = $max_customer;
               } else {
                  $num = _bctrim(bcadd(0,substr($max_customer,0,$pos)));
                  $max = ceil(($num/100)*count($customer));
               }
               $pos = strpos($min_customer,"%");
               if(!$pos) {
                  $min = $min_customer;
               } else {
                  $num = _bctrim(bcadd(0,substr($min_customer,0,$pos)));
                  $min = ceil(($num/100)*count($customer));
               }
               if($min>$max) {
                  $limit = $min;
               } else {
                  $limit = $max;
               }
               
               $no = 1;
               foreach($customer as $v) {
                  list($employee_idx,$assessor_nm)=$v;
                  if($no>$limit) {
                     $status_cd = "inactive";
                  } else {
                     $status_cd = "active";
                  }
                  $sql = "INSERT INTO ".XOCP_PREFIX."assessor_360 (asid,employee_id,assessor_id,assessor_t,status_cd,generate_user_id) VALUES ('$asid','$employee_id','$employee_idx','customer','$status_cd','$user_id')";
                  $db->query($sql);
                  $no++;
               }
            }
         }
      }
   }
   
   
   //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
   
   function app_newSession($args) {
      $db=&Database::getInstance();
      $sql = "SELECT MAX(asid) FROM ".XOCP_PREFIX."assessment_session";
      $result = $db->query($sql);
      list($asidx)=$db->fetchRow($result);
      $asid = $asidx+1;
      $user_id = getUserID();
      $session_periode = date("Y");
      $start = getSQLDate();
      $stop = getSQLDate();
      $sql = "INSERT INTO ".XOCP_PREFIX."assessment_session (asid,session_nm,created_user_id,session_periode,assessment_start,assessment_stop)"
           . " VALUES('$asid','$session_nm','$user_id','$session_periode','$start','$stop')";
      $db->query($sql);
      
      $hdr = "<table><colgroup><col width='60'/><col/></colgroup><tbody><tr>"
           . "<td id='td_periode_${asid}'>$session_periode</td>"
           . "<td><span id='sp_${asid}' class='xlnk' onclick='edit_session(\"$asid\",this,event);'>"._EMPTY."</span></td>"
           . "</tr></tbody></table>";
      return array($asid,$hdr);
   }
   
   function app_saveSession($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $asid = $args[0];
      $vars = parseForm($args[1]);
      $session_nm = addslashes(trim($vars["session_nm"]));
      $session_periode = _bctrim(bcadd(0,$vars["session_periode"]));
      $start = getSQLDate($vars["assessment_start"]);
      $stop = getSQLDate($vars["assessment_stop"]);
      $default_session = $vars["default_session"]+0;
      $edit_employee = $vars["edit_employee"]+0;
      if($session_nm=="") {
         $session_nm = "noname";
      }
      if($asid=="new") {
         $sql = "SELECT MAX(asid) FROM ".XOCP_PREFIX."assessment_session";
         $result = $db->query($sql);
         list($asidx)=$db->fetchRow($result);
         $asid = $asidx+1;
         $user_id = getUserID();
         $sql = "INSERT INTO ".XOCP_PREFIX."assessment_session (asid,session_nm,created_user_id,session_periode,assessment_start,assessment_stop)"
              . " VALUES('$asid','$session_nm','$user_id','$session_periode','$start','$stop')";
         $db->query($sql);
      } else {
         
         $vars["peer_min"] = str_replace(" ","",$vars["peer_min"]);
         $vars["peer_max"] = str_replace(" ","",$vars["peer_max"]);
         $vars["sub_min"] = str_replace(" ","",$vars["sub_min"]);
         $vars["sub_max"] = str_replace(" ","",$vars["sub_max"]);
         $vars["cust_min"] = str_replace(" ","",$vars["cust_min"]);
         $vars["cust_max"] = str_replace(" ","",$vars["cust_max"]);
         
         $vars["assessi_peer_max"] = str_replace(" ","",$vars["assessi_peer_max"]);
         $vars["assessi_superior_max"] = str_replace(" ","",$vars["assessi_superior_max"]);
         $vars["assessi_customer_max"] = str_replace(" ","",$vars["assessi_customer_max"]);
         
         $vars["weight_superior"] = _bctrim(toMoney($vars["weight_superior"]));
         $vars["weight_subordinate"] = _bctrim(toMoney($vars["weight_subordinate"]));
         $vars["weight_peer"] = _bctrim(toMoney($vars["weight_peer"]));
         $vars["weight_customer"] = _bctrim(toMoney($vars["weight_customer"]));
         
         if($default_session==1) {
            $sql = "UPDATE ".XOCP_PREFIX."assessment_session SET default_session = '0'";
            $db->query($sql);
         }
         
         if($edit_employee==1) {
            $sql = "UPDATE ".XOCP_PREFIX."assessment_session SET edit_employee = '0'";
            $db->query($sql);
         }
         
         $sql = "UPDATE ".XOCP_PREFIX."assessment_session SET session_nm = '$session_nm',"
              . "session_periode = '$session_periode', assessment_start = '$start', assessment_stop = '$stop',"
              . "default_session = '$default_session',"
              . "edit_employee = '$edit_employee',"
              . "weight_superior = '".$vars["weight_superior"]."',"
              . "weight_subordinate = '".$vars["weight_subordinate"]."',"
              . "weight_peer = '".$vars["weight_peer"]."',"
              . "weight_customer = '".$vars["weight_customer"]."',"
              . "min_peer = '".$vars["peer_min"]."',"
              . "max_peer = '".$vars["peer_max"]."',"
              . "min_subordinat = '".$vars["sub_min"]."',"
              . "max_subordinat = '".$vars["sub_max"]."',"
              . "min_customer = '".$vars["cust_min"]."',"
              . "max_customer = '".$vars["cust_max"]."',"
              . "assessi_max_superior = '".$vars["assessi_superior_max"]."',"
              . "assessi_max_peer = '".$vars["assessi_peer_max"]."',"
              . "assessi_max_customer = '".$vars["assessi_customer_max"]."'"
              . " WHERE asid = '$asid'";
         $db->query($sql);
      }
      
      /*
      
      $sql = "SELECT employee_id,job_id FROM ".XOCP_PREFIX."employee_job"
           . " WHERE stop_dttm = '0000-00-00 00:00:00'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($employee_id,$job_id)=$db->fetchRow($result)) {
            $sql = "REPLACE INTO ".XOCP_PREFIX."assessment_session_job (asid,employee_id,job_id,updated_user_id)"
                 . " VALUES ('$asid','$employee_id','$job_id','$user_id')";
            $db->query($sql);
         }
      }
      
      */

      return array($asid,$session_nm,$session_periode);
   }
   
   function app_editSession($args) {
      $db=&Database::getInstance();
      $asid = $args[0];
      if($asid=="new") {
         $start = $stop = getSQLDate();
         $generate = "";
      } else {
         
         /// check for update
         $sql = "SELECT assessi_max_peer,assessi_max_superior,assessi_max_customer"
              . " FROM ".XOCP_PREFIX."assessment_session"
              . " WHERE asid = '$asid'";
         $result = $db->query($sql);
         $errno = $db->errno()+0;
         if($errno==1054) {
            $sql = "ALTER TABLE hris_assessment_session ADD COLUMN assessi_max_superior CHAR(30) NOT NULL DEFAULT ''";
            $db->query($sql);
            $sql = "ALTER TABLE hris_assessment_session ADD COLUMN assessi_max_peer CHAR(30) NOT NULL DEFAULT ''";
            $db->query($sql);
            $sql = "ALTER TABLE hris_assessment_session ADD COLUMN assessi_max_customer CHAR(30) NOT NULL DEFAULT ''";
            $db->query($sql);
         }
         
         $sql = "SELECT session_nm,session_periode,assessment_start,assessment_stop,"
              . "min_peer,max_peer,min_subordinat,max_subordinat,min_customer,max_customer,"
              . "weight_superior,weight_peer,weight_subordinate,weight_customer,"
              . "assessi_max_peer,assessi_max_superior,assessi_max_customer,default_session,edit_employee"
              . " FROM ".XOCP_PREFIX."assessment_session"
              . " WHERE asid = '$asid'";
         $result = $db->query($sql);
         
         list($session_nm,$session_periode,$start,$stop,$peer_min,$peer_max,$sub_min,$sub_max,$cust_min,$cust_max,
              $weight_superior,$weight_peer,$weight_subordinate,$weight_customer,
              $assessi_peer_max,$assessi_superior_max,$assessi_customer_max,$default_session,$edit_employee)=$db->fetchRow($result);
         $session_nm = htmlentities($session_nm,ENT_QUOTES);
         $generate = "<!-- tr><td>Superior Assessor</td><td>"
                   . "<input type='button' value='Reset' onclick='generate_superior(\"$asid\",this,event);'/>&nbsp;<span id='gsuperiorprogress'></span></td></tr -->"
                   
                   
                   . "<tr><td>Reporting Session</td><td><input ".($default_session==1?"checked='checked'":"")." type='checkbox' id='ckdefaultassessment' value='1' name='default_session'/> <span style='font-style:italic;'>Default session for reporting.</span></td></tr>"
                   . "<tr><td>Set Job Edit Employee</td><td><input ".($edit_employee==1?"checked='checked'":"")." type='checkbox' id='ckeditemployee' value='1' name='edit_employee'/> "
                   . "<span style='font-style:italic;color:red;'>Check in emergency only where edit job title will set job and assessor for this session.</span></td></tr>"
                   
                   . "<tr><td>Weighing</td><td>"
                   . "<table class='sfrm'><tbody>"
                   . "<tr><td>Superior:</td><td><input id='weight_superior' name='weight_superior' type='text' style='width:40px;text-align:right;' value='$weight_superior'/></td></tr>"
                   . "<tr><td>Subordinate:</td><td><input id='weight_subordinate' name='weight_subordinate' type='text' style='width:40px;text-align:right;' value='$weight_subordinate'/></td></tr>"
                   . "<tr><td>Peer:</td><td><input id='weight_peer' name='weight_peer' type='text' style='width:40px;text-align:right;' value='$weight_peer'/></td></tr>"
                   . "<tr><td>Customer:</td><td><input id='weight_customer' name='weight_customer' type='text' style='width:40px;text-align:right;' value='$weight_customer'/></td></tr>"
                   . "</tbody></table>"
                   . "</td></tr>"
                   
                   
                   . "<tr><td>Limit Assessi</td><td>"
                   . "<table class='sfrm' style=''><tbody>"
                   . "<tr>"
                       . "<td>Superior - Maximum:</td><td><input id='assessi_superior_max' name='assessi_superior_max' type='text' style='width:40px;text-align:right;' value='$assessi_superior_max'/> / Assessor</td></tr>"
                   . "<tr>"
                       . "<td>Peer - Maximum:</td><td><input id='assessi_peer_max' name='assessi_peer_max' type='text' style='width:40px;text-align:right;' value='$assessi_peer_max'/> / Assessor</td></tr>"
                   . "<tr>"
                       . "<td>Customer - Maximum:</td><td><input id='assessi_cust_max' name='assessi_customer_max' type='text' style='width:40px;text-align:right;' value='$assessi_customer_max'/> / Assessor</td></tr>"
                   . "</tbody></table>"
                   . "</td></tr>"
                   
                   . "<tr><td>Limit Assessor</td><td>"
                   . "<table class='sfrm' style=''><tbody>"
                   . "<tr>"
                       //. "<td>Peer - Minimum:</td><td><input id='peer_min' name='peer_min' type='text' style='width:40px;text-align:right;' value='$peer_min'/></td>"
                       . "<td>Peer - Maximum:</td><td><input id='peer_max' name='peer_max' type='text' style='width:40px;text-align:right;' value='$peer_max'/></td></tr>"
                   . "<tr>"
                       //. "<td>Sub Ordinat - Minimum:</td><td><input id='sub_min' name='sub_min' type='text' style='width:40px;text-align:right;' value='$sub_min'/></td>"
                       . "<td>Sub Ordinat - Maximum:</td><td><input id='sub_max' name='sub_max' type='text' style='width:40px;text-align:right;' value='$sub_max'/></td></tr>"
                   . "<tr>"
                       //. "<td>Customer - Minimum:</td><td><input id='cust_min' name='cust_min' type='text' style='width:40px;text-align:right;' value='$cust_min'/>/org</td>"
                       . "<td>Customer - Maximum:</td><td><input id='cust_max' name='cust_max' type='text' style='width:40px;text-align:right;' value='$cust_max'/></td></tr>"
                   . "</tbody></table>"
                   . "<input type='hidden' id='peer_min' name='peer_min' value='0'/>"
                   . "<input type='hidden' id='sub_min' name='sub_min' value='0'/>"
                   . "<input type='hidden' id='cust_min' name='cust_min' value='0'/>"
                   . "<div style='margin-top:5px;'><input type='button' id='savegenerate_btn' value='Save &amp; Generate Assessor' onclick='generate360(\"$asid\",this,event);'/>&nbsp;<span id='g360progress'></span></div>"
                   . "</td></tr>"
                   
                   . "<tr><td>Assessor Password</td><td><input id='btn_reset_password' type='button' value='Reset' onclick='reset_pass_session(\"$asid\",this.parentNode,event);'/>"
                   . "&nbsp;&nbsp;<input id='btn_xlspass' type='button' value='Download XLS' onclick='xlspass(\"$asid\");'/></td></tr>";
      }
      $ret = "<form id='frm'><table class='xxfrm' style='width:100%;'><colgroup><col width='160'/><col/></colgroup><tbody>"
           . "<tr><td>Session Name</td><td><input type='text' value=\"$session_nm\" id='inp_session_nm' name='session_nm' style='width:90%;'/></td></tr>"
           . "<tr><td>Year</td><td><input type='text' value=\"$session_periode\" id='inp_session_periode' name='session_periode' style='width:50px;'/></td></tr>"
           . "<tr><td>Assessment Start</td><td><span class='xlnk' onclick='chstart(this,event);' id='assessment_start_txt'>".sql2ind($start)."</span>"
           . "<input type='hidden' name='assessment_start' id='assessment_start' value='$start'/></td></tr>"
           . "<tr><td>Assessment Stop</td><td><span class='xlnk' onclick='chstop(this,event);' id='assessment_stop_txt'>".sql2ind($stop)."</span>"
           . "<input type='hidden' name='assessment_stop' id='assessment_stop' value='$stop'/></td></tr>"
           
           . $generate
           
           . "<tr><td colspan='2'><span id='progress'></span>&nbsp;"
           . "<input id='btn_save_session' onclick='save_session();' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input id='btn_cancel_edit' onclick='cancel_edit();' type='button' value='"._CANCEL."'/>&nbsp;&nbsp;"
           . ($asid!="new"?"<input id='btn_delete_session' onclick='delete_session();' type='button' value='"._DELETE."'/>":"")
           . "</td></tr>"
           . "</tbody></table></form>";
      return $ret;
   }
   
   function app_Delete($args) {
      $db=&Database::getInstance();
      $asid = $args[0];
      $user_id = getUserID();
      $sql = "UPDATE ".XOCP_PREFIX."assessment_session SET status_cd = 'nullified', nullified_user_id = '$user_id', nullified_dttm = now() WHERE asid = '$asid'";
      $db->query($sql);
   }
   
}

} /// HRIS_ASSESSMENTSESSIONAJAX_DEFINED
?>