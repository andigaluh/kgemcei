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
      
      $tips0 = array();
      $tips1 = array();
      _dumpvar($args);
      
      $ret = "";
      
      if(!isset($_SESSION["hris_posmatrix_compgroup"])) {
         $_SESSION["hris_posmatrix_compgroup"] = 1;
      }
      $compgroup_id = $_SESSION["hris_posmatrix_compgroup"];
      
      $xset = array();
      $sql = "SELECT competency_class_set FROM ".XOCP_PREFIX."compgroup WHERE compgroup_id = '$compgroup_id'";
      _debuglog($sql);
      $result = $db->query($sql);
      list($class_set)=$db->fetchRow($result);
      $xset = explode(",",$class_set);
      _dumpvar($xset);
      _debuglog($_SESSION["hris_posmatrix_compclass"][$compgroup_id]);
      if(!isset($_SESSION["hris_posmatrix_compclass"][$compgroup_id])) {
         $_SESSION["hris_posmatrix_compclass"][$compgroup_id] = $xset[0];
      }
      $competency_class = $_SESSION["hris_posmatrix_compclass"][$compgroup_id];
      
      $jobs = array();
      if(is_array($_SESSION["hris_jobs"])) {
         ksort($_SESSION["hris_jobs"]);
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
      $job_rcl = array();
      $job_itj = array();
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
               $hdr[$competency_id] = array($competency_abbr,$competency_nm,$competency_id,$desc_en,$desc_id);
               $job_rcl[$job_idx][$competency_id] = $rcl;
               $job_itj[$job_idx][$competency_id] = $itj;
            }
         }
      }
      
      arsort($hdr);
      $ret = "<table style='border-spacing:0px;background-color:#fff;'><tbody><tr><td colspan='2'>&nbsp;</td><td style='border-bottom:0px solid black;padding:0px;background-color:#fff;' colspan='".(count($hdr))."'>";
      
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
      
      $ret .= "</td></tr><tr><td style='text-align:center;font-weight:bold;border-bottom:1px solid black;border-left:1px solid white;border-right:1px solid white;border-top:1px solid white;'>&nbsp;</td>"
            . "<td style='padding:6px;padding-bottom:5px;text-align:center;font-weight:bold;border-bottom:1px solid black;border-right:1px solid black;border-top:1px solid white;'>&nbsp;</td>"
            . "<td colspan='".(count($hdr))."' rowspan='".(count($jobs)+4+count($_SESSION["hris_poslevel"]))."' style='padding:0px;border-right:1px solid black;border-bottom:1px solid black;' valign='top'>";
      
      
      /// table start
      $ret .= "<div style='cursor:default;overflow:auto;width:600px !important;' onscroll='testscroll(this,event)'>";
      
      $ret .= "<table style='border-spacing:0px;'><colgroup>";
      foreach($hdr as $competency_id=>$arc) {
         $ret .= "<col width='35'/><col width='35'/>";
      }
      $ret .= "<col/></colgroup><tbody><tr>";
      
      foreach($hdr as $competency_id=>$arc) {
         list($competency_abbr,$competency_nm,$competency_idx)=$arc;
         $ret .= "<td colspan='2' style='border-top:1px solid black;background-color:#ddd;font-weight:bold;color:black;border-bottom:1px solid black;text-align:center;border-right:1px solid black;' title='$competency_nm'>"
               . "<div style='min-width:70px !important;'>$competency_abbr</div>"
               . "</td>";
      }
      $ret .= "<td style='border-bottom:1px solid black;'><div style='width:700px;padding:6px;padding-bottom:4px;'>&nbsp;</div></td>";
      $ret .= "</tr>";
      
      
         $ret .= "<tr>";
         foreach($hdr as $competency_id=>$arc) {
            $ret .= "<td title='Required Competency Level' style='background-color:#eee;color:black;font-weight:bold;padding:0px;text-align:center;border-right:1px solid black;border-bottom:1px solid black;'>RCL</td>";
            $ret .= "<td title='Importance to Job' style='background-color:#eee;color:black;font-weight:bold;padding:0px;text-align:center;border-right:1px solid black;border-bottom:1px solid black;'>ITJ</td>";
         }
         $ret .= "<td style='border-bottom:1px solid black;padding:2px;'><div style='width:700px;padding:2px;'>&nbsp;</div></td>";
         $ret .= "</tr>";
      
      
      $old_job_class = 0;
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
               $ret .= "<td title='$competency_nm : RCL = $rcl' id='tiprcl_${job_idx}_${competency_id}' style='padding:4px;text-align:center;border-right:1px solid #bbb;border-bottom:1px solid #bbb;'>$rcl</td>";
               $ret .= "<td title='$competency_nm : ITJ = $itj' id='tipitj_${job_idx}_${competency_id}' style='padding:4px;text-align:center;border-right:1px solid #777;border-bottom:1px solid #bbb;".($itj>=3?"background-color:#ffdddd;":"background-color:#ddffff;")."'>$itj</td>";
               $tips0[] = array($job_id,$competency_id,$rcl,$itj,$competency_nm,$competency_abbr);
            } else {
               $ret .= "<td colspan='2' style='padding:4px;text-align:center;border-right:1px solid #bbb;border-bottom:1px solid #bbb;'>&nbsp;</td>";
            }
         }
         $ret .= "<td style='border-bottom:1px solid #bbb;padding:2px;'><div style='width:700px;padding:2px;'>&nbsp;</div></td>";
         $ret .= "</tr>";
      }
      $ret .= "</tbody></table>";
      
      $ret .= "</div>";
      //// table end;
      
      
      $ret .= "<tr><td style='padding:4px;border-left:1px solid black;border-right:1px solid black;border-bottom:1px solid black;text-align:center;font-weight:bold;background-color:#ddd;color:black;'>Job Title</td>"
            . "<td style='padding:4px;border-right:1px solid black;border-bottom:1px solid black;text-align:center;font-weight:bold;background-color:#eee;color:black;'>Section</td>";
      $ret .= "</tr>";
      
      
      $old_job_class = 0;
      foreach($jobs as $job_idx=>$job) {
         list($job_id,$job_nm,$job_abbr,$job_class_id,$job_class_nm,$org_nm,$org_class_nm,$org_abbr)=$job;
         if($old_job_class!=$job_class_id) {
            $ret .= "<tr><td style='font-weight:bold;color:black;padding:4px;border-left:1px solid white;border-right:1px solid white;border-bottom:1px solid black;'>"
                  . "<div style='overflow:hidden;width:250px;'><div style='width:900px;'>$job_class_nm</div></div>"
                  . "</td>"
                  . "<td style='padding:4px;border-right:1px solid white;border-bottom:1px solid black;'>&nbsp;</td>";
            $ret .= "</tr>";
         }
         $old_job_class = $job_class_id;
         $ret .= "<tr><td style='background-color:#dddddd;padding:4px;border-left:1px solid black;border-right:1px solid black;border-bottom:1px solid black;' title='$job_nm'>"
               . "<div style='overflow:hidden;width:250px;'><div style='width:900px;'>$job_nm</div></div>"
               . "</td>"
               . "<td style='background-color:#eeeeee;padding:4px;border-right:1px solid black;border-bottom:1px solid black;' title='$org_nm $org_class_nm'>$org_abbr</td>";
         $ret .= "</tr>";
      }
      
      $ret .= "<tr><td style='border-left:1px solid black;border-bottom:1px solid black;padding:10px;'>&nbsp;</td><td style='border-bottom:1px solid black;'>&nbsp;</td></tr>";
      
      
      
      
      
      $ret .= "</tbody></table>";
      
      _dumpvar($tips0);
      
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
         $sql = "SELECT a.job_id,a.job_nm,a.job_abbr,a.job_class_id,b.job_class_nm,c.org_nm,d.org_class_nm,c.org_abbr"
              . " FROM ".XOCP_PREFIX."jobs a"
              . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
              . " LEFT JOIN ".XOCP_PREFIX."orgs c ON c.org_id = a.org_id"
              . " LEFT JOIN ".XOCP_PREFIX."org_class d USING(org_class_id)"
              . " WHERE a.org_id = '$subdiv_id'";
         $result = $db->query($sql);
         
         if($db->getRowsNum($result)>0) {
            while(list($job_id,$job_nm,$job_abbr,$job_class_id,$job_class_nm,$org_nm,$org_class_nm,$org_abbr)=$db->fetchRow($result)) {
               $_SESSION["hris_poslevel"][$job_class_id] = array($job_class_id,$job_class_nm);
               $_SESSION["hris_jobs"][$job_class_id][$job_id] = array($job_id,$job_nm,$job_abbr,$job_class_id,$job_class_nm,$org_nm,$org_class_nm,$org_abbr);
            }
         }
      } else {
         
         /// division jobs
         $sql = "SELECT a.job_id,a.job_nm,a.job_abbr,a.job_class_id,b.job_class_nm,c.org_nm,d.org_class_nm,c.org_abbr"
              . " FROM ".XOCP_PREFIX."jobs a"
              . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
              . " LEFT JOIN ".XOCP_PREFIX."orgs c ON c.org_id = a.org_id"
              . " LEFT JOIN ".XOCP_PREFIX."org_class d USING(org_class_id)"
              . " WHERE a.org_id = '$division_id'";
         $result = $db->query($sql);
         if($db->getRowsNum($result)>0) {
            while(list($job_id,$job_nm,$job_abbr,$job_class_id,$job_class_nm,$org_nm,$org_class_nm,$org_abbr)=$db->fetchRow($result)) {
               $_SESSION["hris_poslevel"][$job_class_id] = array($job_class_id,$job_class_nm);
               $_SESSION["hris_jobs"][$job_class_id][$job_id] = array($job_id,$job_nm,$job_abbr,$job_class_id,$job_class_nm,$org_nm,$org_class_nm,$org_abbr);
            }
         }
         
         
         foreach($_SESSION["hris_subdiv"] as $org_class_idx=>$orgs) {
            foreach($orgs as $org_idx=>$v) {
               $sql = "SELECT a.job_id,a.job_nm,a.job_abbr,a.job_class_id,b.job_class_nm,c.org_nm,d.org_class_nm,c.org_abbr"
                    . " FROM ".XOCP_PREFIX."jobs a"
                    . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
                    . " LEFT JOIN ".XOCP_PREFIX."orgs c ON c.org_id = a.org_id"
                    . " LEFT JOIN ".XOCP_PREFIX."org_class d USING(org_class_id)"
                    . " WHERE a.org_id = '$org_idx'";
               $result = $db->query($sql);
               if($db->getRowsNum($result)>0) {
                  while(list($job_id,$job_nm,$job_abbr,$job_class_id,$job_class_nm,$org_nm,$org_class_nm,$org_abbr)=$db->fetchRow($result)) {
                     $_SESSION["hris_poslevel"][$job_class_id] = array($job_class_id,$job_class_nm);
                     $_SESSION["hris_jobs"][$job_class_id][$job_id] = array($job_id,$job_nm,$job_abbr,$job_class_id,$job_class_nm,$org_nm,$org_class_nm,$org_abbr);
                  }
               }
            }
         }
      }
      
   }
   
   
}

} /// HRIS_POSTMATRIXAJAX_DEFINED
?>