<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_editworkarea.php                //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_WORKAREAAJAX_DEFINED') ) {
   define('HRIS_WORKAREAAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");

class _hris_class_EditWorkAreaAjax extends AjaxListener {
   
   function _hris_class_EditWorkAreaAjax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/hris/class/ajax_editworkarea.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_Delete","app_editWorkArea","app_saveWorkArea");
   }
   
   function app_saveWorkArea($args) {
      $db=&Database::getInstance();
      $workarea_id = $args[0];
      $workarea_nm = addslashes(trim(urldecode($args[1])));
      $description = addslashes(trim(urldecode($args[2])));
      $workarea_cd = $args[3];
      $gradeval_top = _bctrim(bcadd(0,$args[5]));
      $gradeval_bottom = _bctrim(bcadd(0,$args[6]));
      if($workarea_nm=="") {
         $workarea_nm = "noname";
      }
      if($workarea_id=="new") {
         $sql = "SELECT MAX(workarea_id) FROM ".XOCP_PREFIX."workarea";
         $result = $db->query($sql);
         list($workarea_idx)=$db->fetchRow($result);
         $workarea_id = $workarea_idx+1;
         $user_id = getUserID();
         $sql = "INSERT INTO ".XOCP_PREFIX."workarea (workarea_id,workarea_nm,description,created_user_id,workarea_cd)"
              . " VALUES('$workarea_id','$workarea_nm','$description','$user_id','$workarea_cd')";
         $db->query($sql);
      } else {
         $sql = "UPDATE ".XOCP_PREFIX."workarea SET workarea_nm = '$workarea_nm', description = '$description',"
              . "workarea_cd = '$workarea_cd'"
              . " WHERE workarea_id = '$workarea_id'";
         $db->query($sql);
      }
      $ret = "$workarea_cd <span id='sp_${workarea_id}' class='xlnk' onclick='edit_workarea(\"$workarea_id\",this,event);'>".htmlentities(stripslashes($workarea_nm))."</span>"
           . "<div style='padding:4px;'>".nl2br(htmlentities(stripslashes($description)))."</div>";
      $ret = "<table border='0' class='ilist'>"
           . "<colgroup><col width='40'/><col/></colgroup>"
           . "<tbody><tr>"
           . "<td>$workarea_cd</td>"
           . "<td><span id='sp_${workarea_id}' class='xlnk' onclick='edit_workarea(\"$workarea_id\",this,event);'>".htmlentities(stripslashes($workarea_nm))."</span></td>"
           . "</tr></tbody></table>";
           
      return array("tdworkarea_${workarea_id}",$ret);
   }
   
   function app_editWorkArea($args) {
      $db=&Database::getInstance();
      $workarea_id = $args[0];
      if($workarea_id=="new") {
      } else {
         $sql = "SELECT description,workarea_nm,workarea_cd"
              . " FROM ".XOCP_PREFIX."workarea"
              . " WHERE workarea_id = '$workarea_id'";
         $result = $db->query($sql);
         list($desc,$workarea_nm,$workarea_cd)=$db->fetchRow($result);
         $workarea_nm = htmlentities($workarea_nm,ENT_QUOTES);
         $desc = htmlentities($desc,ENT_QUOTES);
      }
                
      $ret = "<form id='frm'><table class='xxfrm' style='width:100%;'><tbody>"
           . "<tr><td>Work Area Name</td><td><input type='text' value=\"$workarea_nm\" id='inp_workarea_nm' name='workarea_nm' style='width:90%;'/></td></tr>"
           . "<tr><td>Code</td><td><input type='text' value=\"$workarea_cd\" id='inp_workarea_cd' name='workarea_cd' style='width:50px;'/></td></tr>"
           . "<tr><td>Description</td><td><textarea id='description' style='width:50%;'>$desc</textarea></td></tr>"
           . "<tr><td colspan='2'><input onclick='save_workarea();' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input onclick='cancel_edit();' type='button' value='"._CANCEL."'/>&nbsp;&nbsp;"
           . ($workarea_id!="new"?"<input onclick='delete_workarea();' type='button' value='"._DELETE."'/>":"")
           . "</td></tr>"
           . "</tbody></table></form>";
      return $ret;
   }
   
   function app_Delete($args) {
      $db=&Database::getInstance();
      $workarea_id = $args[0];
      $sql = "DELETE FROM ".XOCP_PREFIX."workarea WHERE workarea_id = '$workarea_id'";
      $db->query($sql);
   }
   
}

} /// HRIS_WORKAREAAJAX_DEFINED
?>