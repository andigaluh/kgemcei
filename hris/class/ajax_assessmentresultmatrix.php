<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_posmatrix.php                   //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_ASSESSMENTMRESULTMATRIXAJAX_DEFINED') ) {
   define('HRIS_ASSESSMENTMRESULTMATRIXAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");

function sort_job($job_class_id_a,$job_class_id_b) {
   
   list($job_class_idx,$job_class_nmx,$job_class_level_a) = $_SESSION["hris_poslevel"][$job_class_id_a];
   list($job_class_idx,$job_class_nmx,$job_class_level_b) = $_SESSION["hris_poslevel"][$job_class_id_b];
   
   if($job_class_level_a==$job_class_level_b) {
      return 0;
   }
   
   return ($job_class_level_a < $job_class_level_b) ? -1 : 1;
}

function sort_competency($a,$b) {
   global $job_rcl,$job_itj;
   $ax = 0;
   $bx = 0;
   $cnta = 0;
   $cntb = 0;
   list($a_competency_abbr,$a_competency_nm,$a_competency_id)=$a;
   list($b_competency_abbr,$b_competency_nm,$b_competency_id)=$b;
   foreach($job_rcl as $job_id=>$v) {
      $cnta++;
      if(isset($v[$a_competency_id])&&$v[$a_competency_id]>0) {
         $ax = 1;
         break;
      }
   }
   foreach($job_rcl as $job_id=>$v) {
      $cntb++;
      if(isset($v[$b_competency_id])&&$v[$b_competency_id]>0) {
         $bx = 1;
         break;
      }
   }
   if ($cnta == $cntb) {
      return strcmp($a_competency_abbr,$b_competency_abbr);
   }
   return ($cnta < $cntb) ? -1 : 1;
}
                    
class _hris_class_AssessmentResultMatrixAjax extends AjaxListener {
   
   function _hris_class_AssessmentResultMatrixAjax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/hris/class/ajax_assessmentresultmatrix.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_setPosition","app_loadMatrix","app_setCompetencyClass","app_setCompetencyGroup",
                                             "app_loadDetail","app_viewChartPos","app_viewChartProactiveIndex");
   }
   
   function app_viewChartProactiveIndex($args) {
      $db=&Database::getInstance();
      $division_id = $args[0];
      $subdiv_id = $args[1];
      $poslevel_id = $args[2];
      $asid = $args[3];
      $aposlevel = $args[4];
      
      $_SESSION["spider_chart_data"] = array();
      
      $ret = "";
      
      $divarr = array();
      
      $compdiv = array();
      
      $org_nm = array();
      
      $param = array();
      
      if(isset($_SESSION["spider_chart"])) {
         foreach($_SESSION["spider_chart"] as $employee_id=>$v0) {
            foreach($v0 as $job_id=>$v1) {
               $sql = "SELECT org_id,job_class_id FROM ".XOCP_PREFIX."jobs WHERE job_id = '$job_id'";
               $result = $db->query($sql);
               list($org_id,$job_class_id)=$db->fetchRow($result);
               
               list($division_org_id,$division_org_nm,$division_org_abbr,$division_org_class_nm,$division_org_class_id,$parent_id) = $this->getDivision($org_id);
               $divarr[$division_org_id] = array($division_org_id,$division_org_nm,$division_org_abbr,$division_org_class_nm,$division_org_class_id,$parent_id);
               
               $sql = "SELECT jm,cf FROM ".XOCP_PREFIX."employee_competency_final_recap"
                    . " WHERE asid = '$asid'"
                    . " AND employee_id = '$employee_id'"
                    . " AND job_id = '$job_id'";
               $rc = $db->query($sql);
               if($db->getRowsNum($rc)>0) {
                  list($match,$cf)=$db->fetchRow($rc);
               } else {
                  $match = 0;
                  $cf = 0;
               }
               
               foreach($v1 as $competency_id=>$v2) {
                  switch($competency_id) {
                     case 3:
                     case 4:
                     case 7:
                        list($employee_id,$job_id,$competency_idx,$ccl,$rcl,$itj,$gap)=$v2;
                        $compdiv[$job_class_id][$competency_idx]["ccl"] += $ccl;
                        $compdiv[$job_class_id][$competency_idx]["ccl_count"]++;
                        $compdiv[$job_class_id][$competency_idx]["rcl"] += $rcl;
                        $compdiv[$job_class_id][$competency_idx]["rcl_count"]++;
                        break;
                     default:
                        break;
                  }
               }
            }
         }
      }
      
      $_SESSION["spider_chart_data"] = $compdiv;
      
      $_SESSION["spider_chart_divisions"] = $divarr;
      
      $ret = "<div><div style='padding:10px;background-color:#6d84b4;color:white;text-align:center;font-weight:bold;'>Proactive Index</div>"
           . "<div style='padding:10px;height:463px;overflow:auto;'>";
      
      
      $no = 0;
      $ret .= "<table style='width:100%;'><colgroup><col width='50%'/><col/></colgroup><tbody>";
      
      $sql = "SELECT job_class_id,job_class_abbr,job_class_nm FROM ".XOCP_PREFIX."job_class WHERE job_class_level >= '40' ".($aposlevel!="all"?" AND job_class_id = '$aposlevel'":"")." ORDER BY job_class_level";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($job_class_id,$job_class_abbr,$job_class_nm)=$db->fetchRow($result)) {
            if(!isset($compdiv[$job_class_id])) continue;
            
            if($no%2==1) {
               $ret .= "</td><td>";
            } else {
               if($no>0) {
                  $ret .= "</tr>";
               }
               $ret .= "<tr><td>";
            }
            $ret .= "<div style='margin-bottom:10px;text-align:center;padding-top:0px;height:328px;width:330px;border:1px solid #888;-moz-border-radius:5px;-moz-box-shadow:0px 1px 5px #888;position:relative;left:50%;margin-left:-165px;'>"
                 . "<div style='padding:5px;text-align:center;background-color:#eee;-moz-border-radius:5px 5px 0 0;border-bottom:1px solid #bbb;'>$job_class_nm</div>"
                 . "<img src='".XOCP_SERVER_SUBDIR."/modules/hris/spider_chart_proactive.php?job_class_id=${job_class_id}&i=".uniqid()."'/>"
                 . "</div>";
            $no++;
         }
      }
      
      $ret .= "</tbody></table>";
      $ret .= "</div>"
           . "<div style='background-color:#eee;border-top:1px solid #bbb;text-align:center;padding:10px;'><input type='button' onclick='spchartbox.fade();' value='Close'/></div>"
           
           . "</div>";
      
      return array($ret,0);
   }
   
   function app_viewChartPos($args) {
      $db=&Database::getInstance();
      $division_id = $args[0];
      $subdiv_id = $args[1];
      $poslevel_id = $args[2];
      $asid = $args[3];
      $aposlevel_id = $args[4];
      
      
      $_SESSION["spider_chart_data"] = array();
      
      $ret = "";
      
      $divarr = array();
      
      $compdiv = array();
      
      $org_nm = array();
      
      $param = array();
      
      if(isset($_SESSION["spider_chart"])) {
         foreach($_SESSION["spider_chart"] as $employee_id=>$v0) {
            foreach($v0 as $job_id=>$v1) {
               $sql = "SELECT org_id,job_class_id FROM ".XOCP_PREFIX."jobs WHERE job_id = '$job_id'";
               $result = $db->query($sql);
               list($org_id,$job_class_id)=$db->fetchRow($result);
               if($job_class_id!=$aposlevel_id) continue;
               list($division_org_id,$division_org_nm,$division_org_abbr,$division_org_class_nm,$division_org_class_id,$parent_id) = $this->getDivision($org_id);
               $divarr[$division_org_id] = array($division_org_id,$division_org_nm,$division_org_abbr,$division_org_class_nm,$division_org_class_id,$parent_id);
               
               $param[$division_org_id][$job_class_id] = 1;
               
               $sql = "SELECT jm,cf FROM ".XOCP_PREFIX."employee_competency_final_recap"
                    . " WHERE asid = '$asid'"
                    . " AND employee_id = '$employee_id'"
                    . " AND job_id = '$job_id'";
               $rc = $db->query($sql);
               if($db->getRowsNum($rc)>0) {
                  list($match,$cf)=$db->fetchRow($rc);
               } else {
                  $match = 0;
                  $cf = 0;
               }
               
               foreach($v1 as $competency_id=>$v2) {
                  list($employee_id,$job_id,$competency_idx,$ccl,$rcl,$itj,$gap)=$v2;
                  $compdiv[$division_org_id][$job_class_id][$competency_idx]["ccl"] += $ccl;
                  $compdiv[$division_org_id][$job_class_id][$competency_idx]["ccl_count"]++;
                  $compdiv[$division_org_id][$job_class_id][$competency_idx]["rcl"] += $rcl;
                  $compdiv[$division_org_id][$job_class_id][$competency_idx]["rcl_count"]++;
               }
            }
         }
      }
      
      $_SESSION["spider_chart_data"] = $compdiv;
      
      $_SESSION["spider_chart_divisions"] = $divarr;
      
      /*
      

+--------+----------+-----------------+----------+
| org_id | org_abbr | org_nm          | order_no |
+--------+----------+-----------------+----------+
|     14 | ADM      | Administration  |      140 | 
|     15 | FIN      | Finance         |      150 | 
|     16 | MKT      | Marketing       |      160 | 
|     17 | MNT      | Maintenance     |      170 | 
|     18 | MFG1     | Manufacturing 1 |      180 | 
|     19 | MFG2     | Manufacturing 2 |      190 | 
|     20 | RCP      | RCP             |      200 | 
+--------+----------+-----------------+----------+


      */



      $orgs[0] = 18;   ////MFG1       ////
      $orgs[1] = 19;   ////MFG2       ////
      $orgs[2] = 17;   ////MNT        ////
      $orgs[3] = 20;   ////RCP        ////
      $orgs[4] = 16;   ////MKT        ////
      $orgs[5] = 14;   ////ADM        ////
      $orgs[6] = 15;   ////FIN        ////
      
      $sql = "SELECT job_class_nm FROM ".XOCP_PREFIX."job_class WHERE job_class_id = '$aposlevel_id'";
      $result =$db->query($sql);
      list($job_class_nm)=$db->fetchRow($result);
                                          
      $ret = "<div><div style='padding:10px;background-color:#6d84b4;color:white;text-align:center;font-weight:bold;'>Division Assessment Result / Position Level $job_class_nm</div>"
           . "<div style='padding:10px;height:463px;overflow:auto;'>";
      
      $_SESSION["var_print"] = array($orgs,$param,$divarr);
      
      $no = 0;
      $ret .= "<table style='width:100%;'><colgroup><col width='50%'/><col/></colgroup><tbody>";
      foreach($orgs as $nourut=>$division_org_idx) {
         if(!isset($param[$division_org_idx])) continue;
         $div = $param[$division_org_idx];
         list($division_org_id,$division_org_nm,$division_org_abbr,$division_org_class_nm,$division_org_class_id,$parent_id)=$divarr[$division_org_idx];
         foreach($div as $job_class_id=>$div2) {
            if($no%2==1) {
               $ret .= "</td><td>";
            } else {
               if($no>0) {
                  $ret .= "</tr>";
               }
               $ret .= "<tr><td>";
            }
            $ret .= "<div style='margin-bottom:10px;text-align:center;padding-top:0px;height:328px;width:330px;border:1px solid #888;-moz-border-radius:5px;-moz-box-shadow:0px 1px 5px #888;position:relative;left:50%;margin-left:-165px;'>"
                 . "<div style='padding:5px;text-align:center;background-color:#eee;-moz-border-radius:5px 5px 0 0;border-bottom:1px solid #bbb;'>$division_org_nm</div>"
                 . "<img src='".XOCP_SERVER_SUBDIR."/modules/hris/spider_chart_pos.php?division_org_id=${division_org_idx}&job_class_id=${job_class_id}&i=".uniqid()."'/>"
                 . "</div>";
            $no++;
         }
      }
      $ret .= "</tbody></table>";
      $ret .= "</div>"
           . "<div style='background-color:#eee;border-top:1px solid #bbb;text-align:center;padding:10px;'>"
           . "<input type='button' value='Print' onclick='print_viewchartpos(\"$division_id\",\"$subdiv_id\",\"$poslevel_id\",\"$asid\",\"$aposlevel_id\",this,event);'/>&nbsp;"
           . "<input type='button' onclick='spchartbox.fade();' value='Close'/>"
           . "</div>"
           
           . "</div>";
      
      return array($ret,0);
   }
   
   function app_loadDetail($args) {
      require_once(XOCP_DOC_ROOT."/modules/hris/assessmentresult.php");
      $asr = new _hris_AssessmentResult();
      $db=&Database::getInstance();
      $employee_id = $args[0];
      $asid = $args[1]+0;
      $sql = "SELECT job_id FROM ".XOCP_PREFIX."assessment_session_job WHERE employee_id = '$employee_id' AND asid = '$asid'";
      $result = $db->query($sql);
      $job_id = 0;
      if($db->getRowsNum($result)==1) {
         list($job_id)=$db->fetchRow($result);
      }
      if($job_id>0) {
         $sql = "SELECT session_nm,session_periode FROM ".XOCP_PREFIX."assessment_session WHERE asid = '$asid'";
         $result = $db->query($sql);
         list($session_nm,$session_periode)=$db->fetchRow($result);
         $ret = $asr->result($employee_id,$job_id,$asid,TRUE);
         return $ret;
      }
      return _EMPTY;
      
   }
   
   function app_setCompetencyGroup($args) {
      $_SESSION["hris_posmatrix_compgroup"] = $args[0];
   }
   
   function app_setCompetencyClass($args) {
      $compgroup_id = $_SESSION["hris_posmatrix_compgroup"];
      $_SESSION["hris_posmatrix_compclass"][$compgroup_id] = $args[0];
   }
   
   function app_loadMatrix($args) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/assessment.php");
      $db=&Database::getInstance();
      $division_id = $args[0];
      $subdiv_id = $args[1];
      $poslevel_id = $args[2];
      $asid = $args[3];
      
      unset($_SESSION["mtrx_ccl"]);
      $_SESSION["mtrx_ccl"] = array();
      unset($_SESSION["spider_chart"]);
      $_SESSION["spider_chart"] = array();
      unset($_SESSION["spider_chart_competency"]);
      $_SESSION["spider_chart_competency"] = array();
      
      
      
      $wpx = "20px";
      $wcpx = "50px";
      
      $tips0 = array();
      $tips1 = array();
      
      $jmttl_count = 0;
      $jmttl_sum = 0;
      $cfttl_count = 0;
      $cfttl_sum = 0;
      
      $matrix = array();
      
      $ret = "";
      
      if(!isset($_SESSION["hris_posmatrix_compgroup"])) {
         $_SESSION["hris_posmatrix_compgroup"] = 1;
      }
      $compgroup_id = $_SESSION["hris_posmatrix_compgroup"];
      
      $xset = array();
      $sql = "SELECT competency_class_set FROM ".XOCP_PREFIX."compgroup WHERE compgroup_id = '$compgroup_id'";
      $result = $db->query($sql);
      list($class_set)=$db->fetchRow($result);
      $xset = explode(",",$class_set);
      if(!isset($_SESSION["hris_posmatrix_compclass"][$compgroup_id])) {
         $_SESSION["hris_posmatrix_compclass"][$compgroup_id] = $xset[0];
      }
      $competency_class = $_SESSION["hris_posmatrix_compclass"][$compgroup_id];
      
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
      
      $hdr = array();
      global $job_rcl,$job_itj;
      $job_rcl = array();
      $job_itj = array();
      $job_ttl_rcl = array();
      foreach($jobs as $job_idx=>$v) {
         $sql = "SELECT a.rcl,a.itj,b.competency_id,b.competency_nm,b.competency_abbr,b.desc_en,b.desc_id"
              . " FROM ".XOCP_PREFIX."job_competency a"
              . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
              . " WHERE a.job_id = '$job_idx'"
              . " AND b.compgroup_id = '$compgroup_id'"
              . " AND b.competency_class = '$competency_class'"
              . " ORDER BY b.competency_abbr";
         $result = $db->query($sql);
         if($db->getRowsNum($result)>0) {
            while(list($rcl,$itj,$competency_id,$competency_nm,$competency_abbr,$desc_en,$desc_id)=$db->fetchRow($result)) {
               if(!isset($job_ttl_rcl[$job_idx])) {
                  $job_ttl_rcl[$job_idx] = 0;
               }
               $hdr[$competency_id] = array($competency_abbr,$competency_nm,$competency_id,$desc_en,$desc_id);
               $job_rcl[$job_idx][$competency_id] = $rcl;
               $job_itj[$job_idx][$competency_id] = $itj;
               $job_ttl_rcl[$job_idx] += $rcl;
            }
         }
      }
      
      uasort($hdr,"sort_competency");
      //arsort($hdr);
      $ret  = "<table style='border-spacing:0px;'><tbody><tr><td style='padding:0px;'>" ///// frame
            
            . "<table style='border-spacing:0px;width:100%;table-layout:fixed;'>"
            . "<colgroup>"
            . "<col width='108'/>"
            . "<col width='63'/>"
            . "<col width='63'/>"
            . "<col width='53'/>"
            . "<col width='53'/>"
            . "<col width='*'/>"
            . "</colgroup>"
            . "<tbody>"
            . "<tr><td style='height:80px;border:1px solid #888;padding:4px;text-align:center;font-weight:bold;background-color:#eeeeee;color:black;'>"
            . "<div style='width:100px !important;text-align:center;'>Employee</div>"
            . "</td>"
            . "<td style='border:1px solid #888;border-left:0px;padding:4px;text-align:center;font-weight:bold;background-color:#f0f0f0;color:black;'>"
            . "<div style='width:55px !important;text-align:center;'>Job</div>"
            . "</td>"
            
            . "<td style='border:1px solid #888;border-left:0px;padding:4px;text-align:center;font-weight:bold;background-color:#f7f7f7;color:black;'>"
            . "<div style='width:55px !important;text-align:center;'>Section</div>"
            . "</td>"
            
            . "<td style='border:1px solid #888;border-left:0px;padding:4px;text-align:center;font-weight:bold;background-color:#fcfcfc;color:black;'>"
            . "<div style='text-align:center;width:45px !important;'>Job<br/>Match<br/>(%)</div>"
            . "</td>"
            
            . "<td style='border:1px solid #888;border-left:0px;padding:4px;text-align:center;font-weight:bold;background-color:#fcfcfc;color:black;'>"
            . "<div style='text-align:center;width:45px !important;'>Comp.<br/>Fit<br/>(%)</div>"
            . "</td>"
            
            . "<td style='background-color:#eee;padding:0px;border-right:1px solid #888;border-top:1px solid #888;padding-top:10px;' class='thx'><div><div>"; ///////////////////// TAB HEADER
      /////////////////////////////////////////////////////////////////////////////////////////
      /////////////////////////////////////////////////////////////////////////////////////////
      
      $ret .= "<table style='border-spacing:0px;'>"
            . "<tbody>"
            . "<tr>"
            
            . "<td style='vertical-align:bottom;padding:0px;'>"
            . "<ul class='axul' style='margin-left:10px;margin-top:5px;'>"
            . "<li id='tabcg_1' style='width:100px;' onclick='swcg(\"1\",this,event);' class='compsel'><span>General</span></li>"
            . "<li id='tabcg_2' style='width:100px;' onclick='swcg(\"2\",this,event);'><span>Managerial</span></li>"
            . "<li id='tabcg_3' style='width:100px;' onclick='swcg(\"3\",this,event);'><span>Specific</span></li>"
            . "</ul>"
            . "<div style='clear:both;border-top:1px solid #888;'></div>"
            . "</td>"
            
            . "</tr>"
            . "<tr><td style='border-right:1px solid #888;padding:0px;'>";
      
      $div_general = $div_managerial = $div_specific = "";
      $div_general .= "<div id='cgdiv_1' style='padding:0px;background-color:#f7f7f7;'>"
                    . "<div style='padding-top:9px;'>"
                    . "<ul id='ulcc_1' style='margin-left:15px;' class='axul'>"
                    . "<li id='tabcc_1_soft' onclick='swcc(\"1\",\"soft\",this,event);' style='width:100px;' class='ccsel'><span>Soft</span></li>"
                    . "<li id='tabcc_1_technical' onclick='swcc(\"1\",\"technical\",this,event);' style='width:100px;'><span>Technical</span></li>"
                    . "<li style='visibility:hidden;'><span>&nbsp;</span></li>"
                    . "</ul><div style='clear:both;border-top:1px solid #888;'></div></div></div>";
      $div_managerial .= "<div id='cgdiv_2' style='padding:0px;background-color:#f7f7f7;display:none;'>"
                    . "<div style='padding-top:9px;'>"
                    . "<ul id='ulcc_2' style='margin-left:15px;' class='axul'>"
                    . "<li id='tabcc_2_soft' onclick='swcc(\"2\",\"soft\",this,event);' style='width:100px;' class='ccsel'><span>Soft</span></li>"
                    . "<li id='tabcc_2_technical' onclick='swcc(\"2\",\"technical\",this,event);' style='width:100px;'><span>Technical</span></li>"
                    . "<li style='visibility:hidden;'><span>&nbsp;</span></li>"
                    . "</ul><div style='clear:both;border-top:1px solid #888;'></div></div></div>";
      $div_specific .= "<div id='cgdiv_3' style='padding:0px;background-color:#f7f7f7;display:none;'>"
                    . "<div style='padding-top:9px;'>"
                    . "<ul id='ulcc_3' style='margin-left:15px;' class='axul'>"
                    . "<li id='tabcc_3_technical' style='width:100px;' class='ccsel'><span>Technical</span></li>"
                    . "<li style='visibility:hidden;'><span>&nbsp;</span></li>"
                    . "</ul><div style='clear:both;border-top:1px solid #888;'></div></div></div>";
      
      $ret .= $div_general.$div_managerial.$div_specific."</td></tr>";
      
      
      $ret .= "<tr><td style='padding:0px;background-color:#f0f0f0;padding-top:5px;'>"
            . "<div style='border-bottom:1px solid #888;'>"
            . "<table class='comphdr'><tbody class='comphdr'><tr id='trcomphdr'>"
                     . "<td></td>"
                     . "<td></td>"
                     . "<td></td>"
                     . "<td></td>"
                     . "<td></td>"
                     . "<td></td>"
                     . "<td></td>"
                     . "<td></td>"
                     . "<td></td>"
                     . "<td></td>"
                     . "<td></td>"
                     . "<td></td>"
                     . "<td></td>"
                     . "<td></td>"
                     . "<td></td>"
                     . "<td></td>"
                     . "<td></td>"
                     . "<td></td>"
                     . "<td></td>"
                     . "<td></td>"
                     . "</tr></tbody></table>"
                     . "</div></td></tr>";
      
      
      $ret .= "</tbody></table>";
      
      
      
      
      
      //////// TAB HEADER - END
      /////////////////////////////////////////////////////////////////////////////////////////
      /////////////////////////////////////////////////////////////////////////////////////////
      $ret .= "</div></div></td>"
            . "</tr>";
      
      
      $ret .= "</tbody><tbody id='tbody_mtrx'>"; ////////////////////// split body
      
      $old_job_class = 0;
      $tooltip_job = $tooltip_org = $tooltip_emp = "";
      $arr_emp = array();
      foreach($jobs as $job_idx=>$job) {
         list($job_id,$job_nm,$job_abbr,$job_class_id,$job_class_nm,$org_nm,$org_class_nm,$org_abbr,$jlvl,$summary)=$job;
         
         ///////////////////// load competency data
         $sql = "SELECT a.rcl,a.itj,a.competency_id,b.compgroup_id,b.competency_cd,"
              . "b.competency_abbr,b.competency_nm,b.competency_class,b.desc_en,b.desc_id,(b.competency_class+0) as urcl"
              . " FROM ".XOCP_PREFIX."job_competency a"
              . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
              . " WHERE a.job_id = '$job_id'"
              . " ORDER BY b.compgroup_id,urcl,b.competency_id";
         $resrcl = $db->query($sql);
         if($db->getRowsNum($resrcl)>0) {
            while(list($rcl,$itj,$competency_id,$compgroup_id,$competency_cd,
                       $competency_abbr,$competency_nm,$competency_class,$desc_en,$desc_id)=$db->fetchRow($resrcl)) {
               
               $_SESSION["spider_chart_competency"][$competency_id] = array($compgroup_id,$competency_cd,$competency_abbr,$competency_nm,$competency_class,$desc_en,$desc_id);
               
               $arr_xttl_rcl[$job_id] += ($rcl*$itj);
               if($competency_abbr=="") {
                  $competency_abbr = "-";
               }
               $arr_comp[$competency_id] = array($competency_id,$compgroup_id,$competency_class,$competency_nm,$competency_abbr,$desc_en,$desc_id);
               $arr_job_competency[$job_id][$competency_id] = array($job_id,$competency_id,$rcl,$itj);
               $sql = "SELECT behaviour_id,proficiency_lvl,behaviour_en_txt,behaviour_id_txt"
                    . " FROM ".XOCP_PREFIX."compbehaviour"
                    . " WHERE competency_id = '$competency_id'"
                    . " ORDER BY proficiency_lvl";
               $rbh = $db->query($sql);
               if($db->getRowsNum($rbh)>0) {
                  while(list($behaviour_id,$lvl,$xen,$xid)=$db->fetchRow($rbh)) {
                     $arr_desclvl[$competency_id][$lvl] = array($xen,$xid);
                  }
               }
            }
         }
         
         ///////////////////// load competency data - end
         
         if($old_job_class!=$job_class_id) {
            if($old_job_class>0) {
               ///// display average
               
               if(isset($jm_jc_average[$old_job_class])&&isset($jm_jc_average[$old_job_class]["count"])&&$jm_jc_average[$old_job_class]["count"]>0) {
                  $jm_average = toMoney($jm_jc_average[$old_job_class]["sum"]/$jm_jc_average[$old_job_class]["count"]);
               } else {
                  $jm_average = "-";
               }
               
               if(isset($cf_jc_average[$old_job_class])&&isset($cf_jc_average[$old_job_class]["count"])&&$cf_jc_average[$old_job_class]["count"]>0) {
                  $cf_average = toMoney($cf_jc_average[$old_job_class]["sum"]/$cf_jc_average[$old_job_class]["count"]);
               } else {
                  $cf_average = "-";
               }
               
               $ret .= "<tr><td colspan='3' style='background-color:#fff;padding:4px;border:0px solid #888;border-right:1px solid #888;cursor:default;text-align:right;'>Average : </td>"
                     . "<td style='font-weight:bold;background-color:#eee;padding:4px;border-left:0px solid #888;border-right:1px solid #888;border-bottom:1px solid #888;cursor:default;text-align:center;'>$jm_average</td>"
                     . "<td style='font-weight:bold;background-color:#eee;padding:4px;border-left:0px solid #888;border-right:1px solid #888;border-bottom:1px solid #888;cursor:default;text-align:center;'>$cf_average</td>"
                     . "<td style='padding-left:5px;text-align:left;'>"
                     . "<input type='button' value='Chart' onclick='view_chart_pos(\"$old_job_class\",this,event);'/>&nbsp;"
                     . "<input type='button' value='Proactive Index Chart' onclick='view_chart_proactive_index(\"$old_job_class\",this,event);'/>"
                     . "</td>"
                     . "</tr>";
               
               ///// display average - end
            }
            $ret .= "<tr>";
            $ret .= "<td colspan='6' style='font-weight:bold;color:black;padding:4px;border-left:1px solid white;border-right:0px solid transparent;border-bottom:1px solid #888;'>"
                  . "$job_class_nm"
                  . "</td>";
            $ret .= "</tr>";
         }
         $old_job_class = $job_class_id;
         
         $sql = "SELECT a.employee_id,b.employee_ext_id,c.person_nm"
              . " FROM ".XOCP_PREFIX."assessment_session_job a"
              . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
              . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
              . " WHERE a.asid = '$asid' AND a.job_id = '$job_id'"
              . " ORDER BY c.person_nm";
         $remp = $db->query($sql);
         
         if($db->getRowsNum($remp)>0) {
            while(list($employee_id,$nip,$employee_nm)=$db->fetchRow($remp)) {
               //_calculate_competency($asid,$employee_id,$job_id);
               $ret .= "<tr id='tremp_${employee_id}_${job_id}' class='trmatrix'  onclick='select_emp(\"$employee_id\",\"$job_id\");' >";
               $ret .= "<td onmousemove='temp_mousemove(\"$employee_id\",this,event);' onmouseout='temp_mouseout(this,event);' style='padding:4px;border-left:1px solid #888;border-right:1px solid #888;border-bottom:1px solid #888;'>"
                     . "<div style='overflow:hidden;width:100px;'><div style='width:900px;'><span class='xlnk' onclick='view_assessment_detail(\"$employee_id\",\"$job_id\",\"$asid\",this,event);'>$employee_nm</span></div></div>"
                     . "</td>"
                     . "<td onmousemove='tjob_mousemove(\"$job_id\",this,event);' onmouseout='tjob_mouseout(this,event);' style='padding:4px;border-right:1px solid #888;border-bottom:1px solid #888;'>"
                     . "<div style='overflow:hidden;width:55px;'><div style='width:900px;'>$job_abbr</div></div>"
                     . "</td>"
                     . "<td  onmousemove='torg_mousemove(\"$job_id\",this,event);' onmouseout='torg_mouseout(this,event);' style='padding:4px;border-right:1px solid #888;border-bottom:1px solid #888;' title='$org_nm $org_class_nm'>"
                     . "<div style='overflow:hidden;width:55px;text-align:left;'><div style='width:900px;'>$org_abbr</div></div>"
                     . "</td>";
               
               $ttlccl = $ttlrcl = $ttlgap = 0;
               $cf = 0;
               $cf_compgroup = array();
               $cf_pass = array();
               
               $arr_emp[] = array($employee_id,$job_id,$job_class_id);
               
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
                            . "<colgroup><col width='90'/><col/></colgroup>"
                            . "<tbody>"
                            . "<tr><td>Name :</td><td>$employee_nm</td></tr>"
                            . "<tr><td>Employee ID :</td><td>$nip</td></tr>"
                            . "<tr><td>Entrance Date :</td><td>".sql2ind($entrance_dttm,"date")." ("._bctrim(toMoney($jobage/365.25))." year)</td></tr>"
                            . "<tr><td>Job Assigned :</td><td>".sql2ind($jobstart,"date")."</td></tr>"
                            . "<tr><td>Previous Job :</td><td></td></tr></tbody></table></td></tr></tbody></table>";
               $tooltip_emp .= "<div id='temp_${employee_id}' style='-moz-box-shadow:1px 1px 3px #000;font-size:0.9em;opacity:1;position:absolute;z-index:1000;background-color:#ffffff;padding:0px;border:1px solid #888;visibility:hidden;-moz-border-radius:5px;'>"
                             . "<div style='background-color:#eee;padding:4px;-moz-border-radius: 5px 5px 0 0;color:#888;font-weight:bold;text-align:center;'>$job_nm</div><div style='padding:8px;'>"
                             . $person_info."</div>"
                             . "</div>";
               
               
               /////////////// load final competency level
               $sql = "SELECT a.competency_id,a.cclxxx,a.rcl,a.itj,a.gapxxx,a.updated_dttm,(TO_DAYS(now())-TO_DAYS(a.updated_dttm)) as la,a.cclxxx,"
                    . "b.compgroup_id,b.competency_abbr"
                    . " FROM ".XOCP_PREFIX."employee_competency_final a"
                    . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
                    . " WHERE a.asid = '$asid'"
                    . " AND a.employee_id = '$employee_id'"
                    . " AND a.job_id = '$job_id'";
               $rcc = $db->query($sql);
               if($employee_id==367) {
                  _debuglog($sql);
               }
               if($db->getRowsNum($rcc)>0) {
                  while(list($competency_idx,$ccl,$rcl,$itj,$gap,$updated_dttm,$last_assess,$last_level,$compgroup_id)=$db->fetchRow($rcc)) {
                     $ccl = number_format($ccl,2,".","");
                     $gap = number_format($gap,2,".","");
                     $arr_ccl[$employee_id][$job_id][$competency_idx] = array($employee_id,$job_id,$competency_idx,$ccl,$rcl,$itj,$gap,$last_assess,$updated_dttm,$last_level);
                     $_SESSION["mtrx_ccl"][$employee_id][$job_id][$competency_idx] = array($employee_id,$job_id,$competency_idx,$ccl,$rcl,$itj,$gap,$job_class_id);
                     if($compgroup_id==1||$compgroup_id==2) {
                        $_SESSION["spider_chart"][$employee_id][$job_id][$competency_idx] = array($employee_id,$job_id,$competency_idx,$ccl,$rcl,$itj,$gap,$job_class_id);
                     }
                  }
               }
               ////////////// calculate job match here ///////////////////////////////////////////////////////////////////////////////////
               /// query from final recap
               //$sql = "SELECT jmxxx,cf FROM ".XOCP_PREFIX."employee_competency_final_recap"
			   $sql = "SELECT jmxxx,cfxxx FROM ".XOCP_PREFIX."employee_competency_final_recap"
                    . " WHERE asid = '$asid'"
                    . " AND employee_id = '$employee_id'"
                    . " AND job_id = '$job_id'";
               $rc = $db->query($sql);
               if($db->getRowsNum($rc)>0) {
                  list($match,$cf)=$db->fetchRow($rc);
               } else {
                  $match = 0;
                  $cf = 0;
               }
               
               
               if($job_class_id==5||$job_class_id==6) {
                  $jmttl_sum+=$match;
                  $jmttl_count++;
                  $cfttl_sum+=$cf;
                  $cfttl_count++;
               }
               
               
               ////////////// stop calculate job match here //////////////////////////////////////////////////////////////////////////////
               
               $ret .= "<td style='padding:4px;border-left:0px solid #888;border-right:1px solid #888;border-bottom:1px solid #888;text-align:center;'>".toMoney($match)."</td>"
                     . "<td style='padding:4px;border-left:0px solid #888;border-right:1px solid #888;border-bottom:1px solid #888;text-align:center;'>".toMoney($cf)."</td>";
               
               $jm_jc_average[$job_class_id]["sum"] += $match;
               $jm_jc_average[$job_class_id]["count"]++;
               $cf_jc_average[$job_class_id]["sum"] += $cf;
               $cf_jc_average[$job_class_id]["count"]++;
               
               $ret .= "<td style='padding:0px;border-left:0px solid #888;border-right:1px solid #888;border-bottom:1px solid #888;text-align:center;' class='thx'>"
                     . "<div><div>"
                     . "<table class='lvl'><tbody class='lvl'><tr id='trlvl_${employee_id}_${job_id}'>"
                     . "<td></td>"
                     . "<td></td>"
                     . "<td></td>"
                     . "<td></td>"
                     . "<td></td>"
                     . "<td></td>"
                     . "<td></td>"
                     . "<td></td>"
                     . "<td></td>"
                     . "<td></td>"
                     . "<td></td>"
                     . "<td></td>"
                     . "<td></td>"
                     . "<td></td>"
                     . "<td></td>"
                     . "<td></td>"
                     . "<td></td>"
                     . "<td></td>"
                     . "<td></td>"
                     . "<td></td>"
                     . "</tr></tbody></table>"
                     . "</div></div></td>";
               
               $ret .= "</tr>";
               
               $matrix[] = array($job_class_id,$job_class_nm,$job_id,$job_nm,$job_abbr,$jlvl,$summary,$org_nm,$org_abbr,$org_class_nm,$nip,$employee_id,$employee_nm,$person_id,$cf_pass_cnt,$cf_cnt,$pr_value,$ttlccl,$ttlrcl,$match,$cf);
               
            }
         } else {
            $arr_emp[] = array(0,$job_id,$job_class_id);
            $person_id = $cf_pass_cnt = $cf_cnt = $pr_value = $ttlccl = $ttlrcl = "";
            $ret .= "<tr class='trmatrix0'>";
            $ret .= "<td style='padding:4px;border-left:1px solid #888;border-right:1px solid #888;border-bottom:1px solid #888;'>"
                  . "<div style='overflow:hidden;width:100px;'><div style='width:900px;'>-</div></div>"
                  . "</td>"
                  . "<td onmousemove='tjob_mousemove(\"$job_id\",this,event);' onmouseout='tjob_mouseout(this,event);' style='cursor:default;padding:4px;border-right:1px solid #888;border-bottom:1px solid #888;'>"
                  . "<div style='overflow:hidden;width:55px;'><div style='width:900px;'>$job_abbr</div></div>"
                  . "</td>"
                  . "<td  onmousemove='torg_mousemove(\"$job_id\",this,event);' onmouseout='torg_mouseout(this,event);' style='cursor:default;padding:4px;border-right:1px solid #888;border-bottom:1px solid #888;' title='$org_nm $org_class_nm'>"
                  . "<div style='overflow:hidden;width:55px;text-align:left;'><div style='width:900px;'>$org_abbr</div></div>"
                  . "</td>";
            $ret .= "<td style='padding:4px;border-left:0px solid #888;border-right:1px solid #888;border-bottom:1px solid #888;cursor:default;text-align:center;'>-</td>"
                  . "<td style='padding:4px;border-left:0px solid #888;border-right:1px solid #888;border-bottom:1px solid #888;cursor:default;text-align:center;'>-</td>";
            $ret .= "<td style='padding:4px;border-left:0px solid #888;border-right:1px solid #888;border-bottom:1px solid #888;text-align:center;height:27px;'>&nbsp;</td>";
            $ret .= "</tr>";
            
            $matrix[] = array($job_class_id,$job_class_nm,$job_id,$job_nm,$job_abbr,$jlvl,$summary,$org_nm,$org_abbr,$org_class_nm,$nip,$employee_id,"-",$person_id,$cf_pass_cnt,$cf_cnt,$pr_value,$ttlccl,$ttlrcl,0,0);
            
         }
         
         $tooltip_org .= "<div id='torg_${job_id}' style='-moz-box-shadow:1px 1px 3px #000;min-width:100px;font-size:0.9em;opacity:1;position:absolute;z-index:1000;background-color:#ffffff;padding:0px;border:1px solid #888;visibility:hidden;-moz-border-radius:5px;'>"
                       . "<div style='background-color:#eee;padding:4px;-moz-border-radius:5px 5px 0 0;color:#888;font-weight:bold;text-align:center;'>$org_abbr</div><div style='padding:8px;text-align:center;'>"
                       . "$org_nm $org_class_nm</div>"
                       . "</div>";
         $tooltip_job .= "<div id='tjob_${job_id}' style='-moz-box-shadow:1px 1px 3px #000;width:300px !important;font-size:0.9em;opacity:1;position:absolute;z-index:1000;background-color:#ffffff;padding:0px;border:1px solid #888;visibility:hidden;-moz-border-radius:5px;'>"
                       . "<div style='background-color:#eee;padding:4px;-moz-border-radius: 5px 5px 0 0;color:#888;font-weight:bold;text-align:center;'>$job_nm</div><div style='padding:8px;'>"
                       . "<div style='font-weight:bold;'>Job Summary:</div>$summary</div>"
                       . "</div>";
      }
      
      /////// display last average
      
      if(isset($jm_jc_average[$old_job_class])&&isset($jm_jc_average[$old_job_class]["count"])&&$jm_jc_average[$old_job_class]["count"]>0) {
         $jm_average = toMoney($jm_jc_average[$old_job_class]["sum"]/$jm_jc_average[$old_job_class]["count"]);
      } else {
         $jm_average = "-";
      }
      
      if(isset($cf_jc_average[$old_job_class])&&isset($cf_jc_average[$old_job_class]["count"])&&$cf_jc_average[$old_job_class]["count"]>0) {
         $cf_average = toMoney($cf_jc_average[$old_job_class]["sum"]/$cf_jc_average[$old_job_class]["count"]);
      } else {
         $cf_average = "-";
      }
      
      $ret .= "<tr><td colspan='3' style='background-color:#fff;padding:4px;border:0px solid #888;border-right:1px solid #888;cursor:default;text-align:right;'>Average : </td>"
            . "<td style='font-weight:bold;background-color:#eee;padding:4px;border-left:0px solid #888;border-right:1px solid #888;border-bottom:1px solid #888;cursor:default;text-align:center;'>$jm_average</td>"
            . "<td style='font-weight:bold;background-color:#eee;padding:4px;border-left:0px solid #888;border-right:1px solid #888;border-bottom:1px solid #888;cursor:default;text-align:center;'>$cf_average</td>"
            . "<td style='padding-left:5px;text-align:left;'>"
            . "<input type='button' value='Chart' onclick='view_chart_pos(\"$old_job_class\",this,event);'/>&nbsp;"
            . "<input type='button' value='Proactive Index Chart' onclick='view_chart_proactive_index(\"$old_job_class\",this,event);'/>"
            . "</td>"
            . "</tr>";
      
      /////// display last average - end
      
      $ret .= "</tbody><tbody>"; ////////////////////// split body
      
      $ret .= "<tr><td>&nbsp</td></tr>";
      
      $ret .= "<tr><td colspan='3' style='font-weight:bold;background-color:#fff;padding:4px;border:1px solid #555;cursor:default;text-align:center;'>Total Average SPV + OFF</td>"
            . "<td style='font-weight:bold;background-color:#eee;padding:4px;border:1px solid #555;border-left:0px solid #555;cursor:default;text-align:center;'>".toMoney($jmttl_sum/$jmttl_count)."</td>"
            . "<td style='font-weight:bold;background-color:#eee;padding:4px;border:1px solid #555;border-left:0px solid #555;cursor:default;text-align:center;'>".toMoney($cfttl_sum/$cfttl_count)."</td>"
            . "<td style='padding-left:5px;text-align:left;'><input type='button' value='All Proactive Index Chart' onclick='view_chart_proactive_index(\"all\",this,event);'/></td>"
            . "</tr>";
      
      $ret .= "</tbody></table>";
      
      $ret .= "</td><td style='vertical-align:top;padding:0px;'>";  //// frame
      
      ////////// tabbed competency detail
      
      
      $ret .= "</td></tr></tbody></table>"; //// frame
      
      $_SESSION["result_matrix"] = $matrix;
      
      $ret .= $tooltips.$tooltip_rcl.$tooltip_job.$tooltip_org.$tooltip_emp;
      
      $data = array();
      
      if(count($arr_comp)<=0) {
         $data = "NOCOMPETENCYDEFINED";
      } else {
         $ret_comp = array();
         foreach($arr_comp as $k=>$v) {
            $ret_comp[] = $v;
         }
         
         $ret_ccl = array();
         foreach($arr_ccl as $employee_id=>$v) {
            foreach($v as $job_id=>$w) {
               foreach($w as $competency_id=>$x) {
                  $ret_ccl[] = $x;
               }
            }
         }
         
         $ret_job = array();
         foreach($arr_job_competency as $job_id=>$v) {
            foreach($v as $competency_id=>$w) {
               $ret_job[] = $w;
            }
         }
         
         $data = array($ret_comp,$ret_ccl,$ret_job);
      }
      
      
      return array(array($tips0,$tips1),$ret,$data);
      
   }
   
   function app_setPosition($args) {
      $db=&Database::getInstance();
      if($args[0]!=$_SESSION["hris_posmatrix_division"]) {
         $_SESSION["hris_posmatrix_subdivision"] = 0;
      } else {
         $_SESSION["hris_posmatrix_subdivision"] = $args[1];
      }
      $_SESSION["hris_posmatrix_division"] = $args[0];
      $_SESSION["hris_posmatrix_poslevel"] = $args[2];
      $_SESSION["hris_arm_asid"] = $args[3];
      
      $_SESSION["hris_subdiv"] = array();
      
      _debuglog($_SESSION["hris_posmatrix_division"]. " --------------------<<<<<<");
      
      if($_SESSION["hris_posmatrix_division"]=="all") {
         foreach($_SESSION["hris_division_allow"] as $division_org_id=>$a) {
            $this->recurseDivision($division_org_id);
         }
      } else {
         $this->recurseDivision($_SESSION["hris_posmatrix_division"]);
      }
      
      $subdiv_id = $_SESSION["hris_posmatrix_subdivision"];
      
      ksort($_SESSION["hris_subdiv"]);
      
      
      if($_SESSION["arm_levelmatrix"]==4) {
         $optsubdiv = "NOCHANGE";
      } else {
         if(!isset($_SESSION["hris_subdiv"][$subdiv_id])) {
            $optsubdiv = "<option value='0'>All</option>";
            foreach($_SESSION["hris_subdiv"] as $org_class_idx=>$orgs) {
               foreach($orgs as $org_idx=>$v) {
                  list($org_id,$org_nm,$org_abbr,$org_class_nm)=$v;
                  $optsubdiv .= "<option value='$org_id' ".($org_id==$_SESSION["hris_posmatrix_subdivision"]?"selected='1'":"").">$org_nm $org_class_nm</option>";
               }
            }
         } else {
            $optsubdiv = "NOCHANGE";
         }
      }
      
      $this->getAllJobs();
      
      $poslevel_id = $args[2];
      $optlevel = "<option value='0'>All</option>";
      foreach($_SESSION["hris_poslevel"] as $level) {
         list($job_class_id,$job_class_nm)=$level;
         $optlevel .= "<option value='$job_class_id' ".($poslevel_id==$job_class_id?"selected='1'":"").">$job_class_nm</option>";
      }
      
      return array($optsubdiv,$optlevel);
   }
   
   function getDivision($org_id) {
      $db=&Database::getInstance();
      $sql = "SELECT a.org_id,a.org_nm,a.org_abbr,b.org_class_nm,a.org_class_id,a.parent_id"
           . " FROM ".XOCP_PREFIX."orgs a"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . " WHERE a.org_id = '$org_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($org_id,$org_nm,$org_abbr,$org_class_nm,$org_class_id,$parent_id)=$db->fetchRow($result)) {
            if($org_class_id>3) {
               return $this->getDivision($parent_id);
            } else {
               return array($org_id,$org_nm,$org_abbr,$org_class_nm,$org_class_id,$parent_id);
            }
         }
      }
      return FALSE;
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
            if($_SESSION["arm_levelmatrix"]==4) {
               if($org_class_id>=4) {
                  $_SESSION["hris_subdiv"][$org_class_id][$org_id] = array($org_id,$org_nm,$org_abbr,$org_class_nm);
               }
            } else {
               if($org_class_id>=3) {
                  $_SESSION["hris_subdiv"][$org_class_id][$org_id] = array($org_id,$org_nm,$org_abbr,$org_class_nm);
               }
            }
            $this->recurseDivision($org_id);
         }
      }
   }
   
   function recurseSection($parent_id) {
      $db=&Database::getInstance();
      $sql = "SELECT a.org_id,a.org_nm,a.org_abbr,b.org_class_nm,a.org_class_id"
           . " FROM ".XOCP_PREFIX."orgs a"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . ($parent_id=="all"?"":" WHERE a.parent_id = '$parent_id'");
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($org_id,$org_nm,$org_abbr,$org_class_nm,$org_class_id)=$db->fetchRow($result)) {
            if($org_class_id>=4) {
               $_SESSION["hris_subdiv"][$org_class_id][$org_id] = array($org_id,$org_nm,$org_abbr,$org_class_nm);
            }
            $this->recurseSection($org_id);
         }
      }
   }
   
   function getAllJobs() {
      $db=&Database::getInstance();
      $division_id = $_SESSION["hris_posmatrix_division"];
      $subdiv_id = $_SESSION["hris_posmatrix_subdivision"];
      
      if($_SESSION["arm_levelmatrix"]==4) {
      } else {
         $_SESSION["hris_subdiv"] = array();
         if($division_id=="all") {
            foreach($_SESSION["hris_division_allow"] as $division_org_id=>$a) {
               $this->recurseDivision($division_org_id);
            }
         } else {
            $this->recurseDivision($_SESSION["hris_posmatrix_division"]);
         }
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
               if($job_class_level<=50) continue;
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
            if($_SESSION["arm_levelmatrix"]!=4) {
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
                     if($_SESSION["arm_levelmatrix"]==4&&$job_class_level<=50) continue;
                     $_SESSION["hris_poslevel"][$job_class_id] = array($job_class_id,$job_class_nm,$job_class_level);
                     $_SESSION["hris_jobs"][$job_class_id][$job_id] = array($job_id,$job_nm,$job_abbr,$job_class_id,$job_class_nm,$org_nm,$org_class_nm,$org_abbr,$job_class_level,$summary);
                  }
               }
            }
         }
      }
   }
   
   
}

} /// HRIS_ASSESSMENTMRESULTMATRIXAJAX_DEFINED
?>