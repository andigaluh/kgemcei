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

class _antrain_class_ObjectiveAjax extends AjaxListener {
   
   function _antrain_class_ObjectiveAjax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/antrain/class/ajax_objective.php";
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
                                             "app_submitActionPlan","app_approval1antrainActionPlan",
                                             "app_firstAssessorReturnantrainActionPlan","app_setantrainMonth",
                                             "app_editAchievement","app_setAchievement","app_addSub",
                                             "app_recalculate","app_savePICA","app_submitActionPlanReport",
                                             "app_firstAssessorReturnantrainActionPlanReport","app_approvalantrainActionPlanReport",
                                             "app_calcRemainingShare","app_selectDashboardOrg","app_editDashboard",
                                             "app_saveDashboard","app_setRadarMonth","app_setRadarYTD",
                                             "app_saveFinalAchievement","app_SOWeight","app_SOWeightRemaining",
                                             "app_checkDeployObjectives");
   }
   
   function app_checkDeployObjectives($args) {
      $db=&Database::getInstance();
      $org_id = $_SESSION["antrain_org_id"];
      $psid = $_SESSION["antrain_psid"];
      list($x0,$x1,$x2,$comp)=$this->calcTotalShared();
      switch($comp) {
         case 0:
            $ret = "<div style='padding:5px;'>Share is OK. Are you sure you want to deploy these objectives?</div>"
                 . "<div style='padding:10px;padding-bottom:15px;;color:red;'>This action will delete sub objectives and their KPI.</div>"
                 . "<div id='deploy_btn'><input style='width:80px;' type='button' value='Yes (deploy)' onclick='do_deploy_objectives();'/>"
                 . "&nbsp;<input style='width:80px;' type='button' value='No' onclick='confirmdeploybox.fade();'/></div>";
            break;
         case 1:
            $ret = "<div style='padding:15px;'>Total weight is more than total shared. You cannot deploy these objectives.</div>"
                 . "<div id='deploy_btn'>"
                 . "<input style='width:80px;' type='button' value='Ok' onclick='confirmdeploybox.fade();'/></div>";
            break;
         case -1:
            $ret = "<div style='padding:15px;'>Total weight is less than total shared. You cannot deploy these objectives.</div>"
                 . "<div id='deploy_btn'>"
                 . "<input style='width:80px;' type='button' value='Ok' onclick='confirmdeploybox.fade();'/></div>";
            break;
      }
      return $ret;
   }
   
   function app_SOWeightRemaining($args) {
      $db=&Database::getInstance();
      $org_id = $_SESSION["antrain_org_id"];
      $psid = $_SESSION["antrain_psid"];
      $antrain_objective_id = $args[0];
      $has_local_sub = 0;
      $ttl_sub_weight = 0;
      
      if($antrain_objective_id=="new") {
         $antrain_parent_objective_id = $args[1];
         if($antrain_parent_objective_id>0) {
            $has_local_sub++;
         }
      } else {
         $sql = "SELECT a.antrain_parent_objective_id,b.antrain_org_id"
              . " FROM antrain_objective a"
              . " LEFT JOIN antrain_objective b ON b.antrain_objective_id = a.antrain_parent_objective_id AND b.psid = a.psid"
              . " WHERE a.psid = '$psid' AND a.antrain_objective_id = '$antrain_objective_id'";
         $result = $db->query($sql);
         list($antrain_parent_objective_id,$parent_antrain_org_id)=$db->fetchRow($result);
         if($org_id==$parent_antrain_org_id) {
            $has_local_sub++;
         }
      }
      
      
      
      //// source objective
      $sql = "SELECT p.antrain_perspective_code,a.antrain_objective_no,a.antrain_org_id,a.antrain_objective_text,b.org_nm,c.org_class_nm"
           . " FROM antrain_objective a"
           . " LEFT JOIN ".XOCP_PREFIX."orgs b ON b.org_id = a.antrain_org_id"
           . " LEFT JOIN ".XOCP_PREFIX."org_class c USING(org_class_id)"
           . " LEFT JOIN antrain_perspective p ON p.antrain_perspective_id = a.antrain_perspective_id"
           . " WHERE a.psid = '$psid' AND a.antrain_objective_id = '$antrain_parent_objective_id'";
      $result = $db->query($sql);
      list($antrain_parent_perspective_code,$antrain_parent_objective_no,$antrain_parent_org_idxxx,$antrain_parent_objective_text,$antrain_parent_org_nm,$antrain_parent_org_class_nm)=$db->fetchRow($result);
      
      $sql = "SELECT antrain_share_weight FROM antrain_kpi_share WHERE psid = '$psid' AND antrain_org_id = '$antrain_parent_org_idxxx' AND antrain_share_org_id = '$org_id' AND antrain_objective_id = '$antrain_parent_objective_id'";
      $result = $db->query($sql);
      $source_so_ttlweight = 0;
      if($db->getRowsNum($result)>0) {
         while(list($antrain_parent_share_weight)=$db->fetchRow($result)) {
            $source_so_ttlweight = _bctrim(bcadd($antrain_parent_share_weight,$source_so_ttlweight));
         }
      } else {
         $sql = "SELECT antrain_objective_weight FROM antrain_objective WHERE psid = '$psid' AND antrain_objective_id = '$antrain_parent_objective_id'";
         $result = $db->query($sql);
         list($source_so_ttlweight)=$db->fetchRow($result);
      }
      
      $sql = "SELECT a.antrain_objective_id,a.antrain_org_id,a.antrain_objective_weight"
           . " FROM antrain_objective a"
           . " LEFT JOIN antrain_objective b ON b.antrain_objective_id = a.antrain_parent_objective_id AND b.psid = a.psid"
           . " WHERE a.psid = '$psid' AND a.antrain_parent_objective_id = '$antrain_parent_objective_id'"
           . " AND b.antrain_org_id = '$org_id'"
           . " AND a.antrain_org_id = '$org_id'";
      $rchild = $db->query($sql);
      if($db->getRowsNum($rchild)>0) {
         while(list($sub_antrain_objective_id,$sub_antrain_org_id,$sub_weight)=$db->fetchRow($rchild)) {
            if($sub_antrain_objective_id==$antrain_objective_id) {
               $sub_weight = 0;
            }
            $ttl_sub_weight = _bctrim(bcadd($ttl_sub_weight,$sub_weight));
         }
      }
      
      if($has_local_sub>0) {
         $ret = "( <span class='xlnk' onclick='use_sub_so_remaining(this,event);'>".toMoney(0)."</span> % remaining )";
         $weight_100base = 100*bcsub($source_so_ttlweight,$ttl_sub_weight)/$source_so_ttlweight;
         return array($weight_100base,$ret);
      }
      
      return "FAILED";
      
      
   }
   
   function app_SOWeight($args) {
      $db=&Database::getInstance();
      _dumpvar($args);
      $org_id = $_SESSION["antrain_org_id"];
      $psid = $_SESSION["antrain_psid"];
      $antrain_objective_id = $args[0];
      $antrain_parent_objective_id = $args[1];
      $weight = _bctrim(bcadd($args[2],0));
      $has_local_sub = 0;
      $ttl_sub_weight = 0;
      if($antrain_objective_id=="new") {
         $antrain_parent_objective_id = $args[1];
         if($antrain_parent_objective_id>0) {
            $has_local_sub++;
         }
      } else {
         $sql = "SELECT a.antrain_parent_objective_id,b.antrain_org_id"
              . " FROM antrain_objective a"
              . " LEFT JOIN antrain_objective b ON b.antrain_objective_id = a.antrain_parent_objective_id AND b.psid = a.psid"
              . " WHERE a.psid = '$psid' AND a.antrain_objective_id = '$antrain_objective_id'";
         $result = $db->query($sql);
         list($antrain_parent_objective_id,$parent_antrain_org_id)=$db->fetchRow($result);
         if($org_id==$parent_antrain_org_id) {
            $has_local_sub++;
         }
      }
      
      $antrain_objective_weight = $weight;
      
      //// source objective
      $sql = "SELECT p.antrain_perspective_code,a.antrain_objective_no,a.antrain_org_id,a.antrain_objective_text,b.org_nm,c.org_class_nm"
           . " FROM antrain_objective a"
           . " LEFT JOIN ".XOCP_PREFIX."orgs b ON b.org_id = a.antrain_org_id"
           . " LEFT JOIN ".XOCP_PREFIX."org_class c USING(org_class_id)"
           . " LEFT JOIN antrain_perspective p ON p.antrain_perspective_id = a.antrain_perspective_id"
           . " WHERE a.psid = '$psid' AND a.antrain_objective_id = '$antrain_parent_objective_id'";
      $result = $db->query($sql);
      list($antrain_parent_perspective_code,$antrain_parent_objective_no,$antrain_parent_org_idxxx,$antrain_parent_objective_text,$antrain_parent_org_nm,$antrain_parent_org_class_nm)=$db->fetchRow($result);
      
      $sql = "SELECT antrain_share_weight FROM antrain_kpi_share WHERE psid = '$psid' AND antrain_org_id = '$antrain_parent_org_idxxx' AND antrain_share_org_id = '$org_id' AND antrain_objective_id = '$antrain_parent_objective_id'";
      $result = $db->query($sql);
      $source_so_ttlweight = 0;
      if($db->getRowsNum($result)>0) {
         while(list($antrain_parent_share_weight)=$db->fetchRow($result)) {
            $source_so_ttlweight = _bctrim(bcadd($antrain_parent_share_weight,$source_so_ttlweight));
         }
      } else {
         $sql = "SELECT antrain_objective_weight FROM antrain_objective WHERE psid = '$psid' AND antrain_objective_id = '$antrain_parent_objective_id'";
         $result = $db->query($sql);
         list($source_so_ttlweight)=$db->fetchRow($result);
      }
      
      $sql = "SELECT a.antrain_objective_id,a.antrain_org_id,a.antrain_objective_weight"
           . " FROM antrain_objective a"
           . " LEFT JOIN antrain_objective b ON b.antrain_objective_id = a.antrain_parent_objective_id AND b.psid = a.psid"
           . " WHERE a.psid = '$psid' AND a.antrain_parent_objective_id = '$antrain_parent_objective_id'"
           . " AND b.antrain_org_id = '$org_id'"
           . " AND a.antrain_org_id = '$org_id'";
      $rchild = $db->query($sql);
      if($db->getRowsNum($rchild)>0) {
         while(list($sub_antrain_objective_id,$sub_antrain_org_id,$sub_weight)=$db->fetchRow($rchild)) {
            if($sub_antrain_objective_id==$antrain_objective_id) {
               continue;
            }
            $ttl_sub_weight = _bctrim(bcadd($ttl_sub_weight,$sub_weight));
         }
      }
      
      if($has_local_sub>0) {
         $weight_100base = $source_so_ttlweight*($weight/100);
         $ttl_sub_weight = _bctrim(bcadd($ttl_sub_weight,$weight_100base));
      } else {
         $ttl_sub_weight = _bctrim(bcadd($ttl_sub_weight,$weight));
      }
      
      
      if($has_local_sub>0) {
         $ret = "( <span class='xlnk' onclick='use_sub_so_remaining(this,event);'>".toMoney(bcsub($source_so_ttlweight,$ttl_sub_weight))."</span> % remaining )";
         return array("sub",$ret);
      } else {
         $ret = "";
         return NULL;
      }
      
      
      
   }
   
   function app_saveFinalAchievement($args) {
      $db=&Database::getInstance();
      $org_id = $_SESSION["antrain_org_id"];
      $psid = $_SESSION["antrain_psid"];
      $employee_id = $args[0]+0;
      $antrain_objective_id = $args[1]+0;
      $actionplan_id = $args[2]+0;
      $actionplan_group_id = $args[3]+0;
      $final_achievement = _bctrim(bcadd($args[4],0));
      $final_kpi_achievement = _bctrim(bcadd($args[5],0));
      
      
      if($actionplan_group_id>0) {
         $sql = "UPDATE antrain_pic_action SET final_achievement = '$final_achievement', final_kpi_achievement = '$final_kpi_achievement'"
              . " WHERE psid = '$psid'"
              . " AND employee_id = '$employee_id'"
              . " AND antrain_objective_id = '$antrain_objective_id'"
              . " AND actionplan_group_id = '$actionplan_group_id'";
         $db->query($sql);
         $sql = "UPDATE antrain_pic_action SET final_achievement = '$final_achievement', final_kpi_achievement = '$final_kpi_achievement'"
              . " WHERE psid = '$psid'"
              . " AND employee_id = '$employee_id'"
              . " AND antrain_objective_id = '$antrain_objective_id'"
              . " AND actionplan_id = '$actionplan_group_id'";
         $db->query($sql);
      } else {
         $_SESSION["pica_actionplan_id"] = array();
         $_SESSION["pica_actionplan_id"][$actionplan_id] = 1;
         $pica_root = $this->getpicaroot($antrain_objective_id,$employee_id,$actionplan_id);
         foreach($_SESSION["pica_actionplan_id"] as $pica_actionplan_id=>$xz) {
            $sql = "UPDATE antrain_pic_action SET final_achievement = '$final_achievement', final_kpi_achievement = '$final_kpi_achievement'"
                 . " WHERE psid = '$psid'"
                 . " AND employee_id = '$employee_id'"
                 . " AND antrain_objective_id = '$antrain_objective_id'"
                 . " AND actionplan_id = '$pica_actionplan_id'";
            $db->query($sql);
         }
         
      }
   }
   
   function calcTotalShared($antrain_perspective_id=0) {
      $db=&Database::getInstance();
      $org_id = $_SESSION["antrain_org_id"];
      $psid = $_SESSION["antrain_psid"];
      
      $sql = "SELECT a.antrain_share_org_id,b.org_abbr,b.org_nm"
           . " FROM antrain_org_share a"
           . " LEFT JOIN ".XOCP_PREFIX."orgs b ON b.org_id = a.antrain_share_org_id"
           . " WHERE a.antrain_org_id = '$org_id' AND psid = '$psid'"
           . " ORDER BY b.order_no";
      $result = $db->query($sql);
      $share_arr = array();
      $share_cnt = 0;
      $colgroup = "";
      if($db->getRowsNum($result)>0) {
         $share_cnt = $db->getRowsNum($result);
         while(list($antrain_share_org_id,$antrain_share_org_abbr,$antrain_share_org_nm)=$db->fetchRow($result)) {
            $share_arr[] = array($antrain_share_org_id,$antrain_share_org_nm,$antrain_share_org_abbr);
         }
      }
      
      
      
      
      /////////////////////////////////////////////////////////////////////////////////////////////////////////////
      /////////////////////////////////////////////////////////////////////////////////////////////////////////////
      $sql = "SELECT antrain_perspective_code,antrain_perspective_id,antrain_perspective_name FROM antrain_perspective WHERE psid = '$psid' ORDER BY antrain_perspective_id";
      $result = $db->query($sql);
      $ttlw = 0;
      $ttl_antrain_share = array();
      $ttl_perspective_share = array();
      $job_nm = $job_abbr = "";
      if($db->getRowsNum($result)>0) {
         while(list($antrain_perspective_code,$antrain_perspective_idx,$antrain_perspective_name)=$db->fetchRow($result)) {
            $sql = "SELECT antrain_objective_id,antrain_objective_no,antrain_objective_text,antrain_kpi_text,antrain_target_text,antrain_measurement_unit,antrain_objective_weight,"
                 . "antrain_pic_job_id,antrain_pic_employee_id,antrain_parent_objective_id,antrain_parent_kpi_id"
                 . " FROM antrain_objective"
                 . " WHERE antrain_org_id = '$org_id'"
                 . " AND psid = '$psid'"
                 . " AND antrain_perspective_id = '$antrain_perspective_idx'"
                 . " ORDER BY antrain_objective_no";
            $ro = $db->query($sql);
            $cnt = $db->getRowsNum($ro);
            $so = "";
            $so_no = 0;
            if($cnt>0) {
               $subttlw = 0;
               $subttl_antrain_share = array();
               while(list($antrain_objective_id,$antrain_objective_no,$antrain_objective_text,$antrain_kpi_text,$antrain_target_text,$antrain_measurement_unit,$antrain_objective_weight,
                          $antrain_pic_job_id,$antrain_pic_employee_id,$antrain_parent_objective_idx,$antrain_parent_kpi_idx)=$db->fetchRow($ro)) {
                  
                  /// check if it is a local sub
                  $sql = "SELECT antrain_org_id FROM antrain_objective WHERE psid = '$psid' AND antrain_objective_id = '$antrain_parent_objective_idx'";
                  $rp = $db->query($sql);
                  if($db->getRowsNum($rp)>0) {
                     list($antrain_parent_org_idx)=$db->fetchRow($rp);
                  }
                  
                  /// has local sub?
                  $sql = "SELECT antrain_objective_id,antrain_org_id,antrain_objective_weight FROM antrain_objective WHERE psid = '$psid' AND antrain_parent_objective_id = '$antrain_objective_id' AND antrain_org_id = '$org_id'";
                  $rchild = $db->query($sql);
                  $has_local_sub = 0;
                  $ttl_sub_weight = 0;
                  if($db->getRowsNum($rchild)>0) {
                     while(list($sub_antrain_objective_id,$sub_antrain_org_id,$sub_weight)=$db->fetchRow($rchild)) {
                        $has_local_sub++;
                        $ttl_sub_weight = _bctrim(bcadd($ttl_sub_weight,$sub_weight));
                     }
                  }
                  
                  $sql = "SELECT a.job_nm,a.job_abbr FROM ".XOCP_PREFIX."jobs a WHERE a.job_id = '$antrain_pic_job_id'";
                  $rj = $db->query($sql);
                  if($db->getRowsNum($rj)>0) {
                     list($so_pic_job_nm,$so_pic_job_abbr)=$db->fetchRow($rj);
                  } else {
                     $so_pic_job_nm = $so_pic_job_abbr = "";
                  }
                  $kpi_cnt = 0;
                  $sql = "SELECT antrain_kpi_id,antrain_kpi_text,antrain_kpi_weight,antrain_kpi_target_text,antrain_kpi_measurement_unit"
                       . " FROM antrain_kpi WHERE antrain_objective_id = '$antrain_objective_id'";
                  $rkpi = $db->query($sql);
                  $kpi_cnt = $db->getRowsNum($rkpi);
                  if($kpi_cnt>0&&$has_local_sub==0) {
                     $kpi_no = 0;
                     while(list($antrain_kpi_id,$antrain_kpi_text,$antrain_kpi_weight,$antrain_kpi_target_text,$antrain_kpi_measurement_unit)=$db->fetchRow($rkpi)) {
                        if($share_cnt>0) {
                           foreach($share_arr as $vshare) {
                              list($antrain_share_org_id,$antrain_share_org_nm,$antrain_share_org_abbr)=$vshare;
                              $sql = "SELECT antrain_share_weight FROM antrain_kpi_share"
                                   . " WHERE antrain_objective_id = '$antrain_objective_id'"
                                   . " AND antrain_kpi_id = '$antrain_kpi_id'"
                                   . " AND antrain_org_id = '$org_id'"
                                   . " AND antrain_share_org_id = '$antrain_share_org_id'";
                              $rw = $db->query($sql);
                              if($db->getRowsNum($rw)>0) {
                                 list($antrain_share_weight)=$db->fetchRow($rw);
                              } else {
                                 $antrain_share_weight = 0;
                              }
                              
                              if(!isset($subttl_antrain_share[$antrain_share_org_id])) $subttl_antrain_share[$antrain_share_org_id] = 0; /// initialize
                              $subttl_antrain_share[$antrain_share_org_id] = bcadd($subttl_antrain_share[$antrain_share_org_id],$antrain_share_weight);
                              if($antrain_perspective_idx==$antrain_perspective_id) {
                                 if(!isset($ttl_perspective_share[$antrain_share_org_id])) $ttl_perspective_share[$antrain_share_org_id] = 0; /// initialize;
                                 $ttl_perspective_share[$antrain_share_org_id] = bcadd($ttl_perspective_share[$antrain_share_org_id],$antrain_share_weight);
                              }
                           }
                        } else {
                        }
                        
                        $kpi_no++;
                        
                     }
                     
                  } else {
                  }
                  $so_no++;
                  
                  $do_count = 0;
                  if($antrain_parent_objective_idx==0) {
                     $do_count++;
                  } else {
                     $sql = "SELECT antrain_org_id FROM antrain_objective WHERE psid = '$psid' AND antrain_parent_objective_id = '$antrain_objective_id'";
                     $rpx = $db->query($sql);
                     if($db->getRowsNum($rpx)>0) {
                        list($antrain_parent_org_id)=$db->fetchRow($rpx);
                        if($antrain_parent_org_id==$org_id) {
                        } else {
                           $do_count++;
                        }
                     } else {
                        $do_count++;
                     }
                  }
                  if($do_count>0) {
                     $subttlw = _bctrim(bcadd($subttlw,$antrain_objective_weight));
                     $ttlw = _bctrim(bcadd($ttlw,$antrain_objective_weight));
                  }
               }
               
               if(count($share_arr)>0) {
                  foreach($share_arr as $vshare) {
                     list($antrain_share_org_id,$antrain_share_org_nm,$antrain_share_org_abbr)=$vshare;
                     $ttl_antrain_share[$antrain_share_org_id] = bcadd($ttl_antrain_share[$antrain_share_org_id],$subttl_antrain_share[$antrain_share_org_id]);
                  }
               }
            }
         }
      }
      
      $total_shared = 0;
      $arr_org = array();
      if(count($share_arr)>0) {
         $tdtotal = "";
         foreach($share_arr as $vshare) {
            list($antrain_share_org_id,$antrain_share_org_nm,$antrain_share_org_abbr)=$vshare;
            $total_shared = _bctrim(bcadd($total_shared,$ttl_antrain_share[$antrain_share_org_id]));
            $share = $ttl_antrain_share[$antrain_share_org_id];
            $arr_org[] = array($antrain_share_org_id,(bccomp($share,0)==0?toMoney(0):toMoney($share)));
         }
      }
      $arr_pers = array();
      foreach($ttl_perspective_share as $antrain_share_org_id=>$share) {
         $arr_pers[] = array($antrain_perspective_id,$antrain_share_org_id,(bccomp($share,0)==0?"-":toMoney($share)));
      }
      
      return array(toMoney($total_shared),$arr_org,$arr_pers,(bccomp(number_format($ttlw,4,".",""),number_format($total_shared,4,".",""))));
      
      /////////////////////////////////////////////////////////////////////////////////////////////////////////////
      /////////////////////////////////////////////////////////////////////////////////////////////////////////////
   }
   
   
   
   /////////////////////
   
   
   function app_setRadarYTD($args) {
      $_SESSION["antrainmonitor_radar_ytd"] = ($_SESSION["antrainmonitor_radar_ytd"]==TRUE?FALSE:TRUE);
   }
   
   function app_setRadarMonth($args) {
      $db=&Database::getInstance();
      $month = $args[0];
      $_SESSION["antrainmonitor_radar_month"] = $month;
   }
   
   function app_saveDashboard($args) {
      $db=&Database::getInstance();
      $vars = _parseForm($args[0]);
      $antrain_org_id = $_SESSION["antrain_org_id"]+0;
      
      $sql = "UPDATE antrain_dashboard_setup SET ach_cause_effect = '".$vars["ach_cause_effect"]."' WHERE antrain_org_id = '$antrain_org_id'";
      $db->query($sql);
      
      $sql = "UPDATE antrain_objective SET show_dashboard = '0' WHERE antrain_org_id = '$antrain_org_id'";
      $db->query($sql);
      foreach($vars["dash"] as $k=>$antrain_objective_id) {
         $sql = "UPDATE antrain_objective SET show_dashboard = '1' WHERE antrain_org_id = '$antrain_org_id' AND antrain_objective_id = '$antrain_objective_id'";
         $db->query($sql);
      }
   }
   
   function app_editDashboard($args) {
      $db=&Database::getInstance();
      $psid = $_SESSION["antrain_psid"];
      $antrain_org_id = $_SESSION["antrain_org_id"]+0;
      
      $sql = "SELECT ach_cause_effect FROM antrain_dashboard_setup"
           . " WHERE antrain_org_id = '$antrain_org_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($ach_cause_effect)=$db->fetchRow($result);
      }
      
      $sql = "SELECT a.antrain_objective_id,a.antrain_objective_no,b.antrain_perspective_code,a.antrain_objective_text,a.show_dashboard,b.antrain_perspective_name"
           . " FROM antrain_objective a"
           . " LEFT JOIN antrain_perspective b USING(antrain_perspective_id)"
           . " LEFT JOIN antrain_objective c ON c.antrain_objective_id = a.antrain_parent_objective_id"
           . " WHERE a.psid = '$psid' AND a.antrain_org_id = '$antrain_org_id'"
           . " AND (c.antrain_org_id != a.antrain_org_id OR c.antrain_org_id IS NULL)"
           . " ORDER BY a.antrain_perspective_id,a.antrain_objective_no";
      $result = $db->query($sql);
      $cnt = $db->getRowsNum($result);
      $oldp = "";
      if($cnt>0) {
         while(list($antrain_objective_idx,$antrain_objective_nox,$antrain_perspective_codex,$antrain_objective_text,$show_dashboard,$perspective_name)=$db->fetchRow($result)) {
            if($antrain_perspective_codex!=$oldp) {
               $obj .= "<tr><td colspan='3' style='font-weight:bold;color:black;'>$perspective_name</td></tr>";
            }
            $obj .= "<tr><td><input name='dash[]' value='$antrain_objective_idx' type='checkbox' ".($show_dashboard==1?"checked='1'":"")." id='stdck_${antrain_objective_idx}'/></td><td>${antrain_perspective_codex}${antrain_objective_nox}</td><td><label for='stdck_${antrain_objective_idx}' class='xlnk'>".htmlentities($antrain_objective_text)."</label></td></tr>";
            $oldp = $antrain_perspective_codex;
         }
      }
            
      
      $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>"
           . "Edit Dashboard "
           . "</div>"
           
           . "<div id='frmdash'>"
           
           . "<div style='background-color:#f2f2f2;border-bottom:1px solid #bbb;padding:10px;'>"
           . "<div>How to display achievement result with cause effect:</div>"
           . "</div>"
           . "<div style='background-color:#fff;border-bottom:1px solid #bbb;padding:10px;'>"
           . "<div>"
               . "<input ".($ach_cause_effect=="no"?"checked='1'":"")." type='radio' name='ach_cause_effect' value='no' id='ach_cause_effect_no'/> <label for='ach_cause_effect_no' class='xlnk'>Original Result</label>&#160;&#160;"
               . "<input ".($ach_cause_effect=="yes"?"checked='1'":"")." type='radio' name='ach_cause_effect' value='yes' id='ach_cause_effect_yes'/> <label for='ach_cause_effect_yes' class='xlnk'>Cause Effect Result</label>&#160;&#160;"
               . "<input ".($ach_cause_effect=="both"?"checked='1'":"")." type='radio' name='ach_cause_effect' value='both' id='ach_cause_effect_both'/> <label for='ach_cause_effect_both' class='xlnk'>Both Result</label>&#160;&#160;"
           . "</div>"
           . "</div>"
           
           . "<div style='background-color:#f2f2f2;border-bottom:1px solid #bbb;padding:10px;'>"
           . "<div style='background-color:#ffffcc;padding:10px;text-align:center;border:1px solid #bbb;'>Please select to display objective, or unselect to hide:</div>"
           . "</div>"
           
           . "<div style='padding:5px;background-color:#fff;color:#555;max-height:250px;height:250px;overflow:auto;'>"
           
           . "<table><tbody>"
           . $obj
           . "</tbody></table>"
           
           . "</div>"
           
           . "</div>" /// frmdash
           
           . "<div style='text-align:center;padding:10px;background-color:#ddd;border-top:1px solid #bbb;height:100px;' id='frmbtn'>"
           
           . "<input type='button' value='Save' onclick='save_dashboard();'/>&#160;&#160;"
           . "<input type='button' value='Cancel' onclick='setupdashboardbox.fade()'/>"
           
           . "<div id='vbtn'>"
           . "</div>"
           
           
           . "</div>";
      
      
      return $ret;
   }
   
   function app_selectDashboardOrg($args) {
      $_SESSION["antrain_dashboard_org"] = $args[0]+0;
   }
   
   function app_calcRemainingShare($args) {
      $db=&Database::getInstance();
      //_dumpvar($args);
      $antrain_objective_id = $args[0];
      $antrain_kpi_id = $args[1];
      $antrain_share_org_id = $args[2];
      
      $sql = "SELECT antrain_share_weight,antrain_share_org_id,antrain_kpi_id FROM antrain_kpi_share WHERE antrain_objective_id = '$antrain_objective_id'";
      $result = $db->query($sql);
      _debuglog($sql);
      $ttl = 0;
      $ttl_other = 0;
      $this_share = 0;
      if($db->getRowsNum($result)>0) {
         while(list($antrain_share_weightx,$antrain_share_org_idx,$antrain_kpi_idx)=$db->fetchRow($result)) {
            if($antrain_share_org_idx==$antrain_share_org_id&&$antrain_kpi_idx==$antrain_kpi_id) {
               $this_share = $antrain_share_weightx;
            } else {
               $ttl_other = _bctrim(bcadd($ttl_other,$antrain_share_weightx));
            }
            $ttl = _bctrim(bcadd($ttl,$antrain_share_weightx));
         }
      }
      
      $sql = "SELECT antrain_perspective_id,antrain_objective_weight FROM antrain_objective WHERE antrain_objective_id = '$antrain_objective_id'";
      $result = $db->query($sql);
      _debuglog($sql);
      if($db->getRowsNum($result)>0) {
         list($antrain_perspective_id,$antrain_objective_weight)=$db->fetchRow($result);
      }
      
      $ret = "Objective Weight: ".toMoney($antrain_objective_weight)." %<br/>"
           . "Used : ".toMoney($ttl)." %<br/>"
           . "Remaining : <span class='xlnk' onclick='get_all_remaining(\"$antrain_objective_id\",\"$antrain_kpi_id\",\"$antrain_share_org_id\",this,event);'>".toMoney(bcsub($antrain_objective_weight,$ttl))."</span> %";
      
      $this_share_100 = _bctrim((100*$this_share/$antrain_objective_weight));
      $remaining = _bctrim(bcsub($antrain_objective_weight,$ttl_other));
      $remaining_100 = _bctrim(100*$remaining/$antrain_objective_weight);
      
      $ret = array($remaining,
                   $ret,
                   $this_share,
                   $this_share_100,
                   $remaining_100,
                   $this->calcTotalShared($antrain_perspective_id));
      
      return $ret;
      
      ///return array(_bctrim(bcsub($antrain_objective_weight,$ttl_other)),$ret,$this_share);
      
   }
   
   function app_approvalantrainActionPlanReport($args) {
      $psid = $_SESSION["antrain_psid"];
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $db=&Database::getInstance();
      $month_id = $_SESSION["antrain_month"];
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
      
      $sql = "SELECT report_approval_st,antrain_objective_id,actionplan_id FROM antrain_pic_action WHERE psid = '$psid' AND employee_id = '$employee_id' AND month_id = '$month_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($report_approval_st,$antrain_objective_id,$actionplan_id)=$db->fetchRow($result)) {
            switch($report_approval_st) {
               case "approval":
                  $sql = "UPDATE antrain_pic_action SET report_approval_st = 'final', report_return_note = '', report_approval_dttm = now()"
                       . " WHERE psid = '$psid' AND antrain_objective_id = '$antrain_objective_id' AND employee_id = '$employee_id' AND actionplan_id = '$actionplan_id'";
                  $db->query($sql);
                  
                  $sql = "SELECT pica_id FROM antrain_pic_action"
                       . " WHERE employee_id = '$employee_id'"
                       . " AND antrain_objective_id = '$antrain_objective_id'"
                       . " AND actionplan_id = '$actionplan_id'";
                  $rpica = $db->query($sql);
                  list($pica_id)=$db->fetchRow($rpica);
                  
                  if($pica_id>0) {
                     $sql = "UPDATE antrain_pic_action SET approval_st = 'implementation', return_note = '', submit_dttm = now()"
                          . " WHERE psid = '$psid' AND antrain_objective_id = '$antrain_objective_id' AND employee_id = '$employee_id' AND actionplan_id = '$pica_id'";
                     $db->query($sql);
                  }
                  
                  break;
               default:
                  break;
            }
         }
      }
   
   }
   
   function app_firstAssessorReturnantrainActionPlanReport($args) {
      $psid = $_SESSION["antrain_psid"];
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $db=&Database::getInstance();
      $user_id = getUserID();
      $month_id = $_SESSION["antrain_month"];
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
      
      $sql = "SELECT report_approval_st,antrain_objective_id,actionplan_id FROM antrain_pic_action WHERE psid = '$psid' AND employee_id = '$employee_id' AND month_id = '$month_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($report_approval_st,$antrain_objective_id,$actionplan_id)=$db->fetchRow($result)) {
            switch($report_approval_st) {
               case "approval":
                  $sql = "UPDATE antrain_pic_action SET report_approval_st = 'return', report_approval_dttm = '0000-00-00 00:00:00', report_return_note = '$return_note'"
                       . " WHERE psid = '$psid' AND antrain_objective_id = '$antrain_objective_id' AND employee_id = '$employee_id' AND actionplan_id = '$actionplan_id'";
                  $db->query($sql);
                  break;
               default:
                  break;
            }
         }
      }
   
   }
   
   function app_submitActionPlanReport($args) {
      $psid = $_SESSION["antrain_psid"];
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $db=&Database::getInstance();
      $user_id = getUserID();
      $month_id = $_SESSION["antrain_month"];
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
      
      
      
      $sql = "SELECT report_approval_st,antrain_objective_id,actionplan_id FROM antrain_pic_action WHERE psid = '$psid' AND employee_id = '$self_employee_id' AND month_id = '$month_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($report_approval_st,$antrain_objective_id,$actionplan_id)=$db->fetchRow($result)) {
            switch($report_approval_st) {
               case "new":
               case "return":
                  $sql = "UPDATE antrain_pic_action SET report_approval_st = 'approval', report_return_note = '', report_submit_dttm = now()"
                       . " WHERE psid = '$psid' AND antrain_objective_id = '$antrain_objective_id' AND employee_id = '$self_employee_id' AND actionplan_id = '$actionplan_id'";
                  $db->query($sql);
                  
                  $sql = "SELECT pica_id FROM antrain_pic_action"
                       . " WHERE employee_id = '$self_employee_id'"
                       . " AND antrain_objective_id = '$antrain_objective_id'"
                       . " AND actionplan_id = '$actionplan_id'";
                  $rpica = $db->query($sql);
                  list($pica_id)=$db->fetchRow($rpica);
                  
                  if($pica_id>0) {
                     $sql = "UPDATE antrain_pic_action SET approval_st = 'approval1', approval1_employee_id = '$first_assessor_employee_id', return_note = '', submit_dttm = now()"
                          . " WHERE psid = '$psid' AND antrain_objective_id = '$antrain_objective_id' AND employee_id = '$self_employee_id' AND actionplan_id = '$pica_id'";
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
      $psid = $_SESSION["antrain_psid"];
      $db=&Database::getInstance();
      $user_id = getUserID();
      $org_id = $_SESSION["antrain_org_id"];
      $employee_id = $args[0]+0;
      $src_antrain_objective_id = $args[1]+0;
      $src_actionplan_id = $args[2]+0;
      $root_cause = addslashes(trim(urldecode($args[3])));
      $improvement = addslashes(trim(urldecode($args[4])));
      $month_id = $args[5]+0;
      $pica_id = $args[6]+0;
      
      $sql = "UPDATE antrain_pic_action SET target_text = '$improvement', month_id = '$month_id'"
           . " WHERE psid = '$psid' AND employee_id = '$employee_id'"
           . " AND antrain_objective_id = '$src_antrain_objective_id'"
           . " AND actionplan_id = '$pica_id'";
      $db->query($sql);
      
      $sql = "UPDATE antrain_pic_action SET root_cause = '$root_cause', improvement_text = '$improvement'"
           . " WHERE psid = '$psid' AND employee_id = '$employee_id'"
           . " AND antrain_objective_id = '$src_antrain_objective_id'"
           . " AND actionplan_id = '$src_actionplan_id'";
      $db->query($sql);
      
      return $this->getActionPlanUpdate($src_antrain_objective_id,$employee_id,TRUE,$_SESSION["antrain_month"]);
      
   }
   
   function app_recalculate($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $org_id = $_SESSION["antrain_org_id"];
      $this->recalculate($org_id);
   }
   
   function recalculate($org_id) {
      $psid = $_SESSION["antrain_psid"];
      $db=&Database::getInstance();
      $user_id = getUserID();
      
      $shared_objective = array();
      $share_kpi = array();
      $total_weight = 0;
      $local_weight = 0;
      $sql = "SELECT antrain_objective_id,antrain_objective_weight FROM antrain_objective WHERE psid = '$psid' AND antrain_org_id = '$org_id' AND antrain_parent_objective_id = '0'";
      $rc = $db->query($sql);
      if($db->getRowsNum($rc)>0) {
         while(list($local_antrain_objective_id,$local_antrain_objective_weight)=$db->fetchRow($rc)) {
            $local_weight = _bctrim(bcadd($local_weight,$local_antrain_objective_weight));
         }
      }
      
      $factor = _bctrim(bcsub(100,$local_weight));
            
      /*
         factor = 100 - local weight
         because local weight is already in 100 based scale
      */
      
      $sql = "SELECT a.antrain_objective_id,b.antrain_share_weight,b.antrain_kpi_id"
           . " FROM antrain_objective a"
           . " LEFT JOIN antrain_kpi_share b ON b.antrain_objective_id = a.antrain_parent_objective_id AND b.antrain_kpi_id = a.antrain_parent_kpi_id AND b.antrain_share_org_id = a.antrain_org_id"
           . " LEFT JOIN antrain_kpi c ON c.antrain_objective_id = b.antrain_objective_id AND c.antrain_kpi_id = b.antrain_kpi_id"
           . " LEFT JOIN antrain_objective d ON d.antrain_objective_id = c.antrain_objective_id"
           . " WHERE a.antrain_org_id = '$org_id'"
           . " AND a.antrain_parent_org_id > '0'"
           . " AND a.antrain_parent_org_id != '$org_id'"
           . " AND d.antrain_objective_id IS NOT NULL"
           . " AND a.psid = '$psid'";
      $rkpi = $db->query($sql);
      $shared_objective = array();
      if($db->getRowsNum($rkpi)>0) {
         while(list($antrain_objective_id,$antrain_share_weight,$antrain_kpi_id)=$db->fetchRow($rkpi)) {
            $total_weight = _bctrim(bcadd($total_weight,$antrain_share_weight));
            $shared_objective[$antrain_objective_id][$antrain_kpi_id] = array($antrain_objective_id,$antrain_kpi_id,$antrain_share_weight);
         }
      }
      
      
      /// $total_weight is total inherited
      
      $new_antrain_objective_no = 0;
      $new_total_objective_weight = 0;
      foreach($shared_objective as $antrain_perspective_id=>$v0) {
         $new_antrain_objective_no = 0;
         foreach($v0 as $antrain_objective_id=>$v1) {
            $new_antrain_objective_no++;
            list($antrain_objective_text,$antrain_objective_start,$antrain_objective_stop)=$v1;
            $objective_weight = 0;
            $objective_pic_job_id = 0;
            $objective_pic_employee_id = 0;
            $new_objective_start = "9999-12-31 00:00:00";
            $new_objective_stop = "0000-00-00 00:00:00";
            if(is_array($shared_kpi)) {
               foreach($shared_kpi[$antrain_objective_id] as $antrain_kpi_id=>$v2) {
                  list($antrain_share_weight,$antrain_kpi_text,$antrain_kpi_start,$antrain_kpi_stop,$antrain_kpi_target_text,$antrain_kpi_measurement_unit)=$v2;
                  $objective_weight = _bctrim(bcadd($objective_weight,$antrain_share_weight));
                  $new_objective_start = min($antrain_kpi_start,$new_objective_start);
                  $new_objective_stop = max($antrain_kpi_stop,$new_objective_stop);
               }
            }
            $new_objective_weight = _bctrim(bcmul(bcdiv($objective_weight,$total_weight),$factor));
            $new_total_objective_weight = _bctrim(bcadd($new_total_objective_weight,$new_objective_weight));
            //// update objective here:
            
            //$sql = "UPDATE antrain_objective SET antrain_objective_weight = '$new_objective_weight'"
            //     . " WHERE antrain_objective_id = '
            
            $db->query($sql);
            $new_objective_id = $db->getInsertId();
            
            $new_kpi_id = 0;
            //// insert kpi here:
            if(is_array($shared_kpi)) {
               foreach($shared_kpi[$antrain_objective_id] as $antrain_kpi_id=>$v2) {
                  list($antrain_share_weight,$antrain_kpi_text,$antrain_kpi_start,$antrain_kpi_stop,$antrain_kpi_target_text,$antrain_kpi_measurement_unit)=$v2;
                  $new_kpi_share_weight = _bctrim(bcmul(bcdiv($antrain_share_weight,$total_weight),100));
                  $new_kpi_id++;
                  $sql = "INSERT INTO antrain_kpi (antrain_objective_id,antrain_kpi_id,antrain_kpi_text,antrain_kpi_start,antrain_kpi_stop,antrain_kpi_target_text,antrain_kpi_measurement_unit,antrain_parent_objective_id,antrain_parent_kpi_id)"
                       . " VALUES ('$new_objective_id','$new_kpi_id','$antrain_kpi_text','$antrain_kpi_start','$antrain_kpi_stop','$antrain_kpi_target_text','$antrain_kpi_measurement_unit','$antrain_objective_id','$antrain_kpi_id')";
                  $db->query($sql);
               }
            }
            
            
         }
      }
   }
   
   function app_addSub($args) {
      $psid = $_SESSION["antrain_psid"];
      $db=&Database::getInstance();
      $user_id = getUserID();
      $antrain_objective_id = $args[0];
      $org_id = $_SESSION["antrain_org_id"];
      $psid = $_SESSION["antrain_psid"];
      
      $sql = "SELECT a.org_nm,b.org_class_nm FROM ".XOCP_PREFIX."orgs a LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id) WHERE a.org_id = '$org_id'";
      $result = $db->query($sql);
      list($org_nm,$org_class_nm)=$db->fetchRow($result);
      
      
      $sql = "SELECT a.antrain_perspective_id,b.antrain_perspective_code,b.antrain_perspective_name FROM antrain_objective a LEFT JOIN antrain_perspective b USING(antrain_perspective_id) WHERE a.antrain_objective_id = '$antrain_objective_id'";
      $result = $db->query($sql);
      list($antrain_perspective_id,$antrain_perspective_code,$antrain_perspective_name)=$db->fetchRow($result);
      
      $sql = "SELECT antrain_objective_id,antrain_objective_no FROM antrain_objective WHERE psid = '$psid' AND antrain_org_id = '$org_id' AND antrain_parent_objective_id = '$antrain_objective_id' ORDER BY antrain_objective_no DESC LIMIT 1";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($antrain_objective_idx,$antrain_objective_nox)=$db->fetchRow($result);
         if($antrain_objective_idx=="") {
            $sql = "SELECT antrain_objective_no FROM antrain_objective WHERE antrain_objective_id = '$antrain_objective_id'";
            $result = $db->query($sql);
            list($antrain_objective_no)=$db->fetchRow($result);
            $new_antrain_objective_no = $antrain_objective_no.".1";
         } else {
            $nos = explode(".",$antrain_objective_nox);
            _dumpvar($nos);
            $n = count($nos)-1;
            $new_antrain_objective_no = substr($antrain_objective_nox,0,-2).".".($nos[$n]+1);
         }
      } else {
         $sql = "SELECT antrain_objective_no FROM antrain_objective WHERE antrain_objective_id = '$antrain_objective_id'";
         $result = $db->query($sql);
         list($antrain_objective_no)=$db->fetchRow($result);
         $new_antrain_objective_no = $antrain_objective_no.".1";
      }
      
      $sql = "SELECT antrain_perspective_code,antrain_perspective_id,antrain_perspective_name FROM antrain_perspective WHERE psid = '$psid' ORDER BY antrain_perspective_id";
      $result = $db->query($sql);
      $optpers = "";
      if($db->getRowsNum($result)>0) {
         while(list($antrain_perspective_codex,$antrain_perspective_idx,$antrain_perspective_namex)=$db->fetchRow($result)) {
            $optpers .= "<option value='$antrain_perspective_idx|$antrain_perspective_codex' ".($antrain_perspective_idx==$antrain_perspective_id?"selected='selected'":"").">$antrain_perspective_namex</option>";
         }
      }
      
      $optpers = "$antrain_perspective_name<input type='hidden' name='antrain_perspective_id' id='antrain_perpsective_id' value='$antrain_perspective_id'/>";
      
      $sel_pers = "<tr><td>Perspective</td><td>"
                //. "<select id='antrain_perspective_id' name='antrain_perspective_id' onchange='chgpers(this,event);'>"
                . "$optpers"
                //. "</select>"
                . "&#160;"
                . "No : <input disabled='1' name='antrain_objective_no' id='antrain_objective_no' type='text' style='width:40px;text-align:center;' value='$new_antrain_objective_no'/>"
                . "</td></tr>";
      
      //// source objective
      $sql = "SELECT p.antrain_perspective_code,a.antrain_objective_no,a.antrain_org_id,a.antrain_objective_text,b.org_nm,c.org_class_nm,a.antrain_objective_weight"
           . " FROM antrain_objective a"
           . " LEFT JOIN ".XOCP_PREFIX."orgs b ON b.org_id = a.antrain_org_id"
           . " LEFT JOIN ".XOCP_PREFIX."org_class c USING(org_class_id)"
           . " LEFT JOIN antrain_perspective p ON p.antrain_perspective_id = a.antrain_perspective_id"
           . " WHERE a.antrain_objective_id = '$antrain_objective_id'";
      $result = $db->query($sql);
      list($antrain_parent_perspective_code,$antrain_parent_objective_no,$antrain_parent_org_idxxx,$antrain_parent_objective_text,$antrain_parent_org_nm,$antrain_parent_org_class_nm,$antrain_parent_objective_weight)=$db->fetchRow($result);
      
      $sql = "SELECT antrain_objective_id,antrain_org_id,antrain_objective_weight FROM antrain_objective WHERE psid = '$psid' AND antrain_parent_objective_id = '$antrain_objective_id'"
           //. " AND antrain_objective_id != '$antrain_objective_id'"
           . " AND antrain_org_id = '$org_id'";
      $rchild = $db->query($sql);
      $has_local_sub = 0;
      $ttl_sub_weight = 0;
      if($db->getRowsNum($rchild)>0) {
         while(list($sub_antrain_objective_id,$sub_antrain_org_id,$sub_weight)=$db->fetchRow($rchild)) {
            $has_local_sub++;
            $ttl_sub_weight = _bctrim(bcadd($ttl_sub_weight,$sub_weight));
         }
      }
      
      $source_so = "Strategic Objective Source:"
                 . "<div id='parent_so'>"
                 . "<table class='xxfrm' style='width:100%;'>"
                 . "<colgroup><col width='150'/><col/></colgroup>"
                 . "<tbody>"
                 . "<tr><td>Organization</td><td>".htmlentities("$antrain_parent_org_nm $antrain_parent_org_class_nm")."</td></tr>"
                 . "<tr><td>Strategic Objective</td><td>".htmlentities("${antrain_parent_perspective_code}${antrain_parent_objective_no} $antrain_parent_objective_text")."</td></tr>"
                 . "<tr><td>Weight</td><td>".toMoney($antrain_parent_objective_weight)." % <span id='sub_so_remaining' style='color:blue;'>( <span class='xlnk' onclick='use_sub_so_remaining(this,event);'>".toMoney(bcsub($antrain_parent_objective_weight,$ttl_sub_weight))."</span> % remaining )</span></td></tr>"
                 ///. "<tr><td colspan='2'><input type='button' value='Select Source' onclick='change_so_origin(this,event);'/></td></tr>"
                 . "</tbody></table>"
                 . "<input type='hidden' name='antrain_parent_objective_id' id='antrain_parent_objective_id' value='$antrain_objective_id'/>"
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
            $optpic .= "<option value='$job_id' ".($job_id==$antrain_pic_job_id?"selected='selected'":"").">".htmlentities("$job_abbr - $job_nm")."</option>";
         }
      }
      
      $title = "Add New Sub Strategic Objective";
      $btn = "<input type='button' value='Add New' onclick='save_so(\"new\",this,event);'/>&#160;&#160;"
           . "<input type='button' value='"._CANCEL."' onclick='editsobox.fade();'/>";
      $btn_sub = "&nbsp;";
      $tm_start = getSQLDate();
      $tm_stop = getSQLDate();
      
      $sql = "SELECT start_dttm,stop_dttm FROM antrain_session WHERE psid = '$psid'";
      $result = $db->query($sql);
      list($tm_start,$tm_stop)=$db->fetchRow($result);
      
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
                  . "<tr><td>ID</td><td id='antrain_obj_code'>${antrain_perspective_code}${new_antrain_objective_no}</td></tr>"
                  
                  
                  . $sel_pers
                  
                  . "<tr><td>Strategic Objective</td><td><input type='text' id='so_txt' name='so_txt' style='width:400px;' value='".htmlentities($antrain_objective_text)."'/></td></tr>"
                  
                  . "<tr><td>PIC</td><td><select id='pic_job_id' name='pic_job_id'>$optpic</select></td></tr>"
                  
                  . "<tr><td>Weight</td><td>"
                     . "<input onclick='_dsa(this,event);' onkeypress='kp_so_weight(this,event);' id='weight' name='weight' type='text' style='width:80px;' value='$antrain_objective_weight'/> %"
                  . "</td></tr>"
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
                  . "<input type='hidden' name='antrain_objective_id' id='antrain_objective_id' value='new'/>"
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
      $psid = $_SESSION["antrain_psid"];
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $db=&Database::getInstance();
      $user_id = getUserID();
      $employee_id = $args[0];
      $antrain_objective_id = $args[1];
      $actionplan_id = $args[2];
      $achievement = _bctrim(bcadd($args[3],0));
      $achievement_kpi = _bctrim(bcadd($args[4],0));
      $final_result = addslashes(urldecode($args[5]));
      
      $generate_pica = 0;
      
      $sql = "SELECT actionplan_text,target_text,current_achievement,target_achievement,final_result_text,is_pica,pica_id,month_id,allow_carry_over,root_cause,improvement_text,"
           . "actionplan_group_id"
           . " FROM antrain_pic_action"
           . " WHERE employee_id = '$employee_id'"
           . " AND antrain_objective_id = '$antrain_objective_id'"
           . " AND actionplan_id = '$actionplan_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($actionplan_text,$target_text,$current_achievement,$target_achievement,$final_result_text,$is_pica,$pica_id,$month_id,$allow_carry_over,$pica_root_cause,$pica_improvement,
              $actionplan_group_id)=$db->fetchRow($result);
      }
      
      if($month_id>=12) $allow_carry_over = 0;
      
      $pica_month_id = $month_id+1;
      
      if($pica_month_id>12) $pica_month_id = 12;
      
      $sql = "UPDATE antrain_pic_action SET "
           . "current_achievement = '$achievement',"
           . "current_kpi_achievement = '$achievement_kpi',"
           . "final_result_text = '$final_result'"
           . " WHERE employee_id = '$employee_id'"
           . " AND antrain_objective_id = '$antrain_objective_id'"
           . " AND actionplan_id = '$actionplan_id'";
      $db->query($sql);
      
      $gap_ap = bcsub($achievement,$target_achievement);
      $gap_kpi = bcsub($achievement_kpi,$target_achievement);
      
      
      $sql = "SELECT a.antrain_objective_text,a.antrain_objective_no,b.antrain_perspective_code"
           . " FROM antrain_objective a"
           . " LEFT JOIN antrain_perspective b USING(antrain_perspective_id)"
           . " WHERE a.antrain_objective_id = '$antrain_objective_id'";
      $result = $db->query($sql);
      list($antrain_objective_text,$antrain_objective_no,$antrain_perspective_code)=$db->fetchRow($result);
      
      
      if($gap_ap<-9||$gap_kpi<-9) { /// pica
         
         if($allow_carry_over==1) {
            
            $target_for_pica = _bctrim(bcsub($target_achievement,$achievement));
            
            if($pica_id==0) { /// create new action plan for pica
               $sql = "SELECT MAX(actionplan_id) FROM antrain_pic_action"
                    . " WHERE employee_id = '$employee_id' AND antrain_objective_id = '$antrain_objective_id'";
               $result = $db->query($sql);
               if($db->getRowsNum($result)>0) {
                  list($new_actionplan_id)=$db->fetchRow($result);
               }
               
               $new_actionplan_id++;
               
               $sql = "SELECT MAX(order_no) FROM antrain_pic_action WHERE employee_id = '$employee_id' AND antrain_objective_id = '$antrain_objective_id'";
               $result = $db->query($sql);
               $order_no = 0;
               if($db->getRowsNum($result)>0) {
                  list($order_no)=$db->fetchRow($result);
               }
               
               $order_no++;
               $sql = "INSERT INTO antrain_pic_action (psid,antrain_objective_id,employee_id,actionplan_id,month_id,created_dttm,created_user_id,order_no,is_pica,actionplan_text,target_text,target_achievement,prev_actionplan_id)"
                    . " VALUES ('$psid','$antrain_objective_id','$employee_id','$new_actionplan_id','$pica_month_id',now(),'$user_id','$order_no','1','".addslashes($actionplan_text)."','".addslashes($target_text)."','$target_for_pica','$actionplan_id')";
               $db->query($sql);
               
               /// update link to pica
               $sql = "UPDATE antrain_pic_action SET "
                    . "pica_id = '$new_actionplan_id'"
                    . " WHERE employee_id = '$employee_id'"
                    . " AND antrain_objective_id = '$antrain_objective_id'"
                    . " AND actionplan_id = '$actionplan_id'";
               $db->query($sql);
               
               $pica_id = $new_actionplan_id;
            } else {
               $sql = "UPDATE antrain_pic_action SET target_achievement = '$target_for_pica',is_pica = '1'"
                    . " WHERE employee_id = '$employee_id'"
                    . " AND antrain_objective_id = '$antrain_objective_id'"
                    . " AND actionplan_id = '$pica_id'";
               $db->query($sql);
            }
            
            
            $sql = "SELECT actionplan_text,target_text,month_id,target_achievement,month_id"
                 . " FROM antrain_pic_action"
                 . " WHERE employee_id = '$employee_id'"
                 . " AND antrain_objective_id = '$antrain_objective_id'"
                 . " AND actionplan_id = '$pica_id'";
            $result = $db->query($sql);
            list($pica_actionplan_text,$pica_target_text,$pica_month_id,$pica_target,$pica_month_id)=$db->fetchRow($result);
         } else {
            $sql = "UPDATE antrain_pic_action SET "
                 . "pica_id = '-1'"
                 . " WHERE employee_id = '$employee_id'"
                 . " AND antrain_objective_id = '$antrain_objective_id'"
                 . " AND actionplan_id = '$actionplan_id'";
            $db->query($sql);
            $pica_id = -1;
            
         }
         
         global $xocp_vars;
         $opt_month = "";
         foreach($xocp_vars["month_year"] as $k=>$v) {
            if($v=="") continue;
            $opt_month .= "<option value='$k' ".($pica_month_id==$k?"selected='1'":"").">$v</option>";
         }
         
         $ret = "<div style='padding:10px;color:red;padding-top:5px;'>Your achievement generate PICA. Please fulfill PICA form below:</div>"
              . "<input type='hidden' id='pica_id' value='$pica_id'/>"
              . "<input type='hidden' id='actionplan_id' value='$actionplan_id'/>"
              . "<table class='sfrm' align='center'>"
              . "<tbody>"
              . "<tr><td>Objective :</td><td>${antrain_perspective_code}${antrain_objective_no} $antrain_objective_text</td></tr>"
              . "<tr><td>Action Plan :</td><td>$actionplan_text</td></tr>"
              . "<tr><td>Action Plan Achievement :</td><td>".toMoney($achievement)." %</td></tr>"
              . "<tr><td>KPI Achievement :</td><td>".toMoney($achievement_kpi)." %</td></tr>"
              . "<tr><td>Root Cause :</td><td><input type='text' style='width:250px;' id='root_cause' value='".addslashes($pica_root_cause)."'/></td></tr>";
         
         if($allow_carry_over==1) {
            $ret .= "<tr><td>Corrective Action :</td><td><input type='text' style='width:250px;' id='target_text' value='".addslashes($pica_improvement)."'/></td></tr>";
            $ret .= "<tr><td>Next Target Achievement :</td><td>".toMoney($pica_target)." %</td></tr>";
            $ret .= "<tr><td>Next Schedule :</td><td><select id='pica_month_id'>$opt_month</select></td></tr>";
         } else {
            $ret .= "<tr><td>Corrective Action :</td><td><input type='text' style='width:250px;' id='target_text' value='".addslashes($pica_improvement)."'/></td></tr>";
            $ret .= "<tr><td>Next Schedule :</td><td style='color:red;'>Not Applicable. PICA will not carry over.</td></tr>";
         }
              
         $ret .= "</tbody>"
              . "</table>";
         $generate_pica = 1;
         return array($generate_pica,$ret);
      } else {
         
         //// clean up pica if any
         $sql = "DELETE FROM antrain_pic_action"
              . " WHERE employee_id = '$employee_id'"
              . " AND antrain_objective_id = '$antrain_objective_id'"
              . " AND is_pica = '1'"
              . " AND prev_actionplan_id = '$actionplan_id'";
         $db->query($sql);
         
         $sql = "UPDATE antrain_pic_action SET pica_id = '0', root_cause = '', improvement_text = ''"
              . " WHERE employee_id = '$employee_id'"
              . " AND antrain_objective_id = '$antrain_objective_id'"
              . " AND actionplan_id = '$actionplan_id'";
         $db->query($sql);
         $generate_pica = 0;
         
         //// check if there is another scheduled action plan for the same action plan
         if($actionplan_group_id>0) {
            $sql = "SELECT final_achievement,final_kpi_achievement FROM antrain_pic_action"
                 . " WHERE psid = '$psid'"
                 . " AND employee_id = '$employee_id'"
                 . " AND antrain_objective_id = '$antrain_objective_id'"
                 . " AND actionplan_id = '$actionplan_group_id'";
            $result = $db->query($sql);
            list($final_achievement,$final_kpi_achievement)=$db->fetchRow($result);
            $sql = "SELECT MAX(month_id) FROM antrain_pic_action"
                 . " WHERE psid = '$psid'"
                 . " AND employee_id = '$employee_id'"
                 . " AND antrain_objective_id = '$antrain_objective_id'"
                 . " AND actionplan_group_id = '$actionplan_group_id'";
            $result = $db->query($sql);
            if($db->getRowsNum($result)>0) {
               list($max_month_id)=$db->fetchRow($result);
               if($max_month_id==$month_id) {
                  $sql = "SELECT month_id,current_achievement,current_kpi_achievement,target_achievement,pica_id,is_pica"
                       . " FROM antrain_pic_action"
                       . " WHERE psid = '$psid'"
                       . " AND employee_id = '$employee_id'"
                       . " AND antrain_objective_id = '$antrain_objective_id'"
                       . " AND actionplan_group_id = '$actionplan_group_id'"
                       . " ORDER BY month_id";
                  $rac = $db->query($sql);
                  if($db->getRowsNum($rac)>0) {
                     while(list($x_month_id,$x_current_achievement,$x_current_kpi_achievement,$x_target_achievement,$x_pica_id,$x_is_pica)=$db->fetchRow($rac)) {
                        $vv = "x_ap_${x_month_id}";
                        $$vv = "$x_current_achievement%";
                        $ww = "x_kpi_${x_month_id}";
                        $$ww = "$x_current_kpi_achievement%";
                     }
                  }
                  
                  $ret = "<div style='padding:10px;color:blue;padding-top:5px;'>Your action plan is completed. Please fulfill final achievement value below:</div>"
                       . "<input type='hidden' id='actionplan_id' value='$actionplan_id'/>"
                       . "<input type='hidden' id='actionplan_group_id' value='$actionplan_group_id'/>"
                       . "<table class='sfrm' align='center'>"
                       . "<tbody>"
                       . "<tr><td>Objective :</td><td>${antrain_perspective_code}${antrain_objective_no} $antrain_objective_text</td></tr>"
                       . "<tr><td>Action Plan :</td><td>$actionplan_text</td></tr>"
                       . "<tr><td colspan='2'>"
                           ///. "<div style='text-align:center;font-weight:bold;'>Achievement:</div>"
                           . "<table class='xxlist' style='font-size:0.8em;'>"
                           . "<colgroup>"
                              . "<col/>"
                              . "<col width='40'/>"
                              . "<col width='40'/>"
                              . "<col width='40'/>"
                              . "<col width='40'/>"
                              . "<col width='40'/>"
                              . "<col width='40'/>"
                              . "<col width='40'/>"
                              . "<col width='40'/>"
                              . "<col width='40'/>"
                              . "<col width='40'/>"
                              . "<col width='40'/>"
                              . "<col width='40'/>"
                           . "</colgroup>"
                           . "<thead>"
                              . "<tr><td>Month</td>"
                                  . "<td style='text-align:center;'>Jan</td>"
                                  . "<td style='text-align:center;'>Feb</td>"
                                  . "<td style='text-align:center;'>Mar</td>"
                                  . "<td style='text-align:center;'>Apr</td>"
                                  . "<td style='text-align:center;'>May</td>"
                                  . "<td style='text-align:center;'>Jun</td>"
                                  . "<td style='text-align:center;'>Jul</td>"
                                  . "<td style='text-align:center;'>Aug</td>"
                                  . "<td style='text-align:center;'>Sep</td>"
                                  . "<td style='text-align:center;'>Oct</td>"
                                  . "<td style='text-align:center;'>Nov</td>"
                                  . "<td style='text-align:center;'>Des</td>"
                              . "</tr>"
                           . "</thead>"
                           . "<tbody>"
                              . "<tr><td>Action Plan Achievement</td>"
                                  . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_ap_1</td>"
                                  . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_ap_2</td>"
                                  . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_ap_3</td>"
                                  . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_ap_4</td>"
                                  . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_ap_5</td>"
                                  . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_ap_6</td>"
                                  . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_ap_7</td>"
                                  . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_ap_8</td>"
                                  . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_ap_9</td>"
                                  . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_ap_10</td>"
                                  . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_ap_11</td>"
                                  . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_ap_12</td>"
                              . "</tr>"
                              . "<tr><td>KPI Achievement</td>"
                                  . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_kpi_1</td>"
                                  . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_kpi_2</td>"
                                  . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_kpi_3</td>"
                                  . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_kpi_4</td>"
                                  . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_kpi_5</td>"
                                  . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_kpi_6</td>"
                                  . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_kpi_7</td>"
                                  . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_kpi_8</td>"
                                  . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_kpi_9</td>"
                                  . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_kpi_10</td>"
                                  . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_kpi_11</td>"
                                  . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_kpi_12</td>"
                              . "</tr>"
                           . "</tbody>"
                           . "</table>"
                       . "</td></tr>";
                  
                  $ret .= "<tr><td>Action Plan Final Achievement:</td><td><input type='text' style='width:50px;' id='final_achievement' value='$final_achievement'/> %</td></tr>";
                  $ret .= "<tr><td>KPI Final Achievement:</td><td><input type='text' style='width:50px;' id='final_kpi_achievement' value=''/> %</td></tr>";
                       
                  $ret .= "</tbody>"
                       . "</table>";
                  
                  return array(2,$ret);
                  
               }
            }
            
         } else { /// non group actionplan, single with carry over
            
            if($is_pica==1) {
               $_SESSION["pica_actionplan_id"] = array();
               $_SESSION["pica_actionplan_id"][$actionplan_id] = 1;
               $pica_root = $this->getpicaroot($antrain_objective_id,$employee_id,$actionplan_id);
               
               $sql = "SELECT final_achievement,final_kpi_achievement FROM antrain_pic_action"
                    . " WHERE psid = '$psid'"
                    . " AND employee_id = '$employee_id'"
                    . " AND antrain_objective_id = '$antrain_objective_id'"
                    . " AND actionplan_id = '$pica_root'";
               $result = $db->query($sql);
               list($final_achievement,$final_kpi_achievement)=$db->fetchRow($result);
               foreach($_SESSION["pica_actionplan_id"] as $pica_actionplan_id=>$xz) {
                  $sql = "SELECT month_id,current_achievement,current_kpi_achievement,target_achievement,pica_id,is_pica"
                       . " FROM antrain_pic_action"
                       . " WHERE psid = '$psid'"
                       . " AND employee_id = '$employee_id'"
                       . " AND antrain_objective_id = '$antrain_objective_id'"
                       . " AND actionplan_id = '$pica_actionplan_id'"
                       . " ORDER BY month_id";
                  $rac = $db->query($sql);
                  if($db->getRowsNum($rac)>0) {
                     while(list($x_month_id,$x_current_achievement,$x_current_kpi_achievement,$x_target_achievement,$x_pica_id,$x_is_pica)=$db->fetchRow($rac)) {
                        $vv = "x_ap_${x_month_id}";
                        $$vv = "$x_current_achievement%";
                        $ww = "x_kpi_${x_month_id}";
                        $$ww = "$x_current_kpi_achievement%";
                     }
                  }
               }
            } else {
               $sql = "SELECT month_id,current_achievement,current_kpi_achievement,target_achievement,pica_id,is_pica,final_achievement,final_kpi_achievement"
                    . " FROM antrain_pic_action"
                    . " WHERE psid = '$psid'"
                    . " AND employee_id = '$employee_id'"
                    . " AND antrain_objective_id = '$antrain_objective_id'"
                    . " AND actionplan_id = '$actionplan_id'"
                    . " ORDER BY month_id";
               $rac = $db->query($sql);
               if($db->getRowsNum($rac)>0) {
                  while(list($x_month_id,$x_current_achievement,$x_current_kpi_achievement,$x_target_achievement,$x_pica_id,$x_is_pica,$final_achievement,$final_kpi_achievement)=$db->fetchRow($rac)) {
                     $vv = "x_ap_${x_month_id}";
                     $$vv = "$x_current_achievement%";
                     $ww = "x_kpi_${x_month_id}";
                     $$ww = "$x_current_kpi_achievement%";
                  }
               }
            }
            
            $ret = "<div style='padding:10px;color:blue;padding-top:5px;'>Your action plan is completed. Please fulfill final achievement value below:</div>"
                 . "<input type='hidden' id='actionplan_id' value='$actionplan_id'/>"
                 . "<input type='hidden' id='actionplan_group_id' value='$actionplan_group_id'/>"
                 . "<table class='sfrm' align='center'>"
                 . "<tbody>"
                 . "<tr><td>Objective :</td><td>${antrain_perspective_code}${antrain_objective_no} $antrain_objective_text</td></tr>"
                 . "<tr><td>Action Plan :</td><td>$actionplan_text</td></tr>"
                 . "<tr><td colspan='2'>"
                     ///. "<div style='text-align:center;font-weight:bold;'>Achievement:</div>"
                     . "<table class='xxlist' style='font-size:0.8em;'>"
                     . "<colgroup>"
                        . "<col/>"
                        . "<col width='40'/>"
                        . "<col width='40'/>"
                        . "<col width='40'/>"
                        . "<col width='40'/>"
                        . "<col width='40'/>"
                        . "<col width='40'/>"
                        . "<col width='40'/>"
                        . "<col width='40'/>"
                        . "<col width='40'/>"
                        . "<col width='40'/>"
                        . "<col width='40'/>"
                        . "<col width='40'/>"
                     . "</colgroup>"
                     . "<thead>"
                        . "<tr><td>Month</td>"
                            . "<td style='text-align:center;'>Jan</td>"
                            . "<td style='text-align:center;'>Feb</td>"
                            . "<td style='text-align:center;'>Mar</td>"
                            . "<td style='text-align:center;'>Apr</td>"
                            . "<td style='text-align:center;'>May</td>"
                            . "<td style='text-align:center;'>Jun</td>"
                            . "<td style='text-align:center;'>Jul</td>"
                            . "<td style='text-align:center;'>Aug</td>"
                            . "<td style='text-align:center;'>Sep</td>"
                            . "<td style='text-align:center;'>Oct</td>"
                            . "<td style='text-align:center;'>Nov</td>"
                            . "<td style='text-align:center;'>Des</td>"
                        . "</tr>"
                     . "</thead>"
                     . "<tbody>"
                        . "<tr><td>Action Plan Achievement</td>"
                            . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_ap_1</td>"
                            . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_ap_2</td>"
                            . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_ap_3</td>"
                            . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_ap_4</td>"
                            . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_ap_5</td>"
                            . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_ap_6</td>"
                            . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_ap_7</td>"
                            . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_ap_8</td>"
                            . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_ap_9</td>"
                            . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_ap_10</td>"
                            . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_ap_11</td>"
                            . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_ap_12</td>"
                        . "</tr>"
                        . "<tr><td>KPI Achievement</td>"
                            . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_kpi_1</td>"
                            . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_kpi_2</td>"
                            . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_kpi_3</td>"
                            . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_kpi_4</td>"
                            . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_kpi_5</td>"
                            . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_kpi_6</td>"
                            . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_kpi_7</td>"
                            . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_kpi_8</td>"
                            . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_kpi_9</td>"
                            . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_kpi_10</td>"
                            . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_kpi_11</td>"
                            . "<td style='border-left:1px solid #bbb;text-align:center;'>$x_kpi_12</td>"
                        . "</tr>"
                     . "</tbody>"
                     . "</table>"
                 . "</td></tr>";
            
            $ret .= "<tr><td>Action Plan Final Achievement:</td><td><input type='text' style='width:50px;' id='final_achievement' value='$final_achievement'/> %</td></tr>";
            $ret .= "<tr><td>KPI Final Achievement:</td><td><input type='text' style='width:50px;' id='final_kpi_achievement' value='$final_kpi_achievement'/> %</td></tr>";
                 
            $ret .= "</tbody>"
                 . "</table>";
            
            return array(2,$ret);
            
         }
         
      }
      
   }
   
   function app_editAchievement($args) {
      $psid = $_SESSION["antrain_psid"];
      global $xocp_vars;
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $db=&Database::getInstance();
      $user_id = getUserID();
      $employee_id = $args[0];
      $antrain_objective_id = $args[1];
      $actionplan_id = $args[2];
      
      $sql = "SELECT actionplan_text,target_text,current_achievement,current_kpi_achievement,target_achievement,final_result_text,is_pica,pica_id,month_id,"
           . "actionplan_group_id"
          . " FROM antrain_pic_action"
          . " WHERE psid = '$psid' AND employee_id = '$employee_id'"
          . " AND antrain_objective_id = '$antrain_objective_id'"
          . " AND actionplan_id = '$actionplan_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($actionplan_text,$target_text,$current_achievement,$current_kpi_achievement,$target_achievement,$final_result_text,$is_pica,$pica_id,$month_id,
              $actionplan_group_id)=$db->fetchRow($result);
         $sql = "SELECT a.antrain_objective_text,a.antrain_objective_no,b.antrain_perspective_code"
              . " FROM antrain_objective a"
              . " LEFT JOIN antrain_perspective b USING(antrain_perspective_id)"
              . " WHERE a.psid = '$psid' AND a.antrain_objective_id = '$antrain_objective_id'";
         $result = $db->query($sql);
         list($antrain_objective_text,$antrain_objective_no,$antrain_perspective_code)=$db->fetchRow($result);
         if(trim($actionplan_text)=="") {
            $actionplan_text = "<span class='empty'>"._EMPTY."</span>";
         }
         if(trim($target_text)=="") {
            $target_text = "<span class='empty'>"._EMPTY."</span>";
         }
         $repeat_type = 0;
         if($actionplan_group_id>0) {
            $sql = "SELECT repeat_type"
                 . " FROM antrain_pic_action"
                 . " WHERE psid = '$psid' AND employee_id = '$employee_id'"
                 . " AND antrain_objective_id = '$antrain_objective_id'"
                 . " AND actionplan_id = '$actionplan_group_id'";
            $rg = $db->query($sql);
            if($db->getRowsNum($rg)>0) {
               list($repeat_type)=$db->fetchRow($rg);
            }
         }
         switch($repeat_type) {
            case 1:
               $repeat_type_txt = "Repeat Every Month";
               break;
            case 2:
               $repeat_type_txt = "Repeat Every Chosen Month";
               break;
            case 0:
            default:
               $repeat_type_txt = "No Repeat";
               break;
         }
         
         $ret = "<table class='sfrm' align='center'>"
              . "<tbody>"
              . "<tr><td>Objective :</td><td>${antrain_perspective_code}${antrain_objective_no} $antrain_objective_text</td></tr>"
              . "<tr><td>Action Plan :</td><td>$actionplan_text</td></tr>"
              . "<tr><td>Month :</td><td>".$xocp_vars["month_year"][$month_id]."</td></tr>"
              . "<tr><td>Repeat :</td><td>$repeat_type_txt</td></tr>"
              . "<tr><td>Target :</td><td>$target_text</td></tr>"
              . "<tr><td>Target Achievement :</td><td>".toMoney($target_achievement)." %</td></tr>"
              . "<tr><td>Action Plan Achievement :</td><td><input type='text' style='width:50px;' id='inp_current_achievement' value='$current_achievement'/> %</td></tr>"
              . "<tr><td>KPI Achievement :</td><td><input type='text' style='width:50px;' id='inp_current_kpi_achievement' value='$current_kpi_achievement'/> %</td></tr>"
              . "<tr><td>Realization :</td><td><input style='width:250px;' type='text' id='inp_final_result' value='$final_result_text'/></td></tr>"
              . "</tbody>"
              . "</table>";
         return $ret;
      }
      
   }
   
   function app_setantrainMonth($args) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $db=&Database::getInstance();
      $_SESSION["antrain_month"] = $args[0]+0;
   }
   
   function app_firstAssessorReturnantrainActionPlan($args) {
      $psid = $_SESSION["antrain_psid"];
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
      
      $sql = "SELECT approval_st,antrain_objective_id,actionplan_id FROM antrain_pic_action WHERE psid = '$psid' AND employee_id = '$employee_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($approval_st,$antrain_objective_id,$actionplan_id)=$db->fetchRow($result)) {
            switch($approval_st) {
               case "approval1":
                  $sql = "UPDATE antrain_pic_action SET approval_st = 'return', approval1_dttm = '0000-00-00 00:00:00', return_note = '$return_note'"
                       . " WHERE psid = '$psid' AND antrain_objective_id = '$antrain_objective_id' AND employee_id = '$employee_id' AND actionplan_id = '$actionplan_id'";
                  $db->query($sql);
                  break;
               default:
                  break;
            }
         }
      }
   
   }
   
   function app_approval1antrainActionPlan($args) {
      $psid = $_SESSION["antrain_psid"];
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
      
      $sql = "SELECT approval_st,antrain_objective_id,actionplan_id FROM antrain_pic_action WHERE psid = '$psid' AND employee_id = '$employee_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($approval_st,$antrain_objective_id,$actionplan_id)=$db->fetchRow($result)) {
            switch($approval_st) {
               case "approval1":
                  $sql = "UPDATE antrain_pic_action SET approval_st = 'implementation', return_note = '', approval1_dttm = now()"
                       . " WHERE psid = '$psid' AND antrain_objective_id = '$antrain_objective_id' AND employee_id = '$employee_id' AND actionplan_id = '$actionplan_id'";
                  $db->query($sql);
                  break;
               default:
                  break;
            }
         }
      }
   }
   
   function app_submitActionPlan($args) {
      $psid = $_SESSION["antrain_psid"];
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
      
      $sql = "SELECT approval_st,antrain_objective_id,actionplan_id FROM antrain_pic_action WHERE psid = '$psid' AND employee_id = '$self_employee_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($approval_st,$antrain_objective_id,$actionplan_id)=$db->fetchRow($result)) {
            switch($approval_st) {
               case "new":
               case "return":
                  $sql = "UPDATE antrain_pic_action SET approval_st = 'approval1', approval1_employee_id = '$first_assessor_employee_id', return_note = '', submit_dttm = now()"
                       . " WHERE psid = '$psid' AND antrain_objective_id = '$antrain_objective_id' AND employee_id = '$self_employee_id' AND actionplan_id = '$actionplan_id'";
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
      $antrain_objective_id = $args[1];
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
      
      $sql = "DELETE FROM antrain_pic_action WHERE employee_id = '$employee_id' AND antrain_objective_id = '$antrain_objective_id' AND actionplan_id = '$actionplan_id'";
      $db->query($sql);
      return $this->getActionPlanUpdate($antrain_objective_id,$employee_id);
   }
   
   function getpicaroot($antrain_objective_id,$employee_id,$actionplan_id) {
      $db=&Database::getInstance();
      $sql = "SELECT actionplan_id,is_pica FROM antrain_pic_action"
           . " WHERE antrain_objective_id = '$antrain_objective_id'"
           . " AND employee_id = '$employee_id'"
           . " AND pica_id = '$actionplan_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($root_actionplan_id,$is_pica)=$db->fetchRow($result);
         $_SESSION["pica_actionplan_id"][$root_actionplan_id] = 1;
         if($is_pica==0) {
            return $root_actionplan_id;
         } else {
            return $this->getpicaroot($antrain_objective_id,$employee_id,$root_actionplan_id);
         }
      }
      return $actionplan_id;
   }
   
   function getActionPlanReportUpdate($antrain_objective_id,$employee_id,$report_month=0) {
      $psid = $_SESSION["antrain_psid"];
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
      
      $sql = "SELECT start_dttm,stop_dttm,closing_dttm,now() FROM antrain_session WHERE psid = '$psid'";
      $result = $db->query($sql);
      list($start_dttm,$stop_dttm,$closing_dttm,$now_dttm)=$db->fetchRow($result);
      
      $vardvtg = "";
      $vardvach = "";
      $varzone = "";
      $vardvaptxt = "";
      $vardvpica_root = "";
      $vardvpica_improve = "";
      $vardvpica_month = "";
      
      $xmonth = 0;
      $current_objective_ap_count = 0;
      $sql = "SELECT b.antrain_perspective_code,a.antrain_objective_no,a.antrain_objective_text"
           . " FROM antrain_objective a"
           . " LEFT JOIN antrain_perspective b USING(antrain_perspective_id)"
           . " WHERE a.antrain_objective_id = '$antrain_objective_id'";
      $result = $db->query($sql);
      list($antrain_perspective_code,$antrain_objective_no,$antrain_objective_text)=$db->fetchRow($result);
      
      $sql = "SELECT COUNT(*) FROM antrain_pic_action"
           . " WHERE antrain_objective_id = '$antrain_objective_id'"
           . " AND employee_id = '$employee_id'"
           . " AND is_pica = '0'"
           . " AND month_id = '$report_month'";
      $rcnt = $db->query($sql);
      list($apcnt)=$db->fetchRow($rcnt);
      
      $tooltip = "";
      
      $ap_need_submission = 0;
      $ap_need_approval = 0;
      $ap_submit_dttm = "0000-00-00 00:00:00";
      $ap_approval_dttm = "0000-00-00 00:00:00";
      
      //////////// query all action plan first //////////////////////////
      
      
      
      $sql = "SELECT actionplan_group_id"
           . " FROM antrain_pic_action"
           . " WHERE antrain_objective_id = '$antrain_objective_id'"
           . " AND employee_id = '$employee_id'"
           . " AND is_pica = '0'"
           . " AND month_id = '$report_month'"
           . " AND actionplan_group_id > '0'"
           . " ORDER BY month_id,order_no";
      $resultx = $db->query($sql);
      $group_arr = array();
      if($db->getRowsNum($resultx)>0) {
         while(list($actionplan_group_id)=$db->fetchRow($resultx)) {
            $group_arr[$actionplan_group_id] = 1;
         }
      }
      
      $sql = "SELECT actionplan_id,actionplan_text,target_text,current_achievement,order_no,approval_st,submit_dttm,approval1_dttm,approval1_employee_id,"
           . "pica_id,is_pica,target_achievement,report_approval_st,month_id,allow_carry_over,repeat_type,actionplan_group_id,root_cause,improvement_text"
           . " FROM antrain_pic_action"
           . " WHERE antrain_objective_id = '$antrain_objective_id'"
           . " AND employee_id = '$employee_id'"
           //. " AND is_pica = '0'"
           . " AND month_id = '$report_month'"
           . " ORDER BY month_id,order_no";
      $resultx = $db->query($sql);
      _debuglog($sql);
      $aparr = array();
      $aptextarr = array();
      $apno = array();
      $no = 0;
      
      $ttl_target = 0;
      $ttl_ach = 0;
      
      if($db->getRowsNum($resultx)>0) {
         while(list($actionplan_id,$actionplan_text,$target_text,$current_achievement,$order_no,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,
                    $pica_id,$is_pica,$target_achievement,$report_approval_st,$month_id,$allow_carry_over,$repeat_type,$actionplan_group_id,$pica_root_cause,$pica_improvement)=$db->fetchRow($resultx)) {
            
            if($group_arr[$actionplan_id]==1) continue;
            
            $ttl_target = bcadd($ttl_target,$target_achievement);
            $ttl_ach = bcadd($ttl_ach,$current_achievement);
            
            $aparr[$month_id][$no] = array($actionplan_id,$actionplan_text,$target_text,$current_achievement,$order_no,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,$pica_id,$is_pica,$target_achievement,$report_approval_st,$month_id);
            $aptextarr[$no] = array($actionplan_id,$actionplan_text,$target_text,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,$month_id,$target_achievement,$is_pica,$current_achievement);
            $apno[$actionplan_id] = $no;
               
               if($pica_id==0) {
                  $pica_root_cause = "-";
                  $pica_improvement = "-";
                  $pica_month_text = "-";
               } else {
                  $sql = "SELECT month_id FROM antrain_pic_action"
                       . " WHERE employee_id = '$employee_id'"
                       . " AND antrain_objective_id = '$antrain_objective_id'"
                       . " AND actionplan_id = '$pica_id'";
                  $rpica = $db->query($sql);
                  if($db->getRowsNum($rpica)>0) {
                     list($pica_month_id)=$db->fetchRow($rpica);
                     $pica_month_text = $xocp_vars["month_year"][$pica_month_id];
                  } else {
                     $pica_month_text = "-";
                  }
               }
               
               $vardvpica_root .= "<div style='border-bottom:1px solid #bbb;' ".($pica_root_cause=="-"?"style='color:#555;'":"")." onmousemove='show_ap_tooltip(\"$antrain_objective_id\",\"$actionplan_id\",this,event);' onmouseover='mouseover_aptext".($is_pica==1?"pica":"")."(\"$no\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);' onmouseout='mouseout_aptext".($is_pica==1?"pica":"")."(\"$no\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);' class='appicatext".($is_pica==1?"pica":"")."' id='dvpica_root_${antrain_objective_id}_${actionplan_id}'>"
                                . htmlentities($pica_root_cause)."&nbsp;"
                                . "</div>";
               $vardvpica_improve .= "<div style='border-bottom:1px solid #bbb;' ".($pica_root_cause=="-"?"style='color:#555;'":"")." onmousemove='show_ap_tooltip(\"$antrain_objective_id\",\"$actionplan_id\",this,event);' onmouseover='mouseover_aptext".($is_pica==1?"pica":"")."(\"$no\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);' onmouseout='mouseout_aptext".($is_pica==1?"pica":"")."(\"$no\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);' class='appicatext".($is_pica==1?"pica":"")."' id='dvpica_improve_${antrain_objective_id}_${actionplan_id}'>"
                                   . htmlentities($pica_improvement)."&nbsp;"
                                   . "</div>";
               $vardvpica_month .= "<div style='border-bottom:1px solid #bbb;' ".($pica_root_cause=="-"?"style='color:#555;'":"")." onmousemove='show_ap_tooltip(\"$antrain_objective_id\",\"$actionplan_id\",this,event);' onmouseover='mouseover_aptext".($is_pica==1?"pica":"")."(\"$no\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);' onmouseout='mouseout_aptext".($is_pica==1?"pica":"")."(\"$no\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);' class='appicatext".($is_pica==1?"pica":"")."' id='dvpica_month_${antrain_objective_id}_${actionplan_id}'>"
                                . htmlentities($pica_month_text)."&nbsp;"
                                . "</div>";
               
            
            
            $no++;
         }
      }
      
      $apcnt = $no;
      
      ///////////////////////////////////////////////////////////////////
      
      
      foreach($aptextarr as $no=>$v) {
         list($actionplan_id,$actionplan_text,$target_text,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,$month_id,$target_achievement,$is_pica,$current_achievement)=$v;
         $vardvaptxt .= "<div style='border-bottom:1px solid #bbb;' onmousemove='show_ap_tooltip(\"$antrain_objective_id\",\"$actionplan_id\",this,event);' onmouseover='mouseover_aptext".($is_pica==1?"pica":"")."(\"$no\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);' onmouseout='mouseout_aptext".($is_pica==1?"pica":"")."(\"$no\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);' class='aptext".($is_pica==1?"pica":"")."' id='dvap_${antrain_objective_id}_${actionplan_id}'>";
         if($employee_id==$self_employee_id&&($approval_st=="new"||$approval_st=="return")) {
            $vardvaptxt .= "<span class='xlnk' onclick='edit_actionplan(\"$actionplan_id\",\"$antrain_objective_id\",this,event);' id='spap_${antrain_objective_id}_${actionplan_id}'>$actionplan_text</span>";
         } else if($approval1_employee_id==$self_employee_id&&$approval_st=="approval1") {
            $vardvaptxt .= "<span class='xlnk' onclick='edit_actionplan(\"$actionplan_id\",\"$antrain_objective_id\",this,event);' id='spap_${antrain_objective_id}_${actionplan_id}'>$actionplan_text</span>";
         } else {
            $vardvaptxt .= "<span id='spap_${antrain_objective_id}_${actionplan_id}'>$actionplan_text</span>";
         }
         $vardvaptxt .= "</div>";
         
         $vardvtg .= "<div style='border-bottom:1px solid #bbb;' onmousemove='show_ap_tooltip(\"$antrain_objective_id\",\"$actionplan_id\",this,event);' onmouseover='mouseover_aptext".($is_pica==1?"pica":"")."(\"$no\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);' onmouseout='mouseout_aptext".($is_pica==1?"pica":"")."(\"$no\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);' class='aptgtext".($is_pica==1?"pica":"")."' id='dvtg_${antrain_objective_id}_${actionplan_id}'>"
                   . $target_text."&nbsp;"
                   . "</div>";
         
         $tooltip .= "\n<div style='display:none;' id='intooltip_${antrain_objective_id}_${actionplan_id}'>"
                   . "<div>"
                   . "<table style='min-width:300px;' class='aptooltip'>"
                   . "<colgroup><col width='80'/><col/></colgroup>"
                   . "<tbody>"
                   . ($is_pica==1?"<tr><td style='text-align:center;background-color:#fff;border:1px solid #bbb;' colspan='2'>PICA</td></tr>":"")
                   . "<tr><td># : </td><td>$antrain_perspective_code$antrain_objective_no</td></tr>"
                   . "<tr><td>SO : </td><td>$antrain_objective_text</td></tr>"
                   . "<tr><td>Month : </td><td>".$xocp_vars["month_year"][$month_id]."</td></tr>"
                   . "<tr><td>Action Plan : </td><td>$actionplan_text</td></tr>"
                   . "<tr><Td>Target : </td><td>$target_text</td></tr>"
                   . "<tr><Td>Target Ach. : </td><td>".toMoney($target_achievement)." %</td></tr>"
                   . "<tr><Td>Ach. : </td><td>".toMoney($current_achievement)." %</td></tr>"
                   . "</tbody></table>"
                   . "</div>"
                   . "</div>";
      }
      
      
      for($i=1;$i<=12;$i++) {
         $vardvap = "dvapx_${i}";
         $$vardvap = "";
         for($j=0;$j<$apcnt;$j++) {
            if(isset($aparr[$i][$j])) {
               list($actionplan_id,$actionplan_text,$target_text,$current_achievement,$order_no,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,$pica_id,$is_pica,$target_achievement,$report_approval_st,$month_id)=$aparr[$i][$j];
               $$vardvap .= "<div style='border-bottom:1px solid #bbb;'"
                          . " onmousemove='show_ap_tooltip(\"$antrain_objective_id\",\"$actionplan_id\",this,event);'"
                          . " onmouseover='mouseover_aptext".($is_pica==1?"pica":"")."(\"$j\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);'"
                          . " onmouseout='mouseout_aptext".($is_pica==1?"pica":"")."(\"$j\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);'"
                          . " class='apblockreport".($is_pica==1?"pica":"")."'>"
                          . ($closing_dttm>=$now_dttm?"<span class='xlnk' onclick='edit_report_ap(\"$antrain_objective_id\",\"$actionplan_id\",\"$no\",this,event);'>".toMoney($current_achievement)."</span>":toMoney($current_achievement))
                          . " %</div>";
            } else if(isset($picaarr[$i][$j])) {
               list($actionplan_id,$actionplan_text,$target_text,$current_achievement,$order_no,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,$pica_id,$is_pica,$target_achievement,$report_approval_st,$month_id)=$picaarr[$i][$j];
               $$vardvap .= "<div style='border-bottom:1px solid #bbb;'"
                          . " onmousemove='show_ap_tooltip(\"$antrain_objective_id\",\"$actionplan_id\",this,event);'"
                          . " onmouseover='mouseover_aptext(\"$j\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);'"
                          . " onmouseout='mouseout_aptext(\"$j\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);'"
                          . " class='apblockreportpica'>"
                          . ($closing_dttm>=$now_dttm?"<span class='xlnk' onclick='edit_report_ap(\"$antrain_objective_id\",\"$actionplan_id\",\"$no\",this,event);'>".toMoney($current_achievement)."</span>":toMoney($current_achievement))
                          . " %</div>";
            } else if(isset($gaparr[$i][$j])) {
               list($actionplan_id,$actionplan_text,$target_text,$current_achievement,$order_no,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,$pica_id,$is_pica,$target_achievement,$report_approval_st,$month_id,$actionplan_group_id)=$gaparr[$i][$j];
               $$vardvap .= "<div style='border-bottom:1px solid #bbb;'"
                          . " onmousemove='show_ap_tooltip(\"$antrain_objective_id\",\"$actionplan_id\",this,event);'"
                          . " onmouseover='mouseover_aptext(\"$j\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);'"
                          . " onmouseout='mouseout_aptext(\"$j\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);'"
                          . " class='apblockreport'>"
                          . ($closing_dttm>=$now_dttm?"<span class='xlnk' onclick='edit_report_ap(\"$antrain_objective_id\",\"$actionplan_id\",\"$no\",this,event);'>".toMoney($current_achievement)."</span>":toMoney($current_achievement))
                          . " %</div>";
            
            } else {
               $$vardvap .= "<div style='border-bottom:1px solid #bbb;'"
                          . " onmouseover='mouseover_aptext(\"$j\",\"$antrain_objective_id\",0,this,event);'"
                          . " onmouseout='mouseout_aptext(\"$j\",\"$antrain_objective_id\",0,this,event);'"
                          . " class='apblock_empty'>&nbsp;</div>";
            }
         }
      }
      
      $vardvap = "dvapx_".$_SESSION["antrain_month"];
      if(trim($$vardvap)=="") {
         //$$vardvap = "<div class='apblock_empty'>&nbsp;</div>";
         //$vardvaptxt = "<div class='aptext'>&nbsp;</div>";
      }
      
      _debuglog("$status_approval==0&&$employee_id==$self_employee_id&&$report_mode==FALSE");
      
      if($status_approval==0&&$employee_id==$self_employee_id&&$report_mode==FALSE) {
         //$vardvaptxt .= "<div id='dvaddap_${antrain_objective_id}' style='padding:3px;text-align:right;'>[<span onclick='new_actionplan(\"$antrain_objective_id\",this,event);' class='ylnk'>add</span>]</div>";
      }
      
      
      if($apcnt==0) return "EMPTY";
      
      return array($antrain_objective_id,$vardvaptxt,$vardvtg,$dvapx_1,$dvapx_2,$dvapx_3,$dvapx_4,$dvapx_5,$dvapx_6,$dvapx_7,$dvapx_8,$dvapx_9,$dvapx_10,$dvapx_11,$dvapx_12,$tooltip,$vardvpica_root,$vardvpica_improve,$vardvpica_month);
   }
   
   function getActionPlanUpdate($antrain_objective_id,$employee_id,$report_mode=FALSE,$month=0) {
      $psid = $_SESSION["antrain_psid"];
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
      
      
      $sql = "SELECT start_dttm,stop_dttm,closing_dttm,now() FROM antrain_session WHERE psid = '$psid'";
      $result = $db->query($sql);
      list($start_dttm,$stop_dttm,$closing_dttm,$now_dttm)=$db->fetchRow($result);
      
      
      $vardvtg = "";
      $vardvach = "";
      $varzone = "";
      $vardvaptxt = "";
      $vardvpica_root = "";
      $vardvpica_improve = "";
      $vardvpica_month = "";
      
      $xmonth = 0;
      $current_objective_ap_count = 0;
      $sql = "SELECT b.antrain_perspective_code,a.antrain_objective_no,a.antrain_objective_text"
           . " FROM antrain_objective a"
           . " LEFT JOIN antrain_perspective b USING(antrain_perspective_id)"
           . " WHERE a.antrain_objective_id = '$antrain_objective_id'";
      $result = $db->query($sql);
      
      list($antrain_perspective_code,$antrain_objective_no,$antrain_objective_text)=$db->fetchRow($result);
      
      $sql = "SELECT COUNT(*) FROM antrain_pic_action"
           . " WHERE antrain_objective_id = '$antrain_objective_id'"
           . " AND employee_id = '$employee_id'"
           . " AND is_pica = '0'"
           . " AND actionplan_group_id = '0'";
      $rcnt = $db->query($sql);
      list($apcnt)=$db->fetchRow($rcnt);
      
      $tooltip = "";
      
      $ap_need_submission = 0;
      $ap_need_approval = 0;
      $ap_submit_dttm = "0000-00-00 00:00:00";
      $ap_approval_dttm = "0000-00-00 00:00:00";
      
      //////////// query all action plan first //////////////////////////
      
      $sql = "SELECT actionplan_id,actionplan_text,target_text,current_achievement,order_no,approval_st,submit_dttm,approval1_dttm,approval1_employee_id,"
           . "pica_id,is_pica,target_achievement,report_approval_st,month_id,repeat_type"
           . " FROM antrain_pic_action"
           . " WHERE antrain_objective_id = '$antrain_objective_id'"
           . " AND employee_id = '$employee_id'"
           . " AND is_pica = '0'"
           . " AND actionplan_group_id = '0'"
           . " ORDER BY month_id,order_no";
      $resultx = $db->query($sql);
      $aparr = array();
      $aptextarr = array();
      $apno = array();
      $no = 0;
      if($db->getRowsNum($resultx)>0) {
         while(list($actionplan_id,$actionplan_text,$target_text,$current_achievement,$order_no,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,
                    $pica_id,$is_pica,$target_achievement,$report_approval_st,$month_id,$repeat_type)=$db->fetchRow($resultx)) {
            $aparr[$month_id][$no] = array($actionplan_id,$actionplan_text,$target_text,$current_achievement,$order_no,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,$pica_id,$is_pica,$target_achievement,$report_approval_st,$month_id);
            $aptextarr[$no] = array($actionplan_id,$actionplan_text,$target_text,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,$month_id,$target_achievement,$current_achievement);
            $apno[$actionplan_id] = $no;
            $no++;
            
            if($repeat_type==0) {
               /// fix
               $sql = "UPDATE antrain_pic_action SET allow_carry_over = '1'"
                    . " WHERE antrain_objective_id = '$antrain_objective_id'"
                    . " AND employee_id = '$employee_id'"
                    . " AND actionplan_id = '$actionplan_id'";
               $db->query($sql);
            
            }
            
         }
      }
      
      $gaparr = array();
      $gaptextarr = array();
      $gapno = array();
      foreach($apno as $actionplan_group_id=>$gno) {
         $sql = "SELECT actionplan_id,actionplan_text,target_text,current_achievement,order_no,approval_st,submit_dttm,approval1_dttm,approval1_employee_id,"
              . "pica_id,is_pica,target_achievement,report_approval_st,month_id"
              . " FROM antrain_pic_action"
              . " WHERE antrain_objective_id = '$antrain_objective_id'"
              . " AND employee_id = '$employee_id'"
              . " AND is_pica = '0'"
              . " AND actionplan_group_id = '$actionplan_group_id'"
              . " ORDER BY month_id,order_no";
         $resultx = $db->query($sql);
         if($db->getRowsNum($resultx)>0) {
            while(list($actionplan_id,$actionplan_text,$target_text,$current_achievement,$order_no,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,
                       $pica_id,$is_pica,$target_achievement,$report_approval_st,$month_id)=$db->fetchRow($resultx)) {
               $gaparr[$month_id][$gno] = array($actionplan_id,$actionplan_text,$target_text,$current_achievement,$order_no,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,$pica_id,$is_pica,$target_achievement,$report_approval_st,$month_id,$actionplan_group_id);
               $gaptextarr[$gno] = array($actionplan_id,$actionplan_text,$target_text,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,$month_id,$target_achievement,$actionplan_group_id);
               $gapno[$actionplan_id] = $gno;
               
               /// fix
               $sql = "UPDATE antrain_pic_action SET allow_carry_over = '0'"
                    . " WHERE antrain_objective_id = '$antrain_objective_id'"
                    . " AND employee_id = '$employee_id'"
                    . " AND actionplan_id = '$actionplan_id'";
               $db->query($sql);
               
               /// unset to display
               unset($aparr[$month_id][$gno]);
               
               $tooltip .= "\n<div style='display:none;' id='intooltip_${antrain_objective_id}_${actionplan_id}'>"
                         . "<div>"
                         . "<table class='aptooltip'><tbody>"
                         . "<tr><td>SO : </td><td>$antrain_objective_text</td></tr>"
                         . "<tr><td>Month : </td><td>".$xocp_vars["month_year"][$month_id]."</td></tr>"
                         . "<tr><td>Action Plan : </td><td>$actionplan_text</td></tr>"
                         . "<tr><Td>Target : </td><td>$target_text</td></tr>"
                         . "<tr><Td>Target Ach. : </td><td>".toMoney($target_achievement)." %</td></tr>"
                         . "<tr><Td>Ach. : </td><td>".toMoney($current_achievement)." %</td></tr>"
                         . "</tbody></table>"
                         . "</div>"
                         . "</div>";
               
            }
         }
      }
      
      
      $sql = "SELECT actionplan_id,actionplan_text,target_text,current_achievement,order_no,approval_st,submit_dttm,approval1_dttm,approval1_employee_id,"
           . "pica_id,is_pica,target_achievement,report_approval_st,month_id,root_cause"
           . " FROM antrain_pic_action"
           . " WHERE antrain_objective_id = '$antrain_objective_id'"
           . " AND employee_id = '$employee_id'"
           . " AND is_pica = '1'"
           . " AND actionplan_group_id = '0'"
           . " ORDER BY month_id,order_no";
      $resultx = $db->query($sql);
      if($db->getRowsNum($resultx)>0) {
         while(list($actionplan_id,$actionplan_text,$target_text,$current_achievement,$order_no,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,
                    $pica_id,$is_pica,$target_achievement,$report_approval_st,$month_id,$root_cause)=$db->fetchRow($resultx)) {
            $root_actionplan_id = $this->getpicaroot($antrain_objective_id,$employee_id,$actionplan_id);
            if(1) {
               $no = $apno[$root_actionplan_id];
               $picaarr[$month_id][$no] = array($actionplan_id,$actionplan_text,$target_text,$current_achievement,$order_no,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,$pica_id,$is_pica,$target_achievement,$report_approval_st,$month_id);
               
               $sql = "SELECT root_cause FROM antrain_pic_action"
                    . " WHERE antrain_objective_id = '$antrain_objective_id'"
                    . " AND employee_id = '$employee_id'"
                    . " AND pica_id = '$actionplan_id'";
               $rrc = $db->query($sql);
               list($root_cause)=$db->fetchRow($rrc);
               
               $tooltip .= "\n<div style='display:none;' id='intooltip_${antrain_objective_id}_${actionplan_id}'>"
                         . "<div>"
                         . "<table class='aptooltip'><tbody>"
                         . "<tr><td style='text-align:center;background-color:#fff;border:1px solid #bbb;' colspan='2'>PICA</td></tr>"
                         . "<tr><td>SO : </td><td>$antrain_objective_text</td></tr>"
                         . "<tr><td>Month : </td><td>".$xocp_vars["month_year"][$month_id]."</td></tr>"
                         . "<tr><td>Action Plan : </td><td>$actionplan_text</td></tr>"
                         . "<tr><Td>Improvement : </td><td>$target_text</td></tr>"
                         . "<tr><Td>Root Cause : </td><td>$root_cause</td></tr>"
                         . "<tr><Td>Target : </td><td>".toMoney($target_achievement)." %</td></tr>"
                         . "<tr><Td>Ach. : </td><td>".toMoney($current_achievement)." %</td></tr>"
                         . "</tbody></table>"
                         . "</div>"
                         . "</div>";
               
            }
         }
      }
      
      ///////////////////////////////////////////////////////////////////
      
      
      foreach($aptextarr as $no=>$v) {
         list($actionplan_id,$actionplan_text,$target_text,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,$month_id,$target_achievement,$current_achievement)=$v;
         $vardvaptxt .= "<div onmousemove='show_ap_tooltip(\"$antrain_objective_id\",\"$actionplan_id\",this,event);' onmouseover='mouseover_aptext(\"$no\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);' onmouseout='mouseout_aptext(\"$no\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);' class='aptext' id='dvap_${antrain_objective_id}_${actionplan_id}'>";
         if($employee_id==$self_employee_id&&($approval_st=="new"||$approval_st=="return")) {
            $vardvaptxt .= "<span class='xlnk' onclick='edit_actionplan(\"$actionplan_id\",\"$antrain_objective_id\",this,event);' id='spap_${antrain_objective_id}_${actionplan_id}'>$actionplan_text</span>";
         } else if($approval1_employee_id==$self_employee_id&&$approval_st=="approval1") {
            $vardvaptxt .= "<span class='xlnk' onclick='edit_actionplan(\"$actionplan_id\",\"$antrain_objective_id\",this,event);' id='spap_${antrain_objective_id}_${actionplan_id}'>$actionplan_text</span>";
         } else {
            $vardvaptxt .= "<span id='spap_${antrain_objective_id}_${actionplan_id}'>$actionplan_text</span>";
         }
         $vardvaptxt .= "</div>";
         
         $vardvtg .= "<div onmousemove='show_ap_tooltip(\"$antrain_objective_id\",\"$actionplan_id\",this,event);' onmouseover='mouseover_aptext(\"$no\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);' onmouseout='mouseout_aptext(\"$no\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);' class='aptgtext' id='dvtg_${antrain_objective_id}_${actionplan_id}'>"
                   . $target_text."&nbsp;"
                   . "</div>";
         
         $tooltip .= "\n<div style='display:none;' id='intooltip_${antrain_objective_id}_${actionplan_id}'>"
                   . "<div>"
                   . "<table class='aptooltip'><tbody>"
                   . ($is_pica==1?"<tr><td style='text-align:center;background-color:#fff;border:1px solid #bbb;' colspan='2'>PICA</td></tr>":"")
                   . "<tr><td>SO : </td><td>$antrain_objective_text</td></tr>"
                   . "<tr><td>Month : </td><td>".$xocp_vars["month_year"][$month_id]."</td></tr>"
                   . "<tr><td>Action Plan : </td><td>$actionplan_text</td></tr>"
                   . "<tr><Td>Target : </td><td>$target_text</td></tr>"
                   . "<tr><Td>Target Ach. : </td><td>".toMoney($target_achievement)." %</td></tr>"
                   . "<tr><Td>Ach. : </td><td>".toMoney($current_achievement)." %</td></tr>"
                   . "</tbody></table>"
                   . "</div>"
                   . "</div>";
      }
      
      
      for($i=1;$i<=12;$i++) {
         $vardvap = "dvapx_${i}";
         $$vardvap = "";
         for($j=0;$j<$apcnt;$j++) {
            if(isset($aparr[$i][$j])) {
               list($actionplan_id,$actionplan_text,$target_text,$current_achievement,$order_no,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,$pica_id,$is_pica,$target_achievement,$report_approval_st,$month_id)=$aparr[$i][$j];
               $$vardvap .= "<div"
                          . " onmousemove='show_ap_tooltip(\"$antrain_objective_id\",\"$actionplan_id\",this,event);'"
                          . " onmouseover='mouseover_aptext(\"$j\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);'"
                          . " onmouseout='mouseout_aptext(\"$j\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);'"
                          . " class='apblock_${approval_st}'><div style='text-align:center;color:black;'>"
                          . ($current_achievement==$target_achievement&&$report_approval_st=="final"?"&nbsp;<img src='".XOCP_SERVER_SUBDIR."/images/checkmark.png'/>&nbsp;":"&nbsp;")
                          . "</div></div>";
            } else if(isset($picaarr[$i][$j])) {
               list($actionplan_id,$actionplan_text,$target_text,$current_achievement,$order_no,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,$pica_id,$is_pica,$target_achievement,$report_approval_st,$month_id)=$picaarr[$i][$j];
               $$vardvap .= "<div"
                          . " onmousemove='show_ap_tooltip(\"$antrain_objective_id\",\"$actionplan_id\",this,event);'"
                          . " onmouseover='mouseover_aptext(\"$j\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);'"
                          . " onmouseout='mouseout_aptext(\"$j\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);'"
                          . " class='apblockpica_${approval_st}'><div style='text-align:center;color:black;'>"
                          . ($current_achievement==$target_achievement&&$report_approval_st=="final"?"&nbsp;<img src='".XOCP_SERVER_SUBDIR."/images/checkmark.png'/>&nbsp;":"&nbsp;")
                          . "</div></div>";
            } else if(isset($gaparr[$i][$j])) {
               list($actionplan_id,$actionplan_text,$target_text,$current_achievement,$order_no,$approval_st,$submit_dttm,$approval1_dttm,$approval1_employee_id,$pica_id,$is_pica,$target_achievement,$report_approval_st,$month_id,$actionplan_group_id)=$gaparr[$i][$j];
               $$vardvap .= "<div"
                          . " onmousemove='show_ap_tooltip(\"$antrain_objective_id\",\"$actionplan_id\",this,event);'"
                          . " onmouseover='mouseover_aptext(\"$j\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);'"
                          . " onmouseout='mouseout_aptext(\"$j\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);'"
                          . " class='apblock_${approval_st}'><div style='text-align:center;color:black;'>"
                          . ($current_achievement==$target_achievement&&$report_approval_st=="final"?"&nbsp;<img src='".XOCP_SERVER_SUBDIR."/images/checkmark.png'/>&nbsp;":"&nbsp;")
                          . "</div></div>";
            
            } else {
               $$vardvap .= "<div"
                          . " onmouseover='mouseover_aptext(\"$j\",\"$antrain_objective_id\",0,this,event);'"
                          . " onmouseout='mouseout_aptext(\"$j\",\"$antrain_objective_id\",0,this,event);'"
                          . " class='apblock_empty'>&nbsp;</div>";
            }
         }
      }
      
      $vardvap = "dvapx_".$_SESSION["antrain_month"];
      if(trim($$vardvap)=="") {
         //$$vardvap = "<div class='apblock_empty'>&nbsp;</div>";
         //$vardvaptxt = "<div class='aptext'>&nbsp;</div>";
      }
      
      if($closing_dttm>=$now_dttm&&$status_approval==0&&$employee_id==$self_employee_id&&$report_mode==FALSE) {
         $vardvaptxt .= "<div id='dvaddap_${antrain_objective_id}' style='padding:3px;text-align:right;'>[<span onclick='new_actionplan(\"$antrain_objective_id\",this,event);' class='ylnk'>add</span>]</div>";
      }
      
      $sql = "SELECT approval_st,report_approval_st,is_pica FROM antrain_pic_action"
           . " WHERE psid = '$psid' AND employee_id = '$employee_id' AND actionplan_group_id = '0'";
      $result = $db->query($sql);
      $ap_need_submission = 0;
      $status_new = 0;
      $status_return = 0;
      $status_approval = 0;
      $status_implementation = 0;
      if($db->getRowsNum($result)>0) {
         while(list($ap_status_cd,$report_approval_st,$is_pica)=$db->fetchRow($result)) {
            
            if($is_pica==0) {
               if($ap_status_cd=="return") {
                  $status_return++;
                  $ap_need_submission++;
               }
               if($ap_status_cd=="new") {
                  $status_new++;
                  $ap_need_submission++;
               }
               if($ap_status_cd=="approval1") {
                  $status_approval1++;
                  $ap_need_approval++;
               }
               if($ap_status_cd=="implementation") {
                  $status_implementation++;
               }
            }
         }
      }
      
      return array($antrain_objective_id,$vardvaptxt,$vardvtg,$dvapx_1,$dvapx_2,$dvapx_3,$dvapx_4,$dvapx_5,$dvapx_6,$dvapx_7,$dvapx_8,$dvapx_9,$dvapx_10,$dvapx_11,$dvapx_12,$tooltip,$vardvpica_root,$vardvpica_improve,$vardvpica_month,$ap_need_submission);
   }
   
   
   function backup_getActionPlanUpdate($antrain_objective_id,$employee_id,$report_mode=FALSE,$month=0) {
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
      $sql = "SELECT b.antrain_perspective_code,a.antrain_objective_no,a.antrain_objective_text"
           . " FROM antrain_objective a"
           . " LEFT JOIN antrain_perspective b USING(antrain_perspective_id)"
           . " WHERE a.antrain_objective_id = '$antrain_objective_id'";
      $result = $db->query($sql);
      list($antrain_perspective_code,$antrain_objective_no,$antrain_objective_text)=$db->fetchRow($result);
      
      $sql = "SELECT COUNT(*) FROM antrain_pic_action"
           . " WHERE antrain_objective_id = '$antrain_objective_id'"
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
            if($_SESSION["antrain_month"]!=$i) {
               continue;
            }
         }
         
         $vardvap = "dvapx_${i}";
         $$vardvap = "";
         $sql = "SELECT actionplan_id,actionplan_text,target_text,current_achievement,order_no,approval_st,submit_dttm,approval1_dttm,approval1_employee_id,"
              . "pica_id,is_pica,target_achievement,report_approval_st"
              . " FROM antrain_pic_action"
              . " WHERE antrain_objective_id = '$antrain_objective_id'"
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
                           list($pica_idx,$pica_approval_st,$pica_actionplan_text,$pica_target_text,$pica_root_cause,$pica_target_achievement,$pica_current_achievement,$pica_antrain_objective_text)=$pica_block[$indent][$i];
                           $tooltip .= "\n<div style='display:none;' id='intooltip_${antrain_objective_id}_${pica_idx}'>"
                                     . "<div>"
                                     . "<table class='aptooltip'><tbody>"
                                     . "<tr><td style='text-align:center;background-color:#fff;border:1px solid #bbb;' colspan='2'>PICA</td></tr>"
                                     . "<tr><td>SO : </td><td>$pica_antrain_objective_text</td></tr>"
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
                                      . " onmousemove='show_ap_tooltip(\"$antrain_objective_id\",\"$pica_idx\",this,event);'"
                                      . " onmouseover='mouseover_aptext(\"$vindent\",\"$antrain_objective_id\",\"$pica_idx\",this,event);'"
                                      . " onmouseout='mouseout_aptext(\"$vindent\",\"$antrain_objective_id\",\"$pica_idx\",this,event);'"
                                      . " class='apblockpica_${pica_approval_st}'><div>&nbsp;</div></div>";
                        } else {
                           $$vardvap .= "<div"
                                      . " onmouseover='mouseover_aptext(\"$vindent\",\"$antrain_objective_id\",0,this,event);'"
                                      . " onmouseout='mouseout_aptext(\"$vindent\",\"$antrain_objective_id\",0,this,event);'"
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
                                . " onmousemove='show_ap_tooltip(\"$antrain_objective_id\",\"$actionplan_id\",this,event);'"
                                . " onmouseover='mouseover_aptext".($is_pica==1?"pica":"")."(\"$no\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);'"
                                . " onmouseout='mouseout_aptext".($is_pica==1?"pica":"")."(\"$no\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);'"
                                . " class='apblockreport".($is_pica==1?"pica":"")."'><span class='xlnk' onclick='edit_report_ap(\"$antrain_objective_id\",\"$actionplan_id\",\"$no\",this,event);'>".toMoney($current_achievement)."</span> %</div>";
                  } else {
                     $$vardvap .= "<div"
                                . " onmousemove='show_ap_tooltip(\"$antrain_objective_id\",\"$actionplan_id\",this,event);'"
                                . " onmouseover='mouseover_aptext".($is_pica==1?"pica":"")."(\"$no\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);'"
                                . " onmouseout='mouseout_aptext".($is_pica==1?"pica":"")."(\"$no\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);'"
                                . " class='apblockreport".($is_pica==1?"pica":"")."'>".toMoney($current_achievement)." %</div>";
                  
                  }
               } else {
                  if($is_pica==0) {
                     $$vardvap .= "<div"
                                . " onmousemove='show_ap_tooltip(\"$antrain_objective_id\",\"$actionplan_id\",this,event);'"
                                . " onmouseover='mouseover_aptext(\"$no\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);'"
                                . " onmouseout='mouseout_aptext(\"$no\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);'"
                                . " class='apblock_${approval_st}'><div>&nbsp;99</div></div>";
                  }
               }
               
               
               if($is_pica==0) {
                  $current_objective_ap_count++;
               }
               
               
               if($pica_id>0) {
                  $sqlx = "SELECT actionplan_text,target_text,root_cause,target_achievement,current_achievement,approval_st,month_id FROM antrain_pic_action"
                       . " WHERE antrain_objective_id = '$antrain_objective_id'"
                       . " AND employee_id = '$employee_id'"
                       . " AND actionplan_id = '$pica_id'";
                  $rpicax = $db->query($sqlx);
               
                  if($db->getRowsNum($rpicax)>0) {
                     list($pica_actionplan_text,$pica_target_text,$pica_root_cause,$pica_target_achievement,$pica_current_achievement,$pica_approval_st,$pica_month_idxx)=$db->fetchRow($rpicax);
                     $pica_block[$current_objective_ap_count][$pica_month_idxx] = array($pica_id,$pica_approval_st,$pica_actionplan_text,$pica_target_text,$pica_root_cause,$pica_target_achievement,$pica_current_achievement,$antrain_objective_text);
                  }
               }
               
               
               
               if($is_pica==0) {
               
                  $vardvaptxt .= "<div onmousemove='show_ap_tooltip(\"$antrain_objective_id\",\"$actionplan_id\",this,event);' onmouseover='mouseover_aptext(\"$no\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);' onmouseout='mouseout_aptext(\"$no\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);' class='aptext' id='dvap_${antrain_objective_id}_${actionplan_id}'>";
                  if($employee_id==$self_employee_id&&($approval_st=="new"||$approval_st=="return")) {
                     $vardvaptxt .= "<span class='xlnk' onclick='edit_actionplan(\"$actionplan_id\",\"$antrain_objective_id\",this,event);' id='spap_${antrain_objective_id}_${actionplan_id}'>$actionplan_text</span>";
                  } else if($approval1_employee_id==$self_employee_id&&$approval_st=="approval1") {
                     $vardvaptxt .= "<span class='xlnk' onclick='edit_actionplan(\"$actionplan_id\",\"$antrain_objective_id\",this,event);' id='spap_${antrain_objective_id}_${actionplan_id}'>$actionplan_text</span>";
                  } else {
                     $vardvaptxt .= "<span id='spap_${antrain_objective_id}_${actionplan_id}'>$actionplan_text</span>";
                  }
                  $vardvaptxt .= "</div>";
               } else if($report_mode==TRUE) {
                  $vardvaptxt .= "<div onmousemove='show_ap_tooltip(\"$antrain_objective_id\",\"$actionplan_id\",this,event);' onmouseover='mouseover_aptext".($is_pica==1?"pica":"")."(\"$no\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);' onmouseout='mouseout_aptext".($is_pica==1?"pica":"")."(\"$no\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);' class='aptext".($is_pica==1?"pica":"")."' id='dvap_${antrain_objective_id}_${actionplan_id}'>";
                  if($employee_id==$self_employee_id&&($approval_st=="new"||$approval_st=="return")) {
                     $vardvaptxt .= "<span class='xlnk' onclick='edit_actionplan(\"$actionplan_id\",\"$antrain_objective_id\",this,event);' id='spap_${antrain_objective_id}_${actionplan_id}'>$actionplan_text</span>";
                  } else if($approval1_employee_id==$self_employee_id&&$approval_st=="approval1") {
                     $vardvaptxt .= "<span class='xlnk' onclick='edit_actionplan(\"$actionplan_id\",\"$antrain_objective_id\",this,event);' id='spap_${antrain_objective_id}_${actionplan_id}'>$actionplan_text</span>";
                  } else {
                     $vardvaptxt .= "<span id='spap_${antrain_objective_id}_${actionplan_id}'>$actionplan_text</span>";
                  }
                  $vardvaptxt .= "</div>";
               
               }
               $tooltip .= "\n<div style='display:none;' id='intooltip_${antrain_objective_id}_${actionplan_id}'>"
                         . "<div>"
                         . "<table class='aptooltip'><tbody>"
                         . ($is_pica==1?"<tr><td style='text-align:center;background-color:#fff;border:1px solid #bbb;' colspan='2'>PICA</td></tr>":"")
                         . "<tr><td>SO : </td><td>$antrain_objective_text</td></tr>"
                         . "<tr><td>Month : </td><td>".$xocp_vars["month_year"][$i]."</td></tr>"
                         . "<tr><td>Action Plan : </td><td>$actionplan_text</td></tr>"
                         . "<tr><Td>Target : </td><td>$target_text</td></tr>"
                         . "<tr><Td>Target Ach. : </td><td>".toMoney($target_achievement)." %</td></tr>"
                         . "<tr><Td>Ach. : </td><td>".toMoney($current_achievement)." %</td></tr>"
                         . "</tbody></table>"
                         . "</div>"
                         . "</div>";
               
               
               if($is_pica==0) {
                  $vardvtg .= "<div onmousemove='show_ap_tooltip(\"$antrain_objective_id\",\"$actionplan_id\",this,event);' onmouseover='mouseover_aptext(\"$no\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);' onmouseout='mouseout_aptext(\"$no\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);' class='aptgtext' id='dvtg_${antrain_objective_id}_${actionplan_id}'>"
                             . $target_text."&nbsp;"
                             . "</div>";
               } else if($report_mode==TRUE) {
                  $vardvtg .= "<div onmousemove='show_ap_tooltip(\"$antrain_objective_id\",\"$actionplan_id\",this,event);' onmouseover='mouseover_aptext".($is_pica==1?"pica":"")."(\"$no\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);' onmouseout='mouseout_aptext".($is_pica==1?"pica":"")."(\"$no\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);' class='aptgtext".($is_pica==1?"pica":"")."' id='dvtg_${antrain_objective_id}_${actionplan_id}'>"
                             . $target_text."&nbsp;"
                             . "</div>";
               }
               $sql = "SELECT root_cause,target_text,month_id FROM antrain_pic_action"
                    . " WHERE employee_id = '$employee_id'"
                    . " AND antrain_objective_id = '$antrain_objective_id'"
                    . " AND actionplan_id = '$pica_id'";
               $rpica = $db->query($sql);
               if($db->getRowsNum($rpica)>0) {
                  list($pica_root_cause,$pica_target_text,$pica_month_id)=$db->fetchRow($rpica);
                  $pica_month_text = $xocp_vars["month_year"][$pica_month_id];
               } else {
                  $pica_root_cause = $pica_target_text = $pica_month_text = "-";
               }
               
               $vardvpica_root .= "<div ".($pica_root_cause=="-"?"style='color:#555;'":"")." onmousemove='show_ap_tooltip(\"$antrain_objective_id\",\"$actionplan_id\",this,event);' onmouseover='mouseover_aptext".($is_pica==1?"pica":"")."(\"$no\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);' onmouseout='mouseout_aptext".($is_pica==1?"pica":"")."(\"$no\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);' class='appicatext".($is_pica==1?"pica":"")."' id='dvpica_root_${antrain_objective_id}_${actionplan_id}'>"
                                . htmlentities($pica_root_cause)."&nbsp;"
                                . "</div>";
               $vardvpica_improve .= "<div ".($pica_root_cause=="-"?"style='color:#555;'":"")." onmousemove='show_ap_tooltip(\"$antrain_objective_id\",\"$actionplan_id\",this,event);' onmouseover='mouseover_aptext".($is_pica==1?"pica":"")."(\"$no\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);' onmouseout='mouseout_aptext".($is_pica==1?"pica":"")."(\"$no\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);' class='appicatext".($is_pica==1?"pica":"")."' id='dvpica_improve_${antrain_objective_id}_${actionplan_id}'>"
                                   . htmlentities($pica_target_text)."&nbsp;"
                                   . "</div>";
               $vardvpica_month .= "<div ".($pica_root_cause=="-"?"style='color:#555;'":"")." onmousemove='show_ap_tooltip(\"$antrain_objective_id\",\"$actionplan_id\",this,event);' onmouseover='mouseover_aptext".($is_pica==1?"pica":"")."(\"$no\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);' onmouseout='mouseout_aptext".($is_pica==1?"pica":"")."(\"$no\",\"$antrain_objective_id\",\"$actionplan_id\",this,event);' class='appicatext".($is_pica==1?"pica":"")."' id='dvpica_month_${antrain_objective_id}_${actionplan_id}'>"
                                . htmlentities($pica_month_text)."&nbsp;"
                                . "</div>";
               
            }
            if($report_mode==FALSE) {
               for($vindent=$current_objective_ap_count;$vindent<$apcnt;$vindent++) {
                  $$vardvap .= "<div onmouseover='mouseover_aptext(\"$vindent\",\"$antrain_objective_id\",0,this,event);' onmouseout='mouseout_aptext(\"$vindent\",\"$antrain_objective_id\",0,this,event);' class='apblock_empty'>&nbsp;--</div>";
               }
            }
         } else {
            if($report_mode==FALSE) {
               for($vindent=0;$vindent<$apcnt;$vindent++) {
                  $indent = $vindent+1;
                  
                  if(isset($pica_block[$indent][$i])) {
                     list($pica_idx)=$pica_block[$indent][$i];
                     $tooltip .= "\n<div style='display:none;' id='intooltip_${antrain_objective_id}_${pica_idx}'>"
                               . "<div>"
                               . "<table class='aptooltip'><tbody>"
                               . "<tr><td>SO : </td><td>${pica_antrain_perspective_code}${pica_antrain_objective_no} $pica_antrain_objective_text</td></tr>"
                               . "<tr><td>Month : </td><td>".$xocp_vars["month_year"][$i]."</td></tr>"
                               . "<tr><td>Action Plan : </td><td>$pica_actionplan_text</td></tr>"
                               . "<tr><Td>Target : </td><td>$pica_target_text</td></tr>"
                               . "</tbody></table>"
                               . "</div>"
                               . "</div>";
                     
                     $$vardvap .= "<div"
                                . " onmousemove='show_ap_tooltip(\"$antrain_objective_id\",\"$pica_idx\",this,event);'"
                                . " onmouseover='mouseover_aptext(\"$no\",\"$antrain_objective_id\",\"$pica_idx\",this,event);'"
                                . " onmouseout='mouseout_aptext(\"$no\",\"$antrain_objective_id\",\"$pica_idx\",this,event);'"
                                . " class='apblockpica_${approval_st}'><div>&nbsp;44</div></div>";
                  } else {
                     $$vardvap .= "<div onmouseover='mouseover_aptext(\"$vindent\",\"$antrain_objective_id\",0,this,event);' onmouseout='mouseout_aptext(\"$vindent\",\"$antrain_objective_id\",0,this,event);' class='apblock_empty'>&nbsp;33</div>";
                  }
               }
            }
         }
      }
      
      
      
      
      $vardvap = "dvapx_".$_SESSION["antrain_month"];
      if(trim($$vardvap)=="") {
         $$vardvap = "<div class='apblock_empty'>&nbsp;</div>";
         $vardvaptxt = "<div class='aptext'>&nbsp;</div>";
      }
      
      if($status_approval==0&&$employee_id==$self_employee_id&&$report_mode==FALSE) {
         $vardvaptxt .= "<div id='dvaddap_${antrain_objective_id}' style='padding:3px;text-align:right;'>[<span onclick='new_actionplan(\"$antrain_objective_id\",this,event);' class='ylnk'>add</span>]</div>";
      }
      
      
      return array($antrain_objective_id,$vardvaptxt,$vardvtg,$dvapx_1,$dvapx_2,$dvapx_3,$dvapx_4,$dvapx_5,$dvapx_6,$dvapx_7,$dvapx_8,$dvapx_9,$dvapx_10,$dvapx_11,$dvapx_12,$tooltip,$vardvpica_root,$vardvpica_improve,$vardvpica_month);
   }
   
   
   
   function app_editActionPlan($args) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $db=&Database::getInstance();
      $user_id = getUserID();
      $employee_id = $args[0];
      $antrain_objective_id = $args[1];
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
      
      $sql = "SELECT actionplan_text,target_text,month_id,allow_carry_over,actionplan_group_id,repeat_type FROM antrain_pic_action"
           . " WHERE employee_id = '$employee_id' AND antrain_objective_id = '$antrain_objective_id' AND actionplan_id = '$actionplan_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($actionplan_text,$target_text,$month_id,$allow_carry_over,$actionplan_group_id,$repeat_type)=$db->fetchRow($result);
      } else {
         $allow_carry_over = 1;
         $repeat_type = 0;
         $actionplan_group_id = 0;
         $month_id = 1;
         $actionplan_text = $target_text = "";
      }
      
      switch($repeat_type) {
         case 1:
            $sql = "SELECT month_id FROM antrain_pic_action WHERE employee_id = '$employee_id' AND antrain_objective_id = '$antrain_objective_id' AND actionplan_group_id = '$actionplan_id' ORDER BY month_id";
            $result = $db->query($sql);
            $mno = 0;
            if($db->getRowsNum($result)>0) {
               while(list($month_idx)=$db->fetchRow($result)) {
                  if($mno==0) {
                     $month_id = $month_idx;
                  }
                  $month_id2 = $month_idx;
                  $mno++;
                  $vm = "ckm${month_idx}";
                  $$vm = "checked='1'";
               }
            }
            break;
         case 2:
            $arm = array();
            $sql = "SELECT month_id FROM antrain_pic_action WHERE employee_id = '$employee_id' AND antrain_objective_id = '$antrain_objective_id' AND actionplan_group_id = '$actionplan_id' ORDER BY month_id";
            $result = $db->query($sql);
            if($db->getRowsNum($result)>0) {
               while(list($month_idx)=$db->fetchRow($result)) {
                  $arm[$month_idx] = 1;
               }
            }
            for($mno=1;$mno<=12;$mno++) {
               $vm = "ckm${mno}";
               $$vm = (isset($arm[$mno])&&$arm[$mno]==1?"checked='1'":"");
            }
            break;
         default:
            $vm = "ckm${month_id}";
            $$vm = "checked='1'";
            break;
      }
      
      if($month_id2<$month_id) {
         $month_id2=$month_id;
      }
      
      global $xocp_vars;
      $opt_month = "";
      foreach($xocp_vars["month_year"] as $k=>$v) {
         if($k<=0) continue;
         $opt_month .= "<option value='$k' ".($month_id==$k?"selected='1'":"").">$v</option>";
      }
      
      $opt_month2 = "";
      foreach($xocp_vars["month_year"] as $k=>$v) {
         if($k<=0) continue;
         $opt_month2 .= "<option value='$k' ".($month_id2==$k?"selected='1'":"").">$v</option>";
      }
      
      $ret = "<div style='padding:5px;'>"
           . "<table class='xxfrm'><tbody>"
           . "<tr><td>Action Plan : </td><td><input type='text' style='width:200px;' id='inp_aptext' value='".addslashes($actionplan_text)."'/></td></tr>"
           . "<tr><td>Target : </td><td><input type='text' style='width:200px;' id='inp_tgtext' value='".addslashes($target_text)."'/></td></tr>"
           . "<tr><td>Repeat : </td><td><select id='repeat_type' onchange='chgselrepeat(this,event);'>"
               . "<option value='0' ".($repeat_type==0?"selected='1'":"").">No Repeat</option>"
               . "<option value='1' ".($repeat_type==1?"selected='1'":"").">Every Month</option>"
               . "<option value='2' ".($repeat_type==2?"selected='1'":"").">Choose Month ...</option>"
           . "</select></td></tr>"
           . "<tr><td>Time Schedule : </td><td><div id='month_range' style='".($repeat_type!=2?"":"display:none;")."'>"
               . "<select id='selmonth' onchange='chgselmonth(this,event);'>$opt_month</select>"
               . " to "
               . "<select ".($repeat_type==1?"":"disabled='1'")." id='selmonth2'>$opt_month2</select>"
           . "</div>"
           . "<div id='choose_month' style='".($repeat_type==2?"":"display:none;")."'>"
               . "<table><tbody><tr><td>"
               . "<div><input $ckm1 name='ckchoose_month[]' type='checkbox' id='ckchoose_month_1' value='1'/> <label for='ckchoose_month_1' class='xlnk'>".$xocp_vars["month_year"][1]."</label></div>"
               . "<div><input $ckm2 name='ckchoose_month[]' type='checkbox' id='ckchoose_month_2' value='2'/> <label for='ckchoose_month_2' class='xlnk'>".$xocp_vars["month_year"][2]."</label></div>"
               . "<div><input $ckm3 name='ckchoose_month[]' type='checkbox' id='ckchoose_month_3' value='3'/> <label for='ckchoose_month_3' class='xlnk'>".$xocp_vars["month_year"][3]."</label></div>"
               . "<div><input $ckm4 name='ckchoose_month[]' type='checkbox' id='ckchoose_month_4' value='4'/> <label for='ckchoose_month_4' class='xlnk'>".$xocp_vars["month_year"][4]."</label></div>"
               . "<div><input $ckm5 name='ckchoose_month[]' type='checkbox' id='ckchoose_month_5' value='5'/> <label for='ckchoose_month_5' class='xlnk'>".$xocp_vars["month_year"][5]."</label></div>"
               . "<div><input $ckm6 name='ckchoose_month[]' type='checkbox' id='ckchoose_month_6' value='6'/> <label for='ckchoose_month_6' class='xlnk'>".$xocp_vars["month_year"][6]."</label></div>"
               . "</td><td>"
               . "<div><input $ckm7 name='ckchoose_month[]' type='checkbox' id='ckchoose_month_7' value='7'/> <label for='ckchoose_month_7' class='xlnk'>".$xocp_vars["month_year"][7]."</label></div>"
               . "<div><input $ckm8 name='ckchoose_month[]' type='checkbox' id='ckchoose_month_8' value='8'/> <label for='ckchoose_month_8' class='xlnk'>".$xocp_vars["month_year"][8]."</label></div>"
               . "<div><input $ckm9 name='ckchoose_month[]' type='checkbox' id='ckchoose_month_9' value='9'/> <label for='ckchoose_month_9' class='xlnk'>".$xocp_vars["month_year"][9]."</label></div>"
               . "<div><input $ckm10 name='ckchoose_month[]' type='checkbox' id='ckchoose_month_10' value='10'/> <label for='ckchoose_month_10' class='xlnk'>".$xocp_vars["month_year"][10]."</label></div>"
               . "<div><input $ckm11 name='ckchoose_month[]' type='checkbox' id='ckchoose_month_11' value='11'/> <label for='ckchoose_month_11' class='xlnk'>".$xocp_vars["month_year"][11]."</label></div>"
               . "<div><input $ckm12 name='ckchoose_month[]' type='checkbox' id='ckchoose_month_12' value='12'/> <label for='ckchoose_month_12' class='xlnk'>".$xocp_vars["month_year"][12]."</label></div>"
               . "</td></tr></tbody></table>"
           . "</div>"
           . "</td></tr>"
           . "<tr><td>PICA Carry Over : </td><td><span id='sp_carry_over'>".($allow_carry_over==1?"Yes":"No")."</span><input id='allow_carry_over' type='hidden' value='$allow_carry_over'/></td></tr>"
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
      $_SESSION["antrain_employee_id"] = $args[0];
   }
   
   function app_loadSnapshotHistory($args) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $db=&Database::getInstance();
      $user_id = getUserID();
      $antrain_objective_id = $args[0];
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
      
      $sql = "SELECT snapshot_dttm,current_achievement FROM antrain_pic_action_snapshot"
           . " WHERE employee_id = '$self_employee_id' AND antrain_objective_id = '$antrain_objective_id' AND actionplan_id = '$actionplan_id'"
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
      
      $sql = "SELECT antrain_objective_id,actionplan_id,month_id,order_no,actionplan_text,target_text,final_result_text,current_achievement"
           . " FROM antrain_pic_action"
           . " WHERE employee_id = '$self_employee_id'"
           . " ORDER BY antrain_objective_id, actionplan_id, order_no";
      $result = $db->query($sql);
      $snapshot_dttm = getSQLDate($_SESSION["ach_dttm"],"date");
      if($db->getRowsNum($result)>0) {
         while(list($antrain_objective_id,$actionplan_id,$month_id,$order_no,$actionplan_text,$target_text,$final_result_text,$current_achievement)=$db->fetchRow($result)) {
            $sql = "REPLACE INTO antrain_pic_action_snapshot (employee_id,snapshot_dttm,antrain_objective_id,actionplan_id,month_id,order_no,actionplan_text,target_text,final_result_text,current_achievement,created_user_id)"
                 . " VALUES ('$self_employee_id','$snapshot_dttm','$antrain_objective_id','$actionplan_id','$month_id','$order_no','$actionplan_text','$target_text','$final_result_text','$current_achievement','$user_id')";
            $db->query($sql);
         }
      }
   }
   
   
   function app_saveCurrentTargetAchievement($args) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $db=&Database::getInstance();
      $user_id = getUserID();
      $current_achievement = _bctrim(bcadd($args[0],0));
      $antrain_objective_id = $args[1];
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
      
      $sql = "UPDATE antrain_pic_action SET "
           . "current_achievement = '$current_achievement'"
           . " WHERE employee_id = '$self_employee_id' AND antrain_objective_id = '$antrain_objective_id' AND actionplan_id = '$actionplan_id'";
      $db->query($sql);
      
   }
   
   function app_setCurrentStatusDate($args) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $_SESSION["ach_dttm"] = getSQLDate($args[0]);
   }
   
   function app_saveActionPlan($args) {
      $psid = $_SESSION["antrain_psid"];
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $db=&Database::getInstance();
      $user_id = getUserID();
      $employee_id = $args[0];
      $ap_text = urldecode($args[1]);
      $tg_text = urldecode($args[2]);
      $actionplan_id = $args[3];
      $antrain_objective_id = $args[4];
      $month_id = $args[5];
      $month_id2 = $args[6];
      $allow_carry_over = $args[7];
      $repeat_type = $args[8];
      $choose_month = _parseForm($args[9]);
      
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
         $sql = "SELECT MAX(actionplan_id) FROM antrain_pic_action"
              . " WHERE employee_id = '$employee_id' AND antrain_objective_id = '$antrain_objective_id'";
         $result = $db->query($sql);
         if($db->getRowsNum($result)>0) {
            list($new_actionplan_id)=$db->fetchRow($result);
         }
         
         $new_actionplan_id++;
         
         $sql = "SELECT MAX(order_no) FROM antrain_pic_action WHERE employee_id = '$employee_id' AND antrain_objective_id = '$antrain_objective_id'";
         $result = $db->query($sql);
         $order_no = 0;
         if($db->getRowsNum($result)>0) {
            list($order_no)=$db->fetchRow($result);
         }
         
         $order_no++;
         $sql = "INSERT INTO antrain_pic_action (psid,antrain_objective_id,employee_id,actionplan_id,month_id,created_dttm,created_user_id,order_no)"
              . " VALUES ('$psid','$antrain_objective_id','$employee_id','$new_actionplan_id','$month_id',now(),'$user_id','$order_no')";
         $db->query($sql);
         
         $actionplan_id = $new_actionplan_id;
         
      }
      
      $sql = "SELECT COUNT(*) FROM antrain_pic_action"
           . " WHERE antrain_objective_id = '$antrain_objective_id'"
           . " AND employee_id = '$employee_id'";
      $rcnt = $db->query($sql);
      list($apcnt)=$db->fetchRow($rcnt);
      
      $sql = "SELECT order_no,month_id FROM antrain_pic_action"
           . " WHERE employee_id = '$employee_id' AND antrain_objective_id = '$antrain_objective_id' AND actionplan_id = '$actionplan_id'";
      $result = $db->query($sql);
      list($order_no,$old_month_id)=$db->fetchRow($result);
      
      $sql = "UPDATE antrain_pic_action SET "
           . "actionplan_text = '".addslashes($ap_text)."',"
           . "target_text = '".addslashes($tg_text)."',"
           . "month_id = '$month_id',"
           . "allow_carry_over = '$allow_carry_over',"
           . "repeat_type = '$repeat_type'"
           . " WHERE employee_id = '$employee_id' AND antrain_objective_id = '$antrain_objective_id' AND actionplan_id = '$actionplan_id'";
      $db->query($sql);
      
      //// repeat_type processing //////////////////////////
      switch($repeat_type) {
         case 1:
            /// sanitize
            if($month_id2<$month_id) {
               $month_id2 = $month_id;
            }
            if($month_id<=0) {
               $month_id=1;
            }
            
            /// delete unused action plan
            $sql = "DELETE FROM antrain_pic_action"
                 . " WHERE employee_id = '$employee_id' AND antrain_objective_id = '$antrain_objective_id' AND actionplan_group_id = '$actionplan_id' AND month_id < '$month_id'";
            $db->query($sql);
            $sql = "DELETE FROM antrain_pic_action"
                 . " WHERE employee_id = '$employee_id' AND antrain_objective_id = '$antrain_objective_id' AND actionplan_group_id = '$actionplan_id' AND month_id > '$month_id2'";
            $db->query($sql);
            
            $n=0;
            for($m=$month_id;$m<=$month_id2;$m++) {
               if($n==0) {
                  $sql = "UPDATE antrain_pic_action SET "
                       . "month_id = '$m'"
                       . " WHERE employee_id = '$employee_id' AND antrain_objective_id = '$antrain_objective_id' AND actionplan_id = '$actionplan_id'";
                  $db->query($sql);
               }
               $n++;
               $sql = "SELECT actionplan_id FROM antrain_pic_action"
                    . " WHERE employee_id = '$employee_id' AND antrain_objective_id = '$antrain_objective_id' AND actionplan_group_id = '$actionplan_id' AND month_id = '$m'";
               $rg = $db->query($sql);
               if($db->getRowsNum($rg)==1) {
               
               } else {
                  /// delete to make sure no dups
                  $sql = "DELETE FROM antrain_pic_action"
                       . " WHERE employee_id = '$employee_id' AND antrain_objective_id = '$antrain_objective_id' AND actionplan_group_id = '$actionplan_id' AND month_id = '$m'";
                  $db->query($sql);
                  
                  /// insert new actionplan
                  $sql = "SELECT MAX(actionplan_id) FROM antrain_pic_action"
                       . " WHERE employee_id = '$employee_id' AND antrain_objective_id = '$antrain_objective_id'";
                  $result = $db->query($sql);
                  if($db->getRowsNum($result)>0) {
                     list($new_actionplan_id)=$db->fetchRow($result);
                  }
                  
                  $new_actionplan_id++;
                  
                  $order_no = 0;
                  
                  $sql = "INSERT INTO antrain_pic_action (psid,antrain_objective_id,employee_id,actionplan_id,month_id,created_dttm,created_user_id,order_no,is_pica,actionplan_text,target_text,target_achievement,actionplan_group_id)"
                       . " VALUES ('$psid','$antrain_objective_id','$employee_id','$new_actionplan_id','$m',now(),'$user_id','$order_no','0','".addslashes($ap_text)."','".addslashes($tg_text)."','100','$actionplan_id')";
                  $db->query($sql);
                  
                  
                  ////////////////////////////////////////////
                  
               }
            }
            
            break;
         case 2:
            if(is_array($choose_month["ckchoose_month"])&&count($choose_month["ckchoose_month"])>0) {
               $in_month = join("','",$choose_month["ckchoose_month"]);
               
               /// delete unused action plan
               $sql = "DELETE FROM antrain_pic_action"
                    . " WHERE employee_id = '$employee_id' AND antrain_objective_id = '$antrain_objective_id' AND actionplan_group_id = '$actionplan_id' AND month_id NOT IN ('$in_month')";
               $db->query($sql);
               
               $n = 0;
               
               foreach($choose_month["ckchoose_month"] as $k=>$m) {
                  if($n==0) {
                     $sql = "UPDATE antrain_pic_action SET "
                          . "month_id = '$m'"
                          . " WHERE employee_id = '$employee_id' AND antrain_objective_id = '$antrain_objective_id' AND actionplan_id = '$actionplan_id'";
                     $db->query($sql);
                  }
                  $n++;
                  $sql = "SELECT actionplan_id FROM antrain_pic_action"
                       . " WHERE employee_id = '$employee_id' AND antrain_objective_id = '$antrain_objective_id' AND actionplan_group_id = '$actionplan_id' AND month_id = '$m'";
                  $rg = $db->query($sql);
                  if($db->getRowsNum($rg)==1) {
                  
                  } else {
                     /// delete to make sure no dups
                     $sql = "DELETE FROM antrain_pic_action"
                          . " WHERE employee_id = '$employee_id' AND antrain_objective_id = '$antrain_objective_id' AND actionplan_group_id = '$actionplan_id' AND month_id = '$m'";
                     $db->query($sql);
                     
                     /// insert new actionplan
                     $sql = "SELECT MAX(actionplan_id) FROM antrain_pic_action"
                          . " WHERE employee_id = '$employee_id' AND antrain_objective_id = '$antrain_objective_id'";
                     $result = $db->query($sql);
                     if($db->getRowsNum($result)>0) {
                        list($new_actionplan_id)=$db->fetchRow($result);
                     }
                     
                     $new_actionplan_id++;
                     
                     $order_no = 0;
                     
                     $sql = "INSERT INTO antrain_pic_action (psid,antrain_objective_id,employee_id,actionplan_id,month_id,created_dttm,created_user_id,order_no,is_pica,actionplan_text,target_text,target_achievement,actionplan_group_id)"
                          . " VALUES ('$psid','$antrain_objective_id','$employee_id','$new_actionplan_id','$m',now(),'$user_id','$order_no','0','".addslashes($ap_text)."','".addslashes($tg_text)."','100','$actionplan_id')";
                     $db->query($sql);
                     
                     
                     ////////////////////////////////////////////
                     
                  }
               }
            }
            break;
         case 0:
         default:
            /// delete group action plan
            $sql = "DELETE FROM antrain_pic_action"
                 . " WHERE psid = '$psid' AND employee_id = '$employee_id' AND antrain_objective_id = '$antrain_objective_id' AND actionplan_group_id = '$actionplan_id'";
            $db->query($sql);
            break;
      }
      
      $dvapx_0 = "";
      
      $ret = $this->getActionPlanUpdate($antrain_objective_id,$employee_id);
      return $ret;
   }
   
   function app_nextAssessorReturnJAM($args) {
      $psid = $_SESSION["antrain_psid"];
      $db=&Database::getInstance();
      $employee_id = $args[0];
      $jam_org_ind = $args[1];
      $jam_org_id = $args[2];
      $return_note = addslashes(urldecode($args[3]));
      $sql = "UPDATE antrain_jam SET approval_st = 'return', return_note = '$return_note' WHERE psid = '$psid' AND employee_id = '$employee_id' AND jam_org_ind = '$jam_org_ind' AND org_id = '$jam_org_id'";
      $db->query($sql);
   }
   
   function app_approval2JAM($args) {
      $psid = $_SESSION["antrain_psid"];
      $db=&Database::getInstance();
      $employee_id = $args[0];
      $jam_org_ind = $args[1];
      $jam_org_id = $args[2];
      $sql = "UPDATE antrain_jam SET approval_st = 'implementation', approval2_dttm = now() WHERE psid = '$psid' AND employee_id = '$employee_id' AND jam_org_ind = '$jam_org_ind' AND org_id = '$jam_org_id'";
      $db->query($sql);
      $sql = "INSERT INTO antrain_jam_history SELECT NULL,now(),a.* FROM antrain_jam a WHERE psid = '$psid' AND employee_id = '$employee_id' AND jam_org_ind = '$jam_org_ind' AND org_id = '$jam_org_id'";
      $db->query($sql);
   }
   
   function app_firstAssessorReturnJAM($args) {
      $psid = $_SESSION["antrain_psid"];
      $db=&Database::getInstance();
      $employee_id = $args[0];
      $jam_org_ind = $args[1];
      $jam_org_id = $args[2];
      $return_note = addslashes(urldecode($args[3]));
      $sql = "UPDATE antrain_jam SET approval_st = 'return', return_note = '$return_note' WHERE psid = '$psid' AND employee_id = '$employee_id' AND jam_org_ind = '$jam_org_ind' AND org_id = '$jam_org_id'";
      $db->query($sql);
   }
   
   function app_approval1JAM($args) {
      $psid = $_SESSION["antrain_psid"];
      $db=&Database::getInstance();
      $employee_id = $args[0];
      $jam_org_ind = $args[1];
      $jam_org_id = $args[2];
      $sql = "UPDATE antrain_jam SET approval_st = 'approval2', approval1_dttm = now() WHERE psid = '$psid' AND employee_id = '$employee_id' AND jam_org_ind = '$jam_org_ind' AND org_id = '$jam_org_id'";
      $db->query($sql);
   }
   
   function app_submitJAM($args) {
      $psid = $_SESSION["antrain_psid"];
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $db=&Database::getInstance();
      $user_id = getUserID();
      $employee_id = $args[0];
      $jam_org_ind = $_SESSION["antrain_jam_org"];
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
      
      $sql = "UPDATE antrain_jam SET submit_dttm = now(),"
           . " approval_st = 'approval1',"
           . " approval1_employee_id = '$first_superior_employee_id',"
           . " approval2_employee_id = '$next_superior_employee_id'"
           . " WHERE psid = '$psid' AND employee_id = '$employee_id' AND jam_org_ind = '$jam_org_ind'";
      $db->query($sql);
   }
   
   function app_saveJAMTargetText($args) {
      $psid = $_SESSION["antrain_psid"];
      $db=&Database::getInstance();
      $user_id = getUserID();
      $val = addslashes($args[0]);
      $antrain_objective_id = $args[1];
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
         $sql = "UPDATE antrain_jam SET final_result_text = '$val' WHERE psid = '$psid' AND employee_id = '$self_employee_id' AND antrain_objective_id = '$antrain_objective_id'";
      } else {
         $sql = "UPDATE antrain_jam SET target_text${no} = '$val' WHERE psid = '$psid' AND employee_id = '$self_employee_id' AND antrain_objective_id = '$antrain_objective_id'";
      }
      $db->query($sql);
   }
   
   function app_saveJAMTargetWeight($args) {
      $psid = $_SESSION["antrain_psid"];
      $db=&Database::getInstance();
      $user_id = getUserID();
      $val = _bctrim(bcadd($args[0],0));
      $antrain_objective_id = $args[1];
      $no = $args[2];
      
      if($_SESSION["antrain_jam_org"]==1) {
         $org_id = $_SESSION["antrain_org_id"];
         $update_org = " AND jam_org_ind = '1' AND org_id = '$org_id'";
      } else {
         $update_org = " AND jam_org_ind = '0'";
      }
      
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
         $sql = "UPDATE antrain_jam SET final_result_weight = '$val' WHERE psid = '$psid' AND employee_id = '$self_employee_id' AND antrain_objective_id = '$antrain_objective_id'".$update_org;
      } else {
         $sql = "UPDATE antrain_jam SET target_weight${no} = '$val' WHERE psid = '$psid' AND employee_id = '$self_employee_id' AND antrain_objective_id = '$antrain_objective_id'".$update_org;
      }
      $db->query($sql);
   }
   
   function app_importObjectives($args) {
      $db=&Database::getInstance();
      $org_id = $_SESSION["antrain_org_id"];
      $sql = "SELECT parent_id FROM ".XOCP_PREFIX."orgs WHERE org_id = '$org_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($src_org_id)=$db->fetchRow($result);
         $this->run_deployObjectives(array($src_org_id,$org_id));
      }
   }
   
   function app_deployObjectives($args) {
      $psid = $_SESSION["antrain_psid"];
      $db=&Database::getInstance();
      $org_id = $args[0];
      $dst_org_id = $args[1];
      $sql = "SELECT a.antrain_share_org_id,b.org_abbr,b.org_nm"
           . " FROM antrain_org_share a"
           . " LEFT JOIN ".XOCP_PREFIX."orgs b ON b.org_id = a.antrain_share_org_id"
           . " WHERE psid = '$psid' AND a.antrain_org_id = '$org_id'"
           . ($dst_org_id>0?" AND a.antrain_share_org_id = '$dst_org_id'":"")
           . " ORDER BY b.order_no";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($antrain_share_org_id,$antrain_share_org_abbr,$antrain_share_org_nm)=$db->fetchRow($result)) {
            $this->run_deployObjectives(array($org_id,$antrain_share_org_id));
         }
      }
   }
   
   function run_deployObjectives($args) {
      $psid = $_SESSION["antrain_psid"];
      $db=&Database::getInstance();
      $org_id = $args[0];
      $dst_org_id = $args[1];
      $sql = "SELECT a.antrain_share_org_id,b.org_abbr,b.org_nm"
           . " FROM antrain_org_share a"
           . " LEFT JOIN ".XOCP_PREFIX."orgs b ON b.org_id = a.antrain_share_org_id"
           . " WHERE psid = '$psid' AND a.antrain_org_id = '$org_id'"
           . ($dst_org_id>0?" AND a.antrain_share_org_id = '$dst_org_id'":"")
           . " ORDER BY b.order_no";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($antrain_share_org_id,$antrain_share_org_abbr,$antrain_share_org_nm)=$db->fetchRow($result)) {
            //// clean up first here:
            $sql = "SELECT antrain_objective_id,antrain_objective_weight,antrain_parent_objective_id FROM antrain_objective WHERE psid = '$psid' AND antrain_org_id = '$antrain_share_org_id' AND antrain_parent_objective_id > '0'";
            $rc = $db->query($sql);
            $ttl_clean_weight = 0;
            if($db->getRowsNum($rc)>0) {
               while(list($clean_antrain_objective_id,$clean_antrain_objective_weight,$clean_antrain_parent_objective_id)=$db->fetchRow($rc)) {
                  /// check for local sub
                  $sql = "SELECT antrain_org_id FROM antrain_objective WHERE antrain_objective_id = '$clean_antrain_parent_objective_id'";
                  $rp = $db->query($sql);
                  if($db->getRowsNum($rp)>0) {
                     list($clean_antrain_parent_org_id)=$db->fetchRow($rp);
                     if($antrain_share_org_id==$clean_antrain_parent_org_id) continue;
                  }
                  $this->recurseDeleteSO($clean_antrain_objective_id);
                  
                  $ttl_clean_weight += $clean_antrain_objective_weight;
               }
            }
            
            $shared_objective = array();
            $share_kpi = array();
            $total_weight = 0;
            $local_weight = 0;
            $sql = "SELECT antrain_objective_id,antrain_objective_weight FROM antrain_objective WHERE psid = '$psid' AND antrain_org_id = '$antrain_share_org_id' AND antrain_parent_objective_id = '0'";
            $rc = $db->query($sql);
            if($db->getRowsNum($rc)>0) {
               while(list($local_antrain_objective_id,$local_antrain_objective_weight)=$db->fetchRow($rc)) {
                  $local_weight = _bctrim(bcadd($local_weight,$local_antrain_objective_weight));
               }
            }
            
            $factor = _bctrim(bcsub(100,$local_weight));
            
            /*
               factor = 100 - local weight
               because local weight is already in 100 based scale
            */
            
            
            $sql = "SELECT a.antrain_objective_id,a.antrain_kpi_id,a.antrain_share_weight,b.antrain_perspective_id,"
                 . "c.antrain_kpi_text,c.antrain_kpi_start,c.antrain_kpi_stop,c.antrain_kpi_target_text,c.antrain_kpi_measurement_unit,"
                 . "b.antrain_objective_text,b.antrain_objective_start,b.antrain_objective_stop"
                 . " FROM antrain_kpi_share a"
                 . " LEFT JOIN antrain_objective b USING(antrain_objective_id)"
                 . " LEFT JOIN antrain_kpi c ON c.antrain_objective_id = a.antrain_objective_id AND c.antrain_kpi_id = a.antrain_kpi_id"
                 . " WHERE a.psid = '$psid' AND a.antrain_org_id = '$org_id' AND a.antrain_share_org_id = '$antrain_share_org_id'"
                 . " AND b.antrain_objective_id IS NOT NULL"
                 . " ORDER BY b.antrain_perspective_id,b.antrain_objective_no";
            $rkpi = $db->query($sql);
            
            
            if($db->getRowsNum($rkpi)>0) {
               while(list($antrain_objective_id,$antrain_kpi_id,$antrain_share_weight,$antrain_perspective_id,
                          $antrain_kpi_text,$antrain_kpi_start,$antrain_kpi_stop,$antrain_kpi_target_text,$antrain_kpi_measurement_unit,
                          $antrain_objective_text,$antrain_objective_start,$antrain_objective_stop)=$db->fetchRow($rkpi)) {
                  
                  $shared_objective[$antrain_perspective_id][$antrain_objective_id] = array($antrain_objective_text,$antrain_objective_start,$antrain_objective_stop,$antrain_perspective_id);
                  $shared_kpi[$antrain_objective_id][$antrain_kpi_id] = array($antrain_share_weight,$antrain_kpi_text,$antrain_kpi_start,$antrain_kpi_stop,$antrain_kpi_target_text,$antrain_kpi_measurement_unit);
                  $total_weight = _bctrim(bcadd($total_weight,$antrain_share_weight));
                  
               }
            }
            
            $new_antrain_objective_no = 0;
            $new_total_objective_weight = 0;
            foreach($shared_objective as $antrain_perspective_id=>$v0) {
               $new_antrain_objective_no = 0;
               foreach($v0 as $antrain_objective_id=>$v1) {
                  $new_antrain_objective_no++;
                  list($antrain_objective_text,$antrain_objective_start,$antrain_objective_stop)=$v1;
                  $objective_weight = 0;
                  $objective_pic_job_id = 0;
                  $objective_pic_employee_id = 0;
                  $new_objective_start = "9999-12-31 00:00:00";
                  $new_objective_stop = "0000-00-00 00:00:00";
                  if(is_array($shared_kpi)) {
                     foreach($shared_kpi[$antrain_objective_id] as $antrain_kpi_id=>$v2) {
                        list($antrain_share_weight,$antrain_kpi_text,$antrain_kpi_start,$antrain_kpi_stop,$antrain_kpi_target_text,$antrain_kpi_measurement_unit)=$v2;
                        $objective_weight = _bctrim(bcadd($objective_weight,$antrain_share_weight));
                        $new_objective_start = min($antrain_kpi_start,$new_objective_start);
                        $new_objective_stop = max($antrain_kpi_stop,$new_objective_stop);
                     }
                  }
                  $new_objective_weight = _bctrim(bcmul(bcdiv($objective_weight,$total_weight),$factor));
                  $new_total_objective_weight = _bctrim(bcadd($new_total_objective_weight,$new_objective_weight));
                  //// insert objective here:
                  $sql = "INSERT INTO antrain_objective (psid,antrain_objective_text,antrain_objective_weight,antrain_objective_start,antrain_objective_stop,antrain_perspective_id,antrain_org_id,antrain_objective_no,antrain_parent_objective_id)"
                       . "\nVALUES ('$psid','$antrain_objective_text','$new_objective_weight','$new_objective_start','$new_objective_stop','$antrain_perspective_id','$antrain_share_org_id','$new_antrain_objective_no','$antrain_objective_id')";
                  $db->query($sql);
                  $new_objective_id = $db->getInsertId();
                  
                  $new_kpi_id = 0;
                  //// insert kpi here:
                  if(is_array($shared_kpi)) {
                     foreach($shared_kpi[$antrain_objective_id] as $antrain_kpi_id=>$v2) {
                        list($antrain_share_weight,$antrain_kpi_text,$antrain_kpi_start,$antrain_kpi_stop,$antrain_kpi_target_text,$antrain_kpi_measurement_unit)=$v2;
                        $new_kpi_share_weight = _bctrim(bcmul(bcdiv($antrain_share_weight,$total_weight),100));
                        $new_kpi_id++;
                        $sql = "INSERT INTO antrain_kpi (psid,antrain_objective_id,antrain_kpi_id,antrain_kpi_text,antrain_kpi_start,antrain_kpi_stop,antrain_kpi_target_text,antrain_kpi_measurement_unit,antrain_parent_objective_id,antrain_parent_kpi_id)"
                             . " VALUES ('$psid','$new_objective_id','$new_kpi_id','$antrain_kpi_text','$antrain_kpi_start','$antrain_kpi_stop','$antrain_kpi_target_text','$antrain_kpi_measurement_unit','$antrain_objective_id','$antrain_kpi_id')";
                        $db->query($sql);
                     }
                  }
               }
            }
            
            $this->resortObjectives($antrain_share_org_id);
            
         }
      }
   }
   
   function recalcObjectivesShare($args) {
      $psid = $_SESSION["antrain_psid"];
      $db=&Database::getInstance();
      $org_id = $args[0];
      $dst_org_id = $args[1];
      $sql = "SELECT a.antrain_share_org_id,b.org_abbr,b.org_nm"
           . " FROM antrain_org_share a"
           . " LEFT JOIN ".XOCP_PREFIX."orgs b ON b.org_id = a.antrain_share_org_id"
           . " WHERE psid = '$psid' AND a.antrain_org_id = '$org_id'"
           . " AND a.antrain_share_org_id = '$dst_org_id'"
           . " ORDER BY b.order_no";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($antrain_share_org_id,$antrain_share_org_abbr,$antrain_share_org_nm)=$db->fetchRow($result)) {
            //// clean up first here:
            $sql = "SELECT antrain_objective_id,antrain_objective_weight,antrain_parent_objective_id FROM antrain_objective WHERE psid = '$psid' AND antrain_org_id = '$antrain_share_org_id' AND antrain_parent_objective_id > '0'";
            $rc = $db->query($sql);
            $ttl_clean_weight = 0;
            if($db->getRowsNum($rc)>0) {
               while(list($clean_antrain_objective_id,$clean_antrain_objective_weight,$clean_antrain_parent_objective_id)=$db->fetchRow($rc)) {
                  /// check for local sub
                  $sql = "SELECT antrain_org_id FROM antrain_objective WHERE antrain_objective_id = '$clean_antrain_parent_objective_id'";
                  $rp = $db->query($sql);
                  if($db->getRowsNum($rp)>0) {
                     list($clean_antrain_parent_org_id)=$db->fetchRow($rp);
                     if($antrain_share_org_id==$clean_antrain_parent_org_id) continue;
                  }
                  $this->recurseDeleteSO($clean_antrain_objective_id);
                  
                  $ttl_clean_weight += $clean_antrain_objective_weight;
               }
            }
            
            $shared_objective = array();
            $share_kpi = array();
            $total_weight = 0;
            $local_weight = 0;
            $sql = "SELECT antrain_objective_id,antrain_objective_weight FROM antrain_objective WHERE psid = '$psid' AND antrain_org_id = '$antrain_share_org_id' AND antrain_parent_objective_id = '0'";
            $rc = $db->query($sql);
            if($db->getRowsNum($rc)>0) {
               while(list($local_antrain_objective_id,$local_antrain_objective_weight)=$db->fetchRow($rc)) {
                  $local_weight = _bctrim(bcadd($local_weight,$local_antrain_objective_weight));
               }
            }
            
            $factor = _bctrim(bcsub(100,$local_weight));
            
            /*
               factor = 100 - local weight
               because local weight is already in 100 based scale
            */
            
            
            $sql = "SELECT a.antrain_objective_id,a.antrain_kpi_id,a.antrain_share_weight,b.antrain_perspective_id,"
                 . "c.antrain_kpi_text,c.antrain_kpi_start,c.antrain_kpi_stop,c.antrain_kpi_target_text,c.antrain_kpi_measurement_unit,"
                 . "b.antrain_objective_text,b.antrain_objective_start,b.antrain_objective_stop"
                 . " FROM antrain_kpi_share a"
                 . " LEFT JOIN antrain_objective b USING(antrain_objective_id)"
                 . " LEFT JOIN antrain_kpi c ON c.antrain_objective_id = a.antrain_objective_id AND c.antrain_kpi_id = a.antrain_kpi_id"
                 . " WHERE a.psid = '$psid' AND a.antrain_org_id = '$org_id' AND a.antrain_share_org_id = '$antrain_share_org_id'"
                 . " AND b.antrain_objective_id IS NOT NULL"
                 . " ORDER BY b.antrain_perspective_id,b.antrain_objective_no";
            $rkpi = $db->query($sql);
            
            
            if($db->getRowsNum($rkpi)>0) {
               while(list($antrain_objective_id,$antrain_kpi_id,$antrain_share_weight,$antrain_perspective_id,
                          $antrain_kpi_text,$antrain_kpi_start,$antrain_kpi_stop,$antrain_kpi_target_text,$antrain_kpi_measurement_unit,
                          $antrain_objective_text,$antrain_objective_start,$antrain_objective_stop)=$db->fetchRow($rkpi)) {
                  
                  $shared_objective[$antrain_perspective_id][$antrain_objective_id] = array($antrain_objective_text,$antrain_objective_start,$antrain_objective_stop,$antrain_perspective_id);
                  $shared_kpi[$antrain_objective_id][$antrain_kpi_id] = array($antrain_share_weight,$antrain_kpi_text,$antrain_kpi_start,$antrain_kpi_stop,$antrain_kpi_target_text,$antrain_kpi_measurement_unit);
                  $total_weight = _bctrim(bcadd($total_weight,$antrain_share_weight));
                  
               }
            }
            
            
            $new_antrain_objective_no = 0;
            $new_total_objective_weight = 0;
            foreach($shared_objective as $antrain_perspective_id=>$v0) {
               $new_antrain_objective_no = 0;
               foreach($v0 as $antrain_objective_id=>$v1) {
                  $new_antrain_objective_no++;
                  list($antrain_objective_text,$antrain_objective_start,$antrain_objective_stop)=$v1;
                  $objective_weight = 0;
                  $objective_pic_job_id = 0;
                  $objective_pic_employee_id = 0;
                  $new_objective_start = "9999-12-31 00:00:00";
                  $new_objective_stop = "0000-00-00 00:00:00";
                  if(is_array($shared_kpi)) {
                     foreach($shared_kpi[$antrain_objective_id] as $antrain_kpi_id=>$v2) {
                        list($antrain_share_weight,$antrain_kpi_text,$antrain_kpi_start,$antrain_kpi_stop,$antrain_kpi_target_text,$antrain_kpi_measurement_unit)=$v2;
                        $objective_weight = _bctrim(bcadd($objective_weight,$antrain_share_weight));
                        $new_objective_start = min($antrain_kpi_start,$new_objective_start);
                        $new_objective_stop = max($antrain_kpi_stop,$new_objective_stop);
                     }
                  }
                  $new_objective_weight = _bctrim(bcmul(bcdiv($objective_weight,$total_weight),$factor));
                  $new_total_objective_weight = _bctrim(bcadd($new_total_objective_weight,$new_objective_weight));
                  //// insert objective here:
                  $sql = "INSERT INTO antrain_objective (psid,antrain_objective_text,antrain_objective_weight,antrain_objective_start,antrain_objective_stop,antrain_perspective_id,antrain_org_id,antrain_objective_no,antrain_parent_objective_id)"
                       . "\nVALUES ('$psid','$antrain_objective_text','$new_objective_weight','$new_objective_start','$new_objective_stop','$antrain_perspective_id','$antrain_share_org_id','$new_antrain_objective_no','$antrain_objective_id')";
                  $db->query($sql);
                  $new_objective_id = $db->getInsertId();
                  
                  $new_kpi_id = 0;
                  //// insert kpi here:
                  if(is_array($shared_kpi)) {
                     foreach($shared_kpi[$antrain_objective_id] as $antrain_kpi_id=>$v2) {
                        list($antrain_share_weight,$antrain_kpi_text,$antrain_kpi_start,$antrain_kpi_stop,$antrain_kpi_target_text,$antrain_kpi_measurement_unit)=$v2;
                        $new_kpi_share_weight = _bctrim(bcmul(bcdiv($antrain_share_weight,$total_weight),100));
                        $new_kpi_id++;
                        $sql = "INSERT INTO antrain_kpi (psid,antrain_objective_id,antrain_kpi_id,antrain_kpi_text,antrain_kpi_start,antrain_kpi_stop,antrain_kpi_target_text,antrain_kpi_measurement_unit,antrain_parent_objective_id,antrain_parent_kpi_id)"
                             . " VALUES ('$psid','$new_objective_id','$new_kpi_id','$antrain_kpi_text','$antrain_kpi_start','$antrain_kpi_stop','$antrain_kpi_target_text','$antrain_kpi_measurement_unit','$antrain_objective_id','$antrain_kpi_id')";
                        $db->query($sql);
                     }
                  }
               }
            }
            
            $this->resortObjectives($antrain_share_org_id);
            
         }
      }
   }
   
   function resortObjectives($org_id) {
      $psid = $_SESSION["antrain_psid"];
      $db=&Database::getInstance();
      //// re-sorting
      $sql0 = "SELECT antrain_objective_no,antrain_objective_id,antrain_perspective_id,'1' as urut"
            . " FROM antrain_objective WHERE psid = '$psid' AND antrain_org_id = '$org_id'"
            . " AND antrain_parent_objective_id > '0'";
      $sql1 = "SELECT antrain_objective_no,antrain_objective_id,antrain_perspective_id,'2' as urut"
            . " FROM antrain_objective WHERE psid = '$psid' AND antrain_org_id = '$org_id'"
            . " AND antrain_parent_objective_id = '0'";
      $sql = "($sql0) UNION ($sql1)"
           . " ORDER BY antrain_perspective_id,urut,antrain_objective_no,antrain_objective_id";
      $rxx = $db->query($sql);
      if($db->getRowsNum($rxx)>0) {
         $oldp = 0;
         $no = 0;
         while(list($antrain_objective_no,$antrain_objective_idx,$antrain_perspective_idx)=$db->fetchRow($rxx)) {
            if($oldp!=$antrain_perspective_idx) {
               $oldp = $antrain_perspective_idx;
               $no=0;
            }
            $no++;
            //$sql = "UPDATE antrain_objective SET antrain_objective_no = '$no' WHERE antrain_objective_id = '$antrain_objective_idx'";
            //$db->query($sql);
         }
      }
   }
   
   function app_saveCauseEffectRelation($args) {
      $psid = $_SESSION["antrain_psid"];
      $db=&Database::getInstance();
      $checked = $args[0];
      $src_antrain_objective_id = $args[1];
      $dst_antrain_objective_id = $args[2];
      $org_id = $_SESSION["antrain_org_id"];
      if($checked==1) {
         $sql = "REPLACE INTO antrain_cause_effect (psid,src_antrain_objective_id,dst_antrain_objective_id) VALUES ('$psid','$src_antrain_objective_id','$dst_antrain_objective_id')";
      } else {
         $sql = "DELETE FROM antrain_cause_effect WHERE psid = '$psid' AND src_antrain_objective_id = '$src_antrain_objective_id' AND dst_antrain_objective_id = '$dst_antrain_objective_id'";
      }
      $db->query($sql);
      return array($checked,$src_antrain_objective_id,$dst_antrain_objective_id);
   }
   
   function app_getCauseEffectRelation($args) {
      $psid = $_SESSION["antrain_psid"];
      $db=&Database::getInstance();
      $antrain_objective_id = $args[0];
      $org_id = $_SESSION["antrain_org_id"];
      
      $sql = "SELECT dst_antrain_objective_id FROM antrain_cause_effect WHERE psid = '$psid' AND src_antrain_objective_id = '$antrain_objective_id'";
      $result = $db->query($sql);
      $arr_dst = array();
      if($db->getRowsNum($result)>0) {
         while(list($dst_antrain_objective_id)=$db->fetchRow($result)) {
            $arr_dst[$dst_antrain_objective_id] = 1;
         }
      }
      
      $sql = "SELECT antrain_perspective_id FROM antrain_objective where psid = '$psid' AND antrain_objective_id = '$antrain_objective_id'";
      $result = $db->query($sql);
      list($antrain_perspective_id)=$db->fetchRow($result);
      
      $ret = "<div><div style='border:1px solid #bbb;background-color:#ddd;color:black;font-weight:bold;text-align:center;'>Choose Effect Destinations:</div>";
      
      $sql = "SELECT a.antrain_objective_id,a.antrain_objective_no,a.antrain_objective_text,a.antrain_perspective_id,b.antrain_perspective_code"
           . " FROM antrain_objective a"
           . " LEFT JOIN antrain_perspective b USING(antrain_perspective_id)"
           . " WHERE a.psid = '$psid' AND a.antrain_org_id = '$org_id'"
           . " ORDER BY a.antrain_perspective_id,a.antrain_objective_no";
      $result = $db->query($sql);
      $arr_p = array();
      $arr_o = array();
      $divo = array();
      if($db->getRowsNum($result)>0) {
         while(list($antrain_objective_idx,$antrain_objective_nox,$antrain_objective_textx,$antrain_perspective_idx,$antrain_perpective_codex)=$db->fetchRow($result)) {
            $arr_p[$antrain_perspective_idx] = $antrain_perpective_codex;
            $arr_o[$antrain_perspective_idx][$antrain_objective_idx] = array($antrain_objective_nox,$antrain_objective_textx);
         }
      }
      
      $divp = "<div style='background-color:#fff;border:1px solid #bbb;border-top:0;'><table style='width:200px;' align='center'><colgroup><col width='25%'/><col width='25%'/><col width='25%'/><col width='25%'/></colgroup><tbody><tr id='trselper'>";
      $selper = ($antrain_perspective_id==1?1:$antrain_perspective_id-1);
      foreach($arr_p as $antrain_perspective_idx=>$antrain_perpective_codex) {
         $divp .= "<td id='tdpers_${antrain_perspective_idx}' class='".($selper==$antrain_perspective_idx?"selper_selected":"selper")."' onclick='select_perspective_effect(\"$antrain_perspective_idx\",this,event);'>$antrain_perpective_codex</td>";
         $divo[$antrain_perspective_idx] = "<div id='dvpers_${antrain_perspective_idx}' style='text-align:left;".($antrain_perspective_idx==$selper?"":"display:none;")."'>"
                                     . "<table style='border-spacing:0;'><tbody>";
         
         foreach($arr_o[$antrain_perspective_idx] as $antrain_objective_idx=>$v) {
            list($antrain_objective_nox,$antrain_objective_textx)=$v;
            $divo[$antrain_perspective_idx] .= "<tr>"
                                         . "<td style=''><input ".($arr_dst[$antrain_objective_idx]==1?"checked='1'":"")." onclick='do_ck(\"$antrain_objective_id\",\"$antrain_objective_idx\",this,event);' type='checkbox' id='ckb_${antrain_objective_idx}'/></td>"
                                         . "<td style='font-weight:bold;text-align:center;'>${antrain_perpective_codex}${antrain_objective_nox}</td>"
                                         . "<td style=''><label for='ckb_${antrain_objective_idx}' class='xlnk'>".htmlentities($antrain_objective_textx)."</label></td></tr>";
         }
         $divo[$antrain_perspective_idx] .= "</tbody></table></div>";
      }
      $divp .= "</tr></tbody></table></div>";
      
      $ret .= $divp;
      
      foreach($divo as $antrain_perspective_idx => $v) {
         $ret .= $v;
      }
      
      $ret .= "</div>";
      return $ret;
   }
   
   function app_setSOOrigin($args) {
      $psid = $_SESSION["antrain_psid"];
      $db=&Database::getInstance();
      $antrain_parent_objective_id = $args[0];
      $org_id = $_SESSION["antrain_org_id"];
      
      
      $sql = "SELECT p.antrain_perspective_code,a.antrain_objective_no,a.antrain_org_id,a.antrain_objective_text,b.org_nm,c.org_class_nm"
           . " FROM antrain_objective a"
           . " LEFT JOIN ".XOCP_PREFIX."orgs b ON b.org_id = a.antrain_org_id"
           . " LEFT JOIN ".XOCP_PREFIX."org_class c USING(org_class_id)"
           . " LEFT JOIN antrain_perspective p ON p.antrain_perspective_id = a.antrain_perspective_id"
           . " WHERE a.psid = '$psid' AND a.antrain_objective_id = '$antrain_parent_objective_id'";
      $result = $db->query($sql);
      list($antrain_perspective_code,$antrain_objective_no,$antrain_parent_org_id,$antrain_parent_objective_text,$antrain_parent_org_nm,$antrain_parent_org_class_nm)=$db->fetchRow($result);
      
      $sql = "SELECT antrain_share_weight FROM antrain_kpi_share WHERE psid = '$psid' AND antrain_org_id = '$antrain_parent_org_id' AND antrain_share_org_id = '$org_id' AND antrain_objective_id = '$antrain_parent_objective_id'";
      $result = $db->query($sql);
      $ttlweight = 0;
      if($db->getRowsNum($result)>0) {
         while(list($antrain_share_weight)=$db->fetchRow($result)) {
            $ttlweight = _bctrim(bcadd($antrain_share_weight,$ttlweight));
         }
      }
      
      $ret = "<table class='xxfrm' style='width:100%;'>"
           . "<colgroup><col width='150'/><col/></colgroup>"
           . "<tbody>"
           . "<tr><td>Organization</td><td>".htmlentities("$antrain_parent_org_nm $antrain_parent_org_class_nm")."</td></tr>"
           . "<tr><td>Strategic Objective</td><td>".htmlentities("${antrain_perspective_code}${antrain_objective_no} $antrain_parent_objective_text")."</td></tr>"
           . "<tr><td>Weight</td><td>$ttlweight %</td></tr>"
           . "<tr><td colspan='2'>"
           //. "<input type='button' value='Select Source' onclick='change_so_origin(this,event);'/>"
           . "&#160;</td></tr>"
           . "</tbody></table><input type='hidden' name='antrain_parent_objective_id' id='antrain_parent_objective_id' value='$antrain_parent_objective_id'/>";
      return $ret;
   }
   
   function app_saveKPIShare($args) {
      $psid = $_SESSION["antrain_psid"];
      $db=&Database::getInstance();
      $antrain_objective_id = $args[0];
      $antrain_kpi_id = $args[1];
      $antrain_share_org_id = $args[2];
      $antrain_org_id = $_SESSION["antrain_org_id"];
      $vars = _parseForm($args[3]);
      
      $antrain_share_weight_100 = _bctrim(bcadd($vars["antrain_share_weight"],0));
      
      if(bccomp($antrain_share_weight_100,0)<=0) {
         $sql = "DELETE FROM antrain_kpi_share"
              . " WHERE psid = '$psid' AND antrain_objective_id = '$antrain_objective_id'"
              . " AND antrain_kpi_id = '$antrain_kpi_id'"
              . " AND antrain_org_id = '$antrain_org_id'"
              . " AND antrain_share_org_id = '$antrain_share_org_id'";
      } else {
         
         $sql = "SELECT antrain_objective_weight FROM antrain_objective WHERE antrain_objective_id = '$antrain_objective_id'";
         $result = $db->query($sql);
         if($db->getRowsNum($result)>0) {
            list($antrain_objective_weight)=$db->fetchRow($result);
         }
         
         $antrain_share_weight = _bctrim(bcdiv(bcmul($antrain_share_weight_100,$antrain_objective_weight),100));
         
         
         $sql = "REPLACE INTO antrain_kpi_share (psid,antrain_objective_id,antrain_kpi_id,antrain_org_id,antrain_share_org_id,antrain_share_weight)"
              . " VALUES ('$psid','$antrain_objective_id','$antrain_kpi_id','$antrain_org_id','$antrain_share_org_id','$antrain_share_weight')";
      }
      $db->query($sql);
      
      return $this->app_calcRemainingShare(array($antrain_objective_id,$antrain_kpi_id,$antrain_share_org_id));
   }
   
   function app_editKPIShare($args) {
      $psid = $_SESSION["antrain_psid"];
      $db=&Database::getInstance();
      $antrain_objective_id = $args[0];
      $antrain_kpi_id = $args[1];
      $antrain_share_org_id = $args[2];
      $org_id = $_SESSION["antrain_org_id"];
      
      $btn = "<input type='button' value='"._SAVE."' onclick='save_kpi_share(\"$antrain_objective_id\",\"$antrain_kpi_id\",\"$antrain_share_org_id\",this,event);'/>&#160;&#160;"
           . "<input type='button' value='"._CANCEL."' onclick='editkpisharebox.fade();'/>";
      
      $sql = "SELECT antrain_kpi_text,antrain_kpi_weight,antrain_kpi_start,antrain_kpi_stop,antrain_kpi_target_text,antrain_kpi_measurement_unit,antrain_kpi_pic_job_id"
           . " FROM antrain_kpi WHERE psid = '$psid' AND antrain_objective_id = '$antrain_objective_id' AND antrain_kpi_id = '$antrain_kpi_id'";
      $result = $db->query($sql);
      list($antrain_kpi_text,$antrain_kpi_weight,$tm_start,$tm_stop,$antrain_kpi_target_text,$antrain_kpi_measurement_unit,$antrain_kpi_pic_job_id)=$db->fetchRow($result);
      $sql = "SELECT a.org_abbr,a.org_nm,b.org_class_nm FROM ".XOCP_PREFIX."orgs a"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . " WHERE a.org_id = '$antrain_share_org_id'";
      $result = $db->query($sql);
      list($antrain_share_org_abbr,$antrain_share_org_nm,$antrain_share_org_class_nm)=$db->fetchRow($result);
      $sql = "SELECT antrain_perspective_id,antrain_objective_no,antrain_objective_text FROM antrain_objective WHERE psid = '$psid' AND antrain_objective_id = '$antrain_objective_id'";
      $result = $db->query($sql);
      list($antrain_perspective_id,$antrain_objective_no,$antrain_objective_text)=$db->fetchRow($result);
      $sql = "SELECT antrain_perspective_code,antrain_perspective_name FROM antrain_perspective WHERE psid = '$psid' AND antrain_perspective_id = '$antrain_perspective_id'";
      $result = $db->query($sql);
      list($antrain_perspective_code,$antrain_perspective_name)=$db->fetchRow($result);
      
      $sql = "SELECT antrain_share_weight FROM antrain_kpi_share"
           . " WHERE psid = '$psid' AND antrain_objective_id = '$antrain_objective_id'"
           . " AND antrain_kpi_id = '$antrain_kpi_id'"
           . " AND antrain_org_id = '$org_id'"
           . " AND antrain_share_org_id = '$antrain_share_org_id'";
      $rw = $db->query($sql);
      if($db->getRowsNum($rw)>0) {
         list($antrain_share_weight)=$db->fetchRow($rw);
      } else {
         $antrain_share_weight = 0;
      }
      
      $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>"
           . "Edit KPI Share"
           . "</div>"
           . "<div style='padding:5px;background-color:#f2f2f2;color:#555;'>"
              . "<div style='border:1px solid #999;background-color:#fff;padding:4px;' id='frmkpi'>"
                  . "<table style='width:100%;border-spacing:0px;'><tbody><tr><td style='height:135px;'>"
                  
                  . "<div style='max-height:135px;overflow:auto;padding-top:3px;'>"
                  . "<table class='xxfrm' style='width:100%;'><tbody>"
                  . "<tr><td>Perspective</td><td>${antrain_perspective_name}</td></tr>"
                  . "<tr><td>Strategic Objective</td><td>${antrain_perspective_code}${antrain_objective_no} - $antrain_objective_text</td></tr>"
                  . "<tr><td>KPI</td><td>$antrain_kpi_text</td></tr>"
                  . "<tr><td>Share to</td><td>$antrain_share_org_abbr - $antrain_share_org_nm $antrain_share_org_class_nm</td></tr>"
                  . "<tr><td>Weight</td><td><input id='antrain_share_weight' name='antrain_share_weight' type='text' style='text-align:center;width:40px;' value='$antrain_share_weight' onkeypress='kp_kpi_share(\"$antrain_objective_id\",\"$antrain_kpi_id\",\"$antrain_share_org_id\",this,event);'/> %</td></tr>"
                  . "</tbody></table>"
                  . "</div>"
                  . "<input type='hidden' name='antrain_objective_id' id='antrain_objective_id' value='$antrain_objective_id'/>"
                  . "<input type='hidden' name='antrain_kpi_id' id='antrain_kpi_id' value='$antrain_kpi_id'/>"
                  . "<input type='hidden' name='antrain_share_org_id' id='antrain_share_org_id' value='$antrain_share_org_id'/>"
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
      $org_id = $_SESSION["antrain_org_id"];
      $antrain_objective_id = $args[0];
      $antrain_kpi_id = $args[1];
      $sql = "DELETE FROM antrain_kpi WHERE antrain_objective_id = '$antrain_objective_id' AND antrain_kpi_id = '$antrain_kpi_id'";
      $db->query($sql);
      
   }
   
   function app_saveKPI($args) {
      $psid = $_SESSION["antrain_psid"];
      $db=&Database::getInstance();
      $org_id = $_SESSION["antrain_org_id"];
      $vars = _parseForm($args[0]);
      $antrain_objective_id = $vars["antrain_objective_id"]+0;
      if($vars["antrain_kpi_id"]=="new") {
         $sql = "SELECT MAX(antrain_kpi_id) FROM antrain_kpi WHERE psid = '$psid' AND antrain_objective_id = '$antrain_objective_id'";
         $result = $db->query($sql);
         list($antrain_kpi_id)=$db->fetchRow($result);
         $antrain_kpi_id++;
         $sql = "INSERT INTO antrain_kpi (psid,antrain_objective_id,antrain_kpi_id) VALUES ('$psid','$antrain_objective_id','$antrain_kpi_id')";
         $db->query($sql);
      } else {
         $antrain_kpi_id = $vars["antrain_kpi_id"];
      }
      $sql = "UPDATE antrain_kpi SET "
           . "antrain_kpi_text = '".addslashes($vars["antrain_kpi_text"])."',"
           . "antrain_kpi_weight = '"._bctrim(bcadd($vars["antrain_kpi_weigth"],0))."',"
           . "antrain_kpi_start = '".getSQLDate($vars["h_tm_start"])."',"
           . "antrain_kpi_stop = '".getSQLDate($vars["h_tm_stop"])."',"
           . "antrain_kpi_target_text = '".addslashes($vars["antrain_kpi_target_text"])."',"
           . "antrain_kpi_measurement_unit = '".addslashes($vars["antrain_kpi_measurement_unit"])."',"
           . "antrain_kpi_pic_job_id = '".($vars["antrain_kpi_pic_job_id"]+0)."'"
           . " WHERE antrain_objective_id = '$antrain_objective_id' AND antrain_kpi_id = '$antrain_kpi_id'";
      $db->query($sql);
   }
   
   function app_editKPI($args) {
      $psid = $_SESSION["antrain_psid"];
      $db=&Database::getInstance();
      $antrain_objective_id = $args[0];
      $antrain_kpi_id = $args[1];
      $org_id = $_SESSION["antrain_org_id"];
      if($antrain_kpi_id=="new") {
         $title = "Add New KPI";
         $btn = "<input type='button' value='Add New' onclick='save_kpi(\"$antrain_objective_id\",\"$antrain_kpi_id\",this,event);'/>&#160;&#160;"
              . "<input type='button' value='"._CANCEL."' onclick='editkpibox.fade();'/>";
         $tm_start = getSQLDate();
         $tm_stop = getSQLDate();
         $sql = "SELECT start_dttm,stop_dttm FROM antrain_session WHERE psid = '$psid'";
         $result = $db->query($sql);
         list($tm_start,$tm_stop)=$db->fetchRow($result);
      } else {
         $title = "Edit KPI";
         $sql = "SELECT antrain_kpi_text,antrain_kpi_weight,antrain_kpi_start,antrain_kpi_stop,antrain_kpi_target_text,antrain_kpi_measurement_unit,antrain_kpi_pic_job_id"
              . " FROM antrain_kpi WHERE psid = '$psid' AND antrain_objective_id = '$antrain_objective_id' AND antrain_kpi_id = '$antrain_kpi_id'";
         $result = $db->query($sql);
         list($antrain_kpi_text,$antrain_kpi_weight,$tm_start,$tm_stop,$antrain_kpi_target_text,$antrain_kpi_measurement_unit,$antrain_kpi_pic_job_id)=$db->fetchRow($result);
         $btn = "<input type='button' value='"._SAVE."' onclick='save_kpi(\"$antrain_objective_id\",\"$antrain_kpi_id\",this,event);'/>&#160;&#160;"
              . "<input type='button' value='"._CANCEL."' onclick='editkpibox.fade();'/>&#160;&#160;&#160;"
              . "<input type='button' value='"._DELETE."' onclick='delete_kpi(\"$antrain_objective_id\",\"$antrain_kpi_id\",this,event);'/>";
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
            $optpic .= "<option value='$job_id' ".($job_id==$antrain_kpi_pic_job_id?"selected='selected'":"").">$job_abbr - $job_nm</option>";
         }
      }
      
      $sql = "SELECT antrain_perspective_id,antrain_objective_no,antrain_objective_text FROM antrain_objective WHERE psid = '$psid' AND antrain_objective_id = '$antrain_objective_id'";
      $result = $db->query($sql);
      list($antrain_perspective_id,$antrain_objective_no,$antrain_objective_text)=$db->fetchRow($result);
      $sql = "SELECT antrain_perspective_code,antrain_perspective_name FROM antrain_perspective WHERE psid = '$psid' AND antrain_perspective_id = '$antrain_perspective_id'";
      $result = $db->query($sql);
      list($antrain_perspective_code,$antrain_perspective_name)=$db->fetchRow($result);
      
      $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>"
           . $title
           . "</div>"
           . "<div style='padding:5px;background-color:#f2f2f2;color:#555;'>"
              . "<div style='border:1px solid #999;background-color:#fff;padding:4px;' id='frmkpi'>"
                  . "<table style='width:100%;border-spacing:0px;'><tbody><tr><td style='height:235px;'>"
                  
                  . "<div style='max-height:235px;overflow:auto;padding-top:3px;'>"
                  . "<table class='xxfrm' style='width:100%;'><tbody>"
                  . "<tr><td>Perspective</td><td>${antrain_perspective_name}</td></tr>"
                  . "<tr><td>Strategic Objective</td><td>${antrain_perspective_code}${antrain_objective_no} - $antrain_objective_text</td></tr>"
                  . "<tr><td>KPI</td><td><input type='text' id='antrain_kpi_text' name='antrain_kpi_text' style='width:400px;' value='$antrain_kpi_text'/></td></tr>"
                  . "<tr><td>PIC</td><td><select id='antrain_kpi_pic_job_id' name='antrain_kpi_pic_job_id'>$optpic</select></td></tr>"
                  . "<tr><td>Target</td><td><input id='antrain_kpi_target_text' name='antrain_kpi_target_text' type='text' style='width:300px;' value='$antrain_kpi_target_text'/></td></tr>"
                  . "<tr><td>Measurement Unit</td><td><input id='antrain_kpi_measurement_unit' name='antrain_kpi_measurement_unit' type='text' style='width:100px;' value='$antrain_kpi_measurement_unit'/></td></tr>"
                  /// . "<tr><td>Weight</td><td><input id='antrain_kpi_weight' name='antrain_kpi_weight' type='text' style='width:40px;' value='$antrain_kpi_weight'/> %</td></tr>"
                  . "<tr><td>Time Frame</td><td>"
                  . "<span class='xlnk' id='sp_tm_start' onclick='_changedatetime(\"sp_tm_start\",\"h_tm_start\",\"date\",true,false);'>".sql2ind($tm_start,"date")."</span>"
                  . "<input type='hidden' name='h_tm_start' id='h_tm_start' value='$tm_start'/>"
                  . " until "
                  . "<span class='xlnk' id='sp_tm_stop' onclick='_changedatetime(\"sp_tm_stop\",\"h_tm_stop\",\"date\",true,false);'>".sql2ind($tm_stop,"date")."</span>"
                  . "<input type='hidden' name='h_tm_stop' id='h_tm_stop' value='$tm_stop'/>"
                  . "</td></tr>"
                  . "</tbody></table>"
                  . "</div>"
                  . "<input type='hidden' name='antrain_objective_id' id='antrain_objective_id' value='$antrain_objective_id'/>"
                  . "<input type='hidden' name='antrain_kpi_id' id='antrain_kpi_id' value='$antrain_kpi_id'/>"
                  . "</td></tr></tbody></table>"
              . "</div>"
           . "</div>"
           . "<div style='text-align:center;padding:10px;background-color:#f2f2f2;height:100px;' id='frmbtn'>"
           . $btn
           . "</div>";
      
      return $ret;
   }
   
   function app_deleteShare($args) {
      $psid = $_SESSION["antrain_psid"];
      $db=&Database::getInstance();
      $antrain_share_org_id = $args[0];
      $org_id = $_SESSION["antrain_org_id"];
      $sql = "DELETE FROM antrain_org_share WHERE psid = '$psid' AND antrain_org_id = '$org_id' AND antrain_share_org_id = '$antrain_share_org_id'";
      $db->query($sql);
   }
   
   function app_viewShare($args) {
      $psid = $_SESSION["antrain_psid"];
      $db=&Database::getInstance();
      $antrain_share_org_id = $args[0];
      $sql = "SELECT a.org_abbr,a.org_nm,b.org_class_nm"
           . " FROM ".XOCP_PREFIX."orgs a"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . " WHERE a.org_id = '$antrain_share_org_id'";
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
           //. "<input type='button' value='"._DELETE."' onclick='delete_share(\"$antrain_share_org_id\",this,event);'/>"
           . "</div>";
      
      return $ret;
      
   }
   
   function app_addShare($args) {
      $psid = $_SESSION["antrain_psid"];
      $db=&Database::getInstance();
      $antrain_share_org_id = $args[0];
      $antrain_org_id = $_SESSION["antrain_org_id"];
      
      if($antrain_org_id==$antrain_org_share_id) return;
      
      $sql = "INSERT INTO antrain_org_share (psid,antrain_org_id,antrain_share_org_id) VALUES ('$psid','$antrain_org_id','$antrain_share_org_id')";
      $db->query($sql);
      
   }
   
   function recurseDeleteSO($antrain_objective_id) {
      $psid = $_SESSION["antrain_psid"];
      $db=&Database::getInstance();
      $sql = "SELECT antrain_objective_id FROM antrain_objective WHERE psid = '$psid' AND antrain_parent_objective_id = '$antrain_objective_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($child_antrain_objective_id)=$db->fetchRow($result)) {
            $this->recurseDeleteSO($child_antrain_objective_id);
         }
      }
      $sql = "DELETE FROM antrain_objective WHERE psid = '$psid' AND antrain_objective_id = '$antrain_objective_id'";
      $db->query($sql);
      $sql = "DELETE FROM antrain_kpi WHERE psid = '$psid' AND antrain_objective_id = '$antrain_objective_id'";
      $db->query($sql);
      $sql = "DELETE FROM antrain_kpi_share WHERE psid = '$psid' AND antrain_objective_id = '$antrain_objective_id'";
      $db->query($sql);
      $sql = "DELETE FROM antrain_pic_action WHERE psid = '$psid' AND antrain_objective_id = '$antrain_objective_id'";
      $db->query($sql);
      $sql = "DELETE FROM antrain_jam WHERE psid = '$psid' AND antrain_objective_id = '$antrain_objective_id'";
      $db->query($sql);
   }
   
   function app_deleteSO($args) {
      $db=&Database::getInstance();
      $antrain_objective_id = $args[0];
      $this->recurseDeleteSO($antrain_objective_id);
   }
   
   
   function app_saveSO($args) {
      $psid = $_SESSION["antrain_psid"];
      $db=&Database::getInstance();
      $org_id = $_SESSION["antrain_org_id"];
      $vars = _parseForm($args[0]);
      
      list($antrain_perspective_id,$d) = explode("|",$vars["antrain_perspective_id"]);
      
      $antrain_parent_objective_id = ($vars["antrain_parent_objective_id"]+0);
      
      if($vars["antrain_objective_id"]=="new") {
         $sql = "INSERT INTO antrain_objective (psid,antrain_objective_text) VALUES('$psid','-')";
         $result = $db->query($sql);
         $antrain_objective_id = $db->getInsertId();
         $_SESSION["antrain_perspective_last"] = $antrain_perspective_id;
         if($antrain_parent_objective_id>0) {
            $has_local_sub++;
         }
      } else {
         $antrain_objective_id = $vars["antrain_objective_id"];
         $sql = "SELECT a.antrain_parent_objective_id,b.antrain_org_id"
              . " FROM antrain_objective a"
              . " LEFT JOIN antrain_objective b ON b.antrain_objective_id = a.antrain_parent_objective_id AND b.psid = a.psid"
              . " WHERE a.psid = '$psid' AND a.antrain_objective_id = '$antrain_objective_id'";
         $result = $db->query($sql);
         list($antrain_parent_objective_id,$parent_antrain_org_id)=$db->fetchRow($result);
         if($org_id==$parent_antrain_org_id) {
            $has_local_sub++;
         }
      }
      
      
      //// source objective
      $sql = "SELECT p.antrain_perspective_code,a.antrain_objective_no,a.antrain_org_id,a.antrain_objective_text,b.org_nm,c.org_class_nm"
           . " FROM antrain_objective a"
           . " LEFT JOIN ".XOCP_PREFIX."orgs b ON b.org_id = a.antrain_org_id"
           . " LEFT JOIN ".XOCP_PREFIX."org_class c USING(org_class_id)"
           . " LEFT JOIN antrain_perspective p ON p.antrain_perspective_id = a.antrain_perspective_id"
           . " WHERE a.psid = '$psid' AND a.antrain_objective_id = '$antrain_parent_objective_id'";
      $result = $db->query($sql);
      list($antrain_parent_perspective_code,$antrain_parent_objective_no,$antrain_parent_org_idxxx,$antrain_parent_objective_text,$antrain_parent_org_nm,$antrain_parent_org_class_nm)=$db->fetchRow($result);
      
      $sql = "SELECT antrain_share_weight FROM antrain_kpi_share WHERE psid = '$psid' AND antrain_org_id = '$antrain_parent_org_idxxx' AND antrain_share_org_id = '$org_id' AND antrain_objective_id = '$antrain_parent_objective_id'";
      $result = $db->query($sql);
      $source_so_ttlweight = 0;
      if($db->getRowsNum($result)>0) {
         while(list($antrain_parent_share_weight)=$db->fetchRow($result)) {
            $source_so_ttlweight = _bctrim(bcadd($antrain_parent_share_weight,$source_so_ttlweight));
         }
      } else {
         $sql = "SELECT antrain_objective_weight FROM antrain_objective WHERE psid = '$psid' AND antrain_objective_id = '$antrain_parent_objective_id'";
         $result = $db->query($sql);
         list($source_so_ttlweight)=$db->fetchRow($result);
      }
      
      
      
      if($has_local_sub>0) {
         $weight = _bctrim(  bcmul( $source_so_ttlweight,          bcdiv($vars["weight"],100)                ));
      } else {
         $weight = _bctrim(bcadd($vars["weight"],0));
      }
      
      $sql = "UPDATE antrain_objective SET "
           . "antrain_org_id = '$org_id',"
           . "antrain_parent_objective_id = '".($vars["antrain_parent_objective_id"]+0)."',"
           . "antrain_perspective_id = '$antrain_perspective_id',"
           . "antrain_objective_no = '".($vars["antrain_objective_no"])."',"
           . "antrain_objective_text = '".addslashes($vars["so_txt"])."',"
           . "antrain_kpi_text = '".addslashes($vars["kpi_txt"])."',"
           . "antrain_target_text = '".addslashes($vars["target_text"])."',"
           . "antrain_measurement_unit = '".addslashes($vars["munit"])."',"
           . "antrain_objective_weight = '$weight',"
           . "antrain_objective_start = '".getSQLDate($vars["h_tm_start"])."',"
           . "antrain_objective_stop = '".getSQLDate($vars["h_tm_stop"])."',"
           . "antrain_pic_job_id = '".($vars["pic_job_id"]+0)."'"
           . " WHERE antrain_objective_id = '$antrain_objective_id'";
      $db->query($sql);
      $this->resortObjectives($org_id);
   }
   
   function app_getNo($args) {
      $psid = $_SESSION["antrain_psid"];
      $db=&Database::getInstance();
      list($antrain_perspective_id,$antrain_perspective_code) = explode("|",$args[0]);
      $org_id = $_SESSION["antrain_org_id"];
      $sql = "SELECT MAX(antrain_objective_no) FROM antrain_objective"
           . " WHERE antrain_perspective_id = '$antrain_perspective_id'"
           . " AND psid = '$psid'"
           . " AND antrain_org_id = '$org_id'";
      $result = $db->query($sql);
      list($antrain_objective_no)=$db->fetchRow($result);
      $antrain_objective_no++;
      return array($antrain_objective_no);
   }
   
   function app_editSO($args) {
      include_once(XOCP_DOC_ROOT."/modules/antrain/include/antrain.php");
      $psid = $_SESSION["antrain_psid"];
      $db=&Database::getInstance();
      $antrain_objective_id = $args[0];
      $org_id = $_SESSION["antrain_org_id"];
      
      $sql = "SELECT org_class_id FROM ".XOCP_PREFIX."orgs WHERE org_id = '$org_id'";
      $result = $db->query($sql);
      list($current_org_class_id)=$db->fetchRow($result);
      
      
      $sql = "SELECT a.org_nm,b.org_class_nm FROM ".XOCP_PREFIX."orgs a LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id) WHERE a.org_id = '$org_id'";
      $result = $db->query($sql);
      list($org_nm,$org_class_nm)=$db->fetchRow($result);
      
      if(substr($antrain_objective_id,0,3)=="new") {
         list($new,$perspective_idx)=explode("_",$antrain_objective_id);
         $title = "Add New Strategic Objective";
         $antrain_objective_id = $new;
         
         if($perspective_idx!="") {
            $antrain_perspective_id = $perspective_idx;
         } else {
            $antrain_perspective_id = $_SESSION["antrain_perspective_last"];
         }
         if($antrain_perspective_id==0) $antrain_perspective_id = 1;
         
         $sql = "SELECT MAX(antrain_objective_no) FROM antrain_objective"
              . " WHERE psid = '$psid' AND antrain_perspective_id = '$antrain_perspective_id'"
              . " AND antrain_org_id = '$org_id'";
         $result = $db->query($sql);
         list($antrain_objective_no)=$db->fetchRow($result);
         list($first_part_no)=explode(".",$antrain_objective_no);
         $antrain_objective_no = $first_part_no+1;
         
         $btn = "<input type='button' value='Add New' onclick='save_so(\"$antrain_objective_id\",this,event);'/>&#160;&#160;"
              . "<input type='button' value='"._CANCEL."' onclick='editsobox.fade();'/>";
         $btn_sub = "&nbsp;";
         $tm_start = getSQLDate();
         $tm_stop = getSQLDate();
         $sql = "SELECT antrain_perspective_code,antrain_perspective_id,antrain_perspective_name FROM antrain_perspective WHERE psid = '$psid' ORDER BY antrain_perspective_id";
         $result = $db->query($sql);
         $optpers = "";
         if($db->getRowsNum($result)>0) {
            while(list($antrain_perspective_codex,$antrain_perspective_idx,$antrain_perspective_name)=$db->fetchRow($result)) {
               $optpers .= "<option value='$antrain_perspective_idx|$antrain_perspective_codex' ".($antrain_perspective_idx==$antrain_perspective_id?"selected='selected'":"").">$antrain_perspective_name</option>";
            }
         }
         $sel_pers = "<tr><td>Perspective</td><td><select id='antrain_perspective_id' name='antrain_perspective_id' onchange='chgpers(this,event);'>$optpers</select>&#160;"
                   . "No : <input disabled='1' name='antrain_objective_no' id='antrain_objective_no' type='text' style='width:40px;text-align:center;' value='$antrain_objective_no'/>"
                   . "</td></tr>";
         $antrain_parent_objective_id = 0;
         
         $source_so = "Strategic Objective Source:"
                    . "<div id='parent_so'>"
                    . "<div style='text-align:center;padding:20px;border:1px solid #bbb;-moz-border-radius:5px;'>"
                    . "<span style='font-style:italic;'>This objective's scope will be top level or local.</span><br/>"
                    . "<input type='hidden' name='antrain_parent_objective_id' id='antrain_parent_objective_id' value='0'/>"
                    . "</div>"
                    . "</div>";
         $initiative_btn = "&#160;";
      } else {
         $sql = "SELECT antrain_parent_objective_id,antrain_objective_no,antrain_objective_text,antrain_kpi_text,antrain_target_text,antrain_measurement_unit,antrain_objective_weight,antrain_objective_start,antrain_objective_stop,antrain_perspective_id,antrain_pic_job_id"
              . " FROM antrain_objective WHERE psid = '$psid' AND antrain_objective_id = '$antrain_objective_id'";
         $result = $db->query($sql);
         list($antrain_parent_objective_id,$antrain_objective_no,$antrain_objective_text,$antrain_kpi_text,$antrain_target_text,$antrain_measurement_unit,$antrain_objective_weight,$tm_start,$tm_stop,$antrain_perspective_id,$antrain_pic_job_id)=$db->fetchRow($result);
         $sql = "SELECT antrain_perspective_code,antrain_perspective_name FROM antrain_perspective WHERE psid = '$psid' AND antrain_perspective_id = '$antrain_perspective_id'";
         $result = $db->query($sql);
         list($antrain_perspective_code,$antrain_perspective_name)=$db->fetchRow($result);
         
         $title = "Edit Strategic Objective";
         $initiative_btn = ""; //<input type='button' value='Create Initiative' onclick='add_initiative(\"$antrain_objective_id\",this,event);'/>";
         $btn = "<input type='button' value='"._SAVE."' onclick='save_so(\"$antrain_objective_id\",this,event);'/>&#160;&#160;"
              . "<input type='button' value='"._CANCEL."' onclick='editsobox.fade();'/>&#160;&#160;&#160;"
              . "<input type='button' value='"._DELETE."' onclick='delete_so(\"$antrain_objective_id\",this,event);'/>";
         $btn_sub = ($allow_add_initiative[$current_org_class_id]==1?"<input type='button' value='Add Initiative' onclick='add_sub(\"$antrain_objective_id\",this,event);'/>":"");
         
         /*
         $sel_pers = "<tr><td>Perspective</td><td>$antrain_perspective_name"
                   . "<input type='hidden' name='antrain_perspective_id' value='$antrain_perspective_id'/>"
                   . "<input type='hidden' name='antrain_objective_no' value='$antrain_objective_no'/>"
                   . "</td></tr>";
         */
         
         $sql = "SELECT antrain_perspective_code,antrain_perspective_id,antrain_perspective_name FROM antrain_perspective WHERE psid = '$psid' ORDER BY antrain_perspective_id";
         $result = $db->query($sql);
         $optpers = "";
         if($db->getRowsNum($result)>0) {
            while(list($antrain_perspective_codex,$antrain_perspective_idx,$antrain_perspective_name)=$db->fetchRow($result)) {
               $optpers .= "<option value='$antrain_perspective_idx|$antrain_perspective_codex' ".($antrain_perspective_idx==$antrain_perspective_id?"selected='selected'":"").">$antrain_perspective_name</option>";
            }
         }
         $sel_pers = "<tr><td>Perspective</td><td><select id='antrain_perspective_id' name='antrain_perspective_id' onchange='chgpers(this,event);'>$optpers</select>&#160;"
                   . "No : <input disabled='1' name='antrain_objective_no' id='antrain_objective_no' type='text' style='width:40px;text-align:center;' value='$antrain_objective_no'/>"
                   . "</td></tr>";
         
         //// source objective
         $sql = "SELECT p.antrain_perspective_code,a.antrain_objective_no,a.antrain_org_id,a.antrain_objective_text,b.org_nm,c.org_class_nm"
              . " FROM antrain_objective a"
              . " LEFT JOIN ".XOCP_PREFIX."orgs b ON b.org_id = a.antrain_org_id"
              . " LEFT JOIN ".XOCP_PREFIX."org_class c USING(org_class_id)"
              . " LEFT JOIN antrain_perspective p ON p.antrain_perspective_id = a.antrain_perspective_id"
              . " WHERE a.psid = '$psid' AND a.antrain_objective_id = '$antrain_parent_objective_id'";
         $result = $db->query($sql);
         list($antrain_parent_perspective_code,$antrain_parent_objective_no,$antrain_parent_org_idxxx,$antrain_parent_objective_text,$antrain_parent_org_nm,$antrain_parent_org_class_nm)=$db->fetchRow($result);
         
         $sql = "SELECT antrain_share_weight FROM antrain_kpi_share WHERE psid = '$psid' AND antrain_org_id = '$antrain_parent_org_idxxx' AND antrain_share_org_id = '$org_id' AND antrain_objective_id = '$antrain_parent_objective_id'";
         $result = $db->query($sql);
         $source_so_ttlweight = 0;
         if($db->getRowsNum($result)>0) {
            while(list($antrain_parent_share_weight)=$db->fetchRow($result)) {
               $source_so_ttlweight = _bctrim(bcadd($antrain_parent_share_weight,$source_so_ttlweight));
            }
         } else {
            $sql = "SELECT antrain_objective_weight FROM antrain_objective WHERE psid = '$psid' AND antrain_objective_id = '$antrain_parent_objective_id'";
            $result = $db->query($sql);
            list($source_so_ttlweight)=$db->fetchRow($result);
         }
         
         $sql = "SELECT a.antrain_objective_id,a.antrain_org_id,a.antrain_objective_weight"
              . " FROM antrain_objective a"
              . " LEFT JOIN antrain_objective b ON b.antrain_objective_id = a.antrain_parent_objective_id AND b.psid = a.psid"
              . " WHERE a.psid = '$psid' AND a.antrain_parent_objective_id = '$antrain_parent_objective_id'"
              . " AND b.antrain_org_id = '$org_id'"
              . " AND a.antrain_org_id = '$org_id'";
         $rchild = $db->query($sql);
         $has_local_sub = 0;
         $ttl_sub_weight = 0;
         if($db->getRowsNum($rchild)>0) {
            while(list($sub_antrain_objective_id,$sub_antrain_org_id,$sub_weight)=$db->fetchRow($rchild)) {
               $has_local_sub++;
               $ttl_sub_weight = _bctrim(bcadd($ttl_sub_weight,$sub_weight));
            }
         }
         
         if($antrain_parent_objective_id>0) {
            $source_so = "Strategic Objective Source:"
                       . "<div id='parent_so'>"
                       . "<table class='xxfrm' style='width:100%;'>"
                       . "<colgroup><col width='150'/><col/></colgroup>"
                       . "<tbody>"
                       . "<tr><td>Organization</td><td>".htmlentities("$antrain_parent_org_nm $antrain_parent_org_class_nm")."</td></tr>"
                       . "<tr><td>Strategic Objective</td><td>".htmlentities("${antrain_parent_perspective_code}${antrain_parent_objective_no} $antrain_parent_objective_text")."</td></tr>"
                       . "<tr><td>Weight</td><td>".toMoney($source_so_ttlweight)." %"
                       . ($has_local_sub>0?" <span style='color:blue;' id='sub_so_remaining'>( <span class='xlnk' onclick='use_sub_so_remaining(this,event);'>".toMoney(bcsub($source_so_ttlweight,$ttl_sub_weight))."</span> % remaining )</span>":"")
                       . "</td></tr>"
                       . "</tbody></table>"
                       . "<input type='hidden' name='antrain_parent_objective_id' id='antrain_parent_objective_id' value='$antrain_parent_objective_id'/>"
                       . "</div>";
         } else {
            $source_so = "Strategic Objective Source:"
                       . "<div id='parent_so'>"
                       . "<div style='text-align:center;padding:20px;border:1px solid #bbb;-moz-border-radius:5px;'>"
                       . "<span style='font-style:italic;'>This objective's scope is top level or local.</span><br/>"
                       . "<input type='hidden' name='antrain_parent_objective_id' id='antrain_parent_objective_id' value='0'/>"
                       . "</div>"
                       . "</div>";
         }
         $sql = "SELECT antrain_share_weight FROM antrain_kpi_share WHERE psid = '$psid' AND antrain_org_id = '$antrain_parent_org_id' AND antrain_share_org_id = '$org_id'";
         $result = $db->query($sql);
         $ttlweight = 0;
         if($db->getRowsNum($result)>0) {
            while(list($antrain_share_weight)=$db->fetchRow($result)) {
               $ttlweight = _bctrim(bcadd($antrain_share_weight,$ttlweight));
            }
         }
      
         
      }
      
      $sql = "SELECT antrain_parent_objective_id FROM antrain_objective WHERE psid = '$psid' AND antrain_org_id = '$org_id'";
      $result = $db->query($sql);
      $arr_source_so = array();
      if($db->getRowsNum($result)>0) {
         while(list($antrain_parent_objective_idx)=$db->fetchRow($result)) {
            $arr_source_so[$antrain_parent_objective_idx] = 1;
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
      
      $sql = "SELECT a.antrain_org_id,a.antrain_objective_id,a.antrain_kpi_id,SUM(a.antrain_share_weight),"
           . "b.org_nm,b2.org_class_nm,c.antrain_objective_text,c.antrain_objective_no,"
           . "p.antrain_perspective_code,p.antrain_perspective_name,d.antrain_kpi_text,c.antrain_perspective_id"
           . " FROM antrain_kpi_share a"
           . " LEFT JOIN ".XOCP_PREFIX."orgs b ON b.org_id = a.antrain_org_id"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b2 USING(org_class_id)"
           . " LEFT JOIN antrain_objective c ON c.antrain_objective_id = a.antrain_objective_id"
           . " LEFT JOIN antrain_perspective p ON p.antrain_perspective_id = c.antrain_perspective_id"
           . " LEFT JOIN antrain_kpi d ON d.antrain_kpi_id = a.antrain_kpi_id AND d.antrain_objective_id = a.antrain_objective_id"
           . " WHERE a.psid = '$psid' AND a.antrain_share_org_id = '$org_id'"
           . " AND c.antrain_objective_id IS NOT NULL"
           . " GROUP BY a.antrain_objective_id"
           . " ORDER BY a.antrain_objective_id";
      
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($shared_antrain_org_id,$shared_antrain_objective_id,$shared_antrain_kpi_id,$shared_antrain_share_weight,
                    $shared_org_nm,$shared_org_class_nm,$shared_antrain_objective_text,$shared_antrain_objective_no,
                    $shared_antrain_perspective_code,$shared_antrain_perspective_name,$shared_antrain_kpi_text,$shared_antrain_perspective_id)=$db->fetchRow($result)) {
            
            if(isset($arr_source_so[$shared_antrain_objective_id])&&$arr_source_so[$shared_antrain_objective_id]==1) {
               $btnstatus = "Sourced";
            } else {
               $btnstatus = "<input type='button' value='Source' onclick='set_so_origin(\"$shared_antrain_objective_id\",this,event);'/>";
            }
            $selso .= "<tr>"
                    . "<td>".htmlentities("$shared_org_nm $shared_org_class_nm")."</td>"
                    . "<td>".htmlentities("${shared_antrain_perspective_code}${shared_antrain_objective_no} - $shared_antrain_objective_text")."</td>"
                    . "<td style='text-align:center;'>$shared_antrain_share_weight</td>"
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
            $optpic .= "<option value='$job_id' ".($job_id==$antrain_pic_job_id?"selected='selected'":"").">".htmlentities("$job_abbr - $job_nm")."</option>";
         }
      }
      
      
      if($has_local_sub==0) {
         $antrain_objective_weight_subcalc = $antrain_objective_weight;
         $in_what = "of 100 %.";
      } else {
         $antrain_objective_weight_subcalc = 100*($antrain_objective_weight/$source_so_ttlweight);
         $in_what = "of ".toMoney($source_so_ttlweight)." %";
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
                  . "<tr><td>ID</td><td id='antrain_obj_code'>${antrain_perspective_code}${antrain_objective_no}</td></tr>"
                  
                  . $sel_pers
                  
                  . "<tr><td>Strategic Objective</td><td><input type='text' id='so_txt' name='so_txt' style='width:400px;' value='".htmlentities($antrain_objective_text)."'/></td></tr>"
                  
                  . "<tr><td>PIC</td><td><select id='pic_job_id' name='pic_job_id'>$optpic</select></td></tr>"
                  
                  . "<tr><td>Weight</td><td><input onclick='_dsa(this);' onkeypress='kp_so_weight(this,event);' id='weight' name='weight' type='text' style='width:80px;' value='$antrain_objective_weight_subcalc'/> %</td></tr>"
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
                  . "<input type='hidden' name='antrain_objective_id' id='antrain_objective_id' value='$antrain_objective_id'/>"
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
      $_SESSION["antrain_org_id"] = $org_id;
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