<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/editjobclass.php                              //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_IDPMETHODCLASS_DEFINED') ) {
   define('HRIS_IDPMETHODCLASS_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_IDPMethodClass extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_IDPMETHODCLASS_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_IDPMETHODCLASS_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_IDPMethodClass($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function listClass() {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_idpmethodclass.php");
      $ajax = new _hris_class_IDPMethodClassAjax("ocjx");
      
      $sql = "SELECT method_t,method_type"
           . " FROM ".XOCP_PREFIX."idp_development_method_type";
      
      $result = $db->query($sql);
      $ret = "<table class='xxlist' style='width:100%;' align='center'><thead><tr><td>"
           . "<span style='float:left;'>Method Type</span>"
           . "<span style='float:right;'><input onclick='edit_class(\"new\",this,event);' type='button' value='"._ADD."'/></span></td></tr></thead><tbody id='tbproc'>";
      if($db->getRowsNum($result)>0) {
         while(list($method_t,$method_type)=$db->fetchRow($result)) {
            $ret .= "<tr><td id='tdclass_${method_t}'>"
                  . "<table border='0' class='ilist'>"
                  . "<colgroup><col width='100'/><col/></colgroup>"
                  . "<tbody><tr>"
                  . "<td>$method_t</td>"
                  . "<td><span id='sp_${method_t}' class='xlnk' onclick='edit_class(\"$method_t\",this,event);'>".htmlentities(stripslashes($method_type))."</span></td>"
                  . "</tr></tbody></table>"
                  . "</td></tr>";
         }
      }
      $ret .= "<tr style='display:none;' id='trempty'><td>"._EMPTY."</td></tr>";
      $ret .= "</tbody></table>";
      
      return $ret.$ajax->getJs()."<script type='text/javascript'><!--
      
      function reset_all_superior(d,e) {
         var upper_job_class = $('supperjob').options[$('supperjob').selectedIndex].value;
         $('sp_progress_upper').d = d;
         d.style.visibility = 'hidden';
         $('sp_progress_upper').innerHTML = '';
         $('sp_progress_upper').appendChild(progress_span('&nbsp;'));
         $('sp_progress_upper').style.display = '';
         ocjx_app_resetSuperior(wdv.method_t,upper_job_class,function(_data) {
            $('sp_progress_upper').d.style.visibility = 'visible';
            $('sp_progress_upper').style.display = 'none';
         });
      }
      
      var wdv = null;
      function edit_class(method_t,d,e) {
         if(wdv) {
            if(wdv.method_t != 'new' && wdv.method_t == method_t) {
               cancel_edit();
               return;
            } else {
               cancel_edit();
            }
         }
         wdv = _dce('div');
         wdv.method_t = method_t;
         if(method_t=='new') {
            var tre = $('trempty');
            var tr = _dce('tr');
            var td = tr.appendChild(_dce('td'));
            tr = tre.parentNode.insertBefore(tr,tre.parentNode.firstChild);
         } else {
            var td = $('tdclass_'+method_t);
         }
         wdv.setAttribute('style','padding:10px;');
         wdv = td.appendChild(wdv);
         wdv.appendChild(progress_span());
         wdv.td = td;
         ocjx_app_editClass(method_t,function(_data) {
            wdv.innerHTML = _data;
            $('inp_method_t').focus();
         });
      }
      
      function cancel_edit() {
         wdv.td.style.backgroundColor = '';
         if(wdv.method_t=='new') {
            _destroy(wdv.td.parentNode);
         }
         wdv.method_t = null;
         _destroy(wdv);
         wdv = null;
      }
      
      function delete_class() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '#ffcccc';
         wdv.oldHTML = wdv.innerHTML;
         wdv.innerHTML = 'Are you sure you want to delete this method type?<br/><br/>'
                       + '<input type=\"button\" value=\""._CANCEL."\" onclick=\"cancel_delete();\"/>&nbsp;&nbsp;'
                       + '<input type=\"button\" value=\""._DELETE."\" onclick=\"do_delete();\"/>';
      }
      
      function cancel_delete() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '';
         wdv.innerHTML = wdv.oldHTML;
      }
      
      function do_delete() {
         ocjx_app_Delete(wdv.method_t,null);
         var tr = wdv.parentNode.parentNode;
         _destroy(tr);
         wdv.method_t = null;
         wdv = null;
      }
      
      function save_class() {
         var method_t = $('inp_method_t').value;
         var method_type = $('inp_method_type').value;
         wdv.innerHTML = '';
         wdv.appendChild(progress_span());
         ocjx_app_saveClass(method_t,method_type,function(_data) {
            var td = wdv.td;
            wdv.method_t = null;
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

} // HRIS_IDPMETHODCLASS_DEFINED
?>
