<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/compiled.php                               //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2012-03-22                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_HRCOMPILEDREPORT_DEFINED') ) {
   define('HRIS_HRCOMPILEDREPORT_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
include_once(XOCP_DOC_ROOT."/modules/hris/class/selectasid.php");
include_once(XOCP_DOC_ROOT."/modules/pms/class/selectpms.php");

function sort_job($job_class_id_a,$job_class_id_b) {
   
   list($job_class_idx,$job_class_nmx,$job_class_level_a) = $_SESSION["hris_poslevel"][$job_class_id_a];
   list($job_class_idx,$job_class_nmx,$job_class_level_b) = $_SESSION["hris_poslevel"][$job_class_id_b];
   
   if($job_class_level_a==$job_class_level_b) {
      return 0;
   }
   
   return ($job_class_level_a < $job_class_level_b) ? -1 : 1;
}


class _hris_CompiledReport extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_ASSESSMENT_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_COMPILEDREPORT_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function __construct($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function listSession() {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/hris/include/assessment.php");
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_compiled.php");
      $ajax = new _hris_class_CompiledReportAjax("arc");
      
      $asid = $_SESSION["hris_assessment_asid"];
      $psid = $_SESSION["pms_psid"];
      
      $arr_nip = array('9408572'=>1,'9303584'=>1,'9212588'=>1,'10209052'=>1,'10803150'=>1,'9503597'=>1,'11006262'=>1,'9306623'=>1,'9503602'=>1,'9503598'=>1,'9304589'=>1,'9503614'=>1,'9503594'=>1,'9506629'=>1,'9506627'=>1,'9506631'=>1,'9506630'=>1,'9503612'=>1,'9503615'=>1,'9309295'=>1,'10912237'=>1,'9503601'=>1,'9503592'=>1,'9503608'=>1,'9503596'=>1,'9507449'=>1,'9503607'=>1,'10312070'=>1,'10907219'=>1,'10907218'=>1,'9812039'=>1,'10312069'=>1,'9503604'=>1,'9503611'=>1,'9503600'=>1,'9412583'=>1,'9503616'=>1,'10905202'=>1,'9803667'=>1,'9408373'=>1,'10705136'=>1);

      $js = $ajax->getJs()."<script type='text/javascript'><!--
      // --></script>";
      
      
      $_SESSION["hris_poslevel"] = array();
      $_SESSION["hris_jobs"] = array();
      
      $this->getAllJobs();
      
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
      
      $old_job_class = 0;
      $tooltip_job = $tooltip_org = $tooltip_emp = "";
      $arr_emp = array();
      $tbl = "<table class='xxlist'><thead><tr>"
           . "<td style='text-align:center;'>NIP</td>"
           . "<td style='text-align:center;'>Employee</td>"
           . "<td style='text-align:center;'>Job</td>"
           . "<td style='text-align:center;'>Org</td>"
           . "<td style='text-align:center;'>JM 2011<br/>Original</td>"
           . "<td style='text-align:center;'>JM 2011</td>"
           . "<td style='text-align:center;'>JM 2012</td>"
           . "<td style='text-align:center;'>PMS 2011</td>"
           . "</tr></thead><tbody>";
      $job_class_nm_old = "";
      foreach($jobs as $job_idx=>$job) {
         list($job_id,$job_nm,$job_abbr,$job_class_id,$job_class_nm,$org_nm,$org_class_nm,$org_abbr,$jlvl,$summary)=$job;
         
         
         $sql = "SELECT a.employee_id,b.employee_ext_id,c.person_nm"
              . " FROM ".XOCP_PREFIX."assessment_session_job a"
              . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
              . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
              . " WHERE a.job_id = '$job_id'"
              . " AND a.asid = '$asid'"
              . " ORDER BY c.person_nm";
         $remp = $db->query($sql);
         
         if($db->getRowsNum($remp)>0) {
            if($job_class_nm_old!=$job_class_nm) {
               $tbl .= "<tr><td colspan='8' style='font-weight:bold;text-align:left;background-color:#ddd;'>$job_class_nm</td></tr>";
               $job_class_nm_old = $job_class_nm;
            }
            while(list($employee_id,$nip,$employee_nm)=$db->fetchRow($remp)) {
               
               ///if(!isset($arr_nip[$nip])) continue;
               
               $sql = "SELECT c.job_nm,c.job_abbr,d.org_nm,d.org_abbr,a.employee_ext_id,e.person_nm,e.person_id"
                    . " FROM ".XOCP_PREFIX."employee a"
                    . " LEFT JOIN ".XOCP_PREFIX."assessment_session_job b ON b.employee_id = a.employee_id AND b.job_id = '$job_id'"
                    . " LEFT JOIN ".XOCP_PREFIX."jobs c ON c.job_id = '$job_id'"
                    . " LEFT JOIN ".XOCP_PREFIX."orgs d ON d.org_id = c.org_id"
                    . " LEFT JOIN ".XOCP_PREFIX."persons e ON e.person_id = a.person_id"
                    . " WHERE a.employee_id = '$employee_id'";
               $res_emp = $db->query($sql);
               list($job_nm,$job_abbr,$org_nm,$org_abbr,$nip,$employee_nm,$person_id)=$db->fetchRow($res_emp);
               
               
               ////////////// calculate job match here ///////////////////////////////////////////////////////////////////////////////////
               /// query from final recap 2011
               $sql = "SELECT jm,cf,jmxxx,cfxxx FROM ".XOCP_PREFIX."employee_competency_final_recap"
                    . " WHERE asid = '8'"
                    . " AND employee_id = '$employee_id'"
                    . " AND job_id = '$job_id'";
               $rc = $db->query($sql);
               if($employee_id==441) _debuglog($sql);
               if($db->getRowsNum($rc)>0) {
                  list($old_match,$old_cf,$match,$cf)=$db->fetchRow($rc);
               } else {
                  $old_match = $old_cf = 0;
                  $match = 0;
                  $cf = 0;
               }
               
               $tbl .= "<tr><td>$nip</td><td>$employee_nm</td><td>$job_abbr</td><td>$org_abbr</td>"
                     . "<td style='text-align:center;'>".toMoney($old_match)."</td>"
                     . "<td style='text-align:center;'>".toMoney($match)."</td>";
               
               $sql = "SELECT jm,cf,jmxxx,cfxxx FROM ".XOCP_PREFIX."employee_competency_final_recap"
                    . " WHERE asid = '10'"
                    . " AND employee_id = '$employee_id'"
                    . " AND job_id = '$job_id'";
               $rc = $db->query($sql);
               if($db->getRowsNum($rc)>0) {
                  list($old_match,$old_cf,$match,$cf)=$db->fetchRow($rc);
               } else {
                  $old_match = $old_cf = 0;
                  $match = 0;
                  $cf = 0;
               }
               
               $tbl .= "<td style='text-align:center;'>".toMoney($match)."</td>";
               
               
               
               $sql = "SELECT a.reported_final_result"
                    . " FROM pms_jam a"
                    . " LEFT JOIN pms_objective b USING(psid,pms_objective_id)"
                    . " LEFT JOIN pms_perspective c USING(psid,pms_perspective_id)"
                    . " WHERE a.psid = '$psid' AND a.employee_id = '$employee_id'"
                    . " AND b.pms_objective_id IS NOT NULL"
                    . " AND a.jam_org_ind = '0'"
                    . " ORDER BY b.pms_perspective_id,b.pms_objective_no";
               $result = $db->query($sql);
               $ttl_achievement = 0;
               if($db->getRowsNum($result)>0) {
                  while(list($reported_final_result)=$db->fetchRow($result)) {
                     $ttl_achievement = _bctrim(bcadd($ttl_achievement,$reported_final_result));
                  }
               }
               
               $tbl .= "<td style='text-align:center;'>".toMoney($ttl_achievement)."</td></tr>";
               
            }
         }
      }
      
      $tbl .= "</tbody></table>";
      
      $ret = "<div style='text-align:left;'>"
           . $tbl
           . "</div>"
           . "<div id='progress' style='padding:10px;text-align:center;'></div>";
      return $js.$ret;
   }
   
   function getAllJobs() {
      $db=&Database::getInstance();
      $sql = "SELECT a.job_id,a.job_nm,a.job_abbr,a.job_class_id,b.job_class_nm,c.org_nm,d.org_class_nm,c.org_abbr,b.job_class_level,a.summary"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
           . " LEFT JOIN ".XOCP_PREFIX."orgs c ON c.org_id = a.org_id"
           . " LEFT JOIN ".XOCP_PREFIX."org_class d USING(org_class_id)"
           . " WHERE a.status_cd = 'normal'";
      $result = $db->query($sql);
      
      if($db->getRowsNum($result)>0) {
         while(list($job_id,$job_nm,$job_abbr,$job_class_id,$job_class_nm,$org_nm,$org_class_nm,$org_abbr,$job_class_level,$summary)=$db->fetchRow($result)) {
            if($job_class_level<=15) continue;
            $_SESSION["hris_poslevel"][$job_class_id] = array($job_class_id,$job_class_nm,$job_class_level);
            $_SESSION["hris_jobs"][$job_class_id][$job_id] = array($job_id,$job_nm,$job_abbr,$job_class_id,$job_class_nm,$org_nm,$org_class_nm,$org_abbr,$job_class_level,$summary);
         }
      }
   }
   
   
   function main() {
      $db = &Database::getInstance();
      
      $asidselobj = new _hris_class_SelectAssessmentSession();
      $asidsel = "<div style='padding-bottom:2px;'>".$asidselobj->show()."</div>";
      
      $pmsselobj = new _pms_class_SelectSession();
      $pmssel = "<div style='padding-bottom:2px;'>".$pmsselobj->show()."</div>";
      
      if(!isset($_SESSION["hris_assessment_asid"])||$_SESSION["hris_assessment_asid"]==0) {
         return $asidsel;
      }
      
      if(!isset($_SESSION["pms_psid"])||$_SESSION["pms_psid"]==0) {
         return $asidsel.$pmssel;
      }
      
      switch ($this->catch) {
         case $this->blockID:
            $ret = $this->listSession();
            break;
         default:
            $ret = $this->listSession();
            break;
      }
      return "<div style='width:900px;'>".$asidsel.$pmssel.$ret."</div>";
      
   }
}

} // HRIS_HRCOMPILEDREPORT_DEFINED
?>