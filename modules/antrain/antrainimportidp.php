<?php
//--------------------------------------------------------------------//
// Filename : modules/antrain/antrainimportidp.php                     //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2014-09-16                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('ANTRAIN_ANTRAINIMPORTIDP_DEFINED') ) {
   define('ANTRAIN_ANTRAINIMPORTIDP_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/antrain/modconsts.php");

class _antrain_ANTRAINImportIDP extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _ANTRAIN_ANTRAINIMPORTIDP_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = 'Import IDP to Antrain Plan Specific';
   var $display_comment = TRUE;
   var $data;
   
   function __construct($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function listSession() {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/antrain/class/ajax_antrainimportidp.php");
      $ajax = new _antrain_class_ANTRAINImportIDPAjax("psjx");
	  
	   $ret = "<input onclick='do_import_idp();' type='button' value='Start Import'/>";

      $ret .= "<div></div>";
      
      return $ret.$ajax->getJs()."<script src='".XOCP_SERVER_SUBDIR."/include/calendar.js' type='text/javascript'></script><script type='text/javascript'><!--
      
   
      
      function do_import_idp() {
         psjx_app_importidp();
         alert('OK!');
      }
      
      
      // --></script>";
   }
   
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $this->listSession();
            break;
         default:
            $ret = $this->listSession();
            break;
      }
      return $ret;
   }
}

} // ANTRAIN_ANTRAINSESSION_DEFINED
?>