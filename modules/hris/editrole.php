<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/editrole.php                              //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_EDITROLE_DEFINED') ) {
   define('HRIS_EDITROLE_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_EditRole extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_EDITROLE_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_EDITROLE_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_EditRole($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function listRole() {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_editrole.php");
      $ajax = new _hris_class_EditRoleAjax("ocjx");
      
      $sql = "SELECT role_id,role_nm"
           . " FROM ".XOCP_PREFIX."role"
           . " WHERE status_cd = 'normal'"
           . " ORDER BY role_nm,role_id";
      
      $result = $db->query($sql);
      $ret = "<table class='xxlist' style='width:100%;' align='center'><thead><tr><td>"
           . "<span style='float:left;'>Role</span>"
           . "<span style='float:right;'><input onclick='edit_role(\"new\",this,event);' type='button' value='"._ADD."'/></span></td></tr></thead><tbody id='tbproc'>";
      if($db->getRowsNum($result)>0) {
         while(list($role_id,$role_nm)=$db->fetchRow($result)) {
            $ret .= "<tr><td id='tdclass_${role_id}'>"
                  . "<table border='0' class='ilist'>"
                  . "<colgroup><col width='80'/><col/></colgroup>"
                  . "<tbody><tr>"
                  . "<td>$role_id</td>"
                  . "<td><span id='sp_${role_id}' class='xlnk' onclick='edit_role(\"$role_id\",this,event);'>".htmlentities(stripslashes($role_nm))."</span></td>"
                  . "</tr></tbody></table>"
                  . "</td></tr>";
         }
      }
      $ret .= "<tr style='display:none;' id='trempty'><td>"._EMPTY."</td></tr>";
      $ret .= "</tbody></table>";
      
      return $ret.$ajax->getJs()."<script type='text/javascript'><!--
      
      var wdv = null;
      function edit_role(role_id,d,e) {
         if(wdv) {
            if(wdv.role_id != 'new' && wdv.role_id == role_id) {
               cancel_edit();
               return;
            } else {
               cancel_edit();
            }
         }
         wdv = _dce('div');
         wdv.role_id = role_id;
         if(role_id=='new') {
            var tre = $('trempty');
            var tr = _dce('tr');
            var td = tr.appendChild(_dce('td'));
            tr = tre.parentNode.insertBefore(tr,tre.parentNode.firstChild);
         } else {
            var td = $('tdclass_'+role_id);
         }
         wdv.setAttribute('style','padding:10px;');
         wdv = td.appendChild(wdv);
         wdv.appendChild(progress_span());
         wdv.td = td;
         ocjx_app_editRole(role_id,function(_data) {
            wdv.innerHTML = _data;
            $('inp_role_nm').focus();
         });
      }
      
      function cancel_edit() {
         wdv.td.style.backgroundColor = '';
         if(wdv.role_id=='new') {
            _destroy(wdv.td.parentNode);
         }
         wdv.role_id = null;
         _destroy(wdv);
         wdv = null;
      }
      
      function delete_class() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '#ffcccc';
         wdv.oldHTML = wdv.innerHTML;
         wdv.innerHTML = 'Are you sure you want to delete this role?<br/><br/>'
                       + '<input type=\"button\" value=\""._CANCEL."\" onclick=\"cancel_delete();\"/>&nbsp;&nbsp;'
                       + '<input type=\"button\" value=\""._DELETE."\" onclick=\"do_delete();\"/>';
      }
      
      function cancel_delete() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '';
         wdv.innerHTML = wdv.oldHTML;
      }
      
      function do_delete() {
         ocjx_app_Delete(wdv.role_id,null);
         var tr = wdv.parentNode.parentNode;
         _destroy(tr);
         wdv.role_id = null;
         wdv = null;
      }
      
      function save_class() {
         var ret = parseForm('frm');
         wdv.innerHTML = '';
         wdv.appendChild(progress_span());
         ocjx_app_saveRole(wdv.role_id,ret,function(_data) {
            var td = wdv.td;
            wdv.role_id = null;
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
            $this->listRole();
            break;
         default:
            $ret = $this->listRole();
            break;
      }
      return $ret."<div style='height:100px;'>&nbsp;</div>";
   }
}

} // HRIS_EDITROLE_DEFINED
?>
