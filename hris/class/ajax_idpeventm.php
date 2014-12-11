<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_idpeventm.php                   //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_IDPDEVLIBAJAX_DEFINED') ) {
   define('HRIS_IDPDEVLIBAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");

class _hris_class_IDPEventManagementAjax extends AjaxListener {
   
   function _hris_class_IDPEventManagementAjax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/hris/class/ajax_idpeventm.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_Delete","app_editEvent","app_saveEvent",
                            "app_resetSuperior","app_browseCompetency","app_selectCompgroup",
                            "app_addCompetency","app_deleteCompetencyRel","app_selectMethod",
                            "app_createEvent","app_renderEventRequest","app_viewEventRequester",
                            "app_getSubject","app_changeSubject","app_createEventBySubject",
                            "app_searchSubject");
   }
   
   
   function app_searchSubject($args) {
      $db=&Database::getInstance();
      $q = $barcode = $qstr = trim($args[0]);
      $ret = array();
      $cnt = array();
      
      $q = addslashes($q);
      $sql = "SELECT method_id,method_nm FROM ".XOCP_PREFIX."idp_development_method WHERE status_cd = 'normal' AND method_nm LIKE '$q%'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($method_id,$method_nm)=$db->fetchRow($result)) {
            $ret[$method_id] = $method_nm;
            $cnt[$method_id]+=1;
         }
      }
      
      $qstr = ereg_replace("[[:space:]]+"," ",trim(strtolower($qstr)));
      $qstr = formatQueryString($qstr);
      $sql = "SELECT method_id,method_nm,MATCH (method_nm) AGAINST ('$qstr' IN BOOLEAN MODE) as score"
           . " FROM ".XOCP_PREFIX."idp_development_method"
           . " WHERE MATCH (method_nm) AGAINST ('$qstr' IN BOOLEAN MODE)"
           . " AND status_cd = 'normal'"
           . " ORDER BY score";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($method_id,$method_nm)=$db->fetchRow($result)) {
            $ret[$method_id] = $method_nm;
            $cnt[$method_id]+= $score*1;
         }
      }
      
      $nret = array();
      arsort($cnt);
      if(count($cnt)>0) {
         foreach($cnt as $method_idx=>$score) {
            $method_nm = $ret[$method_idx];
            $nret[] = array($method_nm,$method_idx);
         }
         return $nret;
      } else {
         return _EMTPY;
      }
   
   }
   
   
   
   function app_createEventBySubject($args) {
      $db=&Database::getInstance();
      $method_id = $args[0];
      
   }
   
   function app_changeSubject($args) {
      $db=&Database::getInstance();
      $event_id = $args[0];
      $method_id = $args[1];
      if($method_id>0) {
         $sql = "UPDATE ".XOCP_PREFIX."idp_event SET method_id = '$method_id'"
              . " WHERE event_id = '$event_id'";
         $db->query($sql);
      }
      $sql = "SELECT method_nm,method_t FROM ".XOCP_PREFIX."idp_development_method"
           . " WHERE method_id = '$method_id'";
      $result = $db->query($sql);
      list($method_nmx,$method_tx)=$db->fetchRow($result);
      return "$method_id. $method_nmx";
   }
   
   function app_getSubject($args) {
      $db=&Database::getInstance();
      $event_id = $args[0];
      $q = trim(urldecode(trim($args[1])));
      
      if($q!="") {
         $qmethod = " AND method_nm LIKE '%$q%'";
      } else {
         $qmethod = "";
      }
      
      $sql = "SELECT method_id,method_nm FROM ".XOCP_PREFIX."idp_development_method WHERE status_cd = 'normal' $qmethod ORDER BY method_nm";
      $rm = $db->query($sql);
      $methods = array();
      if($db->getRowsNum($rm)>0) {
         while(list($method_id,$method_nm)=$db->fetchRow($rm)) {
            $methods[$method_id] = $method_nm;
         }
      }
      
      $xret = "<div><div style='padding:5px;font-weight:bold;background-color:#ddd;'>"
            . "<table style='width:100%;'><tbody><tr><td>Choose Subject</td>"
            . "<td style='text-align:right;'>Search : <input type='text' style='width:100px;-moz-border-radius:10px;padding-left:5px;padding-right:5px;' id='qsubjectc'/></td></tr></tbody></table>"
            . "</div>"
            . "<div style='max-height:250px;overflow:auto'>";
      foreach($methods as $method_id=>$method_nm) {
         $xret .= "<div class='cb' style='padding:3px;border-bottom:1px solid #ddd;max-width:300px;' onclick='do_change_subject(\"$event_id\",\"$method_id\",this,event);'><span style='font-weight:bold;'>$method_id</span>. $method_nm</div>";
      }
      $xret .= "</div></div>";
      
      
      return $xret;
      
   }
   
   function app_viewEventRequester($args) {
      $db=&Database::getInstance();
      $method_id = $args[0];
      $ret = "";
      $sql = "SELECT d.employee_id,a.event_id,a.actionplan_id,d.status_cd,e.employee_ext_id,f.person_nm,b.rcl,b.ccl,b.gap FROM ".XOCP_PREFIX."idp_request_actionplan a"
           . " LEFT JOIN ".XOCP_PREFIX."idp_request d ON d.request_id = a.request_id"
           . " LEFT JOIN ".XOCP_PREFIX."idp_request_competency b ON b.request_id = a.request_id AND b.competency_id = a.competency_id"
           . " LEFT JOIN ".XOCP_PREFIX."employee e ON e.employee_id = d.employee_id"
           . " LEFT JOIN ".XOCP_PREFIX."persons f ON f.person_id = e.person_id"
           . " WHERE a.method_id = '$method_id'"
           . " AND a.status_cd IN ('requested','approved','implementation')";
      $result = $db->query($sql);
      
      $ret = "<table class='xxlist' align='center'><thead>"
           
             . "<tr><td>"
             
                  . "<table border='0' class='ilist' style='width:100%;'>"
                  . "<colgroup><col width='70'/><col/><col width='50'/><col width='50'/><col width='50'/><col width='150'/></colgroup>"
                  . "<tbody><tr>"
                  . "<td>Emp. ID</td>"
                  . "<td style='text-align:left;'>Employee Name</td>"
                  . "<td style='text-align:center;'></td>"
                  . "<td style='text-align:center;'></td>"
                  . "<td style='text-align:center;'></td>"
                  . "<td style='text-align:center;'>Event Status</td>"
                  . "</tr></tbody></table>"
                  
                  . "</td></tr>"
           . "</thead><tbody id='tbreqs'>";
      
      $cnt_pop = $cnt_none = $cnt_join = $cnt_unconfirmed = 0;
      if($db->getRowsNum($result)>0) {
         while(list($employee_id,$event_id,$actionplan_id,$request_status_cd,$nip,$employee_nm,$rcl,$ccl,$gap)=$db->fetchRow($result)) {
            $cnt_pop++;
            $sql = "SELECT status_cd FROM ".XOCP_PREFIX."idp_event_registration WHERE event_id = '$event_id' AND employee_id = '$employee_id'";
            $re = $db->query($sql);
            if($db->getRowsNum($re)>0) {
               list($event_status)=$db->fetchRow($re);
               switch($event_status) {
                  case "in":
                     $event_status_txt = "Join";
                     $cnt_join++;
                     break;
                  case "out":
                     $event_status_txt = "-";
                     $cnt_none++;
                     break;
                  case "self_registered":
                     $event_status_txt = "Unconfirmed";
                     $cnt_unconfirmed++;
                     break;
                  case "invited":
                     $event_status_txt = "Unconfirmed";
                     $cnt_unconfirmed++;
                     break;
                  default:
                     $event_status_txt = "-";
                     $cnt_none++;
                     break;
               }
            } else {
               $event_status_txt = "None";
               $cnt_none++;
            }
            
            $ret .= "<tr><td>"
                  . "<table border='0' class='ilist' style='width:100%;'>"
                  . "<colgroup><col width='70'/><col/><col width='50'/><col width='50'/><col width='50'/><col width='150'/></colgroup>"
                  . "<tbody><tr>"
                  . "<td>$nip</td>"
                  . "<td style='text-align:left;'>$employee_nm</td>"
                  . "<td style='text-align:center;'>$rclx</td>"
                  . "<td style='text-align:center;'>$cclx</td>"
                  . "<td style='text-align:center;'>$gapx</td>"
                  . "<td style='text-align:center;'>$event_status_txt</td>"
                  . "</tr></tbody></table>"
                  . "</td></tr>";
         }
      }
      
      $ret .= "<tr><td>"
            . "<div>"
            . "<table style='width:100%;'><tbody><tr>"
            . "<td>"
            
            . "<table class='xxfrm'><tbody>"
               . "<tr><td style='text-align:center;' colspan='2'>Event Status Recap</td></tr>"
               . "<tr><td>Total Popularity</td><td style='text-align:center;width:100px;'>$cnt_pop</td></tr>"
               . "<tr><td>Already Join Event</td><td style='text-align:center;width:100px;'>$cnt_join</td></tr>"
               . "<tr><td>Registered but unconfirmed</td><td style='text-align:center;width:100px;'>$cnt_unconfirmed</td></tr>"
               . "<tr><td>Not Join Any Event</td><td style='text-align:center;width:100px;'>$cnt_none</td></tr>"
            . "</tbody></table>"
            
            . "</td><td><input type='button' disabled='1' value='Create Event On This Subject' onclick='create_event_on(\"$method_id\");'/></td>"
            . "</tr></tbody></table>"
            
            . "</div></td></tr>";
      
      $ret .= "</tbody></table>";
      return $ret;
   }
   
   function app_renderEventRequest($args) {
      $db=&Database::getInstance();
      list($xyyyy)=explode("-",getSQLDate());
      $sql = "SELECT request_id,employee_id,approve_superior_id,approve_superior_dttm,approve_hris_id,approve_hris_dttm,cost_estimate,requested_dttm,status_cd"
           . " FROM ".XOCP_PREFIX."idp_request"
           . " AND requested_dttm >= '$xyyyy-01-01 00:00:00'"
           . " ORDER BY requested_dttm";
      $result = $db->query($sql);
      $ret = "";
      $arrmethod = array();
      $method_attr = array();
      if($db->getRowsNum($result)>0) {
         while(list($request_id,$employee_id,$approve_superior_id,$approve_superior_dttm,$approve_hris_id,$approve_hris_dttm,$cost_estimate,$requested_dttm,$status_cd)=$db->fetchRow($result)) {
            if($status_cd=="rejected") continue;
            if($status_cd=="nullified") continue;
            if($status_cd=="completed") continue;
            $sql = "SELECT a.actionplan_id,a.method_id,a.method_t,b.method_nm,c.method_type FROM ".XOCP_PREFIX."idp_request_actionplan a"
                 . " LEFT JOIN ".XOCP_PREFIX."idp_development_method b USING(method_id)"
                 . " LEFT JOIN ".XOCP_PREFIX."idp_development_method_type c ON c.method_t = a.method_t"
                 . " WHERE a.request_id = '$request_id'"
                 . " AND a.method_id>0"
                 . " AND a.status_cd IN ('requested','approved','implementation')";
            $ra = $db->query($sql);
            if($db->getRowsNum($ra)>0) {
               while(list($actionplan_id,$method_id,$method_t,$method_nm,$method_type)=$db->fetchRow($ra)) {
                  $arrmethod[$method_id]++;
                  $method_attr[$method_id] = array($method_nm,$method_type,$method_t);
               }
            }
            
         }
         
         foreach($arrmethod as $method_id=>$pop) {
            list($method_nm,$method_type,$method_t)=$method_attr[$method_id];
            
            $sql = "SELECT COUNT(*) FROM ".XOCP_PREFIX."idp_event"
                 . " WHERE method_id = '$method_id'"
                 . " AND start_dttm > now()";
            
            $sql = "SELECT a.rel_id,a.competency_id,a.rcl_min,a.rcl_max,b.competency_nm,b.competency_abbr"
                 . " FROM ".XOCP_PREFIX."idp_method_competency_rel a"
                 . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
                 . " WHERE a.method_id = '$method_id'"
                 . " ORDER BY a.rel_id DESC";
            $rc = $db->query($sql);
            $complist = "<div style='color:#888;padding:2px;padding-left:20px;font-size:0.9em;'>";
            if($db->getRowsNum($rc)>0) {
               while(list($rel_id,$competency_id,$rcl_min,$rcl_max,$competency_nm,$competency_abbr)=$db->fetchRow($rc)) {
                  $complist .= "<div>$competency_abbr - $competency_nm [$rcl_min-$rcl_max]</div>";
               }
            }
            $complist .= "</div>";
            
            $ret .= "<div style='border-bottom:1px solid #ddd;padding:3px;' id='dvreqmethod_${method_id}'>"
                  . "<table border='0' class='ilist' style='width:100%;'>"
                  . "<colgroup><col/><col width='150'/><col width='50'/><col width='150'/></colgroup>"
                  . "<tbody><tr>"
                  . "<td><span class='xlnk' onclick='view_requester(\"$method_id\",this,event);'>$method_nm</span></td>"
                  . "<td style='text-align:center;'>$method_type</td>"
                  . "<td style='text-align:center;'>$pop</td>"
                  . "<td style='text-align:right;'>-</td>"
                  . "</tr>"
                  . "<tr><td colspan='4'>$complist</td></tr>"
                  . "</tbody></table>"
                  . "</div>";
         
         }
      }
      return $ret;
   }
   
   function app_deleteCompetencyRel($args) {
      $db=&Database::getInstance();
      $rel_id = $args[1];
      $sql = "DELETE FROM ".XOCP_PREFIX."idp_event_competency_rel WHERE rel_id = '$rel_id'";
      $db->query($sql);
   }
   
   function app_addCompetency($args) {
      $db=&Database::getInstance();
      $competency_id = $args[0];
      $event_id = $args[1];
      $sql = "INSERT INTO ".XOCP_PREFIX."idp_event_competency_rel (competency_id,event_id,rcl_min,rcl_max) VALUES ('$competency_id','$event_id','0','4')";
      $db->query($sql);
      $rel_id = $db->getInsertId();
      $sql = "SELECT competency_nm,competency_abbr FROM ".XOCP_PREFIX."competency WHERE competency_id = '$competency_id'";
      $result = $db->query($sql);
      list($competency_nm,$competency_abbr)=$db->fetchRow($result);
      $ret = $this->renderCompetencyUpgrade($rel_id,$competency_abbr,$competency_nm,0,4,TRUE);
      return array($ret,$rel_id);
   }
   
   function app_browseCompetency($args) {
      $db=&Database::getInstance();
      
      $_SESSION["cb_compgroup_id"] = $args[0];
      
      if(!isset($_SESSION["cb_competency_id"])) {
         $_SESSION["cb_competency_id"] = 0;
      }
      if(!isset($_SESSION["cb_compgroup_id"])) {
         $_SESSION["cb_compgroup_id"] = 0;
      }
      if($_SESSION["cb_compgroup_id"]==0) {
         $sql = "SELECT compgroup_id,compgroup_nm,competency_class_set FROM ".XOCP_PREFIX."compgroup WHERE status_cd = 'normal' ORDER BY compgroup_id";
         $result = $db->query($sql);
         $ret = "<div style='padding:3px;font-weight:bold;border-top:1px solid #bbb;background-color:#ccc;text-align:center;color:#000;'>"
              . "Add - Select Group</div>";
         if($db->getRowsNum($result)>0) {
            while(list($compgroup_id,$compgroup_nm,$competency_class_set)=$db->fetchRow($result)) {
               $ret .= "<div class='cb' style='padding:3px;border-top:1px solid #bbb;' onclick='add_comp_select_group(\"$compgroup_id\",this,event);'>"
                     . "<table style='width:100%;border-spacing:0px;'><tbody><tr><td style='text-align:left;'>$compgroup_nm</td>"
                     . "<td style='text-align:right;font-style:italic;'>$competency_class_set</td></tr></tbody></table></div>";
            }
         }
      } else {
         $compgroup_id = $_SESSION["cb_compgroup_id"];
         $sql = "SELECT compgroup_nm FROM ".XOCP_PREFIX."compgroup WHERE status_cd = 'normal' AND compgroup_id = '$compgroup_id'";
         $result = $db->query($sql);
         list($compgroup_nm)=$db->fetchRow($result);
         $sql = "SELECT competency_id,competency_nm,competency_abbr,competency_class FROM ".XOCP_PREFIX."competency WHERE compgroup_id = '$compgroup_id' AND status_cd = 'normal' ORDER BY competency_class,competency_nm";
         $result = $db->query($sql);
         $ret = "<div style='padding:3px;font-weight:bold;border-top:1px solid #bbb;background-color:#ccc;text-align:center;color:#000;'>"
              . "<span style='float:left;cursor:pointer;'><img src='".XOCP_SERVER_SUBDIR."/images/return.gif' onclick='back_compgroup(this,event);'/></span>"
              . "Add - Select Competency $compgroup_nm</div>"
              . "<div style='max-height:300px;overflow:auto;'>";
         if($db->getRowsNum($result)>0) {
            while(list($competency_id,$competency_nm,$competency_abbr,$competency_class)=$db->fetchRow($result)) {
               $ret .= "<div class='cb' style='padding:3px;border-top:1px solid #bbb;' onclick='add_comp_select_competency(\"$compgroup_id\",\"$competency_id\",this,event);'>"
                     . "<table style='width:100%;border-spacing:0px;'><tbody><tr><td style='text-align:left;'>$competency_abbr $competency_nm</td>"
                     . "<td style='text-align:right;font-style:italic;'>$competency_class</td></tr></tbody></table></div>";
            }
         }
         $ret .= "</div>";
         
      }
      return $ret;
   }
   
   
   function renderCompetencyUpgrade($rel_id,$competency_abbr,$competency_nm,$rcl_min,$rcl_max,$strip_header=FALSE) {
      $ret = ($strip_header==TRUE?"":"<div id='comprel_${rel_id}' style='border-top:1px solid #ddd;padding:2px;'>");
      $ret .= "<table style='width:100%;border-spacing:0px;'>"
            . "<colgroup>"
            . "<col width='20'/>"
            . "<col width='70'/>"
            . "<col/>"
            . "<col width='120'/>"
            . "<col width='120'/>"
            . "</colgroup>"
            . "<tbody><tr>"
            . "<td><img src='".XOCP_SERVER_SUBDIR."/images/delete_gray_16.png' style='width:12px;cursor:pointer;' title='"._DELETE."' onclick='delete_comprel(\"${rel_id}\",this,event);'/></td>"
            . "<td>$competency_abbr</td>"
            . "<td>$competency_nm</td>"
            . "<td>RCL Min : <input type='text' style='width:30px;text-align:center;' id='rclmin_${rel_id}' name='rclmin_${rel_id}' value='$rcl_min' onclick='_dsa(this);'/></td>"
            . "<td>RCL Max : <input type='text' style='width:30px;text-align:center;' id='rclmax_${rel_id}' name='rclmax_${rel_id}' value='$rcl_max' onclick='_dsa(this);'/></td>"
            . "</tr></tbod>"
            . "</table>";
      $ret .= ($strip_header==TRUE?"":"</div>");
      return $ret;
   }
   
   
   function app_createEvent($args) {
      $db=&Database::getInstance();
      $method_t = $args[0];
      $method_id = $args[1];
      
      $sql = "SELECT method_nm,institute_id,cost_estimate FROM ".XOCP_PREFIX."idp_development_method"
           . " WHERE method_id = '$method_id'";
      $result = $db->query($sql);
      list($method_nm,$institute_id,$cost_estimate)=$db->fetchRow($result);
      
      $sql = "SELECT method_type FROM ".XOCP_PREFIX."idp_development_method_type WHERE method_t = '$method_t'";
      $resultx = $db->query($sql);
      list($method_type)=$db->fetchRow($resultx);
      
      $sql = "INSERT INTO ".XOCP_PREFIX."idp_event (method_id,method_t,event_title,institute_id,cost_budget,start_dttm,stop_dttm) VALUES ('$method_id','$method_t','$method_nm','$institute_id','$cost_estimate',now(),now())";
      $result = $db->query($sql);
      $event_id = $db->getInsertId();
      
      $event_title = $method_nm;
      
      /*
      $sql = "SELECT competency_id,rcl_min,rcl_max FROM ".XOCP_PREFIX."idp_method_competency_rel WHERE method_id = '$method_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($competency_id,$rcl_min,$rcl_max)=$db->fetchRow($result)) {
            $sql = "INSERT INTO ".XOCP_PREFIX."idp_event_competency_rel (competency_id,event_id,rcl_min,rcl_max) VALUES ('$competency_id','$event_id','$rcl_min','$rcl_max')";
            $db->query($sql);
         }
      }
      */
      
      $sql = "SELECT a.rel_id,a.competency_id,a.rcl_min,a.rcl_max,b.competency_nm,b.competency_abbr"
           . " FROM ".XOCP_PREFIX."idp_event_competency_rel a"
           . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
           . " WHERE a.event_id = '$event_id'"
           . " ORDER BY a.rel_id DESC";
      $rc = $db->query($sql);
      $info = "<div style='color:#888;padding:2px;padding-left:20px;font-size:0.9em;'>";
      if($db->getRowsNum($rc)>0) {
         while(list($rel_id,$competency_id,$rcl_min,$rcl_max,$competency_nm,$competency_abbr)=$db->fetchRow($rc)) {
            $info .= "<div>$competency_abbr - $competency_nm [$rcl_min-$rcl_max]</div>";
         }
      }
      $info .= "<div style='font-weight:bold;'>$institute_nm</div>";
      $info .= "</div>";
      
      
      $ret = "<table border='0' class='ilist' style='width:100%;'>"
           ///. "<colgroup><col/><col/><col width='150'/></colgroup>"
           . "<tbody><tr>"
           . "<td><span onclick='edit_event(\"$event_id\",this,event);' class='xlnk'>".htmlentities(stripslashes($event_title))."</span></td>"
           . "</tr>"
           . "</tbody></table>";
      return array($event_id,$method_id,$ret);
   }
   
   function app_selectMethod($args) {
      $db=&Database::getInstance();
      $method_t = $args[0];
      $method_id = $args[1];
      ////if($method_t=="ALL") {
         $sql = "SELECT method_t,method_type FROM ".XOCP_PREFIX."idp_development_method_type";
         $result = $db->query($sql);
         $mlist = "";
         if($db->getRowsNum($result)>0) {
            while(list($method_tx,$method_typex)=$db->fetchRow($result)) {
               $mlist .= "<div style='padding:3px;border-bottom:1px solid #ddd;cursor:default;'>"
                  . "<table style='width:100%;border-spacing:0px;'><tbody><tr>"
                  ///. "<td style='text-align:left;padding-left:5px;'><span class='xlnk' onclick='select_method(\"$method_tx\",this,event);'>$method_typex</span></td>"
                  . "<td style='text-align:left;padding-left:5px;'><span class='xlnk' onclick='do_create_event(\"$method_tx\",\"$method_id\",this,event);'>$method_typex</span></td>"
                  . "</tr></tbody></table></div>";
            
            }
         }
         
         $sql = "SELECT method_nm FROM ".XOCP_PREFIX."idp_development_method WHERE method_id = '$method_id'";
         $result = $db->query($sql);
         list($method_nm)=$db->fetchRow($result);
         
         $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>Add Event For Subject:</div>"
              . "<div style='padding:7px;text-align:center;background-color:#eee;font-weight:bold;color:#444;border-bottom:1px solid #888;font-size:1.1em;'>$method_nm</div>"
              . "<div id='apformeditor' style='padding:5px;'>"
                  . $mlist
              . "</div>"
              . "<div id='apformbtn' style='text-align:center;padding:10px;'>"
                  . "<input type='button' value='"._CANCEL."' style='' onclick='addeventbox.fade();'/>&nbsp;&nbsp;&nbsp;&nbsp;"
              . "</div>";
         return array($ret,440);
      ///}
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
      $cost_budget_person = _bctrim(bcadd($vars["cost_budget_person"],0));
      $registration_t = $vars["registration_t"];
      $location = addslashes(trim($vars["location"]));
      $max_participant = _bctrim(bcadd($vars["max_participant"],0));
      $min_participant = _bctrim(bcadd($vars["min_participant"],0));
      $institute_id = $vars["sel_institute"];
      
      $comps = _parseForm($args[2]);
      if(is_array($comps)) {
         foreach($comps as $k=>$v) {
            $rel_type = substr($k,0,6);
            $rel_id = substr($k,7);
            if($rel_type=="rclmin") {
               $sql = "UPDATE ".XOCP_PREFIX."idp_event_competency_rel SET rcl_min = '$v' WHERE rel_id = '$rel_id'";
               $db->query($sql);
            }
            if($rel_type=="rclmax") {
               $sql = "UPDATE ".XOCP_PREFIX."idp_event_competency_rel SET rcl_max = '$v' WHERE rel_id = '$rel_id'";
               $db->query($sql);
            }
         }
      }
      
      $sql = "UPDATE ".XOCP_PREFIX."idp_event SET "
           . "event_title = '$event_title',"
           . "event_description = '$event_description',"
           . "start_dttm = '$start_dttm',"
           . "stop_dttm = '$stop_dttm',"
           . "institute_id = '$institute_id',"
           . "registration_t = '$registration_t',"
           . "cost_budget = '$cost_budget',"
           . "cost_budget_person = '$cost_budget_person',"
           . "max_participant = '$max_participant',"
           . "min_participant = '$min_participant',"
           . "location = '$location'"
           . " WHERE event_id = '$event_id'";
      $db->query($sql);
      
      $sql = "SELECT c.method_type FROM ".XOCP_PREFIX."idp_event a"
           . " LEFT JOIN ".XOCP_PREFIX."idp_development_method_type c USING(method_t)"
           . " WHERE a.event_id = '$event_id'";
      $result = $db->query($sql);
      list($method_type)=$db->fetchRow($result);
      
      $sql = "SELECT institute_nm FROM ".XOCP_PREFIX."idp_institutes WHERE institute_id = '$institute_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($institute_nm)=$db->fetchRow($result);
      } else {
         $institute_nm = "";
      }
      
      $sql = "SELECT a.rel_id,a.competency_id,a.rcl_min,a.rcl_max,b.competency_nm,b.competency_abbr"
           . " FROM ".XOCP_PREFIX."idp_event_competency_rel a"
           . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
           . " WHERE a.event_id = '$event_id'"
           . " ORDER BY a.rel_id DESC";
      $rc = $db->query($sql);
      $info = "<div style='color:#888;padding:2px;padding-left:20px;font-size:0.9em;'>";
      if($db->getRowsNum($rc)>0) {
         while(list($rel_id,$competency_id,$rcl_min,$rcl_max,$competency_nm,$competency_abbr)=$db->fetchRow($rc)) {
            $info .= "<div>$competency_abbr - $competency_nm [$rcl_min-$rcl_max]</div>";
         }
      }
      $info .= "<div style='font-weight:bold;'>$institute_nm</div>";
      $info .= "</div>";
      
      
      $ret = "<table border='0' class='ilist' style='width:100%;'>"
           . "<tbody><tr>"
           . "<td><span onclick='edit_event(\"$event_id\",this,event);' class='xlnk'>".htmlentities(stripslashes($event_title))."</span> <span style='font-style:italic;color:#8888ff;'>($method_type)</span></td>"
           . "</tr>"
           . "</tbody></table>";
      
      return array($event_id,"tdclass_${event_id}",$this->app_editEvent(array($event_id)),$ret);
   }
   
   function app_editEvent($args) {
      $db=&Database::getInstance();
      $event_id = $args[0];
      if($event_id=="new") {
         $bypeer = 1;
         $bycustomer = 1;
         $bysubordinate = 1;
         $event_id_val = "";
      } else {
         $sql = "SELECT method_t,method_id,event_id,event_title,event_description,institute_id,start_dttm,stop_dttm,cost_budget,cost_real,status_cd,registration_t,location,"
              . "cost_budget_person,max_participant,min_participant"
              . " FROM ".XOCP_PREFIX."idp_event"
              . " WHERE event_id = '$event_id'";
         $result = $db->query($sql);
         list($method_t,$method_id,$event_id,$event_title,$event_desc,$institute_id,$start_dttm,$stop_dttm,$cost_budget,$cost_real,$status_cd,$registration_t,$location,
              $cost_budget_person,$max_participant,$min_participant)=$db->fetchRow($result);
         $event_title = htmlentities($event_title,ENT_QUOTES);
         $event_desc = htmlentities($event_desc,ENT_QUOTES);
         $event_id_val = $event_id;
         
         $sql = "SELECT a.method_nm,b.method_type FROM ".XOCP_PREFIX."idp_development_method a"
              . " LEFT JOIN ".XOCP_PREFIX."idp_development_method_type b ON b.method_t = '$method_t'"
              . " WHERE a.method_id = '$method_id'";
         $result = $db->query($sql);
         list($method_nm,$method_type)=$db->fetchRow($result);
         
         $sql = "SELECT institute_nm FROM ".XOCP_PREFIX."idp_institutes WHERE institute_id = '$institute_id'";
         $result = $db->query($sql);
         list($institute_nm)=$db->fetchRow($result);
         
         switch($status_cd) {
            case "normal":
               break;
            default:
               $status_cd_txt = "";
               break;
         }
         
         
         $sql = "SELECT institute_nm,institute_id FROM ".XOCP_PREFIX."idp_institutes WHERE status_cd = 'normal' ORDER BY institute_nm";
         $result = $db->query($sql);
         $optin = "<option value=''></option>";
         if($db->getRowsNum($result)>0) {
            while(list($institute_nmx,$institute_idx)=$db->fetchRow($result)) {
               $optin .= "<option value='$institute_idx'".($institute_idx==$institute_id?" selected='1'":"").">$institute_nmx</option>";
            }
         }
         
         $sql = "SELECT a.rel_id,a.competency_id,a.rcl_min,a.rcl_max,b.competency_nm,b.competency_abbr"
              . " FROM ".XOCP_PREFIX."idp_event_competency_rel a"
              . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
              . " WHERE a.event_id = '$event_id'"
              . " ORDER BY a.rel_id DESC";
         $result = $db->query($sql);
         $complist = "";
         if($db->getRowsNum($result)>0) {
            while(list($rel_id,$competency_id,$rcl_min,$rcl_max,$competency_nm,$competency_abbr)=$db->fetchRow($result)) {
               $complist .= $this->renderCompetencyUpgrade($rel_id,$competency_abbr,$competency_nm,$rcl_min,$rcl_max);
            }
            $complist .= "<div id='comprel_empty' style='text-align:center;font-style:italic;color:#999;display:none;'>"._EMPTY."</div>";
         } else {
            $complist .= "<div id='comprel_empty' style='text-align:center;font-style:italic;color:#999;'>"._EMPTY."</div>";
         }
         
         $competency_upgrade = "<tr><td style='background-color:#ddd;' colspan='2'>"
                             . "<table style='width:100%;'>"
                             . "<colgroup>"
                             . "<col/>"
                             . "<col width='50'/>"
                             . "</colgroup>"
                             . "<tbody><tr><td style='text-align:center;font-weight:bold;'>Related Competency Upgrade</td><td><input id='btn_add_competency' onclick='add_browse_competency(this,event);' type='button' value='"._ADD."'/></td></tr></tbody></table></td></tr>"
                             . "<tr><td colspan='2' style='text-align:left;padding:5px;background-color:#fff;'>"
                             . "<div id='complist'>"
                             . $complist
                             . "</div>"
                             . "</td></tr>";
         
         
         $updfrm = ($method_t!="TRN_EX"?"":"<tr><td>Institute</td><td><select id='sel_institute' name='sel_institute'>$optin</select></td></tr>")
               . "<tr><td>Time Frame</td><td>"
                     . "<table><colgroup><col><col width='150'/><col/><col width='150'/></colgroup><tbody><tr>"
                     . "<td>Start :</td><td><span class='xlnk' id='spcstart' onclick='_changedatetime(\"spcstart\",\"start_dttm\",\"date\",true,false);'>".sql2ind($start_dttm,"date")."</span></td>"
                     . "<td>Stop :</td><td><span class='xlnk' id='spcstop' onclick='_changedatetime(\"spcstop\",\"stop_dttm\",\"date\",true,false);'>".sql2ind($stop_dttm,"date")."</span></td>"
                     . "</tr></tbody></table>"
               . "</td></tr>"
               . "<input type='hidden' name='start_dttm' id='start_dttm' value='$start_dttm'/>"
               . "<input type='hidden' name='stop_dttm' id='stop_dttm' value='$stop_dttm'/>"
               . "<tr><td>Location</td><td><input id='inp_location' name='location' style='width:90%;' value='$location'/></td></tr>"
               . "<tr><td>Description</td><td><textarea id='inp_event_description' name='event_description' style='width:90%;height:100px;'/>$event_desc</textarea></td></tr>"
               . "<tr><td>Cost Budget</td><td><input type='text' style='width:100px;' id='cost_budget' name='cost_budget' value='$cost_budget'/></td></tr>"
               . "<tr><td>Cost Budget Per Person</td><td><input type='text' style='width:100px;' id='cost_budget_person' name='cost_budget_person' value='$cost_budget_person'/></td></tr>"
               . "<tr><td>Maximum Participant</td><td><input type='text' style='width:100px;' id='max_participant' name='max_participant' value='$max_participant'/></td></tr>"
               . "<tr><td>Minimum Participant</td><td><input type='text' style='width:100px;' id='min_participant' name='min_participant' value='$min_participant'/></td></tr>"
               . "<tr><td>Type of Registration</td><td>"
                  . "<input type='radio' id='rt_open' name='registration_t' value='open' ".($registration_t=="open"?"checked='1'":"")."/> <label for='rt_open' class='xlnk'>Open</label>"
                  . "<input type='radio' id='rt_invite' name='registration_t' value='invite' ".($registration_t=="invite"?"checked='1'":"")."/> <label for='rt_invite' class='xlnk'>Invite</label>"
               . "</td></tr>"
               . $competency_upgrade;
               //. "<tr><td>Status</td><td>$status_cd_txt</td></tr>";
               
           
      }
      
      $ret = "<table id='frm' class='xxfrm' style='width:100%;'>"
           . "<colgroup><col width='150'/><col/></colgroup>"
           . "<tbody>"
           . "<tr><td>Method</td><td>$method_type</td></tr>"
           . "<tr><td>Subject</td><td><span id='spsubject' class='xlnk' onclick='change_subject(this,event);'>$method_id. $method_nm</span>&nbsp;<img class='cb' alt='Click to select' title='Click to select' src='".XOCP_SERVER_SUBDIR."/images/arrow_down.png' style='vertical-align:middle;cursor:pointer;' onclick='change_subject(this,event);'/></td></tr>"
           . "<tr><td>Event Title</td><td><input type='text' value=\"$event_title\" id='inp_event_title' name='event_title' style='width:90%;'/></td></tr>"
           
           . $updfrm 
           
           . "<tr><td colspan='2'><span id='progressm'></span>&nbsp;<input onclick='save_event();' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input onclick='cancel_edit();' type='button' value='"._CANCEL."'/>"
           . "&nbsp;&nbsp;" . ($event_id!="new"?"<input onclick='delete_action();' type='button' value='"._DELETE."'/>":"")
           . "</td></tr>"
           . "</tbody></table>";
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