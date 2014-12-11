<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/hrmonitorassessment.php                      //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-12-18                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_HRMONITORASSESSMENT_DEFINED') ) {
   define('HRIS_HRMONITORASSESSMENT_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
include_once(XOCP_DOC_ROOT."/modules/hris/class/selectasid.php");

class _hris_HRMonitorAssessment extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_MONITORASSESSMENT_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_MONITORASSESSMENT_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function __construct($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function listSession() {
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_recalcass.php");
      $ajax = new _hris_class_RecalculateAssessmentAjax("arc");
      
      $db=&Database::getInstance();
      
      $asid = $_SESSION["hris_assessment_asid"];
      
      ///// get job list + competencies
      $sql = "SELECT a.job_id,b.job_level FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
           . " WHERE a.status_cd = 'normal'"
           . " ORDER BY b.job_class_level";
      $result = $db->query($sql);
      $jobs = array();
      _debuglog($sql);
      if($db->getRowsNum($result)>0) {
         while(list($job_id)=$db->fetchRow($result)) {
            $jobs[$job_id] = 1;
         }
      }
      
      foreach($jobs as $job_idx=>$v) {
         $sql = "SELECT a.rcl,a.itj,b.competency_id,b.competency_nm,b.competency_abbr,b.desc_en,b.desc_id"
              . " FROM ".XOCP_PREFIX."job_competency a"
              . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
              . " WHERE a.job_id = '$job_idx'"
              . " ORDER BY b.competency_abbr";
         $result = $db->query($sql);
         if($db->getRowsNum($result)>0) {
            while(list($rcl,$itj,$competency_id,$competency_nm,$competency_abbr,$desc_en,$desc_id)=$db->fetchRow($result)) {
               if(!isset($job_ttl_rcl[$job_idx])) {
                  $job_ttl_rcl[$job_idx] = 0;
               }
               
               $sql = "SELECT compgroup_id FROM ".XOCP_PREFIX."competency WHERE competency_id = '$competency_id'";
               $rgroup = $db->query($sql);
               list($compgroup_id)=$db->fetchRow($rgroup);
               
               if($compgroup_id==3) {
                  $answer_t = "grade";
               } else {
                  $answer_t = "yesno";
               }
               $answer_t = "grade";
               
               $job_data[$job_idx][$competency_id] = array($rcl,$itj,$answer_t,$compgroup_id,$competency_abbr,$competency_nm,$competency_id,$desc_en,$desc_id);
            }
         }
      }
      
      
      $ret = "<table style='width:100%;' class='xxlist'><thead>"
           . "<tr><td>Level</td><td>Employee</td><td>Assessor</td><td>Status</td></tr>"
           . "</thead>"
           . "<tbody id='unfinished_body'>";
      
      foreach($jobs as $job_idx=>$v) { //// each job
         $sql = "SELECT a.employee_id,b.employee_ext_id,c.person_nm,d.job_abbr,d.job_nm,e.job_level"
              . " FROM ".XOCP_PREFIX."employee_job a"
              . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
              . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
              . " LEFT JOIN ".XOCP_PREFIX."jobs d ON d.job_id = a.job_id"
              . " LEFT JOIN ".XOCP_PREFIX."job_class e ON e.job_class_id = d.job_class_id"
              . " WHERE a.job_id = '$job_idx'"
              . " ORDER BY c.person_nm";
         $remp = $db->query($sql);
         
         
         if($job_idx==32) _debuglog($sql);
         
         if($db->getRowsNum($remp)>0) {
            while(list($employee_id,$nip,$employee_nm,$job_abbr,$job_nm,$job_level)=$db->fetchRow($remp)) { //// each employee
               _debuglog($employee_nm);
               $sql = "SELECT assessor_id,assessor_t FROM hris_assessor_360 WHERE asid = '$asid' AND employee_id = '$employee_id' AND status_cd = 'active'";
               $ras = $db->query($sql);
               if($employee_id==357) _debuglog($sql);
               if($db->getRowsNum($ras)>0) {
                  while(list($assessor_id,$assessor_t)=$db->fetchRow($ras)) {
                     
                     $sql = "SELECT b.person_nm FROM ".XOCP_PREFIX."employee a"
                          . " LEFT JOIN ".XOCP_PREFIX."persons b USING(person_id)"
                          . " WHERE a.employee_id = '$assessor_id'";
                     $rempx = $db->query($sql);
                     list($assessor_nm)=$db->fetchRow($rempx);
                     
                     
                     $unfinished = 0;
                     $notes = "";
                     foreach($job_data[$job_idx]  as $competency_id=>$v) {
                        
                        list($rcl,$itj,$answer_t,$compgroup_id,$competency_abbr,$competency_nm,$competency_id,$desc_en,$desc_id)=$v;
                        
                        if($assessor_t!="superior"&&$compgroup_id==3) continue;
                        
                        if($assessor_t=="superior") {
                           
                           $sql = "SELECT proficiency_lvl,fulfilled,level_value FROM ".XOCP_PREFIX."employee_level"
                                . " WHERE employee_id = '$employee_id'"
                                . " AND competency_id = '$competency_id'"
                                . " AND assessor_id = '$assessor_id'"
                                . " ORDER BY proficiency_lvl DESC LIMIT 1";
                           $r0 = $db->query($sql);
                           if($db->getRowsNum($r0)==1) {
                              list($proficiency_lvl,$fulfilled_last,$level_value_last)=$db->fetchRow($r0);
                              if($proficiency_lvl==$rcl&&$fulfilled_last==0) {
                                 $proficiency_lvl--;
                                 $unfinished++;
                                 $notes .= "<div style='border-bottom:1px solid #eee;padding:2px;'>$competency_abbr $competency_nm / CCL : $proficiency_lvl / RCL : $rcl</div>";
                              } else if($proficiency_lvl<$rcl&&$fulfilled_last!=-1) {
                                 $unfinished++;
                                 $notes .= "<div style='border-bottom:1px solid #eee;padding:2px;'>$competency_abbr $competency_nm / CCL : $proficiency_lvl / RCL : $rcl</div>";
                              }
                           } else {
                              $unfinished++;
                              $notes .= "<div style='border-bottom:1px solid #eee;padding:2px;'>$competency_abbr $competency_nm / CCL : 0? / RCL : $rcl</div>";
                           }
                           
                        } else { ///// 360
                           
                           $sql = "SELECT proficiency_lvl,fulfilled,level_value FROM ".XOCP_PREFIX."employee_level360"
                                . " WHERE employee_id = '$employee_id'"
                                . " AND competency_id = '$competency_id'"
                                . " AND assessor_id = '$assessor_id'"
                                . " ORDER BY proficiency_lvl DESC LIMIT 1";
                           $r0 = $db->query($sql);
                           if($db->getRowsNum($r0)==1) {
                              list($proficiency_lvl,$fulfilled_last,$level_value_last)=$db->fetchRow($r0);
                              if($proficiency_lvl==$rcl&&$fulfilled_last==0) {
                                 $proficiency_lvl--;
                                 $unfinished++;
                                 $notes .= "<div style='border-bottom:1px solid #eee;padding:2px;'>$competency_abbr $competency_nm / CCL : $proficiency_lvl / RCL : $rcl</div>";
                              } else if($proficiency_lvl<$rcl&&$fulfilled_last!=-1) {
                                 $unfinished++;
                                 $notes .= "<div style='border-bottom:1px solid #eee;padding:2px;'>$competency_abbr $competency_nm / CCL : $proficiency_lvl / RCL : $rcl</div>";
                              }
                           } else {
                              $unfinished++;
                              $notes .= "<div style='border-bottom:1px solid #eee;padding:2px;'>$competency_abbr $competency_nm / CCL : 0? / RCL : $rcl</div>";
                              
                           }
                        }
                     }
                     
                     if($unfinished>0) {
                        $ret .= "<tr><td>$job_level</td><td>$employee_nm</td><td>$assessor_nm</td><td><span class='xlnk' onclick='view_detail(\"$employee_id\",\"$assessor_id\");'>Unfinished</span></td></tr>"
                              . "<tr style='display:none;' id='detail_${employee_id}_${assessor_id}'><td style='padding:10px;padding-left:20px;' colspan='4'>$notes</td></tr>";
                     }
                     
                  }
               }
               
            }
         }
      }
      
            
      
      
      
      
      $ret .= "</tbody></table>";
      
      $js = $ajax->getJs()."<script type='text/javascript'><!--
      
      function view_detail(employee_id,assessor_id) {
         if($('detail_'+employee_id+'_'+assessor_id).style.display=='none') {
            $('detail_'+employee_id+'_'+assessor_id).style.display = '';
         } else {
            $('detail_'+employee_id+'_'+assessor_id).style.display = 'none';
         }
      }
      
      var dtr = null;
      function get_detail(assessor_id,employee_id,d,e) {
         if(dtr) {
            _destroy(dtr);
            if(dtr.assessor_id&&dtr.assessor_id==assessor_id&&dtr.employee_id&&dtr.employee_id==employee_id) {
               dtr.assessor_id = null;
               dtr.employee_id = null;
               dtr = null;
               return;
            }
         }
         dtr.assessor_id = assessor_id;
         dtr.employee_id = employee_id;
         dtr = _dce('tr');
         dtr.td = dtr.appendChild(_dce('td'));
         dtr.td.setAttribute('colspan','4');
         dtr = $('unfinished_body').insertBefore(dtr,d.parentNode.parentNode.nextSibling);
         dtr.td.setAttribute('style','padding:10px;');
         
      }
      
      // --></script>";
      
      return $js.$ret;
   }
   
   
   function main() {
      $db = &Database::getInstance();
      
      $asidselobj = new _hris_class_SelectAssessmentSession();
      $asidsel = "<div style='padding-bottom:2px;'>".$asidselobj->show()."</div>";
      
      if(!isset($_SESSION["hris_assessment_asid"])||$_SESSION["hris_assessment_asid"]==0) {
         return $asidsel;
      }
      
      switch ($this->catch) {
         case $this->blockID:
            $ret = $this->listSession();
            break;
         default:
            $ret = $this->listSession();
            break;
      }
      return "<div style='width:900px;'>".$asidsel.$ret."</div>";
      
   }
}

} // HRIS_HRMONITORASSESSMENT_DEFINED
?>