<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_idpmyreport.php              //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_IDPDEVLIBAJAX_DEFINED') ) {
   define('HRIS_IDPDEVLIBAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");

class _hris_class_IDPMyReportAjax extends AjaxListener {
   
   function _hris_class_IDPMyReportAjax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/hris/class/ajax_idpmyreport.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_Delete","app_editEvent","app_saveEvent",
                            "app_resetSuperior","app_browseCompetency","app_selectCompgroup",
                            "app_addCompetency","app_deleteCompetencyRel","app_selectMethod",
                            "app_createEvent","app_searchEmployee","app_registerEmployee",
                            "app_editRegistration","app_confirmNotify","app_deleteRegistration",
                            "app_selectUnnotified","app_sendNotification","app_confirmDelete",
                            "app_importFromIDPRequest","app_importSelected");
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
      
      $sql = "SELECT d.person_nm,c.employee_ext_id,b.employee_id,a.event_id,a.request_id,a.actionplan_id"
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
         while(list($employee_nm,$nip,$employee_id,$event_idx,$request_id,$actionplan_id)=$db->fetchRow($result)) {
            $cnt++;
            $mlist .= "<div style='padding:3px;border-bottom:1px solid #ddd;cursor:default;'>"
                     . "<table border='0' class='ilist' style='width:100%;'>"
                     . "<colgroup><col width='40'/><col width='80'/><col/><col width='30'/></colgroup>"
                     . "<tbody><tr>"
                     . "<td style='text-align:center;'>"
                        . ($event_idx==$event_id?"-":"<input type='checkbox' name='importemployee_${employee_id}' value='$request_id|$employee_id|$actionplan_id'/>")
                     . "</td>"
                     . "<td style='text-align:left;'>$nip</td>"
                     . "<td>".htmlentities(stripslashes($employee_nm))."</td>"
                     . "<td style='text-align:center;'>".($event_idx==$event_id?"Registered":"")."</td>"
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
                  $status_cd_txt = "Invitation";
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
            $emplist[] = $employee_id;
            $mlist .= "<div style='padding:3px;border-bottom:1px solid #ddd;cursor:default;'>"
                     . "<table border='0' class='ilist' style='width:100%;'>"
                     . "<colgroup><col width='80'/><col/><col width='30'/></colgroup>"
                     . "<tbody><tr>"
                     . "<td style='text-align:left;'>$nip</td>"
                     . "<td>".htmlentities(stripslashes($employee_nm))."</td>"
                     . "<td style='text-align:center;'></td>"
                     //. "<td style='text-align:center;'>$status_cd_txt</td>"
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
      if(count($emplist)>0) {
         foreach($emplist as $employee_id) {
            if($employee_id>0) {
               $sql = "UPDATE ".XOCP_PREFIX."idp_event_registration SET email_notify_ind = email_notify_ind+1 WHERE event_id = '$event_id' AND employee_id = '$employee_id'";
               $db->query($sql);
            }
         }
      }
   }
   
   function app_selectUnnotified($args) {
      $db=&Database::getInstance();
      $event_id = $args[0];
      $sql = "SELECT employee_id,email_notify_ind FROM ".XOCP_PREFIX."idp_event_registration WHERE event_id = '$event_id'";
      $result = $db->query($sql);
      $ret = array();
      if($db->getRowsNum($result)>0) {
         while(list($employee_id,$email_notify_ind)=$db->fetchRow($result)) {
            if($email_notify_ind>0) continue;
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
                  $status_cd_txt = "Invitation";
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
            $emplist[] = $employee_id;
            $mlist .= "<div style='padding:3px;border-bottom:1px solid #ddd;cursor:default;'>"
                     . "<table border='0' class='ilist' style='width:100%;'>"
                     . "<colgroup><col width='80'/><col/><col width='30'/></colgroup>"
                     . "<tbody><tr>"
                     . "<td style='text-align:left;'>$nip</td>"
                     . "<td>".htmlentities(stripslashes($employee_nm))."</td>"
                     . "<td style='text-align:center;'>$email_notify_ind x</td>"
                     //. "<td style='text-align:center;'>$status_cd_txt</td>"
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
      
      $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>Send E-Mail Notification</div>"
           . "<div style='padding:5px;border-bottom:1px solid #bbb;background-color:#eee;'>Event : $event_title</div>"
           . "<div id='apformeditor' style='padding:5px;overflow:auto;max-height:260px;'>"
               . $mlist
           . "</div>"
           . "<div id='apformbtn' style='text-align:center;padding:10px;'>"
               . ($is_empty==0?"<input type='button' value='Send' style='' onclick='send_email_confirmed(\"$event_id\",this,event);'/>&nbsp;":"")
               . "<input type='button' value='"._CANCEL."' style='' onclick='confirmnotifybox.fade();'/>"
               . "<input type='hidden' id='emplist_h' value='$emplist_h'/>"
           . "</div>";
      return array($ret,min(410,150+($cnt*25)));
   }
   
   
   
   
   function app_editRegistration($args) {
      $db=&Database::getInstance();
      $event_id = $args[0];
      $employee_id = $args[1];
      $sql = "SELECT a.status_cd,b.employee_ext_id,c.person_nm,"
           . "a.email_notify_ind,a.hris_confirm_id,hris_confirm_dttm,a.employee_confirm_dttm"
           . " FROM ".XOCP_PREFIX."idp_event_registration a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " WHERE a.event_id = '$event_id'"
           . " AND a.employee_id = '$employee_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($status_cd,$nip,$employee_nm,$email_notify_ind,$hris_confirm_id,$hris_confirm_dttm,
              $employee_confirm_dttm)=$db->fetchRow($result);
         switch($status_cd) {
            case "invited":
               $status_cd_txt = "Invitation";
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
                  . "<tr><td style='text-align:right;vertical-align:top;'>E-Mail Notification : </td><td style='font-weight:bold;'>$email_notify_ind times</td></tr>"
                  . "</tbody></table>"
              . "</div>"
              . "<div id='actformbtn' style='text-align:right;padding:10px;'>"
                  . "<input type='button' value='Send E-Mail Notification' style='' onclick='send_email_notification(\"$event_id\",\"$employee_id\",this,event);'/>&nbsp;&nbsp;"
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
      $sql = "INSERT INTO ".XOCP_PREFIX."idp_event_registration (event_id,employee_id,request_id,actionplan_id,status_cd)"
           . " VALUES ('$event_id','$employee_id','$request_id','$actionplan_id','invited')";
      $result = $db->query($sql);
      if($db->errno()==1062) {
         return "DUPLICATE";
      }
      
      if($request_id>0&&$actionplan_id>0) {
         $sql = "UPDATE ".XOCP_PREFIX."idp_request_actionplan SET event_id = '$event_id' WHERE request_id = '$request_id' AND actionplan_id = '$actionplan_id'";
         $db->query($sql);
      }
      
      $sql = "SELECT a.status_cd,b.employee_ext_id,c.person_nm"
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
               $status_cd_txt = "Invitation";
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
         
         $email_notify_ind = 0;
         
         $ret = "<table border='0' class='ilist' style='width:100%;'>"
                  . "<colgroup><col width='40'/><col width='80'/><col/><col width='150'/><col width=='150'/></colgroup>"
                  . "<tbody><tr>"
                  . "<td style='text-align:center;'><input type='checkbox' name='ce_${employee_id}' value='$employee_id' id='ckbemp_${event_id}_${employee_id}'/></td>"
                  . "<td style='text-align:left;'>$nip</td>"
                  . "<td><span onclick='edit_reg(\"$event_id\",\"$employee_id\",this,event);' class='xlnk'>".htmlentities(stripslashes($employee_nm))."</span></td>"
                  . "<td style='text-align:center;'>$email_notify_ind x</td>"
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
      
      $sql = "SELECT a.employee_id,a.status_cd,a.email_notify_ind,b.employee_ext_id,c.person_nm"
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
              . "<td><span class='xlnk' onclick='select_emp(this,event);'>Select</span></td>"
              . "<td style='text-align:center;'>Employee ID</td>"
              . "<td>Employee Name</td>"
              . "<td style='text-align:center;'>Notification</td>"
              . "<td style='text-align:center;'>Status</td>"
              . "</tr></tbody></table>"
           
           . "</td></tr>"
           . "</thead><tbody id='tbemplist'>";
      
      if($db->getRowsNum($result)>0) {
         while(list($employee_id,$status_cd,$email_notify_ind,$nip,$employee_nm)=$db->fetchRow($result)) {
            switch($status_cd) {
               case "invited":
                  $status_cd_txt = "Invitation";
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
            $ret .= "<tr><td id='tdemp_${event_id}_${employee_id}'>"
                  . "<table border='0' class='ilist' style='width:100%;'>"
                  . "<colgroup><col width='40'/><col width='80'/><col/><col width='150'/><col width=='150'/></colgroup>"
                  . "<tbody><tr>"
                  . "<td style='text-align:center;'><input type='checkbox' name='ce_${employee_id}' value='$employee_id' id='ckbemp_${event_id}_${employee_id}'/></td>"
                  . "<td style='text-align:left;'>$nip</td>"
                  . "<td><span onclick='edit_reg(\"$event_id\",\"$employee_id\",this,event);' class='xlnk'>".htmlentities(stripslashes($employee_nm))."</span></td>"
                  . "<td style='text-align:center;'>$email_notify_ind x</td>"
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
            . "&nbsp;<input type='button' value='Send E-Mail Notification' onclick='send_email_notification(\"$event_id\",\"A\",this,event);'/>"
            . "&nbsp;<input type='button' value='Delete' onclick='delete_selected(\"$event_id\",\"A\",this,event);'/>"
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