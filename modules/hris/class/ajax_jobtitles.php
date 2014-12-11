<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_editjobtitles.php                //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_JOBTITLESAJAX_DEFINED') ) {
   define('HRIS_JOBTITLESAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");

class _hris_class_JobTitlesAjax extends AjaxListener {
   
   function _hris_class_JobTitlesAjax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/hris/class/ajax_editjobtitles.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_Delete","app_editJobTitles","app_saveJobTitles",
                            "app_searchCompetency","app_addCompetency","app_editCompetencyProperty",
                            "app_saveCompetencyProperty","app_detachCompetency","app_changeJobClass",
                            "app_changeOrganization","app_cancelNewJob","app_downloadCompetency");
   }
   
   function app_downloadCompetency($args) {
      $db=&Database::getInstance();
      $job_id = $args[0];
      $compgroup_id = $args[1];
      
      $sql = "SELECT competency_id,competency_nm,competency_abbr FROM ".XOCP_PREFIX."competency"
           . " WHERE compgroup_id = '$compgroup_id'"
           . " AND status_cd = 'normal'"
           . " ORDER BY competency_class";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         $ret = "";
         while(list($competency_id,$competency_nm,$competency_abbr)=$db->fetchRow($result)) {
            $rcl = 2; $itj = 2;
            $sql = "INSERT INTO ".XOCP_PREFIX."job_competency (job_id,competency_id,rcl,itj)"
                 . " VALUES ('$job_id','$competency_id','$rcl','$itj')";
            $db->query($sql);
            if($db->errno()==1062) {
               $sql = "SELECT rcl,itj FROM ".XOCP_PREFIX."job_competency"
                    . " WHERE job_id = '$job_id' AND competency_id = '$competency_id'";
               $res = $db->query($sql);
               list($rcl,$itj)=$db->fetchRow($res);
            }
            $ret .= $this->renderCompetency($competency_id,$competency_nm,$competency_abbr,$rcl,$itj);
         }
         return array($compgroup_id,$ret);
      }
      return "EMPTY";
   }
   
   function app_cancelNewJob($args) {
      $db=&Database::getInstance();
      $job_id = $args[0];
      $sql = "DELETE FROM ".XOCP_PREFIX."jobs WHERE job_id = '$job_id'";
      $db->query($sql);
   }
   
   function getOrgsUp($org_id) {
      $db=&Database::getInstance();
      $sql = "SELECT parent_id FROM ".XOCP_PREFIX."orgs WHERE org_id = '$org_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($parent_id)=$db->fetchRow($result);
         if($parent_id>0) {
            $_SESSION["hris_org_parents"][] = $parent_id;
            $this->getOrgsUp($parent_id);
         }
      }
   }
   
   function getAssessorOptions($job_id) {
      $db=&Database::getInstance();
      $_SESSION["hris_org_parents"] = array();
      $sql = "SELECT org_id,job_class_id,assessor_job_id FROM ".XOCP_PREFIX."jobs"
           . " WHERE job_id = '$job_id'";
      $result = $db->query($sql);
      list($org_id,$job_class_id,$assessor_job_id)=$db->fetchRow($result);
      $_SESSION["hris_org_parents"][] = $org_id;
      $this->getOrgsUp($org_id);
      
      $opt_assessor = "";
      if(count($_SESSION["hris_org_parents"])>0) {
         $no = 0;
         foreach($_SESSION["hris_org_parents"] as $org_idx) {
            $sql = "SELECT a.job_id,a.job_cd,a.job_nm,b.job_class_nm,a.job_class_id"
                 . " FROM ".XOCP_PREFIX."jobs a"
                 . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
                 . " WHERE a.org_id = '$org_idx'"
                 . " ORDER BY a.job_class_id,a.job_nm";
            $result = $db->query($sql);
            if($db->getRowsNum($result)>0) {
               while(list($job_idx,$job_cdx,$job_nmx,$job_class_nmx,$job_class_idx)=$db->fetchRow($result)) {
                  if($job_class_idx>=$job_class_id) continue;
                  if($job_class_idx>5) continue; /// batas assessor terendah adalah supervisor (job_class_id = 5)
                  $sassessor = "";
                  if($no==0&&$assessor_job_id==0) {
                     $sassessor = "selected='1'";
                  }
                  if($assessor_job_id>0&&$assessor_job_id==$job_idx) {
                     $sassessor = "selected='1'";
                  }
                  $opt_assessor = "\n<option value='$job_idx' ${sassessor}>$job_cdx $job_nmx [$job_class_nmx]</option>" . $opt_assessor;
                  $no++;
               }
            }
         }
      }
      
      return "<option value='0'>-</option>".$opt_assessor;
   }
   
   function app_changeOrganization($args) {
      $db=&Database::getInstance();
      $job_id = $args[0];
      $org_id = $args[1];
      $sql = "UPDATE ".XOCP_PREFIX."jobs SET org_id = '$org_id'"
           . " WHERE job_id = '$job_id'";
      $db->query($sql);
      return $this->getAssessorOptions($job_id);
   }
   
   function app_changeJobClass($args) {
      $db=&Database::getInstance();
      $job_id = $args[0];
      $job_class_id = $args[1];
      $sql = "UPDATE ".XOCP_PREFIX."jobs SET job_class_id = '$job_class_id'"
           . " WHERE job_id = '$job_id'";
      $db->query($sql);
      return $this->getAssessorOptions($job_id);
   }
   
   function app_detachCompetency($args) {
      $db=&Database::getInstance();
      $competency_id = $args[0];
      $job_id = $args[1];
      $sql = "DELETE FROM ".XOCP_PREFIX."job_competency"
           . " WHERE competency_id = '$competency_id'"
           . " AND job_id = '$job_id'";
      $db->query($sql);
   }
   
   function app_saveCompetencyProperty($args) {
      $db=&Database::getInstance();
      $competency_id = $args[0];
      $job_id = $args[1];
      $arr = parseForm($args[2]);
      foreach($arr as $k=>$v) {
         $$k = addslashes(trim($v));
      }
      $sql = "UPDATE ".XOCP_PREFIX."job_competency SET rcl = '$rcl', itj = '$itj'"
           . " WHERE job_id = '$job_id' AND competency_id = '$competency_id'";
      $db->query($sql);
      return array($rcl,$itj);
   }
   
   function app_editCompetencyProperty($args) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
      global $proficiency_level_name;
      $db=&Database::getInstance();
      $competency_id = $args[0];
      $job_id = $args[1];
      $sql = "SELECT b.competency_cd,b.competency_abbr,a.competency_id,b.competency_nm,a.rcl,a.itj,"
           . "b.compgroup_id"
           . " FROM ".XOCP_PREFIX."job_competency a"
           . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
           . " WHERE a.job_id = '$job_id'"
           . " AND a.competency_id = '$competency_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($competency_cd,$competency_abbr,$competency_id,$competency_nm,$rcl,$itj,$compgroup_id)=$db->fetchRow($result);
         $ckr[0] = $ckr[1] = $ckr[2] = $ckr[3] = $ckr[4] = "";
         $ckr[$rcl] = "checked='1'";
         $ret = "<form id='compfrm'><table class='xxfrm' style='width:100%;'><tbody>"
              . "<tr><td>Required Competency Level (RCL)</td><td>"
                  . "<input type='radio' name='rcl' value='4' id='rcl4' $ckr[4]/> <label class='xlnk' for='rcl4'>$proficiency_level_name[4] (4)</label>&nbsp;&nbsp;<br/>"
                  . "<input type='radio' name='rcl' value='3' id='rcl3' $ckr[3]/> <label class='xlnk' for='rcl3'>$proficiency_level_name[3] (3)</label>&nbsp;&nbsp;<br/>"
                  . "<input type='radio' name='rcl' value='2' id='rcl2' $ckr[2]/> <label class='xlnk' for='rcl2'>$proficiency_level_name[2] (2)</label>&nbsp;&nbsp;<br/>"
                  . "<input type='radio' name='rcl' value='1' id='rcl1' $ckr[1]/> <label class='xlnk' for='rcl1'>$proficiency_level_name[1] (1)</label>&nbsp;&nbsp;<br/>"
                  . "<input type='radio' name='rcl' value='0' id='rcl0' $ckr[0]/> <label class='xlnk' for='rcl0'>$proficiency_level_name[0] (0)</label>&nbsp;&nbsp;"
                  . "</td></tr>"
              . "<tr><td>Importance of Competency to Job (ITJ)</td><td><input onclick='_dsa(this);'name='itj' type='text' style='width:20px;' value='$itj'/></td></tr>"
              . "<tr><td colspan='2'>"
              . "<input type='button' onclick='savecompjob(this,event);' value='"._SAVE."'/>&nbsp;"
              . "<input type='button' onclick='detachcompjob(this,event);' value='Detach'/>&nbsp;"
              . "</td></tr>"
              . "</tbody></table></form>";
         return array($compgroup_id,$ret);
      }
      return "EMPTY";
   }
   
   function app_addCompetency($args) {
      $db=&Database::getInstance();
      $competency_id = $args[0];
      $compgroup_id = $args[1];
      $job_id = $args[2];
      $rcl = 2; $itj = 2;
      $sql = "INSERT INTO ".XOCP_PREFIX."job_competency (job_id,competency_id,rcl,itj)"
           . " VALUES ('$job_id','$competency_id','$rcl','$itj')";
      $db->query($sql);
      if($db->errno()==0) {
         $sql = "SELECT a.competency_cd,a.competency_abbr,a.competency_nm,a.competency_cd,a.competency_abbr,a.competency_class,"
              . "b.compgroup_nm"
              . " FROM ".XOCP_PREFIX."competency a"
              . " LEFT JOIN ".XOCP_PREFIX."compgroup b USING(compgroup_id)"
              . " WHERE a.competency_id = '$competency_id'";
         $result = $db->query($sql);
         list($competency_cd,$competency_abbr,$competency_nm,$competency_cd,$competency_abbr,$competency_class,$compgroup_nm)=$db->fetchRow($result);
         $ret = "<table class='rowlist'>"
              . "<colgroup><col/><col width='40'/><col width='40'/></colgroup>"
              . "<tbody><tr>"
              . "<td>$competency_abbr <span class='xlnk' onclick='editjobcomp(\"$competency_id\",this,event);'>$competency_nm</span></td>"
              . "<td style='text-align:center;' id='rclcomp_${competency_id}'>$rcl</td>"
              . "<td style='text-align:center;' id='itjcomp_${competency_id}'>$itj</td>"
              . "</tr></tbody></table>";
         return array($compgroup_id,$competency_id,$ret);
      } else {
         return "FAIL";
      }
   }
   
   function app_searchCompetency($args) {
      $db=&Database::getInstance();
      $q = $args[0];
      $compgroup_id = $args[1];
      $qstr = formatQueryString($q);
      $ret = array();
      
      $sql = "SELECT a.competency_id, a.competency_nm, a.competency_cd, "
           . "MATCH(a.competency_abbr) AGAINST('$qstr' IN BOOLEAN MODE) as score0, "
           . "MATCH (a.competency_nm) AGAINST ('$qstr' IN BOOLEAN MODE) as score"
           . " FROM ".XOCP_PREFIX."competency a"
           . " WHERE (MATCH (a.competency_nm) AGAINST ('$qstr' IN BOOLEAN MODE)"
//           . " OR MATCH (a.competency_cd) AGAINST ('$qstr' IN BOOLEAN MODE)"
//           . " OR MATCH (a.competency_abbr) AGAINST ('$qstr' IN BOOLEAN MODE)"
           . ")"
           . " AND a.compgroup_id = '$compgroup_id'"
           . " ORDER BY score DESC, score0 DESC";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         $no = 0;
         while(list($competency_id,$competency_nm,$competency_cd)=$db->fetchRow($result)) {
            if($no >= 1000) break;
            $ret[] = array("$competency_nm [$competency_cd]",$competency_id);
            $no++;
         }
      }
      
      if(count($ret)>0) {
         return $ret;
      } else {
         return "EMPTY";
      }
      
   }
   
   function app_saveJobTitles($args) {
      $db=&Database::getInstance();
      $job_id = $args[0];
      $arr = parseForm($args[1]);
      foreach($arr as $k=>$v) {
         $$k = addslashes(trim($v));
      }
      if($job_nm=="") {
         $job_nm = "noname";
      }
      $description = urldecode($args[2]);
      $summary = urldecode($args[3]);
      $location_id = "0";
      if($job_id=="new") {
         $sql = "SELECT MAX(job_id) FROM ".XOCP_PREFIX."jobs";
         $result = $db->query($sql);
         list($job_idx)=$db->fetchRow($result);
         $job_id = $job_idx+1;
         $user_id = getUserID();
         $sql = "INSERT INTO ".XOCP_PREFIX."jobs (job_id,job_nm,summary,description,created_user_id,job_cd,job_class_id,workarea_id,location_id,org_id,job_abbr,assessor_job_id)"
              . " VALUES('$job_id','$job_nm','$summary','$description','$user_id','$job_cd','$job_class_id','$workarea_id','$location_id','$org_id','$job_abbr','$assessor_job_id')";
         $db->query($sql);
      } else {
         $sql = "UPDATE ".XOCP_PREFIX."jobs SET "
              . "job_nm = '$job_nm',"
              . "summary = '$summary',"
              . "description = '$description',"
              . "job_cd = '$job_cd',"
              . "job_class_id = '$job_class_id',"
              . "workarea_id = '$workarea_id',"
              . "location_id = '$location_id',"
              . "org_id = '$org_id',"
              . "job_abbr = '$job_abbr',"
              . "assessor_job_id = '$assessor_job_id'"
              . " WHERE job_id = '$job_id'";
         $db->query($sql);
      }

      $sql = "SELECT a.job_id,a.job_cd,a.job_nm,a.description,"
           . "b.org_nm,c.org_class_nm,a.job_abbr"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."orgs b USING(org_id)"
           . " LEFT JOIN ".XOCP_PREFIX."org_class c USING(org_class_id)"
           . " WHERE a.job_id = '$job_id'";
      
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($job_id,$job_cd,$job_nm,$description,$org_nm,$org_class_nm,$job_abbr)=$db->fetchRow($result);
      } else {
         return "EMPTY";
      }
      
      return array("dvjob_${job_id}",$this->app_editJobTitles(array($job_id)));
   }
   
   function app_editJobTitles($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $job_id = $args[0];
      if($job_id=="new") {
         $job_nm = "noname";
         $sql = "SELECT MAX(job_id) FROM ".XOCP_PREFIX."jobs";
         $result = $db->query($sql);
         list($job_idx)=$db->fetchRow($result);
         $job_id = $job_idx+1;
         $sql = "INSERT INTO ".XOCP_PREFIX."jobs (job_id,job_nm,created_user_id)"
              . " VALUES('$job_id','$job_nm','$user_id')";
         $db->query($sql);
         $job_nm = $job_cd = $job_abbr = $desc = "";
         $delete_button = "";
      } else {

         $sql = "SELECT b.org_nm,c.org_class_nm,"
              . "a.description,a.job_nm,a.job_cd,a.job_class_id,"
              . "a.workarea_id,a.location_id,a.org_id,a.job_abbr,a.assessor_job_id,"
              . "a.summary"
              . " FROM ".XOCP_PREFIX."jobs a"
              . " LEFT JOIN ".XOCP_PREFIX."orgs b USING(org_id)"
              . " LEFT JOIN ".XOCP_PREFIX."org_class c USING(org_class_id)"
              . " WHERE a.job_id = '$job_id'";
         $result = $db->query($sql);
         
         list($org_nm,$org_class_nm,$desc,$job_nm,$job_cd,$job_class_id,$workarea_id,$location_id,
              $org_id,$job_abbr,$assessor_job_id,$summary)=$db->fetchRow($result);
         $job_nm = htmlentities($job_nm,ENT_QUOTES);
         $job_cd = htmlentities($job_cd,ENT_QUOTES);
         $job_abbr = htmlentities($job_abbr,ENT_QUOTES);
         //$desc = htmlentities($desc,ENT_QUOTES);
         $delete_button = "<input onclick='delete_job();' type='button' value='"._DELETE."'/>";
      }
      
      //// position level
      $sql = "SELECT job_class_id,job_class_cd,job_class_nm,(job_level+0) as srt"
           . " FROM ".XOCP_PREFIX."job_class"
           . " ORDER BY gradeval_bottom DESC,srt DESC";
      $result = $db->query($sql);
      $opt_job_class = "";
      if($db->getRowsNum($result)>0) {
         while(list($job_class_idx,$job_class_cdx,$job_class_nmx)=$db->fetchRow($result)) {
            if($job_class_id==$job_class_idx) {
               $sjob_class = "selected='1'";
            } else {
               $sjob_class = "";
            }
            $opt_job_class .= "<option value='$job_class_idx' ${sjob_class}>$job_class_cdx $job_class_nmx</option>";
         }
      }
                
      $sql = "SELECT workarea_id,workarea_cd,workarea_nm"
           . " FROM ".XOCP_PREFIX."workarea"
           . " ORDER BY workarea_id";
      $result = $db->query($sql);
      $opt_workarea = "";
      if($db->getRowsNum($result)>0) {
         while(list($workarea_idx,$workarea_cdx,$workarea_nmx)=$db->fetchRow($result)) {
            if($workarea_id==$workarea_idx) {
               $sworkarea = "selected='1'";
            } else {
               $sworkarea = "";
            }
            $opt_workarea .= "<option value='$workarea_idx' ${sworkarea}>$workarea_cdx $workarea_nmx</option>";
         }
      }
                
      $sql = "SELECT a.org_id,a.org_cd,a.org_nm,b.org_class_nm"
           . " FROM ".XOCP_PREFIX."orgs a"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . " ORDER BY b.order_no,a.org_nm";
      $result = $db->query($sql);
      $opt_org = "";
      if($db->getRowsNum($result)>0) {
         while(list($org_idx,$org_cdx,$org_nmx,$org_class_nmx)=$db->fetchRow($result)) {
            if($org_id==$org_idx) {
               $sorg = "selected='1'";
            } else {
               $sorg = "";
            }
            $opt_org .= "<option value='$org_idx' ${sorg}>$org_cdx $org_nmx [$org_class_nmx]</option>";
         }
      }
      
      $opt_assessor = $this->getAssessorOptions($job_id);
      
      $ret = $this->renderJob($job_id,$job_nm,$job_abbr,$org_class_nm,$org_nm)
           . "<div id='jobeditor' style='padding:10px;'><form id='frm'><table class='xxfrm' style='width:100%;'><tbody>"
           . "<tr><td>Job Title</td><td><input type='text' value=\"$job_nm\" id='inp_job_nm' name='job_nm' style='width:90%;'/></td></tr>"
           . "<tr><td>Code</td><td><input type='text' value=\"$job_cd\" id='inp_job_cd' name='job_cd' style='width:90px;'/></td></tr>"
           . "<tr><td>Abbreviation</td><td><input type='text' value=\"$job_abbr\" id='inp_job_abbr' name='job_abbr' style='width:90px;'/></td></tr>"
           . "<tr><td>Organization</td><td><select onchange='chorganization(this,event);' id='sorg' name='org_id'>$opt_org</select></td></tr>"
           . "<tr><td>Position Level</td><td><select onchange='chjobclass(this,event);' id='sjob_class' name='job_class_id'>$opt_job_class</select></td></tr>"
           . "<tr><td>Work Area</td><td><select id='sworkarea' name='workarea_id'>$opt_workarea</select></td></tr>"
           . "<tr><td>Assessor's Job</td><td><select id='sassessor' name='assessor_job_id'>$opt_assessor</select></td></tr>"
           . "<tr><td>Job Summary</td><td><div style='height:100px;width:100%;' id='summary'>$summary</div></td></tr>"
           . "<tr><td>Job Description</td><td><div style='height:200px;width:100%;' id='description'>$desc</div></td></tr>"
           
           . "<tr><td colspan='2'><span id='progress'></span><input onclick='save_job();' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input onclick='cancel_edit();' type='button' value='"._CANCEL."'/>&nbsp;&nbsp;"
           . $delete_button
           . "</td></tr>"
           . "</tbody></table></form>";
      
      $sql = "SELECT compgroup_id,compgroup_nm FROM ".XOCP_PREFIX."compgroup ORDER BY compgroup_id";
      $result = $db->query($sql);
      $comp = "";
      $compgroup_array = array();
      if($db->getRowsNum($result)>0) {
         $comp .= "<div style='padding:5px;border:1px solid #aaaaaa;border-top:0px;'>";
         while(list($compgroup_id,$compgroup_nm)=$db->fetchRow($result)) {
            $compgroup_array[] = $compgroup_id;
            $comp .= "<div class='compgroupheader'><table border='0' cellpadding='0' cellspacing='0' style='width:100%;'><tbody>"
                   . "<tr><td>&nbsp;&nbsp;Competency Group : $compgroup_nm</td>"
                   . "<td style='text-align:right;'>Add : <input type='text' style='width:90px;' id='qcompetency_${compgroup_id}'/>"
                   . "&nbsp;<input type='button' value='"._DOWNLOAD."' onclick='dlcompgroup(\"$compgroup_id\",this,event);'/>"
                   . "</td></tr>"
                   . "</tbody></table></div>";
            $sql = "SELECT b.competency_cd,b.competency_abbr,a.competency_id,b.competency_nm,a.rcl,a.itj"
                 . " FROM ".XOCP_PREFIX."job_competency a"
                 . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
                 . " WHERE a.job_id = '$job_id'"
                 . " AND b.compgroup_id = '$compgroup_id'"
                 . " ORDER BY a.itj DESC";
            $rescomp = $db->query($sql);
            $comp .= "<div id='complist_${compgroup_id}'>";
            $comp .= "<div id='compgrouphead_${compgroup_id}'>"
                   . "<table class='rowhead'>"
                   . "<colgroup><col/><col width='40'/><col width='40'/></colgroup>"
                   . "<tbody><tr>"
                   . "<td style='text-align:center;'>Competency</td>"
                   . "<td style='text-align:center;'>RCL</td>"
                   . "<td style='text-align:center;'>ITJ</td>"
                   . "</tr></tbody></table>"
                   . "</div><div id='compgroupitem_${compgroup_id}'>";
            if($db->getRowsNum($rescomp)>0) {
               while(list($competency_cd,$competency_abbr,$competency_id,$competency_nm,$rcl,$itj)=$db->fetchRow($rescomp)) {
                  $comp .= $this->renderCompetency($competency_id,$competency_nm,$competency_abbr,$rcl,$itj);
               }
               $emptyx = "<div id='emptycomp_${compgroup_id}' style='text-align:center;font-style:italic;display:none;'>No Competency.</div>";
            } else {
               $emptyx = "<div id='emptycomp_${compgroup_id}' style='text-align:center;font-style:italic;'>No Competency.</div>";
            }
            $comp .= "</div>$emptyx</div>";
         }
         $comp .= "</div>";
      }
      $comp .= "</div>";
      
           
      return array($ret.$comp,$compgroup_array,$job_id);
   }
   
   function app_Delete($args) {
      $db=&Database::getInstance();
      $job_id = $args[0];
      $sql = "DELETE FROM ".XOCP_PREFIX."jobs WHERE job_id = '$job_id'";
      $db->query($sql);
   }
   
   function renderCompetency($competency_id,$competency_nm,$competency_abbr,$rcl,$itj) {
      $ret = "<div id='dvjobcomp_${competency_id}'>"
           . "<table class='rowlist'>"
           . "<colgroup><col/><col width='40'/><col width='40'/></colgroup>"
           . "<tbody><tr>"
           . "<td>$competency_abbr <span class='xlnk' onclick='editjobcomp(\"$competency_id\",this,event);'>$competency_nm</span></td>"
           . "<td style='text-align:center;' id='rclcomp_${competency_id}'>$rcl</td>"
           . "<td style='text-align:center;' id='itjcomp_${competency_id}'>$itj</td>"
           . "</tr></tbody></table>"
           . "</div>";
      return $ret;
   }
   
   function renderJob($job_id,$job_nm,$job_abbr,$org_class_nm,$org_nm) {
      $ret = "<table style='border:0px;width:100%;'>"
                  . "<colgroup><col width='100'/><col/><col width='200'/></colgroup><tbody>"
                  . "<tr><td>$job_abbr</td>"
                      . "<td><div style='overflow:hidden;width:300px;'><div style='width:900px;'><span id='sp_${job_id}' class='xlnk' onclick='edit_job(\"$job_id\",this,event);'>".htmlentities(stripslashes($job_nm))."</span></div></div></td>"
                      . "<td><div style='overflow:hidden;width:200px;'><div style='width:900px;'>$org_nm [$org_class_nm]</div></div></td>"
                  . "</tr></tbody></table>";
      return $ret;
   }
   
}

} /// HRIS_JOBTITLESAJAX_DEFINED
?>