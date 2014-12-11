<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/org_class.php                              //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_ORGCLASS_DEFINED') ) {
   define('HRIS_ORGCLASS_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_OrgClass extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_ORGCLASS_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_ORGCLASS_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_OrgClass($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function listClass() {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_orgclass.php");
      $ajax = new _hris_class_OrgClassAjax("ocjx");
      
      $sql = "SELECT org_class_id,org_class_nm,description,order_no"
           . " FROM ".XOCP_PREFIX."org_class"
           . " WHERE status_cd = 'normal'"
           . " ORDER BY order_no";
      
      $result = $db->query($sql);
      $ret = "<table class='xxlist' style='width:100%;' align='center'><thead><tr><td>"
           . "<span style='float:left;'>Organization Levels</span>"
           . "<span style='float:right;'><input onclick='edit_class(\"new\",this,event);' type='button' value='"._ADD."'/></span></td></tr>"
           . "</thead><tbody id='tbproc'>";
      if($db->getRowsNum($result)>0) {
         while(list($org_class_id,$org_class_nm,$description,$order_no)=$db->fetchRow($result)) {
            $ret .= "<tr><td id='tdclass_${org_class_id}'><span style='padding-right:8px;border:0px solid black;'>$order_no</span>&nbsp;<span id='sp_${org_class_id}' class='xlnk' onclick='edit_class(\"$org_class_id\",this,event);'>".htmlentities(stripslashes($org_class_nm))."</span>"
                  . "</td></tr>";
         }
      }
      $ret .= "<tr style='display:none;' id='trempty'><td>"._EMPTY."</td></tr>";
      $ret .= "</tbody></table>";
      
      return $ret.$ajax->getJs()."<script type='text/javascript'><!--
      
      var wdv = null;
      function edit_class(org_class_id,d,e) {
         if(wdv) {
            if(wdv.org_class_id != 'new' && wdv.org_class_id == org_class_id) {
               cancel_edit();
               return;
            } else {
               cancel_edit();
            }
         }
         wdv = _dce('div');
         wdv.org_class_id = org_class_id;
         if(org_class_id=='new') {
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
         ocjx_app_editClass(org_class_id,function(_data) {
            wdv.innerHTML = _data;
            $('inp_org_class_nm').focus();
         });
      }
      
      function cancel_edit() {
         wdv.td.style.backgroundColor = '';
         if(wdv.org_class_id=='new') {
            _destroy(wdv.td.parentNode);
         }
         wdv.org_class_id = null;
         _destroy(wdv);
         wdv = null;
      }
      
      function delete_class() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '#ffcccc';
         wdv.oldHTML = wdv.innerHTML;
         wdv.innerHTML = 'Are you sure you want to delete this class?<br/><br/>'
                       + '<input type=\"button\" value=\""._CANCEL."\" onclick=\"cancel_delete();\"/>&nbsp;&nbsp;'
                       + '<input type=\"button\" value=\""._DELETE."\" onclick=\"do_delete();\"/>';
      }
      
      function cancel_delete() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '';
         wdv.innerHTML = wdv.oldHTML;
      }
      
      function do_delete() {
         ocjx_app_Delete(wdv.org_class_id,null);
         var tr = wdv.parentNode.parentNode;
         _destroy(tr);
         wdv.org_class_id = null;
         wdv = null;
      }
      
      function save_class() {
         var org_class_nm = urlencode($('inp_org_class_nm').value);
         var order_no = urlencode($('inp_order_no').value);
         var description = urlencode($('description').value);
         wdv.innerHTML = '';
         wdv.appendChild(progress_span());
         ocjx_app_saveClass(wdv.org_class_id,org_class_nm,description,order_no,function(_data) {
            var td = wdv.td;
            wdv.org_class_id = null;
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
            $this->listClass();
            break;
         default:
            $ret = $this->listClass();
            break;
      }
      return $ret."<div style='height:100px;'>&nbsp;</div>";
   }
}

} // HRIS_ORGCLASS_DEFINED
?>