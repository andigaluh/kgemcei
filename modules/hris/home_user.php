<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/home_user.php                              //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-07-18                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_HOMEUSER_DEFINED') ) {
   define('HRIS_HOMEUSER_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/idp/idp.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");

class _hris_HomeUser extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_HOMEUSER_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = FALSE;
   var $title = _HRIS_HOMEUSER_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_HomeUser($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   function add_display_count($notification_id) {
      $db=&Database::getInstance();
      $sql = "UPDATE ".XOCP_PREFIX."idp_notifications SET display_count = display_count + 1, last_display_dttm = now() WHERE notification_id = '$notification_id'";
      $db->query($sql);
   }
   
   function home() {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $person_id_self = getPersonID();
      $agenda_cnt = 0;

      $sql = "SELECT employee_id FROM hris_employee WHERE person_id = $person_id_self";
      $result = $db->query($sql);
      list($employee_id_self)=$db->fetchRow($result);
      
      $_SESSION["my_subordinate_request"] = array();
      $subordinate_list = $this->subordinateList();
	  $curyear = date('Y',time());
      $yearplus = $curyear+1;
	  
      $sql = "SELECT MAX(a.notification_id),UNIX_TIMESTAMP(now()),UNIX_TIMESTAMP(MAX(a.notification_dttm)),a.request_id,a.message_id,"
           . "a.message_txt,a.source_app,a.click_count,a.display_count,MAX(a.notification_dttm),c.person_nm as employee_nm,b.employee_id,"
           . "a.event_id,r.employee_id,f.person_nm,r.request_id"
           . " FROM ".XOCP_PREFIX."idp_notifications a"
           . " LEFT JOIN ".XOCP_PREFIX."idp_request r USING(request_id)"
           . " LEFT JOIN ".XOCP_PREFIX."users d ON d.user_id = a.user_id"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee b ON b.person_id = d.person_id"
           . " LEFT JOIN ".XOCP_PREFIX."employee e ON e.employee_id = r.employee_id"
           . " LEFT JOIN ".XOCP_PREFIX."persons f ON f.person_id = e.person_id"
           . " WHERE a.user_id = '$user_id'"
           . " AND a.is_followed = '0' AND (a.notification_dttm BETWEEN  '$curyear-04-01 00:00:00' AND  '$yearplus-06-30 00:00:00')  "
           . " GROUP BY a.request_id,a.message_id,a.event_id ORDER BY a.notification_dttm DESC"; // ORDER INBOX HERE
      $result = $db->query($sql);
      $req = "";
      
      
      if($db->getRowsNum($result)>0) {
        $req .= "<div style='font-weight:bold;margin:15px 0 5px -10px;'>IDP </div>";
         while(list($notification_id,$now_unixtime,$notification_unixtime,$request_id,$message_id,$message_txt,$source_app,
                    $click_count,$display_count,$notification_dttm,$notification_employee_nm,$notification_employee_id,$event_id,
                    $employee_id,$employee_nm,$ck)=$db->fetchRow($result)) {
            //// clean up deleted request
            if($ck<=0) {
               $sql = "UPDATE ".XOCP_PREFIX."idp_notifications SET is_followed = '1' WHERE notification_id = '$notification_id'";
               $db->query($sql);
               continue;
            }
            
            $report_project_id = $report_actionplan_id = 0;
            
            if(defined($message_id)) {
               $msgfmt = constant($message_id);
            } else {
               $msgfmt = $message_id;
            }
            $msg = sprintf($msgfmt,$employee_nm);
            $dofollow = 0;
            
            switch($message_id) {
               
               ///// EVENT ///////////////////////////////////////////////////////////////
               case "_IDP_EVENTYOUHAVEREGISTRATIONCONFIRMATION":
                  $dofollow = 0;
                  $sql = "SELECT event_title FROM ".XOCP_PREFIX."idp_event WHERE event_id = '$event_id'";
                  $re = $db->query($sql);
                  if($db->getRowsNum($re)>0) {
                     list($event_title)=$db->fetchRow($re);
                     $event_title = "&nbsp;$event_title";
                  } else {
                     $event_title = "&nbsp;Unknown event.";
                  }
                  $newurl = urlencode(urlencode(XOCP_SERVER_SUBDIR."/index.php?XP_idpeventconfirmation_hris="._HRIS_HOMEUSER_BLOCK."&goto=${event_id}&employee_id=${notification_employee_id}"));
                  $req .= "<li><a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&df=${dofollow}&msg=${notification_id}&url=${newurl}'>${msg}${event_title}</a>"
                        . "<div style='color:#888;font-size:0.9em;'>".sql2ind($notification_dttm)."</div></li>";
                  break;
               
               ///// REQUEST /////////////////////////////////////////////////////////////
               case "_IDP_YOURIDPREQUESTAPPROVED1":
               case "_IDP_YOURIDPREQUESTAPPROVED2":
               case "_IDP_YOURIDPREQUESTCOMPLETE":
                  $dofollow = 1;
                  $newurl = urlencode(urlencode(XOCP_SERVER_SUBDIR."/index.php?XP_idprequest_hris="._HRIS_HOMEUSER_BLOCK."&goto=${request_id}"));
                  $req .= "<li><a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&df=${dofollow}&msg=${notification_id}&url=${newurl}'>$msg</a>"
                        . "<div style='color:#888;font-size:0.9em;'>".sql2ind($notification_dttm)."</div></li>";
                  break;
               case "_IDP_YOURIDPREQUESTRETURNED2":
               case "_IDP_YOURIDPREQUESTSTARTED":
               case "_IDP_YOURIDPREQUESTRETURNED":
                  $newurl = urlencode(urlencode(XOCP_SERVER_SUBDIR."/index.php?XP_idprequest_hris="._HRIS_HOMEUSER_BLOCK."&goto=${request_id}"));
                  $req .= "<li><a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&df=${dofollow}&msg=${notification_id}&url=${newurl}'>$msg</a>"
                        . "<div style='color:#888;font-size:0.9em;'>".sql2ind($notification_dttm)."</div></li>";
                  break;
               case "_IDP_YOURSUBORDINATEIDPREQUESTAPPROVED2":
                  $dofollow = 1;
               case "_IDP_YOUHAVEAPPROVAL1":
               case "_IDP_YOURAPPROVAL1RETURNED":
                  $newurl = urlencode(urlencode(XOCP_SERVER_SUBDIR."/index.php?XP_idpsuperiorapprove_hris="._HRIS_IDPSUPERIORAPPROVAL_BLOCK."&req=y&e=${employee_id}&j=1"));
                  $req .= "<li><a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&df=${dofollow}&msg=${notification_id}&url=${newurl}'>$msg</a>"
                        . "<div style='color:#888;font-size:0.9em;'>".sql2ind($notification_dttm)."</div></li>";
                  break;
               case "_IDP_YOUHAVEAPPROVAL2":
                  $newurl = urlencode(urlencode(XOCP_SERVER_SUBDIR."/index.php?XP_idpnextsuperiorapprove_hris="._HRIS_IDPNEXTSUPERIORAPPROVAL_BLOCK."&req=y&e=${employee_id}&j=1"));
                  $req .= "<li><a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&df=${dofollow}&msg=${notification_id}&url=${newurl}'>$msg</a>"
                        . "<div style='color:#888;font-size:0.9em;'>".sql2ind($notification_dttm)."</div></li>";
                  break;
               case "_IDP_YOUHAVEHRAPPROVAL":
                  $sql = "SELECT status_cd FROM ".XOCP_PREFIX."idp_request WHERE request_id = '$request_id'";
                  $rs = $db->query($sql);
                  if($db->getRowsNum($rs)>0) {
                     list($ck_status_cd)=$db->fetchRow($rs);
                     if($ck_status_cd=="approval3") {
                        $newurl = urlencode(urlencode(XOCP_SERVER_SUBDIR."/index.php?XP_idphrapprove_hris="._HRIS_IDPHRAPPROVAL_BLOCK."&req=y&e=${employee_id}&j=1"));
                        $req .= "<li><a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&df=${dofollow}&msg=${notification_id}&url=${newurl}'>$msg</a>"
                              . "<div style='color:#888;font-size:0.9em;'>".sql2ind($notification_dttm)."</div></li>";
                     } else {
                        $sql = "UPDATE ".XOCP_PREFIX."idp_notifications SET is_followed = '1' WHERE notification_id = '$notification_id'";
                        $db->query($sql);
                     }
                  } else {
                     $sql = "UPDATE ".XOCP_PREFIX."idp_notifications SET is_followed = '1' WHERE notification_id = '$notification_id'";
                     $db->query($sql);
                  }
                  break;
               
               ///// REPORT /////////////////////////////////////////////////////////////
               case "_IDP_YOURAPPROVAL1REPORTRETURNED":
               case "_IDP_YOUHAVEAPPROVAL1REPORT":
                  eval($message_txt);
                  if($report_project_id>0) {
                     $newurl = urlencode(urlencode(XOCP_SERVER_SUBDIR."/index.php?XP_idpreportapprovalsection_hris="._HRIS_IDPREPORTSECTIONAPPROVAL_BLOCK."&approve_project=y&request_id=${report_request_id}&project_id=${report_project_id}"));
                     $req .= "<li><a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&df=${dofollow}&msg=${notification_id}&url=${newurl}'>$msg</a>"
                           . "<div style='color:#888;font-size:0.9em;'>".sql2ind($notification_dttm)."</div></li>";
                  } else {
                     $newurl = urlencode(urlencode(XOCP_SERVER_SUBDIR."/index.php?XP_idpreportapprovalsection_hris="._HRIS_IDPREPORTSECTIONAPPROVAL_BLOCK."&approve=y&request_id=${report_request_id}&actionplan_id=${report_actionplan_id}"));
                     $req .= "<li><a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&df=${dofollow}&msg=${notification_id}&url=${newurl}'>$msg</a>"
                           . "<div style='color:#888;font-size:0.9em;'>".sql2ind($notification_dttm)."</div></li>";
                  }
                  break;
               case "_IDP_YOUHAVEDMREPORTNOTIFICATION":
                  $dofollow = 1;
               case "_IDP_YOUHAVEAPPROVAL2REPORT":
                  eval($message_txt);
                  $newurl = urlencode(urlencode(XOCP_SERVER_SUBDIR."/index.php?XP_idpreportapprovaldivision_hris="._HRIS_IDPREPORTDIVISIONAPPROVAL_BLOCK."&approve=y&request_id=${report_request_id}&actionplan_id=${report_actionplan_id}"));
                  $req .= "<li><a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&df=${dofollow}&msg=${notification_id}&url=${newurl}'>$msg</a>"
                        . "<div style='color:#888;font-size:0.9em;'>".sql2ind($notification_dttm)."</div></li>";
                  break;
               case "_IDP_YOURREPORTHASBEENCOMPLETED":
                  $dofollow = 1;
               case "_IDP_YOURREPORTRETURNEDBYDM":
               case "_IDP_YOURREPORTRETURNED":
                  eval($message_txt);
                  if($report_project_id>0) {
                     $newurl = urlencode(urlencode(XOCP_SERVER_SUBDIR."/index.php?XP_idpmyreport_hris="._HRIS_IDPMYREPORT_BLOCK."&reporting_project=y&request_id=${report_request_id}&project_id=${report_project_id}"));
                     $req .= "<li><a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&df=${dofollow}&msg=${notification_id}&url=${newurl}'>$msg</a>"
                           . "<div style='color:#888;font-size:0.9em;'>".sql2ind($notification_dttm)."</div></li>";
                  } else {
                     $newurl = urlencode(urlencode(XOCP_SERVER_SUBDIR."/index.php?XP_idpmyreport_hris="._HRIS_IDPMYREPORT_BLOCK."&reporting=y&request_id=${report_request_id}&actionplan_id=${report_actionplan_id}"));
                     $req .= "<li><a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&df=${dofollow}&msg=${notification_id}&url=${newurl}'>$msg</a>"
                           . "<div style='color:#888;font-size:0.9em;'>".sql2ind($notification_dttm)."</div></li>";
                  }
                  break;
               default:
                  break;
            }
            $agenda_cnt++;
            $this->add_display_count($notification_id);
         }
      }
      
      $sql = "SELECT a.request_id,a.employee_id,a.status_cd,c.person_nm,a.created_dttm FROM ".XOCP_PREFIX."idp_request a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " WHERE a.created_user_id = '$user_id' AND a.status_cd = 'start' AND (a.notification_dttm BETWEEN  '$curyear-04-01 00:00:00' AND  '$yearplus-06-30 00:00:00')  ORDER BY a.created_dttm DESC";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
        $req .= "<div style='font-weight:bold;margin:15px 0 5px -10px;'>IDP</div>";
         while(list($request_id,$employee_id,$rstatus_cd,$employee_nm,$created_dttm)=$db->fetchRow($result)) {
            $message_id = "_IDP_IDPCREATEDNOTSTARTED";
            if(defined($message_id)) {
               $msgfmt = constant($message_id);
            } else {
               $msgfmt = $message_id;
            }
            $msg = sprintf($msgfmt,$employee_nm);
            $newurl = urlencode(urlencode(XOCP_SERVER_SUBDIR."/index.php?XP_idpreviewtool_hris="._HRIS_IDPREVIEWTOOL_BLOCK."&req=y&e=${employee_id}&j=1"));
            $req .= "<li><a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&df=${dofollow}&msg=0&url=${newurl}'>$msg</a>"
                  . "<div style='color:#888;font-size:0.9em;'>".sql2ind($created_dttm)."</div></li>";
            $agenda_cnt++;
         
         }
      }
      
      ///////////////////// PMS ALERT /////////////////////////////////////////
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
      
      ///////////////////// PMS ACTION PLAN RETURN //////////////////////////////////////
      $sql = "SELECT a.employee_id,c.person_nm,MAX(a.submit_dttm)"
           . " FROM pms_pic_action a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " WHERE a.employee_id = '$self_employee_id'"
           . " AND a.approval_st = 'return' AND (a.submit_dttm BETWEEN  '$curyear-04-01 00:00:00' AND  '$yearplus-06-30 00:00:00') "
           . " GROUP BY a.employee_id ORDER BY a.submit_dttm DESC";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
        $req .= "<div style='font-weight:bold;margin:15px 0 5px -10px;'>PMS Action Plan Return</div>";
         while(list($req_employee_id,$req_employee_nm,$req_submit_dttm)=$db->fetchRow($result)) {
            $newurl = urlencode(urlencode(XOCP_SERVER_SUBDIR."/index.php?XP_pmsmyactionplan_hris=0"));
            $req .= "<li><a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&df=0&msgpms=asdf&url=${newurl}'>Your PMS Action Plan is returned by Superior.</a>"
                  . "<div style='color:#888;font-size:0.9em;'>".sql2ind($req_submit_dttm)."</div></li>";
            $agenda_cnt++;
         }
      }
      
      ///////////////////// PMS ACTION PLAN APPROVAL 1 //////////////////////////////////////
      $sql = "SELECT a.employee_id,c.person_nm,MAX(a.submit_dttm)"
           . " FROM pms_pic_action a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " WHERE a.approval1_employee_id = '$self_employee_id'"
           . " AND a.approval_st = 'approval1'"
           . " AND a.is_pica = '0' AND (a.submit_dttm BETWEEN  '$curyear-04-01 00:00:00' AND  '$yearplus-06-30 00:00:00') "
           . " GROUP BY a.employee_id ORDER BY a.submit_dttm DESC";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         $req .= "<div style='font-weight:bold;margin:15px 0 5px -10px;'>PMS Action Plan Approval</div>";
         while(list($req_employee_id,$req_employee_nm,$req_submit_dttm)=$db->fetchRow($result)) {
            $newurl = urlencode(urlencode(XOCP_SERVER_SUBDIR."/index.php?XP_pmsapapproval_hris="._HRIS_HOMEUSER_BLOCK."&employee_id=${req_employee_id}&goto=y"));
            $req .= "<li><a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&df=0&msgpms=asdf&url=${newurl}'>You have PMS action plan approval for: $req_employee_nm</a>"
                  . "<div style='color:#888;font-size:0.9em;'>".sql2ind($req_submit_dttm)."</div></li>";
            $agenda_cnt++;
         }
      }
      
      ///////////////////// PMS ACTION PLAN REPORT APPROVAL //////////////////////////////////////
      $sql = "SELECT a.employee_id,c.person_nm,MAX(a.submit_dttm),a.month_id"
           . " FROM pms_pic_action a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " WHERE a.approval1_employee_id = '$self_employee_id'"
           . " AND a.report_approval_st = 'approval' AND (a.submit_dttm BETWEEN  '$curyear-04-01 00:00:00' AND  '$yearplus-06-30 00:00:00')"
           //. " AND a.is_pica = '0'"
           . " GROUP BY a.employee_id,a.month_id ORDER BY a.submit_dttm DESC";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
        $req .= "<div style='font-weight:bold;margin:15px 0 5px -10px;'>PMS Action Plan Report Approval</div>";
         while(list($req_employee_id,$req_employee_nm,$req_submit_dttm,$month_id)=$db->fetchRow($result)) {
            $newurl = urlencode(urlencode(XOCP_SERVER_SUBDIR."/index.php?XP_pmsapreportapproval_hris="._HRIS_HOMEUSER_BLOCK."&employee_id=${req_employee_id}&goto=y&month_id=${month_id}"));
            $req .= "<li><a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&df=0&msgpms=asdf&url=${newurl}'>You have PMS action plan report approval for: $req_employee_nm</a>"
                  . "<div style='color:#888;font-size:0.9em;'>".sql2ind($req_submit_dttm)."</div></li>";
            $agenda_cnt++;
         }
      }
      
      ///////////////////// JAM APPROVAL 1 //////////////////////////////////////
      $sql = "SELECT a.employee_id,c.person_nm,a.submit_dttm,a.jam_org_ind,a.org_id"
           . " FROM pms_jam a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " WHERE a.approval1_employee_id = '$self_employee_id'"
           . " AND a.approval_st = 'approval1' AND (a.submit_dttm BETWEEN  '$curyear-04-01 00:00:00' AND  '$yearplus-06-30 00:00:00')"
           . " GROUP BY a.employee_id,a.jam_org_ind,a.org_id ORDER BY a.submit_dttm DESC";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
        $req .= "<div style='font-weight:bold;margin:15px 0 5px -10px;'>JAM Approval 1</div>";
         while(list($req_employee_id,$req_employee_nm,$req_submit_dttm,$jam_org_ind,$org_idx)=$db->fetchRow($result)) {
            $newurl = urlencode(urlencode(XOCP_SERVER_SUBDIR."/index.php?XP_pmsjamapproval_hris="._HRIS_HOMEUSER_BLOCK."&employee_id=${req_employee_id}&goto=y&jam_org_ind=${jam_org_ind}&jam_org_id=${org_idx}"));
            if($jam_org_ind==1) {
               $sql = "SELECT a.org_nm,b.org_class_nm FROM ".XOCP_PREFIX."orgs a LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id) WHERE a.org_id = '$org_idx'";
               $ro = $db->query($sql);
               list($org_nmx,$org_class_nmx)=$db->fetchRow($ro);
               $jam_org_nm = " / ".trim("$org_nmx $org_class_nmx");
            } else {
               $jam_org_nm = "";
            }
            $req .= "<li><a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&df=0&msgpms=asdf&url=${newurl}'>You have JAM approval request from: $req_employee_nm$jam_org_nm</a>"
                  . "<div style='color:#888;font-size:0.9em;'>".sql2ind($req_submit_dttm)."</div></li>";
            $agenda_cnt++;
         }
      }
      
      ///////////////////// JAM APPROVAL 2 //////////////////////////////////////
      $sql = "SELECT a.employee_id,c.person_nm,a.submit_dttm,a.jam_org_ind,a.org_id"
           . " FROM pms_jam a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " WHERE a.approval2_employee_id = '$self_employee_id'"
           . " AND a.approval_st = 'approval2' AND (a.submit_dttm BETWEEN  '$curyear-04-01 00:00:00' AND  '$yearplus-06-30 00:00:00')"
           . " GROUP BY a.employee_id,a.jam_org_ind,a.org_id ORDER BY a.submit_dttm DESC";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
        $req .= "<div style='font-weight:bold;margin:15px 0 5px -10px;'>JAM Approval 2</div>";
         while(list($req_employee_id,$req_employee_nm,$req_submit_dttm,$jam_org_ind,$org_idx)=$db->fetchRow($result)) {
            $newurl = urlencode(urlencode(XOCP_SERVER_SUBDIR."/index.php?XP_pmsjamapproval_hris="._HRIS_HOMEUSER_BLOCK."&employee_id=${req_employee_id}&goto=y&jam_org_ind=${jam_org_ind}&jam_org_id=${org_idx}"));
            if($jam_org_ind==1) {
               $sql = "SELECT a.org_nm,b.org_class_nm FROM ".XOCP_PREFIX."orgs a LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id) WHERE a.org_id = '$org_idx'";
               $ro = $db->query($sql);
               list($org_nmx,$org_class_nmx)=$db->fetchRow($ro);
               $jam_org_nm = " / ".trim("$org_nmx $org_class_nmx");
            } else {
               $jam_org_nm = "";
            }
            $req .= "<li><a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&df=0&msgpms=asdf&url=${newurl}'>You have JAM approval request from: $req_employee_nm$jam_org_nm</a>"
                  . "<div style='color:#888;font-size:0.9em;'>".sql2ind($req_submit_dttm)."</div></li>";
            $agenda_cnt++;
         }
      }
      
      ///////////////////// JAM RETURN //////////////////////////////////////
      $sql = "SELECT a.employee_id,c.person_nm,a.submit_dttm,a.jam_org_ind,a.org_id"
           . " FROM pms_jam a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " WHERE a.employee_id = '$self_employee_id'"
           . " AND a.approval_st = 'return' AND (a.submit_dttm BETWEEN  '$curyear-04-01 00:00:00' AND  '$yearplus-06-30 00:00:00')"
           . " GROUP BY a.employee_id,a.jam_org_ind,a.org_id ORDER BY a.submit_dttm DESC";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
        $req .= "<div style='font-weight:bold;margin:15px 0 5px -10px;'>JAM Return</div>";
         while(list($req_employee_id,$req_employee_nm,$req_submit_dttm,$jam_org_ind,$org_idx)=$db->fetchRow($result)) {
            $newurl = urlencode(urlencode(XOCP_SERVER_SUBDIR."/index.php?XP_pmsjam".($jam_org_ind==1?"org":"")."_hris="._HRIS_HOMEUSER_BLOCK."&employee_id=${req_employee_id}&goto=y"));
            $req .= "<li><a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&df=0&msgpms=asdf&url=${newurl}'>Your JAM is returned / not approved by your superior.</a>"
                  . "<div style='color:#888;font-size:0.9em;'>".sql2ind($req_submit_dttm)."</div></li>";
            $agenda_cnt++;
         }
      }
      
      ///////////////////// new assessment for completed //////////////////////
      /*
      foreach($_SESSION["my_subordinate_request"] as $request_idx=>$vv) {
         list($employee_idx,$request_idx,$status_cdx)=$vv;
         if($status_cdx=="completed") {
            $req .= "<li><a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&df=${dofollow}&msg=0&url=${newurl}'>$msg</a>"
                  . "<div style='color:#888;font-size:0.9em;'>".sql2ind($created_dttm)."</div></li>";
            $agenda_cnt++;
         
         }
      }
      */
      ////////////////////////////////////////////////////////
      
      $idp_progress = "";
      
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
      
      
      
      $sql = "SELECT request_id,employee_id,approve_superior_id,approve_superior_dttm,approve_hris_id,approve_hris_dttm,cost_estimate,requested_dttm,status_cd"
           . " FROM ".XOCP_PREFIX."idp_request"
           . " WHERE employee_id = '$self_employee_id'"
           . " ORDER BY requested_dttm";
      $rreq = $db->query($sql);
      if($db->getRowsNum($rreq)>0) {
         
         
                  while(list($request_id,$employee_idx,$approve_superior_id,$approve_superior_dttm,$approve_hris_id,$approve_hris_dttm,$cost_estimate,$requested_dttm,$status_cd)=$db->fetchRow($rreq)) {
                     if($status_cd=="rejected") continue;
                     if($status_cd=="nullified") continue;
                     if($status_cd=="completed") continue;
                     
                     $sql = "SELECT COUNT(*) FROM ".XOCP_PREFIX."idp_request_actionplan WHERE request_id = '$request_id' AND status_cd NOT IN ('rejected','nullified')";
                     $rc = $db->query($sql);
                     if($db->getRowsNum($rc)==1) {
                        list($cntaap)=$db->fetchRow($rc);
                     } else {
                        $cntaap = 0;
                     }
                     $sql = "SELECT COUNT(*) FROM ".XOCP_PREFIX."idp_request_actionplan WHERE request_id = '$request_id' AND status_cd = 'completed'";
                     $rc = $db->query($sql);
                     if($db->getRowsNum($rc)==1) {
                        list($cntaapc)=$db->fetchRow($rc);
                     } else {
                        $cntaapc = 0;
                     }
                     switch($status_cd) {
                        case "start":
                           $req_status = "New Request";
                           break;
                        case "employee":
                           $req_status = "Employee Request";
                           break;
                        case "approval1":
                           $req_status = "Superior Approval";
                           break;
                        case "approval2":
                           $req_status = "Next Superior Approval";
                           break;
                        case "approval3":
                           $req_status = "HR Approval";
                           break;
                        case "implementation":
                           $req_status = "Implementation";
                           break;
                        case "completed":
                           $req_status = "Completed";
                           break;
                        default:
                           break;
                     }
                     
                     if($cntaap>0) {
                        $progress_qty = ceil(bcmul(100,bcdiv($cntaapc,$cntaap)));
                        if($progress_qty>100) $progress_qty = 100;
                        $progress_qty_txt = toMoneyShort($progress_qty)."%";
                     } else {
                        $progress_qty_txt = "0%";
                        $progress_qty = 0;
                     }
                     
                     list($timeframe_start,$timeframe_stop)=_idp_get_timeframe($request_id);
                     
                     $sql = "SELECT TO_DAYS(now()),TO_DAYS('$timeframe_start'),TO_DAYS('$timeframe_stop')";
                     $resultx = $db->query($sql);
                     list($now,$start,$stop)=$db->fetchRow($resultx);
                     if($now<=$start) {
                        $progress_time_txt = "0%";
                     } else {
                        $p = $now-$start;
                        $q = $stop-$start;
                        if($q>0) {
                           $progress_time = 100*($p/$q);
                        } else {
                           $progress_time = 0;
                        }
                        if($progress_time>100) $progress_time = 100;
                        $progress_time_txt = toMoneyShort($progress_time)."%";
                     }
                     
                     
                     $idp_progress .= "<table align='center'><tbody><tr><td style='font-size:0.9em;'>"
                                   . "Elapsed Time:"
                                   . "</td></tr><tr><td>"
                                   . "<div style='float:left;overflow:hidden;width:50px !important;border:1px solid #999999;margin:auto;text-align:left;'>"
                                   . "<img src='".XOCP_SERVER_SUBDIR."/modules/hris/images/level_foreground.png' style='width:50px;height:12px;margin-left:".((int)(-50+($progress_time/2)))."px;'/>"
                                   . "</div>"
                                   . "<div style='float:left;padding-left:3px;'>$progress_time_txt</div>"
                                   . "</td></tr><tr><td style='font-size:0.9em;'>"
                                   . "Progress:"
                                   . "</td></tr><tr><td>"
                                   . "<div style='float:left;overflow:hidden;width:50px !important;border:1px solid #999999;margin:auto;text-align:left;'>"
                                   . "<img src='".XOCP_SERVER_SUBDIR."/modules/hris/images/level_foreground.png' style='width:50px;height:12px;margin-left:".((int)(-50+($progress_qty/2)))."px;'/>"
                                   . "</div>"
                                   . "<div style='float:left;padding-left:3px;'>$progress_qty_txt</div>"
                                   . "</td></tr></tbody></table>";
                  }
             
      }

      /////////////////////PROFILE PICTURE//////////////////////////////////


      
      $motd = "<div style='padding:5px;border:1px solid #B0A94F;background-color:#C0B95F;-moz-border-radius:5px 5px 0 0;text-align:center;color: #ffffff'>Quick Links</div>"
            . "<div style='border:1px solid #bbb;border-top:0px;-moz-border-radius:0 0 5px 5px;padding:5px;'>"
            
            . "<div class='motd_box' style='background-color:#FCF59B;'>"
               . "<a href='".XOCP_SERVER_SUBDIR."/index.php?XP_assessment_menu=0'>"
               . "<img src='".XOCP_SERVER_SUBDIR."/images/home/icon_assessment.jpg'/>"
               . "<div>Assessment</div>"
               . "</a>"
            . "</div>"
            
            . "<div class='motd_box' style='background-color:#FCF59B;'>"
               . "<a href='".XOCP_SERVER_SUBDIR."/index.php?XP_idprequest_menu=0'>"
               . "<img src='".XOCP_SERVER_SUBDIR."/images/home/icon_my_idp.png'/>"
               . $idp_progress
               . "<div>My IDP</div>"
               . "</a>"
            . "</div>"
            
            . "</div>";
      
      $inbox = "<div style='padding:5px;border:1px solid #bbb;border-bottom:0px;background-color:#C0B95F;-moz-border-radius:5px 5px 0 0;text-align:center;color: #000000;border-color: #B0A94F;color:#ffffff'>Inbox ($agenda_cnt messages)</div>"
            . "<div style='border:1px solid #bbb;-moz-border-radius:0 0 5px 5px;padding:0px;min-height:158px;'>"
            . ($agenda_cnt==0?"<div style='padding:5px;text-align:center;font-style:italic;'>"._EMPTY."</div>":"<ul>".$req."</ul>")
            . "</div>";

      $sql = "SELECT a.job_nm,e.org_nm,e.parent_id FROM hris_jobs a" 
           . " LEFT JOIN hris_employee_job b ON b.job_id = a.job_id"
           . " LEFT JOIN hris_orgs e ON a.org_id = e.org_id"
           . " WHERE b.employee_id = $employee_id_self";
           
      $result = $db->query($sql);
      list($job_nm,$org_nm,$parent_id)=$db->fetchRow($result);

      $sql = "SELECT org_nm FROM hris_orgs WHERE org_id = $parent_id";
      $result = $db->query($sql);
      list($org_nmx)=$db->fetchRow($result);

      $ret = "<div style='background: url(./modules/hris/images/picture_cover_1.jpg) no-repeat scroll 0 0 rgba(0, 0, 0, 0);width:100%;height:200px;margin-bottom:70px;background-color:#eeeeee;border:1px solid #cccccc;'>"
           . "<div id='img_thumb_div' style='min-height:120px;padding:91px 0 0 19px;/*background-color:#eeeeee;border:1px solid #cccccc;*/'>"
           . "<div style='background-color:#dddddd;height: 165px;width: 403px;'>"
           . "<img style='width:100px;border:2px solid #eeeeee;float:left;margin:15px 10px 0 10px;' src='".XOCP_SERVER_SUBDIR."/modules/hris/thumb.php?pid=".getPersonID()."'/>"
           . "<div style='float:left;margin:35px 0 0 0;line-height: 10px;width:269px;'>"
           . "<table cellspacing='0px' cellpadding='2px' style='line-height:12px;'>"
           . "<tbody>"
           . "<tr>"
           . "<td style='vertical-align:top;border-bottom:1px solid #777777;'>Name</td>"
           . "<td style='vertical-align:top;border-bottom:1px solid #777777;'>:</td>"
           . "<td style='border-bottom:1px solid #777777;width:165px;'>".getUserFullname()."</td>"
           . "</tr>"
           . "<tr>"
           . "<td style='vertical-align:top;border-bottom:1px solid #777777;'>Job Title</td>"
           . "<td style='vertical-align:top;border-bottom:1px solid #777777;'>:</td>"
           . "<td style='border-bottom:1px solid #777777;width:165px;'>".$job_nm."</td>"
           . "</tr>"
           . "<tr>"
           . "<td style='vertical-align:top;'>Division/Section</td>"
           . "<td style='vertical-align:top;'>:</td>"
           . "<td style='width:165px;'>".$org_nmx.($org_nm==""?"":"/").$org_nm."</td>"
           . "</tr>"
           . "<tr><td colspan='3' style='border-bottom:1px solid #777777;'></tr>"
           /*. "<span style='float:left'>Name : ".getUserFullname()."</span><br>"
           . "<span style='margin-top:10px;float:left;'>Job Title : ".$job_nm."</span>"
           . "<span style='margin-top:10px;float:left;'>Job Title : ".$job_nm."</span>"*/
           . "</tbody>"
           . "</table>"
           . "</div>"
           . "</div>"
           . "</div>"
           . "</div>"
           . "<div style='background-color: #FF8C00;border: 1px solid #B0A94F;border-radius: 5px;box-shadow: 0 1px 2px #000000;clear: both;padding: 0;'>"
           . "<img style='margin-left:25px;margin-top:-10px;position:absolute;z-index:100;' src='".XOCP_SERVER_SUBDIR."/images/paperclip.png'>"
           . "<div style='padding: 20px;'>"
           . "<div style='background-color: #FCF59B;border-radius: 500px 5px 100px 10px / 5px 100px 10px 500px;box-shadow: 1px 1px 3px #000000;padding: 60px 20px 20px;position: relative;'>"
           . "<h4 style='width:400px;float:left;'>Welcome, ".getUserFullname()."</h4>
              <h4 style='width:200px;float:right;'>
              <script type='text/javascript'><!--  
              //membuat variabel bertipe array untuk nama hari
              var NamaHari = new Array(\"Sunday\", \"Monday\", \"Tuesday\", \"Wednesday\", \"Thursday\", \"Friday\",
              \"Saturday\");
              //membuat variabel bertipe array untuk nama bulan
              var NamaBulan = new Array(\"January\", \"February\", \"March\", \"April\", \"May\",
              \"June\", \"July\", \"August\", \"September\", \"October\", \"November\", \"December\");
              var sekarang = new Date();
              var HariIni = NamaHari[sekarang.getDay()];
              var BulanIni = NamaBulan[sekarang.getMonth()];
              var tglSekarang = sekarang.getDate();
              var TahunIni = sekarang.getFullYear();
              document.write(HariIni + ', ' + tglSekarang + ' ' + BulanIni + ' ' + TahunIni);
              //--></script></h4>"
           . "<table style='width:100%;border-spacing:0px;'><colgroup><col width='75%'/><col width='25%'/></colgroup>"
           . "<tbody><tr><td style='vertical-align:top;padding:5px;padding-left:0px;'>$inbox</td><td style='vertical-align:top;padding:5px;'>$motd</td></tr>"
           . "</tbody></table>"
           ."</div></div></div>";
      return $ret.$subordinate_list."<div style='padding:30px;'></div>";
   }
   
   
   function subordinateList() {
      return;
      $db=&Database::getInstance();
      $user_id = getUserID();
      $assessor_job_count = 0;
      global $proficiency_level_name;
      
      $_SESSION["html"]->js_scriptaculous_effecs=TRUE;
      
      require_once(XOCP_DOC_ROOT."/modules/hris/include/assessment.php");
      
      $asid = _get_last_asid();
      
      
      $arr_compgroup = array();
      
      $sql = "SELECT compgroup_id,compgroup_nm,competency_class_set FROM ".XOCP_PREFIX."compgroup"
           . " ORDER BY compgroup_id";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($compgroup_id,$compgroup_nm,$cset)=$db->fetchRow($result)) {
            $arr_compgroup[$compgroup_id] = array($compgroup_nm,explode(",",$cset));
         }
      }
      
      $person_info = "";
      $tooltips = "";
      
      $sql = "SELECT c.job_id,b.employee_id"
           . " FROM ".XOCP_PREFIX."users a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(person_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job c USING(employee_id)"
           . " WHERE a.user_id = '$user_id'";
      $result = $db->query($sql);
      $ret = "";
      $employee_list = "";
      if($db->getRowsNum($result)>0) {
         while(list($assessor_job_id,$self_employee_id)=$db->fetchRow($result)) {
            $_SESSION["self_employee_id"] = $self_employee_id;
            if($assessor_job_id==0) continue;
            $assessor_job_count++;
            $sql = "SELECT a.job_id,a.job_nm,a.job_cd,a.job_abbr,a.org_id,a.description,a.summary,b.job_level"
                 . " FROM ".XOCP_PREFIX."jobs a"
                 . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
                 . " WHERE a.assessor_job_id = '$assessor_job_id'"
                 . " ORDER BY a.job_class_id";
            $res = $db->query($sql);
            $no = 0;
            if($db->getRowsNum($res)>0) {
               while($rrow=$db->fetchRow($res)) {
                  list($job_id,$job_nm,$job_cd,$job_abbr,$org_id,$job_descx,$job_summary,$job_level)=$rrow;
                  $job_summary = str_replace("\n","",$job_summary);
                  $job_summary = addslashes($job_summary);
                  
                  $sql = "SELECT a.employee_id,b.employee_ext_id,c.person_nm,a.gradeval,c.birth_dttm,c.birthplace,"
                       . "c.adm_gender_cd,c.addr_txt,c.cell_phone,c.home_phone,c.marital_st,"
                       . "b.entrance_dttm,a.start_dttm,a.stop_dttm,(TO_DAYS(now())-TO_DAYS(b.entrance_dttm)) as jobage,"
                       . "c.person_id"
                       . " FROM ".XOCP_PREFIX."employee_job a"
                       . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
                       . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
                       . " WHERE a.job_id = '$job_id'";
                  $res2 = $db->query($sql);
                  if($db->getRowsNum($res2)>0) {
                     while(list($employee_id,$nip,$employee_nm,$gradeval,$dob,$pob,$gender,$addr,$cellphone,$phone,$marital,
                                $entrance_dttm,$jobstart,$jobstop,$jobage,$person_id)=$db->fetchRow($res2)) {
                        
                        $sql = "SELECT c.job_nm,c.job_abbr,d.org_nm,d.org_abbr,a.employee_ext_id,e.person_nm,e.person_id"
                             . " FROM ".XOCP_PREFIX."employee a"
                             . " LEFT JOIN ".XOCP_PREFIX."employee_job b ON b.employee_id = a.employee_id AND b.job_id = '$job_id'"
                             . " LEFT JOIN ".XOCP_PREFIX."jobs c ON c.job_id = '$job_id'"
                             . " LEFT JOIN ".XOCP_PREFIX."orgs d ON d.org_id = c.org_id"
                             . " LEFT JOIN ".XOCP_PREFIX."persons e ON e.person_id = a.person_id"
                             . " WHERE a.employee_id = '$employee_id'";
                        $res_emp = $db->query($sql);
                        list($job_nm,$job_abbr,$org_nm,$org_abbr,$nip,$employee_nm,$person_id)=$db->fetchRow($res_emp);
                        
                        
                        $person_info = "<table style='width:100%;'><tbody><tr><td style='vertical-align:top;'><img src='".XOCP_SERVER_SUBDIR."/modules/hris/thumb.php?pid=$person_id' height='100'/></td>"
                                     . "<td style='vertical-align:top;'><table class='emp_info' style='margin-top:0px;width:290px;'>"
                                     . "<colgroup><col width='80'/><col/></colgroup>"
                                     . "<tbody>"
                                     . "<tr><td>Employee ID :</td><td>$nip</td></tr>"
                                     . "<tr><td>Entrance Date :</td><td>".sql2ind($entrance_dttm,"date")." ("._bctrim(toMoney($jobage/365.25))." year)</td></tr>"
                                     . "<tr><td>Job Assigned :</td><td>".sql2ind($jobstart,"date")."</td></tr>"
                                     . "<tr><td>Gender :</td><td>".($gender=="f"?"Female":"Male")."</td></tr>"
                                     . "<tr><td>Previous Job :</td><td></td></tr></tbody></table></td></tr></tbody></table>";
                        $tooltips .= "\nnew Tip('empjob_${employee_id}_${job_id}', '<div style=\"font-weight:bold;\">Job Summary:</div>$job_summary', {viewport:true,title:'$job_nm',style:'emp'});";
                        $tooltips .= "\nnew Tip('emp_${employee_id}_${job_id}', \"$person_info\", {title:'$employee_nm',width:350,style:'emp'});";
                        
                        
                        
                        ////////////////////////////////////////////////////////////////////
                        ////////////////////////////////////////////////////////////////////
                        
                        $sql = "SELECT pr_session_id,pr_session_nm FROM ".XOCP_PREFIX."pr_session ORDER BY pr_session_nm DESC LIMIT 1";
                        $rpr = $db->query($sql);
                        if($db->getRowsNum($rpr)>0) {
                           list($pr_session_id,$pr_session_nm)=$db->fetchRow($rpr);
                           $sql = "SELECT pr_value FROM ".XOCP_PREFIX."pr_result WHERE pr_session_id = '$pr_session_id' AND employee_id = '$employee_id'";
                           $rpr = $db->query($sql);
                           if($db->getRowsNum($rpr)>0) {
                              list($pr_value)=$db->fetchRow($rpr);
                           } else {
                              $pr_value = 0;
                           }
                        } else {
                           $pr_value = 0;
                        }
                        
                        $ccl = 0;
                        $ttlccl = 0;
                        $ttlrcl = 0;
                        $cf_compgroup = array();
                        $cf_pass = array();
                        
                        $sql = "SELECT a.competency_id,a.rcl,a.itj,b.competency_nm,c.compgroup_nm,b.competency_class,d.ccl,(b.competency_class+0) as urcl,"
                             . "f.person_nm,b.desc_en,b.desc_id,b.compgroup_id"
                             . " FROM ".XOCP_PREFIX."job_competency a"
                             . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
                             . " LEFT JOIN ".XOCP_PREFIX."compgroup  c USING(compgroup_id)"
                             . " LEFT JOIN ".XOCP_PREFIX."employee_competency d ON d.employee_id = '$employee_id' AND d.competency_id = b.competency_id"
                             . " LEFT JOIN ".XOCP_PREFIX."employee e ON e.employee_id = d.assessor_id"
                             . " LEFT JOIN ".XOCP_PREFIX."persons f ON f.person_id = e.person_id"
                             . " WHERE a.job_id = '$job_id'"
                             . " ORDER BY b.compgroup_id,urcl";
                        $result = $db->query($sql);
                        $oldcompgroup = "";
                        $oldcompgroup_id = "";
                        $ccl = 0;
                        
                        if($db->getRowsNum($result)>0) {
                           while(list($competency_id,$rcl,$itj,$competency_nm,$compgroup_nm,$cc,$ccl,$urcl,$asr_nm,$desc_en,$desc_id,$compgroup_id)=$db->fetchRow($result)) {
                              
                              /// competency fit
                              if($compgroup_id==1||$compgroup_id==2) {
                                 $cf_compgroup[$compgroup_id][$competency_id] = array($competency_id,$competency_nm,$compgroup_nm);
                              }
                              
                              $cc = ucfirst($cc);
                              $ccl = $ccl+0;
                              $arrccl = array();
                              $arrccl["superior"] = $ccl;
                              $asrlist = "<table class=\"asrdtl\" style=\"width:100%;\"><thead><tr><td>Assessor</td><td>Type</td><td>CCL</td></tr></thead>"
                                        . "<tbody><tr><td>$asr_nm</td><td>Superior</td><td>$ccl</td></tr>";
                              //// 360
                              $sql = "SELECT a.ccl,a.assessor_id,c.person_nm,d.assessor_t FROM ".XOCP_PREFIX."employee_competency360 a"
                                   . " LEFT JOIN ".XOCP_PREFIX."employee b ON b.employee_id = a.assessor_id"
                                   . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
                                   . " LEFT JOIN ".XOCP_PREFIX."assessor_360 d ON d.asid = '$asid'"
                                   . " AND d.employee_id = a.employee_id AND d.assessor_id = a.assessor_id"
                                   . " AND d.status_cd = 'active'"
                                   . " WHERE a.employee_id = '$employee_id'"
                                   . " AND a.competency_id = '$competency_id'"
                                   . " AND d.asid = '$asid'"
                                   . " ORDER BY a.ccl DESC";
                              $r360 = $db->query($sql);
                              if($db->getRowsNum($r360)>0) {
                                 while(list($ccl360,$asr360_id,$asr360_nm,$assessor_t)=$db->fetchRow($r360)) {
                                    if($assessor_t=="superior") continue;
                                    $ccl360 = $ccl360+0;
                                    $arrccl[$asr360_id] = $ccl360;
                                    $asrlist .= "<tr><td>$asr360_nm</td><td>$assessor_t</td><td>$ccl360</td></tr>";
                                 }
                              }
                              
                              arsort($arrccl);
                              $ascnt = count($arrccl);
                              $xxccl = 4;
                              $cnt = 0;
                              $calc_ccl = 0;
                              
                              $r = 0;
                              $old_r = $r;
                              foreach($arrccl as $k=>$v) {
                                 if($cnt==0) {
                                    $calc_ccl = $v;
                                 }
                                 $cnt++;
                                 $r = _bctrim(bcdiv($cnt,$ascnt));
                                 
                                 if(bccomp($old_r,0.75)>=0) {
                                 } else {
                                    $calc_ccl = $v;
                                 }
                                 $old_r = $r;
                              }
                              
                              $asrlist .= "<tr><td colspan=\"2\" style=\"text-align:left;font-weight:bold;\">Result</td><td style=\"font-weight:bold;\">$calc_ccl</td></tr>";
                              $asrlist .= "</tbody></table>";
                              if($_SESSION["asmresself"]==0) {
                                 ///$tooltips .= "\nnew Tip('tdccl_${competency_id}', '$asrlist', {title:'Assessment Result Detail',style:'emp',offset:{x:0,y:10},width:300});";
                              }
                              
                              ////
                              if($oldcompgroup!=$compgroup_nm) {
                                 $ret .= "<tr><td colspan='11' style='font-weight:bold;background-color:#eee;padding:4px;'>$compgroup_nm</td></tr>";
                                 $oldcompgroup = $compgroup_nm;
                                 $oldcompgroup_id = $compgroup_id;
                                 $oldcc = "";
                              }
                              if($oldcc!=$cc) {
                                 $cctxt = $cc;
                                 $oldcc = $cc;
                                 $style = "style='border-bottom:0px;'";
                              } else {
                                 $cctxt = "";
                                 $style = "style='border-top:0px;border-bottom:0px;'";
                              }
                              $gapx = $calc_ccl*$itj-$rcl*$itj;
                              if($gapx<0) {
                                 $gap_color = "color:red;font-weight:bold;";
                                 $competency_color = "color:red;";
                              } else {
                                 $gap_color = "";
                                 $competency_color = "";
                                 if($compgroup_id==1||$compgroup_id==2) {
                                    $cf_pass[$compgroup_id][$competency_id] = 1;
                                 }
                              }
                              $retxxx .= "<tr><td $style>$cctxt</td>"
                                    . "<td style='${competency_color}' id='tcomp_${competency_id}' class='tdcomp'>$competency_nm</td>"
                                    . "<td $style></td>"
                                    . "<td style='text-align:center;'>$itj</td>"
                                    . "<td style='text-align:center;'>$rcl</td>"
                                    . "<td $style></td>"
                                    . "<td style='text-align:center;cursor:default;' id='tdccl_${competency_id}'>$calc_ccl</td>"
                                    . "<td $style></td>"
                                    . "<td style='text-align:center;'>".($rcl*$itj)."</td>"
                                    . "<td style='text-align:center;'>".($calc_ccl*$itj)."</td>"
                                    . "<td style='text-align:center;${gap_color}'>$gapx</td>"
                                    . "</tr>";
                              $ttlccl += ($calc_ccl*$itj);
                              $ttlrcl += ($rcl*$itj);
                              $ttlgap += (($calc_ccl-$rcl)*$itj);
                              ///$tooltips .= "\nnew Tip('tcomp_${competency_id}', \"".addslashes($desc_en)."<hr noshade='1' size='1' color='#dddddd'/><span style='font-style:italic;'>".addslashes($desc_id)."</span>\", {title:'Description',width:350,style:'emp'});";
                           }
                        }
                        
                        if($ttlrcl==0) {
                           $match = 0;
                        } else {
                           $match = toMoney(_bctrim(100*$ttlccl/$ttlrcl));
                        }
                        
                        if($match < 80) {
                           $clr = "color:red;";
                        } else {
                           $clr = "";
                        }
                              
                        
                        /// competency fit
                        $cf_cnt = $cf_pass_cnt = 0;
                        foreach($cf_compgroup as $cg=>$x) {
                           $cf_cnt += count($cf_compgroup[$cg]);
                           $cf_pass_cnt += count($cf_pass[$cg]);
                        }
      
                        $cf = toMoney(_bctrim(bcmul(100,bcdiv($cf_pass_cnt,$cf_cnt))));
                        $pr = toMoney(_bctrim(bcmul(100,$pr_value)));
                        
                        if($cf<70) {
                           $cf_clr = "color:red;";
                        } else {
                           $cf_clr = "";
                        }
      
                        if($pr<70) {
                           $pr_clr = "color:red;";
                        } else {
                           $pr_clr = "";
                        }
      
                        if($job_level=="nonmanagement") {
                           $jl = $gradeval;
                        } else {
                           $jl = "-";
                        }
                        
                        ////////////////////////////////////////////////////////////////////
                        ////////////////////////////////////////////////////////////////////
                        
                        $sql = "SELECT request_id,status_cd,request_t FROM ".XOCP_PREFIX."idp_request"
                             . " WHERE employee_id = '$employee_id'"
                             . " AND status_cd NOT IN('nullified')";
                        $rreq = $db->query($sql);
                        $req_count = 0;
                        $req_status = "";
                        $progress_time = $progress_qty = 0;
                        $progress_time_txt = "0%";
                        $progress_qty_txt = "0%";
                        $xlink = "";
                        if($db->getRowsNum($rreq)>0) {

                           while(list($request_id,$status_cd,$request_t)=$db->fetchRow($rreq)) {
                              
                              
                              //////////////////////////////////////////////////////////////////////////////////////////////////////////
                              $sql = "SELECT COUNT(*) FROM ".XOCP_PREFIX."idp_request_actionplan WHERE request_id = '$request_id' AND status_cd NOT IN ('rejected','nullified')";
                              $rc = $db->query($sql);
                              if($db->getRowsNum($rc)==1) {
                                 list($cntaap)=$db->fetchRow($rc);
                              } else {
                                 $cntaap = 0;
                              }
                              
                              $sql = "SELECT COUNT(*) FROM ".XOCP_PREFIX."idp_request_actionplan WHERE request_id = '$request_id' AND status_cd = 'completed'";
                              $rc = $db->query($sql);
                              if($db->getRowsNum($rc)==1) {
                                 list($cntaapc)=$db->fetchRow($rc);
                              } else {
                                 $cntaapc = 0;
                              }
                              
                              if($cntaap>0) {
                                 $progress_qty = ceil(bcmul(100,bcdiv($cntaapc,$cntaap)));
                                 if($progress_qty>100) $progress_qty = 100;
                                 $progress_qty_txt = toMoneyShort($progress_qty)."%";
                              } else {
                                 $progress_qty_txt = "0%";
                                 $progress_qty = 0;
                              }
                              
                              if($progress_qty==100) {
                                 $sql = "UPDATE ".XOCP_PREFIX."idp_request SET status_cd = 'completed' WHERE request_id = '$request_id'";
                                 $db->query($sql);
                                 $status_cd = "completed";
                              }
                           
                              $_SESSION["my_subordinate_request"][$request_id] = array($employee_id,$request_id,$status_cd);
                              
                              switch($status_cd) {
                                 case "start":
                                    $req_status .= ", New Request";
                                    $xlink .= ", <a href='".XOCP_SERVER_SUBDIR."/index.php?XP_idpreviewtool_hris="._HRIS_IDPREVIEWTOOL_BLOCK."&req=y&e=${employee_id}&j=${job_id}'>New Request</a>";
                                    break;
                                 case "employee":
                                    $req_status .= ", Employee Request";
                                    $xlink .= ", <a href='".XOCP_SERVER_SUBDIR."/index.php?XP_idpreviewtool_hris="._HRIS_IDPREVIEWTOOL_BLOCK."&req=y&e=${employee_id}&j=${job_id}'>Employee Completion</a>";
                                    break;
                                 case "approval1":
                                    $req_status .= ", Need Approval";
                                    $xlink .= ", <a href='".XOCP_SERVER_SUBDIR."/index.php?XP_idpsuperiorapprove_hris="._HRIS_IDPSUPERIORAPPROVAL_BLOCK."&req=y&e=${employee_id}&j=${job_id}'>Need Approval</a>";
                                    break;
                                 case "approval2":
                                    $req_status .= ", Next Superior Approval";
                                    $xlink .= ", <a href='".XOCP_SERVER_SUBDIR."/index.php?XP_idpreviewtool_hris="._HRIS_IDPREVIEWTOOL_BLOCK."&req=y&e=${employee_id}&j=${job_id}'>Next Superior Approval</a>";
                                    break;
                                 case "approval3":
                                    $req_status .= ", HR Approval";
                                    $xlink .= ", <a href='".XOCP_SERVER_SUBDIR."/index.php?XP_idpreviewtool_hris="._HRIS_IDPREVIEWTOOL_BLOCK."&req=y&e=${employee_id}&j=${job_id}'>HR Approval</a>";
                                    break;
                                 case "implementation":
                                    $req_status .= ", Implementation";
                                    $xlink .= ", <a href='".XOCP_SERVER_SUBDIR."/index.php?XP_idpreviewtool_hris="._HRIS_IDPREVIEWTOOL_BLOCK."&req=y&e=${employee_id}&j=${job_id}'>Implementation</a>";
                                    break;
                                 case "completed":
                                    $req_status .= ", Completed";
                                    
                                    $sql = "SELECT asid FROM hris_assessment_session WHERE idp_request_id = '$request_id' AND idp_employee_id = '$employee_id'";
                                    $rasid = $db->query($sql);
                                    _debuglog($sql);
                                    if($db->getRowsNum($rasid)>0) {
                                       list($asid)=$db->fetchRow($rasid);
                                       $req_status .= ", Assessment";
                                       $xlink .= ", <a href='".XOCP_SERVER_SUBDIR."/index.php?XP_assessment_hris=asm&assessment_idp=y&employee_id=${employee_id}&j=${job_id}&asid=${asid}'>Assessment</a>";
                                    } else {
                                       $req_status .= ", Completed";
                                       $xlink .= ", <a href='".XOCP_SERVER_SUBDIR."/index.php?XP_idpreviewtool_hris="._HRIS_IDPREVIEWTOOL_BLOCK."&req=y&e=${employee_id}&j=${job_id}'>Completed</a>";
                                    }
                                    
                                    break;
                                 default:
                                    $xlink .= ", <a href='".XOCP_SERVER_SUBDIR."/index.php?XP_idpreviewtool_hris="._HRIS_IDPREVIEWTOOL_BLOCK."&req=y&e=${employee_id}&j=${job_id}'>?</a>";
                                    break;
                              }
                              $req_count++;
                              //////////////////////////////////////////////////////////////////////////////////////////////////////////
                              list($timeframe_start,$timeframe_stop)=_idp_get_timeframe($request_id);
                              
                              $progress_time_txt = "0%";
                              
                              $sql = "SELECT TO_DAYS(now()),TO_DAYS('$timeframe_start'),TO_DAYS('$timeframe_stop')";
                              $resultx = $db->query($sql);
                              list($now,$start,$stop)=$db->fetchRow($resultx);
                              if($now<=$start) {
                                 $progress_time_txt = "0%";
                              } else {
                                 $p = $now-$start;
                                 $q = $stop-$start;
                                 if($q>0) {
                                    $progress_time = 100*($p/$q);
                                 } else {
                                    $progress_time = 0;
                                 }
                                 if($progress_time>100) $progress_time = 100;
                                 $progress_time_txt = toMoneyShort($progress_time)."%";
                              }
            
                              
                              //////////////////////////////////////////////////////////////////////////////////////////////////////////
                           
                           }
                        }
                        
                        $req_status = substr($req_status,2);
                        $xlink = substr($xlink,2);
                        
                        $employee_list .= "<tr id='tremp_${employee_id}_${job_id}'>"
                                          . "<td id='emp_${employee_id}_${job_id}' style='cursor:default;'>$employee_nm</td>"
                                          . "<td id='empjob_${employee_id}_${job_id}' style='cursor:default;'>$job_abbr</td>"
                                          . "<td style='text-align:right;'>"
                                             . "<a style='$clr' href='".XOCP_SERVER_SUBDIR."/index.php?XP_idpreviewtool_hris="._HRIS_IDPREVIEWTOOL_BLOCK."&r=y&e=${employee_id}&j=${job_id}'>$match%</a>"
                                          . "</td>"
                                          . "<td style='text-align:right;'>"
                                             . "<a style='$cf_clr' href='".XOCP_SERVER_SUBDIR."/index.php?XP_idpreviewtool_hris="._HRIS_IDPREVIEWTOOL_BLOCK."&r=y&e=${employee_id}&j=${job_id}'>$cf%</a>"
                                          . "</td>"
                                          . "<td style='text-align:left;'>"
                                             . "<div style='float:left;overflow:hidden;width:50px !important;border:1px solid #999999;margin:auto;text-align:left;'>"
                                             . "<img src='".XOCP_SERVER_SUBDIR."/modules/hris/images/level_foreground.png' style='width:50px;height:12px;margin-left:".((int)(-50+($progress_time/2)))."px;'/>"
                                             . "</div>"
                                             . "<div style='float:left;padding-left:3px;'>$progress_time_txt</div>"
                                          . "</td>"
                                          . "<td style='text-align:left;'>"
                                             . "<div style='float:left;overflow:hidden;width:50px !important;border:1px solid #999999;margin:auto;text-align:left;'>"
                                             . "<img src='".XOCP_SERVER_SUBDIR."/modules/hris/images/level_foreground.png' style='width:50px;height:12px;margin-left:".((int)(-50+($progress_qty/2)))."px;'/>"
                                             . "</div>"
                                             . "<div style='float:left;padding-left:3px;'>$progress_qty_txt</div>"
                                          . "</td>"
                                          . "<td style='text-align:center;'>" 
                                             ///. "<a href='".XOCP_SERVER_SUBDIR."/index.php?XP_idpreviewtool_hris="._HRIS_IDPREVIEWTOOL_BLOCK."&req=y&e=${employee_id}&j=${job_id}'>".($req_count>0?$req_status:"Click to start")."</a>&nbsp;"
                                             . $xlink
                                          . "</td>"
                                          . "</tr>";
                        $no++;
                     }
                  } else {
                     continue;
                     $employee_list .= "<tr id='eemp_0_${job_id}' class='trd2'>"
                                       . "<td><div style='color:#bbbbbb;font-style:italic;padding-left:30px;font-weight:normal;'>Empty</div></td>"
                                       . "<td id='empjob_0_${job_id}' style='cursor:default;'>$job_abbr</td>"
                                       . "<td style='text-align:center;'>&nbsp;</td>"
                                       . "<td style='text-align:right;'>&nbsp;</td>"
                                       . "<td style='text-align:right;'>&nbsp;</td>"
                                       . "<td style='text-align:right;'>&nbsp;</td>"
                                       . "<td>&nbsp;</td>"
                                       . "</tr>";
                     $tooltips .= "\nnew Tip('empjob_0_${job_id}', '<div style=\"font-weight:bold;\">Job Summary:</div>$job_summary', {viewport:true,title:'$job_nm',style:'emp'});";
                     $no++;
                  }
                  
               }
            } else {
               return;
               return "<div style='text-align:center;'>You don't have any employee/subordinate.</div>";
            }
         }
      }
      
      
      if($assessor_job_count==0) {
         return;
         return "<div style='text-align:center;'>You don't have a job assigned. Please contact HR Administrator.</div>";
      }
      
      
      
      $ret = "<div style='padding:5px;padding-left:0px;'>"
           . "<div style='padding:5px;border:1px solid #bbb;background-color:#ddddff;-moz-border-radius:5px 5px 0 0;text-align:center;'>Subordinate IDP</div>"
           . "<div style='border:1px solid #bbb;border-top:0px;-moz-border-radius:0 0 5px 5px;padding:5px;'>"
           . "<div>"
           . "<table style='width:100%;' class='xxlist'><tbody style=''><tr style=''>"
               . "<td style='background-color:#eef;font-weight:bold;'>Employee Name</td>"
               . "<td style='background-color:#eef;font-weight:bold;'>Job</td>"
               . "<td style='background-color:#eef;font-weight:bold;text-align:right;'>Job Match</td>"
               . "<td style='background-color:#eef;font-weight:bold;text-align:right;'>Comp. Fit</td>"
               . "<td style='background-color:#eef;font-weight:bold;text-align:left;'>Elapsed Time</td>"
               . "<td style='background-color:#eef;font-weight:bold;text-align:left;'>Progress</td>"
               . "<td style='background-color:#eef;font-weight:bold;text-align:center;'>IDP Status</td>"
           . "</tr></tbody><tbody>"
           . $employee_list
           . "</tbody></table>"
           . "</div>"
           . "</div>"
           . "</div>";
      
      
      
      $ret .= "<script type='text/javascript' src='".XOCP_SERVER_SUBDIR."/include/prototip2.0.5/js/prototip.js'></script>"
           . "<link rel='stylesheet' type='text/css' href='".XOCP_SERVER_SUBDIR."/include/prototip2.0.5/css/prototip.css' />";
      
      $ret .= "<script type='text/javascript'><!--
      
      $tooltips
      
      
      // --></script>";

      $ret .= "<script type='text/javascript'>
      function show_hari() {
      //membuat variabel bertipe array untuk nama hari
      var NamaHari = new Array(\"Minggu\", \"Senin\", \"Selasa\", \"Rabu\", \"Kamis\", \"Jumat\",
      \"Sabtu\");
      //membuat variabel bertipe array untuk nama bulan
      var NamaBulan = new Array(\"Januari\", \"Februari\", \"Maret\", \"April\", \"Mei\",
      \"Juni\", \"Juli\", \"Agustus\", \"September\", \"Oktober\", \"November\", \"Desember\");
      var sekarang = new Date();
      var HariIni = NamaHari[sekarang.getDay()];
      var BulanIni = NamaBulan[sekarang.getMonth()];
      var tglSekarang = sekarang.getDate();
      var TahunIni = sekarang.getFullYear();
      document.write(HariIni + ', ' + tglSekarang + ' ' + BulanIni + ' ' + TahunIni);
      alert('HariIni');
      }
      </script>";
      
      
      
      return $ret;
      
   }
   
   
   
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            if(isset($_GET["msg"])&&$_GET["msg"]!="") {
               $notification_id = $_GET["msg"];
               /// check is request exists:
               $sql = "SELECT request_id FROM ".XOCP_PREFIX."idp_notifications WHERE notification_id = '$notification_id'";
               $result = $db->query($sql);
               list($request_id)=$db->fetchRow($result);
               $sql = "SELECT status_cd FROM ".XOCP_PREFIX."idp_request WHERE request_id = '$request_id'";
               $result = $db->query($sql);
               if($db->getRowsNum($result)>0) {
                  list($status_cd)=$db->fetchRow($result);
                  if($status_cd=='nullified') {
                     $sql = "UPDATE ".XOCP_PREFIX."idp_notifications SET is_followed='1', followed_dttm = now() WHERE notification_id = '$notification_id'";
                     $db->query($sql);
                  }
               } else {
                  $sql = "UPDATE ".XOCP_PREFIX."idp_notifications SET is_followed='1', followed_dttm = now() WHERE notification_id = '$notification_id'";
                  $db->query($sql);
               
               }
               
               $url = urldecode($_GET["url"]);
               $_SESSION["html"]->redirect = $url;
               $dofollow = $_GET["df"];
               if($notification_id>0) {
                  if($dofollow>0) {
                     $sql = "UPDATE ".XOCP_PREFIX."idp_notifications SET is_followed='1', followed_dttm = now() WHERE notification_id = '$notification_id'";
                     $db->query($sql);
                     
                     /// then follow the same message_id and request_id
                     $sql = "SELECT request_id,user_id,message_id FROM ".XOCP_PREFIX."idp_notifications WHERE notification_id = '$notification_id'";
                     $result = $db->query($sql);
                     if($db->getRowsNum($result)>0) {
                        while(list($request_id,$user_id,$message_id)=$db->fetchRow($result)) {
                           $sql = "UPDATE ".XOCP_PREFIX."idp_notifications SET is_followed='1', followed_dttm = now() WHERE user_id = '$user_id' AND message_id = '$message_id' AND request_id = '$request_id'";
                           $db->query($sql);
                        }
                     }
                  }
                  $sql = "UPDATE ".XOCP_PREFIX."idp_notifications SET click_dttm = now(), click_count=click_count+1 WHERE notification_id = '$notification_id'";
                  $db->query($sql);
               }
               return "";
            } else if(isset($_GET["msgpms"])&&$_GET["msgpms"]!="") {
               $url = urldecode($_GET["url"]);
               $_SESSION["html"]->redirect = $url;
            } else {
               $ret = $this->home();
            }
            break;
         default:
            $ret = $this->home();
            break;
      }
      return $ret;
   }
}

} // HRIS_HOMEUSER_DEFINED
?>