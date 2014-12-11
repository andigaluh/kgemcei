<?php
if ( !defined('HRIS_INCLUDEJAMMONITOR_DEFINED') ) {
   define('HRIS_INCLUDEJAMMONITOR_DEFINED', TRUE);

   function _pmsjamreport_calcPeopleAch($employee_id,$pms_objective_id,$report_ytd=TRUE,$report_month_id=0) {
      $db=&Database::getInstance();
      
      $sql = "SELECT report_approval_st,reported_final_result,objective_weight FROM pms_jam WHERE employee_id = '$employee_id' AND pms_objective_id = '$pms_objective_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($report_approval_st,$reported_final_result,$objective_weight)=$db->fetchRow($result);
      } else {
         $report_approval_st = "";
      }
      if($report_approval_st=="final") {
         $final_result = _bctrim(bcdiv(bcmul($reported_final_result,100),$objective_weight));
         return array($final_result,$final_result);
      } else {
         return array(-999,-999);
      }
   }

   function _pmsjamreport_calcYTD($pms_objective_id) {
      $psid = $_SESSION["pms_psid"];
      $db=&Database::getInstance();
      
      $sql = "SELECT pms_org_id,pms_objective_weight FROM pms_objective WHERE pms_objective_id = '$pms_objective_id'";
      $result = $db->query($sql);
      list($pms_org_id,$pms_objective_weight)=$db->fetchRow($result);
      
      list($val,$_kpi_val) = _pmsjamreport_calcSO($pms_objective_id,TRUE);
      
      
      return array($val,$_kpi_val);
   }
   
   
   function _pmsjamreport_calcSO($pms_objective_id,$report_ytd=TRUE,$report_month_id=0) {
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
            list($ach_initiative,$_kpi_ach_initiative) = _pmsjamreport_calcSO($sub_pms_objective_id,$report_ytd,$report_month_id);
            
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
                     list($ach,$_kpi_ach) = _pmsjamreport_calcSO($sub_pms_objective_id,$report_ytd,$report_month_id);
                     
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
                     list($pic_ach,$__kpi_pic_ach) = _pmsjamreport_calcPeopleAch($pms_actionplan_pic_employee_idxxx,$pms_objective_id,$report_ytd,$report_month_id);
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
   
   
   
   
   
   


} // HRIS_INCLUDEJAMMONITOR_DEFINED
?>