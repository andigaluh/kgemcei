<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/assessment_session.php                     //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-12-17                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_ASSESSMENTSESSION_DEFINED') ) {
   define('HRIS_ASSESSMENTSESSION_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_AssessmentSession extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_ASSESSMENTSESSION_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_ASSESSMENTSESSION_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_AssessmentSession($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function listSession() {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_asmsession.php");
      $ajax = new _hris_class_AssessmentSessionAjax("ocjx");
      
      $sql = "SELECT asid,session_nm,session_periode"
           . " FROM ".XOCP_PREFIX."assessment_session"
           . " WHERE status_cd = 'normal'"
           . " ORDER BY session_periode DESC";
      
      $result = $db->query($sql);
      $ret = "<table class='xxlist' style='width:100%;' align='center'>"
           . "<thead><tr><td>"
           . "<span style='float:left;'>Sessions</span>"
           . "<span style='float:right;'><input onclick='new_session();' type='button' value='"._ADD."'/></span></td></tr>"
           . "</thead><tbody id='tbproc'>";
      if($db->getRowsNum($result)>0) {
         while(list($asid,$session_nm,$session_periode)=$db->fetchRow($result)) {
            if($session_nm=="") $session_nm = _EMPTY;
            $ret .= "<tr><td id='tdclass_${asid}'>"
                  . "<table><colgroup><col width='60'/><col/></colgroup><tbody><tr>"
                  . "<td id='td_periode_${asid}'>$session_periode</td>"
                  . "<td><span id='sp_${asid}' class='xlnk' onclick='edit_session(\"$asid\",this,event);'>".htmlentities(stripslashes($session_nm))."</span></td>"
                  . "</tr></tbody></table>"
                  . "</td></tr>";
         }
      }
      $ret .= "<tr style='display:none;' id='trempty'><td>"._EMPTY."</td></tr>";
      $ret .= "</tbody></table>";
      
      return $ret.$ajax->getJs()."<script src='".XOCP_SERVER_SUBDIR."/include/calendar.js' type='text/javascript'></script><script type='text/javascript'><!--
      
      var dvreset = null;
      function reset_pass_session(asid,d,e) {
         d.oldHTML = d.innerHTML;
         dvreset = d;
         d.innerHTML = '<div style=\"background-color:#ffcccc;padding:10px;\">You are going to reset assessor password for this session.<br/>Are you sure?<br/><br/>'
                     + '<input type=\"button\" value=\"Yes (reset)\" onclick=\"do_reset_pass(\\''+asid+'\\');\"/>&nbsp;&nbsp;<input type=\"button\" value=\"No (cancel)\" onclick=\"cancel_reset_pass();\"/>'
                     + '</div>';
      }
      
      function xlspass(asid) {
         location.href = '".XOCP_SERVER_SUBDIR."/modules/hris/xlspass.php?asid='+asid;
      }
      
      function do_reset_pass(asid) {
         dvreset.innerHTML = '';
         dvreset.appendChild(progress_span(' ... reseting password'));
         $('savegenerate_btn').disabled = true;
         $('savegenerate_btn').setAttribute('disabled','1');
         $('btn_save_session').disabled = true;
         $('btn_save_session').setAttribute('disabled','1');
         $('btn_cancel_edit').disabled = true;
         $('btn_cancel_edit').setAttribute('disabled','1');
         $('btn_delete_session').disabled = true;
         $('btn_delete_session').setAttribute('disabled','1');
         //$('btn_xlspass').disabled = true;
         //$('btn_xlspass').setAttribute('disabled','1');
         //$('btn_reset_password').disabled = true;
         //$('btn_reset_password').setAttribute('disabled','1');
         
         
         ocjx_app_resetSessionPass(asid,function(_data) {
            dvreset.innerHTML = '<div style=\"background-color:#ffcccc;padding:10px;\">Done.<br/><br/>'
                              + '<input type=\"button\" value=\"Ok\" onclick=\"cancel_reset_pass();\"/>'
                              + '</div>';
         });
      }
      
      function cancel_reset_pass() {
         dvreset.innerHTML = dvreset.oldHTML;
         $('savegenerate_btn').disabled = false;
         $('savegenerate_btn').removeAttribute('disabled');
         $('btn_save_session').disabled = false;
         $('btn_save_session').removeAttribute('disabled');
         $('btn_cancel_edit').disabled = false;
         $('btn_cancel_edit').removeAttribute('disabled');
         $('btn_delete_session').disabled = false;
         $('btn_delete_session').removeAttribute('disabled');
        
      }
      
      function generate_superior(asid,d,e) {
         var td = d.parentNode;
         var dv = td.appendChild(_dce('div'));
         dv.setAttribute('style','padding:5px;text-align:center;background-color:#ffcccc;margin-top:5px;');
         dv.setAttribute('id','dvsuperior_'+asid);
         $('gsuperiorprogress').innerHTML = '';
         dv.innerHTML = 'Are you sure you want to generate superior assessor?<br/><br/>'
                      + '<input type=\"button\" value=\"Yes\" onclick=\"do_generate_superior(\\''+asid+'\\');\"/>&nbsp;&nbsp;'
                      + '<input type=\"button\" value=\"No\" onclick=\"cancel_generate_superior(\\''+asid+'\\');\"/>';
      }
      
      function do_generate_superior(asid) {
         _destroy($('dvsuperior_'+wdv.asid));
         $('gsuperiorprogress').innerHTML = '';
         $('gsuperiorprogress').appendChild(progress_span());
         ocjx_app_generateSuperior(asid,function(_data) {
            $('gsuperiorprogress').innerHTML = '&nbsp&nbsp;<span style=\"color:blue;\">Done.</span>';
         });
      }
      
      function cancel_generate_superior(asid) {
         _destroy($('dvsuperior_'+asid));
      }
      
      function generate360(asid,d,e) {
         if($('dv360_'+asid)) {
            _destroy($('dv360_'+asid));
            return;
         }
         var td = d.parentNode;
         var dv = td.appendChild(_dce('div'));
         dv.setAttribute('style','padding:5px;text-align:center;background-color:#ffcccc;margin-top:5px;');
         dv.setAttribute('id','dv360_'+asid);
         $('g360progress').innerHTML = '';
         dv.innerHTML = 'Are you sure you want to generate assessor?<br/><br/>'
                      + '<input type=\"button\" value=\"Yes\" onclick=\"do_generate360(\\''+asid+'\\');\"/>&nbsp;&nbsp;'
                      + '<input type=\"button\" value=\"No\" onclick=\"cancel_generate360(\\''+asid+'\\');\"/>';
      }
      
      function do_generate360(asid) {
         var ret = parseForm('frm');
         _destroy($('dv360_'+wdv.asid));
         $('g360progress').innerHTML = '';
         $('g360progress').appendChild(progress_span(' ... saving'));
         $('savegenerate_btn').disabled = true;
         $('savegenerate_btn').setAttribute('disabled','1');
         $('btn_save_session').disabled = true;
         $('btn_save_session').setAttribute('disabled','1');
         $('btn_cancel_edit').disabled = true;
         $('btn_cancel_edit').setAttribute('disabled','1');
         $('btn_delete_session').disabled = true;
         $('btn_delete_session').setAttribute('disabled','1');
         $('btn_xlspass').disabled = true;
         $('btn_xlspass').setAttribute('disabled','1');
         $('btn_reset_password').disabled = true;
         $('btn_reset_password').setAttribute('disabled','1');
         ocjx_app_saveSession(wdv.asid,ret,function(_data) {   /// first phase : saving
            var data = recjsarray(_data);
            setTimeout('do_generate360_secondphase()',1000);
         });
      }
      
      function do_generate360_secondphase() {                  /// second phase : generating
         _destroy($('dv360_'+wdv.asid));
         $('g360progress').innerHTML = '';
         $('g360progress').appendChild(progress_span(' ... generating'));
         ocjx_app_generate360(wdv.asid,function(_data) {
            setTimeout(\"$('g360progress').innerHTML = '&nbsp&nbsp;<span style=\\\'color:blue;\\\'>Done in \"+_data+\" second.</span>'\",1000);
            $('savegenerate_btn').disabled = false;
            $('savegenerate_btn').removeAttribute('disabled');
            $('btn_save_session').disabled = false;
            $('btn_save_session').removeAttribute('disabled');
            $('btn_cancel_edit').disabled = false;
            $('btn_cancel_edit').removeAttribute('disabled');
            $('btn_delete_session').disabled = false;
            $('btn_delete_session').removeAttribute('disabled');
            $('btn_xlspass').disabled = false;
            $('btn_xlspass').removeAttribute('disabled');
            $('btn_reset_password').disabled = false;
            $('btn_reset_password').removeAttribute('disabled');
         });
      }
      
      
      function cancel_generate360(asid) {
         _destroy($('dv360_'+asid));
      }
      
      function chstart(d,e) {
         if(!d.obdt) {
            var cal = new calendarClass('obdtstart',function(dt) {
               var obdt = $('obdtstart');
               $('assessment_start_txt').innerHTML = obdt.obj.toString(obdt.obj.getResult(),'datetime');
               $('assessment_start').value = dt;
            });
            cal.div.style.position = 'absolute';
            cal.div.style.visibility='hidden';
            d.obdt=d.parentNode.appendChild(cal.div);
            d.obdt.style.left = (oX(d))+'px';
            d.obdt.style.top = (oY(d)+d.offsetHeight)+'px';
            d.obdt.style.visibility='visible';
            d.obdt.obj.setDTTM($('assessment_start').value);
         } else {
            d.obdt.obj.hide();
            _destroy(d.obdt);
            d.obdt = null;
         }
      }
      
      function chstop(d,e) {
         if(!d.obdt) {
            var cal = new calendarClass('obdtstop',function(dt) {
               var obdt = $('obdtstop');
               $('assessment_stop_txt').innerHTML = obdt.obj.toString(obdt.obj.getResult(),'datetime');
               $('assessment_stop').value = dt;
            });
            cal.div.style.position = 'absolute';
            cal.div.style.visibility='hidden';
            d.obdt=d.parentNode.appendChild(cal.div);
            d.obdt.style.left = (oX(d))+'px';
            d.obdt.style.top = (oY(d)+d.offsetHeight)+'px';
            d.obdt.style.visibility='visible';
            d.obdt.obj.setDTTM($('assessment_stop').value);
         } else {
            d.obdt.obj.hide();
            _destroy(d.obdt);
            d.obdt = null;
         }
      }
      
      
      
      function new_session() {
         if(wdv) {
            cancel_edit();
         }
         var tre = $('trempty');
         var tr = _dce('tr');
         var td = tr.appendChild(_dce('td'));
         tr = tre.parentNode.insertBefore(tr,tre);
         wdv = _dce('div');
         wdv.td = td;
         ocjx_app_newSession(function(_data) {
            var data = recjsarray(_data);
            wdv.td.setAttribute('id','tdclass_'+data[0]);
            wdv.td.innerHTML = data[1];
            wdv.td = null;
            wdv = null;
            edit_session(data[0],null,null);
         });
      }
      
      var wdv = null;
      function edit_session(asid,d,e) {
         if(wdv) {
            if(wdv.asid == asid) {
               cancel_edit();
               return;
            } else {
               cancel_edit();
            }
         }
         wdv = _dce('div');
         wdv.asid = asid;
         var td = $('tdclass_'+asid);
         wdv.setAttribute('style','padding:10px;');
         wdv = td.appendChild(wdv);
         wdv.appendChild(progress_span());
         wdv.td = td;
         ocjx_app_editSession(asid,function(_data) {
            wdv.innerHTML = _data;
            $('inp_session_nm').focus();
         });
      }
      
      function cancel_edit() {
         wdv.td.style.backgroundColor = '';
         if(wdv.asid=='new') {
            _destroy(wdv.td.parentNode);
         }
         wdv.asid = null;
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
         ocjx_app_Delete(wdv.asid,null);
         var tr = wdv.parentNode.parentNode;
         _destroy(tr);
         wdv.asid = null;
         wdv = null;
      }
      
      function save_session() {
         var ret = parseForm('frm');
         $('progress').appendChild(progress_span('&nbsp;...saving&nbsp;&nbsp;'));
         ocjx_app_saveSession(wdv.asid,ret,function(_data) {
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

} // HRIS_ASSESSMENTSESSION_DEFINED
?>