<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/home_user.php                              //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-07-18                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_IDPEVENTCONFIRMATION_DEFINED') ) {
   define('HRIS_IDPEVENTCONFIRMATION_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
include_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_idpeventconfirmation.php");

class _hris_IDPEventConfirmation extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_IDPEVENTCONFIRMATION_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_IDPEVENTCONFIRMATION_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_IDPEventConfirmation($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   function confirm() {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $agenda_cnt = 0;
      if(!isset($_GET["goto"])||!isset($_GET["employee_id"])) {
         $_SESSION["html"]->redirect(XOCP_SERVER_SUBDIR."/index.php?XP_myhome_menu=0");
         return;
      }
      $ajax = new _hris_class_IDPEventConfirmationAjax("econ");
      $ret = $ajax->render_event($_GET["goto"],$_GET["employee_id"]);
      
      return $ret;
   }
   
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            if(isset($_GET["msg"])&&$_GET["msg"]!="") {
               $url = urldecode($_GET["url"]);
               $_SESSION["html"]->redirect = $url;
               $notification_id = $_GET["msg"];
               $dofollow = $_GET["df"];
               if($notification_id>0) {
                  if($dofollow>0) {
                     $sql = "UPDATE ".XOCP_PREFIX."idp_notifications SET is_followed='1', followed_dttm = now() WHERE notification_id = '$notification_id'";
                     $db->query($sql);
                     
                     /// then follow the same message_id and request_id
                     $sql = "SELECT request_id,user_id,message_id FROM ".XOCP_PREFIX."idp_notifications WHERE notification_id = '$notification_id'";
                     $result = $db->query($sql);
                     if($db->getRowsNum($result)>0) {
                        while(list($request_id,$user_id,$message_id)=$db->fetchRow($result)) {
                           $sql = "UPDATE ".XOCP_PREFIX."idp_notifications SET is_followed='1', followed_dttm = now() WHERE user_id = '$user_id' AND message_id = '$message_id' AND request_id = '$request_id'";
                           $db->query($sql);
                        }
                     }
                  }
                  $sql = "UPDATE ".XOCP_PREFIX."idp_notifications SET click_dttm = now(), click_count=click_count+1 WHERE notification_id = '$notification_id'";
                  $db->query($sql);
               }
               return "";
            } else {
               $ret = $this->confirm();
            }
            break;
         default:
            $ret = $this->confirm();
            break;
      }
      return $ret;
   }
}

} // HRIS_IDPEVENTCONFIRMATION_DEFINED
?>