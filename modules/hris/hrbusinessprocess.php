<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/hrbusinessprocess.php                            //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-07-18                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_HRBUSINESSPROCESS_DEFINED') ) {
   define('HRIS_HRBUSINESSPROCESS_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_HRBusinessProcess extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_HRBUSINESSPROCESS_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_HRBUSINESSPROCESS_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_HRBusinessProcess($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function hrbusinessprocess() {
      switch($_SESSION["lang"]) {
         case "ID":
            //// silakan diganti isi $ret ini dengan versi bahasa indonesia
            $ret = "Hubungan antara tiap prosedur didalam manajemen HR digambarkan sebagai berikut:
            <br/>
            <br/>
            <div style='text-align:center;'>
            <img src='".XOCP_SERVER_SUBDIR."/modules/hris/images/businessprocess.png' style='width:620px;'/></div><p>&nbsp</p>";
            break;
         case "EN":
            //// silakan diganti isi $ret ini dengan versi bahasa inggris
            $ret = "The relationship between every procedure in the HR management can be described as follows:
            <br/>
            <br/>
            <div style='text-align:center;'>
            <img src='".XOCP_SERVER_SUBDIR."/modules/hris/images/businessprocess.png' style='width:620px;'/></div><p>&nbsp</p>";
            break;
      }
      return $ret;
   }
   
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $this->hrbusinessprocess();
            break;
         default:
            $ret = $this->hrbusinessprocess();
            break;
      }
      return $ret;
   }
}

} // HRIS_HRBUSINESSPROCESS_DEFINED
?>