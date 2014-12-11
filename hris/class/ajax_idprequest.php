<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_idpmethodclass.php              //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_IDPREQUESTAJAX_DEFINED') ) {
   define('HRIS_IDPREQUESTAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");
require_once(XOCP_DOC_ROOT."/modules/hris/include/idp/idp.php");
require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");

class _hris_class_IDPRequestAjax extends AjaxListener {
   
   function _hris_class_IDPRequestAjax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/hris/class/ajax_idprequest.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_newRequest","app_getCompetencyGap","app_addCompetency",
                                             "app_deleteRequest","app_setPriorityUp","app_editFocusDevelopment",
                                             "app_saveFocusDevelopment","app_deleteCompetency","app_getMethodType",
                                             "app_addActionPlan","app_editActionPlan","app_saveActionPlan",
                                             "app_editIDPCompetency","app_deleteActionPlan","app_addProject",
                                             "app_setProjectPriorityUp","app_editProject","app_editProjectName",
                                             "app_saveProjectName","app_deleteProject","app_editKPO",
                                             "app_saveKPO","app_addActivity","app_editProjectActivity",
                                             "app_deleteProjectActivity",
                                             "app_saveProjectActivity","app_superiorStartRequest",
                                             "app_employeeSubmitRequest","app_superiorApproveRequest",
                                             "app_superiorReturnRequest","app_superiorNotApprovaRequest",
                                             "app_nextSuperiorApproveRequest","app_HRApproveRequest",
                                             "app_checkBeforeSubmit","app_nextSuperiorReturnRequest",
                                             "app_wizardCompetencyReview","app_wizardFocusDev",
                                             "app_wizardFocusDevForm","app_wizardProjectTitle",
                                             "app_wizardFinish","app_checkBeforeStart","app_wizardSetPriorityUp",
                                             "app_wizempActionPlan","app_wizempFinish","app_wizempAddActionPlan",
                                             "app_wizempActivity","app_wizempKPO","app_wizardSetPriorityDown",
                                             "app_checkBeforeReturn","app_employeeReturnRequest",
                                             "app_reviseRequest");
   }
   
   function app_reviseRequest($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $user_id = getUserID();
      $revision_id = 0;
      
      $sql = "SELECT MAX(revision_id) FROM ".XOCP_PREFIX."idp_rev_request WHERE request_id = '$request_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($revision_id)=$db->fetchRow($result);
      }
      $revision_id++;
      
      /// copying...
      $tblarray = array(
         array(XOCP_PREFIX."idp_request","tmp_hris_idp_request_${request_id}",XOCP_PREFIX."idp_rev_request"),
         array(XOCP_PREFIX."idp_request_actionplan","tmp_hris_idp_request_actionplan_${request_id}",XOCP_PREFIX."idp_rev_request_actionplan"),
         array(XOCP_PREFIX."idp_request_competency","tmp_hris_idp_request_competency_${request_id}",XOCP_PREFIX."idp_rev_request_competency"),
         array(XOCP_PREFIX."idp_project_activities","tmp_hris_idp_request_activities_${request_id}",XOCP_PREFIX."idp_rev_project_activities"),
         array(XOCP_PREFIX."idp_project","tmp_hris_idp_project_${request_id}",XOCP_PREFIX."idp_rev_project")
      );
      foreach($tblarray as $v) {
         list($src,$tmp,$dst)=$v;
         $sql = "CREATE TABLE $tmp SELECT * FROM $src WHERE request_id = '$request_id'";
         $result = $db->query($sql);
         $sql = "ALTER TABLE $tmp ADD COLUMN revision_id int(10) unsigned NOT NULL default '0'";
         $db->query($sql);
         $sql = "UPDATE $tmp SET revision_id = '$revision_id'";
         $db->query($sql);
         $sql = "INSERT INTO $dst SELECT * FROM $tmp";
         $db->query($sql);
         $sql = "DROP TABLE IF EXISTS $tmp";
         $db->query($sql);
      }
      
      $sql = "UPDATE ".XOCP_PREFIX."idp_request SET status_cd = 'employee' WHERE request_id = '$request_id'";
      $db->query($sql);
      
      return array($request_id,$this->renderRequest($request_id));
   }
   
   function app_employeeReturnRequest($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $request_id = $args[0];
      $sql = "UPDATE ".XOCP_PREFIX."idp_request SET status_cd = 'start', requested_dttm = '0000-00-00 00:00:00' WHERE request_id = '$request_id' AND status_cd = 'employee'";
      $db->query($sql);
      
      $this->updateTimeFrame($request_id);
      
      $sql = "SELECT notification_id"
           . " FROM ".XOCP_PREFIX."idp_notifications"
           . " WHERE user_id = '$user_id'"
           . " AND request_id = '$request_id'"
           . " AND message_id = '_IDP_YOURIDPREQUESTSTARTED'"
           . " AND source_app = 'SUPERIORSTARTREQUEST'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($notification_id)=$db->fetchRow($result)) {
            _idp_notification_followed($notification_id);
         }
      }
      
      $sql = "SELECT notification_id"
           . " FROM ".XOCP_PREFIX."idp_notifications"
           . " WHERE user_id = '$user_id'"
           . " AND request_id = '$request_id'"
           . " AND message_id = '_IDP_YOURIDPREQUESTRETURNED'"
           . " AND source_app = 'SUPERIORRETURNREQUEST'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($notification_id)=$db->fetchRow($result)) {
            _idp_notification_followed($notification_id);
         }
      }
      
      /// delete return note
      $sql = "DELETE FROM ".XOCP_PREFIX."idp_request_return_note WHERE request_id = '$request_id'";
      $db->query($sql);
      
      return $this->renderRequest($request_id);
   }
   
   function app_wizempKPO($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $project_id = $args[1];
      
      $arrproj = array();
      $arract = array();
      
      $sql = "SELECT project_id FROM ".XOCP_PREFIX."idp_project WHERE request_id = '$request_id' ORDER BY priority_no";
      $result = $db->query($sql);
      $pstep = 0;
      $pcount = 0;
      if($db->getRowsNum($result)>0) {
         while(list($project_idx)=$db->fetchRow($result)) {
            if($project_id==0) $project_id = $project_idx;
            $arrproj[] = $project_idx;
            if($project_id==$project_idx) {
               $pstep=$pcount;
            }
            $pcount++;
         }
      }
      
      $sql = "SELECT activity_id"
           . " FROM ".XOCP_PREFIX."idp_project_activities"
           . " WHERE request_id = '$request_id'"
           . " AND project_id = '$project_id'"
           . " AND status_cd = 'normal'"
           . " ORDER BY activity_id";
      $result = $db->query($sql);
      $actcount = 0;
      $actstep = 0;
      $last_act = 0;
      if($db->getRowsNum($result)>0) {
         while(list($activity_idx)=$db->fetchRow($result)) {
            if($activity_id==0) $activity_id = $activity_idx;
            $arract[] = $activity_idx;
            if($activity_id==$activity_idx) {
               $actstep = $actcount;
            }
            $actcount++;
            $last_act = $activity_idx;
         }
      }
      
      
      $sql = "SELECT project_nm,kpo,priority_no FROM ".XOCP_PREFIX."idp_project WHERE request_id = '$request_id' AND project_id = '$project_id'";
      $result = $db->query($sql);
      list($project_nm,$kpo,$priority_no)=$db->fetchRow($result);
      
      $retx = "<div style='padding:5px;'>"
               . "<table style='width:100%;'>"
               . "<colgroup>"
                  . "<col width='220'/>"
                  . "<col/>"
               . "</colgroup>"
               . "<tbody>"
               . "<tr><td style='text-align:right;vertical-align:top;'>Key Performance Outcome (KPO) : </td><td><textarea style='width:95%;height:70px;' id='editprojkpo_${request_id}_${project_id}'>$kpo</textarea></td></tr>"
               . "</tbody></table>"
           . "</div>";
      
      /////////////////////////////////////
      $btn = "<table style='width:100%;border-spacing:0px;'>"
           . "<colgroup><col/><col/></colgroup>"
           . "<tbody><tr>"
           . "<td style='text-align:left;'>"
               . "<input type='button' value='Add Activity' style='' onclick='wizemp_act_add(\"$request_id\",\"$project_id\",\"$activity_id\",this,event);'/>"
           . "</td>"
           . "<td style='text-align:right;'>"
               . "<input type='button' class='xaction' value='&lt; Previous' style='width:90px;' onclick='wizemp_act_prev();'/>&nbsp;"
               . "<input type='button' class='xaction' value='Next &gt;' style='width:90px;' onclick='wizemp_act_next();'/>"
           . "</td>"
           . "</tr></tbody></table>";
      
      $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>"
           . "IDP Request Wizard - Step 2: Add Activities"
           . "</div>"
           . "<div style='padding:5px;background-color:#f2f2f2;color:#555;'>"
              . "<div id='wizemp_flow' style='max-height:345px;height:345px;overflow:auto;border:1px solid #999;background-color:#fff;padding:4px;'>"
              . "<div style='padding:10px;font-size:1.1em;border:1px solid #888;background-color:#eee;'>"
                  . "<table><tbody>"
                     . "<tr><td style='font-weight:bold;text-align:right;white-space:nowrap;'>Project ".($pstep+1)." of $pcount : </td><td style='font-weight:bold;'>$project_nm</td></tr>"
                     . "<tr><td style='font-weight:bold;text-align:left;' colspan='2'>Add Key Performance Outcome</td></tr>"
                  . "</tbody></table>"
              . "</div>"
                  . "<table style='width:100%;border-spacing:0px;'><tbody><tr><td style='height:215px;'>"
                     . $retx
                  . "</td></tr></tbody></table>"
              . "</div>"
           . "</div>"
           . "<div id='wizemp_act_btn' style='text-align:center;padding:10px;background-color:#f2f2f2;height:100px;'>"
           . $btn
           . "</div>";
      
      return array($request_id,$project_id,$ret,$arrproj,$pcount,$arract,$actstep);
      
   }
   
   function app_wizempActivity($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $project_id = $args[1];
      $activity_id = $args[2];
      
      $arrproj = array();
      $arract = array();
      
      $sql = "SELECT project_id FROM ".XOCP_PREFIX."idp_project WHERE request_id = '$request_id' ORDER BY priority_no";
      $result = $db->query($sql);
      $pstep = 0;
      $pcount = 0;
      if($db->getRowsNum($result)>0) {
         while(list($project_idx)=$db->fetchRow($result)) {
            if($project_id==0) $project_id = $project_idx;
            $arrproj[] = $project_idx;
            if($project_id==$project_idx) {
               $pstep=$pcount;
            }
            $pcount++;
         }
      }
      
      $sql = "SELECT activity_id"
           . " FROM ".XOCP_PREFIX."idp_project_activities"
           . " WHERE request_id = '$request_id'"
           . " AND project_id = '$project_id'"
           . " AND status_cd = 'normal'"
           . " ORDER BY activity_id";
      $result = $db->query($sql);
      $actcount = 0;
      $actstep = 0;
      $last_act = 0;
      if($db->getRowsNum($result)>0) {
         while(list($activity_idx)=$db->fetchRow($result)) {
            if($activity_id==0) $activity_id = $activity_idx;
            $arract[] = $activity_idx;
            if($activity_id==$activity_idx) {
               $actstep = $actcount;
            }
            $actcount++;
            $last_act = $activity_idx;
         }
      }
      
      $arract[] = -1000;
      $actcount++;
      
      if($activity_id==-1) {
         $activity_id = -1000;
         $actstep = $actcount-1;
      }
      
      $sql = "SELECT project_nm,kpo,priority_no FROM ".XOCP_PREFIX."idp_project WHERE request_id = '$request_id' AND project_id = '$project_id'";
      $result = $db->query($sql);
      list($project_nm,$kpo,$priority_no)=$db->fetchRow($result);
      
      /////////////////////////////////////
      $retx = "";
      if($activity_id==-1000) { /// edit kpo
         $sql = "SELECT activity_id,activity_nm,activity_start_dttm,activity_stop_dttm,kpd"
              . " FROM ".XOCP_PREFIX."idp_project_activities"
              . " WHERE request_id = '$request_id'"
              . " AND project_id = '$project_id'"
              . " AND status_cd IN ('normal','finish')"
              . " ORDER BY activity_id";
         $result = $db->query($sql);
         $tba = "<table class='xxlist' style='width:100%;'>"
              . "<thead>"
              . "<tr><td>Activity</td><td>Time Frame</td><td>Key Performance Driver</td></tr>"
              . "</thead>"
              . "<tbody>";
         if($db->getRowsNum($result)>0) {
            while(list($activity_idx,$activity_nmx,$activity_start_dttmx,$activity_stop_dttmx,$kpdx)=$db->fetchRow($result)) {
               $tba .= "<tr>"
                     . "<td>$activity_nmx</td>"
                     . "<td>".sql2ind($activity_start_dttmx,"date") ." - ". sql2ind($activity_stop_dttmx,"date")."</td>"
                     . "<td>$kpdx</td>"
                     . "</tr>";
            }
         } else {
            $tba .= "<tr><td colspan='3' style='text-align:center;font-style:italic;'>No activity.</td></tr>";
         }
         $tba .= "</tbody></table>";
         $retx = "<div style='padding:5px;'>"
               . "<table style='width:100%;'>"
               . "<colgroup>"
                  . "<col width='220'/>"
                  . "<col/>"
               . "</colgroup>"
               . "<tbody>"
               . "<tr><td colspan='2'>$tba</td></tr>"
               . "<tr><td style='text-align:right;vertical-align:top;padding-top:10px;'>Key Performance Outcome (KPO) : </td>"
               . "<td style='padding-top:10px;'><textarea style='width:95%;height:70px;' id='editprojkpo_${request_id}_${project_id}'>$kpo</textarea></td></tr>"
               . "</tbody></table>"
           . "</div>";
         $js_url = "";
         $delete_btn = "";
         $actstep = $actcount;
         array_unshift($arract,10000);
      } else if($activity_id==0) {
         array_unshift($arract,0);
         $retx = "<div style='text-align:center;'>No activity found for project :"
               . "<div style='padding:10px;font-weight:bold;'>$project_nm</div>"
               . "<br/>You can add by clicking '<span style='font-weight:bold;'>Add Activity</span>' button below.</div>";
         $js_url = "";
         $activity_step = "<tr><td style='font-weight:bold;text-align:right;white-space:nowrap;'>Activity 0 of 0</td><td style='font-weight:bold;'>&nbsp;</td></tr>";
         $delete_btn = "";
      } else {
         $sql = "SELECT project_nm,kpo,priority_no FROM ".XOCP_PREFIX."idp_project WHERE request_id = '$request_id' AND project_id = '$project_id'";
         $result = $db->query($sql);
         list($project_nm,$kpo,$priority_no)=$db->fetchRow($result);
         
         $sql = "SELECT activity_id,activity_nm,kpd,activity_start_dttm,activity_stop_dttm FROM ".XOCP_PREFIX."idp_project_activities"
              . " WHERE request_id = '$request_id' AND project_id = '$project_id' AND status_cd = 'normal' AND activity_id = '$activity_id'";
         $result = $db->query($sql);
         if($db->getRowsNum($result)>0) {
            list($activity_id,$activity_nm,$kpd,$activity_start_dttm,$activity_stop_dttm)=$db->fetchRow($result);
            
            $retx = "<div id='actformeditor' style='padding:5px;'>"
                  . "<table style='width:100%;'>"
                  . "<colgroup>"
                     . "<col width='200'/>"
                     . "<col/>"
                  . "</colgroup>"
                  . "<tbody>"
                  . "<tr><td style='text-align:right;vertical-align:top;'>Activity : </td><td><textarea style='width:95%;height:70px;' id='editactivity_${request_id}_${project_id}_${activity_id}'>$activity_nm</textarea></td></tr>"
                  . "<tr><td style='text-align:right;vertical-align:top;'>Key Performance Driver (KPD) : </td><td><textarea style='width:95%;height:70px;' id='editkpd_${request_id}_${project_id}_${activity_id}'>$kpd</textarea></td></tr>"
                  . "<tr><td style='text-align:right;vertical-align:top;padding-top:5px;'>Time Frame : </td><td style='padding:5px;'>"
                        . "<table style='width:100%;border-spacing:0px;'><colgroup><col width='50%'/><col/></colgroup><tbody><tr>"
                              . "<td style='padding:0px;'>Start : <span onclick='_changedatetime(\"sp_activity_start_dttm\",\"h_activity_start_dttm\",\"date\",true,false);' id='sp_activity_start_dttm' class='xlnk'>".sql2ind($activity_start_dttm,"date")."</span><input type='hidden' id='h_activity_start_dttm' value='$activity_start_dttm'/></td>"
                              . "<td style='padding:0px;'>Stop : <span onclick='_changedatetime(\"sp_activity_stop_dttm\",\"h_activity_stop_dttm\",\"date\",true,false);' id='sp_activity_stop_dttm' class='xlnk'>".sql2ind($activity_stop_dttm,"date")."</span><input type='hidden' id='h_activity_stop_dttm' value='$activity_stop_dttm'/></td></tr></tbody></table>"
                     . "</td></tr>"
                  . "</tbody></table>"
              . "</div>";
              
              /*
              . "<div id='actformbtn' style='text-align:center;padding:10px;'>"
                  . "<input type='button' value='"._SAVE."' style='' onclick='save_activity(\"$request_id\",\"$project_id\",\"$activity_id\",this,event);'/>&nbsp;&nbsp;"
                  . "<input type='button' value='"._CANCEL."' style='' onclick='actbox.fade();'/>&nbsp;&nbsp;&nbsp;&nbsp;"
                  . "<input type='button' value='"._DELETE."' style='' onclick='delete_activity(\"$request_id\",\"$project_id\",\"$activity_id\",this,event);'/>"
              . "</div>";
              */
         }
         
         ////////////////////////////////////////////////////////////////////////////////////
         $activity_step = "<tr><td style='font-weight:bold;text-align:right;white-space:nowrap;'>Activity ".($actstep+1)." of ".($actcount-1)."</td><td style='font-weight:bold;'>&nbsp;</td></tr>";
         $delete_btn = "<input type='button' value='Delete' onclick='wizemp_act_delete(\"$activity_id\",this,event);'/>&nbsp;&nbsp;";
      }
      
      /////////////////////////////////////
      $btn = "<table style='width:100%;border-spacing:0px;'>"
           . "<colgroup><col/><col/></colgroup>"
           . "<tbody><tr>"
           . "<td style='text-align:left;'>"
               . "<input type='button' value='Add Activity' style='' onclick='wizemp_act_add(\"$request_id\",\"$project_id\",\"$activity_id\",this,event);'/>"
           . "</td>"
           . "<td style='text-align:right;'>"
               . $delete_btn
               . "<input type='button' class='xaction' value='&lt; Previous' style='width:90px;' onclick='wizemp_act_prev();'/>&nbsp;"
               . "<input type='button' class='xaction' value='Next &gt;' style='width:90px;' onclick='wizemp_act_next();'/>"
           . "</td>"
           . "</tr></tbody></table>";
      
      $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>"
           . "IDP Request Wizard - Step 2 : Add Activities"
           . "</div>"
           . "<div style='padding:5px;background-color:#f2f2f2;color:#555;'>"
              . "<div id='wizemp_flow' style='max-height:345px;height:345px;overflow:auto;border:1px solid #999;background-color:#fff;padding:4px;'>"
              . "<div style='padding:10px;font-size:1.1em;border:1px solid #888;background-color:#eee;'>"
                  . "<table><tbody>"
                     . "<tr><td style='vertical-align:top;font-weight:bold;text-align:right;white-space:nowrap;'>Project ".($pstep+1)." of $pcount : </td><td style='font-weight:bold;'>$project_nm</td></tr>"
                     . $activity_step
                  . "</tbody></table>"
              . "</div>"
                  . "<table style='width:100%;border-spacing:0px;'><tbody><tr><td style='height:215px;'>"
                     . $retx
                     . "<div style='text-align:center;padding:10px;display:none;' id='wizemp_act_delete_btn'>"
                        . "<input type='button' value='Yes (delete)' onclick='wizemp_act_confirm_delete(\"$project_id\",\"$activity_id\",this,event);'/>&nbsp;"
                        . "<input type='button' value='No (cancel delete)' onclick='wizemp_act_cancel_delete(this,event);'/>"
                     . "</div>"
                  . "</td></tr></tbody></table>"
              . "</div>"
           . "</div>"
           . "<div id='wizemp_act_btn' style='text-align:center;padding:10px;background-color:#f2f2f2;height:100px;'>"
           . $btn
           . "</div>";
      
      return array($request_id,$project_id,$activity_id,$arrproj,$arract,$pstep,$actstep,$ret);
      
   }
   
   function app_wizempAddActionPlan($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $competency_id = $args[1];
      $last_ap_id = $args[2];
      
      $arrcomp = array();
      $arrap = array();
      
      $sql = "SELECT competency_id FROM ".XOCP_PREFIX."idp_request_competency WHERE request_id = '$request_id' ORDER BY priority_no";
      $result = $db->query($sql);
      $cstep = 0;
      $ccount = 0;
      if($db->getRowsNum($result)>0) {
         while(list($competency_idx)=$db->fetchRow($result)) {
            if($competency_id==0) $competency_id = $competency_idx;
            $arrcomp[] = $competency_idx;
            if($competency_id==$competency_idx) {
               $cstep=$ccount;
            }
            $ccount++;
         }
      }
      
      $sql = "SELECT method_t,method_type FROM ".XOCP_PREFIX."idp_development_method_type";
      $result = $db->query($sql);
      $method = "";
      $cnt=0;
      if($db->getRowsNum($result)>0) {
         while(list($method_t,$method_type)=$db->fetchRow($result)) {
            if($method_t=="PROJECT") continue;
            switch($method_t) {
               case "TRN_EX":
               case "TRN_IN":
               case "SELF":
               case "COACH":
               case "COUNSL":
               case "COMPARE":
                  $disabled = "";
                  $style = "";
                  break;
               default:
                  $style = "style='color:#888;'";
                  $disabled="disabled='1'";
                  break;
            }
            $method .= "<div style='text-align:left;padding:3px;border-bottom:1px solid #bbb;cursor:default;' class='cb'>"
                     . "<table style='width:100%;'><tbody><tr>"
                        . "<td $style>$method_type</td>"
                        . "<td style='text-align:right;'><input type='button' value='Add' style='width:80px;' onclick='wizemp_add_ap(\"$request_id\",\"$competency_id\",\"$method_t\",this,event);' $disabled/></td>"
                     . "</tr></tbody></table>"
                  . "</div>";
            $cnt++;
         }
      }
      
      $sql = "SELECT a.competency_id,b.competency_nm,b.competency_abbr,a.focus_dev"
           . " FROM ".XOCP_PREFIX."idp_request_competency a"
           . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
           . " WHERE a.request_id = '$request_id'"
           . " AND a.competency_id = '$competency_id'";
      $result = $db->query($sql);
      list($competency_idx,$competency_nm,$competency_abbr,$focus)=$db->fetchRow($result);
      
      $btn = "<table style='width:100%;border-spacing:0px;'>"
           . "<colgroup><col/><col/></colgroup>"
           . "<tbody><tr>"
           . "<td style='text-align:left;'>"
               . "<input type='button' value='Back' style='width:90px;' onclick='wizemp_ap_get(\"$request_id\",\"$competency_id\",\"$last_ap_id\",this,event);'/>"
           . "</td>"
           . "<td style='text-align:right;'>"
               //. "<input type='button' class='xaction' value='&lt; Previous' style='width:90px;' onclick='wizemp_ap_prev();' ".($cstep==0?"disabled='1'":"")."/>&nbsp;"
               //. "<input type='button' class='xaction' value='Next &gt;' style='width:90px;' onclick='wizemp_ap_next();'/>"
           . "</td>"
           . "</tr></tbody></table>";
      
      
      
      $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>"
           . "IDP Request Wizard - Step 1 : Add Development Method"
           . "</div>"
           . "<div style='padding:5px;background-color:#f2f2f2;color:#555;'>"
              . "<div style='max-height:345px;height:345px;overflow:auto;border:1px solid #999;background-color:#fff;padding:4px;'>"
                 . "<div style='padding:10px;font-size:1.1em;border:1px solid #888;background-color:#ddd;'>"
                     . "<table><tbody>"
                        . "<tr><td style='vertical-align:top;font-weight:bold;text-align:right;white-space:nowrap;'>Competency ".($cstep+1)." of $ccount : </td><td style='font-weight:bold;'>$competency_abbr - $competency_nm</td></tr>"
                        . "<tr><td style='vertical-align:top;font-weight:bold;text-align:right;white-space:nowrap;'>Focus of Development : </td><td style='font-weight:bold;'>$focus</td></tr>"
                     . "</tbody></table>"
                 . "</div>"
              
                  . "<table style='width:100%;border-spacing:0px;'><tbody><tr><td style='height:315px;'>"
                     . $method
                  . "</td></tr></tbody></table>"
              . "</div>"
           . "</div>"
           . "<div style='text-align:center;padding:10px;background-color:#f2f2f2;height:100px;'>"
           . $btn
           . "</div>";
      
      return $ret;
   }
   
   function app_wizempFinish($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $employee_id = $args[1];
      $job_id = $args[2];
      
      $btn = "<table style='width:100%;border-spacing:0px;'>"
           . "<colgroup><col/><col/></colgroup>"
           . "<tbody><tr>"
           . "<td style='text-align:left;'>"
               . "&nbsp;" ///<input type='button' value='Finish' style='width:90px;' onclick='wizemp_stop();'/>"
           . "</td>"
           . "<td style='text-align:right;'>"
               .  "<input type='button' class='xaction' value='&lt; Previous' style='width:90px;' onclick='wizemp_run_prev();'/>&nbsp;"
               //.  "<input type='button' class='xaction' value='Next &gt;' style='width:90px;' disabled='1'/>&nbsp;"
               . "<input type='button' value='Finish' style='width:90px;' onclick='wizemp_stop();'/>"
           . "</td>"
           . "</tr></tbody></table>";
      
      $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>IDP Request Wizard - Finish</div>"
           . "<div style='padding:10px;padding-top:25px;padding-bottom:25px;text-align:center;' id='wizardfocusdev'>"
               . "You are at the final step of the wizard.<br/><br/>"
               . "You can back review your data by clicking '<span style='font-weight:bold;color:#666;'>Previous</span>' button,"
               . "<br/>or click '<span style='font-weight:bold;color:#666;'>Finish</span>' button to close this window."
           . "</div>"
           . "<div style='text-align:center;padding:10px;background-color:#f2f2f2;height:100px;'>"
           . $btn
           . "</div>";
      
      return $ret;
      
   }
   
   function app_wizempActionPlan($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $competency_id = $args[1];
      $actionplan_id = $args[2];
      
      $arrcomp = array();
      $arrap = array();
      
      $sql = "SELECT competency_id FROM ".XOCP_PREFIX."idp_request_competency WHERE request_id = '$request_id' ORDER BY priority_no";
      $result = $db->query($sql);
      $cstep = 0;
      $ccount = 0;
      if($db->getRowsNum($result)>0) {
         while(list($competency_idx)=$db->fetchRow($result)) {
            if($competency_id==0) $competency_id = $competency_idx;
            $arrcomp[] = $competency_idx;
            if($competency_id==$competency_idx) {
               $cstep=$ccount;
            }
            $ccount++;
         }
      }
      
      $sql = "SELECT actionplan_id,method_t"
           . " FROM ".XOCP_PREFIX."idp_request_actionplan"
           . " WHERE request_id = '$request_id'"
           . " AND competency_id = '$competency_id'"
           . " ORDER BY actionplan_id";
      $result = $db->query($sql);
      $apcount = 0;
      $apstep = 0;
      $last_ap = 0;
      if($db->getRowsNum($result)>0) {
         while(list($actionplan_idx,$method_t)=$db->fetchRow($result)) {
            if($method_t=="PROJECT") continue;
            if($actionplan_id==0) $actionplan_id = $actionplan_idx;
            $arrap[] = $actionplan_idx;
            if($actionplan_id==$actionplan_idx) {
               $apstep = $apcount;
            }
            $apcount++;
            $last_ap = $actionplan_idx;
         }
      }
      
      if($actionplan_id==-1) {
         $actionplan_id = $last_ap;
         $apstep = $apcount-1;
      }
      
      $sql = "SELECT a.competency_id,b.competency_nm,b.competency_abbr,a.focus_dev"
           . " FROM ".XOCP_PREFIX."idp_request_competency a"
           . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
           . " WHERE a.request_id = '$request_id'"
           . " AND a.competency_id = '$competency_id'";
      $result = $db->query($sql);
      list($competency_idx,$competency_nm,$competency_abbr,$focus)=$db->fetchRow($result);
      
      /////////////////////////////////////
      $retx = "";
      if($actionplan_id==0) {
         $retx = "<div style='text-align:center;'>No development method found for competency:"
               . "<div style='padding:10px;font-weight:bold;'>$competency_abbr - $competency_nm</div>"
               . "<br/>You can add by clicking '<span style='font-weight:bold;'>Add Development Method</span>' button below.</div>";
         $js_url = "";
         $method_step = "<tr><td style='font-weight:bold;text-align:right;'>Method 0 of 0 : </td><td style='font-weight:bold;'>-</td></tr>";
         $delete_btn = "";
      } else {
         
         $sql = "SELECT a.method_t,b.method_type,a.method_id,a.competency_id,a.plan_start_dttm,a.plan_stop_dttm FROM ".XOCP_PREFIX."idp_request_actionplan a"
              . " LEFT JOIN ".XOCP_PREFIX."idp_development_method_type b USING(method_t)"
              . " WHERE a.request_id = '$request_id' AND a.actionplan_id = '$actionplan_id'";
         $result = $db->query($sql);
         $height = "260px";
         if($db->getRowsNum($result)==1) {
            list($method_t,$method_type,$method_id,$competency_idx,$plan_start_dttm,$plan_stop_dttm)=$db->fetchRow($result);
            if($method_t!="") {
               $editor_file = XOCP_DOC_ROOT."/modules/hris/include/idp/method_${method_t}.php";
               if(file_exists($editor_file)) {
                  require_once($editor_file);
                  $fedit = "${method_t}_idp_m_editActionPlan";
                  list($form,$height) = $fedit($request_id,$actionplan_id);
               }
            }
            
            $js_url = XOCP_SERVER_SUBDIR."/modules/hris/include/idp/method_${method_t}.php?js=1";
         
         }
         $retx = "<div id='apformeditor' style='padding:5px;'>"
               . $form
               . "</div>";
         $method_step = "<tr><td style='font-weight:bold;text-align:right;'>Method ".($apstep+1)." of $apcount : </td><td style='font-weight:bold;'>$method_type</td></tr>";
         $delete_btn = "<input type='button' value='Delete' onclick='wizemp_ap_delete(\"$actionplan_id\",this,event);'/>&nbsp;&nbsp;";
      }
      
      /////////////////////////////////////
      $btn = "<table style='width:100%;border-spacing:0px;'>"
           . "<colgroup><col/><col/></colgroup>"
           . "<tbody><tr>"
           . "<td style='text-align:left;'>"
               . "<input type='button' value='Add Development Method' style='' onclick='wizemp_ap_getmethod(\"$request_id\",\"$competency_id\",\"$actionplan_id\",this,event);'/>"
           . "</td>"
           . "<td style='text-align:right;'>"
               . $delete_btn
               . "<input type='button' class='xaction' value='&lt; Previous' style='width:90px;' onclick='wizemp_ap_prev();' ".($cstep==0&&$apstep<=0?"disabled='1'":"")."/>&nbsp;"
               . "<input type='button' class='xaction' value='Next &gt;' style='width:90px;' onclick='wizemp_ap_next();'/>"
           . "</td>"
           . "</tr></tbody></table>";
      
      $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>"
           . "IDP Request Wizard - Step 1 : Add Development Method"
           . "</div>"
           . "<div style='padding:5px;background-color:#f2f2f2;color:#555;'>"
              . "<div id='wizemp_flow' style='max-height:345px;height:345px;overflow:auto;border:1px solid #999;background-color:#fff;padding:4px;'>"
              . "<div style='padding:10px;font-size:1.1em;border:1px solid #888;background-color:#eee;'>"
                  . "<table><tbody>"
                     . "<tr><td style='vertical-align:top;font-weight:bold;text-align:right;white-space:nowrap;'>Competency ".($cstep+1)." of $ccount : </td><td style='font-weight:bold;'>$competency_abbr - $competency_nm</td></tr>"
                     . "<tr><td style='vertical-align:top;font-weight:bold;text-align:right;white-space:nowrap;'>Focus of Development : </td><td style='font-weight:bold;'>$focus</td></tr>"
                     . $method_step
                  . "</tbody></table>"
              . "</div>"
                  . "<table style='width:100%;border-spacing:0px;'><tbody><tr><td style='height:215px;'>"
                     . $retx
                     . "<div style='text-align:center;padding:10px;display:none;' id='wizemp_ap_delete_btn'>"
                        . "<input type='button' value='Yes (delete)' onclick='wizemp_ap_confirm_delete(\"$actionplan_id\",this,event);'/>&nbsp;"
                        . "<input type='button' value='No (cancel delete)' onclick='wizemp_ap_cancel_delete(this,event);'/>"
                     . "</div>"
                  . "</td></tr></tbody></table>"
              . "</div>"
           . "</div>"
           . "<div id='wizemp_ap_btn' style='text-align:center;padding:10px;background-color:#f2f2f2;height:100px;'>"
           . $btn
           . "</div>";
      
      return array($request_id,$competency_id,$actionplan_id,$arrcomp,$arrap,$cstep,$apstep,$ret,$js_url);
      
   }
   
   function app_wizardSetPriorityUp($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $competency_id = $args[1];
      $priority_no = (5*$args[2])-6;
      $employee_id = $args[3];
      $job_id = $args[4];
      $sql = "UPDATE ".XOCP_PREFIX."idp_request_competency SET priority_no = (priority_no*5) WHERE request_id = '$request_id'";
      $db->query($sql);
      $sql = "UPDATE ".XOCP_PREFIX."idp_request_competency SET priority_no = '$priority_no' WHERE request_id = '$request_id' AND competency_id = '$competency_id'";
      $db->query($sql);
      
      $this->sortPriority($request_id);
      return array($request_id,$competency_id,$this->renderIDPCompetency($request_id),$this->app_wizardCompetencyReview(array($request_id,$employee_id,$job_id,1)));
   }
   
   function app_wizardSetPriorityDown($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $competency_id = $args[1];
      $priority_no = (5*$args[2])+6;
      $employee_id = $args[3];
      $job_id = $args[4];
      $sql = "UPDATE ".XOCP_PREFIX."idp_request_competency SET priority_no = (priority_no*5) WHERE request_id = '$request_id'";
      $db->query($sql);
      $sql = "UPDATE ".XOCP_PREFIX."idp_request_competency SET priority_no = '$priority_no' WHERE request_id = '$request_id' AND competency_id = '$competency_id'";
      $db->query($sql);
      
      $this->sortPriority($request_id);
      return array($request_id,$competency_id,$this->renderIDPCompetency($request_id),$this->app_wizardCompetencyReview(array($request_id,$employee_id,$job_id,1)));
   }
   
   function app_checkBeforeStart($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $ok = 1;
      
      $error_activity_nm = array();
      $error_activity_start = array();
      $error_activity_stop = array();
      $error_kpo = array();
      
      $error_plan_project = $error_plan_method = $error_plan_self = $error_plan_start = $error_plan_stop = $error_comp = array();
      $error_project_nm = array();
      $sql = "SELECT a.plan_start_dttm,a.plan_stop_dttm,a.method_id,a.method_t,b.competency_nm,b.competency_abbr,"
           . "a.competency_id,c.method_type,a.actionplan_id,a.method_id,a.selfstudy_id,a.project_id,d.focus_dev"
           . " FROM ".XOCP_PREFIX."idp_request_actionplan a"
           . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
           . " LEFT JOIN ".XOCP_PREFIX."idp_development_method_type c ON c.method_t = a.method_t"
           . " LEFT JOIN ".XOCP_PREFIX."idp_request_competency d ON d.request_id = a.request_id AND d.competency_id = a.competency_id"
           . " WHERE a.request_id = '$request_id' AND a.status_cd != 'nullified'";
      $rmin = $db->query($sql);
      if($db->getRowsNum($rmin)>0) {
         while(list($plan_start_dttm,$plan_stop_dttm,$method_id,$method_t,$competency_nm,$competency_abbr,$competency_id,
                    $method_type,$actionplan_id,$method_id,$selfstudy_id,$project_id,$focus_dev)=$db->fetchRow($rmin)) {
            if(trim($focus_dev)=="") {
               $error_comp[$competency_id] = array($competency_nm);
               $ok=0;
            }
            switch($method_t) {
               case "PROJECT":
                  if($project_id==0) {
                     $error_plan_project[$competency_id][$actionplan_id] = array($competency_nm,$method_type);
                     $ok=0;
                  }
                  break;
               case "TRN_IN":
               case "TRN_EX":
               case "SELF":
                  if($method_t!="SELF"&&$method_id==0) {
                     $error_plan_method[$competency_id][$actionplan_id] = array($competency_nm,$method_type);
                     $ok=0;
                  }
                  if($method_t=="SELF"&&$selfstudy_id==0) {
                     $error_plan_self[$competency_id][$actionplan_id] = array($competency_nm,$method_type);
                     $ok=0;
                  }
                  if($plan_start_dttm=="0000-00-00 00:00:00") {
                     $error_plan_start[$competency_id][$actionplan_id] = array($competency_nm,$method_type);
                     $ok=0;
                  }
                  if($plan_stop_dttm=="0000-00-00 00:00:00") {
                     $error_plan_stop[$competency_id][$actionplan_id] = array($competency_nm,$method_type);
                     $ok=0;
                  }
                  break;
               default:
                  break;
            }
         }
      }
      
      $sql = "SELECT project_nm,project_id FROM ".XOCP_PREFIX."idp_project WHERE request_id = '$request_id' ORDER BY project_id";
      $result = $db->query($sql);
      while(list($project_nm,$project_id)=$db->fetchRow($result)) {
         if(trim($project_nm)=="") {
            $error_project_nm[$project_id] = 1;
            $ok = 0;
         }
         if(trim($project_nm)=="Untitled Project") {
            $error_project_nm[$project_id] = 1;
            $ok = 0;
         }
      }
      
      if($ok==1) {
         $ret = "<div style='background-color:#f2f2f2;font-weight:bold;font-size:1.1em;height:300px;padding:20px;text-align:center;'>You are finish and going to submit this IDP request."
              . "<br/><br/>Are you sure?"
              . "<br/><br/><br/>"
              . "<input type='button' value='Yes (Start IDP Request)' onclick='confirm_superior_start_request(\"$request_id\");'/>&nbsp;"
              . "<input type='button' value='No (Close)' onclick='supstartbox.fade();'/></div>";
         $h = 190;
      } else {
         $error = "";
         $cnt = 0;
         foreach($error_comp as $x=>$y) {
            list($competency_nm)=$y;
            $error .= "<li>Focus of Development is still empty in $competency_nm</li>";
            $cnt++;
         }
         if(count($error_project_nm)>0) {
            $error .= "<li>Some project are untitled.</li>";
            $cnt++;
         }
         
         foreach($error_plan_project as $x=>$y) {
            foreach($y as $v) {
               list($competency_nm,$method_type)=$v;
               $error .= "<li>$competency_nm has no Project Assignment</li>";
               $cnt++;
            }
         }
         
         foreach($error_plan_method as $x=>$y) {
            foreach($y as $v) {
               list($competency_nm,$method_type)=$v;
               $error .= "<li>Action Plan is empty in $competency_nm - $method_type</li>";
               $cnt++;
            }
         }
         foreach($error_plan_self as $x=>$y) {
            foreach($y as $v) {
               list($competency_nm,$method_type)=$v;
               $error .= "<li>Self study type is undefined in $competency_nm</li>";
               $cnt++;
            }
         }
         foreach($error_plan_start as $x=>$y) {
            foreach($y as $v) {
               list($competency_nm,$method_type)=$v;
               $error .= "<li>Start date is not set in $competency_nm - $method_type</li>";
               $cnt++;
            }
         }
         foreach($error_plan_stop as $x=>$y) {
            foreach($y as $v) {
               list($competency_nm,$method_type)=$v;
               $error .= "<li>Stop date is not set in $competency_nm - $method_type</li>";
               $cnt++;
            }
         }
         foreach($error_kpo as $id=>$v) {
            list($project_nm)=$v;
            $error .= "<li>KPO is still empty in $project_nm</li>";
            $cnt++;
         }
         foreach($error_activity_nm as $id=>$v) {
            list($project_nm)=$v;
            $error .= "<li>Activity is still empty in project $project_nm</li>";
            $cnt++;
         }
         foreach($error_activity_start as $id=>$v) {
            foreach($v as $activity_id=>$vv) {
               list($project_nm,$activity_nm)=$vv;
               $error .= "<li>Activity start date is still empty in $project_nm - $activity_nm</li>";
               $cnt++;
            }
         }
         foreach($error_activity_stop as $id=>$v) {
            foreach($v as $activity_id=>$vv) {
               list($project_nm,$activity_nm)=$vv;
               $error .= "<li>Activity stop date is still empty in $project_nm - $activity_nm</li>";
               $cnt++;
            }
         }
         $ret = "<div style='background-color:#f2f2f2;font-weight:bold;font-size:1.1em;height:300px;padding:20px;text-align:center;'>Request is not complete. Please check the following:"
              . "<div style='max-height:200px;overflow:auto;text-align:left;padding:5px;font-weight:normal;'>"
              . "<ul>${error}</ul>"
              . "</div>"
              . "<div style='padding:10px;'><input style='width:100px;' type='button' value='Ok' onclick='supstartbox.fade();'/></div></div>";
         $h = 150;
         $add = (20*$cnt);
         if($add<=200) {
            $h += $add;
         } else {
            $h += 180;
         }
      }
      return array($ret,$h);
   }
   
   
   
   function app_wizardFinish($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $employee_id = $args[1];
      $job_id = $args[2];
      
      $btn = "<table style='width:100%;border-spacing:0px;'>"
           . "<colgroup><col/><col/></colgroup>"
           . "<tbody><tr>"
           . "<td style='text-align:left;'>"
               .  "&nbsp;" ////<input type='button' class='xaction' value='&lt; Previous' style='width:90px;' onclick='wizard_run_prev();'/>&nbsp;"
           . "</td>"
           . "<td style='text-align:right;'>"
               .  "<input type='button' class='xaction' value='&lt; Previous' style='width:90px;' onclick='wizard_run_prev();'/>&nbsp;"
               . "<input type='button' value='Finish' style='width:90px;' onclick='wizard_stop();'/>"
           . "</td>"
           . "</tr></tbody></table>";
      
      $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>IDP Request Wizard - Finish</div>"
           . "<div style='padding:10px;padding-top:25px;padding-bottom:25px;text-align:center;' id='wizardfocusdev'>"
               . "You are at the final step of the wizard.<br/><br/>"
               . "You can back review your data by clicking '<span style='font-weight:bold;color:#666;'>Previous</span>' button,"
               . "<br/>or click '<span style='font-weight:bold;color:#666;'>Finish</span>' button to close this window."
           . "</div>"
           . "<div style='text-align:center;padding:10px;background-color:#f2f2f2;height:100px;'>"
           . $btn
           . "</div>";
      
      return $ret;
      
   }
   
   
   function app_wizardProjectTitle($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $project_id = $args[1];
      $add_project = $args[2];
      
      $arr_proj = array();
      $xproj = "";
      
      $first_id = 0;
      $last_id = 0;
      
      //// check is there any empty project
      if($add_project==2) {
         $competency_unrel = 0;
         $sql = "SELECT a.competency_id,b.competency_nm,b.competency_abbr FROM ".XOCP_PREFIX."idp_request_competency a"
              . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
              . " WHERE a.request_id = '$request_id'"
              . " ORDER BY a.priority_no";
         $result = $db->query($sql);
         $compcnt = 0;
         if($db->getRowsNum($result)>0) {
            while(list($competency_id,$competency_nm,$competency_abbr)=$db->fetchRow($result)) {
               $sql = "SELECT project_id FROM ".XOCP_PREFIX."idp_request_actionplan WHERE request_id = '$request_id' AND method_t = 'PROJECT' AND competency_id = '$competency_id' AND status_cd != 'nullified'";
               $rselcomp = $db->query($sql);
               if($db->getRowsNum($rselcomp)>0) {
                  list($project_idx)=$db->fetchRow($rselcomp);
                  if($project_idx==0) {
                     $competency_unrel++;
                  }
               } else {
                  $project_idx = 0;
               }
               $compcnt++;
            }
         }
         
         $project_empty = 0;
         if($competency_unrel>0) {
            $sql = "SELECT project_id FROM ".XOCP_PREFIX."idp_project WHERE request_id = '$request_id' AND status_cd = 'normal' ORDER BY priority_no,project_id";
            $result = $db->query($sql);
            if($db->getRowsNum($result)>0) {
               while(list($project_idx)=$db->fetchRow($result)) {
                  $sql = "SELECT competency_id FROM ".XOCP_PREFIX."idp_request_actionplan WHERE request_id = '$request_id' AND method_t = 'PROJECT' AND project_id = '$project_idx' AND status_cd NOT IN ('rejected','nullified')";
                  $rselcomp = $db->query($sql);
                  if($db->getRowsNum($rselcomp)>0) {
                  } else {
                     $project_empty++;
                  }
               }
            }
            if($project_empty==0) {
               $add_project=1;
            } else {
               return "NO_MORE";
            }
         } else {
            return "NO_MORE";
         }
      }
      
      
      if($add_project==1) {
         $sql = "SELECT MAX(project_id) FROM ".XOCP_PREFIX."idp_project WHERE request_id = '$request_id'";
         $result = $db->query($sql);
         if($db->getRowsNum($result)>0) {
            list($project_id)=$db->fetchRow($result);
         }
         $project_id++;
         $sql = "INSERT INTO ".XOCP_PREFIX."idp_project (request_id,project_id,project_nm,priority_no)"
              . " VALUES ('$request_id','$project_id','Untitled Project','200')";
         $result = $db->query($sql);
         $this->sortProjectPriority($request_id);
         $xproj = $this->renderProject($request_id,$project_id);
      }
      
      
      $sql = "SELECT project_id FROM ".XOCP_PREFIX."idp_project WHERE request_id = '$request_id' AND status_cd = 'normal' ORDER BY priority_no,project_id";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($project_idx)=$db->fetchRow($result)) {
            if($first_id==0) $first_id = $project_idx;
            $arr_proj[] = $project_idx;
            $last_id = $project_idx;
         }
      }
      
      if($project_id==0) {
         $project_id = $first_id;
      }
      
      if($project_id==0) {
         $sql = "SELECT MAX(project_id) FROM ".XOCP_PREFIX."idp_project WHERE request_id = '$request_id'";
         $result = $db->query($sql);
         if($db->getRowsNum($result)>0) {
            list($project_id)=$db->fetchRow($result);
         }
         $project_id++;
         $sql = "INSERT INTO ".XOCP_PREFIX."idp_project (request_id,project_id,project_nm,priority_no)"
              . " VALUES ('$request_id','$project_id','Untitled Project','200')";
         $result = $db->query($sql);
         $this->sortProjectPriority($request_id);
         $xproj = $this->renderProject($request_id,$project_id);
         $first_id = $project_id;
         $last_id = $project_id;
         $arr_proj[] = $project_id;
      }
      
      $sql = "SELECT project_nm,cost_estimate,priority_no FROM ".XOCP_PREFIX."idp_project WHERE request_id = '$request_id' AND project_id = '$project_id'";
      $result = $db->query($sql);
      list($project_nm,$cost_estimate,$priority_no)=$db->fetchRow($result);
      
      $sql = "SELECT a.competency_id,b.competency_nm,b.competency_abbr FROM ".XOCP_PREFIX."idp_request_competency a"
           . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
           . " WHERE a.request_id = '$request_id'"
           . " ORDER BY a.priority_no";
      $result = $db->query($sql);
      $compcnt = 0;
      if($db->getRowsNum($result)>0) {
         while(list($competency_id,$competency_nm,$competency_abbr)=$db->fetchRow($result)) {
            $sql = "SELECT project_id FROM ".XOCP_PREFIX."idp_request_actionplan WHERE request_id = '$request_id' AND method_t = 'PROJECT' AND competency_id = '$competency_id' AND status_cd != 'nullified'";
            $rselcomp = $db->query($sql);
            if($db->getRowsNum($rselcomp)>0) {
               list($project_idx)=$db->fetchRow($rselcomp);
            } else {
               $project_idx = 0;
            }
            $complist .= "<div><input ".($project_idx==$project_id?"checked='1'":"")." type='checkbox' id='projcompsel_${competency_id}' name='projcompsel_${competency_id}' value='$competency_id'/> <label for='projcompsel_${competency_id}' class='xlnk'>$competency_abbr - $competency_nm</label></div>";
            $compcnt++;
         }
      } else {
         $complist = "No competency found.";
         $compcnt++;
      }
      
      $btn_prev = "<input type='button' class='xaction' style='width:90px;' value='&lt; Previous' onclick='wizard_project_prev();'/>";
      $btn_next = "<input type='button' class='xaction' style='width:90px;' value='Next &gt;' onclick='wizard_project_next();'/>";
      
      if($first_id==$project_id) {
         $btn_prev = "<input type='button' class='xaction' style='width:90px;' value='&lt; Previous' onclick='wizard_run_prev();'/>";
      }
      
      if($last_id==$project_id) {
         $btn_add = "<input type='button' style='min-width:90px;' value='Add New Project' onclick='wizard_project_new();'/>";
      } else {
         $btn_add = "";
      }
      
      $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>IDP Request Wizard - Step 4 of 4 : Add Assignment Action Plan</div>"
           . "<div style='padding:5px;background-color:#f2f2f2;border-bottom:1px solid #ddd;'>"
              . "<div style='padding:10px;text-align:center;'>Edit the 'Project Assigment' and select competencies to be developed:</div>"
           . "</div>"
           . "<div style='padding:5px;'>"
               . "<table style='width:100%;'>"
               . "<colgroup>"
                  . "<col width='190'/>"
                  . "<col/>"
               . "</colgroup>"
               . "<tbody>"
               . "<tr><td style='text-align:right;vertical-align:top;'>Project Assignment : </td><td><input type='text' id='editprojnm_${request_id}_${project_id}' value='$project_nm' style='width:400px;'/></td></tr>"
               . "<tr><td style='text-align:right;vertical-align:top;'>Competency to be Developed : </td><td><div id='dvprojcomplist' style='padding:10px;width:405px;border:1px solid #bbb;'>$complist</div></td></tr>"
               . "</tbody></table>"
           . "</div>"
           . "<div style='text-align:center;padding:10px;background-color:#f2f2f2;height:100px;'>"
               . "<table style='width:100%;border-spacing:0px;'>"
               . "<colgroup><col/><col/></colgroup>"
               . "<tbody><tr>"
               . "<td style='text-align:left;'>"
                  . $btn_add
               . "</td>"
               . "<td style='text-align:right;'>"
                  . "${btn_prev}&nbsp;${btn_next}"
               . "</td>"
               . "</tr></tbody></table>"
           . "</div>";
      
      return array($request_id,$project_id,$ret,(226+($compcnt*19))."px",$arr_proj,$xproj);
   }
   
   
   
   function app_wizardFocusDevForm($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $competency_id = $args[1];
      $sql = "SELECT a.focus_dev,b.competency_nm,b.competency_abbr,a.priority_no FROM ".XOCP_PREFIX."idp_request_competency a"
           . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
           . " WHERE a.request_id = '$request_id' AND a.competency_id = '$competency_id'";
      $result = $db->query($sql);
      list($focus_dev,$competency_nm,$competency_abbr,$priority_no)=$db->fetchRow($result);
      $sql = "SELECT COUNT(*) FROM ".XOCP_PREFIX."idp_request_competency WHERE request_id = '$request_id'";
      $result = $db->query($sql);
      list($cnt)=$db->fetchRow($result);
      $ret = "<table style='width:100%;'>"
               . "<colgroup>"
                  . "<col width='200'/>"
                  . "<col/>"
               . "</colgroup>"
               . "<tbody>"
               . "<tr><td style='text-align:right;vertical-align:top;'>No : </td><td>$priority_no of $cnt</td></tr>"
               . "<tr><td style='text-align:right;vertical-align:top;'>Competency to be Developed : </td><td style='font-weight:bold;'>$competency_abbr - $competency_nm</td></tr>"
               . "<tr><td style='text-align:right;vertical-align:top;'>Focus of Development : </td><td><textarea style='width:95%;height:70px;' id='editfocusdev_${request_id}_${competency_id}'>$focus_dev</textarea></td></tr>"
               . "</tbody></table>";
      return $ret;
   }
   
   function app_wizardFocusDev($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $employee_id = $args[1];
      $job_id = $args[2];
      $no_current = $args[3];
      $sql = "SELECT priority_no,competency_id FROM ".XOCP_PREFIX."idp_request_competency WHERE request_id = '$request_id' ORDER BY priority_no";
      $result = $db->query($sql);
      $first_competency_id = 0;
      $first_priority_no = 0;
      $arr_competency = array();
      if($db->getRowsNum($result)>0) {
         $no = 0;
         while(list($priority_no,$competency_id)=$db->fetchRow($result)) {
            if($first_competency_id==0) {
               $first_competency_id = $competency_id;
               $first_priority_no = $priority_no;
            }
            $arr_competency[] = $competency_id;
            if($no==$no_current) {
               $first_competency_id = $competency_id;
            }
            $no++;
         }
      }
      
      $btn = "<table style='width:100%;border-spacing:0px;'>"
           . "<colgroup><col/><col/></colgroup>"
           . "<tbody><tr>"
           . "<td style='text-align:left;'>"
               . "&nbsp;"
           . "</td>"
           . "<td style='text-align:right;'>"
               .  "<input type='button' class='xaction' value='&lt; Previous' style='width:90px;' onclick='wizard_focusdev_prev();'/>&nbsp;"
               . "<input type='button' class='xaction' value='Next &gt;' style='width:90px;' onclick='wizard_focusdev_next();'/>"
           . "</td>"
           . "</tr></tbody></table>";
      
      $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>IDP Request Wizard - Step 3 of 4 : Add Focus of Development</div>"
           . "<div style='padding:5px;height:130px;' id='wizardfocusdev'>"
               . $this->app_wizardFocusDevForm(array($request_id,$first_competency_id))
           . "</div>"
           . "<div style='text-align:center;padding:10px;background-color:#f2f2f2;height:100px;'>"
           . $btn
           . "</div>";
      
      return array($ret,$arr_competency);
      
   }
   
   
   function app_wizardCompetencyReview($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $employee_id = $args[1];
      $job_id = $args[2];
      $orderby = $args[3];
      
      $sql = "SELECT MAX(asid) FROM ".XOCP_PREFIX."employee_competency_final WHERE employee_id = '$employee_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($asid)=$db->fetchRow($result);
      } else {
         $asid = 0;
      }
      
      $requested_arr = array();
      $sql = "SELECT competency_id,ccl,rcl,itj,gap,priority_no FROM ".XOCP_PREFIX."idp_request_competency WHERE request_id = '$request_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($competency_id,$ccl,$rcl,$itj,$gap,$priority_no)=$db->fetchRow($result)) {
            $requested_arr[$competency_id] = array($ccl,$rcl,$itj,$gap,$priority_no);
         }
      }
      
      $ret = "";
      $ret .= "<div style='background-color:#eee;padding:0px;border:1px solid #bbb;cursor:default;'>"
                         . "<table style='width:100%;border-spacing:0px;'>"
                         . "<colgroup>"
                         . "<col width=''/>"
                         . "<col width='90'/>"
                         . "<col width='50'/>"
                         . "<col width='50'/>"
                         . "<col width='50'/>"
                         . "<col width='50'/>"
                         . "<col width='70'/>"
                         . "</colgroup>"
                         . "<tbody><tr>"
                         . "<td style='padding:5px;text-align:left;'>Competency</td>"
                         . "<td style='padding:5px;text-align:center;'><span onclick='wizard_review_orderby_group();' class='xlnk'>Group</span><span style='font-size:0.8em;'>&#9660;</span></td>"
                         . "<td style='padding:5px;text-align:center;'>CCL</td>"
                         . "<td style='padding:5px;text-align:center;'>RCL</td>"
                         . "<td style='padding:5px;text-align:center;'>ITJ</td>"
                         . "<td style='padding:5px;text-align:center;'>Gap</td>"
                         . "<td style='padding:5px;text-align:center;'><span onclick='wizard_review_orderby_priority();' class='xlnk'>Priority</span><span style='font-size:0.8em;'>&#9660;</span></td>"
                         . "</tr></tbody></table></div>";
      
      
      if(count($requested_arr)>0) {
         $sql = "SELECT a.competency_id,a.rcl,a.itj,b.competency_nm,c.compgroup_nm,b.competency_class,d.ccl,(b.competency_class+0) as urcl,"
              . "b.desc_en,b.desc_id,b.compgroup_id,b.competency_abbr"
              . " FROM ".XOCP_PREFIX."idp_request_competency a"
              . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
              . " LEFT JOIN ".XOCP_PREFIX."compgroup  c USING(compgroup_id)"
              . " LEFT JOIN ".XOCP_PREFIX."employee_competency_final d ON d.asid = '$asid' AND d.employee_id = '$employee_id' AND d.job_id = '$job_id' AND d.competency_id = b.competency_id"
              . " WHERE a.request_id = '$request_id'"
              . ($orderby==1?" ORDER BY a.priority_no":" ORDER BY b.compgroup_id,urcl");
         $result = $db->query($sql);
         $oldcompgroup = "";
         $oldcompgroup_id = "";
         if($db->getRowsNum($result)>0) {
            while(list($competency_id,$rcl,$itj,$competency_nm,$compgroup_nm,$cc,$ccl,$urcl,$desc_en,$desc_id,$compgroup_id,$competency_abbr)=$db->fetchRow($result)) {
         
               if(!isset($requested_arr[$competency_id])) continue;
               list($ccl_,$rcl_,$itj_,$gap_,$priority_no)=$requested_arr[$competency_id];
               
               $gapx = $ccl*$itj-$rcl*$itj;
               if($gapx<0) {
                  $gap_color = "color:red;font-weight:bold;";
                  $competency_color = "color:red;";
               } else if($gapx>0) {
                  $gap_color = "color:blue;font-weight:bold;";
                  $competency_color = "color:blue;";
               } else {
                  $gap_color = "";
                  $competency_color = "";
               }
               
               $ret .= "<div style='padding:0px;border-bottom:1px solid #ccc;cursor:default;' id='wxcomp_${request_id}_${competency_id}'>"
                     . "<table style='width:100%;border-spacing:0px;'>"
                     . "<colgroup>"
                     . "<col width=''/>"
                     . "<col width='90'/>"
                     . "<col width='50'/>"
                     . "<col width='50'/>"
                     . "<col width='50'/>"
                     . "<col width='50'/>"
                     . "<col width='70'/>"
                     . "</colgroup>"
                     . "<tbody><tr>"
                     . "<td style='text-align:left;'><div style='padding:3px;min-height:18px;'>$competency_abbr - $competency_nm</div></td>"
                     . "<td style='padding:3px;text-align:center;border-left:1px solid #ddd;border-right:1px solid #ddd;'>$compgroup_nm</td>"
                     . "<td style='padding:3px;text-align:center;border-right:1px solid #ddd;${gap_color}'>$ccl</td>"
                     . "<td style='padding:3px;text-align:center;border-right:1px solid #ddd;${gap_color}'>$rcl</td>"
                     . "<td style='padding:3px;text-align:center;border-right:1px solid #ddd;${gap_color}'>$itj</td>"
                     . "<td style='padding:3px;text-align:center;border-right:1px solid #ddd;${gap_color}'>$gapx</td>"
                     . "<td style='padding:3px;text-align:center;border-right:1px solid #ddd;'>"
                        . "<span>$priority_no</span>&nbsp;"
                        . "<img src='".XOCP_SERVER_SUBDIR."/images/triangle_down.png' style='vertical-align:middle;cursor:pointer;' onclick='wizard_competency_priority_down(\"$request_id\",\"$competency_id\",\"$priority_no\",this,event);'/>"
                        . "<img src='".XOCP_SERVER_SUBDIR."/images/triangle_up.png' style='vertical-align:middle;cursor:pointer;' onclick='wizard_competency_priority(\"$request_id\",\"$competency_id\",\"$priority_no\",this,event);'/>"
                        /*
                        . "<input onclick='wizard_competency_priority(\"$request_id\",\"$competency_id\",\"$priority_no\",this,event);' type='button' "
                        . "style='width:20px;-moz-border-radius:5px 0 0 5px;"
                        . "border-top:1px solid #ccc;"
                        . "border-right:1px solid #999;"
                        . "border-bottom:1px solid #999;"
                        . "border-left:1px solid #ccc;"
                        . "font-size:0.8em;"
                        . "text-align:right;' value='&#9661;'/>"
                        . "<input onclick='wizard_competency_priority_down(\"$request_id\",\"$competency_id\",\"$priority_no\",this,event);' type='button' "
                        . "style='width:20px;-moz-border-radius:0 5px 5px 0;"
                        . "border-top:1px solid #ccc;"
                        . "border-right:1px solid #999;"
                        . "border-bottom:1px solid #999;"
                        . "border-left:0px solid #ccc;"
                        . "font-size:0.8em;"
                        . "text-align:left;' value='&#9651;'/>"
                        */
                     . "</td>"
                     . "</tr></tbody></table></div>";
               
            }
         }
         $btn = "<table style='width:100%;border-spacing:0px;'>"
              . "<colgroup><col/><col/></colgroup>"
              . "<tbody><tr>"
              . "<td style='text-align:left;'>"
                  . "&nbsp;"
              . "</td>"
              . "<td style='text-align:right;'>"
                  . "<input type='button' class='xaction' value='&lt; Previous' style='width:90px;' onclick='wizard_run_prev();'/>&nbsp;"
                  . "<input type='button' class='xaction' value='Next &gt;' style='width:90px;' onclick='wizard_run_next();'/>"
              . "</td>"
              . "</tr></tbody></table>";
      } else {
         $ret = "<div style='padding:10px;text-align:center;color:#888;font-style:italic;'>No Competency Selected. Please click Previous Button to select competency.</div>";
         $btn = "<table style='width:100%;border-spacing:0px;'>"
              . "<colgroup><col/><col/></colgroup>"
              . "<tbody><tr>"
              . "<td style='text-align:left;'>"
                  . "&nbsp;"
              . "</td>"
              . "<td style='text-align:right;'>"
                  . "<input type='button' class='xaction' value='&lt; Previous' style='width:90px;' onclick='wizard_run_prev();'/>&nbsp;"
                  . "<input type='button' class='xaction' value='Next &gt;' style='width:90px;' onclick='wizard_run_next();' disabled='1'/>"
              . "</td>"
              . "</tr></tbody></table>";
      }
      
      
      return "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>"
           . "IDP Request Wizard - Step 2 of 4 : Review Selected Competency"
           . "</div>"
           . "<div style='padding:5px;background-color:#f2f2f2;'>"
              . "<div style='padding:10px;text-align:center;'>Adjust priority for selected competencies:</div>"
              . "<div style='color:#000;max-height:317px;height:317px;overflow:auto;border:1px solid #999;background-color:#fff;padding:4px;'>$ret</div>"
           . "</div>"
           . "<div style='text-align:center;padding:10px;background-color:#f2f2f2;height:100px;'>"
           . $btn
           . "</div>";
   }
   
   
   
   function app_nextSuperiorReturnRequest($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $request_id = $args[0];
      $return_note = addslashes(trim(urldecode($args[1])));
      $sql = "UPDATE ".XOCP_PREFIX."idp_request SET status_cd = 'employee' WHERE request_id = '$request_id' AND status_cd = 'approval2'";
      $db->query($sql);
      $sql = "REPLACE INTO ".XOCP_PREFIX."idp_request_return_note (request_id,return_note,user_id)"
           . " VALUES ('$request_id','$return_note','$user_id')";
      $db->query($sql);
      
      
      $sql = "SELECT employee_id FROM ".XOCP_PREFIX."idp_request WHERE request_id = '$request_id'";
      $result = $db->query($sql);
      list($employee_id)=$db->fetchRow($result);
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
      _idp_send_notification($user_idx,$request_id,"_IDP_YOURIDPREQUESTRETURNED2","","NEXTSUPERIORRETURNREQUEST",$user_id);
      
      //// hide follow notification
      $sql = "SELECT notification_id"
           . " FROM ".XOCP_PREFIX."idp_notifications"
           . " WHERE request_id = '$request_id'"
           . " AND message_id = '_IDP_YOUHAVEAPPROVAL2'"
           . " AND source_app = 'SUPERIORAPPROVAL1REQUEST'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($notification_id)=$db->fetchRow($result)) {
            _idp_notification_followed($notification_id);
         }
      }
      
      //// notify superior
      $sql = "SELECT created_user_id FROM ".XOCP_PREFIX."idp_request WHERE request_id = '$request_id'";
      $result = $db->query($sql);
      list($approval1_user_id)=$db->fetchRow($result);
      _idp_send_notification($approval1_user_id,$request_id,"_IDP_YOURAPPROVAL1RETURNED","","NEXTSUPERIORRETURNREQUEST",$user_id);
      
      return $this->renderRequest($request_id);
   }
   
   
   
   function app_checkBeforeSubmit($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $ok = 1;
      
      /// check activities
      $error_project_no_activity = array();
      $error_kpo = array();
      $sql = "SELECT project_id,project_nm,kpo FROM ".XOCP_PREFIX."idp_project WHERE request_id = '$request_id' ORDER BY priority_no";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($project_id,$project_nm,$kpo)=$db->fetchRow($result)) {
            $sql = "SELECT count(*) FROM ".XOCP_PREFIX."idp_project_activities WHERE request_id = '$request_id' AND project_id = '$project_id' AND status_cd != 'nullified'";
            $ra = $db->query($sql);
            list($cnta)=$db->fetchRow($ra);
            if($cnta<=0) {
               $ok=0;
               $error_project_no_activity[$project_id] = $project_nm;
            }
            if(trim($kpo)=="") {
               $error_kpo[$project_id] = array($project_nm);
               $ok=0;
            }
         }
      }
      
      
      $error_activity_nm = array();
      $error_activity_start = array();
      $error_activity_stop = array();
      //// check time frame
      $sql = "SELECT a.activity_nm,a.activity_start_dttm,a.activity_stop_dttm,"
           . "b.project_nm,a.project_id,a.activity_id,b.kpo"
           . " FROM ".XOCP_PREFIX."idp_project_activities a"
           . " LEFT JOIN ".XOCP_PREFIX."idp_project b USING(request_id,project_id)"
           . " WHERE a.request_id = '$request_id' AND a.status_cd = 'normal'";
      $rmin = $db->query($sql);
      if($db->getRowsNum($rmin)>0) {
         while(list($activity_nm,$activity_start_dttm,$activity_stop_dttm,$project_nm,$project_id,$activity_id,$kpo)=$db->fetchRow($rmin)) {
            if(trim($activity_nm)=="") {
               $error_activity_nm[$project_id] = array($project_nm);
               $ok=0;
            }
            if($activity_start_dttm=="0000-00-00 00:00:00") {
               $error_activity_start[$project_id][$activity_id] = array($project_nm,$activity_nm);
               $ok=0;
            }
            if($activity_stop_dttm=="0000-00-00 00:00:00") {
               $error_activity_stop[$project_id][$activity_id] = array($project_nm,$activity_nm);
               $ok=0;
            }
         }
      }
      
      $error_institute = $error_plan_project = $error_plan_method = $error_plan_self = $error_plan_start = $error_plan_stop = $error_comp = array();
      $sql = "SELECT a.plan_start_dttm,a.plan_stop_dttm,a.method_id,a.method_t,b.competency_nm,b.competency_abbr,"
           . "a.competency_id,c.method_type,a.actionplan_id,a.method_id,a.selfstudy_id,a.project_id,d.focus_dev,"
           . "a.method_subject,a.other_institute_nm"
           . " FROM ".XOCP_PREFIX."idp_request_actionplan a"
           . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
           . " LEFT JOIN ".XOCP_PREFIX."idp_development_method_type c ON c.method_t = a.method_t"
           . " LEFT JOIN ".XOCP_PREFIX."idp_request_competency d ON d.request_id = a.request_id AND d.competency_id = a.competency_id"
           . " WHERE a.request_id = '$request_id' AND a.status_cd != 'nullified'";
      $rmin = $db->query($sql);
      _debuglog($sql);
      if($db->getRowsNum($rmin)>0) {
         while(list($plan_start_dttm,$plan_stop_dttm,$method_id,$method_t,$competency_nm,$competency_abbr,$competency_id,
                    $method_type,$actionplan_id,$method_id,$selfstudy_id,$project_id,$focus_dev,
                    $method_subject,$other_institute_nm)=$db->fetchRow($rmin)) {
            if(trim($focus_dev)=="") {
               $error_comp[$competency_id] = array($competency_nm);
               $ok=0;
            }
            switch($method_t) {
               case "PROJECT":
                  if($project_id==0) {
                     $error_plan_project[$competency_id][$actionplan_id] = array($competency_nm,$method_type);
                     $ok=0;
                  }
                  break;
               case "TRN_IN":
               case "TRN_EX":
               case "SELF":
                  if($method_t!="SELF"&&$method_id==0&&trim($method_subject=="")) {
                     $error_plan_method[$competency_id][$actionplan_id] = array($competency_nm,$method_type);
                     $ok=0;
                  }
                  if($method_t=="TRN_EX"&&$method_id==0&&trim($other_institute_nm=="")) {
                     $error_institute[$competency_id][$actionplan_id] = array($competency_nm,$method_type);
                     $ok=0;
                  }
                  if($method_t=="SELF"&&$selfstudy_id==0) {
                     $error_plan_self[$competency_id][$actionplan_id] = array($competency_nm,$method_type);
                     $ok=0;
                  }
                  if($plan_start_dttm=="0000-00-00 00:00:00") {
                     $error_plan_start[$competency_id][$actionplan_id] = array($competency_nm,$method_type);
                     $ok=0;
                  }
                  if($plan_stop_dttm=="0000-00-00 00:00:00") {
                     $error_plan_stop[$competency_id][$actionplan_id] = array($competency_nm,$method_type);
                     $ok=0;
                  }
                  break;
               default:
                  break;
            }
         }
      }
      
      
      if($ok==1) {
         $ret = "<div style='background-color:#f2f2f2;font-weight:bold;font-size:1.1em;height:300px;padding:20px;text-align:center;'>You are finish and going to submit this IDP request."
              . "<br/><br/>Are you sure?"
              . "<br/><br/><br/>"
              . "<input type='button' value='Yes (Submit IDP)' onclick='confirm_employee_submit_request(\"$request_id\");'/>&nbsp;"
              . "<input type='button' value='No' onclick='empsubmitbox.fade();'/></div>";
         $h = 190;
      } else {
         $error = "";
         $cnt = 0;
         
         foreach($error_comp as $x=>$y) {
            list($competency_nm)=$y;
            $error .= "<li>Focus of Development is still empty in $competency_nm</li>";
            $cnt++;
         }
         foreach($error_plan_project as $x=>$y) {
            foreach($y as $v) {
               list($competency_nm,$method_type)=$v;
               $error .= "<li>Project is still empty in $competency_nm</li>";
               $cnt++;
            }
         }
         foreach($error_plan_method as $x=>$y) {
            foreach($y as $v) {
               list($competency_nm,$method_type)=$v;
               $error .= "<li>Action Plan is empty in $competency_nm - $method_type</li>";
               $cnt++;
            }
         }
         foreach($error_institute as $x=>$y) {
            foreach($y as $v) {
               list($competency_nm,$method_type)=$v;
               $error .= "<li>Institute / Training Provider is empty in $competency_nm - $method_type</li>";
               $cnt++;
            }
         }
         foreach($error_plan_self as $x=>$y) {
            foreach($y as $v) {
               list($competency_nm,$method_type)=$v;
               $error .= "<li>Self study type is undefined in $competency_nm</li>";
               $cnt++;
            }
         }
         foreach($error_plan_start as $x=>$y) {
            foreach($y as $v) {
               list($competency_nm,$method_type)=$v;
               $error .= "<li>Start date is not set in $competency_nm - $method_type</li>";
               $cnt++;
            }
         }
         foreach($error_plan_stop as $x=>$y) {
            foreach($y as $v) {
               list($competency_nm,$method_type)=$v;
               $error .= "<li>Stop date is not set in $competency_nm - $method_type</li>";
               $cnt++;
            }
         }
         foreach($error_project_no_activity as $project_id=>$project_nm) {
            $error .= "<li>Project '<span style='font-weight:bold;color:#555;'>$project_nm</span>' has no activity.</li>";
            $cnt++;
         }
         foreach($error_kpo as $id=>$v) {
            list($project_nm)=$v;
            $error .= "<li>KPO is still empty in '<span style='font-weight:bold;color:#555;'>$project_nm</span>'</li>";
            $cnt++;
         }
         foreach($error_activity_nm as $id=>$v) {
            list($project_nm)=$v;
            $error .= "<li>Activity is still empty in project '<span style='font-weight:bold;color:#555;'>$project_nm</span>'</li>";
            $cnt++;
         }
         foreach($error_activity_start as $id=>$v) {
            foreach($v as $activity_id=>$vv) {
               list($project_nm,$activity_nm)=$vv;
               $error .= "<li>Activity start date is still empty in '<span style='font-weight:bold;color:#555;'>$project_nm - $activity_nm</span>'</li>";
               $cnt++;
            }
         }
         foreach($error_activity_stop as $id=>$v) {
            foreach($v as $activity_id=>$vv) {
               list($project_nm,$activity_nm)=$vv;
               $error .= "<li>Activity stop date is still empty in '<span style='font-weight:bold;color:#555;'>$project_nm - $activity_nm</span>'</li>";
               $cnt++;
            }
         }
         $ret = "<div style='background-color:#f2f2f2;font-weight:bold;font-size:1.1em;height:300px;padding:20px;text-align:center;'>Request is not complete. Please check the following:"
              . "<div style='max-height:200px;overflow:auto;text-align:left;padding:5px;font-weight:normal;'><ul>$error</ul></div>"
              . "<input style='width:100px;' type='button' value='Ok' onclick='empsubmitbox.fade();'/></div>";
         $h = 150;
         $add = (17*$cnt);
         if($add<=200) {
            $h += $add;
         } else {
            $h += 200;
         }
      }
      return array($ret,$h);
   }
   
   function app_checkBeforeReturn($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $ret = "<div style='background-color:#f2f2f2;font-weight:bold;font-size:1.1em;height:300px;padding:20px;text-align:center;'>You are going to return this IDP request."
              . "<br/><br/>Are you sure?"
              . "<br/><br/><br/>"
              . "<input type='button' value='Yes (Return IDP) ret' onclick='confirm_employee_return_request(\"$request_id\");'/>&nbsp;"
              . "<input type='button' value='No' onclick='empreturnbox.fade();'/></div>";
         $h = 190;
      return array($ret,$h);
   }
   
   function app_superiorRejectRequest($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      //$sql = "UPDATE ".XOCP_PREFIX."idp_request SET status_cd = 'approval3' WHERE request_id = '$request_id' AND status_cd = 'approval1'";
      //$db->query($sql);
      return $this->renderRequest($request_id);
   }
   
   function app_superiorReturnRequest($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $request_id = $args[0];
      $return_note = addslashes(trim(urldecode($args[1])));
      $sql = "UPDATE ".XOCP_PREFIX."idp_request SET status_cd = 'employee' WHERE request_id = '$request_id' AND status_cd = 'approval1'";
      $db->query($sql);
      $sql = "REPLACE INTO ".XOCP_PREFIX."idp_request_return_note (request_id,return_note,user_id)"
           . " VALUES ('$request_id','$return_note','$user_id')";
      $db->query($sql);
      
      //// hide follow notification
      $sql = "SELECT notification_id"
           . " FROM ".XOCP_PREFIX."idp_notifications"
           . " WHERE request_id = '$request_id'"
           . " AND message_id = '_IDP_YOUHAVEAPPROVAL1'"
           . " AND source_app = 'EMPLOYEESUBMITREQUEST'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($notification_id)=$db->fetchRow($result)) {
            _idp_notification_followed($notification_id);
         }
      }
      
      //// hide follow notification
      $sql = "SELECT notification_id"
           . " FROM ".XOCP_PREFIX."idp_notifications"
           . " WHERE request_id = '$request_id'"
           . " AND message_id = '_IDP_YOURIDPREQUESTRETURNED2'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($notification_id)=$db->fetchRow($result)) {
            _idp_notification_followed($notification_id);
         }
      }
      
      //// hide follow notification
      $sql = "SELECT notification_id"
           . " FROM ".XOCP_PREFIX."idp_notifications"
           . " WHERE request_id = '$request_id'"
           . " AND message_id = '_IDP_YOURAPPROVAL1RETURNED'"
           . " AND source_app = 'NEXTSUPERIORRETURNREQUEST'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($notification_id)=$db->fetchRow($result)) {
            _idp_notification_followed($notification_id);
         }
      }
      
      //// notify employee
      $sql = "SELECT c.user_id FROM ".XOCP_PREFIX."idp_request a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."users c USING(person_id)"
           . " WHERE a.request_id = '$request_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($employee_user_id)=$db->fetchRow($result);
         _idp_send_notification($employee_user_id,$request_id,"_IDP_YOURIDPREQUESTRETURNED","","SUPERIORRETURNREQUEST",$user_id);
      }
      
      return $this->renderRequest($request_id);
   }
   
   function app_superiorApproveRequest($args) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $db=&Database::getInstance();
      $request_id = $args[0];
      $user_id = getUserID();
      $sql = "SELECT employee_id FROM ".XOCP_PREFIX."idp_request WHERE request_id = '$request_id'";
      $result = $db->query($sql);
      list($employee_id)=$db->fetchRow($result);
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
      $sql = "UPDATE ".XOCP_PREFIX."idp_request SET status_cd = 'approval2', approve_superior_dttm = now() WHERE request_id = '$request_id' AND status_cd = 'approval1'";
      $db->query($sql);
      
      _idp_send_notification($user_idx,$request_id,"_IDP_YOURIDPREQUESTAPPROVED1","","SUPERIORAPPROVAL1REQUEST",$user_id);
      
      //// hide follow notification
      $sql = "SELECT notification_id"
           . " FROM ".XOCP_PREFIX."idp_notifications"
           . " WHERE request_id = '$request_id'"
           . " AND message_id = '_IDP_YOURIDPREQUESTRETURNED2'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($notification_id)=$db->fetchRow($result)) {
            _idp_notification_followed($notification_id);
         }
      }
      
      $sql = "SELECT a.employee_id FROM ".XOCP_PREFIX."employee_job a"
           . " WHERE a.job_id = '$next_assessor_job_id'"
           . " AND a.stop_dttm = '0000-00-00 00:00:00'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($next_superior_employee_id)=$db->fetchRow($result)) {
            list($next_superior_job_id,
                 $next_superior_employee_idx,
                 $next_superior_job_nm,
                 $next_superior_nm,
                 $next_superior_nip,
                 $next_superior_gender,
                 $next_superior_jobstart,
                 $next_superior_entrance_dttm,
                 $next_superior_jobage,
                 $next_superior_job_summary,
                 $next_superior_person_id,
                 $next_superior_user_idx,
                 $next_superior_first_assessor_job_id,
                 $next_superior_next_assessor_job_id)=_hris_getinfobyemployeeid($next_superior_employee_id);
            _idp_send_notification($next_superior_user_idx,$request_id,"_IDP_YOUHAVEAPPROVAL2","","SUPERIORAPPROVAL1REQUEST",$user_id);
         }
      }
           
      //// hide follow notification
      $sql = "SELECT notification_id"
           . " FROM ".XOCP_PREFIX."idp_notifications"
           . " WHERE request_id = '$request_id'"
           . " AND message_id = '_IDP_YOUHAVEAPPROVAL1'"
           . " AND source_app = 'EMPLOYEESUBMITREQUEST'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($notification_id)=$db->fetchRow($result)) {
            _idp_notification_followed($notification_id);
         }
      }
      
      //// hide follow notification
      $sql = "SELECT notification_id"
           . " FROM ".XOCP_PREFIX."idp_notifications"
           . " WHERE request_id = '$request_id'"
           . " AND message_id = '_IDP_YOURAPPROVAL1RETURNED'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($notification_id)=$db->fetchRow($result)) {
            _idp_notification_followed($notification_id);
         }
      }
      
      /// delete return note
      $sql = "DELETE FROM ".XOCP_PREFIX."idp_request_return_note WHERE request_id = '$request_id'";
      $db->query($sql);
      
      return $this->renderRequest($request_id);
   }
   
   function app_nextSuperiorApproveRequest($args) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $db=&Database::getInstance();
      $request_id = $args[0];
      $user_id = getUserID();
      $sql = "SELECT employee_id FROM ".XOCP_PREFIX."idp_request WHERE request_id = '$request_id'";
      $result = $db->query($sql);
      list($employee_id)=$db->fetchRow($result);
      $sql = "UPDATE ".XOCP_PREFIX."idp_request SET status_cd = 'approval3', approve_higher_superior_dttm = now() WHERE request_id = '$request_id' AND status_cd = 'approval2'";
      $db->query($sql);

      /** antrain plan specific link **/
      $id_created = getUserID();
      $date_created = getSQLDate();
      $id_session = "";
       $year_now = date("Y");
       $year_next = date("Y") + 1;
       $year_before = date("Y") - 1;
       $today = date("Y-m-d");
       $start_ses = $year_now."-4-1";
       $end_ses = $year_next."-3-31";

       $today_ts = strtotime($today);
       $start_ts = strtotime($start_ses);
       $end_ts = strtotime($end_ses);

       if (($today_ts >= $start_ts) && ($today_ts <= $end_ts)) {
          $id_session = $year_now;
       }elseif ($today_ts < $start_ts) {
         $id_session = $year_before;
       }elseif ($today_ts > $end_ts) {
         $id_session = $year_next;
       }

      $sql = "SELECT employee_id FROM hris_idp_request WHERE request_id = '$request_id'";
       $result = $db->query($sql);
       list($employee_id)=$db->fetchRow($result);
      $sql = "SELECT b.org_id FROM hris_employee_job a LEFT JOIN hris_jobs b ON b.job_id = a.job_id WHERE a.employee_id = $employee_id";
      $result = $db->query($sql);
      list($org_id)=$db->fetchRow($result);
      $sql = "SELECT a.person_nm"
           . " FROM hris_persons a LEFT JOIN hris_employee b ON b.person_id = a.person_id"
           . " WHERE b.employee_id = '$employee_id'";
      $result = $db->query($sql);
      list($person_nm)=$db->fetchRow($result);

       $sql = "SELECT rupiah FROM antrain_exc_rate WHERE status_cd = 'normal' AND id_global_session = $id_session";
       $result = $db->query($sql);
       list($rupiah) = $db->fetchRow($result);

      $sqlses = "SELECT psid FROM antrain_sessionss a LEFT JOIN antrain_budget b ON b.id = a.id_hris_budget WHERE b.id_global_session = '$id_session' AND b.org_id = '$org_id'";
      $resultses = $db->query($sqlses);
      list($psid)=$db->fetchRow($resultses);
       
      $sqljobx = "SELECT a.job_id,b.job_class_id FROM hris_employee_job a LEFT JOIN hris_jobs b ON b.job_id = a.job_id WHERE a.employee_id ='$employee_id'";
      $resultx = $db->query($sqljobx);
      list($job_idx,$job_class_idx)=$db->fetchRow($resultx);
      $sqly = "SELECT actionplan_id,competency_id,method_t,method_subject,other_institute_nm,plan_start_dttm,plan_stop_dttm,cost_estimate FROM hris_idp_request_actionplan WHERE request_id = '$request_id'";
      $resulty = $db->query($sqly);
      while (list($actionplan_id,$competency_id,$method_t,$method_subject,$other_institute_nm,$plan_start_dttm,$plan_stop_dttm,$cost_estimate)=$db->fetchRow($resulty)) {
        $to_dollar = $cost_estimate / $rupiah;
        if ($method_t == "TRN_EX") {
          $inst = "ext";
          $sql = "INSERT INTO antrain_plan_specific (id_antrain_session,request_id,actionplan_id,competency_id,employee_id,name,inst,id_job_class1,subject,schedule_start,schedule_end,institution,cost,create_user_id,create_date)"
           . " VALUES ('$psid','$request_id','$actionplan_id','$competency_id','$employee_id','$person_nm','$inst','$job_class_idx','$method_subject','$plan_start_dttm','$plan_stop_dttm','$other_institute_nm','$to_dollar','$id_created','$date_created')";
        $db->query($sql);
        }elseif ($method_t == "TRN_IN") {
          $inst = "int";
          $sql = "INSERT INTO antrain_plan_specific (id_antrain_session,request_id,actionplan_id,competency_id,employee_id,name,inst,id_job_class1,subject,schedule_start,schedule_end,institution,cost,create_user_id,create_date)"
           . " VALUES ('$psid','$request_id','$actionplan_id','$competency_id','$employee_id','$person_nm','$inst','$job_class_idx','$method_subject','$plan_start_dttm','$plan_stop_dttm','$other_institute_nm','$to_dollar','$id_created','$date_created')";
        $db->query($sql);
        }
       }
      /** eo antrain plan specific link **/
      
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
      _idp_send_notification($user_idx,$request_id,"_IDP_YOURIDPREQUESTAPPROVED2","","NEXTSUPERIORAPPROVEREQUEST",$user_id);
      
      //// hide follow notification
      $sql = "SELECT notification_id"
           . " FROM ".XOCP_PREFIX."idp_notifications"
           . " WHERE request_id = '$request_id'"
           . " AND message_id = '_IDP_YOUHAVEAPPROVAL2'"
           . " AND source_app = 'SUPERIORAPPROVAL1REQUEST'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($notification_id)=$db->fetchRow($result)) {
            _idp_notification_followed($notification_id);
         }
      }
      
      //// notify superior
      $sql = "SELECT created_user_id FROM ".XOCP_PREFIX."idp_request WHERE request_id = '$request_id'";
      $result = $db->query($sql);
      list($approval1_user_id)=$db->fetchRow($result);
      _idp_send_notification($approval1_user_id,$request_id,"_IDP_YOURSUBORDINATEIDPREQUESTAPPROVED2","","NEXTSUPERIORAPPROVEREQUEST",$user_id);
      
      
      
      $sql = "SELECT b.user_id"
           . " FROM ".XOCP_PREFIX."menuitems a"
           . " LEFT JOIN ".XOCP_PREFIX."user_pgroup b USING(pgroup_id)"
           . " WHERE a.param0 LIKE 'XP\\_idphrapprove\\_%'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($hr_user_id)=$db->fetchRow($result)) {
            _idp_send_notification($hr_user_id,$request_id,"_IDP_YOUHAVEHRAPPROVAL","","NEXTSUPERIORAPPROVEREQUEST",$user_id);
         }
      }
      
      return $this->renderRequest($request_id);
   }
   
   function app_HRApproveRequest($args) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $db=&Database::getInstance();
      $request_id = $args[0];
      $user_id = getUserID();
      $sql = "SELECT employee_id FROM ".XOCP_PREFIX."idp_request WHERE request_id = '$request_id'";
      $result = $db->query($sql);
      list($employee_id)=$db->fetchRow($result);
      
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
      _idp_send_notification($user_idx,$request_id,"_IDP_YOURIDPREQUESTCOMPLETE","","HRCONFIRMREQUEST",$user_id);
      
      list($self_job_id,
           $self_employee_idx,
           $self_job_nm,
           $self_nm,
           $self_nip,
           $self_gender,
           $self_jobstart,
           $self_entrance_dttm,
           $self_jobage,
           $self_job_summary,
           $self_person_id,
           $self_user_idx,
           $self_first_assessor_job_id,
           $self_next_assessor_job_id)=_hris_getinfobyemployeeid($user_id);
      $sql = "UPDATE ".XOCP_PREFIX."idp_request SET status_cd = 'implementation', approve_hris_id = '$self_job_id',  approve_hris_dttm = now() WHERE request_id = '$request_id' AND status_cd = 'approval3'";
      $db->query($sql);
      $sql = "UPDATE ".XOCP_PREFIX."idp_request_actionplan SET status_cd = 'implementation' WHERE request_id = '$request_id' AND status_cd = 'requested'";
      $db->query($sql);
      
      //// hide follow notification
      $sql = "SELECT notification_id"
           . " FROM ".XOCP_PREFIX."idp_notifications"
           . " WHERE request_id = '$request_id'"
           . " AND message_id = '_IDP_YOUHAVEHRAPPROVAL'"
           . " AND source_app = 'NEXTSUPERIORAPPROVEREQUEST'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($notification_id)=$db->fetchRow($result)) {
            _idp_notification_followed($notification_id);
         }
      }
      
      return $this->renderRequest($request_id);
   }
   
   function updateTimeFrame($request_id) {
      $db=&Database::getInstance();
      list($timeframe_start,$timeframe_stop)=_idp_get_timeframe($request_id);
      $sql = "UPDATE ".XOCP_PREFIX."idp_request SET timeframe_start = '$timeframe_start', timeframe_stop = '$timeframe_stop' WHERE request_id = '$request_id' AND status_cd = 'employee'";
      $db->query($sql);
      return array($timeframe_start,$timeframe_stop);
   }
   
   function app_employeeSubmitRequest($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $request_id = $args[0];
      $sql = "UPDATE ".XOCP_PREFIX."idp_request SET status_cd = 'approval1', requested_dttm = now() WHERE request_id = '$request_id' AND status_cd = 'employee'";
      $db->query($sql);
      $this->updateTimeFrame($request_id);
      
      $sql = "SELECT notification_id"
           . " FROM ".XOCP_PREFIX."idp_notifications"
           . " WHERE user_id = '$user_id'"
           . " AND request_id = '$request_id'"
           . " AND message_id = '_IDP_YOURIDPREQUESTSTARTED'"
           . " AND source_app = 'SUPERIORSTARTREQUEST'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($notification_id)=$db->fetchRow($result)) {
            _idp_notification_followed($notification_id);
         }
      }
      
      $sql = "SELECT notification_id"
           . " FROM ".XOCP_PREFIX."idp_notifications"
           . " WHERE user_id = '$user_id'"
           . " AND request_id = '$request_id'"
           . " AND message_id = '_IDP_YOURIDPREQUESTRETURNED'"
           . " AND source_app = 'SUPERIORRETURNREQUEST'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($notification_id)=$db->fetchRow($result)) {
            _idp_notification_followed($notification_id);
         }
      }
      
      $sql = "SELECT created_user_id FROM ".XOCP_PREFIX."idp_request WHERE request_id = '$request_id'";
      $result = $db->query($sql);
      list($approval1_user_id)=$db->fetchRow($result);
      _idp_send_notification($approval1_user_id,$request_id,"_IDP_YOUHAVEAPPROVAL1","","EMPLOYEESUBMITREQUEST",$user_id);
      
      /// delete return note
      $sql = "DELETE FROM ".XOCP_PREFIX."idp_request_return_note WHERE request_id = '$request_id'";
      $db->query($sql);
      
      //// hack untuk data entry awal
      //$sql = "UPDATE ".XOCP_PREFIX."idp_request SET status_cd = 'implementation', requested_dttm = now(), approve_superior_dttm = now(), approve_higher_superior_dttm = now(), approve_hris_dttm = now() WHERE request_id = '$request_id'";
      //_debuglog($sql);
      //$db->query($sql);
      
      return $this->renderRequest($request_id);
   }
   
   function app_superiorStartRequest($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $request_id = $args[0];
      $sql = "UPDATE ".XOCP_PREFIX."idp_request SET status_cd = 'employee', requester_id = '$user_id' WHERE request_id = '$request_id'";
      $db->query($sql);
      $sql = "SELECT c.user_id,b.employee_id FROM ".XOCP_PREFIX."idp_request a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."users c USING(person_id)"
           . " WHERE a.request_id = '$request_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($employee_user_id,$employee_id)=$db->fetchRow($result);
         if ($user_id == $employee_user_id) {
            $sql = "SELECT a.upper_employee_id,b.employee_id FROM hris_employee_job a LEFT JOIN hris_employee b USING(employee_id) WHERE b.employee_id = '$employee_id'";
            $result = $db->query($sql);
            list($upper_employee_id)=$db->fetchRow($result);

            $sql = "SELECT a.user_id FROM hris_users a LEFT JOIN hris_employee b USING(person_id )WHERE b.employee_id = '$upper_employee_id'";
            $result = $db->query($sql);
            list($user_idy)=$db->fetchRow($result);

            $employee_user_id = $user_idy;
          }
         _idp_send_notification($employee_user_id,$request_id,"_IDP_YOURIDPREQUESTSTARTED","","SUPERIORSTARTREQUEST",$user_id);
      }
      return $this->renderRequest($request_id);
   }
   
   function app_deleteProjectActivity($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $project_id = $args[1];
      $activity_id = $args[2];
      $sql = "UPDATE ".XOCP_PREFIX."idp_project_activities SET status_cd = 'nullified'"
           . " WHERE request_id = '$request_id' AND project_id = '$project_id' AND activity_id = '$activity_id'";
      $db->query($sql);
      $newactlist = array();
      $sql = "SELECT competency_id FROM ".XOCP_PREFIX."idp_request_competency WHERE request_id = '$request_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($competency_id)=$db->fetchRow($result)) {
            $newactlist[] = array($request_id,$competency_id,$this->renderActionList($request_id,$competency_id));
         }
      }
      list($start_dttm,$stop_dttm)=$this->updateTimeFrame($request_id);
      $sql = "SELECT activity_id"
           . " FROM ".XOCP_PREFIX."idp_project_activities"
           . " WHERE request_id = '$request_id'"
           . " AND project_id = '$project_id'"
           . " AND status_cd = 'normal'"
           . " ORDER BY activity_id";
      $result = $db->query($sql);
      $actcount = 0;
      $actstep = 0;
      $last_act = 0;
      $arract = array();
      if($db->getRowsNum($result)>0) {
         while(list($activity_idx)=$db->fetchRow($result)) {
            if($activity_id==0) $activity_id = $activity_idx;
            $arract[] = $activity_idx;
            if($activity_id==$activity_idx) {
               $actstep = $actcount;
            }
            $actcount++;
            $last_act = $activity_idx;
         }
      }
      
      return array($request_id,$newactlist,sql2ind($start_dttm,"date"),sql2ind($stop_dttm,"date"),$arract);
   }
   
   function app_saveProjectActivity($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $project_id = $args[1];
      $activity_id = $args[2];
      $activity_nm = urldecode($args[3]);
      $activity_nm_txt = addslashes($activity_nm);
      $start = getSQLDate($args[4]);
      $stop = getSQLDate($args[5]);
      $kpd = urldecode($args[6]);
      $kpd_txt = addslashes($kpd);
      $sql = "UPDATE ".XOCP_PREFIX."idp_project_activities SET activity_nm = '$activity_nm_txt', activity_start_dttm = '$start', activity_stop_dttm = '$stop', kpd = '$kpd_txt'"
           . " WHERE request_id = '$request_id' AND project_id = '$project_id' AND activity_id = '$activity_id'";
      $db->query($sql);
      $this->updateTimeFrame($request_id);
      
      $newactlist = array();
      $sql = "SELECT competency_id FROM ".XOCP_PREFIX."idp_request_competency WHERE request_id = '$request_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($competency_id)=$db->fetchRow($result)) {
            $newactlist[] = array($request_id,$competency_id,$this->renderActionList($request_id,$competency_id));
         }
      }
      
      list($startframe,$stopframe)=_idp_get_timeframe($request_id);
      
      $sql = "SELECT MIN(activity_start_dttm) FROM ".XOCP_PREFIX."idp_project_activities WHERE request_id = '$request_id' AND project_id = '$project_id' AND status_cd IN ('normal','finish')";
      $rmin = $db->query($sql);
      if($db->getRowsNum($rmin)>0) {
         list($minstart0)=$db->fetchRow($rmin);
      }
      
      $sql = "SELECT MAX(activity_stop_dttm) FROM ".XOCP_PREFIX."idp_project_activities WHERE request_id = '$request_id' AND project_id = '$project_id' AND status_cd IN ('normal','finish')";
      $rmax = $db->query($sql);
      if($db->getRowsNum($rmax)>0) {
         list($maxstop0)=$db->fetchRow($rmax);
      }
      
      $sql = "UPDATE ".XOCP_PREFIX."idp_project SET start_dttm = '$minstart0', due_dttm = '$maxstop0' WHERE request_id = '$request_id' AND project_id = '$project_id'";
      $db->query($sql);
      
      return array($request_id,$project_id,$activity_id,"$activity_nm [<span class='ylnk' onclick='edit_activity(\"$request_id\",\"$project_id\",\"$activity_id\");'>edit</span>]",sql2ind($start,"date")." - ".sql2ind($stop,"date"),$kpd,$newactlist,sql2ind($startframe,"date"),sql2ind($stopframe,"date"));
   }
   
   function app_editProjectActivity($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $project_id = $args[1];
      $activity_id = $args[2];
      
      $sql = "SELECT project_nm,kpo,priority_no FROM ".XOCP_PREFIX."idp_project WHERE request_id = '$request_id' AND project_id = '$project_id'";
      $result = $db->query($sql);
      list($project_nm,$kpo,$priority_no)=$db->fetchRow($result);
      
      $sql = "SELECT activity_id,activity_nm,kpd,activity_start_dttm,activity_stop_dttm FROM ".XOCP_PREFIX."idp_project_activities"
           . " WHERE request_id = '$request_id' AND project_id = '$project_id' AND status_cd = 'normal' AND activity_id = '$activity_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($activity_id,$activity_nm,$kpd,$activity_start_dttm,$activity_stop_dttm)=$db->fetchRow($result);
         
         $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>Edit Activity</div>"
              . "<div id='actformeditor' style='padding:5px;'>"
                  . "<table style='width:100%;'>"
                  . "<colgroup>"
                     . "<col width='200'/>"
                     . "<col/>"
                  . "</colgroup>"
                  . "<tbody>"
                  . "<tr><td style='text-align:right;vertical-align:top;'>Project Assignment : </td><td style='font-weight:bold;'>$project_nm</td></tr>"
                  . "<tr><td style='text-align:right;vertical-align:top;'>Activity : </td><td><textarea style='width:95%;height:70px;' id='editactivity_${request_id}_${project_id}_${activity_id}'>$activity_nm</textarea></td></tr>"
                  . "<tr><td style='text-align:right;vertical-align:top;padding-top:5px;'>Time Frame : </td><td style='padding:5px;'>"
                        . "<table style='width:100%;border-spacing:0px;'><colgroup><col width='50%'/><col/></colgroup><tbody><tr>"
                              . "<td style='padding:0px;'>Start : <span onclick='_changedatetime(\"sp_activity_start_dttm\",\"h_activity_start_dttm\",\"date\",true,false);' id='sp_activity_start_dttm' class='xlnk'>".sql2ind($activity_start_dttm,"date")."</span><input type='hidden' id='h_activity_start_dttm' value='$activity_start_dttm'/></td>"
                              . "<td style='padding:0px;'>Stop : <span onclick='_changedatetime(\"sp_activity_stop_dttm\",\"h_activity_stop_dttm\",\"date\",true,false);' id='sp_activity_stop_dttm' class='xlnk'>".sql2ind($activity_stop_dttm,"date")."</span><input type='hidden' id='h_activity_stop_dttm' value='$activity_stop_dttm'/></td></tr></tbody></table>"
                     . "</td></tr>"
                  . "<tr><td style='text-align:right;vertical-align:top;'>Key Performance Driver (KPD) : </td><td><textarea style='width:95%;height:70px;' id='editkpd_${request_id}_${project_id}_${activity_id}'>$kpd</textarea></td></tr>"
                  . "</tbody></table>"
              . "</div>"
              . "<div id='actformbtn' style='text-align:center;padding:10px;'>"
                  . "<input type='button' value='"._SAVE."' style='' onclick='save_activity(\"$request_id\",\"$project_id\",\"$activity_id\",this,event);'/>&nbsp;&nbsp;"
                  . "<input type='button' value='"._CANCEL."' style='' onclick='actbox.fade();'/>&nbsp;&nbsp;&nbsp;&nbsp;"
                  . "<input type='button' value='"._DELETE."' style='' onclick='delete_activity(\"$request_id\",\"$project_id\",\"$activity_id\",this,event);'/>"
              . "</div>";
         
         return array($request_id,$project_id,$activity_id,$ret);
      }
   }
   
   function renderProjectActivityList($request_id,$project_id) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $sql = "SELECT c.job_id,b.employee_id"
           . " FROM ".XOCP_PREFIX."users a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(person_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job c USING(employee_id)"
           . " WHERE a.user_id = '$user_id'";
      $result = $db->query($sql);
      list($self_job_id,$self_employee_id)=$db->fetchRow($result);
      
      $sql = "SELECT a.employee_id,a.status_cd"
           . " FROM ".XOCP_PREFIX."idp_request a"
           . " WHERE a.request_id = '$request_id'";
      $result = $db->query($sql);
      $self = TRUE;
      if($db->getRowsNum($result)==1) {
         list($employee_id,$req_status_cd)=$db->fetchRow($result);
         if($employee_id==$self_employee_id) {
            $self = TRUE;
         } else {
            $self = FALSE;
         }
      }
      
      $sql = "SELECT activity_id,activity_nm,kpd,activity_start_dttm,activity_stop_dttm,status_cd FROM ".XOCP_PREFIX."idp_project_activities"
           . " WHERE request_id = '$request_id' AND project_id = '$project_id' AND status_cd IN ('normal','finish')"
           . " ORDER BY activity_id,activity_start_dttm,activity_stop_dttm";
      $result = $db->query($sql);
      $activity_txt = "";
      if($db->getRowsNum($result)>0) {
         $no = 0;
         while(list($activity_id,$activity_nm,$kpd,$activity_start_dttm,$activity_stop_dttm,$status_cd)=$db->fetchRow($result)) {
            if(trim($activity_nm)=="") {
               $activity_nm = _EMPTY;
            }
            if(trim($kpd)=="") {
               $kpd = _EMPTY;
            }
            
            if($_SESSION["xocp_page_id"]=="idphrapprove") {
               $btneditactivity = " [<span class='ylnk' onclick='edit_activity(\"$request_id\",\"$project_id\",\"$activity_id\");'>edit</span>]";
            } else {
               $btneditactivity = ($self==TRUE&&$req_status_cd=="employee"?" [<span class='ylnk' onclick='edit_activity(\"$request_id\",\"$project_id\",\"$activity_id\");'>edit</span>]":"");
            }
            
            $activity_txt .= "<div style='padding:0px;".($status_cd=="finish"?"background-color:#dff;":"")."' id='dvactivitylist_${request_id}_${project_id}_${activity_id}'>"
                         . "<table class='tblidpcom' style='border-spacing:0px;'><colgroup>"
                         . "<col width='*'/>"
                         . "<col width='*'/>"
                         . "<col width='*'/>"
                         . "</colgroup>"
                         . "<tbody>"
                         . "<tr>"
                         . "<td style='padding:0px;'>"
                              . "<div style='padding:4px;width:192px !important;' id='projactnm_${request_id}_${project_id}_${activity_id}'>$activity_nm"
                              ///. (TRUE?" [<span class='ylnk' onclick='edit_activity(\"$request_id\",\"$project_id\",\"$activity_id\");'>edit</span>]":"")
                              . $btneditactivity
                              . "</div>"
                         . "</td>"
                         . "<td style='padding:0px;'>"
                              . "<div style='padding:4px;width:252px !important;text-align:center;' id='projtmfrm_${request_id}_${project_id}_${activity_id}'>".sql2ind($activity_start_dttm,"date")." - ".sql2ind($activity_stop_dttm,"date")."</div>"
                              . ($status_cd=="finish"?"<div style='color:green;text-align:center;padding:2px;'>Completed</div>":"")
                         . "</td>"
                         . "<td style='border-right:0px;padding:0px;'>"
                              . "<div style='padding:4px;width:135px !important;' id='projkpd_${request_id}_${project_id}_${activity_id}'>$kpd</div>"
                         . "</td>"
                         . "</tr>"
                         . "</tbody>"
                         . "</table>"
                         . "</div>";
         
         }
      }
      
      return $activity_txt;
   }
   
   function app_addActivity($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $project_id = $args[1];
      $activity_id = 0;
      $sql = "SELECT MAX(activity_id) FROM ".XOCP_PREFIX."idp_project_activities"
           . " WHERE request_id = '$request_id' AND project_id = '$project_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($activity_id)=$db->fetchRow($result);
      }
      $activity_id++;
      $sql = "INSERT INTO ".XOCP_PREFIX."idp_project_activities (request_id,project_id,activity_id)"
           . " VALUES ('$request_id','$project_id','$activity_id')";
      $db->query($sql);
      
      $newactlist = array();
      $sql = "SELECT competency_id FROM ".XOCP_PREFIX."idp_request_competency WHERE request_id = '$request_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($competency_id)=$db->fetchRow($result)) {
            $newactlist[] = array($request_id,$competency_id,$this->renderActionList($request_id,$competency_id));
         }
      }
      
      
      list($start_dttm,$stop_dttm)=$this->updateTimeFrame($request_id);
      
      $sql = "SELECT activity_id"
           . " FROM ".XOCP_PREFIX."idp_project_activities"
           . " WHERE request_id = '$request_id'"
           . " AND project_id = '$project_id'"
           . " AND status_cd = 'normal'"
           . " ORDER BY activity_id";
      $result = $db->query($sql);
      $actcount = 0;
      $actstep = 0;
      $last_act = 0;
      if($db->getRowsNum($result)>0) {
         while(list($activity_idx)=$db->fetchRow($result)) {
            if($activity_id==0) $activity_id = $activity_idx;
            $arract[] = $activity_idx;
            if($activity_id==$activity_idx) {
               $actstep = $actcount;
            }
            $actcount++;
            $last_act = $activity_idx;
         }
      }
      
      if($activity_id==-1) {
         $activity_id = $last_act;
         $actstep = $actcount-1;
      }
      
      return array($request_id,$project_id,$activity_id,$this->renderProjectActivityList($request_id,$project_id),sql2ind($start_dttm,"date"),sql2ind($stop_dttm,"date"),$newactlist,$arract,$actstep);
   }
   
   function app_saveKPO($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $project_id = $args[1];
      $kpo = addslashes(trim(urldecode($args[2])));
      $kpo_txt = trim(urldecode($args[2]));
      if($kpo_txt=="") {
         //$kpo_txt = "<span style='font-style:italic;'>Untitled Project</span>";
         //$kpo = "Untitled Project";
      }
      $sql = "UPDATE ".XOCP_PREFIX."idp_project SET kpo = '$kpo' WHERE request_id = '$request_id' AND project_id = '$project_id'";
      $db->query($sql);
      
      $kpo_txt = "<div>".nl2br($kpo_txt)
              . " [<span onclick='edit_kpo(\"$request_id\",\"$project_id\",this,event);' class='ylnk'>edit</span>]"
              . "</div>";
      
      return array($request_id,$project_id,$kpo_txt);
   }
   function app_editKPO($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $project_id = $args[1];
      $sql = "SELECT project_nm,kpo,priority_no FROM ".XOCP_PREFIX."idp_project WHERE request_id = '$request_id' AND project_id = '$project_id'";
      $result = $db->query($sql);
      list($project_nm,$kpo,$priority_no)=$db->fetchRow($result);
      
      $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>Edit Key Performance Outcome (KPO)</div>"
           . "<div style='padding:5px;'>"
               . "<table style='width:100%;'>"
               . "<colgroup>"
                  . "<col width='220'/>"
                  . "<col/>"
               . "</colgroup>"
               . "<tbody>"
               . "<tr><td style='text-align:right;vertical-align:top;'>Project Assingment : </td><td style='font-weight:bold;'>$project_nm</td></tr>"
               . "<tr><td style='text-align:right;vertical-align:top;'>Key Performance Outcome (KPO) : </td><td><textarea style='width:95%;height:70px;' id='editprojkpo_${request_id}_${project_id}'>$kpo</textarea></td></tr>"
               . "</tbody></table>"
           . "</div>"
           . "<div style='text-align:center;padding:10px;'>"
               . "<input type='button' value='"._SAVE."' style='width:90px;' onclick='save_project_kpo(\"$request_id\",\"$project_id\",this,event);'/>&nbsp;&nbsp;"
               . "<input type='button' value='"._CANCEL."' style='width:90px;' onclick='projkpobox.fade();'/>"
           . "</div>";
      
      return array($request_id,$project_id,$ret);
   }
   
   
   
   function app_deleteProject($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $project_id = $args[1];
      $sql = "DELETE FROM ".XOCP_PREFIX."idp_project WHERE request_id = '$request_id' AND project_id = '$project_id'";
      $result = $db->query($sql);
      $sql = "DELETE FROM ".XOCP_PREFIX."idp_project_activities WHERE request_id = '$request_id' AND project_id = '$project_id'";
      $result = $db->query($sql);
      $sql = "UPDATE ".XOCP_PREFIX."idp_request_actionplan SET project_id = '0' WHERE request_id = '$request_id' AND project_id = '$project_id' AND method_t = 'PROJECT'";
      $db->query($sql);
      $newactlist = array();
      $sql = "SELECT competency_id FROM ".XOCP_PREFIX."idp_request_competency WHERE request_id = '$request_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($competency_id)=$db->fetchRow($result)) {
            $newactlist[] = array($request_id,$competency_id,$this->renderActionList($request_id,$competency_id));
         }
      }
      return array($request_id,$project_id,$newactlist);
   }
   
   function app_saveProjectName($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $project_id = $args[1];
      $project_nm = addslashes(trim(urldecode($args[2])));
      $project_nm_txt = trim(urldecode($args[2]));
      $cost_estimate = ($args[3]+0);
      $arrcomp = _parseForm($args[4]);
      if($project_nm_txt=="") {
         $project_nm_txt = "<span style='font-style:italic;'>Untitled Project</span>";
         $project_nm = "Untitled Project";
      }
      $sql = "UPDATE ".XOCP_PREFIX."idp_project SET project_nm = '$project_nm', cost_estimate = '$cost_estimate' WHERE request_id = '$request_id' AND project_id = '$project_id'";
      $db->query($sql);
      
      //// set project for each competency
      $sql = "UPDATE ".XOCP_PREFIX."idp_request_actionplan SET project_id = '0' WHERE request_id = '$request_id' AND project_id = '$project_id' AND method_t = 'PROJECT'";
      $db->query($sql);
      if(is_array($arrcomp)) {
         foreach($arrcomp as $k=>$v) {
            $sql = "UPDATE ".XOCP_PREFIX."idp_request_actionplan SET project_id = '$project_id' WHERE request_id = '$request_id' AND competency_id = '$v' AND method_t = 'PROJECT'";
            $db->query($sql);
         }
      }
      
      $project_txt = "<div id='project_${request_id}_${project_id}'><span id='sp_project_nm_${request_id}_${project_id}'>".nl2br($project_nm)."</span>"
              //. "<div style='font-size:0.9em;color:#888;'>IDR ".toMoneyShort($cost_estimate)."</div>"
              . "<div>[<span onclick='edit_project_nm(\"$request_id\",\"$project_id\",this,event);' class='ylnk'>edit</span>]</div>"
              . "</div>";
      
      $ttl_cost = _idp_calc_cost_estimate($request_id);
      $cost_txt = toMoney($ttl_cost);
      
      $newactlist = array();
      $sql = "SELECT competency_id FROM ".XOCP_PREFIX."idp_request_competency WHERE request_id = '$request_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($competency_id)=$db->fetchRow($result)) {
            $newactlist[] = array($request_id,$competency_id,$this->renderActionList($request_id,$competency_id));
         }
      }
      
      return array($request_id,$project_id,$project_txt,$cost_txt,$newactlist);
   }
   
   function app_editProjectName($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $project_id = $args[1];
      
      $sql = "SELECT project_nm,cost_estimate,priority_no FROM ".XOCP_PREFIX."idp_project WHERE request_id = '$request_id' AND project_id = '$project_id'";
      $result = $db->query($sql);
      list($project_nm,$cost_estimate,$priority_no)=$db->fetchRow($result);
      
      $sql = "SELECT a.competency_id,b.competency_nm,b.competency_abbr FROM ".XOCP_PREFIX."idp_request_competency a"
           . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
           . " WHERE a.request_id = '$request_id'"
           . " ORDER BY a.priority_no";
      $result = $db->query($sql);
      $compcnt = 0;
      if($db->getRowsNum($result)>0) {
         while(list($competency_id,$competency_nm,$competency_abbr)=$db->fetchRow($result)) {
            $sql = "SELECT project_id FROM ".XOCP_PREFIX."idp_request_actionplan WHERE request_id = '$request_id' AND method_t = 'PROJECT' AND competency_id = '$competency_id'";
            $rselcomp = $db->query($sql);
            if($db->getRowsNum($rselcomp)>0) {
               list($project_idx)=$db->fetchRow($rselcomp);
            } else {
               $project_idx = 0;
            }
            $complist .= "<div><input ".($project_idx==$project_id?"checked='1'":"")." type='checkbox' id='projcompsel_${competency_id}' name='projcompsel_${competency_id}' value='$competency_id'/> <label for='projcompsel_${competency_id}' class='xlnk'>$competency_abbr - $competency_nm</label></div>";
            $compcnt++;
         }
      } else {
         $complist = "No competency found.";
         $compcnt++;
      }
      
      $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>Edit Project Assignment</div>"
           . "<div style='padding:5px;'>"
               . "<table style='width:100%;'>"
               . "<colgroup>"
                  . "<col width='160'/>"
                  . "<col/>"
               . "</colgroup>"
               . "<tbody>"
               . "<tr><td style='text-align:right;vertical-align:top;'>Project Assignment : </td><td><input type='text' id='editprojnm_${request_id}_${project_id}' value='$project_nm' style='width:450px;'/></td></tr>"
               . "<tr><td style='text-align:right;vertical-align:top;'>Competency To Be Developed : </td><td><div id='dvprojcomplist' style='padding:10px;width:435px;border:1px solid #bbb;'>$complist</div></td></tr>"
               . "</tbody></table>"
           . "</div>"
           . "<div style='text-align:center;padding:10px;'>"
               . "<input type='button' value='"._SAVE."' style='width:90px;' onclick='save_project_nm(\"$request_id\",\"$project_id\",this,event);'/>&nbsp;&nbsp;"
               . "<input type='button' value='"._CANCEL."' style='width:90px;' onclick='projnmbox.fade();'/>"
           . "</div>";
      
      return array($request_id,$project_id,$ret,(185+($compcnt*19)));
   }
   
   function app_editProject($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $project_id = $args[1];
      $sql = "SELECT project_nm FROM ".XOCP_PREFIX."idp_project"
           . " WHERE request_id = '$request_id'"
           . " AND project_id = '$project_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($project_nm)=$db->fetchRow($result);
      }
      
      $name_txt = "<div id='projectname_${request_id}_${project_id}'>$project_nm"
              . "&nbsp;[<span onclick='edit_project_name(\"$request_id\",\"$project_id\",this,event);' class='ylnk'>edit</span>]"
              . "</div>";
              
      $activities_txt = "<div style='text-align:right;padding:3px;'><input type='button' value='Add'/></div>"
                      . "<table style='width:100%;' class='xxlist'><thead>"
                      . "<tr>"
                      . "<td>Activity</td>"
                      . "<td>Time Frame</td>"
                      . "<td>Key Performance Driver</td>"
                      . "</tr>"
                      . "</thead>"
                      . "<tbody>"
                      . "<tr id='tractivity_empty'><td colspan='3' style='text-align:center;'>"._EMPTY."</td></tr>"
                      . "</tbody>"
                      . "</table>";
      
      $ultab = ///// UL ////////////////////////////
             "<ul class='ultab'>"
           . "<li id='projlitab_0' class='ultabsel_greyrev'><span onclick='projtab(\"0\",this,event);' class='xlnk'>Activities &amp; Key Performance Drivers</span></li>"
           . "<li id='projlitab_1'><span onclick='projtab(\"1\",this,event);' class='xlnk'>Key Performance Outcome</span></li>"
           . "</ul>";
         
      $dvtab = "<div id='dv' style='min-height:100px;margin-bottom:1px;overflow:auto;border:0px solid #999999;clear:both;padding:0px;'>"
           . "<div style='border:1px solid #bbb;padding:10px;'>"
           . "<div id='projdvtab_0'>$activities_txt</div>"
           . "<div id='projdvtab_1' style='display:none;'>$kpo_txt</div>"
           . "</div>"
           . "</div>";
      
      $ret = "<div id='projcontent_${request_id}_${project_id}' style='padding:5px;padding-left:7px;border-top:1px solid #ccc;'>"
           . "<div style='padding:5px;'>$name_txt</div>"
           . $ultab
           . $dvtab
              . "<div style='margin-top:5px;border-top:0px solid #bbb;padding:2px;text-align:right;'>"
                  . "<input type='button' onclick='delete_project(\"$request_id\",\"$project_id\",\"$project_nm_txt\");' value='Delete Project'/>"
              . "</div>"
              
           . "</div>";
      return $ret;
   }
   
   function app_setProjectPriorityUp($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $project_id = $args[1];
      $priority_no = (5*$args[2])-6;
      $sql = "UPDATE ".XOCP_PREFIX."idp_project SET priority_no = (priority_no*5) WHERE request_id = '$request_id'";
      $db->query($sql);
      $sql = "UPDATE ".XOCP_PREFIX."idp_project SET priority_no = '$priority_no' WHERE request_id = '$request_id' AND project_id = '$project_id'";
      $db->query($sql);
      
      $this->sortProjectPriority($request_id);
      return array($request_id,$project_id,$this->renderIDPProject($request_id));
   }
   
   
   
   function app_addProject($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $project_id = 0;
      $sql = "SELECT MAX(project_id) FROM ".XOCP_PREFIX."idp_project WHERE request_id = '$request_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($project_id)=$db->fetchRow($result);
      }
      $project_id++;
      $sql = "INSERT INTO ".XOCP_PREFIX."idp_project (request_id,project_id,project_nm,priority_no)"
           . " VALUES ('$request_id','$project_id','Untitled Project','200')";
      $result = $db->query($sql);
      
      $this->sortProjectPriority($request_id);
      
      return array($request_id,$project_id,$this->renderProject($request_id,$project_id));
   }
   
   function renderIDPProject($request_id) {
      $db=&Database::getInstance();
      $sql = "SELECT project_id"
           . " FROM ".XOCP_PREFIX."idp_project"
           . " WHERE request_id = '$request_id'"
           . " AND status_cd = 'normal'"
           . " ORDER BY priority_no";
      $result = $db->query($sql);
      $ret = "";
      if($db->getRowsNum($result)>0) {
         while(list($project_id)=$db->fetchRow($result)) {
            $ret .= "<div id='xproj_${request_id}_${project_id}' style='padding:3px;padding-left:0px;'>".$this->renderProject($request_id,$project_id)."</div>";
         }
         $display = "display:none;";
      }
      $ret .= "<div style='${display}font-style:italic;color:#bbb;text-align:left;' id='empty_project_${request_id}'>"._EMPTY."</div>";
      return $ret;
   
   }
   
   function renderProject($request_id,$project_id) {
      $db=&Database::getInstance();
      
      $user_id = getUserID();
      $sql = "SELECT c.job_id,b.employee_id"
           . " FROM ".XOCP_PREFIX."users a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(person_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job c USING(employee_id)"
           . " WHERE a.user_id = '$user_id'";
      $result = $db->query($sql);
      list($self_job_id,$self_employee_id)=$db->fetchRow($result);
      
      $sql = "SELECT a.employee_id,a.status_cd"
           . " FROM ".XOCP_PREFIX."idp_request a"
           . " WHERE a.request_id = '$request_id'";
      $result = $db->query($sql);
      $self = TRUE;
      if($db->getRowsNum($result)==1) {
         list($employee_id,$req_status_cd)=$db->fetchRow($result);
         if($employee_id==$self_employee_id) {
            $self = TRUE;
         } else {
            $self = FALSE;
         }
      }
      
      $sql = "SELECT kpo,project_nm,project_id,start_dttm,due_dttm,priority_no,cost_estimate,report_status_cd"
           . " FROM ".XOCP_PREFIX."idp_project"
           . " WHERE request_id = '$request_id'"
           . " AND project_id = '$project_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($kpo,$project_nm,$project_id,$start_dttm,$due_dttm,$priority_no,$cost_estimate,$report_status_cd)=$db->fetchRow($result);
      }
      
      $project_nm_html = htmlentities($project_nm);
      
      $project_txt = "<div id='project_${request_id}_${project_id}'><span id='sp_project_nm_${request_id}_${project_id}'>".nl2br($project_nm)."</span>"
              ///. "<div style='font-size:0.9em;color:#888;'>IDR ".toMoneyShort($cost_estimate)."</div>"
              . ($report_status_cd=="completed"?"<div style='font-style:italic;color:green;'>Complete</div>":"")
              /// hack untuk data entry
              ///. (($req_status_cd=="start")?"<div>[<span onclick='edit_project_nm(\"$request_id\",\"$project_id\",this,event);' class='ylnk'>edit</span>]</div>":"")
              . (TRUE?"<div>[<span onclick='edit_project_nm(\"$request_id\",\"$project_id\",this,event);' class='ylnk'>edit</span>]</div>":"")
              . "</div>";
      
      $activity_txt = $this->renderProjectActivityList($request_id,$project_id);
      
      
      
      if($kpo=="") {
         $kpo = _EMPTY;
      } else {
      
      }
      $kpo_txt = "<div>".nl2br($kpo)
              . ($self==TRUE&&$req_status_cd=="employee"?" [<span onclick='edit_kpo(\"$request_id\",\"$project_id\",this,event);' class='ylnk'>edit</span>]":"")
              . "</div>";
      
      
      if($_SESSION["xocp_page_id"]=="idphrapprove") {
         $deleteprojbtn = " onmousemove='$(\"projbtndel_${request_id}_${project_id}\").style.visibility=\"visible\";' onmouseout='$(\"projbtndel_${request_id}_${project_id}\").style.visibility=\"hidden\";'";
      } else {
         $deleteprojbtn = ($req_status_cd=="start"?" onmousemove='$(\"projbtndel_${request_id}_${project_id}\").style.visibility=\"visible\";' onmouseout='$(\"projbtndel_${request_id}_${project_id}\").style.visibility=\"hidden\";'":"");
      }
      
      return "<table $deleteprojbtn ><tbody><tr><td>"
           . "<div style='border:2px solid #777;padding:0px;width:900px !important;-moz-border-radius:5px;".($report_status_cd=="completed"?"background-color:#dff;":"")."'"
           . ">"
           
           . "<table id='ptbl_${request_id}_${project_id}' style='border-spacing:2px;width:100%;background-color:#ffd;-moz-border-radius:3px 3px 0 0;'>"
           . "<colgroup><col/><col/><col width='30'/></colgroup>"
           . "<tbody><tr>"
              . "<td style='padding-left:3px;'>"
              . "</td>"
              . "<td style='text-align:right;'>&nbsp;</td>"
              . "<td style='text-align:center;'>&nbsp;"
              //. ($self==TRUE&&$req_status_cd=="employee"?"<img onclick='up_project_priority(\"$request_id\",\"$project_id\",\"$priority_no\");' src='".XOCP_SERVER_SUBDIR."/images/uplevel.gif' style='cursor:pointer;vertical-align:baseline;' title='Change Priority'/>&nbsp;":"")
              . "</td>"
           . "</tr></tbody>"
           . "</table>"
           
           . "<table class='tblidpcom'><colgroup>"
           . "<col width=''/>"
           . "<col width='200'/>"
           . "<col width='260'/>"
           . "<col width='143'/>"
           . "<col width='140'/>"
           . "</colgroup>"
           . "<thead>"
           . "<tr>"
           . "<td>Project Assignment</td>"
           . "<td>Activities</td>"
           . "<td>Time Frame</td>"
           . "<td title='Key Performance Driver'>KPD</td>"
           . "<td style='border-right:0px;' title='Key Performance Outcome'>KPO</td>"
           . "</tr>"
           . "</thead>"
           . "<tbody>"
           . "<tr>"
           . "<td rowspan='2' style='border-bottom:0px;-moz-border-radius:0 0 0 3px;' id='projectnm_${request_id}_${project_id}'>$project_txt</td>"
           . "<td id='activitylist_${request_id}_${project_id}' colspan='3' style='border-bottom:0px;padding:0px;'>$activity_txt</td>"
           . "<td rowspan='2' style='border-bottom:0px;border-right:0px;-moz-border-radius:0 0 3px 0;' id='kpo_${request_id}_${project_id}'>$kpo_txt</td>"
           . "</tr>"
           . "<tr>"
           . "<td colspan='3' style='border-bottom:0px;padding:0px;vertical-align:bottom;text-align:right;'>"
               . "<div style='padding:3px;border-top:1px solid #ddd;width:618px !important;'>"
               . ($self==TRUE&&$req_status_cd=="employee"||$_SESSION["xocp_page_id"]=="idphrapprove"?"<input class='sbtn' onclick='add_activity(\"$request_id\",\"$project_id\",this,event);' type='button' value='Add Activity'/>":"")
               . "</div>"
           . "</td>"
           . "</tr>"
           . "</tbody>"
           . "</table>"
           . "</div>"
           . "</td><td style='vertical-align:top;'>"
              . "<span id='projbtndel_${request_id}_${project_id}' style='visibility:hidden;'><input style='-moz-box-shadow:1px 1px 2px #333;' class='sbtn' type='button' value='"._DELETE."' onclick='delete_project(\"$request_id\",\"$project_id\",\"$project_nm_html\",this,event);'/></span>"
           . "</td></tr></tbody></table>";
      
      
      return "<div style='border:1px solid #bbb;padding:0px;width:900px !important;'>"
           
           . "<table id='ptbl_${request_id}_${project_id}' style='border-spacing:2px;width:100%;background-color:#ffd;'>"
           . "<colgroup><col/><col/><col width='30'/></colgroup>"
           . "<tbody><tr>"
              . "<td style='padding-left:3px;'><span class='xlnk' id='titleproject_${request_id}_${project_id}' onclick='edit_project(\"$request_id\",\"$project_id\",this,event);'>$project_nm</span></td>"
              . "<td style='text-align:right;'>Priority : $priority_no</td>"
              . "<td style='text-align:center;'><img onclick='up_project_priority(\"$request_id\",\"$project_id\",\"$priority_no\");' src='".XOCP_SERVER_SUBDIR."/images/uplevel.gif' style='cursor:pointer;vertical-align:baseline;' title='Change Priority'/>&nbsp;</td>"
           . "</tr></tbody>"
           . "</table>"
           
           . "</div>";
   }
   
   function app_deleteActionPlan($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $actionplan_id = $args[1];
      
      $sql = "SELECT competency_id FROM ".XOCP_PREFIX."idp_request_actionplan WHERE request_id = '$request_id' AND actionplan_id = '$actionplan_id'";
      $result = $db->query($sql);
      list($competency_id)=$db->fetchRow($result);
      
      $sql = "DELETE FROM ".XOCP_PREFIX."idp_request_actionplan WHERE request_id = '$request_id' AND actionplan_id = '$actionplan_id'";
      $db->query($sql);
      list($start_dttm,$stop_dttm)=$this->updateTimeFrame($request_id);
      
         $sql = "SELECT actionplan_id,method_t"
              . " FROM ".XOCP_PREFIX."idp_request_actionplan"
              . " WHERE request_id = '$request_id'"
              . " AND competency_id = '$competency_id'"
              . " ORDER BY actionplan_id";
         $result = $db->query($sql);
         $apcount = 0;
         $apstep = 0;
         $last_ap = 0;
         $arrap = array();
         if($db->getRowsNum($result)>0) {
            while(list($actionplan_idx,$method_t)=$db->fetchRow($result)) {
               if($method_t=="PROJECT") continue;
               if($actionplan_id==0) $actionplan_id = $actionplan_idx;
               $arrap[] = $actionplan_idx;
               $apcount++;
               $last_ap = $actionplan_idx;
            }
         }
      
      
      return array($request_id,$competency_id,$this->renderActionList($request_id,$competency_id),sql2ind($start_dttm,"date"),sql2ind($stop_dttm,"date"),toMoney(_idp_calc_cost_estimate($request_id)),$arrap);
   }
   
   function app_editIDPCompetency($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $competency_id = $args[1];
      $sql = "SELECT b.competency_nm,b.competency_abbr,a.ccl,a.rcl,a.itj,a.gap,a.priority_no,a.focus_dev"
           . " FROM ".XOCP_PREFIX."idp_request_competency a"
           . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
           . " WHERE a.request_id = '$request_id'"
           . " AND a.competency_id = '$competency_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($competency_nm,$competency_abbr,$ccl,$rcl,$itj,$gap,$priority_no,$focus_dev)=$db->fetchRow($result);
      }
      if($focus_dev=="") $focus_dev = "<span style='font-style:italic;'>"._EMPTY."</span>";
      
      $assessment_txt = "<table style='border-spacing:0px;'>"
                  . "<tbody>"
                     . "<tr><td>Required Competency Level</td><td>: $rcl</td></tr>"
                     . "<tr><td>Current Competency Level</td><td>: $ccl</td></tr>"
                     . "<tr><td>Importance to Job</td><td>: $itj</td></tr>"
                     . "<tr><td>Gap</td><td>: $gap</td></tr>"
                  . "</tbody></table>";
              
      $focus_txt = "<div id='focus_${request_id}_${competency_id}'>$focus_dev"
              . "&nbsp;[<span onclick='edit_focus_dev(\"$request_id\",\"$competency_id\",this,event);' class='ylnk'>edit</span>]"
              . "</div>";
              
      $actionplan_txt = $this->renderActionPlanUL($request_id,$competency_id);
      
      $ultab = ///// UL ////////////////////////////
             "<ul class='ultab'>"
           . "<li id='complitab_0' class='ultabsel_greyrev'><span onclick='comptab(\"0\",this,event);' class='xlnk'>Assessment Result</span></li>"
           . "<li id='complitab_1'><span onclick='comptab(\"1\",this,event);' class='xlnk'>Focus of Development</span></li>"
           . "<li id='complitab_2'><span onclick='comptab(\"2\",this,event);' class='xlnk'>Action Plan</span></li>"
           . "</ul>";
         
      $dvtab = "<div id='dv' style='min-height:100px;margin-bottom:1px;overflow:auto;border:0px solid #999999;clear:both;padding:0px;'>"
           . "<div style='border:1px solid #bbb;padding:10px;'>"
           . "<div id='compdvtab_0'>$assessment_txt</div>" 
           . "<div id='compdvtab_1' style='display:none;'>$focus_txt</div>"
           . "<div id='compdvtab_2' style='display:none;'>$actionplan_txt</div>"
           . "</div>"
           . "</div>";
      
      $ret = "<div id='compcontent_${request_id}_${competency_id}' style='padding:5px;padding-left:7px;border-top:1px solid #ccc;'>"
           . $ultab
           . $dvtab
              . "<div style='margin-top:5px;border-top:0px solid #bbb;padding:2px;text-align:right;'>"
                  . "<input type='button' onclick='delete_competency(\"$request_id\",\"$competency_id\");' value='Delete Competency'/>"
              . "</div>"
              
           . "</div>";
      return $ret;
   }
   
   function app_saveActionPlan($args) {
      $db=&Database::getInstance();
      $method_t = $args[0];
      $request_id = $args[1];
      $actionplan_id = $args[2];
      $ret = $args[3];
      
      if($method_t!="") {
         $editor_file = XOCP_DOC_ROOT."/modules/hris/include/idp/method_${method_t}.php";
         if(file_exists($editor_file)) {
            require_once($editor_file);
            $fsave = "${method_t}_idp_m_saveAction";
            $fsave($request_id,$actionplan_id,$ret);
            $this->updateTimeFrame($request_id);
            
            $sql = "SELECT competency_id FROM ".XOCP_PREFIX."idp_request_actionplan WHERE request_id = '$request_id' AND actionplan_id = '$actionplan_id'";
            $result = $db->query($sql);
            list($competency_id)=$db->fetchRow($result);
            list($startframe,$stopframe)=$this->updateTimeFrame($request_id);
            return array($request_id,$competency_id,$this->renderActionList($request_id,$competency_id),sql2ind($startframe,"date"),sql2ind($stopframe,"date"),toMoney(_idp_calc_cost_estimate($request_id)));
         }
      }
   }
   
   function app_editActionPlan($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $actionplan_id = $args[1];
      
      $ret = "";
      $sql = "SELECT a.method_t,b.method_type,a.method_id,a.competency_id,a.plan_start_dttm,a.plan_stop_dttm FROM ".XOCP_PREFIX."idp_request_actionplan a"
           . " LEFT JOIN ".XOCP_PREFIX."idp_development_method_type b USING(method_t)"
           . " WHERE a.request_id = '$request_id' AND a.actionplan_id = '$actionplan_id'";
      $result = $db->query($sql);
      $height = "260px";
      if($db->getRowsNum($result)==1) {
         list($method_t,$method_type,$method_id,$competency_id,$plan_start_dttm,$plan_stop_dttm)=$db->fetchRow($result);
         if($method_t!="") {
            $editor_file = XOCP_DOC_ROOT."/modules/hris/include/idp/method_${method_t}.php";
            if(file_exists($editor_file)) {
               require_once($editor_file);
               $fedit = "${method_t}_idp_m_editActionPlan";
               list($form,$height) = $fedit($request_id,$actionplan_id);
            }
         }
         
         $js_url = XOCP_SERVER_SUBDIR."/modules/hris/include/idp/method_${method_t}.php?js=1";
         
         $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>Edit Action Plan - $method_type</div>"
              . "<div id='apformeditor' style='padding:5px;'>"
                  . $form
              . "</div>"
              . "<div id='apformbtn' style='text-align:center;padding:10px;'>"
                  . "[<a href='".XOCP_SERVER_SUBDIR."/index.php?XP_idpeventm_menu=0'>Go to Event Management</a>]&nbsp;&nbsp;"
                  . "<input type='button' value='"._SAVE."' style='' onclick='_idpm_save(\"$request_id\",\"$actionplan_id\",this,event);'/>&nbsp;&nbsp;"
                  . "<input type='button' value='"._CANCEL."' style='' onclick='apbox.fade();'/>&nbsp;&nbsp;&nbsp;&nbsp;"
                  . ($method_t=="PROJECT"?"":"<input type='button' value='"._DELETE."' style='' onclick='delete_ap(\"$request_id\",\"$actionplan_id\",\"$method_type\",this,event);'/>")
              . "</div>";
         return array($js_url,$ret,$height);
      } else {
         return "FAIL";
      }
   }
   
   function renderActionPlan($request_id,$actionplan_id) {
      $db=&Database::getInstance();
      $sql = "SELECT a.method_t,b.method_type,a.method_id FROM ".XOCP_PREFIX."idp_request_actionplan a"
           . " LEFT JOIN ".XOCP_PREFIX."idp_development_method_type b USING(method_t)"
           . " WHERE a.request_id = '$request_id' AND a.actionplan_id = '$actionplan_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($method_t,$method_type)=$db->fetchRow($result);
         $ret = "$method_type : [<span class='ylnk' onclick='edit_ap(\"$request_id\",\"$actionplan_id\",this,event);'>edit</span>]";
         return array($method_t,$method_type,$ret);
      }
   }
   
   function app_addActionPlan($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $competency_id = $args[1];
      $method_t = $args[2];
      $actionplan_id = 0;
      $year_now = date('Y');
      $today = date("Y-m-d"); 
      $id_created = getUserID();
      $date_created = getSQLDate();
      $psid = 0;
      
      $sql = "SELECT MAX(actionplan_id) FROM ".XOCP_PREFIX."idp_request_actionplan WHERE request_id = '$request_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($actionplan_id)=$db->fetchRow($result);
      }
      $actionplan_id++;

      $sql = "INSERT INTO ".XOCP_PREFIX."idp_request_actionplan (request_id,actionplan_id,competency_id,method_t)"
           . " VALUES ('$request_id','$actionplan_id','$competency_id','$method_t')";
      $db->query($sql);
      $sql = "SELECT a.method_t FROM ".XOCP_PREFIX."idp_request_actionplan a"
           . " WHERE a.request_id = '$request_id' AND a.actionplan_id = '$actionplan_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($start_dttm,$stop_dttm)=$this->updateTimeFrame($request_id);
         
         $sql = "SELECT actionplan_id,method_t"
              . " FROM ".XOCP_PREFIX."idp_request_actionplan"
              . " WHERE request_id = '$request_id'"
              . " AND competency_id = '$competency_id'"
              . " ORDER BY actionplan_id";
         $result = $db->query($sql);
         $apcount = 0;
         $apstep = 0;
         $last_ap = 0;
         $arrap = array();
         if($db->getRowsNum($result)>0) {
            while(list($actionplan_idx,$method_t)=$db->fetchRow($result)) {
               if($method_t=="PROJECT") continue;
               if($actionplan_id==0) $actionplan_id = $actionplan_idx;
               $arrap[] = $actionplan_idx;
               if($actionplan_id==$actionplan_idx) {
                  $apstep = $apcount;
               }
               $apcount++;
               $last_ap = $actionplan_idx;
            }
         }
         return array($request_id,$competency_id,$this->renderActionList($request_id,$competency_id),sql2ind($start_dttm,"date"),sql2ind($stop_dttm,"date"),$arrap,$apstep,$apcount);
      } else {
         return "FAIL";
      }
   }
   
   function app_getMethodType($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $competency_id = $args[1];
      $sql = "SELECT method_t,method_type FROM ".XOCP_PREFIX."idp_development_method_type";
      $result = $db->query($sql);
      $ret = "";
      $cnt=0;
      if($db->getRowsNum($result)>0) {
         while(list($method_t,$method_type)=$db->fetchRow($result)) {
            if($method_t=="PROJECT") continue;
            switch($method_t) {
               case "TRN_EX":
               case "TRN_IN":
               case "SELF":
               case "COACH":
               case "COUNSL":
               case "COMPARE":
                  $disabled = "";
                  $style = "";
                  break;
               default:
                  $style = "style='color:#888;'";
                  $disabled="disabled='1'";
                  break;
            }
            $ret .= "<div style='text-align:left;padding:3px;border-bottom:1px solid #bbb;cursor:default;' class='cb'>"
                     . "<table style='width:100%;'><tbody><tr>"
                        . "<td $style>$method_type</td>"
                        . "<td style='text-align:right;'><input type='button' value='Add' style='width:80px;' onclick='add_ap(\"$request_id\",\"$competency_id\",\"$method_t\",this,event);' $disabled/></td>"
                     . "</tr></tbody></table>"
                  . "</div>";
            $cnt++;
         }
      }
      
      $xret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>Add Development Method</div>"
           . "<div style='padding:5px;background-color:#f2f2f2;'>"
              . "<div style='color:#000;border:1px solid #999;border-bottom:0px;background-color:#fff;'>$ret</div>"
           . "</div>"
           . "<div style='text-align:center;padding:10px;padding-bottom:30px;background-color:#f2f2f2;'>"
               . "<input type='button' value='Done' style='width:90px;' onclick='methodbox.fade();'/>"
           . "</div>";
      $h=132;
      $d=$cnt*33;
      return array($xret,$h+$d);
   }
   
   function app_deleteCompetency($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $competency_id = $args[1];
      
      $sql = "SELECT ccl,rcl,itj,gap,asid FROM ".XOCP_PREFIX."idp_request_competency WHERE request_id = '$request_id' AND competency_id = '$competency_id'";
      $result = $db->query($sql);
      list($ccl,$rcl,$itj,$gap,$asid)=$db->fetchRow($result);
      
      $sql = "DELETE FROM ".XOCP_PREFIX."idp_request_competency WHERE request_id = '$request_id' AND competency_id = '$competency_id'";
      $db->query($sql);
      $sql = "DELETE FROM ".XOCP_PREFIX."idp_request_actionplan WHERE request_id = '$request_id' AND competency_id = '$competency_id'";
      $db->query($sql);
      
      $this->sortPriority($request_id);
      
      return array($request_id,$competency_id,$ccl,$rcl,$itj,$gap,$asid,$this->renderIDPCompetency($request_id));
   }
   
   function app_saveFocusDevelopment($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $competency_id = $args[1];
      $focus_dev = addslashes(trim(urldecode($args[2])));
      $focus_dev_txt = trim(urldecode($args[2]));
      $sql = "UPDATE ".XOCP_PREFIX."idp_request_competency SET focus_dev = '$focus_dev' WHERE request_id = '$request_id' AND competency_id = '$competency_id'";
      $db->query($sql);
      
      if($focus_dev_txt=="") {
         $focus_dev_txt = "<span style='font-style:italic;'>"._EMPTY."</span>";
         $btn = "add";
      } else {
         $btn = "edit";
      }
      
      return array($request_id,$competency_id,$focus_dev_txt."&nbsp;[<span onclick='edit_focus_dev(\"$request_id\",\"$competency_id\",this,event);' class='ylnk'>$btn</span>]");
   }
   
   function app_editFocusDevelopment($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $competency_id = $args[1];
      $sql = "SELECT competency_abbr,competency_nm FROM ".XOCP_PREFIX."competency WHERE competency_id = '$competency_id'";
      $result = $db->query($sql);
      list($competency_abbr,$competency_nm)=$db->fetchRow($result);
      $sql = "SELECT focus_dev,priority_no FROM ".XOCP_PREFIX."idp_request_competency WHERE request_id = '$request_id' AND competency_id = '$competency_id'";
      $result = $db->query($sql);
      list($focus_dev,$priority_no)=$db->fetchRow($result);
      
      $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>Edit Focus of Development</div>"
           . "<div style='padding:5px;'>"
               . "<table style='width:100%;'>"
               . "<colgroup>"
                  . "<col width='200'/>"
                  . "<col/>"
               . "</colgroup>"
               . "<tbody>"
               . "<tr><td style='text-align:right;vertical-align:top;'>Competency to be Developed : </td><td style='font-weight:bold;'>$competency_abbr - $competency_nm</td></tr>"
               . "<tr><td style='text-align:right;vertical-align:top;'>Focus of Development : </td><td><textarea style='width:95%;height:70px;' id='editfocusdev_${request_id}_${competency_id}'>$focus_dev</textarea></td></tr>"
               //. "<tr><td style='text-align:right;vertical-align:top;'>Priority No : </td><td>$priority_no</td></tr>"
               . "</tbody></table>"
           . "</div>"
           . "<div style='text-align:center;padding:10px;'>"
               . "<input type='button' value='"._SAVE."' style='width:90px;' onclick='save_focus_dev(\"$request_id\",\"$competency_id\",this,event);'/>&nbsp;&nbsp;"
               . "<input type='button' value='"._CANCEL."' style='width:90px;' onclick='fdbox.fade();'/>"
           . "</div>";
      
      return array($request_id,$competency_id,$ret);
   }
   
   function app_setPriorityUp($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $competency_id = $args[1];
      $priority_no = (5*$args[2])-6;
      $sql = "UPDATE ".XOCP_PREFIX."idp_request_competency SET priority_no = (priority_no*5) WHERE request_id = '$request_id'";
      $db->query($sql);
      $sql = "UPDATE ".XOCP_PREFIX."idp_request_competency SET priority_no = '$priority_no' WHERE request_id = '$request_id' AND competency_id = '$competency_id'";
      $db->query($sql);
      
      $this->sortPriority($request_id);
      return array($request_id,$competency_id,$this->renderIDPCompetency($request_id));
   }
   
   function app_deleteRequest($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $user_id = getUserID();
      $sql = "UPDATE ".XOCP_PREFIX."idp_request SET status_cd = 'nullified', nullified_user_id = '$user_id', nullified_dttm = now()"
           . " WHERE request_id = '$request_id'";
      $db->query($sql);
      
      $sql = "SELECT notification_id"
           . " FROM ".XOCP_PREFIX."idp_notifications"
           . " WHERE request_id = '$request_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($notification_id)=$db->fetchRow($result)) {
            _idp_notification_followed($notification_id);
         }
      }
      
      return $request_id;
   }
   
   function sortPriority($request_id) {
      $db=&Database::getInstance();
      
      $arrproj = array();
      
      //// also reset project priority
      $sql = "SELECT project_id FROM ".XOCP_PREFIX."idp_project WHERE request_id = '$request_id' ORDER BY priority_no";
      $result = $db->query($sql);
      $pp = 200;
      if($db->getRowsNum($result)>0) {
         while(list($project_id)=$db->fetchRow($result)) {
            $sql = "UPDATE ".XOCP_PREFIX."idp_project SET priority_no = '$pp' WHERE request_id = '$request_id' AND project_id = '$project_id'";
            $db->query($sql);
            _debuglog($sql);
            $pp++;
         }
      }
      
      $sql = "SELECT a.competency_id"
           . " FROM ".XOCP_PREFIX."idp_request_competency a"
           . " WHERE a.request_id = '$request_id'"
           . " ORDER BY a.priority_no";
      $result = $db->query($sql);
      $priority_no = 1;
      if($db->getRowsNum($result)>0) {
         while(list($competency_id)=$db->fetchRow($result)) {
            $sql = "UPDATE ".XOCP_PREFIX."idp_request_competency SET priority_no = '$priority_no' WHERE request_id = '$request_id' AND competency_id = '$competency_id'";
            $db->query($sql);
            
            $sql = "SELECT project_id FROM ".XOCP_PREFIX."idp_request_actionplan WHERE request_id = '$request_id' AND method_t = 'PROJECT' AND competency_id = '$competency_id'";
            $rselcomp = $db->query($sql);
            if($db->getRowsNum($rselcomp)>0) {
               list($project_idx)=$db->fetchRow($rselcomp);
               $sql = "UPDATE ".XOCP_PREFIX."idp_project SET priority_no = '$priority_no' WHERE request_id = '$request_id' AND project_id = '$project_idx'";
               $db->query($sql);
               _debuglog($sql);
            }
            $priority_no++;
         }
      }
      
      $this->sortProjectPriority($request_id);
   }
   
   function sortProjectPriority($request_id) {
      $db=&Database::getInstance();
      $sql = "SELECT a.project_id"
           . " FROM ".XOCP_PREFIX."idp_project a"
           . " WHERE a.request_id = '$request_id'"
           . " ORDER BY a.priority_no";
      $result = $db->query($sql);
      $priority_no = 1;
      if($db->getRowsNum($result)>0) {
         while(list($project_id)=$db->fetchRow($result)) {
            $sql = "UPDATE ".XOCP_PREFIX."idp_project SET priority_no = '$priority_no' WHERE request_id = '$request_id' AND project_id = '$project_id'";
            $db->query($sql);
            $priority_no++;
         }
      }
   }
   
   function app_addCompetency($args) {
      $db=&Database::getInstance();
      $employee_id = $args[0];
      $request_id = $args[1];
      $competency_id = $args[2];
      $ccl = $args[3];
      $rcl = $args[4];
      $itj = $args[5];
      $gap = $args[6];
      $asid = $args[7];
      
      $sql = "SELECT competency_id FROM ".XOCP_PREFIX."idp_request_competency WHERE request_id = '$request_id' AND competency_id = '$competency_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         return "DUPLICATE";
      }
      
      $sql = "INSERT INTO ".XOCP_PREFIX."idp_request_competency (request_id,competency_id,ccl,rcl,itj,gap,asid,priority_no)"
           . " VALUES ('$request_id','$competency_id','$ccl','$rcl','$itj','$gap','$asid','200')";
      $db->query($sql);
      
      $this->sortPriority($request_id);
      
      $this->app_addActionPlan(array($request_id,$competency_id,"PROJECT"));
      
      $sql = "SELECT a.competency_id"
           . " FROM ".XOCP_PREFIX."idp_request_competency a"
           . " WHERE a.request_id = '$request_id'"
           . " AND a.competency_id = '$competency_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($competency_idx)=$db->fetchRow($result);
         return array($request_id,$competency_id,$this->renderCompetency($request_id,$competency_id));
      } else {
         return "FAIL";
      }
      
   }
   
   function app_getCompetencyGap($args) {
      $db=&Database::getInstance();
      $request_id = $args[0];
      $employee_id = $args[1];
      $job_id = $args[2];
      
      $wizard_mode = $_COOKIE["wizard_mode"];
      
      $sql = "SELECT MAX(asid) FROM ".XOCP_PREFIX."employee_competency_final WHERE employee_id = '$employee_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($asid)=$db->fetchRow($result);
      } else {
         $asid = 0;
      }
      
      $requested_arr = array();
      $sql = "SELECT competency_id FROM ".XOCP_PREFIX."idp_request_competency WHERE request_id = '$request_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($competency_id)=$db->fetchRow($result)) {
            $requested_arr[$competency_id] = 1;
         }
      }
      
      $ret = "";
      
      
      $retcg["General"] .= "<div style='background-color:#eee;padding:0px;border:1px solid #bbb;cursor:default;'>"
                         . "<table style='width:100%;border-spacing:0px;'>"
                         . "<colgroup>"
                         . "<col width=''/>"
                         . "<col width='100'/>"
                         . "<col width='100'/>"
                         . "</colgroup>"
                         . "<tbody><tr>"
                         . "<td style='padding:5px;text-align:left;padding-left:5px;'>Competency $asid $employee_id $job_id</td>"
                         . "<td style='padding:5px;text-align:center;padding-left:5px;'>Gap</td>"
                         . "<td style='padding:5px;text-align:center;color:black;font-weight:normal;' id='tdc__'>Action</td>"
                         . "</tr></tbody></table></div>";
      
      $retcg["Managerial"] .= "<div style='background-color:#eee;padding:0px;border:1px solid #bbb;cursor:default;'>"
                         . "<table style='width:100%;border-spacing:0px;'>"
                         . "<colgroup>"
                         . "<col width=''/>"
                         . "<col width='100'/>"
                         . "<col width='100'/>"
                         . "</colgroup>"
                         . "<tbody><tr>"
                         . "<td style='padding:5px;text-align:left;padding-left:5px;'>Competency</td>"
                         . "<td style='padding:5px;text-align:center;padding-left:5px;'>Gap</td>"
                         . "<td style='padding:5px;text-align:center;color:black;font-weight:normal;' id='tdc__'>Action</td>"
                         . "</tr></tbody></table></div>";
      
      $retcg["Specific"] .= "<div style='background-color:#eee;padding:0px;border:1px solid #bbb;cursor:default;'>"
                         . "<table style='width:100%;border-spacing:0px;'>"
                         . "<colgroup>"
                         . "<col width=''/>"
                         . "<col width='100'/>"
                         . "<col width='100'/>"
                         . "</colgroup>"
                         . "<tbody><tr>"
                         . "<td style='padding:5px;text-align:left;padding-left:5px;'>Competency</td>"
                         . "<td style='padding:5px;text-align:center;padding-left:5px;'>Gap</td>"
                         . "<td style='padding:5px;text-align:center;color:black;font-weight:normal;' id='tdc__'>Action</td>"
                         . "</tr></tbody></table></div>";
      
      
      
      $sql = "SELECT a.competency_id,a.rcl,a.itj,b.competency_nm,c.compgroup_nm,b.competency_class,d.ccl,(b.competency_class+0) as urcl,"
           . "b.desc_en,b.desc_id,b.compgroup_id,b.competency_abbr"
           . " FROM ".XOCP_PREFIX."job_competency a"
           . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
           . " LEFT JOIN ".XOCP_PREFIX."compgroup  c USING(compgroup_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee_competency_final d ON d.asid = '$asid' AND d.employee_id = '$employee_id' AND d.job_id = a.job_id AND d.competency_id = b.competency_id"
           . " WHERE a.job_id = '$job_id'"
           . " ORDER BY b.compgroup_id,urcl";
      $result = $db->query($sql);
      $oldcompgroup = "";
      $oldcompgroup_id = "";
      if($db->getRowsNum($result)>0) {
         while(list($competency_id,$rcl,$itj,$competency_nm,$compgroup_nm,$cc,$ccl,$urcl,$desc_en,$desc_id,$compgroup_id,$competency_abbr)=$db->fetchRow($result)) {
            
            ////
            
            if($oldcc!=$cc) {
               $cctxt = $cc;
               $oldcc = $cc;
               $style = "style='border-bottom:0px;'";
            } else {
               $cctxt = "";
               $style = "style='border-top:0px;border-bottom:0px;'";
            }
            $gapx = $ccl*$itj-$rcl*$itj;
            if($gapx<0) {
               $gap_color = "color:red;font-weight:bold;";
               $competency_color = "color:red;";
            } else if($gapx>0) {
               $gap_color = "color:blue;font-weight:bold;";
               $competency_color = "color:red;";
            } else {
               $gap_color = "";
               $competency_color = "";
            }
            
            $retcg[$compgroup_nm] .= "<div id='dvc_${competency_id}' style='padding:0px;border-bottom:1px solid #bbb;cursor:default;".($requested_arr[$competency_id]==1?"background-color:#ccddff;color:blue;":"")."' class='cb'>"
                  . "<table style='width:100%;border-spacing:0px;'>"
                  . "<colgroup>"
                  . "<col width=''/>"
                  . "<col width='100'/>"
                  . "<col width='100'/>"
                  . "</colgroup>"
                  . "<tbody><tr>"
                  . "<td style='text-align:left;'><div style='padding:3px;min-height:18px;'>$competency_abbr - $competency_nm</div></td>"
                  . "<td style='padding:3px;text-align:center;border-right:1px solid #ddd;border-left:1px solid #ddd;${gap_color}'>$gapx</td>"
                  . "<td style='padding:3px;text-align:center;color:black;font-weight:normal;' id='tdc_${competency_id}'>"
                     . ($requested_arr[$competency_id]==1?"<input style='width:70px;' type='button' value='Remove' onclick='remove_competency(\"$employee_id\",\"$request_id\",\"$competency_id\",this,event);'/>":"<input style='width:70px;' type='button' value='Add' onclick='add_competency(\"$employee_id\",\"$request_id\",\"$competency_id\",\"$ccl\",\"$rcl\",\"$itj\",\"$gapx\",\"$asid\",this,event);'/>")
                  . "</td>"
                  . "</tr></tbody></table></div>";
            
         }
      }
      
      
      $ultab = ///// UL ////////////////////////////
             "<ul class='ultab' style=''>"
           . "<li id='litab_0' style='background-color:#ffffff;' class='ultabsel_greyrev' onclick='pnltab(\"0\",this,event);'><span class='xlnk'>General</span></li>"
           . "<li id='litab_1' style='background-color:#ffffff;' onclick='pnltab(\"1\",this,event);'><span class='xlnk'>Managerial</span></li>"
           . "<li id='litab_2' style='background-color:#ffffff;' onclick='pnltab(\"2\",this,event);'><span class='xlnk'>Specific</span></li>"
           . "</ul>";
         
           ///////// DIV ////////////////
      $dvtab = "<div id='dv' style='min-height:100px;margin-bottom:0px;overflow:auto;border:0px solid #999999;border-left:0px;clear:both;padding:4px;'>"
           . "<div id='dvtab_0'>$retcg[General]</div>" 
           . "<div id='dvtab_1' style='display:none;'>$retcg[Managerial]</div>"
           . "<div id='dvtab_2' style='display:none;'>$retcg[Specific]</div>"
           . "</div>";
      
      if($wizard_mode==1) {
         $btn = "<table style='width:100%;border-spacing:0px;'>"
              . "<colgroup><col/><col/></colgroup>"
              . "<tbody><tr>"
              . "<td style='text-align:left;'>"
                  . "&nbsp;"
              . "</td>"
              . "<td style='text-align:right;'>"
                  . "<input type='button' class='xaction' value='&lt; Previous' style='width:90px;' onclick='wizard_run_prev();' disabled='1'/>&nbsp;"
                  . "<input type='button' class='xaction' value='Next &gt;' style='width:90px;' onclick='wizard_run_next();'/>"
              . "</td>"
              . "</tr></tbody></table>";
      } else {
         $btn = "<input type='button' value='Done' style='width:90px;' onclick='cb_fade();'/>";
      }
      
      return "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>"
           . ($wizard_mode==1?"IDP Request Wizard - Step 1 of 4 : Add Competency":"Add Competency")
           . "</div>"
           . "<div style='padding:5px;background-color:#f2f2f2;'>"
              . "<div style='padding:10px;text-align:center;'>Click 'Add' button to add competencies:</div>"
              . "<div style='padding-left:10px;'>$ultab</div>"
              . "<div style='color:#000;max-height:332px;height:332px;overflow:auto;border:1px solid #999;background-color:#fff;'>$dvtab</div>"
           . "</div>"
           . "<div style='text-align:center;padding:10px;background-color:#f2f2f2;height:100px;'>"
               . $btn
           . "</div>";
   }
   
   function app_newRequest($args) {
      $db=&Database::getInstance();
      $employee_id = $args[0];
      $job_id = $args[1];
      $user_id = getUserID();
      
      $sql = "SELECT b.employee_id FROM ".XOCP_PREFIX."users a LEFT JOIN ".XOCP_PREFIX."employee b USING(person_id) WHERE a.user_id = '$user_id'";
      $result = $db->query($sql);
      list($requester_id)=$db->fetchRow($result);
      
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
      
      
      $asid = $_SESSION["asid"];
      
      $sql = "INSERT INTO ".XOCP_PREFIX."idp_request (employee_id,requester_id,created_user_id,requested_dttm,timeframe_start,current_job_id,asid)"
           . " VALUES ('$employee_id','$requester_id','$user_id',now(),now(),'$job_id','$asid')";
      $db->query($sql);
      $request_id = $db->getInsertId();
      return $this->renderRequest($request_id);
   }
   
   function renderRequest($request_id) {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/hris/include/idp/idp.php");
      $user_id = getUserID();
      
      list($self_job_id,
           $self_employee_id,
           $self_job_nm,
           $self_nm,
           $self_nip,
           $self_gender,
           $self_jobstart,
           $self_entrance_dttm,
           $self_jobage,
           $self_job_summary,
           $self_person_id,
           $self_user_id,
           $self_first_assessor_job_id,
           $self_next_assessor_job_id)=_hris_getinfobyuserid($user_id);
      
      $sql = "SELECT a.requested_dttm,a.timeframe_start,a.timeframe_stop,a.requester_id,c.person_nm,a.employee_id,a.status_cd,d.job_nm,d.job_abbr"
           . " FROM ".XOCP_PREFIX."idp_request a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b ON b.employee_id = a.requester_id"
           . " LEFT JOIN ".XOCP_PREFIX."persons c ON c.person_id = b.person_id"
           . " LEFT JOIN ".XOCP_PREFIX."jobs d ON d.job_id = a.current_job_id"
           . " WHERE a.request_id = '$request_id'";
      $result = $db->query($sql);
      $btn_delete = "";
      if($db->getRowsNum($result)==1) {
         list($requested_dttm,$timeframe_start,$timeframe_stop,$requester_id,$requester_nm,$employee_id,$status_cd,$job_nm,$job_abbr)=$db->fetchRow($result);
         
         
            list($job_idx,
                 $employee_idx,
                 $job_nmx,
                 $nmx,
                 $nipx,
                 $genderx,
                 $jobstartx,
                 $entrance_dttmx,
                 $jobagex,
                 $job_summaryx,
                 $person_idx,
                 $user_idx,
                 $first_assessor_job_id,
                 $next_assessor_job_id)=_hris_getinfobyemployeeid($employee_id);
         
         if($employee_id==$self_employee_id) {
            $self = TRUE;
         } else {
            $self = FALSE;
         }
         
         switch($status_cd) {
            case "employee":
               $status_txt = "Request Completion by Employee";
               if($self) {
                  $btn_add_competency = "";
                  $btn_wizemp_devmethod = "&nbsp;<input type='button' class='xaction' value='Start Wizard' onclick='start_wizemp(\"$request_id\");'/>";
                  $btn_action = "&nbsp;<input type='button' value='Submit Request' class='xaction' onclick='employee_submit_request(\"$request_id\");'/>";
                  $btn_action .= "&nbsp;<input type='button' value='Return to Superior' onclick='employee_return_request(\"$request_id\");'/>";
                  $btn_delete = ""; //"&nbsp;&nbsp;<input type='button' value='Delete' onclick='delete_request(\"$request_id\",this,event);'/>";
               } else {
                  $btn_add_project = "";
                  $btn_add_competency = "";
                  $btn_action = "";
                  $btn_delete = "";
               }
               if($_SESSION["xocp_page_id"]=="idphrapprove") {
                  $btn_add_competency = "&nbsp;<input type='button' class='xaction' value='Start Wizard Superior' onclick='start_wizard(\"$request_id\");'/>"
                                      . "&nbsp;<input type='button' class='xaction' value='Start Wizard Employee' onclick='start_wizemp(\"$request_id\");'/>";
                  $btn_delete = "&nbsp;&nbsp;<input type='button' value='Delete' onclick='delete_request(\"$request_id\",this,event);'/>";
               } else {
                  $btn_delete = "";
               }
               break;
            case "approval1":
               $status_txt = "Waiting Superior Approval";
               if($self) {
                  $btn_add_project = "";
                  $btn_add_competency = "";
                  $btn_action = "";
                  $btn_delete = "";
               } else {
                  $btn_add_project = "";
                  $btn_add_competency = "";
                  if($_SESSION["xocp_page_id"]=="idpsuperiorapprove") {
                     $btn_action = "&nbsp;<input type='button' value='Approve' class='xaction' onclick='superior_approve_request(\"$request_id\");'/>";
                     $btn_action .= "&nbsp;&nbsp;<input type='button' value='Not Approve' onclick='superior_return_request(\"$request_id\");'/>";
                  }
                  $btn_delete = "";
               }
               if($_SESSION["xocp_page_id"]=="idphrapprove") {
                  $btn_add_competency = "&nbsp;<input type='button' class='xaction' value='Start Wizard Superior' onclick='start_wizard(\"$request_id\");'/>"
                                      . "&nbsp;<input type='button' class='xaction' value='Start Wizard Employee' onclick='start_wizemp(\"$request_id\");'/>";
                  $btn_delete = "&nbsp;&nbsp;<input type='button' value='Delete' onclick='delete_request(\"$request_id\",this,event);'/>";
               } else {
                  $btn_delete = "";
               }
               break;
            case "approval2":
               $status_txt = "Waiting Next Superior Approval";
               if($self_job_id==$next_assessor_job_id) {
                  $btn_action = "&nbsp;<input type='button' value='Approve' class='xaction' onclick='next_superior_approve_request(\"$request_id\");'/>";
                  $btn_action .= "&nbsp;&nbsp;<input type='button' value='Not Approve' onclick='next_superior_return_request(\"$request_id\");'/>";
               } else {
                  $btn_action = "";
               }
               if($_SESSION["xocp_page_id"]=="idphrapprove") {
                  $btn_delete = "&nbsp;&nbsp;<input type='button' value='Delete' onclick='delete_request(\"$request_id\",this,event);'/>";
                  $btn_add_competency = "&nbsp;<input type='button' class='xaction' value='Start Wizard Superior' onclick='start_wizard(\"$request_id\");'/>"
                                     . "&nbsp;<input type='button' class='xaction' value='Start Wizard Employee' onclick='start_wizemp(\"$request_id\");'/>";
                  $btn_add_competency = "&nbsp;<input type='button' class='xaction' value='Start Wizard' onclick='start_wizard(\"$request_id\");'/>";
               } else {
                  $btn_delete = "";
               }
               break;
            case "approval3":
               $status_txt = "Waiting HR Confirmation";
               if($_SESSION["xocp_page_id"]=="idphrapprove") {
                  $btn_action = "&nbsp;<input type='button' value='Confirm IDP Request' class='xaction' onclick='hr_approve_request(\"$request_id\");'/>";
                  $btn_delete = "&nbsp;&nbsp;<input type='button' value='Delete' onclick='delete_request(\"$request_id\",this,event);'/>";
                  $btn_add_competency = "&nbsp;<input type='button' class='xaction' value='Start Wizard Superior' onclick='start_wizard(\"$request_id\");'/>"
                                      . "&nbsp;<input type='button' class='xaction' value='Start Wizard Employee' onclick='start_wizemp(\"$request_id\");'/>";
               } else {
                  $btn_action = "";
                  $btn_delete = "";
               }
               break;
            case "implementation":
               $status_txt = "Implementation";
               if($_SESSION["xocp_page_id"]=="idphrapprove") {
                  $btn_delete = "&nbsp;&nbsp;<input type='button' value='Delete' onclick='delete_request(\"$request_id\",this,event);'/>";
                  $btn_add_competency = "&nbsp;<input type='button' class='xaction' value='Start Wizard Superior' onclick='start_wizard(\"$request_id\");'/>"
                                      . "&nbsp;<input type='button' class='xaction' value='Start Wizard Employee' onclick='start_wizemp(\"$request_id\");'/>";
               } else {
                  $btn_delete = "";
                  if($self) {
                     $btn_action = "&nbsp;<input type='button' value='Revise' onclick='revise_request(\"$request_id\");'/>";
                  }
               }
               break;
            case "completed":
               $status_txt = "Completed";
               $btn_wizard = "";
               $btn_add_competency = "";
               break;
            case "start":
            default:
               $status_txt = "New Request";
               $btn_wizard = ""; //"<input type='button' value='Start Wizard' onclick='start_wizard(\"$request_id\");'/>";
               $btn_add_competency = "&nbsp;<input type='button' class='xaction' value='Start Wizard' onclick='start_wizard(\"$request_id\");'/>";
               //$btn_add_competency = "&nbsp;<input type='button' value='Add Competency' onclick='stage_add_browse_competency(\"$request_id\",\"$employee_id\",this,event);'/>";
               $btn_action = "&nbsp;<input type='button' value='Start Request' class='xaction' onclick='superior_start_request(\"$request_id\");'/>";
               $btn_delete = "&nbsp;&nbsp;<input type='button' value='Delete' onclick='delete_request(\"$request_id\",this,event);'/>";
               $btn_add_project = "&nbsp;<input type='button' value='Add' onclick='add_project(\"$request_id\",\"$employee_id\",this,event);'/>";
               break;
         }
         
         list($timeframe_start,$timeframe_stop)=$this->updateTimeFrame($request_id);
         
         $sql = "SELECT return_note FROM ".XOCP_PREFIX."idp_request_return_note WHERE request_id = '$request_id'";
         $result = $db->query($sql);
         if($db->getRowsNum($result)>0) {
            list($return_note)=$db->fetchRow($result);
            if(($status_cd=="employee")||($status_cd=="approval1")) {
               $return_div = "<div style='color:blue;font-weight:bold;margin-top:10px;'>"
                           . "Request returned with notes:"
                          . "</div>"
                          . "<div id='xreturn_note' style='color:blue;padding-left:20px;'>"
                              . $return_note
                          . "</div>";
            }
         }
         
         $ttl_cost = _idp_calc_cost_estimate($request_id);
         $cost_txt = toMoney($ttl_cost);
         
         $ret = "<div>"
              
              . $return_div
              
              . "<div style='font-weight:bold;margin-top:10px;'>"
                  . "ID: $request_id"
              . "</div>"
              . "<div style='font-weight:bold;margin-top:10px;'>"
                  . "General Information:"
              . "</div>"
              . "<div id='geninfo_${request_id}' style='padding:3px;padding-left:20px;'>"
                 . "<div style='border:2px solid #777;padding:0px;width:900px !important;-moz-border-radius:5px;'>"
                    . "<table style='border-spacing:0px;width:100%;background-color:#fff;-moz-border-radius:3px 3px 3px 3px;' cellspacing='0'>"
                       . "<tbody>"
                          . "<tr style='font-weight:bold;text-align:center;'>"
                             . "<td style='border-right:2px solid #777;padding:2px;border-bottom:1px solid #bbb;'>Status</td>"
                             . "<td style='border-right:2px solid #777;padding:2px;border-bottom:1px solid #bbb;'>Start Date</td>"
                             . "<td style='border-right:2px solid #777;padding:2px;border-bottom:1px solid #bbb;'>Stop Date</td>"
                             . "<td style='border-bottom:1px solid #bbb;'>Cost Estimate IDR</td>"
                          . "</tr>"
                          . "<tr style='text-align:center;'>"
                             . "<td style='border-right:2px solid #777;padding:2px;' id='idpstatus_${request_id}'>$status_txt</td>"
                             . "<td style='border-right:2px solid #777;padding:2px;' id='idptimeframe_start_${request_id}'>".sql2ind($timeframe_start,"date")."</td>"
                             . "<td style='border-right:2px solid #777;padding:2px;' id='idptimeframe_stop_${request_id}'>".sql2ind($timeframe_stop,"date")."</td>"
                             . "<td id='idpcost_${request_id}'>$cost_txt</td>"
                          . "</tr>"
                       . "</tbody>"
                    . "</table>"
                 . "</div>"
              . "</div>";
          /*if($self&&$status_cd=="start") {
           $ret .= "<div style='padding-top:10px;color:blue;'>... Waiting for superior to start the request.</div>"
                  . "</div>";
            return array($request_id,$ret);
         }*/
         $ret .= "<div style='font-weight:bold;margin-top:10px;'>"
                  . "Competency to be developed:".$btn_add_competency.$btn_wizemp_devmethod
              . "</div>"
              . "<div id='comp_${request_id}' style='padding-left:20px;'>"
                  . $this->renderIDPCompetency($request_id) 
              . "</div>"
              
              . "<div style='font-weight:bold;margin-top:10px;'>"
                  . "Assignment Action Plan:".$btn_add_project
              . "</div>"
              . "<div id='proj_${request_id}' style='padding-left:20px;'>"
                  . $this->renderIDPProject($request_id) 
              . "</div>"
              
              
              . "<div style='margin-top:10px;border:1px solid #888;background-color:#eee;padding:5px;' id='btn_${request_id}'>"
                  . "<table style='width:100%;'><tbody><tr>"
                  . "<td style='text-align:left;'>"
                  . $btn_wizard
                  . "</td>"
                  . "<td style='text-align:right;'>"
                  . "<input type='button' value='Print' onclick='print_idp(\"$request_id\");'/>"
                  . $btn_action
                  . $btn_delete
                  . "</td>"
                  . "</tr></tbody></table>"
              . "</div>"
              . "</div>";
         return array($request_id,$ret);
      } else {
         return "FAIL";
      }
   }
   
   function renderIDPCompetency($request_id) {
      $db=&Database::getInstance();
      
      $sql = "SELECT a.competency_id"
           . " FROM ".XOCP_PREFIX."idp_request_competency a"
           . " WHERE a.request_id = '$request_id'"
           . " ORDER BY a.priority_no";
      $result = $db->query($sql);
      $ret = "";
      $display = "";
      if($db->getRowsNum($result)>0) {
         while(list($competency_id)=$db->fetchRow($result)) {
            $ret .= "<div id='xcomp_${request_id}_${competency_id}' style='padding:3px;padding-left:0px;'>".$this->renderCompetency($request_id,$competency_id)."</div>";
         }
         $display = "display:none;";
      }
      $ret .= "<div style='${display}font-style:italic;color:#bbb;text-align:left;' id='empty_competency_${request_id}'>"._EMPTY."</div>";
      return $ret;
   }
   
   
   function renderActionList($request_id,$competency_id) {
      $db=&Database::getInstance();
      
      $user_id = getUserID();
      $sql = "SELECT c.job_id,b.employee_id"
           . " FROM ".XOCP_PREFIX."users a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(person_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job c USING(employee_id)"
           . " WHERE a.user_id = '$user_id'";
      $result = $db->query($sql);
      list($self_job_id,$self_employee_id)=$db->fetchRow($result);
      
      $sql = "SELECT a.employee_id,a.status_cd"
           . " FROM ".XOCP_PREFIX."idp_request a"
           . " WHERE a.request_id = '$request_id'";
      $result = $db->query($sql);
      $self = TRUE;
      if($db->getRowsNum($result)==1) {
         list($employee_id,$req_status_cd)=$db->fetchRow($result);
         if($employee_id==$self_employee_id) {
            $self = TRUE;
         } else {
            $self = FALSE;
         }
      }
      
      $sql = "SELECT a.actionplan_id,a.event_id FROM ".XOCP_PREFIX."idp_request_actionplan a"
           . " WHERE a.request_id = '$request_id' AND competency_id = '$competency_id'"
           . " ORDER BY a.actionplan_id";
      $result = $db->query($sql);
      $action_txt = "";
      if($db->getRowsNum($result)>0) {
         $no = 0;
         while(list($actionplan_id,$event_id)=$db->fetchRow($result)) {
            $sql = "SELECT a.status_cd,a.method_t,b.method_type,a.method_id,c.method_nm,a.plan_start_dttm,a.plan_stop_dttm FROM ".XOCP_PREFIX."idp_request_actionplan a"
                 . " LEFT JOIN ".XOCP_PREFIX."idp_development_method_type b USING(method_t)"
                 . " LEFT JOIN ".XOCP_PREFIX."idp_development_method c ON c.method_id = a.method_id"
                 . " WHERE a.request_id = '$request_id' AND a.actionplan_id = '$actionplan_id'";
            $rap = $db->query($sql);
            if($db->getRowsNum($rap)==1) {
               list($aap_status_cd,$method_t,$method_type,$method_id,$method_nm,$plan_start_dttm,$plan_stop_dttm)=$db->fetchRow($rap);
               
               $actionplan_remark = "";
               $actionplan_start = "0000-00-00 00:00:00";
               $actionplan_stop = "0000-00-00 00:00:00";
               $report_status = "";
               $cost_estimate = 0;
               if($method_t!="") {
                  $editor_file = XOCP_DOC_ROOT."/modules/hris/include/idp/method_${method_t}.php";
                  if(file_exists($editor_file)) {
                     require_once($editor_file);
                     $fremark = "${method_t}_idp_m_getRemark";
                     list($actionplan_remark,$actionplan_start,$actionplan_stop,$report_status,$cost_estimate) = $fremark($request_id,$actionplan_id);
                  }
               }
               
               if($event_id>0) {
                  $sql = "SELECT status_cd,last_email_dttm FROM ".XOCP_PREFIX."idp_event_registration WHERE event_id = '$event_id' AND employee_id = '$employee_id'";
                  $re = $db->query($sql);
                  if($db->getRowsNum($re)>0) {
                     list($event_status_cd,$event_last_email)=$db->fetchRow($re);
                     switch($event_status_cd) {
                        case "invited":
                           $event_status_txt = "<div style='font-style:italic;color:blue;'>Status: Invited<br/>Wait for confirmation's e-mail</div>";
                           break;
                        case "self_registered":
                           if($event_last_email=="0000-00-00 00:00:00") {
                              $event_status_txt = "<div style='font-style:italic;color:blue;'>Status: Registered<br/>Wait for confirmation's e-mail</div>";
                           } else {
                              $event_status_txt = "<div style='font-style:italic;color:blue;'>Status: Confirmation E-mailed.";
                              $event_status_txt .= ($self==TRUE?"<br/><a href='".XOCP_SERVER_SUBDIR."/index.php?XP_idpeventconfirmation_menu=0&goto=${event_id}&employee_id=${employee_id}'>Please confirm</a></div>":"");
                           }
                           break;
                        case "in":
                           $event_status_txt = "<div style='font-style:italic;color:blue;'>Status: Join</div>";
                           break;
                        case "out":
                           $event_status_txt = "<div style='font-style:italic;color:red;'>Status: Leave</div>";
                           break;
                        default:
                           break;
                     }
                  } else {
                     $event_status_txt = "";
                  }
               } else {
                  $event_status_txt = "";
               }
               
               if($req_status_cd!="implementation") $event_status_txt = "";
               
               $action_txt .= "<div style='padding:0px;".($aap_status_cd=="completed"?"background-color:#dff;":"")."'>"
                            . "<table class='tblidpcom' style='border-spacing:0px;'><colgroup>"
                            . "<col width='198'/>"
                            . "<col/>"
                            . "<col width='260'/>"
                            . "</colgroup>"
                            . "<tbody>"
                            . "<tr>"
                            . "<td style='width:198px;'>$method_type"
                            . ($method_t!="PROJECT"?" [<span class='ylnk' onclick='edit_action_plan(\"$request_id\",\"$actionplan_id\",this,event);'>edit</span>]":"")
                            . "</td>"
                            . "<td>$actionplan_remark"
                            . ($method_t=="PROJECT"||$method_t=="SELF"||$method_t=="COACH"||$method_t=="COUNSL"?"":"<div style='font-size:0.9em;color:#888;'>IDR ".toMoneyShort($cost_estimate)."</div>")
                            . "</td>"
                            . "<td style='width:260px;text-align:center;border-right:0px;'><div style='width:260px;'>".sql2ind($actionplan_start,"date")." - ".sql2ind($actionplan_stop,"date")."</div>"
                            . ($aap_status_cd=="completed"?"<div style='color:green;width:260px;'>Completed</div>":$event_status_txt)
                            ."</td>"
                            . "</tr>"
                            . "</tbody>"
                            . "</table>"
                            . "</div>";
            }
         
         }
      }
      
      
      if($_SESSION["xocp_page_id"]=="idphrapprove") {
         $addbtnx = "<input class='sbtn' type='button' value='Add Development Method' onclick='select_method(\"$request_id\",\"$competency_id\",this,event);'/>";
      } else {
         $addbtnx = ($self==TRUE&&$req_status_cd=="employee"?"<input class='sbtn' type='button' value='Add Development Method' onclick='select_method(\"$request_id\",\"$competency_id\",this,event);'/>":"");
      }
      
      $action_txt .= "<div style='text-align:right;padding:3px;'>"
                   //. ($self==TRUE&&$req_status_cd=="employee"?"<input class='sbtn' type='button' value='Add Development Method' onclick='select_method(\"$request_id\",\"$competency_id\",this,event);'/>":"")
                   . $addbtnx
                   . "</div>";
      return $action_txt;
   }
   
   function renderCompetency($request_id,$competency_id) {
      $db=&Database::getInstance();
      
      $user_id = getUserID();
      $sql = "SELECT c.job_id,b.employee_id"
           . " FROM ".XOCP_PREFIX."users a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(person_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job c USING(employee_id)"
           . " WHERE a.user_id = '$user_id'";
      $result = $db->query($sql);
      list($self_job_id,$self_employee_id)=$db->fetchRow($result);
      
      $sql = "SELECT a.employee_id,a.status_cd"
           . " FROM ".XOCP_PREFIX."idp_request a"
           . " WHERE a.request_id = '$request_id'";
      $result = $db->query($sql);
      $self = TRUE;
      if($db->getRowsNum($result)==1) {
         list($employee_id,$req_status_cd)=$db->fetchRow($result);
         if($employee_id==$self_employee_id) {
            $self = TRUE;
         } else {
            $self = FALSE;
         }
      }
      
      $sql = "SELECT b.competency_nm,b.competency_abbr,a.ccl,a.rcl,a.itj,a.gap,a.priority_no,a.focus_dev"
           . " FROM ".XOCP_PREFIX."idp_request_competency a"
           . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
           . " WHERE a.request_id = '$request_id'"
           . " AND a.competency_id = '$competency_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($competency_nm,$competency_abbr,$ccl,$rcl,$itj,$gap,$priority_no,$focus_dev)=$db->fetchRow($result);
      }
      
      if($focus_dev=="") {
         $focus_dev = "<span style='font-style:italic;'>"._EMPTY."</span>";
         $focusdevbtntitle = "add";
      } else {
         $focusdevbtntitle = "edit";
      }
      
      if($_SESSION["xocp_page_id"]=="idphrapprove") {
         $editcompbtn = "&nbsp;[<span onclick='edit_focus_dev(\"$request_id\",\"$competency_id\",this,event);' class='ylnk'>$focusdevbtntitle</span>]";
      } else {
         $editcompbtn = ($self==FALSE&&$req_status_cd=="start"?"&nbsp;[<span onclick='edit_focus_dev(\"$request_id\",\"$competency_id\",this,event);' class='ylnk'>$focusdevbtntitle</span>]":"");
      }
      
      $sql = "SELECT revision_id,focus_dev FROM ".XOCP_PREFIX."idp_rev_request_competency WHERE request_id = '$request_id' AND competency_id = '$competency_id' ORDER BY revision_id DESC";
      $result = $db->query($sql);
      $rev = $revlink = "";
      if($db->getRowsNum($result)>0) {
         $revlink = "&nbsp;[<span class='xlnk' onclick='view_competency_revision(\"$request_id\",\"$competency_id\",this,event);'>rev</span>]";
         $rev = "<div id='rev_comp_${request_id}_${competency_id}' style='display:none;position:absolute;width:200px;background-color:#fff;border:1px solid #888;-moz-border-radius:5px;padding:3px;-moz-box-shadow:1px 1px 3px #999;'>";
         while(list($revision_id,$focus_dev_rev)=$db->fetchRow($result)) {
            $rev .= "<div><div style='background-color:#ddd;'>Revision: $revision_id</div><div style='padding:5px;'>$focus_dev_rev</div></div>";
         }
         $rev .= "</div>";
      }
      
      $focus_txt = "<div id='focus_${request_id}_${competency_id}'>$focus_dev"
              /// hack untuk data entry
              ///. ($self==FALSE&&$req_status_cd=="start"?"&nbsp;[<span onclick='edit_focus_dev(\"$request_id\",\"$competency_id\",this,event);' class='ylnk'>$focusdevbtntitle</span>]":"")
              ///. (TRUE?"&nbsp;[<span onclick='edit_focus_dev(\"$request_id\",\"$competency_id\",this,event);' class='ylnk'>$focusdevbtntitle</span>]":"")
              . $editcompbtn
              //. $revlink
              //. $rev
              . "</div>";
      $action_txt = $this->renderActionList($request_id,$competency_id);
      
      if($_SESSION["xocp_page_id"]=="idphrapprove") {
         $deletecompbtn = "onmousemove='$(\"compbtndel_${request_id}_${competency_id}\").style.visibility=\"visible\";' "
                        . "onmouseout='$(\"compbtndel_${request_id}_${competency_id}\").style.visibility=\"hidden\";' ";
      } else {
         $deletecompbtn = ($self==FALSE&&$req_status_cd=="start"?"onmousemove='$(\"compbtndel_${request_id}_${competency_id}\").style.visibility=\"visible\";' ":"")
                        . ($self==FALSE&&$req_status_cd=="start"?"onmouseout='$(\"compbtndel_${request_id}_${competency_id}\").style.visibility=\"hidden\";' ":"");
      }
      
      
      //////// table for delete button
      return "<table $deletecompbtn ><tbody><tr><td style=''>"
           . "<div style='border:2px solid #777;padding:0px;width:900px !important;-moz-border-radius:5px;'>"
           . "<table id='ctbl_${request_id}_${competency_id}' style='border-spacing:2px;width:100%;background-color:#ddf;-moz-border-radius:3px 3px 0 0;'>"
           . "<colgroup><col/><col width='100'/><col width='30'/></colgroup>"
           . "<tbody><tr>"
              . "<td style='padding-left:3px;'><span style='font-weight:bold;color:#222;'>$competency_abbr - $competency_nm&nbsp;&nbsp;</span>"
              . "</td>"
              . "<td style='text-align:right;'>Priority : $priority_no</td>"
              . "<td style='text-align:center;'>"
                  . ($self==FALSE&&$req_status_cd=="start"?"<img onclick='up_priority(\"$request_id\",\"$competency_id\",\"$priority_no\");' src='".XOCP_SERVER_SUBDIR."/images/uplevel.gif' style='cursor:pointer;vertical-align:baseline;' title='Change Priority'/>":"")
                  . "&nbsp;</td>"
           . "</tr></tbody>"
           . "</table>"
           
           . "<table class='tblidpcom'><colgroup>"
           . "<col width='170'/>"
           . "<col width='198'/>"
           . "<col/>"
           . "<col width='260'/>"
           . "</colgroup>"
           . "<thead>"
           . "<tr>"
           . "<td style='width:170px;'>Focus of Development</td>"
           . "<td style='width:198px;'><div style='width:198px;'>Development Method</div></td>"
           . "<td style=''>Action Plan</td>"
           . "<td style='width:260px;border-right:0px;'>Time Frame</td>"
           . "</tr>"
           . "</thead>"
           . "<tbody>"
           . "<tr>"
           . "<td style='border-bottom:0px;-moz-border-radius:0 0 0 3px;'>$focus_txt</td>"
           . "<td id='actlist_${request_id}_${competency_id}' colspan='3' style='border-bottom:0px;border-right:0px;padding:0px;-moz-border-radius:0 0 3px 0;'>$action_txt</td>"
           . "</tr>"
           . "</tbody>"
           . "</table>"
           
           
           . "</div>"
           
           
           ///// table for delete button
           . "</td><td style='vertical-align:top;'>"
              . "<span id='compbtndel_${request_id}_${competency_id}' style='visibility:hidden;'><input class='sbtn' type='button' value='"._DELETE."' style='-moz-box-shadow:1px 1px 2px #333;' onclick='delete_competency(\"$request_id\",\"$competency_id\",\"$competency_abbr\",\"$competency_nm\",this,event);'/></span>"
           . "</td></tr></tbody></table>";
   }
   
}

} /// HRIS_IDPREQUESTAJAX_DEFINED
?>