<?php
//--------------------------------------------------------------------//
// Filename : modules/antrain/class/ajax_smsobj.php                  //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-12-17                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('SMS_OBJAJAX_DEFINED') ) {
   define('SMS_OBJAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");
require_once(XOCP_DOC_ROOT."/modules/sms/modconsts.php");


class _sms_class_SMSObjAjax extends AjaxListener {
   
   function __construct($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/sms/class/ajax_smsobj.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_Delete","app_editSession","app_saveSession",
                                             "app_setANTRAINSession","app_newSession","app_saveObjTargetText","app_addObjOwner","app_saveObjectiveOwner","app_addMeasure","app_saveMeasure","app_newKsf","app_saveKsf","app_editKsf","app_deleteKsf","app_editPicObj","app_deletePicObj","app_savePicObj");
   }
  
  function app_saveObjTargetText($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $val = addslashes($args[0]);
      $idobj = $args[1];
      $ids = $args[2];
      $no = $args[3];
     
	 if($val==''){$val='Empty';}else{$val=$val;}
	 
	if($no == 0) { 
	  $sql = "UPDATE sms_objective SET objective_code = '$val' WHERE id = '$idobj'";
      $db->query($sql); }
	
	elseif ($no == 1) {
	  $sql = "UPDATE sms_objective SET objective_title = '$val' WHERE id = '$idobj'";
       $db->query($sql); }
	elseif ($no == 2) {
	  $sql = "UPDATE sms_objective SET objective_description = '$val' WHERE id = '$idobj'";
       $db->query($sql); }
	
	#MEASURE
	elseif ($no == 3) {
	  $sql = "UPDATE sms_objective_measure SET measure_code = '$val' WHERE id = '$ids'";
       $db->query($sql); }
	elseif ($no == 4) {
	  $sql = "UPDATE sms_objective_measure SET measure_description = '$val' WHERE id = '$ids'";
       $db->query($sql); }
	
	#INTENT
	elseif ($no == 5) {
	  $sql = "UPDATE sms_objective_intent SET intent_code = '$val' WHERE id = '$ids'";
       $db->query($sql); }
	elseif ($no == 6) {
	  $sql = "UPDATE sms_objective_intent SET intent_description = '$val' WHERE id = '$ids'";
       $db->query($sql); }
	
	#FREQUENCY
	elseif ($no == 7) {
	  $sql = "UPDATE sms_objective_frequency SET frequency_code = '$val' WHERE id = '$ids'";
	  $db->query($sql); }
	elseif ($no == 8) {
	   $sql = "UPDATE sms_objective_frequency SET frequency_description = '$val' WHERE id = '$ids' ";
       $db->query($sql); }
	
	elseif ($no == 9) {
	  $sql = "UPDATE sms_section_objective SET ap_req = '$val' WHERE id = '$idobj'";
	  $db->query($sql); }
	elseif ($no == 10) {
	
	   $sql = "UPDATE sms_section_objective SET ap_far_req = '$val' WHERE id = '$idobj'";
       $db->query($sql); }	   

	#TARGET
	   
	   elseif ($no == 12) {
	
	   $sql = "UPDATE sms_objective_target SET target_code = '$val' WHERE id = '$ids'";
       $db->query($sql); }	   

	   elseif ($no == 13) {
	
	   $sql = "UPDATE sms_objective_target SET target_high = '$val' WHERE id = '$ids'";
       $db->query($sql); }	
	   
      elseif ($no == 14) {
	
	   $sql = "UPDATE sms_objective_target SET target_medium = '$val' WHERE id = '$ids'";
       $db->query($sql); }	   
		elseif ($no == 15) {
	
	   $sql = "UPDATE sms_objective_target SET target_low = '$val' WHERE id = '$ids'";
       $db->query($sql); }	   
	   
	   #ACTIONPLAN
	   
	   	elseif ($no == 16) {
		
			if($ids==0)
			{
				$idss = $ids+1;
				$sql = "INSERT INTO sms_objective_actionplan(id_objective,actionplan_description) VALUES ('$idobj','$val')";
				$db->query($sql);
				
			}
			else{
				$sql = "UPDATE sms_objective_actionplan SET actionplan_description = '$val' WHERE id = '$ids'";
				$db->query($sql);
			}	   
		
		}
	   
	   #WGT FUNCTIONAL
	   
	   	elseif ($no == 11) {
	
	  $sql = "UPDATE sms_section_objective SET weight = '$val' WHERE id = '$idobj'";
       $db->query($sql); }
 
	/*    	  $sql = "UPDATE sms_objective_ksf SET "
        . "ksf_lower_perform = '".addslashes($vars["ksf_lower_perform"])."',"
        . "ksf_need_improvement = '".addslashes($vars["ksf_need_improvement"])."',"
        . "ksf_target = '".addslashes($vars["ksf_target"])."',"
        . "ksf_req = '".addslashes($vars["ksf_req"])."',"
		. "ksf_far_req = '".addslashes($vars["ksf_far_req"])."'"
        . " WHERE id = '$idobj'";
       $db->query($sql); */
	}
 
  
   function app_setANTRAINSession($args) {
      $_SESSION["antrain_psid"] = $args[0];
   }
   
   function app_newSession($args) {
      $db=&Database::getInstance();
      // $sql = "SELECT MAX(psid) FROM antrain_session ORDER BY year DESC " ;
	  $sql = "SELECT p.psid, p.year, p.budget, p.remark, p.status_cd, pk.org_nm, pk.org_class_id FROM antrain_session p"
			  . " LEFT JOIN hris_orgs pk ON p.org_id = pk.org_id"
			  . " WHERE psid = ( SELECT MAX(psid) FROM antrain_session ) ORDER BY year DESC " ;
     $result = $db->query($sql);
      list($psidx,$year,$budget,$remark)=$db->fetchRow($result);
      $psid = $psidx+1;
      $year = date("Y");
	  $budget = addslashes(trim($vars["budget"]));
	  $remark = addslashes(trim($vars["remark"]));	  
	  $id_created = getUserID();
	  $date_created = getSQLDate();
	  $sql = "INSERT INTO antrain_session (psid,year,budget,remark,id_created,date_created)"
           . " VALUES('$psid','$year','$budget','$remark','$id_created','$date_created')";
      $db->query($sql);

	 
     $hdr = "<table><colgroup><col width='60'/><col/></colgroup><tbody><tr>"
			  . "<td width=70><span id='sp_${psid}' class='xlnk' onclick='edit_session(\"$psid\",this,event);' >$year</span></td>"
			  . "<td id='td_org_nm_${psid}' width=125 style='text-align: left;'>MCCI</td>"
			  . "<td id='td_remark_${psid}' width=200 style='text-align: left;'>$remark</td>"
			  . "</tr></tbody></table>";
      return array($psid,$hdr);
   }
   
   function app_saveSession($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $psid = $args[0];
      $vars = parseForm($args[1]);
      $year = _bctrim(bcadd(0,$vars["year"]));
	  $budget = _bctrim(bcadd(0,$vars["budget"]));
	  $remark = addslashes(trim($vars["remark"]));
	  $id_created = getUserID();
	  $id_modified = getUserID();
	  $date_created = getSQLDate($vars["date_created"]);
	  $date_modified =  getSQLDate($vars["date_modified"]);
	
	  //$org_nm = addslashes(trim($vars["org_nm"]));
	  
      if($psid=="new") {
 
		$sql = "SELECT p.psid, p.year, p.budget, p.remark, p.status_cd, p.date_created, p.date_modified, pk.org_nm, pk.org_class_id FROM antrain_session p"
			  . " LEFT JOIN hris_orgs pk ON p.org_id = pk.org_id"
			  . " WHERE psid = ( SELECT MAX(psid) FROM antrain_session ) ORDER BY year DESC " ;
	         
	     //$sql =  "SELECT MAX(psid) FROM antrain_session ORDER BY year DESC " ;
         $result = $db->query($sql);
         list($psidx)=$db->fetchRow($result);
         $psid = $psidx+1;
         // $user_id = getUserID();
		 // $date_created = getSQLDate();
		// $org_nm = $org_nmx;
         $sql = "INSERT INTO antrain_session (psid,year,budget,remark,id_created,date_created)"
              . " VALUES('$psid','$year','$budget','$remark','$id_created','$date_created')";
         $db->query($sql);
      } else {
         $sql = "UPDATE antrain_session SET "
              . "year = '$year', budget = '$budget', remark = '$remark', id_modified = '$id_modified', date_modified = '$date_modified'"			  
              . " WHERE psid = '$psid'";
         $db->query($sql);
		 
      }
      
      return array($psid,$year,$remark);
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
/* 	$sql = "SELECT year,budget,remark,date_created"
             . " FROM antrain_session"
             . " WHERE psid = '$psid'"; */
		  $sql =  "SELECT p.year, p.budget, p.remark, pk.org_nm FROM antrain_session p"
			  . " LEFT JOIN hris_orgs pk ON p.org_id = pk.org_id"
			  . " WHERE psid = '$psid' ORDER BY year DESC " ;
	
         $result = $db->query($sql);
    	 
       
	   list($year,$budget,$remark,$org_nm)=$db->fetchRow($result);
        $year = htmlentities($year,ENT_QUOTES);
		 
	 //  list($org_id,$org_nm,$org_class_id)=$db->fetchRow($result2);
     //    $org_nm = htmlentities($org_nm,ENT_QUOTES);		
      }
      $ret = "<form id='frm'><table class='xxfrm' style='width:100%;'><colgroup><col width='160'/><col/></colgroup><tbody>"
         
           . "<tr><td>Year</td><td><input type='text' value=\"$year\" id='inp_year' name='year' style='width:50px;'/></td></tr>" 
		   . "<tr><td>Budget</td><td><input type='text' value=\"$budget\" id='inp_budget' name='budget' style='width:50px;'/></td></tr>" 
		   . "<tr><td>Div/Section</td><td><p>$org_nm</p></td></tr>" 
		   . "<tr><td>Remark</td><td><input type='text' value=\"$remark\" id='inp_remark' name='remark' style='width:95%;'/></td></tr>"; 
          
           $ret .= "<tr><td colspan='2'><span id='progress'></span>&nbsp;"
           . "<input id='btn_save_session' onclick='save_session();' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input id='btn_cancel_edit' onclick='cancel_edit();' type='button' value='"._CANCEL."'/>&nbsp;&nbsp;"
           . ($psid!="new"?"<input id='btn_delete_session' onclick='delete_session();' type='button' value='"._DELETE."'/>":"")
           . "</td></tr>"
           . "</tbody></table></form>";
      return $ret;
   }
   
   function app_Delete($args) {
      $db=&Database::getInstance();
      $psid = $args[0];
      $user_id = getUserID();
      $sql = "UPDATE antrain_session SET status_cd = 'nullified', nullified_user_id = '$user_id' WHERE psid = '$psid'";
      $db->query($sql);
   }
   
   #// ADD/EDIT OBJ OWNER
 
    function app_saveObjectiveOwner($args) {
      $db=&Database::getInstance();
	  $vars = _parseForm($args[1]);
      $idobj = $args[0];
      $vars["id_objective_owner"] = _bctrim(bcadd($vars["id_objective_owner"],0));
      $vars["id_objective_owner_2"] = _bctrim(bcadd($vars["id_objective_owner_2"],0));
      
      $sql = "UPDATE sms_objective SET "
        . "id_objective_owner = '".$vars["id_objective_owner"]."',"
        . "id_objective_owner_2 = '".$vars["id_objective_owner_2"]."' "
        . "  WHERE id = '$idobj'";
       $db->query($sql);
      
         
      return array( $idobj,$vars["id_objective_owner"],$vars["id_objective_owner_2"]);
     
   }
   
   function app_addObjOwner($args) {
      $db=&Database::getInstance();
      $idobj = $args[0];
	  
	  
	  
   $btn = "<input type='button' value='Save' onclick='save_objowner(\"$idobj\");'/>&nbsp;&nbsp;"
           . "&nbsp;<input type='button' value='"._CANCEL."' onclick='persbox.fade();'/>";
     
	 
    $date =getSQLDate();
	$sqlsess = "SELECT id,periode_session FROM sms_session";
	$resultsess = $db->query($sqlsess); 
	  
	$sqltheme = "SELECT id,title FROM sms_ref_themes";
	$resulttheme = $db->query($sqltheme);
	
	$sqlper = "SELECT id,code,title FROM sms_ref_perspektive";
	$resultper = $db->query($sqlper);

	$sqloo = "SELECT person_id,person_nm FROM hris_persons WHERE status_cd = 'normal' ORDER BY hris_persons.person_nm ASC";
	$resultoo = $db->query($sqloo);
	
	$sqloo2 = "SELECT person_id,person_nm FROM hris_persons WHERE status_cd = 'normal' ORDER BY hris_persons.person_nm ASC";
	$resultoo2 = $db->query($sqloo2);
	
	
	$sqlobj = "SELECT id_objective_owner,id_objective_owner_2 FROM sms_objective WHERE id = $idobj";
	$resultobj = $db->query($sqlobj);
	list($id_objective_ownerdb,$id_objective_owner_2db)=$db->fetchRow($resultobj);

	

	$ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>"
           . ($objid=="new"?"Add Objective owner":"Edit Objective owner")
           . "</div>"
           . "<div style='padding:5px;background-color:#f2f2f2;color:#555;'>"
              . "<div style='max-height:82px;height:82px;overflow:auto;border:1px solid #999;background-color:#fff;padding:4px;' id='frmobj'>"
                  . "<table style='width:100%;border-spacing:0px;'><tbody><tr><td style='height:85px;'>"
                     . "<table class='xxfrm' style='width:100%;'><tbody>"     
		                . "<tr><td>Owner</td><td>"
						."<select name='id_objective_owner' id='id_objective_owner'>";
						while(list($id_objective_owner,$fullname)=$db->fetchRow($resultoo)) {
							if($id_objective_ownerdb==$id_objective_owner)
								{
									$selected_1 = ' selected ';
								}
							else
								{
									$selected_1 = '';
								}

							$ret .= "<option value='$id_objective_owner' $selected_1>$fullname</option>";
						 

						}
				$ret .= "</select> </td></tr></td></tr>"
				   . "<tr><td>Owner 2</td><td>"
						."<select name='id_objective_owner_2' id='id_objective_owner_2'>"
										  ."<option value='0'>-</option>";
						while(list($id_objective_owner_2,$fullname2)=$db->fetchRow($resultoo2)) {
								if($id_objective_owner_2db==$id_objective_owner_2)
								{
									$selected_2 = ' selected ';
								}
							else
								{
									$selected_2 = '';
								}
								$ret .= "<option value='$id_objective_owner_2' $selected_2>$fullname2</option>";
						
						}
				$ret .= "</select> </td></tr></td></tr>"
                       . "</tbody></table>"
	                . "</td></tr></tbody></table>"
              . "</div>"
           . "</div>"
           . "<div style='text-align:center;padding:10px;background-color:#f2f2f2;height:100px;' id='frmbtn'>"
           . $btn
           . "</div>";
      
      return $ret;
   }
  
#ADD MEASURE

    function app_saveMeasure($args) {
      $db=&Database::getInstance();
	  $idobj = $args[0];
	  $id_inp = $args[1];
      $vars = _parseForm($args[2]);
	  $user_id = getUserID();
      $date =getSQLDate();
	 
	#Input Measure	 
	 if( $id_inp==1){
      $sql = "SELECT MAX(id) FROM sms_objective_measure";
      $result = $db->query($sql);
      list($id_measure)=$db->fetchRow($result);


      $id_measure= $id_measure+1;
      $sql = "INSERT INTO sms_objective_measure (id,id_objective,create_date) VALUES ('$id_measure','$idobj','$date')";
      $db->query($sql);
      $is_new = 1;
  

      $sql = "UPDATE sms_objective_measure SET "
        . "measure_code = '".addslashes($vars["measure_code"])."',"
		. "measure_description = '".addslashes($vars["measure_description"])."'"
        . "  WHERE id = '$id_measure'";
       $db->query($sql);
      
      return array($id_measure,$idobj,$id_inp,$objid);
     }
	 
	 #Input intent	 
	 elseif($id_inp==2)
	 {
		  $sql = "SELECT MAX(id) FROM sms_objective_intent";
		  $result = $db->query($sql);
		  list($id_intent)=$db->fetchRow($result);


		  $id_intent= $id_intent+1;
		  $sql = "INSERT INTO sms_objective_intent (id,id_objective,create_date) VALUES ('$id_intent','$idobj','$date')";
		  $db->query($sql);
		  $is_new = 1;
	  

		  $sql = "UPDATE sms_objective_intent SET "
			. "intent_code = '".addslashes($vars["intent_code"])."',"
			. "intent_description = '".addslashes($vars["intent_description"])."'"
			. "  WHERE id = '$id_intent'";
		   $db->query($sql);
		  
		  return array($id_intent,$idobj,$id_inp,$objid);
		 }
		 
		  #Input Frequency	 
	 elseif($id_inp==3)
	 {
		  $sql = "SELECT MAX(id) FROM sms_objective_frequency";
		  $result = $db->query($sql);
		  list($id_freq)=$db->fetchRow($result);


		  $id_freq= $id_freq+1;
		  $sql = "INSERT INTO sms_objective_frequency (id,id_objective,create_date) VALUES ('$id_freq','$idobj','$date')";
		  $db->query($sql);
		  $is_new = 1;
	  

		  $sql = "UPDATE sms_objective_frequency SET "
			. "frequency_code = '".addslashes($vars["frequency_code"])."',"
			. "frequency_description = '".addslashes($vars["frequency_description"])."'"
			. "  WHERE id = '$id_freq'";
		   $db->query($sql);
		  
		  return array($id_freq,$idobj,$id_inp,$objid);
		}
		
		 elseif($id_inp==4)
	 {
		  $sql = "SELECT MAX(id) FROM sms_objective_target";
		  $result = $db->query($sql);
		  list($id_freq)=$db->fetchRow($result);


		  $id_freq= $id_freq+1;
		  $sql = "INSERT INTO sms_objective_target (id,id_objective,create_date) VALUES ('$id_freq','$idobj','$date')";
		  $db->query($sql);
		  $is_new = 1;
	  

		  $sql = "UPDATE sms_objective_target SET "
			. "target_code = '".addslashes($vars["target_code"])."',"
			. "target_high = '".addslashes($vars["target_high"])."',"
			. "target_medium = '".addslashes($vars["target_medium"])."',"
			. "target_low = '".addslashes($vars["target_low"])."'"
			. "  WHERE id = '$id_freq'";
		   $db->query($sql);
		  
		  return array($id_freq,$idobj,$id_inp,$objid);
		
		
	 }
		
		 
		 
	 
	 
   }
   
   function app_addMeasure($args) {
      $db=&Database::getInstance();
      $idobj = $args[0];
	  $id_inp = $args[1];
	  
	#MEASURE  
    if( $id_inp==1)
	{
	$btn = "<input type='button' value='"._ADD."' onclick='save_measure(\"$idobj\",\"$id_inp\");'/>&nbsp;&nbsp;"
              . "&nbsp;<input type='button' value='"._CANCEL."' onclick='persbox.fade();'/>";
 
     $date =getSQLDate();

	$ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>"
           . ($objid=="new"?"Add Measure":"Add Measure")
           . "</div>"
           . "<div style='padding:5px;background-color:#f2f2f2;color:#555;'>"
              . "<div style='max-height:85px;height:85px;overflow:auto;border:1px solid #999;background-color:#fff;padding:4px;' id='frmobj'>"
                  . "<table style='width:100%;border-spacing:0px;'><tbody><tr><td style='height:85px;'>"
                     . "<table class='xxfrm' style='width:100%;'><tbody>"     
						. "<tr><td>Code Measure  </td><td><input type='text' name='measure_code' id='measure_code' value='$measure_code' style='width:400px;'/> </td><td>"
                       . "<tr><td>Measure Description</td><td><input type='text' name='measure_description' id='measure_description' value='$measure_description' style='width:400px;'/></td></tr>"
					   
                       . "</tbody></table>"
	                . "</td></tr></tbody></table>"
              . "</div>"
           . "</div>"
           . "<div style='text-align:center;padding:10px;background-color:#f2f2f2;height:100px;' id='frmbtn'>"
           . $btn
           . "</div>";
      
      return $ret;
	  
	  }
	  
	  #INTENT
	  elseif( $id_inp ==2)
	  {
				$btn = "<input type='button' value='"._ADD."' onclick='save_measure(\"$idobj\",\"$id_inp\");'/>&nbsp;&nbsp;"
              . "&nbsp;<input type='button' value='"._CANCEL."' onclick='persbox.fade();'/>";
 
     $date =getSQLDate();

	$ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>"
           . ($objid=="new"?"Add Measure Intent":"Add Measure Intent")
           . "</div>"
           . "<div style='padding:5px;background-color:#f2f2f2;color:#555;'>"
              . "<div style='max-height:85px;height:85px;overflow:auto;border:1px solid #999;background-color:#fff;padding:4px;' id='frmobj'>"
                  . "<table style='width:100%;border-spacing:0px;'><tbody><tr><td style='height:85px;'>"
                     . "<table class='xxfrm' style='width:100%;'><tbody>"     
						. "<tr><td>Code Intent  </td><td><input type='text' name='intent_code' id='intent_code' value='$intent_code' style='width:400px;'/> </td><td>"
                       . "<tr><td>Measure Intent Description</td><td><input type='text' name='intent_description' id='intent_description' value='$intent_description' style='width:400px;'/></td></tr>"
					   
                       . "</tbody></table>"
	                . "</td></tr></tbody></table>"
              . "</div>"
           . "</div>"
           . "<div style='text-align:center;padding:10px;background-color:#f2f2f2;height:100px;' id='frmbtn'>"
           . $btn
           . "</div>";
      
      return $ret;
	  }
	  
	   #FREQUENCY
	  elseif( $id_inp ==3)
	  {
				$btn = "<input type='button' value='"._ADD."' onclick='save_measure(\"$idobj\",\"$id_inp\");'/>&nbsp;&nbsp;"
              . "&nbsp;<input type='button' value='"._CANCEL."' onclick='persbox.fade();'/>";
 
     $date =getSQLDate();

	$ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>"
           . ($objid=="new"?"Add Frequency":"Add Frequency")
           . "</div>"
           . "<div style='padding:5px;background-color:#f2f2f2;color:#555;'>"
              . "<div style='max-height:85px;height:85px;overflow:auto;border:1px solid #999;background-color:#fff;padding:4px;' id='frmobj'>"
                  . "<table style='width:100%;border-spacing:0px;'><tbody><tr><td style='height:85px;'>"
                     . "<table class='xxfrm' style='width:100%;'><tbody>"     
						. "<tr><td>Code Frequency  </td><td><input type='text' name='frequency_code' id='frequency_code' value='$frequency_code' style='width:400px;'/> </td><td>"
                       . "<tr><td>Frequency Description</td><td><input type='text' name='frequency_description' id='frequency_description' value='$frequency_description' style='width:400px;'/></td></tr>"
					   
                       . "</tbody></table>"
	                . "</td></tr></tbody></table>"
              . "</div>"
           . "</div>"
           . "<div style='text-align:center;padding:10px;background-color:#f2f2f2;height:100px;' id='frmbtn'>"
           . $btn
           . "</div>";
      
      return $ret;
  }
  
  	   #TARGET
	  elseif( $id_inp ==4)
	  {
				$btn = "<input type='button' value='"._ADD."' onclick='save_measure(\"$idobj\",\"$id_inp\");'/>&nbsp;&nbsp;"
              . "&nbsp;<input type='button' value='"._CANCEL."' onclick='persbox.fade();'/>";
 
     $date =getSQLDate();

	$ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>"
           . ($objid=="new"?"Add Target":"Add Target")
           . "</div>"
           . "<div style='padding:5px;background-color:#f2f2f2;color:#555;'>"
              . "<div style='height: 125px; max-height: 150px;overflow:auto;border:1px solid #999;background-color:#fff;padding:4px;' id='frmobj'>"
                  . "<table style='width:100%;border-spacing:0px;'><tbody><tr><td style='height:85px;'>"
                     . "<table class='xxfrm' style='width:100%;'><tbody>"     
						. "<tr><td>Code Frequency  </td><td><input type='text' name='target_code' id='target_code' value='$target_code' style='width:400px;'/> </td><td>"
                       . "<tr><td>Target High</td><td><input type='text' name='target_high' id='target_high' value='$target_high' style='width:400px;'/></td></tr>"
                       . "<tr><td>Target Medium</td><td><input type='text' name='target_medium' id='target_medium' value='$target_medium' style='width:400px;'/></td></tr>"
                       . "<tr><td>Target Low</td><td><input type='text' name='target_low' id='target_low' value='$target_low' style='width:400px;'/></td></tr>"
					   
                       . "</tbody></table>"
	                . "</td></tr></tbody></table>"
              . "</div>"
           . "</div>"
           . "<div style='text-align:center;padding:10px;background-color:#f2f2f2;height:100px;' id='frmbtn'>"
           . $btn
           . "</div>";
      
      return $ret;
  }
	  
	  
	  
	  
	  
	  
	  
	  
   }
  
    /// Made by Deni 
    function app_newKsf($args) {
        $db=&Database::getInstance();
        $sql = "SELECT MAX(id) FROM sms_objective_ksf";
        $result = $db->query($sql);
        list($idx)=$db->fetchRow($result);
        $id = $idx+1;
        $idobj = $args[0];
        $create_user_id = getUserID();
        $create_date = getSQLDate();
        $sql = "INSERT INTO sms_objective_ksf (id,id_objective,create_user_id,create_date)"
             . " VALUES('$id','$idobj','$create_user_id','$create_date')";
        $db->query($sql);
        
        $hdr = "<table><colgroup><col width='60' /><col /><col /></colgroup><tbody>"
             . "<tr id='trclass_${ksf_id}'>"
             . "<td id='td_code_${ksf_id}'></td>"
             . "<td id='td_title_${ksf_id}'></td>"
             . "<td id='td_target_${ksf_id}' colspan='3'></td>"
             . "</tr></tbody></table>";
        return array($id,$hdr,$idobj);
     }
     
     function app_saveKsf($args) {
        $db=&Database::getInstance();
        $create_user_id = getUserID();
        $id = $args[0];
        $idobj = $args[1];
        $vars = parseForm($args[2]);
        
         
        $code_ksf = addslashes(trim($vars["code_ksf"]));
        $title_ksf = addslashes(trim($vars["title_ksf"]));
        $target_ksf = addslashes(trim($vars["target_ksf"]));
        
       
        if($title_ksf=="") {
           $title_ksf = "noname";
        }

        if($id=="new") {
           $sql = "SELECT MAX(id) FROM sms_objective_ksf";
           $result = $db->query($sql);
           list($idx)=$db->fetchRow($result);
           $id = $idx+1;
           $create_user_id = getUserID();
           $create_date = getSQLDate();
           $sql = "INSERT INTO sms_objective_ksf (id_objective,ksf_code,ksf_title,ksf_target)"
                . " VALUES($idobj','$code_ksf','$title_ksf')";
           $db->query($sql);
        } else {
           $sql = "UPDATE sms_objective_ksf SET ksf_code = '$code_ksf' , ksf_title = '$title_ksf',"
                . "ksf_target = '$target_ksf'"
                . " WHERE id = '$id'";
           $db->query($sql);
        }
        
        return array($idobj,$code_ksf,$title_ksf,$target_ksf);
     }
     
     function app_editKsf($args) {
        $db=&Database::getInstance();
        $id = $args[0];
        $idobj = $args[1];
        if($id=="new") {
           $generate = "";
        } else {
           $sql = "SELECT ksf_code,ksf_title,ksf_target"
                . " FROM sms_objective_ksf"
                . " WHERE id = '$id'";
           $result = $db->query($sql);
           
           list($code_ksf,$title_ksf,$target_ksf)=$db->fetchRow($result);
           $title_ksf = htmlentities($title_ksf,ENT_QUOTES);
        }
        $ret = "<td colspan='12'><div style='width: 870px;'><form id='frm'><table class='xxfrm' style='width:100%;'><colgroup><col width='160'/><col/></colgroup><tbody>"
             . "<tr><td>Code KSF</td><td><input type='text' value=\"$code_ksf\" id='inp_ksf_code' name='code_ksf' style='width:60%;'/></td></tr>"
             . "<tr><td>Title KSF</td><td><input type='text' value=\"$title_ksf\" id='inp_ksf_title' name='title_ksf' style='width:60%;'/></td></tr>"
             . "<tr><td>Target KSF</td><td><input type='text' value=\"$target_ksf\" id='inp_ksf_target' name='target_ksf' style='width:60%;'/></td></tr>"
             
             . "<tr><td colspan='2'><span id='progress'></span>&nbsp;"
             . "<input id='btn_save_ksf' onclick='save_ksf(\"$idobj\");' type='button' value='"._SAVE."'/>&nbsp;"
             . "<input id='btn_cancel_edit' onclick='cancel_edit();' type='button' value='"._CANCEL."'/>&nbsp;&nbsp;"
             . ($id!="new"?"<input id='btn_delete_ksf' onclick='delete_ksf();' type='button' value='"._DELETE."'/>":"")
             . "</td></tr>"
             . "</tbody></table></form></div></td>";
        return $ret;
     }
     
     function app_deleteKsf($args) {
        $db=&Database::getInstance();
        $id = $args[0];
        $sql = "DELETE FROM sms_objective_ksf WHERE id = '$id'";
        $db->query($sql);
     }

      function app_deletePicObj($args) {
      $db=&Database::getInstance();
      $id = $args[0];
      $sql = "DELETE FROM sms_objective_team WHERE id = '$id'";
      $db->query($sql);
      
   }

   function app_savePicObj($args) {
      $db=&Database::getInstance();
      $vars = _parseForm($args[0]);

      $option_pic = ($vars["option_pic"]);
      $ksf_id = $vars["ksf_id"];
      $id = $vars["id"];

      if($id=="new") {
         $create_user_id = getUserID();
         $create_date = getSQLDate();
         $sql = "SELECT MAX(id) FROM sms_objective_team";
         $result = $db->query($sql);
         list($id)=$db->fetchRow($result);
         $id++;
         $sql = "INSERT INTO sms_objective_team (id,id_objective_ksf,id_pic,create_user_id,create_date) VALUES ('$id','$ksf_id','$option_pic','$create_user_id','$create_date')";
         $db->query($sql);
      } else {
        $sql = "UPDATE sms_objective_team SET "
           . "id_pic = '".addslashes($vars["option_pic"])."',"
           . " WHERE id = '$id'";
        $db->query($sql);
      }

      return array($id,$ksf_id,$option_pic,$create_user_id,$create_date);
   }

   function app_editPicObj($args) {
      $db=&Database::getInstance();
      $id = $args[0];
      $ksf_id = $args[1];

      if($id=="new") {
         $tm_start = getSQLDate();
         $tm_stop = getSQLDate();

         $title = "Add PIC";
         $btn = "<input type='button' value='Add New' onclick='save_picobj(\"$id\",\"$ksf_id\",this,event);'/>&#160;&#160;"
              . "<input type='button' value='"._CANCEL."' onclick='editpicobjbox.fade();'/>";
      } else {
         $title = "Edit PIC";
         $sql = "SELECT id_objective_ksf, id_pic"
              . " FROM sms_objective_team WHERE id = '$id'";
         $result = $db->query($sql);
         list($id_objective_ksf,$id_pic)=$db->fetchRow($result);
         //$btn = "<input type='button' value='"._SAVE."' onclick='save_picobj(\"$id\",\"$id_objective_ksf\",this,event);'/>&#160;&#160;"
            $btn =  "<input type='button' value='"._DELETE."' onclick='delete_picobj(\"$id\",this,event);'/>"
              . "<input style='margin-left:10px;' type='button' value='"._CANCEL."' onclick='editpicobjbox.fade();'/>&#160;&#160;&#160;";
      }
    
        $sql = "SELECT a.employee_id,a.alias_nm,a.person_id,"
       . "b.person_nm,d.job_id,e.org_id,f.org_class_id "
       . " FROM ".XOCP_PREFIX."employee a"
       . " LEFT JOIN ".XOCP_PREFIX."persons b USING(person_id)"
       . " LEFT JOIN ".XOCP_PREFIX."employee_job c USING(employee_id)"
       . " LEFT JOIN ".XOCP_PREFIX."jobs d ON d.job_id = c.job_id"
       . " LEFT JOIN ".XOCP_PREFIX."orgs e USING(org_id)"
       . " LEFT JOIN ".XOCP_PREFIX."org_class f USING(org_class_id)"
       . " WHERE a.status_cd = 'normal'"
       . " ORDER BY b.person_nm ASC";
      $result = $db->query($sql);
      $picnum = $db->getRowsNum($result);
      $optpic = "";
      $tdpic = "";
      $i = 0;
      $picempid = explode(",", $sms_action_plan_pic_employee_id);
      if($db->getRowsNum($result)>0) {
         while(list($employee_idx,$alias_nmx,$person_idx,$person_nmx)=$db->fetchRow($result)) {
            $arscrh = array_search($employee_idx, $picempid);
            if ($alias_nmx != "") {
              $alias_nmxs = $alias_nmx." - ";
            }else{
              $alias_nmxs = "";
            }
            $optpic .= "<option value='$employee_idx' ".($employee_idx==$id_pic?"selected='selected'":"").">".($arscrh > -1?"checked":"")."".htmlentities("$person_nmx")."</option>";
            $i++;
         }
      }
      
      $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>"
           . $title
           . "</div>"
           . "<div style='padding:5px;background-color:#f2f2f2;color:#555;'>"
           . "<div style='border:1px solid #999;background-color:#fff;padding:4px;' id='frmpicobj'>"
           . "<select name='option_pic'>$optpic</select>"
           . "<input type='hidden' name='id' value='$id'>"
           . "<input type='hidden' name='ksf_id' value='$ksf_id'>"
           . "</div>"
           . "</div>"
           . "<div style='text-align:center;padding:10px;background-color:#f2f2f2;height:100px;' id='frmbtn'>"
           . $btn
           . "</div>";
      
      return $ret;
   }
   /// End of Made by Denimaru
   
   
}

} /// HRIS_ASSESSMENTSESSIONAJAX_DEFINED
?>