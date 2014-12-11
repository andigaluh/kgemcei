<?php
//--------------------------------------------------------------------//
// Filename : modules/antrain/antrainplan.php                         //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2010-09-22                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('ANTRAIN_PLAN_MATRIX_DEFINED') ) {
   define('ANTRAIN_PLAN_MATRIX_DEFINED', TRUE);

//include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
//include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
include_once(XOCP_DOC_ROOT."/config.php");
global $xocpConfig;

include_once(XOCP_DOC_ROOT."/modules/antrain/class/ajax_objective.php");
require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
include_once(XOCP_DOC_ROOT."/modules/antrain/class/selectantrain.php");

class _antrain_Plan_matrix extends XocpBlock {
   var $catchvar = _ANTRAIN_CATCH_VAR;
   var $blockID = _ANTRAIN_PLAN_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = 'Annual Training Plan Matrix - General Subject';
   var $display_comment = TRUE;
   var $data;
   
   function _antrain_Plan_matrix($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */

   }
   
/*    function antrainplanproposed() {
    $id_propose = GetUserId();
	  $is_propose = '1';
      $dt_propose = getSQLDate(); 
	  
	   $sql = "UPDATE antrain session SET id_proposed = '$id_propose', is_proposed ='$is_propose', date_proposed = '$dt_propose' WHERE psid = '$psid'";
     $db->query($sql);
   
   }
    */
   
   function antrainplanmatrix() {
      $psid = $_SESSION["pms_psid"];
      global $xocp_vars;
      
      
      $db=&Database::getInstance();
      $ajax = new _antrain_class_ObjectiveAjax("orgjx");
      
	  $user_id = getUserID();
      
      $current_year = 2013;
	  
	 /*    $proposedbutton = "<form method= 'post' action='index.php?XP_antrainplan'><input type='hidden' name='id_proposed' value='$id_propose' ><input type='hidden' name='is_proposed' value='$is_propose' ><input type='hidden' name='date_proposed' value='$dt_propose' ><input type='submit' value='Proposed' ></form>";      */
	
      $sql = "SELECT p.year, p.is_proposed, p.date_proposed,  pk.org_nm, pk.org_class_id FROM antrain_session p LEFT JOIN hris_orgs pk ON p.org_id = pk.org_id WHERE psid = ('$psid') ORDER BY year DESC " ;
	  
	  /* "SELECT year,session_nm FROM antrain_session WHERE psid  = '$psid'"; */
      $result = $db->query($sql);
      list($year,$is_proposed,$date_proposed,$org_nm)=$db->fetchRow($result);
   $proposedbutton = "<form id='proposedbutton' method= 'post'><input type='hidden' name='id_proposed' value='$id_propose' ><input type='hidden' name='is_proposed' value='$is_propose' ><input type='hidden' name='date_proposed' value='$dt_propose' ><input type='button' onclick='propose();' value='Propose'  id='propbut'></form>";
	  $returnbtn = "<span style='float:right; margin:5px;'><input onclick='return_session(\"$psid\",this,event);' type='button' value='Return' id='returnbtn'/></span>";  
	  $remindbtn = "<span style='float:right; margin:5px;'><input onclick='remind_btn(\"$psid\",this,event);' type='button' value='Remind' id='remindbtn'/></span>"; 
	  $remindbtnomsm = "<span style='float:right; margin:5px;'><input onclick='remind_btn_omsm(\"$psid\",this,event);' type='button' value='Remind' id='remindbtnomsm'/></span>"; 	  
	  $addbtn = "<span style='float:right; margin:5px;'><input onclick='new_session();' type='button' value='Add'/></span>";
	  
	 if($id_proposed = 1)
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
	 if ($job_id == 68 || $job_id == 999){
	 
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
	 
	 }else {
	 
		$proposedbutton = '';
		$returnbtn = '';
		$remindbtnomsm = '';
		
	 }
	
	if ( getUserId == 1493 )
	{
		$remindbtnomsm = '';
	}
	else
	{
		$remindbtnomsm = $remindbtnomsm;
	}
 	
		$sqltbls = "SELECT id_antrain_peergroup,antrain_peergroup_nm FROM antrain_peer_group";
		$resulttbls = $db->query($sqltbls);
		$numrowname = $db->getRowsNum($resulttbls);
		$tbname = "";
		$i = 0;
		 while(list($id_ant,$antrain_peergroup_nm)=$db->fetchRow($resulttbls)){
		 $tbname .= "<td style='border-right: 1px solid #CCC; text-align:center;font-size:8px;'>$antrain_peergroup_nm</td>";
		 if(++$i == $numrowname )
		 {
			$tbname .= "";
		 }
		 else
		 {
		 $tbname .= "";
		 }
		 
		 }
	
	$tooltip = "<div id='all_ap_tooltip' style='display:none;'>";
      

      $ret .= "<div>";
      
     /*  $ret .= $return_notes;
      $ret .= $report_return_notes;
       */
      $ret .= "<table style='margin-top:5px;table-layout:fixed;' class='xxlist'>";
      
      $ret .= "<div style='padding:5px;font-size:20px;color: #666666; text-align:center;'>ANNUAL TRAINING / SEMINAR PLAN MATRIX</div>"
			 . "<div style='padding:5px;font-size:15px;color: #666666; text-align:center;'>(General Subject /Year $year )</div>"
			 . "<div style='padding:2px;font-size:12px;color: #666666; text-align:left;'>Division/ Section:  $org_nm . $addbtn $remindbtnomsm $remindbtn $returnbtn<div id='id_return'></div></div>"
			 
			. "<colgroup><col width='17%'/><col width='12%'/><col width='5%'/></colgroup>"
			
			. "<thead><tr><td rowspan='3' style='border-right: 1px solid #CCC; text-align: center;'>Subject</td>"
			. "<td rowspan='3' style='border-right: 1px solid #CCC; text-align: center;'>Date</td>"
			. "<td style='border-right: 1px solid #CCC; text-align:center; ' colspan='$numrowname'>Name</td>"
			. "<tr>$tbname</tr></thead>";
   
      $ret .= "<tbody>";
      
	$sql = "SELECT p.id, p.number, p.subject, p.objectives, p.schedule_start, p.schedule_end,p.cost, p.id_pr_group1, p.id_pr_group2, p.remark,p.inst, ps.antrain_peergroup_nm , ps2.antrain_peergroup_nm AS peer_group_nm2 FROM antrain_plan_general p LEFT JOIN antrain_session pk ON p.id_antrain_session = pk.psid LEFT JOIN antrain_peer_group ps ON p.id_pr_group1 = ps.id_antrain_peergroup LEFT JOIN antrain_peer_group ps2 ON p.id_pr_group2 = ps2.id_antrain_peergroup WHERE p.id_antrain_session = '$psid' AND p.is_deleted = 'F' ";
	$result = $db->query($sql);
	  if($db->getRowsNum($result)>0) {
	while(list($id,$number,$subject,$objectives,$schedule_start,$schedule_end,$cost,$id_pr_group1,$id_pr_group2, $remark,$inst, $peer_group_nm,$peer_group_nm2)=$db->fetchRow($result)){
	 //return array($id,$number,$subject,$objectives,$schedule_start,$schedule_end,$cost,$id_pr_group1,$id_pr_group2,$remark,$inst);	
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
				
		  $schedule_start = date('d M Y', strtotime($schedule_start));
		  $schedule_end = date('d M Y', strtotime($schedule_end));
		  $numberloop = $numberloop + 1;
		  
				  if($id_pr_group2 <> 0 && $id_pr_group2 <> NULL ) {
				  $participant = htmlentities(stripslashes($peer_group_nm))." - ".htmlentities(stripslashes($peer_group_nm2));
				  }else{
				   $participant = htmlentities(stripslashes($peer_group_nm));
				  }
				 
			//edit by andi
			  $ret .= "<tr><td id='tdclass_${id}' style='margin: 0px; padding:0px;' colspan=10 >"
				  . "<table style='width:100%'><colgroup><col width='17%'/><col width='12%'/><col width='5%'/><col width='12%'/><col width='12%'/><col width='12%'/><col width='12%'/><col width='7%'/><col width='7%'/><col width='10%'/></colgroup><tbody><tr>"
				  // . "<td id='td_num_loop_${id}' style='text-align:center;width:50px'>$numberloop</td>"
				  // . "<td><span id='sp_${id}' class='xlnk' onclick='edit_session(\"$id\",this,event); '>".$participant ."</span></td>"
               	  // . "<td id='td_number_${id}' style='text-align: center;'>$number</td>"
				  . "<td id='td_subject_${id}' style='text-align: left;'>$subject</td>"
				  // . "<td id='td_objectives_${id}' style='text-align: left;'>$objectives</td>"
				  . "<td id='td_schedule_${id}' style='text-align: center; font-size:10px;'>$schedule_start - $schedule_end</td>"
				  . "<td id='td_cost_${id}' style='text-align: right; padding-right: 10px;'>$. $cost</td>"
				  . "<td id='td_ins_int_${id}' style='text-align: center;'>$ins_int</td>"
				  . "<td id='td_ins_ext_${id}' style='text-align: center;'>$ins_ext</td>"
				  . "<td id='td_remark_${id}' style='text-align: center;'>$remark </td>"
                 . "</tr></tbody></table>"
                  . "</td></tr>";
				  
				   
			}
	
		 }
	
	$sqlbdgt = "SELECT budget"
				.   " FROM antrain_session"
				.	" WHERE psid='$psid'";
	$resultbdgt = $db->query($sqlbdgt);
	list($budget)=$db->fetchRow($resultbdgt);
	
	$sql = "SELECT SUM(cost) FROM antrain_plan_general WHERE id_antrain_session = '$psid' AND is_deleted = 'F'";
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
	  $ret .= "<tr><td colspan=6 style='border-right: 1px solid #CCC'><strong>Budget in year</strong></td><td style='border-right: 1px solid #CCC; text-align: right; padding-right: 12px;'>$. $budget</td><td style='border-right: 1px solid #CCC' ></td><td style='border-right: 1px solid #CCC' ></td><td style='border-right: 1px solid #CCC' ></td></tr>";
      
	  $ret .= "</tbody>";
      $ret .= "</table>";
      $ret .= "</div>";
     
	/*  $sql = "SELECT id_proposed, date_proposed"
           . " FROM antrain_session"
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
   
     $sql = "SELECT p.user_id,ps.person_nm,pk.date_proposed FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id LEFT JOIN antrain_session pk ON p.user_id = pk.id_proposed WHERE pk.psid = '$psid'";  
     $result = $db->query($sql);
     list($user_id_proposed,$proposedby,$date_proposed)=$db->fetchRow($result);
     $date_proposed = date('d/M/Y', strtotime($date_proposed));
	 
	 $sql = "SELECT p.user_id,ps.person_nm,pk.date_approved FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id LEFT JOIN antrain_session pk ON p.user_id = pk.id_approved  WHERE pk.psid = '$psid'";  
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
	 
	  if($date_approved == '01/Jan/1970')
	  {
		$date_approved = '-';
	 }
	 else
	 {
	 $date_approved = $date_approved;
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
         
                
                . "</tr>";
				
		   $ret .= "<tr><td id='tdclass_${psid}'>"
                . "<table><colgroup><col width='60'/><col/></colgroup><tbody><tr>"
                . "<td width=70 style='text-align: left;' ><span id='sp_${psid}' class='xlnk' onclick='edit_session(\"$psid\",this,event); '>".htmlentities(stripslashes($year))."</span></td>"
				. "<td id='td_org_nm_${psid}' width=125 style='text-align: left;'>$org_nm</td>"
				. "<td id='td_remark_${psid}' width=200 style='text-align: left;'>$remark</td>"
                . "</tr></tbody></table>"
                . "</td></tr>"
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
         td.setAttribute('colspan','10');
         td.setAttribute('style','padding:0px;');
		 wdv = td.appendChild(wdv);
         wdv.appendChild(progress_span());
         wdv.td = td;
         orgjx_app_editSession(id,function(_data) {
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
			var participant = null;
			if (data[8] != 0 && data[8] != null) {
			participant = data[11] + '-' +data[12];
			}else{
			participant = data[11] ;
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
			$('sp_'+data[0]).innerHTML = participant;
			$('td_number_'+data[0]).innerHTML = data[1];
			$('td_subject_'+data[0]).innerHTML = data[2];
			$('td_objectives_'+data[0]).innerHTML = data[3];
			$('td_schedule_'+data[0]).innerHTML = data[4] + ' - ' +data[5];
			$('td_cost_'+data[0]).innerHTML = '$.'+data[6];
			$('td_ins_int_'+data[0]).innerHTML = internal;		
			$('td_ins_ext_'+data[0]).innerHTML = external;
			$('td_remark_'+data[0]).innerHTML = data[9];
            $('inp_remark').focus();
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
            $ret = $this->antrainplanmatrix($self_employee_id);
            break;
         default:
            $ret = $this->antrainplanmatrix($self_employee_id);
            break;
      }
    $sqljob = "SELECT j.job_id FROM hris_employee_job j LEFT JOIN hris_users u ON (u.person_id = j.employee_id) WHERE u.user_id = ".getUserid()."";
	$resultjob = $db->query($sqljob);
	 list($job_id)=$db->fetchRow($resultjob);
	//echo 'getuserid = '.getuserid(). '<br> job_id = '.$job_id; exit();
	 	 if ($job_id == 90 || $job_id == 68 || $job_id == 101 || $job_id == 999){
      return $antrainsel.$ret;
	  }
	  else
	  {
	  return 'you have no access';
	  }
   }
}

} // PMS_MYACTIONPLAN_DEFINED
?>