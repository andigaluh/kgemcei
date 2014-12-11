<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/hrvision.php                                //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-07-18                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_HRVISION_DEFINED') ) {
   define('HRIS_HRVISION_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_HRVision extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_HRVISION_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_HRVISION_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_HRVision($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function hrvision() {
      return "Vision ...
      
      ";
   }
   
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $this->hrvision();
            break;
         default:
            $ret = $this->hrvision();
            break;
      }
      return $ret;
   }
}

} // HRIS_HRVISION_DEFINED
?>