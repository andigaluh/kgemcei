<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_idpeventconfirmation.php        //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2010-05-04                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_IDPEVENTCONFIRMATIONAJAX_DEFINED') ) {
   define('HRIS_IDPEVENTCONFIRMATIONAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");

class _hris_class_IDPEventConfirmationAjax extends AjaxListener {
   
   function _hris_class_IDPEventConfirmationAjax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/hris/class/ajax_idpeventconfirmation.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_eventAccept","app_eventDeny");
   }
   
   function render_event($event_id,$employee_id,$strip_header=FALSE) {
      include_once(XOCP_DOC_ROOT."/modules/hris/include/idp/idp.php");
      $db=&Database::getInstance();
      $user_id = getUserID();
      $act_name = $this->_act_name;
      
      //// set in
      $sql = "UPDATE ".XOCP_PREFIX."idp_event_registration SET "
           . "status_cd = 'self_registered',"
           . "employee_confirm_dttm = now()"
           . " WHERE event_id = '$event_id'"
           . " AND employee_id = '$employee_id'";
      //$db->query($sql);
      
      $sql = "SELECT b.person_nm,c.employee_id"
           . " FROM ".XOCP_PREFIX."users a"
           . " LEFT JOIN ".XOCP_PREFIX."persons b USING(person_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee c ON c.person_id = a.person_id"
           . " WHERE a.user_id = '$user_id'";
      $result = $db->query($sql);
      _debuglog($sql);
      _debuglog($employee_id);
      if($db->getRowsNum($result)>0) {
         list($employee_nm,$employee_idx)=$db->fetchRow($result);
         if($employee_id!=$employee_idx) {
            return "We're sorry but you are not authorized to access this URL.<br/><br/>Please logout first then relogin.";
         }
      }
      
      
      $sql = "SELECT event_title FROM ".XOCP_PREFIX."idp_event WHERE event_id = '$event_id'";
      $result = $db->query($sql);
      list($event_title)=$db->fetchRow($result);
      
      $sql = "SELECT a.event_id,a.event_title,a.start_dttm,a.stop_dttm,a.cost_budget_person,a.registration_t,b.institute_nm,"
           . "d.method_type,a.event_description"
           . " FROM ".XOCP_PREFIX."idp_event a"
           . " LEFT JOIN ".XOCP_PREFIX."idp_institutes b USING(institute_id)"
           . " LEFT JOIN ".XOCP_PREFIX."idp_development_method c ON c.method_id = a.method_id"
           . " LEFT JOIN ".XOCP_PREFIX."idp_development_method_type d ON d.method_t = c.method_t"
           . " WHERE a.event_id = '$event_id'";
      $revent = $db->query($sql);
      if($db->getRowsNum($revent)>0) {
         list($event_idx,$event_title,$event_start_dttm,$event_stop_dttm,$event_cost,$event_registration_t,$institute_nmx,
              $method_type,$event_description)=$db->fetchRow($revent);
      }
      
      $sql = "SELECT a.request_id,a.actionplan_id,a.status_cd"
           . " FROM ".XOCP_PREFIX."idp_event_registration a"
           . " LEFT JOIN ".XOCP_PREFIX."idp_request b USING(request_id)"
           . " WHERE a.event_id = '$event_id'"
           . " AND a.employee_id = '$employee_id'";
      $result = $db->query($sql);
      $reqmatch = "";
      $eventmatch = "";
      if($db->getRowsNum($result)>0) {
         list($request_id,$actionplan_id,$participation_status_cd)=$db->fetchRow($result);
         if($request_id>0&&$actionplan_id>0) {
            $sql = "SELECT b.competency_abbr,b.competency_nm,a.competency_id,a.plan_start_dttm,a.plan_stop_dttm,"
                 . "a.cost_estimate,a.cost_real,a.event_id,a.method_id,d.method_type"
                 . " FROM ".XOCP_PREFIX."idp_request_actionplan a"
                 . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
                 . " LEFT JOIN ".XOCP_PREFIX."idp_development_method c ON c.method_id = a.method_id"
                 . " LEFT JOIN ".XOCP_PREFIX."idp_development_method_type d ON d.method_t = c.method_t"
                 . " WHERE a.request_id = '$request_id'"
                 . " AND a.actionplan_id = '$actionplan_id'"
                 . " AND a.status_cd = 'implementation'";
            $result = $db->query($sql);
            if($db->getRowsNum($result)>0) {
               list($competency_abbr,$competency_nm,$competency_id,$plan_start_dttm,$plan_stop_dttm,$cost_estimate,$cost_real,$event_id,$method_id)=$db->fetchRow($result);
               $reqmatch = "<tr><td colspan='2' style='text-align:center;font-weight:bold;'>Match with your IDP request :</td></tr>"
                         . "<tr><td>Competency :</td><td>$competency_nm - $competency_abbr</td></tr>"
                         . "<tr><td>Action Plan :</td><td>$method_type</td></tr>"
                         . "<tr><td>Time :</td><td>".sql2ind($plan_start_dttm)." - ".sql2ind($plan_stop_dttm)."</td></tr>"
                         . "<tr><td>Cost Estimate :</td><td>".toMoney($cost_estimate)." IDR</td></tr>";
               $eventmatch = "<div style='margin-top:10px;font-style:italic;'>By clicking '<span style='color:black;'>Join</span>', your IDP record will be updated and synchronized with this event.</div>";
            }
         }
      }
      
      switch($participation_status_cd) {
         case "self_registered":
            $salutation = "Dear $employee_nm,"
                        . "<br/><br/>"
                        . "You are registered to this event:"
                        . "<br/><br/>";
            $btn = "<br/><div>Please click '<span style='color:black;'>Join</span>' to confirm your participation or click '<span style='color:black;'>Leave</span>' to cancel your participation.</div>"
                 . $eventmatch
                 . "<div style='padding-top:20px;padding-bottom:10px;text-align:center;width:500px;' id='dvbutton'>"
                 . "<input type='button' value='Join' onclick='accept_event(\"$event_id\",\"$employee_id\",this,event);' style='width:90px;' class='xaction'/>&nbsp;&nbsp;"
                 . "<input type='button' value='Leave' onclick='deny_event(\"$event_id\",\"$employee_id\",this,event);' style='width:90px;'/>"
                 . "</div>";
            break;
         case "invited":
            $salutation = "Dear $employee_nm,"
                        . "<br/><br/>"
                        . "You are invited to this event:"
                        . "<br/><br/>";
            $btn = "<br/><div>Please click '<span style='color:black;'>Join</span>' to confirm your participation or click '<span style='color:black;'>Leave</span>' to cancel your participation.</div>"
                 . $eventmatch
                 . "<div style='padding-top:20px;padding-bottom:10px;text-align:center;width:500px;' id='dvbutton'>"
                 . "<input type='button' value='Join' onclick='accept_event(\"$event_id\",\"$employee_id\",this,event);' style='width:90px;' class='xaction'/>&nbsp;&nbsp;"
                 . "<input type='button' value='Leave' onclick='deny_event(\"$event_id\",\"$employee_id\",this,event);' style='width:90px;'/>"
                 . "</div>";
            break;
         case "in":
            $salutation = "Dear $employee_nm,"
                        . "<br/><br/>"
                        . "You have confirmed your participation to this event:"
                        . "<br/><br/>";
            $note = "<br/><div style='color:blue;width:500px;text-align:center;font-style:italic;'>Thank you for joining.</div>";
            if($reqmatch!="") {
               $note .= "<div style='color:blue;width:500px;text-align:center;font-style:italic;'>Your IDP record has been updated and synchronized with this event.</div>";
            }
            $sql = "SELECT notification_id FROM ".XOCP_PREFIX."idp_notifications WHERE user_id = '$user_id'"
                 . " AND message_id = '_IDP_EVENTYOUHAVEREGISTRATIONCONFIRMATION'"
                 . " AND event_id = '$event_id'";
            $result = $db->query($sql);
            if($db->getRowsNum($result)>0) {
               while(list($notification_id)=$db->fetchRow($result)) {
                  _idp_notification_followed($notification_id);
               }
            }
            break;
         case "out":
         default:
            $salutation = "Dear $employee_nm,"
                        . "<br/><br/>"
                        . "You have decided not to join this event:"
                        . "<br/><br/>";
            $note = "<br/><div style='color:red;width:500px;text-align:center;font-style:italic;'>You are not joining this event.</div>";
            $sql = "SELECT notification_id FROM ".XOCP_PREFIX."idp_notifications WHERE user_id = '$user_id'"
                 . " AND message_id = '_IDP_EVENTYOUHAVEREGISTRATIONCONFIRMATION'"
                 . " AND event_id = '$event_id'";
            $result = $db->query($sql);
            if($db->getRowsNum($result)>0) {
               while(list($notification_id)=$db->fetchRow($result)) {
                  _idp_notification_followed($notification_id);
               }
            }
            break;
      }
      
      $ret = ($strip_header==TRUE?"":"<div style='margin-bottom:50px;' id='dvevent'>")
            . $salutation
            . "<table class='xxfrm' style='width:500px;border:2px solid #888;'><tbody>"
            . "<tr><td>Event Title :</td><td style=''>$event_title</td></tr>"
            . "<tr><td>Type :</td><td style=''>$method_type</td></tr>"
            . "<tr><td>Description :</td><td style=''>".nl2br($event_description)."</td></tr>"
            . "<tr><td>Time :</td><td style=''>".sql2ind($event_start_dttm)." - ".sql2ind($event_stop_dttm)."</td></tr>"
            . "<tr><td>Provider :</td><td style=''>$institute_nmx</td></tr>"
            . "<tr><td>Cost Per Person :</td><td style=''>".toMoney($event_cost)." IDR</td></tr>"
            . $reqmatch
            . "</tbody></table>"
            . $btn
            . $note
            . "<div style='margin-top:10px;'>For more information about this event, please contact HR Administrator.</div>"
            . ($strip_header==TRUE?"":"</div>");
      
      if($strip_header==TRUE) {
         $js = "";
      } else {
         $js = $this->getJs()."<script type='text/javascript'><!--
         
         function accept_event(event_id,employee_id,d,e) {
            var p = d.parentNode;
            p.innerHTML = '';
            p.appendChild(progress_span(' ... Accepting'));
            ${act_name}_app_eventAccept(event_id,employee_id,function(_data) {
               var xp = $('dvevent');
               xp.innerHTML = _data;
            });
         }
         
         function deny_event(event_id,employee_id,d,e) {
            var p = d.parentNode;
            p.innerHTML = '';
            p.appendChild(progress_span(' ... Accepting'));
            ${act_name}_app_eventDeny(event_id,employee_id,function(_data) {
               var xp = $('dvevent');
               xp.innerHTML = _data;
            });
         }
         
         // --></script>";
      }
      
      return $js.$ret;
      
   }
   
   function app_eventAccept($args) {
      include_once(XOCP_DOC_ROOT."/modules/hris/include/idp/idp.php");
      $db=&Database::getInstance();
      $user_id = getUserID();
      $event_id = $args[0];
      $employee_id = $args[1];
      
      $sql = "SELECT b.person_nm,c.employee_id"
           . " FROM ".XOCP_PREFIX."users a"
           . " LEFT JOIN ".XOCP_PREFIX."persons b USING(person_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee c ON c.person_id = a.person_id"
           . " WHERE a.user_id = '$user_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($employee_nm,$employee_idx)=$db->fetchRow($result);
         if($employee_id!=$employee_idx) {
            return "<div style='color:red;'>Confirmation failed. Please contact administrator.<br/>We apologize for this inconvenience.</div>";
         }
      }
      
      $sql = "SELECT a.start_dttm,a.stop_dttm,a.cost_budget_person"
           . " FROM ".XOCP_PREFIX."idp_event a"
           . " LEFT JOIN ".XOCP_PREFIX."idp_institutes b USING(institute_id)"
           . " LEFT JOIN ".XOCP_PREFIX."idp_development_method c ON c.method_id = a.method_id"
           . " LEFT JOIN ".XOCP_PREFIX."idp_development_method_type d ON d.method_t = c.method_t"
           . " WHERE a.event_id = '$event_id'";
      $revent = $db->query($sql);
      if($db->getRowsNum($revent)>0) {
         list($event_start_dttm,$event_stop_dttm,$event_cost)=$db->fetchRow($revent);
      } else {
         return "<div style='color:red;'>Confirmation failed. Please contact administrator.<br/>We apologize for this inconvenience.</div>";
      }
      
      //// set in
      $sql = "UPDATE ".XOCP_PREFIX."idp_event_registration SET "
           . "status_cd = 'in',"
           . "employee_confirm_dttm = now()"
           . " WHERE event_id = '$event_id'"
           . " AND employee_id = '$employee_id'";
      $db->query($sql);
      
      $sql = "SELECT a.request_id,a.actionplan_id,a.status_cd"
           . " FROM ".XOCP_PREFIX."idp_event_registration a"
           . " LEFT JOIN ".XOCP_PREFIX."idp_request b USING(request_id)"
           . " WHERE a.event_id = '$event_id'"
           . " AND a.employee_id = '$employee_id'";
      $result = $db->query($sql);
      $reqmatch = "";
      $eventmatch = "";
      if($db->getRowsNum($result)>0) {
         list($request_id,$actionplan_id,$participation_status_cd)=$db->fetchRow($result);
         if($request_id>0&&$actionplan_id>0) {
            $sql = "UPDATE ".XOCP_PREFIX."idp_request_actionplan SET "
                 . "plan_start_dttm = '$event_start_dttm',"
                 . "plan_stop_dttm = '$event_stop_dttm',"
                 . "cost_estimate = '$event_cost'"
                 . " WHERE request_id = '$request_id'"
                 . " AND actionplan_id = '$actionplan_id'";
            $db->query($sql);
            $retidp = "<div style='color:blue;'>Your IDP record was updated and synchronized.</div>";
         }
      }
      
      $sql = "SELECT notification_id FROM ".XOCP_PREFIX."idp_notifications WHERE user_id = '$user_id'"
           . " AND message_id = '_IDP_EVENTYOUHAVEREGISTRATIONCONFIRMATION'"
           . " AND event_id = '$event_id'";
      $result = $db->query($sql);
      _debuglog($sql);
      if($db->getRowsNum($result)>0) {
         while(list($notification_id)=$db->fetchRow($result)) {
            _idp_notification_followed($notification_id);
         }
      }
      
      
      $ret = $this->render_event($event_id,$employee_id,TRUE);
      return $ret;
      
   }
   
   function app_eventDeny($args) {
      include_once(XOCP_DOC_ROOT."/modules/hris/include/idp/idp.php");
      $db=&Database::getInstance();
      $user_id = getUserID();
      $event_id = $args[0];
      $employee_id = $args[1];
      
      $sql = "SELECT b.person_nm,c.employee_id"
           . " FROM ".XOCP_PREFIX."users a"
           . " LEFT JOIN ".XOCP_PREFIX."persons b USING(person_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee c ON c.person_id = a.person_id"
           . " WHERE a.user_id = '$user_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($employee_nm,$employee_idx)=$db->fetchRow($result);
         if($employee_id!=$employee_idx) {
            return "<div style='color:red;'>Confirmation failed. Please contact administrator.<br/>We apologize for this inconvenience.</div>";
         }
      }
      
      $sql = "SELECT a.start_dttm,a.stop_dttm,a.cost_budget_person"
           . " FROM ".XOCP_PREFIX."idp_event a"
           . " LEFT JOIN ".XOCP_PREFIX."idp_institutes b USING(institute_id)"
           . " LEFT JOIN ".XOCP_PREFIX."idp_development_method c ON c.method_id = a.method_id"
           . " LEFT JOIN ".XOCP_PREFIX."idp_development_method_type d ON d.method_t = c.method_t"
           . " WHERE a.event_id = '$event_id'";
      $revent = $db->query($sql);
      if($db->getRowsNum($revent)>0) {
         list($event_start_dttm,$event_stop_dttm,$event_cost)=$db->fetchRow($revent);
      } else {
         return "<div style='color:red;'>Confirmation failed. Please contact administrator.<br/>We apologize for this inconvenience.</div>";
      }
      
      //// set out
      $sql = "UPDATE ".XOCP_PREFIX."idp_event_registration SET "
           . "status_cd = 'out',"
           . "employee_confirm_dttm = now()"
           . " WHERE event_id = '$event_id'"
           . " AND employee_id = '$employee_id'";
      $db->query($sql);
      
      $sql = "SELECT notification_id FROM ".XOCP_PREFIX."idp_notifications WHERE user_id = '$user_id'"
           . " AND message_id = '_IDP_EVENTYOUHAVEREGISTRATIONCONFIRMATION'"
           . " AND event_id = '$event_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($notification_id)=$db->fetchRow($result)) {
            _idp_notification_followed($notification_id);
         }
      }
      
      $ret = $this->render_event($event_id,$employee_id,TRUE);
      return $ret;
      
   }
   
   
}

} /// HRIS_IDPEVENTCONFIRMATIONAJAX_DEFINED
?>