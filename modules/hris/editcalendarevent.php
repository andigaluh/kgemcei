<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/editcalendarevent.php                   //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2011-06-15                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_EDITCALENDAREVENT_DEFINED') ) {
   define('HRIS_EDITCALENDAREVENT_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_EditCalendarEvent extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_EDITCALENDAREVENT_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_EDITCALENDAREVENT_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_EditCalendarEvent($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function listEvent() {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_editcalendarevent.php");
      $ajax = new _hris_class_EditCalendarEventAjax("ocjx");
      if(!isset($_SESSION["cal_event_year"])) {
         $_SESSION["cal_event_year"] = date("Y");
      }
      
      $current_year = $_SESSION["cal_event_year"];
      
      $js = $ajax->getJs()."<script type='text/javascript'><!--
      
      function prev_year() {
         ocjx_app_previousYear(function(_data) {
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?u='+_data;
         });
      }
      
      function next_year() {
         ocjx_app_nextYear(function(_data) {
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?u='+_data;
         });
      }
      
      function current_year() {
         ocjx_app_currentYear(function(_data) {
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?u='+_data;
         });
      }
      
      // --></script>";
      
      $ret = "<div id='dv_event'>"
           . "<div style='padding:5px;text-align:center;border:1px solid #bbb;background-color:#eef;'>"
           . "<span onclick='prev_year();' style='cursor:pointer;'>"
           . "<img src='".XOCP_SERVER_SUBDIR."/images/prev.gif'/>"
           . "<img src='".XOCP_SERVER_SUBDIR."/images/prev.gif'/>"
           . "</span>"
           . "&nbsp;"
           . "<span class='xlnk' onclick='current_year();'>$current_year</span>"
           . "&nbsp;"
           . "<span onclick='next_year();' style='cursor:pointer;'>"
           . "<img src='".XOCP_SERVER_SUBDIR."/images/next.gif'/>"
           . "<img src='".XOCP_SERVER_SUBDIR."/images/next.gif'/>"
           . "</span>"
           . "</div>"
           . "</div>";
      return $js.$ret;
   }
   
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            if($_GET["prev_year"]==1) {
               $_SESSION["cal_event_year"]--;
               $ret = $this->listEvent();
            } else if($_GET["next_year"]==1) {
               $_SESSION["cal_event_year"]++;
               $ret = $this->listEvent();
            } else {
               $ret = $this->listEvent();
            }
            break;
         default:
            $ret = $this->listEvent();
            break;
      }
      return $ret."<div style='height:100px;'>&nbsp;</div>";
   }
}

} // HRIS_EDITCALENDAREVENT_DEFINED
?>
