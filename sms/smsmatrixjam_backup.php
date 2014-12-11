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

include_once(XOCP_DOC_ROOT."/config.php");
global $xocpConfig;

require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
include_once(XOCP_DOC_ROOT."/modules/sms/class/selectsmsmatrixjam.php");
include_once(XOCP_DOC_ROOT."/modules/sms/class/ajax_smssession.php");

class _sms_matrix_JAM extends XocpBlock {
   var $catchvar = _ANTRAIN_CATCH_VAR;
   var $blockID = _ANTRAIN_PLAN_SS_MATRIX_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = 'SMS My JAM';
   var $display_comment = TRUE;
   var $data;
   
   function _sms_matrix_jam($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */

   }
   

   function smsmatrixjam() {
      $psid = $_SESSION["pms_psid"];
      global $xocp_vars;
      
      
      $db=&Database::getInstance();
      $ajax = new _sms_class_SMSSessionAjax("orgjx");
  	  $user_id = getUserID();
      
   
       $ret = "";
      
     
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
      
      $ret .= "<div>";
      
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
	$sqlperid = "SELECT person_id FROM hris_users WHERE user_id = ".getUserid()."";
	$resultperid = $db->query($sqlperid);
	list($person_id)=$db->fetchRow($resultperid);
    
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
							
							$sqlksf = "SELECT  a.id,a.ksf_code,a.ksf_title,a.ksf_lower_perform FROM sms_objective_ksf a JOIN sms_objective_team b ON(a.id =  b.id_objective_ksf) WHERE b.id_pic = $person_id AND a.id_objective = $objid";
							$resultksf = $db->query($sqlksf);
							$ret .= "<table> &nbsp ";
							while(list($id_ksf,$ksf_code,$ksf_title,$ksf_lowper)=$db->fetchRow($resultksf)){
							if($ksf_lowper == '')
							{ $ksf_lowper = 'Empty';	}
							else
							{	$ksf_lowper = $ksf_lowper; }
							$ret .="<tr>"
								   ."<td><span class='xlnk' onclick='edit_target_text(\"$id_ksf\",0,this,event);'>$ksf_lowper</span></td>"
									."</tr>";
							}
					$ret .="</table>";		
					$ret .="</td>";
				 
				 #NEED IMPROVEMENT
					$ret .= "<td style='white-space:pre-wrap;vertical-align:top;border-right:1px solid #bbb;'>";
							
							$sqlksf = "SELECT  a.id,a.ksf_code,a.ksf_title,a.ksf_need_improvement FROM sms_objective_ksf a JOIN sms_objective_team b ON(a.id =  b.id_objective_ksf) WHERE b.id_pic = $person_id AND a.id_objective = $objid";
							$resultksf = $db->query($sqlksf);
							$ret .= "<table> &nbsp ";
							while(list($id_ksf,$ksf_code,$ksf_title,$ksf_needimp)=$db->fetchRow($resultksf)){
							
							if($ksf_needimp == '')
							{ $ksf_needimp = 'Empty';	}
							else
							{	$ksf_needimp = $ksf_needimp; }					
							$ret .="<tr>"
								   ."<td><span class='xlnk' onclick='edit_target_text(\"$id_ksf\",1,this,event);'>$ksf_needimp</span></td>"
									."</tr>";
							}
					$ret .="</table>";		
					$ret .="</td>";
					
					#TARGET
					$ret .= "<td style='white-space:pre-wrap;vertical-align:top;border-right:1px solid #bbb;'>";
							
							$sqlksf = "SELECT  a.id,a.ksf_code,a.ksf_title,ksf_target FROM sms_objective_ksf a JOIN sms_objective_team b ON(a.id =  b.id_objective_ksf) WHERE b.id_pic = $person_id AND a.id_objective = $objid";
							$resultksf = $db->query($sqlksf);
							$ret .= "<table> &nbsp ";
							while(list($id_ksf,$ksf_code,$ksf_title,$ksf_target)=$db->fetchRow($resultksf)){
							
							if($ksf_target == '')
							{ $ksf_target = 'Empty';	}
							else
							{	$ksf_target = $ksf_target; }	
							$ret .="<tr>"
								   ."<td><span class='xlnk' onclick='edit_target_text(\"$id_ksf\",2,this,event);'>$ksf_target</span></td>"
									."</tr>";
							}
					$ret .="</table>";		
					$ret .="</td>";
					
					#REQ PER
					$ret .= "<td style='white-space:pre-wrap;vertical-align:top;border-right:1px solid #bbb;'>";
							
							$sqlksf = "SELECT  a.id,a.ksf_code,a.ksf_title,a.ksf_req  FROM sms_objective_ksf a JOIN sms_objective_team b ON(a.id =  b.id_objective_ksf) WHERE b.id_pic = $person_id AND a.id_objective = $objid";
							$resultksf = $db->query($sqlksf);
							$ret .= "<table> &nbsp ";
							while(list($id_ksf,$ksf_code,$ksf_title,$ksf_req)=$db->fetchRow($resultksf)){
							
							if($ksf_req == '')
							{ $ksf_req = 'Empty';	}
							else
							{	$ksf_req = $ksf_req; }	
							$ret .="<tr>"
								   ."<td><span class='xlnk' onclick='edit_target_text(\"$id_ksf\",3,this,event);'>$ksf_req</span></td>"
									."</tr>";
							}
					$ret .="</table>";		
					$ret .="</td>";
					
					#FAR REQ PER
					$ret .= "<td style='white-space:pre-wrap;vertical-align:top;border-right:1px solid #bbb;'>";
							
							$sqlksf = "SELECT  a.id,a.ksf_code,a.ksf_title,a.ksf_far_req  FROM sms_objective_ksf a JOIN sms_objective_team b ON(a.id =  b.id_objective_ksf) WHERE b.id_pic = $person_id AND a.id_objective = $objid";
							$resultksf = $db->query($sqlksf);
							$ret .= "<table> &nbsp ";
							while(list($id_ksf,$ksf_code,$ksf_title,$ksf_far_req)=$db->fetchRow($resultksf)){
							
							if($ksf_far_req == '')
							{ $ksf_far_req = 'Empty';	}
							else
							{	$ksf_far_req = $ksf_far_req; }	
							$ret .="<tr>"
								   ."<td><span class='xlnk' onclick='edit_target_text(\"$id_ksf\",4,this,event);'>$ksf_far_req</span></td>"
									."</tr>";
							}
					$ret .="</table>";		
					$ret .="</td>";
					
					
					#EDIT
				
			
				
				$wgt1 = $objective_weight * 59/100;
				
				$wgt2 = $objective_weight * 69/100;
				
				$wgt3 = $objective_weight * 79/100;
				
				$wgt4 = $objective_weight * 89/100;
				
				$wgt5 = $objective_weight * 100/100;

					$ret	.= "</tr>";
      $ret .= "<tr>"
					."<td style='border-right:1px solid #bbb;text-align:right;'><span class='xlnk' onclick='edit_target_text(\"$objid\",5,this,event);'>$objective_weight</span> %</td>"
					
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
	  
	  #FUNCTIONAL 
	
	    $ret .= "<tbody>";
	$sqlperid = "SELECT person_id FROM hris_users WHERE user_id = ".getUserid()."";
	$resultperid = $db->query($sqlperid);
	list($person_id)=$db->fetchRow($resultperid);
    
	$sqlobj = "SELECT DISTINCT a.id,a.objective_code, a.objective_title,a.objective_weight 
		FROM sms_objective a
		LEFT JOIN sms_session b ON ( a.psid = b.id )
		LEFT JOIN sms_kpi d ON ( a.id = d.sms_objective_id )
		LEFT JOIN sms_action_plan c ON ( c.sms_kpi_id = d.sms_kpi_id )
		WHERE a.psid = $psid
		AND c.sms_action_plan_pic_employee_id LIKE '%$person_id%'";
	$resultobj = $db->query($sqlobj);
	$nom = 1;
	
	  $ret .=  "<tr>"
                . "<td colspan=9>Functional </td>"
            . "</tr>";
			
	if($db->getRowsNum($resultobj)>0) {
	$nm = $nm++;
	while(list($objid,$objective_code,$objective_title,$objective_weight)=$db->fetchRow($resultobj)){
    	$nm = $nom++;
		
		 	 	 	
	$ret .= " <tr height='75'>"
						."<td style='vertical-align:top;border-right:1px solid #bbb;text-align:center;' rowspan='2'>$nm</td>"
						."<td style='vertical-align:top;border-right:1px solid #bbb;text-align:center;color:blue;font-weight:bold;' rowspan='2'>$objective_code</td>"
							."<td style='vertical-align:top;border-right:1px solid #bbb;'>"
								."<span style='color:black;font-weight:bold;'>$objective_title</span>";
				$sqlactionplan = "SELECT a.sms_action_plan_id,a.sms_action_plan_text FROM sms_action_plan a JOIN sms_kpi b ON(a.sms_kpi_id =  b.sms_kpi_id) WHERE b.sms_kpi_pic_employee_id LIKE '%$person_id%' AND b.sms_objective_id = $objid";
				$resultactionplan = $db->query($sqlactionplan);
			
					$ret .= "<table>";
					
					while(list($id_ap,$ap_title)=$db->fetchRow($resultactionplan)){
							$ret .="<tr>"
								   ."<div>- $ap_title</div>"
									."</tr>";
							}
					$ret .="</table>";

					
					$ret .= "</td>";
					
				
					#LOW PER
					$ret .= "<td style='white-space:pre-wrap;vertical-align:top;border-right:1px solid #bbb;'>";
							
				$sqlactionplan = "SELECT a.sms_action_plan_id,a.ap_lower_perform FROM sms_action_plan a JOIN sms_kpi b ON(a.sms_kpi_id =  b.sms_kpi_id) WHERE b.sms_kpi_pic_employee_id LIKE '%$person_id%'  AND b.sms_objective_id = $objid";
				$resultactionplan = $db->query($sqlactionplan);
			
					$ret .= "<table>";
					
					while(list($id_ap,$ap_lower_perform)=$db->fetchRow($resultactionplan)){
							if($ap_lower_perform == '')
							{ $ap_lower_perform = 'Empty';	}
							else
							{	$ap_lower_perform = $ap_lower_perform; }
							$ret .="<tr>"
								   ."<td><span class='xlnk' onclick='edit_target_text(\"$id_ap\",6,this,event);'>$ap_lower_perform</span></td>"
									."</tr>";
							}
					$ret .="</table>";		
					$ret .="</td>";
				 
				 #NEED IMPROVEMENT
					$ret .= "<td style='white-space:pre-wrap;vertical-align:top;border-right:1px solid #bbb;'>";
							
				$sqlactionplan = "SELECT a.sms_action_plan_id,a.ap_need_improvement FROM sms_action_plan a JOIN sms_kpi b ON(a.sms_kpi_id =  b.sms_kpi_id) WHERE b.sms_kpi_pic_employee_id LIKE '%$person_id%'  AND b.sms_objective_id = $objid";
				$resultactionplan = $db->query($sqlactionplan);
			
					$ret .= "<table>";
					
					while(list($id_ap,$ap_need_improvement)=$db->fetchRow($resultactionplan)){
							if($ap_need_improvement == '')
							{ $ap_need_improvement = 'Empty';	}
							else
							{	$ap_need_improvement = $ap_need_improvement; }
							$ret .="<tr>"
								   ."<td><span class='xlnk' onclick='edit_target_text(\"$id_ap\",7,this,event);'>$ap_need_improvement</span></td>"
									."</tr>";
							}
					$ret .="</table>";		
					$ret .="</td>";
					
					#TARGET
					$ret .= "<td style='white-space:pre-wrap;vertical-align:top;border-right:1px solid #bbb;'>";
							
				$sqlactionplan = "SELECT b.sms_kpi_id,b.sms_kpi_target_text FROM sms_action_plan a JOIN sms_kpi b ON(a.sms_kpi_id =  b.sms_kpi_id) WHERE b.sms_kpi_pic_employee_id LIKE '%$person_id%'  AND b.sms_objective_id = $objid";
				$resultactionplan = $db->query($sqlactionplan);
			
					$ret .= "<table>";
					
					while(list($sms_kpi_id,$sms_kpi_target_text)=$db->fetchRow($resultactionplan)){
							if($sms_kpi_target_text == '')
							{ $sms_kpi_target_text = 'Empty';	}
							else
							{	$sms_kpi_target_text = $sms_kpi_target_text; }
							$ret .="<tr>"
								   ."<td><span class='xlnk' onclick='edit_target_text(\"$sms_kpi_id\",8,this,event);'>$sms_kpi_target_text</span></td>"
									."</tr>";
							}
					$ret .="</table>";		
					$ret .="</td>";
					
					#REQ PER
					$ret .= "<td style='white-space:pre-wrap;vertical-align:top;border-right:1px solid #bbb;'>";
							
				$sqlactionplan = "SELECT a.sms_action_plan_id,a.ap_req FROM sms_action_plan a JOIN sms_kpi b ON(a.sms_kpi_id =  b.sms_kpi_id) WHERE b.sms_kpi_pic_employee_id LIKE '%$person_id%'  AND b.sms_objective_id = $objid";
				$resultactionplan = $db->query($sqlactionplan);
			
					$ret .= "<table>";
					
					while(list($id_ap,$ap_req)=$db->fetchRow($resultactionplan)){
							if($ap_req == '')
							{ $ap_req = 'Empty';	}
							else
							{	$ap_req = $ap_req; }
							$ret .="<tr>"
								   ."<td><span class='xlnk' onclick='edit_target_text(\"$id_ap\",9,this,event);'>$ap_req</span></td>"
									."</tr>";
							}
					$ret .="</table>";		
					$ret .="</td>";
					
					#FAR REQ PER
					$ret .= "<td style='white-space:pre-wrap;vertical-align:top;border-right:1px solid #bbb;'>";
							
				$sqlactionplan = "SELECT a.sms_action_plan_id,a.ap_far_req FROM sms_action_plan a JOIN sms_kpi b ON(a.sms_kpi_id =  b.sms_kpi_id) WHERE b.sms_kpi_pic_employee_id LIKE '%$person_id%'  AND b.sms_objective_id = $objid";
				$resultactionplan = $db->query($sqlactionplan);
			
					$ret .= "<table>";
					
					while(list($id_ap,$ap_far_req)=$db->fetchRow($resultactionplan)){
							if($ap_far_req == '')
							{ $ap_far_req = 'Empty';	}
							else
							{	$ap_far_req = $ap_far_req; }
							$ret .="<tr>"
								   ."<td><span class='xlnk' onclick='edit_target_text(\"$id_ap\",10,this,event);'>$ap_far_req</span></td>"
									."</tr>";
							}
					$ret .="</table>";		
					$ret .="</td>";
					
					
					#EDIT
				
			
				
				$wgt1 = $objective_weight * 59/100;
				
				$wgt2 = $objective_weight * 69/100;
				
				$wgt3 = $objective_weight * 79/100;
				
				$wgt4 = $objective_weight * 89/100;
				
				$wgt5 = $objective_weight * 100/100;

					$ret	.= "</tr>";
      $ret .= "<tr>"
					."<td style='border-right:1px solid #bbb;text-align:right;'><span class='xlnk' onclick='edit_target_text(\"$objid\",5,this,event);'>$objective_weight</span> %</td>"
					
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
		$sqlksf = "SELECT SUM(DISTINCT a.objective_weight) 
		FROM sms_objective a
		LEFT JOIN sms_session b ON ( a.psid = b.id )
		LEFT JOIN sms_objective_ksf d ON ( a.id = d.id_objective )
		LEFT JOIN sms_objective_team c ON ( c.id_objective_ksf = d.id )
		WHERE a.psid = $psid
		AND c.id_pic =$person_id";
		$resultksf = $db->query($sqlksf);
	list($sumwgtksf)=$db->fetchRow($resultksf);
	
		
	#SUM KPI
		
	$sqlkpi = "SELECT SUM(DISTINCT a.objective_weight) 
		FROM sms_objective a
		LEFT JOIN sms_session b ON ( a.psid = b.id )
		LEFT JOIN sms_kpi d ON ( a.id = d.sms_objective_id )
		LEFT JOIN sms_action_plan c ON ( c.sms_kpi_id = d.sms_kpi_id )
		WHERE a.psid = $psid
		AND c.sms_action_plan_pic_employee_id LIKE '%$person_id%'";
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
      
      $sql = "SELECT c.job_nm,c.job_abbr,d.org_nm,d.org_abbr,a.employee_ext_id,e.person_nm,e.person_id"
           . " FROM ".XOCP_PREFIX."employee a"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job b ON b.employee_id = a.employee_id AND b.job_id = '$job_id'"
           . " LEFT JOIN ".XOCP_PREFIX."jobs c ON c.job_id = '$job_id'"
           . " LEFT JOIN ".XOCP_PREFIX."orgs d ON d.org_id = c.org_id"
           . " LEFT JOIN ".XOCP_PREFIX."persons e ON e.person_id = a.person_id"
           . " WHERE a.employee_id = '$employee_id'";
      $result = $db->query($sql);
      list($job_nm,$job_abbr,$org_nm,$org_abbr,$nip,$employee_nm,$person_id)=$db->fetchRow($result);
      
      $sql = "SELECT a.job_nm,b.employee_ext_id,c.person_nm,b.employee_id"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job e USING(job_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " WHERE a.job_id = '$first_assessor_job_id'"
           . " ORDER BY e.start_dttm DESC LIMIT 1";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($first_superior_job,$nip,$first_superior_name,$first_assessor_employee_id)=$db->fetchRow($result);
      }
      
      $sql = "SELECT a.job_nm,b.employee_ext_id,c.person_nm,b.employee_id"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job e USING(job_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " WHERE a.job_id = '$next_assessor_job_id'"
           . " ORDER BY e.start_dttm DESC LIMIT 1";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($next_superior_job,$nip,$next_superior_name,$next_assessor_employee_id)=$db->fetchRow($result);
      }
      
      $form .= "<div style='text-align:right;padding:10px;margin-top:20px;'>"
             //. $all_jam_status . $employee_id
             . "<table align='center' style='border-top:2px solid #777;border-left:2px solid #777;border-spacing:0px;'>"
             . "<colgroup>"
             . "<col width='200'/>"
             . "<col width='200'/>"
             . "<col width='200'/>"
             . "</colgroup>"
             . "<tbody>"
             . "<tr>"
             . "<td style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;'>"
             . "Submited by,"
             . "</td>"
             . "<td ".($doubleapproval==1?"colspan='2'":"")." style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;'>"
             . "Approved by,"
             . "</td>"
             . "</tr>"
             . "<tr>"
             . "<td style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
             . getUserFullname()
             . "</td>"
             . "<td style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
             . "$first_superior_name"
             . "</td>"
             
             . ($doubleapproval==1?""
             . "<td style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
             . "$next_superior_name"
             . "</td>"
             . "":"")
             
             . "</tr>"
             . "<tr>"
             . "<td style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
             . "Employee, PIC"
             . "</td>"
             . "<td style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
             . "$first_superior_job"
             . "</td>"
             
             . ($doubleapproval==1?""
             . "<td style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
             . "$next_superior_job"
             . "</td>"
             . "":"")
             
             . "</tr>"
             . "<tr>"
             . "<td style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;padding:10px;'>"

             . "</td>"
             . "<td style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;padding:10px;'>"
         
             . "</td>"
             
         
             . "<td style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;padding:10px;'>"
  
             . "</td>"
           
             
             . "</tr>"
           . "</tbody>"
           . "</table>"
           . "</div>";
				    
			
      
      
    $js = $ajax->getJs()."\n<script type='text/javascript'><!--
      
    ////////////////////////////
      function save_target_text(id_ksf,no) {
         var val = trim($('inp_target_text').value);
         if(dvedittargettext) {
            dvedittargettext.d.innerHTML = val;
         }
         orgjx_app_saveJAMTargetText(val,id_ksf,no,null);
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
            save_target_text(dvedittargettext.id_ksf,dvedittargettext.no);
         } else if (k==27) {
            _destroy(dvedittargettext);
            dvedittargettext.d = null;
            dvedittargettext = null;
         } else {
            d.chgt = new ctimer('save_target_text(\"'+dvedittargettext.id_ksf+'\",\"'+dvedittargettext.no+'\");',300);
            d.chgt.start();
         }
      }
      
      function close_target_text() {
         document.body.onclick = null;
         _destroy(dvedittargettext);
         dvedittargettext.d = null;
         dvedittargettext = null;
         return;
      }
      
      var dvedittargettext = null;
      function edit_target_text(id_ksf,no,d,e) {
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
         d.dv.innerHTML = '<div style=\"text-align:left;padding:2px;\">Target :<br/>'
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
         dvedittargettext.id_ksf = id_ksf;
         dvedittargettext.no = no;
         //setTimeout('document.body.onclick = function() { document.body.onclick = null; _destroy(dvedittargettext); };',100);
      }
      
      ////////////////////////////
      
      // --></script>";
      
      return $ret.$form.$tooltip.$js;
   }
   
   function main() {
      $antrainselses = new _sms_class_SelectSession();
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
            $ret = $this->smsmatrixjam($self_employee_id);
            break;
         default:
            $ret = $this->smsmatrixjam($self_employee_id);
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