<?php
//--------------------------------------------------------------------//
// Filename : modules/antrain/assessment_session.php                     //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-12-17                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('ANTRAIN_ANTRAINREQSESSION_DEFINED') ) {
   define('ANTRAIN_ANTRAINREQSESSION_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/antrain/modconsts.php");

class _antrain_ANTRAINReqsession extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _ANTRAIN_ANTRAINREQSESSION_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = 'Requisition Session';
   var $display_comment = TRUE;
   var $data;
   
   function __construct($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function listSession() {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/antrain/class/ajax_antrainreqsession.php");
      $ajax = new _antrain_class_ANTRAINReqsessionAjax("psjx");
	
	
	$sqljob = "SELECT j.job_id,a.org_id FROM hris_employee_job j LEFT JOIN hris_users u ON (u.person_id = j.employee_id) LEFT JOIN hris_jobs a ON (j.job_id = a.job_id) WHERE u.user_id = ".getUserid()."";
	$resultjob = $db->query($sqljob);
	list($job_id,$org_id_sec)=$db->fetchRow($resultjob);
	
	$sqlorg = "SELECT b.job_nm,c.job_class_nm,e.org_nm,f.org_class_nm,e.org_id FROM hris_jobs b LEFT JOIN hris_job_class c USING(job_class_id) LEFT JOIN hris_workarea d ON d.workarea_id = b.workarea_id LEFT JOIN hris_orgs e ON e.org_id = b.org_id LEFT JOIN hris_org_class f ON f.org_class_id = e.org_class_id WHERE b.job_id = '$job_id'";
	$resultorg = $db->query($sqlorg);
	 list($job_nm,$job_class_nm,$org_nm,$org_class_nm,$org_ids)=$db->fetchRow($resultorg);
	
	
		if ($job_id == 999 || $job_id == 130 || $job_id == 68 || $job_id == 147  || $job_id == 101 || $job_id == 90 || $job_id == 6 || $job_id == 0 )
			{
				$divsecid = '';
			}
		//edit disini untuk filter by Division
		elseif($org_class_nm == 'Division')
			{
				$divsecid = " AND p.org_id = '$org_id_sec' ";
			}
		//edit disini untuk filter by Section
		elseif($org_class_nm == 'Section')
			{
				$divsecid = " AND p.org_id_sec = '$org_id_sec' ";
			}

	  
	$sql = "SELECT p.psid,  p.year,p.org_id, p.budget, p.remark, pk.org_nm FROM antrain_sessionreq p LEFT JOIN  hris_orgs pk ON p.org_id = pk.org_id WHERE p.status_cd = 'normal' $divsecid ORDER BY year DESC";	   

			  
      $result = $db->query($sql);

	  $ret = "<table class='xxlist' style='width:100%;' align='center'>"
           . "<thead><tr><td>"
           . "<span style='float:left; width:45px;'>Year</span>"
           //. "<span style='float:left; margin-left: 30px;'>Budget</span>"
		   . "<span style='float:left; margin-left: 30px;'>Div/Section</span>"
		   . "<span style='float:left; margin-left: 140px;'>Remark</span>"
           . "<span style='float:right;'><input onclick='new_session();' type='button' value='"._ADD."'/></span></td></tr>"
           . "</thead><tbody id='tbproc'>";
      if($db->getRowsNum($result)>0) {
         while(list($psid,$year,$org_id,$budget,$remark,$org_nm)=$db->fetchRow($result)) {
            if($year=="") $year = _EMPTY;
			
			$sqlsec = "SELECT pk.org_nm FROM antrain_sessionreq p LEFT JOIN hris_orgs pk ON p.org_id_sec = pk.org_id WHERE p.status_cd =  'normal' AND p.psid = '$psid' ORDER BY YEAR DESC ";	   
		  	$resultsec = $db->query($sqlsec);
			list($org_nm_sec)=$db->fetchRow($resultsec);
			
			  
            $ret .= "<tr><td id='tdclass_${psid}'>"
                  . "<table><colgroup><col width='60'/><col/></colgroup><tbody><tr>"
                  . "<td width=70 style='text-align: left;' ><span id='sp_${psid}' class='xlnk' onclick='edit_session(\"$psid\",this,event); '>".htmlentities(stripslashes($year))."</span></td>"
				  . "<td id='td_org_nm_${psid}' width=200 style='text-align: left;'>$org_nm / "
				  . "$org_nm_sec </td>"
				  . "<td id='td_remark_${psid}' width=200 style='text-align: left;'>$remark</td>"
                  . "</tr></tbody></table>"
                  . "</td></tr>";
				  
         }
      }
      $ret .= "<tr></tr><tr style='display:none;' id='trempty'><td>"._EMPTY."</td></tr>";
      $ret .= "</tbody></table>";
      
      return $ret.$ajax->getJs()."<script src='".XOCP_SERVER_SUBDIR."/include/calendar.js' type='text/javascript'></script><script type='text/javascript'><!--
      
      function new_session() {
         if(wdv) {
            cancel_edit();
         }
         var tre = $('trempty');
         var tr = _dce('tr');
         var td = tr.appendChild(_dce('td'));
         tr = tre.parentNode.insertBefore(tr,tre);
         wdv = _dce('div');
         wdv.td = td;
         psjx_app_newSession(function(_data) {
            var data = recjsarray(_data);
            wdv.td.setAttribute('id','tdclass_'+data[0]);
            wdv.td.innerHTML = data[1];
            wdv.td = null;
            wdv = null;
            edit_session(data[0],null,null);
         });
      }
      
      var wdv = null;
      function edit_session(psid,d,e) {
         if(wdv) {
            if(wdv.psid == psid) {
               cancel_edit();
               return;
            } else {
               cancel_edit();
            }
         }
         wdv = _dce('div');
         wdv.psid = psid;
         var td = $('tdclass_'+psid);
         wdv.setAttribute('style','padding:10px;');
         wdv = td.appendChild(wdv);
         wdv.appendChild(progress_span());
         wdv.td = td;
         psjx_app_editSession(psid,function(_data) {
            wdv.innerHTML = _data;
            $('inp_year').focus();
         });
      }
      
      function cancel_edit() {
         wdv.td.style.backgroundColor = '';
         if(wdv.psid=='new') {
            _destroy(wdv.td.parentNode);
         }
         wdv.psid = null;
         _destroy(wdv);
         wdv = null;
      }
      
      function delete_session() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '#ffcccc';
         wdv.oldHTML = wdv.innerHTML;
         wdv.innerHTML = 'Are you sure you want to delete this session?<br/><br/>'
                       + '<input type=\"button\" value=\""._CANCEL."\" onclick=\"cancel_delete();\"/>&nbsp;&nbsp;'
                       + '<input type=\"button\" value=\""._DELETE."\" onclick=\"do_delete();\"/>';
      }
      
      function cancel_delete() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '';
         wdv.innerHTML = wdv.oldHTML;
      }
      
      function do_delete() {
         psjx_app_Delete(wdv.psid,null);
         var tr = wdv.parentNode.parentNode;
         _destroy(tr);
         wdv.psid = null;
         wdv = null;
      }
      
      function save_session() {
         var ret = parseForm('frm');
         $('progress').appendChild(progress_span('&nbsp;...saving&nbsp;&nbsp;'));
         psjx_app_saveSession(wdv.psid,ret,function(_data) {
            var data = recjsarray(_data);
            $('sp_'+data[0]).innerHTML = data[1];
			$('td_org_nm_'+data[0]).innerHTML = data[2] + ' / ' + data[4];
			$('td_remark_'+data[0]).innerHTML = data[3];
			$('inp_year').focus();
            setTimeout(\"$('progress').innerHTML = '';\",1000);
			//location.reload();
         });
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

} // ANTRAIN_ANTRAINSESSION_DEFINED
?>