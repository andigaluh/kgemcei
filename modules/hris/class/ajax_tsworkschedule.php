<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_tsworkschedule.php                //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_TSWORKSCHEDULEAJAX_DEFINED') ) {
   define('HRIS_TSWORKSCHEDULEAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");

class _hris_class_TSWorkScheduleAjax extends AjaxListener {
   
   function _hris_class_TSWorkScheduleAjax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/hris/class/ajax_tsworkschedule.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_Delete","app_editWorkSchedule","app_saveWorkSchedule");
   }
   
   function app_saveWorkSchedule($args) {
      $db=&Database::getInstance();
      $ts_schedule_id = $args[0];
      $vars = _parseForm($args[1]);
      
      if($ts_schedule_id=="new") {
         $sql = "SELECT MAX(ts_schedule_id) FROM ".XOCP_PREFIX."ts_schedule";
         $result = $db->query($sql);
         list($ts_schedule_idx)=$db->fetchRow($result);
         $ts_schedule_id = $ts_schedule_idx+1;
         $user_id = getUserID();
         $sql = "INSERT INTO ".XOCP_PREFIX."ts_schedule (ts_schedule_id,ts_schedule_nm,ts_schedule_abbr)"
              . " VALUES('$ts_schedule_id','".addslashes($vars["ts_schedule_nm"])."','".addslashes($vars["ts_schedule_abbr"])."')";
         $db->query($sql);
      }
      
      $sql = "UPDATE ".XOCP_PREFIX."ts_schedule SET "
           . "ts_schedule_nm = '".addslashes($vars["ts_schedule_nm"])."',"
           . "ts_schedule_abbr = '".addslashes($vars["ts_schedule_abbr"])."',"
           . "ts_constraint = '".$vars["ts_constraint"]."',"
           . "ts_interval = '".($vars["ts_constraint"]=="interval"?$vars["ts_interval"]:"")."',"
           . "ts_start_min_dttm = '".$vars["ts_start_min_dttm"]."',"
           . "ts_start_max_dttm = '".$vars["ts_start_max_dttm"]."',"
           . "ts_stop_min_dttm = '".$vars["ts_stop_min_dttm"]."',"
           . "ts_stop_max_dttm = '".$vars["ts_stop_max_dttm"]."',"
           . "description = '".addslashes($vars["description"])."'"
           . " WHERE ts_schedule_id = '$ts_schedule_id'";
      $db->query($sql);
      
      $ret = "<table border='0' class='ilist'>"
           . "<colgroup><col width='40'/><col/></colgroup>"
           . "<tbody><tr>"
           . "<td>".$vars["ts_schedule_abbr"]."</td>"
           . "<td><span id='sp_${ts_schedule_id}' class='xlnk' onclick='edit_tsschedule(\"$ts_schedule_id\",this,event);'>".htmlentities($vars["ts_schedule_nm"])."</span></td>"
           . "</tr></tbody></table>";
      return array("tdtsschedule_${ts_schedule_id}",$ret);
   }
   
   function app_editWorkSchedule($args) {
      $db=&Database::getInstance();
      $ts_schedule_id = $args[0];
      if($ts_schedule_id=="new") {
         $ts_start_min_dttm = "00:00";
         $ts_start_max_dttm = "00:00";
         $ts_stop_min_dttm = "00:00";
         $ts_stop_max_dttm = "00:00";
         $ts_constraint = "time";
         $ts_interval = "00:00";
      } else {
         $sql = "SELECT description,ts_schedule_nm,ts_schedule_abbr,ts_start_min_dttm,ts_start_max_dttm,ts_stop_min_dttm,ts_stop_max_dttm,ts_constraint,ts_interval"
              . " FROM ".XOCP_PREFIX."ts_schedule"
              . " WHERE ts_schedule_id = '$ts_schedule_id'";
         $result = $db->query($sql);
         list($desc,$ts_schedule_nm,$ts_schedule_abbr,$ts_start_min_dttm,$ts_start_max_dttm,$ts_stop_min_dttm,$ts_stop_max_dttm,$ts_constraint,$ts_interval)=$db->fetchRow($result);
         $ts_schedule_nm = htmlentities($ts_schedule_nm,ENT_QUOTES);
         $ts_schedule_abbr = htmlentities($ts_schedule_abbr,ENT_QUOTES);
         $desc = htmlentities($desc,ENT_QUOTES);
         $ts_start_min_dttm = substr($ts_start_min_dttm,0,-3);
         $ts_start_max_dttm = substr($ts_start_max_dttm,0,-3);
         $ts_stop_min_dttm = substr($ts_stop_min_dttm,0,-3);
         $ts_stop_max_dttm = substr($ts_stop_max_dttm,0,-3);
      }
      
      $ret = "<form id='frm'><table class='xxfrm'><tbody>"
           . "<tr><td>Work Schedule Name</td><td><input type='text' value=\"$ts_schedule_nm\" id='inp_tsschedule_nm' name='ts_schedule_nm' style='width:400px;'/></td></tr>"
           . "<tr><td>Abbreviation</td><td><input type='text' value=\"$ts_schedule_abbr\" id='inp_tsschedule_abbr' name='ts_schedule_abbr' style='width:70px;'/></td></tr>"
           . "<tr><td>Constraint</td><td>"
               . "<select name='ts_constraint' onchange='chg_constraint(this,event)'>"
                  . "<option value='no' ".($ts_constraint=="no"?"selected='1'":"").">no constraint</option>"
                  . "<option value='interval' ".($ts_constraint=="interval"?"selected='1'":"").">interval</option>"
                  . "<option value='time' ".($ts_constraint=="time"?"selected='1'":"").">time</option>"
               . "</select>"
           . "</td></tr>"
           
           . "<tr id='trinterval' ".($ts_constraint=="interval"?"":"style='display:none;'")."><td>Interval</td><td><input type='text' id='ts_interval' name='ts_interval' style='width:70px;text-align:center;' value='$ts_interval'/> <span style='font-style:italic;color:blue;'>[hh:mm]</span></td></tr>"
           
           . "<tr><td>Start Time</td><td>"
               . "<table style='width:100%;border-spacing:0px;'><colgroup><col width='50%'/><col width='50%'/></colgroup><tbody><tr>"
                  . "<td>Minimum : <input type='text' style='width:100px;text-align:center;' name='ts_start_min_dttm' id='ts_start_min_dttm' value='$ts_start_min_dttm'/></td>"
                  . "<td>Maximum : <input type='text' style='width:100px;text-align:center;' name='ts_start_max_dttm' id='ts_start_max_dttm' value='$ts_start_max_dttm'/></td>"
               . "</tr></tbody></table>"
           . "</td></tr>"
           
           . "<tr><td>Stop Time</td><td>"
               . "<table style='width:100%;border-spacing:0px;'><colgroup><col width='50%'/><col width='50%'/></colgroup><tbody><tr>"
                  . "<td>Minimum : <input type='text' style='width:100px;text-align:center;' name='ts_stop_min_dttm' id='ts_stop_min_dttm' value='$ts_stop_min_dttm'/></td>"
                  . "<td>Maximum : <input type='text' style='width:100px;text-align:center;' name='ts_stop_max_dttm' id='ts_stop_max_dttm' value='$ts_stop_max_dttm'/></td>"
               . "</tr></tbody></table>"
           . "</td></tr>"
           
           . "<tr><td>Description</td><td><textarea id='description' name='description' style='width:400px;'>$desc</textarea></td></tr>"
           . "<tr><td colspan='2'><input onclick='save_tsschedule();' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input onclick='cancel_edit();' type='button' value='"._CANCEL."'/>&nbsp;&nbsp;"
           . ($ts_schedule_id!="new"?"<input onclick='delete_tsschedule();' type='button' value='"._DELETE."'/>":"")
           . "</td></tr>"
           . "</tbody></table></form>";
      return $ret;
   }
   
   function app_Delete($args) {
      $db=&Database::getInstance();
      $ts_schedule_id = $args[0];
      $sql = "DELETE FROM ".XOCP_PREFIX."ts_schedule WHERE ts_schedule_id = '$ts_schedule_id'";
      $db->query($sql);
   }
   
}

} /// HRIS_TSWORKSCHEDULEAJAX_DEFINED
?>