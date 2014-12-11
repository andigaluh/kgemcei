<?php
//--------------------------------------------------------------------//
// Filename : modules/antrain/class/ajax_antrainsession.php                  //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-12-17                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_ANTRAINGLOBALSESSIONAJAX_DEFINED') ) {
   define('HRIS_ANTRAINGLOBALSESSIONAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");
require_once(XOCP_DOC_ROOT."/modules/antrain/modconsts.php");


class _antrain_class_ANTRAINGlobalsessionAjax extends AjaxListener {
   
   function __construct($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/antrain/class/ajax_antrainglobalsession.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_Delete","app_editSession","app_saveSession",
                                             "app_setANTRAINGlobalsessions","app_newSession");
   }
   
   function app_setANTRAINGlobalsessions($args) {
      $_SESSION["antrain_psid"] = $args[0];
   }
   
   function app_newSession($args) {
    $db=&Database::getInstance();
    $id = "new";
	  $title = addslashes(trim($vars["title"]));
	  $description = addslashes(trim($vars["description"]));	  
	  $create_user_id = getUserID();
	  $create_date = getSQLDate();
	  $sql = "INSERT INTO hris_global_sess (id, description,create_user_id,create_date)"
           . " VALUES('$id','$description','$create_user_id','$create_date')";
    //$db->query($sql);
	
     $hdr = "<table><colgroup><col width='60'/><col/></colgroup><tbody><tr>"
			  . "<td width=110><span id='sp_${id}' class='xlnk' onclick='edit_session(\"$id\",this,event);' >$title</span></td>"
			  . "<td id='td_description_${id}' width=200 style='text-align: left;'>$description</td>"
			  . "</tr></tbody></table>";
      return array($id,$hdr);
   }
   
   function app_saveSession($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $id = $args[0];
      $vars = parseForm($args[1]);
      $inp_id = _bctrim(bcadd(0,$vars["id"]));
  	 // $title = addslashes(trim($vars["title"]));
  	  $description = addslashes(trim($vars["description"]));	
  	  $create_user_id = getUserID();
  	  $modify_user_Id = getUserID();
  	  $create_date = getSQLDate($vars["create_date"]);
  	  $modify_date =  getSQLDate($vars["modify_date"]);
  	  $exist = 0;
      
      if($id=="new") {
        $sql = "SELECT * FROM hris_global_sess WHERE id = '$inp_id' AND status_cd = 'normal'";
        $result = $db->query($sql);
        if ($db->getRowsNum($result) > 0) {
          $exist = 1;
        }
        if ($exist == 0) {
          $sql = "INSERT INTO hris_global_sess (id, description,create_user_id,create_date)"
            . " VALUES('$inp_id','$description','$create_user_id','$create_date')";
          $db->query($sql);  
        }
      } else {
    	  $sql = "UPDATE hris_global_sess SET"
               . " id = '$inp_id', description = '$description', modify_user_Id = '$modify_user_Id', modify_date = '$modify_date'"	
               . " WHERE id = '$id'";
     	  $db->query($sql);
	    }
      return array($id,$inp_id,$exist);
   }
   
   function app_editSession($args) {
      $db=&Database::getInstance();
      $id = $args[0];
      if($id=="new") {
         $create_date  = getSQLDate();
		     $create_user_id = getUserID();
         $generate = "";
         $id_val = "";
      } else {
         $id_modified = getUserID();
		     $date_modified  = getSQLDate();

    		 $sql =  "SELECT id, description FROM hris_global_sess"
    			  . " WHERE id = '$id' ORDER BY id DESC " ;
    	
             $result = $db->query($sql);
           
    	  	 list($id,$description)=$db->fetchRow($result);
           $id_val = $id;
             //$title = htmlentities($title,ENT_QUOTES);	
      }

	$ret = "<form id='frm'><table class='xxfrm' style='width:100%;'><colgroup><col width='160'/><col/></colgroup><tbody>"
         . "<tr><td>Session</td><td><input type='text' value=\"$id_val\" id='inp_id' name='id' style='width:150px;'/></td></tr>" 
        // . "<tr><td>Title</td><td><input type='text' value=\"$title\" id='inp_title' name='title' style='width:150px;'/></td></tr>" 
		   . "<tr><td>Description</td><td><input type='text' value=\"$description\" id='inp_description' name='description' style='width:200px;'/></td></tr>"; 
		   
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
      $sql = "UPDATE hris_global_sess SET status_cd = 'nullified', nullified_user_id = '$user_id' WHERE id = '$id'";
      $db->query($sql);
   }
   
}

} /// HRIS_ASSESSMENTSESSIONAJAX_DEFINED
?>