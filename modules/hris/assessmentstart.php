<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/assessmentstart.php                        //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-07-18                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_ASSESSMENTPROC_DEFINED') ) {
   define('HRIS_ASSESSMENTPROC_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_AssessmentStart extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_ASSESSMENTPROC_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_ASSESSMENTSTART_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_AssessmentStart($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function assessmentstart() {
      return "
      <p>
      Jika Anda seorang atasan (minimal level supervisor) untuk menilai kompetensi bawahan Anda, atau Anda ditunjuk
      untuk menjadi assessor untuk menilai kompetensi atasan atau rekan kerja Anda, maka silakan  
      <a href='".XOCP_SERVER_SUBDIR."/index.php?XP_login_menu=0'>login</a>.
      </p>
      <p>
      Username dan password Anda akan dibagikan oleh seksi HR.
      </p>
      <hr noshade='1' size='1'/>
      <p style='font-style:italic;'>
      If you are a superior (supervisor level up) to assess your subordinate's competency, or you are appointed
      as assessor to assess competency of your superior or peers, please
      <a href='".XOCP_SERVER_SUBDIR."/index.php?XP_login_menu=0'>login</a>.
      </p>
      <p style='font-style:italic;'>
      Your username and password will be given by HR section.
      </p>
      <br/><br/>
      <table align='center' style='border:1px solid black;'><tbody><tr><td style='padding:10px;font-weight:bold;font-size:2em;'>
      <a href='".XOCP_SERVER_SUBDIR."/index.php?XP_login_menu=0'>Login</a>.
      </td></tr></tbody></table>
      ";
   }
   
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $this->assessmentstart();
            break;
         default:
            $ret = $this->assessmentstart();
            break;
      }
      return $ret;
   }
}

} // HRIS_ASSESSMENTPROC_DEFINED
?>