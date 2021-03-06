<?php
//--------------------------------------------------------------------//
// Filename : modules/pms/pmsaction.php                               //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-07-18                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('PMS_ACTION_DEFINED') ) {
   define('PMS_ACTION_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

include_once(XOCP_DOC_ROOT."/modules/pms/pmsxocp.php");

class _pms_Action extends XocpBlock {
   var $catchvar = _PMS_CATCH_VAR;
   var $blockID = _PMS_ACTION_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _PMS_ACTION_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _pms_Action($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */

   }
   
   function pmsaction() {
      if (PMSUnit::$currentId) {
	      return Widget::js(XOCP_SERVER_SUBDIR."/include/calendar.js").PMSAction::getEditor(PMSUnit::$currentId);
     }
     else {
          return t_("No current unit assigned, cannot continue!");
     }
   }
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $this->pmsaction();
            break;
         default:
            $ret = $this->pmsaction();
            break;
      }
      return $ret;
   }
}

} // PMS_ACTION_DEFINED
?>