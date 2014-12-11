<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/orgrel.php                                 //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_ORGREL_DEFINED') ) {
   define('HRIS_ORGREL_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_OrgRel extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_ORGREL_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_ORGREL_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_OrgRel($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function listOrgRel() {
      $db=&Database::getInstance();
      global $arr_rel;
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_orgrel.php");
      $ajax = new _hris_class_OrgRelAjax("ocjx");
      
      $sql = "SELECT a.org_id,a.org_nm,a.description,b.org_class_nm,a.org_cd"
           . " FROM ".XOCP_PREFIX."orgs a"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . " WHERE a.status_cd = 'normal'"
           . " ORDER BY a.org_class_id,a.org_id";
      

      $result = $db->query($sql);
      $ret = "<table class='xxlist' style='width:100%;' align='center'><thead><tr><td>"
           . "Organization List"
           . "</td></tr></thead><tbody id='tbproc'>";
      if($db->getRowsNum($result)>0) {
         while(list($org_id,$org_nm,$description,$org_class_nm,$org_cd)=$db->fetchRow($result)) {
            $rel = "";
            $sql = "SELECT a.org_id0,a.org_id1,c.org_nm,a.rel_type,a.order_no,e.org_class_nm,(a.rel_type+0) as urut"
                 . " FROM ".XOCP_PREFIX."org_rel a"
                 . " LEFT JOIN ".XOCP_PREFIX."orgs c ON c.org_id = a.org_id1"
                 . " LEFT JOIN ".XOCP_PREFIX."org_class e ON e.org_class_id = c.org_class_id"
                 . " WHERE a.org_id0 = '$org_id'"
                 . " AND a.status_cd = 'active'"
                 . " ORDER BY urut";
            
            $res = $db->query($sql);
            if($db->getRowsNum($res)>0) {
               while(list($org_id0,$org_id1,$org_nm1,$rel_type,$order_no,$org_class_nm1)=$db->fetchRow($res)) {
                  $rel .= "<div><span class='xlnk' onclick='edit_rel(\"$org_id\",\"$org_id1\",\"$rel_type\",this,event);'>$arr_rel[$rel_type]</span> : ".htmlentities(stripslashes($org_nm1))." [$org_class_nm1]</div>";
               }
            }

            $ret .= "<tr><td id='tdclass_${org_id}'>$org_cd <span id='sp_${org_id}'>".htmlentities(stripslashes($org_nm))." [$org_class_nm]</span>"
                  . "&nbsp;<span class='xlnk' onclick='edit_rel(\"$org_id\",\"new\",\"new\",this,event);'>[+]</span>"
                  . "<div id='rel_${org_id}' style='padding:4px;padding-left:20px;'>$rel</div>"
                  . "</td></tr>";
         }
      }
      $ret .= "</tbody></table>";
      
      return $ret.$ajax->getJs()."<script type='text/javascript'><!--
      
      var wdv = null;
      function edit_org(org_id,d,e) {
         if(wdv) {
            if(wdv.org_id != 'new' && wdv.org_id == org_id) {
               cancel_edit();
               return;
            } else {
               cancel_edit();
            }
         }
         wdv = _dce('div');
         wdv.org_id = org_id;
         $('rel_'+wdv.org_id).style.display = 'none';
         if(org_id=='new') {
            var tre = $('trempty');
            var tr = _dce('tr');
            var td = tr.appendChild(_dce('td'));
            tr = tre.parentNode.insertBefore(tr,tre);
         } else {
            var td = d.parentNode;
         }
         wdv.setAttribute('style','padding:10px;');
         wdv = td.appendChild(wdv);
         wdv.appendChild(progress_span());
         wdv.td = td;
         ocjx_app_editOrgRel(org_id,function(_data) {
            wdv.innerHTML = _data;
         });
      }
      
      function cancel_edit() {
         wdv.td.style.backgroundColor = '';
         
         $('rel_'+wdv.org_id).style.display = '';
         var org_id = wdv.org_id;
         wdv.org_id = null;
         _destroy(wdv);
         wdv = null;
         ocjx_app_loadRel(org_id,function(_data) {
            var data = recjsarray(_data);
            $('rel_'+data[0]).innerHTML = data[1];
         });
      }
      

      var dvrel = null;
      function edit_rel(org_id0,org_id1,rel_type,d,e) {
         if(dvrel) {
            if(dvrel.org_id1 != 'new' && dvrel.org_id1 == org_id1) {
               cancel_edit_rel();
               return;
            } else {
               cancel_edit_rel();
            }
         }
         dvrel = _dce('div');
         dvrel.org_id0 = org_id0;
         dvrel.org_id1 = org_id1;
         dvrel.rel_type = rel_type;
         if(org_id1=='new') {
            var dv = $('rel_'+org_id0).insertBefore(_dce('div'),$('rel_'+org_id0).firstChild);
         } else {
            var dv = d.parentNode;
         }
         dvrel.setAttribute('style','padding:10px;');
         dvrel = dv.appendChild(dvrel);
         dvrel.appendChild(progress_span());
         dvrel.dv = dv;
         ocjx_app_editRel(org_id0,org_id1,rel_type,function(_data) {
            dvrel.innerHTML = _data;
         });
      }
      
      function cancel_edit_rel() {
         if(dvrel.rel_type=='new') {
            _destroy(dvrel.dv);
         }
         dvrel.org_id0 = null;
         dvrel.org_id1 = null;
         dvrel.rel_type = null;
         _destroy(dvrel);
         dvrel = null;
      }
      
      function delete_rel() {
         dvrel.oldHTML = dvrel.innerHTML;
         dvrel.innerHTML = '<div style=\"text-align:center;background-color:#ffcccc;padding:10px;\">Are you sure you want to delete this relation?<br/><br/>'
                         + '<input type=\"button\" value=\"Yes\" onclick=\"do_delete_rel();\"/>&nbsp;&nbsp;'
                         + '<input type=\"button\" value=\"No\" onclick=\"cancel_delete_rel();\"/>';
      }
      
      function cancel_delete_rel() {
         dvrel.innerHTML = dvrel.oldHTML;
      }
      
      function do_delete_rel() {
         dvrel.innerHTML = '';
         dvrel.appendChild(progress_span());
         ocjx_app_deleteRel(dvrel.org_id0,dvrel.org_id1,dvrel.rel_type,function(_data) {
            _destroy(dvrel.dv);
            dvrel.org_id0 = null;
            dvrel.org_id1 = null;
            dvrel.rel_type = null;
            dvrel = null;
         });
      }

      function delete_class() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '#ffcccc';
         wdv.oldHTML = wdv.innerHTML;
         wdv.innerHTML = 'Confirm organization class deletion?<br/><br/>'
                       + '<input type=\"button\" value=\""._CANCEL."\" onclick=\"cancel_delete();\"/>&nbsp;&nbsp;'
                       + '<input type=\"button\" value=\""._DELETE."\" onclick=\"do_delete();\"/>';
      }
      
      function cancel_delete() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '';
         wdv.innerHTML = wdv.oldHTML;
      }
      
      function do_delete() {
         ocjx_app_Delete(wdv.org_id,null);
         var tr = wdv.parentNode.parentNode;
         _destroy(tr);
         wdv.org_id = null;
         wdv = null;
      }
      
      function save_rel() {
         if(dvrel.org_id1=='new') {
            var org1 = $('selorg');
            var org_id1 = org1.options[org1.selectedIndex].value;
         } else {
            org_id1 = dvrel.org_id1;
         }
         var rel = $('selrel');
         var rel_type = rel.options[rel.selectedIndex].value;
         dvrel.innerHTML = '';
         dvrel.appendChild(progress_span());
         ocjx_app_saveRel(dvrel.org_id0,org_id1,rel_type,function(_data) {
            var dv = dvrel.dv;
            dvrel.org_id0 = null;
            dvrel.org_id1 = null;
            dvrel.rel_type = null;
            _destroy(dvrel);
            dvrel = null;
            var data = recjsarray(_data);
            dv.innerHTML = data[1];
         });
      }
      
      // --></script>";
   }
   
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $this->listOrgRel();
            break;
         default:
            $ret = $this->listOrgRel();
            break;
      }
      return $ret."<div style='height:100px;'>&nbsp;</div>";
   }
}

} // HRIS_ORGREL_DEFINED
?>