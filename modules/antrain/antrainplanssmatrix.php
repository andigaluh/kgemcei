<?php
//--------------------------------------------------------------------//
// Filename : modules/antrain/antrainplan.php                         //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2010-09-22                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('ANTRAIN_PLAN_SS_MATRIX_DEFINED') ) {
   define('ANTRAIN_PLAN_SS_MATRIX_DEFINED', TRUE);

//include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
//include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
include_once(XOCP_DOC_ROOT."/config.php");
global $xocpConfig;

include_once(XOCP_DOC_ROOT."/modules/antrain/class/ajax_specific_subject.php");
require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
include_once(XOCP_DOC_ROOT."/modules/antrain/class/selectantrainss.php");

class _antrain_Plan_ss_matrix extends XocpBlock {
   var $catchvar = _ANTRAIN_CATCH_VAR;
   var $blockID = _ANTRAIN_PLAN_SS_MATRIX_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = 'Annual Training Plan Matrix - Specific Subject ';
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
      $ajax = new _antrain_class_SpecificSubjectAjax("orgjx");
      
	  $user_id = getUserID();
      
      $current_year = 2013;
	  
	 /*    $proposedbutton = "<form method= 'post' action='index.php?XP_antrainplan'><input type='hidden' name='id_proposed' value='$id_propose' ><input type='hidden' name='is_proposed' value='$is_propose' ><input type='hidden' name='date_proposed' value='$dt_propose' ><input type='submit' value='Proposed' ></form>";      */
	
      $sql = "SELECT p.year, p.is_proposed,p.is_proposeddiv,p.is_approved,p.date_proposed,  pk.org_nm, pk.org_class_id FROM antrain_sessionss p LEFT JOIN hris_orgs pk ON p.org_id = pk.org_id WHERE psid = ('$psid') ORDER BY year DESC " ;
	  
	  /* "SELECT year,session_nm FROM antrain_sessionss WHERE psid  = '$psid'"; */
      $result = $db->query($sql);
      list($year,$is_proposed,$is_proposeddiv,$is_approved,$date_proposed,$org_nm)=$db->fetchRow($result);
	  
	  $sqlsecnm = "SELECT pk.org_nm, pk.org_class_id FROM antrain_sessionss p LEFT JOIN hris_orgs pk ON p.org_id_sec = pk.org_id WHERE psid = ('$psid') ORDER BY year DESC " ;
	  
	  /* "SELECT year,session_nm FROM antrain_sessionss WHERE psid  = '$psid'"; */
      $resultsecnm = $db->query($sqlsecnm);
      list($org_nm_sec)=$db->fetchRow($resultsecnm);
	  
   $proposedbutton = "<form id='proposedbutton' method= 'post'><input type='hidden' name='id_proposed' value='$id_propose' ><input type='hidden' name='is_proposed' value='$is_propose' ><input type='hidden' name='date_proposed' value='$dt_propose' ><input type='button' onclick='propose();' value='Propose'  id='propbut'></form>";

   $proposeddivbutton = "<form id='proposeddivbutton' method= 'post'><input type='hidden' name='id_proposeddiv' value='$id_proposeddiv' ><input type='hidden' name='is_proposeddiv' value='$is_proposeddiv' ><input type='hidden' name='date_proposeddiv' value='$dt_proposeddiv' ><input type='button' onclick='proposeddiv();' value='Propose'  id='propbutdiv'></form>";
   
   $approvedbutton = "<form id='approvedbutton' method= 'post'><input type='hidden' name='id_approved' value='$id_approve' ><input type='hidden' name='is_approved' value='$is_approve' ><input type='hidden' name='date_approved' value='$dt_approve' ><input type='button' onclick='approve();' value='Approve'  id='apprbut'></form>";

	
   
	  
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
	
	$sqljob = "SELECT j.job_id, a.job_class_id FROM hris_employee_job j LEFT JOIN hris_users u ON ( u.person_id = j.employee_id ) LEFT JOIN hris_jobs a ON ( j.job_id = a.job_id ) WHERE u.user_id = ".getUserid()."";
	$resultjob = $db->query($sqljob);
	 list($job_id,$job_class_id)=$db->fetchRow($resultjob);
	//echo 'getuserid = '.getuserid(). '<br> job_id = '.$job_id; exit();
	
//PROPOSE
	if ( $job_id == 999|| $job_id == 141|| $job_id == 68|| $job_id == 138|| $job_id == 132|| $job_id == 151|| $job_id == 37|| $job_id == 169|| $job_id == 107|| $job_id == 111|| $job_id == 74|| $job_id == 85|| $job_id == 49|| $job_id == 60|| $job_id == 91|| $job_id == 183|| $job_id == 25|| $job_id == 211|| $job_id == 220 ) {
	  
		 if ($is_proposed == '1')
		{
			$proposedbutton = '';
			$remindbtn = '';
			$returnbtn = '';
			$addbtn = '';
		}	
		else
		{
		$proposedbutton = $proposedbutton;  
		$returnbtn = $returnbtn;
		$remindbtnomsm = $remindbtnomsm;
		$addbtn = $addbtn;
		} 
	 
	 }
	 else
	 {
		$proposedbutton = '';
		$returnbtn = '';
		$remindbtnomsm = '';
	 }
	 
	 //PROPOSEDIV
	  if ( $job_id == 999|| $job_id == 6|| $job_id == 36|| $job_id == 48|| $job_id == 104|| $job_id == 126|| $job_id == 147|| $job_id == 168 )
	  
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
	  if ( $job_id == 999|| $job_id == 47|| $job_id == 146|| $job_id == 167|| $job_id == 129 )
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
      
      $ret .= "<div style='padding:5px;font-size:20px;color: #666666; text-align:center;'>TRAINING / SEMINAR PLAN  MATRIX (in $year)</div>"
			 . "<div style='padding:2px;font-size:12px;color: #666666; text-align:left;'>Division/ Section:  $org_nm / $org_nm_sec  <div id='id_return'></div></div>";
			 
		$sqltb = "SELECT DISTINCT p.name FROM antrain_plan_specific p LEFT JOIN antrain_sessionss pk ON p.id_antrain_session = pk.psid LEFT JOIN hris_job_class ps ON p.id_job_class1 = ps.job_class_id LEFT JOIN hris_job_class ps2 ON p.id_job_class2 = ps2.job_class_id WHERE p.id_antrain_session = '$psid' AND p.is_deleted = 'F' GROUP BY p.name";
	$resulttb = $db->query($sqltb);
/* 	while(list($id,$name,$subject,$objectives,$schedule_start,$schedule_end,$cost,$id_job_class1,$id_job_class2, $remark,$inst, $hris_job_class,$hris_job_class2)=$db->fetchRow($resulttb)){	 
	$tbname = "<tr><td style='border-right: 1px solid #CCC; text-align:center;'>$name</td>";
	  } */
	$tbname = "";
		$numrowname = $db->getRowsNum($resulttb);
		
		$i = 0;
		 while(list($name)=$db->fetchRow($resulttb)){
		 $tbname .= "<td style='border-right: 1px solid #CCC; text-align:center;'>$name</td>";
		 if(++$i == $numrowname )
		 {
			$tbname .= "";
		 }
		 else
		 {
		 $tbname .= "";
		 }
		 
		 }
 
	  
	   $ret .=  "<colgroup><col width='20%'/><col width='16%'/></colgroup>"
			
			. "<thead><tr><td rowspan='3' style='border-right: 1px solid #CCC; text-align: center;'>Subject</td>"
			. "<td rowspan='3' style='border-right: 1px solid #CCC; text-align: center;'>Date</td>"
			. "<td style='border-right: 1px solid #CCC; text-align:center; ' colspan='$numrowname'>Name</td>"
			. "<tr>$tbname</tr></thead>";
      $ret .= "<tbody>";
      
	$sql = "SELECT DISTINCT p.id, p.name, p.subject, p.objectives, p.schedule_start, p.schedule_end, p.cost, p.id_job_class1, p.id_job_class2, p.remark, p.inst, ps.job_class_nm, ps2.job_class_nm AS job_class_nm2
FROM antrain_plan_specific p LEFT JOIN antrain_sessionss pk ON p.id_antrain_session = pk.psid LEFT JOIN hris_job_class ps ON p.id_job_class1 = ps.job_class_id LEFT JOIN hris_job_class ps2 ON p.id_job_class2 = ps2.job_class_id WHERE p.id_antrain_session = '$psid' AND p.is_deleted = 'F' GROUP BY p.schedule_start";
	$result = $db->query($sql);
	  if($db->getRowsNum($result)>0) {
	while(list($id,$name,$subject,$objectives,$schedule_start,$schedule_end,$cost,$id_job_class1,$id_job_class2, $remark,$inst, $hris_job_class,$hris_job_class2)=$db->fetchRow($result)){
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
				
		  $schedule_start = date('d M Y', strtotime($schedule_start));
		  $schedule_end = date('d M Y', strtotime($schedule_end));
		  $numberloop = $numberloop + 1;
		  if($id_job_class2 <> 0 && $id_job_class2 <> NULL ) {
			$participantname = htmlentities(stripslashes($hris_job_class))." - ".htmlentities(stripslashes($hris_job_class2));
			  }
			  else
			  {
			$participantname = htmlentities(stripslashes($hris_job_class));
			  }
			$editlink = "class='xlnk' onclick='edit_text(\"$id\",this,event)";

				if($is_proposed == 1)
				{
				$editlink = '';
				}
				else
				{
				$editlink = $editlink;
				}
				
				$sqltb2 = "SELECT  p.name, p.subject, p.schedule_start,p.status_cancel FROM antrain_plan_specific p LEFT JOIN antrain_sessionss pk ON p.id_antrain_session = pk.psid LEFT JOIN hris_job_class ps ON p.id_job_class1 = ps.job_class_id LEFT JOIN hris_job_class ps2 ON p.id_job_class2 = ps2.job_class_id WHERE p.id_antrain_session = '$psid' AND p.is_deleted = 'F' GROUP BY p.name";
				$resulttb2= $db->query($sqltb2);


		$tbdown = "";
		$numrowdown = $db->getRowsNum($resulttb2);
		
		$nma = 0;
		 while(list($named,$subjectd,$scdstartd,$statcan)=$db->fetchRow($resulttb2)){
		  $scdstartd = date('d M Y', strtotime($scdstartd));
		  if($scdstartd == $schedule_start AND $statcan == 'AN' XOR $statcan == 'T' AND $subject == $subjectd AND $named == $name )
		  {
		   $matrixdot = '&Omicron;';
		   $clr = '';
		  }
		  
		   elseif($scdstartd == $schedule_start AND $statcan == 'F' AND $subject == $subjectd AND $named == $name  )
		  {
		   $matrixdot = '&#9679;';
		   $clr = '';
		  }
		  
		  else
		  {
			$matrixdot = '&nbsp;';
			$clr = 'color:white;';
		  }
		  
		 $tbdown .= "<td id='matrixx' style='text-align: center; $clr border-left: 1px solid #CCC;'>$matrixdot</td>";
		 if(++$nma == $numrowdown)
		 {
			$tbdown .= "";
		 }
		 else
		 {
		 $tbdown .= "";
		 }
		 
		 }
		 
		 $colspanplus = $numrowdown + 2;
			//edit by fahmi
			  $ret 	.= "<tbody><tr>"
						. "<td id='td_subject_${id}' style='text-align: left;'>$subject</td>"
						. "<td id='td_schedule_${id}' style='text-align: center; font-size:10px;'>$schedule_start - $schedule_end</td>"
						. "$tbdown"
						. "</tr></tbody>";
				  		   
			}
	
		 }
	
      
	  $ret .= "</tbody>";
      $ret .= "</table>";
      $ret .= "</div>";
     
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
                . " &nbsp &nbsp &nbsp *<br/>"
                . " &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp - Cancelled or Altered to next fiscal year = &Omicron;<br/>"
                . " &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp - Altered or ongoing = &#9679;<br/>"
				. "</td>"
         
                
                . "</tr>";
   
		 $sqlnote = "SELECT psid,note_text FROM antrain_sessionss WHERE psid = $psid ";
		 $resultnote = $db->query($sqlnote);
		 list($psid,$note_text)=$db->fetchRow($resultnote);
		 $btnnote = "<span id='sp_${psid}' class='xlnk' onclick='edit_text(\"$psid\",this,event); '>Edit note :</span>";
		
		if ($job_class_id == 12)
			{
				$btnnote = $btnnote ;
			}
		else
		{
				$btnnote  = '';
		}
		  $form .= "<tr><td id='tdclass_${psid}'>"
                . "<table><colgroup><col width='60'/><col/></colgroup><tbody><tr>"
                . "<td width=20 style='text-align: left;' >$btnnote</td>"
				. "<td id='td_note_text_${psid}' width=200 style='text-align: left; font-size: 10px;'> - $note_text</td>"
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
            edit_text(data[0],null,null);
         });
      }
	  
		       var wdv = null;
      function edit_text(id,d,e) {
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
         td.setAttribute('colspan','2');
         td.setAttribute('style','padding:0px;');
		 wdv = td.appendChild(wdv);
         wdv.appendChild(progress_span());
         wdv.td = td;
         orgjx_app_editText(id,function(_data) {
            wdv.innerHTML = _data;
            $('td_note_text_'+data[0]).innerHTML = data[1];
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
      
      function save_text() {
         var ret = parseForm('frm');
         $('progress').appendChild(progress_span('&nbsp;...saving&nbsp;&nbsp;'));
		orgjx_app_saveText (wdv.id,ret,function(_data) {
            var data = recjsarray(_data);
			var participantname = null;
			
		
			$('sp_'+data[0]).innerHTML = 'Edit note:';
			$('td_note_text_'+data[0]).innerHTML = '- ' + data[1];
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
	 	
      return $antrainsel.$ret;
	
	  
   }
}

} // PMS_MYACTIONPLAN_DEFINED
?>