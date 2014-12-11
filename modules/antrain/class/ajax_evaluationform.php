<?php
//--------------------------------------------------------------------//
// Filename : modules/antrain/class/ajax_antrainsession.php                  //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-12-17                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_ANTRAINREQSESSIONAJAX_DEFINED') ) {
   define('HRIS_ANTRAINREQSESSIONAJAX_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT.'/config.php');
require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");
require_once(XOCP_DOC_ROOT."/modules/antrain/modconsts.php");



class _antrain_class_ANTRAINevformajax extends AjaxListener {
   
   function __construct($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/antrain/class/ajax_evaluationform.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_Delete","app_editSession","app_viewSession","app_saveSession",
                                             "app_setANTRAINReqsessions","app_newSession");
   }
   
   function app_setANTRAINReqsessions($args) {
      $_SESSION["antrain_psid"] = $args[0];
   }
   
   function app_newSession($args) {
      $db=&Database::getInstance();
      // $sql = "SELECT MAX(psid) FROM antrain_evaluationform ORDER BY year DESC " ;
	  $sql = "SELECT psid FROM antrain_evaluationform WHERE psid = ( SELECT MAX( psid ) FROM antrain_evaluationform ) ORDER BY YEAR " ;
     $result = $db->query($sql);
      list($psidx)=$db->fetchRow($result);
      $psid = $psidx+1;
      $Institution = 'EMPTY';
	  $contact_person = addslashes(trim($vars["contact_person"]));	  
	  $subject = addslashes(trim($vars["subject"]));	
	  $id_created = getUserID();
	  $date_created = getSQLDate();
	  $sql = "INSERT INTO antrain_evaluationform (psid,institution,contact_person,subject,id_created,date_created)"
           . " VALUES('$psid','$institution','$contact_person','$subject','$id_created','$date_created')";
      $db->query($sql);
	  
	  $num  = $num+1;
	
	// LINKED/// 
	//<span id='sp_${psid}' class='xlnk' onclick='edit_session(\"$psid\",this,event); '>".htmlentities(stripslashes($institution))."</span>
	///////////
     $hdr = "<table><colgroup><col width='60'/><col/></colgroup><tbody><tr>
				<td width=5 style='text-align: center;' >
						<span id='no_${psid}'  '>$num</span>
					</td>"
				 . "<td width=175 style='text-align: left;' >
						<span id='sp_${psid}' class='xlnk' onclick='edit_session(\"$psid\",this,event); '>".htmlentities(stripslashes($institution))."</span>
					</td>"
				  . "<td id='td_cp_${psid}' width=200 style='text-align: left;'>
						$contact_person
					</td>"
				  . "<td id='td_subject_${psid}' width=200 style='text-align: left;'>
						$subject
					</td>"
			  . "</tr></tbody></table>";
      return array($psid,$hdr);
   }
   
   function app_saveSession($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $psid = $args[0];
      $vars = parseForm($args[1]);
	  $institution = addslashes(trim($vars["institution"])); 
	  $address = addslashes(trim($vars["address"])); 
	  $contact_person = addslashes(trim($vars["contact_person"])); 
	  $phone = addslashes(trim($vars["phone"])); 
	  $subject = addslashes(trim($vars["subject"])); 
	  $num_participant = _bctrim(bcadd(0,$vars["num_participant"]));
	  $budget = _bctrim(bcadd(0,$vars["budget"]));
	  $duration = _bctrim(bcadd(0,$vars["duration"]));
	  $focus = addslashes(trim($vars["focus"])); 
	  $objective = addslashes(trim($vars["objective"])); 
	  $rate_analysis = _bctrim(bcadd(0,$vars["rate_analysis"]));
	  $rate_inprof = _bctrim(bcadd(0,$vars["rate_inprof"]));
	  $rate_tailor = _bctrim(bcadd(0,$vars["rate_tailor"]));
	  $rate_ref = _bctrim(bcadd(0,$vars["rate_ref"]));
	  $rate_applicable = _bctrim(bcadd(0,$vars["rate_applicable"]));
	  $rate_fee = _bctrim(bcadd(0,$vars["rate_fee"]));
	  $rate_teachmethod = _bctrim(bcadd(0,$vars["rate_teachmethod"]));
	  $rate_paymethod = _bctrim(bcadd(0,$vars["rate_paymethod"]));
	  $rate_insability = _bctrim(bcadd(0,$vars["rate_insability"]));
	  $rate_adm = _bctrim(bcadd(0,$vars["rate_adm"]));
	  $rate_evalprogram = _bctrim(bcadd(0,$vars["rate_evalprogram"]));
	  $rate_train = _bctrim(bcadd(0,$vars["rate_train"]));
	  $rate_followup = _bctrim(bcadd(0,$vars["rate_followup"]));
	  $rate_other = _bctrim(bcadd(0,$vars["rate_other"]));
	  $rate_other_inp =  addslashes(trim($vars["rate_other_inp"])); 
	  $reason = addslashes(trim($vars["reason"])); 
	  $reason_expertise = _bctrim(bcadd(0,$vars["reason_expertise"]));
	  $reason_fee = _bctrim(bcadd(0,$vars["reason_fee"]));
	  $reason_duration = _bctrim(bcadd(0,$vars["reason_duration"]));
	  $presenter_name = addslashes(trim($vars["presenter_name"])); 
	  $presenter_pos = addslashes(trim($vars["presenter_pos"])); 
	  $presenter_date = addslashes(trim($vars["presenter_date"]));
	  $eval_name = addslashes(trim($vars["eval_name"])); 
	  $eval_pos = addslashes(trim($vars["eval_pos"])); 
	  $eval_date = addslashes(trim($vars["eval_date"]));
	  $focus_knowledge = _bctrim(bcadd(0,$vars["focus_knowledge"]));
	  $focus_skills = _bctrim(bcadd(0,$vars["focus_skills"]));
	  $focus_attitude = _bctrim(bcadd(0,$vars["focus_attitude"]));
	  $focus_motivation = _bctrim(bcadd(0,$vars["focus_motivation"]));
	  $focus_other = _bctrim(bcadd(0,$vars["focus_other"]));
      // $year = _bctrim(bcadd(0,$vars["year"]));
	  // $budget = _bctrim(bcadd(0,$vars["budget"]));
	  // $org_id = _bctrim(bcadd(0,$vars["org_id"]));
	  // $org_id_sec = _bctrim(bcadd(0,$vars["org_id_sec"]));
	  // $remark = addslashes(trim($vars["remark"]));
	  $id_created = getUserID();
	  $id_modified = getUserID();
	  $date_created = getSQLDate($vars["date_created"]);
	  $date_modified =  getSQLDate($vars["date_modified"]);
	
	  //$org_nm = addslashes(trim($vars["org_nm"]));
	  
      if($psid=="new") {
 
		$sql = "SELECT psid, institution, address, contact_person, phone, subject, num_participant, budget, duration, focus, objective, rate_analysis, rate_inprof, rate_tailor, rate_ref, rate_applicable, rate_fee, rate_teachmethod, rate_paymethod, rate_insability, rate_adm, rate_evalprogram, rate_train, rate_followup, rate_other, rate_other_inp, reason,reason_expertise,reason_fee,reason_duration, presenter_name, presenter_pos, presenter_date, eval_name, eval_pos, eval_date, date_created, date_modified FROM antrain_evaluationform WHERE psid = ( SELECT MAX( psid ) FROM antrain_evaluationform ) ORDER BY YEAR" ;
	         
	     //$sql =  "SELECT MAX(psid) FROM antrain_evaluationform ORDER BY year DESC " ;
         $result = $db->query($sql);
         list($psidx)=$db->fetchRow($result);
         $psid = $psidx+1;
         // $user_id = getUserID();
		 // $date_created = getSQLDate();
		// $org_nm = $org_nmx;
         $sql = "INSERT INTO antrain_evaluationform (psid,institution,address, contact_person,phone, subject, num_participant, budget, duration, focus, objective, rate_analysis, rate_inprof, rate_tailor, rate_ref, rate_applicable,rate_fee,rate_teachmethod,rate_paymethod,rate_insability,rate_adm,rate_evalprogram,rate_train,rate_followup,rate_other,rate_other_inp,reason,reason_expertise,reason_fee,reason_duration,presenter_name,presenter_pos,presenter_date,eval_name,eval_pos,eval_date,status_cd, id_created,date_created,focus_knowledge,focus_skills,focus_attitude,focus_motivation,focus_other) VALUES('$psid','$institution','$address','$contact_person','$phone','$subject','$num_participant','$budget','$duration','$focus','$objective','$rate_analysis','$rate_inprof','$rate_tailor','$rate_ref','$rate_applicable','$rate_fee','$rate_teachmethod','$rate_paymethod','$rate_insability','$rate_adm','$rate_evalprogram','$rate_train','$rate_followup','$rate_other','$rate_other_inp','$reason','$reason_expertise','$reason_fee','$reason_duration',$presenter_name','$presenter_pos','$presenter_date','$eval_name','$eval_pos','$eval_date','$status_cd','$id_created','$date_created','$focus_knowledge','$focus_skills','$focus_attitude','$focus_motivation','$focus_other')";
         $db->query($sql);
      } else {
         $sql = "UPDATE antrain_evaluationform SET institution = '$institution',address = '$address', contact_person = '$contact_person',phone = '$phone', subject = '$subject', num_participant ='$num_participant', budget = '$budget', duration = '$duration', focus = '$focus', objective = '$objective', rate_analysis = '$rate_analysis', rate_inprof = '$rate_inprof', rate_tailor = '$rate_tailor', rate_ref = '$rate_ref', rate_applicable = '$rate_applicable',rate_fee = '$rate_fee',rate_teachmethod = '$rate_teachmethod',rate_paymethod = '$rate_paymethod',rate_insability = '$rate_insability',rate_adm = '$rate_adm',rate_evalprogram = '$rate_evalprogram',rate_train = '$rate_train',rate_followup = '$rate_followup' ,rate_other = '$rate_other',rate_other_inp = '$rate_other_inp',reason = '$reason',reason_expertise = '$reason_expertise',reason_fee = '$reason_fee',reason_duration = '$reason_duration',presenter_name = '$presenter_name',presenter_pos = '$presenter_pos',presenter_date = '$presenter_date',eval_name = '$eval_name',eval_pos = '$eval_pos',eval_date = '$eval_date', id_modified = '$id_modified', date_modified = '$date_modified', focus_knowledge = '$focus_knowledge',focus_skills = '$focus_skills',focus_attitude = '$focus_attitude',focus_motivation = '$focus_motivation',focus_other ='$focus_other' WHERE psid = '$psid'";
         $db->query($sql);
		 
   	  }
      
	   return array($psid,$institution,$contact_person,$subject);
   }
   
   function app_editSession($args) {
      $db=&Database::getInstance();
      $psid = $args[0];
      if($psid=="new") {
         $date_created  = getSQLDate();
		 $id_created = getUserID();
         $generate = "";
      } else {
        $id_modified = getUserID();
		$date_modified  = getSQLDate();

		  $sql =  "SELECT institution,address, contact_person,phone, subject, num_participant, budget, duration, focus, objective, rate_analysis, rate_inprof, rate_tailor, rate_ref, rate_applicable,rate_fee,rate_teachmethod,rate_paymethod,rate_insability,rate_adm,rate_evalprogram,rate_train,rate_followup,rate_other,rate_other_inp,reason,reason_expertise,reason_fee,reason_duration,presenter_name,presenter_pos,presenter_date,eval_name,eval_pos,eval_date,focus_knowledge,focus_skills,focus_attitude,focus_motivation,focus_other FROM antrain_evaluationform WHERE psid = '$psid' ORDER BY psid DESC " ;
	
         $result = $db->query($sql);
    	 
       
	   list($institution,$address,$contact_person,$phone,$subject,$num_participant,$budget,$duration,$focus,$objective,$rate_analysis,$rate_inprof,$rate_tailor,$rate_ref,$rate_applicable,$rate_fee,$rate_teachmethod,$rate_paymethod,$rate_insability,$rate_adm,$rate_evalprogram,$rate_train,$rate_followup,$rate_other,$rate_other_inp,$reason,$reason_expertise,$reason_fee,$reason_duration,$presenter_name,$presenter_pos,$presenter_date,$eval_name,$eval_pos,$eval_date,$focus_knowledge,$focus_skills,$focus_attitude,$focus_motivation,$focus_other)=$db->fetchRow($result);
        $institution = htmlentities($institution,ENT_QUOTES);
		
		//$presenter_date = date('d/M/Y', strtotime($presenter_date));
		//$eval_date = date('d/M/Y', strtotime($eval_date));
		
		
		
		/* if($focus == 'Knowledge')
		{
			$checkedknowledge = 'checked';
		}
		else
		{
			$checkedknowledge = '';
		}
		
		if($focus == 'Skills')
		{
			$checkedskills = 'checked';
		}
		else
		{
			$checkedskills = ''; 
		}
		
		if($focus == 'Attitude')
		{
		$checkedattitude = 'checked';
		}
		else
		{
		$checkedattitude = '';
		}

		if($focus == 'Motivation')
		{
			$checkedmotivation = 'checked';
		}
		else
		{
		$checkedmotivation = '';
		}
		
		if($focus == 'Other')
		{
		$checkedother = 'checked';
		}
		else
		{
		$checkedother = '';
		} */
			
	 } 
	 
		
									  
      $ret = "<form id='frm'><table class='xxfrm' style='width:100%;'><colgroup><col width='160'/><col/></colgroup><tbody>"
         
           . "<tr><td style='text-align:left;'> &nbsp Institution<span style='float:right';>:</span></td><td><input type='text' value=\"$institution\" id='inp_institution' name='institution' style='width:200px;'/></td></tr>" 
		   
		   . "<tr><td style='text-align:left;'> &nbsp Address<span style='float:right';>:</span></td><td><input type='text' value=\"$address\" id='inp_address' name='address' style='width:200px;'/></td></tr>"
		   
		   . "<tr><td style='text-align:left;'> &nbsp Contact Person<span style='float:right';>:</span></td><td><input type='text' value=\"$contact_person\" id='inp_contact_person' name='contact_person' style='width:100px;'/></td></tr>"
		   
		   . "<tr><td style='text-align:left;'> &nbsp Phone<span style='float:right';>:</span></td><td><input type='text' value=\"$phone\" id='inp_phone' name='phone' style='width:100px;'/></td></tr>"

		   . "<tr><td style='text-align:left;'> &nbsp Training/Seminar Subject<span style='float:right';>:</span></td><td><input type='text' value=\"$subject\" id='inp_subject' name='subject' style='width:200px;'/></td></tr>"
		   
		   . "<tr><td style='text-align:left;'> &nbsp Number of participant<span style='float:right';>:</span></td><td><input type='text' value=\"$num_participant\" id='inp_num_participant' name='num_participant' style='width:50px;'/></td></tr>"
		   
		  . "<tr><td style='text-align:left;'> &nbsp Approximate Budget<span style='float:right';>:</span></td><td><input type='text' value=\"$budget\" id='inp_budget' name='budget' style='width:50px;'/></td></tr>" 
		 
		 . "<tr><td style='text-align:left;'> &nbsp Duration<span style='float:right';>:</span></td><td><input type='text' value=\"$duration\" id='inp_duration' name='duration' style='width:50px;'/></td></tr>" 
		
		/*  . "<tr><td style='text-align:left;'> &nbsp Focus on Improvement<span style='float:right';>:</span></td>
				<td>
					<input type='radio' name='focus' value='Knowledge' $checkedknowledge>Knowledge
					<br>
					<input type='radio' name='focus' value='Skills' $checkedskills>Skills
					<br>
					<input type='radio' name='focus' value='Attitude' $checkedattitude>Attitude
					<br>
					<input type='radio' name='focus' value='Motivation' $checkedmotivation>Motivation
					<br>
					<input type='radio' name='focus' value='Other' $checkedother>Other
				</td>
			</tr>" */ 
			. "<tr><td style='text-align:left;'> &nbsp Focus on Improvement<span style='float:right';>:</span></td>
				<td>";
				if($focus_knowledge == 1){ $selectedknw = 'checked';} else { $selectedknw = '';}
				if($focus_skills == 1){ $selectedskl = 'checked';} else { $selectedskl = '';}
				if($focus_attitude == 1){ $selectedatt = 'checked';} else { $selectedatt = '';}
				if($focus_motivation == 1){ $selectedmtv = 'checked';} else { $selectedmtv = '';}
				if($focus_other == 1){ $selectedoth = 'checked';} else { $selectedoth = '';}
			 $ret .= "
			 <input type='checkbox' value='1' id='inp_focus_knowledge' name='focus_knowledge' $selectedknw />Knowledge<br/>
			 <input type='checkbox' value='1' id='inp_focus_skills' name='focus_skills' $selectedskl />Skills<br/>
			 <input type='checkbox' value='1' id='inp_focus_attitude' name='focus_attitude' $selectedatt />Attitude<br/>
			 <input type='checkbox' value='1' id='inp_focus_motivation' name='focus_motivation' $selectedmtv />Motivation<br/>
			 <input type='checkbox' value='1' id='inp_focus_other' name='focus_other' $selectedoth />Other<br/>
				</td>
			</tr>"

			. "<tr><td style='text-align:left;'> &nbsp Objective of Training/Seminar<span style='float:right';>:</span></td><td><input type='text' value=\"$objective\" id='inp_objective' name='objective' style='width:450px;'/></td></tr>" 
			
			//RATING
			//1
			
			. "<tr>
					<td style='text-align:left;'>
						&nbsp Rating of  Institution to be Considered &nbsp <br/> &nbsp (1 - 5,5 is the best score)<span style='float:right';>:</span>
					</td>
					<td>
						<div style='float:left; width:300px;'>
							<select id='inp_rate_analysis' name='rate_analysis'>
								";
								 $num1 = 1;
								 $num2 = 2;
								 $num3 = 3;
								 $num4 = 4;
								 $num5 = 5;
								if($rate_analysis == $num1){ $selected1 = 'selected';} else { $selected1 = '';}
								if($rate_analysis == $num2){ $selected2 = 'selected';} else { $selected2 = '';}
								if($rate_analysis == $num3){ $selected3 = 'selected';} else { $selected3 = '';}
								if($rate_analysis == $num4){ $selected4 = 'selected';} else { $selected4 = '';}
								if($rate_analysis == $num5){ $selected5 = 'selected';} else { $selected5 = '';}
								 $ret .= "<option value='$num1' $selected1>$num1</option>
									<option value='$num2' $selected2>$num2</option>
									<option value='$num3' $selected3>$num3</option>
									<option value='$num4' $selected4>$num4</option>
									<option value='$num5' $selected5>$num5</option>
							</select> &nbsp Needs Analysis
						</div>
							<select id='inp_rate_analysis' name='rate_analysis'> 
								";
								 $num1 = 1;
								 $num2 = 2;
								 $num3 = 3;
								 $num4 = 4;
								 $num5 = 5;
								if($rate_analysis == $num1){ $selected1 = 'selected';} else { $selected1 = '';}
								if($rate_analysis == $num2){ $selected2 = 'selected';} else { $selected2 = '';}
								if($rate_analysis == $num3){ $selected3 = 'selected';} else { $selected3 = '';}
								if($rate_analysis == $num4){ $selected4 = 'selected';} else { $selected4 = '';}
								if($rate_analysis == $num5){ $selected5 = 'selected';} else { $selected5 = '';}
								 $ret .= "<option value='$num1' $selected1>$num1</option>
									<option value='$num2' $selected2>$num2</option>
									<option value='$num3' $selected3>$num3</option>
									<option value='$num4' $selected4>$num4</option>
									<option value='$num5' $selected5>$num5</option>
							</select>&nbsp Institution Profile; Years in business,owner,etc

					</td>
					
				</tr>"
			 
			 //2
				. "<tr>
					<td style='text-align:left;'>
					</td>
					<td>
						<div style='float:left; width:300px;'>
							<select id='inp_rate_tailor' name='rate_tailor'> 
									";
									 $num1 = 1;
									 $num2 = 2;
									 $num3 = 3;
									 $num4 = 4;
									 $num5 = 5;
									if($rate_tailor == $num1){ $selected1 = 'selected';} else { $selected1 = '';}
									if($rate_tailor == $num2){ $selected2 = 'selected';} else { $selected2 = '';}
									if($rate_tailor == $num3){ $selected3 = 'selected';} else { $selected3 = '';}
									if($rate_tailor == $num4){ $selected4 = 'selected';} else { $selected4 = '';}
									if($rate_tailor == $num5){ $selected5 = 'selected';} else { $selected5 = '';}
									 $ret .= "<option value='$num1' $selected1>$num1</option>
										<option value='$num2' $selected2>$num2</option>
										<option value='$num3' $selected3>$num3</option>
										<option value='$num4' $selected4>$num4</option>
										<option value='$num5' $selected5>$num5</option>
								</select> &nbsp Tailor made contents					
						</div>
							<select id='inp_rate_ref' name='rate_ref'> 
									";
									 $num1 = 1;
									 $num2 = 2;
									 $num3 = 3;
									 $num4 = 4;
									 $num5 = 5;
									if($rate_ref == $num1){ $selected1 = 'selected';} else { $selected1 = '';}
									if($rate_ref == $num2){ $selected2 = 'selected';} else { $selected2 = '';}
									if($rate_ref == $num3){ $selected3 = 'selected';} else { $selected3 = '';}
									if($rate_ref == $num4){ $selected4 = 'selected';} else { $selected4 = '';}
									if($rate_ref == $num5){ $selected5 = 'selected';} else { $selected5 = '';}
									 $ret .= "<option value='$num1' $selected1>$num1</option>
										<option value='$num2' $selected2>$num2</option>
										<option value='$num3' $selected3>$num3</option>
										<option value='$num4' $selected4>$num4</option>
										<option value='$num5' $selected5>$num5</option>
								</select> &nbsp Reference from other companies
					</td>
				   </tr>"
				
				//3
				. "<tr>
						<td style='text-align:left;'>
						</td>
						<td>
							<div style='float:left; width:300px;'>
							<select id='inp_rate_applicable' name='rate_applicable'> 
									";
									 $num1 = 1;
									 $num2 = 2;
									 $num3 = 3;
									 $num4 = 4;
									 $num5 = 5;
									if($rate_applicable == $num1){ $selected1 = 'selected';} else { $selected1 = '';}
									if($rate_applicable == $num2){ $selected2 = 'selected';} else { $selected2 = '';}
									if($rate_applicable == $num3){ $selected3 = 'selected';} else { $selected3 = '';}
									if($rate_applicable == $num4){ $selected4 = 'selected';} else { $selected4 = '';}
									if($rate_applicable == $num5){ $selected5 = 'selected';} else { $selected5 = '';}
									 $ret .= "<option value='$num1' $selected1>$num1</option>
										<option value='$num2' $selected2>$num2</option>
										<option value='$num3' $selected3>$num3</option>
										<option value='$num4' $selected4>$num4</option>
										<option value='$num5' $selected5>$num5</option>
								</select>  &nbsp Applicable contents
							</div>
								<select id='inp_rate_fee' name='rate_fee'> 
									";
									 $num1 = 1;
									 $num2 = 2;
									 $num3 = 3;
									 $num4 = 4;
									 $num5 = 5;
									if($rate_fee == $num1){ $selected1 = 'selected';} else { $selected1 = '';}
									if($rate_fee == $num2){ $selected2 = 'selected';} else { $selected2 = '';}
									if($rate_fee == $num3){ $selected3 = 'selected';} else { $selected3 = '';}
									if($rate_fee == $num4){ $selected4 = 'selected';} else { $selected4 = '';}
									if($rate_fee == $num5){ $selected5 = 'selected';} else { $selected5 = '';}
									 $ret .= "<option value='$num1' $selected1>$num1</option>
										<option value='$num2' $selected2>$num2</option>
										<option value='$num3' $selected3>$num3</option>
										<option value='$num4' $selected4>$num4</option>
										<option value='$num5' $selected5>$num5</option>
								</select>  &nbsp Training/seminar fee
						</td>
					</tr>"
					
							//4
				. "<tr>
						<td style='text-align:left;'>
						</td>
						<td>
							<div style='float:left; width:300px;'>
								<select id='inp_rate_teachmethod' name='rate_teachmethod'> 
									";
									 $num1 = 1;
									 $num2 = 2;
									 $num3 = 3;
									 $num4 = 4;
									 $num5 = 5;
									if($rate_teachmethod == $num1){ $selected1 = 'selected';} else { $selected1 = '';}
									if($rate_teachmethod == $num2){ $selected2 = 'selected';} else { $selected2 = '';}
									if($rate_teachmethod == $num3){ $selected3 = 'selected';} else { $selected3 = '';}
									if($rate_teachmethod == $num4){ $selected4 = 'selected';} else { $selected4 = '';}
									if($rate_teachmethod == $num5){ $selected5 = 'selected';} else { $selected5 = '';}
									 $ret .= "<option value='$num1' $selected1>$num1</option>
										<option value='$num2' $selected2>$num2</option>
										<option value='$num3' $selected3>$num3</option>
										<option value='$num4' $selected4>$num4</option>
										<option value='$num5' $selected5>$num5</option>
								</select>  &nbsp Method of teaching					
							</div>
								<select id='inp_rate_paymethod' name='rate_paymethod'> 
									";
									 $num1 = 1;
									 $num2 = 2;
									 $num3 = 3;
									 $num4 = 4;
									 $num5 = 5;
									if($rate_paymethod == $num1){ $selected1 = 'selected';} else { $selected1 = '';}
									if($rate_paymethod == $num2){ $selected2 = 'selected';} else { $selected2 = '';}
									if($rate_paymethod == $num3){ $selected3 = 'selected';} else { $selected3 = '';}
									if($rate_paymethod == $num4){ $selected4 = 'selected';} else { $selected4 = '';}
									if($rate_paymethod == $num5){ $selected5 = 'selected';} else { $selected5 = '';}
									 $ret .= "<option value='$num1' $selected1>$num1</option>
										<option value='$num2' $selected2>$num2</option>
										<option value='$num3' $selected3>$num3</option>
										<option value='$num4' $selected4>$num4</option>
										<option value='$num5' $selected5>$num5</option>
								</select>  &nbsp Payment method			
						</td>
					</tr>"
					
							//5
				. "<tr>
						<td style='text-align:left;'>
						</td>
						<td>
							<div style='float:left; width:300px;'>
								<select id='inp_rate_insability' name='rate_insability'> 
									";
									 $num1 = 1;
									 $num2 = 2;
									 $num3 = 3;
									 $num4 = 4;
									 $num5 = 5;
									if($rate_insability == $num1){ $selected1 = 'selected';} else { $selected1 = '';}
									if($rate_insability == $num2){ $selected2 = 'selected';} else { $selected2 = '';}
									if($rate_insability == $num3){ $selected3 = 'selected';} else { $selected3 = '';}
									if($rate_insability == $num4){ $selected4 = 'selected';} else { $selected4 = '';}
									if($rate_insability == $num5){ $selected5 = 'selected';} else { $selected5 = '';}
									 $ret .= "<option value='$num1' $selected1>$num1</option>
										<option value='$num2' $selected2>$num2</option>
										<option value='$num3' $selected3>$num3</option>
										<option value='$num4' $selected4>$num4</option>
										<option value='$num5' $selected5>$num5</option>
								</select>  &nbsp Instructor's ability
							</div>
								<select id='inp_rate_adm' name='rate_adm'> 
									";
									 $num1 = 1;
									 $num2 = 2;
									 $num3 = 3;
									 $num4 = 4;
									 $num5 = 5;
									if($rate_adm == $num1){ $selected1 = 'selected';} else { $selected1 = '';}
									if($rate_adm == $num2){ $selected2 = 'selected';} else { $selected2 = '';}
									if($rate_adm == $num3){ $selected3 = 'selected';} else { $selected3 = '';}
									if($rate_adm == $num4){ $selected4 = 'selected';} else { $selected4 = '';}
									if($rate_adm == $num5){ $selected5 = 'selected';} else { $selected5 = '';}
									 $ret .= "<option value='$num1' $selected1>$num1</option>
										<option value='$num2' $selected2>$num2</option>
										<option value='$num3' $selected3>$num3</option>
										<option value='$num4' $selected4>$num4</option>
										<option value='$num5' $selected5>$num5</option>
								</select>  &nbsp Administration matter,contract,certificate, etc.
						</td>
					</tr>"
					
							//6
				. "<tr>
						<td style='text-align:left;'>
						</td>
						<td>
							<div style='float:left; width:300px;'>
							     <select id='inp_rate_evalprogram' name='rate_evalprogram'> 
									";
									 $num1 = 1;
									 $num2 = 2;
									 $num3 = 3;
									 $num4 = 4;
									 $num5 = 5;
									if($rate_evalprogram == $num1){ $selected1 = 'selected';} else { $selected1 = '';}
									if($rate_evalprogram == $num2){ $selected2 = 'selected';} else { $selected2 = '';}
									if($rate_evalprogram == $num3){ $selected3 = 'selected';} else { $selected3 = '';}
									if($rate_evalprogram == $num4){ $selected4 = 'selected';} else { $selected4 = '';}
									if($rate_evalprogram == $num5){ $selected5 = 'selected';} else { $selected5 = '';}
									 $ret .= "<option value='$num1' $selected1>$num1</option>
										<option value='$num2' $selected2>$num2</option>
										<option value='$num3' $selected3>$num3</option>
										<option value='$num4' $selected4>$num4</option>
										<option value='$num5' $selected5>$num5</option>
								</select>  &nbsp Evaluation program
							</div>
							     <select id='inp_rate_train' name='rate_train'> 
									";
									 $num1 = 1;
									 $num2 = 2;
									 $num3 = 3;
									 $num4 = 4;
									 $num5 = 5;
									if($rate_train == $num1){ $selected1 = 'selected';} else { $selected1 = '';}
									if($rate_train == $num2){ $selected2 = 'selected';} else { $selected2 = '';}
									if($rate_train == $num3){ $selected3 = 'selected';} else { $selected3 = '';}
									if($rate_train == $num4){ $selected4 = 'selected';} else { $selected4 = '';}
									if($rate_train == $num5){ $selected5 = 'selected';} else { $selected5 = '';}
									 $ret .= "<option value='$num1' $selected1>$num1</option>
										<option value='$num2' $selected2>$num2</option>
										<option value='$num3' $selected3>$num3</option>
										<option value='$num4' $selected4>$num4</option>
										<option value='$num5' $selected5>$num5</option>
								</select>  &nbsp Training facility
						</td>
					</tr>"
					
							//7
				. "<tr>
						<td style='text-align:left;'>
						</td>
						<td>
							<div style='float:left; width:300px;'>
							     <select id='inp_rate_followup' name='rate_followup'> 
									";
									 $num1 = 1;
									 $num2 = 2;
									 $num3 = 3;
									 $num4 = 4;
									 $num5 = 5;
									if($rate_followup == $num1){ $selected1 = 'selected';} else { $selected1 = '';}
									if($rate_followup == $num2){ $selected2 = 'selected';} else { $selected2 = '';}
									if($rate_followup == $num3){ $selected3 = 'selected';} else { $selected3 = '';}
									if($rate_followup == $num4){ $selected4 = 'selected';} else { $selected4 = '';}
									if($rate_followup == $num5){ $selected5 = 'selected';} else { $selected5 = '';}
									 $ret .= "<option value='$num1' $selected1>$num1</option>
										<option value='$num2' $selected2>$num2</option>
										<option value='$num3' $selected3>$num3</option>
										<option value='$num4' $selected4>$num4</option>
										<option value='$num5' $selected5>$num5</option>
								</select> &nbsp Follow up action
							</div>
							   <select id='inp_rate_other' name='rate_other'> 
									";
									 $num1 = 1;
									 $num2 = 2;
									 $num3 = 3;
									 $num4 = 4;
									 $num5 = 5;
									if($rate_other == $num1){ $selected1 = 'selected';} else { $selected1 = '';}
									if($rate_other == $num2){ $selected2 = 'selected';} else { $selected2 = '';}
									if($rate_other == $num3){ $selected3 = 'selected';} else { $selected3 = '';}
									if($rate_other == $num4){ $selected4 = 'selected';} else { $selected4 = '';}
									if($rate_other == $num5){ $selected5 = 'selected';} else { $selected5 = '';}
									 $ret .= "<option value='$num1' $selected1>$num1</option>
										<option value='$num2' $selected2>$num2</option>
										<option value='$num3' $selected3>$num3</option>
										<option value='$num4' $selected4>$num4</option>
										<option value='$num5' $selected5>$num5</option>
								</select> &nbsp Other
							<input type='text' value=\"$rate_other_inp\" id='rate_other_inp' name='rate_other_inp' style='width:100px;'/>
							
						</td>
					</tr>"
			
			 . "<tr><td style='text-align:left;'> &nbsp Main reason for selecting this institution<br/> &nbsp (expertise/fee/duration) <span style='float:right';>:</span></td>
			 <td>"; 
			if($reason_expertise == 1){ $selectedexp = 'checked';} else { $selectedexp = '';}
			if($reason_fee == 1){ $selectedfee = 'checked';} else { $selectedfee = '';}
			if($reason_duration == 1){ $selecteddrt = 'checked';} else { $selecteddrt = '';}
			 $ret .= "
			 <input type='checkbox' value='1' id='inp_reason_exp' name='reason_expertise' $selectedexp />Expertise<br/>
			 <input type='checkbox' value='1' id='inp_reason_fee' name='reason_fee' $selectedfee />Fee<br/>
			 <input type='checkbox' value='1' id='inp_reason_drt' name='reason_duration' $selecteddrt />Duration<br/></td></tr>" 
			
			//PRESENTER
			 . "<tr><td style='text-align:left;'> &nbsp Presenter from Institution</td><td><div style='float:left; width:120px;'>Name <span style='float:right';>: &nbsp  </span></div><input type='text' value=\"$presenter_name\" id='inp_presenter_name' name='presenter_name' style='width:200px;'/></td></tr>" 
			 . "<tr><td style='text-align:left;'></td><td><div style='float:left; width:120px;'>Position<span style='float:right';>: &nbsp  </span></div><input type='text' value=\"$presenter_pos\" id='inp_presenter_pos' name='presenter_pos' style='width:450px;'/></td></tr>" 
			 . "<tr><td style='text-align:left;'></td><td><div style='float:left; width:120px;'>Presentation date <span style='float:right';>: &nbsp  </span></div>"
			 
			 //<input type='text' value=\"$presenter_date\" id='inp_presenter_date' name='presenter_date' style='width:100px;'/>
			 
			 . "<span class='xlnk' id='spdob' onclick='_changedatetime(\"spdob\",\"hdob\",\"date\",true,false)'>".sql2ind($presenter_date,'date')."</span><input type='hidden' value=\"$presenter_date\" name='presenter_date' id='hdob' id='inp_presenter_date'/></td></tr>" 
			
			//EVALUATOR
			 . "<tr><td style='text-align:left;'> &nbsp Evaluated by</td><td><div style='float:left; width:120px;'>Name <span style='float:right';>: &nbsp  </span></div><input type='text' value=\"$eval_name\" id='inp_eval_name' name='eval_name' style='width:200px;'/></td></tr>" 
			 . "<tr><td style='text-align:left;'></td><td><div style='float:left; width:120px;'>Position<span style='float:right';>: &nbsp  </span></div><input type='text' value=\"$eval_pos\" id='inp_eval_pos' name='eval_pos' style='width:450px;'/></td></tr>" 
			 . "<tr><td style='text-align:left;'></td><td><div style='float:left; width:120px;'>Date/ Sign <span style='float:right';>: &nbsp  </span></div>"
			 
			 //<input type='text' value=\"$eval_date\" id='inp_eval_date' name='eval_date' style='width:100px;'/>
			 
			 ."<span class='xlnk' id='spdob2' onclick='_changedatetime(\"spdob2\",\"hdob2\",\"date\",true,false)'>".sql2ind($eval_date,'date')."</span><input type='hidden' value=\"$eval_date\" name='eval_date' id='hdob2' id='inp_eval_date'/></td></tr>" ;
		   
       
	   
		$ret .= "<tr><td colspan='2'><span id='progress'></span>&nbsp;"
           . "<input id='btn_save_session' onclick='save_session();' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input id='btn_cancel_edit' onclick='cancel_edit();' type='button' value='"._CANCEL."'/>&nbsp;&nbsp;"
           . ($psid!="new"?"<input id='btn_delete_session' onclick='delete_session();' type='button' value='"._DELETE."'/>":"")
           . "</td></tr>"
           . "</tbody></table></form>";
      return $ret;
   }
   
   function app_viewSession($args) {
      $db=&Database::getInstance();
      $psid = $args[0];
      if($psid=="new") {
         $date_created  = getSQLDate();
		 $id_created = getUserID();
         $generate = "";
      } else {
        $id_modified = getUserID();
		$date_modified  = getSQLDate();

		  $sql =  "SELECT institution,address, contact_person,phone, subject, num_participant, budget, duration, focus, objective, rate_analysis, rate_inprof, rate_tailor, rate_ref, rate_applicable,rate_fee,rate_teachmethod,rate_paymethod,rate_insability,rate_adm,rate_evalprogram,rate_train,rate_followup,rate_other,rate_other_inp,reason,reason_expertise,reason_fee,reason_duration,presenter_name,presenter_pos,presenter_date,eval_name,eval_pos,eval_date,focus_knowledge,focus_skills,focus_attitude,focus_motivation,focus_other FROM antrain_evaluationform WHERE psid = '$psid' ORDER BY psid DESC " ;
	
         $result = $db->query($sql);
    	 
       
	   list($institution,$address,$contact_person,$phone,$subject,$num_participant,$budget,$duration,$focus,$objective,$rate_analysis,$rate_inprof,$rate_tailor,$rate_ref,$rate_applicable,$rate_fee,$rate_teachmethod,$rate_paymethod,$rate_insability,$rate_adm,$rate_evalprogram,$rate_train,$rate_followup,$rate_other,$rate_other_inp,$reason,$reason_expertise,$reason_fee,$reason_duration,$presenter_name,$presenter_pos,$presenter_date,$eval_name,$eval_pos,$eval_date,$focus_knowledge,$focus_skills,$focus_attitude,$focus_motivation,$focus_other)=$db->fetchRow($result);
        $institution = htmlentities($institution,ENT_QUOTES);
		
		//$presenter_date = date('d/M/Y', strtotime($presenter_date));
		//$eval_date = date('d/M/Y', strtotime($eval_date));
		
		
			
	 }
	 
		
									  
      $ret = "<form id='frm'><table class='xxfrm' style='width:100%;'><colgroup><col width='160'/><col/></colgroup><tbody>"
         
           . "<tr><td style='text-align:left;'> &nbsp Institution<span style='float:right';>:</span></td><td><input type='text' value=\"$institution\" id='inp_institution' name='institution' style='width:200px;' disabled /></td></tr>" 
		   
		   . "<tr><td style='text-align:left;'> &nbsp Address<span style='float:right';>:</span></td><td><input type='text' value=\"$address\" id='inp_address' name='address' style='width:200px;' disabled  /></td></tr>"
		   
		   . "<tr><td style='text-align:left;'> &nbsp Contact Person<span style='float:right';>:</span></td><td><input type='text' value=\"$contact_person\" id='inp_contact_person' name='contact_person' style='width:100px;' disabled  /></td></tr>"
		   
		   . "<tr><td style='text-align:left;'> &nbsp Phone<span style='float:right';>:</span></td><td><input type='text' value=\"$phone\" id='inp_phone' name='phone' style='width:100px;' disabled  /></td></tr>"

		   . "<tr><td style='text-align:left;'> &nbsp Training/Seminar Subject<span style='float:right';>:</span></td><td><input type='text' value=\"$subject\" id='inp_subject' name='subject' style='width:200px;' disabled  /></td></tr>"
		   
		   . "<tr><td style='text-align:left;'> &nbsp Number of participant<span style='float:right';>:</span></td><td><input type='text' value=\"$num_participant\" id='inp_num_participant' name='num_participant' style='width:50px;' disabled  /></td></tr>"
		   
		  . "<tr><td style='text-align:left;'> &nbsp Approximate Budget<span style='float:right';>:</span></td><td><input type='text' value=\"$budget\" id='inp_budget' name='budget' style='width:50px;' disabled  /></td></tr>" 
		 
		 . "<tr><td style='text-align:left;'> &nbsp Duration<span style='float:right';>:</span></td><td><input type='text' value=\"$duration\" id='inp_duration' name='duration' style='width:50px;' disabled  /></td></tr>" 
		
		 . "<tr><td style='text-align:left;'> &nbsp Focus on Improvement<span style='float:right';>:</span></td>
				<td>";
				if($focus_knowledge == 1){ $selectedknw = 'checked';} else { $selectedknw = '';}
				if($focus_skills == 1){ $selectedskl = 'checked';} else { $selectedskl = '';}
				if($focus_attitude == 1){ $selectedatt = 'checked';} else { $selectedatt = '';}
				if($focus_motivation == 1){ $selectedmtv = 'checked';} else { $selectedmtv = '';}
				if($focus_other == 1){ $selectedoth = 'checked';} else { $selectedoth = '';}
			 $ret .= "
			 <input type='checkbox' value='1' id='inp_focus_knowledge' name='focus_knowledge' $selectedknw disabled />Knowledge<br/>
			 <input type='checkbox' value='1' id='inp_focus_skills' name='focus_skills' $selectedskl disabled />Skills<br/>
			 <input type='checkbox' value='1' id='inp_focus_attitude' name='focus_attitude' $selectedatt disabled />Attitude<br/>
			 <input type='checkbox' value='1' id='inp_focus_motivation' name='focus_motivation' $selectedmtv disabled />Motivation<br/>
			 <input type='checkbox' value='1' id='inp_focus_other' name='focus_other' $selectedoth disabled />Other<br/>
				</td>
			</tr>"

			. "<tr><td style='text-align:left;'> &nbsp Objective of Training/Seminar<span style='float:right';>:</span></td><td><input type='text' value=\"$objective\" id='inp_objective' name='objective' style='width:200px;' disabled  /></td></tr>" 
			
			//RATING
			//1
			
			. "<tr>
					<td style='text-align:left;'>
						&nbsp Rating of  Institution to be Considered &nbsp <br/> &nbsp (1 - 5,5 is the best score)<span style='float:right';>:</span>
					</td>
					<td>
						<div style='float:left; width:300px;'>
							<select id='inp_rate_analysis' name='rate_analysis' disabled >
								";
								 $num1 = 1;
								 $num2 = 2;
								 $num3 = 3;
								 $num4 = 4;
								 $num5 = 5;
								if($rate_analysis == $num1){ $selected1 = 'selected';} else { $selected1 = '';}
								if($rate_analysis == $num2){ $selected2 = 'selected';} else { $selected2 = '';}
								if($rate_analysis == $num3){ $selected3 = 'selected';} else { $selected3 = '';}
								if($rate_analysis == $num4){ $selected4 = 'selected';} else { $selected4 = '';}
								if($rate_analysis == $num5){ $selected5 = 'selected';} else { $selected5 = '';}
								 $ret .= "<option value='$num1' $selected1>$num1</option>
									<option value='$num2' $selected2>$num2</option>
									<option value='$num3' $selected3>$num3</option>
									<option value='$num4' $selected4>$num4</option>
									<option value='$num5' $selected5>$num5</option>
							</select> &nbsp Needs Analysis
						</div>
							<select id='inp_rate_analysis' name='rate_analysis' disabled > 
								";
								 $num1 = 1;
								 $num2 = 2;
								 $num3 = 3;
								 $num4 = 4;
								 $num5 = 5;
								if($rate_analysis == $num1){ $selected1 = 'selected';} else { $selected1 = '';}
								if($rate_analysis == $num2){ $selected2 = 'selected';} else { $selected2 = '';}
								if($rate_analysis == $num3){ $selected3 = 'selected';} else { $selected3 = '';}
								if($rate_analysis == $num4){ $selected4 = 'selected';} else { $selected4 = '';}
								if($rate_analysis == $num5){ $selected5 = 'selected';} else { $selected5 = '';}
								 $ret .= "<option value='$num1' $selected1>$num1</option>
									<option value='$num2' $selected2>$num2</option>
									<option value='$num3' $selected3>$num3</option>
									<option value='$num4' $selected4>$num4</option>
									<option value='$num5' $selected5>$num5</option>
							</select>&nbsp Institution Profile; Years in business,owner,etc

					</td>
					
				</tr>"
			 
			 //2
				. "<tr>
					<td style='text-align:left;'>
					</td>
					<td>
						<div style='float:left; width:300px;'>
							<select id='inp_rate_tailor' name='rate_tailor' disabled > 
									";
									 $num1 = 1;
									 $num2 = 2;
									 $num3 = 3;
									 $num4 = 4;
									 $num5 = 5;
									if($rate_tailor == $num1){ $selected1 = 'selected';} else { $selected1 = '';}
									if($rate_tailor == $num2){ $selected2 = 'selected';} else { $selected2 = '';}
									if($rate_tailor == $num3){ $selected3 = 'selected';} else { $selected3 = '';}
									if($rate_tailor == $num4){ $selected4 = 'selected';} else { $selected4 = '';}
									if($rate_tailor == $num5){ $selected5 = 'selected';} else { $selected5 = '';}
									 $ret .= "<option value='$num1' $selected1>$num1</option>
										<option value='$num2' $selected2>$num2</option>
										<option value='$num3' $selected3>$num3</option>
										<option value='$num4' $selected4>$num4</option>
										<option value='$num5' $selected5>$num5</option>
								</select> &nbsp Tailor made contents					
						</div>
							<select id='inp_rate_ref' name='rate_ref' disabled > 
									";
									 $num1 = 1;
									 $num2 = 2;
									 $num3 = 3;
									 $num4 = 4;
									 $num5 = 5;
									if($rate_ref == $num1){ $selected1 = 'selected';} else { $selected1 = '';}
									if($rate_ref == $num2){ $selected2 = 'selected';} else { $selected2 = '';}
									if($rate_ref == $num3){ $selected3 = 'selected';} else { $selected3 = '';}
									if($rate_ref == $num4){ $selected4 = 'selected';} else { $selected4 = '';}
									if($rate_ref == $num5){ $selected5 = 'selected';} else { $selected5 = '';}
									 $ret .= "<option value='$num1' $selected1>$num1</option>
										<option value='$num2' $selected2>$num2</option>
										<option value='$num3' $selected3>$num3</option>
										<option value='$num4' $selected4>$num4</option>
										<option value='$num5' $selected5>$num5</option>
								</select> &nbsp Reference from other companies
					</td>
				   </tr>"
				
				//3
				. "<tr>
						<td style='text-align:left;'>
						</td>
						<td>
							<div style='float:left; width:300px;'>
							<select id='inp_rate_applicable' name='rate_applicable' disabled > 
									";
									 $num1 = 1;
									 $num2 = 2;
									 $num3 = 3;
									 $num4 = 4;
									 $num5 = 5;
									if($rate_applicable == $num1){ $selected1 = 'selected';} else { $selected1 = '';}
									if($rate_applicable == $num2){ $selected2 = 'selected';} else { $selected2 = '';}
									if($rate_applicable == $num3){ $selected3 = 'selected';} else { $selected3 = '';}
									if($rate_applicable == $num4){ $selected4 = 'selected';} else { $selected4 = '';}
									if($rate_applicable == $num5){ $selected5 = 'selected';} else { $selected5 = '';}
									 $ret .= "<option value='$num1' $selected1>$num1</option>
										<option value='$num2' $selected2>$num2</option>
										<option value='$num3' $selected3>$num3</option>
										<option value='$num4' $selected4>$num4</option>
										<option value='$num5' $selected5>$num5</option>
								</select>  &nbsp Applicable contents
							</div>
								<select id='inp_rate_fee' name='rate_fee' disabled > 
									";
									 $num1 = 1;
									 $num2 = 2;
									 $num3 = 3;
									 $num4 = 4;
									 $num5 = 5;
									if($rate_fee == $num1){ $selected1 = 'selected';} else { $selected1 = '';}
									if($rate_fee == $num2){ $selected2 = 'selected';} else { $selected2 = '';}
									if($rate_fee == $num3){ $selected3 = 'selected';} else { $selected3 = '';}
									if($rate_fee == $num4){ $selected4 = 'selected';} else { $selected4 = '';}
									if($rate_fee == $num5){ $selected5 = 'selected';} else { $selected5 = '';}
									 $ret .= "<option value='$num1' $selected1>$num1</option>
										<option value='$num2' $selected2>$num2</option>
										<option value='$num3' $selected3>$num3</option>
										<option value='$num4' $selected4>$num4</option>
										<option value='$num5' $selected5>$num5</option>
								</select>  &nbsp Training/seminar fee
						</td>
					</tr>"
					
							//4
				. "<tr>
						<td style='text-align:left;'>
						</td>
						<td>
							<div style='float:left; width:300px;'>
								<select id='inp_rate_teachmethod' name='rate_teachmethod' disabled > 
									";
									 $num1 = 1;
									 $num2 = 2;
									 $num3 = 3;
									 $num4 = 4;
									 $num5 = 5;
									if($rate_teachmethod == $num1){ $selected1 = 'selected';} else { $selected1 = '';}
									if($rate_teachmethod == $num2){ $selected2 = 'selected';} else { $selected2 = '';}
									if($rate_teachmethod == $num3){ $selected3 = 'selected';} else { $selected3 = '';}
									if($rate_teachmethod == $num4){ $selected4 = 'selected';} else { $selected4 = '';}
									if($rate_teachmethod == $num5){ $selected5 = 'selected';} else { $selected5 = '';}
									 $ret .= "<option value='$num1' $selected1>$num1</option>
										<option value='$num2' $selected2>$num2</option>
										<option value='$num3' $selected3>$num3</option>
										<option value='$num4' $selected4>$num4</option>
										<option value='$num5' $selected5>$num5</option>
								</select>  &nbsp Method of teaching					
							</div>
								<select id='inp_rate_paymethod' name='rate_paymethod' disabled > 
									";
									 $num1 = 1;
									 $num2 = 2;
									 $num3 = 3;
									 $num4 = 4;
									 $num5 = 5;
									if($rate_paymethod == $num1){ $selected1 = 'selected';} else { $selected1 = '';}
									if($rate_paymethod == $num2){ $selected2 = 'selected';} else { $selected2 = '';}
									if($rate_paymethod == $num3){ $selected3 = 'selected';} else { $selected3 = '';}
									if($rate_paymethod == $num4){ $selected4 = 'selected';} else { $selected4 = '';}
									if($rate_paymethod == $num5){ $selected5 = 'selected';} else { $selected5 = '';}
									 $ret .= "<option value='$num1' $selected1>$num1</option>
										<option value='$num2' $selected2>$num2</option>
										<option value='$num3' $selected3>$num3</option>
										<option value='$num4' $selected4>$num4</option>
										<option value='$num5' $selected5>$num5</option>
								</select>  &nbsp Payment method			
						</td>
					</tr>"
					
							//5
				. "<tr>
						<td style='text-align:left;'>
						</td>
						<td>
							<div style='float:left; width:300px;'>
								<select id='inp_rate_insability' name='rate_insability' disabled > 
									";
									 $num1 = 1;
									 $num2 = 2;
									 $num3 = 3;
									 $num4 = 4;
									 $num5 = 5;
									if($rate_insability == $num1){ $selected1 = 'selected';} else { $selected1 = '';}
									if($rate_insability == $num2){ $selected2 = 'selected';} else { $selected2 = '';}
									if($rate_insability == $num3){ $selected3 = 'selected';} else { $selected3 = '';}
									if($rate_insability == $num4){ $selected4 = 'selected';} else { $selected4 = '';}
									if($rate_insability == $num5){ $selected5 = 'selected';} else { $selected5 = '';}
									 $ret .= "<option value='$num1' $selected1>$num1</option>
										<option value='$num2' $selected2>$num2</option>
										<option value='$num3' $selected3>$num3</option>
										<option value='$num4' $selected4>$num4</option>
										<option value='$num5' $selected5>$num5</option>
								</select>  &nbsp Instructor's ability
							</div>
								<select id='inp_rate_adm' name='rate_adm' disabled > 
									";
									 $num1 = 1;
									 $num2 = 2;
									 $num3 = 3;
									 $num4 = 4;
									 $num5 = 5;
									if($rate_adm == $num1){ $selected1 = 'selected';} else { $selected1 = '';}
									if($rate_adm == $num2){ $selected2 = 'selected';} else { $selected2 = '';}
									if($rate_adm == $num3){ $selected3 = 'selected';} else { $selected3 = '';}
									if($rate_adm == $num4){ $selected4 = 'selected';} else { $selected4 = '';}
									if($rate_adm == $num5){ $selected5 = 'selected';} else { $selected5 = '';}
									 $ret .= "<option value='$num1' $selected1>$num1</option>
										<option value='$num2' $selected2>$num2</option>
										<option value='$num3' $selected3>$num3</option>
										<option value='$num4' $selected4>$num4</option>
										<option value='$num5' $selected5>$num5</option>
								</select>  &nbsp Administration matter,contract,certificate, etc.
						</td>
					</tr>"
					
							//6
				. "<tr>
						<td style='text-align:left;'>
						</td>
						<td>
							<div style='float:left; width:300px;'>
							     <select id='inp_rate_evalprogram' name='rate_evalprogram' disabled > 
									";
									 $num1 = 1;
									 $num2 = 2;
									 $num3 = 3;
									 $num4 = 4;
									 $num5 = 5;
									if($rate_evalprogram == $num1){ $selected1 = 'selected';} else { $selected1 = '';}
									if($rate_evalprogram == $num2){ $selected2 = 'selected';} else { $selected2 = '';}
									if($rate_evalprogram == $num3){ $selected3 = 'selected';} else { $selected3 = '';}
									if($rate_evalprogram == $num4){ $selected4 = 'selected';} else { $selected4 = '';}
									if($rate_evalprogram == $num5){ $selected5 = 'selected';} else { $selected5 = '';}
									 $ret .= "<option value='$num1' $selected1>$num1</option>
										<option value='$num2' $selected2>$num2</option>
										<option value='$num3' $selected3>$num3</option>
										<option value='$num4' $selected4>$num4</option>
										<option value='$num5' $selected5>$num5</option>
								</select>  &nbsp Evaluation program
							</div>
							     <select id='inp_rate_train' name='rate_train' disabled > 
									";
									 $num1 = 1;
									 $num2 = 2;
									 $num3 = 3;
									 $num4 = 4;
									 $num5 = 5;
									if($rate_train == $num1){ $selected1 = 'selected';} else { $selected1 = '';}
									if($rate_train == $num2){ $selected2 = 'selected';} else { $selected2 = '';}
									if($rate_train == $num3){ $selected3 = 'selected';} else { $selected3 = '';}
									if($rate_train == $num4){ $selected4 = 'selected';} else { $selected4 = '';}
									if($rate_train == $num5){ $selected5 = 'selected';} else { $selected5 = '';}
									 $ret .= "<option value='$num1' $selected1>$num1</option>
										<option value='$num2' $selected2>$num2</option>
										<option value='$num3' $selected3>$num3</option>
										<option value='$num4' $selected4>$num4</option>
										<option value='$num5' $selected5>$num5</option>
								</select>  &nbsp Training facility
						</td>
					</tr>"
					
							//7
				. "<tr>
						<td style='text-align:left;'>
						</td>
						<td>
							<div style='float:left; width:300px;'>
							     <select id='inp_rate_followup' name='rate_followup' disabled > 
									";
									 $num1 = 1;
									 $num2 = 2;
									 $num3 = 3;
									 $num4 = 4;
									 $num5 = 5;
									if($rate_followup == $num1){ $selected1 = 'selected';} else { $selected1 = '';}
									if($rate_followup == $num2){ $selected2 = 'selected';} else { $selected2 = '';}
									if($rate_followup == $num3){ $selected3 = 'selected';} else { $selected3 = '';}
									if($rate_followup == $num4){ $selected4 = 'selected';} else { $selected4 = '';}
									if($rate_followup == $num5){ $selected5 = 'selected';} else { $selected5 = '';}
									 $ret .= "<option value='$num1' $selected1>$num1</option>
										<option value='$num2' $selected2>$num2</option>
										<option value='$num3' $selected3>$num3</option>
										<option value='$num4' $selected4>$num4</option>
										<option value='$num5' $selected5>$num5</option>
								</select> &nbsp Follow up action
							</div>
							   <select id='inp_rate_other' name='rate_other' disabled > 
									";
									 $num1 = 1;
									 $num2 = 2;
									 $num3 = 3;
									 $num4 = 4;
									 $num5 = 5;
									if($rate_other == $num1){ $selected1 = 'selected';} else { $selected1 = '';}
									if($rate_other == $num2){ $selected2 = 'selected';} else { $selected2 = '';}
									if($rate_other == $num3){ $selected3 = 'selected';} else { $selected3 = '';}
									if($rate_other == $num4){ $selected4 = 'selected';} else { $selected4 = '';}
									if($rate_other == $num5){ $selected5 = 'selected';} else { $selected5 = '';}
									 $ret .= "<option value='$num1' $selected1>$num1</option>
										<option value='$num2' $selected2>$num2</option>
										<option value='$num3' $selected3>$num3</option>
										<option value='$num4' $selected4>$num4</option>
										<option value='$num5' $selected5>$num5</option>
								</select> &nbsp Other
							<input type='text' value=\"$rate_other_inp\" id='rate_other_inp' name='rate_other_inp' style='width:100px;' disabled  />
							
						</td>
					</tr>"
			
			 . "<tr><td style='text-align:left;'> &nbsp Main reason for selecting this institution<br/> &nbsp (expertise/fee/duration) <span style='float:right';>:</span></td>
			 <td>"; 
			if($reason_expertise == 1){ $selectedexp = 'checked';} else { $selectedexp = '';}
			if($reason_fee == 1){ $selectedfee = 'checked';} else { $selectedfee = '';}
			if($reason_duration == 1){ $selecteddrt = 'checked';} else { $selecteddrt = '';}
			 $ret .= "
			 <input type='checkbox' value='1' id='inp_reason_exp' name='reason_expertise' $selectedexp / disabled >Expertise<br/>
			 <input type='checkbox' value='1' id='inp_reason_fee' name='reason_fee' $selectedfee  disabled  />Fee<br/>
			 <input type='checkbox' value='1' id='inp_reason_drt' name='reason_duration' $selecteddrt disabled  />Duration<br/></td></tr>" 
			
			//PRESENTER
			 . "<tr><td style='text-align:left;'> &nbsp Presenter from Institution</td><td><div style='float:left; width:120px;'>Name <span style='float:right';>: &nbsp  </span></div><input type='text' value=\"$presenter_name\" id='inp_presenter_name' name='presenter_name' style='width:200px;' disabled  /></td></tr>" 
			 . "<tr><td style='text-align:left;'></td><td><div style='float:left; width:120px;'>Postition<span style='float:right';>: &nbsp  </span></div><input type='text' value=\"$presenter_pos\" id='inp_presenter_pos' name='presenter_pos' style='width:100px;' disabled  /></td></tr>" 
			 . "<tr><td style='text-align:left;'></td><td><div style='float:left; width:120px;'>Presentation date <span style='float:right';>: &nbsp  </span></div>"
			 
			 //<input type='text' value=\"$presenter_date\" id='inp_presenter_date' name='presenter_date' style='width:100px;'/>
			 
			 . "<span>".sql2ind($presenter_date,'date')."</span><input type='hidden' value=\"$presenter_date\" name='presenter_date' id='hdob' id='inp_presenter_date'/></td></tr>" 
			
			//EVALUATOR
			 . "<tr><td style='text-align:left;'> &nbsp Evaluated by</td><td><div style='float:left; width:120px;'>Name <span style='float:right';>: &nbsp  </span></div><input type='text' value=\"$eval_name\" id='inp_eval_name' name='eval_name' style='width:200px;' disabled /></td></tr>" 
			 . "<tr><td style='text-align:left;'></td><td><div style='float:left; width:120px;'>Postition<span style='float:right';>: &nbsp  </span></div><input type='text' value=\"$presenter_pos\" id='inp_eval_pos' name='eval_pos' style='width:100px;' disabled /></td></tr>" 
			 . "<tr><td style='text-align:left;'></td><td><div style='float:left; width:120px;'>Date/ Sign <span style='float:right';>: &nbsp  </span></div>"
			 
			 //<input type='text' value=\"$eval_date\" id='inp_eval_date' name='eval_date' style='width:100px;'/>
			 
			 ."<span>".sql2ind($eval_date,'date')."</span><input type='hidden' value=\"$eval_date\" name='eval_date' id='hdob2' id='inp_eval_date' disabled /></td></tr>" ;
		   
       
	   
		$ret .= "<tr><td colspan='2'><span id='progress'></span>&nbsp;"
//           . "<input id='btn_save_session' onclick='save_session();' type='button' value='"._SAVE."' disabled />&nbsp;"
           . "<input id='btn_cancel_edit' onclick='cancel_edit();' type='button' value='Close' />&nbsp;&nbsp;"
//           . ($psid!="new"?"<input id='btn_delete_session' onclick='delete_session();' type='button' value='"._DELETE."' disabled />":"")
           . "</td></tr>"
           . "</tbody></table></form>";
      return $ret;
   }
   
   
   function app_Delete($args) {
      $db=&Database::getInstance();
      $psid = $args[0];
      $user_id = getUserID();
      $sql = "UPDATE antrain_evaluationform SET status_cd = 'nullified', nullified_user_id = '$user_id' WHERE psid = '$psid'";
      $db->query($sql);
   }
   
}

} /// HRIS_ASSESSMENTSESSIONAJAX_DEFINED
?>