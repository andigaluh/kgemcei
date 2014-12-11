<?php
//--------------------------------------------------------------------//
// Filename : modules/antrain/assessment_session.php                     //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2014-04-21                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('ANTRAIN_MDP_DEFINED') ) {
   define('ANTRAIN_MDP_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/antrain/modconsts.php");

class _antrain_Mdp extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _ANTRAIN_MDP_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = 'MDP';
   var $display_comment = TRUE;
   var $data;
   
   function __construct($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function listMdp() {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/antrain/class/ajax_antrainsession.php");
      $ajax = new _antrain_class_ANTRAINSessionAjax("psjx");
      
	  $person_id = getpersonid();
	  $sql = "SELECT job_class_cd, job_class_nm FROM hris_employee_job LEFT JOIN hris_jobs ON hris_employee_job.job_id = hris_jobs.job_id LEFT JOIN hris_job_class ON hris_jobs.job_class_id = hris_job_class.job_class_id WHERE employee_id = '$person_id'";
	  $result = $db->query($sql);
	  list($job_cd,$job_class)=$db->fetchRow($result);
	
	$proflvl = $_GET['proflvl'];
	$comclass =    $_GET['comclass'];
	$competency_class =    $_GET['competency_class'];

	$ret = "<form action='index.php?XP_mdp' method='get'>";

	$sqlgrup = "SELECT compgroup_id,compgroup_nm FROM hris_mdp_compgroup";
	$resultgrup =  $db->query($sqlgrup);
	
	$sqlcomclass = "SELECT DISTINCT(competency_class) FROM hris_mdp_competency";
	$resultcomclass = $db->query($sqlcomclass);
	
	$ret .= "<p>Competency Class</p>"
				. "<select name='competency_class'>";

/* while(list($comp_ids,$compgroup_nm)=$db->fetchRow($resultgrup)){
				if($compgroup_id == $comp_ids){$selected = 'selected';}else{$selected='';}
				$ret .= "<option name='compgroup_id' value='$comp_ids' $selected>$compgroup_nm</option>"; */
				
				while(list($comclass)=$db->fetchRow($resultcomclass)){
				if($competency_class == $comclass){$selected = 'selected';}else{$selected='';}
				$ret .= "<option name='competency_class' value='$comclass' $selected>$comclass</option>";
				
				
}

			

	$ret .= "</select>";
	
	$ret .= "<button name='search' type='submit'>Search</button> </form>";
	
	
	 $ret .= "<table style='width:100%;margin-top:10px;' class='xxlist'>"
            . "<colgroup>"
				. "<col width='30'/>"
				. "<col width='150'/>"
				. "<col width='*'/>"
				. "<col width='100'/>"
				. "<col width='100'/>"
				. "<col width='100'/>"
				. "<col width='100'/>"
				. "<col width='100'/>"
            . "</colgroup>";
      
    $ret .= "<thead>"
				. "<tr>"
				   . "<td style='border-right:1px solid #bbb;text-align:center;' rowspan='2'>Competencies Group</td>"
				   . "<td style='border-right:1px solid #bbb;text-align:center;' colspan='5'>$job_class ($job_cd)   </td>"
			   . "</tr>"; 

	$ret .= "<tr>"
				   . "<td style='border-right:1px solid #bbb;text-align:center;'>Competency</td>"
				   . "<td style='border-right:1px solid #bbb;text-align:center;'>Behavior indicator</td>"
				   . "<td style='border-right:1px solid #bbb;text-align:center;'>Program</td>"
				   . "<td style='border-right:1px solid #bbb;text-align:center;'>Institution</td>"
				   . "<td style='border-right:1px solid #bbb;text-align:center;'>Method</td>"
			   . "</tr>"
            . "</thead>";


		if($competency_class =='')
		{
			$ret .= "";
			$ret .=  "<tr>"
						   . "<td style='border-right:1px solid #bbb; text-align:center;' colspan=6>Please Select Competency Class</td>";
					$ret .= "</tr>";
		}
		
		else
		{
		
			/*	$sqlcomclass = "SELECT DISTINCT(competency_class) FROM hris_mdp_competency WHERE compgroup_id = '$compgroup_id' ";
				$resultcomclass = $db->query($sqlcomclass);
				$numrowcomclass = $db->getRowsNum($resultcomclass)+1;			
			*/
			
			$sqlgrup = "SELECT compgroup_id,compgroup_nm FROM hris_mdp_compgroup";
			$resultgrup =  $db->query($sqlgrup);
			//$numrowcomclass = $db->getRowsNum($resultgrup)+1;
		
			$ret .= "<tbody>";	

			while(list($compgroup_id,$compgroup_nm)=$db->fetchRow($resultgrup))
			  {	
					$sqlcompt = "SELECT a.competency_id, a.competency_nm, a.competency_cd, a.desc_en,a.program,a.institution,a.method FROM hris_mdp_competency a LEFT JOIN hris_mdp_employes b ON a.competency_id = b.competency_id WHERE a.compgroup_id = '$compgroup_id' AND a.competency_class = '$competency_class' AND b.employee_id = ' $person_id' ";
					$resultcompt = $db->query($sqlcompt);
					$numrowcompt = $db->getRowsNum($resultcompt)+1;
	
					
					
					$ret .=  "<tr>"
						   . "<td style='border-right:1px solid #bbb;' rowspan=$numrowcompt>$compgroup_nm</td>";
					$ret .= "</tr>";					
					
					while(list($compid,$compnm,$compcd,$desc,$program,$institution,$method)=$db->fetchRow($resultcompt))
						{
							$ret .=  "<tr>"
										. "<td style='border-right:1px solid #bbb;' >$compnm - ($compcd)</td>";
							
							
							
								$ret .= "<td style='border-right:1px solid #bbb;' >";
							
								$sqlbehav = "SELECT behaviour_en_txt FROM  hris_mdp_compbehaviour WHERE competency_id ='$compid' ";
								$resultbehav = $db->query($sqlbehav);
								while(list($behav)=$db->fetchRow($resultbehav))
								{
										$ret .= "<p style='margin: 3px;'>- $behav</p>";
							
								}	
							
								$ret .= "</td>";
							
							$ret .= "<td style='border-right:1px solid #bbb;' >$program</td>";
							$ret .= "<td style='border-right:1px solid #bbb;' >$institution</td>";
							$ret .= "<td style='border-right:1px solid #bbb;' >$method</td>";
							
							
							
							
							$ret .= "</tr>";
						}
					
				}

		}
		
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
			$('td_remark_'+data[0]).innerHTML = data[2];
            $('inp_year').focus();
            setTimeout(\"$('progress').innerHTML = '';\",1000);
			location.reload();
         });
      }
      
      // --></script>";
   }
   
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $this->listMdp();
            break;
         default:
            $ret = $this->listMdp();
            break;
      }
    
/* 	$sqljob = "SELECT j.job_id FROM hris_employee_job j LEFT JOIN hris_users u ON (u.person_id = j.employee_id) WHERE u.user_id = ".getUserid()."";
	$resultjob = $db->query($sqljob);
	 list($job_id)=$db->fetchRow($resultjob);
	//echo 'getuserid = '.getuserid(). '<br> job_id = '.$job_id; exit();
	 	 if ($job_id ==  130 || $job_id == 90 || $job_id == 68 || $job_id == 101 || $job_id == 0){
      return $ret;
	  }
	 // elseif( getUserID() == '' ) { }
	  else
	  {
	  return getUserID();
} */


	  return $ret;

   }
}

} // ANTRAIN_ANTRAINSESSION_DEFINED
?>