<?php
//--------------------------------------------------------------------//
// Filename : modules/pms/pmsperspective.php                          //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-07-18                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('PMS_MONITOR_DEFINED') ) {
   define('PMS_MONITOR_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

//include_once(XOCP_DOC_ROOT."/modules/pms/pmsxocp.php");
include_once(XOCP_DOC_ROOT."/modules/pms/class/ajax_objective.php");
include_once(XOCP_DOC_ROOT."/modules/pms/class/selectpms.php");
include_once(XOCP_DOC_ROOT."/modules/pms/include/pms.php");

class _pms_Monitor extends XocpBlock {
   var $catchvar = _PMS_CATCH_VAR;
   var $blockID = _PMS_MONITOR_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _PMS_MONITOR_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _pms_Monitor($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */

   }
   
   function recurseParentOrg($pms_objective_id) {
      $db=&Database::getInstance();
      
      $pms_org_id = 0;
      $sql = "SELECT pms_org_id,pms_parent_objective_id FROM pms_objective WHERE pms_objective_id = '$pms_objective_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($pms_org_id,$pms_parent_objective_id)=$db->fetchRow($result);
         if($pms_parent_objective_id>0) {
            return $this->recurseParentOrg($pms_parent_objective_id);
         }
      }
      $sql = "SELECT parent_id FROM ".XOCP_PREFIX."orgs WHERE org_id = '$pms_org_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($parent_id)=$db->fetchRow($result);
         return $parent_id; //// return parent_id, hopefully corporate org_id
      }
      return -1;
   }
   
   function renderOrg() {
      $psid = $_SESSION["pms_psid"];
      $org_id = $_SESSION["pms_org_id"];
      $db=&Database::getInstance();
      global $xocp_vars;
      $current_radar_month = $_SESSION["pmsmonitor_radar_month"];
      if($current_radar_month==0) $current_radar_month = 1;
      
      $sql = "SELECT o.org_id,o.org_nm,b.org_class_nm,o.org_abbr"
           . " FROM ".XOCP_PREFIX."orgs o"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b ON b.org_class_id = o.org_class_id"
           . " WHERE o.org_id = '$org_id'";
      $result = $db->query($sql);
      list($org_idx,$org_nmx,$org_class_nm,$org_abbr) = $db->fetchRow($result);
      
      $sql = "SELECT a.pms_share_org_id,b.org_nm,c.org_class_nm"
           . " FROM pms_org_share a"
           . " LEFT JOIN ".XOCP_PREFIX."orgs b ON b.org_id = a.pms_share_org_id"
           . " LEFT JOIN ".XOCP_PREFIX."org_class c USING(org_class_id)"
           . " WHERE a.psid = '$psid' AND a.pms_org_id = '$org_id'"
           . " ORDER BY b.org_class_id,b.order_no";
      $result = $db->query($sql);
      
      $orgtbl .= "<div style='color:black;font-weight:bold;text-align:center;padding:5px;'>Choose Organization</div>";
      $orgtbl .= "<div style='".($_SESSION["pms_dashboard_org"]==$org_id?"background-color:#eef;font-weight:bold;":"")."max-width:290px;overflow:hidden;border-bottom:1px solid #bbb;border-top:1px solid #bbb;'>"
               . "<div style='width:900px;padding:4px;' class='xlnk' onclick='select_pms_org(\"$org_id\");'>".htmlentities("$org_nmx $org_class_nm")."</div>"
               . "</div>";
      if($db->getRowsNum($result)>0) {
         while(list($pms_share_org_id,$pms_share_org_nm,$pms_share_org_class_nm)=$db->fetchRow($result)) {
            if(!isset($_SESSION["pms_dashboard_org"])) {
               $_SESSION["pms_dashboard_org"] = $pms_share_org_id;
            }
            $orgtbl .= "<div style='font-size:0.9em;padding-left:10px;".($_SESSION["pms_dashboard_org"]==$pms_share_org_id?"background-color:#eef;font-weight:bold;":"")."max-width:290px;overflow:hidden;border-bottom:1px solid #bbb;'>"
                     . "<div style='width:900px;padding:4px;' class='xlnk' onclick='select_pms_org(\"$pms_share_org_id\");'>".htmlentities("$pms_share_org_nm $pms_share_org_class_nm")."</div></div>";
         }
      }
      
      
      //$svg = "<div style='padding-top:10px;'>";
      $cx = $cy = 147;
      $svg .= "\n<svg:svg xmlns:svg='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink' version='1.1'  style='width:290px;height:290px;border:0px solid #bbb;'>";
      //$svg .= "<svg:circle cx='$cx' cy='$cy' r='138px' fill='#fff' stroke='#999' stroke-width='0px'/>";
      $svg .= "<svg:circle cx='$cx' cy='$cy' r='120px' fill='#ffffff' stroke='#999' stroke-width='0px'/>";
      $svg .= "<svg:circle cx='$cx' cy='$cy' r='100px' fill='#aaffaa' stroke='#999' stroke-width='0px'/>";
      $svg .= "<svg:circle cx='$cx' cy='$cy' r='69px' fill='#ffffaa' stroke='#999' stroke-width='0px'/>";
      $svg .= "<svg:circle cx='$cx' cy='$cy' r='59px' fill='#ffdddd' stroke='#999' stroke-width='0px'/>";
      
      $svg .= "<svg:circle cx='$cx' cy='$cy' r='120px' fill='none' stroke='#999' stroke-width='1px'/>";
      $svg .= "<svg:circle cx='$cx' cy='$cy' r='100px' fill='none' stroke='#999' stroke-width='0.5px'/>";
      $svg .= "<svg:circle cx='$cx' cy='$cy' r='80px' fill='none' stroke='#999' stroke-width='0px'/>";
      $svg .= "<svg:circle cx='$cx' cy='$cy' r='60px' fill='none' stroke='#f99' stroke-width='0.5px'/>";
      $svg .= "<svg:circle cx='$cx' cy='$cy' r='40px' fill='none' stroke='#f99' stroke-width='0.5px'/>";
      $svg .= "<svg:circle cx='$cx' cy='$cy' r='20px' fill='none' stroke='#f99' stroke-width='0.5px'/>";
      $svg .= "<svg:circle cx='$cx' cy='$cy' r='1px' fill='none' stroke='#999' stroke-width='0.5px'/>";
      
      $sql = "SELECT a.pms_objective_id,a.pms_objective_no,b.pms_perspective_code,a.pms_objective_text"
           . " FROM pms_objective a"
           . " LEFT JOIN pms_perspective b USING(pms_perspective_id)"
           . " LEFT JOIN pms_objective c ON c.pms_objective_id = a.pms_parent_objective_id"
           . " WHERE a.psid = '$psid' AND a.pms_org_id = '".$_SESSION["pms_dashboard_org"]."'"
           . " AND (c.pms_org_id != a.pms_org_id OR c.pms_org_id IS NULL)"
           . " ORDER BY a.pms_perspective_id,a.pms_objective_no";
      $result = $db->query($sql);
      $cnt = $db->getRowsNum($result);
      $start = 270;
      $tt = deg2rad($start);
      $r = 100;
      $no = 0;
      $oldx = $oldy = 0;
      $arr_line = array();
      $arr_id = array();
      $tooltip = "";
      if($cnt>0) {
         $step_t = 360/$cnt;
         while(list($pms_objective_idx,$pms_objective_nox,$pms_perspective_codex,$pms_objective_text)=$db->fetchRow($result)) {
            
            $xval = $cx + ($r+20) * cos($tt);
            $yval = $cy + ($r+20) * sin($tt);
            $no_x = $cx + ($r+30) * cos($tt);
            $no_y = 3 + $cy + ($r+30) * sin($tt);
            $svg .= "\n<svg:line x1='$cx' y1='$cy' x2='$xval' y2='$yval' stroke='#aaa' stroke-width='0.5' />";
            $svg .= "<svg:text x='$no_x' y='$no_y' style='text-anchor:middle;fill:#777;font-size:10px;font-weight:normal;font-family:Arial;'>${pms_perspective_codex}${pms_objective_nox}</svg:text>";
            ///$ach = translate_norm($this->_calcSO($pms_objective_idx,FALSE,$current_radar_month));
            $ach = $this->_calcSO($pms_objective_idx,FALSE,$current_radar_month);
            $rx = $r*($ach/100);
            $xpos = $cx + $rx * cos($tt);
            $ypos = $cy + $rx * sin($tt);
            if($oldx!=0) {
               //$svg .= "\n<svg:line x1='$xpos' y1='$ypos' x2='$oldx' y2='$oldy' stroke='#aaf' stroke-width='2' />";
            }
            //$svg .= "<svg:circle cx='$xpos' cy='$ypos' r='3px' fill='#00f' stroke='#000' stroke-width='1px'/>";
            $tt += deg2rad($step_t);
            $oldx = $xpos;
            $oldy = $ypos;
            $tooltip .= "<div id='achtooltip_${pms_objective_idx}'><div>${pms_perspective_codex}${pms_objective_nox} ".htmlentities($pms_objective_text)." : ".toMoney($ach)."%</div></div>";
            $arr_line[$no] = array($xpos,$ypos,$pms_objective_idx,$pms_objective_nox,$pms_perspective_codex,$pms_objective_text,$ach);
            $no++;
         }
         $oldx = $oldy = 0;
         $firstx = $firsty = 0;
         $oldach = 0;
         foreach($arr_line as $no=>$v) {
            list($x,$y,$pms_objective_idx,$pms_objective_nox,$pms_perspective_codex,$pms_objective_text,$ach)=$v;
            
            if($pms_objective_nox==3&&$pms_perspective_codex="C") {
               //_debuglog("$pms_objective_idx,$pms_objective_nox,$pms_perspective_codex $ach");
            }
            
            //$ach = 100;
            if($firstx==$firsty&&$firstx==0&&$ach>0) {
               $firstx = $x;
               $firsty = $y;
            }
            if($oldx!=0) {
               if($ach>0&&$oldach>0) {
                  $svg .= "\n<svg:line x1='$x' y1='$y' x2='$oldx' y2='$oldy' stroke='#77f' stroke-width='3' />";
               }
            }
            $oldx = $x;
            $oldy = $y;
            $oldach = $ach;
         }
         if($firstx!=0&&$oldach>0) {
            $svg .= "\n<svg:line x1='$oldx' y1='$oldy' x2='$firstx' y2='$firsty' stroke='#77f' stroke-width='3' />";
         }
         foreach($arr_line as $no=>$v) {
            list($x,$y,$pms_objective_id,$pms_objective_no,$pms_perspective_code,$pms_objective_text,$ach)=$v;
            if($ach==0) continue;
            if($ach>90) {
               $fill = "#0f0";
            } else if($ach>82) {
               $fill = "#ff0";
            } else {
               $fill = "#f00";
            }
            $svg .= "<svg:circle cx='$x' cy='$y' r='5px' fill='$fill' stroke='#333' stroke-width='1px' onmousemove='show_ach_tooltip(\"$pms_objective_id\",this,evt);' onmouseout='hide_ach_tooltip(this);'/>";
         }
      }
      
      
      $svg .= "\n</svg:svg>";
      
      $mon .= "<div style='background-color:#ddd;color:black;padding:2px;font-weight:bold;text-align:center;'>Month [<span onclick='toggle_ytd(this,event);' class='ylnk' style='font-weight:normal;font-size:0.9em;' id='spytd'>".($_SESSION["pmsmonitor_radar_ytd"]==TRUE?"YTD":"MON")."</span>]</div>";
      $mon .= "<div style='text-align:center;'>"
            . "<span style='cursor:pointer;' onclick='_pms_choose_month(".($current_radar_month-1).",this,event);'><span class='xlnk'>prev</span> <img src='".XOCP_SERVER_SUBDIR."/images/prev.gif'/><img src='".XOCP_SERVER_SUBDIR."/images/prev.gif'/></span>"
            . "&#160;&#160;<span id='sp_month' onclick='_pms_show_month(this,event);' class='xlnk'>".$xocp_vars["month_year"][$current_radar_month]."</span>&#160;&#160;"
            . "<span style='cursor:pointer;' onclick='_pms_choose_month(".($current_radar_month+1).",this,event);'><img src='".XOCP_SERVER_SUBDIR."/images/next.gif'/><img src='".XOCP_SERVER_SUBDIR."/images/next.gif'/> <span class='xlnk'>next</span></span>"
            . "</div>";
            
      
      $dvmonth = "<div id='dvmonth' style='display:none;position:absolute;padding:5px;background-color:#fff;border:1px solid #bbb;-moz-box-shadow:1px 1px 3px #000;'>"
               . "<div style='padding:2px;'><span class='xlnk' onclick='_pms_choose_month(1,this,event);'>January</span></div>"
               . "<div style='padding:2px;'><span class='xlnk' onclick='_pms_choose_month(2,this,event);'>February</span></div>"
               . "<div style='padding:2px;'><span class='xlnk' onclick='_pms_choose_month(3,this,event);'>March</span></div>"
               . "<div style='padding:2px;'><span class='xlnk' onclick='_pms_choose_month(4,this,event);'>April</span></div>"
               . "<div style='padding:2px;'><span class='xlnk' onclick='_pms_choose_month(5,this,event);'>May</span></div>"
               . "<div style='padding:2px;'><span class='xlnk' onclick='_pms_choose_month(6,this,event);'>June</span></div>"
               . "<div style='padding:2px;'><span class='xlnk' onclick='_pms_choose_month(7,this,event);'>July</span></div>"
               . "<div style='padding:2px;'><span class='xlnk' onclick='_pms_choose_month(8,this,event);'>August</span></div>"
               . "<div style='padding:2px;'><span class='xlnk' onclick='_pms_choose_month(9,this,event);'>September</span></div>"
               . "<div style='padding:2px;'><span class='xlnk' onclick='_pms_choose_month(10,this,event);'>October</span></div>"
               . "<div style='padding:2px;'><span class='xlnk' onclick='_pms_choose_month(11,this,event);'>November</span></div>"
               . "<div style='padding:2px;'><span class='xlnk' onclick='_pms_choose_month(12,this,event);'>December</span></div>"
               . "</div>";
      
      $ret = "<div style='-moz-border-radius:5px;-moz-box-shadow:1px 1px 3px #000;border:1px solid #bbb;background-color:#fff;padding:5px;'>";
      $ret .= $orgtbl;
      $ret .= $svg;
      $ret .= $mon;
      $ret .= "</div>";
      $ret .= "<div style='display:none;'>$tooltip</div>".$dvmonth;
      return $ret;
   }
   
   function calcYTD($pms_objective_id) {
      $psid = $_SESSION["pms_psid"];
      $db=&Database::getInstance();
      
      $sql = "SELECT pms_org_id,pms_objective_weight FROM pms_objective WHERE pms_objective_id = '$pms_objective_id'";
      $result = $db->query($sql);
      list($pms_org_id,$pms_objective_weight)=$db->fetchRow($result);
      
      $_SESSION["pica"] = array();
      
      $val = $this->_calcSO($pms_objective_id);
      
      return $val;
   }
   
   function _calcSO($pms_objective_id,$report_ytd=TRUE,$report_month_id=0) {
      $psid = $_SESSION["pms_psid"];
      $db=&Database::getInstance();
      
      ////////////////////
      //// initialize ////
      ////////////////////
      
      $achievement = 0;
      
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
         foreach($initiative as $sub_pms_objective_id=>$v) {
            $ach_initiative = $this->_calcSO($sub_pms_objective_id,$report_ytd,$report_month_id);
            
            if($ach_initiative!=-999) { //// not empty
               $initiative_ttl = bcadd($initiative_ttl,$ach_initiative);
               $initiative_ttl_cnt++;
            }
            
         }
         
         $achievement = _bctrim(bcdiv($initiative_ttl,$initiative_ttl_cnt));
         
      } else { /////////////////// no
         
         
         /////////////////////////////////////////////////////////////
         //// KPI .... achievement of sub organization ///////////////
         /////////////////////////////////////////////////////////////
         
         $kpi_share_ttl = 0;
         $achievement_kpi = 0;
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
                     $ach = $this->_calcSO($sub_pms_objective_id,$report_ytd,$report_month_id);
                     
                     if($ach!=-999) { //// not empty
                        $ttl_kpi_ach = bcadd($ttl_kpi_ach,bcmul($ach,$share_weightx));
                        $ttl_kpi_share_weight = bcadd($ttl_kpi_share_weight,$share_weightx);
                        $kpi_sub_cnt++; //// indicate that it's not empty, it has children
                     }
                     
                  }
               }
            }
            
            if($ttl_kpi_share_weight>0) {
               $achievement_kpi = bcdiv($ttl_kpi_ach,$ttl_kpi_share_weight);
            } else {
               $achievement_kpi = -999; //// we don't have kpi
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
                    //. " AND pms_actionplan_id = '$pms_actionplan_id'"
                    . " GROUP BY pms_actionplan_pic_employee_id";
               $rsh = $db->query($sql);
               $pic_share = array();
               $pic_ach = 0;
               $ttl_pic_share = 0;
               $ttl_pic_ach = 0;
               if($db->getRowsNum($rsh)>0) {
                  while(list($pms_share_weightxxx,$pms_actionplan_pic_employee_idxxx)=$db->fetchRow($rsh)) {
                     $pic_ach = $this->_calcPeopleAch($pms_actionplan_pic_employee_idxxx,$pms_objective_id,$report_ytd,$report_month_id);
                     
                     
                     if($pic_ach!=-999) {
                        $ttl_pic_share += $pms_share_weightxxx;
                        $ttl_pic_ach += ($pic_ach*$pms_share_weightxxx);
                        $_SESSION["pica"]["$pms_objective_id-$pms_actionplan_pic_employee_idxxx"] = array($pms_actionplan_pic_employee_idxxx,$pms_objective_id,$actionplan_text,$pic_ach);
                     } else {
                        //$ttl_pic_share += $pms_share_weightxxx;
                        //$ttl_pic_ach += 0; //($pic_ach*$pms_share_weightxxx);
                        //$_SESSION["pica"]["$pms_objective_id-$pms_actionplan_pic_employee_idxxx"] = array($pms_actionplan_pic_employee_idxxx,$pms_objective_id,$actionplan_text,0);
                     }
                  }
               } else {
                  //$_SESSION["pica"]["$pms_objective_id-0"] = array(0,$pms_objective_id,$actionplan_text,0);
               }
               
               if($ttl_pic_share>0) {
                  $achievement_ap = $ttl_pic_ach/$ttl_pic_share;
               } else {
                  $achievement_ap = -999;
                  //$_SESSION["pica"]["$pms_objective_id-0"] = array(0,$pms_objective_id,$actionplan_text,0,0);
               }
               
            }
            
         } else {
            $achievement_ap = -999;
         }
      }
      
      $sql = "SELECT org_nm,org_class_id FROM ".XOCP_PREFIX."orgs WHERE org_id = '$pms_org_id'";
      $result = $db->query($sql);
      list($org_nm,$org_class_id)=$db->fetchRow($result);
      
      
      $ach_ttl = 0;
      $ach_cnt = 0;
      if($kpi_sub_cnt>0&&$achievement_kpi!=-999) {
         $ach_ttl += $achievement_kpi;
         $ach_cnt++;
      }
      
      if($ap_cnt>0&&$achievement_ap!=-999) {
         $ach_ttl += $achievement_ap;
         $ach_cnt++;
      }
      
      if($ach_cnt>0) {
         $achievement = $ach_ttl/$ach_cnt;
      }
      
      return $achievement;
      
   }
   
   function _calcPeopleAch($employee_id,$pms_objective_id,$report_ytd=TRUE,$report_month_id=0) {
      $db=&Database::getInstance();
      
      //////////// query all action plan first //////////////////////////
      
      $sql = "SELECT actionplan_id,actionplan_text,target_text,current_achievement,order_no,approval_st,submit_dttm,approval1_dttm,approval1_employee_id,"
           . "pica_id,is_pica,target_achievement,report_approval_st,month_id,repeat_type"
           . " FROM pms_pic_action"
           . " WHERE pms_objective_id = '$pms_objective_id'"
           . " AND employee_id = '$employee_id'"
           . " AND is_pica = '0'"
           . " AND actionplan_group_id = '0'"
           . " ORDER BY month_id,order_no";
      $resultx = $db->query($sql);
      if($pms_objective_id==691) {
         _debuglog($sql);
      }
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
            $root_actionplan_id = $this->getpicaroot($pms_objective_id,$employee_id,$actionplan_id);
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
               $month_id_start = 1;
               $month_id_stop = 12;
            }
            
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
                  continue;
               }
               
               if($report_approval_st=="final") {
               
                  $ap_report_final[$actionplan_idx] = 1;
                  $ttl_ap_achievement = _bctrim(bcadd($ttl_ap_achievement,$current_achievement));
                  if($repeat_type!=0) {
                     $div += 100;
                  }
                  //$_SESSION["pica"]["$employee_id-$pms_objective_id"] = array($employee_id,$pms_objective_id,"$actionplan_text",$current_achievement,$month_id);
                  $ap_month_cnt++;
                }
               
               
            }
            
            if($div>0) {
               $ap_ach = _bctrim(bcdiv($ttl_ap_achievement,$div));
               $ap_ach_ttl = bcadd($ap_ach_ttl,$ap_ach);
               //_debuglog("$pms_objective_id $employee_id : $ap_ach $ap_ach_ttl = $ttl_ap_achievement / $div");
            }
            
            ///list($actionplan_id,$actionplan_text,$target_text,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,$month_idx,$target_achievement,$current_achievement)=$aptextarr[$no];
            
            
         }
         
         
         if(count($ap_report_final)>0) {
            $achievement = translate_norm(100*$ap_ach_ttl/count($ap_report_final));
         } else {
            $achievement = -999;
         }
         
         return $achievement;
      } else {
         return -999;
      }
      
      
   }
   
   function getpicaroot($pms_objective_id,$employee_id,$actionplan_id) {
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
            return $this->getpicaroot($pms_objective_id,$employee_id,$root_actionplan_id);
         }
      }
      return $actionplan_id;
   }
   
   
   
   
   function _svg_TextWrap($lines,$x,$y,$width=200,$height=75,$fontsize="12px") {
      $ret = "";
      $cntline = count($lines);
      
      $h = 15;
      
      $new_y = 3+round(((4*$h)-($h*$cntline))/2)+$y;
      
      if(is_array($lines)) {
         $ret .= "<svg:text x='$x' y='$new_y' style='text-anchor:middle;fill:white; font-size:12px; font-weight:normal;'>";
         foreach($lines as $line) {
            $ret .= "<svg:tspan x='".($x+($width/2))."' dy='$h'>$line</svg:tspan>";
         }
         $ret .= "</svg:text>";
      }
      return $ret;
   }
   
   function createMarker($pno,$a,$b,$value = 0,$cause_effect_value = NULL, $ach_cause_effect = "no") {
      $start = 150;
      $marker = "<svg:g>";
      $t = deg2rad($start);
      $r = 50;
      
      //$value = $cause_effect_value;
      
      $selected_value = $value;
      $ce_marker = "";
      switch($ach_cause_effect) {
         case "no":
         default:
            $value_txt = toMoney($value);
            $selected_value = $value;
            break;
         case "yes":
            if(isset($cause_effect_value)) {
               $value_txt = toMoney($cause_effect_value);
               $selected_value = $cause_effect_value;
            } else {
               $value_txt = toMoney($value);
               $selected_value = $value;
            }
            break;
         case "both":
            if($cause_effect_value!==NULL) {
               $ce_tt = deg2rad($start+(240*$cause_effect_value/100));
               $ce_xval = $a + ($r-20) * cos($ce_tt);
               $ce_yval = $b + ($r-20) * sin($ce_tt);
               $ce_marker .= "<svg:line x1='$a' y1='$b' x2='$ce_xval' y2='$ce_yval' stroke='#f00' stroke-width='3'/>";
            } else {
               $ce_marker = "";
            }
            $value_txt = toMoney($value).($cause_effect_value===NULL?"":" / ".toMoney($cause_effect_value));
            $selected_value = $value;
            break;
      }
      
      
      $tt = deg2rad($start+(240*$selected_value/100));
      $xval = $a + ($r-5) * cos($tt);
      $yval = $b + ($r-5) * sin($tt);
      
      
      
      if($selected_value>70) {
         $color = "#00ff00";
         $deg0 = deg2rad($start+(240*90/100));
         $deg1 = deg2rad($start+(240));
         $la=0;
      } else if($selected_value>=60) {
         $color = "#ffff00";
         $deg0 = deg2rad($start+(240*82/100));
         $deg1 = deg2rad($start+(240*90/100));
         $la=0;
      } else {
         $color = "#ff0000";
         $deg0 = deg2rad($start);
         $deg1 = deg2rad($start+(240*82/100));
         $la=1;
      }
      $deg0 = deg2rad($start);
      $deg1 = deg2rad($start+240);
      $la=1;
      
      $re = 50;
      $x0 = $a + $re * cos($deg0);
      $y0 = $b + $re * sin($deg0);
      $xx = $a + $re * cos($deg1);
      $yy = $b + $re * sin($deg1);
      
      $id = uniqid();
      $marker .= "<svg:defs>";
      $marker .= "<svg:radialGradient id='MyGradient${id}' gradientUnits='userSpaceOnUse' cx='$a' cy='$b' r='".($re)."'>";
      $marker .= "<svg:stop offset='0%' stop-color='black' stop-opacity='0'/>";
      $marker .= "<svg:stop offset='10%' stop-color='$color' stop-opacity='0.7'/>";
      $marker .= "<svg:stop offset='70%' stop-color='$color' stop-opacity='0.1'/>";
      $marker .= "<svg:stop offset='80%' stop-color='$color' stop-opacity='0'/>";
      $marker .= "<svg:stop offset='100%' stop-color='black' stop-opacity='0'/>";
      $marker .= "</svg:radialGradient>";
      $marker .= "</svg:defs>";
      
      //$marker .= "<svg:path d='M$a,$b L$x0,$y0 a$re,$re 0 $la,1 ".($xx-$x0).",".($yy-$y0)." z' fill='url(#MyGradient${id})' stroke-width='0' />";
      $marker .= "<svg:circle cx='$a' cy='$b' r='$re' fill='url(#MyGradient${id})' stroke-width='0' />";
      for($tip=0;$tip<=20;$tip++) {
         $d = 3;
         $x1 = $a + $r * cos($t);
         $y1 = $b + $r * sin($t);
         $x2 = $a + ($r-$d) * cos($t);
         $y2 = $b + ($r-$d) * sin($t);
         $marker .= "<svg:line x1='$x1' y1='$y1' x2='$x2' y2='$y2' stroke='#bbb' stroke-width='1'/>";
         //$marker .= "<svg:text x='$x1' y='$y1' style='text-anchor:middle;fill:#fff; font-size:9px;font-weight:normal;font-family:Arial;'>$y1</svg:text>";
         $t+=deg2rad(12);
      }
      
      $marker .= "<svg:line x1='$a' y1='$b' x2='$xval' y2='$yval' stroke='#fff' stroke-width='2'/>";
      $marker .= $ce_marker;
      
      $marker .= "<svg:text x='$a' y='".($b+40)."' style='text-anchor:middle;fill:#bbb; font-size:11px;font-weight:normal;font-family:Arial;' filter='url(#drop_shadow_text${pno})'>$value_txt</svg:text>";
      $marker .= "</svg:g>";
      return $marker;
   }
   
   function showOrg($showOpt=FALSE) {
      $db =& Database::getInstance();
      if(!isset($_SESSION["pms_org_id"])) {
         $_SESSION["pms_org_id"] = 1;
      }
      
      $org_id = $_SESSION["pms_org_id"];
      $sql = "SELECT o.org_id,o.org_nm,b.org_class_nm,o.org_abbr"
           . " FROM ".XOCP_PREFIX."orgs o"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b ON b.org_class_id = o.org_class_id"
           . " WHERE o.org_id = '$org_id'";
      $result = $db->query($sql);
      $cnt = $db->getRowsNum($result);
      $showOpt = FALSE;
      $org_nm = "-";
      if($cnt == 1) {
         list($org_id,$org_nmx,$org_class_nm,$org_abbr) = $db->fetchRow($result);
         $_SESSION["hris_org_nm"] = "$org_abbr $org_nmx [$org_class_nm]";
         $org_nm = "$org_nmx $org_class_nm";
      } else if($cnt > 1) {
         $found = 0;
         while(list($org_id,$org_nmx,$org_class_nm)=$db->fetchRow($result)) {
            if($org_id==$_SESSION["hris_org_id"]) {
               $found = 1;
               $org_nm = "$org_abbr $org_nmx [$org_class_nm]";
               break;
            }
         }
         if($found==0) {
            $showOpt = TRUE;
         }
      } else {
         $_SESSION["hris_org_nm"] = NULL;
         $_SESSION["hris_org_id"] = 0;
         $showOpt = TRUE;
      }
      if($org_nm == "") $org_nm = "-";
      
      require_once(XOCP_DOC_ROOT."/modules/pms/class/ajax_selectorg.php");
      $ajax = new _hris_class_SelectOrgAjax("slrjx");
      $js = "";
      $js .= "\n<script type=\"text/javascript\" src=\"".XOCP_SERVER_SUBDIR."/include/treeorg.js\"></script>";
      $js .= $ajax->getJs();
      $js .= "\n<script type='text/javascript'>\n//<![CDATA[
      
      function toggle_ytd() {
         orgjx_app_setRadarYTD(function(_data) {
            location.reload();
         });
      
      }
      
      function _pms_choose_month(m,d,e) {
         if(d.className=='xlnk') {
            $('sp_month').innerHTML = d.innerHTML;
         }
         $('dvmonth').style.display = 'none';
         if(m<1) m = 1;
         if(m>12) m = 12;
         orgjx_app_setRadarMonth(m,function(_data) {
            location.reload();
         });
      }
      
      function _pms_show_month(d,e) {
         if($('dvmonth').style.display=='none') {
            $('dvmonth').style.display='';
            $('dvmonth').style.left = oX(d)+'px';
            $('dvmonth').style.top = (oY(d)+d.offsetHeight)+'px';
         } else {
            $('dvmonth').style.display = 'none';
         }
      }
      
      function save_dashboard() {
         ajax_feedback = _caf;
         var ret = _parseForm('frmdash');
         orgjx_app_saveDashboard(ret,function(_data) {
            location.reload();
            setupdashboardbox.fade();
         });
      }
      
      var setupdashboardedit = null;
      var setupdashboardbox = null;
      function editdashboard() {
         setupdashboardedit = _dce('div');
         setupdashboardedit.setAttribute('id','setupdashboardedit');
         setupdashboardedit = document.body.appendChild(setupdashboardedit);
         setupdashboardedit.sub = setupdashboardedit.appendChild(_dce('div'));
         setupdashboardedit.sub.setAttribute('id','innersetupdashboardedit');
         setupdashboardbox = new GlassBox();
         setupdashboardbox.init('setupdashboardedit','800px','505px','hidden','default',false,false);
         setupdashboardbox.lbo(false,0.3);
         setupdashboardbox.appear();
         
         orgjx_app_editDashboard(function(_data) {
            $('innersetupdashboardedit').innerHTML = _data;
         });
         
      }
      
      
      var dashboardtooltip = null;
      function dashboard_tooltip(pms_objective_id,d,e) {
         if(!$('gaugetip_'+pms_objective_id)) return;
         if(!dashboardtooltip) {
            dashboardtooltip = _dce('div');
            dashboardtooltip.setAttribute('style','visibility:hidden;position:absolute;padding:5px;-moz-border-radius:5px;border:1px solid #777;background-color:#ffffdd;left:0px;-moz-box-shadow:-1px -1px 1px #00f;-moz-box-shadow:1px 1px 3px #000;color:#555;');
            dashboardtooltip = document.body.appendChild(dashboardtooltip);
            dashboardtooltip.style.left = '-1000px';
            dashboardtooltip.style.top = '-1000px';
            dashboardtooltip.arrow = _dce('img');
            dashboardtooltip.arrow.setAttribute('style','position:absolute;left:0px;');
            dashboardtooltip.arrow.src = '".XOCP_SERVER_SUBDIR."/images/leftmiddle.png';
            dashboardtooltip.arrow = dashboardtooltip.appendChild(dashboardtooltip.arrow);
            dashboardtooltip.arrow.style.top = '3px';
            dashboardtooltip.arrow.style.left = '-12px';
            dashboardtooltip.inner = dashboardtooltip.appendChild(_dce('div'));
         }
         var xtooltip = $('gaugetip_'+pms_objective_id);
         if(xtooltip) {
            dashboardtooltip.innerHTML = xtooltip.innerHTML;
            if(e.pageX>0) {
               dashboardtooltip.style.left = parseInt(e.pageX-dashboardtooltip.offsetWidth)+'px';
               dashboardtooltip.style.top = parseInt(e.pageY+3)+'px';
            } else {
               dashboardtooltip.style.left = parseInt(e.pageX+3)+'px';
               dashboardtooltip.style.top = parseInt(e.pageY+3)+'px';
            }
            if(parseInt(dashboardtooltip.style.left)<0) {
               dashboardtooltip.style.left = '0px';
            }
            dashboardtooltip.style.visibility = 'visible';
         }
         d.setAttribute('r','8px');
      }
      
      function hide_dashboard_tooltip(d) {
         if(dashboardtooltip) {
            dashboardtooltip.style.left = '-1000px';
            dashboardtooltip.style.top = '-1000px';
            dashboardtooltip.style.visibility = 'hidden';
         }
      }
      
     
      
      
      var dvtooltip = null;
      function show_ach_tooltip(pms_objective_id,d,e) {
         if(!dvtooltip) {
            dvtooltip = _dce('div');
            dvtooltip.setAttribute('style','visibility:hidden;position:absolute;padding:5px;-moz-border-radius:5px;border:1px solid #000;background-color:#ffffdd;left:0px;-moz-box-shadow:-1px -1px 1px #00f;-moz-box-shadow:1px 1px 3px #000;color:#555;');
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
         var xtooltip = $('achtooltip_'+pms_objective_id);
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
         d.setAttribute('r','8px');
      }
      
      function hide_ach_tooltip(d) {
         dvtooltip.style.left = '-1000px';
         dvtooltip.style.top = '-1000px';
         dvtooltip.style.visibility = 'hidden';
         d.setAttribute('r','5px');
      }
      
     
      function _org_select_org(org_id,d,e) {
         slrjx_app_setOrg(org_id,function(_data) {
            location.reload();
         });
      }
      
      var dv = null;
      function show_org_opt(d,e) {
         var Element = _gel('list_org');
         if (dv&&dv.style.display!='none') {
            var uls = _gel('navSlide');
            var dvx = _gel('dvSlide');
            new Effect.toggle(Element,'blind',{duration:0.2}); 
         } else {
            _destroy(uls);  
            dv = document.createElement('div');
            dv.setAttribute('id','dvSlide');
            dv.innerHTML = '';
            dv = Element.appendChild(dv);
            Element.dv = dv;
            Element.dv.appendChild(progress_span());
            slrjx_app_getOrgOpt(function(_data) {
               Element.dv.innerHTML = _data;
               new Effect.toggle(Element,'blind',{duration:0.2});
            });
          
         }
         return true;
      }
      
      var newHref = null;
      function selorgopt(org_id,org_nm) {
         var Element = _gel('list_org');
         new Effect.toggle(Element,'blind',{duration:0.2});
         slrjx_app_setOrg(org_id,obj_id,null);
         newHref = '".XOCP_SERVER_SUBDIR."/index.php?X_hris="._HRIS_SELECTORG_BLOCK."&org_id='+org_id+'&obj_id='+obj_id;
         setTimeout('gotoOrg();',300);
      }
      
      function gotoOrg() {
         location.href = newHref;
      }

      ".($showOpt==TRUE?"setTimeout('show_org_opt(null,null);',100);":"")."
      
      //]]>\n</script>";
      
      $js .= "\n<script type=\"text/javascript\" src=\"".XOCP_SERVER_SUBDIR."/modules/pms/include/treeorg.js\"></script>";
      
      
      return $js."<div class='orgsel'><table border='0' width='100%' cellpadding='2' cellspacing='0'>
              <tr><td id='hris_org_nm'>Level of Organization : <span style='font-weight:bold;'>".htmlentities($org_nm)."</span></td>
              <td align='right'>[<span class='xlnk' id='chorgsp' onclick='return show_org_opt(this,event);'>Change Level"
              ."</span>]</td></tr></table><div id='list_org' style='display:none;background-color:#FFFFFF;text-align:left;'></div></div>";
   }

   function pmsobjective() {
      $psid = $_SESSION["pms_psid"];
      $db=&Database::getInstance();
      $ajax = new _pms_class_ObjectiveAjax("orgjx");
      if(!isset($_SESSION["pms_org_id"])) {
         $_SESSION["pms_org_id"] = 1;
      }
      
      $org_id = $_SESSION["pms_org_id"];
      
      $pmsselobj = new _pms_class_SelectSession();
      $pmssel = "<div style='padding-bottom:2px;max-width:970px;'>".$pmsselobj->show()."</div>";
      
      if(!isset($_SESSION["pms_psid"])||$_SESSION["pms_psid"]==0) {
         return $pmssel;
      }
      
      $sql = "SELECT ach_cause_effect FROM pms_dashboard_setup"
           . " WHERE pms_org_id = '$org_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($ach_cause_effect)=$db->fetchRow($result);
      } else {
         $ach_cause_effect = "no";
         $sql = "INSERT INTO pms_dashboard_setup (pms_org_id,ach_cause_effect) VALUES ('$org_id','$ach_cause_effect')";
         $db->query($sql);
      }
      
      $step_y = 200;
      
      $sql = "SELECT a.pms_share_org_id,b.org_abbr,b.org_nm"
           . " FROM pms_org_share a"
           . " LEFT JOIN ".XOCP_PREFIX."orgs b ON b.org_id = a.pms_share_org_id"
           . " WHERE a.psid = '$psid' AND a.pms_org_id = '$org_id'"
           . " ORDER BY b.order_no";
      $result = $db->query($sql);
      $tdshare = "";
      $share_arr = array();
      $share_cnt = 0;
      $colgroup = "";
      if($db->getRowsNum($result)>0) {
         $share_cnt = $db->getRowsNum($result);
         $sharehead = "";
         while(list($pms_share_org_id,$pms_share_org_abbr,$pms_share_org_nm)=$db->fetchRow($result)) {
            //$tdshare .= "<td style='border-bottom:1px solid #333;border-left:1px solid #bbb;text-align:center;'><span class='xlnk' onclick='view_share(\"$pms_share_org_id\",this,event);'>$pms_share_org_abbr</span></td>";
            $share_arr[] = array($pms_share_org_id,$pms_share_org_nm,$pms_share_org_abbr);
            //$colgroup .= "<col width='50'/>";
         }
      } else {
         //$tdshare .= "<td style='border-bottom:1px solid #333;border-left:1px solid #bbb;text-align:center;'>-</td>";
         $sharehead = "";
         //$colgroup .= "<col width='50'/>";
      }
      
      $sql = "SELECT a.org_abbr,a.org_nm,b.org_class_nm FROM ".XOCP_PREFIX."orgs a LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . " WHERE org_id = '".$_SESSION["pms_org_id"]."'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($org_abbr,$org_nm,$org_class_nm)=$db->fetchRow($result);
         $orgsel = "<div style='padding:5px;border:1px solid #bbb;background-color:#ddd;'><span id='orgspan' class='xlnk' onclick='select_org(this,event);'>Level Organization : <span style='font-weight:bold;'>$org_nm $org_class_nm</span></span></div>";
      }
      
      $orgsel = "<div style='max-width:970px;'>".$this->showOrg()."</div>";
      
      reset($share_arr);
      
      $orgtbl = $this->renderOrg();
      
      if($found==0) {
         $_SESSION["pms_employee_id"] = $first_employee_id;
      }
      
      /////////////////////////// LOAD PROGRESS ///////////////////////////////////////////////////////////////////////////////////////////////////////////
      /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
      ////////////////////////////////////
      
      $sql = "SELECT org_id FROM ".XOCP_PREFIX."orgs WHERE parent_id = '$pms_org_id'";
      $result = $db->query($sql);
      $child_org = array();
      if($db->getRowsNum($result)>0) {
         while(list($org_idx)=$db->fetchRow($result)) {
            $child_org[$org_idx] = 1;
         }
      }
      
      
      $sql = "SELECT a.pms_objective_id,a.pms_perspective_id,a.pms_objective_text,b.pms_perspective_code,a.pms_objective_no,a.show_dashboard"
           . " FROM pms_objective a"
           . " LEFT JOIN pms_perspective b USING(pms_perspective_id)"
           . " LEFT JOIN pms_objective c ON c.pms_objective_id = a.pms_parent_objective_id"
           . " WHERE a.psid = '$psid' AND a.pms_org_id = '".$_SESSION["pms_org_id"]."'"
           //. " AND (c.pms_org_id IS NOT NULL OR c.pms_org_id != a.pms_org_id)"
           . " ORDER BY a.pms_perspective_id,a.pms_objective_no";
      $result = $db->query($sql);
      if($_SESSION["pms_org_id"]==1) _debuglog($sql);
      $acharr = array();
      $obj_arr = array();
      $arr_cause = array();
      $gaugetip = "";
      if($db->getRowsNum($result)>0) {
         while(list($pms_objective_idx,$pms_perspective_idx,$pms_objective_text,$pms_perspective_code,$pms_objective_no,$show_dashboard)=$db->fetchRow($result)) {
            
            $top_level_org_id = $this->recurseParentOrg($pms_objective_idx);
            
            /////// select cause objective .............
            $sql = "SELECT src_pms_objective_id FROM pms_cause_effect WHERE psid = '$psid' AND dst_pms_objective_id = '$pms_objective_idx'";
            $rcause = $db->query($sql);
            if($db->getRowsNum($rcause)>0) {
               while(list($cause_src_pms_objective_id)=$db->fetchRow($rcause)) {
                  $arr_cause[$pms_perspective_idx][$pms_objective_idx][$cause_src_pms_objective_id] = 1;
               }
            }
            ///$achYTD = translate_norm($this->calcYTD($pms_objective_idx));
            $achYTD = $this->calcYTD($pms_objective_idx);
            $acharr[$pms_perspective_idx][$pms_objective_idx] = array($show_dashboard,$achYTD);
            $obj_arr[$pms_objective_idx] = array($pms_objective_text,$pms_perspective_code,$pms_objective_no,$achYTD);
            
            $sql = "SELECT org_class_id FROM ".XOCP_PREFIX."orgs WHERE org_id = '".$_SESSION["pms_org_id"]."'";
            $rc = $db->query($sql);
            list($org_class_id)=$db->fetchRow($rc);
            $gaugetip .= "<div id='gaugetip_${pms_objective_idx}'><div style='padding:5px;'><span style='color:#000;font-weight:bold;'>${pms_perspective_code}${pms_objective_no}</span> ".htmlentities($pms_objective_text)."</div><table style='font-size:0.9em;' class='xxlist'><tbody>";
            switch($org_class_id) {
               //// section
               case 4:
               case 5:
                  
                  $sql = "SELECT pms_actionplan_id,pms_actionplan_text FROM pms_actionplan WHERE pms_objective_id = '$pms_objective_idx' GROUP BY pms_objective_id";
                  $resultx1 = $db->query($sql);
                  if($db->getRowsNum($resultx1)>0) { ////// yes, has ap
                     
                     while(list($pms_actionplan_id,$actionplan_text)=$db->fetchRow($resultx1)) {
                        $sql = "SELECT SUM(pms_share_weight),pms_actionplan_pic_employee_id FROM pms_actionplan_share"
                             . " WHERE psid = '$psid' AND pms_objective_id = '$pms_objective_idx'"
                             . " GROUP BY pms_actionplan_pic_employee_id";
                        $rsh = $db->query($sql);
                        if($db->getRowsNum($rsh)>0) {
                           while(list($pms_share_weightxxx,$pms_actionplan_pic_employee_idxxx)=$db->fetchRow($rsh)) {
                              //$pic_ach = translate_norm($this->_calcPeopleAch($pms_actionplan_pic_employee_idxxx,$pms_objective_idx));
                              $pic_ach = $this->_calcPeopleAch($pms_actionplan_pic_employee_idxxx,$pms_objective_idx);
                              
                              
                              $sql = "SELECT b.person_nm,a.alias_nm FROM ".XOCP_PREFIX."employee a"
                                   . " LEFT JOIN ".XOCP_PREFIX."persons b USING(person_id)"
                                   . " WHERE a.employee_id = '$pms_actionplan_pic_employee_idxxx'";
                              $re = $db->query($sql);
                              list($employee_nmx,$alias_nm)=$db->fetchRow($re);
                              $gaugetip .= "<tr><td style='vertical-align:top;'>".htmlentities($alias_nm)."</td>";
                              $gaugetip .= "<td style='text-align:right;vertical-align:top;'>".toMoney($pic_ach)."%</td></tr>";
                              
                              
                           }
                        }
                        
                     }
                     
                  }
                  
                  
                  
                  
                  
                  
                  
                  
                  
                  
                  
                  
                  
                  
                  
                  
                  
                  
                  
                  
                  
                  
                  
                  
                  
                  
                  
                  break;
               //// division, department, company
               case 1:
               case 2:
               case 3:
               default:
                  //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                  
                  $pms_org_id = $_SESSION["pms_org_id"];
                  
                  //// check has initiatives
                  $sql = "SELECT a.pms_objective_id,a.pms_objective_weight"
                       . " FROM pms_objective a"
                       . " LEFT JOIN pms_objective b ON b.pms_objective_id = a.pms_parent_objective_id"
                       . " WHERE a.psid = '$psid' AND a.pms_parent_objective_id = '$pms_objective_idx'"
                       . " AND a.pms_org_id = '$pms_org_id'"
                       . " AND b.pms_org_id = a.pms_org_id"
                       . " ORDER BY a.pms_objective_no";
                  $rchild = $db->query($sql);
                  $has_local_sub = 0;
                  $ttl_sub_weight = 0;
                  $initiative = $initiative_weight = array();
                  if($db->getRowsNum($rchild)>0) {
                     while(list($sub_pms_objective_id,$sub_weight)=$db->fetchRow($rchild)) {
                        $has_local_sub++;
                        $ttl_sub_weight = _bctrim(bcadd($ttl_sub_weight,$sub_weight));
                        $initiative[$sub_pms_objective_id] = 1;
                        $initiative_weight[$sub_pms_objective_id] = $sub_weight;
                     }
                  }
                  
                  if($has_local_sub>0) {
                     
                     $ttl_initiative = 0;
                     $ttl_initiative_weight = 0;
                     
                     $gaugetip .= "<tr>"
                                . "<td style='background-color:#eee;'></td>"
                                . "<td style='background-color:#eee;'></td>"
                                . "<td style='text-align:right;vertical-align:top;font-weight:bold;background-color:#eee;'>a</td>"
                                . "<td style='text-align:right;vertical-align:top;font-weight:bold;background-color:#eee;'>b</td>"
                                . "<td style='text-align:right;vertical-align:top;font-weight:bold;background-color:#eee;'>a x b</td></tr>";
                           
                     foreach($initiative_weight as $sub_pms_objective_id=>$sub_weight) {
                        $sql = "SELECT b.pms_perspective_code,a.pms_objective_no,a.pms_objective_text"
                             . " FROM pms_objective a"
                             . " LEFT JOIN pms_perspective b USING(pms_perspective_id)"
                             . " WHERE a.pms_objective_id = '$sub_pms_objective_id'"
                             . " AND a.psid = '$psid'";
                        $resultsub = $db->query($sql);
                        if($db->getRowsNum($resultsub)>0) {
                           list($sub_pms_perspective_code,$sub_pms_objective_no,$sub_pms_objective_text)=$db->fetchRow($resultsub);
                           
                           $gaugetip .= "<tr>"
                                      . "<td style='background-color:#eee;'>${sub_pms_perspective_code}${sub_pms_objective_no}</td>"
                                      . "<td style='background-color:#eee;'>".htmlentities($sub_pms_objective_text)."</td>"
                                      . "<td style='text-align:right;vertical-align:top;font-weight:bold;background-color:#eee;'>".toMoney($sub_weight)."</td>"
                                      . "<td style='text-align:right;vertical-align:top;font-weight:bold;background-color:#eee;'></td>"
                                      . "<td style='text-align:right;vertical-align:top;font-weight:bold;background-color:#eee;'></td></tr>";
                           
                           
                           $sql = "SELECT pms_kpi_id FROM pms_kpi WHERE pms_objective_id = '$sub_pms_objective_id'";
                           $resultkpi = $db->query($sql);
                           
                           if($db->getRowsNum($resultkpi)>0) { /// yes, has kpi
                              
                              $kpi_share_array = array();
                              
                              while(list($pms_kpi_id)=$db->fetchRow($resultkpi)) {
                                 
                                 
                                 $sql = "SELECT pms_share_org_id,pms_share_weight FROM pms_kpi_share WHERE psid = '$psid' AND pms_objective_id = '$sub_pms_objective_id' AND pms_kpi_id = '$pms_kpi_id'";
                                 $rkpishare = $db->query($sql);
                                 _debuglog($sql);
                                 if($db->getRowsNum($rkpishare)>0) {
                                    while(list($pms_share_org_idx,$pms_share_weightx)=$db->fetchRow($rkpishare)) {
                                       //if(isset($child_org[$pms_share_org_idx])&&$child_org[$pms_share_org_idx]==1) {
                                          $kpi_share_ttl += $pms_share_weightx;
                                          $kpi_share_array[$pms_share_org_idx] = _bctrim(bcadd($kpi_share_array[$pms_share_org_idx],$pms_share_weightx));
                                          
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
                                   . " WHERE a.pms_parent_objective_id = '$sub_pms_objective_id'"
                                   . " AND a.pms_org_id = '$pms_share_org_idx'"
                                   . " ORDER BY c.order_no,a.pms_objective_no";
                              $rorg = $db->query($sql);
                              if($db->getRowsNum($rorg)>0) {
                                 while(list($sub_pms_objective_idxxx,$sub_pms_objective_textxxx,$sub_pms_org_idxxx,$sub_org_nmxxx,$pms_perspective_codexxx,$pms_objective_noxxx)=$db->fetchRow($rorg)) {
                                    
                                    $ach_sub_so = $this->_calcSO($sub_pms_objective_idxxx);
                                    $ach_sub_so_weight = ($pms_share_weightx*$ach_sub_so);
                                    $ttl_ach_sub_so_weight += $ach_sub_so_weight;
                                    
                                    $gaugetip .= "<tr><td style='vertical-align:top;'>".htmlentities($sub_org_nmxxx)."</td>";
                                    $gaugetip .= "<td style='text-align:left;vertical-align:top;'>"
                                               . "<span style='font-weight:bold;color:black;'>${pms_perspective_codexxx}${pms_objective_noxxx}</span> "
                                               . htmlentities($sub_pms_objective_textxxx)."</td>";
                                    $gaugetip .= "<td style='text-align:right;vertical-align:top;'>"
                                               . toMoney($ach_sub_so)
                                               . "%</td>";
                                    $gaugetip .= "<td style='text-align:right;vertical-align:top;'>"
                                               . toMoney($pms_share_weightx)
                                               . "</td>";
                                    $gaugetip .= "<td style='text-align:right;vertical-align:top;'>"
                                               . toMoney($ach_sub_so_weight)
                                               . "</td>";
                                    $gaugetip .= "</tr>";
                                    
                                    $ttl_share += $pms_share_weightx;
                                 }
                              }
                           }
                           
                     
                           
                           
                           
                           
                        }
                        
                        
                        
                        $gaugetip .= "<tr>"
                                   . "<td style='font-weight:bold;background-color:#eee;' colspan='3'>Subtotal</td>"
                                   . "<td style='font-weight:bold;background-color:#eee;text-align:right;vertical-align:top;'>".toMoney($ttl_share)."</td>"
                                   . "<td style='font-weight:bold;background-color:#eee;text-align:right;vertical-align:top;'>".toMoney($ttl_ach_sub_so_weight)."</td></tr>";
                        
                        $gaugetip .= "<tr>"
                                   . "<td style='font-weight:bold;background-color:#ddd;color:#000;'>Subtotal Result</td>"
                                   . "<td style='font-weight:bold;background-color:#ddd;color:#000;text-align:right;vertical-align:top;' colspan='4'>".toMoney($ttl_ach_sub_so_weight)." / ".toMoney($ttl_share)." = ".toMoney($ttl_ach_sub_so_weight/$ttl_share)."</td></tr>";
                        
                        $ttl_initiative += $ttl_ach_sub_so_weight/$ttl_share;
                        $ttl_initiative_weight += ($ttl_ach_sub_so_weight/$ttl_share)*$sub_weight;
                        
                        
                        
                        
                     }
                     
                  } else {
                     
                     
                     $sql = "SELECT pms_kpi_id FROM pms_kpi WHERE pms_objective_id = '$pms_objective_idx'";
                     $resultkpi = $db->query($sql);
                     
                     if($db->getRowsNum($resultkpi)>0) { /// yes, has kpi
                        
                        $kpi_share_array = array();
                        
                        while(list($pms_kpi_id)=$db->fetchRow($resultkpi)) {
                           
                           
                           $sql = "SELECT pms_share_org_id,pms_share_weight FROM pms_kpi_share WHERE psid = '$psid' AND pms_objective_id = '$pms_objective_idx' AND pms_kpi_id = '$pms_kpi_id'";
                           $rkpishare = $db->query($sql);
                           _debuglog($sql);
                           if($db->getRowsNum($rkpishare)>0) {
                              while(list($pms_share_org_idx,$pms_share_weightx)=$db->fetchRow($rkpishare)) {
                                 //if(isset($child_org[$pms_share_org_idx])&&$child_org[$pms_share_org_idx]==1) {
                                    $kpi_share_ttl += $pms_share_weightx;
                                    $kpi_share_array[$pms_share_org_idx] = _bctrim(bcadd($kpi_share_array[$pms_share_org_idx],$pms_share_weightx));
                                    
                                 //}
                              }
                           }
                        }
                     }
                     
                     $ttl_share = 0;
                     $ttl_ach_sub_so_weight = 0;
                     
                     $gaugetip .= "<tr>"
                                . "<td style='background-color:#eee;'></td>"
                                . "<td style='background-color:#eee;'></td>"
                                . "<td style='text-align:right;vertical-align:top;font-weight:bold;background-color:#eee;'>a</td>"
                                . "<td style='text-align:right;vertical-align:top;font-weight:bold;background-color:#eee;'>b</td>"
                                . "<td style='text-align:right;vertical-align:top;font-weight:bold;background-color:#eee;'>a x b</td></tr>";
                     
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
                              
                              $ach_sub_so = $this->_calcSO($sub_pms_objective_id);
                              $ach_sub_so_weight = ($pms_share_weightx*$ach_sub_so);
                              $ttl_ach_sub_so_weight += $ach_sub_so_weight;
                              
                              $gaugetip .= "<tr><td style='vertical-align:top;'>".htmlentities($sub_org_nm)."</td>";
                              $gaugetip .= "<td style='text-align:left;vertical-align:top;'>"
                                         . "<span style='font-weight:bold;color:black;'>${pms_perspective_code}${pms_objective_no}</span> "
                                         . htmlentities($sub_pms_objective_text)."</td>";
                              $gaugetip .= "<td style='text-align:right;vertical-align:top;'>"
                                         . toMoney($ach_sub_so)
                                         . "%</td>";
                              $gaugetip .= "<td style='text-align:right;vertical-align:top;'>"
                                         . toMoney($pms_share_weightx)
                                         . "</td>";
                              $gaugetip .= "<td style='text-align:right;vertical-align:top;'>"
                                         . toMoney($ach_sub_so_weight)
                                         . "</td>";
                              $gaugetip .= "</tr>";
                              
                              $ttl_share += $pms_share_weightx;
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
                     
                     
                     
                  }
                  
                  
                  
                  break;
            }
            $gaugetip .= "</tbody></table><div style='font-size:0.8em;padding-left:2px;'>a = achievement<br/>b = share weight</div></div>";
            /*
            if(count($_SESSION["pica"])>0) {
               $gaugetip .= "<div id='gaugetip_${pms_objective_idx}'><table style='font-size:0.9em;' class='xxlist'><tbody>";
               
               foreach($_SESSION["pica"] as $k=>$v) {
                  list($employee_idxx,$pms_objective_idxx,$actionplan_textxx,$current_achievementxx)=$v;
                  $sql = "SELECT b.person_nm,a.alias_nm FROM ".XOCP_PREFIX."employee a"
                       . " LEFT JOIN ".XOCP_PREFIX."persons b USING(person_id)"
                       . " WHERE a.employee_id = '$employee_idxx'";
                  $re = $db->query($sql);
                  list($employee_nmx,$alias_nm)=$db->fetchRow($re);
                  $gaugetip .= "<tr><td style='vertical-align:top;'>".htmlentities($alias_nm)."</td>";
                  $gaugetip .= "<td style='text-align:right;vertical-align:top;'>".toMoney($current_achievementxx)."%</td></tr>";
                             //$gaugetip .= "<td style='vertical-align:top;'>".htmlentities($actionplan_textxx)."</td>";
               }
               
               $gaugetip .= "</tbody></table></div>";
            } else {
               $gaugetip .= "<div id='gaugetip_${pms_objective_idx}'>"._EMPTY;
               $gaugetip .= "</div>";
            }
            */
            
            
            
         }
      }
      
      ////////////////////////////////////////////////////////////////////////////
      //////////// CAUSE EFFECT CALCULATION //////////////////////////////////////
      ////////////////////////////////////////////////////////////////////////////
      
      krsort($arr_cause);
      $arr_cause_result = array();
      foreach($arr_cause as $pms_perspective_id=>$v) {
         foreach($v as $pms_objective_id=>$vv) {
            list($pms_objective_textx,$pms_perspective_codex,$pms_objective_nox,$ach)=$obj_arr[$pms_objective_id];
            $cause_cnt = 1;
            $cause_ttl = $ach;
            $log = "${pms_perspective_codex}${pms_objective_nox}:$ach + ";
            foreach($vv as $src_pms_objective_id=>$vvv) {
               list($pms_objective_text,$pms_perspective_code,$pms_objective_no,$ach)=$obj_arr[$src_pms_objective_id];
               $cause_cnt++;
               $cause_ttl = _bctrim(bcadd($cause_ttl,$ach));
               $log .= "${pms_perspective_code}${pms_objective_no}:$ach + ";
            }
            $arr_cause_result[$pms_objective_id] = _bctrim(bcdiv($cause_ttl,$cause_cnt));
            //_debuglog("${pms_perspective_codex}${pms_objective_nox}:".$arr_cause_result[$pms_objective_id]." = (".substr($log,0,-3).") / $cause_cnt");
         }
      }
      
      /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
      /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
      
      $ttl_obj_cnt = 0;
      $ttl_obj_ach = 0;
      $ttl_obj_ach_cause = 0;
      foreach($acharr as $pms_perspective_id=>$v) {
         foreach($v as $pms_objective_idx=>$vv) {
            list($show_dashboardx,$achx)=$vv;
            $ttl_obj_cnt++;
            if(isset($arr_cause_result[$pms_objective_id])) {
               $ttl_obj_ach_cause += $arr_cause_result[$pms_objective_id];
            } else {
               $ttl_obj_ach_cause += $achx;
            }
            $ttl_obj_ach += $achx;
         }
      }
      
      if($ttl_obj_cnt>0) {
         $org_ach = $ttl_obj_ach/$ttl_obj_cnt;
         $org_ach_cause = $ttl_obj_ach_cause/$ttl_obj_cnt;
      } else {
         $org_ach = 0;
         $org_ach_cause = 0;
      }
      
      /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
      /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
      
      
      $ret = "<table style='table-layout:fixed;width:970px;'><colgroup><col width='310'/><col/></colgroup>"
           . "<tbody><tr><td style='vertical-align:top;'>$orgtbl</td>"
           . "<td>";
      
      $ret .= "<table style='width:100%;-moz-box-shadow:1px 1px 3px #000;-moz-border-radius:5px;border:1px solid #bbb;'><tbody>";
      
      $ret .= "<tr><td style='text-align:center;font-weight:bold;border:0px solid #bbb;color:black;background-color:#ddf;padding:5px;-moz-border-radius:5px;-moz-box-shadow:1px 1px 3px #000;'>"
            . "<span style='float:right;font-weight:normal;'>[<span class='ylnk' onclick='editdashboard();'>setup</span>]</span>"
            . "<div style=''>Achievement Recap</div>"
            . "</td>"
            . "</tr>";
      
      //// render org achievement first
      
      $sql = "SELECT a.org_nm,b.org_class_nm FROM ".XOCP_PREFIX."orgs a"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . " WHERE a.org_id = '$org_id'";
      $result = $db->query($sql);
      list($org_nm,$org_class_nm)=$db->fetchRow($result);
      
      $ret .= "<tr><td style='border-bottom:0px solid #bbb;background-color:#fff;'>";
      
      $ret .= "\n<svg:svg xmlns:svg='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink' version='1.1'  style='width:650px;height:".((1*$step_y)+20)."px;border:0px solid black;'>";
      
      $ret .= "<svg:filter id='drop_shadow_toplevel' width='195px' height='195px'>";
      $ret .= "<svg:feGaussianBlur in='SourceAlpha' result='blur-out' stdDeviation='3'/>";
      $ret .= "<svg:feOffset in='blur-out' result='the-shadow' dx='2' dy='2'/>";
      $ret .= "</svg:filter>";

      $ret .= "<svg:filter id='emboss_toplevel'>";
      $ret .= "<svg:feGaussianBlur in='SourceAlpha' stdDeviation='3' result='blur'/>";
      $ret .= "<svg:feSpecularLighting in='blur' surfaceScale='-3' style='lighting-color:white' specularConstant='1' specularExponent='16' result='spec' kernelUnitLength='1' >";
      $ret .= "<svg:feDistantLight azimuth='45' elevation='45' />";
      $ret .= "</svg:feSpecularLighting>";
      $ret .= "<svg:feComposite in='spec' in2='SourceGraphic' operator='in' result='specOut'/>";
      $ret .= "</svg:filter>";
      
      $ret .= "<svg:filter id='drop_shadow_text_toplevel' width='195px' height='195px'>";
      $ret .= "<svg:feGaussianBlur in='SourceAlpha' result='blur-out' stdDeviation='2'/>";
      $ret .= "<svg:feOffset in='blur-out' result='the-shadow' dx='2' dy='2'/>";
      $ret .= "<svg:feBlend in='SourceGraphic' in2='the-shadow' mode='normal'/>";
      $ret .= "</svg:filter>";
      
      $ret .= "<svg:filter id='itoplevel' x='0%' y='0%' width='100%' height='100%'>";
      $ret .= "<svg:feImage xlink:href = '".XOCP_SERVER_SUBDIR."/modules/pms/images/gauge_meter.png'/>";
      $ret .= "</svg:filter>";
      
      $ret .= "<svg:filter id='igtoplevel' x='0%' y='0%' width='100%' height='100%'>";
      $ret .= "<svg:feImage xlink:href = '".XOCP_SERVER_SUBDIR."/modules/pms/images/glass_gauge_meter.png'/>";
      $ret .= "</svg:filter>";
                                                               
      $ret .= "<svg:g>"; /// content here
      
      $no = 1;
      
      $ret .= "<svg:circle cx='325' cy='".(10+75)."' r='70px' fill='#000000' stroke='black' stroke-width='0px' filter='url(#drop_shadow_toplevel)'/>";
      $ret .= "<svg:rect x='250' y='10' width='150' height='150' filter='url(#itoplevel)'/>";
                  
      /// score
      $ret .= "<svg:text x='325' y='".(10+180)."' style='text-anchor:middle;fill:#000; font-size:14px;font-weight:bold;font-family:Arial;'>".htmlentities("$org_nm $org_class_nm")."</svg:text>";
                  
      $marker = $this->createMarker("_toplevel",325,85,$org_ach,$org_ach_cause,$ach_cause_effect);
      
      $ret .= $marker;
      
      $ret .= "<svg:rect x='250' y='10' width='150' height='150' filter='url(#igtoplevel)'/>";
      
      $ret .= "</svg:g>";
      
      $ret .= "</svg:svg>";
      
      
      
      $ret .= "</td></tr>";
      
      
      $sql = "SELECT a.org_nm,b.org_class_nm FROM ".XOCP_PREFIX."orgs a"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . " WHERE a.org_id = '".($_SESSION["pms_org_id"])."'";
      $result = $db->query($sql);
      list($sorg_nm,$sorg_class_nm)=$db->fetchRow($result);
      $ret .= "<tr><td style='text-align:center;font-weight:bold;border:0px solid #bbb;color:black;background-color:#ddf;padding:5px;-moz-border-radius:5px;-moz-box-shadow:1px 1px 3px #000;'>"
            . htmlentities("Detail for $sorg_nm $sorg_class_nm")
            . "</td>"
            . "</tr>";
      
      
      $sql = "SELECT pms_perspective_code,pms_perspective_id,pms_perspective_name FROM pms_perspective WHERE psid = '$psid' ORDER BY pms_perspective_id";
      $result = $db->query($sql);
      $ttlw = 0;
      $ttl_pms_share = array();
      $job_nm = $job_abbr = "";
      $pno = 0;
      if($db->getRowsNum($result)>0) {
         while(list($pms_perspective_code,$pms_perspective_id,$pms_perspective_name)=$db->fetchRow($result)) {
            //$ret .= "<tr><td style='font-weight:bold;border:0px solid #bbb;color:black;background-color:#ddf;padding:5px;-moz-border-radius:5px;-moz-box-shadow:1px 1px 3px #000;'>"
            //      . "$pms_perspective_name Perspective"
            //      . "</td>"
            //      . "</tr>";
            
            
            $pno++;
            
            $cnt = 0;
            if(isset($acharr[$pms_perspective_id])) {
               foreach($acharr[$pms_perspective_id] as $pms_objective_idx=>$vv) {
                  list($show_dashboardx,$achx)=$vv;
                  if($show_dashboardx==1) {
                     $cnt++;
                  }
               }
            }
            
            
            $so = "";
            $so_no = 0;
            if($cnt>0) {
               $ret .= "<tr><td style='border-bottom:1px solid #bbb;".($pno%2!=0?"background-color:#ddd;":"background-color:#eee;")."'>";
               
               
               $offset_y = 10;
               $row_cnt = 1;
               
               switch($cnt) {
                  case 1:
                     $cx[0] = 275;
                     $cy[0] = (0*$step_y)+$offset_y;
                     break;
                  case 2:
                     $cx[0] = 175;
                     $cy[0] = (0*$step_y)+$offset_y;
                     $cx[1] = 475;
                     $cy[1] = (0*$step_y)+$offset_y;
                     break;
                  case 3:
                     $cx[0] = 175;
                     $cy[0] = (0*$step_y)+$offset_y;
                     $cx[1] = 325;
                     $cy[1] = (0*$step_y)+$offset_y;
                     $cx[2] = 475;
                     $cy[2] = (0*$step_y)+$offset_y;
                     break;
                  case 4:
                     $cx[0] = 100;
                     $cy[0] = (0*$step_y)+$offset_y;
                     $cx[1] = 250;
                     $cy[1] = (0*$step_y)+$offset_y;
                     $cx[2] = 400;
                     $cy[2] = (0*$step_y)+$offset_y;
                     $cx[3] = 550;
                     $cy[3] = (0*$step_y)+$offset_y;
                     break;
                  case 5:
                     $cx[0] = 175;
                     $cy[0] = (0*$step_y)+$offset_y;
                     $cx[1] = 325;
                     $cy[1] = (0*$step_y)+$offset_y;
                     $cx[2] = 475;
                     $cy[2] = (0*$step_y)+$offset_y;
                     
                     
                     $cx[3] = 250;
                     $cy[3] = (1*$step_y)+$offset_y;
                     $cx[4] = 400;
                     $cy[4] = (1*$step_y)+$offset_y;
                     $row_cnt = 2;
                     break;
                  case 6:
                     $cx[0] = 175;
                     $cy[0] = (0*$step_y)+$offset_y;
                     $cx[1] = 325;
                     $cy[1] = (0*$step_y)+$offset_y;
                     $cx[2] = 475;
                     $cy[2] = (0*$step_y)+$offset_y;
                     
                     $cx[3] = 175;
                     $cy[3] = (1*$step_y)+$offset_y;
                     $cx[4] = 325;
                     $cy[4] = (1*$step_y)+$offset_y;
                     $cx[5] = 475;
                     $cy[5] = (1*$step_y)+$offset_y;
                     $row_cnt = 2;
                     break;
                  case 7:
                     $cx[0] = 100;
                     $cy[0] = (0*$step_y)+$offset_y;
                     $cx[1] = 250;
                     $cy[1] = (0*$step_y)+$offset_y;
                     $cx[2] = 400;
                     $cy[2] = (0*$step_y)+$offset_y;
                     $cx[3] = 550;
                     $cy[3] = (0*$step_y)+$offset_y;
                     
                     $cx[4] = 175;
                     $cy[4] = (1*$step_y)+$offset_y;
                     $cx[5] = 325;
                     $cy[5] = (1*$step_y)+$offset_y;
                     $cx[6] = 475;
                     $cy[6] = (1*$step_y)+$offset_y;
                     $row_cnt = 2;
                     break;
                  case 8:
                     $cx[0] = 100;
                     $cy[0] = (0*$step_y)+$offset_y;
                     $cx[1] = 250;
                     $cy[1] = (0*$step_y)+$offset_y;
                     $cx[2] = 400;
                     $cy[2] = (0*$step_y)+$offset_y;
                     $cx[3] = 550;
                     $cy[3] = (0*$step_y)+$offset_y;
                     $cx[4] = 100;
                     $cy[4] = (1*$step_y)+$offset_y;
                     $cx[5] = 250;
                     $cy[5] = (1*$step_y)+$offset_y;
                     $cx[6] = 400;
                     $cy[6] = (1*$step_y)+$offset_y;
                     $cx[7] = 550;
                     $cy[7] = (1*$step_y)+$offset_y;
                     $row_cnt = 2;
                     break;
                  case 9:
                     $cx[0] = 175;
                     $cy[0] = (0*$step_y)+$offset_y;
                     $cx[1] = 325;
                     $cy[1] = (0*$step_y)+$offset_y;
                     $cx[2] = 475;
                     $cy[2] = (0*$step_y)+$offset_y;
                     $cx[3] = 175;
                     $cy[3] = (1*$step_y)+$offset_y;
                     $cx[4] = 325;
                     $cy[4] = (1*$step_y)+$offset_y;
                     $cx[5] = 475;
                     $cy[5] = (1*$step_y)+$offset_y;
                     $cx[6] = 175;
                     $cy[6] = (2*$step_y)+$offset_y;
                     $cx[7] = 325;
                     $cy[7] = (2*$step_y)+$offset_y;
                     $cx[8] = 475;
                     $cy[8] = (2*$step_y)+$offset_y;
                     $row_cnt = 3;
                     break;
                  default:
                     $step_x = 150;
                     $more = $cnt%4;
                     $row_cnt = 0;
                     $row_n = 0;
                     $no_n = 0;
                     for($ix=0;$ix<=($cnt-$more);$ix++) {
                        if($no_n>=4) {
                           $no_n=0;
                           $row_n++;
                           $row_cnt++;
                        }
                        $cx[$ix] = ($no_n+1)*$step_x-50;
                        $cy[$ix] = ($row_n*$step_y)+$offset_y;
                        $no_n++;
                     }
                     //$row_n++;
                     //$row_cnt++;
                     $step_x = round(650/($more+1));
                     $no_n = 0;
                     for($ix=($cnt-$more);$ix<$cnt;$ix++) {
                        $cx[$ix] = ($no_n+1)*$step_x;
                        $cy[$ix] = ($row_n*$step_y)+$offset_y;
                        $no_n++;
                     }
                     $row_cnt++;
                     break;
               }
               
               $svg = "\n<svg:svg xmlns:svg='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink' version='1.1'  style='width:650px;height:".(($row_cnt*$step_y)+20)."px;border:0px solid black;'>";
               
               $svg .= "<svg:filter id='drop_shadow${pno}' width='195px' height='195px'>";
               $svg .= "<svg:feGaussianBlur in='SourceAlpha' result='blur-out' stdDeviation='3'/>";
               $svg .= "<svg:feOffset in='blur-out' result='the-shadow' dx='2' dy='2'/>";
               $svg .= "</svg:filter>";
               
               $svg .= "<svg:filter id='drop_shadow_text${pno}' width='195px' height='195px'>";
               $svg .= "<svg:feGaussianBlur in='SourceAlpha' result='blur-out' stdDeviation='3'/>";
               $svg .= "<svg:feOffset in='blur-out' result='the-shadow' dx='2' dy='2'/>";
               $svg .= "<svg:feBlend in='SourceGraphic' in2='the-shadow' mode='normal'/>";
               $svg .= "</svg:filter>";
               
               
               $svg .= "<svg:filter id='emboss${pno}'>";
               $svg .= "<svg:feGaussianBlur in='SourceAlpha' stdDeviation='1' result='blur'/>";
               $svg .= "<svg:feSpecularLighting in='blur' surfaceScale='-1' style='lighting-color:white' specularConstant='1' specularExponent='8' result='spec' kernelUnitLength='1' >";
               $svg .= "<svg:feDistantLight azimuth='45' elevation='45' />";
               $svg .= "</svg:feSpecularLighting>";
               $svg .= "<svg:feComposite in='spec' in2='SourceGraphic' operator='in' result='specOut'/>";
               $svg .= "<svg:feBlend in='SourceGraphic' in2='specOut' mode='normal'/>";
               $svg .= "</svg:filter>";
               
               
               $svg .= "<svg:filter id='i${pno}' x='0%' y='0%' width='100%' height='100%'>";
               $svg .= "<svg:feImage xlink:href = '".XOCP_SERVER_SUBDIR."/modules/pms/images/gauge_meter.png'/>";
               $svg .= "</svg:filter>";
               
               $svg .= "<svg:filter id='ig${pno}' x='0%' y='0%' width='100%' height='100%'>";
               $svg .= "<svg:feImage xlink:href = '".XOCP_SERVER_SUBDIR."/modules/pms/images/glass_gauge_meter.png'/>";
               $svg .= "</svg:filter>";
                                                                        
               $svg .= "<svg:g>";
               
               $no = 0;
               
               
               foreach($acharr[$pms_perspective_id] as $pms_objective_id=>$vvx) {
                  list($show_dashboardx,$achYTD)=$vvx;
                  if($show_dashboardx!=1) continue;
                  list($pms_objective_text,$pms_perspective_code,$pms_objective_no)=$obj_arr[$pms_objective_id];
                  
                  $wordwrapped = explode("||",wordwrap($pms_objective_text,25,"||"));
                  
                  $svg .= "<svg:circle cx='".($cx[$no])."' cy='".($cy[$no]+75)."' r='70px' fill='#000000' stroke='black' stroke-width='0px' filter='url(#drop_shadow${pno})'/>";
                  $svg .= "<svg:rect x='".($cx[$no]-75)."' y='".$cy[$no]."' width='150' height='150' filter='url(#i${pno})'/>";
                  
                  /// score
                  //$svg .= "<svg:text x='".($cx[$no])."' y='".($cy[$no]+110)."' style='text-anchor:middle;fill:#bbb; font-size:14px;font-weight:bold;font-family:Arial;'>$score</svg:text>";
                  $svg .= "<svg:text x='".($cx[$no])."' y='".($cy[$no]+165)."' style='text-anchor:middle;fill:#000; font-size:14px;font-weight:bold;font-family:Arial;'>${pms_perspective_code}${pms_objective_no}</svg:text>";
                  if(isset($arr_cause_result[$pms_objective_id])) {
                     $svg .= $this->createMarker($pno,$cx[$no],$cy[$no]+75,$achYTD,$arr_cause_result[$pms_objective_id],$ach_cause_effect);
                  } else {
                     $svg .= $this->createMarker($pno,$cx[$no],$cy[$no]+75,$achYTD,NULL,$ach_cause_effect);
                  }
                  $svg .= "<svg:rect x='".($cx[$no]-75)."' y='".$cy[$no]."' width='150' height='150' filter='url(#ig${pno})' onmousemove='dashboard_tooltip(\"$pms_objective_id\",this,evt);' onmouseout='hide_dashboard_tooltip(this);'/>";
                  $lw = 0;
                  foreach($wordwrapped as $ptx) {
                     $svg .= "<svg:text x='".($cx[$no])."' dy='".($lw*12)."' y='".($cy[$no]+180)."'  stroke-size='0.5' style='text-anchor:middle;fill:#777;font-size:12px;font-weight:normal;font-family:Arial;'>".htmlentities($ptx)."</svg:text>";
                     $lw++;
                  }
                  
                  $no++;
               }
               
               $svg .= "</svg:g>";
               $svg .= "</svg:svg>";
               
               $ret .= $svg;
               
               $ret .= "</td></tr>";
            
            }
         }
      }
      
      $ret .= "</tbody>"
            . "<tfoot>"
            . "<tr><td>&nbsp;</td></tr>"
            . "</tfoot>"
            . "</table>";
      
      
      
      
      $ret .= "</td></tr></tbody></table>";
      
      $ret .= "<div style='padding:100px;'>&nbsp;</div><div style='display:none;'>$gaugetip</div>";
      
      
      
      $_SESSION["html"]->registerLoadAction("drawShape");
      //// canvas
      $retx .= "
<canvas style='z-index:200;position:absolute;top:200px;left:200px;' id='canvas' width='1' height='1'></canvas>      
      ";
      
      
      $_SESSION["html"]->addHeadScript("\n<script type='text/javascript'>//<![CDATA[
      
      
function drawShape(){
   return;
  // get the canvas element using the DOM
  var canvas = document.getElementById('canvas');

  // Make sure we don't execute when canvas isn't supported
  if (canvas.getContext){

    // use getContext to use the canvas for drawing
    var ctx = canvas.getContext('2d');
    //ctx.clearRect(0,0,150,150);
    ctx.globalCompositeOperation = 'destination-in';
    
    // Draw shapes

    ctx.beginPath();
    ctx.moveTo(75,25);
    ctx.quadraticCurveTo(25,25,25,62.5);
    ctx.quadraticCurveTo(25,100,50,100);
    ctx.quadraticCurveTo(50,120,30,125);
    ctx.quadraticCurveTo(60,120,65,100);
    ctx.quadraticCurveTo(125,100,125,62.5);
    ctx.quadraticCurveTo(125,25,75,25);
    //ctx.clip();
    ctx.stroke();

  } else {
    alert('You need Safari or Firefox 1.5+ to see this demo.');
  }
}
      
      
      function draw_canvas() {
         return;
         var canvas = $('canvas');
         if (canvas.getContext) {
            var ctx = canvas.getContext('2d');
            ctx.fillStyle = 'rgb(200,0,0)';
            ctx.fillRect (10, 10, 55, 50);
         }
      }
      
      
      //]]></script>");
      
      $js = $ajax->getJs()
          . "<script type='text/javascript' src='".XOCP_SERVER_SUBDIR."/include/calendar.js'></script>"
          . "<script type='text/javascript'>//<![CDATA[
      
      function select_employee(employee_id,d,e) {
         orgjx_app_selectEmployee(employee_id,function(_data) {
            location.reload();
         });
      }
      
      function select_pms_org(org_id,d,e) {
         orgjx_app_selectDashboardOrg(org_id,function(_data) {
            location.reload();
         });
      }
      
      function do_ck(src_pms_objective_id,dst_pms_objective_id,d,e) {
         var ckb = $('ckb_'+dst_pms_objective_id);
         var checked = 0;
         if(ckb.checked) {
            checked = 1;
         }
         orgjx_app_saveCauseEffectRelation(checked,src_pms_objective_id,dst_pms_objective_id,function(_data) {
         
         });
      }
      
      function select_perspective_effect(pms_perspective_id,d,e) {
         if(d.className=='selper_selected') {
            return;
         }
         var tds = $('trselper').childNodes;
         for(var i=0;i<tds.length;i++) {
            tds[i].className = 'selper';
            var pers = tds[i].id.split('_');
            $('dvpers_'+pers[1]).style.display = 'none';
         }
         d.className = 'selper_selected';
         $('dvpers_'+pms_perspective_id).style.display = '';
         
         var scrx = oX(dvce.d);
         if(scrx>500) {
            dvce.style.left = parseInt(oX(dvce.d)+dvce.d.offsetWidth-dvce.offsetWidth)+'px';
         } else {
            dvce.style.left = parseInt(oX(dvce.d))+'px';
         }
         dvce.arrow.style.top = parseInt(-10)+'px';
         if(scrx>500) {
            dvce.arrow.style.left = parseInt(dvce.offsetWidth-25)+'px';
         } else {
            dvce.arrow.style.left = parseInt(6)+'px';
         }
      }
      
      var dvce = null;
      function edit_cause_effect(pms_objective_id,d,e) {
         document.body.onclick = null;
         if(dvce) {
            _destroy(dvce);
            if(dvce.pms_objective_id==pms_objective_id) {
               dvce.pms_objective_id = null;
               dvce = null;
               return;
            }
         }
         d.dv = _dce('div');
         d.dv.setAttribute('style','position:absolute;padding:5px;-moz-border-radius:5px;border:1px solid #777;background-color:#ffffdd;left:0px;width:300px !important;-moz-box-shadow:1px 1px 3px #000;');
         d.dv.dv = d.dv.appendChild(_dce('div'));
         d.dv.dv.appendChild(progress_span());
         d.dv = d.parentNode.appendChild(d.dv);
         d.dv.style.top = parseInt(oY(d)+d.offsetHeight+8)+'px';
         var scrx = oX(d);
         if(scrx>500) {
            d.dv.style.left = parseInt(oX(d)+d.offsetWidth-d.dv.offsetWidth)+'px';
         } else {
            d.dv.style.left = parseInt(oX(d))+'px';
         }
         d.dv.arrow = _dce('img');
         d.dv.arrow.setAttribute('style','position:absolute;left:0px;');
         d.dv.arrow.src = '".XOCP_SERVER_SUBDIR."/images/bottommiddle.png';
         d.dv.arrow = d.dv.appendChild(d.dv.arrow);
         d.dv.arrow.style.top = parseInt(-10)+'px';
         if(scrx>500) {
            d.dv.arrow.style.left = parseInt(d.dv.offsetWidth-25)+'px';
         } else {
            d.dv.arrow.style.left = parseInt(6)+'px';
         }
         dvce = d.dv;
         dvce.d = d;
         dvce.pms_objective_id = pms_objective_id;
         dvce.onclick = function(event) {
            event.cancelBubble = true;
            return true;
         };
         
         orgjx_app_getCauseEffectRelation(pms_objective_id,function(_data) {
            dvce.dv.innerHTML = _data;
            var scrx = oX(dvce.d);
            if(scrx>500) {
               dvce.style.left = parseInt(oX(dvce.d)+dvce.d.offsetWidth-dvce.offsetWidth)+'px';
            } else {
               dvce.style.left = parseInt(oX(dvce.d))+'px';
            }
            
            dvce.arrow.style.top = parseInt(-10)+'px';
            if(scrx>500) {
               dvce.arrow.style.left = parseInt(dvce.offsetWidth-25)+'px';
            } else {
               dvce.arrow.style.left = parseInt(6)+'px';
            }
            
            
            
         });
         setTimeout('document.body.onclick = function() { document.body.onclick = null; _destroy(dvce); dvce=null; };',100);
         
      }
      
      function add_initiative(pms_objective_id,d,e) {
         orgjx_app_editInitiative(pms_objective_id,function(_data) {
            $('innereditsoedit').innerHTML = _data;
         });
      }
      
      function set_so_origin(pms_objective_id,d,e) {
         orgjx_app_setSOOrigin(pms_objective_id,function(_data) {
            $('parent_so').innerHTML = _data;
            $('so_editor').style.display = '';
            $('origin_chooser').style.display = 'none';
            $('vbtn').style.display = '';
         });
      }
      
      function change_so_origin(d,e) {
         $('so_editor').style.display = 'none';
         $('vbtn').style.display = 'none';
         $('origin_chooser').style.display = '';
      }
      
      function cancel_change_origin(d,e) {
         $('so_editor').style.display = '';
         $('origin_chooser').style.display = 'none';
         $('vbtn').style.display = '';
      }
      
      function kp_kpi_share_old(pms_objective_id,pms_kpi_id,pms_share_org_id,d,e) {
         var k = getkeyc(e);
         if(k==13) {
            save_kpi_share(pms_objective_id,pms_kpi_id,pms_share_org_id,d,e);
         }
      }
      
      function save_kpi_share_old(pms_objective_id,pms_kpi_id,pms_share_org_id,d,e) {
         var ret = _parseForm('frmkpi');
         orgjx_app_saveKPIShare(pms_objective_id,pms_kpi_id,pms_share_org_id,ret,function(_data) {
            location.reload(true);
         });
      }
      
      
      function save_kpi_share(val,pms_objective_id,pms_kpi_id,pms_share_org_id) {
         if(dveditshare) {
            dveditshare.d.innerHTML = val+'%';
         }
         orgjx_app_saveKPIShare(pms_objective_id,pms_kpi_id,pms_share_org_id,urlencode('pms_share_weight^^'+val),null);
      }
      
      function kp_kpi_share(d,e) {
         var k = getkeyc(e);
         if(d.chgt) {
            d.chgt.reset();
            d.chgt = null;
         }
         var val = parseFloat(d.value);
         if(k==13) {
            dveditshare.d.innerHTML = val+'%';
            _destroy(dveditshare);
            save_kpi_share(val,dveditshare.pms_objective_id,dveditshare.pms_kpi_id,dveditshare.pms_share_org_id);
            dveditshare.d = null;
            dveditshare = null;
         } else if (k==27) {
            _destroy(dveditshare);
            dveditshare.d = null;
            dveditshare = null;
         } else {
            d.chgt = new ctimer('save_kpi_share(\"'+val+'\",\"'+dveditshare.pms_objective_id+'\",\"'+dveditshare.pms_kpi_id+'\",\"'+dveditshare.pms_share_org_id+'\");',300);
            d.chgt.start();
         }
      }
      
      var dveditshare = null;
      function edit_kpi_share(pms_objective_id,pms_kpi_id,pms_share_org_id,d,e) {
         document.body.onclick = null;
         _destroy(dveditshare);
         if(dveditshare&&d==dveditshare.d) {
            dveditshare.d = null;
            dveditshare = null;
            return;
         }
         d.dv = _dce('div');
         d.dv.setAttribute('style','position:absolute;padding:5px;-moz-border-radius:5px;border:1px solid #777;background-color:#ffffcc;left:0px;');
         d.dv.innerHTML = '<div style=\"text-align:right;padding:2px;\">Share : <input onkeyup=\"kp_kpi_share(this,event);\" id=\"inp_kpi_share\" style=\"-moz-border-radius:3px;width:50px;text-align:center;\" type=\"text\" value=\"'+parseFloat(d.innerHTML)+'\"/>&nbsp;%</div>';
         d.dv = d.parentNode.appendChild(d.dv);
         d.dv.style.top = parseInt(oY(d)+d.offsetHeight+15)+'px';
         d.dv.style.left = parseInt(oX(d.parentNode)-d.dv.offsetWidth+d.parentNode.offsetWidth)+'px';
         d.dv.arrow = _dce('img');
         d.dv.arrow.setAttribute('style','position:absolute;left:0px;');
         d.dv.arrow.src = '".XOCP_SERVER_SUBDIR."/images/topmiddle.png';
         d.dv.arrow = d.dv.appendChild(d.dv.arrow);
         d.dv.arrow.style.top = '-12px';
         d.dv.arrow.style.left = parseInt(d.dv.offsetWidth-(d.parentNode.offsetWidth/2)-7)+'px';
         _dsa($('inp_kpi_share'));
         dveditshare = d.dv;
         dveditshare.d = d;
         dveditshare.pms_objective_id = pms_objective_id;
         dveditshare.pms_kpi_id = pms_kpi_id;
         dveditshare.pms_share_org_id = pms_share_org_id;
         setTimeout('document.body.onclick = function() { document.body.onclick = null; _destroy(dveditshare); };',100);
      }
      
      var editkpishareedit = null;
      var editkpisharebox = null;
      function edit_kpi_share_old(pms_objective_id,pms_kpi_id,pms_share_org_id,d,e) {
         editkpishareedit = _dce('div');
         editkpishareedit.setAttribute('id','editkpishareedit');
         editkpishareedit = document.body.appendChild(editkpishareedit);
         editkpishareedit.sub = editkpishareedit.appendChild(_dce('div'));
         editkpishareedit.sub.setAttribute('id','innereditkpishareedit');
         editkpisharebox = new GlassBox();
         editkpisharebox.init('editkpishareedit','700px','270px','hidden','default',false,false);
         editkpisharebox.lbo(false,0.3);
         editkpisharebox.appear();
         
         orgjx_app_editKPIShare(pms_objective_id,pms_kpi_id,pms_share_org_id,function(_data) {
            $('innereditkpishareedit').innerHTML = _data;
            _dsa($('pms_share_weight'));
         });
         
      }
      
      
      function delete_kpi(pms_objective_id,pms_kpi_id,d,e) {
         orgjx_app_deleteKPI(pms_objective_id,pms_kpi_id,function(_data) {
            location.reload(true);
         });
      }
      
      function save_kpi(pms_objective_id,pms_kpi_id,d,e) {
         var ret = _parseForm('frmkpi');
         orgjx_app_saveKPI(ret,function(_data) {
            location.reload(true);
         });
      }
      
      var editkpiedit = null;
      var editkpibox = null;
      function edit_kpi(pms_objective_id,pms_kpi_id,d,e) {
         editkpiedit = _dce('div');
         editkpiedit.setAttribute('id','editkpiedit');
         editkpiedit = document.body.appendChild(editkpiedit);
         editkpiedit.sub = editkpiedit.appendChild(_dce('div'));
         editkpiedit.sub.setAttribute('id','innereditkpiedit');
         editkpibox = new GlassBox();
         editkpibox.init('editkpiedit','700px','370px','hidden','default',false,false);
         editkpibox.lbo(false,0.3);
         editkpibox.appear();
         
         orgjx_app_editKPI(pms_objective_id,pms_kpi_id,function(_data) {
            $('innereditkpiedit').innerHTML = _data;
            _dsa($('pms_kpi_text'));
         });
         
      }
      
      function delete_share(pms_share_org_id,d,e) {
         orgjx_app_deleteShare(pms_share_org_id,function(_data) {
            location.reload(true);
         });
      }
      
      function kpi_mouse_over(d,e) {
         return;
         var dv = d.firstChild;
         dv.style.display = '';
      }
      
      function kpi_mouse_out(d,e) {
         return;
         var dv = d.firstChild;
         dv.style.display = 'none';
      }
      
      var vshareedit = null;
      var vsharebox = null;
      function view_share(pms_share_org_id,d,e) {
         vshareedit = _dce('div');
         vshareedit.setAttribute('id','vshareedit');
         vshareedit = document.body.appendChild(vshareedit);
         vshareedit.sub = vshareedit.appendChild(_dce('div'));
         vshareedit.sub.setAttribute('id','innervshareedit');
         vsharebox = new GlassBox();
         vsharebox.init('vshareedit','600px','270px','hidden','default',false,false);
         vsharebox.lbo(false,0.3);
         vsharebox.appear();
         
         orgjx_app_viewShare(pms_share_org_id,function(_data) {
            $('innervshareedit').innerHTML = _data;
         });
         
      }
      
      function delete_so(pms_objective_id,d,e) {
         orgjx_app_deleteSO(pms_objective_id,function(_data) {
            location.reload(true);
         });
      
      }
      
      function save_so(pms_objective_id,d,e) {
         var ret = _parseForm('frmobjective');
         orgjx_app_saveSO(ret,function(_data) {
            location.reload(true);
         });
      }
      
      function chgno(d,e) {
         var k = getkeyc(e);
         if(k==9) return;
         if(d.chgt) {
            d.chgt.reset();
            d.chgt = null;
         }
         d.chgt = new ctimer('_setcode();',500);
         d.chgt.start();
      }
      
      function _setcode() {
         var no = $('pms_objective_no').value;
         var d = $('pms_perspective_id');
         var p = d.options[d.selectedIndex].value;
         var px = p.split('|');
         $('pms_obj_code').innerHTML = px[1]+no;
         _dsa($('pms_objective_no'));
      }
      
      function chgpers(d,e) {
         var p = d.options[d.selectedIndex].value;
         orgjx_app_getNo(p,function(_data) {
            var data = recjsarray(_data);
            $('pms_objective_no').value = data[0];
            var p = d.options[d.selectedIndex].value;
            var px = p.split('|');
            $('pms_obj_code').innerHTML = px[1]+data[0];
            _dsa($('pms_objective_no'));
         });
      }
      
      var editsoedit = null;
      var editsobox = null;
      function edit_so(pms_objective_id,d,e) {
         editsoedit = _dce('div');
         editsoedit.setAttribute('id','editsoedit');
         editsoedit = document.body.appendChild(editsoedit);
         editsoedit.sub = editsoedit.appendChild(_dce('div'));
         editsoedit.sub.setAttribute('id','innereditsoedit');
         editsobox = new GlassBox();
         editsobox.init('editsoedit','800px','510px','hidden','default',false,false);
         editsobox.lbo(false,0.3);
         editsobox.appear();
         
         orgjx_app_editSO(pms_objective_id,function(_data) {
            $('innereditsoedit').innerHTML = _data;
            setTimeout(\"_dsa($('pms_objective_no'))\",300);
         });
         
      }
      
      var slorgedit = null;
      var slorgbox = null;
      function select_org(d,e) {
         slorgedit = _dce('div');
         slorgedit.setAttribute('id','slorgedit');
         slorgedit = document.body.appendChild(slorgedit);
         slorgedit.sub = slorgedit.appendChild(_dce('div'));
         slorgedit.sub.setAttribute('id','innerslorgedit');
         slorgbox = new GlassBox();
         slorgbox.init('slorgedit','700px','500px','hidden','default',false,false);
         slorgbox.lbo(false,0.3);
         slorgbox.appear();
         
         orgjx_app_browseOrgs(null,function(_data) {
            $('innerslorgedit').innerHTML = _data;
         });
         
      }
      
      function do_select_org(org_id,d,e) {
         orgjx_app_selectOrg(org_id,function(_data) {
            location.reload(true);
         });
      }
      
      ////// sharing
      
      var slorgshareedit = null;
      var slorgsharebox = null;
      function add_share(d,e) {
         slorgshareedit = _dce('div');
         slorgshareedit.setAttribute('id','slorgshareedit');
         slorgshareedit = document.body.appendChild(slorgshareedit);
         slorgshareedit.sub = slorgshareedit.appendChild(_dce('div'));
         slorgshareedit.sub.setAttribute('id','innerslorgshareedit');
         slorgsharebox = new GlassBox();
         slorgsharebox.init('slorgshareedit','700px','500px','hidden','default',false,false);
         slorgsharebox.lbo(false,0.3);
         slorgsharebox.appear();
         
         orgjx_app_browseOrgShare(null,function(_data) {
            $('innerslorgshareedit').innerHTML = _data;
         });
         
      }
      
      function do_select_org_share(org_id,d,e) {
         orgjx_app_addShare(org_id,function(_data) {
            location.reload(true);
         });
      }
      
      //]]>
      </script>";
      
      $_SESSION["html"]->addHeadScript($js);
      
      return $pmssel.$orgsel.$ret;
   }
   
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $this->pmsobjective();
            break;
         default:
            $ret = $this->pmsobjective();
            break;
      }
      return $ret;
   }
}

} // PMS_MONITOR_DEFINED
?>