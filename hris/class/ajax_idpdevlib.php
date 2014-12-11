<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_idpmethodclass.php              //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_IDPDEVLIBAJAX_DEFINED') ) {
   define('HRIS_IDPDEVLIBAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");
require_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");

class _hris_class_IDPDevelopmentLibraryAjax extends AjaxListener {
   
   function _hris_class_IDPDevelopmentLibraryAjax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/hris/class/ajax_idpdevlib.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_Delete","app_editMethod","app_saveAction",
                            "app_resetSuperior","app_browseCompetency","app_selectCompgroup",
                            "app_addCompetency","app_deleteCompetencyRel","app_eventList",
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
   
   function app_eventList($args) {
      $db=&Database::getInstance();
      $method_id = $args[0];
      $sql = "SELECT a.event_title,a.event_description,a.start_dttm,a.stop_dttm,b.institute_nm"
           . " FROM ".XOCP_PREFIX."idp_event a"
           . " LEFT JOIN ".XOCP_PREFIX."idp_institutes b USING(institute_id)"
           . " WHERE a.method_id = '$method_id'"
           . " AND a.status_cd IN ('normal','registration','started')"
           . " ORDER BY a.event_title";
      $result = $db->query($sql);
      $ret = "";
      if($db->getRowsNum($result)>0) {
         while(list($event_title,$event_description,$start_dttm,$stop_dttm,$institute_nm)=$db->fetchRow($result)) {
            $ret .= "<div style='padding:3px;border-bottom:1px solid #bbb;'>$event_title</div>";
         }
      }
      
      return "<div style='padding:10px;'>"
           . "<div style='padding:2px;border:1px solid #aaa;-moz-border-radius:5px;'>"
           . "<div style='padding:5px;text-align:center;background-color:#eee;border-bottom:1px solid #bbb;font-weight:bold;-moz-border-radius:5px 5px 0 0;'>Event with the same subject:</div>"
           . "<div>$ret</div>"
           . "<div style='padding:5px;text-align:right;background-color:#eee;font-weight:bold;-moz-border-radius:0 0 5px 5px;'><input onclick='new_event(\"$method_id\");' type='button' value='New Event'/></div>"
           . "</div>"
           . "</div>";
   }
   
   function app_deleteCompetencyRel($args) {
      $db=&Database::getInstance();
      $rel_id = $args[1];
      $sql = "DELETE FROM ".XOCP_PREFIX."idp_method_competency_rel WHERE rel_id = '$rel_id'";
      $db->query($sql);
   }
   
   function app_addCompetency($args) {
      $db=&Database::getInstance();
      $competency_id = $args[0];
      $method_id = $args[1];
      $sql = "INSERT INTO ".XOCP_PREFIX."idp_method_competency_rel (competency_id,method_id,rcl_min,rcl_max) VALUES ('$competency_id','$method_id','0','4')";
      $db->query($sql);
      $rel_id = $db->getInsertId();
      $sql = "SELECT competency_nm,competency_abbr FROM ".XOCP_PREFIX."competency WHERE competency_id = '$competency_id'";
      $result = $db->query($sql);
      list($competency_nm,$competency_abbr)=$db->fetchRow($result);
      $ret = $this->renderCompetencyUpgrade($rel_id,$competency_abbr,$competency_nm,0,4,TRUE);
      return array($ret,$rel_id);
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
            . "<td>RCL min : <input type='text' style='width:30px;text-align:center;' id='rclmin_${rel_id}' name='rclmin_${rel_id}' value='$rcl_min' onclick='_dsa(this);'/></td>"
            . "<td>RCL max : <input type='text' style='width:30px;text-align:center;' id='rclmax_${rel_id}' name='rclmax_${rel_id}' value='$rcl_max' onclick='_dsa(this);'/></td>"
            . "</tr></tbod>"
            . "</table>";
      $ret .= ($strip_header==TRUE?"":"</div>");
      return $ret;
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
   
   function app_saveAction($args) {
      $db=&Database::getInstance();
      $method_t = $_SESSION["hris_method_t"];
      $method_id = $args[0];
      $method_nm = addslashes(trim($args[1]));
      $method_description_txt = trim(urldecode($args[2]));
      $method_description = addslashes($method_description_txt);
      if($method_nm=="") $method_nm = "?";
      
      if($method_id=="new") {
         $sql = "INSERT INTO ".XOCP_PREFIX."idp_development_method (method_nm)"
              . " VALUES('$method_nm')";
         $db->query($sql);
         $method_id = $db->getInsertId();
      }
      $sql = "UPDATE ".XOCP_PREFIX."idp_development_method SET "
           . "method_nm = '$method_nm', method_description = '$method_description',"
           . "institute_id = '$institute_id'"
           . " WHERE method_id = '$method_id'";
      $db->query($sql);
      
      $ret .= "<table border='0' class='ilist'>"
            . "<colgroup><col/></colgroup>"
            . "<tbody><tr>"
            . "<td><span onclick='edit_method(\"$method_id\",this,event);' class='xlnk'>".htmlentities(stripslashes($method_nm))."</span></td>"
            . "</tr>"
            . "<tr><td><div style='color:#888;padding:2px;padding-left:20px;font-size:0.9em;'>$method_description_txt</div></td></tr>"
            . "</tbody></table>";
      
      return array($method_id,"tdclass_${method_id}",$this->app_editMethod(array($method_id)),$ret);
      
      
      return array("tdclass_${method_id}",$ret);
   }
   
   function app_editMethod($args) {
      $db=&Database::getInstance();
      $method_id = $args[0];
      $method_t = $_SESSION["hris_method_t"];
      if($method_id=="new") {
         $bypeer = 1;
         $bycustomer = 1;
         $bysubordinate = 1;
         $method_id_val = "";
      } else {
         $sql = "SELECT method_id,method_nm,method_description,institute_id"
              . " FROM ".XOCP_PREFIX."idp_development_method"
              . " WHERE method_id = '$method_id'";
         $result = $db->query($sql);
         list($method_id,$method_nm,$method_desc,$institute_id)=$db->fetchRow($result);
         $method_nm = htmlentities($method_nm,ENT_QUOTES);
         $method_desc = htmlentities($method_desc,ENT_QUOTES);
         $method_id_val = $method_id;
         
         
      }
      
      $ret = "<form id='frm'><table class='xxfrm' style='width:100%;'>"
           . "<colgroup><col width='220'/><col/></colgroup>"
           . "<tbody>"
           . "<tr><td>Training/Action Name</td><td><input onkeydown='kdnm(this,event);' type='text' value=\"$method_nm\" id='inp_method_nm' name='method_nm' style='width:90%;'/></td></tr>"
            
           . "<tr><td>Description</td><td><textarea id='inp_method_desc' name='method_desc' style='width:90%;height:100px;'/>$method_desc</textarea></td></tr>"
           
           . "<tr><td colspan='2'>"
           
           . "<table style='width:100%;'><tbody><tr><td style='text-align:left;'>"
           . "</td><td>"
           . "<span id='progressm'></span>&nbsp;<input onclick='save_subject();' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input onclick='cancel_edit_subject();' type='button' value='"._CANCEL."'/>"
           . "&nbsp;&nbsp;" . ($method_id!="new"?"<input onclick='delete_subject();' type='button' value='"._DELETE."'/>":"")
           . "</td></tr></tbody></table>"
           . "</td></tr>"
           . "</tbody></table></form>";
      
      $sql = "SELECT a.event_id,a.event_title,a.event_description,a.start_dttm,a.stop_dttm,b.institute_nm"
           . " FROM ".XOCP_PREFIX."idp_event a"
           . " LEFT JOIN ".XOCP_PREFIX."idp_institutes b USING(institute_id)"
           . " WHERE a.method_id = '$method_id'"
           . " AND a.status_cd IN ('normal','registration','started')"
           . " ORDER BY a.event_title";
      $result = $db->query($sql);
      $el = "";
      if($db->getRowsNum($result)>0) {
         while(list($event_id,$event_title,$event_description,$start_dttm,$stop_dttm,$institute_nm)=$db->fetchRow($result)) {
            $sql = "SELECT a.competency_id,b.competency_nm,b.competency_abbr"
                 . " FROM ".XOCP_PREFIX."idp_event_competency_rel a"
                 . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
                 . " WHERE a.event_id = '$event_id'"
                 . " ORDER BY b.compgroup_id,b.competency_class";
            $rc = $db->query($sql);
            $complist = "";
            if($db->getRowsNum($rc)>0) {
               while(list($competency_id,$competency_nm,$competency_abbr)=$db->fetchRow($rc)) {
                  $complist .= "<span title='$competency_nm'>$competency_abbr</span>&nbsp;";
               }
            }
            $el .= "<div style='padding:3px;border-bottom:1px solid #bbb;'>"
                 . "<a href='".XOCP_SERVER_SUBDIR."/index.php?XP_idpeventm_hris="._HRIS_IDPEVENTMANAGEMENT_BLOCK."&editeventsubject=y&event_id=$event_id&method_id=${method_id}'>$event_title</a>"
                 . "<div style='font-size:0.9em;padding:3px;padding-left:20px;'>"
                 . "<div><table><tbody><tr><td>Institute :</td><td>$institute_nm</td></tr>"
                 . "<tr><td>Timeframe :</td><td>".sql2ind($start_dttm,"date")." - ".sql2ind($stop_dttm,"date")."</td></tr>"
                 . "<tr><td>Competency :</td><td>$complist</td></tr>"
                 . "</tbody></table>"
                 . "</div>"
                 . "</div>"
                 . "</div>";
         }
         $el .= "<div style='display:none;padding:3px;border-bottom:1px solid #bbb;text-align:center;font-style:italic;'>"._EMPTY."</div>";
      } else {
         $el = "<div style='padding:3px;border-bottom:1px solid #bbb;text-align:center;font-style:italic;'>"._EMPTY."</div>";
      }
      
      
      return $ret
           . ($method_id!="new"?"<div style='padding:10px;'>"
           . "<div style='padding:2px;border:1px solid #aaa;-moz-border-radius:5px;'>"
           . "<div style='padding:5px;text-align:center;background-color:#eee;border-bottom:1px solid #bbb;font-weight:bold;-moz-border-radius:5px 5px 0 0;'>Event with the same subject:</div>"
           . "<div>$el</div>"
           . "<div style='padding:5px;text-align:right;background-color:#eee;font-weight:bold;-moz-border-radius:0 0 5px 5px;'><input onclick='new_event(\"$method_id\");' type='button' value='New Event'/></div>"
           . "</div>"
           . "</div>":"");
      
   }
   
   function app_Delete($args) {
      $db=&Database::getInstance();
      $method_id = $args[0];
      $user_id = getUserID();
      $sql = "UPDATE ".XOCP_PREFIX."idp_development_method SET status_cd = 'nullified', nullified_dttm = now(), nullified_user_id = '$user_id' WHERE method_id = '$method_id'";
      $db->query($sql);
   }
   
}

} /// HRIS_IDPDEVLIBAJAX_DEFINED
?>