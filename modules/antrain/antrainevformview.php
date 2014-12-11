<?php
//--------------------------------------------------------------------//
// Filename : modules/antrain/antrainevform.php                     //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2013-2-17                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('ANTRAIN_ANTRAINEVFORMVIEW_DEFINED') ) {
   define('ANTRAIN_ANTRAINEVFORMVIEW_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/antrain/modconsts.php");
require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");

class _antrain_evaluationformview extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _ANTRAIN_ANTRAINEVFORMVIEW_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = 'Evaluation Form of Training/Seminar Institution';
   var $display_comment = TRUE;
   var $data;
   
   function __construct($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function evaluationformview() {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/antrain/class/ajax_evaluationform.php");
      $ajax = new _antrain_class_ANTRAINevformajax("psjx");
      	   
	$sql = "SELECT p.psid,  p.institution,p.contact_person, p.subject FROM antrain_evaluationform p LEFT JOIN  hris_orgs pk ON p.org_id = pk.org_id WHERE p.status_cd = 'normal' ORDER BY year DESC";	   

			  
      $result = $db->query($sql);

	  $ret = "<table class='xxlist' style='width:100%;' align='center'>"
           . "<thead><tr><td>"
		   . "<span style='float:left; text-align: center; width:60px;'>No.</span>"
           . "<span style='float:left; width:150px;'>Institution</span>"
     	   . "<span style='float:left; margin-left: 35px;'>Contact Person</span>"
		   . "<span style='float:left; margin-left: 130px;'>Training/Seminar Subject</span>"
           //. "<span style='float:right;'><input onclick='new_session();' type='button' value='"._ADD."'/></span></td></tr>"
           . "</thead><tbody id='tbproc'>";
      if($db->getRowsNum($result)>0) {
         while(list($psid,$institution,$contact_person,$subject)=$db->fetchRow($result)) {
            if($institution=="") $institution = _EMPTY;
			
		/* 	$sqlsec = "SELECT pk.org_nm FROM antrain_sessionreq p LEFT JOIN hris_orgs pk ON p.org_id_sec = pk.org_id WHERE p.status_cd =  'normal' AND p.psid = '$psid' ORDER BY year DESC ";	   
		  	$resultsec = $db->query($sqlsec);
			list($org_nm_sec)=$db->fetchRow($resultsec); */
			
			$num = $num +1;
			  
            $ret .= "<tr><td id='tdclass_${psid}'>"
                  . "<table><colgroup><col width='60'/><col width='60'/><col width='60'/><col/></colgroup>
				  <tbody><tr>"
				 . "<td width=5 style='text-align: center;' >
						<span id='no_${psid}'  '>$num</span>
					</td>"
				 . "<td width=175 style='text-align: left;' >
						<span id='sp_${psid}' class='xlnk' onclick='view_session(\"$psid\",this,event); '>".htmlentities(stripslashes($institution))."</span>
					</td>"
				  . "<td id='td_cp_${psid}' width=200 style='text-align: left;'>
						$contact_person
					</td>"
				  . "<td id='td_subject_${psid}' width=200 style='text-align: left;'>
						$subject
					</td>"
                  . "</tr>
				  </tbody>
				  </table>"
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
            //$('inp_year').focus();
         });
      }
	  
	    function view_session(psid,d,e) {
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
         psjx_app_viewSession(psid,function(_data) {
            wdv.innerHTML = _data;
            //$('inp_year').focus();
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
			$('td_cp_'+data[0]).innerHTML = data[2];
			$('td_subject_'+data[0]).innerHTML = data[3];
			//$('inp_year').focus();
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
            $this->evaluationformview();
            break;
         default:
            $ret = $this->evaluationformview();
            break;
      }
      return $ret;
   }
}

} // ANTRAIN_ANTRAINSESSION_DEFINED
?>