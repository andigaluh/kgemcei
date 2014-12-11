<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/posmatrix.php                            //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-07-18                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_POSMATRIX_DEFINED') ) {
   define('HRIS_POSMATRIX_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_PositionMatrix extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_POSMATRIX_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_POSMATRIX_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_PositionMatrix($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   function posmatrix() {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_posmatrix.php");
      $ajax = new _hris_class_PositionMatrixAjax("psm");
      
      if(!isset($_SESSION["hris_posmatrix_division"])) {
         $_SESSION["hris_posmatrix_division"] = 0;
      }
      
      if(!isset($_SESSION["hris_posmatrix_subdivision"])) {
         $_SESSION["hris_posmatrix_subdivision"] = 0;
      }
      
      /// DIVISION SELECT
      $sql = "SELECT org_id,org_nm,org_abbr FROM ".XOCP_PREFIX."orgs WHERE org_class_id = '3'";
      $result = $db->query($sql);
      $optdiv = "";
      if($db->getRowsNum($result)>0) {
         while(list($org_id,$org_nm,$org_abbr)=$db->fetchRow($result)) {
            if($_SESSION["hris_posmatrix_division"]==0) {
               $_SESSION["hris_posmatrix_division"] = $org_id;
            }
            $optdiv .= "<option value='$org_id' ".($org_id==$_SESSION["hris_posmatrix_division"]?"selected='1'":"").">$org_nm</option>";
         }
      }
      
      $division_id = $_SESSION["hris_posmatrix_division"];
      
      /// SUBDIVISION SELECT
      $_SESSION["hris_subdiv"] = array();
      $ajax->recurseDivision($division_id);
      ksort($_SESSION["hris_subdiv"]);
      $optsubdiv = "<option value='0'>All</option>";
      foreach($_SESSION["hris_subdiv"] as $org_class_idx=>$orgs) {
         foreach($orgs as $org_idx=>$v) {
            list($org_id,$org_nm,$org_abbr,$org_class_nm)=$v;
            $optsubdiv .= "<option value='$org_id' ".($org_id==$_SESSION["hris_posmatrix_subdivision"]?"selected='1'":"").">$org_nm $org_class_nm</option>";
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
             . "<tr><td>Division :</td><td><select id='seldivision' onchange='set_pos();'>$optdiv</select></td></tr>"
             . "<tr><td>Section/Unit :</td><td><select id='selsubdivision' onchange='set_pos()'>$optsubdiv</select></td></tr>"
             . "<tr><td>Position Level :</td><td><select id='selposlevel' onchange='set_pos()'>$optlevel</select></td></tr>"
             //. "<tr><td colspan='2'><input type='button' value='Submit'/></td></tr>"
             . "</tbody></table>";
      
      $ret = "<script type='text/javascript' src='".XOCP_SERVER_SUBDIR."/include/prototip2.0.5/js/prototip.js'></script>"
           . "<link rel='stylesheet' type='text/css' href='".XOCP_SERVER_SUBDIR."/include/prototip2.0.5/css/prototip.css'/>";
      $ret .= "<table style='border-spacing:0px;width:100%;'>"
           . "<tbody><tr><td style='background-color:#fff;padding:0px;'>$query</td></tr>"
           . "<tr><td style='background-color:#fff;border:1px solid #bbb;border-top:0px;padding:10px;' id='matrix_content'>&nbsp;</td></tr>"
           . "</tbody></table><div style='padding:10px;'>&nbsp;</div>";
      
      
      $_SESSION["html"]->addHeadScript("<script type='text/javascript'><!--
         function scrolldv(d,e) {
            var l = d.scrollLeft;
            var t = d.scrollTop;
            $('dvscrolljob').scrollTop = t;
            $('dvcomptitle').scrollLeft = l;
         }
         function hris_posmatrix_load() {
            var division = $('seldivision').options[$('seldivision').selectedIndex].value;
            var subdivision = $('selsubdivision').options[$('selsubdivision').selectedIndex].value;
            var poslevel = $('selposlevel').options[$('selposlevel').selectedIndex].value;
            psm_app_loadMatrix(division,subdivision,poslevel,function(_data) {
               var data = recjsarray(_data);
               $('matrix_content').innerHTML = data[1];
               //setTooltips(data[0]);
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
            var division = $('seldivision').options[$('seldivision').selectedIndex].value;
            var subdivision = $('selsubdivision').options[$('selsubdivision').selectedIndex].value;
            var poslevel = $('selposlevel').options[$('selposlevel').selectedIndex].value;
            psm_app_setPosition(division,subdivision,poslevel,function(_data) {
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
            psm_app_setCompetencyClass(classx,function(_data) {
               hris_posmatrix_load();
            });
         }
         
         function change_group(groupx) {
            psm_app_setCompetencyGroup(groupx,function(_data) {
               hris_posmatrix_load();
            });
         }
         
      // --></script>";
      
      return $ret.$js.$ajax->getJs();
      
   }
   
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $this->posmatrix();
            break;
         default:
            $ret = $this->posmatrix();
            break;
      }
      return $ret;
   }
}

} // HRIS_POSMATRIX_DEFINED
?>