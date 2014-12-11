<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_tsgroupschedule.php                //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_TSGROUPSCHEDULEAJAX_DEFINED') ) {
   define('HRIS_TSGROUPSCHEDULEAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");

class _hris_class_TSGroupScheduleAjax extends AjaxListener {
   
   function _hris_class_TSGroupScheduleAjax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/hris/class/ajax_tsgroupschedule.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_Delete","app_editGroupSchedule","app_saveGroupSchedule",
                                             "app_addSequence","app_changeSchedule","app_saveOverTimeIndicator");
   }
   
   function app_saveOverTimeIndicator($args) {
      $db=&Database::getInstance();
      $ts_group_id = $args[0];
      $ts_sequence_id = $args[1];
      $ot_ind = $args[2];
      $sql = "UPDATE ".XOCP_PREFIX."ts_group_schedule SET ot_ind = '$ot_ind' WHERE ts_group_id = '$ts_group_id' AND ts_sequence_id = '$ts_sequence_id'";
      $db->query($sql);
   }
   
   function app_changeSchedule($args) {
      $db=&Database::getInstance();
      $ts_group_id = $args[0];
      $ts_sequence_id = $args[1];
      $ts_schedule_id = $args[2];
      $sql = "UPDATE ".XOCP_PREFIX."ts_group_schedule SET ts_schedule_id = '$ts_schedule_id' WHERE ts_group_id = '$ts_group_id' AND ts_sequence_id = '$ts_sequence_id'";
      $db->query($sql);
      
      
      $sql = "SELECT ts_schedule_id,ts_schedule_nm,ts_schedule_abbr,ts_start_min_dttm,ts_start_max_dttm,"
           . "ts_stop_min_dttm,ts_stop_max_dttm,ts_constraint,ts_interval,description,"
           . "TIMEDIFF(ts_stop_min_dttm,ts_start_min_dttm) as tmlen"
           . " FROM ".XOCP_PREFIX."ts_schedule"
           . " ORDER BY ts_schedule_id";
      $result = $db->query($sql);
      $arr_schedule = array();
      if($db->getRowsNum($result)>0) {
         while(list($ts_schedule_idxx,$ts_schedule_nm,$ts_schedule_abbr,$ts_start_min_dttm,$ts_start_max_dttm,$ts_stop_min_dttm,$ts_stop_max_dttm,$ts_constraint,$ts_interval,$description,$tmlen)=$db->fetchRow($result)) {
            $arr_schedule[$ts_schedule_idxx] = array($ts_schedule_nm,$ts_schedule_abbr,$ts_start_min_dttm,$ts_start_max_dttm,$ts_stop_min_dttm,$ts_stop_max_dttm,$ts_constraint,$ts_interval,$description,$tmlen);
         }
      }
      
      $sql = "SELECT ts_sequence_id,ts_schedule_id,ot_ind FROM ".XOCP_PREFIX."ts_group_schedule"
           . " WHERE ts_group_id = '$ts_group_id'"
           . " ORDER BY ts_sequence_id";
      $result = $db->query($sql);
      $ttl_tmlen = "00:00:00";
      if($db->getRowsNum($result)>0) {
         while(list($ts_sequence_idxx,$ts_schedule_idxx,$ot_ind)=$db->fetchRow($result)) {
            foreach($arr_schedule as $ts_schedule_idx=>$v) {
               list($ts_schedule_nm,$ts_schedule_abbr,$ts_start_min_dttm,$ts_start_max_dttm,$ts_stop_min_dttm,$ts_stop_max_dttm,$ts_constraint,$ts_interval,$description,$tmlen)=$v;
               if($ts_schedule_idx==$ts_schedule_idxx) {
                  $sql = "SELECT ADDTIME('$ttl_tmlen','$tmlen')";
                  $rtt = $db->query($sql);
                  list($ttl_tmlen)=$db->fetchRow($rtt);
               }
            }
         }
      }
      
      $sql = "SELECT ot_ind FROM ".XOCP_PREFIX."ts_group_schedule WHERE ts_group_id = '$ts_group_id' AND ts_sequence_id = '$ts_sequence_id'";
      $result = $db->query($sql);
      list($ot_ind)=$db->fetchRow($result);
      
      $sql = "SELECT ts_schedule_id,ts_schedule_nm,ts_schedule_abbr,ts_start_min_dttm,ts_start_max_dttm,ts_stop_min_dttm,ts_stop_max_dttm,ts_constraint,ts_interval,description,"
           . "TIMEDIFF(ts_stop_min_dttm,ts_start_min_dttm) as tmlen"
           . " FROM ".XOCP_PREFIX."ts_schedule"
           . " WHERE ts_schedule_id = '$ts_schedule_id'";
      $result = $db->query($sql);
      $arr_schedule = array();
      if($db->getRowsNum($result)>0) {
         list($ts_schedule_idx,$ts_schedule_nm,$ts_schedule_abbr,$ts_start_min_dttm,$ts_start_max_dttm,$ts_stop_min_dttm,$ts_stop_max_dttm,$ts_constraint,$ts_interval,$description,$tmlen)=$db->fetchRow($result);
         $start = substr($ts_start_min_dttm,0,-3)." - ".substr($ts_start_max_dttm,0,-3);
         $stop = substr($ts_stop_min_dttm,0,-3)." - ".substr($ts_stop_max_dttm,0,-3);
         $constraint = $ts_constraint;
         $interval = ($ts_constraint=="interval"?$ts_interval:substr($tmlen,0,-3));
         $ot = "<input type='checkbox' ".($ot_ind==1?"checked='1'":"")." id='ot_ind_${ts_group_id}_${ts_sequence_id}' value='1' onclick='onclick_overtime_indicator(\"$ts_group_id\",\"$ts_sequence_id\",this,event)'/>";
         return array($ts_sequence_id,$start,$stop,$constraint,$interval,$ot,substr($ttl_tmlen,0,-3));
      }
      return array($ts_sequence_id,"-","-","-","-","-",substr($ttl_tmlen,0,-3));
   }
   
   function app_addSequence($args) {
      $db=&Database::getInstance();
      $ts_group_id = $args[0];
      $sql = "SELECT MAX(ts_sequence_id) FROM ".XOCP_PREFIX."ts_group_schedule"
           . " WHERE ts_group_id = '$ts_group_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($ts_sequence_id)=$db->fetchRow($result);
      } else {
         $ts_sequence_id = 0;
      }
      
      $ts_sequence_id++;
      
      $sql = "INSERT INTO ".XOCP_PREFIX."ts_group_schedule (ts_group_id,ts_sequence_id) VALUES ('$ts_group_id','$ts_sequence_id')";
      $db->query($sql);
      
      $sql = "SELECT ts_schedule_id,ts_schedule_nm,ts_schedule_abbr,ts_start_min_dttm,ts_start_max_dttm,ts_stop_min_dttm,ts_stop_max_dttm,ts_constraint,ts_interval,description"
           . " FROM ".XOCP_PREFIX."ts_schedule"
           . " ORDER BY ts_schedule_id";
      $result = $db->query($sql);
      $arr_schedule = array();
      if($db->getRowsNum($result)>0) {
         while(list($ts_schedule_id,$ts_schedule_nm,$ts_schedule_abbr,$ts_start_min_dttm,$ts_start_max_dttm,$ts_stop_min_dttm,$ts_stop_max_dttm,$ts_constraint,$ts_interval,$description)=$db->fetchRow($result)) {
            $arr_schedule[$ts_schedule_id] = array($ts_schedule_nm,$ts_schedule_abbr,$ts_start_min_dttm,$ts_start_max_dttm,$ts_stop_min_dttm,$ts_stop_max_dttm,$ts_constraint,$ts_interval,$description);
         }
      }
      
      $sel = "<select name='' onchange='chg_schedule(\"$ts_group_id\",\"$ts_sequence_id\",this,event);'><option value='0'>Libur</option>";
      foreach($arr_schedule as $ts_schedule_idx=>$v) {
         list($ts_schedule_nm,$ts_schedule_abbr,$ts_start_min_dttm,$ts_start_max_dttm,$ts_stop_min_dttm,$ts_stop_max_dttm,$ts_constraint,$ts_interval,$description)=$v;
         $sel .= "<option value='$ts_schedule_idx'>$ts_schedule_nm</option>";
      }
      $sel .= "</select>";
      
      
      return array($ts_sequence_id,$sel);
   }
   
   function app_saveGroupSchedule($args) {
      $db=&Database::getInstance();
      $ts_group_id = $args[0];
      $vars = _parseForm($args[1]);
      
      if($ts_group_id=="new") {
         $sql = "SELECT MAX(ts_group_id) FROM ".XOCP_PREFIX."ts_group";
         $result = $db->query($sql);
         list($ts_group_idx)=$db->fetchRow($result);
         $ts_group_id = $ts_group_idx+1;
         $user_id = getUserID();
         $sql = "INSERT INTO ".XOCP_PREFIX."ts_group (ts_group_id,ts_group_nm,ts_group_abbr)"
              . " VALUES('$ts_group_id','".addslashes($vars["ts_group_nm"])."','".addslashes($vars["ts_group_abbr"])."')";
         $db->query($sql);
      }
      
      $sql = "UPDATE ".XOCP_PREFIX."ts_group SET "
           . "ts_group_nm = '".addslashes($vars["ts_group_nm"])."',"
           . "ts_group_abbr = '".addslashes($vars["ts_group_abbr"])."',"
           . "description = '".addslashes($vars["description"])."'"
           . " WHERE ts_group_id = '$ts_group_id'";
      $db->query($sql);
      
      $ret = "<table border='0' class='ilist'>"
           . "<colgroup><col width='40'/><col/></colgroup>"
           . "<tbody><tr>"
           . "<td>".$vars["ts_group_abbr"]."</td>"
           . "<td><span id='sp_${ts_group_id}' class='xlnk' onclick='edit_tsgroup(\"$ts_group_id\",this,event);'>".htmlentities($vars["ts_group_nm"])."</span></td>"
           . "</tr></tbody></table>";
      return array("tdtsgroup_${ts_group_id}",$ret);
   }
   
   function app_editGroupSchedule($args) {
      $db=&Database::getInstance();
      $ts_group_id = $args[0];
      $sql = "SELECT description,ts_group_nm,ts_group_abbr"
           . " FROM ".XOCP_PREFIX."ts_group"
           . " WHERE ts_group_id = '$ts_group_id'";
      $result = $db->query($sql);
      list($desc,$ts_group_nm,$ts_group_abbr)=$db->fetchRow($result);
      $ts_group_nm = htmlentities($ts_group_nm,ENT_QUOTES);
      $ts_group_abbr = htmlentities($ts_group_abbr,ENT_QUOTES);
      $desc = htmlentities($desc,ENT_QUOTES);
      
      $sql = "SELECT ts_schedule_id,ts_schedule_nm,ts_schedule_abbr,ts_start_min_dttm,ts_start_max_dttm,"
           . "ts_stop_min_dttm,ts_stop_max_dttm,ts_constraint,ts_interval,description,"
           . "TIMEDIFF(ts_stop_min_dttm,ts_start_min_dttm) as tmlen"
           . " FROM ".XOCP_PREFIX."ts_schedule"
           . " ORDER BY ts_schedule_id";
      $result = $db->query($sql);
      $arr_schedule = array();
      _debuglog($sql);
      if($db->getRowsNum($result)>0) {
         while(list($ts_schedule_id,$ts_schedule_nm,$ts_schedule_abbr,$ts_start_min_dttm,$ts_start_max_dttm,$ts_stop_min_dttm,$ts_stop_max_dttm,$ts_constraint,$ts_interval,$description,$tmlen)=$db->fetchRow($result)) {
            $arr_schedule[$ts_schedule_id] = array($ts_schedule_nm,$ts_schedule_abbr,$ts_start_min_dttm,$ts_start_max_dttm,$ts_stop_min_dttm,$ts_stop_max_dttm,$ts_constraint,$ts_interval,$description,$tmlen);
         }
      }
      
      $ret = "<table class='xxfrm' style='width:100%;'>"
           . "<colgroup>"
           . "<col width='200'/>"
           . "<col width='*'/>"
           . "</colgroup>"
           . "<tbody>"
           . "<tr><td>Work Group Name</td><td>$ts_group_nm</td></tr>"
           . "<tr><td>Abbreviation</td><td>$ts_group_abbr</td></tr>"
           
           . "<tr><td>Description</td><td>$desc</td></tr>"
           . "<tr><td colspan='2'>"
           . "<input onclick='cancel_edit();' type='button' value='"._CANCEL."'/>"
           . "</td></tr>"
           . "</tbody></table>";
      
      //// list of schedule pattern
      $ret .= "<div style='padding:5px;'>"
            . "<div style='padding:5px;border:1px solid #bbb;background-color:#ddd;text-align:center;font-weight:bold;'>"
            . "<table style='width:100%;'><tbody><tr>"
            . "<td style='text-align:left;'>Schedule Pattern</td><td style='text-align:right;'><input type='button' value='Add Sequence' onclick='add_sequence(\"$ts_group_id\",this,event);'/></td>"
            . "</tr></tbody></table>"
            . "</div>"
            . "<div style='padding-top:0px;'>"
            . "<table style='width:100%;' class='tsgs'>"
            . "<colgroup>"
            . "</colgroup>"
            . "<thead>"
            . "<tr>"
            . "<td>#SEQ</td>"
            . "<td>Schedule</td>"
            . "<td>Start</td>"
            . "<td>Stop</td>"
            . "<td>Constraint</td>"
            . "<td>Interval</td>"
            . "<td>OT</td>"
            . "</tr>"
            . "</thead><tbody id='seqbody'>";
      $sql = "SELECT ts_sequence_id,ts_schedule_id,ot_ind FROM ".XOCP_PREFIX."ts_group_schedule"
           . " WHERE ts_group_id = '$ts_group_id'"
           . " ORDER BY ts_sequence_id";
      $result = $db->query($sql);
      $ttl_tmlen = "00:00:00";
      if($db->getRowsNum($result)>0) {
         while(list($ts_sequence_id,$ts_schedule_id,$ot_ind)=$db->fetchRow($result)) {
            $sched .= "";
            reset($arr_schedule);
            $ret .= "<tr id='trseq_${ts_sequence_id}''><td style='text-align:center;'>$ts_sequence_id</td>"
                  . "<td><select name='' onchange='chg_schedule(\"$ts_group_id\",\"$ts_sequence_id\",this,event);'><option value='0'>Libur</option>";
            $start_min = "";
            $start_max = "";
            $stop_min = "";
            $stop_max = "";
            if($ts_schedule_id==0) {
               $start = "-";
               $stop = "-";
               $constraint = "-";
               $interval = "-";
               $ot = "-";
            } 
            foreach($arr_schedule as $ts_schedule_idx=>$v) {
               list($ts_schedule_nm,$ts_schedule_abbr,$ts_start_min_dttm,$ts_start_max_dttm,$ts_stop_min_dttm,$ts_stop_max_dttm,$ts_constraint,$ts_interval,$description,$tmlen)=$v;
               $ret .= "<option value='$ts_schedule_idx' ".($ts_schedule_idx==$ts_schedule_id?"selected='1'":"").">$ts_schedule_nm</option>";
               if($ts_schedule_idx==$ts_schedule_id) {
                  $start = substr($ts_start_min_dttm,0,-3)." - ".substr($ts_start_max_dttm,0,-3);
                  $stop = substr($ts_stop_min_dttm,0,-3)." - ".substr($ts_stop_max_dttm,0,-3);
                  $constraint = $ts_constraint;
                  $interval = ($ts_constraint=="interval"?$ts_interval:substr($tmlen,0,-3));
                  $sql = "SELECT ADDTIME('$ttl_tmlen','$tmlen')";
                  $rtt = $db->query($sql);
                  list($ttl_tmlen)=$db->fetchRow($rtt);
                  $ot = "<input type='checkbox' ".($ot_ind==1?"checked='1'":"")." id='ot_ind_${ts_group_id}_${ts_sequence_id}' value='1' onclick='onclick_overtime_indicator(\"$ts_group_id\",\"$ts_sequence_id\",this,event)'/>";
               }
            }
            $ret .= "</select></td>"
                  . "<td style='text-align:center;'>$start</td>"
                  . "<td style='text-align:center;'>$stop</td>"
                  . "<td style='text-align:center;'>$constraint</td>"
                  . "<td style='text-align:center;'>$interval</td>"
                  . "<td style='text-align:center;'>$ot</td>"
                  . "</tr>";
         }
         $ret .= "<tr id='tr_empty'><td colspan='7' style='font-style:italic;text-align:center;display:none;'>"._EMPTY."</td></tr>";
      } else {
         $ret .= "<tr id='tr_empty'><td colspan='7' style='font-style:italic;text-align:center;'>"._EMPTY."</td></tr>";
         $ttl_tmlen = 0;
      }
      $ret .= "<tr>"
            . "<td colspan='5'>Total</td>"
            . "<td id='ttl_tmlen'>".substr($ttl_tmlen,0,-3)."</td>"
            . "<td>&nbsp;</td>"
            . "</tr>";
      $ret .= "</tbody></table>";
      $ret .= "</div></div>";
      return $ret;
   }
   
   function app_Delete($args) {
      $db=&Database::getInstance();
      $ts_group_id = $args[0];
      $sql = "DELETE FROM ".XOCP_PREFIX."ts_group WHERE ts_group_id = '$ts_group_id'";
      $db->query($sql);
   }
   
}

} /// HRIS_TSGROUPSCHEDULEAJAX_DEFINED
?>