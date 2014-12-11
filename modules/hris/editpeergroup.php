<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/editpeergroup.php                              //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_EDITPEERGROUP_DEFINED') ) {
   define('HRIS_EDITPEERGROUP_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_EditPeerGroup extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_EDITPEERGROUP_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_EDITPEERGROUP_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_EditPeerGroup($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function listGroup() {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_editpeergroup.php");
      $ajax = new _hris_class_EditPeerGroupAjax("ocjx");
      
      $sql = "SELECT peer_group_id,peer_group_nm"
           . " FROM ".XOCP_PREFIX."peer_group"
           . " ORDER BY peer_group_id";
      
      $result = $db->query($sql);
      $ret = "<table class='xxlist' style='width:100%;' align='center'><thead><tr><td>"
           . "<span style='float:left;'>Peer Group</span>"
           . "<span style='float:right;'><input onclick='edit_group(\"new\",this,event);' type='button' value='"._ADD."'/></span></td></tr></thead><tbody id='tbproc'>";
      if($db->getRowsNum($result)>0) {
         while(list($peer_group_id,$peer_group_nm)=$db->fetchRow($result)) {
            $ret .= "<tr><td id='tdclass_${peer_group_id}'>"
                  . "<table border='0' class='ilist'>"
                  . "<colgroup><col width='80'/><col/></colgroup>"
                  . "<tbody><tr>"
                  . "<td>$peer_group_id</td>"
                  . "<td><span id='sp_${peer_group_id}' class='xlnk' onclick='edit_group(\"$peer_group_id\",this,event);'>".htmlentities(stripslashes($peer_group_nm))."</span></td>"
                  . "</tr></tbody></table>"
                  . "</td></tr>";
         }
      }
      $ret .= "<tr style='display:none;' id='trempty'><td>"._EMPTY."</td></tr>";
      $ret .= "</tbody></table>";
      
      return $ret.$ajax->getJs()."<script type='text/javascript'><!--
      
      var wdv = null;
      function edit_group(peer_group_id,d,e) {
         if(wdv) {
            if(wdv.peer_group_id != 'new' && wdv.peer_group_id == peer_group_id) {
               cancel_edit();
               return;
            } else {
               cancel_edit();
            }
         }
         wdv = _dce('div');
         wdv.peer_group_id = peer_group_id;
         if(peer_group_id=='new') {
            var tre = $('trempty');
            var tr = _dce('tr');
            var td = tr.appendChild(_dce('td'));
            tr = tre.parentNode.insertBefore(tr,tre.parentNode.firstChild);
         } else {
            var td = $('tdclass_'+peer_group_id);
         }
         wdv.setAttribute('style','padding:10px;');
         wdv = td.appendChild(wdv);
         wdv.appendChild(progress_span());
         wdv.td = td;
         ocjx_app_editGroup(peer_group_id,function(_data) {
            wdv.innerHTML = _data;
            $('inp_peer_group_nm').focus();
         });
      }
      
      function cancel_edit() {
         wdv.td.style.backgroundColor = '';
         if(wdv.peer_group_id=='new') {
            _destroy(wdv.td.parentNode);
         }
         wdv.peer_group_id = null;
         _destroy(wdv);
         wdv = null;
      }
      
      function delete_class() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '#ffcccc';
         wdv.oldHTML = wdv.innerHTML;
         wdv.innerHTML = 'Are you sure you want to delete this peer group?<br/><br/>'
                       + '<input type=\"button\" value=\""._CANCEL."\" onclick=\"cancel_delete();\"/>&nbsp;&nbsp;'
                       + '<input type=\"button\" value=\""._DELETE."\" onclick=\"do_delete();\"/>';
      }
      
      function cancel_delete() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '';
         wdv.innerHTML = wdv.oldHTML;
      }
      
      function do_delete() {
         ocjx_app_Delete(wdv.peer_group_id,null);
         var tr = wdv.parentNode.parentNode;
         _destroy(tr);
         wdv.peer_group_id = null;
         wdv = null;
      }
      
      function save_class() {
         var ret = parseForm('frm');
         wdv.innerHTML = '';
         wdv.appendChild(progress_span());
         ocjx_app_saveGroup(wdv.peer_group_id,ret,function(_data) {
            var td = wdv.td;
            wdv.peer_group_id = null;
            _destroy(wdv);
            wdv = null;
            var data = recjsarray(_data);
            td.setAttribute('id',data[0]);
            td.innerHTML = data[1];
         });
      }
      
      // --></script>";
   }
   
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $this->listGroup();
            break;
         default:
            $ret = $this->listGroup();
            break;
      }
      return $ret."<div style='height:100px;'>&nbsp;</div>";
   }
}

} // HRIS_EDITPEERGROUP_DEFINED
?>
