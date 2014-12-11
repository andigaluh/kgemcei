<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/jobtitles.php                            //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-07-18                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_JOBTITLES_DEFINED') ) {
   define('HRIS_JOBTITLES_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_JobTitles extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_JOBTITLES_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_JOBTITLES_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_JobTitles($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function jobtitles() {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_editjobtitles.php");
      $ajax = new _hris_class_EditJobTitlesAjax("ocjx");
      $ajax->setReqPOST();
      
      $sql = "SELECT a.job_id,a.job_cd,a.job_nm,a.description,"
           . "b.org_nm,c.org_class_nm,a.job_abbr,(d.job_level+0) as srt"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."orgs b USING(org_id)"
           . " LEFT JOIN ".XOCP_PREFIX."org_class c USING(org_class_id)"
           . " LEFT JOIN ".XOCP_PREFIX."job_class d ON d.job_class_id = a.job_class_id"
           . " WHERE a.status_cd = 'normal'"
           . " ORDER BY d.gradeval_bottom DESC,srt DESC,a.job_nm";
      $result = $db->query($sql);
      $ret = "<table class='xxlist' style='width:100%;' align='center'>"
           . "<thead><tr><td>"
           . "<span style='float:left;'>Job Titles</span>"
           . "<span style='float:right;'>"
           . "Search : <input type='text' id='qjob' style='width:200px;' value=''/>"
           . "</span></td></tr></thead>"
           . "<tbody><tr><td id='tdjobs'>";
      if($db->getRowsNum($result)>0) {
         while(list($job_id,$job_cd,$job_nm,$description,$org_nm,$org_class_nm,$job_abbr)=$db->fetchRow($result)) {
            $ret .= "<div id='dvjob_${job_id}' class='sb'>"
                  . "<table style='border:0px;width:100%;'>"
                  . "<colgroup><col width='100'/><col/><col width='200'/></colgroup><tbody>"
                  . "<tr><td>$job_abbr</td>"
                      . "<td><div style='overflow:hidden;width:300px;'><div style='width:900px;'>"
                           . "<a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&jobdetail=$job_id'>"
                           . htmlentities(stripslashes($job_nm))."</a></div></div></td>"
                      . "<td><div style='overflow:hidden;width:200px;'><div style='width:900px;'>$org_nm $org_class_nm</div></div></td>"
                  . "</tr></tbody></table>"
                  . "</div>";
         }
      }
      $ret .= "</td></tr>";
      $ret .= "<tr style='display:none;' id='trempty'><td>"._EMPTY."</td></tr>";
      $ret .= "</tbody></table>";
      
      $ret .= $ajax->getJs()."<script type='text/javascript'><!--
      
      var qjob = _gel('qjob');
      qjob._get_param=function() {
         var qval = this.value;
         qval = trim(qval);
         if(qval.length < 2) {
            return '';
         }
         return qval;
      };
      qjob._onselect=function(resId) {
         qjob._reset();
         qjob._showResult(false);
         location.href = '".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&jobdetail='+resId;
      };
      qjob._send_query = ocjx_app_searchJob;
      _make_ajax(qjob);
      qjob.focus();
      
      
      // --></script>";


      return $ret;
   }
   
   function getOrgsUp($org_id) {
      $db=&Database::getInstance();
      $sql = "SELECT parent_id FROM ".XOCP_PREFIX."orgs WHERE org_id = '$org_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($parent_id)=$db->fetchRow($result);
         $sql = "SELECT org_class_id,org_nm FROM ".XOCP_PREFIX."orgs WHERE org_id = '$org_id'";
         $result = $db->query($sql);
         if($db->getRowsNum($result)>0) {
            list($org_class_id,$org_nm)=$db->fetchRow($result);
         }
         if($parent_id>=0) {
            $_SESSION["hris_org_parent"][$org_class_id] = $org_nm;
            $this->getOrgsUp($parent_id);
         }
      }
   }
   
   
   function jobdetail($job_id) {
      global $proficiency_level_name;
      $db=&Database::getInstance();
      $sql = "SELECT b.org_nm,c.org_class_nm,"
           . "a.description,a.job_nm,a.job_cd,a.job_class_id,"
           . "a.workarea_id,a.location_id,a.org_id,a.job_abbr,a.assessor_job_id,"
           . "a.summary,d.job_class_nm,e.workarea_nm,f.job_nm,"
           . "a.summary_id_txt,a.description_id_txt"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."orgs b USING(org_id)"
           . " LEFT JOIN ".XOCP_PREFIX."org_class c USING(org_class_id)"
           . " LEFT JOIN ".XOCP_PREFIX."job_class d ON d.job_class_id = a.job_class_id"
           . " LEFT JOIN ".XOCP_PREFIX."workarea e ON e.workarea_id = a.workarea_id"
           . " LEFT JOIN ".XOCP_PREFIX."jobs f ON f.job_id = a.assessor_job_id"
           . " WHERE a.job_id = '$job_id'";
      $result = $db->query($sql);
      
      $_SESSION["hris_org_parent"] = array();
      
      list($org_nm,$org_class_nm,$description,$job_nm,$job_cd,$job_class_id,$workarea_id,$location_id,
           $org_id,$job_abbr,$assessor_job_id,$summary,$job_class_nm,$workarea_nm,$assessor_job_nm,
           $summary_id_txt,$description_id_txt)=$db->fetchRow($result);
      $job_nm = htmlentities($job_nm,ENT_QUOTES);
      $job_cd = htmlentities($job_cd,ENT_QUOTES);
      $job_abbr = htmlentities($job_abbr,ENT_QUOTES);
      $this->getOrgsUp($org_id);
      
      $sql = "SELECT org_class_id,org_class_nm"
           . " FROM ".XOCP_PREFIX."org_class"
           . " ORDER BY order_no";
      $result = $db->query($sql);
      $xorgs = "";
      if($db->getRowsNum($result)>0) {
         while(list($org_class_idx,$org_class_nmx)=$db->fetchRow($result)) {
            $xorgs .= "<tr><td>$org_class_nmx</td><td>".$_SESSION["hris_org_parent"][$org_class_idx]."</td></tr>";
         }
      }
      
      
      
      $sql = "SELECT a.competency_id,a.rcl,a.itj,b.competency_nm,c.compgroup_nm,"
           . "b.competency_class,(b.competency_class+0) as srt,b.desc_en,b.desc_id"
           . " FROM ".XOCP_PREFIX."job_competency a"
           . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
           . " LEFT JOIN ".XOCP_PREFIX."compgroup c USING(compgroup_id)"
           . " WHERE a.job_id = '$job_id'"
           . " ORDER BY c.compgroup_id,srt";
      $result = $db->query($sql);
      $competency_profile = "";
      $oldgroup = "";
      $oldclass = "";
      $tooltips = "";
      if($db->getRowsNum($result)>0) {
         while(list($competency_id,$rcl,$itj,$competency_nm,$compgroup_nm,$comp_class,$sort,$desc_en,$desc_id)=$db->fetchRow($result)) {
            if($oldgroup!=$compgroup_nm) {
               $competency_profile .= "<tr><td colspan='4' style='vertical-align:top;padding-left:5px;background-color:#eee;border-top:1px solid #efefef;font-weight:bold;'>$compgroup_nm</td></tr>";
               $oldgroup = $compgroup_nm;
               $oldclass = "";
            }
            if($oldclass!=$comp_class) {
               $competency_profile .= "<tr><td style='vertical-align:top;border-top:1px solid #ddd;font-weight:bold;text-align:right;'>".ucfirst($comp_class)."</td>";
               $oldclass = $comp_class;
               $border_top = "border-top:1px solid #ddd;";
            } else {
               $competency_profile .= "<tr><td>&nbsp;</td>";
               $border_top = "";
            }
            $sql = "SELECT behaviour_en_txt,behaviour_id_txt,proficiency_lvl"
                 . " FROM ".XOCP_PREFIX."compbehaviour"
                 . " WHERE competency_id = '$competency_id'"
                 . " AND proficiency_lvl <= '$rcl'"
                 . " ORDER BY proficiency_lvl DESC";
            $rbh = $db->query($sql);
            $bhvx = "<div style='padding:2px;padding-left:30px;'><div style='text-align:center;padding:2px;background-color:#eef;'>Behaviour Indicators:</div>";
            if($db->getRowsNum($rbh)>0) {
               $bhvx .= "<table class='xxlist' style='font-size:0.9em;width:100%;'><tbody>";
               $no = 1;
               $old_level = 0;
               while(list($bh_en,$bh_id,$plvl)=$db->fetchRow($rbh)) {
                  if($old_level!=$plvl) {
                     $bhvx .= "<tr><td colspan='2' style='text-align:center;padding:2px;font-weight:bold;'>Level $plvl - $proficiency_level_name[$plvl]</td></tr>";
                     $old_level = $plvl;
                     $no = 1;
                  }
                  $bhvx .= "<tr><td style='text-align:center;padding:3px;'>$no.</td><td>$bh_en<hr noshade='1' size='1' color='#eeeeee'/><span style='font-style:italic;'>$bh_id</span></td></tr>";
                  $no++;
               }
               $bhvx .= "</tbody></table>";
            }
            $bhvx .= "</div>";
            $competency_profile .= "<td class='tdcomp' style='${border_top};border-left:1px solid #ddd;cursor:pointer;' onclick='toggleBehaviour(\"$competency_id\");' id='comp_${competency_id}'>$competency_nm<div id='bhv_${competency_id}' style='display:none;'>$bhvx</div></td>"
                                 . "<td style='border-left:1px solid #ddd;${border_top};text-align:center;vertical-align:top;'>$rcl</td>"
                                 . "<td style='border-left:1px solid #ddd;${border_top};text-align:center;vertical-align:top;'>$itj</td></tr>";
            $tooltips .= "\nnew Tip('comp_${competency_id}', \"".addslashes($desc_en)."<hr noshade='1' size='1' color='#dddddd'/><span style='font-style:italic;'>".addslashes($desc_id)."</span>\", {title:'Description',width:350,style:'emp'});";
         }
      }
      $ret = "<script type='text/javascript' src='".XOCP_SERVER_SUBDIR."/include/prototip2.0.5/js/prototip.js'></script>"
           . "<link rel='stylesheet' type='text/css' href='".XOCP_SERVER_SUBDIR."/include/prototip2.0.5/css/prototip.css' />";
      
      $ret .= "<div id='jobviewer' style='padding:10px;'><table class='xxfrm' style='width:100%;'>"
           . "<colgroup><col width='140'/><col/></colgroup>"
           . "<tbody>"
           . "<tr><td style='background-color:#ffffff;padding:6px;font-weight:bold;border-right:1px solid #dddddd;'>Job Title</td>"
               . "<td style='background-color:#ffffff;padding:6px;font-weight:bold;'><span style='float:left;'>$job_nm</span>"
                  . "<span style='float:right;font-weight:normal;'>[<a href='".XOCP_SERVER_SUBDIR."/index.php'>back</a>]</span></td></tr>"
           . "<tr><td>Abbreviation</td><td>$job_abbr</td></tr>"
           // . "<tr><td colspan='2' style='font-weight:bold;text-align:center;'>Organization</td></td></tr>"
           . $xorgs
           . "<tr><td>Position Level</td><td>$job_class_nm</td></tr>"
           . "<tr><td>Work Area</td><td>$workarea_nm</td></tr>"
           . "<tr><td>Accountability</td><td>$assessor_job_nm</td></tr>"
           . "</tbody></table>";
      
      $ret .= "<div style='border:1px solid #999999;padding:4px;margin-top:4px;'>"
            . "<div style='background-color:#eeeeee;border:1px solid #999999;padding:2px;text-align:center;font-weight:bold;'>Position Structure</div>"
            . "<div style='text-align:center;padding:5px;'><img src='".XOCP_SERVER_SUBDIR."/modules/hris/img_position_structure.php?job=${job_id}'/></div>"
            . "</div>";
            
      $ret .= "<div style='border:1px solid #999999;padding:4px;margin-top:4px;'>"
            . "<span style='float:right;margin-top:3px;padding-left:10px;'>"
            . "[<span onclick='switch_summary(\"en\");' class='xlnk'>English</span>]&nbsp;"
            . "[<span onclick='switch_summary(\"id\");' class='xlnk'>Indonesian</span>]&nbsp;"
            . "</span>"
            . "<div style='background-color:#eeeeee;border:1px solid #999999;padding:2px;text-align:center;font-weight:bold;'>Summary of Job Responsibility</div>"
            . "<div style='padding:5px;' id='summary_en_txt'>$summary</div>"
            . "<div style='display:none;padding:5px;' id='summary_id_txt'>$summary_id_txt</div>"
            . "</div>";
            
      $ret .= "<div style='border:1px solid #999999;padding:4px;margin-top:4px;'>"
            . "<span style='float:right;margin-top:3px;padding-left:10px;'>"
            . "[<span onclick='switch_description(\"en\");' class='xlnk'>English</span>]&nbsp;"
            . "[<span onclick='switch_description(\"id\");' class='xlnk'>Indonesian</span>]&nbsp;"
            . "</span>"
            . "<div style='background-color:#eeeeee;border:1px solid #999999;padding:2px;text-align:center;font-weight:bold;'>Duties and Responsibilities</div>"
            . "<div style='padding:5px;' id='description_en_txt'>$description</div>"
            . "<div style='display:none;padding:5px;' id='description_id_txt'>$description_id_txt</div>"
            . "</div>";
            
      $ret .= "<div style='border:1px solid #999999;padding:4px;margin-top:4px;'>"
            . "<div style='background-color:#eeeeee;border:1px solid #999999;padding:2px;text-align:center;font-weight:bold;'>Competency Profile</div>"
            . "<div style='padding:5px;'><table border='0' style='width:100%;' class='comp_profile'>"
                  . "<thead><tr><td colspan='2' style='padding:4px;background-color:#dddddd;color:#444444;text-align:center;font-weight:bold;'>Required Competency</td>"
                             . "<td style='padding:4px;background-color:#dddddd;color:#444444;text-align:center;font-weight:bold;' title='Required Competency Level'>RCL</td>"
                             . "<td style='padding:4px;background-color:#dddddd;color:#444444;text-align:center;font-weight:bold;' title='Importance to Job'>ITJ</td></tr></thead>"
                  . "<tbody>$competency_profile</tbody></table></div>"
            . "</div><div style='height:100px;'>&nbsp;</div>";
      return $ret."<script type='text/javascript'><!--\n$tooltips\n
      
      function switch_summary(l) {
         if(l=='en') {
            var dv = $('summary_en_txt');
            if(dv.style.display=='') {
               return;
            }
            $('summary_id_txt').style.display = 'none';
            new Effect.Appear('summary_en_txt',{duration:0.5});
         } else {
            var dv = $('summary_id_txt');
            if(dv.style.display=='') {
               return;
            }
            $('summary_en_txt').style.display = 'none';
            new Effect.Appear('summary_id_txt',{duration:0.5});
         
         }
      }
      
      function switch_description(l) {
         if(l=='en') {
            var dv = $('description_en_txt');
            if(dv.style.display=='') {
               return;
            }
            $('description_id_txt').style.display = 'none';
            new Effect.Appear('description_en_txt',{duration:0.5});
         } else {
            var dv = $('description_id_txt');
            if(dv.style.display=='') {
               return;
            }
            $('description_en_txt').style.display = 'none';
            new Effect.Appear('description_id_txt',{duration:0.5});
         
         }
      }
      
      function toggleBehaviour(competency_id) {
         new Effect.toggle($('bhv_'+competency_id),'appear',{duration:0.5,afterFinish:function(o) {
         }}); 
      }
      
      // --></script>";
   }
   
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            if(isset($_GET["jobdetail"])&&$_GET["jobdetail"]>0) {
               $ret = $this->jobdetail($_GET["jobdetail"]);
            } else {
               $ret = $this->jobtitles();
            }
            break;
         default:
            $ret = $this->jobtitles();
            break;
      }
      return $ret;
   }
}

} // HRIS_JOBTITLES_DEFINED
?>