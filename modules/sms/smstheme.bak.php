<?php
//--------------------------------------------------------------------//
// Filename : modules/antrain/antrainplan.php                         //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2010-09-22                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('ANTRAIN_PLAN_SS_MATRIX_DEFINED') ) {
   define('ANTRAIN_PLAN_SS_MATRIX_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/config.php");
global $xocpConfig;

require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
include_once(XOCP_DOC_ROOT."/modules/sms/class/selectsession.php");
include_once(XOCP_DOC_ROOT."/modules/sms/class/ajax_smssession.php");



class _sms_SMSTheme extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _SMS_SMSOBJ_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = 'Strategic Map';
   var $display_comment = TRUE;
   var $data;
   
   function _sms_matrix_jam($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */

   }
   

   function listSession() {
      $psid = $_SESSION["pms_psid"];
      global $xocp_vars;
      
      
      $db=&Database::getInstance();
      $ajax = new _sms_class_SMSSessionAjax("psjx");
  	  $user_id = getUserID();
      
      $sqlvision = "SELECT vision FROM sms_session WHERE id='$psid'";
	  $resultvision = $db->query($sqlvision);
	  list($vision)=$db->fetchRow($resultvision);
   
    $ret = "<style type='text/css'>

					table {
						border-bottom:0;
						border-left:0;
					}
					td, th {
						border-top:0;
						border-right:0;
					}
				.grn {background-color: #00b050!important;}
				.ylw {background-color: #ffff00!important;}
				.red {background-color: #ff0000!important;}
				
				.ylw-box {background-color: #ffff00!important; text-align: center; width: 100%; padding: 10px 0px; margin: 0px 0px 10px 0px; float: left; font-size: 13px; }
				.gry-box {background-color: #c0c0c0!important; text-align: center; width: 100%; padding: 10px 0px; margin: 0px 0px 10px 0px; float: left; font-size: 13px; }
				.grn-box {background-color: #00ff00!important; text-align: center; width: 100%; padding: 10px 0px; margin: 0px 0px 10px 0px; float: left; font-size: 13px; }
				.red-box {background-color: #ff0000!important; text-align: center; width: 100%; padding: 10px 0px; margin: 0px 0px 10px 0px; float: left; color: #fff; font-size: 13px; }
				.blus-box {background-color: #827cff!important; text-align: center; width: 100%; padding: 10px 0px; margin: 0px 0px 10px 0px; float: left; color: #fff; font-size: 13px; border: solid 2px #fff; -webkit-border-radius: 10px; -moz-border-radius: 10px; border-radius: 10px; }
				.blus-box:hover { background-color: #fff!important; text-align: center; width: 100%; padding: 10px 0px; margin: 0px 0px 10px 0px; float: left; color: #827cff; font-size: 13px; border: solid 2px #827cff; }
				
				.light {background-color: #f4f4f4!important; font-size: 10px!important; text-align: center;}
				.tg-table-light { border-collapse: collapse; border-spacing: 2px; }
				.tg-table-light td, .tg-table-light th { background-color: none; border: none; color: #333; font-family:  Lucida Grande,Arial,Lucida Grande,Gill Sans,Futura,Verdana,Helvetica; font-size: 100%; padding: 10px; vertical-align: bottom; }
	
				.tg-head  td { background-color: #7f7f7f; border: 1px #fff solid; color: #fff!important; text-align: center;}
				.tg-head  { background-color: #7f7f7f; border: 1px #fff solid; color: #fff!important; text-align: center;}
				.contents { border: 2px dotted gray!important; min-height:100px;}
				
				.tg-table-light th  { background-color: #fff; color: #333; font-size: 110%; font-weight: bold; border: none!important; }
				.tg-bf { font-weight: bold; } .tg-it { font-style: italic; }
				.tg-left { text-align: left; } .tg-right { text-align: right; } .tg-center { text-align: center; }
				.red-text { color: red!important; font-size: 11px!important;}
				.banner { height: 40px; background-image:url('images/bg-head.png');  background-size:100% 100%; background-repeat: no-repeat; color: #fff!important; }
				
				.rotate {
						vertical-align: middle!important;
				}

				
				</style>";
      $ret .= "<table class='tg-table-light' width='870px' >
					 <col width='4%' />
					 <col width='32%' />
					 <col width='32%' />
					 <col width='32%' />
				  <tr>
				<th rowspan='2'><input type='button' value='Add Objective' onclick='add_objective(\"new\",this,event);'/></th>
				<th  class='banner' colspan='3'>Vision: $vision</th>
			  </tr>";
				
				
		
				
	//
				$sql = "SELECT a.id, a.title,a.id_theme_leader,a.create_user_id,a.create_user_id, a.modified_user_id, a.modified_date,b.alias_nm	FROM sms_ref_themes a JOIN hris_employee b ON(a.id_theme_leader = b.person_id) WHERE session = '$psid' ORDER BY a.id ASC LIMIT 3";
				$result = $db->query($sql);
				  
				$ret .= "<tr class='tg-head'>";
				if($db->getRowsNum($result)>0) {
				while(list($id,$title,$id_theme_leader,$create_user_id,$create_user_id,$modified_user_id,$modified_date,$alias_nm)=$db->fetchRow($result)){
				$ret .="<td class=$id>$title ($alias_nm)</td>";
				}
	
		 }
		$ret .= "</tr>";
				  
				   
		
	$sqlper = "SELECT id, code, title FROM sms_ref_perspektive WHERE session = '$psid' ORDER BY id LIMIT 3  ";
	$resultper = $db->query($sqlper);
	$ret .= "<tr class='tg-head'>";
	if($db->getRowsNum($resultper)>0) {
	while(list($idper,$code,$titleper)=$db->fetchRow($resultper)){
		$ret .= "<tr cellpadding='4' cellspacing='2' class='contents'>
					<td class='rotate'>$titleper</td>";
		
		$sql = "SELECT id, title, create_user_id,create_user_id, modified_user_id, modified_date	FROM sms_ref_themes WHERE session = '$psid'  ORDER BY id LIMIT 3 ";
		$result = $db->query($sql);
		if($db->getRowsNum($result)>0) {
		while(list($id,$title,$create_user_id,$create_user_id,$modified_user_id,$modified_date)=$db->fetchRow($result)){
		

				$ret .="<td>";
				$sqlobj = "SELECT id, id_themes, id_ref_perspektive,objective_code,objective_title,id_objective_owner,id_objective_owner_2 FROM sms_objective WHERE id_ref_perspektive = $idper AND id_themes = $id AND psid= '$psid' AND status='normal'";
				$resultobj = $db->query($sqlobj);
				if($db->getRowsNum($resultobj)>0) {
				while(list($idobj,$id_themes,$id_ref_perspektive,$objective_code,$obj_title,$id_objective_owner,$id_objective_owner_2)=$db->fetchRow($resultobj)){ 
				
				$sqlalias = "SELECT alias_nm FROM hris_employee WHERE person_id = $id_objective_owner";
				$resultalias = $db->query($sqlalias);
				list($alias)=$db->fetchRow($resultalias);
				
				$sqlalias2 = "SELECT alias_nm FROM hris_employee WHERE person_id = $id_objective_owner_2";
				$resultalias2 = $db->query($sqlalias2);
				list($alias2)=$db->fetchRow($resultalias2);
				
				$ret .=	"<a href='?XP_smsobj&idper=$idper&id=$id&idobj=$idobj'  class='blus-box'>
								$objective_code :	$obj_title ($alias,$alias2)
								</a>";
					}
				}
				
				$ret .= "</td>";
			}
		}					
	$ret .= "</td>
			</tr>";
		}
	}				

			  
	$sql = "SELECT a.id, a.title,a.id_theme_leader,a.create_user_id,a.create_user_id, a.modified_user_id, a.modified_date,b.alias_nm	FROM sms_ref_themes a JOIN hris_employee b ON(a.id_theme_leader = b.person_id) WHERE a.session = '$psid' ORDER BY a.id ASC LIMIT 3,4";
	$result = $db->query($sql);
	  
	$ret .= "<tr class='tg-head'>";
	if($db->getRowsNum($result)>0) {
	list($id,$title,$id_theme_leader,$create_user_id,$create_user_id,$modified_user_id,$modified_date,$alias_nm)=$db->fetchRow($result);
	$ret .="<td></td>
				<td colspan=3 class=$id>$title ($alias_nm)</td>";
				
	
		 }
		$ret .= "</tr>";	
	$sqlper = "SELECT id, code, title FROM sms_ref_perspektive WHERE session = '$psid' LIMIT 3,4";
	$resultper = $db->query($sqlper);
	$ret .= "<tr class='tg-head'>";
	if($db->getRowsNum($resultper)>0) {
	list($idper,$code,$titleper)=$db->fetchRow($resultper);
		$ret .= "<tr cellpadding='4' cellspacing='2' class='contents'>
					<td class='rotate'>$titleper </td>";
		
		$sql = "SELECT id, title, create_user_id,create_user_id, modified_user_id, modified_date	FROM sms_ref_themes WHERE session = '$psid' LIMIT 3,4";
		$result = $db->query($sql);
		if($db->getRowsNum($result)>0) {
		while(list($id,$title,$create_user_id,$create_user_id,$modified_user_id,$modified_date)=$db->fetchRow($result)){
		

				$ret .="<td colspan=3 style='padding-left:135px;'>";
				$sqlobj = "SELECT id, id_themes, id_ref_perspektive,objective_code,objective_title,id_objective_owner,id_objective_owner_2 FROM sms_objective WHERE id_ref_perspektive = $idper AND id_themes = $id AND psid= '$psid' AND status='normal'";
				$resultobj = $db->query($sqlobj);
				if($db->getRowsNum($resultobj)>0) {
				while(list($idobj,$id_themes,$id_ref_perspektive,$objective_code,$obj_title,$id_objective_owner,$id_objective_owner_2)=$db->fetchRow($resultobj)){ 
			
				$sqlalias = "SELECT alias_nm FROM hris_employee WHERE person_id = $id_objective_owner";
				$resultalias = $db->query($sqlalias);
				list($alias)=$db->fetchRow($resultalias);
				
				$sqlalias2 = "SELECT alias_nm FROM hris_employee WHERE person_id = $id_objective_owner_2";
				$resultalias2 = $db->query($sqlalias2);
				list($alias2)=$db->fetchRow($resultalias2);
				
				$ret .=	"<a href='?XP_smsobj&idper=$idper&id=$id&idobj=$idobj'  class='blus-box' style='max-width: 250px;' >
								$objective_code :	$obj_title ($alias,$alias2)
								</a>";
					}
				}
				
				$ret .= "</td>";
			}
		}					
	$ret .= "</td>
			</tr>";
		
	}				
	
	//PALING BAWAH
	
	$sql = "SELECT a.id, a.title,a.id_theme_leader,a.create_user_id,a.create_user_id, a.modified_user_id, a.modified_date,b.alias_nm	FROM sms_ref_themes a JOIN hris_employee b ON(a.id_theme_leader = b.person_id) WHERE session = '$psid' ORDER BY a.id ASC LIMIT 4,7";
				$result = $db->query($sql);
				  
	$ret .= "<tr class='tg-head'>";
	if($db->getRowsNum($result)>0) {
	while(list($id,$title,$id_theme_leader,$create_user_id,$create_user_id,$modified_user_id,$modified_date,$alias_nm)=$db->fetchRow($result)){
	$ret .="<td class=$id>$title ($alias_nm)</td>";
			}
		 }
		$ret .= "</tr>";
				  
	
	$sqlper = "SELECT id, code, title FROM sms_ref_perspektive WHERE session = '$psid' LIMIT 4,7  ";
	$resultper = $db->query($sqlper);
	$ret .= "<tr class='tg-head'>";
	if($db->getRowsNum($resultper)>0) {
	while(list($idper,$code,$titleper)=$db->fetchRow($resultper)){
		$ret .= "<tr cellpadding='4' cellspacing='2' class='contents'>
					<td class='rotate'>$titleper</td>";
		
		$sql = "SELECT id, title, create_user_id,create_user_id, modified_user_id, modified_date	FROM sms_ref_themes LIMIT 4,7 ";
		$result = $db->query($sql);
		if($db->getRowsNum($result)>0) {
		while(list($id,$title,$create_user_id,$create_user_id,$modified_user_id,$modified_date)=$db->fetchRow($result)){
		

				$ret .="<td>";
				$sqlobj = "SELECT id, id_themes, id_ref_perspektive,objective_code,objective_title,id_objective_owner,id_objective_owner_2 FROM sms_objective WHERE id_ref_perspektive = $idper AND id_themes = $id AND psid= '$psid' AND status='normal'";
				$resultobj = $db->query($sqlobj);
				if($db->getRowsNum($resultobj)>0) {
				while(list($idobj,$id_themes,$id_ref_perspektive,$objective_code,$obj_title,$id_objective_owner,$id_objective_owner_2)=$db->fetchRow($resultobj)){ 
				
				$sqlalias = "SELECT alias_nm FROM hris_employee WHERE person_id = $id_objective_owner";
				$resultalias = $db->query($sqlalias);
				list($alias)=$db->fetchRow($resultalias);
				
				$sqlalias2 = "SELECT alias_nm FROM hris_employee WHERE person_id = $id_objective_owner_2";
				$resultalias2 = $db->query($sqlalias2);
				list($alias2)=$db->fetchRow($resultalias2);
				
				$ret .=	"<a href='?XP_smsobj&idper=$idper&id=$id&idobj=$idobj'  class='blus-box'>
								$objective_code :	$obj_title ($alias,$alias2)
								</a>";
					}
				}
				
				$ret .= "</td>";
			}
		}					
	$ret .= "</td>
			</tr>";
		}
	}				
	
	
	$ret .= "<tr class='tg-even'>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
				</tr>
			</table>";
   

			
      
      
    $js = $ajax->getJs()."\n<script type='text/javascript'><!--
      
      function cancel_copy() {
         $('dvperspective').innerHTML = $('dvperspective').oldHTML;
         _destroy($('dvcp'));
         dvcp = null;
      }
      
      function do_copy_perspective(psid) {
         $('dvperspective').oldHTML = $('dvperspective').innerHTML;
         $('dvperspective').innerHTML = '<div style=\"padding:10px;text-align:center;background-color:#ffcccc;\">'
                                      + 'Are you sure you want to copy from other SMS session?<br/>Content of this session will be erased.<br/><br/>'
                                      + '<input type=\"button\" value=\"Yes (copy)\" onclick=\"do_do_copy_perspective(\\''+psid+'\\');\"/>&nbsp;'
                                      + '<input type=\"button\" value=\"No (cancel)\" onclick=\"cancel_copy();\"/>'
                                      + '</div>';
      }
      
      function do_do_copy_perspective(psid) {
         _destroy(dvcp);
         dvcp = null;
         ajax_feedback = _caf;
         psjx_app_copyPerspective(psid,function(_data) {
            location.reload();
         });
      }
      
      var dvcp = null;
      function copy_perspective(d,e) {
         if(!dvcp) {
            dvcp = _dce('div');
            dvcp.setAttribute('id','dvcp');
            dvcp.setAttribute('style','min-width:200px;position:absolute;padding:5px;border:1px solid #bbb;-moz-box-shadow:1px 1px 3px #000;-moz-border-radius:5px;background-color:#fff;');
            dvcp = d.parentNode.appendChild(dvcp);
            dvcp.appendChild(progress_span());
            psjx_app_getCopyAlt(function(_data) {
               dvcp.innerHTML = _data;
            });
         } else {
            _destroy(dvcp);
            dvcp = null;
         }
      }
      
      function do_delete(objid) {
         _destroy($('trsms_'+objid));
         persbox.fade();
         psjx_app_deletePerspective(objid,null);
      }
      
      function cancel_delete() {
         var dv = $('frmobj');
         dv.innerHTML = dv.oldHTML;
         dv.style.backgroundColor = 'transparent';
         dvbtn = $('frmbtn');
         dvbtn.innerHTML = dvbtn.oldHTML;
      }
      
      function delete_perspective(objid,d,e) {
         var dv = $('frmobj');
         dv.style.backgroundColor = '#ffcccc';
         dv.oldHTML = dv.innerHTML;
         var cd = $('pc_'+objid).innerHTML;
         var nm = $('pm_'+objid).innerHTML;
         dv.innerHTML = '<div style=\"text-align:center;padding-top:30px;\">Do you want to delete this perspective?<br/><br/>'
                      + '<table align=\"center\"><tbody>'
                      + '<tr><td style=\"text-align:left;\">Code</td><td style=\"text-align:left;font-weight:bold;\"> : '+cd+'</td></tr>'
                      + '<tr><td style=\"text-align:left;\">Name</td><td style=\"text-align:left;font-weight:bold;\"> : '+nm+'</td></tr>'
                      + '</tbody></table>'
                      + '<div style=\"padding:10px;\">'
                      + '<input type=\"button\" value=\"Yes (delete)\" onclick=\"do_delete(\\''+objid+'\\');\"/>&nbsp;'
                      + '<input type=\"button\" value=\"No (cancel)\" onclick=\"cancel_delete();\"/>'
                      + '</div>'
                      + '</div>';
         dvbtn = $('frmbtn');
         dvbtn.oldHTML = dvbtn.innerHTML;
         dvbtn.innerHTML = '';
      }
      
      var persedit = null;
      var persbox = null;
      function add_objective(objid,d,e) {
         persedit = _dce('div');
         persedit.setAttribute('id','persedit');
         persedit = document.body.appendChild(persedit);
         persedit.sub = persedit.appendChild(_dce('div'));
         persedit.sub.setAttribute('id','innerpersedit');
         persbox = new GlassBox();
         persbox.init('persedit','700px','410px','hidden','default',false,false);
         persbox.lbo(false,0.3);
         persbox.appear();
         
         psjx_app_addObj(objid,function(_data) {
            $('innerpersedit').innerHTML = _data;
            $('sms_perspective_code').focus();
         });
         
      }
      
      function save_obj() {
         var ret = _parseForm('frmobj');
         psjx_app_saveObjective(ret,function(_data) {
            var data = recjsarray(_data);
			// alert(data[0]+data[1]+data[2]+data[3]+data[4]+data[5]+data[6]+data[7]+  data[8]+data[9]+data[10]+data[11]);
            if(data[0]==1) {
               var tr = _dce('tr');
               
               tr.setAttribute('id','trsms_'+data[1]);
               
               tr.td0 = tr.appendChild(_dce('td'));
               tr.td0.innerHTML = data[2];
               tr.td0.setAttribute('id','pc_'+data[1]);
               tr.td0.setAttribute('style','text-align:center;font-size:1.2em;font-weight:bold;vertical-align:middle;border-right:1px solid #bbb;');
               
               tr.td1 = tr.appendChild(_dce('td'));
               tr.td1.setAttribute('style','vertical-align:middle;border-right:1px solid #bbb;');
               tr.td1.sp = tr.td1.appendChild(_dce('span'));
               tr.td1.sp.setAttribute('class','xlnk');
               tr.td1.sp.setAttribute('onclick','add_objective(\"'+data[1]+'\",this,event);');
               tr.td1.sp.setAttribute('id','pm_'+data[1]);
               tr.td1.sp.innerHTML = data[3];
               tr.td1.dv = tr.td1.appendChild(_dce('div'));
               tr.td1.dv.setAttribute('style','font-style:italic;color:#888;');
               tr.td1.dv.innerHTML = data[5];
               tr.td1.dv.setAttribute('id','pdesc_'+data[1]);
               
               tr.td2 = tr.appendChild(_dce('td'));
               tr.td2.innerHTML = data[4]+' %';
               tr.td2.setAttribute('id','pw_'+data[1]);
               tr.td2.setAttribute('style','text-align:center;vertical-align:middle');
               $('tbdpers').appendChild(tr);
            } else {
               $('pm_'+data[1]).innerHTML = data[3];
               $('pc_'+data[1]).innerHTML = data[2];
               $('pdesc_'+data[1]).innerHTML = data[5];
               $('pw_'+data[1]).innerHTML = data[4]+' %';
            }
            $('ttlw').innerHTML = data[6]+' %';
         });
	
         persbox.fade();
		 window.setTimeout(function(){location.reload()},2000)
      }
      
      // --></script>";
      
      return $ret.$form.$tooltip.$js;
   }
     
   function main() {
      $antrainselses = new _sms_class_SelectSession();
      $antrainsel = "<div style='padding-bottom:2px;'>".$antrainselses->show()."</div>";
      
      if(!isset($_SESSION["pms_psid"])||$_SESSION["pms_psid"]==0) {
         return $antrainsel;
      }
      $db = &Database::getInstance();
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
      
      switch ($this->catch) {
         case $this->blockID:
            $ret = $this->listSession($self_employee_id);
            break;
         default:
            $ret = $this->listSession($self_employee_id);
            break;
      }
    $sqljob = "SELECT j.job_id FROM hris_employee_job j LEFT JOIN hris_users u ON (u.person_id = j.employee_id) WHERE u.user_id = ".getUserid()."";
	$resultjob = $db->query($sqljob);
	 list($job_id)=$db->fetchRow($resultjob);
	//echo 'getuserid = '.getuserid(). '<br> job_id = '.$job_id; exit();
	 	
      return $antrainsel.$ret;
	
	  
   }
}

} // PMS_MYACTIONPLAN_DEFINED
?>