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
	 list($job_id,$org_id_sec)=$db->fetchRow($resultjob);
	 
	  $sqlorg = "SELECT b.job_nm,c.job_class_nm,e.org_nm,f.org_class_nm,e.org_id FROM hris_jobs b LEFT JOIN hris_job_class c USING(job_class_id) LEFT JOIN hris_workarea d ON d.workarea_id = b.workarea_id LEFT JOIN hris_orgs e ON e.org_id = b.org_id LEFT JOIN hris_org_class f ON f.org_class_id = e.org_class_id WHERE b.job_id = $job_id";
	$resultorg = $db->query($sqlorg);
	 list($job_nm,$job_class_nm,$org_nm,$org_class_nm,$org_ids)=$db->fetchRow($resultorg);
	
	 	if ($job_id == 999 || $job_id == 130 || $job_id == 68 || $job_id == 147  || $job_id == 101 || $job_id == 90 || $job_id == 6 || $job_id == 0)
		{
			$divsecid = '';
		}
	//edit disini untuk filter by Division
	elseif($org_class_nm == 'Division')
		{
			$divsecid = " AND org_id = '$org_id_sec' ORDER BY year";
		}
	//edit disini untuk filter by Section
	elseif($org_class_nm == 'Section')
		 {
			$divsecid = " AND org_id_sec = '$org_id_sec' ORDER BY year";
		 }
	else
		{
	
		}	
	  
      $sql = "SELECT p.psid,p.year,pk.org_nm, pk.org_class_id FROM antrain_sessionss p LEFT JOIN hris_orgs pk ON p.org_id = pk.org_id"
				. " WHERE p.status_cd = 'normal' AND is_approved = '1'  $divsecid ";

		
      $result = $db->query($sql);
      _debuglog($sql);
      $cnt = $db->getRowsNum($result);
      $showOpt = FALSE;
      $session_nm = "-";
      $sessionopt = "";
      if($cnt == 1) {
         list($psid,$year,$org_nm) = $db->fetchRow($result);
		 
		$sqlsecnm = "SELECT pk.org_nm, pk.org_class_id FROM antrain_sessionss p LEFT JOIN hris_orgs pk ON p.org_id_sec = pk.org_id WHERE psid = $psid ORDER BY year DESC " ;
		$resultsecnm = $db->query($sqlsecnm);
		list($org_nm_sec)=$db->fetchRow($resultsecnm);
		 
         $_SESSION["antrain_year"] = $year;
         $_SESSION["pms_psid"] = $psid;
         $sessionopt .= "<div style='padding:3px;'>$year <span onclick='_antrain_select_antrain(\"$psid\",this,event);' class='xlnk'>$org_nm/$org_nm_sec</span></div>";
      } 
	  else if($cnt > 1) 
	  {
	  
	  
         $found = 0;
         while(list($psid,$year,$org_nm)=$db->fetchRow($result)) {
		
		$sqlsecnm = "SELECT pk.org_nm, pk.org_class_id FROM antrain_sessionss p LEFT JOIN hris_orgs pk ON p.org_id_sec = pk.org_id WHERE psid = $psid ORDER BY year DESC " ;
		$resultsecnm = $db->query($sqlsecnm);
		list($org_nm_sec)=$db->fetchRow($resultsecnm);
		
            $sessionopt .= "<div style='padding:3px;'>$year <span onclick='_antrain_select_antrain(\"$psid\",this,event);' class='xlnk'>$org_nm/$org_nm_sec</span></div>";
            if($found==1) continue;
            if($psid==$_SESSION["pms_psid"]) {
               $found = 1;
               $_SESSION["antrain_year"] = $year;
               continue;
            }
         }
         if($found==0) {
            $showOpt = TRUE;
         }
      } 
	  
	    else if($cnt <1 ) 
	  {
	       $sessionopt .= "<div style='padding:3px;'>No Training Cancellation / Alteration. </div>";
		   $_SESSION["antrain_year"] = NULL;
		   $_SESSION["pms_psid"] = 0;
		   $showOpt = TRUE;
      } 
	  
	  
	  
	  else 
	  {
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
         new Effect.toggle(Element,'blind',{duration:0.2}); 
         return true;
      }
      
      ".($showOpt==TRUE?"setTimeout('show_session_opt(null,null);',100);":"")."
      
      //]]>\n</script>";
      
      return $js."<div class='orgsel'><table border='0' width='100%' cellpadding='2' cellspacing='0'>
              <tr><td id='antrain_year'>".$_SESSION["antrain_year"]."</td>
              <td align='right'>[<span class='xlnk' id='chorgsp' onclick='return show_session_opt(this,event);'>Select Annual Training Session"
              ."</span>]</td></tr></table><div id='list_session' style='display:none;background-color:#FFFFFF;text-align:left;border:1px solid #aaa;'>$sessionopt</div></div>";
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