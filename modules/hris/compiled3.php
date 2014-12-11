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
   var $title = "Recalculation Debug";
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
         
         function view_detail(employee_id,job_id,d,e) {
            if(!d.tr) {
               d.tr = _dce('tr');
               d.td = d.tr.appendChild(_dce('td'));
               d.td.setAttribute('colspan','12');
               d.tr = d.parentNode.parentNode.parentNode.insertBefore(d.tr,d.parentNode.parentNode.nextSibling);
               d.td.setAttribute('id','tdemployee_'+employee_id+'_'+job_id);
               d.td.setAttribute('style','padding:10px;background-color:#ffffcc;');
               d.td.d = d;
            } else {
               _destroy(d.tr);
               d.td = null;
               d.tr = null;
               return;
            }
            arc_app_viewDetail(employee_id,job_id,function(_data) {
               var data = recjsarray(_data);
               var td = $('tdemployee_'+data[0]+'_'+data[1]);
               td.innerHTML = data[2];
            });
         }
         
         
      // --></script>";
      
      
      $_SESSION["db0"] = "hris201203210941";
      $_SESSION["db1"] = "hris";
      
      $db0 = $_SESSION["db0"];
      $db1 = $_SESSION["db1"];
      
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
      $cnt = 0;
      $tbl = "<table class='xxlist'><thead><tr>"
           . "<td style='text-align:center;'>No</td>"
           . "<td style='text-align:center;'>NIP</td>"
           . "<td style='text-align:center;'>Employee</td>"
           . "<td style='text-align:center;'>Job</td>"
           . "<td style='text-align:center;'>Org</td>"
           . "<td style='text-align:center;'>JM0</td>"
           . "<td style='text-align:center;'>JM1</td>"
           . "<td style='text-align:center;'>Update0</td>"
           . "<td style='text-align:center;'>Update1</td>"
           . "<td style='text-align:center;'>CF0</td>"
           . "<td style='text-align:center;'>CF1</td>"
           . "<td style='text-align:center;'>RCL0</td>"
           . "<td style='text-align:center;'>RCL1</td>"
           . "<td style='text-align:center;'>DIFF</td>"
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
            while(list($employee_id,$nip,$employee_nm)=$db->fetchRow($remp)) {
               
               ///if(!isset($arr_nip[$nip])) continue;
               
               $sql = "SELECT c.job_nm,c.job_abbr,d.org_nm,d.org_abbr,a.employee_ext_id,e.person_nm,e.person_id"
                    . " FROM ".XOCP_PREFIX."employee a"
                    . " LEFT JOIN ${db1}.".XOCP_PREFIX."assessment_session_job b ON b.employee_id = a.employee_id AND b.job_id = '$job_id'"
                    . " LEFT JOIN ${db1}.".XOCP_PREFIX."jobs c ON c.job_id = '$job_id'"
                    . " LEFT JOIN ${db1}.".XOCP_PREFIX."orgs d ON d.org_id = c.org_id"
                    . " LEFT JOIN ${db1}.".XOCP_PREFIX."persons e ON e.person_id = a.person_id"
                    . " WHERE a.employee_id = '$employee_id'";
               $res_emp = $db->query($sql);
               list($job_nm,$job_abbr,$org_nm,$org_abbr,$nip,$employee_nm,$person_id)=$db->fetchRow($res_emp);
               
               $sql = "SELECT c.job_nm,c.job_abbr,d.org_nm,d.org_abbr,a.employee_ext_id,e.person_nm,e.person_id"
                    . " FROM ".XOCP_PREFIX."employee a"
                    . " LEFT JOIN ${db0}.".XOCP_PREFIX."assessment_session_job b ON b.employee_id = a.employee_id AND b.job_id = '$job_id'"
                    . " LEFT JOIN ${db0}.".XOCP_PREFIX."jobs c ON c.job_id = '$job_id'"
                    . " LEFT JOIN ${db0}.".XOCP_PREFIX."orgs d ON d.org_id = c.org_id"
                    . " LEFT JOIN ${db0}.".XOCP_PREFIX."persons e ON e.person_id = a.person_id"
                    . " WHERE a.employee_id = '$employee_id'";
               $res_emp = $db->query($sql);
               list($job_nm0,$job_abbr0,$org_nm0,$org_abbr0,$nip0,$employee_nm0,$person_id0)=$db->fetchRow($res_emp);
               
               
               $change_flag = "";
               $sql = "SELECT ttlrcl,jm,cf,jmxxx,cfxxx,updated_dttm FROM ${db0}.".XOCP_PREFIX."employee_competency_final_recap"
                    . " WHERE asid = '10'"
                    . " AND employee_id = '$employee_id'"
                    . " AND job_id = '$job_id'";
               $rc = $db->query($sql);
               if($db->getRowsNum($rc)>0) {
                  list($ttlrcl0,$old_match,$old_cf,$match0,$cf0,$updated_dttm0)=$db->fetchRow($rc);
               } else {
                  $old_match = $old_cf = 0;
                  $match0 = 0;
                  $cf0 = 0;
                  $updated_dttm0 = "";
                  continue;
               }
               
               $sql = "SELECT ttlrcl,jm,cf,jmxxx,cfxxx,updated_dttm,IF(updated_dttm>='2012-03-21 11:00:00','no','yes') FROM ${db1}.".XOCP_PREFIX."employee_competency_final_recap"
                    . " WHERE asid = '10'"
                    . " AND employee_id = '$employee_id'"
                    . " AND job_id = '$job_id'";
               $rc = $db->query($sql);
               if($db->getRowsNum($rc)>0) {
                  list($ttlrcl1,$old_match,$old_cf,$match1,$cf1,$updated_dttm1,$change_flag)=$db->fetchRow($rc);
               } else {
                  $old_match = $old_cf = 0;
                  $match1 = 0;
                  $cf1 = 0;
                  $updated_dttm1 = "";
                  $change_flag = "";
                  continue;
               }
               
               if($match0==$match1) continue;
               if($match0!=$match1&&$change_flag!="yes") continue;
               
               $diff = "";
               if($match0!=$match1) {
                  $sql = "SELECT b.competency_abbr,a.cclxxx,a.competency_id"
                       . " FROM ${db0}.".XOCP_PREFIX."employee_competency_final a"
                       . " LEFT JOIN ${db0}.".XOCP_PREFIX."competency b USING(competency_id)"
                       . " WHERE a.employee_id = '$employee_id'"
                       . " AND a.asid = '10'"
                       . " AND a.job_id = '$job_id'";
                  $rcf = $db->query($sql);
                  if($cnt==23) _debuglog($sql);
                  if($db->getRowsNum($rcf)>0) {
                     while(list($cabr,$cclxxx,$competency_id0)=$db->fetchRow($rcf)) {
                        $sql = "SELECT b.competency_abbr,a.cclxxx,a.competency_id"
                             . " FROM ${db1}.".XOCP_PREFIX."employee_competency_final a"
                             . " LEFT JOIN ${db1}.".XOCP_PREFIX."competency b USING(competency_id)"
                             . " WHERE a.employee_id = '$employee_id'"
                             . " AND a.asid = '10'"
                             . " AND a.job_id = '$job_id'"
                             . " AND a.competency_id = '$competency_id0'";
                        $rcf1 = $db->query($sql);
                        if($cnt==23) _debuglog($sql);
                        if($db->getRowsNum($rcf1)>0) {
                           list($cabr1,$cclxxx1,$competency_id1)=$db->fetchRow($rcf1);
                           if($cclxxx!=$cclxxx1) {
                              $diff .= "|$cabr";
                              $diff = "[<span class='ylnk' onclick='view_detail(\"$employee_id\",\"$job_id\",this,event);'>detail</span>]";
                           }
                        }
                        
                     }
                  }
               } else {
               
               }
               if($job_class_nm_old!=$job_class_nm) {
                  $tbl .= "<tr><td colspan='14' style='font-weight:bold;text-align:left;background-color:#ddd;'>$job_class_nm</td></tr>";
                  $job_class_nm_old = $job_class_nm;
               }
               $cnt++;
               $tbl .= "<tr id='tremployee_${employee_id}_${job_id}'><td>$cnt</td><td>$nip</td><td>$employee_nm/$employee_id</td><td>$job_abbr/$job_id</td><td>$org_abbr</td>";
               
               
               if($match0!=$match1) {
               
                  $tbl .= "<td style='text-align:center;color:red;'>".toMoney($match0)."</td>";
                  $tbl .= "<td style='text-align:center;color:red;'>".toMoney($match1)."</td>";
                  
                  if($change_flag=="yes" ) {
                     $tbl .= "<td style='color:red;'>$updated_dttm0</td>";
                     $tbl .= "<td style='color:red;'>$updated_dttm1</td>";
                  } else {
                     $tbl .= "<td style=''>$updated_dttm0</td>";
                     $tbl .= "<td style=''>$updated_dttm1</td>";
                  }
                  
               } else {
               
                  $tbl .= "<td style='text-align:center;'>".toMoney($match0)."</td>";
                  $tbl .= "<td style='text-align:center;'>".toMoney($match1)."</td>";
               
                  $tbl .= "<td style=''>$updated_dttm0</td>";
                  $tbl .= "<td style=''>$updated_dttm1</td>";
               }
               
               $tbl .= "<td>".toMoney($cf0)."</td>";
               $tbl .= "<td>".toMoney($cf1)."</td>";
               $tbl .= "<td>$ttlrcl0</td>";
               $tbl .= "<td>$ttlrcl1</td>";
               $tbl .= "<td>$diff</td>";
               
               
               
               
               $tbl .= "</tr>";
               
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
           . " FROM ".XOCP_PREFIX."employee_competency_final_recap r"
           . " LEFT JOIN ".XOCP_PREFIX."jobs a USING(job_id)"
           . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
           . " LEFT JOIN ".XOCP_PREFIX."orgs c ON c.org_id = a.org_id"
           . " LEFT JOIN ".XOCP_PREFIX."org_class d USING(org_class_id)"
           . " WHERE a.status_cd = 'normal'"
           . " AND r.asid = '10'"
           . " GROUP BY r.job_id";
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
      return "<div style=''>".$asidsel.$pmssel.$ret."</div>";
      
   }
}

} // HRIS_HRCOMPILEDREPORT_DEFINED
?>