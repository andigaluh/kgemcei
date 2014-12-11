<?php
if ( !defined('HRIS_IDPREQUESTFORM_DEFINED') ) {
   define('HRIS_IDPREQUESTFORM_DEFINED', TRUE);
   
   function _idp_notification_followed($notification_id) {
      $db =& Database::getInstance();
      $sql = "UPDATE ".XOCP_PREFIX."idp_notifications SET is_followed = '1', followed_dttm = now() WHERE notification_id = '$notification_id'";
      $db->query($sql);
   }
   
   function _idp_send_notification($user_id,$request_id,$message_id,$message_txt,$source_app,$generate_user_id=0,$event_id=0) {
      $db =& Database::getInstance();
      $notification_id = _idp_generate_notification_id();
      
      if($_SESSION["suing"]==1) {
         $sql = "INSERT INTO ".XOCP_PREFIX."idp_notifications (notification_id,user_id,request_id,message_id,message_txt,source_app,generate_user_id,event_id)"
              . " VALUES ('$notification_id','$user_id','$request_id','$message_id','$message_txt','$source_app','$generate_user_id','$event_id')";
         $db->query($sql);
      } else {
         $sql = "INSERT INTO ".XOCP_PREFIX."idp_notifications (notification_id,user_id,request_id,message_id,message_txt,source_app,generate_user_id,event_id)"
              . " VALUES ('$notification_id','$user_id','$request_id','$message_id','$message_txt','$source_app','$generate_user_id','$event_id')";
         $db->query($sql);
         _idp_email_notification($notification_id);
      }
      return $notification_id;
   }
   
   function _idp_email_notification($notification_id) {
      $db=&Database::getInstance();
      
      if($_SESSION["suing"]==1) {
         return;
      }
      
      $linkhost = "146.67.1.47";
      ///$linkhost = "localhost/devel";
      
      $sql = "SELECT a.user_id,UNIX_TIMESTAMP(now()),UNIX_TIMESTAMP(a.notification_dttm),a.request_id,a.message_id,"
           . "a.message_txt,a.source_app,a.click_count,a.display_count,a.notification_dttm,"
           . "c.person_nm as employee_nm,r.employee_id,e.person_nm as user,a.event_id"
           . " FROM ".XOCP_PREFIX."idp_notifications a"
           . " LEFT JOIN ".XOCP_PREFIX."idp_request r USING(request_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " LEFT JOIN ".XOCP_PREFIX."users d ON d.user_id = a.user_id"
           . " LEFT JOIN ".XOCP_PREFIX."persons e ON e.person_id = d.person_id"
           . " WHERE a.notification_id = '$notification_id'";
      $result = $db->query($sql);
      $req = "";
      if($db->getRowsNum($result)>0) {
         list($user_id,$now_unixtime,$notification_unixtime,$request_id,$message_id,$message_txt,$source_app,
              $click_count,$display_count,$notification_dttm,$employee_nm,$employee_id,$receiver_nm,$event_id)=$db->fetchRow($result);
         
         if($event_id>0) {
            $sql = "SELECT event_title FROM ".XOCP_PREFIX."idp_event WHERE event_id = '$event_id'";
            $re = $db->query($sql);
            list($event_title)=$db->fetchRow($re);
         }
         
         $report_project_id = $report_actionplan_id = 0;
         
         if(defined($message_id)) {
            $msgfmt = constant($message_id);
         } else {
            $msgfmt = $message_id;
         }
         $subject = sprintf($msgfmt,$employee_nm);
         
         
         $sql = "SELECT a.pwd0,a.person_id,b.email,b.smtp_location,b.person_nm FROM ".XOCP_PREFIX."users a"
              . " LEFT JOIN ".XOCP_PREFIX."persons b USING(person_id)"
              . " WHERE a.user_id = '$user_id'";
         $result = $db->query($sql);
         list($pwd0,$person_id,$email,$smtp,$person_nm)=$db->fetchRow($result);
         
         list($user_mail,$domain_mail)=explode("@",$email);
         $email = "${user_mail}@${smtp}";
         
         $send_dttm = getSQLDate();
         
         $email_key1 = md5($send_dttm);
         $email_key2 = md5($user_id.$send_dttm);
         $email_key3 = md5($pwd0.$send_dttm);
         
         switch($message_id) {
            
            ///// EVENT ///////////////////////////////////////////////////////////////
            case "_IDP_EVENTYOUHAVEMESSAGE":
               break;
            case "_IDP_EVENTYOUHAVEREGISTRATIONCONFIRMATION":
               $body_msg = "<div style='padding:15px;font-size:20px;color: #666666;'>Your event registration confirmation.</div>
<div style='padding:15px;padding-top:0px;width:400px;'>
<p>Dear $receiver_nm,</p>
<p>You are invited/registered to event <b>$event_title</b>.</p>
<p style='font-weight:bold;'>What should I do?</p>
<p>Please confirm your participation for this event by <a href='http://${linkhost}/hris/ld.php?msg=${email_key1}-${email_key2}-${email_key3}'>clicking this link</a>.

<p style='font-weight:bold;'>Where can I get more info?</p>
<p>You can visit our site regarding IDP <a href='http://${linkhost}/hris/index.php?XP_developmentplan_menu=0'>here</a>. Or you
can contact HRIS Administrator.</p>
<p>We will keep you informed about this IDP request through email notification like this. Thank you for your attention.</p>
<p>Sincerely,<br/>
HRIS</p>
</div>";
               
               break;
            ///// REQUEST /////////////////////////////////////////////////////////////
            case "_IDP_YOURIDPREQUESTRETURNED2":
               $body_msg = "<div style='padding:15px;font-size:20px;color: #666666;'>Your IDP request has been returned by next superior.</div>
<div style='padding:15px;padding-top:0px;width:400px;'>
<p>Dear $receiver_nm,</p>
<p>We would like to inform you that your Individual Development Program (IDP) request was returned by your next superior.</p>
<p style='font-weight:bold;'>What should I do?</p>
<p>You must check it by <a href='http://${linkhost}/hris/ld.php?msg=${email_key1}-${email_key2}-${email_key3}'>clicking this link</a>.
After you check and correct for any error or additional requirement, you can then submit it again to get approval from your superior.</p>

<p style='font-weight:bold;'>Where can I get more info?</p>
<p>You can visit our site regarding IDP <a href='http://${linkhost}/hris/index.php?XP_developmentplan_menu=0'>here</a>. Or you
can contact HRIS Administrator.</p>
<p>We will keep you informed about this IDP request through email notification like this. Thank you for your attention.</p>
<p>Sincerely,<br/>
HRIS</p>
</div>";
               break;
            case "_IDP_YOURIDPREQUESTCOMPLETE":
               $body_msg = "<div style='padding:15px;font-size:20px;color: #666666;'>Your IDP request has been completely approved.</div>
<div style='padding:15px;padding-top:0px;width:400px;'>
<p>Dear $receiver_nm,</p>
<p>We would like to inform you that your Individual Development Program (IDP) request has been completely approved. And now you can start your IDP.</p>
<p style='font-weight:bold;'>What should I do?</p>
<p>Please follow your IDP assignment plan and project schedule. <a href='http://${linkhost}/hris/ld.php?msg=${email_key1}-${email_key2}-${email_key3}'>Click here</a> to see your IDP request.

<p style='font-weight:bold;'>Where can I get more info?</p>
<p>You can visit our site regarding IDP <a href='http://${linkhost}/hris/index.php?XP_developmentplan_menu=0'>here</a>. Or you
can contact HRIS Administrator.</p>
<p>We will keep you informed about this IDP request through email notification like this. Thank you for your attention.</p>
<p>Sincerely,<br/>
HRIS</p>
</div>";
               break;
            case "_IDP_YOURIDPREQUESTAPPROVED2":
               $body_msg = "<div style='padding:15px;font-size:20px;color: #666666;'>Your IDP request has been approved by your next superior.</div>
<div style='padding:15px;padding-top:0px;width:400px;'>
<p>Dear $receiver_nm,</p>
<p>We would like to inform you that your Individual Development Program (IDP) request was approved by your next superior.</p>
<p style='font-weight:bold;'>What should I do?</p>
<p>Nothing. You will wait until your request confirmed by HR. <a href='http://${linkhost}/hris/ld.php?msg=${email_key1}-${email_key2}-${email_key3}'>Click here</a> to see your IDP request.

<p style='font-weight:bold;'>Where can I get more info?</p>
<p>You can visit our site regarding IDP <a href='http://${linkhost}/hris/index.php?XP_developmentplan_menu=0'>here</a>. Or you
can contact HRIS Administrator.</p>
<p>We will keep you informed about this IDP request through email notification like this. Thank you for your attention.</p>
<p>Sincerely,<br/>
HRIS</p>
</div>";
               break;
            case "_IDP_YOURIDPREQUESTAPPROVED1":
               $body_msg = "<div style='padding:15px;font-size:20px;color: #666666;'>Your IDP request has been approved by your superior.</div>
<div style='padding:15px;padding-top:0px;width:400px;'>
<p>Dear $receiver_nm,</p>
<p>We would like to inform you that your Individual Development Program (IDP) request was approved by your superior.</p>
<p style='font-weight:bold;'>What should I do?</p>
<p>Nothing. You will wait until your request approved by your next superior. <a href='http://${linkhost}/hris/ld.php?msg=${email_key1}-${email_key2}-${email_key3}'>Click here</a> to see your IDP request.

<p style='font-weight:bold;'>Where can I get more info?</p>
<p>You can visit our site regarding IDP <a href='http://${linkhost}/hris/index.php?XP_developmentplan_menu=0'>here</a>. Or you
can contact HRIS Administrator.</p>
<p>We will keep you informed about this IDP request through email notification like this. Thank you for your attention.</p>
<p>Sincerely,<br/>
HRIS</p>
</div>";
               break;
            case "_IDP_YOURIDPREQUESTSTARTED":
               $body_msg = "<div style='padding:15px;font-size:20px;color: #666666;'>Your IDP request has been initiated.</div>
<div style='padding:15px;padding-top:0px;width:400px;'>
<p>Dear $receiver_nm,</p>
<p>We would like to inform you that your Individual Development Program (IDP) request was already initiated by your superior.</p>
<p style='font-weight:bold;'>What should I do?</p>
<p>You must complete your IDP request that has been initiated by your superior by <a href='http://${linkhost}/hris/ld.php?msg=${email_key1}-${email_key2}-${email_key3}'>clicking this link</a>.
After you complete the request, you can then submit it to get approval from your superior.</p>

<p style='font-weight:bold;'>Where can I get more info?</p>
<p>You can visit our site regarding IDP <a href='http://${linkhost}/hris/index.php?XP_developmentplan_menu=0'>here</a>. Or you
can contact HRIS Administrator.</p>
<p>We will keep you informed about this IDP request through email notification like this. Thank you for your attention.</p>
<p>Sincerely,<br/>
HRIS</p>
</div>";
               break;
            case "_IDP_YOURIDPREQUESTRETURNED":
               $body_msg = "<div style='padding:15px;font-size:20px;color: #666666;'>Your IDP request has been returned.</div>
<div style='padding:15px;padding-top:0px;width:400px;'>
<p>Dear $receiver_nm,</p>
<p>We would like to inform you that your Individual Development Program (IDP) request was returned by your superior.</p>
<p style='font-weight:bold;'>What should I do?</p>
<p>You should improve it by <a href='http://${linkhost}/hris/ld.php?msg=${email_key1}-${email_key2}-${email_key3}'>clicking this link</a>.
After you improve and correct it, you can then submit it again to get approval from your superior.</p>

<p style='font-weight:bold;'>Where can I get more info?</p>
<p>You can visit our site regarding IDP <a href='http://${linkhost}/hris/index.php?XP_developmentplan_menu=0'>here</a>. Or you
can contact HRIS Administrator.</p>
<p>We will keep you informed about this IDP request through email notification like this. Thank you for your attention.</p>
<p>Sincerely,<br/>
HRIS</p>
</div>";
               break;
            case "_IDP_YOURSUBORDINATEIDPREQUESTAPPROVED2":
               $body_msg = "<div style='padding:15px;font-size:20px;color: #666666;'>IDP request for your subordinate named $employee_nm has been approved by your superior.</div>
<div style='padding:15px;padding-top:0px;width:400px;'>
<p>Dear $receiver_nm,</p>
<p>We would like to inform that IDP for your subordinate named $employee_nm has been approved by your superior.</p>
<p style='font-weight:bold;'>What should I do?</p>
<p>Nothing. You could view the request by <a href='http://${linkhost}/hris/ld.php?msg=${email_key1}-${email_key2}-${email_key3}'>clicking this link</a>.</p>

<p style='font-weight:bold;'>Where can I get more info?</p>
<p>You can visit our site regarding IDP <a href='http://${linkhost}/hris/index.php?XP_developmentplan_menu=0'>here</a>. Or you
can contact HRIS Administrator.</p>
<p>We will keep you informed about this IDP request through email notification like this. Thank you for your attention.</p>
<p>Sincerely,<br/>
HRIS</p>
</div>";
               break;
            case "_IDP_YOUHAVEAPPROVAL1":
               $body_msg = "<div style='padding:15px;font-size:20px;color: #666666;'>You have IDP request approval from $employee_nm</div>
<div style='padding:15px;padding-top:0px;width:400px;'>
<p>Dear $receiver_nm,</p>
<p>We would like to inform that employee named $employee_nm has submitted IDP request and now need your approval.</p>
<p style='font-weight:bold;'>What should I do?</p>
<p>You could view the request and then approve or disapprove by <a href='http://${linkhost}/hris/ld.php?msg=${email_key1}-${email_key2}-${email_key3}'>clicking this link</a>.</p>

<p style='font-weight:bold;'>Where can I get more info?</p>
<p>You can visit our site regarding IDP <a href='http://${linkhost}/hris/index.php?XP_developmentplan_menu=0'>here</a>. Or you
can contact HRIS Administrator.</p>
<p>We will keep you informed about this IDP request through email notification like this. Thank you for your attention.</p>
<p>Sincerely,<br/>
HRIS</p>
</div>";
               break;
            case "_IDP_YOURAPPROVAL1RETURNED":
               $body_msg = "<div style='padding:15px;font-size:20px;color: #666666;'>IDP request from $employee_nm has been returned by your superior.</div>
<div style='padding:15px;padding-top:0px;width:400px;'>
<p>Dear $receiver_nm,</p>
<p>Your approval regarding IDP request from employee named $employee_nm has been disapproved by your superior.</p>
<p style='font-weight:bold;'>What should I do?</p>
<p>You could review disapproval notes. To review the request please <a href='http://${linkhost}/hris/ld.php?msg=${email_key1}-${email_key2}-${email_key3}'>click here</a>.</p>

<p style='font-weight:bold;'>Where can I get more info?</p>
<p>You can visit our site regarding IDP <a href='http://${linkhost}/hris/index.php?XP_developmentplan_menu=0'>here</a>. Or you
can contact HRIS Administrator.</p>
<p>We will keep you informed about this IDP request through email notification like this. Thank you for your attention.</p>
<p>Sincerely,<br/>
HRIS</p>
</div>";
               break;
            case "_IDP_YOUHAVEAPPROVAL2":
               $body_msg = "<div style='padding:15px;font-size:20px;color: #666666;'>You have IDP request approval from $employee_nm</div>
<div style='padding:15px;padding-top:0px;width:400px;'>
<p>Dear $receiver_nm,</p>
<p>We would like to inform that employee named $employee_nm has submitted IDP request and now need your approval.</p>
<p style='font-weight:bold;'>What should I do?</p>
<p>You could view the request and then approve or disapprove by <a href='http://${linkhost}/hris/ld.php?msg=${email_key1}-${email_key2}-${email_key3}'>clicking this link</a>.</p>

<p style='font-weight:bold;'>Where can I get more info?</p>
<p>You can visit our site regarding IDP <a href='http://${linkhost}/hris/index.php?XP_developmentplan_menu=0'>here</a>. Or you
can contact HRIS Administrator.</p>
<p>We will keep you informed about this IDP request through email notification like this. Thank you for your attention.</p>
<p>Sincerely,<br/>
HRIS</p>
</div>";
               break;
            case "_IDP_YOUHAVEHRAPPROVAL":
               return;
               break;
            
            ///// REPORT /////////////////////////////////////////////////////////////
            case "_IDP_YOURAPPROVAL1REPORTRETURNED":
               eval($message_txt);
               if($report_project_id>0) {
                  $body_msg = "<div style='padding:15px;font-size:20px;color: #666666;'>IDP Project Report from $employee_nm has been returned by superior.</div>
<div style='padding:15px;padding-top:0px;width:400px;'>
<p>Dear $receiver_nm,</p>
<p>Your approval regarding IDP report from $employee_nm has been disapproved by next superior.</p>
<p style='font-weight:bold;'>What should I do?</p>
<p>To review the report please <a href='http://${linkhost}/hris/ld.php?msg=${email_key1}-${email_key2}-${email_key3}'>click here</a>.</p>

<p style='font-weight:bold;'>Where can I get more info?</p>
<p>You can visit our site regarding IDP <a href='http://${linkhost}/hris/index.php?XP_developmentplan_menu=0'>here</a>. Or you
can contact HRIS Administrator.</p>
<p>We will keep you informed about this IDP report through email notification like this. Thank you for your attention.</p>
<p>Sincerely,<br/>
HRIS</p>
</div>";
               } else {
                  $body_msg = "<div style='padding:15px;font-size:20px;color: #666666;'>IDP Report from $employee_nm has been returned by superior.</div>
<div style='padding:15px;padding-top:0px;width:400px;'>
<p>Dear $receiver_nm,</p>
<p>Your approval regarding IDP report from $employee_nm has been disapproved by next superior.</p>
<p style='font-weight:bold;'>What should I do?</p>
<p>To review the report please <a href='http://${linkhost}/hris/ld.php?msg=${email_key1}-${email_key2}-${email_key3}'>click here</a>.</p>

<p style='font-weight:bold;'>Where can I get more info?</p>
<p>You can visit our site regarding IDP <a href='http://${linkhost}/hris/index.php?XP_developmentplan_menu=0'>here</a>. Or you
can contact HRIS Administrator.</p>
<p>We will keep you informed about this IDP report through email notification like this. Thank you for your attention.</p>
<p>Sincerely,<br/>
HRIS</p>
</div>";
               }
               break;
            case "_IDP_YOUHAVEAPPROVAL1REPORT":
               eval($message_txt);
               if($report_project_id>0) {
                  $body_msg = "<div style='padding:15px;font-size:20px;color: #666666;'>IDP Project Report from $employee_nm need your approval.</div>
<div style='padding:15px;padding-top:0px;width:400px;'>
<p>Dear $receiver_nm,</p>
<p>IDP Report from $employee_nm has been submitted and need your approval.</p>
<p style='font-weight:bold;'>What should I do?</p>
<p>You can review the report and then approve or disapprove by <a href='http://${linkhost}/hris/ld.php?msg=${email_key1}-${email_key2}-${email_key3}'>clicking here</a>.</p>

<p style='font-weight:bold;'>Where can I get more info?</p>
<p>You can visit our site regarding IDP <a href='http://${linkhost}/hris/index.php?XP_developmentplan_menu=0'>here</a>. Or you
can contact HRIS Administrator.</p>
<p>We will keep you informed about this IDP report through email notification like this. Thank you for your attention.</p>
<p>Sincerely,<br/>
HRIS</p>
</div>";
               } else {
                  $body_msg = "<div style='padding:15px;font-size:20px;color: #666666;'>IDP Report from $employee_nm need your approval.</div>
<div style='padding:15px;padding-top:0px;width:400px;'>
<p>Dear $receiver_nm,</p>
<p>IDP Report from $employee_nm has been submitted and need your approval.</p>
<p style='font-weight:bold;'>What should I do?</p>
<p>You can view the report and then approve or disapprove by <a href='http://${linkhost}/hris/ld.php?msg=${email_key1}-${email_key2}-${email_key3}'>clicking here</a>.</p>

<p style='font-weight:bold;'>Where can I get more info?</p>
<p>You can visit our site regarding IDP <a href='http://${linkhost}/hris/index.php?XP_developmentplan_menu=0'>here</a>. Or you
can contact HRIS Administrator.</p>
<p>We will keep you informed about this IDP report through email notification like this. Thank you for your attention.</p>
<p>Sincerely,<br/>
HRIS</p>
</div>";
               }
               break;
            case "_IDP_YOUHAVEDMREPORTNOTIFICATION":
               eval($message_txt);
               $body_msg = "<div style='padding:15px;font-size:20px;color: #666666;'>IDP Report Submission from $employee_nm.</div>
<div style='padding:15px;padding-top:0px;width:400px;'>
<p>Dear $receiver_nm,</p>
<p>We would like to inform you that IDP Report from $employee_nm has been submitted.</p>
<p style='font-weight:bold;'>What should I do?</p>
<p>Nothing. But you can view the report by <a href='http://${linkhost}/hris/ld.php?msg=${email_key1}-${email_key2}-${email_key3}'>clicking here</a>.</p>

<p style='font-weight:bold;'>Where can I get more info?</p>
<p>You can visit our site regarding IDP <a href='http://${linkhost}/hris/index.php?XP_developmentplan_menu=0'>here</a>. Or you
can contact HRIS Administrator.</p>
<p>We will keep you informed about this IDP report through email notification like this. Thank you for your attention.</p>
<p>Sincerely,<br/>
HRIS</p>
</div>";
               $dofollow = 1;
               break;
            case "_IDP_YOUHAVEAPPROVAL2REPORT":
               eval($message_txt);
               $body_msg = "<div style='padding:15px;font-size:20px;color: #666666;'>IDP Report Submission from $employee_nm need your approval.</div>
<div style='padding:15px;padding-top:0px;width:400px;'>
<p>Dear $receiver_nm,</p>
<p>IDP Report from $employee_nm has been submitted and now need your approval.</p>
<p style='font-weight:bold;'>What should I do?</p>
<p>You can view the report and then approve or disapprove by <a href='http://${linkhost}/hris/ld.php?msg=${email_key1}-${email_key2}-${email_key3}'>clicking here</a>.</p>

<p style='font-weight:bold;'>Where can I get more info?</p>
<p>You can visit our site regarding IDP <a href='http://${linkhost}/hris/index.php?XP_developmentplan_menu=0'>here</a>. Or you
can contact HRIS Administrator.</p>
<p>We will keep you informed about this IDP report through email notification like this. Thank you for your attention.</p>
<p>Sincerely,<br/>
HRIS</p>
</div>";
               break;
            case "_IDP_YOURREPORTHASBEENCOMPLETED":
               eval($message_txt);
               if($report_project_id>0) {
                  $body_msg = "<div style='padding:15px;font-size:20px;color: #666666;'>Your IDP Project Report has been fully approved.</div>
<div style='padding:15px;padding-top:0px;width:400px;'>
<p>Dear $receiver_nm,</p>
<p>We would like to inform you that your IDP Project Report has been fully approved.</p>
<p style='font-weight:bold;'>What should I do?</p>
<p>Nothing. But you can view your report by <a href='http://${linkhost}/hris/ld.php?msg=${email_key1}-${email_key2}-${email_key3}'>clicking here</a>.</p>

<p style='font-weight:bold;'>Where can I get more info?</p>
<p>You can visit our site regarding IDP <a href='http://${linkhost}/hris/index.php?XP_developmentplan_menu=0'>here</a>. Or you
can contact HRIS Administrator.</p>
<p>We will keep you informed about this IDP report through email notification like this. Thank you for your attention.</p>
<p>Sincerely,<br/>
HRIS</p>
</div>";
               } else {
                  $body_msg = "<div style='padding:15px;font-size:20px;color: #666666;'>Your IDP Report has been fully approved.</div>
<div style='padding:15px;padding-top:0px;width:400px;'>
<p>Dear $receiver_nm,</p>
<p>We would like to inform you that your IDP Report has been fully approved.</p>
<p style='font-weight:bold;'>What should I do?</p>
<p>Nothing. But you can view your report by <a href='http://${linkhost}/hris/ld.php?msg=${email_key1}-${email_key2}-${email_key3}'>clicking here</a>.</p>

<p style='font-weight:bold;'>Where can I get more info?</p>
<p>You can visit our site regarding IDP <a href='http://${linkhost}/hris/index.php?XP_developmentplan_menu=0'>here</a>. Or you
can contact HRIS Administrator.</p>
<p>We will keep you informed about this IDP report through email notification like this. Thank you for your attention.</p>
<p>Sincerely,<br/>
HRIS</p>
</div>";
               }
               $dofollow = 1;
               break;
            case "_IDP_YOURREPORTRETURNEDBYDM":
               eval($message_txt);
               if($report_project_id>0) {
                  $body_msg = "<div style='padding:15px;font-size:20px;color: #666666;'>Your IDP Project Report has been disapproved by next superior.</div>
<div style='padding:15px;padding-top:0px;width:400px;'>
<p>Dear $receiver_nm,</p>
<p>We would like to inform you that your IDP Project Report has been been disapproved by next superior.</p>
<p style='font-weight:bold;'>What should I do?</p>
<p>Nothing for now. But you can view disapproval notes by <a href='http://${linkhost}/hris/ld.php?msg=${email_key1}-${email_key2}-${email_key3}'>clicking here</a>.</p>

<p style='font-weight:bold;'>Where can I get more info?</p>
<p>You can visit our site regarding IDP <a href='http://${linkhost}/hris/index.php?XP_developmentplan_menu=0'>here</a>. Or you
can contact HRIS Administrator.</p>
<p>We will keep you informed about this IDP report through email notification like this. Thank you for your attention.</p>
<p>Sincerely,<br/>
HRIS</p>
</div>";
               } else {
                  $body_msg = "<div style='padding:15px;font-size:20px;color: #666666;'>Your IDP Report has been disapproved by next superior.</div>
<div style='padding:15px;padding-top:0px;width:400px;'>
<p>Dear $receiver_nm,</p>
<p>We would like to inform you that your IDP Report has been disapproved by next superior.</p>
<p style='font-weight:bold;'>What should I do?</p>
<p>Nothing for now. But you can view disapproval notes by <a href='http://${linkhost}/hris/ld.php?msg=${email_key1}-${email_key2}-${email_key3}'>clicking here</a>.</p>

<p style='font-weight:bold;'>Where can I get more info?</p>
<p>You can visit our site regarding IDP <a href='http://${linkhost}/hris/index.php?XP_developmentplan_menu=0'>here</a>. Or you
can contact HRIS Administrator.</p>
<p>We will keep you informed about this IDP report through email notification like this. Thank you for your attention.</p>
<p>Sincerely,<br/>
HRIS</p>
</div>";
               }
               $dofollow = 1;
               break;
            case "_IDP_YOURREPORTRETURNED":
               eval($message_txt);
               if($report_project_id>0) {
                  $body_msg = "<div style='padding:15px;font-size:20px;color: #666666;'>Your IDP Project Report has been disapproved by your superior.</div>
<div style='padding:15px;padding-top:0px;width:400px;'>
<p>Dear $receiver_nm,</p>
<p>We would like to inform you that your IDP Project Report has been been disapproved by your superior.</p>
<p style='font-weight:bold;'>What should I do?</p>
<p>Please <a href='http://${linkhost}/hris/ld.php?msg=${email_key1}-${email_key2}-${email_key3}'>click here</a> to view disapproval notes and refine your report.</p>
<p>After that you can resubmit your report again.</p>

<p style='font-weight:bold;'>Where can I get more info?</p>
<p>You can visit our site regarding IDP <a href='http://${linkhost}/hris/index.php?XP_developmentplan_menu=0'>here</a>. Or you
can contact HRIS Administrator.</p>
<p>We will keep you informed about this IDP report through email notification like this. Thank you for your attention.</p>
<p>Sincerely,<br/>
HRIS</p>
</div>";
               } else {
                  $body_msg = "<div style='padding:15px;font-size:20px;color: #666666;'>Your IDP Report has been disapproved by your superior.</div>
<div style='padding:15px;padding-top:0px;width:400px;'>
<p>Dear $receiver_nm,</p>
<p>We would like to inform you that your IDP Report has been been disapproved by your superior.</p>
<p style='font-weight:bold;'>What should I do?</p>
<p>Please <a href='http://${linkhost}/hris/ld.php?msg=${email_key1}-${email_key2}-${email_key3}'>click here</a> to view disapproval notes and refine your report.</p>
<p>After that you can resubmit your report again.</p>

<p style='font-weight:bold;'>Where can I get more info?</p>
<p>You can visit our site regarding IDP <a href='http://${linkhost}/hris/index.php?XP_developmentplan_menu=0'>here</a>. Or you
can contact HRIS Administrator.</p>
<p>We will keep you informed about this IDP report through email notification like this. Thank you for your attention.</p>
<p>Sincerely,<br/>
HRIS</p>
</div>";
               }
               
               break;
            default:
               return;
               break;
         }
         
         $body = "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'><html xmlns='http://www.w3.org/1999/xhtml'>
<head>
<meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1' />
<meta name='author' content='HRIS - Mitsubishi Chemical Indonesia' />
<meta name='copyright' content='Copyright (c) 2005 by HRIS - Mitsubishi Chemical Indonesia' />
<meta name='keywords' content='' />
<meta name='description' content='XOCP Powered HRIS' />
<meta name='generator' content='XOCP 1.0' />
<title>HRIS - Mitsubishi Chemical Indonesia</title>
</head>
<body>

<table width='600' border='0' cellspaccing='0' cellpadding='0'>
<tbody>
<tr><td style='border-right:1px solid #bbb;'><img src='http://${linkhost}/hris/images/logo.gif'/></td><td><img src='http://${linkhost}/hris/images/ocd_logo_20100618.jpg'/></td></tr>
<tr><td colspan='2' style='background-color:#5666b3;'>&nbsp;</td></tr>
<tr><td colspan='2'>

<div width='400' style='padding:15px;padding-top:0px;width:400px;'>
$body_msg
</div>

</td></tr>

<tr><td colspan='2' style='background-color:#5666b3;'>&nbsp;</td></tr>


</tbody>
</table>


</body>
</html>
";


         $created_user_id = getUserID();
         $sql = "INSERT INTO ".XOCP_PREFIX."idp_notifications_email (user_id,send_dttm,expiration_dttm,email_key1,email_key2,email_key3,created_user_id,notification_id)"
              . " VALUES ('$user_id','$send_dttm',DATE_ADD('$send_dttm', INTERVAL 10 DAY),'$email_key1','$email_key2','$email_key3','$created_user_id','$notification_id')";
         $db->query($sql);
         
         
         $file = uniqid("idp_").".html";
         
         // In our example we're opening $filename in append mode.
         // The file pointer is at the bottom of the file hence
         // that's where $somecontent will go when we fwrite() it.
         $filename = XOCP_DOC_ROOT."/tmp/$file";
         if (!$handle = fopen($filename, 'a')) {
              return;
         }
     
         // Write $somecontent to our opened file.
         if (fwrite($handle, $body) === FALSE) {
             return;
         }
     
         _debuglog("Success, asdf write html email to file : $file");
         
         fclose($handle);
         
         /////////////// sending the e-mail /////////////////////
         
         include_once('Mail.php');
         
         
         $recipient = $email;
         $headers["From"]    = "hris@mcci.com";
         $headers["To"]      = $recipient;
         $headers["Subject"] = "[HRIS IDP] ". $subject;
         $headers["MIME-Version"] = "1.0";
         $headers["Content-type"] = "text/html; charset=iso-8859-1";
         
         $smtp = strtoupper($smtp);
         
         switch($smtp) {
            case "MKMS01":
               $params['host'] = '146.67.100.30';
               break;
            case "JKMS01":
               $params['host'] = '192.168.233.30';
               break;
            default:
               $params['host'] = '';
               break;
         }
         
         $params['debug'] = TRUE;
         _dumpvar($params);
         _dumpvar($headers);
         //_debuglog($body);
         
         if($params['host']=="") {
            return;
         }
         
         // Create the mail object using the Mail::factory method
         
         ob_start();
         $mail_object =& Mail::factory('smtp', $params);
         $mail_object->send($recipient, $headers, $body);
         $str = ob_get_contents();
         ob_end_clean();
         
         
         _debuglog($recipient);
         
         _debuglog($str);
         
      }
   }
   
   function _idp_generate_notification_id() {
      $db =& Database::getInstance();
      $y = 2010;
      $notification_y = $new_notification_no = "";
      while(1) {
         $cur_notification_y = sprintf("%05d", date("Y")  - $y);
         $sql = "SELECT notification_y,MAX(notification_no) FROM ".XOCP_PREFIX."idp_notifications_seq"
              . " WHERE notification_y = '$cur_notification_y' GROUP BY notification_y";
         $result = $db->query($sql);
         if($db->getRowsNum($result)==1) {
            list($notification_y,$notification_no)=$db->fetchRow($result);
         } else {
            $notification_y = sprintf("%05d", date("Y") - $y);
            $notification_no = 0;
         }
         $new_notification_no = sprintf("%010d",$notification_no+1);
         $sql = "INSERT INTO ".XOCP_PREFIX."idp_notifications_seq (notification_y,notification_no) VALUES ('$notification_y','$new_notification_no')";
         $db->query($sql);
         if($db->errno()!=1062) {
            break;
         }
      }
      return "$notification_y$new_notification_no";
   }
   
   function _idp_calc_cost_estimate($request_id) {
      $db=&Database::getInstance();
      $ttl_cost = 0;
      
      $sql = "SELECT cost_estimate,status_cd"
           . " FROM ".XOCP_PREFIX."idp_request_actionplan"
           . " WHERE request_id = '$request_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($cost_estimate,$status_cd)=$db->fetchRow($result)) {
            if($status_cd=="nullified") continue;
            if($status_cd=="rejected") continue;
            $ttl_cost = _bctrim(bcadd($ttl_cost,$cost_estimate));
         }
      }
      
      $sql = "SELECT cost_estimate,status_cd"
           . " FROM ".XOCP_PREFIX."idp_project"
           . " WHERE request_id = '$request_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($cost_estimate,$status_cd)=$db->fetchRow($result)) {
            if($status_cd=="nullified") continue;
            $ttl_cost = _bctrim(bcadd($ttl_cost,$cost_estimate));
         }
      }
      
      return $ttl_cost;
   }
   
   function _idp_calc_cost_real($request_id) {
      $db=&Database::getInstance();
      $sql = "SELECT cost_real,status_cd"
           . " FROM ".XOCP_PREFIX."idp_request_actionplan"
           . " WHERE request_id = '$request_id'";
      $result = $db->query($sql);
      $ttl_cost = 0;
      if($db->getRowsNum($result)>0) {
         while(list($cost_real,$status_cd)=$db->fetchRow($result)) {
            if($status_cd=="nullified") continue;
            if($status_cd=="rejected") continue;
            $ttl_cost = _bctrim(bcadd($ttl_cost,$cost_real));
         }
      }
      return $ttl_cost;
   }
   
   function _idp_get_timeframe($request_id) {
      $db=&Database::getInstance();
      
      //// get time frame
      $sql = "SELECT MIN(activity_start_dttm) FROM ".XOCP_PREFIX."idp_project_activities WHERE request_id = '$request_id' AND status_cd = 'normal'";
      $rmin = $db->query($sql);
      if($db->getRowsNum($rmin)>0) {
         list($minstart0)=$db->fetchRow($rmin);
      }
      $sql = "SELECT MAX(activity_stop_dttm) FROM ".XOCP_PREFIX."idp_project_activities WHERE request_id = '$request_id' AND status_cd = 'normal'";
      $rmin = $db->query($sql);
      if($db->getRowsNum($rmin)>0) {
         list($maxstop0)=$db->fetchRow($rmin);
      }
      
      $sql = "SELECT MIN(plan_start_dttm) FROM ".XOCP_PREFIX."idp_request_actionplan WHERE request_id = '$request_id' AND status_cd != 'nullified' AND method_t != 'PROJECT'";
      $rmin = $db->query($sql);
      if($db->getRowsNum($rmin)>0) {
         list($minstart2)=$db->fetchRow($rmin);
      }
      $sql = "SELECT MAX(plan_stop_dttm) FROM ".XOCP_PREFIX."idp_request_actionplan WHERE request_id = '$request_id' AND status_cd != 'nullified' AND method_t != 'PROJECT'";
      $rmin = $db->query($sql);
      if($db->getRowsNum($rmin)>0) {
         list($maxstop2)=$db->fetchRow($rmin);
      }
      
      if($minstart0!=""&&$minstart2!="") {
         $timeframe_start = min($minstart0,$minstart2);
      } else if($minstart0!=""&&$minstart2=="") {
         $timeframe_start = $minstart0;
      } else if($minstart0==""&&$minstart2!="") {
         $timeframe_start = $minstart2;
      } else if($minstart0!=""&&$minstart2=="0000-00-00 00:00:00") {
         $timeframe_start = $minstart0;
      } else if($minstart0=="0000-00-00 00:00:00"&&$minstart2!="") {
         $timeframe_start = $minstart2;
      } else {
         $timeframe_start = "0000-00-00 00:00:00";
      }
      $timeframe_stop = max($maxstop0,$maxstop2);
      
      return array($timeframe_start,$timeframe_stop);
      
   }
   
   function _idp_view_request($employee_id,$job_id,$back_button=TRUE,$new_button=TRUE) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $db=&Database::getInstance();
      $user_id = getUserID();
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_idprequest.php");
      $ajax = new _hris_class_IDPRequestAjax("rqjx");
      
      list($job_id,
           $employee_idx,
           $job_nm,
           $nm,
           $nip,
           $gender,
           $jobstart,
           $entrance_dttm,
           $jobage,
           $job_summary,
           $person_id,
           $user_idx,
           $first_assessor_job_id,
           $next_assessor_job_id)=_hris_getinfobyemployeeid($employee_id);
      
      $sql = "SELECT c.job_id,b.employee_id"
           . " FROM ".XOCP_PREFIX."users a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(person_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job c USING(employee_id)"
           . " WHERE a.user_id = '$user_id'";
      $result = $db->query($sql);
      list($self_job_id,$self_employee_id)=$db->fetchRow($result);
      
      if($self_employee_id==$employee_id) {
         $self = TRUE;
      } else {
         $self = FALSE;
      }
      
      $sql = "SELECT c.job_nm,c.job_abbr,d.org_nm,d.org_abbr,a.employee_ext_id,e.person_nm,e.person_id"
           . " FROM ".XOCP_PREFIX."employee a"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job b ON b.employee_id = a.employee_id AND b.job_id = '$job_id'"
           . " LEFT JOIN ".XOCP_PREFIX."jobs c ON c.job_id = '$job_id'"
           . " LEFT JOIN ".XOCP_PREFIX."orgs d ON d.org_id = c.org_id"
           . " LEFT JOIN ".XOCP_PREFIX."persons e ON e.person_id = a.person_id"
           . " WHERE a.employee_id = '$employee_id'";
      $result = $db->query($sql);
      list($job_nm,$job_abbr,$org_nm,$org_abbr,$nip,$employee_nm,$person_id)=$db->fetchRow($result);
      
      $empl = "<div style='padding-top:10px;padding-bottom:10px;'>"
            . "<table style='margin-left:20px;'><tr><td style='-moz-box-shadow:1px 1px 4px #999;padding:4px;border:1px solid #bbb;'><img src='".XOCP_SERVER_SUBDIR."/modules/hris/thumb.php?pid=${person_id}' height='100'/></td>"
            . "<td style='vertical-align:top;padding-left:10px;'>"
            
            . "<table style='font-weight:bold;margin-left:0px;font-size:1.1em;'><colgroup><col width='120'/><col/></colgroup><tbody>"
            . "<tr><td>Job Title</td><td>: $job_nm ($job_abbr)</td></tr>"
            . "<tr><td>Section/Division</td><td>: $org_nm ($org_abbr)</td></tr>"
            . "<tr><td>Incumbent</td><td>: $employee_nm</td></tr>"
            . "<tr><td>NIP</td><td>: $nip</td></tr>"
            . "</tbody></table>"
            
            . "</td></tr></table></div>";
      
      list($cur_year)=explode("-",getSQLDate());
      
      $sql = "SELECT request_id,request_t,timeframe_start,timeframe_stop,status_cd"
           . " FROM ".XOCP_PREFIX."idp_request"
           . " WHERE employee_id = '$employee_id'"
           . " AND created_dttm >= '$cur_year-01-01 00:00:00'"
           . " ORDER BY created_dttm DESC";
      $result = $db->query($sql);
      $empty_display = "";
      $cnt = 0;
      if($db->getRowsNum($result)>0) {
         while(list($request_id,$request_t,$timeframe_start,$timeframe_stop,$status_cd)=$db->fetchRow($result)) {
            if($status_cd=="nullified") continue;
            $cnt++;
            $req = $ajax->renderRequest($request_id);
            if($req!="FAIL") {
               list($x,$request) = $req;
            } else {
               continue;
            }
            $idplist .= "<div id='dvl_${request_id}' style='padding:5px;'><span class='xlnk' onclick='new Effect.toggle(\"xidp_${request_id}\",\"appear\");'>ID : $request_id</span></div><div id='xidp_${request_id}' style='padding:5px;border-bottom:1px solid #bbb;".($cnt>1?"display:none;":"")."'>$request</div>";
         }
         if($cnt>0) {
            $empty_display = "display:none;";
         }
      } else {
      
      }
      
      $ret = "<div style='max-width:980px;'><div>"
           . "<table style='width:100%;' class='xxlist'><thead><tr>"
               . "<td>"
               . ($back_button==TRUE?"<a href='".XOCP_SERVER_SUBDIR."/index.php'><img src='".XOCP_SERVER_SUBDIR."/images/return.gif'/></a>&nbsp;":"")
               . "Individual Development Program (IDP)</td>"
           . "</tr></thead><tbody>"
           . "</tbody></table>"
           . "</div>"
           . $empl
      
           . "<div style='margin-bottom:100px;'>"
           . "<table style='width:100%;' class='xxlist'><thead><tr>"
               . "<td style='text-align:center;background-color:#eee;font-weight:bold;border-bottom:0px;'>List of IDP <input type='button' value='New IDP' style='".($new_button==TRUE?"":"display:none;")."float:right;' onclick='new_idp();'/></td>"
           . "</tr></thead><tbody>"
           . "<tr><td id='tdhdr' style='padding-top:0px;padding-bottom:0px;'>"
           . "</td></tr>"
           . "<tr><td id='tdcontent' style='padding:0px;'>"
               . $idplist
               . "<div id='empty_list' style='padding:5px;font-style:italic;text-align:center;color:#999;${empty_display}'>"._EMPTY."</div>"
               . "</td></tr>"
           . "</tbody></table>"
           . "</div>"
           . "<div id='cbBox' class='glassbox'><div id='innercbbox' style='padding:0px;'></div></div>"
           . "<div id='wizardbox' class='glassbox'><div id='innerwizardbox' style='padding:0px;'></div></div>"
           . "<div id='wizempbox' class='glassbox'><div id='innerwizempbox' style='padding:0px;'></div></div>"
           . "</div>";
      
      $ret .= $ajax->getJs()."<script src='".XOCP_SERVER_SUBDIR."/include/calendar.js' type='text/javascript'></script>"
            . "<script type='text/javascript'><!--
      
      function view_competency_revision(request_id,competency_id,d,e) {
         var dv = $('rev_comp_'+request_id+'_'+competency_id);
         if(dv) {
            if(dv.style.display=='none') {
               dv.style.left = parseInt(oX(d))+'px';
               dv.style.top = parseInt(oY(d)+d.offsetHeight)+'px';
               dv.style.display = '';
            } else {
               dv.style.display = 'none';
            }
         }
      }
      
      function confirm_revise_request(request_id) {
         rqjx_app_reviseRequest(request_id,function(_data) {
            var data = recjsarray(_data);
            $('xidp_'+data[0]).innerHTML = data[1];
            reqrevbox.fade();
         });
      }
      
      var reqrevedit = null;
      var reqrevbox = null;
      function revise_request(request_id) {
         reqrevedit = _dce('div');
         reqrevedit.setAttribute('id','reqrevedit');
         reqrevedit = document.body.appendChild(reqrevedit);
         reqrevedit.sub = reqrevedit.appendChild(_dce('div'));
         reqrevedit.sub.setAttribute('id','innerreqrevedit');
         reqrevbox = new GlassBox();
         reqrevbox.init('reqrevedit','500px','170px','hidden','default',false,false);
         reqrevbox.lbo(false,0.3);
         reqrevbox.appear();
         
         $('innerreqrevedit').innerHTML = '<div style=\"padding:20px;text-align:center;\">You are going to revise this IDP request.'
                                   + '<br/><br/>Are you sure?'
                                   + '<br/><br/>'
                                   + '<input type=\"button\" value=\"Yes (revise)\" onclick=\"confirm_revise_request(\\''+request_id+'\\');\"/>&nbsp;&nbsp;'
                                   + '<input type=\"button\" value=\"No\" onclick=\"reqrevbox.fade();\"/></div>';
      }
      
      
      function print_idp(request_id) {
         location.href = '".XOCP_SERVER_SUBDIR."/modules/hris/print/idp.php?r='+request_id;
      }
      
      var wizard_request_id = 0;
      var wizard_employee_id = 0;
      var wizard_job_id = 0;
      var wizard_box = null;
      var wizard_mode = 0;
      SetCookie('wizard_mode','0');
      var wizard_current_step = 0;
      var wizard_step = new Array(
         
         //// STEP 0 : Add Competency
         function() {
            reset_wizard_box();
            wizard_box = new GlassBox();
            wizard_box.init( 'wizardbox', '700px', '525px', 'hidden', 'default' ,false,false);
            wizard_box.lbo(false,0.3 );
            wizard_box_exitbutton(wizard_box);
            wizard_box.appear();
            rqjx_app_getCompetencyGap(wizard_request_id,wizard_employee_id,wizard_job_id,function(_data) {
               $('innerwizardbox').innerHTML = _data;
            });
         },
         
         //// STEP 1 : Competency Summary
         function() {
            wizard_project_list = Array();
            wizard_project_current = 0;
            reset_wizard_box();
            wizard_box = new GlassBox();
            wizard_box.init( 'wizardbox', '700px', '503px', 'hidden', 'default' ,false,false);
            wizard_box.lbo(false,0.3 ); 
            wizard_box_exitbutton(wizard_box);
            wizard_box.appear();
            rqjx_app_wizardCompetencyReview(wizard_request_id,wizard_employee_id,wizard_job_id,0,function(_data) {
               $('innerwizardbox').innerHTML = _data;
            });
         },
         
         //// STEP 2 : Edit Focus of Development
         function() {
            reset_wizard_box();
            wizard_box = new GlassBox();
            wizard_box.init( 'wizardbox', '700px', '252px', 'hidden', 'default' ,false,false);
            wizard_box.lbo(false,0.3 ); 
            wizard_box_exitbutton(wizard_box);
            wizard_box.appear();
            rqjx_app_wizardFocusDev(wizard_request_id,wizard_employee_id,wizard_job_id,wizard_focusdev_no_current,function(_data) {
               var data = recjsarray(_data);
               $('innerwizardbox').innerHTML = data[0];
               wizard_focusdev_list = data[1];
               var competency_id = wizard_focusdev_list[wizard_focusdev_no_current];
               setTimeout(\"$('editfocusdev_\"+wizard_request_id+\"_\"+competency_id+\"').focus();\",50);
            });
         },
         
         //// STEP 3 : Edit Assignment Action Plan
         function() {
            var project_id = 0;
            if(wizard_project_list&&wizard_project_list[wizard_project_current]) {
               project_id = wizard_project_list[wizard_project_current];
            }
            rqjx_app_wizardProjectTitle(wizard_request_id,project_id,0,function(_data) {
               var data = recjsarray(_data);
               var xprojobj = $('xproj_'+data[0]+'_'+data[1]);
               if(!xprojobj) {
                  var dv = $('proj_'+wizard_request_id);
                  dv.dv = _dce('div');
                  dv.dv.setAttribute('style','padding:3px;padding-left:0px;');
                  dv.dv = dv.insertBefore(dv.dv,dv.firstChild);
                  dv.dv.setAttribute('id','xproj_'+data[0]+'_'+data[1]);
                  dv.dv.innerHTML = data[5];
               }
               reset_wizard_box();
               wizard_box = new GlassBox();
               wizard_box.init( 'wizardbox', '700px', data[3], 'hidden', 'default' ,false,false);
               wizard_box.lbo(false,0.3 );
               wizard_project_list = data[4];
               $('innerwizardbox').innerHTML = data[2];
               wizard_box_exitbutton(wizard_box);
               wizard_box.appear();
            });
         },
         
         //// STEP 4 : Finish Wizard
         function() {
            reset_wizard_box();
            wizard_box = new GlassBox();
            wizard_box.init( 'wizardbox', '700px', '225px', 'hidden', 'default' ,false,false);
            wizard_box.lbo(false,0.3 ); 
            wizard_box_exitbutton(wizard_box);
            wizard_box.appear();
            rqjx_app_wizardFinish(wizard_request_id,wizard_employee_id,wizard_job_id,function(_data) {
               $('innerwizardbox').innerHTML = _data;
            });
         },
         
         null
         
      );
      
      function wizard_review_orderby_group() {
         rqjx_app_wizardCompetencyReview(wizard_request_id,wizard_employee_id,wizard_job_id,0,function(_data) {
            $('innerwizardbox').innerHTML = _data;
         });
      }
      
      function wizard_review_orderby_priority() {
         rqjx_app_wizardCompetencyReview(wizard_request_id,wizard_employee_id,wizard_job_id,1,function(_data) {
            $('innerwizardbox').innerHTML = _data;
         });
      }
      
      var wizard_project_list = Array();
      var wizard_project_current = 0;
      function wizard_project_next() {
         ajax_feedback = _caf;
         /// save first
         var project_id = wizard_project_list[wizard_project_current];
         if(project_id) {
            var project_nm = urlencode($('editprojnm_'+wizard_request_id+'_'+project_id).value);
            var retcomp = _parseForm('dvprojcomplist');
            rqjx_app_saveProjectName(wizard_request_id,project_id,project_nm,0,retcomp,function(_data) {
               var data = recjsarray(_data);
               $('projectnm_'+data[0]+'_'+data[1]).innerHTML = data[2];
               $('idpcost_'+data[0]).innerHTML = data[3];
               for(var i=0;i<data[4].length;i++) {
                  if($('actlist_'+data[4][i][0]+'_'+data[4][i][1])) {
                     $('actlist_'+data[4][i][0]+'_'+data[4][i][1]).innerHTML = data[4][i][2];
                  }
               }
            });
         }
         var n = wizard_project_current+1;
         if(wizard_project_list[n]) {
            rqjx_app_wizardProjectTitle(wizard_request_id,wizard_project_list[n],0,function(_data) {
               var data = recjsarray(_data);
               var xprojobj = $('xproj_'+data[0]+'_'+data[1]);
               if(!xprojobj) {
                  var dv = $('proj_'+wizard_request_id);
                  dv.dv = _dce('div');
                  dv.dv.appendChild(progress_span());
                  dv.dv.setAttribute('style','padding:3px;padding-left:0px;');
                  dv.dv = dv.insertBefore(dv.dv,dv.firstChild);
                  dv.dv.setAttribute('id','xproj_'+data[0]+'_'+data[1]);
                  dv.dv.innerHTML = _data[5];
               }
               
               reset_wizard_box();
               wizard_box = new GlassBox();
               wizard_box.init( 'wizardbox', '700px', data[3], 'hidden', 'default' ,false,false);
               wizard_box.lbo(false,0.3 );
               wizard_project_list = data[4];
               wizard_project_current++;
               $('innerwizardbox').innerHTML = data[2];
               wizard_box_exitbutton(wizard_box);
               wizard_box.appear();
            });
         } else {
            rqjx_app_wizardProjectTitle(wizard_request_id,0,2,function(_data) {
               if(_data=='NO_MORE') {
                  wizard_run_next();
                  return;
               }
               var data = recjsarray(_data);
               var xprojobj = $('xproj_'+data[0]+'_'+data[1]);
               if(!xprojobj) {
                  var dv = $('proj_'+wizard_request_id);
                  dv.dv = _dce('div');
                  dv.dv.appendChild(progress_span());
                  dv.dv.setAttribute('style','padding:3px;padding-left:0px;');
                  dv.dv = dv.appendChild(dv.dv);
                  dv.dv.setAttribute('id','xproj_'+data[0]+'_'+data[1]);
                  dv.dv.innerHTML = data[5];
               }
               reset_wizard_box();
               wizard_box = new GlassBox();
               wizard_box.init( 'wizardbox', '700px', data[3], 'hidden', 'default' ,false,false);
               wizard_box.lbo(false,0.3 );
               wizard_project_list = data[4];
               wizard_project_current++;
               $('innerwizardbox').innerHTML = data[2];
               wizard_box_exitbutton(wizard_box);
               wizard_box.appear();
            });
            
         }
      }
      
      function wizard_project_prev() {
         /// save first
         var project_id = wizard_project_list[wizard_project_current];
         var project_nm = urlencode($('editprojnm_'+wizard_request_id+'_'+project_id).value);
         var retcomp = _parseForm('dvprojcomplist');
         rqjx_app_saveProjectName(wizard_request_id,project_id,project_nm,0,retcomp,function(_data) {
            var data = recjsarray(_data);
            $('projectnm_'+data[0]+'_'+data[1]).innerHTML = data[2];
            $('idpcost_'+data[0]).innerHTML = data[3];
            for(var i=0;i<data[4].length;i++) {
               if($('actlist_'+data[4][i][0]+'_'+data[4][i][1])) {
                  $('actlist_'+data[4][i][0]+'_'+data[4][i][1]).innerHTML = data[4][i][2];
               }
            }
         });
         var n = wizard_project_current-1;
         if(wizard_project_list[n]) {
            rqjx_app_wizardProjectTitle(wizard_request_id,wizard_project_list[n],0,function(_data) {
               var data = recjsarray(_data);
               var xprojobj = $('xproj_'+data[0]+'_'+data[1]);
               if(!xprojobj) {
                  var dv = $('proj_'+wizard_request_id);
                  dv.dv = _dce('div');
                  dv.dv.appendChild(progress_span());
                  dv.dv.setAttribute('style','padding:3px;padding-left:0px;');
                  dv.dv = dv.insertBefore(dv.dv,dv.firstChild);
                  dv.dv.setAttribute('id','xproj_'+data[0]+'_'+data[1]);
                  dv.dv.innerHTML = _data[5];
               }
               reset_wizard_box();
               wizard_box = new GlassBox();
               wizard_box.init( 'wizardbox', '700px', data[3], 'hidden', 'default' ,false,false);
               wizard_box.lbo(false,0.3 );
               wizard_project_list = data[4];
               wizard_project_current--;
               $('innerwizardbox').innerHTML = data[2];
               wizard_box_exitbutton(wizard_box);
               wizard_box.appear();
            });
         } else {
            wizard_stop();
         }
      }
      
      function wizard_project_new() {
         /// save first
         var project_id = wizard_project_list[wizard_project_current];
         var project_nm = urlencode($('editprojnm_'+wizard_request_id+'_'+project_id).value);
         var retcomp = _parseForm('dvprojcomplist');
         rqjx_app_saveProjectName(wizard_request_id,project_id,project_nm,0,retcomp,function(_data) {
            var data = recjsarray(_data);
            $('projectnm_'+data[0]+'_'+data[1]).innerHTML = data[2];
            $('idpcost_'+data[0]).innerHTML = data[3];
            for(var i=0;i<data[4].length;i++) {
               if($('actlist_'+data[4][i][0]+'_'+data[4][i][1])) {
                  $('actlist_'+data[4][i][0]+'_'+data[4][i][1]).innerHTML = data[4][i][2];
               }
            }
         });
         ajax_feedback = _caf;
         rqjx_app_wizardProjectTitle(wizard_request_id,0,1,function(_data) {
            var data = recjsarray(_data);
            var xprojobj = $('xproj_'+data[0]+'_'+data[1]);
            if(!xprojobj) {
               var dv = $('proj_'+wizard_request_id);
               dv.dv = _dce('div');
               dv.dv.appendChild(progress_span());
               dv.dv.setAttribute('style','padding:3px;padding-left:0px;');
               dv.dv = dv.appendChild(dv.dv);
               dv.dv.setAttribute('id','xproj_'+data[0]+'_'+data[1]);
               dv.dv.innerHTML = data[5];
            }
            reset_wizard_box();
            wizard_box = new GlassBox();
            wizard_box.init( 'wizardbox', '700px', data[3], 'hidden', 'default' ,false,false);
            wizard_box.lbo(false,0.3 );
            wizard_project_list = data[4];
            wizard_project_current++;
            $('innerwizardbox').innerHTML = data[2];
            wizard_box_exitbutton(wizard_box);
            wizard_box.appear();
         });
      }
      
      function wizard_project_finish() {
         var project_id = wizard_project_list[wizard_project_current];
         var project_nm = urlencode($('editprojnm_'+wizard_request_id+'_'+project_id).value);
         var retcomp = _parseForm('dvprojcomplist');
         rqjx_app_saveProjectName(wizard_request_id,project_id,project_nm,0,retcomp,function(_data) {
            var data = recjsarray(_data);
            $('projectnm_'+data[0]+'_'+data[1]).innerHTML = data[2];
            $('idpcost_'+data[0]).innerHTML = data[3];
            for(var i=0;i<data[4].length;i++) {
               if($('actlist_'+data[4][i][0]+'_'+data[4][i][1])) {
                  $('actlist_'+data[4][i][0]+'_'+data[4][i][1]).innerHTML = data[4][i][2];
               }
            }
            wizard_stop();
         });
      }
      
      var wizard_focusdev_list = Array();
      var wizard_focusdev_no_prev = 0;
      var wizard_focusdev_no_next = 0;
      var wizard_focusdev_no_current = 0;
      function wizard_focusdev_next() {
         var competency_id = wizard_focusdev_list[wizard_focusdev_no_current];
         var focus_dev = urlencode($('editfocusdev_'+wizard_request_id+'_'+competency_id).value);
         rqjx_app_saveFocusDevelopment(wizard_request_id,competency_id,focus_dev,function(_data) {
            var data = recjsarray(_data);
            $('focus_'+data[0]+'_'+data[1]).innerHTML = data[2];
         });
         var n = wizard_focusdev_no_current+1;
         if(wizard_focusdev_list[n]) {
            rqjx_app_wizardFocusDev(wizard_request_id,wizard_employee_id,wizard_job_id,n,function(_data) {
               var data = recjsarray(_data);
               reset_wizard_box();
               wizard_box = new GlassBox();
               wizard_box.init( 'wizardbox', '700px', '255px', 'hidden', 'default' ,false,false);
               wizard_box.lbo(false,0.3 ); 
               $('innerwizardbox').innerHTML = data[0];
               wizard_box_exitbutton(wizard_box);
               wizard_box.appear();
               wizard_focusdev_no_current++;
               var competency_id = wizard_focusdev_list[wizard_focusdev_no_current];
               setTimeout(\"$('editfocusdev_\"+wizard_request_id+\"_\"+competency_id+\"').focus();\",50);
            });
         } else {
            wizard_run_next();
         }
      }
      
      function wizard_focusdev_prev() {
         var competency_id = wizard_focusdev_list[wizard_focusdev_no_current];
         var focus_dev = urlencode($('editfocusdev_'+wizard_request_id+'_'+competency_id).value);
         rqjx_app_saveFocusDevelopment(wizard_request_id,competency_id,focus_dev,function(_data) {
            var data = recjsarray(_data);
            $('focus_'+data[0]+'_'+data[1]).innerHTML = data[2];
         });
         var n = wizard_focusdev_no_current-1;
         if(wizard_focusdev_list[n]) {
            rqjx_app_wizardFocusDev(wizard_request_id,wizard_employee_id,wizard_job_id,n,function(_data) {
               var data = recjsarray(_data);
               reset_wizard_box();
               wizard_box = new GlassBox();
               wizard_box.init( 'wizardbox', '700px', '255px', 'hidden', 'default' ,false,false);
               wizard_box.lbo(false,0.3 ); 
               $('innerwizardbox').innerHTML = data[0];
               wizard_box_exitbutton(wizard_box);
               wizard_box.appear();
               wizard_focusdev_no_current--;
               var competency_id = wizard_focusdev_list[wizard_focusdev_no_current];
               setTimeout(\"$('editfocusdev_\"+wizard_request_id+\"_\"+competency_id+\"').focus();\",50);
            });
         } else {
            wizard_run_prev();
         }
      }
      
      function chcost(inp_id,d,e) {
         var k = getkeyc(e);
         if(d.chgt) {
            d.chgt.reset();
            d.chgt = null;
         }
         d.chgt = new ctimer('setspcost(\"'+inp_id+'\");',10);
         d.chgt.start();
      }
      
      function setspcost(inp_id) {
         var d = parseInt($(inp_id).value);
         $('spcost').innerHTML = thSep(d);
      }
      
      function hide_add_comment() {
         return;
         _destroy(imgcomm);
         imgcomm = null;
      }
      
      var imgcomm = null;
      function show_add_comment(request_id,competency_id,d,e) {
         if(!imgcomm) {
            imgcomm = _dce('img');
            imgcomm.setAttribute('src','".XOCP_SERVER_SUBDIR."/images/add.png');
            imgcomm.setAttribute('style','position:absolute;cursor:pointer;');
            imgcomm.setAttribute('title','Add comment');
            imgcomm = document.body.appendChild(imgcomm);
            imgcomm.style.top = parseInt(oY(d)+d.offsetHeight)+'px';
            imgcomm.style.left = parseInt(oX(d)+d.offsetWidth)+'px';
         }
         imgcomm.style.display = '';
         imgcomm.style.top = parseInt(oY(d)+d.offsetHeight-imgcomm.offsetHeight)+'px';
         imgcomm.style.left = parseInt(oX(d)+d.offsetWidth-imgcomm.offsetWidth)+'px';
      }
      
      function projtab(tabno,d,e) {
         var p = d.parentNode.parentNode;
         var tabcount = p.childNodes.length;
         for(var i=0;i<tabcount;i++) {
            if($('projlitab_'+i)) {
               if(tabno==i) {
                  $('projlitab_'+i).className = 'ultabsel_greyrev';
               } else {
                  $('projlitab_'+i).className = '';
               }
            }
            if($('projdvtab_'+i)) {
               $('projdvtab_'+i).style.display = 'none';
            }
         }
         
         var dv = $('projdvtab_'+tabno);
         if(dv) {
            dv.style.display = '';
         }
      }
      
      
      function cancel_edit_project_name(request_id,project_id) {
         $('projectname_'+request_id+'_'+project_id).innerHTML = $('projectname_'+request_id+'_'+project_id).oldHTML;
      }
      
      function save_project_name(request_id,project_id,d,e) {
         var project_name = urlencode($('editprojname_'+request_id+'_'+project_id).value);
         $('projectname_'+request_id+'_'+project_id).innerHTML = '';
         $('projectname_'+request_id+'_'+project_id).appendChild(progress_span());
         rqjx_app_saveProjectName(request_id,project_id,project_name,function(_data) {
            var data = recjsarray(_data);
            $('projectname_'+data[0]+'_'+data[1]).innerHTML = data[2];
            $('titleproject_'+data[0]+'_'+data[1]).innerHTML = data[3];
            for(var i=0;i<data[4].length;i++) {
               if(isset($('actlist_'+data[4][i][0]+'_'+data[4][i][1]))) {
                  $('actlist_'+data[4][i][0]+'_'+data[4][i][1]).innerHTML = data[4][i][2];
               }
            }
         });
      }
      
      function edit_project_name(request_id,project_id,d,e) {
         $('projectname_'+request_id+'_'+project_id).oldHTML = $('projectname_'+request_id+'_'+project_id).innerHTML;
         $('projectname_'+request_id+'_'+project_id).innerHTML = '';
         $('projectname_'+request_id+'_'+project_id).appendChild(progress_span());
         rqjx_app_editProjectName(request_id,project_id,function(_data) {
            var data = recjsarray(_data);
            $('projectname_'+data[0]+'_'+data[1]).innerHTML = data[2];
            $('editprojname_'+data[0]+'_'+data[1]).focus();
         });
      }
      
      
      var idpprojdv = null;
      function edit_project(request_id,project_id,d,e) {
         if(idpprojdv) {
            _destroy(idpprojdv);
            if(idpprojdv.request_id==request_id&&idpprojdv.project_id==project_id) {
               idpprojdv.request_id = null;
               idpprojdv.project_id = null;
               idpprojdv = null;
               return;
            }
         }
         var ctbl = $('ptbl_'+request_id+'_'+project_id);
         ctbl.dv = _dce('div');
         ctbl.dv.appendChild(progress_span());
         idpprojdv = ctbl.parentNode.appendChild(ctbl.dv);
         idpprojdv.request_id = request_id;
         idpprojdv.project_id = project_id;
         rqjx_app_editProject(request_id,project_id,function(_data) {
            idpprojdv.innerHTML = _data;
         });
         
      }
      
      function add_project(request_id,employee_id,d,e) {
         var dv = $('proj_'+request_id);
         dv.dv = _dce('div');
         dv.dv.appendChild(progress_span());
         dv.dv.setAttribute('style','padding:3px;padding-left:0px;');
         dv.dv = dv.appendChild(dv.dv);
         dv.dv.setAttribute('id','xproj_'+request_id+'_new');
         $('empty_project_'+request_id).style.display = 'none';
         rqjx_app_addProject(request_id,employee_id,function(_data) {
            if(_data=='FAIL') {
               alert('Insert failed. Please contact administrator.');
               return;
            }
            var data = recjsarray(_data);
            if($('xproj_'+data[0]+'_new')) {
               $('xproj_'+data[0]+'_new').setAttribute('id','xproj_'+data[0]+'_'+data[1]);
               $('xproj_'+data[0]+'_'+data[1]).innerHTML = data[2];
               edit_project_nm(data[0],data[1],null,null);
            }
         });
      }
      
      function pnltab(tabno,d,e) {
         var p = d.parentNode;
         var tabcount = p.childNodes.length;
         for(var i=0;i<tabcount;i++) {
            if($('litab_'+i)) {
               if(tabno==i) {
                  $('litab_'+i).className = 'ultabsel_greyrev';
               } else {
                  $('litab_'+i).className = '';
               }
            }
            if($('dvtab_'+i)) {
               $('dvtab_'+i).style.display = 'none';
            }
         }
         
         var dv = $('dvtab_'+tabno);
         if(dv) {
            dv.style.display = '';
         }
      }
      
      function comptab(tabno,d,e) {
         var p = d.parentNode.parentNode;
         var tabcount = p.childNodes.length;
         for(var i=0;i<tabcount;i++) {
            if($('complitab_'+i)) {
               if(tabno==i) {
                  $('complitab_'+i).className = 'ultabsel_greyrev';
               } else {
                  $('complitab_'+i).className = '';
               }
            }
            if($('compdvtab_'+i)) {
               $('compdvtab_'+i).style.display = 'none';
            }
         }
         
         var dv = $('compdvtab_'+tabno);
         if(dv) {
            dv.style.display = '';
         }
      }
      
      function aptab(tabno,d,e) {
         var p = d.parentNode.parentNode;
         var tabcount = p.childNodes.length;
         for(var i=0;i<tabcount;i++) {
            if($('aptab_'+i)) {
               if(tabno==i) {
                  $('aptab_'+i).className = 'ultabsel_greyrev';
               } else {
                  $('aptab_'+i).className = '';
               }
            }
            if($('apdvtab_'+i)) {
               $('apdvtab_'+i).style.display = 'none';
            }
         }
         
         var dv = $('apdvtab_'+tabno);
         if(dv) {
            dv.style.display = '';
         }
      }
      
      var idpcompdv = null;
      function edit_idp_competency(request_id,competency_id,d,e) {
         if(idpcompdv) {
            _destroy(idpcompdv);
            if(idpcompdv.request_id==request_id&&idpcompdv.competency_id==competency_id) {
               idpcompdv.request_id = null;
               idpcompdv.competency_id = null;
               idpcompdv = null;
               return;
            }
         }
         var ctbl = $('ctbl_'+request_id+'_'+competency_id);
         ctbl.dv = _dce('div');
         ctbl.dv.appendChild(progress_span());
         idpcompdv = ctbl.parentNode.appendChild(ctbl.dv);
         idpcompdv.request_id = request_id;
         idpcompdv.competency_id = competency_id;
         rqjx_app_editIDPCompetency(request_id,competency_id,function(_data) {
            idpcompdv.innerHTML = _data;
         });
      }
      
      function do_delete_ap(request_id,actionplan_id,d,e) {
         var apfrm = $('apformeditor');
         if(apfrm) {
            apfrm.innerHTML = '';
            apfrm.appendChild(progress_span());
            rqjx_app_deleteActionPlan(request_id,actionplan_id,function(_data) {
               if(_data=='FAIL') {
                  alert('Fail to delete action plan. Please contact administrator.');
                  return;
               }
               if(apbox&&apbox.fade) {
                  apbox.fade();
               }
               var data = recjsarray(_data);
               $('actlist_'+data[0]+'_'+data[1]).innerHTML = data[2];
               $('idptimeframe_start_'+data[0]).innerHTML = data[3];
               $('idptimeframe_stop_'+data[0]).innerHTML = data[4];
               $('idpcost_'+data[0]).innerHTML = data[5];
            });
         }
      }
      
      function cancel_delete_ap(request_id,actionplan_id,d,e) {
         var apfrm = $('apformeditor');
         apfrm.innerHTML = apfrm.oldHTML;
         $('apformbtn').style.display = '';
      }
      
      function delete_ap(request_id,actionplan_id,method_type,d,e) {
         var apfrm = $('apformeditor');
         apfrm.oldHTML = apfrm.innerHTML;
         apfrm.innerHTML = '<div style=\"text-align:center;\"><br/><br/>You are going to delete this development method:<br/><br/><span style=\"font-weight:bold;\";>'+method_type+'</span><br/><br/>'
                         + 'Are you sure?<br/><br/>'
                         + '<input type=\"button\" value=\"Yes (delete)\" onclick=\"do_delete_ap(\\''+request_id+'\\',\\''+actionplan_id+'\\',this,event);\"/>&nbsp;&nbsp;'
                         + '<input type=\"button\" value=\"No\" onclick=\"cancel_delete_ap(\\''+request_id+'\\',\\''+actionplan_id+'\\',this,event);\"/>'
                         + '</div>';
         $('apformbtn').style.display = 'none';
      }
      
      function save_ap(method_t,request_id,actionplan_id,ret) {
         if(apbox) {
            apbox.fade();
         }
         rqjx_app_saveActionPlan(method_t,request_id,actionplan_id,ret,function(_data) {
            var data = recjsarray(_data);
            $('actlist_'+data[0]+'_'+data[1]).innerHTML = data[2];
            $('idptimeframe_start_'+data[0]).innerHTML = data[3];
            $('idptimeframe_stop_'+data[0]).innerHTML = data[4];
            $('idpcost_'+data[0]).innerHTML = data[5];
         });
      }
      
      function add_ap(request_id,competency_id,method_t) {
         _destroy(mb);
         mb = null;
         rqjx_app_addActionPlan(request_id,competency_id,method_t,function(_data) {
            if(_data=='FAIL') {
               alert('Fail to add action plan. Please contact administrator.');
               return;
            }
            var data = recjsarray(_data);
            $('actlist_'+data[0]+'_'+data[1]).innerHTML = data[2];
            $('idptimeframe_start_'+data[0]).innerHTML = data[3];
            $('idptimeframe_stop_'+data[0]).innerHTML = data[4];
            wizemp_ap_list = data[5];
            wizemp_ap_current = data[6];
         });
      }
      
      var mb = null;
      var methodedit = null;
      var methodbox = null
      function select_method(request_id,competency_id,d,e) {
         methodedit = _dce('div');
         methodedit.setAttribute('id','methodedit');
         methodedit = document.body.appendChild(methodedit);
         methodedit.sub = methodedit.appendChild(_dce('div'));
         methodedit.sub.setAttribute('id','innermethodedit');
         rqjx_app_getMethodType(request_id,competency_id,function(_data) {
            var data = recjsarray(_data);
            $('innermethodedit').innerHTML = data[0];
            methodbox = new GlassBox();
            methodbox.init('methodedit','400px',data[1]+'px','hidden','default',false,false);
            methodbox.lbo(false,0.3);
            methodbox.appear();
         });
      }
      
      
      
      
      function confirm_delete_competency(request_id,competency_id) {
         $('innercompdeledit').innerHTML = '';
         $('innercompdeledit').appendChild(progress_span());
         rqjx_app_deleteCompetency(request_id,competency_id,function(_data) {
            var data = recjsarray(_data);
            _destroy($('xcomp_'+data[0]+'_'+data[1]));
            $('comp_'+data[0]).innerHTML = data[7];
            compdelbox.fade();
         });
      }
      
      var compdeledit = null;
      var compdelbox = null;
      function delete_competency(request_id,competency_id,competency_abbr,competency_nm) {
         compdeledit = _dce('div');
         compdeledit.setAttribute('idllll','compdeledit');
         compdeledit = document.body.appendChild(compdeledit);
         compdeledit.sub = compdeledit.appendChild(_dce('div'));
         compdeledit.sub.setAttribute('id','innercompdeledit');
         compdelbox = new GlassBox();
         compdelbox.init('compdeledit','500px','190px','hidden','default',false,false);
         compdelbox.lbo(false,0.3);
         compdelbox.appear();
         
         $('innercompdeledit').innerHTML = '<div style=\"padding:20px;text-align:center;\">You are going to delete this competency.'
                                   + '<br/><br/><span style=\"font-weight:bold;\">'+competency_abbr+' - '+competency_nm+'</span>'
                                   + '<br/><br/>Are you sure?'
                                   + '<br/><br/>'
                                   + '<input type=\"button\" value=\"Yes (delete)\" onclick=\"confirm_delete_competency(\\''+request_id+'\\',\\''+competency_id+'\\');\"/>&nbsp;'
                                   + '<input type=\"button\" value=\"No\" onclick=\"compdelbox.fade();\"/></div>';
      }
      
      
      ////////////////////////////////////////
      
      function confirm_delete_project(request_id,project_id) {
         $('innerprojdeledit').innerHTML = '';
         $('innerprojdeledit').appendChild(progress_span());
         rqjx_app_deleteProject(request_id,project_id,function(_data) {
            var data = recjsarray(_data);
            _destroy($('xproj_'+data[0]+'_'+data[1]));
            projdelbox.fade();
            for(var i=0;i<data[2].length;i++) {
               if($('actlist_'+data[2][i][0]+'_'+data[2][i][1])) {
                  $('actlist_'+data[2][i][0]+'_'+data[2][i][1]).innerHTML = data[2][i][2];
               }
            }
         });
      }
      
      var projdeledit = null;
      var projdelbox = null;
      function delete_project(request_id,project_id,project_nm) {
         projdeledit = _dce('div');
         projdeledit.setAttribute('id','projdeledit');
         projdeledit = document.body.appendChild(projdeledit);
         projdeledit.sub = projdeledit.appendChild(_dce('div'));
         projdeledit.sub.setAttribute('id','innerprojdeledit');
         projdelbox = new GlassBox();
         projdelbox.init('projdeledit','500px','190px','hidden','default',false,false);
         projdelbox.lbo(false,0.3);
         projdelbox.appear();
         
         $('innerprojdeledit').innerHTML = '<div style=\"padding:20px;text-align:center;\">You are going to delete this project.'
                                   + '<br/><br/><span style=\"font-weight:bold;\">'+$('sp_project_nm_'+request_id+'_'+project_id).innerHTML+'</span>'
                                   + '<br/><br/>Are you sure?'
                                   + '<br/><br/>'
                                   + '<input type=\"button\" value=\"Yes (delete)\" onclick=\"confirm_delete_project(\\''+request_id+'\\',\\''+project_id+'\\');\"/>&nbsp;&nbsp;'
                                   + '<input type=\"button\" value=\"No\" onclick=\"projdelbox.fade();\"/></div>';
      }
      
      ////////////////////////////////////////
      
      function save_project_kpo(request_id,project_id,d,e) {
         if(!$('editprojkpo_'+request_id+'_'+project_id)) return;
         var kpo = urlencode($('editprojkpo_'+request_id+'_'+project_id).value);
         if(projkpobox) {
            projkpobox.fade();
         }
         rqjx_app_saveKPO(request_id,project_id,kpo,function(_data) {
            var data = recjsarray(_data);
            $('kpo_'+data[0]+'_'+data[1]).innerHTML = data[2];
         });
      }
      
      var projkpo = null;
      var projkpobox = null;
      function edit_kpo(request_id,project_id,d,e) {
         projkpo = _dce('div');
         projkpo.setAttribute('id','projkpo');
         projkpo = document.body.appendChild(projkpo);
         projkpo.sub = projkpo.appendChild(_dce('div'));
         projkpo.sub.setAttribute('id','innerprojkpo');
         projkpobox = new GlassBox();
         projkpobox.init('projkpo','700px','235px','hidden','default',false,false);
         projkpobox.lbo(false,0.3);
         projkpobox.appear();
         rqjx_app_editKPO(request_id,project_id,function(_data) {
            var data = recjsarray(_data);
            $('innerprojkpo').innerHTML = data[2];
            //$('editprojkpo_'+data[0]+'_'+data[1]).focus(); ///// bug
         });
      }
      
      
      /////////////////////////////////////////////////////
      
      ////////////////////////////////////////
      
      function save_project_nm(request_id,project_id,d,e) {
         var project_nm = urlencode($('editprojnm_'+request_id+'_'+project_id).value);
         var retcomp = _parseForm('dvprojcomplist');
         projnmbox.fade();
         rqjx_app_saveProjectName(request_id,project_id,project_nm,0,retcomp,function(_data) {
            var data = recjsarray(_data);
            $('projectnm_'+data[0]+'_'+data[1]).innerHTML = data[2];
            $('idpcost_'+data[0]).innerHTML = data[3];
            for(var i=0;i<data[4].length;i++) {
               if($('actlist_'+data[4][i][0]+'_'+data[4][i][1])) {
                  $('actlist_'+data[4][i][0]+'_'+data[4][i][1]).innerHTML = data[4][i][2];
               }
            }
         });
      }
      
      var projnm = null;
      var projnmbox = null;
      function edit_project_nm(request_id,project_id,d,e) {
         projnm = _dce('div');
         projnm.setAttribute('id','projnm');
         projnm = document.body.appendChild(projnm);
         projnm.sub = projnm.appendChild(_dce('div'));
         projnm.sub.setAttribute('id','innerprojnm');
         projnmbox = new GlassBox();
         rqjx_app_editProjectName(request_id,project_id,function(_data) {
            var data = recjsarray(_data);
            projnmbox.init('projnm','700px',data[3]+'px','hidden','default',false,false);
            projnmbox.lbo(false,0.3);
            projnmbox.appear();
            $('innerprojnm').innerHTML = data[2];
            $('editprojnm_'+data[0]+'_'+data[1]).focus();
         });
      }
      
      
      /////////////////////////////////////////////////////
      
      
      function cancel_edit_focus_dev(request_id,competency_id) {
         $('focus_'+request_id+'_'+competency_id).innerHTML = $('focus_'+request_id+'_'+competency_id).oldHTML;
      }
      
      function save_focus_dev(request_id,competency_id,d,e) {
         var focus_dev = urlencode($('editfocusdev_'+request_id+'_'+competency_id).value);
         fdbox.fade();
         rqjx_app_saveFocusDevelopment(request_id,competency_id,focus_dev,function(_data) {
            var data = recjsarray(_data);
            $('focus_'+data[0]+'_'+data[1]).innerHTML = data[2];
         });
      }
      
      var focdev = null;
      var fdbox = null;
      function edit_focus_dev(request_id,competency_id,d,e) {
         focdev = _dce('div');
         focdev.setAttribute('id','focdev');
         focdev = document.body.appendChild(focdev);
         focdev.sub = focdev.appendChild(_dce('div'));
         focdev.sub.setAttribute('id','innerfocdev');
         fdbox = new GlassBox();
         fdbox.init('focdev','700px','230px','hidden','default',false,false);
         fdbox.lbo(false,0.3);
         fdbox.appear();
         rqjx_app_editFocusDevelopment(request_id,competency_id,function(_data) {
            var data = recjsarray(_data);
            $('innerfocdev').innerHTML = data[2];
            setTimeout(\"$('editfocusdev_\"+data[0]+\"_\"+data[1]+\"').focus();\",50);
         });
      }
      
      var apedit = null;
      var apbox = null;
      function edit_action_plan(request_id,actionplan_id,d,e) {
         apedit = _dce('div');
         apedit.setAttribute('id','apedit');
         apedit = document.body.appendChild(apedit);
         apedit.sub = apedit.appendChild(_dce('div'));
         apedit.sub.setAttribute('id','innerapedit');
         apbox = new GlassBox();
         rqjx_app_editActionPlan(request_id,actionplan_id,function(_data) {
            if(_data!='FAIL') {
               var data = recjsarray(_data);
               include(data[0]);
               $('innerapedit').innerHTML = data[1];
               apbox.init('apedit','700px',data[2],'hidden','default',false,false);
               apbox.lbo(false,0.3);
               apbox.appear();
            }
         });
      }
      
      function add_activity(request_id,project_id,d,e) {
         var dv = $('activitylist_'+request_id+'_'+project_id).appendChild(_dce('div'));
         dv.setAttribute('style','padding:5px;');
         dv.appendChild(progress_span());
         rqjx_app_addActivity(request_id,project_id,function(_data) {
            var data = recjsarray(_data);
            $('activitylist_'+data[0]+'_'+data[1]).innerHTML = data[3];
            $('idptimeframe_start_'+data[0]).innerHTML = data[4];
            $('idptimeframe_stop_'+data[0]).innerHTML = data[5];
            for(var i=0;i<data[6].length;i++) {
               if($('actlist_'+data[6][i][0]+'_'+data[6][i][1])) {
                  $('actlist_'+data[6][i][0]+'_'+data[6][i][1]).innerHTML = data[6][i][2];
               }
            }
            edit_activity(data[0],data[1],data[2],null,null);
         });
      }
      
      var activity_saved = 0;
      function save_activity(request_id,project_id,activity_id,d,e) {
         if(!activity_id) return;
         if(!project_id) return;
         if(!request_id) return;
         if(!$('editactivity_'+request_id+'_'+project_id+'_'+activity_id)) return;
         var activity = urlencode($('editactivity_'+request_id+'_'+project_id+'_'+activity_id).value);
         var kpd = urlencode($('editkpd_'+request_id+'_'+project_id+'_'+activity_id).value);
         var start = $('h_activity_start_dttm').value;
         var stop = $('h_activity_stop_dttm').value;
         if(actbox) {
            actbox.fade();
            actbox = null;
         }
         rqjx_app_saveProjectActivity(request_id,project_id,activity_id,activity,start,stop,kpd,function(_data) {
            var data = recjsarray(_data);
            $('projactnm_'+data[0]+'_'+data[1]+'_'+data[2]).innerHTML = data[3];
            $('projtmfrm_'+data[0]+'_'+data[1]+'_'+data[2]).innerHTML = data[4];
            $('projkpd_'+data[0]+'_'+data[1]+'_'+data[2]).innerHTML = data[5];
            for(var i=0;i<data[6].length;i++) {
               if($('actlist_'+data[6][i][0]+'_'+data[6][i][1])) {
                  $('actlist_'+data[6][i][0]+'_'+data[6][i][1]).innerHTML = data[6][i][2];
               }
            }
            $('idptimeframe_start_'+data[0]).innerHTML = data[7];
            $('idptimeframe_stop_'+data[0]).innerHTML = data[8];
            activity_saved = 1;
         });
      }
      
      function confirm_delete_activity(request_id,project_id,activity_id) {
         actbox.fade();
         actbox = null;
         _destroy($('dvactivitylist_'+request_id+'_'+project_id+'_'+activity_id));
         rqjx_app_deleteProjectActivity(request_id,project_id,activity_id,function(_data) {
            var data = recjsarray(_data);
            for(var i=0;i<data[1].length;i++) {
               if($('actlist_'+data[1][i][0]+'_'+data[1][i][1])) {
                  $('actlist_'+data[1][i][0]+'_'+data[1][i][1]).innerHTML = data[1][i][2];
               }
            }
            $('idptimeframe_start_'+data[0]).innerHTML = data[2];
            $('idptimeframe_stop_'+data[0]).innerHTML = data[3];
         });
      }
      
      function delete_activity(request_id,project_id,activity_id,d,e) {
         var activity = $('editactivity_'+request_id+'_'+project_id+'_'+activity_id).value;
         $('actformeditor').oldHTML = $('actformeditor').innerHTML;
         $('actformbtn').style.display = 'none';
         $('actformeditor').innerHTML = '<div style=\"padding:20px;text-align:center;\">You are going to delete this activity.'
                                   + '<br/><br/><span style=\"font-weight:bold;\">'+activity+'</span>'
                                   + '<br/><br/>Are you sure?'
                                   + '<br/><br/>'
                                   + '<input type=\"button\" value=\"Yes (delete)\" onclick=\"confirm_delete_activity(\\''+request_id+'\\',\\''+project_id+'\\',\\''+activity_id+'\\');\"/>&nbsp;'
                                   + '<input type=\"button\" value=\"No\" onclick=\"cancel_delete_activity();\"/></div>';
      }
      
      function cancel_delete_activity() {
         $('actformeditor').innerHTML = $('actformeditor').oldHTML;
         $('actformbtn').style.display = '';
      }
      
      var actedit = null;
      var actbox = null;
      function edit_activity(request_id,project_id,activity_id,d,e) {
         actedit = _dce('div');
         actedit.setAttribute('id','actedit');
         actedit = document.body.appendChild(actedit);
         actedit.sub = actedit.appendChild(_dce('div'));
         actedit.sub.setAttribute('id','inneractedit');
         actbox = new GlassBox();
         actbox.init('actedit','700px','340px','hidden','default',false,false);
         actbox.lbo(false,0.3);
         actbox.appear();
         rqjx_app_editProjectActivity(request_id,project_id,activity_id,function(_data) {
            var data = recjsarray(_data);
            $('inneractedit').innerHTML = data[3];
            //$('editactivity_'+data[0]+'_'+data[1]+'_'+data[2]).focus();
         });
      }
      
      var up_effect = null;
      function up_priority(request_id,competency_id,priority_no) {
         rqjx_app_setPriorityUp(request_id,competency_id,priority_no,function(_data) {
            var data = recjsarray(_data);
            $('comp_'+data[0]).innerHTML = data[2];
            //$('xcomp_'+data[0]+'_'+data[1]).style.backgroundColor = '#ffffcc';
            if(up_effect) {
               up_effect.cancel();
            }
            up_effect = new Effect.Highlight('xcomp_'+data[0]+'_'+data[1], {startcolor: '#ffffcc', restorecolor: 'transparent', delay:0, duration:2,afterFinish:function(o) {
               up_effect = null;
            }});
         });
      }
      
      var up_project_effect = null;
      function up_project_priority(request_id,project_id,priority_no) {
         rqjx_app_setProjectPriorityUp(request_id,project_id,priority_no,function(_data) {
            var data = recjsarray(_data);
            $('proj_'+data[0]).innerHTML = data[2];
            if(up_project_effect) {
               up_project_effect.cancel();
            }
            up_project_effect = new Effect.Highlight('xproj_'+data[0]+'_'+data[1], {startcolor: '#ffffcc', restorecolor: 'transparent', delay:0, duration:2,afterFinish:function(o) {
               up_project_effect = null;
            }});
            
         });
      }
      
      function confirm_superior_start_request(request_id) {
         $('xidp_'+request_id).innerHTML = '';
         $('xidp_'+request_id).appendChild(progress_span());
         rqjx_app_superiorStartRequest(request_id,function(_data) {
            var data = recjsarray(_data);
            $('xidp_'+request_id).innerHTML = data[1];
            supstartbox.fade();
         });
      }
      
      
      var supstartedit = null;
      var supstartbox = null;
      function superior_start_request(request_id) {
         supstartedit = _dce('div');
         supstartedit.setAttribute('id','supstartedit');
         supstartedit = document.body.appendChild(supstartedit);
         supstartedit.sub = supstartedit.appendChild(_dce('div'));
         supstartedit.sub.setAttribute('id','innersupstartedit');
         supstartbox = new GlassBox();
         
         rqjx_app_checkBeforeStart(request_id,function(_data) {
            var data = recjsarray(_data);
            supstartbox.init('supstartedit','700px',data[1]+'px','hidden','default',false,false);
            supstartbox.lbo(false,0.3);
            supstartbox.appear();
            $('innersupstartedit').innerHTML = data[0];
         });
      }
      
      function confirm_employee_return_request(request_id) {
         $('xidp_'+request_id).innerHTML = '';
         $('xidp_'+request_id).appendChild(progress_span());
         rqjx_app_employeeReturnRequest(request_id,function(_data) {
            var data = recjsarray(_data);
            $('xidp_'+request_id).innerHTML = data[1];
            empreturnbox.fade();
         });
      }
      
      var empreturnedit = null;
      var empreturnbox = null;
      function employee_return_request(request_id) {
         empreturnedit = _dce('div');
         empreturnedit.setAttribute('id','empreturnedit');
         empreturnedit = document.body.appendChild(empreturnedit);
         empreturnedit.sub = empreturnedit.appendChild(_dce('div'));
         empreturnedit.sub.setAttribute('id','innerempreturnedit');
         empreturnbox = new GlassBox();
         ajax_feedback=_caf;
         _caf.txt = ' ... check before return';
         rqjx_app_checkBeforeReturn(request_id,function(_data) {
            var data = recjsarray(_data);
            ajax_feedback=null;
            empreturnbox.init('empreturnedit','700px',data[1]+'px','hidden','default',false,false);
            empreturnbox.lbo(false,0.3);
            empreturnbox.appear();
            $('innerempreturnedit').innerHTML = data[0];
         });
      }
      
      function confirm_employee_submit_request(request_id) {
         $('xidp_'+request_id).innerHTML = '';
         $('xidp_'+request_id).appendChild(progress_span());
         rqjx_app_employeeSubmitRequest(request_id,function(_data) {
            var data = recjsarray(_data);
            $('xidp_'+request_id).innerHTML = data[1];
            empsubmitbox.fade();
         });
      }
      
      var empsubmitedit = null;
      var empsubmitbox = null;
      function employee_submit_request(request_id) {
         empsubmitedit = _dce('div');
         empsubmitedit.setAttribute('id','empsubmitedit');
         empsubmitedit = document.body.appendChild(empsubmitedit);
         empsubmitedit.sub = empsubmitedit.appendChild(_dce('div'));
         empsubmitedit.sub.setAttribute('id','innerempsubmitedit');
         empsubmitbox = new GlassBox();
         ajax_feedback=_caf;
         _caf.txt = ' ... check before submit';
         rqjx_app_checkBeforeSubmit(request_id,function(_data) {
            var data = recjsarray(_data);
            ajax_feedback=null;
            empsubmitbox.init('empsubmitedit','700px',data[1]+'px','hidden','default',false,false);
            empsubmitbox.lbo(false,0.3);
            empsubmitbox.appear();
            $('innerempsubmitedit').innerHTML = data[0];
         });
      }
      
      function confirm_superior_approve_request(request_id) {
         $('xidp_'+request_id).innerHTML = '';
         $('xidp_'+request_id).appendChild(progress_span());
         rqjx_app_superiorApproveRequest(request_id,function(_data) {
            var data = recjsarray(_data);
            $('xidp_'+request_id).innerHTML = data[1];
            supapprovebox.fade();
         });
      }
      
      var supapproveedit = null;
      var supapprovebox = null;
      function superior_approve_request(request_id) {
         supapproveedit = _dce('div');
         supapproveedit.setAttribute('id','supapproveedit');
         supapproveedit = document.body.appendChild(supapproveedit);
         supapproveedit.sub = supapproveedit.appendChild(_dce('div'));
         supapproveedit.sub.setAttribute('id','innersupapproveedit');
         supapprovebox = new GlassBox();
         supapprovebox.init('supapproveedit','500px','190px','hidden','default',false,false);
         supapprovebox.lbo(false,0.3);
         supapprovebox.appear();
         
         $('innersupapproveedit').innerHTML = '<div style=\"background-color:#f2f2f2;font-weight:bold;font-size:1.1em;height:300px;padding:20px;text-align:center;\">You are going to approve this IDP request.'
                                   + '<br/><br/>Are you sure?'
                                   + '<br/><br/><br/>'
                                   + '<input type=\"button\" value=\"Yes (Approve Request)\" onclick=\"confirm_superior_approve_request(\\''+request_id+'\\');\"/>&nbsp;'
                                   + '<input type=\"button\" value=\"No\" onclick=\"supapprovebox.fade();\"/></div>';
      }
      
      function confirm_next_superior_approve_request(request_id) {
         $('xidp_'+request_id).innerHTML = '';
         $('xidp_'+request_id).appendChild(progress_span());
         rqjx_app_nextSuperiorApproveRequest(request_id,function(_data) {
            var data = recjsarray(_data);
            $('xidp_'+request_id).innerHTML = data[1];
            nextsupapprovebox.fade();
         });
      }
      
      var nextsupapproveedit = null;
      var nextsupapprovebox = null;
      function next_superior_approve_request(request_id) {
         nextsupapproveedit = _dce('div');
         nextsupapproveedit.setAttribute('id','nextsupapproveedit');
         nextsupapproveedit = document.body.appendChild(nextsupapproveedit);
         nextsupapproveedit.sub = nextsupapproveedit.appendChild(_dce('div'));
         nextsupapproveedit.sub.setAttribute('id','innernextsupapproveedit');
         nextsupapprovebox = new GlassBox();
         nextsupapprovebox.init('nextsupapproveedit','500px','190px','hidden','default',false,false);
         nextsupapprovebox.lbo(false,0.3);
         nextsupapprovebox.appear();
         
         $('innernextsupapproveedit').innerHTML = '<div style=\"background-color:#f2f2f2;font-weight:bold;font-size:1.1em;height:300px;padding:20px;text-align:center;\">You are going to approve this IDP request.'
                                   + '<br/><br/>Are you sure?'
                                   + '<br/><br/><br/>'
                                   + '<input type=\"button\" value=\"Yes (Approve Request)\" onclick=\"confirm_next_superior_approve_request(\\''+request_id+'\\');\"/>&nbsp;'
                                   + '<input type=\"button\" value=\"No\" onclick=\"nextsupapprovebox.fade();\"/></div>';
      }
      
      function confirm_hr_approve_request(request_id) {
         $('xidp_'+request_id).innerHTML = '';
         $('xidp_'+request_id).appendChild(progress_span());
         rqjx_app_HRApproveRequest(request_id,function(_data) {
            var data = recjsarray(_data);
            $('xidp_'+request_id).innerHTML = data[1];
            hrapprovebox.fade();
         });
      }
      
      var hrapproveedit = null;
      var hrapprovebox = null;
      function hr_approve_request(request_id) {
         hrapproveedit = _dce('div');
         hrapproveedit.setAttribute('id','hrapproveedit');
         hrapproveedit = document.body.appendChild(hrapproveedit);
         hrapproveedit.sub = hrapproveedit.appendChild(_dce('div'));
         hrapproveedit.sub.setAttribute('id','innerhrapproveedit');
         hrapprovebox = new GlassBox();
         hrapprovebox.init('hrapproveedit','500px','190px','hidden','default',false,false);
         hrapprovebox.lbo(false,0.3);
         hrapprovebox.appear();
         
         $('innerhrapproveedit').innerHTML = '<div style=\"background-color:#f2f2f2;font-weight:bold;font-size:1.1em;height:300px;padding:20px;text-align:center;\">You are going to confirm this IDP request.'
                                   + '<br/><br/>Are you sure?'
                                   + '<br/><br/><br/>'
                                   + '<input type=\"button\" value=\"Yes (Confirm &amp; Start Implementation)\" onclick=\"confirm_hr_approve_request(\\''+request_id+'\\');\"/>&nbsp;'
                                   + '<input type=\"button\" value=\"No (Close)\" onclick=\"hrapprovebox.fade();\"/></div>';
      }
      
      function confirm_superior_return_request(request_id) {
         var return_note = urlencode($('return_note').value);
         $('xidp_'+request_id).innerHTML = '';
         $('xidp_'+request_id).appendChild(progress_span());
         rqjx_app_superiorReturnRequest(request_id,return_note,function(_data) {
            var data = recjsarray(_data);
            $('xidp_'+request_id).innerHTML = data[1];
            supreturnbox.fade();
         });
      }
      
      var supreturnedit = null;
      var supreturnbox = null;
      function superior_return_request(request_id) {
         supreturnedit = _dce('div');
         supreturnedit.setAttribute('id','supreturnedit');
         supreturnedit = document.body.appendChild(supreturnedit);
         supreturnedit.sub = supreturnedit.appendChild(_dce('div'));
         supreturnedit.sub.setAttribute('id','innersupreturnedit');
         supreturnbox = new GlassBox();
         supreturnbox.init('supreturnedit','600px','320px','hidden','default',false,false);
         supreturnbox.lbo(false,0.3);
         supreturnbox.appear();
         
         var return_note = '';
         if($('xreturn_note')) {
            return_note = $('xreturn_note').innerHTML;
         }
         
         $('innersupreturnedit').innerHTML = '<div style=\"background-color:#f2f2f2;font-weight:bold;font-size:1.1em;height:300px;padding:20px;text-align:center;\">'
                                   + 'You are not approving this request.<br/>You are going to return this IDP request to the employee.'
                                   + '<br/><br/>With the following notes:<br/>'
                                   + '<textarea id=\"return_note\" style=\"width:400px;height:100px;\">'+return_note+'</textarea>'
                                   + '<br/><br/>Are you sure?'
                                   + '<br/><br/>'
                                   + '<input type=\"button\" value=\"Yes (Not Approve)\" onclick=\"confirm_superior_return_request(\\''+request_id+'\\');\"/>&nbsp;'
                                   + '<input type=\"button\" value=\"No\" onclick=\"supreturnbox.fade();\"/></div>';
         setTimeout('$(\"return_note\").focus();',200);
      }
      
      function confirm_next_superior_return_request(request_id) {
         var return_note = urlencode($('return_note').value);
         $('xidp_'+request_id).innerHTML = '';
         $('xidp_'+request_id).appendChild(progress_span());
         rqjx_app_nextSuperiorReturnRequest(request_id,return_note,function(_data) {
            var data = recjsarray(_data);
            $('xidp_'+request_id).innerHTML = data[1];
            nextsupreturnbox.fade();
         });
      }
      
      var nextsupreturnedit = null;
      var nextsupreturnbox = null;
      function next_superior_return_request(request_id) {
         nextsupreturnedit = _dce('div');
         nextsupreturnedit.setAttribute('id','nextsupreturnedit');
         nextsupreturnedit = document.body.appendChild(nextsupreturnedit);
         nextsupreturnedit.sub = nextsupreturnedit.appendChild(_dce('div'));
         nextsupreturnedit.sub.setAttribute('id','innernextsupreturnedit');
         nextsupreturnbox = new GlassBox();
         nextsupreturnbox.init('nextsupreturnedit','600px','320px','hidden','default',false,false);
         nextsupreturnbox.lbo(false,0.3);
         nextsupreturnbox.appear();
         
         var return_note = '';
         if($('xreturn_note')) {
            return_note = $('xreturn_note').innerHTML;
         }
         
         $('innernextsupreturnedit').innerHTML = '<div style=\"background-color:#f2f2f2;font-weight:bold;font-size:1.1em;height:300px;padding:20px;text-align:center;\">'
                                   + 'You are not approving this request.<br/>You are going to return this IDP request to the employee\'s superior.'
                                   + '<br/><br/>With the following notes:<br/>'
                                   + '<textarea id=\"return_note\" style=\"width:400px;height:100px;\">'+return_note+'</textarea>'
                                   + '<br/><br/>Are you sure?'
                                   + '<br/><br/>'
                                   + '<input type=\"button\" value=\"Yes (Not Approve)\" onclick=\"confirm_next_superior_return_request(\\''+request_id+'\\');\"/>&nbsp;'
                                   + '<input type=\"button\" value=\"No\" onclick=\"nextsupreturnbox.fade();\"/></div>';
         setTimeout('$(\"return_note\").focus();',200);
      }
      
      function confirm_delete_request(request_id) {
         $('xidp_'+request_id).innerHTML = '';
         $('xidp_'+request_id).appendChild(progress_span());
         rqjx_app_deleteRequest(request_id,function(_data) {
            _destroy($('xidp_'+_data));
            reqdelbox.fade();
            _destroy($('dvl_'+_data));
         });
      }
      
      var reqdeledit = null;
      var reqdelbox = null;
      function delete_request(request_id) {
         reqdeledit = _dce('div');
         reqdeledit.setAttribute('id','reqdeledit');
         reqdeledit = document.body.appendChild(reqdeledit);
         reqdeledit.sub = reqdeledit.appendChild(_dce('div'));
         reqdeledit.sub.setAttribute('id','innerreqdeledit');
         reqdelbox = new GlassBox();
         reqdelbox.init('reqdeledit','500px','170px','hidden','default',false,false);
         reqdelbox.lbo(false,0.3);
         reqdelbox.appear();
         
         $('innerreqdeledit').innerHTML = '<div style=\"padding:20px;text-align:center;\">You are going to delete this IDP request.'
                                   + '<br/><br/>Are you sure?'
                                   + '<br/><br/>'
                                   + '<input type=\"button\" value=\"Yes (delete)\" onclick=\"confirm_delete_request(\\''+request_id+'\\');\"/>&nbsp;&nbsp;'
                                   + '<input type=\"button\" value=\"No\" onclick=\"reqdelbox.fade();\"/></div>';
      }
      
      
      
      function add_competency(employee_id,request_id,competency_id,ccl,rcl,itj,gap,asid,d,e) {
         var p = d.parentNode;
         p.innerHTML = '<div style=\"max-width:70px;overflow:hidden;\"><div id=\"xpg_'+competency_id+'\" style=\"padding-left:15px;\"></div></div>';
         $('xpg_'+competency_id).appendChild(progress_span(' '));
         var dv = $('comp_'+request_id);
         dv.dv = _dce('div');
         dv.dv.appendChild(progress_span());
         dv.dv.setAttribute('style','padding:3px;padding-left:0px;');
         dv.dv = dv.appendChild(dv.dv);
         dv.dv.setAttribute('id','xcomp_'+request_id+'_'+competency_id);
         dv.dv.removebutton = '<input style=\"width:70px;\" type=\"button\" value=\"Remove\" onclick=\"remove_competency(\\''+employee_id+'\\',\\''+request_id+'\\',\\''+competency_id+'\\',this,event);\"/>';
         $('empty_competency_'+request_id).style.display = 'none';
         rqjx_app_addCompetency(employee_id,request_id,competency_id,ccl,rcl,itj,gap,asid,function(_data) {
            if(_data=='DUPLICATE') {
               alert('Competency already selected.');
               return;
            }
            if(_data=='FAIL') {
               alert('Insert failed. Please contact administrator.');
               return;
            }
            var data = recjsarray(_data);
            $('xcomp_'+data[0]+'_'+data[1]).innerHTML = data[2];
            $('tdc_'+data[1]).innerHTML = $('xcomp_'+data[0]+'_'+data[1]).removebutton;
            $('dvc_'+data[1]).style.backgroundColor = '#ccddff';
            $('dvc_'+data[1]).style.color = 'blue';
         });
      }
      
      function remove_competency(employee_id,request_id,competency_id,d,e) {
         var p = d.parentNode;
         p.innerHTML = '<div style=\"max-width:70px;overflow:hidden;\"><div id=\"xpg_'+competency_id+'\" style=\"padding-left:15px;\"></div></div>';
         $('xpg_'+competency_id).appendChild(progress_span(' '));
         rqjx_app_deleteCompetency(request_id,competency_id,function(_data) {
            var data = recjsarray(_data);
            _destroy($('xcomp_'+data[0]+'_'+data[1]));
            $('tdc_'+data[1]).innerHTML = '<input style=\"width:70px;\" type=\"button\" value=\"Add\" onclick=\"add_competency(\\''+employee_id+'\\',\\''+request_id+'\\',\\''+competency_id+'\\',\\''+data[2]+'\\',\\''+data[3]+'\\',\\''+data[4]+'\\',\\''+data[5]+'\\',\\''+data[6]+'\\',this,event);\"/>';
            $('dvc_'+data[1]).style.backgroundColor = '';
            $('dvc_'+data[1]).style.color = '';
         });
      }
      
      function stage_add_browse_competency(request_id,employee_id,d,e) {
         wizard_mode = 0;
         SetCookie('wizard_mode','0');
         add_browse_competency(request_id,employee_id,d,e);
      }
      
      var cb = new GlassBox();
      var myBox;
      path_to_root_dir = '".XOCP_SERVER_SUBDIR."/';
      function add_browse_competency(request_id,employee_id,d,e) {
         cb = new GlassBox();
         cb.init( 'cbBox', '700px', '490px', 'hidden', 'default' ,false,false);
         cb.lbo(false,0.3 ); 
         cb.appear();
         rqjx_app_getCompetencyGap(request_id,employee_id,'$job_id',function(_data) {
            $('innercbbox').innerHTML = _data;
         });
      }
      
      var newidp = null;
      function new_idp() {
         var dv = _dce('div');
         dv.setAttribute('style','padding:5px;border-bottom:1px solid #bbb;');
         dv.appendChild(progress_span());
         newidp = $('tdcontent').insertBefore(dv,$('tdcontent').firstChild);
         $('empty_list').style.display = 'none';
         rqjx_app_newRequest('$employee_id','$job_id',function(_data) {
            if(_data!='FAIL') {
               var data = recjsarray(_data);
               newidp.innerHTML = data[1];
               newidp.setAttribute('id','xidp_'+data[0]);
               wizard_mode = 1;
               SetCookie('wizard_mode','1');
               start_wizard(data[0]);
            }
         });
      }
      
      function cb_fade() {
         if(wizard_mode==1) {
            wizard_run_next();
         } else {
            cb.fade();
         }
      }
      
      function start_wizard(request_id) {
         wizard_mode = 1;
         SetCookie('wizard_mode','1');
         wizard_request_id = request_id;
         wizard_employee_id = '$employee_id';
         wizard_job_id = '$job_id';
         wizard_current_step = 0;
         wizard_focusdev_no_current = 0;
         wizard_project_current = 0;
         wizard_run();
      }
      
      function wizard_run() {
         if(wizard_step[wizard_current_step]) {
            wizard_step[wizard_current_step]();
         }
      }
      
      function wizard_run_next() {
         wizard_current_step++;
         if(wizard_step[wizard_current_step]) {
            wizard_step[wizard_current_step]();
         }
      }
      
      function wizard_run_prev() {
         wizard_current_step--;
         if(wizard_step[wizard_current_step]) {
            wizard_step[wizard_current_step]();
         }
      }
      
      function wizard_stop() {
         switch(wizard_current_step) {
            case 0:
               break;
            case 1:
               break;
            case 2:
               /// save first focus devevelopment
               var competency_id = wizard_focusdev_list[wizard_focusdev_no_current];
               var focus_dev = urlencode($('editfocusdev_'+wizard_request_id+'_'+competency_id).value);
               rqjx_app_saveFocusDevelopment(wizard_request_id,competency_id,focus_dev,function(_data) {
                  var data = recjsarray(_data);
                  $('focus_'+data[0]+'_'+data[1]).innerHTML = data[2];
               });
               break;
            case 3:
               var project_id = wizard_project_list[wizard_project_current];
               var project_nm = urlencode($('editprojnm_'+wizard_request_id+'_'+project_id).value);
               var retcomp = _parseForm('dvprojcomplist');
               rqjx_app_saveProjectName(wizard_request_id,project_id,project_nm,0,retcomp,function(_data) {
                  var data = recjsarray(_data);
                  $('projectnm_'+data[0]+'_'+data[1]).innerHTML = data[2];
                  $('idpcost_'+data[0]).innerHTML = data[3];
                  for(var i=0;i<data[4].length;i++) {
                     if($('actlist_'+data[4][i][0]+'_'+data[4][i][1])) {
                        $('actlist_'+data[4][i][0]+'_'+data[4][i][1]).innerHTML = data[4][i][2];
                     }
                  }
               });
               break;
            default:
               break;
         }
         wizard_box.fade();
         _destroy($('wizardbox'));
         //reset_wizard_box();
      }
      
      function reset_wizard_box() {
         if($('innerwizardbox')) _destroy($('innerwizardbox'));
         if($('wizardbox')) _destroy($('wizardbox'));
         if(wizard_box) {
            if(wizard_box.overlay) _destroy(wizard_box.overlay);
            wizard_box = null;
         }
         var wbox = _dce('div');
         wbox.setAttribute('id','wizardbox');
         wbox.setAttribute('class','glassbox');
         wbox.sub = wbox.appendChild(_dce('div'));
         wbox.sub.setAttribute('id','innerwizardbox');
         wbox.sub.appendChild(progress_span());
         document.body.appendChild(wbox);
      }
      
      function wizard_cleanup() {
         if(wizard_box&&wizard_box.remove) {
            wizard_box.remove();
            $('innerwizardbox').innerHTML = '';
            $('wizardbox').top = '-1000px';
            _destroy(wizard_box.overlay);
         }
         wizard_box = null;
      }
      
      function wizard_box_exitbutton(wizard_box) {
         /// exit button
         var exitButton = document.createElement( 'div' );
         exitButton.setAttribute( 'id','exitButton' );
         exitButton.style.position = 'absolute';
         exitButton.style.left = wizard_box.glassboxWidth - 39 + 'px';
         exitButton.style.top = 23 + 'px';
         exitButton.style.zIndex = 1001;
         exitButton.title = 'close';
         wizard_box.glassbox.appendChild( exitButton );
   
         var exitLink = document.createElement( 'a' );
         exitLink.href = 'javascript:wizard_stop();';
         exitButton.appendChild( exitLink );
   
         var exitImage = document.createElement( 'img' );
         exitImage.setAttribute( 'id', 'exitImage' );
         exitImage.style.border = 0;
         exitImage.src = wizard_box.skin_path + 'exitButton.png';
         exitLink.appendChild( exitImage );
      }
      
      var wizard_up_effect = null;
      function wizard_competency_priority(request_id,competency_id,priority_no) {
         wizard_project_list = Array();
         wizard_project_current = 0;
         rqjx_app_wizardSetPriorityUp(request_id,competency_id,priority_no,wizard_employee_id,wizard_job_id,function(_data) {
            var data = recjsarray(_data);
            $('comp_'+data[0]).innerHTML = data[2];
            $('innerwizardbox').innerHTML = data[3];
            
            if(up_effect) {
               up_effect.cancel();
            }
            up_effect = new Effect.Highlight('xcomp_'+data[0]+'_'+data[1], {startcolor: '#ffffcc', restorecolor: 'transparent', delay:0, duration:2,afterFinish:function(o) {
               up_effect = null;
            }});
            
            if(wizard_up_effect) {
               wizard_up_effect.cancel();
            }
            wizard_up_effect = new Effect.Highlight('wxcomp_'+data[0]+'_'+data[1], {startcolor: '#ffffcc', restorecolor: 'transparent', delay:0, duration:2,afterFinish:function(o) {
               wizard_up_effect = null;
            }});
            
         });
      }
      
      function wizard_competency_priority_down(request_id,competency_id,priority_no) {
         wizard_project_list = Array();
         wizard_project_current = 0;
         rqjx_app_wizardSetPriorityDown(request_id,competency_id,priority_no,wizard_employee_id,wizard_job_id,function(_data) {
            var data = recjsarray(_data);
            $('comp_'+data[0]).innerHTML = data[2];
            $('innerwizardbox').innerHTML = data[3];
            
            if(up_effect) {
               up_effect.cancel();
            }
            up_effect = new Effect.Highlight('xcomp_'+data[0]+'_'+data[1], {startcolor: '#ffffcc', restorecolor: 'transparent', delay:0, duration:2,afterFinish:function(o) {
               up_effect = null;
            }});
            
            if(wizard_up_effect) {
               wizard_up_effect.cancel();
            }
            wizard_up_effect = new Effect.Highlight('wxcomp_'+data[0]+'_'+data[1], {startcolor: '#ffffcc', restorecolor: 'transparent', delay:0, duration:2,afterFinish:function(o) {
               wizard_up_effect = null;
            }});
            
         });
      }
      
      ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
      /////////////////////// wizard employee ////////////////////////////////////////////////////////////////////////////////////////////////////////
      ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
      
      var wizemp_focusdev_list = Array();
      var wizemp_focusdev_no_prev = 0;
      var wizemp_focusdev_no_next = 0;
      var wizemp_focusdev_no_current = 0;
      var wizemp_request_id = 0;
      var wizemp_employee_id = 0;
      var wizemp_job_id = 0;
      var wizemp_box = null;
      var wizemp_mode = 0;
      SetCookie('wizemp_mode','0');
      var wizemp_current_step = 0;
      
      var wizemp_ap_list = Array();
      var wizemp_ap_current = 0;
      var wizemp_competency_list = Array();
      var wizemp_competency_current = 0;
      
      var wizemp_project_current = 0;
      var wizemp_project_list = Array();
      var wizemp_act_current = 0;
      var wizemp_act_list = Array();
      
      var wizemp_step = new Array(
         
         //// STEP 0 : Add Action Plan
         function() {
            reset_wizemp_box();
            wizemp_box = new GlassBox();
            wizemp_box.init( 'wizempbox', '700px', '490px', 'hidden', 'default' ,false,false);
            wizemp_box.lbo(false,0.3 );
            wizemp_box_exitbutton(wizemp_box);
            wizemp_box.appear();
            var competency_id = 0;
            wizemp_kpo_edit=null;
            if(wizemp_competency_list[wizemp_competency_current]) {
               competency_id = wizemp_competency_list[wizemp_competency_current];
            }
            var actionplan_id = 0;
            if(wizemp_ap_list[wizemp_ap_current]) {
               actionplan_id = wizemp_ap_list[wizemp_ap_current];
            }
            wizemp_ap_get(wizemp_request_id,competency_id,actionplan_id);
         },
         
         //// STEP 1 : Add Activities
         function() {
            reset_wizemp_box();
            wizemp_box = new GlassBox();
            wizemp_box.init( 'wizempbox', '700px', '490px', 'hidden', 'default' ,false,false);
            wizemp_box.lbo(false,0.3 );
            wizemp_box_exitbutton(wizemp_box);
            wizemp_box.appear();
            var project_id = 0;
            if(wizemp_project_list[wizemp_project_current]) {
               project_id = wizemp_project_list[wizemp_project_current];
            }
            var activity_id = 0;
            if(wizemp_act_list[wizemp_act_current]) {
               activity_id = wizemp_act_list[wizemp_act_current];
            }
            wizemp_act_get(wizemp_request_id,project_id,activity_id);
         },
         
         
         //// STEP 4 : Finish Wizard
         function() {
            reset_wizemp_box();
            wizemp_box = new GlassBox();
            wizemp_box.init( 'wizempbox', '700px', '225px', 'hidden', 'default' ,false,false);
            wizemp_box.lbo(false,0.3 ); 
            wizemp_box_exitbutton(wizemp_box);
            wizemp_box.appear();
            rqjx_app_wizempFinish(wizemp_request_id,wizemp_employee_id,wizemp_job_id,function(_data) {
               $('innerwizempbox').innerHTML = _data;
            });
         },
         
         null
         
      );
      
      function start_wizemp(request_id) {
         _destroy(apbox);
         apbox = null;
         _destroy(actbox);
         actbox = null;
         wizemp_mode = 1;
         SetCookie('wizemp_mode','1');
         wizemp_request_id = request_id;
         wizemp_employee_id = '$employee_id';
         wizemp_job_id = '$job_id';
         wizemp_current_step = 0;
         wizemp_focusdev_no_current = 0;
         wizemp_project_current = 0;
         wizemp_competency_current = 0;
         wizemp_ap_current = 0;
         wizemp_project_current = 0;
         wizemp_act_current = 0;
         wizemp_act_list = Array();
         wizemp_run();
      }
      
      function wizemp_run() {
         if(wizemp_step[wizemp_current_step]) {
            wizemp_step[wizemp_current_step]();
         }
      }
      
      function wizemp_run_next() {
         wizemp_current_step++;
         if(wizemp_step[wizemp_current_step]) {
            wizemp_step[wizemp_current_step]();
         }
      }
      
      function wizemp_run_prev() {
         wizemp_current_step--;
         if(wizemp_step[wizemp_current_step]) {
            wizemp_step[wizemp_current_step]();
         }
      }
      
      function wizemp_stop() {
         switch(wizemp_current_step) {
            case 0:
               if(wizemp_ap_list&&wizemp_ap_list[wizemp_ap_current]) {
                  var actionplan_id = wizemp_ap_list[wizemp_ap_current];
                  var competency_id = wizemp_competency_list[wizemp_competency_current];
                  _idpm_save(wizemp_request_id,actionplan_id,null,null);
               }
               break;
            case 1:
              if(wizemp_act_list&&wizemp_act_list[wizemp_act_current]) {
                  var activity_id = wizemp_act_list[wizemp_act_current];
                  var project_id = wizemp_project_list[wizemp_project_current];
                  save_activity(wizemp_request_id,project_id,activity_id,null,null);
               }
               var project_id = wizemp_project_list[wizemp_project_current];
               if($('editprojkpo_'+wizemp_request_id+'_'+project_id)) {
                  save_project_kpo(wizemp_request_id,project_id,null,null);
               }
               break;
            case 2:
               break;
            case 3:
               break;
            default:
               break;
         }
         wizemp_box.fade();
         ///reset_wizemp_box();
         _destroy($('wizempbox'));
      }
      
      function reset_wizemp_box() {
         if($('innerwizempdbox')) _destroy($('innerwizempdbox'));
         if($('wizempbox')) _destroy($('wizempbox'));
         if(wizemp_box) {
            if(wizemp_box.overlay) _destroy(wizemp_box.overlay);
            wizemp_box = null;
         }
         var webox = _dce('div');
         webox.setAttribute('id','wizempbox');
         webox.setAttribute('class','glassbox');
         webox.sub = webox.appendChild(_dce('div'));
         webox.sub.setAttribute('id','innerwizempbox');
         webox.sub.appendChild(progress_span());
         document.body.appendChild(webox);
      }
      
      function wizemp_cleanup() {
         if(wizemp_box&&wizemp_box.remove) {
            wizemp_box.remove();
            $('innerwizempbox').innerHTML = '';
            $('wizempbox').top = '-1000px';
            _destroy(wizemp_box.overlay);
         }
         wizemp_box = null;
      }
      
      function wizemp_box_exitbutton(wizemp_box) {
         /// exit button
         var exitButton = document.createElement( 'div' );
         exitButton.setAttribute( 'id','exitButton' );
         exitButton.style.position = 'absolute';
         exitButton.style.left = wizemp_box.glassboxWidth - 39 + 'px';
         exitButton.style.top = 23 + 'px';
         exitButton.style.zIndex = 1001;
         exitButton.title = 'close';
         wizemp_box.glassbox.appendChild( exitButton );
         
         var exitLink = document.createElement( 'a' );
         exitLink.href = 'javascript:wizemp_stop();';
         exitButton.appendChild( exitLink );
   
         var exitImage = document.createElement( 'img' );
         exitImage.setAttribute( 'id', 'exitImage' );
         exitImage.style.border = 0;
         exitImage.src = wizemp_box.skin_path + 'exitButton.png';
         exitLink.appendChild( exitImage );
      }
      
      function wizemp_add_ap(request_id,competency_id,method_t) {
         rqjx_app_addActionPlan(request_id,competency_id,method_t,function(_data) {
            if(_data=='FAIL') {
               alert('Fail to add action plan. Please contact administrator.');
               return;
            }
            var data = recjsarray(_data);
            $('actlist_'+data[0]+'_'+data[1]).innerHTML = data[2];
            $('idptimeframe_start_'+data[0]).innerHTML = data[3];
            $('idptimeframe_stop_'+data[0]).innerHTML = data[4];
            wizemp_ap_list = data[5];
            wizemp_ap_current = data[6];
            var actionplan_id = 0;
            if(wizemp_ap_list[wizemp_ap_current]) {
               actionplan_id = wizemp_ap_list[wizemp_ap_current];
            }
            wizemp_ap_get(wizemp_request_id,competency_id,actionplan_id);
         });
      }
      
      function wizemp_ap_getmethod(request_id,competency_id,actionplan_id) {
         ajax_feedback = _caf;
         if(wizemp_ap_list&&wizemp_ap_list[wizemp_ap_current]) {
            var actionplan_id = wizemp_ap_list[wizemp_ap_current];
            var competency_id = wizemp_competency_list[wizemp_competency_current];
            _idpm_save(wizemp_request_id,actionplan_id,null,null);
         }
         
         rqjx_app_wizempAddActionPlan(request_id,competency_id,actionplan_id,function(_data) {
            $('innerwizempbox').innerHTML = _data;
         });
      
      }
      
      function wizemp_ap_get(wizemp_request_id,competency_id,actionplan_id) {
         rqjx_app_wizempActionPlan(wizemp_request_id,competency_id,actionplan_id,function(_data) {
            var data = recjsarray(_data);
            $('innerwizempbox').innerHTML = data[7];
            wizemp_competency_list = data[3];
            wizemp_ap_list = data[4];
            wizemp_competency_current = data[5];
            wizemp_ap_current = data[6];
            if(data[8]!='') {
               include(data[8]);
            }
         });
      }
      
      function wizemp_ap_next() {
         //// save current
         ajax_feedback = _caf;
         if(wizemp_ap_list&&wizemp_ap_list[wizemp_ap_current]) {
            var actionplan_id = wizemp_ap_list[wizemp_ap_current];
            var competency_id = wizemp_competency_list[wizemp_competency_current];
            _idpm_save(wizemp_request_id,actionplan_id,null,null);
         }
         var n = wizemp_ap_current+1;
         var m = wizemp_competency_current+1;
         if(wizemp_ap_list&&wizemp_ap_list[n]) {
            var actionplan_id = wizemp_ap_list[n];
            var competency_id = wizemp_competency_list[wizemp_competency_current];
            wizemp_ap_get(wizemp_request_id,competency_id,actionplan_id);
         } else if(wizemp_competency_list&&wizemp_competency_list[m]) {
            var actionplan_id = 0;
            var competency_id = wizemp_competency_list[m];
            wizemp_ap_get(wizemp_request_id,competency_id,actionplan_id);
         } else {
            wizemp_run_next();
         }
      }
      
      function wizemp_ap_prev() {
         ajax_feedback = _caf;
         if(wizemp_ap_list&&wizemp_ap_list[wizemp_ap_current]) {
            var actionplan_id = wizemp_ap_list[wizemp_ap_current];
            var competency_id = wizemp_competency_list[wizemp_competency_current];
            _idpm_save(wizemp_request_id,actionplan_id,null,null);
         }
         var n = wizemp_ap_current-1;
         var m = wizemp_competency_current-1;
         if(wizemp_ap_list&&wizemp_ap_list[n]) {
            var actionplan_id = wizemp_ap_list[n];
            var competency_id = wizemp_competency_list[wizemp_competency_current];
            wizemp_ap_get(wizemp_request_id,competency_id,actionplan_id);
         } else if(wizemp_competency_list&&wizemp_competency_list[m]) {
            var actionplan_id = -1;
            var competency_id = wizemp_competency_list[m];
            wizemp_ap_get(wizemp_request_id,competency_id,actionplan_id);
         }
      }
      
      function wizemp_ap_confirm_delete(actionplan_id,d,e) {
         ///ajax_feedback = _caf;
         var apfrm = $('apformeditor');
         if(apfrm) {
            apfrm.innerHTML = '';
            apfrm.style.textAlign = 'center';
            apfrm.appendChild(progress_span(' ... deleting'));
         }
         $('wizemp_ap_delete_btn').style.display = 'none';
         rqjx_app_deleteActionPlan(wizemp_request_id,actionplan_id,function(_data) {
            if(_data=='FAIL') {
               alert('Fail to delete action plan. Please contact administrator.');
               return;
            }
            var data = recjsarray(_data);
            $('actlist_'+data[0]+'_'+data[1]).innerHTML = data[2];
            $('idptimeframe_start_'+data[0]).innerHTML = data[3];
            $('idptimeframe_stop_'+data[0]).innerHTML = data[4];
            $('idpcost_'+data[0]).innerHTML = data[5];
            wizemp_ap_list = data[6];
            wizemp_ap_current--;
            var actionplan_id = 0;
            var competency_id = wizemp_competency_list[wizemp_competency_current];
            if(wizemp_ap_list[wizemp_ap_current]) {
               actionplan_id = wizemp_ap_list[wizemp_ap_current];
            }
            wizemp_ap_get(wizemp_request_id,competency_id,actionplan_id);
         });
      }
      
      function wizemp_ap_cancel_delete(d,e) {
         var dvbtn = $('wizemp_ap_btn');
         dvbtn.innerHTML = dvbtn.oldHTML;
         var dvdel = $('wizemp_ap_delete_btn');
         dvdel.style.display = 'none';
         $('wizemp_flow').style.backgroundColor = '#fff';
      }
      
      function wizemp_ap_delete(d,e) {
         var dvbtn = $('wizemp_ap_btn');
         dvbtn.oldHTML = dvbtn.innerHTML;
         dvbtn.innerHTML = '';
         var dvdel = $('wizemp_ap_delete_btn');
         dvdel.style.display = '';
         $('wizemp_flow').style.backgroundColor = '#ffcccc';
      }
      
      function wizemp_act_get(wizemp_request_id,project_id,activity_id) {
         rqjx_app_wizempActivity(wizemp_request_id,project_id,activity_id,function(_data) {
            var data = recjsarray(_data);
            $('innerwizempbox').innerHTML = data[7];
            wizemp_project_list = data[3];
            wizemp_act_list = data[4];
            wizemp_project_current = data[5];
            wizemp_act_current = data[6];
            var activity_id = wizemp_act_list[wizemp_act_current];
            var project_id = wizemp_project_list[wizemp_project_current];
            if(activity_id&&project_id) {
               if($('editactivity_'+wizemp_request_id+'_'+project_id+'_'+activity_id)) {
                  setTimeout('$(\"editactivity_'+wizemp_request_id+'_'+project_id+'_'+activity_id+'\").focus()',100);
               }
            }
            if($('editprojkpo_'+wizemp_request_id+'_'+project_id)) {
               setTimeout('$(\"editprojkpo_'+wizemp_request_id+'_'+project_id+'\").focus()',100);
            }
         });
      }
      
      
      var wizemp_kpo_edit = null;
      function wizemp_act_next() {
         //// save current
         ajax_feedback = _caf;
         if(wizemp_act_list&&wizemp_act_list[wizemp_act_current]) {
            var activity_id = wizemp_act_list[wizemp_act_current];
            var project_id = wizemp_project_list[wizemp_project_current];
            activity_saved = 0;
            save_activity(wizemp_request_id,project_id,activity_id,null,null);
         }
         var project_id = wizemp_project_list[wizemp_project_current];
         if($('editprojkpo_'+wizemp_request_id+'_'+project_id)) {
            save_project_kpo(wizemp_request_id,project_id,null,null);
         }
         var n = wizemp_act_current+1;
         var m = wizemp_project_current+1;
         
         if(wizemp_act_list&&wizemp_act_list[n]) {
            var activity_id = wizemp_act_list[n];
            var project_id = wizemp_project_list[wizemp_project_current];
            wizemp_act_get(wizemp_request_id,project_id,activity_id);
         } else if(wizemp_project_list&&wizemp_project_list[m]) {
            var activity_id = 0;
            var project_id = wizemp_project_list[m];
            wizemp_act_get(wizemp_request_id,project_id,activity_id);
         } else {
            wizemp_run_next();
         }
      }
      
      function wizemp_act_prev() {
         ajax_feedback = _caf;
         if(wizemp_act_list&&wizemp_act_list[wizemp_act_current]) {
            var activity_id = wizemp_act_list[wizemp_act_current];
            var project_id = wizemp_project_list[wizemp_project_current];
            save_activity(wizemp_request_id,project_id,activity_id,null,null);
         }
         var n = wizemp_act_current-1;
         var m = wizemp_project_current-1;
         if(wizemp_act_list&&wizemp_act_list[n]) {
            var activity_id = wizemp_act_list[n];
            if(activity_id==10000) activity_id = 0;
            var project_id = wizemp_project_list[wizemp_project_current];
            wizemp_act_get(wizemp_request_id,project_id,activity_id);
         } else if(wizemp_project_list&&wizemp_project_list[m]) {
            var activity_id = -1;
            var project_id = wizemp_project_list[m];
            wizemp_act_get(wizemp_request_id,project_id,activity_id);
         } else {
            wizemp_run_prev();
         }
      }
      
      function wizemp_act_add(request_id,project_id,activity_id,d,e) {
         if(wizemp_act_list&&wizemp_act_list[wizemp_act_current]) {
            var activity_id = wizemp_act_list[wizemp_act_current];
            var project_id = wizemp_project_list[wizemp_project_current];
            save_activity(wizemp_request_id,project_id,activity_id,null,null);
         }
         var dv = $('activitylist_'+request_id+'_'+project_id).appendChild(_dce('div'));
         dv.setAttribute('style','padding:5px;');
         dv.appendChild(progress_span());
         rqjx_app_addActivity(request_id,project_id,function(_data) {
            var data = recjsarray(_data);
            $('activitylist_'+data[0]+'_'+data[1]).innerHTML = data[3];
            $('idptimeframe_start_'+data[0]).innerHTML = data[4];
            $('idptimeframe_stop_'+data[0]).innerHTML = data[5];
            for(var i=0;i<data[6].length;i++) {
               if($('actlist_'+data[6][i][0]+'_'+data[6][i][1])) {
                  $('actlist_'+data[6][i][0]+'_'+data[6][i][1]).innerHTML = data[6][i][2];
               }
            }
            wizemp_act_list = data[7];
            wizemp_act_current = data[8];
            var activity_id = wizemp_act_list[wizemp_act_current];
            var project_id = wizemp_project_list[wizemp_project_current];
            wizemp_act_get(wizemp_request_id,project_id,activity_id);
         });
      
      }
      
      function wizemp_act_confirm_delete(project_id,activity_id,d,e) {
         ///ajax_feedback = _caf;
         var apfrm = $('actformeditor');
         if(apfrm) {
            apfrm.innerHTML = '';
            apfrm.style.textAlign = 'center';
            apfrm.appendChild(progress_span(' ... deleting'));
         }
         $('wizemp_act_delete_btn').style.display = 'none';
         _destroy($('dvactivitylist_'+wizemp_request_id+'_'+project_id+'_'+activity_id));
         rqjx_app_deleteProjectActivity(wizemp_request_id,project_id,activity_id,function(_data) {
            var data = recjsarray(_data);
            for(var i=0;i<data[1].length;i++) {
               if($('actlist_'+data[1][i][0]+'_'+data[1][i][1])) {
                  $('actlist_'+data[1][i][0]+'_'+data[1][i][1]).innerHTML = data[1][i][2];
               }
            }
            $('idptimeframe_start_'+data[0]).innerHTML = data[2];
            $('idptimeframe_stop_'+data[0]).innerHTML = data[3];
            wizemp_act_list = data[4];
            wizemp_act_current--;
            var activity_id = 0;
            var project_id = wizemp_project_list[wizemp_project_current];
            if(wizemp_act_list[wizemp_act_current]) {
               activity_id = wizemp_act_list[wizemp_act_current];
            }
            wizemp_act_get(wizemp_request_id,project_id,activity_id);
         });
         
         /*
         rqjx_app_deleteActionPlan(wizemp_request_id,activity_id,function(_data) {
            if(_data=='FAIL') {
               alert('Fail to delete action plan. Please contact administrator.');
               return;
            }
            var data = recjsarray(_data);
            $('actlist_'+data[0]+'_'+data[1]).innerHTML = data[2];
            $('idptimeframe_start_'+data[0]).innerHTML = data[3];
            $('idptimeframe_stop_'+data[0]).innerHTML = data[4];
            $('idpcost_'+data[0]).innerHTML = data[5];
            wizemp_act_list = data[6];
            wizemp_act_current--;
            var activity_id = 0;
            var project_id = wizemp_project_list[wizemp_project_current];
            if(wizemp_act_list[wizemp_act_current]) {
               activity_id = wizemp_act_list[wizemp_act_current];
            }
            wizemp_act_get(wizemp_request_id,project_id,activity_id);
         });
         */
      }
      
      function wizemp_act_cancel_delete(d,e) {
         var dvbtn = $('wizemp_act_btn');
         dvbtn.innerHTML = dvbtn.oldHTML;
         var dvdel = $('wizemp_act_delete_btn');
         dvdel.style.display = 'none';
         $('wizemp_flow').style.backgroundColor = '#fff';
      }
      
      function wizemp_act_delete(d,e) {
         var dvbtn = $('wizemp_act_btn');
         dvbtn.oldHTML = dvbtn.innerHTML;
         dvbtn.innerHTML = '';
         var dvdel = $('wizemp_act_delete_btn');
         dvdel.style.display = '';
         $('wizemp_flow').style.backgroundColor = '#ffcccc';
      }
      
      
      
      
      /*
      
      competency
      actionplan
      
      */
      
      var wizemp_project_list = Array();
      var wizemp_project_current = 0;
      
      
      
      
      ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
      ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
      ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
      
      
      // --></script>";
      
      return $ret;
      
   }
   


} // HRIS_IDPREQUESTFORM_DEFINED
?>