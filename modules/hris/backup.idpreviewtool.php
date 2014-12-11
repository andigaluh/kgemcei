<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/idpreviewtool.php                          //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_IDPREVIEWTOOL_DEFINED') ) {
   define('HRIS_IDPREVIEWTOOL_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
include_once(XOCP_DOC_ROOT."/modules/hris/assessmentresult.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/idp/idp.php");

class _hris_IDPReviewTool extends _hris_AssessmentResult {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_IDPREVIEWTOOL_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_IDPREVIEWTOOL_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_IDPReviewTool($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   function subordinateList() {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $assessor_job_count = 0;
      global $proficiency_level_name;
      
      $_SESSION["html"]->js_scriptaculous_effecs=TRUE;
      
      require_once(XOCP_DOC_ROOT."/modules/hris/include/assessment.php");
      
      $asid = _get_last_asid();
      
      $_SESSION["asid"] = $asid;
      
      $arr_compgroup = array();
      
      $sql = "SELECT compgroup_id,compgroup_nm,competency_class_set FROM ".XOCP_PREFIX."compgroup"
           . " ORDER BY compgroup_id";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($compgroup_id,$compgroup_nm,$cset)=$db->fetchRow($result)) {
            $arr_compgroup[$compgroup_id] = array($compgroup_nm,explode(",",$cset));
         }
      }
      
      $person_info = "";
      $tooltips = "";
      
      $sql = "SELECT c.job_id,b.employee_id"
           . " FROM ".XOCP_PREFIX."users a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(person_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job c USING(employee_id)"
           . " WHERE a.user_id = '$user_id'";
      $result = $db->query($sql);
      $ret = "";
      $employee_list = "";
      if($db->getRowsNum($result)>0) {
         while(list($assessor_job_id,$self_employee_id)=$db->fetchRow($result)) {
            $_SESSION["self_employee_id"] = $self_employee_id;
            if($assessor_job_id==0) continue;
            $assessor_job_count++;
            $sql = "SELECT 0,a.job_id,a.job_nm,a.job_cd,a.job_abbr,a.org_id,a.description,a.summary,b.job_level"
                 . " FROM ".XOCP_PREFIX."jobs a"
                 . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
                 . " WHERE a.assessor_job_id = '$assessor_job_id'"
                 . " ORDER BY a.job_class_id";
            
/*            
            $sql = "SELECT a.employee_id,b.job_id,c.job_nm,c.job_cd,c.job_abbr,c.org_id,c.description,c.summary,d.job_level"
                 . " FROM ".XOCP_PREFIX."assessor_360 a"
                 . " LEFT JOIN ".XOCP_PREFIX."employee_job b USING(employee_id)"
                 . " LEFT JOIN ".XOCP_PREFIX."jobs c USING(job_id)"
                 . " LEFT JOIN ".XOCP_PREFIX."job_class d USING(job_class_id)"
                 . " WHERE a.asid = '$asid' AND a.assessor_id = '$self_employee_id'"
                 . " AND a.status_cd = 'active'"
                 . " AND a.assessor_t = 'superior'"
                 . " AND b.job_id IS NOT NULL"
                 . " ORDER BY c.job_class_id";
   */         
            $res = $db->query($sql);
            $no = 0;
            if($db->getRowsNum($res)>0) {
               while($rrow=$db->fetchRow($res)) {
                  list($employee_id,$job_id,$job_nm,$job_cd,$job_abbr,$org_id,$job_descx,$job_summary,$job_level)=$rrow;
                  $job_summary = str_replace("\n","",$job_summary);
                  
                  
                  
                  $sql = "SELECT a.employee_id,b.employee_ext_id,c.person_nm,a.gradeval,c.birth_dttm,c.birthplace,"
                       . "c.adm_gender_cd,c.addr_txt,c.cell_phone,c.home_phone,c.marital_st,"
                       . "b.entrance_dttm,a.start_dttm,a.stop_dttm,(TO_DAYS(now())-TO_DAYS(b.entrance_dttm)) as jobage,"
                       . "c.person_id"
                       . " FROM ".XOCP_PREFIX."employee_job a"
                       . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
                       . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
                       . " WHERE a.job_id = '$job_id'";
                  $res2 = $db->query($sql);
                  if($db->getRowsNum($res2)>0) {
                     while(list($employee_id,$nip,$employee_nm,$gradeval,$dob,$pob,$gender,$addr,$cellphone,$phone,$marital,
                                $entrance_dttm,$jobstart,$jobstop,$jobage,$person_id)=$db->fetchRow($res2)) {
                        
                        $sql = "SELECT c.job_nm,c.job_abbr,d.org_nm,d.org_abbr,a.employee_ext_id,e.person_nm,e.person_id"
                             . " FROM ".XOCP_PREFIX."employee a"
                             . " LEFT JOIN ".XOCP_PREFIX."employee_job b ON b.employee_id = a.employee_id AND b.job_id = '$job_id'"
                             . " LEFT JOIN ".XOCP_PREFIX."jobs c ON c.job_id = '$job_id'"
                             . " LEFT JOIN ".XOCP_PREFIX."orgs d ON d.org_id = c.org_id"
                             . " LEFT JOIN ".XOCP_PREFIX."persons e ON e.person_id = a.person_id"
                             . " WHERE a.employee_id = '$employee_id'";
                        $res_emp = $db->query($sql);
                        list($job_nm,$job_abbr,$org_nm,$org_abbr,$nip,$employee_nm,$person_id)=$db->fetchRow($res_emp);
                        
                        
                        $person_info = "<table style='width:100%;'><tbody><tr><td style='vertical-align:top;'><img src='".XOCP_SERVER_SUBDIR."/modules/hris/thumb.php?pid=$person_id' height='100'/></td>"
                                     . "<td style='vertical-align:top;'><table class='emp_info' style='margin-top:0px;width:290px;'>"
                                     . "<colgroup><col width='80'/><col/></colgroup>"
                                     . "<tbody>"
                                     . "<tr><td>Employee ID :</td><td>$nip</td></tr>"
                                     . "<tr><td>Entrance Date :</td><td>".sql2ind($entrance_dttm,"date")." ("._bctrim(toMoney($jobage/365.25))." year)</td></tr>"
                                     . "<tr><td>Job Assigned :</td><td>".sql2ind($jobstart,"date")."</td></tr>"
                                     . "<tr><td>Gender :</td><td>".($gender=="f"?"Female":"Male")."</td></tr>"
                                     . "<tr><td>Previous Job :</td><td></td></tr></tbody></table></td></tr></tbody></table>";
                        $tooltips .= "\nnew Tip('empjob_${employee_id}_${job_id}', '<div style=\"font-weight:bold;\">Job Summary:</div>$job_summary', {viewport:true,title:'$job_nm',style:'emp'});";
                        $tooltips .= "\nnew Tip('emp_${employee_id}_${job_id}', \"$person_info\", {title:'$employee_nm',width:350,style:'emp'});";
                        
                        
                        
                        ////////////////////////////////////////////////////////////////////
                        ////////////////////////////////////////////////////////////////////
                        
                        $sql = "SELECT pr_session_id,pr_session_nm FROM ".XOCP_PREFIX."pr_session ORDER BY pr_session_nm DESC LIMIT 1";
                        $rpr = $db->query($sql);
                        if($db->getRowsNum($rpr)>0) {
                           list($pr_session_id,$pr_session_nm)=$db->fetchRow($rpr);
                           $sql = "SELECT pr_value FROM ".XOCP_PREFIX."pr_result WHERE pr_session_id = '$pr_session_id' AND employee_id = '$employee_id'";
                           $rpr = $db->query($sql);
                           if($db->getRowsNum($rpr)>0) {
                              list($pr_value)=$db->fetchRow($rpr);
                           } else {
                              $pr_value = 0;
                           }
                        } else {
                           $pr_value = 0;
                        }
                        
                        $ccl = 0;
                        $ttlccl = 0;
                        $ttlrcl = 0;
                        $cf_compgroup = array();
                        $cf_pass = array();
                        
                        $sql = "SELECT a.competency_id,a.rcl,a.itj,b.competency_nm,c.compgroup_nm,b.competency_class,d.ccl,(b.competency_class+0) as urcl,"
                             . "f.person_nm,b.desc_en,b.desc_id,b.compgroup_id"
                             . " FROM ".XOCP_PREFIX."job_competency a"
                             . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
                             . " LEFT JOIN ".XOCP_PREFIX."compgroup  c USING(compgroup_id)"
                             . " LEFT JOIN ".XOCP_PREFIX."employee_competency d ON d.employee_id = '$employee_id' AND d.competency_id = b.competency_id"
                             . " LEFT JOIN ".XOCP_PREFIX."employee e ON e.employee_id = d.assessor_id"
                             . " LEFT JOIN ".XOCP_PREFIX."persons f ON f.person_id = e.person_id"
                             . " WHERE a.job_id = '$job_id'"
                             . " ORDER BY b.compgroup_id,urcl";
                        $result = $db->query($sql);
                        $oldcompgroup = "";
                        $oldcompgroup_id = "";
                        $ccl = 0;
                        
                        if($db->getRowsNum($result)>0) {
                           while(list($competency_id,$rcl,$itj,$competency_nm,$compgroup_nm,$cc,$ccl,$urcl,$asr_nm,$desc_en,$desc_id,$compgroup_id)=$db->fetchRow($result)) {
                              
                              /// competency fit
                              if($compgroup_id==1||$compgroup_id==2) {
                                 $cf_compgroup[$compgroup_id][$competency_id] = array($competency_id,$competency_nm,$compgroup_nm);
                              }
                              
                              $cc = ucfirst($cc);
                              $ccl = $ccl+0;
                              $arrccl = array();
                              $arrccl["superior"] = $ccl;
                              $asrlist = "<table class=\"asrdtl\" style=\"width:100%;\"><thead><tr><td>Assessor</td><td>Type</td><td>CCL</td></tr></thead>"
                                        . "<tbody><tr><td>$asr_nm</td><td>Superior</td><td>$ccl</td></tr>";
                              //// 360
                              $sql = "SELECT a.ccl,a.assessor_id,c.person_nm,d.assessor_t FROM ".XOCP_PREFIX."employee_competency360 a"
                                   . " LEFT JOIN ".XOCP_PREFIX."employee b ON b.employee_id = a.assessor_id"
                                   . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
                                   . " LEFT JOIN ".XOCP_PREFIX."assessor_360 d ON d.asid = '$asid'"
                                   . " AND d.employee_id = a.employee_id AND d.assessor_id = a.assessor_id"
                                   . " AND d.status_cd = 'active'"
                                   . " WHERE a.employee_id = '$employee_id'"
                                   . " AND a.competency_id = '$competency_id'"
                                   . " AND d.asid = '$asid'"
                                   . " ORDER BY a.ccl DESC";
                              $r360 = $db->query($sql);
                              if($db->getRowsNum($r360)>0) {
                                 while(list($ccl360,$asr360_id,$asr360_nm,$assessor_t)=$db->fetchRow($r360)) {
                                    if($assessor_t=="superior") continue;
                                    $ccl360 = $ccl360+0;
                                    $arrccl[$asr360_id] = $ccl360;
                                    $asrlist .= "<tr><td>$asr360_nm</td><td>$assessor_t</td><td>$ccl360</td></tr>";
                                 }
                              }
                              
                              arsort($arrccl);
                              $ascnt = count($arrccl);
                              $xxccl = 4;
                              $cnt = 0;
                              $calc_ccl = 0;
                              
                              $r = 0;
                              $old_r = $r;
                              foreach($arrccl as $k=>$v) {
                                 if($cnt==0) {
                                    $calc_ccl = $v;
                                 }
                                 $cnt++;
                                 $r = _bctrim(bcdiv($cnt,$ascnt));
                                 
                                 if(bccomp($old_r,0.75)>=0) {
                                 } else {
                                    $calc_ccl = $v;
                                 }
                                 $old_r = $r;
                              }
                              
                              $asrlist .= "<tr><td colspan=\"2\" style=\"text-align:left;font-weight:bold;\">Result</td><td style=\"font-weight:bold;\">$calc_ccl</td></tr>";
                              $asrlist .= "</tbody></table>";
                              if($_SESSION["asmresself"]==0) {
                                 ///$tooltips .= "\nnew Tip('tdccl_${competency_id}', '$asrlist', {title:'Assessment Result Detail',style:'emp',offset:{x:0,y:10},width:300});";
                              }
                              
                              ////
                              if($oldcompgroup!=$compgroup_nm) {
                                 $ret .= "<tr><td colspan='11' style='font-weight:bold;background-color:#eee;padding:4px;'>$compgroup_nm</td></tr>";
                                 $oldcompgroup = $compgroup_nm;
                                 $oldcompgroup_id = $compgroup_id;
                                 $oldcc = "";
                              }
                              if($oldcc!=$cc) {
                                 $cctxt = $cc;
                                 $oldcc = $cc;
                                 $style = "style='border-bottom:0px;'";
                              } else {
                                 $cctxt = "";
                                 $style = "style='border-top:0px;border-bottom:0px;'";
                              }
                              $gapx = $calc_ccl*$itj-$rcl*$itj;
                              if($gapx<0) {
                                 $gap_color = "color:red;font-weight:bold;";
                                 $competency_color = "color:red;";
                              } else {
                                 $gap_color = "";
                                 $competency_color = "";
                                 if($compgroup_id==1||$compgroup_id==2) {
                                    $cf_pass[$compgroup_id][$competency_id] = 1;
                                 }
                              }
                              $retxxx .= "<tr><td $style>$cctxt</td>"
                                    . "<td style='${competency_color}' id='tcomp_${competency_id}' class='tdcomp'>$competency_nm</td>"
                                    . "<td $style></td>"
                                    . "<td style='text-align:center;'>$itj</td>"
                                    . "<td style='text-align:center;'>$rcl</td>"
                                    . "<td $style></td>"
                                    . "<td style='text-align:center;cursor:default;' id='tdccl_${competency_id}'>$calc_ccl</td>"
                                    . "<td $style></td>"
                                    . "<td style='text-align:center;'>".($rcl*$itj)."</td>"
                                    . "<td style='text-align:center;'>".($calc_ccl*$itj)."</td>"
                                    . "<td style='text-align:center;${gap_color}'>$gapx</td>"
                                    . "</tr>";
                              $ttlccl += ($calc_ccl*$itj);
                              $ttlrcl += ($rcl*$itj);
                              $ttlgap += (($calc_ccl-$rcl)*$itj);
                              ///$tooltips .= "\nnew Tip('tcomp_${competency_id}', \"".addslashes($desc_en)."<hr noshade='1' size='1' color='#dddddd'/><span style='font-style:italic;'>".addslashes($desc_id)."</span>\", {title:'Description',width:350,style:'emp'});";
                           }
                        }
                        
                        if($ttlrcl==0) {
                           $match = 0;
                        } else {
                           $match = toMoney(_bctrim(100*$ttlccl/$ttlrcl));
                        }
                        
                        if($match < 80) {
                           $clr = "color:red;";
                        } else {
                           $clr = "";
                        }
                              
                        
                        /// competency fit
                        $cf_cnt = $cf_pass_cnt = 0;
                        foreach($cf_compgroup as $cg=>$x) {
                           $cf_cnt += count($cf_compgroup[$cg]);
                           $cf_pass_cnt += count($cf_pass[$cg]);
                        }
      
                        $cf = toMoney(_bctrim(bcmul(100,bcdiv($cf_pass_cnt,$cf_cnt))));
                        $pr = toMoney(_bctrim(bcmul(100,$pr_value)));
                        
                        $sql = "SELECT jmxxx,cf FROM ".XOCP_PREFIX."employee_competency_final_recap"
                             . " WHERE employee_id = '$employee_id' ORDER BY asid DESC LIMIT 1";
                        $rm = $db->query($sql);
                        if($db->getRowsNum($rm)==1) {
                           list($match,$cf)=$db->fetchRow($rm);
                           $match = toMoney($match);
                           $cf = toMoney($cf);
                        } else {
                          $match = 0;
                          $cf = 0;
                        }

                        
                        if($cf<70) {
                           $cf_clr = "color:red;";
                        } else {
                           $cf_clr = "";
                        }
      
                        if($pr<70) {
                           $pr_clr = "color:red;";
                        } else {
                           $pr_clr = "";
                        }
      
                        if($job_level=="nonmanagement") {
                           $jl = $gradeval;
                        } else {
                           $jl = "-";
                        }
                        
                        ////////////////////////////////////////////////////////////////////
                        ////////////////////////////////////////////////////////////////////
                        
                        list($cur_year)=explode("-",getSQLDate()); /// 2012-05-02 08:13:00
                        $sql = "SELECT request_id,status_cd,request_t FROM ".XOCP_PREFIX."idp_request"
                             . " WHERE employee_id = '$employee_id'"
                             . " AND status_cd NOT IN('nullified')"
                             . " AND created_dttm >= '$cur_year-01-01 00:00:00'";
                        $rreq = $db->query($sql);
                        _debuglog($sql);
                        $req_count = 0;
                        $req_status = "";
                        $progress_time = $progress_qty = 0;
                        $progress_time_txt = "0%";
                        $progress_qty_txt = "0%";
                        if($db->getRowsNum($rreq)>0) {
                           while(list($request_id,$status_cd,$request_t)=$db->fetchRow($rreq)) {
                           
                              switch($status_cd) {
                                 case "start":
                                    $req_status .= ", New Request";
                                    break;
                                 case "employee":
                                    $req_status .= ", Employee Request";
                                    break;
                                 case "approval1":
                                    $req_status .= ", Need Approval";
                                    break;
                                 case "approval2":
                                    $req_status .= ", Next Superior Approval";
                                    break;
                                 case "approval3":
                                    $req_status .= ", HR Approval";
                                    break;
                                 case "implementation":
                                    $req_status .= ", Implementation";
                                    break;
                                 case "completed":
                                    $req_status .= ", Completed";
                                    break;
                                 default:
                                    break;
                              }
                              $req_count++;
                              //////////////////////////////////////////////////////////////////////////////////////////////////////////
                              $sql = "SELECT COUNT(*) FROM ".XOCP_PREFIX."idp_request_actionplan WHERE request_id = '$request_id' AND status_cd NOT IN ('rejected','nullified')";
                              $rc = $db->query($sql);
                              if($db->getRowsNum($rc)==1) {
                                 list($cntaap)=$db->fetchRow($rc);
                              } else {
                                 $cntaap = 0;
                              }
                              
                              $sql = "SELECT COUNT(*) FROM ".XOCP_PREFIX."idp_request_actionplan WHERE request_id = '$request_id' AND status_cd = 'completed'";
                              $rc = $db->query($sql);
                              if($db->getRowsNum($rc)==1) {
                                 list($cntaapc)=$db->fetchRow($rc);
                              } else {
                                 $cntaapc = 0;
                              }
                              
                              if($cntaap>0) {
                                 $progress_qty = ceil(bcmul(100,bcdiv($cntaapc,$cntaap)));
                                 if($progress_qty>100) $progress_qty = 100;
                                 $progress_qty_txt = toMoneyShort($progress_qty)."%";
                              } else {
                                 $progress_qty_txt = "0%";
                                 $progress_qty = 0;
                              }
            
                              list($timeframe_start,$timeframe_stop)=_idp_get_timeframe($request_id);
                              
                              $progress_time_txt = "0%";
                              
                              $sql = "SELECT TO_DAYS(now()),TO_DAYS('$timeframe_start'),TO_DAYS('$timeframe_stop')";
                              $resultx = $db->query($sql);
                              list($now,$start,$stop)=$db->fetchRow($resultx);
                              if($now<=$start) {
                                 $progress_time_txt = "0%";
                              } else {
                                 $p = $now-$start;
                                 $q = $stop-$start;
                                 $progress_time = 100*($p/$q);
                                 if($progress_time>100) $progress_time = 100;
                                 $progress_time_txt = toMoneyShort($progress_time)."%";
                              }
            
                              
                              //////////////////////////////////////////////////////////////////////////////////////////////////////////
                           
                           }
                        }
                        
                        $req_status = substr($req_status,2);
                        
                        $employee_list .= "<tr id='tremp_${employee_id}_${job_id}'>"
                                          . "<td id='emp_${employee_id}_${job_id}' style='cursor:default;'>$employee_nm</td>"
                                          . "<td id='empjob_${employee_id}_${job_id}' style='cursor:default;'>$job_abbr</td>"
                                          . "<td style='text-align:center;'>$jl</td>"
                                          //. "<td style='text-align:right;'>"
                                          //   . "<a style='$clr' href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&r=y&e=${employee_id}&j=${job_id}'>$match %</a>"
                                          //. "</td>"
                                          . "<td style='text-align:right;'>"
                                             . "<a style='$cf_clr' href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&r=y&e=${employee_id}&j=${job_id}'>$cf %</a>"
                                          . "</td>"
                                          . "<td style='text-align:right;$pr_clr'>"
                                             . "$pr %"
                                          . "</td>"
                                          . "<td style='text-align:left;'>"
                                             . "<div style='float:left;overflow:hidden;width:50px !important;border:1px solid #999999;margin:auto;text-align:left;'>"
                                             . "<img src='".XOCP_SERVER_SUBDIR."/modules/hris/images/level_foreground.png' style='width:50px;height:12px;margin-left:".((int)(-50+($progress_time/2)))."px;'/>"
                                             . "</div>"
                                             . "<div style='float:left;padding-left:3px;'>$progress_time_txt</div>"
                                          . "</td>"
                                          . "<td style='text-align:left;'>"
                                             . "<div style='float:left;overflow:hidden;width:50px !important;border:1px solid #999999;margin:auto;text-align:left;'>"
                                             . "<img src='".XOCP_SERVER_SUBDIR."/modules/hris/images/level_foreground.png' style='width:50px;height:12px;margin-left:".((int)(-50+($progress_qty/2)))."px;'/>"
                                             . "</div>"
                                             . "<div style='float:left;padding-left:3px;'>$progress_qty_txt</div>"
                                          . "</td>"
                                          . "<td style='text-align:center;'>"
                                             . "<a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&req=y&e=${employee_id}&j=${job_id}'>".($req_count>0?$req_status:"Click to start")."</a>&nbsp;"
                                          . "</td>"
                                          . "</tr>";
                        $no++;
                     }
                  } else {
                     $employee_list .= "<tr id='eemp_0_${job_id}' class='trd2'>"
                                       . "<td><div style='color:#bbbbbb;font-style:italic;padding-left:30px;font-weight:normal;'>Empty</div></td>"
                                       . "<td id='empjob_0_${job_id}' style='cursor:default;'>$job_abbr</td>"
                                       . "<td style='text-align:center;'>&nbsp;</td>"
                                       . "<td style='text-align:right;'>&nbsp;</td>"
                                       . "<td style='text-align:right;'>&nbsp;</td>"
                                       // . "<td style='text-align:right;'>&nbsp;</td>"
                                       . "<td style='text-align:right;'>&nbsp;</td>"
                                       . "<td style='text-align:right;'>&nbsp;</td>"
                                       . "<td>&nbsp;</td>"
                                       . "</tr>";
                     $tooltips .= "\nnew Tip('empjob_0_${job_id}', '<div style=\"font-weight:bold;\">Job Summary:</div>$job_summary', {viewport:true,title:'$job_nm',style:'emp'});";
                     $no++;
                  }
                  
               }
            } else {
               return "You don't have any employee/subordinate.";
            }
         }
      }
      
      
      if($assessor_job_count==0) {
         return "You don't have a job assigned. Please contact HR Administrator.";
      }
      
      $ret = "<div>"
           . "<table style='width:100%;' class='xxlist'><thead><tr>"
               . "<td>Employee Name</td>"
               . "<td>Job</td>"
               . "<td style='text-align:center;'>Grade</td>"
               . "<td style='text-align:right;'>Job Match</td>"
               . "<td style='text-align:right;'>Comp. Fit</td>"
               //. "<td style='text-align:right;'>Performance</td>"
               . "<td style='text-align:left;'>Elapsed Time</td>"
               . "<td style='text-align:left;'>Progress</td>"
               . "<td style='text-align:center;'>IDP</td>"
           . "</tr></thead><tbody>"
           . $employee_list
           . "</tbody></table>"
           . "</div>";
      
      
      $ret .= "<script type='text/javascript' src='".XOCP_SERVER_SUBDIR."/include/prototip2.0.5/js/prototip.js'></script>"
           . "<link rel='stylesheet' type='text/css' href='".XOCP_SERVER_SUBDIR."/include/prototip2.0.5/css/prototip.css' />";
      
      $ret .= "<script type='text/javascript'><!--
      
      $tooltips
      
      
      // --></script>";
      
      
      
      return $ret;
      
   }
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            if(isset($_GET["smethod"])&&$_GET["smethod"]==1&&isset($_GET["t"])&&$_GET["t"]!="") {
               $_SESSION["hris_method_t"] = $_GET["t"];
               $ret = $this->subordinateList();
            } elseif(isset($_GET["r"])&&$_GET["r"]=="y"&&isset($_GET["e"])&&isset($_GET["j"])) {
               $employee_id = $_GET["e"]+0;
               $job_id = $_GET["j"]+0;
               $ret = $this->result($employee_id,$job_id);
            } elseif(isset($_GET["req"])&&$_GET["req"]=="y"&&isset($_GET["e"])) {
               include_once(XOCP_DOC_ROOT."/modules/hris/include/idp/idp.php");
               $employee_id = $_GET["e"]+0;
               $job_id = $_GET["j"]+0;
               $ret = _idp_view_request($employee_id,$job_id);
            } else if(isset($_GET["backlist"])&&$_GET["backlist"]==1) {
               unset($_SESSION["hris_method_t"]);
               $ret = $this->subordinateList();
            } else {
               $ret = $this->subordinateList();
            }
            break;
         default:
            $ret = $this->subordinateList();
            break;
      }
      return $ret."<div style='height:100px;'>&nbsp;</div>";
   }
}

} // HRIS_IDPREVIEWTOOL_DEFINED
