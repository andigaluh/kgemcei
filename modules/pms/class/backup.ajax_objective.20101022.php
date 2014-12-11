<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_objective.php                   //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_OBJECTIVEAJAX_DEFINED') ) {
   define('HRIS_OBJECTIVEAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");

class _pms_class_ObjectiveAjax extends AjaxListener {
   
   function _pms_class_ObjectiveAjax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/pms/class/ajax_objective.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_browseOrgs","app_selectOrg",
                                             "app_editSO","app_getNo","app_saveSO",
                                             "app_deleteSO","app_browseOrgShare","app_addShare",
                                             "app_viewShare","app_deleteShare","app_editKPI",
                                             "app_saveKPI","app_deleteKPI","app_editKPIShare","app_saveKPIShare",
                                             "app_setSOOrigin","app_getCauseEffectRelation","app_saveCauseEffectRelation",
                                             "app_deployObjectives","app_importObjectives","app_saveJAMTargetWeight",
                                             "app_saveJAMTargetText","app_submitJAM","app_approval1JAM",
                                             "app_firstAssessorReturnJAM","app_approval2JAM","app_nextAssessorReturnJAM",
                                             "app_saveActionPlan","app_setCurrentStatusDate","app_saveCurrentTargetAchievement",
                                             "app_createSnapshot","app_loadSnapshotHistory","app_selectEmployee",
                                             "app_editActionPlan","app_newActionPlan","app_deleteActionPlan",
                                             "app_submitActionPlan","app_approval1PMSActionPlan",
                                             "app_firstAssessorReturnPMSActionPlan","app_setPMSMonth",
                                             "app_editAchievement","app_setAchievement","app_addSub",
                                             "app_recalculate","app_savePICA","app_submitActionPlanReport",
                                             "app_firstAssessorReturnPMSActionPlanReport","app_approvalPMSActionPlanReport",
                                             "app_calcRemainingShare");
   }
   
   function app_calcRemainingShare($args) {
      $db=&Database::getInstance();
      $pms_objective_id = $args[0];
      $pms_kpi_id = $args[1];
      $pms_share_org_id = $args[2];
      
      $sql = "SELECT pms_share_weight,pms_share_org_id,pms_kpi_id FROM pms_kpi_share WHERE pms_objective_id = '$pms_objective_id'";
      $result = $db->query($sql);
      $ttl = 0;
      $ttl_other = 0;
      $this_share = 0;
      if($db->getRowsNum($result)>0) {
         while(list($pms_share_weightx,$pms_share_org_idx,$pms_kpi_idx)=$db->fetchRow($result)) {
            if($pms_share_org_idx==$pms_share_org_id&&$pms_kpi_idx==$pms_kpi_id) {
               $this_share = $pms_share_weightx;
            } else {
               $ttl_other = _bctrim(bcadd($ttl_other,$pms_share_weightx));
            }
            $ttl = _bctrim(bcadd($ttl,$pms_share_weightx));
         }
      }
      
      $sql = "SELECT pms_objective_weight FROM pms_objective WHERE pms_objective_id = '$pms_objective_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($pms_objective_weight)=$db->fetchRow($result);
      }
      
      $ret = "Objective Weight: ".toMoney($pms_objective_weight)." %<br/>"
           . "Used : ".toMoney($ttl)." %<br/>"
           . "Remaining : <span class='xlnk' onclick='get_all_remaining(\"$pms_objective_id\",\"$pms_kpi_id\",\"$pms_share_org_id\",this,event);'>".toMoney(bcsub($pms_objective_weight,$ttl))."</span> %";
      
      _debuglog("$pms_objective_weight - $ttl_other");
      
      return array(_bctrim(bcsub($pms_objective_weight,$ttl_other)),$ret,$this_share);
      
   }
   
   function app_approvalPMSActionPlanReport($args) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $db=&Database::getInstance();
      $month_id = $_SESSION["pms_month"];
      $user_id = getUserID();
      $employee_id = $args[0];
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
      
      $sql = "SELECT report_approval_st,pms_objective_id,actionplan_id FROM pms_pic_action WHERE employee_id = '$employee_id' AND month_id = '$month_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($report_approval_st,$pms_objective_id,$actionplan_id)=$db->fetchRow($result)) {
            switch($report_approval_st) {
               case "approval":
                  $sql = "UPDATE pms_pic_action SET report_approval_st = 'final', report_return_note = '', report_approval_dttm = now()"
                       . " WHERE pms_objective_id = '$pms_objective_id' AND employee_id = '$employee_id' AND actionplan_id = '$actionplan_id'";
                  $db->query($sql);
                  
                  $sql = "SELECT pica_id FROM pms_pic_action"
                       . " WHERE employee_id = '$employee_id'"
                       . " AND pms_objective_id = '$pms_objective_id'"
                       . " AND actionplan_id = '$actionplan_id'";
                  $rpica = $db->query($sql);
                  list($pica_id)=$db->fetchRow($rpica);
                  
                  if($pica_id>0) {
                     $sql = "UPDATE pms_pic_action SET approval_st = 'implementation', return_note = '', submit_dttm = now()"
                          . " WHERE pms_objective_id = '$pms_objective_id' AND employee_id = '$employee_id' AND actionplan_id = '$pica_id'";
                     $db->query($sql);
                  }
                  
                  break;
               default:
                  break;
            }
         }
      }
   
   }
   
   function app_firstAssessorReturnPMSActionPlanReport($args) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $db=&Database::getInstance();
      $user_id = getUserID();
      $month_id = $_SESSION["pms_month"];
      $employee_id = $args[0];
      $return_note = addslashes(urldecode($args[1]));
      
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
      
      $sql = "SELECT report_approval_st,pms_objective_id,actionplan_id FROM pms_pic_action WHERE employee_id = '$employee_id' AND month_id = '$month_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($report_approval_st,$pms_objective_id,$actionplan_id)=$db->fetchRow($result)) {
            switch($report_approval_st) {
               case "approval":
                  $sql = "UPDATE pms_pic_action SET report_approval_st = 'return', report_approval_dttm = '0000-00-00 00:00:00', report_return_note = '$return_note'"
                       . " WHERE pms_objective_id = '$pms_objective_id' AND employee_id = '$employee_id' AND actionplan_id = '$actionplan_id'";
                  $db->query($sql);
                  _debuglog($sql);
                  break;
               default:
                  break;
            }
         }
      }
   
   }
   
   function app_submitActionPlanReport($args) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $db=&Database::getInstance();
      $user_id = getUserID();
      $month_id = $_SESSION["pms_month"];
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
      
      $sql = "SELECT a.job_nm,b.employee_ext_id,c.person_nm,b.employee_id"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job e USING(job_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " WHERE a.job_id = '$self_first_assessor_job_id'"
           . " ORDER BY e.start_dttm DESC LIMIT 1";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($first_superior_job,$nip,$first_superior_name,$first_assessor_employee_id)=$db->fetchRow($result);
      }
      
      
      
      $sql = "SELECT report_approval_st,pms_objective_id,actionplan_id FROM pms_pic_action WHERE employee_id = '$self_employee_id' AND month_id = '$month_id'";
      $result = $db->query($sql);
      _debuglog($sql);
      if($db->getRowsNum($result)>0) {
         while(list($report_approval_st,$pms_objective_id,$actionplan_id)=$db->fetchRow($result)) {
            switch($report_approval_st) {
               case "new":
               case "return":
                  $sql = "UPDATE pms_pic_action SET report_approval_st = 'approval', report_return_note = '', report_submit_dttm = now()"
                       . " WHERE pms_objective_id = '$pms_objective_id' AND employee_id = '$self_employee_id' AND actionplan_id = '$actionplan_id'";
                  $db->query($sql);
                  
                  $sql = "SELECT pica_id FROM pms_pic_action"
                       . " WHERE employee_id = '$self_employee_id'"
                       . " AND pms_objective_id = '$pms_objective_id'"
                       . " AND actionplan_id = '$actionplan_id'";
                  $rpica = $db->query($sql);
                  list($pica_id)=$db->fetchRow($rpica);
                  
                  if($pica_id>0) {
                     $sql = "UPDATE pms_pic_action SET approval_st = 'approval1', approval1_employee_id = '$first_assessor_employee_id', return_note = '', submit_dttm = now()"
                          . " WHERE pms_objective_id = '$pms_objective_id' AND employee_id = '$self_employee_id' AND actionplan_id = '$pica_id'";
                     $db->query($sql);
                  }
                  
                  break;
               default:
                  break;
            }
         }
      }
   
   }
   
   function app_savePICA($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $org_id = $_SESSION["pms_org_id"];
      $employee_id = $args[0]+0;
      $src_pms_objective_id = $args[1]+0;
      $src_actionplan_id = $args[2]+0;
      $root_cause = addslashes(trim(urldecode($args[3])));
      $improvement = addslashes(trim(urldecode($args[4])));
      $month_id = $args[5]+0;
      $pica_id = $args[6]+0;
      
      $sql = "UPDATE pms_pic_action SET root_cause = '$root_cause', target_text = '$improvement', month_id = '$month_id'"
           . " WHERE employee_id = '$employee_id'"
           . " AND pms_objective_id = '$src_pms_objective_id'"
           . " AND actionplan_id = '$pica_id'";
      $db->query($sql);
      return $this->getActionPlanUpdate($src_pms_objective_id,$employee_id,TRUE,$_SESSION["pms_month"]);
      
   }
   
   function app_recalculate($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $org_id = $_SESSION["pms_org_id"];
      $this->recalculate($org_id);
   }
   
   function recalculate($org_id) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      
      $shared_objective = array();
      $share_kpi = array();
      $total_weight = 0;
      $local_weight = 0;
      $sql = "SELECT pms_objective_id,pms_objective_weight FROM pms_objective WHERE pms_org_id = '$org_id' AND pms_parent_objective_id = '0'";
      $rc = $db->query($sql);
      if($db->getRowsNum($rc)>0) {
         while(list($local_pms_objective_id,$local_pms_objective_weight)=$db->fetchRow($rc)) {
            $local_weight = _bctrim(bcadd($local_weight,$local_pms_objective_weight));
         }
      }
      
      $factor = _bctrim(bcsub(100,$local_weight));
            
      /*
         factor = 100 - local weight
         because local weight is already in 100 based scale
      */
      
      $sql = "SELECT a.pms_objective_id,b.pms_share_weight,b.pms_kpi_id"
           . " FROM pms_objective a"
           . " LEFT JOIN pms_kpi_share b ON b.pms_objective_id = a.pms_parent_objective_id AND b.pms_kpi_id = a.pms_parent_kpi_id AND b.pms_share_org_id = a.pms_org_id"
           . " LEFT JOIN pms_kpi c ON c.pms_objective_id = b.pms_objective_id AND c.pms_kpi_id = b.pms_kpi_id"
           . " LEFT JOIN pms_objective d ON d.pms_objective_id = c.pms_objective_id"
           . " WHERE a.pms_org_id = '$org_id'"
           . " AND a.pms_parent_org_id > '0'"
           . " AND a.pms_parent_org_id != '$org_id'"
           . " AND d.pms_objective_id IS NOT NULL";
      $rkpi = $db->query($sql);
      $shared_objective = array();
      if($db->getRowsNum($rkpi)>0) {
         while(list($pms_objective_id,$pms_share_weight,$pms_kpi_id)=$db->fetchRow($rkpi)) {
            $total_weight = _bctrim(bcadd($total_weight,$pms_share_weight));
            $shared_objective[$pms_objective_id][$pms_kpi_id] = array($pms_objective_id,$pms_kpi_id,$pms_share_weight);
         }
      }
      
      
      /// $total_weight is total inherited
      
      $new_pms_objective_no = 0;
      $new_total_objective_weight = 0;
      foreach($shared_objective as $pms_perspective_id=>$v0) {
         $new_pms_objective_no = 0;
         foreach($v0 as $pms_objective_id=>$v1) {
            $new_pms_objective_no++;
            list($pms_objective_text,$pms_objective_start,$pms_objective_stop)=$v1;
            $objective_weight = 0;
            $objective_pic_job_id = 0;
            $objective_pic_employee_id = 0;
            $new_objective_start = "9999-12-31 00:00:00";
            $new_objective_stop = "0000-00-00 00:00:00";
            if(is_array($shared_kpi)) {
               foreach($shared_kpi[$pms_objective_id] as $pms_kpi_id=>$v2) {
                  list($pms_share_weight,$pms_kpi_text,$pms_kpi_start,$pms_kpi_stop,$pms_kpi_target_text,$pms_kpi_measurement_unit)=$v2;
                  $objective_weight = _bctrim(bcadd($objective_weight,$pms_share_weight));
                  $new_objective_start = min($pms_kpi_start,$new_objective_start);
                  $new_objective_stop = max($pms_kpi_stop,$new_objective_stop);
               }
            }
            $new_objective_weight = _bctrim(bcmul(bcdiv($objective_weight,$total_weight),$factor));
            $new_total_objective_weight = _bctrim(bcadd($new_total_objective_weight,$new_objective_weight));
            //// update objective here:
            
            //$sql = "UPDATE pms_objective SET pms_objective_weight = '$new_objective_weight'"
            //     . " WHERE pms_objective_id = '
            
            $db->query($sql);
            $new_objective_id = $db->getInsertId();
            
            $new_kpi_id = 0;
            //// insert kpi here:
            if(is_array($shared_kpi)) {
               foreach($shared_kpi[$pms_objective_id] as $pms_kpi_id=>$v2) {
                  list($pms_share_weight,$pms_kpi_text,$pms_kpi_start,$pms_kpi_stop,$pms_kpi_target_text,$pms_kpi_measurement_unit)=$v2;
                  $new_kpi_share_weight = _bctrim(bcmul(bcdiv($pms_share_weight,$total_weight),100));
                  $new_kpi_id++;
                  $sql = "INSERT INTO pms_kpi (pms_objective_id,pms_kpi_id,pms_kpi_text,pms_kpi_start,pms_kpi_stop,pms_kpi_target_text,pms_kpi_measurement_unit,pms_parent_objective_id,pms_parent_kpi_id)"
                       . " VALUES ('$new_objective_id','$new_kpi_id','$pms_kpi_text','$pms_kpi_start','$pms_kpi_stop','$pms_kpi_target_text','$pms_kpi_measurement_unit','$pms_objective_id','$pms_kpi_id')";
                  $db->query($sql);
               }
            }
            
            
         }
      }
   }
   
   function app_addSub($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $pms_objective_id = $args[0];
      $org_id = $_SESSION["pms_org_id"];
      
      $sql = "SELECT a.org_nm,b.org_class_nm FROM ".XOCP_PREFIX."orgs a LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id) WHERE a.org_id = '$org_id'";
      $result = $db->query($sql);
      list($org_nm,$org_class_nm)=$db->fetchRow($result);
      
      
      $sql = "SELECT a.pms_perspective_id,b.pms_perspective_code FROM pms_objective a LEFT JOIN pms_perspective b USING(pms_perspective_id) WHERE a.pms_objective_id = '$pms_objective_id'";
      $result = $db->query($sql);
      list($pms_perspective_id,$pms_perspective_code)=$db->fetchRow($result);
      
      $sql = "SELECT pms_objective_id,pms_objective_no FROM pms_objective WHERE pms_org_id = '$org_id' AND pms_parent_objective_id = '$pms_objective_id' ORDER BY pms_objective_no DESC LIMIT 1";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($pms_objective_idx,$pms_objective_nox)=$db->fetchRow($result);
         if($pms_objective_idx=="") {
            $sql = "SELECT pms_objective_no FROM pms_objective WHERE pms_objective_id = '$pms_objective_id'";
            $result = $db->query($sql);
            list($pms_objective_no)=$db->fetchRow($result);
            $new_pms_objective_no = $pms_objective_no.".1";
         } else {
            $nos = explode(".",$pms_objective_nox);
            _dumpvar($nos);
            $n = count($nos)-1;
            $new_pms_objective_no = substr($pms_objective_nox,0,-2).".".($nos[$n]+1);
         }
      } else {
         $sql = "SELECT pms_objective_no FROM pms_objective WHERE pms_objective_id = '$pms_objective_id'";
         $result = $db->query($sql);
         list($pms_objective_no)=$db->fetchRow($result);
         $new_pms_objective_no = $pms_objective_no.".1";
      }
      
      $sql = "SELECT pms_perspective_code,pms_perspective_id,pms_perspective_name FROM pms_perspective ORDER BY pms_perspective_id";
      $result = $db->query($sql);
      $optpers = "";
      if($db->getRowsNum($result)>0) {
         while(list($pms_perspective_codex,$pms_perspective_idx,$pms_perspective_name)=$db->fetchRow($result)) {
            $optpers .= "<option value='$pms_perspective_idx|$pms_perspective_codex' ".($pms_perspective_idx==$pms_perspective_id?"selected='selected'":"").">$pms_perspective_name</option>";
         }
      }
      $sel_pers = "<tr><td>Perspective</td><td><select id='pms_perspective_id' name='pms_perspective_id' onchange='chgpers(this,event);'>$optpers</select>&#160;"
                . "No : <input disabled='1' name='pms_objective_no' id='pms_objective_no' type='text' style='width:40px;text-align:center;' value='$new_pms_objective_no'/>"
                . "</td></tr>";
      
      //// source objective
      $sql = "SELECT p.pms_perspective_code,a.pms_objective_no,a.pms_org_id,a.pms_objective_text,b.org_nm,c.org_class_nm,a.pms_objective_weight"
           . " FROM pms_objective a"
           . " LEFT JOIN ".XOCP_PREFIX."orgs b ON b.org_id = a.pms_org_id"
           . " LEFT JOIN ".XOCP_PREFIX."org_class c USING(org_class_id)"
           . " LEFT JOIN pms_perspective p ON p.pms_perspective_id = a.pms_perspective_id"
           . " WHERE a.pms_objective_id = '$pms_objective_id'";
      $result = $db->query($sql);
      list($pms_parent_perspective_code,$pms_parent_objective_no,$pms_parent_org_idxxx,$pms_parent_objective_text,$pms_parent_org_nm,$pms_parent_org_class_nm,$pms_parent_objective_weight)=$db->fetchRow($result);
      
      $sql = "SELECT pms_objective_id,pms_org_id,pms_objective_weight FROM pms_objective WHERE pms_parent_objective_id = '$pms_objective_id'"
           //. " AND pms_objective_id != '$pms_objective_id'"
           . " AND pms_org_id = '$org_id'";
      $rchild = $db->query($sql);
      $has_local_sub = 0;
      $ttl_sub_weight = 0;
      if($db->getRowsNum($rchild)>0) {
         while(list($sub_pms_objective_id,$sub_pms_org_id,$sub_weight)=$db->fetchRow($rchild)) {
            $has_local_sub++;
            $ttl_sub_weight = _bctrim(bcadd($ttl_sub_weight,$sub_weight));
         }
      }
      
      $source_so = "Strategic Objective Source:"
                 . "<div id='parent_so'>"
                 . "<table class='xxfrm' style='width:100%;'>"
                 . "<colgroup><col width='150'/><col/></colgroup>"
                 . "<tbody>"
                 . "<tr><td>Organization</td><td>".htmlentities("$pms_parent_org_nm $pms_parent_org_class_nm")."</td></tr>"
                 . "<tr><td>Strategic Objective</td><td>".htmlentities("${pms_parent_perspective_code}${pms_parent_objective_no} $pms_parent_objective_text")."</td></tr>"
                 . "<tr><td>Weight</td><td>".toMoney($pms_parent_objective_weight)." % <span style='color:blue;'>( ".toMoney(bcsub($pms_parent_objective_weight,$ttl_sub_weight))." remaining )</span></td></tr>"
                 ///. "<tr><td colspan='2'><input type='button' value='Select Source' onclick='change_so_origin(this,event);'/></td></tr>"
                 . "</tbody></table>"
                 . "<input type='hidden' name='pms_parent_objective_id' id='pms_parent_objective_id' value='$pms_objective_id'/>"
                 . "</div>";
      
      $sql = "SELECT a.job_id,a.job_cd,a.job_nm,a.description,"
           . "b.org_nm,c.org_class_nm,a.job_abbr,(d.job_level+0) as srt"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."orgs b USING(org_id)"
           . " LEFT JOIN ".XOCP_PREFIX."org_class c USING(org_class_id)"
           . " LEFT JOIN ".XOCP_PREFIX."job_class d ON d.job_class_id = a.job_class_id"
           . " WHERE a.status_cd = 'normal'"
           . " ORDER BY d.job_class_level, b.org_nm,d.gradeval_bottom DESC,srt DESC,a.job_nm";
      
      $result = $db->query($sql);
      $optpic = "<option value=''></option>";
      if($db->getRowsNum($result)>0) {
         while(list($job_id,$job_cd,$job_nm,$description,$org_nmx,$org_class_nmx,$job_abbr)=$db->fetchRow($result)) {
            $optpic .= "<option value='$job_id' ".($job_id==$pms_pic_job_id?"selected='selected'":"").">".htmlentities("$job_abbr - $job_nm")."</option>";
         }
      }
      
      $title = "Add New Sub Strategic Objective";
      $btn = "<input type='button' value='Add New' onclick='save_so(\"new\",this,event);'/>&#160;&#160;"
           . "<input type='button' value='"._CANCEL."' onclick='editsobox.fade();'/>";
      $btn_sub = "&nbsp;";
      $tm_start = getSQLDate();
      $tm_stop = getSQLDate();
      
      $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>"
           . $title
           . "</div>"
           
           . "<div style='padding:5px;background-color:#f2f2f2;color:#555;'>"
           
           
              . "<div style='border:1px solid #999;background-color:#fff;padding:4px;' id='frmobjective'>"
                  
                  
                  . "<table style='width:100%;border-spacing:0px;'><tbody><tr><td style='height:275px;'>"
                  
                  . "<div style='max-height:375px;overflow:auto;padding-top:3px;'>"
                  
                  . "<div style='display:none;' id='origin_chooser'>"
                     . $selso
                  . "</div>"
                  
                  . "<div id='so_editor'>"
                  
                  . $source_so
                  
                  . "<br/>"
                  . "Strategic Objective:"
                  . "<table class='xxfrm' style='width:100%;'>"
                  . "<colgroup><col width='150'/><col/></colgroup>"
                  . "<tbody>"
                  . "<tr><td>Organization</td><td>$org_nm $org_class_nm</td></tr>"
                  . "<tr><td>ID</td><td id='pms_obj_code'>${pms_perspective_code}${new_pms_objective_no}</td></tr>"
                  
                  
                  . $sel_pers
                  
                  . "<tr><td>Strategic Objective</td><td><input type='text' id='so_txt' name='so_txt' style='width:400px;' value='".htmlentities($pms_objective_text)."'/></td></tr>"
                  
                  . "<tr><td>PIC</td><td><select id='pic_job_id' name='pic_job_id'>$optpic</select></td></tr>"
                  
                  . "<tr><td>Weight</td><td><input id='weight' name='weight' type='text' style='width:40px;' value='$pms_objective_weight'/> %</td></tr>"
                  . "<tr><td>Time Frame</td><td>"
                  . "<span class='xlnk' id='sp_tm_start' onclick='_changedatetime(\"sp_tm_start\",\"h_tm_start\",\"date\",true,false);'>".sql2ind($tm_start,"date")."</span>"
                  . "<input type='hidden' name='h_tm_start' id='h_tm_start' value='$tm_start'/>"
                  . " until "
                  . "<span class='xlnk' id='sp_tm_stop' onclick='_changedatetime(\"sp_tm_stop\",\"h_tm_stop\",\"date\",true,false);'>".sql2ind($tm_stop,"date")."</span>"
                  . "<input type='hidden' name='h_tm_stop' id='h_tm_stop' value='$tm_stop'/>"
                  . "</td></tr>"
                  . "</tbody></table>"
                  
                  . "</div>" /// so_editor
                  
                  . "</div>"
                  . "<input type='hidden' name='pms_objective_id' id='pms_objective_id' value='new'/>"
                  . "</td></tr></tbody></table>"
                  
                  
                  
              . "</div>"
           . "</div>"
           
           
           
           . "<div style='text-align:center;padding:10px;background-color:#f2f2f2;height:100px;' id='frmbtn'>"
           
           
           
           . "<div id='vbtn'>"
           . "<table style='width:100%;border-spacing:0px;'>"
           . "<tbody>"
           . "<tr>"
           . "<td style='text-align:left;'>$btn_sub</td>"
           . "<td style='text-align:right;'>$btn</td>"
           . "</tr>"
           . "</tbody>"
           . "</table>"
           . "</div>"
           
           
           . "</div>";
      
      return $ret;
      
      
      
   }
   
   function app_setAchievement($args) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $db=&Database::getInstance();
      $user_id = getUserID();
      $employee_id = $args[0];
      $pms_objective_id = $args[1];
      $actionplan_id = $args[2];
      $achievement = _bctrim(bcadd($args[3],0));
      $final_result = addslashes(urldecode($args[4]));
      
      $generate_pica = 0;
      
      $sql = "SELECT actionplan_text,target_text,current_achievement,target_achievement,final_result_text,is_pica,pica_id,month_id"
          . " FROM pms_pic_action"
          . " WHERE employee_id = '$employee_id'"
          . " AND pms_objective_id = '$pms_objective_id'"
          . " AND actionplan_id = '$actionplan_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($actionplan_text,$target_text,$current_achievement,$target_achievement,$final_result_text,$is_pica,$pica_id,$month_id)=$db->fetchRow($result);
      }
      
      $pica_month_id = $month_id+1;
      
      if($pica_month_id>12) $pica_month_id = 12;
      
      $sql = "UPDATE pms_pic_action SET "
           . "current_achievement = '$achievement',"
           . "final_result_text = '$final_result'"
           . " WHERE employee_id = '$employee_id'"
           . " AND pms_objective_id = '$pms_objective_id'"
           . " AND actionplan_id = '$actionplan_id'";
      $db->query($sql);
      
      
      if(bccomp($achievement,$target_achievement)<0) { /// pica
         
         $target_for_pica = _bctrim(bcsub($target_achievement,$achievement));
         
         if($pica_id==0) { /// create new action plan for pica
            $sql = "SELECT MAX(actionplan_id) FROM pms_pic_action"
                 . " WHERE employee_id = '$employee_id' AND pms_objective_id = '$pms_objective_id'";
            $result = $db->query($sql);
            if($db->getRowsNum($result)>0) {
               list($new_actionplan_id)=$db->fetchRow($result);
            }
            
            $new_actionplan_id++;
            
            $sql = "SELECT MAX(order_no) FROM pms_pic_action WHERE employee_id = '$employee_id' AND pms_objective_id = '$pms_objective_id'";
            $result = $db->query($sql);
            $order_no = 0;
            if($db->getRowsNum($result)>0) {
               list($order_no)=$db->fetchRow($result);
            }
            
            $order_no++;
            $sql = "INSERT INTO pms_pic_action (pms_objective_id,employee_id,actionplan_id,month_id,created_dttm,created_user_id,order_no,is_pica,actionplan_text,target_text,target_achievement,prev_actionplan_id)"
                 . " VALUES ('$pms_objective_id','$employee_id','$new_actionplan_id','$pica_month_id',now(),'$user_id','$order_no','1','".addslashes($actionplan_text)."','".addslashes($target_text)."','$target_for_pica','$actionplan_id')";
            $db->query($sql);
            
            /// update link to pica
            $sql = "UPDATE pms_pic_action SET "
                 . "pica_id = '$new_actionplan_id'"
                 . " WHERE employee_id = '$employee_id'"
                 . " AND pms_objective_id = '$pms_objective_id'"
                 . " AND actionplan_id = '$actionplan_id'";
            $db->query($sql);
            
            $pica_id = $new_actionplan_id;
         } else {
            $sql = "UPDATE pms_pic_action SET target_achievement = '$target_for_pica',is_pica = '1'"
                 . " WHERE employee_id = '$employee_id'"
                 . " AND pms_objective_id = '$pms_objective_id'"
                 . " AND actionplan_id = '$pica_id'";
            $db->query($sql);
         }
         
         $sql = "SELECT actionplan_text,target_text,month_id,target_achievement,root_cause,target_text,month_id"
              . " FROM pms_pic_action"
              . " WHERE employee_id = '$employee_id'"
              . " AND pms_objective_id = '$pms_objective_id'"
              . " AND actionplan_id = '$pica_id'";
         $result = $db->query($sql);
         list($pica_actionplan_text,$pica_target_text,$pica_month_id,$pica_target,$pica_root_cause,$pica_improvement,$pica_month_id)=$db->fetchRow($result);
         
         $sql = "SELECT a.pms_objective_text,a.pms_objective_no,b.pms_perspective_code"
              . " FROM pms_objective a"
              . " LEFT JOIN pms_perspective b USING(pms_perspective_id)"
              . " WHERE a.pms_objective_id = '$pms_objective_id'";
         $result = $db->query($sql);
         list($pms_objective_text,$pms_objective_no,$pms_perspective_code)=$db->fetchRow($result);
         
         global $xocp_vars;
         $opt_month = "";
         foreach($xocp_vars["month_year"] as $k=>$v) {
            if($v=="") continue;
            $opt_month .= "<option value='$k' ".($pica_month_id==$k?"selected='1'":"").">$v</option>";
         }
         
         $ret = "<div style='padding:10px;color:red;padding-top:5px;'>Your achievement generate PICA. Please fulfill PICA form below:</div>"
              . "<input type='hidden' id='pica_id' value='$pica_id'/>"
              . "<table class='sfrm' align='center'>"
              . "<tbody>"
              . "<tr><td>Objective :</td><td>${pms_perspective_code}${pms_objective_no} $pms_objective_text</td></tr>"
              . "<tr><td>Action Plan :</td><td>$actionplan_text</td></tr>"
              . "<tr><td>Current Achievement :</td><td>".toMoney($achievement)." %</td></tr>"
              . "<tr><td>Root Cause :</td><td><input type='text' style='width:250px;' id='root_cause' value='".addslashes($pica_root_cause)."'/></td></tr>"
              
              . "<tr><td>Next Target Month :</td><td><select id='pica_month_id'>$opt_month</select></td></tr>"
              . "<tr><td>Next Target Improvement :</td><td><input type='text' style='width:250px;' id='target_text' value='".addslashes($pica_target_text)."'/></td></tr>"
              . "<tr><td>Next Target Achievement :</td><td>".toMoney($pica_target)." %</td></tr>"
              
              . "</tbody>"
              . "</table>";
         $generate_pica = 1;
         return array($generate_pica,$ret);
      } else {
         
         //// clean up pica if any
         $sql = "DELETE FROM pms_pic_action"
              . " WHERE employee_id = '$employee_id'"
              . " AND pms_objective_id = '$pms_objective_id'"
              . " AND is_pica = '1'"
              . " AND prev_actionplan_id = '$actionplan_id'";
         $db->query($sql);
         
         $sql = "UPDATE pms_pic_action SET pica_id = '0'"
              . " WHERE employee_id = '$employee_id'"
              . " AND pms_objective_id = '$pms_objective_id'"
              . " AND actionplan_id = '$actionplan_id'";
         $db->query($sql);
         $generate_pica = 0;
         
      }
      
   }
   
   function app_editAchievement($args) {
      global $xocp_vars;
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $db=&Database::getInstance();
      $user_id = getUserID();
      $employee_id = $args[0];
      $pms_objective_id = $args[1];
      $actionplan_id = $args[2];
      
      $sql = "SELECT actionplan_text,target_text,current_achievement,target_achievement,final_result_text,is_pica,pica_id,month_id"
          . " FROM pms_pic_action"
          . " WHERE employee_id = '$employee_id'"
          . " AND pms_objective_id = '$pms_objective_id'"
          . " AND actionplan_id = '$actionplan_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($actionplan_text,$target_text,$current_achievement,$target_achievement,$final_result_text,$is_pica,$pica_id,$month_id)=$db->fetchRow($result);
         $sql = "SELECT a.pms_objective_text,a.pms_objective_no,b.pms_perspective_code"
              . " FROM pms_objective a"
              . " LEFT JOIN pms_perspective b USING(pms_perspective_id)"
              . " WHERE a.pms_objective_id = '$pms_objective_id'";
         $result = $db->query($sql);
         list($pms_objective_text,$pms_objective_no,$pms_perspective_code)=$db->fetchRow($result);
         if(trim($actionplan_text)=="") {
            $actionplan_text = "<span class='empty'>"._EMPTY."</span>";
         }
         if(trim($target_text)=="") {
            $target_text = "<span class='empty'>"._EMPTY."</span>";
         }
         $ret = "<table class='sfrm' align='center'>"
              . "<tbody>"
              . "<tr><td>Objective :</td><td>${pms_perspective_code}${pms_objective_no} $pms_objective_text</td></tr>"
              . "<tr><td>Action Plan :</td><td>$actionplan_text</td></tr>"
              . "<tr><td>Month :</td><td>".$xocp_vars["month_year"][$month_id]."</td></tr>"
              . "<tr><td>Target :</td><td>$target_text</td></tr>"
              . "<tr><td>Target Achievement :</td><td>".toMoney($target_achievement)." %</td></tr>"
              . "<tr><td>Your Achievement :</td><td><input type='text' style='width:50px;' id='inp_current_achievement' value='$current_achievement'/> %</td></tr>"
              . "<tr><td>Realization :</td><td><input style='width:250px;' type='text' id='inp_final_result' value='$final_result_text'/></td></tr>"
              . "</tbody>"
              . "</table>";
         return $ret;
      }
      
   }
   
   function app_setPMSMonth($args) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $db=&Database::getInstance();
      $_SESSION["pms_month"] = $args[0]+0;
   }
   
   function app_firstAssessorReturnPMSActionPlan($args) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $db=&Database::getInstance();
      $user_id = getUserID();
      $employee_id = $args[0];
      $return_note = addslashes(urldecode($args[1]));
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
      
      $sql = "SELECT approval_st,pms_objective_id,actionplan_id FROM pms_pic_action WHERE employee_id = '$employee_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($approval_st,$pms_objective_id,$actionplan_id)=$db->fetchRow($result)) {
            switch($approval_st) {
               case "approval1":
                  $sql = "UPDATE pms_pic_action SET approval_st = 'return', approval1_dttm = '0000-00-00 00:00:00', return_note = '$return_note'"
                       . " WHERE pms_objective_id = '$pms_objective_id' AND employee_id = '$employee_id' AND actionplan_id = '$actionplan_id'";
                  $db->query($sql);
                  break;
               default:
                  break;
            }
         }
      }
   
   }
   
   function app_approval1PMSActionPlan($args) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $db=&Database::getInstance();
      $user_id = getUserID();
      $employee_id = $args[0];
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
      
      $sql = "SELECT approval_st,pms_objective_id,actionplan_id FROM pms_pic_action WHERE employee_id = '$employee_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($approval_st,$pms_objective_id,$actionplan_id)=$db->fetchRow($result)) {
            switch($approval_st) {
               case "approval1":
                  $sql = "UPDATE pms_pic_action SET approval_st = 'implementation', return_note = '', approval1_dttm = now()"
                       . " WHERE pms_objective_id = '$pms_objective_id' AND employee_id = '$employee_id' AND actionplan_id = '$actionplan_id'";
                  $db->query($sql);
                  break;
               default:
                  break;
            }
         }
      }
   }
   
   function app_submitActionPlan($args) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $db=&Database::getInstance();
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
      
      
      $sql = "SELECT a.job_nm,b.employee_ext_id,c.person_nm,b.employee_id"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job e USING(job_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " WHERE a.job_id = '$self_first_assessor_job_id'"
           . " ORDER BY e.start_dttm DESC LIMIT 1";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($first_superior_job,$nip,$first_superior_name,$first_assessor_employee_id)=$db->fetchRow($result);
      }
      
      $sql = "SELECT approval_st,pms_objective_id,actionplan_id FROM pms_pic_action WHERE employee_id = '$self_employee_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($approval_st,$pms_objective_id,$actionplan_id)=$db->fetchRow($result)) {
            switch($approval_st) {
               case "new":
               case "return":
                  $sql = "UPDATE pms_pic_action SET approval_st = 'approval1', approval1_employee_id = '$first_assessor_employee_id', return_note = '', submit_dttm = now()"
                       . " WHERE pms_objective_id = '$pms_objective_id' AND employee_id = '$self_employee_id' AND actionplan_id = '$actionplan_id'";
                  $db->query($sql);
                  break;
               default:
                  break;
            }
         }
      }
   }
   
   function app_deleteActionPlan($args) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $db=&Database::getInstance();
      $user_id = getUserID();
      $employee_id = $args[0];
      $pms_objective_id = $args[1];
      $actionplan_id = $args[2];
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
      
      $sql = "DELETE FROM pms_pic_action WHERE employee_id = '$employee_id' AND pms_objective_id = '$pms_objective_id' AND actionplan_id = '$actionplan_id'";
      $db->query($sql);
      return $this->getActionPlanUpdate($pms_objective_id,$employee_id);
   }
   
   function getActionPlanUpdate($pms_objective_id,$employee_id,$report_mode=FALSE,$month=0) {
      global $xocp_vars;
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $db=&Database::getInstance();
      $user_id = getUserID();
      $pica_block = array();
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
      
      $vardvtg = "";
      $vardvach = "";
      $varzone = "";
      $vardvaptxt = "";
      $vardvpica_root = "";
      $vardvpica_improve = "";
      $vardvpica_month = "";
      
      $xmonth = 0;
      $current_objective_ap_count = 0;
      $sql = "SELECT b.pms_perspective_code,a.pms_objective_no,a.pms_objective_text"
           . " FROM pms_objective a"
           . " LEFT JOIN pms_perspective b USING(pms_perspective_id)"
           . " WHERE a.pms_objective_id = '$pms_objective_id'";
      $result = $db->query($sql);
      list($pms_perspective_code,$pms_objective_no,$pms_objective_text)=$db->fetchRow($result);
      
      $sql = "SELECT COUNT(*) FROM pms_pic_action"
           . " WHERE pms_objective_id = '$pms_objective_id'"
           . " AND employee_id = '$employee_id'";
      $rcnt = $db->query($sql);
      list($apcnt)=$db->fetchRow($rcnt);
      
      $tooltip = "";
      
      $ap_need_submission = 0;
      $ap_need_approval = 0;
      $ap_submit_dttm = "0000-00-00 00:00:00";
      $ap_approval_dttm = "0000-00-00 00:00:00";
      
      
      for($i=1;$i<=12;$i++) {
         if($report_mode==TRUE) {
            if($_SESSION["pms_month"]!=$i) {
               continue;
            }
         }
         
         $vardvap = "dvapx_${i}";
         $$vardvap = "";
         $sql = "SELECT actionplan_id,actionplan_text,target_text,current_achievement,order_no,approval_st,submit_dttm,approval1_dttm,approval1_employee_id,"
              . "pica_id,is_pica,target_achievement,report_approval_st"
              . " FROM pms_pic_action"
              . " WHERE pms_objective_id = '$pms_objective_id'"
              . " AND employee_id = '$employee_id'"
              . " AND month_id = '$i'"
              . " ORDER BY order_no";
         $resultx = $db->query($sql);
         if($db->getRowsNum($resultx)>0) {
            while(list($actionplan_id,$actionplan_text,$target_text,$current_achievement,$order_no,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,
                       $pica_id,$is_pica,$target_achievement,$report_approval_st)=$db->fetchRow($resultx)) {
               
               
               if($is_pica>0) {
                  //continue;
               }
               
               if($report_mode==TRUE&&$approval_st != "implementation") continue;
               $no = $current_objective_ap_count;
               
               
               
               $ap_submit_dttm = max($ap_submit_dttm,$submit_dttm);
               $ap_approval_dttm = max($ap_approval_dttm,$approval1_dttm);
               if($is_pica==0) {
                  if($approval_st=="return") {
                     $status_return++;
                     $ap_need_submission++;
                  }
                  if($approval_st=="new") {
                     $status_new++;
                     $ap_need_submission++;
                  }
                  if($approval_st=="approval1") {
                     $status_approval1++;
                     $ap_need_approval++;
                  }
               }
               
               if($report_mode==FALSE) {
                  if($xmonth!=$i) {
                     for($vindent=0;$vindent<$current_objective_ap_count;$vindent++) {
                        
                        $indent = $vindent+1;
                        
                        if(isset($pica_block[$indent][$i])) {
                           list($pica_idx,$pica_approval_st,$pica_actionplan_text,$pica_target_text,$pica_root_cause,$pica_target_achievement,$pica_current_achievement,$pica_pms_objective_text)=$pica_block[$indent][$i];
                           $tooltip .= "\n<div style='display:none;' id='intooltip_${pms_objective_id}_${pica_idx}'>"
                                     . "<div>"
                                     . "<table class='aptooltip'><tbody>"
                                     . "<tr><td style='text-align:center;background-color:#fff;border:1px solid #bbb;' colspan='2'>PICA</td></tr>"
                                     . "<tr><td>SO : </td><td>$pica_pms_objective_text</td></tr>"
                                     . "<tr><td>Month : </td><td>".$xocp_vars["month_year"][$i]."</td></tr>"
                                     . "<tr><td>Action Plan : </td><td>$pica_actionplan_text</td></tr>"
                                     . "<tr><Td>Improvement : </td><td>$pica_target_text</td></tr>"
                                     . "<tr><Td>Root Cause : </td><td>$pica_root_cause</td></tr>"
                                     . "<tr><Td>Target : </td><td>".toMoney($pica_target_achievement)." %</td></tr>"
                                     . "<tr><Td>Ach. : </td><td>".toMoney($pica_current_achievement)." %</td></tr>"
                                     . "</tbody></table>"
                                     . "</div>"
                                     . "</div>";
                           
                           $$vardvap .= "<div"
                                      . " onmousemove='show_ap_tooltip(\"$pms_objective_id\",\"$pica_idx\",this,event);'"
                                      . " onmouseover='mouseover_aptext(\"$vindent\",\"$pms_objective_id\",\"$pica_idx\",this,event);'"
                                      . " onmouseout='mouseout_aptext(\"$vindent\",\"$pms_objective_id\",\"$pica_idx\",this,event);'"
                                      . " class='apblockpica_${pica_approval_st}'><div>&nbsp;</div></div>";
                        } else {
                           $$vardvap .= "<div"
                                      . " onmouseover='mouseover_aptext(\"$vindent\",\"$pms_objective_id\",0,this,event);'"
                                      . " onmouseout='mouseout_aptext(\"$vindent\",\"$pms_objective_id\",0,this,event);'"
                                      . " class='apblock_empty'>&nbsp;CC</div>";
                        }
                     }
                  }
               }
               
               $xmonth = $i;
               
               if(trim($actionplan_text)=="") {
                  $actionplan_text = "<span class='empty'>"._EMPTY."</span>";
               } else {
                  $actionplan_text = htmlentities($actionplan_text);
               }
               
               if(trim($target_text)=="") {
                  $target_text = "<span class='empty'>"._EMPTY."</span>";
               }  else {
                  $target_text =  htmlentities($target_text);
               }
               
               if($report_mode==TRUE) {
                  if($report_approval_st!="final") {
                     $$vardvap .= "<div"
                                . " onmousemove='show_ap_tooltip(\"$pms_objective_id\",\"$actionplan_id\",this,event);'"
                                . " onmouseover='mouseover_aptext".($is_pica==1?"pica":"")."(\"$no\",\"$pms_objective_id\",\"$actionplan_id\",this,event);'"
                                . " onmouseout='mouseout_aptext".($is_pica==1?"pica":"")."(\"$no\",\"$pms_objective_id\",\"$actionplan_id\",this,event);'"
                                . " class='apblockreport".($is_pica==1?"pica":"")."'><span class='xlnk' onclick='edit_report_ap(\"$pms_objective_id\",\"$actionplan_id\",\"$no\",this,event);'>".toMoney($current_achievement)."</span> %</div>";
                  } else {
                     $$vardvap .= "<div"
                                . " onmousemove='show_ap_tooltip(\"$pms_objective_id\",\"$actionplan_id\",this,event);'"
                                . " onmouseover='mouseover_aptext".($is_pica==1?"pica":"")."(\"$no\",\"$pms_objective_id\",\"$actionplan_id\",this,event);'"
                                . " onmouseout='mouseout_aptext".($is_pica==1?"pica":"")."(\"$no\",\"$pms_objective_id\",\"$actionplan_id\",this,event);'"
                                . " class='apblockreport".($is_pica==1?"pica":"")."'>".toMoney($current_achievement)." %</div>";
                  
                  }
               } else {
                  if($is_pica==0) {
                     $$vardvap .= "<div"
                                . " onmousemove='show_ap_tooltip(\"$pms_objective_id\",\"$actionplan_id\",this,event);'"
                                . " onmouseover='mouseover_aptext(\"$no\",\"$pms_objective_id\",\"$actionplan_id\",this,event);'"
                                . " onmouseout='mouseout_aptext(\"$no\",\"$pms_objective_id\",\"$actionplan_id\",this,event);'"
                                . " class='apblock_${approval_st}'><div>&nbsp;99</div></div>";
                  }
               }
               
               
               if($is_pica==0) {
                  _debuglog("$current_objective_ap_count -- $actionplan_text ++");
                  $current_objective_ap_count++;
               }
               
               
               if($pica_id>0) {
                  $sqlx = "SELECT actionplan_text,target_text,root_cause,target_achievement,current_achievement,approval_st,month_id FROM pms_pic_action"
                       . " WHERE pms_objective_id = '$pms_objective_id'"
                       . " AND employee_id = '$employee_id'"
                       . " AND actionplan_id = '$pica_id'";
                  $rpicax = $db->query($sqlx);
               
                  if($db->getRowsNum($rpicax)>0) {
                     list($pica_actionplan_text,$pica_target_text,$pica_root_cause,$pica_target_achievement,$pica_current_achievement,$pica_approval_st,$pica_month_idxx)=$db->fetchRow($rpicax);
                     $pica_block[$current_objective_ap_count][$pica_month_idxx] = array($pica_id,$pica_approval_st,$pica_actionplan_text,$pica_target_text,$pica_root_cause,$pica_target_achievement,$pica_current_achievement,$pms_objective_text);
                  }
               }
               
               
               
               if($is_pica==0) {
               
                  $vardvaptxt .= "<div onmousemove='show_ap_tooltip(\"$pms_objective_id\",\"$actionplan_id\",this,event);' onmouseover='mouseover_aptext(\"$no\",\"$pms_objective_id\",\"$actionplan_id\",this,event);' onmouseout='mouseout_aptext(\"$no\",\"$pms_objective_id\",\"$actionplan_id\",this,event);' class='aptext' id='dvap_${pms_objective_id}_${actionplan_id}'>";
                  if($employee_id==$self_employee_id&&($approval_st=="new"||$approval_st=="return")) {
                     $vardvaptxt .= "<span class='xlnk' onclick='edit_actionplan(\"$actionplan_id\",\"$pms_objective_id\",this,event);' id='spap_${pms_objective_id}_${actionplan_id}'>$actionplan_text</span>";
                  } else if($approval1_employee_id==$self_employee_id&&$approval_st=="approval1") {
                     $vardvaptxt .= "<span class='xlnk' onclick='edit_actionplan(\"$actionplan_id\",\"$pms_objective_id\",this,event);' id='spap_${pms_objective_id}_${actionplan_id}'>$actionplan_text</span>";
                  } else {
                     $vardvaptxt .= "<span id='spap_${pms_objective_id}_${actionplan_id}'>$actionplan_text</span>";
                  }
                  $vardvaptxt .= "</div>";
               } else if($report_mode==TRUE) {
                  $vardvaptxt .= "<div onmousemove='show_ap_tooltip(\"$pms_objective_id\",\"$actionplan_id\",this,event);' onmouseover='mouseover_aptext".($is_pica==1?"pica":"")."(\"$no\",\"$pms_objective_id\",\"$actionplan_id\",this,event);' onmouseout='mouseout_aptext".($is_pica==1?"pica":"")."(\"$no\",\"$pms_objective_id\",\"$actionplan_id\",this,event);' class='aptext".($is_pica==1?"pica":"")."' id='dvap_${pms_objective_id}_${actionplan_id}'>";
                  if($employee_id==$self_employee_id&&($approval_st=="new"||$approval_st=="return")) {
                     $vardvaptxt .= "<span class='xlnk' onclick='edit_actionplan(\"$actionplan_id\",\"$pms_objective_id\",this,event);' id='spap_${pms_objective_id}_${actionplan_id}'>$actionplan_text</span>";
                  } else if($approval1_employee_id==$self_employee_id&&$approval_st=="approval1") {
                     $vardvaptxt .= "<span class='xlnk' onclick='edit_actionplan(\"$actionplan_id\",\"$pms_objective_id\",this,event);' id='spap_${pms_objective_id}_${actionplan_id}'>$actionplan_text</span>";
                  } else {
                     $vardvaptxt .= "<span id='spap_${pms_objective_id}_${actionplan_id}'>$actionplan_text</span>";
                  }
                  $vardvaptxt .= "</div>";
               
               }
               $tooltip .= "\n<div style='display:none;' id='intooltip_${pms_objective_id}_${actionplan_id}'>"
                         . "<div>"
                         . "<table class='aptooltip'><tbody>"
                         . ($is_pica==1?"<tr><td style='text-align:center;background-color:#fff;border:1px solid #bbb;' colspan='2'>PICA</td></tr>":"")
                         . "<tr><td>SO : </td><td>$pms_objective_text</td></tr>"
                         . "<tr><td>Month : </td><td>".$xocp_vars["month_year"][$i]."</td></tr>"
                         . "<tr><td>Action Plan : </td><td>$actionplan_text</td></tr>"
                         . "<tr><Td>Target : </td><td>$target_text</td></tr>"
                         . "<tr><Td>Target Ach. : </td><td>".toMoney($target_achievement)." %</td></tr>"
                         . "<tr><Td>Ach. : </td><td>".toMoney($current_achievement)." %</td></tr>"
                         . "</tbody></table>"
                         . "</div>"
                         . "</div>";
               
               
               if($is_pica==0) {
                  $vardvtg .= "<div onmousemove='show_ap_tooltip(\"$pms_objective_id\",\"$actionplan_id\",this,event);' onmouseover='mouseover_aptext(\"$no\",\"$pms_objective_id\",\"$actionplan_id\",this,event);' onmouseout='mouseout_aptext(\"$no\",\"$pms_objective_id\",\"$actionplan_id\",this,event);' class='aptgtext' id='dvtg_${pms_objective_id}_${actionplan_id}'>"
                             . $target_text."&nbsp;"
                             . "</div>";
               } else if($report_mode==TRUE) {
                  $vardvtg .= "<div onmousemove='show_ap_tooltip(\"$pms_objective_id\",\"$actionplan_id\",this,event);' onmouseover='mouseover_aptext".($is_pica==1?"pica":"")."(\"$no\",\"$pms_objective_id\",\"$actionplan_id\",this,event);' onmouseout='mouseout_aptext".($is_pica==1?"pica":"")."(\"$no\",\"$pms_objective_id\",\"$actionplan_id\",this,event);' class='aptgtext".($is_pica==1?"pica":"")."' id='dvtg_${pms_objective_id}_${actionplan_id}'>"
                             . $target_text."&nbsp;"
                             . "</div>";
               }
               $sql = "SELECT root_cause,target_text,month_id FROM pms_pic_action"
                    . " WHERE employee_id = '$employee_id'"
                    . " AND pms_objective_id = '$pms_objective_id'"
                    . " AND actionplan_id = '$pica_id'";
               $rpica = $db->query($sql);
               if($db->getRowsNum($rpica)>0) {
                  list($pica_root_cause,$pica_target_text,$pica_month_id)=$db->fetchRow($rpica);
                  $pica_month_text = $xocp_vars["month_year"][$pica_month_id];
               } else {
                  $pica_root_cause = $pica_target_text = $pica_month_text = "-";
               }
               
               $vardvpica_root .= "<div ".($pica_root_cause=="-"?"style='color:#555;'":"")." onmousemove='show_ap_tooltip(\"$pms_objective_id\",\"$actionplan_id\",this,event);' onmouseover='mouseover_aptext".($is_pica==1?"pica":"")."(\"$no\",\"$pms_objective_id\",\"$actionplan_id\",this,event);' onmouseout='mouseout_aptext".($is_pica==1?"pica":"")."(\"$no\",\"$pms_objective_id\",\"$actionplan_id\",this,event);' class='appicatext".($is_pica==1?"pica":"")."' id='dvpica_root_${pms_objective_id}_${actionplan_id}'>"
                                . htmlentities($pica_root_cause)."&nbsp;"
                                . "</div>";
               $vardvpica_improve .= "<div ".($pica_root_cause=="-"?"style='color:#555;'":"")." onmousemove='show_ap_tooltip(\"$pms_objective_id\",\"$actionplan_id\",this,event);' onmouseover='mouseover_aptext".($is_pica==1?"pica":"")."(\"$no\",\"$pms_objective_id\",\"$actionplan_id\",this,event);' onmouseout='mouseout_aptext".($is_pica==1?"pica":"")."(\"$no\",\"$pms_objective_id\",\"$actionplan_id\",this,event);' class='appicatext".($is_pica==1?"pica":"")."' id='dvpica_improve_${pms_objective_id}_${actionplan_id}'>"
                                   . htmlentities($pica_target_text)."&nbsp;"
                                   . "</div>";
               $vardvpica_month .= "<div ".($pica_root_cause=="-"?"style='color:#555;'":"")." onmousemove='show_ap_tooltip(\"$pms_objective_id\",\"$actionplan_id\",this,event);' onmouseover='mouseover_aptext".($is_pica==1?"pica":"")."(\"$no\",\"$pms_objective_id\",\"$actionplan_id\",this,event);' onmouseout='mouseout_aptext".($is_pica==1?"pica":"")."(\"$no\",\"$pms_objective_id\",\"$actionplan_id\",this,event);' class='appicatext".($is_pica==1?"pica":"")."' id='dvpica_month_${pms_objective_id}_${actionplan_id}'>"
                                . htmlentities($pica_month_text)."&nbsp;"
                                . "</div>";
               
            }
            if($report_mode==FALSE) {
               for($vindent=$current_objective_ap_count;$vindent<$apcnt;$vindent++) {
                  $$vardvap .= "<div onmouseover='mouseover_aptext(\"$vindent\",\"$pms_objective_id\",0,this,event);' onmouseout='mouseout_aptext(\"$vindent\",\"$pms_objective_id\",0,this,event);' class='apblock_empty'>&nbsp;--</div>";
               }
            }
         } else {
            if($report_mode==FALSE) {
               for($vindent=0;$vindent<$apcnt;$vindent++) {
                  $indent = $vindent+1;
                  
                  if(isset($pica_block[$indent][$i])) {
                     _debuglog("here set");
                     _dumpvar($pica_block[$indent][$i]);
                     list($pica_idx)=$pica_block[$indent][$i];
                     $tooltip .= "\n<div style='display:none;' id='intooltip_${pms_objective_id}_${pica_idx}'>"
                               . "<div>"
                               . "<table class='aptooltip'><tbody>"
                               . "<tr><td>SO : </td><td>${pica_pms_perspective_code}${pica_pms_objective_no} $pica_pms_objective_text</td></tr>"
                               . "<tr><td>Month : </td><td>".$xocp_vars["month_year"][$i]."</td></tr>"
                               . "<tr><td>Action Plan : </td><td>$pica_actionplan_text</td></tr>"
                               . "<tr><Td>Target : </td><td>$pica_target_text</td></tr>"
                               . "</tbody></table>"
                               . "</div>"
                               . "</div>";
                     
                     $$vardvap .= "<div"
                                . " onmousemove='show_ap_tooltip(\"$pms_objective_id\",\"$pica_idx\",this,event);'"
                                . " onmouseover='mouseover_aptext(\"$no\",\"$pms_objective_id\",\"$pica_idx\",this,event);'"
                                . " onmouseout='mouseout_aptext(\"$no\",\"$pms_objective_id\",\"$pica_idx\",this,event);'"
                                . " class='apblockpica_${approval_st}'><div>&nbsp;44</div></div>";
                  } else {
                     $$vardvap .= "<div onmouseover='mouseover_aptext(\"$vindent\",\"$pms_objective_id\",0,this,event);' onmouseout='mouseout_aptext(\"$vindent\",\"$pms_objective_id\",0,this,event);' class='apblock_empty'>&nbsp;33</div>";
                  }
               }
            }
         }
      }
      
      $vardvap = "dvapx_".$_SESSION["pms_month"];
      if(trim($$vardvap)=="") {
         $$vardvap = "<div class='apblock_empty'>&nbsp;</div>";
         $vardvaptxt = "<div class='aptext'>&nbsp;</div>";
      }
      
      if($status_approval==0&&$employee_id==$self_employee_id&&$report_mode==FALSE) {
         $vardvaptxt .= "<div id='dvaddap_${pms_objective_id}' style='padding:3px;text-align:right;'>[<span onclick='new_actionplan(\"$pms_objective_id\",this,event);' class='ylnk'>add</span>]</div>";
      }
      
      _dumpvar($pica_block);
      
      return array($pms_objective_id,$vardvaptxt,$vardvtg,$dvapx_1,$dvapx_2,$dvapx_3,$dvapx_4,$dvapx_5,$dvapx_6,$dvapx_7,$dvapx_8,$dvapx_9,$dvapx_10,$dvapx_11,$dvapx_12,$tooltip,$vardvpica_root,$vardvpica_improve,$vardvpica_month);
   }
   
   function app_editActionPlan($args) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $db=&Database::getInstance();
      $user_id = getUserID();
      $employee_id = $args[0];
      $pms_objective_id = $args[1];
      $actionplan_id = $args[2];
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
      
      $sql = "SELECT actionplan_text,target_text,month_id FROM pms_pic_action"
           . " WHERE employee_id = '$employee_id' AND pms_objective_id = '$pms_objective_id' AND actionplan_id = '$actionplan_id'";
      $result = $db->query($sql);
      list($actionplan_text,$target_text,$month_id)=$db->fetchRow($result);
      
      global $xocp_vars;
      $opt_month = "";
      foreach($xocp_vars["month_year"] as $k=>$v) {
         if($k<=0) continue;
         $opt_month .= "<option value='$k' ".($month_id==$k?"selected='1'":"").">$v</option>";
      }
      
      $ret = "<div style='padding:5px;'>"
           . "<table class='xxfrm'><tbody>"
           . "<tr><td>Month : </td><td><select id='selmonth'>$opt_month</select></td></tr>"
           . "<tr><td>Action Plan : </td><td><input type='text' style='width:200px;' id='inp_aptext' value='".addslashes($actionplan_text)."'/></td></tr>"
           . "<tr><td>Target : </td><td><input type='text' style='width:200px;' id='inp_tgtext' value='".addslashes($target_text)."'/></td></tr>"
           . "<tr><td colspan='2'>"
           . "<input type='button' onclick='save_actionplan();' value='"._SAVE."'/>&nbsp;"
           . "<input type='button' onclick='close_actionplan();' value='"._CANCEL."'/>"
           . ($actionplan_id=="new"?"":"&nbsp;&nbsp;<input type='button' onclick='delete_actionplan();' value='"._DELETE."'/>")
           . "</td></tr>"
           . "</tbody></table>"
           . "</div>";
      return $ret;
   }
   
   function app_selectEmployee($args) {
      $_SESSION["pms_employee_id"] = $args[0];
   }
   
   function app_loadSnapshotHistory($args) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $db=&Database::getInstance();
      $user_id = getUserID();
      $pms_objective_id = $args[0];
      $actionplan_id = $args[1];
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
      
      $sql = "SELECT snapshot_dttm,current_achievement FROM pms_pic_action_snapshot"
           . " WHERE employee_id = '$self_employee_id' AND pms_objective_id = '$pms_objective_id' AND actionplan_id = '$actionplan_id'"
           . " ORDER BY snapshot_dttm DESC";
      $result = $db->query($sql);
      $ret = "<div style='text-align:left;'><div style='text-align:center;font-weight:bold;'>Report Snapshot History</div>";
      if($db->getRowsNum($result)>0) {
         $ret .= "<table style='width:100%;' class='xxlist'><tbody>";
         while(list($snapshot_dttm,$current_achievement)=$db->fetchRow($result)) {
            $ret .= "<tr><td>".sql2ind($snapshot_dttm,"date")."</td><td style='text-align:center;'>".toMoney($current_achievement)." %</td></tr>";
         }
         $ret .= "</tbody></table>";
      } else {
         $ret .= "No report snapshot found.<div style='font-style:italic;color:'>You can create report snapshot by clicking button &quot;Create Report Snapshot&quot; at the bottom.</div>";
      }
      $ret .= "</div>";
      return $ret;
   }
   
   function app_createSnapshot($args) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $db=&Database::getInstance();
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
      
      $sql = "SELECT pms_objective_id,actionplan_id,month_id,order_no,actionplan_text,target_text,final_result_text,current_achievement"
           . " FROM pms_pic_action"
           . " WHERE employee_id = '$self_employee_id'"
           . " ORDER BY pms_objective_id, actionplan_id, order_no";
      $result = $db->query($sql);
      $snapshot_dttm = getSQLDate($_SESSION["ach_dttm"],"date");
      if($db->getRowsNum($result)>0) {
         while(list($pms_objective_id,$actionplan_id,$month_id,$order_no,$actionplan_text,$target_text,$final_result_text,$current_achievement)=$db->fetchRow($result)) {
            $sql = "REPLACE INTO pms_pic_action_snapshot (employee_id,snapshot_dttm,pms_objective_id,actionplan_id,month_id,order_no,actionplan_text,target_text,final_result_text,current_achievement,created_user_id)"
                 . " VALUES ('$self_employee_id','$snapshot_dttm','$pms_objective_id','$actionplan_id','$month_id','$order_no','$actionplan_text','$target_text','$final_result_text','$current_achievement','$user_id')";
            $db->query($sql);
         }
      }
   }
   
   
   function app_saveCurrentTargetAchievement($args) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $db=&Database::getInstance();
      $user_id = getUserID();
      $current_achievement = _bctrim(bcadd($args[0],0));
      $pms_objective_id = $args[1];
      $actionplan_id = $args[2];
      
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
      
      $sql = "UPDATE pms_pic_action SET "
           . "current_achievement = '$current_achievement'"
           . " WHERE employee_id = '$self_employee_id' AND pms_objective_id = '$pms_objective_id' AND actionplan_id = '$actionplan_id'";
      $db->query($sql);
      
   }
   
   function app_setCurrentStatusDate($args) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $_SESSION["ach_dttm"] = getSQLDate($args[0]);
   }
   
   function app_saveActionPlan($args) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $db=&Database::getInstance();
      $user_id = getUserID();
      $employee_id = $args[0];
      $ap_text = urldecode($args[1]);
      $tg_text = urldecode($args[2]);
      $actionplan_id = $args[3];
      $pms_objective_id = $args[4];
      $month_id = $args[5];
      
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
      
      if($actionplan_id=="new") {
         $sql = "SELECT MAX(actionplan_id) FROM pms_pic_action"
              . " WHERE employee_id = '$employee_id' AND pms_objective_id = '$pms_objective_id'";
         $result = $db->query($sql);
         if($db->getRowsNum($result)>0) {
            list($new_actionplan_id)=$db->fetchRow($result);
         }
         
         $new_actionplan_id++;
         
         $sql = "SELECT MAX(order_no) FROM pms_pic_action WHERE employee_id = '$employee_id' AND pms_objective_id = '$pms_objective_id'";
         $result = $db->query($sql);
         $order_no = 0;
         if($db->getRowsNum($result)>0) {
            list($order_no)=$db->fetchRow($result);
         }
         
         $order_no++;
         $sql = "INSERT INTO pms_pic_action (pms_objective_id,employee_id,actionplan_id,month_id,created_dttm,created_user_id,order_no)"
              . " VALUES ('$pms_objective_id','$employee_id','$new_actionplan_id','$month_id',now(),'$user_id','$order_no')";
         $db->query($sql);
         
         $actionplan_id = $new_actionplan_id;
         
      }
      
      $sql = "SELECT COUNT(*) FROM pms_pic_action"
           . " WHERE pms_objective_id = '$pms_objective_id'"
           . " AND employee_id = '$employee_id'";
      $rcnt = $db->query($sql);
      list($apcnt)=$db->fetchRow($rcnt);
      
      $sql = "SELECT order_no,month_id FROM pms_pic_action"
           . " WHERE employee_id = '$employee_id' AND pms_objective_id = '$pms_objective_id' AND actionplan_id = '$actionplan_id'";
      $result = $db->query($sql);
      list($order_no,$old_month_id)=$db->fetchRow($result);
      
      $sql = "UPDATE pms_pic_action SET "
           . "actionplan_text = '".addslashes($ap_text)."',"
           . "target_text = '".addslashes($tg_text)."',"
           . "month_id = '$month_id'"
           . " WHERE employee_id = '$employee_id' AND pms_objective_id = '$pms_objective_id' AND actionplan_id = '$actionplan_id'";
      $db->query($sql);
      
      $dvapx_0 = "";
      
      $ret = $this->getActionPlanUpdate($pms_objective_id,$employee_id);
      return $ret;
   }
   
   function app_nextAssessorReturnJAM($args) {
      $db=&Database::getInstance();
      $employee_id = $args[0];
      $return_note = addslashes(urldecode($args[1]));
      $sql = "UPDATE pms_jam SET approval_st = 'return', return_note = '$return_note' WHERE employee_id = '$employee_id'";
      $db->query($sql);
   }
   
   function app_approval2JAM($args) {
      $db=&Database::getInstance();
      $employee_id = $args[0];
      $sql = "UPDATE pms_jam SET approval_st = 'implementation', approval2_dttm = now() WHERE employee_id = '$employee_id'";
      $db->query($sql);
   }
   
   function app_firstAssessorReturnJAM($args) {
      $db=&Database::getInstance();
      $employee_id = $args[0];
      $return_note = addslashes(urldecode($args[1]));
      $sql = "UPDATE pms_jam SET approval_st = 'return', return_note = '$return_note' WHERE employee_id = '$employee_id'";
      $db->query($sql);
   }
   
   function app_approval1JAM($args) {
      $db=&Database::getInstance();
      $employee_id = $args[0];
      $sql = "UPDATE pms_jam SET approval_st = 'approval2', approval1_dttm = now() WHERE employee_id = '$employee_id'";
      $db->query($sql);
   }
   
   function app_submitJAM($args) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $db=&Database::getInstance();
      $user_id = getUserID();
      $employee_id = $args[0];
      list($job_id,
           $employee_idx,
           $job_nm,
           $employee_nm,
           $nip,
           $gender,
           $jobstart,
           $entrance_dttm,
           $jobage,
           $job_summary,
           $person_id,
           $employee_user_id,
           $first_assessor_job_id,
           $next_assessor_job_id)=_hris_getinfobyemployeeid($employee_id);
      
      $sql = "SELECT a.job_nm,b.employee_ext_id,c.person_nm,b.employee_id"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job e USING(job_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " WHERE a.job_id = '$first_assessor_job_id'"
           . " ORDER BY e.start_dttm DESC LIMIT 1";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($first_superior_job,$first_superior_nip,$first_superior_name,$first_superior_employee_id)=$db->fetchRow($result);
      }
      
      $sql = "SELECT a.job_nm,b.employee_ext_id,c.person_nm,b.employee_id"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job e USING(job_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " WHERE a.job_id = '$next_assessor_job_id'"
           . " ORDER BY e.start_dttm DESC LIMIT 1";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($next_superior_job,$next_superior_nip,$next_superior_name,$next_superior_employee_id)=$db->fetchRow($result);
      }
      
      $sql = "UPDATE pms_jam SET submit_dttm = now(), approval_st = 'approval1', approval1_employee_id = '$first_superior_employee_id', approval2_employee_id = '$next_superior_employee_id' WHERE employee_id = '$employee_id'";
      $db->query($sql);
   }
   
   function app_saveJAMTargetText($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $val = addslashes($args[0]);
      $pms_objective_id = $args[1];
      $no = $args[2];
      
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      
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
      if($no==5) {
         $sql = "UPDATE pms_jam SET final_result_text = '$val' WHERE employee_id = '$self_employee_id' AND pms_objective_id = '$pms_objective_id'";
      } else {
         $sql = "UPDATE pms_jam SET target_text${no} = '$val' WHERE employee_id = '$self_employee_id' AND pms_objective_id = '$pms_objective_id'";
      }
      $db->query($sql);
   }
   
   function app_saveJAMTargetWeight($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $val = _bctrim(bcadd($args[0],0));
      $pms_objective_id = $args[1];
      $no = $args[2];
      
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      
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
      if($no==5) {
         $sql = "UPDATE pms_jam SET final_result_weight = '$val' WHERE employee_id = '$self_employee_id' AND pms_objective_id = '$pms_objective_id'";
      } else {
         $sql = "UPDATE pms_jam SET target_weight${no} = '$val' WHERE employee_id = '$self_employee_id' AND pms_objective_id = '$pms_objective_id'";
      }
      $db->query($sql);
   }
   
   function app_importObjectives($args) {
      $db=&Database::getInstance();
      $org_id = $_SESSION["pms_org_id"];
      $sql = "SELECT parent_id FROM ".XOCP_PREFIX."orgs WHERE org_id = '$org_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($src_org_id)=$db->fetchRow($result);
         $this->app_deployObjectives(array($src_org_id,$org_id));
      }
   }
   
   function app_deployObjectives($args) {
      $db=&Database::getInstance();
      $org_id = $args[0];
      $dst_org_id = $args[1];
      $sql = "SELECT a.pms_share_org_id,b.org_abbr,b.org_nm"
           . " FROM pms_org_share a"
           . " LEFT JOIN ".XOCP_PREFIX."orgs b ON b.org_id = a.pms_share_org_id"
           . " WHERE a.pms_org_id = '$org_id'"
           . ($dst_org_id>0?" AND a.pms_share_org_id = '$dst_org_id'":"")
           . " ORDER BY b.order_no";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($pms_share_org_id,$pms_share_org_abbr,$pms_share_org_nm)=$db->fetchRow($result)) {
            //// clean up first here:
            $sql = "SELECT pms_objective_id,pms_objective_weight,pms_parent_objective_id FROM pms_objective WHERE pms_org_id = '$pms_share_org_id' AND pms_parent_objective_id > '0'";
            $rc = $db->query($sql);
            $ttl_clean_weight = 0;
            if($db->getRowsNum($rc)>0) {
               while(list($clean_pms_objective_id,$clean_pms_objective_weight,$clean_pms_parent_objective_id)=$db->fetchRow($rc)) {
                  /// check for local sub
                  $sql = "SELECT pms_org_id FROM pms_objective WHERE pms_objective_id = '$clean_pms_parent_objective_id'";
                  $rp = $db->query($sql);
                  if($db->getRowsNum($rp)>0) {
                     list($clean_pms_parent_org_id)=$db->fetchRow($rp);
                     if($pms_share_org_id==$clean_pms_parent_org_id) continue;
                  }
                  $this->recurseDeleteSO($clean_pms_objective_id);
                  
                  /*
                  $sql = "DELETE FROM pms_objective WHERE pms_objective_id = '$clean_pms_objective_id'";
                  $db->query($sql);
                  $sql = "DELETE FROM pms_kpi WHERE pms_objective_id = '$clean_pms_objective_id'";
                  $db->query($sql);
                  $sql = "DELETE FROM pms_kpi_share WHERE pms_objective_id = '$clean_pms_objective_id'";
                  $db->query($sql);
                  */
                  
                  $ttl_clean_weight += $clean_pms_objective_weight;
               }
            }
            
            $shared_objective = array();
            $share_kpi = array();
            $total_weight = 0;
            $local_weight = 0;
            $sql = "SELECT pms_objective_id,pms_objective_weight FROM pms_objective WHERE pms_org_id = '$pms_share_org_id' AND pms_parent_objective_id = '0'";
            $rc = $db->query($sql);
            if($db->getRowsNum($rc)>0) {
               while(list($local_pms_objective_id,$local_pms_objective_weight)=$db->fetchRow($rc)) {
                  $local_weight = _bctrim(bcadd($local_weight,$local_pms_objective_weight));
               }
            }
            
            $factor = _bctrim(bcsub(100,$local_weight));
            
            /*
               factor = 100 - local weight
               because local weight is already in 100 based scale
            */
            
            //$total_weight = _bctrim(bcadd($total_weight,$local_weight));
            
            $sql = "SELECT a.pms_objective_id,a.pms_kpi_id,a.pms_share_weight,b.pms_perspective_id,"
                 . "c.pms_kpi_text,c.pms_kpi_start,c.pms_kpi_stop,c.pms_kpi_target_text,c.pms_kpi_measurement_unit,"
                 . "b.pms_objective_text,b.pms_objective_start,b.pms_objective_stop"
                 . " FROM pms_kpi_share a"
                 . " LEFT JOIN pms_objective b USING(pms_objective_id)"
                 . " LEFT JOIN pms_kpi c ON c.pms_objective_id = a.pms_objective_id AND c.pms_kpi_id = a.pms_kpi_id"
                 . " WHERE a.pms_org_id = '$org_id' AND a.pms_share_org_id = '$pms_share_org_id'"
                 . " AND b.pms_objective_id IS NOT NULL"
                 . " ORDER BY b.pms_perspective_id,b.pms_objective_no";
            $rkpi = $db->query($sql);
            if($db->getRowsNum($rkpi)>0) {
               while(list($pms_objective_id,$pms_kpi_id,$pms_share_weight,$pms_perspective_id,
                          $pms_kpi_text,$pms_kpi_start,$pms_kpi_stop,$pms_kpi_target_text,$pms_kpi_measurement_unit,
                          $pms_objective_text,$pms_objective_start,$pms_objective_stop)=$db->fetchRow($rkpi)) {
                  
                  $shared_objective[$pms_perspective_id][$pms_objective_id] = array($pms_objective_text,$pms_objective_start,$pms_objective_stop,$pms_perspective_id);
                  $shared_kpi[$pms_objective_id][$pms_kpi_id] = array($pms_share_weight,$pms_kpi_text,$pms_kpi_start,$pms_kpi_stop,$pms_kpi_target_text,$pms_kpi_measurement_unit);
                  $total_weight = _bctrim(bcadd($total_weight,$pms_share_weight));
                  
               }
            }
            
            $new_pms_objective_no = 0;
            $new_total_objective_weight = 0;
            foreach($shared_objective as $pms_perspective_id=>$v0) {
               $new_pms_objective_no = 0;
               foreach($v0 as $pms_objective_id=>$v1) {
                  $new_pms_objective_no++;
                  list($pms_objective_text,$pms_objective_start,$pms_objective_stop)=$v1;
                  $objective_weight = 0;
                  $objective_pic_job_id = 0;
                  $objective_pic_employee_id = 0;
                  $new_objective_start = "9999-12-31 00:00:00";
                  $new_objective_stop = "0000-00-00 00:00:00";
                  if(is_array($shared_kpi)) {
                     foreach($shared_kpi[$pms_objective_id] as $pms_kpi_id=>$v2) {
                        list($pms_share_weight,$pms_kpi_text,$pms_kpi_start,$pms_kpi_stop,$pms_kpi_target_text,$pms_kpi_measurement_unit)=$v2;
                        $objective_weight = _bctrim(bcadd($objective_weight,$pms_share_weight));
                        $new_objective_start = min($pms_kpi_start,$new_objective_start);
                        $new_objective_stop = max($pms_kpi_stop,$new_objective_stop);
                     }
                  }
                  $new_objective_weight = _bctrim(bcmul(bcdiv($objective_weight,$total_weight),$factor));
                  $new_total_objective_weight = _bctrim(bcadd($new_total_objective_weight,$new_objective_weight));
                  //// insert objective here:
                  $sql = "INSERT INTO pms_objective (pms_objective_text,pms_objective_weight,pms_objective_start,pms_objective_stop,pms_perspective_id,pms_org_id,pms_objective_no,pms_parent_objective_id)"
                       . "\nVALUES ('$pms_objective_text','$new_objective_weight','$new_objective_start','$new_objective_stop','$pms_perspective_id','$pms_share_org_id','$new_pms_objective_no','$pms_objective_id')";
                  $db->query($sql);
                  $new_objective_id = $db->getInsertId();
                  
                  $new_kpi_id = 0;
                  //// insert kpi here:
                  if(is_array($shared_kpi)) {
                     foreach($shared_kpi[$pms_objective_id] as $pms_kpi_id=>$v2) {
                        list($pms_share_weight,$pms_kpi_text,$pms_kpi_start,$pms_kpi_stop,$pms_kpi_target_text,$pms_kpi_measurement_unit)=$v2;
                        $new_kpi_share_weight = _bctrim(bcmul(bcdiv($pms_share_weight,$total_weight),100));
                        $new_kpi_id++;
                        $sql = "INSERT INTO pms_kpi (pms_objective_id,pms_kpi_id,pms_kpi_text,pms_kpi_start,pms_kpi_stop,pms_kpi_target_text,pms_kpi_measurement_unit,pms_parent_objective_id,pms_parent_kpi_id)"
                             . " VALUES ('$new_objective_id','$new_kpi_id','$pms_kpi_text','$pms_kpi_start','$pms_kpi_stop','$pms_kpi_target_text','$pms_kpi_measurement_unit','$pms_objective_id','$pms_kpi_id')";
                        $db->query($sql);
                     }
                  }
               }
            }
            $this->resortObjectives($pms_share_org_id);
            
         }
      }
   }
   
   function resortObjectives($org_id) {
      $db=&Database::getInstance();
      //// re-sorting
      $sql0 = "SELECT pms_objective_no,pms_objective_id,pms_perspective_id,'1' as urut"
            . " FROM pms_objective WHERE pms_org_id = '$org_id'"
            . " AND pms_parent_objective_id > '0'";
      $sql1 = "SELECT pms_objective_no,pms_objective_id,pms_perspective_id,'2' as urut"
            . " FROM pms_objective WHERE pms_org_id = '$org_id'"
            . " AND pms_parent_objective_id = '0'";
      $sql = "($sql0) UNION ($sql1)"
           . " ORDER BY pms_perspective_id,urut,pms_objective_no,pms_objective_id";
      $rxx = $db->query($sql);
      if($db->getRowsNum($rxx)>0) {
         $oldp = 0;
         $no = 0;
         while(list($pms_objective_no,$pms_objective_idx,$pms_perspective_idx)=$db->fetchRow($rxx)) {
            if($oldp!=$pms_perspective_idx) {
               $oldp = $pms_perspective_idx;
               $no=0;
            }
            $no++;
            //$sql = "UPDATE pms_objective SET pms_objective_no = '$no' WHERE pms_objective_id = '$pms_objective_idx'";
            //$db->query($sql);
         }
      }
   }
   
   function app_saveCauseEffectRelation($args) {
      $db=&Database::getInstance();
      $checked = $args[0];
      $src_pms_objective_id = $args[1];
      $dst_pms_objective_id = $args[2];
      $org_id = $_SESSION["pms_org_id"];
      if($checked==1) {
         $sql = "REPLACE INTO pms_cause_effect (src_pms_objective_id,dst_pms_objective_id) VALUES ('$src_pms_objective_id','$dst_pms_objective_id')";
      } else {
         $sql = "DELETE FROM pms_cause_effect WHERE src_pms_objective_id = '$src_pms_objective_id' AND dst_pms_objective_id = '$dst_pms_objective_id'";
      }
      $db->query($sql);
      return array($checked,$src_pms_objective_id,$dst_pms_objective_id);
   }
   
   function app_getCauseEffectRelation($args) {
      $db=&Database::getInstance();
      $pms_objective_id = $args[0];
      $org_id = $_SESSION["pms_org_id"];
      
      $sql = "SELECT dst_pms_objective_id FROM pms_cause_effect WHERE src_pms_objective_id = '$pms_objective_id'";
      $result = $db->query($sql);
      $arr_dst = array();
      if($db->getRowsNum($result)>0) {
         while(list($dst_pms_objective_id)=$db->fetchRow($result)) {
            $arr_dst[$dst_pms_objective_id] = 1;
         }
      }
      
      $sql = "SELECT pms_perspective_id FROM pms_objective where pms_objective_id = '$pms_objective_id'";
      $result = $db->query($sql);
      list($pms_perspective_id)=$db->fetchRow($result);
      
      $ret = "<div><div style='border:1px solid #bbb;background-color:#ddd;color:black;font-weight:bold;text-align:center;'>Choose Effect Destinations:</div>";
      
      $sql = "SELECT a.pms_objective_id,a.pms_objective_no,a.pms_objective_text,a.pms_perspective_id,b.pms_perspective_code"
           . " FROM pms_objective a"
           . " LEFT JOIN pms_perspective b USING(pms_perspective_id)"
           . " WHERE a.pms_org_id = '$org_id'"
           . " ORDER BY a.pms_perspective_id,a.pms_objective_no";
      $result = $db->query($sql);
      $arr_p = array();
      $arr_o = array();
      $divo = array();
      if($db->getRowsNum($result)>0) {
         while(list($pms_objective_idx,$pms_objective_nox,$pms_objective_textx,$pms_perspective_idx,$pms_perpective_codex)=$db->fetchRow($result)) {
            $arr_p[$pms_perspective_idx] = $pms_perpective_codex;
            $arr_o[$pms_perspective_idx][$pms_objective_idx] = array($pms_objective_nox,$pms_objective_textx);
         }
      }
      
      $divp = "<div style='background-color:#fff;border:1px solid #bbb;border-top:0;'><table style='width:200px;' align='center'><colgroup><col width='25%'/><col width='25%'/><col width='25%'/><col width='25%'/></colgroup><tbody><tr id='trselper'>";
      $selper = ($pms_perspective_id==1?1:$pms_perspective_id-1);
      foreach($arr_p as $pms_perspective_idx=>$pms_perpective_codex) {
         $divp .= "<td id='tdpers_${pms_perspective_idx}' class='".($selper==$pms_perspective_idx?"selper_selected":"selper")."' onclick='select_perspective_effect(\"$pms_perspective_idx\",this,event);'>$pms_perpective_codex</td>";
         $divo[$pms_perspective_idx] = "<div id='dvpers_${pms_perspective_idx}' style='text-align:left;".($pms_perspective_idx==$selper?"":"display:none;")."'>"
                                     . "<table style='border-spacing:0;'><tbody>";
         
         foreach($arr_o[$pms_perspective_idx] as $pms_objective_idx=>$v) {
            list($pms_objective_nox,$pms_objective_textx)=$v;
            $divo[$pms_perspective_idx] .= "<tr>"
                                         . "<td style=''><input ".($arr_dst[$pms_objective_idx]==1?"checked='1'":"")." onclick='do_ck(\"$pms_objective_id\",\"$pms_objective_idx\",this,event);' type='checkbox' id='ckb_${pms_objective_idx}'/></td>"
                                         . "<td style='font-weight:bold;text-align:center;'>${pms_perpective_codex}${pms_objective_nox}</td>"
                                         . "<td style=''><label for='ckb_${pms_objective_idx}' class='xlnk'>".htmlentities($pms_objective_textx)."</label></td></tr>";
         }
         $divo[$pms_perspective_idx] .= "</tbody></table></div>";
      }
      $divp .= "</tr></tbody></table></div>";
      
      $ret .= $divp;
      
      foreach($divo as $pms_perspective_idx => $v) {
         $ret .= $v;
      }
      
      $ret .= "</div>";
      return $ret;
   }
   
   function app_setSOOrigin($args) {
      $db=&Database::getInstance();
      $pms_parent_objective_id = $args[0];
      $org_id = $_SESSION["pms_org_id"];
      
      
      $sql = "SELECT p.pms_perspective_code,a.pms_objective_no,a.pms_org_id,a.pms_objective_text,b.org_nm,c.org_class_nm"
           . " FROM pms_objective a"
           . " LEFT JOIN ".XOCP_PREFIX."orgs b ON b.org_id = a.pms_org_id"
           . " LEFT JOIN ".XOCP_PREFIX."org_class c USING(org_class_id)"
           . " LEFT JOIN pms_perspective p ON p.pms_perspective_id = a.pms_perspective_id"
           . " WHERE a.pms_objective_id = '$pms_parent_objective_id'";
      $result = $db->query($sql);
      list($pms_perspective_code,$pms_objective_no,$pms_parent_org_id,$pms_parent_objective_text,$pms_parent_org_nm,$pms_parent_org_class_nm)=$db->fetchRow($result);
      
      $sql = "SELECT pms_share_weight FROM pms_kpi_share WHERE pms_org_id = '$pms_parent_org_id' AND pms_share_org_id = '$org_id' AND pms_objective_id = '$pms_parent_objective_id'";
      $result = $db->query($sql);
      $ttlweight = 0;
      if($db->getRowsNum($result)>0) {
         while(list($pms_share_weight)=$db->fetchRow($result)) {
            $ttlweight = _bctrim(bcadd($pms_share_weight,$ttlweight));
         }
      }
      
      $ret = "<table class='xxfrm' style='width:100%;'>"
           . "<colgroup><col width='150'/><col/></colgroup>"
           . "<tbody>"
           . "<tr><td>Organization</td><td>".htmlentities("$pms_parent_org_nm $pms_parent_org_class_nm")."</td></tr>"
           . "<tr><td>Strategic Objective</td><td>".htmlentities("${pms_perspective_code}${pms_objective_no} $pms_parent_objective_text")."</td></tr>"
           . "<tr><td>Weight</td><td>$ttlweight %</td></tr>"
           . "<tr><td colspan='2'>"
           //. "<input type='button' value='Select Source' onclick='change_so_origin(this,event);'/>"
           . "&#160;</td></tr>"
           . "</tbody></table><input type='hidden' name='pms_parent_objective_id' id='pms_parent_objective_id' value='$pms_parent_objective_id'/>";
      return $ret;
   }
   
   function app_saveKPIShare($args) {
      $db=&Database::getInstance();
      $pms_objective_id = $args[0];
      $pms_kpi_id = $args[1];
      $pms_share_org_id = $args[2];
      $pms_org_id = $_SESSION["pms_org_id"];
      $vars = _parseForm($args[3]);
      
      $pms_share_weight = _bctrim(bcadd($vars["pms_share_weight"],0));
      
      if(bccomp($pms_share_weight,0)<=0) {
         $sql = "DELETE FROM pms_kpi_share"
              . " WHERE pms_objective_id = '$pms_objective_id'"
              . " AND pms_kpi_id = '$pms_kpi_id'"
              . " AND pms_org_id = '$pms_org_id'"
              . " AND pms_share_org_id = '$pms_share_org_id'";
      } else {
         $sql = "REPLACE INTO pms_kpi_share (pms_objective_id,pms_kpi_id,pms_org_id,pms_share_org_id,pms_share_weight)"
              . " VALUES ('$pms_objective_id','$pms_kpi_id','$pms_org_id','$pms_share_org_id','$pms_share_weight')";
      }
      $db->query($sql);
      
      return $this->app_calcRemainingShare(array($pms_objective_id,$pms_kpi_id,$pms_share_org_id));
   }
   
   function app_editKPIShare($args) {
      $db=&Database::getInstance();
      $pms_objective_id = $args[0];
      $pms_kpi_id = $args[1];
      $pms_share_org_id = $args[2];
      $org_id = $_SESSION["pms_org_id"];
      
      $btn = "<input type='button' value='"._SAVE."' onclick='save_kpi_share(\"$pms_objective_id\",\"$pms_kpi_id\",\"$pms_share_org_id\",this,event);'/>&#160;&#160;"
           . "<input type='button' value='"._CANCEL."' onclick='editkpisharebox.fade();'/>";
      
      $sql = "SELECT pms_kpi_text,pms_kpi_weight,pms_kpi_start,pms_kpi_stop,pms_kpi_target_text,pms_kpi_measurement_unit,pms_kpi_pic_job_id"
           . " FROM pms_kpi WHERE pms_objective_id = '$pms_objective_id' AND pms_kpi_id = '$pms_kpi_id'";
      $result = $db->query($sql);
      list($pms_kpi_text,$pms_kpi_weight,$tm_start,$tm_stop,$pms_kpi_target_text,$pms_kpi_measurement_unit,$pms_kpi_pic_job_id)=$db->fetchRow($result);
      $sql = "SELECT a.org_abbr,a.org_nm,b.org_class_nm FROM ".XOCP_PREFIX."orgs a"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . " WHERE a.org_id = '$pms_share_org_id'";
      $result = $db->query($sql);
      list($pms_share_org_abbr,$pms_share_org_nm,$pms_share_org_class_nm)=$db->fetchRow($result);
      $sql = "SELECT pms_perspective_id,pms_objective_no,pms_objective_text FROM pms_objective WHERE pms_objective_id = '$pms_objective_id'";
      $result = $db->query($sql);
      list($pms_perspective_id,$pms_objective_no,$pms_objective_text)=$db->fetchRow($result);
      $sql = "SELECT pms_perspective_code,pms_perspective_name FROM pms_perspective WHERE pms_perspective_id = '$pms_perspective_id'";
      $result = $db->query($sql);
      list($pms_perspective_code,$pms_perspective_name)=$db->fetchRow($result);
      
      $sql = "SELECT pms_share_weight FROM pms_kpi_share"
           . " WHERE pms_objective_id = '$pms_objective_id'"
           . " AND pms_kpi_id = '$pms_kpi_id'"
           . " AND pms_org_id = '$org_id'"
           . " AND pms_share_org_id = '$pms_share_org_id'";
      $rw = $db->query($sql);
      if($db->getRowsNum($rw)>0) {
         list($pms_share_weight)=$db->fetchRow($rw);
      } else {
         $pms_share_weight = 0;
      }
      
      $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>"
           . "Edit KPI Share"
           . "</div>"
           . "<div style='padding:5px;background-color:#f2f2f2;color:#555;'>"
              . "<div style='border:1px solid #999;background-color:#fff;padding:4px;' id='frmkpi'>"
                  . "<table style='width:100%;border-spacing:0px;'><tbody><tr><td style='height:135px;'>"
                  
                  . "<div style='max-height:135px;overflow:auto;padding-top:3px;'>"
                  . "<table class='xxfrm' style='width:100%;'><tbody>"
                  . "<tr><td>Perspective</td><td>${pms_perspective_name}</td></tr>"
                  . "<tr><td>Strategic Objective</td><td>${pms_perspective_code}${pms_objective_no} - $pms_objective_text</td></tr>"
                  . "<tr><td>KPI</td><td>$pms_kpi_text</td></tr>"
                  . "<tr><td>Share to</td><td>$pms_share_org_abbr - $pms_share_org_nm $pms_share_org_class_nm</td></tr>"
                  . "<tr><td>Weight</td><td><input id='pms_share_weight' name='pms_share_weight' type='text' style='text-align:center;width:40px;' value='$pms_share_weight' onkeypress='kp_kpi_share(\"$pms_objective_id\",\"$pms_kpi_id\",\"$pms_share_org_id\",this,event);'/> %</td></tr>"
                  . "</tbody></table>"
                  . "</div>"
                  . "<input type='hidden' name='pms_objective_id' id='pms_objective_id' value='$pms_objective_id'/>"
                  . "<input type='hidden' name='pms_kpi_id' id='pms_kpi_id' value='$pms_kpi_id'/>"
                  . "<input type='hidden' name='pms_share_org_id' id='pms_share_org_id' value='$pms_share_org_id'/>"
                  . "</td></tr></tbody></table>"
              . "</div>"
           . "</div>"
           . "<div style='text-align:center;padding:10px;background-color:#f2f2f2;height:100px;' id='frmbtn'>"
           . $btn
           . "</div>";
      
      return $ret;
   }
   
   function app_deleteKPI($args) {
      $db=&Database::getInstance();
      $org_id = $_SESSION["pms_org_id"];
      $pms_objective_id = $args[0];
      $pms_kpi_id = $args[1];
      $sql = "DELETE FROM pms_kpi WHERE pms_objective_id = '$pms_objective_id' AND pms_kpi_id = '$pms_kpi_id'";
      $db->query($sql);
      
   }
   
   function app_saveKPI($args) {
      $db=&Database::getInstance();
      $org_id = $_SESSION["pms_org_id"];
      $vars = _parseForm($args[0]);
      $pms_objective_id = $vars["pms_objective_id"]+0;
      if($vars["pms_kpi_id"]=="new") {
         $sql = "SELECT MAX(pms_kpi_id) FROM pms_kpi WHERE pms_objective_id = '$pms_objective_id'";
         $result = $db->query($sql);
         list($pms_kpi_id)=$db->fetchRow($result);
         $pms_kpi_id++;
         $sql = "INSERT INTO pms_kpi (pms_objective_id,pms_kpi_id) VALUES ('$pms_objective_id','$pms_kpi_id')";
         $db->query($sql);
      } else {
         $pms_kpi_id = $vars["pms_kpi_id"];
      }
      $sql = "UPDATE pms_kpi SET "
           . "pms_kpi_text = '".addslashes($vars["pms_kpi_text"])."',"
           . "pms_kpi_weight = '"._bctrim(bcadd($vars["pms_kpi_weigth"],0))."',"
           . "pms_kpi_start = '".getSQLDate($vars["h_tm_start"])."',"
           . "pms_kpi_stop = '".getSQLDate($vars["h_tm_stop"])."',"
           . "pms_kpi_target_text = '".addslashes($vars["pms_kpi_target_text"])."',"
           . "pms_kpi_measurement_unit = '".addslashes($vars["pms_kpi_measurement_unit"])."',"
           . "pms_kpi_pic_job_id = '".($vars["pms_kpi_pic_job_id"]+0)."'"
           . " WHERE pms_objective_id = '$pms_objective_id' AND pms_kpi_id = '$pms_kpi_id'";
      $db->query($sql);
   }
   
   function app_editKPI($args) {
      $db=&Database::getInstance();
      $pms_objective_id = $args[0];
      $pms_kpi_id = $args[1];
      $org_id = $_SESSION["pms_org_id"];
      if($pms_kpi_id=="new") {
         $title = "Add New KPI";
         $btn = "<input type='button' value='Add New' onclick='save_kpi(\"$pms_objective_id\",\"$pms_kpi_id\",this,event);'/>&#160;&#160;"
              . "<input type='button' value='"._CANCEL."' onclick='editkpibox.fade();'/>";
         $tm_start = getSQLDate();
         $tm_stop = getSQLDate();
      } else {
         $title = "Edit KPI";
         $sql = "SELECT pms_kpi_text,pms_kpi_weight,pms_kpi_start,pms_kpi_stop,pms_kpi_target_text,pms_kpi_measurement_unit,pms_kpi_pic_job_id"
              . " FROM pms_kpi WHERE pms_objective_id = '$pms_objective_id' AND pms_kpi_id = '$pms_kpi_id'";
         $result = $db->query($sql);
         list($pms_kpi_text,$pms_kpi_weight,$tm_start,$tm_stop,$pms_kpi_target_text,$pms_kpi_measurement_unit,$pms_kpi_pic_job_id)=$db->fetchRow($result);
         $btn = "<input type='button' value='"._SAVE."' onclick='save_kpi(\"$pms_objective_id\",\"$pms_kpi_id\",this,event);'/>&#160;&#160;"
              . "<input type='button' value='"._CANCEL."' onclick='editkpibox.fade();'/>&#160;&#160;&#160;"
              . "<input type='button' value='"._DELETE."' onclick='delete_kpi(\"$pms_objective_id\",\"$pms_kpi_id\",this,event);'/>";
      }
      
      $sql = "SELECT a.job_id,a.job_cd,a.job_nm,a.description,"
           . "b.org_nm,c.org_class_nm,a.job_abbr,(d.job_level+0) as srt"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."orgs b USING(org_id)"
           . " LEFT JOIN ".XOCP_PREFIX."org_class c USING(org_class_id)"
           . " LEFT JOIN ".XOCP_PREFIX."job_class d ON d.job_class_id = a.job_class_id"
           . " WHERE a.status_cd = 'normal'"
           . " ORDER BY d.job_class_level, b.org_nm,d.gradeval_bottom DESC,srt DESC,a.job_nm";
      
      $result = $db->query($sql);
      $optpic = "<option value=''></option>";
      if($db->getRowsNum($result)>0) {
         while(list($job_id,$job_cd,$job_nm,$description,$org_nm,$org_class_nm,$job_abbr)=$db->fetchRow($result)) {
            $optpic .= "<option value='$job_id' ".($job_id==$pms_kpi_pic_job_id?"selected='selected'":"").">$job_abbr - $job_nm</option>";
         }
      }
      
      $sql = "SELECT pms_perspective_id,pms_objective_no,pms_objective_text FROM pms_objective WHERE pms_objective_id = '$pms_objective_id'";
      $result = $db->query($sql);
      list($pms_perspective_id,$pms_objective_no,$pms_objective_text)=$db->fetchRow($result);
      $sql = "SELECT pms_perspective_code,pms_perspective_name FROM pms_perspective WHERE pms_perspective_id = '$pms_perspective_id'";
      $result = $db->query($sql);
      list($pms_perspective_code,$pms_perspective_name)=$db->fetchRow($result);
      
      $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>"
           . $title
           . "</div>"
           . "<div style='padding:5px;background-color:#f2f2f2;color:#555;'>"
              . "<div style='border:1px solid #999;background-color:#fff;padding:4px;' id='frmkpi'>"
                  . "<table style='width:100%;border-spacing:0px;'><tbody><tr><td style='height:235px;'>"
                  
                  . "<div style='max-height:235px;overflow:auto;padding-top:3px;'>"
                  . "<table class='xxfrm' style='width:100%;'><tbody>"
                  . "<tr><td>Perspective</td><td>${pms_perspective_name}</td></tr>"
                  . "<tr><td>Strategic Objective</td><td>${pms_perspective_code}${pms_objective_no} - $pms_objective_text</td></tr>"
                  . "<tr><td>KPI</td><td><input type='text' id='pms_kpi_text' name='pms_kpi_text' style='width:400px;' value='$pms_kpi_text'/></td></tr>"
                  . "<tr><td>PIC</td><td><select id='pms_kpi_pic_job_id' name='pms_kpi_pic_job_id'>$optpic</select></td></tr>"
                  . "<tr><td>Target</td><td><input id='pms_kpi_target_text' name='pms_kpi_target_text' type='text' style='width:300px;' value='$pms_kpi_target_text'/></td></tr>"
                  . "<tr><td>Measurement Unit</td><td><input id='pms_kpi_measurement_unit' name='pms_kpi_measurement_unit' type='text' style='width:100px;' value='$pms_kpi_measurement_unit'/></td></tr>"
                  /// . "<tr><td>Weight</td><td><input id='pms_kpi_weight' name='pms_kpi_weight' type='text' style='width:40px;' value='$pms_kpi_weight'/> %</td></tr>"
                  . "<tr><td>Time Frame</td><td>"
                  . "<span class='xlnk' id='sp_tm_start' onclick='_changedatetime(\"sp_tm_start\",\"h_tm_start\",\"date\",true,false);'>".sql2ind($tm_start,"date")."</span>"
                  . "<input type='hidden' name='h_tm_start' id='h_tm_start' value='$tm_start'/>"
                  . " until "
                  . "<span class='xlnk' id='sp_tm_stop' onclick='_changedatetime(\"sp_tm_stop\",\"h_tm_stop\",\"date\",true,false);'>".sql2ind($tm_stop,"date")."</span>"
                  . "<input type='hidden' name='h_tm_stop' id='h_tm_stop' value='$tm_stop'/>"
                  . "</td></tr>"
                  . "</tbody></table>"
                  . "</div>"
                  . "<input type='hidden' name='pms_objective_id' id='pms_objective_id' value='$pms_objective_id'/>"
                  . "<input type='hidden' name='pms_kpi_id' id='pms_kpi_id' value='$pms_kpi_id'/>"
                  . "</td></tr></tbody></table>"
              . "</div>"
           . "</div>"
           . "<div style='text-align:center;padding:10px;background-color:#f2f2f2;height:100px;' id='frmbtn'>"
           . $btn
           . "</div>";
      
      return $ret;
   }
   
   function app_deleteShare($args) {
      $db=&Database::getInstance();
      $pms_share_org_id = $args[0];
      $org_id = $_SESSION["pms_org_id"];
      $sql = "DELETE FROM pms_org_share WHERE pms_org_id = '$org_id' AND pms_share_org_id = '$pms_share_org_id'";
      $db->query($sql);
   }
   
   function app_viewShare($args) {
      $db=&Database::getInstance();
      $pms_share_org_id = $args[0];
      $sql = "SELECT a.org_abbr,a.org_nm,b.org_class_nm"
           . " FROM ".XOCP_PREFIX."orgs a"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . " WHERE a.org_id = '$pms_share_org_id'";
      $result = $db->query($sql);
      list($org_abbr,$org_nm,$org_class_nm)=$db->fetchRow($result);
      $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>"
           . "Share Contribution to"
           . "</div>"
           . "<div style='padding:5px;background-color:#f2f2f2;color:#555;'>"
              . "<div style='border:1px solid #999;background-color:#fff;padding:4px;' id='frmvshare'>"
                  . "<table style='width:100%;border-spacing:0px;'><tbody><tr><td style='height:95px;'>"
                  
                  . "<div style='max-height:95px;overflow:auto;padding-top:3px;'>"
                  . "<table class='xxfrm' style='width:100%;'>"
                  . "<colgroup>"
                  . "<col width='140'/>"
                  . "<col/>"
                  . "</colgroup>"
                  . "<tbody>"
                  . "<tr><td>Organization Name</td><td>$org_nm</td></tr>"
                  . "<tr><td>Abbreviation</td><td>$org_abbr</td></tr>"
                  . "<tr><td>Organization Level</td><td>$org_class_nm</td></tr>"
                  . "<tr><td>Total Contribution</td><td>%</td></tr>"
                  . "</tbody>"
                  . "</table>"
                  . "</div>"
                  . "</td></tr></tbody></table>"
              . "</div>"
           . "</div>"
           . "<div style='text-align:center;padding:10px;background-color:#f2f2f2;height:100px;' id='frmbtn'>"
           . "<input type='button' value='Close' onclick='vsharebox.fade();'/>&#160;&#160;"
           //. "<input type='button' value='"._DELETE."' onclick='delete_share(\"$pms_share_org_id\",this,event);'/>"
           . "</div>";
      
      return $ret;
      
   }
   
   function app_addShare($args) {
      $db=&Database::getInstance();
      $pms_share_org_id = $args[0];
      $pms_org_id = $_SESSION["pms_org_id"];
      
      if($pms_org_id==$pms_org_share_id) return;
      
      $sql = "INSERT INTO pms_org_share (pms_org_id,pms_share_org_id) VALUES ('$pms_org_id','$pms_share_org_id')";
      $db->query($sql);
      
   }
   
   function recurseDeleteSO($pms_objective_id) {
      $db=&Database::getInstance();
      $sql = "SELECT pms_objective_id FROM pms_objective WHERE pms_parent_objective_id = '$pms_objective_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($child_pms_objective_id)=$db->fetchRow($result)) {
            $this->recurseDeleteSO($child_pms_objective_id);
         }
      }
      $sql = "DELETE FROM pms_objective WHERE pms_objective_id = '$pms_objective_id'";
      $db->query($sql);
      $sql = "DELETE FROM pms_kpi WHERE pms_objective_id = '$pms_objective_id'";
      $db->query($sql);
      $sql = "DELETE FROM pms_kpi_share WHERE pms_objective_id = '$pms_objective_id'";
      $db->query($sql);
      $sql = "DELETE FROM pms_pic_action WHERE pms_objective_id = '$pms_objective_id'";
      $db->query($sql);
      $sql = "DELETE FROM pms_jam WHERE pms_objective_id = '$pms_objective_id'";
      $db->query($sql);
   }
   
   function app_deleteSO($args) {
      $db=&Database::getInstance();
      $pms_objective_id = $args[0];
      $this->recurseDeleteSO($pms_objective_id);
   }
   
   
   function app_saveSO($args) {
      $db=&Database::getInstance();
      $org_id = $_SESSION["pms_org_id"];
      $vars = _parseForm($args[0]);
      
      list($pms_perspective_id,$d) = explode("|",$vars["pms_perspective_id"]);
      
      if($vars["pms_objective_id"]=="new") {
         $sql = "INSERT INTO pms_objective (pms_objective_text) VALUES('-')";
         $result = $db->query($sql);
         $pms_objective_id = $db->getInsertId();
         $_SESSION["pms_perspective_last"] = $pms_perspective_id;
      } else {
         $pms_objective_id = $vars["pms_objective_id"];
      }
      
      $sql = "UPDATE pms_objective SET "
           . "pms_org_id = '$org_id',"
           . "pms_parent_objective_id = '".($vars["pms_parent_objective_id"]+0)."',"
           . "pms_perspective_id = '$pms_perspective_id',"
           . "pms_objective_no = '".($vars["pms_objective_no"])."',"
           . "pms_objective_text = '".addslashes($vars["so_txt"])."',"
           . "pms_kpi_text = '".addslashes($vars["kpi_txt"])."',"
           . "pms_target_text = '".addslashes($vars["target_text"])."',"
           . "pms_measurement_unit = '".addslashes($vars["munit"])."',"
           . "pms_objective_weight = '"._bctrim(bcadd($vars["weight"],0))."',"
           . "pms_objective_start = '".getSQLDate($vars["h_tm_start"])."',"
           . "pms_objective_stop = '".getSQLDate($vars["h_tm_stop"])."',"
           . "pms_pic_job_id = '".($vars["pic_job_id"]+0)."'"
           . " WHERE pms_objective_id = '$pms_objective_id'";
      $db->query($sql);
      $this->resortObjectives($org_id);
   }
   
   function app_getNo($args) {
      $db=&Database::getInstance();
      list($pms_perspective_id,$pms_perspective_code) = explode("|",$args[0]);
      $org_id = $_SESSION["pms_org_id"];
      $sql = "SELECT MAX(pms_objective_no) FROM pms_objective"
           . " WHERE pms_perspective_id = '$pms_perspective_id'"
           . " AND pms_org_id = '$org_id'";
      $result = $db->query($sql);
      list($pms_objective_no)=$db->fetchRow($result);
      $pms_objective_no++;
      return array($pms_objective_no);
   }
   
   function app_editSO($args) {
      $db=&Database::getInstance();
      $pms_objective_id = $args[0];
      $org_id = $_SESSION["pms_org_id"];
      
      $sql = "SELECT a.org_nm,b.org_class_nm FROM ".XOCP_PREFIX."orgs a LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id) WHERE a.org_id = '$org_id'";
      $result = $db->query($sql);
      list($org_nm,$org_class_nm)=$db->fetchRow($result);
      
      if(substr($pms_objective_id,0,3)=="new") {
         list($new,$perspective_idx)=explode("_",$pms_objective_id);
         $title = "Add New Strategic Objective";
         $pms_objective_id = $new;
         
         if($perspective_idx!="") {
            $pms_perspective_id = $perspective_idx;
         } else {
            $pms_perspective_id = $_SESSION["pms_perspective_last"];
         }
         if($pms_perspective_id==0) $pms_perspective_id = 1;
         
         $sql = "SELECT MAX(pms_objective_no) FROM pms_objective"
              . " WHERE pms_perspective_id = '$pms_perspective_id'"
              . " AND pms_org_id = '$org_id'";
         $result = $db->query($sql);
         list($pms_objective_no)=$db->fetchRow($result);
         list($first_part_no)=explode(".",$pms_objective_no);
         $pms_objective_no = $first_part_no+1;
         
         $btn = "<input type='button' value='Add New' onclick='save_so(\"$pms_objective_id\",this,event);'/>&#160;&#160;"
              . "<input type='button' value='"._CANCEL."' onclick='editsobox.fade();'/>";
         $btn_sub = "&nbsp;";
         $tm_start = getSQLDate();
         $tm_stop = getSQLDate();
         $sql = "SELECT pms_perspective_code,pms_perspective_id,pms_perspective_name FROM pms_perspective ORDER BY pms_perspective_id";
         $result = $db->query($sql);
         $optpers = "";
         if($db->getRowsNum($result)>0) {
            while(list($pms_perspective_codex,$pms_perspective_idx,$pms_perspective_name)=$db->fetchRow($result)) {
               $optpers .= "<option value='$pms_perspective_idx|$pms_perspective_codex' ".($pms_perspective_idx==$pms_perspective_id?"selected='selected'":"").">$pms_perspective_name</option>";
            }
         }
         $sel_pers = "<tr><td>Perspective</td><td><select id='pms_perspective_id' name='pms_perspective_id' onchange='chgpers(this,event);'>$optpers</select>&#160;"
                   . "No : <input disabled='1' name='pms_objective_no' id='pms_objective_no' type='text' style='width:40px;text-align:center;' value='$pms_objective_no'/>"
                   . "</td></tr>";
         $pms_parent_objective_id = 0;
         
         $source_so = "Strategic Objective Source:"
                    . "<div id='parent_so'>"
                    . "<div style='text-align:center;padding:20px;border:1px solid #bbb;-moz-border-radius:5px;'>"
                    . "<span style='font-style:italic;'>This objective's scope will be top level or local.</span><br/>"
                    . "<input type='hidden' name='pms_parent_objective_id' id='pms_parent_objective_id' value='0'/>"
                    . "</div>"
                    . "</div>";
         $initiative_btn = "&#160;";
      } else {
         $sql = "SELECT pms_parent_objective_id,pms_objective_no,pms_objective_text,pms_kpi_text,pms_target_text,pms_measurement_unit,pms_objective_weight,pms_objective_start,pms_objective_stop,pms_perspective_id,pms_pic_job_id"
              . " FROM pms_objective WHERE pms_objective_id = '$pms_objective_id'";
         $result = $db->query($sql);
         list($pms_parent_objective_id,$pms_objective_no,$pms_objective_text,$pms_kpi_text,$pms_target_text,$pms_measurement_unit,$pms_objective_weight,$tm_start,$tm_stop,$pms_perspective_id,$pms_pic_job_id)=$db->fetchRow($result);
         $sql = "SELECT pms_perspective_code,pms_perspective_name FROM pms_perspective WHERE pms_perspective_id = '$pms_perspective_id'";
         $result = $db->query($sql);
         list($pms_perspective_code,$pms_perspective_name)=$db->fetchRow($result);
         
         $title = "Edit Strategic Objective";
         $initiative_btn = ""; //<input type='button' value='Create Initiative' onclick='add_initiative(\"$pms_objective_id\",this,event);'/>";
         $btn = "<input type='button' value='"._SAVE."' onclick='save_so(\"$pms_objective_id\",this,event);'/>&#160;&#160;"
              . "<input type='button' value='"._CANCEL."' onclick='editsobox.fade();'/>&#160;&#160;&#160;"
              . "<input type='button' value='"._DELETE."' onclick='delete_so(\"$pms_objective_id\",this,event);'/>";
         $btn_sub = "<input type='button' value='Add Initiative' onclick='add_sub(\"$pms_objective_id\",this,event);'/>";
         /*
         $sel_pers = "<tr><td>Perspective</td><td>$pms_perspective_name"
                   . "<input type='hidden' name='pms_perspective_id' value='$pms_perspective_id'/>"
                   . "<input type='hidden' name='pms_objective_no' value='$pms_objective_no'/>"
                   . "</td></tr>";
         */
         
         $sql = "SELECT pms_perspective_code,pms_perspective_id,pms_perspective_name FROM pms_perspective ORDER BY pms_perspective_id";
         $result = $db->query($sql);
         $optpers = "";
         if($db->getRowsNum($result)>0) {
            while(list($pms_perspective_codex,$pms_perspective_idx,$pms_perspective_name)=$db->fetchRow($result)) {
               $optpers .= "<option value='$pms_perspective_idx|$pms_perspective_codex' ".($pms_perspective_idx==$pms_perspective_id?"selected='selected'":"").">$pms_perspective_name</option>";
            }
         }
         $sel_pers = "<tr><td>Perspective</td><td><select id='pms_perspective_id' name='pms_perspective_id' onchange='chgpers(this,event);'>$optpers</select>&#160;"
                   . "No : <input disabled='1' name='pms_objective_no' id='pms_objective_no' type='text' style='width:40px;text-align:center;' value='$pms_objective_no'/>"
                   . "</td></tr>";
         
         //// source objective
         $sql = "SELECT p.pms_perspective_code,a.pms_objective_no,a.pms_org_id,a.pms_objective_text,b.org_nm,c.org_class_nm"
              . " FROM pms_objective a"
              . " LEFT JOIN ".XOCP_PREFIX."orgs b ON b.org_id = a.pms_org_id"
              . " LEFT JOIN ".XOCP_PREFIX."org_class c USING(org_class_id)"
              . " LEFT JOIN pms_perspective p ON p.pms_perspective_id = a.pms_perspective_id"
              . " WHERE a.pms_objective_id = '$pms_parent_objective_id'";
         $result = $db->query($sql);
         list($pms_parent_perspective_code,$pms_parent_objective_no,$pms_parent_org_idxxx,$pms_parent_objective_text,$pms_parent_org_nm,$pms_parent_org_class_nm)=$db->fetchRow($result);
         
         $sql = "SELECT pms_share_weight FROM pms_kpi_share WHERE pms_org_id = '$pms_parent_org_idxxx' AND pms_share_org_id = '$org_id' AND pms_objective_id = '$pms_parent_objective_id'";
         $result = $db->query($sql);
         $source_so_ttlweight = 0;
         if($db->getRowsNum($result)>0) {
            while(list($pms_parent_share_weight)=$db->fetchRow($result)) {
               $source_so_ttlweight = _bctrim(bcadd($pms_parent_share_weight,$source_so_ttlweight));
            }
         } else {
            $sql = "SELECT pms_objective_weight FROM pms_objective WHERE pms_objective_id = '$pms_parent_objective_id'";
            $result = $db->query($sql);
            list($source_so_ttlweight)=$db->fetchRow($result);
         }
         
         $sql = "SELECT a.pms_objective_id,a.pms_org_id,a.pms_objective_weight"
              . " FROM pms_objective a"
              . " LEFT JOIN pms_objective b ON b.pms_objective_id = a.pms_parent_objective_id"
              . " WHERE a.pms_parent_objective_id = '$pms_parent_objective_id'"
              . " AND b.pms_org_id = '$org_id'";
         $rchild = $db->query($sql);
         $has_local_sub = 0;
         $ttl_sub_weight = 0;
         if($db->getRowsNum($rchild)>0) {
            while(list($sub_pms_objective_id,$sub_pms_org_id,$sub_weight)=$db->fetchRow($rchild)) {
               $has_local_sub++;
               $ttl_sub_weight = _bctrim(bcadd($ttl_sub_weight,$sub_weight));
            }
         }
         
         if($pms_parent_objective_id>0) {
            $source_so = "Strategic Objective Source:"
                       . "<div id='parent_so'>"
                       . "<table class='xxfrm' style='width:100%;'>"
                       . "<colgroup><col width='150'/><col/></colgroup>"
                       . "<tbody>"
                       . "<tr><td>Organization</td><td>".htmlentities("$pms_parent_org_nm $pms_parent_org_class_nm")."</td></tr>"
                       . "<tr><td>Strategic Objective</td><td>".htmlentities("${pms_parent_perspective_code}${pms_parent_objective_no} $pms_parent_objective_text")."</td></tr>"
                       . "<tr><td>Weight</td><td>".toMoney($source_so_ttlweight)." %"
                       . ($has_local_sub>0?" <span style='color:blue;'>( ".toMoney(bcsub($source_so_ttlweight,$ttl_sub_weight))." % remaining )</span>":"")
                       . "</td></tr>"
                       ///. "<tr><td colspan='2'><input type='button' value='Select Source' onclick='change_so_origin(this,event);'/></td></tr>"
                       . "</tbody></table>"
                       . "<input type='hidden' name='pms_parent_objective_id' id='pms_parent_objective_id' value='$pms_parent_objective_id'/>"
                       . "</div>";
         } else {
            $source_so = "Strategic Objective Source:"
                       . "<div id='parent_so'>"
                       . "<div style='text-align:center;padding:20px;border:1px solid #bbb;-moz-border-radius:5px;'>"
                       . "<span style='font-style:italic;'>This objective's scope is top level or local.</span><br/>"
                       . "<input type='hidden' name='pms_parent_objective_id' id='pms_parent_objective_id' value='0'/>"
                       . "</div>"
                       . "</div>";
         }
         $sql = "SELECT pms_share_weight FROM pms_kpi_share WHERE pms_org_id = '$pms_parent_org_id' AND pms_share_org_id = '$org_id'";
         $result = $db->query($sql);
         $ttlweight = 0;
         if($db->getRowsNum($result)>0) {
            while(list($pms_share_weight)=$db->fetchRow($result)) {
               $ttlweight = _bctrim(bcadd($pms_share_weight,$ttlweight));
            }
         }
      
         
      }
      
      $sql = "SELECT pms_parent_objective_id FROM pms_objective WHERE pms_org_id = '$org_id'";
      $result = $db->query($sql);
      $arr_source_so = array();
      if($db->getRowsNum($result)>0) {
         while(list($pms_parent_objective_idx)=$db->fetchRow($result)) {
            $arr_source_so[$pms_parent_objective_idx] = 1;
         }
      }
      
      ///// get shared objective from other units
      $selso = "<div style='text-align:center;padding:10px;margin-bottom:5px;border:1px solid #bbb;-moz-border-radius:5px;background-color:#ffffcc;font-style:italic;'>Please click 'Source' button to source Strategic Objective.</div><table class='xxlist'>"
             . "<thead><tr>"
             . "<td>From</td>"
             . "<td>Strategic Objective</td>"
             . "<td style='text-align:center;'>Weight</td>"
             . "<td style='text-align:center;'>Status</td>"
             . "</tr></thead><tbody>";
      
      $sql = "SELECT a.pms_org_id,a.pms_objective_id,a.pms_kpi_id,SUM(a.pms_share_weight),"
           . "b.org_nm,b2.org_class_nm,c.pms_objective_text,c.pms_objective_no,"
           . "p.pms_perspective_code,p.pms_perspective_name,d.pms_kpi_text,c.pms_perspective_id"
           . " FROM pms_kpi_share a"
           . " LEFT JOIN ".XOCP_PREFIX."orgs b ON b.org_id = a.pms_org_id"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b2 USING(org_class_id)"
           . " LEFT JOIN pms_objective c ON c.pms_objective_id = a.pms_objective_id"
           . " LEFT JOIN pms_perspective p ON p.pms_perspective_id = c.pms_perspective_id"
           . " LEFT JOIN pms_kpi d ON d.pms_kpi_id = a.pms_kpi_id AND d.pms_objective_id = a.pms_objective_id"
           . " WHERE a.pms_share_org_id = '$org_id'"
           . " AND c.pms_objective_id IS NOT NULL"
           . " GROUP BY a.pms_objective_id"
           . " ORDER BY a.pms_objective_id";
      
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($shared_pms_org_id,$shared_pms_objective_id,$shared_pms_kpi_id,$shared_pms_share_weight,
                    $shared_org_nm,$shared_org_class_nm,$shared_pms_objective_text,$shared_pms_objective_no,
                    $shared_pms_perspective_code,$shared_pms_perspective_name,$shared_pms_kpi_text,$shared_pms_perspective_id)=$db->fetchRow($result)) {
            
            if(isset($arr_source_so[$shared_pms_objective_id])&&$arr_source_so[$shared_pms_objective_id]==1) {
               $btnstatus = "Sourced";
            } else {
               $btnstatus = "<input type='button' value='Source' onclick='set_so_origin(\"$shared_pms_objective_id\",this,event);'/>";
            }
            $selso .= "<tr>"
                    . "<td>".htmlentities("$shared_org_nm $shared_org_class_nm")."</td>"
                    . "<td>".htmlentities("${shared_pms_perspective_code}${shared_pms_objective_no} - $shared_pms_objective_text")."</td>"
                    . "<td style='text-align:center;'>$shared_pms_share_weight</td>"
                    . "<td style='text-align:center;'>$btnstatus</td>"
                    . "</tr>";
         }
      } else {
         $selso .= "<tr><td colspan='6' style='text-align:center;font-style:italic;color:#888;'>No shared strategic objective found.</td></tr>";
      }
      $selso .= "<tr><td colspan='6' style='text-align:center;'><input type='button' value='"._CANCEL."' onclick='cancel_change_origin(this,event);'/></td></tr>"
              . "</tbody>"
              . "</table>";
      
      $sql = "SELECT a.job_id,a.job_cd,a.job_nm,a.description,"
           . "b.org_nm,c.org_class_nm,a.job_abbr,(d.job_level+0) as srt"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."orgs b USING(org_id)"
           . " LEFT JOIN ".XOCP_PREFIX."org_class c USING(org_class_id)"
           . " LEFT JOIN ".XOCP_PREFIX."job_class d ON d.job_class_id = a.job_class_id"
           . " WHERE a.status_cd = 'normal'"
           . " ORDER BY d.job_class_level, b.org_nm,d.gradeval_bottom DESC,srt DESC,a.job_nm";
      
      $result = $db->query($sql);
      $optpic = "<option value=''></option>";
      if($db->getRowsNum($result)>0) {
         while(list($job_id,$job_cd,$job_nm,$description,$org_nmx,$org_class_nmx,$job_abbr)=$db->fetchRow($result)) {
            $optpic .= "<option value='$job_id' ".($job_id==$pms_pic_job_id?"selected='selected'":"").">".htmlentities("$job_abbr - $job_nm")."</option>";
         }
      }
      
      
      $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>"
           . $title
           . "</div>"
           
           . "<div style='padding:5px;background-color:#f2f2f2;color:#555;'>"
           
           
              . "<div style='border:1px solid #999;background-color:#fff;padding:4px;' id='frmobjective'>"
                  
                  
                  . "<table style='width:100%;border-spacing:0px;'><tbody><tr><td style='height:275px;'>"
                  
                  . "<div style='max-height:375px;overflow:auto;padding-top:3px;'>"
                  
                  . "<div style='display:none;' id='origin_chooser'>"
                     . $selso
                  . "</div>"
                  
                  . "<div id='so_editor'>"
                  
                  . $source_so
                  
                  . "<br/>"
                  . "Strategic Objective:"
                  . "<table class='xxfrm' style='width:100%;'>"
                  . "<colgroup><col width='150'/><col/></colgroup>"
                  . "<tbody>"
                  . "<tr><td>Organization</td><td>$org_nm $org_class_nm</td></tr>"
                  . "<tr><td>ID</td><td id='pms_obj_code'>${pms_perspective_code}${pms_objective_no}</td></tr>"
                  
                  
                  . $sel_pers
                  
                  . "<tr><td>Strategic Objective</td><td><input type='text' id='so_txt' name='so_txt' style='width:400px;' value='".htmlentities($pms_objective_text)."'/></td></tr>"
                  
                  . "<tr><td>PIC</td><td><select id='pic_job_id' name='pic_job_id'>$optpic</select></td></tr>"
                  
                  . "<tr><td>Weight</td><td><input id='weight' name='weight' type='text' style='width:40px;' value='$pms_objective_weight'/> %</td></tr>"
                  . "<tr><td>Time Frame</td><td>"
                  . "<span class='xlnk' id='sp_tm_start' onclick='_changedatetime(\"sp_tm_start\",\"h_tm_start\",\"date\",true,false);'>".sql2ind($tm_start,"date")."</span>"
                  . "<input type='hidden' name='h_tm_start' id='h_tm_start' value='$tm_start'/>"
                  . " until "
                  . "<span class='xlnk' id='sp_tm_stop' onclick='_changedatetime(\"sp_tm_stop\",\"h_tm_stop\",\"date\",true,false);'>".sql2ind($tm_stop,"date")."</span>"
                  . "<input type='hidden' name='h_tm_stop' id='h_tm_stop' value='$tm_stop'/>"
                  . "</td></tr>"
                  . "</tbody></table>"
                  
                  . "</div>" /// so_editor
                  
                  . "</div>"
                  . "<input type='hidden' name='pms_objective_id' id='pms_objective_id' value='$pms_objective_id'/>"
                  . "</td></tr></tbody></table>"
                  
                  
                  
              . "</div>"
           . "</div>"
           
           
           
           . "<div style='text-align:center;padding:10px;background-color:#f2f2f2;height:100px;' id='frmbtn'>"
           
           
           
           . "<div id='vbtn'>"
           . "<table style='width:100%;border-spacing:0px;'>"
           . "<tbody>"
           . "<tr>"
           . "<td style='text-align:left;'>$btn_sub</td>"
           . "<td style='text-align:right;'>$btn</td>"
           . "</tr>"
           . "</tbody>"
           . "</table>"
           . "</div>"
           
           
           . "</div>";
      
      return $ret;
   }
   
   function app_selectOrg($args) {
      $db=&Database::getInstance();
      $org_id = $args[0];
      $_SESSION["pms_org_id"] = $org_id;
      $sql = "SELECT a.org_nm,b.org_class_nm FROM ".XOCP_PREFIX."orgs a LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . " WHERE org_id = '$org_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($org_nm,$org_class_nm)=$db->fetchRow($result);
      }
      
      return "$org_class_nm : $org_nm";
   }
   
   function recurseRenderOrg($org_id,$last=0) {
      $db=&Database::getInstance();
      $sql = "SELECT a.org_nm,b.org_class_nm FROM ".XOCP_PREFIX."orgs a LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . " WHERE org_id = '$org_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($org_nm,$org_class_nm)=$db->fetchRow($result);
         
         $ret = "<div style='margin-top:-3px;".($last<=1?"":"border-left:1px solid #bbb;")."'>"
              . "<div style='".($last==1?"border-left:1px solid #bbb;":"")."'>"
              . "<table style=''><tbody><tr><td class='orgbox' onclick='do_select_org(\"$org_id\",this,event);'><div style='padding:5px;border:1px solid #bbb;'>".htmlentities("$org_nm $org_class_nm")."</div></td></tr></tbody></table>"
              . "</div>"
              . "<div style=''>";
         
         $sql = "SELECT org_id FROM ".XOCP_PREFIX."orgs WHERE parent_id = '$org_id' ORDER BY order_no";
         $res = $db->query($sql);
         $cnt = $db->getRowsNum($res);
         
         if($cnt>0) {
            $no=0;
            while(list($org_idx)=$db->fetchRow($res)) {
               $ret .= "<div style='padding-left:15px;'><div style='padding-left:20px;'>";
               $ret .= $this->recurseRenderOrg($org_idx,$cnt-$no);
               $ret .= "</div></div>";
               $no++;
            }
         }
         $ret .= "</div></div>";
      }
      return $ret;
   }
   
   function app_browseOrgs($args) {
      $db=&Database::getInstance();
      $sql = "";
      
      $org = $this->recurseRenderOrg(1);
      
      $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>"
           . "Select Organization"
           . "</div>"
           . "<div style='padding:5px;background-color:#f2f2f2;color:#555;'>"
              . "<div style='border:1px solid #999;background-color:#fff;padding:4px;' id='frmorg'>"
                  . "<table style='width:100%;border-spacing:0px;'><tbody><tr><td style='height:365px;'>"
                  
                  . "<div style='max-height:365px;overflow:auto;padding-top:3px;'>"
                  . $org
                  . "</div>"
                  . "</td></tr></tbody></table>"
              . "</div>"
           . "</div>"
           . "<div style='text-align:center;padding:10px;background-color:#f2f2f2;height:100px;' id='frmbtn'>"
           . "<input type='button' value='"._CANCEL."' onclick='slorgbox.fade();'/>"
           . "</div>";
      
      return $ret;
      
   }
   
   function recurseRenderOrgShare($org_id,$last=0) {
      $db=&Database::getInstance();
      $sql = "SELECT a.org_nm,b.org_class_nm FROM ".XOCP_PREFIX."orgs a LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . " WHERE org_id = '$org_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($org_nm,$org_class_nm)=$db->fetchRow($result);
         
         $ret = "<div style='margin-top:-3px;".($last<=1?"":"border-left:1px solid #bbb;")."'>"
              . "<div style='".($last==1?"border-left:1px solid #bbb;":"")."'>"
              . "<table style=''><tbody><tr><td class='orgbox' onclick='do_select_org_share(\"$org_id\",this,event);'><div style='padding:5px;border:1px solid #bbb;'>$org_nm $org_class_nm</div></td></tr></tbody></table>"
              . "</div>"
              . "<div style=''>";
         
         $sql = "SELECT org_id FROM ".XOCP_PREFIX."orgs WHERE parent_id = '$org_id' ORDER BY order_no";
         $res = $db->query($sql);
         $cnt = $db->getRowsNum($res);
         
         if($cnt>0) {
            $no=0;
            while(list($org_idx)=$db->fetchRow($res)) {
               $ret .= "<div style='padding-left:15px;'><div style='padding-left:20px;'>";
               $ret .= $this->recurseRenderOrgShare($org_idx,$cnt-$no);
               $ret .= "</div></div>";
               $no++;
            }
         }
         $ret .= "</div></div>";
      }
      return $ret;
   }
   
   function app_browseOrgShare($args) {
      $db=&Database::getInstance();
      $sql = "";
      
      $org = $this->recurseRenderOrgShare(1);
      
      $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>"
           . "Select Organization to Share With"
           . "</div>"
           . "<div style='padding:5px;background-color:#f2f2f2;color:#555;'>"
              . "<div style='border:1px solid #999;background-color:#fff;padding:4px;' id='frmorgshare'>"
                  . "<table style='width:100%;border-spacing:0px;'><tbody><tr><td style='height:365px;'>"
                  
                  . "<div style='max-height:365px;overflow:auto;padding-top:3px;'>"
                  . $org
                  . "</div>"
                  . "</td></tr></tbody></table>"
              . "</div>"
           . "</div>"
           . "<div style='text-align:center;padding:10px;background-color:#f2f2f2;height:100px;' id='frmbtn'>"
           . "<input type='button' value='"._CANCEL."' onclick='slorgsharebox.fade();'/>"
           . "</div>";
      
      return $ret;
      
   }
   
}

} /// HRIS_OBJECTIVEAJAX_DEFINED
?>