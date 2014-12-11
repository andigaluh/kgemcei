<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/idpreportapprovaldivision.php               //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_IDPREPORTDIVISIONAPPROVAL_DEFINED') ) {
   define('HRIS_IDPREPORTDIVISIONAPPROVAL_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/idp/idp.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
include_once(XOCP_DOC_ROOT."/modules/hris/assessmentresult.php");

class _hris_IDPReportApprovalDivision extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_IDPREPORTDIVISIONAPPROVAL_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_IDPREPORTDIVISIONAPPROVAL_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_IDPReportApprovalDivision($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   function approval($request_id,$actionplan_id) {
      $db=&Database::getInstance();
      $_SESSION["html"]->js_tinymce = TRUE;
      $user_id = getUserID();
      
      //// hide follow notification
      $sql = "SELECT notification_id"
           . " FROM ".XOCP_PREFIX."idp_notifications"
           . " WHERE request_id = '$request_id'"
           . " AND message_id = '_IDP_YOUHAVEDMREPORTNOTIFICATION'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($notification_id)=$db->fetchRow($result)) {
            _idp_notification_followed($notification_id);
         }
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
      
      $sql = "SELECT a.event_id,a.method_t,b.method_type,a.method_id,c.method_nm,a.plan_start_dttm,a.plan_stop_dttm,a.competency_id FROM ".XOCP_PREFIX."idp_request_actionplan a"
           . " LEFT JOIN ".XOCP_PREFIX."idp_development_method_type b USING(method_t)"
           . " LEFT JOIN ".XOCP_PREFIX."idp_development_method c ON c.method_id = a.method_id"
           . " WHERE a.request_id = '$request_id' AND a.actionplan_id = '$actionplan_id'";
      $rap = $db->query($sql);
      if($db->getRowsNum($rap)==1) {
         list($event_id,$method_t,$method_type,$method_id,$method_nm,$plan_start_dttm,$plan_stop_dttm,$competency_id)=$db->fetchRow($rap);
         
         $actionplan_remark = "";
         $form = "";
         if($method_t!="") {
            $editor_file = XOCP_DOC_ROOT."/modules/hris/include/idp/method_${method_t}.php";
            if(file_exists($editor_file)) {
               require_once($editor_file);
               $fremark = "${method_t}_idp_m_getRemark";
               list($actionplan_remark,$actionplan_start,$actionplan_stop) = $fremark($request_id,$actionplan_id);
               $freport = "${method_t}_idp_m_getReportingForm";
               $form = $freport($request_id,$actionplan_id);
               $ret = "<div style='text-align:right;border:1px solid #bbb;background-color:#ddd;padding:3px;'>"
                    . "[<a href='".XOCP_SERVER_SUBDIR."/index.php'>back</a>]"
                    . "</div><div style='border:0px solid #ddd;border-top:0;padding:3px;padding-top:10px;'>$form</div>";
            }
         }
      } 
      
      return $ret;
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
      
      $ret = "<table class='xxlist' style='width:100%;'>"
           . "<thead>"
           . "<tr>"
           . "<td>Employee</td>"
           . "<td>Report</td>"
           . "<td>Submit Date</td>"
           . "<td>&nbsp;</td>"
           . "</tr>"
           . "</thead>"
           . "<tbody>";
      $sql = "SELECT e.event_title,a.employee_id,a.request_id,a.actionplan_id,a.event_id,b.method_t,bt.method_type,d.person_nm,a.submit_dttm,a.status_cd"
           . " FROM ".XOCP_PREFIX."idp_report_TRN_EX a"
           . " LEFT JOIN ".XOCP_PREFIX."idp_request_actionplan b USING(request_id,actionplan_id)"
           . " LEFT JOIN ".XOCP_PREFIX."idp_development_method_type bt USING(method_t)"
           . " LEFT JOIN ".XOCP_PREFIX."employee c ON c.employee_id = a.employee_id"
           . " LEFT JOIN ".XOCP_PREFIX."persons d USING(person_id)"
           . " LEFT JOIN ".XOCP_PREFIX."idp_event e ON e.event_id = a.event_id"
           . " WHERE a.division_mgr_job_id = '$self_job_id'"
           . " AND a.status_cd IN ('approval1','approval2','completed')";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($event_title,$employee_id,$request_id,$actionplan_id,$event_id,$method_t,$method_type,$employee_nm,$submit_dttm,$status_cd)=$db->fetchRow($result)) {
            if($status_cd=="approval1") {
               $link_txt = "Superior Approval";
            } else if($status_cd=="approval2") {
               $link_txt = "Click to Approve";
            } else if($status_cd=="completed") {
               $link_txt = "Completed";
            } else {
               $link_txt = "Click to View";
            }
            
            $link = "<a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&approve=y&request_id=${request_id}&actionplan_id=${actionplan_id}'>$link_txt</a>&nbsp;";
            
            
            $ret .= "<tr>"
                  . "<td>$employee_nm</td>"
                  . "<td>$event_title</td>"
                  . "<td>".sql2ind($submit_dttm,"date")."</td>"
                  . "<td>$link</td>"
                  . "</tr>";
         }
      }
      
      $sql = "SELECT e.event_title,a.employee_id,a.request_id,a.actionplan_id,a.event_id,b.method_t,bt.method_type,d.person_nm,a.submit_dttm,a.status_cd"
           . " FROM ".XOCP_PREFIX."idp_report_TRN_IN a"
           . " LEFT JOIN ".XOCP_PREFIX."idp_request_actionplan b USING(request_id,actionplan_id)"
           . " LEFT JOIN ".XOCP_PREFIX."idp_development_method_type bt USING(method_t)"
           . " LEFT JOIN ".XOCP_PREFIX."employee c ON c.employee_id = a.employee_id"
           . " LEFT JOIN ".XOCP_PREFIX."persons d USING(person_id)"
           . " LEFT JOIN ".XOCP_PREFIX."idp_event e ON e.event_id = a.event_id"
           . " WHERE a.division_mgr_job_id = '$self_job_id'"
           . " AND a.status_cd IN ('approval1','approval2','completed')";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($event_title,$employee_id,$request_id,$actionplan_id,$event_id,$method_t,$method_type,$employee_nm,$submit_dttm,$status_cd)=$db->fetchRow($result)) {
            if($status_cd=="approval1") {
               $link_txt = "Superior Approval";
            } else if($status_cd=="approval2") {
               $link_txt = "Click to Approve";
            } else if($status_cd=="completed") {
               $link_txt = "Completed";
            } else {
               $link_txt = "Click to View";
            }
            
            $link = "<a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&approve=y&request_id=${request_id}&actionplan_id=${actionplan_id}'>$link_txt</a>&nbsp;";
            
            
            $ret .= "<tr>"
                  . "<td>$employee_nm</td>"
                  . "<td>$event_title</td>"
                  . "<td>".sql2ind($submit_dttm,"date")."</td>"
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
            } else if(isset($_GET["approve"])&&$_GET["approve"]=="y") {
               $request_id = $_GET["request_id"];
               $actionplan_id = $_GET["actionplan_id"];
               $ret = $this->approval($request_id,$actionplan_id);
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
             if(isset($_GET["approve"])&&$_GET["approve"]=="y") {
               $request_id = $_GET["request_id"];
               $actionplan_id = $_GET["actionplan_id"];
               $ret = $this->approval($request_id,$actionplan_id);
            } else {
               $ret = $this->requestList();
            }
            break;
      }
      return $ret."<div style='height:100px;'>&nbsp;</div>";
   }
}

} // HRIS_IDPREPORTDIVISIONAPPROVAL_DEFINED
?>