<?php
//--------------------------------------------------------------------//
// Filename : modules/pms/pmsmyactionplan.php                         //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2010-09-22                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('PMS_MYACTIONPLAN_DEFINED') ) {
   define('PMS_MYACTIONPLAN_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
include_once(XOCP_DOC_ROOT."/modules/pms/class/ajax_objective.php");
require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
include_once(XOCP_DOC_ROOT."/modules/pms/class/selectpms.php");

class _pms_MyActionPlan extends XocpBlock {
   var $catchvar = _PMS_CATCH_VAR;
   var $blockID = _PMS_MYACTIONPLAN_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _PMS_MYACTIONPLAN_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _pms_MyActionPlan($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */

   }
   
   
   function pmsmyactionplan($employee_id,$report_mode=FALSE,$force_month_id=0) {
      $psid = $_SESSION["pms_psid"];
      global $xocp_vars;
      
      
      
      if(!isset($_SESSION["pms_month"])) {
         $_SESSION["pms_month"] = 1;
      }
      
      if($force_month>0) {
         $_SESSION["pms_month"] = $force_month_id;
      }
      
      $db=&Database::getInstance();
      $ajax = new _pms_class_ObjectiveAjax("orgjx");
      $user_id = getUserID();
      
      $current_year = 2011;
      
      $sql = "SELECT session_nm FROM pms_session WHERE psid  = '$psid'";
      $result = $db->query($sql);
      list($session_nm)=$db->fetchRow($result);
      
      if(!isset($_SESSION["ach_dttm"])) {
         $ach_dttm = getSQLDate();
         $_SESSION["ach_dttm"] = $ach_dttm;
      } else {
         $ach_dttm = $_SESSION["ach_dttm"];
      }
      
      $sql = "SELECT DISTINCT(approval_st) FROM pms_pic_action WHERE psid = '$psid' AND employee_id = '$employee_id' AND actionplan_group_id = '0' AND is_pica = '0'";
      $result = $db->query($sql);
      $arr_approval_st = array();
      if($db->getRowsNum($result)>0) {
         while(list($ap_approval_st)=$db->fetchRow($result)) {
            $arr_approval_st[$ap_approval_st] = 1;
         }
      }
      
      list($job_id,
           $employee_idx,
           $job_nm,
           $employee_nm,
           $nip,
           $gender,
           $jobstart,
           $entrance_dttm,
           $jobage,
           $job_summary,
           $person_id,
           $employee_user_id,
           $first_assessor_job_id,
           $next_assessor_job_id)=_hris_getinfobyemployeeid($employee_id);
      
      $ap_status_cd = "";
      $submit_dttm = "0000-00-00 00:00:00";
      $first_assessor_approved_dttm = "0000-00-00 00:00:00";
      $next_assessor_approved_dttm = "0000-00-00 00:00:00";
      
      list($self_job_id,
           $self_employee_id,
           $self_job_nm,
           $self_nm,
           $self_nip,
           $self_gender,
           $self_jobstart,
           $self_entrance_dttm,
           $self_jobage,
           $self_job_summary,
           $self_person_id,
           $self_user_id,
           $self_first_assessor_job_id,
           $self_next_assessor_job_id)=_hris_getinfobyuserid($user_id);
      
      $ret = "";
      
      $sql = "SELECT approval_st,return_note,report_return_note,submit_dttm,approval1_dttm,report_approval_st,report_submit_dttm,report_approval_dttm,is_pica FROM pms_pic_action"
           . " WHERE psid = '$psid' AND employee_id = '$employee_id' AND actionplan_group_id = '0'"
           . ($report_mode==TRUE?" AND month_id = '".$_SESSION["pms_month"]."'":"");
      $result = $db->query($sql);
      $return_notes = "";
      $status_return = 0;
      $status_new = 0;
      $status_approval1 = 0;
      $status_implementation = 0;
      $ap_need_submission=0;
      $ap_need_approval=0;
      $ap_submit_dttm = "0000-00-00 00:00:00";
      $ap_approval_dttm = "0000-00-00 00:00:00";
      
      $apreport_need_submission = 0;
      $apreport_status_approval = 0;
      $apreport_submit_dttm = "0000-00-00 00:00:00";
      $apreport_approval_dttm = "0000-00-00 00:00:00";
      $apreport_status_approval = 0;
      $apreport_need_approval = 0;
      
      if($db->getRowsNum($result)>0) {
         while(list($ap_status_cd,$return_note,$report_return_note,$submit_dttm,$approval1_dttm,$report_approval_st,$report_submit_dttm,$report_approval_dttm,$is_pica)=$db->fetchRow($result)) {
            $ap_submit_dttm = max($ap_submit_dttm,$submit_dttm);
            $ap_approval_dttm = max($ap_approval_dttm,$approval1_dttm);
            $apreport_submit_dttm = max($apreport_submit_dttm,$report_submit_dttm);
            $apreport_approval_dttm = max($apreport_approval_dttm,$report_approval_dttm);
            
            if($is_pica==0) {
               if($ap_status_cd=="return") {
                  $status_return++;
                  $ap_need_submission++;
               }
               if($ap_status_cd=="new") {
                  $status_new++;
                  $ap_need_submission++;
               }
               if($ap_status_cd=="approval1") {
                  $status_approval1++;
                  $ap_need_approval++;
               }
               if($ap_status_cd=="implementation") {
                  $status_implementation++;
               }
            }
            
            
            if($report_approval_st=="return") {
               $apreport_need_submission++;
            }
            if($report_approval_st=="new") {
               $apreport_need_submission++;
            }
            if($report_approval_st=="approval") {
               $apreport_need_approval++;
            }
            if($report_approval_st=="final") {
               $apreport_final++;
            }
            
         }
      }
      
      $sql = "SELECT DISTINCT(return_note) FROM pms_pic_action WHERE psid = '$psid' AND employee_id = '$employee_id' AND approval_st = 'return'";
      $result = $db->query($sql);
      $return_notes = "";
      if($db->getRowsNum($result)>0) {
         while(list($return_note)=$db->fetchRow($result)) {
               $return_notes .= "<div>$return_note</div>";
         }
         if($return_notes != "") {
               $return_notes = "<div style='border:1px solid #bbf;max-width:600px;-moz-border-radius:5px;padding:5px;color:blue;margin-bottom:10px;'>"
                             . "<div style='font-weight:bold;color:#000;'>Returned / Not Approved:</div>"
                             . "$return_notes"
                             . "</div>";
         }
      }
      
      $sql = "SELECT DISTINCT(report_return_note) FROM pms_pic_action WHERE psid = '$psid' AND employee_id = '$employee_id' AND month_id = '".$_SESSION["pms_month"]."' AND report_approval_st = 'return'";
      $result = $db->query($sql);
      $report_return_notes = "";
      if($db->getRowsNum($result)>0) {
         while(list($report_return_note)=$db->fetchRow($result)) {
               $report_return_notes .= "<div>$report_return_note</div>";
         }
         if($report_return_notes != "") {
               $report_return_notes = "<div style='border:1px solid #bbf;max-width:600px;-moz-border-radius:5px;padding:5px;color:blue;margin-bottom:10px;'>"
                             . "<div style='font-weight:bold;color:#000;'>Returned / Not Approved:</div>"
                             . "$report_return_notes"
                             . "</div>";
         }
      }
      
      $tooltip = "<div id='all_ap_tooltip' style='display:none;'>";
      

      $ret .= "<div>";
      
      $ret .= $return_notes;
      $ret .= $report_return_notes;
      
      $ret .= "<table style='width:900px;margin-top:10px;table-layout:fixed;' class='xxlist'>"
            . "<colgroup>"
            . "<col width='60'/>"
            . "<col width='".($report_mode==TRUE?"125":"125")."'/>"
            . "<col width='80'/>";
      
      if($report_mode==TRUE) {
         $ret .= "<col width='100'/>";
         $ret .= "<col width='120'/>";
         $ret .= "<col width='120'/>";
         $ret .= "<col width='120'/>";
      } else {
         $ret .= "<col width='40'/>"
               . "<col width='40'/>"
               . "<col width='40'/>"
               . "<col width='40'/>"
               . "<col width='40'/>"
               . "<col width='40'/>"
               . "<col width='40'/>"
               . "<col width='40'/>"
               . "<col width='40'/>"
               . "<col width='40'/>"
               . "<col width='40'/>"
               . "<col width='40'/>";
      }
      
      $ret .= "<col width='*'/>"
            . "<col width='40'/>"
            . "</colgroup>";
      
      $ret .= "<thead>"
            . "<tr>"
               //. "<td style='border-right:1px solid #bbb;text-align:center;' rowspan='2'>No.</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;' colspan='3' rowspan='2'>Action Plan</td>"
               . "<td ".($report_mode==TRUE?"":"colspan='12'")." style='border-right:1px solid #bbb;text-align:center;'>$session_nm</td>"
               . ($report_mode==TRUE?"<td colspan='3' style='border-right:1px solid #bbb;text-align:center;'>PICA</td>":"")
               . "<td style='text-align:center;border-right:1px solid #bbb;' rowspan='2'>".($report_mode==TRUE?"Target":"Monthly Target")."</td>"
               . "<td rowspan='2' style='text-align:center;'>YTD<br/>Ach.</td>"
            . "</tr>"
            . "<tr>";
      if($report_mode==TRUE) {
         $ret .= "<td style='border-right:1px solid #bbb;text-align:center;'>";
         if($force_month_id==0) {
            $ret .= "<select onchange='set_month(this,event);'>";
            foreach($xocp_vars["month_year"] as $k=>$v) {
               if($v=="") continue;
               $ret .= "<option value='$k' ".($k==$_SESSION["pms_month"]?"selected='1'":"").">$v</option>";
            }
            $ret .= "</select>";
         } else {
            $ret .= $xocp_vars["month_year"][$force_month_id];
         }
         $ret .= "</td>";
         $ret .= "<td style='text-align:center;border-right:1px solid #bbb;'>Root Cause</td>";
         $ret .= "<td style='text-align:center;border-right:1px solid #bbb;'>Improvement</td>";
         $ret .= "<td style='text-align:center;border-right:1px solid #bbb;'>Target Month</td>";
      } else {
         $ret .= "<td style='border-right:1px solid #bbb;text-align:center;'>Jan</td>"
                  . "<td style='border-right:1px solid #bbb;text-align:center;'>Feb</td>"
                  . "<td style='border-right:1px solid #bbb;text-align:center;'>Mar</td>"
                  . "<td style='border-right:1px solid #bbb;text-align:center;'>Apr</td>"
                  . "<td style='border-right:1px solid #bbb;text-align:center;'>May</td>"
                  . "<td style='border-right:1px solid #bbb;text-align:center;'>Jun</td>"
                  . "<td style='border-right:1px solid #bbb;text-align:center;'>Jul</td>"
                  . "<td style='border-right:1px solid #bbb;text-align:center;'>Aug</td>"
                  . "<td style='border-right:1px solid #bbb;text-align:center;'>Sep</td>"
                  . "<td style='border-right:1px solid #bbb;text-align:center;'>Oct</td>"
                  . "<td style='border-right:1px solid #bbb;text-align:center;'>Nov</td>"
                  . "<td style='border-right:1px solid #bbb;text-align:center;'>Dec</td>";
      }
      $ret .= "</tr>"
            . "</thead>";
      
      $ret .= "<tbody>";
      
      $sql = "SELECT a.pms_objective_id,a.pms_share_weight,b.pms_objective_text"
           . " FROM pms_actionplan_share a"
           . " LEFT JOIN pms_objective b USING(pms_objective_id)"
           . " WHERE a.psid = '$psid' AND a.pms_actionplan_pic_employee_id = '$employee_id'"
           . " AND b.pms_objective_id IS NOT NULL"
           . " ORDER BY b.pms_perspective_id,b.pms_objective_no";
      $result = $db->query($sql);
      $total_weight = 0;
      $arr_share = array();
      $objective_share_arr = array();
      if($db->getRowsNum($result)>0) {
         while(list($pms_objective_id,$pms_share_weight,$pms_objective_text)=$db->fetchRow($result)) {
            $total_weight = _bctrim(bcadd($total_weight,$pms_share_weight));
            $objective_share_arr[$pms_objective_id] = _bctrim(bcadd($objective_share_arr[$pms_objective_id],$pms_share_weight));
            $arr_share[$pms_objective_id] = array($pms_objective_id,$pms_share_weight,$pms_objective_text);
         }
         
         foreach($arr_share as $pms_objective_id=>$v) {
            list($pms_objective_idx,$pms_share_weight,$pms_objective_text)=$v;
            $sql = "SELECT * FROM pms_jam WHERE psid = '$psid' AND employee_id = '$employee_id' AND pms_objective_id = '$pms_objective_id' AND jam_org_ind = '0'";
            $rjam = $db->query($sql);
            if($db->getRowsNum($rjam)==0) {
               $objective_weight = _bctrim(bcmul(bcdiv($objective_share_arr[$pms_objective_id],$total_weight),100));
               //$sql = "INSERT INTO pms_jam (psid,pms_objective_id,employee_id,objective_weight) VALUES ('$psid','$pms_objective_id','$employee_id','$objective_weight')";
               //$db->query($sql);
            } else {
               $objective_weight = _bctrim(bcmul(bcdiv($objective_share_arr[$pms_objective_id],$total_weight),100));
               //$sql = "UPDATE pms_jam SET objective_weight = '$objective_weight' WHERE psid = '$psid' AND pms_objective_id = '$pms_objective_id' AND employee_id = '$employee_id'";
               //$db->query($sql);
            }
         }
      }
      
      ////////////////////////////////////
      
      $zone_cnt = 0;
      $zone_ttl = 0;
      
      $sql = "SELECT b.pms_objective_no,c.pms_perspective_code,b.pms_org_id,a.pms_objective_id,b.pms_objective_text,a.objective_weight,"
           . "a.target_text0,a.target_text1,a.target_text2,a.target_text3,a.target_text4,a.final_result_text,"
           . "a.target_weight0,a.target_weight1,a.target_weight2,a.target_weight3,a.target_weight4,a.final_result_weight"
           . " FROM pms_jam a"
           . " LEFT JOIN pms_objective b USING(pms_objective_id)"
           . " LEFT JOIN pms_perspective c USING(pms_perspective_id)"
           . " WHERE a.psid = '$psid' AND a.employee_id = '$employee_id'"
           . " AND a.approval_st = 'implementation'"
           . " AND b.pms_objective_id IS NOT NULL"
           . " ORDER BY b.pms_perspective_id,b.pms_objective_no";
      
      $result = $db->query($sql);
      $ttl_weight = 0;
      $ttl_target_weight0 = 0;
      $ttl_target_weight1 = 0;
      $ttl_target_weight2 = 0;
      $ttl_target_weight3 = 0;
      $ttl_target_weight4 = 0;
      if($db->getRowsNum($result)>0) {
         $no = 0;
         while(list($pms_objective_no,$pms_perspective_code,$pms_org_id,$pms_objective_id,$pms_objective_text,$objective_weight,
                    $target_text0,$target_text1,$target_text2,$target_text3,$target_text4,$final_result_text,
                    $target_weight0,$target_weight1,$target_weight2,$target_weight3,$target_weight4,$final_result_weight)=$db->fetchRow($result)) {
            $no++;
            $current_objective_ap_count = 0;
            
            $submit_dttm = $submit_dttmx;
            $first_assessor_approved_dttm = $approval1_dttm;
            $next_assessor_approved_dttm = $approval2_dttm;
            
            if(trim($target_text0)=="") $target_text0 = _EMPTY;
            if(trim($target_text1)=="") $target_text1 = _EMPTY;
            if(trim($target_text2)=="") $target_text2 = _EMPTY;
            if(trim($target_text3)=="") $target_text3 = _EMPTY;
            if(trim($target_text4)=="") $target_text4 = _EMPTY;
            if(trim($final_result_text)=="") $final_result_text = _EMPTY;
            
            $target_weight0 = bcdiv(bcmul(59,$objective_weight),100);
            $target_weight1 = bcdiv(bcmul(69,$objective_weight),100);
            $target_weight2 = bcdiv(bcmul(79,$objective_weight),100);
            $target_weight3 = bcdiv(bcmul(89,$objective_weight),100);
            $target_weight4 = bcdiv(bcmul(100,$objective_weight),100);
            $final_result_weight += 0;
            
            if($report_mode==TRUE) {
               $apdata = $ajax->getActionPlanUpdate($pms_objective_id,$employee_id,TRUE,$_SESSION["pms_month"]);
            } else {
               $apdata = $ajax->getActionPlanUpdate($pms_objective_id,$employee_id);
            }
            
            $tooltip .= "<div id='so_ap_tooltip_${pms_objective_id}'>".$apdata[15]."</div>";
            
            
            $sql = "SELECT b.pms_actionplan_text,a.pms_share_weight,b.pms_actionplan_start,b.pms_actionplan_stop FROM pms_actionplan_share a"
                 . " LEFT JOIN pms_actionplan b USING(pms_objective_id,pms_actionplan_id)"
                 . " WHERE a.psid = '$psid' AND a.pms_objective_id = '$pms_objective_id'"
                 . " AND a.pms_actionplan_pic_employee_id = '$employee_id'";
            $rap = $db->query($sql);
            $apdiv = "<div style='padding-left:20px;font-size:0.9em;'>";
            
            
                  
                  $sql = "SELECT pms_kpi_id,pms_kpi_text,pms_kpi_weight,pms_kpi_target_text,pms_kpi_measurement_unit"
                       . " FROM pms_kpi WHERE psid = '$psid' AND pms_objective_id = '$pms_objective_id'";
                  $rkpi = $db->query($sql);
                  $kpi_cnt = $db->getRowsNum($rkpi);
                  $kpix = "";
                  if($kpi_cnt>0) {
                     while(list($xkpiid,$xkpitxt,$xkpiw,$xkpitarget,$xunit)=$db->fetchRow($rkpi)) {
                        $kpix .= "<div style='color:#55f;'>KPI : $xkpitxt : $xkpitarget $xunit</div>";
                     }
                  } else {
                     $kpix = "";
                  }
                  
                  $apdiv .= $kpix;
            
            if($db->getRowsNum($rap)>0) {
               while(list($pms_actionplan_text,$pms_share_weight,$ap_start_dttmxx,$ap_stop_dttmxx)=$db->fetchRow($rap)) {
                  
                  
                  $apdiv .= "<div>Action Plan : $pms_actionplan_text / ".(sql2ind($ap_start_dttmxx,"date"))." - ".(sql2ind($ap_stop_dttmxx,"date"))." / ".toMoney($pms_share_weight)."%</div>";
               }
            }
            $apdiv .= "</div>";
            
            
            
            $ret .= "<tr>";
            //$ret .= "<td style='border-right:1px solid #bbb;text-align:center;' ".($report_mode==TRUE?"rowspan='2'":"rowspan='3'").">$no</td>";
            $ret .= "<td style='vertical-align:top;border-right:1px solid #bbb;text-align:left;font-weight:bold;color:black;' rowspan='1'>${pms_perspective_code}${pms_objective_no}</td>";
            $ret .= "<td style='border-right:0px solid #bbb;' ".($report_mode==TRUE?"colspan='7'":"colspan='16'")."><span style='color:black;font-weight:bold;'>$pms_objective_text</span>${apdiv}</td>";
            $ret .= "</tr>";
            
            $ret .= "<tr ".($report_mode==TRUE?"":"height='75'").">";
            
            $ret .= "<td colspan='3' id='tdaptext_${pms_objective_id}' style='border-right:1px solid #bbb;vertical-align:top;overflow:hidden;padding:0px;'>"
                  . $apdata[1]
                  . "</td>"; //// actionplan
            
            if($report_mode==TRUE) {
               $current_month = $_SESSION["pms_month"];
               $ret .= "<td style='text-align:center;padding:0px;border-right:1px solid #bbb;vertical-align:top;border-bottom:1px solid #bbb;' id='tdap_${pms_objective_id}_${current_month}'>".$apdata[$current_month+2]."</td>";
               $ret .= "<td style='text-align:left;padding:0px;border-right:1px solid #bbb;vertical-align:top;border-bottom:1px solid #bbb;overflow:hidden;' id='tdpica_root_${pms_objective_id}_${current_month}'>".$apdata[16]."</td>";
               $ret .= "<td style='text-align:left;padding:0px;border-right:1px solid #bbb;vertical-align:top;border-bottom:1px solid #bbb;overflow:hidden;' id='tdpica_improve_${pms_objective_id}_${current_month}'>".$apdata[17]."</td>";
               $ret .= "<td style='text-align:left;padding:0px;border-right:1px solid #bbb;vertical-align:top;border-bottom:1px solid #bbb;overflow:hidden;' id='tdpica_month_${pms_objective_id}_${current_month}'>".$apdata[18]."</td>";
            } else {
               $ret .= "<td style='padding:0px;border-right:1px solid #ddd;vertical-align:top;border-bottom:0;' id='tdap_${pms_objective_id}_1'>".$apdata[3]."</td>";
               $ret .= "<td style='padding:0px;border-right:1px solid #ddd;vertical-align:top;border-bottom:0;' id='tdap_${pms_objective_id}_2'>".$apdata[4]."</td>";
               $ret .= "<td style='padding:0px;border-right:1px solid #ddd;vertical-align:top;border-bottom:0;' id='tdap_${pms_objective_id}_3'>".$apdata[5]."</td>";
               $ret .= "<td style='padding:0px;border-right:1px solid #ddd;vertical-align:top;border-bottom:0;' id='tdap_${pms_objective_id}_4'>".$apdata[6]."</td>";
               $ret .= "<td style='padding:0px;border-right:1px solid #ddd;vertical-align:top;border-bottom:0;' id='tdap_${pms_objective_id}_5'>".$apdata[7]."</td>";
               $ret .= "<td style='padding:0px;border-right:1px solid #ddd;vertical-align:top;border-bottom:0;' id='tdap_${pms_objective_id}_6'>".$apdata[8]."</td>";
               $ret .= "<td style='padding:0px;border-right:1px solid #ddd;vertical-align:top;border-bottom:0;' id='tdap_${pms_objective_id}_7'>".$apdata[9]."</td>";
               $ret .= "<td style='padding:0px;border-right:1px solid #ddd;vertical-align:top;border-bottom:0;' id='tdap_${pms_objective_id}_8'>".$apdata[10]."</td>";
               $ret .= "<td style='padding:0px;border-right:1px solid #ddd;vertical-align:top;border-bottom:0;' id='tdap_${pms_objective_id}_9'>".$apdata[11]."</td>";
               $ret .= "<td style='padding:0px;border-right:1px solid #ddd;vertical-align:top;border-bottom:0;' id='tdap_${pms_objective_id}_10'>".$apdata[12]."</td>";
               $ret .= "<td style='padding:0px;border-right:1px solid #ddd;vertical-align:top;border-bottom:0;' id='tdap_${pms_objective_id}_11'>".$apdata[13]."</td>";
               $ret .= "<td style='padding:0px;border-right:1px solid #bbb;vertical-align:top;border-bottom:0;' id='tdap_${pms_objective_id}_12'>".$apdata[14]."</td>";
            }
            
            $ret .= "<td style='padding:0px;border-right:1px solid #bbb;vertical-align:top;overflow:hidden;' ".($report_mode==TRUE?"":"rowspan='2'")." id='tdtg_${pms_objective_id}'>".$apdata[2]."</td>";
            $ret .= "<td style='padding:0px;border-right:0px solid #bbb;vertical-align:top;overflow:hidden;text-align:center;border-bottom:0;' id='tdytd_${pms_objective_id}'>".$apdata[20]."</td>";
            $ret .= "</tr>";
            
            
            if($report_mode==TRUE) {
            } else {
               $ret .= "<tr>";
               $ret .= "<td colspan='3' style='border-right:1px solid #bbb;text-align:right;'>".toMoney(_bctrim($objective_weight))." %</td>";
               $ret .= "<td style='border-right:1px solid #ddd;text-align:center;'>&nbsp;</td>";
               $ret .= "<td style='border-right:1px solid #ddd;text-align:center;'>&nbsp;</td>";
               $ret .= "<td style='border-right:1px solid #ddd;text-align:center;'>&nbsp;</td>";
               $ret .= "<td style='border-right:1px solid #ddd;text-align:center;'>&nbsp;</td>";
               $ret .= "<td style='border-right:1px solid #ddd;text-align:center;'>&nbsp;</td>";
               $ret .= "<td style='border-right:1px solid #ddd;text-align:center;'>&nbsp;</td>";
               $ret .= "<td style='border-right:1px solid #ddd;text-align:center;'>&nbsp;</td>";
               $ret .= "<td style='border-right:1px solid #ddd;text-align:center;'>&nbsp;</td>";
               $ret .= "<td style='border-right:1px solid #ddd;text-align:center;'>&nbsp;</td>";
               $ret .= "<td style='border-right:1px solid #ddd;text-align:center;'>&nbsp;</td>";
               $ret .= "<td style='border-right:1px solid #ddd;text-align:center;'>&nbsp;</td>";
               $ret .= "<td style='border-right:1px solid #bbb;text-align:center;'>&nbsp;</td>";
               $ret .= "<td style='border-right:0px solid #bbb;text-align:center;'>&nbsp;</td>";
               $ret .= "</tr>";
            }
            
            $ttl_target_weight0 = _bctrim(bcadd($ttl_target_weight0,$target_weight0));
            $ttl_target_weight1 = _bctrim(bcadd($ttl_target_weight1,$target_weight1));
            $ttl_target_weight2 = _bctrim(bcadd($ttl_target_weight2,$target_weight2));
            $ttl_target_weight3 = _bctrim(bcadd($ttl_target_weight3,$target_weight3));
            $ttl_target_weight4 = _bctrim(bcadd($ttl_target_weight4,$target_weight4));
         }
      } else {
         $ret .= "<tr><td colspan='16' style='text-align:center;font-style:italic;'>JAM not found</td></tr>";
      }
      
      if($zone_cnt>0) {
         $zone_ttl_txt = toMoney(bcdiv($zone_ttl,$zone_cnt))." %";
      } else {
         $zone_ttl_txt = "-";
      }
      
      $tooltip .= "</div>";
      
      if($report_mode==TRUE) {
      } else {
         $ret .= "<tr>";
         $ret .= "<td style='border-top:1px solid #bbb;background-color:#eee;color:black;border-right:1px solid transparent;text-align:center;font-weight:bold;' colspan='2'>Total</td>";
         $ret .= "<td style='border-top:1px solid #bbb;background-color:#eee;color:black;border-right:1px solid #bbb;text-align:right;font-weight:bold;'>100 %</td>";
         $ret .= "<td style='border-top:1px solid #bbb;background-color:#eee;color:black;border-right:1px solid #bbb;text-align:right;font-weight:bold;' ".($report_mode==TRUE?"":"colspan='12'")."></td>";
         $ret .= "<td style='border-top:1px solid #bbb;background-color:#eee;color:black;border-right:1px solid #bbb;text-align:center;font-weight:bold;'>$zone_ttl_txt</td>";
         $ret .= "<td style='border-top:1px solid #bbb;background-color:#eee;color:black;border-right:0px solid #bbb;text-align:center;font-weight:bold;'>&nbsp;</td>";
         $ret .= "</tr>";
      }
      
      $ret .= "</tbody>";
      $ret .= "</table>";
      $ret .= "</div>";
      
      $doubleapproval = 1;
      
      $sql = "SELECT c.job_nm,c.job_abbr,d.org_nm,d.org_abbr,a.employee_ext_id,e.person_nm,e.person_id"
           . " FROM ".XOCP_PREFIX."employee a"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job b ON b.employee_id = a.employee_id AND b.job_id = '$job_id'"
           . " LEFT JOIN ".XOCP_PREFIX."jobs c ON c.job_id = '$job_id'"
           . " LEFT JOIN ".XOCP_PREFIX."orgs d ON d.org_id = c.org_id"
           . " LEFT JOIN ".XOCP_PREFIX."persons e ON e.person_id = a.person_id"
           . " WHERE a.employee_id = '$employee_id'";
      $result = $db->query($sql);
      list($job_nm,$job_abbr,$org_nm,$org_abbr,$nip,$employee_nm,$person_id)=$db->fetchRow($result);
      
      $sql = "SELECT a.job_nm,b.employee_ext_id,c.person_nm,b.employee_id"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job e USING(job_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " WHERE a.job_id = '$first_assessor_job_id'"
           . " ORDER BY e.start_dttm DESC LIMIT 1";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($first_superior_job,$nip,$first_superior_name,$first_assessor_employee_id)=$db->fetchRow($result);
      }
      
      $sql = "SELECT a.job_nm,b.employee_ext_id,c.person_nm,b.employee_id"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job e USING(job_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " WHERE a.job_id = '$next_assessor_job_id'"
           . " ORDER BY e.start_dttm DESC LIMIT 1";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($next_superior_job,$nip,$next_superior_name,$next_assessor_employee_id)=$db->fetchRow($result);
      }
      
      $ret .= "<div style='padding:20px;'>&#160;</div>";
      
      if($report_mode==TRUE) {
         $form .= "<div style='width:900px;text-align:right;padding:10px;margin-top:10px;margin-bottom:100px;'>"
                . "<table align='center' style='border-top:2px solid #777;border-left:2px solid #777;border-spacing:0px;'>"
                . "<colgroup>"
                . "<col width='200'/>"
                . "<col width='200'/>"
                . "</colgroup>"
                . "<tbody>"
                . "<tr>"
                . "<td style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;'>"
                . "Submited by,"
                . "</td>"
                . "<td style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;'>"
                . "Approved by,"
                . "</td>"
                . "</tr>"
                . "<tr>"
                . "<td style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
                . "$employee_nm"
                . "</td>"
                . "<td style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
                . "$first_superior_name"
                . "</td>"
                
                . "</tr>"
                . "<tr>"
                . "<td style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;padding:10px;' id='submited_by_button'>"
                . (($apreport_need_submission>0)&&$self_job_id==$job_id?"<input type='button' value='Submit Report' onclick='confirm_submit_apreport(this,event);'/>":"")
                . (!($apreport_need_submission>0)?"Submited at:<br/>".sql2ind($apreport_submit_dttm,"date"):"")
                . ($apreport_need_submission>0&&$self_employee_id!=$employee_id?"Preparation":"")
                . "</td>"
                . "<td style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;padding:10px;'>"
                . ($apreport_need_submission>0?"-":"")
                . (($apreport_need_approval>0)&&$self_job_id==$first_assessor_job_id?"<input type='button' value='Approve' onclick='confirm_report_approval(this,event);'/>&nbsp;<input type='button' value='Not Approve' onclick='first_assessor_return_PMSActionPlanReport(\"$employee_id\");'/>":"")
                . ($apreport_need_approval>0&&$self_job_id!=$first_assessor_job_id&&$apreport_need_submission==0?"Waiting for approval":"")
                . ($apreport_need_submission==0&&$apreport_need_approval=="0"?"Approved at:<br/>".sql2ind($apreport_approval_dttm,"date"):"")
                . "</td>"
                
                . "</tr>"
                . "</tbody>"
                . "</table>"
                . "</div>";
      } else {
         $form .= "<div style='width:900px;text-align:right;padding:10px;margin-top:10px;margin-bottom:100px;'>"
                . "<table align='center' style='border-top:2px solid #777;border-left:2px solid #777;border-spacing:0px;'>"
                . "<colgroup>"
                . "<col width='200'/>"
                . "<col width='200'/>"
                . "</colgroup>"
                . "<tbody>"
                . "<tr>"
                . "<td style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;'>"
                . "Submited by,"
                . "</td>"
                . "<td style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;'>"
                . "Approved by,"
                . "</td>"
                . "</tr>"
                . "<tr>"
                . "<td style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
                . "$employee_nm"
                . "</td>"
                . "<td style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
                . "$first_superior_name"
                . "</td>"
                
                . "</tr>"
                . "<tr>"
                . "<td style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;padding:10px;' id='submited_by_button'>"
                . (($ap_need_submission>0)&&$self_job_id==$job_id?"<input type='button' value='Submit' onclick='confirm_submit(this,event);'/>":"")
                . (!($ap_need_submission>0)?"Submited at:<br/>".sql2ind($ap_submit_dttm,"date"):"")
                . (($ap_need_submission>0)&&$self_job_id!=$job_id?"Preparation":"")
                . "</td>"
                . "<td style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;padding:10px;'>"
                . ($ap_need_submission>0?"-":"")
                . (($status_approval1>0)&&$self_job_id==$first_assessor_job_id?"<input type='button' value='Approve' onclick='confirm_approval1(this,event);'/>&nbsp;<input type='button' value='Not Approve' onclick='first_assessor_return_PMSActionPlan(\"$employee_id\");'/>":"")
                . ($ap_need_approval>0&&$self_job_id!=$first_assessor_job_id?"Waiting for approval":"")
                . ($status_implementation>0&&$ap_need_submission==0&&$ap_need_approval=="0"?"Approved at:<br/>".sql2ind($ap_approval_dttm,"date"):"")
                . "</td>"
                
                . "</tr>"
                . "</tbody>"
                . "</table>"
                . "</div>";
      }
      
      
      
      $js = $ajax->getJs()."<script type='text/javascript' src='".XOCP_SERVER_SUBDIR."/include/calendar.js'></script><script type='text/javascript'>//<![CDATA[
      
      
      //////////////////////////////////////////////////////////////////////////////
      
      function do_approval_report(employee_id) {
         $('confirmbtn').innerHTML = '';
         $('confirmbtn').appendChild(progress_span(' ... approve PMS action plan report'));
         orgjx_app_approvalPMSActionPlanReport('$employee_id',function(_data) {
            confirmapapprovalreportbox.fade();
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?goto=y&employee_id=${employee_id}&r='+uniqid('r');
         });
      }
      
      
      function confirm_first_assessor_return_PMSActionPlanReport(employee_id) {
         var return_note = urlencode($('return_note').value);
         orgjx_app_firstAssessorReturnPMSActionPlanReport('$employee_id',return_note,function(_data) {
            var data = recjsarray(_data);
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?goto=y&employee_id=${employee_id}&r='+uniqid('r');
         });
      }
      
      
      var firstassessorreportreturnedit = null;
      var firstassessorreportreturnbox = null;
      function first_assessor_return_PMSActionPlanReport(employee_id) {
         firstassessorreportreturnedit = _dce('div');
         firstassessorreportreturnedit.setAttribute('id','firstassessorreportreturnedit');
         firstassessorreportreturnedit = document.body.appendChild(firstassessorreportreturnedit);
         firstassessorreportreturnedit.sub = firstassessorreportreturnedit.appendChild(_dce('div'));
         firstassessorreportreturnedit.sub.setAttribute('id','innerfirstassessorreportreturnedit');
         firstassessorreportreturnbox = new GlassBox();
         firstassessorreportreturnbox.init('firstassessorreportreturnedit','600px','350px','hidden','default',false,false);
         firstassessorreportreturnbox.lbo(false,0.3);
         firstassessorreportreturnbox.appear();
         
         var xreturn_note = '';
         if($('xreturn_note')) {
            xreturn_note = $('xreturn_note').innerHTML;
         }
         
         $('innerfirstassessorreportreturnedit').innerHTML = '<div style=\"height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;\">Not Approve PMS Action Plan Report Confirmation</div>'
                                   + '<div style=\"background-color:#ffffff;font-weight:bold;font-size:1.1em;height:300px;text-align:center;padding-top:20px;\">'
                                   + 'You do not approve these PMS Actions Plan Report.<br/>You are going to return these PMS Actions Plan Report to the employee.'
                                   + '<br/><br/>With the following notes:<br/>'
                                   + '<textarea id=\"return_note\" style=\"width:400px;height:100px;\">'+xreturn_note+'</textarea>'
                                   + '<br/><br/>Please enter notes, then click Yes to finish. Or click No to cancel.'
                                   + '<br/><br/>'
                                           + '<div id=\"confirmbtn\" style=\"background-color:#eee;padding:10px;text-align:center;\">'
                                   + '<input type=\"button\" value=\"Yes (Not Approve)\" onclick=\"confirm_first_assessor_return_PMSActionPlanReport(\\''+employee_id+'\\');\"/>&nbsp;'
                                   + '<input type=\"button\" value=\"No (Cancel)\" onclick=\"firstassessorreportreturnbox.fade();\"/>'
                                   + '</div>'
                                   + '</div>';
         setTimeout('$(\"return_note\").focus();',200);
      }
      
      
      var confirmapapprovalreport = null;
      var confirmapapprovalreportbox = null;
      function confirm_report_approval(d,e) {
         confirmapapprovalreport = _dce('div');
         confirmapapprovalreport.setAttribute('id','confirmapapprovalreport');
         confirmapapprovalreport = document.body.appendChild(confirmapapprovalreport);
         confirmapapprovalreport.sub = confirmapapprovalreport.appendChild(_dce('div'));
         confirmapapprovalreport.sub.setAttribute('id','innerconfirmapapprovalreport');
         confirmapapprovalreportbox = new GlassBox();
         $('innerconfirmapapprovalreport').innerHTML = '<div style=\"height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;\">Approve PMS Action Plan Report Confirmation</div>'
                                           + '<div id=\"confirmmsg\" style=\"padding:20px;text-align:center;color:#888;\">Are you going to approve this PMS action plan report?</div>'
                                           + '<div id=\"confirmbtn\" style=\"background-color:#eee;padding:10px;text-align:center;\">'
                                             + '<input type=\"button\" value=\"Yes (Approve Report)\" onclick=\"do_approval_report();\"/>&nbsp;&nbsp;'
                                             + '<input type=\"button\" value=\"No (Cancel)\" onclick=\"confirmapapprovalreportbox.fade();\"/>'
                                           + '</div>';
         
         
         confirmapapprovalreportbox = new GlassBox();
         confirmapapprovalreportbox.init('confirmapapprovalreport','500px','165px','hidden','default',false,false);
         confirmapapprovalreportbox.lbo(false,0.3);
         confirmapapprovalreportbox.appear();
      }
      
      //////////////////////////////////////////////////////////////
      
      
      
      function save_pica() {
         ajax_feedback = _caf;
         var pica_id = $('pica_id').value;
         var root_cause = urlencode($('root_cause').value);
         var improvement = urlencode($('target_text').value);
         var selm = $('pica_month_id');
         var pica_month_id = selm.options[selm.selectedIndex].value;
         orgjx_app_savePICA('$employee_id',apreporteditor.pms_objective_id,apreporteditor.actionplan_id,root_cause,improvement,pica_month_id,pica_id,function(_data) {
            //actionplan_updater(_data);
            apreporteditorbox.fade();
            location.reload();
         });
      }
      
      function set_achievement() {
         var achievement = parseFloat($('inp_current_achievement').value);
         var result = urlencode($('inp_final_result').value);
         if(isNaN(achievement)) {
            alert('Target must be a number.');
            _dsa($('inp_current_achievement'));
            return;
         } else {
            $('inp_current_achievement').value = achievement;
         }
         orgjx_app_setAchievement('$employee_id',apreporteditor.pms_objective_id,apreporteditor.actionplan_id,achievement,result,function(_data) {
            $('apreportmsg').oldHTML = $('apreportmsg').innerHTML;
            $('apreporttitle').oldHTML = $('apreporttitle').innerHTML;
            var data = recjsarray(_data);
            if(data[0]==1) {
               $('confirmbtn').style.display = 'none';
               $('savepica').style.display = '';
               $('apreportmsg').innerHTML = data[1];
               $('apreporttitle').innerHTML = 'Edit PICA';
               $('root_cause').focus();
            } else {
               //actionplan_updater(_data);
               apreporteditorbox.fade();
               location.reload();
            }
            
         });
      }
      
      function back_pica() {
         $('confirmbtn').style.display = '';
         $('savepica').style.display = 'none';
         $('apreportmsg').innerHTML = $('apreportmsg').oldHTML;
         $('apreporttitle').innerHTML = $('apreporttitle').oldHTML;
      
      }
      
      var apreporteditor = null;
      var apreporteditorbox = null;
      function edit_report_ap(pms_objective_id,actionplan_id,no,d,e) {
         apreporteditor = _dce('div');
         apreporteditor.setAttribute('id','apreporteditor');
         apreporteditor = document.body.appendChild(apreporteditor);
         apreporteditor.sub = apreporteditor.appendChild(_dce('div'));
         apreporteditor.sub.setAttribute('id','innerapreporteditor');
         apreporteditorbox = new GlassBox();
         $('innerapreporteditor').innerHTML = '<div id=\"apreporttitle\" style=\"height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;\">Enter Action Plan Achievement</div>'
                                           + '<div id=\"apreportmsg\" style=\"padding:20px;text-align:center;min-height:180px;\"></div>'
                                           + '<div id=\"confirmbtn\" style=\"background-color:#eee;padding:10px;text-align:center;\">'
                                             + '<input class=\"xaction\" type=\"button\" value=\"Set Achievement\" onclick=\"set_achievement();\"/>&nbsp;&nbsp;'
                                             + '<input type=\"button\" value=\"Cancel\" onclick=\"apreporteditorbox.fade();\"/>'
                                           + '</div>'
                                           + '<div id=\"savepica\" style=\"display:none;background-color:#eee;padding:10px;text-align:center;\">'
                                             + '<input class=\"xaction\" type=\"button\" value=\"Save PICA\" onclick=\"save_pica();\"/>&nbsp;&nbsp;'
                                             + '<input type=\"button\" value=\"Back\" onclick=\"back_pica();\"/>'
                                           + '</div>';
         
         $('apreportmsg').appendChild(progress_span());
         
         apreporteditorbox = new GlassBox();
         apreporteditorbox.init('apreporteditor','600px','330px','hidden','default',false,false);
         apreporteditorbox.lbo(false,0.3);
         apreporteditorbox.appear();
         
         apreporteditor.pms_objective_id = pms_objective_id;
         apreporteditor.actionplan_id = actionplan_id;
         
         orgjx_app_editAchievement('$employee_id',pms_objective_id,actionplan_id,function(_data) {
            $('apreportmsg').innerHTML = _data;
            $('inp_current_achievement').focus();
         });
      }
      
      function set_month(d,e) {
         var month = d.options[d.selectedIndex].value;
         orgjx_app_setPMSMonth(month,function(_data) {
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?u='+uniqid('a');
         });
      }
      
      function do_submit(employee_id) {
         $('confirmbtn').innerHTML = '';
         $('confirmbtn').appendChild(progress_span(' ... submiting action plan'));
         orgjx_app_submitActionPlan('$employee_id',function(_data) {
            confirmsubmitbox.fade();
            location.reload();
            //location.href = '".XOCP_SERVER_SUBDIR."/index.php?r='+uniqid('r');
         });
      }
      
      var confirmsubmit = null;
      var confirmsubmitbox = null;
      function confirm_submit(d,e) {
         confirmsubmit = _dce('div');
         confirmsubmit.setAttribute('id','confirmsubmit');
         confirmsubmit = document.body.appendChild(confirmsubmit);
         confirmsubmit.sub = confirmsubmit.appendChild(_dce('div'));
         confirmsubmit.sub.setAttribute('id','innerconfirmsubmit');
         confirmsubmitbox = new GlassBox();
         $('innerconfirmsubmit').innerHTML = '<div style=\"height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;\">Submit Action Plan Confirmation</div>'
                                           + '<div id=\"confirmmsg\" style=\"padding:20px;text-align:center;\">Are you going to submit this Action Plan?</div>'
                                           + '<div id=\"confirmbtn\" style=\"background-color:#eee;padding:10px;text-align:center;\">'
                                             + '<input type=\"button\" value=\"Yes (Submit)\" onclick=\"do_submit();\"/>&nbsp;&nbsp;'
                                             + '<input type=\"button\" value=\"No\" onclick=\"confirmsubmitbox.fade();\"/>'
                                           + '</div>';
         
         
         confirmsubmitbox = new GlassBox();
         confirmsubmitbox.init('confirmsubmit','500px','165px','hidden','default',false,false);
         confirmsubmitbox.lbo(false,0.3);
         confirmsubmitbox.appear();
      }
      
      function do_submit_report(employee_id) {
         $('confirmbtn').innerHTML = '';
         $('confirmbtn').appendChild(progress_span(' ... submiting report'));
         orgjx_app_submitActionPlanReport('$employee_id',function(_data) {
            confirmsubmitreportbox.fade();
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?r='+uniqid('r');
         });
      }
      
      var confirmsubmitreport = null;
      var confirmsubmitreportbox = null;
      function confirm_submit_apreport(d,e) {
         confirmsubmitreport = _dce('div');
         confirmsubmitreport.setAttribute('id','confirmsubmitreport');
         confirmsubmitreport = document.body.appendChild(confirmsubmitreport);
         confirmsubmitreport.sub = confirmsubmitreport.appendChild(_dce('div'));
         confirmsubmitreport.sub.setAttribute('id','innerconfirmsubmitreport');
         confirmsubmitreportbox = new GlassBox();
         $('innerconfirmsubmitreport').innerHTML = '<div style=\"height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;\">Submit Action Plan Report Confirmation</div>'
                                           + '<div id=\"confirmmsg\" style=\"padding:20px;text-align:center;\">Are you going to submit this Action Plan Report?</div>'
                                           + '<div id=\"confirmbtn\" style=\"background-color:#eee;padding:10px;text-align:center;\">'
                                             + '<input type=\"button\" value=\"Yes (Submit Report)\" onclick=\"do_submit_report();\"/>&nbsp;&nbsp;'
                                             + '<input type=\"button\" value=\"No\" onclick=\"confirmsubmitreportbox.fade();\"/>'
                                           + '</div>';
         
         
         confirmsubmitreportbox = new GlassBox();
         confirmsubmitreportbox.init('confirmsubmitreport','500px','165px','hidden','default',false,false);
         confirmsubmitreportbox.lbo(false,0.3);
         confirmsubmitreportbox.appear();
      }
      
      
      
      var dvtooltip = null;
      function show_ap_tooltip(pms_objective_id,actionplan_id,d,e) {
         if(actionplan_id==0) return;
         if(dvapeditor&&dvapeditor.style.visibility=='visible') {
            return;
         }
         if(!dvtooltip) {
            dvtooltip = _dce('div');
            dvtooltip.setAttribute('style','visibility:hidden;position:absolute;padding:5px;-moz-border-radius:5px;border:1px solid #bbb;background-color:#ffffdd;left:0px;-moz-box-shadow:-1px -1px 1px #00f;-moz-box-shadow:1px 1px 3px #000;');
            dvtooltip = document.body.appendChild(dvtooltip);
            dvtooltip.style.left = '-1000px';
            dvtooltip.style.top = '-1000px';
            dvtooltip.arrow = _dce('img');
            dvtooltip.arrow.setAttribute('style','position:absolute;left:0px;');
            dvtooltip.arrow.src = '".XOCP_SERVER_SUBDIR."/images/leftmiddle.png';
            dvtooltip.arrow = dvtooltip.appendChild(dvtooltip.arrow);
            dvtooltip.arrow.style.top = '3px';
            dvtooltip.arrow.style.left = '-12px';
            dvtooltip.inner = dvtooltip.appendChild(_dce('div'));
         }
         var xtooltip = $('intooltip_'+pms_objective_id+'_'+actionplan_id);
         if(xtooltip) {
            dvtooltip.innerHTML = xtooltip.innerHTML;
            if(e.pageX>660) {
               dvtooltip.style.left = parseInt(e.pageX-dvtooltip.offsetWidth)+'px';
               dvtooltip.style.top = parseInt(e.pageY+3)+'px';
            } else {
               dvtooltip.style.left = parseInt(e.pageX+3)+'px';
               dvtooltip.style.top = parseInt(e.pageY+3)+'px';
            }
            dvtooltip.style.visibility = 'visible';
         }
      }
      
      function hide_ap_tooltip(pms_objective_id,actionplan_id,d,e) {
         dvtooltip.style.left = '-1000px';
         dvtooltip.style.top = '-1000px';
         dvtooltip.style.visibility = 'hidden';
      }
      
      function mouseover_aptextpica(no,pms_objective_id,actionplan_id,d,e) {
         //show_ap_tooltip(pms_objective_id,actionplan_id,d,e);
         for(var i=1;i<=12;i++) {
            var td = $('tdap_'+pms_objective_id+'_'+i);
            if(td&&td.childNodes&&td.childNodes[no]) {
               td.childNodes[no].style.backgroundColor = '#fcc';
            }
            td = $('tdpica_root_'+pms_objective_id+'_'+i);
            if(td&&td.childNodes&&td.childNodes[no]) {
               td.childNodes[no].style.backgroundColor = '#fcc';
            }
            td = $('tdpica_improve_'+pms_objective_id+'_'+i);
            if(td&&td.childNodes&&td.childNodes[no]) {
               td.childNodes[no].style.backgroundColor = '#fcc';
            }
            td = $('tdpica_month_'+pms_objective_id+'_'+i);
            if(td&&td.childNodes&&td.childNodes[no]) {
               td.childNodes[no].style.backgroundColor = '#fcc';
            }
         }
         var tdtg = $('tdtg_'+pms_objective_id);
         if(tdtg&&tdtg.childNodes&&tdtg.childNodes[no]) {
            tdtg.childNodes[no].style.backgroundColor = '#fcc';
         }
         var tdap = $('tdaptext_'+pms_objective_id);
         if(tdap&&tdap.childNodes&&tdap.childNodes[no]) {
            tdap.childNodes[no].style.backgroundColor = '#fcc';
         }
      }
      
      function mouseout_aptextpica(no,pms_objective_id,actionplan_id,d,e) {
         if(dvtooltip) {
            hide_ap_tooltip(pms_objective_id,actionplan_id,d,e);
         }
         
         if(dvapeditor&&dvapeditor.style.visibility=='visible') {
            if(dvapeditor.no==no&&dvapeditor.pms_objective_id==pms_objective_id) return;
         }
         for(var i=1;i<=12;i++) {
            var td = $('tdap_'+pms_objective_id+'_'+i);
            if(td&&td.childNodes&&td.childNodes[no]) {
               td.childNodes[no].style.backgroundColor = '#fc9';
            }
            td = $('tdpica_root_'+pms_objective_id+'_'+i);
            if(td&&td.childNodes&&td.childNodes[no]) {
               td.childNodes[no].style.backgroundColor = '#fc9';
            }
            td = $('tdpica_improve_'+pms_objective_id+'_'+i);
            if(td&&td.childNodes&&td.childNodes[no]) {
               td.childNodes[no].style.backgroundColor = '#fc9';
            }
            td = $('tdpica_month_'+pms_objective_id+'_'+i);
            if(td&&td.childNodes&&td.childNodes[no]) {
               td.childNodes[no].style.backgroundColor = '#fc9';
            }
         }
         var tdtg = $('tdtg_'+pms_objective_id);
         if(tdtg&&tdtg.childNodes&&tdtg.childNodes[no]) {
            tdtg.childNodes[no].style.backgroundColor = '#fc9';
         }
         var tdap = $('tdaptext_'+pms_objective_id);
         if(tdap&&tdap.childNodes&&tdap.childNodes[no]) {
            tdap.childNodes[no].style.backgroundColor = '#fc9';
         }
      }
      
      
      
      function mouseover_aptext(no,pms_objective_id,actionplan_id,d,e) {
         //show_ap_tooltip(pms_objective_id,actionplan_id,d,e);
         for(var i=1;i<=12;i++) {
            var td = $('tdap_'+pms_objective_id+'_'+i);
            if(td&&td.childNodes&&td.childNodes[no]) {
               td.childNodes[no].style.backgroundColor = '#eee';
            }
            td = $('tdpica_root_'+pms_objective_id+'_'+i);
            if(td&&td.childNodes&&td.childNodes[no]) {
               td.childNodes[no].style.backgroundColor = '#eee';
            }
            td = $('tdpica_improve_'+pms_objective_id+'_'+i);
            if(td&&td.childNodes&&td.childNodes[no]) {
               td.childNodes[no].style.backgroundColor = '#eee';
            }
            td = $('tdpica_month_'+pms_objective_id+'_'+i);
            if(td&&td.childNodes&&td.childNodes[no]) {
               td.childNodes[no].style.backgroundColor = '#eee';
            }
         }
         var tdtg = $('tdtg_'+pms_objective_id);
         if(tdtg&&tdtg.childNodes&&tdtg.childNodes[no]) {
            tdtg.childNodes[no].style.backgroundColor = '#eee';
         }
         var tdap = $('tdaptext_'+pms_objective_id);
         if(tdap&&tdap.childNodes&&tdap.childNodes[no]) {
            tdap.childNodes[no].style.backgroundColor = '#eee';
         }
         var tdytd = $('tdytd_'+pms_objective_id);
         if(tdytd&&tdytd.childNodes&&tdytd.childNodes[no]) {
            tdytd.childNodes[no].style.backgroundColor = '#eee';
         }
      }
      
      function mouseout_aptext(no,pms_objective_id,actionplan_id,d,e) {
         if(dvtooltip) {
            hide_ap_tooltip(pms_objective_id,actionplan_id,d,e);
         }
         
         if(dvapeditor&&dvapeditor.style.visibility=='visible') {
            if(dvapeditor.no==no&&dvapeditor.pms_objective_id==pms_objective_id) return;
         }
         for(var i=1;i<=12;i++) {
            var td = $('tdap_'+pms_objective_id+'_'+i);
            if(td&&td.childNodes&&td.childNodes[no]) {
               td.childNodes[no].style.backgroundColor = 'transparent';
            }
            td = $('tdpica_root_'+pms_objective_id+'_'+i);
            if(td&&td.childNodes&&td.childNodes[no]) {
               td.childNodes[no].style.backgroundColor = 'transparent';
            }
            td = $('tdpica_improve_'+pms_objective_id+'_'+i);
            if(td&&td.childNodes&&td.childNodes[no]) {
               td.childNodes[no].style.backgroundColor = 'transparent';
            }
            td = $('tdpica_month_'+pms_objective_id+'_'+i);
            if(td&&td.childNodes&&td.childNodes[no]) {
               td.childNodes[no].style.backgroundColor = 'transparent';
            }
         }
         var tdtg = $('tdtg_'+pms_objective_id);
         if(tdtg&&tdtg.childNodes&&tdtg.childNodes[no]) {
            tdtg.childNodes[no].style.backgroundColor = 'transparent';
         }
         var tdap = $('tdaptext_'+pms_objective_id);
         if(tdap&&tdap.childNodes&&tdap.childNodes[no]) {
            tdap.childNodes[no].style.backgroundColor = 'transparent';
         }
         var tdytd = $('tdytd_'+pms_objective_id);
         if(tdytd&&tdytd.childNodes&&tdytd.childNodes[no]) {
            tdytd.childNodes[no].style.backgroundColor = 'transparent';
         }
      }
      
      function _changedatetime_callback(span_txt_id,result,visibility) {
         if(span_txt_id=='spdtach') {
            orgjx_app_setCurrentStatusDate(result,function(_data) {
               var d = $('spdtach');
               if(d) {
                  if(d.obdt.style.visibility=='hidden') {
                     location.reload();
                  }
               }
            });
         }
      }
      
      //////////////////////////////////////////////////////////////////////////////
      function save_ap() {
         save_actionplan(dvmonthaction.pms_objective_id,dvmonthaction.actionplan_id);
         _destroy(dvmonthaction);
         dvmonthaction.actionplan_id = null;
         dvmonthaction.pms_objective_id = null;
         dvmonthaction.d = null;
         dvmonthaction.month_id = null;
         dvmonthaction = null;
         return;
      }
      
      function actionplan_updater(_data) {
         var data = recjsarray(_data);
         $('tdaptext_'+data[0]).innerHTML = data[1];
         $('tdtg_'+data[0]).innerHTML = data[2];
         if($('tdap_'+data[0]+'_3')) {
            $('tdap_'+data[0]+'_1').innerHTML = data[3];
            $('tdap_'+data[0]+'_2').innerHTML = data[4];
            $('tdap_'+data[0]+'_3').innerHTML = data[5];
            $('tdap_'+data[0]+'_4').innerHTML = data[6];
            $('tdap_'+data[0]+'_5').innerHTML = data[7];
            $('tdap_'+data[0]+'_6').innerHTML = data[8];
            $('tdap_'+data[0]+'_7').innerHTML = data[9];
            $('tdap_'+data[0]+'_8').innerHTML = data[10];
            $('tdap_'+data[0]+'_9').innerHTML = data[11];
            $('tdap_'+data[0]+'_10').innerHTML = data[12];
            $('tdap_'+data[0]+'_11').innerHTML = data[13];
            $('tdap_'+data[0]+'_12').innerHTML = data[14];
            if(data[19]>0) {
               ".($self_job_id==$job_id?"$('submited_by_button').innerHTML = '<input type=\"button\" value=\"Submit\" onclick=\"confirm_submit(this,event);\"/>';":"")."
            } else {
               ".($self_job_id==$job_id?"$('submited_by_button').innerHTML = 'Submited at:<br/>".sql2ind($apreport_submit_dttm,"date")."';":"")."
            }
         } else if($('tdpica_root_'+data[0]+'_${current_month}')) {
            $('tdpica_root_'+data[0]+'_${current_month}').innerHTML = data[16];
            $('tdpica_improve_'+data[0]+'_${current_month}').innerHTML = data[17];
            $('tdpica_month_'+data[0]+'_${current_month}').innerHTML = data[18];
         }
         $('so_ap_tooltip_'+data[0]).innerHTML = data[15];
      }
      
      function delete_actionplan() {
         orgjx_app_deleteActionPlan('$employee_id',dvapeditor.pms_objective_id,dvapeditor.actionplan_id,function(_data) {
            actionplan_updater(_data);
            dvapeditor.actionplan_id = 0;
            dvapeditor.pms_objective_id = 0;
            dvapeditor.style.visibility = 'hidden';
            dvapeditor.style.left = '-1000px';
            dvapeditor.style.top = '-1000px';
         });
      }
      
      function chgselmonth(d,e) {
         var rt = $('repeat_type');
         if(rt.options[rt.selectedIndex].value==0) {
            $('selmonth2').disabled = true;
            $('selmonth2').setAttribute('disabled','1');
            $('selmonth2').selectedIndex = $('selmonth').selectedIndex;
         }
      }
      
      function chgselrepeat(d,e) {
         var repeat_type = d.options[d.selectedIndex].value;
         $('sp_carry_over').style.color = 'blue';
         if(repeat_type==0) {
            $('selmonth2').disabled = true;
            $('selmonth2').setAttribute('disabled','1');
            $('selmonth2').selectedIndex = $('selmonth').selectedIndex;
            $('month_range').style.display = '';
            $('choose_month').style.display = 'none';
            $('sp_carry_over').innerHTML = 'Yes';
            $('allow_carry_over').value = '1';
         } else if(repeat_type==1) {
            $('selmonth2').disabled = false;
            $('month_range').style.display = '';
            $('choose_month').style.display = 'none';
            $('sp_carry_over').innerHTML = 'No';
            $('allow_carry_over').value = '0';
         } else {
            $('month_range').style.display = 'none';
            $('choose_month').style.display = '';
            $('sp_carry_over').innerHTML = 'No';
            $('allow_carry_over').value = '0';
         }
      }
      
      function save_actionplan() {
         var selmonth = $('selmonth');
         var month_id = selmonth.options[selmonth.selectedIndex].value;
         var selmonth2 = $('selmonth2');
         var month_id2 = selmonth2.options[selmonth2.selectedIndex].value;
         var actionplan_text = urlencode($('inp_aptext').value);
         var target_text = urlencode($('inp_tgtext').value);
         var carry_over = 1;
         var selrepeat = $('repeat_type');
         var repeat_type = selrepeat.options[selrepeat.selectedIndex].value;
         if(!$('allow_carry_over').checked) {
            carry_over = 0;
         }
         var choose_month = _parseForm('choose_month');
         orgjx_app_saveActionPlan('$employee_id',actionplan_text,target_text,dvapeditor.actionplan_id,dvapeditor.pms_objective_id,month_id,month_id2,carry_over,repeat_type,choose_month,function(_data) {
            actionplan_updater(_data);
            
            dvapeditor.actionplan_id = 0;
            dvapeditor.pms_objective_id = 0;
            dvapeditor.style.visibility = 'hidden';
            dvapeditor.style.left = '-1000px';
            dvapeditor.style.top = '-1000px';
            
            ".($self_job_id==$job_id?"$('submited_by_button').innerHTML = '<input type=\"button\" value=\"Submit\" onclick=\"confirm_submit(this,event);\"/>';":"")."
            return;
            
         });
      }
      
      function kp_actionplan(d,e) {
         var k = getkeyc(e);
         if(d.chgt) {
            d.chgt.reset();
            d.chgt = null;
         }
         if(k==13) {
            save_ap();
         } else if(k==27) {
            _destroy(dvmonthaction);
            dvmonthaction.actionplan_id = null;
            dvmonthaction.pms_objective_id = null;
            dvmonthaction.d = null;
            dvmonthaction = null;
         } else {
            d.chgt = new ctimer('save_actionplan();',100);
            d.chgt.start();
         }
      }
      
      function do_stop_actionplan() {
         orgjx_app_stopActionPlan('$employee_id',dvapeditor.pms_objective_id,dvapeditor.actionplan_id,function(_data) {
            actionplan_updater(_data);
            dvapeditor.actionplan_id = 0;
            dvapeditor.pms_objective_id = 0;
            dvapeditor.style.visibility = 'hidden';
            dvapeditor.style.left = '-1000px';
            dvapeditor.style.top = '-1000px';
         });
      }
      
      function cancel_stop_actionplan() {
         dvapeditor.inner.innerHTML = dvapeditor.oldHTML;
      }
      
      function stop_actionplan() {
         dvapeditor.oldHTML = dvapeditor.inner.innerHTML;
         dvapeditor.inner.innerHTML = '<div style=\"text-align:center;padding:5px;\">Are you sure you want to stop this action plan?<br/><br/>'
                              + '<input style=\"width:80px;\" type=\"button\" value=\"Yes (stop)\" onclick=\"do_stop_actionplan();\"/>&nbsp;'
                              + '<input style=\"width:80px;\" type=\"button\" value=\"No\" onclick=\"cancel_stop_actionplan();\"/></div>';
      }
      
      function close_actionplan() {
         if(dvapeditor.actionplan_id=='new') {
            _destroy($('dvap_'+dvapeditor.pms_objective_id+'_new'));
         }
         if($('dvap_'+dvapeditor.pms_objective_id+'_'+dvapeditor.actionplan_id)) {
            $('dvap_'+dvapeditor.pms_objective_id+'_'+dvapeditor.actionplan_id).style.borderTop = '1px solid transparent';
            $('dvtg_'+dvapeditor.pms_objective_id+'_'+dvapeditor.actionplan_id).style.borderTop = '1px solid transparent';
            $('dvap_'+dvapeditor.pms_objective_id+'_'+dvapeditor.actionplan_id).style.borderBottom = '1px solid transparent';
            $('dvtg_'+dvapeditor.pms_objective_id+'_'+dvapeditor.actionplan_id).style.borderBottom = '1px solid transparent';
         }
         var no = dvapeditor.no;
         for(var i=1;i<=12;i++) {
            var td = $('tdap_'+dvapeditor.pms_objective_id+'_'+i);
            if(td&&td.childNodes&&td.childNodes[no]) {
               td.childNodes[no].style.borderTop = '1px solid transparent';
               td.childNodes[no].style.borderBottom = '1px solid transparent';
            }
         }
         dvapeditor.no = null;
         mouseout_aptext(no,dvapeditor.pms_objective_id,null,null);
         
         dvapeditor.actionplan_id = 0;
         dvapeditor.pms_objective_id = 0;
         dvapeditor.style.visibility = 'hidden';
         dvapeditor.style.left = '-1000px';
         dvapeditor.style.top = '-1000px';
      }
      
      function new_actionplan(pms_objective_id,d,e) {
         _destroy($('new_ap'));
         var dv = _dce('div');
         var td = $('tdaptext_'+pms_objective_id);
         dv = td.insertBefore(dv,$('dvaddap_'+pms_objective_id));
         dv.setAttribute('id','dvap_'+pms_objective_id+'_new');
         var no = td.childNodes.length - 1;
         dv.className = 'aptext';
         dv.innerHTML = '<span id=\"spnew_ap\" class=\"xlnk\">"._EMPTY."</span>';
         edit_actionplan('new',pms_objective_id,$('spnew_ap'),e);
      }
      
      var dvapeditor = null;
      function edit_actionplan(actionplan_id,pms_objective_id,d,e) {
         if(dvtooltip) {
            hide_ap_tooltip(pms_objective_id,actionplan_id,d,e);
         }
         var no = 0;
         if(!dvapeditor) {
            dvapeditor = _dce('div');
            dvapeditor.setAttribute('style','visibility:hidden;position:absolute;padding:5px;-moz-border-radius:5px;border:1px solid #777;background-color:#ffffff;left:0px;-moz-box-shadow:1px 1px 3px #000;');
            dvapeditor = document.body.appendChild(dvapeditor);
            dvapeditor.style.left = '-1000px';
            dvapeditor.style.top = '-1000px';
            dvapeditor.arrow = _dce('img');
            dvapeditor.arrow.setAttribute('style','position:absolute;left:0px;');
            dvapeditor.arrow.src = '".XOCP_SERVER_SUBDIR."/images/leftmiddle.png';
            dvapeditor.arrow = dvapeditor.appendChild(dvapeditor.arrow);
            dvapeditor.arrow.style.top = '3px';
            dvapeditor.arrow.style.left = '-12px';
            dvapeditor.inner = dvapeditor.appendChild(_dce('div'));
         }
         
         if(dvapeditor.actionplan_id&&dvapeditor.actionplan_id==actionplan_id&&dvapeditor.pms_objective_id&&dvapeditor.pms_objective_id==pms_objective_id) {
            no = dvapeditor.no;
            
            /// hide border
            $('dvap_'+pms_objective_id+'_'+actionplan_id).style.borderTop = '1px solid transparent';
            $('dvap_'+pms_objective_id+'_'+actionplan_id).style.borderBottom = '1px solid transparent';
            for(var i=1;i<=12;i++) {
               var td = $('tdap_'+dvapeditor.pms_objective_id+'_'+i);
               if(td&&td.childNodes&&td.childNodes[no]) {
                  td.childNodes[no].style.borderTop = '1px solid transparent';
                  td.childNodes[no].style.borderBottom = '1px solid transparent';
               }
            }
            var tdtg = $('tdtg_'+dvapeditor.pms_objective_id);
            if(tdtg&&tdtg.childNodes&&tdtg.childNodes[no]) {
               tdtg.childNodes[no].style.borderTop = '1px solid transparent';
               tdtg.childNodes[no].style.borderBottom = '1px solid transparent';
            }
            
            dvapeditor.actionplan_id = 0;
            dvapeditor.pms_objective_id = 0;
            dvapeditor.style.visibility = 'hidden';
            dvapeditor.style.left = '-1000px';
            dvapeditor.style.top = '-1000px';
            
            
            return;
         }
         
         if(dvapeditor.actionplan_id&&dvapeditor.pms_objective_id) {
            no = dvapeditor.no;
            
            /// hide border
            $('dvap_'+dvapeditor.pms_objective_id+'_'+dvapeditor.actionplan_id).style.borderTop = '1px solid transparent';
            $('dvap_'+dvapeditor.pms_objective_id+'_'+dvapeditor.actionplan_id).style.borderBottom = '1px solid transparent';
            for(var i=1;i<=12;i++) {
               var td = $('tdap_'+dvapeditor.pms_objective_id+'_'+i);
               if(td&&td.childNodes&&td.childNodes[no]) {
                  td.childNodes[no].style.borderTop = '1px solid transparent';
                  td.childNodes[no].style.borderBottom = '1px solid transparent';
               }
            }
            var tdtg = $('tdtg_'+dvapeditor.pms_objective_id);
            if(tdtg&&tdtg.childNodes&&tdtg.childNodes[no]) {
               tdtg.childNodes[no].style.borderTop = '1px solid transparent';
               tdtg.childNodes[no].style.borderBottom = '1px solid transparent';
            }
            dvapeditor.no = null;
            mouseout_aptext(no,dvapeditor.pms_objective_id,null,null);
            
         }
         
         for(var i=0;i<$('tdaptext_'+pms_objective_id).childNodes.length;i++) {
            if($('tdaptext_'+pms_objective_id).childNodes[i].id=='dvap_'+pms_objective_id+'_'+actionplan_id) {
               no=i;
            }
         }
         
         dvapeditor.inner.innerHTML = '';
         dvapeditor.actionplan_id = actionplan_id;
         dvapeditor.pms_objective_id = pms_objective_id;
         dvapeditor.style.left = parseInt(oX(d)+d.parentNode.parentNode.offsetWidth+5)+'px';
         dvapeditor.style.top = parseInt(oY(d)-3)+'px';
         dvapeditor.style.visibility = 'visible';
         dvapeditor.inner.appendChild(progress_span());
         dvapeditor.d = d;
         dvapeditor.no = no;
         
         /// expose border
         $('dvap_'+pms_objective_id+'_'+actionplan_id).style.borderTop = '1px solid #888';
         $('dvap_'+pms_objective_id+'_'+actionplan_id).style.borderBottom = '1px solid #888';
         for(var i=1;i<=12;i++) {
            var td = $('tdap_'+pms_objective_id+'_'+i);
            if(td&&td.childNodes&&td.childNodes[no]) {
               td.childNodes[no].style.borderTop = '1px solid #888';
               td.childNodes[no].style.borderBottom = '1px solid #888';
            }
         }
         var tdtg = $('tdtg_'+pms_objective_id);
         if(tdtg&&tdtg.childNodes&&tdtg.childNodes[no]) {
            tdtg.childNodes[no].style.borderTop = '1px solid #888';
            tdtg.childNodes[no].style.borderBottom = '1px solid #888';
         }
         
         
         orgjx_app_editActionPlan('$employee_id',pms_objective_id,actionplan_id,function(_data) {
            dvapeditor.inner.innerHTML = _data;
            setTimeout('$(\"inp_aptext\").focus();',100);
         });
      }
      
      var dvmonthaction = null;
      function old_edit_actionplan(actionplan_id,month_id,pms_objective_id,d,e) {
         _destroy(dvmonthaction);
         if(dvmonthaction&&actionplan_id==dvmonthaction.actionplan_id&&pms_objective_id==dvmonthaction.pms_objective_id&&dvmonthaction.month_id==month_id) {
            dvmonthaction.actionplan_id = null;
            dvmonthaction.pms_objective_id = null;
            dvmonthaction.d = null;
            dvmonthaction.month_id = null;
            dvmonthaction = null;
            return;
         }
         d.dv = _dce('div');
         d.dv.setAttribute('style','position:absolute;padding:5px;-moz-border-radius:5px;border:1px solid #777;background-color:#ffffcc;left:0px;-moz-box-shadow:1px 1px 3px #000;');
         var ap_text = '';
         var tg_text = '';
         if(actionplan_id!='new') {
            ap_text = d.innerHTML;
            tg_text = $('sptg_'+pms_objective_id+'_'+actionplan_id).innerHTML;
            /// tg_text = $('target_'+pms_objective_id+'_'+actionplan_id).innerHTML;
            if(ap_text=='"._EMPTY."') {
               ap_text = '';
            }
            if(tg_text=='"._EMPTY."') {
               tg_text = '';
            }
         }
         d.dv.innerHTML = '<div style=\"text-align:left;padding:2px;\">Action Plan:<br/>'
                        + '<input type=\"text\" onkeyup=\"kp_actionplan(this,event);\" id=\"inp_actionplan\" style=\"width:350px;\" value=\"'+ap_text+'\"/><br/>'
                        + 'Target:<br/>'
                        + '<input type=\"text\" onkeyup=\"kp_actionplan(this,event);\" id=\"inp_target\" style=\"width:350px;\" value=\"'+tg_text+'\"/><br/>'
                        + '<div style=\"padding:2px;text-align:right;\">'
                        + '<input type=\"button\" value=\""._SAVE."\" onclick=\"save_ap();\"/>'
                        + '</div>'
                        + '</div>';
         d.dv = d.parentNode.parentNode.parentNode.appendChild(d.dv);
         d.dv.style.top = parseInt(oY(d.parentNode)+d.parentNode.offsetHeight+10)+'px';
         var x = oX(d);
         d.dv.style.left = parseInt(oX(d)-(d.dv.offsetWidth/2)+(d.offsetWidth/2))+'px';
         d.dv.arrow = _dce('img');
         d.dv.arrow.setAttribute('style','position:absolute;left:0px;');
         d.dv.arrow.src = '".XOCP_SERVER_SUBDIR."/images/topmiddle.png';
         d.dv.arrow = d.dv.appendChild(d.dv.arrow);
         d.dv.arrow.style.top = '-12px';
         d.dv.arrow.style.left = parseInt(d.dv.offsetWidth-(d.dv.offsetWidth/2)-7)+'px';
         $('inp_actionplan').focus();
         dvmonthaction = d.dv;
         dvmonthaction.d = d;
         dvmonthaction.pms_objective_id = pms_objective_id;
         dvmonthaction.actionplan_id = actionplan_id;
         dvmonthaction.month_id = month_id;
      
      }
      
      function do_approval2(employee_id) {
         $('confirmbtn').innerHTML = '';
         $('confirmbtn').appendChild(progress_span(' ... approve JAM'));
         orgjx_app_approval2JAM('$employee_id',function(_data) {
            confirmapproval2box.fade();
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?goto=y&employee_id=${employee_id}&r='+uniqid('r');
         });
      }
      
      
      function confirm_next_assessor_return_JAM(employee_id) {
         var return_note = urlencode($('return_note').value);
         orgjx_app_nextAssessorReturnJAM(employee_id,return_note,function(_data) {
            var data = recjsarray(_data);
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?goto=y&employee_id=${employee_id}&r='+uniqid('r');
         });
      }
      
      
      var nextassessorreturnedit = null;
      var nextassessorreturnbox = null;
      function next_assessor_return_JAM(employee_id) {
         nextassessorreturnedit = _dce('div');
         nextassessorreturnedit.setAttribute('id','nextassessorreturnedit');
         nextassessorreturnedit = document.body.appendChild(nextassessorreturnedit);
         nextassessorreturnedit.sub = nextassessorreturnedit.appendChild(_dce('div'));
         nextassessorreturnedit.sub.setAttribute('id','innernextassessorreturnedit');
         nextassessorreturnbox = new GlassBox();
         nextassessorreturnbox.init('nextassessorreturnedit','600px','350px','hidden','default',false,false);
         nextassessorreturnbox.lbo(false,0.3);
         nextassessorreturnbox.appear();
         
         var xreturn_note = '';
         if($('xreturn_note')) {
            xreturn_note = $('xreturn_note').innerHTML;
         }
         
         $('innernextassessorreturnedit').innerHTML = '<div style=\"height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;\">Not Approve JAM Confirmation</div>'
                                   + '<div style=\"background-color:#ffffff;font-weight:bold;font-size:1.1em;height:300px;text-align:center;padding-top:20px;\">'
                                   + 'You do not approve this JAM.<br/>You are going to return this JAM to the employee.'
                                   + '<br/><br/>With the following notes:<br/>'
                                   + '<textarea id=\"return_note\" style=\"width:400px;height:100px;\">'+xreturn_note+'</textarea>'
                                   + '<br/><br/>Are you sure?'
                                   + '<br/><br/>'
                                           + '<div id=\"confirmbtn\" style=\"background-color:#eee;padding:10px;text-align:center;\">'
                                   + '<input type=\"button\" value=\"Yes (Not Approve)\" onclick=\"confirm_next_assessor_return_JAM(\\''+employee_id+'\\');\"/>&nbsp;'
                                   + '<input type=\"button\" value=\"No (Cancel)\" onclick=\"nextassessorreturnbox.fade();\"/>'
                                   + '</div>'
                                   + '</div>';
         setTimeout('$(\"return_note\").focus();',200);
      }
      
      
      var confirmapproval2 = null;
      var confirmapproval2box = null;
      function confirm_approval2(d,e) {
         confirmapproval2 = _dce('div');
         confirmapproval2.setAttribute('id','confirmapproval2');
         confirmapproval2 = document.body.appendChild(confirmapproval2);
         confirmapproval2.sub = confirmapproval2.appendChild(_dce('div'));
         confirmapproval2.sub.setAttribute('id','innerconfirmapproval2');
         confirmapproval2box = new GlassBox();
         $('innerconfirmapproval2').innerHTML = '<div style=\"height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;\">Approve JAM Confirmation</div>'
                                           + '<div id=\"confirmmsg\" style=\"padding:20px;text-align:center;\">Are you going to approve this JAM?</div>'
                                           + '<div id=\"confirmbtn\" style=\"background-color:#eee;padding:10px;text-align:center;\">'
                                             + '<input type=\"button\" value=\"Yes (Approve)\" onclick=\"do_approval2();\"/>&nbsp;&nbsp;'
                                             + '<input type=\"button\" value=\"No (Cancel)\" onclick=\"confirmapproval2box.fade();\"/>'
                                           + '</div>';
         
         
         confirmapproval2box = new GlassBox();
         confirmapproval2box.init('confirmapproval2','500px','165px','hidden','default',false,false);
         confirmapproval2box.lbo(false,0.3);
         confirmapproval2box.appear();
      }
      
      //////////////////////////////////////////////////////////////
      
      
      //////////////////////////////////////////////////////////////////////////////
      
      function do_approval1(employee_id) {
         $('confirmbtn').innerHTML = '';
         $('confirmbtn').appendChild(progress_span(' ... approve PMS action plan'));
         orgjx_app_approval1PMSActionPlan('$employee_id',function(_data) {
            confirmapproval1box.fade();
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?goto=y&employee_id=${employee_id}&r='+uniqid('r');
         });
      }
      
      
      function confirm_first_assessor_return_PMSActionPlan(employee_id) {
         var return_note = urlencode($('return_note').value);
         orgjx_app_firstAssessorReturnPMSActionPlan(employee_id,return_note,function(_data) {
            var data = recjsarray(_data);
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?goto=y&employee_id=${employee_id}&r='+uniqid('r');
         });
      }
      
      
      var firstassessorreturnedit = null;
      var firstassessorreturnbox = null;
      function first_assessor_return_PMSActionPlan(employee_id) {
         firstassessorreturnedit = _dce('div');
         firstassessorreturnedit.setAttribute('id','firstassessorreturnedit');
         firstassessorreturnedit = document.body.appendChild(firstassessorreturnedit);
         firstassessorreturnedit.sub = firstassessorreturnedit.appendChild(_dce('div'));
         firstassessorreturnedit.sub.setAttribute('id','innerfirstassessorreturnedit');
         firstassessorreturnbox = new GlassBox();
         firstassessorreturnbox.init('firstassessorreturnedit','600px','350px','hidden','default',false,false);
         firstassessorreturnbox.lbo(false,0.3);
         firstassessorreturnbox.appear();
         
         var xreturn_note = '';
         if($('xreturn_note')) {
            xreturn_note = $('xreturn_note').innerHTML;
         }
         
         $('innerfirstassessorreturnedit').innerHTML = '<div style=\"height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;\">Not Approve PMS Action Plan Confirmation</div>'
                                   + '<div style=\"background-color:#ffffff;font-weight:bold;font-size:1.1em;height:300px;text-align:center;padding-top:20px;\">'
                                   + 'You do not approve these PMS Actions Plan.<br/>You are going to return these PMS Actions Plan to the employee.'
                                   + '<br/><br/>With the following notes:<br/>'
                                   + '<textarea id=\"return_note\" style=\"width:400px;height:100px;\">'+xreturn_note+'</textarea>'
                                   + '<br/><br/>Please enter notes, then click Yes to finish. Or click No to cancel.'
                                   + '<br/><br/>'
                                           + '<div id=\"confirmbtn\" style=\"background-color:#eee;padding:10px;text-align:center;\">'
                                   + '<input type=\"button\" value=\"Yes (Not Approve)\" onclick=\"confirm_first_assessor_return_PMSActionPlan(\\''+employee_id+'\\');\"/>&nbsp;'
                                   + '<input type=\"button\" value=\"No (Cancel)\" onclick=\"firstassessorreturnbox.fade();\"/>'
                                   + '</div>'
                                   + '</div>';
         setTimeout('$(\"return_note\").focus();',200);
      }
      
      
      var confirmapproval1 = null;
      var confirmapproval1box = null;
      function confirm_approval1(d,e) {
         confirmapproval1 = _dce('div');
         confirmapproval1.setAttribute('id','confirmapproval1');
         confirmapproval1 = document.body.appendChild(confirmapproval1);
         confirmapproval1.sub = confirmapproval1.appendChild(_dce('div'));
         confirmapproval1.sub.setAttribute('id','innerconfirmapproval1');
         confirmapproval1box = new GlassBox();
         $('innerconfirmapproval1').innerHTML = '<div style=\"height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;\">Approve PMS Action Plan Confirmation</div>'
                                           + '<div id=\"confirmmsg\" style=\"padding:20px;text-align:center;color:#888;\">Are you going to approve this PMS action plan?</div>'
                                           + '<div id=\"confirmbtn\" style=\"background-color:#eee;padding:10px;text-align:center;\">'
                                             + '<input type=\"button\" value=\"Yes (Approve)\" onclick=\"do_approval1();\"/>&nbsp;&nbsp;'
                                             + '<input type=\"button\" value=\"No (Cancel)\" onclick=\"confirmapproval1box.fade();\"/>'
                                           + '</div>';
         
         
         confirmapproval1box = new GlassBox();
         confirmapproval1box.init('confirmapproval1','500px','165px','hidden','default',false,false);
         confirmapproval1box.lbo(false,0.3);
         confirmapproval1box.appear();
      }
      
      //////////////////////////////////////////////////////////////
      
      function do_snapshot(employee_id) {
         $('confirmbtn').innerHTML = '';
         $('confirmbtn').appendChild(progress_span(' ... creating snapshot'));
         orgjx_app_createSnapshot('$employee_id',function(_data) {
            confirmsnapshotbox.fade();
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?r='+uniqid('r');
         });
      }
      
      var confirmsnapshot = null;
      var confirmsnapshotbox = null;
      function confirm_snapshot(d,e) {
         confirmsnapshot = _dce('div');
         confirmsnapshot.setAttribute('id','confirmsnapshot');
         confirmsnapshot = document.body.appendChild(confirmsnapshot);
         confirmsnapshot.sub = confirmsnapshot.appendChild(_dce('div'));
         confirmsnapshot.sub.setAttribute('id','innerconfirmsnapshot');
         confirmsnapshotbox = new GlassBox();
         $('innerconfirmsnapshot').innerHTML = '<div style=\"height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;\">Create Report Snapshot Confirmation</div>'
                                           + '<div id=\"confirmmsg\" style=\"padding:20px;text-align:center;\">Are you going to create snapshot of these achievement values?</div>'
                                           + '<div id=\"confirmbtn\" style=\"background-color:#eee;padding:10px;text-align:center;\">'
                                             + '<input type=\"button\" value=\"Yes (Submit)\" onclick=\"do_snapshot();\"/>&nbsp;&nbsp;'
                                             + '<input type=\"button\" value=\"No\" onclick=\"confirmsnapshotbox.fade();\"/>'
                                           + '</div>';
         
         
         confirmsnapshotbox = new GlassBox();
         confirmsnapshotbox.init('confirmsnapshot','500px','165px','hidden','default',false,false);
         confirmsnapshotbox.lbo(false,0.3);
         confirmsnapshotbox.appear();
      }
      
      function save_target_achievement() {
         var val = parseFloat($('inp_target_achievement').value).toFixed(2);
         if(dvedittargetachievement) {
            dvedittargetachievement.d.innerHTML = val;
         }
         orgjx_app_saveCurrentTargetAchievement(val,dvedittargetachievement.pms_objective_id,dvedittargetachievement.actionplan_id,null);
      }
      
      function kp_target_achievement(d,e) {
         var k = getkeyc(e);
         if(d.chgt) {
            d.chgt.reset();
            d.chgt = null;
         }
         var val = parseFloat(d.value);
         if(k==13) {
            save_target_achievement();
            _destroy(dvedittargetachievement);
            dvedittargetachievement.d = null;
            dvedittargetachievement = null;
         } else if (k==27) {
            _destroy(dvedittargetachievement);
            dvedittargetachievement.d = null;
            dvedittargetachievement = null;
         } else {
            d.chgt = new ctimer('save_target_achievement();',300);
            d.chgt.start();
         }
      }
      
      var dvedittargetachievement = null;
      function edit_target_achievement(pms_objective_id,actionplan_id,d,e) {
         document.body.onclick = null;
         _destroy(dvedittargetachievement);
         if(dvedittargetachievement&&d==dvedittargetachievement.d) {
            dvedittargetachievement.d = null;
            dvedittargetachievement = null;
            return;
         }
         d.dv = _dce('div');
         d.dv.setAttribute('style','width:270px;position:absolute;padding:5px;-moz-border-radius:5px;border:1px solid #777;background-color:#ffffcc;left:0px;-moz-box-shadow:1px 1px 3px #000;');
         d.dv.innerHTML = '<div style=\"text-align:center;padding:2px;\">Achievement : <input onkeyup=\"kp_target_achievement(this,event);\" id=\"inp_target_achievement\" style=\"-moz-border-radius:3px;width:50px;text-align:center;\" type=\"text\" value=\"'+parseFloat(d.innerHTML)+'\"/>&nbsp;%</div>';
         d.dv.innerHTML += '<div style=\"margin-top:5px;padding:5px;border:1px solid #888;background-color:#fff;\" id=\"dvsnapshot_history\"></div>';
         d.dv = d.parentNode.appendChild(d.dv);
         d.dv.style.top = parseInt(oY(d)+d.offsetHeight+10)+'px';
         d.dv.style.left = parseInt(oX(d.parentNode)-(d.dv.offsetWidth-d.parentNode.offsetWidth)/2)+'px';
         d.dv.arrow = _dce('img');
         d.dv.arrow.setAttribute('style','position:absolute;left:0px;');
         d.dv.arrow.src = '".XOCP_SERVER_SUBDIR."/images/topmiddle.png';
         d.dv.arrow = d.dv.appendChild(d.dv.arrow);
         d.dv.arrow.style.top = '-12px';
         d.dv.arrow.style.left = parseInt(d.dv.offsetWidth-(d.dv.offsetWidth/2)-7)+'px';
         _dsa($('inp_target_achievement'));
         dvedittargetachievement = d.dv;
         dvedittargetachievement.d = d;
         dvedittargetachievement.pms_objective_id = pms_objective_id;
         dvedittargetachievement.actionplan_id = actionplan_id;
         setTimeout('document.body.onclick = function() { document.body.onclick = null; _destroy(dvedittargetachievement); };',100);
         $('dvsnapshot_history').appendChild(progress_span(' ... loading history'));
         orgjx_app_loadSnapshotHistory(pms_objective_id,actionplan_id,function(_data) {
            $('dvsnapshot_history').innerHTML = _data;
         });
      }
      
      
      ////////////////////////////
      function save_target_text(pms_objective_id,no) {
         var val = $('inp_target_text').value;
         if(dvedittargettext) {
            dvedittargettext.d.innerHTML = val;
         }
         orgjx_app_saveJAMTargetText(val,pms_objective_id,no,null);
      }
      
      function kp_target_text(d,e) {
         var k = getkeyc(e);
         if(d.chgt) {
            d.chgt.reset();
            d.chgt = null;
         }
         var val = d.value;
         if(k==13) {
            dvedittargettext.d.innerHTML = val;
            save_target_text(dvedittargettext.pms_objective_id,dvedittargettext.no);
         } else if (k==27) {
            _destroy(dvedittargettext);
            dvedittargettext.d = null;
            dvedittargettext = null;
         } else {
            d.chgt = new ctimer('save_target_text(\"'+dvedittargettext.pms_objective_id+'\",\"'+dvedittargettext.no+'\");',300);
            d.chgt.start();
         }
      }
      
      var dvedittargettext = null;
      function edit_target_text(pms_objective_id,no,d,e) {
         document.body.onclick = null;
         _destroy(dvedittargettext);
         if(dvedittargettext&&d==dvedittargettext.d) {
            dvedittargettext.d = null;
            dvedittargettext = null;
            return;
         }
         d.dv = _dce('div');
         d.dv.setAttribute('style','position:absolute;padding:5px;-moz-border-radius:5px;border:1px solid #777;background-color:#ffffcc;left:0px;-moz-box-shadow:1px 1px 3px #000;');
         var text = d.innerHTML;
         if(text=='"._EMPTY."') {
            text = '';
         }
         d.dv.innerHTML = '<div style=\"text-align:left;padding:2px;\">Target :<br/><textarea onkeyup=\"kp_target_text(this,event);\" id=\"inp_target_text\" style=\"-moz-border-radius:3px;width:350px;height:200px;\">'+text+'</textarea></div>';
         d.dv = d.parentNode.appendChild(d.dv);
         d.dv.style.top = parseInt(oY(d.parentNode)+d.parentNode.offsetHeight+25)+'px';
         var x = oX(d);
         if(x>650) {
            d.dv.style.left = parseInt(oX(d.parentNode)-(d.dv.offsetWidth)+(d.parentNode.offsetWidth))+'px';
         } else {
            d.dv.style.left = parseInt(oX(d)-(d.dv.offsetWidth/2)+(d.offsetWidth/2))+'px';
         }
         d.dv.arrow = _dce('img');
         d.dv.arrow.setAttribute('style','position:absolute;left:0px;');
         d.dv.arrow.src = '".XOCP_SERVER_SUBDIR."/images/topmiddle.png';
         d.dv.arrow = d.dv.appendChild(d.dv.arrow);
         d.dv.arrow.style.top = '-12px';
         if(x>650) {
            d.dv.arrow.style.left = parseInt(d.dv.offsetWidth-(d.parentNode.offsetWidth/2)-7)+'px';
         } else {
            d.dv.arrow.style.left = parseInt(d.dv.offsetWidth-(d.dv.offsetWidth/2)-7)+'px';
         }
         $('inp_target_text').focus();
         dvedittargettext = d.dv;
         dvedittargettext.d = d;
         dvedittargettext.pms_objective_id = pms_objective_id;
         dvedittargettext.no = no;
         //setTimeout('document.body.onclick = function() { document.body.onclick = null; _destroy(dvedittargettext); };',100);
      }
      
      ////////////////////////////
      
      //]]></script>";
      
      
      return $ret.$form.$tooltip.$js;
   }
   
   function main() {
      $pmsselobj = new _pms_class_SelectSession();
      $pmssel = "<div style='padding-bottom:2px;max-width:900px;'>".$pmsselobj->show()."</div>";
      
      if(!isset($_SESSION["pms_psid"])||$_SESSION["pms_psid"]==0) {
         return $pmssel;
      }
      $db = &Database::getInstance();
      $user_id = getUserID();
      list($self_job_id,
           $self_employee_id,
           $self_job_nm,
           $self_nm,
           $self_nip,
           $self_gender,
           $self_jobstart,
           $self_entrance_dttm,
           $self_jobage,
           $self_job_summary,
           $self_person_id,
           $self_user_id,
           $self_first_assessor_job_id,
           $self_next_assessor_job_id)=_hris_getinfobyuserid($user_id);
      
      switch ($this->catch) {
         case $this->blockID:
            $ret = $this->pmsmyactionplan($self_employee_id);
            break;
         default:
            $ret = $this->pmsmyactionplan($self_employee_id);
            break;
      }
      return $pmssel.$ret;
   }
}

} // PMS_MYACTIONPLAN_DEFINED
?>