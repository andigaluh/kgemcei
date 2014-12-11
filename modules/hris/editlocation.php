<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/editlocation.php                           //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_EDITLOCATION_DEFINED') ) {
   define('HRIS_EDITLOCATION_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_EditLocation extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_EDITLOCATION_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_EDITLOCATION_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_EditLocation($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function listLocation() {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_editlocation.php");
      $ajax = new _hris_class_EditLocationAjax("ocjx");
      
      $sql = "SELECT location_id,location_cd,location_nm,description"
           . " FROM ".XOCP_PREFIX."location"
           . " WHERE status_cd = 'normal'"
           . " ORDER BY location_id";
      
      $result = $db->query($sql);
      $ret = "<table class='xxlist' style='width:100%;' align='center'><thead><tr><td>"
           . "<span style='float:left;'>Location</span>"
           . "<span style='float:right;'><input onclick='edit_location(\"new\",this,event);' type='button' value='"._ADD."'/></span></td></tr></thead><tbody id='tbproc'>";
      if($db->getRowsNum($result)>0) {
         while(list($location_id,$location_cd,$location_nm,$description)=$db->fetchRow($result)) {
            $ret .= "<tr><td id='tdlocation_${location_id}'>"
                  // $location_cd <span id='sp_${location_id}' class='xlnk' onclick='edit_location(\"$location_id\",this,event);'>".htmlentities(stripslashes($location_nm))."</span>"
            
                  . "<table border='0' class='ilist'>"
                  . "<colgroup><col width='40'/><col/></colgroup>"
                  . "<tbody><tr>"
                  . "<td>$location_cd</td>"
                  . "<td><span id='sp_${location_id}' class='xlnk' onclick='edit_location(\"$location_id\",this,event);'>".htmlentities(stripslashes($location_nm))."</span></td>"
                  . "</tr></tbody></table>"
                  
                  . "</td></tr>";
         }
      }
      $ret .= "<tr style='display:none;' id='trempty'><td>"._EMPTY."</td></tr>";
      $ret .= "</tbody></table>";
      
      return $ret.$ajax->getJs()."<script type='text/javascript'><!--
      
      var wdv = null;
      function edit_location(location_id,d,e) {
         if(wdv) {
            if(wdv.location_id != 'new' && wdv.location_id == location_id) {
               cancel_edit();
               return;
            } else {
               cancel_edit();
            }
         }
         wdv = _dce('div');
         wdv.location_id = location_id;
         if(location_id=='new') {
            var tre = $('trempty');
            var tr = _dce('tr');
            var td = tr.appendChild(_dce('td'));
            tr = tre.parentNode.insertBefore(tr,tre);
         } else {
            var td = $('tdlocation_'+location_id);
         }
         wdv.setAttribute('style','padding:10px;');
         wdv = td.appendChild(wdv);
         wdv.appendChild(progress_span());
         wdv.td = td;
         ocjx_app_editLocation(location_id,function(_data) {
            wdv.innerHTML = _data;
            $('inp_location_nm').focus();
         });
      }
      
      function cancel_edit() {
         wdv.td.style.backgroundColor = '';
         if(wdv.location_id=='new') {
            _destroy(wdv.td.parentNode);
         }
         wdv.location_id = null;
         _destroy(wdv);
         wdv = null;
      }
      
      function delete_location() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '#ffcccc';
         wdv.oldHTML = wdv.innerHTML;
         wdv.innerHTML = 'Are you sure you want to delete this location?<br/><br/>'
                       + '<input type=\"button\" value=\""._CANCEL."\" onclick=\"cancel_delete();\"/>&nbsp;&nbsp;'
                       + '<input type=\"button\" value=\""._DELETE."\" onclick=\"do_delete();\"/>';
      }
      
      function cancel_delete() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '';
         wdv.innerHTML = wdv.oldHTML;
      }
      
      function do_delete() {
         ocjx_app_Delete(wdv.location_id,null);
         var tr = wdv.parentNode.parentNode;
         _destroy(tr);
         wdv.location_id = null;
         wdv = null;
      }
      
      function save_location() {
         var location_nm = urlencode($('inp_location_nm').value);
         var location_cd = urlencode($('inp_location_cd').value);
         var sl = $('sellevel');
         var description = urlencode($('description').value);
         wdv.innerHTML = '';
         wdv.appendChild(progress_span());
         ocjx_app_saveLocation(wdv.location_id,location_nm,description,location_cd,function(_data) {
            var td = wdv.td;
            wdv.location_id = null;
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
            $this->listLocation();
            break;
         default:
            $ret = $this->listLocation();
            break;
      }
      return $ret;
   }
}

} // HRIS_EDITLOCATION_DEFINED
?>