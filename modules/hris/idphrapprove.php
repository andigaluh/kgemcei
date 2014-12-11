<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/editjobclass.php                              //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_IDPHRAPPROVAL_DEFINED') ) {
   define('HRIS_IDPHRAPPROVAL_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/idp/idp.php");
include_once(XOCP_DOC_ROOT."/modules/hris/assessmentresult.php");

function sort_job($job_class_id_a,$job_class_id_b) {
   
   list($job_class_idx,$job_class_nmx,$job_class_level_a) = $_SESSION["hris_poslevel"][$job_class_id_a];
   list($job_class_idx,$job_class_nmx,$job_class_level_b) = $_SESSION["hris_poslevel"][$job_class_id_b];
   
   if($job_class_level_a==$job_class_level_b) {
      return 0;
   }
   
   return ($job_class_level_a < $job_class_level_b) ? -1 : 1;
}

class _hris_IDPHRApprove extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_IDPHRAPPROVAL_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_IDPHRAPPROVAL_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_IDPHRApprove($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   function recurseDivision($parent_id) {
      $db=&Database::getInstance();
      $sql = "SELECT a.org_id,a.org_nm,a.org_abbr,b.org_class_nm,a.org_class_id"
           . " FROM ".XOCP_PREFIX."orgs a"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . ($parent_id=="all"?"":" WHERE a.parent_id = '$parent_id'");
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($org_id,$org_nm,$org_abbr,$org_class_nm,$org_class_id)=$db->fetchRow($result)) {
            if($org_class_id>=3) {
               $_SESSION["hris_subdiv"][$org_class_id][$org_id] = array($org_id,$org_nm,$org_abbr,$org_class_nm);
            }
            $this->recurseDivision($org_id);
         }
      }
   }
   
   function getAllJobs() {
      $db=&Database::getInstance();
      $division_id = $_SESSION["hris_posmatrix_division"];
      $subdiv_id = $_SESSION["hris_posmatrix_subdivision"];
      
      $_SESSION["hris_subdiv"] = array();
      if($division_id=="all") {
         foreach($_SESSION["hris_division_allow"] as $division_org_id=>$a) {
            $this->recurseDivision($division_org_id);
         }
      } else {
         $this->recurseDivision($_SESSION["hris_posmatrix_division"]);
      }
      
      ksort($_SESSION["hris_subdiv"]);
      
      $_SESSION["hris_poslevel"] = array();
      $_SESSION["hris_jobs"] = array();
      
      /// subdivision jobs
      if($subdiv_id>0) {
         $sql = "SELECT a.job_id,a.job_nm,a.job_abbr,a.job_class_id,b.job_class_nm,c.org_nm,d.org_class_nm,c.org_abbr,b.job_class_level,a.summary"
              . " FROM ".XOCP_PREFIX."jobs a"
              . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
              . " LEFT JOIN ".XOCP_PREFIX."orgs c ON c.org_id = a.org_id"
              . " LEFT JOIN ".XOCP_PREFIX."org_class d USING(org_class_id)"
              . " WHERE a.org_id = '$subdiv_id'";
         $result = $db->query($sql);
         
         if($db->getRowsNum($result)>0) {
            while(list($job_id,$job_nm,$job_abbr,$job_class_id,$job_class_nm,$org_nm,$org_class_nm,$org_abbr,$job_class_level,$summary)=$db->fetchRow($result)) {
               $_SESSION["hris_poslevel"][$job_class_id] = array($job_class_id,$job_class_nm,$job_class_level);
               $_SESSION["hris_jobs"][$job_class_id][$job_id] = array($job_id,$job_nm,$job_abbr,$job_class_id,$job_class_nm,$org_nm,$org_class_nm,$org_abbr,$job_class_level,$summary);
            }
         }
      } else {
         
         /// division jobs
         if($division_id=="all") {
            foreach($_SESSION["hris_division_allow"] as $division_org_id=>$a) {
               $sql = "SELECT a.job_id,a.job_nm,a.job_abbr,a.job_class_id,b.job_class_nm,c.org_nm,d.org_class_nm,c.org_abbr,b.job_class_level,a.summary"
                    . " FROM ".XOCP_PREFIX."jobs a"
                    . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
                    . " LEFT JOIN ".XOCP_PREFIX."orgs c ON c.org_id = a.org_id"
                    . " LEFT JOIN ".XOCP_PREFIX."org_class d USING(org_class_id)"
                    . " WHERE a.org_id = '$division_org_id'";
               $result = $db->query($sql);
               if($db->getRowsNum($result)>0) {
                  while(list($job_id,$job_nm,$job_abbr,$job_class_id,$job_class_nm,$org_nm,$org_class_nm,$org_abbr,$job_class_level,$summary)=$db->fetchRow($result)) {
                     $_SESSION["hris_poslevel"][$job_class_id] = array($job_class_id,$job_class_nm,$job_class_level);
                     $_SESSION["hris_jobs"][$job_class_id][$job_id] = array($job_id,$job_nm,$job_abbr,$job_class_id,$job_class_nm,$org_nm,$org_class_nm,$org_abbr,$job_class_level,$summary);
                  }
               }
            }
         } else {
            $sql = "SELECT a.job_id,a.job_nm,a.job_abbr,a.job_class_id,b.job_class_nm,c.org_nm,d.org_class_nm,c.org_abbr,b.job_class_level,a.summary"
                 . " FROM ".XOCP_PREFIX."jobs a"
                 . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
                 . " LEFT JOIN ".XOCP_PREFIX."orgs c ON c.org_id = a.org_id"
                 . " LEFT JOIN ".XOCP_PREFIX."org_class d USING(org_class_id)"
                 . " WHERE a.org_id = '$division_id'";
            $result = $db->query($sql);
            if($db->getRowsNum($result)>0) {
               while(list($job_id,$job_nm,$job_abbr,$job_class_id,$job_class_nm,$org_nm,$org_class_nm,$org_abbr,$job_class_level,$summary)=$db->fetchRow($result)) {
                  $_SESSION["hris_poslevel"][$job_class_id] = array($job_class_id,$job_class_nm,$job_class_level);
                  $_SESSION["hris_jobs"][$job_class_id][$job_id] = array($job_id,$job_nm,$job_abbr,$job_class_id,$job_class_nm,$org_nm,$org_class_nm,$org_abbr,$job_class_level,$summary);
               }
            }
         }
         
         foreach($_SESSION["hris_subdiv"] as $org_class_idx=>$orgs) {
            foreach($orgs as $org_idx=>$v) {
               $sql = "SELECT a.job_id,a.job_nm,a.job_abbr,a.job_class_id,b.job_class_nm,c.org_nm,d.org_class_nm,c.org_abbr,b.job_class_level,a.summary"
                    . " FROM ".XOCP_PREFIX."jobs a"
                    . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
                    . " LEFT JOIN ".XOCP_PREFIX."orgs c ON c.org_id = a.org_id"
                    . " LEFT JOIN ".XOCP_PREFIX."org_class d USING(org_class_id)"
                    . " WHERE a.org_id = '$org_idx'";
               $result = $db->query($sql);
               if($db->getRowsNum($result)>0) {
                  while(list($job_id,$job_nm,$job_abbr,$job_class_id,$job_class_nm,$org_nm,$org_class_nm,$org_abbr,$job_class_level,$summary)=$db->fetchRow($result)) {
                     $_SESSION["hris_poslevel"][$job_class_id] = array($job_class_id,$job_class_nm,$job_class_level);
                     $_SESSION["hris_jobs"][$job_class_id][$job_id] = array($job_id,$job_nm,$job_abbr,$job_class_id,$job_class_nm,$org_nm,$org_class_nm,$org_abbr,$job_class_level,$summary);
                  }
               }
            }
         }
      }
   }
   
   
   
   function requestList() {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $user_id = getUserID();
      $assessor_job_count = 0;
      global $proficiency_level_name;
      
      $_SESSION["html"]->js_scriptaculous_effecs=TRUE;
      
      $arr_compgroup = array();
      
      $y = $_GET["y"]+0;
      $d = $_GET["d"]+0;
      $s = $_GET["s"]+0;
      $p = $_GET["p"]+0;
      $_SESSION["hris_posmatrix_year"] = $y;
      $_SESSION["hris_posmatrix_division"] = $d;
      $_SESSION["hris_posmatrix_subdivision"] = $s;
      $_SESSION["hris_posmatrix_poslevel"] = $p;
      
      if($d!=$_SESSION["hris_posmatrix_division"]) {
         $_SESSION["hris_posmatrix_subdivision"] = 0;
      } else {
         $_SESSION["hris_posmatrix_subdivision"] = $s;
      }
      
      $_SESSION["hris_subdiv"] = array();
      
      if($_SESSION["hris_posmatrix_division"]=="all") {
         foreach($_SESSION["hris_division_allow"] as $division_org_id=>$a) {
            $this->recurseDivision($division_org_id);
         }
      } else {
         $this->recurseDivision($_SESSION["hris_posmatrix_division"]);
      }
      
      $_SESSION["hris_division_allow"] = array();
      
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
      
      if($_SESSION["arm_levelmatrix"]==3) {
         $sql = "SELECT a.org_id,b.org_class_id FROM ".XOCP_PREFIX."jobs a LEFT JOIN ".XOCP_PREFIX."orgs b USING(org_id) WHERE a.job_id = '$self_job_id'";
         $result = $db->query($sql);
         list($org_id,$org_class_id)=$db->fetchRow($result);
         if($org_class_id>=3) {
            list($division_org_id,$division_org_nm,$division_org_abbr,$division_org_class_nm,$division_org_class_id,$parent_id) = $this->getDivisionUp($org_id);
            $_SESSION["hris_division_allow"][$division_org_id] = 1;
         } else {
            $this->getDivisionDown($org_id);
         }
         $_SESSION["hris_posmatrix_division"] = $division_org_id;
      } else if($_SESSION["arm_levelmatrix"]==0) {
         $sql = "SELECT org_id FROM ".XOCP_PREFIX."orgs WHERE org_class_id = '3' ORDER BY order_no";
         $result = $db->query($sql);
         if($db->getRowsNum($result)>0) {
            while(list($division_org_id)=$db->fetchRow($result)) {
               $_SESSION["hris_division_allow"][$division_org_id] = 1;
            }
         }
      }
      
      $cookie_empx = $_COOKIE["empx"]+0;
      $cookie_jobx = $_COOKIE["jobx"]+0;
      
      
      if($_SESSION["arm_levelmatrix"]==0&&!isset($_SESSION["hris_posmatrix_division"])) {
         $_SESSION["hris_posmatrix_division"] = 14;
      }
      
      if(!isset($_SESSION["hris_posmatrix_subdivision"])) {
         $_SESSION["hris_posmatrix_subdivision"] = 0;
      }
      
      /// DIVISION SELECT
      $sql = "SELECT org_id,org_nm,org_abbr FROM ".XOCP_PREFIX."orgs WHERE org_class_id = '3'";
      $result = $db->query($sql);
      $optdiv = "<option value='all'>All</option>";
      if($db->getRowsNum($result)>0) {
         while(list($org_id,$org_nm,$org_abbr)=$db->fetchRow($result)) {
            if($_SESSION["arm_levelmatrix"]==3&&!isset($_SESSION["hris_division_allow"][$org_id])) {
               continue;
            }
            
            if($_SESSION["hris_posmatrix_division"]!="all"&&$_SESSION["hris_posmatrix_division"]==0) {
               $_SESSION["hris_posmatrix_division"] = $org_id;
            }
            
            
            $optdiv .= "<option value='$org_id' ".($org_id==$_SESSION["hris_posmatrix_division"]?"selected='1'":"").">$org_nm</option>";
         }
      }
      
      $division_id = $_SESSION["hris_posmatrix_division"];
      $subdiv_id = $_SESSION["hris_posmatrix_subdivision"];
      $poslevel_id = $_SESSION["hris_posmatrix_poslevel"];
      
      /// SUBDIVISION SELECT
      
      $optsubdiv = "<option value='0'>All</option>";
      
      $_SESSION["hris_section_allow"] = array();
      
      //foreach($_SESSION["hris_division_allow"] as $division_org_idx=>$a) {
         $_SESSION["hris_subdiv"] = array();
         $this->recurseDivision($division_id);
         ksort($_SESSION["hris_subdiv"]);
         foreach($_SESSION["hris_subdiv"] as $org_class_idx=>$orgs) {
            foreach($orgs as $org_idx=>$v) {
               list($org_id,$org_nm,$org_abbr,$org_class_nm)=$v;
               $optsubdiv .= "<option value='$org_id' ".($org_id==$_SESSION["hris_posmatrix_subdivision"]?"selected='1'":"").">$org_nm $org_class_nm</option>";
               $_SESSION["hris_section_allow"][$org_id] = 1;
            }
         }
      //}
      
      /// POSITION SELECT
      $_SESSION["hris_poslevel"] = array();
      $_SESSION["hris_jobs"] = array();
      $this->getAllJobs();
      
      $optlevel = "<option value='0'>All</option>";
      foreach($_SESSION["hris_poslevel"] as $level) {
         list($job_class_id,$job_class_nm)=$level;
         $optlevel .= "<option value='$job_class_id' ".($_SESSION["hris_posmatrix_poslevel"]==$job_class_id?"selected='1'":"").">$job_class_nm</option>";
      }

      //// YEAR SELECT
      $sql = "SELECT id FROM hris_global_sess WHERE status_cd = 'normal' ORDER BY id DESC";
      $result = $db->query($sql);
      $optyear = "<option value='0'>All</option>";
      while (list($year)=$db->fetchRow($result)) {
        $optyear .= "<option value='$year' ".($_SESSION["hris_posmatrix_year"]==$year?"selected='1'":"").">$year</option>";
      }
      if ($_SESSION["hris_posmatrix_year"] != 0) {
        $filteryear = "AND YEAR(requested_dttm) = '".$_SESSION["hris_posmatrix_year"]."'";
      }

      
      //// FORM QUERY
      $query = "<table style='width:100%;' class='xxfrm'>"
             . "<colgroup><col width='200'/><col/></colgroup>"
             . "<tbody>"
             . "<tr><td>Year :</td><td><select id='selyear' onchange='set_pos();'>$optyear</select></td></tr>"
             . "<tr><td>Division :</td><td><select id='seldivision' onchange='set_pos();'>$optdiv</select></td></tr>"
             . "<tr><td>Section/Unit :</td><td><select id='selsubdivision' onchange='set_pos()'>$optsubdiv</select></td></tr>"
             . "<tr><td>Position Level :</td><td><select id='selposlevel' onchange='set_pos()'>$optlevel</select></td></tr>"
             . "<tr><td colspan='2' style='padding:5px;'><input type='button' value='Print' onclick='print_list();'/></td></tr>"
             . "</tbody></table>";
      
      
      //// load matrix
      $jobs = array();
      if(is_array($_SESSION["hris_jobs"])) {
         uksort($_SESSION["hris_jobs"],"sort_job");
         foreach($_SESSION["hris_jobs"] as $job_class_idx=>$v) {
            foreach($v as $job_idx=>$w) {
               list($job_id,$job_nm,$job_abbr,$job_class_id,$job_class_nm)=$w;
               if($poslevel_id==0) {
                  $jobs[$job_id] = $w;
               } else if($poslevel_id==$job_class_id) {
                  $jobs[$job_id] = $w;
               }
            }
         }
      }
      
      
      $ret = "<table class='xxlist' style='width:100%;'>"
           . "<thead>"
           . "<tr>"
           //. "<td>ID</td>"
           . "<td>Employee</td>"
           . "<td style='text-align:right;'>NIP</td>"
           . "<td>Time Frame</td>"
           . "<td>Requested Date</td>"
           . "<td style='text-align:left;padding-right:5px;'>Elapsed Time</td>"
           . "<td style='text-align:left;'>Progress</td>"
           . "<td>Status</td>"
           . "</tr>"
           . "</thead>"
           . "<tbody>";
      
      foreach($jobs as $job_idx=>$job) {
         list($job_id,$job_nm,$job_abbr,$job_class_id,$job_class_nm,$org_nm,$org_class_nm,$org_abbr,$jlvl,$summary)=$job;
         
         $sql = "SELECT a.employee_id,b.employee_ext_id,c.person_nm"
              . " FROM ".XOCP_PREFIX."employee_job a"
              . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
              . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
              . " WHERE a.job_id = '$job_id'"
              . " ORDER BY c.person_nm";
         $remp = $db->query($sql);
         
         if($db->getRowsNum($remp)>0) {
            while(list($employee_id,$nip,$employee_nm)=$db->fetchRow($remp)) {
               $sql = "SELECT request_id,employee_id,approve_superior_id,approve_superior_dttm,approve_hris_id,approve_hris_dttm,cost_estimate,requested_dttm,status_cd"
                    . " FROM ".XOCP_PREFIX."idp_request"
                    . " WHERE employee_id = '$employee_id' $filteryear"
                    . " ORDER BY requested_dttm";
               $rreq = $db->query($sql);
               
               if($db->getRowsNum($rreq)>0) {
                  
                  
         if($old_job_class!=$job_class_id) {
            $ret .= "<tr>";
            $ret .= "<td colspan='7' style='font-weight:bold;color:black;padding:4px;border-left:1px solid white;border-right:0px solid transparent;'>"
                  . "$job_class_nm"
                  . "</td>";
            $ret .= "</tr>";
         }
         
         $old_job_class = $job_class_id;
         
                  
                  
                  while(list($request_id,$employee_id,$approve_superior_id,$approve_superior_dttm,$approve_hris_id,$approve_hris_dttm,$cost_estimate,$requested_dttm,$status_cd)=$db->fetchRow($rreq)) {
                     if($status_cd=="rejected") continue;
                     if($status_cd=="nullified") continue;
                     if($status_cd=="completed") continue;
                     
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
                     switch($status_cd) {
                        case "start":
                           $req_status = "New Request";
                           break;
                        case "employee":
                           $req_status = "Employee Request";
                           break;
                        case "approval1":
                           $req_status = "Superior Approval";
                           break;
                        case "approval2":
                           $req_status = "Next Superior Approval";
                           break;
                        case "approval3":
                           $req_status = "HR Approval";
                           break;
                        case "implementation":
                           $req_status = "Implementation";
                           break;
                        case "completed":
                           $req_status = "Completed";
                           break;
                        default:
                           break;
                     }
                     
                     if($cntaap>0) {
                        $progress_qty = ceil(bcmul(100,bcdiv($cntaapc,$cntaap)));
                        if($progress_qty>100) $progress_qty = 100;
                        $progress_qty_txt = toMoneyShort($progress_qty)."%";
                     } else {
                        $progress_qty_txt = "0%";
                        $progress_qty = 0;
                     }
                     
                     $link = "<a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&req=y&e=${employee_id}&j=${job_id}'>$req_status</a>&nbsp;";
                     
                     $sql = "SELECT b.person_nm,a.employee_ext_id FROM ".XOCP_PREFIX."employee a"
                          . " LEFT JOIN ".XOCP_PREFIX."persons b USING(person_id)"
                          . " WHERE employee_id = '$employee_id'";
                     $rempx = $db->query($sql);
                     if($db->getRowsNum($rempx)>0) {
                        list($employee_nm,$nip)=$db->fetchRow($rempx);
                     }
                     list($timeframe_start,$timeframe_stop)=_idp_get_timeframe($request_id);
                     
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
                     
                     $ret .= "<tr>"
                           //. "<td>$request_id</td>"
                           . "<td>$employee_nm</td>"
                           . "<td style='text-align:right;'>$nip</td>"
                           . "<td>".sql2ind($timeframe_start,"date")." - ".sql2ind($timeframe_stop,"date")."</td>"
                           . "<td>".sql2ind($requested_dttm,"date")."</td>"
                           . "<td style='text-align:left;padding-right:5px;'>"
                                 . "<div style='float:left;overflow:hidden;width:50px !important;border:1px solid #999999;margin:auto;text-align:left;'>"
                                 . "<img src='".XOCP_SERVER_SUBDIR."/modules/hris/images/level_foreground.png' style='width:50px;height:12px;margin-left:".((int)(-50+($progress_time/2)))."px;'/>"
                                 . "</div>"
                                 . "<div style='float:left;padding-left:3px;'>$progress_time_txt</div>"
                           . "</td>"
                           . "<td style='text-align:left;padding-right:5px;'>"
                                 . "<div style='float:left;overflow:hidden;width:50px !important;border:1px solid #999999;margin:auto;text-align:left;'>"
                                 . "<img src='".XOCP_SERVER_SUBDIR."/modules/hris/images/level_foreground.png' style='width:50px;height:12px;margin-left:".((int)(-50+($progress_qty/2)))."px;'/>"
                                 . "</div>"
                                 . "<div style='float:left;padding-left:3px;'>$progress_qty_txt</div>"
                            . "</td>"
                           . "<td>$link</td>"
                           . "</tr>";
                  }
               }
            }
         }
      }
      
      
      
      /////
      
      
      
      $ret .= "</tbody></table>";
      
      
      $js = "<script type='text/javascript'><!--
         
         function print_list() {
            var division = $('seldivision').options[$('seldivision').selectedIndex].value;
            var subdivision = $('selsubdivision').options[$('selsubdivision').selectedIndex].value;
            var poslevel = $('selposlevel').options[$('selposlevel').selectedIndex].value;
            location.href = '".XOCP_SERVER_SUBDIR."/modules/hris/print/idpmonitor.php?d='+division+'&s='+subdivision+'&p='+poslevel;
         }
         
         function set_pos() {
            var year = $('selyear').options[$('selyear').selectedIndex].value;
            var division = $('seldivision').options[$('seldivision').selectedIndex].value;
            var subdivision = $('selsubdivision').options[$('selsubdivision').selectedIndex].value;
            var poslevel = $('selposlevel').options[$('selposlevel').selectedIndex].value;
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&y='+year+'&d='+division+'&s='+subdivision+'&p='+poslevel;
         }
         
      
      // --></script>";
      
      return $js.$query."<br/>".$ret;
      
   }
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            if(isset($_GET["smethod"])&&$_GET["smethod"]==1&&isset($_GET["t"])&&$_GET["t"]!="") {
               $_SESSION["hris_method_t"] = $_GET["t"];
               $ret = $this->requestList();
            } elseif(isset($_GET["r"])&&$_GET["r"]=="y"&&isset($_GET["e"])&&isset($_GET["j"])) {
               $employee_id = $_GET["e"]+0;
               $job_id = $_GET["j"]+0;
               $ret = $this->result($employee_id,$job_id);
            } elseif(isset($_GET["req"])&&$_GET["req"]=="y"&&isset($_GET["e"])) {
               include_once(XOCP_DOC_ROOT."/modules/hris/include/idp/idp.php");
               $employee_id = $_GET["e"]+0;
               $job_id = $_GET["j"]+0;
               $ret = _idp_view_request($employee_id,$job_id,TRUE,FALSE);
            } else if(isset($_GET["backlist"])&&$_GET["backlist"]==1) {
               unset($_SESSION["hris_method_t"]);
               $ret = $this->requestList();
            } else {
               $ret = $this->requestList();
            }
            break;
         default:
            $ret = $this->requestList();
            break;
      }
      return $ret."<div style='height:100px;'>&nbsp;</div>";
   }
}

} // HRIS_IDPHRAPPROVAL_DEFINED
