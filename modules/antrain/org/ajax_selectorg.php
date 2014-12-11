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
      $this->_include_file = XOCP_DOC_ROOT."/modules/pms/class/ajax_selectorg.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_getOrgOpt","app_setOrg");
   }
   
   function app_getOrgOpt($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      
      $treeorg = new TreeOrg();
      
      $sql = "SELECT a.org_id,a.parent_id,a.org_nm,a.order_no,a.org_abbr,b.org_class_nm FROM ".XOCP_PREFIX."orgs a"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . " WHERE a.status_cd = 'normal' AND a.org_id != '2'"
           . " ORDER BY a.parent_id,a.order_no";
      $result = $db->query($sql);
      $found = $db->getRowsNum($result);

      if($found > 0) {
         while(list($org_id,$parent_id,$org_nm,$order_no,$org_abbr,$org_class_nm)=$db->fetchRow($result)) {
            $treeorg->addItem(new NodeOrg($org_id,"$org_nm $org_class_nm",$parent_id));
         }
 
         $ret .= $treeorg->render("O".$user_id."X");
			
	      $retx = "<table cellpadding='0' style='width:100%;border-spacing:0px;' id='orgtbl'><tr><td></td></tr>"
               . "<tr><td style='border:1px solid #999;padding:10px;white-space:nowrap;color:black;'>"
               . $ret
               . "</td></tr></table>";
              
      }
      
      return $retx;
      
   }
   
   function app_setOrg($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $_SESSION["pms_org_id"] = $args[0];
      $_SESSION["pms_dashboard_org"] = $args[0];
   }
   
}

class TreeOrg {
   var $text;
   var $link;
   var $icon;
   var $item = array();
   var $parent = array();
   var $expanded;
   var $ret = "";
   var $prefix = "XXX";

   function addItem($node) {
      $this->item[$node->id] = $node;
      $this->parent[$node->parent][$node->id] = $node->parent;
   }
   
   function isParent($node_id) {
      if(empty($this->parent[$node_id])) {
         return FALSE;
      } else {
         return is_array($this->parent[$node_id]);
      }
   }

   function isHTML($node_id) {
      if(empty($this->item[$node_id]->html)) {
         return FALSE;
      } else {
         return TRUE;
      }
   }
   
   function renderNodeOrg($node_id,$depth=0) {
      if($this->isParent($node_id)) {

         // render as parent
         $this->ret .= "<div style='padding:3px;'><img src='".XOCP_SERVER_SUBDIR."/modules/menu/images/spacer.gif' width='".($depth*20)."' height='1' alt=''/>"
                     . "<img src='".XOCP_SERVER_SUBDIR."/images/collapsed.gif' style='border:0;cursor:pointer;' name='img_".$this->prefix."$node_id' alt='+' onclick='return OrgToggleBranch(\"".$this->prefix."$node_id\");'/> "
                     . "<span class='xlnk' onclick='_org_select_org(\"".$this->item[$node_id]->id."\",this,event);'>"
                     . $this->item[$node_id]->text."</span></div>\n";
         $this->ret .= "<div id='".$this->prefix."$node_id' style='display:none;'>";

         // recurse the child
         foreach($this->parent[$node_id] as $child_id => $parent_id) {
            $this->renderNodeOrg($child_id,$depth+1);
         }
         
         $this->ret .= "</div>";
         
      } else {

         // render as child
         if($this->isHTML($node_id)) {
            $this->ret .= $this->item[$node_id]->html;
         } else {
            $this->ret .= "<div style='padding:3px;'><img src='".XOCP_SERVER_SUBDIR."/modules/menu/images/spacer.gif' width='".($depth*20)."' height='1' alt=''/>"
                        . "<img src='".XOCP_SERVER_SUBDIR."/modules/menu/images/bullet.gif' border='0' alt='+'/> "
                        . "<span class='xlnk' onclick='_org_select_org(\"".$this->item[$node_id]->id."\",this,event);'>"
                        . $this->item[$node_id]->text."</span></div>\n";
         }

      }
   }

   function render($prefix) {
      if(count($this->parent[0])<=0) {
         return;
      }

      $this->prefix = $prefix;
      foreach($this->parent[0] as $node_id => $parent_id) {
         $this->renderNodeOrg($node_id);
      }
      
      $this->ret .= "<script type='text/javascript' language='javascript'>\n//<![CDATA[\n\nOrgResetBranches();\n//]]>\n</script>\n";
      
      return $this->ret;
   }
}


class NodeOrg {
   var $id;
   var $text;
   var $link;
   var $html;
   var $icon;
   var $expanded;
   var $parent;

   function NodeOrg($id, $text, $parent = 0, $icon = NULL, $expanded = FALSE) {
      $this->id       = $id;
      $this->text     = htmlentities($text);
      $this->parent   = intval($parent);
      $this->icon     = $icon;
      $this->expanded = $expanded;
   }

}

} /// HRIS_CLASS_AJAXSELECTORG_DEFINED
?>