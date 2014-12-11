<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_editlocation.php                //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_LOCATIONAJAX_DEFINED') ) {
   define('HRIS_LOCATIONAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");

class _hris_class_EditLocationAjax extends AjaxListener {
   
   function _hris_class_EditLocationAjax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/hris/class/ajax_editlocation.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_Delete","app_editLocation","app_saveLocation");
   }
   
   function app_saveLocation($args) {
      $db=&Database::getInstance();
      $location_id = $args[0];
      $location_nm = addslashes(trim(urldecode($args[1])));
      $description = addslashes(trim(urldecode($args[2])));
      $location_cd = $args[3];
      $gradeval_top = _bctrim(bcadd(0,$args[5]));
      $gradeval_bottom = _bctrim(bcadd(0,$args[6]));
      if($location_nm=="") {
         $location_nm = "noname";
      }
      if($location_id=="new") {
         $sql = "SELECT MAX(location_id) FROM ".XOCP_PREFIX."location";
         $result = $db->query($sql);
         list($location_idx)=$db->fetchRow($result);
         $location_id = $location_idx+1;
         $user_id = getUserID();
         $sql = "INSERT INTO ".XOCP_PREFIX."location (location_id,location_nm,description,created_user_id,location_cd)"
              . " VALUES('$location_id','$location_nm','$description','$user_id','$location_cd')";
         $db->query($sql);
      } else {
         $sql = "UPDATE ".XOCP_PREFIX."location SET location_nm = '$location_nm', description = '$description',"
              . "location_cd = '$location_cd'"
              . " WHERE location_id = '$location_id'";
         $db->query($sql);
      }
      $ret = "$location_cd <span id='sp_${location_id}' class='xlnk' onclick='edit_location(\"$location_id\",this,event);'>".htmlentities(stripslashes($location_nm))."</span>"
           . "<div style='padding:4px;'>".nl2br(htmlentities(stripslashes($description)))."</div>";
      $ret = "<table border='0' class='ilist'>"
           . "<colgroup><col width='40'/><col/></colgroup>"
           . "<tbody><tr>"
           . "<td>$location_cd</td>"
           . "<td><span id='sp_${location_id}' class='xlnk' onclick='edit_location(\"$location_id\",this,event);'>".htmlentities(stripslashes($location_nm))."</span></td>"
           . "</tr></tbody></table>";
      return array("tdlocation_${location_id}",$ret);
   }
   
   function app_editLocation($args) {
      $db=&Database::getInstance();
      $location_id = $args[0];
      if($location_id=="new") {
      } else {
         $sql = "SELECT description,location_nm,location_cd"
              . " FROM ".XOCP_PREFIX."location"
              . " WHERE location_id = '$location_id'";
         $result = $db->query($sql);
         list($desc,$location_nm,$location_cd)=$db->fetchRow($result);
         $location_nm = htmlentities($location_nm,ENT_QUOTES);
         $desc = htmlentities($desc,ENT_QUOTES);
      }
                
      $ret = "<form id='frm'><table class='xxfrm' style='width:100%;'><tbody>"
           . "<tr><td>Location Name</td><td><input type='text' value=\"$location_nm\" id='inp_location_nm' name='location_nm' style='width:90%;'/></td></tr>"
           . "<tr><td>Code</td><td><input type='text' value=\"$location_cd\" id='inp_location_cd' name='location_cd' style='width:50px;'/></td></tr>"
           . "<tr><td>Description</td><td><textarea id='description' style='width:50%;'>$desc</textarea></td></tr>"
           . "<tr><td colspan='2'><input onclick='save_location();' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input onclick='cancel_edit();' type='button' value='"._CANCEL."'/>&nbsp;&nbsp;"
           . ($location_id!="new"?"<input onclick='delete_location();' type='button' value='"._DELETE."'/>":"")
           . "</td></tr>"
           . "</tbody></table></form>";
      return $ret;
   }
   
   function app_Delete($args) {
      $db=&Database::getInstance();
      $location_id = $args[0];
      $sql = "DELETE FROM ".XOCP_PREFIX."location WHERE location_id = '$location_id'";
      $db->query($sql);
   }
   
}

} /// HRIS_LOCATIONAJAX_DEFINED
?>