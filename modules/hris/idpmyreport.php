<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/idpmyreport.php                            //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_IDPMYREPORT_DEFINED') ) {
   define('HRIS_IDPMYREPORT_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/idp/idp.php");

class _hris_IDPMyReport extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_IDPMYREPORT_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_IDPMYREPORT_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_IDPMyReport($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function browser() {
      $ret = $this->listEvent();
      return $ret;
   }
   
   function reporting($request_id,$actionplan_id,$preview=0) {
      $db=&Database::getInstance();
      $_SESSION["html"]->js_tinymce = TRUE;
      $user_id = getUserID();
      
      //// hide follow notification
      $sql = "SELECT notification_id"
           . " FROM ".XOCP_PREFIX."idp_notifications"
           . " WHERE request_id = '$request_id'"
           . " AND message_id = '_IDP_YOURREPORTHASBEENCOMPLETED'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($notification_id)=$db->fetchRow($result)) {
            _idp_notification_followed($notification_id);
         }
      }
      
      list($self_job_id,
           $self_employee_id,
           $self_job_nm,
           $self_nm,
           $self_nip,
           $self_gender,
           $self_jobstart,
           $self_entrance_dttm,
           $self_jobage,
           $self_job_summary,
           $self_person_id,
           $self_user_id,
           $self_first_assessor_job_id,
           $self_next_assessor_job_id)=_hris_getinfobyuserid($user_id);
      
      $sql = "SELECT employee_id FROM ".XOCP_PREFIX."idp_request WHERE request_id = '$request_id'";
      $result = $db->query($sql);
      list($employee_id)=$db->fetchRow($result);
      if($self_employee_id!=$employee_id) {
         return "Invalid user.";
      }
      
      $sql = "SELECT a.project_id,a.event_id,a.method_t,b.method_type,a.method_id,c.method_nm,a.plan_start_dttm,a.plan_stop_dttm,a.competency_id FROM ".XOCP_PREFIX."idp_request_actionplan a"
           . " LEFT JOIN ".XOCP_PREFIX."idp_development_method_type b USING(method_t)"
           . " LEFT JOIN ".XOCP_PREFIX."idp_development_method c ON c.method_id = a.method_id"
           . " WHERE a.request_id = '$request_id' AND a.actionplan_id = '$actionplan_id'";
      $rap = $db->query($sql);
      if($db->getRowsNum($rap)==1) {
         list($project_id,$event_id,$method_t,$method_type,$method_id,$method_nm,$plan_start_dttm,$plan_stop_dttm,$competency_id)=$db->fetchRow($rap);
         
         $actionplan_remark = "";
         $form = "";
         if($method_t!="") {
            $editor_file = XOCP_DOC_ROOT."/modules/hris/include/idp/method_${method_t}.php";
            if(file_exists($editor_file)) {
               require_once($editor_file);
               $fremark = "${method_t}_idp_m_getRemark";
               list($actionplan_remark,$actionplan_start,$actionplan_stop) = $fremark($request_id,$actionplan_id);
               $freport = "${method_t}_idp_m_getReportingForm";
               if($method_t=="PROJECT") {
                  $form = $freport($request_id,$project_id,$preview);
               } else {
                  $form = $freport($request_id,$actionplan_id,$preview);
               }
               $ret = "<div style='border:1px solid #bbb;background-color:#ddd;padding:3px;margin-bottom:10px;text-align:right;'>"
                    . "[<a href='".XOCP_SERVER_SUBDIR."/index.php'/>back</a>]"
                    . "</div><div style='border:0px solid #ddd;border-top:0;padding:3px;padding-top:10px;'>$form</div>";
            }
         }
      } 
      
      return $ret;
   }
   
   function reporting_project($request_id,$project_id,$preview=0) {
      $db=&Database::getInstance();
      $_SESSION["html"]->js_tinymce = TRUE;
      $user_id = getUserID();
      
      //// hide follow notification
      $sql = "SELECT notification_id"
           . " FROM ".XOCP_PREFIX."idp_notifications"
           . " WHERE request_id = '$request_id'"
           . " AND message_id = '_IDP_YOURREPORTHASBEENCOMPLETED'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($notification_id)=$db->fetchRow($result)) {
            _idp_notification_followed($notification_id);
         }
      }
      
      list($self_job_id,
           $self_employee_id,
           $self_job_nm,
           $self_nm,
           $self_nip,
           $self_gender,
           $self_jobstart,
           $self_entrance_dttm,
           $self_jobage,
           $self_job_summary,
           $self_person_id,
           $self_user_id,
           $self_first_assessor_job_id,
           $self_next_assessor_job_id)=_hris_getinfobyuserid($user_id);
      
      $sql = "SELECT employee_id FROM ".XOCP_PREFIX."idp_request WHERE request_id = '$request_id'";
      $result = $db->query($sql);
      list($employee_id)=$db->fetchRow($result);
      if($self_employee_id!=$employee_id) {
         return "Invalid user.";
      }
      
      $actionplan_remark = "";
      $form = "";
      $method_t = "PROJECT";
      if($method_t!="") {
         $editor_file = XOCP_DOC_ROOT."/modules/hris/include/idp/method_${method_t}.php";
         if(file_exists($editor_file)) {
            require_once($editor_file);
            $freport = "${method_t}_idp_m_getReportingForm";
            $form = $freport($request_id,$project_id,$preview);
            $ret = "<div style='border:1px solid #bbb;background-color:#ddd;padding:3px;margin-bottom:10px;text-align:right;'>"
                 . "[<a href='".XOCP_SERVER_SUBDIR."/index.php'/>back</a>]"
                 . "</div><div style='border:0px solid #ddd;border-top:0;padding:3px;padding-top:10px;'>$form</div>";
         }
      } 
      
      return $ret;
   }
   
   function reporting_event($event_id,$employee_id) {
      $db=&Database::getInstance();
      $_SESSION["html"]->js_tinymce = TRUE;
      $user_id = getUserID();
      
      $sql = "SELECT a.event_id,a.method_t,b.method_type,a.method_id,c.method_nm,a.plan_start_dttm,a.plan_stop_dttm,a.competency_id FROM ".XOCP_PREFIX."idp_request_actionplan a"
           . " LEFT JOIN ".XOCP_PREFIX."idp_development_method_type b USING(method_t)"
           . " LEFT JOIN ".XOCP_PREFIX."idp_development_method c ON c.method_id = a.method_id"
           . " WHERE a.request_id = '$request_id' AND a.actionplan_id = '$actionplan_id'";
      $rap = $db->query($sql);
      if($db->getRowsNum($rap)==1) {
         list($event_id,$method_t,$method_type,$method_id,$method_nm,$plan_start_dttm,$plan_stop_dttm,$competency_id)=$db->fetchRow($rap);
         
         $actionplan_remark = "";
         $form = "";
         if($method_t!="") {
            $editor_file = XOCP_DOC_ROOT."/modules/hris/include/idp/method_${method_t}.php";
            if(file_exists($editor_file)) {
               require_once($editor_file);
               $freport = "${method_t}_idp_m_getReportingEventForm";
               $form = $freport($event_id,$employee_id);
               $ret = "<div style='border:1px solid #bbb;background-color:#ddd;padding:3px;margin-bottom:10px;'>"
                    . "<a href='".XOCP_SERVER_SUBDIR."/index.php'/><img src='".XOCP_SERVER_SUBDIR."/images/return.gif'/></a>"
                    . "</div>$form";
            }
         }
      } 
      return $ret;
   }
   
   function listEvent() {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_idpmyreport.php");
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $ajax = new _hris_class_IDPMyReportAjax("ocjx");
      
      $user_id = getUserID();
      list($self_job_id,
           $self_employee_id,
           $self_job_nm,
           $self_nm,
           $self_nip,
           $self_gender,
           $self_jobstart,
           $self_entrance_dttm,
           $self_jobage,
           $self_job_summary,
           $self_person_id,
           $self_user_id,
           $self_first_assessor_job_id,
           $self_next_assessor_job_id)=_hris_getinfobyuserid($user_id);
      
      
      $sql = "SELECT b.request_id"
           . " FROM ".XOCP_PREFIX."idp_request b"
           . " WHERE b.employee_id = '$self_employee_id'"
           . " AND b.status_cd IN ('implementation','completed')"
           . " ORDER BY b.request_id";
      $result = $db->query($sql);
      $action_txt = "";
      if($db->getRowsNum($result)>0) {
         $no = 0;
         while(list($request_id)=$db->fetchRow($result)) {
            $sql = "SELECT a.status_cd,a.actionplan_id,a.event_id,a.method_t,b.method_type,a.method_id,c.method_nm,a.plan_start_dttm,a.plan_stop_dttm,a.competency_id FROM ".XOCP_PREFIX."idp_request_actionplan a"
                 . " LEFT JOIN ".XOCP_PREFIX."idp_development_method_type b USING(method_t)"
                 . " LEFT JOIN ".XOCP_PREFIX."idp_development_method c ON c.method_id = a.method_id"
                 . " WHERE a.request_id = '$request_id' AND a.method_t != 'PROJECT'"
                 . " ORDER BY a.method_t";
            $rap = $db->query($sql);
            if($db->getRowsNum($rap)>0) {
               while(list($status_cd,$actionplan_id,$event_id,$method_t,$method_type,$method_id,$method_nm,$plan_start_dttm,$plan_stop_dttm,$competency_id)=$db->fetchRow($rap)) {
                  $report_status_txt = "-";
                  $actionplan_remark = "";
                  if($method_t!="") {
                     $editor_file = XOCP_DOC_ROOT."/modules/hris/include/idp/method_${method_t}.php";
                     if(file_exists($editor_file)) {
                        require_once($editor_file);
                        $fremark = "${method_t}_idp_m_getRemark";
                        list($actionplan_remark,$actionplan_start,$actionplan_stop,$report_status) = $fremark($request_id,$actionplan_id);
                        
                        $replen = 0;
                        
                        _debuglog($method_t);
                        
                        switch($method_t) {
                           case "SELF":
                              $sql = "SELECT chapter_txt FROM ".XOCP_PREFIX."idp_report_SELF_1_chapters"
                                   . " WHERE request_id = '$request_id' AND actionplan_id = '$actionplan_id'";
                              $rck = $db->query($sql);
                              if($db->getRowsNum($rck)>0) {
                                 while(list($chapter_txt)=$db->fetchRow($rck)) {
                                    $replen += strlen(trim($chapter_txt));
                                 }
                              }
                              break;
                           case "TRN_EX":
                              $sql = "SELECT conclusion,my_advantage,company_advantage FROM ".XOCP_PREFIX."idp_report_TRN_EX"
                                   . " WHERE request_id = '$request_id' AND actionplan_id = '$actionplan_id'";
                              $rck = $db->query($sql);
                              if($db->getRowsNum($rck)>0) {
                                 while(list($conclusion,$my_advantage,$company_advantage)=$db->fetchRow($rck)) {
                                    $replen += strlen(trim($conclusion));
                                    $replen += strlen(trim($my_advantage));
                                    $replen += strlen(trim($company_advantage));
                                 }
                              }
                              break;
                           default:
                              break;
                        }
                        
                        $report_status_txt = "-";
                        if($report_status!="") {
                           switch($report_status) {
                              case "prepared":
                                 if($replen>0) {
                                    $report_status_txt = "Preparation";
                                 } else {
                                    $report_status_txt = "-";
                                 }
                                 break;
                              case "approval1":
                                 $report_status_txt = "Waiting for Superior Approval";
                                 break;
                              case "approval2":
                                 $report_status_txt = "Waiting for Next Superior Approval";
                                 break;
                              case "completed":
                                 $report_status_txt = "Completed";
                                 break;
                              default:
                                 $report_status_txt = "-";
                                 break;
                           }
                        }
                     }
                  }
                  
                  $sql = "SELECT competency_nm FROM ".XOCP_PREFIX."competency WHERE competency_id = '$competency_id'";
                  $rc = $db->query($sql);
                  list($competency_nm)=$db->fetchRow($rc);
                  
                  if($event_id>0) {
                     $sql = "SELECT event_title FROM ".XOCP_PREFIX."idp_event WHERE event_id = '$event_id'";
                     $rc = $db->query($sql);
                     list($event_title)=$db->fetchRow($rc);
                  } else {
                     $event_title = "";
                  }
                  
                  if($event_title!="") {
                     $actionplan_remark = $event_title;
                  }
                  
                  $action_txt .= "<div style='padding:3px;border-bottom:1px solid #bbb;'>"
                               . "<table class='ilist' style='border-spacing:0px;width:100%;'><colgroup>"
                               . "<col width='198'/>"
                               . "<col/>"
                               . "<col width='100'/>"
                               . "<col width='130'/>"
                               . "</colgroup>"
                               . "<tbody>"
                               . "<tr>"
                               . "<td>$method_type</td>"
                               . "<td><a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&reporting=y&request_id=${request_id}&actionplan_id=${actionplan_id}'>$actionplan_remark</a></td>"
                               . "<td style='text-align:left;'>".sql2ind($actionplan_stop,"date")."</td>"
                               . "<td style='text-align:right;'>$report_status_txt</td>"
                               . "</tr>"
                               . "</tbody>"
                               . "</table>"
                               . "</div>";
               }
            }
            
            /// project
            $sql = "SELECT a.report_status_cd,a.project_id,a.project_nm,a.due_dttm FROM ".XOCP_PREFIX."idp_project a"
                 . " WHERE a.request_id = '$request_id'"
                 . " ORDER BY priority_no";
            $rap = $db->query($sql);
            _debuglog($sql);
            if($db->getRowsNum($rap)>0) {
               $method_t = "PROJECT";
               while(list($report_status,$project_id,$project_nm,$due_dttm)=$db->fetchRow($rap)) {
                  /// get time frame
                  //// get time frame
                  $sql = "SELECT MIN(activity_start_dttm) FROM ".XOCP_PREFIX."idp_project_activities WHERE request_id = '$request_id' AND project_id = '$project_id' AND status_cd IN ('normal','finish')";
                  $rmin = $db->query($sql);
                  if($db->getRowsNum($rmin)>0) {
                     list($minstart0)=$db->fetchRow($rmin);
                  }
                  $sql = "SELECT MAX(activity_stop_dttm) FROM ".XOCP_PREFIX."idp_project_activities WHERE request_id = '$request_id' AND project_id = '$project_id' AND status_cd IN ('normal','finish')";
                  $rmin = $db->query($sql);
                  if($db->getRowsNum($rmin)>0) {
                     list($maxstop0)=$db->fetchRow($rmin);
                  }
                  
                  $sql = "UPDATE ".XOCP_PREFIX."idp_project SET start_dttm = '$minstart0', due_dttm = '$maxstop0' WHERE request_id = '$request_id' AND project_id = '$project_id'";
                  $db->query($sql);
                  
                  $due_dttm = $maxstop0;
                  
                  $report_status_txt = "-";
                  $actionplan_remark = "";
                  $report_status_txt = "-";
                  
                  $replen = 0;
                  $sql = "SELECT experience FROM ".XOCP_PREFIX."idp_project_activities WHERE request_id = '$request_id' AND project_id = '$project_id'";
                  $rck = $db->query($sql);
                  if($db->getRowsNum($rck)>0) {
                     while(list($experience)=$db->fetchRow($rck)) {
                        $replen += strlen(trim($experience));
                     }
                  }
                  
                  if($report_status!="") {
                     switch($report_status) {
                        case "prepared":
                           if($replen>0) {
                              $report_status_txt = "Preparation";
                           } else {
                              $report_status_txt = "-";
                           }
                           break;
                        case "approval1":
                           $report_status_txt = "Waiting for Superior Approval";
                           break;
                        case "approval2":
                           $report_status_txt = "Waiting for Next Superior Approval";
                           break;
                        case "completed":
                           $report_status_txt = "Completed";
                           break;
                        default:
                           $report_status_txt = "-";
                           break;
                     }
                  }
                  
                  $action_txt .= "<div style='padding:3px;border-bottom:1px solid #bbb;'>"
                               . "<table class='ilist' style='border-spacing:0px;width:100%;'><colgroup>"
                               . "<col width='198'/>"
                               . "<col/>"
                               . "<col width='100'/>"
                               . "<col width='130'/>"
                               . "</colgroup>"
                               . "<tbody>"
                               . "<tr>"
                               . "<td>Project Assignment</td>"
                               . "<td><a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&reporting_project=y&request_id=${request_id}&project_id=${project_id}'>$project_nm</a></td>"
                               . "<td style='text-align:left;'>".sql2ind($due_dttm,"date")."</td>"
                               . "<td style='text-align:right;'>$report_status_txt</td>"
                               . "</tr>"
                               . "</tbody>"
                               . "</table>"
                               . "</div>";
               }
            }
            
            
            
         }
      }
      
      
      $ret = "<table class='xxlist' style='width:100%;' align='center'><thead><tr><td>"
           
                            . "<div style='padding:3px;border-bottom:0px solid #bbb;padding-top:0px;padding-bottom:0px;'>"
                            . "<table class='ilist' style='border-spacing:0px;width:100%;'><colgroup>"
                            . "<col width='198'/>"
                            . "<col/>"
                            . "<col width='100'/>"
                            . "<col width='130'/>"
                            . "</colgroup>"
                            . "<tbody>"
                            . "<tr>"
                            . "<td>Method</td>"
                            . "<td>Action Plan</td>"
                            . "<td style='text-align:left;'>Due Date</td>"
                            . "<td style='text-align:right;'>Status</td>"
                            . "</tr>"
                            . "</tbody>"
                            . "</table>"
                            . "</div>"
           
           
           . "</td></tr></thead><tbody id='tbproc'>";
         
            $ret .= "<tr><td id='tdclass_${event_id}'>"
                  . $action_txt
                  . "</td></tr>";
         
      
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
         var dv = d.parentNode;
         var emplist = '';
         if($('emplist_h')) {
            emplist = $('emplist_h').value;
         }
         dv.innerHTML = '';
         dv.appendChild(progress_span(' ... sending e-mail notification'));
         ocjx_app_sendNotification(event_id,emplist,function(_data) {
            confirmnotifybox.fade();
         });
      }
      
      function select_emp_unnotified(d) {
         ocjx_app_selectUnnotified(wdv.event_id,function(_data) {
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
            d.parentNode.parentNode.style.display = 'none';
         });
      }
      
      function select_emp_all(d) {
         var tb = $('tbemplist');
         var inps = tb.getElementsByTagName('input');
         for(var i=0;i<inps.length;i++) {
            inps[i].checked=true;
         }
         d.parentNode.parentNode.style.display = 'none';
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
         d.parentNode.parentNode.style.display = 'none';
      }
      
      function select_emp(d,e) {
         if(!d.dv) {
            d.dv = _dce('div');
            d.dv.setAttribute('style','display:none;background-color:#fff;padding:5px;border:1px solid #bbb;-moz-border-radius:5px;-moz-box-shadow:1px 1px 5px #888;position:absolute;top:-1000px;');
            d.dv = d.parentNode.appendChild(d.dv);
         }
         if(d.dv.style.display=='none') {
            d.dv.style.display='';
            d.dv.style.top = (oY(d)+d.offsetHeight)+'px';
            d.dv.style.left = oX(d)+'px';
            d.dv.innerHTML = '<div class=\"cb\" style=\"padding:3px;\"><span onclick=\"select_emp_all(this);\" class=\"xlnk\">All</span></div>'
                           + '<div class=\"cb\" style=\"padding:3px;\"><span onclick=\"select_emp_unnotified(this)\" class=\"xlnk\">Unnotified Only</span></div>'
                           + '<div class=\"cb\" style=\"padding:3px;\"><span onclick=\"select_emp_invert(this);\" class=\"xlnk\">Invert</span></div>';
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
      
      var confirmnotify = null;
      var confirmnotifybox = null;
      function send_email_notification(event_id,employee_id,d,e) {
         var ckbx = _parseForm('tbemplist');
         confirmnotify = _dce('div');
         confirmnotify.setAttribute('id','confirmnotify');
         confirmnotify = document.body.appendChild(confirmnotify);
         confirmnotify.sub = confirmnotify.appendChild(_dce('div'));
         confirmnotify.sub.setAttribute('id','innerconfirmnotify');
         confirmnotifybox = new GlassBox();
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
            } else if(isset($_GET["reporting"])&&$_GET["reporting"]=="y") {
               $request_id = $_GET["request_id"];
               $actionplan_id = $_GET["actionplan_id"];
               $preview = $_GET["preview"]+0;
               $ret = $this->reporting($request_id,$actionplan_id,$preview);
            } else if(isset($_GET["reporting_project"])&&$_GET["reporting_project"]=="y") {
               $request_id = $_GET["request_id"];
               $project_id = $_GET["project_id"];
               $preview = $_GET["preview"]+0;
               $ret = $this->reporting_project($request_id,$project_id,$preview);
            } else if(isset($_GET["reportingevent"])&&$_GET["reportingevent"]=="y") {
               $event_id = $_GET["event_id"];
               $employee_id = $_GET["employee_id"];
               $ret = $this->reporting_event($event_id,$employee_id);
            } else if(isset($_GET["backlist"])&&$_GET["backlist"]==1) {
               unset($_SESSION["hris_method_t"]);
               $ret = $this->browser();
            } else {
               $ret = $this->browser();
            }
            break;
         default:
            if(isset($_GET["reporting"])&&$_GET["reporting"]=="y") {
               $request_id = $_GET["request_id"];
               $actionplan_id = $_GET["actionplan_id"];
               $preview = $_GET["preview"]+0;
               $ret = $this->reporting($request_id,$actionplan_id,$preview);
            } else if(isset($_GET["reporting_project"])&&$_GET["reporting_project"]=="y") {
               $request_id = $_GET["request_id"];
               $project_id = $_GET["project_id"];
               $preview = $_GET["preview"]+0;
               $ret = $this->reporting_project($request_id,$project_id,$preview);
            } else if(isset($_GET["reportingevent"])&&$_GET["reportingevent"]=="y") {
               $event_id = $_GET["event_id"];
               $employee_id = $_GET["employee_id"];
               $ret = $this->reporting_event($event_id,$employee_id);
            } else {
               $ret = $this->browser();
            }
            break;
      }
      return $ret."<div style='height:100px;'>&nbsp;</div>";
   }
}

} // HRIS_IDPMYREPORT_DEFINED
?>
