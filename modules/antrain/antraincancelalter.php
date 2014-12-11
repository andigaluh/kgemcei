<?php
//--------------------------------------------------------------------//
// Filename : modules/antrain/antraincancelalter.php                         //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2010-09-22                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('ANTRAIN_PLAN_DEFINED') ) {
   define('ANTRAIN_PLAN_DEFINED', TRUE);

//include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
//include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
include_once(XOCP_DOC_ROOT."/modules/antrain/class/ajax_cancelalter.php");
require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
include_once(XOCP_DOC_ROOT."/modules/antrain/class/selectantraincancelalter.php");

class _antrain_Cancelalter extends XocpBlock {
   var $catchvar = _ANTRAIN_CATCH_VAR;
   var $blockID = _ANTRAIN_PLAN_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = 'General Training Plan - Cancellation/Alteration';
   var $display_comment = TRUE;
   var $data;
   
   function _antrain_Plan($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */

   }
   
   
   function antraincancelalter() {
      $psid = $_SESSION["pms_psid"];
      global $xocp_vars;
      
      
      $db=&Database::getInstance();
      $ajax = new _antrain_class_CancelalterAjax("cantrjx");
      
	  $user_id = getUserID();
      
      $current_year = 2013;
	  
	  $sql = "SELECT p.year, p.is_inform, p.is_ackn,p.is_appralter,p.date_inform,p.date_ackn,p.date_appralter, pk.org_nm, pk.org_class_id FROM antrain_session p LEFT JOIN hris_orgs pk ON p.org_id = pk.org_id WHERE psid = ('$psid') ORDER BY year DESC" ;
	  
	  $result = $db->query($sql);
      list($year,$is_inform,$is_ackn,$is_appralter,$date_inform,$date_ackn,$date_appralter,$org_nm)=$db->fetchRow($result);
	
	$informbutton = "<form id='informbutton' method= 'post'><input type='hidden' name='id_proposed' value='$id_inform' ><input type='hidden' name='is_inform' value='$is_inform' ><input type='hidden' name='date_inform' value='$dt_inform' ><input type='button' onclick='inform();' value='Inform'  id='informbut'></form>";
	
	$acknbutton = "<form id='acknbutton' method= 'post'><input type='hidden' name='id_ackn' value='$id_ackn' ><input type='hidden' name='is_ackn' value='$is_ackn' ><input type='hidden' name='date_ackn' value='$dt_ackn' ><input type='button' onclick='ackn();' value='Acknowledge'  id='acknbut'></form>";
	
	$appralterbutton = "<form id='appralterbutton' method= 'post'><input type='hidden' name='id_appralter' value='$id_appralter' ><input type='hidden' name='is_appralter' value='$is_appralter' ><input type='hidden' name='date_appralter' value='$dt_appralter' ><input type='button' onclick='appralter();' value='Approve'  id='appralterbut'></form>";
	
	$returnbtnsm = "<span style='float:right; margin:5px;'><input onclick='return_sessionsm(\"$psid\",this,event);' type='button' value='Return' id='returnbtnsm'/></span>";  
	$returnbtnhd = "<span style='float:right; margin:5px;'><input onclick='return_sessionhd(\"$psid\",this,event);' type='button' value='Return' id='returnbtnhd'/></span>";  
	$returnbtnrps = "<span style='float:right; margin:5px;'><input onclick='return_sessionrps(\"$psid\",this,event);' type='button' value='Return' id='returnbtnrps'/></span>"; 		
	$returnbtn = "<span style='float:right; margin:5px;'><input onclick='return_session(\"$psid\",this,event);' type='button' value='Return' id='returnbtn'/></span>"; 	
	$remindbtn = "<span style='float:right; margin:5px;'><input onclick='remind_btn(\"$psid\",this,event);' type='button' value='Remind' id='remindbtn'/></span>";  
	if ($sumcost == 0)
	{
		$sumcost = '0' ;
	}
	else
	{
		$sumcost = $sumcosts;
	}
	
	//$sqljob = "SELECT j.job_id FROM hris_employee_job j LEFT JOIN hris_users u ON (u.person_id = j.employee_id) WHERE u.user_id = ".getUserid()."";
	
	$sqljob = "SELECT j.job_id, a.job_class_id FROM hris_employee_job j LEFT JOIN hris_users u ON ( u.person_id = j.employee_id ) LEFT JOIN hris_jobs a ON ( j.job_id = a.job_id ) WHERE u.user_id = ".getUserid()."";
	$resultjob = $db->query($sqljob);
	 list($job_id,$job_class_id)=$db->fetchRow($resultjob);
		 
	  if ($job_class_id = 3 || $job_id == 68 || $job_id == 999 || $job_id == 1)
	 {
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
	
	 if ( $job_class_id = 2 || $job_class_id = 1 || $job_id == 999 || $job_id == 1 || $job_id == 146)
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
	
	
	if ($job_class_id = 2 || $job_class_id = 1  || $job_id == 6 || $job_id == 999 || $job_id == 1) 
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
	
	//CONDITION RETURN BUTTON

	if( $job_id == 68 || $get_userid == 1)
 	{
		$returnbtnsm = $returnbtnsm;
	}
	else
	{
		$returnbtnsm = '';
	}
	
	if( $job_id == 146 || $get_userid == 1 )
 	{
		$returnbtnhd = $returnbtnhd;
	}
	else
	{
		$returnbtnhd = '';
	}
	
	if( $job_id == 6 || $get_userid == 1 )
 	{
		$returnbtnrps = $returnbtnrps;
	}
	else
	{
		$returnbtnrps = '';
	}
    
	$tooltip = "<div id='all_ap_tooltip' style='display:none;'>";
      

      $ret .= "<div>";
      
     /*  $ret .= $return_notes;
      $ret .= $report_return_notes;
       */
      $ret .= "<table style='margin-top:5px;table-layout:fixed;' class='xxlist'>";
      
      $ret .= "<div style='padding:5px;font-size:20px;color: #666666; text-align:center;'>TRAINING  CANCELLATION/ALTERATION</div>"
			 . "<div style='padding:5px;font-size:15px;color: #666666; text-align:center;'>(General Subject /Year $year )</div>"
			 . "<div style='padding:2px;font-size:12px;color: #666666; text-align:left;'>Division/ Section:  $org_nm .";
	  $ret .= //"<span style='float:right; margin:5px;'><input onclick='new_session();' type='button' value='Add'/></span> "
			"$returnbtnsm $returnbtnhd $returnbtnrps <div id='id_return'></div><div   	id='id_returnrps'></div><div id='id_returnhd'></div><div id='id_returnsm'></div>"
			 
			. "<colgroup><col width='4%'/><col width='12%'/><col width='10%'/><col width='10%'/><col width='16%'/><col width='12%'/><col width='12%'/><col width='7%'/><col width='14%'/><col width='7%'/></colgroup>"
			
			. "<thead><tr><td rowspan='3' style='border-right: 1px solid #CCC; text-align: center;'>No.</td><td style='border-right: 1px solid #CCC; text-align:center; ' colspan='3'>Employees</td><td style='border-right: 1px solid #CCC;  text-align:center;' colspan='4'>Training/ Seminar Programs</td><td style='border-right: 1px solid #CCC;  text-align:center;' rowspan='3'>Remarks</td><td style='border-right: 1px solid #CCC;  text-align:center;' rowspan='3'>Status Cancellation</td></tr>"
			. "<tr><td style='border-right: 1px solid #CCC;  text-align:center;' rowspan='2'>Name</td><td style='border-right: 1px solid #CCC;  text-align:center;' rowspan='2'>Position Level</td><td style='border-right: 1px solid #CCC; text-align:center;' rowspan='2'>Section/Division</td><td style='border-right: 1px solid #CCC;  text-align:center;' rowspan='2'>Subject</td><td style='border-right: 1px solid #CCC; text-align:center;' colspan='3'>Schedule</td></tr>"
			. "<tr><td style='border-right: 1px solid #CCC; text-align:center;'>Date</td><td style='border-right: 1px solid #CCC; text-align:center;'>Place</td><td style='border-right: 1px solid #CCC; text-align:center;'>Cost</td></tr></thead>";
   
      $ret .= "</tr>"
             . "</thead>";
      
      $ret .= "<tbody>";
      
	$sql = "SELECT p.id, p.name, p.number, p.subject, p.objectives, p.schedule_start, p.schedule_end,p.place,p.cost, p.id_pr_group1, p.id_pr_group2, p.remark,p.status_cancel, ps.antrain_peergroup_nm , ps2.antrain_peergroup_nm AS peer_group_nm2 FROM antrain_plan_general p LEFT JOIN antrain_session pk ON p.id_antrain_session = pk.psid LEFT JOIN antrain_peer_group ps ON p.id_pr_group1 = ps.id_antrain_peergroup LEFT JOIN antrain_peer_group ps2 ON p.id_pr_group2 = ps2.id_antrain_peergroup WHERE p.id_antrain_session = '$psid' AND p.is_deleted = 'F' ";
	$result = $db->query($sql);
	  if($db->getRowsNum($result)>0) {
	while(list($id,$name,$number,$subject,$objectives,$schedule_start,$schedule_end,$place,$cost,$id_pr_group1,$id_pr_group2, $remark,$status_cancel, $peer_group_nm,$peer_group_nm2)=$db->fetchRow($result)){
	 //return array($id,$number,$subject,$objectives,$schedule_start,$schedule_end,$cost,$id_pr_group1,$id_pr_group2,$remark,$inst);	
		   
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
			
				
		  $schedule_start = date('d M Y', strtotime($schedule_start));
		  $schedule_end = date('d M Y', strtotime($schedule_end));
		  $numberloop = $numberloop + 1;
		  
		  if($id_pr_group2 <> 0 && $id_pr_group2 <> NULL )
		  {
			$participant = htmlentities(stripslashes($peer_group_nm))." - ".htmlentities(stripslashes($peer_group_nm2));
		  }
		  else
		  {
			$participant = htmlentities(stripslashes($peer_group_nm));
		   }
				 
			//edit by andi
			  $ret .= "<tr><td id='tdclass_${id}' style='margin: 0px; padding:0px;' colspan=11 >"
					. "<table style='width:100%'><colgroup><col width='3%'/><col width='12%'/><col width='10%'/><col width='10%'/><col width='15%'/><col width='12%'/><col width='12%'/><col width='6%'/><col width='15%'/><col width='7%'/></colgroup><tbody><tr>"
				  . "<td id='td_num_loop_${id}' style='text-align:center;width:50px'>$numberloop</td>"
				   . "<td id='td_name_${id}' style='text-align: center;'>$name</td>"
				  //   . "<td><span id='sp_${id}' class='xlnk' onclick='edit_session(\"$id\",this,event); '>".$participant ."</span></td>"
				  . "<td id='td_participant_${id}' style='text-align: center;'>$participant</td>"
               	  . "<td id='td_secdiv_${id}' style='text-align: center;'>MCCI</td>"
				  . "<td id='td_subject_${id}' style='text-align: left;'>$subject</td>"
				  . "<td id='td_schedule_${id}' style='text-align: center; font-size:10px;'>$schedule_start - $schedule_end</td>"
				  . "<td id='td_place_${id}' style='text-align: center;'>$place</td>"
				  . "<td id='td_cost_${id}' style='text-align: right; padding-right: 10px;'>$. $cost</td>"				 
				  . "<td id='td_remark_${id}' style='text-align: center;'>$remark </td>"
				  . "<td><span id='sp_${id}' class='xlnk' onclick='edit_session(\"$id\",this,event); '>".$stat_can."</span></td>"
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
				.   " FROM antrain_session"
				.	" WHERE psid='$psid'";
	$resultbdgt = $db->query($sqlbdgt);
	list($budget)=$db->fetchRow($resultbdgt);
	
	$sql = "SELECT SUM(cost) FROM antrain_plan_general WHERE id_antrain_session = '$psid' AND is_deleted = 'F' AND status_cancel = 'F' OR status_cancel = 'A'";
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
	  $ret .= "<tr><td colspan=7 style='border-right: 1px solid #CCC;border-top: 1px solid #CCC'><strong>Total Cost Estimation</strong></td><td style='border-right: 1px solid #CCC;border-top: 1px solid #CCC; text-align: right; padding-right: 12px;'>$. $sumcost</td><td style='border-right: 1px solid #CCC;border-top: 1px solid #CCC'></td><td style='border-right: 1px solid #CCC;border-top: 1px solid #CCC'></td><td  style='border-right: 1px solid #CCC; border-top: 1px solid #CCC;' ></td></tr>";
	  $ret .= "<tr><td colspan=7 style='border-right: 1px solid #CCC'><strong>Budget in year</strong></td><td style='border-right: 1px solid #CCC; text-align: right; padding-right: 12px;'>$. $budget</td><td style='border-right: 1px solid #CCC' ></td><td style='border-right: 1px solid #CCC' ></td><td style='border-right: 1px solid #CCC' ></td></tr>";
      
	  $ret .= "</tbody>";
      $ret .= "</table>";
      $ret .= "</div>";
     
	
   
     $sql = "SELECT p.user_id,ps.person_nm,pk.date_inform FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id LEFT JOIN antrain_session pk ON p.user_id = pk.id_inform WHERE pk.psid = '$psid'";  
     $result = $db->query($sql);
     list($user_id_inform,$inform_by,$date_inform)=$db->fetchRow($result);
     $date_inform = date('d/M/Y', strtotime($date_inform));
	 
	 $sql = "SELECT p.user_id,ps.person_nm,pk.date_ackn FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id LEFT JOIN antrain_session pk ON p.user_id = pk.id_ackn  WHERE pk.psid = '$psid'";  
     $result = $db->query($sql);
     list($user_id_ackn,$ackn_by,$date_ackn)=$db->fetchRow($result);
     $date_ackn = date('d/M/Y', strtotime($date_ackn));
	 
	 $sql = "SELECT p.user_id,ps.person_nm,pk.date_appralter FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id LEFT JOIN antrain_session pk ON p.user_id = pk.id_appralter  WHERE pk.psid = '$psid'";  
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
                . " &nbsp &nbsp &nbsp <strong>Please write down the reason of cancellation/alteration</strong> <br/>"
			
                . "</td>"
         
                
                . "</tr>"
  
                . "</tbody>"
                . "</table>"               

			   . "<table align='right' style='border-top:2px solid #777;border-left:2px solid #777;border-spacing:0px;'>"
                . "<colgroup>"
                . "<col width='175'/>"
                . "<col width='175'/>"
				. "<col width='175'/>"
                . "</colgroup>"
                . "<tbody>"
                . "<tr>"
                . "<td style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;'>"
                . "Informed by,"
                . "</td>"
                . "<td style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;'>"
                . "Acknowledged by,"
                . "</td>"
				  . "<td style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;'>"
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
         cantrjx_app_newSession(function(_data) {
            var data = recjsarray(_data);
            wdv.td.setAttribute('id','tdclass_' + data[0] );
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
         var td = $('tdclass_' + id);
         wdv.setAttribute('style','padding:10px;');
         td.setAttribute('colspan','10');
         td.setAttribute('style','padding:0px;');
		 wdv = td.appendChild(wdv);
         wdv.appendChild(progress_span());
         wdv.td = td;
         cantrjx_app_editSession(id,function(_data) {
            wdv.innerHTML = _data;
            $('inp_remark').focus();
         });
      }
	  
	  function inform() {
       
	 	var ret = parseForm('informbutton');
         //$('progress').appendChild(progress_span('&nbsp;...saving&nbsp;&nbsp;'));
		cantrjx_app_inform (ret,function(_data) {
		
            var data = recjsarray(_data);
			$('informby_'+data[0]).innerHTML = data[4] + '<br/> Section Manager';
			$('dateinform_'+data[0]).innerHTML = 'Submitted on: ' +'<br>' + data[3];
			$('informbut').setAttribute('style','display:none;');
			//setTimeout(\"$('progress').innerHTML = '';\",1000);
         }); 
      }
	  
	  function ackn() {
        
		var ret = parseForm('acknbutton');
         //$('progress').appendChild(progress_span('&nbsp;...saving&nbsp;&nbsp;'));
		cantrjx_app_ackn (ret,function(_data) {
		
            var data = recjsarray(_data);
			$('acknby_'+data[0]).innerHTML = data[4] + '<br/> Division Manager';
			$('dateackn_'+data[0]).innerHTML = 'Submitted on: ' +'<br>' + data[3];
			$('acknbut').setAttribute('style','display:none;');
			//setTimeout(\"$('progress').innerHTML = '';\",1000);
         }); 
      }
	  
	  function appralter() {
       
	 	var ret = parseForm('appralterbutton');
         //$('progress').appendChild(progress_span('&nbsp;...saving&nbsp;&nbsp;'));
		cantrjx_app_appralter (ret,function(_data) {
		
            var data = recjsarray(_data);
			$('appralterby_'+data[0]).innerHTML = data[4] +  '<br/> MGT Represebtative';
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
         cantrjx_app_Delete(wdv.id,null);
         var tr = wdv.parentNode.parentNode;
         _destroy(tr);
         wdv.id = null;
         wdv = null;
		 // setTimeout(\"$('progress').innerHTML = '';\",1000);
		 location.reload(),delay(1000);
		 
      }
      
      function save_session() {
         var ret = parseForm('frm');
        $('progress').appendChild(progress_span('&nbsp;...saving&nbsp;&nbsp;'));
		cantrjx_app_saveSession (wdv.id,ret,function(_data) {
            var data = recjsarray(_data);
			var participant = null;
			if (data[8] != 0 && data[8] != null) {
			participant = data[11] + '-' + data[12];
			}else{
			participant = data[11] ;
			}
				
			var stat_can = null;
			if (data[10] == 'T')
			{
				stat_can = 'Cancelled';
			}
			else
			{
				stat_can = 'Ongoing';
			}
			
			$('td_participant_'+data[0]).innerHTML = participant;
			$('td_name_'+data[0]).innerHTML = data[1];
			$('td_secdiv_'+data[0]).innerHTML = 'MCCI';
			$('td_subject_'+data[0]).innerHTML = data[2];
			$('td_schedule_'+data[0]).innerHTML = data[3] + ' - ' + data[4];
			$('td_place_'+data[0]).innerHTML = data[5];
			$('td_cost_'+data[0]).innerHTML = '$.' + data[6];
			$('td_remark_'+data[0]).innerHTML = data[9];
			$('sp_'+data[0]).innerHTML = stat_can;
			
            setTimeout(\"$('progress').innerHTML = '';\",800);
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
		 
		$('id_return').setAttribute('style','display:inline;')
         // wdv = _dce('div');
         // wdv.psid = psid;
         // var td = $('returnbtn');
         // wdv.setAttribute('style','padding:10px;');
         // wdv = td.appendChild(wdv);
        // wdv.appendChild(progress_span());
         // wdv.td = td;
         cantrjx_app_reminder(psid,function(_data) {
           // wdv.innerHTML = _data;
		    $('id_return').innerHTML = _data;
           // $('inp_year').focus();
         });
      }
	  
	   function send_reminder() {
       
         //$('progress').appendChild(progress_span('&nbsp;...saving&nbsp;&nbsp;'));
		 
         cantrjx_app_send_reminder(function(_data) {
		
            var data = recjsarray(_data);
			if (data[0] = 1){
				alert('email sent');
			}
		
            setTimeout(\"$('progress').innerHTML = '';\",1000);
			$('id_return').setAttribute('style','display:none;');
		
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
		 
		$('id_return').setAttribute('style','display:inline;')
         // wdv = _dce('div');
         // wdv.psid = psid;
         // var td = $('returnbtn');
         // wdv.setAttribute('style','padding:10px;');
         // wdv = td.appendChild(wdv);
        // wdv.appendChild(progress_span());
         // wdv.td = td;
         cantrjx_app_returnSession(psid,function(_data) {
           // wdv.innerHTML = _data;
		    $('id_return').innerHTML = _data;
           $('inp_year').focus();
         });
      }
	  
	  function save_return(id) {
         var ret = parseForm('frm');
         $('progress').appendChild(progress_span('&nbsp;...saving&nbsp;&nbsp;'));
         cantrjx_app_saveReturn(id,ret,function(_data) {
            var data = recjsarray(_data);
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
 
 
 //return hd
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
		 
		$('id_return').setAttribute('style','display:inline;')
         // wdv = _dce('div');
         // wdv.psid = psid;
         // var td = $('returnbtn');
         // wdv.setAttribute('style','padding:10px;');
         // wdv = td.appendChild(wdv);
        // wdv.appendChild(progress_span());
         // wdv.td = td;
         cantrjx_app_returnSession(psid,function(_data) {
           // wdv.innerHTML = _data;
		    $('id_return').innerHTML = _data;
           $('inp_year').focus();
         });
      }
	  
	  function save_return(id) {
         var ret = parseForm('frm');
         $('progress').appendChild(progress_span('&nbsp;...saving&nbsp;&nbsp;'));
         cantrjx_app_saveReturn(id,ret,function(_data) {
            var data = recjsarray(_data);
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
	  
	  //RETURN SM
	  
	  var wdv = null;
      function return_sessionsm(psid,d,e) {
         if(wdv) {
            if(wdv.psid == psid) {
               cancel_returnsm();
               return;
            } else {
               cancel_returnsm();
            }
         }
		 
		$('id_returnsm').setAttribute('style','display:inline;')
         // wdv = _dce('div');
         // wdv.psid = psid;
         // var td = $('returnbtnsm');
         // wdv.setAttribute('style','padding:10px;');
         // wdv = td.appendChild(wdv);
        // wdv.appendChild(progress_span());
         // wdv.td = td;
         cantrjx_app_returnSessionsm(psid,function(_data) {
           // wdv.innerHTML = _data;
		    $('id_returnsm').innerHTML = _data;
           $('inp_year').focus();
         });
      }
	  
	  function save_returnsm(id) {
         var ret = parseForm('frm');
         $('progress').appendChild(progress_span('&nbsp;...saving&nbsp;&nbsp;'));
         cantrjx_app_saveReturnsm(id,ret,function(_data) {
            var data = recjsarray(_data);
            // $('sp_'+data[0]).innerHTML = data[1];
			// $('td_remark_'+data[0]).innerHTML = data[2];
            // $('inp_year').focus();
            setTimeout(\"$('progress').innerHTML = '';\",1000);
			$('id_returnsm').setAttribute('style','display:none;');
         });
      }
	  
	   function cancel_returnsm() {
        // wdv.td.style.backgroundColor = '';
        // wdv.psid = null;
		$('id_returnsm').setAttribute('style','display:none;');
         //_destroy($('id_returnsm'));
         //wdv = null;
      }
	  
	  //RETURN HD
	  
	  var wdv = null;
      function return_sessionhd(psid,d,e) {
         if(wdv) {
            if(wdv.psid == psid) {
               cancel_returnhd();
               return;
            } else {
               cancel_returnhd();
            }
         }
		 
		$('id_returnhd').setAttribute('style','display:inline;')
         // wdv = _dce('div');
         // wdv.psid = psid;
         // var td = $('returnbtnhd');
         // wdv.setAttribute('style','padding:10px;');
         // wdv = td.appendChild(wdv);
        // wdv.appendChild(progress_span());
         // wdv.td = td;
         cantrjx_app_returnSessionhd(psid,function(_data) {
           // wdv.innerHTML = _data;
		    $('id_returnhd').innerHTML = _data;
           $('inp_year').focus();
         });
      }
	  
	  function save_returnhd(id) {
         var ret = parseForm('frm');
         $('progress').appendChild(progress_span('&nbsp;...saving&nbsp;&nbsp;'));
         cantrjx_app_saveReturnhd(id,ret,function(_data) {
            var data = recjsarray(_data);
            // $('sp_'+data[0]).innerHTML = data[1];
			// $('td_remark_'+data[0]).innerHTML = data[2];
            // $('inp_year').focus();
            setTimeout(\"$('progress').innerHTML = '';\",1000);
			$('id_returnhd').setAttribute('style','display:none;');
         });
      }
	  
	   function cancel_returnhd() {
        // wdv.td.style.backgroundColor = '';
        // wdv.psid = null;
		$('id_returnhd').setAttribute('style','display:none;');
         //_destroy($('id_returnhd'));
         //wdv = null;
      }
	  
	  //RETURN RPS
	  
	  var wdv = null;
      function return_sessionrps(psid,d,e) {
         if(wdv) {
            if(wdv.psid == psid) {
               cancel_returnrps();
               return;
            } else {
               cancel_returnrps();
            }
         }
		 
		$('id_returnrps').setAttribute('style','display:inline;')
         // wdv = _dce('div');
         // wdv.psid = psid;
         // var td = $('returnbtnrps');
         // wdv.setAttribute('style','padding:10px;');
         // wdv = td.appendChild(wdv);
        // wdv.appendChild(progress_span());
         // wdv.td = td;
         cantrjx_app_returnSessionrps(psid,function(_data) {
           // wdv.innerHTML = _data;
		    $('id_returnrps').innerHTML = _data;
           $('inp_year').focus();
         });
      }
	  
	  function save_returnrps(id) {
         var ret = parseForm('frm');
         $('progress').appendChild(progress_span('&nbsp;...saving&nbsp;&nbsp;'));
         cantrjx_app_saveReturnrps(id,ret,function(_data) {
            var data = recjsarray(_data);
            // $('sp_'+data[0]).innerHTML = data[1];
			// $('td_remark_'+data[0]).innerHTML = data[2];
            // $('inp_year').focus();
            setTimeout(\"$('progress').innerHTML = '';\",1000);
			$('id_returnrps').setAttribute('style','display:none;');
         });
      }
	  
	   function cancel_returnrps() {
        // wdv.td.style.backgroundColor = '';
        // wdv.psid = null;
		$('id_returnrps').setAttribute('style','display:none;');
         //_destroy($('id_returnrps'));
         //wdv = null;
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
            $ret = $this->antraincancelalter($self_employee_id);
            break;
         default:
            $ret = $this->antraincancelalter($self_employee_id);
            break;
      }

	  	$sqljob = "SELECT j.job_id FROM hris_employee_job j LEFT JOIN hris_users u ON (u.person_id = j.employee_id) WHERE u.user_id = ".getUserid()."";
	$resultjob = $db->query($sqljob);
	 list($job_id)=$db->fetchRow($resultjob);
	//echo 'getuserid = '.getuserid(). '<br> job_id = '.$job_id; exit();
	 	 if ($job_id ==  130 || $job_id == 90 || $job_id == 68 || $job_id == 101 || $job_id == 0 || $job_id == 258){
      return $antrainsel.$ret;
	  }
	 // elseif( getUserID() == '' ) { }
	  else
	  {
	  return  'you have no access';
}
	  
	  
   }
}

} // PMS_MYACTIONPLAN_DEFINED
?>