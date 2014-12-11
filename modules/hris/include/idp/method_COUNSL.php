<?php

function COUNSL_idp_m_getReportingEventForm($event_id,$employee_id) {
   require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
   $db=&Database::getInstance();
   $sql = "SELECT a.event_id,a.method_t,b.method_type,a.method_id,c.method_nm,a.plan_start_dttm,a.plan_stop_dttm,a.competency_id FROM ".XOCP_PREFIX."idp_request_actionplan a"
        . " LEFT JOIN ".XOCP_PREFIX."idp_development_method_type b USING(method_t)"
        . " LEFT JOIN ".XOCP_PREFIX."idp_development_method c ON c.method_id = a.method_id"
        . " WHERE a.request_id = '$request_id' AND a.actionplan_id = '$actionplan_id'";
   $rap = $db->query($sql);
   if($db->getRowsNum($rap)==1) {
      list($event_id,$method_t,$method_type,$method_id,$method_nm,$plan_start_dttm,$plan_stop_dttm,$competency_id)=$db->fetchRow($rap);
   }
}



function COUNSL_idp_m_getReportingForm($request_id,$actionplan_id) {
   require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
   require_once(XOCP_DOC_ROOT."/modules/hris/include/idp/ajax/ajax_method_COUNSL.php");
   $ajax = new _idp_class_method_COUNSL_ajax("mCOUNSL");
   $db=&Database::getInstance();
   $user_id = getUserID();
   
   $sql = "SELECT a.employee_id"
        . " FROM ".XOCP_PREFIX."idp_request a"
        . " WHERE a.request_id = '$request_id'";
   $result = $db->query($sql);
   list($employee_id)=$db->fetchRow($result);
   
   list($self_job_id,
        $self_employee_idx,
        $self_job_nm,
        $self_nm,
        $self_nip,
        $self_gender,
        $self_jobstart,
        $self_entrance_dttm,
        $self_jobage,
        $self_job_summary,
        $self_person_id,
        $self_user_idx,
        $self_first_assessor_job_id,
        $self_next_assessor_job_id)=_hris_getinfobyuserid($user_id);
   
   list($job_id,
        $employee_idx,
        $job_nm,
        $nm,
        $nip,
        $gender,
        $jobstart,
        $entrance_dttm,
        $jobage,
        $job_summary,
        $person_id,
        $user_idx,
        $first_assessor_job_id,
        $next_assessor_job_id)=_hris_getinfobyemployeeid($employee_id);
   
   $sql = "SELECT c.job_nm,c.job_abbr,d.org_nm,d.org_abbr,a.employee_ext_id,e.person_nm,e.person_id"
        . " FROM ".XOCP_PREFIX."employee a"
        . " LEFT JOIN ".XOCP_PREFIX."employee_job b ON b.employee_id = a.employee_id AND b.job_id = '$job_id'"
        . " LEFT JOIN ".XOCP_PREFIX."jobs c ON c.job_id = '$job_id'"
        . " LEFT JOIN ".XOCP_PREFIX."orgs d ON d.org_id = c.org_id"
        . " LEFT JOIN ".XOCP_PREFIX."persons e ON e.person_id = a.person_id"
        . " WHERE a.employee_id = '$employee_id'";
   $result = $db->query($sql);
   list($job_nm,$job_abbr,$org_nm,$org_abbr,$nip,$employee_nm,$person_id)=$db->fetchRow($result);
      
   $sql = "SELECT a.method_subject,a.method_t,b.method_type,a.method_id,c.method_nm,a.plan_start_dttm,a.plan_stop_dttm,a.competency_id"
        . " FROM ".XOCP_PREFIX."idp_request_actionplan a"
        . " LEFT JOIN ".XOCP_PREFIX."idp_development_method_type b USING(method_t)"
        . " LEFT JOIN ".XOCP_PREFIX."idp_development_method c ON c.method_id = a.method_id"
        . " WHERE a.request_id = '$request_id' AND a.actionplan_id = '$actionplan_id'";
   $rap = $db->query($sql);
   if($db->getRowsNum($rap)==1) {
      list($method_subject,$method_t,$method_type,$method_id,$method_nm,$plan_start_dttm,$plan_stop_dttm,$competency_id)=$db->fetchRow($rap);
   }
   
   $sql = "SELECT subject_txt,r_01,r_02,r_03,r_04,r_05,r_06,r_07,"
        . "remark_r_01,remark_r_02,remark_r_03,remark_r_04,remark_r_05,remark_r_06,remark_r_07,submit_dttm,"
        . "approval1_job_id,approval2_job_id,"
        . "approval1_dttm,approval2_dttm,"
        . "approval1_emp_id,approval2_emp_id,status_cd,"
        . "approval1_note,approval2_note"
        . " FROM ".XOCP_PREFIX."idp_report_COUNSL"
        . " WHERE request_id = '$request_id'"
        . " AND actionplan_id = '$actionplan_id'";
   $result = $db->query($sql);
   if($db->getRowsNum($result)==1) {
      list($method_subject,$r_01,$r_02,$r_03,$r_04,$r_05,$r_06,$r_07,
           $remark_r_01,$remark_r_02,$remark_r_03,$remark_r_04,$remark_r_05,$remark_r_06,$remark_r_07,
           $submit_dttm,$approval1_job_id,$approval2_job_id,$approval1_dttm,$approval2_dttm,
           $approval1_emp_id,$approval2_emp_id,$status_cd,$approval1_note,$approval2_note)=$db->fetchRow($result);
      
      $sql = "SELECT a.job_nm,b.employee_ext_id,c.person_nm,b.employee_id"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job e USING(job_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " WHERE a.job_id = '$approval1_job_id'"
           . " ORDER BY e.start_dttm DESC LIMIT 1";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($approval1_job_nm,$nip,$approval1_person_nm,$approval1_emp_id)=$db->fetchRow($result);
      }
      
      $sql = "SELECT a.job_nm,b.employee_ext_id,c.person_nm,b.employee_id"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job e USING(job_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " WHERE a.job_id = '$approval2_job_id'"
           . " ORDER BY e.start_dttm DESC LIMIT 1";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($approval2_job_nm,$approval2_nip,$approval2_person_nm,$approval2_emp_id)=$db->fetchRow($result);
      }
   
   } else {
      
      $approval1_job_id = $first_assessor_job_id;
      $approval2_job_id = $next_assessor_job_id;
      
      $sql = "SELECT a.job_nm,b.employee_ext_id,c.person_nm,b.employee_id"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job e USING(job_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " WHERE a.job_id = '$approval1_job_id'"
           . " ORDER BY e.start_dttm DESC LIMIT 1";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($approval1_job_nm,$nip,$approval1_person_nm,$approval1_emp_id)=$db->fetchRow($result);
      }
      
      $sql = "SELECT a.job_nm,b.employee_ext_id,c.person_nm,b.employee_id"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job e USING(job_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " WHERE a.job_id = '$approval2_job_id'"
           . " ORDER BY e.start_dttm DESC LIMIT 1";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($approval2_job_nm,$approval2_nip,$approval2_person_nm,$approval2_emp_id)=$db->fetchRow($result);
      }
      
      $sql = "INSERT INTO ".XOCP_PREFIX."idp_report_COUNSL (employee_id,actionplan_id,request_id,subject_txt,approval1_job_id,approval1_emp_id,approval2_job_id,approval2_emp_id)"
           . " VALUES ('$employee_id','$actionplan_id','$request_id','$method_subject','$approval1_job_id','$approval1_emp_id','$approval2_job_id','$approval2_emp_id')";
      $db->query($sql);
      $status_cd = "prepared";
      
   }
   
   ////////////////////////////
   
   $sql = "SELECT subject_id,subject_txt,read_only"
        . " FROM ".XOCP_PREFIX."idp_report_COUNSL_subject"
        . " WHERE request_id = '$request_id'"
        . " AND actionplan_id = '$actionplan_id'"
        . " ORDER BY subject_id";
   $result = $db->query($sql);
   $subjects = "";
   $idchap = "";
   if($db->getRowsNum($result)>0) {
      $display_subject = "display:none;";
      while(list($subject_id,$subject_txt,$read_only)=$db->fetchRow($result)) {
         if($status_cd=="prepared"&&$read_only==0) {
            //$idchap .= ",textareasubject_${subject_id}";
            $subjects .= "<tr id='trsubject_${subject_id}'><td style='padding:20px;'>"
                       . $ajax->renderSubjectEdit($request_id,$actionplan_id,$subject_id)
                       . "</td></tr>";
         } else {
            $subjects .= "<tr id='trsubject_${subject_id}'><td style='padding:20px;'>"
                       . $ajax->renderSubjectReadOnly($request_id,$actionplan_id,$subject_id)
                       . "</td></tr>";
         }
      }
      $idchap = substr($idchap,1);
   } else {
      $display_subject = "";
   }
   
   
   ////////////////////////
   
   $form = "<div style='text-align:center;font-size:1.2em;font-weight:bold;'>COUNSELING REPORT</div>"
         
         . "<div style='margin-bottom:10px;margin-top:10px;padding:5px;border:1px solid #bbb;-moz-border-radius:5px;'>"
            . "<table style='width:100%;'><tbody>"
            . "<tr><td style='text-align:right;font-weight:bold;'>Subject : </td><td>$method_subject</td><td style='text-align:right;font-weight:bold;'>Time frame : </td><td>".sql2ind($plan_start_dttm,"date")." - ".sql2ind($plan_stop_dttm,"date")."</td></tr>"
            . "<tr><td style='text-align:right;font-weight:bold;'>Name : </td><td>$employee_nm</td><td style='text-align:right;font-weight:bold;'>NIP : </td><td>$nip</td></tr>"
            . "<tr><td style='text-align:right;font-weight:bold;'>Job Title : </td><td>$job_nm ($job_abbr)</td><td style='text-align:right;font-weight:bold;'>Section/Division : </td><td>$org_nm ($org_abbr)</td></tr>"
            . "</tbody></table>"
         . "</div>"
         
         . "<div style='padding:10px;'>"
            . "<div style='font-weight:bold;'>A. Counseling Summary:</div>"
            . "<div style='padding:10px;padding-left:15px;' id='dvsummary'>"
               . "<table class='xxlist' style='width:100%;'><colgroup><col/></colgroup>"
               . "<thead>"
               . ($preview==0&&$status_cd=="prepared"?"<tr><td colspan='2' style='text-align:right;'><span id='subjectprogress'></span>&nbsp;<input type='button' value='Add Subject' onclick='new_subject(this,event);'/></td></tr>":"")
               //. "<tr><td style='text-align:left;'>Summary</td></tr>"
               . "</thead>"
               . "<tbody id='tbodysubject'>"
               . $subjects
               . "<tr id='trempty_subject' style='$display_subject'><td style='text-align:center;font-style:italic;'>"._EMPTY."</td></tr>"
               . "</tbody>"
               . "</table>"
            . "</div>"
         . "</div>";
         
         ///. ($status_cd=="prepared"?"<div style='padding:5px;text-align:right;background-color:#eee;border:1px solid #bbb;'><span id='progress'></span>&nbsp;<input type='button' value='Save Draft' onclick='save_report(this,event);'/></div>":"");
         
   $form .= "<div style='text-align:right;padding:10px;margin-bottom:50px;'>"
          . "<table align='center' style='border-top:2px solid #777;border-left:2px solid #777;border-spacing:0px;'><colgroup><col width='200'/><col width='200'/>".($doubleapproval==1?"<col width='200'/>":"")."</colgroup>"
          . "<tbody>"
            . "<tr>"
            . "<td style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;'>"
            . "Submited by,"
            . "</td>"
            . "<td ".($doubleapproval==1?"colspan='2'":"")." style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;'>"
            . "Approved by,"
            . "</td>"
            . "</tr>"
            . "<tr>"
            . "<td style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
            . "$employee_nm"
            . "</td>"
            . "<td style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
            . "$approval1_person_nm"
            . "</td>"
            . ($doubleapproval==1?"<td style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
            . "$approval2_person_nm"
            . "</td>":"")
            . "</tr>"
            . "<tr>"
            . "<td style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
            . "Participant"
            . "</td>"
            . "<td style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
            . "$approval1_job_nm"
            . "</td>"
            . ($doubleapproval==1?"<td style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
            . "$approval2_job_nm"
            . "</td>":"")
            . "</tr>"
            . "<tr>"
            . "<td style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;padding:10px;'>"
            . ($status_cd=="prepared"&&$self_job_id==$job_id?"<input type='button' value='Submit' onclick='confirm_submit(this,event);'/>":"")
            . ($status_cd!="prepared"?"Submited at:<br/>".sql2ind($submit_dttm,"date"):"")
            . "</td>"
            . "<td style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;padding:10px;'>"
            . ($status_cd=="prepared"?"-":"")
            . ($status_cd=="approval1"&&$self_job_id==$approval1_job_id?"<input type='button' value='Approve' onclick='confirm_approval1(this,event);'/>&nbsp;<input type='button' value='Not Approve' onclick='confirm_return1(this,event);'/>":"")
            //. ($status_cd=="approval1"&&$self_job_id==$approval1_job_id?"&nbsp;<input type='button' value='Return' onclick='confirm_return1(this,event);'/>":"")
            . ($status_cd=="approval1"&&$self_job_id!=$approval1_job_id?"-":"")
            . ($status_cd=="approval2"?"Approved at:<br/>".sql2ind($approval1_dttm,"date"):"")
            . ($status_cd=="completed"?"Approved at:<br/>".sql2ind($approval1_dttm,"date"):"")
            . "</td>"
            . ($doubleapproval==1?"<td style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;padding:10px;'>"
            . ($status_cd=="prepared"?"-":"")
            . ($status_cd=="approval2"&&$self_job_id==$approval2_job_id?"<input type='button' value='Approve' onclick='confirm_approval2(this,event);'/>":"")
            . ($status_cd=="approval2"&&$self_job_id!=$approval2_job_id?"-":"")
            . ($status_cd=="completed"?"Approved at:<br/>".sql2ind($approval2_dttm,"date"):"")
            . "</td>":"")
            . "</tr>"
          . "</tbody>"
          . "</table>"
          . "</div>";

   $tinycss = tinycss(getTheme());
   
   $_SESSION["html"]->js_tinymce = TRUE;
   if($status_cd=="prepared") {
      $_SESSION["html"]->registerLoadAction("init_my_tiny");
   }
   $js = $ajax->getJs()."<script type='text/javascript'><!--
      
      function cancel_delete_subject(subject_id,d,e) {
         var td = $('trsubject_'+subject_id).firstChild;
         _destroy(td.dv);
         $('btndelsubject_'+subject_id).style.display = '';
      }
      
      function delete_subject(subject_id,d,e) {
         var td = $('trsubject_'+subject_id).firstChild;
         td.dv = _dce('div');
         td.dv = td.insertBefore(td.dv,td.lastChild);
         td.dv.innerHTML = '<div style=\"background-color:#ffcccc;border:1px solid #bbb;border-top:0;padding:5px;text-align:center;\">Are you going to delete this subject?<br/><br/>'
                      + '<input type=\"button\" onclick=\"confirm_delete_subject(\\''+subject_id+'\\',this,event);\" value=\"Yes (delete)\"/>&nbsp;'
                      + '<input type=\"button\" onclick=\"cancel_delete_subject(\\''+subject_id+'\\',this,event);\" value=\"No\"/></div>';
         $('btndelsubject_'+subject_id).style.display = 'none';
      }
      
      function confirm_delete_subject(subject_id,d,e) {
         var tr = $('trsubject_'+subject_id);
         _destroy(tr);
         mCOUNSL_app_deleteSubject('$request_id','$actionplan_id',subject_id,null);
      }
      
      function new_subject() {
         $('subjectprogress').innerHTML = '';
         $('subjectprogress').appendChild(progress_span());
         mCOUNSL_app_newSubject('$request_id','$actionplan_id',function(_data) {
            $('subjectprogress').innerHTML = '';
            var data = recjsarray(_data);
            var tr = _dce('tr');
            tr.td0 = tr.appendChild(_dce('td'));
            tr.td0.setAttribute('style','padding:20px;');
            tr.setAttribute('id','trsubject_'+data[0]);
            tr.td0.innerHTML = data[1];
            tr = $('tbodysubject').insertBefore(tr,$('trempty_subject'));
            $('textareasubject_'+data[0]).focus();
            $('trempty_subject').style.display = 'none';
            ///tinyMCE.execCommand('mceAddControl',false,'textareasubject_'+data[0]);
         });
      }
      
      
      
      function cancel_delete_idea(subject_id,idea_id,d,e) {
         var td = $('tridea_'+idea_id+'_'+subject_id).firstChild;
         td.innerHTML = td.oldHTML;
      }
      
      
      function delete_idea(subject_id,idea_id,d,e) {
         var td = $('tridea_'+idea_id+'_'+subject_id).firstChild;
         td.oldHTML = td.innerHTML;
         td.innerHTML = '<div style=\"background-color:#ffcccc;padding:5px;text-align:center;\">Are you going to delete this item?<br/><br/>'
                      + '<input type=\"button\" onclick=\"confirm_delete_idea(\\''+subject_id+'\\',\\''+idea_id+'\\',this,event);\" value=\"Yes (delete)\"/>&nbsp;'
                      + '<input type=\"button\" onclick=\"cancel_delete_idea(\\''+subject_id+'\\',\\''+idea_id+'\\',this,event);\" value=\"No\"/></div>';
                      
      }
      
      function confirm_delete_idea(subject_id,idea_id,d,e) {
         var tr = $('tridea_'+idea_id+'_'+subject_id);
         _destroy(tr);
         mCOUNSL_app_deleteIdea('$request_id','$actionplan_id',subject_id,idea_id,null);
      }
      
      function new_idea(subject_id) {
         $('ideaprogress_'+subject_id).innerHTML = '';
         $('ideaprogress_'+subject_id).appendChild(progress_span());
         mCOUNSL_app_newIdea('$request_id','$actionplan_id',subject_id,function(_data) {
            var data = recjsarray(_data);
            $('ideaprogress_'+data[1]).innerHTML = '';
            var tr = _dce('tr');
            tr.td0 = tr.appendChild(_dce('td'));
            tr.td1 = tr.appendChild(_dce('td'));
            tr.td1.setAttribute('valign','top');
            tr.setAttribute('id','tridea_'+data[0]+'_'+data[1]);
            tr.td0.innerHTML = data[2];
            tr.td1.innerHTML = data[3];
            tr = $('tbodyidea_'+data[1]).insertBefore(tr,$('trempty_idea'));
            $('textareaidea_'+data[0]+'_'+data[1]).focus();
            $('trempty_idea_'+data[1]).style.display = 'none';
            
         });
      }
      
      
      
      
      /*
      function cancel_delete_idea(idea_id,d,e) {
         var td = $('tridea_'+idea_id).firstChild;
         td.innerHTML = td.oldHTML;
      }
      
      function delete_idea(idea_id,d,e) {
         var td = $('tridea_'+idea_id).firstChild;
         td.oldHTML = td.innerHTML;
         td.innerHTML = '<div style=\"padding:5px;text-align:center;\">Are you going to delete this idea?<br/><br/>'
                      + '<input type=\"button\" onclick=\"confirm_delete_idea(\\''+idea_id+'\\',this,event);\" value=\"Yes (delete)\"/>&nbsp;'
                      + '<input type=\"button\" onclick=\"cancel_delete_idea(\\''+idea_id+'\\',this,event);\" value=\"No\"/></div>';
                      
      }
      
      function confirm_delete_idea(idea_id,d,e) {
         var tr = $('tridea_'+idea_id);
         _destroy(tr);
         mCOUNSL_app_deleteIdea('$event_id','$employee_id',idea_id,null);
      }
      
      function new_idea() {
         $('ideaprogress').innerHTML = '';
         $('ideaprogress').appendChild(progress_span());
         mCOUNSL_app_newIdea('$event_id','$employee_id',function(_data) {
            $('ideaprogress').innerHTML = '';
            var data = recjsarray(_data);
            var tr = _dce('tr');
            tr.td0 = tr.appendChild(_dce('td'));
            tr.td1 = tr.appendChild(_dce('td'));
            tr.td1.setAttribute('valign','top');
            tr.setAttribute('id','tridea_'+data[0]);
            tr.td0.innerHTML = data[1];
            tr.td1.innerHTML = data[2];
            tr = $('tbodyidea').insertBefore(tr,$('trempty_idea'));
            $('textareaidea_'+data[0]).focus();
            $('trempty_idea').style.display = 'none';
         });
      }
      */
      
      
      
      
      function do_approval2() {
         $('confirmbtn').innerHTML = '';
         $('confirmbtn').appendChild(progress_span(' ... approve report'));
         mCOUNSL_app_approval2Report('$request_id','$actionplan_id',function(_data) {
            confirmapproval2box.fade();
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?approve=y&request_id=${request_id}&actionplan_id=${actionplan_id}&r='+uniqid('r');
         });
      }
      
      var confirmapproval2 = null;
      var confirmapproval2box = null;
      function confirm_approval2(d,e) {
         confirmapproval2 = _dce('div');
         confirmapproval2.setAttribute('id','confirmapproval2');
         confirmapproval2 = document.body.appendChild(confirmapproval2);
         confirmapproval2.sub = confirmapproval2.appendChild(_dce('div'));
         confirmapproval2.sub.setAttribute('id','innerconfirmapproval2');
         confirmapproval2box = new GlassBox();
         $('innerconfirmapproval2').innerHTML = '<div style=\"height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;\">Approve Report Confirmation</div>'
                                           + '<div id=\"confirmmsg\" style=\"padding:20px;text-align:center;\">Are you going to approve this report?</div>'
                                           + '<div id=\"confirmbtn\" style=\"background-color:#eee;padding:10px;text-align:center;\">'
                                             + '<input type=\"button\" value=\"Yes (Approve)\" onclick=\"do_approval2();\"/>&nbsp;&nbsp;'
                                             + '<input type=\"button\" value=\"No\" onclick=\"confirmapproval2box.fade();\"/>'
                                           + '</div>';
         
         
         confirmapproval2box = new GlassBox();
         confirmapproval2box.init('confirmapproval2','500px','165px','hidden','default',false,false);
         confirmapproval2box.lbo(false,0.3);
         confirmapproval2box.appear();
      }
      
      
      
      function do_return1() {
         var return_note = urlencode($('return_note').value);
         mCOUNSL_app_return1Report(\"$request_id\",\"$actionplan_id\",return_note,function(_data) {
            var data = recjsarray(_data);
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?approve=y&request_id=${request_id}&actionplan_id=${actionplan_id}&r='+uniqid('r');
         });
      }
      
      var superiorreturn1edit = null;
      var superiorreturn1box = null;
      function confirm_return1(d,e) {
         superiorreturn1edit = _dce('div');
         superiorreturn1edit.setAttribute('id','superiorreturn1edit');
         superiorreturn1edit = document.body.appendChild(superiorreturn1edit);
         superiorreturn1edit.sub = superiorreturn1edit.appendChild(_dce('div'));
         superiorreturn1edit.sub.setAttribute('id','innersuperiorreturn1edit');
         superiorreturn1box = new GlassBox();
         superiorreturn1box.init('superiorreturn1edit','600px','350px','hidden','default',false,false);
         superiorreturn1box.lbo(false,0.3);
         superiorreturn1box.appear();
         
         var xreturn_note = '';
         if($('xreturn_note')) {
            xreturn_note = $('xreturn_note').innerHTML;
         }
         
         $('innersuperiorreturn1edit').innerHTML = '<div style=\"height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;\">Not Approve Report Confirmation</div>'
                                   + '<div style=\"background-color:#ffffff;font-weight:bold;font-size:1.1em;height:300px;text-align:center;padding-top:20px;\">'
                                   + 'You do not approve this report.<br/>You are going to return this report to the employee.'
                                   + '<br/><br/>With the following notes:<br/>'
                                   + '<textarea id=\"return_note\" style=\"width:400px;height:100px;\">'+xreturn_note+'</textarea>'
                                   + '<br/><br/>Are you sure?'
                                   + '<br/><br/>'
                                           + '<div id=\"confirmbtn\" style=\"background-color:#eee;padding:10px;text-align:center;\">'
                                   + '<input type=\"button\" value=\"Yes (Not Approve)\" onclick=\"do_return1();\"/>&nbsp;'
                                   + '<input type=\"button\" value=\"No\" onclick=\"superiorreturn1box.fade();\"/>'
                                   + '</div>'
                                   + '</div>';
         setTimeout('$(\"return_note\").focus();',200);
      }
      
      
      
      
      function do_approval1() {
         $('confirmbtn').innerHTML = '';
         $('confirmbtn').appendChild(progress_span(' ... approve report'));
         mCOUNSL_app_approval1Report('$request_id','$actionplan_id',function(_data) {
            confirmapproval1box.fade();
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?approve=y&request_id=${request_id}&actionplan_id=${actionplan_id}&r='+uniqid('r');
         });
      }
      
      var confirmapproval1 = null;
      var confirmapproval1box = null;
      function confirm_approval1(d,e) {
         confirmapproval1 = _dce('div');
         confirmapproval1.setAttribute('id','confirmapproval1');
         confirmapproval1 = document.body.appendChild(confirmapproval1);
         confirmapproval1.sub = confirmapproval1.appendChild(_dce('div'));
         confirmapproval1.sub.setAttribute('id','innerconfirmapproval1');
         confirmapproval1box = new GlassBox();
         $('innerconfirmapproval1').innerHTML = '<div style=\"height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;\">Approve Report Confirmation</div>'
                                           + '<div id=\"confirmmsg\" style=\"padding:20px;text-align:center;\">Are you going to approve this report?</div>'
                                           + '<div id=\"confirmbtn\" style=\"background-color:#eee;padding:10px;text-align:center;\">'
                                             + '<input type=\"button\" value=\"Yes (Approve)\" onclick=\"do_approval1();\"/>&nbsp;&nbsp;'
                                             + '<input type=\"button\" value=\"No\" onclick=\"confirmapproval1box.fade();\"/>'
                                           + '</div>';
         
         
         confirmapproval1box = new GlassBox();
         confirmapproval1box.init('confirmapproval1','500px','165px','hidden','default',false,false);
         confirmapproval1box.lbo(false,0.3);
         confirmapproval1box.appear();
      }
      
      function do_submit() {
         $('confirmbtn').innerHTML = '';
         $('confirmbtn').appendChild(progress_span(' ... submiting report'));
         mCOUNSL_app_submitReport('$request_id','$actionplan_id',function(_data) {
            confirmsubmitbox.fade();
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?reporting=y&request_id=${request_id}&actionplan_id=${actionplan_id}&r='+uniqid('r');
         });
      }
      
      var confirmsubmit = null;
      var confirmsubmitbox = null;
      function confirm_submit(d,e) {
         confirmsubmit = _dce('div');
         confirmsubmit.setAttribute('id','confirmsubmit');
         confirmsubmit = document.body.appendChild(confirmsubmit);
         confirmsubmit.sub = confirmsubmit.appendChild(_dce('div'));
         confirmsubmit.sub.setAttribute('id','innerconfirmsubmit');
         confirmsubmitbox = new GlassBox();
         $('innerconfirmsubmit').innerHTML = '<div style=\"height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;\">Submit Report Confirmation</div>'
                                           + '<div id=\"confirmmsg\" style=\"padding:20px;text-align:center;\">Are you going to submit this report?</div>'
                                           + '<div id=\"confirmbtn\" style=\"background-color:#eee;padding:10px;text-align:center;\">'
                                             + '<input type=\"button\" value=\"Yes (Submit)\" onclick=\"do_submit();\"/>&nbsp;&nbsp;'
                                             + '<input type=\"button\" value=\"No\" onclick=\"confirmsubmitbox.fade();\"/>'
                                           + '</div>';
         
         
         confirmsubmitbox = new GlassBox();
         confirmsubmitbox.init('confirmsubmit','500px','165px','hidden','default',false,false);
         confirmsubmitbox.lbo(false,0.3);
         confirmsubmitbox.appear();
      }
      
      function reedit_subject(subject_id,d,e) {
         $('progress_subject_'+subject_id).innerHTML = '';
         $('progress_subject_'+subject_id).appendChild(progress_span());
         mCOUNSL_app_reeditSubject('$request_id','$actionplan_id',subject_id,function(_data) {
            var data = recjsarray(_data);
            var tr = $('trsubject_'+data[0]);
            tr.td = tr.firstChild;
            tr.td.innerHTML = data[1];
            //setTimeout(\"$('progress_subject_\"+data[0]+\"').innerHTML = '';\",1000);
         });
      }
      
      function save_subject(subject_id,read_only,d,e) {
         var idea = '';
         var subject_txt = '';
         var subject_dttm = $('inp_subject_dttm_'+subject_id).value;
         if($('textareasubject_'+subject_id)) {
            if(subject_id>0) {
               subject_txt = $('textareasubject_'+subject_id).value;
               var xidea = $('tbodyidea_'+subject_id);
               if(xidea) {
                  idea += urlencode('@@')+_parseForm(xidea);
               }
            }
         }
         $('progress_subject_'+subject_id).innerHTML = '';
         $('progress_subject_'+subject_id).appendChild(progress_span(' saving ... '));
         mCOUNSL_app_saveReportSubject('$request_id','$actionplan_id',subject_id,urlencode(subject_txt),idea,read_only,subject_dttm,function(_data) {
            var data = recjsarray(_data);
            if(data[2]==0) {
               setTimeout(\"$('progress_subject_\"+data[0]+\"').innerHTML = '';\",1000);
            } else {
               var tr = $('trsubject_'+data[0]);
               tr.td = tr.firstChild;
               tr.td.innerHTML = data[1];
            }
         });
      }
      
      function save_report(d,e) {
         var rating = _parseForm('formrating');
         var idea = '';
         var tbodysubject = $('tbodysubject');
         var subjects = tbodysubject.getElementsByTagName('div');
         var retsubject = '';
         for(var i=0;i<subjects.length;i++) {
            if(subjects[i].id&&subjects[i].id.substring(0,16)=='textareasubject_') {
               var ss = subjects[i].id.split('_');
               if(ss.length==2) {
                  var subject_id = parseInt(ss[1]);
                  if(subject_id>0) {
                     var subject_txt = tinyMCE.get(subjects[i].id).getContent();
                     retsubject += '@@' + subjects[i].id + '^^' + subject_txt;
                     var xidea = $('tbodyidea_'+ss[1]);
                     if(xidea) {
                        idea += urlencode('@@')+_parseForm(xidea);
                     }
                  }
               }
            }
         }
         retsubject = urlencode(retsubject.substring(2));
         $('progress').innerHTML = '';
         $('progress'). appendChild(progress_span(' saving ... '));
         mCOUNSL_app_saveReport('$request_id','$actionplan_id',retsubject,rating,idea,function(_data) {
            setTimeout(\"$('progress').innerHTML = '';\",1000);
         });
      }
      
      function init_my_tiny() {
         if(1) {
            tinyMCE.init({
               mode : '".($idchap!=""?"exact":"none")."',
               elements : '${idchap}',
               theme : 'advanced',
               theme_advanced_buttons1 : 'bold,italic,underline,strikethrough,separator,bullist,numlist,outdent,indent,separator,justifyleft,justifycenter,justifyright,separator,forecolor,removeformat,cleanup,separator,image,table,code,cut,copy,pasteword',
               theme_advanced_buttons2 : '', //cut,copy,pasteword',
               theme_advanced_buttons3 : '',
            	theme_advanced_toolbar_location : 'top',
            	theme_advanced_toolbar_align : 'left',
            	apply_source_formatting : true,
            	content_css : '".XOCP_SERVER_SUBDIR."/${tinycss}',
            	browsers : 'msie,gecko,opera,safari',
            	auto_reset_designmode : true,
            	convert_urls : false,
               button_tile_map : true,
               cleanup_on_startup : true,
               cleanup: true,
            	plugins : 'inlinepopups,table,paste'
            });
         }
      }
      
      function init2_my_tiny(element_id) {
         if(1) {
            tinyMCE.init({
               mode : '".($idchap!=""?"exact":"none")."',
               elements : element_id,
               theme : 'advanced',
               theme_advanced_buttons1 : 'bold,italic,underline,strikethrough,separator,bullist,numlist,outdent,indent,separator,justifyleft,justifycenter,justifyright,separator,forecolor,removeformat,cleanup,separator,image,table,code,cut,copy,pasteword',
               theme_advanced_buttons2 : '', //cut,copy,pasteword',
               theme_advanced_buttons3 : '',
            	theme_advanced_toolbar_location : 'top',
            	theme_advanced_toolbar_align : 'left',
            	apply_source_formatting : true,
            	content_css : '".XOCP_SERVER_SUBDIR."/${tinycss}',
            	browsers : 'msie,gecko,opera,safari',
            	auto_reset_designmode : true,
            	convert_urls : false,
               button_tile_map : true,
               cleanup_on_startup : true,
               cleanup: true,
            	plugins : 'inlinepopups,table,paste'
            });
         }
      }
      
   
   // --></script>";
   
   $_SESSION["html"]->addHeadScript($js);
   $_SESSION["html"]->addHeadScript("<script type='text/javascript' src='".XOCP_SERVER_SUBDIR."/include/calendar.js'></script>");
   return $form;
}

function COUNSL_idp_m_getRemark($request_id,$actionplan_id) {
   $db=&Database::getInstance();
   
   $method_nm = "";
   $sql = "SELECT a.status_cd,a.method_t,b.method_type,a.method_id,a.competency_id,a.plan_start_dttm,a.plan_stop_dttm,a.cost_estimate,method_subject FROM ".XOCP_PREFIX."idp_request_actionplan a"
        . " LEFT JOIN ".XOCP_PREFIX."idp_development_method_type b USING(method_t)"
        . " WHERE a.request_id = '$request_id' AND a.actionplan_id = '$actionplan_id'";
   $result = $db->query($sql);
   if($db->getRowsNum($result)==1) {
      list($request_status,$method_t,$method_type,$method_id,$competency_id,$plan_start_dttm,$plan_stop_dttm,$cost_estimate,$method_subject)=$db->fetchRow($result);
      $sql = "SELECT status_cd FROM ".XOCP_PREFIX."idp_report_COUNSL"
           . " WHERE request_id = '$request_id' AND actionplan_id = '$actionplan_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($report_status)=$db->fetchRow($result);
      } else {
         $report_status = "";
      }
   }
   $remark = "<span>$method_subject</span>";
   return array($remark,$plan_start_dttm,$plan_stop_dttm,$report_status,$cost_estimate,$method_subject,"");
}


function COUNSL_idp_m_editActionPlan($request_id,$actionplan_id) {
   $db=&Database::getInstance();
   
   $sql = "SELECT a.event_id,a.method_t,b.method_type,a.method_id,a.competency_id,a.plan_start_dttm,a.plan_stop_dttm,a.cost_estimate,method_subject,selfstudy_id"
        . " FROM ".XOCP_PREFIX."idp_request_actionplan a"
        . " LEFT JOIN ".XOCP_PREFIX."idp_development_method_type b USING(method_t)"
        . " WHERE a.request_id = '$request_id' AND a.actionplan_id = '$actionplan_id'";
   $result = $db->query($sql);
   if($db->getRowsNum($result)==1) {
      list($event_id,$method_t,$method_type,$method_id,$competency_id,$plan_start_dttm,$plan_stop_dttm,$cost_estimate,$method_subject,$selfstudy_id)=$db->fetchRow($result);
      
      $dvopthdr = "<div style='padding:3px;text-align:center;background-color:#fff;font-weight:bold;background-color:#ddd;color:black;cursor:default;'>Select Self Study</div>";
      $current_selfstudy = "-";
      
   }
   
   if($current_selfstudy=="-") {
      $current_selfstudy = "Click to select";
   }
   
   $sql = "SELECT competency_abbr,competency_nm FROM ".XOCP_PREFIX."competency WHERE competency_id = '$competency_id'";
   $result = $db->query($sql);
   list($competency_abbr,$competency_nm)=$db->fetchRow($result);
   
   $ret = "<table id='method_coach_${request_id}_${actionplan_id}' style='width:100%;'>"
               . "<colgroup>"
                  . "<col width='200'/>"
                  . "<col/>"
                  . "<col/>"
                  . "<col/>"
               . "</colgroup>"
        . "<tbody>"
        . "<tr><td style='text-align:right;'>Competency to be Developed : </td><td colspan='3' style='font-weight:bold;'>$competency_abbr - $competency_nm</td></tr>"
        . "<tr><td style='text-align:right;'>Method : </td><td colspan='3' >$method_type</td></tr>"
        . "<tr><td style='text-align:right;'>Subject : </td><td class='cb' colspan='3' style='vertical-align:middle;'>"
            . "<input type='text' style='width:350px;margin-right:2px;' name='method_subject' id='method_subject' value='$method_subject'/>"
        . "</td></tr>"
        . "<tr>"
            . "<td style='text-align:right;'>Time Frame : </td><td colspan='4'>"
               . "<table style='width:100%;'><colgroup><col width='50%'/><col width='50%'/></colgroup><tbody><tr>"
               . "<td>Start : "
                     . "<span style='".($event_id>0?"display:none;":"")."' class='xlnk' id='sp_plan_start_dttm' onclick='_changedatetime(\"sp_plan_start_dttm\",\"plan_start_dttm\",\"date\",true,false);'>".sql2ind($plan_start_dttm,"date")."</span>"
                     . "<span style='".($event_id==0?"display:none;":"")."' id='spstatic_plan_start_dttm'>".sql2ind($plan_start_dttm,"date")."</span>"
                     . "<input type='hidden' value='$plan_start_dttm' id='plan_start_dttm' name='plan_start_dttm'/>"
                     . "</td>"
               . "<td>Stop : "
                     . "<span style='".($event_id>0?"display:none;":"")."' class='xlnk' id='sp_plan_stop_dttm' onclick='_changedatetime(\"sp_plan_stop_dttm\",\"plan_stop_dttm\",\"date\",true,false);'>".sql2ind($plan_stop_dttm,"date")."</span>"
                     . "<span style='".($event_id==0?"display:none;":"")."' id='spstatic_plan_stop_dttm'>".sql2ind($plan_stop_dttm,"date")."</span>"
                     . "<input type='hidden' value='$plan_stop_dttm' id='plan_stop_dttm' name='plan_stop_dttm'/>"
                     . "</td>"
               . "</tr></tbody></table>"
               . "<input type='hidden' name='cost_estimate' value='0' id='inpcost'/>"
               . "</td></tr>"
        // . "<tr><td style='text-align:right;'>Cost Estimate IDR: </td><td colspan='3'><input type='text' style='width:120px;' name='cost_estimate' value='$cost_estimate' id='inpcost' onkeydown='chcost(\"inpcost\",this,event);'/> <span id='spcost'>".toMoneyShort($cost_estimate)."</span></td></tr>"
        . "</tbody></table>"
        . "<div style='-moz-border-radius:5px;border:1px solid #bbb;background-color:#fff;display:none;position:absolute;width:400px;padding:3px;-moz-box-shadow:1px 1px 5px #000;' id='dvchtraining'>"
        . "${dvopthdr}<div style='max-height:200px;overflow:auto;'>${dvtemplate}</div>"
        . "</div>";
   return array($ret,"255px");
}

function COUNSL_idp_m_saveAction($request_id,$actionplan_id,$ret) {
   $db=&Database::getInstance();
   $vars = _parseForm($ret);
   $sql = "UPDATE ".XOCP_PREFIX."idp_request_actionplan SET "
        . "method_subject = '".$vars["method_subject"]."',"
        . "plan_start_dttm = '".getSQLDate($vars["plan_start_dttm"])."',"
        . "plan_stop_dttm = '".getSQLDate($vars["plan_stop_dttm"])."'"
        . " WHERE request_id = '$request_id'"
        . " AND actionplan_id = '$actionplan_id'";
   $db->query($sql);
}

////////////////////////// Javascript ////////////////////////////////

if(isset($_GET["js"])&&$_GET["js"]==1) {

$str = <<<EOD

function _idpm_save(request_id,actionplan_id) {
   ret = _parseForm('method_coach_'+request_id+'_'+actionplan_id);
   save_ap('COUNSL',request_id,actionplan_id,ret);
}

function _COUNSL_doselecttemplate(selfstudy_id,selfstudy_nm,d,e) {
   $('spcurtrn').innerHTML = selfstudy_nm;
   $('spcurtrn').style.display = '';
   $('selfstudy_id').value = selfstudy_id;
   $('dvchtraining').style.display = 'none';
   $('inpcost').style.display = '';
   $('sp_plan_start_dttm').innerHTML = sql2string($('plan_start_dttm').value);
   $('sp_plan_stop_dttm').innerHTML = sql2string($('plan_stop_dttm').value);
   $('sp_plan_start_dttm').style.display = '';
   $('spstatic_plan_start_dttm').style.display = 'none';
   $('sp_plan_stop_dttm').style.display = '';
   $('spstatic_plan_stop_dttm').style.display = 'none';
}

function _COUNSL_doselectother(d,e) {
   $('spcurtrn').innerHTML = 'Click to select';
   $('spcurtrn').style.display = 'none';
   $('method_id').value = 0;
   $('event_id').value = 0;
   $('method_subject').style.display = '';
   $('dvchtraining').style.display = 'none';
   $('inpcost').style.display = '';
   $('sp_plan_start_dttm').innerHTML = sql2string($('plan_start_dttm').value);
   $('sp_plan_stop_dttm').innerHTML = sql2string($('plan_stop_dttm').value);
   $('sp_plan_start_dttm').style.display = '';
   $('spstatic_plan_start_dttm').style.display = 'none';
   $('sp_plan_stop_dttm').style.display = '';
   $('spstatic_plan_stop_dttm').style.display = 'none';
   $('method_subject').focus();
}

function _COUNSL_selectselfstudy(request_id,actionplan_id,d,e) {
   if($('dvchtraining').style.display == '') {
      $('dvchtraining').style.display = 'none';
   } else {
      $('dvchtraining').style.display = '';
      $('dvchtraining').style.top = parseInt(oY(d)+d.offsetHeight)+'px';
      $('dvchtraining').style.left = oX(d.parentNode)+'px';
   }
}

EOD;

header("Content-type: text/javascript; charset=utf-8");

echo $str;

}

?>