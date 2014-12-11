<?php
//--------------------------------------------------------------------//
// Filename : modules/antrain/antrainplansscancelalterview.php                         //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2010-01-22                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_SSCANCELALTERVIEWAJAX_DEFINED') ) {
   define('HRIS_SSCANCELALTERVIEWAJAX_DEFINED', TRUE);

//include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
//include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
include_once(XOCP_DOC_ROOT."/config.php");
global $xocpConfig;

include_once(XOCP_DOC_ROOT."/modules/antrain/class/ajax_sscancelalter.php");
require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
include_once(XOCP_DOC_ROOT."/modules/antrain/class/selectantrainsscancelalter.php");

class _antrain_sscancelalterview extends XocpBlock {
   var $catchvar = _ANTRAIN_CATCH_VAR;
   var $blockID = _ANTRAIN_SSCANCELALTERVIEW_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = 'Annual Training - Specific Subject Cancellation/Alteration';
   var $display_comment = TRUE;
   var $data;
   
   function _antrain_sscancelalterview($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */

   }
   
   
   function antrainsscancelalterview() {
      $psid = $_SESSION["pms_psid"];
      global $xocp_vars;
      
      
      $db=&Database::getInstance();
      $ajax = new _antrain_class_sscancelalterAjax("orgjx");
      
	  $user_id = getUserID();
      
      $current_year = 2013;
	  
      $sql = "SELECT p.year, p.is_inform, p.is_ackn,p.is_appralter,p.date_inform,p.date_ackn,p.date_appralter, pk.org_nm, pk.org_class_id FROM antrain_sessionss p LEFT JOIN hris_orgs pk ON p.org_id = pk.org_id WHERE psid = ('$psid') ORDER BY year DESC" ;
	  
	  $result = $db->query($sql);
      list($year,$is_inform,$is_ackn,$is_appralter,$date_inform,$date_ackn,$date_appralter,$org_nm)=$db->fetchRow($result);
	
	  
	  $sqlsecnm = "SELECT pk.org_nm, pk.org_class_id FROM antrain_sessionss p LEFT JOIN hris_orgs pk ON p.org_id_sec = pk.org_id WHERE psid = ('$psid') ORDER BY year DESC " ;
	  
	  /* "SELECT year,session_nm FROM antrain_sessionss WHERE psid  = '$psid'"; */
      $resultsecnm = $db->query($sqlsecnm);
      list($org_nm_sec)=$db->fetchRow($resultsecnm);
	  
   /* $informbutton = "<form id='informbutton' method= 'post'><input type='hidden' name='id_proposed' value='$id_inform' ><input type='hidden' name='is_inform' value='$is_inform' ><input type='hidden' name='date_inform' value='$dt_inform' ><input type='button' onclick='inform();' value='Inform'  id='informbut'></form>";
	
	$acknbutton = "<form id='acknbutton' method= 'post'><input type='hidden' name='id_ackn' value='$id_ackn' ><input type='hidden' name='is_ackn' value='$is_ackn' ><input type='hidden' name='date_ackn' value='$dt_ackn' ><input type='button' onclick='ackn();' value='Acknowledge'  id='acknbut'></form>";
	
	$appralterbutton = "<form id='appralterbutton' method= 'post'><input type='hidden' name='id_appralter' value='$id_appralter' ><input type='hidden' name='is_appralter' value='$is_appralter' ><input type='hidden' name='date_appralter' value='$dt_appralter' ><input type='button' onclick='appralter();' value='Approve'  id='appralterbut'></form>";

	
	  $returnbtn = "<span style='float:right; margin:5px;'><input onclick='return_session(\"$psid\",this,event);' type='button' value='Return to Clerk' id='returnbtn'/></span>";  
	  $returnbtndivmjr = "<span style='float:right; margin:5px;'><input onclick='return_session_divmjr(\"$psid\",this,event);' type='button' value='Return to Section Manager' id='returnbtn'/></span>";  
	  $returnbtndir = "<span style='float:right; margin:5px;'><input onclick='return_session_dir(\"$psid\",this,event);' type='button' value='Return to Division Manager' id='returnbtn'/></span>";  
	  $remindbtn = "<span style='float:right; margin:5px;'><input onclick='remind_btn(\"$psid\",this,event);' type='button' value='Remind' id='remindbtn'/></span>"; 
	  $remindbtnomsm = "<span style='float:right; margin:5px;'><input onclick='remind_btn_omsm(\"$psid\",this,event);' type='button' value='Remind' id='remindbtnomsm'/></span>"; 	  */
	  //$addbtn =	"<span style='float:right; margin:5px;'><input onclick='new_session();' type='button' value='Add'/></span>";
	  
	if($is_proposed == 1)
	{
		$addbtn = '';
	}
	else
	{
		$addbtn = $addbtn;
	}  
	  
	if ($sumcost == 0)
	{
		$sumcost = '0' ;
	}
	else
	{
		$sumcost = $sumcosts;
	}
	
	$sqljob = "SELECT j.job_id FROM hris_employee_job j LEFT JOIN hris_users u ON (u.person_id = j.employee_id) WHERE u.user_id = ".getUserid()."";
	$resultjob = $db->query($sqljob);
	 list($job_id)=$db->fetchRow($resultjob);
	//echo 'getuserid = '.getuserid(). '<br> job_id = '.$job_id; exit();
	
//INFORM
	if ( $job_id == 999|| $job_id == 141|| $job_id == 68|| $job_id == 138|| $job_id == 132|| $job_id == 151|| $job_id == 37|| $job_id == 169|| $job_id == 107|| $job_id == 111|| $job_id == 74|| $job_id == 85|| $job_id == 49|| $job_id == 60|| $job_id == 91|| $job_id == 183|| $job_id == 25|| $job_id == 211|| $job_id == 220 ) {
	  
		 	 if ($is_inform == '1')
		{
			$informbutton = '';
			$returnbtn = '';
		}	
		else
		{
			$informbutton = $informbutton;  
			$returnbtn = $returnbtn;
		} 
	 }
	 else 
	 {
	 	$informbutton = '';
		$returnbtn = '';
	 }
	 
	 //Return to SM
	 if( $job_id == 999|| $job_id == 6|| $job_id == 36|| $job_id == 48|| $job_id == 104|| $job_id == 126|| $job_id == 147|| $job_id == 168 AND $is_proposed == 1)
	 {
		$returnbtndivmjr = $returnbtndivmjr;
	 }
	 else{
		$returnbtndivmjr = '';
	 }
	 
	 //Return to DIv Manager
	 if( $job_id == 999|| $job_id == 47|| $job_id == 146|| $job_id == 167|| $job_id == 129 AND $is_proposeddiv == 1)
	 {
		$returnbtndir = $returnbtndir;
	 }
	 else{
		$returnbtndir =  '';
	 }
	 
	 
	 //ACKN
	  if ( $job_id == 999|| $job_id == 6|| $job_id == 36|| $job_id == 48|| $job_id == 104|| $job_id == 126|| $job_id == 147|| $job_id == 168 || $job_id == 146 )
	  
	  {
		if ($is_ackn == '1' || $is_inform == '0')
		{
			$acknbutton = '';
		}
		else
		{
			$acknbutton = $acknbutton;  
		}
	}
	else
	{
		$acknbutton = '';
	}
	 
	 
	  //APPROVECANCELALTER
	 if ($job_id == 6 || $job_id == 999)
	{
		if ($is_appralter == '1' || $is_ackn == '0')
		{
			$appralterbutton = '';
		}	
		else
		{
			$appralterbutton = $appralterbutton;  
		}		
	}
	else
	{
		$appralterbutton = '';
	}
	 
	 
	 
	if ( $job_id == 999|| $job_id == 145|| $job_id == 130 || $job_id == 112|| $job_id == 57|| $job_id == 166|| $job_id == 42|| $job_id == 181 || $job_id == 12|| $job_id == 110|| $job_id == 127|| $job_id == 125 || $job_id == 84|| $job_id == 89|| $job_id == 59|| $job_id == 103|| $job_id == 198|| $job_id == 208|| $job_id == 5|| $job_id == 23|| $job_id == 34 )
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
      $ret .= "<table style='margin-top:5px;table-layout:fixed;' class='xxlist'>";
      
      $ret .= "<div style='padding:5px;font-size:20px;color: #666666; text-align:center;'>Annual Training - Specific Subject Cancellation/Alteration </div>"
			 . "<div style='padding:5px;font-size:15px;color: #666666; text-align:center;'>(Specific Subject /Year $year )</div>"
			 . "<div style='padding:2px;font-size:12px;color: #666666; text-align:left;'>Division/ Section:  $org_nm / $org_nm_sec  $addbtn $remindbtnomsm $remindbtn $returnbtn $returnbtndivmjr $returnbtndir <div id='id_return'></div><div id='id_return_divmjr'></div><div id='id_return_dir'></div></div>"
			 
			. "<colgroup><col width='3%'/><col width='17%'/><col width='5%'/><col width='12%'/><col width='10%'/><col width='10%'/><col width='10%'/><col width='7%'/><col width='7%'/><col width='8%'/><col width='8%'/></colgroup>"
			
			. "<thead>
			<tr>
				<td rowspan='3' style='border-right: 1px solid #CCC; text-align: center;'>No.</td>
				<td style='border-right: 1px solid #CCC; text-align:center; ' colspan='2'>Employees</td>
				<td style='border-right: 1px solid #CCC;  text-align:center;' colspan='6'>Training/ Seminar Programs</td>
				<td style='border-right: 1px solid #CCC;  text-align:center;' rowspan='3'>Remarks</td>
				<td style='border-right: 1px solid #CCC;  text-align:center;' rowspan='3'>Status Cancellation</td>
			</tr>"
			. "<tr>
				<td style='border-right: 1px solid #CCC;  text-align:center;' rowspan='2'>Name</td>
				<td style='border-right: 1px solid #CCC; text-align:center;' rowspan='2'>Position</td>
				<td style='border-right: 1px solid #CCC;  text-align:center;' rowspan='2'>Subject</td>
				<td style='border-right: 1px solid #CCC;  text-align:center;' rowspan='2'>Objective</td>
				<td style='border-right: 1px solid #CCC; text-align:center;' rowspan='2'>Schedule</td>
				<td style='border-right: 1px solid #CCC; text-align:center;' rowspan='2'>Cost Estimation</td>
				<td style='border-right: 1px solid #CCC; text-align:center;' colspan='2'>Instructor*</td>
			</tr>"
			. "<tr><td style='border-right: 1px solid #CCC; text-align:center;'>Int.</td>
			<td style='border-right: 1px solid #CCC; text-align:center;'>Ext.</td>
			</tr></thead>";
   
      $ret .= "<tbody>";
      
	$sql = "SELECT p.id, p.name, p.subject, p.objectives, p.schedule_start, p.schedule_end, p.cost, p.id_job_class1, p.id_job_class2, p.remark, p.status_cancel,p.inst, ps.job_class_nm, ps2.job_class_nm AS job_class_nm2
FROM antrain_plan_specific p LEFT JOIN antrain_sessionss pk ON p.id_antrain_session = pk.psid LEFT JOIN hris_job_class ps ON p.id_job_class1 = ps.job_class_id LEFT JOIN hris_job_class ps2 ON p.id_job_class2 = ps2.job_class_id WHERE p.id_antrain_session = '$psid' AND p.is_deleted = 'F' ";
	$result = $db->query($sql);
	  if($db->getRowsNum($result)>0) {
	while(list($id,$name,$subject,$objectives,$schedule_start,$schedule_end,$cost,$id_job_class1,$id_job_class2, $remark,$status_cancel,$inst, $hris_job_class,$hris_job_class2)=$db->fetchRow($result)){
	 //return array($id,$name,$subject,$objectives,$schedule_start,$schedule_end,$cost,$id_job_class1,$id_job_class2,$remark,$inst);	
		$ins_int = ' &#9679; ';
			
			   
			   if ($inst == 'int') 
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
				
				$ins_ext = ' &#9679; ';
				if ($inst == 'ext') 
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
				
		  $schedule_start = date('d/m/Y', strtotime($schedule_start));
		  $schedule_end = date('d/m/Y', strtotime($schedule_end));
		  $numberloop = $numberloop + 1;
		  
				  if($id_job_class2 <> 0 && $id_job_class2 <> NULL ) {
				  $participantname = htmlentities(stripslashes($hris_job_class))." - ".htmlentities(stripslashes($hris_job_class2));
				  }else{
				   $participantname = htmlentities(stripslashes($hris_job_class));
				  }
			$editlink = "class='xlnk' onclick='edit_session(\"$id\",this,event)'";

/* 				if($is_proposed == 1)
				{
				$editlink = '';
				}
				else
				{
				$editlink = $editlink;
				} */
			
			 
			   if ($status_cancel == 'T') 
				{
					$stat_can = 'Cancelled';
				}
				
				else
				{
					if ($status_cancel == 'A')
					{
						$stat_can = 'Altered';
							}
				else{
						$stat_can = 'Ongoing';
						}
				}
			
			
			//edit by fahmi
			
			$ret .= "<tr><td id='tdclass_${id}' style='margin: 0px; padding:0px;' colspan=11 >"
					. "<table style='width:100%'><colgroup><col width='3%'/><col width='17%'/><col width='5%'/><col width='12%'/><col width='10%'/><col width='10%'/><col width='10%'/><col width='7%'/><col width='7%'/><col width='8%'/><col width='8%'/></colgroup><tbody><tr>"
					. "<td id='td_num_loop_${id}' style='text-align:center;width:50px'>$numberloop</td>"
					. "<td id='td_name_${id}' >$name</td>"
					. "<td><span id='sp_${id}' ; '>".$participantname ."</span></td>"
					. "<td id='td_subject_${id}' style='text-align: left;'>$subject</td>"
					. "<td id='td_objectives_${id}' style='text-align: left;'>$objectives</td>"
					. "<td id='td_schedule_${id}' style='text-align: center; font-size:10px;'>$schedule_start - $schedule_end</td>"
					. "<td id='td_cost_${id}' style='text-align: right; padding-right: 10px;'>$. $cost</td>"
					. "<td id='td_ins_int_${id}' style='text-align: center;'>$ins_int</td>"
					. "<td id='td_ins_ext_${id}' style='text-align: center;'>$ins_ext</td>"
					. "<td id='td_remark_${id}' style='text-align: center;'>$remark </td>"
					. "<td id='td_status_cancel_${id}' style='text-align: center;'>".$stat_can."</td>"
					. "</tr></tbody></table>"
                  . "</td></tr>";
				  
				  
				  
				  /*  $hdr = "<table><colgroup><col width='60'/><col/></colgroup><tbody><tr>"
				   . "<td id='td_periode_${psid}'width=75>$year</td>"
				   . "<td width=300><span id='sp_${psid}' class='xlnk' onclick='edit_session(\"$psid\",this,event);' >"._EMPTY."</span></td>"
				   . "<td id='td_org_nm_${psid}' width=275 style='text-align: left;'>$org_id</td>"
				   . "</tr></tbody></table>"; */
				   
			}
	
		 }
	
	$sqlbdgt = "SELECT budget"
				.   " FROM antrain_sessionss"
				.	" WHERE psid='$psid'";
	$resultbdgt = $db->query($sqlbdgt);
	list($budget)=$db->fetchRow($resultbdgt);
	
	$sql = "SELECT SUM(cost) FROM antrain_plan_specific WHERE id_antrain_session = '$psid' AND is_deleted = 'F' AND status_cancel = 'F' OR status_cancel = 'A'";
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
	  $ret .= "<tr>
						<td colspan=6 style='border-right: 1px solid #CCC;border-top: 1px solid #CCC'><strong>Total Cost Estimation</strong></td>
						<td style='border-right: 1px solid #CCC;border-top: 1px solid #CCC; text-align: right; padding-right: 12px;'>$. $sumcost</td>
						<td style='border-right: 1px solid #CCC;border-top: 1px solid #CCC'></td><td style='border-right: 1px solid #CCC;border-top: 1px solid #CCC'></td>
						<td  style='border-right: 1px solid #CCC; border-top: 1px solid #CCC;' ></td>
						<td  style='border-right: 1px solid #CCC; border-top: 1px solid #CCC;' ></td>
					</tr>";
	  $ret .= "<tr>
						<td colspan=6 style='border-right: 1px solid #CCC'><strong>Budget in year</strong></td>
						<td style='border-right: 1px solid #CCC; text-align: right; padding-right: 12px;'>$. $budget</td>
						<td style='border-right: 1px solid #CCC' ></td><td style='border-right: 1px solid #CCC' ></td>
						<td style='border-right: 1px solid #CCC' ></td><td style='border-right: 1px solid #CCC' ></td>
					</tr>";
      
	  $ret .= "</tbody>";
      $ret .= "</table>";
      $ret .= "</div>";
     
   
   $sql = "SELECT p.user_id,ps.person_nm,pk.date_inform FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id LEFT JOIN antrain_sessionss pk ON p.user_id = pk.id_inform WHERE pk.psid = '$psid'";  
     $result = $db->query($sql);
     list($user_id_inform,$inform_by,$date_inform)=$db->fetchRow($result);
     $date_inform = date('d/M/Y', strtotime($date_inform));
	 
	 $sql = "SELECT p.user_id,ps.person_nm,pk.date_ackn FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id LEFT JOIN antrain_sessionss pk ON p.user_id = pk.id_ackn  WHERE pk.psid = '$psid'";  
     $result = $db->query($sql);
     list($user_id_ackn,$ackn_by,$date_ackn)=$db->fetchRow($result);
     $date_ackn = date('d/M/Y', strtotime($date_ackn));
	 
	 $sql = "SELECT p.user_id,ps.person_nm,pk.date_appralter FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id LEFT JOIN antrain_sessionss pk ON p.user_id = pk.id_appralter  WHERE pk.psid = '$psid'";  
     $result = $db->query($sql);
     list($user_id_appralter,$appralter_by,$date_appralter)=$db->fetchRow($result);
     $date_appralter = date('d/M/Y', strtotime($date_appralter));
	 
	 if($date_inform == '01/Jan/1970')
	 {
		$date_inform = '-';
	 }
	 else
	 {
	 $date_inform = $date_inform;
	 }
	 
	  if($date_ackn == '01/Jan/1970')
	  {
		$date_ackn = '-';
	 }
	 else
	 {
	 $date_ackn = $date_ackn;
	 }
	 
	  if($date_appralter == '01/Jan/1970')
	  {
		$date_appralter = '-';
	 }
	 else
	 {
	 $date_appralter = $date_appralter;
	 }
	 
	 $form .= "<div style='text-align:right;padding:10px;margin-top:10px;margin-bottom:100px;'>"
				. "<table align='left' style='border-top:none solid #777;border-left:none solid #777;border-spacing:0px;'>"
                . "<colgroup>"
                . "<col width='500'/>"
              
                . "</colgroup>"
                . "<tbody>"
                . "<tr>"
                . "<td style='text-align:left;border-bottom:none solid #777;border-right:none solid #777;'>"
                . "Note:"
                . "</td>"
               
                . "</tr>"
                . "<tr>"
                . "<td style='text-align:left;border-bottom:none solid #bbb;border-right:none solid #777; font-size: 10px;'>"
                . " &nbsp &nbsp &nbsp * Instructor: Int = Internal, Ext = External <br/>"
				. " &nbsp &nbsp &nbsp ** If total Estimation Cost more than budget in year, please fill a reason in remarks column. <br/>"
                . "</td>"
         
                
                . "</tr>"
  
                . "</tbody>"
                . "</table>"               

			   . "<table align='right' style='border-top:2px solid #777;border-left:2px solid #777;border-spacing:0px;'>"
                . "<colgroup>"
                . "<col width='200'/>"
				. "<col width='200'/>"
                . "<col width='200'/>"
                . "</colgroup>"
                . "<tbody>"
                . "<tr>"
                . "<td style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;'>"
                . "Informed by,"
                . "</td>"
				. "<td style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;'>"
                . "Acknowledged by,"
                . "</td>"
                . "<td  style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;'>"
                . "Approved by,"
                . "</td>"
                . "</tr>"
				
				 . "<tr>"
               
				. "<td height=60 style='text-align:center;border-bottom:0px solid #bbb;border-right:2px solid #777;'>"
				. "<span style= margin:5px;'>$informbutton</span>"
                . "</td>"
               
				. "<td style='text-align:center;border-bottom:0px solid #bbb;border-right:2px solid #777;'>"
				. "<span style= margin:5px;'>$acknbutton</span>"
                . "</td>"

				. "<td style='text-align:center;border-bottom:0px solid #bbb;border-right:2px solid #777;'>"
				. "<span style= margin:5px;'>$appralterbutton</span>"
                . "</td>"
                
                . "</tr>"
				
                . "<tr>"
                . "<td id=informby_${psid} style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
                . "$inform_by <br/> Section Manager"
                . "</td>"
                . "<td id=acknby_${psid} style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
				. "$ackn_by<br/>Division Manager"
                . "</td>"
				. "<td id=appralterby_${psid} style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
				. "$appralter_by<br/>MGT.Representative"
                . "</td>"
                
                
                . "</tr>"
                . "<tr>"
                . "<td id=dateinform_${psid} style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;padding:5px;' id='submited_by_button'>"
                . "Submitted on:<br/> $date_inform"
                . "</td>"
                . "<td id=dateackn_${psid} style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;padding:5px;'>"
                 . "Submitted on:<br/> $date_ackn"
				. "</td>"
				 . "<td id=dateappralter_${psid} style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;padding:5px;'>"
                 . "Submitted on:<br/> $date_appralter"
				. "</td>"
                
                . "</tr>"
                . "</tbody>"
                . "</table>"
                . "</div>";
      
      
      
      
      $js = $ajax->getJs()."<script type='text/javascript' src='".XOCP_SERVER_SUBDIR."/include/calendar.js'></script><script type='text/javascript'>//<![CDATA[
      
      
      //////////////////////////////////////////////////////////////////////////////
	
	      

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
         td.setAttribute('colspan','11');
         td.setAttribute('style','padding:0px;');
		 wdv = td.appendChild(wdv);
         wdv.appendChild(progress_span());
         wdv.td = td;
         orgjx_app_editSession(id,function(_data) {
            wdv.innerHTML = _data;
            $('inp_remark').focus();
         });
      }
	  
	  function inform() {
       // alert('test');
	 	var ret = parseForm('informbutton');
         //$('progress').appendChild(progress_span('&nbsp;...saving&nbsp;&nbsp;'));
		orgjx_app_inform (ret,function(_data) {
		alert('email sent');
            var data = recjsarray(_data);
			$('informby_'+data[0]).innerHTML = data[4] + '<br/> Section Manager';
			$('dateinform_'+data[0]).innerHTML = 'Submitted on: ' +'<br>' + data[3];
			$('informbut').setAttribute('style','display:none;');
			//setTimeout(\"$('progress').innerHTML = '';\",1000);
         }); 
      }
	  
	  function ackn() {
        //alert('test');
		var ret = parseForm('acknbutton');
         //$('progress').appendChild(progress_span('&nbsp;...saving&nbsp;&nbsp;'));
		orgjx_app_ackn (ret,function(_data) {
		alert('email sent');
            var data = recjsarray(_data);
			$('acknby_'+data[0]).innerHTML = data[4] + '<br/> Division Manager';
			$('dateackn_'+data[0]).innerHTML = 'Submitted on: ' +'<br>' + data[3];
			$('acknbut').setAttribute('style','display:none;');
			//setTimeout(\"$('progress').innerHTML = '';\",1000);
         }); 
      }
	  
	  function appralter() {
       // alert('test');
	 	var ret = parseForm('appralterbutton');
         //$('progress').appendChild(progress_span('&nbsp;...saving&nbsp;&nbsp;'));
		orgjx_app_appralter (ret,function(_data) {
		alert('email sent');
            var data = recjsarray(_data);
			$('appralterby_'+data[0]).innerHTML = data[4] +  '<br/>MGT.Representative';
			$('dateappralter_'+data[0]).innerHTML = 'Submitted on: ' +'<br>' + data[3];
			$('appralterbut').setAttribute('style','display:none;');
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
         orgjx_app_Delete(wdv.id,null);
         var tr = wdv.parentNode.parentNode;
         _destroy(tr);
         wdv.id = null;
         wdv = null;
		 location.reload();
		 
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
			
			var stat_can = null;
			if (data[11] == 'T')
			{
				stat_can = 'Cancelled';
			}
			else
			{
				stat_can = 'Ongoing';
			}
			
			$('sp_'+data[0]).innerHTML = participantname;
			$('td_name_'+data[0]).innerHTML = data[1];
			$('td_subject_'+data[0]).innerHTML = data[2];
			$('td_objectives_'+data[0]).innerHTML = data[3];
			$('td_schedule_'+data[0]).innerHTML = data[4] + ' - ' +data[5];
			$('td_cost_'+data[0]).innerHTML = '$.'+data[6];
			$('td_ins_int_'+data[0]).innerHTML = internal;		
			$('td_ins_ext_'+data[0]).innerHTML = external;
			$('td_remark_'+data[0]).innerHTML = data[9];
            $('inp_remark').focus();
			$('td_status_cancel_'+data[0]).innerHTML = stat_can;
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
            $ret = $this->antrainsscancelalterview($self_employee_id);
            break;
         default:
            $ret = $this->antrainsscancelalterview($self_employee_id);
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