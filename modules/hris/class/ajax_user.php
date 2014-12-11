<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_employee.php                     //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-11-06                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_CLASS_AJAXUSER_DEFINED') ) {
   define('HRIS_CLASS_AJAXUSER_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");

class _hris_class_UserAjax extends AjaxListener {
   
   function _hris_class_UserAjax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/hris/class/ajax_user.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_searchEmployee","app_searchJob",
                            "app_save","app_deleteEmployee","app_editJob","app_saveJob",
                            "app_getJobList","app_addJob","app_getImportJobList",
                            "app_deleteJob","app_importJob","app_savePerson",
                            "app_getLogin","app_assignLogin","app_unlinkLogin",
                            "app_resetPassword","app_getGroupList","app_addGroup",
                            "app_deleteGroup","app_invertStatus","app_searchJob",
                            "app_confirmAddJob","app_emailPassword","app_addRole",
                            "app_deleteRole","app_getRoleList");
   }
   
   function app_getRoleList($args) {
      $db=&Database::getInstance();
      $person_id = $_SESSION["hris_employee_person_id"];
      $sql = "SELECT c.role_id,c.role_nm from ".XOCP_PREFIX."users a"
        . " LEFT JOIN ".XOCP_PREFIX."user_role b ON b.user_id = a.user_id AND b.status_cd = 'normal'"
        . " LEFT JOIN ".XOCP_PREFIX."role c ON c.role_id = b.role_id"
        . " WHERE a.person_id = '$person_id'"
        . " ORDER BY c.role_nm";
      $result = $db->query($sql);
      $c = $db->getRowsNum($result);
      $rolearray = array();
      if($c > 0) {
         while(list($role_id,$role_nm) = $db->fetchRow($result)) {
            $rolearray[$role_id] = $role_nm;
         }
      }
      $sql = "SELECT role_id,role_nm FROM ".XOCP_PREFIX."role WHERE status_cd = 'normal'"
           . " ORDER BY role_nm";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         $ret = array();
         while(list($role_idx,$role_nmx)=$db->fetchRow($result)) {
            if(!isset($rolearray[$role_idx])) {
               $ret[] = array($role_nmx,$role_idx);
            }
         }
         if(count($ret)==0) {
            return "EMPTY";
         }
      } else {
         $ret = "EMPTY";
      }
      return $ret;
   }
   
   
   function app_emailPassword($args) {
      $db=&Database::getInstance();
      $person_id = $_SESSION["hris_employee_person_id"];
      $pass = substr(md5(uniqid(rand())), 2, 5);
      $sql = "SELECT a.user_id,a.user_nm,a.pwd1,b.person_nm,c.employee_id FROM ".XOCP_PREFIX."users a"
           . " LEFT JOIN ".XOCP_PREFIX."persons b USING(person_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee c ON c.person_id = b.person_id"
           . " WHERE a.person_id = '$person_id'";
      $result = $db->query($sql);
      list($user_idx,$user_nm,$pwd1,$person_nm,$employee_id)=$db->fetchRow($result);
      
                  $sql = "SELECT b.email,b.smtp_location FROM ".XOCP_PREFIX."users a"
                       . " LEFT JOIN ".XOCP_PREFIX."persons b USING(person_id)"
                       . " WHERE a.user_id = '$user_idx'";
                  $result = $db->query($sql);
                  _debuglog($sql);
                  list($email,$smtp)=$db->fetchRow($result);
                  
                  list($user_mail,$domain_mail)=explode("@",$email);
                  $email = "${user_mail}@${smtp}";
                  $subject = "HRIS - Your Password";
                  
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
<tr><td style='border-right:1px solid #bbb;'><img src='http://146.67.1.47/hris/images/logo.gif'/></td><td><img src='http://146.67.1.47/hris/images/ocd_logo_20100514b.png'/></td></tr>
<tr><td colspan='2' style='background-color:#5666b3;'>&nbsp;</td></tr>
<tr><td colspan='2'>

<pre width='400' style='padding:15px;padding-top:0px;width:400px;'>
Dear $person_nm:

This is your login information:
Username: $user_nm
Password: $pwd1

You can login to system by opening this address http://146.67.1.47/hris using Mozilla Firefox.

Thank you.

HRIS
</pre>

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
                  $headers["Subject"] = "[HRIS] Your Login Information";
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
                  
                  // Create the mail object using the Mail::factory method
                  
                  ob_start();
                  $mail_object =& Mail::factory('smtp', $params);
                  $mail_object->send($recipient, $headers, $body);
                  $str = ob_get_contents();
                  ob_end_clean();
                  
                  
                  ///////////////////////////////////////////////////////////////////////////////////////
                  ///////////////////////////////////////////////////////////////////////////////////////
      
      
      return "OK";
      
   }
   
   function app_confirmAddJob($args) {
      $db=&Database::getInstance();
      $job_id = $args[0];

      $ret = "<div style='padding:4px;' id='dvjob_${job_id}'><form id='frmjob'>"
           . "<div style='padding:4px;font-weight:bold;color:red;text-align:center;'>Confirm Job Assignment</div>"
           . "<table style='width:100%;' class='xxfrm'>"
           . "<tbody>";
         
      $sql = "SELECT b.job_nm,b.job_cd,c.job_class_nm,d.workarea_nm,e.org_nm,f.org_class_nm"
           . " FROM ".XOCP_PREFIX."jobs b"
           . " LEFT JOIN ".XOCP_PREFIX."job_class c USING(job_class_id)"
           . " LEFT JOIN ".XOCP_PREFIX."workarea d ON d.workarea_id = b.workarea_id"
           . " LEFT JOIN ".XOCP_PREFIX."orgs e ON e.org_id = b.org_id"
           . " LEFT JOIN ".XOCP_PREFIX."org_class f ON f.org_class_id = e.org_class_id"
           . " WHERE b.job_id = '$job_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($job_nm,$job_cd,$job_class_nm,$workarea_nm,
              $org_nm,$org_class_nm)=$db->fetchRow($result);
         $ret .= "<tr><td>Job Title</td><td>$job_nm</td></tr>"
               . "<tr><td>Job Code</td><td>$job_cd</td></tr>"
               . "<tr><td>Position Level</td><td>$job_class_nm</td></tr>"
               . "<tr><td>Work Area</td><td>$workarea_nm</td></tr>"
               . "<tr><td>Organization</td><td>$org_nm [$org_class_nm]</td></tr>";
      }
         
      $ret .= "</tbody>"
            . "</table>"
            . "<div style='text-align:center;padding:4px;'>"
            . "<div style='text-align:center;color:red;font-weight:bold;padding:4px;'>You are about to assign the job to this person?</div>"
            . "<input type='button' value='Confirm' onclick='confirm_add_job(\"$job_id\",this,event);' id='btn'/>&nbsp;&nbsp;"
            . "<input type='button' value='"._CANCEL."' id='btn_cancel' onclick='cancel_add_job(\"$job_id\",this,event);'/>"
            . "</div>"
            . "</form></div>";
      
      return array($job_id,$ret);
   }
   
   function app_searchJob($args) {
      $db=&Database::getInstance();
      $qstr = trim($args[0]);
      $sql = "SELECT job_nm,job_id,job_cd FROM ".XOCP_PREFIX."jobs"
           . " WHERE job_cd LIKE '$qstr%'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($job_nm,$job_id,$job_cd)=$db->fetchRow($result);
         $ret[] = array("$job_nm [$job_cd]",$job_id);
      }

      $qstr = formatQueryString($qstr);

      $sql = "SELECT a.job_id, a.job_nm, a.job_cd, MATCH (a.job_nm) AGAINST ('$qstr' IN BOOLEAN MODE) as score"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " WHERE MATCH (a.job_nm) AGAINST ('$qstr' IN BOOLEAN MODE)"
           . " ORDER BY score DESC";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         $no = 0;
         while(list($job_id,$job_nm,$job_cd)=$db->fetchRow($result)) {
            if($no >= 1000) break;
            $ret[] = array("$job_nm [$job_cd]",$job_id);
            $no++;
         }
      }
      
      if(count($ret)>0) {
         return $ret;
      } else {
         return "EMPTY";
      }
      
   }
   
   function app_invertStatus($args) {
      $db=&Database::getInstance();
      $person_id = $_SESSION["hris_employee_person_id"];
      $sql = "SELECT user_id,status_cd FROM ".XOCP_PREFIX."users WHERE person_id = '$person_id'";
      $result = $db->query($sql);
      list($user_id,$status_cd)=$db->fetchRow($result);
      if($status_cd=="active") {
         $sql = "UPDATE ".XOCP_PREFIX."users SET status_cd = 'inactive'"
              . " WHERE user_id = '$user_id'";
         $new_status_txt = "Inactive";
         $new_status_btn = "Activate";
      } else {
         $sql = "UPDATE ".XOCP_PREFIX."users SET status_cd = 'active'"
              . " WHERE user_id = '$user_id'";
         $new_status_txt = "Active";
         $new_status_btn = "De-activate";
      }
      $db->query($sql);
      return array($new_status_txt,$new_status_btn);
   }
   
   function app_deleteGroup($args) {
      $db=&Database::getInstance();
      $arr = explode("|",urldecode($args[0]));
      $person_id = $_SESSION["hris_employee_person_id"];
      $sql = "SELECT user_id FROM ".XOCP_PREFIX."users WHERE person_id = '$person_id'";
      $result = $db->query($sql);
      list($user_id)=$db->fetchRow($result);
      $ret = array();
      foreach($arr as $k=>$v) {
         $pgroup_id = (int)$v;
         $sql = "DELETE FROM ".XOCP_PREFIX."user_pgroup"
              . " WHERE user_id = '$user_id'"
              . " AND pgroup_id = '$pgroup_id'";
         $db->query($sql);
         $ret[] = $pgroup_id;
      }
      return $ret;
   }
   
   function app_addGroup($args) {
      $db=&Database::getInstance();
      $pgroup_id = (int)$args[0];
      $person_id = $_SESSION["hris_employee_person_id"];
      
      $sql = "SELECT user_id FROM ".XOCP_PREFIX."users WHERE person_id = '$person_id'";
      $result = $db->query($sql);
      list($user_id)=$db->fetchRow($result);
      
      $sql = "SELECT pgroup_cd FROM ".XOCP_PREFIX."pgroups WHERE pgroup_id = '$pgroup_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($pgroup_cd)=$db->fetchRow($result);
         $sql = "INSERT INTO ".XOCP_PREFIX."user_pgroup (user_id,pgroup_id)"
              . " VALUES ('$user_id','$pgroup_id')";
         $db->query($sql);
         if($db->errno==0) {
            return array($pgroup_id,$pgroup_cd);
         } else {
            return "FAIL";
         }
      } else {
         return "FAIL";
      }
   }
   
   function app_getGroupList($args) {
      $db=&Database::getInstance();
      $person_id = $_SESSION["hris_employee_person_id"];
      $sql = "SELECT c.pgroup_id,c.pgroup_cd from ".XOCP_PREFIX."users a"
        . " LEFT JOIN ".XOCP_PREFIX."user_pgroup b USING (user_id)"
        . " LEFT JOIN ".XOCP_PREFIX."pgroups c ON c.pgroup_id = b.pgroup_id"
        . " WHERE a.person_id = '$person_id'"
        . " ORDER BY c.pgroup_cd";
      $result = $db->query($sql);
      $c = $db->getRowsNum($result);
      $grouparray = array();
      if($c > 0) {
         while(list($pgroup_id,$pgroup_cd) = $db->fetchRow($result)) {
            $grouparray[$pgroup_id] = $pgroup_cd;
         }
      }
      $sql = "SELECT pgroup_id,pgroup_cd FROM ".XOCP_PREFIX."pgroups"
           . " ORDER BY pgroup_cd";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         $ret = array();
         while(list($pgroup_idx,$pgroup_cdx)=$db->fetchRow($result)) {
            if(!isset($grouparray[$pgroup_idx])) {
               $ret[] = array($pgroup_cdx,$pgroup_idx);
            }
         }
      } else {
         $ret = "EMPTY";
      }
      return $ret;
   }
   
   function app_resetPassword($args) {
      $db=&Database::getInstance();
      $person_id = $_SESSION["hris_employee_person_id"];
      $pass = substr(md5(uniqid(rand())), 2, 5);
      $sql = "UPDATE ".XOCP_PREFIX."users SET pwd0 = md5('$pass'), pwd1 = '$pass'"
           . " WHERE person_id = '$person_id'";
      $db->query($sql);
      $rand = uniqid('t');
      return $pass;//"<img src='".XOCP_SERVER_SUBDIR."/modules/hris/include/img.php?rnd=$rand' width='50' height='20'/>";
   }
   
   function app_unlinkLogin($args) {
      $db=&Database::getInstance();
      $person_id = $_SESSION["hris_employee_person_id"];
      $sql = "UPDATE ".XOCP_PREFIX."users SET person_id = '0', status_cd = 'inactive'"
           . " WHERE person_id = '$person_id'";
      $db->query($sql);
      return _hris_class_UserAjax::editLogin($person_id);
   }
   
   function app_assignLogin($args) {
      $db=&Database::getInstance();
      $user_id = $args[0];
      $user_nm = $args[1];
      $person_id = $_SESSION["hris_employee_person_id"];
      $sql = "SELECT person_id FROM ".XOCP_PREFIX."users WHERE user_nm = '$user_nm' AND person_id != '0'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($person_idx)=$db->fetchRow($result);
         return "ID_TAKEN";
      }
      if($user_id==_HRIS_EMPLOYEE_DUMMY_ID) {
         $sql = "INSERT INTO ".XOCP_PREFIX."users (person_id,user_nm)"
              . " VALUES ('$person_id','$user_nm')";
         $db->query($sql);
         $user_id = $db->getInsertId();
      } else {
         $user_id = (int)$user_id;
         $sql = "UPDATE ".XOCP_PREFIX."users SET person_id = '0'"
              . " WHERE person_id = '$person_id'";
         $db->query($sql);
         $sql = "UPDATE ".XOCP_PREFIX."users SET person_id = '$person_id'"
              . " WHERE user_id = '$user_id'";
         $db->query($sql);
      }
      return _hris_class_UserAjax::editLogin($person_id);
   }
   
   function app_getLogin($args) {
      $db=&Database::getInstance();
      $qstr = $args[0];
      $qstr = ereg_replace("[[:space:]]+"," ",trim(strtolower($qstr)));
      
      $sql = "SELECT user_nm,user_id,person_id"
           . " FROM ".XOCP_PREFIX."users"
           . " WHERE user_nm LIKE '$qstr%'"
           . " AND person_id = '0'"
           . " ORDER BY user_nm";
      $result = $db->query($sql);
      $ret = array();
      if($db->getRowsNum($result)>0) {
         while(list($user_nm,$user_id,$person_id)=$db->fetchRow($result)) {
            if($person_id>0) {
               $user_id = _HRIS_EMPLOYEE_TAKEN_ID;
            }
            $ret[$user_nm] = array($user_nm,$user_id);
         }
      }
      if(!isset($ret[$qstr])) {
         sort($ret);
         array_unshift($ret,array($qstr,_HRIS_EMPLOYEE_DUMMY_ID));
      } else {
         sort($ret);
      }
      return $ret;
   }
   
   function app_deleteEmployee($args) {
      $db=&Database::getInstance();
      $person_id = $_SESSION["hris_employee_person_id"];
      $employee_id = $_SESSION["hris_employee_id"];
      $sql = "SELECT obj_id,job_id FROM ".XOCP_PREFIX."hris_job_plan"
           . " WHERE employee_id = '$employee_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($obj_id,$job_id)=$db->fetchRow($result)) {
            // create deleted job table
            //$sql = "DELETE FROM ".XOCP_PREFIX."hris_obj WHERE obj_id = '$obj_id'";
            //$db->query($sql);
         }
      }
      $sql = "DELETE FROM ".XOCP_PREFIX."hris_job_plan WHERE employee_id = '$employee_id'";
      $db->query($sql);
      $sql = "DELETE FROM ".XOCP_PREFIX."employee WHERE employee_id = '$employee_id'";
      $db->query($sql);
      $sql = "DELETE FROM ".XOCP_PREFIX."persons WHERE person_id = '$person_id'";
      $db->query($sql);
      $sql = "UPDATE ".XOCP_PREFIX."users SET person_id = '0', status_cd = 'inactive'"
           . " WHERE person_id = '$person_id'";
      $db->query($sql);
   }
   
   function app_savePerson($args) {
      $db=&Database::getInstance();
      $arr = parseForm($args[0]);
      
      $telecom = trim($arr["telephone"])."|".trim($arr["fax"])."|".trim($arr["hp"])."|".trim($arr["email"]);
      if($args[1]=="new") {
         $sql = "INSERT INTO ".XOCP_PREFIX."persons (person_nm,status_cd) VALUES ('".$arr["person_nm"]."','active')";
         $result = $db->query($sql);
         $person_id = $db->getInsertId();
         $sql = "INSERT INTO ".XOCP_PREFIX."employee (status_cd,person_id)"
              . " VALUES ('active','$person_id')";
         $db->query($sql);
         $employee_id = $db->getInsertId();
         $_SESSION["hris_employee_id"] = $employee_id;
         $_SESSION["hris_employee_person_id"] = $person_id;
      } else {
         $employee_id = $_SESSION["hris_employee_id"];
         $person_id = $_SESSION["hris_employee_person_id"];
      }
      $sql = "UPDATE ".XOCP_PREFIX."persons SET "
           . "person_nm = '".$arr["person_nm"]."',"
           . "ext_id = '".$arr["ext_id"]."',"
           . "birth_dttm = '".$arr["birth_dttm"]."',"
           . "birthplace = '".$arr["birthplace"]."',"
           . "adm_gender_cd = '".$arr["adm_gender_cd"]."',"
           . "addr_txt = '".$arr["addr_txt"]."',"
           . "regional_cd = '".$arr["regional_cd"]."',"
           . "zip_cd = '".$arr["zip_cd"]."',"
           . "cell_phone = '".$arr["hp"]."',"
           . "home_phone = '".$arr["telephone"]."',"
           . "fax = '".$arr["fax"]."',"
           . "blood_type = '".$arr["blood_t"]."',"
           . "marital_st = '".$arr["marital_st"]."',"
           . "educlvl_id = '".$arr["education"]."',"
           . "status_cd = 'active'"
           . " WHERE person_id = '$person_id'";
      $db->query($sql);
      $sql = "UPDATE ".XOCP_PREFIX."employee SET "
           . "employee_ext_id = '".$arr["employee_ext_id"]."',"
           . "entrance_dttm = '".$arr["entrance_dttm"]."',"
           . "status_cd = 'active'"
           . " WHERE person_id = '$person_id'";
      $db->query($sql);
      /*
      $sql = "SELECT obj_id,job_id FROM ".XOCP_PREFIX."hris_job_plan"
           . " WHERE employee_id = '$employee_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($obj_id,$job_id)=$db->fetchRow($result)) {
            $obj_nm = $arr["person_nm"];
            $sql = "UPDATE ".XOCP_PREFIX."hris_obj SET obj_nm = '$obj_nm', description = '$obj_nm' WHERE obj_id = '$obj_id'";
            $db->query($sql);
         }
      }
      */


      return $arr["person_id"];
   }
   
   function app_importJob($args) {
      $db=&Database::getInstance();
      $employee_idx = (int)$args[0];
      $employee_id = $_SESSION["hris_employee_id"];
      $sql = "SELECT a.job_id,a.payplan_id,a.tariff,b.concept_nm FROM ".XOCP_PREFIX."hris_job_plan a"
           . " LEFT JOIN ".XOCP_PREFIX."hris_concepts b ON b.concept_id = a.job_id"
           . " WHERE a.employee_id = '$employee_idx'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         $ret = array();
         while(list($job_id,$payplan_id,$tariff,$job_nm)=$db->fetchRow($result)) {
            $obj_id = "${job_id}.${employee_id}.${payplan_id}";
            $sql = "REPLACE INTO ".XOCP_PREFIX."hris_job_plan (employee_id,job_id,payplan_id,tariff,obj_id)"
                 . " VALUES ('$employee_id','$job_id','$payplan_id','$tariff','$obj_id')";
            $db->query($sql);
            $sql = "REPLACE INTO ".XOCP_PREFIX."hris_obj (obj_id,obj_nm,unit_cost,concept_id,description)"
                    . " VALUES ('$obj_id','".$_SESSION["hris_employee_person_nm"]."','0','$job_id',"
                    . "'".$_SESSION["hris_employee_person_nm"]."')";
            $db->query($sql);
            $ret[$job_id] = array($job_id,$job_nm);
         }
         sort($ret);
         return $ret;
      } else {
         return "FAIL";
      }
   }
   
   function app_deleteJob($args) {
      $db=&Database::getInstance();
      $job_id = $args[0];
      $employee_id = $_SESSION["hris_employee_id"];
      $sql = "DELETE FROM ".XOCP_PREFIX."employee_job"
           . " WHERE employee_id = '$employee_id'"
           . " AND job_id = '$job_id'";
      $db->query($sql);
   }
   
   function app_addJob($args) {
      $db=&Database::getInstance();
      $job_id = $args[0];
      $employee_id = $_SESSION["hris_employee_id"];
      $sql = "SELECT job_nm,job_cd FROM ".XOCP_PREFIX."jobs WHERE job_id = '$job_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($job_nm,$job_cd)=$db->fetchRow($result);
         $sql = "INSERT INTO ".XOCP_PREFIX."employee_job (job_id,employee_id,start_dttm,stop_dttm)"
              . " VALUES ('$job_id','$employee_id',now(),now())";
         $db->query($sql);
         if($db->errno()==0) {
            $ret = "<span>$job_cd</span> "
                  . "<span onclick='edit_job(\"$job_id\",this,event);' class='xlnk'>$job_nm</span>"
                  . "<div id='jobeditor'>".$this->app_editJob(array($job_id))."</div>";
            return $ret;
         } else {
            return "FAIL";
         }
      } else {
         return "FAIL";
      }
   }
   
   function app_getImportJobList($args) {
      $db=&Database::getInstance();
      $employee_id = $_SESSION["hris_employee_id"];
      $sql = "SELECT b.employee_id,d.person_nm"
           . " FROM ".XOCP_PREFIX."hris_job_plan b"
           . " LEFT JOIN ".XOCP_PREFIX."hris_employee c USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons d USING(person_id)"
           . " WHERE b.employee_id != '$employee_id'"
           . " AND b.employee_id != 0"
           . " GROUP BY b.employee_id"
           . " ORDER BY d.person_nm";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         $ret = array();
         while(list($employee_idx,$employee_nmx)=$db->fetchRow($result)) {
            $ret[] = array($employee_nmx,$employee_idx);
         }
         return $ret;
      } else {
         return "EMPTY";
      }
   }
   
   function app_getJobList($args) {
      $db=&Database::getInstance();
      $employee_id = $_SESSION["hris_employee_id"];
      $sql = "SELECT job_id FROM ".XOCP_PREFIX."hris_job_plan"
           . " WHERE employee_id = '$employee_id'"
           . " GROUP BY job_id";
      $result = $db->query($sql);
      $jobs = array();
      if($db->getRowsNum($result)>0) {
         while(list($job_id)=$db->fetchRow($result)) {
            $jobs[$job_id] = $job_id;
         }
      }
      $sql = "SELECT a.concept_id,a.concept_nm"
           . " FROM ".XOCP_PREFIX."hris_con_class b"
           . " LEFT JOIN ".XOCP_PREFIX."hris_concepts a USING(concept_id)"
           . " WHERE b.con_class_id = 'ROLE'"
           . " GROUP BY a.concept_id";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         $ret = array();
         while(list($concept_id,$concept_nm)=$db->fetchRow($result)) {
            if(!isset($jobs[$concept_id])) {
               $ret[] = array($concept_nm,$concept_id);
            }
         }
         return $ret;
      } else {
         return "EMPTY";
      }
   }
   
   function app_saveJob($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $employee_id = $_SESSION["hris_employee_id"];
      $job_id = $args[0];
      
      $arr = parseForm($args[1]);
      
      $location_id = $arr["slocation"];
      $gradeval = $arr["gradeval"];
      $start = $arr["hstartjob"];
      $stop = $arr["hstopjob"];
      $sql = "UPDATE ".XOCP_PREFIX."employee_job SET "
           . "location_id = '$location_id',"
           . "gradeval = '$gradeval',"
           . "start_dttm = '$start',"
           . "stop_dttm = '$stop'"
           . " WHERE employee_id = '$employee_id'"
           . " AND job_id = '$job_id'";
           
      $db->query($sql);
      
      return "OK";
   }
   
   function app_editJob($args) {
      $db=&Database::getInstance();
      $job_id = $args[0];
      $employee_id = $_SESSION["hris_employee_id"];

      $ret = "<div style='padding:4px;' id='dvjob_${job_id}'><form id='frmjob'><table style='width:100%;' class='xxfrm'>"
           . "<tbody>";
         
      $sql = "SELECT b.job_nm,b.job_cd,a.location_id,"
           . "c.job_class_nm,d.workarea_nm,e.org_nm,f.org_class_nm,"
           . "a.gradeval,a.start_dttm,a.stop_dttm"
           . " FROM ".XOCP_PREFIX."employee_job a"
           . " LEFT JOIN ".XOCP_PREFIX."jobs b USING(job_id)"
           . " LEFT JOIN ".XOCP_PREFIX."job_class c USING(job_class_id)"
           . " LEFT JOIN ".XOCP_PREFIX."workarea d ON d.workarea_id = b.workarea_id"
           . " LEFT JOIN ".XOCP_PREFIX."orgs e ON e.org_id = b.org_id"
           . " LEFT JOIN ".XOCP_PREFIX."org_class f ON f.org_class_id = e.org_class_id"
           . " WHERE a.employee_id = '$employee_id'"
           . " AND a.job_id = '$job_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($job_nm,$job_cd,$location_id,$job_class_nm,$workarea_nm,
              $org_nm,$org_class_nm,$gradeval,$start_dttm,$stop_dttm)=$db->fetchRow($result);
         if($start_dttm=="0000-00-00 00:00:00") {
            $start_dttm = getSQLDate();
         }
         if($stop_dttm=="0000-00-00 00:00:00") {
            $stop_dttm = getSQLDate();
         }
         $sql = "SELECT location_id,location_cd,location_nm FROM ".XOCP_PREFIX."location ORDER BY location_cd";
         $result = $db->query($sql);
         $opt = "";
         if($db->getRowsNum($result)>0) {
            while(list($location_idx,$location_cdx,$location_nmx)=$db->fetchRow($result)) {
               if($location_id==$location_idx) {
                  $sel = "selected='1'";
               } else {
                  $sel = "";
               }
               $opt .= "<option value='$location_idx' $sel>$location_cdx $location_nmx</option>";
            }
         }
         $ret .= "<tr><td>Job Title</td><td>$job_nm</td></tr>"
               . "<tr><td>Job Code</td><td>$job_cd</td></tr>"
               . "<tr><td>Position Level</td><td>$job_class_nm</td></tr>"
               . "<tr><td>Grade</td><td><input type='text' style='width:30px;' value='$gradeval' id='gradeval' name='gradeval'/></td></tr>"
               . "<tr><td>Work Area</td><td>$workarea_nm</td></tr>"
               . "<tr><td>Organization</td><td>$org_nm [$org_class_nm]</td></tr>"
               . "<tr><td>Location</td><td><select name='slocation'>$opt</select></td></tr>"
               . "<tr><td>Start Datetime</td><td><span class='xlnk' id='startjob_txt' onclick='editstartjob(this,event);'>".sql2ind($start_dttm)."</span></td></tr>"
               . "<tr><td>Stop Datetime</td><td><span class='xlnk' id='stopjob_txt' onclick='editstopjob(this,event);'>".sql2ind($stop_dttm)."</span></td></tr>";
      }
         
      $ret .= "<tr><td colspan='2'>"
            . "<input type='hidden' name='hstartjob' id='hstartjob' value='$start_dttm'/>"
            . "<input type='hidden' name='hstopjob' id='hstopjob' value='$stop_dttm'/>"
            . "<input type='button' value='"._SAVE."' onclick='save_job(\"$job_id\",this,event);' id='btn'/>&nbsp;"
            . "<input type='button' value='Stop' id='btn_stopjob' onclick='stop_job(\"$job_id\",this,event);'/>&nbsp;&nbsp;"
            . "<input type='button' value='"._DELETE."' id='btn_delete' onclick='delete_job(\"$job_id\",this,event);'/>"
            . "</td></tr></tbody>"
            . "</table></form></div>";
      
      return $ret;
   }
   
   function app_searchEmployee($args) {
      $db=&Database::getInstance();
      $qstr = $args[0];
      
      $sql = "SELECT b.employee_id,b.employee_ext_id,a.person_nm,a.person_id"
           . " FROM ".XOCP_PREFIX."persons a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(person_id)"
           . " WHERE b.employee_ext_id LIKE '$qstr%'"
           . " AND b.person_id IS NOT NULL"
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

   function editLogin($person_id) {
      $db=&Database::getInstance();
      $sql = "SELECT a.user_id,a.user_nm,a.pwd0,a.pwd1,a.status_cd"
           . " FROM ".XOCP_PREFIX."users a"
           . " WHERE a.person_id = '$person_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($user_id,$user_nm,$pwd0,$pwd1,$status_cd) = $db->fetchRow($result);
         if($pwd0 == md5($pwd1)) {
            $reset = $pwd1;
            $rand = uniqid('t');
            //$reset = "<img src='".XOCP_SERVER_SUBDIR."/modules/hris/include/img.php?rnd=$rand' width='50' height='20'/>";
         } else {
            $reset = "-";
         }
         
         if($status_cd=="active") {
            $status_txt = "Active";
            $status_btn = "De-activate";
         } else if($status_cd=="inactive") {
            $status_txt = "Inactive";
            $status_btn = "Activate";
         } else {
            $status_txt = "Blocked";
            $status_btn = "Unblock";
         }
         
         $sql = "SELECT c.pgroup_id,c.pgroup_cd"
              . " FROM ".XOCP_PREFIX."user_pgroup b"
              . " LEFT JOIN ".XOCP_PREFIX."pgroups c ON c.pgroup_id = b.pgroup_id"
              . " WHERE b.user_id = '$user_id'"
              . " ORDER BY c.pgroup_cd";
         $result = $db->query($sql);
         $c = $db->getRowsNum($result);
         if($c > 0) {
            $groups = "";
            while(list($pgroup_id,$pgroup_cd) = $db->fetchRow($result)) {
               if($pgroup_id=="") continue;
               $groups .= "<div id='dvgrp_$pgroup_id'><input type='checkbox' id='grp_$pgroup_id' value='$pgroup_cd'/> <label for='grp_$pgroup_id'>$pgroup_cd</label></div>";
            }
            $groups .= "<div id='dvgrp_empty' style='display:none;'>No group assigned.</div>";
         } else {
            $groups = "<div id='dvgrp_empty'>No group assigned.</div>";
         }
         
         
         $sql = "SELECT a.role_id,b.role_nm"
              . " FROM ".XOCP_PREFIX."user_role a"
              . " LEFT JOIN ".XOCP_PREFIX."role b USING(role_id)"
              . " WHERE a.user_id = '$user_id'"
              . " AND a.status_cd = 'normal'"
              . " AND b.status_cd = 'normal'";
         $result = $db->query($sql);
         $c = $db->getRowsNum($result);
         if($c > 0) {
            $roles = "";
            while(list($role_id,$role_nm) = $db->fetchRow($result)) {
               if($role_id=="") continue;
               $roles .= "<div id='dvrole_${role_id}'><input type='checkbox' id='rl_${role_id}' value='${role_nm}'/> <label for='rl_${role_id}'>$role_nm</label></div>";
            }
            $roles .= "<div id='dvrole_empty' style='display:none;'>No role assigned.</div>";
         } else {
            $roles = "<div id='dvrole_empty'>No role assigned.</div>";
         }
         
         $ret = "<table class='xxfrm' style='width:100%;'>"
              . "<colgroup><col width='100'/><col/><col width='200'/></colgroup>"
              . "<tbody>"
              . "<tr><td>Login</td><td><span class='xlnk'>$user_nm</span></td><td><input type='button' value='Unlink' onclick='unlink_login(this,event);'/></td></tr>"
              . "<tr><td>Password</td><td id='pwd'>$reset</td><td>"
                  . "<input type='button' value='"._RESET."' onclick='reset_password(this,event);'/>&nbsp;"
                  . "<input type='button' value='E-mail Password' onclick='email_password(this,event);'/>"
              . "</td></tr>"
              . "<tr><td>Status</td><td id='stt'>$status_txt</td><td><input id='btn_stt' type='button' value='$status_btn' onclick='invert_status(this,event);'/></td></tr>"
              . "<tr><td>Group Access</td><td id='grp'>$groups</td><td>"
                 . "<input type='button' value='"._ADD."' onclick='add_group(this,event);'/>&nbsp;&nbsp;"
                 . "<input type='button' value='"._DELETE."' onclick='delete_group(this,event);'/></td></tr>"
              . "<tr><td>Role Access</td><td id='rl'>$roles</td><td>"
                 . "<input type='button' value='"._ADD."' onclick='add_role(this,event);'/>&nbsp;&nbsp;"
                 . "<input type='button' value='"._DELETE."' onclick='delete_role(this,event);'/></td></tr>"
              . "</tbody>"
              . "</table>";
      } else {
         $ret = "Pegawai ini belum punya login, silakan ketik login yang diinginkan pada input dibawah ini:<br/><br/>"
              . "<table class='xxfrm'>"
              . "<tbody>"
              . "<tr><td>Login</td><td><input type='text' style='width:300px;' id='qlogin'/></td></tr>"
              . "</tbody>"
              . "</table>";
      }
      return $ret;
   }
   
   
   function app_addRole($args) {
      $db=&Database::getInstance();
      $xuser_id = getUserID();
      $role_id = (int)$args[0];
      $person_id = $_SESSION["hris_employee_person_id"];
      
      $sql = "SELECT user_id FROM ".XOCP_PREFIX."users WHERE person_id = '$person_id'";
      $result = $db->query($sql);
      list($user_id)=$db->fetchRow($result);
      
      $sql = "SELECT role_nm FROM ".XOCP_PREFIX."role WHERE role_id = '$role_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($role_nm)=$db->fetchRow($result);
         $sql = "INSERT INTO ".XOCP_PREFIX."user_role (user_id,role_id,created_user_id)"
              . " VALUES ('$user_id','$role_id','$xuser_id')";
         $db->query($sql);
         if($db->errno==0) {
            return array($role_id,$role_nm);
         } else {
            return "FAIL";
         }
      } else {
         return "FAIL";
      }
   }
   
   function app_deleteRole($args) {
      $db=&Database::getInstance();
      $xuser_id = getUserID();
      $arr = explode("|",urldecode($args[0]));
      $person_id = $_SESSION["hris_employee_person_id"];
      $sql = "SELECT user_id FROM ".XOCP_PREFIX."users WHERE person_id = '$person_id'";
      $result = $db->query($sql);
      list($user_id)=$db->fetchRow($result);
      $ret = array();
      foreach($arr as $k=>$v) {
         $role_id = (int)$v;
         $sql = "UPDATE ".XOCP_PREFIX."user_role SET "
              . "status_cd = 'nullified',"
              . "nullified_dttm = now(),"
              . "nullified_user_id = '$xuser_id'"
              . " WHERE user_id = '$user_id'"
              . " AND role_id = '$role_id'"
              . " AND status_cd = 'normal'";
         $db->query($sql);
         $ret[] = $role_id;
      }
      return $ret;
   }
   
   
   
   
   
}

} /// HRIS_CLASS_AJAXUSER_DEFINED
?>