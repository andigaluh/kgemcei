<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/editcompgroup.php                           //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_EDITCOMPETENCYGROUP_DEFINED') ) {
   define('HRIS_EDITCOMPETENCYGROUP_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_EditCompetencyGroup extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_EDITCOMPETENCYGROUP_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_EDITCOMPETENCYGROUP_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_EditCompetencyGroup($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function listCompetencyGroup() {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_editcompgroup.php");
      $ajax = new _hris_class_EditCompetencyGroupAjax("ocjx");
      
      $sql = "SELECT a.compgroup_id,a.compgroup_nm,a.description"
           . " FROM ".XOCP_PREFIX."compgroup a"
           . " WHERE a.status_cd = 'normal'"
           . " ORDER BY a.compgroup_id";
      
      $result = $db->query($sql);
      $ret = "<table class='xxlist' style='width:100%;' align='center'>"
           . "<thead><tr><td>"
           . "Competency Group</td><td style='text-align:right;'>"
           . "<input onclick='edit_compgroup(\"new\",this,event);' type='button' value='"._ADD."'/>"
           . "</td></tr></thead>"
           . "<tbody><tr><td colspan='2' id='tdcompgroup'>";
      if($db->getRowsNum($result)>0) {
         while(list($compgroup_id,$compgroup_nm,$description)=$db->fetchRow($result)) {
            $ret .= "<div id='dvcompgroup_${compgroup_id}' class='sb'><table style='border:0px;width:100%;'>"
                  . "<tr>"
                      . "<td><span id='sp_${compgroup_id}' class='xlnk' onclick='edit_compgroup(\"$compgroup_id\",this,event);'>".htmlentities(stripslashes($compgroup_nm))."</span></td>"
                  . "</tr></tbody></table></div>";
         }
      }
      $ret .= "</td></tr>";
      $ret .= "<tr style='display:none;' id='trempty'><td colspan='2'>"._EMPTY."</td></tr>";
      $ret .= "</tbody></table>";
      
      return $ret.$ajax->getJs()."<script type='text/javascript'><!--
      
      var wdv = null;
      function edit_compgroup(compgroup_id,d,e) {
         if(wdv) {
            if(wdv.compgroup_id != 'new' && wdv.compgroup_id == compgroup_id) {
               cancel_edit();
               return;
            } else {
               cancel_edit();
            }
         }
         wdv = _dce('div');
         wdv.compgroup_id = compgroup_id;
         if(compgroup_id=='new') {
            var dv = _dce('div');
            dv = $('tdcompgroup').insertBefore(dv,$('tdcompgroup').firstChild);
            dv.setAttribute('class','sb');
         } else {
            var dv = $('dvcompgroup_'+compgroup_id);
         }
         wdv.setAttribute('style','padding:10px;');
         wdv = dv.appendChild(wdv);
         wdv.appendChild(progress_span());
         wdv.dv = dv;
         ocjx_app_editCompetencyGroup(compgroup_id,function(_data) {
            wdv.innerHTML = _data;
            $('inp_compgroup_nm').focus();
         });
      }
      
      function cancel_edit() {
         wdv.dv.style.backgroundColor = '';
         if(wdv.compgroup_id=='new') {
            _destroy(wdv.dv);
         }
         wdv.dv = null;
         wdv.compgroup_id = null;
         _destroy(wdv);
         wdv = null;
      }
      
      function delete_compgroup() {
         var dv = wdv.dv;
         dv.style.backgroundColor = '#ffcccc';
         wdv.oldHTML = wdv.innerHTML;
         wdv.innerHTML = 'Are you sure you want to delete this competency group?<br/><br/>'
                       + '<input type=\"button\" value=\""._CANCEL."\" onclick=\"cancel_delete();\"/>&nbsp;&nbsp;'
                       + '<input type=\"button\" value=\""._DELETE."\" onclick=\"do_delete();\"/>';
      }
      
      function cancel_delete() {
         var dv = wdv.dv;
         dv.style.backgroundColor = '';
         wdv.innerHTML = wdv.oldHTML;
      }
      
      function do_delete() {
         ocjx_app_Delete(wdv.compgroup_id,null);
         var dv = wdv.dv;
         _destroy(dv);
         wdv.dv = null;
         wdv.compgroup_id = null;
         wdv = null;
      }
      
      function save_compgroup() {
         var compgroup_nm = urlencode($('inp_compgroup_nm').value);
         
         var description = urlencode($('description').value);
         wdv.innerHTML = '';
         wdv.appendChild(progress_span());
         ocjx_app_saveCompetencyGroup(wdv.compgroup_id,compgroup_nm,description,function(_data) {
            var dv = wdv.dv;
            wdv.compgroup_id = null;
            _destroy(wdv);
            wdv = null;
            if(_data=='EMPTY') {
               alert('Update failed.');
               return;
            }
            var data = recjsarray(_data);
            dv.setAttribute('id',data[0]);
            dv.innerHTML = data[1];
         });
      }
      
      // --></script>";
   }
   
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $this->listCompetencyGroup();
            break;
         default:
            $ret = $this->listCompetencyGroup();
            break;
      }
      return $ret;
   }
}

} // HRIS_EDITCOMPETENCYGROUP_DEFINED
?>