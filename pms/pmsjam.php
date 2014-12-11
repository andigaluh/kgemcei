<?php
//--------------------------------------------------------------------//
// Filename : modules/pms/pmsjam.php                                  //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2010-09-22                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('PMS_JAM_DEFINED') ) {
   define('PMS_JAM_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
include_once(XOCP_DOC_ROOT."/modules/pms/class/ajax_objective.php");
require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
include_once(XOCP_DOC_ROOT."/modules/pms/class/selectpms.php");

class _pms_JAM extends XocpBlock {
   var $catchvar = _PMS_CATCH_VAR;
   var $blockID = _PMS_JAM_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _PMS_JAM_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _pms_JAM($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
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
              <tr><td id='hris_org_nm'>Level of Organization : <span style='font-weight:bold;'>$org_nm</span></td>
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
   
   
   function pmsjam($employee_id,$jam_org_ind,$jam_org_id) {
      $psid = $_SESSION["pms_psid"];
      $db=&Database::getInstance();
      $ajax = new _pms_class_ObjectiveAjax("orgjx");
      $user_id = getUserID();
      
      if($jam_org_ind==0) $jam_org_id = 0;
      
      $pmsselobj = new _pms_class_SelectSession();
      $pmssel = "<div style='padding-bottom:2px;'>".$pmsselobj->show()."</div>";
      
      $sql = "SELECT DISTINCT(approval_st) FROM pms_jam WHERE psid = '$psid' AND employee_id = '$employee_id'";
      $result = $db->query($sql);
      list($jam_status_cd)=$db->fetchRow($result);
      
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
      
      $jam_status_cd = "";
      $submit_dttm = "0000-00-00 00:00:00";
      $first_assessor_approved_dttm = "0000-00-00 00:00:00";
      $next_assessor_approved_dttm = "0000-00-00 00:00:00";
      
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
      
      if(!isset($_SESSION["pms_psid"])||$_SESSION["pms_psid"]==0) {
         return $pmssel;
      }
      
      $ret = "";
      
      $approval1_employee_idx = $approval2_employee_idx = 0;
      
      $sql = "SELECT approval1_employee_id,approval2_employee_id FROM pms_jam WHERE psid = '$psid' AND employee_id = '$employee_id' AND jam_org_ind = '$jam_org_ind' AND org_id = '$jam_org_id'";
      $result = $db->query($sql);
      $need_resubmit = 0;
      if($db->getRowsNum($result)>0) {
         while(list($approval1_employee_id,$approval2_employee_id)=$db->fetchRow($result)) {
            $approval1_employee_idx = $approval1_employee_id;
            $approval2_employee_idx = $approval2_employee_id;
            if($approval1_employee_id==0) {
               $need_resubmit++;
            }
            if($approval2_employee_id==0) {
               $need_resubmit++;
            }
         }
      }
      
      $sql = "SELECT approval_st,return_note FROM pms_jam WHERE psid = '$psid' AND employee_id = '$employee_id' AND jam_org_ind = '$jam_org_ind' AND org_id = '$jam_org_id'";
      $result = $db->query($sql);
      list($jam_status_cd,$return_note)=$db->fetchRow($result);
      if($jam_status_cd=="return") {
         $ret .= "<div style='margin-top:5px;background-color:#ffcccc;-moz-box-shadow:1px 1px 3px #000;border:1px solid #000;-moz-border-radius:5px;padding:5px;color:black;margin-bottom:10px;'><span style='font-weight:bold;'>Returned / Not Approved:</span><div style='white-space:pre-wrap;'>$return_note</div></div>";
      }
      
      $ret .= "<div style='max-width:700px;border:1px solid #bbb;-moz-border-radius:5px;padding:10px;margin-top:10px;'>";
      $ret .= "<div style=''>Percentage below each objective is the weight of the objective. Calculate weighed score for each rating. "
            . "Select the most appropriate statement for employee's performance in each area of contribution, "
            . "then input the weighed score and selected statement into \"Final Result\" column. "
            . "Total score is the sum of the percentage in \"Final Result\" column of each objective.</div>";
      $ret .= "<hr noshade='1' size='1'/>";
      $ret .= "<div style='font-style:italic;'>Persentase di bawah setiap objektif adalah bobot dari objektif tersebut. "
            . "Hitunglah nilai bobot untuk setiap penilaian dengan memilih pernyataan yang sesuai dengan kinerja karyawan "
            . "untuk masing-masing area kontribusi, kemudian masukkan nilai bobot dan pernyataan yang dipilih itu di "
            . "kolom \"Final Result\". Total nilai adalah jumlah persentase penilaian di kolom \"Final Result\" dari setiap objektif. </div>";
      $ret .= "</div>";
      
      $ret .= "<div>";
      
      $ret .= "<table style='width:100%;margin-top:10px;' class='xxlist'>"
            . "<colgroup>"
            . "<col width='30'/>"
            . "<col width='50'/>"
            . "<col width='*'/>"
            . "<col width='125'/>"
            . "<col width='125'/>"
            . "<col width='125'/>"
            . "<col width='125'/>"
            . "<col width='125'/>"
            //. "<col width='125'/>"
            . "</colgroup>";
      
      $ret .= "<thead>"
            . "<tr>"
               . "<td style='border-right:1px solid #bbb;text-align:center;' colspan='3'>Objectives</td>"
               . "<td></td>"
               . "<td></td>"
               . "<td></td>"
               . "<td></td>"
               . "<td style='border-right:1px solid #bbb;'></td>"
               //. "<td style='text-align:center;' rowspan='3'>Final Result</td>"
            . "</tr>"
            . "<tr>"
               . "<td style='border-right:1px solid #bbb;text-align:center;' rowspan='2'>No.</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;' rowspan='2'>ID</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;' rowspan='2'>Refer to Objective and Target Form</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;'>&lt;59%</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;'>69%</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;'>79%</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;'>89%</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;'>100%</td>"
            . "</tr>"
            . "<tr>"
               . "<td style='border-right:1px solid #bbb;text-align:center;'>Lower performer</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;'>Still need improvement</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;'>Fulfill standard of work performance</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;'>Exceed required performance</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;'>Far exceed required performance</td>"
            . "</tr>"
            . "</thead>";
      
      $ret .= "<tbody>";
      
      if($jam_org_ind==1) {
         $org_id = $jam_org_id;
         $sql = "SELECT 'pms_actionplan_share',b.pms_objective_id,b.pms_objective_weight,b.pms_objective_text,1,1,b.pms_org_id,c.pms_org_id"
              . " FROM pms_objective b"
              . " LEFT JOIN pms_objective c ON c.pms_objective_id = b.pms_parent_objective_id"
              . " WHERE b.psid = '$psid' AND b.pms_org_id = '$jam_org_id'"
              . " AND b.pms_objective_id IS NOT NULL"
              . " ORDER BY b.pms_perspective_id,b.pms_objective_no";
         $result = $db->query($sql);
         $total_weight = 0;
         $arr_share = array();
         $objective_share_arr = array();
         if($db->getRowsNum($result)>0) {
            while(list($x,$pms_objective_id,$pms_share_weight,$pms_objective_text,$pms_actionplan_id,$ck_share,$obj_org_id,$parent_obj_org_id)=$db->fetchRow($result)) {
               if($parent_obj_org_id==$obj_org_id) {
                  $sql = "DELETE FROM pms_jam WHERE psid = '$psid' AND pms_objective_id = '$pms_objective_id' AND employee_id = '$employee_id'";
                  $db->query($sql);
                  continue;
               }
               if($ck_share=="") {
                  $sql = "DELETE FROM pms_jam WHERE psid = '$psid' AND pms_objective_id = '$pms_objective_id' AND employee_id = '$employee_id'";
                  $db->query($sql);
               }
               $total_weight = _bctrim(bcadd($total_weight,$pms_share_weight));
               $objective_share_arr[$pms_objective_id] = _bctrim(bcadd($objective_share_arr[$pms_objective_id],$pms_share_weight));
               $arr_share[$pms_objective_id] = array($pms_objective_id,$pms_share_weight,$pms_objective_text,$pms_actionplan_id);
            }
            
            foreach($arr_share as $pms_objective_id=>$v) {
               list($pms_objective_idx,$pms_share_weight,$pms_objective_text)=$v;
               $sql = "SELECT * FROM pms_jam WHERE psid = '$psid' AND employee_id = '$employee_id' AND pms_objective_id = '$pms_objective_id'";
               $rjam = $db->query($sql);
               if($db->getRowsNum($rjam)==0) {
                  $objective_weight = _bctrim(bcmul(bcdiv($objective_share_arr[$pms_objective_id],$total_weight),100));
                  $sql = "INSERT INTO pms_jam (psid,pms_objective_id,employee_id,objective_weight,jam_org_ind,org_id) VALUES ('$psid','$pms_objective_id','$employee_id','$objective_weight','1','$jam_org_id')";
                  $db->query($sql);
               } else {
                  $objective_weight = _bctrim(bcmul(bcdiv($objective_share_arr[$pms_objective_id],$total_weight),100));
                  $sql = "UPDATE pms_jam SET objective_weight = '$objective_weight' WHERE psid = '$psid' AND pms_objective_id = '$pms_objective_id' AND employee_id = '$employee_id'";
                  $db->query($sql);
               }
            }
         }
      } else {
         $sql = "SELECT 'pms_actionplan_share',a.pms_objective_id,SUM(a.pms_share_weight),b.pms_objective_text,a.pms_actionplan_id,pa.pms_actionplan_id"
              . " FROM pms_actionplan_share a"
              . " LEFT JOIN pms_actionplan pa USING(psid,pms_objective_id,pms_actionplan_id)"
              . " LEFT JOIN pms_objective b USING(psid,pms_objective_id)"
              . " WHERE a.psid = '$psid' AND a.pms_actionplan_pic_employee_id = '$employee_id'"
              . " AND b.pms_objective_id IS NOT NULL"
              . " GROUP BY a.pms_objective_id"
              . " ORDER BY b.pms_perspective_id,b.pms_objective_no";
         $result = $db->query($sql);
         $total_weight = 0;
         $arr_share = array();
         $objective_share_arr = array();
         if($db->getRowsNum($result)>0) {
            while(list($x,$pms_objective_id,$pms_share_weight,$pms_objective_text,$pms_actionplan_id,$ck_share)=$db->fetchRow($result)) {
               if($ck_share=="") {
                  $sql = "DELETE FROM pms_jam WHERE psid = '$psid' AND pms_objective_id = '$pms_objective_id' AND employee_id = '$employee_id'";
                  $db->query($sql);
                  continue;
               }
               $total_weight = _bctrim(bcadd($total_weight,$pms_share_weight));
               $objective_share_arr[$pms_objective_id] = _bctrim(bcadd($objective_share_arr[$pms_objective_id],$pms_share_weight));
               $arr_share[$pms_objective_id] = array($pms_objective_id,$pms_share_weight,$pms_objective_text,$pms_actionplan_id);
            }
            
            foreach($arr_share as $pms_objective_id=>$v) {
               list($pms_objective_idx,$pms_share_weight,$pms_objective_text)=$v;
               $sql = "SELECT * FROM pms_jam WHERE psid = '$psid' AND employee_id = '$employee_id' AND pms_objective_id = '$pms_objective_id'";
               $rjam = $db->query($sql);
               if($db->getRowsNum($rjam)==0) {
                  $objective_weight = _bctrim(bcmul(bcdiv($objective_share_arr[$pms_objective_id],$total_weight),100));
                  $sql = "INSERT INTO pms_jam (psid,pms_objective_id,employee_id,objective_weight,jam_org_ind) VALUES ('$psid','$pms_objective_id','$employee_id','$objective_weight','0')";
                  $db->query($sql);
               } else {
                  $objective_weight = _bctrim(bcmul(bcdiv($objective_share_arr[$pms_objective_id],$total_weight),100));
                  $sql = "UPDATE pms_jam SET objective_weight = '$objective_weight' WHERE psid = '$psid' AND pms_objective_id = '$pms_objective_id' AND employee_id = '$employee_id'";
                  $db->query($sql);
               }
            }
         }
      }
      
      $sql = "SELECT b.pms_objective_no,c.pms_perspective_code,b.pms_org_id,a.pms_objective_id,b.pms_objective_text,a.objective_weight,"
           . "a.target_text0,a.target_text1,a.target_text2,a.target_text3,a.target_text4,a.final_result_text,"
           . "a.target_weight0,a.target_weight1,a.target_weight2,a.target_weight3,a.target_weight4,a.final_result_weight,"
           . "a.approval1_dttm,a.approval2_dttm,a.approval_st,a.submit_dttm"
           . " FROM pms_jam a"
           . " LEFT JOIN pms_objective b USING(psid,pms_objective_id)"
           . " LEFT JOIN pms_perspective c USING(psid,pms_perspective_id)"
           . " WHERE a.psid = '$psid' AND a.employee_id = '$employee_id'"
           . " AND b.pms_objective_id IS NOT NULL"
           . ($jam_org_ind==1?" AND a.jam_org_ind = '1' AND a.org_id = '$jam_org_id'":" AND a.jam_org_ind = '0'")
           . " ORDER BY b.pms_perspective_id,b.pms_objective_no";
      $result = $db->query($sql);
      $ttl_weight = 0;
      $ttl_target_weight0 = 0;
      $ttl_target_weight1 = 0;
      $ttl_target_weight2 = 0;
      $ttl_target_weight3 = 0;
      $ttl_target_weight4 = 0;
      $all_jam_status = "";
      if($db->getRowsNum($result)>0) {
         $no = 0;
         while(list($pms_objective_no,$pms_perspective_code,$pms_org_id,$pms_objective_id,$pms_objective_text,$objective_weight,
                    $target_text0,$target_text1,$target_text2,$target_text3,$target_text4,$final_result_text,
                    $target_weight0,$target_weight1,$target_weight2,$target_weight3,$target_weight4,$final_result_weight,
                    $approval1_dttm,$approval2_dttm,$approval_st,$submit_dttmx)=$db->fetchRow($result)) {
            
            if(!isset($arr_share[$pms_objective_id])) {
               if($jam_org_ind==1) {
                  $org_id = $jam_org_id;
                  $qdel = " AND jam_org_ind = '1' AND org_id = '$jam_org_id'";
               } else {
                  $qdel = " AND jam_org_ind = '0'";
               }
               $sql = "DELETE FROM pms_jam WHERE psid = '$psid' AND pms_objective_id = '$pms_objective_id' AND employee_id = '$employee_id'".$qdel;
               $db->query($sql);
               continue;
            }
            
            if($all_jam_status=="new") {
               /// do nothing, this is the least
            } else {
               if($all_jam_status=="return") {
                  if($approval_st=="new") {
                     $all_jam_status = "new";
                  }
               } else {
                  if($all_jam_status=="approval1") {
                     if($approval_st=="return") $all_jam_status = "return";
                     if($approval_st=="new") $all_jam_status = "new";
                  } else {
                     if($all_jam_status=="approval2") {
                        if($approval_st=="approval1") $all_jam_status = "approval1";
                        if($approval_st=="return") $all_jam_status = "return";
                        if($approval_st=="new") $all_jam_status = "new";
                     } else {
                        $all_jam_status = $approval_st;
                     }
                  }
               }
            }
            
            $top_level_org_id = $this->recurseParentOrg($pms_objective_id);
            
            $no++;
            
            $sql = "SELECT b.pms_actionplan_text FROM pms_actionplan_share a"
                 . " LEFT JOIN pms_actionplan b USING(pms_objective_id,pms_actionplan_id)"
                 . " WHERE a.psid = '$psid' AND a.pms_objective_id = '$pms_objective_id'"
                 . " AND a.pms_actionplan_pic_employee_id = '$employee_id'";
            $rap = $db->query($sql);
            $apdiv = "<div style='padding-left:20px;font-size:0.9em;'>";
            if($db->getRowsNum($rap)>0) {
               while(list($pms_actionplan_text)=$db->fetchRow($rap)) {
                  $apdiv .= "<div>$pms_actionplan_text</div>";
               }
            }
            $apdiv .= "</div>";
            
            $jam_status_cd = $approval_st;
            $submit_dttm = $submit_dttmx;
            $first_assessor_approved_dttm = $approval1_dttm;
            $next_assessor_approved_dttm = $approval2_dttm;
            
            if(trim($target_text0)=="") $target_text0 = _EMPTY;
            if(trim($target_text1)=="") $target_text1 = _EMPTY;
            if(trim($target_text2)=="") $target_text2 = _EMPTY;
            if(trim($target_text3)=="") $target_text3 = _EMPTY;
            if(trim($target_text4)=="") $target_text4 = _EMPTY;
            if(trim($final_result_text)=="") $final_result_text = _EMPTY;
            
            $target_weight0 = bcdiv(bcmul(59,$objective_weight),100);
            $target_weight1 = bcdiv(bcmul(69,$objective_weight),100);
            $target_weight2 = bcdiv(bcmul(79,$objective_weight),100);
            $target_weight3 = bcdiv(bcmul(89,$objective_weight),100);
            $target_weight4 = bcdiv(bcmul(100,$objective_weight),100);
            $final_result_weight += 0;
            $ret .= "<tr height='75'>";
            $ret .= "<td style='vertical-align:top;border-right:1px solid #bbb;text-align:center;' rowspan='2'>$no</td>";
            $ret .= "<td style='vertical-align:top;border-right:1px solid #bbb;text-align:center;".($top_level_org_id==0?"color:blue;":"")."font-weight:bold;' rowspan='2'>${pms_perspective_code}${pms_objective_no}</td>";
            $ret .= "<td style='vertical-align:top;border-right:1px solid #bbb;'><span style='color:black;font-weight:bold;'>$pms_objective_text</span>${apdiv}</td>";
            $ret .= "<td style='white-space:pre-wrap;vertical-align:top;border-right:1px solid #bbb;'>".(($jam_status_cd=="new"||$jam_status_cd=="return")&&$self_employee_id==$employee_id?"<span class='xlnk' onclick='edit_target_text(\"$pms_objective_id\",0,this,event);'>".($target_text0)."</span>":$target_text0)."</td>";
            $ret .= "<td style='white-space:pre-wrap;vertical-align:top;border-right:1px solid #bbb;'>".(($jam_status_cd=="new"||$jam_status_cd=="return")&&$self_employee_id==$employee_id?"<span class='xlnk' onclick='edit_target_text(\"$pms_objective_id\",1,this,event);'>".($target_text1)."</span>":$target_text1)."</td>";
            $ret .= "<td style='white-space:pre-wrap;vertical-align:top;border-right:1px solid #bbb;'>".(($jam_status_cd=="new"||$jam_status_cd=="return")&&$self_employee_id==$employee_id?"<span class='xlnk' onclick='edit_target_text(\"$pms_objective_id\",2,this,event);'>".($target_text2)."</span>":$target_text2)."</td>";
            $ret .= "<td style='white-space:pre-wrap;vertical-align:top;border-right:1px solid #bbb;'>".(($jam_status_cd=="new"||$jam_status_cd=="return")&&$self_employee_id==$employee_id?"<span class='xlnk' onclick='edit_target_text(\"$pms_objective_id\",3,this,event);'>".($target_text3)."</span>":$target_text3)."</td>";
            $ret .= "<td style='white-space:pre-wrap;vertical-align:top;border-right:1px solid #bbb;'>".(($jam_status_cd=="new"||$jam_status_cd=="return")&&$self_employee_id==$employee_id?"<span class='xlnk' onclick='edit_target_text(\"$pms_objective_id\",4,this,event);'>".($target_text4)."</span>":$target_text4)."</td>";
            //$ret .= "<td>".(($jam_status_cd=="new"||$jam_status_cd=="return")&&$self_employee_id==$employee_id?"<span class='xlnk' onclick='edit_target_text(\"$pms_objective_id\",5,this,event);'>$final_result_text</span>":$final_result_text)."</td>";
            $ret .= "</tr>";
            $ret .= "<tr>";
            $ret .= "<td style='border-right:1px solid #bbb;text-align:right;'>".toMoney(_bctrim($objective_weight))." %</td>";
            $ret .= "<td style='border-right:1px solid #bbb;text-align:right;'>".toMoney($target_weight0)." %</td>";
            $ret .= "<td style='border-right:1px solid #bbb;text-align:right;'>".toMoney($target_weight1)." %</td>";
            $ret .= "<td style='border-right:1px solid #bbb;text-align:right;'>".toMoney($target_weight2)." %</td>";
            $ret .= "<td style='border-right:1px solid #bbb;text-align:right;'>".toMoney($target_weight3)." %</td>";
            $ret .= "<td style='border-right:1px solid #bbb;text-align:right;'>".toMoney($target_weight4)." %</td>";
            //$ret .= "<td style='text-align:right;'>".(($jam_status_cd=="new"||$jam_status_cd=="return")&&$self_employee_id==$employee_id?"<span class='xlnk' onclick='edit_target_weight(\"$pms_objective_id\",5,this,event);'>".toMoney($final_result_weight)." %</span>":toMoney($final_result_weight)." %")."</td>";
            $ret .= "</tr>";
            $ttl_target_weight0 = _bctrim(bcadd($ttl_target_weight0,$target_weight0));
            $ttl_target_weight1 = _bctrim(bcadd($ttl_target_weight1,$target_weight1));
            $ttl_target_weight2 = _bctrim(bcadd($ttl_target_weight2,$target_weight2));
            $ttl_target_weight3 = _bctrim(bcadd($ttl_target_weight3,$target_weight3));
            $ttl_target_weight4 = _bctrim(bcadd($ttl_target_weight4,$target_weight4));
         }
      } else {
         $ret .= "<tr><td colspan='9' style='text-align:center;font-style:italic;'>"._EMPTY."</td></tr>";
      }
      
      $ret .= "<tr>";
      $ret .= "<td style='border-top:1px solid #bbb;background-color:#eee;color:black;border-right:1px solid #bbb;text-align:center;font-weight:bold;' colspan='2'>Total</td>";
      $ret .= "<td style='border-top:1px solid #bbb;background-color:#eee;color:black;border-right:1px solid #bbb;text-align:right;font-weight:bold;'>100 %</td>";
      $ret .= "<td style='border-top:1px solid #bbb;background-color:#eee;color:black;border-right:1px solid #bbb;text-align:right;font-weight:bold;'>".toMoney($ttl_target_weight0)." %</td>";
      $ret .= "<td style='border-top:1px solid #bbb;background-color:#eee;color:black;border-right:1px solid #bbb;text-align:right;font-weight:bold;'>".toMoney($ttl_target_weight1)." %</td>";
      $ret .= "<td style='border-top:1px solid #bbb;background-color:#eee;color:black;border-right:1px solid #bbb;text-align:right;font-weight:bold;'>".toMoney($ttl_target_weight2)." %</td>";
      $ret .= "<td style='border-top:1px solid #bbb;background-color:#eee;color:black;border-right:1px solid #bbb;text-align:right;font-weight:bold;'>".toMoney($ttl_target_weight3)." %</td>";
      $ret .= "<td style='border-top:1px solid #bbb;background-color:#eee;color:black;border-right:1px solid #bbb;text-align:right;font-weight:bold;'>".toMoney($ttl_target_weight4)." %</td>";
      //$ret .= "<td style='border-top:1px solid #bbb;background-color:#eee;color:black;text-align:right;font-weight:bold;'>".(($jam_status_cd=="new"||$jam_status_cd=="return")&&$self_employee_id==$employee_id?"<span class='xlnk' onclick='edit_target_weight(\"$pms_objective_id\",5,this,event);'>".toMoney($final_result_weight)." %</span>":toMoney($final_result_weight)." %")."</td>";
      $ret .= "</tr>";
      
      
      $ret .= "</tbody>";
      $ret .= "</table>";
      $ret .= "</div>";
      
      $doubleapproval = 1;
      
      $sql = "SELECT c.job_nm,c.job_abbr,d.org_nm,d.org_abbr,a.employee_ext_id,e.person_nm,e.person_id"
           . " FROM ".XOCP_PREFIX."employee a"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job b ON b.employee_id = a.employee_id AND b.job_id = '$job_id'"
           . " LEFT JOIN ".XOCP_PREFIX."jobs c ON c.job_id = '$job_id'"
           . " LEFT JOIN ".XOCP_PREFIX."orgs d ON d.org_id = c.org_id"
           . " LEFT JOIN ".XOCP_PREFIX."persons e ON e.person_id = a.person_id"
           . " WHERE a.employee_id = '$employee_id'";
      $result = $db->query($sql);
      list($job_nm,$job_abbr,$org_nm,$org_abbr,$nip,$employee_nm,$person_id)=$db->fetchRow($result);
      
      $sql = "SELECT a.job_nm,b.employee_ext_id,c.person_nm,b.employee_id"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job e USING(job_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " WHERE a.job_id = '$first_assessor_job_id'"
           . " ORDER BY e.start_dttm DESC LIMIT 1";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($first_superior_job,$nip,$first_superior_name,$first_assessor_employee_id)=$db->fetchRow($result);
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
         list($next_superior_job,$nip,$next_superior_name,$next_assessor_employee_id)=$db->fetchRow($result);
      }
      
      if($need_resubmit>0) {
         $all_jam_status = "new";
      }
      
      list($assessor_job_id,$assessor_employee_id)=_getFirstAssessor($employee_id,$job_id);
      list($next_assessor_job_id,$next_assessor_employee_id)=_getNextAssessor($employee_id,$job_id);
      
      $form .= "<div style='text-align:right;padding:10px;margin-top:20px;'>"
             //. $all_jam_status . $employee_id
             . "<table align='center' style='border-top:2px solid #777;border-left:2px solid #777;border-spacing:0px;'>"
             . "<colgroup>"
             . "<col width='200'/>"
             . "<col width='200'/>"
             . "<col width='200'/>"
             . "</colgroup>"
             . "<tbody>"
             . "<tr>"
             . "<td style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;'>"
             . "Submited by,"
             . "</td>"
             . "<td ".($doubleapproval==1?"colspan='2'":"")." style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;'>"
             . "Approved by,"
             . "</td>"
             . "</tr>"
             . "<tr>"
             . "<td style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
             . "$employee_nm"
             . "</td>"
             . "<td style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
             . "$first_superior_name"
             . "</td>"
             
             . ($doubleapproval==1?""
             . "<td style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
             . "$next_superior_name"
             . "</td>"
             . "":"")
             
             . "</tr>"
             . "<tr>"
             . "<td style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
             . "Employee, PIC"
             . "</td>"
             . "<td style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
             . "$first_superior_job"
             . "</td>"
             
             . ($doubleapproval==1?""
             . "<td style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
             . "$next_superior_job"
             . "</td>"
             . "":"")
             
             . "</tr>"
             . "<tr>"
             . "<td style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;padding:10px;'>"
             . (($all_jam_status=="new"||$all_jam_status=="return")&&$self_job_id==$job_id?"<input type='button' value='Submit' onclick='confirm_submit(this,event);'/>":"")
             . (!($all_jam_status==""||$all_jam_status=="new"||$all_jam_status=="return")?"Submited at:<br/>".sql2ind($submit_dttm,"date"):"")
             . (($all_jam_status=="new"||$all_jam_status=="return")&&$self_job_id!=$job_id?"Preparation":"")
             . "</td>"
             . "<td style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;padding:10px;'>"
             . ($all_jam_status=="new"||$all_jam_status=="return"?"-":"")
             . ($all_jam_status=="approval1"&&$self_job_id==$first_assessor_job_id?"<input type='button' value='Approve' onclick='confirm_approval1(this,event);'/>&nbsp;<input type='button' value='Not Approve' onclick='first_assessor_return_JAM(\"$employee_id\");'/>":"")
             //. ($all_jam_status=="approval1"&&$self_job_id==$first_assessor_job_id?"&nbsp;<input type='button' value='Return' onclick='confirm_return1(this,event);'/>":"")
             . ($all_jam_status=="approval1"&&$self_job_id!=$first_assessor_job_id?"Waiting for approval":"")
             . ($all_jam_status=="approval2"?"Approved at:<br/>".sql2ind($first_assessor_approved_dttm,"date"):"")
             . ($all_jam_status=="implementation"?"Approved at:<br/>".sql2ind($first_assessor_approved_dttm,"date"):"")
             . "</td>"
             
             . ($doubleapproval==1?""
             . "<td style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;padding:10px;'>"
             . ($all_jam_status=="new"||$all_jam_status=="return"?"-":"")
             . ($all_jam_status=="approval2"&&$self_employee_id==$approval2_employee_idx?"<input type='button' value='Approve' onclick='confirm_approval2(this,event);'/>&nbsp;<input type='button' value='Not Approve' onclick='next_assessor_return_JAM(\"$employee_id\");'/>":"")
             . ($all_jam_status=="approval2"&&$self_employee_id!=$approval2_employee_idx?"Waiting for approval":"")
             . ($all_jam_status=="approval1"&&$self_employee_id!=$approval2_employee_idx?"-":"")
             . ($all_jam_status=="approval1"&&$self_employee_id==$approval2_employee_idx?"-":"")
             . ($all_jam_status=="implementation"?"Approved at:<br/>".sql2ind($next_assessor_approved_dttm,"date"):"")
             . "</td>"
             . "":"")
             
             . "</tr>"
           . "</tbody>"
           . "</table>"
           . "</div>";
             
      $ret .= $form."<div style='padding:20px;'>&#160;</div>";
      
      $js = $ajax->getJs()."<script type='text/javascript'>//<![CDATA[
      
      //////////////////////////////////////////////////////////////////////////////
      
      function do_approval2(employee_id) {
         $('confirmbtn').innerHTML = '';
         $('confirmbtn').appendChild(progress_span(' ... approve JAM'));
         orgjx_app_approval2JAM('$employee_id','$jam_org_ind','$jam_org_id',function(_data) {
            confirmapproval2box.fade();
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?goto=y&jam_org_ind=${jam_org_ind}&jam_org_id=${jam_org_id}&employee_id=${employee_id}&r='+uniqid('r');
         });
      }
      
      
      function confirm_next_assessor_return_JAM(employee_id) {
         var return_note = urlencode($('return_note').value);
         orgjx_app_nextAssessorReturnJAM('$employee_id','$jam_org_ind','$jam_org_id',return_note,function(_data) {
            var data = recjsarray(_data);
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?goto=y&jam_org_ind=${jam_org_ind}&jam_org_id=${jam_org_id}&employee_id=${employee_id}&r='+uniqid('r');
         });
      }
      
      
      var nextassessorreturnedit = null;
      var nextassessorreturnbox = null;
      function next_assessor_return_JAM(employee_id) {
         nextassessorreturnedit = _dce('div');
         nextassessorreturnedit.setAttribute('id','nextassessorreturnedit');
         nextassessorreturnedit = document.body.appendChild(nextassessorreturnedit);
         nextassessorreturnedit.sub = nextassessorreturnedit.appendChild(_dce('div'));
         nextassessorreturnedit.sub.setAttribute('id','innernextassessorreturnedit');
         nextassessorreturnbox = new GlassBox();
         nextassessorreturnbox.init('nextassessorreturnedit','600px','350px','hidden','default',false,false);
         nextassessorreturnbox.lbo(false,0.3);
         nextassessorreturnbox.appear();
         
         var xreturn_note = '';
         if($('xreturn_note')) {
            xreturn_note = $('xreturn_note').innerHTML;
         }
         
         $('innernextassessorreturnedit').innerHTML = '<div style=\"height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;\">Not Approve JAM Confirmation</div>'
                                   + '<div style=\"background-color:#ffffff;font-weight:bold;font-size:1.1em;height:300px;text-align:center;padding-top:20px;\">'
                                   + 'You do not approve this JAM.<br/>You are going to return this JAM to the employee.'
                                   + '<br/><br/>With the following notes:<br/>'
                                   + '<textarea id=\"return_note\" style=\"width:400px;height:100px;\">'+xreturn_note+'</textarea>'
                                   + '<br/><br/>Are you sure?'
                                   + '<br/><br/>'
                                           + '<div id=\"confirmbtn\" style=\"background-color:#eee;padding:10px;text-align:center;\">'
                                   + '<input type=\"button\" value=\"Yes (Not Approve)\" onclick=\"confirm_next_assessor_return_JAM(\\''+employee_id+'\\');\"/>&nbsp;'
                                   + '<input type=\"button\" value=\"No (Cancel)\" onclick=\"nextassessorreturnbox.fade();\"/>'
                                   + '</div>'
                                   + '</div>';
         setTimeout('$(\"return_note\").focus();',200);
      }
      
      
      var confirmapproval2 = null;
      var confirmapproval2box = null;
      function confirm_approval2(d,e) {
         confirmapproval2 = _dce('div');
         confirmapproval2.setAttribute('id','confirmapproval2');
         confirmapproval2 = document.body.appendChild(confirmapproval2);
         confirmapproval2.sub = confirmapproval2.appendChild(_dce('div'));
         confirmapproval2.sub.setAttribute('id','innerconfirmapproval2');
         confirmapproval2box = new GlassBox();
         $('innerconfirmapproval2').innerHTML = '<div style=\"height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;\">Approve JAM Confirmation</div>'
                                           + '<div id=\"confirmmsg\" style=\"padding:20px;text-align:center;\">Are you going to approve this JAM?</div>'
                                           + '<div id=\"confirmbtn\" style=\"background-color:#eee;padding:10px;text-align:center;\">'
                                             + '<input type=\"button\" value=\"Yes (Approve)\" onclick=\"do_approval2();\"/>&nbsp;&nbsp;'
                                             + '<input type=\"button\" value=\"No (Cancel)\" onclick=\"confirmapproval2box.fade();\"/>'
                                           + '</div>';
         
         
         confirmapproval2box = new GlassBox();
         confirmapproval2box.init('confirmapproval2','500px','165px','hidden','default',false,false);
         confirmapproval2box.lbo(false,0.3);
         confirmapproval2box.appear();
      }
      
      //////////////////////////////////////////////////////////////
      
      
      //////////////////////////////////////////////////////////////////////////////
      
      function do_approval1(employee_id) {
         $('confirmbtn').innerHTML = '';
         $('confirmbtn').appendChild(progress_span(' ... approve JAM'));
         orgjx_app_approval1JAM('$employee_id','$jam_org_ind','$jam_org_id',function(_data) {
            confirmapproval1box.fade();
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?goto=y&jam_org_ind=${jam_org_ind}&jam_org_id=${jam_org_id}&employee_id=${employee_id}&r='+uniqid('r');
         });
      }
      
      
      function confirm_first_assessor_return_JAM(employee_id) {
         var return_note = urlencode($('return_note').value);
         orgjx_app_firstAssessorReturnJAM('$employee_id','$jam_org_ind','$jam_org_id',return_note,function(_data) {
            var data = recjsarray(_data);
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?goto=y&jam_org_ind=${jam_org_ind}&jam_org_id=${jam_org_id}&employee_id=${employee_id}&r='+uniqid('r');
         });
      }
      
      
      var firstassessorreturnedit = null;
      var firstassessorreturnbox = null;
      function first_assessor_return_JAM(employee_id) {
         firstassessorreturnedit = _dce('div');
         firstassessorreturnedit.setAttribute('id','firstassessorreturnedit');
         firstassessorreturnedit = document.body.appendChild(firstassessorreturnedit);
         firstassessorreturnedit.sub = firstassessorreturnedit.appendChild(_dce('div'));
         firstassessorreturnedit.sub.setAttribute('id','innerfirstassessorreturnedit');
         firstassessorreturnbox = new GlassBox();
         firstassessorreturnbox.init('firstassessorreturnedit','600px','350px','hidden','default',false,false);
         firstassessorreturnbox.lbo(false,0.3);
         firstassessorreturnbox.appear();
         
         var xreturn_note = '';
         if($('xreturn_note')) {
            xreturn_note = $('xreturn_note').innerHTML;
         }
         
         $('innerfirstassessorreturnedit').innerHTML = '<div style=\"height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;\">Not Approve JAM Confirmation</div>'
                                   + '<div style=\"background-color:#ffffff;font-weight:bold;font-size:1.1em;height:300px;text-align:center;padding-top:20px;\">'
                                   + 'You do not approve this JAM.<br/>You are going to return this JAM to the employee.'
                                   + '<br/><br/>With the following notes:<br/>'
                                   + '<textarea id=\"return_note\" style=\"width:400px;height:100px;\">'+xreturn_note+'</textarea>'
                                   + '<br/><br/>Are you sure?'
                                   + '<br/><br/>'
                                           + '<div id=\"confirmbtn\" style=\"background-color:#eee;padding:10px;text-align:center;\">'
                                   + '<input type=\"button\" value=\"Yes (Not Approve)\" onclick=\"confirm_first_assessor_return_JAM(\\''+employee_id+'\\');\"/>&nbsp;'
                                   + '<input type=\"button\" value=\"No (Cancel)\" onclick=\"firstassessorreturnbox.fade();\"/>'
                                   + '</div>'
                                   + '</div>';
         setTimeout('$(\"return_note\").focus();',200);
      }
      
      
      var confirmapproval1 = null;
      var confirmapproval1box = null;
      function confirm_approval1(d,e) {
         confirmapproval1 = _dce('div');
         confirmapproval1.setAttribute('id','confirmapproval1');
         confirmapproval1 = document.body.appendChild(confirmapproval1);
         confirmapproval1.sub = confirmapproval1.appendChild(_dce('div'));
         confirmapproval1.sub.setAttribute('id','innerconfirmapproval1');
         confirmapproval1box = new GlassBox();
         $('innerconfirmapproval1').innerHTML = '<div style=\"height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;\">Approve JAM Confirmation</div>'
                                           + '<div id=\"confirmmsg\" style=\"padding:20px;text-align:center;\">Are you going to approve this JAM?</div>'
                                           + '<div id=\"confirmbtn\" style=\"background-color:#eee;padding:10px;text-align:center;\">'
                                             + '<input type=\"button\" value=\"Yes (Approve)\" onclick=\"do_approval1();\"/>&nbsp;&nbsp;'
                                             + '<input type=\"button\" value=\"No (Cancel)\" onclick=\"confirmapproval1box.fade();\"/>'
                                           + '</div>';
         
         
         confirmapproval1box = new GlassBox();
         confirmapproval1box.init('confirmapproval1','500px','165px','hidden','default',false,false);
         confirmapproval1box.lbo(false,0.3);
         confirmapproval1box.appear();
      }
      
      //////////////////////////////////////////////////////////////
      
      function do_submit(employee_id) {
         $('confirmbtn').innerHTML = '';
         $('confirmbtn').appendChild(progress_span(' ... submiting JAM'));
         orgjx_app_submitJAM('$employee_id',function(_data) {
            confirmsubmitbox.fade();
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?r='+uniqid('r');
         });
      }
      
      var confirmsubmit = null;
      var confirmsubmitbox = null;
      function confirm_submit(d,e) {
         confirmsubmit = _dce('div');
         confirmsubmit.setAttribute('id','confirmsubmit');
         confirmsubmit = document.body.appendChild(confirmsubmit);
         confirmsubmit.sub = confirmsubmit.appendChild(_dce('div'));
         confirmsubmit.sub.setAttribute('id','innerconfirmsubmit');
         confirmsubmitbox = new GlassBox();
         $('innerconfirmsubmit').innerHTML = '<div style=\"height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;\">Submit JAM Confirmation</div>'
                                           + '<div id=\"confirmmsg\" style=\"padding:20px;text-align:center;\">Are you going to submit this JAM proposal?</div>'
                                           + '<div id=\"confirmbtn\" style=\"background-color:#eee;padding:10px;text-align:center;\">'
                                             + '<input type=\"button\" value=\"Yes (Submit)\" onclick=\"do_submit();\"/>&nbsp;&nbsp;'
                                             + '<input type=\"button\" value=\"No\" onclick=\"confirmsubmitbox.fade();\"/>'
                                           + '</div>';
         
         
         confirmsubmitbox = new GlassBox();
         confirmsubmitbox.init('confirmsubmit','500px','165px','hidden','default',false,false);
         confirmsubmitbox.lbo(false,0.3);
         confirmsubmitbox.appear();
      }
      
      function save_target_weight(val,pms_objective_id,no) {
         if(dvedittargetweight) {
            dvedittargetweight.d.innerHTML = val+'&#160;%';
         }
         orgjx_app_saveJAMTargetWeight(val,pms_objective_id,no,null);
      }
      
      function kp_target_weight(d,e) {
         var k = getkeyc(e);
         if(d.chgt) {
            d.chgt.reset();
            d.chgt = null;
         }
         var val = parseFloat(d.value);
         if(k==13) {
            dvedittargetweight.d.innerHTML = val+'%';
            _destroy(dvedittargetweight);
            save_target_weight(val,dvedittargetweight.pms_objective_id,dvedittargetweight.no);
            dvedittargetweight.d = null;
            dvedittargetweight = null;
         } else if (k==27) {
            _destroy(dvedittargetweight);
            dvedittargetweight.d = null;
            dvedittargetweight = null;
         } else {
            d.chgt = new ctimer('save_target_weight(\"'+val+'\",\"'+dvedittargetweight.pms_objective_id+'\",\"'+dvedittargetweight.no+'\");',300);
            d.chgt.start();
         }
      }
      
      var dvedittargetweight = null;
      function edit_target_weight(pms_objective_id,no,d,e) {
         document.body.onclick = null;
         _destroy(dvedittargetweight);
         if(dvedittargetweight&&d==dvedittargetweight.d) {
            dvedittargetweight.d = null;
            dvedittargetweight = null;
            return;
         }
         d.dv = _dce('div');
         d.dv.setAttribute('style','position:absolute;padding:5px;-moz-border-radius:5px;border:1px solid #777;background-color:#ffffcc;left:0px;');
         d.dv.innerHTML = '<div style=\"text-align:right;padding:2px;\">Share : <input onkeyup=\"kp_target_weight(this,event);\" id=\"inp_target_weight\" style=\"-moz-border-radius:3px;width:50px;text-align:center;\" type=\"text\" value=\"'+parseFloat(d.innerHTML)+'\"/>&nbsp;%</div>';
         d.dv = d.parentNode.appendChild(d.dv);
         d.dv.style.top = parseInt(oY(d)+d.offsetHeight+15)+'px';
         d.dv.style.left = parseInt(oX(d.parentNode))+'px';
         d.dv.arrow = _dce('img');
         d.dv.arrow.setAttribute('style','position:absolute;left:0px;');
         d.dv.arrow.src = '".XOCP_SERVER_SUBDIR."/images/topmiddle.png';
         d.dv.arrow = d.dv.appendChild(d.dv.arrow);
         d.dv.arrow.style.top = '-12px';
         d.dv.arrow.style.left = parseInt(d.dv.offsetWidth-(d.parentNode.offsetWidth/2)-7)+'px';
         _dsa($('inp_target_weight'));
         dvedittargetweight = d.dv;
         dvedittargetweight.d = d;
         dvedittargetweight.pms_objective_id = pms_objective_id;
         dvedittargetweight.no = no;
         setTimeout('document.body.onclick = function() { document.body.onclick = null; _destroy(dvedittargetweight); };',100);
      }
      
      ////////////////////////////
      function save_target_text(pms_objective_id,no) {
         var val = trim($('inp_target_text').value);
         if(dvedittargettext) {
            dvedittargettext.d.innerHTML = val;
         }
         orgjx_app_saveJAMTargetText(val,pms_objective_id,no,null);
      }
      
      function kp_target_text(d,e) {
         var k = getkeyc(e);
         if(d.chgt) {
            d.chgt.reset();
            d.chgt = null;
         }
         var val = d.value;
         if(k==13) {
            dvedittargettext.d.innerHTML = val;
            save_target_text(dvedittargettext.pms_objective_id,dvedittargettext.no);
         } else if (k==27) {
            _destroy(dvedittargettext);
            dvedittargettext.d = null;
            dvedittargettext = null;
         } else {
            d.chgt = new ctimer('save_target_text(\"'+dvedittargettext.pms_objective_id+'\",\"'+dvedittargettext.no+'\");',300);
            d.chgt.start();
         }
      }
      
      function close_target_text() {
         document.body.onclick = null;
         _destroy(dvedittargettext);
         dvedittargettext.d = null;
         dvedittargettext = null;
         return;
      }
      
      var dvedittargettext = null;
      function edit_target_text(pms_objective_id,no,d,e) {
         document.body.onclick = null;
         _destroy(dvedittargettext);
         if(dvedittargettext&&d==dvedittargettext.d) {
            dvedittargettext.d = null;
            dvedittargettext = null;
            return;
         }
         d.dv = _dce('div');
         d.dv.setAttribute('style','position:absolute;padding:5px;-moz-border-radius:5px;border:1px solid #777;background-color:#ffffcc;left:0px;-moz-box-shadow:1px 1px 3px #000;');
         var text = d.innerHTML;
         if(text=='"._EMPTY."') {
            text = '';
         }
         d.dv.innerHTML = '<div style=\"text-align:left;padding:2px;\">Target :<br/>'
                        + '<textarea onkeyup=\"kp_target_text(this,event);\" id=\"inp_target_text\" style=\"-moz-border-radius:3px;width:350px;height:100px;\">'+text+'</textarea>'
                        + '<div style=\"text-align:right;\"><input class=\"sbtn\" type=\"button\" value=\"Close\" onclick=\"close_target_text();\"/></div>'
                        + '</div>';
         d.dv = d.parentNode.appendChild(d.dv);
         d.dv.style.top = parseInt(oY(d.parentNode)+d.parentNode.offsetHeight+25)+'px';
         var x = oX(d);
         if(x>650) {
            d.dv.style.left = parseInt(oX(d.parentNode)-(d.dv.offsetWidth)+(d.parentNode.offsetWidth))+'px';
         } else {
            d.dv.style.left = parseInt(oX(d)-(d.dv.offsetWidth/2)+(d.offsetWidth/2))+'px';
         }
         d.dv.arrow = _dce('img');
         d.dv.arrow.setAttribute('style','position:absolute;left:0px;');
         d.dv.arrow.src = '".XOCP_SERVER_SUBDIR."/images/topmiddle.png';
         d.dv.arrow = d.dv.appendChild(d.dv.arrow);
         d.dv.arrow.style.top = '-12px';
         if(x>650) {
            d.dv.arrow.style.left = parseInt(d.dv.offsetWidth-(d.parentNode.offsetWidth/2)-7)+'px';
         } else {
            d.dv.arrow.style.left = parseInt(d.dv.offsetWidth-(d.dv.offsetWidth/2)-7)+'px';
         }
         $('inp_target_text').focus();
         dvedittargettext = d.dv;
         dvedittargettext.d = d;
         dvedittargettext.pms_objective_id = pms_objective_id;
         dvedittargettext.no = no;
         //setTimeout('document.body.onclick = function() { document.body.onclick = null; _destroy(dvedittargettext); };',100);
      }
      
      ////////////////////////////
      
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
      
      $found_access = 0;
      $sql = "SELECT pms_org_id,access_id FROM pms_jam_org_access WHERE psid = '$psid' AND employee_id = '$self_employee_id' AND status_cd = 'normal'";
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
         $sql = "SELECT pms_org_id,access_id FROM pms_jam_org_access WHERE psid = '$psid' AND employee_id = '0' AND status_cd = 'normal'";
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
      
      
      if($_SESSION["pms_jam_org"]==1) {
         $orgsel = $this->showOrg();
      } else {
         $orgsel = "";
      }
      
      if($_SESSION["pms_jam_org"]==1&&$found_access==0) {
         return $pmssel.$orgsel."<div style='padding:5px;'>You don't have access privilege to setup JAM for Organization.</div>";
      }
      
      
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

} // PMS_JAM_DEFINED
?>