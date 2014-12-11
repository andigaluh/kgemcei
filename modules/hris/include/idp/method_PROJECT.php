<?php

function PROJECT_idp_m_getRemark($request_id,$actionplan_id) {
   $db=&Database::getInstance();
   
   $method_nm = "";
   $sql = "SELECT b.project_nm,a.project_id,b.cost_estimate,a.status_cd FROM ".XOCP_PREFIX."idp_request_actionplan a"
        . " LEFT JOIN ".XOCP_PREFIX."idp_project b USING(request_id,project_id)"
        . " WHERE a.request_id = '$request_id' AND a.actionplan_id = '$actionplan_id' AND a.status_cd != 'nullified'";
   $result = $db->query($sql);
   if($db->getRowsNum($result)==1) {
      list($project_nm,$project_id,$cost_estimate,$request_status_cd)=$db->fetchRow($result);
   }
   
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
   
   if($request_status_cd=="requested") {
      $sql = "UPDATE ".XOCP_PREFIX."idp_request_actionplan SET plan_start_dttm = '$minstart0', plan_stop_dttm = '$maxstop0'"
          . " WHERE request_id = '$request_id' AND actionplan_id = '$actionplan_id' AND method_t = 'PROJECT'";
      $db->query($sql);
      
      $sql = "UPDATE ".XOCP_PREFIX."idp_project SET start_dttm = '$minstart0', due_dttm = '$maxstop0'"
           . " WHERE request_id = '$request_id'"
           . " AND project_id = '$project_id'";
      $db->query($sql);
   }
   
   return array($project_nm,$minstart0,$maxstop0,$report_status,$cost_estimate,$project_nm,"");
}

function PROJECT_idp_m_editActionPlan($request_id,$actionplan_id) {
   $db=&Database::getInstance();
   
   $sql = "SELECT a.project_id,a.method_t,b.method_type,a.method_id,a.competency_id,a.plan_start_dttm,a.plan_stop_dttm,a.cost_estimate,method_description FROM ".XOCP_PREFIX."idp_request_actionplan a"
        . " LEFT JOIN ".XOCP_PREFIX."idp_development_method_type b USING(method_t)"
        . " WHERE a.request_id = '$request_id' AND a.actionplan_id = '$actionplan_id'";
   $result = $db->query($sql);
   if($db->getRowsNum($result)==1) {
      list($project_id,$method_t,$method_type,$method_id,$competency_id,$plan_start_dttm,$plan_stop_dttm,$cost_estimate,$method_description)=$db->fetchRow($result);
      
      $sql = "SELECT project_id,project_nm,cost_estimate FROM ".XOCP_PREFIX."idp_project"
           . " WHERE request_id = '$request_id' ORDER BY priority_no";
      $result = $db->query($sql);
      
      $dvopt = "<div style='padding:3px;text-align:center;background-color:#fff;font-weight:bold;background-color:#ddd;color:black;cursor:default;'>Select Project Title</div>";
      $current_project = "-";
      $cost_estimate = 0;
      if($db->getRowsNum($result)>0) {
         while(list($project_idx,$project_nm,$cost_estimatex)=$db->fetchRow($result)) {
            $dvopt .= "<div class='cb' style='padding:3px;border-top:1px solid #bbb;' onclick='_idpm_selectproject(\"$project_idx\",\"$cost_estimatex\",this,event);'>$project_nm</div>";
            if($project_id==$project_idx) {
               $current_project = $project_nm;
               $cost_estimate = $cost_estimatex;
            }
         }
      }
      if($current_project=="-") {
         $current_project = "click to change";
      }
   }
   
   $sql = "SELECT competency_abbr,competency_nm FROM ".XOCP_PREFIX."competency WHERE competency_id = '$competency_id'";
   $result = $db->query($sql);
   list($competency_abbr,$competency_nm)=$db->fetchRow($result);
   
   $ret = "<div style='min-height:90px;'><table id='method_trn_in_${request_id}_${actionplan_id}' style='width:100%;'>"
               . "<colgroup>"
                  . "<col width='200'/>"
                  . "<col/>"
                  . "<col/>"
                  . "<col/>"
               . "</colgroup>"
        . "<tbody>"
        . "<tr><td style='text-align:right;'>Competency to be Developed : </td><td colspan='3' style='font-weight:bold;'>$competency_abbr - $competency_nm</td></tr>"
        . "<tr><td style='text-align:right;'>Method : </td><td colspan='3' >$method_type</td></tr>"
        . "<tr><td style='text-align:right;vertical-align:top;'>Project Title : </td><td colspan='3'><span class='xlnk' id='spcurproj' onclick='_idpm_chgproject(this,event);'>$current_project</span><input type='hidden' value='$project_id' id='project_id' name='project_id'/></td></tr>"
        . "<tr><td style='text-align:right;'>Cost Estimate (IDR) : </td><td colspan='3' id='projectcost'>".toMoneyShort($cost_estimate)."</td></tr>"
        . "</tbody></table></div><div style='-moz-border-radius:5px;border:1px solid #888;background-color:#fff;display:none;position:absolute;width:300px;padding:3px;-moz-box-shadow:1px 1px 5px #000;' id='dvchproject'>$dvopt</div>";
   return array($ret,"220px");
}



function PROJECT_idp_m_getReportingForm($request_id,$project_id,$preview=0) {
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
   $doubleapproval = 0;
   
   $sql = "SELECT c.job_nm,c.job_abbr,d.org_nm,d.org_abbr,a.employee_ext_id,e.person_nm,e.person_id"
        . " FROM ".XOCP_PREFIX."employee a"
        . " LEFT JOIN ".XOCP_PREFIX."employee_job b ON b.employee_id = a.employee_id AND b.job_id = '$job_id'"
        . " LEFT JOIN ".XOCP_PREFIX."jobs c ON c.job_id = '$job_id'"
        . " LEFT JOIN ".XOCP_PREFIX."orgs d ON d.org_id = c.org_id"
        . " LEFT JOIN ".XOCP_PREFIX."persons e ON e.person_id = a.person_id"
        . " WHERE a.employee_id = '$employee_id'";
   $result = $db->query($sql);
   list($job_nm,$job_abbr,$org_nm,$org_abbr,$nip,$employee_nm,$person_id)=$db->fetchRow($result);
      
   $sql = "SELECT activity_nm,activity_start_dttm,activity_stop_dttm,kpd,experience"
        . " FROM ".XOCP_PREFIX."idp_project_activities"
        . " WHERE request_id = '$request_id'"
        . " AND project_id = '$project_id'";
   
   $sql = "SELECT start_dttm,due_dttm,project_nm,kpo,cost_estimate,"
        . "report_submit_dttm,report_section_mgr_job_id,report_division_mgr_job_id,report_section_mgr_approve_dttm,report_division_mgr_approve_dttm,"
        . "report_section_mgr_emp_id,report_division_mgr_emp_id,report_status_cd,report_return_note,"
        . "report_cost,achievement"
        . " FROM ".XOCP_PREFIX."idp_project"
        . " WHERE request_id = '$request_id'"
        . " AND project_id = '$project_id'";
   
   $result = $db->query($sql);
   if($db->getRowsNum($result)==1) {
      list($plan_start_dttm,$plan_stop_dttm,$project_nm,$kpo,$cost_estimate,
           $submit_dttm,$section_mgr_job_id,$division_mgr_job_id,$section_mgr_approved_dttm,$division_mgr_approved_dttm,
           $section_mgr_emp_id,$division_mgr_emp_id,$status_cd,$return_note,
           $report_cost,$achievement)=$db->fetchRow($result);
      
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
      
      $kpo = htmlentities($kpo,ENT_QUOTES);
      $project_nm = htmlentities($project_nm,ENT_QUOTES);
      
      if($status_cd=="prepared") {
         if(1) {
            $section_mgr_job_id = $first_assessor_job_id; ///_getSectionManagerJobID($job_id);
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
            $sql = "UPDATE ".XOCP_PREFIX."idp_project SET report_section_mgr_job_id = '$section_mgr_job_id', report_section_mgr_emp_id = '$section_mgr_emp_id' WHERE request_id = '$request_id' AND project_id = '$project_id'";
            $db->query($sql);
         }
         if(1) {
            $division_mgr_job_id = $next_assessor_job_id; ///_getDivisionManagerJobID($job_id);
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
            $sql = "UPDATE ".XOCP_PREFIX."idp_project SET report_division_mgr_job_id = '$division_mgr_job_id', report_division_mgr_emp_id = '$division_mgr_emp_id' WHERE request_id = '$request_id' AND project_id = '$project_id'";
            $db->query($sql);
         }
      } else {
      
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
      }
   }
   
   $sql = "SELECT b.competency_nm,b.competency_abbr FROM ".XOCP_PREFIX."idp_request_actionplan a"
        . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
        . " WHERE a.request_id = '$request_id' AND a.project_id = '$project_id'";
   $result = $db->query($sql);
   $compdiv = "<table><tbody><tr><td colspan='2' style='font-weight:bold;'>Competency Related : </td></tr>";
   if($db->getRowsNum($result)>0) {
      while(list($competency_nm,$competency_abbr)=$db->fetchRow($result)) {
         $compdiv .= "<tr><td>$competency_abbr</td><td>$competency_nm</td></tr>";
      }
   }
   $compdiv .= "</tbody></table>";
   
   
   $sql = "SELECT idea_id,idea_txt,start_dttm,stop_dttm"
        . " FROM ".XOCP_PREFIX."idp_report_PROJECT_ideas"
        . " WHERE request_id = '$request_id'"
        . " AND project_id = '$project_id'"
        . " ORDER BY idea_id";
   $result = $db->query($sql);
   $ideas = "";
   if($db->getRowsNum($result)>0) {
      $display_idea = "display:none;";
      while(list($idea_id,$idea_txt,$idea_start_dttm,$idea_stop_dttm)=$db->fetchRow($result)) {
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
   
   $sql = "SELECT activity_id,activity_nm,activity_start_dttm,activity_stop_dttm,kpd,experience,status_cd"
        . " FROM ".XOCP_PREFIX."idp_project_activities"
        . " WHERE request_id = '$request_id'"
        . " AND project_id = '$project_id'"
        . " AND status_cd IN ('normal','finish')"
        . " ORDER BY activity_stop_dttm";
   $result = $db->query($sql);
   $activities = "";
   $kpd_id = "";
   if($db->getRowsNum($result)>0) {
      $no = 1;
      $curr_dttm = getSQLDate();
      while(list($activity_id,$activity_nm,$activity_start_dttm,$activity_stop_dttm,$kpd,$experience,$actrep_status_cd)=$db->fetchRow($result)) {
         $kpd_idx = "inp_ex_${activity_id}";
         $kpd_id .= ($actrep_status_cd=="normal"?",${kpd_idx}":"");
         if($preview==0&&$status_cd=="prepared") {
            $activities .= "<thead><tr>"
                         . "<td colspan='2'>No. $no</td>"
                         . "</tr></thead>";
            $activities .= "<tbody id='tbodyactivity_${activity_id}'>"
                         . "<tr>"
                         . "<td style='text-align:right;'>Activity : </td>"
                         . "<td>$activity_nm</td>"
                         . "</tr>"
                         . "<tr>"
                         . "<td style='text-align:right;'>KPD : </td>"
                         . "<td>$kpd</td>"
                         . "</tr>"
                         . "<tr>"
                         . "<td style='text-align:right;'>Time Frame : </td>"
                         . "<td>".sql2ind($activity_start_dttm,"date")." - ".sql2ind($activity_stop_dttm,"date")." ".($activity_stop_dttm<=$curr_dttm?"<span style='color:red;'>[passed]</span>":($activity_start_dttm<=$curr_dttm?"[in progress]":"[not yet started]"))."</td>"
                         . "</tr>"
                         . "<tr>"
                         . "<td style='text-align:right;vertical-align:top;border-bottom:0px;'>Learning Experience : </td>"
                         . "<td style='border-bottom:0px;'><div style='width:400px;".($actrep_status_cd=="normal"?"height:300px;":"margin-top:-10px;")."' id='${kpd_idx}'>$experience</div></td>"
                         . "</tr>"
                         . "<tr>"
                         . "<td>&nbsp;</td>"
                         . "<td style='text-align:left;' id='tdbtn_${activity_id}'>"
                         . ($actrep_status_cd=="normal"? "<input class='' type='button' value='Save Draft' onclick='save_activity(\"$activity_id\",this,event);'/>&nbsp;&nbsp;"
                         . "<input type='button' value='Finish' onclick='finish_activity(\"$activity_id\",this,event);'/>&nbsp;&nbsp;"
                         . "<span id='activity_progress_${activity_id}'</span>" : "")
                         . "</td>"
                         . "</tr>"
                         . "</tbody>";
         } else {
            $activities .= "<thead><tr>"
                         . "<td colspan='2'>No. $no</td>"
                         . "</tr></thead>";
            $activities .= "<tbody id='tbodyactivity_${activity_id}'>"
                         . "<tr>"
                         . "<td style='text-align:right;'>Activity : </td>"
                         . "<td>$activity_nm</td>"
                         . "</tr>"
                         . "<tr>"
                         . "<td style='text-align:right;'>KPD : </td>"
                         . "<td>$kpd</td>"
                         . "</tr>"
                         . "<tr>"
                         . "<td style='text-align:right;'>Time Frame : </td>"
                         . "<td>".sql2ind($activity_start_dttm,"date")." - ".sql2ind($activity_stop_dttm,"date")." ".($activity_stop_dttm<=$curr_dttm?"<span style='color:red;'>[passed]</span>":($activity_start_dttm<=$curr_dttm?"[in progress]":"[not yet started]"))."</td>"
                         . "</tr>"
                         . "<tr>"
                         . "<td style='text-align:right;vertical-align:top;border-bottom:0px;'>Learning Experience : </td>"
                         . "<td style='border-bottom:0px;'><div style='width:400px;margin-top:-10px;' id='${kpd_idx}'>$experience</div></td>"
                         . "</tr>"
                         . "<tr>"
                         . "<td>&nbsp;</td>"
                         . "<td style='text-align:left;'>"
                         . "</td>"
                         . "</tr>"
                         . "</tbody>";
         }
         $no++;
      }
      $kpd_id = substr($kpd_id,1);
   }
   
   $form = "<div style='text-align:center;font-size:1.2em;font-weight:bold;'>PROJECT ASSIGNMENT</div>"
         . "<div style='text-align:center;'>EVALUATION REPORT</div>"
         . "<div style='margin-bottom:10px;margin-top:10px;padding:5px;border:1px solid #bbb;-moz-border-radius:5px;'>"
            . "<table style='width:100%;'><tbody>"
            . "<tr><td style='font-weight:bold;text-align:right;'>Project Name : </td><td>$project_nm</td><td style='font-weight:bold;text-align:right;'>Start Date : </td><td>".sql2ind($plan_start_dttm,"date")."</td></tr>"
            . "<tr><td style='font-weight:bold;text-align:right;'>Objective : </td><td>$kpo</td><td style='font-weight:bold;text-align:right;'>Stop Date : </td><td>".sql2ind($plan_stop_dttm,"date")."</td></tr>"
            . "<tr><td style='font-weight:bold;text-align:right;'>Project Leader : </td><td>$employee_nm</td><td style='font-weight:bold;text-align:right;'>NIP : </td><td>$nip</td></tr>"
            . "<tr><td style='font-weight:bold;text-align:right;'>Section/Division : </td><td>$org_nm ($org_abbr)</td><td style='font-weight:bold;text-align:right;'>Job Title : </td><td>$job_nm ($job_abbr)</td></tr>"
            . "<tr><td style='font-weight:bold;text-align:right;'>Cost Estimate IDR : </td><td colspan='3'>".toMoneyShort($cost_estimate)."</td></tr>"
            . "<tr><td style='font-weight:bold;text-align:right;'>Cost Real IDR : </td><td colspan='3'>".($preview==0&&$status_cd=="prepared"?"<input type='text' onkeydown='_chcost(\"report_cost\",\"spreport_cost\",this,event);' id='report_cost' value='$report_cost' name='report_cost'/> ":"")."<span id='spreport_cost'>".toMoneyShort($report_cost)."</span></td></tr>"
            . "</tbody></table>"
         . "</div>"
         
         . "<div style='margin-bottom:10px;margin-top:10px;padding:5px;border:1px solid #bbb;-moz-border-radius:5px;'>"
            . $compdiv
         . "</div>"
         
         . "<div style='padding:10px;'>"
            . "<div style='font-weight:bold;'>A. Project Stages / Activities:</div>"
            . "<div style='padding:10px;padding-left:15px;' id='dvactivities'>"
               . "<table class='xxlist' id='frmactivities' style='width:100%;'><colgroup>"
               . "<col/>"
               . "<col/>"
               . "<col/>"
               . "<col/>"
               //. "<col width='210'/>"
               . "</colgroup>"
               . $activities
               . "</table>"
            . "</div>"
         . "</div>"
         
         . "<div style='padding:10px;'>"
            . "<div style='font-weight:bold;'>B. Project Result:</div>"
            . "<div style='padding:10px;padding-left:15px;' id='dvactivities'>"
            . "<table class='xxlist' style='width:100%;'><colgroup><col width='100'/><col/></colgroup>"
            . "<tbody>"
            . "<tr><td style='vertical-align:middle;text-align:right;color:#000;background-color:#eee;border-right:1px solid #bbb;'>KPO : </td><td>$kpo</td></tr>"
            . "<tr><td style='vertical-align:middle;text-align:right;color:#000;background-color:#eee;border-right:1px solid #bbb;'>Achievement : </td><td>"
            . ($preview==0&&$status_cd=="prepared"?"<input style='width:300px;' type='text' id='achievement' name='achievement' value='$achievement'/>":"$achievement")
            . "</td></tr>"
            . "</tbody>"
            . "</table>"
            . "</div>"
         . "</div>"
         
         . "<div style='padding:10px;'>"
            . "<div style='font-weight:bold;'>C. Next Improvement:</div>"
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
         
         . ($status_cd=="prepared"?"<div style='padding:5px;text-align:right;background-color:#eee;border:1px solid #bbb;'><span id='progress'></span>&nbsp;"
         . ($preview==1?"<input ".($self_employee_idx==$employee_id?"":"style='display:none;'")." type='button' value='Edit' onclick='cancel_preview_report(\"$request_id\",\"$project_id\");'/>":"<input class='xaction' type='button' value='Save Draft' onclick='save_report(this,event);'/>"
         . "&nbsp;<input type='button' value='Preview' onclick='preview_report(\"$request_id\",\"$project_id\");'/>")
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
            . ($status_cd=="approval1"&&$self_job_id==$section_mgr_job_id?"<input type='button' value='Approve' onclick='confirm_approval1(this,event);'/>&nbsp;<input type='button' value='Not Approve' onclick='section_mgr_return_report(\"$request_id\",\"$project_id\");'/>":"")
            //. ($status_cd=="approval1"&&$self_job_id==$section_mgr_job_id?"&nbsp;<input type='button' value='Return' onclick='confirm_return1(this,event);'/>":"")
            . ($status_cd=="approval1"&&$self_job_id!=$section_mgr_job_id?"Waiting for approval":"")
            . ($status_cd=="approval2"?"Approved at:<br/>".sql2ind($section_mgr_approved_dttm,"date"):"")
            . ($status_cd=="completed"?"Approved at:<br/>".sql2ind($section_mgr_approved_dttm,"date"):"")
            . "</td>"
            
            . ($doubleapproval==1?""
            . "<td style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;padding:10px;'>"
            . ($status_cd=="prepared"?"-":"")
            . ($status_cd=="approval2"&&$self_job_id==$division_mgr_job_id?"<input type='button' value='Approve' onclick='confirm_approval2(this,event);'/>&nbsp;<input type='button' value='Not Approve' onclick='division_mgr_return_report(\"$request_id\",\"$project_id\");'/>":"")
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
   require_once(XOCP_DOC_ROOT."/modules/hris/include/idp/ajax/ajax_method_PROJECT.php");
   $ajax = new _idp_class_method_PROJECT_ajax("mPROJECT");
   $js = $ajax->getJs()."<script type='text/javascript'><!--
      
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
         mPROJECT_app_deleteIdea('$request_id','$project_id',idea_id,null);
      }
      
      function new_idea() {
         $('ideaprogress').innerHTML = '';
         $('ideaprogress').appendChild(progress_span());
         mPROJECT_app_newIdea('$request_id','$project_id',function(_data) {
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
      
      function do_approval2(request_id,project_id) {
         $('confirmbtn').innerHTML = '';
         $('confirmbtn').appendChild(progress_span(' ... approve report'));
         mPROJECT_app_approval2Report('$request_id','$project_id',function(_data) {
            confirmapproval2box.fade();
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?approve_project=y&request_id=${request_id}&project_id=${project_id}&r='+uniqid('r');
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
      
      function do_approval1(request_id,project_id) {
         $('confirmbtn').innerHTML = '';
         $('confirmbtn').appendChild(progress_span(' ... approve report'));
         mPROJECT_app_approval1Report('$request_id','$project_id',function(_data) {
            confirmapproval1box.fade();
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?approve_project=y&request_id=${request_id}&project_id=${project_id}&r='+uniqid('r');
         });
      }
      
      function confirm_division_mgr_return_report(request_id,project_id) {
         var return_note = urlencode($('return_note').value);
         mPROJECT_app_divisionManagerReturnReport(request_id,project_id,return_note,function(_data) {
            var data = recjsarray(_data);
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?approve_project=y&request_id='+data[0]+'&project_id='+data[1]+'&r='+uniqid('r');
         });
      }
      
      var divisionmgrreturnedit = null;
      var divisionmgrreturnbox = null;
      function division_mgr_return_report(request_id,project_id) {
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
                                   + '<input type=\"button\" value=\"Yes (Not Approve)\" onclick=\"confirm_division_mgr_return_report(\\''+request_id+'\\',\\''+project_id+'\\');\"/>&nbsp;'
                                   + '<input type=\"button\" value=\"No\" onclick=\"divisionmgrreturnbox.fade();\"/>'
                                   + '</div>'
                                   + '</div>';
         setTimeout('$(\"return_note\").focus();',200);
      }
      
      function confirm_section_mgr_return_report(request_id,project_id) {
         var return_note = urlencode($('return_note').value);
         mPROJECT_app_sectionManagerReturnReport(request_id,project_id,return_note,function(_data) {
            var data = recjsarray(_data);
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?approve_project=y&request_id='+data[0]+'&project_id='+data[1]+'&r='+uniqid('r');
         });
      }
      
      var sectionmgrreturnedit = null;
      var sectionmgrreturnbox = null;
      function section_mgr_return_report(request_id,project_id) {
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
                                   + '<input type=\"button\" value=\"Yes (Not Approve)\" onclick=\"confirm_section_mgr_return_report(\\''+request_id+'\\',\\''+project_id+'\\');\"/>&nbsp;'
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
      
      function do_submit(request_id,project_id) {
         $('confirmbtn').innerHTML = '';
         $('confirmbtn').appendChild(progress_span(' ... submiting report'));
         mPROJECT_app_submitReport('$request_id','$project_id',function(_data) {
            confirmsubmitbox.fade();
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?reporting_project=y&request_id=${request_id}&project_id=${project_id}&r='+uniqid('r');
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
      
      function preview_report(request_id,project_id) {
         location.href = '".XOCP_SERVER_SUBDIR."/index.php?reporting_project=y&request_id='+request_id+'&project_id='+project_id+'&preview=1';
      }
      
      function cancel_preview_report(request_id,project_id) {
         location.href = '".XOCP_SERVER_SUBDIR."/index.php?reporting_project=y&request_id='+request_id+'&project_id='+project_id+'&preview=0';
      }
      
      function do_finish_report(request_id,project_id,activity_id) {
         $('confirmmsg').innerHTML = '';
         $('confirmmsg').appendChild(progress_span());
         mPROJECT_app_finishActivity('$request_id','$project_id',activity_id,function(_data) {
            var data = recjsarray(_data);
            var id = 'inp_ex_'+data[0];
            if (tinyMCE.getInstanceById(id)) {
               tinyMCE.execCommand('mceFocus', false, id);                    
               tinyMCE.execCommand('mceRemoveControl', false, id);
            }
            $(id).style.height = null;
                    
            $('inp_ex_'+data[0]).innerHTML = data[1];
            $('tdbtn_'+data[0]).innerHTML = '';
            confirmfinishactbox.fade();
         });
      }
      
      var confirmfinishact = null;
      var confirmfinishactbox = null;
      function finish_activity(activity_id,d,e) {
         var actret = urlencode(tinyMCE.get('inp_ex_'+activity_id).getContent());
         $('activity_progress_'+activity_id).innerHTML = '';
         $('activity_progress_'+activity_id).appendChild(progress_span(' saving ... '));
         mPROJECT_app_beforeFinishActivity('$request_id','$project_id',activity_id,actret,function(_data) {
            setTimeout(\"$('activity_progress_\"+_data+\"').innerHTML = '';\",1000);
            confirmfinishact = _dce('div');
            confirmfinishact.setAttribute('id','confirmfinishact');
            confirmfinishact = document.body.appendChild(confirmfinishact);
            confirmfinishact.sub = confirmfinishact.appendChild(_dce('div'));
            confirmfinishact.sub.setAttribute('id','innerconfirmfinishact');
            confirmfinishactbox = new GlassBox();
            $('innerconfirmfinishact').innerHTML = '<div style=\"height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;\">Finish Activity\\'s Report Confirmation</div>'
                                              + '<div id=\"confirmmsg\" style=\"padding:20px;text-align:center;\">Are you going to finish this activity report?</div>'
                                              + '<div id=\"confirmbtn\" style=\"background-color:#eee;padding:10px;text-align:center;\">'
                                                + '<input type=\"button\" value=\"Yes (Finish)\" onclick=\"do_finish_report(\\'$request_id\\',\\'$project_id\\',\\''+activity_id+'\\');\"/>&nbsp;&nbsp;'
                                                + '<input type=\"button\" value=\"No\" onclick=\"confirmfinishactbox.fade();\"/>'
                                              + '</div>';
            
            
            confirmfinishactbox = new GlassBox();
            confirmfinishactbox.init('confirmfinishact','500px','165px','hidden','default',false,false);
            confirmfinishactbox.lbo(false,0.3);
            confirmfinishactbox.appear();
         });
      }
      
      function save_activity(activity_id,d,e) {
         var actret = urlencode(tinyMCE.get('inp_ex_'+activity_id).getContent());
         $('activity_progress_'+activity_id).innerHTML = '';
         $('activity_progress_'+activity_id).appendChild(progress_span(' saving ... '));
         mPROJECT_app_saveActivityReport('$request_id','$project_id',activity_id,actret,function(_data) {
            setTimeout(\"$('activity_progress_\"+_data+\"').innerHTML = '';\",1000);
         });
      }
      
      function save_report(d,e) {
         var achievement = $('achievement').value;
         var kpdret = '';
         var tmc = '$kpd_id';
         if(trim(tmc)!='') {
            var tmca = tmc.split(',');
            if(tmca.length>0) {
               for(var i=0;i<tmca.length;i++) {
                  if(tinyMCE.get(tmca[i])) {
                     var expr = tinyMCE.get(tmca[i]).getContent();
                     kpdret += '@@' + tmca[i] + '^^' + expr;
                  }
               }
            }
            kpdret = urlencode(kpdret.substring(2));
         }
         var idea = _parseForm('dvidea');
         $('progress').innerHTML = '';
         $('progress').appendChild(progress_span(' saving ... '));
         var report_cost = $('report_cost').value;
         mPROJECT_app_saveReport('$request_id','$project_id',report_cost,idea,kpdret,achievement,function(_data) {
            setTimeout(\"$('progress').innerHTML = '';\",1000);
         });
      }
      
      function init_my_tiny() {
         if(1) {
            tinyMCE.init({
               mode : 'exact',
               elements : '$kpd_id',
               theme : 'advanced',
               theme_advanced_buttons1 : 'bold,italic,underline,separator,bullist,numlist,outdent,indent,separator,justifyleft,justifycenter,justifyright,separator,forecolor,cleanup,separator,image,table,code,cut,copy,pasteword',
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



function PROJECT_idp_m_saveAction($request_id,$actionplan_id,$ret) {
   $db=&Database::getInstance();
   $vars = _parseForm($ret);
   $sql = "UPDATE ".XOCP_PREFIX."idp_request_actionplan SET "
        . "project_id = '".$vars["project_id"]."'"
        . " WHERE request_id = '$request_id'"
        . " AND actionplan_id = '$actionplan_id'";
   $db->query($sql);

}

////////////////////////// Javascript ////////////////////////////////

if(isset($_GET["js"])&&$_GET["js"]==1) {

$str = <<<EOD

function _idpm_save(request_id,actionplan_id) {
   ret = _parseForm('method_trn_in_'+request_id+'_'+actionplan_id);
   save_ap('PROJECT',request_id,actionplan_id,ret);
}

function _idpm_selectproject(project_id,cost_estimate,d,e) {
   $('spcurproj').innerHTML = d.innerHTML;
   $('projectcost').innerHTML = thSep(cost_estimate);
   $('project_id').value = project_id;
   $('dvchproject').style.display = 'none';
}

function _idpm_chgproject(d,e) {
   if($('dvchproject').style.display == '') {
      $('dvchproject').style.display = 'none';
   } else {
      $('dvchproject').style.display = '';
      $('dvchproject').style.top = parseInt(oY(d)+d.offsetHeight)+'px';
      $('dvchproject').style.left = oX(d)+'px';
   }
}

EOD;

header("Content-type: text/javascript; charset=utf-8");

echo $str;

}

?>