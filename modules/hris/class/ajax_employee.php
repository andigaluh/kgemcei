<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_employee.php                     //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-11-06                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_CLASS_AJAXEMPLOYEE_DEFINED') ) {
   define('HRIS_CLASS_AJAXEMPLOYEE_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");
require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
require_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
require_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");

class _hris_class_EmployeeAjax extends AjaxListener {
   
   function _hris_class_EmployeeAjax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/hris/class/ajax_employee.php";
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
                            "app_confirmAddJob","app_confirmStopJob","app_stopJob",
                            "app_editJobHistory","app_saveJobHistory","app_deleteJobHistory",
                            "app_confirmTerminate","app_Terminate","app_setEntranceDTTM",
                            "app_setExitDTTM","app_saveAddress","app_newEducation",
                            "app_editEducation","app_saveEducation","app_deleteEducation",
                            "app_newTraining","app_editTraining","app_saveTraining",
                            "app_deleteTraining","app_calcAge","app_substitute",
                            "app_editFamily","app_saveBasicSalary","app_deleteFamily",
                            "app_saveFamily","app_getPayrollComponentList","app_addPayrollComponent",
                            "app_editPayrollComponent","app_savePayrollComponent","app_deletePayrollComponent",
                            "app_payrollChangeMonth","app_generatePayroll","app_resetPayroll",
                            "app_confirmFinishPayroll","app_finishPayroll","app_confirmEditPayroll",
                            "app_checkPasswordEditPayroll","app_editWorkGroupAssign","app_deleteWorkGroupAssign",
                            "app_saveWorkGroupAssign");
   }
   
   function renderWorkGroupAssign($employee_id) {
      $db=&Database::getInstance();
      
      $wg .= "<table class='xxlist' style='width:100%;' align='center'>"
           . "<tbody id='tbwglist'>";
      
      $sql = "SELECT a.history_id,a.ts_group_id,b.ts_group_nm,a.start_dttm"
           . " FROM ".XOCP_PREFIX."employee_workgroup_history a"
           . " LEFT JOIN ".XOCP_PREFIX."ts_group b USING(ts_group_id)"
           . " WHERE a.employee_id = '$employee_id'"
           . " AND a.status_cd = 'normal'"
           . " ORDER BY a.start_dttm DESC";
      $result = $db->query($sql);
      $old_start = "";
      if($db->getRowsNum($result)>0) {
         while(list($history_idx,$ts_group_idx,$ts_group_nm,$start_dttmx)=$db->fetchRow($result)) {
            $wg .= "<tr><td id='dvwg_${history_idx}'>"
                 . "<table style='width:100%;'><colgroup><col/><col width='200'/><col width='20'/><col width='200'/></colgroup><tbody><tr>"
                 . "<td><span class='xlnk' onclick='assign_workgroup(\"$history_idx\",this,event);'>$ts_group_nm</span></td>"
                 . "<td style='text-align:right;'>".sql2ind($start_dttmx)."</td>"
                 . "<td style='text-align:center;'>-</td>"
                 . "<td style='text-align:left;'>".($old_start==""?"now":sql2ind($old_start))."</td>"
                 . "</tr></tbody></table>"
                 . "</td></tr>";
            $old_start = $start_dttmx;
            
         }
         $wg .= "<tr style='display:none;' id='wgtrempty'><td>"._EMPTY."</td></tr>";
      } else {
         $wg .= "<tr id='wgtrempty'><td>"._EMPTY."</td></tr>";
      }
      
      $wg .= "</tbody></table>";
      return $wg;
   }
   
   function app_saveWorkGroupAssign($args) {
      $db=&Database::getInstance();
      $history_id = $args[0];
      $vars = _parseForm($args[1]);
      $employee_id = $_SESSION["hris_employee_id"];
      $user_id = getUserID();
      
      foreach($vars as $k=>$v) {
         $$k = $v;
      }
      
      if($history_id=="new") {
         $user_id = getUserID();
         $sql = "INSERT INTO ".XOCP_PREFIX."employee_workgroup_history (employee_id,ts_group_id,start_dttm,created_user_id)"
              . " VALUES('$employee_id','$ts_group_id','$start_dttm','$user_id')";
         $db->query($sql);
         $history_id = $db->getInsertId();
         _debuglog($sql);
      }
      
      $sql = "UPDATE ".XOCP_PREFIX."employee_workgroup_history SET "
           . "ts_group_id = '$ts_group_id',"
           . "start_dttm = '$start_dttm'"
           . " WHERE history_id = '$history_id'";
      $db->query($sql);
      _debuglog($sql);
      
      $sql = "SELECT ts_group_nm FROM ".XOCP_PREFIX."ts_group WHERE ts_group_id = '$ts_group_id'";
      $result = $db->query($sql);
      list($ts_group_nm)=$db->fetchRow($result);
      
      $sql = "SELECT start_dttm FROM ".XOCP_PREFIX."employee_workgroup_history WHERE employee_id = '$employee_id' AND start_dttm > '$start_dttm' LIMIT 1";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($old_start)=$db->fetchRow($result);
      } else {
         $old_start = "";
      }
      
      $ret = "<table style='width:100%;'><colgroup><col/><col width='200'/><col width='20'/><col width='200'/></colgroup><tbody><tr>"
           . "<td><span class='xlnk' onclick='assign_workgroup(\"$history_id\",this,event);'>$ts_group_nm</span></td>"
           . "<td style='text-align:right;'>".sql2ind($start_dttm)."</td>"
           . "<td style='text-align:center;'>-</td>"
           . "<td style='text-align:left;'>".($old_start==""?"now":sql2ind($old_start))."</td>"
           . "</tr></tbody></table>";
      
      return array("dvwg_${history_id}",$this->renderWorkGroupAssign($employee_id));
   }
   
   function app_deleteWorkGroupAssign($args) {
      $db=&Database::getInstance();
      $history_id = $args[0];
      $user_id = getUserID();
      $sql = "UPDATE ".XOCP_PREFIX."employee_workgroup_history SET status_cd = 'nullified', nullified_dttm = now(), nullified_user_id = '$user_id'"
           . " WHERE history_id = '$history_id'";
      $db->query($sql);
      
   }
   
   function app_editWorkGroupAssign($args) {
      $db=&Database::getInstance();
      $history_id = $args[0];
      if($history_id=="new") {
         $start_dttm = getSQLDate();
      } else {
         $sql = "SELECT a.ts_group_id,a.start_dttm,b.ts_group_nm"
              . " FROM ".XOCP_PREFIX."employee_workgroup_history a"
              . " LEFT JOIN ".XOCP_PREFIX."ts_group b USING(ts_group_id)"
              . " WHERE a.history_id = '$history_id'";
         $result = $db->query($sql);
         list($ts_group_id,$start_dttm,$ts_group_nm)=$db->fetchRow($result);
         $ts_group_nm = htmlentities($ts_group_nm,ENT_QUOTES);
      }
      
      $sql = "SELECT ts_group_nm,ts_group_id"
           . " FROM ".XOCP_PREFIX."ts_group"
           . " ORDER BY ts_group_id";
      $result = $db->query($sql);
      _debuglog($sql);
      $opt = "";
      if($db->getRowsNum($result)>0) {
         while(list($ts_group_nmx,$ts_group_idx)=$db->fetchRow($result)) {
            $opt .= "<option value='$ts_group_idx' ".($ts_group_idx==$ts_group_id?"selected='1'":"").">$ts_group_nmx</option>";
         }
      }
      
      $ret = "<div id='frmwg'><table style='width:100%;' class='xxfrm'><tbody>"
           . "<tr><td>Work Group Name</td><td><select name='ts_group_id'>$opt</select></td></tr>"
           . "<tr><td>Start Datetime</td><td><span class='xlnk' id='spstart' onclick='_changedatetime(\"spstart\",\"hstart_dttm\",\"datetime\",false,false)'>".sql2ind($start_dttm)."</span><input type='hidden' value='$start_dttm' name='start_dttm' id='hstart_dttm'/></td></tr>"
           . "<tr><td colspan='2'><input onclick='save_workgroup_assign();' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input onclick='cancel_edit_workgroup_assign();' type='button' value='"._CANCEL."'/>&nbsp;&nbsp;"
           . ($history_id!="new"?"<input onclick='delete_workgroup_assign();' type='button' value='"._DELETE."'/>":"")
           . "</td></tr>"
           . "</tbody></table></div>";
      return $ret;
   }
   
   
   
   function app_checkPasswordEditPayroll($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $xid = $args[0];
      $hpass = $args[1];
      $employee_id = $_SESSION["hris_employee_id"];
      
      list($dt,$tm)=explode(" ",$_SESSION["payroll_current_time"]);
      list($payroll_year,$payroll_month,$payroll_date)=explode("-",$dt);
      $payroll_month += 0;
      
      $sql = "SELECT pwd0 FROM ".XOCP_PREFIX."users WHERE user_id = '$user_id'";
      $result = $db->query($sql);
      list($pwd0)=$db->fetchRow($result);
      $pwd1 = md5($xid."-".$pwd0);
      if($hpass!=$pwd1) {
         return "WRONGPASSWORD";
      } else {
         $sql = "UPDATE ".XOCP_PREFIX."payroll_posting SET status_cd = 'normal', finish_dttm = '0000-00-00 00:00:00', finish_user_id = '0'"
              . " WHERE payroll_year = '$payroll_year'"
              . " AND payroll_month = '$payroll_month'";
         $result = $db->query($sql);
      }
      
      $list = $this->getPayrollList($employee_id,$payroll_year,$payroll_month);
      
      $btn = "<input type='button' class='xaction' id='btngeneratepayroll' value='Generate' onclick='generate_payroll(this,event);'/>&nbsp;"
           . "<input type='button' value='Finish' onclick='finish_payroll(this,event);'/>";
      
      return array($list,$btn);
   }
   
   function app_confirmEditPayroll($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $employee_id = $_SESSION["hris_employee_id"];
      
      $ret = "<div style='font-size:1em;'>"
           . "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>Edit Payroll Confirmation</div>";
      
      $ret .= "<div style='padding:10px;text-align:center;'>";
      
      $ret .= "Are you sure you want to edit this employee&#39;s payroll?<br/><br/>"
            . "Please enter your password for security protection:<br/><br/><input id='editpayrollpass' type='password' style='width:150px;text-align:center;' onkeydown='kd_payrollpass(this,event);'/>"
            . "<br/>"
            . "<div style='margin-top:10px;color:red;' id='ckpass_feedback'>&nbsp;</div>"
            . "<br/>"
            . "<input type='button' value='Yes (edit)' onclick='do_edit_payroll();'/>&nbsp;"
            . "<input type='button' value='No (cancel)' onclick='editpayrollbox.fade();'/>";
      
      $ret .= "</div>";
      
      $ret .= "</div>";
      
      
      return array($ret);
   }
   
   function app_finishPayroll($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $employee_id = $_SESSION["hris_employee_id"];
      
      list($dt,$tm)=explode(" ",$_SESSION["payroll_current_time"]);
      list($payroll_year,$payroll_month,$payroll_date)=explode("-",$dt);
      $payroll_month += 0;
   
      $sql = "UPDATE ".XOCP_PREFIX."payroll_posting SET status_cd = 'finish', finish_dttm = now(), finish_user_id = '$user_id'"
           . " WHERE payroll_year = '$payroll_year'"
           . " AND payroll_month = '$payroll_month'";
      $result = $db->query($sql);
      
      $list = $this->getPayrollList($employee_id,$payroll_year,$payroll_month);
      
      $btn = "<input type='button' value='Print Payslip' onclick='print_payroll(this,event);'/>&nbsp;"
           . "<input type='button' value='Edit' onclick='edit_payroll(this,event);'/>";
      
      return array($list,$btn);
   }
   
   function app_confirmFinishPayroll($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $employee_id = $_SESSION["hris_employee_id"];
      
      $ret = "<div style='font-size:1em;'>"
           . "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>Finish Payroll Confirmation</div>";
      
      $ret .= "<div style='padding:10px;text-align:center;'>";
      
      $ret .= "Are you sure you want to finish this employee&#39;s payroll?<br/>Payroll will be protected from subsequent modifications.<br/><br/>"
            . "<input type='button' value='Yes (finish)' onclick='do_finish_payroll();' class='xaction'/>&nbsp;"
            . "<input type='button' value='No (cancel)' onclick='finalpayrollbox.fade();'/>";
      
      $ret .= "</div>";
      
      $ret .= "</div>";
      
      
      return array($ret);
   }
   
   function app_resetPayroll($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $employee_id = $_SESSION["hris_employee_id"];
      
      list($dt,$tm)=explode(" ",$_SESSION["payroll_current_time"]);
      list($payroll_year,$payroll_month,$payroll_date)=explode("-",$dt);
      $payroll_month += 0;
      
      $sql = "SELECT payroll_posting_id FROM ".XOCP_PREFIX."payroll_posting"
           . " WHERE payroll_year = '$payroll_year'"
           . " AND payroll_month = '$payroll_month'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($payroll_posting_id)=$db->fetchRow($result);
         $sql = "DELETE FROM ".XOCP_PREFIX."payroll_posting_employee_item WHERE employee_id = '$employee_id' AND payroll_posting_id = '$payroll_posting_id'";
         $db->query($sql);
      }
      
      return $this->getPayrollList($employee_id,$payroll_year,$payroll_month);
      
   }
   
   function app_generatePayroll($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $employee_id = $_SESSION["hris_employee_id"];
      
      $payroll_posting_id = $_SESSION["hris_payroll_posting_id"];
      $payroll_year = $_SESSION["hris_payroll_year"];
      $payroll_month = $_SESSION["hris_payroll_month"];
      
      list($JOB_ID,
           $EMPLOYEE_ID,
           $JOB_NM,
           $EMPLOYEE_NM,
           $NIP,
           $GENDER,
           $JOB_START_DTTM,
           $ENTRANCE_DTTM,
           $JOB_AGE_YEAR,
           $JOB_SUMMARY,
           $PERSON_ID,
           $USER_ID,
           $FIRST_ASSESSOR_JOB_ID,
           $NEXT_ASSESSOR_JOB_ID)=_hris_getinfobyemployeeid($employee_id);
      $sql = "SELECT b.location_cd FROM ".XOCP_PREFIX."employee_job a"
           . " LEFT JOIN ".XOCP_PREFIX."location b USING(location_id)"
           . " WHERE a.employee_id = '$EMPLOYEE_ID'"
           . " AND a.job_id = '$JOB_ID'";
      $result = $db->query($sql);
      list($LOCATION)=$db->fetchRow($result);
      
      $PAYROLL_POSTING_ID = $payroll_posting_id;
      $PAYROLL_YEAR = $payroll_year;
      $PAYROLL_MONTH = $payroll_month;
      
      
      $sql = "SELECT attendance_id,pbk FROM ".XOCP_PREFIX."employee WHERE employee_id = '$employee_id'";
      $result = $db->query($sql);
      list($attendance_id,$PBK)=$db->fetchRow($result);
      
      /*
      list($dt,$tm)=explode(" ",$_SESSION["payroll_current_time"]);
      list($payroll_year,$payroll_month,$payroll_date)=explode("-",$dt);
      $payroll_month += 0;
      */
      
      $JOB_MONTH = $payroll_month-1;
      if($JOB_MONTH==0) {
         $JOB_MONTH = 12;
         $JOB_YEAR = $payroll_year-1;
      } else {
         $JOB_YEAR = $payroll_year;
      }
               
      $sql = "DELETE FROM ".XOCP_PREFIX."payroll_posting_employee_item WHERE employee_id = '$employee_id' AND payroll_posting_id = '$payroll_posting_id'";
      $db->query($sql);
      
      $item_id = 0;
      
      $sql = "SELECT a.assignment_id,a.payrollobject_class_id,b.payrollobject_class_cd,b.payrollobject_class_nm,b.payrollobject_type,a.static_value,b.php_script,b.payrollobject_class_cd"
           . " FROM ".XOCP_PREFIX."payrollobject_employee a"
           . " LEFT JOIN ".XOCP_PREFIX."payrollobject_class b USING(payrollobject_class_id)"
           . " WHERE a.employee_id = '$employee_id'"
           . " AND b.payrollobject_type = 'income'"
           . " AND a.status_cd = 'normal'"
           . " ORDER BY b.order_no";
      $res_income = $db->query($sql);
      if($db->getRowsNum($res_income)>0) {
         while(list($assignment_id,$payrollobject_class_id,$payrollobject_class_cd,$payrollobject_class_nm,$payrollobject_type,$static_value,$php_script,$payrollobject_class_cd)=$db->fetchRow($res_income)) {
            $final_value = 0;
            $item_id++;
            
            /// calculate here
            if($static_value>0) {
               $final_value = $static_value+0;
            } else {
               eval($php_script);
            }
            
            $$payrollobject_class_cd = $final_value;
            
            $sql = "INSERT INTO ".XOCP_PREFIX."payroll_posting_employee_item (payroll_posting_id,employee_id,item_id,payrollobject_class_id,final_value,posting_dttm,posting_user_id,created_user_id)"
                 . " VALUES ('$payroll_posting_id','$employee_id','$item_id','$payrollobject_class_id','$final_value',now(),'$user_id','$user_id')";
            $db->query($sql);
         }
      }
      
      $sql = "SELECT a.assignment_id,a.payrollobject_class_id,b.payrollobject_class_cd,b.payrollobject_class_nm,b.payrollobject_type,a.static_value,b.php_script,b.payrollobject_class_cd"
           . " FROM ".XOCP_PREFIX."payrollobject_employee a"
           . " LEFT JOIN ".XOCP_PREFIX."payrollobject_class b USING(payrollobject_class_id)"
           . " WHERE a.employee_id = '$employee_id'"
           . " AND b.payrollobject_type = 'deduction'"
           . " AND a.status_cd = 'normal'"
           . " ORDER BY b.order_no";
      $res_deduction = $db->query($sql);
      if($db->getRowsNum($res_deduction)>0) {
         while(list($assignment_id,$payrollobject_class_id,$payrollobject_class_cd,$payrollobject_class_nm,$payrollobject_type,$static_value,$php_script,$payrollobject_class_cd)=$db->fetchRow($res_deduction)) {
            $final_value = 0;
            $item_id++;
            
            /// calculate here
            if($static_value>0) {
               $final_value = $static_value+0;
            } else {
               eval($php_script);
            }
            $$payrollobject_class_cd = $final_value;
            
            $sql = "INSERT INTO ".XOCP_PREFIX."payroll_posting_employee_item (payroll_posting_id,employee_id,item_id,payrollobject_class_id,final_value,posting_dttm,posting_user_id,created_user_id)"
                 . " VALUES ('$payroll_posting_id','$employee_id','$item_id','$payrollobject_class_id','$final_value',now(),'$user_id','$user_id')";
            $db->query($sql);
            _debuglog($sql);
         }
      }
      
      $sql = "SELECT a.assignment_id,a.payrollobject_class_id,b.payrollobject_class_cd,b.payrollobject_class_nm,b.payrollobject_type,a.static_value,b.php_script,b.payrollobject_class_cd"
           . " FROM ".XOCP_PREFIX."payrollobject_employee a"
           . " LEFT JOIN ".XOCP_PREFIX."payrollobject_class b USING(payrollobject_class_id)"
           . " WHERE a.employee_id = '$employee_id'"
           . " AND b.payrollobject_type = 'tax'"
           . " AND a.status_cd = 'normal'"
           . " ORDER BY b.order_no";
      $res_tax = $db->query($sql);
      if($db->getRowsNum($res_tax)>0) {
         while(list($assignment_id,$payrollobject_class_id,$payrollobject_class_cd,$payrollobject_class_nm,$payrollobject_type,$static_value,$php_script,$payrollobject_class_cd)=$db->fetchRow($res_tax)) {
            $final_value = 0;
            $item_id++;
            
            /// calculate here
            if($static_value>0) {
               $final_value = $static_value+0;
            } else {
               eval($php_script);
            }
            $$payrollobject_class_cd = $final_value;
            
            $sql = "INSERT INTO ".XOCP_PREFIX."payroll_posting_employee_item (payroll_posting_id,employee_id,item_id,payrollobject_class_id,final_value,posting_dttm,posting_user_id,created_user_id)"
                 . " VALUES ('$payroll_posting_id','$employee_id','$item_id','$payrollobject_class_id','$final_value',now(),'$user_id','$user_id')";
            $db->query($sql);
            _debuglog($sql);
         }
      }
      
      return $this->getPayrollList($employee_id,$payroll_year,$payroll_month);
      
   }
   
   function getPayrollList($employee_id,$payroll_year,$payroll_month) {
      $db=&Database::getInstance();
      $employee_id = $_SESSION["hris_employee_id"];
      
      $sql = "SELECT payroll_posting_id,status_cd FROM ".XOCP_PREFIX."payroll_posting"
           . " WHERE payroll_year = '$payroll_year'"
           . " AND payroll_month = '$payroll_month'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($payroll_posting_id,$status_cd)=$db->fetchRow($result);
      } else {
         $payroll_posting_id = 0;
      }
      
      if($status_cd=="finish") {
         $ret = "<div style='-moz-border-radius:5px;border:1px solid #888;padding:5px;background-color:#eeeeff;'>";
      } else {
         $ret = "<div style='-moz-border-radius:5px;border:1px solid #888;padding:5px;'>";
      }
      
      $cnt = 0;
      
      $ttl = 0;
      $sql = "SELECT a.item_id,a.payrollobject_class_id,b.payrollobject_class_nm,b.payrollobject_type,a.final_value"
           . " FROM ".XOCP_PREFIX."payroll_posting_employee_item a"
           . " LEFT JOIN ".XOCP_PREFIX."payrollobject_class b USING(payrollobject_class_id)"
           . " WHERE a.employee_id = '$employee_id'"
           . " AND a.payroll_posting_id = '$payroll_posting_id'"
           . " AND b.payrollobject_type = 'income'";
      $result = $db->query($sql);
      $ret .= "<div style='background-color:#eee;padding:5px;text-align:left;border:1px solid #bbb;margin-top:0px;font-weight:bold;'>Income</div>";
      if($db->getRowsNum($result)>0) {
         while(list($item_id,$payrollobject_class_id,$payrollobject_class_nm,$payrollobject_type,$final_value)=$db->fetchRow($result)) {
            $ret .= "<div style='padding:3px;border-bottom:1px solid #bbb;padding-left:20px;'>"
                  . "<table style='width:100%;'>"
                  . "<colgroup><col/><col width='200'/></colgroup>"
                  . "<tbody><tr><td>$payrollobject_class_nm</td><td style='text-align:right;'>".toMoney($final_value)."</td></tr></tbody></table>"
                  . "</div>";
            $ttl = _bctrim(bcadd($ttl,$final_value));
            $cnt++;
         }
      }
      
      $sql = "SELECT a.item_id,a.payrollobject_class_id,b.payrollobject_class_nm,b.payrollobject_type,a.final_value"
           . " FROM ".XOCP_PREFIX."payroll_posting_employee_item a"
           . " LEFT JOIN ".XOCP_PREFIX."payrollobject_class b USING(payrollobject_class_id)"
           . " WHERE a.employee_id = '$employee_id'"
           . " AND a.payroll_posting_id = '$payroll_posting_id'"
           . " AND b.payrollobject_type = 'deduction'";
      $result = $db->query($sql);
      $ret .= "<div style='background-color:#eee;padding:5px;text-align:left;border:1px solid #bbb;margin-top:5px;font-weight:bold;'>Deduction</div>";
      if($db->getRowsNum($result)>0) {
         while(list($item_id,$payrollobject_class_id,$payrollobject_class_nm,$payrollobject_type,$final_value)=$db->fetchRow($result)) {
            $ret .= "<div style='padding:3px;border-bottom:1px solid #bbb;padding-left:20px;'>"
                  . "<table style='width:100%;'>"
                  . "<colgroup><col/><col width='200'/></colgroup>"
                  . "<tbody><tr><td>$payrollobject_class_nm</td><td style='text-align:right;color:red;'>".toMoney($final_value)."</td></tr></tbody></table>"
                  . "</div>";
            $ttl = _bctrim(bcsub($ttl,$final_value));
            $cnt++;
         }
      }
      
      $sql = "SELECT a.item_id,a.payrollobject_class_id,b.payrollobject_class_nm,b.payrollobject_type,a.final_value"
           . " FROM ".XOCP_PREFIX."payroll_posting_employee_item a"
           . " LEFT JOIN ".XOCP_PREFIX."payrollobject_class b USING(payrollobject_class_id)"
           . " WHERE a.employee_id = '$employee_id'"
           . " AND a.payroll_posting_id = '$payroll_posting_id'"
           . " AND b.payrollobject_type = 'tax'";
      $result = $db->query($sql);
      $ret .= "<div style='background-color:#eee;padding:5px;text-align:left;border:1px solid #bbb;margin-top:5px;font-weight:bold;'>Tax</div>";
      if($db->getRowsNum($result)>0) {
         while(list($item_id,$payrollobject_class_id,$payrollobject_class_nm,$payrollobject_type,$final_value)=$db->fetchRow($result)) {
            $ret .= "<div style='padding:3px;border-bottom:1px solid #bbb;padding-left:20px;'>"
                  . "<table style='width:100%;'>"
                  . "<colgroup><col/><col width='200'/></colgroup>"
                  . "<tbody><tr><td>$payrollobject_class_nm</td><td style='text-align:right;color:#0000ff;'>".toMoney($final_value)."</td></tr></tbody></table>"
                  . "</div>";
            $ttl = _bctrim(bcsub($ttl,$final_value));
            $cnt++;
         }
      }
      
      $ret .= "<div style='background-color:#ddd;color:black;font-weight:bold;padding:5px;text-align:left;border:1px solid #bbb;margin-top:5px;'>"
                  . "<table style='width:100%;'>"
                  . "<colgroup><col/><col width='200'/></colgroup>"
                  . "<tbody><tr><td>Total</td><td style='text-align:right;'>".toMoney($ttl)."</td></tr></tbody></table>"
            . "</div>";
      
      
      $ret .= "</div>";
      
      return $ret;
   }
   
   function app_payrollChangeMonth($args) {
      $db=&Database::getInstance();
      global $xocp_vars;
      $interval = $args[0];
      $current_payroll_dttm = $_SESSION["payroll_current_time"];
      $sql = "SELECT YEAR(DATE_ADD('$current_payroll_dttm', INTERVAL $interval MONTH)),MONTH(DATE_ADD('$current_payroll_dttm', INTERVAL $interval MONTH))";
      $result = $db->query($sql);
      _debuglog($sql);
      list($payroll_year,$payroll_month)=$db->fetchRow($result);
      
      $_SESSION["payroll_current_time"] = getSQLDate("$payroll_year-$payroll_month-05 00:00:00");
      
      list($dt,$tm)=explode(" ",$_SESSION["payroll_current_time"]);
      list($payroll_year,$payroll_month,$payroll_date)=explode("-",$dt);
      $payroll_month += 0;
      
      $sql = "SELECT payroll_posting_id,status_cd FROM ".XOCP_PREFIX."payroll_posting"
           . " WHERE payroll_year = '$payroll_year'"
           . " AND payroll_month = '$payroll_month'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($payroll_posting_id,$payroll_posting_status)=$db->fetchRow($result);
      } else {
         $payroll_posting_status = "";
      }
      
      $list = $this->getPayrollList($employee_id,$payroll_year,$payroll_month);
      
      if($payroll_posting_status=="finish") {
         $btn = "<input type='button' value='Print Payslip' onclick='print_payroll(this,event);'/>&nbsp;"
              . "<input type='button' value='Edit' onclick='edit_payroll(this,event);'/>";
      } else {
         $btn = "<input type='button' class='xaction' id='btngeneratepayroll' value='Generate' onclick='generate_payroll(this,event);'/>&nbsp;"
              . "<input type='button' value='Finish' onclick='finish_payroll(this,event);'/>";
      }
      
      
      return array("$payroll_year ".$xocp_vars['month_year'][$payroll_month],$list,$btn);
      
   }  
   
   function app_deletePayrollComponent($args) {
      $db=&Database::getInstance();
      $employee_id = $_SESSION["hris_employee_id"];
      $assignment_id = $args[0];
      $sql = "UPDATE ".XOCP_PREFIX."payrollobject_employee SET status_cd = 'nullified', nullified_dttm = now()"
           . " WHERE assignment_id = '$assignment_id'";
      $db->query($sql);
      _debuglog($sql);
   }
   
   function app_savePayrollComponent($args) {
      $db=&Database::getInstance();
      $employee_id = $_SESSION["hris_employee_id"];
      
      $pc_assign_id = $args[0];
      $vars = _parseForm($args[1]);
      foreach($vars as $k=>$v) {
         $$k=addslashes($v);
      }
      
      $sql = "UPDATE hris_payrollobject_employee SET static_value = '$static_value' "
           . " WHERE employee_id = '$employee_id'"
           . " AND assignment_id = '$pc_assign_id'";
      $db->query($sql);
      
   }
   
   function app_editPayrollComponent($args) {
      $db=&Database::getInstance();
      $employee_id = $_SESSION["hris_employee_id"];
      
      $pc_assign_id = $args[0];
      
      $sql = "SELECT a.assign_start_dttm,a.assign_stop_dttm,a.status_cd,a.php_script,a.static_value,b.payrollobject_class_nm,b.payrollobject_type"
           . " FROM ".XOCP_PREFIX."payrollobject_employee a"
           . " LEFT JOIN ".XOCP_PREFIX."payrollobject_class b USING(payrollobject_class_id)"
           . " WHERE a.assignment_id = '$pc_assign_id'"
           . " AND a.employee_id = '$employee_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($assign_start_dttm,$assign_stop_dttm,$status_cd,$php_script,$static_value,$payrollobject_class_nm,$payrollobject_type)=$db->fetchRow($result);
      }
      
      $ret = "<div id='dvfrmeditpc'><table class='xxfrm' style='width:100%;'>"
           . "<colgroup><col width='120'/><col/></colgroup>"
           . "<tbody>"
           . "<tr><td>Component Name</td><td>$payrollobject_class_nm</td></tr>"
           . "<tr><td>Type</td><td>$payrollobject_type</td></tr>"
           . "<tr><td>Amount</td><td><input type='text' value='$static_value' style='width:200px;' name='static_value'/></td></tr>"
           . "<tr><td>Start</td><td><span class='xlnk' onclick='_changedatetime(\"spassign_start_dttm\",\"hassign_start_dttm\",\"datetime\",false,false);' id='spassign_start_dttm'>".sql2ind($assign_start_dttm)."</span><input type='hidden' value='$assign_start_dttm' name='assign_start_dttm' id='hassign_start_dttm'/></td></tr>"
           . "<tr><td>Stop</td><td><span class='xlnk' onclick='_changedatetime(\"spassign_stop_dttm\",\"hassign_stop_dttm\",\"datetime\",false,false);' id='spassign_stop_dttm'>".sql2ind($assign_stop_dttm)."</span><input type='hidden' value='$assign_start_dttm' name='assign_start_dttm' id='hassign_start_dttm'/></td></tr>"
           . "<tr><td colspan='2'><input onclick='save_pc();' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input onclick='cancel_edit_pc();' type='button' value='"._CANCEL."'/>&nbsp;&nbsp;"
           . "<input onclick='delete_pc();' type='button' value='"._DELETE."'/>"
           . "</td></tr>"
           . "</tbody></table></div>";
      return $ret;
   }
   
   function app_addPayrollComponent($args) {
      $db=&Database::getInstance();
      $employee_id = $_SESSION["hris_employee_id"];
      $user_id = getUserID();
      $pc_class_id = $args[0];
      $sql = "INSERT INTO ".XOCP_PREFIX."payrollobject_employee (employee_id,payrollobject_class_id,assign_start_dttm,created_user_id)"
           . " VALUES ('$employee_id','$pc_class_id',now(),'$user_id')";
      $db->query($sql);
      $assign_id = $db->getInsertId();
      $sql = "SELECT payrollobject_class_nm FROM ".XOCP_PREFIX."payrollobject_class WHERE payrollobject_class_id = '$pc_class_id'";
      $result = $db->query($sql);
      list($payrollobject_class_nm)=$db->fetchRow($result);
      
      $span = "<span class='xlnk' onclick='edit_pc(\"$assign_id\",this,event);'>$payrollobject_class_nm</span>";
      
      $ret = array($assign_id,$span);
      
      _dumpvar($ret);
      
      return $ret;
      
   }
   
   function app_getPayrollComponentList($args) {
      $db=&Database::getInstance();
      $employee_id = $_SESSION["hris_employee_id"];
      $pc_type = $args[0];
      
      $sql = "SELECT payrollobject_class_id FROM ".XOCP_PREFIX."payrollobject_employee"
           . " WHERE employee_id = '$employee_id'"
           . " AND status_cd = 'normal'";
      $result = $db->query($sql);
      $addedd = array();
      if($db->getRowsNum($result)>0) {
         while(list($payrollobject_class_idx)=$db->fetchRow($result)) {
            $addedd[$payrollobject_class_idx] = 1;
         }
      }
      
      $sql = "SELECT payrollobject_class_id,payrollobject_class_nm,payrollobject_class_cd"
           . " FROM ".XOCP_PREFIX."payrollobject_class"
           . " WHERE payrollobject_type = '$pc_type'"
           . " ORDER BY order_no";
      $result = $db->query($sql);
      _debuglog($sql);
      $ret = array();
      if($db->getRowsNum($result)>0) {
         while(list($pc_class_id,$pc_class_nm,$pc_class_cd)=$db->fetchRow($result)) {
            if($addedd[$pc_class_id]==1) continue;
            $ret[] = array($pc_class_nm,$pc_class_id);
         }
      }
      _dumpvar($ret);
      
      if(count($ret)>0) {
         return $ret;
      } else {
         return "EMPTY";
      }
   }
   
   function app_saveFamily($args) {
      $db=&Database::getInstance();
      $employee_id = $_SESSION["hris_employee_id"];
      $ctc_seq = $args[0];
      $vars = _parseForm($args[1]);
      foreach($vars as $k=>$v) {
         $$k=addslashes($v);
      }
      $deceased_ind += 0;
      if($deceased_ind==1) {
         $deceased_dttm = getSQLDate($deceased_dttm);
      } else {
         $deceased_dttm = "0000-00-00 00:00:00";
      }
      $sql = "UPDATE ".XOCP_PREFIX."employee_family SET"
           . " person_nm = '$person_nm',"
           . " date_of_birth = '$date_of_birth',"
           . " gender_cd = '$gender_cd',"
           . " relation_type = '$relation_type',"
           . " deceased_ind = '$deceased_ind',"
           . " deceased_dttm = '$deceased_dttm'"
           . " WHERE employee_id = '$employee_id'"
           . " AND ctc_seq = '$ctc_seq'";
      $db->query($sql);
      $sql = "SELECT TO_DAYS(now()),TO_DAYS(date_of_birth),TO_DAYS(deceased_dttm) FROM ".XOCP_PREFIX."employee_family"
           . " WHERE employee_id = '$employee_id'"
           . " AND ctc_seq = '$ctc_seq'";
      $result = $db->query($sql);
      list($now_days,$birth_days,$deceased_days)=$db->fetchRow($result);
      if($deceased_ind==1) {
         $age_years = toMoney(($deceased_days-$birth_days)/365.25);
      } else {
         $age_years = toMoney(($now_days-$birth_days)/365.25);
      }
      return array($ctc_seq,htmlentities($vars["person_nm"]),$gender_cd,sql2ind($date_of_birth,"date"),$age_years,$relation_type);
   }
   
   function app_deleteFamily($args) {
      $db=&Database::getInstance();
      $employee_id = $_SESSION["hris_employee_id"];
      $ctc_seq = $args[0];
      $sql = "DELETE FROM ".XOCP_PREFIX."employee_family"
           . " WHERE employee_id = '$employee_id'"
           . " AND ctc_seq = '$ctc_seq'";
      $db->query($sql);
   }
   
   function app_saveBasicSalary($args) {
      $db=&Database::getInstance();
      $employee_id = $args[0];
      $basic_salary = _bctrim(bcadd($args[1],0));
      $sql = "UPDATE ".XOCP_PREFIX."employee SET basic_salary = '$basic_salary' WHERE employee_id = '$employee_id'";
      $result = $db->query($sql);
   }
   
   function app_editFamily($args) {
      global $family_relation;
      require_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
      $db=&Database::getInstance();
      $employee_id = $_SESSION["hris_employee_id"];
      $ctc_seq = $args[0];
      if($ctc_seq=="new") {
         $ctc_seq = 0;
         $sql = "SELECT MAX(ctc_seq) FROM ".XOCP_PREFIX."employee_family WHERE employee_id = '$employee_id'";
         $result = $db->query($sql);
         if($db->getRowsNum($result)==1) {
            list($ctc_seq)=$db->fetchRow($result);
         }
         $ctc_seq++;
         $sql = "INSERT INTO ".XOCP_PREFIX."employee_family (employee_id,ctc_seq,relation_type,date_of_birth) VALUES ('$employee_id','$ctc_seq','child',now())";
         $db->query($sql);
         
         
         
      }
      $sql = "SELECT person_nm,gender_cd,date_of_birth,relation_type,deceased_ind,deceased_dttm,TO_DAYS(now())-TO_DAYS(date_of_birth)"
           . " FROM ".XOCP_PREFIX."employee_family"
           . " WHERE employee_id = '$employee_id'"
           . " AND ctc_seq = '$ctc_seq'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($person_nm,$gender_cd,$dob,$relation_type,$deceased_ind,$deceased_dttm,$days_age)=$db->fetchRow($result);
         
         $list = "<table style='width:100%;'><colgroup><col width='25'/><col/><col width='70'/><col width='150'/><col width='90'/><col width='70'/></colgroup>"
               . "<tbody><tr><td>$ctc_seq</td><td><span id='spfamilynm_${ctc_seq}' class='xlnk' onclick='edit_family(\"$ctc_seq\",this,event);'>".(trim($person_nm)==""?_EMPTY:$person_nm)."</span>"
               . "</td><td style='text-align:center;' id='tdfamilygender_${ctc_seq}'>$gender_cd"
               . "</td><td id='tdfamilydob_${ctc_seq}'>".sql2ind($dob,"date")
               . "</td><td id='tdfamilyage_${ctc_seq}' style='text-align:right;'>".toMoney($days_age/365.25)."&nbsp;&nbsp;&nbsp;&nbsp;"
               . "</td><td id='tdfamilyrel_${ctc_seq}'>$relation_type"
               . "</td></tr></tbody></table>";
         
         $opt_relation = "";
         foreach($family_relation as $k=>$v) {
            $opt_relation .= "<option value='$k' ".($relation_type==$k?"selected='1'":"").">$v</option>";
         }
         $ret = "<div id='frmeditfamily'>";
         $ret .= "<table style='width:100%;' class='xxfrm'><tbody>";
         $ret .= "<tr><td>Person Name</td><td><input type='text' style='width:200px;' value='$person_nm' name='person_nm' id='family_person_nm'/></td></tr>";
         $ret .= "<tr><td>Gender</td><td>"
               . "<input type='radio' value='m' name='gender_cd' id='gender_cd_m' ".($gender_cd=="m"?"checked='1'":"")."/> <label for='gender_cd_m' class='xlnk'>Male</label>&nbsp;&nbsp;"
               . "<input type='radio' value='f' name='gender_cd' id='gender_cd_f' ".($gender_cd=="f"?"checked='1'":"")."/> <label for='gender_cd_f' class='xlnk'>Female</label>&nbsp;&nbsp;"
               . "</td></tr>";
         $ret .= "<tr><td>Date of Birth</td><td><span class='xlnk' id='spdob' onclick='_changedatetime(\"spdob\",\"date_of_birth\",\"datetime\",false,false);'>".sql2ind($dob)."</span><input type='hidden' name='date_of_birth' value='$dob' id='date_of_birth'/></td></tr>";
         $ret .= "<tr><td>Relation</td><td><select name='relation_type'>$opt_relation</select></td></tr>";
         $ret .= "<tr><td>Deceased</td><td>"
               . "<input onclick='toggle_deceased(this,event);' type='checkbox' value='1' name='deceased_ind' id='deceased_ind' ".($deceased_ind=="1"?"checked='1'":"")."/>&nbsp;&nbsp;"
               . "<span ".($deceased_ind==1?"style=''":"style='display:none;'")." class='xlnk' id='spdod' onclick='_changedatetime(\"spdod\",\"deceased_dttm\",\"datetime\",false,false);'>".sql2ind($deceased_dttm)."</span><input type='hidden' name='deceased_dttm' value='$deceased_dttm' id='deceased_dttm'/>"
               . "</td></tr>";
         $ret .= "<tr><td colspan='2'>"
               . "&nbsp;<input type='button' value='"._SAVE."' onclick='save_family(this,event);'/>"
               . "&nbsp;<input type='button' value='"._CANCEL."' onclick='cancel_family(this,event);'/>"
               . "&nbsp;&nbsp;<input type='button' value='"._DELETE."' onclick='delete_family(this,event);'/>"
               . "</td></tr>";
         $ret .= "</tbody></table>";
         $ret .= "</div>";
      
      
      
      } else {
         $ret = "FAIL";
      }
      return array($ctc_seq,$ret,$list);
   }
   
   function app_substitute($args) {
      $db=&Database::getInstance();
      $person_id = $args[0];
      
      $sql = "SELECT user_id FROM ".XOCP_PREFIX."users WHERE person_id = '$person_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($user_id)=$db->fetchRow($result);
         $_SESSION["xocp_user"]->load($user_id);
      }
   }
   
   function app_calcAge($args) {
      $db=&Database::getInstance();
      $dt = $args[0];
      $sql = "SELECT TO_DAYS(now())-TO_DAYS('$dt')";
      $result = $db->query($sql);
      list($days_age)=$db->fetchRow($result);
      return toMoney($days_age/365.25);
   }
   
   function app_deleteTraining($args) {
      $db=&Database::getInstance();
      $training_seq = $args[0];
      $person_id = $_SESSION["hris_employee_person_id"];
      $sql = "DELETE FROM ".XOCP_PREFIX."person_training WHERE person_id = '$person_id' AND training_seq = '$training_seq'";
      $db->query($sql);
   }
   
   function app_saveTraining($args) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

      $db=&Database::getInstance();
      $training_seq = $args[0];
      $person_id = $_SESSION["hris_employee_person_id"];
      
      $arr = parseForm($args[1]);
      
      if(isset($arr["tr_effective"])&&$arr["tr_effective"]==1) {
         $tr_effective = 1;
      } else {
         $tr_effective = 0;
      }
      
      if(isset($arr["tr_report"])&&$arr["tr_report"]==1) {
         $tr_report = 1;
      } else {
         $tr_report = 0;
      }
      
      if(isset($arr["is_planned"])&&$arr["is_planned"]==1) {
         $is_planned = 1;
      } else {
         $is_planned = 0;
      }
      
      $training_subject = $arr["training_subject"];
      $institution = $arr["institution"];
      $start = $arr["htrainingstart"];
      $stop = $arr["htrainingstop"];
      $cost_center = $arr["cost_center"];
      $training_fee = _bctrim(bcadd($arr["training_fee"],0));
      $training_tax = _bctrim(bcadd($arr["training_tax"],0));
      $sql = "UPDATE ".XOCP_PREFIX."person_training SET "
           . "training_subject = '$training_subject',"
           . "institution = '$institution',"
           . "start_dttm = '$start',"
           . "stop_dttm = '$stop',"
           . "training_fee = '$training_fee',"
           . "training_tax = '$training_tax',"
           . "cost_center = '$cost_center',"
           . "is_planned = '$is_planned',"
           . "tr_report = '$tr_report',"
           . "tr_effective = '$tr_effective'"
           . " WHERE person_id = '$person_id'"
           . " AND training_seq = '$training_seq'";
      $db->query($sql);
      
      return array($training_seq,sql2ind($start,"date"),sql2ind($stop,"date"),$institution,$training_subject);
   
   }
   
   function app_editTraining($args) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
      $db=&Database::getInstance();
      $training_seq = $args[0];
      $person_id = $_SESSION["hris_employee_person_id"];

      $ret = "<div style='padding:4px;' id='dvtraining_${training_seq}'><form id='frmtraining'><table style='width:100%;' class='xxfrm'>"
           . "<tbody>";
         
      $sql = "SELECT a.training_subject,a.institution,a.start_dttm,a.stop_dttm,a.cost_center,a.tr_report,a.tr_effective,a.is_planned,a.training_fee,a.training_tax"
           . " FROM ".XOCP_PREFIX."person_training a"
           . " WHERE a.person_id = '$person_id'"
           . " AND a.training_seq = '$training_seq'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($training_subject,$institution,$start_dttm,$stop_dttm,$cost_center,$tr_report,$tr_effective,$is_planned,$training_fee,$training_tax)=$db->fetchRow($result);
         if($start_dttm=="0000-00-00 00:00:00") {
            $start_dttm = getSQLDate();
         }
         if($stop_dttm=="0000-00-00 00:00:00") {
            $stop_dttm = getSQLDate();
         }
         
         $ret .= "<tr><td>#</td><td>$training_seq</td></tr>"
               . "<tr><td>Training Subject</td><td><input name='training_subject' id='training_subject' type='text' style='width:250px;' value='$training_subject'/></td></tr>"
               . "<tr><td>Institution</td><td><input type='text' name='institution' id='institution' value='$institution' style='width:250px;'/></td></tr>"
               . "<tr><td>Start Date</td><td><span class='xlnk' id='trainingstart_txt' onclick='editstarttraining(this,event);'>".sql2ind($start_dttm,"date")."</span></td></tr>"
               . "<tr><td>End Date</td><td><span class='xlnk' id='trainingstop_txt' onclick='editstoptraining(this,event);'>".sql2ind($stop_dttm,"date")."</span></td></tr>"
               . "<tr><td>Cost Center</td><td><input type='text' name='cost_center' id='cost_center' value='$cost_center' style='width:100px;'/></td></tr>"
               . "<tr><td>Fee</td><td><input type='text' name='training_fee' id='training_fee' value='$training_fee' style='width:150px;'/></td></tr>"
               . "<tr><td>Tax</td><td><input type='text' name='training_tax' id='training_tax' value='$training_tax' style='width:150px;'/></td></tr>"
               . "<tr><td>Planned</td><td>"
                  . "<input type='radio' ".($is_planned==1?"":"checked='1'")." name='is_planned' id='is_planned_0' value='0'/><label for='is_planned_0' class='xlnk'> Unplanned</label>&nbsp;&nbsp;"
                  . "<input type='radio' ".($is_planned==1?"checked='1'":"")." name='is_planned' id='is_planned_1' value='1'/><label for='is_planned_1' class='xlnk'> Planned</label>&nbsp;&nbsp;"
               . "</td></tr>"
               . "<tr><td>Report</td><td>"
                  . "<input type='checkbox' name='tr_report' id='tr_report' value='1' ".($tr_report==1?"checked='1'":"")."/>"
               . "</td></tr>"
               . "<tr><td>Effectiveness</td><td>"
                  . "<input type='checkbox' name='tr_effective' id='tr_effective' value='1' ".($tr_effective==1?"checked='1'":"")."/>"
               . "</td></tr>";
      }
         
      $ret .= "<tr><td colspan='2'>"
            . "<input type='hidden' name='htrainingstart' id='htrainingstart' value='$start_dttm'/>"
            . "<input type='hidden' name='htrainingstop' id='htrainingstop' value='$stop_dttm'/>"
            . "<input type='button' value='"._SAVE."' onclick='save_training(\"$training_seq\",this,event);' id='btn'/>&nbsp;"
            . "<input type='button' value='"._DELETE."' id='btn_delete' onclick='delete_training(\"$training_seq\",this,event);'/>"
            . "</td></tr></tbody>"
            . "</table></form></div>";
      
      return $ret;
   }
   
   function app_newTraining($args) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
      $db=&Database::getInstance();
      $employee_id = $_SESSION["hris_employee_id"];
      $person_id = $_SESSION["hris_employee_person_id"];
      $sql = "SELECT MAX(training_seq) FROM ".XOCP_PREFIX."person_training WHERE person_id = '$person_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($training_seq)=$db->fetchRow($result);
         
         $training_seq++;
         
         $sql = "INSERT INTO ".XOCP_PREFIX."person_training (person_id,training_seq)"
              . " VALUES ('$person_id','$training_seq')";
         $db->query($sql);
         $ret = "";
         if($db->errno()==0) {
            $start_dttm = $stop_dttm = getSQLDate();
            $ret .= "<table style='width:100%;'><colgroup><col width='20'/><col/><col width='125'/><col width='130'/><col width='130'/></colgroup>"
                  . "<tbody><tr>"
                  . "<td>$training_seq"
                  . "</td><td id='tdtraining_subject_${training_seq}'><span id='sptraining_${training_seq}' class='xlnk' onclick='edit_training(\"$training_seq\",this,event);'>-</span>"
                  . "</td><td id='tdinstitution_${training_seq}' style='text-align:left;'><div style='width:123px !important;overflow:hidden;'><div id='dvinstitution_${training_seq}' style='width:900px;'>-</div></div>"
                  . "</td><td id='tdtrainingstart_${training_seq}'>".sql2ind($start_dttm_dob,"date")
                  . "</td><td id='tdtrainingstop_${training_seq}'>".sql2ind($stop_dttm_dob,"date")
                  . "</td></tr></tbody></table>"
                  . "<div id='trainingeditor'>".$this->app_editTraining(array($training_seq))."</div>";
            return array($training_seq,$ret);
         } else {
            return "FAIL";
         }
      } else {
         return "FAIL";
      }
   }
   
   //// Education education_seq educlvl ///////////////////////////////////////////////////////////////////////////
   
   function app_deleteEducation($args) {
      $db=&Database::getInstance();
      $education_seq = $args[0];
      $person_id = $_SESSION["hris_employee_person_id"];
      $sql = "DELETE FROM ".XOCP_PREFIX."person_education WHERE person_id = '$person_id' AND education_seq = '$education_seq'";
      $db->query($sql);
   }
   
   function app_saveEducation($args) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
      global $arr_edu;

      $db=&Database::getInstance();
      $education_seq = $args[0];
      $person_id = $_SESSION["hris_employee_person_id"];
      
      $arr = parseForm($args[1]);
      
      $education_nm = $arr["education_nm"];
      $educlvl_cd = $arr["educlvl_cd"];
      $gradeval = $arr["gradeval"];
      $start = $arr["hedustart"];
      $stop = $arr["hedustop"];
      $sql = "UPDATE ".XOCP_PREFIX."person_education SET "
           . "education_nm = '$education_nm',"
           . "educlvl_cd = '$educlvl_cd',"
           . "start_dttm = '$start',"
           . "stop_dttm = '$stop'"
           . " WHERE person_id = '$person_id'"
           . " AND education_seq = '$education_seq'";
           
      $db->query($sql);
      
      return array($education_seq,sql2ind($start,"date"),sql2ind($stop,"date"),$arr_edu[$educlvl_cd],$education_nm);
   
   }
   
   function app_editEducation($args) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
      global $arr_edu;
      $db=&Database::getInstance();
      $education_seq = $args[0];
      $person_id = $_SESSION["hris_employee_person_id"];

      $ret = "<div style='padding:4px;' id='dvedu_${education_seq}'><form id='frmedu'><table style='width:100%;' class='xxfrm'>"
           . "<tbody>";
         
      $sql = "SELECT a.education_nm,a.educlvl_cd,a.start_dttm,a.stop_dttm"
           . " FROM ".XOCP_PREFIX."person_education a"
           . " WHERE a.person_id = '$person_id'"
           . " AND a.education_seq = '$education_seq'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($education_nm,$educlvl_cd,$start_dttm,$stop_dttm)=$db->fetchRow($result);
         if($start_dttm=="0000-00-00 00:00:00") {
            $start_dttm = getSQLDate();
         }
         if($stop_dttm=="0000-00-00 00:00:00") {
            $stop_dttm = getSQLDate();
         }
         
         $opt_level = "";
         foreach($arr_edu as $k=>$v) {
            $opt_level .= "<option value='$k' ".($educlvl_cd==$k?"selected='1'":"").">$v</option>";
         }
         
         $ret .= "<tr><td>#</td><td>$education_seq</td></tr>"
               . "<tr><td>School or Colleges</td><td><input name='education_nm' id='education_nm' type='text' style='width:250px;' value='$education_nm'/></td></tr>"
               . "<tr><td>Level</td><td><select id='educlvl_cd' name='educlvl_cd'>$opt_level</select></td></tr>"
               . "<tr><td>Start Date</td><td><span class='xlnk' id='edustart_txt' onclick='editstartedu(this,event);'>".sql2ind($start_dttm,"date")."</span></td></tr>"
               . "<tr><td>End Date</td><td><span class='xlnk' id='edustop_txt' onclick='editstopedu(this,event);'>".sql2ind($stop_dttm,"date")."</span></td></tr>";
      }
         
      $ret .= "<tr><td colspan='2'>"
            . "<input type='hidden' name='hedustart' id='hedustart' value='$start_dttm'/>"
            . "<input type='hidden' name='hedustop' id='hedustop' value='$stop_dttm'/>"
            . "<input type='button' value='"._SAVE."' onclick='save_edu(\"$education_seq\",this,event);' id='btn'/>&nbsp;"
            . "<input type='button' value='"._DELETE."' id='btn_delete' onclick='delete_edu(\"$education_seq\",this,event);'/>"
            . "</td></tr></tbody>"
            . "</table></form></div>";
      
      return $ret;
   }
   
   function app_newEducation($args) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
      global $arr_edu;
      $db=&Database::getInstance();
      $employee_id = $_SESSION["hris_employee_id"];
      $person_id = $_SESSION["hris_employee_person_id"];
      $sql = "SELECT MAX(education_seq) FROM ".XOCP_PREFIX."person_education WHERE person_id = '$person_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($education_seq)=$db->fetchRow($result);
         
         $education_seq++;
         
         $educlvl_nm = $arr_edu[0];
         
         $sql = "INSERT INTO ".XOCP_PREFIX."person_education (person_id,education_seq)"
              . " VALUES ('$person_id','$education_seq')";
         $db->query($sql);
         $ret = "";
         if($db->errno()==0) {
            $start_dttm = $stop_dttm = getSQLDate();
            $ret .= "<table style='width:100%;'><colgroup><col width='20'/><col/><col width='125'/><col width='130'/><col width='130'/></colgroup>"
                  . "<tbody><tr>"
                  . "<td>$education_seq"
                  . "</td><td id='tdeducation_nm_${education_seq}'><span id='spedu_${education_seq}' class='xlnk' onclick='edit_edu(\"$education_seq\",this,event);'>$education_nm</span>"
                  . "</td><td id='tdeducation_lvl_${education_seq}' style='text-align:left;'>$educlvl_nm"
                  . "</td><td id='tdedustart_${education_seq}'>".sql2ind($start_dttm_dob,"date")
                  . "</td><td id='tdedustop_${education_seq}'>".sql2ind($stop_dttm_dob,"date")
                  . "</td></tr></tbody></table>"
                  . "<div id='edueditor'>".$this->app_editEducation(array($education_seq))."</div>";
            return array($education_seq,$ret);
         } else {
            return "FAIL";
         }
      } else {
         return "FAIL";
      }
   }
   
   function app_saveAddress($args) {
      $db=&Database::getInstance();
      $employee_id = $_SESSION["hris_employee_id"];
      $person_id = $_SESSION["hris_employee_person_id"];
      $arr = parseForm($args[0]);
      
      $sql = "UPDATE ".XOCP_PREFIX."employee SET pbk = '".trim(addslashes($arr["pbk"]))."'"
           . " WHERE employee_id = '$employee_id'";
      $db->query($sql);
      
      $sql = "UPDATE ".XOCP_PREFIX."persons SET "
           . "addr_txt = '".trim(addslashes($arr["addr_txt"]))."',"
           . "regional_cd = '".addslashes($arr["regional_cd"])."',"
           . "zip_cd = '".addslashes($arr["zip_cd"])."',"
           . "country = '".addslashes($arr["country"])."',"
           . "cell_phone = '".addslashes($arr["cell_phone"])."',"
           . "home_phone = '".addslashes($arr["home_phone"])."',"
           . "fax = '".addslashes($arr["fax"])."',"
           . "email = '".trim(addslashes($arr["email"]))."',"
           . "smtp_location = '".trim(addslashes($arr["smtp_location"]))."',"
           
           . "tmp_addr_txt = '".addslashes($arr["tmp_addr_txt"])."',"
           . "tmp_regional_cd = '".addslashes($arr["tmp_regional_cd"])."',"
           . "tmp_zip_cd = '".addslashes($arr["tmp_zip_cd"])."',"
           . "tmp_country = '".addslashes($arr["tmp_country"])."',"
           . "tmp_phone = '".addslashes($arr["tmp_phone"])."',"
           
           . "emergency_person_nm = '".addslashes($arr["emergency_person_nm"])."',"
           . "emergency_occupation = '".addslashes($arr["emergency_occupation"])."',"
           . "emergency_relation = '".addslashes($arr["emergency_relation"])."',"
           . "emergency_addr_txt = '".addslashes($arr["emergency_addr_txt"])."',"
           . "emergency_regional_cd = '".addslashes($arr["emergency_regional_cd"])."',"
           . "emergency_zip_cd = '".addslashes($arr["emergency_zip_cd"])."',"
           . "emergency_country = '".addslashes($arr["emergency_country"])."',"
           . "emergency_phone = '".addslashes($arr["emergency_phone"])."'"
           
           . " WHERE person_id = '$person_id'";
      
      $db->query($sql);
   }
   
   function app_setExitDTTM($args) {
      $db=&Database::getInstance();
      $employee_id = $_SESSION["hris_employee_id"];
      $exit_dttm = getSQLDate($args[0]);
      
      $sql = "UPDATE ".XOCP_PREFIX."employee SET exit_dttm = '$exit_dttm' WHERE status_cd = 'terminated' AND employee_id = '$employee_id'";
      $db->query($sql);
   
   }
   
   function app_setEntranceDTTM($args) {
      $db=&Database::getInstance();
      $employee_id = $_SESSION["hris_employee_id"];
      $entrance_dttm = getSQLDate($args[0]);
      
      $sql = "UPDATE ".XOCP_PREFIX."employee SET entrance_dttm = '$entrance_dttm' WHERE employee_id = '$employee_id'";
      $db->query($sql);
      
      $sql = "SELECT IF(b.exit_dttm='0000-00-00 00:00:00',(TO_DAYS(NOW())-TO_DAYS(b.entrance_dttm)),(TO_DAYS(b.exit_dttm)-TO_DAYS(b.entrance_dttm))) as ln"
           . " FROM ".XOCP_PREFIX."employee b"
           . " WHERE employee_id = '$employee_id'";
      $result = $db->query($sql);
      list($length)=$db->fetchRow($result);
      return number_format($length/365.25,1);
   
   }
   
   function app_Terminate($args) {
      $db=&Database::getInstance();
      $employee_id = $_SESSION["hris_employee_id"];
      $exit_dttm = getSQLDate($args[0]);
      
      $sql = "UPDATE ".XOCP_PREFIX."employee SET exit_dttm = '$exit_dttm', status_cd = 'terminated' WHERE employee_id = '$employee_id'";
      $db->query($sql);
      
      $sql = "SELECT entrance_dttm FROM ".XOCP_PREFIX."employee WHERE employee_id = '$employee_id'";
      $result = $db->query($sql);
      list($entrance_dttm)=$db->fetchRow($result);
      
      if($exit_dttm==""||$exit_dttm == "0000-00-00 00:00:00"||$exit_dttm<=$entrance_dttm) {
         $exit_dttm = getSQLDate();
      }
      
      $ret = "<table style='width:100%;' class='xxfrm'>"
           . "<colgroup><col width='250'/><col/></colgroup>"
           . "<tbody>"
           . "<tr><td>Entrance Date</td><td>"
              . "<span class='xlnk' onclick='showCalEntr(this,event);' id='entrance_dttm_txt'>".sql2ind($entrance_dttm,"date")."</span>"
              . "<input type='hidden' name='entrance_dttm' id='entrance_dttm' value='$entrance_dttm'/>"
           . "</td></tr>"
           . "<tr><td>Termination Date</td><td>"
              . "<span class='xlnk' onclick='showCalExit(this,event);' id='exit_dttm_txt'>".sql2ind($exit_dttm,"date")."</span>"
              . "<input type='hidden' name='exit_dttm' id='exit_dttm' value='$exit_dttm'/>"
           . "</td></tr>"
           . "<tr><td colspan='2' style='font-weight:bold;text-align:center;'>This employee has been terminated.</td></tr>"
           . "</tbody></table>";
      return $ret;
   }
   
   function app_confirmTerminate($args) {
      $db=&Database::getInstance();
      $employee_id = $_SESSION["hris_employee_id"];
      $sql = "SELECT entrance_dttm FROM ".XOCP_PREFIX."employee WHERE employee_id = '$employee_id'";
      $result = $db->query($sql);
      list($entrance_dttm)=$db->fetchRow($result);
      $exit_dttm = getSQLDate();
      $ret = "<div style='background-color:#ffcccc;padding:10px;text-align:center;'>Are you sure you want to terminate this employee?<br/><br/>"
           . "<table align='center'><tbody>"
           . "<tr><td style='text-align:right;'>Entrance Date :</td><td style='text-align:left;'>".sql2ind($entrance_dttm,"date")."</td></tr>"
           . "<tr><td style='text-align:right;'>Termination Date :</td><td style='text-align:left;'><span class='xlnk' onclick='showCalExit(this,event);' id='exit_dttm_txt'>".sql2ind($exit_dttm,"date")."</span></td></tr>"
           . "</tbody></table><input type='hidden' id='exit_dttm' value='$exit_dttm' name='exit_dttm'/>"
           . "<br/>"
           . "<input type='button' value='Terminate' onclick='do_terminate(this,event);'/>&nbsp;"
           . "<input type='button' value='Cancel' onclick='cancel_terminate(this,event);'/>"
           . "</div>";
      return array($exit_dttm,$ret);
   }
   
   function app_deleteJobHistory($args) {
      $db=&Database::getInstance();
      $history_id = $args[0];
      $employee_id = $_SESSION["hris_employee_id"];
      $sql = "DELETE FROM ".XOCP_PREFIX."employee_job_history"
           . " WHERE employee_id = '$employee_id'"
           . " AND history_id = '$history_id'";
      $db->query($sql);
   }
   
   function app_saveJobHistory($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $employee_id = $_SESSION["hris_employee_id"];
      $history_id = $args[0];
      
      $arr = parseForm($args[1]);
      
      $location_id = $arr["hist_slocation"];
      $gradeval = $arr["hist_gradeval"];
      $start = $arr["hist_hstartjob"];
      $stop = $arr["hist_hstopjob"];
      $assignment_t = $arr["assignment_t"];
      $sql = "UPDATE ".XOCP_PREFIX."employee_job_history SET "
           . "location_id = '$location_id',"
           . "gradeval = '$gradeval',"
           . "start_dttm = '$start',"
           . "stop_dttm = '$stop',"
           . "assignment_t = '$assignment_t'"
           . " WHERE employee_id = '$employee_id'"
           . " AND history_id = '$history_id'";
      $db->query($sql);
      
      return array($history_id,sql2ind($start,"date"),sql2ind($stop,"date"),$gradeval,ucfirst($assignment_t));
   }
   
   function app_editJobHistory($args) {
      $db=&Database::getInstance();
      $history_id = $args[0];
      $employee_id = $_SESSION["hris_employee_id"];

      $ret = "<div style='padding:4px;' id='dvjob_${history_id}'><form id='frmjobhistory'><table style='width:100%;' class='xxfrm'>"
           . "<tbody>";
         
      $sql = "SELECT b.job_nm,b.job_cd,a.location_id,"
           . "c.job_class_nm,d.workarea_nm,e.org_nm,f.org_class_nm,"
           . "a.gradeval,a.start_dttm,a.stop_dttm,a.assignment_t,"
           . "a.upper_job_id,a.upper_employee_id,a.assessor_job_id,a.assessor_employee_id"
           . " FROM ".XOCP_PREFIX."employee_job_history a"
           . " LEFT JOIN ".XOCP_PREFIX."jobs b USING(job_id)"
           . " LEFT JOIN ".XOCP_PREFIX."job_class c USING(job_class_id)"
           . " LEFT JOIN ".XOCP_PREFIX."workarea d ON d.workarea_id = b.workarea_id"
           . " LEFT JOIN ".XOCP_PREFIX."orgs e ON e.org_id = b.org_id"
           . " LEFT JOIN ".XOCP_PREFIX."org_class f ON f.org_class_id = e.org_class_id"
           . " WHERE a.employee_id = '$employee_id'"
           . " AND a.history_id = '$history_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($job_nm,$job_cd,$location_id,$job_class_nm,$workarea_nm,
              $org_nm,$org_class_nm,$gradeval,$start_dttm,$stop_dttm,$assignment_t,
              $upper_job_id,$upper_employee_id,$assessor_job_id,$assessor_employee_id)=$db->fetchRow($result);
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
         $ckr_assignment["temporary"] = $ckr_assignment["permanent"] = "";
         $ckr_assignment[$assignment_t] = "checked='1'";
         
         $sql = "SELECT a.employee_id,a.job_id,c.person_nm,d.job_nm,d.job_abbr"
              . " FROM ".XOCP_PREFIX."employee_job a"
              . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
              . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
              . " LEFT JOIN ".XOCP_PREFIX."jobs d ON d.job_id = a.job_id"
              . " LEFT JOIN ".XOCP_PREFIX."job_class e USING(job_class_id)"
              . " WHERE a.employee_id != '$employee_id'"
              . " ORDER BY e.job_class_level";
         $result = $db->query($sql);
         $arr_superior = array();
         $opt_upper = "";
         $opt_assessor = "";
         if($db->getRowsNum($result)>0) {
            while(list($x_employee_id,$x_job_id,$x_person_nm,$x_job_nm,$job_abbr)=$db->fetchRow($result)) {
               $opt_upper .= "<option value='${x_employee_id}_${x_job_id}'>$x_job_nm - $x_person_nm</option>";
            }
         }
         
         $ret .= "<tr><td>Job Title</td><td>$job_nm</td></tr>"
               . "<tr><td>Job Code</td><td>$job_cd</td></tr>"
               . "<tr><td>Position Level</td><td>$job_class_nm</td></tr>"
               . "<tr><td>Grade</td><td><input type='text' style='width:30px;' value='$gradeval' id='hist_gradeval' name='hist_gradeval'/></td></tr>"
               . "<tr><td>Work Area</td><td>$workarea_nm</td></tr>"
               . "<tr><td>Organization</td><td>$org_nm [$org_class_nm]</td></tr>"
               . "<tr><td>Location</td><td><select name='hist_slocation'>$opt</select></td></tr>"
               
               . "<tr><td>Superior</td><td><select name='hist_superior'>$opt_upper</select></td></tr>"
               
               . "<tr><td>Start Datetime</td><td><span class='xlnk' id='hist_startjob_txt' onclick='hist_editstartjob(this,event);'>".sql2ind($start_dttm,"date")."</span></td></tr>"
               . "<tr><td>Stop Datetime</td><td><span class='xlnk' id='hist_stopjob_txt' onclick='hist_editstopjob(this,event);'>".sql2ind($stop_dttm,"date")."</span></td></tr>"
               . "<tr><td>Assignment Status</td><td>"
                  . "<input type='radio' id='ast_permanent' name='assignment_t' value='permanent' $ckr_assignment[permanent]/> <label for='ast_permanent' class='xlnk'>Permanent</label>"
                  . "<input type='radio' id='ast_temporary' name='assignment_t' value='temporary' $ckr_assignment[temporary]/> <label for='ast_temporary' class='xlnk'>Temporary</label>"
               . "</td></tr>";
      }
         
      $ret .= "<tr><td colspan='2'>"
            . "<input type='hidden' name='hist_hstartjob' id='hist_hstartjob' value='$start_dttm'/>"
            . "<input type='hidden' name='hist_hstopjob' id='hist_hstopjob' value='$stop_dttm'/>"
            . "<input type='button' value='"._SAVE."' onclick='save_job_history(\"$history_id\",this,event);' id='btn'/>&nbsp;&nbsp;"
            . "<input type='button' value='"._DELETE."' id='btn_delete' onclick='delete_job_history(\"$history_id\",this,event);'/>"
            . "</td></tr></tbody>"
            . "</table></form></div>";
      
      return $ret;
   }
   
   function app_stopJob($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $employee_id = $_SESSION["hris_employee_id"];
      $job_id = $args[0];
      
      if($args[1]=="terminate") {
         $stop_dttm = getSQLDate($args[2]);
         $sql = "SELECT start_dttm FROM ".XOCP_PREFIX."employee_job WHERE employee_id = '$employee_id' AND job_id = '$job_id'";
         $result = $db->query($sql);
         list($start_dttm)=$db->fetchRow($result);
      } else {
         $arr = parseForm($args[1]);
         $start_dttm = $arr["hstartjob"];
         $stop_dttm = $arr["hstopjob"];
      }
      
      $sql = "SELECT job_abbr,job_nm FROM ".XOCP_PREFIX."jobs WHERE job_id = '$job_id'";
      $result = $db->query($sql);
      list($job_abbr,$job_nm)=$db->fetchRow($result);
      
      $sql = "SELECT location_id,gradeval,assignment_t,upper_job_id,upper_employee_id,assessor_job_id,assessor_employee_id FROM ".XOCP_PREFIX."employee_job WHERE employee_id = '$employee_id' AND job_id = '$job_id'";
      $result = $db->query($sql);
      list($location_id,$gradeval,$assignment_t,$upper_job_id,$upper_employee_id,$assessor_job_id,$assessor_employee_id)=$db->fetchRow($result);
      
      $sql = "SELECT MAX(history_id) FROM ".XOCP_PREFIX."employee_job_history"
           . " WHERE employee_id = '$employee_id'";
      $result = $db->query($sql);
      list($history_id)=$db->fetchRow($result);
      $history_id++;
      
      $sql = "INSERT INTO ".XOCP_PREFIX."employee_job_history (employee_id,history_id,job_id,start_dttm,stop_dttm,gradeval,location_id,assignment_t,created_dttm,created_user_id,upper_job_id,upper_employee_id,assessor_job_id,assessor_employee_id)"
           . " VALUES ('$employee_id','$history_id','$job_id','$start_dttm','$stop_dttm','$gradeval','$location_id','$assignment_t',now(),'$user_id','$upper_job_id','$upper_employee_id','$assessor_job_id','$assessor_employee_id')";
      $db->query($sql);
      
      $sql = "DELETE FROM ".XOCP_PREFIX."employee_job WHERE employee_id = '$employee_id' AND job_id = '$job_id'";
      $db->query($sql);
      
      $ret  = "<table style='width:100%;'><colgroup><col width='80'/><col/><col width='50'/><col width='130'/><col width='130'/></colgroup>"
            . "<tbody><tr><td id='history_job_assignment_t_${history_id}'>"
            . ucfirst($assignment_t)
            . "</td><td>"
            . "<span onclick='edit_job_history(\"$history_id\",this,event);' class='xlnk'>$job_nm</span>"
            . "</td><td id='history_job_grade_${history_id}' style='text-align:center;'>$gradeval"
            . "</td><td id='history_job_start_${history_id}'>"
            . sql2ind($start_dttm,"date")
            . "</td><td id='history_job_stop_${history_id}'>"
            . sql2ind($stop_dttm,"date")
            . "</td></tr></tbody></table>";
      return array($history_id,$ret);
   }
   
   function app_confirmStopJob($args) {
      $db=&Database::getInstance();
      $job_id = $args[0];
      $employee_id = $_SESSION["hris_employee_id"];

      $ret = "<div style='padding:4px;' id='dvjob_${job_id}'><form id='frmjob'><table style='width:100%;' class='xxfrm'>"
           . "<tbody>"
           . "<tr><td colspan='2' style='padding:4px;background-color:#ffcccc;font-weight:bold;text-align:center;'>Are you sure you want to stop this person job?</td></tr>";
      $sql = "SELECT b.job_nm,b.job_cd,a.location_id,"
           . "c.job_class_nm,d.workarea_nm,e.org_nm,f.org_class_nm,"
           . "a.gradeval,a.start_dttm,a.stop_dttm,g.location_cd,g.location_nm,a.assignment_t"
           . " FROM ".XOCP_PREFIX."employee_job a"
           . " LEFT JOIN ".XOCP_PREFIX."jobs b USING(job_id)"
           . " LEFT JOIN ".XOCP_PREFIX."job_class c USING(job_class_id)"
           . " LEFT JOIN ".XOCP_PREFIX."workarea d ON d.workarea_id = b.workarea_id"
           . " LEFT JOIN ".XOCP_PREFIX."orgs e ON e.org_id = b.org_id"
           . " LEFT JOIN ".XOCP_PREFIX."org_class f ON f.org_class_id = e.org_class_id"
           . " LEFT JOIN ".XOCP_PREFIX."location g ON g.location_id = a.location_id"
           . " WHERE a.employee_id = '$employee_id'"
           . " AND a.job_id = '$job_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($job_nm,$job_cd,$location_id,$job_class_nm,$workarea_nm,
              $org_nm,$org_class_nm,$gradeval,$start_dttm,$stop_dttm,$location_cd,$location_nm,$assignment_t)=$db->fetchRow($result);
         if($start_dttm=="0000-00-00 00:00:00") {
            $start_dttm = getSQLDate();
         }
         if($stop_dttm=="0000-00-00 00:00:00") {
            $stop_dttm = getSQLDate();
         }
         $ret .= "<tr><td>Job Title</td><td>$job_nm</td></tr>"
               . "<tr><td>Job Code</td><td>$job_cd</td></tr>"
               . "<tr><td>Position Level</td><td>$job_class_nm</td></tr>"
               . "<tr><td>Grade</td><td>$gradeval</td></tr>"
               . "<tr><td>Work Area</td><td>$workarea_nm</td></tr>"
               . "<tr><td>Organization</td><td>$org_nm [$org_class_nm]</td></tr>"
               . "<tr><td>Location</td><td>$location_cd $location_nm</td></tr>"
               . "<tr><td>Start Datetime</td><td><span class='xlnk' id='startjob_txt' onclick='editstartjob(this,event);'>".sql2ind($start_dttm,"date")."</span></td></tr>"
               . "<tr><td>Stop Datetime</td><td><span class='xlnk' id='stopjob_txt' onclick='editstopjob(this,event);'>".sql2ind($stop_dttm,"date")."</span></td></tr>"
               . "<tr><td>Assignment Status</td><td>".ucfirst($assignment_t)."</td></tr>";
      }
         
      $ret .= "<tr><td colspan='2' style='background-color:#ffcccc;'>"
            . "<input type='hidden' name='hstartjob' id='hstartjob' value='$start_dttm'/>"
            . "<input type='hidden' name='hstopjob' id='hstopjob' value='$stop_dttm'/>"
            . "<input type='button' value='"._YES."' onclick='do_stop_job(\"$job_id\",this,event);'/>&nbsp;"
            . "<input type='button' value='"._NO."' onclick='cancel_stop_job(\"$job_id\",this,event);'/>"
            . "</td></tr></tbody>"
            . "</table></form></div>";
      
      return $ret;
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
         $new_status_txt = "Tidak Aktif";
         $new_status_btn = "Aktifasi";
      } else {
         $sql = "UPDATE ".XOCP_PREFIX."users SET status_cd = 'active'"
              . " WHERE user_id = '$user_id'";
         $new_status_txt = "Aktif";
         $new_status_btn = "De-Aktifasi";
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
      return _hris_class_EmployeeAjax::editLogin($person_id);
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
      return _hris_class_EmployeeAjax::editLogin($person_id);
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
      $user_id = getUserID();
      $person_id = $_SESSION["hris_employee_person_id"];
      $employee_id = $_SESSION["hris_employee_id"];
      $sql = "UPDATE ".XOCP_PREFIX."employee SET status_cd = 'nullified', nullified_dttm = now(), nullified_user_id = '$user_id' WHERE employee_id = '$employee_id'";
      $db->query($sql);
      $sql = "UPDATE ".XOCP_PREFIX."persons SET status_cd = 'nullified', nullified_dttm = now(), nullified_user_id = '$user_id' WHERE person_id = '$person_id'";
      $db->query($sql);
      $sql = "UPDATE ".XOCP_PREFIX."users SET person_id = '0', status_cd = 'nullified'"
           . " WHERE person_id = '$person_id'";
      $db->query($sql);
   }
   
   function app_savePerson($args) {
      $db=&Database::getInstance();
      $arr = parseForm($args[0]);
      
      $telecom = trim($arr["telephone"])."|".trim($arr["fax"])."|".trim($arr["hp"])."|".trim($arr["email"]);
      if($args[1]=="new") {
         $sql = "INSERT INTO ".XOCP_PREFIX."persons (person_nm,status_cd) VALUES ('".$arr["person_nm"]."','normal')";
         $result = $db->query($sql);
         $person_id = $db->getInsertId();
         $sql = "INSERT INTO ".XOCP_PREFIX."employee (status_cd,person_id)"
              . " VALUES ('normal','$person_id')";
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
           . "blood_type = '".$arr["blood_type"]."',"
           . "blood_rhesus = '".$arr["blood_rhesus"]."',"
           . "marital_st = '".$arr["marital_st"]."',"
           . "educlvl_id = '".$arr["education"]."',"
           . "status_cd = 'normal'"
           . " WHERE person_id = '$person_id'";
      $db->query($sql);
      $sql = "UPDATE ".XOCP_PREFIX."employee SET "
           . "employee_ext_id = '".$arr["employee_ext_id"]."',"
           . "alias_nm = '".$arr["alias_nm"]."',"
           . "attendance_id = '".$arr["attendance_id"]."',"
           //. "entrance_dttm = '".$arr["entrance_dttm"]."',"
           . "status_cd = 'normal'"
           . " WHERE person_id = '$person_id'";
      $db->query($sql);

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
      $sql = "SELECT job_nm,job_cd,job_class_id FROM ".XOCP_PREFIX."jobs WHERE job_id = '$job_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($job_nm,$job_cd,$job_class_id)=$db->fetchRow($result);
         
         $sql = "SELECT gradeval_bottom FROM ".XOCP_PREFIX."job_class WHERE job_class_id = '$job_class_id'";
         $result = $db->query($sql);
         if($db->getRowsNum($result)==1) {
            list($gradeval_bottom)=$db->fetchRow($result);
         } else {
            $gradeval_bottom = 0;
         }
         
         $start = getSQLDate();
         $assignment_t = "permanent";
         $sql = "INSERT INTO ".XOCP_PREFIX."employee_job (job_id,employee_id,start_dttm,gradeval,assignment_t)"
              . " VALUES ('$job_id','$employee_id','$start','$gradeval_bottom','$assignment_t')";
         $db->query($sql);
         if($db->errno()==0) {
            $ret  = "<table style='width:100%;'><colgroup><col width='80'/><col/><col width='50'/><col width='130'/><col width='130'/></colgroup>"
                  . "<tbody><tr><td id='active_assignment_t_${job_id}'>"
                  . ucfirst($assignment_t)."</td><td>"
                  . "<span onclick='edit_job(\"$job_id\",this,event);' class='xlnk'>$job_nm</span>"
                  . "</td><td id='active_grade_${job_id}' style='text-align:center;'>$gradeval_bottom"
                  . "</td><td id='active_job_${job_id}'>"
                  . sql2ind($start,"date")
                  . "</td><td>-"
                  . "</td></tr></tbody></table>"
                  . "<div id='jobeditor'>".$this->app_editJob(array($job_id))."</div>";
            return $ret;
         } else {
            if($db->errno()==1062) {
               return "DUPLICATE";
            }
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
      
      list($upper_employee_id,$upper_job_id)=explode("_",$arr["ssuperior"]);
      list($assessor_employee_id,$assessor_job_id)=explode("_",$arr["sassessor"]);
      
      $location_id = $arr["slocation"];
      $gradeval = $arr["gradeval"];
      $start = $arr["hstartjob"];
      $stop = $arr["hstopjob"];
      $assignment_t = $arr["assignment_t"];
      $sql = "UPDATE ".XOCP_PREFIX."employee_job SET "
           . "location_id = '$location_id',"
           . "gradeval = '$gradeval',"
           . "start_dttm = '$start',"
           . "assignment_t = '$assignment_t',"
           . "upper_job_id = '$upper_job_id',"
           . "upper_employee_id = '$upper_employee_id',"
           . "assessor_job_id = '$assessor_job_id',"
           . "assessor_employee_id = '$assessor_employee_id'"
           . " WHERE employee_id = '$employee_id'"
           . " AND job_id = '$job_id'";
      $db->query($sql);
      
      ///// temporary hack
      
      $hackdate = getSQLDate();
      $asid = 0;
      $sql = "SELECT asid FROM ".XOCP_PREFIX."assessment_session WHERE edit_employee = '1'";
      $ra = $db->query($sql);
      
      if($db->getRowsNum($ra)>0) {
         list($asid)=$db->fetchRow($ra);
         
         $sql = "delete from hris_assessor_360 where asid = '$asid' and employee_id = '$employee_id' and assessor_t = 'superior'";
         $db->query($sql);
         $sql = "insert into hris_assessor_360 values ('$asid','$employee_id','$assessor_employee_id','superior','active','$hackdate','1','0000-00-00 00:00:00','0')";
         $db->query($sql);
         
         $sql = "update hris_assessor_360 set status_cd = 'inactive' where asid = '$asid' and assessor_id = '$employee_id' and assessor_t = 'subordinat'";
         $db->query($sql);
         $sql = "replace into hris_assessor_360 values ('$asid','$upper_employee_id','$employee_id','subordinat','active','$hackdate','1','0000-00-00 00:00:00','0')";
         $db->query($sql);
         
         $sql = "REPLACE INTO ".XOCP_PREFIX."assessment_session_job (asid,employee_id,job_id,updated_user_id)"
              . " VALUES ('$asid','$employee_id','$job_id','$user_id')";
         $db->query($sql);
         
            list($emp_job_id,
                 $emp_employee_id,
                 $emp_job_nm,
                 $emp_nm,
                 $emp_nip,
                 $emp_gender,
                 $emp_jobstart,
                 $emp_entrance_dttm,
                 $emp_jobage,
                 $emp_job_summary,
                 $emp_person_id,
                 $emp_user_id,
                 $first_assessor_job_id,
                 $next_assessor_job_id)=_hris_getinfobyemployeeid($employee_id);
            
            list($ass_job_id,
                 $ass_employee_id,
                 $ass_job_nm,
                 $ass_nm,
                 $ass_nip,
                 $ass_gender,
                 $ass_jobstart,
                 $ass_entrance_dttm,
                 $ass_jobage,
                 $ass_job_summary,
                 $ass_person_id,
                 $ass_user_id,
                 $first_assessor_job_id,
                 $next_assessor_job_id)=_hris_getinfobyemployeeid($assessor_employee_id);
            
         _activitylog("PERSONEL_ADMINISTRATION",0,"Update superior assessor $ass_nm for employee $emp_nm");
         
      }
      /////
      
      return array($job_id,sql2ind($start,"date"),$gradeval,ucfirst($assignment_t));
   }
   
   function app_editJob($args) {
      $db=&Database::getInstance();
      $job_id = $args[0];
      $employee_id = $_SESSION["hris_employee_id"];

      $ret = "<div style='padding:4px;' id='dvjob_${job_id}'><form id='frmjob'><table style='width:100%;' class='xxfrm'>"
           . "<colgroup><col width='200'/><col/></colgroup>"
           . "<tbody>";
         
      $sql = "SELECT b.job_nm,b.job_cd,a.location_id,"
           . "c.job_class_nm,d.workarea_nm,e.org_nm,f.org_class_nm,"
           . "a.gradeval,a.start_dttm,a.stop_dttm,a.assignment_t,"
           . "a.upper_job_id,a.upper_employee_id,a.assessor_job_id,a.assessor_employee_id"
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
              $org_nm,$org_class_nm,$gradeval,$start_dttm,$stop_dttm,$assignment_t,
              $upper_job_id,$upper_employee_id,$assessor_job_id,$assessor_employee_id)=$db->fetchRow($result);
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
         $ckr_assignment["temporary"] = $ckr_assignment["permanent"] = "";
         $ckr_assignment[$assignment_t] = "checked='1'";
         
         $sql = "SELECT a.employee_id,a.job_id,c.person_nm,d.job_nm,d.job_abbr"
              . " FROM ".XOCP_PREFIX."employee_job a"
              . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
              . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
              . " LEFT JOIN ".XOCP_PREFIX."jobs d ON d.job_id = a.job_id"
              . " LEFT JOIN ".XOCP_PREFIX."job_class e USING(job_class_id)"
              . " WHERE NOT (a.employee_id = '$employee_id' AND a.job_id = '$job_id')"
              . " ORDER BY e.job_class_level,d.job_abbr";
         $result = $db->query($sql);
         $arr_superior = array();
         $opt_upper = "";
         $opt_assessor = "";
         $job_arr = array();
         $opt_assessor = "<option value='0'>-</option>";
         $opt_upper = "<option value='0'>-</option>";
         if($db->getRowsNum($result)>0) {
            while(list($x_employee_id,$x_job_id,$x_person_nm,$x_job_nm,$x_job_abbr)=$db->fetchRow($result)) {
               $job_arr[$x_job_id] = "$x_job_abbr $x_job_nm";
               $opt_upper .= "\n<option value='${x_employee_id}_${x_job_id}' ".($upper_job_id==$x_job_id&&$upper_employee_id==$x_employee_id?"selected='1'":"").">$x_job_abbr $x_job_nm - $x_person_nm</option>";
               $opt_assessor .= "<option value='${x_employee_id}_${x_job_id}' ".($assessor_job_id==$x_job_id&&$assessor_employee_id==$x_employee_id?"selected='1'":"").">$x_job_abbr $x_job_nm - $x_person_nm</option>";
            }
         }
         
         $sql = "SELECT a.job_id,a.job_abbr,a.job_nm FROM ".XOCP_PREFIX."jobs a"
              . " LEFT JOIN ".XOCP_PREFIX."job_class e USING(job_class_id)"
              . " ORDER BY e.job_class_level,a.job_abbr";
         $result = $db->query($sql);
         if($db->getRowsNum($result)>0) {
            while(list($x_job_id,$x_job_abbr,$x_job_nm)=$db->fetchRow($result)) {
               $opt_upper .= "\n<option value='0_${x_job_id}' ".($upper_job_id==$x_job_id&&$upper_employee_id==0?"selected='1'":"").">$x_job_abbr $x_job_nm</option>";
            }
         }
         
         $ret .= "<tr><td>Job Title</td><td>$job_nm</td></tr>"
               . "<tr><td>Job Code</td><td>$job_cd</td></tr>"
               . "<tr><td>Position Level</td><td>$job_class_nm</td></tr>"
               . "<tr><td>Grade</td><td><input type='text' style='width:30px;' value='$gradeval' id='gradeval' name='gradeval'/></td></tr>"
               . "<tr><td>Work Area</td><td>$workarea_nm</td></tr>"
               . "<tr><td>Organization</td><td>$org_nm [$org_class_nm]</td></tr>"
               . "<tr><td>Location</td><td><select name='slocation'>$opt</select></td></tr>"
               
               . "<tr><td>Superior</td><td><select name='ssuperior'>$opt_upper</select></td></tr>"
               . "<tr><td>Assessor</td><td><select name='sassessor'>$opt_assessor</select></td></tr>"
               
               . "<tr><td>Start Datetime</td><td><span class='xlnk' id='startjob_txt' onclick='editstartjob(this,event);'>".sql2ind($start_dttm,"date")."</span></td></tr>"
               . "<tr><td>Assignment Status</td><td>"
                  . "<input type='radio' id='ast_permanent' name='assignment_t' value='permanent' $ckr_assignment[permanent]/> <label for='ast_permanent' class='xlnk'>Permanent</label>"
                  . "<input type='radio' id='ast_temporary' name='assignment_t' value='temporary' $ckr_assignment[temporary]/> <label for='ast_temporary' class='xlnk'>Temporary</label>"
               . "</td></tr>";
               //. "<tr><td>Generate Assessor</td><td><input type='button' value='Generate' onclick='generate_assessor(\"$employee_id\",this,event);'/>&nbsp;<span id='progress_generate_assessor'></span></td></tr>";
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
           . " WHERE b.employee_ext_id LIKE '".addslashes($qstr)."%'"
           . " AND b.person_id IS NOT NULL"
           . " AND a.status_cd = 'normal'"
           . " GROUP BY a.person_id"
           . " ORDER BY b.employee_ext_id";
      $result = $db->query($sql);
      _debuglog($sql);
      $ret = array();
      if($db->getRowsNum($result)>0) {
         $no = 0;
         while(list($employee_id,$employee_ext_id,$employee_nm,$person_id)=$db->fetchRow($result)) {
            if($no >= 1000) break;
            $ret[$employee_id] = array("$employee_nm ($employee_ext_id)",$person_id);
            $no++;
         }
      }
      
      $sql = "SELECT b.employee_id,b.employee_ext_id,a.person_nm,a.person_id"
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
         while(list($employee_id,$employee_ext_id,$employee_nm,$person_id)=$db->fetchRow($result)) {
            if($no >= 1000) break;
            $ret[$employee_id] = array("$employee_nm ($employee_ext_id)",$person_id);
            $no++;
         }
      }
      
      $qstr = ereg_replace("[[:space:]]+"," ",trim(strtolower($qstr)));
      
      $qstr = formatQueryString($qstr);
      
      $sql = "SELECT b.employee_id,b.employee_ext_id,a.person_nm,a.person_id, MATCH (a.person_nm) AGAINST ('".addslashes($qstr)."' IN BOOLEAN MODE) as score"
           . " FROM ".XOCP_PREFIX."persons a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(person_id)"
           . " WHERE MATCH (a.person_nm) AGAINST ('".addslashes($qstr)."' IN BOOLEAN MODE)"
           . " AND b.person_id IS NOT NULL"
           . " AND a.status_cd = 'normal'"
           . " GROUP BY a.person_id"
           . " ORDER BY score DESC";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         $no = 0;
         while(list($employee_id,$employee_ext_id,$employee_nm,$person_id)=$db->fetchRow($result)) {
            if($no >= 1000) break;
            $ret[$employee_id] = array("$employee_nm ($employee_ext_id)",$person_id);
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
         } else {
            $status_txt = "Inactive";
            $status_btn = "Activate";
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
      


         $ret = "<table class='xxfrm'>"
              . "<colgroup><col width='100'/><col width='200'/><col width='200'/></colgroup>"
              . "<tbody>"
              . "<tr><td>Login</td><td><span class='xlnk'>$user_nm</span></td><td><input type='button' value='Unlink' onclick='unlink_login(this,event);'/></td></tr>"
              . "<tr><td>Password</td><td id='pwd'>$reset</td><td><input type='button' value='"._RESET."' onclick='reset_password(this,event);'/></td></tr>"
              . "<tr><td>Status</td><td id='stt'>$status_txt</td><td><input id='btn_stt' type='button' value='$status_btn' onclick='invert_status(this,event);'/></td></tr>"
              . "<tr><td>Group Akses</td><td id='grp'>$groups</td><td>"
                 . "<input type='button' value='"._ADD."' onclick='add_group(this,event);'/>&nbsp;&nbsp;"
                 . "<input type='button' value='"._DELETE."' onclick='delete_group(this,event);'/></td></tr>"
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

}

} /// HRIS_CLASS_AJAXEMPLOYEE_DEFINED
?>