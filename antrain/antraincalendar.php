<?php
//--------------------------------------------------------------------//
// Filename : modules/antrain/antraincalendar.php                     //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2014-09-16                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('ANTRAIN_ANTRAINCALENDAR_DEFINED') ) {
   define('ANTRAIN_ANTRAINCALENDAR_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/antrain/modconsts.php");

class _antrain_ANTRAINCalendar extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _ANTRAIN_ANTRAINCALENDAR_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = 'Calendar';
   var $display_comment = TRUE;
   var $data;
   
   function __construct($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function displayCalendar() {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/antrain/class/ajax_antraincalendar.php");
      $ajax = new _antrain_class_ANTRAINCalendarAjax("psjx");

      $_SESSION["html"]->addStylesheet("<link rel='stylesheet' type='text/css' href='include/fullcalendar/fullcalendar.css' />");

      $ret = "<div id='calendar'></div>";
	   
      return $ret.$ajax->getJs()."
      <script type='text/javascript' src='".XOCP_SERVER_SUBDIR."/include/jquery-1.11.1.min.js'></script>
      <script type='text/javascript' src='".XOCP_SERVER_SUBDIR."/include/moment.min.js'></script>
      <script type='text/javascript' src='".XOCP_SERVER_SUBDIR."/include/fullcalendar/fullcalendar.js'></script>
      <script src='".XOCP_SERVER_SUBDIR."/include/calendar.js' type='text/javascript'></script>
      <script type='text/javascript'><!--


      var j = jQuery.noConflict();



      j(document).ready(function() {

       // page is now ready, initialize the calendar...

       j('#calendar').fullCalendar({
            editable:true,
            events: '".XOCP_SERVER_SUBDIR."/ajaxreq.php?ac=psjx&ff=app_renderTraining&ffrnd=1417590608014',
            eventClick: function(calEvent, jsEvent, view) {

                 eventdetail = _dce('div');
                 eventdetail.setAttribute('id','eventdetail');
                 eventdetail = document.body.appendChild(eventdetail);
                 eventdetail.sub = eventdetail.appendChild(_dce('div'));
                 eventdetail.sub.setAttribute('id','innereventdetail');
                 eventbox = new GlassBox();
                 eventbox.init('eventdetail','600px','300px','hidden','default',false,false);
                 eventbox.lbo(false,0.3);
                 eventbox.appear();
                 
                 psjx_app_detailTraining(calEvent.id,function(_data) {
                    $('innereventdetail').innerHTML = _data;
                 });

            }  
       })

      });

   
      
      // --></script>";
   }
   
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $this->displayCalendar();
            break;
         default:
            $ret = $this->displayCalendar();
            break;
      }
      return $ret;
   }
}

} // ANTRAIN_ANTRAINSESSION_DEFINED
?>