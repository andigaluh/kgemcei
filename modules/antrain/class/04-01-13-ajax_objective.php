<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_objective.php                   //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_OBJECTIVEAJAX_DEFINED') ) {
   define('HRIS_OBJECTIVEAJAX_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT.'/config.php');
//global $xocpConfig;
require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");
require_once(XOCP_DOC_ROOT."/modules/antrain/modconsts.php");

class _antrain_class_ObjectiveAjax extends AjaxListener {
   
   function _antrain_class_ObjectiveAjax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/antrain/class/ajax_objective.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_Delete","app_editSession","app_saveSession",
                                             "app_setANTRAINSession","app_newSession" ,"app_propose","app_approve" ,"app_returnSession", "app_saveReturn" , "app_returnSessionApproval", "app_saveReturnApproval", "app_reminder", "app_send_reminder");
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
/* 	$sql = "SELECT year,budget,remark,date_created"
             . " FROM antrain_session"
             . " WHERE psid = '$psid'"; */
	/*   $sql =  "SELECT p.year, p.budget, p.remark, pk.org_nm FROM antrain_session p"
			  . " LEFT JOIN hris_orgs pk ON p.org_id = pk.org_id"
			  . " WHERE psid = '$psid' ORDER BY year DESC " ;
	
         $result = $db->query($sql);
    	 
       
	   list($year,$budget,$remark,$org_nm)=$db->fetchRow($result);
        $year = htmlentities($year,ENT_QUOTES); */
		 
	 //  list($org_id,$org_nm,$org_class_id)=$db->fetchRow($result2);
     //    $org_nm = htmlentities($org_nm,ENT_QUOTES);		
      
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
   include_once(XOCP_DOC_ROOT.'/config.php');
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
      include_once(XOCP_DOC_ROOT.'/config.php');
    include_once('Mail.php');
	$db=&Database::getInstance();
	//$psid = $args[0];
	
	 $sqlto = "SELECT p.user_id,ps.email,ps.person_nm,ps.smtp_location FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id WHERE p.user_id= '1040'";    
     $resultto = $db->query($sqlto);
     list($user_id_proposed,$emailto,$namato,$smtpto)=$db->fetchRow($resultto);
	
	$sqlfrom = "SELECT p.user_id,ps.email,ps.person_nm,ps.smtp_location FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id WHERE p.user_id= ".getuserid();"";  
    $resultfrom = $db->query($sqlfrom);
	list($user_id_approved,$emailfrom,$namafrom,$smtpfrom)=$db->fetchRow($resultfrom);
	

	$smtp = "JKMS01";
	//$to      = 		"$emailto";
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
         $headers["Subject"] = "[HRIS IDP] ". $subject;
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
									<tr><td style='border-right:1px solid #bbb;'><img src='http://146.67.1.47/hris/images/logo.gif'/></td><td><img src='http://146.67.1.47/hris/images/ocd_logo_20100618.jpg'/></td></tr>
									<tr><td colspan='2' style='background-color:#5666b3;'>&nbsp;</td></tr>
									<tr><td colspan='2'>

									<pre width='400' style='padding:15px;padding-top:0px;width:400px;'>
									Dear $namato
									<br/>
									Form Annual training plan for General subject year $year has been submited to you, please check and recheck before approval.
									<br/>
									thanks
									<br/>
									<br/>
									$namafrom
									</pre>

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
         //$mail_object->send($recipient, $headers, $body);
         $str = ob_get_contents();
         ob_end_clean();
		 return array($str);
   //return array ($str);
   }
   
   
   function app_propose($args) {
      include_once(XOCP_DOC_ROOT.'/config.php');
	include_once('Mail.php');
		$db=&Database::getInstance();
		$psid = $_SESSION["pms_psid"];
		//$psid = $args[0];
		
		//$sql = "SELECT id_proposed, is_proposed, date_proposed FROM antrain_session WHERE psid= '$psid' ORDER BY year";
		//$db->query($sql);		
	 $sqlto = "SELECT p.user_id,ps.email,ps.person_nm,ps.smtp_location FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id WHERE p.user_id= '1040'";    
     $resultto = $db->query($sqlto);
     list($user_id_proposed,$emailto,$namato,$smtpto)=$db->fetchRow($resultto);
	
	$sqlfrom = "SELECT p.user_id,ps.email,ps.person_nm,ps.smtp_location FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id WHERE p.user_id= ".getuserid();"";  
    $resultfrom = $db->query($sqlfrom);
	list($user_id_approved,$emailfrom,$namafrom,$smtpfrom)=$db->fetchRow($resultfrom);
	

	$smtp = "JKMS01";
	//$to      = 		"$emailto";
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
         $headers["Subject"] = "[HRIS REMINDER] ". $subject;
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
									<tr><td style='border-right:1px solid #bbb;'><img src='http://146.67.1.47/hris/images/logo.gif'/></td><td><img src='http://146.67.1.47/hris/images/ocd_logo_20100618.jpg'/></td></tr>
									<tr><td colspan='2' style='background-color:#5666b3;'>&nbsp;</td></tr>
									<tr><td colspan='2'>

									<pre width='400' style='padding:15px;padding-top:0px;width:400px;'>
									Dear $namato
									<br/>
									General subject Annual training plan year $year has been submited to you, please check and recheck before approved.
									<br/>
									thanks
									<br/>
									<br/>
									$namafrom 
									</pre>

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
         //$mail_object->send($recipient, $headers, $body);
         $str = ob_get_contents();
         ob_end_clean();
		
		$id_propose = getUserId();
		$is_propose = '1';
		$date_propose = getSQLDate();
		$proposed_by =  getuserFullname();
        $sql = "UPDATE antrain_session SET id_proposed = '$id_propose', is_proposed = $is_propose, date_proposed = '$date_propose' WHERE psid = '$psid'";
        $db->query($sql);
		$date_propose = date('d/M/Y', strtotime($date_propose));
		return array($psid,$id_propose,$is_propose,$date_propose,$proposed_by);
//		return array($psid,$id_propose,$is_propose,$date_propose,$proposed_by,$str);

   }
   
    function app_approve($args) {
	
		$db=&Database::getInstance();
		$psid = $_SESSION["pms_psid"];
		// $sql = "SELECT id_approved, is_approved, date_approved FROM antrain_session WHERE psid= '$psid' ORDER BY year";
		// $db->query($sql);		
		
		$id_approve = getUserId();
		$is_approve = '1';
		$date_approve = getSQLDate();
		$approved_by =  "".getUserFullname()."";
        $sql = "UPDATE antrain_session SET id_approved = '$id_approve', is_approved = $is_approve, date_approved = '$date_approve' WHERE psid = '$psid'";
        $db->query($sql);
		$date_approve = date('d/M/Y', strtotime($date_approve));
		return array($psid,$id_approve,$is_approve,$date_approve,$approved_by);
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
	include_once(XOCP_DOC_ROOT.'/config.php');
	include_once('Mail.php');
	$db=&Database::getInstance();
	$psid = $args[0];
	
	 $sqlto = "SELECT p.user_id,ps.email,ps.person_nm,pk.date_proposed FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id LEFT JOIN antrain_session pk ON p.user_id = pk.id_created WHERE pk.psid = $psid";  
     $resultto = $db->query($sqlto);
     list($user_id_proposed,$emailto,$namato,$date_proposed)=$db->fetchRow($resultto);
	
	$sqlfrom = "SELECT p.user_id,ps.email,ps.person_nm FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id WHERE p.user_id= ".getuserid();"";  
    $resultfrom = $db->query($sqlfrom);
	list($user_id_approved,$emailfrom,$namafrom,$date_approved)=$db->fetchRow($resultfrom);
	

	
	$smtp = "JKMS01";
	$to      = 		$this->emailmci($emailto);
	$subject = 	"Return $namato";
	
	/* $headers = 	"From: $emailfrom ". "\r\n" .
						"Reply-To: $emailfrom" . "\r\n" .
						"MIME-Version: 1.0" . "\r\n" .
						"Content-type: text/html; charset=iso-8859-1" . "\r\n" .
						"X-Mailer: PHP/" . phpversion(); */

         $recipient = $to;
         $headers["From"]    = "hris@mcci.com";
         $headers["To"]      = $recipient;
		 $headers["Cc"]		= 'BKC0150@JKMS01';
         $headers["Subject"] = "[HRIS RETURN] ". $subject;
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
									<tr><td style='border-right:1px solid #bbb;'><img src='http://146.67.1.47/hris/images/logo.gif'/></td><td><img src='http://146.67.1.47/hris/images/ocd_logo_20100618.jpg'/></td></tr>
									<tr><td colspan='2' style='background-color:#5666b3;'>&nbsp;</td></tr>
									<tr><td colspan='2'>

									<pre width='400' style='padding:15px;padding-top:0px;width:400px;'>
									 Dear $namato 
									 <br/>
									 <br/>
									I have been return your General subject Annual training plan year $year please check again.
									 <br/>
									thanks
									<br/>
									$namafrom
									</pre>

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
		//  $mail_object->send($recipient, $headers, $body);
         $str = ob_get_contents();
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
	  $sql = "UPDATE antrain_session SET "
              . "year = '$year', budget = '$budget', remark = '$remark', id_modified = '$id_modified', date_modified = '$date_modified'"			  
              . " WHERE psid = '$psid'";
      $db->query($sql);
	  
	  		 
      return array($psid,$year,$remark,$str);
    //  return array($psid,$year,$remark);
	 
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
	   include_once(XOCP_DOC_ROOT.'/config.php');
	include_once('Mail.php');
	
	$db=&Database::getInstance();
	$psid = $args[0];
	
	 $sqlto = "SELECT p.user_id,ps.email,ps.person_nm FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id LEFT JOIN antrain_session pk ON p.user_id = pk.id_proposed WHERE pk.psid = $psid";  
     $resultto = $db->query($sqlto);
     list($user_id_proposed,$emailto,$namato)=$db->fetchRow($resultto);
	
	$sqlfrom = "SELECT p.user_id,ps.email,ps.person_nm FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id WHERE p.user_id = ".getuserid();"";  
    $resultfrom = $db->query($sqlfrom);
	list($user_id_approved,$emailfrom,$namafrom)=$db->fetchRow($resultfrom);
	

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
         $headers["Subject"] = "[HRIS RETURN] ". $subject;
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
									<tr><td style='border-right:1px solid #bbb;'><img src='http://146.67.1.47/hris/images/logo.gif'/></td><td><img src='http://146.67.1.47/hris/images/ocd_logo_20100618.jpg'/></td></tr>
									<tr><td colspan='2' style='background-color:#5666b3;'>&nbsp;</td></tr>
									<tr><td colspan='2'>

									<pre width='400' style='padding:15px;padding-top:0px;width:400px;'>
									Dear $namato 
									 <br/>
									 <br/>
									I have been return your General subject Annual training plan year $year please check again.
									 <br/>
									thanks
									<br/>
									$namafrom
									</pre>

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
		 // return array($str);
	
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
      $number = addslashes(trim($vars["number"]));
	  $subject = addslashes(trim($vars["subject"]));
	  $objectives = addslashes(trim($vars["objectives"]));
	  $schedule_start = addslashes(trim($vars["schedule_start"]));
	  //$schedule_start = date('d/M/Y', strtotime($schedule_start));
	  //$schedule_end = date('d/M/Y', strtotime($schedule_end));
	  $schedule_end = addslashes(trim($vars["schedule_end"]));
	  $cost = addslashes(trim($vars["cost"]));	
	  $id_pr_group1 = addslashes(trim($vars["id_pr_group1"]));
	  $id_pr_group2 = addslashes(trim($vars["id_pr_group2"]));
	  $remark = addslashes(trim($vars["remark"]));
	  $inst = addslashes(trim($vars["inst"]));
	  $peer_group_nm = addslashes(trim($vars["peer_group_nm"]));
	  $id_created = getUserID();
	  $date_created = getSQLDate();
	  $id_antrain_session = $_SESSION["pms_psid"];
	  $numlop = $numberloop + 1;
	  $sql = "INSERT INTO antrain_plan_general (id,number,subject,objectives,schedule_start,schedule_end,cost,id_pr_group1,id_pr_group2,remark,id_created,date_created,inst,id_antrain_session)"
           . " VALUES('$id','$number','$subject','$objectives','$schedule_start','$schedule_end','$cost','$id_pr_group1','$id_pr_group2','$remark','$id_created','$date_created','$inst','$id_antrain_session')";
      $db->query($sql);
	  
     $hdr   .= 	 "<table style='width:100%'><colgroup><col width='3%'/><col width='17%'/><col width='5%'/><col width='12%'/><col width='12%'/><col width='12%'/><col width='12%'/><col width='7%'/><col width='7%'/><col width='10%'/></colgroup><tbody><tr>"
				  .	"<td id='td_num_loop_${id}' style='text-align:center';>$numlop</td>"
				  . "<td><span id='sp_${id}' class='xlnk' onclick='edit_session(\"$id\",this,event); '>".htmlentities(stripslashes($peer_group_nm))."</span></td>"
               	  . "<td id='td_number_${id}' style='text-align: center;'>$number</td>"
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
      $number = _bctrim(bcadd(0,$vars["number"]));
	  $subject = addslashes(trim($vars["subject"]));
	  $objectives = addslashes(trim($vars["objectives"]));
	  $schedule_start = addslashes(trim($vars["schedule_start"]));
	  $schedule_end = addslashes(trim($vars["schedule_end"]));
	  $cost = _bctrim(bcadd(0,$vars["cost"]));
	  $id_pr_group1 = _bctrim(bcadd(0,$vars["id_pr_group1"]));
	  $id_pr_group2 = _bctrim(bcadd(0,$vars["id_pr_group2"]));
	  $remark = addslashes(trim($vars["remark"]));
	  $inst = addslashes(trim($vars["inst"]));
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
	  
      if($id=="new") {
	   $psid = $_SESSION["pms_psid"];
	   $sql = "SELECT p.id, p.number, p.subject, p.objectives, p.schedule_start, p.schedule_end,p.cost, p.id_pr_group1, p.id_pr_group2, p.remark,p.inst, ps.peer_group_nm FROM antrain_plan_general p LEFT JOIN antrain_session pk ON p.id_antrain_session = pk.psid LEFT JOIN hris_peer_group ps ON p.id_pr_group1 = ps.peer_group_id WHERE p.id = ( SELECT MAX(id) FROM antrain_plan_general  )  ORDER BY id DESC";  

         $result = $db->query($sql);
         list($idx)=$db->fetchRow($result);
         $id = $idx+1;
		 $id_antrain_session = $_SESSION["pms_psid"];

         $sql = "INSERT INTO antrain_plan_general (id,id_antrain_session,number,subject,objectives,schedule_start,schedule_end,cost,id_pr_group1,id_pr_group2,remark,id_created,date_created,inst)"
              . " VALUES('$id','$id_antrain_session','$number','$subject','$objectives','$schedule_start','$schedule_end','$cost','$id_pr_group1','$id_pr_group2','$remark','$id_created','$date_created','$inst')";
         $db->query($sql);
      } else {
	
         $sql = "UPDATE antrain_plan_general SET "
              . "number = '$number', subject ='$subject', objectives = '$objectives', schedule_start = '$schedule_start', schedule_end = '$schedule_end', cost = '$cost', id_pr_group1 = '$id_pr_group1', id_pr_group2 = '$id_pr_group2', remark = '$remark',inst = '$inst', id_modified = '$id_modified', date_modified = '$date_modified' "			  
              . " WHERE id = '$id'";
         $db->query($sql);
		     $schedule_start = date('d M Y', strtotime($schedule_start));
		 $schedule_end = date('d M Y', strtotime($schedule_end));
      }
      
      return array($id,$number,$subject,$objectives,$schedule_start,$schedule_end,$cost,$id_pr_group1,$id_pr_group2,$remark,$inst,$prgroupnm1,$prgroupnm2);
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
    	
		$sql = "SELECT p.id, p.number, p.subject, p.objectives, p.schedule_start, p.schedule_end,p.cost, p.id_pr_group1, p.id_pr_group2, p.remark,p.inst, p.date_modified, ps.peer_group_nm FROM antrain_plan_general p LEFT JOIN antrain_session pk ON p.id_antrain_session = pk.psid LEFT JOIN hris_peer_group ps ON p.id_pr_group1 = ps.peer_group_id WHERE p.id = '$id' ORDER BY id ASC";  
		
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
	   list($id,$number,$subject,$objectives,$schedule_start,$schedule_end,$cost,$id_pr_group1,$id_pr_group2,$remark,$inst,$date_modified,$peer_group_nm)=$db->fetchRow($result);
        $peer_group_nm = htmlentities($peer_group_nm,ENT_QUOTES);
		 
	
	 //  list($org_id,$org_nm,$org_class_id)=$db->fetchRow($result2);
     //    $org_nm = htmlentities($org_nm,ENT_QUOTES);		
      }
      $ret = "<form id='frm'><table class='xxfrm' style='width:100%;'><colgroup><col width='160'/><col/></colgroup><tbody>"
			. "<tr><td>Participant target 1</td><td><select id='inp_id_pr_group1' name='id_pr_group1' style='width:150px;'>";
			
		
			
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

				. "<tr><td>Participant target 2</td><td><select id='inp_id_pr_group2' name='id_pr_group2' style='width:150px;'>";
			
			
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
				
/* 	 . "<tr><td>Participant target 1</td><td><input type='text' value=\"$id_pr_group1\" id='inp_id_pr_group1' name='id_pr_group1' style='width:150px;'/></td></tr>" 
		   . "<tr><td>Participant target 2</td><td><input type='text' value=\"$id_pr_group2\" id='inp_id_pr_group2' name='id_pr_group2' style='width:150px;'/></td></tr>"  ; */
  
			$ret .= "<tr><td>Number</td><td><input type='text' value=\"$number\" id='inp_number' name='number' style='width:100px;'/></td></tr>" 
		   . "<tr><td>Subject</td><td><input type='text' value=\"$subject\" id='inp_subject' name='subject' style='width:150px;'/></td></tr>" 
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
      $sql = "UPDATE antrain_plan_general SET is_deleted = 'T' WHERE id = '$id'";
      $db->query($sql);
   }
   
   function emailmci($email_asli)
   {
	$part_email = explode('@',$email_asli);
	$head_email = substr($part_emaril[0],0,4);
	if($head_email == 'BKC0')
		$email_masking = $part_email[0].'@JKMS01';
	else
		$email_masking = $part_email[0].'@MKMS01';
		
		return $email_masking;
		}
   
   
   }
   
   
     


} /// HRIS_OBJECTIVEAJAX_DEFINED
?>