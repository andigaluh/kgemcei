<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/recalc_assessment.php                      //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-12-18                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_HRRECALCASSESSMENT_DEFINED') ) {
   define('HRIS_HRRECALCASSESSMENT_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
include_once(XOCP_DOC_ROOT."/modules/hris/class/selectasid.php");

class _hris_HRRecalcAssessment extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_ASSESSMENT_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_RECALCASSESSMENT_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function __construct($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function listSession() {
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_recalcass.php");
      $ajax = new _hris_class_RecalculateAssessmentAjax("arc");
      
      $asid = $_SESSION["hris_assessment_asid"];
      
      $js = $ajax->getJs()."<script type='text/javascript'><!--
      
      function recalc() {
         $('recalc_button').style.display = 'none';
         $('confirm_recalc_button').style.display = '';
      }
      
      function cancel_recalc() {
         $('recalc_button').style.display = '';
         $('confirm_recalc_button').style.display = 'none';
      }
      
      function do_recalc() {
         $('recalc_button').style.display = 'none';
         $('confirm_recalc_button').style.display = 'none';
         $('progress').innerHTML = '';
         $('progress').appendChild(progress_span(' ... recalculating'));
         arc_app_recalculate(function(_data) {
            $('recalc_finish').innerHTML = 'Recalculation finished in: '+_data;
            $('recalc_finish').style.display = '';
            $('progress').innerHTML = '';
         });
      }
      
      // --></script>";
      $ret = "<div style='padding:10px;text-align:center;'>"
              . "<div id='recalc_button'>"
                 . "<input style='padding:20px;width:200px;font-size:1.5em;' ".(0?"":"disabled='1'")." type='button' value='Recalculate' onclick='recalc();'/>"
              . "</div>"
              . "<div id='confirm_recalc_button' style='display:none;'>"
                 . "<div style='padding:10px;'>Are you sure you want to recalculate result?</div>"
                 . "<input type='button' style='padding:10px;width:150px;' value='Yes (recalculate)' onclick='do_recalc();'/>&nbsp;"
                 . "<input type='button' style='padding:10px;width:150px;' value='No' onclick='cancel_recalc();'/>"
              . "</div>"
              . "<div id='recalc_finish' style='display:none;padding:10px;'>"
                  . "Recalculation Finished."
              . "</div>"
           . "</div>"
           . "<div id='progress' style='padding:10px;text-align:center;'></div>";
      return $js.$ret;
   }
   
   
   function main() {
      $db = &Database::getInstance();
      
      $asidselobj = new _hris_class_SelectAssessmentSession();
      $asidsel = "<div style='padding-bottom:2px;'>".$asidselobj->show()."</div>";
      
      if(!isset($_SESSION["hris_assessment_asid"])||$_SESSION["hris_assessment_asid"]==0) {
         return $asidsel;
      }
      
      switch ($this->catch) {
         case $this->blockID:
            $ret = $this->listSession();
            break;
         default:
            $ret = $this->listSession();
            break;
      }
      return "<div style='width:900px;'>".$asidsel.$ret."</div>";
      
   }
}

} // HRIS_HRRECALCASSESSMENT_DEFINED
?>