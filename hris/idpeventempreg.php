<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/idpeventempreg.php                         //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_IDPEVENTEMPLOYEEREGISTRATION_DEFINED') ) {
   define('HRIS_IDPEVENTEMPLOYEEREGISTRATION_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_IDPEventEmployeeRegistration extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_IDPEVENTEMPLOYEEREGISTRATION_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_IDPEVENTEMPLOYEEREGISTRATION_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_IDPEventEmployeeRegistration($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function browser() {
      $ret = $this->listEvent();
      return $ret;
   }
   
   function listEvent() {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_idpeventempreg.php");
      $ajax = new _hris_class_IDPEventEmployeeRegistrationAjax("ocjx");
      
      $sql = "SELECT a.event_id,a.event_title,c.institute_nm,b.method_t,a.start_dttm,a.registration_t"
           . " FROM ".XOCP_PREFIX."idp_event a"
           . " LEFT JOIN ".XOCP_PREFIX."idp_development_method b ON b.method_id = a.method_id"
           . " LEFT JOIN ".XOCP_PREFIX."idp_institutes c ON c.institute_id = a.institute_id"
           . " WHERE a.status_cd != 'nullified'"
           . " ORDER BY a.event_title";
      $result = $db->query($sql);
      $ret = "<table class='xxlist' style='width:100%;' align='center'><thead>"
           . "<tr><td>"
                  . "<table border='0' class='ilist' style='width:100%;'>"
                  . "<colgroup><col/><col width='130'/><col width='150'/><col width='70'/></colgroup>"
                  . "<tbody><tr>"
                  . "<td style=''>Event</td>"
                  . "<td style='text-align:center;'>Method</td>"
                  . "<td style='text-align:center;'>Start Date</td>"
                  . "<td style='text-align:center;'>Status</td>"
                  . "</tr></tbody></table>"
           . "</td></tr>"
           
           . "</thead><tbody id='tbproc'>";
      if($db->getRowsNum($result)>0) {
         while(list($event_id,$event_title,$institute_nm,$method_t,$start_dttm,$registration_t)=$db->fetchRow($result)) {
            $sql = "SELECT method_type FROM ".XOCP_PREFIX."idp_development_method_type WHERE method_t = '$method_t'";
            $resultx = $db->query($sql);
            list($method_type)=$db->fetchRow($resultx);
            
            
            $sql = "SELECT a.rel_id,a.competency_id,a.rcl_min,a.rcl_max,b.competency_nm,b.competency_abbr"
                 . " FROM ".XOCP_PREFIX."idp_event_competency_rel a"
                 . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
                 . " WHERE a.event_id = '$event_id'"
                 . " ORDER BY a.rel_id DESC";
            $rc = $db->query($sql);
            $info = "<div style='color:#888;padding:2px;padding-left:20px;font-size:0.9em;'>";
            if($db->getRowsNum($rc)>0) {
               while(list($rel_id,$competency_id,$rcl_min,$rcl_max,$competency_nm,$competency_abbr)=$db->fetchRow($rc)) {
                  $info .= "<div>$competency_abbr - $competency_nm [$rcl_min-$rcl_max]</div>";
               }
            }
            $info .= "<div style='font-weight:bold;'>$institute_nm</div>";
            $info .= "</div>";
            
            
            
            $ret .= "<tr><td id='tdclass_${event_id}'>"
                  . "<table border='0' class='ilist' style='width:100%;'>"
                  . "<colgroup><col/><col width='130'/><col width='150'/><col width='70'/></colgroup>"
                  . "<tbody><tr>"
                  . "<td>"
                     . "<span onclick='edit_event(\"$event_id\",this,event);' class='xlnk'>".htmlentities(stripslashes($event_title))."</span>"
                  . "</td>"
                  . "<td style='text-align:center;'>$method_type</td>"
                  . "<td style='text-align:center;'>".sql2ind($start_dttm,"date")."</td>"
                  . "<td style='text-align:center;'>$registration_t</td>"
                  . "</tr>"
                  . "<tr><td colspan='4'>$info</td></tr>"
                  . "</tbody></table>"
                  . "</td></tr>";
         
         }
         $ret .= "<tr id='trempty' style='display:none;'><td>"._EMPTY."</td></tr>";
      } else {
         $ret .= "<tr id='trempty'><td>"._EMPTY."</td></tr>";
      }
      
      $ret .= "</tbody></table><div style='margin-bottom:200px;'>&nbsp;</div>";
      
      return $ret.$ajax->getJs()."<script type='text/javascript' src='".XOCP_SERVER_SUBDIR."/include/calendar.js'></script><script type='text/javascript'><!--
      
      function import_selected(event_id,d,e) {
         var ckbx = _parseForm('dvimport');
         ocjx_app_importSelected(event_id,ckbx,function(_data) {
            if(_data=='ERROR') {
               alert('Unknown error. Please contact administrator.');
               return;
            }
            var data = recjsarray(_data);
            for(var i=0;i<data.length;i++) {
               var tr = _dce('tr');
               var td = tr.appendChild(_dce('td'));
               td.innerHTML = data[i][1];
               td.setAttribute('id','tdemp_'+wdv.event_id+'_'+data[i][0]);
               $('tbemplist').insertBefore(tr,$('tbemplist').firstChild);
            }
            importidpbox.fade();
         });
      }
      
      function select_import_all(d) {
         var dv = $('dvimport');
         var inps = dv.getElementsByTagName('input');
         for(var i=0;i<inps.length;i++) {
            inps[i].checked=true;
         }
         d.parentNode.parentNode.style.display = 'none';
      }
      
      function select_import_invert(d) {
         var dv = $('dvimport');
         var inps = dv.getElementsByTagName('input');
         for(var i=0;i<inps.length;i++) {
            if(inps[i].checked) {
               inps[i].checked=false;
            } else {
               inps[i].checked=true;
            }
         }
         d.parentNode.parentNode.style.display = 'none';
      }
      
      function select_import(d,e) {
         if(!d.dv) {
            d.dv = _dce('div');
            d.dv.setAttribute('style','display:none;background-color:#fff;padding:5px;border:1px solid #bbb;-moz-border-radius:5px;-moz-box-shadow:1px 1px 5px #888;position:absolute;top:-1000px;');
            d.dv = d.parentNode.appendChild(d.dv);
         }
         if(d.dv.style.display=='none') {
            d.dv.style.display='';
            d.dv.style.top = (oY(d)+d.offsetHeight)+'px';
            d.dv.style.left = oX(d)+'px';
            d.dv.innerHTML = '<div class=\"cb\" style=\"padding:3px;\"><span onclick=\"select_import_all(this);\" class=\"xlnk\">All</span></div>'
                           + '<div class=\"cb\" style=\"padding:3px;\"><span onclick=\"select_import_invert(this);\" class=\"xlnk\">Invert</span></div>';
         } else {
            d.dv.style.display = 'none';
         }
      }
      
      
      var importidp = null;
      var importidpbox = null;
      function import_from_request(event_id,d,e) {
         importidp = _dce('div');
         importidp.setAttribute('id','importidp');
         importidp = document.body.appendChild(importidp);
         importidp.sub = importidp.appendChild(_dce('div'));
         importidp.sub.setAttribute('id','innerimportidp');
         importidpbox = new GlassBox();
         ocjx_app_importFromIDPRequest(event_id,function(_data) {
            if(_data!='FAIL') {
               var data = recjsarray(_data);
               $('innerimportidp').innerHTML = data[0];
               if(importidpbox) {
                  _destroy(importidpbox.overlay);
               }
               importidpbox = new GlassBox();
               importidpbox.init('importidp','700px',data[1],'hidden','default',false,false);
               importidpbox.lbo(false,0.3);
               importidpbox.appear();
            }
         });
      }
      
      function delete_confirmed(event_id,d,e) {
         var dv = d.parentNode;
         var emplist = '';
         if(confirmdelete.employee_id=='A') {
            if($('emplist_h')) {
               emplist = $('emplist_h').value;
               if(emplist!='') {
                  var empx = emplist.split('|');
                  for(var i=0;i<empx.length;i++) {
                     var employee_id = empx[i];
                     if(employee_id>0) {
                        var td = $('tdemp_'+event_id+'_'+employee_id);
                        if(td) {
                           var tr = td.parentNode;
                           _destroy(tr);
                        }
                     }
                  }
               }
            }
         } else {
            var td = $('tdemp_'+event_id+'_'+confirmdelete.employee_id);
            var tr = td.parentNode;
            _destroy(tr);
            emplist = confirmdelete.employee_id;
         }
         dv.innerHTML = '';
         dv.appendChild(progress_span(' ... deleting'));
         ocjx_app_deleteRegistration(event_id,emplist,function(_data) {
            confirmdeletebox.fade();
         });
      }
      
      var confirmdelete = null;
      var confirmdeletebox = null;
      function delete_selected(event_id,employee_id,d,e) {
         var ckbx = _parseForm('tbemplist');
         confirmdelete = _dce('div');
         confirmdelete.setAttribute('id','confirmdelete');
         confirmdelete = document.body.appendChild(confirmdelete);
         confirmdelete.sub = confirmdelete.appendChild(_dce('div'));
         confirmdelete.sub.setAttribute('id','innerconfirmdelete');
         confirmdeletebox = new GlassBox();
         confirmdelete.employee_id=employee_id;
         ocjx_app_confirmDelete(event_id,employee_id,ckbx,function(_data) {
            if(_data!='FAIL') {
               var data = recjsarray(_data);
               $('innerconfirmdelete').innerHTML = data[0];
               if(confirmdeletebox) {
                  _destroy(confirmdeletebox.overlay);
               }
               confirmdeletebox = new GlassBox();
               confirmdeletebox.init('confirmdelete','500px',data[1],'hidden','default',false,false);
               confirmdeletebox.lbo(false,0.3);
               confirmdeletebox.appear();
            }
         });
      }
      
      function send_email_confirmed(event_id,d,e) {
         var emailt = _parseForm('tblemailtype');
         var dv = d.parentNode;
         var emplist = '';
         if($('emplist_h')) {
            emplist = $('emplist_h').value;
         }
         dv.innerHTML = '';
         dv.appendChild(progress_span(' ... sending e-mail notification'));
         ocjx_app_sendNotification(event_id,emplist,emailt,function(_data) {
            confirmnotifybox.fade();
         });
      }
      
      function select_emp_out(d) {
         ocjx_app_selectOut(wdv.event_id,function(_data) {
            var tb = $('tbemplist');
            var inps = tb.getElementsByTagName('input');
            for(var i=0;i<inps.length;i++) {
               inps[i].checked=false;
            }
            var data = recjsarray(_data);
            for(var i=0;i<data.length;i++) {
               if($('ckbemp_'+wdv.event_id+'_'+data[i])) {
                  var ckb = $('ckbemp_'+wdv.event_id+'_'+data[i]);
                  ckb.checked = true;
               }
            }
            d.parentNode.style.display = 'none';
         });
      }
      
      function select_emp_in(d) {
         ocjx_app_selectIn(wdv.event_id,function(_data) {
            var tb = $('tbemplist');
            var inps = tb.getElementsByTagName('input');
            for(var i=0;i<inps.length;i++) {
               inps[i].checked=false;
            }
            var data = recjsarray(_data);
            for(var i=0;i<data.length;i++) {
               if($('ckbemp_'+wdv.event_id+'_'+data[i])) {
                  var ckb = $('ckbemp_'+wdv.event_id+'_'+data[i]);
                  ckb.checked = true;
               }
            }
            d.parentNode.style.display = 'none';
         });
      }
      
      function select_emp_invited(d) {
         ocjx_app_selectInvited(wdv.event_id,function(_data) {
            var tb = $('tbemplist');
            var inps = tb.getElementsByTagName('input');
            for(var i=0;i<inps.length;i++) {
               inps[i].checked=false;
            }
            var data = recjsarray(_data);
            for(var i=0;i<data.length;i++) {
               if($('ckbemp_'+wdv.event_id+'_'+data[i])) {
                  var ckb = $('ckbemp_'+wdv.event_id+'_'+data[i]);
                  ckb.checked = true;
               }
            }
            d.parentNode.style.display = 'none';
         });
      }
      
      function select_emp_self_registered(d) {
         ocjx_app_selectSelfRegistered(wdv.event_id,function(_data) {
            var tb = $('tbemplist');
            var inps = tb.getElementsByTagName('input');
            for(var i=0;i<inps.length;i++) {
               inps[i].checked=false;
            }
            var data = recjsarray(_data);
            for(var i=0;i<data.length;i++) {
               if($('ckbemp_'+wdv.event_id+'_'+data[i])) {
                  var ckb = $('ckbemp_'+wdv.event_id+'_'+data[i]);
                  ckb.checked = true;
               }
            }
            d.parentNode.style.display = 'none';
         });
      }
      
      function select_emp_unconfirmed(d) {
         ocjx_app_selectUnconfirmed(wdv.event_id,function(_data) {
            var tb = $('tbemplist');
            var inps = tb.getElementsByTagName('input');
            for(var i=0;i<inps.length;i++) {
               inps[i].checked=false;
            }
            var data = recjsarray(_data);
            for(var i=0;i<data.length;i++) {
               if($('ckbemp_'+wdv.event_id+'_'+data[i])) {
                  var ckb = $('ckbemp_'+wdv.event_id+'_'+data[i]);
                  ckb.checked = true;
               }
            }
            d.parentNode.style.display = 'none';
         });
      }
      
      function select_emp_all(d) {
         var tb = $('tbemplist');
         var inps = tb.getElementsByTagName('input');
         for(var i=0;i<inps.length;i++) {
            inps[i].checked=true;
         }
         d.parentNode.style.display = 'none';
      }
      
      function select_emp_invert(d) {
         var tb = $('tbemplist');
         var inps = tb.getElementsByTagName('input');
         for(var i=0;i<inps.length;i++) {
            if(inps[i].checked) {
               inps[i].checked=false;
            } else {
               inps[i].checked=true;
            }
         }
         d.parentNode.style.display = 'none';
      }
      
      function select_emp(d,e) {
         d.checked=false;
         if(!d.dv) {
            d.dv = _dce('div');
            d.dv.setAttribute('style','text-align:left;display:none;background-color:#fff;padding:5px;border:1px solid #bbb;-moz-border-radius:5px;-moz-box-shadow:1px 1px 5px #888;position:absolute;top:-1000px;');
            d.dv = d.parentNode.appendChild(d.dv);
         }
         if(d.dv.style.display=='none') {
            d.dv.style.display='';
            d.dv.style.top = (oY(d)+d.offsetHeight)+'px';
            d.dv.style.left = oX(d)+'px';
            d.dv.innerHTML = '<div class=\"cb\" style=\"padding:3px;\" onclick=\"select_emp_unconfirmed(this)\" ><span class=\"xlnk\">Unconfirmed</span></div>'
                           + '<div class=\"cb\" style=\"padding:3px;\" onclick=\"select_emp_self_registered(this)\" ><span class=\"xlnk\">Self Registered</span></div>'
                           + '<div class=\"cb\" style=\"padding:3px;\" onclick=\"select_emp_invited(this)\" ><span class=\"xlnk\">Invited</span></div>'
                           + '<div class=\"cb\" style=\"padding:3px;\" onclick=\"select_emp_in(this)\" ><span class=\"xlnk\">In</span></div>'
                           + '<div class=\"cb\" style=\"padding:3px;\" onclick=\"select_emp_out(this)\" ><span class=\"xlnk\">Out</span></div>'
                           + '<div class=\"cb\" style=\"padding:3px;\" onclick=\"select_emp_invert(this);\" ><span class=\"xlnk\">Invert</span></div>'
                           + '<div class=\"cb\" style=\"padding:3px;\" onclick=\"select_emp_all(this);\" ><span class=\"xlnk\">All</span></div>';
         } else {
            d.dv.style.display = 'none';
         }
      }
      
      function delete_registration(event_id,employee_id) {
         var td = $('tdemp_'+event_id+'_'+employee_id);
         var tr = td.parentNode;
         _destroy(tr);
         ocjx_app_deleteRegistration(event_id,employee_id,null);
      }
      
      function save_draft(event_id,email_id) {
         var ret = _parseForm('emaileditor');
         ocjx_app_saveDraft(event_id,email_id,ret,null);
         emaileditfade();
      }
      
      function confirmnotifyfade() {
        if(emaileditbox) {
            _destroy(emaileditbox.overlay);
            emaileditbox.overlay = null;
            emaileditbox = null;
         }
         confirmnotifybox.fade();
      }
      
      var confirmnotify = null;
      var confirmnotifybox = null;
      function send_email_notification(event_id,employee_id,d,e) {
         var ckbx = _parseForm('tbemplist');
         confirmnotify = _dce('div');
         confirmnotify.setAttribute('id','confirmnotify');
         confirmnotify = document.body.appendChild(confirmnotify);
         confirmnotify.sub = confirmnotify.appendChild(_dce('div'));
         confirmnotify.sub.setAttribute('id','innerconfirmnotify');
         ocjx_app_confirmNotify(event_id,employee_id,ckbx,function(_data) {
            if(_data!='FAIL') {
               var data = recjsarray(_data);
               $('innerconfirmnotify').innerHTML = data[0];
               if(confirmnotifybox) {
                  _destroy(confirmnotifybox.overlay);
               }
               confirmnotifybox = new GlassBox();
               confirmnotifybox.init('confirmnotify','500px',data[1],'hidden','default',false,false);
               confirmnotifybox.lbo(false,0.3);
               confirmnotifybox.appear();
            }
         });
      }
      
      function emaileditfade() {
         _destroy($('inneremailedit'));
         if(emaileditbox) {
            _destroy(emaileditbox.overlay);
         }
         if(emailedit.sub) {
            _destroy(emailedit.sub);
         }
         if(emailedit) {
            _destroy(emailedit);
            emailedit = null;
         }
         THIS = oldTHIS;
      }
      
      var emailedit = null;
      var emaileditbox = null;
      var oldTHIS = null;
      function edit_email(event_id,email_id,d,e) {
         oldTHIS = THIS;
         emailedit = _dce('div');
         emailedit.setAttribute('id','emailedit');
         emailedit = document.body.appendChild(emailedit);
         emailedit.sub = emailedit.appendChild(_dce('div'));
         emailedit.sub.setAttribute('id','inneremailedit');
         ocjx_app_editEmail(event_id,email_id,function(_data) {
            if(_data!='FAIL') {
               var data = recjsarray(_data);
               $('inneremailedit').innerHTML = data[0];
               if(emaileditbox) {
                  _destroy(emaileditbox.overlay);
               }
               emaileditbox = new GlassBox();
               emaileditbox.init('emailedit','570px',data[1],'hidden','default',false,false);
               emaileditbox.lbo(false,0.3);
               emaileditbox.appear();
               setTimeout('$(\"msgbody\").focus();',100);
            }
         });
      }
      
      
      function back_compgroup(d,e) {
         ocjx_app_browseCompetency(0,function(_data) {
            var d = $('btn_add_competency');
            cb.sub.innerHTML = _data;
            cb.style.display = '';
            cb.style.left = parseInt(oX(d)+d.offsetWidth-cb.offsetWidth)+'px';
         });
      }
      
      function do_delete_rel(rel_id) {
         var dv = $('comprel_'+rel_id);
         _destroy(dv);
         ocjx_app_deleteCompetencyRel(wdv.event_id,rel_id,function(_data) {
            
         });
      }
      
      function cancel_delete_rel(rel_id) {
         var dv = $('comprel_'+rel_id);
         _destroy(dv.confirm);
      }
      
      function delete_comprel(rel_id) {
         var dv = $('comprel_'+rel_id);
         dv.rel_id = rel_id;
         dv.confirm = dv.appendChild(_dce('div'));
         dv.confirm.setAttribute('style','padding:10px;background-color:#ffcccc;text-align:left;');
         dv.confirm.innerHTML = 'Do you want to delete this competency upgrade relation?'
                              + '<br/><br/>'
                              + '<input type=\"button\" value=\"Yes (delete)\" onclick=\"do_delete_rel(\\''+rel_id+'\\');\"/>&nbsp;'
                              + '<input type=\"button\" value=\"No\" onclick=\"cancel_delete_rel(\\''+rel_id+'\\');\"/>&nbsp;';
      }
      
      function add_comp_select_competency(compgroup_id,competency_id,d,e) {
         d.innerHTML = '';
         d.appendChild(progress_span());
         ocjx_app_addCompetency(competency_id,wdv.event_id,function(_data) {
            var data = recjsarray(_data);
            cb.style.display = 'none';
            $('comprel_empty').style.display = 'none';
            var dv = $('complist').insertBefore(_dce('div'),$('complist').firstChild);
            dv.setAttribute('style','border-top:1px solid #ddd;padding:2px;');
            dv.setAttribute('id','comprel_'+data[1]);
            dv.innerHTML = data[0];
         });
      }
      
      function add_comp_select_group(compgroup_id,d,e) {
         d.innerHTML = '';
         d.appendChild(progress_span());
         ocjx_app_browseCompetency(compgroup_id,function(_data) {
            cb.sub.innerHTML = _data;
            var btn = $('btn_add_competency');
            cb.style.left = parseInt(oX(btn)+btn.offsetWidth-cb.offsetWidth)+'px';
         });
      }
      
      var cb = null;
      function add_browse_competency(d,e) {
         if(!cb) {
            cb = _dce('div');
            cb.setAttribute('style','position:absolute;display:none;min-width:300px;padding:5px;background-color:#fff;border:1px solid #bbb;');
            cb.sub = cb.appendChild(_dce('div'));
            cb.sub.setAttribute('style','border:0px;');
            cb.sub.appendChild(progress_span());
            cb = document.body.appendChild(cb);
         }
         if(cb.style.display=='none') {
            cb.style.top = parseInt(oY(d)+d.offsetHeight)+'px';
            cb.style.left = parseInt(oX(d)+d.offsetWidth-cb.offsetWidth)+'px';
            ocjx_app_browseCompetency(0,function(_data) {
               cb.sub.innerHTML = _data;
               cb.style.display = '';
               cb.style.left = parseInt(oX(d)+d.offsetWidth-cb.offsetWidth)+'px';
            });
         } else {
            cb.style.display = 'none';
         }
      }
      
      function do_create_event(method_id,d,e) {
         var td = d.parentNode;
         td.innerHTML = '';
         td.appendChild(progress_span());
         ocjx_app_createEvent(method_id,function(_data) {
            var data = recjsarray(_data);
            addeventbox.fade();
            var tr = _dce('tr');
            var td = tr.appendChild(_dce('td'));
            td.innerHTML = data[1];
            td.setAttribute('id','tdclass_'+data[0]);
            $('tbproc').insertBefore(tr,$('tbproc').firstChild);
            edit_event(data[0],null,null);
         });
      }
      
      var addevent = null;
      var addeventbox = null;
      function select_method(method_t,d,e) {
         if(method_t!='ALL') {
            var td = d.parentNode;
            td.innerHTML = '';
            td.appendChild(progress_span());
         }
         addevent = _dce('div');
         addevent.setAttribute('id','addevent');
         addevent = document.body.appendChild(addevent);
         addevent.sub = addevent.appendChild(_dce('div'));
         addevent.sub.setAttribute('id','inneraddevent');
         ocjx_app_selectMethod(method_t,function(_data) {
            if(_data!='FAIL') {
               var data = recjsarray(_data);
               $('inneraddevent').innerHTML = data[0];
               if(addeventbox) {
                  _destroy(addeventbox.overlay);
               }
               addeventbox = new GlassBox();
               addeventbox.init('addevent','500px',data[1],'hidden','default',false,false);
               addeventbox.lbo(false,0.3);
               addeventbox.appear();
            }
         });
      }
      
      
      var rege = null;
      function edit_reg(event_id,employee_id,d,e) {
         var td = $('tdemp_'+event_id+'_'+employee_id);
         if(rege) {
            if(rege.event_id==event_id&&rege.employee_id==employee_id) {
               _destroy(rege);
               rege.event_id = null;
               rege.employee_id = null;
               rege = null;
               return;
            }
            _destroy(rege);
         }
         rege = _dce('div');
         rege.setAttribute('style','padding:5px;');
         rege = td.appendChild(rege);
         rege.appendChild(progress_span());
         rege.event_id = event_id;
         rege.employee_id = employee_id;
         ocjx_app_editRegistration(event_id,employee_id,function(_data) {
            rege.innerHTML = _data;
         });
      }
      
      var wdv = null;
      function edit_event(event_id,d,e) {
         if(rege) {
            _destroy(rege);
            rege.event_id = null;
            rege.employee_id = null;
            rege = null;
         }
         if(wdv) {
            if(wdv.event_id != 'new' && wdv.event_id == event_id) {
               cancel_edit();
               return;
            } else {
               cancel_edit();
            }
         }
         wdv = _dce('div');
         wdv.event_id = event_id;
         if(event_id=='new') {
            var tre = $('trempty');
            var tr = _dce('tr');
            var td = tr.appendChild(_dce('td'));
            tr = tre.parentNode.insertBefore(tr,tre.parentNode.firstChild);
         } else {
            var td = $('tdclass_'+event_id);
         }
         wdv.setAttribute('style','padding:10px;');
         wdv = td.appendChild(wdv);
         wdv.appendChild(progress_span());
         wdv.td = td;
         $('trempty').style.display = 'none';
         ocjx_app_editEvent(event_id,function(_data) {
            wdv.innerHTML = _data;
            var qemp = _gel('qemp');
            qemp._get_param=function() {
               ajax_feedback = null;
               var qval = this.value;
               qval = trim(qval);
               if(qval.length < 2) {
                  return '';
               }
               return qval;
            };
            qemp._onselect=function(resId) {
               ajax_feedback = _caf;
               ocjx_app_registerEmployee(wdv.event_id,resId,0,0,function(_data) {
                  if(_data=='ERROR') {
                     alert('Unknown error. Please contact administrator.');
                     return;
                  }
                  if(_data=='DUPLICATE') {
                     alert('Employee already registered.');
                     return;
                  }
                  var data = recjsarray(_data);
                  var tr = _dce('tr');
                  var td = tr.appendChild(_dce('td'));
                  td.innerHTML = data[1];
                  td.setAttribute('id','tdemp_'+wdv.event_id+'_'+data[0]);
                  $('tbemplist').insertBefore(tr,$('tbemplist').firstChild);
               });
               $('qemp')._showResult(false);
            };
            qemp._send_query = ocjx_app_searchEmployee;
            _make_ajax(qemp);
            qemp.focus();
            
         });
      }
      
      function cancel_edit() {
         wdv.td.style.backgroundColor = '';
         if(wdv.event_id=='new') {
            _destroy(wdv.td.parentNode);
         }
         wdv.event_id = null;
         _destroy(wdv);
         wdv = null;
      }
      
      function delete_action() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '#ffcccc';
         wdv.oldHTML = wdv.innerHTML;
         wdv.innerHTML = 'Are you sure you want to delete this method?<br/><br/>'
                       + '<input type=\"button\" value=\""._CANCEL."\" onclick=\"cancel_delete();\"/>&nbsp;&nbsp;'
                       + '<input type=\"button\" value=\""._DELETE."\" onclick=\"do_delete();\"/>';
      }
      
      function cancel_delete() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '';
         wdv.innerHTML = wdv.oldHTML;
      }
      
      function do_delete() {
         ocjx_app_Delete(wdv.event_id,null);
         var tr = wdv.parentNode.parentNode;
         _destroy(tr);
         wdv.event_id = null;
         wdv = null;
      }
      
      function save_event() {
         var ret = _parseForm('frm');
         var event_id = wdv.event_id;
         $('progressm').innerHTML = '';
         $('progressm').appendChild(progress_span());
         ocjx_app_saveEvent(event_id,ret,function(_data) {
            var data = recjsarray(_data);
            var td = wdv.td;
            wdv.event_id = null;
            _destroy(wdv);
            wdv = _dce('div');
            wdv.event_id = data[0];
            td.setAttribute('id',data[1]);
            td.innerHTML = data[3];
            wdv.td = td;
            
            wdv.setAttribute('style','padding:10px;');
            wdv = td.appendChild(wdv);
            wdv.td = td;
            wdv.innerHTML = data[2];
            $('inp_event_title').focus();
         });
      }
      
      // --></script>";
   }
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            if(isset($_GET["smethod"])&&$_GET["smethod"]==1&&isset($_GET["t"])&&$_GET["t"]!="") {
               $_SESSION["hris_method_t"] = $_GET["t"];
               $ret = $this->browser();
            } else if(isset($_GET["backlist"])&&$_GET["backlist"]==1) {
               unset($_SESSION["hris_method_t"]);
               $ret = $this->browser();
            } else {
               $ret = $this->browser();
            }
            break;
         default:
            $ret = $this->browser();
            break;
      }
      return $ret."<div style='height:100px;'>&nbsp;</div>";
   }
}

} // HRIS_IDPEVENTEMPLOYEEREGISTRATION_DEFINED
?>
