<?php
//--------------------------------------------------------------------//
// Filename : modules/antrain/assessment_session.php                     //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-12-17                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('ANTRAIN_ANTRAINEXCRATE_DEFINED') ) {
   define('ANTRAIN_ANTRAINEXCRATE_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/antrain/modconsts.php");

class _antrain_ANTRAINExcrate extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _ANTRAIN_ANTRAINEXCRATE_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = 'Exchange Rate';
   var $display_comment = TRUE;
   var $data;
   
   function __construct($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function listSession() {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/antrain/class/ajax_antrainexcrate.php");
      $ajax = new _antrain_class_ANTRAINExcrateAjax("psjx");
	
	  
   	$sql = "SELECT id,id_global_session,rupiah FROM antrain_exc_rate WHERE status_cd = 'normal' ORDER BY id DESC";
      $result = $db->query($sql);

	  $ret = "<table class='xxlist' style='width:100%;' align='center'>"
           . "<thead><tr><td>"
           . "<span style='float:left; width:130px;'>Session</span>"
   		  //. "<span style='float:left; margin-left: 30px;'>Title</span>"
           . "<span style='float:right;'><input onclick='new_session();' type='button' value='"._ADD."'/></span></td></tr>"
           . "</thead><tbody id='tbproc'>";
      $test = $db->getRowsNum($result);
      if($db->getRowsNum($result)>0) {
         while(list($id,$id_global_session,$title,$dollar,$rupiah)=$db->fetchRow($result)) {

			   $sqly = "SELECT id FROM hris_global_sess WHERE id = $id_global_session";
            $resulty = $db->query($sqly);
            list($id_global_session)=$db->fetchRow($resulty);
			  
            $ret .= "<tr><td id='tdclass_${id}'>"
                  . "<table><colgroup><col width='60'/><col/></colgroup><tbody><tr>"
                  . "<td width=150 style='text-align: left;' ><span id='sp_${id}' class='xlnk' onclick='edit_session(\"$id\",this,event); '>".htmlentities(stripslashes($id_global_session))."</span></td>"
				      //. "<td id='td_title_${id}' width=150 style='text-align: left;'>$title"
				      . "</td>"
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
         psjx_app_editSession(id,function(_data) {
            wdv.innerHTML = _data;
            $('inp_rupiah').focus();
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
         psjx_app_Delete(wdv.id,null);
         var tr = wdv.parentNode.parentNode;
         _destroy(tr);
         wdv.id = null;
         wdv = null;
      }
      
      function save_session() {
         var ret = parseForm('frm');
         $('progress').appendChild(progress_span('&nbsp;...saving&nbsp;&nbsp;'));

         psjx_app_saveSession(wdv.id,ret,function(_data) {
            var data = recjsarray(_data);
            //alert(data[0]);
            if (data[3] == 1) {
               alert('exchange rate in this session is exist');
               location.reload();
            }else{ 
               wdv.td.setAttribute('id','tdclass_'+data[0]);
               wdv.td.innerHTML = data[1];
               wdv.td = null;
               wdv = null;
               edit_session(data[0],null,null);
               $('inp_rupiah').focus();
               setTimeout(\"$('progress').innerHTML = '';\",1000);
            }
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