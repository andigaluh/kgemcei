<?php
//--------------------------------------------------------------------//
// Filename : modules/sms/smssetaccess.php                       //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2013-12-12                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('SMS_SETACCESS_DEFINED') ) {
   define('SMS_SETACCESS_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/sms/modconsts.php");

class _sms_SMSSetAccess extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _SMS_SETACCESS_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = "Set Access for Management";
   var $display_comment = TRUE;
   var $data;
   
   function __construct($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function listSetAccess() {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/sms/class/ajax_smssetaccess.php");
      $ajax = new _sms_class_SMSSetAccessAjax("psjx");

      $user_id = getUserID();

      if ($user_id == 1) {
         $ret = "<table>"
              . "<tr><td>This button set access to all Management User.</td></tr>"
              . "<tr><td style='padding:10px 0 10px 10px;'><input onclick='set_access()' type='button' value='CONFIRM'/></td></tr>"
              . "<tr><td><span id='progress'></span></td></tr>"
              . "</table>";
      }else{
         $ret = "You don't have privileges to access this menu. ";
      }

      
      return $ret.$ajax->getJs()."

      <script type='text/javascript'><!--

         function set_access(d,e) {
            $('progress').appendChild(progress_span('&nbsp;...saving&nbsp;&nbsp;'));
            psjx_app_setAccess(function(_data) {
               location.reload(true);
            });
         }
      
      
      // --></script>";
   }
   
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $this->listSetAccess();
            break;
         default:
            $ret = $this->listSetAccess();
            break;
      }
      return $ret;
   }
}

} // SMS_SETACCESS_DEFINED
?>