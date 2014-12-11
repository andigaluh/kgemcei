<?php
//--------------------------------------------------------------------//
// Filename : modules/antrain/assessment_session.php                     //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-12-17                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('ANTRAIN_ANTRAINSESSION_DEFINED') ) {
   define('ANTRAIN_ANTRAINSESSION_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/antrain/modconsts.php");

class _antrain_ANTRAINSession extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _ANTRAIN_ANTRAINSESSION_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = 'Annual Training Session';
   var $display_comment = TRUE;
   var $data;
   
   function __construct($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function listSession() {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/antrain/class/ajax_antrainsession.php");
      $ajax = new _antrain_class_ANTRAINSessionAjax("psjx");
      
/*      $sql = "SELECT psid,year,year"
           . " FROM antrain_session"
           . " WHERE status_cd = 'normal'"
           . " ORDER BY year DESC";
*/		   
	 $sql = "SELECT p.psid,  p.year, p.org_id, pk.org_nm, pk.org_class_id"
           . " FROM antrain_session p LEFT JOIN  hris_orgs pk"
           . " ON p.org_id = pk.org_id"
		   . " WHERE p.status_cd = 'normal'"
		   . " ORDER BY year DESC";	   

			  
      $result = $db->query($sql);

      $ret = "<table class='xxlist' style='width:100%;' align='center'>"
           . "<thead><tr><td>"
           . "<span style='float:left; width:45px;'>Year</span>"
           . "<span style='float:left; margin-left: 35px;'>Session Name</span>"
           . "<span style='float:left; margin-left: 215px;'>Div/Section</span>"
           . "<span style='float:right;'><input onclick='new_session();' type='button' value='"._ADD."'/></span></td></tr>"
           . "</thead><tbody id='tbproc'>";
      if($db->getRowsNum($result)>0) {
         while(list($psid,$year,$org_id,$org_nm,$org_class_id)=$db->fetchRow($result)) {
            if($year=="") $year = _EMPTY;
            $ret .= "<tr><td id='tdclass_${psid}'>"
                  . "<table><colgroup><col width='60'/><col/></colgroup><tbody><tr>"
                  . "<td id='td_periode_${psid}' width=75>$year</td>"
                  . "<td width=300 style='text-align: left;' ><span id='sp_${psid}' class='xlnk' onclick='edit_session(\"$psid\",this,event); '>".htmlentities(stripslashes($year))."</span></td>"
                  . "<td id='td_org_nm_${psid}' width=275 style='text-align: left;'>$org_nm</td>"
                  . "</tr></tbody></table>"
                  . "</td></tr>";
				  
				  /*  $hdr = "<table><colgroup><col width='60'/><col/></colgroup><tbody><tr>"
           . "<td id='td_periode_${psid}'width=75>$year</td>"
           . "<td width=300><span id='sp_${psid}' class='xlnk' onclick='edit_session(\"$psid\",this,event);' >"._EMPTY."</span></td>"
		   . "<td id='td_org_nm_${psid}' width=275 style='text-align: left;'>$org_id</td>"
		   . "</tr></tbody></table>"; */
         }
      }
      $ret .= "<tr style='display:none;' id='trempty'><td>"._EMPTY."</td></tr>";
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
            $('td_periode_'+data[0]).innerHTML = data[2]; 
			$('td_org_nm_'+data[0]).innerHTML = data[3];
            $('inp_year').focus();
            setTimeout(\"$('progress').innerHTML = '';\",1000);
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
         orgjx_app_newSession(function(_data) {
            var data = recjsarray(_data);
            wdv.td.setAttribute('id','tdclass_'+data[0]);
            wdv.td.innerHTML = data[1];
            wdv.td = null;
            wdv = null;
            edit_session(data[0],null,null);
         });
      }
	  
	       var wdv = null;
      function edit_session(id,d,e) {
         if(wdv) {
            if(wdv.id == id) {
               cancel_edit();
               return;
            } else {
               cancel_edit();
            }
         }
         wdv = _dce('div');
         wdv.id = id;
         var td = $('tdclass_'+id);
         wdv.setAttribute('style','padding:10px;');
         wdv = td.appendChild(wdv);
         wdv.appendChild(progress_span());
         wdv.td = td;
         orgjx_app_editSession(id,function(_data) {
            wdv.innerHTML = _data;
            $('inp_year').focus();
         });
      }
      
      function cancel_edit() {
         wdv.td.style.backgroundColor = '';
         if(wdv.id=='new') {
            _destroy(wdv.td.parentNode);
         }
         wdv.id = null;
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
         orgjx_app_Delete(wdv.id,null);
         var tr = wdv.parentNode.parentNode;
         _destroy(tr);
         wdv.id = null;
         wdv = null;
      }
      
      function save_session() {
         var ret = parseForm('frm');
         $('progress').appendChild(progress_span('&nbsp;...saving&nbsp;&nbsp;'));
         orgjx_app_saveSession(wdv.id,ret,function(_data) {
            var data = recjsarray(_data);
            $('sp_'+data[0]).innerHTML = data[1];
			$('td_remark_'+data[0]).innerHTML = data[2];
            $('inp_year').focus();
            setTimeout(\"$('progress').innerHTML = '';\",1000);
         });