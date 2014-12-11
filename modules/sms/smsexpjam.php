<?php
//--------------------------------------------------------------------//
// Filename : modules/sms/smsexpjam.php                     //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2013-12-17                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('SMS_SMSEXPJAM_DEFINED') ) {
   define('SMS_SMSEXPJAM_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/antrain/modconsts.php");

class _sms_SMSExpJam extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _SMS_SMSOBJ_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = 'SMS Export Jam';
   var $display_comment = TRUE;
   var $data;
   
   function __construct($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function listJam() {
   

   
   $psid = $_GET['psid']; 
   $person_id = $_GET['person_id']; 
    global $xocp_vars;
      
   
      $db=&Database::getInstance();
 
  	  $user_id = getUserID();
      $person_id = $employee_id;
	  $returnbtn = "<span style='float:right; margin:5px;'><input onclick='return_approval(\"$psid\",\"$person_id\",this,event);' type='button' value='Not Approved' id='returnbtn'/></span>";   
      
	  
	  
     
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
      

      
      $ret .= "<table style='width:100%;margin-top:10px;' class='xxlist'>"
            . "<colgroup>"
            . "<col width='30'/>"
            . "<col width='50'/>"
            . "<col width='*'/>"
            . "<col width='100'/>"
            . "<col width='100'/>"
            . "<col width='100'/>"
            . "<col width='100'/>"
            . "<col width='100'/>"

            //. "<col width='125'/>"
            . "</colgroup>";
      
      $ret .= "<thead>"
            . "<tr>"
               . "<td style='border-right:1px solid #bbb;text-align:center;' colspan='3'>Objectives</td>"
               . "<td></td>"
               . "<td></td>"
               . "<td></td>"
               . "<td></td>"
               . "<td style='border-right:1px solid #bbb;'></td>"
            . "</tr>"
            . "<tr>"
               . "<td style='border-right:1px solid #bbb;text-align:center;' rowspan='2'>No.</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;' rowspan='2'>ID</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;' rowspan='2'>Refer to Objective and Target Form</td>"
             
			 
               . "<td style='border-right:1px solid #bbb;text-align:center;'>&lt;59%</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;'>69%</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;'>79%</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;'>89%</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;'>100%</td>"
			  
            . "</tr>"
			
           
            . "<tr>"
               . "<td style='border-right:1px solid #bbb;text-align:center;'>Lower performer</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;'>Still need improvement</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;'>Fulfill standard of work performance</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;'>Exceed required performance</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;'>Far exceed required performance</td>"
            . "</tr>"
            . "</thead>";
      
      $ret .= "<tbody>";

    
	$sqlobj = "SELECT DISTINCT a.id,a.objective_code, a.objective_title,a.objective_weight 
		FROM sms_objective a
		LEFT JOIN sms_session b ON ( a.psid = b.id )
		LEFT JOIN sms_objective_ksf d ON ( a.id = d.id_objective )
		LEFT JOIN sms_objective_team c ON ( c.id_objective_ksf = d.id )
		WHERE a.psid = $psid
		AND c.id_pic =$person_id";
	$resultobj = $db->query($sqlobj);
	$nom = 1;
	
	$ret .=  "<tr>"
                . "<td colspan=9>Strategic</td>"
            . "</tr>";
			
	if($db->getRowsNum($resultobj)>0) {
	$nm = $nm++;
	while(list($objid,$objective_code,$objective_title,$objective_weight)=$db->fetchRow($resultobj)){
    	$nm = $nom++;
		if ($ksf_target = '0000-00-00'){
			$ksf_target = ' ';
		}
		else{
		$ksf_target = date('d M Y', strtotime($ksf_target));
		}
		
		if($objective_weight == ''){$objective_weight = 0;}
		else{$objective_weight = $objective_weight;}
		 	 	 	
	$ret .= " <tr height='75'>"
						."<td style='vertical-align:top;border-right:1px solid #bbb;text-align:center;' rowspan='2'>$nm</td>"
						."<td style='vertical-align:top;border-right:1px solid #bbb;text-align:center;color:blue;font-weight:bold;' rowspan='2'>$objective_code</td>"
							."<td style='vertical-align:top;border-right:1px solid #bbb;'>"
								."<span style='color:black;font-weight:bold;'>$objective_title</span>";
				$sqlksf = "SELECT a.id,a.ksf_code,a.ksf_title,a.ksf_lower_perform,a.ksf_need_improvement,a.ksf_target,a.ksf_req,a.ksf_far_req  FROM sms_objective_ksf a JOIN sms_objective_team b ON(a.id =  b.id_objective_ksf) WHERE b.id_pic = $person_id AND a.id_objective = $objid";
				$resultksf = $db->query($sqlksf);
			
					$ret .= "<table>";
					
					while(list($id_ksf,$ksf_code,$ksf_title,$ksf_lowper,$ksf_needimp,$ksf_target,$ksf_req,$ksf_far_req)=$db->fetchRow($resultksf)){
							$ret .="<tr>"
								   ."<div>- $ksf_title</div>"
									."</tr>";
							}
					$ret .="</table>";

					
					$ret .= "</td>";
					
				
	#LOW PER
					
					$ret .= "<td style='white-space:pre-wrap;vertical-align:top;border-right:1px solid #bbb;'>";
					$sqljam = "SELECT id,id_objective,person_id,ap_lower_perform FROM sms_jam_strategic WHERE person_id = $person_id AND id_objective = $objid";
					$resultjam = $db->query($sqljam);
					$ret .= "<table> &nbsp ";
					list($ids,$idobj,$personid,$ap_lowper)=$db->fetchRow($resultjam);
					if($ids == null)
						{ 
							$ap_lowper = 'Empty';
							$ids = $person_id;
						}
					elseif($ids !== null )
						{
							$ids = $ids;
						}
					
						if($ap_lowper == '')
						{
							$ap_lowper = 'Empty';
						}
					else 
						{
							$ap_lowper = $ap_lowper;
						}
					
					
					$ret .="<tr>"
							   ."<td><span class='xlnk' onclick='edit_target_text(\"$ids \",\"$objid\",\"$psid\",0,this,event);'>$ap_lowper</span></td>"
					         ."</tr>";
					$ret .="</table>";		
					$ret .="</td>";
				 
				 #NEED IMPROVEMENT
					$ret .= "<td style='white-space:pre-wrap;vertical-align:top;border-right:1px solid #bbb;'>";
					$sqljam = "SELECT id,id_objective,person_id,ap_need_improvement FROM sms_jam_strategic WHERE person_id = $person_id AND id_objective = $objid";
					$resultjam = $db->query($sqljam);
					$ret .= "<table> &nbsp ";
					list($ids,$idobj,$personid,$ap_need_improvement)=$db->fetchRow($resultjam);
					if($ids == null )
						{ 
							$ap_need_improvement = 'Empty';
							$ids = $person_id;
						}
					elseif($ids !== null )
						{
							$ids = $ids;
						}
					
					if($ap_need_improvement == '')
						{
							$ap_need_improvement = 'Empty';
						}
					else 
						{
							$ap_need_improvement = $ap_need_improvement;
						}
					
					$ret .="<tr>"
							   ."<td><span class='xlnk' onclick='edit_target_text(\"$ids \",\"$objid\",\"$psid\",1,this,event);'>$ap_need_improvement</span></td>"
					         ."</tr>";
					$ret .="</table>";		
					$ret .="</td>";
					
					#TARGET
					$ret .= "<td style='white-space:pre-wrap;vertical-align:top;border-right:1px solid #bbb;'>";
					$sqljam = "SELECT id,id_objective,person_id,ap_target FROM sms_jam_strategic WHERE person_id = $person_id AND id_objective = $objid";
					$resultjam = $db->query($sqljam);
					$ret .= "<table> &nbsp ";
					list($ids,$idobj,$personid,$ap_target)=$db->fetchRow($resultjam);
					if($ids==null)
						{
							$ap_target = 'Empty';
							$ids = $person_id;
						}
					elseif($ids !== null )
						{
							$ids = $ids;
						}
					
						if($ap_target == '')
						{
							$ap_target = 'Empty';
						}
					else 
						{
							$ap_target = $ap_target;
						}
					
					$ret .="<tr>"
							   ."<td><span class='xlnk' onclick='edit_target_text(\"$ids \",\"$objid\",\"$psid\",2,this,event);'>$ap_target</span></td>"
					         ."</tr>";
					$ret .="</table>";		
					$ret .="</td>";
					
					#REQ PER
					$ret .= "<td style='white-space:pre-wrap;vertical-align:top;border-right:1px solid #bbb;'>";
					$sqljam = "SELECT id,id_objective,person_id,ap_req FROM sms_jam_strategic WHERE person_id = $person_id AND id_objective = $objid";
					$resultjam = $db->query($sqljam);
					$ret .= "<table> &nbsp ";
					list($ids,$idobj,$personid,$ap_req)=$db->fetchRow($resultjam);
					if($ids == null)
						{ 
							$ap_req = 'Empty';
							$ids = $person_id;
						}
					elseif($ids !== null )
						{
							$ids = $ids;
						}
					
					if($ap_req == '')
						{
							$ap_req = 'Empty';
						}
					else 
						{
							$ap_req = $ap_req;
						}
					
					$ret .="<tr>"
							   ."<td><span class='xlnk' onclick='edit_target_text(\"$ids \",\"$objid\",\"$psid\",3,this,event);'>$ap_req</span></td>"
					         ."</tr>";
					$ret .="</table>";		
					$ret .="</td>";
					
					#FAR REQ PER
					$ret .= "<td style='white-space:pre-wrap;vertical-align:top;border-right:1px solid #bbb;'>";
					$sqljam = "SELECT id,id_objective,person_id,ap_far_req FROM sms_jam_strategic WHERE person_id = $person_id AND id_objective = $objid";
					$resultjam = $db->query($sqljam);
					$ret .= "<table> &nbsp ";
					list($ids,$idobj,$personid,$ap_far_req)=$db->fetchRow($resultjam);
					if($ids == null)
						{ 
							$ap_far_req = 'Empty';
						   $ids = $person_id;
						 }
					elseif($ids !== null )
						{
							$ids = $ids;
						}
					
					if($ap_far_req == '')
						{
							$ap_far_req = 'Empty';
						}
					else 
						{
							$ap_far_req = $ap_far_req;
						}
					$ret .="<tr>"
							   ."<td><span class='xlnk' onclick='edit_target_text(\"$ids \",\"$objid\",\"$psid\",4,this,event);'>$ap_far_req</span></td>"
					         ."</tr>";
					$ret .="</table>";		
					$ret .="</td>";
					
					
					#EDIT
				
					$sqljam = "SELECT id,value FROM sms_jam_strategic WHERE person_id = $person_id AND id_objective = $objid";
					$resultjam = $db->query($sqljam);
					list($ids,$objective_weight)=$db->fetchRow($resultjam);
					if($ids == null)
						{ 
							$objective_weight = '0';
							$ids = $person_id;
						}
					elseif($ids !== null )
						{
							$ids = $ids;
						}
					
						if($objective_weight == '')
						{
							$objective_weight = '0';
						}
					else 
						{
							$objective_weight = $objective_weight;
						}
			
				$wgt1 = $objective_weight * 59/100;
				
				$wgt2 = $objective_weight * 69/100;
				
				$wgt3 = $objective_weight * 79/100;
				
				$wgt4 = $objective_weight * 89/100;
				
				$wgt5 = $objective_weight * 100/100;

					$ret	.= "</tr>";
      $ret .= "<tr>"
					."<td style='border-right:1px solid #bbb;text-align:right;'><span class='xlnk' onclick='edit_target_text(\"$ids\",\"$objid\",\"$psid\",5,this,event);'>$objective_weight</span> %</td>"
					
					."<td style='border-right:1px solid #bbb;text-align:right;'>$wgt1 %</td>"
					
					."<td style='border-right:1px solid #bbb;text-align:right;'>$wgt2 % </td>"
					
					."<td style='border-right:1px solid #bbb;text-align:right;'>$wgt3 %</td>"
					
					."<td style='border-right:1px solid #bbb;text-align:right;'>$wgt4 %</td>"
					
					."<td style='border-right:1px solid #bbb;text-align:right;'>$wgt5 %</td>"
					
				."</tr>";
		}
	  }
	  else{
	  
		$ret .= "<tr><td colspan='10' style='text-align:center;font-style:italic;'>"._EMPTY."</td></tr>";
	

	  }
	  
		

    
	$sqlobj = "SELECT DISTINCT a.id, a.section_objective_desc, a.weight
		FROM sms_section_objective a
		LEFT JOIN sms_session b ON ( a.id_section_session = b.id )
		LEFT JOIN sms_kpi d ON ( a.id = d.sms_objective_id )
		LEFT JOIN sms_action_plan c ON ( c.sms_objective_id = d.sms_objective_id )
		WHERE a.id_section_session = $psid
		AND c.sms_action_plan_pic_employee_id LIKE '%$person_id%'";
	$resultobj = $db->query($sqlobj);
	$nom = 1;
	
	  $ret .=  "<tr>"
                . "<td colspan=9>Functional </td>"
            . "</tr>";
			
	if($db->getRowsNum($resultobj)>0) {
	$nm = $nm++;
	while(list($objid,$objective_title,$objective_weight)=$db->fetchRow($resultobj)){
    	$nm = $nom++;
		
		 	 	 	
	$ret .= " <tr height='75'>"
						."<td style='vertical-align:top;border-right:1px solid #bbb;text-align:center;' rowspan='2'>$nm</td>"
						."<td style='vertical-align:top;border-right:1px solid #bbb;text-align:center;color:blue;font-weight:bold;' rowspan='2'></td>"
							."<td style='vertical-align:top;border-right:1px solid #bbb;'>"
								."<span style='color:black;font-weight:bold;'>$objective_title </span>";
				$sqlactionplan = "SELECT  sms_action_plan_id,sms_action_plan_text,sms_kpi_id FROM sms_action_plan WHERE sms_action_plan_pic_employee_id LIKE '%$person_id%' AND sms_objective_id = $objid ORDER BY sms_kpi_id";
				$resultactionplan = $db->query($sqlactionplan);
			
					$ret .= "<table><br>";
					
					while(list($id_ap,$ap_title,$kpid)=$db->fetchRow($resultactionplan)){
							$ret .="<tr>"
								   ."<div>- $ap_title  </div>"
									."</tr>";
							}
					$ret .="</table>";

					
					$ret .= "</td>";
					
				
		#LOW PER
					
					$ret .= "<td style='white-space:pre-wrap;vertical-align:top;border-right:1px solid #bbb;'>";
					$sqljam = "SELECT id,id_objective,person_id,ap_lower_perform FROM sms_jam_functional WHERE person_id = $person_id AND id_objective = $objid";
					$resultjam = $db->query($sqljam);
					$ret .= "<table> &nbsp ";
					list($ids,$idobj,$personid,$ap_lowper)=$db->fetchRow($resultjam);
					if($ids == null)
						{ 
							$ap_lowper = 'Empty';
							$ids = $person_id;
						}
					elseif($ids !== null )
						{
							$ids = $ids;
						}
					
						if($ap_lowper == '')
						{
							$ap_lowper = 'Empty';
						}
					else 
						{
							$ap_lowper = $ap_lowper;
						}
					
					
					$ret .="<tr>"
							   ."<td><span class='xlnk' onclick='edit_target_text(\"$ids \",\"$objid\",\"$psid\",6,this,event);'>$ap_lowper</span></td>"
					         ."</tr>";
					$ret .="</table>";		
					$ret .="</td>";
				 
				 #NEED IMPROVEMENT
					$ret .= "<td style='white-space:pre-wrap;vertical-align:top;border-right:1px solid #bbb;'>";
					$sqljam = "SELECT id,id_objective,person_id,ap_need_improvement FROM sms_jam_functional WHERE person_id = $person_id AND id_objective = $objid";
					$resultjam = $db->query($sqljam);
					$ret .= "<table> &nbsp ";
					list($ids,$idobj,$personid,$ap_need_improvement)=$db->fetchRow($resultjam);
					if($ids == null )
						{ 
							$ap_need_improvement = 'Empty';
							$ids = $person_id;
						}
					elseif($ids !== null )
						{
							$ids = $ids;
						}
					
					if($ap_need_improvement == '')
						{
							$ap_need_improvement = 'Empty';
						}
					else 
						{
							$ap_need_improvement = $ap_need_improvement;
						}
					
					$ret .="<tr>"
							   ."<td><span class='xlnk' onclick='edit_target_text(\"$ids \",\"$objid\",\"$psid\",7,this,event);'>$ap_need_improvement</span></td>"
					         ."</tr>";
					$ret .="</table>";		
					$ret .="</td>";
					
					#TARGET
					$ret .= "<td style='white-space:pre-wrap;vertical-align:top;border-right:1px solid #bbb;'>";
					
					
					$sqlactionplan = "SELECT DISTINCT a.sms_kpi_id FROM sms_action_plan a JOIN sms_kpi b ON ( a.sms_objective_id = b.sms_objective_id ) WHERE a.sms_action_plan_pic_employee_id LIKE '%$person_id%' AND b.sms_objective_id = $objid ORDER BY a.sms_kpi_id ASC LIMIT 0 , 1";
					$resultactionplan = $db->query($sqlactionplan);

			
					$ret .= "<table>";
					
					list($kpid)=$db->fetchRow($resultactionplan);
					
					$sqlkpi = "SELECT sms_kpi_text,sms_kpi_target_text, sms_kpi_note,sms_kpi_measurement_unit FROM sms_kpi WHERE sms_objective_id = $objid AND sms_kpi_id = $kpid ";
					$resultkpi = $db->query($sqlkpi);
					list($sms_kpi_text,$sms_kpi_target_text,$sms_kpi_note,$sms_kpi_measurement_unit)=$db->fetchRow($resultkpi);
					
					
					$sqljam = "SELECT id,id_objective,person_id,ap_target FROM sms_jam_functional WHERE person_id = $person_id AND id_objective = $objid";
					$resultjam = $db->query($sqljam);
					$ret .= "<table> &nbsp ";
					list($ids,$idobj,$personid,$ap_target)=$db->fetchRow($resultjam);
					if($ids==null)
						{
							$ap_target = 'Empty';
							$ids = $person_id;
						}
					elseif($ids !== null )
						{
							$ids = $ids;
						}
					
						if($ap_target == '')
						{
							$ap_target = 'Empty';
						}
					else 
						{
							$ap_target = $ap_target;
						}
					
					$ret .="<tr>"
					
							   ."<td><span>$sms_kpi_text</span><span> ($sms_kpi_target_text$sms_kpi_measurement_unit)</span><br><span class='xlnk' onclick='edit_target_text(\"$ids \",\"$objid\",\"$psid\",8,this,event);'>$ap_target</span></td>"
					         ."</tr>";
					$ret .="</table>";		
					$ret .="</td>";
					
					#REQ PER
					$ret .= "<td style='white-space:pre-wrap;vertical-align:top;border-right:1px solid #bbb;'>";
					$sqljam = "SELECT id,id_objective,person_id,ap_req FROM sms_jam_functional WHERE person_id = $person_id AND id_objective = $objid";
					$resultjam = $db->query($sqljam);
					$ret .= "<table> &nbsp ";
					list($ids,$idobj,$personid,$ap_req)=$db->fetchRow($resultjam);
					if($ids == null)
						{ 
							$ap_req = 'Empty';
							$ids = $person_id;
						}
					elseif($ids !== null )
						{
							$ids = $ids;
						}
					
					if($ap_req == '')
						{
							$ap_req = 'Empty';
						}
					else 
						{
							$ap_req = $ap_req;
						}
					
					$ret .="<tr>"
							   ."<td><span class='xlnk' onclick='edit_target_text(\"$ids \",\"$objid\",\"$psid\",9,this,event);'>$ap_req</span></td>"
					         ."</tr>";
					$ret .="</table>";		
					$ret .="</td>";
					
					#FAR REQ PER
					$ret .= "<td style='white-space:pre-wrap;vertical-align:top;border-right:1px solid #bbb;'>";
					$sqljam = "SELECT id,id_objective,person_id,ap_far_req FROM sms_jam_functional WHERE person_id = $person_id AND id_objective = $objid";
					$resultjam = $db->query($sqljam);
					$ret .= "<table> &nbsp ";
					list($ids,$idobj,$personid,$ap_far_req)=$db->fetchRow($resultjam);
					if($ids == null)
						{ 
							$ap_far_req = 'Empty';
						   $ids = $person_id;
						 }
					elseif($ids !== null )
						{
							$ids = $ids;
						}
					
					if($ap_far_req == '')
						{
							$ap_far_req = 'Empty';
						}
					else 
						{
							$ap_far_req = $ap_far_req;
						}
					$ret .="<tr>"
							   ."<td><span class='xlnk' onclick='edit_target_text(\"$ids \",\"$objid\",\"$psid\",10,this,event);'>$ap_far_req</span></td>"
					         ."</tr>";
					$ret .="</table>";		
					$ret .="</td>";
					
					
					#EDIT
				
					$sqljam = "SELECT id,value FROM sms_jam_functional WHERE person_id = $person_id AND id_objective = $objid";
					$resultjam = $db->query($sqljam);
					list($ids,$objective_weight)=$db->fetchRow($resultjam);
					if($ids == null)
						{ 
							$objective_weight = '0';
							$ids = $person_id;
						}
					elseif($ids !== null )
						{
							$ids = $ids;
						}
					
						if($objective_weight == '')
						{
							$objective_weight = '0';
						}
					else 
						{
							$objective_weight = $objective_weight;
						}
			
				$wgt1 = $objective_weight * 59/100;
				
				$wgt2 = $objective_weight * 69/100;
				
				$wgt3 = $objective_weight * 79/100;
				
				$wgt4 = $objective_weight * 89/100;
				
				$wgt5 = $objective_weight * 100/100;

					$ret	.= "</tr>";
      $ret .= "<tr>"
					."<td style='border-right:1px solid #bbb;text-align:right;'><span class='xlnk' onclick='edit_target_text(\"$ids\",\"$objid\",\"$psid\",11,this,event);'>$objective_weight</span> %</td>"
					
					."<td style='border-right:1px solid #bbb;text-align:right;'>$wgt1 %</td>"
					
					."<td style='border-right:1px solid #bbb;text-align:right;'>$wgt2 % </td>"
					
					."<td style='border-right:1px solid #bbb;text-align:right;'>$wgt3 %</td>"
					
					."<td style='border-right:1px solid #bbb;text-align:right;'>$wgt4 %</td>"
					
					."<td style='border-right:1px solid #bbb;text-align:right;'>$wgt5 %</td>"
					
				."</tr>";
		}
	  }
	  else{
	  
		$ret .= "<tr><td colspan='10' style='text-align:center;font-style:italic;'>"._EMPTY."</td></tr>";
	

	  }
	
	
	
	#SUM KSF
		$sqlksf = "SELECT SUM(value)
		FROM sms_jam_strategic 
		WHERE id_session = $psid
		AND person_id =$person_id";
		$resultksf = $db->query($sqlksf);
	list($sumwgtksf)=$db->fetchRow($resultksf);
	
		
	#SUM KPI
		
	$sqlkpi = "SELECT SUM(value)
		FROM sms_jam_functional 
		WHERE id_session = $psid
		AND person_id =$person_id";

	$resultkpi = $db->query($sqlkpi);
	list($sumwgtkpi)=$db->fetchRow($resultkpi);
	 
	$sumall = $sumwgtkpi + $sumwgtksf;
	
	$wgtall1 = $sumall * 59/100;
				
	$wgtall2 = $sumall * 69/100;
		
	$wgtall3 = $sumall * 79/100;
				
	$wgtall4 = $sumall * 89/100;
				
	$wgtall5 = $sumall * 100/100;


	
      $ret .="<tr>"
				 . "<td colspan='2' style='border-top:1px solid #bbb;background-color:#eee;color:black;border-right:1px solid #bbb;text-align:center;font-weight:bold;'>Total</td>"
				 . "<td style='border-top:1px solid #bbb;background-color:#eee;color:black;border-right:1px solid #bbb;text-align:right;font-weight:bold;'>$sumall %</td>"
				 . "<td style='border-top:1px solid #bbb;background-color:#eee;color:black;border-right:1px solid #bbb;text-align:right;font-weight:bold;'>$wgtall1  %</td>"
				 . "<td style='border-top:1px solid #bbb;background-color:#eee;color:black;border-right:1px solid #bbb;text-align:right;font-weight:bold;'>$wgtall2 %</td>"
				 . "<td style='border-top:1px solid #bbb;background-color:#eee;color:black;border-right:1px solid #bbb;text-align:right;font-weight:bold;'>$wgtall3 %</td>"
				 . "<td style='border-top:1px solid #bbb;background-color:#eee;color:black;border-right:1px solid #bbb;text-align:right;font-weight:bold;'>$wgtall4 %</td>"
				 . "<td style='border-top:1px solid #bbb;background-color:#eee;color:black;border-right:1px solid #bbb;text-align:right;font-weight:bold;'>$wgtall5 %</td>"
			."</tr>";
      
      $ret .= "</tbody>";
      $ret .= "</table>";
      $ret .= "</div>";


	  
      $doubleapproval = 1;
      #PROPOSE
	  
      $sqlprop=   "SELECT propose_stat FROM sms_jam_approval WHERE id_session = '$psid' AND employee_id = '$person_id'";
      $resultprop = $db->query($sqlprop);
	  list($propose_stat)=$db->fetchRow($resultprop);
	  
	  if($propose_stat == '1')
	  {
			    $sql = "SELECT c.job_nm,c.job_abbr,d.org_nm,d.org_abbr,a.employee_ext_id,e.person_nm,e.person_id"
           . " FROM ".XOCP_PREFIX."employee a"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job b ON b.employee_id = a.employee_id AND b.job_id = '$job_id'"
           . " LEFT JOIN ".XOCP_PREFIX."jobs c ON c.job_id = '$job_id'"
           . " LEFT JOIN ".XOCP_PREFIX."orgs d ON d.org_id = c.org_id"
           . " LEFT JOIN ".XOCP_PREFIX."persons e ON e.person_id = a.person_id"
           . " WHERE a.employee_id = '$employee_id'";
		  $result = $db->query($sql);
		  list($job_nm,$job_abbr,$org_nm,$org_abbr,$nip,$employee_nm,$person_id)=$db->fetchRow($result);
	  
	  }
	  else
	  {
		$employee_nm = "";
	  }
	  
	  
	  
	   #APPROVE 1
     	$sqlapp=   "SELECT id,approve1_stat ,approve1_date,approve1_by   FROM sms_jam_approval WHERE id_session = '$psid' AND employee_id = '$person_id'";
		$resultapp = $db->query($sqlapp);
		list($id_app,$approve1_stat,$approve1_date,$approve1_by )=$db->fetchRow($resultapp);
		
		$approve1_date  = date('d M Y', strtotime($approve1_date ));
			  
		   if($approve1_date  == '01 Jan 1970' || $approve1_date == '30 Nov 1999')
			{
				$approve1_date  = '-';
			}
			else
			{
				$approve1_date  = $approve1_date ;
			}
	  
	  
	  if($approve1_stat == '1' )
	  {
	  
	  	$sql = "SELECT c.job_nm,e.person_nm"
           . " FROM hris_employee a"
           . " LEFT JOIN  hris_employee_job b ON b.employee_id = a.employee_id "
           . " LEFT JOIN  hris_jobs c ON c.job_id = b.job_id"
           . " LEFT JOIN  hris_orgs d ON d.org_id = c.org_id"
           . " LEFT JOIN  hris_persons e ON e.person_id = a.person_id"
           . " WHERE a.employee_id = '$approve1_by'";
		  
		$result = $db->query($sql);
		list($app_job_nm,$employee_app1)=$db->fetchRow($result);
	  
			 $approvedbutton ='';
			 $returnbtn = '';
	  
	  }
	    elseif($propose_stat== null)
	  {
	  
	  	$sql = "SELECT c.job_nm,e.person_nm"
           . " FROM hris_employee a"
           . " LEFT JOIN  hris_employee_job b ON b.employee_id = a.employee_id "
           . " LEFT JOIN  hris_jobs c ON c.job_id = b.job_id"
           . " LEFT JOIN  hris_orgs d ON d.org_id = c.org_id"
           . " LEFT JOIN  hris_persons e ON e.person_id = a.person_id"
           . " WHERE a.employee_id = '$approve1_by'";
		  
		$result = $db->query($sql);
		list($app_job_nm,$employee_app1)=$db->fetchRow($result);
	  
			 $approvedbutton ='';
			
			  $returnbtn = '';
	  }
	  else
	  {
	  	  
		  $approvedbutton = "<form id='proposedbutton' method= 'post'>"
										. "<input type='button' onclick='approve(\"$id_app\",this,event);' value='Approve'  id='approvebut'>"
								 ."</form>";
	  
		   $returnbtn = "<span style='float:right; margin:5px;'><input onclick='return_approval(\"$psid\",\"$person_id\",this,event);' type='button' value='Not Approved' id='returnbtn'/></span>";   

	  }
	 
	 
	  #APPROVE 2
     	$sqlapp2=   "SELECT id,approve2_stat ,approve2_date,approve2_by FROM sms_jam_approval WHERE id_session = '$psid' AND employee_id = '$person_id'";
		$resultapp2 = $db->query($sqlapp2);
		list($id_app,$approve2_stat,$approve2_date,$approve2_by )=$db->fetchRow($resultapp2);
		
		$approve2_date  = date('d M Y', strtotime($approve2_date ));
			  
		   if($approve2_date  == '01 Jan 1970' || $approve2_date == '30 Nov 1999')
			{
				$approve2_date  = '-';
			}
			else
			{
				$approve2_date  = $approve2_date ;
			}
	  
	  
	  if($approve2_stat == '1' )
	  {
	  
	  	$sql = "SELECT c.job_nm,e.person_nm"
           . " FROM hris_employee a"
           . " LEFT JOIN  hris_employee_job b ON b.employee_id = a.employee_id "
           . " LEFT JOIN  hris_jobs c ON c.job_id = b.job_id"
           . " LEFT JOIN  hris_orgs d ON d.org_id = c.org_id"
           . " LEFT JOIN  hris_persons e ON e.person_id = a.person_id"
           . " WHERE a.employee_id = '$approve2_by'";
		  
		$result = $db->query($sql);
		list($app2_job_nm,$employee_app2)=$db->fetchRow($result);
	  
			 $approvedbutton2 ='';
			 $returnbtn2 = '';   

	  
	  }
	    elseif($approve1_stat== null || $approve1_stat=='0' )
	  {
	  
	  	$sql = "SELECT c.job_nm,e.person_nm"
           . " FROM hris_employee a"
           . " LEFT JOIN  hris_employee_job b ON b.employee_id = a.employee_id "
           . " LEFT JOIN  hris_jobs c ON c.job_id = b.job_id"
           . " LEFT JOIN  hris_orgs d ON d.org_id = c.org_id"
           . " LEFT JOIN  hris_persons e ON e.person_id = a.person_id"
           . " WHERE a.employee_id = '$approve2_by'";
		  
		$result = $db->query($sql);
		list($app2_job_nm,$employee_app2)=$db->fetchRow($result);
	  
			 $approvedbutton2 ='';
			  $returnbtn2 = "";   

			 
			
	  }
	  else
	  {
	  	  
		  $approvedbutton2 = "<form id='proposedbutton' method= 'post'>"
										. "<input type='button' onclick='approve2(\"$id_app\",this,event);' value='Approve'  id='approvebut2'>"
								 ."</form>";
		  $returnbtn2 = "<span style='float:right; margin:5px;'><input onclick='return_approval(\"$psid\",\"$person_id\",this,event);' type='button' value='Not Approved' id='returnbtn'/></span>";   

	  
	  }
	 
	   $sqlprop=   "SELECT propose_date,return_note,date_return FROM sms_jam_approval WHERE id_session = '$psid' AND employee_id = '$person_id'";
	   $resultprop = $db->query($sqlprop);
		list($propose_date,$return_note,$date_return)=$db->fetchRow($resultprop);
		
		$propose_date = date('d M Y', strtotime($propose_date));
		
		   
		     if($propose_date  == '01 Jan 1970' || $propose_date == '30 Nov 1999')
			{
				$propose_date = '-';
				$proposedatestat = "Returned on:<br/> $date_return";
				$notereturn = "<div> Note: $return_note </div>";
				$notapp = 'Not Approved';
				$approvedbutton = "";
				$returnbtn = "";
			}
			else
			{
				$propose_date = $propose_date;
				$proposedatestat = "Submitted on:<br/> $propose_date";
				$notereturn = "";
				$notapp = "";
			}
		   
	  
	  $ret .= "<div style='padding: 4px 8px 28px 0;font-size:12px;color: #666666; text-align:left;'> <div id='id_return'></div><div id='id_return_divmjr'></div><div id='id_return_dir'></div></div>";
	
	 $form .= "$notereturn";
	 
	  $form .= "<div style='text-align:right;padding:10px;margin-top:20px;'>"
             //. $all_jam_status . $employee_id
    	   . "<table align='right' style='border-top:2px solid #777;border-left:2px solid #777;border-spacing:0px;'>"
                . "<colgroup>"
                . "<col width='200'/>"
				. "<col width='200'/>"
                . "<col width='200'/>"
                . "</colgroup>"
                . "<tbody>"
                . "<tr>"
                . "<td  style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;'>"
                . "Proposed by,"
                . "</td>"
			    . "<td colspan='2' style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;'>"
                . "Approved by,"
                . "</td>"
                . "</tr>"
				
				 . "<tr>"
                . "<td height=60 style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
				. "<span style= margin:5px;'>$proposedbutton</span> "
                . "</td>"
				   . "<td style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
				. "<span style= 'float: left; margin: 4px 0px 0px 14px;'>$approvedbutton </span> $returnbtn"
                . "</td>"
                . "<td style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
              . "<span style= 'float: left; margin: 4px 0px 0px 14px;'>$approvedbutton2</span> $returnbtn2"
                . "</td>"
                
                . "</tr>"
				
                . "<tr>"
                . "<td id=proposed_${psid} style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
                . "$employee_nm "
                . "$notapp  "
                . "</td>"
				 . "<td id=proposeddiv_${psid} style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
                . "$employee_app1<br>$app_job_nm"
                . "</td>"
				. "<td id=approved_${psid} style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
				. "$employee_app2<br>$app2_job_nm"
                . "</td>"
                
                
                . "</tr>"
                . "<tr>"
                . "<td id=dateproposed_${psid} style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;padding:5px;' id='submited_by_button'>"
                . "$proposedatestat"
                . "</td>"
				. "<td id=dateproposeddiv_${psid} style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;padding:5px;' id='submited_by_button'>"
                . "Approved on:<br/> $approve1_date "
                . "</td>"
               . "<td id=dateapproved_${psid} style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;padding:5px;' id='submited_by_button'>"
                 . "Approved on:<br/> $approve2_date "
				. "</td>"
                
                . "</tr>"
                . "</tbody>"
                . "</table>"
           . "</div>";
   
   
   }
   
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $this->listJam();
            break;
         default:
            $ret = $this->listJam();
            break;
   }
      /*    $sqljob = "SELECT j.job_id FROM hris_employee_job j LEFT JOIN hris_users u ON (u.person_id = j.employee_id) WHERE u.user_id = ".getUserid()."";
	$resultjob = $db->query($sqljob);
	 list($job_id)=$db->fetchRow($resultjob);
	//echo 'getuserid = '.getuserid(). '<br> job_id = '.$job_id; exit();
	 	 if ($job_id ==  130 || $job_id == 90 || $job_id == 68 || $job_id == 101 || $job_id == 0){ */
      return $ret;
/* 	  }
	  else
	  {
	  return 'you have no access';
} */
   }
}

} 
?>