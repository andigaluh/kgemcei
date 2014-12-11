<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/idprequest.php                             //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2010-02-09                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_IDPREQUEST_DEFINED') ) {
   define('HRIS_IDPREQUEST_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
include_once(XOCP_DOC_ROOT."/modules/hris/assessmentresult.php");

class _hris_IDPRequest extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_IDPREQUEST_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_IDPMYREQUEST_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_IDPRequest($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   function view_request() {
      include_once(XOCP_DOC_ROOT."/modules/hris/include/idp/idp.php");
      $db=&Database::getInstance();
      $user_id = getUserID();
      
      $_SESSION["html"]->js_scriptaculous_effecs=TRUE;
      
      $sql = "SELECT c.job_id,b.employee_id"
           . " FROM ".XOCP_PREFIX."users a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(person_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job c USING(employee_id)"
           . " WHERE a.user_id = '$user_id'";
      $result = $db->query($sql);
      list($job_id,$employee_id)=$db->fetchRow($result);
      
      $sql = "SELECT request_id,notification_id"
           . " FROM ".XOCP_PREFIX."idp_notifications"
           . " WHERE user_id = '$user_id'"
           . " AND message_id = '_IDP_YOURIDPREQUESTSTARTED'"
           . " AND source_app = 'SUPERIORSTARTREQUEST'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($request_id,$notification_id)=$db->fetchRow($result)) {
            $sql = "UPDATE ".XOCP_PREFIX."idp_notifications SET click_dttm = now(), click_count=click_count+1 WHERE notification_id = '$notification_id'";
            $db->query($sql);
         }
      }
      
      $ret = _idp_view_request($employee_id,$job_id,FALSE,TRUE);
      
      return $ret;
      
   }
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $ret = $this->view_request();
            break;
         default:
            $ret = $this->view_request();
            break;
      }
      return $ret."<div style='height:100px;'>&nbsp;</div>";
   }
}

} // HRIS_IDPREQUEST_DEFINED
?>
