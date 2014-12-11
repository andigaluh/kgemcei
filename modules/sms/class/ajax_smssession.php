<?php
//--------------------------------------------------------------------//
// Filename : modules/sms/class/ajax_smssession.php                  //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2013-10-10                                              //                                                     //
// License  : GPL                                                     //
//--------------------------------------------------------------------//
/* error_reporting(E_ALL);
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
 */
if ( !defined('HRIS_SMSSESSIONAJAX_DEFINED') ) {
   define('HRIS_SMSSESSIONAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");
require_once(XOCP_DOC_ROOT."/modules/sms/modconsts.php");


class _sms_class_SMSSessionAjax extends AjaxListener {
   
   
   function __construct($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/sms/class/ajax_smssession.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_Delete","app_editSession","app_saveSession",
                                             "app_setSMSSession","app_newSession","app_editTarget","app_saveTarget","app_saveJAMTargetText","app_propose","app_approve","app_approve2","app_deletePerspective","app_addObj","app_saveObjective","app_returns","app_saveReturn");
   }
   
   function app_setSMSSession($args) {
      $_SESSION["sms_id"] = $args[0];
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
   
   
   function app_newSession($args) {
      $db=&Database::getInstance();
      $sql = "SELECT MAX(id) FROM sms_session";
      $result = $db->query($sql);
      list($idx)=$db->fetchRow($result);
      $id = $idx+1;
      $create_user_id = getUserID();
      $periode_session = date("Y");
      $create_date = getSQLDate();
      $sql = "INSERT INTO sms_session (id,periode_session,title_session,vision,create_user_id,create_date)"
           . " VALUES('$id','$periode_session','$title_session','$vision','$create_user_id','$create_date')";
      $db->query($sql);
      
      $hdr = "<table><colgroup><col width='60'/><col/></colgroup><tbody><tr>"
           . "<td id='td_periode_${id}'>$periode_session</td>"
           . "<td><span id='sp_${id}' class='xlnk' onclick='edit_session(\"$id\",this,event);'>"._EMPTY."</span></td>"
           . "</tr></tbody></table>";
      return array($id,$hdr);
   }
   
   function app_saveSession($args) {
      $db=&Database::getInstance();
      $create_user_id = getUserID();
      $id = $args[0];
      $vars = parseForm($args[1]);
      $title_session = addslashes(trim($vars["title_session"]));
      $vision = addslashes(trim($vars["vision"]));
      $periode_session = _bctrim(bcadd(0,$vars["periode_session"]));
     
	 if($title_session=="") {
         $title_session = "noname";
      }
      if($id=="new") {
         $sql = "SELECT MAX(id) FROM sms_session";
         $result = $db->query($sql);
         list($idx)=$db->fetchRow($result);
         $id = $idx+1;
         $create_user_id = getUserID();
         $sql = "INSERT INTO sms_session (id,periode_session,title_session,vision,create_user_id)"
              . " VALUES('$id','$periode_session','$title_session','$vision','$create_user_id')";
         $db->query($sql);
      } else {
         $sql = "UPDATE sms_session SET title_session = '$title_session',vision = '$vision', "
              . "periode_session = '$periode_session' "
              . " WHERE id = '$id'";
         $db->query($sql);
      }
      
      return array($id,$title_session,$periode_session);
   }
   
   function app_editSession($args) {
      $db=&Database::getInstance();
      $id = $args[0];
      if($id=="new") {
         $generate = "";
      } else {
         
         $sql = "SELECT title_session,periode_session,vision"
              . " FROM sms_session"
              . " WHERE id = '$id'";
         $result = $db->query($sql);
         
         list($title_session,$periode_session,$vision)=$db->fetchRow($result);
         $title_session = htmlentities($title_session,ENT_QUOTES);
      }
      $ret = "<form id='frm'><table class='xxfrm' style='width:100%;'><colgroup><col width='160'/><col/></colgroup><tbody>"
           . "<tr><td>Periode Session</td><td><input type='text' value=\"$periode_session\" id='inp_session_periode' name='periode_session' style='width:60%;'/></td></tr>"
           . "<tr><td>Title Session</td><td><input type='text' value=\"$title_session\" id='inp_session_title' name='title_session' style='width:60%;'/></td></tr>"
           . "<tr><td>Vision</td><td><input type='text' value=\"$vision\" id='inp_session_vision' name='vision' style='width:60%;'/></td></tr>"
           
           . "<tr><td colspan='2'><span id='progress'></span>&nbsp;"
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
      $sql = "DELETE FROM sms_session WHERE id = '$id'";
      $db->query($sql);
   }
  
 #FUNCTION MATRIX
 
  function app_saveJAMTargetText($args) {
		  $db=&Database::getInstance();
		  $user_id = getUserID();
		  $val = addslashes($args[0]);
		  $ids = $args[1];
		  $objid = $args[2];
		  $psid= $args[3];
		  $no = $args[4];
		  
		  	$table = null;
		  	#strategic
			if ($no == 0) { $col_val = 'ap_lower_perform'; $table='sms_jam_strategic';	}
			elseif ($no == 1) { $col_val = 'ap_need_improvement'; $table='sms_jam_strategic';}
			elseif ($no == 2) { $col_val = 'ap_target'; $table='sms_jam_strategic'; }
			elseif ($no == 3) { $col_val = 'ap_req'; $table='sms_jam_strategic'; }
			elseif ($no == 4) { $col_val = 'ap_far_req'; $table='sms_jam_strategic'; }
			#value
			elseif ($no == 5) { $col_val = 'value'; $table='sms_jam_strategic'; }
		
			#functional
			elseif ($no == 6) { $col_val = 'ap_lower_perform'; $table='sms_jam_functional'; }
			elseif ($no == 7) { $col_val = 'ap_need_improvement'; $table='sms_jam_functional'; }
			elseif ($no == 8) { $col_val = 'ap_target'; $table='sms_jam_functional'; }
			elseif ($no == 9) { $col_val = 'ap_req'; $table='sms_jam_functional'; }
			elseif ($no == 10) { $col_val = 'ap_far_req'; $table='sms_jam_functional'; }
			#value
			elseif ($no == 11) { $col_val = 'value'; $table='sms_jam_functional'; }
			
     
		$sqljam = "SELECT id,person_id FROM $table WHERE person_id = $ids AND id_objective = $objid";
		$resultjam = $db->query($sqljam);
		list($idjam,$persidcek)=$db->fetchRow($resultjam);
	
		
		
			if($idjam == null)
			{
				$sql = "INSERT INTO $table (id_session,id_objective,person_id,$col_val) VALUES ('$psid','$objid','$ids','$val')";
				$db->query($sql);
			}
			else
			{
				$sql = "UPDATE $table SET $col_val = '$val' WHERE id = '$ids'";
				$db->query($sql); 
			}


	}
 
   function app_editTarget($args) {
      $db=&Database::getInstance();
      $id_ksf = $args[0];
      if($id_ksf=="new") {
         $btn = "<input type='button' value='"._ADD."' onclick='save_perspective();'/>&nbsp;&nbsp;"
              . "&nbsp;<input type='button' value='"._CANCEL."' onclick='persbox.fade();'/>";
      } else {
		$sqlksf = "SELECT  a.id,a.ksf_code,a.ksf_title,a.ksf_lower_perform,a.ksf_need_improvement,a.ksf_target,a.ksf_req,a.ksf_far_req  FROM sms_objective_ksf a JOIN sms_objective_team b ON(a.id =  b.id_objective_ksf) WHERE a.id= $id_ksf";
		$resultksf = $db->query($sqlksf);						
		list($id_ksf,$ksf_code,$ksf_title,$ksf_lowper,$ksf_needimp,$ksf_target,$ksf_req,$ksf_far_req)=$db->fetchRow($resultksf);        
         $ksf_lowper = htmlentities($ksf_lowper,ENT_QUOTES);
         $ksf_needimp = htmlentities($ksf_needimp,ENT_QUOTES);
         $ksf_target = htmlentities($ksf_target,ENT_QUOTES);
         $ksf_req = htmlentities($ksf_req,ENT_QUOTES);
         $ksf_far_req = htmlentities($ksf_far_req,ENT_QUOTES);
         $btn = "<input type='button' value='"._SAVE."' onclick='save_ksf();'/>&nbsp;&nbsp;"
              . "<input type='button' value='"._CANCEL."' onclick='persbox.fade();'/>&nbsp;&nbsp;&nbsp;&nbsp;";
      }
                
      $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>"
           . ($id_ksf=="new"?"Add KSF":"Edit KSF $id_ksf")
           . "</div>"
           . "<div style='padding:5px;background-color:#f2f2f2;color:#555;'>"
              . "<div style='max-height:235px;height:345px;overflow:auto;border:1px solid #999;background-color:#fff;padding:4px;' id='frmobj'>"
                  . "<table style='width:100%;border-spacing:0px;'><tbody><tr><td style='height:215px;'>"
                     . "<table class='xxfrm' style='width:100%;'><tbody>"
                        . "<tr><td>Lower performer</td><td><input type='text' name='ksf_lower_perform' id='ksf_lower_perform' value='$ksf_lowper' style='width:95%;'/></td></tr>"
                        . "<tr><td>Still need improvement</td><td><input type='text' name='ksf_need_improvement' id='ksf_need_improvement' value='$ksf_needimp' style='width:95%%;'/></;'/></td></tr>"
                        . "<tr><td>Fulfill standard of work performance</td><td><input type='text' name='ksf_target' id='ksf_target' value='$ksf_target' style='width:95%;'/></;'/></td></tr>"
                        . "<tr><td>Exceed required performance</td><td><input type='text' name='ksf_req' id='ksf_req' value='$ksf_req' style='width:95%;'/></;'/></td></tr>"
                        . "<tr><td>Far exceed required performance</td><td><input type='text' name='ksf_far_req' id='ksf_far_req' value='$ksf_far_req' style='width:95%;'/></;'/></td></tr>"

                       . "</tbody></table>"
                     . "<input type='hidden' name='id_ksf' id='id_ksf' value='$id_ksf'/>"
                  . "</td></tr></tbody></table>"
              . "</div>"
           . "</div>"
           . "<div style='text-align:center;padding:10px;background-color:#f2f2f2;height:100px;' id='frmbtn'>"
           . $btn
           . "</div>";
     
	  
      return $ret;
   }

    function app_saveTarget($args) {
      $db=&Database::getInstance();
      $vars = _parseForm($args[0]);
	  $user_id = getUserID();
      $date = getSQLDate();
	  $id_ksf = $vars["id_ksf"];
	
      $sql = "UPDATE sms_objective_ksf SET "
        . "ksf_lower_perform = '".addslashes($vars["ksf_lower_perform"])."',"
        . "ksf_need_improvement = '".addslashes($vars["ksf_need_improvement"])."',"
        . "ksf_target = '".addslashes($vars["ksf_target"])."',"
        . "ksf_req = '".addslashes($vars["ksf_req"])."',"
		. "ksf_far_req = '".addslashes($vars["ksf_far_req"])."'"
        . " WHERE id = '$id_ksf'";
       $db->query($sql);
    
	  $ksf_lowper = $vars["ksf_lower_perform"];
	  $ksf_needimp = $vars["ksf_need_improvement"];
	  $ksf_target = $vars["ksf_target"];
	  $ksf_req = $vars["ksf_req"];
	  $ksf_far_req = $vars["ksf_far_req"];

        
      return array($id_ksf,$ksf_lowper,$ksf_needimp,$ksf_target,$ksf_req,$ksf_far_req);
   }
  
  #PROPOSE
   function app_propose($args) {
		
		include_once(XOCP_DOC_ROOT.'/config.php');
		include_once('Mail.php');
		$db=&Database::getInstance();
		$psid =  $_SESSION["pms_psid"];
		
		$sqljob = "SELECT j.employee_id,j.upper_employee_id,j.job_id,j.upper_job_id FROM hris_employee_job j LEFT JOIN hris_users u ON (u.person_id = j.employee_id) WHERE u.user_id = ".getUserid()."";
		$resultjob = $db->query($sqljob);
		 list($employee_id,$upper_employee_id,$job_id,$upper_job_id)=$db->fetchRow($resultjob);		

		$propose_stat = '1';
		$status = 'proposed';
		$proposedate = getSQLDate();
		//$sql = "UPDATE sms_jam_approval SET id_session = '$psid', employee_id = '$employee_id', propose_stat = $propose_stat, propose_date = '$propose_date'";
        
		$sqlcnt = "SELECT COUNT(id) FROM sms_jam_approval WHERE employee_id = $employee_id AND id_session = '$psid'";
		$resultcnt = $db->query($sqlcnt);
		 list($idcount)=$db->fetchRow($resultcnt);	
		
		$sqlect = "SELECT id FROM sms_jam_approval WHERE employee_id = $employee_id AND id_session = '$psid'";
		$resultect = $db->query($sqlect);
		 list($idprop)=$db->fetchRow($resultect);	
		
		
		if ($idcount > 0){
			
			$sql = "UPDATE sms_jam_approval SET propose_stat = '$propose_stat', propose_date = '$proposedate', status = '$status' WHERE id_session = '$psid' AND id ='$idprop' ";
			$db->query($sql);	
			
		}
		elseif ($idcount == 0) {
			$sql = "INSERT INTO sms_jam_approval (id_session,employee_id,id_upper_employee,propose_stat,propose_date,status) VALUES ('$psid','$employee_id','$upper_employee_id','$propose_stat','$proposedate','$status');";
			$db->query($sql);
		}
		
		else {
			return;
		}
		
	
		$propose_date = date('d/M/Y',strtotime($proposedate));
		
		$sqljob = "SELECT j.job_id,j.upper_employee_id,j.gradeval FROM hris_employee_job j LEFT JOIN hris_users u ON (u.person_id = j.employee_id) WHERE u.user_id = ".getUserid()."";
		$resultjob = $db->query($sqljob);
		 list($job_id,$upper_employee_id,$grade)=$db->fetchRow($resultjob);		
	
	
	 $jobidto = $upper_employee_id;
	

	 $sqlto = "SELECT p.user_id, u.email, u.person_nm, u.smtp_location FROM hris_employee_job j LEFT JOIN hris_persons u ON ( u.person_id = j.employee_id )  LEFT JOIN hris_users p ON ( u.person_id = p.person_id ) WHERE j.employee_id = '$jobidto'";    
     $resultto = $db->query($sqlto);
     list($user_id_proposed,$emailto,$namato,$smtpto)=$db->fetchRow($resultto);
	
	$sqlfrom = "SELECT p.user_id,ps.email,ps.person_nm,ps.smtp_location FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id WHERE p.user_id= ".getuserid();"";  
    $resultfrom = $db->query($sqlfrom);
	list($user_id_approved,$emailfrom,$namafrom,$smtpfrom)=$db->fetchRow($resultfrom);

	
	 $smtp = "JKMS01";

    $emailto = $this->emailmci($emailto);
   
     $subject =   "Reminder $namato";

     $recipient = $emailto;
     $headers["From"]    = "hris@mcci.com";
     $headers["To"]      = $namato;
     //$headers["Cc"] =  $emailcc;   
     $headers["Subject"] = "HRIS JAM: Need Your Approval  ";
     $headers["MIME-Version"] = "1.0";
     $headers["Content-type"] = "text/html; charset=iso-8859-1";
     $body =  "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'><html xmlns='http://www.w3.org/1999/xhtml'>
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
              A JAM form has been submitted to you, please click this <a href='http://".$_SERVER['SERVER_NAME']."/".XOCP_SERVER_SUBDIR."/index.php?XP_smsjamapproval'>link</a> to check and process approval.
              <br/>
              <br/>
              Thank you and best regards,
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
             $mail_object->send($recipient, $headers, $body);
             $str = "Email Sent";
          ob_end_clean();
           
	
		$str = "Proposed, an email has sent to $namato";
		
		return array($psid,$employee_id,$propose_stat,$propose_date,$str);

   }
  
    
  #APPROVE
   function app_approve($args) {
   
		include_once(XOCP_DOC_ROOT.'/config.php');
		include_once('Mail.php');
		$db=&Database::getInstance();
		$psid =  $_SESSION["pms_psid"];
		
		$id_app = $args[0];
		$sqljob = "SELECT j.employee_id,j.upper_employee_id,j.job_id,j.upper_job_id FROM hris_employee_job j LEFT JOIN hris_users u ON (u.person_id = j.employee_id) WHERE u.user_id = ".getUserid()."";
		$resultjob = $db->query($sqljob);
		list($employee_id,$upper_employee_id,$job_id,$upper_job_id)=$db->fetchRow($resultjob);		

		$approve_stat = '1';
		$approve_date = getSQLDate();
		$sql = "UPDATE sms_jam_approval SET approve1_by = '$employee_id', approve1_stat = '$approve_stat', approve1_date = '$approve_date' WHERE id_session = '$psid' AND id ='$id_app' ";
		$db->query($sql);
		$approve_date = date('d/M/Y', strtotime($approve_date));
		
		$sqljob = "SELECT j.job_id,j.upper_employee_id,j.gradeval FROM hris_employee_job j LEFT JOIN hris_users u ON (u.person_id = j.employee_id) WHERE u.user_id = ".getUserid()."";
		$resultjob = $db->query($sqljob);
		list($job_id,$upper_employee_id,$grade)=$db->fetchRow($resultjob);		
	
	
	$jobidto = $upper_employee_id;
	
	$sqljobs = "SELECT j.job_id,j.gradeval FROM hris_employee_job j LEFT JOIN hris_users u ON (u.person_id = j.employee_id) WHERE u.user_id = ".getUserid()."";
	$resultjobs = $db->query($sqljobs);
	 list($job_id,$gradeval)=$db->fetchRow($resultjobs);
	//echo 'getuserid = '.getuserid(). '<br> job_id = '.$job_id; exit();
	 	 if ($gradeval > 8){
       	$jobidto = '0';
	  }
	  else
	  {
			$jobidto = $upper_employee_id;
	  }
	
	

	$sqlto = "SELECT p.user_id, u.email, u.person_nm, u.smtp_location FROM hris_employee_job j LEFT JOIN hris_persons u ON ( u.person_id = j.employee_id )  LEFT JOIN hris_users p ON ( u.person_id = p.person_id ) WHERE j.employee_id = '$jobidto'";    
     $resultto = $db->query($sqlto);
     list($user_id_proposed,$emailto,$namato,$smtpto)=$db->fetchRow($resultto);
	
	
		$sqlfrom = "SELECT p.user_id,ps.email,ps.person_nm,ps.smtp_location FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id WHERE p.user_id= ".getuserid();"";  
		$resultfrom = $db->query($sqlfrom);
		list($user_id_approved,$emailfrom,$namafrom,$smtpfrom)=$db->fetchRow($resultfrom);

		$smtp = "JKMS01";
		$to        =		$this->emailmci($emailto);
		$subject = 	"Reminder $namato";

	     $recipient = $to;
         $headers["From"]    = "hris@mcci.com";
         $headers["To"]      = $emailto;
		 //$headers["Cc"] =  $emailcc;  
         $headers["Subject"] = "HRIS JAM: Need Your Approval ";
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
									A JAM form has been submitted to you, please click this <a href='http://".$_SERVER['SERVER_NAME']."/".XOCP_SERVER_SUBDIR."/index.php?XP_smsjamapproval'>link</a> to check and process approval.
									<br/>
									Thank you and best regards,
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
			$str = "Email Sent to $namato";
			ob_end_clean();
		}
		else 
		{
			$str = "not sent";
		}  
		
		$str = "Approved, an email has sent to $namato";
		
		return array($psid,$employee_id,$approve_stat,$approve_date,$str,$id_app);
		

   }
   
     #APPROVE2
   function app_approve2($args) {
		$db=&Database::getInstance();
		$psid =  $_SESSION["pms_psid"];
		$id_app = $args[0];
		
		$sqljob = "SELECT j.employee_id,j.upper_employee_id,j.job_id,j.upper_job_id FROM hris_employee_job j LEFT JOIN hris_users u ON (u.person_id = j.employee_id) WHERE u.user_id = ".getUserid()."";
		$resultjob = $db->query($sqljob);
		 list($employee_id,$upper_employee_id,$job_id,$upper_job_id)=$db->fetchRow($resultjob);		
	
	
		$approve2_stat = '1';
		$approve2_date = getSQLDate();
		$sql = "UPDATE sms_jam_approval SET approve2_by = '$employee_id', approve2_stat = '$approve2_stat', approve2_date = '$approve2_date' WHERE id_session = '$psid' AND id ='$id_app' ";
		$db->query($sql);
		$approve2_date = date('d/M/Y', strtotime($approve_date));
		$str = 'approved';
		return array($psid,$employee_id,$approve2_stat,$approve2_date,$str,$id_app);

   }
  
 
 #//ADD OBJECTIVE//#
 
    function app_saveObjective($args) {
      $db=&Database::getInstance();
      $vars = _parseForm($args[0]);
      $current_psid = $_SESSION["sms_id"];
      $user_id = getUserID();
      $date =getSQLDate();
      $vars["objective_weight"] = _bctrim(bcadd($vars["objective_weight"],0));
      $vars["id_themes"] = _bctrim(bcadd($vars["id_themes"],0));
      $vars["id_ref_perspektive"] = _bctrim(bcadd($vars["id_ref_perspektive"],0));
      $vars["id_objective_owner"] = _bctrim(bcadd($vars["id_objective_owner"],0));
      $vars["id_objective_owner_2"] = _bctrim(bcadd($vars["id_objective_owner_2"],0));
      $vars["psid"] = _bctrim(bcadd($vars["psid"],0));

      
         $sql = "SELECT MAX(id) FROM sms_objective";
         $result = $db->query($sql);
         list($objid)=$db->fetchRow($result);


      $objid= $objid+1;
      $sql = "INSERT INTO sms_objective (id,create_user_id,create_date) VALUES ('$objid','$user_id','$date')";
      $db->query($sql);
      $is_new = 1;
  

      $sql = "UPDATE sms_objective SET "
        . "objective_code = '".addslashes($vars["objective_code"])."',"
        . "id_themes = '".$vars["id_themes"]."',"
		. "objective_title = '".addslashes($vars["objective_title"])."',"
        . "objective_description = '".addslashes($vars["objective_description"])."',"
        . "id_ref_perspektive = '".$vars["id_ref_perspektive"]."',"
        . "id_objective_owner = '".$vars["id_objective_owner"]."',"
        . "id_objective_owner_2 = '".$vars["id_objective_owner_2"]."',"
        . "objective_weight = '".$vars["objective_weight"]."',"
        . "psid = '".$vars["psid"]."'"
        . "  WHERE id = '$objid'";
       $db->query($sql);
      
      $sql = "SELECT weight FROM sms_section_perspective";
      $result = $db->query($sql);
      $ttlw = 0;
      if($db->getRowsNum($result)>0) {
         while(list($w)=$db->fetchRow($result)) {
            $ttlw = bcadd($ttlw,$w);
         }
      }
      
      $ttlw = _bctrim($ttlw);
      
      return array($is_new,$objid,$vars["objective_code"],$vars["objective_title"],$vars["id_themes"],$vars["id_ref_perspektive"],$vars["id_objective_owner"],$vars["id_objective_owner_2"],$vars["objective_weight"],$vars["objective_description"],$vars["psid"]);
     
   }
   
   function app_addObj($args) {
      $db=&Database::getInstance();
      $objid = $args[0];
      if($objid=="new") {
         $btn = "<input type='button' value='"._ADD."' onclick='save_obj();'/>&nbsp;&nbsp;"
              . "&nbsp;<input type='button' value='"._CANCEL."' onclick='persbox.fade();'/>";
      } 
        $date =getSQLDate();
	$psid = $_SESSION["pms_psid"];
	$sqlsess = "SELECT id,periode_session FROM sms_session";
	$resultsess = $db->query($sqlsess); 
	  
	$sqltheme = "SELECT id,title FROM sms_ref_themes WHERE session='$psid'";
	$resulttheme = $db->query($sqltheme);
	
	$sqlper = "SELECT id,code,title FROM sms_ref_perspektive WHERE session='$psid'";
	$resultper = $db->query($sqlper);

	$sqloo = "SELECT person_id,person_nm FROM hris_persons WHERE status_cd = 'normal' ORDER BY hris_persons.person_nm ASC";
	$resultoo = $db->query($sqloo);
	
	$sqloo2 = "SELECT person_id,person_nm FROM hris_persons WHERE status_cd = 'normal' ORDER BY hris_persons.person_nm ASC";
	$resultoo2 = $db->query($sqloo2);
	  

	$ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>"
           . ($objid=="new"?"Add Objective":"Edit Objective")
           . "</div>"
           . "<div style='padding:5px;background-color:#f2f2f2;color:#555;'>"
              . "<div style='max-height:280px;height:280px;overflow:auto;border:1px solid #999;background-color:#fff;padding:4px;' id='frmobj'>"
                  . "<table style='width:100%;border-spacing:0px;'><tbody><tr><td style='height:215px;'>"
                     . "<table class='xxfrm' style='width:100%;'><tbody>"     
						. "<tr><td>Session   </td><td>";
				$ret .= "<select name='psid' id='psid'>";
					
				
						while(list($psid,$periode)=$db->fetchRow($resultsess)) {
								if($_SESSION["pms_psid"]==$psid){$selecteds='selected';}else{$selecteds='';}
								$ret.= "<option id='psid' value='$psid' disabled $selecteds>$periode</option>";
								}    
				 $ret .= "</select> </td></tr></td></tr>"    
						. "<tr><td>Objective Code</td><td><input type='text' name='objective_code' id='objective_code' value='$objective_code' style='width:40px;'/></td></tr>"
                        . "<tr><td>Theme</td><td>"
							."<select name='id_themes' id='id_themes'>";
						while(list($id_themes,$reftheme)=$db->fetchRow($resulttheme)) {
								$ret.= "<option value='$id_themes'>$reftheme</option>";
								}
				 $ret .= "</select> </td></tr></td></tr>"    
							. "<tr><td>Perspective</td><td>"
							."<select name='id_ref_perspektive' id='id_ref_perspektive'>";
						while(list($id_ref_perspektive,$code,$reftitle)=$db->fetchRow($resultper)) {
								$ret.= "<option value='$id_ref_perspektive'>$code - $reftitle</option>";
								}
				$ret .= "</select> </td></tr>"
                        . "<tr><td>Objective Title</td><td><input type='text' name='objective_title' id='objective_title' value='$objective_title' style='width:90%;'/></td></tr>"
                         . "<tr><td>Description</td><td><textarea name='objective_description' id='objective_description' style='width:90%;height:70px;'>$objective_description</textarea></td></tr>"
                        . "<tr><td>Owner</td><td>"
						."<select name='id_objective_owner' id='id_objective_owner'>";
						while(list($id_objective_owner,$fullname)=$db->fetchRow($resultoo)) {
								$ret .= "<option value='$id_objective_owner'>$fullname</option>";
								}
				$ret .= "</select> </td></tr></td></tr>"
				   . "<tr><td>Owner 2</td><td>"
						."<select name='id_objective_owner_2' id='id_objective_owner_2'>"
										  ."<option value='0'>-</option>";
						while(list($id_objective_owner_2,$fullname2)=$db->fetchRow($resultoo2)) {
								$ret .= "<option value='$id_objective_owner_2'>$fullname2</option>";
								}
				$ret .= "</select> </td></tr></td></tr>"
                       . "<tr><td>Weigth</td><td><input type='text' name='objective_weight' id='objective_weight' value='$objective_weight' style='width:40px;'/> %</td></tr>"
					   
                       . "</tbody></table>"
	                . "</td></tr></tbody></table>"
              . "</div>"
           . "</div>"
           . "<div style='text-align:center;padding:10px;background-color:#f2f2f2;height:100px;' id='frmbtn'>"
           . $btn
           . "</div>";
      
      return $ret;
   }
   
   function app_deletePerspective($args) {
      $db=&Database::getInstance();
      $objid = $args[0];
      $sql = "DELETE FROM sms_section_perspective WHERE id = '$objid'";
      $db->query($sql);
   }
   
   
	function app_returns($args) {
      

//	BUAT RETURN YANG APPROVAL;

	  $db=&Database::getInstance();
      $psid = $args[0];
      $person_id = $args[1];
      $id_modified = getUserID();
	  $date_modified  = getSQLDate();

	  $sql =  "SELECT id,id_session,employee_id,return_note FROM sms_jam_approval "
			  . "  WHERE id_session = '$psid' AND  employee_id = '$person_id' " ;
	  $result = $db->query($sql); 
    	 
       
	   list($id,$psid,$person_id,$return_note)=$db->fetchRow($result);
       // $year = htmlentities($year,ENT_QUOTES);
	
      
      $ret = "<form id='frm' style='padding-top:10px;'><table class='xxfrm' style='width:100%;'><colgroup><col width='160'/><col/></colgroup><tbody>"
			. "<tr><td>Return Note</td><td><input type='text' value='$return_note' id='return_note' name='return_note' style='width:400px;'/></td></tr>" ;
        
       $ret .= "<tr><td colspan='2'><span id='progress'></span>&nbsp;"
           . "<input id='btn_save_session' onclick='save_return($psid,$person_id);' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input id='btn_cancel_edit' onclick='cancel_return();' type='button' value='"._CANCEL."'/>&nbsp;&nbsp;"
           //. ($psid!="new"?"<input id='btn_delete_session' onclick='delete_session();' type='button' value='"._DELETE."'/>":"")
           . "</td></tr>"
           . "</tbody></table></form>";
      return $ret;
   }
	
	function app_saveReturn($args) {
	   include_once(XOCP_DOC_ROOT.'/config.php');
	   include_once('Mail.php');
		//$vars = _parseForm($args[0]);
	    
		$db=&Database::getInstance();
		$psid = $args[0];
		$person_id = $args[1];	
		$date_modified  = getSQLDate();
		$user_id = getUserID();
     
      $vars = parseForm($args[2]);
      $return_note = addslashes(trim($vars["return_note"]));


	  $sql = "UPDATE sms_jam_approval SET  propose_stat = '0', propose_date = '0000-00-00', approve1_by = ' ', approve1_stat = '0', approve1_date = '0000-00-00', approve2_by = ' ',  approve2_stat = '0', approve2_date = '0000-00-00' , status = 'return', return_note = '$return_note' , date_return = '$date_modified' WHERE id_session = '$psid' AND  employee_id = '$person_id'";
	  $db->query($sql);
	  
	 $sqlto = "SELECT p.user_id, u.email, u.person_nm, u.smtp_location FROM hris_employee_job j LEFT JOIN hris_persons u ON ( u.person_id = j.employee_id )  LEFT JOIN hris_users p ON ( u.person_id = p.person_id ) WHERE j.employee_id = '$person_id'";    
     $resultto = $db->query($sqlto);
     list($user_id_proposed,$emailto,$namato,$smtpto)=$db->fetchRow($resultto);
	
	$sqlfrom = "SELECT p.user_id,ps.email,ps.person_nm,ps.smtp_location FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id WHERE p.user_id= ".getuserid();"";  
    $resultfrom = $db->query($sqlfrom);
	list($user_id_approved,$emailfrom,$namafrom,$smtpfrom)=$db->fetchRow($resultfrom);
	

	$smtp = "JKMS01";

	$to        =		$this->emailmci($emailto);
	$subject = 	"JAM Return $namato";

	     $recipient = $to;
         $headers["From"]    = "hris@mcci.com";
         $headers["To"]      = $emailto;
		 //$headers["Cc"] =  $emailcc;  
         $headers["Subject"] = "HRIS JAM: JAM Return";
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
									$ret_notes
									<br/>
									A JAM form has been returned to you, please  click this <a href='http://".$_SERVER['SERVER_NAME']."/".XOCP_SERVER_SUBDIR."/index.php?XP_smsmatrixjam'>link</a> to check and submit again.
									<br/>
									<br/>
									Thank you and best regards,
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
			$str = "Email Sent to $namato";
			ob_end_clean();
		}
		else 
		{
			$str = "not sent";
		}   
	  
	  $str = 'JAM has Returned'; 
	  		 
	return array($psid,$person_id,$str);
	 
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

} /// HRIS_ASSESSMENTSESSIONAJAX_DEFINED
?>