<?php
//--------------------------------------------------------------------//
// Filename : modules/sms/class/ajax_smssession.php                  //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2013-10-10                                              //                                                     //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_SMSSESSIONAJAX_DEFINED') ) {
   define('HRIS_SMSSESSIONAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");
require_once(XOCP_DOC_ROOT."/modules/sms/modconsts.php");


class _sms_class_SMSSessionAjax extends AjaxListener {
   
   function __construct($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/sms/class/ajax_smssession.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_Delete","app_editSession","app_saveSession",
                                             "app_setSMSSession","app_newSession","app_editTarget","app_saveTarget","app_saveJAMTargetText");
   }
   
   function app_setSMSSession($args) {
      $_SESSION["sms_id"] = $args[0];
   }
   
   function app_newSession($args) {
      $db=&Database::getInstance();
      $sql = "SELECT MAX(id) FROM sms_session";
      $result = $db->query($sql);
      list($idx)=$db->fetchRow($result);
      $id = $idx+1;
      $create_user_id = getUserID();
      $periode_session = date("Y");
      $create_date = getSQLDate();
      $sql = "INSERT INTO sms_session (id,periode_session,title_session,create_user_id,create_date)"
           . " VALUES('$id','$periode_session','$title_session','$create_user_id','$create_date')";
      $db->query($sql);
      
      $hdr = "<table><colgroup><col width='60'/><col/></colgroup><tbody><tr>"
           . "<td id='td_periode_${id}'>$periode_session</td>"
           . "<td><span id='sp_${id}' class='xlnk' onclick='edit_session(\"$id\",this,event);'>"._EMPTY."</span></td>"
           . "</tr></tbody></table>";
      return array($id,$hdr);
   }
   
   function app_saveSession($args) {
      $db=&Database::getInstance();
      $create_user_id = getUserID();
      $id = $args[0];
      $vars = parseForm($args[1]);
      $title_session = addslashes(trim($vars["title_session"]));
      $periode_session = _bctrim(bcadd(0,$vars["periode_session"]));
     
	 if($title_session=="") {
         $title_session = "noname";
      }
      if($id=="new") {
         $sql = "SELECT MAX(id) FROM sms_session";
         $result = $db->query($sql);
         list($idx)=$db->fetchRow($result);
         $id = $idx+1;
         $create_user_id = getUserID();
         $sql = "INSERT INTO sms_session (id,periode_session,title_session,create_user_id)"
              . " VALUES('$id','$periode_session','$title_session','$create_user_id')";
         $db->query($sql);
      } else {
         $sql = "UPDATE sms_session SET title_session = '$title_session',"
              . "periode_session = '$periode_session' "
              . " WHERE id = '$id'";
         $db->query($sql);
      }
      
      return array($id,$title_session,$periode_session);
   }
   
   function app_editSession($args) {
      $db=&Database::getInstance();
      $id = $args[0];
      if($id=="new") {
         $generate = "";
      } else {
         
         $sql = "SELECT title_session,periode_session"
              . " FROM sms_session"
              . " WHERE id = '$id'";
         $result = $db->query($sql);
         
         list($title_session,$periode_session)=$db->fetchRow($result);
         $title_session = htmlentities($title_session,ENT_QUOTES);
      }
      $ret = "<form id='frm'><table class='xxfrm' style='width:100%;'><colgroup><col width='160'/><col/></colgroup><tbody>"
           . "<tr><td>Periode Session</td><td><input type='text' value=\"$periode_session\" id='inp_session_periode' name='periode_session' style='width:60%;'/></td></tr>"
           . "<tr><td>Title Session</td><td><input type='text' value=\"$title_session\" id='inp_session_title' name='title_session' style='width:60%;'/></td></tr>"
           
           . "<tr><td colspan='2'><span id='progress'></span>&nbsp;"
           . "<input id='btn_save_session' onclick='save_session();' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input id='btn_cancel_edit' onclick='cancel_edit();' type='button' value='"._CANCEL."'/>&nbsp;&nbsp;"
           . ($id!="new"?"<input id='btn_delete_session' onclick='delete_session();' type='button' value='"._DELETE."'/>":"")
           . "</td></tr>"
           . "</tbody></table></form>";
      return $ret;
   }
   
   function app_Delete($args) {
      $db=&Database::getInstance();
      $id = $args[0];
      $sql = "DELETE FROM sms_session WHERE id = '$id'";
      $db->query($sql);
   }
  
 #FUNCTION MATRIX
 
  function app_saveJAMTargetText($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $val = addslashes($args[0]);
      $id_ksf = $args[1];
      $no = $args[2];
     
	if($no == 0) { 
	  $sql = "UPDATE sms_objective_ksf SET ksf_lower_perform = '$val' WHERE id = '$id_ksf'";
      $db->query($sql); }
	elseif ($no == 1) {
	  $sql = "UPDATE sms_objective_ksf SET ksf_need_improvement = '$val' WHERE id = '$id_ksf'";
       $db->query($sql); }
	elseif ($no == 2) {
	  $sql = "UPDATE sms_objective_ksf SET ksf_target = '$val' WHERE id = '$id_ksf'";
       $db->query($sql); }
	elseif ($no == 3) {
	  $sql = "UPDATE sms_objective_ksf SET ksf_req = '$val' WHERE id = '$id_ksf'";
       $db->query($sql); }
	elseif ($no == 4) {
	  $sql = "UPDATE sms_objective_ksf SET ksf_far_req = '$val' WHERE id = '$id_ksf'";
       $db->query($sql); }
	
	# SAVE WEIGHT
	elseif ($no == 5) {
	
	  $sql = "UPDATE sms_objective SET objective_weight = '$val' WHERE id = '$id_ksf'";
       $db->query($sql); }

#FUNCTIONAL	   
	elseif ($no == 6) {
	  $sql = "UPDATE sms_action_plan SET ap_lower_perform = '$val' WHERE sms_action_plan_id = '$id_ksf'";
       $db->query($sql); }
	elseif ($no == 7) {
	
	  $sql = "UPDATE sms_action_plan SET ap_need_improvement = '$val' WHERE sms_action_plan_id = '$id_ksf'";
       $db->query($sql); }
	elseif ($no == 8) {
	
	   $sql = "UPDATE sms_kpi SET sms_kpi_target_text = '$val' WHERE sms_kpi_id = '$id_ksf'";
       $db->query($sql); }
	elseif ($no == 9) {
	
	  $sql = "UPDATE sms_action_plan SET ap_req = '$val' WHERE sms_action_plan_id = '$id_ksf'";
       $db->query($sql); }
	elseif ($no == 10) {
	
	  $sql = "UPDATE sms_action_plan SET ap_far_req = '$val' WHERE sms_action_plan_id = '$id_ksf'";
       $db->query($sql); }	   

	   #WGT FUNCTIONAL
	   
	   	elseif ($no == 11) {
	
	  $sql = "UPDATE sms_section_objective SET weight = '$val' WHERE id = '$id_ksf'";
       $db->query($sql); }
 
	/*    	  $sql = "UPDATE sms_objective_ksf SET "
        . "ksf_lower_perform = '".addslashes($vars["ksf_lower_perform"])."',"
        . "ksf_need_improvement = '".addslashes($vars["ksf_need_improvement"])."',"
        . "ksf_target = '".addslashes($vars["ksf_target"])."',"
        . "ksf_req = '".addslashes($vars["ksf_req"])."',"
		. "ksf_far_req = '".addslashes($vars["ksf_far_req"])."'"
        . " WHERE id = '$id_ksf'";
       $db->query($sql); */
	}
 
   function app_editTarget($args) {
      $db=&Database::getInstance();
      $id_ksf = $args[0];
      if($id_ksf=="new") {
         $btn = "<input type='button' value='"._ADD."' onclick='save_perspective();'/>&nbsp;&nbsp;"
              . "&nbsp;<input type='button' value='"._CANCEL."' onclick='persbox.fade();'/>";
      } else {
		$sqlksf = "SELECT  a.id,a.ksf_code,a.ksf_title,a.ksf_lower_perform,a.ksf_need_improvement,a.ksf_target,a.ksf_req,a.ksf_far_req  FROM sms_objective_ksf a JOIN sms_objective_team b ON(a.id =  b.id_objective_ksf) WHERE a.id= $id_ksf";
		$resultksf = $db->query($sqlksf);						
		list($id_ksf,$ksf_code,$ksf_title,$ksf_lowper,$ksf_needimp,$ksf_target,$ksf_req,$ksf_far_req)=$db->fetchRow($resultksf);        
         $ksf_lowper = htmlentities($ksf_lowper,ENT_QUOTES);
         $ksf_needimp = htmlentities($ksf_needimp,ENT_QUOTES);
         $ksf_target = htmlentities($ksf_target,ENT_QUOTES);
         $ksf_req = htmlentities($ksf_req,ENT_QUOTES);
         $ksf_far_req = htmlentities($ksf_far_req,ENT_QUOTES);
         $btn = "<input type='button' value='"._SAVE."' onclick='save_ksf();'/>&nbsp;&nbsp;"
              . "<input type='button' value='"._CANCEL."' onclick='persbox.fade();'/>&nbsp;&nbsp;&nbsp;&nbsp;";
      }
                
      $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>"
           . ($id_ksf=="new"?"Add KSF":"Edit KSF $id_ksf")
           . "</div>"
           . "<div style='padding:5px;background-color:#f2f2f2;color:#555;'>"
              . "<div style='max-height:235px;height:345px;overflow:auto;border:1px solid #999;background-color:#fff;padding:4px;' id='frmperspective'>"
                  . "<table style='width:100%;border-spacing:0px;'><tbody><tr><td style='height:215px;'>"
                     . "<table class='xxfrm' style='width:100%;'><tbody>"
                        . "<tr><td>Lower performer</td><td><input type='text' name='ksf_lower_perform' id='ksf_lower_perform' value='$ksf_lowper' style='width:95%;'/></td></tr>"
                        . "<tr><td>Still need improvement</td><td><input type='text' name='ksf_need_improvement' id='ksf_need_improvement' value='$ksf_needimp' style='width:95%%;'/></;'/></td></tr>"
                        . "<tr><td>Fulfill standard of work performance</td><td><input type='text' name='ksf_target' id='ksf_target' value='$ksf_target' style='width:95%;'/></;'/></td></tr>"
                        . "<tr><td>Exceed required performance</td><td><input type='text' name='ksf_req' id='ksf_req' value='$ksf_req' style='width:95%;'/></;'/></td></tr>"
                        . "<tr><td>Far exceed required performance</td><td><input type='text' name='ksf_far_req' id='ksf_far_req' value='$ksf_far_req' style='width:95%;'/></;'/></td></tr>"

                       . "</tbody></table>"
                     . "<input type='hidden' name='id_ksf' id='id_ksf' value='$id_ksf'/>"
                  . "</td></tr></tbody></table>"
              . "</div>"
           . "</div>"
           . "<div style='text-align:center;padding:10px;background-color:#f2f2f2;height:100px;' id='frmbtn'>"
           . $btn
           . "</div>";
     
	  
      return $ret;
   }

    function app_saveTarget($args) {
      $db=&Database::getInstance();
      $vars = _parseForm($args[0]);
	  $user_id = getUserID();
      $date = getSQLDate();
	  $id_ksf = $vars["id_ksf"];
	
      $sql = "UPDATE sms_objective_ksf SET "
        . "ksf_lower_perform = '".addslashes($vars["ksf_lower_perform"])."',"
        . "ksf_need_improvement = '".addslashes($vars["ksf_need_improvement"])."',"
        . "ksf_target = '".addslashes($vars["ksf_target"])."',"
        . "ksf_req = '".addslashes($vars["ksf_req"])."',"
		. "ksf_far_req = '".addslashes($vars["ksf_far_req"])."'"
        . " WHERE id = '$id_ksf'";
       $db->query($sql);
    
	  $ksf_lowper = $vars["ksf_lower_perform"];
	  $ksf_needimp = $vars["ksf_need_improvement"];
	  $ksf_target = $vars["ksf_target"];
	  $ksf_req = $vars["ksf_req"];
	  $ksf_far_req = $vars["ksf_far_req"];

        
      return array($id_ksf,$ksf_lowper,$ksf_needimp,$ksf_target,$ksf_req,$ksf_far_req);
   }
  
}

} /// HRIS_ASSESSMENTSESSIONAJAX_DEFINED
?>