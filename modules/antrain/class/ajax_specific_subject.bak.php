<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_objective.php                   //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_SPECIFICSUBJECTAJAX_DEFINED') ) {
   define('HRIS_SPECIFICSUBJECTAJAX_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT.'/config.php');
//global $xocpConfig;
require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");
require_once(XOCP_DOC_ROOT."/modules/antrain/modconsts.php");

class _antrain_class_SpecificSubjectAjax extends AjaxListener {
   
   function _antrain_class_SpecificSubjectAjax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/antrain/class/ajax_specific_subject.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_Delete","app_editSession","app_saveSession",
                                             "app_setANTRAINSession","app_newSession" ,"app_propose","app_proposediv","app_approve" ,"app_returnSession", "app_saveReturn" ,"app_returnSessionDivmjr", "app_saveReturnDivmjr" ,"app_returnSessionDir", "app_saveReturnDir" , "app_returnSessionApproval", "app_saveReturnApproval", "app_reminder", "app_send_reminder","app_reminder_omsm","app_send_reminder_omsm","app_editText","app_saveText");
   }
   
/*    function app_setANTRAINSession($args) {
      $_SESSION["antrain_psid"] = $args[0];
   }
    */
	
   //fahmi punya
   
   function app_reminder($args) {
      

//	 mail("prince.fachmy@gmail.com","subject","pesan");

	  $db=&Database::getInstance();
      $psid = $args[0];
      $id_modified = getUserID();
	  $date_modified  = getSQLDate();
     
      $ret = "<form id='frm' style='padding-top: 5px;margin-top: 12px;margin-bottom: 10px;'><table class='xxfrm' style='width:12%; float: right;'><colgroup><col width='160'/><col/></colgroup><tbody>"
				. "<tr><td>Send Reminder e-mail?</td></tr>"; 
          
       $ret .= "<tr><td colspan='2'><span id='progress'></span>&nbsp;"
				. "<input id='btn_save_session' onclick='send_reminder();' type='button' value='Send'/>&nbsp;"
				. "<input id='btn_cancel_edit' onclick='cancel_return();' type='button' value='"._CANCEL."'/>&nbsp;&nbsp;"
				//. ($psid!="new"?"<input id='btn_delete_session' onclick='delete_session();' type='button' value='"._DELETE."'/>":"")
				. "</td></tr>"
				. "</tbody></table></form>";
      return $ret;
   }
   
    function app_reminder_omsm($args) {
      

//	 mail("prince.fachmy@gmail.com","subject","pesan");

	  $db=&Database::getInstance();
      $psid = $args[0];
      $id_modified = getUserID();
	  $date_modified  = getSQLDate();
      
      $ret = "<form id='frm' style='padding-top: 5px;margin-top: 12px;margin-bottom: 10px;'><table class='xxfrm' style='width:12%; float: right;'><colgroup><col width='160'/><col/></colgroup><tbody>"
         
         
		   . "<tr><td>Send Reminder e-mail?</td></tr>"; 
          
           $ret .= "<tr><td colspan='2'><span id='progress'></span>&nbsp;"
           . "<input id='btn_save_session' onclick='send_reminder_omsm();' type='button' value='Send'/>&nbsp;"
           . "<input id='btn_cancel_edit' onclick='cancel_return();' type='button' value='"._CANCEL."'/>&nbsp;&nbsp;"
           //. ($psid!="new"?"<input id='btn_delete_session' onclick='delete_session();' type='button' value='"._DELETE."'/>":"")
           . "</td></tr>"
           . "</tbody></table></form>";
		   
      return $ret;
   
   }
   
   function mail_alert($email,$body,$subject,$smtp) {
   include_once(XOCP_DOC_ROOT.'/config.php');
         include_once('Mail.php');
         
         $recipient = $email;
         $headers["From"]    = "hris@mcci.com";
         $headers["To"]      = $recipient;
         $headers["Subject"] = "[HRIS] ". $subject;
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
         
         if($params['host']=="") {
            return;
         }
         
         
         // Create the mail object using the Mail::factory method
         ob_start();
         $mail_object =& Mail::factory('smtp', $params);
         $mail_object->send($recipient, $headers, $body);
         //$str = ob_get_contents();//
         $str = "Email Sent";
         ob_end_clean();
        
        //echo "$str\n";
		return $str;
}
   
   
   //REMINDER BUTTON
   function app_send_reminder() {
      include_once(XOCP_DOC_ROOT.'/config.php');
    include_once('Mail.php');
	$db=&Database::getInstance();
	//$psid = $args[0];
		
	$sqljob = "SELECT j.job_id,j.upper_job_id,j.gradeval FROM hris_employee_job j LEFT JOIN hris_users u ON (u.person_id = j.employee_id) WHERE u.user_id = ".getUserid()."";
		$resultjob = $db->query($sqljob);
		 list($job_id,$upper_job_id,$grade)=$db->fetchRow($resultjob);		
	
		$sqljobup = "SELECT j.job_id,j.upper_job_id FROM hris_employee_job j LEFT JOIN hris_users u ON (u.person_id = j.employee_id) WHERE j.job_id = $upper_job_id ";
		$resultjobup = $db->query($sqljobup);
		 list($job_id_up,$upper_job_id_up)=$db->fetchRow($resultjobup);		

//ADM DIV MGR
	if ($job_id == 146) 
		{ 
			$jobidto = 146;
		} 
	//CLERK
	elseif ($grade == 4 || $grade == 3) 
		{ 
			$jobidto = $upper_job_id_up;
		} 
	else 
		{ 
			$jobidto = $upper_job_id;
		} 
	
	//QUERY WITH FILTER JOB_ID

	$sqlto = "SELECT p.user_id, u.email, u.person_nm, u.smtp_location FROM hris_employee_job j LEFT JOIN hris_persons u ON ( u.person_id = j.employee_id )  LEFT JOIN hris_users p ON ( u.person_id = p.person_id ) WHERE j.job_id = '$jobidto'";    	
	$resultto = $db->query($sqlto);
	 list($user_id_proposed,$emailto,$namato,$smtpto)=$db->fetchRow($resultto); 
	
	$sqlfrom = "SELECT p.user_id,ps.email,ps.person_nm,ps.smtp_location FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id WHERE p.user_id= ".getuserid();"";  
    $resultfrom = $db->query($sqlfrom);
	list($user_id_approved,$emailfrom,$namafrom,$smtpfrom)=$db->fetchRow($resultfrom);
	

	//cc mail list
	$sqlcc = "SELECT email,name FROM antrainss_cc_email";
	$resultcc = $db->query($sqlcc);
	$smtp = "JKMS01";
	$to      = 	$this->emailmci($emailto);
	
	$emailcc = "";
		 $nummail = $db->getRowsNum($resultcc);
		 $i = 0;
		 while(list($ccmail,$ccname)=$db->fetchRow($resultcc)){
			 $emailcc .= $this->emailmci($ccmail);
			 if(++$i == $nummail )
			 {
				$emailcc .= "";
				$ccname .= "";
			 }
			 else
			 {
			 $emailcc .= ",";
			 $ccname .= ",";
			 }
		 
		 }
	
		 $subject = 	"Reminder $namato";

         $recipient = $to.",".$emailcc;
         $headers["From"]    = "hris@mcci.com";
         $headers["To"]      = $to;
		 $headers["Cc"] =  $ccname;  
         $headers["Subject"] = "[HRIS SPECIFIC SUBJECT TRAINING REMINDER] ". $subject;
         $headers["MIME-Version"] = "1.0";
         $headers["Content-type"] = "text/html; charset=iso-8859-1";
         $body = 	"<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'><html xmlns='http://www.w3.org/1999/xhtml'>
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
									<tr><td style='border-right:1px solid #bbb;'></td></tr>
									<tr><td colspan='2' style='background-color:#5666b3;'>&nbsp;</td></tr>
									<tr><td colspan='2'>

									<p>
									Dear $namato
									<br/>
									Annual Training - Specific Subject Training form has been submitted to you, please <a href='/hris/index.php?XP_antrainplanss'>check</a> before approval.
									<br/>
									Or click this <a href='/hris/index.php?XP_antrainplanss'>link.</a>
									<br/>
									<br/>
									Thank You
									<br/>
									<br/>
									$namafrom
									</p>

									</td></tr>

									<tr><td colspan='2' style='background-color:#5666b3;'>&nbsp;</td></tr>


									</tbody>
									</table>


									</body>
									</html>";
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
         
         if($params['host']=="") {
            return;
         }
         
         
         // Create the mail object using the Mail::factory method
         ob_start();
         $mail_object =& Mail::factory('smtp', $params);
        if($mail_object->send($recipient, $headers, $body))
		 {
			$str = "Email Sent to".$namato."-".$emailto ;
			ob_end_clean();
		}
		else 
		{
			$str = "not sent";
		} 
		
		 $smtp = "MKMS01";
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
         
         if($params['host']=="") {
            return;
         }
         
         
         ob_start();
         $mail_object =& Mail::factory('smtp', $params);
        if($mail_object->send($recipient, $headers, $body))
		 {
			$str = "Email Sent to $namato - $emailto";
			ob_end_clean();
		}
		else 
		{
			$str = "not sent";
		} 
		
		
		return array( $str);
   }
   
   
    function app_send_reminder_omsm() {
    include_once(XOCP_DOC_ROOT.'/config.php');
    include_once('Mail.php');
	$db=&Database::getInstance();
	$sqljob = "SELECT j.job_id,j.upper_job_id,j.gradeval FROM hris_employee_job j LEFT JOIN hris_users u ON (u.person_id = j.employee_id) WHERE u.user_id = ".getUserid()."";
		$resultjob = $db->query($sqljob);
		 list($job_id,$upper_job_id,$grade)=$db->fetchRow($resultjob);		
	
		$sqljobup = "SELECT j.job_id,j.upper_job_id FROM hris_employee_job j LEFT JOIN hris_users u ON (u.person_id = j.employee_id) WHERE j.job_id = $upper_job_id ";
		$resultjobup = $db->query($sqljobup);
		 list($job_id_up,$upper_job_id_up)=$db->fetchRow($resultjobup);		

//ADM DIV MGR
	if ($job_id == 146) 
		{ 
			$jobidto = 146;
		} 
	//CLERK
	elseif ($grade == 4 || $grade == 3) 
		{ 
			$jobidto = $upper_job_id_up;
		} 
	else 
		{ 
			$jobidto = $upper_job_id;
		} 
	
	//QUERY WITH FILTER JOB_ID

	$sqlto = "SELECT p.user_id, u.email, u.person_nm, u.smtp_location FROM hris_employee_job j LEFT JOIN hris_persons u ON ( u.person_id = j.employee_id )  LEFT JOIN hris_users p ON ( u.person_id = p.person_id ) WHERE j.job_id = '$jobidto'";    	
	list($user_id_proposed,$emailto,$namato,$smtpto)=$db->fetchRow($resultto); 
	
	$sqlfrom = "SELECT p.user_id,ps.email,ps.person_nm,ps.smtp_location FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id WHERE p.user_id= ".getuserid();"";  
    $resultfrom = $db->query($sqlfrom);
	list($user_id_approved,$emailfrom,$namafrom,$smtpfrom)=$db->fetchRow($resultfrom);
	

	//cc mail list
	$sqlcc = "SELECT email FROM antrainss_cc_email";
	$resultcc = $db->query($sqlcc);
	$smtp = "JKMS01";
	$to      = 	$this->emailmci($emailto);
	
	$emailcc = "";
		 $nummail = $db->getRowsNum($resultcc);
		 $i = 0;
		 while(list($ccmail)=$db->fetchRow($resultcc)){
		 $emailcc .= $this->emailmci($ccmail);
		 if(++$i == $nummail )
		 {
			$emailcc .= "";
		 }
		 else
		 {
		 $emailcc .= ",";
		 }
		 
		 }
	
	$subject = 	"Reminder $namato";

         $recipient = $to.",".$emailcc;
         $headers["From"]    = "hris@mcci.com";
         $headers["To"]      = $namato ."-". $emailto; //$this->emailmci($emailcc);
		 $headers["Cc"] =  $emailcc;  
         $headers["Subject"] = "[HRIS SPECIFIC SUBJECT TRAINING REMINDER". $subject;
         $headers["MIME-Version"] = "1.0";
         $headers["Content-type"] = "text/html; charset=iso-8859-1";
         $body = 	"<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
									<html xmlns='http://www.w3.org/1999/xhtml'>
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
									<tr><td style='border-right:1px solid #bbb;'></td></tr>
									<tr><td colspan='2' style='background-color:#5666b3;'>&nbsp;</td></tr>
									<tr><td colspan='2'>

									<p>
									Dear $namato
									<br/>
									Annual Training - Specific Subject Training form has been submitted to you, please <a href='/hris/index.php?XP_antrainplanss'>check</a> before approval.
									<br/>
									Or click this <a href='/hris/index.php?XP_antrainplanss'>link.</a>
									<br/>
									<br/>
									Thank You
									<br/>
									<br/>
									$namafrom
									</p>

									</td></tr>

									<tr><td colspan='2' style='background-color:#5666b3;'>&nbsp;</td></tr>


									</tbody>
									</table>


									</body>
									</html>";
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
         
         if($params['host']=="") {
            return;
         }
         
         
         // Create the mail object using the Mail::factory method
         ob_start();
         $mail_object =& Mail::factory('smtp', $params);
        if($mail_object->send($recipient, $headers, $body))
		 {
			$str = "Email Sent";
			ob_end_clean();
		}
		else 
		{
			$str = "not sent";
		} 
		
		$smtp = "MKMS01";
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
         
         if($params['host']=="") {
            return;
         }
         
         
         ob_start();
         $mail_object =& Mail::factory('smtp', $params);
        if($mail_object->send($recipient, $headers, $body))
		 {
			$str = "Email Sent";
			ob_end_clean();
		}
		else 
		{
			$str = "not sent";
		} 
		return array( $str);
   //return array ($str);
   }
   
   
   function app_propose($args) {
      include_once(XOCP_DOC_ROOT.'/config.php');
	include_once('Mail.php');
		$db=&Database::getInstance();
		$psid = $_SESSION["pms_psid"];
		
	$sqljob = "SELECT j.job_id,j.upper_job_id,j.gradeval FROM hris_employee_job j LEFT JOIN hris_users u ON (u.person_id = j.employee_id) WHERE u.user_id = ".getUserid()."";
		$resultjob = $db->query($sqljob);
		 list($job_id,$upper_job_id,$grade)=$db->fetchRow($resultjob);		
	
		$sqljobup = "SELECT j.job_id,j.upper_job_id FROM hris_employee_job j LEFT JOIN hris_users u ON (u.person_id = j.employee_id) WHERE j.job_id = $upper_job_id ";
		$resultjobup = $db->query($sqljobup);
		 list($job_id_up,$upper_job_id_up)=$db->fetchRow($resultjobup);		

//ADM DIV MGR
	if ($job_id == 146) 
		{ 
			$jobidto = 146;
		} 
	//CLERK
	elseif ($grade == 4 || $grade == 3) 
		{ 
			$jobidto = $upper_job_id_up;
		} 
	else 
		{ 
			$jobidto = $upper_job_id;
		} 
	
	 $sqlto = "SELECT p.user_id, u.email, u.person_nm, u.smtp_location FROM hris_employee_job j LEFT JOIN hris_persons u ON ( u.person_id = j.employee_id )  LEFT JOIN hris_users p ON ( u.person_id = p.person_id ) WHERE j.job_id = '$jobidto'";    
     $resultto = $db->query($sqlto);
     list($user_id_proposed,$emailto,$namato,$smtpto)=$db->fetchRow($resultto);
	
	$sqlfrom = "SELECT p.user_id,ps.email,ps.person_nm,ps.smtp_location FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id WHERE p.user_id= ".getuserid();"";  
    $resultfrom = $db->query($sqlfrom);
	list($user_id_approved,$emailfrom,$namafrom,$smtpfrom)=$db->fetchRow($resultfrom);
	
	$sqlcc = "SELECT email FROM antrainss_cc_email";
	$resultcc = $db->query($sqlcc);

	$smtp = "JKMS01";
	$to      = 	$this->emailmci($emailto);
	
	$emailcc = "";
		 $nummail = $db->getRowsNum($resultcc);
		 $i = 0;
		 while(list($ccmail,$ccname)=$db->fetchRow($resultcc)){
			 $emailcc .= $this->emailmci($ccmail);
			 if(++$i == $nummail )
			 {
				$emailcc .= "";
				$ccname .= "";
			 }
			 else
			 {
			 $emailcc .= ",";
			 $ccname .= ",";
			 }
		 
		 }
	
		 $subject = 	"Reminder $namato";

	$subject = 	"Reminder $namato";

	     $recipient = $to;
         $headers["From"]    = "hris@mcci.com";
         $headers["To"]      = $emailto;
		 $headers["Cc"] =  $emailcc;  
         $headers["Subject"] = "[HRIS SPECIFIC SUBJECT TRAINING REMINDER] ". $subject;
         $headers["MIME-Version"] = "1.0";
         $headers["Content-type"] = "text/html; charset=iso-8859-1";
         $body = 	"<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'><html xmlns='http://www.w3.org/1999/xhtml'>
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
									<tr><td style='border-right:1px solid #bbb;'></td></tr>
									<tr><td colspan='2' style='background-color:#5666b3;'>&nbsp;</td></tr>
									<tr><td colspan='2'>

									<p>
									Dear $namato
									<br/>
									Annual Training - Specific Subject Training form has been submitted to you, please <a href='/hris/index.php?XP_antrainplanss'>check</a> before approval.
									<br/>
									Or click this <a href='/hris/index.php?XP_antrainplanss'>link.</a>
									<br/>
									<br/>
									Thank You
									<br/>
									<br/>
									$namafrom 
									</p>

									</td></tr>

									<tr><td colspan='2' style='background-color:#5666b3;'>&nbsp;</td></tr>


									</tbody>
									</table>


									</body>
									</html>";
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
         
         if($params['host']=="") {
            return;
         }
         
         
         // Create the mail object using the Mail::factory method
         ob_start();
         $mail_object =& Mail::factory('smtp', $params);
		$mail_object->send($recipient, $headers, $body);
         $str = "Email Sent";
         ob_end_clean();
		
		$id_propose = getUserId();
		$is_propose = '1';
		$date_propose = getSQLDate();
		$proposed_by =  getuserFullname();
        $sql = "UPDATE antrain_sessionss SET id_proposed = '$id_propose', is_proposed = $is_propose, date_proposed = '$date_propose' WHERE psid = '$psid'";
        $db->query($sql);
		$date_propose = date('d/M/Y', strtotime($date_propose));
//		return array($psid,$id_propose,$is_propose,$date_propose,$proposed_by);
		
		$smtp = "MKMS01";
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
         
         if($params['host']=="") {
            return;
         }
         

        
		ob_start();
         $mail_object =& Mail::factory('smtp', $params);
         if($mail_object->send($recipient, $headers, $body))
		 {
			$str = "Email Sent";
			ob_end_clean();
		}
		else 
		{
			$str = "not sent";
		} 
 

		return array($psid,$id_propose,$is_propose,$date_propose,$proposed_by,$str);

   }
   
   function app_proposediv($args) {
      include_once(XOCP_DOC_ROOT.'/config.php');
	include_once('Mail.php');
		$db=&Database::getInstance();
		$psid = $_SESSION["pms_psid"];
		
	$sqljob = "SELECT j.job_id,j.upper_job_id,j.gradeval FROM hris_employee_job j LEFT JOIN hris_users u ON (u.person_id = j.employee_id) WHERE u.user_id = ".getUserid()."";
		$resultjob = $db->query($sqljob);
		 list($job_id,$upper_job_id,$grade)=$db->fetchRow($resultjob);		
	
		$sqljobup = "SELECT j.job_id,j.upper_job_id FROM hris_employee_job j LEFT JOIN hris_users u ON (u.person_id = j.employee_id) WHERE j.job_id = $upper_job_id ";
		$resultjobup = $db->query($sqljobup);
		 list($job_id_up,$upper_job_id_up)=$db->fetchRow($resultjobup);		

//ADM DIV MGR
	if ($job_id == 146) 
		{ 
			$jobidto = 146;
		} 
	//CLERK
	elseif ($grade == 4 || $grade == 3) 
		{ 
			$jobidto = $upper_job_id_up;
		} 
	else 
		{ 
			$jobidto = $upper_job_id;
		} 
	
	 $sqlto = "SELECT p.user_id, u.email, u.person_nm, u.smtp_location FROM hris_employee_job j LEFT JOIN hris_persons u ON ( u.person_id = j.employee_id )  LEFT JOIN hris_users p ON ( u.person_id = p.person_id ) WHERE j.job_id = '$jobidto'";    
     $resultto = $db->query($sqlto);
     list($user_id_proposed,$emailto,$namato,$smtpto)=$db->fetchRow($resultto);
	
	$sqlfrom = "SELECT p.user_id,ps.email,ps.person_nm,ps.smtp_location FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id WHERE p.user_id= ".getuserid();"";  
    $resultfrom = $db->query($sqlfrom);
	list($user_id_approved,$emailfrom,$namafrom,$smtpfrom)=$db->fetchRow($resultfrom);
	
	$sqlcc = "SELECT email FROM antrainss_cc_email";
	$resultcc = $db->query($sqlcc);

	$smtp = "JKMS01";
	$to      = 	$this->emailmci($emailto);
	$emailcc = "";
		 $nummail = $db->getRowsNum($resultcc);
		 $i = 0;
		 while(list($ccmail)=$db->fetchRow($resultcc)){
		 $emailcc .= $this->emailmci($ccmail);
		 if(++$i == $nummail )
		 {
			$emailcc .= "";
		 }
		 else
		 {
		 $emailcc .= ",";
		 }
		 
		 }
	$to      = 		$to.",".$emailcc;
	$subject = 	"Reminder $namato";

	     $recipient = $to;
         $headers["From"]    = "hris@mcci.com";
         $headers["To"]      = $emailto;
		 $headers["Cc"] =  $emailcc;  
         $headers["Subject"] = "[HRIS TRAINING REMINDER] ". $subject;
         $headers["MIME-Version"] = "1.0";
         $headers["Content-type"] = "text/html; charset=iso-8859-1";
         $body = 	"<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'><html xmlns='http://www.w3.org/1999/xhtml'>
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
									<tr><td style='border-right:1px solid #bbb;'></td></tr>
									<tr><td colspan='2' style='background-color:#5666b3;'>&nbsp;</td></tr>
									<tr><td colspan='2'>

									<p>
									Dear $namato
									<br/>
									Annual Training - Specific Subject Training form has been submitted to you, please <a href='/hris/index.php?XP_antrainplanss'>check</a> before approval.
									<br/>
									Or click this <a href='/hris/index.php?XP_antrainplanss'>link.</a>
									<br/>
									<br/>
									Thank You
									<br/>
									<br/>
									$namafrom 
									</p>

									</td></tr>

									<tr><td colspan='2' style='background-color:#5666b3;'>&nbsp;</td></tr>


									</tbody>
									</table>


									</body>
									</html>";
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
         
         if($params['host']=="") {
            return;
         }
         
         
         // Create the mail object using the Mail::factory method
         ob_start();
         $mail_object =& Mail::factory('smtp', $params);
		$mail_object->send($recipient, $headers, $body);
         $str = "Email Sent";
         ob_end_clean();
		
		$id_proposediv = getUserId();
		$is_proposediv = '1';
		$date_proposediv = getSQLDate();
		$proposeddiv_by =  getuserFullname();
        $sql = "UPDATE antrain_sessionss SET id_proposeddiv = '$id_proposediv', is_proposeddiv = $is_proposediv, date_proposeddiv = '$date_proposediv' WHERE psid = '$psid'";
        $db->query($sql);
		$date_proposediv = date('d/M/Y', strtotime($date_proposediv));
//		return array($psid,$id_propose,$is_propose,$date_propose,$proposed_by);
			
		$smtp = "MKMS01";
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
         
         if($params['host']=="") {
            return;
         }
         
         
         ob_start();
         $mail_object =& Mail::factory('smtp', $params);
       if($mail_object->send($recipient, $headers, $body))
		 {
			$str = "Email Sent";
			ob_end_clean();
		}
		else 
		{
			$str = "not sent";
		}  

		return array($psid,$id_proposeddiv,$is_proposeddiv,$date_proposeddiv,$proposeddiv_by,$str);

   }
   
    function app_approve($args) {
	
	include_once(XOCP_DOC_ROOT.'/config.php');
	include_once('Mail.php');
		$db=&Database::getInstance();
		$psid = $_SESSION["pms_psid"];
		$id_approve = getUserId();
		$is_approve = '1';
		$date_approve = getSQLDate();
		$approved_by =  "".getUserFullname()."";
        $sql = "UPDATE antrain_sessionss SET id_approved = '$id_approve', is_approved = $is_approve, date_approved = '$date_approve' WHERE psid = '$psid'";
        $db->query($sql);
		$date_approve = date('d/M/Y', strtotime($date_approve));
		
	
		return array($psid,$id_approve,$is_approve,$date_approve,$approved_by);
   }
	
	
	 function app_returnSession($args) {
      


	  $db=&Database::getInstance();
      $psid = $args[0];
      $id_modified = getUserID();
	  $date_modified  = getSQLDate();
/* 	$sql = "SELECT year,budget,remark,date_created"
             . " FROM antrain_sessionss"
             . " WHERE psid = '$psid'"; */
	  $sql =  "SELECT p.year, p.budget, p.remark, pk.org_nm FROM antrain_sessionss p"
			  . " LEFT JOIN hris_orgs pk ON p.org_id = pk.org_id"
			  . " WHERE psid = '$psid' ORDER BY year DESC " ;
	
         $result = $db->query($sql);
    	 
       
	   list($year,$budget,$remark,$org_nm)=$db->fetchRow($result);
        $year = htmlentities($year,ENT_QUOTES);
		 

      $ret = "<form id='frm' style='padding-top:10px;'><table class='xxfrm' style='width:100%;'><colgroup><col width='160'/><col/></colgroup><tbody>"
         
           . "<tr><td>Year</td><td><input type='text' value=\"$year\" id='inp_year' name='year' style='width:50px;' readonly/></td></tr>" 
		   . "<tr><td>Budget</td><td><input type='text' value=\"$budget\" id='inp_budget' name='budget' style='width:50px;'/></td></tr>" 
		   . "<tr><td>Div/Section</td><td><p>$org_nm</p></td></tr>" 
		   . "<tr><td>Remark</td><td><input type='text' value=\"$remark\" id='inp_remark' name='remark' style='width:95%;'/></td></tr>"; 
          
           $ret .= "<tr><td colspan='2'><span id='progress'></span>&nbsp;"
           . "<input id='btn_save_session' onclick='save_return($psid);' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input id='btn_cancel_edit' onclick='cancel_return();' type='button' value='"._CANCEL."'/>&nbsp;&nbsp;"
           //. ($psid!="new"?"<input id='btn_delete_session' onclick='delete_session();' type='button' value='"._DELETE."'/>":"")
           . "</td></tr>"
           . "</tbody></table></form>";
      return $ret;
   }
	
	function app_saveReturn($args) {
	include_once(XOCP_DOC_ROOT.'/config.php');
	include_once('Mail.php');
	$db=&Database::getInstance();
	$psid = $args[0];
	
	$sqlto = "SELECT p.user_id, ps.email, ps.person_nm FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id LEFT JOIN antrain_sessionss pk ON ( p.user_id = pk.id_created ) WHERE pk.psid = $psid"; $resultto = $db->query($sqlto);
	list($user_id_proposed,$emailto,$namato)=$db->fetchRow($resultto); 
	
	
	$sqlfrom = "SELECT p.user_id,ps.email,ps.person_nm FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id WHERE p.user_id= ".getuserid();"";  
    $resultfrom = $db->query($sqlfrom);
	list($user_id_approved,$emailfrom,$namafrom,$date_approved)=$db->fetchRow($resultfrom);
	
	$sqlcc = "SELECT email FROM antrainss_cc_email";
	$resultcc = $db->query($sqlcc);

	$to      = 		$this->emailmci($emailto);
	$subject = 	"Reminder $namato";
	
	$smtp = "JKMS01";
	$emailcc = "";
		 $nummail = $db->getRowsNum($resultcc);
		 $i = 0;
		 while(list($ccmail)=$db->fetchRow($resultcc)){
		 $emailcc .= $this->emailmci($ccmail);
		 if(++$i == $nummail )
		 {
			$emailcc .= "";
		 }
		 else
		 {
		 $emailcc .= ",";
		 }
		 
		 }
	$to      = 		$to.",".$emailcc;
	$subject = 	"Revision $namato";
	
         $recipient = $to;
         $headers["From"]    = "hris@mcci.com";
         $headers["To"]      = $emailto;
		
		 $headers["Cc"] =  $emailcc;  
         $headers["Subject"] = "[HRIS SPECIFIC SUBJECT TRAINING RETURN] ". $subject;
         $headers["MIME-Version"] = "1.0";
         $headers["Content-type"] = "text/html; charset=iso-8859-1";
         $body = 	"<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'><html xmlns='http://www.w3.org/1999/xhtml'>
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
									<tr><td style='border-right:1px solid #bbb;'></td></tr>
									<tr><td colspan='2' style='background-color:#5666b3;'>&nbsp;</td></tr>
									<tr><td colspan='2'>

									<p>
									 Dear $namato 
									 <br/>
									 <br/>
									I have been return your Specific subject Annual Training plan year $year please <a href='/hris/index.php?XP_antrainplanss'>check</a> again.
									<br/>
									Or click this <a href='/hris/index.php?XP_antrainplanss'>link.</a>
									<br/>
									<br/>
									Thank You
									<br/>
									$namafrom
									</p>

									</td></tr>

									<tr><td colspan='2' style='background-color:#5666b3;'>&nbsp;</td></tr>


									</tbody>
									</table>


									</body>
									</html>";
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
         
         if($params['host']=="") {
            return;
         }
         
         
         // Create the mail object using the Mail::factory method
         ob_start();
         $mail_object =& Mail::factory('smtp', $params);
		  $mail_object->send($recipient, $headers, $body);
         $str = "Email Sent";
         ob_end_clean();
		 //return array($str);
	
	
      $user_id = getUserID();
     
      $vars = parseForm($args[1]);
      $year = _bctrim(bcadd(0,$vars["year"]));
	  $budget = _bctrim(bcadd(0,$vars["budget"]));
	  $remark = addslashes(trim($vars["remark"]));
	  $id_created = getUserID();
	  $id_modified = getUserID();
	  $date_created = getSQLDate($vars["date_created"]);
	  $date_modified =  getSQLDate($vars["date_modified"]);
	
	  //$org_nm = addslashes(trim($vars["org_nm"]));
	  $sql = "UPDATE antrain_sessionss SET "
              . "year = '$year', budget = '$budget', remark = '$remark', id_modified = '$id_modified', date_modified = '$date_modified'"			  
              . " WHERE psid = '$psid'";
      $db->query($sql);
	  
	  	$smtp = "MKMS01";
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
         
         if($params['host']=="") {
            return;
         }
         
         
         ob_start();
         $mail_object =& Mail::factory('smtp', $params);
        if($mail_object->send($recipient, $headers, $body))
		 {
			$str = "Email Sent";
			ob_end_clean();
		}
		else 
		{
			$str = "not sent";
		} 
	  		 
   return array($psid,$year,$remark,$str);
  //    return array($psid,$year,$remark);
	 
   }
	
	function app_returnSessionDivmjr($args) {
      

//	 mail("prince.fachmy@gmail.com","subject","pesan");

	  $db=&Database::getInstance();
      $psid = $args[0];
      $id_modified = getUserID();
	  $date_modified  = getSQLDate();

	  $sql =  "SELECT p.year, p.budget, p.remark, pk.org_nm FROM antrain_sessionss p"
			  . " LEFT JOIN hris_orgs pk ON p.org_id = pk.org_id"
			  . " WHERE psid = '$psid' ORDER BY year DESC " ;
	
         $result = $db->query($sql);
    	 
       
	   list($year,$budget,$remark,$org_nm)=$db->fetchRow($result);
        $year = htmlentities($year,ENT_QUOTES);
		 
	 //  list($org_id,$org_nm,$org_class_id)=$db->fetchRow($result2);
     //    $org_nm = htmlentities($org_nm,ENT_QUOTES);		
      
      $ret = "<form id='frm' style='padding-top:10px;'><table class='xxfrm' style='width:100%;'><colgroup><col width='160'/><col/></colgroup><tbody>"
         
           . "<tr><td>Year</td><td><input type='text' value=\"$year\" id='inp_year' name='year' style='width:50px;' readonly/></td></tr>" 
		   . "<tr><td>Budget</td><td><input type='text' value=\"$budget\" id='inp_budget' name='budget' style='width:50px;'/></td></tr>" 
		   . "<tr><td>Div/Section</td><td><p>$org_nm</p></td></tr>" 
		   . "<tr><td>Remark</td><td><input type='text' value=\"$remark\" id='inp_remark' name='remark' style='width:95%;'/></td></tr>"; 
          
           $ret .= "<tr><td colspan='2'><span id='progress'></span>&nbsp;"
           . "<input id='btn_save_session' onclick='save_return_divmjr($psid);' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input id='btn_cancel_edit' onclick='cancel_return_divmjr();' type='button' value='"._CANCEL."'/>&nbsp;&nbsp;"
           //. ($psid!="new"?"<input id='btn_delete_session' onclick='delete_session();' type='button' value='"._DELETE."'/>":"")
           . "</td></tr>"
           . "</tbody></table></form>";
      return $ret;
   }
	
	function app_saveReturnDivmjr($args) {
	include_once(XOCP_DOC_ROOT.'/config.php');
	include_once('Mail.php');
	$db=&Database::getInstance();
	$psid = $args[0];
	
	$sqlto = "SELECT p.user_id, ps.email, ps.person_nm FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id LEFT JOIN antrain_sessionss pk ON ( p.user_id = pk.id_proposed ) WHERE pk.psid = $psid";    	
	$resultto = $db->query($sqlto);
	 list($user_id_proposed,$emailto,$namato)=$db->fetchRow($resultto); 
	
	
	$sqlfrom = "SELECT p.user_id,ps.email,ps.person_nm FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id WHERE p.user_id= ".getuserid();"";  
    $resultfrom = $db->query($sqlfrom);
	list($user_id_approved,$emailfrom,$namafrom,$date_approved)=$db->fetchRow($resultfrom);
	
	$sqlcc = "SELECT email FROM antrainss_cc_email";
	$resultcc = $db->query($sqlcc);

	$to      = 		$this->emailmci($emailto);
	$subject = 	"Reminder $namato";
	
	$smtp = "JKMS01";
	$emailcc = "";
		 $nummail = $db->getRowsNum($resultcc);
		 $i = 0;
		 while(list($ccmail)=$db->fetchRow($resultcc)){
		 $emailcc .= $this->emailmci($ccmail);
		 if(++$i == $nummail )
		 {
			$emailcc .= "";
		 }
		 else
		 {
		 $emailcc .= ",";
		 }
		 
		 }
	
	$subject = 	"Revision $namato";
	
         $recipient = $to.",".$emailcc;
         $headers["From"]    = "hris@mcci.com";
         $headers["To"]      = $emailto;
		
		 $headers["Cc"] =  $emailcc;  
         $headers["Subject"] = "[HRIS SPECIFIC SUBJECT TRAINING RETURN] ". $subject;
         $headers["MIME-Version"] = "1.0";
         $headers["Content-type"] = "text/html; charset=iso-8859-1";
         $body = 	"<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'><html xmlns='http://www.w3.org/1999/xhtml'>
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
									<tr><td style='border-right:1px solid #bbb;'></td></tr>
									<tr><td colspan='2' style='background-color:#5666b3;'>&nbsp;</td></tr>
									<tr><td colspan='2'>

									<p>
									 Dear $namato 
									 <br/>
									 <br/>
									I have been return your Specific subject Annual Training plan year $year please <a href='/hris/index.php?XP_antrainplanss'>check</a> again.
									<br/>
									Or click this <a href='/hris/index.php?XP_antrainplanss'>link.</a>
									<br/>
									<br/>
									Thank You
									<br/>
									$namafrom
									</p>

									</td></tr>

									<tr><td colspan='2' style='background-color:#5666b3;'>&nbsp;</td></tr>


									</tbody>
									</table>


									</body>
									</html>";
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
         
         if($params['host']=="") {
            return;
         }
         
         
         // Create the mail object using the Mail::factory method
         ob_start();
         $mail_object =& Mail::factory('smtp', $params);
		$mail_object->send($recipient, $headers, $body);
         $str = "Email Sent";
         ob_end_clean();
		 //return array($str);
	
	
      $user_id = getUserID();
     
      $vars = parseForm($args[1]);
      $year = _bctrim(bcadd(0,$vars["year"]));
	  $budget = _bctrim(bcadd(0,$vars["budget"]));
	  $remark = addslashes(trim($vars["remark"]));
	  $id_created = getUserID();
	  $id_modified = getUserID();
	  $date_created = getSQLDate($vars["date_created"]);
	  $date_modified =  getSQLDate($vars["date_modified"]);
	
	  //$org_nm = addslashes(trim($vars["org_nm"]));
	  $sql = "UPDATE antrain_sessionss SET "
              . "year = '$year', budget = '$budget', remark = '$remark', id_modified = '$id_modified', date_modified = '$date_modified'"			  
              . " WHERE psid = '$psid'";
      $db->query($sql);
	  
	  	$smtp = "MKMS01";
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
         
         if($params['host']=="") {
            return;
         }
         
        // EMAIL NONAKTIF 
        ob_start();
       $mail_object =& Mail::factory('smtp', $params);
        if($mail_object->send($recipient, $headers, $body))
		 {
			$str = "Email Sent";
			ob_end_clean();
		}
		else 
		{
			$str = "not sent";
		} 
	  		 
   return array($psid,$year,$remark,$str);

   }
	
	function app_returnSessionDir($args) {
      

//	 mail("prince.fachmy@gmail.com","subject","pesan");

	  $db=&Database::getInstance();
      $psid = $args[0];
      $id_modified = getUserID();
	  $date_modified  = getSQLDate();

	  $sql =  "SELECT p.year, p.budget, p.remark, pk.org_nm FROM antrain_sessionss p"
			  . " LEFT JOIN hris_orgs pk ON p.org_id = pk.org_id"
			  . " WHERE psid = '$psid' ORDER BY year DESC " ;
	
         $result = $db->query($sql);
    	 
       
	   list($year,$budget,$remark,$org_nm)=$db->fetchRow($result);
        $year = htmlentities($year,ENT_QUOTES);
		 
	 //  list($org_id,$org_nm,$org_class_id)=$db->fetchRow($result2);
     //    $org_nm = htmlentities($org_nm,ENT_QUOTES);		
      
      $ret = "<form id='frm' style='padding-top:10px;'><table class='xxfrm' style='width:100%;'><colgroup><col width='160'/><col/></colgroup><tbody>"
         
           . "<tr><td>Year</td><td><input type='text' value=\"$year\" id='inp_year' name='year' style='width:50px;' readonly/></td></tr>" 
		   . "<tr><td>Budget</td><td><input type='text' value=\"$budget\" id='inp_budget' name='budget' style='width:50px;'/></td></tr>" 
		   . "<tr><td>Div/Section</td><td><p>$org_nm</p></td></tr>" 
		   . "<tr><td>Remark</td><td><input type='text' value=\"$remark\" id='inp_remark' name='remark' style='width:95%;'/></td></tr>"; 
          
           $ret .= "<tr><td colspan='2'><span id='progress'></span>&nbsp;"
           . "<input id='btn_save_session' onclick='save_return_dir($psid);' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input id='btn_cancel_edit' onclick='cancel_return_dir();' type='button' value='"._CANCEL."'/>&nbsp;&nbsp;"
           //. ($psid!="new"?"<input id='btn_delete_session' onclick='delete_session();' type='button' value='"._DELETE."'/>":"")
           . "</td></tr>"
           . "</tbody></table></form>";
      return $ret;
   }
	
	function app_saveReturnDir($args) {
	include_once(XOCP_DOC_ROOT.'/config.php');
	include_once('Mail.php');
	$db=&Database::getInstance();
	$psid = $args[0];
	
	$sqlto = "SELECT p.user_id, ps.email, ps.person_nm FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id LEFT JOIN antrain_sessionss pk ON ( p.user_id = pk.id_proposeddiv ) WHERE pk.psid = $psid";    	
	 $resultto = $db->query($sqlto);
	 list($user_id_proposed,$emailto,$namato)=$db->fetchRow($resultto); 
	
	
	$sqlfrom = "SELECT p.user_id,ps.email,ps.person_nm FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id WHERE p.user_id= ".getuserid();"";  
    $resultfrom = $db->query($sqlfrom);
	list($user_id_approved,$emailfrom,$namafrom,$date_approved)=$db->fetchRow($resultfrom);
	
	$sqlcc = "SELECT email FROM antrainss_cc_email";
	$resultcc = $db->query($sqlcc);

	$to      = 		$this->emailmci($emailto);
	$subject = 	"Reminder $namato";
	
	$smtp = "JKMS01";
	$emailcc = "";
		 $nummail = $db->getRowsNum($resultcc);
		 $i = 0;
		 while(list($ccmail)=$db->fetchRow($resultcc)){
		 $emailcc .= $this->emailmci($ccmail);
		 if(++$i == $nummail )
		 {
			$emailcc .= "";
		 }
		 else
		 {
		 $emailcc .= ",";
		 }
		 
		 }

	$subject = 	"Revision $namato";
	
         $recipient = $to.",".$emailcc;
         $headers["From"]    = "hris@mcci.com";
         $headers["To"]      = $emailto;
		
		 $headers["Cc"] =  $emailcc;  
         $headers["Subject"] = "[HRIS SPECIFIC SUBJECT TRAINING RETURN] ". $subject;
         $headers["MIME-Version"] = "1.0";
         $headers["Content-type"] = "text/html; charset=iso-8859-1";
         $body = 	"<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'><html xmlns='http://www.w3.org/1999/xhtml'>
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
									<tr><td style='border-right:1px solid #bbb;'></td></tr>
									<tr><td colspan='2' style='background-color:#5666b3;'>&nbsp;</td></tr>
									<tr><td colspan='2'>

									<p>
									 Dear $namato 
									 <br/>
									 <br/>
									I have been return your Specific subject Annual Training plan year $year please <a href='/hris/index.php?XP_antrainplanss'>check</a> again.
									<br/>
									Or click this <a href='/hris/index.php?XP_antrainplanss'>link.</a>
									<br/>
									<br/>
									Thank You
									<br/>
									$namafrom
									</p>

									</td></tr>

									<tr><td colspan='2' style='background-color:#5666b3;'>&nbsp;</td></tr>


									</tbody>
									</table>


									</body>
									</html>";
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
         
         if($params['host']=="") {
            return;
         }
         
         
         // Create the mail object using the Mail::factory method
         ob_start();
         $mail_object =& Mail::factory('smtp', $params);
		$mail_object->send($recipient, $headers, $body);
         $str = "Email Sent";
         ob_end_clean();
		 //return array($str);
	
	
      $user_id = getUserID();
     
      $vars = parseForm($args[1]);
      $year = _bctrim(bcadd(0,$vars["year"]));
	  $budget = _bctrim(bcadd(0,$vars["budget"]));
	  $remark = addslashes(trim($vars["remark"]));
	  $id_created = getUserID();
	  $id_modified = getUserID();
	  $date_created = getSQLDate($vars["date_created"]);
	  $date_modified =  getSQLDate($vars["date_modified"]);
	
	  //$org_nm = addslashes(trim($vars["org_nm"]));
	  $sql = "UPDATE antrain_sessionss SET "
              . "year = '$year', budget = '$budget', remark = '$remark', id_modified = '$id_modified', date_modified = '$date_modified'"			  
              . " WHERE psid = '$psid'";
      $db->query($sql);
	  
	  	$smtp = "MKMS01";
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
         
         if($params['host']=="") {
            return;
         }
         
         //EMAIL NONAKTIF
       ob_start();
			$mail_object =& Mail::factory('smtp', $params);
        if($mail_object->send($recipient, $headers, $body))
		 {
			$str = "Email Sent";
			ob_end_clean();
		}
		else 
		{
			$str = "not sent";
		} 
	  		 
   return array($psid,$year,$remark,$str);

   }
	
	
   function app_newSession($args) {
      $db=&Database::getInstance();
		$psid = $_SESSION["pms_psid"];
	
   $sql = "SELECT p.id FROM antrain_plan_specific p WHERE p.id = ( SELECT MAX(id) FROM antrain_plan_specific  )  ORDER BY id DESC"; 
 /* 	$sql = "SELECT p.psid, p.year, p.budget, p.remark, p.status_cd, pk.org_nm, pk.org_class_id FROM antrain_sessionss p"
			  . " LEFT JOIN hris_orgs pk ON p.org_id = pk.org_id"
			  . " WHERE psid = ( SELECT MAX(psid) FROM antrain_sessionss ) ORDER BY year DESC " ;
 */
	  $result = $db->query($sql);
      list($idx)=$db->fetchRow($result);
      $id = $idx+1;
  	  $name = addslashes(trim($vars["name"]));
      $number = addslashes(trim($vars["number"]));
	  $subject = addslashes(trim($vars["subject"]));
	  $objectives = addslashes(trim($vars["objectives"]));
	  $schedule_start = addslashes(trim($vars["schedule_start"]));
	  //$schedule_start = date('d/M/Y', strtotime($schedule_start));
	  //$schedule_end = date('d/M/Y', strtotime($schedule_end));
	  $schedule_end = addslashes(trim($vars["schedule_end"]));
	  $cost = addslashes(trim($vars["cost"]));	
	  $id_job_class1 = addslashes(trim($vars["id_job_class1"]));
	  $id_job_class2 = addslashes(trim($vars["id_job_class2"]));
	  $remark = addslashes(trim($vars["remark"]));
	  $inst = addslashes(trim($vars["inst"]));
	  $hris_job_class = addslashes(trim($vars["hris_job_class"]));
	  $id_created = getUserID();
	  $date_created = getSQLDate();
	  $id_antrain_session = $_SESSION["pms_psid"];
	  $numlop = $numberloop + 1;
	  $sql = "INSERT INTO antrain_plan_specific (id,name,number,subject,objectives,schedule_start,schedule_end,cost,id_job_class1,id_job_class2,remark,id_created,date_created,inst,id_antrain_session)"
           . " VALUES('$id','$name','$number','$subject','$objectives','$schedule_start','$schedule_end','$cost','$id_job_class1','$id_job_class2','$remark','$id_created','$date_created','$inst','$id_antrain_session')";
      $db->query($sql);
	  
     $hdr   .= 	 "<table style='width:100%'><colgroup><col width='3%'/><col width='17%'/><col width='5%'/><col width='12%'/><col width='12%'/><col width='12%'/><col width='12%'/><col width='7%'/><col width='7%'/><col width='10%'/></colgroup><tbody><tr>"
				  .	"<td id='td_num_loop_${id}' style='text-align:center';>$numlop</td>"
				  . "<td id='td_name_${id}'class='xlnk' onclick='edit_session(\"$id\",this,event) center;'>$name</td>" 
				  . "<td><span id='sp_${id}' ; '>".htmlentities(stripslashes($hris_job_class))."</span></td>"
               	  . "<td id='td_subject_${id}' style='text-align: left;'>$subject</td>"
				  . "<td id='td_objectives_${id}' style='text-align: left;'>$objectives</td>"
				  . "<td id='td_schedule_${id}' style='text-align: left; font-size:10px;'>$schedule_start - $schedule_end</td>"
				  . "<td id='td_cost_${id}' style='text-align: right; padding-right: 10px;'>$.$cost</td>"
				  . "<td id='td_ins_int_${id}' style='text-align: center;'>$ins_int</td>"
				  . "<td id='td_ins_ext_${id}' style='text-align: center;'>$ins_ext</td>"
				  . "<td id='td_remark_${id}' style='text-align: center;'>$remark </td>"
				    . "</tr></tbody></table>"
                  . "</td></tr>";
				  
				  
	/* $ret .= "<tr id='tdclass_${id}'  style='margin: 0px;'>"
				  . "<td id='td_num_loop_${id}' style='text-align:center';>$numberloop</td>"
				  . "<td><span id='sp_${id}' class='xlnk' onclick='edit_session(\"$psid\",this,event); '>".htmlentities(stripslashes($hris_job_class))."</span></td>"
               	  . "<td id='td_number_${id}' style='text-align: center;'>$number</td>"
				  . "<td id='td_subject_${id}' style='text-align: left;'>$subject</td>"
				  . "<td id='td_objectives_${id}' style='text-align: left;'>$objectives</td>"
				  . "<td id='td_schedule_${id}' style='text-align: left; font-size:10px;'>$schedule_start - $schedule_end</td>"
				  . "<td id='td_cost_${id}' style='text-align: right; padding-right: 10px;'>$.$cost</td>"
				  . "<td id='td_ins_int_${id}' style='text-align: center;'>$ins_int</td>"
				  . "<td id='td_ins_ext_${id}' style='text-align: center;'>$ins_ext</td>"
				  . "<td id='td_remark_${id}' style='text-align: center;'>$remark</td>"
                  . "</tr>"; */
	  
      return array($id,$hdr);
   
   
   }
   
   function app_saveSession($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $id = $args[0];
      $vars = parseForm($args[1]);
      $number = _bctrim(bcadd(0,$vars["number"]));
	  $name = addslashes(trim($vars["name"]));
	  $subject = addslashes(trim($vars["subject"]));
	  $objectives = addslashes(trim($vars["objectives"]));
	  $schedule_start = addslashes(trim($vars["schedule_start"]));
	  $schedule_end = addslashes(trim($vars["schedule_end"]));
	  $cost = _bctrim(bcadd(0,$vars["cost"]));
	  $id_job_class1 = _bctrim(bcadd(0,$vars["id_job_class1"]));
	  $id_job_class2 = _bctrim(bcadd(0,$vars["id_job_class2"]));
	  $remark = addslashes(trim($vars["remark"]));
	  $inst = addslashes(trim($vars["inst"]));
	  $id_created = getUserID();
	  $id_modified = getUserID();
	  $date_created = getSQLDate($vars["date_created"]);
	  $date_modified =  getSQLDate($vars["date_modified"]);
	  $id_antrain_session = $_SESSION["pms_psid"];
	  $sqlprgroup1 = " SELECT job_class_id,job_class_nm FROM hris_job_class WHERE job_class_id = '$id_job_class1'";
	  $resultprgroup1 = $db->query($sqlprgroup1);
	  list($idx,$nmx)=$db->fetchRow($resultprgroup1);
	  $prgroupnm1 = $nmx;
	  
	  $sqlprgroup2 = " SELECT job_class_id,job_class_nm FROM hris_job_class WHERE job_class_id = '$id_job_class2'";
	  $resultprgroup2 = $db->query($sqlprgroup2);
	  list($idx,$nmxx)=$db->fetchRow($resultprgroup2);
	  $prgroupnm2 = $nmxx;
	  //$org_nm = addslashes(trim($vars["org_nm"]));
	  
      if($id=="new") {
	   $psid = $_SESSION["pms_psid"];
	   $sql = "SELECT p.id, p.name,p.number, p.subject, p.objectives, p.schedule_start, p.schedule_end,p.cost, p.id_job_class1, p.id_job_class2, p.remark,p.inst, ps.hris_job_class FROM antrain_plan_specific p LEFT JOIN antrain_sessionss pk ON p.id_antrain_session = pk.psid LEFT JOIN hris_peer_group ps ON p.id_job_class1 = ps.job_class_id WHERE p.id = ( SELECT MAX(id) FROM antrain_plan_specific  )  ORDER BY id DESC";  

         $result = $db->query($sql);
         list($idx)=$db->fetchRow($result);
         $id = $idx+1;
		 $id_antrain_session = $_SESSION["pms_psid"];

         $sql = "INSERT INTO antrain_plan_specific (id,id_antrain_session,name,number,subject,objectives,schedule_start,schedule_end,cost,id_job_class1,id_job_class2,remark,id_created,date_created,inst)"
              . " VALUES('$id','$id_antrain_session','$name','$number','$subject','$objectives','$schedule_start','$schedule_end','$cost','$id_job_class1','$id_job_class2','$remark','$id_created','$date_created','$inst')";
         $db->query($sql);
      } else {
	
         $sql = "UPDATE antrain_plan_specific SET "
              . "name = '$name',number = '$number', subject ='$subject', objectives = '$objectives', schedule_start = '$schedule_start', schedule_end = '$schedule_end', cost = '$cost', id_job_class1 = '$id_job_class1', id_job_class2 = '$id_job_class2', remark = '$remark',inst = '$inst', id_modified = '$id_modified', date_modified = '$date_modified' "			  
              . " WHERE id = '$id'";
         $db->query($sql);
		     $schedule_start = date('d M Y', strtotime($schedule_start));
		 $schedule_end = date('d M Y', strtotime($schedule_end));
      }
      
      return array($id,$name,$subject,$objectives,$schedule_start,$schedule_end,$cost,$id_job_class1,$id_job_class2,$remark,$inst,$prgroupnm1,$prgroupnm2);
   }
   
   function app_editSession($args) {
      $db=&Database::getInstance();
	  $psid = $_SESSION["pms_psid"];
      $id = $args[0];
      if($id=="new") {
         $date_created  = getSQLDate();
		 $id_created = getUserID();
         $generate = "";
      } else {
        $id_modified = getUserID();
		$date_modified  = getSQLDate();
    	
		$sql = "SELECT p.id,p.name, p.number, p.subject, p.objectives, p.schedule_start, p.schedule_end,p.cost, p.id_job_class1, p.id_job_class2, p.remark,p.inst, p.date_modified, ps.job_class_nm FROM antrain_plan_specific p LEFT JOIN antrain_sessionss pk ON p.id_antrain_session = pk.psid LEFT JOIN hris_job_class ps ON p.id_job_class1 = ps.job_class_id WHERE p.id = '$id' ORDER BY id ASC";  
		
		$sqlpos1 = "SELECT job_class_id,job_class_nm FROM hris_job_class";
		
	
         $result = $db->query($sql);
    	 $resultpos1 = $db->query($sqlpos1);
		 $result2 = $db->query($sql2);
	
         $schedule_start = date('d M Y', strtotime($schedule_start));
		 $schedule_end = date('d M Y', strtotime($schedule_end));
	   list($id,$name,$number,$subject,$objectives,$schedule_start,$schedule_end,$cost,$id_job_class1,$id_job_class2,$remark,$inst,$date_modified,$hris_job_class)=$db->fetchRow($result);
        $hris_job_class = htmlentities($hris_job_class,ENT_QUOTES);
		 
	
	 //  list($org_id,$org_nm,$org_class_id)=$db->fetchRow($result2);
     //    $org_nm = htmlentities($org_nm,ENT_QUOTES);		
      }
      $ret = "<form id='frm'><table class='xxfrm' style='width:100%;'><colgroup><col width='160'/><col/></colgroup><tbody>";
		
		$ret .= "<tr><td>Name</td><td><input type='text' value=\"$name\" id='inp_name' name='name' style='width:100px;'/></td></tr>" 
				. "<tr><td>Position</td><td><select id='inp_id_job_class1' name='id_job_class1' style='width:150px;'>";
			
			while(list($job_class_id1,$job_class_nm1)=$db->fetchRow($resultpos1)){
			$selected1 = "selected";
		
			 if  ($job_class_id1 == $id_job_class1)
			 {
				$selected1 = $selected1;
			 }
			 else 
			 {
				$selected1 = '';
			 }
			$ret .= "<option value=\"$job_class_id1\" $selected1>$job_class_nm1</option>" ;
			          }
			$ret .= "</select></td></tr>";
			$ret .= "<tr><td>Subject</td><td><input type='text' value=\"$subject\" id='inp_subject' name='subject' style='width:150px;'/></td></tr>" 
		   . "<tr><td>Objectives</td><td><input type='text' value=\"$objectives\" id='inp_objectives' name='objectives' style='width:150px;'/></td></tr>" 
		    . "<tr><td>Schedule start</td><td><span class='xlnk' id='spdob' onclick='_changedatetime(\"spdob\",\"hdob\",\"date\",true,false)'>".sql2ind($schedule_start,'date')."</span><input type='hidden' value=\"$schedule_start\" name='schedule_start' id='hdob' id='inp_schedule_start'/></td></tr>" 
		    . "<tr><td>Schedule end</td><td><span class='xlnk' id='spdob2' onclick='_changedatetime(\"spdob2\",\"hdob2\",\"date\",true,false)'>".sql2ind($schedule_end,'date')."</span><input type='hidden' value=\"$schedule_end\" name='schedule_end' id='hdob2' id='inp_schedule_end'/></td></tr>"
			. "<tr><td>Cost</td><td><input type='text' value=\"$cost\" id='inp_cost' name='cost' style='width:100px;'/></td></tr>";
		
		$checked1 = 'checked';
		$checked2 = 'checked';
		 if  ($inst == 'ext'){
			$checked1 = '';
			$checked2 = 'checked';
		 }
		 else 
		 {
		 	$checked2 = '';
			$checked1 = 'checked';
		 
		 }
		 $ret  .= "<tr><td>instructor</td><td><input type='radio' name='inst' value='int' $checked1>Int<br><input type='radio' name='inst' value='ext' $checked2>Ext</td></tr>";

		  $ret .= "<tr><td>Remark</td><td><input type='text' value=\"$remark\" id='inp_remark' name='remark' style='width:95%;'/></td></tr>"; 
                  
           $ret .= "<tr><td colspan='2'><span id='progress'></span>&nbsp;"
           . "<input id='btn_save_session' onclick='save_session();' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input id='btn_cancel_edit' onclick='cancel_edit();' type='button' value='"._CANCEL."'/>&nbsp;&nbsp;"
           . ($id!="new"?"<input id='btn_delete_session' onclick='delete_session();' type='button' value='"._DELETE."'/>":"")
           . "</td></tr>"
           . "</tbody></table></form>";
      return $ret;
   }
   
   function app_Delete($args) {
      $db=&Database::getInstance();
      $id = $args[0];
      $user_id = getUserID();
      $sql = "UPDATE antrain_plan_specific SET is_deleted = 'T' WHERE id = '$id'";
      $db->query($sql);
   }
   
   function app_editText($args) {
      $db=&Database::getInstance();
	  $psid = $_SESSION["pms_psid"];
      $id = $args[0];
   
    	
		$sqlnote = "SELECT note_text FROM antrain_sessionss WHERE psid = $psid ";
		 $resultnote = $db->query($sqlnote);
		 list($note_text)=$db->fetchRow($resultnote);
		 
      $ret = "<form id='frm'><table class='xxfrm' style='width:100%;'><colgroup><col width='50'/><col/></colgroup><tbody>";
		
	  $ret .= "<tr><td>Text</td><td><input type='text' value=\"$note_text\" id='inp_note_text' name='note_text' style='width:400px;'/></td></tr>";
		
                  
      $ret .= "<tr><td colspan='2'><span id='progress'></span>&nbsp;"
           . "<input id='btn_save_session' onclick='save_text();' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input id='btn_cancel_edit' onclick='cancel_edit();' type='button' value='"._CANCEL."'/>&nbsp;&nbsp;"
           . "</td></tr>"
           . "</tbody></table></form>";
      return $ret;
   }
   
   function app_saveText($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
	  $vars = parseForm($args[1]);
      $psid = $_SESSION["pms_psid"];
	  $note_text = addslashes(trim($vars["note_text"]));
	  
      $sql = "UPDATE antrain_sessionss SET note_text = '$note_text' WHERE psid = '$psid'";
      $db->query($sql);
      
      return array($psid,$note_text);
   }
   
   function emailmci($email_asli)
   {
	$part_email = explode('@',$email_asli);
	$head_email = strtoupper(substr($part_email[0],0,4));
	if($head_email == 'BKC0')
		$email_masking = $part_email[0].'@JKMS01';
	else
		$email_masking = $part_email[0].'@MKMS01';
		
		return strtoupper($email_masking);
		}
   
   
   }
   
   
     


} /// HRIS_OBJECTIVEAJAX_DEFINED
?>