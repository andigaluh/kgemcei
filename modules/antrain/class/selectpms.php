<?php
//--------------------------------------------------------------------//
// Filename : modules/pms/class/selectpms.php                         //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2005-07-22                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('antrain_class_SELECTORG_DEFINED') ) {
   define('antrain_class_SELECTORG_DEFINED', TRUE);

class _antrain_class_SelectSession extends XocpAttachable {
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

   function showPMSSession($showOpt=FALSE) {
      $db =& Database::getInstance();
      
      $sql = "SELECT psid,session_nm,session_periode"
           . " FROM antrain_session"
           . " WHERE status_cd = 'normal' ORDER BY session_periode,start_dttm";
      $result = $db->query($sql);
      _debuglog($sql);
      $cnt = $db->getRowsNum($result);
      $showOpt = FALSE;
      $session_nm = "-";
      $sessionopt = "";
      if($cnt == 1) {
         list($psid,$session_nm,$session_periode) = $db->fetchRow($result);
         $_SESSION["antrain_session_nm"] = $session_nm;
         $_SESSION["pms_psid"] = $psid;
         $sessionopt .= "<div style='padding:3px;'>$session_periode <span onclick='_pms_select_pms(\"$psid\",this,event);' class='xlnk'>$session_nm</span></div>";
      } else if($cnt > 1) {
         $found = 0;
         while(list($psid,$session_nm,$session_periode)=$db->fetchRow($result)) {
            $sessionopt .= "<div style='padding:3px;'>$session_periode <span onclick='_pms_select_pms(\"$psid\",this,event);' class='xlnk'>$session_nm</span></div>";
            if($found==1) continue;
            if($psid==$_SESSION["pms_psid"]) {
               $found = 1;
               $_SESSION["antrain_session_nm"] = $session_nm;
               continue;
            }
         }
         if($found==0) {
            $showOpt = TRUE;
         }
      } else {
         $_SESSION["antrain_session_nm"] = NULL;
         $_SESSION["pms_psid"] = 0;
         $showOpt = TRUE;
      }
      
      require_once(XOCP_DOC_ROOT."/modules/pms/class/ajax_pmssession.php");
      $ajax = new _pms_class_PMSSessionAjax("slpsidjx");
      $js = "";
      //$js .= "\n<script type=\"text/javascript\" src=\"".XOCP_SERVER_SUBDIR."/include/treeorg.js\"></script>";
      $js .= $ajax->getJs();
      $js .= "\n<script type='text/javascript'>\n//<![CDATA[
     
      function _pms_select_pms(psid,d,e) {
         slpsidjx_app_setPMSSession(psid,function(_data) {
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
              <tr><td id='antrain_session_nm'>".$_SESSION["antrain_session_nm"]."</td>
              <td align='right'>[<span class='xlnk' id='chorgsp' onclick='return show_session_opt(this,event);'>Select Annual Training Session"
              ."</span>]</td></tr></table><div id='list_session' style='display:none;background-color:#FFFFFF;text-align:left;border:1px solid #aaa;'>$sessionopt</div></div>";
   }



   function show() {
      $db=&Database::getInstance();
      if ($_SESSION["pms_psid"] > 0) {
         $ret = $this->showPMSSession();
      } else {
         $ret = $this->showPMSSession(TRUE);
      }
      return $ret;
   }

}

} // antrain_class_SELECTORG_DEFINED
?>