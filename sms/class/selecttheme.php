<?php
//-------------------------------------------------------------------- //
// Filename : modules/pms/class/selecttheme.php                //
// Software : XOCP - X Open Community Portal                  //
// Version  : 0.1                                                   			 //
// Date     : 201-10-16                                                   //
// License  : GPL                                                           //
//--------------------------------------------------------------------//

if ( !defined('sms_class_SELECTTHEME_DEFINED') ) {
   define('sms_class_SELECTTHEME_DEFINED', TRUE);

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

   function showSmsSession($showOpt=FALSE) {
      $db =& Database::getInstance();
      
      $sql = "SELECT id, periode_session FROM `sms_session`ORDER BY periode_session";
      $result = $db->query($sql);
      _debuglog($sql);
      $cnt = $db->getRowsNum($result);
      $showOpt = FALSE;
      $session_nm = "-";
      $sessionopt = "";
      if($cnt == 1) {
         list($idses,$year) = $db->fetchRow($result);
         $_SESSION["sms_year"] = $year;
         $_SESSION["sms_id"] = $idses;
         $sessionopt .= "<div style='padding:3px;'>$year <span onclick='_antrain_select_antrain(\"$idses\",this,event);' class='xlnk'>$year</span></div>";
      } else if($cnt > 1) {
         $found = 0;
         while(list($psid,$year)=$db->fetchRow($result)) {
            $sessionopt .= "<div style='padding:3px;'>$year <span onclick='_antrain_select_antrain(\"$idses\",this,event);' class='xlnk'>$year</span></div>";
            if($found==1) continue;
            if($psid==$_SESSION["sms_id"]) {
               $found = 1;
               $_SESSION["sms_year"] = $year;
               continue;
            }
         }
         if($found==0) {
            $showOpt = TRUE;
         }
      } else {
         $_SESSION["sms_year"] = NULL;
         $_SESSION["sms_id"] = 0;
         $showOpt = TRUE;
      }
      
      require_once(XOCP_DOC_ROOT."/modules/pms/class/ajax_pmssession.php");
      $ajax = new _pms_class_PMSSessionAjax("slpsidjx");
      $js = "";
      //$js .= "\n<script type=\"text/javascript\" src=\"".XOCP_SERVER_SUBDIR."/include/treeorg.js\"></script>";
      $js .= $ajax->getJs();
      $js .= "\n<script type='text/javascript'>\n//<![CDATA[
     
      function _antrain_select_antrain(psid,d,e) {
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
              <tr><td id='sms_year'>".$_SESSION["sms_year"]."</td>
              <td align='right'>[<span class='xlnk' id='chorgsp' onclick='return show_session_opt(this,event);'>Select SMS Session"
              ."</span>]</td></tr></table><div id='list_session' style='display:none;background-color:#FFFFFF;text-align:left;border:1px solid #aaa;'>$sessionopt</div></div>";
   }



   function show() {
      $db=&Database::getInstance();
      if ($_SESSION["sms_id"] > 0) {
         $ret = $this->showSmsSession();
      } else {
         $ret = $this->showSmsSession(TRUE);
      }
      return $ret;
   }

}

} // antrain_class_SELECTORG_DEFINED
?>