<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/pages_editor.php                           //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_PAGESEDITOR_DEFINED') ) {
   define('HRIS_PAGESEDITOR_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_PagesViewer extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_PAGESEDITOR_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = "";
   var $display_comment = TRUE;
   var $data;
   
   function _hris_PagesViewer($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   
   function main() {
      $db = &Database::getInstance();
      if(isset($_GET["page_id"])) {
         $page_id = $_GET["page_id"];
      } else {
         $page_id = 2;
      }
      
      $sql = "SELECT page_title,page_content FROM ".XOCP_PREFIX."static_pages WHERE page_id = '$page_id'";
      $result = $db->query($sql);
      list($page_title,$page_content)=$db->fetchRow($result);
      $page_title = htmlentities($page_title,ENT_QUOTES);
      
      $this->title = $page_title;
      
      return $page_content;
   }
}

} // HRIS_PAGESEDITOR_DEFINED
?>