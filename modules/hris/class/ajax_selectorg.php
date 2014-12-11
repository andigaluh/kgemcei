<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_selectorg.php                    //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2005-11-19                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_CLASS_AJAXSELECTORG_DEFINED') ) {
   define('HRIS_CLASS_AJAXSELECTORG_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");

class _hris_class_SelectOrgAjax extends AjaxListener {
   
   function _hris_class_SelectOrgAjax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/hris/class/ajax_selectorg.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_getOrgOpt","app_setOrg");
   }
   
   function app_getOrgOpt($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $sql = "SELECT a.org_id,a.org_nm,b.org_class_nm"
           . " FROM ".XOCP_PREFIX."orgs a"
           . " LEFT JOIN ".XOCP_PREFIX."pgroup2org p USING(org_id)"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b ON b.org_class_id = a.org_class_id"
           . " WHERE a.status_cd = 'normal'"
           . " AND p.pgroup_id = '".$_SESSION["xocp_user"]->getVar("pgroup_id")."'"
           . " ORDER BY a.org_nm";
      $result = $db->query($sql);
      $cnt=0;
      $colgrp = "";
      $colno = 0;
      $maxcol = 4;
      $itemx ="<tr>";
      if($db->getRowsNum($result)>0) {
         while(list($org_id,$org_nm,$org_class_nm)=$db->fetchRow($result)) {
            $cnt++;
            $colno++;
            $itemx .= "<td align='center' class='blnk' onclick='selorgopt(\"$org_id\",\"$org_nm\");'>"
                    . "<img src='".XOCP_SERVER_SUBDIR."/images/office-building.png' /><br/>"
                    . "$org_nm<br/>[$org_class_nm]</td>";
            if($colno>$maxcol) {
               $colno = 0;
               $itemx .= "</tr><tr>";
            }
         }
         $itemx .= "</tr>";
         $tbl = "<table id='tblc' align='center' border='0' cellspacing='2' cellpadding='0' style='width:100%;'>"
              . "<colgroup><col width='20%'/><col width='20%'/><col width='20%'/><col width='20%'/><col width='20%'/></colgroup>"
              . "$itemx</table>";
         return $tbl;
      } else {
         return "EMPTY";
      }
   }
   
   function app_setOrg($args) {
      $db=&Database::getInstance();
      $_SESSION["hris_org_id"] = $args[0];
      $_SESSION["hris_org_obj_id"] = $args[1];
      $sql = "UPDATE ".XOCP_PREFIX."users SET last_org_id = '".$args[0]."'"
           . " WHERE user_id = '".$_SESSION["xocp_user"]->getVar("user_id")."'";
      $db->query($sql);
   }

}

} /// HRIS_CLASS_AJAXSELECTORG_DEFINED
?>
