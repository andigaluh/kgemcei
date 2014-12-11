<?php
//--------------------------------------------------------------------//
// Filename : modules/ehr/procorg.php                                 //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('EHR_PROCEDUREORG_DEFINED') ) {
   define('EHR_PROCEDUREORG_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/ehr/language/adiet.php");
include_once(XOCP_DOC_ROOT."/modules/ehr/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/ehr/include/vocab.php");
include_once(XOCP_DOC_ROOT."/modules/ehr/class/selectorg.php");

class _ehr_ProcedureOrg extends XocpBlock {
   var $catchvar = _EHR_CATCH_VAR;
   var $blockID = _EHR_PROCEDUREORG_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _EHR_PROCEDUREORG_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _ehr_ProcedureOrg($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function listProcedure() {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/ehr/class/ajax_procorg.php");
      $ajax = new _ehr_class_ProcOrgAjax("porjx");
      
      $org_id = $_SESSION["ehr_org_id"];
      
      $_SESSION["html"]->addStyleSheet("<style type='text/css'>"
      . "\ntable.xxlist {width:100%;background-color:white;border-spacing:1px;}"
      . "\ntable.xxlist > thead > tr > td {background-color:#ccccff;color:black;border-right:1px solid #9999cc;font-weight:bold;padding:2px; }"
      . "\ntable.xxlist > tbody > tr > td {background-color:white;color:black;border-top:1px solid #9999cc;border-right:1px solid #9999cc;padding:2px; }"
      . "\ntable.xxfrm {border-spacing:1px;border:1px solid #999999;background-color:white;}"
      . "\ntable.xxfrm > thead > tr > td {border-bottom:1px solid #cccccc;background-color:#999999;padding:2px;font-weight:bold;text-align:center;}"
      . "\ntable.xxfrm > tbody > tr > td {border-bottom:1px solid #cccccc;background-color:#cccccc;padding:2px;font-weight:bold;text-align:right;}"
      . "\ntable.xxfrm > tbody > tr > td + td {border-bottom:1px solid #cccccc;background-color:#eeeeff;padding:2px;text-align:left;}"
      . "\n</style>");
      
      
      $sql = "SELECT a.obj_id,b.obj_nm"
           . " FROM ".XOCP_PREFIX."ehr_org_proc a"
           . " LEFT JOIN ".XOCP_PREFIX."ehr_obj b USING(obj_id)"
           . " WHERE a.org_id = '$org_id'"
           . " ORDER BY b.obj_nm";
      
      $result = $db->query($sql);
      $ret = "<table class='xxlist'><thead><tr><td>"
           . "<span style='float:left;'>Daftar Tindakan</span>"
           . "<span style='float:right;'>"._ADD." : <input id='qproc' type='text' style='width:200px;'/></span></td></tr></thead><tbody id='tbproc'>";
      if($db->getRowsNum($result)>0) {
         while(list($obj_id,$obj_nm)=$db->fetchRow($result)) {
            $ret .= "<tr><td>$obj_id <span id='sp_$obj_id' class='xlnk' onclick='edit_proc(\"$obj_id\",this,event);'>$obj_nm</span></td></tr>";
         }
      } else {
         $ret .= "<tr id='trempty'><td>"._EMPTY."</td></tr>";
      }
      $ret .= "</tbody></table>";
      
      return $ret.$ajax->getJs()."<script type='text/javascript'><!--
      
      ajax_feedback = null;
      var qproc = _gel('qproc');
      qproc._get_param=function() {
         var qval = this.value;
         qval = trim(qval);
         if(qval.length < 2) {
            return '';
         }
         return qval;
      };
      qproc._onselect=function(resId) {
         porjx_app_addProc(resId,putItem);
      };
      qproc._send_query = porjx_app_getProc;
      _make_ajax(qproc);
      qproc.focus();
      
      function putItem(_data) {
         if(_data=='FAILED') {
            alert('Registrasi gagal.');
            return;
         }
         var tbproc = _gel('tbproc');
         var tr = _dce('tr');
         var td = _dce('td');
         td = tr.appendChild(td);
         td.innerHTML = _data;
         tr = tbproc.insertBefore(tr,tbproc.firstChild);
      }
      
      
      var wdv = null;
      function edit_proc(obj_id,d,e) {
         if(wdv) {
            if(wdv.obj_id == obj_id) {
               cancel_edit(obj_id);
               return;
            }
            cancel_edit(obj_id);
         }
         var td = d.parentNode;
         wdv = _dce('div');
         wdv.obj_id = obj_id;
         wdv.setAttribute('id','dv_'+obj_id);
         wdv.setAttribute('style','padding:10px;');
         wdv = td.appendChild(wdv);
         wdv.appendChild(progress_span());
         porjx_app_editProc(obj_id,function(_data) {
            wdv.innerHTML = _data;
         });
      }
      
      function edit_obj(obj_id) {
         location.href = '".XOCP_SERVER_SUBDIR."/index.php?XP_ehrobject_ehr=obj&editobject=y&obj_id='+obj_id;
      }
      
      function cancel_edit(obj_id) {
         wdv.obj_id = null;
         _destroy(wdv);
         wdv = null;
      }
      
      function unreg() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '#ffcccc';
         wdv.oldHTML = wdv.innerHTML;
         wdv.innerHTML = 'Anda akan menghapus tindakan ini?<br/><br/>'
                       + '<input type=\"button\" value=\""._CANCEL."\" onclick=\"cancel_unreg();\"/>&nbsp;&nbsp;'
                       + '<input type=\"button\" value=\""._DELETE."\" onclick=\"do_unreg();\"/>';
      }
      
      function cancel_unreg() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '';
         wdv.innerHTML = wdv.oldHTML;
      }
      
      function do_unreg() {
         porjx_app_Unregister(wdv.obj_id,null);
         var tr = wdv.parentNode.parentNode;
         _destroy(tr);
         wdv = null;
      }
      
      function save_proc() {
         var ret = parseForm('frm');
         wdv.innerHTML = '';
         wdv.appendChild(progress_span());
         porjx_app_saveProc(wdv.obj_id,ret,function(_data) {
            wdv.obj_id = null;
            _destroy(wdv);
            wdv = null;
            var data = recjsarray(_data);
            $('sp_'+data[0]).innerHTML = data[1];
         });
      }
      
      // --></script>";
   }
   
   
   function main() {
      $slorg = new _ehr_class_SelectOrganization($this->catch);
      $slorghtml = $slorg->show();
      if($_SESSION["ehr_org_id"] == 0) {
         return $slorghtml;
      }
      
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $this->listProcedure();
            break;
         default:
            $ret = $this->listProcedure();
            break;
      }
      return $slorghtml."<br/>". $ret;
   }
}

} // EHR_PROCEDUREORG_DEFINED
?>