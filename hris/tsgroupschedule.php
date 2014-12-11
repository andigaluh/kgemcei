<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/tsgroupschedule.php                           //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_TSGROUPSCHEDULE_DEFINED') ) {
   define('HRIS_TSGROUPSCHEDULE_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_TSGroupSchedule extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_TSGROUPSCHEDULE_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_TSGROUPSCHEDULE_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_TSGroupSchedule($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function listGroupSchedule() {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_tsgroupschedule.php");
      $ajax = new _hris_class_TSGroupScheduleAjax("ocjx");
      
      $sql = "SELECT ts_group_id,ts_group_nm,ts_group_abbr,description"
           . " FROM ".XOCP_PREFIX."ts_group"
           . " ORDER BY ts_group_id";
      
      $result = $db->query($sql);
      $ret = "<table class='xxlist' style='width:100%;' align='center'><thead><tr><td>"
           . "<span style='float:left;'>Work Group</span>"
           . "<span style='float:right;'>&nbsp;</span></td></tr></thead><tbody id='tbproc'>";
      if($db->getRowsNum($result)>0) {
         while(list($ts_group_id,$ts_group_nm,$ts_group_abbr,$description)=$db->fetchRow($result)) {
            $ret .= "<tr><td id='tdtsgroup_${ts_group_id}'>"
            
                  . "<table border='0' class='ilist'>"
                  . "<colgroup><col width='40'/><col/></colgroup>"
                  . "<tbody><tr>"
                  . "<td>$ts_group_abbr</td>"
                  . "<td><span id='sp_${ts_group_id}' class='xlnk' onclick='edit_tsgroup(\"$ts_group_id\",this,event);'>".htmlentities(stripslashes($ts_group_nm))."</span></td>"
                  . "</tr></tbody></table>"
                  
                  . "</td></tr>";
         }
      }
      $ret .= "<tr style='display:none;' id='trempty'><td>"._EMPTY."</td></tr>";
      $ret .= "</tbody></table>";
      
      return $ret.$ajax->getJs()."<script type='text/javascript'><!--
      
      function onclick_overtime_indicator(ts_group_id,ts_sequence_id,d,e) {
         ajax_feedback = _caf;
         var checked = 0;
         if(d.checked) {
            checked = 1;
         }
         ocjx_app_saveOverTimeIndicator(ts_group_id,ts_sequence_id,checked,null);
      }
      
      function chg_schedule(ts_group_id,ts_sequence_id,d,e) {
         ajax_feedback = _caf;
         var ts_schedule_id = d.options[d.selectedIndex].value;
         ocjx_app_changeSchedule(ts_group_id,ts_sequence_id,ts_schedule_id,function(_data) {
            var data = recjsarray(_data);
            var tr = $('trseq_'+data[0]);
            tr.firstChild.nextSibling.nextSibling.innerHTML = data[1];
            tr.firstChild.nextSibling.nextSibling.nextSibling.innerHTML = data[2];
            tr.firstChild.nextSibling.nextSibling.nextSibling.nextSibling.innerHTML = data[3];
            tr.firstChild.nextSibling.nextSibling.nextSibling.nextSibling.nextSibling.innerHTML = data[4];
            tr.firstChild.nextSibling.nextSibling.nextSibling.nextSibling.nextSibling.nextSibling.innerHTML = data[5];
            $('ttl_tmlen').innerHTML = data[6];
         });
      }
      
      function add_sequence(ts_group_id) {
         ajax_feedback = _caf;
         ocjx_app_addSequence(ts_group_id,function(_data) {
            var data = recjsarray(_data);
            var tbody = $('seqbody');
            var tr = _dce('tr');
            tr.td0 = tr.appendChild(_dce('td'));
            tr.td1 = tr.appendChild(_dce('td'));
            tr.td2 = tr.appendChild(_dce('td'));
            tr.td3 = tr.appendChild(_dce('td'));
            tr.td4 = tr.appendChild(_dce('td'));
            tr.td5 = tr.appendChild(_dce('td'));
            tr.td6 = tr.appendChild(_dce('td'));
            tr.setAttribute('id','trseq_'+data[0]);
            tr.td0.innerHTML = data[0];
            tr.td1.innerHTML = data[1];
            tr.td2.innerHTML = '-';
            tr.td3.innerHTML = '-';
            tr.td4.innerHTML = '-';
            tr.td5.innerHTML = '-';
            tr.td6.innerHTML = '-';
            tbody.insertBefore(tr,$('tr_empty'));
            $('tr_empty').firstChild.style.display = 'none';
         });
      }
      
      function chg_constraint(d,e) {
         var constraint = d.options[d.selectedIndex].value;
         if(constraint=='interval') {
            $('trinterval').style.display = '';
            _dsa($('ts_interval'));
         } else {
            $('trinterval').style.display = 'none';
         }
      }
      
      var wdv = null;
      function edit_tsgroup(ts_group_id,d,e) {
         if(wdv) {
            if(wdv.ts_group_id != 'new' && wdv.ts_group_id == ts_group_id) {
               cancel_edit();
               return;
            } else {
               cancel_edit();
            }
         }
         wdv = _dce('div');
         wdv.ts_group_id = ts_group_id;
         if(ts_group_id=='new') {
            var tre = $('trempty');
            var tr = _dce('tr');
            var td = tr.appendChild(_dce('td'));
            tr = tre.parentNode.insertBefore(tr,tre);
         } else {
            var td = $('tdtsgroup_'+ts_group_id);
         }
         wdv.setAttribute('style','padding:10px;');
         wdv = td.appendChild(wdv);
         wdv.appendChild(progress_span());
         wdv.td = td;
         ocjx_app_editGroupSchedule(ts_group_id,function(_data) {
            wdv.innerHTML = _data;
         });
      }
      
      function cancel_edit() {
         wdv.td.style.backgroundColor = '';
         if(wdv.ts_group_id=='new') {
            _destroy(wdv.td.parentNode);
         }
         wdv.ts_group_id = null;
         _destroy(wdv);
         wdv = null;
      }
      
      function delete_tsgroup() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '#ffcccc';
         wdv.oldHTML = wdv.innerHTML;
         wdv.innerHTML = 'Are you sure you want to delete this work group?<br/><br/>'
                       + '<input type=\"button\" value=\""._CANCEL."\" onclick=\"cancel_delete();\"/>&nbsp;&nbsp;'
                       + '<input type=\"button\" value=\""._DELETE."\" onclick=\"do_delete();\"/>';
      }
      
      function cancel_delete() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '';
         wdv.innerHTML = wdv.oldHTML;
      }
      
      function do_delete() {
         ocjx_app_Delete(wdv.ts_group_id,null);
         var tr = wdv.parentNode.parentNode;
         _destroy(tr);
         wdv.ts_group_id = null;
         wdv = null;
      }
      
      function save_tsgroup() {
         var ret = _parseForm('frm');
         wdv.innerHTML = '';
         wdv.appendChild(progress_span());
         ocjx_app_saveGroupSchedule(wdv.ts_group_id,ret,function(_data) {
            var td = wdv.td;
            wdv.ts_group_id = null;
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
            $this->listGroupSchedule();
            break;
         default:
            $ret = $this->listGroupSchedule();
            break;
      }
      return $ret;
   }
}

} // HRIS_TSGROUPSCHEDULE_DEFINED
?>