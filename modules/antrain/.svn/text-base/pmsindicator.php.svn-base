<?php
//--------------------------------------------------------------------//
// Filename : modules/pms/pmsindicator.php                            //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-07-18                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('PMS_INDICATOR_DEFINED') ) {
   define('PMS_INDICATOR_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

include_once(XOCP_DOC_ROOT."/modules/pms/pmsxocp.php");

class _pms_Indicator extends XocpBlock {
   var $catchvar = _PMS_CATCH_VAR;
   var $blockID = _PMS_INDICATOR_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _PMS_INDICATOR_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _pms_Indicator($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */

   }
   
   function pmsindicator() {
      if (PMSUnit::$currentId) {
	      return Widget::js(XOCP_SERVER_SUBDIR."/include/calendar.js").PMSIndicator::getEditor(PMSUnit::$currentId);
     }
     else {
          return t_("No current unit assigned, cannot continue!");
     }
   }
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $this->pmsindicator();
            break;
         default:
            $ret = $this->pmsindicator();
            break;
      }
      return $ret;
   }
}

} // PMS_INDICATOR_DEFINED
?>