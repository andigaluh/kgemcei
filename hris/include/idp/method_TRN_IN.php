<?php


function TRN_IN_idp_m_getReportingEventForm($event_id,$employee_id) {
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



function TRN_IN_idp_m_getReportingForm($request_id,$actionplan_id,$preview=0) {
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
   
   $sql = "SELECT b.job_level FROM ".XOCP_PREFIX."jobs a"
        . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
        . " WHERE a.job_id = '$job_id'";
   $result = $db->query($sql);
   list($employee_job_level)=$db->fetchRow($result);
   
   if($employee_job_level=="management") {
      $doubleapproval = 1;
   } else {
      $doubleapproval = 0;
   }
   
   $sql = "SELECT c.job_nm,c.job_abbr,d.org_nm,d.org_abbr,a.employee_ext_id,e.person_nm,e.person_id"
        . " FROM ".XOCP_PREFIX."employee a"
        . " LEFT JOIN ".XOCP_PREFIX."employee_job b ON b.employee_id = a.employee_id AND b.job_id = '$job_id'"
        . " LEFT JOIN ".XOCP_PREFIX."jobs c ON c.job_id = '$job_id'"
        . " LEFT JOIN ".XOCP_PREFIX."orgs d ON d.org_id = c.org_id"
        . " LEFT JOIN ".XOCP_PREFIX."persons e ON e.person_id = a.person_id"
        . " WHERE a.employee_id = '$employee_id'";
   $result = $db->query($sql);
   list($job_nm,$job_abbr,$org_nm,$org_abbr,$nip,$employee_nm,$person_id)=$db->fetchRow($result);
      
   $sql = "SELECT a.event_id,a.method_t,b.method_type,a.method_id,c.method_nm,a.plan_start_dttm,a.plan_stop_dttm,a.competency_id FROM ".XOCP_PREFIX."idp_request_actionplan a"
        . " LEFT JOIN ".XOCP_PREFIX."idp_development_method_type b USING(method_t)"
        . " LEFT JOIN ".XOCP_PREFIX."idp_development_method c ON c.method_id = a.method_id"
        . " WHERE a.request_id = '$request_id' AND a.actionplan_id = '$actionplan_id'";
   $rap = $db->query($sql);
   if($db->getRowsNum($rap)==1) {
      list($event_id,$method_t,$method_type,$method_id,$method_nm,$plan_start_dttm,$plan_stop_dttm,$competency_id)=$db->fetchRow($rap);
   }
   
   if($event_id>0) {
      $sql = "SELECT a.event_title,b.institute_nm,a.start_dttm,a.stop_dttm,a.location FROM ".XOCP_PREFIX."idp_event a LEFT JOIN ".XOCP_PREFIX."idp_institutes b USING(institute_id) WHERE a.event_id = '$event_id'";
      $rc = $db->query($sql);
      list($event_title,$institute_nm,$start_dttm,$stop_dttm,$location)=$db->fetchRow($rc);
   } else {
      $event_title = "";
      $sql = "DELETE FROM ".XOCP_PREFIX."idp_report_TRN_IN"
           . " WHERE event_id = '$event_id'"
           . " AND employee_id = '$employee_id'";
      $db->query($sql);
      return "<div style='padding:20px;text-align:center;'>Report is pending.<br/>Please register training event first to continue reporting.</div>";
   }
   
   $sql = "SELECT conclusion,my_advantage,company_advantage,r_01,r_02,r_03,r_04,r_05,r_06,r_07,"
        . "remark_r_01,remark_r_02,remark_r_03,remark_r_04,remark_r_05,remark_r_06,remark_r_07,"
        . "submit_dttm,section_mgr_job_id,division_mgr_job_id,section_mgr_approve_dttm,division_mgr_approve_dttm,"
        . "section_mgr_emp_id,division_mgr_emp_id,status_cd,return_note"
        . " FROM ".XOCP_PREFIX."idp_report_TRN_IN"
        . " WHERE event_id = '$event_id'"
        . " AND employee_id = '$employee_id'";
   $result = $db->query($sql);
   if($db->getRowsNum($result)==1) {
      list($conclusion,$my_advantage,$company_advantage,$r_01,$r_02,$r_03,$r_04,$r_05,$r_06,$r_07,
           $remark_r_01,$remark_r_02,$remark_r_03,$remark_r_04,$remark_r_05,$remark_r_06,$remark_r_07,
           $submit_dttm,$section_mgr_job_id,$division_mgr_job_id,$section_mgr_approved_dttm,$division_mgr_approved_dttm,
           $section_mgr_emp_id,$division_mgr_emp_id,$status_cd,$return_note)=$db->fetchRow($result);
      
      if($employee_id!=$self_employee_idx&&$status_cd=="prepared") {
         $preview=1;
      }
      
      if(trim($return_note)!="") {
         $return_div = "<div style='color:blue;font-weight:bold;margin-top:10px;'>"
                     . "Report returned with notes:"
                     . "</div>"
                     . "<div id='xreturn_note' style='color:blue;padding-left:20px;'>"
                        . $return_note
                     . "</div>";
      } else {
         $return_div = "";
      }
      
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
           . " WHERE a.job_id = '$section_mgr_job_id'"
           . " ORDER BY e.start_dttm DESC LIMIT 1";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($section_manager_job,$nip,$section_manager_name,$section_mgr_emp_id)=$db->fetchRow($result);
      }
      
      $sql = "SELECT a.job_nm,b.employee_ext_id,c.person_nm,b.employee_id"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job e USING(job_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " WHERE a.job_id = '$division_mgr_job_id'"
           . " ORDER BY e.start_dttm DESC LIMIT 1";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($division_manager_job,$division_manager_nip,$division_manager_name,$division_mgr_emp_id)=$db->fetchRow($result);
      }
   
   } else {
      
      $section_mgr_job_id = $first_assessor_job_id; ///_getSectionManagerJobID($job_id);
      $division_mgr_job_id = $next_assessor_job_id; ///_getDivisionManagerJobID($job_id);
      
      $sql = "SELECT a.job_nm,b.employee_ext_id,c.person_nm,b.employee_id"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job e USING(job_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " WHERE a.job_id = '$section_mgr_job_id'"
           . " ORDER BY e.start_dttm DESC LIMIT 1";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($section_manager_job,$nip,$section_manager_name,$section_mgr_emp_id)=$db->fetchRow($result);
      }
      
      $sql = "SELECT a.job_nm,b.employee_ext_id,c.person_nm,b.employee_id"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job e USING(job_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " WHERE a.job_id = '$division_mgr_job_id'"
           . " ORDER BY e.start_dttm DESC LIMIT 1";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($division_manager_job,$division_manager_nip,$division_manager_name,$division_mgr_emp_id)=$db->fetchRow($result);
      }
   
      $sql = "INSERT INTO ".XOCP_PREFIX."idp_report_TRN_IN (event_id,employee_id,actionplan_id,request_id,section_mgr_job_id,section_mgr_emp_id,division_mgr_job_id,division_mgr_emp_id)"
           . " VALUES ('$event_id','$employee_id','$actionplan_id','$request_id','$section_mgr_job_id','$section_mgr_emp_id','$division_mgr_job_id','$division_mgr_emp_id')";
      $db->query($sql);
      
      $status_cd = "prepared";
      
   }
   
   $sql = "SELECT idea_id,idea_txt,start_dttm,stop_dttm"
        . " FROM ".XOCP_PREFIX."idp_report_TRN_IN_ideas"
        . " WHERE event_id = '$event_id'"
        . " AND employee_id = '$employee_id'"
        . " ORDER BY idea_id";
   $result = $db->query($sql);
   $ideas = "";
   if($db->getRowsNum($result)>0) {
      $display_idea = "display:none;";
      while(list($idea_id,$idea_txt,$idea_start_dttm,$idea_stop_dttm)=$db->fetchRow($result)) {
         if($preview==0&&$status_cd=="prepared") {
            $ideas .= "<tr id='tridea_${idea_id}'><td>"
                    . "<input type='text' style='width:95%;' id='textareaidea_${idea_id}' name='textareaidea_${idea_id}' value='$idea_txt'/>"
                    . "<div style='padding:2px;'>[<span class='xlnk' onclick='delete_idea(\"$idea_id\",this,event);'>delete</span>]</div>"
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
   $form = "<div style='text-align:center;font-size:1.2em;font-weight:bold;'>TRAINING REPORT</div>"
         
         . "<div style='margin-bottom:10px;margin-top:10px;padding:5px;border:1px solid #bbb;-moz-border-radius:5px;'>"
            . "<table style='width:100%;'><tbody>"
            . "<tr><td style='font-weight:bold;text-align:right;'>Training Subject : </td><td>$event_title</td><td style='font-weight:bold;text-align:right;'>Institution : </td><td>Internal</td></tr>"
            . "<tr><td style='font-weight:bold;text-align:right;'>Place of Training : </td><td>$location</td><td style='font-weight:bold;text-align:right;'>Date of Training : </td><td><nobr>".sql2ind($start_dttm,"date")." - ".sql2ind($stop_dttm,"date")."</nobr></td></tr>"
            . "<tr><td style='font-weight:bold;text-align:right;'>Name : </td><td>$employee_nm</td><td style='font-weight:bold;text-align:right;'>NIP : </td><td>$nip</td></tr>"
            . "<tr><td style='font-weight:bold;text-align:right;'>Job Title : </td><td>$job_nm ($job_abbr)</td><td style='font-weight:bold;text-align:right;'>Section/Division : </td><td>$org_nm ($org_abbr)</td></tr>"
            . "</tbody></table>"
         . "</div>"
         
         . "<div style='padding:10px;'>"
            . "<div style='font-weight:bold;'>A. Conclusion of Training Result:</div>"
            . "<div style='".($preview==0&&$status_cd=="prepared"?"height:300px;":"")."border:0px solid #bbb;padding:15px;padding-top:0px;' id='conclusion' name='conclusion'>$conclusion</div>"
         . "</div>"
         
         . "<div style='padding:10px;'>"
            . "<div style='font-weight:bold;'>B. The Advantages of Training:</div>"
            . "<div style='padding:10px;padding-left:15px;'>"
               . "<div style='font-weight:bold;'>For My Self</div>"
               . "<div style='".($preview==0&&$status_cd=="prepared"?"height:200px;":"")."border:0px solid #bbb;padding:15px;padding-top:0px;' id='my_advantage' name='my_advantage'>$my_advantage</div>"
            . "</div>"
            . "<div style='padding:10px;padding-left:15px;'>"
               . "<div style='font-weight:bold;'>For Company</div>"
               . "<div style='".($preview==0&&$status_cd=="prepared"?"height:200px;":"")."border:0px solid #bbb;padding:15px;padding-top:0px;' id='company_advantage' name='company_advantage'>$company_advantage</div>"
            . "</div>"
         . "</div>"
         
         . "<div id='formrating' style='padding:10px;'>"
            . "<div style='font-weight:bold;'>C. Rating of Program:</div>"
            . "<div style='padding:10px;padding-left:15px;'>"
               . "<div style='font-weight:bold;'>Choose the suitable point (1 ~ 5, 5 is the highest) to rate the below factors:</div>"
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
               . "<tr>"
                  . "<td style='text-align:center;'>1</td>"
                  . "<td>Training Content</td>"
                  . "<td style='text-align:center;'>".($preview==0&&$status_cd=="prepared"?"<input type='radio' name='r_01' value='1' ".($r_01==1?"checked='1'":"")."/>":($r_01==1?"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td style='text-align:center;'>".($preview==0&&$status_cd=="prepared"?"<input type='radio' name='r_01' value='2' ".($r_01==2?"checked='1'":"")."/>":($r_01==2?"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td style='text-align:center;'>".($preview==0&&$status_cd=="prepared"?"<input type='radio' name='r_01' value='3' ".($r_01==3?"checked='1'":"")."/>":($r_01==3?"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td style='text-align:center;'>".($preview==0&&$status_cd=="prepared"?"<input type='radio' name='r_01' value='4' ".($r_01==4?"checked='1'":"")."/>":($r_01==4?"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td style='text-align:center;'>".($preview==0&&$status_cd=="prepared"?"<input type='radio' name='r_01' value='5' ".($r_01==5?"checked='1'":"")."/>":($r_01==5?"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td>".($preview==0&&$status_cd=="prepared"?"<input type='text' name='remark_r_01' id='remark_r_01' value='$remark_r_01'/>":$remark_r_01)."</td>"
               . "</tr>"
               . "<tr>"
                  . "<td style='text-align:center;'>2</td>"
                  . "<td>Teacher/Instructor's Ability</td>"
                  . "<td style='text-align:center;'>".($preview==0&&$status_cd=="prepared"?"<input type='radio' name='r_02' value='1' ".($r_02==1?"checked='1'":"")."/>":($r_02==1?"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td style='text-align:center;'>".($preview==0&&$status_cd=="prepared"?"<input type='radio' name='r_02' value='2' ".($r_02==2?"checked='1'":"")."/>":($r_02==2?"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td style='text-align:center;'>".($preview==0&&$status_cd=="prepared"?"<input type='radio' name='r_02' value='3' ".($r_02==3?"checked='1'":"")."/>":($r_02==3?"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td style='text-align:center;'>".($preview==0&&$status_cd=="prepared"?"<input type='radio' name='r_02' value='4' ".($r_02==4?"checked='1'":"")."/>":($r_02==4?"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td style='text-align:center;'>".($preview==0&&$status_cd=="prepared"?"<input type='radio' name='r_02' value='5' ".($r_02==5?"checked='1'":"")."/>":($r_02==5?"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td>".($preview==0&&$status_cd=="prepared"?"<input type='text' name='remark_r_02' id='remark_r_02' value='$remark_r_02'/>":$remark_r_02)."</td>"
               . "</tr>"
               . "<tr>"
                  . "<td style='text-align:center;'>3</td>"
                  . "<td>Method of Teaching</td>"
                  . "<td style='text-align:center;'>".($preview==0&&$status_cd=="prepared"?"<input type='radio' name='r_03' value='1' ".($r_03==1?"checked='1'":"")."/>":($r_03==1?"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td style='text-align:center;'>".($preview==0&&$status_cd=="prepared"?"<input type='radio' name='r_03' value='2' ".($r_03==2?"checked='1'":"")."/>":($r_03==2?"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td style='text-align:center;'>".($preview==0&&$status_cd=="prepared"?"<input type='radio' name='r_03' value='3' ".($r_03==3?"checked='1'":"")."/>":($r_03==3?"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td style='text-align:center;'>".($preview==0&&$status_cd=="prepared"?"<input type='radio' name='r_03' value='4' ".($r_03==4?"checked='1'":"")."/>":($r_03==4?"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td style='text-align:center;'>".($preview==0&&$status_cd=="prepared"?"<input type='radio' name='r_03' value='5' ".($r_03==5?"checked='1'":"")."/>":($r_03==5?"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td>".($preview==0&&$status_cd=="prepared"?"<input type='text' name='remark_r_03' id='remark_r_03' value='$remark_r_03'/>":$remark_r_03)."</td>"
               . "</tr>"
               . "<tr>"
                  . "<td style='text-align:center;'>4</td>"
                  . "<td>Training Process</td>"
                  . "<td style='text-align:center;'>".($preview==0&&$status_cd=="prepared"?"<input type='radio' name='r_04' value='1' ".($r_04==1?"checked='1'":"")."/>":($r_04==1?"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td style='text-align:center;'>".($preview==0&&$status_cd=="prepared"?"<input type='radio' name='r_04' value='2' ".($r_04==2?"checked='1'":"")."/>":($r_04==2?"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td style='text-align:center;'>".($preview==0&&$status_cd=="prepared"?"<input type='radio' name='r_04' value='3' ".($r_04==3?"checked='1'":"")."/>":($r_04==3?"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td style='text-align:center;'>".($preview==0&&$status_cd=="prepared"?"<input type='radio' name='r_04' value='4' ".($r_04==4?"checked='1'":"")."/>":($r_04==4?"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td style='text-align:center;'>".($preview==0&&$status_cd=="prepared"?"<input type='radio' name='r_04' value='5' ".($r_04==5?"checked='1'":"")."/>":($r_04==5?"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td>".($preview==0&&$status_cd=="prepared"?"<input type='text' name='remark_r_04' id='remark_r_04' value='$remark_r_04'/>":$remark_r_04)."</td>"
               . "</tr>"
               . "<tr>"
                  . "<td style='text-align:center;'>5</td>"
                  . "<td>Duration of Training</td>"
                  . "<td style='text-align:center;'>".($preview==0&&$status_cd=="prepared"?"<input type='radio' name='r_05' value='1' ".($r_05==1?"checked='1'":"")."/>":($r_05==1?"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td style='text-align:center;'>".($preview==0&&$status_cd=="prepared"?"<input type='radio' name='r_05' value='2' ".($r_05==2?"checked='1'":"")."/>":($r_05==2?"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td style='text-align:center;'>".($preview==0&&$status_cd=="prepared"?"<input type='radio' name='r_05' value='3' ".($r_05==3?"checked='1'":"")."/>":($r_05==3?"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td style='text-align:center;'>".($preview==0&&$status_cd=="prepared"?"<input type='radio' name='r_05' value='4' ".($r_05==4?"checked='1'":"")."/>":($r_05==4?"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td style='text-align:center;'>".($preview==0&&$status_cd=="prepared"?"<input type='radio' name='r_05' value='5' ".($r_05==5?"checked='1'":"")."/>":($r_05==5?"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td>".($preview==0&&$status_cd=="prepared"?"<input type='text' name='remark_r_05' id='remark_r_05' value='$remark_r_05'/>":$remark_r_05)."</td>"
               . "</tr>"
               . "<tr>"
                  . "<td style='text-align:center;'>6</td>"
                  . "<td>Training Facilities</td>"
                  . "<td style='text-align:center;'>".($preview==0&&$status_cd=="prepared"?"<input type='radio' name='r_06' value='1' ".($r_06==1?"checked='1'":"")."/>":($r_06==1?"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td style='text-align:center;'>".($preview==0&&$status_cd=="prepared"?"<input type='radio' name='r_06' value='2' ".($r_06==2?"checked='1'":"")."/>":($r_06==2?"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td style='text-align:center;'>".($preview==0&&$status_cd=="prepared"?"<input type='radio' name='r_06' value='3' ".($r_06==3?"checked='1'":"")."/>":($r_06==3?"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td style='text-align:center;'>".($preview==0&&$status_cd=="prepared"?"<input type='radio' name='r_06' value='4' ".($r_06==4?"checked='1'":"")."/>":($r_06==4?"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td style='text-align:center;'>".($preview==0&&$status_cd=="prepared"?"<input type='radio' name='r_06' value='5' ".($r_06==5?"checked='1'":"")."/>":($r_06==5?"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td>".($preview==0&&$status_cd=="prepared"?"<input type='text' name='remark_r_06' id='remark_r_06' value='$remark_r_06'/>":$remark_r_06)."</td>"
               . "</tr>"
               . "<tr>"
                  . "<td style='text-align:center;'>7</td>"
                  . "<td>Suitability with Your Expectation</td>"
                  . "<td style='text-align:center;'>".($preview==0&&$status_cd=="prepared"?"<input type='radio' name='r_07' value='1' ".($r_07==1?"checked='1'":"")."/>":($r_07==1?"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td style='text-align:center;'>".($preview==0&&$status_cd=="prepared"?"<input type='radio' name='r_07' value='2' ".($r_07==2?"checked='1'":"")."/>":($r_07==2?"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td style='text-align:center;'>".($preview==0&&$status_cd=="prepared"?"<input type='radio' name='r_07' value='3' ".($r_07==3?"checked='1'":"")."/>":($r_07==3?"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td style='text-align:center;'>".($preview==0&&$status_cd=="prepared"?"<input type='radio' name='r_07' value='4' ".($r_07==4?"checked='1'":"")."/>":($r_07==4?"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td style='text-align:center;'>".($preview==0&&$status_cd=="prepared"?"<input type='radio' name='r_07' value='5' ".($r_07==5?"checked='1'":"")."/>":($r_07==5?"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_black.png'/>":"<img alt='' src='".XOCP_SERVER_SUBDIR."/images/bullet_white.png'/>"))."</td>"
                  . "<td>".($preview==0&&$status_cd=="prepared"?"<input type='text' name='remark_r_07' id='remark_r_07' value='$remark_r_07'/>":$remark_r_07)."</td>"
               . "</tr>"
               . "</tbody></table>"
            . "</div>"
         . "</div>"
         
         . "<div style='padding:10px;'>"
            . "<div style='font-weight:bold;'>D. Description of my ideas and schedule to implement the training result in my section/division:</div>"
            
            . "<div style='padding:10px;padding-left:15px;' id='dvidea'>"
               . "<table class='xxlist' style='width:100%;'><colgroup><col width='50%'/><col/></colgroup>"
               . "<thead>"
               . ($preview==0&&$status_cd=="prepared"?"<tr><td>&nbsp;</td><td style='text-align:right;'><span id='ideaprogress'></span>&nbsp;<input type='button' value='New' onclick='new_idea(this,event);'/></td></tr>":"")
               . "<tr><td>Idea</td><td>Schedule</td></tr>"
               . "</thead>"
               . "<tbody id='tbodyidea'>"
               . $ideas
               . "<tr id='trempty_idea' style='$display_idea'><td colspan='2' style='text-align:center;font-style:italic;'>"._EMPTY."</td></tr>"
               . "</tbody>"
               . "</table>"
            . "</div>"
            
            
         . "</div>"
         
         . ($status_cd=="prepared"?"<div style='padding:5px;text-align:right;background-color:#eee;border:1px solid #bbb;'><span id='progress'></span>&nbsp;"
         . ($preview==1?"<input ".($self_employee_idx==$employee_id?"":"style='display:none;'")." type='button' value='Edit' onclick='cancel_preview_report(\"$request_id\",\"$actionplan_id\");'/>":"<input type='button' value='Save Draft' onclick='save_report(this,event);'/>"
         . "&nbsp;" /* <input type='button' value='Preview' onclick='preview_report(\"$request_id\",\"$actionplan_id\");'/>" */ )
         . "</div>":"")
         
         . ($preview==1&&$self_job_id==$job_id?"":$return_div);
         
   $form .= "<div style='text-align:right;padding:10px;'>"
          
          . "<table align='center' style='border-top:2px solid #777;border-left:2px solid #777;border-spacing:0px;'>"
          . "<colgroup>"
          . "<col width='200'/>"
          . "<col width='200'/>"
          . ($doubleapproval==1?"<col width='200'/>":"")
          . "</colgroup>"
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
            . "$section_manager_name"
            . "</td>"
            
            . ($doubleapproval==1?""
            . "<td style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
            . "$division_manager_name"
            . "</td>"
            . "":"")
            
            . "</tr>"
            . "<tr>"
            . "<td style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
            . "Participant"
            . "</td>"
            . "<td style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
            . "$section_manager_job"
            . "</td>"
            
            . ($doubleapproval==1?""
            . "<td style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'>"
            . "$division_manager_job"
            . "</td>"
            . "":"")
            
            . "</tr>"
            . "<tr>"
            . "<td style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;padding:10px;'>"
            . ($status_cd=="prepared"&&$self_job_id==$job_id?"<input type='button' value='Submit' onclick='confirm_submit(\"".($preview==1?"0":"1")."\",this,event);'/>":"")
            . ($status_cd!="prepared"?"Submited at:<br/>".sql2ind($submit_dttm,"date"):"")
            . ($status_cd=="prepared"&&$self_job_id!=$job_id?"Preparation":"")
            . "</td>"
            . "<td style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;padding:10px;'>"
            . ($status_cd=="prepared"?"-":"")
            . ($status_cd=="approval1"&&$self_job_id==$section_mgr_job_id?"<input type='button' value='Approve' onclick='confirm_approval1(this,event);'/>&nbsp;<input type='button' value='Not Approve' onclick='section_mgr_return_report(\"$event_id\",\"$employee_id\");'/>":"")
            //. ($status_cd=="approval1"&&$self_job_id==$section_mgr_job_id?"&nbsp;<input type='button' value='Return' onclick='confirm_return1(this,event);'/>":"")
            . ($status_cd=="approval1"&&$self_job_id!=$section_mgr_job_id?"Waiting for approval":"")
            . ($status_cd=="approval2"?"Approved at:<br/>".sql2ind($section_mgr_approved_dttm,"date"):"")
            . ($status_cd=="completed"?"Approved at:<br/>".sql2ind($section_mgr_approved_dttm,"date"):"")
            . "</td>"
            
            . ($doubleapproval==1?""
            . "<td style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;padding:10px;'>"
            . ($status_cd=="prepared"?"-":"")
            . ($status_cd=="approval2"&&$self_job_id==$division_mgr_job_id?"<input type='button' value='Approve' onclick='confirm_approval2(this,event);'/>&nbsp;<input type='button' value='Not Approve' onclick='division_mgr_return_report(\"$event_id\",\"$employee_id\");'/>":"")
            . ($status_cd=="approval2"&&$self_job_id!=$division_mgr_job_id?"Waiting for approval":"")
            . ($status_cd=="approval1"&&$self_job_id!=$division_mgr_job_id?"-":"")
            . ($status_cd=="approval1"&&$self_job_id==$division_mgr_job_id?"-":"")
            . ($status_cd=="completed"?"Approved at:<br/>".sql2ind($division_mgr_approved_dttm,"date"):"")
            . "</td>"
            . "":"")
            
            . "</tr>"
          . "</tbody>"
          . "</table>"
          
          . "</div>";
          
   $tinycss = tinycss(getTheme());
   
   $_SESSION["html"]->js_tinymce = TRUE;
   if($preview==0&&$status_cd=="prepared") {
      $_SESSION["html"]->registerLoadAction("init_my_tiny");
   }
   require_once(XOCP_DOC_ROOT."/modules/hris/include/idp/ajax/ajax_method_TRN_IN.php");
   $ajax = new _idp_class_method_TRN_IN_ajax("mTRN_IN");
   $js = $ajax->getJs()."<script type='text/javascript'><!--
      
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
         mTRN_IN_app_deleteIdea('$event_id','$employee_id',idea_id,null);
      }
      
      function new_idea() {
         $('ideaprogress').innerHTML = '';
         $('ideaprogress').appendChild(progress_span());
         mTRN_IN_app_newIdea('$event_id','$employee_id',function(_data) {
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
      
      function do_approval2(event_id,employee_id) {
         $('confirmbtn').innerHTML = '';
         $('confirmbtn').appendChild(progress_span(' ... approve report'));
         mTRN_IN_app_approval2Report('$event_id','$employee_id',function(_data) {
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
      
      function do_approval1(event_id,employee_id) {
         $('confirmbtn').innerHTML = '';
         $('confirmbtn').appendChild(progress_span(' ... approve report'));
         mTRN_IN_app_approval1Report('$event_id','$employee_id',function(_data) {
            confirmapproval1box.fade();
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?approve=y&request_id=${request_id}&actionplan_id=${actionplan_id}&r='+uniqid('r');
         });
      }
      
      function confirm_division_mgr_return_report(event_id,employee_id) {
         var return_note = urlencode($('return_note').value);
         mTRN_IN_app_divisionManagerReturnReport(event_id,employee_id,return_note,function(_data) {
            var data = recjsarray(_data);
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?approve=y&request_id='+data[0]+'&actionplan_id='+data[1]+'&r='+uniqid('r');
         });
      }
      
      var divisionmgrreturnedit = null;
      var divisionmgrreturnbox = null;
      function division_mgr_return_report(event_id,employee_id) {
         divisionmgrreturnedit = _dce('div');
         divisionmgrreturnedit.setAttribute('id','divisionmgrreturnedit');
         divisionmgrreturnedit = document.body.appendChild(divisionmgrreturnedit);
         divisionmgrreturnedit.sub = divisionmgrreturnedit.appendChild(_dce('div'));
         divisionmgrreturnedit.sub.setAttribute('id','innerdivisionmgrreturnedit');
         divisionmgrreturnbox = new GlassBox();
         divisionmgrreturnbox.init('divisionmgrreturnedit','600px','350px','hidden','default',false,false);
         divisionmgrreturnbox.lbo(false,0.3);
         divisionmgrreturnbox.appear();
         
         $('innerdivisionmgrreturnedit').innerHTML = '<div style=\"height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;\">Not Approve Report Confirmation</div>'
                                   + '<div style=\"background-color:#ffffff;font-weight:bold;font-size:1.1em;height:300px;text-align:center;padding-top:20px;\">'
                                   + 'You do not approve this report.<br/>You are going to return this report to the Section Manager.'
                                   + '<br/><br/>With the following notes:<br/>'
                                   + '<textarea id=\"return_note\" style=\"width:400px;height:100px;\"></textarea>'
                                   + '<br/><br/>Are you sure?'
                                   + '<br/><br/>'
                                           + '<div id=\"confirmbtn\" style=\"background-color:#eee;padding:10px;text-align:center;\">'
                                   + '<input type=\"button\" value=\"Yes (Not Approve)\" onclick=\"confirm_division_mgr_return_report(\\''+event_id+'\\',\\''+employee_id+'\\');\"/>&nbsp;'
                                   + '<input type=\"button\" value=\"No\" onclick=\"divisionmgrreturnbox.fade();\"/>'
                                   + '</div>'
                                   + '</div>';
         setTimeout('$(\"return_note\").focus();',200);
      }
      
      function confirm_section_mgr_return_report(event_id,employee_id) {
         var return_note = urlencode($('return_note').value);
         mTRN_IN_app_sectionManagerReturnReport(event_id,employee_id,return_note,function(_data) {
            var data = recjsarray(_data);
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?approve=y&request_id='+data[0]+'&actionplan_id='+data[1]+'&r='+uniqid('r');
         });
      }
      
      var sectionmgrreturnedit = null;
      var sectionmgrreturnbox = null;
      function section_mgr_return_report(event_id,employee_id) {
         sectionmgrreturnedit = _dce('div');
         sectionmgrreturnedit.setAttribute('id','sectionmgrreturnedit');
         sectionmgrreturnedit = document.body.appendChild(sectionmgrreturnedit);
         sectionmgrreturnedit.sub = sectionmgrreturnedit.appendChild(_dce('div'));
         sectionmgrreturnedit.sub.setAttribute('id','innersectionmgrreturnedit');
         sectionmgrreturnbox = new GlassBox();
         sectionmgrreturnbox.init('sectionmgrreturnedit','600px','350px','hidden','default',false,false);
         sectionmgrreturnbox.lbo(false,0.3);
         sectionmgrreturnbox.appear();
         
         var xreturn_note = '';
         if($('xreturn_note')) {
            xreturn_note = $('xreturn_note').innerHTML;
         }
         
         $('innersectionmgrreturnedit').innerHTML = '<div style=\"height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;\">Not Approve Report Confirmation</div>'
                                   + '<div style=\"background-color:#ffffff;font-weight:bold;font-size:1.1em;height:300px;text-align:center;padding-top:20px;\">'
                                   + 'You do not approve this report.<br/>You are going to return this report to the employee.'
                                   + '<br/><br/>With the following notes:<br/>'
                                   + '<textarea id=\"return_note\" style=\"width:400px;height:100px;\">'+xreturn_note+'</textarea>'
                                   + '<br/><br/>Are you sure?'
                                   + '<br/><br/>'
                                           + '<div id=\"confirmbtn\" style=\"background-color:#eee;padding:10px;text-align:center;\">'
                                   + '<input type=\"button\" value=\"Yes (Not Approve)\" onclick=\"confirm_section_mgr_return_report(\\''+event_id+'\\',\\''+employee_id+'\\');\"/>&nbsp;'
                                   + '<input type=\"button\" value=\"No\" onclick=\"sectionmgrreturnbox.fade();\"/>'
                                   + '</div>'
                                   + '</div>';
         setTimeout('$(\"return_note\").focus();',200);
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
      
      function do_submit(event_id,employee_id) {
         $('confirmbtn').innerHTML = '';
         $('confirmbtn').appendChild(progress_span(' ... submiting report'));
         mTRN_IN_app_submitReport('$event_id','$employee_id',function(_data) {
            confirmsubmitbox.fade();
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?reporting=y&request_id=${request_id}&actionplan_id=${actionplan_id}&r='+uniqid('r');
         });
      }
      
      var confirmsubmit = null;
      var confirmsubmitbox = null;
      function confirm_submit(save_first,d,e) {
         if(save_first==1) {
            save_report(null,null);
         }
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
      
      function preview_report(request_id,actionplan_id) {
         location.href = '".XOCP_SERVER_SUBDIR."/index.php?reporting=y&request_id='+request_id+'&actionplan_id='+actionplan_id+'&preview=1';
      }
      
      function cancel_preview_report(request_id,actionplan_id) {
         location.href = '".XOCP_SERVER_SUBDIR."/index.php?reporting=y&request_id='+request_id+'&actionplan_id='+actionplan_id+'&preview=0';
      }
      
      function save_report(d,e) {
         var rating = _parseForm('formrating');
         var idea = _parseForm('dvidea');
         var conclusion = tinyMCE.get('conclusion').getContent();
         conclusion = urlencode(conclusion);
         var my_advantage = tinyMCE.get('my_advantage').getContent();
         my_advantage = urlencode(my_advantage);
         var company_advantage = tinyMCE.get('company_advantage').getContent();
         company_advantage = urlencode(company_advantage);
         $('progress').innerHTML = '';
         $('progress'). appendChild(progress_span(' saving ... '));
         mTRN_IN_app_saveReport('$event_id','$employee_id',conclusion,my_advantage,company_advantage,rating,idea,function(_data) {
            setTimeout(\"$('progress').innerHTML = '';\",1000);
         });
      }
      
      function init_my_tiny() {
         if($('conclusion')) {
            tinyMCE.init({
               mode : 'exact',
               elements : 'conclusion,my_advantage,company_advantage',
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
      
      function auto_save_report() {
         save_report(null,null);
         ct.reset();
         ct.start();
      }
      
      var ct = null;
   
   
   // --></script>";
   
   $_SESSION["html"]->addHeadScript($js);
   $_SESSION["html"]->addHeadScript("<script type='text/javascript' src='".XOCP_SERVER_SUBDIR."/include/calendar.js'></script>");
   
   $jsf = "<script type='text/javascript'><!--
   
      if('$status_cd'=='prepared') {
         ct = new ctimer('auto_save_report();',60000);
         ct.start();
      }
   
   // --></script>";
   
   return $form.$jsf;
}

function TRN_IN_idp_m_getRemark($request_id,$actionplan_id) {
   $db=&Database::getInstance();
   
   $method_nm = "";
   $institute_nm = "";
   $sql = "SELECT a.event_id,a.method_t,b.method_type,a.method_id,a.competency_id,a.plan_start_dttm,a.plan_stop_dttm,a.cost_estimate,method_subject FROM ".XOCP_PREFIX."idp_request_actionplan a"
        . " LEFT JOIN ".XOCP_PREFIX."idp_development_method_type b USING(method_t)"
        . " WHERE a.request_id = '$request_id' AND a.actionplan_id = '$actionplan_id'";
   $result = $db->query($sql);
   if($db->getRowsNum($result)==1) {
      list($event_id,$method_t,$method_type,$method_id,$competency_id,$plan_start_dttm,$plan_stop_dttm,$cost_estimate,$method_subject)=$db->fetchRow($result);
      if($event_id>0) {
         $sql = "SELECT a.event_title,b.institute_nm FROM ".XOCP_PREFIX."idp_event a"
              . " LEFT JOIN ".XOCP_PREFIX."idp_institutes b USING(institute_id)"
              . " WHERE a.event_id = '$event_id'";
         $result = $db->query($sql);
         if($db->getRowsNum($result)==1) {
            list($method_nm,$institute_nm)=$db->fetchRow($result);
         }
      } else if($method_id>0) {
         $sql = "SELECT b.method_nm FROM ".XOCP_PREFIX."idp_development_method b"
              . " WHERE b.method_id = '$method_id'";
         $result = $db->query($sql);
         if($db->getRowsNum($result)>0) {
            list($method_nm)=$db->fetchRow($result);
         }
      } else {
         $method_nm = $method_subject;
      }
   }
   if($event_id>0) {
      $sql = "SELECT status_cd FROM ".XOCP_PREFIX."idp_report_TRN_IN WHERE request_id = '$request_id' AND actionplan_id = '$actionplan_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($report_status)=$db->fetchRow($result);
      }
   } else {
      $report_status = "";
   }
   $remark = "<div>$method_nm</div>";
   return array($remark,$plan_start_dttm,$plan_stop_dttm,$report_status,$cost_estimate,$method_nm,"");
}


function TRN_IN_idp_m_editActionPlan($request_id,$actionplan_id) {
   $db=&Database::getInstance();
   
   $sql = "SELECT a.event_id,a.method_t,b.method_type,a.method_id,a.competency_id,a.plan_start_dttm,"
        . "a.plan_stop_dttm,a.cost_estimate,method_subject,a.other_institute_nm"
        . " FROM ".XOCP_PREFIX."idp_request_actionplan a"
        . " LEFT JOIN ".XOCP_PREFIX."idp_development_method_type b USING(method_t)"
        . " WHERE a.request_id = '$request_id' AND a.actionplan_id = '$actionplan_id'";
   $result = $db->query($sql);
   if($db->getRowsNum($result)==1) {
      list($event_id,$method_t,$method_type,$method_id,$competency_id,$plan_start_dttm,
           $plan_stop_dttm,$cost_estimate,$method_subject,$other_institute_nm)=$db->fetchRow($result);
      $sql = "SELECT rcl,ccl,itj,gap FROM ".XOCP_PREFIX."idp_request_competency"
           . " WHERE request_id = '$request_id' AND competency_id = '$competency_id'";
      $result = $db->query($sql);
      list($rcl,$ccl,$gap)=$db->fetchRow($result);
      
      if($event_id>0) {
         $sql = "SELECT b.institute_nm FROM ".XOCP_PREFIX."idp_event a"
              . " LEFT JOIN ".XOCP_PREFIX."idp_institutes b USING(institute_id)"
              . " WHERE a.event_id = '$event_id'";
         $rins = $db->query($sql);
         if($db->getRowsNum($rins)>0) {
            list($institute_nm)=$db->fetchRow($rins);
         }
      } else {
         $institute_nm = "";
      }
      
      $dvopthdr = "<div style='padding:3px;text-align:center;background-color:#fff;font-weight:bold;background-color:#ddd;color:black;cursor:default;'>Select Internal Training</div>"
                . "<div style='padding:4px;'><div style='font-style:italic;border:0px solid #bbb;padding:3px;-moz-border-radius:5px;text-align:center;'>Please choose training event or training subject below.<br/>In case no event or subject match your requirement<br/>please click 'Other' at the bottom to request one.</div></div>";
      $current_training = "-";
      
      $sql = "SELECT a.method_id,b.method_nm FROM ".XOCP_PREFIX."idp_method_competency_rel a"
           . " LEFT JOIN ".XOCP_PREFIX."idp_development_method b USING(method_id)"
           . " WHERE a.competency_id = '$competency_id'"
           . " AND b.method_t = '$method_t'"
           . " AND a.rcl_min <= '$ccl'"
           . " AND a.rcl_max > '$ccl'"
           . " GROUP BY a.method_id";
      $result = $db->query($sql);
      $dvtemplate = $dvevent = "";
      if($db->getRowsNum($result)>0) {
         while(list($method_idx,$method_nmx)=$db->fetchRow($result)) {
            if($event_id==0&&$method_idx==$method_id) {
               $current_training = $method_nmx;
            }
            $dvtemplate .= "<div class='cb' style='padding:3px;border-top:1px solid #bbb;' onclick='_TRN_IN_doselecttemplate(\"$method_idx\",\"$method_nmx\",this,event);'>$method_nmx (subject)</div>";
            /*
            $sql = "SELECT a.event_id,a.event_title,a.start_dttm,a.stop_dttm,a.cost_budget_person,a.registration_t,b.institute_nm"
                 . " FROM ".XOCP_PREFIX."idp_event a"
                 . " LEFT JOIN ".XOCP_PREFIX."idp_institutes b USING(institute_id)"
                 . " WHERE a.method_id = '$method_idx'"
                 . " AND a.start_dttm >= now()"
                 . " ORDER BY a.start_dttm";
            $revent = $db->query($sql);
            if($db->getRowsNum($revent)>0) {
               while(list($event_idx,$event_title,$event_start_dttm,$event_stop_dttm,$event_cost,$event_registration_t,$institute_nmx)=$db->fetchRow($revent)) {
                  
                  $sql = "SELECT rcl_min,rcl_max FROM ".XOCP_PREFIX."idp_event_competency_rel"
                       . " WHERE event_id = '$event_idx'"
                       . " AND competency_id = '$competency_id'"
                       . " AND rcl_min <= '$ccl'"
                       . " AND rcl_max > '$ccl'";
                  $rr = $db->query($sql);
                  if($db->getRowsNum($rr)>0) {
                     ///
                  } else {
                     continue;
                  }
                  
                  if($event_idx==$event_id) {
                     $current_training = $event_title;
                  }
                  
                  $event_cost += 0;
                  $dvevent .= "<div class='cb' style='padding:3px;border-top:1px solid #bbb;' onclick='_TRN_IN_doselectevent(\"$event_idx\",\"$method_idx\",\"$event_title\",\"$event_start_dttm\",\"$event_stop_dttm\",\"$event_cost\",\"$institute_nmx\",this,event);'>"
                            . "<div style='font-weight:bold;'>$event_title</div>"
                            //. "<div>$institute_nmx</div>"
                            . "<div>".sql2ind($event_start_dttm,"date")." - ".sql2ind($event_stop_dttm,"date")."</div>"
                            . "<div>Cost Estimate : IDR ".toMoney($event_cost)."</div>"
                            . "</div>";
               }
            }
            */
         }
      }
   }
   
   
   
            $sql = "SELECT a.method_id,a.event_id,a.event_title,a.start_dttm,a.stop_dttm,a.cost_budget_person,a.registration_t,b.institute_nm"
                 . " FROM ".XOCP_PREFIX."idp_event a"
                 . " LEFT JOIN ".XOCP_PREFIX."idp_institutes b USING(institute_id)"
                 . " LEFT JOIN ".XOCP_PREFIX."idp_development_method c ON c.method_id = a.method_id"
                 . " WHERE a.method_t = 'TRN_IN'"
                 //. " AND a.start_dttm >= now()"
                 . " ORDER BY a.start_dttm";
            $revent = $db->query($sql);
            if($db->getRowsNum($revent)>0) {
               while(list($method_idx,$event_idx,$event_title,$event_start_dttm,$event_stop_dttm,$event_cost,$event_registration_t,$institute_nmx)=$db->fetchRow($revent)) {
                  
                  $sql = "SELECT rcl_min,rcl_max FROM ".XOCP_PREFIX."idp_event_competency_rel"
                       . " WHERE event_id = '$event_idx'"
                       . " AND competency_id = '$competency_id'"
                       . " AND rcl_min <= '$ccl'"
                       . " AND rcl_max >= '$ccl'";
                  $rr = $db->query($sql);
                  if($db->getRowsNum($rr)>0) {
                     ///
                  } else {
                     continue;
                  }
                  
                  if($event_idx==$event_id) {
                     $current_training = $event_title;
                  }
                  
                  $event_cost += 0;
                  $dvevent .= "<div class='cb' style='padding:3px;border-top:1px solid #bbb;' onclick='_TRN_IN_doselectevent(\"$event_idx\",\"$method_idx\",\"$event_title\",\"$event_start_dttm\",\"$event_stop_dttm\",\"$event_cost\",\"$institute_nmx\",this,event);'>"
                            . "<div style='font-weight:bold;'>$event_title</div>"
                            //. "<div>$institute_nmx</div>"
                            . "<div>".sql2ind($event_start_dttm,"date")." - ".sql2ind($event_stop_dttm,"date")."</div>"
                            . "<div>Cost Estimate : IDR ".toMoney($event_cost)."</div>"
                            . "</div>";
               }
            }
   
   
   
   $dvother = "<div class='cb' style='padding:3px;border-top:1px solid #bbb;' onclick='_TRN_IN_doselectother(this,event);'>Other</div>";
   
   if($current_training=="-") {
      $current_training = "Click to select";
   }
   
   $sql = "SELECT competency_abbr,competency_nm FROM ".XOCP_PREFIX."competency WHERE competency_id = '$competency_id'";
   $result = $db->query($sql);
   list($competency_abbr,$competency_nm)=$db->fetchRow($result);
   
   $ret = "<table id='method_trn_in_${request_id}_${actionplan_id}' style='width:100%;'>"
               . "<colgroup>"
                  . "<col width='200'/>"
                  . "<col/>"
                  . "<col/>"
                  . "<col/>"
               . "</colgroup>"
        . "<tbody>"
        . "<tr><td style='text-align:right;'>Competency to be Developed : </td><td colspan='3' style='font-weight:bold;'>$competency_abbr - $competency_nm</td></tr>"
        . "<tr><td style='text-align:right;'>Method : </td><td colspan='3' >$method_type</td></tr>"
        . "<tr><td style='text-align:right;'>Training Title : </td><td class='cb' colspan='3' style='vertical-align:middle;'>"
            . "<input type='text' style='width:250px;".($method_id>0?"display:none;":($method_subject==""?"display:none;":""))."margin-right:2px;' name='method_subject' id='method_subject' value='$method_subject'/>"
            . "<span id='spcurtrn' style='".($method_id>0?"":($method_subject!=""?"display:none;":""))."margin-right:5px;'>$current_training</span>"
            . "<img class='cb' alt='Click to select' title='Click to select' src='".XOCP_SERVER_SUBDIR."/images/arrow_down.png' style='vertical-align:middle;cursor:pointer;' onclick='_TRN_IN_selecttraining(\"$request_id\",\"$actionplan_id\",this,event);'/>"
            . "<input type='hidden' value='$method_id' id='method_id' name='method_id'/>"
            . "<input type='hidden' value='$event_id' id='event_id' name='event_id'/>"
        . "</td></tr>"
        . "<tr><td style='text-align:right;'>Institute / Provider : </td><td colspan='3' id='institute_nm'>Internal</td></tr>"
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
               . "</td></tr>"
        . "<tr><td style='text-align:right;'>Cost Estimate IDR: </td><td colspan='3'><input type='text' style='".($event_id>0?"display:none;":"")."width:120px;' name='cost_estimate' value='$cost_estimate' id='inpcost' onkeydown='chcost(\"inpcost\",this,event);'/> <span id='spcost'>".toMoneyShort($cost_estimate)."</span></td></tr>"
        . "</tbody></table>"
        . "<div style='-moz-border-radius:5px;border:1px solid #bbb;background-color:#fff;display:none;position:absolute;width:400px;padding:3px;-moz-box-shadow:1px 1px 5px #000;' id='dvchtraining'>"
        . "${dvopthdr}<div style='border:1px solid #999;-moz-border-radius:5px;padding:10px;max-height:200px;overflow:auto;'>${dvevent}${dvtemplate}${dvother}</div>"
        . "</div>";
   return array($ret,"259px");
}

function TRN_IN_idp_m_saveAction($request_id,$actionplan_id,$ret) {
   $db=&Database::getInstance();
   $vars = _parseForm($ret);
   $event_id = $vars["event_id"];
   $sql = "SELECT employee_id FROM ".XOCP_PREFIX."idp_request WHERE request_id = '$request_id'";
   $result = $db->query($sql);
   list($employee_id)=$db->fetchRow($result);
   if($event_id>0) {
      $sql = "SELECT registration_t FROM ".XOCP_PREFIX."idp_event WHERE event_id = '$event_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($registration_t)=$db->fetchRow($result);
         if($registration_t=="open") {
            $sql = "INSERT INTO ".XOCP_PREFIX."idp_event_registration (event_id,employee_id,request_id,actionplan_id,status_cd)"
                 . " VALUES ('$event_id','$employee_id','$request_id','$actionplan_id','self_registered')";
            $db->query($sql);
         }
      }
   }
   $sql = "UPDATE ".XOCP_PREFIX."idp_request_actionplan SET "
        . "method_id = '".$vars["method_id"]."',"
        . "event_id = '".$vars["event_id"]."',"
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
   ret = _parseForm('method_trn_in_'+request_id+'_'+actionplan_id);
   save_ap('TRN_IN',request_id,actionplan_id,ret);
}

function _TRN_IN_doselectevent(event_id,method_id,event_title,start_dttm,stop_dttm,cost_estimate,institute_nm,d,e) {
   $('spcurtrn').innerHTML = event_title;
   //$('institute_nm').innerHTML = institute_nm;
   $('spcurtrn').style.display = '';
   $('spcost').innerHTML = thSep(cost_estimate);
   $('method_id').value = method_id;
   $('event_id').value = event_id;
   $('method_subject').style.display = 'none';
   $('method_subject').value = event_title;
   $('dvchtraining').style.display = 'none';
   $('inpcost').style.display = 'none';
   $('inpcost').value = cost_estimate;
   $('plan_start_dttm').value = start_dttm;
   $('plan_stop_dttm').value = stop_dttm;
   $('spstatic_plan_start_dttm').innerHTML = sql2string(start_dttm);
   $('spstatic_plan_stop_dttm').innerHTML = sql2string(stop_dttm);
   $('sp_plan_start_dttm').style.display = 'none';
   $('spstatic_plan_start_dttm').style.display = '';
   $('sp_plan_stop_dttm').style.display = 'none';
   $('spstatic_plan_stop_dttm').style.display = '';
}

function _TRN_IN_doselecttemplate(method_id,method_nm,d,e) {
   $('spcurtrn').innerHTML = method_nm;
   $('spcurtrn').style.display = '';
   //$('institute_nm').innerHTML = '';
   $('method_id').value = method_id;
   $('event_id').value = 0;
   $('method_subject').style.display = 'none';
   $('method_subject').value = method_nm;
   $('dvchtraining').style.display = 'none';
   $('inpcost').style.display = '';
   $('sp_plan_start_dttm').innerHTML = sql2string($('plan_start_dttm').value);
   $('sp_plan_stop_dttm').innerHTML = sql2string($('plan_stop_dttm').value);
   $('sp_plan_start_dttm').style.display = '';
   $('spstatic_plan_start_dttm').style.display = 'none';
   $('sp_plan_stop_dttm').style.display = '';
   $('spstatic_plan_stop_dttm').style.display = 'none';
}

function _TRN_IN_doselectother(d,e) {
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

function _TRN_IN_selecttraining(request_id,actionplan_id,d,e) {
   if($('dvchtraining').style.display == '') {
      $('dvchtraining').style.display = 'none';
   } else {
      $('dvchtraining').style.display = '';
      if($('method_id').value==0) {
         $('dvchtraining').style.top = parseInt(oY(d)+d.offsetHeight)+'px';
         $('dvchtraining').style.left = oX(d.parentNode)+'px';
      } else {
         $('dvchtraining').style.top = parseInt(oY(d)+d.offsetHeight)+'px';
         $('dvchtraining').style.left = oX(d.parentNode)+'px';
      }
   }
}

EOD;

header("Content-type: text/javascript; charset=utf-8");

echo $str;

}

?>