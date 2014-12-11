<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/tsworkschedule.php                           //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_TSWORKSCHEDULE_DEFINED') ) {
   define('HRIS_TSWORKSCHEDULE_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_TSWorkSchedule extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_TSWORKSCHEDULE_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_TSWORKSCHEDULE_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_TSWorkSchedule($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function listWorkSchedule() {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_tsworkschedule.php");
      $ajax = new _hris_class_TSWorkScheduleAjax("ocjx");
      
      $sql = "SELECT ts_schedule_id,ts_schedule_nm,ts_schedule_abbr,ts_start_min_dttm,ts_start_max_dttm,ts_stop_min_dttm,ts_stop_max_dttm,ts_constraint,ts_interval,description"
           . " FROM ".XOCP_PREFIX."ts_schedule"
           . " ORDER BY ts_schedule_id";
      
      $result = $db->query($sql);
      $ret = "<table class='xxlist' style='width:100%;' align='center'><thead><tr><td>"
           . "<span style='float:left;'>Work Schedule</span>"
           . "<span style='float:right;'><input onclick='edit_tsschedule(\"new\",this,event);' type='button' value='"._ADD."'/></span></td></tr></thead><tbody id='tbproc'>";
      if($db->getRowsNum($result)>0) {
         while(list($ts_schedule_id,$ts_schedule_nm,$ts_schedule_abbr,$ts_start_min_dttm,$ts_start_max_dttm,$ts_stop_min_dttm,$ts_stop_max_dttm,$ts_constraint,$ts_interval,$description)=$db->fetchRow($result)) {
            $ret .= "<tr><td id='tdtsschedule_${ts_schedule_id}'>"
            
                  . "<table border='0' class='ilist'>"
                  . "<colgroup><col width='40'/><col/></colgroup>"
                  . "<tbody><tr>"
                  . "<td>$ts_schedule_abbr</td>"
                  . "<td><span id='sp_${ts_schedule_id}' class='xlnk' onclick='edit_tsschedule(\"$ts_schedule_id\",this,event);'>".htmlentities(stripslashes($ts_schedule_nm))."</span></td>"
                  . "</tr></tbody></table>"
                  
                  . "</td></tr>";
         }
      }
      $ret .= "<tr style='display:none;' id='trempty'><td>"._EMPTY."</td></tr>";
      $ret .= "</tbody></table>";
      
      return $ret.$ajax->getJs()."<script type='text/javascript'><!--
      
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
      function edit_tsschedule(ts_schedule_id,d,e) {
         if(wdv) {
            if(wdv.ts_schedule_id != 'new' && wdv.ts_schedule_id == ts_schedule_id) {
               cancel_edit();
               return;
            } else {
               cancel_edit();
            }
         }
         wdv = _dce('div');
         wdv.ts_schedule_id = ts_schedule_id;
         if(ts_schedule_id=='new') {
            var tre = $('trempty');
            var tr = _dce('tr');
            var td = tr.appendChild(_dce('td'));
            tr = tre.parentNode.insertBefore(tr,tre);
         } else {
            var td = $('tdtsschedule_'+ts_schedule_id);
         }
         wdv.setAttribute('style','padding:10px;');
         wdv = td.appendChild(wdv);
         wdv.appendChild(progress_span());
         wdv.td = td;
         ocjx_app_editWorkSchedule(ts_schedule_id,function(_data) {
            wdv.innerHTML = _data;
            $('inp_tsschedule_nm').focus();
         });
      }
      
      function cancel_edit() {
         wdv.td.style.backgroundColor = '';
         if(wdv.ts_schedule_id=='new') {
            _destroy(wdv.td.parentNode);
         }
         wdv.ts_schedule_id = null;
         _destroy(wdv);
         wdv = null;
      }
      
      function delete_tsschedule() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '#ffcccc';
         wdv.oldHTML = wdv.innerHTML;
         wdv.innerHTML = 'Are you sure you want to delete this work schedule?<br/><br/>'
                       + '<input type=\"button\" value=\""._CANCEL."\" onclick=\"cancel_delete();\"/>&nbsp;&nbsp;'
                       + '<input type=\"button\" value=\""._DELETE."\" onclick=\"do_delete();\"/>';
      }
      
      function cancel_delete() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '';
         wdv.innerHTML = wdv.oldHTML;
      }
      
      function do_delete() {
         ocjx_app_Delete(wdv.ts_schedule_id,null);
         var tr = wdv.parentNode.parentNode;
         _destroy(tr);
         wdv.ts_schedule_id = null;
         wdv = null;
      }
      
      function save_tsschedule() {
         var ret = _parseForm('frm');
         wdv.innerHTML = '';
         wdv.appendChild(progress_span());
         ocjx_app_saveWorkSchedule(wdv.ts_schedule_id,ret,function(_data) {
            var td = wdv.td;
            wdv.ts_schedule_id = null;
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
            $this->listWorkSchedule();
            break;
         default:
            $ret = $this->listWorkSchedule();
            break;
      }
      return $ret;
   }
}

} // HRIS_TSWORKSCHEDULE_DEFINED
?>