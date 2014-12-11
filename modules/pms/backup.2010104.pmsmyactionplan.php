<?php
//--------------------------------------------------------------------//
// Filename : modules/pms/pmsmyactionplan.php                                  //
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
   
   
   function pmsmyactionplan($employee_id) {
      $db=&Database::getInstance();
      $ajax = new _pms_class_ObjectiveAjax("orgjx");
      $user_id = getUserID();
      
      if(!isset($_SESSION["ach_dttm"])) {
         $ach_dttm = getSQLDate();
         $_SESSION["ach_dttm"] = $ach_dttm;
      } else {
         $ach_dttm = $_SESSION["ach_dttm"];
      }
      
      $sql = "SELECT DISTINCT(approval_st) FROM pms_jam WHERE employee_id = '$employee_id'";
      $result = $db->query($sql);
      list($jam_status_cd)=$db->fetchRow($result);
      
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
      
      $jam_status_cd = "";
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
      
      $sql = "SELECT approval_st,return_note FROM pms_jam WHERE employee_id = '$employee_id'";
      $result = $db->query($sql);
      list($jam_status_cd,$return_note)=$db->fetchRow($result);
      if($jam_status_cd=="return") {
         $ret .= "<div style='border:1px solid #00f;-moz-border-radius:5px;padding:5px;color:blue;margin-bottom:10px;'><span style='font-weight:bold;'>Returned / Not Approved:</span><br/>$return_note</div>";
      }
      
      
      $ret .= "<div style='max-width:700px;border:1px solid #bbb;-moz-border-radius:5px;padding:10px;'>";
      $ret .= "<div style=''>Percentage below each objective is the weight of the objective. Calculate weighed score for each rating. "
            . "Select the most appropriate statement for employee's performance in each area of contribution, "
            . "then input the weighed score and selected statement into \"Final Result\" column. "
            . "Total score is the sum of the percentage in \"Final Result\" column of each objective.</div>";
      $ret .= "<hr noshade='1' size='1'/>";
      $ret .= "<div style='font-style:italic;'>Persentase di bawah setiap objektif adalah bobot dari objektif tersebut. "
            . "Hitunglah nilai bobot untuk setiap penilaian dengan memilih pernyataan yang sesuai dengan kinerja karyawan "
            . "untuk masing-masing area kontribusi, kemudian masukkan nilai bobot dan pernyataan yang dipilih itu di "
            . "kolom \"Final Result\". Total nilai adalah jumlah persentase penilaian di kolom \"Final Result\" dari setiap objektif. </div>";
      $ret .= "</div>";
      
      $ret .= "<div>";
      
      $ret .= "<table style='width:2300px;margin-top:10px;' class='xxlist'>"
            . "<colgroup>"
            . "<col width='30'/>"
            . "<col width='30'/>"
            . "<col width='125'/>"
            . "<col width='125'/>"
            . "<col width='125'/>"
            . "<col width='125'/>"
            . "<col width='125'/>"
            . "<col width='125'/>"
            . "<col width='125'/>"
            . "<col width='125'/>"
            . "<col width='125'/>"
            . "<col width='125'/>"
            . "<col width='125'/>"
            . "<col width='125'/>"
            . "<col width='125'/>"
            . "<col width='125'/>"
            . "<col width='125'/>"
            . "<col width='125'/>"
            . "<col width='125'/>"
            . "</colgroup>";
      
      $ret .= "<thead>"
            . "<tr>"
               . "<td style='border-right:1px solid #bbb;text-align:center;' colspan='3'>Objectives</td>"
               . "<td colspan='12' style='border-right:1px solid #bbb;'></td>"
               . "<td style='text-align:center;border-right:1px solid #bbb;' colspan='2'>Monthly Target</td>"
               . "<td style='text-align:center;' colspan='2'>Status<br/><span class='xlnk' id='spdtach' onclick='_changedatetime(\"spdtach\",\"hdtach\",\"date\",true,false)'>".sql2ind($ach_dttm,"date")."</span><input type='hidden' value='$ach_dttm' id='hdtach'/></td>"
            . "</tr>"
            . "<tr>"
               . "<td style='border-right:1px solid #bbb;text-align:center;'>No.</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;'>ID</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;'>Refer to Objective and Target Form</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;'>January</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;'>February</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;'>March</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;'>April</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;'>May</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;'>June</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;'>July</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;'>August</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;'>September</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;'>October</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;'>November</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;'>December</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;'>Target</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;'>Realization</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;'>Achievement</td>"
               . "<td style='text-align:center;'>Zone</td>"
            . "</tr>"
            . "</thead>";
      
      $ret .= "<tbody>";
      
      $sql = "SELECT a.pms_objective_id,a.pms_share_weight,b.pms_objective_text"
           . " FROM pms_actionplan_share a"
           . " LEFT JOIN pms_objective b USING(pms_objective_id)"
           . " WHERE a.pms_actionplan_pic_employee_id = '$employee_id'"
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
            $sql = "SELECT * FROM pms_jam WHERE employee_id = '$employee_id' AND pms_objective_id = '$pms_objective_id'";
            $rjam = $db->query($sql);
            if($db->getRowsNum($rjam)==0) {
               $objective_weight = _bctrim(bcmul(bcdiv($objective_share_arr[$pms_objective_id],$total_weight),100));
               $sql = "INSERT INTO pms_jam (pms_objective_id,employee_id,objective_weight) VALUES ('$pms_objective_id','$employee_id','$objective_weight')";
               $db->query($sql);
            } else {
               $objective_weight = _bctrim(bcmul(bcdiv($objective_share_arr[$pms_objective_id],$total_weight),100));
               $sql = "UPDATE pms_jam SET objective_weight = '$objective_weight' WHERE pms_objective_id = '$pms_objective_id' AND employee_id = '$employee_id'";
               $db->query($sql);
            }
         }
         
      }
      
      ////////////////////////////////////
      
      $zone_cnt = 0;
      $zone_ttl = 0;
      
      $sql = "SELECT b.pms_objective_no,c.pms_perspective_code,b.pms_org_id,a.pms_objective_id,b.pms_objective_text,a.objective_weight,"
           . "a.target_text0,a.target_text1,a.target_text2,a.target_text3,a.target_text4,a.final_result_text,"
           . "a.target_weight0,a.target_weight1,a.target_weight2,a.target_weight3,a.target_weight4,a.final_result_weight,"
           . "a.approval1_dttm,a.approval2_dttm,a.approval_st,a.submit_dttm"
           . " FROM pms_jam a"
           . " LEFT JOIN pms_objective b USING(pms_objective_id)"
           . " LEFT JOIN pms_perspective c USING(pms_perspective_id)"
           . " WHERE a.employee_id = '$employee_id'"
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
                    $target_weight0,$target_weight1,$target_weight2,$target_weight3,$target_weight4,$final_result_weight,
                    $approval1_dttm,$approval2_dttm,$approval_st,$submit_dttmx)=$db->fetchRow($result)) {
            $no++;
            
            $jam_status_cd = $approval_st;
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
            
            $vardvtg = "";
            $vardvach = "";
            $varzone = "";
            
            for($i=1;$i<=12;$i++) {
               $vardvap = "dvapx_${i}";
               $$vardvap = "";
               $sql = "SELECT actionplan_id,actionplan_text,target_text,current_achievement,order_no"
                    . " FROM pms_pic_action"
                    . " WHERE pms_objective_id = '$pms_objective_id'"
                    . " AND employee_id = '$employee_id'"
                    . " AND month_id = '$i'"
                    . " ORDER BY order_no";
               $resultx = $db->query($sql);
               if($db->getRowsNum($resultx)>0) {
                  while(list($actionplan_id,$actionplan_text,$target_text,$current_achievement,$order_no)=$db->fetchRow($resultx)) {
                     $$vardvap .= "<div style='margin-bottom:2px;color:black;padding:3px;overflow:hidden;width:110px;-moz-border-radius:3px;' id='dvap_${pms_objective_id}_${actionplan_id}'>"
                                . "<div style='width:9000px;'>${order_no}. <span onclick='edit_actionplan(\"$actionplan_id\",\"$month_id\",\"$pms_objective_id\",this,event);' id='spap_${pms_objective_id}_${actionplan_id}' class='xlnk'>".htmlentities($actionplan_text)."</span></div>"
                                . "</div>";
                     $vardvtg .= "<div style='margin-bottom:2px;color:black;padding:3px;overflow:hidden;width:110px;-moz-border-radius:3px;' id='dvtg_${pms_objective_id}_${actionplan_id}'>"
                                . "<div style='width:9000px;'>${order_no}. <span id='sptg_${pms_objective_id}_${actionplan_id}'>".htmlentities($target_text)."</span></div>"
                                . "</div>";
                     
                     $vardvach .= "<div style='margin-bottom:2px;color:black;padding:3px;width:110px;-moz-border-radius:3px;text-align:center;' id='dvtg_${pms_objective_id}_${actionplan_id}'>"
                                . "<span onclick='edit_target_achievement(\"$pms_objective_id\",\"$actionplan_id\",this,event);' class='xlnk' id='spach_${pms_objective_id}_${actionplan_id}'>".toMoney($current_achievement)."</span> %</div>"
                                . "</div>";
                     
                     $sql = "SELECT snapshot_dttm,current_achievement,month_id FROM pms_pic_action_snapshot"
                          . " WHERE employee_id = '$employee_id' AND pms_objective_id = '$pms_objective_id'"
                          . " AND actionplan_id = '$actionplan_id'"
                          . " ORDER BY snapshot_dttm DESC,created_dttm DESC,order_no";
                     $rs = $db->query($sql);
                     $ttlach = 0;
                     $sn_cnt = 0;
                     if($db->getRowsNum($rs)>0) {
                        $last_cx = 0;
                        $last_month = 0;
                        while(list($snapshot_dttm,$cx,$mx)=$db->fetchRow($rs)) {
                           list($yyyy,$mm,$ddx)=explode("-",$snapshot_dttm);
                           $mm += 0;
                           if($mm>=$i) {
                              if($last_cx>=100&&$cx>=100) continue;
                              //if($last_month==$mm) continue;
                              $ttlach = _bctrim(bcadd($ttlach,$cx));
                              $sn_cnt++;
                              $last_cx = $cx;
                              $last_month = $mm;
                           }
                        }
                     }
                     if($sn_cnt>0) {
                        $zonex = _bctrim(bcdiv($ttlach,$sn_cnt));
                        $zonex_txt = toMoney($zonex)." %";
                        $zone_cnt++;
                        $zone_ttl = _bctrim(bcadd($zone_ttl,$zonex));
                     } else {
                        $zonex_txt = "-";
                     }
                     
                     $varzone .= "<div style='margin-bottom:2px;color:black;padding:3px;width:110px;-moz-border-radius:3px;text-align:center;' >"
                               . "$zonex_txt"
                               . "</div>";
                     
                     
                  }
               }
            }
            
            $ret .= "<tr height='75'>";
            $ret .= "<td style='border-right:1px solid #bbb;text-align:center;' rowspan='2'>$no</td>";
            $ret .= "<td style='border-right:1px solid #bbb;text-align:center;' rowspan='2'>${pms_perspective_code}${pms_objective_no}</td>";
            $ret .= "<td style='border-right:1px solid #bbb;'>$pms_objective_text</td>";
            $ret .= "<td style='border-right:1px solid #bbb;vertical-align:top;' id='tdap_${pms_objective_id}_1'>$dvapx_1</td>";
            $ret .= "<td style='border-right:1px solid #bbb;vertical-align:top;' id='tdap_${pms_objective_id}_2'>$dvapx_2</td>";
            $ret .= "<td style='border-right:1px solid #bbb;vertical-align:top;' id='tdap_${pms_objective_id}_3'>$dvapx_3</td>";
            $ret .= "<td style='border-right:1px solid #bbb;vertical-align:top;' id='tdap_${pms_objective_id}_4'>$dvapx_4</td>";
            $ret .= "<td style='border-right:1px solid #bbb;vertical-align:top;' id='tdap_${pms_objective_id}_5'>$dvapx_5</td>";
            $ret .= "<td style='border-right:1px solid #bbb;vertical-align:top;' id='tdap_${pms_objective_id}_6'>$dvapx_6</td>";
            $ret .= "<td style='border-right:1px solid #bbb;vertical-align:top;' id='tdap_${pms_objective_id}_7'>$dvapx_7</td>";
            $ret .= "<td style='border-right:1px solid #bbb;vertical-align:top;' id='tdap_${pms_objective_id}_8'>$dvapx_8</td>";
            $ret .= "<td style='border-right:1px solid #bbb;vertical-align:top;' id='tdap_${pms_objective_id}_9'>$dvapx_9</td>";
            $ret .= "<td style='border-right:1px solid #bbb;vertical-align:top;' id='tdap_${pms_objective_id}_10'>$dvapx_10</td>";
            $ret .= "<td style='border-right:1px solid #bbb;vertical-align:top;' id='tdap_${pms_objective_id}_11'>$dvapx_11</td>";
            $ret .= "<td style='border-right:1px solid #bbb;vertical-align:top;' id='tdap_${pms_objective_id}_12'>$dvapx_12</td>";
            $ret .= "<td style='border-right:1px solid #bbb;vertical-align:top;' rowspan='2' id='tdtg_${pms_objective_id}'>".$vardvtg."</td>";
            $ret .= "<td style='border-right:1px solid #bbb;vertical-align:top;' rowspan='2'></td>";
            $ret .= "<td style='border-right:1px solid #bbb;vertical-align:top;' rowspan='2'>$vardvach</td>";
            $ret .= "<td style='border-right:1px solid #bbb;vertical-align:top;' rowspan='2'>$varzone</td>";
            $ret .= "</tr>";
            $ret .= "<tr>";
            $ret .= "<td style='border-right:1px solid #bbb;text-align:right;'>".toMoney(_bctrim($objective_weight))." %</td>";
            $ret .= "<td style='border-right:1px solid #bbb;text-align:center;'>[<span class='xlnk' onclick='edit_actionplan(\"new\",1,\"$pms_objective_id\",this,event);'>Add</span>]</td>";
            $ret .= "<td style='border-right:1px solid #bbb;text-align:center;'>[<span class='xlnk' onclick='edit_actionplan(\"new\",2,\"$pms_objective_id\",this,event);'>Add</span>]</td>";
            $ret .= "<td style='border-right:1px solid #bbb;text-align:center;'>[<span class='xlnk' onclick='edit_actionplan(\"new\",3,\"$pms_objective_id\",this,event);'>Add</span>]</td>";
            $ret .= "<td style='border-right:1px solid #bbb;text-align:center;'>[<span class='xlnk' onclick='edit_actionplan(\"new\",4,\"$pms_objective_id\",this,event);'>Add</span>]</td>";
            $ret .= "<td style='border-right:1px solid #bbb;text-align:center;'>[<span class='xlnk' onclick='edit_actionplan(\"new\",5,\"$pms_objective_id\",this,event);'>Add</span>]</td>";
            $ret .= "<td style='border-right:1px solid #bbb;text-align:center;'>[<span class='xlnk' onclick='edit_actionplan(\"new\",6,\"$pms_objective_id\",this,event);'>Add</span>]</td>";
            $ret .= "<td style='border-right:1px solid #bbb;text-align:center;'>[<span class='xlnk' onclick='edit_actionplan(\"new\",7,\"$pms_objective_id\",this,event);'>Add</span>]</td>";
            $ret .= "<td style='border-right:1px solid #bbb;text-align:center;'>[<span class='xlnk' onclick='edit_actionplan(\"new\",8,\"$pms_objective_id\",this,event);'>Add</span>]</td>";
            $ret .= "<td style='border-right:1px solid #bbb;text-align:center;'>[<span class='xlnk' onclick='edit_actionplan(\"new\",9,\"$pms_objective_id\",this,event);'>Add</span>]</td>";
            $ret .= "<td style='border-right:1px solid #bbb;text-align:center;'>[<span class='xlnk' onclick='edit_actionplan(\"new\",10,\"$pms_objective_id\",this,event);'>Add</span>]</td>";
            $ret .= "<td style='border-right:1px solid #bbb;text-align:center;'>[<span class='xlnk' onclick='edit_actionplan(\"new\",11,\"$pms_objective_id\",this,event);'>Add</span>]</td>";
            $ret .= "<td style='border-right:1px solid #bbb;text-align:center;'>[<span class='xlnk' onclick='edit_actionplan(\"new\",12,\"$pms_objective_id\",this,event);'>Add</span>]</td>";
            //$ret .= "<td style='border-right:1px solid #bbb;text-align:center;'>&nbsp;</td>";
            //$ret .= "<td style='border-right:1px solid #bbb;text-align:center;'>&nbsp;</td>";
            $ret .= "</tr>";
            $ttl_target_weight0 = _bctrim(bcadd($ttl_target_weight0,$target_weight0));
            $ttl_target_weight1 = _bctrim(bcadd($ttl_target_weight1,$target_weight1));
            $ttl_target_weight2 = _bctrim(bcadd($ttl_target_weight2,$target_weight2));
            $ttl_target_weight3 = _bctrim(bcadd($ttl_target_weight3,$target_weight3));
            $ttl_target_weight4 = _bctrim(bcadd($ttl_target_weight4,$target_weight4));
         }
      }
      
      if($zone_cnt>0) {
         $zone_ttl_txt = toMoney(bcdiv($zone_ttl,$zone_cnt))." %";
      } else {
         $zone_ttl_txt = "-";
      }
      
      $ret .= "<tr>";
      $ret .= "<td style='border-top:1px solid #bbb;background-color:#eee;color:black;border-right:1px solid #bbb;text-align:center;font-weight:bold;' colspan='2'>Total</td>";
      $ret .= "<td style='border-top:1px solid #bbb;background-color:#eee;color:black;border-right:1px solid #bbb;text-align:right;font-weight:bold;'>100 %</td>";
      $ret .= "<td style='border-top:1px solid #bbb;background-color:#eee;color:black;border-right:1px solid #bbb;text-align:right;font-weight:bold;' colspan='15'></td>";
      $ret .= "<td style='border-top:1px solid #bbb;background-color:#eee;color:black;border-right:1px solid #bbb;text-align:center;font-weight:bold;'>$zone_ttl_txt</td>";
      $ret .= "</tr>";
      
      $ret .= "<tr>";
      $ret .= "<td style='border-top:1px solid #bbb;background-color:#eee;color:black;border-right:1px solid #bbb;text-align:right;font-weight:bold;' colspan='19'>"
            . "<input type='button' value='Create Report Snapshot' class='xaction' onclick='confirm_snapshot(this,event);'/>"
            . "</td>";
      $ret .= "</tr>";
      
      
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
      
      $js = $ajax->getJs()."<script type='text/javascript' src='".XOCP_SERVER_SUBDIR."/include/calendar.js'></script><script type='text/javascript'>//<![CDATA[
      
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
      
      function save_actionplan() {
         
         var ap = $('inp_actionplan').value;
         var tg = $('inp_target').value;
         
         orgjx_app_saveActionPlan(urlencode(ap),urlencode(tg),dvmonthaction.actionplan_id,dvmonthaction.pms_objective_id,dvmonthaction.month_id,function(_data) {
            var data = recjsarray(_data);
            if(data[0]=='new') {
               dvmonthaction.actionplan_id = data[1];
               var dvap = _dce('div');
               dvap.setAttribute('style','margin-bottom:2px;color:black;padding:3px;overflow:hidden;width:110px;-moz-border-radius:3px;');
               dvap.setAttribute('id','dvap_'+data[2]+'_'+data[1]);
               dvap.innerHTML = data[5];
               dvap = $('tdap_'+data[2]+'_'+data[4]).appendChild(dvap);
               var dvtg = _dce('div');
               dvtg.setAttribute('style','margin-bottom:2px;color:black;padding:3px;overflow:hidden;width:110px;-moz-border-radius:3px;');
               dvtg.setAttribute('id','dvtg_'+data[2]+'_'+data[1]);
               dvtg.innerHTML = data[6];
               dvtg = $('tdtg_'+data[2]).appendChild(dvtg);
            } else {
               
               var dvap = $('dvap_'+data[2]+'_'+data[1]);
               
               dvap.innerHTML = data[5];
               var dvtg = $('dvtg_'+data[2]+'_'+data[1]);
               dvtg.innerHTML = data[6];
               
            }
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
      
      var dvmonthaction = null;
      function edit_actionplan(actionplan_id,month_id,pms_objective_id,d,e) {
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
         $('confirmbtn').appendChild(progress_span(' ... approve JAM'));
         orgjx_app_approval1JAM('$employee_id',function(_data) {
            confirmapproval1box.fade();
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?goto=y&employee_id=${employee_id}&r='+uniqid('r');
         });
      }
      
      
      function confirm_first_assessor_return_JAM(employee_id) {
         var return_note = urlencode($('return_note').value);
         orgjx_app_firstAssessorReturnJAM(employee_id,return_note,function(_data) {
            var data = recjsarray(_data);
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?goto=y&employee_id=${employee_id}&r='+uniqid('r');
         });
      }
      
      
      var firstassessorreturnedit = null;
      var firstassessorreturnbox = null;
      function first_assessor_return_JAM(employee_id) {
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
         
         $('innerfirstassessorreturnedit').innerHTML = '<div style=\"height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;\">Not Approve JAM Confirmation</div>'
                                   + '<div style=\"background-color:#ffffff;font-weight:bold;font-size:1.1em;height:300px;text-align:center;padding-top:20px;\">'
                                   + 'You do not approve this JAM.<br/>You are going to return this JAM to the employee.'
                                   + '<br/><br/>With the following notes:<br/>'
                                   + '<textarea id=\"return_note\" style=\"width:400px;height:100px;\">'+xreturn_note+'</textarea>'
                                   + '<br/><br/>Are you sure?'
                                   + '<br/><br/>'
                                           + '<div id=\"confirmbtn\" style=\"background-color:#eee;padding:10px;text-align:center;\">'
                                   + '<input type=\"button\" value=\"Yes (Not Approve)\" onclick=\"confirm_first_assessor_return_JAM(\\''+employee_id+'\\');\"/>&nbsp;'
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
         $('innerconfirmapproval1').innerHTML = '<div style=\"height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;\">Approve JAM Confirmation</div>'
                                           + '<div id=\"confirmmsg\" style=\"padding:20px;text-align:center;\">Are you going to approve this JAM?</div>'
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
      
      return $ret.$js;
   }
   
   function main() {
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
            
            $this->pmsmyactionplan($self_employee_id);
            break;
         default:
            $ret = $this->pmsmyactionplan($self_employee_id);
            break;
      }
      return $ret;
   }
}

} // PMS_MYACTIONPLAN_DEFINED
?>