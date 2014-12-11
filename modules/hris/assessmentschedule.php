<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/assessmentschedule.php                     //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2010-06-21                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_ASSESSMENTSCHEDULE_DEFINED') ) {
   define('HRIS_ASSESSMENTSCHEDULE_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_AssessmentSchedule extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_ASSESSMENTSCHEDULE_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_ASSESSMENTSCHEDULE_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_AssessmentSchedule($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
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
//           . " AND session_t = 'idp'"
           . " ORDER BY session_periode DESC";
      
      $result = $db->query($sql);
      $ret = "<table class='xxlist' style='width:100%;' align='center'>"
           . "<thead><tr><td>"
           . "<span style='float:left;'>Sessions</span>"
           . "</td></tr>"
           . "</thead><tbody id='tbproc'>";
      if($db->getRowsNum($result)>0) {
         while(list($asid,$session_nm,$session_periode)=$db->fetchRow($result)) {
            if($session_nm=="") $session_nm = _EMPTY;
            
            $sched = "";
            $sql = "SELECT a.schedule_id,b.org_nm,a.start_dttm,a.stop_dttm,c.org_class_nm"
                 . " FROM ".XOCP_PREFIX."assessment_schedule a"
                 . " LEFT JOIN ".XOCP_PREFIX."orgs b ON b.org_id = a.org_id"
                 . " LEFT JOIN ".XOCP_PREFIX."org_class c ON c.org_class_id = b.org_class_id"
                 . " WHERE a.asid = '$asid'"
                 . " AND a.status_cd = 'normal'"
                 . " ORDER BY b.org_class_id,a.start_dttm,a.stop_dttm";
            $rsc = $db->query($sql);
            if($db->getRowsNum($rsc)>0) {
               while(list($schedule_id,$org_nm,$start_dttm,$stop_dttm,$org_class_nm)=$db->fetchRow($rsc)) {
                  $sched .= "<tr id='trsx_${schedule_id}'><td><span class='xlnk' onclick='edit_schedule(\"$asid\",\"$schedule_id\",this,event);'>$org_nm $org_class_nm</span></td><td>"
                          . "<span id='sptxtstart_${schedule_id}'>".sql2ind($start_dttm)."</span>"
                          . "</td>"
                          . "<td>"
                          . "<span id='sptxtstop_${schedule_id}'>".sql2ind($stop_dttm)."</span>"
                          . "</td>"
                          . "</tr>";
               }
               $sched .= "<tr id='trempty' style='display:none;'><td style='text-align:center;font-style:italic;' colspan='3'>"._EMPTY."</td></tr>";
            } else {
               $sched .= "<tr id='trempty'><td style='text-align:center;font-style:italic;' colspan='3'>"._EMPTY."</td></tr>";
            }
            $ret .= "<tr><td id='tdclass_${asid}'>"
                  . "<table><colgroup><col width='60'/><col/></colgroup><tbody><tr>"
                  . "<td style='vertical-align:top;' id='td_periode_${asid}'>$session_periode</td>"
                  . "<td style='vertical-align:top;'>"
                  . htmlentities(stripslashes($session_nm))
                  . "<div style='padding:0px;padding-top:10px;'>"
                     . "Schedule:"
                     . "<table class='xxlist' style='width:560px;'><thead><tr><td>Division/Section</td><td>Start</td><td>Stop</td></tr></thead>"
                     . "<tbody id='tbodysched_${asid}'>"
                     . $sched
                     . "</tbody>"
                     . "<tfoot><tr><td style='text-align:right;' colspan='3'><input onclick='add_schedule(\"$asid\",this,event);' type='button' value='"._ADD."' id='addbutton'/></td></tr></tfoot>"
                     . "</table>"
                  . "</div>"
                  . "</td>"
                  . "</tr></tbody></table>"
                  . "</td></tr>";
         }
      }
      $ret .= "<tr style='display:none;' id='trempty'><td>"._EMPTY."</td></tr>";
      $ret .= "</tbody></table>";
      
      return $ret.$ajax->getJs()."<script src='".XOCP_SERVER_SUBDIR."/include/calendar.js' type='text/javascript'></script><script type='text/javascript'><!--
      
      function do_delete_schedule() {
         var schedule_id = trs.schedule_id;
         _destroy($('trsx_'+schedule_id));
         _destroy(trs);
         trs.schedule_id = null;
         trs = null;
         ocjx_app_deleteSchedule(schedule_id,null);
      }
      
      function cancel_delete_schedule() {
         trs.td.innerHTML = trs.td.innerHTML;
      }
      
      function delete_schedule(schedule_id,d,e) {
         trs.td.oldHTML = trs.td.innerHTML;
         trs.td.innerHTML = '<div style=\"padding:10px;background-color:#ffcccc;text-align:center;\">'
                          + 'You are going to delete this schedule?<br/><br/>'
                          + '<input type=\"button\" value=\"Yes (delete)\" onclick=\"do_delete_schedule();\"/>&nbsp;'
                          + '<input type=\"button\" value=\"No (cancel delete)\" onclick=\"cancel_delete_schedule();\"/>'
                          + '</div>';
      }
      
      function cancel_edit_schedule() {
         _destroy(trs);
         trs.schedule_id = null;
         trs = null;
      }
      
      function save_schedule(schedule_id,d,e) {
         var ret = _parseForm('frmschedule');
         var xstart = $('start_dttm_'+schedule_id).value;
         var xstop = $('stop_dttm_'+schedule_id).value;
         trs.td.innerHTML = '';
         trs.td.appendChild(progress_span());
         ocjx_app_saveSchedule(schedule_id,ret,function(_data) {
            _destroy(trs);
            trs.schedule_id = null;
            trs = null;
            var data = recjsarray(_data);
            $('sptxtstart_'+data[0]).innerHTML = data[1];
            $('sptxtstop_'+data[0]).innerHTML = data[2];
         });
      }
      
      var trs = null;
      function edit_schedule(asid,schedule_id,d,e) {
         if(trs) {
            _destroy(trs);
         }
         if(trs&&trs.schedule_id==schedule_id) {
            trs.schedule_id = null;
            trs = null;
            return;
         }
         trs = _dce('tr');
         trs.td = trs.appendChild(_dce('td'));
         trs.td.setAttribute('colspan','3');
         trs.schedule_id = schedule_id;
         trs = $('tbodysched_'+asid).insertBefore(trs,$('trsx_'+schedule_id).nextSibling);
         trs.td.appendChild(progress_span());
         ocjx_app_editSchedule(schedule_id,function(_data) {
            trs.td.innerHTML = _data;
         });
      }
      
      /*
      function _changedatetime_callback(xid,result) {
         var iid = xid.split('_');
         var schedule_id = iid[1];
         var xstart = $('start_dttm_'+schedule_id).value;
         var xstop = $('stop_dttm_'+schedule_id).value;
         ocjx_app_saveSchedule(schedule_id,xstart,xstop,function(_data) {
         
         });
      }
      */
      
      var addnew = null;
      function add_org(asid,org_id,d,e) {
         ajax_feedback = _caf;
         dvdiv.style.display = 'none';
         $('trempty').style.display = 'none';
         ocjx_app_addSchedule(asid,org_id,function(_data) {
            var data = recjsarray(_data);
            var tr = _dce('tr');
            var td0 = tr.appendChild(_dce('td'));
            td0.innerHTML = data[1];
            var td1 = tr.appendChild(_dce('td'));
            td1.innerHTML = data[2];
            var td2 = tr.appendChild(_dce('td'));
            td2.innerHTML = data[3];
            tr = $('trempty').parentNode.insertBefore(tr,$('trempty'));
            tr.setAttribute('id','trsx_'+data[0]);
         });
      }
      
      var dvdiv = null;
      function add_schedule(asid,d,e) {
         if(!dvdiv) {
            dvdiv = _dce('div');
            dvdiv.setAttribute('style','display:none;position:absolute;min-width:200px;left:-1000px;top:0px;background-color:#fff;padding:5px;-moz-border-radius:5px;border:1px solid #bbb;-moz-box-shadow:1px 1px 2px #333;');
            dvdiv.appendChild(progress_span());
            dvdiv = d.parentNode.appendChild(dvdiv);
            ocjx_app_getDivisionList(asid,function(_data) {
               dvdiv.innerHTML = _data;
               var d = $('addbutton');
               dvdiv.style.left = parseInt(oX(d)-parseInt(dvdiv.offsetWidth)+parseInt(d.offsetWidth))+'px';
               dvdiv.style.top = parseInt(oY(d)+d.offsetHeight)+'px';
            });
         }
         if(dvdiv.style.display=='none') {
            dvdiv.style.display = '';
         } else {
            dvdiv.style.display = 'none';
         }
         dvdiv.style.left = parseInt(oX(d)-parseInt(dvdiv.offsetWidth)+parseInt(d.offsetWidth))+'px';
         dvdiv.style.top = parseInt(oY(d)+d.offsetHeight)+'px';
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

} // HRIS_ASSESSMENTSCHEDULE_DEFINED
?>