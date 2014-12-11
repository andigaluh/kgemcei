<?php
//--------------------------------------------------------------------//
// Filename : modules/pms/pmsperspective.php                          //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-07-18                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('PMS_OBJECTIVE_DEFINED') ) {
   define('PMS_OBJECTIVE_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

include_once(XOCP_DOC_ROOT."/modules/pms/pmsxocp.php");
include_once(XOCP_DOC_ROOT."/modules/pms/class/ajax_objective.php");
include_once(XOCP_DOC_ROOT."/modules/pms/class/selectpms.php");


class _pms_Objective extends XocpBlock {
   var $catchvar = _PMS_CATCH_VAR;
   var $blockID = _PMS_OBJECTIVE_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _PMS_OBJECTIVE_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _pms_Objective($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
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
      global $allow_add_objective,$allow_add_kpi;
      include_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      include_once(XOCP_DOC_ROOT."/modules/pms/include/pms.php");
      $db=&Database::getInstance();
      $psid = $_SESSION["pms_psid"];
      $ajax = new _pms_class_ObjectiveAjax("orgjx");
      
      $_SESSION["html"]->registerLoadAction("my_scrollto");
      $_SESSION["html"]->addHeadScript("<script type='text/javascript'>//<![CDATA[\nfunction my_scrollto() { if(GetCookie('pms_objective_scroll_pos')>0) window.scrollTo(0,GetCookie('pms_objective_scroll_pos'));SetCookie('pms_objective_scroll_pos',0); }\n//]]></script>");
      
      $user_id = getUserID();
      $pmsselobj = new _pms_class_SelectSession();
      $pmssel = "<div style='padding-bottom:2px;'>".$pmsselobj->show()."</div>";
      
      
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
         return $pmssel."<div style='padding:5px;'>You don't have access privilege to setup objectives.</div>";
      }
      
      $sql = "SELECT org_class_id FROM ".XOCP_PREFIX."orgs WHERE org_id = '$org_id'";
      $result = $db->query($sql);
      list($current_org_class_id)=$db->fetchRow($result);
      
      $sub_orgs = array();
      $sql = "SELECT org_id,org_abbr,org_nm FROM ".XOCP_PREFIX."orgs"
           . " WHERE parent_id = '$org_id' AND status_cd = 'normal'";
      $result = $db->query($sql);
      $arr_sub_org = array();
      $sql = "DELETE FROM pms_org_share WHERE psid = '$psid' AND pms_org_id = '$org_id'";
      $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($sub_org_id)=$db->fetchRow($result)) {
            $sql = "REPLACE INTO pms_org_share (psid,pms_org_id,pms_share_org_id) VALUES ('$psid','$org_id','$sub_org_id')";
            $db->query($sql);
            $sub_orgs[$sub_org_id] = 1;
            //$arr_sub_org[] = $sub_org_id;
         }
      }
      
      $sql = "SELECT a.pms_share_org_id,b.org_abbr,b.org_nm,b.org_class_id"
           . " FROM pms_org_share a"
           . " LEFT JOIN ".XOCP_PREFIX."orgs b ON b.org_id = a.pms_share_org_id"
           . " LEFT JOIN ".XOCP_PREFIX."org_class c USING(org_class_id)"
           . " WHERE a.pms_org_id = '$org_id'"
           . " AND a.psid = '$psid'"
           . " AND b.org_class_id < 5"
           . " ORDER BY b.order_no";
      $result = $db->query($sql);
      $tdshare = "";
      $share_arr = array();
      $share_cnt = $db->getRowsNum($result);
      $colgroup = "";
      $has_no_sub_shared = 0;
      if($share_cnt>0) {
         $sharehead = "";
         while(list($pms_share_org_id,$pms_share_org_abbr,$pms_share_org_nm)=$db->fetchRow($result)) {
            
            
            $tdshare .= "<td style='border-bottom:1px solid #333;border-left:1px solid #bbb;text-align:center;'><span class='xlnk' onclick='view_share(\"$pms_share_org_id\",this,event);'>$pms_share_org_abbr</span></td>";
            $share_arr[] = array($pms_share_org_id,$pms_share_org_nm,$pms_share_org_abbr);
            $colgroup .= "<col width='50'/>";
         }
      } else {
         $has_no_sub_shared = 1;
         $tdshare .= ""; //"<td style='border-bottom:1px solid #333;border-left:1px solid #bbb;text-align:center;'>-</td>";
         $sharehead = "";
         $colgroup .= ""; //"<col width='50'/>";
      }
      
      $sql = "SELECT a.org_abbr,a.org_nm,b.org_class_nm FROM ".XOCP_PREFIX."orgs a LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . " WHERE org_id = '".$_SESSION["pms_org_id"]."'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($org_abbr,$org_nm,$org_class_nm)=$db->fetchRow($result);
         $orgsel = "<div style='padding:5px;border:1px solid #bbb;background-color:#ddd;'><span id='orgspan' class='xlnk' onclick='select_org(this,event);'>Level Organization : <span style='font-weight:bold;'>$org_nm $org_class_nm</span></span></div>";
      }
      
      if(!isset($_SESSION["pms_psid"])||$_SESSION["pms_psid"]==0) {
         return $pmssel;
      }
      
      $orgsel = $this->showOrg();
      
      reset($share_arr);
      
      $ret = "<table class='yylist' style='width:100%;'>"
           . "<colgroup>"
           . "<col width='30'/>"
           . "<col width='150'/>"
           . "<col width='70'/>"
           . "<col width='50'/>"
           . "<col width='150'/>"
           . "<col width='100'/>"
           . "<col width='*'/>"
           . $colgroup
           . "</colgroup>"
           . "<thead>"
           . $sharehead;
           
      $trhd = "<tr>"
           . "<td style='border-bottom:1px solid #333;text-align:left;border-right:1px solid #bbb;'>ID</td>"
           . "<td style='border-bottom:1px solid #333;border-right:1px solid #bbb;'>Strategic Objective</td>"
           . "<td style='border-bottom:1px solid #333;border-right:1px solid #bbb;text-align:center;'>Weight (%)</td>"
           . "<td style='border-bottom:1px solid #333;border-right:1px solid #bbb;'>PIC</td>"
           . "<td style='border-bottom:1px solid #333;border-right:1px solid #bbb;'>KPI</td>"
           . "<td style='border-bottom:1px solid #333;border-right:1px solid #bbb;'>Unit</td>"
           . "<td style='border-bottom:1px solid #333;border-right:0px solid #bbb;'>Target</td>"
           . $tdshare
           . "</tr>";
      
      //$ret .= $trhd;
      
      $ret .= "</thead>"
           . "<tbody>";
      
      $sql = "SELECT pms_perspective_code,pms_perspective_id,pms_perspective_name FROM pms_perspective WHERE psid = '$psid' ORDER BY pms_perspective_id";
      $result = $db->query($sql);
      $ttlw = 0;
      $ttl_pms_share = array();
      $job_nm = $job_abbr = "";
      if($db->getRowsNum($result)>0) {
         while(list($pms_perspective_code,$pms_perspective_id,$pms_perspective_name)=$db->fetchRow($result)) {                            //////////////////////////// per perspective
            $ret .= "<tr><td style='border:0px;border-bottom:3px solid #333;' colspan='".(7+($share_cnt==0?0:$share_cnt))."'>&nbsp;</td></tr>"
                  . "<tr><td colspan='".(7)."' style='font-weight:bold;border-bottom:1px solid #333;color:black;background-color:#ddf;padding:10px;'>"
                  . "$pms_perspective_name Perspective"
                  . ($allow_add_objective[$current_org_class_id]==1?"&nbsp;&nbsp;[<span class='xlnk' onclick='edit_so(\"new_${pms_perspective_id}\",this,event);' style='font-weight:normal;'/>Add Objective</span>]&nbsp;":"")
                  . "</td>"
                  . ($tdshare!=""?"<td colspan='".($share_cnt==0?0:$share_cnt)."' style='border-bottom:1px solid #333;background-color:#ddf;padding:10px;border-left:1px solid #bbb;text-align:center;'>"
                  . "<div style='min-width:50px;'>Share %</div>"
                  . "</td>":"")
                  . "</tr>";
            $ret .= "</tbody><thead>$trhd</thead><tbody>";
            $sql = "SELECT pms_objective_id,pms_objective_no,pms_objective_text,pms_kpi_text,pms_target_text,pms_measurement_unit,pms_objective_weight,"
                 . "pms_pic_job_id,pms_pic_employee_id,pms_parent_objective_id,pms_parent_kpi_id"
                 . " FROM pms_objective"
                 . " WHERE pms_org_id = '$org_id'"
                 . " AND pms_perspective_id = '$pms_perspective_id'"
                 . " AND psid = '$psid'"
                 . " ORDER BY pms_objective_no";
            $ro = $db->query($sql);
            $cnt = $db->getRowsNum($ro);
            $so = "";
            $so_no = 0;
            if($cnt>0) {
               $subttlw = 0;
               $subttl_pms_share = array();
               while(list($pms_objective_id,$pms_objective_no,$pms_objective_text,$pms_kpi_text,$pms_target_text,$pms_measurement_unit,$pms_objective_weight,
                          $pms_pic_job_id,$pms_pic_employee_id,$pms_parent_objective_idx,$pms_parent_kpi_idx)=$db->fetchRow($ro)) {                                 //////////////////// per objective
                  
                  /// check if it is a local sub
                  $sql = "SELECT pms_org_id FROM pms_objective WHERE psid = '$psid' AND pms_objective_id = '$pms_parent_objective_idx'";
                  $rp = $db->query($sql);
                  if($db->getRowsNum($rp)>0) {
                     list($pms_parent_org_idx)=$db->fetchRow($rp);
                  }
                  
                  //// check uplink
                  $brokenlink = 0;
                  $sql = "SELECT pms_objective_id,pms_kpi_id,pms_org_id FROM pms_kpi_share"
                       . " WHERE psid = '$psid'"
                       . " AND pms_sub_objective_id = '$pms_objective_id'"
                       . " AND pms_share_org_id = '$org_id'";
                  $rckup = $db->query($sql);
                  if($db->getRowsNum($rckup)>0) {
                     $uplink = 1;
                  } else {
                     $uplink = 0;
                     if($pms_parent_objective_idx>0) {
                        if($pms_parent_org_idx==$org_id) {
                           $uplink = 1; //// local sub
                        } else {
                           $brokenlink = 1;
                        }
                     }
                  }
                  
                  if(trim($pms_objective_text)=="") {
                     $pms_objective_text = _EMPTY;
                  }
                  
                  $top_level_org_id = $this->recurseParentOrg($pms_objective_id);
                  
                  /// check if it is a local sub
                  $sql = "SELECT pms_org_id FROM pms_objective WHERE psid = '$psid' AND pms_objective_id = '$pms_parent_objective_idx'";
                  $rp = $db->query($sql);
                  if($db->getRowsNum($rp)>0) {
                     list($pms_parent_org_idx)=$db->fetchRow($rp);
                  }
                  
                  /// check if it has local sub / initiatives?
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
                  if($kpi_cnt>0&&$has_local_sub==0) { /// it has kpi
                     $ret .= "<tr id='trobj_${pms_objective_id}' ".($brokenlink==1?"class='pms_so_brokenlink'":"").">"
                           . "<td id='td1obj_${pms_objective_id}' rowspan='".($kpi_cnt+1)."' style='vertical-align:middle;".($uplink==1?"color:blue;":"color:black;")."text-align:left;border-right:1px solid #333;font-weight:bold;border-bottom:1px solid #333;'>${pms_perspective_code}${pms_objective_no}</td>"
                           . "<td id='td2obj_${pms_objective_id}' rowspan='".($kpi_cnt+1)."' style='vertical-align:middle;border-right:1px solid #bbb;border-bottom:1px solid #333;'>"
                              . "<span onclick='edit_so(\"$pms_objective_id\",this,event);' class='xlnk'>".htmlentities($pms_objective_text)."</span>"
                           . "</td>"
                           . "<td id='td3obj_${pms_objective_id}' rowspan='".($kpi_cnt+1)."' style='vertical-align:middle;border-right:1px solid #bbb;text-align:center;border-bottom:1px solid #333;'>".toMoney($pms_objective_weight)."</td>"
                           . "<td id='td4obj_${pms_objective_id}' rowspan='".($kpi_cnt+1)."' style='vertical-align:middle;border-right:1px solid #bbb;border-bottom:1px solid #333;'><div style='width:50px;overflow:hidden;'><div style='width:900px;'>$so_pic_job_abbr</div></div></td>";
                     $kpi_no = 0;
                     while(list($pms_kpi_id,$pms_kpi_text,$pms_kpi_weight,$pms_kpi_target_text,$pms_kpi_measurement_unit)=$db->fetchRow($rkpi)) {
                        if($kpi_no>0) $ret .= "<tr>";
                        $ret .= "<td id='td1kpi_${pms_objective_id}_${pms_kpi_id}' style='border-right:1px solid #bbb;'><span class='xlnk' onclick='edit_kpi(\"$pms_objective_id\",\"$pms_kpi_id\",this,event);'>".htmlentities($pms_kpi_text)."</span></td>"
                              . "<td id='td2kpi_${pms_objective_id}_${pms_kpi_id}' style='border-right:1px solid #bbb;'>".htmlentities($pms_kpi_measurement_unit)."</td>"
                              . "<td id='td3kpi_${pms_objective_id}_${pms_kpi_id}' style='border-right:0px solid #bbb;'>".htmlentities($pms_kpi_target_text)."</td>";
                        
                        if($share_cnt>0) {
                           foreach($share_arr as $vshare) {
                              list($pms_share_org_id,$pms_share_org_nm,$pms_share_org_abbr)=$vshare;
                              $sql = "SELECT pms_share_weight,pms_sub_objective_id FROM pms_kpi_share"
                                   . " WHERE psid = '$psid' AND pms_objective_id = '$pms_objective_id'"
                                   . " AND pms_kpi_id = '$pms_kpi_id'"
                                   . " AND pms_org_id = '$org_id'"
                                   . " AND pms_share_org_id = '$pms_share_org_id'";
                              $rw = $db->query($sql);
                              if($db->getRowsNum($rw)>0) {
                                 list($pms_share_weight,$pms_sub_objective_id)=$db->fetchRow($rw);
                              } else {
                                 $pms_share_weight = 0;
                                 $pms_sub_objective_id = 0;
                              }
                              if($pms_share_weight>0) {
                                 $pms_share_weight_txt = toMoney($pms_share_weight);
                                 $pms_share_weight_txt = "<span style='color:#3333ff;'>".toMoney($pms_share_weight)."</span>";
                              } else {
                                 $pms_share_weight_txt = "<span style='color:#333;'>-</span>";
                              }
                              
                              
                              $sql = "SELECT pms_objective_weight"
                                   . " FROM pms_objective"
                                   . " WHERE psid = '$psid'"
                                   . " AND pms_objective_id = '$pms_sub_objective_id'"
                                   . " AND pms_parent_objective_id = '$pms_objective_id'"
                                   . " AND pms_org_id = '$pms_share_org_id'";
                              $rckdeploy = $db->query($sql);
                              if($db->getRowsNum($rckdeploy)>0) {
                                 $kpishareclass = "tdlnk";
                                 list($sub_weight)=$db->fetchRow($rckdeploy);
                              } else {
                                 if($pms_share_weight>0) {
                                    $kpishareclass = "pmstdunlink";
                                 } else {
                                    $kpishareclass = "tdlnk";
                                 }
                              }
                              
                              $ret .= "<td id='tdkpishareorg_${pms_objective_id}_${pms_kpi_id}_${pms_share_org_id}' class='$kpishareclass' style='border-left:1px solid #bbb;text-align:center;vertical-align:middle;' onclick='edit_kpi_share(\"$pms_objective_id\",\"$pms_kpi_id\",\"$pms_share_org_id\",this,event);' >$pms_share_weight_txt</td>";
                              if(!isset($subttl_pms_share[$pms_share_org_id])) $subttl_pms_share[$pms_share_org_id] = 0; /// initialize
                              $subttl_pms_share[$pms_share_org_id] = bcadd($subttl_pms_share[$pms_share_org_id],$pms_share_weight);
                           }
                        } else {
                           /// has no sub shared
                           ///
                        }
                        
                        $ret .= "</tr>";
                        $kpi_no++;
                        
                     }
                     
                     $ret .= "<tr id='traddkpi_${pms_objective_id}' ".($brokenlink==1?"class='pms_so_brokenlink'":"").">"
                           . "<td colspan='".(3)."' style='border-right:0px solid #bbb;padding:1px;border-bottom:1px solid #333;padding-left:3px;'>"
                              . ($allow_add_kpi[$current_org_class_id]==1?"[ <span class='ylnk' onclick='edit_kpi(\"$pms_objective_id\",\"new\",this,event);'>Add KPI</span> ]":"")
                           . "</td>";
                     
                     /// has sub shared
                     if($has_no_sub_shared==0) {
                        $ret .= "<td style='border-left:1px solid #bbb;text-align:center;border-bottom:1px solid #333;' colspan='".($share_cnt==0?1:$share_cnt)."'></td>";
                     }
                     
                     $ret .= "</tr>";
                     
                     
                  } else { //// it has no kpi
                     $inherited_kpi = "";
                     $sql = "SELECT pms_kpi_text,pms_kpi_id,pms_kpi_target_text,pms_kpi_measurement_unit FROM pms_kpi WHERE psid = '$psid' AND pms_objective_id = '$pms_objective_id'";
                     $rxkpi = $db->query($sql);
                     if($db->getRowsNum($rxkpi)>0) {
                        while(list($pms_kpi_textxxx,$pms_kpi_idxxx,$pms_kpi_target_textxxx,$pms_kpi_measurement_unitxxx)=$db->fetchRow($rxkpi)) {
                           $inherited_kpi .= "<div style='padding-left:20px;color:#888;'>$pms_kpi_textxxx : $pms_kpi_target_textxxx ($pms_kpi_measurement_unitxxx)</div>";
                        }
                     }
                     
                     $ret .= "<tr id='trobj_${pms_objective_id}' ".($brokenlink==1?"class='pms_so_brokenlink'":"").">"
                           . "<td id='td1obj_${pms_objective_id}' ".($has_local_sub>0?"":"rowspan='2'")." style='vertical-align:middle;text-align:left;border-right:1px solid #333;".($uplink==1?"color:blue;":"color:black;")."font-weight:bold;border-bottom:1px solid #333;'>${pms_perspective_code}${pms_objective_no}</td>"
                           . "<td id='td2obj_${pms_objective_id}' ".($has_local_sub>0?"colspan='".(6+$share_cnt)."'":"")." ".($has_local_sub>0?"":"rowspan='2'")." style='vertical-align:middle;border-right:1px solid #bbb;border-bottom:1px solid #333;'>"
                              . "<span onclick='edit_so(\"$pms_objective_id\",this,event);' class='xlnk'>".htmlentities($pms_objective_text)."</span>"
                              . ($has_local_sub>0?" ( ".toMoney($ttl_sub_weight)." % / ".toMoney($pms_objective_weight)." % )":"")
                              . ($has_local_sub>0?" [ <span class='ylnk' onclick='add_sub(\"$pms_objective_id\",this,event);'>Add Initiative</span> ]":"")
                           . $inherited_kpi
                           . "</td>";
                     
                     if($has_local_sub==0) {
                        $ret .= "<td id='td3obj_${pms_objective_id}' rowspan='2' style='vertical-align:middle;border-right:1px solid #bbb;text-align:center;border-bottom:1px solid #333;'>".toMoney($pms_objective_weight)."</td>";
                        $ret .= "<td id='td4obj_${pms_objective_id}' rowspan='2' style='vertical-align:middle;border-right:1px solid #bbb;border-bottom:1px solid #333;'><div style='width:50px;overflow:hidden;'><div style='width:900px;'>$so_pic_job_abbr</div></div></td>";
                        $ret .= "<td id='tdemptykpi_${pms_objective_id}' colspan='".(3)."' style='border-right:0px solid #bbb;border-bottom:1px solid #bbb;font-style:italic;color:#aaa;'>"._EMPTY."</td>";
                        $ret .= ($share_cnt>0?"<td id='tdemptykpishare_${pms_objective_id}' style='border-left:1px solid #bbb;text-align:center;border-bottom:1px solid #bbb;' colspan='".($share_cnt)."'>&nbsp;</td>":"");
                     }
                     $ret .= "</tr>";
                     
                     if($has_local_sub==0) {
                        $ret .= "<tr id='traddkpi_${pms_objective_id}' ".($brokenlink==1?"class='pms_so_brokenlink'":"").">"
                              . "<td colspan='".(3)."' style='border-right:0px solid #bbb;padding:1px;border-bottom:1px solid #333;padding-left:3px;'>"
                                 . ($allow_add_kpi[$current_org_class_id]==1?"[ <span class='ylnk' onclick='edit_kpi(\"$pms_objective_id\",\"new\",this,event);'>Add KPI</span> ]":"")
                              . "</td>";
                        $ret .= ($share_cnt>0?"<td style='border-left:1px solid #bbb;text-align:center;border-bottom:1px solid #333;' colspan='".($share_cnt)."'>&nbsp;</td>":"");
                        $ret .= "</tr>";
                     }
                  }
                  
                  $so_no++;
                  
                  $do_count = 0;
                  if($pms_parent_objective_idx==0) {
                     $do_count++;
                  } else {
                     $sql = "SELECT pms_org_id FROM pms_objective WHERE psid = '$psid' AND pms_parent_objective_id = '$pms_objective_id'";
                     $rpx = $db->query($sql);
                     if($db->getRowsNum($rpx)>0) {
                        list($pms_parent_org_id)=$db->fetchRow($rpx);
                        if($pms_parent_org_id==$org_id) {
                           //$do_count++;
                        } else {
                           $do_count++;
                        }
                     } else {
                        $do_count++;
                     }
                  }
                  if($has_local_sub==0&&$do_count>0) {
                     $subttlw = _bctrim(bcadd($subttlw,$pms_objective_weight));
                     $ttlw = _bctrim(bcadd($ttlw,$pms_objective_weight));
                  }
               }
               $ret .= "<tr id='trsubtotalperspective_${pms_perspective_id}'>"
                     . "<td colspan='2' style='border-right:1px solid #bbb;text-align:center;border-bottom:3px solid #333;'>Subtotal</td>"
                     . "<td id='tdsubtotalperspective_${pms_perspective_id}' style='text-align:center;background-color:#eeffff;font-weight:bold;color:black;border-right:1px solid #bbb;border-bottom:3px solid #333;'>".toMoney($subttlw)."</td>"
                     . "<td colspan='".(4)."' style='border-right:0px solid #bbb;border-bottom:3px solid #333;'></td>";
               
               if(count($share_arr)>0) {
                  foreach($share_arr as $vshare) {
                     list($pms_share_org_id,$pms_share_org_nm,$pms_share_org_abbr)=$vshare;
                     
                     if(isset($subttl_pms_share[$pms_share_org_id])&&$subttl_pms_share[$pms_share_org_id]>0) {
                        $subttlkpishare = toMoney($subttl_pms_share[$pms_share_org_id]);
                     } else {
                        $subttlkpishare = "-";
                     }
                     
                     $ret .= "<td id='tdsubttlkpishare_${pms_perspective_id}_${pms_share_org_id}' style='text-align:center;background-color:#eeffff;font-weight:bold;color:black;border-left:1px solid #bbb;border-bottom:3px solid #333;'>$subttlkpishare</td>";
                     $ttl_pms_share[$pms_share_org_id] = bcadd($ttl_pms_share[$pms_share_org_id],$subttl_pms_share[$pms_share_org_id]);
                  }
               } else {
                  //$ret .= "<td style='text-align:center;background-color:#eeffff;font-weight:bold;color:black;border-left:1px solid #bbb;border-bottom:3px solid #333;'>-</td>";
               }
               
               $ret .= "</tr>";
            
            
            } else {
               $ret .= "<tr><td colspan='".(7+($share_cnt==0?0:$share_cnt))."' style='text-align:center;font-style:italic;border-bottom:3px solid #333;'>"._EMPTY."</td></tr>";
            }
         }
      }
      
      $ret .= "<tr><td style='border:0px;border-bottom:1px solid #bbb;' colspan='".(7+($share_cnt==0?0:$share_cnt))."'>&nbsp;</td></tr>";
      $total_shared = 0;
      $retshare = "";
      if(count($share_arr)>0) {
         $tdtotal = "";
         foreach($share_arr as $vshare) {
            list($pms_share_org_id,$pms_share_org_nm,$pms_share_org_abbr)=$vshare;
            $total_shared = _bctrim(bcadd($total_shared,$ttl_pms_share[$pms_share_org_id]));
            $tdtotal .= "<td id='tdttlkpishare_${pms_share_org_id}' style='text-align:center;background-color:#bbffdd;font-weight:bold;color:black;padding:10px;border:1px solid #bbb;border-right:0;border-top:0;'>".toMoney(_bctrim($ttl_pms_share[$pms_share_org_id]))."</td>";
         }
         $retshare .= "<td id='tdttlshared' style='text-align:center;background-color:#bbffdd;font-weight:bold;color:black;padding:10px;border:1px solid #bbb;border-right:0;border-top:0;'>".toMoney($total_shared)."</td>$tdtotal";
      } else {
         
            $retshare .= "<td>&nbsp;</td>";
            
            /*
            $retshare .= "<td style='text-align:center;background-color:#eeffff;font-weight:bold;color:black;border-left:1px solid #bbb;padding:10px;'>-</td>";
            */
            
      }
      
      $ret .= "<tr>"
            . "<td colspan='2' style='padding:10px;text-align:center;font-weight:bold;border-right:1px solid #bbb;'>Total</td>"
            . "<td id='tdtotalweight' style='text-align:center;background-color:#bbffdd;font-weight:bold;color:black;padding:10px;border:1px solid #bbb;border-left:0;border-top:0;'>".toMoney($ttlw)."</td>"
            . "<td id='tdbalancewarning' colspan='".(3)."' style='padding:10px;'>";
      
      if($has_no_sub_shared==1) {
         $ret .= "&nbsp;";
      } else {
         switch(bccomp(number_format($ttlw,2,".",""),number_format($total_shared,2,".",""))) {
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
      }
      
      $ret .= "</td>";
      
      $ret .= $retshare;
      
      $ret .= "</tr>";
      
      $ret .= "</tbody>"
            . "<tfoot>"
            . "<tr><td colspan='4'>&nbsp;"
            . "</td>"
            . "<td colspan='".(3+($share_cnt==0?0:$share_cnt))."' style='text-align:right;'>"
            ///. "<input type='button' value='Recalculate Weight' onclick='recalculate_weight(this,event);'/>&#160;"
            //. "<input type='button' value='Import Objectives' onclick='import_objectives(this,event);'/>&#160;"
            . ($has_no_sub_shared==1?"":"<input type='button' value='Deploy Objectives' class='xaction' onclick='deploy_objectives(this,event);'/>")
            . "</td></tr>"
            . "</tfoot>"
            . "</table>";
      
      $ret .= "<div style='padding:100px;'>&nbsp;</div>";
      
      $js = $ajax->getJs()
          . "<script type='text/javascript' src='".XOCP_SERVER_SUBDIR."/include/calendar.js'></script>"
          . "<script type='text/javascript'>//<![CDATA[
      
      function use_sub_so_remaining(d,e) {
         var pms_objective_id = $('pms_objective_id').value;
         var pms_parent_objective_id = $('pms_parent_objective_id').value;
         orgjx_app_SOWeightRemaining(pms_objective_id,pms_parent_objective_id,function(_data) {
            var data = recjsarray(_data);
            if($('sub_so_remaining')) {
               $('weight').value = data[0];
               $('sub_so_remaining').innerHTML = data[1];
            }
            
         });
      }
      
      function kp_so_weight(d,e) {
         var k = getkeyc(e);
         if(k==9) return;
         if(d.chgt) {
            d.chgt.reset();
            d.chgt = null;
         }
         d.chgt = new ctimer('so_setweight();',200);
         d.chgt.start();
      }
      
      function so_setweight() {
         var pms_objective_id = $('pms_objective_id').value;
         var pms_parent_objective_id = $('pms_parent_objective_id').value;
         var w = $('weight').value;
         orgjx_app_SOWeight(pms_objective_id,pms_parent_objective_id,w,function(_data) {
            var data = recjsarray(_data);
            if($('sub_so_remaining')&&data[0]=='sub') {
               $('sub_so_remaining').innerHTML = data[1];
            }
         });
      }
      
      function add_sub(pms_objective_id,d,e) {
         ajax_feedback = _caf;
         if($('innereditsoedit')) {
            $('innereditsoedit').innerHTML = '';
         } else {
            if(editsoedit) {
               _destroy(editsoedit.sub);
            }
            editsoedit = _dce('div');
            editsoedit.setAttribute('id','editsoedit');
            editsoedit = document.body.appendChild(editsoedit);
            editsoedit.sub = editsoedit.appendChild(_dce('div'));
            editsoedit.sub.setAttribute('id','innereditsoedit');
            editsobox = new GlassBox();
            editsobox.init('editsoedit','800px','430px','hidden','default',false,false);
            editsobox.lbo(false,0.3);
            editsobox.appear();
         }
         orgjx_app_addSub(pms_objective_id,function(_data) {
            $('innereditsoedit').innerHTML = _data;
            editsobox.appear();
         });
      }
      
      function do_import_objectives() {
         ajax_feedback = _caf;
         orgjx_app_importObjectives(function(_data) {
            SetCookie('pms_objective_scroll_pos',window.pageYOffset);
            location.reload();
         });
      }
      
      var confirmimport = null;
      var confirmimportbox = null;
      function import_objectives(d,e) {
         confirmimport = _dce('div');
         confirmimport.setAttribute('id','confirmimport');
         confirmimport = document.body.appendChild(confirmimport);
         confirmimport.sub = confirmimport.appendChild(_dce('div'));
         confirmimport.sub.setAttribute('id','innerconfirmimport');
         confirmimportbox = new GlassBox();
         $('innerconfirmimport').innerHTML = '<div style=\"height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;\">Import Objectives Confirmation</div>'
                                           + '<div id=\"confirmmsg\" style=\"padding:20px;text-align:center;\">Are you going to import objectives from upper level organization?</div>'
                                           + '<div id=\"confirmmsg\" style=\"padding:12px;text-align:center;color:red;\">Warning : KPI sharing to lower level will be reset.</div>'
                                           + '<div id=\"confirmbtn\" style=\"background-color:#eee;padding:10px;text-align:center;padding-bottom:30px;\">'
                                             + '<input type=\"button\" value=\"Yes (Submit)\" onclick=\"do_import_objectives();\"/>&nbsp;&nbsp;'
                                             + '<input type=\"button\" value=\"No\" onclick=\"confirmimportbox.fade();\"/>'
                                           + '</div>';
         
         
         confirmimportbox = new GlassBox();
         confirmimportbox.init('confirmimport','500px','205px','hidden','default',false,false);
         confirmimportbox.lbo(false,0.3);
         confirmimportbox.appear();
      }
      
      function do_recalculate() {
         ajax_feedback = _caf;
         orgjx_app_recalculate(function(_data) {
            SetCookie('pms_objective_scroll_pos',window.pageYOffset);
            location.reload();
         });
      }
      
      var confirmrecalculate = null;
      var confirmrecalculatebox = null;
      function recalculate_weight(d,e) {
         confirmrecalculate = _dce('div');
         confirmrecalculate.setAttribute('id','confirmrecalculate');
         confirmrecalculate = document.body.appendChild(confirmrecalculate);
         confirmrecalculate.sub = confirmrecalculate.appendChild(_dce('div'));
         confirmrecalculate.sub.setAttribute('id','innerconfirmrecalculate');
         confirmrecalculatebox = new GlassBox();
         $('innerconfirmrecalculate').innerHTML = '<div style=\"height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;\">Weight Recalculation Confirmation</div>'
                                           + '<div id=\"confirmmsg\" style=\"padding:20px;text-align:center;\">Are you going to recalculate inherited objective&quot;s weight?</div>'
                                           + '<div id=\"confirmmsg\" style=\"padding:12px;padding-top:0px;text-align:center;color:red;\">Warning : This action will affect only inherited objective&quot;s weight proportionally.</div>'
                                           + '<div id=\"confirmbtn\" style=\"background-color:#eee;padding:10px;text-align:center;padding-bottom:30px;\">'
                                             + '<input type=\"button\" value=\"Yes (Recalculate)\" onclick=\"do_recalculate();\"/>&nbsp;&nbsp;'
                                             + '<input type=\"button\" value=\"No\" onclick=\"confirmrecalculatebox.fade();\"/>'
                                           + '</div>';
         
         confirmrecalculatebox = new GlassBox();
         confirmrecalculatebox.init('confirmrecalculate','500px','205px','hidden','default',false,false);
         confirmrecalculatebox.lbo(false,0.3);
         confirmrecalculatebox.appear();
      }
      
      
      var confirmdeploy = null;
      var confirmdeploybox = null;
      function deploy_objectives(d,e) {
         confirmdeploy = _dce('div');
         confirmdeploy.setAttribute('id','confirmdeploy');
         confirmdeploy = document.body.appendChild(confirmdeploy);
         confirmdeploy.sub = confirmdeploy.appendChild(_dce('div'));
         confirmdeploy.sub.setAttribute('id','innerconfirmdeploy');
         confirmdeploybox = new GlassBox();
         $('innerconfirmdeploy').innerHTML = '<div style=\"height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;\">Deploy Objectives Confirmation</div>'
                                           + '<div id=\"confirmdeploymsg\" style=\"padding:10px;text-align:center;\"></div>';
         
         confirmdeploybox = new GlassBox();
         confirmdeploybox.init('confirmdeploy','500px','175px','hidden','default',false,false);
         confirmdeploybox.lbo(false,0.3);
         confirmdeploybox.appear();
         $('confirmdeploymsg').appendChild(progress_span());
         
         orgjx_app_checkDeployObjectives(function(_data) {
            $('confirmdeploymsg').innerHTML = _data;
         });
         
      }
      
      
      
      function do_deploy_objectives(d,e) {
         $('confirmdeploymsg').innerHTML = '';
         $('confirmdeploymsg').appendChild(progress_span(' ... deploying objectives'));
         orgjx_app_deployObjectives('".$_SESSION["pms_org_id"]."',function(_data) {
            confirmdeploybox.fade();
            location.reload(true);
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
      
      function deploy_single_kpi(pms_objective_id,pms_kpi_id,pms_share_org_id,d,e) {
         e.cancelBubble = true;
         ajax_feedback = _caf;
         orgjx_app_deploySingleKPI(pms_objective_id,pms_kpi_id,pms_share_org_id,function(_data) {
            if($('calc_helper')&&$('inp_kpi_share')) {
               var data = recjsarray(_data);
               $('inp_kpi_share').value = data[0];
               $('new_inp_kpi_share').value = data[4];
               $('calc_helper').innerHTML = data[1];
               var d = dveditshare.d;
               d.dv.style.left = parseInt(oX(d.firstChild.parentNode)-d.dv.offsetWidth+d.firstChild.parentNode.offsetWidth)+'px';
               d.dv.arrow.style.left = parseInt(d.dv.offsetWidth-(d.firstChild.parentNode.offsetWidth/2)-7)+'px';
               _dsa($('new_inp_kpi_share'));
               
               if(data[6]==1) {
                  dveditshare.d.className = 'tdlnk';
               } else {
                  if(data[2]>0) {
                     dveditshare.d.className = 'pmstdunlink';
                  } else {
                     dveditshare.d.className = 'tdlnk';
                  }
               }
               
               
            }
         });
      }
      
      function updating_agent(data) {
         
         for(var i=0;i<data[5].length;i++) {
            if(data[5][i]) {
               if($('tdsubtotalperspective_'+data[5][i][0])) {
                  $('tdsubtotalperspective_'+data[5][i][0]).innerHTML = data[5][i][1];
               }
            }
         }
         
         $('tdtotalweight').innerHTML = data[4];
            
         $('tdttlshared').innerHTML = data[0];
         
         for(var i=0;i<=data[1].length;i++) {
            if(data[1][i]) {
               var td = $('tdttlkpishare_'+data[1][i][0]);
               if(td) {
                  td.innerHTML = data[1][i][1];
               }
            }
         }
         
         for(var i=0;i<=data[2].length;i++) {
            if(data[2][i]) {
               var td = $('tdsubttlkpishare_'+data[2][i][0]+'_'+data[2][i][1]);
               if(td) {
                  td.innerHTML = data[2][i][2];
               }
            }
         }
         
         if(data[3]==0) {
            $('tdbalancewarning').innerHTML = '&nbsp;';
         } else if(data[3]>0) {
            $('tdbalancewarning').innerHTML = '<span style=\"color:red;\">Total objective weight is more than total shared.</span>';
         } else if(data[3]<0) {
            $('tdbalancewarning').innerHTML = '<span style=\"color:red;\">Total objective weight is less than total shared.</span>';
         }
            
      
      }
      
      function save_kpi_share(val,pms_objective_id,pms_kpi_id,pms_share_org_id) {
         if(dveditshare) {
            if(!isNaN(val)&&val>0) {
               dveditshare.d.firstChild.style.color = '#33f';
            } else {
               dveditshare.d.firstChild.innerHTML = '-';
               val = 0;
               dveditshare.d.firstChild.style.color = '#333';
            }
         }
         
         orgjx_app_saveKPIShare(pms_objective_id,pms_kpi_id,pms_share_org_id,urlencode('pms_share_weight^^'+val),function(_data) {
            var data = recjsarray(_data);
            if(dveditshare&&dveditshare.d) {
               var val = parseFloat(data[2]).toFixed(2);
               if(val>0) {
                  dveditshare.d.firstChild.innerHTML = val;
               } else {
                  dveditshare.d.firstChild.innerHTML = '-';
               }
               
               if(data[6]==1) {
                  dveditshare.d.className = 'tdlnk';
               } else {
                  if(data[2]>0) {
                     dveditshare.d.className = 'pmstdunlink';
                  } else {
                     dveditshare.d.className = 'tdlnk';
                  }
               }
            }
            
            
            updating_agent(data[5]);
            
            if($('calc_helper')) {
               $('calc_helper').innerHTML = data[1];
               var d = dveditshare.d;
               d.dv.style.left = parseInt(oX(d.firstChild.parentNode)-d.dv.offsetWidth+d.firstChild.parentNode.offsetWidth)+'px';
               d.dv.arrow.style.left = parseInt(d.dv.offsetWidth-(d.firstChild.parentNode.offsetWidth/2)-7)+'px';
            }
         });
      }
      
      function kp_kpi_share(d,e) {
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
            save_kpi_share(val,dveditshare.pms_objective_id,dveditshare.pms_kpi_id,dveditshare.pms_share_org_id);
            dveditshare.d = null;
            dveditshare = null;
         } else if (k==27) {
            _destroy(dveditshare);
            dveditshare.d = null;
            dveditshare = null;
         } else {
            d.chgt = new ctimer('save_kpi_share(\"'+val+'\",\"'+dveditshare.pms_objective_id+'\",\"'+dveditshare.pms_kpi_id+'\",\"'+dveditshare.pms_share_org_id+'\");',100);
            d.chgt.start();
         }
      }
      
      function get_all_remaining(pms_objective_id,pms_kpi_id,pms_share_org_id,d,e) {
         e.cancelBubble = true;
         orgjx_app_calcRemainingShare(pms_objective_id,pms_kpi_id,pms_share_org_id,function(_data) {
            if($('calc_helper')&&$('inp_kpi_share')) {
               var data = recjsarray(_data);
               $('inp_kpi_share').value = data[0];
               $('new_inp_kpi_share').value = data[4];
               $('calc_helper').innerHTML = data[1];
               var d = dveditshare.d;
               d.dv.style.left = parseInt(oX(d.firstChild.parentNode)-d.dv.offsetWidth+d.firstChild.parentNode.offsetWidth)+'px';
               d.dv.arrow.style.left = parseInt(d.dv.offsetWidth-(d.firstChild.parentNode.offsetWidth/2)-7)+'px';
               _dsa($('new_inp_kpi_share'));
               var val = parseFloat($('inp_kpi_share').value);
               save_kpi_share(data[4],dveditshare.pms_objective_id,dveditshare.pms_kpi_id,dveditshare.pms_share_org_id);
            }
         });
      }
      
      var dveditshare = null;
      function edit_kpi_share(pms_objective_id,pms_kpi_id,pms_share_org_id,d,e) {
         document.body.onclick = null;
         _destroy(dveditshare);
         if(dveditshare&&d==dveditshare.d) {
            dveditshare.d = null;
            dveditshare = null;
            return;
         }
         d.dv = _dce('div');
         d.dv.setAttribute('style','position:absolute;padding:5px;-moz-border-radius:5px;border:1px solid #777;background-color:#ffffcc;left:0px;-moz-box-shadow:2px 2px 5px #000;min-width:200px;');
         var wv = 0;
         if(d.firstChild.innerHTML=='-') {
         
         } else {
            wv = parseFloat(d.firstChild.innerHTML);
         }
         
         d.dv.innerHTML = '<div style=\"text-align:right;padding:2px;\">'
                        + '<div style=\"padding:5px;border:1px solid #bbb;background-color:#fff;\" id=\"calc_helper\">&nbsp;</div>'
                        + '<div style=\"padding:5px;\">'
                        + '<input type=\"hidden\" id=\"inp_kpi_share\" value=\"'+wv+'\"/>'
                        + 'Share : <input onkeyup=\"kp_kpi_share(this,event);\" id=\"new_inp_kpi_share\" onclick=\"event.cancelBubble=true;\" style=\"-moz-border-radius:3px;width:50px;text-align:center;\" type=\"text\" value=\"\"/>&nbsp;%'
                        + '</div>'
                        + '</div>';
         d.dv = d.firstChild.parentNode.appendChild(d.dv);
         //$('calc_helper').appendChild(progress_span());
         d.dv.style.top = parseInt(oY(d)+d.offsetHeight)+'px';
         d.dv.style.left = parseInt(oX(d.firstChild.parentNode)-d.dv.offsetWidth+d.firstChild.parentNode.offsetWidth)+'px';
         d.dv.arrow = _dce('img');
         d.dv.arrow.setAttribute('style','position:absolute;left:0px;');
         d.dv.arrow.src = '".XOCP_SERVER_SUBDIR."/images/topmiddle.png';
         d.dv.arrow = d.dv.appendChild(d.dv.arrow);
         d.dv.arrow.style.top = '-12px';
         d.dv.arrow.style.left = parseInt(d.dv.offsetWidth-(d.firstChild.parentNode.offsetWidth/2)-7)+'px';
         _dsa($('new_inp_kpi_share'));
         dveditshare = d.dv;
         dveditshare.d = d;
         dveditshare.pms_objective_id = pms_objective_id;
         dveditshare.pms_kpi_id = pms_kpi_id;
         dveditshare.pms_share_org_id = pms_share_org_id;
         setTimeout('document.body.onclick = function() { document.body.onclick = null; _destroy(dveditshare); };',100);
         orgjx_app_calcRemainingShare(pms_objective_id,pms_kpi_id,pms_share_org_id,function(_data) {
            var data = recjsarray(_data);
            $('inp_kpi_share').value = data[2];
            $('new_inp_kpi_share').value = data[3];
            $('calc_helper').innerHTML = data[1];
            var d = dveditshare.d;
            d.dv.style.left = parseInt(oX(d.firstChild.parentNode)-d.dv.offsetWidth+d.firstChild.parentNode.offsetWidth)+'px';
            d.dv.arrow.style.left = parseInt(d.dv.offsetWidth-(d.firstChild.parentNode.offsetWidth/2)-7)+'px';
            _dsa($('new_inp_kpi_share'));
         });
      }
      
      var editkpishareedit = null;
      var editkpisharebox = null;
      function edit_kpi_share_old(pms_objective_id,pms_kpi_id,pms_share_org_id,d,e) {
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
      
      
      function delete_kpi(pms_objective_id,pms_kpi_id,d,e) {
         ajax_feedback = _caf;
         orgjx_app_deleteKPI(pms_objective_id,pms_kpi_id,function(_data) {
            var data = recjsarray(_data);
            var pms_objective_id = data[1];
            var pms_kpi_id = data[2];
            var kpi_cnt = data[3];
            var share_cnt = data[4];
            var share_orgs = data[5];
            
            var td1kpi = $('td1kpi_'+pms_objective_id+'_'+pms_kpi_id);
            var td2kpi = $('td2kpi_'+pms_objective_id+'_'+pms_kpi_id);
            var td3kpi = $('td3kpi_'+pms_objective_id+'_'+pms_kpi_id);
            var trobj = td1kpi.parentNode;
            var nx = td3kpi.nextSibling;
            _destroy(td1kpi);
            _destroy(td2kpi);
            _destroy(td3kpi);
            
            if(nx) {
               for(var i=0;i<100;i++) {
                  var nxnew = nx.nextSibling;
                  if(nx) {
                     _destroy(nx);
                  }
                  if(!nxnew) break;
                  nx = nxnew;
               }
            }
            if(kpi_cnt==0) {
               var tdemptykpi = _dce('td');
               tdemptykpi.setAttribute('id','tdemptykpi_'+pms_objective_id);
               tdemptykpi.setAttribute('colspan','3');
               tdemptykpi.setAttribute('style','border-right:0px solid #bbb;border-bottom:1px solid #bbb;font-style:italic;color:#aaa;');
               tdemptykpi.innerHTML = '"._EMPTY."';
               trobj.appendChild(tdemptykpi);
               if(share_cnt>0) {
                  var tdemptykpishare = _dce('td');
                  tdemptykpishare.setAttribute('id','tdemptykpishare_'+pms_objective_id);
                  tdemptykpishare.setAttribute('colspan',share_cnt);
                  tdemptykpishare.setAttribute('style','border-left:1px solid #bbb;text-align:center;border-bottom:1px solid #bbb;');
                  trobj.appendChild(tdemptykpishare);
               }
               for(var i=1;i<=4;i++) {
                  var tdx = $('td'+i+'obj_'+pms_objective_id);
                  tdx.setAttribute('rowspan','2');
               }
            } else {
               if(trobj.id=='trobj_'+pms_objective_id) { /// first line kpi
                  
                  var nexttr = trobj.nextSibling;
                  var arrid = nexttr.firstChild.id.split('_');
                  var xpms_kpi_id = arrid[2];
                  trx = trobj;
                  
                  trx.td1 = _dce('td');
                  trx.td1.setAttribute('id','td1kpi_'+pms_objective_id+'_'+xpms_kpi_id);
                  trx.td1.setAttribute('style','border-right:1px solid #bbb;');
                  trx.td1.innerHTML = nexttr.firstChild.innerHTML;
                  trx.td2 = _dce('td');
                  
                  trx.td2.setAttribute('id','td2kpi_'+pms_objective_id+'_'+xpms_kpi_id);
                  trx.td2.setAttribute('style','border-right:1px solid #bbb;');
                  trx.td2.innerHTML = nexttr.firstChild.nextSibling.innerHTML;
                  trx.td3 = _dce('td');
                  trx.td3.setAttribute('id','td3kpi_'+pms_objective_id+'_'+xpms_kpi_id);
                  trx.td3.setAttribute('style','border-right:0px solid #bbb;');
                  trx.td3.innerHTML = nexttr.firstChild.nextSibling.nextSibling.innerHTML;
                  trx.appendChild(trx.td1);
                  trx.appendChild(trx.td2);
                  trx.appendChild(trx.td3);
                  var nx = nexttr.firstChild.nextSibling.nextSibling;
                  
                  for(var n=0;n<share_cnt;n++) {
                     nx = nx.nextSibling;
                     var tdkpi = _dce('td');
                     tdkpi.setAttribute('style','border-left:1px solid #bbb;text-align:center;');
                     tdkpi.setAttribute('class','tdlnk');
                     tdkpi.setAttribute('onclick','edit_kpi_share(\\''+pms_objective_id+'\\',\\''+xpms_kpi_id+'\\',\\''+share_orgs[n]+'\\',this,event);');
                     tdkpi.setAttribute('id','tdkpishareorg_'+pms_objective_id+'_'+xpms_kpi_id+'_'+share_orgs[n]);
                     tdkpi.innerHTML = nx.innerHTML;
                     trx.appendChild(tdkpi);
                  }
                  _destroy(nexttr);
               } else {
                  _destroy(trobj);
               }
               
               for(var i=1;i<=4;i++) {
                  var tdx = $('td'+i+'obj_'+pms_objective_id);
                  tdx.setAttribute('rowspan',kpi_cnt+1);
               }
            }
            editkpibox.fade();
         });
      }
      
      function save_kpi(pms_objective_id,pms_kpi_id,d,e) {
         ajax_feedback = _caf;
         var ret = _parseForm('frmkpi');
         orgjx_app_saveKPI(ret,function(_data) {
            var data = recjsarray(_data);
            var pms_objective_id = data[1];
            var pms_kpi_id = data[2];
            var kpi_cnt = data[3];
            var share_cnt = data[4];
            var share_orgs = data[5];
            if(data[0]==1) { /// insert new
               for(var i=1;i<=4;i++) {
                  var tdx = $('td'+i+'obj_'+pms_objective_id);
                  tdx.setAttribute('rowspan',kpi_cnt+1);
                  
               }
               if(kpi_cnt==1) { //// start from empty
                  _destroy($('tdemptykpi_'+pms_objective_id));
                  _destroy($('tdemptykpishare_'+pms_objective_id));
                  var trx = $('trobj_'+pms_objective_id);
                  trx.td1 = _dce('td');
                  trx.td1.setAttribute('id','td1kpi_'+pms_objective_id+'_'+pms_kpi_id);
                  trx.td1.setAttribute('style','border-right:1px solid #bbb;');
                  trx.td1.innerHTML = '<span class=\"xlnk\" onclick=\"edit_kpi(\\''+pms_objective_id+'\\',\\''+pms_kpi_id+'\\',this,event)\">'+data[6]+'</span>';
                  trx.td2 = _dce('td');
                  trx.td2.setAttribute('id','td2kpi_'+pms_objective_id+'_'+pms_kpi_id);
                  trx.td2.setAttribute('style','border-right:1px solid #bbb;');
                  trx.td2.innerHTML = data[8];
                  trx.td3 = _dce('td');
                  trx.td3.setAttribute('id','td3kpi_'+pms_objective_id+'_'+pms_kpi_id);
                  trx.td3.setAttribute('style','border-right:0px solid #bbb;');
                  trx.td3.innerHTML = data[7];
                  trx.appendChild(trx.td1);
                  trx.appendChild(trx.td2);
                  trx.appendChild(trx.td3);
                  for(var n=0;n<share_cnt;n++) {
                     var tdkpi = _dce('td');
                     tdkpi.setAttribute('style','border-left:1px solid #bbb;text-align:center;vertical-align:middle;');
                     tdkpi.setAttribute('class','tdlnk');
                     tdkpi.setAttribute('onclick','edit_kpi_share(\\''+pms_objective_id+'\\',\\''+pms_kpi_id+'\\',\\''+share_orgs[n]+'\\',this,event);');
                     tdkpi.setAttribute('id','tdkpishareorg_'+pms_objective_id+'_'+pms_kpi_id+'_'+share_orgs[n]);
                     tdkpi.innerHTML = '<span style=\"color:#333;\">-</span>';
                     trx.appendChild(tdkpi);
                  }
               } else {
                  var traddkpi = $('traddkpi_'+pms_objective_id);
                  var trx = _dce('tr');
                  trx.td1 = _dce('td');
                  trx.td1.setAttribute('id','td1kpi_'+pms_objective_id+'_'+pms_kpi_id);
                  trx.td1.setAttribute('style','border-right:1px solid #bbb;');
                  trx.td1.innerHTML = '<span class=\"xlnk\" onclick=\"edit_kpi(\\''+pms_objective_id+'\\',\\''+pms_kpi_id+'\\',this,event)\">'+data[6]+'</span>';
                  trx.td2 = _dce('td');
                  trx.td2.setAttribute('id','td2kpi_'+pms_objective_id+'_'+pms_kpi_id);
                  trx.td2.setAttribute('style','border-right:1px solid #bbb;');
                  trx.td2.innerHTML = data[8];
                  trx.td3 = _dce('td');
                  trx.td3.setAttribute('id','td3kpi_'+pms_objective_id+'_'+pms_kpi_id);
                  trx.td3.setAttribute('style','border-right:0px solid #bbb;');
                  trx.td3.innerHTML = data[7];
                  trx.appendChild(trx.td1);
                  trx.appendChild(trx.td2);
                  trx.appendChild(trx.td3);
                  for(var n=0;n<share_cnt;n++) {
                     var tdkpi = _dce('td');
                     tdkpi.setAttribute('style','border-left:1px solid #bbb;text-align:center;vertical-align:middle;');
                     tdkpi.setAttribute('class','tdlnk');
                     tdkpi.setAttribute('onclick','edit_kpi_share(\\''+pms_objective_id+'\\',\\''+pms_kpi_id+'\\',\\''+share_orgs[n]+'\\',this,event);');
                     tdkpi.setAttribute('id','tdkpishareorg_'+pms_objective_id+'_'+pms_kpi_id+'_'+share_orgs[n]);
                     tdkpi.innerHTML = '<span style=\"color:#333;\">-</span>';
                     trx.appendChild(tdkpi);
                  }
                  trx = traddkpi.parentNode.insertBefore(trx,traddkpi);
               }
            } else { //// update KPI
               var td1kpi = $('td1kpi_'+pms_objective_id+'_'+pms_kpi_id);
               td1kpi.firstChild.innerHTML = data[6];
               var td2kpi = $('td2kpi_'+pms_objective_id+'_'+pms_kpi_id);
               td2kpi.innerHTML = data[8];
               var td3kpi = $('td3kpi_'+pms_objective_id+'_'+pms_kpi_id);
               td3kpi.innerHTML = data[7];
            }
            editkpibox.fade();
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
            SetCookie('pms_objective_scroll_pos',window.pageYOffset);
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
         vsharebox.init('vshareedit','600px','250px','hidden','default',false,false);
         vsharebox.lbo(false,0.3);
         vsharebox.appear();
         
         orgjx_app_viewShare(pms_share_org_id,function(_data) {
            $('innervshareedit').innerHTML = _data;
         });
         
      }
      
      function do_delete_so(pms_objective_id,d,e) {
         orgjx_app_deleteSO(pms_objective_id,function(_data) {
            var data = recjsarray(_data);
            
            for(var i=0;i<data[1].length;i++) {
               var xid = data[1][i];
               var tr = $('trobj_'+xid);
               if(tr) {
                  while(1) {
                     var nx = tr.nextSibling;
                     if(tr.id=='traddkpi_'+xid) {
                        _destroy(tr);
                        break;
                     } else if(tr.id=='') {
                        _destroy(tr);
                     } else if(tr.id!=''&&tr.id=='trobj_'+xid) {
                        _destroy(tr);
                     } else {
                        break;
                     }
                     if(!nx) break;
                     tr = nx;
                  }
               }
            }
            
            editsobox.fade();
         });
      }
      
      function cancel_delete_so() {
         $('innereditsoedit').innerHTML = $('innereditsoedit').oldHTML;
      }
      
      function delete_so(pms_objective_id,d,e) {
         $('innereditsoedit').oldHTML = $('innereditsoedit').innerHTML;
         if($('innereditsoedit')) {
            $('innereditsoedit').innerHTML = '<div style=\"height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;\">Delete Objective Confirmation</div>'
                                           + '<div id=\"confirmmsg\" style=\"padding:20px;text-align:center;\">Are you going to delete this objective?</div>'
                                           + '<div id=\"confirmmsg\" style=\"padding:12px;padding-top:5px;text-align:center;color:red;height:310px;\">Warning : All sub objectives, KPI, JAM and action plans will also be deleted.</div>'
                                           + '<div id=\"confirmbtn\" style=\"background-color:#eee;padding:10px;text-align:center;padding-bottom:30px;\">'
                                             + '<input type=\"button\" value=\"Yes (delete)\" onclick=\"do_delete_so(\\''+pms_objective_id+'\\');\"/>&nbsp;&nbsp;'
                                             + '<input type=\"button\" value=\"No\" onclick=\"cancel_delete_so();\"/>'
                                           + '</div>';
         }
      }
      
      
      
      function save_so(pms_objective_id,d,e) {
         var ret = _parseForm('frmobjective');
         orgjx_app_saveSO(ret,function(_data) {
            var data = recjsarray(_data);
            var pms_objective_id = data[1];
            if(data[0]==1) { /// insert new objective
               if(data[5]==1) { /// is local initiative
                  
                  var prevtdobjno = $('td1obj_'+data[6]);
                  var prevtraddkpi = $('traddkpi_'+data[6]);
                  
                  var newtr = _dce('tr');
                  newtr.setAttribute('id','trobj_'+pms_objective_id);
                  newtr.td1 = newtr.appendChild(_dce('td'));
                  newtr.td1.setAttribute('id','td1obj_'+pms_objective_id);
                  newtr.td1.setAttribute('rowspan','2');
                  newtr.td1.setAttribute('style','vertical-align:middle;text-align:left;border-right:1px solid #333;color:blue;font-weight:bold;border-bottom:1px solid #333;');
                  newtr.td1.innerHTML = trim(' '+data[8]+data[7]);
                  newtr.td2 = newtr.appendChild(_dce('td'));
                  newtr.td2.setAttribute('id','td2obj_'+pms_objective_id);
                  newtr.td2.setAttribute('rowspan','2');
                  newtr.td2.setAttribute('style','vertical-align:middle;border-right:1px solid #bbb;border-bottom:1px solid #333;');
                  newtr.td2.innerHTML = '<span onclick=\"edit_so(\\''+pms_objective_id+'\\',this,event);\" class=\"xlnk\">'+data[2]+'</span>';
                  newtr.td3 = newtr.appendChild(_dce('td'));
                  newtr.td3.setAttribute('id','td3obj_'+pms_objective_id);
                  newtr.td3.setAttribute('rowspan','2');
                  newtr.td3.setAttribute('style','vertical-align:middle;border-right:1px solid #bbb;text-align:center;border-bottom:1px solid #333;');
                  newtr.td3.innerHTML = data[3];
                  newtr.td4 = newtr.appendChild(_dce('td'));
                  newtr.td4.setAttribute('id','td4obj_'+pms_objective_id);
                  newtr.td4.setAttribute('rowspan','2');
                  newtr.td4.setAttribute('style','vertical-align:middle;border-right:1px solid #bbb;border-bottom:1px solid #333;');
                  newtr.td4.innerHTML = '<div style=\"width:50px;overflow:hidden;\"><div style=\"width:900px;\">'+data[4]+'</div></div>';
                  newtr.tdemptykpi = newtr.appendChild(_dce('td'));
                  newtr.tdemptykpi.setAttribute('id','tdemptykpi_'+pms_objective_id);
                  newtr.tdemptykpi.setAttribute('colspan','3');
                  newtr.tdemptykpi.setAttribute('style','border-right:0px solid #bbb;border-bottom:1px solid #bbb;font-style:italic;color:#aaa;');
                  newtr.tdemptykpi.innerHTML = '"._EMPTY."';
                  if(data[9]>0) {
                     newtr.tdemptykpishare = newtr.appendChild(_dce('td'));
                     newtr.tdemptykpishare.setAttribute('id','tdemptykpishare_'+pms_objective_id);
                     newtr.tdemptykpishare.setAttribute('colspan',data[9]);
                     newtr.tdemptykpishare.setAttribute('style','border-left:1px solid #bbb;text-align:center;border-bottom:1px solid #bbb;');
                     newtr.tdemptykpishare.innerHTML = '&nbsp;';
                  }
                  newtr2 = _dce('tr');
                  newtr2.setAttribute('id','traddkpi_'+pms_objective_id);
                  newtr2.td1 = newtr2.appendChild(_dce('td'));
                  newtr2.td1.setAttribute('colspan','3');
                  newtr2.td1.setAttribute('style','border-right:0px solid #bbb;padding:1px;border-bottom:1px solid #333;background-color:#fff;padding-left:3px;');
                  newtr2.td1.innerHTML = '[ <span class=\"ylnk\" onclick=\"edit_kpi(\\''+pms_objective_id+'\\',\\'new\\',this,event);\">Add KPI</span> ]';
                  if(data[9]>0) {
                     newtr2.td2 = newtr2.appendChild(_dce('td'));
                     newtr2.td2.setAttribute('colspan',data[9]);
                     newtr2.td2.setAttribute('style','border-left:1px solid #bbb;text-align:center;border-bottom:1px solid #333;background-color:#fff;');
                     newtr2.td2.innerHTML = '&nbsp;';
                  }
                  
                  if(prevtraddkpi) {
                     newtr = prevtraddkpi.parentNode.insertBefore(newtr,prevtraddkpi.nextSibling);
                     newtr2 = newtr.parentNode.insertBefore(newtr2,newtr.nextSibling);
                  } else {
                     if(data[11]==1) {
                        
                     }
                  }
                  if(data[11]==1) { /// first and the only children of the parent
                     var pms_parent_objective_id = data[13];
                     if($('td1obj_'+pms_parent_objective_id)) $('td1obj_'+pms_parent_objective_id).setAttribute('rowspan','1');
                     if($('td2obj_'+pms_parent_objective_id)) $('td2obj_'+pms_parent_objective_id).setAttribute('rowspan','1');
                     if($('td2obj_'+pms_parent_objective_id)) $('td2obj_'+pms_parent_objective_id).setAttribute('colspan',parseInt(6+data[9]));
                     if($('td2obj_'+pms_parent_objective_id)) $('td2obj_'+pms_parent_objective_id).innerHTML = data[12];
                     while(1) {
                        var ntd = $('td2obj_'+pms_parent_objective_id).nextSibling;
                        if(ntd) {
                           _destroy(ntd);
                        } else {
                           break;
                        }
                     }
                     while(1) {
                        var xtr = $('trobj_'+pms_parent_objective_id);
                        var ntr = xtr.nextSibling;
                        if(ntr) {
                           if(ntr.id=='traddkpi_'+pms_parent_objective_id) {
                              _destroy(ntr);
                              break;
                           } else {
                              _destroy(ntr);
                           }
                        }
                     }
                     
                     newtr = $('trobj_'+pms_parent_objective_id).parentNode.insertBefore(newtr,$('trobj_'+pms_parent_objective_id).nextSibling);
                     newtr2 = newtr.parentNode.insertBefore(newtr2,newtr.nextSibling);
                     
                     
                  }
                  
               } else { /////////// normal objective
                  
                  var trsubtotal = $('trsubtotalperspective_'+data[14]);
                  var newtr = _dce('tr');
                  newtr.setAttribute('id','trobj_'+pms_objective_id);
                  newtr.td1 = newtr.appendChild(_dce('td'));
                  newtr.td1.setAttribute('id','td1obj_'+pms_objective_id);
                  newtr.td1.setAttribute('rowspan','2');
                  newtr.td1.setAttribute('style','vertical-align:middle;text-align:left;border-right:1px solid #333;color:black;font-weight:bold;border-bottom:1px solid #333;');
                  newtr.td1.innerHTML = trim(' '+data[8]+data[7]);
                  newtr.td2 = newtr.appendChild(_dce('td'));
                  newtr.td2.setAttribute('id','td2obj_'+pms_objective_id);
                  newtr.td2.setAttribute('rowspan','2');
                  newtr.td2.setAttribute('style','vertical-align:middle;border-right:1px solid #bbb;border-bottom:1px solid #333;');
                  newtr.td2.innerHTML = '<span onclick=\"edit_so(\\''+pms_objective_id+'\\',this,event);\" class=\"xlnk\">'+data[2]+'</span>';
                  newtr.td3 = newtr.appendChild(_dce('td'));
                  newtr.td3.setAttribute('id','td3obj_'+pms_objective_id);
                  newtr.td3.setAttribute('rowspan','2');
                  newtr.td3.setAttribute('style','vertical-align:middle;border-right:1px solid #bbb;text-align:center;border-bottom:1px solid #333;');
                  newtr.td3.innerHTML = data[3];
                  newtr.td4 = newtr.appendChild(_dce('td'));
                  newtr.td4.setAttribute('id','td4obj_'+pms_objective_id);
                  newtr.td4.setAttribute('rowspan','2');
                  newtr.td4.setAttribute('style','vertical-align:middle;border-right:1px solid #bbb;border-bottom:1px solid #333;');
                  newtr.td4.innerHTML = '<div style=\"width:50px;overflow:hidden;\"><div style=\"width:900px;\">'+data[4]+'</div></div>';
                  newtr.tdemptykpi = newtr.appendChild(_dce('td'));
                  newtr.tdemptykpi.setAttribute('id','tdemptykpi_'+pms_objective_id);
                  newtr.tdemptykpi.setAttribute('colspan','3');
                  newtr.tdemptykpi.setAttribute('style','border-right:0px solid #bbb;border-bottom:1px solid #bbb;font-style:italic;color:#aaa;');
                  newtr.tdemptykpi.innerHTML = '"._EMPTY."';
               
                  if(data[9]>0) {
                     newtr.tdemptykpishare = newtr.appendChild(_dce('td'));
                     newtr.tdemptykpishare.setAttribute('id','tdemptykpishare_'+pms_objective_id);
                     newtr.tdemptykpishare.setAttribute('colspan',data[9]);
                     newtr.tdemptykpishare.setAttribute('style','border-left:1px solid #bbb;text-align:center;border-bottom:1px solid #bbb;');
                     newtr.tdemptykpishare.innerHTML = '&nbsp;';
                  }
                  newtr2 = _dce('tr');
                  newtr2.setAttribute('id','traddkpi_'+pms_objective_id);
                  newtr2.td1 = newtr2.appendChild(_dce('td'));
                  newtr2.td1.setAttribute('colspan','3');
                  newtr2.td1.setAttribute('style','border-right:0px solid #bbb;padding:1px;border-bottom:1px solid #333;background-color:#fff;padding-left:3px;');
                  newtr2.td1.innerHTML = '[ <span class=\"ylnk\" onclick=\"edit_kpi(\\''+pms_objective_id+'\\',\\'new\\',this,event);\">Add KPI</span> ]';
                  if(data[9]>0) {
                     newtr2.td2 = newtr2.appendChild(_dce('td'));
                     newtr2.td2.setAttribute('colspan',data[9]);
                     newtr2.td2.setAttribute('style','border-left:1px solid #bbb;text-align:center;border-bottom:1px solid #333;background-color:#fff;');
                     newtr2.td2.innerHTML = '&nbsp;';
                  }
               
                  newtr = trsubtotal.parentNode.insertBefore(newtr,trsubtotal);
                  newtr2 = newtr.parentNode.insertBefore(newtr2,newtr.nextSibling);
                  
               }
            } else { /////////// update objective
               editsobox.d.innerHTML = data[2];
               $('td3obj_'+pms_objective_id).innerHTML = data[3];
               $('td4obj_'+pms_objective_id).firstChild.firstChild.innerHTML = data[4];
               if(data[14]!=data[17]) { /// change perspective
                  $('td1obj_'+pms_objective_id).innerHTML = trim(' '+data[8]+data[7]);
                  var new_subtotal = $('trsubtotalperspective_'+data[14]);
                  var tr = $('trobj_'+pms_objective_id);
                  if(tr) {
                     while(1) {
                        var nx = tr.nextSibling;
                        if(tr.id=='traddkpi_'+pms_objective_id) {
                           var xtr = _destroy(tr);
                           new_subtotal.parentNode.insertBefore(xtr,new_subtotal);
                           break;
                        } else if(tr.id=='') {
                           var xtr = _destroy(tr);
                           new_subtotal.parentNode.insertBefore(xtr,new_subtotal);
                        } else if(tr.id!=''&&tr.id=='trobj_'+pms_objective_id) {
                           var xtr = _destroy(tr);
                           new_subtotal.parentNode.insertBefore(xtr,new_subtotal);
                        } else {
                           break;
                        }
                        if(!nx) break;
                        tr = nx;
                     }
                  }
                  
                     
                  
                  
               }
            }
            editsobox.fade();
            updating_agent(data[15]);
            for(var i=0;i<=data[16].length;i++) {
               if(data[16][i]) {
                  var td = $('tdkpishareorg_'+data[16][i][0]+'_'+data[16][i][1]+'_'+data[16][i][2]);
                  if(td) {
                     td.firstChild.innerHTML = data[16][i][3];
                  }
               }
            }
         
            
         });
      }
      
      function chgno(d,e) {
         var k = getkeyc(e);
         if(k==9) return;
         if(d.chgt) {
            d.chgt.reset();
            d.chgt = null;
         }
         d.chgt = new ctimer('_setcode();',200);
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
         orgjx_app_getNo(p,editsobox.pms_objective_id,function(_data) {
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
         editsobox.init('editsoedit','800px','500px','hidden','default',false,false);
         editsobox.lbo(false,0.3);
         editsobox.appear();
         editsobox.d = d;
         editsobox.pms_objective_id = pms_objective_id;
         
         orgjx_app_editSO(pms_objective_id,function(_data) {
            $('innereditsoedit').innerHTML = _data;
            setTimeout(\"_dsa($('pms_objective_no'))\",300);
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
            SetCookie('pms_objective_scroll_pos',0);
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
            SetCookie('pms_objective_scroll_pos',0);
            location.reload(true);
         });
      }
      
      //]]></script>";
      
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

} // PMS_OBJECTIVE_DEFINED
?>