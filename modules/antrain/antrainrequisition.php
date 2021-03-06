<?php
//--------------------------------------------------------------------//
// Filename : modules/antrain/antrainrequisition.php                         //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2013-01-22                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('ANTRAIN_PLAN_SS_DEFINED') ) {
   define('ANTRAIN_PLAN_SS_DEFINED', TRUE);

//include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
//include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
include_once(XOCP_DOC_ROOT."/config.php");
global $xocpConfig;

include_once(XOCP_DOC_ROOT."/modules/antrain/class/ajax_requisition.php");
require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
include_once(XOCP_DOC_ROOT."/modules/antrain/class/selectantrainreq.php");

class _antrain_Requisition extends XocpBlock {
   var $catchvar = _ANTRAIN_CATCH_VAR;
   var $blockID = _ANTRAIN_REQUISITION_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = 'TRAINING REQUISITION';
   var $display_comment = TRUE;
   var $data;
   
   function _antrain_Requisition($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */

   }
   
   
   function antrainrequisition() {
      
	  
	  $psid = $_SESSION["pms_psid"];
      global $xocp_vars;
      
      $db=&Database::getInstance();
	  
	//------------------------
		$sqljob = "SELECT j.job_id FROM hris_employee_job j LEFT JOIN hris_users u ON (u.person_id = j.employee_id) WHERE u.user_id = ".getUserid()."";
	$resultjob = $db->query($sqljob);
	 list($job_id)=$db->fetchRow($resultjob);
	
	 	if ($job_id == 999 || $job_id == 130 || $job_id == 68 || $job_id == 147  || $job_id == 101 || $job_id == 90  || $job_id == 146 || $job_id == 129 ||$job_id == 6)
		{
			$divsecid = '';
		}
		 
		  // ADM/DIR
		  elseif ( $job_id == 146 || $job_id == 147) 
			{
				$divsecid = " AND org_id = 14 ORDER BY year";
			}
		 // ADM-PERS
		 elseif ($job_id == 145 || $job_id == 141  ) 
			{
				$divsecid = " AND p.org_id = 14 AND p.org_id_sec = 22 ORDER BY year";
			}
		  //ADM-HRDOM
     	  elseif ($job_id == 130  || $job_id == 68   )
			{
				$divsecid = " AND p.org_id = 14 AND p.org_id_sec = 21 ORDER BY year";
			}
			//ADM-CLINIC
     	  elseif ($job_id == 112 ) //CLERK LANGSUNG PROPOSE?
			{
				$divsecid = " AND p.org_id = 14 AND p.org_id_sec = 60 ORDER BY year";
			}
			 //ADM-GAL
     	  elseif ($job_id == 57  || $job_id == 138  )
			{
				$divsecid = " AND p.org_id = 14 AND p.org_id_sec = 23 ORDER BY year";
			}
				 //ADM-IT
		elseif ($job_id == 140  || $job_id == 138 )
			{
				$divsecid = " AND p.org_id = 14 OR p.org_id_sec = 24 ORDER BY year";
			} 
			
				 //FINANCE-FINANCE
			 
		  elseif ($job_id == 166  ||$job_id == 151  || $job_id == 168  ||  $job_id == 167)
			{
				$divsecid = " AND p.org_id = 15 ";
			}
			
			
			
			//MKT DIR
			elseif ($job_id == 47)
			{
				$divsecid = " AND p.org_id = 16";
			}
			 //MKT-MKT
     	  elseif ($job_id == 42  ||$job_id == 37  || $job_id == 48  )
			{
				$divsecid = " AND p.org_id = 16 AND p.org_id_sec = 26 ORDER BY year";
			}
				
			 //MKT-LOG
     	  elseif ($job_id == 181  || $job_id == 12||$job_id == 169  || $job_id == 48 )
			{
				$divsecid = " AND p.org_id = 16 AND p.org_id_sec = 65 ORDER BY year";
			}
			
			 
			 elseif ( $job_id == 129)
			{
				$divsecid = " AND p.org_id = 19 OR p.org_id = 17 OR p.org_id = 18 OR p.org_id = 20  ORDER BY year";
			}
			
			 //RCP-QA
     	  elseif ($job_id == 110   ||$job_id == 107  || $job_id == 126  )
			{
				$divsecid = " AND p.org_id = 20 AND p.org_id_sec = 41 ORDER BY year";
			}
			
			 //RCP-SHE
     	  elseif ($job_id == 127   ||$job_id == 111  || $job_id == 126  )
			{
				$divsecid = " AND p.org_id = 20 ORDER BY year";
			//$divsecid = " AND org_id = 20 AND org_id_sec = 42 ORDER BY year";
			}
			
			 //RCP-TC
     	  elseif ($job_id == 127   ||$job_id == 111  || $job_id == 126  )
			{
				$divsecid = " AND p.org_id = 20 AND p.org_id_sec = 43 ORDER BY year";
			}
			
			 //MNT-MECH1
     	  elseif ($job_id == 84   ||$job_id == 74  || $job_id == 104  )
			{
				$divsecid = " AND p.org_id = 17 AND p.org_id_sec = 28 ORDER BY year";
			}
			
			 //MNT-MECH2
     	  elseif ($job_id == 89   ||$job_id == 85  || $job_id == 104  )
			{
				$divsecid = " AND p.org_id = 17 AND p.org_id_sec = 28 ORDER BY year";
			}
			
			 //MNT-ELECT
     	  elseif ($job_id == 59   ||$job_id == 49  || $job_id == 104  )
			{
				$divsecid = " AND p.org_id = 17 AND p.org_id_sec = 31 ORDER BY year";
			}
			
		    //MNT-INST
     	  elseif ($job_id == 59   ||$job_id == 60  || $job_id == 104  )
			{
				$divsecid = " AND p.org_id = 17 AND p.org_id_sec = 30 ORDER BY year";
			}
			
			 //MNT-PROC
     	  elseif ($job_id == 103   ||$job_id == 91  || $job_id == 104  )
			{
				$divsecid = " AND p.org_id = 17 AND p.org_id_sec = 27 ORDER BY year";
			}
			
			 //MFG-PRO1
     	  elseif ($job_id == 198   ||$job_id == 183  || $job_id == 6  )
			{
				$divsecid = " AND p.org_id = 18 AND p.org_id_sec = 32 ORDER BY year";
			}
			
			 //MFG-QI/MTS
     	  elseif ($job_id == 208   ||$job_id == 25 || $job_id == 201  || $job_id == 6  )
			{
				$divsecid = " AND p.org_id = 18 AND p.org_id_sec = 37 ORDER BY year";
			}
			
			 //MFG-UTT
     	  elseif ($job_id == 5   ||$job_id == 211 || $job_id == 6  )
			{
				$divsecid = " AND p.org_id = 18 AND p.org_id_sec = 33 ORDER BY year";
			}
			
			//MFG2
     	  elseif ($job_id ==  23   ||$job_id == 220 || $job_id == 36 )
			{
				$divsecid = " AND p.org_id = 19 ORDER BY year";
			}
			
		/* 	//MFG2
     	  elseif ($job_id ==  23   ||$job_id == 220 || $job_id == 36 )
			{
				$divsecid = " AND p.org_id = 19 AND p.org_id_sec = 59 ORDER BY year";
			}
			
			//MFG2-PRO2
     	  elseif ($job_id ==  23   ||$job_id == 220 || $job_id == 36 )
			{
				$divsecid = " AND p.org_id = 19 AND p.org_id_sec = 64 ORDER BY year";
			}
			
			//MFG2-QI/MTS PET 
     	  elseif ($job_id ==  23   ||$job_id == 220 || $job_id == 36)
			{
				$divsecid = " AND p.org_id = 19 AND p.org_id_sec = 39 ORDER BY year";
			} */
			
			
			else
			 {
				$divsecid = '';
			 }
			
	  
	$sqllist = "SELECT p.psid,  p.year,p.org_id, p.budget, p.remark, pk.org_nm FROM antrain_sessionreq p LEFT JOIN  hris_orgs pk ON p.org_id = pk.org_id WHERE p.status_cd = 'normal' $divsecid";	   
    $resultlist = $db->query($sqllist);
   /*$ret = "<table class='xxlist' style='width:100%;' align='center'>"
           . "<thead><tr><td>"
           . "<span style='float:left; width:45px;'>Year</span>"
           . "<span style='float:left; margin-left: 30px;'>Div/Section</span>"
		   . "<span style='float:left; margin-left: 140px;'>Remark</span>"
           . "<span style='float:right;'><input onclick='new_session_req();' type='button' value='"._ADD."'/></span></td></tr>"
           . "</thead><tbody id='tbproc'>";
      if($db->getRowsNum($resultlist)>0) {
         while(list($psidlist,$year,$org_id,$budget,$remarklist,$org_nm_list)=$db->fetchRow($resultlist)) {
            if($year=="") $year = _EMPTY;
			
			$sqlsec = "SELECT pk.org_nm FROM antrain_sessionreq p LEFT JOIN hris_orgs pk ON p.org_id_sec = pk.org_id WHERE p.status_cd =  'normal' AND p.psid = '$psidlist'  ORDER BY YEAR DESC ";	   
		  	$resultsec = $db->query($sqlsec);
			list($org_nm_seclist)=$db->fetchRow($resultsec);
			
			  
            $ret .= "<tr><td id='tdclassreq_$psidlist'>"
                  . "<table><colgroup><col width='60'/><col/></colgroup><tbody><tr>"
                  . "<td width=70 style='text-align: left; font-size: 10px;' ><span id='sp_$psidlist' class='xlnk' onclick='edit_session_req(\"$psidlist\",this,event); '>".htmlentities(stripslashes($year))."</span></td>"
				  . "<td id='td_org_nm_$psidlist}' width=200 style='text-align: left; font-size: 10px;'>$org_nm_list / "
				  . "$org_nm_seclist </td>"
				  . "<td id='td_remark_$psidlist' width=200 style='text-align: left; font-size: 10px;'>$remarklist</td>"
                  . "</tr></tbody></table>"
                  . "</td></tr>";
				  
         }
      }
      $ret .= "<tr></tr><tr style='display:none;' id='trempty'><td>"._EMPTY."</td></tr>";
      $ret .= "</tbody></table>"; */
    
	//------------------------
	  $ret = "";
      $ajax = new _antrain_class_requisitionAjax("orgjx");
      
	  $user_id = getUserID();
      
      $current_year = 2013;

      //$sql = "SELECT p.year, p.is_proposed,p.is_proposeddiv,p.is_approved,p.is_approvedfgm,p.date_proposed,  pk.org_nm, pk.org_abbr, pk.org_class_id FROM antrain_sessionreq p LEFT JOIN hris_orgs pk ON p.org_id = pk.org_id WHERE psid = ('$psid') ORDER BY year DESC " ;
	  $sql = "SELECT a.is_proposed,a.is_proposeddiv,a.is_approved,a.is_approvedfgm,a.date_proposed,c.org_nm,c.org_abbr FROM antrain_sessionreq a LEFT JOIN antrain_budget b ON b.id = a.id_hris_budget LEFT JOIN hris_orgs c ON c.org_id = b.org_id WHERE a.psid = '$psid'";
	  /* "SELECT year,session_nm FROM antrain_sessionreq WHERE psid  = '$psid'"; */
      $result = $db->query($sql);
      list($is_proposed,$is_proposeddiv,$is_approved,$is_approvedfgm,$date_proposed,$org_nm,$org_abbr)=$db->fetchRow($result);
	  
	  $sqlsecnm = "SELECT c.org_nm, c.org_abbr,c.org_class_id FROM antrain_sessionreq a LEFT JOIN antrain_budget b ON b.id = a.id_hris_budget LEFT JOIN hris_orgs c ON c.org_id = b.org_id WHERE a.psid = '$psid'" ;
	  
	  /* "SELECT year,session_nm FROM antrain_sessionreq WHERE psid  = '$psid'"; */
      $resultsecnm = $db->query($sqlsecnm);
      list($org_nm_sec,$org_abbr_sec)=$db->fetchRow($resultsecnm);
	  
   $proposedbutton = "<form id='proposedbutton' method= 'post'><input type='hidden' name='id_proposed' value='$id_propose' ><input type='hidden' name='is_proposed' value='$is_propose' ><input type='hidden' name='date_proposed' value='$dt_propose' ><input type='button' onclick='propose();' value='Propose'  id='propbut'></form>";

   $proposeddivbutton = "<form id='proposeddivbutton' method= 'post'><input type='hidden' name='id_proposeddiv' value='$id_proposeddiv' ><input type='hidden' name='is_proposeddiv' value='$is_proposeddiv' ><input type='hidden' name='date_proposeddiv' value='$dt_proposeddiv' ><input type='button' onclick='proposeddiv();' value='Propose'  id='propbutdiv'></form>";
   
   $approvedbutton = "<form id='approvedbutton' method= 'post'><input type='hidden' name='id_approved' value='$id_approve' ><input type='hidden' name='is_approved' value='$is_approve' ><input type='hidden' name='date_approved' value='$dt_approve' ><input type='button' onclick='approve();' value='Approve'  id='apprbut'></form>";
   
   $approvedfgmbutton = "<form id='approvedbuttonfgm' method= 'post'><input type='hidden' name='id_approvedfgm' value='$id_approvefgm' ><input type='hidden' name='is_approvedfgm' value='$is_approvefgm' ><input type='hidden' name='date_approvedfgm' value='$dt_approvefgm' ><input type='button' onclick='approvefgm();' value='Approve'  id='apprbutfgm'></form>";

	
	  $returnbtn = "<span style='float:right; margin:5px;'><input onclick='return_session(\"$psid\",this,event);' type='button' value='Return to Clerk' id='returnbtn'/></span>";  
	  $returnbtndivmjr = "<span style='float:right; margin:5px;'><input onclick='return_session_divmjr(\"$psid\",this,event);' type='button' value='Return to Section Manager' id='returnbtn'/></span>";  
	  $returnbtndir = "<span style='float:right; margin:5px;'><input onclick='return_session_dir(\"$psid\",this,event);' type='button' value='Return to Division Manager' id='returnbtn'/></span>";  
	  $remindbtn = "<span style='float:right; margin:5px;'><input onclick='remind_btn(\"$psid\",this,event);' type='button' value='Remind' id='remindbtn'/></span>"; 
	  $remindbtnomsm = "<span style='float:right; margin:5px;'><input onclick='remind_btn_omsm(\"$psid\",this,event);' type='button' value='Remind' id='remindbtnomsm'/></span>"; 	 
	  $addbtn =	"<span style='float:right; margin:5px;'><input onclick='new_session();' type='button' value='Add'/></span>";
	  $createsignbtn = "<form id='createsignbutton' method= 'post'><input type='hidden' name='id_createsign' value='$id_created' ><input type='hidden' name='is_created' value='$is_created' ><input type='hidden' name='date_approvedfgm' value='$dt_created' ><input type='button' onclick='createsign();' value='Signature'  id='createsignbut'></form>";

	  
	  
	if ($sumcost == 0)
	{
		$sumcost = '0' ;
	}
	else
	{
		$sumcost = $sumcosts;
	}
	
	$sqljob = "SELECT j.job_id, a.job_class_id FROM hris_employee_job j LEFT JOIN hris_users u ON ( u.person_id = j.employee_id ) LEFT JOIN hris_jobs a ON ( j.job_id = a.job_id ) WHERE u.user_id =  ".getUserid()."";
	$resultjob = $db->query($sqljob);
	 list($job_id,$job_class_id)=$db->fetchRow($resultjob);
	//echo 'getuserid = '.getuserid(). '<br> job_id = '.$job_id; exit();
	
//PROPOSE
	//if ( $job_id == 0 || $job_id == 999|| $job_id == 141|| $job_id == 68|| $job_id == 138|| $job_id == 132|| $job_id == 151|| $job_id == 37|| $job_id == 169|| $job_id == 107|| $job_id == 111|| $job_id == 74|| $job_id == 85|| $job_id == 49|| $job_id == 60|| $job_id == 91|| $job_id == 183|| $job_id == 25|| $job_id == 211|| $job_id == 220 ) {
	
	if ( $job_class_id == 3 || $job_id == 0 || $job_id == 999|| $job_id == 141|| $job_id == 68|| $job_id == 138|| $job_id == 132|| $job_id == 151|| $job_id == 37|| $job_id == 169|| $job_id == 107|| $job_id == 111|| $job_id == 74|| $job_id == 85|| $job_id == 49|| $job_id == 60|| $job_id == 91|| $job_id == 183|| $job_id == 25|| $job_id == 211|| $job_id == 220 ) {
	  
		 if ($is_proposed == '1')
		{
			$proposedbutton = '';
			$remindbtn = '';
			$returnbtn = '';
		
		}	
		else
		{
		$proposedbutton = $proposedbutton;  
		$returnbtn = $returnbtn;
		$remindbtnomsm = $remindbtnomsm;
	
		} 
	 
	 }
	 else
	 {
		$proposedbutton = '';
		$returnbtn = '';
		$remindbtnomsm = '';
	 }
	 
	 //Return to SM
	 if( $job_class_id == 2 || $job_id == 0 || $job_id == 999|| $job_id == 6|| $job_id == 36|| $job_id == 48|| $job_id == 104|| $job_id == 126|| $job_id == 147|| $job_id == 168 AND $is_proposed == 1)
	 {
		$returnbtndivmjr = $returnbtndivmjr;
	 }
	 else{
		$returnbtndivmjr = '';
	 }
	 
	 //Return to DIv Manager
	 if( $job_class_id == 1 ||  $job_id == 0 || $job_id == 999|| $job_id == 47|| $job_id == 146|| $job_id == 167|| $job_id == 129 AND $is_proposeddiv == 1)
	 {
		$returnbtndir = $returnbtndir;
	 }
	 else{
		$returnbtndir =  '';
	 }
	 

	 
	 //PROPOSEDIV
	  if ($job_class_id == 2 || $job_id == 0 || $job_id == 999|| $job_id == 6|| $job_id == 36|| $job_id == 48|| $job_id == 104|| $job_id == 126|| $job_id == 147|| $job_id == 168 || $job_id == 146 )
	  
	  {
	 
	 
	 if($is_proposeddiv == 1 || $is_proposed == 0 )
	 {
		$proposeddivbutton = '';
	 }
	 else
	 {
		$proposeddivbutton = $proposeddivbutton;
	 }
	 
	 }
	 else
	 {
		$proposeddivbutton = '';
	 
	 }
	 

	$sqlmax = "SELECT inst FROM antrain_requisition WHERE id = (SELECT MAX( id ) FROM antrain_requisition WHERE id_antrain_session = $psid ) ORDER BY id DESC"; 
	$resultmax = $db->query($sqlmax);
	 list($remarksplan)=$db->fetchRow($resultmax);
	 
 //APPROVED
	  if ( $job_id == 999 || $job_id == 0 || $job_id == 146 )
	   {
			if( $remarksplan == 'int' AND $is_proposeddiv == 1  )
			{
			$approvedbutton = $approvedbutton;
			}
			else
			{
				$approvedbutton = '';
			}
		}
		else
		{
			$approvedbutton = '';
		}

//APPROVEDFGM  
	 
	if (  $job_id == 999|| $job_id == 0|| $job_id == 129 )
	   {
			if($remarksplan == 'ext' AND $is_proposeddiv == 1   )
			{
			$approvedfgmbutton = $approvedfgmbutton;
			}
			else
			{
				$approvedfgmbutton = '';
			}
		}
		else
		{
			$approvedfgmbutton = '';
		}		

 
	 
	if ( $job_class_id == 12 || $job_id == 999|| $job_id == 145|| $job_id == 130 || $job_id == 112|| $job_id == 57|| $job_id == 166|| $job_id == 42|| $job_id == 181 || $job_id == 12|| $job_id == 110|| $job_id == 127|| $job_id == 125 || $job_id == 84|| $job_id == 89|| $job_id == 59|| $job_id == 103|| $job_id == 198|| $job_id == 208|| $job_id == 5|| $job_id == 23|| $job_id == 34 )
	{
		$remindbtnomsm =  $remindbtnomsm;
	}
	else
	{
		$remindbtnomsm = '';
	}

	
    
	$tooltip = "<div id='all_ap_tooltip' style='display:none;'>";
      

      $ret .= "<div>";
      
     /*  $ret .= $return_notes;
      $ret .= $report_return_notes;
       */
      $ret .= "<table style='margin-top:5px;table-layout:fixed;font-size:11px;font-weight:bold;' class='xxlist'>";
      
      $ret .= "<div style='padding:5px;font-size:20px;color: #666666; text-align:center;'>TRAINING REQUISITION </div>"
			 //. "<div style='padding:5px;font-size:15px;color: #666666; text-align:center;'>(Specific Subject /Year $year )</div>"
			 . "<div style='padding:2px;font-size:12px;color: #666666; text-align:left;'>"
			 //. "Division/ Section:  $org_nm / $org_nm_sec"
			 . "$addbtn $remindbtnomsm $remindbtn $returnbtn $returnbtndivmjr $returnbtndir <div id='id_return'></div><div id='id_return_divmjr'></div><div id='id_return_dir'></div></div>"
			 
			. "<colgroup>
				<col width='3%'/>
				<col width='9%'/>
				<col width='8%'/>
				<col width='7%'/>
				<col width='10%'/>
				<col width='6%'/>
				<col width='6%'/>
				<col width='6%'/>
				<col width='10%'/>
				<col width='5%'/>
				<col width='5%'/>
				<col width='5%'/>
				</colgroup>"
			
			. "<thead>
					<tr>
						<td rowspan='3' style='border-right: 1px solid #CCC; text-align: center;'>No.</td>
						<td style='border-right: 1px solid #CCC; text-align:center; ' colspan='3'>EMPLOYEE (S)</td>
						<td style='border-right: 1px solid #CCC;  text-align:center;' colspan='7'>TRAINING REQUEST</td>
						<td style='border-right: 1px solid #CCC;  text-align:center;' colspan='1' rowspan ='3'>Attached File</td>
					
					</tr>"
				. "<tr>
					<td style='border-right: 1px solid #CCC;  text-align:center;' rowspan='2'>Name</td>
					<td style='border-right: 1px solid #CCC; text-align:center;' rowspan='2'>Position</td>
					<td style='border-right: 1px solid #CCC; text-align:center;' rowspan='2'>Section</td>
					<td style='border-right: 1px solid #CCC;  text-align:center;' rowspan='2'>Subject</td>
					<td style='border-right: 1px solid #CCC; text-align:center;' colspan='3'>Schedule</td>
					<td style='border-right: 1px solid #CCC; text-align:center;' rowspan='2'>Institution</td>
					<td style='border-right: 1px solid #CCC; text-align:center;' colspan='2' rowspan='2'>Remarks*</td>
				</tr>"
			. "<tr>
						<td style='border-right: 1px solid #CCC; text-align:center;'>Date</td>
							<td style='border-right: 1px solid #CCC; text-align:center;'>Place</td>
							<td style='border-right: 1px solid #CCC; text-align:center;'>Cost</td>
					
				</tr>
			</thead>";
   
      $ret .= "<tbody>";

      if (isset($_GET["reqid"])) {
    	$reqid = $_GET["reqid"];
      	$reqfilter = "AND p.id = '$reqid'";
      }else{
      	$reqfilter = "";
      }  
      
	$sql = "SELECT p.id, p.name, p.subject, p.objectives, p.schedule_start, p.schedule_end,p.place, p.cost, p.usd, p.id_job_class1, p.id_job_class2, p.remark, p.inst, p.institution,ps.job_class_nm, ps2.job_class_nm AS job_class_nm2,p.file_nm,p.request_id
FROM antrain_requisition p LEFT JOIN antrain_sessionreq pk ON p.id_antrain_session = pk.psid LEFT JOIN hris_job_class ps ON p.id_job_class1 = ps.job_class_id LEFT JOIN hris_job_class ps2 ON p.id_job_class2 = ps2.job_class_id WHERE p.id_antrain_session = '$psid' AND p.is_deleted = 'F' $reqfilter";
	$result = $db->query($sql);
	  if($db->getRowsNum($result)>0) {
	while(list($id,$name,$subject,$objectives,$schedule_start,$schedule_end,$place,$cost,$usd,$id_job_class1,$id_job_class2, $remark,$inst,$institution, $hris_job_class,$hris_job_class2,$file_nm,$request_id)=$db->fetchRow($result)){
	 //return array($id,$name,$subject,$objectives,$schedule_start,$schedule_end,$cost,$id_job_class1,$id_job_class2,$remark,$inst);	
		$ins_int = 'Planned';
			
			   
			   if ($request_id == 0) 
				{
					$inst = $ins_int;
				}
				elseif ($inst =='int')
				{
				  $ins_ext = ' '; 
				}
				else 
				{
					$ins_int = ' ';
				
				}
				
				$ins_ext = 'Unplanned';
				if ($request_id != 0) 
				{
					$inst = $ins_ext;
				}
				elseif ($inst =='ext')
				{
				  $ins_int = ' '; 
				}
				else 
				{
					$ins_ext = ' ';
				
				}
				
		  $schedule_start = date('d M Y', strtotime($schedule_start));
		  $schedule_end = date('d M Y', strtotime($schedule_end));
		  $numberloop = $numberloop + 1;

				  if($id_job_class2 <> 0 && $id_job_class2 <> NULL ) {
				  $participantname = htmlentities(stripslashes($hris_job_class))." - ".htmlentities(stripslashes($hris_job_class2));
				  }else{
				   $participantname = htmlentities(stripslashes($hris_job_class));
				  }
			$editlink = "class='xlnk' onclick='edit_session(\"$id\",this,event)";

				if($is_proposed == 1 AND $is_proposeddiv == 1 AND ($is_approved == 1 OR  $is_approvedfgm==1))
				{
				$editlink = '';
				}
				else
				{
				$editlink = $editlink;
				}
				
				if($file_nm == '')
				{
					$downbut = '';
				}				
				else{
				$downbut = "<a href='http://".$_SERVER['HTTP_HOST']."".XOCP_SERVER_SUBDIR."/modules/antrain/upload/$file_nm' target='_blank'>Download</a>";
				}
			//edit by andi
			  $ret .= "<tr><td id='tdclass_${id}' style='margin: 0px; padding:0px;' colspan=12 >"
				. "<table style='width:100%; font-size:11px;'>
						<colgroup>
							<col width='4%'/>
							<col width='11%'/>
							<col width='10%'/>
							<col width='9%'/>
							<col width='13%'/>
							<col width='7%'/>
							<col width='8%'/>
							<col width='7%'/>
							<col width='12%'/>
							<col width='6%'/>
							<col width='6%'/>
							<col width='7%'/>

						</colgroup>
						<tbody>
							<tr>"
								. "<td id='td_num_loop_${id}' style='text-align:center;width:50px'>$numberloop</td>"
								. "<td id='td_name_${id}' $editlink '>$name</td>"
								. "<td><span id='sp_${id}' ; '>".$participantname ."</span></td>"
								. "<td><span id='orgs_${id}' ; '>$org_nm_sec </span></td>"
								. "<td id='td_subject_${id}' style='text-align: left;'>$subject</td>"
								. "<td id='td_schedule_${id}' style='text-align: center; font-size:10px;'>$schedule_start - $schedule_end</td>"
								. "<td id='td_place_${id}' style='text-align: center;' >$place</td>"
								. "<td id='td_cost_${id}' style='text-align: right; padding-right: 10px;'>$ $cost</td>"
								. "<td id='td_institution_${id}' style='text-align: center;'>$institution</td>"
								. "<td id='td_ins_int_${id}' style='text-align: center;' colspan='2'>$ins_int $ins_ext</td>"
								//. "<td id='td_ins_ext_${id}' style='text-align: center;'>$ins_ext</td>"
								. "<td id='td_attach_${id}' style='text-align: center;'>$downbut</td>"
							. "</tr>
					</tbody>
				</table>"
        . "</td></tr>";
				  
			}
	
		 }
	
	/*$sqlbdgt = "SELECT budget"
				.   " FROM antrain_sessionreq"
				.	" WHERE psid='$psid'";*/
	$sqlbdgt = "SELECT a.budget_specific FROM antrain_budget a LEFT JOIN antrain_sessionss b ON b.id_hris_budget = a.id WHERE b.psid = '$psid'";
	$resultbdgt = $db->query($sqlbdgt);
	list($budget)=$db->fetchRow($resultbdgt);
	
	$sql = "SELECT SUM(cost) FROM antrain_requisition WHERE id_antrain_session = '$psid' AND is_deleted = 'F'";
	$result = $db->query($sql);
	list($sumcost)=$db->fetchRow($result);
	$sumcosts = $sumcost;
	
	if ($sumcost == 0)
	{
		$sumcost = '0' ;
	}
	else
	{
		$sumcost = $sumcosts;
	}

	$diff = $budget - $sumcost;
	
		
	$ret .= "<tr style='display:none;' id='tremptyses'><td>"._EMPTY."</td></tr>"; 

	  $ret .= "</tbody>";
      $ret .= "</table>";
      $ret .= "</div>";
     
   
     $sql = "SELECT p.user_id,ps.person_nm,pk.date_proposed FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id LEFT JOIN antrain_sessionreq pk ON p.user_id = pk.id_proposed WHERE pk.psid = '$psid'";  
     $result = $db->query($sql);
     list($user_id_proposed,$proposedby,$date_proposed)=$db->fetchRow($result);
     $date_proposed = date('d/M/Y', strtotime($date_proposed));
	 
	 $sql = "SELECT p.user_id,ps.person_nm,pk.date_proposeddiv FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id LEFT JOIN antrain_sessionreq pk ON p.user_id = pk.id_proposeddiv WHERE pk.psid = '$psid'";  
     $result = $db->query($sql);
     list($user_id_proposeddiv,$proposeddivby,$date_proposeddiv)=$db->fetchRow($result);
     $date_proposeddiv = date('d/M/Y', strtotime($date_proposeddiv));
	 
	 
	 $sql = "SELECT p.user_id,ps.person_nm,pk.date_approved FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id LEFT JOIN antrain_sessionreq pk ON p.user_id = pk.id_approved  WHERE pk.psid = '$psid'";  
     $result = $db->query($sql);
     list($user_id_approved,$approvedby,$date_approved)=$db->fetchRow($result);
     $date_approved = date('d/M/Y', strtotime($date_approved));
	 
	 $sql = "SELECT p.user_id,ps.person_nm,pk.date_approvedfgm FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id LEFT JOIN antrain_sessionreq pk ON p.user_id = pk.id_approvedfgm  WHERE pk.psid = '$psid'";  
     $result = $db->query($sql);
     list($user_id_approvedfgm,$approvedfgmby,$date_approvedfgm)=$db->fetchRow($result);
     $date_approvedfgm = date('d/M/Y', strtotime($date_approvedfgm));
	 
	  $sqlclerk = "SELECT p.user_id,ps.person_nm,pk.date_created FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id LEFT JOIN antrain_sessionreq pk ON p.user_id = pk.id_created WHERE pk.psid = '$psid'";  
     $resultclerk = $db->query($sqlclerk);
     list($user_id_created,$createdby,$date_created)=$db->fetchRow($resultclerk);
     $date_created = date('d/M/Y', strtotime($date_created));
	 
	 if($date_proposed == '01/Jan/1970')
	 {
		$date_proposed = '-';
	 }
	 else
	 {
	 $date_proposed = $date_proposed;
	 }
	 
	  if($date_proposeddiv == '01/Jan/1970')
	 {
		$date_proposeddiv = '-';
	 }
	 else
	 {
	 $date_proposeddiv = $date_proposeddiv;
	 }
	 
	  if($date_approved == '01/Jan/1970')
	  {
		$date_approved = '-';
	 }
	 else
	 {
	 $date_approved = $date_approved;
	 }
	 
	  if($date_approvedfgm == '01/Jan/1970')
	  {
		$date_approvedfgm = '-';
	 }
	 else
	 {
	 $date_approvedfgm = $date_approvedfgm;
	 }
	 
	 	 //CREATEDBY BUTTON
	 if ( $job_id == 999 || $job_id == 0 || $job_id == 130 )
	   {
			if( $createdby == ''  )
			{
			$createsignbtn = $createsignbtn;
			}
			else
			{
				$createsignbtn = '' ;
			}
		}
		else
		{
			$createsignbtn = '';
		}
	 
	 $form .= "<div style='text-align:right;padding:10px;margin-top:10px; margin-bottom: 5px; height: 90px;'>"
				. "<table width='350' align='left' style='border-top:2px solid #777;border-left: 2px solid #777;border-spacing:0px;'>"
                . "<colgroup>"
					. "<col width='30%'/>"
					. "<col width='30%'/>"
					. "<col width='40%'/>"
				. "</colgroup>"
                . "<tbody>"
                . "<tr>"
                . "<td colspan='3' style='text-align:center; font-weight:bold;border-bottom:2px solid #777;border-right: 2px solid #777;'>"
                . "Plan Budget Information Control"
                . "</td>"
               
                . "</tr>"
                . "<tr>"
					. "<td colspan='2' style='text-align:center;border-bottom:1px solid #777;border-right:1px solid #777; font-size: 10px;'>"
					
					. "</td>"
					 . "<td style='text-align:center;border-bottom:1px solid #777;border-right:2px solid #777; font-size: 10px;'>"
					 //. "Signature"
					 . "</td>"
                 . "</tr>"
				 
				   . "<tr>"
					. "<td style='text-align:left;border-bottom:1px solid #777;border-right:1px solid #777; font-size: 10px;'>"
					."Budget"
					. "</td>"
					 . "<td style='text-align:right;border-bottom:1px solid #777;border-right:1px solid #777; font-size: 10px;'>"
					 . "$ $budget"
					 . "</td>"
					  . "<td id=createsign_${psid} rowspan ='2'  style='text-align:center;border-bottom:1px solid #777;border-right:2px solid #777; font-size: 10px;'>"
					 //."$createsignbtn "
					 //. "$createdby"
					 . "</td>"
                 . "</tr>"
				 
				   . "<tr>"
					. "<td style='text-align:left;border-bottom:1px solid #777;border-right:1px solid #777; font-size: 10px;'>"
					."Actual"
					. "</td>"
					 . "<td style='text-align:right;border-bottom:1px solid #777;border-right:1px solid #777; font-size: 10px;'>"
					 . "$ $sumcost"
					 . "</td>"
			      . "</tr>"
				 
				   . "<tr>"
					. "<td style='text-align:left;border-bottom:2px solid #777;border-right:1px solid #777; font-size: 10px;'>"
					."Difference"
					. "</td>"
					 . "<td style='text-align:right;border-bottom:2px solid #777;border-right:1px solid #777; font-size: 10px;'>"
					 . "$ $diff "
					 . "</td>"
					  . "<td id=datecreatesign_${psid} style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777; font-size: 10px;'>"
					 //. "Date:  $date_created"
					 . "</td>"
                 . "</tr>"
  
                . "</tbody>"
                . "</table>"               
				 ."</div>"
				 
				. "<div style='text-align:right;padding:10px;margin-top:0px;margin-bottom:100px;'>"
				. "<table align='left' style='border-top:none solid #777;border-left:none solid #777;border-spacing:0px;'>"
                . "<colgroup>"
                . "<col width='500'/>"
              
                . "</colgroup>"
                . "<tbody>"
                . "<tr>"
                . "<td style='text-align:left;border-bottom:none solid #777;border-right:none solid #777;'>"
                //. "Note:"
                . "</td>"
               
                . "</tr>"
                . "<tr>"
                . "<td style='text-align:left;border-bottom:none solid #bbb;border-right:none solid #777; font-size: 10px;'>"
                //. " &nbsp &nbsp &nbsp * Refer to annual training/seminar plan <br/>"
				//. " &nbsp &nbsp &nbsp ** For unplanned training/seminar <br/>"
                . "</td>"
         
                
                . "</tr>"
  
                . "</tbody>"
                . "</table>"               

			   . "<table align='right' style='border-top:2px solid #777;border-left:2px solid #777;border-spacing:0px;'>"
                . "<colgroup>"
                . "<col width='155'/>"
				. "<col width='155'/>"
                . "<col width='155'/>"
				. "<col width='155'/>"
                . "</colgroup>"
                . "<tbody>"
                . "<tr>"
                . "<td colspan='2' style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;'>"
                . "Proposed by,"
                . "</td>"
                . "<td  colspan='2' style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;'>"
                . "Approved by,"
                . "</td>"
                . "</tr>"
				
				 . "<tr>"
                . "<td height=60 style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
				. "<span style= margin:5px;'>$proposedbutton</span>"
                . "</td>"
				   . "<td style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
				. "<span style= margin:5px;'>$proposeddivbutton</span>"
                . "</td>"
                . "<td style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
              . "<span style= margin:5px;'>$approvedbutton</span>"
                . "</td>"
				. "<td style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
              . "<span style= margin:5px;'>$approvedfgmbutton</span>"
                . "</td>"
                
                . "</tr>"
				
                . "<tr>"
                . "<td id=proposed_${psid} style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
                . "$proposedby"
                . "</td>"
				 . "<td id=proposeddiv_${psid} style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
                . "$proposeddivby"
                . "</td>"
				. "<td id=approved_${psid} style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
				. "$approvedby"
                . "</td>"
				. "<td id=approvedfgm_${psid} style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
				. "$approvedfgmby"
                . "</td>"
                
                
                . "</tr>"
                . "<tr>"
                . "<td id=dateproposed_${psid} style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;padding:5px;' id='submited_by_button'>"
                . "Submitted on:<br/> $date_proposed"
                . "</td>"
				. "<td id=dateproposeddiv_${psid} style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;padding:5px;' id='submited_by_button'>"
                . "Submitted on:<br/> $date_proposeddiv"
                . "</td>"
               . "<td id=dateapproved_${psid} style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;padding:5px;' id='submited_by_button'>"
                 . "Approved on:<br/> $date_approved"
				. "</td>"
				. "<td id=dateapprovedfgm_${psid} style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;padding:5px;' id='submited_by_button'>"
                 . "Approved on:<br/> $date_approvedfgm"
				. "</td>"
                
                . "</tr>"
                . "</tbody>"
                . "</table>"
                . "</div>";
      
      
      
      
      $js = $ajax->getJs()."<script type='text/javascript' src='".XOCP_SERVER_SUBDIR."/include/calendar.js'></script>
	  <script type='text/javascript'>//<![CDATA[
      
      
      //////////////////////////////////////////////////////////////////////////////
	

 function uplfile(id) {
       pdl = _dce('div');
         pdl.setAttribute('id','pdl');
         pdl.bg = _dce('div');
         pdl.bg.setAttribute('style','height:150%;width:150%;position:fixed;top:0px;left:-10px;background-color:#000000;opacity:0.5;z-index:10000');
         pdl.bg = document.body.appendChild(pdl.bg);
			
		pdl.setAttribute('style','text-align:center;padding-top:15px;padding-bottom:15px;width:400px;position:fixed;top:50%;left:50%;background-color:white;border:1px solid #555555;margin-left:-200px;margin-top:-100px;opacity:1;z-index:10001;');
         pdl.innerHTML = '<div style=\"background-color:#5666b3;padding:4px;margin:5px;color:#ffffff;width:366px;margin-left:15px;\">Upload File</div>'
                       + '<iframe src=\"".XOCP_SERVER_SUBDIR."/modules/antrain/class/uploadfile.php?id=\"style=\"width:370px;border:0px solid black;overflow:visible;\"></iframe>';
         pdl = document.body.appendChild(pdl);
         pdl.cancelUpload = function() {
            _destroy(pdl);
            _destroy(pdl.bg);
         };
      }
	
//TRAINING REQ NEW
	  function new_session() {
         if(wdv) {
            cancel_edit();
         }
        var tre = $('tremptyses');
         var tr = _dce('tr');
         var td = tr.appendChild(_dce('td'));
         tr = tre.parentNode.insertBefore(tr,tre);
         wdv = _dce('div');
         wdv.td = td;
         orgjx_app_newSession(function(_data) {
            var data = recjsarray(_data);
            wdv.td.setAttribute('id','tdclass_'+data[0] );
            wdv.td.innerHTML = data[1];
            wdv.td = null;
            wdv = null;
            edit_session(data[0],null,null);
         });
      }


//SESSION REQ NEW
 function new_session_req() {
         if(wdv) {
            cancel_edit();
         }
        var tre = $('trempty');
         var tr = _dce('tr');
         var td = tr.appendChild(_dce('td'));
         tr = tre.parentNode.insertBefore(tr,tre);
         wdv = _dce('div');
         wdv.td = td;
         orgjx_app_newSessionreq(function(_data) {
            var data = recjsarray(_data);
            wdv.td.setAttribute('id','tdclassreq_'+data[0]);
            wdv.td.innerHTML = data[1];
            wdv.td = null;
            wdv = null;
            edit_session_req(data[0],null,null);
         });
      }
	  
		       var wdv = null;
		      
	  //TRAINING REQ EDIT 
	  function edit_session(id,d,e) {
         if(wdv) {
            if(wdv.id == id) {
               cancel_edit();
               return;
            } else {
               cancel_edit();
            }
         }
         wdv = _dce('div');
         wdv.id = id;
         var td = $('tdclass_'+id);
         wdv.setAttribute('style','padding:10px;');
         td.setAttribute('colspan','12');
         td.setAttribute('style','padding:0px;');
		 wdv = td.appendChild(wdv);
         wdv.appendChild(progress_span());
         wdv.td = td;
         orgjx_app_editSession(id,function(_data) {
            wdv.innerHTML = _data;
            //$('inp_remark').focus();
         });
      }
			var wdv = null;
	
  //SESSION REQ EDIT
	    function edit_session_req(psid,d,e) {
         if(wdv) {
            if(wdv.psid == psid) {
               cancel_edit();
               return;
            } else {
               cancel_edit();
            }
         }
         wdv = _dce('div');
         wdv.psid = psid;
         var td = $('tdclassreq_'+psid);
         wdv.setAttribute('style','padding:10px;');
         wdv = td.appendChild(wdv);
         wdv.appendChild(progress_span());
         wdv.td = td;
         orgjx_app_editsessionreq(psid,function(_data) {
            wdv.innerHTML = _data;
            //$('inp_year').focus();
         });
      }
	  
	  //SESSION REQ SAVE
	       function save_sessionreq() {
         var ret = parseForm('frm');
         $('progress').appendChild(progress_span('&nbsp;...saving&nbsp;&nbsp;'));
         orgjx_app_saveSessionreq(wdv.psid,ret,function(_data) {
            var data = recjsarray(_data);
            $('sp_'+data[0]).innerHTML = data[1];
			//$('td_org_nm_'+data[0]).innerHTML = data[2] + ' / ' + data[4];
			$('td_remark_'+data[0]).innerHTML = data[3];
			$('inp_year').focus();
            setTimeout(\"$('progress').innerHTML = '';\",1000);
			location.reload();
         });
      }
	  
//SESSION DELETE ASK
	  function delete_sessionreq() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '#ffcccc';
         wdv.oldHTML = wdv.innerHTML;
         wdv.innerHTML = 'Are you sure you want to delete this session?<br/><br/>'
                       + '<input type=\"button\" value=\""._CANCEL."\" onclick=\"cancel_delete_req();\"/>&nbsp;&nbsp;'
                       + '<input type=\"button\" value=\""._DELETE."\" onclick=\"do_deletereq();\"/>';
						
      }
      
	  //SESSION REQ DELETE  
	    function do_delete_req() {
         orgjx_app_Deletereq(wdv.psid,null);
         var tr = wdv.parentNode.parentNode;
         _destroy(tr);
         wdv.psid = null;
         wdv = null;
      }
	  
	  function propose() {
       // alert('test');
		var ret = parseForm('proposedbutton');
         //$('progress').appendChild(progress_span('&nbsp;...saving&nbsp;&nbsp;'));
		orgjx_app_propose(ret,function(_data) {
		//alert('test');
            var data = recjsarray(_data);
			//alert(data[5]);
			 $('proposed_'+data[0]).innerHTML = data[4];
			 $('dateproposed_'+data[0]).innerHTML = 'Submitted on: ' +'<br>' + data[3];
			 $('propbut').setAttribute('style','display:none;');
			//setTimeout(\"$('progress').innerHTML = '';\",1000);
			location.reload();
         });
      }
	  
	   function proposeddiv() {
       // alert('test');
		var ret = parseForm('proposedbutton');
         //$('progress').appendChild(progress_span('&nbsp;...saving&nbsp;&nbsp;'));
		orgjx_app_proposediv(ret,function(_data) {
		//alert('test');
            var data = recjsarray(_data);
			//alert(data[5]);
			 $('proposeddiv_'+data[0]).innerHTML = data[4];
			 $('dateproposeddiv_'+data[0]).innerHTML = 'Submitted on: ' +'<br>' + data[3];
			 $('propbutdiv').setAttribute('style','display:none;');
			//setTimeout(\"$('progress').innerHTML = '';\",1000);
			location.reload();
         });
      }
	  
	  function approve() {
      // alert('test');
		var ret = parseForm('approvedbutton');
         //$('progress').appendChild(progress_span('&nbsp;...saving&nbsp;&nbsp;'));
		orgjx_app_approve(ret,function(_data) {
		//alert('test');
            var data = recjsarray(_data);
			$('approved_'+data[0]).innerHTML = data[4];
			$('dateapproved_'+data[0]).innerHTML = 'Approved on: ' +'<br>'+ data[3];
			$('apprbut').setAttribute('style','display:none;');
			//setTimeout(\"$('progress').innerHTML = '';\",1000);
         });
      }
	  
	   function approvefgm() {
      // alert('test');
		var ret = parseForm('approvedfgmbutton');
         //$('progress').appendChild(progress_span('&nbsp;...saving&nbsp;&nbsp;'));
		orgjx_app_approvefgm(ret,function(_data) {
		//alert('test');
            var data = recjsarray(_data);
			$('approvedfgm_'+data[0]).innerHTML = data[4];
			$('dateapprovedfgm_'+data[0]).innerHTML = 'Approved on: ' +'<br>'+ data[3];
			$('apprbutfgm').setAttribute('style','display:none;');
			//setTimeout(\"$('progress').innerHTML = '';\",1000);
         });
      }
	  
	  	   function createsign() {
     		var ret = parseForm('createsignbutton');
         //$('progress').appendChild(progress_span('&nbsp;...saving&nbsp;&nbsp;'));
		orgjx_app_createsign(ret,function(_data) {
		//alert('test');
            var data = recjsarray(_data);
			$('createsign_'+data[0]).innerHTML = data[3];
			$('datecreatesign_'+data[0]).innerHTML = 'Date: ' +' '+ data[2];
			$('createsignbut').setAttribute('style','display:none;');
			//setTimeout(\"$('progress').innerHTML = '';\",1000);
			location.reload();
         });
      }
	  
      
      function cancel_edit() {
         wdv.td.style.backgroundColor = '';
         if(wdv.id=='new') {
            _destroy(wdv.td.parentNode);
         }
         wdv.id = null;
         _destroy(wdv);
         wdv = null;
      }
      
      function delete_session() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '#ffcccc';
         wdv.oldHTML = wdv.innerHTML;
         wdv.innerHTML = 'Are you sure you want to delete this session?<br/><br/>'
                       + '<input type=\"button\" value=\""._CANCEL."\" onclick=\"cancel_delete();\"/>&nbsp;&nbsp;'
                       + '<input type=\"button\" value=\""._DELETE."\" onclick=\"do_delete();\"/>';
						
      }
      
	 
	  
      function cancel_delete() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '';
         wdv.innerHTML = wdv.oldHTML;
      }
      
      function do_delete() {
         orgjx_app_Delete(wdv.id,null);
         var tr = wdv.parentNode.parentNode;
         _destroy(tr);
         wdv.id = null;
         wdv = null;
		setInterval(function(){location.reload()},1500);
		}
	  
	  //DELETE REQ SESSION
	     function do_deletereq() {
        orgjx_app_Deletereq(wdv.psid,null);
         var tr = wdv.parentNode.parentNode;
         _destroy(tr);
         wdv.psid = null;
         wdv = null;
     }
      
      function save_session() {
         var ret = parseForm('frm');
         $('progress').appendChild(progress_span('&nbsp;...saving&nbsp;&nbsp;'));
		orgjx_app_saveSession (wdv.id,ret,function(_data) {
            var data = recjsarray(_data);
			var participantname = null;
			if (data[8] != 0 && data[8] != null) {
			participantname = data[12] + '-' +data[13];
			}else{
			participantname = data[12] ;
			}
			
			var internal = null;
			var external = null;
			if (data[10] == 'ext')
			{
				internal = '';
				external = '&#9679;';
			
			} else {
				internal = '&#9679;';
				external = '';
			}
			
			var attach = null;
			if (data[14] == '')
			{
				attach = '';
			}
			else
			{
				attach = '<a href=./modules/antrain/upload/' + data[14] +'>Download</a>';
			}
			
			$('td_name_'+data[0]).innerHTML = data[1];
			$('sp_'+data[0]).innerHTML = participantname;
			$('td_subject_'+data[0]).innerHTML = data[2];
		//$('td_objectives_'+data[0]).innerHTML = data[3];
			$('td_schedule_'+data[0]).innerHTML = data[3] + ' - ' +data[4];
			$('td_place_'+data[0]).innerHTML = data[5];
			$('td_cost_'+data[0]).innerHTML = '$ '+data[6];
			$('td_institution_'+data[0]).innerHTML = data[7];
			//$('td_ins_int_'+data[0]).innerHTML = internal;		
			//$('td_ins_ext_'+data[0]).innerHTML = external;
			$('td_attach_'+data[0]).innerHTML = attach;

		//$('td_remark_'+data[0]).innerHTML = data[9];
        //    $('inp_remark').focus();
			setTimeout(\"$('progress').innerHTML = '';\",1000);
		//location.reload();
         });
      }
	  
	  var wdv = null;
      function remind_btn(psid,d,e) {
         if(wdv) {
            if(wdv.psid == psid) {
               cancel_return();
               return;
            } else {
               cancel_return();
            }
         }
		 // alert('test');
		$('id_return').setAttribute('style','display:inline;')
         // wdv = _dce('div');
         // wdv.psid = psid;
         // var td = $('returnbtn');
         // wdv.setAttribute('style','padding:10px;');
         // wdv = td.appendChild(wdv);
        // wdv.appendChild(progress_span());
         // wdv.td = td;
         orgjx_app_reminder(psid,function(_data) {
           // wdv.innerHTML = _data;
		    $('id_return').innerHTML = _data;
           // $('inp_year').focus();
         });
      }
	  
	   function send_reminder() {
       
         //$('progress').appendChild(progress_span('&nbsp;...saving&nbsp;&nbsp;'));
		 // alert('test');
         orgjx_app_send_reminder(function(_data) {
		
            var data = recjsarray(_data);
			//if(data[0] == 1){
				alert(data[0]);
			//}else{
				//alert(data[0]);
			//}
		
            //setTimeout(\"$('progress').innerHTML = '';\",1000);
			//$('id_return').setAttribute('style','display:none;');
		
         });
      }
	  
	  var wdv = null;
      function remind_btn_omsm(psid,d,e) {
         if(wdv) {
            if(wdv.psid == psid) {
               cancel_return();
               return;
            } else {
               cancel_return();
            }
         }
		 // alert('test');
		$('id_return').setAttribute('style','display:inline;')
         // wdv = _dce('div');
         // wdv.psid = psid;
         // var td = $('returnbtnomsm');
         // wdv.setAttribute('style','padding:10px;');
         // wdv = td.appendChild(wdv);
        // wdv.appendChild(progress_span());
         // wdv.td = td;
         orgjx_app_reminder_omsm(psid,function(_data) {
           // wdv.innerHTML = _data;
		    $('id_return').innerHTML = _data;
           // $('inp_year').focus();
         });
      }
	  
	   function send_reminder_omsm() {
       
         //$('progress').appendChild(progress_span('&nbsp;...saving&nbsp;&nbsp;'));
		 // alert('test');
         orgjx_app_send_reminder_omsm(function(_data) {
		
            var data = recjsarray(_data);
			//if(data[0] == 1){
				alert(data[0]);
			//}else{
				//alert(data[0]);
			//}
		
            //setTimeout(\"$('progress').innerHTML = '';\",1000);
			//$('id_return').setAttribute('style','display:none;');
		
         });
      }
	  	  
	  var wdv = null;
      function return_session(psid,d,e) {
         if(wdv) {
            if(wdv.psid == psid) {
               cancel_return();
               return;
            } else {
               cancel_return();
            }
         }
		 // alert('test');
		$('id_return').setAttribute('style','display:inline;')
         // wdv = _dce('div');
         // wdv.psid = psid;
         // var td = $('returnbtn');
         // wdv.setAttribute('style','padding:10px;');
         // wdv = td.appendChild(wdv);
        // wdv.appendChild(progress_span());
         // wdv.td = td;
         orgjx_app_returnSession(psid,function(_data) {
           // wdv.innerHTML = _data;
		    $('id_return').innerHTML = _data;
           $('inp_year').focus();
         });
      }
	  
	  function save_return(id) {
         var ret = parseForm('frm');
         $('progress').appendChild(progress_span('&nbsp;...saving&nbsp;&nbsp;'));
         orgjx_app_saveReturn(id,ret,function(_data) {
            var data = recjsarray(_data);
			alert(data[3]);
            // $('sp_'+data[0]).innerHTML = data[1];
			// $('td_remark_'+data[0]).innerHTML = data[2];
            // $('inp_year').focus();
            setTimeout(\"$('progress').innerHTML = '';\",1000);
			$('id_return').setAttribute('style','display:none;');
         });
      }
	  
	   function cancel_return() {
        // wdv.td.style.backgroundColor = '';
        // wdv.psid = null;
		$('id_return').setAttribute('style','display:none;');
         //_destroy($('id_return'));
         //wdv = null;
      }
 
 //RETURN TO OMSM
 var wdv = null;
      function return_session_divmjr(psid,d,e) {
         if(wdv) {
            if(wdv.psid == psid) {
               cancel_return_divmjr();
               return;
            } else {
               cancel_return_divmjr();
            }
         }
		 // alert('test');
		$('id_return_divmjr').setAttribute('style','display:inline;')
        orgjx_app_returnSessionDivmjr(psid,function(_data) {
           // wdv.innerHTML = _data;
		    $('id_return_divmjr').innerHTML = _data;
           $('inp_year').focus();
         });
      }
	  
	  function save_return_divmjr(id) {
         var ret = parseForm('frm');
         $('progress').appendChild(progress_span('&nbsp;...saving&nbsp;&nbsp;'));
         orgjx_app_saveReturnDivmjr(id,ret,function(_data) {
            var data = recjsarray(_data);
			alert(data[0] + ' ' + data[1] + ' ' + data[2]);
            // $('sp_'+data[0]).innerHTML = data[1];
			// $('td_remark_'+data[0]).innerHTML = data[2];
            // $('inp_year').focus();
            setTimeout(\"$('progress').innerHTML = '';\",1000);
			$('id_return_divmjr').setAttribute('style','display:none;');
         });
      }
	  
	   function cancel_return_divmjr() {
        // wdv.td.style.backgroundColor = '';
        // wdv.psid = null;
		$('id_return_divmjr').setAttribute('style','display:none;');
         //_destroy($('id_return_divmjr'));
         //wdv = null;
      }
	  
	  //RETURN TO DIV MANAGER
 var wdv = null;
      function return_session_dir(psid,d,e) {
         if(wdv) {
            if(wdv.psid == psid) {
               cancel_return_dir();
               return;
            } else {
               cancel_return_dir();
            }
         }
		 // alert('test');
		$('id_return_dir').setAttribute('style','display:inline;')
        orgjx_app_returnSessionDir(psid,function(_data) {
  	    $('id_return_dir').innerHTML = _data;
        $('inp_year').focus();
         });
      }
	  
	  function save_return_dir(id) {
         var ret = parseForm('frm');
         $('progress').appendChild(progress_span('&nbsp;...saving&nbsp;&nbsp;'));
         orgjx_app_saveReturnDir(id,ret,function(_data) {
            var data = recjsarray(_data);
			alert(data[0] + ' ' + data[1] + ' ' + data[2]);
            // $('sp_'+data[0]).innerHTML = data[1];
			// $('td_remark_'+data[0]).innerHTML = data[2];
            // $('inp_year').focus();
            setTimeout(\"$('progress').innerHTML = '';\",1000);
			$('id_return_dir').setAttribute('style','display:none;');
         });
      }
	  
	   function cancel_return_dir() {
    	$('id_return_dir').setAttribute('style','display:none;');
       }


          
 
      ////////////////////////////
      
      //]]></script>";
      
      
      return $ret.$form.$tooltip.$js;
   }
   
   function main() {
      $antrainselses = new _antrain_class_SelectSession();
      $antrainsel = "<div style='padding-bottom:2px;'>".$antrainselses->show()."</div>";
      
      if(!isset($_SESSION["pms_psid"])||$_SESSION["pms_psid"]==0) {
         return $antrainsel;
      }
      $db = &Database::getInstance();
      $user_id = getUserID();
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
            $ret = $this->antrainrequisition($self_employee_id);
            break;
         default:
            $ret = $this->antrainrequisition($self_employee_id);
            break;
      }
    $sqljob = "SELECT j.job_id FROM hris_employee_job j LEFT JOIN hris_users u ON (u.person_id = j.employee_id) WHERE u.user_id = ".getUserid()."";
	$resultjob = $db->query($sqljob);
	 list($job_id)=$db->fetchRow($resultjob);
	//echo 'getuserid = '.getuserid(). '<br> job_id = '.$job_id; exit();
	 	
      return $antrainsel.$ret;
	
	  
   }
}

} // PMS_MYACTIONPLAN_DEFINED
?>