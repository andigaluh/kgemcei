<?php
//--------------------------------------------------------------------//
// Filename : modules/sms/smsobj.php                     //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2013-12-17                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('SMS_SMSOBJREPORTPOST_DEFINED') ) {
   define('SMS_SMSOBJREPORTPOST_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/antrain/modconsts.php");
//include_once(XOCP_DOC_ROOT."/modules/sms/class/ajax_smssession.php");

class _sms_SMSObjreportpost extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _SMS_SMSOBJREPORT_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = 'SMS Action Plan Report';
   var $display_comment = TRUE;
   var $data;
   
   function __construct($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function listSession() {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/sms/class/ajax_smsobj.php");
         $ajax = new _sms_class_SMSObjAjax("psjx");
   
    $idper = $_GET['idper']; 
    $id = $_GET['id']; 
    $idobj = $_GET['idobj']; 
	$sqlobj = "SELECT id,id_themes, id_ref_perspektive,objective_code,id_objective_owner,id_objective_owner_2,objective_title,objective_description FROM sms_objective WHERE id_ref_perspektive = $idper AND id_themes = $id AND id = $idobj";
	$resultobj = $db->query($sqlobj);
	list($idobj,$id_themes,$id_ref_perspektive,$objective_code,$id_objective_owner,$id_objective_owner_2,$obj_title,$obj_desc)=$db->fetchRow($resultobj);
	
	
/* 	$sqlperid = "SELECT person_id FROM hris_users WHERE user_id = ".getUserid()."";
	$resultperid = $db->query($sqlperid);
	list($person_id)=$db->fetchRow($resultperid);
   
   $exportbtn = "<a style='background-color: #6cc04f; text-decoration: none!important; padding: 4px!important; border-radius: 3px;  color: #fff; cursor: pointer; font-family: 'Lucida Grande',Verdana,Arial,Helvetica,Tahoma,'Gill Sans',Futura; font-size: 11px;' href='./modules/sms/export_excel_jam.php?&psid=$psid&person_id=$person_id&user_id=$user_id'>Export to excel</a>";
   
       $ret = "";
     
	  $sqlpers = "SELECT a.job_nm,e.org_nm,e.parent_id FROM hris_jobs a" 
           . " LEFT JOIN hris_employee_job b ON b.job_id = a.job_id"
           . " LEFT JOIN hris_orgs e ON a.org_id = e.org_id"
           . " WHERE b.employee_id = $person_id AND e.org_class_id = '4'";
           
      $resultpers = $db->query($sqlpers);
      list($job_nm,$org_nm,$parent_id)=$db->fetchRow($resultpers);

      $sqlorg = "SELECT org_nm FROM hris_orgs WHERE org_id = $parent_id";
      $resultorg = $db->query($sqlorg);
      list($org_nmx)=$db->fetchRow($resultorg); */

	   
      $ret .= "<div>";
      
      $ret .= "<table style='width:100%;margin-top:10px;' class='xxlist'>"
            . "<colgroup>"
            . "<col width='5%'/>"
            . "<col width='25%'/>"
            . "<col width='10%'/>"
            . "<col width='5%'/>"
            . "<col width='10%'/>"
            . "<col width='1%'/>"
            . "<col width='7%'/>"
            . "<col width='1%'/>"
            . "<col width='7%'/>"
            . "<col width='1%'/>"
            . "<col width='7%'/>"
            . "<col width='7%'/>"
            . "<col width='2%'/>"
            . "<col width='7%'/>"
            . "<col width='7%'/>"

			
/* 			. "<col width='30'/>"
            . "<col width='120'/>"
            . "<col width='100'/>"
            . "<col width='180'/>"
            . "<col width='100'/>" */

            . "</colgroup>";
      
      $ret .= "<thead>"
        
            . "<tr>"
           
             . "</tr>"
			
           
            . "<tr>"
			   . "<td style='border-right:1px solid #bbb;text-align:center;' rowspan=2>Ref#</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;' rowspan=2 colspan=4>Objective - Action Plan</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;' colspan=6>Target</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;' rowspan=2>Result</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;' rowspan=2>%</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;' rowspan=2>Remark</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;' rowspan=2>Attachment</td>"
            . "</tr>"
			. "<tr>"
               . "<td style='border-right:1px solid #bbb;text-align:center;' colspan=2>>High</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;' colspan=2>>Medium</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;' colspan=2>>Low</td>"
			. "</tr>"


            . "</thead>";
      
      $ret .= "<tbody>";

      $ret .= "<tr>"
					."<td style='border:0px solid #bbb;text-align:center; padding: 1px!important; '>$objective_code</td>"
					
					."<td style='border:0px solid #bbb;text-align:left; padding: 1px!important; ' colspan=4>$obj_title</td>"
					
					."<td class='grn' style='border-right:1px solid #bbb; text-align:right; padding: 1px!important; '></td>"
					
					."<td style='border-right:1px solid #bbb; text-align:right; padding: 1px!important; '></td>"
					
					."<td class='ylw' style='border-right:1px solid #bbb; text-align:right; padding: 1px!important; '></td>"
					
					."<td style='border-right:1px solid #bbb; text-align:right; padding: 1px!important; '></td>"
					
					."<td class='red' style='border-right:1px solid #bbb;text-align:right; padding: 1px!important; '></td>"
					
					."<td style='border-right:1px solid #bbb; text-align:right; padding: 1px!important; '></td>"
					
					."<td style='border-right:1px solid #bbb; border-bottom: 0px; text-align:right; padding: 1px!important; '></td>"
					
					."<td style='border-right:1px solid #bbb; border-bottom: 0px; text-align:right; padding: 1px!important; '></td>"
					
					."<td style='border-right:1px solid #bbb; border-bottom: 0px; text-align:right; padding: 1px!important; '></td>"
					
					."<td style='border-right:1px solid #bbb; border-bottom: 0px; text-align:right; padding: 1px!important; '></td>"
					
				."</tr>";
				
						
			$sqlmeasure = "SELECT id,measure_code,measure_description,result,percentage,remark,attach FROM sms_objective_measure WHERE id_objective = $idobj";
			$resultmeasure = $db->query($sqlmeasure);
			$numrow = $db->getRowsNum($resultmeasure);
		  while(list($id_measure,$measure_code,$measure_description,$result,$percentage,$remark,$attach)=$db->fetchRow($resultmeasure)){			
		  
				$sqlintent = "SELECT intent_description FROM sms_objective_intent WHERE id_objective = $idobj AND intent_code = '$measure_code'";
				$resultintent = $db->query($sqlintent);
				$numintent = $db->getRowsNum($resultintent);
				list($intent_description)=$db->fetchRow($resultintent);
				
				$sqlfrequency = "SELECT frequency_description FROM sms_objective_frequency WHERE id_objective = $idobj AND frequency_code  = '$measure_code'";
				$resultfrequency = $db->query($sqlfrequency);
				$numfrec =$db->getRowsNum($resultfrequency);
				list($frequency_description)=$db->fetchRow($resultfrequency);
				
				$sqltarget = "SELECT target_high,target_medium,target_low FROM sms_objective_target WHERE id_objective = $idobj AND target_code = '$measure_code'";
				$resulttarget = $db->query($sqltarget);
				$numtarget = $db->getRowsNum($resulttarget);
				list($target_high,$target_medium,$target_low)=$db->fetchRow($resulttarget);
		  
		  
		  if($result==null){$result='Empty';}else{$result=$result;}
		  if($percentage==null){$percentage='0';}else{$percentage=$percentage;}
		  if($remark==null){$remark='Empty';}else{$remark=$remark;}
		  if($attach==null){$attachment="<a href='$attach'>Empty</a>";}else{$attachment="<a href='".XOCP_SERVER_SUBDIR."/modules/sms/upload/$attach'>Download</a>";}
			
		  $attachbutton = "<form action='http://".$_SERVER['HTTP_HOST']."".XOCP_SERVER_SUBDIR."/uploadfile.php?id=$id_measure' method='post'
							enctype='multipart/form-data'><input type='button' value='Upload File' onclick='uplfile($id_measure);'/>
							</form>";
							
		   $ret .= "<tr>"
		   
					."<td style='border:0px solid #bbb;text-align:right;'></td>"

					. "<td style='border:0px solid #bbb;text-align:left;' colspan=2> &nbsp; &nbsp; - $measure_code $measure_description </td>"
					
					."<td style='border:0px solid #bbb;text-align:left;'>$intent_description </td>"
					
					."<td style='border:0px solid #bbb;text-align:left;'>$frequency_description </td>"
					
					."<td class='grn' style='border-right:1px solid #bbb;text-align:right;'></td>"
					
					."<td style='border-right:1px solid #bbb;text-align:left;'>$target_high</td>"
					
					."<td class='ylw' style='border-right:1px solid #bbb;text-align:right;'></td>"
					
					."<td style='border-right:1px solid #bbb;text-align:left;'>$target_medium</td>"
					
					."<td class='red' style='border-right:1px solid #bbb;text-align:right;'></td>"
					
					."<td style='border-right:1px solid #bbb;text-align:left;'>$target_low</td>"
					
					."<td style='border-right:1px solid #bbb;text-align:left;'><span class='xlnk' onclick='edit_target_text(\"$idobj\",\"$id_measure\",41,this,event);' style='text-decoration:none;' '>$result</span></td>"
					
					."<td style='border-right:1px solid #bbb;text-align:left;'><span class='xlnk' onclick='edit_target_text(\"$idobj\",\"$id_measure\",42,this,event);' style='text-decoration:none;' '>$percentage</span></td>"
					
					."<td style='border-right:1px solid #bbb;text-align:left;'><span class='xlnk' onclick='edit_target_text(\"$idobj\",\"$id_measure\",43,this,event);' style='text-decoration:none;' '>$remark</span></td>"
					
					."<td style='border-right:1px solid #bbb;text-align:left;'>$attachbutton ";
					
				$sqlatt = "SELECT id,attach FROM sms_measure_attach WHERE id_measure = '$id_measure' ";
				$resultatt = $db->query($sqlatt);
				$num=0;
				while(list($idatt,$attach)=$db->fetchRow($resultatt)){
					$num++;
					$attachment ="$num. <a href='".XOCP_SERVER_SUBDIR."/modules/sms/upload/$attach'> Download</a><br><span class='xlnk' onclick='delete_measure(\"10\",\"$idatt\",0,this,event);' style='text-decoration:none;' '>(Delete)</span><br/>";
					$ret .= "$attachment";
				}			

					$ret .= "</td>"
					
			."</tr>";		
				
			
		}
				

	  
		
	$ret .= "<tbody>";

      $ret .= "</tbody>";
      $ret .= "</table>";
      $ret .= "</div>";
      $ret .= "<br>";
     
	//PROPOSE & APPROVAL
	

	$sql = "SELECT id,propose_id,propose_date FROM sms_ap_approval WHERE id_obj = '$idobj' AND id_per = '$idper'"; 
     $result = $db->query($sql);
     list($user_id_proposed,$proposedby,$date_proposed)=$db->fetchRow($result);
     $date_proposed = date('d/M/Y', strtotime($date_proposed));
	 
	$sql = "SELECT id,approve_id,approve_date FROM sms_ap_approval WHERE id_obj = '$idobj' AND id_per = '$idper'"; 
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
	
	  
	  $dt_now =getSQLDate();
	
	  $proposedbutton = "<form id='proposedbutton' method= 'post'><input type='hidden' name='id_obj' value='$idobj' ><input type='hidden' name='id_per' value='$idper' ><input type='hidden' name='propose_id' value='$person_id' ><input type='hidden' name='propose_date' value='$dt_now' ><input type='button' onclick='propose();' value='Propose'  id='propbut'></form>";
	  
	 $ret .= "<div style='text-align:right;padding:10px;margin-top:10px;margin-bottom:100px;'>"
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
                // . " &nbsp &nbsp &nbsp * Instructor: Int = Internal, Ext = External <br/>"
				//. " &nbsp &nbsp &nbsp ** If total Estimation Cost more than budget in year, please fill a reason in remarks column. <br/>"
                . "</td>"
         
                
                . "</tr>"
  
                . "</tbody>"
                . "</table>"               

			   . "<table align='right' style='border-top:2px solid #777;border-left:2px solid #777;border-spacing:0px;'>"
                . "<colgroup>"
                . "<col width='200'/>"
                . "<col width='200'/>"
                . "</colgroup>"
                . "<tbody>"
                . "<tr>"
                . "<td style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;'>"
                . "Proposed by,"
                . "</td>"
                . "<td style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;'>"
                . "Approved by,"
                . "</td>"
                . "</tr>"
				
				 . "<tr>"
                . "<td height=60 style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
				. "<span style='margin:5px;'>$proposedbutton</span>"
                . "</td>"
                . "<td style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
               	 . "<span style='margin:5px;'>$approvedbutton</span>"
                . "</td>"
                
                . "</tr>"
				
                . "<tr>"
              . "<td style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
                . "$proposedby"
                . "</td>"
                 . "<td id=approved_${psid} style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
				. "$approvedby"
                . "</td>"
                
                
                . "</tr>"
                . "<tr>"
                . "<td style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;padding:5px;' id='submited_by_button'>"
                . "Submitted on:<br/> $date_proposed"
                . "</td>"
                 . "<td id=dateapproved_${psid} style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;padding:5px;' id='submited_by_button'>"
                 . "Approved on:<br/> $date_approved"
				. "</td>"
                
                . "</tr>"
                . "</tbody>"
                . "</table>"
                . "</div>";
      
      
      
	
	
	 $ret .= "<style type='text/css'>

					table {
						border-bottom:0;
						border-left:0;
					}
					td, th {
						border-top:0;
						border-right:0;
					}
				.grn {background-color: #00b050!important;}
				.ylw {background-color: #ffff00!important;}
				.red {background-color: #ff0000!important;}
				.light {background-color: #f4f4f4!important; font-size: 10px!important; text-align: center;}
				.tg-table-light { border-collapse: collapse; border-spacing: 0; }
				.tg-table-light td, .tg-table-light th { background-color: #fff; border: 1px #bbb solid; color: #333; font-family: Lucida Grande,Arial,Lucida Grande,Gill Sans,Futura,Verdana,Helvetica; font-size: 12px; padding: 10px; }
				.tg-table-light .tg-even   { background-color: #eee; }
				.tg-table-light th  { background-color: #ddd; color: #333; font-size: 110%; font-weight: bold; }
				.tg-bf { font-weight: bold; } .tg-it { font-style: italic; }
				.tg-left { text-align: left; } .tg-right { text-align: right; } .tg-center { text-align: center; }
				.red-text { color: red!important; font-size: 11px!important;}
				</style>";
    


		$ret .= "	</table>";

      return $ret.$ajax->getJs()."<script src='".XOCP_SERVER_SUBDIR."/include/calendar.js' type='text/javascript'></script><script type='text/javascript'><!--
	// Fachmy punya
		
	function uplfile(id) {
		psjx_app_setSessions(id,function(_data) {
							var data = recjsarray(_data);
							//alert(idap);
							});		 
		 pdl = _dce('div');
         pdl.setAttribute('id','pdl');
         pdl.bg = _dce('div');
         pdl.bg.setAttribute('style','height:150%;width:150%;position:fixed;top:0px;left:-10px;background-color:#000000;opacity:0.5;z-index:10000');
         pdl.bg = document.body.appendChild(pdl.bg);
			
		pdl.setAttribute('style','text-align:center;padding-top:15px;padding-bottom:15px;width:400px;position:fixed;top:50%;left:50%;background-color:white;border:1px solid #555555;margin-left:-200px;margin-top:-100px;opacity:1;z-index:10001;');
         pdl.innerHTML = '<div style=\"background-color:#5666b3;padding:4px;margin:5px;color:#ffffff;width:366px;margin-left:15px;\">Upload File</div>'
                       + '<iframe src=\"".XOCP_SERVER_SUBDIR."/modules/sms/uploadfile.php?id=\"style=\"width:370px;border:0px solid black;overflow:visible;\"></iframe>';
         pdl = document.body.appendChild(pdl);
         pdl.cancelUpload = function() {
            _destroy(pdl);
            _destroy(pdl.bg);
         };
		 
		 pdl.finishUpload = function() {
								     //window.setTimeout(function(){alert('Upload Finished')},5000)
									 window.setTimeout(function(){location.reload()},8000)
							
							};
		 
		 pdl.cancelUpload = function() {
								_destroy(pdl);
								_destroy(pdl.bg);
						           window.setTimeout(function(){location.reload()},1000)
							
							};
		 
      }
		
		
		  function propose() {
       // alert('test');
		var ret = parseForm('proposedbutton');
         //$('progress').appendChild(progress_span('&nbsp;...saving&nbsp;&nbsp;'));
		psjx_app_appropose(ret,function(_data) {
		//alert('test');
            var data = recjsarray(_data);
			//alert(data[5]);
			 $('proposed_'+data[0]).innerHTML = data[4];
			 $('dateproposed_'+data[0]).innerHTML = 'Submitted on: ' +'<br>' + data[3];
			 $('propbut').setAttribute('style','display:none;');
			//setTimeout(\"$('progress').innerHTML = '';\",1000);
         });
      }
      
	  
	  // Made by Denimaru

	      function new_ksf(id,idobj) {
	         if(wdv) {
	            cancel_edit();
	         }
	         var tre = $('trempty');
	         var tr = _dce('div');
	         tr.setAttribute('style','padding-top:30px;')
	         var td = tr.appendChild(_dce('td'));
	         tr = tre.parentNode.insertBefore(tr,tre);
	         wdv = _dce('div');
	         wdv.td = td;

	         psjx_app_newKsf(id,idobj,function(_data) {
	            var data = recjsarray(_data);
	            wdv.td.setAttribute('id','trclass_'+data[0]);
	            wdv.td.innerHTML = data[1];
	            wdv.td = null;
	            wdv = null;
	            //alert(data[0]);
	            edit_ksf(data[0],data[2],null,null);
	         });
	      }
	      
	      var wdv = null;
	      function edit_ksf(id,idobj,d,e) {
	         if(wdv) {
	            if(wdv.id == id) {
	               cancel_edit();
	               return;
	            } else {
	               cancel_edit();
	            }
	         }

	         wdv = _dce('tr');

	         wdv.id = id;

	         var tr = $('trclass_'+id);
	         wdv = tr.parentNode.insertBefore(wdv,tr);

	         wdv.appendChild(progress_span());
	         wdv.tr = tr;

	         psjx_app_editKsf(id,idobj,function(_data) {
	            wdv.innerHTML = _data;
	            $('inp_ksf_code').focus();
	         });
	      }
	      
	      function cancel_edit() {
	         wdv.tr.style.backgroundColor = '';
	         if(wdv.id=='new') {
	            _destroy(wdv.tr.parentNode);
	         }
	         wdv.id = null;
	         _destroy(wdv);
	         wdv = null;
	      }
	      
	      function delete_ksf() {
	         var tr = wdv.parentNode;
	         tr.style.backgroundColor = '#ffcccc';
	         wdv.oldHTML = wdv.innerHTML;
	         wdv.innerHTML = '<td colspan=\"12\">Are you sure you want to delete this ksf?<br/><br/>'
	                       + '<input type=\"button\" value=\""._CANCEL."\" onclick=\"cancel_delete();\"/>&nbsp;&nbsp;'
	                       + '<input type=\"button\" value=\""._DELETE."\" onclick=\"do_delete();\"/></td>';
	      }
	      
	      function cancel_delete() {
	         var tr = wdv.parentNode;
	         tr.style.backgroundColor = '';
	         wdv.innerHTML = wdv.oldHTML;
	      }
	      
	      function do_delete() {
	         psjx_app_deleteKsf(wdv.id,null);
	         //var tr = wdv.parentNode.parentNode;
	         //_destroy(tr);
	         //wdv.id = null;
	         //wdv = null;
	         window.setTimeout(function(){location.reload()},1500);
	      }
	      
	      function save_ksf(idobj) {
	         var ret = parseForm('frm');
	         $('progress').appendChild(progress_span('&nbsp;...saving&nbsp;&nbsp;'));
	         psjx_app_saveKsf(wdv.id,idobj,ret,function(_data) {
	            var data = recjsarray(_data);
	            //$('sp_'+data[0]).innerHTML = data[1];
	            //$('td_code_'+data[0]).innerHTML = data[2];
	            //$('inp_ksf_title').focus();
	            setTimeout(\"$('progress').innerHTML = '';\",1000);
	            //window.setTimeout(function(){location.reload()},2000);
	         });
	      }

	      ///// PIC
	      var editpicedit = null;
	      var editpicobjbox = null;
	      function edit_picobj(id,ksf_id,d,e) {
	         editpicedit = _dce('div');
	         editpicedit.setAttribute('id','editpicedit');
	         editpicedit = document.body.appendChild(editpicedit);
	         editpicedit.sub = editpicedit.appendChild(_dce('div'));
	         editpicedit.sub.setAttribute('id','innereditpicedit');
	         editpicobjbox = new GlassBox();
	         editpicobjbox.init('editpicedit','300px','170px','hidden','default',false,false);
	         editpicobjbox.lbo(false,0.3);
	         editpicobjbox.appear();
	         
	         psjx_app_editPicObj(id,ksf_id,function(_data) {
	         	$('innereditpicedit').innerHTML = _data;
            	//_dsa($('sms_action_plan_text'));
	         });  
	      }

	      function delete_picobj(id,d,e) {
	         psjx_app_deletePicObj(id,function(_data) {
	            location.reload(true);
	         });
	      }
	      
	      function save_picobj(id,ksf_id,d,e) {
	         var ret = _parseForm('frmpicobj');
	         psjx_app_savePicObj(ret,function(_data) {
	            location.reload(true);
	            //var data = recjsarray(_data);
	            //alert(data[0]);
	         });
	      }
    // update 05/03/2014

		  function do_delete_obj(idobj,d,e) {
	        psjx_app_deleteObj(idobj,function(_data) {
	      		window.setTimeout(function(){window.location = 'http://".$_SERVER['SERVER_NAME']."/".XOCP_SERVER_SUBDIR."/index.php?XP_smstheme&menuid=85&mpid=83';},1500)
	      	
	      	});
	      }
	      
	      function cancel_delete_obj() {
	         $('innerobjedit').innerHTML = $('innerobjedit').oldHTML;
	      }
	      
	      function delete_obj(idobj,d,e) {
		     objedit = _dce('div');
	         objedit.setAttribute('id','objedit');
	         objedit = document.body.appendChild(objedit);
	         objedit.sub = objedit.appendChild(_dce('div'));
	         objedit.sub.setAttribute('id','innerobjedit');
	         objbox = new GlassBox();
	         objbox.init('objedit','300px','210px','hidden','default',false,false);
	         objbox.lbo(false,0.3);
	         objbox.appear();

	        psjx_app_deleteObjOpt(idobj,function(_data) {
	            $('innerobjedit').innerHTML = _data;
         	});
	      }

	  // end of update 05/03/2014
		  
		  
      // End of Made by Denimaru
  	  
	  //EDIT 
	  
	    function save_target_text(idobj,kpid,no) {
         var val = trim($('inp_target_text').value);
         if(dvedittargettext) {
            dvedittargettext.d.innerHTML = val;
         }
         psjx_app_saveObjTargetText(val,idobj,kpid,no,null);
      }
      
      function kp_target_text(d,e) {
         var k = getkeyc(e);
         if(d.chgt) {
            d.chgt.reset();
            d.chgt = null;
         }
         var val = d.value;
         if(k==13) {
            dvedittargettext.d.innerHTML = val;
            save_target_text(dvedittargettext.idobj,dvedittargettext.kpid,dvedittargettext.no);
         } else if (k==27) {
            _destroy(dvedittargettext);
            dvedittargettext.d = null;
            dvedittargettext = null;
         } else {
            d.chgt = new ctimer('save_target_text(\"'+dvedittargettext.idobj+'\",\"'+dvedittargettext.kpid+'\",\"'+dvedittargettext.no+'\");',300);
            d.chgt.start();
         }
      }
      
      function close_target_text() {
         document.body.onclick = null;
         _destroy(dvedittargettext);
         dvedittargettext.d = null;
         dvedittargettext = null;
		 window.setTimeout(function(){location.reload()},2000)
         return;

      }
      
      var dvedittargettext = null;
      function edit_target_text(idobj,kpid,no,d,e) {
         document.body.onclick = null;
         _destroy(dvedittargettext);
         if(dvedittargettext&&d==dvedittargettext.d) {
            dvedittargettext.d = null;
            dvedittargettext = null;
            return;
         }
         d.dv = _dce('div');
         d.dv.setAttribute('style','position:absolute;padding:5px;-moz-border-radius:5px;border:1px solid #777;background-color:#ffffcc;left:0px;-moz-box-shadow:1px 1px 3px #000;');
         var text = d.innerHTML;
         if(text=='"._EMPTY."') {
            text = '';
         }
         d.dv.innerHTML = '<div style=\"text-align:left;padding:2px;\">Edit :<br/>'
                        + '<textarea onkeyup=\"kp_target_text(this,event);\" id=\"inp_target_text\" style=\"-moz-border-radius:3px;width:350px;height:100px;\">'+text+'</textarea>'
                        + '<div style=\"text-align:right;\"><input class=\"sbtn\" type=\"button\" value=\"Close\" onclick=\"close_target_text();\"/></div>'
                        + '</div>';
         d.dv = d.parentNode.appendChild(d.dv);
         d.dv.style.top = parseInt(oY(d.parentNode)+d.parentNode.offsetHeight+5)+'px';
         var x = oX(d);
         if(x>650) {
            d.dv.style.left = parseInt(oX(d.parentNode)-(d.dv.offsetWidth)+(d.parentNode.offsetWidth))+'px';
         } else {
            d.dv.style.left = parseInt(oX(d)-(d.dv.offsetWidth/2)+(d.offsetWidth/2))+'px';
         }
         d.dv.arrow = _dce('img');
         d.dv.arrow.setAttribute('style','position:absolute;left:0px;');
         d.dv.arrow.src = '".XOCP_SERVER_SUBDIR."/images/topmiddle.png';
         d.dv.arrow = d.dv.appendChild(d.dv.arrow);
         d.dv.arrow.style.top = '-12px';
         if(x>650) {
            d.dv.arrow.style.left = parseInt(d.dv.offsetWidth-(d.parentNode.offsetWidth/2)-7)+'px';
         } else {
            d.dv.arrow.style.left = parseInt(d.dv.offsetWidth-(d.dv.offsetWidth/2)-7)+'px';
         }
         $('inp_target_text').focus();
         dvedittargettext = d.dv;
         dvedittargettext.d = d;
         dvedittargettext.idobj = idobj;
         dvedittargettext.kpid = kpid;
         dvedittargettext.no = no;
         //setTimeout('document.body.onclick = function() { document.body.onclick = null; _destroy(dvedittargettext); };',100);
      }

	  //ADD MEASURE
	  var persedit = null;
      var persbox = null;
      function add_measure(idobj,id_inp,d,e) {
         persedit = _dce('div');
         persedit.setAttribute('id','persedit');
         persedit = document.body.appendChild(persedit);
         persedit.sub = persedit.appendChild(_dce('div'));
         persedit.sub.setAttribute('id','innerpersedit');
         persbox = new GlassBox();
         persbox.init('persedit','700px','260px','hidden','default',false,false);
         persbox.lbo(false,0.3);
         persbox.appear();
         
         psjx_app_addMeasure(idobj,id_inp,function(_data) {
            $('innerpersedit').innerHTML = _data;
            $('sms_perspective_code').focus();
         });
         
      }
      
      function save_measure(idobj,id_inp) {
         var ret = _parseForm('frmobj');
         psjx_app_saveMeasure(idobj,id_inp,ret,function(_data) {
            var data = recjsarray(_data);
			//alert(data[0]+','+data[1]+','+data[2]);
            if(data[0]==1) {
               var tr = _dce('tr');
               
               tr.setAttribute('id','trsms_'+data[1]);
               
               tr.td0 = tr.appendChild(_dce('td'));
               tr.td0.innerHTML = data[2];
               tr.td0.setAttribute('id','pc_'+data[1]);
               tr.td0.setAttribute('style','text-align:center;font-size:1.2em;font-weight:bold;vertical-align:middle;border-right:1px solid #bbb;');
               
               tr.td1 = tr.appendChild(_dce('td'));
               tr.td1.setAttribute('style','vertical-align:middle;border-right:1px solid #bbb;');
               tr.td1.sp = tr.td1.appendChild(_dce('span'));
               tr.td1.sp.setAttribute('class','xlnk');
               tr.td1.sp.setAttribute('onclick','edit_objowner(\"'+data[1]+'\",this,event);');
               tr.td1.sp.setAttribute('id','pm_'+data[1]);
               tr.td1.sp.innerHTML = data[3];
               tr.td1.dv = tr.td1.appendChild(_dce('div'));
               tr.td1.dv.setAttribute('style','font-style:italic;color:#888;');
               tr.td1.dv.innerHTML = data[5];
               tr.td1.dv.setAttribute('id','pdesc_'+data[1]);
               
               tr.td2 = tr.appendChild(_dce('td'));
               tr.td2.innerHTML = data[4]+' %';
               tr.td2.setAttribute('id','pw_'+data[1]);
               tr.td2.setAttribute('style','text-align:center;vertical-align:middle');
               $('tbdpers').appendChild(tr);
            } else {
               $('pm_'+data[1]).innerHTML = data[3];
               $('pc_'+data[1]).innerHTML = data[2];
               $('pdesc_'+data[1]).innerHTML = data[5];
               $('pw_'+data[1]).innerHTML = data[4]+' %';
            }
            $('ttlw').innerHTML = data[6]+' %';
         });
		
         persbox.fade();
		 window.setTimeout(function(){location.reload()},2000)
      }

	//DELETE MEASURE
	
	var persedit = null;
      var persbox = null;
      function delete_measure(id_del,id_measure,d,e) {
         persedit = _dce('div');
         persedit.setAttribute('id','persedit');
         persedit = document.body.appendChild(persedit);
         persedit.sub = persedit.appendChild(_dce('div'));
         persedit.sub.setAttribute('id','innerpersedit');
         persbox = new GlassBox();
         persbox.init('persedit','400px','165px','hidden','default',false,false);
         persbox.lbo(false,0.3);
         persbox.appear();
         
         psjx_app_deleteMeasure(id_del,id_measure,function(_data) {
            $('innerpersedit').innerHTML = _data;
            $('sms_perspective_code').focus();
         });
         
      }
      
      function confirmdelete_measure(id_del,id_measure) {
         var ret = _parseForm('frmobj');
         psjx_app_confirmdeleteMeasure(id_del,id_measure,ret,function(_data) {
            var data = recjsarray(_data);
			//alert(data[0]+','+data[1]);
            if(data[0]==1) {
               var tr = _dce('tr');
               
               tr.setAttribute('id','trsms_'+data[1]);
               
               tr.td0 = tr.appendChild(_dce('td'));
               tr.td0.innerHTML = data[2];
               tr.td0.setAttribute('id','pc_'+data[1]);
               tr.td0.setAttribute('style','text-align:center;font-size:1.2em;font-weight:bold;vertical-align:middle;border-right:1px solid #bbb;');
               
               tr.td1 = tr.appendChild(_dce('td'));
               tr.td1.setAttribute('style','vertical-align:middle;border-right:1px solid #bbb;');
               tr.td1.sp = tr.td1.appendChild(_dce('span'));
               tr.td1.sp.setAttribute('class','xlnk');
               tr.td1.sp.setAttribute('onclick','edit_objowner(\"'+data[1]+'\",this,event);');
               tr.td1.sp.setAttribute('id','pm_'+data[1]);
               tr.td1.sp.innerHTML = data[3];
               tr.td1.dv = tr.td1.appendChild(_dce('div'));
               tr.td1.dv.setAttribute('style','font-style:italic;color:#888;');
               tr.td1.dv.innerHTML = data[5];
               tr.td1.dv.setAttribute('id','pdesc_'+data[1]);
               
               tr.td2 = tr.appendChild(_dce('td'));
               tr.td2.innerHTML = data[4]+' %';
               tr.td2.setAttribute('id','pw_'+data[1]);
               tr.td2.setAttribute('style','text-align:center;vertical-align:middle');
               $('tbdpers').appendChild(tr);
            } else {
               $('pm_'+data[1]).innerHTML = data[3];
               $('pc_'+data[1]).innerHTML = data[2];
               $('pdesc_'+data[1]).innerHTML = data[5];
               $('pw_'+data[1]).innerHTML = data[4]+' %';
            }
            $('ttlw').innerHTML = data[6]+' %';
         });
		
         persbox.fade();
		 window.setTimeout(function(){location.reload()},2000)
      }
      
	  //EDIT OBJ OWNER
	  var persedit = null;
      var persbox = null;
      function edit_objowner(objid,d,e) {
         persedit = _dce('div');
         persedit.setAttribute('id','persedit');
         persedit = document.body.appendChild(persedit);
         persedit.sub = persedit.appendChild(_dce('div'));
         persedit.sub.setAttribute('id','innerpersedit');
         persbox = new GlassBox();
         persbox.init('persedit','700px','210px','hidden','default',false,false);
         persbox.lbo(false,0.3);
         persbox.appear();
         
         psjx_app_addObjOwner(objid,function(_data) {
            $('innerpersedit').innerHTML = _data;
            $('sms_perspective_code').focus();
         });
         
      }
      
      function save_objowner(idobj) {
         var ret = _parseForm('frmobj');
         psjx_app_saveObjectiveOwner(idobj,ret,function(_data) {
            var data = recjsarray(_data);
			//alert(data[0]+','+data[1]+','+data[2]);
            if(data[0]==1) {
               var tr = _dce('tr');
               
               tr.setAttribute('id','trsms_'+data[1]);
               
               tr.td0 = tr.appendChild(_dce('td'));
               tr.td0.innerHTML = data[2];
               tr.td0.setAttribute('id','pc_'+data[1]);
               tr.td0.setAttribute('style','text-align:center;font-size:1.2em;font-weight:bold;vertical-align:middle;border-right:1px solid #bbb;');
               
               tr.td1 = tr.appendChild(_dce('td'));
               tr.td1.setAttribute('style','vertical-align:middle;border-right:1px solid #bbb;');
               tr.td1.sp = tr.td1.appendChild(_dce('span'));
               tr.td1.sp.setAttribute('class','xlnk');
               tr.td1.sp.setAttribute('onclick','edit_objowner(\"'+data[1]+'\",this,event);');
               tr.td1.sp.setAttribute('id','pm_'+data[1]);
               tr.td1.sp.innerHTML = data[3];
               tr.td1.dv = tr.td1.appendChild(_dce('div'));
               tr.td1.dv.setAttribute('style','font-style:italic;color:#888;');
               tr.td1.dv.innerHTML = data[5];
               tr.td1.dv.setAttribute('id','pdesc_'+data[1]);
               
               tr.td2 = tr.appendChild(_dce('td'));
               tr.td2.innerHTML = data[4]+' %';
               tr.td2.setAttribute('id','pw_'+data[1]);
               tr.td2.setAttribute('style','text-align:center;vertical-align:middle');
               $('tbdpers').appendChild(tr);
            } else {
               $('pm_'+data[1]).innerHTML = data[3];
               $('pc_'+data[1]).innerHTML = data[2];
               $('pdesc_'+data[1]).innerHTML = data[5];
               $('pw_'+data[1]).innerHTML = data[4]+' %';
            }
            $('ttlw').innerHTML = data[6]+' %';
         });
		
         persbox.fade();
		 window.setTimeout(function(){location.reload()},2000)
      }
	  
	  
	  
      // --></script>";
   }
   
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $this->listSession();
            break;
         default:
            $ret = $this->listSession();
            break;
		}
	
		
	$sqlusid = "SELECT person_id FROM hris_users WHERE user_id = ".getuserid()."";
	$resultuserid = $db->query($sqlusid);
	list($persid)=$db->fetchRow($resultuserid);
	
	$idper = $_GET['idper']; 
    $id = $_GET['id']; 
	$idobj = $_GET['idobj']; 
	$sqlobj = "SELECT id,id_themes, id_ref_perspektive,objective_code,id_objective_owner,id_objective_owner_2,objective_title,objective_description FROM sms_objective WHERE id_ref_perspektive = $idper AND id_themes = $id AND id = $idobj";
	$resultobj = $db->query($sqlobj);
	list($idobj,$id_themes,$id_ref_perspektive,$objective_code,$id_objective_owner,$id_objective_owner_2,$obj_title,$obj_desc)=$db->fetchRow($resultobj);
	
	$sqlowner = "SELECT person_id  FROM hris_persons WHERE person_id = $id_objective_owner";
	$resultowner =   $db->query($sqlowner);
	list($id_owner)=$db->fetchRow($resultowner);
	
	$sqlowner2 = "SELECT person_id  FROM hris_persons WHERE person_id = $id_objective_owner_2";
	$resultowner2 =   $db->query($sqlowner2);
	list($id_owner2)=$db->fetchRow($resultowner2);
 
	if($persid == $id_owner OR $persid == $id_owner2)
		{
			$ret = $ret;
		}
	else 
		{
			$sqlcore = "SELECT person_id FROM sms_core_team WHERE person_id ='$persid' ";
			$resultcore = $db->query($sqlcore);
			  if($db->getRowsNum($resultcore)>0) 
				{
					$ret = $ret;
				}
			else
				{
					$ret = "You have no access";
				}
		}

			return $ret.$form;
		
 }
}

} 
?>