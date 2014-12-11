<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/pages_editor.php                           //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_PAGESEDITOR_DEFINED') ) {
   define('HRIS_PAGESEDITOR_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_PagesEditor extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_PAGESEDITOR_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_PAGESEDITOR_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_PagesEditor($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function listPage() {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_pages_editor.php");
      $ajax = new _hris_class_PagesEditorAjax("ocjx");
      $ajax->setReqPOST();
      
      $sql = "SELECT page_id,page_title,page_content"
           . " FROM ".XOCP_PREFIX."static_pages"
           . " WHERE status_cd = 'normal'"
           . " ORDER BY created_dttm";
      
      $result = $db->query($sql);
      $ret = "<table class='xxlist' style='width:100%;' align='center'><thead><tr><td>"
           . "<span style='float:left;'>Pages</span>"
           . "<span style='float:right;'><input onclick='edit_page(\"new\",this,event);' type='button' value='"._ADD."'/></span></td></tr>"
           . "</thead><tbody id='tbproc'>";
      if($db->getRowsNum($result)>0) {
         while(list($page_id,$page_title,$page_content)=$db->fetchRow($result)) {
            $ret .= "<tr><td id='tdpage_${page_id}'><span id='sp_${page_id}' class='xlnk' onclick='edit_page(\"$page_id\",this,event);'>".htmlentities(stripslashes($page_title))."</span>"
                  . "</td></tr>";
         }
      }
      $ret .= "<tr style='display:none;' id='trempty'><td>"._EMPTY."</td></tr>";
      $ret .= "</tbody></table>";
      
      $themecss = tinycss(getTheme());
      $_SESSION["html"]->js_tinymce = TRUE;
      
      return $ret.$ajax->getJs()."<script type='text/javascript'><!--
      
      var wdv = null;
      function edit_page(page_id,d,e) {
         if(wdv) {
            if(wdv.page_id != 'new' && wdv.page_id == page_id) {
               cancel_edit();
               return;
            } else {
               cancel_edit();
            }
         }
         wdv = _dce('div');
         wdv.setAttribute('style','padding:10px;');
         wdv.page_id = page_id;
         var cancel_title = '';
         if(page_id=='new') {
            var tre = $('trempty');
            var tr = _dce('tr');
            var td = tr.appendChild(_dce('td'));
            tr = tre.parentNode.insertBefore(tr,tre);
         } else {
            var td = d.parentNode;
            cancel_title = td.innerHTML;
         }
         wdv = td.appendChild(wdv);
         wdv.appendChild(progress_span());
         wdv.td = td;
         ocjx_app_editPage(page_id,function(_data) {
            var data = recjsarray(_data);
            wdv.innerHTML = data[2];
            $('inp_page_title').focus();
            init_my_tiny();
            if(wdv.page_id=='new') {
               wdv.page_id = data[0];
               wdv.cancel_title = data[1];
            }
         });
      }
      
      function cancel_edit() {
         wdv.td.style.backgroundColor = '';
         if(wdv.cancel_title) {
            wdv.td.innerHTML = wdv.cancel_title;
         }
         if(wdv.page_id=='new') {
            //_destroy(wdv.td.parentNode);
         }
         wdv.page_id = null;
         _destroy(wdv);
         wdv = null;
      }
      
      function delete_page() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '#ffcccc';
         wdv.oldHTML = wdv.innerHTML;
         wdv.innerHTML = 'Are you sure you want to delete this page?<br/><br/>'
                       + '<input type=\"button\" value=\""._CANCEL."\" onclick=\"cancel_delete();\"/>&nbsp;&nbsp;'
                       + '<input type=\"button\" value=\""._DELETE."\" onclick=\"do_delete();\"/>';
      }
      
      function cancel_delete() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '';
         wdv.innerHTML = wdv.oldHTML;
      }
      
      function do_delete() {
         ocjx_app_Delete(wdv.page_id,null);
         var tr = wdv.parentNode.parentNode;
         _destroy(tr);
         wdv.page_id = null;
         wdv = null;
      }
      
      function save_page() {
         var page_title = urlencode($('inp_page_title').value);
         var page_content = tinyMCE.get('page_content').getContent();
         page_content = urlencode(page_content);
         wdv.innerHTML = '';
         wdv.appendChild(progress_span());
         ocjx_app_savePage(wdv.page_id,page_title,page_content,function(_data) {
            var td = wdv.td;
            wdv.page_id = null;
            _destroy(wdv);
            wdv = null;
            var data = recjsarray(_data);
            td.setAttribute('id',data[0]);
            td.innerHTML = data[1];
         });
      }
      
      function init_my_tiny() {
         if($('page_content')) {
            tinyMCE.init({
               mode : 'exact',
               elements : 'page_content',
               theme : 'advanced',
               theme_advanced_buttons1 : 'bold,italic,underline,strikethrough,separator,bullist,numlist,outdent,indent,separator,justifyleft,justifycenter,justifyright,separator,forecolor,removeformat,cleanup,separator,image,table,code,cut,copy,pasteword',
               theme_advanced_buttons2 : '', //cut,copy,pasteword',
               theme_advanced_buttons3 : '',
            	theme_advanced_toolbar_location : 'top',
            	theme_advanced_toolbar_align : 'left',
            	apply_source_formatting : true,
            	content_css : '".XOCP_SERVER_SUBDIR."/${themecss}',
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
      
      
      function delete_img() {
         var dv = _gel('img_thumb_div');
         if(dv) {
            var img = dv.firstChild;
            if(img) {
               var id = img.id.substring(5);
               _destroy(img);
               $('img_name').innerHTML = 'DELETED';
               ocjx_app_deleteImage(wdv.page_id,id,function(_data) {
                  if(_data=='EMPTY') return;
                  var data = recjsarray(_data);
                  document.load_thumbnail(data[0],data[1],data[2]);
               });
            }
         }
      }
      
      function send_full() {
         var dv = _gel('img_thumb_div');
         if(dv) {
            var img = dv.firstChild;
            if(img) {
               var id = img.id.substring(5);
               ocjx_app_getImgPath(wdv.page_id,id,function(_data) {
                  var data = recjsarray(_data);
                  var fpath = data[0];
                  var image_nm = urlencode(data[1]);
                  var link = '<img src=\"".XOCP_SERVER_SUBDIR."/modules/hris/data/pages/images/page_'+wdv.page_id+'/'+fpath+'/'+image_nm+'\" style=\"margin:4px;\"/>';
                  tinyMCE.execCommand('mceInsertContent',false,link);
               });
            }
         }
      }
      
      document.load_thumbnail = function(image_id,uniqid,image_nm) {
         var dv = _gel('img_thumb_div');
         dv.innerHTML = '';
         _destroy(dv.img);
         dv.img = dv.appendChild(_dce('img'));
         dv.img.setAttribute('id','impr_'+image_id);
         dv.img.src = '".XOCP_SERVER_SUBDIR."/modules/hris/page_img_thumb.php?page_id='+wdv.page_id+'&image_id='+image_id+'&f='+uniqid;
         _gel('img_name').innerHTML = image_nm;
      };
      
      function next_image(t,d,e) {
         var dv = _gel('img_thumb_div');
         if(dv) {
            if(!dv.img&&dv.childNodes.length>0) {
               dv.img = dv.firstChild;
            }
            if(!dv.img) return;
            var id = dv.img.id;
            ocjx_app_getNextImage(wdv.page_id,id,t,function(_data) {
               if(_data=='EMPTY') return;
               var data = recjsarray(_data);
               document.load_thumbnail(data[0],data[1],data[2]);
            });
         }
      }
      
      
      
      
      
      // --></script>";
   }
   
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $ret = $this->listPage();
            break;
         default:
            $ret = $this->listPage();
            break;
      }
      return $ret."<div style='height:100px;'>&nbsp;</div>";
   }
}

} // HRIS_PAGESEDITOR_DEFINED
?>