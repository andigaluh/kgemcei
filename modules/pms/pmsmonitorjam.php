<?php
//--------------------------------------------------------------------//
// Filename : modules/pms/pmsjam.php                                  //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2010-09-22                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('PMS_JAMMONITORREPORT_DEFINED') ) {
   define('PMS_JAMMONITORREPORT_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
include_once(XOCP_DOC_ROOT."/modules/pms/class/ajax_objective.php");
require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
include_once(XOCP_DOC_ROOT."/modules/pms/class/selectpms.php");
include_once(XOCP_DOC_ROOT."/modules/pms/include/jammonitor.php");

class _pms_JAMMonitorReport extends XocpBlock {
   var $catchvar = _PMS_CATCH_VAR;
   var $blockID = _PMS_JAMMONITORREPORT_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _PMS_JAMMONITORREPORT_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function __construct($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */

   }
   
   ////////////////////////////////////
   ////////////////////////////////////
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
            location.reload();
         });
      }
      
      var dv = null;
      function show_org_opt(d,e) {
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
              <tr><td id='hris_org_nm'>Level of Organization : <span style='font-weight:bold;'>".htmlentities($org_nm,ENT_QUOTES)."</span></td>
              <td align='right'>[<span class='xlnk' id='chorgsp' onclick='return show_org_opt(this,event);'>Change Level"
              ."</span>]</td></tr></table><div id='list_org' style='display:none;background-color:#FFFFFF;text-align:left;'></div></div>";
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
   
   
   ////////////////////////////////////
   ////////////////////////////////////
   
   
   function pmsjam() {
      $psid = $_SESSION["pms_psid"];
      $db=&Database::getInstance();
      $ajax = new _pms_class_ObjectiveAjax("orgjx");
      $user_id = getUserID();
      
      $org_id = $_SESSION["pms_org_id"];
      
      $ret = "";
      
      $ret .= "<table style='width:100%;margin-top:10px;' class='xxlist'>";
      
      $ret .= "<colgroup><col width='50'/><col/><col/></colgroup>";
      
      $ret .= "<tbody>";
      
      
      $sql = "SELECT pms_perspective_code,pms_perspective_id,pms_perspective_name FROM pms_perspective WHERE psid = '$psid' ORDER BY pms_perspective_id";
      $ttl = 0;
      $ttlweight = 0;
      $ttlw = 0;
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($pms_perspective_code,$pms_perspective_id,$pms_perspective_name)=$db->fetchRow($result)) {                            //////////////////////////// per perspective
            $ret .= "<tr><td colspan='4' style='font-weight:bold;background-color:#ddddff;padding:10px;border-top:3px solid #555;'>$pms_perspective_name Perspective</td></tr>";
            $ret .= "<tr><td style='font-weight:bold;'>Code</td><td style='font-weight:bold;'>Objective</td>"
                  . "<td style='text-align:right;font-weight:bold;'>Achievement</td>"
                  . "<td style='text-align:right;font-weight:bold;'>Weight</td>"
                  . "</tr>";
            
            $sql = "SELECT pms_objective_id,pms_objective_no,pms_objective_text,pms_kpi_text,pms_target_text,pms_measurement_unit,pms_objective_weight,"
                 . "pms_pic_job_id,pms_pic_employee_id,pms_parent_objective_id,pms_parent_kpi_id"
                 . " FROM pms_objective"
                 . " WHERE pms_org_id = '$org_id'"
                 . " AND pms_perspective_id = '$pms_perspective_id'"
                 . " AND psid = '$psid'"
                 . " ORDER BY pms_objective_no";
            $ro = $db->query($sql);
            $cnt = $db->getRowsNum($ro);
            if($cnt>0) {
               while(list($pms_objective_id,$pms_objective_no,$pms_objective_text,$pms_kpi_text,$pms_target_text,$pms_measurement_unit,$pms_objective_weight,
                          $pms_pic_job_id,$pms_pic_employee_id,$pms_parent_objective_idx,$pms_parent_kpi_idx)=$db->fetchRow($ro)) {                                 //////////////////// per objective
                  list($ach0,$ach1)=_pmsjamreport_calcSO($pms_objective_id,FALSE,0);
                  
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
                     $ttl += ($ach0*$pms_objective_weight)/100;
                  }
                  
                  
                  
                  $ret .= "<tr><td>${pms_perspective_code}${pms_objective_no}</td><td>".htmlentities($pms_objective_text,ENT_QUOTES)."</td>"
                        . "<td style='text-align:right;'>".toMoney($ach0)." %</td>"
                        . "<td style='text-align:right;'>".toMoney($pms_objective_weight)." %</td>"
                        . "</tr>";
                  
                  $ttlweight += $pms_objective_weight;
                  
               }
            }
            
            
         }
      }
      
      
      $ret .= "<tr style='font-size:1.5em;font-weight:bold;'><td colspan='2' style='border-top:2px solid #bbb;text-align:left;font-weight:bold;'>Total</td>"
            . "<td style='border-top:2px solid #bbb;text-align:right;'>".toMoney($ttl)." %</td>"
            . "<td style='border-top:2px solid #bbb;text-align:right;'>".toMoney($ttlw)." %</td>"
            . "</tr>"; 
      
      $ret .= "</tbody></table>";
      
      
      $js = $ajax->getJs()."<script type='text/javascript'>//<![CDATA[
      
      //]]></script>";
      
      return $ret.$js;
   }
   
   function main() {
      $db = &Database::getInstance();
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
      
      $pmsselobj = new _pms_class_SelectSession();
      $pmssel = "<div style='padding-bottom:2px;'>".$pmsselobj->show()."</div>";
      
      $psid = $_SESSION["pms_psid"];
      
      if(!isset($_SESSION["pms_org_id"])) {
         $_SESSION["pms_org_id"] = 1;
      }
      
      $org_id = $_SESSION["pms_org_id"];
      
      $orgsel = $this->showOrg();
      
      
      switch ($this->catch) {
         case $this->blockID:
            $this->pmsjam($self_employee_id,$_SESSION["pms_jam_org"],$_SESSION["pms_org_id"]);
            break;
         default:
            $ret = $this->pmsjam($self_employee_id,$_SESSION["pms_jam_org"],$_SESSION["pms_org_id"]);
            break;
      }
      return $pmssel.$orgsel.$ret;
   }
}

} // PMS_JAMMONITORREPORT_DEFINED
?>