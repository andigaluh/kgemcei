<?php

global $_self_type;
$_self_type = array(1=>"Read Book",
                    2=>"Comparative Study",
                    3=>"View Instructional Video",
                    4=>"Self Practice",
                    1000=>"Other");
      


function SELF_idp_m_getReportingEventForm($event_id,$employee_id) {
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



function SELF_idp_m_getReportingForm($request_id,$actionplan_id) {
   global $_self_type;
   require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
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
      
   $sql = "SELECT a.method_subject,a.selfstudy_id,a.method_t,b.method_type,a.method_id,c.method_nm,a.plan_start_dttm,a.plan_stop_dttm,a.competency_id"
        . " FROM ".XOCP_PREFIX."idp_request_actionplan a"
        . " LEFT JOIN ".XOCP_PREFIX."idp_development_method_type b USING(method_t)"
        . " LEFT JOIN ".XOCP_PREFIX."idp_development_method c ON c.method_id = a.method_id"
        . " WHERE a.request_id = '$request_id' AND a.actionplan_id = '$actionplan_id'";
   $rap = $db->query($sql);
   if($db->getRowsNum($rap)==1) {
      list($method_subject,$selfstudy_id,$method_t,$method_type,$method_id,$method_nm,$plan_start_dttm,$plan_stop_dttm,$competency_id)=$db->fetchRow($rap);
   }
   
   $sql = "SELECT book_title,book_subtitle,book_author,book_year,book_page_count,r_01,r_02,r_03,r_04,r_05,r_06,r_07,"
        . "remark_r_01,remark_r_02,remark_r_03,remark_r_04,remark_r_05,remark_r_06,remark_r_07,submit_dttm,"
        . "approval1_job_id,approval2_job_id,"
        . "approval1_dttm,approval2_dttm,"
        . "approval1_emp_id,approval2_emp_id,status_cd,"
        . "approval1_note,approval2_note"
        . " FROM ".XOCP_PREFIX."idp_report_SELF_1"
        . " WHERE request_id = '$request_id'"
        . " AND actionplan_id = '$actionplan_id'";
   $result = $db->query($sql);
   if($db->getRowsNum($result)==1) {
      list($book_title,$book_subtitle,$book_author,$book_year,$book_page_count,$r_01,$r_02,$r_03,$r_04,$r_05,$r_06,$r_07,
           $remark_r_01,$remark_r_02,$remark_r_03,$remark_r_04,$remark_r_05,$remark_r_06,$remark_r_07,
           $submit_dttm,$approval1_job_id,$approval2_job_id,$approval1_dttm,$approval2_dttm,
           $approval1_emp_id,$approval2_emp_id,$status_cd,$approval1_note,$approval2_note)=$db->fetchRow($result);
      
      $remark_r_01 = htmlentities($remark_r_01,ENT_QUOTES);
      $remark_r_02 = htmlentities($remark_r_02,ENT_QUOTES);
      $remark_r_03 = htmlentities($remark_r_03,ENT_QUOTES);
      $remark_r_04 = htmlentities($remark_r_04,ENT_QUOTES);
      $remark_r_05 = htmlentities($remark_r_05,ENT_QUOTES);
      $remark_r_06 = htmlentities($remark_r_06,ENT_QUOTES);
      $remark_r_07 = htmlentities($remark_r_07,ENT_QUOTES);
      
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
      
      $book_title = $method_subject;
      
      $sql = "INSERT INTO ".XOCP_PREFIX."idp_report_SELF_1 (employee_id,actionplan_id,request_id,book_title,approval1_job_id,approval1_emp_id,approval2_job_id,approval2_emp_id)"
           . " VALUES ('$employee_id','$actionplan_id','$request_id','$book_title','$approval1_job_id','$approval1_emp_id','$approval2_job_id','$approval2_emp_id')";
      $db->query($sql);
      $status_cd = "prepared";
      
   }
   
   if($book_title != $method_subject) {
      $sql = "UPDATE ".XOCP_PREFIX."idp_report_SELF_1 SET book_title = '$method_subject'"
           . " WHERE request_id = '$request_id'"
           . " AND actionplan_id = '$actionplan_id'"
           . " AND employee_id = '$employee_id'";
      $db->query($sql);
   }
   
   $book_title = $method_subject;
   
   //if($request_id=="106") {
   //   $status_cd = "prepared";
   //}
   
   $sql = "SELECT chapter_id,chapter_txt"
        . " FROM ".XOCP_PREFIX."idp_report_SELF_1_chapters"
        . " WHERE request_id = '$request_id'"
        . " AND actionplan_id = '$actionplan_id'"
        . " ORDER BY chapter_id";
   $result = $db->query($sql);
   $chapters = "";
   $idchap = "";
   if($db->getRowsNum($result)>0) {
      $display_chapter = "display:none;";
      while(list($chapter_id,$chapter_txt)=$db->fetchRow($result)) {
         if($status_cd=="prepared") {
            $idchap .= ",textareachapter_${chapter_id}";
            $chapters .= "<tr id='trchapter_${chapter_id}'><td>"
                       . "<div style='text-align:center;background-color:#eee;padding:2px;'>Chapter No. : $chapter_id</div>"
                       . "<div id='textareachapter_${chapter_id}' style='height:200px;'>$chapter_txt</div>"
                       . "<div id='btndelchapter_${chapter_id}' style='padding:2px;text-align:right;'>[<span class='xlnk' onclick='delete_chapter(\"$chapter_id\",this,event);'>delete chapter</span>]</div>"
                       . "</td></tr>";
         } else {
            $chapters .= "<tr id='trchapter_${chapter_id}'><td>"
                       . "<div style='text-align:center;background-color:#eee;padding:2px;'>Chapter No. : $chapter_id</div>"
                       . "<div id='textareachapter_${chapter_id}'>$chapter_txt</div>"
                       . "</td></tr>";
         }
      }
      $idchap = substr($idchap,1);
   } else {
      $display_chapter = "";
   }
   
   
   ////////////////////////
   
   
   /*
   $sql = "SELECT idea_id,idea_txt"
        . " FROM ".XOCP_PREFIX."idp_report_SELF_1_ideas"
        . " WHERE event_id = '$event_id'"
        . " AND employee_id = '$employee_id'"
        . " ORDER BY idea_id";
   $result = $db->query($sql);
   $ideas = "";
   if($db->getRowsNum($result)>0) {
      $display_idea = "display:none;";
      while(list($idea_id,$idea_txt,$idea_start_dttm,$idea_stop_dttm)=$db->fetchRow($result)) {
         if($status_cd=="prepared") {
            $ideas .= "<tr id='tridea_${idea_id}'><td>"
                    . "<textarea style='width:95%;' id='textareaidea_${idea_id}' name='textareaidea_${idea_id}'>$idea_txt</textarea>"
                    . "<div style='padding:2px;'><input type='button' value='"._DELETE."' class='sbtn' onclick='delete_idea(\"$idea_id\",this,event);'/></div>"
                    . "</td>"
                    . "<td valign='top'><span class='xlnk' id='sp_ideastart_${idea_id}' onclick='_changedatetime(\"sp_ideastart_${idea_id}\",\"idea_start_dttm_${idea_id}\",\"date\",true,false)'>".sql2ind($idea_start_dttm,"date")."</span> - "
                    . "<span class='xlnk' id='sp_ideastop_${idea_id}' onclick='_changedatetime(\"sp_ideastop_${idea_id}\",\"idea_stop_dttm_${idea_id}\",\"date\",true,false);'>".sql2ind($idea_stop_dttm,"date")."</span>"
                    . "<input type='hidden' id='idea_start_dttm_${idea_id}' name='idea_start_dttm_${idea_id}' value='$idea_start_dttm'/>"
                    . "<input type='hidden' id='idea_stop_dttm_${idea_id}' name='idea_stop_dttm_${idea_id}' value='$idea_stop_dttm'/>"
                    . "</td></tr>";
         } else {
            $ideas .= "<tr id='tridea_${idea_id}'><td>"
                    . "$idea_txt"
                    . "</td>"
                    . "<td>".sql2ind($idea_start_dttm,"date")." - "
                    . sql2ind($idea_stop_dttm,"date")
                    . "</td></tr>";
         }
      }
   } else {
      $display_idea = "";
   }
   */
   
   $sql = "SELECT idea_id,idea_txt"
        . " FROM ".XOCP_PREFIX."idp_report_SELF_1_ideas"
        . " WHERE request_id = '$request_id'"
        . " AND actionplan_id = '$actionplan_id'"
        . " ORDER BY idea_id";
   $result = $db->query($sql);
   $ideas = "";
   if($db->getRowsNum($result)>0) {
      $display_idea = "display:none;";
      while(list($idea_id,$idea_txt)=$db->fetchRow($result)) {
         if($preview==0&&$status_cd=="prepared") {
            $ideas .= "<tr id='tridea_${idea_id}'><td>"
                    . "<input type='text' style='width:80%;' id='textareaidea_${idea_id}' name='textareaidea_${idea_id}' value='$idea_txt'/>"
                    . "&nbsp;[<span class='xlnk' onclick='delete_idea(\"$idea_id\",this,event);'>delete</span>]"
                    . "</td></tr>";
         } else {
            $ideas .= "<tr id='tridea_${idea_id}'><td>"
                    . "$idea_txt"
                    . "</td></tr>";
         }
      }
   } else {
      $display_idea = "";
   }
   
   
   
   $form = "<div style='text-align:center;font-size:1.2em;font-weight:bold;'>SELF STUDY REPORT</div>"
         . "<div style='text-align:center;font-size:1.2em;font-style:italic;'>Learning Type : ".$_self_type[$selfstudy_id]."</div>"
         
         . "<div style='margin-bottom:10px;margin-top:10px;padding:5px;border:1px solid #bbb;-moz-border-radius:5px;'>"
            . "<table style='width:100%;'>"
            . "<colgroup><col width='130'/><col/></colgroup>"
            . "<tbody>"
            . "<tr><td style='text-align:right;font-weight:bold;'>Name : </td><td>$employee_nm</td></tr>"
            . "<tr><td style='text-align:right;font-weight:bold;'>NIP : </td><td>$nip</td></tr>"
            . "<tr><td style='text-align:right;font-weight:bold;'>Book Title : </td><td>$book_title</td></tr>"
            . "<tr><td style='text-align:right;font-weight:bold;'>Time frame : </td><td>".sql2ind($plan_start_dttm,"date")." - ".sql2ind($plan_stop_dttm,"date")." $request_id $actionplan_id</td></tr>"
            . "<tr><td style='text-align:right;font-weight:bold;'>Job Title : </td><td>$job_nm ($job_abbr)</td></tr>"
            . "<tr><td style='text-align:right;font-weight:bold;'>Section/Division : </td><td>$org_nm ($org_abbr)</td></tr>"
            . "</tbody></table>"
         . "</div>"
         
         . "<div style='padding:10px;'>"
            . "<div style='font-weight:bold;'>A. Summary:</div>"
            . "<div style='padding:10px;padding-left:15px;' id='dvsummary'>"
               . "<table class='xxlist' style='width:100%;'><colgroup><col/></colgroup>"
               . "<thead>"
               . ($preview==0&&$status_cd=="prepared"?"<tr><td colspan='2' style='text-align:right;'><span id='chapterprogress'></span>&nbsp;<input type='button' value='Add' onclick='new_chapter(this,event);'/></td></tr>":"")
               . "<tr><td style='text-align:left;'>Summary</td></tr>"
               . "</thead>"
               . "<tbody id='tbodychapter'>"
               . $chapters
               . "<tr id='trempty_chapter' style='$display_chapter'><td style='text-align:center;font-style:italic;'>"._EMPTY."</td></tr>"
               . "</tbody>"
               . "</table>"
            . "</div>"
         . "</div>"
         
         
         
         
         . "<div id='formrating' style='padding:10px;'>"
            . "<div style='font-weight:bold;'>B. Rating of Book:</div>"
            . "<div style='padding:10px;padding-left:15px;'>"
               . "<div style='font-weight:bold;'>Chose the suitable point (1 ~ 5, 5 is the highest) to rate the below factors:</div>"
               . "<table style='width:100%;' class='xxlist'>"
               . "<colgroup><col/><col/><col/><col/><col/><col/><col/><col width='170'/></colgroup>"
               . "<thead>"
               . "<tr>"
                  . "<td>No</td>"
                  . "<td>Factors</td>"
                  . "<td colspan='5' style='text-align:center;'>Rating</td>"
                  . "<td>Remarks</td>"
               . "</tr>"
               . "</thead>"
               . "<tbody>"
               . "<tr>"
                  . "<td>&nbsp;</td>"
                  . "<td>&nbsp;</td>"
                  . "<td style='text-align:center;'>1</td>"
                  . "<td style='text-align:center;'>2</td>"
                  . "<td style='text-align:center;'>3</td>"
                  . "<td style='text-align:center;'>4</td>"
                  . "<td style='text-align:center;'>5</td>"
                  . "<td>&nbsp;</td>"
               . "</tr>"
               
               . "</thead>"
               . "<tbody>"
               . "<tr>"
                  . "<td style='text-align:center;'>1</td>"
                  . "<td>Informative & Helpful</td>"
                  . "<td style='text-align:center;'>".($status_cd=="prepared"?"<input type='radio' name='r_01' value='1' ".($r_01==1?"checked='1'":"")."/>":($r_01==1?"<img src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td style='text-align:center;'>".($status_cd=="prepared"?"<input type='radio' name='r_01' value='2' ".($r_01==2?"checked='1'":"")."/>":($r_01==2?"<img src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td style='text-align:center;'>".($status_cd=="prepared"?"<input type='radio' name='r_01' value='3' ".($r_01==3?"checked='1'":"")."/>":($r_01==3?"<img src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td style='text-align:center;'>".($status_cd=="prepared"?"<input type='radio' name='r_01' value='4' ".($r_01==4?"checked='1'":"")."/>":($r_01==4?"<img src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td style='text-align:center;'>".($status_cd=="prepared"?"<input type='radio' name='r_01' value='5' ".($r_01==5?"checked='1'":"")."/>":($r_01==5?"<img src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td>".($status_cd=="prepared"?"<input type='text' name='remark_r_01' id='remark_r_01' value='$remark_r_01'/>":$remark_r_01)."</td>"
               . "</tr>"
               . "<tr>"
                  . "<td style='text-align:center;'>2</td>"
                  . "<td>Applicable to The Job</td>"
                  . "<td style='text-align:center;'>".($status_cd=="prepared"?"<input type='radio' name='r_02' value='1' ".($r_02==1?"checked='1'":"")."/>":($r_02==1?"<img src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td style='text-align:center;'>".($status_cd=="prepared"?"<input type='radio' name='r_02' value='2' ".($r_02==2?"checked='1'":"")."/>":($r_02==2?"<img src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td style='text-align:center;'>".($status_cd=="prepared"?"<input type='radio' name='r_02' value='3' ".($r_02==3?"checked='1'":"")."/>":($r_02==3?"<img src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td style='text-align:center;'>".($status_cd=="prepared"?"<input type='radio' name='r_02' value='4' ".($r_02==4?"checked='1'":"")."/>":($r_02==4?"<img src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td style='text-align:center;'>".($status_cd=="prepared"?"<input type='radio' name='r_02' value='5' ".($r_02==5?"checked='1'":"")."/>":($r_02==5?"<img src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td>".($status_cd=="prepared"?"<input type='text' name='remark_r_02' id='remark_r_02' value='$remark_r_02'/>":$remark_r_02)."</td>"
               . "</tr>"
               . "</tbody></table>"
            . "</div>"
         . "</div>"
         
         . "<div style='padding:10px;'>"
            . "<div style='font-weight:bold;'>C. Next Improvement /  Work Impact:</div>"
            . "<div style='padding:10px;padding-left:15px;' id='dvidea'>"
               . "<table class='xxlist' style='width:100%;'><colgroup><col/></colgroup>"
               . "<thead>"
               . ($preview==0&&$status_cd=="prepared"?"<tr><td style='text-align:right;'><span id='ideaprogress'></span>&nbsp;<input type='button' value='Add' onclick='new_idea(this,event);'/></td></tr>":"")
               . "<tr><td>Improvement</td></tr>"
               . "</thead>"
               . "<tbody id='tbodyidea'>"
               . $ideas
               . "<tr id='trempty_idea' style='$display_idea'><td colspan='2' style='text-align:center;font-style:italic;'>"._EMPTY."</td></tr>"
               . "</tbody>"
               . "</table>"
            . "</div>"
         . "</div>"
         
         . ($status_cd=="prepared"?"<div style='padding:5px;text-align:right;background-color:#eee;border:1px solid #bbb;'><span id='progress'></span>&nbsp;<input type='button' value='Save Draft' onclick='save_report(this,event);'/></div>":"");
         
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
   require_once(XOCP_DOC_ROOT."/modules/hris/include/idp/ajax/ajax_method_SELF.php");
   $ajax = new _idp_class_method_SELF_ajax("mSELF");
   $js = $ajax->getJs()."<script type='text/javascript'><!--
      
      function cancel_delete_chapter(chapter_id,d,e) {
         var td = $('trchapter_'+chapter_id).firstChild;
         _destroy(td.dv);
         $('btndelchapter_'+chapter_id).style.display = '';
      }
      
      function delete_chapter(chapter_id,d,e) {
         var td = $('trchapter_'+chapter_id).firstChild;
         td.dv = _dce('div');
         td.dv = td.insertBefore(td.dv,td.lastChild);
         td.dv.innerHTML = '<div style=\"padding:5px;text-align:center;\">Are you going to delete this chapter?<br/><br/>'
                      + '<input type=\"button\" onclick=\"confirm_delete_chapter(\\''+chapter_id+'\\',this,event);\" value=\"Yes (delete)\"/>&nbsp;'
                      + '<input type=\"button\" onclick=\"cancel_delete_chapter(\\''+chapter_id+'\\',this,event);\" value=\"No\"/></div>';
         $('btndelchapter_'+chapter_id).style.display = 'none';
      }
      
      function confirm_delete_chapter(chapter_id,d,e) {
         var tr = $('trchapter_'+chapter_id);
         _destroy(tr);
         mSELF_app_deleteChapter('$request_id','$actionplan_id',chapter_id,null);
      }
      
      function new_chapter() {
         $('chapterprogress').innerHTML = '';
         $('chapterprogress').appendChild(progress_span());
         mSELF_app_newChapter('$request_id','$actionplan_id',function(_data) {
            $('chapterprogress').innerHTML = '';
            var data = recjsarray(_data);
            var tr = _dce('tr');
            tr.td0 = tr.appendChild(_dce('td'));
            tr.setAttribute('id','trchapter_'+data[0]);
            tr.td0.innerHTML = data[1];
            tr = $('tbodychapter').insertBefore(tr,$('trempty_chapter'));
            $('textareachapter_'+data[0]).focus();
            $('trempty_chapter').style.display = 'none';
            tinyMCE.execCommand('mceAddControl',false,'textareachapter_'+data[0]);
         });
      }
      
      
      
      function cancel_delete_idea(idea_id,d,e) {
         var td = $('tridea_'+idea_id).firstChild;
         td.innerHTML = td.oldHTML;
      }
      
      
      function delete_idea(idea_id,d,e) {
         var td = $('tridea_'+idea_id).firstChild;
         td.oldHTML = td.innerHTML;
         td.innerHTML = '<div style=\"padding:5px;text-align:center;\">Are you going to delete this item?<br/><br/>'
                      + '<input type=\"button\" onclick=\"confirm_delete_idea(\\''+idea_id+'\\',this,event);\" value=\"Yes (delete)\"/>&nbsp;'
                      + '<input type=\"button\" onclick=\"cancel_delete_idea(\\''+idea_id+'\\',this,event);\" value=\"No\"/></div>';
                      
      }
      
      function confirm_delete_idea(idea_id,d,e) {
         var tr = $('tridea_'+idea_id);
         _destroy(tr);
         mSELF_app_deleteIdea('$request_id','$actionplan_id',idea_id,null);
      }
      
      function new_idea() {
         $('ideaprogress').innerHTML = '';
         $('ideaprogress').appendChild(progress_span());
         mSELF_app_newIdea('$request_id','$actionplan_id',function(_data) {
            $('ideaprogress').innerHTML = '';
            var data = recjsarray(_data);
            var tr = _dce('tr');
            tr.td0 = tr.appendChild(_dce('td'));
            tr.setAttribute('id','tridea_'+data[0]);
            tr.td0.innerHTML = data[1];
            tr = $('tbodyidea').insertBefore(tr,$('trempty_idea'));
            $('textareaidea_'+data[0]).focus();
            $('trempty_idea').style.display = 'none';
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
         mSELF_app_deleteIdea('$event_id','$employee_id',idea_id,null);
      }
      
      function new_idea() {
         $('ideaprogress').innerHTML = '';
         $('ideaprogress').appendChild(progress_span());
         mSELF_app_newIdea('$event_id','$employee_id',function(_data) {
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
         mSELF_app_approval2Report('$request_id','$actionplan_id',function(_data) {
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
         mSELF_app_return1Report(\"$request_id\",\"$actionplan_id\",return_note,function(_data) {
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
         mSELF_app_approval1Report('$request_id','$actionplan_id',function(_data) {
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
         mSELF_app_submitReport('$request_id','$actionplan_id',function(_data) {
            confirmsubmitbox.fade();
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?reporting=y&request_id=${request_id}&actionplan_id=${actionplan_id}&r='+uniqid('r');
         });
      }
      
      var confirmsubmit = null;
      var confirmsubmitbox = null;
      function confirm_submit(d,e) {
         save_report(null,null);
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
      
      function save_report(d,e) {
         var rating = _parseForm('formrating');
         var idea = _parseForm('dvidea');
         var tbodychapter = $('tbodychapter');
         var chapters = tbodychapter.getElementsByTagName('div');
         var retchapter = '';
         for(var i=0;i<chapters.length;i++) {
            if(chapters[i].id&&chapters[i].id.substring(0,16)=='textareachapter_') {
               var ss = chapters[i].id.split('_');
               if(ss.length==2) {
                  var chapter_txt = tinyMCE.get(chapters[i].id).getContent();
                  retchapter += '@@' + chapters[i].id + '^^' + chapter_txt;
               }
            }
         }
         retchapter = urlencode(retchapter.substring(2));
         $('progress').innerHTML = '';
         $('progress'). appendChild(progress_span(' saving ... '));
         mSELF_app_saveReport('$request_id','$actionplan_id',retchapter,rating,idea,function(_data) {
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
      
   
   // --></script>";
   
   $_SESSION["html"]->addHeadScript($js);
   $_SESSION["html"]->addHeadScript("<script type='text/javascript' src='".XOCP_SERVER_SUBDIR."/include/calendar.js'></script>");
   return $form;
}

function SELF_idp_m_getRemark($request_id,$actionplan_id) {
   $db=&Database::getInstance();
   global $_self_type;
   
   $method_nm = "";
   $sql = "SELECT a.status_cd,a.selfstudy_id,a.method_t,b.method_type,a.method_id,a.competency_id,a.plan_start_dttm,a.plan_stop_dttm,a.cost_estimate,method_subject FROM ".XOCP_PREFIX."idp_request_actionplan a"
        . " LEFT JOIN ".XOCP_PREFIX."idp_development_method_type b USING(method_t)"
        . " WHERE a.request_id = '$request_id' AND a.actionplan_id = '$actionplan_id'";
   $result = $db->query($sql);
   if($db->getRowsNum($result)==1) {
      list($request_status,$selfstudy_id,$method_t,$method_type,$method_id,$competency_id,$plan_start_dttm,$plan_stop_dttm,$cost_estimate,$method_subject)=$db->fetchRow($result);
      if($selfstudy_id>0) {
         $method_nm = $_self_type[$selfstudy_id];
         $sql = "SELECT status_cd FROM ".XOCP_PREFIX."idp_report_SELF_${selfstudy_id}"
              . " WHERE request_id = '$request_id' AND actionplan_id = '$actionplan_id'";
         $result = $db->query($sql);
         if($db->getRowsNum($result)==1) {
            list($report_status)=$db->fetchRow($result);
         } else {
            $report_status = "";
         }
      } else {
         $method_nm = "";
      }
   }
   if($method_nm!="") {
      $remark = "<span style='font-style:italic;'>$method_nm : </span><br/><span>$method_subject</span>";
   } else {
      $remark = "<span style='font-style:italic;'></span><br/><span>$method_subject</span>";
   }
   return array($remark,$plan_start_dttm,$plan_stop_dttm,$report_status,$cost_estimate,$method_nm,$method_subject);
}


function SELF_idp_m_editActionPlan($request_id,$actionplan_id) {
   $db=&Database::getInstance();
   global $_self_type;
   
   $sql = "SELECT a.event_id,a.method_t,b.method_type,a.method_id,a.competency_id,a.plan_start_dttm,a.plan_stop_dttm,a.cost_estimate,method_subject,selfstudy_id"
        . " FROM ".XOCP_PREFIX."idp_request_actionplan a"
        . " LEFT JOIN ".XOCP_PREFIX."idp_development_method_type b USING(method_t)"
        . " WHERE a.request_id = '$request_id' AND a.actionplan_id = '$actionplan_id'";
   $result = $db->query($sql);
   if($db->getRowsNum($result)==1) {
      list($event_id,$method_t,$method_type,$method_id,$competency_id,$plan_start_dttm,$plan_stop_dttm,$cost_estimate,$method_subject,$selfstudy_id)=$db->fetchRow($result);
      
      $dvopthdr = "<div style='padding:3px;text-align:center;background-color:#fff;font-weight:bold;background-color:#ddd;color:black;cursor:default;'>Select Self Study</div>";
      $current_selfstudy = "-";
      
      $dvtemplate = "";
      foreach($_self_type as $selfstudy_idx=>$valx) {
         if($selfstudy_idx==1) {
            $dvtemplate .= "<div class='cb' style='font-weight:bold;padding:3px;border-top:1px solid #bbb;' onclick='_SELF_doselecttemplate(\"$selfstudy_idx\",\"$valx\",this,event);'>$valx</div>";
         } else {
            $dvtemplate .= "<div class='cb' style='padding:3px;border-top:1px solid #bbb;color:#888;cursor:default;'>$valx</div>";
         }
         if($selfstudy_idx==$selfstudy_id) {
            $current_selfstudy = $valx;
         }
      }
   }
   
   if($current_selfstudy=="-") {
      $current_selfstudy = "Click to select";
   }
   
   $sql = "SELECT competency_abbr,competency_nm FROM ".XOCP_PREFIX."competency WHERE competency_id = '$competency_id'";
   $result = $db->query($sql);
   list($competency_abbr,$competency_nm)=$db->fetchRow($result);
   
   $ret = "<table id='method_self_${request_id}_${actionplan_id}' style='width:100%;'>"
               . "<colgroup>"
                  . "<col width='200'/>"
                  . "<col/>"
                  . "<col/>"
                  . "<col/>"
               . "</colgroup>"
        . "<tbody>"
        . "<tr><td style='text-align:right;'>Competency to be Developed : </td><td colspan='3' style='font-weight:bold;'>$competency_abbr - $competency_nm</td></tr>"
        . "<tr><td style='text-align:right;'>Method : </td><td colspan='3' >$method_type</td></tr>"
        . "<tr><td style='text-align:right;'>Type of Self Study : </td><td class='cb' colspan='3' style='vertical-align:middle;'>"
            . "<input type='hidden' name='selfstudy_id' id='selfstudy_id' value='$selfstudy_id'/>"
            . "<input type='text' style='width:250px;display:none;margin-right:2px;' name='method_subject' id='method_subject' value='$method_subject'/>"
            . "<span id='spcurtrn' style='margin-right:5px;'>$current_selfstudy</span>"
            . "<img class='cb' alt='Click to select' title='Click to select' src='".XOCP_SERVER_SUBDIR."/images/arrow_down.png' style='vertical-align:middle;cursor:pointer;' onclick='_SELF_selectselfstudy(\"$request_id\",\"$actionplan_id\",this,event);'/>"
        . "</td></tr>"
        . "<tr><td style='text-align:right;'>Subject / Title : </td><td class='cb' colspan='3' style='vertical-align:middle;'>"
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

function SELF_idp_m_saveAction($request_id,$actionplan_id,$ret) {
   $db=&Database::getInstance();
   $vars = _parseForm($ret);
   $sql = "UPDATE ".XOCP_PREFIX."idp_request_actionplan SET "
        . "selfstudy_id = '".$vars["selfstudy_id"]."',"
        . "method_subject = '".$vars["method_subject"]."',"
        . "plan_start_dttm = '".getSQLDate($vars["plan_start_dttm"])."',"
        . "plan_stop_dttm = '".getSQLDate($vars["plan_stop_dttm"])."',"
        . "cost_estimate = '".$vars["cost_estimate"]."'"
        . " WHERE request_id = '$request_id'"
        . " AND actionplan_id = '$actionplan_id'";
   $db->query($sql);
}

////////////////////////// Javascript ////////////////////////////////

if(isset($_GET["js"])&&$_GET["js"]==1) {

$str = <<<EOD

function _idpm_save(request_id,actionplan_id) {
   ret = _parseForm('method_self_'+request_id+'_'+actionplan_id);
   save_ap('SELF',request_id,actionplan_id,ret);
}

function _SELF_doselecttemplate(selfstudy_id,selfstudy_nm,d,e) {
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

function _SELF_doselectother(d,e) {
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

function _SELF_selectselfstudy(request_id,actionplan_id,d,e) {
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