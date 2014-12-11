<?php
//--------------------------------------------------------------------//
// Filename : modules/antrain/class/ajax_antrainsession.php                  //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-12-17                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_ANTRAINEXCRATEAJAX_DEFINED') ) {
   define('HRIS_ANTRAINEXCRATEAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");
require_once(XOCP_DOC_ROOT."/modules/antrain/modconsts.php");


class _antrain_class_ANTRAINExcrateAjax extends AjaxListener {
   
   function __construct($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/antrain/class/ajax_antrainexcrate.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_Delete","app_editSession","app_saveSession",
                                             "app_setANTRAINExcrate","app_newSession");
   }
   
   function app_setANTRAINExcrate($args) {
      $_SESSION["antrain_psid"] = $args[0];
   }
   
   function app_newSession($args) {
    $db=&Database::getInstance();
	  $sql = "SELECT id,id_global_session,rupiah FROM antrain_exc_rate"
			  . " WHERE id = ( SELECT MAX(id) FROM antrain_exc_rate ) ORDER BY id DESC" ;
      $result = $db->query($sql);
      list($idx,$id_global_session,$rupiah)=$db->fetchRow($result);
      $id = $idx+1;
      $sql = "SELECT id FROM hris_global_sess WHERE id = '$id_global_session'";
      $result = $db->query($sql);
      list($idx)=$db->fetchRow($result);
      $id_global_session = $idx;
    $id_global_session = addslashes(trim($vars["id_global_session"]));  
	  $rupiah = addslashes(trim($vars["rupiah"]));
	  $create_user_id = getUserID();;
	  $create_date = getSQLDate();
	  $sql = "INSERT INTO antrain_exc_rate (id,id_global_session,rupiah,create_date,create_user_id)"
           . " VALUES('$id','$id_global_session','$rupiah','$create_date','$create_user_id')";
   // $db->query($sql);
    $id = "new";
	
     $hdr = "<table><colgroup><col width='60'/><col/></colgroup><tbody><tr>"
			  . "<td width=150><span id='sp_${id}' class='xlnk' onclick='edit_session(\"$id\",this,event);' >$id_global_session</span></td>"
			  //. "<td id='td_title_${id}' width=125 style='text-align: left;'>$dollar</td>"
			  . "<td id='td_dollar_${id}' width=200 style='text-align: left;'>$rupiah</td>"
			  . "</tr></tbody></table>";
      return array($id,$hdr);
   }
   
   function app_saveSession($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $id = $args[0];
      $vars = parseForm($args[1]);
      $rupiah = _bctrim(bcadd(0,$vars["rupiah"]));
	    $id_global_session = _bctrim(bcadd(0,$vars["id_global_session"]));
      $id_test = $id_global_session;
	    $create_user_id = getUserID();
	    $modify_user_id = getUserID();
	    $create_date = getSQLDate($vars["create_date"]);
	    $modify_date =  getSQLDate($vars["modify_date"]);
      $exist = 0;
	   
	    $id_awal = $id;
      if($id=="new") {
        $sql = "SELECT * FROM antrain_exc_rate WHERE id_global_session = '$id_global_session' AND status_cd = 'normal'";
        $result = $db->query($sql);
        $num_test = $db->getRowsNum($result);

        if ($db->getRowsNum($result) > 0) {
          $exist = 1;
        }

        if ($exist == 0) {
           $sql = "SELECT id,id_global_session,rupiah FROM antrain_exc_rate"
        . " WHERE id = ( SELECT MAX(id) FROM antrain_exc_rate ) ORDER BY id DESC " ;
           
         $result = $db->query($sql);
         list($idx)=$db->fetchRow($result);
         $id = $idx+1;

         $sql = "INSERT INTO antrain_exc_rate (id,id_global_session,rupiah,create_user_id,create_date)"
              . " VALUES('$id','$id_global_session','$rupiah','$create_user_id','$create_date')";
         $db->query($sql);
        }
      } else {
         $sql = "UPDATE antrain_exc_rate SET "
              . "id_global_session = '$id_global_session',rupiah = '$rupiah' , modify_user_id = '$modify_user_id', modify_date = '$modify_date'"			  
              . " WHERE id = '$id'";
         $db->query($sql);
		 
      $sqlnm = "SELECT org_nm FROM hris_orgs WHERE org_id= '$org_id'";
  	  $resultnm = $db->query($sqlnm);
  	  list($org_nm)=$db->fetchRow($resultnm);
  	  
  	  $sqlnmsec = "SELECT org_nm FROM hris_orgs WHERE org_id= '$org_id_sec'";
  	  $resultnmsec = $db->query($sqlnmsec);
  	  list($org_nm_sec)=$db->fetchRow($resultnmsec);

      $sql = "SELECT id FROM hris_global_sess WHERE id = '$id_global_session'";
      $result = $db->query($sql);
      list($idx)=$db->fetchRow($result);
      $id_global_session = $idx;
	  
	  }
      return array($id,$id_global_session,$id_awal,$exist);
   }
   
   function app_editSession($args) {
      $db=&Database::getInstance();
      $id = $args[0];
      $optyear = "";
      if($id=="new") {
         $create_date  = getSQLDate();
		 $create_user_id = getUserID();
         $generate = "";
      } else {
        $modify_user_id = getUserID();
		$modify_date  = getSQLDate();
		
		$sql = "SELECT id_global_session,rupiah FROM antrain_exc_rate WHERE id = '$id'";
		$result = $db->query($sql);
    	 
	   list($id_global_session,$rupiah)=$db->fetchRow($result);
       $id_global_session = htmlentities($id_global_session,ENT_QUOTES);	
      }

      $sql = "SELECT id FROM hris_global_sess";
      $result = $db->query($sql);
      while (list($id_global_sessionx)=$db->fetchRow($result)) {
    if($id_global_session == $id_global_sessionx ) {
			$selected = 'selected';
		}
		else {
			$selected = ''; 
		}

      	$optyear .= "<option value=\"$id_global_sessionx\" $selected>$id_global_sessionx</option>";
      }

	$ret = "<form id='frm'><table class='xxfrm' style='width:100%;'><colgroup><col width='160'/><col/></colgroup><tbody>"
         
           . "<tr><td>Session</td><td><select id='inp_year' name='id_global_session' style='width:150px;'>$optyear</select></td></tr>" 
		   //. "<tr><td>Title</td><td><input type='text' value=\"$title\" id='inp_title' name='title' style='width:50px;'/></td></tr>"
		   //. "<tr><td>Dollar</td><td><input type='text' value=\"$dollar\" id='inp_dollar' name='dollar' style='width:150px;'/></td></tr>" 
		   . "<tr><td>Rupiah</td><td><input type='text' value=\"$rupiah\" id='inp_rupiah' name='rupiah' style='width:150px;'/></td></tr>";
          
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
      $sql = "UPDATE antrain_exc_rate SET status_cd = 'nullified', nullified_user_id = '$user_id' WHERE id = '$id'";
      $db->query($sql);
   }
   
}

} /// HRIS_ASSESSMENTSESSIONAJAX_DEFINED
?>