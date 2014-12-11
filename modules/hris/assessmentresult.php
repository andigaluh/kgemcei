<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/assessmentresult.php                       //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-12-18                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_ASSESSMENT_DEFINED') ) {
   define('HRIS_ASSESSMENT_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
include_once(XOCP_DOC_ROOT."/modules/hris/class/selectasid.php");

class _hris_AssessmentResult extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_ASSESSMENT_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_ASSESSMENTRESULT_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_AssessmentResult($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function assessment() {
      $db=&Database::getInstance();
      $ret = "";
      $user_id = getUserID();
      $tooltips = ""; /// for tooltips definition
      $assessor_job_count = 0;
      global $proficiency_level_name;
      
      $tabmargin = 482;
      
      $_SESSION["html"]->js_scriptaculous_effecs=TRUE;
      
      require_once(XOCP_DOC_ROOT."/modules/hris/include/assessment.php");
      $asid = $_SESSION["hris_assessment_asid"];
      
      $arr_compgroup = array();
      
      $sql = "SELECT compgroup_id,compgroup_nm,competency_class_set FROM ".XOCP_PREFIX."compgroup"
           . " ORDER BY compgroup_id";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($compgroup_id,$compgroup_nm,$cset)=$db->fetchRow($result)) {
            $arr_compgroup[$compgroup_id] = array($compgroup_nm,explode(",",$cset));
         }
      }
      
      $ret = "";
      
      $mtrx = ""; //// matrix of employee and job
      
      $sql = "SELECT c.job_id,b.employee_id"
           . " FROM ".XOCP_PREFIX."users a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(person_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job c USING(employee_id)"
           . " WHERE a.user_id = '$user_id' LIMIT 1";
      $result = $db->query($sql);
      $employee_list = "";
      if($db->getRowsNum($result)>0) {
         while(list($assessor_job_id,$self_employee_id)=$db->fetchRow($result)) {
            
            $_SESSION["self_employee_id"] = $self_employee_id;
            if($assessor_job_id==0) continue;
            $assessor_job_count++;
            
            
            if($_SESSION["assessor360"]==1) {
               $sql = "SELECT a.employee_id,b.job_id,c.job_nm,c.job_cd,c.job_abbr,c.org_id,c.description,c.summary,d.job_level"
                    . " FROM ".XOCP_PREFIX."assessor_360 a"
                    . " LEFT JOIN ".XOCP_PREFIX."employee_job b USING(employee_id)"
                    . " LEFT JOIN ".XOCP_PREFIX."jobs c USING(job_id)"
                    . " LEFT JOIN ".XOCP_PREFIX."job_class d USING(job_class_id)"
                    . " WHERE a.asid = '$asid' AND a.assessor_id = '$self_employee_id'"
                    . " AND a.status_cd = 'active'"
                    . " AND b.job_id IS NOT NULL"
                    . " GROUP BY a.employee_id"
                    . " ORDER BY c.job_class_id";
            } else {
                    
               $sql0 = "SELECT b.employee_id,j.job_id,c.job_nm,c.job_cd,c.job_abbr,c.org_id,c.description,c.summary,d.job_level"
                    . " FROM ".XOCP_PREFIX."employee_job b"
                    . " LEFT JOIN ".XOCP_PREFIX."assessment_session_job j ON j.asid = '$asid' AND j.employee_id = b.employee_id"
                    . " LEFT JOIN ".XOCP_PREFIX."jobs c ON c.job_id = j.job_id"
                    . " LEFT JOIN ".XOCP_PREFIX."job_class d USING(job_class_id)"
                    . " WHERE b.assessor_employee_id = '$self_employee_id'"
                    . " AND j.job_id IS NOT NULL"
                    . " GROUP BY b.employee_id";
               
               $sql1 = "SELECT a.employee_id,b.job_id,c.job_nm,c.job_cd,c.job_abbr,c.org_id,c.description,c.summary,d.job_level"
                    . " FROM ".XOCP_PREFIX."assessor_360 a"
                    . " LEFT JOIN ".XOCP_PREFIX."assessment_session_job b USING(asid,employee_id)"
                    . " LEFT JOIN ".XOCP_PREFIX."jobs c USING(job_id)"
                    . " LEFT JOIN ".XOCP_PREFIX."job_class d USING(job_class_id)"
                    . " WHERE a.asid = '$asid' AND a.assessor_id = '$self_employee_id'"
                    . " AND a.status_cd = 'active'"
                    . " AND a.assessor_t = 'superior'"
                    . " AND b.job_id IS NOT NULL";
                    //. " GROUP BY a.employee_id";
                    
               $sql = "( $sql0 ) UNION ( $sql1 )";
               //$sql = $sql0;
            }
            
            _debuglog("$sql0 #CK");
            
            $res = $db->query($sql);
            $no = 0;
            if($db->getRowsNum($res)>0) {
               while($rrow=$db->fetchRow($res)) {
                  if($_SESSION["assessor360"]==1) {
                     list($employee_idx,$job_id,$job_nm,$job_cd,$job_abbr,$org_id,$job_descx,$job_summary,$job_level)=$rrow;
                  } else {
                     //list($job_id,$job_nm,$job_cd,$job_abbr,$org_id,$job_descx,$job_summary,$job_level)=$rrow;
                     list($employee_idx,$job_id,$job_nm,$job_cd,$job_abbr,$org_id,$job_descx,$job_summary,$job_level)=$rrow;
                  }
                  
                  $sql = "SELECT * FROM ".XOCP_PREFIX."assessment_session_job WHERE asid = '$asid' AND employee_id = '$employee_idx' AND job_id = '$job_id'";
                  $rck = $db->query($sql);
                  _debuglog("\n$sql #CK");
                  if($db->getRowsNum($rck)==0) {
                     continue;
                  }
                  
                  $job_summary = str_replace("\n","",$job_summary);
                  if($_SESSION["assessor360"]==1) {
                     $sql = "SELECT a.employee_id,b.employee_ext_id,c.person_nm,a.gradeval,c.birth_dttm,c.birthplace,"
                          . "c.adm_gender_cd,c.addr_txt,c.cell_phone,c.home_phone,c.marital_st,"
                          . "b.entrance_dttm,a.start_dttm,a.stop_dttm,(TO_DAYS(now())-TO_DAYS(b.entrance_dttm)) as jobage,"
                          . "c.person_id"
                          . " FROM ".XOCP_PREFIX."assessment_session_job a"
                          . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
                          . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
                          . " WHERE a.asid = '$asid' AND a.employee_id = '$employee_idx'";
                  } else {
                     $sql = "SELECT a.employee_id,b.employee_ext_id,c.person_nm,c.birth_dttm,c.birthplace,"
                          . "c.adm_gender_cd,c.addr_txt,c.cell_phone,c.home_phone,c.marital_st,"
                          . "b.entrance_dttm,c.person_id"
                          . " FROM ".XOCP_PREFIX."assessment_session_job a"
                          . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
                          . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
                          . " WHERE a.employee_id = '$employee_idx' AND a.asid = '$asid'";
                  }
                  
                  $res2 = $db->query($sql);
                  if($db->getRowsNum($res2)>0) {
                     while(list($employee_id,$nip,$employee_nm,$dob,$pob,$gender,$addr,$cellphone,$phone,$marital,
                                $entrance_dttm,$person_id)=$db->fetchRow($res2)) {
                        $arr_xttl_rcl = array();
                                
                        $arr_employee[$employee_id] = array($employee_nm,$nip,$job_nm,$job_abbr,$job_id,$gradeval,$job_desc,$dob,$pob,$gender,$addr,$cellphone,$phone,$marital,
                                                            $entrance_dttm,$jobstart,$jobstop,$jobage,$person_id);
                        $arrmtrx[$employee_id][$job_id] = array($employee_id,$job_id);
                        $mtrx .= "$employee_id.$job_id-";
                        
                        
                        
                        $arr_ttl_ccl[$employee_id] = $ttl_ccl;
                        $arr_ttl_rcl[$employee_id] = $arr_xttl_rcl[$job_id];
                        
                        $matchcount = ($arr_ttl_rcl[$employee_id]>0?bcdiv($arr_ttl_ccl[$employee_id],$arr_ttl_rcl[$employee_id]):0);
                        $match = number_format(_bctrim(bcmul(100,$matchcount)),2);
                        $person_info = "<table style='width:100%;'><tbody><tr><td style='vertical-align:top;'><img src='".XOCP_SERVER_SUBDIR."/modules/hris/thumb.php?pid=$person_id' height='100'/></td>"
                                     . "<td style='vertical-align:top;'><table class='emp_info' style='margin-top:0px;width:290px;'>"
                                     . "<colgroup><col width='80'/><col/></colgroup>"
                                     . "<tbody>"
                                     . "<tr><td>Employee ID $ttl_ccl :</td><td>$nip</td></tr>"
                                     . "<tr><td>Entrance Date :</td><td>".sql2ind($entrance_dttm,"date")." ("._bctrim(toMoney($jobage/365.25))." year)</td></tr>"
                                     . "<tr><td>Gender :</td><td>".($gender=="f"?"Female":"Male")."</td></tr>"
                                     //. "<tr><td>Previous Job :</td><td></td></tr>"
                                     . "</tbody></table></td></tr></tbody></table>";
                        $tooltips .= "\nnew Tip('empjob_${employee_id}_${job_id}', '<div style=\"font-weight:bold;\">Job Summary:</div>$job_summary', {viewport:true,title:'$job_nm',style:'emp'});";
                        $tooltips .= "\nnew Tip('emp_${employee_id}_${job_id}', \"$person_info\", {title:'$employee_nm',width:350,style:'emp'});";
                        
                        $matchbox = "<div class='match' style='width:100px;border:1px solid #999999;'><img src='".XOCP_SERVER_SUBDIR."/modules/hris/images/level_foreground.png' style='margin-left:".(int)(-200+($match))."px;'/></div>"
                                  . "<table class='emp_info'><tbody>"
                                  . "<tr><td>Total Current Competency Level:</td><td>".$arr_ttl_ccl[$employee_id]."</td></tr>"
                                  . "<tr><td>Total Required Competency Level:</td><td>".$arr_ttl_rcl[$employee_id]."</td></tr>"
                                  . "<tr><td>Job Match:</td><td>${match}%</td></tr>"
                                  . "</tbody></table>";
                        
                        if($_SESSION["assessor360"]==1) {
                           $match_txt = "-";
                           $clr = "";
                        } else {
                           //$tooltips .= "\nnew Tip('match_${employee_id}_${job_id}', \"$matchbox\", {title:'Job Match Detail',width:250,style:'emp'});";
                           $match_txt = "$match%";
                        }
                                 
                        
                        if($job_level=="nonmanagement") {
                           $jl = $gradeval;
                        } else {
                           $jl = "-";
                        }
                        
                        /// $sql = "SELECT ttlccl,ttlrcl,ttlgap,jm,cf FROM ".XOCP_PREFIX."employee_competency_final_recap"
                        $sql = "SELECT ttlcclxxx,ttlrcl,ttlgapxxx,jmxxx,cfxxx FROM ".XOCP_PREFIX."employee_competency_final_recap"
                             . " WHERE asid = '$asid'"
                             . " AND employee_id = '$employee_id'"
                             . " AND job_id = '$job_id'";
                        $result = $db->query($sql);
                        _debuglog("$sql #CK");
                        if($db->getRowsNum($result)>0) {
                           list($ttlccl,$ttlrcl,$ttlgap,$jm,$cf)=$db->fetchRow($result);
                           $match_txt = number_format($jm,2,".","")."%";
                           if($jm < 80) {
                              $clr = "color:red;";
                           } else {
                              $clr = "";
                           }
                           
                           
                        } else {
                           $match_txt = "-";
                           $clr = "";
                        }
                              
                        
                        
                        $employee_list .= "<tr id='tremp_${employee_id}_${job_id}' class='trd2'>"
                                          . "<td id='emp_${employee_id}_${job_id}'>$employee_nm</td>"
                                          . "<td id='empjob_${employee_id}_${job_id}' style='text-align:left;'>$job_abbr</td>"
                                          . "<td style='text-align:center;'>$jl</td>"
                                          . "<td id='match_${employee_id}_${job_id}' style='text-align:center;$clr'>$match_txt</td>"
                                          . "<td style='text-align:center;'>"
                                             . "[<a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&r=y&e=${employee_id}&j=${job_id}'>view</a>]&nbsp;"
                                             . "[<a href='".XOCP_SERVER_SUBDIR."/modules/hris/print/asmresult.php?e=${employee_id}&j=${job_id}&asid=${asid}'>print</a>]"
                                          . "</td>"
                                          . "</tr>";
                        $no++;
                     }
                  } else {
                     $tooltips .= "\nnew Tip('empjob_0_${job_id}', '<div style=\"font-weight:bold;\">Job Summary:</div>$job_summary', {viewport:true,title:'$job_nm',style:'emp'});";
                     
                     $employee_list .= "<tr id='eemp_0_${job_id}'>"
                                       . "<td style='color:#bbbbbb;font-style:italic;padding-left:30px;font-weight:normal;'>Empty</td>"
                                       . "<td id='empjob_0_${job_id}'>$job_abbr</div></div></td>"
                                       . "<td style='text-align:center;'>-</td>"
                                       . "<td style='text-align:center;'>-</td>"
                                       . "<td style='text-align:center;'>-</td>"
                                       . "</tr>";
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
      
      $ret .= "<table style='cursor:default;width:900px;' class='xxlist'>"
            . "<thead>"
            . "<tr>"
               . "<td style='text-align:center;'>Employee</td>"
               . "<td style='text-align:left;'>Job</td>"
               . "<td style='text-align:center;'>Grade</td>"
               . "<td style='text-align:center;'>Job Match</td>"
               . "<td style='text-align:center;'>Action</td>"
            . "</tr>"
            . "</thead>"
            . "<tbody id='tbody_mtrx'>"
               . $employee_list
            . "</tbody>"
            . "</table>";
            


      $ret .= "<script type='text/javascript' src='".XOCP_SERVER_SUBDIR."/include/prototip2.0.5/js/prototip.js'></script>"
           . "<link rel='stylesheet' type='text/css' href='".XOCP_SERVER_SUBDIR."/include/prototip2.0.5/css/prototip.css' />";
      
      $mtrx = substr($mtrx,0,-1);
      
      $ret .= "<script type='text/javascript'><!--
      
      $tooltips
      
      
      // --></script>";
      
      return $ret;
         
   }
   
   function result($employee_id,$job_id,$asid=0,$noscript=FALSE) {
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_assessmentresult.php");
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      require_once(XOCP_DOC_ROOT."/modules/hris/include/assessment.php");
      $db=&Database::getInstance();
      $user_id = getUserID();
      
      $asid = $_SESSION["hris_assessment_asid"];
      
      if($asid>0) {
         $sql = "SELECT job_id FROM ".XOCP_PREFIX."assessment_session_job WHERE asid = '$asid' AND employee_id = '$employee_id'";
         $result = $db->query($sql);
         if($db->getRowsNum($result)>0) {
            list($job_id)=$db->fetchRow($result);
         }
      }
      
      
      list($emp_job_id,
           $emp_employee_id,
           $emp_job_nm,
           $emp_nm,
           $emp_nip,
           $emp_gender,
           $emp_jobstart,
           $emp_entrance_dttm,
           $emp_jobage,
           $emp_job_summary,
           $emp_person_id,
           $emp_user_id,
           $first_assessor_job_id,
           $next_assessor_job_id)=_hris_getinfobyemployeeid($employee_id);
      
      
      $ajax = new _hris_class_AssessmentResultModifierAjax("amr");
      
      $ttlccl = 0;
      $ttlgap = 0 ;
      $ttlrcl = 0;
      $ttlcclxxx = 0;
      $ttlgapxxx = 0 ;
      
      /// competency fit
      $cf_compgroup = array();
      $cf_pass = array();
      
      $tooltips = "";
      if($asid==0) {
         $asid = $_SESSION["hris_assessment_asid"];
      }
      
      //// UNCOMMENT BERIKUT JIKA INGIN REKALKULASI /// 
      if($user_id==1||$user_id==1041) { /// tambahkan user_id sendiri
         /// _calculate_competency($asid,$employee_id,$emp_job_id); //// untuk recalculate
      }
      
      $sql = "SELECT c.job_nm,c.job_abbr,d.org_nm,d.org_abbr,a.employee_ext_id,e.person_nm,e.person_id"
           . " FROM ".XOCP_PREFIX."employee a"
           . " LEFT JOIN ".XOCP_PREFIX."assessment_session_job b ON b.asid = '$asid' AND b.employee_id = a.employee_id AND b.job_id = '$job_id'"
           . " LEFT JOIN ".XOCP_PREFIX."jobs c ON c.job_id = '$job_id'"
           . " LEFT JOIN ".XOCP_PREFIX."orgs d ON d.org_id = c.org_id"
           . " LEFT JOIN ".XOCP_PREFIX."persons e ON e.person_id = a.person_id"
           . " WHERE a.employee_id = '$employee_id'";
      $result = $db->query($sql);
      _debuglog("$sql #CK");
      list($job_nm,$job_abbr,$org_nm,$org_abbr,$nip,$employee_nm,$person_id)=$db->fetchRow($result);
      
      if($noscript==TRUE) {
         $ret = "<html><head><style type='text/css' media='all'>
      
tr.bhv td { font-weight:bold;color:black;}
               
         
table.iresult {border-spacing:0px;}
table.iresult td {padding:4px;border:1px solid #bbb;border-bottom:0px;}
table.iresult td+td {border-left:0px solid #bbb;}
table.iresult tr.irhdr td {font-weight:bold;border-bottom:0px solid #bbb;}
table.iresult tr.recap td {font-weight:bold;border-bottom:1px solid #bbb;}
         
         </style></head><body>";
      } else {
         $ret = "<div style='border:1px solid #ddd;text-align:right;padding:4px;background-color:#eeeeee;'>"
              . "[<a href='".XOCP_SERVER_SUBDIR."/modules/hris/print/asmresult.php?e=${employee_id}&j=${job_id}&asid=${asid}'>Print</a>]&nbsp;"
              . ($_SESSION["asmresself"]==0?"[<a href='".XOCP_SERVER_SUBDIR."/index.php'>Back</a>]&nbsp;":"")
              . "</div>";
         $ret .= "<style type='text/css' media='all'> tr.bhv td { font-weight:bold;color:black;} </style>";
               
      }
      $ret .= "<br/><table style='margin-left:20px;'><tr><td style='padding:4px;border:1px solid #bbb;-moz-box-shadow:2px 2px 5px #333;'><img src='".XOCP_SERVER_SUBDIR."/modules/hris/thumb.php?pid=${person_id}' height='100'/></td>"
            . "<td style='vertical-align:top;padding-left:10px;'>"
            
            . "<table style='font-weight:bold;margin-left:0px;font-size:1.1em;'><colgroup><col width='120'/><col/></colgroup><tbody>"
            . "<tr><td>Job Title</td><td>: $job_nm ($job_abbr)</td></tr>"
            . "<tr><td>Section/Division</td><td>: $org_nm ($org_abbr)</td></tr>"
            . "<tr><td>Incumbent</td><td>: $employee_nm</td></tr>"
            . "<tr><td>NIP</td><td>: $nip</td></tr>"
            . "</tbody></table></td></tr></table><div style='padding:10px;'>";
      
      $ret .= "<table class='iresult'><colgroup>"
            . "<col width='70'/>"
            . "<col width='300'/>"
            . "<col width='30'/>"
            . "<col width='50'/>"
            . "<col width='50'/>"
            . "<col width='30'/>"
            . "<col width='50'/>"
            . "<col width='30'/>"
            . "<col width='50'/>"
            . "<col width='50'/>"
            . "<col width='50'/>"
            . "<col width='50'/>"
            . "</colgroup><tbody>"
            . "<tr class='irhdr'>"
            . "<td colspan='8' style='border-left:0px;border-top:0px;border-bottom:0px;font-size:1.1em;'>Competency Profile</td>"
            . "<td style='text-align:center;' colspan='3'>Total Value</td></tr>"
            . "<tr class='irhdr'>"
            . "<td colspan='3' style='border-left:0px;border-bottom:0px;border-top:0px;'></td>"
            . "<td style='text-align:center;'>ITJ</td>"
            . "<td style='text-align:center;'>RCL</td>"
            . "<td style='border-top:0px;'></td>"
            . "<td style='text-align:center;'>CCL</td>"
            . "<td style='border-top:0px;'></td>"
            . "<td style='text-align:center;'>RCL</td>"
            . "<td style='text-align:center;'>CCL</td>"
            . "<td style='text-align:center;'>GAP</td>"
            . "</tr>";
      $sql = "SELECT a.competency_id,a.rcl,a.itj,b.competency_nm,c.compgroup_nm,b.competency_class,d.ccl,(b.competency_class+0) as urcl,"
           . "'-',b.desc_en,b.desc_id,b.compgroup_id"
           /// . " FROM ".XOCP_PREFIX."job_competency a"
           . " FROM ".XOCP_PREFIX."assessment_session_job_competency a"
           . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
           . " LEFT JOIN ".XOCP_PREFIX."compgroup  c USING(compgroup_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee_competency_final d ON d.employee_id = '$employee_id' AND d.job_id = a.job_id AND d.competency_id = b.competency_id AND d.asid = '$asid'"
           //. " LEFT JOIN ".XOCP_PREFIX."employee e ON e.employee_id = d.assessor_id"
           //. " LEFT JOIN ".XOCP_PREFIX."persons f ON f.person_id = e.person_id"
           . " WHERE a.asid = '$asid' AND a.job_id = '$job_id'"
           . " ORDER BY b.compgroup_id,urcl,b.competency_id";
      $result = $db->query($sql);
      
      //if($user_id==1) 
      _debuglog("$sql #CK");
      
      $oldcompgroup = "";
      $oldcompgroup_id = "";
      if($db->getRowsNum($result)>0) {
         while(list($competency_id,$rcl,$itj,$competency_nm,$compgroup_nm,$cc,$ccl,$urcl,$asr_nm,$desc_en,$desc_id,$compgroup_id)=$db->fetchRow($result)) {
            
            /// $sql = "SELECT a.ccl,a.is_modified,a.last_modified_dttm,c.person_nm" /// commented on 2012-01-16
            $sql = "SELECT a.cclxxx,a.is_modified,a.last_modified_dttm,c.person_nm"
                 . " FROM ".XOCP_PREFIX."employee_competency_final a"
                 . " LEFT JOIN ".XOCP_PREFIX."users b ON b.user_id = a.last_modified_user_id"
                 . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
                 . " WHERE a.asid = '$asid'"
                 . " AND a.employee_id = '$employee_id'"
                 . " AND a.job_id = '$job_id'"
                 . " AND a.competency_id = '$competency_id'";
            $rc = $db->query($sql);
            if($db->getRowsNum($rc)>0) {
               list($final_ccl,$final_modified,$last_modified_dttm,$modify_person)=$db->fetchRow($rc);
            } else {
               $final_ccl = $final_modified = 0;
            }
            
            /// competency fit
            if($compgroup_id==1||$compgroup_id==2) {
               $cf_compgroup[$compgroup_id][$competency_id] = array($competency_id,$competency_nm,$compgroup_nm);
            }
            
            $cc = ucfirst($cc);
            $ccl = $ccl+0;
            $arrccl = array();
            $asrlist = "<table class=\"asrdtl\" style=\"width:100%;\"><thead><tr><td>Progress</td><td>Type</td><td>CCL</td></tr></thead>"
                      . "<tbody>";
            
            list($arrccl,$arrasr,$calc_ccl,$original_calc_ccl,$arravg,$arrcclxxx,$calc_cclxxx,$arravgxxx,$arrasrxxx) = _get_arrccl($asid,$employee_id,$competency_id,$job_id);
            
            /* commented on 2012-01-16
            foreach($arrasr as $k=>$v) {
               list($ccl360,$asr360_id,$asr360_nm,$assessor_t,$finish_status,$fulfilled)=$v;
               if($_SESSION["hrassess"]==1) {
                  $asrlist .= "<tr><td>$asr360_nm - $finish_status</td><td>$assessor_t</td><td>".number_format($ccl360,2,".","")."</td></tr>";
               } else {
                  $asrlist .= "<tr><td>$finish_status</td><td>$assessor_t</td><td>".number_format($ccl360,2,".","")."</td></tr>";
               }
            }
            */
            
            foreach($arrasrxxx as $k=>$v) {
               list($ccl360xxx,$asr360_id,$asr360_nm,$assessor_t,$finish_status,$fulfilled)=$v;
               if($_SESSION["hrassess"]==1) {
                  $asrlist .= "<tr><td>$asr360_nm - $finish_status</td><td>$assessor_t</td><td>".number_format($ccl360xxx,2,".","")."</td></tr>";
               } else {
                  $asrlist .= "<tr><td>$finish_status</td><td>$assessor_t</td><td>".number_format($ccl360xxx,2,".","")."</td></tr>";
               }
            }
            
            
            //$asrlist .= "<tr><td colspan=\"2\" style=\"text-align:left;font-weight:bold;\">Average</td><td style=\"font-weight:bold;\">".(count($arrccl)>0?number_format($original_calc_ccl,2,".",""):"-")."</td></tr>";
            
            /// result
            if($final_modified==1) {
               $asrlist .= "<tr><td style=\"background-color:#ddffff;\">".sql2ind($last_modified_dttm)."</td><td style=\"background-color:#ddffff;\">Alter</td><td style=\"background-color:#ddffff;\">".number_format($final_ccl,2,".","")."</td></tr>";
               $asrlist .= "<tr><td colspan=\"2\" style=\"text-align:left;font-weight:bold;\">Result</td><td style=\"font-weight:bold;\">".number_format($final_ccl,2,".","")."</td></tr>";
               $calc_ccl = $final_ccl;
            } else {
               /// $asrlist .= "<tr><td colspan=\"2\" style=\"text-align:left;font-weight:bold;\">Result</td><td style=\"font-weight:bold;\">".(count($arrccl)>0?number_format($calc_ccl,2,".",""):"-")."</td></tr>"; /// commented on 2012-01-16
               $asrlist .= "<tr><td colspan=\"2\" style=\"text-align:left;font-weight:bold;\">Result</td><td style=\"font-weight:bold;\">".(count($arrcclxxx)>0?number_format($calc_cclxxx,2,".",""):"-")."</td></tr>"; /// added on 2012-01-16
            }
            
            //$asrlist .= "<tr><td colspan=\"3\" style=\"text-align:center;color:blue;\">Click to alter result.</td></tr>";
            
            $asrlist .= "</tbody></table>";
            if($_SESSION["hrassess"]==1||$_SESSION["asmresself"]==0) {
               $tooltips .= "\nnew Tip('tdccl_${competency_id}', \n'$asrlist', \n{title:'Assessment Result Detail',style:'emp',offset:{x:0,y:10},width:300});";
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
            $gapxxxx = $calc_cclxxx*$itj-$rcl*$itj;
            
            /// if($gapx<0) { /// commented on 2012-01-16
            if($gapxxxx<0) { /// added on 2012-01-16
               $gap_color = "color:red;font-weight:bold;";
               $competency_color = "color:red;";
            } else {
               $gap_color = "";
               $competency_color = "";
               
               /// if($compgroup_id==1||$compgroup_id==2) {
               ///    $cf_pass[$compgroup_id][$competency_id] = 1;
               /// }
            }
            
            /*
            if($gapx<0) { /// added on 2012-01-16
               $gap_color = "color:red;font-weight:bold;";
               $competency_color = "color:red;";
            } else {
               $gap_color = "";
               $competency_color = "";
               if($compgroup_id==1||$compgroup_id==2) {
                  $cf_pass[$compgroup_id][$competency_id] = 1;
               }
            }
            */
            
            
            //if(trim($competency_nm)=="") $competency_nm = $competency_id;
            
            $ret .= "<tr class='trasmresult'><td $style>$cctxt</td>"
                  . "<td style='${competency_color}' id='tcomp_${competency_id}'>"
                     . ($_SESSION["asmresself"]==0?"<span onclick='_asmr_view_behaviour(\"$asid\",\"$employee_id\",\"$job_id\",\"$competency_id\",\"$calc_ccl\",this,event);' class='xlnk'>$competency_nm</span>":"$competency_nm")
                  . "</td>"
                  . "<td $style></td>"
                  . "<td style='text-align:center;'>$itj</td>"
                  . "<td style='text-align:center;'>$rcl</td>"
                  . "<td $style></td>"
                  . "<td style='text-align:center;cursor:pointer;' id='tdccl_${competency_id}'"
                  /// . " onclick='assessment_modify_result(\"$asid\",\"$employee_id\",\"$job_id\",\"$competency_id\");'"
                  /// . ">".(count($arrccl)>0?number_format($calc_ccl,2,".",""):"-")."</td>" /// commented on 2012-01-16
                  . ">".(count($arrcclxxx)>0?number_format($calc_cclxxx,2,".",""):"-")."</td>" /// added on 2012-01-16
                  . "<td $style></td>"
                  . "<td style='text-align:center;'>".($rcl*$itj)."</td>"
                  /// . "<td id='tdcalcccl_${competency_id}' style='text-align:center;'>".(count($arrccl)>0?number_format(($calc_ccl*$itj),2,".",""):"-")."</td>" /// commented on 2012-01-16
                  . "<td id='tdcalcccl_${competency_id}' style='text-align:center;'>".(count($arrcclxxx)>0?number_format(($calc_cclxxx*$itj),2,".",""):"-")."</td>" /// added on 2012-01-16
                  ///. "<td id='tdcalcgap_${competency_id}' style='text-align:center;${gap_color}'>".(count($arrccl)>0?number_format($gapx,2,".",""):"-")."</td>" /// commented on 2012-01-16
                  . "<td id='tdcalcgap_${competency_id}' style='text-align:center;${gap_color}'>".(count($arrcclxxx)>0?number_format($gapxxxx,2,".",""):"-")."</td>" /// added on 2012-01-16
                  . "</tr>";
            if(count($arrccl)>0) {
               $ttlccl += ($calc_ccl*$itj);
               $ttlrcl += ($rcl*$itj);
               $ttlgap += (($calc_ccl-$rcl)*$itj);
            }
            if(count($arrcclxxx)>0) {
               $ttlcclxxx += ($calc_cclxxx*$itj);
               $ttlgapxxx += (($calc_cclxxx-$rcl)*$itj);
            }
            //$tooltips .= "\nnew Tip('tcomp_${competency_id}', \"".addslashes($desc_en)."<hr noshade='1' size='1' color='#dddddd'/><span style='font-style:italic;'>".addslashes($desc_id)."</span>\", {title:'Description',width:350,style:'emp'});";
         }
      }
      if($ttlrcl==0) {
         $match = 0;
      } else {
         $match = toMoney(_bctrim(100*$ttlccl/$ttlrcl));
         $matchxxx = toMoney(_bctrim(100*$ttlcclxxx/$ttlrcl));
      }
      //$ret .= "<tr><td colspan='10' style='border:0px;'>&nbsp;</td></tr>";
      $ret .= "<tr class='irhdr'>"
            . "<td colspan='2' style='border-left:0px;border-right:0px;'>&nbsp;</td>"
            . "<td style='border-top:0px;border-left:0px;border-right:0px;'></td>"
            . "<td style='border-left:0px;border-right:0px;'></td>"
            . "<td style='border-left:0px;border-right:0px;'></td>"
            . "<td style='border-top:0px;border-left:0px;border-right:0px;'></td>"
            . "<td style='border-left:0px;border-right:0px;'></td>"
            . "<td style='border-top:0px;border-left:0px;border-right:0px;'></td>"
            . "<td style='border-left:0px;border-right:0px;'></td>"
            . "<td style='border-left:0px;border-right:0px;'></td>"
            . "<td style='border-left:0px;border-right:0px;'></td>"
            . "</tr>";
      
      /// if($ttlgap<0) { /// commented on 2012-01-16
      if($ttlgap<0) {
         $gap_color = "color:red;";
      } else {
         $gap_color = "";
      }
      
      //// REKAP
      
      /// $sql = "SELECT ttlccl,ttlrcl,ttlgap,jm,cf FROM ".XOCP_PREFIX."employee_competency_final_recap" /// commented on 2012-01-16
      $sql = "SELECT ttlcclxxx,ttlrcl,ttlgapxxx,jmxxx,cfxxx,cf FROM ".XOCP_PREFIX."employee_competency_final_recap"
           . " WHERE asid = '$asid'"
           . " AND employee_id = '$employee_id'"
           . " AND job_id = '$job_id'";
      $result = $db->query($sql);
                        _debuglog("$sql #CK");
      if($db->getRowsNum($result)>0) {
         list($ttlccl,$ttlrcl,$ttlgap,$jm,$cf,$cf_old)=$db->fetchRow($result);
         $match = number_format($jm,2,".","");
         if($jm < 80) {
            $clr = "color:red;";
         } else {
            $clr = "";
         }
      } else {
         $match_txt = "-";
         $clr = "";
      }
      
      $ret .= "<tr class='irhdr'>"
            . "<td colspan='2' style='font-weight:bold;text-align:center;'>Total : </td>"
            . "<td style='border:0px;'></td>"
            . "<td style='border:0px;'></td>"
            . "<td style='border:0px;'></td>"
            . "<td style='border:0px;'></td>"
            . "<td style='border:0px;'></td>"
            . "<td style='border-left:0px;border-top:0px;border-bottom:0px;'></td>"
            . "<td style='text-align:center;'>$ttlrcl</td>"
            /// . "<td id='tdttlccl' style='text-align:center;font-weight:bold;'>".number_format($ttlccl,2,".","")."</td>" /// commented on 2012-01-16
            . "<td id='tdttlccl' style='text-align:center;font-weight:bold;'>".number_format($ttlccl,2,".","")."</td>" /// addedd on 2012-01-16
            /// . "<td id='tdttlgap' style='text-align:center;font-weight:bold;${gap_color}'>".number_format($ttlgap,2,".","")."</td>" /// commented on 2012-01-16
            . "<td id='tdttlgap' style='text-align:center;font-weight:bold;${gap_color}'>".number_format($ttlgap,2,".","")."</td>" /// added on 2012-01-16
            . "</tr>";
      $ret .= "<tr class='recap'>"
            . "<td style='background-color:#ffeecc;font-weight:bold;text-align:center;' colspan='2'>Job Match :</td>"
            . "<td style='border:0px;'></td>"
            . "<td style='border:0px;'></td>"
            . "<td style='border:0px;'></td>"
            . "<td style='border:0px;'></td>"
            . "<td style='border:0px;'></td>"
            . "<td style='border-left:0px;border-top:0px;border-bottom:0px;'></td>"
            . "<td id='tdjm' style='text-align:center;background-color:#ffeecc;font-weight:bold;' colspan='3'>$match %</td></tr>";
      
      $cf_txt = number_format($cf,2,".","");
      $cf_old_txt = number_format($cf_old,2,".","");
      
      $ret .= "<tr class='recap'>"
            . "<td style='background-color:#ffeecc;font-weight:bold;text-align:center;border-top:0px;' colspan='2'>Competency Fit :</td>"
            . "<td style='border:0px;'></td>"
            . "<td style='border:0px;'></td>"
            . "<td style='border:0px;'></td>"
            . "<td style='border:0px;'></td>"
            . "<td style='border:0px;'></td>"
            . "<td style='border-left:0px;border-top:0px;border-bottom:0px;'></td>"
            . "<td id='tdcf' style='text-align:center;background-color:#ffeecc;font-weight:bold;border-top:0px;' colspan='3'>$cf_txt %</td></tr>";
      
      
      if(0) { ///$asid >=10 && $_SESSION["hrassess"]==1) {
         /// recalculate button
         $ret .= "<tr class='recap'>"
               . "<td style='border:0px;'></td>"
               . "<td style='border:0px;'></td>"
               . "<td style='border:0px;'></td>"
               . "<td style='border:0px;'></td>"
               . "<td style='border:0px;'></td>"
               . "<td style='border:0px;'></td>"
               . "<td style='border:0px;'></td>"
               . "<td style='border-left:0px;border-top:0px;border-bottom:0px;'></td>"
               . "<td id='tdcf' style='text-align:center;background-color:#fffff;font-weight:bold;border-top:0px;' colspan='3'>"
               . "<input type='button' value='Recalculate' onclick='recalc_ccl(\"$asid\",\"$employee_id\",\"$job_id\");'/></td></tr>";
      }
      
      $ret .= "</tbody></table>";
      
      $ret .= "<div style='padding:0px;padding-top:50px;'><table style='border-spacing:0px;'><tbody><tr><td style='border:0px solid #bbb;padding:5px;font-size:0.9em;'>"
            . "<div style='font-weight:bold;'>Remark:</div>"
            . "<div>Job Match = Total CCL / Total RCL</div>"
            . "<div>Competency Fit = ( General + Managerial Fulfilled Competecy Count ) / ( General + Managerial Competency Count )</div>"
            . "</td></tr></tbody></table></div>";

      $ret .= "</div>";
      $ret .= "<div style='padding-top:30px;'></div>";
      return $ret . ($noscript==FALSE?$ajax->getJs()."<script type='text/javascript' src='".XOCP_SERVER_SUBDIR."/include/prototip2.0.5/js/prototip.js'></script>"
           . "<link rel='stylesheet' type='text/css' href='".XOCP_SERVER_SUBDIR."/include/prototip2.0.5/css/prototip.css' />"
           . "<script type='text/javascript'><!--
           
            $tooltips
            
            function recalc_ccl(asid,employee_id,job_id) {
               amr_app_recalculateCCL(asid,employee_id,job_id,function(_data) {
                  location.reload();
               });
            }
            
            var asmodres = null;
            var asmodresbox = null;
            function _asmr_view_behaviour(asid,employee_id,job_id,competency_id,ccl,d,e) {
               ajax_feedback = _caf;
               asmodres = _dce('div');
               asmodres.setAttribute('id','asmodres');
               asmodres = document.body.appendChild(asmodres);
               asmodres.sub = asmodres.appendChild(_dce('div'));
               asmodres.sub.setAttribute('id','innerasmodres');
               asmodres.asid = asid;
               asmodres.employee_id = employee_id;
               asmodres.job_id = job_id;
               asmodres.competency_id = competency_id;
               asmodres.ccl = ccl;
               amr_app_viewBehaviourBox(asid,employee_id,job_id,competency_id,ccl,1,function(_data) {
                  if(_data!='FAIL') {
                     var data = recjsarray(_data);
                     $('innerasmodres').innerHTML = data[0];
                     if(asmodresbox) {
                        _destroy(asmodresbox.overlay);
                     }
                     asmodresbox = new GlassBox();
                     asmodresbox.init('asmodres','1000px','630px','hidden','default',false,false);
                     asmodresbox.lbo(true,0.3);
                     asmodresbox.appear();
                     vx = $('vx');
                     vx.ccl = asmodres.ccl;
                  }
               });
            }
            
            
            var vx = null;
            function view_behaviour_indicator(d,e) {
               vx = $('vx');
               vx.oldHTML = vx.innerHTML;
               var ccl = trim($('altered_ccl').value);
               vx.innerHTML = '';
               vx.appendChild(progress_span());
               vx.ccl = ccl;
               amr_app_viewBehaviourIndicator(asmodres.asid,asmodres.employee_id,asmodres.job_id,asmodres.competency_id,ccl,1,function(_data) {
                  $('vx').innerHTML = _data;
               });
            }
            
            function next_vx(d,e) {
               vx = $('vx');
               vx.ccl++;
               if(vx.ccl>4) {
                  vx.ccl = 4;
                  return;
               }
               $('vxcontent').innerHTML = '';
               $('vxcontent').appendChild(progress_span());
               amr_app_viewBehaviourIndicator(asmodres.asid,asmodres.employee_id,asmodres.job_id,asmodres.competency_id,vx.ccl,1,function(_data) {
                  $('vx').innerHTML = _data;
               });
            }
            
            function previous_vx(d,e) {
               vx = $('vx');
               vx.ccl--;
               if(vx.ccl<1) {
                  vx.ccl = 1;
                  return;
               }
               $('vxcontent').innerHTML = '';
               $('vxcontent').appendChild(progress_span());
               amr_app_viewBehaviourIndicator(asmodres.asid,asmodres.employee_id,asmodres.job_id,asmodres.competency_id,vx.ccl,1,function(_data) {
                  $('vx').innerHTML = _data;
               });
            }
            
            function back_vx(d,e) {
               return;
               $('vx').innerHTML = $('vx').oldHTML;
               
            }
            
            function reset_ccl(d,e) {
               $('dvbtnalter').innerHTML = '';
               $('dvbtnalter').appendChild(progress_span());
               var ccl = trim($('altered_ccl').value);
               $('altered_ccl').disabled = true;
               amr_app_resetCCL(asmodres.asid,asmodres.employee_id,asmodres.job_id,asmodres.competency_id,ccl,function(_data) {
                  asmodres.asid = null;
                  asmodres.employee_id = null;
                  asmodres.job_id = null;
                  asmodres.competency_id = null;
                  var data = recjsarray(_data);
                  $('tdccl_'+data[0]).innerHTML = data[1];
                  $('tdcalcccl_'+data[0]).innerHTML = data[2];
                  $('tdcalcgap_'+data[0]).innerHTML = data[3];
                  if(data[3]<0) {
                     $('tdcalcgap_'+data[0]).style.color = 'red';
                  } else {
                     $('tdcalcgap_'+data[0]).style.color = '';
                  }
                  $('tdttlccl').innerHTML = data[4];
                  $('tdttlgap').innerHTML = data[5];
                  if(data[5]<0) {
                     $('tdttlgap').style.color = 'red';
                  } else {
                     $('tdttlgap').style.color = '';
                  }
                  $('tdjm').innerHTML = data[6];
                  $('tdcf').innerHTML = data[7];
                  if(Tips&&Tips.remove) {
                     Tips.remove('tdccl_'+data[0]);
                     new Tip('tdccl_'+data[0],data[8],{title:'Assessment Result Detail',style:'emp',offset:{x:0,y:10},width:300});
                  }
                  asmodresbox.fade();
               });
            }
            
            function save_ccl(d,e) {
               $('dvbtnalter').innerHTML = '';
               $('dvbtnalter').appendChild(progress_span());
               var ccl = trim($('altered_ccl').value);
               $('altered_ccl').disabled = true;
               amr_app_saveCCL(asmodres.asid,asmodres.employee_id,asmodres.job_id,asmodres.competency_id,ccl,function(_data) {
                  asmodres.asid = null;
                  asmodres.employee_id = null;
                  asmodres.job_id = null;
                  asmodres.competency_id = null;
                  var data = recjsarray(_data);
                  $('tdccl_'+data[0]).innerHTML = data[1];
                  $('tdcalcccl_'+data[0]).innerHTML = data[2];
                  $('tdcalcgap_'+data[0]).innerHTML = data[3];
                  if(data[3]<0) {
                     $('tdcalcgap_'+data[0]).style.color = 'red';
                  } else {
                     $('tdcalcgap_'+data[0]).style.color = '';
                  }
                  $('tdttlccl').innerHTML = data[4];
                  $('tdttlgap').innerHTML = data[5];
                  if(data[5]<0) {
                     $('tdttlgap').style.color = 'red';
                  } else {
                     $('tdttlgap').style.color = '';
                  }
                  $('tdjm').innerHTML = data[6];
                  $('tdcf').innerHTML = data[7];
                  if(Tips&&Tips.remove) {
                     Tips.remove('tdccl_'+data[0]);
                     new Tip('tdccl_'+data[0],data[8],{title:'Assessment Result Detail',style:'emp',offset:{x:0,y:10},width:300});
                  }
                  asmodresbox.fade();
               });
            }
            
            var asmodres = null;
            var asmodresbox = null;
            assessment_modify_result = function(asid,employee_id,job_id,competency_id) {
               ajax_feedback = _caf;
               asmodres = _dce('div');
               asmodres.setAttribute('id','asmodres');
               asmodres = document.body.appendChild(asmodres);
               asmodres.sub = asmodres.appendChild(_dce('div'));
               asmodres.sub.setAttribute('id','innerasmodres');
               asmodres.asid = asid;
               asmodres.employee_id = employee_id;
               asmodres.job_id = job_id;
               asmodres.competency_id = competency_id;
               amr_app_modifyForm(asid,employee_id,job_id,competency_id,function(_data) {
                  if(_data!='FAIL') {
                     var data = recjsarray(_data);
                     $('innerasmodres').innerHTML = data[0];
                     if(asmodresbox) {
                        _destroy(asmodresbox.overlay);
                     }
                     asmodresbox = new GlassBox();
                     asmodresbox.init('asmodres','700px',data[1]+'px','hidden','default',false,false);
                     asmodresbox.lbo(false,0.3);
                     asmodresbox.appear();
                     setTimeout('_dsa($(\"altered_ccl\"))',30);
                  }
               });
            };
            
            // --></script>":"</body></html>");
      
   }
   
   function main() {
      $db = &Database::getInstance();
      
      $asidselobj = new _hris_class_SelectAssessmentSession();
      $asidsel = "<div style='padding-bottom:2px;'>".$asidselobj->show()."</div>";
      
      if(!isset($_SESSION["hris_assessment_asid"])||$_SESSION["hris_assessment_asid"]==0) {
         return $asidsel;
      }
      
      $self = $_SESSION["asmresself"];
      $user_id = getUserID();
      
      if($self==1) {
         $sql = "SELECT c.job_id,b.employee_id,d.job_nm,p.person_nm"
              . " FROM ".XOCP_PREFIX."users a"
              . " LEFT JOIN ".XOCP_PREFIX."employee b USING(person_id)"
              . " LEFT JOIN ".XOCP_PREFIX."employee_job c USING(employee_id)"
              . " LEFT JOIN ".XOCP_PREFIX."jobs d USING(job_id)"
              . " LEFT JOIN ".XOCP_PREFIX."persons p ON p.person_id = b.person_id"
              . " WHERE a.user_id = '$user_id'";
         $result = $db->query($sql);
         if($db->getRowsNum($result)>0) {
            list($job_id,$employee_id,$self_job_nm,$self_nm)=$db->fetchRow($result);
            return $asidsel.$this->result($employee_id,$job_id);
         } else {
            return "You don't have job assigned to you. Please contact HR Administrator.";
         }
      
      }

      switch ($this->catch) {
         case $this->blockID:
            if(isset($_GET["asm"])&&$_GET["asm"]=="y") {
               $_SESSION["assessment_page"] = "form";
               $_SESSION["assessment_employee_id"] = $_GET["eid"];
               $_SESSION["assessment_competency_id"] = $_GET["cid"];
               $ret = $this->assessment();
            } elseif(isset($_GET["summarypage"])&&$_GET["summarypage"]=="y") {
               $_SESSION["assessment_page"] = "summary";
               $ret = $this->assessment();
            } elseif(isset($_GET["r"])&&$_GET["r"]=="y"&&isset($_GET["e"])&&isset($_GET["j"])) {
               $employee_id = $_GET["e"]+0;
               $job_id = $_GET["j"]+0;
               $ret = $this->result($employee_id,$job_id);
            } else {
               if(isset($_SESSION["assessment_page"])&&$_SESSION["assessment_page"]=="form") {
                  $ret = $this->result($_SESSION["assessment_employee_id"],$_SESSION["assessment_competency_id"]);
               } else {
                  $ret = $this->assessment();
               }
            }
            break;
         default:
            if(isset($_SESSION["assessment_page"])&&$_SESSION["assessment_page"]=="form") {
               $ret = $this->assessment();
            } else {
               $ret = $this->assessment();
            }
            break;
      }
      return "<div style='width:900px;'>".$asidsel.$ret."</div>";
   }
}

} // HRIS_ASSESSMENT_DEFINED
?>