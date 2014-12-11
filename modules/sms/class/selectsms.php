<?php
//--------------------------------------------------------------------//
// Filename : modules/sms/class/selectsms.php                         //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2013-10-22                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('SMS_CLASS_SELECTORG_DEFINED') ) {
   define('SMS_CLASS_SELECTORG_DEFINED', TRUE);

class _sms_class_SelectSession extends XocpAttachable {
   var $prefix = "slps_";
   var $attr;
   var $catch;
   
   function __construct($catch=NULL) {
      $this->catch = $catch;
      $this->setURLParam(XOCP_SERVER_SUBDIR."/index.php",NULL);
      $this->attr = array("nm","mrn","searchorg","f","p","ch","selectpt","ptperson_id");
   }
   
   function getPrefix() {
      return $this->prefix;
   }

   function showSMSSession($showOpt=FALSE) {
      $db =& Database::getInstance();
      
      $sql = "SELECT id,title_session,periode_session"
           . " FROM sms_session"
           . " ORDER BY periode_session";
      $result = $db->query($sql);
      _debuglog($sql);
      $cnt = $db->getRowsNum($result);
      $showOpt = FALSE;
      $title_session = "-";
      $sessionopt = "";
      if($cnt == 1) {
         list($id,$title_session,$periode_session) = $db->fetchRow($result);
         $_SESSION["sms_title_session"] = $title_session;
         $_SESSION["sms_id"] = $id;
         $sessionopt .= "<div style='padding:3px;'>$periode_session <span onclick='_sms_select_sms(\"$id\",this,event);' class='xlnk'>$title_session</span></div>";
      } else if($cnt > 1) {
         $found = 0;
         while(list($id,$title_session,$periode_session)=$db->fetchRow($result)) {
            $sessionopt .= "<div style='padding:3px;'>$periode_session <span onclick='_sms_select_sms(\"$id\",this,event);' class='xlnk'>$title_session</span></div>";
            if($found==1) continue;
            if($id==$_SESSION["sms_id"]) {
               $found = 1;
               $_SESSION["sms_title_session"] = $title_session;
               continue;
            }
         }
         if($found==0) {
            $showOpt = TRUE;
         }
      } else {
         $_SESSION["sms_title_session"] = NULL;
         $_SESSION["sms_id"] = 0;
         $showOpt = TRUE;
      }
      
      require_once(XOCP_DOC_ROOT."/modules/sms/class/ajax_smssession.php");
      $ajax = new _sms_class_SMSSessionAjax("slpsidjx");
      $js = "";
      //$js .= "\n<script type=\"text/javascript\" src=\"".XOCP_SERVER_SUBDIR."/include/treeorg.js\"></script>";
      $js .= $ajax->getJs();
      $js .= "\n<script type='text/javascript'>\n//<![CDATA[
     
      function _sms_select_sms(id,d,e) {
         slpsidjx_app_setSMSSession(id,function(_data) {
            location.reload();
         });
      }
      
      var dv = null;
      function show_session_opt(d,e) {
         var Element = _gel('list_session');
         new Effect.toggle(Element,'blind',{duration:0.2}); 
         return true;
      }
      
      ".($showOpt==TRUE?"setTimeout('show_session_opt(null,null);',100);":"")."
      
      //]]>\n</script>";
      
      return $js."<div class='orgsel'><table border='0' width='100%' cellpadding='2' cellspacing='0'>
              <tr><td id='sms_title_session'>".$_SESSION["sms_title_session"]."</td>
              <td align='right'>[<span class='xlnk' id='chorgsp' onclick='return show_session_opt(this,event);'>Select SMS Session"
              ."</span>]</td></tr></table><div id='list_session' style='display:none;background-color:#FFFFFF;text-align:left;border:1px solid #aaa;'>$sessionopt</div></div>";
   }



   function show() {
      $db=&Database::getInstance();
      if ($_SESSION["sms_id"] > 0) {
         $ret = $this->showSMSSession();
      } else {
         $ret = $this->showSMSSession(TRUE);
      }
      return $ret;
   }

}

} // SMS_CLASS_SELECTORG_DEFINED
?>