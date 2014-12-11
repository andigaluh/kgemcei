<?php
//--------------------------------------------------------------------//
// Filename : modules/pms/pmsapreport.php                             //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2010-09-22                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('SMS_APREPORT_DEFINED') ) {
   define('SMS_APREPORT_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
include_once(XOCP_DOC_ROOT."/modules/sms/class/ajax_myap.php");
include_once(XOCP_DOC_ROOT."/modules/pms/pmsmyactionplan.php");
require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
include_once(XOCP_DOC_ROOT."/modules/sms/class/selectsmsmatrixjam.php");

class _sms_ActionPlanReport extends XocpBlock {
   var $catchvar = _SMS_CATCH_VAR;
   var $blockID = _SMS_APREPORT_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _SMS_APREPORT_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _sms_ActionPlanReport($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */

   }
   
   function recurseParentOrg($pms_objective_id) {
      $db=&Database::getInstance();
      $pms_org_id = 0;
      $sql = "SELECT pms_org_id,pms_parent_objective_id FROM pms_objective WHERE pms_objective_id = '$pms_objective_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($pms_org_id,$pms_parent_objective_id)=$db->fetchRow($result);
         if($pms_parent_objective_id>0) {
            return $this->recurseParentOrg($pms_parent_objective_id);
         }
      }
      $sql = "SELECT parent_id FROM ".XOCP_PREFIX."orgs WHERE org_id = '$pms_org_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($parent_id)=$db->fetchRow($result);
         return $parent_id; //// return parent_id, hopefully corporate org_id
      }
      return -1;
   }
   
 function report($employee_id,$force_month_id) {
      $psid = $_SESSION["pms_psid"];
      global $xocp_vars;
      $db=&Database::getInstance();
      $user_id = getUserID();
      $month_id = $_GET['month_id'];
      
      $report_mode=TRUE;
      
      if($force_month_id<1) {
         $force_month_id = 1;
      }
      if($force_month_id>12) {
         $force_month_id = 12;
      }
      
      $_SESSION["pms_month"] = $force_month_id;
      
      list($job_id,
           $employee_idx,
           $job_nm,
           $employee_nm,
           $nip,
           $gender,
           $jobstart,
           $entrance_dttm,
           $jobage,
           $job_summary,
           $person_id,
           $employee_user_id,
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
      $ret = "<div style='max-width:900px;'><div style='border:1px solid #bbb;background-color:#eee;padding:5px;text-align:right;'>[<a href='".XOCP_SERVER_SUBDIR."/index.php'>Back</a>]</div></div>";
      $ret .= "<br/><table style='margin-left:20px;'><tr><td style='padding:4px;border:1px solid #bbb;-moz-box-shadow:2px 2px 5px #333;'><img src='".XOCP_SERVER_SUBDIR."/modules/hris/thumb.php?pid=${person_id}' height='100'/></td>"
            . "<td style='vertical-align:top;padding-left:10px;'>"
            
            . "<table style='font-weight:bold;margin-left:0px;font-size:1.1em;'><colgroup><col width='120'/><col/></colgroup><tbody>"
            . "<tr><td>Job Title</td><td>: $job_nm ($job_abbr)</td></tr>"
            . "<tr><td>Section/Division</td><td>: $org_nm ($org_abbr)</td></tr>"
            . "<tr><td>Incumbent</td><td>: $employee_nm</td></tr>"
            . "<tr><td>NIP</td><td>: $nip</td></tr>"
            . "</tbody></table></td></tr></table><div style='padding:10px;'>";
      
    /*   $sql = "SELECT DISTINCT(approval_st) FROM pms_pic_action WHERE psid = '$psid' AND employee_id = '$employee_id'";
      $result = $db->query($sql);
      list($jam_status_cd)=$db->fetchRow($result);
      if($employee_id!=$self_employee_id) {
         if($jam_status_cd=="new") {
            return $ret. "<br/><br/>Action Plan is still prepared.";
         }
      } */
      
      //$ret .= _pms_MyActionPlan::pmsmyactionplan($employee_id,TRUE); /// TRUE for reporting mode
      
      
      
      ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
      ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
      
      
      
      
      
      $ajax = new _sms_class_MyApAjax("orgjx");
      
      
      $tooltip = "<div id='all_ap_tooltip' style='display:none;'>";
      

      $ret .= "<divs>";
      
      $ret .= $return_notes;
      $ret .= $report_return_notes;
      
      $ret .= "<div style='text-align:center;color:black;font-size:1.5em;width:900px;padding:10px;'>"
            . $xocp_vars["month_year"][$force_month_id]
            . "</div>";
      
      $ret .= "<table style='width:900px;border-spacing:0px;'><colgroup><col width='50%'/><col width='50%'/></colgroup><tbody>"
            . "<tr>"
            . "<td style='text-align:center;border:1px solid #bbb;padding:5px;'>"
            . "<a href='".XOCP_SERVER_SUBDIR."/index.php?employee_id=$employee_id&goto=y&month_id=".($_SESSION["pms_month"]-1)."'><img src='".XOCP_SERVER_SUBDIR."/images/prev.gif'/><img src='".XOCP_SERVER_SUBDIR."/images/prev.gif'/></a>&nbsp;&nbsp;"
            . "<a href='".XOCP_SERVER_SUBDIR."/index.php?employee_id=$employee_id&goto=y&month_id=".($_SESSION["pms_month"]-1)."'>Previous Month</a>"
            . "</td>"
            . "<td style='text-align:center;border:1px solid #bbb;border-left:0px;padding:5px;'>"
            . "<a href='".XOCP_SERVER_SUBDIR."/index.php?employee_id=$employee_id&goto=y&month_id=".($_SESSION["pms_month"]+1)."'>Next Month</a>"
            . "&nbsp;&nbsp;<a href='".XOCP_SERVER_SUBDIR."/index.php?employee_id=$employee_id&goto=y&month_id=".($_SESSION["pms_month"]+1)."'><img src='".XOCP_SERVER_SUBDIR."/images/next.gif'/><img src='".XOCP_SERVER_SUBDIR."/images/next.gif'/></a>"
            . "</td>"
            . "</tr>"
            . "</tbody></table>";
      
      $ret .= "<table style='width:900px;table-layout:fixed;' class='xxlist'>"
            . "<colgroup>"
            .  "<col width='350'/>"
            .  "<col width='150'/>"
            .  "<col width='150'/>"
            .  "<col width='*'/>"
            . "</colgroup>";
      
      $ret .= "<thead>"
            . "<tr>"
               //. "<td style='border-right:1px solid #bbb;text-align:center;' rowspan='2'>No.</td>"
               . "<td style='border-right:1px solid #bbb;text-align:center;' colspan='1' rowspan='1'>Action Plan</td>"
               . "<td rowspan='1' ".($report_mode==TRUE?"":"colspan='12'")." style='border-right:1px solid #bbb;text-align:center;'>Achievement</td>"
			   . "<td style='text-align:center;border-right:1px solid #bbb;'>Target Month</td>"
               . "<td style='text-align:center;border-right:0px solid #bbb;' rowspan='1'>".($report_mode==TRUE?"Target":"Monthly Target")."</td>"
            . "</tr>"
      
            . "</thead>";
      
	$sqlperid = "SELECT person_id FROM hris_users WHERE user_id = ".getUserid()."";
	$resultperid = $db->query($sqlperid);
	list($person_id)=$db->fetchRow($resultperid);
	
		$sqlobj = "SELECT DISTINCT a.id,a.objective_code, a.objective_title,a.objective_weight
		FROM sms_objective a
		LEFT JOIN sms_session b ON ( a.psid = b.id )
		LEFT JOIN sms_objective_ksf d ON ( a.id = d.id_objective )
		LEFT JOIN sms_objective_team c ON ( c.id_objective_ksf = d.id )
		WHERE a.psid = $psid
		AND c.id_pic =$person_id AND a.status = 'normal'";
	$resultobj = $db->query($sqlobj);
	$nom = 1;

	if($db->getRowsNum($resultobj)>0) {
		$nm = $nm++;
		while(list($objid,$objective_code,$objective_title,$objective_weight)=$db->fetchRow($resultobj)){
		
		$sqlksf = "SELECT a.id,a.ksf_code,a.ksf_title,a.ksf_lower_perform,a.ksf_need_improvement,a.ksf_target,a.ksf_req,a.ksf_far_req  FROM sms_objective_ksf a JOIN sms_objective_team b ON(a.id =  b.id_objective_ksf) WHERE b.id_pic = $person_id AND a.id_objective = $objid ";
		$resultksf = $db->query($sqlksf);
		$numrow = $db->getRowsNum($resultksf)+2;		
		$num = 0;
		$ret .= "<tbody>"
					. "<tr>";
		
		
	//ACTION PLAN ADD
	$sqlksfin = "SELECT a.id,a.ksf_code,a.ksf_title,a.ksf_lower_perform,a.ksf_need_improvement,a.ksf_target,a.ksf_req,a.ksf_far_req  FROM sms_objective_ksf a JOIN sms_objective_team b ON(a.id =  b.id_objective_ksf) WHERE b.id_pic = $person_id AND a.id_objective = $objid";
		$resultksfin = $db->query($sqlksfin);
		$numrow = $db->getRowsNum($resultksfin)+2;		
		$no = 0;
	
		while(list($id_ksf,$ksf_code,$ksf_title,$ksf_lowper,$ksf_needimp,$ksf_target,$ksf_req,$ksf_far_req)=$db->fetchRow($resultksfin)){
			
			$ret .= "<tr><td  colspan='4' style='text-align:left;font-size: 14px;background-color: #CCCBED;' id='tdaptext_new}' >$ksf_title </td></tr>";
		
		
		//PIC Action Plan
			$sqlap = "SELECT  id,sms_objective_id,employee_id,actionplan_id,actionplan_text,target_text,final_result_text,current_achievement,current_kpi_achievement,target_achievement,final_achievement,final_kpi_achievement  FROM sms_pic_action WHERE psid = '$psid' AND employee_id = '$person_id' AND sms_objective_id = '$objid' AND actionplan_id  = '$id_ksf' AND month_id = '$month_id' GROUP BY actionplan_text";
			$resultap = $db->query($sqlap);
			
			if($db->getRowsNum($resultap)==0)
			{
					$ret .= "<tr><td colspan='4' id='tdaptext_${idap}' style='margin:8px;border-right:1px solid #bbb;vertical-align:middle; text-align: center; overflow:hidden;padding:0px;'>No Actionplan Detail</td></tr>";
			}
			else{
			while(list($idap,$sms_objective_id,$employee_id,$actionplan_id,$actionplan_text,$target_text,$final_result_text,$current_achievement,$current_kpi_achievement,$target_achievement,$final_achievement,$final_kpi_achievement)=$db->fetchRow($resultap)){
			 
			 
					$no++;
					$sqlaps = "SELECT  id,sms_objective_id,employee_id,actionplan_id,month_id,actionplan_text,target_text,final_result_text,current_achievement,current_kpi_achievement,target_achievement,final_achievement,final_kpi_achievement  FROM sms_pic_action WHERE psid = '$psid' AND employee_id = '$person_id' AND sms_objective_id = '$objid' AND actionplan_id  = '$id_ksf' AND id = '$idap' AND month_id = '$month_id' GROUP BY actionplan_text";
					$resultaps = $db->query($sqlaps);
					list($idap,$sms_objective_id,$employee_id,$actionplan_id,$month_id,$actionplan_text,$target_text,$final_result_text,$current_achievement,$current_kpi_achievement,$target_achievement,$final_achievement,$final_kpi_achievement)=$db->fetchRow($resultaps);
			   
			     
					$ret .= "<tr><td colspan='1' id='tdaptext_${idap}' style='border-right:1px solid #bbb;vertical-align:top;overflow:hidden;padding:0px;'>";
						$ret .= "<div style='margin: 5px;'>";
						$ret .= "<span> - $actionplan_text</span>";
						$ret .= "</div></td>";
						$ret .= "<td style='padding:0px;border-right:1px solid #ddd;vertical-align:middle; text-align: center;'><span class='xlnk' onclick='edit_target_text(\"$idap\",\"$psid\",\"$person_id\",\"$objid\",\"$id_ksf\",\"$month_id\",1,this,event);' style='text-decoration:none;' '>".toMoney($current_achievement)."  </span> %</td>";
						$ret .= "<td style='padding:0px;border-right:1px solid #ddd;vertical-align:middle; text-align: center;'><span class='xlnk' onclick='edit_target_text(\"$idap\",\"$psid\",\"$person_id\",\"$objid\",\"$id_ksf\",\"$month_id\",2,this,event);' style='text-decoration:none;' '>$target_achievement</span></td>";
						$ret .= "<td style='padding:0px;border-right:1px solid #ddd;vertical-align:middle; text-align: center;'><span class='xlnk' onclick='edit_target_text(\"$idap\",\"$psid\",\"$person_id\",\"$objid\",\"$id_ksf\",\"$month_id\",3,this,event);' style='text-decoration:none;' '>$target_text</span></td>";
					$ret .= "</tr>";
						   
			}
			
		
			 $ret .= "<div onmousemove='show_ap_tooltip(\"$apid\",\"$actionplan_id\",this,event);' onmouseover='mouseover_aptext(\"$no\",\"$sms_objective_id\",\"$actionplan_id\",this,event);' onmouseout='mouseout_aptext(\"$no\",\"$sms_objective_id\",\"$actionplan_id\",this,event);' class='aptgtext' id='dvtg_${sms_objective_id}_${actionplan_id}'>"
						     . "</div>";
		
		//
		}
	}

	//
		$ret .= "</tr>";
				
		}
	}
		else
	{
		$ret .= "<tbody>"
					. "<tr>"
						. "<td colspan='16' style='text-align:center;font-style:italic;'>Action plan not found</td>"
					. "</tr>";
				
	}
	$ret .=  "</tbody>"
		. "</table>";
      
    
	$form = "<div style='width:900px;text-align:right;padding:10px;margin-top:10px;margin-bottom:100px;'>"
					. "<table align='center' style='border-top:2px solid #777;border-left:2px solid #777;border-spacing:0px;'>"
						. "<colgroup>"
							.	"<col width='200'>"
							. "<col width='200'>"
						. "</colgroup>"
						. "<tbody>"
							. "<tr>"
								. "<td style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;'>Submited by,</td>"
								. "<td style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;'>Approved by,</td>"
							. "</tr>"
						. "<tr>"
							. "<td style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'></td>"
							. "<td style='text-align:center;border-bottom:1px solid #bbb;border-right:2px solid #777;'></td>"
						. "</tr>"
						. "<tr>"
							. "<td style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;padding:10px;' id='submited_by_button'>"
							. "<input type='button' value='Submit' onclick='confirm_submit(this,event);'></td>"
							. "<td style='text-align:center;border-bottom:2px solid #777;border-right:2px solid #777;padding:10px;'>-</td>"
						. "</tr>"
					."</tbody>"
				."</table>"
			. "</div>";
			
      $ret .="</div>";
 
      
      
      
      $js = $ajax->getJs()."<script type='text/javascript' src='".XOCP_SERVER_SUBDIR."/include/calendar.js'></script><script type='text/javascript'>//<![CDATA[
      
      
      //////////////////////////////////////////////////////////////////////////////
      ///TARGET EDIT
	  
	    function save_target_text(idap,psid,person_id,objid,id_ksf,month_id,no) {
         var val = trim($('inp_target_text').value);
         if(dvedittargettext) {
            dvedittargettext.d.innerHTML = val;
         }
         orgjx_app_saveObjTargetText(val,idap,psid,person_id,objid,id_ksf,month_id,no,null);
      }
      
      function kp_target_text(d,e) {
         var k = getkeyc(e);
         if(d.chgt) {
            d.chgt.reset();
            d.chgt = null;
         }
         var val = d.value;
         if(k==13) {
            dvedittargettext.d.innerHTML = val;
            save_target_text(dvedittargettext.idap,dvedittargettext.psid,dvedittargettext.person_id,dvedittargettext.objid,dvedittargettext.id_ksf,dvedittargettext.month_id,dvedittargettext.no);
         } else if (k==27) {
            _destroy(dvedittargettext);
            dvedittargettext.d = null;
            dvedittargettext = null;
         } else {
            d.chgt = new ctimer('save_target_text(\"'+dvedittargettext.idap+'\",\"'+dvedittargettext.psid+'\",\"'+dvedittargettext.person_id+'\",\"'+dvedittargettext.objid+'\",\"'+dvedittargettext.id_ksf+'\",\"'+dvedittargettext.month_id+'\",\"'+dvedittargettext.no+'\");',300);
            d.chgt.start();
         }
      }
      
      function close_target_text() {
         document.body.onclick = null;
         _destroy(dvedittargettext);
         dvedittargettext.d = null;
         dvedittargettext = null;
		 window.setTimeout(function(){location.reload()},2000)
         return;

      }
	  
	  
	  var dvedittargettext = null;
      function edit_target_text(idap,psid,person_id,objid,id_ksf,month_id,no,d,e) {
         document.body.onclick = null;
         _destroy(dvedittargettext);
         if(dvedittargettext&&d==dvedittargettext.d) {
            dvedittargettext.d = null;
            dvedittargettext = null;
            return;
         }
         d.dv = _dce('div');
         d.dv.setAttribute('style','position:absolute;padding:5px;-moz-border-radius:5px;border:1px solid #777;background-color:#ffffcc;left:0px;-moz-box-shadow:1px 1px 3px #000;');
         var text = d.innerHTML;
         if(text=='"._EMPTY."') {
            text = '';
         }
         d.dv.innerHTML = '<div style=\"text-align:left;padding:2px;\">Edit :<br/>'
                        + '<textarea onkeyup=\"kp_target_text(this,event);\" id=\"inp_target_text\" style=\"-moz-border-radius:3px;width:350px;height:100px;\">'+text+'</textarea>'
                        + '<div style=\"text-align:right;\"><input class=\"sbtn\" type=\"button\" value=\"Close\" onclick=\"close_target_text();\"/></div>'
                        + '</div>';
         d.dv = d.parentNode.appendChild(d.dv);
         d.dv.style.top = parseInt(oY(d.parentNode)+d.parentNode.offsetHeight+5)+'px';
         var x = oX(d);
         if(x>650) {
            d.dv.style.left = parseInt(oX(d.parentNode)-(d.dv.offsetWidth)+(d.parentNode.offsetWidth))+'px';
         } else {
            d.dv.style.left = parseInt(oX(d)-(d.dv.offsetWidth/2)+(d.offsetWidth/2))+'px';
         }
         d.dv.arrow = _dce('img');
         d.dv.arrow.setAttribute('style','position:absolute;left:0px;');
         d.dv.arrow.src = '".XOCP_SERVER_SUBDIR."/images/topmiddle.png';
         d.dv.arrow = d.dv.appendChild(d.dv.arrow);
         d.dv.arrow.style.top = '-12px';
         if(x>650) {
            d.dv.arrow.style.left = parseInt(d.dv.offsetWidth-(d.parentNode.offsetWidth/2)-7)+'px';
         } else {
            d.dv.arrow.style.left = parseInt(d.dv.offsetWidth-(d.dv.offsetWidth/2)-7)+'px';
         }
         $('inp_target_text').focus();
         dvedittargettext = d.dv;
         dvedittargettext.d = d;
         dvedittargettext.idap = idap;
		 dvedittargettext.psid = psid;
		 dvedittargettext.person_id = person_id;
		 dvedittargettext.objid = objid;
		 dvedittargettext.id_ksf = id_ksf;
		 dvedittargettext.month_id = month_id;
		 dvedittargettext.no = no;

         //setTimeout('document.body.onclick = function() { document.body.onclick = null; _destroy(dvedittargettext); };',100);
      }
	  
	  
	  //////
      
	  
	  function do_approval_report(employee_id) {
         $('confirmbtn').innerHTML = '';
         $('confirmbtn').appendChild(progress_span(' ... approve PMS action plan report'));
         orgjx_app_approvalPMSActionPlanReport('$employee_id',function(_data) {
            confirmapapprovalreportbox.fade();
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?month_id=${force_month_id}&goto=y&employee_id=${employee_id}&r='+uniqid('r');
         });
      }
      
      
      function confirm_first_assessor_return_PMSActionPlanReport(employee_id) {
         var return_note = urlencode($('return_note').value);
         orgjx_app_firstAssessorReturnPMSActionPlanReport('$employee_id',return_note,function(_data) {
            var data = recjsarray(_data);
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?goto=y&employee_id=${employee_id}&r='+uniqid('r');
         });
      }
      
      
      var firstassessorreportreturnedit = null;
      var firstassessorreportreturnbox = null;
      function first_assessor_return_PMSActionPlanReport(employee_id) {
         firstassessorreportreturnedit = _dce('div');
         firstassessorreportreturnedit.setAttribute('id','firstassessorreportreturnedit');
         firstassessorreportreturnedit = document.body.appendChild(firstassessorreportreturnedit);
         firstassessorreportreturnedit.sub = firstassessorreportreturnedit.appendChild(_dce('div'));
         firstassessorreportreturnedit.sub.setAttribute('id','innerfirstassessorreportreturnedit');
         firstassessorreportreturnbox = new GlassBox();
         firstassessorreportreturnbox.init('firstassessorreportreturnedit','600px','350px','hidden','default',false,false);
         firstassessorreportreturnbox.lbo(false,0.3);
         firstassessorreportreturnbox.appear();
         
         var xreturn_note = '';
         if($('xreturn_note')) {
            xreturn_note = $('xreturn_note').innerHTML;
         }
         
         $('innerfirstassessorreportreturnedit').innerHTML = '<div style=\"height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;\">Not Approve PMS Action Plan Report Confirmation</div>'
                                   + '<div style=\"background-color:#ffffff;font-weight:bold;font-size:1.1em;height:300px;text-align:center;padding-top:20px;\">'
                                   + 'You do not approve these PMS Actions Plan Report.<br/>You are going to return these PMS Actions Plan Report to the employee.'
                                   + '<br/><br/>With the following notes:<br/>'
                                   + '<textarea id=\"return_note\" style=\"width:400px;height:100px;\">'+xreturn_note+'</textarea>'
                                   + '<br/><br/>Please enter notes, then click Yes to finish. Or click No to cancel.'
                                   + '<br/><br/>'
                                           + '<div id=\"confirmbtn\" style=\"background-color:#eee;padding:10px;text-align:center;\">'
                                   + '<input type=\"button\" value=\"Yes (Not Approve)\" onclick=\"confirm_first_assessor_return_PMSActionPlanReport(\\''+employee_id+'\\');\"/>&nbsp;'
                                   + '<input type=\"button\" value=\"No (Cancel)\" onclick=\"firstassessorreportreturnbox.fade();\"/>'
                                   + '</div>'
                                   + '</div>';
         setTimeout('$(\"return_note\").focus();',200);
      }
      
      
      var confirmapapprovalreport = null;
      var confirmapapprovalreportbox = null;
      function confirm_report_approval(d,e) {
         confirmapapprovalreport = _dce('div');
         confirmapapprovalreport.setAttribute('id','confirmapapprovalreport');
         confirmapapprovalreport = document.body.appendChild(confirmapapprovalreport);
         confirmapapprovalreport.sub = confirmapapprovalreport.appendChild(_dce('div'));
         confirmapapprovalreport.sub.setAttribute('id','innerconfirmapapprovalreport');
         confirmapapprovalreportbox = new GlassBox();
         $('innerconfirmapapprovalreport').innerHTML = '<div style=\"height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;\">Approve PMS Action Plan Report Confirmation</div>'
                                           + '<div id=\"confirmmsg\" style=\"padding:20px;text-align:center;color:#888;\">Are you going to approve this PMS action plan report?</div>'
                                           + '<div id=\"confirmbtn\" style=\"background-color:#eee;padding:10px;text-align:center;\">'
                                             + '<input type=\"button\" value=\"Yes (Approve Report)\" onclick=\"do_approval_report();\"/>&nbsp;&nbsp;'
                                             + '<input type=\"button\" value=\"No (Cancel)\" onclick=\"confirmapapprovalreportbox.fade();\"/>'
                                           + '</div>';
         
         
         confirmapapprovalreportbox = new GlassBox();
         confirmapapprovalreportbox.init('confirmapapprovalreport','500px','165px','hidden','default',false,false);
         confirmapapprovalreportbox.lbo(false,0.3);
         confirmapapprovalreportbox.appear();
      }
      
      //////////////////////////////////////////////////////////////
      
      
      
      function save_ytd() {
         ajax_feedback = _caf;
         var actionplan_group_id = $('actionplan_group_id').value;
         var final_achievement = urlencode($('final_achievement').value);
         var final_kpi_achievement = urlencode($('final_kpi_achievement').value);
         orgjx_app_saveFinalAchievement('$employee_id',apreporteditor.pms_objective_id,apreporteditor.actionplan_id,actionplan_group_id,final_achievement,final_kpi_achievement,function(_data) {
            apreporteditorbox.fade();
            location.reload();
         });
      }
      
      function save_pica() {
         ajax_feedback = _caf;
         var pica_id = $('pica_id').value;
         var root_cause = urlencode($('root_cause').value);
         var improvement = urlencode($('target_text').value);
         var selm = $('pica_month_id');
         if(selm) {
            var pica_month_id = selm.options[selm.selectedIndex].value;
         } else {
            var pica_month_id = 0;
         }
         orgjx_app_savePICA('$employee_id',apreporteditor.pms_objective_id,apreporteditor.actionplan_id,root_cause,improvement,pica_month_id,pica_id,function(_data) {
            //actionplan_updater(_data);
            apreporteditorbox.fade();
            location.reload();
         });
      }
      
      function set_achievement() {
         var achievement = parseFloat($('inp_current_achievement').value);
         var achievement_kpi = parseFloat($('inp_current_kpi_achievement').value);
         var result = urlencode($('inp_final_result').value);
         if(isNaN(achievement)) {
            alert('Action plan achievement must be a number.');
            _dsa($('inp_current_achievement'));
            return;
         } else {
            $('inp_current_achievement').value = achievement;
         }
         if(isNaN(achievement)) {
            alert('KPI achievement must be a number.');
            _dsa($('inp_current_kpi_achievement'));
            return;
         } else {
            $('inp_current_kpi_achievement').value = achievement_kpi;
         }
         orgjx_app_setAchievement('$employee_id',apreporteditor.pms_objective_id,apreporteditor.actionplan_id,achievement,achievement_kpi,result,function(_data) {
            $('apreportmsg').oldHTML = $('apreportmsg').innerHTML;
            $('apreporttitle').oldHTML = $('apreporttitle').innerHTML;
            var data = recjsarray(_data);
            if(data[0]==1) {
               $('confirmbtn').style.display = 'none';
               $('savepica').style.display = '';
               $('apreportmsg').innerHTML = data[1];
               $('apreporttitle').innerHTML = 'Edit PICA';
               $('root_cause').focus();
            } else if(data[0]==2) {
               $('confirmbtn').style.display = 'none';
               $('saveytd').style.display = '';
               $('apreportmsg').innerHTML = data[1];
               $('apreporttitle').innerHTML = 'Set Final Value';
               $('final_achievement').focus();
            } else {
               apreporteditorbox.fade();
               location.reload();
            }
            
         });
      }
      
      function back_pica() {
         $('confirmbtn').style.display = '';
         $('savepica').style.display = 'none';
         $('saveytd').style.display = 'none';
         $('apreportmsg').innerHTML = $('apreportmsg').oldHTML;
         $('apreporttitle').innerHTML = $('apreporttitle').oldHTML;
      
      }
      
      var apreporteditor = null;
      var apreporteditorbox = null;
      function edit_report_ap(pms_objective_id,actionplan_id,no,d,e) {
         apreporteditor = _dce('div');
         apreporteditor.setAttribute('id','apreporteditor');
         apreporteditor = document.body.appendChild(apreporteditor);
         apreporteditor.sub = apreporteditor.appendChild(_dce('div'));
         apreporteditor.sub.setAttribute('id','innerapreporteditor');
         apreporteditorbox = new GlassBox();
         $('innerapreporteditor').innerHTML = '<div id=\"apreporttitle\" style=\"height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;\">Enter Action Plan Achievement</div>'
                                           + '<div id=\"apreportmsg\" style=\"padding:20px;text-align:center;min-height:180px;\"></div>'
                                           + '<div id=\"confirmbtn\" style=\"background-color:#eee;padding:10px;padding-bottom:20px;text-align:center;\">'
                                             + '<input class=\"xaction\" type=\"button\" value=\"Set Achievement\" onclick=\"set_achievement();\"/>&nbsp;&nbsp;'
                                             + '<input type=\"button\" value=\"Cancel\" onclick=\"apreporteditorbox.fade();\"/>'
                                           + '</div>'
                                           + '<div id=\"savepica\" style=\"display:none;background-color:#eee;padding:10px;padding-bottom:20px;text-align:center;\">'
                                             + '<input class=\"xaction\" type=\"button\" value=\"Save PICA\" onclick=\"save_pica();\"/>&nbsp;&nbsp;'
                                             + '<input type=\"button\" value=\"Back\" onclick=\"back_pica();\"/>'
                                           + '</div>'
                                           + '<div id=\"saveytd\" style=\"display:none;background-color:#eee;padding:10px;padding-bottom:20px;text-align:center;\">'
                                             + '<input class=\"xaction\" type=\"button\" value=\"Save Final Value\" onclick=\"save_ytd();\"/>&nbsp;&nbsp;'
                                             + '<input type=\"button\" value=\"Back\" onclick=\"back_pica();\"/>'
                                           + '</div>';
         
         $('apreportmsg').appendChild(progress_span());
         
         apreporteditorbox = new GlassBox();
         apreporteditorbox.init('apreporteditor','700px','340px','hidden','default',false,false);
         apreporteditorbox.lbo(false,0.3);
         apreporteditorbox.appear();
         
         apreporteditor.pms_objective_id = pms_objective_id;
         apreporteditor.actionplan_id = actionplan_id;
         
         orgjx_app_editAchievement('$employee_id',pms_objective_id,actionplan_id,function(_data) {
            $('apreportmsg').innerHTML = _data;
            $('inp_current_achievement').focus();
         });
      }
      
      function set_month(d,e) {
         var month = d.options[d.selectedIndex].value;
         orgjx_app_setPMSMonth(month,function(_data) {
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?u='+uniqid('a');
         });
      }
      
      function do_submit(employee_id) {
         $('confirmbtn').innerHTML = '';
         $('confirmbtn').appendChild(progress_span(' ... submiting action plan'));
         orgjx_app_submitActionPlan('$employee_id',function(_data) {
            confirmsubmitbox.fade();
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?r='+uniqid('r');
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
         $('innerconfirmsubmit').innerHTML = '<div style=\"height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;\">Submit Action Plan Confirmation</div>'
                                           + '<div id=\"confirmmsg\" style=\"padding:20px;text-align:center;\">Are you going to submit this Action Plan?</div>'
                                           + '<div id=\"confirmbtn\" style=\"background-color:#eee;padding:10px;text-align:center;\">'
                                             + '<input type=\"button\" value=\"Yes (Submit)\" onclick=\"do_submit();\"/>&nbsp;&nbsp;'
                                             + '<input type=\"button\" value=\"No\" onclick=\"confirmsubmitbox.fade();\"/>'
                                           + '</div>';
         
         
         confirmsubmitbox = new GlassBox();
         confirmsubmitbox.init('confirmsubmit','500px','165px','hidden','default',false,false);
         confirmsubmitbox.lbo(false,0.3);
         confirmsubmitbox.appear();
      }
      
      function do_submit_report(employee_id) {
         $('confirmbtn').innerHTML = '';
         $('confirmbtn').appendChild(progress_span(' ... submiting report'));
         orgjx_app_submitActionPlanReport('$employee_id',function(_data) {
            confirmsubmitreportbox.fade();
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?r='+uniqid('r');
         });
      }
      
      var confirmsubmitreport = null;
      var confirmsubmitreportbox = null;
      function confirm_submit_apreport(d,e) {
         confirmsubmitreport = _dce('div');
         confirmsubmitreport.setAttribute('id','confirmsubmitreport');
         confirmsubmitreport = document.body.appendChild(confirmsubmitreport);
         confirmsubmitreport.sub = confirmsubmitreport.appendChild(_dce('div'));
         confirmsubmitreport.sub.setAttribute('id','innerconfirmsubmitreport');
         confirmsubmitreportbox = new GlassBox();
         $('innerconfirmsubmitreport').innerHTML = '<div style=\"height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;\">Submit Action Plan Report Confirmation</div>'
                                           + '<div id=\"confirmmsg\" style=\"padding:20px;text-align:center;\">Are you going to submit this Action Plan Report?</div>'
                                           + '<div id=\"confirmbtn\" style=\"background-color:#eee;padding:10px;text-align:center;\">'
                                             + '<input type=\"button\" value=\"Yes (Submit Report)\" onclick=\"do_submit_report();\"/>&nbsp;&nbsp;'
                                             + '<input type=\"button\" value=\"No\" onclick=\"confirmsubmitreportbox.fade();\"/>'
                                           + '</div>';
         
         
         confirmsubmitreportbox = new GlassBox();
         confirmsubmitreportbox.init('confirmsubmitreport','500px','165px','hidden','default',false,false);
         confirmsubmitreportbox.lbo(false,0.3);
         confirmsubmitreportbox.appear();
      }
      
      
      
      var dvtooltip = null;
      function show_ap_tooltip(pms_objective_id,actionplan_id,d,e) {
         if(actionplan_id==0) return;
         if(dvapeditor&&dvapeditor.style.visibility=='visible') {
            return;
         }
         if(!dvtooltip) {
            dvtooltip = _dce('div');
            dvtooltip.setAttribute('style','visibility:hidden;position:absolute;padding:5px;-moz-border-radius:5px;border:1px solid #bbb;background-color:#ffffdd;left:0px;-moz-box-shadow:-1px -1px 1px #00f;-moz-box-shadow:1px 1px 3px #000;');
            dvtooltip = document.body.appendChild(dvtooltip);
            dvtooltip.style.left = '-1000px';
            dvtooltip.style.top = '-1000px';
            dvtooltip.arrow = _dce('img');
            dvtooltip.arrow.setAttribute('style','position:absolute;left:0px;');
            dvtooltip.arrow.src = '".XOCP_SERVER_SUBDIR."/images/leftmiddle.png';
            dvtooltip.arrow = dvtooltip.appendChild(dvtooltip.arrow);
            dvtooltip.arrow.style.top = '3px';
            dvtooltip.arrow.style.left = '-12px';
            dvtooltip.inner = dvtooltip.appendChild(_dce('div'));
         }
         var xtooltip = $('intooltip_'+pms_objective_id+'_'+actionplan_id);
         if(xtooltip) {
            dvtooltip.innerHTML = xtooltip.innerHTML;
            if(e.pageX>660) {
               dvtooltip.style.left = parseInt(e.pageX-dvtooltip.offsetWidth)+'px';
               dvtooltip.style.top = parseInt(e.pageY+3)+'px';
            } else {
               dvtooltip.style.left = parseInt(e.pageX+3)+'px';
               dvtooltip.style.top = parseInt(e.pageY+3)+'px';
            }
            dvtooltip.style.visibility = 'visible';
         }
      }
      
      function hide_ap_tooltip(pms_objective_id,actionplan_id,d,e) {
         dvtooltip.style.left = '-1000px';
         dvtooltip.style.top = '-1000px';
         dvtooltip.style.visibility = 'hidden';
      }
      
      function mouseover_aptextpica(no,pms_objective_id,actionplan_id,d,e) {
         //show_ap_tooltip(pms_objective_id,actionplan_id,d,e);
         for(var i=1;i<=12;i++) {
            var td = $('tdap_'+pms_objective_id+'_'+i);
            if(td&&td.childNodes&&td.childNodes[no]) {
               td.childNodes[no].style.backgroundColor = '#fcc';
            }
            td = $('tdpica_root_'+pms_objective_id+'_'+i);
            if(td&&td.childNodes&&td.childNodes[no]) {
               td.childNodes[no].style.backgroundColor = '#fcc';
            }
            td = $('tdpica_improve_'+pms_objective_id+'_'+i);
            if(td&&td.childNodes&&td.childNodes[no]) {
               td.childNodes[no].style.backgroundColor = '#fcc';
            }
            td = $('tdpica_month_'+pms_objective_id+'_'+i);
            if(td&&td.childNodes&&td.childNodes[no]) {
               td.childNodes[no].style.backgroundColor = '#fcc';
            }
         }
         var tdtg = $('tdtg_'+pms_objective_id);
         if(tdtg&&tdtg.childNodes&&tdtg.childNodes[no]) {
            tdtg.childNodes[no].style.backgroundColor = '#fcc';
         }
         var tdap = $('tdaptext_'+pms_objective_id);
         if(tdap&&tdap.childNodes&&tdap.childNodes[no]) {
            tdap.childNodes[no].style.backgroundColor = '#fcc';
         }
      }
      
      function mouseout_aptextpica(no,pms_objective_id,actionplan_id,d,e) {
         if(dvtooltip) {
            hide_ap_tooltip(pms_objective_id,actionplan_id,d,e);
         }
         
         if(dvapeditor&&dvapeditor.style.visibility=='visible') {
            if(dvapeditor.no==no&&dvapeditor.pms_objective_id==pms_objective_id) return;
         }
         for(var i=1;i<=12;i++) {
            var td = $('tdap_'+pms_objective_id+'_'+i);
            if(td&&td.childNodes&&td.childNodes[no]) {
               td.childNodes[no].style.backgroundColor = '#fc9';
            }
            td = $('tdpica_root_'+pms_objective_id+'_'+i);
            if(td&&td.childNodes&&td.childNodes[no]) {
               td.childNodes[no].style.backgroundColor = '#fc9';
            }
            td = $('tdpica_improve_'+pms_objective_id+'_'+i);
            if(td&&td.childNodes&&td.childNodes[no]) {
               td.childNodes[no].style.backgroundColor = '#fc9';
            }
            td = $('tdpica_month_'+pms_objective_id+'_'+i);
            if(td&&td.childNodes&&td.childNodes[no]) {
               td.childNodes[no].style.backgroundColor = '#fc9';
            }
         }
         var tdtg = $('tdtg_'+pms_objective_id);
         if(tdtg&&tdtg.childNodes&&tdtg.childNodes[no]) {
            tdtg.childNodes[no].style.backgroundColor = '#fc9';
         }
         var tdap = $('tdaptext_'+pms_objective_id);
         if(tdap&&tdap.childNodes&&tdap.childNodes[no]) {
            tdap.childNodes[no].style.backgroundColor = '#fc9';
         }
      }
      
      
      
      function mouseover_aptext(no,pms_objective_id,actionplan_id,d,e) {
         //show_ap_tooltip(pms_objective_id,actionplan_id,d,e);
         for(var i=1;i<=12;i++) {
            var td = $('tdap_'+pms_objective_id+'_'+i);
            if(td&&td.childNodes&&td.childNodes[no]) {
               td.childNodes[no].style.backgroundColor = '#eee';
            }
            td = $('tdpica_root_'+pms_objective_id+'_'+i);
            if(td&&td.childNodes&&td.childNodes[no]) {
               td.childNodes[no].style.backgroundColor = '#eee';
            }
            td = $('tdpica_improve_'+pms_objective_id+'_'+i);
            if(td&&td.childNodes&&td.childNodes[no]) {
               td.childNodes[no].style.backgroundColor = '#eee';
            }
            td = $('tdpica_month_'+pms_objective_id+'_'+i);
            if(td&&td.childNodes&&td.childNodes[no]) {
               td.childNodes[no].style.backgroundColor = '#eee';
            }
         }
         var tdtg = $('tdtg_'+pms_objective_id);
         if(tdtg&&tdtg.childNodes&&tdtg.childNodes[no]) {
            tdtg.childNodes[no].style.backgroundColor = '#eee';
         }
         var tdap = $('tdaptext_'+pms_objective_id);
         if(tdap&&tdap.childNodes&&tdap.childNodes[no]) {
            tdap.childNodes[no].style.backgroundColor = '#eee';
         }
      }
      
      function mouseout_aptext(no,pms_objective_id,actionplan_id,d,e) {
         if(dvtooltip) {
            hide_ap_tooltip(pms_objective_id,actionplan_id,d,e);
         }
         
         if(dvapeditor&&dvapeditor.style.visibility=='visible') {
            if(dvapeditor.no==no&&dvapeditor.pms_objective_id==pms_objective_id) return;
         }
         for(var i=1;i<=12;i++) {
            var td = $('tdap_'+pms_objective_id+'_'+i);
            if(td&&td.childNodes&&td.childNodes[no]) {
               td.childNodes[no].style.backgroundColor = 'transparent';
            }
            td = $('tdpica_root_'+pms_objective_id+'_'+i);
            if(td&&td.childNodes&&td.childNodes[no]) {
               td.childNodes[no].style.backgroundColor = 'transparent';
            }
            td = $('tdpica_improve_'+pms_objective_id+'_'+i);
            if(td&&td.childNodes&&td.childNodes[no]) {
               td.childNodes[no].style.backgroundColor = 'transparent';
            }
            td = $('tdpica_month_'+pms_objective_id+'_'+i);
            if(td&&td.childNodes&&td.childNodes[no]) {
               td.childNodes[no].style.backgroundColor = 'transparent';
            }
         }
         var tdtg = $('tdtg_'+pms_objective_id);
         if(tdtg&&tdtg.childNodes&&tdtg.childNodes[no]) {
            tdtg.childNodes[no].style.backgroundColor = 'transparent';
         }
         var tdap = $('tdaptext_'+pms_objective_id);
         if(tdap&&tdap.childNodes&&tdap.childNodes[no]) {
            tdap.childNodes[no].style.backgroundColor = 'transparent';
         }
      }
      
      function _changedatetime_callback(span_txt_id,result,visibility) {
         if(span_txt_id=='spdtach') {
            orgjx_app_setCurrentStatusDate(result,function(_data) {
               var d = $('spdtach');
               if(d) {
                  if(d.obdt.style.visibility=='hidden') {
                     location.reload();
                  }
               }
            });
         }
      }
      
      //////////////////////////////////////////////////////////////////////////////
      function save_ap() {
         save_actionplan(dvmonthaction.pms_objective_id,dvmonthaction.actionplan_id);
         _destroy(dvmonthaction);
         dvmonthaction.actionplan_id = null;
         dvmonthaction.pms_objective_id = null;
         dvmonthaction.d = null;
         dvmonthaction.month_id = null;
         dvmonthaction = null;
         return;
      }
      
      function actionplan_updater(_data) {
         var data = recjsarray(_data);
         $('tdaptext_'+data[0]).innerHTML = data[1];
         $('tdtg_'+data[0]).innerHTML = data[2];
         if($('tdap_'+data[0]+'_3')) {
            $('tdap_'+data[0]+'_1').innerHTML = data[3];
            $('tdap_'+data[0]+'_2').innerHTML = data[4];
            $('tdap_'+data[0]+'_3').innerHTML = data[5];
            $('tdap_'+data[0]+'_4').innerHTML = data[6];
            $('tdap_'+data[0]+'_5').innerHTML = data[7];
            $('tdap_'+data[0]+'_6').innerHTML = data[8];
            $('tdap_'+data[0]+'_7').innerHTML = data[9];
            $('tdap_'+data[0]+'_8').innerHTML = data[10];
            $('tdap_'+data[0]+'_9').innerHTML = data[11];
            $('tdap_'+data[0]+'_10').innerHTML = data[12];
            $('tdap_'+data[0]+'_11').innerHTML = data[13];
            $('tdap_'+data[0]+'_12').innerHTML = data[14];
         } else if($('tdpica_root_'+data[0]+'_${current_month}')) {
            $('tdpica_root_'+data[0]+'_${current_month}').innerHTML = data[16];
            $('tdpica_improve_'+data[0]+'_${current_month}').innerHTML = data[17];
            $('tdpica_month_'+data[0]+'_${current_month}').innerHTML = data[18];
         }
         $('so_ap_tooltip_'+data[0]).innerHTML = data[15];
      }
      
      function delete_actionplan() {
         orgjx_app_deleteActionPlan('$employee_id',dvapeditor.pms_objective_id,dvapeditor.actionplan_id,function(_data) {
            actionplan_updater(_data);
            dvapeditor.actionplan_id = 0;
            dvapeditor.pms_objective_id = 0;
            dvapeditor.style.visibility = 'hidden';
            dvapeditor.style.left = '-1000px';
            dvapeditor.style.top = '-1000px';
         });
      }
      
      function chgselmonth(d,e) {
         var rt = $('repeat_type');
         if(rt.options[rt.selectedIndex].value==0) {
            $('selmonth2').disabled = true;
            $('selmonth2').setAttribute('disabled','1');
            $('selmonth2').selectedIndex = $('selmonth').selectedIndex;
         }
      }
      
      function chgselrepeat(d,e) {
         var repeat_type = d.options[d.selectedIndex].value;
         $('sp_carry_over').style.color = 'blue';
         if(repeat_type==0) {
            $('selmonth2').disabled = true;
            $('selmonth2').setAttribute('disabled','1');
            $('selmonth2').selectedIndex = $('selmonth').selectedIndex;
            $('month_range').style.display = '';
            $('choose_month').style.display = 'none';
            $('sp_carry_over').innerHTML = 'Yes';
            $('allow_carry_over').value = '1';
         } else if(repeat_type==1) {
            $('selmonth2').disabled = false;
            $('month_range').style.display = '';
            $('choose_month').style.display = 'none';
            $('sp_carry_over').innerHTML = 'No';
            $('allow_carry_over').value = '0';
         } else {
            $('month_range').style.display = 'none';
            $('choose_month').style.display = '';
            $('sp_carry_over').innerHTML = 'No';
            $('allow_carry_over').value = '0';
         }
      }
      
      function save_actionplan() {
         var selmonth = $('selmonth');
         var month_id = selmonth.options[selmonth.selectedIndex].value;
         var selmonth2 = $('selmonth2');
         var month_id2 = selmonth2.options[selmonth2.selectedIndex].value;
         var actionplan_text = urlencode($('inp_aptext').value);
         var target_text = urlencode($('inp_tgtext').value);
         var carry_over = 1;
         var selrepeat = $('repeat_type');
         var repeat_type = selrepeat.options[selrepeat.selectedIndex].value;
         if(!$('allow_carry_over').checked) {
            carry_over = 0;
         }
         var choose_month = _parseForm('choose_month');
         orgjx_app_saveActionPlan('$employee_id',actionplan_text,target_text,dvapeditor.actionplan_id,dvapeditor.pms_objective_id,month_id,month_id2,carry_over,repeat_type,choose_month,function(_data) {
            actionplan_updater(_data);
            
            dvapeditor.actionplan_id = 0;
            dvapeditor.pms_objective_id = 0;
            dvapeditor.style.visibility = 'hidden';
            dvapeditor.style.left = '-1000px';
            dvapeditor.style.top = '-1000px';
            return;
            
         });
      }
      
      function kp_actionplan(d,e) {
         var k = getkeyc(e);
         if(d.chgt) {
            d.chgt.reset();
            d.chgt = null;
         }
         if(k==13) {
            save_ap();
         } else if(k==27) {
            _destroy(dvmonthaction);
            dvmonthaction.actionplan_id = null;
            dvmonthaction.pms_objective_id = null;
            dvmonthaction.d = null;
            dvmonthaction = null;
         } else {
            d.chgt = new ctimer('save_actionplan();',100);
            d.chgt.start();
         }
      }
      
      function close_actionplan() {
         if(dvapeditor.actionplan_id=='new') {
            _destroy($('dvap_'+dvapeditor.pms_objective_id+'_new'));
         }
         if($('dvap_'+dvapeditor.pms_objective_id+'_'+dvapeditor.actionplan_id)) {
            $('dvap_'+dvapeditor.pms_objective_id+'_'+dvapeditor.actionplan_id).style.borderTop = '1px solid transparent';
            $('dvtg_'+dvapeditor.pms_objective_id+'_'+dvapeditor.actionplan_id).style.borderTop = '1px solid transparent';
            $('dvap_'+dvapeditor.pms_objective_id+'_'+dvapeditor.actionplan_id).style.borderBottom = '1px solid transparent';
            $('dvtg_'+dvapeditor.pms_objective_id+'_'+dvapeditor.actionplan_id).style.borderBottom = '1px solid transparent';
         }
         var no = dvapeditor.no;
         for(var i=1;i<=12;i++) {
            var td = $('tdap_'+dvapeditor.pms_objective_id+'_'+i);
            if(td&&td.childNodes&&td.childNodes[no]) {
               td.childNodes[no].style.borderTop = '1px solid transparent';
               td.childNodes[no].style.borderBottom = '1px solid transparent';
            }
         }
         dvapeditor.no = null;
         mouseout_aptext(no,dvapeditor.pms_objective_id,null,null);
         
         dvapeditor.actionplan_id = 0;
         dvapeditor.pms_objective_id = 0;
         dvapeditor.style.visibility = 'hidden';
         dvapeditor.style.left = '-1000px';
         dvapeditor.style.top = '-1000px';
      }
      
      function new_actionplan(pms_objective_id,d,e) {
         _destroy($('new_ap'));
         var dv = _dce('div');
         var td = $('tdaptext_'+pms_objective_id);
         dv = td.insertBefore(dv,$('dvaddap_'+pms_objective_id));
         dv.setAttribute('id','dvap_'+pms_objective_id+'_new');
         var no = td.childNodes.length - 1;
         dv.className = 'aptext';
         dv.innerHTML = '<span id=\"spnew_ap\" class=\"xlnk\">"._EMPTY."</span>';
         edit_actionplan('new',pms_objective_id,$('spnew_ap'),e);
      }
      
      var dvapeditor = null;
      function edit_actionplan(actionplan_id,pms_objective_id,d,e) {
         if(dvtooltip) {
            hide_ap_tooltip(pms_objective_id,actionplan_id,d,e);
         }
         var no = 0;
         if(!dvapeditor) {
            dvapeditor = _dce('div');
            dvapeditor.setAttribute('style','visibility:hidden;position:absolute;padding:5px;-moz-border-radius:5px;border:1px solid #777;background-color:#ffffff;left:0px;-moz-box-shadow:1px 1px 3px #000;');
            dvapeditor = document.body.appendChild(dvapeditor);
            dvapeditor.style.left = '-1000px';
            dvapeditor.style.top = '-1000px';
            dvapeditor.arrow = _dce('img');
            dvapeditor.arrow.setAttribute('style','position:absolute;left:0px;');
            dvapeditor.arrow.src = '".XOCP_SERVER_SUBDIR."/images/leftmiddle.png';
            dvapeditor.arrow = dvapeditor.appendChild(dvapeditor.arrow);
            dvapeditor.arrow.style.top = '3px';
            dvapeditor.arrow.style.left = '-12px';
            dvapeditor.inner = dvapeditor.appendChild(_dce('div'));
         }
         
         if(dvapeditor.actionplan_id&&dvapeditor.actionplan_id==actionplan_id&&dvapeditor.pms_objective_id&&dvapeditor.pms_objective_id==pms_objective_id) {
            no = dvapeditor.no;
            
            /// hide border
            $('dvap_'+pms_objective_id+'_'+actionplan_id).style.borderTop = '1px solid transparent';
            $('dvap_'+pms_objective_id+'_'+actionplan_id).style.borderBottom = '1px solid transparent';
            for(var i=1;i<=12;i++) {
               var td = $('tdap_'+dvapeditor.pms_objective_id+'_'+i);
               if(td&&td.childNodes&&td.childNodes[no]) {
                  td.childNodes[no].style.borderTop = '1px solid transparent';
                  td.childNodes[no].style.borderBottom = '1px solid transparent';
               }
            }
            var tdtg = $('tdtg_'+dvapeditor.pms_objective_id);
            if(tdtg&&tdtg.childNodes&&tdtg.childNodes[no]) {
               tdtg.childNodes[no].style.borderTop = '1px solid transparent';
               tdtg.childNodes[no].style.borderBottom = '1px solid transparent';
            }
            
            dvapeditor.actionplan_id = 0;
            dvapeditor.pms_objective_id = 0;
            dvapeditor.style.visibility = 'hidden';
            dvapeditor.style.left = '-1000px';
            dvapeditor.style.top = '-1000px';
            
            
            return;
         }
         
         if(dvapeditor.actionplan_id&&dvapeditor.pms_objective_id) {
            no = dvapeditor.no;
            
            /// hide border
            $('dvap_'+dvapeditor.pms_objective_id+'_'+dvapeditor.actionplan_id).style.borderTop = '1px solid transparent';
            $('dvap_'+dvapeditor.pms_objective_id+'_'+dvapeditor.actionplan_id).style.borderBottom = '1px solid transparent';
            for(var i=1;i<=12;i++) {
               var td = $('tdap_'+dvapeditor.pms_objective_id+'_'+i);
               if(td&&td.childNodes&&td.childNodes[no]) {
                  td.childNodes[no].style.borderTop = '1px solid transparent';
                  td.childNodes[no].style.borderBottom = '1px solid transparent';
               }
            }
            var tdtg = $('tdtg_'+dvapeditor.pms_objective_id);
            if(tdtg&&tdtg.childNodes&&tdtg.childNodes[no]) {
               tdtg.childNodes[no].style.borderTop = '1px solid transparent';
               tdtg.childNodes[no].style.borderBottom = '1px solid transparent';
            }
            dvapeditor.no = null;
            mouseout_aptext(no,dvapeditor.pms_objective_id,null,null);
            
         }
         
         for(var i=0;i<$('tdaptext_'+pms_objective_id).childNodes.length;i++) {
            if($('tdaptext_'+pms_objective_id).childNodes[i].id=='dvap_'+pms_objective_id+'_'+actionplan_id) {
               no=i;
            }
         }
         
         dvapeditor.inner.innerHTML = '';
         dvapeditor.actionplan_id = actionplan_id;
         dvapeditor.pms_objective_id = pms_objective_id;
         dvapeditor.style.left = parseInt(oX(d)+d.parentNode.parentNode.offsetWidth+5)+'px';
         dvapeditor.style.top = parseInt(oY(d)-3)+'px';
         dvapeditor.style.visibility = 'visible';
         dvapeditor.inner.appendChild(progress_span());
         dvapeditor.d = d;
         dvapeditor.no = no;
         
         /// expose border
         $('dvap_'+pms_objective_id+'_'+actionplan_id).style.borderTop = '1px solid #888';
         $('dvap_'+pms_objective_id+'_'+actionplan_id).style.borderBottom = '1px solid #888';
         for(var i=1;i<=12;i++) {
            var td = $('tdap_'+pms_objective_id+'_'+i);
            if(td&&td.childNodes&&td.childNodes[no]) {
               td.childNodes[no].style.borderTop = '1px solid #888';
               td.childNodes[no].style.borderBottom = '1px solid #888';
            }
         }
         var tdtg = $('tdtg_'+pms_objective_id);
         if(tdtg&&tdtg.childNodes&&tdtg.childNodes[no]) {
            tdtg.childNodes[no].style.borderTop = '1px solid #888';
            tdtg.childNodes[no].style.borderBottom = '1px solid #888';
         }
         
         
         orgjx_app_editActionPlan('$employee_id',pms_objective_id,actionplan_id,function(_data) {
            dvapeditor.inner.innerHTML = _data;
            setTimeout('$(\"inp_aptext\").focus();',100);
         });
      }
      
      var dvmonthaction = null;
      function old_edit_actionplan(actionplan_id,month_id,pms_objective_id,d,e) {
         _destroy(dvmonthaction);
         if(dvmonthaction&&actionplan_id==dvmonthaction.actionplan_id&&pms_objective_id==dvmonthaction.pms_objective_id&&dvmonthaction.month_id==month_id) {
            dvmonthaction.actionplan_id = null;
            dvmonthaction.pms_objective_id = null;
            dvmonthaction.d = null;
            dvmonthaction.month_id = null;
            dvmonthaction = null;
            return;
         }
         d.dv = _dce('div');
         d.dv.setAttribute('style','position:absolute;padding:5px;-moz-border-radius:5px;border:1px solid #777;background-color:#ffffcc;left:0px;-moz-box-shadow:1px 1px 3px #000;');
         var ap_text = '';
         var tg_text = '';
         if(actionplan_id!='new') {
            ap_text = d.innerHTML;
            tg_text = $('sptg_'+pms_objective_id+'_'+actionplan_id).innerHTML;
            /// tg_text = $('target_'+pms_objective_id+'_'+actionplan_id).innerHTML;
            if(ap_text=='"._EMPTY."') {
               ap_text = '';
            }
            if(tg_text=='"._EMPTY."') {
               tg_text = '';
            }
         }
         d.dv.innerHTML = '<div style=\"text-align:left;padding:2px;\">Action Plan:<br/>'
                        + '<input type=\"text\" onkeyup=\"kp_actionplan(this,event);\" id=\"inp_actionplan\" style=\"width:350px;\" value=\"'+ap_text+'\"/><br/>'
                        + 'Target:<br/>'
                        + '<input type=\"text\" onkeyup=\"kp_actionplan(this,event);\" id=\"inp_target\" style=\"width:350px;\" value=\"'+tg_text+'\"/><br/>'
                        + '<div style=\"padding:2px;text-align:right;\">'
                        + '<input type=\"button\" value=\""._SAVE."\" onclick=\"save_ap();\"/>'
                        + '</div>'
                        + '</div>';
         d.dv = d.parentNode.parentNode.parentNode.appendChild(d.dv);
         d.dv.style.top = parseInt(oY(d.parentNode)+d.parentNode.offsetHeight+10)+'px';
         var x = oX(d);
         d.dv.style.left = parseInt(oX(d)-(d.dv.offsetWidth/2)+(d.offsetWidth/2))+'px';
         d.dv.arrow = _dce('img');
         d.dv.arrow.setAttribute('style','position:absolute;left:0px;');
         d.dv.arrow.src = '".XOCP_SERVER_SUBDIR."/images/topmiddle.png';
         d.dv.arrow = d.dv.appendChild(d.dv.arrow);
         d.dv.arrow.style.top = '-12px';
         d.dv.arrow.style.left = parseInt(d.dv.offsetWidth-(d.dv.offsetWidth/2)-7)+'px';
         $('inp_actionplan').focus();
         dvmonthaction = d.dv;
         dvmonthaction.d = d;
         dvmonthaction.pms_objective_id = pms_objective_id;
         dvmonthaction.actionplan_id = actionplan_id;
         dvmonthaction.month_id = month_id;
      
      }
      
      function do_approval2(employee_id) {
         $('confirmbtn').innerHTML = '';
         $('confirmbtn').appendChild(progress_span(' ... approve JAM'));
         orgjx_app_approval2JAM('$employee_id',function(_data) {
            confirmapproval2box.fade();
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?goto=y&employee_id=${employee_id}&r='+uniqid('r');
         });
      }
      
      
      function confirm_next_assessor_return_JAM(employee_id) {
         var return_note = urlencode($('return_note').value);
         orgjx_app_nextAssessorReturnJAM(employee_id,return_note,function(_data) {
            var data = recjsarray(_data);
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?goto=y&employee_id=${employee_id}&r='+uniqid('r');
         });
      }
      
      
      var nextassessorreturnedit = null;
      var nextassessorreturnbox = null;
      function next_assessor_return_JAM(employee_id) {
         nextassessorreturnedit = _dce('div');
         nextassessorreturnedit.setAttribute('id','nextassessorreturnedit');
         nextassessorreturnedit = document.body.appendChild(nextassessorreturnedit);
         nextassessorreturnedit.sub = nextassessorreturnedit.appendChild(_dce('div'));
         nextassessorreturnedit.sub.setAttribute('id','innernextassessorreturnedit');
         nextassessorreturnbox = new GlassBox();
         nextassessorreturnbox.init('nextassessorreturnedit','600px','350px','hidden','default',false,false);
         nextassessorreturnbox.lbo(false,0.3);
         nextassessorreturnbox.appear();
         
         var xreturn_note = '';
         if($('xreturn_note')) {
            xreturn_note = $('xreturn_note').innerHTML;
         }
         
         $('innernextassessorreturnedit').innerHTML = '<div style=\"height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;\">Not Approve JAM Confirmation</div>'
                                   + '<div style=\"background-color:#ffffff;font-weight:bold;font-size:1.1em;height:300px;text-align:center;padding-top:20px;\">'
                                   + 'You do not approve this JAM.<br/>You are going to return this JAM to the employee.'
                                   + '<br/><br/>With the following notes:<br/>'
                                   + '<textarea id=\"return_note\" style=\"width:400px;height:100px;\">'+xreturn_note+'</textarea>'
                                   + '<br/><br/>Are you sure?'
                                   + '<br/><br/>'
                                           + '<div id=\"confirmbtn\" style=\"background-color:#eee;padding:10px;text-align:center;\">'
                                   + '<input type=\"button\" value=\"Yes (Not Approve)\" onclick=\"confirm_next_assessor_return_JAM(\\''+employee_id+'\\');\"/>&nbsp;'
                                   + '<input type=\"button\" value=\"No (Cancel)\" onclick=\"nextassessorreturnbox.fade();\"/>'
                                   + '</div>'
                                   + '</div>';
         setTimeout('$(\"return_note\").focus();',200);
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
         $('innerconfirmapproval2').innerHTML = '<div style=\"height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;\">Approve JAM Confirmation</div>'
                                           + '<div id=\"confirmmsg\" style=\"padding:20px;text-align:center;\">Are you going to approve this JAM?</div>'
                                           + '<div id=\"confirmbtn\" style=\"background-color:#eee;padding:10px;text-align:center;\">'
                                             + '<input type=\"button\" value=\"Yes (Approve)\" onclick=\"do_approval2();\"/>&nbsp;&nbsp;'
                                             + '<input type=\"button\" value=\"No (Cancel)\" onclick=\"confirmapproval2box.fade();\"/>'
                                           + '</div>';
         
         
         confirmapproval2box = new GlassBox();
         confirmapproval2box.init('confirmapproval2','500px','165px','hidden','default',false,false);
         confirmapproval2box.lbo(false,0.3);
         confirmapproval2box.appear();
      }
      
      //////////////////////////////////////////////////////////////
      
      
      //////////////////////////////////////////////////////////////////////////////
      
      function do_approval1(employee_id) {
         $('confirmbtn').innerHTML = '';
         $('confirmbtn').appendChild(progress_span(' ... approve PMS action plan'));
         orgjx_app_approval1PMSActionPlan('$employee_id',function(_data) {
            confirmapproval1box.fade();
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?goto=y&employee_id=${employee_id}&r='+uniqid('r');
         });
      }
      
      
      function confirm_first_assessor_return_PMSActionPlan(employee_id) {
         var return_note = urlencode($('return_note').value);
         orgjx_app_firstAssessorReturnPMSActionPlan(employee_id,return_note,function(_data) {
            var data = recjsarray(_data);
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?goto=y&employee_id=${employee_id}&r='+uniqid('r');
         });
      }
      
      
      var firstassessorreturnedit = null;
      var firstassessorreturnbox = null;
      function first_assessor_return_PMSActionPlan(employee_id) {
         firstassessorreturnedit = _dce('div');
         firstassessorreturnedit.setAttribute('id','firstassessorreturnedit');
         firstassessorreturnedit = document.body.appendChild(firstassessorreturnedit);
         firstassessorreturnedit.sub = firstassessorreturnedit.appendChild(_dce('div'));
         firstassessorreturnedit.sub.setAttribute('id','innerfirstassessorreturnedit');
         firstassessorreturnbox = new GlassBox();
         firstassessorreturnbox.init('firstassessorreturnedit','600px','350px','hidden','default',false,false);
         firstassessorreturnbox.lbo(false,0.3);
         firstassessorreturnbox.appear();
         
         var xreturn_note = '';
         if($('xreturn_note')) {
            xreturn_note = $('xreturn_note').innerHTML;
         }
         
         $('innerfirstassessorreturnedit').innerHTML = '<div style=\"height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;\">Not Approve PMS Action Plan Confirmation</div>'
                                   + '<div style=\"background-color:#ffffff;font-weight:bold;font-size:1.1em;height:300px;text-align:center;padding-top:20px;\">'
                                   + 'You do not approve these PMS Actions Plan.<br/>You are going to return these PMS Actions Plan to the employee.'
                                   + '<br/><br/>With the following notes:<br/>'
                                   + '<textarea id=\"return_note\" style=\"width:400px;height:100px;\">'+xreturn_note+'</textarea>'
                                   + '<br/><br/>Please enter notes, then click Yes to finish. Or click No to cancel.'
                                   + '<br/><br/>'
                                           + '<div id=\"confirmbtn\" style=\"background-color:#eee;padding:10px;text-align:center;\">'
                                   + '<input type=\"button\" value=\"Yes (Not Approve)\" onclick=\"confirm_first_assessor_return_PMSActionPlan(\\''+employee_id+'\\');\"/>&nbsp;'
                                   + '<input type=\"button\" value=\"No (Cancel)\" onclick=\"firstassessorreturnbox.fade();\"/>'
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
         $('innerconfirmapproval1').innerHTML = '<div style=\"height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;\">Approve PMS Action Plan Confirmation</div>'
                                           + '<div id=\"confirmmsg\" style=\"padding:20px;text-align:center;color:#888;\">Are you going to approve this PMS action plan?</div>'
                                           + '<div id=\"confirmbtn\" style=\"background-color:#eee;padding:10px;text-align:center;\">'
                                             + '<input type=\"button\" value=\"Yes (Approve)\" onclick=\"do_approval1();\"/>&nbsp;&nbsp;'
                                             + '<input type=\"button\" value=\"No (Cancel)\" onclick=\"confirmapproval1box.fade();\"/>'
                                           + '</div>';
         
         
         confirmapproval1box = new GlassBox();
         confirmapproval1box.init('confirmapproval1','500px','165px','hidden','default',false,false);
         confirmapproval1box.lbo(false,0.3);
         confirmapproval1box.appear();
      }
      
      //////////////////////////////////////////////////////////////
      
      function do_snapshot(employee_id) {
         $('confirmbtn').innerHTML = '';
         $('confirmbtn').appendChild(progress_span(' ... creating snapshot'));
         orgjx_app_createSnapshot('$employee_id',function(_data) {
            confirmsnapshotbox.fade();
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?r='+uniqid('r');
         });
      }
      
      var confirmsnapshot = null;
      var confirmsnapshotbox = null;
      function confirm_snapshot(d,e) {
         confirmsnapshot = _dce('div');
         confirmsnapshot.setAttribute('id','confirmsnapshot');
         confirmsnapshot = document.body.appendChild(confirmsnapshot);
         confirmsnapshot.sub = confirmsnapshot.appendChild(_dce('div'));
         confirmsnapshot.sub.setAttribute('id','innerconfirmsnapshot');
         confirmsnapshotbox = new GlassBox();
         $('innerconfirmsnapshot').innerHTML = '<div style=\"height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;\">Create Report Snapshot Confirmation</div>'
                                           + '<div id=\"confirmmsg\" style=\"padding:20px;text-align:center;\">Are you going to create snapshot of these achievement values?</div>'
                                           + '<div id=\"confirmbtn\" style=\"background-color:#eee;padding:10px;text-align:center;\">'
                                             + '<input type=\"button\" value=\"Yes (Submit)\" onclick=\"do_snapshot();\"/>&nbsp;&nbsp;'
                                             + '<input type=\"button\" value=\"No\" onclick=\"confirmsnapshotbox.fade();\"/>'
                                           + '</div>';
         
         
         confirmsnapshotbox = new GlassBox();
         confirmsnapshotbox.init('confirmsnapshot','500px','165px','hidden','default',false,false);
         confirmsnapshotbox.lbo(false,0.3);
         confirmsnapshotbox.appear();
      }
      
      function save_target_achievement() {
         var val = parseFloat($('inp_target_achievement').value).toFixed(2);
         if(dvedittargetachievement) {
            dvedittargetachievement.d.innerHTML = val;
         }
         orgjx_app_saveCurrentTargetAchievement(val,dvedittargetachievement.pms_objective_id,dvedittargetachievement.actionplan_id,null);
      }
      
      function kp_target_achievement(d,e) {
         var k = getkeyc(e);
         if(d.chgt) {
            d.chgt.reset();
            d.chgt = null;
         }
         var val = parseFloat(d.value);
         if(k==13) {
            save_target_achievement();
            _destroy(dvedittargetachievement);
            dvedittargetachievement.d = null;
            dvedittargetachievement = null;
         } else if (k==27) {
            _destroy(dvedittargetachievement);
            dvedittargetachievement.d = null;
            dvedittargetachievement = null;
         } else {
            d.chgt = new ctimer('save_target_achievement();',300);
            d.chgt.start();
         }
      }
      
      var dvedittargetachievement = null;
      function edit_target_achievement(pms_objective_id,actionplan_id,d,e) {
         document.body.onclick = null;
         _destroy(dvedittargetachievement);
         if(dvedittargetachievement&&d==dvedittargetachievement.d) {
            dvedittargetachievement.d = null;
            dvedittargetachievement = null;
            return;
         }
         d.dv = _dce('div');
         d.dv.setAttribute('style','width:270px;position:absolute;padding:5px;-moz-border-radius:5px;border:1px solid #777;background-color:#ffffcc;left:0px;-moz-box-shadow:1px 1px 3px #000;');
         d.dv.innerHTML = '<div style=\"text-align:center;padding:2px;\">Achievement : <input onkeyup=\"kp_target_achievement(this,event);\" id=\"inp_target_achievement\" style=\"-moz-border-radius:3px;width:50px;text-align:center;\" type=\"text\" value=\"'+parseFloat(d.innerHTML)+'\"/>&nbsp;%</div>';
         d.dv.innerHTML += '<div style=\"margin-top:5px;padding:5px;border:1px solid #888;background-color:#fff;\" id=\"dvsnapshot_history\"></div>';
         d.dv = d.parentNode.appendChild(d.dv);
         d.dv.style.top = parseInt(oY(d)+d.offsetHeight+10)+'px';
         d.dv.style.left = parseInt(oX(d.parentNode)-(d.dv.offsetWidth-d.parentNode.offsetWidth)/2)+'px';
         d.dv.arrow = _dce('img');
         d.dv.arrow.setAttribute('style','position:absolute;left:0px;');
         d.dv.arrow.src = '".XOCP_SERVER_SUBDIR."/images/topmiddle.png';
         d.dv.arrow = d.dv.appendChild(d.dv.arrow);
         d.dv.arrow.style.top = '-12px';
         d.dv.arrow.style.left = parseInt(d.dv.offsetWidth-(d.dv.offsetWidth/2)-7)+'px';
         _dsa($('inp_target_achievement'));
         dvedittargetachievement = d.dv;
         dvedittargetachievement.d = d;
         dvedittargetachievement.pms_objective_id = pms_objective_id;
         dvedittargetachievement.actionplan_id = actionplan_id;
         setTimeout('document.body.onclick = function() { document.body.onclick = null; _destroy(dvedittargetachievement); };',100);
         $('dvsnapshot_history').appendChild(progress_span(' ... loading history'));
         orgjx_app_loadSnapshotHistory(pms_objective_id,actionplan_id,function(_data) {
            $('dvsnapshot_history').innerHTML = _data;
         });
      }
      
      
      ////////////////////////////
    
      
      ////////////////////////////
      
      //]]></script>";
      
      
      return $ret.$form.$tooltip.$js;
      
      
   }  
  
   function report_status($employee_id) {
      $psid = $_SESSION["pms_psid"];
      $db=&Database::getInstance();
      
      list($job_id,
           $employee_idx,
           $job_nm,
           $employee_nm,
           $nip,
           $gender,
           $jobstart,
           $entrance_dttm,
           $jobage,
           $job_summary,
           $person_id,
           $employee_user_id,
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
      ///$ret = "<div style='border:1px solid #bbb;background-color:#eee;padding:5px;text-align:right;'>[<a href='".XOCP_SERVER_SUBDIR."/index.php'>Back</a>]</div>";
      $ret .= "<br/><table style='margin-left:20px;'><tr><td style='padding:4px;border:1px solid #bbb;-moz-box-shadow:2px 2px 5px #333;'><img src='".XOCP_SERVER_SUBDIR."/modules/hris/thumb.php?pid=${person_id}' height='100'/></td>"
            . "<td style='vertical-align:top;padding-left:10px;'>"
            
            . "<table style='font-weight:bold;margin-left:0px;font-size:1.1em;'><colgroup><col width='120'/><col/></colgroup><tbody>"
            . "<tr><td>Job Title</td><td>: $job_nm ($job_abbr)</td></tr>"
            . "<tr><td>Section/Division</td><td>: $org_nm ($org_abbr)</td></tr>"
            . "<tr><td>Incumbent</td><td>: $employee_nm</td></tr>"
            . "<tr><td>NIP</td><td>: $nip</td></tr>"
            . "</tbody></table></td></tr></table><div style='padding:10px;'>";
      
      
      $ret .= "<table style='width:900px;margin-top:10px;table-layout:fixed;' class='xxlist'>"
            . "<colgroup>"
            . "<col width='40'/>"
            . "<col width='180'/>"
            . "<col width='180'/>"
            . "<col width='200'/>"
            . "<col width='200'/>"
            //. "<col width=''/>"
            . "</colgroup>";
      
      $ret .= "<thead>"
            . "<tr>"
            . "<td>No.</td>"
            . "<td>Month</td>"
            . "<td>Status</td>"
            . "<td>Report Submited</td>"
            . "<td>Report Approved</td>"
            //. "<td>Achievement</td>"
            . "</tr>"
            . "</thead>";
      
      $ret .= "<tbody>";
      
      global $xocp_vars;
      

      
      for($m=1;$m<=12;$m++) {
         
         $ttl_target = $ttl_ach = 0;
         
         $status_text = "-";
         $sql = "SELECT DISTINCT(report_approval_st),id,report_submit_dttm,report_approval_dttm FROM sms_pic_action"
              . " WHERE psid = '$psid' AND employee_id = '$employee_id'"
              . " AND month_id = '$m'";

         $result = $db->query($sql);
    
		 		 
		 
         if($db->getRowsNum($result)>0) {
            while(list($stat,$idap,$submit_date,$approve_date)=$db->fetchRow($result)) {
				$status_text = $stat;
				$submit_dttm_txt = $submit_date;
				$approval_dttm_txt = $approve_date;
				
				  if($submit_dttm_txt=="0000-00-00 00:00:00") {$submit_dttm_txt = "-"; } else {$submit_dttm_txt = $submit_dttm_txt; }
				  if($approval_dttm_txt=="0000-00-00 00:00:00") {$approval_dttm_txt = "-"; } else {$approval_dttm_txt = $approval_dttm_txt; }
				
				
            }
         } else {
				$status_text = "No Action";
				$submit_dttm_txt = "-";
				$approval_dttm_txt = "-";
         }
         
            
         $ret .= "<tr>"
               . "<td>$m</td>"
               . "<td><a href='".XOCP_SERVER_SUBDIR."/index.php?XP_smsmyap&employee_id=$employee_id&goto=y&month_id=$m'>".$xocp_vars["month_year"][$m]."</a></td>"
               . "<td>$status_text</td>"
               . "<td>$submit_dttm_txt</td>"
               . "<td>$approval_dttm_txt</td>"
               //. "<td style='text-align:right;'>$m_ach</td>"
               . "</tr>";
      }
      
      $ret .= "</tbody>";
      
      
      $ret .= "</table>";
           
      
      $ret .= "</div>";
      return $ret;
      
   }
   
   function main() {
      $db = &Database::getInstance();
      $user_id = getUserID();
      
      $smsselobj = new _sms_class_SelectSession();
      $smssel = "<div style='padding-bottom:2px;max-width:900px;'>".$smsselobj->show()."</div>";
      
      if(!isset($_SESSION["pms_psid"])||$_SESSION["pms_psid"]==0) {
         return $smssel;
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
      
 
      switch ($this->catch) {
         case $this->blockID:
            if($_GET["goto"]=="y") {
               $ret = $this->report($self_employee_id,$_GET["month_id"]);
            } else {
               $ret = $this->report_status($self_employee_id);
            }
            break;
         default:
            if($_GET["goto"]=="y") {
               $ret = $this->report($self_employee_id,$_GET["month_id"]);
            } else {
               $ret = $this->report_status($self_employee_id);
            }
            break;
      }
      return $smssel.$ret;
   }
}

} // PMS_APREPORT_DEFINED
?>