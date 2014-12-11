<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_objective.php                   //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_REQUISITIONAJAX_DEFINED') ) {
   define('HRIS_REQUISITIONAJAX_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT.'/config.php');
//global $xocpConfig;
require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");
require_once(XOCP_DOC_ROOT."/modules/antrain/modconsts.php");

class _antrain_class_requisitionAjax extends AjaxListener {
   
   function _antrain_class_requisitionAjax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/antrain/class/ajax_requisition.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_Delete","app_editSession","app_saveSession",
                                             "app_setANTRAINSession","app_newSession" ,"app_propose","app_proposediv","app_approve" ,"app_approvefgm","app_returnSession", "app_saveReturn" ,"app_returnSessionDivmjr", "app_saveReturnDivmjr" ,"app_returnSessionDir", "app_saveReturnDir" , "app_returnSessionApproval", "app_saveReturnApproval", "app_reminder", "app_send_reminder","app_reminder_omsm","app_send_reminder_omsm","app_editsessionreq","app_saveSessionreq","app_newSessionreq","app_Deletereq","app_createsign","app_searchName");
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
         $str = "sent";
         ob_end_clean();
        
        //echo "$str\n";
		return $str;
}
   
   
   
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
	
	$sqlto = "SELECT p.user_id, u.email, u.person_nm, u.smtp_location FROM hris_employee_job j LEFT JOIN hris_persons u ON ( u.person_id = j.employee_id )  LEFT JOIN hris_users p ON ( u.person_id = p.person_id ) WHERE j.job_id = '$jobidto'";    	
	$resultto = $db->query($sqlto);
	list($user_id_proposed,$emailto,$namato,$smtpto)=$db->fetchRow($resultto); 
	
	$sqlfrom = "SELECT p.user_id,ps.email,ps.person_nm,ps.smtp_location FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id WHERE p.user_id= ".getuserid();"";  
    $resultfrom = $db->query($sqlfrom);
	list($user_id_approved,$emailfrom,$namafrom,$smtpfrom)=$db->fetchRow($resultfrom);
	

	//cc mail list
	$sqlcc = "SELECT email FROM antrainss_cc_email";
	$resultcc = $db->query($sqlcc);
	$smtp = "JKMS01";
	$to      = 		"$emailto";
	
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

         $recipient = $emailcc;
         $headers["From"]    = "hris@mcci.com";
//         $headers["To"]      = $namato ."-". $emailto; //$this->emailmci($emailcc);
         $headers["To"]      = $namato ."-". $emailto; //$this->emailmci($emailcc);
		 $headers["Cc"] =  $emailcc;  
         $headers["Subject"] = "[HRIS TRAINING for JKMS01] ". $subject;
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
									Dear Sir/Madam $namato
									<br/>
									Training Requisiton form has been submitted to you, please <a href='http://".$_SERVER['HTTP_HOST']."/hris/index.php?XP_antrainrequisition'>check</a> before approval.
									<br/>
									Or click this <a href='http://".$_SERVER['HTTP_HOST']."/hris/index.php?XP_antrainrequisition'>link.</a>
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
			$str = "sent";
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
			$str = "sent";
			ob_end_clean();
		}
		else 
		{
			$str = "not sent";
		}  
		
		$str = 'email sent';
		return array( $str);
   }
   
    function app_send_reminder_omsm() {
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
	$sqlcc = "SELECT email FROM antrainss_cc_email";
	$resultcc = $db->query($sqlcc);
	$smtp = "JKMS01";
	$to      = 		"$emailto";
	
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
	
	

         $recipient = $emailcc;
         $headers["From"]    = "hris@mcci.com";
         $headers["To"]      = $namato ."-". $emailto; //$this->emailmci($emailcc);
		 $headers["Cc"] =  $emailcc;  
         $headers["Subject"] = "[HRIS TRAINING] ". $subject;
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
									Dear Sir/Madam $namato
									<br/>
									Training Requisiton form has been submitted to you, please <a href='http://".$_SERVER['HTTP_HOST']."/hris/index.php?XP_antrainrequisition'>check</a> before approval.
									<br/>
									Or click this <a href='http://".$_SERVER['HTTP_HOST']."/hris/index.php?XP_antrainrequisition'>link.</a>
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
			$str = "sent";
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
			$str = "sent";
			ob_end_clean();
		}
		else 
		{
			$str = "not sent";
		} 
		return array( $str);

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
	//$to      = 		"$emailto";
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
	$to      = 		$emailcc;
	$subject = 	"Reminder $namato";

	     $recipient = $to;
         $headers["From"]    = "hris@mcci.com";
         $headers["To"]      = $emailto;
		 $headers["Cc"] =  $emailcc;  
         $headers["Subject"] = "[HRIS TRAINING PROPOSAL] ". $subject;
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
									Dear Sir/Madam $namato
									<br/>
									Training Requisiton form has been proposed to you, please <a href='http://".$_SERVER['HTTP_HOST']."/hris/index.php?XP_antrainrequisition'>check</a> before approval.
									<br/>
									Or click this <a href='http://".$_SERVER['HTTP_HOST']."/hris/index.php?XP_antrainrequisition'>link.</a>
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
         $str = "sent";
         ob_end_clean();
		
		$id_propose = getUserId();
		$is_propose = '1';
		$date_propose = getSQLDate();
		$proposed_by =  getuserFullname();
        $sql = "UPDATE antrain_sessionreq SET id_proposed = '$id_propose', is_proposed = $is_propose, date_proposed = '$date_propose' WHERE psid = '$psid'";
        $db->query($sql);
		$date_propose = date('d/M/Y', strtotime($date_propose));

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
			$str = "sent";
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

		$sqlmax = "SELECT inst FROM antrain_requisition WHERE id = (SELECT MAX( id ) FROM antrain_requisition WHERE id_antrain_session = $psid ) ORDER BY id DESC"; 
		$resultmax = $db->query($sqlmax);
		 list($remarksplan)=$db->fetchRow($resultmax);
	
		//FGM MGR
			if ($remarksplan == 'ext')
				{ 
					$jobidto = 129;
				}
			else
				{
					$jobidto = 146;
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
	$to      = 		$emailcc;
	$subject = 	"Reminder $namato";

	     $recipient = $to;
         $headers["From"]    = "hris@mcci.com";
         $headers["To"]      = $emailto;
		 $headers["Cc"] =  $emailcc;  
         $headers["Subject"] = "[HRIS TRAINING PROPOSAL] ". $subject;
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
									Dear Sir/Madam $namato
									<br/>
									Training Requisiton has been proposed to you, please <a href='http://".$_SERVER['HTTP_HOST']."/hris/index.php?XP_antrainrequisition'>check</a> before approval.
									<br/>
									Or click this <a href='http://".$_SERVER['HTTP_HOST']."/hris/index.php?XP_antrainrequisition'>link.</a>
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
         $str = "sent";
         ob_end_clean();
		
		$id_proposeddiv = getUserId();
		$is_proposeddiv = '1';
		$date_proposeddiv = getSQLDate();
		$proposeddiv_by =  getuserFullname();
        $sql = "UPDATE antrain_sessionreq SET id_proposeddiv = '$id_proposeddiv', is_proposeddiv = $is_proposeddiv, date_proposeddiv = '$date_proposeddiv' WHERE psid = '$psid'";
        $db->query($sql);
		$date_proposeddiv = date('d/M/Y', strtotime($date_proposeddiv));

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
			$str = "sent";
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
		// $sql = "SELECT id_approved, is_approved, date_approved FROM antrain_sessionreq WHERE psid= '$psid' ORDER BY year";
		// $db->query($sql);		
		
		$id_approve = getUserId();
		$is_approve = '1';
		$date_approve = getSQLDate();
		$approved_by =  "".getUserFullname()."";
        $sql = "UPDATE antrain_sessionreq SET id_approved = '$id_approve', is_approved = $is_approve, date_approved = '$date_approve' WHERE psid = '$psid'";
        $db->query($sql);
		$date_approve = date('d/M/Y', strtotime($date_approve));
		
		
		return array($psid,$id_approve,$is_approve,$date_approve,$approved_by);
   }
   
    function app_approvefgm($args) {
	
	include_once(XOCP_DOC_ROOT.'/config.php');
	include_once('Mail.php');
		$db=&Database::getInstance();
		$psid = $_SESSION["pms_psid"];
		// $sql = "SELECT id_approved, is_approved, date_approved FROM antrain_sessionreq WHERE psid= '$psid' ORDER BY year";
		// $db->query($sql);		
		
		$id_approvefgm = getUserId();
		$is_approvefgm = '1';
		$date_approvefgm = getSQLDate();
		$approvedfgm_by =  "".getUserFullname()."";
        $sql = "UPDATE antrain_sessionreq SET id_approvedfgm = '$id_approvefgm', is_approvedfgm = $is_approvefgm, date_approvedfgm = '$date_approvefgm' WHERE psid = '$psid'";
        $db->query($sql);
		$date_approvefgm = date('d/M/Y', strtotime($date_approvefgm));
		
		return array($psid,$id_approvefgm,$is_approvefgm,$date_approvefgm,$approvedfgm_by);
   }
   
      function app_createsign($args) {
	
	include_once(XOCP_DOC_ROOT.'/config.php');
	include_once('Mail.php');
		$db=&Database::getInstance();
		$psid = $_SESSION["pms_psid"];
		$id_created = getUserId();
		$date_created = getSQLDate();
		$created_by =  "".getUserFullname()."";
        $sql = "UPDATE antrain_sessionreq SET id_created = '$id_created', date_created = '$date_created' WHERE psid = '$psid'";
        $db->query($sql);
		$date_created = date('d/M/Y', strtotime($date_created));
		
		return array($psid,$id_created,$date_created,$created_by);
   }
	
	
	 function app_returnSession($args) {
      

//	 mail("prince.fachmy@gmail.com","subject","pesan");

	  $db=&Database::getInstance();
      $psid = $args[0];
      $id_modified = getUserID();
	  $date_modified  = getSQLDate();
/* 	$sql = "SELECT year,budget,remark,date_created"
             . " FROM antrain_sessionreq"
             . " WHERE psid = '$psid'"; */
	  $sql =  "SELECT p.year, p.budget, p.remark, pk.org_nm FROM antrain_sessionreq p"
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
	
	 $sqlto = "SELECT p.user_id,ps.email,ps.person_nm,pk.date_proposed FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id LEFT JOIN antrain_sessionreq pk ON p.user_id = pk.id_created WHERE pk.psid = $psid";  
     $resultto = $db->query($sqlto);
     list($user_id_proposed,$emailto,$namato,$date_proposed)=$db->fetchRow($resultto);
	
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
	$to      = 		$emailcc;
	$subject = 	"Revision $namato";
	
         $recipient = $to;
         $headers["From"]    = "hris@mcci.com";
         $headers["To"]      = $emailto;
		
		 $headers["Cc"] =  $emailcc;  
         $headers["Subject"] = "[HRIS TRAINING RETURN] ". $subject;
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
									Dear Sir/Madam $namato 
									<br/>
									<br/>
									I have been return your Requisition year $year please <a href='http://".$_SERVER['HTTP_HOST']."/hris/index.php?XP_antrainrequisition'>check</a> again.
									<br/>
									Or click this <a href='http://".$_SERVER['HTTP_HOST']."/hris/index.php?XP_antrainrequisition'>link.</a>
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
         $str = "sent";
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
	  $sql = "UPDATE antrain_sessionreq SET "
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
			$str = "sent";
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

	  $sql =  "SELECT p.year, p.budget, p.remark, pk.org_nm FROM antrain_sessionreq p"
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
	
	 $sqlto = "SELECT p.user_id,ps.email,ps.person_nm,pk.date_proposed FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id LEFT JOIN antrain_sessionreq pk ON p.user_id = pk.id_proposed WHERE pk.psid = $psid";  
     $resultto = $db->query($sqlto);
     list($user_id_proposed,$emailto,$namato,$date_proposed)=$db->fetchRow($resultto);
	
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
	$to      = 		$emailcc;
	$subject = 	"Revision $namato";
	
         $recipient = $to;
         $headers["From"]    = "hris@mcci.com";
         $headers["To"]      = $emailto;
		
		 $headers["Cc"] =  $emailcc;  
         $headers["Subject"] = "[HRIS TRAINING RETURN REQUISITION] ". $subject;
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
									 Dear Sir/Madam $namato 
									 <br/>
									 <br/>
									I have been return your Requisition year $year please <a href='http://".$_SERVER['HTTP_HOST']."/hris/index.php?XP_antrainrequisition'>check</a> again.
									<br/>
									Or click this <a href='http://".$_SERVER['HTTP_HOST']."/hris/index.php?XP_antrainrequisition'>link.</a>
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
         $str = "sent";
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
	  $sql = "UPDATE antrain_sessionreq SET "
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
			$str = "sent";
			ob_end_clean();
		}
		else 
		{
			$str = "not sent";
		}  
	  		 
   return array($psid,$year,$remark /*$str*/);

   }
	
	function app_returnSessionDir($args) {
      

//	 mail("prince.fachmy@gmail.com","subject","pesan");

	  $db=&Database::getInstance();
      $psid = $args[0];
      $id_modified = getUserID();
	  $date_modified  = getSQLDate();

	  $sql =  "SELECT p.year, p.budget, p.remark, pk.org_nm FROM antrain_sessionreq p"
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
	
	 $sqlto = "SELECT p.user_id,ps.email,ps.person_nm,pk.date_proposed FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id LEFT JOIN antrain_sessionreq pk ON p.user_id = pk.id_proposeddiv WHERE pk.psid = $psid";  
     $resultto = $db->query($sqlto);
     list($user_id_proposed,$emailto,$namato,$date_proposed)=$db->fetchRow($resultto);
	
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
	$to      = 		$emailcc;
	$subject = 	"Revision $namato";
	
         $recipient = $to;
         $headers["From"]    = "hris@mcci.com";
         $headers["To"]      = $emailto;
		
		 $headers["Cc"] =  $emailcc;  
         $headers["Subject"] = "[HRIS TRAINING RETURN REQUISITION] ". $subject;
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
									 Dear Sir/Madam $namato 
									 <br/>
									 <br/>
									I have been return your Requisition year $year please <a href='http://".$_SERVER['HTTP_HOST']."/hris/index.php?XP_antrainrequisition'>check</a> again.
									<br/>
									Or click this <a href='http://".$_SERVER['HTTP_HOST']."/hris/index.php?XP_antrainrequisition'>link.</a>
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
         $str = "sent";
         ob_end_clean();

	
	
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
	  $sql = "UPDATE antrain_sessionreq SET "
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
       /* ob_start();
			$mail_object =& Mail::factory('smtp', $params);
        if($mail_object->send($recipient, $headers, $body))
		 {
			$str = "sent";
			ob_end_clean();
		}
		else 
		{
			$str = "not sent";
		} */ 
	  		 
   return array($psid,$year,$remark /*$str*/);

   }
	
	//TRAINING REQ NEW
   function app_newSession($args) {
      $db=&Database::getInstance();
		$psid = $_SESSION["pms_psid"];
	
   $sql = "SELECT p.id FROM antrain_requisition p WHERE p.id = ( SELECT MAX(id) FROM antrain_requisition  )  ORDER BY id DESC"; 
 /* 	$sql = "SELECT p.psid, p.year, p.budget, p.remark, p.status_cd, pk.org_nm, pk.org_class_id FROM antrain_sessionreq p"
			  . " LEFT JOIN hris_orgs pk ON p.org_id = pk.org_id"
			  . " WHERE psid = ( SELECT MAX(psid) FROM antrain_sessionreq ) ORDER BY year DESC " ;
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
	  
	  $id_proposed 	  		= 0;
	  $id_proposeddiv 		= 0;
	  $id_approved 	  	    = 0;
	  $id_approvedfgm 		= 0;
	  $date_proposed 		= '0000-00-00 00:00:00'	;
	  $date_proposeddiv 	= '0000-00-00 00:00:00'	;
	  $date_approved 		= '0000-00-00 00:00:00'	;
	  $date_approvedfgm 	= '0000-00-00 00:00:00'	;
	  $id_approved = 0;
	  $id_approved = 0;
	  $id_approved = 0;
	  $id_approved = 0;
	  
	   $sqlses = "UPDATE antrain_sessionreq SET "
              . "id_proposed = '$id_proposed',id_proposeddiv = '$id_proposeddiv', id_approved ='$id_approved', id_approvedfgm = '$id_approvedfgm', date_proposed = '$date_proposed', date_proposeddiv = '$date_proposeddiv',date_approved = '$date_approved',date_approvedfgm = '$date_approvedfgm', is_proposed	 = '$is_proposed	', is_proposeddiv = '$is_proposeddiv', is_approved = '$is_approved',is_approvedfgm = '$is_approvedfgm' "			  
              . " WHERE psid = '$psid'";
       $db->query($sqlses);
	  
	  $sql = "INSERT INTO antrain_requisition (id,name,number,subject,objectives,schedule_start,schedule_end,cost,id_job_class1,id_job_class2,remark,id_created,date_created,inst,id_antrain_session)"
           . " VALUES('$id','$name','$number','$subject','$objectives','$schedule_start','$schedule_end','$cost','$id_job_class1','$id_job_class2','$remark','$id_created','$date_created','$inst','$id_antrain_session')";
      $db->query($sql);
	  
     $hdr   .= 	 "<table style='width:100%'><colgroup>
							<col width='4%'/>
							<col width='11%'/>
							<col width='10%'/>
							<col width='8%'/>
							<col width='13%'/>
							<col width='8%'/>
							<col width='13%'/>
							<col width='8%'/>
							<col width='8%'/>
							<col width='12%'/>
							<col width='6%'/>
							<col width='6%'/>
							<col width='7%'/></colgroup><tbody><tr>"
					. "<td id='td_num_loop_${id}' style='text-align:center';>$numlop</td>"
					. "<td id='td_name_${id}' $editlink '>$name</td>"
					. "<td><span id='sp_${id}' ; '>".htmlentities(stripslashes($hris_job_class))."</span></td>"
				  	. "<td><span id='orgs_${id}' ; '>$org_abbr / $org_abbr_sec </span></td>"
					. "<td id='td_subject_${id}' style='text-align: left;'>$subject</td>"
					. "<td id='td_schedule_${id}' style='text-align: center; font-size:10px;'>$schedule_start - $schedule_end</td>"
					. "<td id='td_place_${id}' style='text-align: center;' >$place</td>"
					. "<td id='td_cost_${id}' style='text-align: right; padding-right: 10px;'>$. $cost</td>"
					. "<td id='td_institution_${id}' style='text-align: center;'>$institution</td>"
					. "<td id='td_ins_int_${id}' style='text-align: center;'>$ins_int</td>"
					. "<td id='td_ins_ext_${id}' style='text-align: center;'>$ins_ext</td>"
					. "<td id='td_attach_${id}' style='text-align: center;'>$ins_ext</td>"
				    . "</tr></tbody></table>"
                  . "</td></tr>";
				  

      return array($id,$hdr);
   
   
   }
   
   //SESSION REQ NEW
   function app_newSessionreq($args) {
      $db=&Database::getInstance();
      // $sql = "SELECT MAX(psid) FROM antrain_sessionreq ORDER BY year DESC " ;
	  $sql = "SELECT p.psid, p.year, p.budget, p.remark, p.status_cd, p.org_id, pk.org_nm, pk.org_class_id FROM antrain_sessionreq p"
			  . " LEFT JOIN hris_orgs pk ON p.org_id = pk.org_id"
			  . " WHERE psid = ( SELECT MAX(psid) FROM antrain_sessionreq ) ORDER BY year DESC " ;
     $result = $db->query($sql);
      list($psidx,$year,$budget,$remark,$status_cd,$org_id)=$db->fetchRow($result);
      $psid = $psidx+1;
      $year = date("Y");
	  $budget = addslashes(trim($vars["budget"]));
	  $remark = addslashes(trim($vars["remark"]));	  
	  $org_id = addslashes(trim($vars["org_id"]));
	  //$id_created = getUserID();
	  $date_created = getSQLDate();
	  $sql = "INSERT INTO antrain_sessionreq (psid,year,budget,remark,date_created,org_id)"
           . " VALUES('$psid','$year','$budget','$remark','$date_created','$org_id')";
      $db->query($sql);
	
     $hdr = "<table><colgroup><col width='60'/><col/></colgroup><tbody><tr>"
			  . "<td width=70><span id='sp_${psid}' class='xlnk' onclick='edit_session(\"$psid\",this,event);' >$year</span></td>"
			  . "<td id='td_org_nm_${psid}' width=125 style='text-align: left;'>$org_nm</td>"
			  . "<td id='td_remark_${psid}' width=200 style='text-align: left;'>$remark</td>"
			  . "</tr></tbody></table>";
      return array($psid,$hdr);
   }
   
   //TRAINING REQ SAVE
   function app_saveSession($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $id = $args[0];
      $vars = parseForm($args[1]);
      $number = _bctrim(bcadd(0,$vars["number"]));
      $employee_id = _bctrim(bcadd(0,$vars["employee_id"]));
	  //$name = addslashes(trim($vars["name"]));
	  $subject = addslashes(trim($vars["subject"]));
	  $competency_id = addslashes(trim($vars["inp_competency"]));
	  //$objectives = addslashes(trim($vars["objectives"]));
	  $schedule_start = addslashes(trim($vars["schedule_start"]));
	  $schedule_end = addslashes(trim($vars["schedule_end"]));
	  $place = 	addslashes(trim($vars["place"]));
	  $institution = addslashes(trim($vars["institution"]));
	  $cost = _bctrim(bcadd(0,$vars["cost"]));
	  $usd = _bctrim(bcadd(0,$vars["usd"]));
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

  	 $sqlx = "SELECT person_nm FROM hris_persons WHERE person_id = '$employee_id'";
	 $resultx = $db->query($sqlx);

	 list($person_nmx) = $db->fetchRow($resultx);
	 $name = $person_nmx;

	  
      if($id=="new") {
	   $psid = $_SESSION["pms_psid"];
	   $sql = "SELECT p.id, p.employee_id, p.name, p.number, p.subject,p.competency_id , p.schedule_start, p.schedule_end,p.place,p.cost,p.usd, p.institution,p.id_job_class1, p.id_job_class2, p.remark,p.inst, ps.hris_job_class FROM antrain_requisition p LEFT JOIN antrain_sessionreq pk ON p.id_antrain_session = pk.psid LEFT JOIN hris_peer_group ps ON p.id_job_class1 = ps.job_class_id WHERE p.id = ( SELECT MAX(id) FROM antrain_requisition  )  ORDER BY id DESC";  

         $result = $db->query($sql);
         list($idx)=$db->fetchRow($result);
         $id = $idx+1;
		 $id_antrain_session = $_SESSION["pms_psid"]; 


         $sql = "INSERT INTO antrain_requisition (id,id_antrain_session,employee_id,name,number,subject,competency_id,schedule_start,schedule_end,place,cost,usd,institution,id_job_class1,id_job_class2,remark,id_created,date_created,inst)"
              . " VALUES('$id','$id_antrain_session',$employee_id,'$name','$number','$subject','$schedule_start','$schedule_end','$place','$cost','$usd','$id_job_class1','$id_job_class2','$remark','$id_created','$date_created','$inst')";
         $db->query($sql);
      } else {
	
         $sql = "UPDATE antrain_requisition SET "
              . "employee_id = '$employee_id', name = '$name',number = '$number', subject ='$subject', competency_id = '$competency_id', schedule_start = '$schedule_start', schedule_end = '$schedule_end', place = '$place',cost = '$cost',usd = '$usd',institution = '$institution', id_job_class1 = '$id_job_class1', id_job_class2 = '$id_job_class2', remark = '$remark',inst = '$inst', id_modified = '$id_modified', date_modified = '$date_modified' "			  
              . " WHERE id = '$id'";
         $db->query($sql);
		 
		 
	     $schedule_start = date('d M Y', strtotime($schedule_start));
		 $schedule_end = date('d M Y', strtotime($schedule_end));
		 
      }
      
	  $sqlfile = "SELECT file_nm FROM antrain_requisition WHERE id = '$id'";
	  $resultfile= $db->query($sqlfile);
	 list($file_nm)=$db->fetchRow($resultfile);
	 
      return array($id,$name,$subject,$schedule_start,$schedule_end,$place,$cost,$institution,$id_job_class1,$id_job_class2,$remark,$inst,$prgroupnm1,$prgroupnm2,$file_nm,$competency_id);
   }
   
   //SESSION REQ SAVE
    function app_saveSessionreq($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $psid = $args[0];
      $vars = parseForm($args[1]);
      $year = _bctrim(bcadd(0,$vars["year"]));
	  $budget = _bctrim(bcadd(0,$vars["budget"]));
	  $org_id = _bctrim(bcadd(0,$vars["org_id"]));
	  $org_id_sec = _bctrim(bcadd(0,$vars["org_id_sec"]));
	  $remark = addslashes(trim($vars["remark"]));
	  $id_created = getUserID();
	  $id_modified = getUserID();
	  $date_created = getSQLDate($vars["date_created"]);
	  $date_modified =  getSQLDate($vars["date_modified"]);
	
	  //$org_nm = addslashes(trim($vars["org_nm"]));
	  
      if($psid=="new") {
 
		$sql = "SELECT p.psid, p.year, p.budget, p.remark, p.status_cd, p.date_created, p.date_modified, pk.org_nm, pk.org_class_id FROM antrain_sessionreq p"
			  . " LEFT JOIN hris_orgs pk ON p.org_id = pk.org_id"
			  . " WHERE psid = ( SELECT MAX(psid) FROM antrain_sessionreq ) ORDER BY year DESC " ;
	         
	     //$sql =  "SELECT MAX(psid) FROM antrain_sessionreq ORDER BY year DESC " ;
         $result = $db->query($sql);
         list($psidx)=$db->fetchRow($result);
         $psid = $psidx+1;
         // $user_id = getUserID();
		 // $date_created = getSQLDate();
		// $org_nm = $org_nmx;
         $sql = "INSERT INTO antrain_sessionreq (psid,year,budget,remark,id_created,date_created)"
              . " VALUES('$psid','$year','$budget','$remark','$id_created','$date_created')";
         $db->query($sql);
      } else {
         $sql = "UPDATE antrain_sessionreq SET "
              . "year = '$year', budget = '$budget', org_id = '$org_id',org_id_sec = '$org_id_sec' , remark = '$remark', id_modified = '$id_modified', date_modified = '$date_modified'"			  
              . " WHERE psid = '$psid'";
         $db->query($sql);
		 
      $sqlnm = "SELECT org_nm FROM hris_orgs WHERE org_id= '$org_id'";
	  $resultnm = $db->query($sqlnm);
	  list($org_nm)=$db->fetchRow($resultnm);
	  
	  $sqlnmsec = "SELECT org_nm FROM hris_orgs WHERE org_id= '$org_id_sec'";
	  $resultnmsec = $db->query($sqlnmsec);
	  list($org_nm_sec)=$db->fetchRow($resultnmsec);
	  
	  }
      
	   return array($psid,$year,$org_nm,$remark,$org_nm_sec);
   }
   
   
   //TRAINING REQ EDIT
   function app_editSession($args) {
      $db=&Database::getInstance();
	  $psid = $_SESSION["pms_psid"];
      $id = $args[0];
	  $_SESSION["id_temp"] = $id;
      if($id=="new") {
         $date_created  = getSQLDate();
		 $id_created = getUserID();
         $generate = "";
      } else {
        $id_modified = getUserID();
		$date_modified  = getSQLDate();
    	
		$sql = "SELECT p.id,p.employee_id, p.name, p.number, p.subject, p.competency_id, p.objectives, p.schedule_start, p.schedule_end,p.place,p.cost,p.usd, p.institution,p.id_job_class1, p.id_job_class2, p.remark,p.inst, p.date_modified, ps.job_class_nm,p.file_nm FROM antrain_requisition p LEFT JOIN antrain_sessionreq pk ON p.id_antrain_session = pk.psid LEFT JOIN hris_job_class ps ON p.id_job_class1 = ps.job_class_id WHERE p.id = '$id' ORDER BY id ASC";  
		
		$sqlpos1 = "SELECT job_class_id,job_class_nm FROM hris_job_class";

		$sqlcompt = "SELECT competency_id,competency_nm FROM hris_competency";
		$resultcompt = $db->query($sqlcompt);
	
	/* 	$sql =  "SELECT p.year, p.budget, p.remark, pk.org_nm FROM antrain_sessionreq p"
			  . " LEFT JOIN hris_orgs pk ON p.org_id = pk.org_id"
			  . " WHERE psid = '$psid' ORDER BY year DESC " ;
	 */
         $result = $db->query($sql);
    	 $resultpos1 = $db->query($sqlpos1);
		 $result2 = $db->query($sql2);
	
         $schedule_start = date('d M Y', strtotime($schedule_start));
		 $schedule_end = date('d M Y', strtotime($schedule_end));
	   list($id,$employee_id,$name,$number,$subject,$competency_id,$objectives,$schedule_start,$schedule_end,$place,$cost,$usd,$institution,$id_job_class1,$id_job_class2,$remark,$inst,$date_modified,$hris_job_class,$file_nm)=$db->fetchRow($result);
        $hris_job_class = htmlentities($hris_job_class,ENT_QUOTES);

	
	 //  list($org_id,$org_nm,$org_class_id)=$db->fetchRow($result2);
     //    $org_nm = htmlentities($org_nm,ENT_QUOTES);
     	$sqlx = "SELECT person_id,person_nm FROM hris_persons WHERE status_cd = 'normal' ORDER BY hris_persons.person_nm ASC";
		$resultx = $db->query($sqlx);		
      }

      $disabled = "disabled";

      $ret = "<form id='frm' enctype='multipart/form-data'><table class='xxfrm' style='width:100%;'><colgroup><col width='160'/><col/></colgroup><tbody>";
      	
			$ret .= "<tr><td>Name</td>"
				  . "<td><select name='employee_id' id='inp_employee_id' $disabled>";
				  while(list($person_idx,$person_nmx)=$db->fetchRow($resultx)) {
					if($person_idx == $employee_id)
						{
							$selected_1 = ' selected ';
						}
					else
						{
							$selected_1 = '';
						}

					 $ret .= "<option value='$person_idx' $selected_1>$person_nmx</option>";
				  }
			$ret .= "</select></td></tr>";
				//. "<tr><td>Name</td><td><input type='text' value=\"$name\" id='inp_name' name='name' style='width:100px;'/></td></tr>" 
			$ret .= "<tr><td>Position</td><td><select id='inp_id_job_class1' name='id_job_class1' style='width:150px;' $disabled>";
			
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
			 
			 $sqldiv = "SELECT pk.org_nm, pk.org_abbr, pk.org_class_id FROM antrain_sessionreq p LEFT JOIN hris_orgs pk ON p.org_id = pk.org_id WHERE psid = ('$psid') ORDER BY year DESC " ;
	  
			  $resultdiv = $db->query($sqldiv);
			  list($org_nm,$org_abbr)=$db->fetchRow($resultdiv);
	  
			  $sqlsecnm = "SELECT c.org_nm, c.org_abbr,c.org_class_id FROM antrain_sessionreq a LEFT JOIN antrain_budget b ON b.id = a.id_hris_budget LEFT JOIN hris_orgs c ON c.org_id = b.org_id WHERE a.psid = '$psid'" ;
	  
		      $resultsecnm = $db->query($sqlsecnm);
		      list($org_nm_sec,$org_abbr_sec)=$db->fetchRow($resultsecnm);
			 
			$ret .= "<option value=\"$job_class_id1\" $selected1>$job_class_nm1</option>" ;
			 }
			$ret .= "</select></td></tr>";
			$ret .= "<tr>
							<td>Section</td><td><input type='text name='org_nm_sec' value='$org_nm_sec' disabled></td>
						</tr>
						<tr>
							<td>Subject</td><td><input type='text' value=\"$subject\" id='inp_subject' name='subject' style='width:150px;' $disabled /></td>
						</tr>
						";

			$ret .= "<tr><td>Competency</td><td>"
				 . "<select id='inp_competency' name='inp_competency' $disabled>";

			while (list($competency_idx,$competency_nmx) = $db->fetchRow($resultcompt)) {
			$selected1 = "selected";
			 if  ($competency_idx == $competency_id)
			 {
				$selected1 = $selected1;
			 }
			 else 
			 {
				$selected1 = '';
			 }

				$ret .= "<option value=\"$competency_idx\" $selected1>$competency_nmx</option>";	
			}
				
			$ret .= "</select></td></tr>";

			$ret .= "<tr>
							<td>Schedule start</td><td><span class='xlnk' id='spdob' onclick='_changedatetime(\"spdob\",\"hdob\",\"date\",true,false)'>".sql2ind($schedule_start,'date')."</span><input type='hidden' value=\"$schedule_start\" name='schedule_start' id='hdob' id='inp_schedule_start'/></td>
						</tr>" 
					  . "<tr>
							<td>Schedule end</td><td><span class='xlnk' id='spdob2' onclick='_changedatetime(\"spdob2\",\"hdob2\",\"date\",true,false)'>".sql2ind($schedule_end,'date')."</span><input type='hidden' value=\"$schedule_end\" name='schedule_end' id='hdob2' id='inp_schedule_end'/></td>
					   </tr>"
					  . "<tr>
							<td>Place</td><td><input type='text' value=\"$place\" id='inp_place' name='place' style='width:100px;' $disabled /></td>
						</tr>"
					  . "<tr>
							<td>Cost (IDR)</td><td><input type='text' value=\"$cost\" id='inp_cost' name='cost' style='width:100px;' $disabled /></td>
					    </tr>"
					  . "<tr>
							<td>Cost (USD)</td><td><input type='text' value=\"$usd\" id='inp_usd' name='usd' style='width:100px;' $disabled /></td>
					    </tr>"
				      . "<tr>
						 	<td>Institution</td><td><input type='text' value=\"$Institution\" id='inp_institution' name='institution' style='width:100px;' $disabled /></td>
					    </tr>";
		
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
		 $ret  .= "<tr><td>Remarks</td><td><input type='radio' name='inst' value='int' $checked1 $disabled>Planned<br><input type='radio' name='inst' value='ext' $checked2 $disabled>Unplanned</td></tr>";
		 
		 $ret  .= "	<tr>
						<td>Attach File $id</td>
						<td>
							<form action='http://".$_SERVER['HTTP_HOST']."".XOCP_SERVER_SUBDIR."/uploadfile.php?id={$_GET['id']}' method='post'
							enctype='multipart/form-data'><input type='button' value='Upload File' onclick='uplfile($id);'/>
							</form>$file_nm
						</td>
					</tr>"; 
		 
/* 		 $ret  .= "<tr><td>Attach File</td><td><input type='hidden' name='MAX_FILE_SIZE' value='100000000'>
					<input type='file' id='xfile' name='xfile'></td></tr>"; */
		 
		 /*$ret  .= "	<input type='hidden' name='MAX_FILE_SIZE' value='100000000'>
					<input type='file' id='xfile' name='xfile'>
					<br><br>
					<input type='button' value='Upload' id='goupload' name='goupload' onclick='do_upload(&quot;iform&quot;,this,event);'>
					&nbsp;<input type='button' value='Cancel' onclick='cancel_upload();'>"; */

		 $ret .= "<tr><td colspan='2'><span id='progress'></span>&nbsp;"
           . "<input id='btn_save_session' onclick='save_session();' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input id='btn_cancel_edit' onclick='cancel_edit();' type='button' value='"._CANCEL."'/>&nbsp;&nbsp;"
           . ($id!="new"?"<input id='btn_delete_session' onclick='delete_session();' type='button' value='"._DELETE."'/>":"")
           . "</td></tr>"
           . "</tbody></table></form>";


      return $ret;
   }
   
   //REQ SESSION EDIT
   function app_editsessionreq($args) {
      $db=&Database::getInstance();
      $psid = $args[0];
      if($psid=="new") {
         $date_created  = getSQLDate();
		 $id_created = getUserID();
         $generate = "";
      } else {
        $id_modified = getUserID();
		$date_modified  = getSQLDate();
/* 	$sql = "SELECT year,budget,remark,date_created"
             . " FROM antrain_sessionreq"
             . " WHERE psid = '$psid'"; */
		  $sql =  "SELECT p.year, p.budget, p.remark,p.org_id, pk.org_nm FROM antrain_sessionreq p"
			  . " LEFT JOIN hris_orgs pk ON p.org_id = pk.org_id"
			  . " WHERE psid = '$psid' ORDER BY year DESC " ;
	
         $result = $db->query($sql);
    	 
       
	   list($year,$budget,$remark,$org_id,$org_nm)=$db->fetchRow($result);
        $year = htmlentities($year,ENT_QUOTES);
		
	$sqldd = "SELECT org_id, org_nm FROM hris_orgs WHERE org_class_id =  '3'";
	$resultdd = $db->query($sqldd);
	
		
	$sqlds = "SELECT org_id, org_nm FROM hris_orgs WHERE org_class_id =  '4'";
	$resultds = $db->query($sqlds);
		
	 //  list($org_id,$org_nm,$org_class_id)=$db->fetchRow($result2);
     //    $org_nm = htmlentities($org_nm,ENT_QUOTES);		
      }
      $ret = "<form id='frm'><table class='xxfrm' style='width:100%;'><colgroup><col width='160'/><col/></colgroup><tbody>"
         
           . "<tr><td>Year</td><td><input type='text' value=\"$year\" id='inp_year' name='year' style='width:50px;'/></td></tr>" 
		   . "<tr><td>Budget</td><td><input type='text' value=\"$budget\" id='inp_budget' name='budget' style='width:50px;'/></td></tr>" 
		   . "<tr><td>Division</td><td><select id='inp_org_id' name='org_id'>";
	
	while(list($org_id,$org_nm)=$db->fetchRow($resultdd)){
		$ret .= "<option value=\"$org_id\">$org_nm</option>";
	
	}
		   
	
		$ret .= "</select></td></tr>" 
	
		. "<tr><td>Section</td><td><select id='inp_org_id_sec' name='org_id_sec'>";
	
	while(list($org_id_sec,$org_nm_sec)=$db->fetchRow($resultds)){
		$ret .= "<option value=\"$org_id_sec\">$org_nm_sec</option>";
	
	}
		   
	
		$ret .= "</select></td></tr>" 
		   . "<tr><td>Remark</td><td><input type='text' value=\"$remark\" id='inp_remark' name='remark' style='width:95%;'/></td></tr>"; 
          
		$ret .= "<tr><td colspan='2'><span id='progress'></span>&nbsp;"
           . "<input id='btn_save_session' onclick='save_sessionreq();' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input id='btn_cancel_edit' onclick='cancel_edit();' type='button' value='"._CANCEL."'/>&nbsp;&nbsp;"
           . ($psid!="new"?"<input id='btn_delete_session' onclick='delete_sessionreq();' type='button' value='"._DELETE."'/>":"")
           . "</td></tr>"
           . "</tbody></table></form>";
      return $ret;
   }
   
     
     //TRAINING REQ DELETE
   function app_Delete($args) {
      $db=&Database::getInstance();
      $id = $args[0];
      $user_id = getUserID();
      $sql = "UPDATE antrain_requisition SET is_deleted = 'T' WHERE id = '$id'";
      $db->query($sql);
   }
   
   //SESSION REQ DELETE
    function app_deletereq($args) {
      $db=&Database::getInstance();
      $psid = $args[0];
      $user_id = getUserID();
      $sql = "UPDATE antrain_sessionreq SET status_cd = 'nullified', nullified_user_id = '$user_id' WHERE psid = '$psid'";
      $db->query($sql);
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

   //SEARCH NAME
   function app_searchName($args)
   {
   	  $db=&Database::getInstance();
      $qstr = $args[0];
      
      $sql = "SELECT b.employee_id,b.employee_ext_id,a.person_nm,a.person_id,a.email"
           . " FROM ".XOCP_PREFIX."persons a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(person_id)"
           . " WHERE a.person_nm LIKE '%".addslashes($qstr)."%'"
           . " AND b.person_id IS NOT NULL"
           . " AND a.status_cd = 'normal'"
           . " GROUP BY a.person_id"
           . " ORDER BY b.employee_ext_id";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         $no = 0;
         while(list($employee_id,$employee_ext_id,$employee_nm,$person_id,$email)=$db->fetchRow($result)) {
            if($no >= 1000) break;
            $ret[$employee_id] = array($employee_nm,$person_id);
            $no++;
         }
      }
      
      if(count($ret)>0) {
         $xret = array();
         foreach($ret as $employee_id=>$v) {
            $xret[] = $v;
         }
         return $xret;
      } else {
         return "EMPTY";
      }
   }
   
   
     


} /// HRIS_OBJECTIVEAJAX_DEFINED
?>