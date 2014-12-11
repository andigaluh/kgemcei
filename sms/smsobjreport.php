<?php
//--------------------------------------------------------------------//
// Filename : modules/sms/smsobj.php                     //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2013-12-17                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('SMS_SMSOBJREPORT_DEFINED') ) {
   define('SMS_SMSOBJREPORT_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/antrain/modconsts.php");
//include_once(XOCP_DOC_ROOT."/modules/sms/class/ajax_smssession.php");

class _sms_SMSObjreport extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _SMS_SMSOBJREPORT_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = 'SMS Objectives Report';
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

	 
	  
   
	 // Add by fahmikun
	$ret .= "<br><br>	<div style='margin: 0px 0px 10px 0px; float: right;'><a href='?XP_smsobjreportpost&idper=$idper&id=$id&idobj=$idobj' 11px;'='' font-size:='' sans',futura;='' grande',verdana,arial,helvetica,tahoma,'gill='' lucida='' style='background-color: #6cc04f; text-decoration: none!important; padding: 4px!important; border-radius: 3px;  color: #fff; cursor: pointer; font-family: '>Submit SRM Monthly</a></div>";
	// End of Add by fahmikun
	  
      $ret .= "<div>";
      
      $ret .= "<table style='width:100%;margin-top:10px;' class='xxlist'>"
            . "<colgroup>"
            . "<col width='30'/>"
            . "<col width='120'/>"
            . "<col width='100'/>"
            . "<col width='180'/>"
            . "<col width='100'/>"
            . "<col width='20'/>"
            . "<col width='110'/>"
            . "<col width='20'/>"
            . "<col width='110'/>"
            . "<col width='20'/>"
            . "<col width='110'/>"

            //. "<col width='125'/>"
            . "</colgroup>";
      
      $ret .= "<thead>"
        
            . "<tr>"
           
             . "</tr>"
			
           
            . "<tr>"
			   . "<td style='border-right:1px solid #bbb;text-align:center;'  rowspan=2>Ref#</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;' rowspan=2 colspan=4>Objective</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;' colspan=6>Target</td>"
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
					
				."</tr>";
				
						
			$sqlmeasure = "SELECT id,measure_code,measure_description FROM sms_objective_measure WHERE id_objective = $idobj";
			$resultmeasure = $db->query($sqlmeasure);
			$numrow = $db->getRowsNum($resultmeasure);
		  while(list($id_measure,$measure_code,$measure_description)=$db->fetchRow($resultmeasure)){			
		  
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
		  
		  
		   $ret .= "<tr>"
		   
					."<td style='border:0px solid #bbb;text-align:right;'></td>"

					. "<td style='border:0px solid #bbb;text-align:left;' colspan=2> &nbsp; &nbsp; - $measure_code $measure_description</td>"
					
					."<td style='border:0px solid #bbb;text-align:left;'>$intent_description </td>"
					
					."<td style='border:0px solid #bbb;text-align:left;'>$frequency_description </td>"
					
					."<td class='grn' style='border-right:1px solid #bbb;text-align:right;'></td>"
					
					."<td style='border-right:1px solid #bbb;text-align:left;'>$target_high</td>"
					
					."<td class='ylw' style='border-right:1px solid #bbb;text-align:right;'></td>"
					
					."<td style='border-right:1px solid #bbb;text-align:left;'>$target_medium</td>"
					
					."<td class='red' style='border-right:1px solid #bbb;text-align:right;'></td>"
					
					."<td style='border-right:1px solid #bbb;text-align:left;'>$target_low</td>"
					
				."</tr>";		
				
				}
				

	  
		
	$ret .= "<tbody>";

      $ret .= "</tbody>";
      $ret .= "</table>";
      $ret .= "</div>";
      $ret .= "<br>";
     

	
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
    
	$sqlowner = "SELECT person_nm  FROM hris_persons WHERE person_id = $id_objective_owner";
	$resultowner =   $db->query($sqlowner);
	list($name_owner)=$db->fetchRow($resultowner);
	
	$sqlowner2 = "SELECT person_nm  FROM hris_persons WHERE person_id = $id_objective_owner_2";
	$resultowner2 =   $db->query($sqlowner2);
	list($name_owner2)=$db->fetchRow($resultowner2);
	
	if($objective_code==''){$objective_code = 'Empty';}else{$objective_code=$objective_code;}
	if($obj_title==''){$obj_title = 'Empty';}else{$obj_title=$obj_title;}
	if($obj_desc==''){$obj_desc = 'Empty';}else{$obj_desc=$obj_desc;}


	
	if($name_owner==''){$name_owner='Add';}else{$name_owner=$name_owner;}
	
	
	$ret .= "<table class='tg-table-light'>
					 <col width='7%' />
					 <col width='5%' />
					 <col width='3%' />
					 <col width='2.5%' />
					 <col width='22%' />
					 <col width='5%' />
					 <col width='2.5%' />
					 <col width='22%' />
					 <col width='5%' />
					 <col width='2.5%' />
					 <col width='22%' />
					 <col width='5%' />
					 <col width='5%' />
  
					<tr>
						<th >REF#</th>
						<th colspan='2'>OBJECTIVE</th>
						<th colspan='7'>OBJECTIVE DESCRIPTION / DEFINITION</th>
						<th colspan='2'>OBJECTIVE OWNER</th>
				  </tr>
				  <tr >
					<td class='tg-center' rowspan='2'><span class='xlnk' onclick='edit_target_text(\"$idobj\",\"0\",0,this,event);' style='text-decoration:none;'>$objective_code</span></td>
					<td colspan='2' rowspan='2'>
						<span class='xlnk' onclick='edit_target_text(\"$idobj\",\"0\",1,this,event);' style='text-decoration:none;' '>$obj_title</span>
					</td>
					<td colspan='7' rowspan='2'>
						<span class='xlnk' onclick='edit_target_text(\"$idobj\",\"0\",2,this,event);' style='text-decoration:none;' '>$obj_desc</span>
					</td>
					<td class='tg-center' colspan='2' rowspan='2'>
						<span class='xlnk' onclick='edit_objowner(\"$idobj\",this,event);' style='text-decoration:none;' '>
						$name_owner <br/> $name_owner2 </span>
					</td>
				  </tr>
				  <tr>
				  </tr>";
				  
	  $sqlmeasure = "SELECT id,measure_code,measure_description FROM sms_objective_measure WHERE id_objective = $idobj";
	  $resultmeasure = $db->query($sqlmeasure);
	  $numrow = $db->getRowsNum($resultmeasure);
	  $measure = "<td class='tg-even' colspan='2' rowspan='$numrow'>
								MEASURE 
								<br/>
								<input onclick='add_measure(\"$idobj\",1,this,event);' type='button' value='Add'/>
							</td>";
	   if($db->getRowsNum($resultmeasure)>0) {
	  while(list($id_measure,$measure_code,$measure_description)=$db->fetchRow($resultmeasure)){
	  $ret .= "<tr >
					$measure
					<td class='light'>
						<span class='xlnk' onclick='edit_target_text(\"$idobj\",\"$id_measure\",3,this,event);' style='text-decoration:none;' '>$measure_code</span>
					</td>
					<td class ='red-text' colspan='9'>
							<span class='xlnk red-text' onclick='edit_target_text(\"$idobj\",\"$id_measure\",4,this,event);' style='text-decoration:none;' '>$measure_description</span>
							<span class='xlnk' style='float:right; text-decoration:none;' onclick='delete_measure(\"1\",\"$id_measure\",0,this,event);'>X</span>
					</td>
				  </tr>";
				  	$measure = '';
			}
		
		}
		else
		{
		$measure = "<td class='tg-even' colspan='2' rowspan='1'>
								MEASURE 
								<br/>
								<input onclick='add_measure(\"$idobj\",1,this,event);' type='button' value='Add'/>
								</td>";
		$ret .= "<tr >
					$measure
					<td class='light'><span class='xlnk' onclick='edit_target_text(\"$idobj\",\"$id_measure\",3,this,event);' style='text-decoration:none;' '>$measure_code</span></td>
					<td class ='red-text' colspan='9'><span class='xlnk red-text' onclick='edit_target_text(\"$idobj\",\"$id_measure\",4,this,event);' style='text-decoration:none;' '>$measure_description</span>
						<span class='xlnk' style='float:right; text-decoration:none;' onclick='delete_measure(\"1\",\"$id_measure\",0,this,event);'>X</span>
					</td>
					
				  </tr>";
				   $measure = '';
		}
		
		
	 $sqlintent = "SELECT id,intent_code,intent_description FROM sms_objective_intent WHERE id_objective = $idobj";
	 $resultintent = $db->query($sqlintent);
	$numintent = $db->getRowsNum($resultintent);
	$intent = "<td class='tg-even' colspan='2' rowspan='$numintent'>MEASURE INTENT
						<br/>
						<input onclick='add_measure(\"$idobj\",2,this,event);' type='button' value='Add'/>
						</td>";
	   if($db->getRowsNum($resultintent)>0) {
	  while(list($id_measure,$intent_code,$intent_description)=$db->fetchRow($resultintent)){
	  
	  if($intent_code==''){ $intent_code= 'Empty';}
	  else{$intent_code = $intent_code;}
	  
	  if($intent_description==''){ $intent_description= 'Empty';}
	  else{$intent_description = $intent_description;}
	  $ret .= "<tr >
					$intent
					<td class='light'><span class='xlnk' onclick='edit_target_text(\"$idobj\",\"$id_measure\",5,this,event);' style='text-decoration:none;' '>$intent_code</span></td>
					<td class ='red-text' colspan='9'><span class='xlnk red-text' onclick='edit_target_text(\"$idobj\",\"$id_measure\",6,this,event);' style='text-decoration:none;' '>$intent_description</span>
					<span class='xlnk' style='float:right; text-decoration:none;' onclick='delete_measure(\"2\",\"$id_measure\",0,this,event);'>X</span>
					</td>
				
				  </tr>";
				  	$intent = '';
			}
		}
			else
		{
			$intent = "<td class='tg-even' colspan='2' rowspan='1'>MEASURE INTENT
						<br/>
						<input onclick='add_measure(\"$idobj\",2,this,event);' type='button' value='Add'/>
						</td>";
			$ret .= "<tr >
					$intent
					<td class='light'><span class='xlnk' onclick='edit_target_text(\"$idobj\",\"$id_measure\",5,this,event);' style='text-decoration:none;' '>$intent_code</span></td>
					<td class ='red-text' colspan='9'><span class='xlnk red-text' onclick='edit_target_text(\"$idobj\",\"$id_measure\",6,this,event);' style='text-decoration:none;' '>$intent_description</span>
						<span class='xlnk' style='float:right; text-decoration:none;' onclick='delete_measure(\"2\",\"$id_measure\",0,this,event);'>X</span>
					</td>
				  </tr>";
				  	$intent = '';
		}
		
	 $sqlfrequency = "SELECT id,frequency_code,frequency_description FROM sms_objective_frequency WHERE id_objective = $idobj";
	 $resultfrequency = $db->query($sqlfrequency);
	 $numfrec =$db->getRowsNum($resultfrequency);
	 $frequency = "<td class='tg-even' colspan='2' rowspan='$numfrec'>MEASURE FREQUENCY
							<br/>
							<input onclick='add_measure(\"$idobj\",3,this,event);' type='button' value='Add'/>
						 </td>";
	   if($db->getRowsNum($resultfrequency)>0) {
	  while(list($idfreq,$frequency_code,$frequency_description)=$db->fetchRow($resultfrequency)){
	  $ret .= "<tr >
					$frequency
					<td class='light'><span class='xlnk' onclick='edit_target_text(\"$idobj\",\"$idfreq\",7,this,event);' style='text-decoration:none;' '>$frequency_code</span></td>
					<td class ='red-text' colspan='9'>
						<span class='xlnk red-text' onclick='edit_target_text(\"$idobj\",\"$idfreq\",8,this,event);' style='text-decoration:none;' '>$frequency_description</span>
						<span class='xlnk' style='float:right; text-decoration:none;' onclick='delete_measure(\"3\",\"$idfreq\",0,this,event);'>X</span>
						</td>
				  </tr>";
				  	$frequency = '';
			}
		}
		else
		{
			 $frequency = "<td class='tg-even' colspan='2' rowspan='1'>MEASURE FREQUENCY
							<br/>
							<input onclick='add_measure(\"$idobj\",3,this,event);' type='button' value='Add'/>
						</td>";
			   $ret .= "<tr >
					$frequency
					<td class='light'>$frequency_code</td>
					<td class ='red-text' colspan='9'>$frequency_description 	<span class='xlnk' style='float:right; text-decoration:none;' onclick='delete_measure(\"3\",\"$id_measure\",0,this,event);'>X</span></td>
				  </tr>";
				  	$frequency = '';
		}
		
	 $sqltarget = "SELECT id,target_code,target_high,target_medium,target_low FROM sms_objective_target WHERE id_objective = $idobj";
	 $resulttarget = $db->query($sqltarget);
	 $numtarget = $db->getRowsNum($resulttarget);
	 $target = "<td class='tg-even' colspan='2' rowspan='$numtarget'>TARGET
						<br/>
						<input onclick='add_measure(\"$idobj\",4,this,event);' type='button' value='Add'/>
					</td>";
	   if($db->getRowsNum($resulttarget)>0) {
	  while(list($idtgt,$target_code,$target_high,$target_medium,$target_low)=$db->fetchRow($resulttarget)){
	  $ret .= "<tr >
						$target
						<td class='light'><span class='xlnk' onclick='edit_target_text(\"$idobj\",\"$idtgt\",12,this,event);' style='text-decoration:none;' '>$target_code</span></td>
						<td class='grn'></td>
						<td class ='red-text' colspan='2'><span class='xlnk red-text' onclick='edit_target_text(\"$idobj\",\"$idtgt\",13,this,event);' style='text-decoration:none;' '>$target_high</span></td>
						<td class='ylw'></td>
						<td class ='red-text' colspan='2'><span class='xlnk red-text' onclick='edit_target_text(\"$idobj\",\"$idtgt\",14,this,event);' style='text-decoration:none;' '>$target_medium</span></td>
						<td class='red'></td>
						<td class ='red-text' colspan='2'><span class='xlnk red-text' onclick='edit_target_text(\"$idobj\",\"$idtgt\",15,this,event);' style='text-decoration:none;' '>$target_low</span>
						<span class='xlnk' style='float:right; text-decoration:none;' onclick='delete_measure(\"4\",\"$idtgt\",0,this,event);'>X</span>
						</td>
				  </tr>";
				  	$target = '';
			}
		}
		else
		{
			$target = "<td class='tg-even' colspan='2' rowspan='1'>TARGET
								<br/>
								<input onclick='add_measure(\"$idobj\",4,this,event);' type='button' value='Add'/>
							</td>";
			$ret .= "<tr >
						$target
						<td class='light'>$target_code</td>
						<td class='grn'></td>
						<td  class ='red-text' colspan='2'>$target_high</td>
						<td class='ylw'></td>
						<td  class ='red-text' colspan='2'>$target_medium</td>
						<td class='red'></td>
						<td  class ='red-text' colspan='2'>$target_low <span class='xlnk' style='float:right; text-decoration:none;' onclick='delete_measure(\"4\",\"$idtgt\",0,this,event);'>X</span></td>
				  </tr>";
				  	$target = '';
		}
	
	
	 $sqlactionplan = "SELECT id,actionplan_description FROM sms_objective_actionplan WHERE id_objective = $idobj";
	 $resultactionplan = $db->query($sqlactionplan);
	 $actionplan = "<td class='tg-even'  colspan='2' rowspan='2'>ACTION PLAN</td>";
	  if($db->getRowsNum($resultactionplan)>0) {
	  list($idap,$actionplan_description)=$db->fetchRow($resultactionplan);
	  $actionplan_description = "<span class='xlnk' onclick='edit_target_text(\"$idobj\",\"$idap\",16,this,event);' style='text-decoration:none;' '>$actionplan_description</span>";		
	  $ret .= "<tr >
					$actionplan
					<td colspan='10' rowspan='2'>
						$actionplan_description
					</td>
				  </tr>
				  <tr>
				  </tr>";
				  $actionplan = '';
			}
		
		else
		{
		if($idap == ''){$idap = '0';}else{}
		$actionplan_description = "<span class='xlnk' onclick='edit_target_text(\"$idobj\",\"$idap\",16,this,event);' style='text-decoration:none;' '>Empty</span>";		
		 $ret .= "<tr >
					$actionplan
					<td colspan='10' rowspan='2'>
						$actionplan_description
					</td>
				  </tr>
				  <tr>
				  </tr>";
				  $actionplan = '';
		}

// Edited by Denimaru
		$ret .= "
			 <tr>
				<td class='tg-even' >KSF Code</td>
				<td class='tg-even' colspan='5'>KSF</td>
				<td class='tg-even' colspan='3'>Target</td>
				<td class='tg-even' colspan='3'>Execution Team</td>
			</tr>
			  ";
			  

	$sqlksf = "SELECT id,ksf_code,ksf_title,ksf_target FROM sms_objective_ksf WHERE id_objective = $idobj";
	$resultksf = $db->query($sqlksf);
	if($db->getRowsNum($resultksf)>0) {
	  while(list($ksf_id, $ksf_code,$ksf_title,$ksf_target)=$db->fetchRow($resultksf)){
			
			
			$target = $ksf_target;
			$ret .="					
					<tr id='trclass_${ksf_id}'>
						<td id='td_code_${ksf_id}'>$ksf_code</td>
						<td id='td_title_${ksf_id}'colspan='5'><span id='sp_${ksf_id}' class='xlnk' onclick='edit_ksf(\"$ksf_id\",\"$idobj\",this,event);'>".htmlentities(stripslashes($ksf_title))."</span</td>
						<td id='td_target_${ksf_id}' colspan='3'>$target</td>
						<td colspan='3' >";
			
			$sqlobjteam = "SELECT id,id_pic FROM sms_objective_team WHERE id_objective_ksf = $ksf_id ";
			$resultobjteam =   $db->query($sqlobjteam);
			if($db->getRowsNum($resultobjteam)>0) {
			while(list($id_team,$id_pic)=$db->fetchRow($resultobjteam)){
			
			
			$sqlteam = "SELECT person_nm  FROM hris_persons WHERE person_id = $id_pic";
			$resultteam =   $db->query($sqlteam);
			list($pic)=$db->fetchRow($resultteam);
				
				$ret .= "<span class='xlnk' onclick='edit_picobj(\"$id_team\",\"$ksf_id\",this,event)'><br />$pic</span>";
				
				}
			}

			
			$ret .=	"<br /><br /><input onclick='edit_picobj(\"new\",\"$ksf_id\",this,event);' type='button' value='Add PIC'/></span></td></tr>";
		// End of edited by Denimaru
			}
		}
		$ret .= "	</table>";

		// Made by Denimaru

		$ret .= "<div style='margin-top:10px;'>" 
		 	 . "<span style='float:right;'><input onclick='new_ksf(\"new\",\"$idobj\");' type='button' value='"._ADD."'/></span>"
		 	 . "<div style='float:left;margin-top:10px' id='trempty'></div>"
		 	 . "</div>";
   		
   		// End of Made by Denimaru
      
      return $ret.$ajax->getJs()."<script src='".XOCP_SERVER_SUBDIR."/include/calendar.js' type='text/javascript'></script><script type='text/javascript'><!--

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

			return $ret;
		
 }
}

} 
?>