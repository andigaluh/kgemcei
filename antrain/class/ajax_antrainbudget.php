<?php
//--------------------------------------------------------------------//
// Filename : modules/antrain/class/ajax_antrainbudget.php                  //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-12-17                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_ANTRAINBUDGETAJAX_DEFINED') ) {
   define('HRIS_ANTRAINBUDGETAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");
require_once(XOCP_DOC_ROOT."/modules/antrain/modconsts.php");


class _antrain_class_ANTRAINBudgetAjax extends AjaxListener {
   
   function __construct($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/antrain/class/ajax_antrainbudget.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_Delete","app_editSession","app_saveSession",
                                             "app_setANTRAINBudget","app_newSession","app_import_db");
   }
   
   function app_setANTRAINBudget($args) {
      $_SESSION["antrain_id"] = $args[0];
   }
   
   function app_newSession($args) {
      $db=&Database::getInstance();
	  $sql = "SELECT p.id, p.id_global_session, p.org_id, p.title, p.id_exc_rate  FROM antrain_budget p"
			  . " WHERE id = ( SELECT MAX(id) FROM antrain_budget ) ORDER BY id DESC" ;
      $result = $db->query($sql);
      list($idx,$id_global_session,$org_id,$title,$id_exc_rate)=$db->fetchRow($result);
      $id = $idx+1;
	  $title = addslashes(trim($vars["title"]));
	  $budget = addslashes(trim($vars["budget"]));
	  $id_created = 0;
	  $date_created = getSQLDate();
	  /*$sql = "INSERT INTO antrain_budget (id, id_global_session, org_id, title, create_date, create_user_id)"
           . " VALUES('$id','$id_global_session','$org_id','$title','$date_created','$id_created')";
      $db->query($sql);
      $sql = "SELECT id FROM antrain_budget"
			  . " WHERE id = ( SELECT MAX(id) FROM antrain_budget ) ORDER BY id DESC" ;
      $result = $db->query($sql);
      list($last_id)=$db->fetchRow($result);
      $id_hris_budget = $last_id;
      $sql = "SELECT psid FROM antrain_sessionss WHERE psid = (SELECT MAX(psid) FROM antrain_sessionss) ORDER BY psid DESC";
      $result = $db->query($sql);
      list($psidx)=$db->fetchRow($result);
      $psid = $psidx+1;
      $sql = "INSERT INTO antrain_sessionss (psid,id_hris_budget) VALUES ('$psid','$id_hris_budget')";
      $db->query($sql);
      $sql = "SELECT id FROM hris_global_sess WHERE id = '$id_global_session'";
      $result = $db->query($sql);
      list($idx)=$db->fetchRow($result);*/

     $id = "new";
	
     $hdr = "";
      return array($id,$hdr,$last_id);
   }
   
   function app_saveSession($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $id = $args[0];
      $vars = parseForm($args[1]);
      $id_global_session = _bctrim(bcadd(0,$vars["id_global_session"]));
	  $org_id = _bctrim(bcadd(0,$vars["org_id"]));
	  $id_exc_rate = _bctrim(bcadd(0,$vars["id_exc_rate"]));
	  $budget_specific = _bctrim(bcadd(0,$vars["budget_specific"]));
	  $budget_general = _bctrim(bcadd(0,$vars["budget_general"]));
	  $id_created = getUserID();
	  $id_modified = getUserID();
	  $date_created = getSQLDate($vars["date_created"]);
	  $date_modified =  getSQLDate($vars["date_modified"]);
	  $exist = 0;

	  $sql = "SELECT org_nm FROM hris_orgs WHERE org_id = '$org_id'";
	  $result = $db->query($sql);
	  list($org_nm) = $db->fetchRow($result);

	  $title = $id_global_session." - ".$org_nm;

	  //$id_awal = $org_id_sec;
      if($id=="new") {
      	  $sql = "SELECT * FROM antrain_budget WHERE id_global_session = '$id_global_session' AND org_id = '$org_id' AND status_cd = 'normal'";
      	  $result = $db->query($sql);
      	  if ($db->getRowsNum($result)) {
      	  	$exist = 1;
      	  }

      	  if ($exist == 0) {
      	  $sql = "SELECT id FROM antrain_budget"
			  . " WHERE id = ( SELECT MAX(id) FROM antrain_budget ) ORDER BY id DESC" ;
	      $result = $db->query($sql);
	      list($idx)=$db->fetchRow($result);
	      $id = $idx+1;
      	  $sql = "INSERT INTO antrain_budget (id, id_global_session, org_id, title, id_exc_rate, budget_general, budget_specific, create_date, create_user_id)"
	           . " VALUES('$id','$id_global_session','$org_id','$title','$id_exc_rate','$budget_general','$budget_specific','$date_created','$id_created')";
	      $result = $db->query($sql);
	      $sql = "SELECT id FROM antrain_budget"
				  . " WHERE id = ( SELECT MAX(id) FROM antrain_budget ) ORDER BY id DESC" ;
	      $result = $db->query($sql);
	      list($last_id)=$db->fetchRow($result);
	      $id_hris_budget = $last_id;
	      $sql = "SELECT psid FROM antrain_sessionss WHERE psid = (SELECT MAX(psid) FROM antrain_sessionss) ORDER BY psid DESC";
	      $result = $db->query($sql);
	      list($psidx)=$db->fetchRow($result);
	      $psid = $psidx+1;
	      $sql = "INSERT INTO antrain_sessionss (psid,id_hris_budget,date_created,id_created) VALUES ('$psid','$id_hris_budget','$date_created','$id_created')";
	      $db->query($sql);
	      $sql = "INSERT INTO antrain_sessionreq (psid,id_hris_budget,date_created,id_created) VALUES ('$psid','$id_hris_budget','$date_created','$id_created')";
	      $db->query($sql);
	      $sql = "SELECT id FROM hris_global_sess WHERE id = '$id_global_session'";
	      $result = $db->query($sql);
	      list($idy)=$db->fetchRow($result);
      	  }
      } else {
         $sql = "UPDATE antrain_budget SET "
              . "id_global_session = '$id_global_session', org_id = '$org_id', title = '$title', id_exc_rate = '$id_exc_rate',  budget_general = '$budget_general',  budget_specific = '$budget_specific', modify_user_id = '$id_modified', modify_date = '$date_modified'"			  
              . " WHERE id = '$id'";
         $result = $db->query($sql);
	  }
			 
	      

		  $sql = "SELECT id_global_session,org_id,budget_specific FROM antrain_budget WHERE id = '$id' AND status_cd = 'normal'";
      	  $result = $db->query($sql);
      	  list($id_global_session,$org_id,$budget_specific) = $db->fetchRow($result);

      	  $sqlnm = "SELECT org_nm FROM hris_orgs WHERE org_id= '$org_id'";
		  $resultnm = $db->query($sqlnm);
		  list($org_nm)=$db->fetchRow($resultnm);

		  /*$hdr = "<table><colgroup><col width='60'/><col/></colgroup><tbody><tr>"
			  . "<td width=200><span id='sp_${id}' class='xlnk' onclick='edit_session(\"$id\",this,event);' >$title</span></td>"
			  . "</tr></tbody></table>";*/

		  $hdr = "<table><colgroup><col width='70'/><col/></colgroup><tbody><tr>"
                  . "<td style='text-align: left;width:93px;' ><span id='sp_${id}' class='xlnk' onclick='edit_session(\"$id\",this,event); '>".htmlentities(stripslashes($id_global_session))."</span></td>"
                  . "<td style='text-align: left;width:300px;'>".htmlentities(stripslashes($org_nm))."</td>"
                  . "<td style='text-align: left;width:100px;'>$ ".toMoney($budget_specific)."</td>"
                  . "</tr></tbody></table>";
	  
      return array($id,$org_nm,$title,$id_global_session,$exist,$hdr);
   }
   
   function app_editSession($args) {
      $db=&Database::getInstance();
      $id = $args[0];
      $sessionopt = "";
      $excopt = "";
      if($id=="new") {
         $date_created  = getSQLDate();
		 $id_created = getUserID();
         $generate = "";
      } else {
        $id_modified = getUserID();
		$date_modified  = getSQLDate();
		$sql =  "SELECT id_global_session, org_id, title,id_exc_rate, budget_general, budget_specific FROM antrain_budget"
			  . " WHERE id = '$id'";
	    $result = $db->query($sql);
	   list($id_global_session,$org_id,$title,$id_exc_rate,$budget_general,$budget_specific)=$db->fetchRow($result);
       $id_global_session = htmlentities($id_global_session,ENT_QUOTES);
      }

	      $sqlds = "SELECT org_id, org_nm FROM hris_orgs WHERE org_class_id =  '4' AND status_cd = 'normal'";
			$resultds = $db->query($sqlds);

			$sqlgs = "SELECT id FROM hris_global_sess WHERE status_cd = 'normal'";
			$resultgs = $db->query($sqlgs);

		while (list($idx)=$db->fetchRow($resultgs)) {
			if ($id_global_session == $idx) {
				$selected1 = "selected";
			}else{
				$selected1 = "";
			}
			$sessionopt .= "<option value=\"$idx\" $selected1>$idx</option>";
		}

		$sqlsess = "SELECT id,id_global_session FROM antrain_exc_rate WHERE status_cd = 'normal'";
		$resultexc = $db->query($sqlsess);

		while (list($idy,$id_global_sessiony)=$db->fetchRow($resultexc)) {
			if ($id_global_session == $idy) {
				$selected1 = "selected";
			}else{
				$selected1 = "";
			}

			$excopt .= "<option value=\"$idy\" $selected1>$id_global_sessiony</option>";
		}
	    
  	  $sqlselected = "SELECT org_id FROM antrain_budget WHERE id = $id";
	  $resultselect = $db->query($sqlselected);
	  list($sel_org)=$db->fetchRow($resultselect);

	$ret = "<form id='frm'><table class='xxfrm' style='width:100%;'><colgroup><col width='160'/><col/></colgroup><tbody>"
         . "<tr><td>Session</td><td><select id='inp_global_session' name='id_global_session' style='width:300px;'>$sessionopt</select></td></tr>" 
	     . "<tr><td>Section</td><td><select id='inp_org_id' name='org_id' onchange='hrfilter();'>";
		
		while(list($org_idx,$org_nmx)=$db->fetchRow($resultds)){
		
			if($org_idx == $org_id )
			{
				$selected2= 'selected';
			}
			else
			{
				$selected2 = ''; 
			}
			
			$ret .= "<option value=\"$org_idx\" $selected2>$org_nmx</option>";
		
		}

		$ret .= "<tr><td>Exchange Rate</td><td><select id='inp_exc' name='id_exc_rate' style='width:300px;'>$excopt</select></td></tr>";
		$ret .= "<tr><td>Budget Specific ($)</td><td><input type='text' value=\"$budget_specific\" id='inp_budget_specific' name='budget_specific' style='width:150px;'/></td></tr>"
		      . "<tr id='tr_budget_general'><td>Budget General ($)</td><td><input type='text' value=\"$budget_general\" id='inp_budget_general' name='budget_general' style='width:150px;'/></td></tr>"; 
          
		$ret .= "<tr><td colspan='2'><span id='progress'></span>&nbsp;"
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
      $user_id = getUserID();
      $sql = "UPDATE antrain_budget SET status_cd = 'nullified' WHERE id = '$id'";
      $db->query($sql);
      $sql = "UPDATE antrain_sessionss SET status_cd = 'nullified' WHERE id_hris_budget = '$id'";
      $db->query($sql);
   }
   
}

} /// HRIS_ASSESSMENTSESSIONAJAX_DEFINED
?>