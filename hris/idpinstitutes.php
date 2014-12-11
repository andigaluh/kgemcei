<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/editjobclass.php                              //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_IDPINSTITUTES_DEFINED') ) {
   define('HRIS_IDPINSTITUTES_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_IDPInstitutes extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_IDPINSTITUTES_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_IDPINSTITUTES_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_IDPInstitutes($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function browser() {
      $ret = $this->listAction();
      return $ret;
   }
   
   function listAction() {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_idpinstitutes.php");
      $ajax = new _hris_class_IDPInstitutesAjax("ocjx");
      
      $sql = "SELECT institute_id,institute_nm FROM ".XOCP_PREFIX."idp_institutes WHERE status_cd = 'normal' ORDER BY institute_nm";
      $result = $db->query($sql);
      $ret = "<table class='xxlist' style='width:100%;' align='center'><thead><tr><td>"
           . "<table style='width:100%;border-spacing:0px;'><tbody><tr><td>"
           . "List of IDP Provider"
           . "</td><td style='text-align:right;'>"
           . "<input type='button' value='"._ADD."' onclick='edit_institute(\"new\",this,event);'/>"
           . "</td></tr></tbody></table>"
           . "</td></tr></thead><tbody id='tbproc'>";
      if($db->getRowsNum($result)>0) {
         while(list($institute_id,$institute_nm)=$db->fetchRow($result)) {
            $ret .= "<tr><td id='tdclass_${institute_id}'>"
                  . "<table border='0' class='ilist'>"
                  . "<colgroup><col/></colgroup>"
                  . "<tbody><tr>"
                  . "<td><span onclick='edit_institute(\"$institute_id\",this,event);' class='xlnk'>".htmlentities(stripslashes($institute_nm))."</span></td>"
                  . "</tr></tbody></table>"
                  . "</td></tr>";
         
         }
         $ret .= "<tr style='display:none;' id='trempty'><td>"._EMPTY."</td></tr>";
      } else {
         $ret .= "<tr id='trempty'><td>"._EMPTY."</td></tr>";
      }
      
      $ret .= "</tbody></table><div style='margin-bottom:200px;'>&nbsp;</div>";
      
      return $ret.$ajax->getJs()."<script type='text/javascript'><!--
      
      function back_compgroup(d,e) {
         ocjx_app_browseCompetency(0,function(_data) {
            var d = $('btn_add_competency');
            cb.sub.innerHTML = _data;
            cb.style.display = '';
            cb.style.left = parseInt(oX(d)+d.offsetWidth-cb.offsetWidth)+'px';
         });
      }
      
      function do_delete_rel(rel_id) {
         var dv = $('comprel_'+rel_id);
         _destroy(dv);
         ocjx_app_deleteCompetencyRel(wdv.institute_id,rel_id,function(_data) {
            
         });
      }
      
      function cancel_delete_rel(rel_id) {
         var dv = $('comprel_'+rel_id);
         _destroy(dv.confirm);
      }
      
      function delete_comprel(rel_id) {
         var dv = $('comprel_'+rel_id);
         dv.rel_id = rel_id;
         dv.confirm = dv.appendChild(_dce('div'));
         dv.confirm.setAttribute('style','padding:10px;background-color:#ffcccc;text-align:left;');
         dv.confirm.innerHTML = 'Do you want to delete this competency upgrade relation?'
                              + '<br/><br/>'
                              + '<input type=\"button\" value=\"Yes (delete)\" onclick=\"do_delete_rel(\\''+rel_id+'\\');\"/>&nbsp;'
                              + '<input type=\"button\" value=\"No\" onclick=\"cancel_delete_rel(\\''+rel_id+'\\');\"/>&nbsp;';
      }
      
      function add_comp_select_competency(compgroup_id,competency_id,d,e) {
         d.innerHTML = '';
         d.appendChild(progress_span());
         ocjx_app_addCompetency(competency_id,wdv.institute_id,function(_data) {
            var data = recjsarray(_data);
            cb.style.display = 'none';
            $('comprel_empty').style.display = 'none';
            var dv = $('complist').insertBefore(_dce('div'),$('complist').firstChild);
            dv.setAttribute('style','border-top:1px solid #ddd;padding:2px;');
            dv.setAttribute('id','comprel_'+data[1]);
            dv.innerHTML = data[0];
         });
      }
      
      function add_comp_select_group(compgroup_id,d,e) {
         d.innerHTML = '';
         d.appendChild(progress_span());
         ocjx_app_browseCompetency(compgroup_id,function(_data) {
            cb.sub.innerHTML = _data;
            var btn = $('btn_add_competency');
            cb.style.left = parseInt(oX(btn)+btn.offsetWidth-cb.offsetWidth)+'px';
         });
      }
      
      var cb = null;
      function add_browse_competency(d,e) {
         if(!cb) {
            cb = _dce('div');
            cb.setAttribute('style','position:absolute;display:none;min-width:300px;padding:5px;background-color:#fff;border:1px solid #bbb;');
            cb.sub = cb.appendChild(_dce('div'));
            cb.sub.setAttribute('style','border:0px;');
            cb.sub.appendChild(progress_span());
            cb = document.body.appendChild(cb);
         }
         if(cb.style.display=='none') {
            cb.style.top = parseInt(oY(d)+d.offsetHeight)+'px';
            cb.style.left = parseInt(oX(d)+d.offsetWidth-cb.offsetWidth)+'px';
            ocjx_app_browseCompetency(0,function(_data) {
               cb.sub.innerHTML = _data;
               cb.style.display = '';
               cb.style.left = parseInt(oX(d)+d.offsetWidth-cb.offsetWidth)+'px';
            });
         } else {
            cb.style.display = 'none';
         }
      }
      
      var wdv = null;
      function edit_institute(institute_id,d,e) {
         if(wdv) {
            if(wdv.institute_id != 'new' && wdv.institute_id == institute_id) {
               cancel_edit();
               return;
            } else {
               cancel_edit();
            }
         }
         wdv = _dce('div');
         wdv.institute_id = institute_id;
         if(institute_id=='new') {
            var tre = $('trempty');
            var tr = _dce('tr');
            var td = tr.appendChild(_dce('td'));
            tr = tre.parentNode.insertBefore(tr,tre.parentNode.firstChild);
         } else {
            var td = $('tdclass_'+institute_id);
         }
         wdv.setAttribute('style','padding:10px;');
         wdv = td.appendChild(wdv);
         wdv.appendChild(progress_span());
         wdv.td = td;
         $('trempty').style.display = 'none';
         ocjx_app_editInstitute(institute_id,function(_data) {
            wdv.innerHTML = _data;
            $('inp_institute_nm').focus();
         });
      }
      
      function cancel_edit() {
         wdv.td.style.backgroundColor = '';
         if(wdv.institute_id=='new') {
            _destroy(wdv.td.parentNode);
         }
         wdv.institute_id = null;
         _destroy(wdv);
         wdv = null;
      }
      
      function delete_action() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '#ffcccc';
         wdv.oldHTML = wdv.innerHTML;
         wdv.innerHTML = 'Are you sure you want to delete this institute?<br/><br/>'
                       + '<input type=\"button\" value=\""._CANCEL."\" onclick=\"cancel_delete();\"/>&nbsp;&nbsp;'
                       + '<input type=\"button\" value=\""._DELETE."\" onclick=\"do_delete();\"/>';
      }
      
      function cancel_delete() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '';
         wdv.innerHTML = wdv.oldHTML;
      }
      
      function do_delete() {
         ocjx_app_deleteInstitute(wdv.institute_id,null);
         var tr = wdv.parentNode.parentNode;
         _destroy(tr);
         wdv.institute_id = null;
         wdv = null;
      }
      
      function save_institute() {
         var institute_id = wdv.institute_id;
         var institute_nm = $('inp_institute_nm').value;
         var institute_addr = $('inp_institute_addr').value;
         var institute_phone = $('inp_institute_phone').value;
         var institute_web = $('inp_institute_web').value;
         var institute_contact = $('inp_institute_contact').value;
         wdv.innerHTML = '';
         wdv.appendChild(progress_span());
         ocjx_app_saveInstitute(institute_id,institute_nm,institute_addr,institute_phone,institute_web,institute_contact,function(_data) {
            var td = wdv.td;
            wdv.institute_id = null;
            _destroy(wdv);
            wdv = null;
            var data = recjsarray(_data);
            td.setAttribute('id',data[0]);
            td.innerHTML = data[1];
         });
      }
      
      // --></script>";
   }
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $ret = $this->browser();
            break;
         default:
            $ret = $this->browser();
            break;
      }
      return $ret."<div style='height:100px;'>&nbsp;</div>";
   }
}

} // HRIS_IDPINSTITUTES_DEFINED
?>
