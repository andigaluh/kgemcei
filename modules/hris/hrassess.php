<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/assessmentresult.php                       //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-12-18                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_HRASSESSMENT_DEFINED') ) {
   define('HRIS_HRASSESSMENT_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
include_once(XOCP_DOC_ROOT."/modules/hris/assessmentresult.php");
include_once(XOCP_DOC_ROOT."/modules/hris/class/selectemployee.php");
include_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_employee.php");

global $slemp;
$slemp = new _hris_class_SelectEmployee();

class _hris_HRAssessmentResult extends _hris_AssessmentResult {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_ASSESSMENT_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_ASSESSMENTRESULT_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function __construct($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function listSession() {
      $db=&Database::getInstance();
      $employee_id = $_SESSION["hris_employee_id"];
      $user_id = getUserID();
      $sql = "SELECT b.job_id,a.asid,a.session_nm,a.session_periode,c.job_nm,a.session_t,a.idp_employee_id"
           . " FROM ".XOCP_PREFIX."assessment_session a"
           . " LEFT JOIN ".XOCP_PREFIX."assessment_session_job b ON b.asid = a.asid AND b.employee_id = '$employee_id'"
           . " LEFT JOIN ".XOCP_PREFIX."jobs c USING(job_id)"
           . " WHERE a.status_cd = 'normal'"
           . ($user_id==1?"":" AND a.asid >= '10'")
           . " ORDER BY a.session_periode DESC";
      $result = $db->query($sql);
      $ret = "<table class='xxlist' style='width:100%;' align='center'>"
           . "<thead><tr><td>Assessment Sessions</td>"
           . "<td style='text-align:right;'>Job Title</td></tr>"
           . "</thead><tbody id='tbproc'>";
      if($db->getRowsNum($result)>0) {
         while(list($job_id,$asid,$session_nm,$session_periode,$job_nm,$session_t,$idp_employee_id)=$db->fetchRow($result)) {
            if($session_t=="idp"&&$idp_employee_id!=$employee_id) continue;
            $ret .= "<tr><td id='tdclass_${asid}'>"
                  . "<a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&view=1&asid=$asid&job_id=$job_id'>".htmlentities(stripslashes($session_nm))."</a>"
                  . "</td>"
                  . "<td style='text-align:right;'>$job_nm</td></tr>";
         }
      }
      $ret .= "<tr style='display:none;' id='trempty'><td>"._EMPTY."</td></tr>";
      $ret .= "</tbody></table>";
      
      return $ret;
   }
   
   
   function main() {
      $db = &Database::getInstance();
      
      $self = $_SESSION["asmresself"];
      $user_id = getUserID();
      
      
      global $slemp;
      $slemp->setURLParam(XOCP_SERVER_SUBDIR."/index.php",array($this->catchvar=>$this->blockID));
      $slemphtml = $slemp->show();
      if($_SESSION["hris_employee_id"] == 0) {
         return $slemphtml;
      }
      

      switch ($this->catch) {
         case $this->blockID:
            if(isset($_GET["view"])&&$_GET["view"]=="1") {
               $employee_id = $_SESSION["hris_employee_id"];
               $person_id = $_SESSION["hris_employee_person_id"];
               $asid = $_GET["asid"]+0;
               $_SESSION["hris_assessment_asid"] = $asid;
               $sql = "SELECT job_id FROM ".XOCP_PREFIX."assessment_session_job WHERE employee_id = '$employee_id' AND asid = '$asid'";
               $result = $db->query($sql);
               $job_id = 0;
               if($db->getRowsNum($result)==1) {
                  list($job_id)=$db->fetchRow($result);
               }
               if($job_id>0) {
                  $sql = "SELECT session_nm,session_periode FROM ".XOCP_PREFIX."assessment_session WHERE asid = '$asid'";
                  $result = $db->query($sql);
                  list($session_nm,$session_periode)=$db->fetchRow($result);
                  $ss = "<div style='border:0px solid #ddd;background-color:#ffffff;padding:2px;text-align:left;'>"
                      . "<table style='width:100%;'><tbody><tr><td style='text-align:center;'>Assessment Session : <span style='font-weight:bold;color:#333;'>$session_periode $session_nm</span></td>"
                      //. "<td style='text-align:right;'>[<a href='".XOCP_SERVER_SUBDIR."/index.php'>Select Session</a>]</td>"
                      . "</tr></tbody></table>"
                      . "</div>";
                  $ret = $ss . $this->result($employee_id,$job_id,$asid);
               } else {
                  $ret = "<div style='margin-top:5px;margin-bottom:5px;padding:10px;text-align:center;border:1px solid #888;background-color:#ffcccc;'>This employee has no job at the selected session.</div>"
                       . $this->listSession();
               }
            } else {
               $ret = $this->listSession();
            }
            break;
         default:
            $ret = $this->listSession();
            break;
      }
      return $slemphtml.$ret;
   }
}

} // HRIS_HRASSESSMENT_DEFINED
?>