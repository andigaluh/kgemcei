<?php
//--------------------------------------------------------------------//
// Filename : modules/pms/pmsjam.php                                  //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2010-09-22                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('PMS_JAMAPPROVAL_DEFINED') ) {
   define('PMS_JAMAPPROVAL_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
include_once(XOCP_DOC_ROOT."/modules/pms/class/ajax_objective.php");
include_once(XOCP_DOC_ROOT."/modules/pms/pmsjam.php");
require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
include_once(XOCP_DOC_ROOT."/modules/pms/class/selectpms.php");

class _pms_JAMApproval extends XocpBlock {
   var $catchvar = _PMS_CATCH_VAR;
   var $blockID = _PMS_JAMAPPROVAL_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _PMS_JAMAPPROVAL_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _pms_JAMApproval($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */

   }
   
   function requestList() {
      $psid = $_SESSION["pms_psid"];
      $db = &Database::getInstance();
      $user_id = getUserID();
      global $xocp_vars;
      
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
      
      _debuglog($self_job_nm);
      
      $ret = "<table style='' class='xxlist'>"
           . "<colgroup>"
           . "<col width='40'/>"
           . "<col width='150'/>"
           . "<col width='*'/>"
           . "<col width='220'/>"
           . "<col width='220'/>"
           . "</colgroup>"
           . "<thead>"
           . "<tr>"
           . "<td style='text-align:center;'>No.</td>"
           . "<td>NIP</td>"
           . "<td>Employee</td>"
           . "<td>JAM Organization</td>"
           . "<td>Status</td>"
           . "</tr>"
           . "</thead>"
           . "<tbody>";
      
      $sql = "SELECT a.approval_st,a.employee_id,b.employee_ext_id,c.person_nm,a.jam_org_ind,a.org_id"
           . " FROM pms_jam a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " WHERE a.psid = '$psid' AND (a.approval1_employee_id = '$self_employee_id' OR a.approval2_employee_id = '$self_employee_id')"
           . " AND a.approval_st IN ('approval1','approval2','return','implementation')"
           . " GROUP BY a.employee_id,a.approval_st,a.jam_org_ind";
      $result = $db->query($sql);
      $no=0;
      _debuglog($sql);
      if($db->getRowsNum($result)>0) {
         while(list($approval_st,$employee_id,$nip,$employee_nm,$jam_org_ind,$org_id)=$db->fetchRow($result)) {
            $no++;
            $jam_org_nm = "";
            $jam_org_class_nm = "";
            if($jam_org_ind==1) {
               $sql = "SELECT a.org_nm,b.org_class_nm FROM ".XOCP_PREFIX."orgs a LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id) WHERE a.org_id = '$org_id'";
               $ro = $db->query($sql);
               if($db->getRowsNum($ro)==1) {
                  list($jam_org_nm,$jam_org_class_nm)=$db->fetchRow($ro);
               }
            }
            switch($approval_st) {
               case "new":
                  $approval_st_txt = "Preparation";
                  break;
               case "approval1":
                  $approval_st_txt = "Waiting Superior Approval";
                  break;
               case "approval2":
                  $approval_st_txt = "Waiting Next Superior Approval";
                  break;
               case "implementation":
                  $approval_st_txt = "Implementation";
                  break;
               case "return":
                  $approval_st_txt = "Returned";
                  break;
               default:
                  $approval_st_txt = "Unknown Status";
                  break;
            }
            
            $ret .= "<tr>"
                  . "<td style='text-align:center;'>$no</td>"
                  . "<td>$nip</td>"
                  . "<td>$employee_nm</td>"
                  . "<td>$jam_org_nm $jam_org_class_nm</td>"
                  . "<td><a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&goto=y&jam_org_ind=$jam_org_ind&jam_org_id=$org_id&employee_id=$employee_id&u=".uniqid('u')."'>$approval_st_txt</a></td>"
                  . "</tr>";
         }
      } else {
         $ret .= "<tr><td colspan='4' style='text-align:center;color:#888;font-style:italic;'>"._EMPTY."</td></tr>";
      }
      
      $ret .= "</tbody></table>";
      
      return $ret;
      
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
   
   
   
   function approval($employee_id,$jam_org_ind,$jam_org_id) {
      $db=&Database::getInstance();
      
      $psid = $_SESSION["pms_psid"];
      
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
      
      
      $sql = "SELECT c.job_nm,c.job_abbr,d.org_nm,d.org_abbr,a.employee_ext_id,e.person_nm,e.person_id"
           . " FROM ".XOCP_PREFIX."employee a"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job b ON b.employee_id = a.employee_id AND b.job_id = '$job_id'"
           . " LEFT JOIN ".XOCP_PREFIX."jobs c ON c.job_id = '$job_id'"
           . " LEFT JOIN ".XOCP_PREFIX."orgs d ON d.org_id = c.org_id"
           . " LEFT JOIN ".XOCP_PREFIX."persons e ON e.person_id = a.person_id"
           . " WHERE a.employee_id = '$employee_id'";
      $result = $db->query($sql);
      list($job_nm,$job_abbr,$org_nm,$org_abbr,$nip,$employee_nm,$person_id)=$db->fetchRow($result);
      $ret = "<div style='border:1px solid #bbb;background-color:#eee;padding:5px;text-align:right;'>[<a href='".XOCP_SERVER_SUBDIR."/index.php'>Back</a>]</div>";
      $ret .= "<br/><table style='margin-left:20px;'><tr><td style='padding:4px;border:1px solid #bbb;-moz-box-shadow:2px 2px 5px #333;'><img src='".XOCP_SERVER_SUBDIR."/modules/hris/thumb.php?pid=${person_id}' height='100'/></td>"
            . "<td style='vertical-align:top;padding-left:10px;'>"
            
            . "<table style='font-weight:bold;margin-left:0px;font-size:1.1em;'><colgroup><col width='120'/><col/></colgroup><tbody>"
            . "<tr><td>Job Title</td><td>: $job_nm ($job_abbr)</td></tr>"
            . "<tr><td>Section/Division</td><td>: $org_nm ($org_abbr)</td></tr>"
            . "<tr><td>Incumbent</td><td>: $employee_nm</td></tr>"
            . "<tr><td>NIP</td><td>: $nip</td></tr>"
            . "</tbody></table></td></tr></table><div style='padding:10px;'>";
      
      $sql = "SELECT DISTINCT(approval_st) FROM pms_jam WHERE psid = '$psid' AND employee_id = '$employee_id' AND jam_org_ind = '$jam_org_ind' AND org_id = '$jam_org_id'";
      $result = $db->query($sql);
      _debuglog($sql);
      if($db->getRowsNum($result)==1) {
         list($jam_status_cd)=$db->fetchRow($result);
         if($employee_id!=$self_employee_id) {
            if($jam_status_cd=="new") {
               return $ret. "<br/><br/>JAM is still prepared.";
            }
         }
      } else {
         return $ret."<br/<br/>No JAM found.";
      }
      
      $ret .= _pms_JAM::pmsjam($employee_id,$jam_org_ind,$jam_org_id);
      return $ret;
   }
   
   function main() {
      $db = &Database::getInstance();
      $user_id = getUserID();
      
      $pmsselobj = new _pms_class_SelectSession();
      $pmssel = "<div style='padding-bottom:2px;'>".$pmsselobj->show()."</div>";
      
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
      
      switch ($this->catch) {
         case $this->blockID:
            if($_GET["goto"]=="y") {
               $ret = $this->approval($_GET["employee_id"],$_GET["jam_org_ind"],$_GET["jam_org_id"]);
            } else {
               $ret = $this->requestList();
            }
            break;
         default:
            if($_GET["goto"]=="y") {
               $ret = $this->approval($_GET["employee_id"],$_GET["jam_org_ind"],$_GET["jam_org_id"]);
            } else {
               $ret = $this->requestList();
            }
            break;
      }
      return $pmssel.$ret;
   }
}

} // PMS_JAMAPPROVAL_DEFINED
?>