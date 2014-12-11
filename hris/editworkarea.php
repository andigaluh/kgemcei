<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/editworkarea.php                              //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_EDITWORKAREA_DEFINED') ) {
   define('HRIS_EDITWORKAREA_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_EditWorkArea extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_EDITWORKAREA_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_EDITWORKAREA_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_EditWorkArea($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function listWorkArea() {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_editworkarea.php");
      $ajax = new _hris_class_EditWorkAreaAjax("ocjx");
      
      $sql = "SELECT workarea_id,workarea_cd,workarea_nm,description"
           . " FROM ".XOCP_PREFIX."workarea"
           . " WHERE status_cd = 'normal'"
           . " ORDER BY workarea_id";
      
      $result = $db->query($sql);
      $ret = "<table class='xxlist' style='width:100%;' align='center'><thead><tr><td>"
           . "<span style='float:left;'>Work Area</span>"
           . "<span style='float:right;'><input onclick='edit_workarea(\"new\",this,event);' type='button' value='"._ADD."'/></span></td></tr></thead><tbody id='tbproc'>";
      if($db->getRowsNum($result)>0) {
         while(list($workarea_id,$workarea_cd,$workarea_nm,$description)=$db->fetchRow($result)) {
            $ret .= "<tr><td id='tdworkarea_${workarea_id}'>"
                  // . "$workarea_cd <span id='sp_${workarea_id}' class='xlnk' onclick='edit_workarea(\"$workarea_id\",this,event);'>".htmlentities(stripslashes($workarea_nm))."</span>"
                  
                  . "<table border='0' class='ilist'>"
                  . "<colgroup><col width='40'/><col/></colgroup>"
                  . "<tbody><tr>"
                  . "<td>$workarea_cd</td>"
                  . "<td><span id='sp_${workarea_id}' class='xlnk' onclick='edit_workarea(\"$workarea_id\",this,event);'>".htmlentities(stripslashes($workarea_nm))."</span></td>"
                  . "</tr></tbody></table>"
                  
                  
                  . "</td></tr>";
         }
      }
      $ret .= "<tr style='display:none;' id='trempty'><td>"._EMPTY."</td></tr>";
      $ret .= "</tbody></table>";
      
      return $ret.$ajax->getJs()."<script type='text/javascript'><!--
      
      var wdv = null;
      function edit_workarea(workarea_id,d,e) {
         if(wdv) {
            if(wdv.workarea_id != 'new' && wdv.workarea_id == workarea_id) {
               cancel_edit();
               return;
            } else {
               cancel_edit();
            }
         }
         wdv = _dce('div');
         wdv.workarea_id = workarea_id;
         if(workarea_id=='new') {
            var tre = $('trempty');
            var tr = _dce('tr');
            var td = tr.appendChild(_dce('td'));
            tr = tre.parentNode.insertBefore(tr,tre);
         } else {
            var td = $('tdworkarea_'+workarea_id);
         }
         wdv.setAttribute('style','padding:10px;');
         wdv = td.appendChild(wdv);
         wdv.appendChild(progress_span());
         wdv.td = td;
         ocjx_app_editWorkArea(workarea_id,function(_data) {
            wdv.innerHTML = _data;
            $('inp_workarea_nm').focus();
         });
      }
      
      function cancel_edit() {
         wdv.td.style.backgroundColor = '';
         if(wdv.workarea_id=='new') {
            _destroy(wdv.td.parentNode);
         }
         wdv.workarea_id = null;
         _destroy(wdv);
         wdv = null;
      }
      
      function delete_workarea() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '#ffcccc';
         wdv.oldHTML = wdv.innerHTML;
         wdv.innerHTML = 'Are you sure you want to delete this work area?<br/><br/>'
                       + '<input type=\"button\" value=\""._CANCEL."\" onclick=\"cancel_delete();\"/>&nbsp;&nbsp;'
                       + '<input type=\"button\" value=\""._DELETE."\" onclick=\"do_delete();\"/>';
      }
      
      function cancel_delete() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '';
         wdv.innerHTML = wdv.oldHTML;
      }
      
      function do_delete() {
         ocjx_app_Delete(wdv.workarea_id,null);
         var tr = wdv.parentNode.parentNode;
         _destroy(tr);
         wdv.workarea_id = null;
         wdv = null;
      }
      
      function save_workarea() {
         var workarea_nm = urlencode($('inp_workarea_nm').value);
         var workarea_cd = urlencode($('inp_workarea_cd').value);
         var sl = $('sellevel');
         var description = urlencode($('description').value);
         wdv.innerHTML = '';
         wdv.appendChild(progress_span());
         ocjx_app_saveWorkArea(wdv.workarea_id,workarea_nm,description,workarea_cd,function(_data) {
            var td = wdv.td;
            wdv.workarea_id = null;
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
            $this->listWorkArea();
            break;
         default:
            $ret = $this->listWorkArea();
            break;
      }
      return $ret;
   }
}

} // HRIS_EDITWORKAREA_DEFINED
?>