<?php
//--------------------------------------------------------------------//
// Filename : modules/sms/class/ajax_smsreftheme.php                  //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2013-10-10                                              //                                                     //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_SMSREFTHEMEAJAX_DEFINED') ) {
   define('HRIS_SMSREFTHEMEAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");
require_once(XOCP_DOC_ROOT."/modules/sms/modconsts.php");


class _sms_class_SMSRefThemeAjax extends AjaxListener {
   
   function __construct($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/sms/class/ajax_smsreftheme.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_Delete","app_editRefTheme","app_saveRefTheme",
                                             "app_setSMSRefTheme","app_newRefTheme");
   }
   
   function app_setSMSRefTheme($args) {
      $_SESSION["sms_id"] = $args[0];
   }
   
   function app_newRefTheme($args) {
      $db=&Database::getInstance();
      $sql = "SELECT MAX(id) FROM sms_ref_themes";
      $result = $db->query($sql);
      list($idx)=$db->fetchRow($result);
      $id = $idx+1;
      $create_user_id = getUserID();
      $create_date = getSQLDate();
      $psid = $_SESSION["sms_id"];
      $sql = "INSERT INTO sms_ref_themes (id,session,title,create_user_id,create_date)"
           . " VALUES('$id','$psid','$title_reftheme','$create_user_id','$create_date')";
      $db->query($sql);
      
      $hdr = "<table><colgroup><col width='200'/><col/></colgroup><tbody><tr>"
           . "<td><span id='sp_${id}' class='xlnk' onclick='edit_reftheme(\"$id\",this,event);'>"._EMPTY."</span></td>"
           . "</tr></tbody></table>";
      return array($id,$hdr);
   }
   
   function app_saveRefTheme($args) {
      $db=&Database::getInstance();
      $create_user_id = getUserID();
      $id = $args[0];
      $psid = $_SESSION["sms_id"];
      $vars = parseForm($args[1]);
      $title_reftheme = addslashes(trim($vars["title"]));
      $id_theme_leader = addslashes(trim($vars["id_theme_leader"]));
     
   if($title_reftheme=="") {
         $title_reftheme_ = "noname";
      }
      if($id=="new") {
         $sql = "SELECT MAX(id) FROM sms_ref_themes";
         $result = $db->query($sql);
         list($idx)=$db->fetchRow($result);
         $id = $idx+1;
         $create_user_id = getUserID();
         $create_date = getSQLDate();
         $sql = "INSERT INTO sms_ref_themes (id,session,title,id_theme_leader,create_user_id,create_date)"
              . " VALUES('$id','$psid','$title_reftheme','$id_theme_leader','$create_user_id','$create_date')";
         $db->query($sql);
      } else {
         $sql = "UPDATE sms_ref_themes SET session = '$psid', title = '$title_reftheme',id_theme_leader = '$id_theme_leader' "
              . " WHERE id = '$id'";
         $db->query($sql);
      }
      
      return array($id,$title_reftheme);
   }
   
   function app_editRefTheme($args) {
      $db=&Database::getInstance();
      $id = $args[0];
      if($id=="new") {
         $generate = "";
      } else {
         
         $sql = "SELECT title,id_theme_leader"
              . " FROM sms_ref_themes"
              . " WHERE id = '$id'";
         $result = $db->query($sql);
         
         list($title_reftheme,$id_theme_leader)=$db->fetchRow($result);
         $title_reftheme = htmlentities($title_reftheme,ENT_QUOTES);
      }
      $ret = "<form id='frm'><table class='xxfrm' style='width:100%;'><colgroup><col width='160'/><col/></colgroup><tbody>"
           . "<tr><td>Title Ref Theme</td><td><input type='text' value=\"$title_reftheme\" id='inp_reftheme_title' name='title' style='width:60%;'/></td></tr>"
		   
		   //Theme Leader
		   
           . "<tr><td>Theme Leader</td>";
		   $ret .= "<td><select id='id_theme_leader' name='id_theme_leader'>";
		   $sqlleader = "SELECT person_id,person_nm FROM hris_persons ORDER BY  `hris_persons`.`person_nm` ASC ";
		   $resultleader = $db->query($sqlleader);
		   while(list($id_leader,$nm_leader)=$db->fetchRow($resultleader)){
				
				if($id_theme_leader == $id_leader){$selected='selected';}else{$selected='';}
				$ret .= "<option id='id_theme_leader' name='id_theme_leader' value='$id_leader' $selected>$nm_leader</option>";
           }
		   $ret .= "</select></td>";
		   $ret .= "</tr>";
		   
		   $ret .= "<tr><td colspan='2'><span id='progress'></span>&nbsp;"
           . "<input id='btn_save_reftheme' onclick='save_reftheme();' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input id='btn_cancel_edit' onclick='cancel_edit();' type='button' value='"._CANCEL."'/>&nbsp;&nbsp;"
           . ($id!="new"?"<input id='btn_delete_reftheme' onclick='delete_reftheme();' type='button' value='"._DELETE."'/>":"")
           . "</td></tr>"
           . "</tbody></table></form>";
      return $ret;
   }
   
   function app_Delete($args) {
      $db=&Database::getInstance();
      $id = $args[0];
      $sql = "DELETE FROM sms_ref_themes WHERE id = '$id'";
      $db->query($sql);
   }
   
}

} /// HRIS_ASSESSMENTSESSIONAJAX_DEFINED
?>