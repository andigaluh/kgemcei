<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/compmodel.php                            //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-07-18                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_COMPMODEL_DEFINED') ) {
   define('HRIS_COMPMODEL_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_CompetencyModel extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_COMPMODEL_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_COMPMODEL_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_CompetencyModel($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function compmodel() {
      switch($_SESSION["lang"]) {
         case "ID":
            $ret = "
	  <p align=\"justify\">
	  Sesuai visi, misi, nilai dan strategi perusahaan, kompetensi model yang diterapkan di MCCI dapat digambarkan sebagai berikut 
      </p>
	  <div align=\"center\">
<img src=\"".XOCP_SERVER_SUBDIR."/modules/hris/images/mcci-kompetensi.jpg\">
</div>
<p>&nbsp;</p>";
            break;
         case "EN":
            $ret = "
	  <p align=\"justify\">
	  In accordance to the visions, missions, values and strategies of the company, competency model applied at MCCI can be described as follows:
      </p>
	  <div align=\"center\">
<img src=\"".XOCP_SERVER_SUBDIR."/modules/hris/images/mcci-kompetensi.jpg\">
</div>
<p>&nbsp;</p>";
            break;
      }
      return $ret;
   }
   
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $this->compmodel();
            break;
         default:
            $ret = $this->compmodel();
            break;
      }
      return $ret;
   }
}

} // HRIS_COMPMODEL_DEFINED
?>