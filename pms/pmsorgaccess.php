<?php
//--------------------------------------------------------------------//
// Filename : modules/pms/pmsorgaccess.php                          //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-07-18                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('PMS_ORGACCESS_DEFINED') ) {
   define('PMS_ORGACCESS_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

include_once(XOCP_DOC_ROOT."/modules/pms/class/ajax_orgaccess.php");
include_once(XOCP_DOC_ROOT."/modules/pms/class/selectpms.php");

class _pms_OrgAccess extends XocpBlock {
   var $catchvar = _PMS_CATCH_VAR;
   var $blockID = _PMS_ORGACCESS_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _PMS_ORGACCESS_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function __construct($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */

   }
   
   function pmsorgaccess() {
      $db=&Database::getInstance();
      $user_id = getUserID();
      
      
      $pmsselobj = new _pms_class_SelectSession();
      $pmssel = "<div style='padding-bottom:2px;'>".$pmsselobj->show()."</div>";
      
      if(!isset($_SESSION["pms_psid"])||$_SESSION["pms_psid"]==0) {
         return $pmssel;
      }
      
      $psid = $_SESSION["pms_psid"];
      
      
      $ajax = new _hris_class_OrgAccessAjax("fma");
      
      $ret = "";
      $js = "";
      
      $js .= $ajax->getJs()."\n<script type=\"text/javascript\" src=\"".XOCP_SERVER_SUBDIR."/modules/pms/include/treeorg.js\"></script>";
      $js .= "\n<script type='text/javascript' language='javascript'>
      //<![CDATA[
      
      
      var qfm = null;
      function kp_find(org_id,d,e) {
         
         if(!qfm) {
            qfm = _gel('new_access');
            qfm.org_id = org_id;
            qfm._get_param=function() {
               var qval = this.value;
               qval = trim(qval);
               if(qval.length < 2) {
                  return '';
               }
               return qval+'##'+qfm.org_id;
            };
            qfm._align = 'left';
            qfm._onselect=function(resId) {
               var i = qfm._data_idx[resId];
               fma_app_addAccess(qfm.org_id,resId,qfm._data[i][0],qfm._data[i][2],qfm._data[i][3],function(_data) {
                  $('spacc_'+qfm.org_id).innerHTML = _data;
                  qfm = null;
                  document.body.onclick = null;
                  _destroy(dvaddaccess);
                  dvaddaccess.d = null;
                  dvaddaccess = null;
               });
            };
            qfm._send_query = fma_app_findEmployeeJob;
            _make_ajax(qfm);
         }
         
         var k = getkeyc(e);
         if(k==13) {
            qfm._query();
         }
         
      }
      
      var dvaddaccess = null;
      function pms_org_add_access(org_id,d,e) {
         qfm = null;
         document.body.onclick = null;
         _destroy(dvaddaccess);
         if(dvaddaccess&&d==dvaddaccess.d) {
            dvaddaccess.d = null;
            dvaddaccess = null;
            return;
         }
         d.dv = _dce('div');
         d.dv.setAttribute('style','position:absolute;padding:5px;-moz-border-radius:5px;border:1px solid #777;background-color:#ffffcc;left:0px;-moz-box-shadow:1px 1px 3px #000;min-width:200px;');
         
         d.dv.innerHTML = '<div style=\"text-align:right;padding:2px;\">'
                        + '<div style=\"padding:5px;\">'
                        + 'Find Employee / Job : <input onkeypress=\"kp_find(\\''+org_id+'\\',this,event);\" id=\"new_access\" onclick=\"event.cancelBubble=true;\" style=\"-moz-border-radius:3px;width:150px;text-align:left;\" type=\"text\" value=\"\"/>'
                        + '</div>'
                        + '</div>';
         d.dv = d.firstChild.parentNode.parentNode.appendChild(d.dv);
         d.dv.style.top = parseInt(oY(d)+d.offsetHeight+10)+'px';
         d.dv.style.left = parseInt(oX(d)-10)+'px';
         d.dv.arrow = _dce('img');
         d.dv.arrow.setAttribute('style','position:absolute;');
         d.dv.arrow.src = '".XOCP_SERVER_SUBDIR."/images/topmiddle.png';
         d.dv.arrow = d.dv.appendChild(d.dv.arrow);
         d.dv.arrow.style.top = '-12px';
         d.dv.arrow.style.left = parseInt(8)+'px';
         _dsa($('new_access'));
         
         
         dvaddaccess = d.dv;
         dvaddaccess.d = d;
         setTimeout('document.body.onclick = function() { document.body.onclick = null; _destroy(dvaddaccess); qfm = null; };',100);
      
      }
      
      var dvdelaccess = null;
      function pms_org_del_access(org_id,access_id,d,e) {
         qfm = null;
         document.body.onclick = null;
         _destroy(dvdelaccess);
         if(dvdelaccess&&d==dvdelaccess.d) {
            dvdelaccess.d = null;
            dvdelaccess = null;
            return;
         }
         d.dv = _dce('div');
         d.dv.setAttribute('style','position:absolute;padding:5px;-moz-border-radius:5px;border:1px solid #777;background-color:#ffdddd;left:0px;-moz-box-shadow:1px 1px 3px #000;min-width:200px;');
         
         d.dv.innerHTML = '<div style=\"text-align:right;padding:2px;\">'
                        + '<div style=\"padding:5px;text-align:center;\">'
                        + '<div style=\"padding:5px;border:1px solid #bbb;text-align:center;background-color:#fff;\">Delete access?</div>'
                        + '<div style=\"margin-top:5px;\">'
                        + '<input onclick=\"do_del_access(\\''+access_id+'\\',this,event);\" type=\"button\" style=\"width:80px;\" value=\"Yes (delete)\"/>&nbsp;'
                        + '<input onclick=\"cancel_del_access(this,event);\" type=\"button\" style=\"width:80px;\" value=\"No\"/>'
                        + '</div>'
                        + '</div>'
                        + '</div>';
         d.dv = d.firstChild.parentNode.parentNode.appendChild(d.dv);
         d.dv.style.top = parseInt(oY(d)+d.offsetHeight+10)+'px';
         d.dv.style.left = parseInt(oX(d)-10)+'px';
         d.dv.arrow = _dce('img');
         d.dv.arrow.setAttribute('style','position:absolute;');
         d.dv.arrow.src = '".XOCP_SERVER_SUBDIR."/images/topmiddle.png';
         d.dv.arrow = d.dv.appendChild(d.dv.arrow);
         d.dv.arrow.style.top = '-12px';
         d.dv.arrow.style.left = parseInt(8)+'px';
         _dsa($('new_access'));
         
         
         dvdelaccess = d.dv;
         dvdelaccess.d = d;
         dvdelaccess.org_id = org_id;
         setTimeout('document.body.onclick = function() { document.body.onclick = null; _destroy(dvdelaccess); qfm = null; };',100);
      
      }
      
      function do_del_access(access_id,d,e) {
         fma_app_delAccess(access_id,function(_data) {
            $('spacc_'+dvdelaccess.org_id).innerHTML = _data;
         });
         e.cancelBubble=true;
      }
      
      function cancel_del_access(d,e) {
         document.body.onclick = null;
         _destroy(dvdelaccess);
         dvdelaccess.d = null;
         dvdelaccess = null;
         e.cancelBubble=true;
         return;
      }
      
      //]]></script>\n";
      
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
 
         $retx = $treeorg->render("OA".$user_id."X");
			
	      $ret = "<table cellpadding='0' style='width:100%;border-spacing:0px;' id='orgtbl'><tr><td></td></tr>"
              . "<tr><td style='border:1px solid #999;padding:10px;white-space:nowrap;color:black;'>"
              . $retx
              . "</td></tr></table>";
              
      }
      
      
      
      return $js.$pmssel.$ret;
   }
   
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $ret = $this->pmsorgaccess();
            break;
         default:
            $ret = $this->pmsorgaccess();
            break;
      }
      return $ret;
   }
}

//////////////////////////////////////////

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
      $db=&Database::getInstance();
      $psid = $_SESSION["pms_psid"];
      
      $emp = "<span style='white-space:normal;' id='spacc_${node_id}'>"._hris_class_OrgAccessAjax::renderAccess($node_id)."</span>";
      
      if($this->isParent($node_id)) {
         
         // render as parent
         $this->ret .= "<div style='padding:3px;border-bottom:1px solid #bbb;'><img src='".XOCP_SERVER_SUBDIR."/modules/menu/images/spacer.gif' width='".($depth*20)."' height='1' alt=''/>"
                     . "<img src='".XOCP_SERVER_SUBDIR."/images/collapsed.gif' style='border:0;cursor:pointer;' name='img_".$this->prefix."$node_id' alt='+' onclick='return OrgToggleBranch(\"".$this->prefix."$node_id\");'/> "
                     . "<span>"
                     . $this->item[$node_id]->text."</span> : [<span class='ylnk' onclick='pms_org_add_access(\"$node_id\",this,event);'>+</span>] $emp</div>\n";
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
            $this->ret .= "<div style='padding:3px;border-bottom:1px solid #bbb;'><img src='".XOCP_SERVER_SUBDIR."/modules/menu/images/spacer.gif' width='".($depth*20)."' height='1' alt=''/>"
                        . "<img src='".XOCP_SERVER_SUBDIR."/modules/menu/images/bullet.gif' border='0' alt='+'/> "
                        . "<span>"
                        . $this->item[$node_id]->text."</span> : [<span class='ylnk' onclick='pms_org_add_access(\"$node_id\",this,event);'>+</span>] $emp</div>\n";
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

//////////////////////////////////////////////

} // PMS_ORGACCESS_DEFINED
?>