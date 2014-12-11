<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_orgrel.php                      //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_ORGRELAJAX_DEFINED') ) {
   define('HRIS_ORGRELAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");
require_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_class_OrgRelAjax extends AjaxListener {
   
   function _hris_class_OrgRelAjax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/hris/class/ajax_orgrel.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_deleteRel","app_editOrgRel","app_editRel",
                            "app_saveRel","app_loadRel");
   }
   
   function app_loadRel($args) {
      global $arr_rel;
      $db=&Database::getInstance();
      $org_id0 = $args[0];
      
      $sql = "SELECT r.rel_type,a.org_nm,b.org_class_nm"
           . " FROM ".XOCP_PREFIX."org_rel r"
           . " LEFT JOIN ".XOCP_PREFIX."orgs a ON a.org_id = r.org_id1"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . " WHERE r.org_id0 = '$org_id0'"
           . " AND r.status_cd = 'active'";
      $result = $db->query($sql);
      $ret = "";
      if($db->getRowsNum($result)>0) {
         while(list($rel_type,$org_nm1,$org_class_nm1)=$db->fetchRow($result)) {
            $ret .= "<div><span style='font-weight:bold;'>$arr_rel[$rel_type]</span> : $org_nm1 [$org_class_nm1]</div>";
         }
      } else {
         $ret = "-";
      }
      return array($org_id0,$ret);
   }
   
   function app_saveRel($args) {
      global $arr_rel;
      $db=&Database::getInstance();
      $org_id0 = $args[0];
      $org_id1 = $args[1];
      $rel_type = $args[2];
      $user_id = getUserID();
      $sql = "REPLACE INTO ".XOCP_PREFIX."org_rel (org_id0,org_id1,rel_type,created_user_id)"
           . " VALUES('$org_id0','$org_id1','$rel_type','$user_id')";
      $db->query($sql);
      
      $sql = "SELECT a.org_nm,b.org_class_nm FROM ".XOCP_PREFIX."orgs a"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . "WHERE a.org_id = '$org_id1'";
      $result = $db->query($sql);
      list($org_nm1,$org_class_nm1)=$db->fetchRow($result);
      $ret = "<span id='sp_${org_id1}' class='xlnk' onclick='edit_rel(\"$org_id0\",\"$org_id1\",\"$rel_type\",this,event);'>$arr_rel[$rel_type]</span> : ".htmlentities(stripslashes($org_nm1))." [$org_class_nm1]";
      return array("tdrel_${org_id}",$ret);
   }
   
   function app_editOrgRel($args) {
      global $arr_rel;
      $db=&Database::getInstance();
      $org_id = $args[0];

      $sql = "SELECT a.org_id0,a.org_id1,c.org_nm,a.rel_type,a.order_no,e.org_class_nm,(a.rel_type+0) as urut"
           . " FROM ".XOCP_PREFIX."org_rel a"
           . " LEFT JOIN ".XOCP_PREFIX."orgs c ON c.org_id = a.org_id1"
           . " LEFT JOIN ".XOCP_PREFIX."org_class e ON e.org_class_id = c.org_class_id"
           . " WHERE a.org_id0 = '$org_id'"
           . " AND a.status_cd = 'active'"
           . " ORDER BY urut";
      
      $result = $db->query($sql);
      
      $ret = "<table class='' style='width:100%;' align='center'><thead><tr><td>"
           . "<span style='float:left;'>Relation List</span>"
           . "<span style='float:right;'><input onclick='edit_rel(\"$org_id\",\"new\",\"new\",this,event);' type='button' value='"._ADD."'/></span>"
           . "</td></tr></thead><tbody id='tbproc'>";
      if($db->getRowsNum($result)>0) {
         while(list($org_id0,$org_id1,$org_nm1,$rel_type,$order_no,$org_class_nm1)=$db->fetchRow($result)) {
            $ret .= "<tr><td id='tdrel_${org_id}'><span id='sp_${org_id}' class='xlnk' onclick='edit_rel(\"$org_id\",\"$org_id1\",\"$rel_type\",this,event);'>$arr_rel[$rel_type]</span> : ".htmlentities(stripslashes($org_nm1))." [$org_class_nm1]"
                  . "</td></tr>";
         }
      }
      $ret .= "<tr style='display:none;' id='trrelempty'><td>"._EMPTY."</td></tr>";
      $ret .= "</tbody></table>";

      return $ret;
   }
   
   function app_editRel($args) {
      $db=&Database::getInstance();
      global $arr_rel;
      $org_id0 = $args[0];
      $org_id1 = $args[1];
      $rel_type = $args[2];
      
      if($org_id1=="new") {
         $rel_type = 0;
         $sql = "SELECT a.org_id,a.org_nm,b.org_class_nm FROM ".XOCP_PREFIX."orgs a"
              . " LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
              . " WHERE a.org_id != '$org_id0'"
              . " ORDER BY a.org_class_id";
         $result = $db->query($sql);
         $selorg = "<select id='selorg'>";
         if($db->getRowsNum($result)>0) {
            while(list($org_idx,$org_nmx,$org_class_nmx)=$db->fetchRow($result)) {
               $selorg .= "<option value='$org_idx'>$org_nmx [$org_class_nmx]</option>";
            }
         }
         $selorg .= "</select>";
         $rel_type = "";
      } else {
         $sql = "SELECT a.org_cd,a.org_nm,b.org_class_nm FROM ".XOCP_PREFIX."orgs a"
              . " LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
              . " WHERE a.org_id = '$org_id1'";
         $result = $db->query($sql);
         list($org_cd,$org_nmx,$org_class_nmx)=$db->fetchRow($result);
         $org_nmx = htmlentities($org_nmx,ENT_QUOTES);
         $selorg = "$org_nmx [$org_class_nmx]";
      }
      
      
      $opt = "";
      foreach($arr_rel as $k=>$v) {
         $opt .= "<option value='$k'".($k==$rel_type?" selected='1'":"").">$v</option>";
      }
      
      $ret = "<form id='frm'><table class='xxfrm' style='width:100%;'><colgroup><col width='150'/><col/></colgroup><tbody>"
           . "<tr><td>Relate to</td><td>$selorg</td></tr>"
           . "<tr><td>Relation type</td><td><select id='selrel'>$opt</select></td></tr>"
           . "<tr><td colspan='2'><input onclick='save_rel();' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input onclick='cancel_edit_rel();' type='button' value='"._CANCEL."'/>&nbsp;&nbsp;"
           . ($org_id1!="new"?"<input onclick='delete_rel();' type='button' value='"._DELETE."'/>":"")
           . "</td></tr>"
           . "</tbody></table></form>";
      return $ret;
   }
   
   
   
   function app_deleteRel($args) {
      $db=&Database::getInstance();
      $org_id0 = $args[0];
      $org_id1 = $args[1];
      $rel_type = $args[2];
      $sql = "DELETE FROM ".XOCP_PREFIX."org_rel WHERE org_id0 = '$org_id0' AND org_id1 = '$org_id1' AND rel_type = '$rel_type'";
      $db->query($sql);
   }
   
}

} /// HRIS_ORGRELAJAX_DEFINED
?>