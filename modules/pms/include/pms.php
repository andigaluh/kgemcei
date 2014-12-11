<?php
if ( !defined('HRIS_INCLUDEPMS_DEFINED') ) {
   define('HRIS_INCLUDEPMS_DEFINED', TRUE);

global $norm_color;

$norm_color[100] = "#aaffaa";
$norm_color[70] = "#ffffaa";
$norm_color[60] = "#ffdddd";

global $pms_ach_range;

$pms_ach_range = array(0,82,90,100,120);

global $allow_deploy,$allow_add_objective,$allow_add_initiative;
global $allow_actionplan;

$allow_add_objective[1] = 1;
$allow_add_objective[2] = 0;
$allow_add_objective[3] = 0;
$allow_add_objective[4] = 0;
$allow_add_objective[5] = 0;

$allow_edit_objective[1] = 1;
$allow_edit_objective[2] = 1;
$allow_edit_objective[3] = 1;
$allow_edit_objective[4] = 0;
$allow_edit_objective[5] = 0;

$allow_add_initiative[1] = 1;
$allow_add_initiative[2] = 1;
$allow_add_initiative[3] = 1;
$allow_add_initiative[4] = 0;
$allow_add_initiative[5] = 0;

$allow_add_kpi[1] = 1;
$allow_add_kpi[2] = 1;
$allow_add_kpi[3] = 1;
$allow_add_kpi[4] = 0;
$allow_add_kpi[5] = 0;

$allow_edit_kpi[1] = 1;
$allow_edit_kpi[2] = 1;
$allow_edit_kpi[3] = 1;
$allow_edit_kpi[4] = 0;
$allow_edit_kpi[5] = 0;

$allow_actionplan[1] = 0;
$allow_actionplan[2] = 0;
$allow_actionplan[3] = 0;
$allow_actionplan[4] = 1;
$allow_actionplan[5] = 0;

   function translate_norm($val) {
      
      //return $val;
      
      if($val>=115) return 100;
      if($val>=114) return 99;
      if($val>=113) return 98;
      if($val>=112) return 97;
      if($val>=111) return 96;
      if($val>=110) return 95;
      if($val>=109) return 94;
      if($val>=108) return 93;
      if($val>=107) return 92;
      if($val>=106) return 91;
      if($val>=105) return 90;
      if($val>=104) return 89;
      if($val>=104) return 88;
      if($val>=103) return 87;
      if($val>=103) return 86;
      if($val>=102) return 85;
      if($val>=102) return 84;
      if($val>=102) return 83;
      if($val>=101) return 82;
      if($val>=101) return 81;
      if($val>=101) return 80;
      if($val>=100) return 79;
      if($val>=99) return 78;
      if($val>=98) return 77;
      if($val>=97) return 76;
      if($val>=96) return 75;
      if($val>=95) return 74;
      if($val>=94) return 73;
      if($val>=93) return 72;
      if($val>=92) return 71;
      if($val>=91) return 70;
      if($val>=90) return 69;
      if($val>=89) return 68;
      if($val>=88) return 67;
      if($val>=87) return 65;
      if($val>=86) return 64;
      if($val>=85) return 63;
      if($val>=84) return 62;
      if($val>=83) return 60;
      if($val>=82) return 59;
      if($val>=81) return 58;
      if($val>=80) return 57;
      if($val>=79) return 56;
      if($val>=78) return 55;
      if($val>=77) return 54;
      if($val>=76) return 53;
      if($val>=75) return 52;
      
      return 52;
      
   }   
   
   function _pms_listPICA($pms_objective_id) {
      $db=&Database::getInstance();
      _pms_recurse_get_PICA($pms_objective_id);
   }
   
   function _pms_recurse_get_PICA($pms_objective_id) {
      $psid = $_SESSION["pms_psid"];
      $db=&Database::getInstance();
      
      //////////////////
      //// so start ////
      //////////////////
      
      $sql = "SELECT a.pms_org_id,a.pms_objective_weight,a.pms_perspective_id,b.pms_perspective_code,a.pms_objective_no"
           . " FROM pms_objective a LEFT JOIN pms_perspective b USING(pms_perspective_id) WHERE a.psid = '$psid' AND a.pms_objective_id = '$pms_objective_id'";
      $result = $db->query($sql);
      list($pms_org_id,$pms_objective_weight,$pms_perspective_id,$pms_perspective_code,$pms_objective_no)=$db->fetchRow($result);
      
      $sql = "SELECT org_id FROM ".XOCP_PREFIX."orgs WHERE parent_id = '$pms_org_id'";
      $result = $db->query($sql);
      $child_org = array();
      if($db->getRowsNum($result)>0) {
         while(list($org_idx)=$db->fetchRow($result)) {
            $child_org[$org_idx] = 1;
         }
      }
      
      
      ////////////////////////
      //// cek initiative ////
      ////////////////////////
      
      $sql = "SELECT a.pms_objective_id,a.pms_org_id,a.pms_objective_weight"
           . " FROM pms_objective a"
           . " LEFT JOIN pms_objective b ON b.pms_objective_id = a.pms_parent_objective_id"
           . " WHERE a.psid = '$psid' AND a.pms_parent_objective_id = '$pms_objective_id'"
           . " AND a.pms_org_id = '$pms_org_id'"
           . " AND b.pms_org_id = a.pms_org_id";
      $rchild = $db->query($sql);
      $has_local_sub = 0;
      $ttl_sub_weight = 0;
      $initiative = $initiative_weight = array();
      if($db->getRowsNum($rchild)>0) {
         while(list($sub_pms_objective_id,$sub_pms_org_id,$sub_weight)=$db->fetchRow($rchild)) {
            $has_local_sub++;
            $initiative[$sub_pms_objective_id] = 1;
         }
      }
      
      /////////////////////////
      //// has initiative? ////
      /////////////////////////
      
      if($has_local_sub>0) { ///// yes, has initiative
         foreach($initiative as $sub_pms_objective_id=>$v) {
            _pms_recurse_get_PICA($sub_pms_objective_id);
         }
         
      } else { /////////////////// no
         
         
         /////////////////////////////////////////////////////////////
         //// KPI .... achievement of sub organization ///////////////
         /////////////////////////////////////////////////////////////
         
         //////////////////
         //// has kpi? ////
         //////////////////
         
         $sql = "SELECT pms_kpi_id FROM pms_kpi WHERE pms_objective_id = '$pms_objective_id'";
         $result = $db->query($sql);
         if($db->getRowsNum($result)>0) { /// yes, has kpi
            
            $kpi_share_array = array();
            
            while(list($pms_kpi_id)=$db->fetchRow($result)) {
               
               /////////////////////
               //// has sub so? ////
               /////////////////////
               $sql = "SELECT pms_share_org_id,pms_share_weight FROM pms_kpi_share WHERE psid = '$psid' AND pms_objective_id = '$pms_objective_id' AND pms_kpi_id = '$pms_kpi_id'";
               $rkpishare = $db->query($sql);
               if($db->getRowsNum($rkpishare)>0) {
                  while(list($pms_share_org_idx,$pms_share_weightx)=$db->fetchRow($rkpishare)) {
                     if(isset($child_org[$pms_share_org_idx])&&$child_org[$pms_share_org_idx]==1) {
                        $kpi_share_ttl += $pms_share_weightx;
                        $kpi_share_array[$pms_share_org_idx] = _bctrim(bcadd($kpi_share_array[$pms_share_org_idx],$pms_share_weightx));
                     }
                  }
               }
            }
            
            ///////////////////////////////////
            //// calculate share cascading ////
            ///////////////////////////////////
            foreach($kpi_share_array as $pms_share_org_idx=>$share_weightx) {
               //// cascading ...
               $sql = "SELECT pms_objective_id,pms_objective_weight FROM pms_objective"
                    . " WHERE psid = '$psid' AND pms_parent_objective_id = '$pms_objective_id'"
                    . " AND pms_org_id = '$pms_share_org_idx'";
               $rcascading = $db->query($sql);
               $cno = 0;
               if($db->getRowsNum($rcascading)>0) { ////////// yes, has sub so
                  while(list($sub_pms_objective_id,$sub_pms_objective_weight)=$db->fetchRow($rcascading)) {
                     _pms_recurse_get_PICA($sub_pms_objective_id);
                  }
               }
            }
         }
         
         
         /////////////////////////////////////////////////////////////
         //// Action Plan .... achievement of peaple  ////////////////
         /////////////////////////////////////////////////////////////
         
         $ap_cnt = 0;
         
         /////////////////
         //// has ap? ////
         /////////////////
         
         $sql = "SELECT pms_actionplan_id,pms_actionplan_text FROM pms_actionplan WHERE pms_objective_id = '$pms_objective_id' GROUP BY pms_objective_id";
         $result = $db->query($sql);
         if($db->getRowsNum($result)>0) { ////// yes, has ap
            $ap_cnt++;
            
            while(list($pms_actionplan_id,$actionplan_text)=$db->fetchRow($result)) {
               $sql = "SELECT SUM(pms_share_weight),pms_actionplan_pic_employee_id FROM pms_actionplan_share"
                    . " WHERE psid = '$psid' AND pms_objective_id = '$pms_objective_id'"
                    . " GROUP BY pms_actionplan_pic_employee_id";
               $rsh = $db->query($sql);
               $pic_share = array();
               $pic_ach = 0;
               $ttl_pic_share = 0;
               $ttl_pic_ach = 0;
               $__kpi_ttl_pic_share = 0;
               $__kpi_ttl_pic_ach = 0;
               if($db->getRowsNum($rsh)>0) {
                  while(list($pms_share_weightxxx,$pms_actionplan_pic_employee_idxxx)=$db->fetchRow($rsh)) {
                     _pms_people_PICA($pms_actionplan_pic_employee_idxxx,$pms_objective_id);
                  }
               }
            }
            
         } else {
         }
      }
      
   }
   
   function _pms_people_PICA($employee_id,$pms_objective_id) {
      $db=&Database::getInstance();
      $psid = $_SESSION["pms_psid"];
      global $xocp_vars;
      
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
            list($no,$repeat_typex) = $apno[$root_actionplan_id];
            $picaarr[$no][$month_id] = array($actionplan_id,$actionplan_text,$target_text,$current_achievement,$order_no,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,$pica_id,$is_pica,$target_achievement,$report_approval_st,$month_id);
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
                  
                  if($current_achievement<91) { //// is pica
                     $bgcolor = "background-color:#ffcccc;";
                     $_SESSION["pms_pica"][$month_id][$employee_id][$actionplan_id] = array($actionplan_text,$current_achievement,$root_cause,$improvement_text);
                  } else {
                     $bgcolor = "";
                  }
                  
                  if($last_ap!=($actionplan_group_id>0?$actionplan_group_id:$actionplan_id)) {
                     $apxno++;
                  }
                  
                  $ttl_achievement = $ttl_achievement+$current_achievement;
                  $cnt++;
                  
                  $ap_month_cnt++;
               }
            }
            
            
         }
         
         
      }
      
      
   }
   
   
   function _pms_calcPeopleAch($employee_id,$pms_objective_id,$report_ytd=TRUE,$report_month_id=0) {
      $db=&Database::getInstance();
      
      //////////// query all action plan first //////////////////////////
      
      $sql = "SELECT actionplan_id,actionplan_text,target_text,current_achievement,current_kpi_achievement,order_no,approval_st,submit_dttm,approval1_dttm,approval1_employee_id,"
           . "pica_id,is_pica,target_achievement,report_approval_st,month_id,repeat_type"
           . " FROM pms_pic_action"
           . " WHERE pms_objective_id = '$pms_objective_id'"
           . " AND employee_id = '$employee_id'"
           . " AND is_pica = '0'"
           . " AND actionplan_group_id = '0'"
           . " ORDER BY month_id,order_no";
      $resultx = $db->query($sql);
      $aparr = array();
      $apno = array();
      $no = 0;
      if($db->getRowsNum($resultx)>0) {
         while(list($actionplan_id,$actionplan_text,$target_text,$current_achievement,$current_kpi_achievement,$order_no,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,
                    $pica_id,$is_pica,$target_achievement,$report_approval_st,$month_id,$repeat_type)=$db->fetchRow($resultx)) {
            $aparr[$no][$month_id] = array($actionplan_id,$actionplan_text,$target_text,$current_achievement,$current_kpi_achievement,$order_no,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,$pica_id,$is_pica,$target_achievement,$report_approval_st,$month_id);
            $apno[$actionplan_id] = array($no,$repeat_type);
            $no++;
         }
      }
      
      $gaparr = array();
      $gapno = array();
      foreach($apno as $actionplan_group_id=>$vv) {
         list($gno,$repeat_typexxx)=$vv;
         $sql = "SELECT actionplan_id,actionplan_text,target_text,current_achievement,current_kpi_achievement,order_no,approval_st,submit_dttm,approval1_dttm,approval1_employee_id,"
              . "pica_id,is_pica,target_achievement,report_approval_st,month_id"
              . " FROM pms_pic_action"
              . " WHERE pms_objective_id = '$pms_objective_id'"
              . " AND employee_id = '$employee_id'"
              . " AND is_pica = '0'"
              . " AND actionplan_group_id = '$actionplan_group_id'"
              . " ORDER BY month_id,order_no";
         $resultx = $db->query($sql);
         
         if($db->getRowsNum($resultx)>0) {
            while(list($actionplan_id,$actionplan_text,$target_text,$current_achievement,$current_kpi_achievement,$order_no,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,
                       $pica_id,$is_pica,$target_achievement,$report_approval_st,$month_id)=$db->fetchRow($resultx)) {
               $gaparr[$gno][$month_id] = array($actionplan_id,$actionplan_text,$target_text,$current_achievement,$current_kpi_achievement,$order_no,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,$pica_id,$is_pica,$target_achievement,$report_approval_st,$month_id,$actionplan_group_id);
               $gapno[$actionplan_id] = $gno;
               
               /// unset to display
               unset($aparr[$gno][$month_id]);
            }
         }
      }
      
      
      $sql = "SELECT actionplan_id,actionplan_text,target_text,current_achievement,current_kpi_achievement,order_no,approval_st,submit_dttm,approval1_dttm,approval1_employee_id,"
           . "pica_id,is_pica,target_achievement,report_approval_st,month_id,root_cause"
           . " FROM pms_pic_action"
           . " WHERE pms_objective_id = '$pms_objective_id'"
           . " AND employee_id = '$employee_id'"
           . " AND is_pica = '1'"
           . " AND actionplan_group_id = '0'"
           . " ORDER BY month_id,order_no";
      $resultx = $db->query($sql);
      if($db->getRowsNum($resultx)>0) {
         while(list($actionplan_id,$actionplan_text,$target_text,$current_achievement,$current_kpi_achievement,$order_no,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,
                    $pica_id,$is_pica,$target_achievement,$report_approval_st,$month_id,$root_cause)=$db->fetchRow($resultx)) {
            $root_actionplan_id = _pms_getpicaroot($pms_objective_id,$employee_id,$actionplan_id);
            if(1) {
               list($no,$repeat_typex) = $apno[$root_actionplan_id];
               $picaarr[$no][$month_id] = array($actionplan_id,$actionplan_text,$target_text,$current_achievement,$current_kpi_achievement,$order_no,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,$pica_id,$is_pica,$target_achievement,$report_approval_st,$month_id);
            }
         }
      }
      
      $ap_report_final = array();
      
      if(count($apno)>0) {
         
         $ap_cnt = count($apno);
         $ap_ach_ttl = 0;
         $ap_month_cnt = 0;
         $kpi_ach_ttl = 0;
         $kpi_month_cnt = 0;
         
         foreach($apno as $actionplan_idx=>$v) {
            list($no,$repeat_type)=$v;
            
            if($repeat_type==0) {
               $div = 100;
            } else {
               $div = 0;
            }
            
            /// $report_ytd=TRUE,$report_month_id=0
            if($report_ytd==FALSE&&$report_month_id>0) {
               if($_SESSION["pmsmonitor_radar_ytd"]==TRUE) {
                  $month_id_start = 1; //$report_month_id;
               } else {
                  $month_id_start = $report_month_id;
               }
               $month_id_stop = $report_month_id;
            } else {
               if($report_month_id>0) {
                  $month_id_stop = $report_month_id;
               } else {
                  $month_id_stop = 12;
               }
               $month_id_start = 1;
            }
            
            $ttl_ap_achievement = 0;
            $ttl_kpi_achievement = 0;
            //// row by row calculation first (carry over calculation)
            for($month_id=$month_id_start;$month_id<=$month_id_stop;$month_id++) {
               
               $report_approval_st = "";
               $current_achievement = 0;
               $current_kpi_achievement = 0;
               
               $actionplan_group_id = 0;
               
               if($repeat_type==0&&isset($aparr[$no][$month_id])) {
                  list($actionplan_id,$actionplan_text,$target_text,$current_achievement,$current_kpi_achievement,$order_no,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,$pica_id,$is_pica,$target_achievement,$report_approval_st,$month_idx)=$aparr[$no][$month_id];
               } else if($repeat_type==0&&isset($picaarr[$no][$month_id])) {
                  list($actionplan_id,$actionplan_text,$target_text,$current_achievement,$current_kpi_achievement,$order_no,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,$pica_id,$is_pica,$target_achievement,$report_approval_st,$month_idx)=$picaarr[$no][$month_id];
               } else if(isset($gaparr[$no][$month_id])) {
                  list($actionplan_id,$actionplan_text,$target_text,$current_achievement,$current_kpi_achievement,$order_no,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,$pica_id,$is_pica,$target_achievement,$report_approval_st,$month_idx,$actionplan_group_id)=$gaparr[$no][$month_id];
               } else {
                  if(isset($aparr[$no][$month_id])) {
                     list($actionplan_id,$actionplan_text,$target_text,$current_achievement,$current_kpi_achievement,$order_no,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,$pica_id,$is_pica,$target_achievement,$report_approval_st,$month_idx)=$aparr[$no][$month_id];
                  } else {
                     continue;
                  }
               }
               
               if($report_approval_st=="final") {
               
                  $ap_report_final[$actionplan_idx] = 1;
                  $ttl_ap_achievement = _bctrim(bcadd($ttl_ap_achievement,$current_achievement));
                  $ttl_kpi_achievement = _bctrim(bcadd($ttl_kpi_achievement,$current_kpi_achievement));
                  if($repeat_type!=0) {
                     $div += 100;
                  }
                  
                  
                  $ap_month_cnt++;
                }
               
               
            }
            
            if($div>0) {
               $ap_ach = _bctrim(bcdiv($ttl_ap_achievement,$div));
               $ap_ach_ttl = bcadd($ap_ach_ttl,$ap_ach);
               $kpi_ach = _bctrim(bcdiv($ttl_kpi_achievement,$div));
               $kpi_ach_ttl = bcadd($kpi_ach_ttl,$kpi_ach);
            }
            
         }
         
         
         if(count($ap_report_final)>0) {
            $achievement = translate_norm(100*$ap_ach_ttl/count($ap_report_final));
            $kpi_achievement = translate_norm(100*$kpi_ach_ttl/count($ap_report_final));
         } else {
            $achievement = -999;
            $kpi_achievement = -999;
         }
         
         if($pms_objective_id==1640) _debuglog("$kpi_achievement = 100 * $kpi_ach_ttl / ".count($ap_report_final));
         
         return array($achievement,$kpi_achievement);
      } else {
         return array(-999,-999);
      }
   }

   function _pms_calcYTD($pms_objective_id) {
      $psid = $_SESSION["pms_psid"];
      $db=&Database::getInstance();
      
      $sql = "SELECT pms_org_id,pms_objective_weight FROM pms_objective WHERE pms_objective_id = '$pms_objective_id'";
      $result = $db->query($sql);
      list($pms_org_id,$pms_objective_weight)=$db->fetchRow($result);
      
      $current_radar_month = $_SESSION["pmsmonitor_radar_month"];
      
      list($val,$_kpi_val) = _pms_calcSO($pms_objective_id,TRUE,$current_radar_month);
      
      
      return array($val,$_kpi_val);
   }
   
   
   function _pms_calcSO($pms_objective_id,$report_ytd=TRUE,$report_month_id=0) {
      $psid = $_SESSION["pms_psid"];
      $db=&Database::getInstance();
      
      ////////////////////
      //// initialize ////
      ////////////////////
      
      $achievement = 0;
      $__kpi_achievement = 0;
      
      //////////////////
      //// so start ////
      //////////////////
      
      $sql = "SELECT a.pms_org_id,a.pms_objective_weight,a.pms_perspective_id,b.pms_perspective_code,a.pms_objective_no"
           . " FROM pms_objective a LEFT JOIN pms_perspective b USING(pms_perspective_id) WHERE a.psid = '$psid' AND a.pms_objective_id = '$pms_objective_id'";
      $result = $db->query($sql);
      list($pms_org_id,$pms_objective_weight,$pms_perspective_id,$pms_perspective_code,$pms_objective_no)=$db->fetchRow($result);
      
      $sql = "SELECT org_id FROM ".XOCP_PREFIX."orgs WHERE parent_id = '$pms_org_id'";
      $result = $db->query($sql);
      $child_org = array();
      if($db->getRowsNum($result)>0) {
         while(list($org_idx)=$db->fetchRow($result)) {
            $child_org[$org_idx] = 1;
         }
      }
      
      
      ////////////////////////
      //// cek initiative ////
      ////////////////////////
      
      $sql = "SELECT a.pms_objective_id,a.pms_org_id,a.pms_objective_weight"
           . " FROM pms_objective a"
           . " LEFT JOIN pms_objective b ON b.pms_objective_id = a.pms_parent_objective_id"
           . " WHERE a.psid = '$psid' AND a.pms_parent_objective_id = '$pms_objective_id'"
           . " AND a.pms_org_id = '$pms_org_id'"
           . " AND b.pms_org_id = a.pms_org_id";
      $rchild = $db->query($sql);
      $has_local_sub = 0;
      $ttl_sub_weight = 0;
      $initiative = $initiative_weight = array();
      if($db->getRowsNum($rchild)>0) {
         while(list($sub_pms_objective_id,$sub_pms_org_id,$sub_weight)=$db->fetchRow($rchild)) {
            $has_local_sub++;
            $ttl_sub_weight = _bctrim(bcadd($ttl_sub_weight,$sub_weight));
            $initiative[$sub_pms_objective_id] = 1;
            $initiative_weight[$sub_pms_objective_id] = $sub_weight;
         }
      }
      
      /////////////////////////
      //// has initiative? ////
      /////////////////////////
      
      if($has_local_sub>0) { ///// yes, has initiative
         $initiative_ttl = 0;
         $initiative_ttl_cnt = 0;
         $initiative_ttl_weight = 0;
         foreach($initiative as $sub_pms_objective_id=>$v) {
            list($ach_initiative,$_kpi_ach_initiative) = _pms_calcSO($sub_pms_objective_id,$report_ytd,$report_month_id);
            
            if($ach_initiative!=-999) { //// not empty
               $initiative_ttl = bcadd($initiative_ttl,($ach_initiative*$initiative_weight[$sub_pms_objective_id]));
               $__kpi_initiative_ttl = bcadd($__kpi_initiative_ttl,($_kpi_ach_initiative*$initiative_weight[$sub_pms_objective_id]));
               $initiative_ttl_cnt++;
               $initiative_ttl_weight = _bctrim(bcadd($initiative_ttl_weight,$initiative_weight[$sub_pms_objective_id]));
            }
            
         }
         
         $achievement = _bctrim(bcdiv($initiative_ttl,$initiative_ttl_weight));
         $__kpi_achievement = _bctrim(bcdiv($__kpi_initiative_ttl,$initiative_ttl_weight));
         
      } else { /////////////////// no
         
         
         /////////////////////////////////////////////////////////////
         //// KPI .... achievement of sub organization ///////////////
         /////////////////////////////////////////////////////////////
         
         $kpi_share_ttl = 0;
         $achievement_kpi = 0;
         $__kpi_achievement_kpi = 0;
         $kpi_sub_cnt = 0;
         
         //////////////////
         //// has kpi? ////
         //////////////////
         
         $sql = "SELECT pms_kpi_id FROM pms_kpi WHERE pms_objective_id = '$pms_objective_id'";
         $result = $db->query($sql);
         if($db->getRowsNum($result)>0) { /// yes, has kpi
            
            $kpi_share_array = array();
            
            while(list($pms_kpi_id)=$db->fetchRow($result)) {
               
               /////////////////////
               //// has sub so? ////
               /////////////////////
               $sql = "SELECT pms_share_org_id,pms_share_weight FROM pms_kpi_share WHERE psid = '$psid' AND pms_objective_id = '$pms_objective_id' AND pms_kpi_id = '$pms_kpi_id'";
               $rkpishare = $db->query($sql);
               if($db->getRowsNum($rkpishare)>0) {
                  while(list($pms_share_org_idx,$pms_share_weightx)=$db->fetchRow($rkpishare)) {
                     if(isset($child_org[$pms_share_org_idx])&&$child_org[$pms_share_org_idx]==1) {
                        $kpi_share_ttl += $pms_share_weightx;
                        $kpi_share_array[$pms_share_org_idx] = _bctrim(bcadd($kpi_share_array[$pms_share_org_idx],$pms_share_weightx));
                     }
                  }
               }
            }
            
            ///////////////////////////////////
            //// calculate share cascading ////
            ///////////////////////////////////
            $ttl_kpi_share_weight = 0;
            $ttl_kpi_ach = 0;
            $__kpi_ttl_kpi_ach = 0;
            foreach($kpi_share_array as $pms_share_org_idx=>$share_weightx) {
               //// cascading ...
               $sql = "SELECT pms_objective_id,pms_objective_weight FROM pms_objective"
                    . " WHERE psid = '$psid' AND pms_parent_objective_id = '$pms_objective_id'"
                    . " AND pms_org_id = '$pms_share_org_idx'";
               $rcascading = $db->query($sql);
               $cno = 0;
               if($db->getRowsNum($rcascading)>0) { ////////// yes, has sub so
                  while(list($sub_pms_objective_id,$sub_pms_objective_weight)=$db->fetchRow($rcascading)) {
                     
                     //// here, $pms_share_weightx is equivalent with $sub_pms_objective_weight, but different in scale
                     list($ach,$_kpi_ach) = _pms_calcSO($sub_pms_objective_id,$report_ytd,$report_month_id);
                     
                     if($ach!=-999) { //// not empty
                        $ttl_kpi_ach = bcadd($ttl_kpi_ach,bcmul($ach,$share_weightx));
                        $__kpi_ttl_kpi_ach = bcadd($__kpi_ttl_kpi_ach,bcmul($_kpi_ach,$share_weightx));
                        $ttl_kpi_share_weight = bcadd($ttl_kpi_share_weight,$share_weightx);
                        $kpi_sub_cnt++; //// indicate that it's not empty, it has children
                     }
                     
                  }
               }
            }
            
            if($ttl_kpi_share_weight>0) {
               $achievement_kpi = bcdiv($ttl_kpi_ach,$ttl_kpi_share_weight);
               $__kpi_achievement_kpi = bcdiv($__kpi_ttl_kpi_ach,$ttl_kpi_share_weight);
            } else {
               $achievement_kpi = -999; //// we don't have kpi
               $__kpi_achievement_kpi = -999; //// we don't have kpi
            }
         }
         
         
         /////////////////////////////////////////////////////////////
         //// Action Plan .... achievement of peaple  ////////////////
         /////////////////////////////////////////////////////////////
         
         $achievement_ap = 0;
         $ap_cnt = 0;
         
         /////////////////
         //// has ap? ////
         /////////////////
         
         $sql = "SELECT pms_actionplan_id,pms_actionplan_text FROM pms_actionplan WHERE pms_objective_id = '$pms_objective_id' GROUP BY pms_objective_id";
         $result = $db->query($sql);
         if($db->getRowsNum($result)>0) { ////// yes, has ap
            $ap_cnt++;
            $achievement_ap = 100;
            
            while(list($pms_actionplan_id,$actionplan_text)=$db->fetchRow($result)) {
               $sql = "SELECT SUM(pms_share_weight),pms_actionplan_pic_employee_id FROM pms_actionplan_share"
                    . " WHERE psid = '$psid' AND pms_objective_id = '$pms_objective_id'"
                    . " GROUP BY pms_actionplan_pic_employee_id";
               $rsh = $db->query($sql);
               $pic_share = array();
               $pic_ach = 0;
               $ttl_pic_share = 0;
               $ttl_pic_ach = 0;
               $__kpi_ttl_pic_share = 0;
               $__kpi_ttl_pic_ach = 0;
               if($db->getRowsNum($rsh)>0) {
                  while(list($pms_share_weightxxx,$pms_actionplan_pic_employee_idxxx)=$db->fetchRow($rsh)) {
                     list($pic_ach,$__kpi_pic_ach) = _pms_calcPeopleAch($pms_actionplan_pic_employee_idxxx,$pms_objective_id,$report_ytd,$report_month_id);
                     if($pic_ach!=-999) {
                        $ttl_pic_share = _bctrim(bcadd($ttl_pic_share,$pms_share_weightxxx));
                        $ttl_pic_ach += ($pic_ach*$pms_share_weightxxx);
                        $__kpi_ttl_pic_ach += ($__kpi_pic_ach*$pms_share_weightxxx);
                     }
                  }
               } else {
               }
               
               if($ttl_pic_share>0) {
                  $achievement_ap = $ttl_pic_ach/$ttl_pic_share;
                  $__kpi_achievement_ap = $__kpi_ttl_pic_ach/$ttl_pic_share;
               } else {
                  $achievement_ap = -999;
                  $__kpi_achievement_ap = -999;
               }
               
            }
            
         } else {
            $__kpi_achievement_ap = -999;
         }
      }
      
      $ach_ttl = 0;
      $__kpi_ach_ttl = 0;
      $ach_cnt = 0;
      if($kpi_sub_cnt>0&&$achievement_kpi!=-999) {
         $ach_ttl += $achievement_kpi;
         $__kpi_ach_ttl += $__kpi_achievement_kpi;
         $ach_cnt++;
      }
      
      if($ap_cnt>0&&$achievement_ap!=-999) {
         $ach_ttl += $achievement_ap;
         $__kpi_ach_ttl += $__kpi_achievement_ap;
         $ach_cnt++;
      } else if($achievement_ap==-999) {
         $achievement = -999;
         $__kpi_achievement = -999;
      }
      
      if($ach_cnt>0) {
         $achievement = $ach_ttl;
         $__kpi_achievement = $__kpi_ach_ttl;
      }
      
      
      return array($achievement,$__kpi_achievement);
      
   }
   
   
   function _pms_getActionPlanAchievement($pms_objective_id,$employee_id,$actionplan_id,$inclusive=TRUE) {
      $db=&Database::getInstance();
      $psid = $_SESSION["pms_psid"];
      //// base100 mod start
      $sql = "SELECT is_pica,current_achievement,current_kpi_achievement,month_id FROM pms_pic_action"
           . " WHERE psid = '$psid'"
           . " AND employee_id = '$employee_id'"
           . " AND pms_objective_id = '$pms_objective_id'"
           . " AND actionplan_id = '$actionplan_id'";
      $result = $db->query($sql);
      list($is_pica,$current_achievement,$current_kpi_achievement,$month_id)=$db->fetchRow($result);
      _debuglog($sql);
      if($is_pica==1) {
         $uniqid = uniqid("picaroot");
         $_SESSION[$uniqid] = array();
         if($inclusive==TRUE) $_SESSION[$uniqid][$actionplan_id] = array($month_id,1);
         $pica_root = _pms_getpicaroot($pms_objective_id,$employee_id,$actionplan_id,$uniqid);
         $ttl_ap_achievement = $ttl_kpi_achievement = 0;
         foreach($_SESSION[$uniqid] as $pica_actionplan_id=>$xz) {
            $sql = "SELECT month_id,current_achievement,current_kpi_achievement,target_achievement,pica_id,is_pica"
                 . " FROM pms_pic_action"
                 . " WHERE psid = '$psid'"
                 . " AND employee_id = '$employee_id'"
                 . " AND pms_objective_id = '$pms_objective_id'"
                 . " AND actionplan_id = '$pica_actionplan_id'"
                 . " ORDER BY month_id";
            $rac = $db->query($sql);
            _debuglog($sql);
            if($db->getRowsNum($rac)>0) {
               while(list($x_month_id,$x_current_achievement,$x_current_kpi_achievement,$x_target_achievement,$x_pica_id,$x_is_pica)=$db->fetchRow($rac)) {
                  $ttl_ap_achievement += $x_current_achievement;
                  $ttl_kpi_achievement += $x_current_kpi_achievement;
               }
            }
         }
         $current_achievement = $ttl_ap_achievement;
         $current_kpi_achievement = $ttl_kpi_achievement;
         
         _dumpvar($_SESSION[$uniqid]);
         
         unset($_SESSION[$uniqid]);
      
      }
      
      return array($current_achievement,$current_kpi_achievement);
      //// base100 mod end
   
   }
   
   function _pms_getpicaroot($pms_objective_id,$employee_id,$actionplan_id,$uniqid="pms_picaroot") {
      $db=&Database::getInstance();
      $sql = "SELECT actionplan_id,is_pica,month_id FROM pms_pic_action"
           . " WHERE pms_objective_id = '$pms_objective_id'"
           . " AND employee_id = '$employee_id'"
           . " AND pica_id = '$actionplan_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($root_actionplan_id,$is_pica,$month_id)=$db->fetchRow($result);
         $_SESSION[$uniqid][$root_actionplan_id] = array($month_id,1);
         asort($_SESSION[$uniqid]);
         if($is_pica==0) {
            return $root_actionplan_id;
         } else {
            return _pms_getpicaroot($pms_objective_id,$employee_id,$root_actionplan_id,$uniqid);
         }
      }
      return $actionplan_id;
   }
   
   function _pms_getpicaroot_old($pms_objective_id,$employee_id,$actionplan_id) {
      $db=&Database::getInstance();
      $sql = "SELECT actionplan_id,is_pica FROM pms_pic_action"
           . " WHERE pms_objective_id = '$pms_objective_id'"
           . " AND employee_id = '$employee_id'"
           . " AND pica_id = '$actionplan_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($root_actionplan_id,$is_pica)=$db->fetchRow($result);
         if($is_pica==0) {
            return $root_actionplan_id;
         } else {
            return _pms_getpicaroot($pms_objective_id,$employee_id,$root_actionplan_id);
         }
      }
      return $actionplan_id;
   }
   
   
   
   function calculate_jam_org($psid,$org_id) {
      $db=&Database::getInstance();
      
      $sql = "SELECT pms_perspective_code,pms_perspective_id,pms_perspective_name FROM pms_perspective WHERE psid = '$psid' ORDER BY pms_perspective_id";
      $ttl = 0;
      $ttlweight = 0;
      $ttlw = 0;
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($pms_perspective_code,$pms_perspective_id,$pms_perspective_name)=$db->fetchRow($result)) {                            //////////////////////////// per perspective
            
            $sql = "SELECT pms_objective_id,pms_objective_no,pms_objective_text,pms_kpi_text,pms_target_text,pms_measurement_unit,pms_objective_weight,"
                 . "pms_pic_job_id,pms_pic_employee_id,pms_parent_objective_id,pms_parent_kpi_id"
                 . " FROM pms_objective"
                 . " WHERE pms_org_id = '$org_id'"
                 . " AND pms_perspective_id = '$pms_perspective_id'"
                 . " AND psid = '$psid'"
                 . " ORDER BY pms_objective_no";
            $ro = $db->query($sql);
            $cnt = $db->getRowsNum($ro);
            if($cnt>0) {
               while(list($pms_objective_id,$pms_objective_no,$pms_objective_text,$pms_kpi_text,$pms_target_text,$pms_measurement_unit,$pms_objective_weight,
                          $pms_pic_job_id,$pms_pic_employee_id,$pms_parent_objective_idx,$pms_parent_kpi_idx)=$db->fetchRow($ro)) {                                 //////////////////// per objective
                  list($ach0,$ach1)=_pmsjamreport_calcSO($pms_objective_id,FALSE,0);
                  
                  /// check if it is a local sub
                  $sql = "SELECT pms_org_id FROM pms_objective WHERE psid = '$psid' AND pms_objective_id = '$pms_parent_objective_idx'";
                  $rp = $db->query($sql);
                  if($db->getRowsNum($rp)>0) {
                     list($pms_parent_org_idx)=$db->fetchRow($rp);
                  }
                  
                  /// check if it has local sub / initiatives?
                  $sql = "SELECT pms_objective_id,pms_org_id,pms_objective_weight FROM pms_objective WHERE psid = '$psid' AND pms_parent_objective_id = '$pms_objective_id' AND pms_org_id = '$org_id'";
                  $rchild = $db->query($sql);
                  $has_local_sub = 0;
                  $ttl_sub_weight = 0;
                  if($db->getRowsNum($rchild)>0) {
                     while(list($sub_pms_objective_id,$sub_pms_org_id,$sub_weight)=$db->fetchRow($rchild)) {
                        $has_local_sub++;
                        $ttl_sub_weight = _bctrim(bcadd($ttl_sub_weight,$sub_weight));
                     }
                  }
                  
                  
                  $do_count = 0;
                  if($pms_parent_objective_idx==0) {
                     $do_count++;
                  } else {
                     $sql = "SELECT pms_org_id FROM pms_objective WHERE psid = '$psid' AND pms_parent_objective_id = '$pms_objective_id'";
                     $rpx = $db->query($sql);
                     if($db->getRowsNum($rpx)>0) {
                        list($pms_parent_org_id)=$db->fetchRow($rpx);
                        if($pms_parent_org_id==$org_id) {
                           //$do_count++;
                        } else {
                           $do_count++;
                        }
                     } else {
                        $do_count++;
                     }
                  }
                  
                  if($has_local_sub==0&&$do_count>0) {
                     $subttlw = _bctrim(bcadd($subttlw,$pms_objective_weight));
                     $ttlw = _bctrim(bcadd($ttlw,$pms_objective_weight));
                     $ttl += ($ach0*$pms_objective_weight)/100;
                  }
                  
                  $ttlweight += $pms_objective_weight;
                  
               }
            }
         }
      }
      
      return array($ttl,$ttlw);
      
   
   }
   


} // HRIS_INCLUDEPMS_DEFINED
?>