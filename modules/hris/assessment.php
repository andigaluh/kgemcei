<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/assessment.php                             //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-07-18                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_ASSESSMENT_DEFINED') ) {
   define('HRIS_ASSESSMENT_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

function __asm_org_down($org_id) {
   $db=&Database::getInstance();
   $_SESSION["asm_org_list"][$org_id] = 1;
   $sql = "SELECT org_id FROM ".XOCP_PREFIX."orgs WHERE parent_id = '$org_id'";
   $result = $db->query($sql);
   if($db->getRowsNum($result)>0) {
      while(list($sub_org_id)=$db->fetchRow($result)) {
         __asm_org_down($sub_org_id);
      }
   }
}

class _hris_Assessment extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_ASSESSMENT_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_ASSESSMENT_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_Assessment($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function assessment_schedule() {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      require_once(XOCP_DOC_ROOT."/modules/hris/include/assessment.php");
      $db=&Database::getInstance();
      $user_id = getUserID();
      
      $schedule_count = 0;
      
      $ret = "<div style='width:800px;' class='asm_schedule'><div class='asm_schedule_inner'>"
           . "<div style='font-size:1.5em;text-align:center;'>Schedule</div>"
           . "<table class='assessment_schedule' style='width:100%;'>"
           . "<thead><tr>"
           . "<td>Session</td>"
           . "<td>Start</td>"
           . "<td>Stop</td>"
           . "<td>Type</td>"
           . "<td>Group</td>"
           . "<td>Action</td>"
           . "</tr></thead>"
           . "<tbody>";
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
           $first_assessor_job_id,
           $next_assessor_job_id)=_hris_getinfobyuserid($user_id);
      
      /// regular assessment ////////////////////////////////// 1
      
      $sql = "SELECT asid,session_nm FROM hris_assessment_session WHERE session_t = 'regular' AND status_cd = 'normal'";
      $ridp = $db->query($sql);
      if($db->getRowsNum($ridp)>0) {
         while(list($asidx,$session_nmx)=$db->fetchRow($ridp)) {
            
            
            $sql = "SELECT schedule_id,org_id,start_dttm,stop_dttm,assessment_superior,assessment_subordinate,assessment_peer,assessment_customer,"
                 . "IF(start_dttm<=now(),1,0),IF(stop_dttm>=now(),1,0)"
                 . " FROM ".XOCP_PREFIX."assessment_schedule"
                 . " WHERE asid = '$asidx'"
                 . " AND status_cd = 'normal'"
                 . " AND stop_dttm >= now() ORDER BY start_dttm";
            $rs = $db->query($sql);
            if($db->getRowsNum($rs)>0) {
               while(list($schedule_id,$org_id,$start_dttm,$stop_dttm,$assessment_superior,$assessment_subordinate,$assessment_peer,$assessment_customer,
                     $start_in,$stop_in)=$db->fetchRow($rs)) {
                  $_SESSION["asm_org_list"] = array();
                  __asm_org_down($org_id);
                  $sql = "SELECT org_id FROM ".XOCP_PREFIX."jobs a"
                       . " WHERE job_id = '$self_job_id'";
                  $rj = $db->query($sql);
                  $fnd = 0;
                  if($db->getRowsNum($rj)>0) {
                     while(list($x_org_id)=$db->fetchRow($rj)) {
                        if(isset($_SESSION["asm_org_list"][$x_org_id])) $fnd++;
                     }
                  }
                  if($fnd==0) continue;
                  $group = "";
                  if($assessment_superior==1) $group .= ", Superior";
                  if($assessment_subordinate==1) $group .= ", Subordinate";
                  if($assessment_peer==1) $group .= ", Peer";
                  if($assessment_customer==1) $group .= ", Customer";
               
                  if($group=="") {
                     $group = "-";
                  } else {
                     $group = substr($group,2);
                  }
                  
                  $class = "trstrip_".($schedule_count%2);
                  
                  $ret .= "<tr class='$class'>"
                        . "<td>$session_nmx</td>"
                        . "<td>".sql2ind($start_dttm)."</td>"
                        . "<td>".sql2ind($stop_dttm)."</td>"
                        . "<td>Regular</td>"
                        . "<td>$group</td>"
                        . "<td>".($start_in==1&&$stop_in==1?"<a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&diagram=y&asid=$asidx'>Start Assessment</a>":"-")."</td>"
                        . "</tr>";
                  $schedule_count++;
               }
            }
         }
      }
      
      /*
      ///////////////////////////////////////////////////////// 1
      /// subordinat idp assessment /////////////////////////// 2
      $sql = "SELECT a.job_id,a.job_nm,a.job_cd,a.job_abbr,a.org_id,a.description,a.summary,b.job_level"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
           . " WHERE a.assessor_job_id = '$self_job_id'"
           . " ORDER BY a.job_class_id";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while($rrow=$db->fetchRow($result)) {
            list($job_id,$job_nm,$job_cd,$job_abbr,$org_id,$job_descx,$job_summary,$job_level)=$rrow;
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
                  $sql = "SELECT asid,session_nm FROM hris_assessment_session WHERE session_t = 'idp' AND idp_employee_id = '$employee_id'";
                  $ridp = $db->query($sql);
                  if($db->getRowsNum($ridp)>0) {
                     while(list($asidx,$session_nmx)=$db->fetchRow($ridp)) {
                        
                        $sch_arr = _asm_get_schedule($asidx);
                        if($sch_arr!==FALSE&&is_array($sch_arr)) {
                           list($asidyyy,$start_dttm,$stop_dttm,$nm,$periode)=$sch_arr;
                           $ret .= "<tr>"
                                 . "<td><a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&assessment_idp=y&asid=$asidx&employee_id=$employee_id'>$session_nmx</td>"
                                 . "<td>".sql2ind($start_dttm)."</td>"
                                 . "<td>".sql2ind($stop_dttm)."</td>"
                                 . "<td>IDP</td>"
                                 . "</tr>";
                           $schedule_count++;
                        }
                     }
                  }
               }
            }
         }
      }
      
      
      ///////////////////////////////////////////////////////// 1
      /// 360 idp assessment /////////////////////////// 2
      $sql = "SELECT ax.employee_id,b.employee_ext_id,c.person_nm,a.gradeval,c.birth_dttm,c.birthplace,"
           . "c.adm_gender_cd,c.addr_txt,c.cell_phone,c.home_phone,c.marital_st,"
           . "b.entrance_dttm,a.start_dttm,a.stop_dttm,(TO_DAYS(now())-TO_DAYS(b.entrance_dttm)) as jobage,"
           . "c.person_id"
           . " FROM ".XOCP_PREFIX."assessor_360 ax"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job a ON a.employee_id = ax.employee_id"
           . " WHERE ax.assessor_id = '$self_employee_id'"
           . " GROUP BY ax.asid,ax.employee_id";
      $res2 = $db->query($sql);
      _debuglog($sql);
      if($db->getRowsNum($res2)>0) {
         while(list($employee_id,$nip,$employee_nm,$gradeval,$dob,$pob,$gender,$addr,$cellphone,$phone,$marital,
                    $entrance_dttm,$jobstart,$jobstop,$jobage,$person_id)=$db->fetchRow($res2)) {
            $sql = "SELECT asid,session_nm FROM hris_assessment_session WHERE session_t = 'idp' AND idp_employee_id = '$employee_id'";
            $ridp = $db->query($sql);
            _debuglog($sql);
            if($db->getRowsNum($ridp)>0) {
               while(list($asidx,$session_nmx)=$db->fetchRow($ridp)) {
                  
                  $sch_arr = _asm_get_schedule($asidx);
                  if($sch_arr!==FALSE&&is_array($sch_arr)) {
                     list($asidyyy,$start_dttm,$stop_dttm,$nm,$periode)=$sch_arr;
                     
                     $ret .= "<tr>"
                           . "<td><a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&assessment_idp=y&asid=$asidx&employee_id=$employee_id'>$session_nmx</td>"
                           . "<td>".sql2ind($start_dttm)."</td>"
                           . "<td>".sql2ind($stop_dttm)."</td>"
                           . "<td>IDP</td>"
                           . "</tr>";
                     $schedule_count++;
                  }
               }
            }
         }
      }
      
      */
      
      
      if($schedule_count==0) {
         $ret .= "<tr><td colspan='6' style='text-align:center;font-style:italic;'>Currently there is no assessment periode running.</td></tr>";
      }
      
      $ret .= "</tbody></table>";
      
      $ret .= "</div></div>";
      
      return $ret;
   }
   
   function assessment() {
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_assessment.php");
      require_once(XOCP_DOC_ROOT."/modules/hris/include/assessment.php");
      $db=&Database::getInstance();
      $ret = "";
      $user_id = getUserID();
      $tooltips = ""; /// for tooltips definition
      $assessor_job_count = 0;
      global $proficiency_level_name;
      
      $asid = $_SESSION["hris_assessment_asid"];
      
      /// trap schedule here
      
      $allow_superior = 0;
      $allow_subordinate = 0;
      $allow_peer = 0;
      $allow_customer = 0;
      //// trap schedule //////////////////////////////////////////////////////////////////////////////////////////////////
      $sql = "SELECT schedule_id,org_id,start_dttm,stop_dttm,assessment_superior,assessment_subordinate,assessment_peer,assessment_customer,"
           . "IF(start_dttm<=now(),1,0),IF(stop_dttm>=now(),1,0)"
           . " FROM ".XOCP_PREFIX."assessment_schedule"
           . " WHERE asid = '$asid'"
           . " AND status_cd = 'normal'"
           . " AND stop_dttm >= now() ORDER BY start_dttm";
      $rs = $db->query($sql);
      if($db->getRowsNum($rs)>0) {
         while(list($schedule_id,$org_id,$start_dttm,$stop_dttm,$assessment_superior,$assessment_subordinate,$assessment_peer,$assessment_customer,
            $start_in,$stop_in)=$db->fetchRow($rs)) {
            if($start_in==1&&$stop_in==1) {
               if($assessment_superior==1) $allow_superior++;
               if($assessment_subordinate==1) $allow_subordinate++;
               if($assessment_peer==1) $allow_peer++;
               if($assessment_customer==1) $allow_customer++;
            }
         }
      }
      /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
      
      
      if($_SESSION["hris_asmgroup"]=="superior"&&$allow_superior==0) {
         return "Currently superior assessment is not in schedule. [<a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&diagram=y&asid=$asid'>Diagram</a>]";
      }
      
      if($_SESSION["hris_asmgroup"]=="subordinate"&&$allow_subordinate==0) {
         return "Currently subordinate assessment is not in schedule. [<a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&diagram=y&asid=$asid'>Diagram</a>]";
      }
      
      if($_SESSION["hris_asmgroup"]=="peer"&&$allow_peer==0) {
         return "Currently peer assessment is not in schedule. [<a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&diagram=y&asid=$asid'>Diagram</a>]";
      }
      
      if($_SESSION["hris_asmgroup"]=="customer"&&$allow_customer==0) {
         return "Currently customer assessment is not in schedule. [<a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&diagram=y&asid=$asid'>Diagram</a>]";
      }
      
      $tabmargin = 282;
      
      $_SESSION["html"]->js_scriptaculous_effecs=TRUE;
      
      $arr_compgroup = array();
      $sql = "SELECT compgroup_id,compgroup_nm,competency_class_set FROM ".XOCP_PREFIX."compgroup"
           . " ORDER BY compgroup_id";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($compgroup_id,$compgroup_nm,$cset)=$db->fetchRow($result)) {
            $arr_compgroup[$compgroup_id] = array($compgroup_nm,explode(",",$cset));
         }
      }
      
      $ret = "<style type='text/css'>
      
               px { margin:0px;margin-bottom:auto; }
               
               ul.axul {
                  cursor:pointer;
                  font-weight:bold;
                  margin: 0; 
                  padding: 0; 
                  list-style: none; 
                  float: left;
                  width:100%;
                  border-bottom:0px solid #c1c1c1;
               }
               
               ul.axul li {
                  padding:0px;
                  float:left;
                  margin-right:2px;
                  margin-top:3px;
                  text-align:center;
                  background-color:#ffffff;
                  border:1px solid #cccccc;
                  border-bottom:0px;
                  padding-top:8px;
                  padding-bottom:9px;
               }
               
                              
               ul.axul li span {
                  display: block; 
                  padding: 0 8px; 
                  line-height: 8px; 
                  text-align:center;
               }

               ul.axul li:hover {
                  font-weight:bold;
                  text-decoration:none;
                  color:#000;
               }
               
               ul.axul li.compclasstips {
                  padding:0px;
                  float:left;
                  margin-right:2px;
                  margin-top:3px;
                  text-align:center;
                  background-color:transparent;
                  border:1px solid transparent;
                  border-bottom:0px;
                  padding-top:8px;
                  padding-bottom:9px;
                  font-weight:normal;
                  cursor:default;
                  color:#4444ff;
               }
               
               ul.axul li.compclasstips:hover {
                  cursor:default;
                  color:#4444ff;
               }
               
               
               ul.axul li.compclasstips span {
                  display: block; 
                  padding: 0 8px; 
                  line-height: 8px; 
                  text-align:center;
                  font-weight:normal;
               }

               

               ul.axul li.compsel {
                  padding:0px;
                  padding-top:2px;
                  float:left;
                  background-color:#f0f0f0;
                  border:1px solid #999999;
                  border-top:2px solid #999;
                  border-bottom:none;
                  position:relative;
                  top:-3px;
                  height:26px;
                  margin-bottom:-4px;
               }
               
               ul.axul li.compsel span {
                  display: block; 
                  padding: 0 1em; 
                  line-height: 20px; 
                  text-align:center;
                  color:#000000;
               }
               
               ul.axul li.ccsel {
                  padding:0px;
                  float:left;
                  padding-top:2px;
                  background-color:#dddddd;
                  border:1px solid #999999;
                  border-top:2px solid #999;
                  border-bottom:none;
                  position:relative;
                  top:-3px;
                  height:26px;
                  margin-bottom:-4px;
               }
               
               ul.axul li.ccsel span {
                  display: block; 
                  padding: 0 1em; 
                  line-height: 20px; 
                  text-align:center;
                  color:#000000;
               }
               
               table.emp_info td { color:#999999; }
               table.emp_info td+td { color:#555555; }
              
               .cl {
                  background-color:#fff;
                  border:1px solid #444;
                  width:59px;
                  overflow:hidden;
                  background-image:url(".XOCP_SERVER_SUBDIR."/modules/hris/images/level_background.png);
                  background-repeat:no-repeat;
                  margin:auto;
                  background-position:-121px;
               }
               
               .match {
                  background-color:#eee;
                  border:1px solid #444;
                  width:59px;
                  overflow:hidden;
                  background-repeat:no-repeat;
                  margin:auto;
                  background-position:-121px;
               }
               
               .outer {
                  width:100%;
                  position:relative;
                  padding:40px 0 0px 0;
                  margin:0 0 0 0;
               }
               
               .innera {
                  overflow:auto;
                  width:100%;
                  height:270px;
               }
               
               .tbla { 
                  width:100%;
                  margin:0px;
                  border-spacing:0;
               }

               .outer table.tbla caption {
                  position:absolute;
                  width:100%;
                  height:41px;
                  border-bottom:1px solid #aaaaaa;
                  top:0;
                  left:0;
                  background-color:#ddd;
                  cursor:default;
               }
               
               .outer table.tbla caption table {
                  border-spacing:0px;
               }
               
               .outer table.tbla caption td {
                  height:40px;
                  font-weight:bold;
                  text-align:left;
                  padding:4px;
               }
               
               .outer table.tbla caption td.th4 {
                  text-align:center;
               }
               
               .outer thead.thdr0 tr {
                  top:0;
                  left:0;
               }
               
               .outer tbody.bodymtrx>tr>td {
                  padding:4px;
                  text-align:left;
                  border-bottom:1px solid #aaa;
                  height:28px;
               }
               
               .outer tbody.bodymtrx>tr>td.thx {
                  padding:0px;
               }
               
               .outer thead.thdr0 th {
                  color:#777777;
                  cursor:default;
                  height:30px;
                  padding:4px;
                  text-align:left;
               }
               
               .outer .th0 { width:112px; }
               .outer .th1 { width:70px; }
               .outer .th2 { width:40px;text-align:center; }
               .outer .th3 { width:49px;border-right:1px solid #bbbbbb;text-align:center; }
               .outer .th4 { width:68px;border-right:1px solid #999999; }
               
               .outer .th2c { width:40px;text-align:left;border-right:1px solid #bbbbbb; }
               
               
               .innera td.th0 > div { width:112px;overflow:hidden; }
               .innera td.th0 > div > div { width:900px;text-align:left; }
               .innera td.th1 > div { width:70px;overflow:hidden; }
               .innera td.th1 > div > div { width:900px;text-align:left; }
               .innera td.th2 > div { width:40px;overflow:hidden;text-align:center; }
               .innera td.th2 > div > div { width:100px;text-align:center;margin-left:-30px; }
               .innera td.th3 > div { width:50px;overflow:hidden; }
               .innera td.th3 > div > div { width:200px;text-align:center;margin-left:-75px; }
               
               .innera td.th2c > div { width:80px;overflow:hidden;text-align:left; }
               .innera td.th2c > div > div { width:200px;text-align:left; }
               
               td.th0 { font-weight:bold; }
               
               .outer .dk {
                  background:#fff;
               }
               
               .trd0 { cursor:default;background-color:#eee; }
               .trd1 { cursor:default;background-color:#bbf; }
               .trd2 { cursor:default;background-color:#eee; }
               .trd1 td.th0 { text-decoration:underline; }
               .trd0:hover td, .trd1:hover td, .trd2:hover td { background-color:#ddf; }
               .trd0 td.th0:hover, .trd1 td.th0:hover { background-color:#888;cursor:pointer;color:white; } 
               .trd0:hover table.lvl td:hover { background-color:#888;cursor:pointer;color:white; }
               .trd1:hover table.lvl td:hover { background-color:#888;cursor:pointer;color:white; }
               .trd2:hover table.lvl td:hover { }
               
               td.th4:hover { background-color:#eee; }
               
               .innera table.lvl {
                  border-spacing:0px;
               }
               
               .innera tbody.lvl>tr>td {
                  width:76px;
                  padding:0px;
                  border-right:1px solid #bbb;
                  height:27px;
               }
               
               .thx div { overflow:hidden;padding:0px; }
               .thx div>div { width:10000px; }

              </style>"
              
           . "<div id='wrapper' style='margin-top:40px;margin-bottom:20px;'>";
           
      if(isset($_SESSION["assessment_page"])&&$_SESSION["assessment_page"]=="assessment_idp") {
         $ret .= "<div style='text-align:right;margin-right:10px;border:0px solid #999999;padding:2px;'>[<a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&schedule_page=y'>Schedule</a>]</div>";
      } else {
         $ret .= "<div style='text-align:right;margin-right:10px;border:0px solid #999999;padding:2px;'>[<a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&diagram=y&asid=$asid'>Diagram</a>]</div>";
      }
      
      $ret .= "<div style='position:absolute;top:105px;left:60px;border:1px solid #bbbbbb;background-color:#fff;padding:0px;font-size:0.8em;'>"
           . "<div style='font-weight:bold;text-align:center;background-color:#f0f0f0;'>Remarks</div>"
           . "<img src='".XOCP_SERVER_SUBDIR."/modules/hris/images/assessment_legend.png'/>"
           /*
           . "<table><colgroup><col width='20'/><col/></colgroup>"
           . "<tbody>"
           . "<tr><td><div style='background-color:#1cd61c;padding:0px;width:15px;height:10px;'>&nbsp;</div></td><td style='padding:0px;'>Required Competency Level</td></tr>"
           . "<tr><td><div style='background-color:#357cc2;padding:0px;width:15px;height:10px;'>&nbsp;</div></td><td style='padding:0px;'>Current Competency Level</td></tr>"
           . "<tr><td><div style='background-color:#ffff99;padding:0px;width:15px;height:10px;'>&nbsp;</div></td><td style='padding:0px;'>Highly Importance</td></tr>"
           . "</tbody>"
           . "</table>"
           */
           . "</div>";
      
      $ret .= "<div style='margin-left:${tabmargin}px;border-bottom:1px solid #aaaaaa;'><ul class='axul'>";
      $cse = "";
      $current_cg = 1;
      $tab_arr_js = "var tabs = new Array(";
      foreach($arr_compgroup as $compgroup_id=>$vg) {
         list($compgroup_nm,$cset)=$vg;
         if($_SESSION["assessor360"]==1) {
            if(!in_array("soft",$cset)) continue;
         }
         $tab_arr_js .= "$compgroup_id,";
         if($current_cg==$compgroup_id) {
            $class = "class='compsel'";
            $dispsub = "";
         } else {
            $class = "";
            $dispsub = "display:none;";
         }
         $ret .= "<li id='tabcg_${compgroup_id}' style='width:120px;' onclick='swcg(\"$compgroup_id\",this,event);' $class><span>$compgroup_nm</span></li>";
         
         $cse .= "<div id='cgdiv_${compgroup_id}' style='padding-top:9px;border-right:1px solid #999999;background-color:#f0f0f0;border-left:1px solid #999999;margin-left:${tabmargin}px;${dispsub}'>"
               . "<ul id='ulcc_${compgroup_id}' style='margin-left:5px;' class='axul'>";
         $csno = 0;
         foreach($cset as $competency_class) {
            if($_SESSION["assessor360"]==1&&$competency_class!="soft") continue;
            $cchdr = ucfirst($competency_class);
            if($csno==0) {
               $class="class='ccsel'";
            } else {
               $class="class='ccunsel'";
            }
            $cse .= "<li id='tabcc_${compgroup_id}_${competency_class}' onclick='swcc(\"$compgroup_id\",\"$competency_class\",this,event);' style='width:100px;' $class><span>$cchdr</span></li>";
            $csno++;
         }
         $cse .= ($compgroup_id==3?"<li class='compclasstips'><span style='font-style:italic;'>Please click employee name on the left to see their competency profile.</span></li>":"");
         $cse .= "<li style='visibility:hidden;'><span>&nbsp;</span></li>";
         $cse .= "</ul><div style='clear:both;'></div></div>";
      }
      $tab_arr_js = substr($tab_arr_js,0,-1) . ");";
      $ret .= "</ul><div style='clear:both;'></div></div>";
      
      $ret .= $cse;
      
      $lpos = array(0=>-199,1=>-184,2=>-169,3=>-155,4=>-140);
      $kpos = array(0=>-200,1=>-175,2=>-150,3=>-125,4=>-100);
      
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
            
            if($_SESSION["assessment_page"]=="assessment_idp") {
               $idp_employee_id = $_SESSION["assessment_idp_employee_id"];
               $sql = "SELECT a.employee_id,b.job_id,c.job_nm,c.job_cd,c.job_abbr,c.org_id,c.description,c.summary,d.job_level,''"
                    . " FROM ".XOCP_PREFIX."employee a"
                    . " LEFT JOIN ".XOCP_PREFIX."employee_job b USING(employee_id)"
                    . " LEFT JOIN ".XOCP_PREFIX."jobs c USING(job_id)"
                    . " LEFT JOIN ".XOCP_PREFIX."job_class d USING(job_class_id)"
                    . " WHERE a.employee_id = '$idp_employee_id'"
                    . " AND b.job_id IS NOT NULL"
                    . " ORDER BY c.job_class_id";
               _debuglog($sql);
            } else {
               if($_SESSION["assessor360"]==1) {
                  switch($_SESSION["hris_asmgroup"]) {
                     case "superior":
                        $qasmgroup = " AND a.assessor_t = 'subordinat'";
                        break;
                     case "customer":
                        $qasmgroup = " AND a.assessor_t = 'customer'";
                        break;
                     case "peer":
                     default:
                        $qasmgroup = " AND a.assessor_t = 'peer'";
                        break;
                  }
                  $sql = "SELECT a.employee_id,b.job_id,c.job_nm,c.job_cd,c.job_abbr,c.org_id,c.description,c.summary,d.job_level,a.assessor_t"
                       . " FROM ".XOCP_PREFIX."assessor_360 a"
                       . " LEFT JOIN ".XOCP_PREFIX."employee_job b USING(employee_id)"
                       . " LEFT JOIN ".XOCP_PREFIX."jobs c USING(job_id)"
                       . " LEFT JOIN ".XOCP_PREFIX."job_class d USING(job_class_id)"
                       . " WHERE a.assessor_id = '$self_employee_id'"
                       . " AND a.asid = '$asid'"
                       . " AND a.status_cd = 'active'"
                       . $qasmgroup
                       . " AND b.job_id IS NOT NULL"
                       . " ORDER BY c.job_class_id";
               } else {
                  $sql = "SELECT a.job_id,a.job_nm,a.job_cd,a.job_abbr,a.org_id,a.description,a.summary,b.job_level"
                       . " FROM ".XOCP_PREFIX."jobs a"
                       . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
                       . " WHERE a.assessor_job_id = '$assessor_job_id'"
                       . " ORDER BY a.job_class_id";
                  $sql = "SELECT a.employee_id,b.job_id,c.job_nm,c.job_cd,c.job_abbr,c.org_id,c.description,c.summary,d.job_level,a.assessor_t"
                       . " FROM ".XOCP_PREFIX."assessor_360 a"
                       . " LEFT JOIN ".XOCP_PREFIX."employee_job b USING(employee_id)"
                       . " LEFT JOIN ".XOCP_PREFIX."jobs c USING(job_id)"
                       . " LEFT JOIN ".XOCP_PREFIX."job_class d USING(job_class_id)"
                       . " WHERE a.assessor_id = '$self_employee_id'"
                       . " AND a.asid = '$asid'"
                       . " AND a.status_cd = 'active'"
                       . " AND a.assessor_t = 'superior'"
                       . " AND b.job_id IS NOT NULL"
                       . " ORDER BY c.job_class_id";
                  
               }
            }
            
            
            
            
            $res = $db->query($sql);
            
            
            
            
            $no = 0;
            if($db->getRowsNum($res)>0) {
               while($rrow=$db->fetchRow($res)) {
                  
                  
                  if($_SESSION["assessment_page"]=="assessment_idp") {
                     $idp_employee_id = $_SESSION["assessment_idp_employee_id"];
                     list($employee_idx,$job_id,$job_nm,$job_cd,$job_abbr,$org_id,$job_descx,$job_summary,$job_level,$assessor_t)=$rrow;
                  } else {
                     if($_SESSION["assessor360"]==1) {
                        list($employee_idx,$job_id,$job_nm,$job_cd,$job_abbr,$org_id,$job_descx,$job_summary,$job_level,$assessor_t)=$rrow;
                        if($assessor_t=="subordinat") {
                           $assessor_t_txt = "Superior";
                        } else {
                           $assessor_t_txt = ucfirst($assessor_t);
                        }
                     } else {
                        //list($job_id,$job_nm,$job_cd,$job_abbr,$org_id,$job_descx,$job_summary,$job_level)=$rrow;
                        list($employee_idx,$job_id,$job_nm,$job_cd,$job_abbr,$org_id,$job_descx,$job_summary,$job_level,$assessor_t)=$rrow;
                     }
                  }
                  
                  
                  
                  $job_summary = str_replace("\n","",$job_summary);
                  
                  if($_SESSION["assessment_page"]=="assessment_idp") {
                     $idp_employee_id = $_SESSION["assessment_idp_employee_id"];
                     $sql = "SELECT a.employee_id,b.employee_ext_id,c.person_nm,a.gradeval,c.birth_dttm,c.birthplace,"
                          . "c.adm_gender_cd,c.addr_txt,c.cell_phone,c.home_phone,c.marital_st,"
                          . "b.entrance_dttm,a.start_dttm,a.stop_dttm,(TO_DAYS(now())-TO_DAYS(b.entrance_dttm)) as jobage,"
                          . "c.person_id"
                          . " FROM ".XOCP_PREFIX."employee_job a"
                          . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
                          . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
                          . " WHERE a.employee_id = '$employee_idx'";
                  } else {
                     if($_SESSION["assessor360"]==1) {
                        $sql = "SELECT a.employee_id,b.employee_ext_id,c.person_nm,a.gradeval,c.birth_dttm,c.birthplace,"
                             . "c.adm_gender_cd,c.addr_txt,c.cell_phone,c.home_phone,c.marital_st,"
                             . "b.entrance_dttm,a.start_dttm,a.stop_dttm,(TO_DAYS(now())-TO_DAYS(b.entrance_dttm)) as jobage,"
                             . "c.person_id"
                             . " FROM ".XOCP_PREFIX."employee_job a"
                             . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
                             . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
                             . " WHERE a.employee_id = '$employee_idx'";
                     } else {
                        $sql = "SELECT a.employee_id,b.employee_ext_id,c.person_nm,a.gradeval,c.birth_dttm,c.birthplace,"
                             . "c.adm_gender_cd,c.addr_txt,c.cell_phone,c.home_phone,c.marital_st,"
                             . "b.entrance_dttm,a.start_dttm,a.stop_dttm,(TO_DAYS(now())-TO_DAYS(b.entrance_dttm)) as jobage,"
                             . "c.person_id"
                             . " FROM ".XOCP_PREFIX."employee_job a"
                             . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
                             . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
                             . " WHERE a.job_id = '$job_id'";
                        $sql = "SELECT a.employee_id,b.employee_ext_id,c.person_nm,a.gradeval,c.birth_dttm,c.birthplace,"
                             . "c.adm_gender_cd,c.addr_txt,c.cell_phone,c.home_phone,c.marital_st,"
                             . "b.entrance_dttm,a.start_dttm,a.stop_dttm,(TO_DAYS(now())-TO_DAYS(b.entrance_dttm)) as jobage,"
                             . "c.person_id"
                             . " FROM ".XOCP_PREFIX."employee_job a"
                             . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
                             . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
                             . " WHERE a.employee_id = '$employee_idx'";
                     }
                  }
                  $res2 = $db->query($sql);
                  if($db->getRowsNum($res2)>0) {
                     while(list($employee_id,$nip,$employee_nm,$gradeval,$dob,$pob,$gender,$addr,$cellphone,$phone,$marital,
                                $entrance_dttm,$jobstart,$jobstop,$jobage,$person_id)=$db->fetchRow($res2)) {
                        $arr_employee[$employee_id] = array($employee_nm,$nip,$job_nm,$job_abbr,$job_id,$gradeval,$job_desc,$dob,$pob,$gender,$addr,$cellphone,$phone,$marital,
                                                            $entrance_dttm,$jobstart,$jobstop,$jobage,$person_id);
                        $arrmtrx[$employee_id][$job_id] = array($employee_id,$job_id);
                        $mtrx .= "$employee_id.$job_id-";
                        
                        //_calculate_competency($asid,$employee_id,$job_id);
                        
                        
                        if($_SESSION["assessment_page"]=="assessment_idp") {
                           $arr_idp_comp = array();
                           $sql = "SELECT idp_request_id FROM ".XOCP_PREFIX."assessment_session WHERE asid = '$asid'";
                           $rreq = $db->query($sql);
                           list($idp_request_id)=$db->fetchRow($rreq);
                           $sql = "SELECT competency_id FROM ".XOCP_PREFIX."idp_request_competency WHERE request_id = '$idp_request_id'";
                           $rreqc = $db->query($sql);
                           if($db->getRowsNum($rreqc)>0) {
                              while(list($idp_competency_id)=$db->fetchRow($rreqc)) {
                                 $arr_idp_comp[$idp_competency_id] = 1;
                              }
                           }
                        } else {
                           $arr_idp_comp = NULL;
                        }
                        
                        $arr_param = $arr_xttl_rcl = $arr_comp_class = $arr_comp = $arr_job_competency = $arr_job_rcl = array();
                        
                        
                        $sql = "SELECT a.rcl,a.itj,a.competency_id,b.compgroup_id,b.competency_cd,"
                             . "b.competency_abbr,b.competency_nm,b.competency_class,b.desc_en,b.desc_id"
                             . " FROM ".XOCP_PREFIX."job_competency a"
                             . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
                             . " WHERE a.job_id = '$job_id'";
                        $resrcl = $db->query($sql);
                        $arr_xttl_rcl = array();
                        if($db->getRowsNum($resrcl)>0) {
                           while(list($rcl,$itj,$competency_id,$compgroup_id,$competency_cd,
                                      $competency_abbr,$competency_nm,$competency_class,$desc_en,$desc_id)=$db->fetchRow($resrcl)) {
                              
                              if($_SESSION["assessment_page"]=="assessment_idp") {
                                 if(!isset($arr_idp_comp[$competency_id])) {
                                    continue;
                                 }
                              }
                              
                              $arr_param[$competency_id] = array($rcl,$itj);
                              $arr_xttl_rcl[$job_id] += ($rcl*$itj);
                              $arr_comp_class[$compgroup_id][$competency_class][$competency_id] = array($rcl,$itj);
                              $arr_comp[$competency_id] = array($competency_nm,$competency_abbr,$desc_en,$desc_id);
                              $arr_job_competency[$job_id][$competency_id] = 1;
                              $arr_job_rcl[$competency_id][$job_id] = array($rcl,$itj);
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
                        
                        
                        $person_info = "<table style='width:100%;'><tbody><tr><td style='vertical-align:top;'><img src='".XOCP_SERVER_SUBDIR."/modules/hris/thumb.php?pid=$person_id' height='100'/></td>"
                                     . "<td style='vertical-align:top;'><table class='emp_info' style='margin-top:0px;width:290px;'>"
                                     . "<colgroup><col width='80'/><col/></colgroup>"
                                     . "<tbody>"
                                     . "<tr><td>Employee ID :</td><td>$nip</td></tr>"
                                     . "<tr><td>Entrance Date :</td><td>".sql2ind($entrance_dttm,"date")." ("._bctrim(toMoney($jobage/365.25))." year)</td></tr>"
                                     . "<tr><td>Job Assigned :</td><td>".sql2ind($jobstart,"date")."</td></tr>"
                                     . "<tr><td>Gender :</td><td>".($gender=="f"?"Female":"Male")."</td></tr>"
                                     . "<tr><td>Previous Job :</td><td></td></tr></tbody></table></td></tr></tbody></table>";
                        $tooltips .= "\nnew Tip('empjob_${employee_id}_${job_id}', '<div style=\"font-weight:bold;\">Job Summary:</div>".addslashes($job_summary)."', {viewport:true,title:'".addslashes($job_nm)."',style:'emp'});";
                        $tooltips .= "\nnew Tip('emp_${employee_id}_${job_id}', \"".addslashes($person_info)."\", {title:'".addslashes($employee_nm)."',width:350,style:'emp'});";
                        
                        $matchbox = "<div class='match' style='width:100px;border:1px solid #999999;'><img src='".XOCP_SERVER_SUBDIR."/modules/hris/images/level_foreground.png' style='margin-left:".(int)(-200+($match))."px;'/></div>"
                                  . "<table class='emp_info'><tbody>"
                                  . "<tr><td>Total Current Competency Level:</td><td>".number_format($arr_ttl_ccl[$employee_id],2,".","")."</td></tr>"
                                  . "<tr><td>Total Required Competency Level:</td><td>".$arr_ttl_rcl[$employee_id]."</td></tr>"
                                  . "<tr><td>Job Match:</td><td>${match}%</td></tr>"
                                  . "</tbody></table>";
                        
                        if($_SESSION["assessor360"]==1) {
                           $match_txt = "-";
                           $clr = "";
                        } else {
                           //$tooltips .= "\nnew Tip('match_${employee_id}_${job_id}', \"".addslashes($matchbox)."\", {title:'Job Match Detail',width:250,style:'emp'});";
                           $match_txt = "$match%";
                        }
                        
                        
                        if($job_level=="nonmanagement") {
                           $jl = $gradeval;
                        } else {
                           $jl = "-";
                        }
                        
                        /// $sql = "SELECT ttlccl,ttlrcl,ttlgap,jm,cf FROM ".XOCP_PREFIX."employee_competency_final_recap" /// commented on 2012-01-16
                        $sql = "SELECT ttlcclxxx,ttlrcl,ttlgapxxx,jmxxx,cfxxx FROM ".XOCP_PREFIX."employee_competency_final_recap"
                             . " WHERE asid = '$asid'"
                             . " AND employee_id = '$employee_id'"
                             . " AND job_id = '$job_id'";
                        $result = $db->query($sql);
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
                        
                        $employee_list .= "<tr id='tremp_${employee_id}_${job_id}' class='trd0'>"
                                          . "<td onclick='select_emp(\"$employee_id\",\"$job_id\");' class='th0' width='100' id='emp_${employee_id}_${job_id}'><div><div>$employee_nm</div></div></td>"
                                          . "<td class='th1' id='empjob_${employee_id}_${job_id}'><div><div>$job_abbr</div></div></td>"
                                          . ($_SESSION["assessor360"]==1?"<td class='th2c' colspan='2'><div><div>".ucfirst($assessor_t_txt)."</div></div></td>"
                                          : "<td class='th2'><div><div>$jl</div></div></td>"
                                          . "<td id='match_${employee_id}_${job_id}' class='th3' style='$clr'><div><div>$match_txt</div></div></td>")
                                          
                                          . "<td class='thx'><div><div>"
                                             . "<table class='lvl'><tbody class='lvl'><tr id='trlvl_${employee_id}_${job_id}'>"
                                                ."<td></td>"
                                                ."<td></td>"
                                                ."<td></td>"
                                                ."<td></td>"
                                                ."<td></td>"
                                                ."<td></td>"
                                                ."<td></td>"
                                                ."<td></td>"
                                                ."<td></td>"
                                                ."<td></td>"
                                                ."<td></td>"
                                                ."<td></td>"
                                                ."<td></td>"
                                                ."<td></td>"
                                                ."<td></td>"
                                                ."<td></td>"
                                                ."<td></td>"
                                                ."<td></td>"
                                                ."<td></td>"
                                                ."<td></td>"
                                                ."<td></td>"
                                                ."<td></td>"
                                                ."<td></td>"
                                                ."<td></td>"
                                             . "</tr></tbody></table></div></div>"
                                          . "</td>"
                                        . "</tr>";
                        $no++;
                     }
                  } else {
                     continue;
                     $tooltips .= "\nnew Tip('empjob_0_${job_id}', '<div style=\"font-weight:bold;\">Job Summary:</div>".addslashes($job_summary)."', {viewport:true,title:'".addslashes($job_nm)."',style:'emp'});";
                     
                     $employee_list .= "<tr id='eemp_0_${job_id}' class='trd2'>"
                                       . "<td class='th0'><div><div style='color:#bbbbbb;font-style:italic;padding-left:30px;font-weight:normal;'>Empty</div></div></td>"
                                       . "<td class='th1' id='empjob_0_${job_id}'><div><div>$job_abbr</div></div></td>"
                                       . ($_SESSION["assessor360"]==1?"<td class='th2c' colspan='2'><div><div>-</div></div></td>":"<td class='th2'><div><div>-</div></div></td>"
                                       . "<td class='th3'><div><div>-</div></div></td>")
                                       . "<td class='thx' style=''><div><div>"
                                          . "<table class='lvl'><tbody class='lvl'><tr id='trlvl_0_${job_id}'>"
                                             ."<td></td>"
                                             ."<td></td>"
                                             ."<td></td>"
                                             ."<td></td>"
                                             ."<td></td>"
                                             ."<td></td>"
                                             ."<td></td>"
                                             ."<td></td>"
                                             ."<td></td>"
                                             ."<td></td>"
                                             ."<td></td>"
                                             ."<td></td>"
                                             ."<td></td>"
                                             ."<td></td>"
                                             ."<td></td>"
                                             ."<td></td>"
                                             ."<td></td>"
                                             ."<td></td>"
                                             ."<td></td>"
                                             ."<td></td>"
                                             ."<td></td>"
                                             ."<td></td>"
                                             ."<td></td>"
                                             ."<td></td>"
                                          . "</tr></tbody></table></div>"
                                       . "</td>"
                                     . "</tr>";
                     $no++;
                  }
               }
            } else {
               return "You don't have any assessi. [<a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&diagram=y&asid=$asid'>Diagram</a>]";
            }
         }
      }
      
      if($assessor_job_count==0) {
         return "You don't have a job assigned. Please contact HR Administrator.";
      }
      
      $ret .= "<div style='padding:0px;border:1px solid #999999;'>"
            
            . "<div class='outer'><div class='innera'>"
            . "<table class='tbla' style='width:100%;table-layout:fixed;'>"
            . "<colgroup><col width='119'/><col width='78'/><col width='48'/><col width='59'/><col width='*'/></colgroup>"
            . "<caption><div style='overflow:hidden;'><div style='width:10000px;'>"
                        . "<table><tbody><tr id='trhdr'>"
                           . "<td class='th0'>Employee</td>"
                           . "<td class='th1'>Job</td>"
                           . ($_SESSION["assessor360"]==1?"<td style='width:97px;' class='th2c' colspan='2'>Related As</td>":"<td class='th2'>Grade</td>"
                           . "<td class='th3' style='text-align:center;'>Job Match</td>")
                           . "<td class='th4'></td>"
                           . "<td class='th4'></td>"
                           . "<td class='th4'></td>"
                           . "<td class='th4'></td>"
                           . "<td class='th4'></td>"
                           . "<td class='th4'></td>"
                           . "<td class='th4'></td>"
                           . "<td class='th4'></td>"
                           . "<td class='th4'></td>"
                           . "<td class='th4'></td>"
                           . "<td class='th4'></td>"
                           . "<td class='th4'></td>"
                           . "<td class='th4'></td>"
                           . "<td class='th4'></td>"
                           . "<td class='th4'></td>"
                           . "<td class='th4'></td>"
                           . "<td class='th4'></td>"
                           . "<td class='th4'></td>"
                        . "</tr></tbody></table>"
               . "</div></div>"
            . "</caption>"
            
            . "<tbody class='bodymtrx' id='tbody_mtrx'>"
               . $employee_list
            . "</tbody>"
            
            . "</table>"
            . "</div></div>" /// outer - innera
            
            . "</div>"
            . "</div>"; /// wrapper

      $ret .= "<div style='width:900px;'>Untuk menampilkan berbagai informasi silakan gerakan posisi mouse diatas sel-sel yang dituju. Untuk memulai assessment silakan klik."
            . "<hr noshade='1' size='1' color='#bbbbbb;'/>"
            . "<span style='font-style:italic;'>To see any available information please position your mouse over the cells. To start assessment please click on it.</span></div>";
      $ret .= "<script type='text/javascript' src='".XOCP_SERVER_SUBDIR."/include/prototip2.0.5/js/prototip.js'></script>"
           . "<link rel='stylesheet' type='text/css' href='".XOCP_SERVER_SUBDIR."/include/prototip2.0.5/css/prototip.css' />";
      
      $mtrx = substr($mtrx,0,-1);
      
      $ret .= "<script type='text/javascript'><!--
      
      $tooltips
      $tab_arr_js
      
      var empx = GetCookie('empx');
      var jobx = GetCookie('jobx');
      
      function setmark() {
         var r = $('tbody_mtrx').childNodes;
         for(var j=0;j<r.length;j++) {
            if(r[j].id&&r[j].id!='') {
               var trempid = r[j].id.split('_');
               var employee_id = trempid[1];
               var job_id = trempid[2];
               if(empx&&jobx&&employee_id==empx&&job_id==jobx) {
                  r[j].className = 'trd1';
               } else {
                  if(r[j].className=='trd1') {
                     r[j].className = 'trd0';
                  }
               }
            }
         }
      }
      setmark();
      
      function select_emp(employee_id,job_id) {
         empx = employee_id;
         jobx = job_id;
         SetCookie('empx',empx);
         SetCookie('jobx',jobx);
         setmark();
         var ctab = gtab();
         if(ctab[0]==3) {
            setcompheader();
            setlevel();
         }
      }
      
      function swcg(compgroup_id,d,e) {
         for(var i=0;i<tabs.length;i++) {
            $('tabcg_'+tabs[i]).className = '';
            $('cgdiv_'+tabs[i]).style.display = 'none';
         }
         $('tabcg_'+compgroup_id).className = 'compsel';
         $('cgdiv_'+compgroup_id).style.display = '';
         setcompheader();
         setlevel();
         var ctab = gtab();
         SetCookie('cg',ctab[0]);
         SetCookie('cc',ctab[1]);
      }
      
      function swcc(compgroup_id,competency_class,d,e) {
         var ccarr = $('ulcc_'+compgroup_id).getElementsByTagName('li');
         for(var i=0;i<ccarr.length;i++) {
            if(ccarr[i].className=='compclasstips') continue;
            ccarr[i].className = '';
         }
         $('tabcc_'+compgroup_id+'_'+competency_class).className = 'ccsel';
         setcompheader();
         setlevel();
         var ctab = gtab();
         SetCookie('cg',ctab[0]);
         SetCookie('cc',ctab[1]);
      }
      
      function gtab() {
         for(var i=0;i<tabs.length;i++) {
            if($('tabcg_'+tabs[i]).className=='compsel') {
               var ccarr = $('ulcc_'+tabs[i]).getElementsByTagName('li');
               for(var j=0;j<ccarr.length;j++) {
                  if(ccarr[j].className=='ccsel') {
                     var xid = ccarr[j].id.split('_');
                     return new Array(tabs[i],xid[2]);
                  }
               }
            }
         }
      }
      
      var lpos = new Array();
      lpos[0] = -200;
      lpos[1] = -166;
      lpos[2] = -151;
      lpos[3] = -136;
      lpos[4] = -121;
      
      var kpos = new Array();
      kpos[0] = -100;
      kpos[1] = -75;
      kpos[2] = -61;
      kpos[3] = -46;
      kpos[4] = -31;
      
      function setlevel() {
         var ctab = gtab();
         var tds = $('trhdr').childNodes;
         var tdcn = ".($_SESSION["assessor360"]==1?"3":"4").";
         var r = $('tbody_mtrx').childNodes;
         
         /// clean up high itj background first
         for(var j=0;j<r.length;j++) {
            if(r[j].id&&r[j].id!=''&&r[j].id.substring(0,5)=='tremp') {
               var trempid = r[j].id.split('_');
               var employee_id = trempid[1];
               var job_id = trempid[2];
               var tr = $('trlvl_'+employee_id+'_'+job_id);
               var tdx = tr.childNodes;
               var no=0;
               for(var i=tdcn;i<tds.length;i++) {
                  tdx[no].style.backgroundColor = '';
                  no++;
               }
            }
         }
         
         for(var j=0;j<r.length;j++) {
            if(r[j].id&&r[j].id!=''&&r[j].id.substring(0,5)=='tremp') {
               var trempid = r[j].id.split('_');
               var employee_id = trempid[1];
               var job_id = trempid[2];
               var tr = $('trlvl_'+employee_id+'_'+job_id);
               var tdx = tr.childNodes;
               var no=0;
               for(var i=tdcn;i<tds.length;i++) {
                  if(tdx[no].prototip) tdx[no].prototip.remove();
                  if(tds[i].id&&tds[i].id!='') {
                     var idx = tds[i].id.split('_');
                     var competency_id = idx[1];
                     tdx[no].innerHTML = '';
                     tdx[no].onclick = '';
                     tdx[no].id = 'tdx_'+employee_id+'_'+competency_id;
                     var rcl = -1;
                     var itj = 0;
                     
                     if(matrix_job[job_id]&&matrix_job[job_id][competency_id]) {
                        rcl = matrix_job[job_id][competency_id][2];
                        itj = matrix_job[job_id][competency_id][3];
                     }
                     
                     var ccl = 0;
                     var real_ccl = 0;
                     var gap = -rcl*itj;
                     var la = 'never';
                     var last_level = 0;
                     
                     if(matrix_level[employee_id]&&matrix_level[employee_id][job_id]) {
                        for(var k=0;k<matrix_level[employee_id][job_id].length;k++) {
                           if(matrix_level[employee_id][job_id][k][2]==competency_id) {
                              ccl = matrix_level[employee_id][job_id][k][3];
                              real_ccl = matrix_level[employee_id][job_id][k][10];
                              gap = matrix_level[employee_id][job_id][k][6];
                              last_level = matrix_level[employee_id][job_id][k][9];
                              if(matrix_level[employee_id][job_id][k][7]>=0) {
                                 la = sql2string(matrix_level[employee_id][job_id][k][8]);
                              }
                           }
                        }
                     }
                     
                     if(rcl>=0) {
                        var dv = _dce('div');
                        dv.setAttribute('class','cl');
                        //dv.setAttribute('style','background-position:'+kpos[rcl]+'px;');
                        tdx[no].appendChild(dv);
                        if(itj>2) {
                           tdx[no].style.backgroundColor = '#ffff99';
                        } else {
                           tdx[no].style.backgroundColor = '';
                        }
                        
                        var imgrcl = _dce('img');
                        imgrcl.setAttribute('src','".XOCP_SERVER_SUBDIR."/modules/hris/images/level_rcl.png');
                        imgrcl.setAttribute('style','margin-left:'+parseInt(lpos[rcl])+'px;');
                        var xdv = dv.appendChild(_dce('div'));
                        xdv.style.height='7px';
                        xdv.style.overflow = 'hidden';
                        xdv.appendChild(imgrcl);
                        
                        var imglastlevel = _dce('img');
                        imglastlevel.setAttribute('src','".XOCP_SERVER_SUBDIR."/modules/hris/images/level_gap.png');
                        imglastlevel.setAttribute('style','margin-left:'+parseInt(lpos[last_level])+'px;');
                        var xdv = dv.appendChild(_dce('div'));
                        xdv.style.height='7px';
                        // xdv.style.width = parseInt(lpos[last_level])+'px';
                        xdv.style.overflow = 'hidden';
                        xdv.appendChild(imglastlevel);
                        
                        var img = _dce('img');
                        img.setAttribute('src','".XOCP_SERVER_SUBDIR."/modules/hris/images/level_ccl.png');
                        img.setAttribute('style','margin-left:'+parseInt(lpos[ccl])+'px;');
                        var xdv = dv.appendChild(_dce('div'));
                        xdv.style.height='7px';
                        xdv.style.overflow = 'hidden';
                        xdv.appendChild(img);
                        xdv.style.marginTop = '-7px';
                        
                        
                        
                        tdx[no].onclick = function() {
                           var asmx = this.id.split('_');
                           asm(asmx[1],asmx[2]);
                        }
                        var content = '<div class=\"cl\" style=\"width:59px;border:1px solid #999999;background-position:-121px;\">'
                                    + '<div style=\"height:7px;overflow:hidden;\">'
                                    + '<img style=\"margin-left:'+lpos[rcl]+'px;\" src=\"".XOCP_SERVER_SUBDIR."/modules/hris/images/level_rcl.png\"/>'
                                    + '</div>'
                                    
                                    + '<div style=\"height:7px;overflow:hidden;\">'
                                    + '<img style=\"margin-left:'+lpos[last_level]+'px;\" src=\"".XOCP_SERVER_SUBDIR."/modules/hris/images/level_gap.png\"/>'
                                    + '</div>'
                                    
                                    + '<div style=\"margin-top:-7px;height:7px;overflow:hidden;\">'
                                    + '<img style=\"margin-left:'+lpos[ccl]+'px;\" src=\"".XOCP_SERVER_SUBDIR."/modules/hris/images/level_ccl.png\"/>'
                                    + '</div>'
                                    + '</div>'
                                    + '<table class=\"emp_info\"><tbody>'
                                    + '<tr><td>Current Competency Level:</td><td style=\"text-align:right;\">'+real_ccl+'</td></tr>'
                                    + '<tr><td>Required Competency Level:</td><td style=\"text-align:right;\">'+rcl+'</td></tr>'
                                    + '<tr><td>Importance to Job:</td><td style=\"text-align:right;\">'+matrix_job[job_id][competency_id][3]+'</td></tr>'
                                    + '<tr><td>Gap:</td><td style=\"text-align:right;\">'+gap+'</td></tr>'
                                    + '</tbody></table>'
                                    + '<div style=\"text-align:center;font-weight:bold;\">Last assessment: '+la+'</div>'
                                    + '<div style=\"text-align:center;color:#9999ff;\">Click to start assessment process.</div>';
                        tdx[no].tip = new Tip(tdx[no],content,{style:'emp',title:matrix_comp[competency_id][3],offset:{x:0,y:10},hook:{tip:'topLeft',mouse:true}});
                        
                        
                     } else {
                        tdx[no].tip = new Tip(tdx[no],'Competency Not Applicable.',{style:'emp',width:180});
                     }
                  } else {
                     tdx[no].innerHTML = '';
                     tdx[no].onclick = '';
                  }
                  no++;
               }
            } else {
               /*
               var trempid = r[j].id.split('_');
               var employee_id = trempid[1];
               var job_id = trempid[2];
               var tr = $('trlvl_0_'+job_id);
               var tdx = tr.childNodes;
               var no=0;
               for(var i=4;i<tds.length;i++) {
                  if(tdx[no].prototip) tdx[no].prototip.remove();
                  if(tds[i].id&&tds[i].id!='') {
                     tdx[no].tip = new Tip(tdx[no],'Job Not Assigned.',{style:'emp',width:180});
                  }
                  no++;
               }
               */
            }
         }
      }
      
      var comp_col = null;
      function setcompheader() {
         var ctab = gtab();
         var hdr = $('trhdr');
         var tds = hdr.childNodes;
         var tdc = ".($_SESSION["assessor360"]==1?"3":"4").";
         var tdcn = ".($_SESSION["assessor360"]==1?"3":"4").";
         comp_col = new Array();
         var compgroup_id = ctab[0];
         var competency_class = ctab[1];
         
         if(matrix_compgroup[compgroup_id]&&matrix_compgroup[compgroup_id][competency_class]) {
            for(var i=0;i<matrix_compgroup[compgroup_id][competency_class].length;i++) {
                  var competency_id = matrix_compgroup[compgroup_id][competency_class][i][0];
                  if(tds[tdc]) {
                     if(ctab[0]==3) {
                        if(matrix_job[jobx]&&matrix_job[jobx][competency_id]) {
                        } else {
                           continue;
                        }
                     }
                     tds[tdc].innerHTML = matrix_compgroup[compgroup_id][competency_class][i][4];
                     tds[tdc].setAttribute('id','competency_'+competency_id);
                     tds[tdc].tip = new Tip('competency_'+competency_id,matrix_compgroup[compgroup_id][competency_class][i][5],{stem:'leftTop',style:'emp',title:matrix_compgroup[compgroup_id][competency_class][i][3],hook:{tip:'leftTop',mouse:true},offset:{x:10,y:-5}});
                     tdc++;
               }
            }
         }
         if(tdc==tdcn) { /// competency not found
            tds[tdc].innerHTML = '( n.a. )';
            tds[tdc].setAttribute('id','');
            tds[tdc].tip = new Tip(tds[tdc],'Please select another employee to see their competency profile for this category by clicking their name.',{stem:'leftTop',style:'emp',title:'Not Applicable',hook:{tip:'leftTop',target:'rightMiddle'},offset:{x:-5,y:-5},hideAfter:15,hideOn:false});
            // tds[tdc].prototip.show();
            tdc++;
         }
         
         for(var i=tdc;i<20;i++) {
            if(tds[i]) {
               tds[i].innerHTML = '';
               if(tds[i].id) {
                  Tips.remove(tds[i].id);
               }
               tds[i].setAttribute('id','');
            }
         }
      }
      
      // var comp_mtrx = null;
      var lvl_mtrx = null;
      var matrix_level = new Array();
      var matrix_compgroup = new Array();
      var matrix_comp = new Array();
      var matrix_job = new Array();
      function loadMatrix() {
         ajax_feedback = _caf;
         asjx_app_loadMatrix('$mtrx',function(_data) {
            if(_data=='EMPTY') {
               alert('Fail to load data.');
               return;
            }
            if(_data=='NOCOMPETENCYDEFINED') {
               alert('No competency found for the jobs. Please setup first.');
               return;
            }
            var data = recjsarray(_data);
            // comp_mtrx = data[0];
            for(var i=0;i<data[0].length;i++) {
               var competency_id = data[0][i][0];
               var compgroup_id = data[0][i][1];
               var competency_class = data[0][i][2];
               matrix_comp[competency_id] = data[0][i];
               if(!matrix_compgroup[compgroup_id]) {
                  matrix_compgroup[compgroup_id] = new Array();
               }
               if(!matrix_compgroup[compgroup_id][competency_class]) {
                  matrix_compgroup[compgroup_id][competency_class] = new Array();
               }
               matrix_compgroup[compgroup_id][competency_class].push(data[0][i]);
            }
            lvl_mtrx = data[1];
            
            for(var i=0;i<data[1].length;i++) {
               var employee_id = data[1][i][0];
               var job_id = data[1][i][1];
               if(!matrix_level[employee_id]) {
                  matrix_level[employee_id] = new Array();
               }
               if(!matrix_level[employee_id][job_id]) {
                  matrix_level[employee_id][job_id] = new Array();
               }
               matrix_level[employee_id][job_id].push(data[1][i]);
            }
            
            for(var i=0;i<data[2].length;i++) {
               var job_id = data[2][i][0];
               var competency_id = data[2][i][1];
               if(!matrix_job[job_id]) {
                  matrix_job[job_id] = new Array();
               }
               matrix_job[job_id][competency_id] = data[2][i];
            }
            var compgroup_id = GetCookie('cg');
            var competency_class = GetCookie('cc');
            if($('tabcg_'+compgroup_id)) {
               if(compgroup_id&&compgroup_id>0) {
                  swcg(compgroup_id,null,null);
                  if($('tabcc_'+compgroup_id+'_'+competency_class)) {
                     if(competency_class&&competency_class!='') {
                        swcc(compgroup_id,competency_class);
                     }
                  }
               }
            }
            
            setcompheader();
            setlevel();
         });
      }
      
      loadMatrix();
      
      function asm(employee_id,competency_id) {
         location = '".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&asm=y&eid='+employee_id+'&cid='+competency_id;
      }
      
      
      // --></script>";
      
      $ajax = new _hris_class_AssessmentAjax("asjx");
      
      return $ajax->getJs().$ret."<div>&nbsp;</div>";
         
   }
   
   function form($employee_id,$competency_id) {
      $db=&Database::getInstance();
      $asid = $_SESSION["hris_assessment_asid"];
      $self_employee_id = $_SESSION["self_employee_id"];
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_assessment.php");
      $ajax = new _hris_class_AssessmentAjax("asjx");
      $sql = "SELECT a.employee_ext_id,b.person_nm"
           . " FROM ".XOCP_PREFIX."employee a"
           . " LEFT JOIN ".XOCP_PREFIX."persons b USING(person_id)"
           . " WHERE a.employee_id = '$employee_id'";
      $result = $db->query($sql);
      list($nip,$employee_nm)=$db->fetchRow($result);
      $sql = "SELECT b.employee_ext_id,c.person_nm,a.gradeval,c.birth_dttm,c.birthplace,"
           . "c.adm_gender_cd,c.addr_txt,c.cell_phone,c.home_phone,c.marital_st,"
           . "b.entrance_dttm,a.start_dttm,a.stop_dttm,(TO_DAYS(now())-TO_DAYS(b.entrance_dttm)) as jobage,"
           . "a.job_id,d.job_cd,d.job_nm,c.person_id"
           . " FROM ".XOCP_PREFIX."employee_job a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " LEFT JOIN ".XOCP_PREFIX."jobs d ON d.job_id = a.job_id"
           . " WHERE a.employee_id = '$employee_id'";
      $res2 = $db->query($sql);
      list($nip,$employee_nm,$gradeval,$dob,$pob,$gender,$addr,$cellphone,$phone,$marital,
           $entrance_dttm,$jobstart,$jobstop,$jobage,$job_id,$job_cd,$job_nm,$person_id)=$db->fetchRow($res2);

      $rcl = 0; $itj = 0;
      $sql = "SELECT rcl,itj FROM ".XOCP_PREFIX."job_competency"
           . " WHERE job_id = '$job_id'"
           . " AND competency_id = '$competency_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($rcl,$itj)=$db->fetchRow($result);
      }
      
      $_SESSION["empl_job_id"] = $job_id;
      $person_info = "<table style='width:100%;'><tbody><tr><td style='vertical-align:top;'><img src='".XOCP_SERVER_SUBDIR."/modules/hris/thumb.php?pid=$person_id' height='100'/></td>"
                   . "<td style='vertical-align:top;'><table class='emp_info' style='margin-top:0px;width:290px;'>"
                   . "<colgroup><col width='80'/><col/></colgroup>"
                   . "<tbody>"
                   . "<tr><td>Employee ID :</td><td>$nip</td></tr>"
                   . "<tr><td>Entrance Date :</td><td>".sql2ind($entrance_dttm,"date")." ("._bctrim(toMoney($jobage/365.25))." year)</td></tr>"
                   . "<tr><td>Job Assigned :</td><td>".sql2ind($jobstart,"date")."</td></tr>"
                   . "<tr><td>Gender :</td><td>".($gender=="f"?"Female":"Male")."</td></tr>"
                   . "<tr><td>Previous Job :</td><td></td></tr></tbody></table></td></tr></tbody></table>";
      $tooltips .= "\nnew Tip('emp_data', \"".addslashes($person_info)."\", {title:'".addslashes($employee_nm)."',width:350,style:'emp'});";
      
      $sql = "SELECT a.competency_nm,a.desc_en,a.desc_id,"
           . "b.compgroup_nm,a.competency_class,a.competency_abbr"
           . " FROM ".XOCP_PREFIX."competency a"
           . " LEFT JOIN ".XOCP_PREFIX."compgroup b USING(compgroup_id)"
           . " WHERE a.competency_id = '$competency_id'";
      $result = $db->query($sql);
      list($competency_nm,$desc_en,$desc_id,$compgroup_nm,$competency_class,$competency_abbr)=$db->fetchRow($result);
      
      list($acl,$ccl,$rcl,$itj,$gap) = $ajax->getCurrentAssessment($employee_id,$competency_id);
      global $proficiency_level_name;
      $current_level = $proficiency_level_name[floor($ccl)]." (".number_format($ccl,2,".","").")";
      $assessment_level = $proficiency_level_name[$acl]. " ($acl)";
      
      $ret = "<script type='text/javascript' src='".XOCP_SERVER_SUBDIR."/include/prototip2.0.5/js/prototip.js'></script>"
           . "<link rel='stylesheet' type='text/css' href='".XOCP_SERVER_SUBDIR."/include/prototip2.0.5/css/prototip.css' />";
      
           ///// title and employee information
      $ret .= "<div style='text-align:right;padding:2px;margin-top:-10px;margin-right:10px;'>&nbsp;"
           //. "[<a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&summarypage=y'>Table</a>]"
           . "</div>"
           . "<div style='padding:4px;font-weight:bold;'>ASSESSMENT FORM</div>"
           . "<div style='background-color:#eeeeee;margin-bottom:10px;border:1px solid #999999;padding:5px;'>"
           . "<table style='width:100%;' border='0' cellpadding='0' cellspacing='0'><tbody><tr>"
           . "<td style='cursor:default;'><span id='emp_data' style='cursor:pointer;font-weight:bold;color:black;'>$employee_nm [$nip]</span> as $job_nm [$job_cd]</td>"
           . "<td style='text-align:right;'>&nbsp;</td></tr>"
           . "<tr><td colspan='2'>"
           . "<table style='border:1px solid #777777;background-color:#ffffff;border-spacing:0px;text-align:center;margin:3px;'>"
           . "<colgroup><col width='200'/><col width='200'/><col width='200'/><col width='200'/></colgroup>"
           . "<tbody><tr>"
           . "<td style='padding:2px;border-right:1px solid #777777;'>CCL : <span id='emp_ccl'>$current_level</span></td>"
           . "<td style='padding:2px;border-right:1px solid #777777;'>RCL : ".$proficiency_level_name[$rcl]." ($rcl)</td>"
           . "<td style='padding:2px;border-right:1px solid #777777;'>ITJ : $itj</td>"
           . "<td style='padding:2px;'>GAP : <span id='empl_gap'>".number_format($gap,2,".","")."</span></td>"
           . "</tr></tbody></table>"
           . "</td>"
           . "</tr></tbody></table>"
           . "</div>"
           
           ///// current competency assessment
           . "<div style='margin-bottom:10px;'><table style=''>"
           . "<colgroup><col width='90'/><col/></colgroup><tbody>"
           . "<tr><td colspan='2' style='padding:10px;font-size:+1.5em;text-align:center;'>$competency_abbr $competency_nm / ".ucfirst($competency_class)."</td></tr>"
           . "<tr>"
           . "<td>Description:</td><td><span style=''>$desc_en</span><hr noshade='1' size=1' color='#bbbbbb'/><span style='font-style:italic;'>$desc_id</span></td>"
           . "</tr></tbody></table>"
           . "</div>"
           . "<div style='border:0px solid #999999;padding:5px;'>"
           
           . "<div style='padding:0px;border:1px solid #999999;'><form id='frm'>"
           
           . $ajax->getQuestions($employee_id,$competency_id,$acl)
           
           . "</form>"
           //. "<div style='text-align:right;margin:5px;'>"
           //. "&nbsp;<input type='button' value='Finish' onclick='save_form(this,event);'/>&nbsp;&nbsp;"
           //. "&nbsp;<input type='button' value='Previous' onclick='goprev(this,event);'/>"
           //. "&nbsp;<input type='button' value='Next' onclick='gonext(this,event);'/>"
           //. "</div>"
           . "</div>"
           
           . "</div><div style='padding:50px;'></div>";
      
      $ret .= $ajax->getJs()."<script type='text/javascript' language='javascript'><!--
      
      function ckrad_mouse_out(d,e) {
         if(dvckrad) {
            dvckrad.style.display = 'none';
         }
      }
      
      var dvckrad = null;
      function ckrad_mouse_move(title,d,e) {
         if(!dvckrad) {
            dvckrad = _dce('div');
            dvckrad.setAttribute('style','width:100px;opacity:0.9;text-align:center;position:absolute;padding:5px;font-size:0.9em;font-style:italic;border:1px solid #888;-moz-box-shadow:1px 1px 3px #000;-moz-border-radius:5px;background-color:#ffffff;');
            dvckrad.arrow = dvckrad.appendChild(_dce('img'));
            dvckrad.arrow.setAttribute('src','".XOCP_SERVER_SUBDIR."/images/topmiddle.png');
            dvckrad.arrow.setAttribute('style','position:absolute;margin-top:-17px;margin-left:35px;');
            dvckrad.dv = dvckrad.appendChild(_dce('div'));
            dvckrad = document.body.appendChild(dvckrad);
         }
         dvckrad.dv.innerHTML = title;
         dvckrad.style.display = '';
         dvckrad.style.top = parseInt(e.pageY+20)+'px';
         dvckrad.style.left = parseInt(e.pageX-parseInt(dvckrad.offsetWidth)+20)+'px';
      }
      
      function delete_notes(d,e) {
         _destroy(notes_editor);
         var dv = $('dvnote_'+notes_editor.acl+'_'+notes_editor.behaviour_id+'_'+notes_editor.ca_id);
         dv.innerHTML = '';
         dv.style.display = 'none';
         asjx_app_deleteNotes(notes_editor.note_id,notes_editor.competency_id,notes_editor.acl,notes_editor.behaviour_id,notes_editor.ca_id,function(_data) {
            var data = recjsarray(_data);
            var note_id = data[0];
            var acl = data[1];
            var behaviour_id = data[2];
            var ca_id = data[3];
            var competency_id = data[4];
            var d = $('spnote_'+acl+'_'+behaviour_id+'_'+ca_id);
            d.setAttribute('onclick','edit_notes(\"'+note_id+'\",\"'+competency_id+'\",\"'+acl+'\",\"'+behaviour_id+'\",\"'+ca_id+'\",this,event);');
         });
         notes_editor.competency_id = null;
         notes_editor.acl = null;
         notes_editor.behaviour_id = null;
         notes_editor.ca_id = null;
         return;
      }
      
      function save_notes(d,e) {
         var notes_txt = $('notes_txt').value;
         _destroy(notes_editor);
         var dv = $('dvnote_'+notes_editor.acl+'_'+notes_editor.behaviour_id+'_'+notes_editor.ca_id);
         dv.innerHTML = notes_txt;
         dv.style.display = '';
         asjx_app_saveNotes(notes_editor.note_id,notes_editor.competency_id,notes_editor.acl,notes_editor.behaviour_id,notes_editor.ca_id,urlencode(notes_txt),function(_data) {
            var data = recjsarray(_data);
            var note_id = data[0];
            var acl = data[1];
            var behaviour_id = data[2];
            var ca_id = data[3];
            var competency_id = data[4];
            var d = $('spnote_'+acl+'_'+behaviour_id+'_'+ca_id);
            d.setAttribute('onclick','edit_notes(\"'+note_id+'\",\"'+competency_id+'\",\"'+acl+'\",\"'+behaviour_id+'\",\"'+ca_id+'\",this,event);');
         });
         notes_editor.competency_id = null;
         notes_editor.acl = null;
         notes_editor.behaviour_id = null;
         notes_editor.ca_id = null;
         return;
      }
      
      function cancel_edit_notes(d,e) {
         _destroy(notes_editor);
         notes_editor.competency_id = null;
         notes_editor.acl = null;
         notes_editor.behaviour_id = null;
         notes_editor.ca_id = null;
         return;
      }
      
      var notes_editor = null;
      function edit_notes(note_id,competency_id,acl,behaviour_id,ca_id,d,e) {
         _destroy(dvhistory);
         if(dvhistory) dvhistory.competency_id = null;
         if(notes_editor) {
            if(notes_editor.acl==acl&&notes_editor.behaviour_id==behaviour_id&&notes_editor.ca_id==ca_id) {
               _destroy(notes_editor);
               notes_editor.competency_id = null;
               notes_editor.acl = null;
               notes_editor.behaviour_id = null;
               notes_editor.ca_id = null;
               return;
            }
            _destroy(notes_editor);
         }
         var dv = $('dvnote_'+acl+'_'+behaviour_id+'_'+ca_id);
         var d = $('spnote_'+acl+'_'+behaviour_id+'_'+ca_id);
         notes_editor = _dce('div');
         notes_editor.setAttribute('style','-moz-border-radius:5px;border:1px solid #bbb;background-color:#fff;display:none;position:absolute;width:410px;padding:3px;-moz-box-shadow:1px 1px 5px #000;');
         notes_editor.innerHTML = '<div style=\"background-color:#ccc;padding:2px;text-align:center;\">Edit Notes</div>'
                                + '<div style=\"padding:2px;text-align:center;\"><textarea spellcheck=\"false\" id=\"notes_txt\" style=\"font-family:Courier;width:400px;height:100px;\">'+dv.innerHTML+'</textarea></div>'
                                + '<div style=\"padding:2px;text-align:right;\">'
                                + '<input type=\"button\" value=\""._SAVE."\" onclick=\"save_notes(this,event);\"/>&nbsp;'
                                + '<input type=\"button\" value=\""._CANCEL."\" onclick=\"cancel_edit_notes(this,event);\"/>&nbsp;&nbsp;'
                                + '<input type=\"button\" value=\""._DELETE."\" onclick=\"delete_notes(this,event);\"/>'
                                + '</div>';
         notes_editor.note_id = note_id;
         notes_editor.competency_id = competency_id;
         notes_editor.acl = acl;
         notes_editor.behaviour_id = behaviour_id;
         notes_editor.ca_id = ca_id;
         notes_editor = d.parentNode.appendChild(notes_editor);
         notes_editor.style.top = (oY(d)-70+d.offsetHeight/2)+'px';
         notes_editor.style.left = (oX(d)-430)+'px';
         notes_editor.style.display='';
         setTimeout('$(\"notes_txt\").focus()',100);
      }
      
      var dvhistory = null;
      function view_history(competency_id,acl,behaviour_id,ca_id,d,e) {
         _destroy(notes_editor);
         if(notes_editor) notes_editor.acl = null;
         if(dvhistory) {
            if(dvhistory.competency_id=competency_id&&dvhistory.acl==acl&&dvhistory.behaviour_id==behaviour_id&&dvhistory.ca_id==ca_id) {
               _destroy(dvhistory);
               dvhistory.competency_id = null;
               dvhistory.acl = null;
               dvhistory.behaviour_id = null;
               dvhistory.ca_id = null;
               return;
            }
            _destroy(dvhistory);
         }
         dvhistory = _dce('div');
         dvhistory.setAttribute('style','border-radius:5px;box-shadow:1px 1px 5px #000;-moz-border-radius:5px;border:1px solid #bbb;background-color:#fff;display:none;position:absolute;width:410px;padding:3px;-moz-box-shadow:1px 1px 5px #000;');
         dvhistory.innerHTML = '';
         dvhistory.appendChild(progress_span());
         dvhistory.competency_id = competency_id;
         dvhistory.acl = acl;
         dvhistory.behaviour_id = behaviour_id;
         dvhistory.ca_id = ca_id;
         dvhistory = d.parentNode.appendChild(dvhistory);
         dvhistory.style.top = (oY(d)-70+d.offsetHeight/2)+'px';
         dvhistory.style.left = (oX(d)-430)+'px';
         dvhistory.style.display='';
         asjx_app_getACLHistory(competency_id,acl,behaviour_id,ca_id,function(_data) {
            dvhistory.innerHTML = _data;
         });
      }
      
      $tooltips
      
      var acl = '$acl';
      var ccl = '$ccl';
      var cbh = '$behaviour_id';
      var competency_id = '$competency_id';
      
      function undofinish() {
         _destroy(hdv.confirm);
         hdv.confirm = null;
         hdv.dv.style.display = '';
      }
      
      var hdv = null;
      function confirmnotfinish(d,e) {
         hdv = d.parentNode.parentNode;
         hdv.dv = d.parentNode;
         hdv.confirm = _dce('div');
         hdv.confirm.setAttribute('style','text-align:center;padding:10px;');
         hdv.confirm.innerHTML = '<div style=\"color:red;\"><br/>Are you sure you want to continue later?'
                               + '<br/><br/><br/><br/></div>'
                               + '<input type=\"button\" value=\"Yes (continue later)\" onclick=\"summarypage();\"/>&nbsp;' ///// tambahkan cek kalo lebih / kurang
                               + '<input type=\"button\" value=\"No\" onclick=\"undofinish();\"/>';
         hdv.dv.style.display = 'none';
         hdv.confirm = hdv.appendChild(hdv.confirm);
      }
      
      function confirmfinish(d,e) {
         hdv = d.parentNode.parentNode;
         hdv.dv = d.parentNode;
         hdv.confirm = _dce('div');
         hdv.confirm.setAttribute('style','text-align:center;padding:10px;');
         hdv.confirm.innerHTML = '<div style=\"color:red;\">You will finish the assessment even you may continue the assessment to the next level.<br/><br/>Are you sure?'
                               + '<br/><br/></div>'
                               + '<input type=\"button\" value=\"Yes (finish)\" onclick=\"summarypage();\"/>&nbsp;' ///// tambahkan cek kalo lebih / kurang
                               + '<input type=\"button\" value=\"No\" onclick=\"undofinish();\"/>';
         hdv.dv.style.display = 'none';
         hdv.confirm = hdv.appendChild(hdv.confirm);
      }
      
      function ckrad(no,cls,answer_t,d,e) {
         $('question_'+no).className = 'trasm_'+cls+'_'+answer_t;
      }
      
      var saveconfirm = null;
      var saveconfirm2 = null;
      function save_form(d,e) {
         var ret = parseForm('frm');
         asjx_app_saveAssessment(ret,function(_data) {
            var data = recjsarray(_data);
            $('emp_ccl').innerHTML = data[0];
            ccl = data[1];
            $('empl_gap').innerHTML = data[4];
            // saveconfirm2.innerHTML = data[3];
            saveconfirm2.innerHTML = '<div style=\"-moz-border-radius:3px 3px 0px 0px;background-color:#556699;color:#fff;text-align:center;font-weight:bold;padding:5px;\">Result</div><div style=\"margin-top:20px;\">'+data[3]+'</div>';
         });
         var dvback = _dce('div');
         dvback.setAttribute('style','height:150%;width:150%;position:fixed;top:0px;left:-10px;background-color:#333333;opacity:0.6;z-index:10000;');
         saveconfirm = document.body.appendChild(dvback);
         var dv = _dce('div');
         dv.setAttribute('style','-moz-border-radius:5px;-moz-box-shadow:1px 1px 3px #000;margin-left:-200px;margin-top:-110px;padding:4px;width:400px;height:220px;position:fixed;display:none;background-color:white;border:1px solid #555555;opacity:1;z-index:10001;');
         dv.appendChild(progress_span(' ... saving'));
         saveconfirm2 = document.body.appendChild(dv);
         saveconfirm2.style.left = '50%'; //parseInt(oX(d)-400+d.offsetWidth)+'px';
         saveconfirm2.style.top = '50%';
         saveconfirm2.style.display = '';
      }

      function goprev(d,e) {
         $('frm').innerHTML = '';
         $('frm').appendChild(progress_span(' ... previous level'));
         asjx_app_getPreviousQuestions(competency_id,function(_data) {
            var data = recjsarray(_data);
            $('frm').innerHTML = data[0];
            //$('emp_acl').innerHTML = data[1];
         });
      }
      
      function confirmgonext(d,e) {
         _destroy(saveconfirm);
         _destroy(saveconfirm2);
         gonext(d,e);
      }
      
      function new_next(d,e) {
         _destroy(saveconfirm);
         _destroy(saveconfirm2);
         gonext(d,e);
      }
      
      function new_prev(d,e) {
         _destroy(saveconfirm);
         _destroy(saveconfirm2);
         goprev(d,e);
      }
      
      function review(d,e) {
         _destroy(saveconfirm);
         _destroy(saveconfirm2);
      }
      
      function gonext(d,e) {
         $('frm').innerHTML = '';
         $('frm').appendChild(progress_span(' ... next level'));
         asjx_app_getNextQuestions(competency_id,function(_data) {
            var data = recjsarray(_data);
            $('frm').innerHTML = data[0];
            //$('emp_acl').innerHTML = data[1];
         });
      }
      
      function summarypage() {
         ".($_SESSION["assessment_idp"]==1?"location = '".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&assessment_idp=y&asid=$asid&employee_id=$employee_id';":"location = '".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&summarypage=y';")."
      }
      
      // --></script>";
      return $ret;
   }
   
   function diagram() {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/assessment.php");
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $db=&Database::getInstance();
      
      $user_id = getUserID();
      $tooltips = "";
      
      $asid = $_SESSION["hris_assessment_asid"];
      
      $sql = "SELECT c.job_id,b.employee_id,d.job_nm,p.person_nm,b.employee_ext_id,p.adm_gender_cd,c.start_dttm,"
           . "b.entrance_dttm,(TO_DAYS(now())-TO_DAYS(b.entrance_dttm)) as jobage,d.summary,b.person_id"
           . " FROM ".XOCP_PREFIX."users a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(person_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job c USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."jobs d USING(job_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons p ON p.person_id = b.person_id"
           . " WHERE a.user_id = '$user_id'";
      $result = $db->query($sql);
      $employee_list = "";
      if($db->getRowsNum($result)>0) {
         list($assessor_job_id,$self_employee_id,$self_job_nm,$self_nm,$self_nip,$self_gender,$self_jobstart,$self_entrance_dttm,$self_jobage,$self_job_summary,$self_person_id)=$db->fetchRow($result);
         $self_job_summary = str_replace("\n","",str_replace("\r","",$sel_job_summary));
         $person_info = "<table style='width:100%;'><tbody><tr><td style='vertical-align:top;'><img src='".XOCP_SERVER_SUBDIR."/modules/hris/thumb.php?pid=$self_person_id' height='100'/></td>"
                      . "<td style='vertical-align:top;'><table class='emp_info' style='margin-top:0px;width:290px;'>"
                      . "<colgroup><col width='80'/><col/></colgroup>"
                      . "<tbody>"
                      . "<tr><td>Employee ID</td><td>: $self_nip</td></tr>"
                      . "<tr><td>Entrance Date</td><td>: ".sql2ind($self_entrance_dttm,"date")." ("._bctrim(toMoney($self_jobage/365.25))." year)</td></tr>"
                      . "<tr><td>Job Assigned</td><td>: ".sql2ind($self_jobstart,"date")."</td></tr>"
                      . "<tr><td>Gender</td><td>: ".($self_gender=="f"?"Female":"Male")."</td></tr>"
                      . "<tr><td>Previous Job</td><td>:</td></tr></tbody></table></td></tr></tbody></table>";
         $tooltips .= "\nnew Tip('you_nm', \"".addslashes($person_info)."\", {title:'".addslashes($self_nm)."',width:350,style:'emp'});";
         $tooltips .= "\nnew Tip('you_job',\"<div style='font-weight:bold;'>Job Summary:</div>".addslashes($self_job_summary)."\", {title:'".addslashes($self_job_nm)."',style:'emp'});";
         $_SESSION["self_employee_id"] = $self_employee_id;
      } else {
         return "You don't have job assigned to you. Please contact HR Administrator.";
      }
      
      /// 360
      $sql = "SELECT a.employee_id,b.job_id,c.job_nm,c.job_cd,c.job_abbr,c.org_id,c.description,c.summary,d.job_level,a.assessor_t"
           . " FROM ".XOCP_PREFIX."assessor_360 a"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."jobs c USING(job_id)"
           . " LEFT JOIN ".XOCP_PREFIX."job_class d USING(job_class_id)"
           . " WHERE a.assessor_id = '$self_employee_id'"
           . " AND a.asid = '$asid'"
           . " AND a.status_cd = 'active'"
           . " AND b.job_id IS NOT NULL"
           . " ORDER BY c.job_class_id";
      $result = $db->query($sql);
      $superior = $peer = $customer = "";
      $peer_count = $customer_count = $superior_count = 0;
      if($db->getRowsNum($result)>0) {
         while($rrow=$db->fetchRow($result)) {
            list($employee_idx,$job_id,$job_nm,$job_cd,$job_abbr,$org_id,$job_descx,$job_summary,$job_level,$assessor_t)=$rrow;
            $sql = "SELECT a.employee_id,b.employee_ext_id,c.person_nm,a.gradeval,c.birth_dttm,c.birthplace,"
                 . "c.adm_gender_cd,c.addr_txt,c.cell_phone,c.home_phone,c.marital_st,"
                 . "b.entrance_dttm,a.start_dttm,a.stop_dttm,(TO_DAYS(now())-TO_DAYS(b.entrance_dttm)) as jobage,"
                 . "c.person_id,d.job_nm,d.summary"
                 . " FROM ".XOCP_PREFIX."employee_job a"
                 . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
                 . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
                 . " LEFT JOIN ".XOCP_PREFIX."jobs d ON d.job_id = a.job_id"
                 . " LEFT JOIN ".XOCP_PREFIX."job_class e ON e.job_class_id = d.job_class_id"
                 . " WHERE a.employee_id = '$employee_idx'"
                 . " ORDER BY e.job_class_level";
            $res2 = $db->query($sql);
            if($db->getRowsNum($res2)>0) {
               while(list($employee_id,$nip,$employee_nm,$gradeval,$dob,$pob,$gender,$addr,$cellphone,$phone,$marital,
                          $entrance_dttm,$jobstart,$jobstop,$jobage,$person_id,$job_nmx,$job_summary)=$db->fetchRow($res2)) {
                  
                  $job_summary = str_replace("\n","",str_replace("\r","",$job_summary));
                  
                  $person_info = "<table style='width:100%;'><tbody><tr><td style='vertical-align:top;'><img src='".XOCP_SERVER_SUBDIR."/modules/hris/thumb.php?pid=$person_id' height='100'/></td>"
                               . "<td style='vertical-align:top;'><table class='emp_info' style='margin-top:0px;width:290px;'>"
                               . "<colgroup><col width='80'/><col/></colgroup>"
                               . "<tbody>"
                               . "<tr><td>Employee ID</td><td>: $nip</td></tr>"
                               . "<tr><td>Entrance Date</td><td>: ".sql2ind($entrance_dttm,"date")." ("._bctrim(toMoney($jobage/365.25))." year)</td></tr>"
                               . "<tr><td>Job Assigned</td><td>: ".sql2ind($jobstart,"date")."</td></tr>"
                               . "<tr><td>Gender</td><td>: ".($gender=="f"?"Female":"Male")."</td></tr>"
                               . "<tr><td>Previous Job</td><td>:</td></tr></tbody></table></td></tr></tbody></table>";
                  if($assessor_t=="subordinat") {
                     $superior .= "<tr><td id='superior_emp_${employee_id}_${job_id}'>"
                                . "<div style='overflow:hidden;width:120px;margin:0px;padding:0px;'><div style='margin:0px;width:900px;'>$employee_nm</div></div></td>"
                                . "<td id='superior_job_${employee_id}_${job_id}'><div style='overflow:hidden;width:120px;margin:0px;padding:0px;'><div style='margin:0px;width:900px;'>$job_nm</div></div></td></tr>";
                     $tooltips .= "\nnew Tip('superior_emp_${employee_id}_${job_id}', \"".addslashes($person_info)."\", {title:'".addslashes($employee_nm)."',width:350,style:'emp'});";
                     $tooltips .= "\nnew Tip('superior_job_${employee_id}_${job_id}','<div style=\"font-weight:bold;\">Job Summary:</div>".addslashes($job_summary)."', {title:'".addslashes($job_nmx)."',style:'emp'});";
                     $superior_count++;
                  }
                  if($assessor_t=="peer") {
                     $peer .= "<tr><td id='peer_emp_${employee_id}_${job_id}'>"
                            . "<div style='overflow:hidden;width:120px;margin:0px;padding:0px;'><div style='margin:0px;width:900px;'>$employee_nm</div></div></td>"
                            . "<td id='peer_job_${employee_id}_${job_id}'><div style='overflow:hidden;width:120px;margin:0px;padding:0px;'><div style='margin:0px;width:900px;'>$job_nm</div></div></td></tr>";
                     $tooltips .= "\nnew Tip('peer_emp_${employee_id}_${job_id}', \"".addslashes($person_info)."\", {title:'".addslashes($employee_nm)."',width:350,style:'emp'});";
                     $tooltips .= "\nnew Tip('peer_job_${employee_id}_${job_id}','<div style=\"font-weight:bold;\">Job Summary:</div>".str_replace("\n","",addslashes($job_summary))."', {title:'".addslashes($job_nmx)."',style:'emp'});";
                     $peer_count++;
                  }
                  if($assessor_t=="customer") {
                     $customer .= "<tr><td id='customer_emp_${employee_id}_${job_id}'>"
                                . "<div style='overflow:hidden;width:120px;margin:0px;padding:0px;'><div style='margin:0px;width:900px;'>$employee_nm</div></div></td>"
                                . "<td id='customer_job_${employee_id}_${job_id}'><div style='overflow:hidden;width:120px;margin:0px;padding:0px;'><div style='margin:0px;width:900px;'>$job_nm</div></div></td></tr>";
                     $tooltips .= "\nnew Tip('customer_emp_${employee_id}_${job_id}', \"".addslashes($person_info)."\", {title:'".addslashes($employee_nm)."',width:350,style:'emp'});";
                     $tooltips .= "\nnew Tip('customer_job_${employee_id}_${job_id}','<div style=\"font-weight:bold;\">Job Summary:</div>".addslashes($job_summary)."', {title:'".addslashes($job_nmx)."',style:'emp'});";
                     $customer_count++;
                  }
               }
            }
         }
      }
      
      /// subordinat
      /*
      $sql = "SELECT a.job_id,a.job_nm,a.job_cd,a.job_abbr,a.org_id,a.description,a.summary,b.job_level"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
           . " WHERE a.assessor_job_id = '$assessor_job_id'"
           . " ORDER BY a.job_class_id";
      $result = $db->query($sql);
      */
      
      $sql = "SELECT a.job_id,a.job_nm,a.job_cd,a.job_abbr,a.org_id,a.description,a.summary,b.job_level,"
           
           . "ee.employee_id,ee.employee_ext_id,pp.person_nm,jj.gradeval,pp.birth_dttm,pp.birthplace,"
                 . "pp.adm_gender_cd,pp.addr_txt,pp.cell_phone,pp.home_phone,pp.marital_st,"
                 . "ee.entrance_dttm,jj.start_dttm,jj.stop_dttm,(TO_DAYS(now())-TO_DAYS(ee.entrance_dttm)) as jobage,"
                 . "pp.person_id"
           
           . " FROM ".XOCP_PREFIX."assessor_360 aa"
           . " LEFT JOIN ".XOCP_PREFIX."employee ee ON ee.employee_id = aa.employee_id"
           . " LEFT JOIN ".XOCP_PREFIX."persons pp ON pp.person_id = ee.person_id"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job jj ON jj.employee_id = aa.employee_id"
           . " LEFT JOIN ".XOCP_PREFIX."jobs a ON a.job_id = jj.job_id"
           . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
           . " WHERE aa.asid = '$asid' AND aa.assessor_id = '$self_employee_id'"
           . " AND aa.assessor_t = 'superior'"
           . " AND aa.status_cd = 'active'"
           . " ORDER BY a.job_class_id";
      $result = $db->query($sql);
      _debuglog($sql);
      if($db->getRowsNum($result)>0) {
         while($rrow=$db->fetchRow($result)) {
            list($job_id,$job_nm,$job_cd,$job_abbr,$org_id,$job_descx,$job_summary,$job_level,
                  $employee_id,$nip,$employee_nm,$gradeval,$dob,$pob,$gender,$addr,$cellphone,$phone,$marital,
                  $entrance_dttm,$jobstart,$jobstop,$jobage,$person_id)=$rrow;
                  $person_info = "<table style='width:100%;'><tbody><tr><td style='vertical-align:top;'><img src='".XOCP_SERVER_SUBDIR."/modules/hris/thumb.php?pid=$person_id' height='100'/></td>"
                               . "<td style='vertical-align:top;'><table class='emp_info' style='margin-top:0px;width:290px;'>"
                               . "<colgroup><col width='80'/><col/></colgroup>"
                               . "<tbody>"
                               . "<tr><td>Employee ID</td><td>: $nip</td></tr>"
                               . "<tr><td>Entrance Date</td><td>: ".sql2ind($entrance_dttm,"date")." ("._bctrim(toMoney($jobage/365.25))." year)</td></tr>"
                               . "<tr><td>Job Assigned</td><td>: ".sql2ind($jobstart,"date")."</td></tr>"
                               . "<tr><td>Gender</td><td>: ".($gender=="f"?"Female":"Male")."</td></tr>"
                               . "<tr><td>Previous Job</td><td>:</td></tr></tbody></table></td></tr></tbody></table>";
                  $tooltips .= "\nnew Tip('sub_emp_${employee_id}_${job_id}', \"".addslashes($person_info)."\", {title:'".addslashes($employee_nm)."',width:350,style:'emp'});";
                  $tooltips .= "\nnew Tip('sub_job_${employee_id}_${job_id}','<div style=\"font-weight:bold;\">Job Summary:</div>".addslashes(str_replace("\n","",($job_summary)))."', {title:'$job_nm',style:'emp'});";
                  $subordinat .= "<tr><td id='sub_emp_${employee_id}_${job_id}'>"
                               . "<div style='overflow:hidden;width:120px;margin:0px;padding:0px;'><div style='margin:0px;width:900px;'>$employee_nm</div></div></td>"
                               . "<td id='sub_job_${employee_id}_${job_id}'><div style='overflow:hidden;width:120px;margin:0px;padding:0px;'><div style='margin:0px;width:900px;'>$job_nm</div></div></td></tr>";
         }
      }
      
      if($superior=="") {
         $superior = "<tr><td colspan='2' style='text-align:center;'>-</td></tr>";
      }
      
      if($peer=="") {
         $peer = "<tr><td colspan='2' style='text-align:center;'>-</td></tr>";
      }
      
      if($customer=="") {
         $customer = "<tr><td colspan='2' style='text-align:center;'>-</td></tr>";
      }
      
      if($subordinat=="") {
         $subordinat = "<tr><td colspan='2' style='text-align:center;'>-</td></tr>";
      }
      
      $allow_superior = 0;
      $allow_subordinate = 0;
      $allow_peer = 0;
      $allow_customer = 0;
      //// trap schedule //////////////////////////////////////////////////////////////////////////////////////////////////
      $sql = "SELECT schedule_id,org_id,start_dttm,stop_dttm,assessment_superior,assessment_subordinate,assessment_peer,assessment_customer,"
           . "IF(start_dttm<=now(),1,0),IF(stop_dttm>=now(),1,0)"
           . " FROM ".XOCP_PREFIX."assessment_schedule"
           . " WHERE asid = '$asid'"
           . " AND status_cd = 'normal'"
           . " AND stop_dttm >= now() ORDER BY start_dttm";
      $rs = $db->query($sql);
      if($db->getRowsNum($rs)>0) {
         while(list($schedule_id,$org_id,$start_dttm,$stop_dttm,$assessment_superior,$assessment_subordinate,$assessment_peer,$assessment_customer,
            $start_in,$stop_in)=$db->fetchRow($rs)) {
            if($start_in==1&&$stop_in==1) {
               if($assessment_superior==1) $allow_superior++;
               if($assessment_subordinate==1) $allow_subordinate++;
               if($assessment_peer==1) $allow_peer++;
               if($assessment_customer==1) $allow_customer++;
            }
         }
      }
      /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
      $allow_superior = $allow_subordinate = $allow_peer = $allow_customer = 1;
      
      $xh = 20;
      
      $superior_height = ((($superior_count<=0?1:$superior_count)-1)*($xh+3))+10;
      $peer_top = max(150,floor(266-(($peer_count<=0?1:$peer_count)/2*$xh)))+$superior_height;
      $customer_top = max(150,floor(266-(($customer_count<=0?1:$customer_count)/2*$xh)))+$superior_height;
      $anchor_x = -220;
      $anchor_y = 190 + $superior_height;
      $image_x = $anchor_x;
      $image_y = $anchor_y;
      $you_x = $anchor_x+86;
      $you_y = $anchor_y+68;
      $superior_x = $anchor_x+14;
      $superior_y = $anchor_y-45-$superior_height;
      $subordinat_x = $anchor_x+14;
      $subordinat_y = $anchor_y+195;
      $peer_x = $anchor_x+436;
      $peer_y = $peer_top;
      $customer_x = $anchor_x-264;
      $customer_y = $customer_top;
      
      $ret = "<div style='text-align:right;margin-right:10px;padding:2px;margin-top:-10px;'>"
           . "[<a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&schedule_page=y'>Schedule</a>]&nbsp;"
           //. "[<a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&summarypage=y'>Table</a>]"
           . "</div>";
      $ret .= "<div style='font-weight:bold;'>Assessment Relation</div>"
           . "<div style='padding:10px;border:0px solid #bbb;margin-bottom:50px;'>"
           . "<div style='padding:10px;border:0px solid #bbb;min-height:500px;'>"
           
           /// image
           . "<div style='position:absolute;width:auto;height:auto;margin-left:${image_x}px;left:50%;top:${image_y}px;;border:0px solid black;padding:0px;'>"
           . "<img src='".XOCP_SERVER_SUBDIR."/modules/hris/images/arrow.png'/>"
           . "</div>"
           /// you
           . "<div style='text-align:center;position:absolute;width:auto;margin-left:${you_x}px;left:50%;top:${you_y}px;border:0px solid black;padding:2px;background-color:#5666b3;'>"
           . "<table align='center' style='width:250px !important;border:0px solid #888;' class='diagram'>"
           . "<colgroup><col width='50%'/><col/></colgroup>"
           . "<thead><tr><td colspan='2' style='color:#ffffff;background-color:#3b4367;'>You as assessor</td></tr></thead><tbody>"
           . "<tr><td id='you_nm' style='background-color:#d2d2f1;border-right:1px solid #888888;'><div style='overflow:hidden;width:120px;margin:0px;padding:0px;'><div style='margin:0px;width:900px;'>$self_nm</div></div></td>"
           . "<td id='you_job' style='background-color:#d2d2f1;'><div style='overflow:hidden;width:120px;margin:0px;padding:0px;'><div style='margin:0px;width:900px;'>$self_job_nm</div></div></td></tr>"
           . "</tbody></table>"
           . "</div>"
           /// superior
           . "<div style='text-align:center;position:absolute;width:400px;margin-left:${superior_x}px;left:50%;top:${superior_y}px;;border:0px solid black;padding:4px;background-color:#fff;'>"
           . "<table align='center' style='width:250px !important;' class='diagram'>"
           . "<colgroup><col width='50%'/><col/></colgroup>"
           . "<thead><tr><td colspan='2'>"
           
           /// trap here
           . ($allow_superior>0?"<a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&asmgroup=superior' title='click to start assessment'>Superior</a>":"Superior")
           
           . "</td></tr></thead><tbody>$superior</tbody></table>"
           . "</div>"
           /// subordinat
           . "<div style='text-align:center;position:absolute;width:400px;margin-bottom:100px;;margin-left:${subordinat_x}px;left:50%;top:${subordinat_y}px;;border:0px solid black;padding:4px;background-color:#fff;z-index:2;'>"
           . "<table align='center' style='width:250px !important;' class='diagram'>"
           . "<colgroup><col width='50%'/><col/></colgroup>"
           . "<thead><tr><td colspan='2'>"
           
           //////// trap here
           . ($allow_subordinate>0?"<a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&asmgroup=subordinate' title='click to start assessment'>Subordinate</a>":"Subordinate")
           
           . "</td></tr></thead><tbody>$subordinat</tbody><tfoot><tr><td colspan='2'><div style='height:40px;'></div></td></tr></tfoot></table>"
           . "</div>"
           /// peer
           . "<div style='text-align:center;position:absolute;width:260px;margin-bottom:100px;;margin-left:${peer_x}px;left:50%;top:${peer_y}px;border:0px solid black;padding:2px;background-color:#fff;z-index:2;'>"
           . "<table align='left' style='width:200px !important;' class='diagram'>"
           . "<colgroup><col width='50%'/><col/></colgroup>"
           . "<thead><tr><td colspan='2'>"
           
           /// trap here
           . ($allow_peer>0?"<a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&asmgroup=peer' title='click to start assessment'>Peer</a>":"Peer")
           
           . "</td></tr></thead><tbody>$peer</tbody><tfoot><tr><td colspan='2'><div style='height:40px;'></div></td></tr></tfoot></table>"
           . "</div>"
           /// customer
           . "<div style='text-align:center;position:absolute;width:260px;margin-bottom:100px;;margin-left:${customer_x}px;left:50%;top:${customer_y}px;;border:0px solid black;padding:2px;background-color:#fff;z-index:2;'>"
           . "<table align='right' style='width:250px !important;' class='diagram'>"
           . "<colgroup><col width='50%'/><col/></colgroup>"
           . "<thead><tr><td colspan='2' style='text-align:center;'>"
           
           /// trap here
           . ($allow_customer>0?"<a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&asmgroup=customer' title='click to start assessment'>Customer</a>":"Customer")
           
           . "</td></tr></thead><tbody>$customer</tbody><tfoot><tr><td colspan='2'><div style='height:40px;'></div></td></tr></tfoot></table>"
           . "</div>"
           
           . "</div>"
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
      
      //$limit_date = "2011-08-24 00:00:00";
      
      switch ($this->catch) {
         case $this->blockID:
            if(isset($_GET["asm"])&&$_GET["asm"]=="y") {
               require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
               $_SESSION["assessment_page"] = "form";
               $_SESSION["assessment_employee_id"] = $_GET["eid"];
               $_SESSION["assessment_competency_id"] = $_GET["cid"];
               
               list($ass_job_id,
                    $ass_employee_id,
                    $ass_job_nm,
                    $ass_nm,
                    $ass_nip,
                    $ass_gender,
                    $ass_jobstart,
                    $ass_entrance_dttm,
                    $ass_jobage,
                    $ass_job_summary,
                    $ass_person_id,
                    $ass_user_id,
                    $first_assessor_job_id,
                    $next_assessor_job_id,
                    $first_assessor_employee_id,
                    $next_assessor_employee_id) = _hris_getinfobyemployeeid($_SESSION["assessment_employee_id"]);
               $sql = "SELECT b.job_level FROM ".XOCP_PREFIX."jobs a LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
                    . " WHERE a.job_id = '$ass_job_id'";
               $result = $db->query($sql);
               list($job_level)=$db->fetchRow($result);
               if($limit_date!=""&&$job_level=="nonmanagement") {
                  $sql = "SELECT UNIX_TIMESTAMP()-UNIX_TIMESTAMP('$limit_date')";
                  $result = $db->query($sql);
                  list($is_over)=$db->fetchRow($result);
                  if($is_over>0) {
                     $_SESSION["assessment_page"] = "summary";
                     $ret = "<div style='padding:30px;border:1px solid #bbb;background-color:#ddddff;font-size:1.1em;-moz-box-shadow:1px 1px 3px #000;-moz-border-radius:5px;width:600px;text-align:center;'>"
                          //. "<div>Periode assessment telah ditutup pada ".sql2ind($limit_date)." untuk level non management.</div>"
                          . "<div style=''>Assessment Periode is already closed at ".sql2ind($limit_date)." for non management level.</div>"
                          . "<br/><br/><a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&summarypage=y'>Please click here to go back.</a></div>";
                  } else {
                     $ret = $this->form($_SESSION["assessment_employee_id"],$_SESSION["assessment_competency_id"]);
                  }
               } else {
                  $ret = $this->form($_SESSION["assessment_employee_id"],$_SESSION["assessment_competency_id"]);
               }
            } elseif(isset($_GET["summarypage"])&&$_GET["summarypage"]=="y") {
               $_SESSION["assessment_page"] = "summary";
               $ret = $this->assessment();
            } elseif(isset($_GET["diagram"])&&$_GET["diagram"]=="y") {
               $_SESSION["assessment_idp"] = 0;
               $_SESSION["assessment_page"] = "diagram";
               $_SESSION["hris_assessment_asid"] = ($_GET["asid"]+0);
               $ret = $this->diagram();
            } elseif(isset($_GET["schedule_page"])&&$_GET["schedule_page"]=="y") {
               $_SESSION["assessment_page"] = "schedule";
               $ret = $this->assessment_schedule();
            } elseif(isset($_GET["assessment_idp"])&&$_GET["assessment_idp"]=="y") {
               $_SESSION["assessment_idp"] = 1;
               $_SESSION["assessment_page"] = "assessment_idp";
               $_SESSION["hris_assessment_asid"] = ($_GET["asid"]+0);
               $_SESSION["assessor360"] = 0;
               $_SESSION["assessment_idp_employee_id"] = $_GET["employee_id"];
               $ret = $this->assessment();
            
            } elseif(isset($_GET["asmgroup"])) {
               switch($_GET["asmgroup"]) {
                  case "customer":
                     $_SESSION["hris_asmgroup"] = "customer";
                     $_SESSION["assessor360"] = 1;
                     break;
                  case "superior":
                     $_SESSION["hris_asmgroup"] = "superior";
                     $_SESSION["assessor360"] = 1;
                     break;
                  case "peer":
                     $_SESSION["hris_asmgroup"] = "peer";
                     $_SESSION["assessor360"] = 1;
                     break;
                  case "subordinate":
                  default:
                     $_SESSION["hris_asmgroup"] = "subordinate";
                     $_SESSION["assessor360"] = 0;
                     break;
               }
               $_SESSION["assessment_page"] = "summary";
               $ret = $this->assessment();
            } else {
               if(isset($_SESSION["assessment_page"])&&$_SESSION["assessment_page"]=="form") {
                  $ret = $this->form($_SESSION["assessment_employee_id"],$_SESSION["assessment_competency_id"]);
               } else if(isset($_SESSION["assessment_page"])&&$_SESSION["assessment_page"]=="diagram") {
                  $ret = $this->diagram();
               } else if(isset($_SESSION["assessment_page"])&&$_SESSION["assessment_page"]=="summary") {
                  $ret = $this->assessment();
               } else if(isset($_SESSION["assessment_page"])&&$_SESSION["assessment_page"]=="schedule") {
                  $ret = $this->assessment_schedule();
               } else if(isset($_SESSION["assessment_page"])&&$_SESSION["assessment_page"]=="assessment_idp") {
                  $ret = $this->assessment();
               } else {
                  $ret = $this->assessment_schedule();
               }
            }
            break;
         default:
            if(isset($_SESSION["assessment_page"])&&$_SESSION["assessment_page"]=="form") {
               $ret = $this->form($_SESSION["assessment_employee_id"],$_SESSION["assessment_competency_id"]);
            } else if(isset($_SESSION["assessment_page"])&&$_SESSION["assessment_page"]=="diagram") {
               $ret = $this->diagram();
            } else if(isset($_SESSION["assessment_page"])&&$_SESSION["assessment_page"]=="summary") {
               $ret = $this->assessment();
            } else if(isset($_SESSION["assessment_page"])&&$_SESSION["assessment_page"]=="schedule") {
               $ret = $this->assessment_schedule();
            } else if(isset($_SESSION["assessment_page"])&&$_SESSION["assessment_page"]=="assessment_idp") {
               $ret = $this->assessment();
            } else {
               $ret = $this->assessment_schedule();
            }
            break;
      }
      return $ret;
   }
}

} // HRIS_ASSESSMENT_DEFINED
