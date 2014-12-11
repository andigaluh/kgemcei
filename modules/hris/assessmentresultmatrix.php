<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/assessmentresultmatrix.php                 //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-07-18                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_ASSESSMENTRESULTMATRIX_DEFINED') ) {
   define('HRIS_ASSESSMENTRESULTMATRIX_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_AssessmentResultMatrix extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_ASSESSMENTRESULTMATRIX_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_ASSESSMENTRESULTMATRIX_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_AssessmentResultMatrix($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   function getSectionDown($parent_id) {
      $db=&Database::getInstance();
      $sql = "SELECT a.org_id,a.org_nm,a.org_abbr,b.org_class_nm,a.org_class_id"
           . " FROM ".XOCP_PREFIX."orgs a"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . " WHERE a.parent_id = '$parent_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($org_id,$org_nm,$org_abbr,$org_class_nm,$org_class_id)=$db->fetchRow($result)) {
            if($org_class_id==4) {
               $_SESSION["hris_section_allow"][$org_id] = array($org_id,$org_nm,$org_abbr,$org_class_nm);
            }
            if($org_class_id>4) {
               return;
            }
            $this->getSectionDown($org_id);
         }
      }
   }
   
   
   function getSectionUp($org_id) {
      $db=&Database::getInstance();
      $sql = "SELECT a.org_id,a.org_nm,a.org_abbr,b.org_class_nm,a.org_class_id,a.parent_id"
           . " FROM ".XOCP_PREFIX."orgs a"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . " WHERE a.org_id = '$org_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($org_id,$org_nm,$org_abbr,$org_class_nm,$org_class_id,$parent_id)=$db->fetchRow($result)) {
            if($org_class_id>4) {
               return $this->getSectionUp($parent_id);
            } else {
               return array($org_id,$org_nm,$org_abbr,$org_class_nm,$org_class_id,$parent_id);
            }
         }
      }
      return FALSE;
   }
   
   function getDivisionDown($parent_id) {
      $db=&Database::getInstance();
      $sql = "SELECT a.org_id,a.org_nm,a.org_abbr,b.org_class_nm,a.org_class_id"
           . " FROM ".XOCP_PREFIX."orgs a"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . " WHERE a.parent_id = '$parent_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($org_id,$org_nm,$org_abbr,$org_class_nm,$org_class_id)=$db->fetchRow($result)) {
            if($org_class_id==3) {
               $_SESSION["hris_division_allow"][$org_id] = array($org_id,$org_nm,$org_abbr,$org_class_nm);
            }
            if($org_class_id>3) {
               return;
            }
            $this->getDivisionDown($org_id);
         }
      }
   }
   
   
   function getDivisionUp($org_id) {
      $db=&Database::getInstance();
      $sql = "SELECT a.org_id,a.org_nm,a.org_abbr,b.org_class_nm,a.org_class_id,a.parent_id"
           . " FROM ".XOCP_PREFIX."orgs a"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . " WHERE a.org_id = '$org_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($org_id,$org_nm,$org_abbr,$org_class_nm,$org_class_id,$parent_id)=$db->fetchRow($result)) {
            if($org_class_id>3) {
               return $this->getDivisionUp($parent_id);
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
            if($org_class_id>=3) {
               $_SESSION["hris_subdiv"][$org_class_id][$org_id] = array($org_id,$org_nm,$org_abbr,$org_class_nm);
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
   
   function arm() {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_assessmentresultmatrix.php");
      $ajax = new _hris_class_AssessmentResultMatrixAjax("arm");
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_assessmentresult.php");
      $ajax2 = new _hris_class_AssessmentResultModifierAjax("amr");
      
      $_SESSION["hris_division_allow"] = array();
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
      } else if($_SESSION["arm_levelmatrix"]==4) {
         $sql = "SELECT a.org_id,b.org_class_id FROM ".XOCP_PREFIX."jobs a LEFT JOIN ".XOCP_PREFIX."orgs b USING(org_id) WHERE a.job_id = '$self_job_id'";
         $result = $db->query($sql);
         list($org_id,$org_class_id)=$db->fetchRow($result);
         if($org_class_id>=4) {
            list($section_org_id,$section_org_nm,$section_org_abbr,$section_org_class_nm,$section_org_class_id,$parent_id) = $this->getSectionUp($org_id);
            $_SESSION["hris_section_allow"][$section_org_id] = 1;
            list($division_org_id,$division_org_nm,$division_org_abbr,$division_org_class_nm,$division_org_class_id,$parent_id) = $this->getDivisionUp($org_id);
            $_SESSION["hris_division_allow"][$division_org_id] = 1;
         } else {
            $this->getSectionDown($org_id);
         }
         $_SESSION["hris_posmatrix_division"] = $division_org_id;
         $_SESSION["hris_posmatrix_section"] = $section_org_id;
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
      
      /// SESSION SELECT
      $sql = "SELECT asid,session_nm,session_periode FROM ".XOCP_PREFIX."assessment_session"
           . " WHERE status_cd = 'normal' AND asid >= '10'"
           . " ORDER BY assessment_start DESC";
      $result = $db->query($sql);
      $optsession = "";
      if($db->getRowsNum($result)>0) {
         while(list($asid,$session_nm,$session_periode)=$db->fetchRow($result)) {
            if($_SESSION["hris_arm_asid"]==0) {
               $_SESSION["hris_arm_asid"] = $asid;
            }
            $optsession .= "<option value='$asid' ".($asid_id==$_SESSION["hris_arm_asid"]?"selected='1'":"").">$session_periode $session_nm</option>";
         }
      }
      
      
      if($_SESSION["arm_levelmatrix"]==4) {
         $section_id = $_SESSION["hris_posmatrix_section"];
         list($division_org_id,$division_org_nm,$division_org_abbr,$division_org_class_nm,$division_org_class_id,$parent_id) = $this->getDivisionUp($section_id);
         $_SESSION["hris_posmatrix_division"] = $division_org_id;
         $optdiv = "<option value='$division_org_id' selected='1'>$division_org_nm</option>";
      } else {
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
      }
      
      $division_id = $_SESSION["hris_posmatrix_division"];
      
      /// SUBDIVISION SELECT
      
      if($_SESSION["arm_levelmatrix"]==3) {
         $optsubdiv = "<option value='0'>All</option>"; /// modification per 2012-03-05
      } else {
         $optsubdiv = ""; // "<option value='0'>All</option>"; /// modification per 2012-03-05
      }
      
      if($_SESSION["arm_levelmatrix"]==4) {
         $_SESSION["hris_subdiv"] = array();
         $section_id = $_SESSION["hris_posmatrix_section"];
         $sql = "SELECT a.org_id,a.org_nm,a.org_abbr,b.org_class_nm,a.org_class_id"
              . " FROM ".XOCP_PREFIX."orgs a"
              . " LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
              . " WHERE a.org_id = '$section_id'";
         $rs = $db->query($sql);
         list($section_org_idx,$section_org_nmx,$section_org_abbrx,$section_org_class_nmx,$section_org_class_idx)=$db->fetchRow($rs);
         $_SESSION["hris_subdiv"][4][$section_id] = array($section_org_idx,$section_org_nmx,$section_org_abbrx,$section_org_class_nmx);
         $ajax->recurseSection($section_id);
         ksort($_SESSION["hris_subdiv"]);
         _dumpvar($_SESSION["hris_subdiv"]);
         foreach($_SESSION["hris_subdiv"] as $org_class_idx=>$orgs) {
            foreach($orgs as $org_idx=>$v) {
               list($org_id,$org_nm,$org_abbr,$org_class_nm)=$v;
               $optsubdiv .= "<option value='$org_id' ".($org_id==$_SESSION["hris_posmatrix_subdivision"]?"selected='1'":"").">$org_nm $org_class_nm</option>";
               $_SESSION["hris_section_allow"][$org_id] = 1;
            }
         }
      
      } else {
      
         $_SESSION["hris_section_allow"] = array();
      
         $_SESSION["hris_subdiv"] = array();
         $ajax->recurseDivision($division_id);
         ksort($_SESSION["hris_subdiv"]);
         foreach($_SESSION["hris_subdiv"] as $org_class_idx=>$orgs) {
            foreach($orgs as $org_idx=>$v) {
               list($org_id,$org_nm,$org_abbr,$org_class_nm)=$v;
               $optsubdiv .= "<option value='$org_id' ".($org_id==$_SESSION["hris_posmatrix_subdivision"]?"selected='1'":"").">$org_nm $org_class_nm</option>";
               $_SESSION["hris_section_allow"][$org_id] = 1;
            }
         }
      }
      
      /// POSITION SELECT
      $_SESSION["hris_poslevel"] = array();
      $_SESSION["hris_jobs"] = array();
      $ajax->getAllJobs();
      
      $optlevel = "<option value='0'>All</option>";
      foreach($_SESSION["hris_poslevel"] as $level) {
         list($job_class_id,$job_class_nm)=$level;
         $optlevel .= "<option value='$job_class_id' ".($_SESSION["hris_posmatrix_poslevel"]==$job_class_id?"selected='1'":"").">$job_class_nm</option>";
      }
      
      //// FORM QUERY
      $query = "<table style='width:100%;' class='xxfrm'>"
             . "<colgroup><col width='200'/><col/></colgroup>"
             . "<tbody>"
             . "<tr><td>Assessment Session :</td><td><select id='selsession' onchange='set_pos();'>$optsession</select></td></tr>"
             . "<tr><td>Division :</td><td><select id='seldivision' onchange='set_pos();'>$optdiv</select></td></tr>"
             . "<tr><td>Section/Unit :</td><td><select id='selsubdivision' onchange='set_pos()'>$optsubdiv</select></td></tr>"
             . "<tr><td>Position Level :</td><td><select id='selposlevel' onchange='set_pos()'>$optlevel</select></td></tr>"
             . "<tr><td colspan='2'><input type='button' value='Download XLS' onclick='dl_xls();'/></td></tr>"
             . "</tbody></table>";
      
      $ret = "<style type='text/css'>
      
               tr.bhv td { font-weight:bold;color:black;}
               
               .thx div { overflow:hidden;padding:0px; }
               .thx div>div { width:10000px; }
               
               table.lvl {
                  border-spacing:0px;
               }
               
               table.lvl tbody.lvl tr td {
                  width:60px;
                  padding:0px;
                  border-right:1px solid #888;
                  height:22px;
               }
               
               table.comphdr {
                  border-spacing:0px;
               }
               
               table.comphdr tbody.comphdr tr td {
                  width:60px !important;
                  padding:0px;
                  border-right:1px solid #bbb;
                  height:40px;
                  text-align:center;
                  font-size:0.9em;
                  cursor:default;
               }
               
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
                  margin-right:4px;
                  margin-top:3px;
                  text-align:center;
                  -moz-border-radius:7px 7px 0 0;
                  background-color:#ffffff;
                  border:1px solid #cccccc;
                  border-bottom:0px;
                  padding-top:9px;
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
                  background-color:#f7f7f7;
                  border:1px solid #999;
                  border-top:1px solid #999;
                  border-bottom:none;
                  position:relative;
                  top:-1px;
                  height:26px;
                  margin-bottom:-4px;
                  -moz-box-shadow:0px -1px 1px #aaa;
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
                  background-color:#f0f0f0;
                  border:1px solid #999;
                  border-top:1px solid #999;
                  border-bottom:none;
                  position:relative;
                  top:-1px;
                  height:26px;
                  margin-bottom:-4px;
                  -moz-box-shadow:0px -1px 1px #aaa;
               }
               
               ul.axul li.ccunsel {
                  margin-bottom:0px;
               }
               
               ul.axul li.ccsel span {
                  display: block; 
                  padding: 0 1em; 
                  line-height: 20px; 
                  text-align:center;
                  color:#000000;
               }
               
               tr.trmatrix { cursor:pointer;font-size:0.9em; }
               tr.trmatrix>td { background-color:#eee; }
               tr.trmatrix>td+td { background-color:#f0f0f0; }
               tr.trmatrix>td+td+td { background-color:#f7f7f7; }
               tr.trmatrix>td+td+td+td { background-color:#fcfcfc; }
               tr.trmatrix>td+td+td+td+td { background-color:#fcfcfc; }
               tr.trmatrix>td+td+td+td+td+td { background-color:#fff; }
               
               tr.trmatrix:hover td { background-color:#ddf; }
               
               tr.trmatrix0 { cursor:default;font-size:0.9em; }
               tr.trmatrix0>td { background-color:#eee; }
               tr.trmatrix0>td+td { background-color:#f0f0f0; }
               tr.trmatrix0>td+td+td { background-color:#f7f7f7; }
               tr.trmatrix0>td+td+td+td { background-color:#fcfcfc; }
               tr.trmatrix0>td+td+td+td+td { background-color:#fcfcfc; }
               tr.trmatrix0>td+td+td+td+td+td { background-color:#fff; }
      
               .trd1 { cursor:pointer;background-color:#ccf;font-size:0.9em; }
               
               .cl {
                  text-align:left;
                  background-color:#fff;
                  border:0px solid #888;
                  width:58px;
                  overflow:hidden;
                  background-image:url(".XOCP_SERVER_SUBDIR."/modules/hris/images/level_background.png);
                  background-repeat:repeat-y;
                  margin:auto;
                  background-position:-121px;
                  height:14px;
               }
               
      </style><script type='text/javascript' src='".XOCP_SERVER_SUBDIR."/include/prototip2.0.5/js/prototip.js'></script>"
           . "<link rel='stylesheet' type='text/css' href='".XOCP_SERVER_SUBDIR."/include/prototip2.0.5/css/prototip.css'/>";
      $ret .= "<table style='border-spacing:0px;width:100%;'>"
           . "<tbody><tr><td style='background-color:#fff;padding:0px;'>$query</td></tr>"
           . "<tr><td style='background-color:#fff;border:0px solid #bbb;border-top:0px;padding-top:10px;' id='matrix_content'>&nbsp;</td></tr>"
           . "</tbody></table><div style='padding:10px;'>&nbsp;</div>";
      
      
      $_SESSION["html"]->addHeadScript("<script type='text/javascript'><!--
         
         function _asmr_view_behaviour() {
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
         
         var tabs = Array(1,2,3);
         
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
      
         var treditor = null;
         function view_assessment_detail(employee_id,job_id,asid,d,e) {
            var str = $('tremp_'+employee_id+'_'+job_id);
            if(treditor) {
               _destroy(treditor);
               if(treditor.employee_id==employee_id) {
                  treditor.employee_id = null;
                  treditor = null;
                  return;
               }
            }
            treditor = _dce('tr');
            treditor.td = treditor.appendChild(_dce('td'));
            treditor.td.setAttribute('colspan','6');
            treditor.td.setAttribute('style','border:1px solid #bbb;border-top:0px;');
            treditor.td.appendChild(progress_span());
            treditor = str.parentNode.insertBefore(treditor,str.nextSibling);
            treditor.employee_id = employee_id;
            arm_app_loadDetail(employee_id,asid,function(_data) {
               treditor.td.innerHTML = _data;
            });
         }
         
         function dl_xls() {
            var asid = $('selsession').options[$('selsession').selectedIndex].value;
            var division = $('seldivision').options[$('seldivision').selectedIndex].value;
            var subdivision = $('selsubdivision').options[$('selsubdivision').selectedIndex].value;
            var poslevel = $('selposlevel').options[$('selposlevel').selectedIndex].value;
            location.href = '".XOCP_SERVER_SUBDIR."/modules/hris/resultmatrix_xls.php?asid='+asid+'&poslevel_id='+poslevel+'&division='+division+'&subdivision='+subdivision;
         }
         
         function scrolldv(d,e) {
            var l = d.scrollLeft;
            var t = d.scrollTop;
            $('dvscrolljob').scrollTop = t;
            $('dvcomptitle').scrollLeft = l;
         }
         
         function hris_posmatrix_load() {
            ajax_feedback = _caf;
            var asid = $('selsession').options[$('selsession').selectedIndex].value;
            var division = $('seldivision').options[$('seldivision').selectedIndex].value;
            var subdivision = $('selsubdivision').options[$('selsubdivision').selectedIndex].value;
            var poslevel = $('selposlevel').options[$('selposlevel').selectedIndex].value;
            arm_app_loadMatrix(division,subdivision,poslevel,asid,function(_data) {
               var data = recjsarray(_data);
               $('matrix_content').innerHTML = data[1];
               //setTooltips(data[0]);
               
               if(data[2]=='EMPTY') {
                  alert('Fail to load data.');
                  return;
               }
               if(data[2]=='NOCOMPETENCYDEFINED') {
                  alert('No competency found for the jobs. Please setup first.');
                  return;
               }
               
               lvl_mtrx = null;
               matrix_level = new Array();
               matrix_compgroup = new Array();
               matrix_comp = new Array();
               matrix_job = new Array();
               
               setupmatrix(data[2]);
               setmark();
            });
         }
         
         var tdt = new Array();
         function setTooltips(data) {
            for(var i=0;i<data[0].length;i++) {
               if(!data[0][i]) break;
               var job_id = data[0][i][0];
               var competency_id = data[0][i][1];
               var rcl = data[0][i][2];
               var itj = data[0][i][3];
               var competency_nm = data[0][i][4];
               var competency_abbr = data[0][i][5];
               //var desc_en = data[0][i][6];
               //var desc_id = data[0][i][7];
               //if($('tiprcl_'+job_id+'_'+competency_id)) {
               //   $('tiprcl_'+job_id+'_'+competency_id).tip = new Tip('tiprcl_'+job_id+'_'+competency_id,'Required Competency Level : '+rcl,{title:competency_nm,stem:'leftTop',style:'emp',hook:{tip:'leftTop',mouse:true},offset:{x:10,y:-5}});
               //}
               //if($('tipitj_'+job_id+'_'+competency_id)) {
               //   $('tipitj_'+job_id+'_'+competency_id).tip = new Tip('tipitj_'+job_id+'_'+competency_id,'Importance to Job : '+itj,{title:competency_nm,stem:'leftTop',style:'emp',hook:{tip:'leftTop',mouse:true},offset:{x:10,y:-5}});
               //}
               //if($('tiprcl_'+job_id+'_'+competency_id)) {
               //   $('tiprcl_'+job_id+'_'+competency_id).tip = new Tip('tiprcl_'+job_id+'_'+competency_id,'<span>'+desc_en+'</span><hr noshade=\"1\" size=\"1\" color=\"#bbbbbb\"/><span style=\"font-style:italic;\">'+desc_id+'</span>',{stem:'leftTop',style:'emp',title:competency_nm,hook:{tip:'leftTop',mouse:true},offset:{x:10,y:-5}});
               //}
               //if($('tipitj_'+job_id+'_'+competency_id)) {
               //   $('tipitj_'+job_id+'_'+competency_id).tip = new Tip('tipitj_'+job_id+'_'+competency_id,'<span>'+desc_en+'</span><hr noshade=\"1\" size=\"1\" color=\"#bbbbbb\"/><span style=\"font-style:italic;\">'+desc_id+'</span>',{stem:'leftTop',style:'emp',title:competency_nm,hook:{tip:'leftTop',mouse:true},offset:{x:10,y:-5}});
               //}
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
            var tds = $('trcomphdr').childNodes;
            var tdcn = 0;
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
                     _destroy(tdx[no].tooltip);
                     tdx[no].tooltip = null;
                     tdx[no].onmousemove = null;
                     //tdx[no].onmouseout = null;
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
                     //if(tdx[no].prototip) tdx[no].prototip.remove();
                     if(tds[i].id&&tds[i].id!='') {
                        var idx = tds[i].id.split('_');
                        var competency_id = idx[1];
                        tdx[no].innerHTML = '';
                        tdx[no].onclick = '';
                        tdx[no].id = 'tdx_'+employee_id+'_'+competency_id+'_'+job_id;
                        var rcl = -1;
                        var itj = 0;
                        
                        if(matrix_job[job_id]&&matrix_job[job_id][competency_id]) {
                           rcl = matrix_job[job_id][competency_id][2];
                           itj = matrix_job[job_id][competency_id][3];
                        }
                        
                        var ccl = 0;
                        var gap = -rcl*itj;
                        var la = 'never';
                        var last_level = 0;
                        
                        
                        if(matrix_level[employee_id]&&matrix_level[employee_id][job_id]) {
                           for(var k=0;k<matrix_level[employee_id][job_id].length;k++) {
                              if(matrix_level[employee_id][job_id][k][2]==competency_id) {
                                 ccl = matrix_level[employee_id][job_id][k][3];
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
                           tdx[no].appendChild(dv);
                           if(itj>2) {
                              tdx[no].style.backgroundColor = '#ffff99';
                           } else {
                              tdx[no].style.backgroundColor = '';
                           }
                           
                           /*
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
                           */
                           
                           dv.innerHTML =  '<div style=\"height:7px;overflow:hidden;\">'
                                       + '<img style=\"margin-left:'+lpos[rcl]+'px;\" src=\"".XOCP_SERVER_SUBDIR."/modules/hris/images/level_rcl.png\"/>'
                                       + '</div>'
                                       
                                       //+ '<div style=\"height:7px;overflow:hidden;\">'
                                       //+ '<img style=\"margin-left:'+lpos[last_level]+'px;\" src=\"".XOCP_SERVER_SUBDIR."/modules/hris/images/level_gap.png\"/>'
                                       //+ '</div>'
                                       
                                       + '<div style=\"margin-top:0px;height:7px;overflow:hidden;\">'
                                       + '<img style=\"margin-left:'+lpos[Math.floor(ccl)]+'px;\" src=\"".XOCP_SERVER_SUBDIR."/modules/hris/images/level_ccl.png\"/>'
                                       + '</div>'

                           
                           tdx[no].onclick = function() {
                              var asmx = this.id.split('_');
                              var asid = $('selsession').options[$('selsession').selectedIndex].value;
                              assessment_modify_result(asid,asmx[1],asmx[3],asmx[2]);
                           }
                           
                           
                           var content = '<div class=\"cl\" style=\"\">'
                                       + '<div style=\"height:7px;overflow:hidden;\">'
                                       + '<img style=\"margin-left:'+lpos[rcl]+'px;\" src=\"".XOCP_SERVER_SUBDIR."/modules/hris/images/level_rcl.png\"/>'
                                       + '</div>'
                                       
                                       + '<div style=\"height:7px;overflow:hidden;\">'
                                       + '<img style=\"margin-left:'+lpos[Math.floor(ccl)]+'px;\" src=\"".XOCP_SERVER_SUBDIR."/modules/hris/images/level_ccl.png\"/>'
                                       + '</div>'
                                       + '</div>'
                                       + '<table class=\"emp_info\" align=\"center\"><tbody>'
                                       + '<tr><td>Current Competency Level:</td><td style=\"text-align:right;\">'+ccl+'</td></tr>'
                                       + '<tr><td>Required Competency Level:</td><td style=\"text-align:right;\">'+rcl+'</td></tr>'
                                       + '<tr><td>Importance to Job:</td><td style=\"text-align:right;\">'+matrix_job[job_id][competency_id][3]+'</td></tr>'
                                       + '<tr><td>Gap:</td><td style=\"text-align:right;\">'+gap+'</td></tr>'
                                       + '</tbody></table>';
                           
                           //tdx[no].tip = new Tip(tdx[no],content,{style:'emp',title:matrix_comp[competency_id][3],offset:{x:0,y:10},hook:{tip:'topLeft',mouse:true}});
                           tdx[no].setAttribute('onmousemove','tt_cell(\"'+matrix_comp[competency_id][3]+'\",\\''+content+'\\',this,event);');
                           tdx[no].setAttribute('onmouseout','tt_cell_out(this,event);');
                           
                        } else {
                           tdx[no].setAttribute('onmousemove','tt_cell(\"'+matrix_comp[competency_id][3]+'\",\"Competency Not Applicable.\",this,event);');
                           tdx[no].setAttribute('onmouseout','tt_cell_out(this,event);');
                           //tdx[no].tip = new Tip(tdx[no],'Competency Not Applicable.',{style:'emp',width:180});
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
         
         
         var empx = 0;
         var jobx = 0;
         
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
                        r[j].className = 'trmatrix';
                     }
                  }
               }
            }
         }
         
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
         
         var comp_col = null;
         function setcompheader() {
            var ctab = gtab();
            var hdr = $('trcomphdr');
            var tds = hdr.childNodes;
            var tdc = 0;
            var tdcn = 0;
            comp_col = new Array();
            var compgroup_id = ctab[0];
            var competency_class = ctab[1];
            
            if(matrix_compgroup[compgroup_id]&&matrix_compgroup[compgroup_id][competency_class]) {
               for(var i=0;i<matrix_compgroup[compgroup_id][competency_class].length;i++) {
                  var competency_id = matrix_compgroup[compgroup_id][competency_class][i][0];
                  if(tds[tdc]) {
                     _destroy(tds[tdc].tooltip);
                     tds[tdc].tooltip = null;
                     if(ctab[0]==3) {
                        if(matrix_job[jobx]&&matrix_job[jobx][competency_id]) {
                        } else {
                           continue;
                        }
                     }
                     tds[tdc].innerHTML = matrix_compgroup[compgroup_id][competency_class][i][4];
                     tds[tdc].setAttribute('id','competency_'+competency_id);
                     tds[tdc].setAttribute('onmousemove','tt_compheader(\"'+matrix_compgroup[compgroup_id][competency_class][i][4]+'\",\"'+matrix_compgroup[compgroup_id][competency_class][i][3]+'\",\"'+matrix_compgroup[compgroup_id][competency_class][i][5]+'\",this,event);');
                     tds[tdc].setAttribute('onmouseout','tt_compheader_out(this,event);');
                     tdc++;
                  }
               }
            }
            
            if(tdc==0) { /// competency not found
               tds[tdc].innerHTML = '( n.a. )';
               tds[tdc].setAttribute('id','');
               tds[tdc].setAttribute('onmousemove','tt_compheader(\"\",\"Not Applicable\",\"Please select another employee to see their competency profile for this category by clicking their name.\",this,event);');
               tds[tdc].setAttribute('onmouseout','tt_compheader_out(this,event);');
               tdc++;
            }
            
            for(var i=tdc;i<20;i++) {
               if(tds[i]) {
                  tds[i].innerHTML = '';
                  tds[i].setAttribute('onmousemove','');
                  _destroy(tds[i].tooltip);
                  tds[i].tooltip = null;
                  tds[i].setAttribute('id','');
               }
            }
         }
         
         var lvl_mtrx = null;
         var matrix_level = new Array();
         var matrix_compgroup = new Array();
         var matrix_comp = new Array();
         var matrix_job = new Array();
         function setupmatrix(data) {
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
                  
                  
                  for(var i=0;i<tabs.length;i++) {
                     $('tabcg_'+tabs[i]).className = '';
                     $('cgdiv_'+tabs[i]).style.display = 'none';
                  }
                  $('tabcg_'+compgroup_id).className = 'compsel';
                  $('cgdiv_'+compgroup_id).style.display = '';
                  
                  if($('tabcc_'+compgroup_id+'_'+competency_class)) {
                     if(competency_class&&competency_class!='') {
                        var ccarr = $('ulcc_'+compgroup_id).getElementsByTagName('li');
                        for(var i=0;i<ccarr.length;i++) {
                           if(ccarr[i].className=='compclasstips') continue;
                           ccarr[i].className = '';
                        }
                        $('tabcc_'+compgroup_id+'_'+competency_class).className = 'ccsel';
                     }
                  }
               }
            }
            
            
            setcompheader();
            setlevel();
         }
         
         
      // --></script>");
      
      $_SESSION["html"]->registerLoadAction("hris_posmatrix_load");
      
      $js = "<script type='text/javascript'><!--
         
         var trcldiv = null;
         var titjdiv = null;
         function t_rcl_mousemove(d,e) {
            if(!trcldiv) {
               trcldiv = _dce('div');
               trcldiv.setAttribute('style','font-size:0.9em;opacity:1;position:absolute;z-index:1000;background-color:#ffffff;padding:6px;border:1px solid #333;visibility:hidden;-moz-border-radius:0 3px 3px 3px;');
               trcldiv = document.body.appendChild(trcldiv);
               trcldiv.innerHTML = 'Required Competency Level';
            }
            
            trcldiv.style.top = parseInt(5+e.pageY)+'px';
            trcldiv.style.left = parseInt(10+e.pageX)+'px';
            trcldiv.style.visibility = 'visible';
         }
         
         function t_rcl_mouseout(d,e) {
            if(trcldiv) {
               trcldiv.style.visibility = 'hidden';
               trcldiv.style.top = '-1000px';
            }
         }
         
         function t_itj_mousemove(d,e) {
            if(!titjdiv) {
               titjdiv = _dce('div');
               titjdiv.setAttribute('style','font-size:0.9em;opacity:1;position:absolute;z-index:1000;background-color:#ffffff;padding:6px;border:1px solid #333;visibility:hidden;-moz-border-radius:0 3px 3px 3px;');
               titjdiv = document.body.appendChild(titjdiv);
               titjdiv.innerHTML = 'Importance To Job';
            }
            
            titjdiv.style.top = parseInt(5+e.pageY)+'px';
            titjdiv.style.left = parseInt(10+e.pageX)+'px';
            titjdiv.style.visibility = 'visible';
         }
         
         function t_itj_mouseout(d,e) {
            if(titjdiv) {
               titjdiv.style.visibility = 'hidden';
               titjdiv.style.top = '-1000px';
            }
         }
         
         function hdr_competency_mousemove(competency_id,d,e) {
            if(!d.tooltip) {
               d.tooltip = $('xcomphdr_'+competency_id);
            }
            
            d.tooltip.style.top = parseInt(5+e.pageY)+'px';
            d.tooltip.style.left = parseInt(10+e.pageX)+'px';
            d.tooltip.style.visibility = 'visible';
         }
         
         function hdr_competency_mouseout(d,e) {
            if(d.tooltip) {
               d.tooltip.style.visibility = 'hidden';
               d.tooltip.style.top = '-1000px';
            }
         }
         
         function tval_rcl_mousemove(job_id,competency_id,d,e) {
            if(!d.tooltip) {
               d.tooltip = $('xval_rcl_'+job_id+'_'+competency_id);
            }
            
            d.tooltip.style.top = parseInt(5+e.pageY)+'px';
            d.tooltip.style.left = parseInt(10+e.pageX)+'px';
            d.tooltip.style.visibility = 'visible';
         }
         
         function tval_rcl_mouseout(d,e) {
            if(d.tooltip) {
               d.tooltip.style.visibility = 'hidden';
               d.tooltip.style.top = '-1000px';
            }
         }
         
         function tjob_mousemove(job_id,d,e) {
            if(!d.tooltip) {
               d.tooltip = $('tjob_'+job_id);
            }
            
            d.tooltip.style.top = parseInt(5+e.pageY)+'px';
            d.tooltip.style.left = parseInt(10+e.pageX)+'px';
            d.tooltip.style.visibility = 'visible';
         }
         
         function tjob_mouseout(d,e) {
            if(d.tooltip) {
               d.tooltip.style.visibility = 'hidden';
               d.tooltip.style.top = '-1000px';
            }
         }
         
         function tt_compheader_out(d,e) {
            if(d.tooltip) {
               d.tooltip.style.visibility = 'hidden';
               d.tooltip.style.top = '-1000px';
            }
         }
         
         function tt_compheader(abbr,title,description,d,e) {
            if(!d.tooltip) {
               d.tooltip = _dce('div');
               
               d.tooltip.setAttribute('style','width:350px;-moz-box-shadow:1px 1px 3px #000;font-size:0.9em;opacity:1;position:absolute;z-index:1000;background-color:#ffffff;padding:0px;border:1px solid #888;visibility:hidden;-moz-border-radius:5px;');
               d.tooltip.innerHTML = '<div style=\"background-color:#eee;padding:4px;-moz-border-radius:5px 5px 0 0;color:#888;font-weight:bold;text-align:left;\">'+title+'</div>'
                                   + '<div style=\"padding:8px;\">' + description + '</div>';
               d.tooltip = document.body.appendChild(d.tooltip);
            }
            
            var pagewidth = parseInt($('tbody_mtrx').offsetWidth);
            
            var x = parseInt(e.screenX);
            if(x>(pagewidth-370)) {
               d.tooltip.style.top = parseInt(5+e.pageY)+'px';
               d.tooltip.style.left = parseInt(-10+e.pageX-350)+'px';
            } else {
               d.tooltip.style.top = parseInt(5+e.pageY)+'px';
               d.tooltip.style.left = parseInt(10+e.pageX)+'px';
            }
            d.tooltip.style.visibility = 'visible';
            
         }
         
         function tt_cell_out(d,e) {
            if(d.tooltip) {
               d.tooltip.style.visibility = 'hidden';
               d.tooltip.style.top = '-1000px';
            }
         }
         
         function tt_cell(title,description,d,e) {
            if(!d.tooltip) {
               d.tooltip = _dce('div');
               
               d.tooltip.setAttribute('style','width:250px;-moz-box-shadow:1px 1px 3px #000;font-size:0.9em;opacity:1;position:absolute;z-index:1000;background-color:#ffffff;padding:0px;border:1px solid #888;visibility:hidden;-moz-border-radius:5px;');
               d.tooltip.innerHTML = '<div style=\"background-color:#eee;padding:4px;-moz-border-radius:5px 5px 0 0;color:#888;font-weight:bold;text-align:left;\">'+title+'</div>'
                                   + '<div style=\"padding:8px;\">' + description + '</div>';
               d.tooltip = document.body.appendChild(d.tooltip);
            }
            
            var pagewidth = parseInt($('tbody_mtrx').offsetWidth);
            
            var x = parseInt(e.screenX);
            if(x>(pagewidth-270)) {
               d.tooltip.style.top = parseInt(5+e.pageY)+'px';
               d.tooltip.style.left = parseInt(-10+e.pageX-250)+'px';
            } else {
               d.tooltip.style.top = parseInt(5+e.pageY)+'px';
               d.tooltip.style.left = parseInt(10+e.pageX)+'px';
            }
            d.tooltip.style.visibility = 'visible';
            
         }
         
         function temp_mousemove(emp_id,d,e) {
            if(!d.tooltip) {
               d.tooltip = $('temp_'+emp_id);
            }
            
            d.tooltip.style.top = parseInt(5+e.pageY)+'px';
            d.tooltip.style.left = parseInt(10+e.pageX)+'px';
            d.tooltip.style.visibility = 'visible';
         }
         
         function temp_mouseout(d,e) {
            if(d.tooltip) {
               d.tooltip.style.visibility = 'hidden';
               d.tooltip.style.top = '-1000px';
            }
         }
         
         function torg_mousemove(job_id,d,e) {
            if(!d.tooltip) {
               d.tooltip = $('torg_'+job_id);
            }
            
            d.tooltip.style.top = parseInt(5+e.pageY)+'px';
            d.tooltip.style.left = parseInt(10+e.pageX)+'px';
            d.tooltip.style.visibility = 'visible';
         }
         
         function torg_mouseout(d,e) {
            if(d.tooltip) {
               d.tooltip.style.visibility = 'hidden';
               d.tooltip.style.top = '-1000px';
            }
         }
         
         function set_pos() {
            ajax_feedback = _caf;
            var asid = $('selsession').options[$('selsession').selectedIndex].value;
            var division = $('seldivision').options[$('seldivision').selectedIndex].value;
            var subdivision = $('selsubdivision').options[$('selsubdivision').selectedIndex].value;
            var poslevel = $('selposlevel').options[$('selposlevel').selectedIndex].value;
            arm_app_setPosition(division,subdivision,poslevel,asid,function(_data) {
               var data = recjsarray(_data);
               if(data[0]=='NOCHANGE') {
               } else {
                  var data = recjsarray(_data);
                  $('selsubdivision').innerHTML = data[0];
               }
               if(data[1]=='NOCHANGE') {
               } else {
                  var data = recjsarray(_data);
                  $('selposlevel').innerHTML = data[1];
               }
               
               hris_posmatrix_load();
               
            });
         }
         
         function change_class(classx) {
            ajax_feedback = _caf;
            arm_app_setCompetencyClass(classx,function(_data) {
               hris_posmatrix_load();
            });
         }
         
         function change_group(groupx) {
            ajax_feedback = _caf;
            arm_app_setCompetencyGroup(groupx,function(_data) {
               hris_posmatrix_load();
            });
         }
         
         
         /////////////////////////////////////////////// ccl editor
         
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
               hris_posmatrix_load();
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
               hris_posmatrix_load();
               asmodresbox.fade();
            });
         }
         
         var asmodres = null;
         var asmodresbox = null;
         function assessment_modify_result(asid,employee_id,job_id,competency_id) {
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
         }
         
         var vx = null;
         function view_behaviour_indicator(d,e) {
            vx = $('vx');
            vx.oldHTML = vx.innerHTML;
            var ccl = trim($('altered_ccl').value);
            vx.innerHTML = '';
            vx.appendChild(progress_span());
            vx.ccl = ccl;
            amr_app_viewBehaviourIndicator(asmodres.asid,asmodres.employee_id,asmodres.job_id,asmodres.competency_id,ccl,function(_data) {
               $('vx').innerHTML = _data;
            });
         }
         
         function next_vx(d,e) {
            vx.ccl++;
            if(vx.ccl>4) {
               vx.ccl = 4;
               return;
            }
            $('vxcontent').innerHTML = '';
            $('vxcontent').appendChild(progress_span());
            amr_app_viewBehaviourIndicator(asmodres.asid,asmodres.employee_id,asmodres.job_id,asmodres.competency_id,vx.ccl,function(_data) {
               $('vx').innerHTML = _data;
            });
         }
         
         function previous_vx(d,e) {
            vx.ccl--;
            if(vx.ccl<1) {
               vx.ccl = 1;
               return;
            }
            $('vxcontent').innerHTML = '';
            $('vxcontent').appendChild(progress_span());
            amr_app_viewBehaviourIndicator(asmodres.asid,asmodres.employee_id,asmodres.job_id,asmodres.competency_id,vx.ccl,function(_data) {
               $('vx').innerHTML = _data;
            });
         }
         
         function back_vx(d,e) {
            $('vx').innerHTML = $('vx').oldHTML;
            
         }
         
         ///////////////////////////////////////////////////// ccl editor - end
         
         ///////////////////////////////////////////////////// spider chart
         
         function print_viewchartpos(division,subdivision,poslevel,asid,aposlevel) {
            location.href = '".XOCP_SERVER_SUBDIR."/modules/hris/print/assessment_radar_chart.php?d='+division+'&s='+subdivision+'&p='+poslevel+'&a='+asid+'&apos='+aposlevel;
         }
         
         var spchart = null;
         var spchartbox = null;
         function view_chart_pos(aposlevel,d,e) {
            var asid = $('selsession').options[$('selsession').selectedIndex].value;
            var division = $('seldivision').options[$('seldivision').selectedIndex].value;
            var subdivision = $('selsubdivision').options[$('selsubdivision').selectedIndex].value;
            var poslevel = $('selposlevel').options[$('selposlevel').selectedIndex].value;
            ajax_feedback = _caf;
            spchart = _dce('div');
            spchart.setAttribute('id','spchart');
            spchart = document.body.appendChild(spchart);
            spchart.sub = spchart.appendChild(_dce('div'));
            spchart.sub.setAttribute('id','innerspchart');
            
            if(spchartbox) {
              _destroy(spchartbox.overlay);
            }
            
            spchartbox = new GlassBox();
            spchartbox.init('spchart','900px','600px','hidden','default',false,false);
            spchartbox.lbo(false,0.3);
            spchartbox.appear();
            
            arm_app_viewChartPos(division,subdivision,poslevel,asid,aposlevel,function(_data) {
               if(_data!='FAIL') {
                  var data = recjsarray(_data);
                  $('innerspchart').innerHTML = data[0];
               }
            });
         }
         
         
         function view_chart_proactive_index(aposlevel,d,e) {
            var asid = $('selsession').options[$('selsession').selectedIndex].value;
            var division = $('seldivision').options[$('seldivision').selectedIndex].value;
            var subdivision = $('selsubdivision').options[$('selsubdivision').selectedIndex].value;
            var poslevel = $('selposlevel').options[$('selposlevel').selectedIndex].value;
            ajax_feedback = _caf;
            spchart = _dce('div');
            spchart.setAttribute('id','spchart');
            spchart = document.body.appendChild(spchart);
            spchart.sub = spchart.appendChild(_dce('div'));
            spchart.sub.setAttribute('id','innerspchart');
            
            if(spchartbox) {
              _destroy(spchartbox.overlay);
            }
            
            spchartbox = new GlassBox();
            spchartbox.init('spchart','900px','600px','hidden','default',false,false);
            spchartbox.lbo(false,0.3);
            spchartbox.appear();
            
            arm_app_viewChartProactiveIndex(division,subdivision,poslevel,asid,aposlevel,function(_data) {
               if(_data!='FAIL') {
                  var data = recjsarray(_data);
                  $('innerspchart').innerHTML = data[0];
               }
            });
         }
         
         
         ///////////////////////////////////////////////////// spider chart - end
         
         
      // --></script>";
      
      return $ret.$js.$ajax->getJs().$ajax2->getJs();
      
   }
   
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $this->arm();
            break;
         default:
            $ret = $this->arm();
            break;
      }
      return $ret;
   }
}

} // HRIS_ASSESSMENTRESULTMATRIX_DEFINED
?>