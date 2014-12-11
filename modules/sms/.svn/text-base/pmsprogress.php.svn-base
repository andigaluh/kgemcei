<?php
//--------------------------------------------------------------------//
// Filename : modules/pms/pmsprogress.php                          //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-07-18                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('PMS_PROGRESS_DEFINED') ) {
   define('PMS_PROGRESS_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

include_once(XOCP_DOC_ROOT."/modules/pms/pmsxocp.php");

class _pms_Progress extends XocpBlock {
   var $catchvar = _PMS_CATCH_VAR;
   var $blockID = _PMS_PROGRESS_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _PMS_PROGRESS_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _pms_Progress($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */

   }
   
   function pmsprogress() {
      if (PMSUnit::$currentId && PMSPerson::$currentId) {
	      return Widget::js(XOCP_SERVER_SUBDIR."/include/calendar.js").PMSProgress::getEditor(PMSUnit::$currentId, PMSPerson::$currentId);
     }
     else {
          return t_("No current unit and person assigned, cannot continue!");
     }
   }
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $this->pmsprogress();
            break;
         default:
            $ret = $this->pmsprogress();
            break;
      }
      return $ret;
   }
}

} // PMS_PROGRESS_DEFINED
?>