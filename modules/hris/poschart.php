<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/jobchart.php                                //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-07-18                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_ORGCHART_DEFINED') ) {
   define('HRIS_ORGCHART_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
include_once(XOCP_DOC_ROOT."/modules/hris/class/mydiagram.php");

class _hris_PositionChart extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_ORGCHART_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = "Position Structure";
   var $display_comment = TRUE;
   var $data;
   
   function _hris_PositionChart($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function diagram($arr=NULL) {
      $db=&Database::getInstance();
      
      if($_GET["job"]) {
         $job_id = $_GET["job"];
      } else {
         $job_id = NULL;
      }
      
      $filename = "jobstruct_${job_id}.png";
      $_SESSION["hris_nodes"] = array();
      $xx = new DiagramX($job_id);
      $xx->render(XOCP_DOC_ROOT."/tmp/${filename}");
      
      $dvjs = "";
      foreach($_SESSION["hris_nodes"] as $k=>$node) {
         $x = $node->x;
         $y = $node->y;
         $w = $node->w;
         $h = $node->h;
         $id = $node->id;
         $dvjs .= "
         dv = _dce('div');
         dv.setAttribute('style','width:${w}px;height:${h}px;border:0px;opacity:0;position:absolute;cursor:pointer;background-color:#444;');
         dv.style.left = (ximg+${x})+'px';
         dv.style.top = (yimg+${y})+'px';
         dv.setAttribute('title','Click to expand');
         dv.onclick=function(e) {
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&job=${id}';
         };
         $('chartimg').parentNode.appendChild(dv);
         ";
      }
      
      
      $js = "<script type='text/javascript'><!--
      
      function set_over_div() {
         var ximg = oX($('chartimg'));
         var yimg = oY($('chartimg'));
         var dv = null;
         ${dvjs}
      }
      
      setTimeout('set_over_div()',500);
      
      // --></script>";
      
      //return $js."<br/><div style='text-align:center;'><img id='chartimg' src='".XOCP_SERVER_SUBDIR."/tmp/${filename}'/></div><div id='coord'></div><br/><br/>";
      return $js."<br/>Dibawah ini adalah struktur level posisi pekerjaan di perusahaan.
      Untuk melihat detil posisi silakan klik di kotak yang diinginkan.
      <hr noshade='1' size='1'/>
      <span style='font-style:italic;'>This below is corporate position level structure. To see more detail position please click the desired box.</span>
      <br/>&nbsp;<div style='text-align:center;'><img id='chartimg' src='".XOCP_SERVER_SUBDIR."/tmp/${filename}'/></div><div id='coord'></div><br/><br/>";
   
   }
   
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            if(isset($_GET["job"])&&$_GET["job"]>0) {
               $ret = $this->diagram($arr);
            } else {
               $ret = $this->diagram($arr);
            }
            break;
         default:
            $ret = $this->diagram($arr);
            break;
      }
      return $ret;
   }
}

} // HRIS_ORGCHART_DEFINED

