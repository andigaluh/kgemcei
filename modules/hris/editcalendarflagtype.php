<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/editcalendarflagtype.php                   //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2011-06-15                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_EDITCALENDARFLAGTYPE_DEFINED') ) {
   define('HRIS_EDITCALENDARFLAGTYPE_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_EditCalendarFlagType extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_EDITCALENDARFLAGTYPE_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_EDITCALENDARFLAGTYPE_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_EditCalendarFlagType($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function listFlag() {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_editcalendarflagtype.php");
      $ajax = new _hris_class_EditCalendarFlagTypeAjax("ocjx");
      
      $sql = "SELECT flag_type,flag_type_nm"
           . " FROM ".XOCP_PREFIX."calendar_flag_type"
           . " ORDER BY flag_type";
      
      $result = $db->query($sql);
      $ret = "<table class='xxlist' style='width:100%;' align='center'><thead><tr><td>"
           . "<span style='float:left;'>Flag Type</span>"
           . "<span style='float:right;'><input onclick='edit_flag(\"new\",this,event);' type='button' value='"._ADD."'/></span></td></tr></thead><tbody id='tbproc'>";
      if($db->getRowsNum($result)>0) {
         while(list($flag_type,$flag_type_nm)=$db->fetchRow($result)) {
            $ret .= "<tr><td id='tdclass_${flag_type}'>"
                  . "<table border='0' class='ilist'>"
                  . "<colgroup><col width='80'/><col/></colgroup>"
                  . "<tbody><tr>"
                  . "<td>$flag_type</td>"
                  . "<td><span id='sp_${flag_type}' class='xlnk' onclick='edit_flag(\"$flag_type\",this,event);'>".htmlentities(stripslashes($flag_type_nm))."</span></td>"
                  . "</tr></tbody></table>"
                  . "</td></tr>";
         }
      }
      $ret .= "<tr style='display:none;' id='trempty'><td>"._EMPTY."</td></tr>";
      $ret .= "</tbody></table>";
      
      return $ret.$ajax->getJs()."<script type='text/javascript'><!--
      
      var wdv = null;
      function edit_flag(flag_type,d,e) {
         if(wdv) {
            if(wdv.flag_type != 'new' && wdv.flag_type == flag_type) {
               cancel_edit();
               return;
            } else {
               cancel_edit();
            }
         }
         wdv = _dce('div');
         wdv.flag_type = flag_type;
         if(flag_type=='new') {
            var tre = $('trempty');
            var tr = _dce('tr');
            var td = tr.appendChild(_dce('td'));
            tr = tre.parentNode.insertBefore(tr,tre.parentNode.firstChild);
         } else {
            var td = $('tdclass_'+flag_type);
         }
         wdv.setAttribute('style','padding:10px;');
         wdv = td.appendChild(wdv);
         wdv.appendChild(progress_span());
         wdv.td = td;
         ocjx_app_editFlag(flag_type,function(_data) {
            wdv.innerHTML = _data;
            $('inp_flag_type_nm').focus();
         });
      }
      
      function cancel_edit() {
         wdv.td.style.backgroundColor = '';
         if(wdv.flag_type=='new') {
            _destroy(wdv.td.parentNode);
         }
         wdv.flag_type = null;
         _destroy(wdv);
         wdv = null;
      }
      
      function delete_class() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '#ffcccc';
         wdv.oldHTML = wdv.innerHTML;
         wdv.innerHTML = 'Are you sure you want to delete this flag type?<br/><br/>'
                       + '<input type=\"button\" value=\""._CANCEL."\" onclick=\"cancel_delete();\"/>&nbsp;&nbsp;'
                       + '<input type=\"button\" value=\""._DELETE."\" onclick=\"do_delete();\"/>';
      }
      
      function cancel_delete() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '';
         wdv.innerHTML = wdv.oldHTML;
      }
      
      function do_delete() {
         ocjx_app_Delete(wdv.flag_type,null);
         var tr = wdv.parentNode.parentNode;
         _destroy(tr);
         wdv.flag_type = null;
         wdv = null;
      }
      
      function save_class() {
         var ret = _parseForm('frm');
         ajax_feedback = _caf;
         //wdv.innerHTML = '';
         //wdv.appendChild(progress_span());
         ocjx_app_saveFlag(wdv.flag_type,ret,function(_data) {
            var td = wdv.td;
            wdv.flag_type = null;
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
            $this->listFlag();
            break;
         default:
            $ret = $this->listFlag();
            break;
      }
      return $ret."<div style='height:100px;'>&nbsp;</div>";
   }
}

} // HRIS_EDITCALENDARFLAGTYPE_DEFINED
?>
