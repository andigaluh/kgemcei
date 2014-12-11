<?php
//--------------------------------------------------------------------//
// Filename : modules/sms/smsrefcorporatevision.php                   //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-12-16                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('SMS_SMSREFCORPORATEVISION_DEFINED') ) {
   define('SMS_SMSREFCORPORATEVISION_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/sms/modconsts.php");

class _sms_SMSRefCorporateVision extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _SMS_SMSREFCORPORATEVISION_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = "SMS Corporate Vision";
   var $display_comment = TRUE;
   var $data;
   
   function __construct($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function listRefCorporateVision() {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/sms/class/ajax_smsrefcorporatevision.php");
      $ajax = new _sms_class_SMSRefCorporateVisionAjax("psjx");
      
      $sql = "SELECT id,year,title"
           . " FROM sms_ref_corporate_vision"
           . " ORDER BY year DESC";
      
      $result = $db->query($sql);
      $ret = "<table class='xxlist' style='width:100%;' align='center'>"
           . "<thead><tr><td>"
           . "<span style='float:left;'>Ref Corporate Vision</span>"
           . "<span style='float:right;'><input onclick='new_refcorporatevision();' type='button' value='"._ADD."'/></span></td></tr>"
           . "</thead><tbody id='tbproc'>";
      if($db->getRowsNum($result)>0) {
         while(list($id,$year_refcorporatevision,$title_refcorporatevision)=$db->fetchRow($result)) {
            if($title_refcorporatevision=="") $title_refcorporatevision = _EMPTY;
            $ret .= "<tr><td id='tdclass_${id}'>"
                  . "<table><colgroup><col width='60'/><col/></colgroup><tbody><tr>"
                  . "<td id='td_year_${id}'>$year_refcorporatevision</td>"
                  . "<td><span id='sp_${id}' class='xlnk' onclick='edit_refcorporatevision(\"$id\",this,event);'>".htmlentities(stripslashes($title_refcorporatevision))."</span></td>"
                  . "</tr></tbody></table>"
                  . "</td></tr>";
         }
      }
      $ret .= "<tr style='display:none;' id='trempty'><td>"._EMPTY."</td></tr>";
      $ret .= "</tbody></table>";
      
      return $ret.$ajax->getJs()."

      <script type='text/javascript'><!--
      
      function new_refcorporatevision() {
         if(wdv) {
            cancel_edit();
         }
         var tre = $('trempty');
         var tr = _dce('tr');
         var td = tr.appendChild(_dce('td'));
         tr = tre.parentNode.insertBefore(tr,tre);
         wdv = _dce('div');
         wdv.td = td;
         psjx_app_newRefCorporateVision(function(_data) {
            var data = recjsarray(_data);
            wdv.td.setAttribute('id','tdclass_'+data[0]);
            wdv.td.innerHTML = data[1];
            wdv.td = null;
            wdv = null;
            edit_refcorporatevision(data[0],null,null);
         });
      }
      
      var wdv = null;
      function edit_refcorporatevision(id,d,e) {
         if(wdv) {
            if(wdv.id == id) {
               cancel_edit();
               return;
            } else {
               cancel_edit();
            }
         }
         wdv = _dce('div');
         wdv.id = id;
         var td = $('tdclass_'+id);
         wdv.setAttribute('style','padding:10px;');
         wdv = td.appendChild(wdv);
         wdv.appendChild(progress_span());
         wdv.td = td;
         psjx_app_editRefCorporateVision(id,function(_data) {
            wdv.innerHTML = _data;
            $('inp_refcorporatevision_title').focus();
         });
      }
      
      function cancel_edit() {
         wdv.td.style.backgroundColor = '';
         if(wdv.id=='new') {
            _destroy(wdv.td.parentNode);
         }
         wdv.id = null;
         _destroy(wdv);
         wdv = null;
      }
      
      function delete_refcorporatevision() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '#ffcccc';
         wdv.oldHTML = wdv.innerHTML;
         wdv.innerHTML = 'Are you sure you want to delete this refcorporatevision?<br/><br/>'
                       + '<input type=\"button\" value=\""._CANCEL."\" onclick=\"cancel_delete();\"/>&nbsp;&nbsp;'
                       + '<input type=\"button\" value=\""._DELETE."\" onclick=\"do_delete();\"/>';
      }
      
      function cancel_delete() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '';
         wdv.innerHTML = wdv.oldHTML;
      }
      
      function do_delete() {
         psjx_app_Delete(wdv.id,null);
         var tr = wdv.parentNode.parentNode;
         _destroy(tr);
         wdv.id = null;
         wdv = null;
      }
      
      function save_refcorporatevision() {
         var ret = parseForm('frm');
         $('progress').appendChild(progress_span('&nbsp;...saving&nbsp;&nbsp;'));
         psjx_app_saveRefCorporateVision(wdv.id,ret,function(_data) {
            var data = recjsarray(_data);
            $('sp_'+data[0]).innerHTML = data[1];
            $('td_year_'+data[0]).innerHTML = data[2];
            $('inp_refcorporatevision_title').focus();
            setTimeout(\"$('progress').innerHTML = '';\",1000);
         });
      }
      
      // --></script>";
   }
   
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $this->listRefCorporateVision();
            break;
         default:
            $ret = $this->listRefCorporateVision();
            break;
      }
      return $ret;
   }
}

} // SMS_SMSREFCORPORATEVISION_DEFINED
?>