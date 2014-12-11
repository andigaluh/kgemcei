<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_posmatrix.php                   //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_POSTMATRIXAJAX_DEFINED') ) {
   define('HRIS_POSTMATRIXAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");

function sort_job($job_class_id_a,$job_class_id_b) {
   
   list($job_class_idx,$job_class_nmx,$job_class_level_a) = $_SESSION["hris_poslevel"][$job_class_id_a];
   list($job_class_idx,$job_class_nmx,$job_class_level_b) = $_SESSION["hris_poslevel"][$job_class_id_b];
   
   if($job_class_level_a==$job_class_level_b) {
      return 0;
   }
   
   return ($job_class_level_a < $job_class_level_b) ? -1 : 1;
}

function sort_competency($a,$b) {
   global $job_rcl,$job_itj;
   $ax = 0;
   $bx = 0;
   $cnta = 0;
   $cntb = 0;
   list($a_competency_abbr,$a_competency_nm,$a_competency_id)=$a;
   list($b_competency_abbr,$b_competency_nm,$b_competency_id)=$b;
   foreach($job_rcl as $job_id=>$v) {
      $cnta++;
      if(isset($v[$a_competency_id])&&$v[$a_competency_id]>0) {
         $ax = 1;
         break;
      }
   }
   foreach($job_rcl as $job_id=>$v) {
      $cntb++;
      if(isset($v[$b_competency_id])&&$v[$b_competency_id]>0) {
         $bx = 1;
         break;
      }
   }
   if ($cnta == $cntb) {
      return strcmp($a_competency_abbr,$b_competency_abbr);
   }
   return ($cnta < $cntb) ? -1 : 1;
}
                    
class _hris_class_PositionMatrixAjax extends AjaxListener {
   
   function _hris_class_PositionMatrixAjax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/hris/class/ajax_posmatrix.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_setPosition","app_loadMatrix","app_setCompetencyClass","app_setCompetencyGroup");
   }
   
   function app_setCompetencyGroup($args) {
      $_SESSION["hris_posmatrix_compgroup"] = $args[0];
   }
   
   function app_setCompetencyClass($args) {
      $compgroup_id = $_SESSION["hris_posmatrix_compgroup"];
      $_SESSION["hris_posmatrix_compclass"][$compgroup_id] = $args[0];
   }
   
   function app_loadMatrix($args) {
      $db=&Database::getInstance();
      $division_id = $args[0];
      $subdiv_id = $args[1];
      $poslevel_id = $args[2];
      $wpx = "20px";
      $wcpx = "50px";
      
      $tips0 = array();
      $tips1 = array();
      
      $ret = "";
      
      if(!isset($_SESSION["hris_posmatrix_compgroup"])) {
         $_SESSION["hris_posmatrix_compgroup"] = 1;
      }
      $compgroup_id = $_SESSION["hris_posmatrix_compgroup"];
      
      $xset = array();
      $sql = "SELECT competency_class_set FROM ".XOCP_PREFIX."compgroup WHERE compgroup_id = '$compgroup_id'";
      $result = $db->query($sql);
      list($class_set)=$db->fetchRow($result);
      $xset = explode(",",$class_set);
      if(!isset($_SESSION["hris_posmatrix_compclass"][$compgroup_id])) {
         $_SESSION["hris_posmatrix_compclass"][$compgroup_id] = $xset[0];
      }
      $competency_class = $_SESSION["hris_posmatrix_compclass"][$compgroup_id];
      
      $jobs = array();
      if(is_array($_SESSION["hris_jobs"])) {
         uksort($_SESSION["hris_jobs"],"sort_job");
         foreach($_SESSION["hris_jobs"] as $job_class_idx=>$v) {
            foreach($v as $job_idx=>$w) {
               list($job_id,$job_nm,$job_abbr,$job_class_id,$job_class_nm)=$w;
               if($poslevel_id==0) {
                  $jobs[$job_id] = $w;
               } else if($poslevel_id==$job_class_id) {
                  $jobs[$job_id] = $w;
               }
            }
         }
      }
      
      $hdr = array();
      global $job_rcl,$job_itj;
      $job_rcl = array();
      $job_itj = array();
      $job_ttl_rcl = array();
      foreach($jobs as $job_idx=>$v) {
         $sql = "SELECT a.rcl,a.itj,b.competency_id,b.competency_nm,b.competency_abbr,b.desc_en,b.desc_id"
              . " FROM ".XOCP_PREFIX."job_competency a"
              . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
              . " WHERE a.job_id = '$job_idx'"
              . " AND b.compgroup_id = '$compgroup_id'"
              . " AND b.competency_class = '$competency_class'"
              . " ORDER BY b.competency_abbr";
         $result = $db->query($sql);
         if($db->getRowsNum($result)>0) {
            while(list($rcl,$itj,$competency_id,$competency_nm,$competency_abbr,$desc_en,$desc_id)=$db->fetchRow($result)) {
               if(!isset($job_ttl_rcl[$job_idx])) {
                  $job_ttl_rcl[$job_idx] = 0;
               }
               $hdr[$competency_id] = array($competency_abbr,$competency_nm,$competency_id,$desc_en,$desc_id);
               $job_rcl[$job_idx][$competency_id] = $rcl;
               $job_itj[$job_idx][$competency_id] = $itj;
               $job_ttl_rcl[$job_idx] += $rcl;
            }
         }
      }
      
      uasort($hdr,"sort_competency");
      //arsort($hdr);
      $ret = "<div style='position:absolute;top:285px;left:100px;border:1px solid #bbbbbb;background-color:#fff;padding:0px;font-size:0.8em;'>"
           . "<div style='font-weight:bold;text-align:center;background-color:#f0f0f0;'>Remarks</div>"
           . "<img src='".XOCP_SERVER_SUBDIR."/modules/hris/images/competency_matrix_legend.png'/>"
           . "</div>"
           . "<table style='border-spacing:0px;background-color:#fff;'><tbody>"
           . "<tr>"
           . "<td>&nbsp;</td>"
           . "<td style='padding:0px;background-color:#fff;'>";
      
      /// ul untuk compgroup
      $ret .= "<div style='border-bottom:1px solid black;margin-left:-1px;padding-left:10px;'><ul style='margin-bottom:0px;' class='ultab'>";
      $sql = "SELECT compgroup_nm,compgroup_id,competency_class_set FROM ".XOCP_PREFIX."compgroup ORDER BY compgroup_id";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($compgroup_nmx,$compgroup_idx,$cset)=$db->fetchRow($result)) {
            $ret .= "<li onclick='change_group(\"$compgroup_idx\");' ".($compgroup_idx==$compgroup_id?"class='ultabsel_greyrev'":"")."><span>$compgroup_nmx</span></li>";
         }
      }
      $ret .= "</ul><div style='clear:both;'></div></div>";
      
      /// ul untuk competency_class
      $ret .= "<div style='margin-left:-1px;margin-bottom:-1px;padding-left:15px;border-right:1px solid black;border-bottom:1px solid black;border-left:1px solid black;'><ul style='margin-bottom:0px;' class='ultab'>";
      foreach($xset as $class) {
         $ret .= "<li onclick='change_class(\"$class\");' ".($class==$competency_class?"class='ultabsel_greyrev'":"")."><span>$class</span></li>";
      }
      $ret .= "<li style='visibility:hidden;'><span>&nbsp;</span></li></ul><div style='clear:both;'></div></div>";
      
      /// space
      $ret .= "<div style='font-weight:bold;color:black;border-left:1px solid black;border-right:1px solid black;padding:10px;margin-left:-1px;border-bottom:1px solid black;margin-bottom:-1px;font-weight:bold;text-align:center;'>Competency</div>";
      
      $ret .= "</td></tr>";
            //////// left : job title
      $ret .= "<tr>"
            . "<td style='padding:0px;vertical-align:bottom;border-right:1px solid black;'>"
            . "<div style='text-align:center;'>"
            
            
            . "<table style='border-spacing:0px;'>"
            . "<tbody>"
            . "<tr><td style='padding:4px;border:1px solid black;text-align:center;font-weight:bold;background-color:#ddd;color:black;'>"
            . "<div style='width:190px !important;text-align:left;'>Job Title</div>"
            . "</td>"
            . "<td style='padding:4px;border:1px solid black;border-left:0px;text-align:center;font-weight:bold;background-color:#eee;color:black;'>"
            . "<div style='width:60px !important;text-align:left;'>Section</div>"
            . "</td>"
            . "<td style='padding:4px;border:1px solid black;border-left:0px;border-right:0px;text-align:center;font-weight:bold;background-color:#f7f7f7;color:black;'>"
            . "<div style='width:60px !important;text-align:center;'> - ". /* Total RCL */ "</div>"
            . "</td>"
            . "</tr>"
            . "</tbody></table>"
            
            
            
            . "</div>"
            . "</td>"
            
            
            
            
            ///////// right : competency title
            . "<td style='padding:0px;'>"
            . "<div id='dvcomptitle' style='width:590px !important;text-align:center;overflow:hidden;padding:0px;border-right:1px solid black;'>"
            
            
            
            
            
            
            . "<table style='border-spacing:0px;'><tbody>";
      
      /// tr untuk competency_abbr
      $ret .= "<tr>";
      $tooltips = "";
      $comp_no = 0;
      foreach($hdr as $competency_id=>$arc) {
         list($competency_abbr,$competency_nm,$competency_idx)=$arc;
         $tooltips .= "<div id='xcomphdr_${competency_idx}' style='min-width:100px;font-size:0.9em;opacity:1;position:absolute;z-index:1000;background-color:#ffffff;padding:0px;border:1px solid #333;visibility:hidden;-moz-border-radius:0 3px 3px 3px;'>"
                    . "<div style='background-color:#eee;padding:1px;-moz-border-radius: 0 3px 0 0;color:#888;font-weight:bold;text-align:center;'>$competency_abbr</div><div style='padding:8px;'>$competency_nm</div>"
                    . "</div>";
         $ret .= "<td colspan='2' style='cursor:default;padding:0px;border:1px solid black;border-left:0px;background-color:#ddd;font-weight:bold;color:black;text-align:center;'"
               . " onmousemove='hdr_competency_mousemove(\"$competency_idx\",this,event);' onmouseout='hdr_competency_mouseout(this,event);'>"
               . "<div style='width:${wcpx} !important;font-size:0.9em;'>$competency_abbr</div>"
               . "</td>";
         $comp_no++;
      }
      $ret .= "<td style='padding:0px;border-bottom:1px solid black;'><div style='width:700px;padding:6px;padding-bottom:4px;'>&nbsp;</div></td>";
      $ret .= "</tr>";
   
      //// tr untuk RCL dan ITJ
      $comp_no = 0;
      $ret .= "<tr>";
      foreach($hdr as $competency_id=>$arc) {
         $ret .= "<td onmousemove='t_rcl_mousemove(this,event);' onmouseout='t_rcl_mouseout(this,event);' style='cursor:default;background-color:#eee;color:black;font-weight:bold;padding:4px;text-align:center;border-right:1px solid black;border-bottom:1px solid black;'>"
               . "<div style='width:${wpx} !important;font-size:0.8em;'>RCL</div></td>";
         $ret .= "<td onmousemove='t_itj_mousemove(this,event);' onmouseout='t_itj_mouseout(this,event);' style='cursor:default;background-color:#eee;color:black;font-weight:bold;padding:4px;text-align:center;border-right:1px solid black;border-bottom:1px solid black;'>"
               . "<div style='width:${wpx} !important;font-size:0.8em;'>ITJ</div></td>";
         $comp_no++;
      }
      $ret .= "<td style='border-bottom:1px solid black;padding:2px;'><div style='width:700px;padding:2px;'>&nbsp;</div></td>";
      $ret .= "</tr>";
   
      $ret .= "</tbody></table>";
      
            
            
      $ret .= "</div>"
            . "</td>"
            . "</tr>";
      
      
      //// job title list here
      $ret .= "<tr>";
      $ret .= "<td style='vertical-align:top;text-align:left;padding:0px;'><div id='dvscrolljob' style='width:338px !important;overflow:hidden;max-height:400px;border-bottom:1px solid black;'>";
      
      $ret .= "<table style='border-spacing:0px;'><tbody>";
      
      $old_job_class = 0;
      $tooltip_job = $tooltip_org = "";
      foreach($jobs as $job_idx=>$job) {
         list($job_id,$job_nm,$job_abbr,$job_class_id,$job_class_nm,$org_nm,$org_class_nm,$org_abbr,$jlvl,$summary)=$job;
         if($old_job_class!=$job_class_id) {
            $ret .= "<tr><td colspan='3' style='font-weight:bold;color:black;padding:4px;border-left:1px solid white;border-right:1px solid white;border-bottom:1px solid black;'>"
                  . "<div style='overflow:hidden;width:190px;'><div style='width:900px;'>$job_class_nm</div></div>"
                  . "</td>";
            $ret .= "</tr>";
         }
         $old_job_class = $job_class_id;
         $job_ttl_rcl[$job_id] = "-";
         $ret .= "<tr><td onmousemove='tjob_mousemove(\"$job_id\",this,event);' onmouseout='tjob_mouseout(this,event);' style='background-color:#dddddd;padding:4px;border-left:1px solid black;border-right:1px solid black;border-bottom:1px solid black;cursor:default;'>"
               . "<div style='overflow:hidden;width:190px;'><div style='width:900px;'>$job_nm</div></div>"
               . "</td>"
               . "<td onmousemove='torg_mousemove(\"$job_id\",this,event);' onmouseout='torg_mouseout(this,event);' style='cursor:default;background-color:#eeeeee;padding:4px;border-right:1px solid black;border-bottom:1px solid black;'>"
               . "<div style='overflow:hidden;width:60px;'><div style='width:900px;'>$org_abbr</div></div>"
               . "</td>"
               . "<td style='background-color:#f7f7f7;padding:4px;border-right:1px solid black;border-bottom:1px solid black;' title='$org_nm $org_class_nm'>"
               . "<div style='overflow:hidden;width:60px;text-align:center;'><div style='margin-left:-420px;width:900px;'>".$job_ttl_rcl[$job_id]."</div></div>"
               . "</td>";
         $ret .= "</tr>";
         $tooltip_org .= "<div id='torg_${job_id}' style='-moz-box-shadow:1px 1px 3px #000;min-width:100px;font-size:0.9em;opacity:1;position:absolute;z-index:1000;background-color:#ffffff;padding:0px;border:1px solid #888;visibility:hidden;-moz-border-radius:5px;'>"
                       . "<div style='background-color:#eee;padding:4px;-moz-border-radius:5px 5px 0 0;color:#888;font-weight:bold;text-align:center;'>$org_abbr</div><div style='padding:8px;text-align:center;'>"
                       . "$org_nm $org_class_nm</div>"
                       . "</div>";
         $tooltip_job .= "<div id='tjob_${job_id}' style='-moz-box-shadow:1px 1px 3px #000;width:300px !important;font-size:0.9em;opacity:1;position:absolute;z-index:1000;background-color:#ffffff;padding:0px;border:1px solid #888;visibility:hidden;-moz-border-radius:5px;'>"
                       . "<div style='background-color:#eee;padding:4px;-moz-border-radius: 5px 5px 0 0;color:#888;font-weight:bold;text-align:center;'>$job_nm</div><div style='padding:8px;'>"
                       . "<div style='font-weight:bold;'>Job Summary:</div>$summary</div>"
                       . "</div>";
      }
      $ret .= "<tr><td colspan='2' style='font-weight:bold;color:black;padding:4px;border-left:1px solid black;border-right:1px solid white;border-bottom:1px solid white;'>"
            . "<div style='overflow:hidden;width:190px;'><div style='width:900px;height:35px;'>&nbsp;</div></div>"
            . "</td>";
      $ret .= "</tr>";
      
      $ret .= "</tbody></table>";
      
      
      $ret .= "</div></td>";
      
      
      //// rcl itj here
      $ret .= "<td style='vertical-align:top;padding:0px;'>";
      $ret .= "<div style='cursor:default;overflow:auto;width:590px !important;max-height:400px;border-bottom:1px solid black;;border-right:1px solid black;' onscroll='scrolldv(this,event);'>";
      
      $ret .= "<table  style='border-spacing:0px;'><tbody>";
      $old_job_class = 0;
      $tooltip_rcl = $tooltip_itj = "";
      foreach($jobs as $job_idx=>$job) {
         list($job_id,$job_nm,$job_abbr,$job_class_id,$job_class_nm,$org_nm,$org_class_nm,$org_abbr)=$job;
         
         if($old_job_class!=$job_class_id) {
            $ret .= "<tr>";
            foreach($hdr as $competency_id=>$arc) {
               $ret .= "<td style='padding:4px;text-align:center;border-right:1px solid white;border-bottom:1px solid #bbb;'>&nbsp;</td>";
               $ret .= "<td style='padding:4px;text-align:center;border-right:1px solid white;border-bottom:1px solid #bbb;'>&nbsp;</td>";
            }
            $ret .= "<td style='border-bottom:1px solid #bbb;padding:2px;'><div style='width:700px;padding:2px;'>&nbsp;</div></td>";
            $ret .= "</tr>";
         }
         $old_job_class = $job_class_id;
         
         
         $ret .= "<tr>";
         foreach($hdr as $competency_id=>$arc) {
            if(isset($job_rcl[$job_idx][$competency_id])) {
               list($competency_abbr,$competency_nm,$competency_idx,$desc_en,$desc_id)=$hdr[$competency_id];
               $rcl = $job_rcl[$job_idx][$competency_id];
               $itj = $job_itj[$job_idx][$competency_id];
               list($job_idxxx,$job_nm)=$jobs[$job_idx];
               $tooltip_rcl .= "<div id='xval_rcl_${job_idx}_${competency_id}' style='-moz-box-shadow:1px 1px 3px #000;min-width:100px;font-size:0.9em;opacity:1;position:absolute;z-index:1000;background-color:#ffffff;padding:0px;border:1px solid #bbb;visibility:hidden;-moz-border-radius:5px;'>"
                             //. "<div style='background-color:#eee;padding:1px;-moz-border-radius: 0 3px 0 0;color:#888;font-weight:bold;text-align:center;'>Required Competency Level</div>"
                             . "<div style='text-align:center;padding:8px;'><div style='color:black;font-weight:bold;'>$job_nm</div><div>Require</div>"
                             . "<div style='font-weight:bold;color:black;'>$competency_nm</div>"
                             . "<table align='center' style='border-spacing:0px;padding:0px;font-weight:bold;color:#3388dd;'><tbody>"
                             . "<tr><td style='text-align:left;'>RCL</td><td>:</td><td>$rcl</td></tr>"
                             . "<tr><td style='text-align:left;'>ITJ</td><td>:</td><td>$itj</td></tr>"
                             . "</tbody></table>"
                             . "</div>"
                             . "</div>";
               $ret .= "<td onmousemove='tval_rcl_mousemove(\"$job_idx\",\"$competency_id\",this,event);' onmouseout='tval_rcl_mouseout(this,event);' id='tiprcl_${job_idx}_${competency_id}' style='padding:4px;text-align:center;border-right:1px solid #eee;border-bottom:1px solid #bbb;'>"
                     . "<div style='width:${wpx} !important;padding:0px;'>$rcl</div></td>";
               $ret .= "<td onmousemove='tval_rcl_mousemove(\"$job_idx\",\"$competency_id\",this,event);' onmouseout='tval_rcl_mouseout(this,event);' id='tipitj_${job_idx}_${competency_id}' style='padding:4px;text-align:center;border-right:1px solid #777;border-bottom:1px solid #bbb;".($itj>=3?"background-color:#ffdddd;":"background-color:#ddffff;")."'>"
                     . "<div style='width:${wpx} !important;padding:0px;'>$itj</div></td>";
            } else {
               $ret .= "<td style='padding:4px;text-align:center;border-right:1px solid #eee;border-bottom:1px solid #bbb;'>"
                     . "<div style='width:${wpx} !important;padding:0px;'>&nbsp;</div></td>";
               $ret .= "<td style='padding:4px;text-align:center;border-right:1px solid #777;border-bottom:1px solid #bbb;background-color:#ddffff;'>"
                     . "<div style='width:${wpx} !important;padding:0px;'>&nbsp;</div></td>";
            }
         }
         $ret .= "<td style='border-bottom:1px solid #bbb;padding:2px;'><div style='width:700px;padding:2px;'>&nbsp;</div></td>";
         $ret .= "</tr>";
      }
      
      $ret .= "<tr>";
      foreach($hdr as $competency_id=>$arc) {
         $ret .= "<td style='padding:4px;text-align:center;border-right:1px solid white;border-bottom:1px solid #bbb;'>&nbsp;</td>";
         $ret .= "<td style='padding:4px;text-align:center;border-right:1px solid white;border-bottom:1px solid #bbb;'>&nbsp;</td>";
      }
      $ret .= "<td style='border-bottom:1px solid #bbb;padding:2px;'><div style='width:700px;padding:2px;height:20px;'>&nbsp;</div></td>";
      $ret .= "</tr>";
      
      $ret .= "</tbody></table>";
      $ret .= "</div>";
      $ret .= "</td></tr>";
      
      $ret .= "</tbody></table>".$tooltips.$tooltip_rcl.$tooltip_job.$tooltip_org;
      
      return array(array($tips0,$tips1),$ret);
      
   }
   
   function app_setPosition($args) {
      $db=&Database::getInstance();
      $_SESSION["hris_posmatrix_division"] = $args[0];
      $_SESSION["hris_posmatrix_subdivision"] = $args[1];
      $_SESSION["hris_posmatrix_poslevel"] = $args[2];
      
      _dumpVar($args);
      
      $_SESSION["hris_subdiv"] = array();
      $this->recurseDivision($_SESSION["hris_posmatrix_division"]);
      $subdiv_id = $args[1];
      
      ksort($_SESSION["hris_subdiv"]);
      
      if(!isset($_SESSION["hris_subdiv"][$subdiv_id])) {
         $optsubdiv = "<option value='0'>All</option>";
         foreach($_SESSION["hris_subdiv"] as $org_class_idx=>$orgs) {
            foreach($orgs as $org_idx=>$v) {
               list($org_id,$org_nm,$org_abbr,$org_class_nm)=$v;
               $optsubdiv .= "<option value='$org_id' ".($org_id==$_SESSION["hris_posmatrix_subdivision"]?"selected='1'":"").">$org_nm $org_class_nm</option>";
            }
         }
      } else {
         $optsubdiv = "NOCHANGE";
      }
      
      $this->getAllJobs();
      
      $poslevel_id = $args[2];
      $optlevel = "<option value='0'>All</option>";
      foreach($_SESSION["hris_poslevel"] as $level) {
         list($job_class_id,$job_class_nm)=$level;
         $optlevel .= "<option value='$job_class_id' ".($poslevel_id==$job_class_id?"selected='1'":"").">$job_class_nm</option>";
      }
      
      return array($optsubdiv,$optlevel);
   }
   
   function recurseDivision($parent_id) {
      $db=&Database::getInstance();
      $sql = "SELECT a.org_id,a.org_nm,a.org_abbr,b.org_class_nm,a.org_class_id"
           . " FROM ".XOCP_PREFIX."orgs a"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . " WHERE a.parent_id = '$parent_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($org_id,$org_nm,$org_abbr,$org_class_nm,$org_class_id)=$db->fetchRow($result)) {
            $_SESSION["hris_subdiv"][$org_class_id][$org_id] = array($org_id,$org_nm,$org_abbr,$org_class_nm);
            $this->recurseDivision($org_id);
         }
      }
   }
   
   function getAllJobs() {
      $db=&Database::getInstance();
      $division_id = $_SESSION["hris_posmatrix_division"];
      $subdiv_id = $_SESSION["hris_posmatrix_subdivision"];
      
      $_SESSION["hris_subdiv"] = array();
      $this->recurseDivision($_SESSION["hris_posmatrix_division"]);
      
      ksort($_SESSION["hris_subdiv"]);
      
      $_SESSION["hris_poslevel"] = array();
      $_SESSION["hris_jobs"] = array();
      
      
      /// subdivision jobs
      if($subdiv_id>0) {
         $sql = "SELECT a.job_id,a.job_nm,a.job_abbr,a.job_class_id,b.job_class_nm,c.org_nm,d.org_class_nm,c.org_abbr,b.job_class_level,a.summary"
              . " FROM ".XOCP_PREFIX."jobs a"
              . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
              . " LEFT JOIN ".XOCP_PREFIX."orgs c ON c.org_id = a.org_id"
              . " LEFT JOIN ".XOCP_PREFIX."org_class d USING(org_class_id)"
              . " WHERE a.org_id = '$subdiv_id'";
         $result = $db->query($sql);
         
         if($db->getRowsNum($result)>0) {
            while(list($job_id,$job_nm,$job_abbr,$job_class_id,$job_class_nm,$org_nm,$org_class_nm,$org_abbr,$job_class_level,$summary)=$db->fetchRow($result)) {
               $_SESSION["hris_poslevel"][$job_class_id] = array($job_class_id,$job_class_nm,$job_class_level);
               $_SESSION["hris_jobs"][$job_class_id][$job_id] = array($job_id,$job_nm,$job_abbr,$job_class_id,$job_class_nm,$org_nm,$org_class_nm,$org_abbr,$job_class_level,$summary);
            }
         }
      } else {
         
         /// division jobs
         $sql = "SELECT a.job_id,a.job_nm,a.job_abbr,a.job_class_id,b.job_class_nm,c.org_nm,d.org_class_nm,c.org_abbr,b.job_class_level,a.summary"
              . " FROM ".XOCP_PREFIX."jobs a"
              . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
              . " LEFT JOIN ".XOCP_PREFIX."orgs c ON c.org_id = a.org_id"
              . " LEFT JOIN ".XOCP_PREFIX."org_class d USING(org_class_id)"
              . " WHERE a.org_id = '$division_id'";
         $result = $db->query($sql);
         if($db->getRowsNum($result)>0) {
            while(list($job_id,$job_nm,$job_abbr,$job_class_id,$job_class_nm,$org_nm,$org_class_nm,$org_abbr,$job_class_level,$summary)=$db->fetchRow($result)) {
               $_SESSION["hris_poslevel"][$job_class_id] = array($job_class_id,$job_class_nm,$job_class_level);
               $_SESSION["hris_jobs"][$job_class_id][$job_id] = array($job_id,$job_nm,$job_abbr,$job_class_id,$job_class_nm,$org_nm,$org_class_nm,$org_abbr,$job_class_level,$summary);
            }
         }
         
         
         foreach($_SESSION["hris_subdiv"] as $org_class_idx=>$orgs) {
            foreach($orgs as $org_idx=>$v) {
               $sql = "SELECT a.job_id,a.job_nm,a.job_abbr,a.job_class_id,b.job_class_nm,c.org_nm,d.org_class_nm,c.org_abbr,b.job_class_level,a.summary"
                    . " FROM ".XOCP_PREFIX."jobs a"
                    . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
                    . " LEFT JOIN ".XOCP_PREFIX."orgs c ON c.org_id = a.org_id"
                    . " LEFT JOIN ".XOCP_PREFIX."org_class d USING(org_class_id)"
                    . " WHERE a.org_id = '$org_idx'";
               $result = $db->query($sql);
               if($db->getRowsNum($result)>0) {
                  while(list($job_id,$job_nm,$job_abbr,$job_class_id,$job_class_nm,$org_nm,$org_class_nm,$org_abbr,$job_class_level,$summary)=$db->fetchRow($result)) {
                     $_SESSION["hris_poslevel"][$job_class_id] = array($job_class_id,$job_class_nm,$job_class_level);
                     $_SESSION["hris_jobs"][$job_class_id][$job_id] = array($job_id,$job_nm,$job_abbr,$job_class_id,$job_class_nm,$org_nm,$org_class_nm,$org_abbr,$job_class_level,$summary);
                  }
               }
            }
         }
      }
      
   }
   
   
}

} /// HRIS_POSTMATRIXAJAX_DEFINED
?>