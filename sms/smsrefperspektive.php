<?php
//--------------------------------------------------------------------//
// Filename : modules/sms/smsrefperspektive.php                       //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-12-17                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('SMS_REFPERSPEKTIVE_DEFINED') ) {
   define('SMS_REFPERSPEKTIVE_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/sms/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/sms/class/selectsms.php");

class _sms_SMSRefPerspektive extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _SMS_REFPERSPEKTIVE_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = "SMS Perspektive";
   var $display_comment = TRUE;
   var $data;
   
   function __construct($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function listRefPerspektive() {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/sms/class/ajax_smsrefperspektive.php");
      $ajax = new _sms_class_SMSRefPerspektiveAjax("psjx");

      $smsselobj = new _sms_class_SelectSession();
      $smssel = "<div style='padding-bottom:2px;'>".$smsselobj->show()."</div>";
      
      if(!isset($_SESSION["sms_id"])||$_SESSION["sms_id"]==0) {
         return $smssel;
      }

      $psid = $_SESSION["sms_id"];
      
      $sql = "SELECT id,code,title"
           . " FROM sms_ref_perspektive WHERE session = '$psid'"
           . " ORDER BY code DESC";
      
      $result = $db->query($sql);
      $ret = "<table class='xxlist' style='width:100%;' align='center'>"
           . "<thead><tr><td>"
           . "<span style='float:left;'>Ref Perspektives</span>"
           . "<span style='float:right;'><input onclick='new_refperspektive();' type='button' value='"._ADD."'/></span></td></tr>"
           . "</thead><tbody id='tbproc'>";
      if($db->getRowsNum($result)>0) {
         while(list($id,$code_refperspektive,$title_refperspektive)=$db->fetchRow($result)) {
            if($title_refperspektive=="") $title_refperspektive = _EMPTY;
            $ret .= "<tr><td id='tdclass_${id}'>"
                  . "<table><colgroup><col width='60'/><col/></colgroup><tbody><tr>"
                  . "<td id='td_code_${id}'>$code_refperspektive</td>"
                  . "<td><span id='sp_${id}' class='xlnk' onclick='edit_refperspektive(\"$id\",this,event);'>".htmlentities(stripslashes($title_refperspektive))."</span></td>"
                  . "</tr></tbody></table>"
                  . "</td></tr>";
         }
      }
      $ret .= "<tr style='display:none;' id='trempty'><td>"._EMPTY."</td></tr>";
      $ret .= "</tbody></table>";
      
      return $smssel.$ret.$ajax->getJs()."

      <script type='text/javascript'><!--
      
      function new_refperspektive() {
         if(wdv) {
            cancel_edit();
         }
         var tre = $('trempty');
         var tr = _dce('tr');
         var td = tr.appendChild(_dce('td'));
         tr = tre.parentNode.insertBefore(tr,tre);
         wdv = _dce('div');
         wdv.td = td;
         psjx_app_newRefPerspektive(function(_data) {
            var data = recjsarray(_data);
            wdv.td.setAttribute('id','tdclass_'+data[0]);
            wdv.td.innerHTML = data[1];
            wdv.td = null;
            wdv = null;
            edit_refperspektive(data[0],null,null);
         });
      }
      
      var wdv = null;
      function edit_refperspektive(id,d,e) {
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
         psjx_app_editRefPerspektive(id,function(_data) {
            wdv.innerHTML = _data;
            $('inp_refperspektive_code').focus();
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
      
      function delete_refperspektive() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '#ffcccc';
         wdv.oldHTML = wdv.innerHTML;
         wdv.innerHTML = 'Are you sure you want to delete this refperspektive?<br/><br/>'
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
      
      function save_refperspektive() {
         var ret = parseForm('frm');
         $('progress').appendChild(progress_span('&nbsp;...saving&nbsp;&nbsp;'));
         psjx_app_saveRefPerspektive(wdv.id,ret,function(_data) {
            var data = recjsarray(_data);
            $('sp_'+data[0]).innerHTML = data[1];
            $('td_code_'+data[0]).innerHTML = data[2];
            $('inp_refperspektive_code').focus();
            setTimeout(\"$('progress').innerHTML = '';\",1000);
         });
      }
      
      // --></script>";
   }
   
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $this->listRefPerspektive();
            break;
         default:
            $ret = $this->listRefPerspektive();
            break;
      }
      return $ret;
   }
}

} // SMS_REFPERSPEKTIVE_DEFINED
?>