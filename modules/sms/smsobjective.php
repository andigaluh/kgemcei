<?php
//--------------------------------------------------------------------//
// Filename : modules/sms/smsperspective.php                          //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2013-10-23                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('SMS_OBJECTIVE_DEFINED') ) {
   define('SMS_OBJECTIVE_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

include_once(XOCP_DOC_ROOT."/modules/sms/smsxocp.php");
include_once(XOCP_DOC_ROOT."/modules/sms/class/ajax_objective.php");
include_once(XOCP_DOC_ROOT."/modules/sms/class/selectsms.php");


class _sms_Objective extends XocpBlock {
   var $catchvar = _SMS_CATCH_VAR;
   var $blockID = _SMS_OBJECTIVE_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _SMS_OBJECTIVE_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _sms_Objective($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */

   }
   
   function showOrg($showOpt=FALSE) {
      $db =& Database::getInstance();

      $person_id = getPersonID();
      if(!isset($_SESSION["sms_org_id"])) {
         $_SESSION["sms_org_id"] = 1;
      }

      $org_id = $_SESSION["sms_org_id"];
      $psid = $_SESSION["sms_id"];

      $sql = "SELECT a.employee_id,e.org_id,e.org_nm,f.org_class_id"
       . " FROM ".XOCP_PREFIX."employee a"
       . " LEFT JOIN ".XOCP_PREFIX."persons b USING(person_id)"
       . " LEFT JOIN ".XOCP_PREFIX."employee_job c USING(employee_id)"
       . " LEFT JOIN ".XOCP_PREFIX."jobs d ON d.job_id = c.job_id"
       . " LEFT JOIN ".XOCP_PREFIX."orgs e USING(org_id)"
       . " LEFT JOIN ".XOCP_PREFIX."org_class f USING(org_class_id)"
       . " WHERE a.status_cd = 'normal' AND a.person_id = $person_id AND c.gradeval >= '9'";// 
      $result = $db->query($sql);
      list($employee_idy,$org_idy,$org_nmy,$org_class_idy) = $db->fetchRow($result);
      
      if ($employee_idy == "") {
        //$returnbtn = "";

        $sql = "SELECT e.org_id,e.org_nm,f.org_class_id"
       . " FROM ".XOCP_PREFIX."employee a"
       . " LEFT JOIN ".XOCP_PREFIX."persons b USING(person_id)"
       . " LEFT JOIN ".XOCP_PREFIX."employee_job c USING(employee_id)"
       . " LEFT JOIN ".XOCP_PREFIX."jobs d ON d.job_id = c.job_id"
       . " LEFT JOIN ".XOCP_PREFIX."orgs e USING(org_id)"
       . " LEFT JOIN ".XOCP_PREFIX."org_class f USING(org_class_id)"
       . " WHERE a.status_cd = 'normal' AND a.person_id = $person_id AND e.org_class_id = '4'";
      $result = $db->query($sql);
      list($org_id,$org_nm,$org_class_id) = $db->fetchRow($result);

      $_SESSION["sms_org_id"] = $org_id;

        return "<div class='orgsel'><table border='0' width='100%' cellpadding='2' cellspacing='0'>
              <tr><td id='hris_org_nm'>Level of Organization : <span style='font-weight:bold;'>$org_nm</span></td>
              </tr></table><div id='list_org' style='display:none;background-color:#FFFFFF;text-align:left;'></div></div>";
      }else{
        $returnbtn = $returnbtn;
        $sql = "SELECT o.org_id,o.org_nm,b.org_class_nm,o.org_abbr"
             . " FROM ".XOCP_PREFIX."orgs o"
             . " LEFT JOIN ".XOCP_PREFIX."org_class b ON b.org_class_id = o.org_class_id"
             . " WHERE o.org_id = '$org_idy'";
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
        
        require_once(XOCP_DOC_ROOT."/modules/sms/class/ajax_selectorg.php");
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
        
        $js .= "\n<script type=\"text/javascript\" src=\"".XOCP_SERVER_SUBDIR."/modules/sms/include/treeorg.js\"></script>";
        
        
        return $js."<div class='orgsel'><table border='0' width='100%' cellpadding='2' cellspacing='0'>
                <tr><td id='hris_org_nm'>Level of Organization : <span style='font-weight:bold;'>$org_nm</span></td>
                <td align='right'>[<span class='xlnk' id='chorgsp' onclick='return show_org_opt(this,event);'>Change Level"
                ."</span>]</td></tr></table><div id='list_org' style='display:none;background-color:#FFFFFF;text-align:left;'></div></div>";
      }
   }

   
   function recurseParentOrg($sms_objective_id) {
      $db=&Database::getInstance();
      $sms_org_id = 0;
      $sql = "SELECT org_id,sms_parent_objective_id FROM sms_section_objective WHERE id = '$sms_objective_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($sms_org_id,$sms_parent_objective_id)=$db->fetchRow($result);
         if($sms_parent_objective_id>0) {
            return $this->recurseParentOrg($sms_parent_objective_id);
         }
      }
      $sql = "SELECT parent_id FROM ".XOCP_PREFIX."orgs WHERE org_id = '$sms_org_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($parent_id)=$db->fetchRow($result);
         return $parent_id; //// return parent_id, hopefully corporate org_id
      }
      return -1;
   }
   
   function smsobjective() {
      global $allow_add_objective;
      include_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      include_once(XOCP_DOC_ROOT."/modules/sms/include/sms.php");
      $db=&Database::getInstance();
      $psid = $_SESSION["sms_id"];
      $org_id = $_SESSION["sms_org_id"];
      $ajax = new _sms_class_ObjectiveAjax("orgjx");
      
      $sql = "SELECT org_class_id FROM ".XOCP_PREFIX."orgs WHERE org_id = '$org_id'";
      $result = $db->query($sql);
      list($current_org_class_id)=$db->fetchRow($result);
      
      $user_id = getUserID();
      $person_id = getPersonID();
      $smsselobj = new _sms_class_SelectSession();
      $smssel = "<div style='padding-bottom:2px;'>".$smsselobj->show()."</div>";
      
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

      $sql = "SELECT e.org_id,e.org_nm,f.org_class_id"
       . " FROM ".XOCP_PREFIX."employee a"
       . " LEFT JOIN ".XOCP_PREFIX."persons b USING(person_id)"
       . " LEFT JOIN ".XOCP_PREFIX."employee_job c USING(employee_id)"
       . " LEFT JOIN ".XOCP_PREFIX."jobs d ON d.job_id = c.job_id"
       . " LEFT JOIN ".XOCP_PREFIX."orgs e USING(org_id)"
       . " LEFT JOIN ".XOCP_PREFIX."org_class f USING(org_class_id)"
       . " WHERE a.status_cd = 'normal' AND a.person_id = $person_id AND e.org_class_id = '4'";
      $result = $db->query($sql);
      list($org_id,$org_nm,$org_class_id) = $db->fetchRow($result);
      
      
      if(!isset($_SESSION["sms_org_id"])) {
         $_SESSION["sms_org_id"] = $org_id;
      }
      
      $org_id = $_SESSION["sms_org_id"];
      
      $found_access = 0;
      $sql = "SELECT sms_org_id,access_id FROM sms_org_access WHERE psid = '$psid' AND employee_id = '$self_employee_id' AND status_cd = 'normal'";
      $result = $db->query($sql);
      $first_access = 0;
      if($db->getRowsNum($result)>0) {
         while(list($sms_org_idx,$access_id)=$db->fetchRow($result)) {
            if($first_access==0) {
               $first_access = $sms_org_idx;
            }
            if($org_id==$sms_org_idx) {
               $_SESSION["sms_org_id"] = $sms_org_idx;
               $org_id = $_SESSION["sms_org_id"];
               $found_access = 1;
            }
         }
         if($first_access>0&&$found_access==0) {
            $_SESSION["sms_org_id"] = $first_access;
            $org_id = $_SESSION["sms_org_id"];
            $found_access = 1;
         }
      } else {
         $sql = "SELECT sms_org_id,access_id FROM sms_org_access WHERE id_section_session = '$psid' AND employee_id = '0' AND status_cd = 'normal'";
         $result = $db->query($sql);
         
         $found_access = 0;
         $first_access = 0;
         if($db->getRowsNum($result)>0) {
            while(list($sms_org_idx,$access_id)=$db->fetchRow($result)) {
               if($first_access==0) {
                  $first_access = $sms_org_idx;
               }
               if($org_id==$sms_org_idx) {
                  $_SESSION["sms_org_id"] = $sms_org_idx;
                  $org_id = $_SESSION["sms_org_id"];
                  $found_access = 1;
               }
            }
            if($fist_access>0&&$found_access==0) {
               $_SESSION["sms_org_id"] = $first_access;
               $org_id = $_SESSION["sms_org_id"];
               $found_access = 1;
            }
         
         }
         
      }
      
      if($_SESSION["hr_smsobjective"]==0&&$found_access==0) {
         return $smssel."<div style='padding:5px;'>You don't have access privilege to setup objectives.</div>";
      }
      
      $sql = "SELECT org_class_id FROM ".XOCP_PREFIX."orgs WHERE org_id = '$org_id'";
      $result = $db->query($sql);
      list($current_org_class_id)=$db->fetchRow($result);
      
      $sub_orgs = array();
      $sql = "SELECT org_id,org_abbr,org_nm FROM ".XOCP_PREFIX."orgs"
           . " WHERE parent_id = '$org_id' AND status_cd = 'normal'";
      $result = $db->query($sql);
      $arr_sub_org = array();
      $sql = "DELETE FROM sms_org_share WHERE id_section_session = '$psid' AND sms_org_id = '$org_id'";
      $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($sub_org_id)=$db->fetchRow($result)) {
            $sql = "REPLACE INTO sms_org_share (id_section_session,sms_org_id,sms_share_org_id) VALUES ('$psid','$org_id','$sub_org_id')";
            $db->query($sql);
            $sub_orgs[$sub_org_id] = 1;
            //$arr_sub_org[] = $sub_org_id;
         }
      }
      
      $sql = "SELECT a.sms_share_org_id,b.org_abbr,b.org_nm,b.org_class_id"
           . " FROM sms_org_share a"
           . " LEFT JOIN ".XOCP_PREFIX."orgs b ON b.org_id = a.sms_share_org_id"
           . " LEFT JOIN ".XOCP_PREFIX."org_class c USING(org_class_id)"
           . " WHERE a.sms_org_id = '$org_id'"
           . " AND a.id_section_session = '$psid'"
           . " AND b.org_class_id < 5"
           . " ORDER BY b.order_no";
      $result = $db->query($sql);
      $tdshare = "";
      $share_arr = array();
      $share_cnt = 0;
      $colgroup = "";
      $has_no_sub_shared = 0;

      // show pic

      $sqlx = "SELECT a.employee_id,a.alias_nm,a.person_id,"
       . "b.person_nm,d.job_id,e.org_id,f.org_class_id "
       . " FROM ".XOCP_PREFIX."employee a"
       . " LEFT JOIN ".XOCP_PREFIX."persons b USING(person_id)"
       . " LEFT JOIN ".XOCP_PREFIX."employee_job c USING(employee_id)"
       . " LEFT JOIN ".XOCP_PREFIX."jobs d ON d.job_id = c.job_id"
       . " LEFT JOIN ".XOCP_PREFIX."orgs e USING(org_id)"
       . " LEFT JOIN ".XOCP_PREFIX."org_class f USING(org_class_id)"
       . " WHERE a.status_cd = 'normal' AND e.org_id = '$org_id' AND f.org_class_id = '$current_org_class_id' AND c.gradeval > 5"
       . " ORDER BY a.entrance_dttm ASC";
      $resultx = $db->query($sqlx);
      $picnum = $db->getRowsNum($resultx);
      $tdpic = "";
      $empid = "";
      $i = 0;
      if($db->getRowsNum($resultx)>0) {
         while(list($employee_idx,$alias_nmx,$person_idx,$person_nmx)=$db->fetchRow($resultx)) {
          $empid .= $employee_idx;
          $fname = explode(" ", $person_nmx);
          if ($i < ($picnum-1)) {
            $empid .= ",";
          }
            $tdpic .= "<td style='border-bottom:1px solid #bbb;border-right:1px solid #bbb;width:50px;'>".($alias_nmx==""?$fname[0]:$alias_nmx)."</td>";
          $i++;
         }
      }else{
        $tdpic .="<td style='border-bottom:1px solid #bbb;border-right:1px solid #bbb;width:25px;>"._EMPTY."</td>";
      }

      if($db->getRowsNum($result)>0) {
         $share_cnt = $db->getRowsNum($result);
         $sharehead = "";
         while(list($sms_share_org_id,$sms_share_org_abbr,$sms_share_org_nm)=$db->fetchRow($result)) {
            
            
            $tdshare .= "<td style='border-bottom:1px solid #bbb;border-left:1px solid #bbb;text-align:center;'><span class='xlnk' onclick='view_share(\"$sms_share_org_id\",this,event);'>$sms_share_org_abbr</span></td>";
            $share_arr[] = array($sms_share_org_id,$sms_share_org_nm,$sms_share_org_abbr);
            $colgroup .= "<col width='50'/>";
         }
      } else {
         $has_no_sub_shared = 1;
         $tdshare .= ""; //"<td style='border-bottom:1px solid #333;border-left:1px solid #bbb;text-align:center;'>-</td>";
         $sharehead = "";
         $colgroup .= ""; //"<col width='50'/>";
      }
      
      $sql = "SELECT a.org_abbr,a.org_nm,b.org_class_nm FROM ".XOCP_PREFIX."orgs a LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . " WHERE org_id = '".$_SESSION["sms_org_id"]."'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($org_abbr,$org_nm,$org_class_nm)=$db->fetchRow($result);
         $orgsel = "<div style='padding:5px;border:1px solid #bbb;background-color:#ddd;'><span id='orgspan' class='xlnk' onclick='select_org(this,event);'>Level Organization : <span style='font-weight:bold;'>$org_nm $org_class_nm</span></span></div>";
      }
      
      if(!isset($_SESSION["sms_id"])||$_SESSION["sms_id"]==0) {
         return $smssel;
      }
      
      $orgsel = $this->showOrg();
      
      reset($share_arr);

      $paperclip = "<div style='background-color: #FF8C00;border: 1px solid #B0A94F;border-radius: 5px;box-shadow: 0 1px 2px #000000;clear: both;padding: 0;'>"
           . "<img style='margin-left:25px;margin-top:-10px;position:absolute;z-index:100;' src='".XOCP_SERVER_SUBDIR."/images/paperclip.png'>"
           . "<div style='padding: 20px;'>"
           . "<div style='background-color: #FCF59B;border-radius: 500px 5px 100px 10px / 5px 100px 10px 500px;box-shadow: 1px 1px 3px #000000;padding: 60px 20px 20px;position: relative;'>"
           
           ."</div></div></div>";
      
      $btn_export = "<div style='float:left;margin-top:10px;margin-bottom:10px;'><img src='".XOCP_SERVER_SUBDIR."/images/xl2.png' style='width:25px;margin:0 5px -8px 0;'><a class='xlnk' style='background-color: #6CC04F;border-radius: 3px;color: #FFFFFF;cursor: pointer;padding: 4px !important;text-decoration: none !important;' href='modules/sms/export_excel_section_objective.php?&psid=$psid&person_id=$person_id&org_id=$org_id'>Export to Excel</a></div>";

      $ret =  "<div style='background-color: #FF8C00;border: 1px solid #B0A94F;border-radius: 5px;box-shadow: 0 1px 2px #000000;clear: both;padding: 0;margin-top:15px;'>"
           . "<img style='margin-left:25px;margin-top:-10px;position:absolute;z-index:100;' src='".XOCP_SERVER_SUBDIR."/images/paperclip.png'>"
           . "<div style='padding: 20px;'>"
           . "<div style='background-color: #F1F1F1;border-radius: 500px 5px 100px 10px / 5px 100px 10px 500px;box-shadow: 1px 1px 3px #000000;padding: 60px 20px 20px;position: relative;'>"
           . "<div style=''>"
           . "<div style='font-weight:bold;float:left;width:701px;margin-top:15px;margin-bottom:10px;'>".$org_nm." ".$org_class_nm."</div>"
           . $btn_export
           . "</div>"
           ."<table class='yylist' style='width:100%;'>"
           . "<colgroup>"
           //. "<col width='30'/>"
           . "<col width='220'/>"
           //. "<col width='70'/>"
           . "<col width='200'/>"
           . "<col width='50'/>"
           . "<col width='50'/>"
           . "<col width='150'/>"
           . "<col width='*'/>"
           . $colgroup
           . "</colgroup>"
           . "<thead>"
           . $sharehead;
           
      $trhd = "<tr>"
           //. "<td style='border-bottom:1px solid #333;text-align:left;border-right:1px solid #bbb;'>ID</td>"
           . "<td style='border-bottom:1px solid #bbb;border-right:1px solid #bbb;'>Objective</td>"
           //. "<td style='border-bottom:1px solid #bbb;border-right:1px solid #bbb;text-align:center;'>Weight (%)</td>"
           . "<td style='border-bottom:1px solid #bbb;border-right:1px solid #bbb;'>KPI</td>"
           . "<td style='border-bottom:1px solid #bbb;border-right:1px solid #bbb;'>Unit</td>"
           . "<td style='border-bottom:1px solid #bbb;border-right:1px solid #bbb;'>Target</td>"
           . "<td style='border-bottom:1px solid #bbb;border-right:1px solid #bbb;'>Action Plan</td>"
           . "<td colspan='".(6+$picnum)."' style='border-bottom:1px solid #bbb;border-right:0px solid #bbb;'>PIC</td>"
           . $tdshare
           . "</tr>";


      $rowpic = "<tr>"
           //. "<td style='border-bottom:1px solid #bbb;border-right:1px solid #bbb;'></td>"
           . "<td style='border-bottom:1px solid #bbb;border-right:1px solid #bbb;'></td>"
           . "<td style='border-bottom:1px solid #bbb;border-right:1px solid #bbb;'></td>"
           . "<td style='border-bottom:1px solid #bbb;border-right:1px solid #bbb;'></td>"
           . "<td style='border-bottom:1px solid #bbb;border-right:1px solid #bbb;'></td>"
           . "<td style='border-bottom:1px solid #bbb;border-right:1px solid #bbb;'></td>"
           . $tdpic
           . "</tr>";
      
      //$ret .= $trhd;
      
      $ret .= "</thead>"
           . "<tbody>";
      
      //$sql = "SELECT code,id,title FROM sms_section_perspective WHERE id_section_session = '$psid' ORDER BY id";
      //$result = $db->query($sql);
      //list($sms_perspective_code,$sms_perspective_id,$sms_perspective_name)=$db->fetchRow($result)
      
      //$ttlw = 0;
      //$ttl_sms_share = array();
      $job_nm = $job_abbr = "";

      $sql = "SELECT employee_id FROM ".XOCP_PREFIX."employee WHERE person_id = '$person_id'";
      $result = $db->query($sql);
      list($employee_id)=$db->fetchRow($result);

      $sql = "SELECT b.job_class_id FROM hris_employee_job a LEFT JOIN hris_jobs b ON a.job_id = b.job_id WHERE a.employee_id = '$employee_id'";
      $result = $db->query($sql);
      list($job_class_id)=$db->fetchRow($result);

      if ($job_class_id == 2 OR $job_class_id == 3 OR $job_class_id == 4) {
        $add_btn = "&nbsp;&nbsp;<span class='xlnk' onclick='edit_so(\"new_${sms_perspective_id}\",this,event);' style='font-weight:normal;'/><img src='".XOCP_SERVER_SUBDIR."/images/add_behavior.png' style='width:17px;float:left;'>Add Objective</span>&nbsp;";
      }else{
        $add_btn = "";
      }

            $ret .= "<tr><td style='border:0px;border-bottom:0px solid #333;background-color:#f1f1f1;display:none;' colspan='".(6+$picnum+($share_cnt==0?0:$share_cnt))."'>&nbsp;</td></tr>"
                  . "<tr><td colspan='".(6+$picnum)."' style='font-weight:bold;border-bottom:1px solid #bbb;color:black;background-color:#ddf;padding:10px;'>"
                  . $add_btn
                  . "</td>"
                  . ($tdshare!=""?"<td colspan='".($share_cnt==0?0:$share_cnt)."' style='border-bottom:1px solid #333;background-color:#ddf;padding:10px;border-left:1px solid #bbb;text-align:center;'>"
                  . "<div style='min-width:50px;'>Share %</div>"
                  . "</td>":"")
                  . "</tr>";
            $ret .= "</tbody><thead>$trhd</thead>$rowpic<tbody>";
            $sql = "SELECT id,objective_no,section_objective_desc,kpi_text,target_text,measurement_unit,weight,"
                 . "pic_job_id,pic_employee_id,parent_objective_id,parent_kpi_id"
                 . " FROM sms_section_objective"
                 . " WHERE org_id = '$org_id'"
                 . " AND id_section_perspective = '$sms_perspective_id'"
                 . " AND id_section_session = '$psid'"
                 . " ORDER BY id";
            $ro = $db->query($sql);
            $cnt = $db->getRowsNum($ro);
            $so = "";
            $so_no = 0;
            if($cnt>0) {
               $subttlw = 0;
               $subttl_sms_share = array();
               while(list($sms_objective_id,$sms_objective_no,$sms_objective_text,$sms_kpi_text,$sms_target_text,$sms_measurement_unit,$sms_objective_weight,
                          $sms_pic_job_id,$sms_pic_employee_id,$sms_parent_objective_idx,$sms_parent_kpi_idx)=$db->fetchRow($ro)) {
                  
                  if(trim($sms_objective_text)=="") {
                     $sms_objective_text = _EMPTY;
                  }
                  
                  $top_level_org_id = $this->recurseParentOrg($sms_objective_id);
                  
                  /// check if it is a local sub
                  $sql = "SELECT org_id FROM sms_section_objective WHERE id_section_session = '$psid' AND id = '$sms_parent_objective_idx'";
                  $rp = $db->query($sql);
                  if($db->getRowsNum($rp)>0) {
                     list($sms_parent_org_idx)=$db->fetchRow($rp);
                  }
                  
                  /// has local sub?
                  $sql = "SELECT id,org_id,weight FROM sms_section_objective WHERE id_section_session = '$psid' AND parent_objective_id = '$sms_objective_id' AND org_id = '$org_id'";
                  $rchild = $db->query($sql);
                  $has_local_sub = 0;
                  $ttl_sub_weight = 0;
                  if($db->getRowsNum($rchild)>0) {
                     while(list($sub_sms_objective_id,$sub_sms_org_id,$sub_weight)=$db->fetchRow($rchild)) {
                        $has_local_sub++;
                        $ttl_sub_weight = _bctrim(bcadd($ttl_sub_weight,$sub_weight));
                     }
                  }
                  
                  $sql = "SELECT a.job_nm,a.job_abbr FROM ".XOCP_PREFIX."jobs a WHERE a.job_id = '$sms_pic_job_id'";
                  $rj = $db->query($sql);
                  if($db->getRowsNum($rj)>0) {
                     list($so_pic_job_nm,$so_pic_job_abbr)=$db->fetchRow($rj);
                  } else {
                     $so_pic_job_nm = $so_pic_job_abbr = "";
                  }
                  $kpi_cnt = 0;
                  $sql = "SELECT sms_kpi_id,sms_kpi_text,sms_kpi_weight,sms_kpi_target_text,sms_kpi_measurement_unit,sms_kpi_pic_employee_id"
                       . " FROM sms_kpi WHERE id_section_session = '$psid' AND sms_objective_id = '$sms_objective_id'";
                  $rkpi = $db->query($sql);
                  $kpi_cnt = $db->getRowsNum($rkpi);
                  
                  if($kpi_cnt>0&&$has_local_sub==0) {
                     $ret .= "<tr>"
                           //. "<td rowspan='".($kpi_cnt+1)."' style='vertical-align:middle;".($sms_parent_objective_idx>0&&$top_level_org_id==0?"color:blue;":"color:black;")."text-align:left;border-right:1px solid #333;font-weight:bold;border-bottom:1px solid #333;'>${sms_objective_id} Test2</td>"
                           . "<td rowspan='".($kpi_cnt+1)."' style='vertical-align:top;border-right:1px solid #bbb;border-bottom:1px solid #bbb;'>"
                           . "<span onclick='edit_so(\"$sms_objective_id\",this,event);' class='xlnk'>".htmlentities($sms_objective_text)." </span>[ <span class='ylnk' onclick='edit_kpi(\"$sms_objective_id\",\"new\",this,event);'> Add KPI </span> ]</td>";
                           //. "<td rowspan = '".($kpi_cnt+1)."' colspan = '$picnum'".+(3)."></td>";
                           //. "<td rowspan='".($kpi_cnt+1)."' style='vertical-align:middle;border-right:1px solid #bbb;text-align:center;border-bottom:1px solid #333;'>".toMoney($sms_objective_weight)."</td>"
                           //. "<td rowspan='".($kpi_cnt+1)."' style='vertical-align:middle;border-right:1px solid #bbb;border-bottom:1px solid #333;'><div style='width:50px;overflow:hidden;'><div style='width:900px;'>$so_pic_job_abbr</div></div></td>";
                     $kpi_no = 0;
                     while(list($sms_kpi_id,$sms_kpi_text,$sms_kpi_weight,$sms_kpi_target_text,$sms_kpi_measurement_unit,$sms_kpi_pic_employee_id)=$db->fetchRow($rkpi)) {
                        if($kpi_no>0) {
                           //$ret .= "<tr><td colspan='3' style='border-right:1px solid #bbb;".(($kpi_no+1)==$kpi_cnt?"":"border-bottom:0;")."'>&nbsp;</td>";
                        }
                        $ret .= "<td style='border-right:1px solid #bbb;'><span class='xlnk' onclick='edit_kpi(\"$sms_objective_id\",\"$sms_kpi_id\",this,event);'>".htmlentities($sms_kpi_text)."</span> [<span class='ylnk' onclick='edit_actionplan(\"$sms_objective_id\",\"$sms_kpi_id\",\"new\",this,event);'> Add Action Plan </span>]</td>"
                              . "<td style='border-right:1px solid #bbb;'>".htmlentities($sms_kpi_measurement_unit)."</td>"
                              . "<td style='border-right:1px solid #bbb;'>".htmlentities($sms_kpi_target_text)."</td>";

                        /*$j = 0;
                        $tdpicres = "";
                        $empidx = explode(",", $empid);
                        $pic_employee_idex = explode(",", $sms_kpi_pic_employee_id);
   
                        while ($j < $picnum) {
                          $arsrch = array_search($empidx[$j], $pic_employee_idex);
                          if ($arsrch > -1) {
                            $picres = "Y";
                          }else{
                            $picres = "-";
                          }
                          $tdpicres .= "<td style='border-right:1px solid #bbb;'>$picres</td>";
                          $j++;
                        }*/

                        $action_plan_cnt = 0;
                        $sqls = "SELECT sms_action_plan_id,sms_action_plan_text,sms_action_plan_pic_employee_id FROM sms_action_plan WHERE sms_kpi_id = '$sms_kpi_id' AND sms_objective_id = '$sms_objective_id'";
                        $ractionplan = $db->query($sqls);
                        $action_plan_cnt = $db->getRowsNum($ractionplan);

                        $ret .= "<td style='border-right:1px solid #bbb;'><table>";

                        while (list($sms_action_plan_id,$sms_action_plan_text,$sms_action_plan_pic_employee_id)=$db->fetchRow($ractionplan)) {
                            /*$j = 0;
                            $k = 0;
                            $tdpicres = "";
                            $row_action_plan = "";
                            $empidx = explode(",", $empid);
                            $pic_employee_idex = explode(",", $sms_action_plan_pic_employee_id);
       
                            while ($j < $picnum) {
                              $arsrch = array_search($empidx[$j], $pic_employee_idex);
                              if ($arsrch > -1) {
                                $picres = "Y";
                              }else{
                                $picres = "-";
                              }
                              $tdpicres .= "<td style='border-right:1px solid #bbb;'>$picres</td>";
                              $j++;
                            }*/
                        
                            $ret .= "<tr style='height:100px;vertical-align:top;'><td><span class='xlnk' onclick='edit_actionplan(\"$sms_objective_id\",\"$sms_kpi_id\",\"$sms_action_plan_id\",this,event);'>".htmlentities($sms_action_plan_text)."</span></td></tr>";
                        }

                        $ret .= "</table></td>";

                        $sqls = "SELECT sms_action_plan_id,sms_action_plan_text,sms_action_plan_pic_employee_id FROM sms_action_plan WHERE sms_kpi_id = '$sms_kpi_id' AND sms_objective_id = '$sms_objective_id'";
                        $ractionplan = $db->query($sqls);
                        $action_plan_cnt = $db->getRowsNum($ractionplan);
                        
                        $ret .= "<td colspan='$picnum'><table style='width:100%;text-align:center;'>";

                        while (list($sms_action_plan_id,$sms_action_plan_text,$sms_action_plan_pic_employee_id)=$db->fetchRow($ractionplan)) {
                            $j = 0;
                            $k = 0;
                            $tdpicres = "";
                            $tdpicresnum = "";
                            $row_action_plan = "";
                            $empidx = explode(",", $empid);
                            $pic_employee_idex = explode(",", $sms_action_plan_pic_employee_id);
                            $num_pic[] = 0;
                            $ret .= "<tr style='height:100px;vertical-align:top;'>";
                            while ($j < $picnum) {
                              $arsrch = array_search($empidx[$j], $pic_employee_idex);
                              if ($arsrch > -1) {
                                $num_pic[$empidx[$j]]++;
                                $picres = "&#10004;";
                              }else{
                                $picres = "-";
                              }
                              $tdpicres .= "<td style='border-right:0px solid #bbb;width:50px;'>$picres</td>";
                              $tdpicresnum .= "<td style='border-right:0px solid #bbb;width:50px;'>".$num_pic[$empidx[$j]]."</td>";
                              $j++;
                            }
                            $ret .= $tdpicres;
                            $ret .= "</tr>";
                            
                        }

                        $ret .= "</tr></table></td>";

                        

                        if($share_cnt>0) {
                           foreach($share_arr as $vshare) {
                              list($sms_share_org_id,$sms_share_org_nm,$sms_share_org_abbr)=$vshare;
                              $sql = "SELECT sms_share_weight FROM sms_kpi_share"
                                   . " WHERE id_section_session = '$psid' AND id_section_objective = '$sms_objective_id'"
                                   . " AND sms_kpi_id = '$sms_kpi_id'"
                                   . " AND sms_org_id = '$org_id'"
                                   . " AND sms_share_org_id = '$sms_share_org_id'";
                              $rw = $db->query($sql);
                              if($db->getRowsNum($rw)>0) {
                                 list($sms_share_weight)=$db->fetchRow($rw);
                              } else {
                                 $sms_share_weight = 0;
                              }
                              if($sms_share_weight>0) {
                                 $sms_share_weight_txt = toMoney($sms_share_weight);
                                 $sms_share_weight_txt = "<span style='color:#3333ff;'>".toMoney($sms_share_weight)."</span>";
                              } else {
                                 $sms_share_weight_txt = "<span style='color:#333;'>-</span>";
                              }
                              $ret .= "<td style='border-left:1px solid #bbb;text-align:center;' class='tdlnk' onclick='edit_kpi_share(\"$sms_objective_id\",\"$sms_kpi_id\",\"$sms_share_org_id\",this,event);' >$sms_share_weight_txt</td>";
                              if(!isset($subttl_sms_share[$sms_share_org_id])) $subttl_sms_share[$sms_share_org_id] = 0; /// initialize
                              $subttl_sms_share[$sms_share_org_id] = bcadd($subttl_sms_share[$sms_share_org_id],$sms_share_weight);
                           }
                        } else {
                           /// has no sub shared
                           ///$ret .= "<td style='border-left:1px solid #bbb;text-align:center;'>&nbsp;</td>";
                           
                        }
                        
                        $ret .= "</tr>";
                        $kpi_no++;
                        
                     }

                     $separatornum = $picnum + 4;
                     
                     $ret .= "<tr>"
                           //. "<td style='border-right:1px solid #bbb;border-bottom:1px solid #333;background-color:#fff;' colspan='3'></td>"
                           . "<td colspan='$separatornum' style='border-right:0px solid #bbb;padding:1px;border-bottom:1px solid #333;background-color:#fff;padding-left:3px;display:none;'>"
                           . "</td>";
                     
                     /// has no sub shared
                     if($has_no_sub_shared==0) {
                        $ret .= "<td style='border-left:1px solid #bbb;text-align:center;border-bottom:1px solid #333;background-color:#fff;' colspan='".($share_cnt==0?0:$share_cnt)."'></td>";
                     }
                     
                     $ret .= "</tr>";
                     
                     
                  } else {
                     $inherited_kpi = "";
                     $sql = "SELECT sms_kpi_text,sms_kpi_id,sms_kpi_target_text,sms_kpi_measurement_unit FROM sms_kpi WHERE id_section_session = '$psid' AND sms_objective_id = '$sms_objective_id'";
                     $rxkpi = $db->query($sql);
                     if($db->getRowsNum($rxkpi)>0) {
                        while(list($sms_kpi_textxxx,$sms_kpi_idxxx,$sms_kpi_target_textxxx,$sms_kpi_measurement_unitxxx)=$db->fetchRow($rxkpi)) {
                           $inherited_kpi .= "<div style='padding-left:20px;color:#777;'>$sms_kpi_textxxx : $sms_kpi_target_textxxx ($sms_kpi_measurement_unitxxx)</div>";
                        }
                     }
                     
                     $ret .= "<tr>"
                           //. "<td ".($has_local_sub>0?"":"")." style='vertical-align:middle;text-align:left;border-right:1px solid #333;".($sms_parent_objective_idx>0&&$top_level_org_id==0?"color:blue;":"color:black;")."font-weight:bold;border-bottom:1px solid #333;'>${sms_objective_id} test1</td>"
                           . "<td ".($has_local_sub>0?"colspan='".(6+($share_cnt==0?0:$share_cnt))."'":"")." ".($has_local_sub>0?"":"")." style='vertical-align:middle;border-right:1px solid #bbb;border-bottom:1px solid #333;'>"
                           . "<span onclick='edit_so(\"$sms_objective_id\",this,event);' class='xlnk'>".htmlentities($sms_objective_text)."</span> [ <span class='ylnk' onclick='edit_kpi(\"$sms_objective_id\",\"new\",this,event);'>Add KPI</span> ]"
                           //. ($has_local_sub>0?" ( ".toMoney($ttl_sub_weight)." % / ".toMoney($sms_objective_weight)." % )":"")
                           //. ($has_local_sub>0?" [ <span class='ylnk' onclick='add_sub(\"$sms_objective_id\",this,event);'>Add Initiative</span> ]":"")
                           . $inherited_kpi
                           . "</td>";
                     
                     if($has_local_sub==0) {      
                        //$ret .= "<td rowspan='2' style='vertical-align:middle;border-right:1px solid #bbb;text-align:center;border-bottom:1px solid #333;'>".toMoney($sms_objective_weight)."</td>";
                        //$ret .= "<td rowspan='2' style='vertical-align:middle;border-right:1px solid #bbb;border-bottom:1px solid #333;'><div style='width:50px;overflow:hidden;'><div style='width:900px;'>$so_pic_job_abbr</div></div></td>";
                        $ret .= "<td colspan='$picnum".+(3)."' style='border-right:0px solid #333;border-bottom:1px solid #333;text-align:center;font-style:italic;color:#aaa;'>"._EMPTY."</td>";
                        //$ret .= "<td style='border-left:1px solid #bbb;text-align:center;border-bottom:1px solid #bbb;' colspan='".($share_cnt==0?0:$share_cnt)."'></td>";
                     }
                     $ret .= "</tr>";
                     
                     if($has_local_sub==0) {
                        /*$ret .= "<tr>";
                        $ret .= "<td style='border-left:1px solid #bbb;text-align:center;border-bottom:1px solid #333;background-color:#fff;' colspan='".($share_cnt==0?0:$share_cnt)."'></td>";
                        $ret .= "</tr>";*/
                     }
                     
                     
                     
                     
                  }
                  $so_no++;
                  
                  $do_count = 0;
                  if($sms_parent_objective_idx==0) {
                     $do_count++;
                  } else {
                     $sql = "SELECT org_id FROM sms_section_objective WHERE id_section_session = '$psid' AND parent_objective_id = '$sms_objective_id'";
                     $rpx = $db->query($sql);
                     if($sms_objective_id==936) {
                        _debuglog($sql);
                     }
                     if($db->getRowsNum($rpx)>0) {
                        list($sms_parent_org_id)=$db->fetchRow($rpx);
                        if($sms_parent_org_id==$org_id) {
                           //$do_count++;
                        } else {
                           $do_count++;
                        }
                     } else {
                        _debuglog($sql);
                        $do_count++;
                     }
                  }
                  if($has_local_sub==0&&$do_count>0) {
                     $subttlw = _bctrim(bcadd($subttlw,$sms_objective_weight));
                     $ttlw = _bctrim(bcadd($ttlw,$sms_objective_weight));
                  }
               }
               /*$ret .= "<tr>"
                     . "<td colspan='2' style='border-right:1px solid #bbb;text-align:center;border-bottom:3px solid #333;'>Subtotal</td>"
                     . "<td style='text-align:center;background-color:#eeffff;font-weight:bold;color:black;border-right:1px solid #bbb;border-bottom:3px solid #333;'>".toMoney($subttlw)."</td>"
                     . "<td colspan='".(4)."' style='border-right:0px solid #bbb;border-bottom:3px solid #333;'></td>";
               */
               if(count($share_arr)>0) {
                  foreach($share_arr as $vshare) {
                     list($sms_share_org_id,$sms_share_org_nm,$sms_share_org_abbr)=$vshare;
                     
                     if(isset($subttl_sms_share[$sms_share_org_id])&&$subttl_sms_share[$sms_share_org_id]>0) {
                        $subttlkpishare = toMoney($subttl_sms_share[$sms_share_org_id]);
                     } else {
                        $subttlkpishare = "-";
                     }
                     
                     $ret .= "<td id='tdsubttlkpishare_${sms_perspective_id}_${sms_share_org_id}' style='text-align:center;background-color:#eeffff;font-weight:bold;color:black;border-left:1px solid #bbb;border-bottom:3px solid #333;'>$subttlkpishare</td>";
                     $ttl_sms_share[$sms_share_org_id] = bcadd($ttl_sms_share[$sms_share_org_id],$subttl_sms_share[$sms_share_org_id]);
                  }
               } else {
                  //$ret .= "<td style='text-align:center;background-color:#eeffff;font-weight:bold;color:black;border-left:1px solid #bbb;border-bottom:3px solid #333;'>-</td>";
               }
               
               $ret .= "</tr>";
            
            
            } else {
               $ret .= "<tr><td colspan='".(6+$picnum+($share_cnt==0?0:$share_cnt))."' style='text-align:center;font-style:italic;border-bottom:1px solid #bbb;'>"._EMPTY."</td></tr>";
            }
         //} // end while
      //} // end if
      
      $ret .= "<tr style='display:none;'><td style='border:0px;border-bottom:1px solid #bbb;background-color:#f1f1f1;' colspan='".(6+$picnum+($share_cnt==0?0:$share_cnt))."'>&nbsp;</td></tr>";
      $total_shared = 0;
      $retshare = "";
      if(count($share_arr)>0) {
         $tdtotal = "";
         foreach($share_arr as $vshare) {
            list($sms_share_org_id,$sms_share_org_nm,$sms_share_org_abbr)=$vshare;
            $total_shared = _bctrim(bcadd($total_shared,$ttl_sms_share[$sms_share_org_id]));
            $tdtotal .= "<td id='tdttlkpishare_${sms_share_org_id}' style='display:none;text-align:center;background-color:#bbffdd;font-weight:bold;color:black;padding:10px;border:1px solid #bbb;border-right:0;border-top:0;'>".toMoney(_bctrim($ttl_sms_share[$sms_share_org_id]))."</td>";
         }
         $retshare .= "<td id='tdttlshared' style='display:none;text-align:center;background-color:#bbffdd;font-weight:bold;color:black;padding:10px;border:1px solid #bbb;border-right:0;border-top:0;'>".toMoney($total_shared)."</td>$tdtotal";
      } else {
         
            $retshare .= "<td style='display:none;'>&nbsp;</td>";
            
            /*
            $retshare .= "<td style='text-align:center;background-color:#eeffff;font-weight:bold;color:black;border-left:1px solid #bbb;padding:10px;'>-</td>";
            */
            
      }
      
      /*$ret .= "<tr>"
            . "<td colspan='2' style='background-color:#fff;padding:10px;text-align:center;font-weight:bold;border-right:1px solid #bbb;'>Total</td>"
            . "<td style='text-align:center;background-color:#bbffdd;font-weight:bold;color:black;padding:10px;border:1px solid #bbb;border-left:0;border-top:0;'>".toMoney($ttlw)."</td>"
            . "<td id='tdbalancewarning' colspan='".(3)."' style='background-color:#fff;padding:10px;'>";*/
      
      if($has_no_sub_shared==1) {
         $ret .= "&nbsp;";
      } else {
         switch(bccomp(number_format($ttlw,4,".",""),number_format($total_shared,4,".",""))) {
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

      //TOTAL ACTION PLAN PIC
      

      $ret .= "<tr><td></td>"
           . "<td></td>"
           . "<td></td>"
           . "<td></td>"
           . "<td style='padding-top:7px;border-right:1px solid #bbb;'><span style='font-weight:bold;'>TOTAL</span></td>"
           . "<td colspan='$picnum'>"
           . "<table style='width:100%;text-align:center;'>"
           . "<tr style='height:10px;vertical-align:top;'>$tdpicresnum</tr>"
           . "</table>"
           . "</td>" 
           . "<tr>";
      
      $ret .= "</tbody>"
            /*. "<tfoot>"
            . "<tr><td colspan='5'>&nbsp;"
            . "</td>"
            . "<td colspan='".($picnum+($share_cnt==0?0:$share_cnt))."' style='text-align:right;'>"
            ///. "<input type='button' value='Recalculate Weight' onclick='recalculate_weight(this,event);'/>&#160;"
            //. "<input type='button' value='Import Objectives' onclick='import_objectives(this,event);'/>&#160;"
            . ($has_no_sub_shared==1?"":"<input type='button' value='Deploy Objectives' class='xaction' onclick='deploy_objectives(this,event);'/>")
            . "</td></tr>"
            . "</tfoot>"*/
            . "</table>";
            

     // APPROVAL

      $section_manager_id = 0;
      $division_manager_id = 0;

       $sql = "SELECT a.employee_id,a.person_id,"
       . "b.person_nm,d.job_class_id,d.job_id "
       . " FROM ".XOCP_PREFIX."employee a"
       . " LEFT JOIN ".XOCP_PREFIX."persons b USING(person_id)"
       . " LEFT JOIN ".XOCP_PREFIX."employee_job c USING(employee_id)"
       . " LEFT JOIN ".XOCP_PREFIX."jobs d ON d.job_id = c.job_id"
       . " LEFT JOIN ".XOCP_PREFIX."orgs e USING(org_id)"
       . " LEFT JOIN ".XOCP_PREFIX."org_class f USING(org_class_id)"
       . " WHERE a.status_cd = 'normal'"
       . " ORDER BY b.person_nm";
      $result = $db->query($sql);
      if ($db->getRowsNum($result)>0) {
        while (list($employee_idx,$person_idx,$person_nmx,$job_class_idx,$job_idx)=$db->fetchRow($result)) {
         if ($job_class_idx == 3 AND $person_idx == $person_id) {
           $section_manager_nm = $person_nmx;
           $section_manager_id = $employee_idx;
           $section_job_id = $job_idx;
         }elseif ($job_class_idx == 2 AND $person_idx == $person_id) {
           $division_manager_nm = $person_nmx;
           $division_manager_id = $employee_idx;
         }elseif ($job_class_idx == 1 AND $person_idx == $person_id) {
           $division_manager_nm = $person_nmx;
           $division_manager_id = $employee_idx;
         }elseif ($job_idx == 133 AND $person_idx == $person_id) {
           $section_manager_nm = $person_nmx;
           $section_manager_id = $employee_idx;
           $section_job_id = $job_idx;
         }
       }
      }

     $sqlapp = "SELECT section_submit_id,section_submit_date,section_submit,section_approval_id,section_approval_date,section_approval,status_return,date_return,remark FROM sms_approval WHERE id_section_session = '$psid' AND org_id = '$org_id'";
     $resultapp = $db->query($sqlapp); 
     list($section_submit_id,$section_submit_date,$section_submit,$section_approval_id,$section_approval_date,$section_approval,$status_return,$date_return,$remark)=$db->fetchRow($resultapp);

     $section_submit_date = date('d M Y',strtotime($section_submit_date));
     $section_approval_date = date('d M Y',strtotime($section_approval_date));

     if ($section_submit == 0) {
        $sm_button = "<input id='btn_propose' onclick='save_propose(\"$section_manager_id\",\"$section_manager_nm\",this,event);' type='button' value='Propose'/>&nbsp";
        $sm_prop = "";
     }else{
        $sqlprop = "SELECT b.person_nm,d.job_nm,e.org_id,f.org_class_id "
         . " FROM ".XOCP_PREFIX."employee a"
         . " LEFT JOIN ".XOCP_PREFIX."persons b USING(person_id)"
         . " LEFT JOIN ".XOCP_PREFIX."employee_job c USING(employee_id)"
         . " LEFT JOIN ".XOCP_PREFIX."jobs d ON d.job_id = c.job_id"
         . " LEFT JOIN ".XOCP_PREFIX."orgs e USING(org_id)"
         . " LEFT JOIN ".XOCP_PREFIX."org_class f USING(org_class_id)"
         . " WHERE a.employee_id = '$section_submit_id'";
        $resultprop = $db->query($sqlprop);
        list($person_nmy,$job_nmy)=$db->fetchRow($resultprop);
        $sm_button = "";
        $sm_prop = "<table style='width:100%'>"
                . "<tr style='font-weight:bold;'><td>$person_nmy</td></tr>"
                . "<tr><td>$job_nmy</td></tr>"
                . "<tr><td>Submitted on : $section_submit_date</td></tr>"
                . "</table>";
     }

      // RETURN
     /* $sql = "SELECT section_submit,section_approval,status_return,date_return FROM sms_approval WHERE org_id = '$org_id' AND id_section_session = '$psid'";
      $result = $db->query($sql);
      list($section_submit,$section_approval) = $db->fetchRow($result);*/

     if ($section_submit == 0 AND $section_approval == 0) {
        $dm_button = "";
        $dm_app = "";
     }elseif ($section_submit == 1 AND $section_approval == 0) {
       $dm_button = "<input id='btn_approve' onclick='save_approval(\"$division_manager_id\",\"$division_manager_nm\",this,event);' type='button' value='Approve'/>&nbsp";
        $dm_app = "";
        if (($section_submit == 1 OR $division_manager_id == 0) AND $section_approval == 0) {
        $returnbtn = "<span style='margin-left:10px;'><input onclick='return_session(\"$psid\",this,event);' type='button' value='Not Approved' id='returnbtn' /></span>";
      }else{
        $returnbtn = "";
      }
     }
     else{
        $sqlapp = "SELECT b.person_nm,d.job_nm,e.org_id,f.org_class_id "
         . " FROM ".XOCP_PREFIX."employee a"
         . " LEFT JOIN ".XOCP_PREFIX."persons b USING(person_id)"
         . " LEFT JOIN ".XOCP_PREFIX."employee_job c USING(employee_id)"
         . " LEFT JOIN ".XOCP_PREFIX."jobs d ON d.job_id = c.job_id"
         . " LEFT JOIN ".XOCP_PREFIX."orgs e USING(org_id)"
         . " LEFT JOIN ".XOCP_PREFIX."org_class f USING(org_class_id)"
         . " WHERE a.employee_id = '$section_approval_id'";
        $resultapp = $db->query($sqlapp);
        list($person_nmz,$job_nmz)=$db->fetchRow($resultapp);
        $dm_button = "";
        $dm_prop = "<table style='width:100%'>"
        . "<tr style='font-weight:bold;'><td>$person_nmz</td></tr>"
        . "<tr><td>$job_nmz</td></tr>"
        . "<tr><td>Approved on : $section_approval_date</td></tr>"
        . "</table>";
     }

     if ($status_return == 1) {
        $date_return_display = "<table style='width:100%'>"
                . "<tr style='font-weight:bold;'><td></td></tr>"
                . "<tr><td></td></tr>"
                . "<tr><td>Not Approved on : $date_return</td></tr>"
                . "</table>";
     }

     if ($remark != "") {
      $note = "<div style='margin-top:10px;'>Note : $remark</div>";
     }
      $ret .= $note 
           ."<div style='margin-top: 40px;'>"
           . "<table border='0px' cellpadding='2' cellspacing='0' style=' width:500px;margin-left:auto;border:1px #BBB solid'>"
           . "<colgroup>"
           . "<col width='200' />"
           . "<col width='200' />"
           . "</colgroup>"
           . "<tbody>"
           . "<tr style='height:50px;background-color:#DDDDDD;'><th style='border-right:1px #BBB solid'>Proposed By</th><th>Approved By</th></tr>"
           //. "<tr style='height:70px;text-align:center;'><td>".(is_null($section_manager_id)?"":"$sm_button")."</td><td>".(is_null($division_manager_id)?"":"$dm_button")."</td></tr>"
           . "<tr style='height:70px;text-align:center;'>"
           . "<td  style='border-right:1px #BBB solid'>"
           . $sm_prop
           . "<div id='frmpropose'><input type='hidden' name='employee_id' value='$section_manager_id'><input type='hidden' name='employee_nm' value='$section_manager_nm'></div>"
           //.(is_null($section_manager_id) ?"":"$sm_button")
           . ($section_manager_id == 0?"":"$sm_button")
           . $date_return_display
           . "</td>"
           . "<td>"
           . $dm_prop
           . "<div id='frmapproval' ><input type='hidden' name='employee_id' value='$division_manager_id'><input type='hidden' name='employee_nm' value='$division_manager_nm'></div>"
           . ($division_manager_id == 0?"":"$dm_button $returnbtn")
           //. $dm_button
           //. "$returnbtn"
           . "</td></tr>"
           . "</tbody>"
           . "</table>"
           . "</div>"
           . "<div style='padding:2px;font-size:12px;color: #666666; text-align:left;'><div id='id_return'></div></div>"
           . "</div></div></div>"; // end of paperclip
      $ret .= "<div style='padding:100px;'>&nbsp;</div>";
      
      $js = $ajax->getJs()
          . "<script type='text/javascript' src='".XOCP_SERVER_SUBDIR."/include/calendar.js'></script>"
          . "<script type='text/javascript'>//<![CDATA[
      
      function use_sub_so_remaining(d,e) {
         var sms_objective_id = $('sms_objective_id').value;
         var sms_parent_objective_id = $('sms_parent_objective_id').value;
         orgjx_app_SOWeightRemaining(sms_objective_id,sms_parent_objective_id,function(_data) {
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
         var sms_objective_id = $('sms_objective_id').value;
         var sms_parent_objective_id = $('sms_parent_objective_id').value;
         var w = $('weight').value;
         orgjx_app_SOWeight(sms_objective_id,sms_parent_objective_id,w,function(_data) {
            var data = recjsarray(_data);
            if($('sub_so_remaining')&&data[0]=='sub') {
               $('sub_so_remaining').innerHTML = data[1];
            }
         });
      }
      
      function add_sub(sms_objective_id,d,e) {
         ajax_feedback = _caf;
         if($('innereditsoedit')) {
            $('innereditsoedit').innerHTML = '';
         } else {
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
         orgjx_app_addSub(sms_objective_id,function(_data) {
            $('innereditsoedit').innerHTML = _data;
            editsobox.appear();
         });
      }
      
      function do_import_objectives() {
         ajax_feedback = _caf;
         orgjx_app_importObjectives(function(_data) {
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
                                           + '<div id=\"confirmdeploymsg\" style=\"padding:20px;text-align:center;\"></div>';
         
         confirmdeploybox = new GlassBox();
         confirmdeploybox.init('confirmdeploy','500px','205px','hidden','default',false,false);
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
         orgjx_app_deployObjectives('".$_SESSION["sms_org_id"]."',function(_data) {
            confirmdeploybox.fade();
         });
      }
      
      function add_initiative(sms_objective_id,d,e) {
         orgjx_app_editInitiative(sms_objective_id,function(_data) {
            $('innereditsoedit').innerHTML = _data;
         });
      }
      
      function set_so_origin(sms_objective_id,d,e) {
         orgjx_app_setSOOrigin(sms_objective_id,function(_data) {
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
      
      
      function save_kpi_share(val,sms_objective_id,sms_kpi_id,sms_share_org_id) {
         if(dveditshare) {
            if(!isNaN(val)&&val>0) {
               ///dveditshare.d.firstChild.innerHTML = parseFloat(val).toFixed(2);
               dveditshare.d.firstChild.style.color = '#33f';
            } else {
               dveditshare.d.firstChild.innerHTML = '-';
               val = 0;
               dveditshare.d.firstChild.style.color = '#333';
            }
         }
         
         orgjx_app_saveKPIShare(sms_objective_id,sms_kpi_id,sms_share_org_id,urlencode('sms_share_weight^^'+val),function(_data) {
            var data = recjsarray(_data);
            if(dveditshare&&dveditshare.d) {
               dveditshare.d.firstChild.innerHTML = parseFloat(data[2]).toFixed(2);
            }
            
            $('tdttlshared').innerHTML = data[5][0];
            
            for(var i=0;i<=data[5][1].length;i++) {
               if(data[5][1][i]) {
                  var td = $('tdttlkpishare_'+data[5][1][i][0]);
                  if(td) {
                     td.innerHTML = data[5][1][i][1];
                  }
               }
            }
            
            for(var i=0;i<=data[5][2].length;i++) {
               if(data[5][2][i]) {
                  var td = $('tdsubttlkpishare_'+data[5][2][i][0]+'_'+data[5][2][i][1]);
                  if(td) {
                     td.innerHTML = data[5][2][i][2];
                  }
               }
            }
            
            if(data[5][3]==0) {
               $('tdbalancewarning').innerHTML = '&nbsp;';
            } else if(data[5][3]>0) {
               $('tdbalancewarning').innerHTML = '<span style=\"color:red;\">Total objective weight is more than total shared.</span>';
            } else if(data[5][3]<0) {
               $('tdbalancewarning').innerHTML = '<span style=\"color:red;\">Total objective weight is less than total shared.</span>';
            }
            
            
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
            save_kpi_share(val,dveditshare.sms_objective_id,dveditshare.sms_kpi_id,dveditshare.sms_share_org_id);
            dveditshare.d = null;
            dveditshare = null;
         } else if (k==27) {
            _destroy(dveditshare);
            dveditshare.d = null;
            dveditshare = null;
         } else {
            d.chgt = new ctimer('save_kpi_share(\"'+val+'\",\"'+dveditshare.sms_objective_id+'\",\"'+dveditshare.sms_kpi_id+'\",\"'+dveditshare.sms_share_org_id+'\");',100);
            d.chgt.start();
         }
      }
      
      function get_all_remaining(sms_objective_id,sms_kpi_id,sms_share_org_id,d,e) {
         e.cancelBubble = true;
         orgjx_app_calcRemainingShare(sms_objective_id,sms_kpi_id,sms_share_org_id,function(_data) {
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
               save_kpi_share(data[4],dveditshare.sms_objective_id,dveditshare.sms_kpi_id,dveditshare.sms_share_org_id);
            }
         });
      }
      
      var dveditshare = null;
      function edit_kpi_share(sms_objective_id,sms_kpi_id,sms_share_org_id,d,e) {
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
                        + '<input type=\"hidden\" id=\"inp_kpi_share\" value=\"'+wv+'\"/>'
                        + 'Share : <input onkeyup=\"kp_kpi_share(this,event);\" id=\"new_inp_kpi_share\" onclick=\"event.cancelBubble=true;\" style=\"-moz-border-radius:3px;width:50px;text-align:center;\" type=\"text\" value=\"\"/>&nbsp;%'
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
         _dsa($('new_inp_kpi_share'));
         dveditshare = d.dv;
         dveditshare.d = d;
         dveditshare.sms_objective_id = sms_objective_id;
         dveditshare.sms_kpi_id = sms_kpi_id;
         dveditshare.sms_share_org_id = sms_share_org_id;
         setTimeout('document.body.onclick = function() { document.body.onclick = null; _destroy(dveditshare); };',100);
         orgjx_app_calcRemainingShare(sms_objective_id,sms_kpi_id,sms_share_org_id,function(_data) {
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
      function edit_kpi_share_old(sms_objective_id,sms_kpi_id,sms_share_org_id,d,e) {
         editkpishareedit = _dce('div');
         editkpishareedit.setAttribute('id','editkpishareedit');
         editkpishareedit = document.body.appendChild(editkpishareedit);
         editkpishareedit.sub = editkpishareedit.appendChild(_dce('div'));
         editkpishareedit.sub.setAttribute('id','innereditkpishareedit');
         editkpisharebox = new GlassBox();
         editkpisharebox.init('editkpishareedit','700px','270px','hidden','default',false,false);
         editkpisharebox.lbo(false,0.3);
         editkpisharebox.appear();
         
         orgjx_app_editKPIShare(sms_objective_id,sms_kpi_id,sms_share_org_id,function(_data) {
            $('innereditkpishareedit').innerHTML = _data;
            _dsa($('sms_share_weight'));
         });
         
      }
      
      
      function delete_kpi(sms_objective_id,sms_kpi_id,d,e) {
         orgjx_app_deleteKPI(sms_objective_id,sms_kpi_id,function(_data) {
            location.reload(true);
         });
      }
      
      function save_kpi(sms_objective_id,sms_kpi_id,d,e) {
         var ret = _parseForm('frmkpi');
         orgjx_app_saveKPI(ret,function(_data) {
            location.reload(true);
         });
      }
      
      var editkpiedit = null;
      var editkpibox = null;
      function edit_kpi(sms_objective_id,sms_kpi_id,d,e) {
         editkpiedit = _dce('div');
         editkpiedit.setAttribute('id','editkpiedit');
         editkpiedit = document.body.appendChild(editkpiedit);
         editkpiedit.sub = editkpiedit.appendChild(_dce('div'));
         editkpiedit.sub.setAttribute('id','innereditkpiedit');
         editkpibox = new GlassBox();
         editkpibox.init('editkpiedit','700px','370px','hidden','default',false,false);
         editkpibox.lbo(false,0.3);
         editkpibox.appear();
         
         orgjx_app_editKPI(sms_objective_id,sms_kpi_id,function(_data) {
            $('innereditkpiedit').innerHTML = _data;
            _dsa($('sms_kpi_text'));
         });
         
      }

      ///// Action Plan
      var editactionplanedit = null;
      var editactionplanbox = null;
      function edit_actionplan(sms_objective_id,sms_kpi_id,sms_action_plan_id,d,e) {
         editactionplanedit = _dce('div');
         editactionplanedit.setAttribute('id','editactionplanedit');
         editactionplanedit = document.body.appendChild(editactionplanedit);
         editactionplanedit.sub = editactionplanedit.appendChild(_dce('div'));
         editactionplanedit.sub.setAttribute('id','innereditactionplanedit');
         editactionplanbox = new GlassBox();
         editactionplanbox.init('editactionplanedit','700px','370px','hidden','default',false,false);
         editactionplanbox.lbo(false,0.3);
         editactionplanbox.appear();
         
         orgjx_app_editActionPlan(sms_objective_id,sms_kpi_id,sms_action_plan_id,function(_data) {
            $('innereditactionplanedit').innerHTML = _data;
            _dsa($('sms_action_plan_text'));
         });  
      }

      function delete_actionplan(sms_objective_id,sms_kpi_id,sms_action_plan_id,d,e) {
         orgjx_app_deleteActionPlan(sms_objective_id,sms_kpi_id,sms_action_plan_id,function(_data) {
            location.reload(true);
         });
      }
      
      function save_actionplan(sms_kpi_id,sms_action_plan_id,d,e) {
         var ret = _parseForm('frmactionplan');
         orgjx_app_saveActionPlan(ret,function(_data) {
            location.reload(true);
         });
      }

      
      function delete_share(sms_share_org_id,d,e) {
         orgjx_app_deleteShare(sms_share_org_id,function(_data) {
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
      function view_share(sms_share_org_id,d,e) {
         vshareedit = _dce('div');
         vshareedit.setAttribute('id','vshareedit');
         vshareedit = document.body.appendChild(vshareedit);
         vshareedit.sub = vshareedit.appendChild(_dce('div'));
         vshareedit.sub.setAttribute('id','innervshareedit');
         vsharebox = new GlassBox();
         vsharebox.init('vshareedit','600px','270px','hidden','default',false,false);
         vsharebox.lbo(false,0.3);
         vsharebox.appear();
         
         orgjx_app_viewShare(sms_share_org_id,function(_data) {
            $('innervshareedit').innerHTML = _data;
         });
         
      }
      
      function do_delete_so(sms_objective_id,d,e) {
         orgjx_app_deleteSO(sms_objective_id,function(_data) {
            location.reload(true);
         });
      }
      
      function cancel_delete_so() {
         $('innereditsoedit').innerHTML = $('innereditsoedit').oldHTML;
      }
      
      function delete_so(sms_objective_id,d,e) {
         $('innereditsoedit').oldHTML = $('innereditsoedit').innerHTML;
         if($('innereditsoedit')) {
            $('innereditsoedit').innerHTML = '<div style=\"height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;\">Delete Objective Confirmation</div>'
                                           + '<div id=\"confirmmsg\" style=\"padding:20px;text-align:center;\">Are you going to delete this objective?</div>'
                                           + '<div id=\"confirmmsg\" style=\"padding:12px;padding-top:5px;text-align:center;color:red;height:240px;\">Warning : All sub objectives, KPI, JAM and action plans will also be deleted.</div>'
                                           + '<div id=\"confirmbtn\" style=\"background-color:#eee;padding:10px;text-align:center;padding-bottom:30px;\">'
                                             + '<input type=\"button\" value=\"Yes (delete)\" onclick=\"do_delete_so(\\''+sms_objective_id+'\\');\"/>&nbsp;&nbsp;'
                                             + '<input type=\"button\" value=\"No\" onclick=\"cancel_delete_so();\"/>'
                                           + '</div>';
         }
      }
      
      
      
      function save_so(sms_objective_id,d,e) {
         var ret = _parseForm('frmobjective');
         orgjx_app_saveSO(ret,function(_data) {
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
         d.chgt = new ctimer('_setcode();',200);
         d.chgt.start();
      }
      
      function _setcode() {
         var no = $('sms_objective_no').value;
         var d = $('sms_perspective_id');
         var p = d.options[d.selectedIndex].value;
         var px = p.split('|');
         $('sms_obj_code').innerHTML = px[1]+no;
         _dsa($('sms_objective_no'));
      }
      
      function chgpers(d,e) {
         var p = d.options[d.selectedIndex].value;
         orgjx_app_getNo(p,function(_data) {
            var data = recjsarray(_data);
            $('sms_objective_no').value = data[0];
            var p = d.options[d.selectedIndex].value;
            var px = p.split('|');
            $('sms_obj_code').innerHTML = px[1]+data[0];
            _dsa($('sms_objective_no'));
         });
      }
      
      var editsoedit = null;
      var editsobox = null;
      function edit_so(sms_objective_id,d,e) {
         editsoedit = _dce('div');
         editsoedit.setAttribute('id','editsoedit');
         editsoedit = document.body.appendChild(editsoedit);
         editsoedit.sub = editsoedit.appendChild(_dce('div'));
         editsoedit.sub.setAttribute('id','innereditsoedit');
         editsobox = new GlassBox();
         editsobox.init('editsoedit','800px','430px','hidden','default',false,false);
         editsobox.lbo(false,0.3);
         editsobox.appear();
         
         orgjx_app_editSO(sms_objective_id,function(_data) {
            $('innereditsoedit').innerHTML = _data;
            setTimeout(\"_dsa($('sms_objective_no'))\",300);
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
            location.reload(true);
         });
      }

      ////// approval

      function save_propose(employee_id,employee_nm,d,e) {
         var ret = _parseForm('frmpropose');
         orgjx_app_savePropose(ret,function(_data) {
			var data = recjsarray(_data);
			alert(data[3]);
            window.setTimeout(function(){location.reload()},2000);
         });
          
      }

      function save_approval(employee_id,employee_nm,d,e) {
         var ret = _parseForm('frmapproval');
         orgjx_app_saveApproval(ret,function(_data) {
            window.setTimeout(function(){location.reload()},2000);
         });
      }

      ////// return 

        var wdv = null;
        function return_session(psid,d,e) {
           if(wdv) {
              if(wdv.psid == psid) {
                 cancel_return();
                 return;
              } else {
                 cancel_return();
              }
           }
       // alert('test');
      $('id_return').setAttribute('style','display:inline;')
           // wdv = _dce('div');
           // wdv.psid = psid;
           // var td = $('returnbtn');
           // wdv.setAttribute('style','padding:10px;');
           // wdv = td.appendChild(wdv);
          // wdv.appendChild(progress_span());
           // wdv.td = td;
           orgjx_app_returnSession(psid,function(_data) {
             // wdv.innerHTML = _data;
          $('id_return').innerHTML = _data;
             $('inp_year').focus();
           });
        }
      
      function save_return(id) {
           var ret = parseForm('frm');
           $('progress').appendChild(progress_span('&nbsp;...saving&nbsp;&nbsp;'));
           orgjx_app_saveReturn(id,ret,function(_data) {
              var data = recjsarray(_data);
              // alert(data[3]);
              // $('sp_'+data[0]).innerHTML = data[1];
              // $('inp_year').focus();
              setTimeout(\"$('progress').innerHTML = '';\",1000);
              $('id_return').setAttribute('style','display:none;');
              location.reload(true);
           });
        }
      
       function cancel_return() {
          // wdv.td.style.backgroundColor = '';
          // wdv.psid = null;
      $('id_return').setAttribute('style','display:none;');
           //_destroy($('id_return'));
           //wdv = null;
        }


      
      //]]></script>";
      
      return $js.$smssel.$orgsel.$ret;
   }
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $this->smsobjective();
            break;
         default:
            $ret = $this->smsobjective();
            break;
      }
      return $ret;
   }
}

} // SMS_OBJECTIVE_DEFINED
?>