<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/idpimportpr.php                            //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_IMPORTPR_DEFINED') ) {
   define('HRIS_IMPORTPR_DEFINED', TRUE);

   include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
   include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

   class _hris_IDPImportPerformanceReview extends XocpBlock {
      var $catchvar = _HRIS_CATCH_VAR;
      var $blockID = _HRIS_IMPORTPR_BLOCK;
      var $width = "100%";
      var $language;
      var $display_title = TRUE;
      var $title = "Performance Review Management";
      var $display_comment = TRUE;
      var $data;
   
      function _hris_IDPImportPerformanceReview ($catch=NULL) {
         $this->XocpBlock($catch);
      }
      
      
   
   function listClass() {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_idpimportpr.php");
      $ajax = new _hris_class_IDPImportPRAjax("imppr");
      
      $sql = "SELECT pr_session_id,pr_session_nm,is_default"
           . " FROM ".XOCP_PREFIX."pr_session";
      
      $result = $db->query($sql);
      $ret = "<table class='xxlist' style='width:100%;' align='center'><thead><tr><td>"
           . "<span style='float:left;'>Performance Review Session List</span>"
           . "<span style='float:right;'><input onclick='edit_class(\"new\",this,event);' type='button' value='"._ADD."'/></span></td></tr></thead><tbody id='tbproc'>";
      if($db->getRowsNum($result)>0) {
         while(list($pr_session_id,$pr_session_nm,$is_default)=$db->fetchRow($result)) {
            $ret .= "<tr><td id='tdclass_${pr_session_id}'>"
                  . "<table border='0' class='ilist'>"
                  . "<colgroup><col width='100'/><col/></colgroup>"
                  . "<tbody><tr>"
                  . "<td>$pr_session_id</td>"
                  . "<td><span id='sp_${pr_session_id}' class='xlnk' onclick='edit_class(\"$pr_session_id\",this,event);'>".htmlentities(stripslashes($pr_session_nm))."</span></td>"
                  . "</tr></tbody></table>"
                  . "</td></tr>";
         }
      }
      $ret .= "<tr style='display:none;' id='trempty'><td>"._EMPTY."</td></tr>";
      $ret .= "</tbody></table>";
      
      return $ret.$ajax->getJs()."<script type='text/javascript'><!--
      
      function reset_all_superior(d,e) {
         var upper_job_class = $('supperjob').options[$('supperjob').selectedIndex].value;
         $('sp_progress_upper').d = d;
         d.style.visibility = 'hidden';
         $('sp_progress_upper').innerHTML = '';
         $('sp_progress_upper').appendChild(progress_span('&nbsp;'));
         $('sp_progress_upper').style.display = '';
         imppr_app_resetSuperior(wdv.pr_session_id,upper_job_class,function(_data) {
            $('sp_progress_upper').d.style.visibility = 'visible';
            $('sp_progress_upper').style.display = 'none';
         });
      }
      
      var wdv = null;
      function edit_class(pr_session_id,d,e) {
         _destroy(trpreview);
         trpreview = null;
         if(wdv) {
            if(wdv.pr_session_id != 'new' && wdv.pr_session_id == pr_session_id) {
               cancel_edit();
               return;
            } else {
               cancel_edit();
            }
         }
         wdv = _dce('div');
         wdv.pr_session_id = pr_session_id;
         if(pr_session_id=='new') {
            var tre = $('trempty');
            var tr = _dce('tr');
            var td = tr.appendChild(_dce('td'));
            tr = tre.parentNode.insertBefore(tr,tre.parentNode.firstChild);
         } else {
            var td = $('tdclass_'+pr_session_id);
         }
         wdv.setAttribute('style','padding:10px;');
         wdv = td.appendChild(wdv);
         wdv.appendChild(progress_span());
         wdv.td = td;
         imppr_app_editClass(pr_session_id,function(_data) {
            wdv.innerHTML = _data;
            $('inp_pr_session_nm').focus();
         });
      }
      
      function cancel_edit() {
         wdv.td.style.backgroundColor = '';
         if(wdv.pr_session_id=='new') {
            _destroy(wdv.td.parentNode);
         }
         wdv.pr_session_id = null;
         _destroy(wdv);
         wdv = null;
      }
      
      function delete_class() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '#ffcccc';
         wdv.oldHTML = wdv.innerHTML;
         wdv.innerHTML = 'Are you sure you want to delete this method type?<br/><br/>'
                       + '<input type=\"button\" value=\""._CANCEL."\" onclick=\"cancel_delete();\"/>&nbsp;&nbsp;'
                       + '<input type=\"button\" value=\""._DELETE."\" onclick=\"do_delete();\"/>';
      }
      
      function cancel_delete() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '';
         wdv.innerHTML = wdv.oldHTML;
      }
      
      function do_delete() {
         imppr_app_Delete(wdv.pr_session_id,null);
         var tr = wdv.parentNode.parentNode;
         _destroy(tr);
         wdv.pr_session_id = null;
         wdv = null;
      }
      
      function save_session() {
         var pr_session_id = $('inp_pr_session_id').value;
         var pr_session_nm = $('inp_pr_session_nm').value;
         $('progress_edit').innerHTML = '';
         $('progress_edit').appendChild(progress_span());
         imppr_app_saveClass(pr_session_id,pr_session_nm,function(_data) {
            var td = wdv.td;
            var data = recjsarray(_data);
            td.setAttribute('id',data[0]);
            td.innerHTML = data[1];
            wdv = $('wdv');
            wdv.pr_session_id = data[2];
            wdv.td = td;
         });
      }
      
      function do_import_pr(pr_import_id,d,e) {
         $('import_progress').innerHTML = '';
         $('import_progress').appendChild(progress_span(' ... importing'));
         imppr_app_importPR(trpreview.pr_session_id,pr_import_id,function(_data) {
            $('import_progress').innerHTML = _data;
         });
      }
      
      var trpreview = null;
      function preview_pr(pr_session_id,pr_import_id,d,e) {
         if(trpreview) {
            _destroy(trpreview);
            if(trpreview.pr_import_id==pr_import_id) {
               trpreview = null;
               return;
            }
            trpreview = null;
         }
         trpreview = _dce('tr');
         trpreview.td = trpreview.appendChild(_dce('td'));
         trpreview.td.setAttribute('colspan','3');
         trpreview.td.setAttribute('style','padding:2px;padding-left:10px;');
         trpreview.td.appendChild(progress_span());
         trpreview = $('tr_'+pr_import_id).parentNode.insertBefore(trpreview,$('tr_'+pr_import_id).nextSibling);
         trpreview.pr_import_id = pr_import_id;
         trpreview.pr_session_id = pr_session_id;
         imppr_app_viewData(pr_import_id,function(_data) {
            trpreview.td.innerHTML = _data;
         });
      }
      
      document.post_upload=function() {
         imppr_app_postUpload(wdv.pr_session_id,function(_data) {
            $('uploadlist').innerHTML = _data;
         });
      };
         
      
      
      // --></script>";
   }
   
      
      /*
      function listClass() {
         $db=&Database::getInstance();
   
         require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_idpimportpr.php");
         $ajax = new _hris_class_IDPImportPRAjax("idpimport");

         $txt = $ajax->getJs();
         
         $ret = $txt . "<div id='_regionDiv' style='display:none;padding:2px;border:1px solid #888888;margin:2px;background-color:#ddddff;'></div>";
         $ret .= "<table class='xlist' style='width:100%;' border='0' align='center'><thead><tr><td style='vertical-align:middle;text-align:right;'>";
         $ret .= "Excel file : </td><td><iframe src='".XOCP_SERVER_SUBDIR."/modules/hris/pr_excel_reader.php' style='width:400px;border:0px solid black;overflow:hidden;height:22px;'></iframe>";
         $ret .= "</td><td style='vertical-align:middle;'></td></tr></thead></table><div id='uploadlist' style='margin-top:10px;'>".$ajax->getList()."</div>";
         
         return $ret. "<script type='text/javascript'><!--
         
         document.post_upload=function(upload_id) {
            idpimport_app_postUpload(upload_id,function(_data) {
               $('uploadlist').innerHTML = _data;
            });
         };
         
         
         // --></script>";
      }
      */
      
      function viewUpload($upload_id,$page=0) {
         $db=&Database::getInstance();
         
         $p = 100;
         
         $sql = "SELECT filename,upload_dttm FROM ".XOCP_PREFIX."dptupload WHERE upload_id = '$upload_id'";
         $result = $db->query($sql);
         list($filename,$upload_dttm)=$db->fetchRow($result);
         
         $sql = "SELECT COUNT(*) FROM ".XOCP_PREFIX."dptx WHERE upload_id = '$upload_id'";
         $result = $db->query($sql);
         list($ttl)=$db->fetchRow($result);
         $ttlp = ceil($ttl/$p);

         $paging = "[ ";
         for($i=1;$i<=$ttlp;$i++) {
            if($i==($page+1)) {
               $style = "style='font-weight:bold;'";
            } else {
               $style = "";
            }
            $paging .= "<a $style href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&view=$upload_id&p=".($i-1)."'>$i</a> ";
         }
         $paging .= "]";


         $limit = "LIMIT ".($page*$p+1).",$p";
         $sql = "SELECT a.petani_idcard,a.petani_nm,a.petani_alamat,b.kios_nm FROM ".XOCP_PREFIX."dptx a "
              . " LEFT JOIN ".XOCP_PREFIX."kios b USING(kios_id)"
              . " WHERE a.upload_id = '$upload_id' ORDER BY a.petani_id $limit";
         $result = $db->query($sql);
         $ret = "[<a href='".XOCP_SERVER_SUBDIR."/index.php'>back</a>]"
              . "<table align='center'><tbody><tr><td>File </td><td>: $filename</td></tr>"
              . "<tr><td>Time </td><td>: $upload_dttm</td></tr>"
              . "</tbody></table>" // . $paging
              . "<table class='xxlist'><thead><tr><td>KTP</td><td>Name</td><td>Address</td><td>Kios</td></tr></thead><tbody>";
         if($db->getRowsNum($result)>0) {
            while(list($ktp,$nm,$alamat,$kios_nm)=$db->fetchRow($result)) {
               $ret .= "<tr><td>$ktp</td><td>$nm</td><td>$alamat</td><td>$kios_nm</td></tr>";
            }
         }
         $ret .= "</tbody></table>$paging";
         
         return $ret;
      }
      
      function main() {
         $db = &Database::getInstance();
         switch ($this->catch) {
            case $this->blockID:
               if($_GET["view"]>0) {
                  $ret = $this->viewUpload($_GET["view"],$_GET["p"]);
               } else {
                  $ret = $this->listClass();
               }
               break;
            default:
               $ret = $this->listClass();
               break;
         }
         return $ret;
      }
   }
} // PK_RDISTKAB_DEFINED
?>