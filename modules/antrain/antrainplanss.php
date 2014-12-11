<?php
//--------------------------------------------------------------------//
// Filename : modules/antrain/antrainplan.php                         //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2010-09-22                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//
//ini_set('display_errors',1);
if ( !defined('ANTRAIN_PLAN_SS_DEFINED') ) {
   define('ANTRAIN_PLAN_SS_DEFINED', TRUE);

//include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
//include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
include_once(XOCP_DOC_ROOT."/config.php");
global $xocpConfig;

include_once(XOCP_DOC_ROOT."/modules/antrain/class/ajax_specific_subject.php");
require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
include_once(XOCP_DOC_ROOT."/modules/antrain/class/selectantrainss.php");

class _antrain_Plan_ss extends XocpBlock {
   var $catchvar = _ANTRAIN_CATCH_VAR;
   var $blockID = _ANTRAIN_PLAN_SS_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = 'Annual Training Plan - Specific Subject';
   var $display_comment = TRUE;
   var $data;
   
   function _antrain_Plan_ss($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */

   }
   
   
   function antrainplanss() {
      $psid = $_SESSION["pms_psid"];
      global $xocp_vars;
      
      
      $db=&Database::getInstance();
      $ajax = new _antrain_class_SpecificSubjectAjax("orgjx");
      
	  $user_id = getUserID();
      
      $current_year = 2013;
	  
	 /*    $proposedbutton = "<form method= 'post' action='index.php?XP_antrainplan'><input type='hidden' name='id_proposed' value='$id_propose' ><input type='hidden' name='is_proposed' value='$is_propose' ><input type='hidden' name='date_proposed' value='$dt_propose' ><input type='submit' value='Proposed' ></form>";      */
	
      $sql = "SELECT a.psid,a.is_proposed,a.is_proposeddiv,a.is_approved,a.date_proposed,b.id_global_session, d.org_nm FROM antrain_sessionss a 
      LEFT JOIN antrain_budget b ON b.id = a.id_hris_budget
      LEFT JOIN hris_orgs d ON d.org_id = b.org_id WHERE psid = ('$psid') ORDER BY b.id_global_session DESC" ;
	  
	  /* "SELECT year,session_nm FROM antrain_sessionss WHERE psid  = '$psid'"; */
      $result = $db->query($sql);
      list($psid,$is_proposed,$is_proposeddiv,$is_approved,$date_proposed,$id_global_session,$org_nm)=$db->fetchRow($result);
	  
	  $sqlsecnm = "SELECT pk.org_nm, pk.org_class_id FROM antrain_sessionss p LEFT JOIN hris_orgs pk ON p.org_id_sec = pk.org_id WHERE psid = ('$psid') ORDER BY year DESC " ;
	  
	  /* "SELECT year,session_nm FROM antrain_sessionss WHERE psid  = '$psid'"; */
      $resultsecnm = $db->query($sqlsecnm);
      list($org_nm_sec)=$db->fetchRow($resultsecnm);
	  
   $proposedbutton = "<form id='proposedbutton' method= 'post'><input type='hidden' name='id_proposed' value='$id_propose' ><input type='hidden' name='is_proposed' value='$is_propose' ><input type='hidden' name='date_proposed' value='$dt_propose' ><input type='button' onclick='propose();' value='Propose'  id='propbut'></form>";

   $proposeddivbutton = "<form id='proposeddivbutton' method= 'post'><input type='hidden' name='id_proposeddiv' value='$id_proposeddiv' ><input type='hidden' name='is_proposeddiv' value='$is_proposeddiv' ><input type='hidden' name='date_proposeddiv' value='$dt_proposeddiv' ><input type='button' onclick='proposeddiv();' value='Propose'  id='propbutdiv'></form>";
   
   $approvedbutton = "<form id='approvedbutton' method= 'post'><input type='hidden' name='id_approved' value='$id_approve' ><input type='hidden' name='is_approved' value='$is_approve' ><input type='hidden' name='date_approved' value='$dt_approve' ><input type='button' onclick='approve();' value='Approve'  id='apprbut'></form>";

	
	  $returnbtn = "<span style='float:right; margin:5px;'><input onclick='return_session(\"$psid\",this,event);' type='button' value='Return to Clerk' id='returnbtn'/></span>";  
	  $returnbtndivmjr = "<span style='float:right; margin:5px;'><input onclick='return_session_divmjr(\"$psid\",this,event);' type='button' value='Return to Section Manager' id='returnbtn'/></span>";  
	  $returnbtndir = "<span style='float:right; margin:5px;'><input onclick='return_session_dir(\"$psid\",this,event);' type='button' value='Return to Division Manager' id='returnbtn'/></span>";  
	  $remindbtn = "<span style='float:right; margin:5px;'><input onclick='remind_btn(\"$psid\",this,event);' type='button' value='Remind' id='remindbtn'/></span>"; 
	  $remindbtndiv = "<span style='float:right; margin:5px;'><input onclick='remind_btn(\"$psid\",this,event);' type='button' value='Remind' id='remindbtn'/></span>";
	  $remindbtnomsm = "<span style='float:right; margin:5px;'><input onclick='remind_btn_omsm(\"$psid\",this,event);' type='button' value='Remind' id='remindbtnomsm'/></span>"; 	 
	  $addbtn =	"<span style='float:right; margin:5px;'><input onclick='new_session();' type='button' value='Add'/></span>";
          $addbtngrade = "<span style='float:right; margin:5px;'><input onclick='new_session_pos();' type='button' value='Add by Position'/></span>";
	  
	if($is_proposed == 1)
	{
		$addbtn = '';
                $addbtngrade = '';
	}
	else
	{
		$addbtn = $addbtn;
                $addbtngrade = $addbtngrade;
	}  
	  
	if ($sumcost == 0)
	{
		$sumcost = '0' ;
	}
	else
	{
		$sumcost = $sumcosts;
	}
	
	$sqljob = "SELECT j.job_id, a.job_class_id FROM hris_employee_job j LEFT JOIN hris_users u ON ( u.person_id = j.employee_id ) LEFT JOIN hris_jobs a ON ( j.job_id = a.job_id ) WHERE u.user_id = ".getUserid()."";
	$resultjob = $db->query($sqljob);
	 list($job_id,$job_class_id)=$db->fetchRow($resultjob);
	//echo 'getuserid = '.getuserid(). '<br> job_id = '.$job_id; exit();
	
//REMIND CLERK
	if ( $job_class_id == 12 ||  $job_id == 0|| $job_id == 999|| $job_id == 145|| $job_id == 130 || $job_id == 112|| $job_id == 57|| $job_id == 166|| $job_id == 42|| $job_id == 181 || $job_id == 12|| $job_id == 110|| $job_id == 127|| $job_id == 125 || $job_id == 84|| $job_id == 89|| $job_id == 59|| $job_id == 103|| $job_id == 198|| $job_id == 208|| $job_id == 5|| $job_id == 23|| $job_id == 34 ) {
	  
		 if ($is_proposed == '1')
		{
			$remindbtn = '';
			$addbtn = '';
                        $addbtngrade = '';
		}	
		else
		{
			$addbtn = $addbtn;
			$remindbtn = $remindbtn;
                        $addbtngrade = $addbtngrade;
		}
	}
	else
	{
		$remindbtn = '';
	 }
	 
	 if ( $job_class_id == 2  || $job_id == 0|| $job_id == 999|| $job_id == 6|| $job_id == 36 || $job_id == 48|| $job_id == 104|| $job_id == 126 || $job_id == 146|| $job_id == 168 ){
		if ($is_proposeddiv == '1')
			{
				$remindbtndiv =  $remindbtndiv;
			}
		else
			{
				$remindbtndiv =  '';
			}
	 	 
	 }
	 else
	 {
			$remindbtndiv = '';
	 }
	 
	 //Return to SM
	 if($job_class_id == 2 || $job_id == 0|| $job_id == 999|| $job_id == 6|| $job_id == 36|| $job_id == 48|| $job_id == 104|| $job_id == 126|| $job_id == 147|| $job_id == 168 AND $is_proposed == 1)
	 {
		$returnbtndivmjr = $returnbtndivmjr;
	 }
	 else
	 {
		$returnbtndivmjr = '';
	 }
	 
	 //Return to DIv Manager
	 if($job_class_id == 1 || $job_id == 0|| $job_id == 999|| $job_id == 47|| $job_id == 146|| $job_id == 167|| $job_id == 129 AND $is_proposeddiv == 1)
	 {
		$returnbtndir = $returnbtndir;
	 }
	 else
	 {
		$returnbtndir =  '';
	 }
	 
	 
	 //PROPOSEDIV
	  if ( $job_class_id == 2 || $job_id == 0|| $job_id == 999|| $job_id == 6|| $job_id == 36|| $job_id == 48|| $job_id == 104|| $job_id == 126|| $job_id == 146|| $job_id == 168 )
	  
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
	 
	 
	  //APPROVED
	  if (  $job_class_id == 1 || $job_id == 0|| $job_id == 999|| $job_id == 47|| $job_id == 146|| $job_id == 167|| $job_id == 129 || $job_id == 128 )
	   {
		if($is_approved == 1 || $is_proposeddiv == 0  )
		{
		$approvedbutton = '';
		}
		else
		{
			$approvedbutton = $approvedbutton;
		}
		}
		else
		{
			$approvedbutton = '';
		} 

	 
	 
	 //REMINDER OM SM & PROPOSE BUTTON
	if (  $job_class_id == 3 || $job_id == 0|| $job_id == 999|| $job_id == 141|| $job_id == 68|| $job_id == 138|| $job_id == 132|| $job_id == 151|| $job_id == 37|| $job_id == 169|| $job_id == 107|| $job_id == 111|| $job_id == 74|| $job_id == 85|| $job_id == 49|| $job_id == 60|| $job_id == 91|| $job_id == 183|| $job_id == 25|| $job_id == 211|| $job_id == 220 || $job_id == 133) 
	{
		if ($is_proposed == '1')
		{
			$remindbtnomsm = $remindbtnomsm;
			$proposedbutton = '';
			$returnbtn = '';
		}
		else
		{
			$remindbtnomsm =  '';
			$returnbtn = $returnbtn;
			$proposedbutton = $proposedbutton;
		}
	
	}
	else
	{
		$remindbtnomsm = '';
		$returnbtn = '';
		$proposedbutton = '';
	}

	
	 
	
    
	$tooltip = "<div id='all_ap_tooltip' style='display:none;'>";
      

      $ret .= "<div>";
      
     /*  $ret .= $return_notes;
      $ret .= $report_return_notes;
       */
      $ret .= "<table style='margin-top:5px;table-layout:fixed;' class='xxlist'>";
      
      $ret .= "<div style='padding:5px;font-size:20px;color: #666666; text-align:center;'>ANNUAL TRAINING / SEMINAR PLAN </div>"
			 . "<div style='padding:5px;font-size:15px;color: #666666; text-align:center;'>(Specific Subject /Year $year )</div>"
			 . "<div style='padding:2px;font-size:12px;color: #666666; text-align:left;'>Section:  $org_nm $addbtngrade $addbtn $remindbtnomsm $remindbtn $remindbtndiv $returnbtn $returnbtndivmjr $returnbtndir <div id='id_return'></div><div id='id_return_divmjr'></div><div id='id_return_dir'></div></div>"
			 
			. "<colgroup><col width='3%'/><col width='17%'/><col width='6%'/><col width='12%'/><col width='12%'/><col width='12%'/><col width='12%'/><col width='7%'/><col width='7%'/><col width='10%'/></colgroup>"
			
			. "<thead><tr><td rowspan='3' style='border-right: 1px solid #CCC; text-align: center;'>No.</td><td style='border-right: 1px solid #CCC; text-align:center; ' colspan='2'>Employees</td><td style='border-right: 1px solid #CCC;  text-align:center;' colspan='6'>Training/ Seminar Programs</td><td style='border-right: 1px solid #CCC;  text-align:center;' rowspan='3'>Remarks</td></tr>"
			. "<tr><td style='border-right: 1px solid #CCC;  text-align:center;' rowspan='2'>Name</td><td style='border-right: 1px solid #CCC; text-align:center;' rowspan='2'>Position</td><td style='border-right: 1px solid #CCC;  text-align:center;' rowspan='2'>Subject</td><td style='border-right: 1px solid #CCC;  text-align:center;' rowspan='2'>Objective</td><td style='border-right: 1px solid #CCC; text-align:center;' rowspan='2'>Schedule</td><td style='border-right: 1px solid #CCC; text-align:center;' rowspan='2'>Cost Estimation</td><td style='border-right: 1px solid #CCC; text-align:center;' colspan='2'>Instructor</td></tr>"
			. "</thead>";
   
      $ret .= "<tbody>";
      $ret .= "<tr><td colspan='10'><strong>IDP</strong></td><tr>";
      
	$sql = "SELECT p.id, p.name, p.subject, p.objectives, p.schedule_start, p.schedule_end, p.cost, p.id_job_class1, p.id_job_class2, p.remark, p.inst, ps.job_class_nm, ps2.job_class_nm AS job_class_nm2
FROM antrain_plan_specific p LEFT JOIN antrain_sessionss pk ON p.id_antrain_session = pk.psid LEFT JOIN hris_job_class ps ON p.id_job_class1 = ps.job_class_id LEFT JOIN hris_job_class ps2 ON p.id_job_class2 = ps2.job_class_id WHERE p.id_antrain_session = '$psid' AND p.is_deleted = 'F' AND p.is_other = 0 ORDER BY p.id ASC";
	$result = $db->query($sql);
	$rescount = $db->getRowsNum($result);
	if($db->getRowsNum($result)>0) {
	$i = 1;
	while(list($id,$name,$subject,$objectives,$schedule_start,$schedule_end,$cost,$id_job_class1,$id_job_class2, $remark,$inst, $hris_job_class,$hris_job_class2)=$db->fetchRow($result)){
	 //return array($id,$name,$subject,$objectives,$schedule_start,$schedule_end,$cost,$id_job_class1,$id_job_class2,$remark,$inst);	

		if ($inst == 'int') {
			$ins_int = 'Internal';
		}else{
			$ins_int = 'External';
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

				/*if($is_proposed == 1)
				{
				$editlink = '';
				}
				else
				{
				$editlink = $editlink;
				}*/
			//edit by andi
			  $ret .= "<tr><td id='tdclass_${id}' style='margin: 0px; padding:0px;' colspan=10 >"
					. "<table style='width:100%'><colgroup><col width='3%'/><col width='17%'/><col width='6%'/><col width='12%'/><col width='12%'/><col width='12%'/><col width='12%'/><col width='7%'/><col width='7%'/><col width='10%'/></colgroup><tbody><tr>"
				  . "<td id='td_num_loop_${id}' style='text-align:center;width:50px'>$i</td>"
				 . "<td id='td_name_${id}' $editlink '>$name</td>"
				  . "<td><span id='sp_${id}' ; '>".$participantname ."</span></td>"
               	  . "<td id='td_subject_${id}' style='text-align: left;'>$subject</td>"
				  . "<td id='td_objectives_${id}' style='text-align: left;'>$objectives</td>"
				  . "<td id='td_schedule_${id}' style='text-align: center; font-size:10px;'>$schedule_start - $schedule_end</td>"
				  . "<td id='td_cost_${id}' style='text-align: right; padding-right: 10px;'>$. $cost</td>"
				  . "<td id='td_ins_int_${id}' style='text-align: center;' colspan='2'>$ins_int</td>"
				  . "<td id='td_remark_${id}' style='text-align: center;'>$remark </td>"
                 . "</tr></tbody></table>"
                  . "</td></tr>";

                 $i++;
			}
		 }

		 $ret .= "<tr><td colspan='10'><strong>Others</strong></td><tr>";

		 $sql = "SELECT p.id, p.name, p.subject, p.objectives, p.schedule_start, p.schedule_end, p.cost, p.id_job_class1, p.id_job_class2, p.remark, p.inst, ps.job_class_nm, ps2.job_class_nm AS job_class_nm2
FROM antrain_plan_specific p LEFT JOIN antrain_sessionss pk ON p.id_antrain_session = pk.psid LEFT JOIN hris_job_class ps ON p.id_job_class1 = ps.job_class_id LEFT JOIN hris_job_class ps2 ON p.id_job_class2 = ps2.job_class_id WHERE p.id_antrain_session = '$psid' AND p.is_deleted = 'F' AND p.is_other = 1 ORDER BY p.id ASC";
	$result = $db->query($sql);
	  if($db->getRowsNum($result)>0) {
	  	$i = 1;
	while(list($id,$name,$subject,$objectives,$schedule_start,$schedule_end,$cost,$id_job_class1,$id_job_class2, $remark,$inst, $hris_job_class,$hris_job_class2)=$db->fetchRow($result)){
	 //return array($id,$name,$subject,$objectives,$schedule_start,$schedule_end,$cost,$id_job_class1,$id_job_class2,$remark,$inst);	

		if ($inst == 'int') {
			$ins_int = 'Internal';
		}else{
			$ins_int = 'External';
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

				/*if($is_proposed == 1)
				{
				$editlink = '';
				}
				else
				{
				$editlink = $editlink;
				}*/

			$submit_req_btn = "<span style='float:right; margin:5px;'><input onclick='return_session(\"$id\",this,event);' type='button' value='Submit Request' id='submitreq_btn'/></span</td>";
			//edit by andi
			  $ret .= "<tr><td id='tdclass_${id}' style='margin: 0px; padding:0px;' colspan=10 >"
					. "<table style='width:100%'><colgroup><col width='3%'/><col width='17%'/><col width='6%'/><col width='12%'/><col width='12%'/><col width='12%'/><col width='12%'/><col width='7%'/><col width='7%'/><col width='10%'/></colgroup><tbody><tr>"
				  . "<td id='td_num_loop_${id}' style='text-align:center;width:50px'>$i</td>"
				 . "<td id='td_name_${id}' $editlink '>$name</td>"
				  . "<td><span id='sp_${id}' ; '>".$participantname ."</span></td>"
               	  . "<td id='td_subject_${id}' style='text-align: left;'>$subject</td>"
				  . "<td id='td_objectives_${id}' style='text-align: left;'>$objectives</td>"
				  . "<td id='td_schedule_${id}' style='text-align: center; font-size:10px;'>$schedule_start - $schedule_end</td>"
				  . "<td id='td_cost_${id}' style='text-align: right; padding-right: 10px;'>$. $cost</td>"
				  . "<td id='td_ins_int_${id}' style='text-align: center;' colspan='2'>$ins_int</td>"
				  . "<td id='td_remark_${id}' style='text-align: center;'>$remark "
                 . "</tr></tbody></table>"
                  . "</td></tr>";
               $i++;
			}
		 }
	

	$sqlbdgt = "SELECT budget_specific FROM antrain_sessionss a LEFT JOIN antrain_budget b ON b.id = a.id_hris_budget WHERE a.psid = '$psid'";
	$resultbdgt = $db->query($sqlbdgt);
	list($budget)=$db->fetchRow($resultbdgt);
	
	$sql = "SELECT SUM(cost) FROM antrain_plan_specific WHERE id_antrain_session = '$psid' AND is_deleted = 'F'";
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


	
		
	$ret .= "<tr style='display:none;' id='trempty'><td>"._EMPTY."</td></tr>"; 
	  $ret .= "<tr><td colspan=6 style='border-right: 1px solid #CCC;border-top: 1px solid #CCC'><strong>Total Cost Estimation</strong></td><td style='border-right: 1px solid #CCC;border-top: 1px solid #CCC; text-align: right; padding-right: 12px;'>$. $sumcost</td><td style='border-right: 1px solid #CCC;border-top: 1px solid #CCC'></td><td style='border-right: 1px solid #CCC;border-top: 1px solid #CCC'></td><td  style='border-right: 1px solid #CCC; border-top: 1px solid #CCC;' ></td></tr>";
	  $ret .= "<tr><td colspan=6 style='border-right: 1px solid #CCC'><strong>Budget in year $id_hris_budget</strong></td><td style='border-right: 1px solid #CCC; text-align: right; padding-right: 12px;'>$. $budget</td><td style='border-right: 1px solid #CCC' ></td><td style='border-right: 1px solid #CCC' ></td><td style='border-right: 1px solid #CCC' ></td></tr>";
      
	  $ret .= "</tbody>";
      $ret .= "</table>";
      $ret .= "</div>";
     
	/*  $sql = "SELECT id_proposed, date_proposed"
           . " FROM antrain_sessionss"
           . " WHERE psid = '$psid'";

   $proposedbutton = "<input onclick='propose();' type='button' value='".PROPOSE."'/>";     
     
	 $result = $db->query($sql);
      list($id_proposed,$date_proposed)=$db->fetchRow($result); 
	  
	  $date_proposed = date('d/M/Y', strtotime($date_proposed));
	  
	  	if ($sumcost == 0)
	{
		$sumcost = '0' ;
	}
	else
	{
		$sumcost = $sumcosts;
	}
	
	if ($id_proposed == '1')
	{
		$proposedbutton = '';
	}	
	else
	{
	$proposedbutton = $proposedbutton;  
	} */
   
     $sql = "SELECT p.user_id,ps.person_nm,pk.date_proposed FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id LEFT JOIN antrain_sessionss pk ON p.user_id = pk.id_proposed WHERE pk.psid = '$psid'";  
     $result = $db->query($sql);
     list($user_id_proposed,$proposedby,$date_proposed)=$db->fetchRow($result);
     $date_proposed = date('d/M/Y', strtotime($date_proposed));
	 
	 $sql = "SELECT p.user_id,ps.person_nm,pk.date_proposeddiv FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id LEFT JOIN antrain_sessionss pk ON p.user_id = pk.id_proposeddiv WHERE pk.psid = '$psid'";  
     $result = $db->query($sql);
     list($user_id_proposeddiv,$proposeddivby,$date_proposeddiv)=$db->fetchRow($result);
     $date_proposeddiv = date('d/M/Y', strtotime($date_proposeddiv));
	 
	 
	 $sql = "SELECT p.user_id,ps.person_nm,pk.date_approved FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id LEFT JOIN antrain_sessionss pk ON p.user_id = pk.id_approved  WHERE pk.psid = '$psid'";  
     $result = $db->query($sql);
     list($user_id_approved,$approvedby,$date_approved)=$db->fetchRow($result);
     $date_approved = date('d/M/Y', strtotime($date_approved));
	 
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

	 $sql = "SELECT note_text FROM antrain_sessionss WHERE psid = '$psid'";
	 $result = $db->query($sql);
	 list($note_text)=$db->fetchRow($result);

	 $note = "<div style='margin-bottom: 10px; float: left;'><form id='frmnote' style='float: left; width: 271px;'>"
	 	   . "<textarea id='txt_note' name='txt_note' style='resize: none; width: 264px; float: left; height: 95px;'>$note_text</textarea>"
	 	   . "<input type='button' value='Submit' style='float: right; margin: 5px 0px 0px;' onclick='save_note()'></form></div>";
	 
	 $form .= "<div style='text-align:right;padding:10px;margin-top:10px;margin-bottom:100px;'>"
				. "<table align='left' style='border-top:none solid #777;border-left:none solid #777;border-spacing: 0px; float: left; width: 100%;margin-bottom:10px;'>"
                . "<colgroup>"
                . "<col width='500'/>"
              
                . "</colgroup>"
                . "<tbody>"
                . "<tr>"
                . "<td style='text-align:left;border-bottom:none solid #777;border-right:none solid #777;'>"
               
                . "</td>"
               
                . "</tr>"
                . "<tr>"
                . "<td style='text-align:left;border-bottom:none solid #bbb;border-right:none solid #777; font-size: 10px;'>"
                //. " &nbsp &nbsp &nbsp * Instructor: Int = Internal, Ext = External <br/>"
				. "* If total Estimation Cost more than budget in year, please fill a reason in remarks column. <br/>"
                . "</td>"
         
                
                . "</tr>"
  
                . "</tbody>"
                . "</table>"               
                . $note
			   . "<table align='right' style='border-top:2px solid #777;border-left:2px solid #777;border-spacing:0px;'>"
                . "<colgroup>"
                . "<col width='200'/>"
				. "<col width='200'/>"
                . "<col width='200'/>"
                . "</colgroup>"
                . "<tbody>"
                . "<tr>"
                . "<td colspan='2' style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;'>"
                . "Proposed by,"
                . "</td>"
                . "<td  style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;'>"
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
                
                . "</tr>"
                . "</tbody>"
                . "</table>"
                . "</div>";
      
      
      
      
      $js = $ajax->getJs()."<script type='text/javascript' src='".XOCP_SERVER_SUBDIR."/include/calendar.js'></script><script type='text/javascript'>//<![CDATA[
      
      
      //////////////////////////////////////////////////////////////////////////////
	
	  ///deni

	  function render_employee() {
	  	var id = document.getElementById('inp_id_job_class1').value;
	  	orgjx_app_renderEmployee(id,function(_data) {
	  		var data = recjsarray(_data);
	  		//alert(data[1]);
	  		document.getElementById('divempty').innerHTML = data[0];
	  	});
	  }

	  function render_position() {
	  	var id = document.getElementById('inp_employee_id').value;
	  	orgjx_app_renderPosition(id,function(_data) {
	  		document.getElementById('spanempty').innerHTML = _data;
	  	});
	  }

	  function save_note() {
	  	var note = document.getElementById('txt_note').value;
	  	orgjx_app_saveText(note,function(_data) {
	  		document.getElementById('txt_note').innerHTML = _data;
	  	});
	  }

	  function submit_requisition(id,d,e) {
	  	$('progress').appendChild(progress_span('&nbsp;...saving&nbsp;&nbsp;'));
	  	orgjx_app_submitRequisition(id,function(_data) {
	  		var data = recjsarray(_data);
	  		//alert(data[0]);
	  		setTimeout(\"$('progress').innerHTML = '';\",1000);
	  		setTimeout(\"document.getElementById('btn_submit_requisition').style.display='none';\",1000);
	  		window.setTimeout(function(){window.location = 'http://".$_SERVER['SERVER_NAME']."/".XOCP_SERVER_SUBDIR."/index.php?XP_antrainrequisition&menuid=13&mpid=7&reqid='+data[0];},1500)
	  	});
	  }

	function new_session() {
         if(wdv) {
            cancel_edit();
         }
         var tre = $('trempty');
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
      
      function new_session_pos() {
         if(wdv) {
            cancel_edit();
         }
         var tre = $('trempty');
         var tr = _dce('tr');
         var td = tr.appendChild(_dce('td'));
         tr = tre.parentNode.insertBefore(tr,tre);
         wdv = _dce('div');
         wdv.td = td;
         orgjx_app_newSessionPos(function(_data) {
            var data = recjsarray(_data);
            wdv.td.setAttribute('id','tdclass_'+data[0] );
            wdv.td.innerHTML = data[1];
            wdv.td = null;
            wdv = null;
            edit_sessionpos(data[0],null,null);
         });
      }
        
      var wdv = null;
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
         td.setAttribute('colspan','10');
         td.setAttribute('style','padding:0px;');
		 wdv = td.appendChild(wdv);
         wdv.appendChild(progress_span());
         wdv.td = td;
         orgjx_app_editSession(id,function(_data) {
            wdv.innerHTML = _data;
		     // inp_name.focus();

         });
      }

       var wdv = null;
      function edit_sessionpos(id,d,e) {
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
         td.setAttribute('colspan','10');
         td.setAttribute('style','padding:0px;');
		 wdv = td.appendChild(wdv);
         wdv.appendChild(progress_span());
         wdv.td = td;
         orgjx_app_editSessionPos(id,function(_data) {
            wdv.innerHTML = _data;
            $('inp_remark').focus();
         });
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
			 alert(data[5]);
			//setTimeout(\"$('progress').innerHTML = '';\",1000);
			//location.reload();
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
      	 //alert(wdv.id);
         orgjx_app_Delete(wdv.id,null);
         var tr = wdv.parentNode.parentNode;
         _destroy(tr);
         wdv.id = null;
         wdv = null;
      }
      
      function save_session() {
         var ret = parseForm('frm');
         $('progress').appendChild(progress_span('&nbsp;...saving&nbsp;&nbsp;'));
		orgjx_app_saveSession (wdv.id,ret,function(_data) {
            var data = recjsarray(_data);
			var participantname = null;
			if (data[8] != 0 && data[8] != null) {
			participantname = data[11] + '-' +data[12];
			}else{
			participantname = data[11] ;
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
			//alert(data[1]);
			/*$('sp_'+data[0]).innerHTML = participantname;
			$('td_name_'+data[0]).innerHTML = data[1];
			$('td_subject_'+data[0]).innerHTML = data[2];
			$('td_objectives_'+data[0]).innerHTML = data[3];
			$('td_schedule_'+data[0]).innerHTML = data[4] + ' - ' +data[5];
			$('td_cost_'+data[0]).innerHTML = '$.'+data[6];
			$('td_ins_int_'+data[0]).innerHTML = internal;		
			$('td_ins_ext_'+data[0]).innerHTML = external;
			$('td_remark_'+data[0]).innerHTML = data[9];
            $('inp_remark').focus();*/
            setTimeout(\"$('progress').innerHTML = '';\",1000);
			location.reload();
         });
      }

      function save_session_pos() {
         var ret = parseForm('frm');
         $('progress').appendChild(progress_span('&nbsp;...saving&nbsp;&nbsp;'));
		orgjx_app_saveSessionPos (wdv.id,ret,function(_data) {
            var data = recjsarray(_data);
			var participantname = null;
			if (data[8] != 0 && data[8] != null) {
			participantname = data[11] + '-' +data[12];
			}else{
			participantname = data[11] ;
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
			//alert(data[1]);
			//$('sp_'+data[0]).innerHTML = participantname;
			/*$('td_name_'+data[0]).innerHTML = data[1];
			$('td_subject_'+data[0]).innerHTML = data[2];
			$('td_objectives_'+data[0]).innerHTML = data[3];
			$('td_schedule_'+data[0]).innerHTML = data[4] + ' - ' +data[5];
			$('td_cost_'+data[0]).innerHTML = '$.'+data[6];
			$('td_ins_int_'+data[0]).innerHTML = internal;		
			$('td_ins_ext_'+data[0]).innerHTML = external;
			$('td_remark_'+data[0]).innerHTML = data[9];
            $('inp_remark').focus();*/
            setTimeout(\"$('progress').innerHTML = '';\",1000);
			location.reload();
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
            $ret = $this->antrainplanss($self_employee_id);
            break;
         default:
            $ret = $this->antrainplanss($self_employee_id);
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