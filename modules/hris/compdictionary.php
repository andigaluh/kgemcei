<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/compdictionary.php                            //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-07-18                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_COMPDICTIONARY_DEFINED') ) {
   define('HRIS_COMPDICTIONARY_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_CompetencyDictionary extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_COMPDICTIONARY_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_COMPDICTIONARY_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_CompetencyDictionary($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function compdictionary() {
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_editjobtitles.php");
      $ajax = new _hris_class_EditJobTitlesAjax("ocjx");
      $ajax->setReqPOST();
      $db=&Database::getInstance();
      $sql = "SELECT b.competency_id,b.competency_nm,c.compgroup_nm,b.competency_class,(b.competency_class+0) as srt,b.competency_abbr"
           . " FROM ".XOCP_PREFIX."competency b"
           . " LEFT JOIN ".XOCP_PREFIX."compgroup c USING(compgroup_id)"
           . " ORDER BY c.compgroup_id,srt";
      $result = $db->query($sql);
      $competency_profile = "";
      $oldgroup = "";
      $oldclass = "";
      if($db->getRowsNum($result)>0) {
         while(list($competency_id,$competency_nm,$compgroup_nm,$comp_class,$x,$abbr)=$db->fetchRow($result)) {
            if($oldgroup!=$compgroup_nm) {
               $competency_profile .= "<tr><td colspan='3' style='padding:10px;background-color:#dddddd;font-weight:bold;text-align:center;'>$compgroup_nm</td></tr>";
               $oldgroup = $compgroup_nm;
               $oldclass = "";
            }
            if($oldclass!=$comp_class) {
               $competency_profile .= "<tr><td style='background-color:#f2f2f2;font-weight:bold;text-align:left;padding:5px;' colspan='3'>".ucfirst($comp_class)."</td>"
                                    //. "<td colspan='2' style='background-color:#eeeeee;'>&nbsp;</td>"
                                    . "</tr>";
               $oldclass = $comp_class;
            }
            $competency_profile .= "<tr><td style='text-align:right;padding-right:4px;'>$abbr</td>"
                                 . "<td><a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&compdetail=$competency_id'>$competency_nm</a></td></tr>";
         }
      }
      
      $ret = "<div style='border:1px solid #ddd;padding:4px;margin-top:4px;'>"
            . "<div style='text-align:right;background-color:#ddd;padding:3px;'>"
           . "Search : <input type='text' id='qcomp' style='width:200px;' value=''/>"
            . "</div>"
            . "<div style='padding:5px;'>Untuk melihat detil definisi, klik masing-masing kompetensi yang diinginkan.<hr noshade='1' size='1'/>"
            . "<span style='font-style:italic;'>To see more detail about definition, please click the desired competency.</span></div>"
            . "<div style='padding:5px;'><table border='0' style='width:100%;' cellpadding='2' cellspacing='0'>"
                  . "<tbody>$competency_profile</tbody></table></div>"
            . "</div><div style='height:100px;'>&nbsp;</div>";

      $ret .= $ajax->getJs()."<script type='text/javascript'><!--
      
      var qcomp = _gel('qcomp');
      qcomp._get_param=function() {
         var qval = this.value;
         qval = trim(qval);
         if(qval.length < 2) {
            return '';
         }
         return qval;
      };
      qcomp._onselect=function(resId) {
         qcomp._reset();
         qcomp._showResult(false);
         location.href = '".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&compdetail='+resId;
      };
      qcomp._send_query = ocjx_app_searchCompetency;
      _make_ajax(qcomp);
      qcomp.focus();
      
      
      // --></script>";

      return $ret;
   }
   
   function compdetail($competency_id) {
      $db=&Database::getInstance();
      global $proficiency_level_name;
      $sql = "SELECT a.competency_nm,a.competency_abbr,a.competency_cd,b.compgroup_nm,a.competency_class,a.desc_en,a.desc_id"
           . " FROM ".XOCP_PREFIX."competency a"
           . " LEFT JOIN ".XOCP_PREFIX."compgroup b USING(compgroup_id)"
           . " WHERE a.competency_id = '$competency_id'";
      $result = $db->query($sql);
      list($competency_nm,$abbr,$cd,$group,$class,$desc_en,$desc_id)=$db->fetchRow($result);
      $ret = "<div><table width='100%'><tr><td style='font-size:1.4em;font-weight:bold;color:#333333;'>$competency_nm ($abbr)</td><td style='text-align:right;'>"
           . "[<a href='".XOCP_SERVER_SUBDIR."/index.php'>back</a>]</td></tr></table></div>";
      $sql = "SELECT behaviour_id,behaviour_en_txt,behaviour_id_txt,proficiency_lvl"
           . " FROM ".XOCP_PREFIX."compbehaviour"
           . " WHERE competency_id = '$competency_id'"
           . " ORDER BY proficiency_lvl DESC,behaviour_id";
      $result = $db->query($sql);
      $oldlvl = -1;
      $bhv = "";
      $no=1;
      if($db->getRowsNum($result)>0) {
         while(list($bid,$en,$id,$lvl)=$db->fetchRow($result)) {
            if($lvl!=$oldlvl) {
               if($oldlvl>=0) {
                  $bhv .= "<tbody></table></td></tr>\n";
               }
               $oldlvl = $lvl;
               $bhv .= "<tr><td style='vertical-align:middle;text-align:center;font-weight:bold;'>$lvl</td>"
                     . "<td style='vertical-align:middle;font-weight:bold;'>".$proficiency_level_name[$lvl]."</td>"
                     . "<td style='padding:0px;'>";
               $no=1;
               $bhv .= "<table style='width:100%;margin:0px;border-spacing:0px;'><colgroup><col width='30'/><col/></colgroup><tbody>";
            }
            $bhv .= "<tr><td style='vertical-align:middle;text-align:center;border-top:".($no==1?"0":"1")."px solid #bbb;border-right:1px solid #bbb;padding:2px;'>$no</td>"
                  . "<td style='border-top:".($no==1?"0":"1")."px solid #bbb;padding:10px;'><span style=''>$en<hr style='margin:2px;' noshade='1' size='1' color='#bbbbbb'/><span style='font-style:italic;'>$id</span></td></tr>";
            $no++;
            
         }
         $bhv .= "</tbody></table><tr><td style='font-weight:bold;text-align:center;'>0</td>"
               . "<td style='font-weight:bold;'>".$proficiency_level_name[0]."</td>"
               . "<td></td></tr>";
      }
      $ret .= "<div style='margin-top:10px;margin-bottom:100px;'><table class='compdict' style='width:100%;'>"
            . "<colgroup><col width='30'/><col width='100'/><col/></colgroup>"
            . "<thead><tr><td colspan='3' style='text-align:center;'>Description</td></tr></thead>"
            . "<tbody><tr><td colspan='3' style='padding:4px;'>$desc_en<hr noshade='1' size='1' color='#bbbbbb'/><span style='font-style:italic;'>$desc_id</span></td></tr></tbody>"
            . "<thead><tr><td colspan='2' style='text-align:center;border-right:1px solid #bbb;'>Level</td>"
            . "<td style='text-align:center;'>Behaviour Indicator</td></tr></thead>"
            . "$bhv</table></div>";
      return $ret;
   }
   
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            if(isset($_GET["compdetail"])&&$_GET["compdetail"]>0) {
               $ret = $this->compdetail($_GET["compdetail"]+0);
            } else {
               $ret = $this->compdictionary();
            }
            break;
         default:
            $ret = $this->compdictionary();
            break;
      }
      return $ret;
   }
}

} // HRIS_COMPDICTIONARY_DEFINED
?>