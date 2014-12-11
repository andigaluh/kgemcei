<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/editjobclass.php                           //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_IDPNEXTSUPERIORAPPROVAL_DEFINED') ) {
   define('HRIS_IDPNEXTSUPERIORAPPROVAL_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/idp/idp.php");
include_once(XOCP_DOC_ROOT."/modules/hris/assessmentresult.php");

class _hris_IDPNextSuperiorApprove extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_IDPNEXTSUPERIORAPPROVAL_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_IDPNEXTSUPERIORAPPROVAL_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_IDPNextSuperiorApprove($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   function requestList() {
      include_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $db=&Database::getInstance();
      $user_id = getUserID();
      list($self_job_id,
           $self_employee_id,
           $self_job_nm,
           $self_nm,
           $self_nip,
           $self_gender,
           $self_jobstart,
           $self_entrance_dttm,
           $self_jobage,
           $self_job_summary,
           $self_person_id,
           $self_user_id,
           $self_first_assessor_job_id,
           $self_next_assessor_job_id)=_hris_getinfobyuserid($user_id);
      
      $assessor_job_count = 0;
      global $proficiency_level_name;
      
      $_SESSION["html"]->js_scriptaculous_effecs=TRUE;
      
      $arr_compgroup = array();
      
      $sql = "SELECT request_id,employee_id,approve_superior_id,approve_superior_dttm,approve_hris_id,approve_hris_dttm,cost_estimate,requested_dttm,status_cd"
           . " FROM ".XOCP_PREFIX."idp_request"
           . " ORDER BY requested_dttm";
      $result = $db->query($sql);
      $ret = "<table class='xxlist' style='width:100%;'>"
           . "<thead>"
           . "<tr>"
           . "<td>ID</td>"
           . "<td>Employee</td>"
           . "<td>NIP</td>"
           . "<td>Request Time</td>"
           . "<td>Time Frame</td>"
           . "<td>Status</td>"
           . "</tr>"
           . "</thead>"
           . "<tbody>";
      if($db->getRowsNum($result)>0) {
         while(list($request_id,$employee_id,$approve_superior_id,$approve_superior_dttm,$approve_hris_id,$approve_hris_dttm,$cost_estimate,$requested_dttm,$status_cd)=$db->fetchRow($result)) {
            if($status_cd=="rejected") continue;
            if($status_cd=="nullified") continue;
            if($status_cd=="completed") continue;
            if($status_cd!="approval2") continue;
            
            
            list($job_idx,
                 $employee_idx,
                 $job_nmx,
                 $nmx,
                 $nipx,
                 $genderx,
                 $jobstartx,
                 $entrance_dttmx,
                 $jobagex,
                 $job_summaryx,
                 $person_idx,
                 $user_idx,
                 $first_assessor_job_id,
                 $next_assessor_job_id)=_hris_getinfobyemployeeid($employee_id);
      
            if($next_assessor_job_id!=$self_job_id) continue;
            
            $link = "<a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&req=y&e=${employee_id}&j=${job_id}'>Click to Approve</a>&nbsp;";
            
            $sql = "SELECT b.person_nm,a.employee_ext_id FROM ".XOCP_PREFIX."employee a"
                 . " LEFT JOIN ".XOCP_PREFIX."persons b USING(person_id)"
                 . " WHERE employee_id = '$employee_id'";
            $remp = $db->query($sql);
            if($db->getRowsNum($remp)>0) {
               list($employee_nm,$nip)=$db->fetchRow($remp);
            }
            list($timeframe_start,$timeframe_stop)=_idp_get_timeframe($request_id);
            
            $ret .= "<tr>"
                  . "<td>$request_id</td>"
                  . "<td>$employee_nm</td>"
                  . "<td>$nip</td>"
                  . "<td>".sql2ind($requested_dttm)."</td>"
                  . "<td>".sql2ind($timeframe_start,"date")." - ".sql2ind($timeframe_stop,"date")."</td>"
                  . "<td>$link</td>"
                  . "</tr>";
         }
      }
      $ret .= "</tbody></table>";
      
      
      return $ret;
      
   }
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            if(isset($_GET["smethod"])&&$_GET["smethod"]==1&&isset($_GET["t"])&&$_GET["t"]!="") {
               $_SESSION["hris_method_t"] = $_GET["t"];
               $ret = $this->requestList();
            } elseif(isset($_GET["r"])&&$_GET["r"]=="y"&&isset($_GET["e"])&&isset($_GET["j"])) {
               $employee_id = $_GET["e"]+0;
               $job_id = $_GET["j"]+0;
               $ret = $this->result($employee_id,$job_id);
            } elseif(isset($_GET["req"])&&$_GET["req"]=="y"&&isset($_GET["e"])) {
               include_once(XOCP_DOC_ROOT."/modules/hris/include/idp/idp.php");
               $employee_id = $_GET["e"]+0;
               $job_id = $_GET["j"]+0;
               $ret = _idp_view_request($employee_id,$job_id,TRUE,FALSE);
            } else if(isset($_GET["backlist"])&&$_GET["backlist"]==1) {
               unset($_SESSION["hris_method_t"]);
               $ret = $this->requestList();
            } else {
               $ret = $this->requestList();
            }
            break;
         default:
            $ret = $this->requestList();
            break;
      }
      return $ret."<div style='height:100px;'>&nbsp;</div>";
   }
}

} // HRIS_IDPNEXTSUPERIORAPPROVAL_DEFINED
?>
