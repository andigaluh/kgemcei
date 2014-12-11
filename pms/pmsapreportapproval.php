<?php
//--------------------------------------------------------------------//
// Filename : modules/pms/pmsjam.php                                  //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2010-09-22                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('PMS_APREPORTAPPROVAL_DEFINED') ) {
   define('PMS_APREPORTAPPROVAL_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
include_once(XOCP_DOC_ROOT."/modules/pms/class/ajax_objective.php");
include_once(XOCP_DOC_ROOT."/modules/pms/pmsapreport.php");
require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
include_once(XOCP_DOC_ROOT."/modules/pms/class/selectpms.php");

class _pms_ActionPlanReportApproval extends XocpBlock {
   var $catchvar = _PMS_CATCH_VAR;
   var $blockID = _PMS_APREPORTAPPROVAL_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _PMS_APREPORTAPPROVAL_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function __construct($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
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
   
   
   
   function requestList() {
      $psid = $_SESSION["pms_psid"];
      $db = &Database::getInstance();
      $user_id = getUserID();
      global $xocp_vars;
      
      $pmsselobj = new _pms_class_SelectSession();
      $pmssel = "<div style='padding-bottom:2px;max-width:900px;'>".$pmsselobj->show()."</div>";
      
      if(!isset($_SESSION["pms_psid"])||$_SESSION["pms_psid"]==0) {
         return $pmssel;
      }
      
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
      
      $ret = "<table style='table-layout:fixed;width:900px;max-width:900px;' class='xxlist'>"
           . "<colgroup>"
           . "<col width='40'/>"
           . "<col width='150'/>"
           . "<col width='*'/>"
           . "<col width='150'/>"
           . "<col width='150'/>"
           . "</colgroup>"
           . "<thead>"
           . "<tr>"
           . "<td style='text-align:center;'>No.</td>"
           . "<td>NIP</td>"
           . "<td>Employee</td>"
           . "<td>Month</td>"
           . "<td>Status</td>"
           . "</tr>"
           . "</thead>"
           . "<tbody>";
      
      $sql = "SELECT a.report_approval_st,a.employee_id,b.employee_ext_id,c.person_nm,a.month_id"
           . " FROM pms_pic_action a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " WHERE a.psid = '$psid' AND a.approval1_employee_id = '$self_employee_id'"
           . " AND a.report_approval_st IN ('approval','return','final')"
           . " GROUP BY a.employee_id,a.report_approval_st,a.month_id";
      $result = $db->query($sql);
      _debuglog($sql);
      $no=0;
      if($db->getRowsNum($result)>0) {
         while(list($approval_st,$employee_id,$nip,$employee_nm,$month_id)=$db->fetchRow($result)) {
            $no++;
            $ret .= "<tr>"
                  . "<td style='text-align:center;'>$no</td>"
                  . "<td>$nip</td>"
                  . "<td>$employee_nm</td>"
                  . "<td>".$xocp_vars["month_year"][$month_id]."</td>"
                  . "<td><a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&goto=y&employee_id=$employee_id&u=".uniqid('u')."&month_id=${month_id}'>$approval_st</a></td>"
                  . "</tr>";
         }
      } else {
         $ret .= "<tr><td colspan='4' style='text-align:center;color:#888;font-style:italic;'>"._EMPTY."</td></tr>";
      }
      
      $ret .= "</tbody></table>";
      
      return $pmssel.$ret;
      
   }
   
   function approval($employee_id) {
      $psid = $_SESSION["pms_psid"];
      $db=&Database::getInstance();
      
      $pmsselobj = new _pms_class_SelectSession();
      $pmssel = "<div style='padding-bottom:2px;max-width:900px;'>".$pmsselobj->show()."</div>";
      
      if(!isset($_SESSION["pms_psid"])||$_SESSION["pms_psid"]==0) {
         return $pmssel;
      }
      
      list($job_id,
           $employee_idx,
           $job_nm,
           $employee_nm,
           $nip,
           $gender,
           $jobstart,
           $entrance_dttm,
           $jobage,
           $job_summary,
           $person_id,
           $employee_user_id,
           $first_assessor_job_id,
           $next_assessor_job_id)=_hris_getinfobyemployeeid($employee_id);
      
      
      $ret .= _pms_ActionPlanReport::report($employee_id,$_GET["month_id"]);
      return $pmssel.$ret;
   }
   
   function main() {
      $db = &Database::getInstance();
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
      
      switch ($this->catch) {
         case $this->blockID:
            if($_GET["goto"]=="y") {
               $ret = $this->approval($_GET["employee_id"]);
            } else {
               $ret = $this->requestList();
            }
            break;
         default:
            if($_GET["goto"]=="y") {
               $ret = $this->approval($_GET["employee_id"]);
            } else {
               $ret = $this->requestList();
            }
            break;
      }
      return $ret;
   }
}

} // PMS_APREPORTAPPROVAL_DEFINED
?>