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

   function showAntrainSession($showOpt=FALSE) {
    $db =& Database::getInstance();
      
   $sqljob = "SELECT j.job_id,a.org_id FROM hris_employee_job j LEFT JOIN hris_users u ON (u.person_id = j.employee_id) LEFT JOIN hris_jobs a ON (j.job_id = a.job_id) WHERE u.user_id = ".getUserid()."";
   $resultjob = $db->query($sqljob);
    list($job_id,$org_id)=$db->fetchRow($resultjob);
   
      if ($job_id == 999 || $job_id == 130 || $job_id == 68 || $job_id == 147  || $job_id == 101 || $job_id == 90 || $job_id == 6 || $job_id == 0)
      {
         $divsecid = '';
      $divsecidx = '';
      }   
         else
          {
            $divsecid = " AND org_id = $org_id ORDER BY id_global_session";
        $divsecidx = " AND org_id = $org_id";
          }
         
     
      $sql = "SELECT a.psid,b.id_global_session,b.org_id"
           . " FROM antrain_sessionss a"
           . " LEFT JOIN antrain_budget b ON b.id = a.id_hris_budget"
           . " WHERE a.status_cd = 'normal' $divsecid";
      $result = $db->query($sql);
      _debuglog($sql);
      $cnt = $db->getRowsNum($result);
      $showOpt = FALSE;
      $session_nm = "-";
      $sessionopt = "";
      $yearopt = "";
      $secopt = "";

      $sqlyear = "SELECT id FROM hris_global_sess WHERE status_cd = 'normal' ORDER BY id DESC LIMIT 1";
      $resultyear = $db->query($sqlyear);

      $sqlsec = "SELECT org_id, org_nm FROM hris_orgs WHERE org_class_id =  '4' AND status_cd = 'normal' $divsecidx";
      $resultsec = $db->query($sqlsec);

      if($cnt == 1) {
         list($psid,$year,$org_id) = $db->fetchRow($result);
       
     $sql = "SELECT p.is_proposed, p.date_proposed,  pk.org_nm, pk.org_class_id FROM antrain_sessionss p LEFT JOIN antrain_budget pb ON pb.id = p.id_hris_budget LEFT JOIN hris_orgs pk ON pb.org_id = pk.org_id WHERE psid = ('$psid')" ;
     
     $result = $db->query($sql);
      list($is_proposed,$date_proposed,$org_nm)=$db->fetchRow($result);
       
       
         $_SESSION["antrain_year"] = $year;
         $_SESSION["pms_psid"] = $psid;
         $sessionopt .= "<table><tr><td>Year :</td><td></td></tr></table>";
      } 
     else if($cnt > 1)
     {
         $found = 0;
      $psidx = $_SESSION["pms_psid"];

      $sqlx = "SELECT b.id_global_session,b.org_id"
           . " FROM antrain_sessionss a"
           . " LEFT JOIN antrain_budget b ON b.id = a.id_hris_budget"
           . " WHERE a.status_cd = 'normal' AND a.psid = '$psidx'";
      $resultx = $db->query($sqlx);
      list($yearx,$org_idx) = $db->fetchRow($resultx);

         while (list($year)=$db->fetchRow($resultyear)) {
            if ($year == $yearx) {
              $selected1 = "selected";
            }else{
              $selected1 = "";
            }
            $yearopt .= "<option value='$year' $selected1>$year</option>";
         }
         while (list($org_id,$org_nm)=$db->fetchRow($resultsec)) {
            if ($org_id == $org_idx) {
              $selected1 = "selected";
            }else{
              $selected1 = "";
            }
            $secopt .= "<option value='$org_id' $selected1>$org_nm</option>";
         }
         $sessionopt .= "<form id='frm1'><table style='margin:10px 0;'>"
                      . "<tr><td style='width:95px;'>Year :</td><td>Section :</td><td></td></tr>"
                      . "<tr><td  style=''><select id='inp_year' name='year' style='width:80px;'>$yearopt</select></td>"
                      . "<td><select id='inp_orgid' name='org_id' style='width:280px;'>$secopt</select></td><td style='padding-left: 15px;'><input id='btn_set_session' onclick='set_session();' type='button' value='"._SUBMIT."'/></td></tr>"
                      . "</table></form>";
         if($found==0) {
            $showOpt = TRUE;
         }
      } else {
         $_SESSION["antrain_year"] = NULL;
         $_SESSION["pms_psid"] = 0;
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
         //new Effect.toggle(Element,'blind',{duration:0.2}); 
         return true;
      }

      function set_session() {
         var ret = parseForm('frm1');
         slpsidjx_app_setAntrainSession(ret,function(_data) {
            var data = recjsarray(_data);
            if (data[2] == 0) {
              alert('Budget not available');
            }
            location.reload();
         });
      }
      
      ".($showOpt==TRUE?"setTimeout('show_session_opt(null,null);',100);":"")."
      
      //]]>\n</script>";
      
           
      return $js."$sessionopt";
   }



   function show() {
      $db=&Database::getInstance();
      if ($_SESSION["pms_psid"] > 0) {
         $ret = $this->showAntrainSession();
      } else {
         $ret = $this->showAntrainSession(TRUE);
      }
      return $ret;
   }

}

} // antrain_class_SELECTORG_DEFINED
?>