<?php
//--------------------------------------------------------------------//
// Filename : modules/pms/pmsperspective.php                          //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-07-18                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('PMS_INITIATIVE_DEFINED') ) {
   define('PMS_INITIATIVE_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

include_once(XOCP_DOC_ROOT."/modules/pms/pmsxocp.php");
include_once(XOCP_DOC_ROOT."/modules/pms/class/ajax_initiative.php");
include_once(XOCP_DOC_ROOT."/modules/pms/class/selectpms.php");
include_once(XOCP_DOC_ROOT."/modules/pms/include/pms.php");

class _pms_Initiative extends XocpBlock {
   var $catchvar = _PMS_CATCH_VAR;
   var $blockID = _PMS_INITIATIVE_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _PMS_INITIATIVE_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _pms_Initiative($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */

   }
   
   function showOrg($showOpt=FALSE) {
      $db =& Database::getInstance();
      if(!isset($_SESSION["pms_org_id"])) {
         $_SESSION["pms_org_id"] = 1;
      }
      
      $org_id = $_SESSION["pms_org_id"];
      $sql = "SELECT o.org_id,o.org_nm,b.org_class_nm,o.org_abbr"
           . " FROM ".XOCP_PREFIX."orgs o"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b ON b.org_class_id = o.org_class_id"
           . " WHERE o.org_id = '$org_id'";
      $result = $db->query($sql);
      $cnt = $db->getRowsNum($result);
      $showOpt = FALSE;
      $org_nm = "-";
      if($cnt == 1) {
         list($org_id,$org_nmx,$org_class_nm,$org_abbr) = $db->fetchRow($result);
         $_SESSION["hris_org_nm"] = "$org_abbr $org_nmx [$org_class_nm]";
         $org_nm = "$org_nmx $org_class_nm";
      } else if($cnt > 1) {
         $found = 0;
         while(list($org_id,$org_nmx,$org_class_nm)=$db->fetchRow($result)) {
            if($org_id==$_SESSION["hris_org_id"]) {
               $found = 1;
               $org_nm = "$org_abbr $org_nmx [$org_class_nm]";
               break;
            }
         }
         if($found==0) {
            $showOpt = TRUE;
         }
      } else {
         $_SESSION["hris_org_nm"] = NULL;
         $_SESSION["hris_org_id"] = 0;
         $showOpt = TRUE;
      }
      if($org_nm == "") $org_nm = "-";
      
      require_once(XOCP_DOC_ROOT."/modules/pms/class/ajax_selectorg.php");
      $ajax = new _hris_class_SelectOrgAjax("slrjx");
      $js = "";
      //$js .= "\n<script type=\"text/javascript\" src=\"".XOCP_SERVER_SUBDIR."/include/treeorg.js\"></script>";
      $js .= $ajax->getJs();
      $js .= "\n<script type='text/javascript'>\n//<![CDATA[
     
      function _org_select_org(org_id,d,e) {
         slrjx_app_setOrg(org_id,function(_data) {
            SetCookie('pms_objective_scroll_pos',0);
            location.reload();
         });
      }
      
      var dv = null;
      function show_org_opt(d,e) {
         ajax_feedback = _caf;
         var Element = _gel('list_org');
         if (dv&&dv.style.display!='none') {
            var uls = _gel('navSlide');
            var dvx = _gel('dvSlide');
            new Effect.toggle(Element,'blind',{duration:0.2}); 
         } else {
            _destroy(uls);  
            dv = document.createElement('div');
            dv.setAttribute('id','dvSlide');
            dv.innerHTML = '';
            dv = Element.appendChild(dv);
            Element.dv = dv;
            Element.dv.appendChild(progress_span());
            slrjx_app_getOrgOpt(function(_data) {
               Element.dv.innerHTML = _data;
               new Effect.toggle(Element,'blind',{duration:0.2});
               OrgResetBranches();
            });
          
         }
         return true;
      }
      
      var newHref = null;
      function selorgopt(org_id,org_nm) {
         var Element = _gel('list_org');
         new Effect.toggle(Element,'blind',{duration:0.2});
         slrjx_app_setOrg(org_id,obj_id,null);
         newHref = '".XOCP_SERVER_SUBDIR."/index.php?X_hris="._HRIS_SELECTORG_BLOCK."&org_id='+org_id+'&obj_id='+obj_id;
         setTimeout('gotoOrg();',300);
      }
      
      function gotoOrg() {
         location.href = newHref;
      }

      ".($showOpt==TRUE?"setTimeout('show_org_opt(null,null);',100);":"")."
      
      //]]>\n</script>";
      
      $js .= "\n<script type=\"text/javascript\" src=\"".XOCP_SERVER_SUBDIR."/modules/pms/include/treeorg.js\"></script>";
      
      
      return $js."<div class='orgsel'><table border='0' width='100%' cellpadding='2' cellspacing='0'>
              <tr><td id='hris_org_nm'>Level of Organization : <span style='font-weight:bold;'>$org_nm</span></td>
              <td align='right'>[<span class='xlnk' id='chorgsp' onclick='return show_org_opt(this,event);'>Change Level"
              ."</span>]</td></tr></table><div id='list_org' style='display:none;background-color:#ffffff;text-align:left;'></div></div>";
   }

   function recurseParentOrg($pms_objective_id) {
      $db=&Database::getInstance();
      $pms_org_id = 0;
      $sql = "SELECT pms_org_id,pms_parent_objective_id FROM pms_objective WHERE pms_objective_id = '$pms_objective_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($pms_org_id,$pms_parent_objective_id)=$db->fetchRow($result);
         if($pms_parent_objective_id>0) {
            return $this->recurseParentOrg($pms_parent_objective_id);
         }
      }
      $sql = "SELECT parent_id FROM ".XOCP_PREFIX."orgs WHERE org_id = '$pms_org_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($parent_id)=$db->fetchRow($result);
         return $parent_id; //// return parent_id, hopefully corporate org_id
      }
      return -1;
   }
   
   function pmsobjective() {
      $psid = $_SESSION["pms_psid"];
      $db=&Database::getInstance();
      $ajax = new _pms_class_InitiativeAjax("orgjx");
      
      $_SESSION["html"]->registerLoadAction("my_scrollto");
      $_SESSION["html"]->addHeadScript("<script type='text/javascript'>//<![CDATA[\nfunction my_scrollto() { if(GetCookie('pms_actionplan_scroll_pos')>0) window.scrollTo(0,GetCookie('pms_actionplan_scroll_pos'));SetCookie('pms_actionplan_scroll_pos',0); }\n//]]></script>");
      
      $user_id = getUserID();
      
      $pmsselobj = new _pms_class_SelectSession();
      $pmssel = "<div style='padding-bottom:2px;'>".$pmsselobj->show()."</div>";
      
      $orgsel = $this->showOrg();
      
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
      
      
      if(!isset($_SESSION["pms_org_id"])) {
         $_SESSION["pms_org_id"] = 1;
      }
      
      $org_id = $_SESSION["pms_org_id"];
      
      $found_access = 0;
      $sql = "SELECT pms_org_id,access_id FROM pms_org_access WHERE psid = '$psid' AND employee_id = '$self_employee_id' AND status_cd = 'normal'";
      $result = $db->query($sql);
      $first_access = 0;
      if($db->getRowsNum($result)>0) {
         while(list($pms_org_idx,$access_id)=$db->fetchRow($result)) {
            if($first_access==0) {
               $first_access = $pms_org_idx;
            }
            if($org_id==$pms_org_idx) {
               $_SESSION["pms_org_id"] = $pms_org_idx;
               $org_id = $_SESSION["pms_org_id"];
               $found_access = 1;
            }
         }
         if($first_access>0&&$found_access==0) {
            $_SESSION["pms_org_id"] = $first_access;
            $org_id = $_SESSION["pms_org_id"];
            $found_access = 1;
         }
      } else {
         $sql = "SELECT pms_org_id,access_id FROM pms_org_access WHERE psid = '$psid' AND employee_id = '0' AND status_cd = 'normal'";
         $result = $db->query($sql);
         
         $found_access = 0;
         $first_access = 0;
         if($db->getRowsNum($result)>0) {
            while(list($pms_org_idx,$access_id)=$db->fetchRow($result)) {
               if($first_access==0) {
                  $first_access = $pms_org_idx;
               }
               if($org_id==$pms_org_idx) {
                  $_SESSION["pms_org_id"] = $pms_org_idx;
                  $org_id = $_SESSION["pms_org_id"];
                  $found_access = 1;
               }
            }
            if($fist_access>0&&$found_access==0) {
               $_SESSION["pms_org_id"] = $first_access;
               $org_id = $_SESSION["pms_org_id"];
               $found_access = 1;
            }
         
         }
         
      }
      
      if($_SESSION["hr_pmsobjective"]==0&&$found_access==0) {
         return $pmssel.$orgsel."<div style='padding:5px;'>You don't have access privilege to setup objectives.</div>";
      }
      
      $sql = "SELECT org_class_id FROM ".XOCP_PREFIX."orgs WHERE org_id = '$org_id'";
      $result = $db->query($sql);
      list($current_org_class_id)=$db->fetchRow($result);
      
      global $allow_actionplan;
      
      if($allow_actionplan[$current_org_class_id]!=1) {
         $orgsel = $this->showOrg();
         return $pmssel.$orgsel."<div style='padding:5px;'>This organization level cannot have action plan.</div>";
      
      }
      
      if(!isset($_SESSION["pms_psid"])||$_SESSION["pms_psid"]==0) {
         return $pmssel;
      }
      
      $sql = "SELECT a.employee_id,c.person_nm,c.person_id,b.alias_nm"
           . " FROM pms_org_actionplan_share a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b ON b.employee_id = a.employee_id"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " WHERE a.psid = '$psid' AND a.pms_org_id = '$org_id'";
      $result = $db->query($sql);
      $tdshare = "";
      $share_arr = array();
      $share_cnt = 0;
      $colgroup = "";
      if($db->getRowsNum($result)>0) {
         $share_cnt = $db->getRowsNum($result);
         $sharehead = "<tr>"
                    . "<td colspan='6' style='background-color:#fff;text-align:right;border-right:0px solid #bbb;'>"
                        . "&nbsp;"
                    . "</td>"
                    . "<td colspan='$share_cnt' style='border-left:1px solid #bbb;text-align:center;'><span class='xlnk' onclick='search_pic(this,event);'>PIC</span> (%)</td>"
                    . "</tr>";
         while(list($employee_id,$person_nm,$person_id,$employee_alias_nm)=$db->fetchRow($result)) {
            if(trim($employee_alias_nm)=="") {
               $employee_alias_nm = $employee_id;
            }
            $tdshare .= "<td style='border-left:1px solid #bbb;text-align:center;'><span class='xlnk' onclick='view_pic(\"$employee_id\",this,event);' title='$person_nm'>$employee_alias_nm</span></td>";
            $share_arr[] = array($employee_id,$person_nm,$person_id);
            $colgroup .= "<col width='60'/>";
         }
      } else {
         
         $tdshare .= "<td style='border-left:1px solid #bbb;text-align:center;'>-</td>";
         $sharehead = "<tr>"
                    . "<td colspan='6' style='background-color:#fff;text-align:right;border-right:0px solid #bbb;'>"
                        . "&nbsp;"
                    . "</td>"
                    . "<td colspan='1' style='border-left:1px solid #bbb;text-align:center;'><span class='xlnk' onclick='search_pic(this,event);'>PIC</span> (%)</td>"
                    . "</tr>";
         $colgroup .= "<col width='50'/>";
         
      }
      
      reset($share_arr);
      
      $ret = "<table class='yylist' style='width:100%;'>"
           . "<colgroup>"
           . "<col width='50'/>"
           . "<col width='130'/>"
           . "<col width='60'/>"
           . "<col width='130'/>"
           . "<col width='*'/>" /// actionplan
           . "<col width='180'/>" /// schedule
           . $colgroup
           . "</colgroup>"
           . "<thead>"
           . $sharehead
           . "<tr>"
           . "<td style='text-align:center;border-right:1px solid #bbb;'>ID</td>"
           . "<td style='border-right:1px solid #bbb;'>Strategic Objective</td>"
           . "<td style='border-right:1px solid #bbb;text-align:center;'><div style='width:60px !important;'>Weight</div></td>"
           . "<td style='border-right:1px solid #bbb;'>KPI</td>"
           . "<td style='border-right:1px solid #bbb;'>Action Plan</td>"
           . "<td style='border-right:0px solid #bbb;'>Schedule</td>"
           . $tdshare
           . "</tr>"
           . "</thead>"
           . "<tbody>";
      
      $sql = "SELECT pms_perspective_code,pms_perspective_id,pms_perspective_name FROM pms_perspective WHERE psid = '$psid' ORDER BY pms_perspective_id";
      $result = $db->query($sql);
      $ttlw = 0;
      $job_nm = $job_abbr = "";
      $ttlpicshare_arr = array();
      if($db->getRowsNum($result)>0) {
         while(list($pms_perspective_code,$pms_perspective_id,$pms_perspective_name)=$db->fetchRow($result)) {
            $subttlpicshare_arr = array();
            $ret .= "<tr><td colspan='".(6+($share_cnt==0?1:$share_cnt))."' style='font-weight:bold;border-bottom:1px solid #888;color:black;background-color:#ddf;'>$pms_perspective_name Perspective</td></tr>";
            $sql = "SELECT pms_objective_id,pms_objective_no,pms_objective_text,pms_kpi_text,pms_target_text,pms_measurement_unit,pms_objective_weight,"
                 . "pms_pic_job_id,pms_pic_employee_id,pms_parent_objective_id"
                 . " FROM pms_objective"
                 . " WHERE psid = '$psid' AND pms_org_id = '$org_id'"
                 . " AND pms_perspective_id = '$pms_perspective_id'"
                 . " ORDER BY pms_objective_no";
            $ro = $db->query($sql);
            $cnt = $db->getRowsNum($ro);
            $so = "";
            $so_no = 0;
            if($cnt>0) {
               $subttlw = 0;
               while(list($pms_objective_id,$pms_objective_no,$pms_objective_text,$pms_kpi_text,$pms_target_text,$pms_measurement_unit,$pms_objective_weight,
                          $pms_pic_job_id,$pms_pic_employee_id,$pms_parent_objective_idx)=$db->fetchRow($ro)) {
                  
                  $top_level_org_id = $this->recurseParentOrg($pms_objective_id);
                  
                  
                  /// check if it is a local sub
                  $sql = "SELECT pms_org_id FROM pms_objective WHERE psid = '$psid' AND pms_objective_id = '$pms_parent_objective_idx'";
                  $rp = $db->query($sql);
                  if($db->getRowsNum($rp)>0) {
                     list($pms_parent_org_idx)=$db->fetchRow($rp);
                  }
                  
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
                  
                  
                  $sql = "SELECT a.job_nm,a.job_abbr FROM ".XOCP_PREFIX."jobs a WHERE a.job_id = '$pms_pic_job_id'";
                  $rj = $db->query($sql);
                  if($db->getRowsNum($rj)>0) {
                     list($so_pic_job_nm,$so_pic_job_abbr)=$db->fetchRow($rj);
                  } else {
                     $so_pic_job_nm = $so_pic_job_abbr = "";
                  }
                  $kpi_cnt = 0;
                  $sql = "SELECT pms_kpi_id,pms_kpi_text,pms_kpi_weight,pms_kpi_target_text,pms_kpi_measurement_unit"
                       . " FROM pms_kpi WHERE psid = '$psid' AND pms_objective_id = '$pms_objective_id'";
                  $rkpi = $db->query($sql);
                  $kpi_cnt = $db->getRowsNum($rkpi);
                  
                  //// query actionplan first
                  
                  $sql = "SELECT pms_actionplan_weight,pms_actionplan_text,pms_actionplan_start,pms_actionplan_stop,pms_actionplan_id FROM pms_actionplan WHERE pms_objective_id = '$pms_objective_id'";
                  $rap = $db->query($sql);
                  $ap_cnt = $db->getRowsNum($rap);
                  $ret .= "<tr id='trobjective_${pms_objective_id}'>"
                        . "<td rowspan='".($ap_cnt>0?$ap_cnt+1:1)."' style='font-weight:bold;text-align:left;border-right:1px solid #bbb;".($pms_parent_objective_idx>0&&$top_level_org_id==0?"color:blue;":"")."'>${pms_perspective_code}${pms_objective_no}</td>"
                        . "<td rowspan='".($ap_cnt>0?$ap_cnt+1:1)."' style='border-right:1px solid #bbb;'>"
                        . $pms_objective_text
                        . "<td rowspan='".($ap_cnt>0?$ap_cnt+1:1)."' style='border-right:1px solid #bbb;text-align:center;'>".toMoney($pms_objective_weight)."</td>";
                  $ret .= "<td rowspan='".($ap_cnt>0?$ap_cnt+1:1)."' style='border-right:1px solid #bbb;' ".($has_local_sub>0?"colspan='".(3+$share_cnt)."'":"").">"; //// kpi start
                  if($kpi_cnt>0) {
                     while(list($pms_kpi_id,$pms_kpi_text,$pms_kpi_weight,$pms_kpi_target_text,$pms_kpi_measurement_unit)=$db->fetchRow($rkpi)) {
                        $ret .= "<div>$pms_kpi_text : $pms_kpi_target_text ($pms_kpi_measurement_unit)</div>";
                     }
                  } else {
                     $ret .= "&nbsp;";
                  }
                  
                  $ret .= "</td>"; //// kpi stop
                  
                  
                  //// select action plan and schedule:
                  
                  if($has_local_sub==0) {
                     
                     if($ap_cnt>0) {
                        $apno = 0;
                        while(list($pms_actionplan_weight,$pms_actionplan_text,$pms_actionplan_start,$pms_actionplan_stop,$pms_actionplan_id)=$db->fetchRow($rap)) {
                           
                           if($apno>0) {
                              $ret .= "<tr>";
                           }
                           
                           list($yyyy_stop,$mm_stop,$dd_stop)=explode("-",$pms_actionplan_stop);
                           list($yyyy_start,$mm_start,$dd_start)=explode("-",$pms_actionplan_start);
                           
                           $ap_pic_weight = 0;
                           //////////////////////////////////////////////////////////////////
                           if($share_cnt>0&&$has_local_sub==0) {
                              foreach($share_arr as $v) {
                                 list($employee_id,$person_nm,$person_id)=$v;
                                 
                                 $sql = "SELECT pms_share_weight FROM pms_actionplan_share"
                                      . " WHERE psid = '$psid'"
                                      . " AND pms_objective_id = '$pms_objective_id'"
                                      . " AND pms_actionplan_id = '$pms_actionplan_id'"
                                      . " AND pms_actionplan_pic_employee_id = '$employee_id'";
                                 $resultshare = $db->query($sql);
                                 if($db->getRowsNum($resultshare)>0) {
                                    list($pms_pic_share_weight)=$db->fetchRow($resultshare);
                                 } else {
                                    $pms_pic_share_weight = 0;
                                 }
                                 $ap_pic_weight+=$pms_pic_share_weight;
                              }
                           }
                           //////////////////////////////////////////////////////////////////
                           
                           $ap_weight = 100*($ap_pic_weight/$pms_objective_weight);
                           if($pms_actionplan_weight==0&&$ap_weight>0) {
                              $sql = "UPDATE pms_actionplan SET "
                                   . "pms_actionplan_weight = '$ap_weight'"
                                   . " WHERE pms_objective_id = '$pms_objective_id' AND pms_actionplan_id = '$pms_actionplan_id'";
                              $db->query($sql);
                           }
                           
                           if(trim($pms_actionplan_text)=="") {
                              $pms_actionplan_text = _EMPTY;
                           }
                           
                           $ret .= "<td style='border-right:1px solid #bbb;'>"
                                 . "<span class='xlnk' onclick='edit_actionplan(\"$pms_objective_id\",\"$pms_actionplan_id\",this,event);'>$pms_actionplan_text</span>"
                                 . " (<span id='spapweight_${pms_objective_id}_${pms_actionplan_id}'>".toMoney($pms_actionplan_weight)."%/".toMoney($ap_weight)."%</span>)"
                                 . "</td>"
                                 . "<td id='tdschedule_${pms_objective_id}_${pms_actionplan_id}'>".sql2indshort($pms_actionplan_start)." - ".sql2indshort($pms_actionplan_stop)."</td>";
                           if($share_cnt>0&&$has_local_sub==0) {
                              foreach($share_arr as $v) {
                                 list($employee_id,$person_nm,$person_id)=$v;
                                 
                                 $sql = "SELECT pms_share_weight FROM pms_actionplan_share"
                                      . " WHERE pms_objective_id = '$pms_objective_id'"
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
                                 if(!isset($subttlpicshare_arr[$employee_id])) {
                                    $subttlpicshare_arr[$employee_id] = 0;
                                 }
                                 $subttlpicshare_arr[$employee_id] = _bctrim(bcadd($subttlpicshare_arr[$employee_id],$pms_pic_share_weight));
                                 
                                 if($pms_pic_share_weight==0) {
                                    $pms_pic_share_weight_txt = "<span style='color:#333;'>-</span>";
                                 } else {
                                    $pms_pic_share_weight_txt = "<span style='color:#33f;'>".toMoney($pms_pic_share_weight)."</span>";
                                 }
                                 $ret .= "<td onclick='edit_pic_share(\"$pms_objective_id\",\"$pms_actionplan_id\",\"$employee_id\",this,event);' style='vertical-align:middle;text-align:center;border-left:1px solid #bbb;' class='tdlnk'>$pms_pic_share_weight_txt</td>";
                                 
                              }
                              $ret .= "</tr>";
                           } else {
                              $ret .= "<td style='border-left:1px solid #bbb;'></td></tr>";
                           }
                           $apno++;
                        }
                        
                        /////////////// add action plan
                        $ret .= "<tr><td id='tdaddactionplan_${pms_objective_id}'>" /// colspan='".(2+($share_cnt>0?$share_cnt:1))."'>"
                              . ($has_local_sub>0?"":"[<span class='ylnk' onclick='edit_actionplan(\"$pms_objective_id\",\"new\",this,event);'>Add Action Plan</span>]")
                              . "</td><td></td>";
                        
                        if($share_cnt>0) {
                           for($ix=0;$ix<$share_cnt;$ix++) {
                              $ret .= "<td></td>";
                           }
                        }
                        $ret .= "</tr>";
                     } else {
                        /////////////// add action plan
                        $ret .= "<td id='tdaddactionplan_${pms_objective_id}' style='border-right:0px solid #Bbb;'>"
                              . ($has_local_sub>0?"":"[<span class='ylnk' onclick='edit_actionplan(\"$pms_objective_id\",\"new\",this,event);'>Add Action Plan</span>]")
                              . "</td><td></td>";
                        if($has_local_sub==0) {
                           if($share_cnt>0&&$has_local_sub==0) {
                              foreach($share_arr as $v) {
                                 list($employee_id,$person_nm,$person_id)=$v;
                                 $ret .= "<td style='text-align:center;border-left:0px solid #bbb;'>&nbsp;</td>";
                              }
                              $ret .= "</tr>";
                           
                           } else {
                              foreach($share_arr as $v) {
                                 $ret .= "<td style='border-left:1px solid #bbb;'></td></tr>";
                              }
                           }
                        }
                     
                     }
                  }
                  
                  
                  
                  $so_no++;
                  
                  $do_count = 0;
                  if($pms_parent_objective_idx==0) {
                     $do_count++;
                  } else {
                     $sql = "SELECT pms_org_id FROM pms_objective WHERE pms_objective_id = '$pms_parent_objective_idx'";
                     $rpx = $db->query($sql);
                     if($db->getRowsNum($rpx)>0) {
                        list($pms_parent_org_id)=$db->fetchRow($rpx);
                        if($pms_parent_org_id!=$org_id) {
                           $do_count++;
                        }
                     }
                  }
                  if($do_count>0) {
                     $subttlw = _bctrim(bcadd($subttlw,$pms_objective_weight));
                     $ttlw = _bctrim(bcadd($ttlw,$pms_objective_weight));
                  }
               }
               
               $ret .= "<tr>"
                     . "<td colspan='2' style='border-right:1px solid #bbb;text-align:center;'>Subtotal</td>"
                     . "<td style='text-align:center;background-color:#eeffff;font-weight:bold;color:black;border-right:1px solid #bbb;'>".toMoney($subttlw)."</td>"
                     . "<td colspan='3' style='border-right:0px solid #bbb;'></td>";
               if($share_cnt>0) {
                  foreach($share_arr as $v) {
                     list($employee_id,$person_nm,$person_id)=$v;
                     if(isset($subttlpicshare_arr[$employee_id])&&$subttlpicshare_arr[$employee_id]>0) {
                        $subttlpicshare = toMoney($subttlpicshare_arr[$employee_id]);
                     } else {
                        $subttlpicshare = "-";
                     }
                     $ret .= "<td id='tdsubttlpicshare_${pms_perspective_id}_${employee_id}' style='text-align:center;background-color:#eeffff;font-weight:bold;color:black;border-left:1px solid #bbb;'>$subttlpicshare</td>";
                  }
               } else {
                  $ret .= "<td>&nbsp;</td>";
               }
               $ret .= "</tr>";
            } else {
               $ret .= "<tr><td colspan='".(6+($share_cnt==0?1:$share_cnt))."' style='text-align:center;font-style:italic;'>"._EMPTY."</td></tr>";
            }
         }
      }
      
      $retshare = "";
      $ttlshared = 0;
      if($share_cnt>0) {
         foreach($ttlpicshare_arr as $employee_id=>$share) {
            $ttlshared = _bctrim(bcadd($ttlshared,$share));
         }
         $retshare .= "<td id='tdttlshared' style='text-align:center;background-color:#bbffdd;font-weight:bold;color:black;padding:10px;border:1px solid #bbb;border-right:0;border-top:0;'>".toMoney($ttlshared)."</td>";
         foreach($share_arr as $v) {
            list($employee_id,$person_nm,$person_id)=$v;
            if(isset($ttlpicshare_arr[$employee_id])&&$ttlpicshare_arr[$employee_id]>0) {
               $ttlpicshare = toMoney($ttlpicshare_arr[$employee_id]);
            } else {
               $ttlpicshare = "-";
            }
            $retshare .= "<td id='tdttlpicshare_${employee_id}' style='text-align:center;background-color:#bbffdd;font-weight:bold;color:black;padding:10px;border:1px solid #bbb;border-right:0;border-top:0;'>$ttlpicshare</td>";
         }
      } else {
         $retshare .= "<td>&nbsp;</td>";
      }
      
      $ret .= "<tr>"
            . "<td colspan='2' style='background-color:#fff;padding:10px;text-align:center;font-weight:bold;'>Total</td>"
            . "<td style='text-align:center;background-color:#bbffdd;font-weight:bold;color:black;padding:10px;border:1px solid #bbb;border-top:0;'>".toMoney($ttlw)."</td>"
            . "<td id='tdbalancewarning' colspan='2' style='background-color:#fff;padding:10px;text-align:center;'>";
      
      switch(bccomp(number_format($ttlw,2,".",""),number_format($ttlshared,2,".",""))) {
         case 1:
            $ret .= "<span style='color:red;'>Total objective weight is more than total shared.</span>";
            break;
         case -1:
            $ret .= "<span style='color:red;'>Total objective weight is less than total shared.</span>";
            break;
         default:
            $ret .= "&nbsp;";
            break;
      }
      
      $ret .= "</td>";
      
      $ret .= $retshare;
      
      $ret .= "</tr>";
      
      $ret .= "</tbody>"
            . "<tfoot>"
            . "<tr><td colspan='4'>"
            //. "<input type='button' value='Add Ini' onclick='edit_so(\"new\",this,event);'/>&nbsp;"
            . "</td>"
            . "<td colspan='".(2+($share_cnt==0?1:$share_cnt))."' style='text-align:right;'>"
            //. "<input type='button' value='Deploy Initiatives' class='xaction'/>"
            . "</td></tr>"
            . "</tfoot>"
            . "</table>";
      
      $ret .= "<div style='padding:100px;'>&nbsp;</div>";
      
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_employee.php");
      $ajaxemp = new _hris_class_EmployeeAjax("empajx");
      
      $js = $ajax->getJs().$ajaxemp->getJs()
          . "<script type='text/javascript' src='".XOCP_SERVER_SUBDIR."/include/calendar.js'>"
          . "<script type='text/javascript' src='".XOCP_SERVER_SUBDIR."/include/protovis-d3.2.js'>"
          . "</script><script type='text/javascript'><!-- 
      
      function do_delete_pic(employee_id) {
         orgjx_app_deletePIC(employee_id,function(_data) {
            SetCookie('pms_actionplan_scroll_pos',window.pageYOffset);
            location.reload();
         });
      }
      
      function close_pic() {
         document.body.onclick = null;
         _destroy(dvpic);
         dvpic.d = null;
         dvpic = null;
      }
      
      function cancel_delete_pic() {
         $('tdbtn').innerHTML = $('tdbtn').oldHTML;
      }
      
      function delete_pic(employee_id) {
         $('tdbtn').oldHTML = $('tdbtn').innerHTML;
         $('tdbtn').innerHTML = '<div style=\"padding:5px;background-color:#ffcccc\">Are you going to delete all shares from this employee?'
                              + '<div style=\"padding:10px;\">'
                              + '<input type=\"button\" value=\"Yes (delete)\" onclick=\"do_delete_pic(\\''+employee_id+'\\')\"/>&#160;&#160;'
                              + '<input type=\"button\" value=\"No (cancel)\" onclick=\"cancel_delete_pic();\"/>'
                              + '</div>'
                              + '</div>';
      }
      
      var dvpic = null;
      function view_pic(employee_id,d,e) {
         document.body.onclick = null;
         _destroy(dvpic);
         if(dvpic&&d==dvpic.d) {
            dvpic.d = null;
            dvpic = null;
            return;
         }
         
         d.dv = _dce('div');
         d.dv.setAttribute('style','width:400px;position:absolute;padding:5px;-moz-border-radius:5px;border:1px solid #777;background-color:#ffffcc;left:0px;-moz-box-shadow:1px 1px 3px #000;');
         
         d.dv.innerHTML = '<div style=\"text-align:left;padding:2px;\"  onclick=\"event.cancelBubble=true;\">'
                        + '<div style=\"padding:5px;border:1px solid #bbb;background-color:#fff;\" id=\"pic_info\">&nbsp;</div>'
                        + '</div>';
         d.dv = d.parentNode.appendChild(d.dv);
         d.dv.style.top = parseInt(oY(d.dv.parentNode)+d.dv.parentNode.offsetHeight+15)+'px';
         d.dv.style.left = parseInt(oX(d.dv.parentNode)-d.dv.offsetWidth+d.dv.parentNode.offsetWidth)+'px';
         d.dv.arrow = _dce('img');
         d.dv.arrow.setAttribute('style','position:absolute;left:0px;');
         d.dv.arrow.src = '".XOCP_SERVER_SUBDIR."/images/topmiddle.png';
         d.dv.arrow = d.dv.appendChild(d.dv.arrow);
         d.dv.arrow.style.top = '-12px';
         d.dv.arrow.style.left = parseInt(d.dv.parentNode.offsetWidth-(d.dv.parentNode.offsetWidth/2)-7)+'px';
         dvpic = d.dv;
         dvpic.d = d;
         dvpic.employee_id = employee_id;
         setTimeout('document.body.onclick = function() { document.body.onclick = null; _destroy(dvpic); };',100);
         orgjx_app_viewPIC(employee_id,function(_data) {
            var data = recjsarray(_data);
            $('pic_info').innerHTML = data[1];
            var d = dvpic.d;
            d.dv.style.left = parseInt(oX(d.dv.parentNode)-d.dv.offsetWidth+d.dv.parentNode.offsetWidth)+'px';
            d.dv.arrow.style.left = parseInt(d.dv.offsetWidth-(d.dv.parentNode.offsetWidth/2)-7)+'px';
         });
      }
      
      
      
      
      
      
      
      
      
      
      
      
      
      function save_pic_share(val,pms_objective_id,pms_actionplan_id,employee_id) {
         if(dveditshare) {
            if(!isNaN(val)&&val>0) {
               dveditshare.d.firstChild.style.color = '#33f';
            } else {
               dveditshare.d.firstChild.innerHTML = '-';
               val = 0;
               dveditshare.d.firstChild.style.color = '#333';
            }
         }
         orgjx_app_savePICShare(pms_objective_id,pms_actionplan_id,employee_id,val,function(_data) {
            var data = recjsarray(_data);
            if(dveditshare&&dveditshare.d) {
               dveditshare.d.firstChild.innerHTML = parseFloat(data[3][2]).toFixed(2);
            }
            $('tdttlshared').innerHTML = data[3][5][0];
            
            for(var i=0;i<=data[3][5][1].length;i++) {
               if(data[3][5][1][i]) {
                  var td = $('tdttlpicshare_'+data[3][5][1][i][0]);
                  if(td) {
                     td.innerHTML = data[3][5][1][i][1];
                  }
               }
            }
            
            for(var i=0;i<=data[3][5][2].length;i++) {
               if(data[3][5][2][i]) {
                  var td = $('tdsubttlpicshare_'+data[3][5][2][i][0]+'_'+data[3][5][2][i][1]);
                  if(td) {
                     td.innerHTML = data[3][5][2][i][2];
                  }
               }
            }
            
            if(data[3][5][3]==0) {
               $('tdbalancewarning').innerHTML = '&nbsp;';
            } else if(data[3][5][3]>0) {
               $('tdbalancewarning').innerHTML = '<span style=\"color:red;\">Total objective weight is more than total shared.</span>';
            } else if(data[3][5][3]<0) {
               $('tdbalancewarning').innerHTML = '<span style=\"color:red;\">Total objective weight is less than total shared.</span>';
            }
            
            if($('calc_helper')) {
               $('calc_helper').innerHTML = data[3][1];
               var d = dveditshare.d;
               d.dv.style.left = parseInt(oX(d.firstChild.parentNode)-d.dv.offsetWidth+d.firstChild.parentNode.offsetWidth)+'px';
               d.dv.arrow.style.left = parseInt(d.dv.offsetWidth-(d.firstChild.parentNode.offsetWidth/2)-7)+'px';
            }
            
            $('spapweight_'+data[0]+'_'+data[1]).innerHTML = data[2];
            
         });
      }
      
      
      
      
      function kp_pic_share(d,e) {
         var k = getkeyc(e);
         if(d.chgt) {
            d.chgt.reset();
            d.chgt = null;
         }
         var val = parseFloat(d.value);
         if(isNaN(val)) {
            val = '-';
         }
         if(val==0) {
            val = '-';
         }
         
         if(k==13) {
            //dveditshare.d.firstChild.innerHTML = parseFloat(val).toFixed(2);
            _destroy(dveditshare);
            save_pic_share(val,dveditshare.pms_objective_id,dveditshare.pms_actionplan_id,dveditshare.employee_id);
            dveditshare.d = null;
            dveditshare = null;
         } else if (k==27) {
            _destroy(dveditshare);
            dveditshare.d = null;
            dveditshare = null;
         } else {
            d.chgt = new ctimer('save_pic_share(\"'+val+'\",\"'+dveditshare.pms_objective_id+'\",\"'+dveditshare.pms_actionplan_id+'\",\"'+dveditshare.employee_id+'\");',300);
            d.chgt.start();
         }
      }
      
      
      function get_all_remaining(pms_objective_id,pms_actionplan_id,employee_id,d,e) {
         e.cancelBubble = true;
         orgjx_app_calcRemainingShare(pms_objective_id,pms_actionplan_id,employee_id,function(_data) {
            if($('calc_helper')&&$('inp_pic_share')) {
               var data = recjsarray(_data);
               $('inp_pic_share').value = data[0];
               $('new_inp_pic_share').value = data[4];
               $('calc_helper').innerHTML = data[1];
               var d = dveditshare.d;
               d.dv.style.left = parseInt(oX(d.firstChild.parentNode)-d.dv.offsetWidth+d.firstChild.parentNode.offsetWidth)+'px';
               d.dv.arrow.style.left = parseInt(d.dv.offsetWidth-(d.firstChild.parentNode.offsetWidth/2)-7)+'px';
               _dsa($('new_inp_pic_share'));
               var val = parseFloat($('inp_pic_share').value);
               save_pic_share(data[4],dveditshare.pms_objective_id,dveditshare.pms_actionplan_id,dveditshare.employee_id);
            }
         });
      }
      
      
      
      
      var dveditshare = null;
      function edit_pic_share(pms_objective_id,pms_actionplan_id,employee_id,d,e) {
         document.body.onclick = null;
         _destroy(dveditshare);
         if(dveditshare&&d==dveditshare.d) {
            dveditshare.d = null;
            dveditshare = null;
            return;
         }
         
         var wv = 0;
         if(d.firstChild.innerHTML=='-') {
         } else {
            wv = parseFloat(d.firstChild.innerHTML);
         }
         
         d.dv = _dce('div');
         d.dv.setAttribute('style','cursor:default;position:absolute;padding:5px;-moz-border-radius:5px;border:1px solid #777;background-color:#ffffcc;left:0px;-moz-box-shadow:1px 1px 3px #000;');
         
         d.dv.innerHTML = '<div style=\"text-align:right;padding:2px;\"  onclick=\"event.cancelBubble=true;\">'
                        + '<div style=\"padding:5px;border:1px solid #bbb;background-color:#fff;\" id=\"calc_helper\">&nbsp;</div>'
                        + '<div style=\"padding:5px;\">'
                        + '<input type=\"hidden\" id=\"inp_pic_share\" value=\"'+wv+'\"/>'
                        + 'Share : <input onkeyup=\"kp_pic_share(this,event);\" id=\"new_inp_pic_share\" onclick=\"event.cancelBubble=true;\" style=\"-moz-border-radius:3px;width:50px;text-align:center;\" type=\"text\" value=\"\"/>&nbsp;%'
                        + '</div>'
                        + '</div>';
         d.dv = d.appendChild(d.dv);
         d.dv.style.top = parseInt(oY(d)+d.offsetHeight)+'px';
         d.dv.style.left = parseInt(oX(d.firstChild.parentNode)-d.dv.offsetWidth+d.firstChild.parentNode.offsetWidth)+'px';
         d.dv.arrow = _dce('img');
         d.dv.arrow.setAttribute('style','position:absolute;left:0px;');
         d.dv.arrow.src = '".XOCP_SERVER_SUBDIR."/images/topmiddle.png';
         d.dv.arrow = d.dv.appendChild(d.dv.arrow);
         d.dv.arrow.style.top = '-12px';
         d.dv.arrow.style.left = parseInt(d.dv.offsetWidth-(d.firstChild.parentNode.offsetWidth/2)-7)+'px';
         _dsa($('new_inp_pic_share'));
         dveditshare = d.dv;
         dveditshare.d = d;
         dveditshare.pms_objective_id = pms_objective_id;
         dveditshare.pms_actionplan_id = pms_actionplan_id;
         dveditshare.employee_id = employee_id;
         setTimeout('document.body.onclick = function() { document.body.onclick = null; _destroy(dveditshare); };',100);
         orgjx_app_calcRemainingShare(pms_objective_id,pms_actionplan_id,employee_id,function(_data) {
            var data = recjsarray(_data);
            $('inp_pic_share').value = data[2];
            $('new_inp_pic_share').value = data[3];
            $('calc_helper').innerHTML = data[1];
            var d = dveditshare.d;
            d.dv.style.left = parseInt(oX(d.firstChild.parentNode)-d.dv.offsetWidth+d.firstChild.parentNode.offsetWidth)+'px';
            d.dv.arrow.style.left = parseInt(d.dv.offsetWidth-(d.firstChild.parentNode.offsetWidth/2)-7)+'px';
            
            d.dv.style.left = parseInt(oX(d.firstChild.parentNode)-d.dv.offsetWidth+d.firstChild.parentNode.offsetWidth)+'px';
            d.dv.arrow.style.left = parseInt(d.dv.offsetWidth-(d.firstChild.parentNode.offsetWidth/2)-7)+'px';
            
            _dsa($('new_inp_pic_share'));
         });
         
         
      }
      
      
      
      function edit_kpi_share(pms_objective_id,pms_kpi_id,pms_share_org_id,d,e) {
         document.body.onclick = null;
         _destroy(dveditshare);
         if(dveditshare&&d==dveditshare.d) {
            dveditshare.d = null;
            dveditshare = null;
            return;
         }
         d.dv = _dce('div');
         d.dv.setAttribute('style','position:absolute;padding:5px;-moz-border-radius:5px;border:1px solid #777;background-color:#ffffcc;left:0px;-moz-box-shadow:1px 1px 3px #000;min-width:200px;');
         var wv = 0;
         if(d.firstChild.innerHTML=='-') {
         
         } else {
            wv = parseFloat(d.firstChild.innerHTML);
         }
         d.dv.innerHTML = '<div style=\"text-align:right;padding:2px;\">'
                        + '<div style=\"padding:5px;border:1px solid #bbb;background-color:#fff;\" id=\"calc_helper\">&nbsp;</div>'
                        + '<div style=\"padding:5px;\">'
                        + 'Share : <input onkeyup=\"kp_kpi_share(this,event);\" id=\"inp_kpi_share\" onclick=\"event.cancelBubble=true;\" style=\"-moz-border-radius:3px;width:100px;text-align:center;\" type=\"text\" value=\"'+wv+'\"/>&nbsp;%'
                        + '</div>'
                        + '</div>';
         d.dv = d.firstChild.parentNode.appendChild(d.dv);
         //$('calc_helper').appendChild(progress_span());
         d.dv.style.top = parseInt(oY(d)+d.firstChild.offsetHeight+15)+'px';
         d.dv.style.left = parseInt(oX(d.firstChild.parentNode)-d.dv.offsetWidth+d.firstChild.parentNode.offsetWidth)+'px';
         d.dv.arrow = _dce('img');
         d.dv.arrow.setAttribute('style','position:absolute;left:0px;');
         d.dv.arrow.src = '".XOCP_SERVER_SUBDIR."/images/topmiddle.png';
         d.dv.arrow = d.dv.appendChild(d.dv.arrow);
         d.dv.arrow.style.top = '-12px';
         d.dv.arrow.style.left = parseInt(d.dv.offsetWidth-(d.firstChild.parentNode.offsetWidth/2)-7)+'px';
         _dsa($('inp_kpi_share'));
         dveditshare = d.dv;
         dveditshare.d = d;
         dveditshare.pms_objective_id = pms_objective_id;
         dveditshare.pms_kpi_id = pms_kpi_id;
         dveditshare.pms_share_org_id = pms_share_org_id;
         setTimeout('document.body.onclick = function() { document.body.onclick = null; _destroy(dveditshare); };',100);
         orgjx_app_calcRemainingShare(pms_objective_id,pms_kpi_id,pms_share_org_id,function(_data) {
            var data = recjsarray(_data);
            $('inp_kpi_share').value = data[2];
            $('calc_helper').innerHTML = data[1];
            var d = dveditshare.d;
            d.dv.style.left = parseInt(oX(d.firstChild.parentNode)-d.dv.offsetWidth+d.firstChild.parentNode.offsetWidth)+'px';
            d.dv.arrow.style.left = parseInt(d.dv.offsetWidth-(d.firstChild.parentNode.offsetWidth/2)-7)+'px';
            _dsa($('inp_kpi_share'));
         });
      }
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      var editpicshareedit = null;
      var editpicsharebox = null;
      function edit_pic_share_old(pms_objective_id,pms_actionplan_id,employee_id,d,e) {
         editpicshareedit = _dce('div');
         editpicshareedit.setAttribute('id','editpicshareedit');
         editpicshareedit = document.body.appendChild(editpicshareedit);
         editpicshareedit.sub = editpicshareedit.appendChild(_dce('div'));
         editpicshareedit.sub.setAttribute('id','innereditpicshareedit');
         editpicsharebox = new GlassBox();
         editpicsharebox.init('editpicshareedit','550px','250px','hidden','default',false,false);
         editpicsharebox.lbo(false,0.3);
         editpicsharebox.appear();
         
         orgjx_app_editPICShare(pms_objective_id,pms_actionplan_id,employee_id,function(_data) {
            $('innereditpicshareedit').innerHTML = _data;
            _dsa($('pms_share_weight'));
         });
      }
      
      
      var searchpicedit = null;
      var searchpicbox = null;
      function search_pic(d,e) {
         searchpicedit = _dce('div');
         searchpicedit.setAttribute('id','searchpicedit');
         searchpicedit = document.body.appendChild(searchpicedit);
         searchpicedit.sub = searchpicedit.appendChild(_dce('div'));
         searchpicedit.sub.setAttribute('id','innersearchpicedit');
         searchpicbox = new GlassBox();
         searchpicbox.init('searchpicedit','550px','250px','hidden','default',false,false);
         searchpicbox.lbo(false,0.3);
         searchpicbox.appear();
         
         orgjx_app_searchPIC(function(_data) {
            $('innersearchpicedit').innerHTML = _data;
            
            var qemp = _gel('qemp');
            qemp._align = 'left';
            qemp._get_param=function() {
               var qval = this.value;
               qval = trim(qval);
               if(qval.length < 2) {
                  return '';
               }
               return qval;
            };
            qemp._onselect=function(resId) {
               orgjx_app_addPIC(resId,function(_data) {
                  SetCookie('pms_actionplan_scroll_pos',window.pageYOffset);
                  location.reload(true);
               });
            };
            qemp._send_query = empajx_app_searchEmployee;
            _make_ajax(qemp);
            qemp.focus();
            
         });
      }
      
      
      
      function delete_actionplan(pms_objective_id,pms_actionplan_id,d,e) {
         orgjx_app_deleteActionPlan(pms_objective_id,pms_actionplan_id,function(_data) {
            SetCookie('pms_actionplan_scroll_pos',window.pageYOffset);
            location.reload(true);
         });
      }
      
      function save_actionplan(pms_objective_id,pms_actionplan_id,d,e) {
         var ret = _parseForm('frmactionplan');
         orgjx_app_saveActionPlan(ret,function(_data) {
            //location.reload(true);
            var data = recjsarray(_data);
            if(data[0]==1) {
               var tr = $('trobjective_'+data[1]);
               
               if(tr) {
               
                  var rowspan = tr.firstChild.getAttribute('rowspan');
                  tr.firstChild.setAttribute('rowspan',parseInt(rowspan)+1);
                  tr.firstChild.nextSibling.setAttribute('rowspan',parseInt(rowspan)+1);
                  tr.firstChild.nextSibling.nextSibling.setAttribute('rowspan',parseInt(rowspan)+1);
                  tr.firstChild.nextSibling.nextSibling.nextSibling.setAttribute('rowspan',parseInt(rowspan)+1);
                  var tdadd = $('tdaddactionplan_'+data[1]);
                  
                  if(rowspan==1) {
                     var newtr = tdadd.parentNode.parentNode.insertBefore(_dce('tr'),tdadd.parentNode.nextSibling);
                     
                     var newtdadd = newtr.appendChild(_dce('td'));
                     newtdadd.innerHTML = '[<span class=\"ylnk\" onclick=\"edit_actionplan(\\''+data[1]+'\\',\\'new\\',this,event);\">Add Action Plan</span>]';
                     var tdschedule = newtr.appendChild(_dce('td'));
                     for(var i=0;i<data[6].length;i++) {
                        var tdx = newtr.appendChild(_dce('td'));
                     }
                     var tdap = tdadd;
                     tdap.setAttribute('id','');
                     tdap.innerHTML = data[7];
                     tdap.nextSibling.setAttribute('id','tdschedule_'+data[1]+'_'+data[2]);
                     newtdadd.setAttribute('id','tdaddactionplan_'+data[1]);
                     var tdx = tdap.nextSibling;
                     for(var i=0;i<data[6].length;i++) {
                        tdx = tdx.nextSibling;
                        tdx.setAttribute('onclick','edit_pic_share(\"'+data[6][i][0]+'\",\"'+data[6][i][1]+'\",\"'+data[6][i][2]+'\",this,event);');
                        tdx.setAttribute('style','text-align:center;border-left:1px solid #bbb;');
                        tdx.setAttribute('class','tdlnk');
                        tdx.innerHTML = '<span style=\"color:#333;\">-</span>';
                     }
                  } else {
                     var newtr = tdadd.parentNode.parentNode.insertBefore(_dce('tr'),tdadd.parentNode);
                     var tdap = newtr.appendChild(_dce('td'));
                     tdap.setAttribute('style','border-right:1px solid #bbb;');
                     tdap.innerHTML = data[7];
                     var tdschedule = newtr.appendChild(_dce('td'));
                     tdschedule.setAttribute('id','tdschedule_'+data[1]+'_'+data[2]);
                     for(var i=0;i<data[6].length;i++) {
                        var tdx = newtr.appendChild(_dce('td'));
                        tdx.setAttribute('onclick','edit_pic_share(\"'+data[6][i][0]+'\",\"'+data[6][i][1]+'\",\"'+data[6][i][2]+'\",this,event);');
                        tdx.setAttribute('style','text-align:center;border-left:1px solid #bbb;');
                        tdx.setAttribute('class','tdlnk');
                        tdx.innerHTML = '<span style=\"color:#333;\">-</span>';
                     }
                  }
                  
                  $('tdschedule_'+data[1]+'_'+data[2]).innerHTML = data[4];
                  $('spapweight_'+data[1]+'_'+data[2]).innerHTML = data[5];
                  
                  
               }
               
               
            } else {
               editactionplanbox.d.innerHTML = data[3];
               $('tdschedule_'+data[1]+'_'+data[2]).innerHTML = data[4];
               $('spapweight_'+data[1]+'_'+data[2]).innerHTML = data[5];
            }
            editactionplanbox.fade();
         });
      }
      
      
      var editactionplanedit = null;
      var editactionplanbox = null;
      function edit_actionplan(pms_objective_id,pms_actionplan_id,d,e) {
         editactionplanedit = _dce('div');
         editactionplanedit.setAttribute('id','editactionplanedit');
         editactionplanedit = document.body.appendChild(editactionplanedit);
         editactionplanedit.sub = editactionplanedit.appendChild(_dce('div'));
         editactionplanedit.sub.setAttribute('id','innereditactionplanedit');
         editactionplanbox = new GlassBox();
         editactionplanbox.init('editactionplanedit','800px','270px','hidden','default',false,false);
         editactionplanbox.lbo(false,0.3);
         editactionplanbox.appear();
         editactionplanbox.d = d;
         orgjx_app_editActionPlan(pms_objective_id,pms_actionplan_id,function(_data) {
            $('innereditactionplanedit').innerHTML = _data;
            _dsa($('pms_actionplan_text'));
         });
      }
      
      function add_initiative(pms_objective_id,d,e) {
         orgjx_app_editInitiative(pms_objective_id,function(_data) {
            $('innereditsoedit').innerHTML = _data;
         });
      }
      
      function set_so_origin(pms_objective_id,d,e) {
         orgjx_app_setSOOrigin(pms_objective_id,function(_data) {
            $('parent_so').innerHTML = _data;
            $('so_editor').style.display = '';
            $('origin_chooser').style.display = 'none';
            $('vbtn').style.display = '';
         });
      }
      
      function change_so_origin(d,e) {
         $('so_editor').style.display = 'none';
         $('vbtn').style.display = 'none';
         $('origin_chooser').style.display = '';
      }
      
      function cancel_change_origin(d,e) {
         $('so_editor').style.display = '';
         $('origin_chooser').style.display = 'none';
         $('vbtn').style.display = '';
      }
      
      /*
      function kp_kpi_share(pms_objective_id,pms_kpi_id,pms_share_org_id,d,e) {
         var k = getkeyc(e);
         if(k==13) {
            save_kpi_share(pms_objective_id,pms_kpi_id,pms_share_org_id,d,e);
         }
      }
      
      function save_kpi_share(pms_objective_id,pms_kpi_id,pms_share_org_id,d,e) {
         var ret = _parseForm('frmkpi');
         orgjx_app_saveKPIShare(pms_objective_id,pms_kpi_id,pms_share_org_id,ret,function(_data) {
            SetCookie('pms_actionplan_scroll_pos',window.pageYOffset);
            location.reload(true);
         });
      }
      
      var editkpishareedit = null;
      var editkpisharebox = null;
      function edit_kpi_share(pms_objective_id,pms_kpi_id,pms_share_org_id,d,e) {
         editkpishareedit = _dce('div');
         editkpishareedit.setAttribute('id','editkpishareedit');
         editkpishareedit = document.body.appendChild(editkpishareedit);
         editkpishareedit.sub = editkpishareedit.appendChild(_dce('div'));
         editkpishareedit.sub.setAttribute('id','innereditkpishareedit');
         editkpisharebox = new GlassBox();
         editkpisharebox.init('editkpishareedit','700px','270px','hidden','default',false,false);
         editkpisharebox.lbo(false,0.3);
         editkpisharebox.appear();
         
         orgjx_app_editKPIShare(pms_objective_id,pms_kpi_id,pms_share_org_id,function(_data) {
            $('innereditkpishareedit').innerHTML = _data;
            _dsa($('pms_share_weight'));
         });
         
      }
      */
      
      
      function delete_kpi(pms_objective_id,pms_kpi_id,d,e) {
         orgjx_app_deleteKPI(pms_objective_id,pms_kpi_id,function(_data) {
            SetCookie('pms_actionplan_scroll_pos',window.pageYOffset);
            location.reload(true);
         });
      }
      
      function save_kpi(pms_objective_id,pms_kpi_id,d,e) {
         var ret = _parseForm('frmkpi');
         orgjx_app_saveKPI(ret,function(_data) {
            SetCookie('pms_actionplan_scroll_pos',window.pageYOffset);
            location.reload(true);
         });
      }
      
      var editkpiedit = null;
      var editkpibox = null;
      function edit_kpi(pms_objective_id,pms_kpi_id,d,e) {
         editkpiedit = _dce('div');
         editkpiedit.setAttribute('id','editkpiedit');
         editkpiedit = document.body.appendChild(editkpiedit);
         editkpiedit.sub = editkpiedit.appendChild(_dce('div'));
         editkpiedit.sub.setAttribute('id','innereditkpiedit');
         editkpibox = new GlassBox();
         editkpibox.init('editkpiedit','700px','370px','hidden','default',false,false);
         editkpibox.lbo(false,0.3);
         editkpibox.appear();
         
         orgjx_app_editKPI(pms_objective_id,pms_kpi_id,function(_data) {
            $('innereditkpiedit').innerHTML = _data;
            _dsa($('pms_kpi_text'));
         });
         
      }
      
      function delete_share(pms_share_org_id,d,e) {
         orgjx_app_deleteShare(pms_share_org_id,function(_data) {
            SetCookie('pms_actionplan_scroll_pos',window.pageYOffset);
            location.reload(true);
         });
      }
      
      function kpi_mouse_over(d,e) {
         return;
         var dv = d.firstChild;
         dv.style.display = '';
      }
      
      function kpi_mouse_out(d,e) {
         return;
         var dv = d.firstChild;
         dv.style.display = 'none';
      }
      
      var vshareedit = null;
      var vsharebox = null;
      function view_share(pms_share_org_id,d,e) {
         vshareedit = _dce('div');
         vshareedit.setAttribute('id','vshareedit');
         vshareedit = document.body.appendChild(vshareedit);
         vshareedit.sub = vshareedit.appendChild(_dce('div'));
         vshareedit.sub.setAttribute('id','innervshareedit');
         vsharebox = new GlassBox();
         vsharebox.init('vshareedit','600px','270px','hidden','default',false,false);
         vsharebox.lbo(false,0.3);
         vsharebox.appear();
         
         orgjx_app_viewShare(pms_share_org_id,function(_data) {
            $('innervshareedit').innerHTML = _data;
         });
         
      }
      
      function delete_so(pms_objective_id,d,e) {
         orgjx_app_deleteSO(pms_objective_id,function(_data) {
            SetCookie('pms_actionplan_scroll_pos',window.pageYOffset);
            location.reload(true);
         });
      
      }
      
      function save_so(pms_objective_id,d,e) {
         var ret = _parseForm('frmobjective');
         orgjx_app_saveSO(ret,function(_data) {
            SetCookie('pms_actionplan_scroll_pos',window.pageYOffset);
            location.reload(true);
         });
      }
      
      function chgno(d,e) {
         var k = getkeyc(e);
         if(k==9) return;
         if(d.chgt) {
            d.chgt.reset();
            d.chgt = null;
         }
         d.chgt = new ctimer('_setcode();',500);
         d.chgt.start();
      }
      
      function _setcode() {
         var no = $('pms_objective_no').value;
         var d = $('pms_perspective_id');
         var p = d.options[d.selectedIndex].value;
         var px = p.split('|');
         $('pms_obj_code').innerHTML = px[1]+no;
         _dsa($('pms_objective_no'));
      }
      
      function chgpers(d,e) {
         var p = d.options[d.selectedIndex].value;
         orgjx_app_getNo(p,function(_data) {
            var data = recjsarray(_data);
            $('pms_objective_no').value = data[0];
            var p = d.options[d.selectedIndex].value;
            var px = p.split('|');
            $('pms_obj_code').innerHTML = px[1]+data[0];
            _dsa($('pms_objective_no'));
         });
      }
      
      var editsoedit = null;
      var editsobox = null;
      function edit_so(pms_objective_id,d,e) {
         editsoedit = _dce('div');
         editsoedit.setAttribute('id','editsoedit');
         editsoedit = document.body.appendChild(editsoedit);
         editsoedit.sub = editsoedit.appendChild(_dce('div'));
         editsoedit.sub.setAttribute('id','innereditsoedit');
         editsobox = new GlassBox();
         editsobox.init('editsoedit','800px','510px','hidden','default',false,false);
         editsobox.lbo(false,0.3);
         editsobox.appear();
         
         orgjx_app_editSO(pms_objective_id,function(_data) {
            $('innereditsoedit').innerHTML = _data;
            _dsa($('pms_objective_no'));
         });
         
      }
      
      var slorgedit = null;
      var slorgbox = null;
      function select_org(d,e) {
         slorgedit = _dce('div');
         slorgedit.setAttribute('id','slorgedit');
         slorgedit = document.body.appendChild(slorgedit);
         slorgedit.sub = slorgedit.appendChild(_dce('div'));
         slorgedit.sub.setAttribute('id','innerslorgedit');
         slorgbox = new GlassBox();
         slorgbox.init('slorgedit','700px','500px','hidden','default',false,false);
         slorgbox.lbo(false,0.3);
         slorgbox.appear();
         
         orgjx_app_browseOrgs(null,function(_data) {
            $('innerslorgedit').innerHTML = _data;
         });
         
      }
      
      function do_select_org(org_id,d,e) {
         orgjx_app_selectOrg(org_id,function(_data) {
            SetCookie('pms_actionplan_scroll_pos',window.pageYOffset);
            location.reload(true);
         });
      }
      
      ////// sharing
      
      var slorgshareedit = null;
      var slorgsharebox = null;
      function add_share(d,e) {
         slorgshareedit = _dce('div');
         slorgshareedit.setAttribute('id','slorgshareedit');
         slorgshareedit = document.body.appendChild(slorgshareedit);
         slorgshareedit.sub = slorgshareedit.appendChild(_dce('div'));
         slorgshareedit.sub.setAttribute('id','innerslorgshareedit');
         slorgsharebox = new GlassBox();
         slorgsharebox.init('slorgshareedit','700px','500px','hidden','default',false,false);
         slorgsharebox.lbo(false,0.3);
         slorgsharebox.appear();
         
         orgjx_app_browseOrgShare(null,function(_data) {
            $('innerslorgshareedit').innerHTML = _data;
         });
         
      }
      
      function do_select_org_share(org_id,d,e) {
         orgjx_app_addShare(org_id,function(_data) {
            SetCookie('pms_actionplan_scroll_pos',window.pageYOffset);
            location.reload(true);
         });
      }
      
      // --></script>";
      
      return $js.$pmssel.$orgsel.$ret;
   }
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $this->pmsobjective();
            break;
         default:
            $ret = $this->pmsobjective();
            break;
      }
      return $ret;
   }
}

} // PMS_INITIATIVE_DEFINED
?>