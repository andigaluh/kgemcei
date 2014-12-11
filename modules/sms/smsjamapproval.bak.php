<?php
//--------------------------------------------------------------------//
// Filename : modules/pms/smsjamapproval.php                                  //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2010-09-22                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('SMS_JAMAPPROVAL_DEFINED') ) {
   define('SMS_JAMAPPROVAL_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
//include_once(XOCP_DOC_ROOT."/modules/pms/class/ajax_objective.php");
//include_once(XOCP_DOC_ROOT."/modules/sms/smsjam.php");
include_once(XOCP_DOC_ROOT."/modules/sms/smsmatrixjam.php");
require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
include_once(XOCP_DOC_ROOT."/modules/sms/class/selectsmsmatrixjam.php");

class _sms_JAMApproval extends XocpBlock {
   var $catchvar = _SMS_CATCH_VAR;
   var $blockID = _SMS_JAMAPPROVAL_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = "SMS JAM Approval";
   var $display_comment = TRUE;
   var $data;
   
   function _sms_JAMApproval($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */

   }
   
   function requestList() {
      $psid = $_SESSION["pms_psid"];
      $db = &Database::getInstance();
      $user_id = getUserID();
      global $xocp_vars;
      
      list($self_job_id,
           $self_employee_id,
           $self_job_nm,
           $self_nm,
           $self_nip,
           $self_gender,
           $self_jobstart,
           $self_entrance_dttm,
           $self_jobage,
           $self_job_summary,
           $self_person_id,
           $self_user_id,
           $self_first_assessor_job_id,
           $self_next_assessor_job_id)=_hris_getinfobyuserid($user_id);
      
      $ret = "<table style='' class='xxlist'>"
           . "<colgroup>"
           . "<col width='40'/>"
           . "<col width='150'/>"
           . "<col width='*'/>"
           . "<col width='220'/>"
           . "<col width='220'/>"
           . "</colgroup>"
           . "<thead>"
           . "<tr>"
           . "<td style='text-align:center;'>No.</td>"
           . "<td>NIP</td>"
           . "<td>Employee</td>"
           . "<td>JAM Organization</td>"
           . "<td>Status</td>"
           . "</tr>"
           . "</thead>"
           . "<tbody>";
      
	   $org_id = $_SESSION["sms_org_id"];
      
      $sql = "SELECT org_class_id FROM ".XOCP_PREFIX."orgs WHERE org_id = '$org_id'";
      $result = $db->query($sql);
      list($current_org_class_id)=$db->fetchRow($result);
	  
	  
      $sql = "SELECT a.employee_id,a.employee_ext_id,"
       . "b.person_nm "
       . " FROM ".XOCP_PREFIX."employee a"
       . " LEFT JOIN ".XOCP_PREFIX."persons b USING(person_id)"
       . " LEFT JOIN ".XOCP_PREFIX."employee_job c USING(employee_id)"
       . " LEFT JOIN ".XOCP_PREFIX."jobs d ON d.job_id = c.job_id"
       . " LEFT JOIN ".XOCP_PREFIX."orgs e USING(org_id)"
       . " LEFT JOIN ".XOCP_PREFIX."org_class f USING(org_class_id)"
       . " WHERE a.status_cd = 'normal' AND e.org_id = '$org_id' AND f.org_class_id = '$current_org_class_id' AND c.gradeval > 5 AND b.person_id != $self_employee_id"
       . " ORDER BY b.person_nm";
	  
	  
/* 	   $sql = "SELECT a.employee_id,b.employee_ext_id,c.person_nm "
           . " FROM hris_employee_job a"
           . " LEFT JOIN hris_employee b USING(employee_id)"
           . " LEFT JOIN hris_persons c USING(person_id)"
           . " WHERE a.upper_employee_id = '$self_employee_id' "; */
         
	 $result = $db->query($sql);
      $no=0;
      _debuglog($sql);
      if($db->getRowsNum($result)>0) {
         while(list($employee_id,$nip,$employee_nm)=$db->fetchRow($result)) {
            $no++;
            $jam_org_nm = "";
            $jam_org_class_nm = "";
            if($jam_org_ind==1) {
               $sql = "SELECT a.org_nm,b.org_class_nm FROM ".XOCP_PREFIX."orgs a LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id) WHERE a.org_id = '$org_id'";
               $ro = $db->query($sql);
               if($db->getRowsNum($ro)==1) {
                  list($jam_org_nm,$jam_org_class_nm)=$db->fetchRow($ro);
               }
            }
       
     	$sqlapp2=   "SELECT propose_stat,approve1_stat,approve2_stat FROM sms_jam_approval WHERE id_session = '$psid' AND employee_id = '$employee_id'";
		$resultapp2 = $db->query($sqlapp2);
		list($prop,$appr,$appr2)=$db->fetchRow($resultapp2);
		
		
		if($prop== null AND $appr==null AND $appr2==null)
			{
				$link = "<td><a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&goto=y&employee_id=$employee_id&u=".uniqid('u')."'>View </a></td>";
			}
		elseif($prop== '1' AND $appr=='0' AND $appr2=='0')
			{
				$link = "<td><a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&goto=y&employee_id=$employee_id&u=".uniqid('u')."'>wait SM approval </a></td>";
			}		  
		elseif($prop== '1' AND $appr=='1' AND $appr2=='0')
			{
				$link = "<td><a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&goto=y&employee_id=$employee_id&u=".uniqid('u')."'>wait DM approval</a></td>";
			}		  
		elseif($prop== '1' AND $appr=='1' AND $appr2=='1')
			{
				$link = "<td><a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&goto=y&employee_id=$employee_id&u=".uniqid('u')."'>Finish approval </a></td>";
			}		  
			
			
			
            $ret .= "<tr>"
                  . "<td style='text-align:center;'>$no</td>"
                  . "<td>$nip</td>"
                  . "<td>$employee_nm</td>"
                  . "<td>$jam_org_nm $jam_org_class_nm</td>"
                  . "$link"
                  . "</tr>";
         }
      } else {
         $ret .= "<tr><td colspan='4' style='text-align:center;color:#888;font-style:italic;'>"._EMPTY."</td></tr>";
      }
      
      $ret .= "</tbody></table>";
      
      return $ret;
      
   }
   
   
   function approval($employee_id,$jam_org_ind,$jam_org_id) {
      $db=&Database::getInstance();
      
      $psid = $_SESSION["pms_psid"];
      
      list($job_id,
           $employee_idx,
           $job_nm,
           $employee_nm,
           $nip,
           $gender,
           $jobstart,
           $entrance_dttm,
           $jobage,
           $job_summary,
           $person_id,
           $employee_user_id,
           $first_assessor_job_id,
           $next_assessor_job_id)=_hris_getinfobyemployeeid($employee_id);
      
	  
	  	$sqlses = "SELECT periode_session FROM sms_session WHERE id = $psid ";
		$resultses = $db->query($sqlses);
		list($periode_session)=$db->fetchRow($resultses);
		
	  
      
      $sql = "SELECT c.job_nm,c.job_abbr,d.org_nm,d.org_abbr,a.employee_ext_id,e.person_nm,e.person_id"
           . " FROM ".XOCP_PREFIX."employee a"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job b ON b.employee_id = a.employee_id AND b.job_id = '$job_id'"
           . " LEFT JOIN ".XOCP_PREFIX."jobs c ON c.job_id = '$job_id'"
           . " LEFT JOIN ".XOCP_PREFIX."orgs d ON d.org_id = c.org_id"
           . " LEFT JOIN ".XOCP_PREFIX."persons e ON e.person_id = a.person_id"
           . " WHERE a.employee_id = '$employee_id'";
      $result = $db->query($sql);
      list($job_nm,$job_abbr,$org_nm,$org_abbr,$nip,$employee_nm,$person_id)=$db->fetchRow($result);
      $ret = "<div style='border:1px solid #bbb;background-color:#eee;padding:5px;text-align:right;'>[<a href='".XOCP_SERVER_SUBDIR."/index.php'>Back</a>]</div>";
	  
	  $ret .= "<div style='border:1px solid #bbb;-moz-border-radius:5px;padding:10px;margin-top:10px;'>";
      $ret .= "<div style=''>Percentage below each objective is the weight of the objective. Calculate weighed score for each rating. "
            . "Select the most appropriate statement for employee's performance in each area of contribution, "
            . "then input the weighed score and selected statement into \"Final Result\" column. "
            . "Total score is the sum of the percentage in \"Final Result\" column of each objective.</div>";
      $ret .= "<hr noshade='1' size='1'/>";
      $ret .= "<div style='font-style:italic;'>Persentase di bawah setiap objektif adalah bobot dari objektif tersebut. "
            . "Hitunglah nilai bobot untuk setiap penilaian dengan memilih pernyataan yang sesuai dengan kinerja karyawan "
            . "untuk masing-masing area kontribusi, kemudian masukkan nilai bobot dan pernyataan yang dipilih itu di "
            . "kolom \"Final Result\". Total nilai adalah jumlah persentase penilaian di kolom \"Final Result\" dari setiap objektif. </div>";
      $ret .= "</div>";
      $ret .= "<br/><table><tr>";
	 // $ret .= "<td style='padding:4px;border:1px solid #bbb;-moz-box-shadow:2px 2px 5px #333;'><img src='".XOCP_SERVER_SUBDIR."/modules/hris/thumb.php?pid=${person_id}' height='100'/></td>";
      
	  $ret .= "<td style='vertical-align:top;padding-left:10px;'>"
            
            . "<table style='font-weight:bold;margin-left:0px;font-size:1.1em;'><colgroup><col width='180'/><col/></colgroup><tbody>"
            . "<tr><td style='border-bottom: 1x solid #bbb;'>Name of employee</td><td>: $employee_nm</td></tr>"
			."<tr><td colspan='3'><div style='border-bottom: 1px solid #777777'></div></td></tr>"
            . "<tr><td>Pisition/Title</td><td>:  $job_nm ($job_abbr)  </td></tr>"
			."<tr><td colspan='3'><div style='border-bottom: 1px solid #777777'></div></td></tr>"
            . "<tr><td>Division/Section</td><td>: $org_nm ($org_abbr)</td></tr>"
			."<tr><td colspan='3'><div style='border-bottom: 1px solid #777777'></div></td></tr>"
            //. "<tr><td>NIP</td><td>: $nip</td></tr>"
            . "<tr><td>Review Period</td><td>: $periode_session</td></tr>"
			."<tr><td colspan='3'><div style='border-bottom: 1px solid #777777'></div></td></tr>"
            . "</tbody></table></td></tr></table><div style='padding:10px;'>";
      
      //$sql = "SELECT DISTINCT(approval_st) FROM pms_jam WHERE psid = '$psid' AND employee_id = '$employee_id' AND jam_org_ind = '$jam_org_ind' AND org_id = '$jam_org_id'";
      $sql = "SELECT DISTINCT(propose_stat) FROM sms_jam_approval WHERE id_session = '$psid' AND employee_id = '$employee_id'";
      $result = $db->query($sql);
      _debuglog($sql);
/*       if($db->getRowsNum($result)==1) {
         list($jam_status_cd)=$db->fetchRow($result);
         if($employee_id!=$self_employee_id) {
            if($jam_status_cd=="new") {
               return $ret. "<br/><br/>JAM is still prepared.";
            }
         }
      } else {
         return $ret."<br/<br/>No JAM found.";
      }
       */
		$ret .= _sms_matrix_JAM::smsmatrixjamapproval($psid,$employee_id,$employee_nm);
      return $ret;
   }
   
    function showOrg($showOpt=FALSE) {
      $db =& Database::getInstance();

      $person_id = getPersonID();
      if(!isset($_SESSION["sms_org_id"])) {
         $_SESSION["sms_org_id"] = 1;
      }
      $org_id = $_SESSION["sms_org_id"];
      $psid = $_SESSION["sms_id"];

      $sql = "SELECT section_submit FROM sms_approval WHERE org_id = '$org_id' AND id_section_session = '$psid'";
      $result = $db->query($sql);
      list($section_submit) = $db->fetchRow($result);
      if ($section_submit == 1) {
        $returnbtn = "<span style='float:right; margin:5px;'><input onclick='return_session(\"$psid\",this,event);' type='button' value='Return' id='returnbtn'/></span>";
      }else{
        $returnbtn = "";
      }
      

      $sql = "SELECT a.employee_id,e.org_id,e.org_nm,f.org_class_id"
       . " FROM ".XOCP_PREFIX."employee a"
       . " LEFT JOIN ".XOCP_PREFIX."persons b USING(person_id)"
       . " LEFT JOIN ".XOCP_PREFIX."employee_job c USING(employee_id)"
       . " LEFT JOIN ".XOCP_PREFIX."jobs d ON d.job_id = c.job_id"
       . " LEFT JOIN ".XOCP_PREFIX."orgs e USING(org_id)"
       . " LEFT JOIN ".XOCP_PREFIX."org_class f USING(org_class_id)"
       . " WHERE a.status_cd = 'normal' AND a.person_id = $person_id AND c.gradeval > '9'";// OR a.person_id = '100'
      $result = $db->query($sql);
      list($employee_idy,$org_idy,$org_nmy,$org_class_idy) = $db->fetchRow($result);
      
      if ($employee_idy == "") {
        $returnbtn = "";

        $sql = "SELECT e.org_id,e.org_nm,f.org_class_id"
       . " FROM ".XOCP_PREFIX."employee a"
       . " LEFT JOIN ".XOCP_PREFIX."persons b USING(person_id)"
       . " LEFT JOIN ".XOCP_PREFIX."employee_job c USING(employee_id)"
       . " LEFT JOIN ".XOCP_PREFIX."jobs d ON d.job_id = c.job_id"
       . " LEFT JOIN ".XOCP_PREFIX."orgs e USING(org_id)"
       . " LEFT JOIN ".XOCP_PREFIX."org_class f USING(org_class_id)"
       . " WHERE a.status_cd = 'normal' AND a.person_id = $person_id AND e.org_class_id = '4'";
      $result = $db->query($sql);
      list($org_id,$org_nm,$org_class_id) = $db->fetchRow($result);

      $_SESSION["sms_org_id"] = $org_id;

        return "<div class='orgsel'><table border='0' width='100%' cellpadding='2' cellspacing='0'>
              <tr><td id='hris_org_nm'>Level of Organization : <span style='font-weight:bold;'>$org_nm</span></td>
              </tr></table><div id='list_org' style='display:none;background-color:#FFFFFF;text-align:left;'></div></div>"
              ."<div style='padding:2px;font-size:12px;color: #666666; text-align:left;'>$returnbtn</div>" ;
      }else{
        $returnbtn = $returnbtn;
        $sql = "SELECT o.org_id,o.org_nm,b.org_class_nm,o.org_abbr"
             . " FROM ".XOCP_PREFIX."orgs o"
             . " LEFT JOIN ".XOCP_PREFIX."org_class b ON b.org_class_id = o.org_class_id"
             . " WHERE o.org_id = '$org_id'";
        $result = $db->query($sql);
        $cnt = $db->getRowsNum($result);
        $showOpt = FALSE;
        $org_nm = "-";
        if($cnt == 1) {
           list($org_id,$org_nmx,$org_class_nm,$org_abbr) = $db->fetchRow($result);
           $_SESSION["hris_org_nm"] = "$org_abbr $org_nmx [$org_class_nm]";
           $org_nm = "$org_nmx $org_class_nm";
        } else if($cnt > 1) {
           $found = 0;
           while(list($org_id,$org_nmx,$org_class_nm)=$db->fetchRow($result)) {
              if($org_id==$_SESSION["hris_org_id"]) {
                 $found = 1;
                 $org_nm = "$org_abbr $org_nmx [$org_class_nm]";
                 break;
              }
           }
           if($found==0) {
              $showOpt = TRUE;
           }
        } else {
           $_SESSION["hris_org_nm"] = NULL;
           $_SESSION["hris_org_id"] = 0;
           $showOpt = TRUE;
        }
        if($org_nm == "") $org_nm = "-";
        
        require_once(XOCP_DOC_ROOT."/modules/sms/class/ajax_selectorg.php");
        $ajax = new _hris_class_SelectOrgAjax("slrjx");
        $js = "";
        //$js .= "\n<script type=\"text/javascript\" src=\"".XOCP_SERVER_SUBDIR."/include/treeorg.js\"></script>";
        $js .= $ajax->getJs();
        $js .= "\n<script type='text/javascript'>\n//<![CDATA[
       
        function _org_select_org(org_id,d,e) {
           slrjx_app_setOrg(org_id,function(_data) {
              location.reload();
           });
        }
        
        var dv = null;
        function show_org_opt(d,e) {
           ajax_feedback = _caf;
           var Element = _gel('list_org');
           if (dv&&dv.style.display!='none') {
              var uls = _gel('navSlide');
              var dvx = _gel('dvSlide');
              new Effect.toggle(Element,'blind',{duration:0.2}); 
           } else {
              _destroy(uls);  
              dv = document.createElement('div');
              dv.setAttribute('id','dvSlide');
              dv.innerHTML = '';
              dv = Element.appendChild(dv);
              Element.dv = dv;
              Element.dv.appendChild(progress_span());
              slrjx_app_getOrgOpt(function(_data) {
                 Element.dv.innerHTML = _data;
                 new Effect.toggle(Element,'blind',{duration:0.2});
                 OrgResetBranches();
              });
            
           }
           return true;
        }
        
        var newHref = null;
        function selorgopt(org_id,org_nm) {
           var Element = _gel('list_org');
           new Effect.toggle(Element,'blind',{duration:0.2});
           slrjx_app_setOrg(org_id,obj_id,null);
           newHref = '".XOCP_SERVER_SUBDIR."/index.php?X_hris="._HRIS_SELECTORG_BLOCK."&org_id='+org_id+'&obj_id='+obj_id;
           setTimeout('gotoOrg();',300);
        }
        
        function gotoOrg() {
           location.href = newHref;
        }

        ".($showOpt==TRUE?"setTimeout('show_org_opt(null,null);',100);":"")."
        
        //]]>\n</script>";
        
        $js .= "\n<script type=\"text/javascript\" src=\"".XOCP_SERVER_SUBDIR."/modules/sms/include/treeorg.js\"></script>";
        
        
        return $js."<div class='orgsel'><table border='0' width='100%' cellpadding='2' cellspacing='0'>
                <tr><td id='hris_org_nm'>Level of Organization : <span style='font-weight:bold;'>$org_nm</span></td>
                <td align='right'>[<span class='xlnk' id='chorgsp' onclick='return show_org_opt(this,event);'>Change Level"
                ."</span>]</td></tr></table><div id='list_org' style='display:none;background-color:#FFFFFF;text-align:left;'></div></div>";
      }
   }

   
   
   
   function main() {
      $db = &Database::getInstance();
      $user_id = getUserID();
      
      $smsselses = new _sms_class_SelectSession();
      $smssel = "<div style='padding-bottom:2px;'>".$smsselses->show()."</div>";
      
     $sql = "SELECT a.org_abbr,a.org_nm,b.org_class_nm FROM ".XOCP_PREFIX."orgs a LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . " WHERE org_id = '".$_SESSION["sms_org_id"]."'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($org_abbr,$org_nm,$org_class_nm)=$db->fetchRow($result);
         $orgsel = "<div style='padding:5px;border:1px solid #bbb;background-color:#ddd;'><span id='orgspan' class='xlnk' onclick='select_org(this,event);'>Level Organization : <span style='font-weight:bold;'>$org_nm $org_class_nm</span></span></div>";
      }
      
      if(!isset($_SESSION["sms_id"])||$_SESSION["sms_id"]==0) {
         return $smssel;
      }
      
      $orgsel = $this->showOrg();
	  
      if(!isset($_SESSION["pms_psid"])||$_SESSION["pms_psid"]==0) {
         return $smssel;
      }
      
      list($self_job_id,
           $self_employee_id,
           $self_job_nm,
           $self_nm,
           $self_nip,
           $self_gender,
           $self_jobstart,
           $self_entrance_dttm,
           $self_jobage,
           $self_job_summary,
           $self_person_id,
           $self_user_id,
           $self_first_assessor_job_id,
           $self_next_assessor_job_id)=_hris_getinfobyuserid($user_id);
      
      switch ($this->catch) {
         case $this->blockID:
            if($_GET["goto"]=="y") {
               $ret = $this->approval($_GET["employee_id"],$_GET["jam_org_ind"],$_GET["jam_org_id"]);
            } else {
               $ret = $this->requestList();
            }
            break;
         default:
            if($_GET["goto"]=="y") {
               $ret = $this->approval($_GET["employee_id"],$_GET["jam_org_ind"],$_GET["jam_org_id"]);
            } else {
               $ret = $this->requestList();
            }
            break;
      }
	  
	$sqljob = "SELECT j.job_id,j.gradeval FROM hris_employee_job j LEFT JOIN hris_users u ON (u.person_id = j.employee_id) WHERE u.user_id = ".getUserid()."";
	$resultjob = $db->query($sqljob);
	 list($job_id,$gradeval)=$db->fetchRow($resultjob);
	//echo 'getuserid = '.getuserid(). '<br> job_id = '.$job_id; exit();
	 	 if ($gradeval > 6){
       return $smssel.$orgsel.$ret;
	  }
	  else
	  {
	  return 'you have no access';
	  }
	  
      return $smssel.$orgsel.$ret;
   }
}

} // PMS_JAMAPPROVAL_DEFINED
?>