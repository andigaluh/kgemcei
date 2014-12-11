<?php
//--------------------------------------------------------------------//
// Filename : modules/pms/class/ajax_monitor.php                  //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-12-17                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_PMSMONITORAJAX_DEFINED') ) {
   define('HRIS_PMSMONITORAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");
require_once(XOCP_DOC_ROOT."/modules/pms/modconsts.php");


class _pms_class_PMSMonitorAjax extends AjaxListener {
   
   function __construct($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/pms/class/ajax_monitor.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_drillDownOrg","app_drillDownPICA","app_listPICA");
   }
   
   function app_listPICA($args) {
      global $xocp_vars;
      include_once(XOCP_DOC_ROOT."/modules/pms/include/pms.php");
      $db=&Database::getInstance();
      $psid = $_SESSION["pms_psid"];
      $org_id = $args[0];
      $pms_objective_id = $args[1];
      $_SESSION["pms_pica"] = array();
      _pms_listPICA($pms_objective_id);
      
      $ret = "<table style='width:100%;'><tbody><tr><td style='text-align:left;font-weight:bold;'>Problem Identification &amp; Corrective Action (PICA)</td>"
           . "<td style='text-align:right;'>[<span class='ylnk' onclick='backfrom_view_pica(\"$org_id\",\"$pms_objective_id\",this,event);'>back</span>]</td></tr></tbody></table>"
           . "<table class='xxlist' style='border-bottom:0px;'><colgroup><col width='50'/><col width='100'/><col width='100'/><col width='40'/><col width='100'/><col width='80'/><col width='20'/></colgroup>"
           . "<thead>";
      $ret .= "<tr>"
            . "<td style='text-align:center;'><div style='width:50px !important;'>Month</div></td>"
            . "<td style='text-align:left;'><div style='width:100px !important;'>Employee</div></td>"
            . "<td style='text-align:left;'><div style='width:100px !important;'>Action Plan</div></td>"
            . "<td style='text-align:center;'><div style='width:40px !important;'>Ach.</div></td>"
            . "<td><div style='width:100px !important;'>Root Cause</div></td>"
            . "<td><div style='width:80px !important;'>Improvement</div></td>"
            . "<td><div style='width:20px !important;'>&#160;</div></td>"
            . "</tr>";
      $ret .= "</thead></table>";
      $ret .= "<div style='max-height:200px;overflow:auto;'>"
            . "<table class='xxlist'><colgroup><col width='50'/><col width='100'/><col width='100'/><col width='40'/><col width='100'/><col width='90'/></colgroup>";
      $ret .= "<tbody>";
      
      $no = 0;
      if(count($_SESSION["pms_pica"])>0) {
         foreach($_SESSION["pms_pica"] as $month_id=>$v) {
            foreach($v as $employee_id=>$vv) {
               $sql = "SELECT b.person_nm FROM ".XOCP_PREFIX."employee a LEFT JOIN ".XOCP_PREFIX."persons b USING(person_id) WHERE a.employee_id = '$employee_id'";
               $result = $db->query($sql);
               list($employee_nm)=$db->fetchRow($result);
               foreach($vv as $actionplan_id=>$vvv) {
                  $no++;
                  list($actionplan_text,$current_achievement,$root_cause,$improvement_text) = $vvv;
                  if($no%2==1) {
                     $bgcolor = "#eee";
                  } else {
                     $bgcolor = "#fff";
                  }
                  $ret .= "<tr style='background-color:${bgcolor};'>"
                        . "<td style='background-color:${bgcolor};text-align:center;'><div style='width:50px !important;'>".$xocp_vars['month_year_short'][$month_id]."</div></td>"
                        . "<td style='background-color:${bgcolor};text-align:left;'><div style='width:95px !important;overflow:hidden;'><div style='width:900px;'>".htmlentities($employee_nm,ENT_QUOTES)."</div></div></td>"
                        . "<td style='background-color:${bgcolor};text-align:left;'><div style='width:100px !important;'>".htmlentities($actionplan_text,ENT_QUOTES)."</div></td>"
                        . "<td style='text-align:center;background-color:#fcc;'><div style='width:40px !important;'>".toMoney($current_achievement)."</div></td>"
                        . "<td style='background-color:${bgcolor};text-align:left;'><div style='width:100px !important;'>".htmlentities($root_cause,ENT_QUOTES)."</div></td>"
                        . "<td style='background-color:${bgcolor};text-align:left;'><div style='width:80px !important;'>".htmlentities($improvement_text,ENT_QUOTES)."</div></td>"
                        . "</tr>";
                  
               }
            }
         }
      } else {
         $ret .= "<tr><td colspan='6' style='text-align:center;font-style:italic;'>No PICA.</td></tr>";
      }
      
      $ret .= "</tbody></table></div>";
      
      return array($org_id,$pms_objective_id,$ret);
   }
   
   function app_drillDownPICA($args) {
      include_once(XOCP_DOC_ROOT."/modules/pms/include/pms.php");
      include_once(XOCP_DOC_ROOT."/modules/pms/pmsmonitor.php");
      $db=&Database::getInstance();
      $psid = $_SESSION["pms_psid"];
      $employee_id = $args[0];
      $pms_objective_id = $args[1];
      global $xocp_vars;
      
      //////////// query all action plan first //////////////////////////
      $ret = "<table class='xxlist'><colgroup><col/><col/><col width='100'/><col width='100'/></colgroup>"
           . "<thead>";
      $ret .= "<tr>"
            . "<td style='text-align:center;'>Month</td>"
            . "<td style='text-align:right;'>Ach.</td>"
            . "<td>Root Cause</td>"
            . "<td>Improvement</td>"
            . "</tr>";
      $ret .= "</thead><tbody>";
      
      $sql = "SELECT actionplan_id,actionplan_text,target_text,current_achievement,order_no,approval_st,submit_dttm,approval1_dttm,approval1_employee_id,"
           . "pica_id,is_pica,target_achievement,report_approval_st,month_id,repeat_type"
           . " FROM pms_pic_action"
           . " WHERE pms_objective_id = '$pms_objective_id'"
           . " AND employee_id = '$employee_id'"
           . " AND is_pica = '0'"
           . " AND actionplan_group_id = '0'"
           . " ORDER BY month_id,order_no";
      $resultx = $db->query($sql);
      $aparr = array();
      $aptextarr = array();
      $apno = array();
      $no = 0;
      if($db->getRowsNum($resultx)>0) {
         while(list($actionplan_id,$actionplan_text,$target_text,$current_achievement,$order_no,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,
                    $pica_id,$is_pica,$target_achievement,$report_approval_st,$month_id,$repeat_type)=$db->fetchRow($resultx)) {
            $aparr[$no][$month_id] = array($actionplan_id,$actionplan_text,$target_text,$current_achievement,$order_no,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,$pica_id,$is_pica,$target_achievement,$report_approval_st,$month_id);
            $aptextarr[$no] = array($actionplan_id,$actionplan_text,$target_text,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,$month_id,$target_achievement,$current_achievement);
            $apno[$actionplan_id] = array($no,$repeat_type);
            $no++;
         }
      }
      
      $gaparr = array();
      $gaptextarr = array();
      $gapno = array();
      foreach($apno as $actionplan_group_id=>$vv) {
         list($gno,$repeat_typexxx)=$vv;
         $sql = "SELECT actionplan_id,actionplan_text,target_text,current_achievement,order_no,approval_st,submit_dttm,approval1_dttm,approval1_employee_id,"
              . "pica_id,is_pica,target_achievement,report_approval_st,month_id"
              . " FROM pms_pic_action"
              . " WHERE pms_objective_id = '$pms_objective_id'"
              . " AND employee_id = '$employee_id'"
              . " AND is_pica = '0'"
              . " AND actionplan_group_id = '$actionplan_group_id'"
              . " ORDER BY month_id,order_no";
         $resultx = $db->query($sql);
         
         if($db->getRowsNum($resultx)>0) {
            while(list($actionplan_id,$actionplan_text,$target_text,$current_achievement,$order_no,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,
                       $pica_id,$is_pica,$target_achievement,$report_approval_st,$month_id)=$db->fetchRow($resultx)) {
               $gaparr[$gno][$month_id] = array($actionplan_id,$actionplan_text,$target_text,$current_achievement,$order_no,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,$pica_id,$is_pica,$target_achievement,$report_approval_st,$month_id,$actionplan_group_id);
               $gaptextarr[$gno] = array($actionplan_id,$actionplan_text,$target_text,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,$month_id,$target_achievement,$actionplan_group_id);
               $gapno[$actionplan_id] = $gno;
               
               /// unset to display
               unset($aparr[$gno][$month_id]);
            }
         }
      }
      
      
      $sql = "SELECT actionplan_id,actionplan_text,target_text,current_achievement,order_no,approval_st,submit_dttm,approval1_dttm,approval1_employee_id,"
           . "pica_id,is_pica,target_achievement,report_approval_st,month_id,root_cause"
           . " FROM pms_pic_action"
           . " WHERE pms_objective_id = '$pms_objective_id'"
           . " AND employee_id = '$employee_id'"
           . " AND is_pica = '1'"
           . " AND actionplan_group_id = '0'"
           . " ORDER BY month_id,order_no";
      $resultx = $db->query($sql);
      if($db->getRowsNum($resultx)>0) {
         while(list($actionplan_id,$actionplan_text,$target_text,$current_achievement,$order_no,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,
                    $pica_id,$is_pica,$target_achievement,$report_approval_st,$month_id,$root_cause)=$db->fetchRow($resultx)) {
            $root_actionplan_id = _pms_getpicaroot($pms_objective_id,$employee_id,$actionplan_id);
            if(1) {
               list($no,$repeat_typex) = $apno[$root_actionplan_id];
               $picaarr[$no][$month_id] = array($actionplan_id,$actionplan_text,$target_text,$current_achievement,$order_no,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,$pica_id,$is_pica,$target_achievement,$report_approval_st,$month_id);
            }
         }
      }
      
      $ap_report_final = array();
      
      if(count($apno)>0) {
         
         $ap_cnt = count($apno);
         $ap_ach_ttl = 0;
         
         $ap_month_cnt = 0;
         $last_ap = 0;
         $apxno = 0;
         foreach($apno as $actionplan_idx=>$v) {
            list($no,$repeat_type)=$v;
            
            if($repeat_type==0) {
               $div = 100;
            } else {
               $div = 0;
            }
            
            $month_id_start = 1; //$report_month_id;
            $month_id_stop = 12;
            
            $ttl_ap_achievement = 0;
            //// row by row calculation first (carry over calculation)
            for($month_id=$month_id_start;$month_id<=$month_id_stop;$month_id++) {
               
               $report_approval_st = "";
               $current_achievement = 0;
               
               $actionplan_group_id = 0;
               
               if($repeat_type==0&&isset($aparr[$no][$month_id])) {
                  list($actionplan_id,$actionplan_text,$target_text,$current_achievement,$order_no,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,$pica_id,$is_pica,$target_achievement,$report_approval_st,$month_idx)=$aparr[$no][$month_id];
               } else if($repeat_type==0&&isset($picaarr[$no][$month_id])) {
                  list($actionplan_id,$actionplan_text,$target_text,$current_achievement,$order_no,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,$pica_id,$is_pica,$target_achievement,$report_approval_st,$month_idx)=$picaarr[$no][$month_id];
               } else if(isset($gaparr[$no][$month_id])) {
                  list($actionplan_id,$actionplan_text,$target_text,$current_achievement,$order_no,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,$pica_id,$is_pica,$target_achievement,$report_approval_st,$month_idx,$actionplan_group_id)=$gaparr[$no][$month_id];
               } else {
                  if(isset($aparr[$no][$month_id])) {
                     list($actionplan_id,$actionplan_text,$target_text,$current_achievement,$order_no,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,$pica_id,$is_pica,$target_achievement,$report_approval_st,$month_idx)=$aparr[$no][$month_id];
                  } else {
                     continue;
                  }
               }
               
               $sql = "SELECT root_cause,improvement_text FROM pms_pic_action WHERE pms_objective_id = '$pms_objective_id' AND employee_id = '$employee_id' AND actionplan_id = '$actionplan_id'";
               $rroot = $db->query($sql);
               if($db->getRowsNum($rroot)>0) {
                  list($root_cause,$improvement_text)=$db->fetchRow($rroot);
               } else {
                  $root_cause = $improvement_text = "&nbsp;";
               }
               
               if($report_approval_st=="final") {
               
                  $ap_report_final[$actionplan_idx] = 1;
                  $ttl_ap_achievement = _bctrim(bcadd($ttl_ap_achievement,$current_achievement));
                  if($repeat_type!=0) {
                     $div += 100;
                  }
                  
                  if($current_achievement<91) {
                     $bgcolor = "background-color:#ffcccc;";
                  } else {
                     $bgcolor = "";
                  }
                  
                  if($last_ap!=($actionplan_group_id>0?$actionplan_group_id:$actionplan_id)) {
                     $apxno++;
                     $ret .= "<tr>"
                           . "<td style='font-weight:bold;' colspan='4'>$apxno. ".htmlentities($actionplan_text)."</td>"
                           . "</tr>";
                  
                  
                  }
                  
                  $ret .= "<tr>"
                        //. "<td style='${bgcolor}text-align:center;'>".($actionplan_group_id>0?$actionplan_group_id:$actionplan_id)."</td>"
                        . "<td style='${bgcolor}text-align:center;'>".$xocp_vars['month_year_short'][$month_id]."</td>"
                        . "<td style='${bgcolor}text-align:right;'>".toMoney($current_achievement)."</td>"
                        . "<td style='${bgcolor}'>".htmlentities($root_cause)."</td>"
                        . "<td style='${bgcolor}'>".htmlentities($improvement_text)."</td>"
                        . "</tr>";
                  
                  $last_ap = ($actionplan_group_id>0?$actionplan_group_id:$actionplan_id);
                  
                  $ttl_achievement = $ttl_achievement+$current_achievement;
                  $cnt++;
                  
                  //$_SESSION["pica"]["$employee_id-$pms_objective_id"] = array($employee_id,$pms_objective_id,"$actionplan_text",$current_achievement,$month_id);
                  $ap_month_cnt++;
               }
            }
            
            if($div>0) {
               $ap_ach = _bctrim(bcdiv($ttl_ap_achievement,$div));
               $ap_ach_ttl = bcadd($ap_ach_ttl,$ap_ach);
            }
            
            ///list($actionplan_id,$actionplan_text,$target_text,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,$month_idx,$target_achievement,$current_achievement)=$aptextarr[$no];
            
            
         }
         
         
         if(count($ap_report_final)>0) {
            $achievement = translate_norm(100*$ap_ach_ttl/count($ap_report_final));
            $ret .= "<tr><td colspan='4' style='background-color:#eef;text-align:center;font-weight:bold;padding:5px;'>Achievement = ".toMoney($ap_ach_ttl*100)."/".count($ap_report_final)." = ".toMoney(100*$ap_ach_ttl/count($ap_report_final))." (Norm = ".toMoney($achievement).")</td></tr>";
         } else {
            $achievement = -999;
            $ret .= "<tr><td colspan='4'>No report approved yet.</td></tr>";
         }
         
         
         
         
      } else {
         $ret .= "<tr><td colspan='4'>No actionplan.</td></tr>";
      }
      
      
      $ret .= "</tbody></table>";
      return array(1,1,$ret);
      
   }
      
   function app_drillDownPICAX($args) {
      include_once(XOCP_DOC_ROOT."/modules/pms/include/pms.php");
      $db=&Database::getInstance();
      $psid = $_SESSION["pms_psid"];
      $employee_id = $args[0];
      $pms_objective_idx = $args[1];
      global $xocp_vars;
      
      $sql = "SELECT month_id,actionplan_text,root_cause,improvement_text,current_achievement,current_kpi_achievement,target_achievement,repeat_type,actionplan_group_id"
           . " FROM pms_pic_action"
           . " WHERE psid = '$psid' AND pms_objective_id = '$pms_objective_idx'"
           . " AND employee_id = '$employee_id'"
           . " AND report_approval_st = 'final'"
           . " ORDER BY month_id,actionplan_id";
      $result = $db->query($sql);
      $ttl_achievement = 0;
      $cnt = 0;
      $ret = "<table class='xxlist'><colgroup><col/><col width='100'/><col/><col width='100'/><col width='100'/></colgroup>"
           . "<thead>";
      $ret .= "<tr>"
            . "<td>Month</td>"
            . "<td>Action Plan</td>"
            . "<td>Ach.</td>"
            . "<td>Root Cause</td>"
            . "<td>Improvement</td>"
            . "</tr>";
      $ret .= "</thead><tbody>";
      if($db->getRowsNum($result)>0) {
         while(list($month_id,$actionplan_text,$root_cause,$improvement_text,$current_achievement,$current_kpi_achievement,$target_achievement,$repeat_type,$actionplan_group_id)=$db->fetchRow($result)) {
            if($repeat_type>0&&$actionplan_group_id==0) continue;
            if($current_achievement<91) {
               $bgcolor = "background-color:#ffcccc;";
            } else {
               $bgcolor = "";
            }
            $ret .= "<tr>"
                  . "<td style='${bgcolor}text-align:center;'>".$xocp_vars['month_year_short'][$month_id]."</td>"
                  . "<td style='${bgcolor}'>".htmlentities($actionplan_text)."</td>"
                  . "<td style='${bgcolor}text-align:right;'>".toMoney($current_achievement)."</td>"
                  . "<td style='${bgcolor}'>".htmlentities($root_cause)."</td>"
                  . "<td style='${bgcolor}'>".htmlentities($improvement_text)."</td>"
                  . "</tr>";
            $ttl_achievement = $ttl_achievement+$current_achievement;
            $cnt++;
         }
         $ret .= "<tr><td colspan='2'>Calculation:</td><td colspan='5'>".toMoney($ttl_achievement)."/$cnt = ".toMoney($ttl_achievement/$cnt)." (Norm = ".toMoney(translate_norm($ttl_achievement/$cnt)).")</td></tr>";
      } else {
         $ret .= "<tr><td>No report approved yet.</td></tr>";
      }
      $ret .= "</tbody></table>";
      return array(1,1,$ret);
   }
   
   function app_drillDownOrg($args) {
      require_once(XOCP_DOC_ROOT."/modules/pms/pmsmonitor.php");
      require_once(XOCP_DOC_ROOT."/modules/pms/include/pms.php");
      $db=&Database::getInstance();
      $psid = $_SESSION["pms_psid"];
      $org_id = $args[0];
      $pms_objective_idx = $args[1];
      
      $sql = "SELECT org_class_id FROM ".XOCP_PREFIX."orgs WHERE org_id = '$org_id'";
      $rc = $db->query($sql);
      list($org_class_id)=$db->fetchRow($rc);
      
      switch($org_class_id) {
         case 4:
         case 5:
            
            $gaugetip = "<table class='xxlist'><tbody>";
            $sql = "SELECT pms_actionplan_id,pms_actionplan_text FROM pms_actionplan WHERE pms_objective_id = '$pms_objective_idx' GROUP BY pms_objective_id";
            $resultx1 = $db->query($sql);
            if($db->getRowsNum($resultx1)>0) { ////// yes, has ap
               
               while(list($pms_actionplan_id,$actionplan_text)=$db->fetchRow($resultx1)) {
                  $sql = "SELECT SUM(pms_share_weight),pms_actionplan_pic_employee_id FROM pms_actionplan_share"
                       . " WHERE psid = '$psid' AND pms_objective_id = '$pms_objective_idx'"
                       . " GROUP BY pms_actionplan_pic_employee_id";
                  $rsh = $db->query($sql);
                  $ttl_pic_ach = 0;
                  $ttl_pic_ach_weight = 0;
                  
                  $gaugetip .= "<tr><td style='font-weight:bold;background-color:#eee;vertical-align:top;'></td>";
                  $gaugetip .= "<td style='font-weight:bold;background-color:#eee;text-align:right;vertical-align:top;'>a</td>";
                  $gaugetip .= "<td style='font-weight:bold;background-color:#eee;text-align:right;vertical-align:top;'>b</td>";
                  $gaugetip .= "<td style='font-weight:bold;background-color:#eee;text-align:right;vertical-align:top;'>a x b</td>";
                  $gaugetip .= "</tr>";
                  
                  if($db->getRowsNum($rsh)>0) {
                     while(list($pms_share_weightxxx,$pms_actionplan_pic_employee_idxxx)=$db->fetchRow($rsh)) {
                        list($pic_ach,$_kpi_pic_ach) = _pms_calcPeopleAch($pms_actionplan_pic_employee_idxxx,$pms_objective_idx,TRUE,$current_radar_month);
                        
                        if($pic_ach==-999) {
                        }
                        
                        if($pic_ach>=70) {
                           $bgcolor = "background-color:#dfd;";
                        } else if($pic_ach>=60) {
                           $bgcolor = "background-color:#ffd;";
                        } else {
                           if($pic_ach!=-999) {
                              $bgcolor = "background-color:#fdd;";
                           } else {
                              $bgcolor = "";
                           }
                        }
                        
                        $sql = "SELECT b.person_nm,a.alias_nm FROM ".XOCP_PREFIX."employee a"
                             . " LEFT JOIN ".XOCP_PREFIX."persons b USING(person_id)"
                             . " WHERE a.employee_id = '$pms_actionplan_pic_employee_idxxx'";
                        $re = $db->query($sql);
                        list($employee_nmx,$alias_nm)=$db->fetchRow($re);
                        $gaugetip .= "<tr><td style='${bgcolor}vertical-align:top;'>"
                                   . "<span class='xlnk' onclick='drill_down_pica(\"$pms_actionplan_pic_employee_idxxx\",\"$pms_objective_idx\",this,event);'>".htmlentities($employee_nmx)."</span>"
                                   . "</td>";
                        $gaugetip .= "<td style='${bgcolor}text-align:right;vertical-align:top;'>".($pic_ach==-999?"-":toMoney($pic_ach)."%")."</td>";
                        $gaugetip .= "<td style='${bgcolor}text-align:right;vertical-align:top;'>".toMoney($pms_share_weightxxx)."</td>";
                        $gaugetip .= "<td style='${bgcolor}text-align:right;vertical-align:top;'>".($pic_ach==-999?"":toMoney($pms_share_weightxxx*$pic_ach))."</td>";
                        $gaugetip .= "</tr>";
                        
                        if($pic_ach==-999) {
                           continue;
                        }
                        
                        $ttl_pic_ach = _bctrim(bcadd($ttl_pic_ach,($pms_share_weightxxx*$pic_ach)));
                        $ttl_pic_ach_weight = _bctrim(bcadd($ttl_pic_ach_weight,$pms_share_weightxxx));
                        
                     }
                  }
                  
                  $gaugetip .= "<tr><td style='font-weight:bold;background-color:#eee;vertical-align:top;' colspan='2'>Total</td>";
                  $gaugetip .= "<td style='font-weight:bold;background-color:#eee;text-align:right;vertical-align:top;'>".toMoney($ttl_pic_ach_weight)."</td>";
                  $gaugetip .= "<td style='font-weight:bold;background-color:#eee;text-align:right;vertical-align:top;'>".toMoney($ttl_pic_ach)."</td>";
                  $gaugetip .= "</tr>";
                  
                  $gaugetip .= "<tr><td style='color:#000;font-weight:bold;background-color:#ddd;vertical-align:top;'>Result</td>";
                  $gaugetip .= "<td style='color:#000;font-weight:bold;background-color:#ddd;text-align:right;vertical-align:top;' colspan='3'>".toMoney($ttl_pic_ach)." / ".toMoney($ttl_pic_ach_weight)." = ".toMoney(bcdiv($ttl_pic_ach,$ttl_pic_ach_weight))."</td>";
                  $gaugetip .= "</tr>";
                  
               }
               
            }
                  
            $gaugetip .= "</tbody></table>";
                  
            
            
            
            return array($org_id,$pms_objective_idx,$gaugetip);
            break;
         case 1:
         case 2:
         case 3:
         default:
            
            $gaugetip .= "<div style='text-align:right;'>[<span class='ylnk' onclick='tooltip_view_pica(\"$org_id\",\"$pms_objective_idx\",this,event);'>PICA</span>]</div><table class='xxlist'><tbody><tr>"
                       . "<td style='background-color:#eee;'></td>"
                       . "<td style='background-color:#eee;'></td>"
                       . "<td style='text-align:right;vertical-align:top;font-weight:bold;background-color:#eee;'>a</td>"
                       . "<td style='text-align:right;vertical-align:top;font-weight:bold;background-color:#eee;'>b</td>"
                       . "<td style='text-align:right;vertical-align:top;font-weight:bold;background-color:#eee;'>a x b</td></tr>";
            
            
            $sql = "SELECT a.pms_objective_id,a.pms_org_id,a.pms_objective_weight,c.pms_perspective_code,a.pms_objective_no,a.pms_objective_text"
                 . " FROM pms_objective a"
                 . " LEFT JOIN pms_objective b ON b.pms_objective_id = a.pms_parent_objective_id"
                 . " LEFT JOIN pms_perspective c ON c.pms_perspective_id = a.pms_perspective_id"
                 . " LEFT JOIN ".XOCP_PREFIX."orgs d ON d.org_id = a.pms_org_id"
                 . " WHERE a.psid = '$psid' AND a.pms_parent_objective_id = '$pms_objective_idx'"
                 . " AND a.pms_org_id = '$org_id'"
                 . " AND b.pms_org_id = a.pms_org_id";
            $rchild = $db->query($sql);
            $has_local_sub = 0;
            $ttl_sub_weight = 0;
            $initiative = $initiative_weight = $initiative_code = array();
            if($db->getRowsNum($rchild)>0) {
               while(list($sub_pms_objective_id,$sub_pms_org_id,$sub_weight,$pms_perspective_code,$pms_objective_no,$sub_pms_objective_text)=$db->fetchRow($rchild)) {
                  $has_local_sub++;
                  $ttl_sub_weight = _bctrim(bcadd($ttl_sub_weight,$sub_weight));
                  $initiative[$sub_pms_objective_id] = 1;
                  $initiative_weight[$sub_pms_objective_id] = $sub_weight;
                  $initiative_code[$sub_pms_objective_id] = "${pms_perspective_code}${pms_objective_no}";
                  $initiative_text[$sub_pms_objective_id] = $sub_pms_objective_text;
               }
            }
            
            if($has_local_sub>0) { ///// yes, has initiative
               $initiative_ttl = 0;
               $initiative_ttl_cnt = 0;
               $initiative_ttl_weight = 0;
               $sub_pms_org_id = $org_id;
               $sub_org_nm = "Initiative";
               foreach($initiative as $sub_pms_objective_id=>$v) {
                  list($ach_initiative,$_kpi_ach_initiative) = _pms_calcSO($sub_pms_objective_id,$report_ytd,$report_month_id);
                  
                  if($ach_initiative!=-999) { //// not empty
                     $initiative_ttl = bcadd($initiative_ttl,($ach_initiative*$initiative_weight[$sub_pms_objective_id]));
                     $initiative_ttl_cnt++;
                     $initiative_ttl_weight = _bctrim(bcadd($initiative_ttl_weight,$initiative_weight[$sub_pms_objective_id]));
                  }
                  
                  
                  if($ach_initiative>=70) {
                     $bgcolor = "background-color:#dfd;";
                  } else if($ach_initiative>=60) {
                     $bgcolor = "background-color:#ffd;";
                  } else {
                     if($ach_initiative!=-999) {
                        $bgcolor = "background-color:#fdd;";
                     } else {
                        $bgcolor = "";
                     }
                  }
                  
            
            
            
                  $gaugetip .= "<tr id='trtip_${sub_pms_org_id}_${sub_pms_objective_id}'><td style='${bgcolor}vertical-align:top;'>"
                             . "<span class='xlnk' onclick='drill_down_org(\"$sub_pms_org_id\",\"$sub_pms_objective_id\",this,event);'>".htmlentities($sub_org_nm)."</span>"
                             . "</td>";
                  $gaugetip .= "<td style='${bgcolor}text-align:left;vertical-align:top;'>"
                             . "<span style='font-weight:bold;color:black;'>".$initiative_code[$sub_pms_objective_id]."</span> "
                             . htmlentities($initiative_text[$sub_pms_objective_id])."</td>";
                  $gaugetip .= "<td style='${bgcolor}text-align:right;vertical-align:top;'>"
                             //. toMoney($ach_sub_so)
                             . ($ach_initiative==-999?"-":toMoney($ach_initiative)."%")
                             . "</td>";
                  $gaugetip .= "<td style='${bgcolor}text-align:right;vertical-align:top;'>"
                             . toMoney($initiative_weight[$sub_pms_objective_id])
                             . "</td>";
                  $gaugetip .= "<td style='${bgcolor}text-align:right;vertical-align:top;'>"
                             . ($ach_initiative==-999?"-":toMoney($ach_initiative*$initiative_weight[$sub_pms_objective_id]))
                             . "</td>";
                  $gaugetip .= "</tr>";
                  
                  
               }
               
               $ttl_share += $initiative_ttl_weight;
               $ttl_ach_sub_so_weight += $initiative_ttl;
               
            } else {
               
               
               
               $kpi_share_array = array();
               $xkpi_share = array();
               $sql = "SELECT pms_kpi_id FROM pms_kpi WHERE pms_objective_id = '$pms_objective_idx'";
               $resultkpi = $db->query($sql);
               
               
               if($db->getRowsNum($resultkpi)>0) { /// yes, has kpi
                  
                  
                  while(list($pms_kpi_id)=$db->fetchRow($resultkpi)) {
                     
                     
                     $sql = "SELECT pms_share_org_id,pms_share_weight FROM pms_kpi_share WHERE psid = '$psid' AND pms_objective_id = '$pms_objective_idx' AND pms_kpi_id = '$pms_kpi_id'";
                     $rkpishare = $db->query($sql);
                     if($db->getRowsNum($rkpishare)>0) {
                        while(list($pms_share_org_idx,$pms_share_weightx)=$db->fetchRow($rkpishare)) {
                           //if(isset($child_org[$pms_share_org_idx])&&$child_org[$pms_share_org_idx]==1) {
                              $kpi_share_ttl += $pms_share_weightx;
                              $kpi_share_array[$pms_share_org_idx] = _bctrim(bcadd($kpi_share_array[$pms_share_org_idx],$pms_share_weightx));
                              $xkpi_share[$pms_objective_idx][$pms_kpi_id][$pms_share_org_idx] = $pms_share_weightx;
                           //}
                        }
                     }
                  }
               }
               
               $ttl_share = 0;
               $ttl_ach_sub_so_weight = 0;
               
               
               foreach($kpi_share_array as $pms_share_org_idx=>$pms_share_weightx) {
                  
                  
                  //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                  $sql = "SELECT a.pms_objective_id,a.pms_objective_text,a.pms_org_id,b.org_nm,c.pms_perspective_code,a.pms_objective_no"
                       . " FROM pms_objective a"
                       . " LEFT JOIN pms_perspective c USING(pms_perspective_id)"
                       . " LEFT JOIN ".XOCP_PREFIX."orgs b ON b.org_id = a.pms_org_id"
                       . " WHERE a.pms_parent_objective_id = '$pms_objective_idx'"
                       . " AND a.pms_org_id = '$pms_share_org_idx'"
                       . " ORDER BY c.order_no,a.pms_objective_no";
                  $rorg = $db->query($sql);
                  if($db->getRowsNum($rorg)>0) {
                     while(list($sub_pms_objective_id,$sub_pms_objective_text,$sub_pms_org_id,$sub_org_nm,$pms_perspective_code,$pms_objective_no)=$db->fetchRow($rorg)) {
                        
                        list($ach_sub_so,$_kpi_ach_sub_so) = _pms_calcSO($sub_pms_objective_id,TRUE,$current_radar_month);
                        
                        if($ach_sub_so!=-999) {
                           $ach_sub_so_weight = ($pms_share_weightx*$ach_sub_so);
                           $ttl_ach_sub_so_weight += $ach_sub_so_weight;
                        }
                        
                        
                        if($ach_sub_so>=70) {
                           $bgcolor = "background-color:#dfd;";
                        } else if($ach_sub_so>=60) {
                           $bgcolor = "background-color:#ffd;";
                        } else {
                           if($ach_sub_so!=-999) {
                              $bgcolor = "background-color:#fdd;";
                           } else {
                              $bgcolor = "";
                           }
                        }
                        
                        $gaugetip .= "<tr id='trtip_${sub_pms_org_id}_${sub_pms_objective_id}'><td style='${bgcolor}vertical-align:top;'>"
                                   . "<span class='xlnk' onclick='drill_down_org(\"$sub_pms_org_id\",\"$sub_pms_objective_id\",this,event);'>".htmlentities($sub_org_nm)."</span>"
                                   . "</td>";
                        $gaugetip .= "<td style='${bgcolor}text-align:left;vertical-align:top;'>"
                                   . "<span style='font-weight:bold;color:black;'>${pms_perspective_code}${pms_objective_no}</span> "
                                   . htmlentities($sub_pms_objective_text)."</td>";
                        $gaugetip .= "<td style='${bgcolor}text-align:right;vertical-align:top;'>"
                                   //. toMoney($ach_sub_so)
                                   . ($ach_sub_so==-999?"-":toMoney($ach_sub_so)."%")
                                   . "</td>";
                        $gaugetip .= "<td style='${bgcolor}text-align:right;vertical-align:top;'>"
                                   . toMoney($pms_share_weightx)
                                   . "</td>";
                        $gaugetip .= "<td style='${bgcolor}text-align:right;vertical-align:top;'>"
                                   . ($ach_sub_so==-999?"-":toMoney($ach_sub_so_weight))
                                   . "</td>";
                        $gaugetip .= "</tr>";
                        
                        if($ach_sub_so!=-999) $ttl_share += $pms_share_weightx;
                     }
                  }
               }
            
            
            }
            
            
            $gaugetip .= "<tr>"
                       . "<td style='font-weight:bold;background-color:#eee;' colspan='3'>Total</td>"
                       . "<td style='font-weight:bold;background-color:#eee;text-align:right;vertical-align:top;'>".toMoney($ttl_share)."</td>"
                       . "<td style='font-weight:bold;background-color:#eee;text-align:right;vertical-align:top;'>".toMoney($ttl_ach_sub_so_weight)."</td></tr>";
            
            $gaugetip .= "<tr>"
                       . "<td style='font-weight:bold;background-color:#ddd;color:#000;'>Result</td>"
                       . "<td style='font-weight:bold;background-color:#ddd;color:#000;text-align:right;vertical-align:top;' colspan='4'>".toMoney($ttl_ach_sub_so_weight)." / ".toMoney($ttl_share)." = ".toMoney($ttl_ach_sub_so_weight/$ttl_share)."</td></tr>";
            $gaugetip .= "</tbody></table>";
            
            return array($org_id,$pms_objective_idx,$gaugetip);
            
            
            break;
      
      }
   }
}

} /// HRIS_PMSMONITORAJAX_DEFINED
?>