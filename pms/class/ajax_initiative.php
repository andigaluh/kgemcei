<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_initiative.php                //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_INITIATIVEAJAX_DEFINED') ) {
   define('HRIS_INITIATIVEAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");

class _pms_class_InitiativeAjax extends AjaxListener {
   
   function _pms_class_InitiativeAjax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/pms/class/ajax_initiative.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_browseOrgs","app_selectOrg",
                                             "app_editSO","app_getNo","app_saveSO",
                                             "app_deleteSO","app_browseOrgShare","app_addShare",
                                             "app_viewShare","app_deleteShare","app_editKPI",
                                             "app_saveKPI","app_deleteKPI","app_editKPIShare","app_saveKPIShare",
                                             "app_setSOOrigin","app_editActionPlan","app_saveActionPlan",
                                             "app_deleteActionPlan","app_searchPIC","app_addPIC","app_editPICShare",
                                             "app_savePICShare","app_calcRemainingShare","app_viewPIC",
                                             "app_deletePIC");
   }
   
   function calcTotalShared($pms_perspective_id=0) {
      $psid = $_SESSION["pms_psid"];
      $db=&Database::getInstance();
      
      $org_id = $_SESSION["pms_org_id"];
      
      $sql = "SELECT a.employee_id,c.person_nm,c.person_id,b.alias_nm"
           . " FROM pms_org_actionplan_share a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b ON b.employee_id = a.employee_id"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " WHERE a.psid = '$psid' AND a.pms_org_id = '$org_id'";
      $result = $db->query($sql);
      $share_arr = array();
      if($db->getRowsNum($result)>0) {
         $share_cnt = $db->getRowsNum($result);
         while(list($employee_id,$person_nm,$person_id,$employee_alias_nm)=$db->fetchRow($result)) {
            $share_arr[] = array($employee_id,$person_nm,$person_id);
         }
      }
      
      $sql = "SELECT pms_perspective_code,pms_perspective_id,pms_perspective_name FROM pms_perspective ORDER BY pms_perspective_id";
      $result = $db->query($sql);
      $ttlpicshare_arr = array();
      $subttlpicshare_arr = array();
      $ttlw = 0;
      if($db->getRowsNum($result)>0) {
         while(list($pms_perspective_code,$pms_perspective_idx,$pms_perspective_name)=$db->fetchRow($result)) {
            $sql = "SELECT pms_objective_id,pms_objective_no,pms_objective_text,pms_kpi_text,pms_target_text,pms_measurement_unit,pms_objective_weight,"
                 . "pms_pic_job_id,pms_pic_employee_id,pms_parent_objective_id"
                 . " FROM pms_objective"
                 . " WHERE psid = '$psid' AND pms_org_id = '$org_id'"
                 . " AND pms_perspective_id = '$pms_perspective_idx'"
                 . " ORDER BY pms_objective_no";
            $ro = $db->query($sql);
            $cnt = $db->getRowsNum($ro);
            if($cnt>0) {
               while(list($pms_objective_id,$pms_objective_no,$pms_objective_text,$pms_kpi_text,$pms_target_text,$pms_measurement_unit,$pms_objective_weight,
                          $pms_pic_job_id,$pms_pic_employee_id,$pms_parent_objective_idx)=$db->fetchRow($ro)) {
                  
                  $ttlw = _bctrim(bcadd($ttlw,$pms_objective_weight));
                  /// has local sub?
                  $sql = "SELECT pms_objective_id,pms_org_id,pms_objective_weight FROM pms_objective WHERE psid = '$psid' AND pms_parent_objective_id = '$pms_objective_id' AND pms_org_id = '$org_id'";
                  $rchild = $db->query($sql);
                  $has_local_sub = 0;
                  $ttl_sub_weight = 0;
                  if($db->getRowsNum($rchild)>0) {
                     while(list($sub_pms_objective_id,$sub_pms_org_id,$sub_weight)=$db->fetchRow($rchild)) {
                        $has_local_sub++;
                        $ttl_sub_weight = _bctrim(bcadd($ttl_sub_weight,$sub_weight));
                     }
                  }
                  
                  
                  $kpi_cnt = 0;
                  $sql = "SELECT pms_kpi_id,pms_kpi_text,pms_kpi_weight,pms_kpi_target_text,pms_kpi_measurement_unit"
                       . " FROM pms_kpi WHERE pms_objective_id = '$pms_objective_id'";
                  $rkpi = $db->query($sql);
                  $kpi_cnt = $db->getRowsNum($rkpi);
                  
                  //// query actionplan first
                  
                  $sql = "SELECT pms_actionplan_text,pms_actionplan_start,pms_actionplan_stop,pms_actionplan_id FROM pms_actionplan WHERE pms_objective_id = '$pms_objective_id'";
                  $rap = $db->query($sql);
                  $ap_cnt = $db->getRowsNum($rap);
                  
                  //// select action plan and schedule:
                  
                  if($has_local_sub==0) {
                     
                     if($ap_cnt>0) {
                        $apno = 0;
                        while(list($pms_actionplan_text,$pms_actionplan_start,$pms_actionplan_stop,$pms_actionplan_id)=$db->fetchRow($rap)) {
                           
                           if($share_cnt>0&&$has_local_sub==0) {
                              foreach($share_arr as $v) {
                                 list($employee_id,$person_nm,$person_id)=$v;
                                 
                                 $sql = "SELECT pms_share_weight FROM pms_actionplan_share"
                                      . " WHERE psid = '$psid' AND pms_objective_id = '$pms_objective_id'"
                                      . " AND pms_actionplan_id = '$pms_actionplan_id'"
                                      . " AND pms_actionplan_pic_employee_id = '$employee_id'";
                                 $resultshare = $db->query($sql);
                                 if($db->getRowsNum($resultshare)>0) {
                                    list($pms_pic_share_weight)=$db->fetchRow($resultshare);
                                 } else {
                                    $pms_pic_share_weight = 0;
                                 }
                                 
                                 if(!isset($ttlpicshare_arr[$employee_id])) {
                                    $ttlpicshare_arr[$employee_id] = 0;
                                 }
                                 $ttlpicshare_arr[$employee_id] = _bctrim(bcadd($ttlpicshare_arr[$employee_id],$pms_pic_share_weight));
                                 
                                 //// total each perspective per person
                                 if($pms_perspective_idx==$pms_perspective_id) {
                                    if(!isset($subttlpicshare_arr[$employee_id])) {
                                       $subttlpicshare_arr[$employee_id] = 0;
                                    }
                                    $subttlpicshare_arr[$employee_id] = _bctrim(bcadd($subttlpicshare_arr[$employee_id],$pms_pic_share_weight));
                                 }
                                 
                              }
                           }
                        }
                     }
                  }
               }
            }
         }
      }
      
      $ttlshared = 0;
      $arr_pic = array();
      if($share_cnt>0) {
         foreach($ttlpicshare_arr as $employee_id=>$share) {
            $ttlshared = _bctrim(bcadd($ttlshared,$share));
            $arr_pic[] = array($employee_id,(bccomp($share,0)==0?"-":toMoney($share)));
         }
      }
      $arr_pers = array();
      foreach($subttlpicshare_arr as $employee_id=>$share) {
         $arr_pers[] = array($pms_perspective_id,$employee_id,(bccomp($share,0)==0?"-":toMoney($share)));
      }
      
      return array(toMoney($ttlshared),$arr_pic,$arr_pers,(bccomp(number_format($ttlw,2,".",""),number_format($ttlshared,2,".",""))));
   }
   
   function app_deletePIC($args) {
      $psid = $_SESSION["pms_psid"];
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $db=&Database::getInstance();
      $employee_id = $args[0];
      
      $pms_org_id = $_SESSION["pms_org_id"];
      
      $sql = "SELECT a.pms_objective_id,a.pms_actionplan_id FROM pms_actionplan_share a"
           . " LEFT JOIN pms_objective b USING(pms_objective_id)"
           . " WHERE a.psid = '$psid' AND a.pms_org_id = '$pms_org_id'"
           . " AND a.pms_actionplan_pic_employee_id = '$employee_id'"
           . " AND b.pms_objective_id IS NOT NULL";
      $resultshare = $db->query($sql);
      $ttl_share = 0;
      if($db->getRowsNum($resultshare)>0) {
         while(list($pms_objective_id,$pms_actionplan_id)=$db->fetchRow($resultshare)) {
            $sql = "DELETE FROM pms_jam WHERE psid = '$psid' AND pms_objective_id = '$pms_objective_id' AND employee_id = '$employee_id'";
            $db->query($sql);
            $sql = "DELETE FROM pms_pic_action WHERE psid = '$psid' AND pms_objective_id = '$pms_objective_id' AND employee_id = '$employee_id'";
            $db->query($sql);
            $sql = "DELETE FROM pms_actionplan_share WHERE psid = '$psid' AND pms_objective_id = '$pms_objective_id' AND pms_actionplan_id = '$pms_actionplan_id' AND pms_actionplan_pic_employee_id = '$employee_id'";
            $db->query($sql);
            
         }
      }
      $sql = "DELETE FROM pms_org_actionplan_share WHERE psid = '$psid' AND pms_org_id = '$pms_org_id' AND employee_id = '$employee_id'";
      $db->query($sql);
      
   }
   
   function app_viewPIC($args) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $db=&Database::getInstance();
      $employee_id = $args[0];
      
      $pms_org_id = $_SESSION["pms_org_id"];
      
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
      
      
      $sql = "SELECT c.job_nm,c.job_abbr,d.org_nm,d.org_abbr,a.employee_ext_id,e.person_nm,e.person_id"
           . " FROM ".XOCP_PREFIX."employee a"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job b ON b.employee_id = a.employee_id AND b.job_id = '$job_id'"
           . " LEFT JOIN ".XOCP_PREFIX."jobs c ON c.job_id = '$job_id'"
           . " LEFT JOIN ".XOCP_PREFIX."orgs d ON d.org_id = c.org_id"
           . " LEFT JOIN ".XOCP_PREFIX."persons e ON e.person_id = a.person_id"
           . " WHERE a.employee_id = '$employee_id'";
      $result = $db->query($sql);
      list($job_nm,$job_abbr,$org_nm,$org_abbr,$nip,$employee_nm,$person_id)=$db->fetchRow($result);
      
      $sql = "SELECT a.pms_share_weight FROM pms_actionplan_share a"
           . " LEFT JOIN pms_objective b USING(pms_objective_id)"
           . " WHERE a.psid = '$psid' AND a.pms_org_id = '$pms_org_id'"
           . " AND a.pms_actionplan_pic_employee_id = '$employee_id'"
           . " AND b.pms_objective_id IS NOT NULL";
      $resultshare = $db->query($sql);
      $ttl_share = 0;
      if($db->getRowsNum($resultshare)>0) {
         while(list($pms_pic_share_weight)=$db->fetchRow($resultshare)) {
            $ttl_share = _bctrim(bcadd($ttl_share,$pms_pic_share_weight));
         }
      } else {
         $ttl_share = 0;
      }
      
      
      $ret = "<table style='margin-left:2px;'><tr><td>"
            . "<img src='".XOCP_SERVER_SUBDIR."/modules/hris/thumb.php?pid=${person_id}' height='70' style='border:1px solid #555;'/></td>"
            . "<td style='vertical-align:top;padding-left:10px;'>"
            . "<table style='margin-left:0px;font-size:0.9em;'><colgroup><col width='90'/><col/><col/></colgroup><tbody>"
            . "<tr><td>Job Title</td><td>:</td><td>$job_nm ($job_abbr)</td></tr>"
            . "<tr><td>Section/Division</td><td>:</td><td>$org_nm ($org_abbr)</td></tr>"
            . "<tr><td>Incumbent</td><td>:</td><td>$employee_nm</td></tr>"
            . "<tr><td>NIP</td><td>:</td><td>$nip</td></tr>"
            . "<tr><td>Total Share</td><td>:</td><td>".toMoney($ttl_share)." %</td></tr>"
            . "</tbody></table></td></tr></table>"
            . "<table class='xxfrm' style='width:100%;font-size:0.9em;'><colgroup><col width='90'/><col/></colgroup><tbody>"
//            . "<tr><td>Total Share</td><td>".toMoney($ttl_share)." %</td></tr>"
            . "<tr><td colspan='3' style='text-align:center;' id='tdbtn'>"
            . "<input type='button' value='Close' onclick='close_pic(\"$employee_id\");' class='xaction'/>&nbsp;&nbsp;"
            . "<input type='button' value='Delete' onclick='delete_pic(\"$employee_id\");'/>"
            . "</td></tr>"
            . "</tbody></table>";
      return array(1,$ret);
      
   }
   
   function app_calcRemainingShare($args) {
      $psid = $_SESSION["pms_psid"];
      $db=&Database::getInstance();
      $pms_objective_id = $args[0];
      $pms_actionplan_id = $args[1];
      $employee_id = $args[2];
      
      $sql = "SELECT pms_actionplan_weight FROM pms_actionplan WHERE pms_objective_id = '$pms_objective_id' AND pms_actionplan_id = '$pms_actionplan_id'";
      $result = $db->query($sql);
      list($pms_actionplan_weight)=$db->fetchRow($result);
      
      $sql = "SELECT pms_share_weight,pms_actionplan_pic_employee_id,pms_actionplan_id FROM pms_actionplan_share WHERE psid = '$psid' AND pms_objective_id = '$pms_objective_id' AND pms_actionplan_id = '$pms_actionplan_id'";
      $result = $db->query($sql);
      $ttl = 0;
      $ttl_other = 0;
      $this_share = 0;
      if($db->getRowsNum($result)>0) {
         while(list($pms_share_weightx,$pms_actionplan_pic_employee_idx,$pms_actionplan_idx)=$db->fetchRow($result)) {
            if($employee_id==$pms_actionplan_pic_employee_idx&&$pms_actionplan_idx==$pms_actionplan_id) {
               $this_share = $pms_share_weightx;
            } else {
               if($pms_actionplan_idx==$pms_actionplan_id) {
                  $ttl_other = _bctrim(bcadd($ttl_other,$pms_share_weightx));
               }
            }
            $ttl = _bctrim(bcadd($ttl,$pms_share_weightx));
         }
      }
      
      $sql = "SELECT pms_perspective_id,pms_objective_weight FROM pms_objective WHERE psid = '$psid' AND pms_objective_id = '$pms_objective_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($pms_perspective_id,$pms_objective_weight)=$db->fetchRow($result);
      }
      
      $actionplan_weight = $pms_objective_weight*$pms_actionplan_weight/100;
      
      $ret = "Objective Weight: ".toMoney($pms_objective_weight)." %<br/>"
           . "Action Plan Weight : ".toMoney($actionplan_weight)." %<br/>"
           . "Used : ".toMoney($ttl)." %<br/>"
           . "Remaining : <span class='xlnk' onclick='get_all_remaining(\"$pms_objective_id\",\"$pms_actionplan_id\",\"$employee_id\",this,event);'>".toMoney(bcsub($actionplan_weight,$ttl))."</span> %";
      
      $this_share_100 = _bctrim((100*$this_share/$actionplan_weight));
      $remaining = _bctrim(bcsub($actionplan_weight,$ttl_other));
      $remaining_100 = _bctrim(100*$remaining/$actionplan_weight);
      $ret = array($remaining,
                   $ret,
                   $this_share,
                   number_format($this_share_100,2,".",""),
                   number_format($remaining_100,2,".",""),
                   $this->calcTotalShared($pms_perspective_id));
      return $ret;
      
   }
   
   function app_savePICShare($args) {
      $psid = $_SESSION["pms_psid"];
      $db=&Database::getInstance();
      $pms_org_id = $_SESSION["pms_org_id"];
      $pms_objective_id = $args[0];
      $pms_actionplan_id = $args[1];
      $employee_id = $args[2];
      $pms_share_weight_100 = _bctrim(bcadd($args[3],0));
      
      if($pms_share_weight_100==0) {
         $sql = "DELETE FROM pms_actionplan_share"
           . " WHERE psid = '$psid' AND pms_objective_id = '$pms_objective_id'"
           . " AND pms_actionplan_id = '$pms_actionplan_id'"
           . " AND pms_actionplan_pic_employee_id = '$employee_id'";
         $db->query($sql);
      } else {
         
         $sql = "SELECT pms_objective_weight FROM pms_objective WHERE psid = '$psid' AND pms_objective_id = '$pms_objective_id'";
         $result = $db->query($sql);
         if($db->getRowsNum($result)>0) {
            list($pms_objective_weight)=$db->fetchRow($result);
         }
         
         $sql = "SELECT pms_actionplan_weight FROM pms_actionplan WHERE pms_objective_id = '$pms_objective_id' AND pms_actionplan_id = '$pms_actionplan_id'";
         $result = $db->query($sql);
         list($pms_actionplan_weight)=$db->fetchRow($result);
      
         $actionplan_weight = $pms_objective_weight*$pms_actionplan_weight/100;
         
         $pms_share_weight = _bctrim(bcdiv(bcmul($pms_share_weight_100,$actionplan_weight),100));
         
         $sql = "REPLACE INTO pms_actionplan_share (psid,pms_objective_id,pms_actionplan_id,pms_actionplan_pic_employee_id,pms_org_id,pms_share_weight)"
              . " VALUES ('$psid','$pms_objective_id','$pms_actionplan_id','$employee_id','$pms_org_id','$pms_share_weight')";
         $db->query($sql);
      }
      
      $sql = "SELECT pms_actionplan_weight FROM pms_actionplan"
           . " WHERE psid = '$psid' AND pms_objective_id = '$pms_objective_id' AND pms_actionplan_id = '$pms_actionplan_id'";
      $result = $db->query($sql);
      list($pms_actionplan_weight)=$db->fetchRow($result);
      
      $sql = "SELECT pms_objective_weight FROM pms_objective"
           . " WHERE psid = '$psid'"
           . " AND pms_objective_id = '$pms_objective_id'";
      $result = $db->query($sql);
      list($pms_objective_weight)=$db->fetchRow($result);
      
      $ap_pic_weight = 0;
      $sql = "SELECT a.pms_share_weight"
           . " FROM pms_actionplan_share a"
           . " WHERE a.psid = '$psid' AND a.pms_org_id = '$pms_org_id'"
           . " AND a.pms_objective_id = '$pms_objective_id'"
           . " AND a.pms_actionplan_id = '$pms_actionplan_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($pms_share_weight)=$db->fetchRow($result))  {
            $ap_pic_weight+=$pms_share_weight;
         }
      }
      
      //////////////////////////////////////////////////////////////////
      
      $ap_weight = 100*($ap_pic_weight/$pms_objective_weight);
      
      return array($pms_objective_id,
                   $pms_actionplan_id,
                   toMoney($pms_actionplan_weight)."%/".toMoney($ap_weight)."%",
                   $this->app_calcRemainingShare(array($pms_objective_id,$pms_actionplan_id,$employee_id)));
   }
   
   function app_editPICShare($args) {
      $psid = $_SESSION["pms_psid"];
      $db=&Database::getInstance();
      $pms_org_id = $_SESSION["pms_org_id"];
      $pms_objective_id = $args[0];
      $pms_actionplan_id = $args[1];
      $employee_id = $args[2];
      
      $sql = "SELECT pms_share_weight FROM pms_actionplan_share"
           . " WHERE psid = '$psid' AND pms_objective_id = '$pms_objective_id'"
           . " AND pms_actionplan_id = '$pms_actionplan_id'"
           . " AND pms_actionplan_pic_employee_id = '$employee_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($pms_share_weight)=$db->fetchRow($result);
      } else {
         $pms_share_weight = 0;
      }
      
      $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>"
           . "Edit PIC Share"
           . "</div>"
           . "<div style='padding:5px;background-color:#f2f2f2;color:#555;'>"
              . "<div style='border:1px solid #999;background-color:#fff;padding:4px;' id='frmpicshare'>"
                  . "<table style='width:100%;border-spacing:0px;'><tbody><tr><td style='height:115px;'>"
                  
                  . "<div style='max-height:115px;overflow:auto;padding-top:3px;'>"
                  
                  . "<div style='text-align:center;'>Share : <input id='pms_share_weight' type='text' value='$pms_share_weight' style='width:40px;text-align:center;'/> %</div>"
                  
                  . "</div>"
                  . "</td></tr></tbody></table>"
              . "</div>"
           . "</div>"
           . "<div style='text-align:center;padding:10px;background-color:#f2f2f2;height:100px;' id='frmbtn'>"
           . "<input type='button' value='"._SAVE."' onclick='save_pic_share(\"$pms_objective_id\",\"$pms_actionplan_id\",\"$employee_id\",this,event);'/>&nbsp;&nbsp;"
           . "<input type='button' value='"._CANCEL."' onclick='editpicsharebox.fade();'/>"
           . "</div>";
      
      return $ret;
   }
   
   function app_addPIC($args) {
      $psid = $_SESSION["pms_psid"];
      $db=&Database::getInstance();
      $pms_org_id = $_SESSION["pms_org_id"];
      $person_id = $args[0];
      
      $sql = "SELECT employee_id FROM ".XOCP_PREFIX."employee WHERE person_id = '$person_id'";
      $result = $db->query($sql);
      list($employee_id)=$db->fetchRow($result);
      
      $sql = "REPLACE INTO pms_org_actionplan_share (psid,pms_org_id,employee_id) VALUES ('$psid','$pms_org_id','$employee_id')";
      $db->query($sql);
   }
   
   function app_searchPIC($args) {
      $db=&Database::getInstance();
      
      $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>"
           . "Search Person In Charge"
           . "</div>"
           . "<div style='padding:5px;background-color:#f2f2f2;color:#555;'>"
              . "<div style='border:1px solid #999;background-color:#fff;padding:4px;' id='frmactionplan'>"
                  . "<table style='width:100%;border-spacing:0px;'><tbody><tr><td style='height:115px;'>"
                  
                  . "<div style='max-height:115px;overflow:auto;padding-top:3px;'>"
                  
                  . "<div style='text-align:center;'>Search : <input id='qemp' onkeydown='initqemployee(this,event);' type='text' class='searchBox' style='width:200px;'/></div>"
                  
                  . "</div>"
                  . "</td></tr></tbody></table>"
              . "</div>"
           . "</div>"
           . "<div style='text-align:center;padding:10px;background-color:#f2f2f2;height:100px;' id='frmbtn'>"
           . "<input type='button' value='"._CANCEL."' onclick='searchpicbox.fade();'/>"
           . "</div>";
      
      return $ret;
   
   }
   
   function app_deleteActionPlan($args) {
      $db=&Database::getInstance();
      $psid = $_SESSION["pms_psid"];
      $org_id = $_SESSION["pms_org_id"];
      $pms_objective_id = $args[0];
      $pms_actionplan_id = $args[1];
      $sql = "DELETE FROM pms_actionplan WHERE psid = '$psid' AND pms_objective_id = '$pms_objective_id' AND pms_actionplan_id = '$pms_actionplan_id'";
      $db->query($sql);
      $sql = "DELETE FROM pms_actionplan_share WHERE psid = '$psid' AND pms_objective_id = '$pms_objective_id' AND pms_actionplan_id = '$pms_actionplan_id'";
      $db->query($sql);
      
   }
   
   function app_saveActionPlan($args) {
      $psid = $_SESSION["pms_psid"];
      $db=&Database::getInstance();
      $org_id = $_SESSION["pms_org_id"];
      $vars = _parseForm($args[0]);
      $pms_objective_id = $vars["pms_objective_id"]+0;
      $is_new = 0;
      if($vars["pms_actionplan_id"]=="new") {
         $is_new = 1;
         $sql = "SELECT MAX(pms_actionplan_id) FROM pms_actionplan WHERE pms_objective_id = '$pms_objective_id'";
         $result = $db->query($sql);
         list($pms_actionplan_id)=$db->fetchRow($result);
         $pms_actionplan_id++;
         $sql = "INSERT INTO pms_actionplan (psid,pms_objective_id,pms_actionplan_id) VALUES ('$psid','$pms_objective_id','$pms_actionplan_id')";
         $db->query($sql);
      } else {
         $pms_actionplan_id = $vars["pms_actionplan_id"];
      }
      $pms_actionplan_weight = _bctrim(bcadd($vars["pms_actionplan_weight"],0));
      $sql = "UPDATE pms_actionplan SET "
           . "pms_actionplan_text = '".addslashes($vars["pms_actionplan_text"])."',"
           . "pms_actionplan_start = '".getSQLDate($vars["h_tm_start"])."',"
           . "pms_actionplan_stop = '".getSQLDate($vars["h_tm_stop"])."',"
           . "pms_actionplan_weight = '$pms_actionplan_weight'"
           . " WHERE pms_objective_id = '$pms_objective_id' AND pms_actionplan_id = '$pms_actionplan_id'";
      $db->query($sql);
      
      $sql = "SELECT pms_objective_weight FROM pms_objective"
           . " WHERE psid = '$psid'"
           . " AND pms_objective_id = '$pms_objective_id'";
      $result = $db->query($sql);
      list($pms_objective_weight)=$db->fetchRow($result);
      
      $ap_pic_weight = 0;
      $sql = "SELECT a.pms_actionplan_pic_employee_id,a.pms_share_weight"
           . " FROM pms_actionplan_share a"
           . " WHERE a.psid = '$psid' AND a.pms_org_id = '$org_id'"
           . " AND a.pms_objective_id = '$pms_objective_id'"
           . " AND a.pms_actionplan_id = '$pms_actionplan_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($employee_id,$pms_share_weight)=$db->fetchRow($result))  {
            $ap_pic_weight+=$pms_share_weight;
         }
      }
      
      //////////////////////////////////////////////////////////////////
      
      $ap_weight = 100*($ap_pic_weight/$pms_objective_weight);
      
      if(trim($pms_actionplan_text)=="") {
         $pms_actionplan_text = _EMPTY;
      }
      
      $ap_text = "<span class='xlnk' onclick='edit_actionplan(\"$pms_objective_id\",\"$pms_actionplan_id\",this,event);'>".htmlentities($vars["pms_actionplan_text"],ENT_QUOTES)."</span>"
               . " (<span id='spapweight_${pms_objective_id}_${pms_actionplan_id}'>".toMoney($pms_actionplan_weight)."%/".toMoney($ap_weight)."%</span>)";

      return array($is_new,
                   $pms_objective_id,
                   $pms_actionplan_id,
                   htmlentities($vars["pms_actionplan_text"]),
                   sql2indshort($vars["h_tm_start"],"date")." - ".sql2indshort($vars["h_tm_stop"],"date"),
                   toMoney($pms_actionplan_weight)."%/".toMoney($ap_weight)."%",
                   $arr_emp,
                   $ap_text);
   }
   
   function app_editActionPlan($args) {
      $psid = $_SESSION["pms_psid"];
      $db=&Database::getInstance();
      $pms_objective_id = $args[0];
      $pms_actionplan_id = $args[1];
      $org_id = $_SESSION["pms_org_id"];
      if($pms_actionplan_id=="new") {
         $title = "Add New Action Plan";
         $btn = "<input type='button' value='Add New' onclick='save_actionplan(\"$pms_objective_id\",\"$pms_actionplan_id\",this,event);'/>&nbsp;&nbsp;"
              . "<input type='button' value='"._CANCEL."' onclick='editactionplanbox.fade();'/>";
         $tm_start = getSQLDate("2010-01-01");
         $tm_stop = getSQLDate("2010-12-31");
         $sql = "SELECT start_dttm,stop_dttm FROM pms_session WHERE psid = '$psid'";
         $pms_actionplan_weight = 0;
         $result = $db->query($sql);
         list($tm_start,$tm_stop)=$db->fetchRow($result);
      } else {
         $title = "Edit Action Plan";
         $sql = "SELECT pms_actionplan_weight,pms_actionplan_text,pms_actionplan_start,pms_actionplan_stop,pms_actionplan_id FROM pms_actionplan WHERE pms_objective_id = '$pms_objective_id' AND pms_actionplan_id = '$pms_actionplan_id'";
         $result = $db->query($sql);
         list($pms_actionplan_weight,$pms_actionplan_text,$tm_start,$tm_stop,$pms_actionplan_idxxx)=$db->fetchRow($result);
         $btn = "<input type='button' value='"._SAVE."' onclick='save_actionplan(\"$pms_objective_id\",\"$pms_actionplan_id\",this,event);'/>&nbsp;&nbsp;"
              . "<input type='button' value='"._CANCEL."' onclick='editactionplanbox.fade();'/>&nbsp;&nbsp;&nbsp;"
              . "<input type='button' value='"._DELETE."' onclick='delete_actionplan(\"$pms_objective_id\",\"$pms_actionplan_id\",this,event);'/>";
      }
      
      $sql = "SELECT pms_perspective_id,pms_objective_no,pms_objective_text FROM pms_objective WHERE psid = '$psid' AND pms_objective_id = '$pms_objective_id'";
      $result = $db->query($sql);
      list($pms_perspective_id,$pms_objective_no,$pms_objective_text)=$db->fetchRow($result);
      $sql = "SELECT pms_perspective_code,pms_perspective_name FROM pms_perspective WHERE pms_perspective_id = '$pms_perspective_id'";
      $result = $db->query($sql);
      list($pms_perspective_code,$pms_perspective_name)=$db->fetchRow($result);
      
      $sql = "SELECT pms_actionplan_weight FROM pms_actionplan WHERE psid = '$psid' AND pms_objective_id = '$pms_objective_id'";
      $result = $db->query($sql);
      $ap_weight_ttl = 0;
      if($db->getRowsNum($result)>0) {
         while(list($pms_actionplan_weightx)=$db->fetchRow($result)) {
            $ap_weight_ttl += $pms_actionplan_weightx;
         }
      }
      $ap_weight_remaining = 100-$ap_weight_ttl;
      
      $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>"
           . $title
           . "</div>"
           . "<div style='padding:5px;background-color:#f2f2f2;color:#555;'>"
              . "<div style='border:1px solid #999;background-color:#fff;padding:4px;' id='frmactionplan'>"
                  . "<table style='width:100%;border-spacing:0px;'><tbody><tr><td style='height:115px;'>"
                  
                  . "<div style='max-height:140px;overflow:auto;padding-top:3px;'>"
                  . "<table class='xxfrm' style='width:100%;'><tbody>"
                  . "<tr><td>Perspective</td><td>${pms_perspective_name}</td></tr>"
                  . "<tr><td>Strategic Objective</td><td>${pms_perspective_code}${pms_objective_no} - $pms_objective_text</td></tr>"
                  . "<tr><td>Action Plan</td><td><input type='text' id='pms_actionplan_text' name='pms_actionplan_text' style='width:430px;' value='$pms_actionplan_text'/></td></tr>"
                  . "<tr><td>Weight</td><td><input type='text' id='pms_actionplan_weight' name='pms_actionplan_weight' style='text-align:center;width:40px;' value='$pms_actionplan_weight'/> % "
                  . "<span style='color:blue;'>( <span class='xlnk'>".toMoney($ap_weight_remaining)."</span> % remaining )</span>"
                  . "</td></tr>"
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
                  . "<input type='hidden' name='pms_actionplan_id' id='pms_actionplan_id' value='$pms_actionplan_id'/>"
                  . "</td></tr></tbody></table>"
              . "</div>"
           . "</div>"
           . "<div style='text-align:center;padding:10px;background-color:#f2f2f2;height:100px;' id='frmbtn'>"
           . $btn
           . "</div>";
      
      return $ret;
   }
   
   
   
   function app_setSOOrigin($args) {
      $psid = $_SESSION["pms_psid"];
      $db=&Database::getInstance();
      $pms_parent_objective_id = $args[0];
      $org_id = $_SESSION["pms_org_id"];
      
      
      $sql = "SELECT p.pms_perspective_code,a.pms_objective_no,a.pms_org_id,a.pms_objective_text,b.org_nm,c.org_class_nm"
           . " FROM pms_objective a"
           . " LEFT JOIN ".XOCP_PREFIX."orgs b ON b.org_id = a.pms_org_id"
           . " LEFT JOIN ".XOCP_PREFIX."org_class c USING(org_class_id)"
           . " LEFT JOIN pms_perspective p ON p.pms_perspective_id = a.pms_perspective_id"
           . " WHERE a.psid = '$psid' AND a.pms_objective_id = '$pms_parent_objective_id'";
      $result = $db->query($sql);
      list($pms_perspective_code,$pms_objective_no,$pms_parent_org_id,$pms_parent_objective_text,$pms_parent_org_nm,$pms_parent_org_class_nm)=$db->fetchRow($result);
      
      $sql = "SELECT pms_share_weight FROM pms_kpi_share WHERE psid = '$psid' AND pms_org_id = '$pms_parent_org_id' AND pms_share_org_id = '$org_id' AND pms_objective_id = '$pms_parent_objective_id'";
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
           . "<tr><td>Organization</td><td>$pms_parent_org_nm $pms_parent_org_class_nm</td></tr>"
           . "<tr><td>Strategic Objective</td><td>${pms_perspective_code}${pms_objective_no} $pms_parent_objective_text</td></tr>"
           . "<tr><td>Weight</td><td>$ttlweight %</td></tr>"
           . "<tr><td colspan='2'><input type='button' value='Select Source' onclick='change_so_origin(this,event);'/></td></tr>"
           . "</tbody></table><input type='hidden' name='pms_parent_objective_id' id='pms_parent_objective_id' value='$pms_parent_objective_id'/>";
      return $ret;
   }
   
   function app_saveKPIShare($args) {
      $psid = $_SESSION["pms_psid"];
      $db=&Database::getInstance();
      $pms_objective_id = $args[0];
      $pms_kpi_id = $args[1];
      $pms_share_org_id = $args[2];
      $pms_org_id = $_SESSION["pms_org_id"];
      $vars = _parseForm($args[3]);
      
      $pms_share_weight = _bctrim(bcadd($vars["pms_share_weight"],0));
      
      if(bccomp($pms_share_weight,0)<=0) {
         $sql = "DELETE FROM pms_kpi_share"
              . " WHERE psid = '$psid' AND pms_objective_id = '$pms_objective_id'"
              . " AND pms_kpi_id = '$pms_kpi_id'"
              . " AND pms_org_id = '$pms_org_id'"
              . " AND pms_share_org_id = '$pms_share_org_id'";
      } else {
         $sql = "REPLACE INTO pms_kpi_share (psid,pms_objective_id,pms_kpi_id,pms_org_id,pms_share_org_id,pms_share_weight)"
              . " VALUES ('$psid','$pms_objective_id','$pms_kpi_id','$pms_org_id','$pms_share_org_id','$pms_share_weight')";
      }
      $db->query($sql);
   }
   
   function app_editKPIShare($args) {
      $psid = $_SESSION["pms_psid"];
      $db=&Database::getInstance();
      $pms_objective_id = $args[0];
      $pms_kpi_id = $args[1];
      $pms_share_org_id = $args[2];
      $org_id = $_SESSION["pms_org_id"];
      
      $btn = "<input type='button' value='"._SAVE."' onclick='save_kpi_share(\"$pms_objective_id\",\"$pms_kpi_id\",\"$pms_share_org_id\",this,event);'/>&nbsp;&nbsp;"
           . "<input type='button' value='"._CANCEL."' onclick='editkpisharebox.fade();'/>";
      
      $sql = "SELECT pms_kpi_text,pms_kpi_weight,pms_kpi_start,pms_kpi_stop,pms_kpi_target_text,pms_kpi_measurement_unit,pms_kpi_pic_job_id"
           . " FROM pms_kpi WHERE psid = '$psid' AND pms_objective_id = '$pms_objective_id' AND pms_kpi_id = '$pms_kpi_id'";
      $result = $db->query($sql);
      list($pms_kpi_text,$pms_kpi_weight,$tm_start,$tm_stop,$pms_kpi_target_text,$pms_kpi_measurement_unit,$pms_kpi_pic_job_id)=$db->fetchRow($result);
      $sql = "SELECT a.org_abbr,a.org_nm,b.org_class_nm FROM ".XOCP_PREFIX."orgs a"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . " WHERE a.org_id = '$pms_share_org_id'";
      $result = $db->query($sql);
      list($pms_share_org_abbr,$pms_share_org_nm,$pms_share_org_class_nm)=$db->fetchRow($result);
      $sql = "SELECT pms_perspective_id,pms_objective_no,pms_objective_text FROM pms_objective WHERE psid = '$psid' AND pms_objective_id = '$pms_objective_id'";
      $result = $db->query($sql);
      list($pms_perspective_id,$pms_objective_no,$pms_objective_text)=$db->fetchRow($result);
      $sql = "SELECT pms_perspective_code,pms_perspective_name FROM pms_perspective WHERE psid = '$psid' AND pms_perspective_id = '$pms_perspective_id'";
      $result = $db->query($sql);
      list($pms_perspective_code,$pms_perspective_name)=$db->fetchRow($result);
      
      $sql = "SELECT pms_share_weight FROM pms_kpi_share"
           . " WHERE psid = '$psid' AND pms_objective_id = '$pms_objective_id'"
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
      $psid = $_SESSION["pms_psid"];
      $db=&Database::getInstance();
      $org_id = $_SESSION["pms_org_id"];
      $pms_objective_id = $args[0];
      $pms_kpi_id = $args[1];
      $sql = "DELETE FROM pms_kpi WHERE psid = '$psid' AND pms_objective_id = '$pms_objective_id' AND pms_kpi_id = '$pms_kpi_id'";
      $db->query($sql);
      
   }
   
   function app_saveKPI($args) {
      $psid = $_SESSION["pms_psid"];
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
         $btn = "<input type='button' value='Add New' onclick='save_kpi(\"$pms_objective_id\",\"$pms_kpi_id\",this,event);'/>&nbsp;&nbsp;"
              . "<input type='button' value='"._CANCEL."' onclick='editkpibox.fade();'/>";
         $tm_start = getSQLDate();
         $tm_stop = getSQLDate();
      } else {
         $title = "Edit KPI";
         $sql = "SELECT pms_kpi_text,pms_kpi_weight,pms_kpi_start,pms_kpi_stop,pms_kpi_target_text,pms_kpi_measurement_unit,pms_kpi_pic_job_id"
              . " FROM pms_kpi WHERE pms_objective_id = '$pms_objective_id' AND pms_kpi_id = '$pms_kpi_id'";
         $result = $db->query($sql);
         list($pms_kpi_text,$pms_kpi_weight,$tm_start,$tm_stop,$pms_kpi_target_text,$pms_kpi_measurement_unit,$pms_kpi_pic_job_id)=$db->fetchRow($result);
         $btn = "<input type='button' value='"._SAVE."' onclick='save_kpi(\"$pms_objective_id\",\"$pms_kpi_id\",this,event);'/>&nbsp;&nbsp;"
              . "<input type='button' value='"._CANCEL."' onclick='editkpibox.fade();'/>&nbsp;&nbsp;&nbsp;"
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
            $optpic .= "<option value='$job_id' ".($job_id==$pms_kpi_pic_job_id?"selected='1'":"").">$job_abbr - $job_nm</option>";
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
                  . "<tr><td>Weight</td><td><input id='pms_kpi_weight' name='pms_kpi_weight' type='text' style='width:40px;' value='$pms_kpi_weight'/> %</td></tr>"
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
           . "<input type='button' value='"._CANCEL."' onclick='vsharebox.fade();'/>&nbsp;&nbsp;"
           . "<input type='button' value='"._DELETE."' onclick='delete_share(\"$pms_share_org_id\",this,event);'/>"
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
   
   function app_deleteSO($args) {
      $db=&Database::getInstance();
      $pms_objective_id = $args[0];
      $sql = "DELETE FROM pms_objective WHERE pms_objective_id = '$pms_objective_id'";
      $db->query($sql);
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
           . "pms_objective_no = '".($vars["pms_objective_no"]+0)."',"
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
      if($pms_objective_id=="new") {
         $title = "Add New Strategic Objective";
         $pms_perspective_id = $_SESSION["pms_perspective_last"];
         if($pms_perspective_id==0) $pms_perspective_id = 1;
         $sql = "SELECT MAX(pms_objective_no) FROM pms_objective"
              . " WHERE pms_perspective_id = '$pms_perspective_id'"
              . " AND pms_org_id = '$org_id'";
         $result = $db->query($sql);
         list($pms_objective_no)=$db->fetchRow($result);
         $pms_objective_no++;
         $btn = "<input type='button' value='Add New' onclick='save_so(\"$pms_objective_id\",this,event);'/>&nbsp;&nbsp;"
              . "<input type='button' value='"._CANCEL."' onclick='editsobox.fade();'/>";
         $tm_start = getSQLDate();
         $tm_stop = getSQLDate();
         $sql = "SELECT pms_perspective_code,pms_perspective_id,pms_perspective_name FROM pms_perspective ORDER BY pms_perspective_id";
         $result = $db->query($sql);
         $optpers = "";
         if($db->getRowsNum($result)>0) {
            while(list($pms_perspective_codex,$pms_perspective_idx,$pms_perspective_name)=$db->fetchRow($result)) {
               $optpers .= "<option value='$pms_perspective_idx|$pms_perspective_codex' ".($pms_perspective_idx==$pms_perspective_id?"selected='1'":"").">$pms_perspective_name</option>";
            }
         }
         $sel_pers = "<tr><td>Perspective</td><td><select id='pms_perspective_id' name='pms_perspective_id' onchange='chgpers(this,event);'>$optpers</select>&nbsp;No : <input onclick='_dsa(this,event);' onkeypress='chgno(this,event);' name='pms_objective_no' id='pms_objective_no' type='text' style='width:40px;text-align:center;' value='$pms_objective_no'/></td></tr>";
         $pms_parent_objective_id = 0;
         
         $source_so = "Strategic Objective Source:"
                    . "<div id='parent_so'>"
                    . "<div style='text-align:center;padding:20px;border:1px solid #bbb;-moz-border-radius:5px;'>"
                    . "<span style='font-style:italic;'>No source selected. Please click 'Select Source' or leave it for local type objective.</span><br/><br/>"
                    . "<input type='button' value='Select Source' onclick='change_so_origin(this,event);'/>"
                    . "<input type='hidden' name='pms_parent_objective_id' id='pms_parent_objective_id' value='0'/>"
                    . "</div>"
                    . "</div>";
         $initiative_btn = "&nbsp;";
      } else {
         $sql = "SELECT pms_parent_objective_id,pms_objective_no,pms_objective_text,pms_kpi_text,pms_target_text,pms_measurement_unit,pms_objective_weight,pms_objective_start,pms_objective_stop,pms_perspective_id,pms_pic_job_id"
              . " FROM pms_objective WHERE pms_objective_id = '$pms_objective_id'";
         $result = $db->query($sql);
         list($pms_parent_objective_id,$pms_objective_no,$pms_objective_text,$pms_kpi_text,$pms_target_text,$pms_measurement_unit,$pms_objective_weight,$tm_start,$tm_stop,$pms_perspective_id,$pms_pic_job_id)=$db->fetchRow($result);
         $sql = "SELECT pms_perspective_code,pms_perspective_name FROM pms_perspective WHERE pms_perspective_id = '$pms_perspective_id'";
         $result = $db->query($sql);
         list($pms_perspective_code,$pms_perspective_name)=$db->fetchRow($result);
         
         $title = "Edit Strategic Objective";
         $initiative_btn = "<input type='button' value='Create Initiative' onclick='add_initiative(\"$pms_objective_id\",this,event);'/>";
         $btn = "<input type='button' value='"._SAVE."' onclick='save_so(\"$pms_objective_id\",this,event);'/>&nbsp;&nbsp;"
              . "<input type='button' value='"._CANCEL."' onclick='editsobox.fade();'/>&nbsp;&nbsp;&nbsp;"
              . "<input type='button' value='"._DELETE."' onclick='delete_so(\"$pms_objective_id\",this,event);'/>";
         $sel_pers = "<tr><td>Perspective</td><td>$pms_perspective_name"
                   . "<input type='hidden' name='pms_perspective_id' value='$pms_perspective_id'/>"
                   . "<input type='hidden' name='pms_objective_no' value='$pms_objective_no'/>"
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
         }
         
         
         $source_so = "Strategic Objective Source:"
                    . "<div id='parent_so'>"
                    . "<table class='xxfrm' style='width:100%;'>"
                    . "<colgroup><col width='150'/><col/></colgroup>"
                    . "<tbody>"
                    . "<tr><td>Organization</td><td>$pms_parent_org_nm $pms_parent_org_class_nm</td></tr>"
                    . "<tr><td>Strategic Objective</td><td>${pms_parent_perspective_code}${pms_parent_objective_no} $pms_parent_objective_text</td></tr>"
                    . "<tr><td>Weight</td><td>$source_so_ttlweight %</td></tr>"
                    . "<tr><td colspan='2'><input type='button' value='Select Source' onclick='change_so_origin(this,event);'/></td></tr>"
                    . "</tbody></table>"
                    . "<input type='hidden' name='pms_parent_objective_id' id='pms_parent_objective_id' value='$pms_parent_objective_id'/>"
                    . "</div>";
         
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
                    . "<td>$shared_org_nm $shared_org_class_nm</td>"
                    . "<td>${shared_pms_perspective_code}${shared_pms_objective_no} - $shared_pms_objective_text</td>"
                    . "<td style='text-align:center;'>$shared_pms_share_weight</td>"
                    . "<td style='text-align:center;'>$btnstatus</td>"
                    . "</tr>";
         }
      } else {
         $selso .= "<tr><td colspan='6' style='text-align:center;font-style:italic;color:#888;'>No shared strategic objective found.</td></tr>";
      }
      $selso .= "</tbody>"
              . "<tfoot>"
              . "<tr><td colspan='6' style='text-align:center;'><input type='button' value='"._CANCEL."' onclick='cancel_change_origin(this,event);'/></td></tr>"
              . "</tfoot>"
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
         while(list($job_id,$job_cd,$job_nm,$description,$org_nm,$org_class_nm,$job_abbr)=$db->fetchRow($result)) {
            $optpic .= "<option value='$job_id' ".($job_id==$pms_pic_job_id?"selected='1'":"").">$job_abbr - $job_nm</option>";
         }
      }
      
      $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>"
           . $title
           . "</div>"
           . "<div style='padding:5px;background-color:#f2f2f2;color:#555;'>"
              . "<div style='border:1px solid #999;background-color:#fff;padding:4px;' id='frmobjective'>"
                  . "<table style='width:100%;border-spacing:0px;'><tbody><tr><td style='height:375px;'>"
                  
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
                  . "<tr><td>ID</td><td id='pms_obj_code'>${pms_perspective_code}${pms_objective_no}</td></tr>"
                  
                  
                  . $sel_pers
                  
                  . "<tr><td>Strategic Objective</td><td><input type='text' id='so_txt' name='so_txt' style='width:400px;' value='$pms_objective_text'/></td></tr>"
                  
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
           
           . "<table style='width:100%;border-spacing:0px;'><tbody><tr><td style='text-align:left;'>"
           . $initiative_btn
           . "</td><td style='text-align:right;'>$btn</td></tr></tbody></table>"
           
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
              . "<table style=''><tbody><tr><td class='orgbox' onclick='do_select_org(\"$org_id\",this,event);'><div style='-moz-box-shadow:1px 1px 3px #000;padding:5px;border:1px solid #999;'>$org_nm $org_class_nm</div></td></tr></tbody></table>"
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

} /// HRIS_INITIATIVEAJAX_DEFINED
?>