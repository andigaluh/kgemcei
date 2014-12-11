<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/assessorcheck.php                          //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-07-18                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_ASSESSORCHECK_DEFINED') ) {
   define('HRIS_ASSESSORCHECK_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_AssessorCheck extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_ASSESSORCHECK_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_ASSESSORCHECK_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_AssessorCheck($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function emplist() {
      $db=&Database::getInstance();
      $asid = 8;
      $tooltips = "";
      $ret = "<div></div>";
      $ret .= "<table class='xxlist' style='width:100%;margin-bottom:100px;'>"
            . "<colgroup><col/><col/><col/><col/><col/></colgroup>"
            . "<thead>"
            . "<tr>"
            . "<td rowspan='2' style='text-align:center;border-right:1px solid #bbb;'>Employee</td>"
            . "<td colspan='4' style='text-align:center;'>Assessor</td></tr>"
            . "<tr><td style='text-align:center;border-right:1px solid #bbb;'>Superior</td>"
            . "<td style='text-align:center;border-right:1px solid #bbb;'>Subordinate</td>"
            . "<td style='text-align:center;border-right:1px solid #bbb;'>Peer</td>"
            . "<td style='text-align:center;'>Customer</td></tr>"
            . "<thead><tbody>";
      $sql = "SELECT a.job_id,a.job_abbr,a.job_nm,b.job_class_nm,e.person_nm,a.assessor_job_id,d.employee_id"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job c ON c.job_id = a.job_id"
           . " LEFT JOIN ".XOCP_PREFIX."employee d ON d.employee_id = c.employee_id"
           . " LEFT JOIN ".XOCP_PREFIX."persons e ON e.person_id = d.person_id"
           . " ORDER BY b.job_class_level,a.job_abbr,a.job_id";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($job_id,$job_abbr,$job_nm,$job_class_nm,$employee_nm,$assessor_job_id,$employee_id)=$db->fetchRow($result)) {
            
            ///// superior
            $sql = "SELECT c.person_nm,a.job_abbr"
                 . " FROM ".XOCP_PREFIX."jobs a"
                 . " LEFT JOIN ".XOCP_PREFIX."employee_job b USING(job_id)"
                 . " LEFT JOIN ".XOCP_PREFIX."employee e USING(employee_id)"
                 . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
                 . " WHERE a.job_id = '$assessor_job_id'";
            $rsuperior = $db->query($sql);
            if($db->getRowsNum($rsuperior)>0) {
               list($assessor_superior_nm,$assessor_superior_job_abbr)=$db->fetchRow($rsuperior);
            } else {
               $assessor_superior_nm = "";
            }
            
            ///// 360
            $sql = "SELECT e.job_abbr,d.person_nm,a.status_cd,a.assessor_t,a.assessor_id,c.employee_ext_id,c.entrance_dttm,"
                 . "b.start_dttm,b.stop_dttm,(TO_DAYS(now())-TO_DAYS(c.entrance_dttm)) as jobage,d.adm_gender_cd,d.person_id,"
                 . "o.org_nm,e.job_nm,p.org_class_nm"
                 . " FROM ".XOCP_PREFIX."assessor_360 a"
                 . " LEFT JOIN ".XOCP_PREFIX."employee_job b ON b.employee_id = a.assessor_id"
                 . " LEFT JOIN ".XOCP_PREFIX."jobs e ON e.job_id = b.job_id"
                 . " LEFT JOIN ".XOCP_PREFIX."employee c ON c.employee_id = b.employee_id"
                 . " LEFT JOIN ".XOCP_PREFIX."persons d USING(person_id)"
                 . " LEFT JOIN ".XOCP_PREFIX."orgs o ON o.org_id = e.org_id"
                 . " LEFT JOIN ".XOCP_PREFIX."org_class p ON p.org_class_id = o.org_class_id"
                 . " WHERE a.employee_id = '$employee_id'"
                 . " ORDER BY a.status_cd,b.job_id";
            $r360 = $db->query($sql);
            $assessor_peer = $assessor_subordinate = $assessor_customer = "";
            if($db->getRowsNum($r360)>0) {
               while(list($a360_job_abbr,$a360_person_nm,$a360_status,$a360_t,$assessor_id,$assessor_nip,$entrance_dttm,
                          $jobstart,$a360_stop_job,$jobage,$gender,$person_id,$org_nm,$job_nm,$org_class_nm)=$db->fetchRow($r360)) {
                  
                  $a360_person_nm = htmlentities($a360_person_nm,ENT_QUOTES);
                  
                  $person_info = "<table style='width:100%;'><tbody><tr><td style='vertical-align:top;'><img src='".XOCP_SERVER_SUBDIR."/modules/hris/thumb.php?pid=$person_id' height='100'/></td>"
                               . "<td style='vertical-align:top;'><table class='emp_info' style='margin-top:0px;width:290px;'>"
                               . "<colgroup><col width='80'/><col/></colgroup>"
                               . "<tbody>"
                               . "<tr><td>Employee ID :</td><td>$assessor_nip</td></tr>"
                               . "<tr><td>Entrance Date :</td><td>".sql2ind($entrance_dttm,"date")." ("._bctrim(toMoney($jobage/365.25))." year)</td></tr>"
                               . "<tr><td>Job Title :</td><td>$job_nm</td></tr>"
                               . "<tr><td>$org_class_nm :</td><td>$org_nm</td></tr>"
                               . "<tr><td>Gender :</td><td>".($gender=="f"?"Female":"Male")."</td></tr></tbody></table></td></tr>"
                               . "</tbody></table>";
                  if($a360_t=="subordinat") {
                     $tooltips .= "\nnew Tip('a360_${employee_id}_${assessor_id}_${a360_t}', \"$person_info\", {title:'$a360_person_nm',width:350,style:'emp'});";
                  }
                  
                  $id = "a360_${employee_id}_${assessor_id}_${a360_t}";
                  if($a360_t=="peer") {
                     if($a360_status=="active") {
                        $clr = "";
                     } else {
                        $clr = "color:#bbb;";
                     }
                     $assessor_peer .= "<div class='a360' style='$clr' id='$id'>$a360_person_nm</div>";
                  }
                  if($a360_t=="subordinat") {
                     if($a360_status=="active") {
                        $clr = "";
                     } else {
                        $clr = "color:#bbb;";
                     }
                     $assessor_subordinate .= "<div class='a360' style='$clr' id='$id'>$a360_person_nm $assessor_idx</div>";
                  }
                  if($a360_t=="customer") {
                     if($a360_status=="active") {
                        $clr = "";
                     } else {
                        $clr = "color:#bbb;";
                     }
                     $assessor_customer .= "<div class='a360' style='$clr' id='$id'>$a360_person_nm</div>";
                  }
               }
            }
            
            $ret .= "\n<tr>"
                  . "<td style='border-right:1px solid #bbb;' id='emp_${employee_id}'>$employee_nm</span></td>"
                  . "<td style='border-right:1px solid #bbb;'>$assessor_superior_nm</td>"
                  . "<td style='border-right:1px solid #bbb;'>$assessor_subordinate</td>"
                  . "<td style='border-right:1px solid #bbb;'>$assessor_peer</td>"
                  . "<td style=''>$assessor_customer</td>"
                  . "</tr>";
            
         }
      }
      $ret .= "</tbody></table>";

      $ret .= "\n<script type='text/javascript' src='".XOCP_SERVER_SUBDIR."/include/prototip2.0.5/js/prototip.js'></script>"
           . "<link rel='stylesheet' type='text/css' href='".XOCP_SERVER_SUBDIR."/include/prototip2.0.5/css/prototip.css' />";
      
      $ret .= "<script type='text/javascript'><!--
      
      $tooltips
      
      // --></script>";

      return $ret;
   }
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $ret = $this->emplist();
            break;
         default:
            $ret = $this->emplist();
            break;
      }
      return $ret;
   }
}

} // HRIS_ASSESSORCHECK_DEFINED
?>
