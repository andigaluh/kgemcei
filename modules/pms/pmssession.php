<?php
//--------------------------------------------------------------------//
// Filename : modules/pms/pmssession.php                              //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-12-17                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('PMS_PMSSESSION_DEFINED') ) {
   define('PMS_PMSSESSION_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/pms/modconsts.php");

class _pms_PMSSession extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _PMS_PMSSESSION_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _PMS_PMSSESSION_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function __construct($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function listSession() {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/pms/class/ajax_pmssession.php");
      $ajax = new _pms_class_PMSSessionAjax("psjx");
      
      $sql = "SELECT psid,session_nm,session_periode"
           . " FROM pms_session"
           . " WHERE status_cd = 'normal'"
           . " ORDER BY session_periode DESC";
      
      $result = $db->query($sql);
      $ret = "<table class='xxlist' style='width:100%;' align='center'>"
           . "<thead><tr><td>"
           . "<span style='float:left;'>Sessions</span>"
           . "<span style='float:right;'><input onclick='new_session();' type='button' value='"._ADD."'/></span></td></tr>"
           . "</thead><tbody id='tbproc'>";
      if($db->getRowsNum($result)>0) {
         while(list($psid,$session_nm,$session_periode)=$db->fetchRow($result)) {
            if($session_nm=="") $session_nm = _EMPTY;
            $ret .= "<tr><td id='tdclass_${psid}'>"
                  . "<table><colgroup><col width='60'/><col/></colgroup><tbody><tr>"
                  . "<td id='td_periode_${psid}'>$session_periode</td>"
                  . "<td><span id='sp_${psid}' class='xlnk' onclick='edit_session(\"$psid\",this,event);'>".htmlentities(stripslashes($session_nm))."</span></td>"
                  . "</tr></tbody></table>"
                  . "</td></tr>";
         }
      }
      $ret .= "<tr style='display:none;' id='trempty'><td>"._EMPTY."</td></tr>";
      $ret .= "</tbody></table>";
      
      return $ret.$ajax->getJs()."<script src='".XOCP_SERVER_SUBDIR."/include/calendar.js' type='text/javascript'></script><script type='text/javascript'><!--
      
      function new_session() {
         
         psjx_app_testtraining(function(_data) {
            
         });
         
         return;
         
         if(wdv) {
            cancel_edit();
         }
         var tre = $('trempty');
         var tr = _dce('tr');
         var td = tr.appendChild(_dce('td'));
         tr = tre.parentNode.insertBefore(tr,tre);
         wdv = _dce('div');
         wdv.td = td;
         psjx_app_newSession(function(_data) {
            var data = recjsarray(_data);
            wdv.td.setAttribute('id','tdclass_'+data[0]);
            wdv.td.innerHTML = data[1];
            wdv.td = null;
            wdv = null;
            edit_session(data[0],null,null);
         });
      }
      
      var wdv = null;
      function edit_session(psid,d,e) {
         if(wdv) {
            if(wdv.psid == psid) {
               cancel_edit();
               return;
            } else {
               cancel_edit();
            }
         }
         wdv = _dce('div');
         wdv.psid = psid;
         var td = $('tdclass_'+psid);
         wdv.setAttribute('style','padding:10px;');
         wdv = td.appendChild(wdv);
         wdv.appendChild(progress_span());
         wdv.td = td;
         psjx_app_editSession(psid,function(_data) {
            wdv.innerHTML = _data;
            $('inp_session_nm').focus();
         });
      }
      
      function cancel_edit() {
         wdv.td.style.backgroundColor = '';
         if(wdv.psid=='new') {
            _destroy(wdv.td.parentNode);
         }
         wdv.psid = null;
         _destroy(wdv);
         wdv = null;
      }
      
      function delete_session() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '#ffcccc';
         wdv.oldHTML = wdv.innerHTML;
         wdv.innerHTML = 'Are you sure you want to delete this session?<br/><br/>'
                       + '<input type=\"button\" value=\""._CANCEL."\" onclick=\"cancel_delete();\"/>&nbsp;&nbsp;'
                       + '<input type=\"button\" value=\""._DELETE."\" onclick=\"do_delete();\"/>';
      }
      
      function cancel_delete() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '';
         wdv.innerHTML = wdv.oldHTML;
      }
      
      function do_delete() {
         psjx_app_Delete(wdv.psid,null);
         var tr = wdv.parentNode.parentNode;
         _destroy(tr);
         wdv.psid = null;
         wdv = null;
      }
      
      function save_session() {
         var ret = parseForm('frm');
         $('progress').appendChild(progress_span('&nbsp;...saving&nbsp;&nbsp;'));
         psjx_app_saveSession(wdv.psid,ret,function(_data) {
            var data = recjsarray(_data);
            $('sp_'+data[0]).innerHTML = data[1];
            $('td_periode_'+data[0]).innerHTML = data[2];
            $('inp_session_nm').focus();
            setTimeout(\"$('progress').innerHTML = '';\",1000);
         });
      }
      
      // --></script>";
   }
   
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $this->listSession();
            break;
         default:
            $ret = $this->listSession();
            break;
      }
      return $ret;
   }
}

} // PMS_PMSSESSION_DEFINED
?>