<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_idpeventempreg.php              //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_IDPDEVLIBAJAX_DEFINED') ) {
   define('HRIS_IDPDEVLIBAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");
require_once(XOCP_DOC_ROOT."/modules/hris/include/idp/idp.php");
require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");

class _hris_class_IDPEventEmployeeRegistrationAjax extends AjaxListener {
   
   function _hris_class_IDPEventEmployeeRegistrationAjax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/hris/class/ajax_idpeventempreg.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_Delete","app_editEvent","app_saveEvent",
                            "app_resetSuperior","app_browseCompetency","app_selectCompgroup",
                            "app_addCompetency","app_deleteCompetencyRel","app_selectMethod",
                            "app_createEvent","app_searchEmployee","app_registerEmployee",
                            "app_editRegistration","app_confirmNotify","app_deleteRegistration",
                            "app_selectUnconfirmed","app_selectInvited","app_selectSelfRegistered",
                            "app_selectIn","app_selectOut","app_sendNotification","app_confirmDelete",
                            "app_importFromIDPRequest","app_importSelected","app_editEmail",
                            "app_saveDraft");
   }
   
   function app_saveDraft($args) {
      $event_id = $args[0];
      $email_id = $args[1];
      $email = _parseForm($args[2]);
      $_SESSION[$email_id] = $email;
   }
   
   function app_editEmail($args) {
      $db=&Database::getInstance();
      $event_id = $args[0];
      $email_id = $args[1];
      $sql = "SELECT event_title FROM ".XOCP_PREFIX."idp_event WHERE event_id = '$event_id'";
      $result = $db->query($sql);
      list($event_title)=$db->fetchRow($result);
      
      if(isset($_SESSION[$email_id])) {
         $msgsubject = $_SESSION[$email_id]["msgsubject"];
         $msgbody = $_SESSION[$email_id]["msgbody"];
      } else {
         $msgsubject = $event_title;
         $msgbody = "";
      }
      
      $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>E-Mail Editor</div>"
           . "<div id='emaileditor' style='padding:5px;overflow:auto;max-height:260px;'>"
               . "<table style='width:100%;'><tbody>"
               . "<tr><td style='text-align:right;'>Subject :</td><td><input id='msgsubject' name='msgsubject' value='$msgsubject' type='text' style='width:400px;'/></td></tr>"
               . "<tr><td style='text-align:right;vertical-align:top;'>Message :</td><td><textarea name='msgbody' id='msgbody' style='height:220px;width:400px;'>$msgbody</textarea></td></tr>"
               . "</tbody></table>"
           . "</div>"
           . "<div id='apformbtn' style='text-align:center;padding:10px;'>"
               . "<input type='button' value='Save Draft' style='' onclick='save_draft(\"$event_id\",\"$email_id\",this,event);'/>&nbsp;"
               . "<input type='button' value='"._CANCEL."' style='' onclick='emaileditfade();'/>"
               . "<input type='hidden' id='emplist_h' value='$emplist_h'/>"
           . "</div>";
      return array($ret,390);
      
   }
   
   function app_importSelected($args) {
      $db=&Database::getInstance();
      $event_id = $args[0];
      $emp = _parseForm($args[1]);
      $ret = array();
      foreach($emp as $k=>$v) {
         list($request_id,$employee_id,$actionplan_id)=explode("|",$v);
         $res = $this->app_registerEmployee(array($event_id,$employee_id,$request_id,$actionplan_id));
         if(is_array($res)) {
            $ret[] = $res;
         }
      }
      return $ret;
   }
   
   function app_importFromIDPRequest($args) {
      $db=&Database::getInstance();
      $event_id = $args[0];
      
      $sql = "SELECT a.event_title,a.method_id,b.method_t FROM ".XOCP_PREFIX."idp_event a"
           . " LEFT JOIN ".XOCP_PREFIX."idp_development_method b USING(method_id)"
           . " WHERE a.event_id = '$event_id'";
      $result = $db->query($sql);
      list($event_title,$method_id,$method_t)=$db->fetchRow($result);
      
      $sql = "SELECT d.person_nm,c.employee_ext_id,b.employee_id,a.event_id,a.request_id,a.actionplan_id,a.competency_id"
           . " FROM ".XOCP_PREFIX."idp_request_actionplan a"
           . " LEFT JOIN ".XOCP_PREFIX."idp_request b USING(request_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee c USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons d USING(person_id)"
           . " WHERE a.method_id = '$method_id'";
      $result = $db->query($sql);
      $mlist = "";
      $cnt = 0;
      $is_empty = 0;
      $emplist = array();
      if($db->getRowsNum($result)>0) {
         while(list($employee_nm,$nip,$employee_id,$event_idx,$request_id,$actionplan_id,$competency_id)=$db->fetchRow($result)) {
            $sql = "SELECT * FROM ".XOCP_PREFIX."idp_event_registration WHERE event_id = '$event_id' AND employee_id = '$employee_id'";
            $rreg = $db->query($sql);
            $is_registered = $db->getRowsNum($rreg);
            
            $sql = "SELECT status_cd FROM ".XOCP_PREFIX."idp_event_registration WHERE event_id = '$event_idx' AND employee_id = '$employee_id'";
            $rreg = $db->query($sql);
            $is_registered_other = $db->getRowsNum($rreg);
            if($is_registered_other>0) {
               list($other_status)=$db->fetchRow($rreg);
            } else {
               $other_status = "";
            }
            
            
            $cnt++;
            $mlist .= "<div style='padding:3px;border-bottom:1px solid #ddd;cursor:default;'>"
                     . "<table border='0' class='ilist' style='width:100%;'>"
                     . "<colgroup><col width='40'/><col width='80'/><col/><col/></colgroup>"
                     . "<tbody><tr>"
                     . "<td style='text-align:center;'>"
                        . ($is_registered>0?"-":"<input type='checkbox' name='importemployee_${employee_id}' value='$request_id|$employee_id|$actionplan_id'/>")
                     . "</td>"
                     . "<td style='text-align:left;'>$nip</td>"
                     . "<td>".htmlentities(stripslashes($employee_nm))."</td>"
                     . "<td style='text-align:right;'>".($event_idx==$event_id?"Registered":($is_registered_other>0?"Registered to other event":""))."</td>"
                     . "</tr></tbody></table>"
                    . "</div>";
         }
      } else {
         $mlist = "<div style='text-align:center;padding:3px;border-bottom:1px solid #ddd;cursor:default;'>No IDP Request match this event.</div>";
         $cnt++;
         $is_empty = 1;
      }
      
      $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>Import Employee from IDP Request</div>"
           . "<div style='padding:5px;border-bottom:1px solid #bbb;background-color:#eee;text-align:center;font-weight:bold;'>Import to event : $event_title</div>"
           . "<div style='padding:5px;border-bottom:1px solid #bbb;background-color:#eee;text-align:left;'>"
           
                     . "<table border='0' class='ilist' style='width:100%;'>"
                     . "<colgroup><col width='40'/><col/></colgroup>"
                     . "<tbody><tr>"
                     . "<td style='text-align:left;'><span onclick='select_import(this,event)' class='xlnk'>Select</span></td>"
                     . "</tr></tbody></table>"
           
           . "</div>"
           . "<div id='dvimport' style='padding:5px;overflow:auto;max-height:260px;'>"
               . $mlist
           . "</div>"
           . "<div id='apformbtn' style='text-align:center;padding:10px;'>"
               . ($is_empty==0?"<input type='button' value='Import' style='' onclick='import_selected(\"$event_id\",this,event);'/>&nbsp;":"")
               . "<input type='button' value='"._CANCEL."' style='' onclick='importidpbox.fade();'/>"
           . "</div>";
      return array($ret,min(410,180+($cnt*25)));
   }
   
   
   
   function app_deleteRegistration($args) {
      $db=&Database::getInstance();
      $event_id = $args[0];
      $emplist = explode("|",$args[1]);
      if(count($emplist)>0) {
         foreach($emplist as $employee_id) {
            if($employee_id>0) {
               $sql = "SELECT request_id,actionplan_id FROM ".XOCP_PREFIX."idp_event_registration WHERE event_id = '$event_id' AND employee_id = '$employee_id'";
               $result = $db->query($sql);
               if($db->getRowsNum($result)==1) {
                  list($request_id,$actionplan_id)=$db->fetchRow($result);
                  $sql = "UPDATE ".XOCP_PREFIX."idp_request_actionplan SET event_id = '0' WHERE request_id = '$request_id' AND actionplan_id = '$actionplan_id'";
                  $db->query($sql);
               }
               $sql = "DELETE FROM ".XOCP_PREFIX."idp_event_registration WHERE event_id = '$event_id' AND employee_id = '$employee_id'";
               $db->query($sql);
            }
         }
      }
   }
   
   function app_confirmDelete($args) {
      $db=&Database::getInstance();
      $event_id = $args[0];
      $employee_id = $args[1];
      $vars = _parseForm($args[2]);
      
      $sql = "SELECT event_title FROM ".XOCP_PREFIX."idp_event WHERE event_id = '$event_id'";
      $result = $db->query($sql);
      list($event_title)=$db->fetchRow($result);
      
      if($employee_id=="A") {
         $qemp = " AND a.employee_id IN ('";
         if(count($vars)>0) {
            $qemp .= implode("','",$vars);
         }
         $qemp .= "')";
      } else {
         $qemp = " AND a.employee_id = '$employee_id'";
      }
      $sql = "SELECT a.employee_id,a.status_cd,a.email_notify_ind,b.employee_ext_id,c.person_nm"
           . " FROM ".XOCP_PREFIX."idp_event_registration a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " WHERE a.event_id = '$event_id'"
           . $qemp
           . " ORDER BY c.person_nm";
      $result = $db->query($sql);
      $mlist = "";
      $cnt = 0;
      $is_empty = 0;
      $emplist = array();
      if($db->getRowsNum($result)>0) {
         while(list($employee_id,$status_cd,$email_notify_ind,$nip,$employee_nm)=$db->fetchRow($result)) {
            $cnt++;
            switch($status_cd) {
               case "invited":
                  $status_cd_txt = "Invited";
                  break;
               case "self_registered":
                  $status_cd_txt = "Self Registered";
                  break;
               case "in":
                  $status_cd_txt = "In";
                  break;
               case "out":
                  $status_cd_txt = "Out";
                  break;
               default:
                  $status_cd_txt = "";
                  break;
            }
            $emplist[] = $employee_id;
            $mlist .= "<div style='padding:3px;border-bottom:1px solid #ddd;cursor:default;'>"
                     . "<table border='0' class='ilist' style='width:100%;'>"
                     . "<colgroup><col width='80'/><col/><col/></colgroup>"
                     . "<tbody><tr>"
                     . "<td style='text-align:left;'>$nip</td>"
                     . "<td>".htmlentities(stripslashes($employee_nm))."</td>"
                     . "<td style='text-align:right;'>$status_cd_txt</td>"
                     . "</tr></tbody></table>"
                    . "</div>";
         }
         $emplist_h = implode("|",$emplist);
      } else {
         $mlist = "<div style='text-align:center;padding:3px;border-bottom:1px solid #ddd;cursor:default;'>None selected. Please select first.</div>";
         $cnt++;
         $is_empty = 1;
         $emplist_h = "";
      }
      
      $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>Delete Registration</div>"
           . "<div style='padding:5px;border-bottom:1px solid #bbb;background-color:#eee;'>Event : $event_title</div>"
           . "<div id='apformeditor' style='padding:5px;overflow:auto;max-height:260px;'>"
               . $mlist
           . "</div>"
           . "<div id='apformbtn' style='text-align:center;padding:10px;'>"
               . ($is_empty==0?"<input type='button' value='Delete' style='' onclick='delete_confirmed(\"$event_id\",this,event);'/>&nbsp;":"")
               . "<input type='button' value='"._CANCEL."' style='' onclick='confirmdeletebox.fade();'/>"
               . "<input type='hidden' id='emplist_h' value='$emplist_h'/>"
           . "</div>";
      return array($ret,min(410,150+($cnt*25)));
   }
   
   
   
   function app_sendNotification($args) {
      $db=&Database::getInstance();
      $event_id = $args[0];
      $emplist = explode("|",$args[1]);
      $emails = _parseForm($args[2]);
      $email_id = "xemail_${event_id}";
      
      if(count($emplist)>0) {
         foreach($emplist as $employee_id) {
            if($employee_id>0) {
               $sql = "UPDATE ".XOCP_PREFIX."idp_event_registration SET last_email_dttm = now(), email_notify_ind = email_notify_ind+1 WHERE event_id = '$event_id' AND employee_id = '$employee_id'";
               $db->query($sql);
               
               $sql = "SELECT request_id FROM ".XOCP_PREFIX."idp_event_registration WHERE event_id = '$event_id' AND employee_id = '$employee_id'";
               $result = $db->query($sql);
               list($request_id)=$db->fetchRow($result);
               
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
               
               if($emails["email_type"]==1) {
                  //// send email only
                  
                  $sql = "SELECT a.pwd0,a.person_id,b.email,b.smtp_location,b.person_nm FROM ".XOCP_PREFIX."users a"
                       . " LEFT JOIN ".XOCP_PREFIX."persons b USING(person_id)"
                       . " WHERE a.user_id = '$user_idx'";
                  $result = $db->query($sql);
                  list($pwd0,$person_id,$email,$smtp,$person_nm)=$db->fetchRow($result);
                  
                  list($user_mail,$domain_mail)=explode("@",$email);
                  $email = "${user_mail}@${smtp}";
                  $send_dttm = getSQLDate();
                  if(isset($_SESSION[$email_id])) {
                     $subject = $_SESSION[$email_id]["msgsubject"];
                     $msg_body = $_SESSION[$email_id]["msgbody"];
                  } else {
                     return;
                  }
                  ///////////////////////////////////////////////////////////////////////////////////////
                  ///////////////////////////////////////////////////////////////////////////////////////
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
<tr><td style='border-right:1px solid #bbb;'><img src='http://146.67.1.47/hris/images/logo.gif'/></td><td><img src='http://146.67.1.47/hris/images/ocd_logo_20100618.jpg'/></td></tr>
<tr><td colspan='2' style='background-color:#5666b3;'>&nbsp;</td></tr>
<tr><td colspan='2'>

<pre width='400' style='padding:15px;padding-top:0px;width:400px;'>$msg_body</pre>

</td></tr>

<tr><td colspan='2' style='background-color:#5666b3;'>&nbsp;</td></tr>


</tbody>
</table>


</body>
</html>
";

                  $created_user_id = getUserID();
                  
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
              
                  _debuglog("Success, write html email to file : $file");
                  
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
                  
                  if($params['host']=="") {
                     return;
                  }
                  
                  /*
                  if($_SESSION["suing"]==1) {
                  } else {
                     // Create the mail object using the Mail::factory method
                     ob_start();
                     $mail_object =& Mail::factory('smtp', $params);
                     $mail_object->send($recipient, $headers, $body);
                     $str = ob_get_contents();
                     ob_end_clean();
                  }
                  */
                  
                  
                  ///////////////////////////////////////////////////////////////////////////////////////
                  ///////////////////////////////////////////////////////////////////////////////////////
                  
               } else {
                  _idp_send_notification($user_idx,$request_id,"_IDP_EVENTYOUHAVEREGISTRATIONCONFIRMATION","","EVENTEMPREG",$user_id,$event_id);
               }
            }
         }
      }
   }
   
   function app_selectOut($args) {
      $db=&Database::getInstance();
      $event_id = $args[0];
      $sql = "SELECT employee_id FROM ".XOCP_PREFIX."idp_event_registration WHERE event_id = '$event_id' AND status_cd = 'out'";
      $result = $db->query($sql);
      $ret = array();
      if($db->getRowsNum($result)>0) {
         while(list($employee_id)=$db->fetchRow($result)) {
            $ret[] = $employee_id;
         }
      }
      return $ret;
   }
   
   function app_selectIn($args) {
      $db=&Database::getInstance();
      $event_id = $args[0];
      $sql = "SELECT employee_id FROM ".XOCP_PREFIX."idp_event_registration WHERE event_id = '$event_id' AND status_cd = 'in'";
      $result = $db->query($sql);
      $ret = array();
      if($db->getRowsNum($result)>0) {
         while(list($employee_id)=$db->fetchRow($result)) {
            $ret[] = $employee_id;
         }
      }
      return $ret;
   }
   
   function app_selectSelfRegistered($args) {
      $db=&Database::getInstance();
      $event_id = $args[0];
      $sql = "SELECT employee_id FROM ".XOCP_PREFIX."idp_event_registration WHERE event_id = '$event_id' AND status_cd = 'self_registered'";
      $result = $db->query($sql);
      $ret = array();
      if($db->getRowsNum($result)>0) {
         while(list($employee_id)=$db->fetchRow($result)) {
            $ret[] = $employee_id;
         }
      }
      return $ret;
   }
   
   function app_selectInvited($args) {
      $db=&Database::getInstance();
      $event_id = $args[0];
      $sql = "SELECT employee_id FROM ".XOCP_PREFIX."idp_event_registration WHERE event_id = '$event_id' AND status_cd = 'invited'";
      $result = $db->query($sql);
      $ret = array();
      if($db->getRowsNum($result)>0) {
         while(list($employee_id)=$db->fetchRow($result)) {
            $ret[] = $employee_id;
         }
      }
      return $ret;
   }
   
   function app_selectUnconfirmed($args) {
      $db=&Database::getInstance();
      $event_id = $args[0];
      $sql = "SELECT employee_id FROM ".XOCP_PREFIX."idp_event_registration WHERE event_id = '$event_id' AND status_cd IN ('invited','self_registered')";
      $result = $db->query($sql);
      $ret = array();
      if($db->getRowsNum($result)>0) {
         while(list($employee_id)=$db->fetchRow($result)) {
            $ret[] = $employee_id;
         }
      }
      return $ret;
   }
   
   function app_confirmNotify($args) {
      $db=&Database::getInstance();
      $event_id = $args[0];
      $employee_id = $args[1];
      $vars = _parseForm($args[2]);
      
      $sql = "SELECT event_title FROM ".XOCP_PREFIX."idp_event WHERE event_id = '$event_id'";
      $result = $db->query($sql);
      list($event_title)=$db->fetchRow($result);
      
      if($employee_id=="A") {
         $qemp = " AND a.employee_id IN ('";
         if(count($vars)>0) {
            $qemp .= implode("','",$vars);
         }
         $qemp .= "')";
      } else {
         $qemp = " AND a.employee_id = '$employee_id'";
      }
      $sql = "SELECT a.employee_id,a.status_cd,a.email_notify_ind,b.employee_ext_id,c.person_nm"
           . " FROM ".XOCP_PREFIX."idp_event_registration a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " WHERE a.event_id = '$event_id'"
           . $qemp
           . " ORDER BY c.person_nm";
      $result = $db->query($sql);
      $mlist = "";
      $cnt = 0;
      $is_empty = 0;
      $emplist = array();
      if($db->getRowsNum($result)>0) {
         while(list($employee_id,$status_cd,$email_notify_ind,$nip,$employee_nm)=$db->fetchRow($result)) {
            $cnt++;
            switch($status_cd) {
               case "invited":
                  $status_cd_txt = "Invited";
                  break;
               case "self_registered":
                  $status_cd_txt = "Self Registered";
                  break;
               case "in":
                  $status_cd_txt = "In";
                  break;
               case "out":
                  $status_cd_txt = "Out";
                  break;
               default:
                  $status_cd_txt = "";
                  break;
            }
            $emplist[] = $employee_id;
            $mlist .= "<div style='padding:3px;border-bottom:1px solid #ddd;cursor:default;'>"
                     . "<table border='0' class='ilist' style='width:100%;'>"
                     . "<colgroup><col width='80'/><col/><col width=''/></colgroup>"
                     . "<tbody><tr>"
                     . "<td style='text-align:left;'>$nip</td>"
                     . "<td>".htmlentities(stripslashes($employee_nm))."</td>"
                     . "<td style='text-align:right;'>$status_cd_txt</td>"
                     . "</tr></tbody></table>"
                    . "</div>";
         }
         $emplist_h = implode("|",$emplist);
      } else {
         $mlist = "<div style='text-align:center;padding:3px;border-bottom:1px solid #ddd;cursor:default;'>None selected. Please select first.</div>";
         $cnt++;
         $is_empty = 1;
         $emplist_h = "";
      }
      
      $email_id = "xemail_${event_id}";
      
      $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>Send E-Mail</div>"
           . "<div style='padding:5px;border-bottom:1px solid #bbb;background-color:#eee;'>Event : $event_title</div>"
           . "<div id='apformeditor' style='padding:5px;overflow:auto;max-height:260px;'>"
               . $mlist
           . "</div>"
           /// email type:
           . "<div>"
               . "<table align='center' id='tblemailtype'><tbody>"
               . "<tr><td colspan='2'>Type of email:</td></tr>"
               . "<tr><td><input type='radio' name='email_type' id='email_type_0' value='0' checked='1'></td><td><label for='email_type_0' class='xlnk'>Confirmation</label></td></tr>"
               . "<tr><td><input type='radio' name='email_type' id='email_type_1' value='1'></td><td><label onclick='edit_email(\"$event_id\",\"$email_id\");' for='email_type_1' class='xlnk'>Other <img src='".XOCP_SERVER_SUBDIR."/images/edit.gif'/></label></td></tr>"
               . "</tbody></table>"
           . "</div>"
           . "<div id='apformbtn' style='text-align:center;padding:10px;'>"
               . ($is_empty==0?"<input type='button' value='Send E-mail' style='' onclick='send_email_confirmed(\"$event_id\",this,event);'/>&nbsp;":"")
               . "<input type='button' value='"._CANCEL."' style='' onclick='confirmnotifyfade();'/>"
               . "<input type='hidden' id='emplist_h' value='$emplist_h'/>"
           . "</div>";
      return array($ret,min(410,210+($cnt*25)));
   }
   
   
   
   
   function app_editRegistration($args) {
      $db=&Database::getInstance();
      $event_id = $args[0];
      $employee_id = $args[1];
      $sql = "SELECT a.status_cd,b.employee_ext_id,c.person_nm,"
           . "a.email_notify_ind,a.hris_confirm_id,hris_confirm_dttm,a.employee_confirm_dttm,"
           . "a.last_email_dttm"
           . " FROM ".XOCP_PREFIX."idp_event_registration a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " WHERE a.event_id = '$event_id'"
           . " AND a.employee_id = '$employee_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($status_cd,$nip,$employee_nm,$email_notify_ind,$hris_confirm_id,$hris_confirm_dttm,
              $employee_confirm_dttm,$last_email_dttm)=$db->fetchRow($result);
         switch($status_cd) {
            case "invited":
               $status_cd_txt = "Invited";
               break;
            case "self_registered":
               $status_cd_txt = "Self Registration";
               break;
            case "in":
               $status_cd_txt = "In";
               break;
            case "out":
               $status_cd_txt = "Out";
               break;
            default:
               $status_cd_txt = "";
               break;
         }
         
         $ret = "<div id='actformeditor' style='padding:5px;border:1px solid black;'>"
                  . "<table style='width:100%;'>"
                  . "<colgroup>"
                     . "<col width='200'/>"
                     . "<col/>"
                  . "</colgroup>"
                  . "<tbody>"
                  . "<tr><td style='text-align:right;vertical-align:top;'>Status : </td><td style='font-weight:bold;'>$status_cd_txt</td></tr>"
                  . "<tr><td style='text-align:right;vertical-align:top;'>Email Sent : </td><td style='font-weight:bold;'>".sql2ind($last_email_dttm,"date")."</td></tr>"
                  . "<tr><td style='text-align:right;vertical-align:top;'>Confirmation Date : </td><td style='font-weight:bold;'>".sql2ind($employee_confirm_dttm,"date")."</td></tr>"
                  . "</tbody></table>"
              . "</div>"
              . "<div id='actformbtn' style='text-align:right;padding:10px;'>"
                  . "<input type='button' value='E-Mail' style='' onclick='send_email_notification(\"$event_id\",\"$employee_id\",this,event);'/>&nbsp;&nbsp;"
                  . "<input type='button' value='"._CANCEL."' style='' onclick='cancel_edit_reg(this,event);'/>&nbsp;&nbsp;&nbsp;&nbsp;"
                  . "<input type='button' value='"._DELETE."' style='' onclick='delete_selected(\"$event_id\",\"$employee_id\",this,event);'/>"
              . "</div>";
         return $ret;
         
         
      }
   }
   
   function app_registerEmployee($args) {
      $db=&Database::getInstance();
      $event_id = $args[0];
      $employee_id = $args[1];
      $request_id = $args[2]+0;
      $actionplan_id = $args[3]+0;
      
      $sql = "SELECT * FROM ".XOCP_PREFIX."idp_event_registration WHERE event_id = '$event_id' AND employee_id = '$employee_id'";
      $result = mysql_query($sql);
      if($db->getRowsNum($result)>0) {
         if($request_id>0&&$actionplan_id>0) {
            $sql = "UPDATE ".XOCP_PREFIX."idp_request_actionplan SET event_id = '$event_id' WHERE request_id = '$request_id' AND actionplan_id = '$actionplan_id'";
            $db->query($sql);
         }
         return "DUPLICATE";
      }
      
      $sql = "INSERT INTO ".XOCP_PREFIX."idp_event_registration (event_id,employee_id,request_id,actionplan_id,status_cd)"
           . " VALUES ('$event_id','$employee_id','$request_id','$actionplan_id','invited')";
      $result = $db->query($sql);
      
      $sql = "SELECT a.status_cd,b.employee_ext_id,c.person_nm,a.employee_confirm_dttm"
           . " FROM ".XOCP_PREFIX."idp_event_registration a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " WHERE a.event_id = '$event_id'"
           . " AND a.employee_id = '$employee_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($status_cd,$nip,$employee_nm)=$db->fetchRow($result);
         switch($status_cd) {
            case "invited":
               $status_cd_txt = "Invited";
               $confirm_dt_txt = "-";
               break;
            case "self_registered":
               $status_cd_txt = "Self Registration";
               $confirm_dt_txt = "-";
               break;
            case "in":
               $status_cd_txt = "In";
               $confirm_dt_txt = sql2ind($employee_confirm_dttm,"date");
               break;
            case "out":
               $status_cd_txt = "Out";
               $confirm_dt_txt = sql2ind($employee_confirm_dttm,"date");
               break;
            default:
               $status_cd_txt = "-";
               $confirm_dt_txt = "-";
               break;
         }
         
         $email_notify_ind = 0;
         
         $ret = "<table border='0' class='ilist' style='width:100%;'>"
                  . "<colgroup><col width='40'/><col width='80'/><col/><col width='150'/><col width=='150'/></colgroup>"
                  . "<tbody><tr>"
                  . "<td style='text-align:center;'><input type='checkbox' name='ce_${employee_id}' value='$employee_id' id='ckbemp_${event_id}_${employee_id}'/></td>"
                  . "<td style='text-align:left;'>$nip</td>"
                  . "<td><span onclick='edit_reg(\"$event_id\",\"$employee_id\",this,event);' class='xlnk'>".htmlentities(stripslashes($employee_nm))."</span></td>"
                  . "<td style='text-align:center;'>$confirm_dt_txt</td>"
                  . "<td style='text-align:center;'>$status_cd_txt</td>"
                  . "</tr></tbody></table>";
         
         
         return array($employee_id,$ret);
      } else {
         return "ERROR";
      }
      
   }
   
   function app_searchEmployee($args) {
      $db=&Database::getInstance();
      $qstr = $args[0];
      
      $sql = "SELECT b.employee_id,b.employee_ext_id,a.person_nm,a.person_id"
           . " FROM ".XOCP_PREFIX."persons a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(person_id)"
           . " WHERE b.employee_ext_id LIKE '$qstr%'"
           . " AND b.person_id IS NOT NULL"
           . " AND a.status_cd = 'normal'"
           . " GROUP BY a.person_id"
           . " ORDER BY b.employee_ext_id";
      $result = $db->query($sql);
      $ret = array();
      if($db->getRowsNum($result)>0) {
         $no = 0;
         while(list($employee_id,$employee_ext_id,$employee_nm,$person_id)=$db->fetchRow($result)) {
            if($no >= 1000) break;
            $ret[] = array("$employee_nm ($employee_ext_id)",$person_id);
            $no++;
         }
      }
      
      $qstr = ereg_replace("[[:space:]]+"," ",trim(strtolower($qstr)));
      
      $qstr = formatQueryString($qstr);
      
      $sql = "SELECT b.employee_id,b.employee_ext_id,a.person_nm,a.person_id, MATCH (a.person_nm) AGAINST ('$qstr' IN BOOLEAN MODE) as score"
           . " FROM ".XOCP_PREFIX."persons a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(person_id)"
           . " WHERE MATCH (a.person_nm) AGAINST ('$qstr' IN BOOLEAN MODE)"
           . " AND b.person_id IS NOT NULL"
           . " AND a.status_cd = 'normal'"
           . " GROUP BY a.person_id"
           . " ORDER BY score DESC";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         $no = 0;
         while(list($employee_id,$employee_ext_id,$employee_nm,$person_id)=$db->fetchRow($result)) {
            if($no >= 1000) break;
            $ret[] = array("$employee_nm ($employee_ext_id)",$person_id);
            $no++;
         }
      }
      if(count($ret)>0) {
         return $ret;
      } else {
         return "EMPTY";
      }
   }
   
   function app_createEvent($args) {
      $db=&Database::getInstance();
      $method_id = $args[0];
      
      $sql = "SELECT method_nm,institute_id,cost_estimate,method_t FROM ".XOCP_PREFIX."idp_development_method"
           . " WHERE method_id = '$method_id'";
      $result = $db->query($sql);
      list($method_nm,$institute_id,$cost_estimate,$method_t)=$db->fetchRow($result);
      
      $sql = "SELECT method_type FROM ".XOCP_PREFIX."idp_development_method_type WHERE method_t = '$method_t'";
      $resultx = $db->query($sql);
      list($method_type)=$db->fetchRow($resultx);
      
      $sql = "INSERT INTO ".XOCP_PREFIX."idp_event (method_id,event_title,institute_id,cost_budget,start_dttm,stop_dttm) VALUES ('$method_id','$method_nm','$institute_id','$cost_estimate',now(),now())";
      $result = $db->query($sql);
      $event_id = $db->getInsertId();
      
      $event_title = $method_nm;
      
      $ret = "<table border='0' class='ilist' style='width:100%;'>"
           . "<colgroup><col/><col/><col width='150'/></colgroup>"
           . "<tbody><tr>"
           . "<td><span onclick='edit_event(\"$event_id\",this,event);' class='xlnk'>".htmlentities(stripslashes($event_title))."</span></td>"
           . "<td style='text-align:right;'>$method_type</td>"
           . "<td style='text-align:right;'>".sql2ind($start_dttm,"date")."</td>"
           . "</tr></tbody></table>";
      return array($event_id,$ret);
   }
   
   function app_selectMethod($args) {
      $db=&Database::getInstance();
      $method_t = $args[0];
      if($method_t=="ALL") {
         $sql = "SELECT method_t,method_type FROM ".XOCP_PREFIX."idp_development_method_type";
         $result = $db->query($sql);
         $mlist = "";
         if($db->getRowsNum($result)>0) {
            while(list($method_tx,$method_typex)=$db->fetchRow($result)) {
               $mlist .= "<div style='padding:3px;border-bottom:1px solid #ddd;cursor:default;' class='cb'>"
                  . "<table style='width:100%;border-spacing:0px;'><tbody><tr>"
                  . "<td style='text-align:left;padding-left:5px;'><span class='xlnk' onclick='select_method(\"$method_tx\",this,event);'>$method_typex</span></td>"
                  . "</tr></tbody></table></div>";
            
            }
         }
         
         $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>Add Event</div>"
              . "<div id='apformeditor' style='padding:5px;'>"
                  . $mlist
              . "</div>"
              . "<div id='apformbtn' style='text-align:center;padding:10px;'>"
                  . "<input type='button' value='"._CANCEL."' style='' onclick='addeventbox.fade();'/>&nbsp;&nbsp;&nbsp;&nbsp;"
              . "</div>";
         return array($ret,390);
      } else {
         $sql = "SELECT method_type FROM ".XOCP_PREFIX."idp_development_method_type WHERE method_t = '$method_t'";
         $result = $db->query($sql);
         list($method_type)=$db->fetchRow($result);
         $sql = "SELECT a.method_id,a.method_nm,b.institute_nm"
              . " FROM ".XOCP_PREFIX."idp_development_method a"
              . " LEFT JOIN ".XOCP_PREFIX."idp_institutes b USING(institute_id)"
              . " WHERE a.method_t = '$method_t'"
              . " ORDER BY a.method_nm";
         $result = $db->query($sql);
         if($db->getRowsNum($result)>0) {
            $mlist = "";
            $cnt = 0;
            while(list($method_id,$method_nm,$institute_nm)=$db->fetchRow($result)) {
               $mlist .= "<div style='padding:3px;border-bottom:1px solid #ddd;cursor:default;' class='cb'>"
                  . "<table style='width:100%;border-spacing:0px;'><tbody><tr>"
                  . "<td style='text-align:left;padding-left:5px;'><span style='font-weight:bold;'>$method_nm</span>"
                  . "<br/><span style='font-style:italic;'>$institute_nm&nbsp;</span></td>"
                  . "<td style='text-align:right;color:black;font-weight:normal;' id='tdc_${method_id}'>"
                     . "<input style='width:80px;' type='button' value='Create' onclick='do_create_event(\"$method_id\",this,event);'/>"
                  . "</td>"
                  . "</tr></tbody></table></div>";
               $cnt++;
            }
            $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>Add Event -  $method_type</div>"
                 . "<div id='apformeditor' style='padding:5px;overflow:auto;max-height:300px;'>"
                 . $mlist
                 . "</div>"
                 . "<div id='apformbtn' style='text-align:center;padding:10px;'>"
                     . "<input type='button' value='Back' style='' onclick='select_method(\"ALL\",this,event);'/>&nbsp;&nbsp;&nbsp;&nbsp;"
                     . "<input type='button' value='"._CANCEL."' style='' onclick='addeventbox.fade();'/>"
                 . "</div>";
            return array($ret,min(420,130+($cnt*40)));
         
         } else {
            $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>Add Event -  $method_type</div>"
                 . "<div id='apformeditor' style='text-align:center;padding:5px;'>"
                     . "Template is empty."
                 . "</div>"
                 . "<div id='apformbtn' style='text-align:center;padding:10px;'>"
                     . "<input type='button' value='Back' style='' onclick='select_method(\"ALL\",this,event);'/>&nbsp;&nbsp;&nbsp;&nbsp;"
                     . "<input type='button' value='"._CANCEL."' style='' onclick='addeventbox.fade();'/>"
                 . "</div>";
            return array($ret,150);
         
         }
      }
   }
   
   
   function app_saveEvent($args) {
      $db=&Database::getInstance();
      $method_t = $_SESSION["hris_method_t"];
      $event_id = $args[0];
      $vars = _parseForm($args[1]);
      
      $event_title = $vars["event_title"];
      $event_description = addslashes(trim($vars["event_description"]));
      $start_dttm = $vars["start_dttm"];
      $stop_dttm = $vars["stop_dttm"];
      $cost_budget = _bctrim(bcadd($vars["cost_budget"],0));
      $registration_t = $vars["registration_t"];
      
      $sql = "UPDATE ".XOCP_PREFIX."idp_event SET "
           . "event_title = '$event_title',"
           . "event_description = '$event_description',"
           . "start_dttm = '$start_dttm',"
           . "stop_dttm = '$stop_dttm',"
           . "registration_t = '$registration_t',"
           . "cost_budget = '$cost_budget'"
           . " WHERE event_id = '$event_id'";
      $db->query($sql);
      
      $sql = "SELECT c.method_type FROM ".XOCP_PREFIX."idp_event a"
           . " LEFT JOIN ".XOCP_PREFIX."idp_development_method b USING(method_id)"
           . " LEFT JOIN ".XOCP_PREFIX."idp_development_method_type c USING(method_t)"
           . " WHERE a.event_id = '$event_id'";
      $result = $db->query($sql);
      list($method_type)=$db->fetchRow($result);
      
      $ret = "<table border='0' class='ilist' style='width:100%;'>"
           . "<colgroup><col/><col/><col width='150'/></colgroup>"
           . "<tbody><tr>"
           . "<td><span onclick='edit_event(\"$event_id\",this,event);' class='xlnk'>".htmlentities(stripslashes($event_title))."</span></td>"
           . "<td style='text-align:right;'>$method_type</td>"
           . "<td style='text-align:right;'>".sql2ind($start_dttm,"date")."</td>"
           . "</tr></tbody></table>";
      
      return array($event_id,"tdclass_${event_id}",$this->app_editEvent(array($event_id)),$ret);
   }
   
   function app_editEvent($args) {
      $db=&Database::getInstance();
      $event_id = $args[0];
      
      $sql = "SELECT a.employee_id,a.status_cd,a.email_notify_ind,b.employee_ext_id,c.person_nm,a.employee_confirm_dttm,a.last_email_dttm"
           . " FROM ".XOCP_PREFIX."idp_event_registration a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " WHERE a.event_id = '$event_id'"
           . " ORDER BY c.person_nm";
      $result = $db->query($sql);
      $ret = "<table class='xxlist' style='width:100%;' align='center'><thead><tr><td>"
           . "<table style='width:100%;border-spacing:0px;'><tbody><tr><td>"
           . "&nbsp;"
           . "</td><td style='text-align:right;'>Search Employee : "
           . "<input type='text' style='width:150px;' id='qemp'/>"
           . "&nbsp;<input type='button' value='Import from IDP Request' onclick='import_from_request(\"$event_id\",this,event);'/>"
           . "</td></tr></tbody></table>"
           . "</td></tr>"
           . "<tr><td>"
           
              . "<table border='0' class='ilist' style='width:100%;font-weight:bold;'>"
              . "<colgroup><col width='40'/><col width='80'/><col/><col width='150'/><col width=='150'/></colgroup>"
              . "<tbody><tr>"
              . "<td style='text-align:center;'><input title='Click to select' type='checkbox' onclick='select_emp(this,event);'/></td>"
              . "<td style='text-align:left;'>Emp. ID</td>"
              . "<td>Employee Name</td>"
              . "<td style='text-align:center;'>Email Sent</td>"
              . "<td style='text-align:center;'>Status</td>"
              . "</tr></tbody></table>"
           
           . "</td></tr>"
           . "</thead><tbody id='tbemplist'>";
      
      if($db->getRowsNum($result)>0) {
         while(list($employee_id,$status_cd,$email_notify_ind,$nip,$employee_nm,$employee_confirm_dttm,$last_email_dttm)=$db->fetchRow($result)) {
            switch($status_cd) {
               case "invited":
                  $status_cd_txt = "Invited";
                  $confirm_dt_txt = sql2ind($last_email_dttm,"date");
                  break;
               case "self_registered":
                  $status_cd_txt = "Self Registration";
                  $confirm_dt_txt = sql2ind($last_email_dttm,"date");
                  break;
               case "in":
                  $status_cd_txt = "In";
                  $confirm_dt_txt = sql2ind($last_email_dttm,"date");
                  break;
               case "out":
                  $status_cd_txt = "Out";
                  $confirm_dt_txt = sql2ind($last_email_dttm,"date");
                  break;
               default:
                  $status_cd_txt = "";
                  $confirm_dt_txt = "-";
                  break;
            }
            
            $ret .= "<tr><td id='tdemp_${event_id}_${employee_id}'>"
                  . "<table border='0' class='ilist' style='width:100%;'>"
                  . "<colgroup><col width='40'/><col width='80'/><col/><col width='150'/><col width=='150'/></colgroup>"
                  . "<tbody><tr>"
                  . "<td style='text-align:center;'><input type='checkbox' name='ce_${employee_id}' value='$employee_id' id='ckbemp_${event_id}_${employee_id}'/></td>"
                  . "<td style='text-align:left;'>$nip</td>"
                  . "<td><span onclick='edit_reg(\"$event_id\",\"$employee_id\",this,event);' class='xlnk'>".htmlentities(stripslashes($employee_nm))."</span></td>"
                  . "<td style='text-align:center;'>$confirm_dt_txt</td>"
                  . "<td style='text-align:center;'>$status_cd_txt</td>"
                  . "</tr></tbody></table>"
                  . "</td></tr>";
         
         }
         $ret .= "<tr><td id='tdempty' style='text-align:center;font-style:italic;display:none;'>"._EMPTY."</td></tr>";
      } else {
         $ret .= "<tr><td id='tdempty' style='text-align:center;font-style:italic;'>"._EMPTY."</td></tr>";
      }
      
      $ret .= "</tbody></table>"
            . "<div style='text-align:right;padding:5px;background-color:#eee;border:1px solid #ccc;border-top:0px;'>"
            . "&nbsp;<input type='button' value='E-Mail Selected' onclick='send_email_notification(\"$event_id\",\"A\",this,event);'/>"
            . "&nbsp;<input type='button' value='Delete Selected' onclick='delete_selected(\"$event_id\",\"A\",this,event);'/>"
            . "</div>";
      return $ret;
   }
   
   function app_Delete($args) {
      $db=&Database::getInstance();
      $event_id = $args[0];
      $user_id = getUserID();
      $sql = "UPDATE ".XOCP_PREFIX."idp_event SET status_cd = 'nullified', nullified_dttm = now(), nullified_user_id = '$user_id' WHERE event_id = '$event_id'";
      $db->query($sql);
   }
   
}

} /// HRIS_IDPDEVLIBAJAX_DEFINED
?>