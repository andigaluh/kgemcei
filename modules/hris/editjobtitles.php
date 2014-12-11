<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/editjobtitles.php                           //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_EDITJOBTITLES_DEFINED') ) {
   define('HRIS_EDITJOBTITLES_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_EditJobTitles extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_EDITJOBTITLES_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_EDITJOBTITLES_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_EditJobTitles($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function listJobTitles() {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_editjobtitles.php");
      $ajax = new _hris_class_EditJobTitlesAjax("jobjx");
      $ajax->setReqPOST();
      
      $sql = "SELECT a.job_id,a.job_cd,a.job_nm,a.description,"
           . "b.org_nm,c.org_class_nm,a.job_abbr,(d.job_level+0) as srt"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."orgs b USING(org_id)"
           . " LEFT JOIN ".XOCP_PREFIX."org_class c USING(org_class_id)"
           . " LEFT JOIN ".XOCP_PREFIX."job_class d ON d.job_class_id = a.job_class_id"
           . " WHERE a.status_cd = 'normal'"
           . " ORDER BY d.job_class_level, b.org_nm,d.gradeval_bottom DESC,srt DESC,a.job_nm";
      
      $result = $db->query($sql);
      $ret = "<table class='xxlist' style='width:100%;' align='center'>"
           . "<thead><tr><td>"
           . "<span style='float:left;'>Job Titles</span>"
           . "<span style='float:right;'>"
           . "Cari : <input type='text' id='qjob' style='width:200px;' value=''/>&nbsp;&nbsp;"
           . "<input onclick='print_all_job(this,event);' type='button' value='Print All'/>&nbsp;"
           . "<input onclick='edit_job(\"new\",this,event);' type='button' value='"._ADD."'/></span></td></tr></thead>"
           . "<tbody><tr><td id='tdjobs'>";
      if($db->getRowsNum($result)>0) {
         while(list($job_id,$job_cd,$job_nm,$description,$org_nm,$org_class_nm,$job_abbr)=$db->fetchRow($result)) {
            $ret .= "<div id='dvjob_${job_id}' class='sb'>"
                  . $ajax->renderJob($job_id,$job_nm,$job_abbr,$org_class_nm,$org_nm)
                  . "</div>";
         }
      }
      $ret .= "</td></tr>";
      $ret .= "<tr style='display:none;' id='trempty'><td>"._EMPTY."</td></tr>";
      $ret .= "</tbody></table>";
      
      $themecss = tinycss(getTheme());
      $_SESSION["html"]->js_tinymce = TRUE;
      
      return $ret.$ajax->getJs()."<script type='text/javascript'><!--
      
      var qjob = _gel('qjob');
      qjob._get_param=function() {
         var qval = this.value;
         qval = trim(qval);
         if(qval.length < 2) {
            return '';
         }
         return qval;
      };
      qjob._onselect=function(resId) {
         qjob._reset();
         qjob._showResult(false);
         edit_job(resId,null,null);
      };
      qjob._send_query = jobjx_app_searchJob;
      _make_ajax(qjob);
      qjob.focus();
      
      
      
      function toggleComp(d,e) {
         if($('dvcomplist').style.display=='none') {
            $('btntogglecomp').className = 'xaction';
         } else {
            $('btntogglecomp').className = '';
         }
         new Effect.toggle($('dvcomplist'),'appear',{duration:0.5,afterFinish:function(o) {
            if($('dvcomplist').style.display=='none') {
               $('btntogglecomp').className = '';
            } else {
               $('btntogglecomp').className = 'xaction';
            }
         }}); 
      }
      
      function toggleStructure(job_id,d,e) {
         if($('dvstructure_'+job_id).style.display=='none') {
            $('btntogglestruct').className = 'xaction';
         } else {
            $('btntogglestruct').className = '';
         }
         new Effect.toggle($('dvstructure_'+job_id),'appear',{duration:0.5,afterFinish:function(o) {
            if($('dvstructure_'+job_id).style.display=='none') {
               $('btntogglestruct').className = '';
            } else {
               $('btntogglestruct').className = 'xaction';
            }
         }}); 
      }
      
      var ctru = null;
      function refreshStructure(job_id,d,e) {
         ctru = job_id;
         if($('dvstructure_'+job_id).style.display=='none') {
            $('btntogglestruct').className = 'xaction';
         } else {
            $('btntogglestruct').className = '';
         }
         jobjx_app_refreshStructure(job_id,function(_data) {
            if($('dvstructure_'+job_id)&&$('dvstructure_'+job_id).style.display=='none') {
               $('dvstructure_'+job_id).innerHTML = _data;
            }
            
            new Effect.toggle($('dvstructure_'+job_id),'appear',{duration:0.5,afterFinish:function(o) {
               if($('dvstructure_'+job_id).style.display=='none') {
                  $('btntogglestruct').className = '';
               } else {
                  $('btntogglestruct').className = 'xaction';
               }
            }}); 
            
            
         });
      }
      
      function init_my_tiny() {
         if($('summary')&&$('description')) {
            tinyMCE.init({
               mode : 'exact',
               elements : 'description,summary,description_id_txt,summary_id_txt',
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
      
      
      
      function dlcompgroup(compgroup_id,d,e) {
         $('compgroupitem_'+compgroup_id).innerHTML = '';
         $('compgroupitem_'+compgroup_id).appendChild(progress_span());
         var job_id = wdv.job_id;
         if(wdv.job_id=='new') {
            job_id = wdv.new_job_id;
         }
         jobjx_app_downloadCompetency(job_id,compgroup_id,function(_data) {
            if(_data!='EMPTY') {
               var data = recjsarray(_data);
               $('compgroupitem_'+data[0]).innerHTML = data[1];
               $('emptycomp_'+data[0]).style.display = 'none';
            }
         });
      }
      
      function detachcompjob(d,e) {
         jcompedit.oldHTML = jcompedit.innerHTML;
         jcompedit.parentdv.style.backgroundColor = '#ffcccc';
         jcompedit.innerHTML = '<div style=\"padding:5px;text-align:center;\">Are you sure you want to detach this competency?<br/<br/>'
                             + '<input type=\"button\" value=\"Yes\" onclick=\"suredetach();\"/>&nbsp;&nbsp;'
                             + '<input type=\"button\" value=\"No\" onclick=\"canceldetach();\"/></div>';
      }
      
      function suredetach() {
         jobjx_app_detachCompetency(jcompedit.competency_id,jcompedit.job_id,null);
         _destroy(jcompedit);
         _destroy(jcompedit.parentdv);
         if($('compgroupitem_'+jcompedit.compgroup_id).childNodes.length<=0) {
            $('emptycomp_'+jcompedit.compgroup_id).style.display = '';
         }
         jcompedit.parentdv = null;
         jcompedit.job_id = null;
         jcompedit.competency_id = null;
         jcompedit = null;
      }
      
      function canceldetach() {
         jcompedit.parentdv.style.backgroundColor = '#eeeeff';
         jcompedit.innerHTML = jcompedit.oldHTML;
      }
      
      function savecompjob(d,e) {
         var ret = parseForm('compfrm');
         jcompedit.innerHTML = '';
         jcompedit.appendChild(progress_span('... saving'));
         jobjx_app_saveCompetencyProperty(jcompedit.competency_id,jcompedit.job_id,ret,function(_data) {
            var data = recjsarray(_data);
            $('rclcomp_'+jcompedit.competency_id).innerHTML = data[0];
            $('itjcomp_'+jcompedit.competency_id).innerHTML = data[1];
            _destroy(jcompedit);
            jcompedit.parentdv.style.backgroundColor = '';
            jcompedit.parentdv = null;
            jcompedit.job_id = null;
            jcompedit.competency_id = null;
            jcompedit = null;
         });
      }
      
      var jcompedit = null;
      function editjobcomp(competency_id,d,e) {
         if(jcompedit) {
            if(jcompedit.competency_id==competency_id&&jcompedit.job_id==wdv.job_id) {
               _destroy(jcompedit);
               jcompedit.parentdv.style.backgroundColor = '';
               jcompedit.parentdv = null;
               jcompedit.job_id = null;
               jcompedit.competency_id = null;
               jcompedit = null;
               return;
            } else {
               _destroy(jcompedit);
               jcompedit.parentdv.style.backgroundColor = '';
               jcompedit.parentdv = null;
               jcompedit.job_id = null;
               jcompedit.competency_id = null;
               jcompedit = null;
            }
         }
         jcompedit=$('dvjobcomp_'+competency_id).appendChild(_dce('div'));
         jcompedit.style.padding='5px';
         jcompedit.job_id = wdv.job_id;
         jcompedit.competency_id = competency_id;
         jcompedit.parentdv = $('dvjobcomp_'+competency_id);
         jcompedit.parentdv.style.backgroundColor = '#eeeeff';
         jcompedit.appendChild(progress_span());
         jobjx_app_editCompetencyProperty(competency_id,wdv.job_id,function(_data) {
            if(_data!='EMPTY') {
               var data = recjsarray(_data);
               jcompedit.innerHTML = data[1];
               jcompedit.compgroup_id = data[0];
            }
         });
      }
      
      function initsearchcomp(arr) {
         if(arr.length>0) {
            for(var i=0;i<arr.length;i++) {
               var qcompx = $('qcompetency_'+arr[i]);
               qcompx.compgroup_id = arr[i];
               qcompx._onselect=function(resId) {
                  jobjx_app_addCompetency(resId,this.compgroup_id,wdv.job_id,function(_data) {
                     if(_data=='FAIL') {
                        alert('Insert fail.');
                        return;
                     }
                     var data = recjsarray(_data);
                     var compgroup_id = data[0];
                     var competency_id = data[1];
                     var dv = $('compgroupitem_'+compgroup_id).appendChild(_dce('div'));
                     dv.setAttribute('id','dvjobcomp_'+competency_id);
                     dv.innerHTML = data[2];
                     $('emptycomp_'+compgroup_id).style.display = 'none';
                  });
               };
               qcompx._send_query=function(q,sucess) {
                  jobjx_app_searchCompetency(q,this.compgroup_id,sucess);
               };
               _make_ajax(qcompx);
            }
         }
      }
      
      function print_all_job() {
         location.href = '".XOCP_SERVER_SUBDIR."/modules/hris/print/jobtitle.php?all=1';
      }
      
      function print_job() {
         location.href = '".XOCP_SERVER_SUBDIR."/modules/hris/print/jobtitle.php?jid='+wdv.job_id;
      }
      
      
      var wdv = null;
      function edit_job(job_id,d,e) {
         if(wdv) {
            if(wdv.job_id != 'new' && wdv.job_id == job_id) {
               cancel_edit();
               return;
            } else if(wdv.job_id == 'new' && wdv.new_job_id == job_id) {
               cancel_edit();
               return;
            } else {
               cancel_edit();
            }
         }
         if(job_id=='new') {
            wdv = _dce('div');
            wdv = $('tdjobs').insertBefore(wdv,$('tdjobs').firstChild);
            wdv.setAttribute('class','sb');
         } else {
            wdv = $('dvjob_'+job_id);
         }
         wdv.job_id = job_id;
         wdv.editor = wdv.appendChild(_dce('div'));
         wdv.editor.setAttribute('style','padding:10px;');
         wdv.editor.appendChild(progress_span());
         //alert(job_id);
         //return;
         jobjx_app_editJobTitles(job_id,function(_data) {
            var data = recjsarray(_data);
            wdv.innerHTML = data[0];
            initsearchcomp(data[1]);
            wdv.editor = $('jobeditor');
            if(wdv.job_id=='new') {
               $('inp_job_nm').focus();
               wdv.new_job_id = data[2];
            }
            init_my_tiny();
            location.hash = 'jobeditortop_'+wdv.job_id;
         });
      }
      
      function cancel_edit() {
         wdv.style.backgroundColor = '';
         if(wdv.job_id=='new') {
            _destroy(wdv);
            jobjx_app_cancelNewJob(wdv.new_job_id,null);
         }
         _destroy($('jobeditor'));
         wdv.editor = null;
         wdv.job_id = null;
         wdv = null;
      }
      
      function delete_job() {
         wdv.style.backgroundColor = '#ffcccc';
         wdv.editor.oldHTML = wdv.editor.innerHTML;
         wdv.editor.innerHTML = 'Are you sure you want to delete this job?<br/><br/>'
                       + '<input type=\"button\" value=\""._CANCEL."\" onclick=\"cancel_delete();\"/>&nbsp;&nbsp;'
                       + '<input type=\"button\" value=\""._DELETE."\" onclick=\"do_delete();\"/>';
      }
      
      function cancel_delete() {
         wdv.style.backgroundColor = '';
         wdv.editor.innerHTML = wdv.editor.oldHTML;
      }
      
      function do_delete() {
         jobjx_app_Delete(wdv.job_id,null);
         _destroy(wdv);
         wdv.job_id = null;
         wdv = null;
      }
      
      function save_job() {
         var ret = parseForm('frm');
         $('progress').innerHTML = '';
         $('progress').appendChild(progress_span(' ... process&nbsp;&nbsp;'));
         var job_id = wdv.job_id;
         if(wdv.job_id=='new') {
            job_id = wdv.new_job_id;
         }
         if($('description')&&$('summary')) {
            var descr = tinyMCE.get('description').getContent();
            descr = urlencode(descr);
            var sumr = tinyMCE.get('summary').getContent();
            sumr = urlencode(sumr);
         } else {
            var descr = '';
            var sumr = '';
         }
         if($('description_id_txt')&&$('summary_id_txt')) {
            var descr_id_txt = tinyMCE.get('description_id_txt').getContent();
            descr_id_txt = urlencode(descr_id_txt);
            var sumr_id_txt = tinyMCE.get('summary_id_txt').getContent();
            sumr_id_txt = urlencode(sumr_id_txt);
         } else {
            var descr_id_txt = '';
            var sumr_id_txt = '';
         }
         jobjx_app_saveJobTitles(job_id,ret,descr,sumr,descr_id_txt,sumr_id_txt,function(_data) {
            if(_data=='EMPTY') {
               alert('Update failed.');
            }
            var data = recjsarray(_data);
            wdv.setAttribute('id',data[0]);
            wdv.innerHTML = data[1][0];
            wdv.editor = $('jobeditor');
            if(wdv.job_id=='new') {
               wdv.job_id = wdv.new_job_id;
            }
            init_my_tiny();
         });
      }
      
      function chjobclass(d,e) {
         var job_class_id = d.options[d.selectedIndex].value;
         var job_id = wdv.job_id;
         if(job_id=='new') {
            job_id = wdv.new_job_id;
         }
         jobjx_app_changeJobClass(job_id,job_class_id,function(_data) {
            $('sassessor').innerHTML = _data;
         });
      }
      
      function chorganization(d,e) {
         var org_id = d.options[d.selectedIndex].value;
         var job_id = wdv.job_id;
         if(job_id=='new') {
            job_id = wdv.new_job_id;
         }
         jobjx_app_changeOrganization(job_id,org_id,function(_data) {
            var data = recjsarray(_data);
            $('sassessor').innerHTML = data[1];
            $('dvo_'+data[0]).innerHTML = data[2];
         });
      }
      
      function chsuperior(d,e) {
         var upper_job_id = d.options[d.selectedIndex].value;
         var job_id = wdv.job_id;
         if(job_id=='new') {
            job_id = wdv.new_job_id;
         }
         jobjx_app_changeSuperior(job_id,upper_job_id,function(_data) {
            var data = recjsarray(_data);
            $('sassessor').innerHTML = data[1];
         });
      }
      
      // --></script>";
   }
   
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $this->listJobTitles();
            break;
         default:
            $ret = $this->listJobTitles();
            break;
      }
      return $ret."<div style='height:100px;'>&nbsp;</div>";
   }
}

} // HRIS_EDITJOBTITLES_DEFINED
?>