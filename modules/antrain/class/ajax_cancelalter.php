<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_cancelalter.php                   //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_OBJECTIVEAJAX_DEFINED') ) {
   define('HRIS_OBJECTIVEAJAX_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT.'/config.php');
require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");
require_once(XOCP_DOC_ROOT."/modules/antrain/modconsts.php");

class _antrain_class_CancelalterAjax extends AjaxListener {
   
   function _antrain_class_CancelalterAjax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/antrain/class/ajax_cancelalter.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_Delete","app_editSession","app_saveSession",
                                             "app_setANTRAINSession","app_newSession" ,"app_propose","app_approve" ,"app_returnSession", "app_saveReturn" , "app_returnSessionApproval", "app_saveReturnApproval", "app_reminder", "app_send_reminder","app_inform","app_ackn","app_appralter","app_returnSessionrps","app_saveReturnrps","app_returnSessionhd","app_saveReturnhd","app_returnSessionsm","app_saveReturnsm");
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
   
   function mail_alert($email,$body,$subject,$smtp) {
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
         
         if($params['host']=="") {
            return;
         }
         
         
         // Create the mail object using the Mail::factory method
         ob_start();
         $mail_object =& Mail::factory('smtp', $params);
         $mail_object->send($recipient, $headers, $body);
         $str = ob_get_contents();
         ob_end_clean();
        
        //echo "$str\n";
		//return $str;
}
   
   
   
   function app_send_reminder() {
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
	
	$sqlto = "SELECT p.user_id, u.email, u.person_nm, u.smtp_location FROM hris_employee_job j LEFT JOIN hris_persons u ON ( u.person_id = j.employee_id )  LEFT JOIN hris_users p ON ( u.person_id = p.person_id ) WHERE j.job_id = '$jobidto'";    	
	$resultto = $db->query($sqlto);
	list($user_id_proposed,$emailto,$namato,$smtpto)=$db->fetchRow($resultto); 
	
	$sqlfrom = "SELECT p.user_id,ps.email,ps.person_nm,ps.smtp_location FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id WHERE p.user_id= ".getuserid();"";  
    $resultfrom = $db->query($sqlfrom);
	list($user_id_approved,$emailfrom,$namafrom,$smtpfrom)=$db->fetchRow($resultfrom);
	
	$sqlcc = "SELECT email FROM antrain_cc_email";
	$resultcc = $db->query($sqlcc);

	$smtp = "JKMS01";
//	$to      = 		"$emailto";
	$to      = 		$this->emailmci($emailto);
	$subject = 	"Revision $namato";


         $recipient = $to;
         $headers["From"]    = "hris@mcci.com";
         $headers["To"]      = $recipient;
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
		 $emailcc .= ";";
		 }
		 
		 }
		 $headers["Cc"] =  "$emailcc;";  
         $headers["Subject"] = "[HRIS TRAINING CANCELLATION/ALTERATION RETURN] ". $subject;
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
									<tr><td style='border-right:1px solid #bbb;'></td><td></td></tr>
									<tr><td colspan='2' style='background-color:#5666b3;'>&nbsp;</td></tr>
									<tr><td colspan='2'>

									<p>
									 Dear $namato 
									 <br/>
									 <br/>
									Training Cancellation/Alteration form has been submitted to you, please approve.
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
         $str = ob_get_contents();
         ob_end_clean();
		 return array($str);
   //return array ($str);
   }
   
   
   function app_inform($args) {
	include_once(XOCP_DOC_ROOT.'/config.php');
	include_once('Mail.php');
		$db=&Database::getInstance();
		$psid = $_SESSION["pms_psid"];
		
		 include_once('Mail.php');
	$db=&Database::getInstance();
	//$psid = $args[0];
	
	 $sqlto = "SELECT p.user_id,ps.email,ps.person_nm,ps.smtp_location FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id WHERE p.user_id= '1040'";    
     $resultto = $db->query($sqlto);
     list($user_id_proposed,$emailto,$namato,$smtpto)=$db->fetchRow($resultto);
	
	$sqlfrom = "SELECT p.user_id,ps.email,ps.person_nm,ps.smtp_location FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id WHERE p.user_id= ".getuserid();"";  
    $resultfrom = $db->query($sqlfrom);
	list($user_id_approved,$emailfrom,$namafrom,$smtpfrom)=$db->fetchRow($resultfrom);
	
	$sqlcc = "SELECT email FROM antrain_cc_email";
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
         $headers["Subject"] = "[HRIS GENERAL TRAINING PROPOSAL] ". $subject;
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
									<tr><td style='border-right:1px solid #bbb;'></td><td></td></tr>
									<tr><td colspan='2' style='background-color:#5666b3;'>&nbsp;</td></tr>
									<tr><td colspan='2'>
									
									<p>
									 Dear $namato 
									 <br/>
									 <br/>
										Training Cancellation/Alteration form has been submitted to you, please approve.
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
         $str = ob_get_contents();
         ob_end_clean();
		
		$id_inform = getUserId();
		$is_inform = '1';
		$date_inform = getSQLDate();
		$inform_by =  getuserFullname();
        $sql = "UPDATE antrain_session SET id_inform = '$id_inform', is_inform = $is_inform, date_inform = '$date_inform' WHERE psid = '$psid'";
        $db->query($sql);
		$date_inform = date('d/M/Y', strtotime($date_inform));

		
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
			$str = ob_get_contents();
			ob_end_clean();
		}
		else 
		{
			$str = "not sent";
		} 
              

			return array($psid,$id_inform,$is_inform,$date_inform,$inform_by,$str);
   }
   
   
   function app_ackn($args) {
		include_once(XOCP_DOC_ROOT.'/config.php');
		include_once('Mail.php');
		$db=&Database::getInstance();
		$psid = $_SESSION["pms_psid"];
		
	 include_once('Mail.php');
	$db=&Database::getInstance();
	//$psid = $args[0];
	
	/*  $sqlto = "SELECT p.user_id,ps.email,ps.person_nm,ps.smtp_location FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id WHERE p.user_id= '1312'";    
     $resultto = $db->query($sqlto);
     list($user_id_proposed,$emailto,$namato,$smtpto)=$db->fetchRow($resultto);
	 */
	 
	$sqljob = "SELECT j.job_id,j.upper_job_id FROM hris_employee_job j LEFT JOIN hris_users u ON (u.person_id = j.employee_id) WHERE u.user_id = ".getUserid()."";
	$resultjob = $db->query($sqljob);
	 list($job_id,$upper_job_id)=$db->fetchRow($resultjob);		
	 
	 if($job_id == 146 )
		{
			$jobidto = 263;
		}
	 else
		{	
			$jobidto =	$upper_job_id;
		}
	
	$sqlto = "SELECT p.user_id, u.email, u.person_nm, u.smtp_location FROM hris_employee_job j LEFT JOIN hris_persons u ON ( u.person_id = j.employee_id )  LEFT JOIN hris_users p ON ( u.person_id = p.person_id ) WHERE j.job_id = '$jobidto'";    	
	$resultto = $db->query($sqlto);
	list($user_id_proposed,$emailto,$namato,$smtpto)=$db->fetchRow($resultto); 
	 
	$sqlfrom = "SELECT p.user_id,ps.email,ps.person_nm,ps.smtp_location FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id WHERE p.user_id= ".getuserid();"";  
    $resultfrom = $db->query($sqlfrom);
	list($user_id_approved,$emailfrom,$namafrom,$smtpfrom)=$db->fetchRow($resultfrom);
	
	$sqlcc = "SELECT email FROM antrain_cc_email";
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
         $headers["Subject"] = "[HRIS GENERAL TRAINING PROPOSAL] ". $subject;
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
									<tr><td style='border-right:1px solid #bbb;'></td><td></td></tr>
									<tr><td colspan='2' style='background-color:#5666b3;'>&nbsp;</td></tr>
									<tr><td colspan='2'>

									<p>
									 Dear $namato 
									 <br/>
									 <br/>
									Training Cancellation/Alteration form has been submitted to you, please approve.
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
         $str = ob_get_contents();
         ob_end_clean();
		
	$id_ackn = getUserId();
		$is_ackn = '1';
		$date_ackn = getSQLDate();
		$ackn_by =  "".getUserFullname()."";
        $sql = "UPDATE antrain_session SET id_ackn = '$id_ackn', is_ackn = '$is_ackn', date_ackn = '$date_ackn' WHERE psid = '$psid'";
        $db->query($sql);
		$date_ackn = date('d/M/Y', strtotime($date_ackn));

		
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
			$str = ob_get_contents();
			ob_end_clean();
		}
		else 
		{
			$str = "not sent";
		} 
              
	
		return array($psid,$id_ackn,$is_ackn,$date_ackn,$ackn_by,$str);
   }
   
    function app_appralter($args) {
		$db=&Database::getInstance();
		$psid = $_SESSION["pms_psid"];
		// $sql = "SELECT id_approved, is_approved, date_approved FROM antrain_session WHERE psid= '$psid' ORDER BY year";
		// $db->query($sql);		
		$id_appralter = getUserId();
		$is_appralter = '1';
		$date_appralter = getSQLDate();
		$appralter_by =  "".getUserFullname()."";
        $sql = "UPDATE antrain_session SET id_appralter = '$id_appralter', is_appralter = '$is_appralter', date_appralter = '$date_appralter' WHERE psid = '$psid'";
        $db->query($sql);
		$date_appralter = date('d/M/Y', strtotime($date_appralter));
		return array($psid,$id_appralter,$is_appralter,$date_appralter,$appralter_by);
   }
	
	
	 function app_returnSession($args) {
      

//	 mail("prince.fachmy@gmail.com","subject","pesan");

	  $db=&Database::getInstance();
      $psid = $args[0];
      $id_modified = getUserID();
	  $date_modified  = getSQLDate();
/* 	$sql = "SELECT year,budget,remark,date_created"
             . " FROM antrain_session"
             . " WHERE psid = '$psid'"; */
	  $sql =  "SELECT p.year, p.budget, p.remark, pk.org_nm FROM antrain_session p"
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
	
	$db=&Database::getInstance();
	$psid = $args[0];

	
    include_once('Mail.php');
	$db=&Database::getInstance();
	
	$sqlto = "SELECT p.user_id,ps.email,ps.person_nm,pk.date_proposed FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id LEFT JOIN antrain_session pk ON p.user_id = pk.id_created WHERE pk.psid = $psid";  
     $resultto = $db->query($sqlto);
     list($user_id_proposed,$emailto,$namato,$date_proposed)=$db->fetchRow($resultto);
	
	$sqlfrom = "SELECT p.user_id,ps.email,ps.person_nm FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id WHERE p.user_id= ".getuserid();"";  
    $resultfrom = $db->query($sqlfrom);
	list($user_id_approved,$emailfrom,$namafrom,$date_approved)=$db->fetchRow($resultfrom);
	
	//$psid = $args[0];
	$sqlcc = "SELECT email FROM antrain_cc_email";
	$resultcc = $db->query($sqlcc);
	$smtp = "JKMS01";
	$to      = 		$this->emailmci($emailto);
	$subject = 	"Revision $namato";
	
	/* $headers = 	"From: $emailfrom ". "\r\n" .
						"Reply-To: $emailfrom" . "\r\n" .
						"MIME-Version: 1.0" . "\r\n" .
						"Content-type: text/html; charset=iso-8859-1" . "\r\n" .
						"X-Mailer: PHP/" . phpversion(); */

         $recipient = $to;
         $headers["From"]    = "hris@mcci.com";
         $headers["To"]      = $recipient;
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
		 $emailcc .= ";";
		 }
		 
		 }
		 $headers["Cc"] =  "$emailcc;";  
         $headers["Subject"] = "[HRIS TRAINING CANCELLATION/ALTERATION RETURN] ". $subject;
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
									<tr><td style='border-right:1px solid #bbb;'></td><td></td></tr>
									<tr><td colspan='2' style='background-color:#5666b3;'>&nbsp;</td></tr>
									<tr><td colspan='2'>

									<p>
									I have been returned your Cancellation/alternate form for General subject year $year please check again.
									<br/>
									Thank You
									<br/>
									<br/>
									$namato
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
         $str = ob_get_contents();
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
	  $sql = "UPDATE antrain_session SET "
              . "year = '$year', budget = '$budget', remark = '$remark', id_modified = '$id_modified', date_modified = '$date_modified'"			  
              . " WHERE psid = '$psid'";
      $db->query($sql);
	  
	  		 
      return array($psid,$year,$remark,$str);
	 
   }
   
    function app_returnSessionsm($args) {
      

//	 mail("prince.fachmy@gmail.com","subject","pesan");

	  $db=&Database::getInstance();
      $psid = $args[0];
      $id_modified = getUserID();
	  $date_modified  = getSQLDate();
/* 	$sql = "SELECT year,budget,remark,date_created"
             . " FROM antrain_session"
             . " WHERE psid = '$psid'"; */
	  $sql =  "SELECT p.year, p.budget, p.remark, pk.org_nm FROM antrain_session p"
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
           . "<input id='btn_save_session' onclick='save_returnsm($psid);' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input id='btn_cancel_edit' onclick='cancel_returnsm();' type='button' value='"._CANCEL."'/>&nbsp;&nbsp;"
           //. ($psid!="new"?"<input id='btn_delete_session' onclick='delete_session();' type='button' value='"._DELETE."'/>":"")
           . "</td></tr>"
           . "</tbody></table></form>";
      return $ret;
   }
	
	function app_saveReturnsm($args) {
	
	$db=&Database::getInstance();
	$psid = $args[0];

	
    include_once('Mail.php');
	$db=&Database::getInstance();
	
	/* $sqlto = "SELECT p.user_id,ps.email,ps.person_nm,ps.smtp_location FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id WHERE p.user_id= '1040'";  
     $resultto = $db->query($sqlto);
     list($user_id_proposed,$emailto,$namato,$smtp_to)=$db->fetchRow($resultto); */
	
	$sqlto = "SELECT p.user_id,ps.email,ps.person_nm,pk.date_proposed FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id LEFT JOIN antrain_session pk ON p.user_id = pk.id_created WHERE pk.psid = $psid";  
     $resultto = $db->query($sqlto);
     list($user_id_proposed,$emailto,$namato,$date_proposed)=$db->fetchRow($resultto);
	
	$sqlfrom = "SELECT p.user_id,ps.email,ps.person_nm FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id WHERE p.user_id= ".getuserid();"";  
    $resultfrom = $db->query($sqlfrom);
	list($user_id_approved,$emailfrom,$namafrom,$date_approved)=$db->fetchRow($resultfrom);
	
	//$psid = $args[0];
	$sqlcc = "SELECT email FROM antrain_cc_email";
	$resultcc = $db->query($sqlcc);
	$smtp = "JKMS01";
	$to      = 		$this->emailmci($emailto);
	$subject = 	"Revision $namato";
	
	/* $headers = 	"From: $emailfrom ". "\r\n" .
						"Reply-To: $emailfrom" . "\r\n" .
						"MIME-Version: 1.0" . "\r\n" .
						"Content-type: text/html; charset=iso-8859-1" . "\r\n" .
						"X-Mailer: PHP/" . phpversion(); */

         $recipient = $to;
         $headers["From"]    = "hris@mcci.com";
         $headers["To"]      = $recipient;
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
		 $emailcc .= ";";
		 }
		 
		 }
		 $headers["Cc"] =  "$emailcc;";  
         $headers["Subject"] = "[HRIS TRAINING CANCELLATION/ALTERATION RETURN] ". $subject;
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
									<tr><td style='border-right:1px solid #bbb;'></td><td></td></tr>
									<tr><td colspan='2' style='background-color:#5666b3;'>&nbsp;</td></tr>
									<tr><td colspan='2'>

									
									<p>
									i have been return your Cancellation/alternate form for General subject year $year please check again.
									<br/>
									Thank You
									<br/>
									<br/>
									$namato
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
         $str = ob_get_contents();
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
	  $sql = "UPDATE antrain_session SET "
              . "year = '$year', budget = '$budget', remark = '$remark', id_modified = '$id_modified', date_modified = '$date_modified'"			  
              . " WHERE psid = '$psid'";
      $db->query($sql);
	  
	  		 
      return array($psid,$year,$remark,$str);
	 
   }
 
   
 function app_returnSessionhd($args) {
      

//	 mail("prince.fachmy@gmail.com","subject","pesan");

	  $db=&Database::getInstance();
      $psid = $args[0];
      $id_modified = getUserID();
	  $date_modified  = getSQLDate();
/* 	$sql = "SELECT year,budget,remark,date_created"
             . " FROM antrain_session"
             . " WHERE psid = '$psid'"; */
	  $sql =  "SELECT p.year, p.budget, p.remark, pk.org_nm FROM antrain_session p"
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
           . "<input id='btn_save_session' onclick='save_returnhd($psid);' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input id='btn_cancel_edit' onclick='cancel_returnhd();' type='button' value='"._CANCEL."'/>&nbsp;&nbsp;"
           //. ($psid!="new"?"<input id='btn_delete_session' onclick='delete_session();' type='button' value='"._DELETE."'/>":"")
           . "</td></tr>"
           . "</tbody></table></form>";
      return $ret;
   }
	
	function app_saveReturnhd($args) {
	
	$db=&Database::getInstance();
	$psid = $args[0];

	
    include_once('Mail.php');
	$db=&Database::getInstance();
	
/* 	$sqlto = "SELECT p.user_id,ps.email,ps.person_nm,ps.smtp_location FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id WHERE p.user_id= '1408'";  
     $resultto = $db->query($sqlto);
     list($user_id_proposed,$emailto,$namato,$smtp_to)=$db->fetchRow($resultto); */
	 
	 $sqlto = "SELECT p.user_id,ps.email,ps.person_nm,pk.date_proposed FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id LEFT JOIN antrain_session pk ON p.user_id = pk.id_inform WHERE pk.psid = $psid";  
     $resultto = $db->query($sqlto);
     list($user_id_proposed,$emailto,$namato,$date_proposed)=$db->fetchRow($resultto);
	 
	
	$sqlfrom = "SELECT p.user_id,ps.email,ps.person_nm FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id WHERE p.user_id= ".getuserid();"";  
    $resultfrom = $db->query($sqlfrom);
	list($user_id_approved,$emailfrom,$namafrom,$date_approved)=$db->fetchRow($resultfrom);
	
	//$psid = $args[0];
	$sqlcc = "SELECT email FROM antrain_cc_email";
	$resultcc = $db->query($sqlcc);
	$smtp = "JKMS01";
	$to      = 		$this->emailmci($emailto);
	$subject = 	"Revision $namato";
	
	/* $headers = 	"From: $emailfrom ". "\r\n" .
						"Reply-To: $emailfrom" . "\r\n" .
						"MIME-Version: 1.0" . "\r\n" .
						"Content-type: text/html; charset=iso-8859-1" . "\r\n" .
						"X-Mailer: PHP/" . phpversion(); */

         $recipient = $to;
         $headers["From"]    = "hris@mcci.com";
         $headers["To"]      = $recipient;
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
		 $emailcc .= ";";
		 }
		 
		 }
		 $headers["Cc"] =  "$emailcc;";  
         $headers["Subject"] = "[HRIS TRAINING CANCELLATION/ALTERATION RETURN] ". $subject;
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
									<tr><td style='border-right:1px solid #bbb;'></td><td></td></tr>
									<tr><td colspan='2' style='background-color:#5666b3;'>&nbsp;</td></tr>
									<tr><td colspan='2'>

									<p>
									i have been return your Cancellation/alternate form for General subject year $year please check again.
									<br/>
									Thank You
									<br/>
									<br/>
									$namato
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
         $str = ob_get_contents();
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
	  $sql = "UPDATE antrain_session SET "
              . "year = '$year', budget = '$budget', remark = '$remark', id_modified = '$id_modified', date_modified = '$date_modified'"			  
              . " WHERE psid = '$psid'";
      $db->query($sql);
	  
	  		 
      return array($psid,$year,$remark,$str);
	 
   }
     
  
  function app_returnSessionrps($args) {
      

//	 mail("prince.fachmy@gmail.com","subject","pesan");

	  $db=&Database::getInstance();
      $psid = $args[0];
      $id_modified = getUserID();
	  $date_modified  = getSQLDate();
/* 	$sql = "SELECT year,budget,remark,date_created"
             . " FROM antrain_session"
             . " WHERE psid = '$psid'"; */
	  $sql =  "SELECT p.year, p.budget, p.remark, pk.org_nm FROM antrain_session p"
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
           . "<input id='btn_save_session' onclick='save_returnrps($psid);' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input id='btn_cancel_edit' onclick='cancel_returnrps();' type='button' value='"._CANCEL."'/>&nbsp;&nbsp;"
           //. ($psid!="new"?"<input id='btn_delete_session' onclick='delete_session();' type='button' value='"._DELETE."'/>":"")
           . "</td></tr>"
           . "</tbody></table></form>";
      return $ret;
   }
	
  function app_saveReturnrps($args) {
	
	$db=&Database::getInstance();
	$psid = $args[0];

	
    include_once('Mail.php');
	$db=&Database::getInstance();
	
	/* $sqlto = "SELECT p.user_id,ps.email,ps.person_nm,ps.smtp_location FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id WHERE p.user_id= '1040'";  
     $resultto = $db->query($sqlto);
     list($user_id_proposed,$emailto,$namato,$smtp_to)=$db->fetchRow($resultto); */
	 
	  $sqlto = "SELECT p.user_id,ps.email,ps.person_nm,pk.date_proposed FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id LEFT JOIN antrain_session pk ON p.user_id = pk.id_ackn WHERE pk.psid = $psid";  
     $resultto = $db->query($sqlto);
     list($user_id_proposed,$emailto,$namato,$date_proposed)=$db->fetchRow($resultto);
	 
	
	$sqlfrom = "SELECT p.user_id,ps.email,ps.person_nm FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id WHERE p.user_id= ".getuserid();"";  
    $resultfrom = $db->query($sqlfrom);
	list($user_id_approved,$emailfrom,$namafrom,$date_approved)=$db->fetchRow($resultfrom);
	
	//$psid = $args[0];
	$sqlcc = "SELECT email FROM antrain_cc_email";
	$resultcc = $db->query($sqlcc);
	$smtp = "JKMS01";
	$to      = 		$this->emailmci($emailto);
	$subject = 	"Revision $namato";
	
	/* $headers = 	"From: $emailfrom ". "\r\n" .
						"Reply-To: $emailfrom" . "\r\n" .
						"MIME-Version: 1.0" . "\r\n" .
						"Content-type: text/html; charset=iso-8859-1" . "\r\n" .
						"X-Mailer: PHP/" . phpversion(); */

         $recipient = $to;
         $headers["From"]    = "hris@mcci.com";
         $headers["To"]      = $recipient;
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
		 $emailcc .= ";";
		 }
		 
		 }
		 $headers["Cc"] =  "$emailcc;";  
         $headers["Subject"] = "[HRIS TRAINING CANCELLATION/ALTERATION RETURN] ". $subject;
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
									<tr><td style='border-right:1px solid #bbb;'></td><td></td></tr>
									<tr><td colspan='2' style='background-color:#5666b3;'>&nbsp;</td></tr>
									<tr><td colspan='2'>

									<p>
									i have been return your Cancellation/alternate form for General subject year $year please check again.
									<br/>
									Thank You
									<br/>
									<br/>
									$namato
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
         $str = ob_get_contents();
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
	  $sql = "UPDATE antrain_session SET "
              . "year = '$year', budget = '$budget', remark = '$remark', id_modified = '$id_modified', date_modified = '$date_modified'"			  
              . " WHERE psid = '$psid'";
      $db->query($sql);
	  
	  		 
      return array($psid,$year,$remark,$str);
	 
   }
   
	
	function app_returnSessionApproval($args) {
      

//	BUAT RETURN YANG APPROVAL;

	  $db=&Database::getInstance();
      $psid = $args[0];
      $id_modified = getUserID();
	  $date_modified  = getSQLDate();

	  $sql =  "SELECT p.year, p.budget, p.remark, pk.org_nm FROM antrain_session p"
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
           . "<input id='btn_save_session' onclick='save_returnapproval($psid);' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input id='btn_cancel_edit' onclick='cancel_return();' type='button' value='"._CANCEL."'/>&nbsp;&nbsp;"
           //. ($psid!="new"?"<input id='btn_delete_session' onclick='delete_session();' type='button' value='"._DELETE."'/>":"")
           . "</td></tr>"
           . "</tbody></table></form>";
      return $ret;
   }
	
	function app_saveReturnApproval($args) {
	
	$db=&Database::getInstance();
	$psid = $args[0];
	
	 $sqlto = "SELECT p.user_id,ps.email,ps.person_nm FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id LEFT JOIN antrain_session pk ON p.user_id = pk.id_proposed WHERE pk.psid = $psid";  
     $resultto = $db->query($sqlto);
     list($user_id_proposed,$emailto,$namato)=$db->fetchRow($resultto);
	
	$sqlfrom = "SELECT p.user_id,ps.email,ps.person_nm FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id WHERE p.user_id = ".getuserid();"";  
    $resultfrom = $db->query($sqlfrom);
	list($user_id_approved,$emailfrom,$namafrom)=$db->fetchRow($resultfrom);
	
	$sqlcc = "SELECT email FROM antrain_cc_email";
	$resultcc = $db->query($sqlcc);
	$smtp = "JKMS01";
	$to      = 		$this->emailmci($emailto);
	$subject = 	"Revision $namato";
	
	/* $headers = 	"From: $emailfrom ". "\r\n" .
						"Reply-To: $emailfrom" . "\r\n" .
						"MIME-Version: 1.0" . "\r\n" .
						"Content-type: text/html; charset=iso-8859-1" . "\r\n" .
						"X-Mailer: PHP/" . phpversion(); */

         $recipient = $to;
         $headers["From"]    = "hris@mcci.com";
         $headers["To"]      = $recipient;
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
		 $emailcc .= ";";
		 }
		 
		 }
		 $headers["Cc"] =  "$emailcc;";  
         $headers["Subject"] = "[HRIS TRAINING CANCELLATION/ALTERATION RETURN] ". $subject;
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
									<tr><td style='border-right:1px solid #bbb;'></td><td></td></tr>
									<tr><td colspan='2' style='background-color:#5666b3;'>&nbsp;</td></tr>
									<tr><td colspan='2'>

									<p>
									i have been return your Cancellation/alternate form for General subject year $year please check again.
									<br/>
									Thank You
									<br/>
									<br/>
									$namato
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
         $str = ob_get_contents();
         ob_end_clean();
	
	/* 
	$to      = 		$this->emailmci($emailto);
	$subject = 	"Revision $namafrom";
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
									<tr><td style='border-right:1px solid #bbb;'></td><td></td></tr>
									<tr><td colspan='2' style='background-color:#5666b3;'>&nbsp;</td></tr>
									<tr><td colspan='2'>

									<p>TESS</p>

									</td></tr>

									<tr><td colspan='2' style='background-color:#5666b3;'>&nbsp;</td></tr>


									</tbody>
									</table>


									</body>
									</html>";
	$headers = 	"From: $namafrom <$emailfrom>". "\r\n" .
						"Reply-To: $emailfrom" . "\r\n" .
						"MIME-Version: 1.0" . "\r\n" .
						"Content-type: text/html; charset=iso-8859-1" . "\r\n" .
						"X-Mailer: PHP/" . phpversion();

	mail($to, $subject, $body, $headers);  */
	
	
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
	  $sql = "UPDATE antrain_session SET "
              . "year = '$year', budget = '$budget', remark = '$remark', id_modified = '$id_modified', date_modified = '$date_modified'"			  
              . " WHERE psid = '$psid'";
      $db->query($sql);
	  
	  		 
      return array($psid,$year,$remark);
	 
}

	
   function app_newSession($args) {
      $db=&Database::getInstance();
		$psid = $_SESSION["pms_psid"];
	
   $sql = "SELECT p.id FROM antrain_plan_general p WHERE p.id = ( SELECT MAX(id) FROM antrain_plan_general  )  ORDER BY id DESC"; 
 /* 	$sql = "SELECT p.psid, p.year, p.budget, p.remark, p.status_cd, pk.org_nm, pk.org_class_id FROM antrain_session p"
			  . " LEFT JOIN hris_orgs pk ON p.org_id = pk.org_id"
			  . " WHERE psid = ( SELECT MAX(psid) FROM antrain_session ) ORDER BY year DESC " ;
 */
	  $result = $db->query($sql);
      list($idx)=$db->fetchRow($result);
      $id = $idx+1;
	  $name = addslashes(trim($vars["name"]));
      $number = addslashes(trim($vars["number"]));
	  $subject = addslashes(trim($vars["subject"]));
	  $objectives = addslashes(trim($vars["objectives"]));
	  $schedule_start = addslashes(trim($vars["schedule_start"]));
	  $schedule_end = addslashes(trim($vars["schedule_end"]));
	  $cost = addslashes(trim($vars["cost"]));	
	  $id_pr_group1 = addslashes(trim($vars["id_pr_group1"]));
	  $id_pr_group2 = addslashes(trim($vars["id_pr_group2"]));
	  $remark = addslashes(trim($vars["remark"]));
	  $inst = addslashes(trim($vars["inst"]));
	  $status_cancel = 'F';
 	  $peer_group_nm = addslashes(trim($vars["peer_group_nm"]));
	  $id_created = getUserID();
	  $date_created = getSQLDate();
	  $id_antrain_session = $_SESSION["pms_psid"];
	  $numlop = $numberloop + 1;
	  $sql = "INSERT INTO antrain_plan_general (id,name,number,subject,objectives,schedule_start,schedule_end,cost,id_pr_group1,id_pr_group2,remark,id_created,date_created,status_cancel,id_antrain_session)"
           . " VALUES('$id','$name','$number','$subject','$objectives','$schedule_start','$schedule_end','$cost','$id_pr_group1','$id_pr_group2','$remark','$id_created','$date_created','$status_cancel','$id_antrain_session')";
      $db->query($sql);
	  
	  $stat_can = $status_cancel;
			if ( $stat_can == 'T')
			{
				$stat_can = 'Cancelled';
			}
			else
			{
				$stat_can = 'Ongoing';
			}
	  
     $hdr   .= 	 "<table style='width:100%'><colgroup><col width='3%'/><col width='12%'/><col width='10%'/><col width='10%'/><col width='15%'/><col width='12%'/><col width='12%'/><col width='6%'/><col width='15%'/><col width='7%'/></colgroup></colgroup><tbody><tr>"
				  .	"<td id='td_num_loop_${id}' style='text-align:center';>$numlop</td>"
				   . "<td id='td_name_${id}' style='text-align: center;'>$name</td>"
				  . "<td><span id='sp_${id}' class='xlnk' onclick='edit_session(\"$id\",this,event); '>".htmlentities(stripslashes($peer_group_nm))."</span></td>"
               	  . "<td id='td_secdiv_${id}' style='text-align: center;'>MCCI</td>"
				  . "<td id='td_subject_${id}' style='text-align: left;'>$subject</td>"
				  . "<td id='td_schedule_${id}' style='text-align: left; font-size:10px;'>$schedule_start - $schedule_end</td>"
				  . "<td id='td_place_${id}' style='text-align: center;'>$place</td>"
				  . "<td id='td_cost_${id}' style='text-align: right; padding-right: 10px;'>$. $cost</td>"		
				  . "<td id='td_remark_${id}' style='text-align: center;'>$remark </td>"
				  . "<td id='td_statcancel_${id}' style='text-align: center;'>$stat_can</td>"
				    . "</tr></tbody></table>"
                  . "</td></tr>";
				  
				  
	/* $ret .= "<tr id='tdclass_${id}'  style='margin: 0px;'>"
				  . "<td id='td_num_loop_${id}' style='text-align:center';>$numberloop</td>"
				  . "<td><span id='sp_${id}' class='xlnk' onclick='edit_session(\"$psid\",this,event); '>".htmlentities(stripslashes($peer_group_nm))."</span></td>"
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
      $name = addslashes(trim($vars["name"]));
	  $subject = addslashes(trim($vars["subject"]));
	  $objectives = addslashes(trim($vars["objectives"]));
	  $schedule_start = addslashes(trim($vars["schedule_start"]));
	  $schedule_end = addslashes(trim($vars["schedule_end"]));
	  $cost = _bctrim(bcadd(0,$vars["cost"]));
	  $id_pr_group1 = _bctrim(bcadd(0,$vars["id_pr_group1"]));
	  $id_pr_group2 = _bctrim(bcadd(0,$vars["id_pr_group2"]));
	  $remark = addslashes(trim($vars["remark"]));
	  $status_cancel =  addslashes(trim($vars["status_cancel"]));
	  $place = addslashes(trim($vars["place"]));
	  $id_created = getUserID();
	  $id_modified = getUserID();
	  $date_created = getSQLDate($vars["date_created"]);
	  $date_modified =  getSQLDate($vars["date_modified"]);
	  $id_antrain_session = $_SESSION["pms_psid"];
	  $sqlprgroup1 = " SELECT id_antrain_peergroup,antrain_peergroup_nm FROM antrain_peer_group WHERE id_antrain_peergroup = '$id_pr_group1'";
	  $resultprgroup1 = $db->query($sqlprgroup1);
	  list($idx,$nmx)=$db->fetchRow($resultprgroup1);
	  $prgroupnm1 = $nmx;
	  
	  $sqlprgroup2 = " SELECT id_antrain_peergroup,antrain_peergroup_nm FROM antrain_peer_group WHERE id_antrain_peergroup = '$id_pr_group2'";
	  $resultprgroup2 = $db->query($sqlprgroup2);
	  list($idx,$nmxx)=$db->fetchRow($resultprgroup2);
	  $prgroupnm2 = $nmxx;
	  //$org_nm = addslashes(trim($vars["org_nm"]));
	  
//BELOM BERES!!!!! VV

 /* if ($status_cancel == 'F' && sa)
	  {
			
	  }
	  else
	  {
	  
	  }
	   */
	  
//BELOM BERES!!!!! ^^	  
	  if($id=="new") {
	   $psid = $_SESSION["pms_psid"];
	   $sql = "SELECT p.id, p.name, p.subject, p.schedule_start, p.schedule_end,p.place,p.cost, p.id_pr_group1, p.id_pr_group2, p.remark,p.status_cancel, ps.peer_group_nm FROM antrain_plan_general p LEFT JOIN antrain_session pk ON p.id_antrain_session = pk.psid LEFT JOIN hris_peer_group ps ON p.id_pr_group1 = ps.peer_group_id WHERE p.id = ( SELECT MAX(id) FROM antrain_plan_general  )  ORDER BY id DESC";  

         $result = $db->query($sql);
         list($idx)=$db->fetchRow($result);
         $id = $idx+1;
		 $id_antrain_session = $_SESSION["pms_psid"];

         $sql = "INSERT INTO antrain_plan_general (id,id_antrain_session,name,subject,schedule_start,schedule_end,place,cost,id_pr_group1,id_pr_group2,remark,id_created,date_created,status_cancel)"
              . " VALUES('$id','$id_antrain_session','$name','$subject','$schedule_start','$schedule_end','$cost','$id_pr_group1','$id_pr_group2','$remark','$id_created','$date_created',$status_cancel)";
         $db->query($sql);
      } else {
	
         $sql = "UPDATE antrain_plan_general SET "
              . "name = '$name', subject ='$subject', schedule_start = '$schedule_start', schedule_end = '$schedule_end', place = '$place', cost = '$cost', id_pr_group1 = '$id_pr_group1', id_pr_group2 = '$id_pr_group2', remark = '$remark', status_cancel = '$status_cancel', id_modified = '$id_modified', date_modified = '$date_modified' "			  
              . " WHERE id = '$id'";
         $db->query($sql);
		    
      }
       $schedule_start = date('d M Y', strtotime($schedule_start));
		 $schedule_end = date('d M Y', strtotime($schedule_end));
      return array($id,$name,$subject,$schedule_start,$schedule_end,$place,$cost,$id_pr_group1,$id_pr_group2,$remark,$status_cancel,$prgroupnm1,$prgroupnm2);
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
    	
		$sql = "SELECT p.id, p.name,p.number, p.subject, p.schedule_start, p.schedule_end,p.place,p.cost, p.id_pr_group1, p.id_pr_group2, p.remark,p.status_cancel, p.date_modified, ps.peer_group_nm FROM antrain_plan_general p LEFT JOIN antrain_session pk ON p.id_antrain_session = pk.psid LEFT JOIN hris_peer_group ps ON p.id_pr_group1 = ps.peer_group_id WHERE p.id = '$id' ORDER BY id ASC";  
		
		$sql2 = "SELECT id_antrain_peergroup,antrain_peergroup_nm FROM antrain_peer_group";
		
		$sql3 = "SELECT id_antrain_peergroup,antrain_peergroup_nm FROM antrain_peer_group";
		
	/* 	$sql =  "SELECT p.year, p.budget, p.remark, pk.org_nm FROM antrain_session p"
			  . " LEFT JOIN hris_orgs pk ON p.org_id = pk.org_id"
			  . " WHERE psid = '$psid' ORDER BY year DESC " ;
	 */
         $result = $db->query($sql);
    	 $result2 = $db->query($sql2);
		 $result3 = $db->query($sql3);
         $schedule_start = date('d M Y', strtotime($schedule_start));
		 $schedule_end = date('d M Y', strtotime($schedule_end));
	   list($id,$name,$number,$subject,$schedule_start,$schedule_end,$place,$cost,$id_pr_group1,$id_pr_group2,$remark,$status_cancel,$date_modified,$peer_group_nm)=$db->fetchRow($result);
        $peer_group_nm = htmlentities($peer_group_nm,ENT_QUOTES);
		 
	
	 //  list($org_id,$org_nm,$org_class_id)=$db->fetchRow($result2);
     //    $org_nm = htmlentities($org_nm,ENT_QUOTES);		
      }
      $ret = "<form id='frm'><table class='xxfrm' style='width:100%;'><colgroup><col width='160'/><col/></colgroup><tbody>"
			. "<tr><td>Name</td><td><input type='text' value=\"$name\" id='inp_name' name='name' style='width:150px;'/></td></tr>"
			. "<tr><td>Postion Level 1</td><td><select id='inp_id_pr_group1' name='id_pr_group1' style='width:150px;'>";
			
		
			
			while(list($id_pr_1,$antrain_peergroup_nm)=$db->fetchRow($result2)){
			$selected1 = "selected";
		
			 if  ($id_pr_1 == $id_pr_group1)
			 {
				$selected1 = $selected1;
			 }
			 else 
			 {
				$selected1 = '';
			 }
			$ret .= "<option value=\"$id_pr_1\" $selected1>$antrain_peergroup_nm</option>" ;
			          }
			$ret .= "</select></td></tr>"

				. "<tr><td>Postion Level 2</td><td><select id='inp_id_pr_group2' name='id_pr_group2' style='width:150px;'>";
			
			
			while(list($id_pr_2,$antrain_peergroup_nm)=$db->fetchRow($result3)){
				$selected2 = "selected";
		
			 if  ($id_pr_2 == $id_pr_group2)
			 {
				$selected2 = $selected2;
			 }
			 else 
			 {
				$selected2 = '';
			 }
			
			$ret .= "<option value=\"$id_pr_2\" $selected2>$antrain_peergroup_nm</option>" ;
			 
			 }
			
			$ret .= "<option value=0 > - </option>";		  
			$ret .= "</select></td></tr>"; 
			
			$ret .= "<tr><td>Subject</td><td><input type='text' value=\"$subject\" id='inp_subject' name='subject' style='width:150px;'/></td></tr>" 
			. "<tr><td>Date start</td><td><span class='xlnk' id='spdob' onclick='_changedatetime(\"spdob\",\"hdob\",\"date\",true,false)'>".sql2ind($schedule_start,'date')."</span><input type='hidden' value=\"$schedule_start\" name='schedule_start' id='hdob' id='inp_schedule_start'/></td></tr>" 
		    . "<tr><td>Date end</td><td><span class='xlnk' id='spdob2' onclick='_changedatetime(\"spdob2\",\"hdob2\",\"date\",true,false)'>".sql2ind($schedule_end,'date')."</span><input type='hidden' value=\"$schedule_end\" name='schedule_end' id='hdob2' id='inp_schedule_end'/></td></tr>"
			. "<tr><td>Place</td><td><input type='text' value=\"$place\" id='inp_place' name='place' style='width:100px;'/></td></tr>"
			. "<tr><td>Cost</td><td><input type='text' value=\"$cost\" id='inp_cost' name='cost' style='width:100px;'/></td></tr>";
			
		
		 $ret .= "<tr><td>Remark</td><td><input type='text' value=\"$remark\" id='inp_remark' name='remark' style='width:95%;'/></td></tr>"; 
		 	$checked1 = 'checked';
			$checked2 = 'checked';
			if  ($status_cancel == 'F' || $status_cancel == 'A')
			{
				$checked1 = '';
				$checked2 = 'checked';
			}
			else 
			{
				$checked2 = '';
				$checked1 = 'checked';
		 	}
		 $ret .= "<tr><td>Status Cancellation</td><td><input type='radio' name='status_cancel' value='T' $checked1>Yes<br><input type='radio' name='status_cancel' value='A' $checked2>No</td></tr>";
                  
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
      $sql = "UPDATE antrain_plan_general SET is_deleted = 'T' WHERE id = '$id'";
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

} /// HRIS_OBJECTIVEAJAX_DEFINED
?>