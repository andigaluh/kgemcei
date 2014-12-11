<?php
//--------------------------------------------------------------------//
// Filename : modules/pms/class/selectpms.php                         //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2005-07-22                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_CLASS_SELECTASMSESSION_DEFINED') ) {
   define('HRIS_CLASS_SELECTASMSESSION_DEFINED', TRUE);

class _hris_class_SelectAssessmentSession extends XocpAttachable {
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

   function showAssessmentSession($showOpt=FALSE) {
      $db =& Database::getInstance();
      $user_id = getUserID();
      
      $sql = "SELECT asid,session_nm,session_periode,session_t"
           . " FROM ".XOCP_PREFIX."assessment_session"
           . " WHERE status_cd = 'normal'"
           . " AND session_t = 'regular'"
           . ($user_id==1?"":" AND asid >= '10'")
           . " ORDER BY session_periode,assessment_start";
      $result = $db->query($sql);
      _debuglog($sql);
      $cnt = $db->getRowsNum($result);
      $showOpt = FALSE;
      $session_nm = "-";
      $sessionopt = "";
      if($cnt == 1) {
         list($asid,$session_nm,$session_periode) = $db->fetchRow($result);
         $_SESSION["assessment_session_nm"] = $session_nm;
         $_SESSION["hris_assessment_asid"] = $asid;
         $sessionopt .= "<div style='padding:3px;'>$session_periode <span onclick='_hris_select_asid(\"$asid\",this,event);' class='xlnk'>$session_nm</span></div>";
      } else if($cnt > 1) {
         $found = 0;
         while(list($asid,$session_nm,$session_periode)=$db->fetchRow($result)) {
            $sessionopt .= "<div style='padding:3px;'>$session_periode <span onclick='_hris_select_asid(\"$asid\",this,event);' class='xlnk'>$session_nm</span></div>";
            if($found==1) continue;
            if($asid==$_SESSION["hris_assessment_asid"]) {
               $found = 1;
               $_SESSION["assessment_session_nm"] = $session_nm;
               continue;
            }
         }
         if($found==0) {
            $showOpt = TRUE;
         }
      } else {
         $_SESSION["assessment_session_nm"] = NULL;
         $_SESSION["hris_assessment_asid"] = 0;
         $showOpt = TRUE;
      }
      
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_asmsession.php");
      $ajax = new _hris_class_AssessmentSessionAjax("slasidjx");
      $js = "";
      $js .= $ajax->getJs();
      $js .= "\n<script type='text/javascript'>\n//<![CDATA[
     
      function _hris_select_asid(asid,d,e) {
         slasidjx_app_setAssessmentSession(asid,function(_data) {
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
              <tr><td id='assessment_session_nm'>".$_SESSION["assessment_session_nm"]."</td>
              <td align='right'>[<span class='xlnk' id='chorgsp' onclick='return show_session_opt(this,event);'>Select Assessment Session"
              ."</span>]</td></tr></table><div id='list_session' style='display:none;background-color:#FFFFFF;text-align:left;border:1px solid #aaa;'>$sessionopt</div></div>";
   }

   function show() {
      $db=&Database::getInstance();
      if ($_SESSION["hris_assessment_asid"] > 0) {
         $ret = $this->showAssessmentSession();
      } else {
         $ret = $this->showAssessmentSession(TRUE);
      }
      return $ret;
   }

}

} // HRIS_CLASS_SELECTASMSESSION_DEFINED
?>