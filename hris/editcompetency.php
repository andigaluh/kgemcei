<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/editcompetency.php                           //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_EDITCOMPETENCY_DEFINED') ) {
   define('HRIS_EDITCOMPETENCY_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_EditCompetency extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_EDITCOMPETENCY_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_EDITCOMPETENCY_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_EditCompetency($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function listCompetency() {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_editcompetency.php");
      $ajax = new _hris_class_EditCompetencyAjax("ocjx");
      
      $sql = "SELECT a.competency_id,a.competency_cd,a.competency_nm,a.desc_en,"
           . "b.compgroup_nm,a.competency_class,a.competency_abbr,(a.competency_class+0) as urut"
           . " FROM ".XOCP_PREFIX."competency a"
           . " LEFT JOIN ".XOCP_PREFIX."compgroup b USING(compgroup_id)"
           . " WHERE a.status_cd = 'normal'"
           . " ORDER BY a.compgroup_id,urut,a.competency_cd,a.competency_abbr,a.competency_id";
      
      $result = $db->query($sql);
      $ret = "<table class='xxlist' style='width:100%;' align='center'>"
           . "<thead><tr><td>"
           . "Competency</td><td style='text-align:right;'>"
           . "Cari : <input type='text' id='qcomp' style='width:200px;' value=''/>&nbsp;&nbsp;"
           . "<input onclick='print_all_competency(this,event);' type='button' value='Print All'/>&nbsp;"
           . "<input onclick='edit_competency(\"new\",this,event);' type='button' value='"._ADD."'/>"
//           . "&nbsp;&nbsp;Search: <input type='text'/>"
           . "</td></tr></thead>"
           . "<tbody><tr><td colspan='2' id='tdcompetency'>";
      if($db->getRowsNum($result)>0) {
         while(list($competency_id,$competency_cd,$competency_nm,$desc_en,$compgroup_nm,$competency_class,$competency_abbr)=$db->fetchRow($result)) {
            if(trim($competency_nm)=="") {
               $competency_nm = "[noname]";
            }
            $ret .= "<div id='dvcompetency_${competency_id}' class='sb'>"
                  . "<a name='compeditortop_${competency_id}'>"
                  . "<table style='border:0px;width:100%;'>"
                  . "<colgroup><col width='50'/><col/><col width='75'/><col width='75'/></colgroup><tbody>"
                  . "<tr><td>$competency_abbr</td>"
                      . "<td><div style='overflow:hidden;width:340px;'><div style='width:900px;'><span id='sp_${competency_id}' class='xlnk' onclick='edit_competency(\"$competency_id\",this,event);'>".htmlentities(stripslashes($competency_nm))."</span></div></div></td>"
                      . "<td><div style='overflow:hidden;width:75px;'><div style='width:900px;'>$compgroup_nm</div></div></td>"
                      . "<td><div style='overflow:hidden;width:75px;'><div style='width:900px;'>$competency_class</div></div></td>"
                  . "</tr></tbody></table></div>";
         }
      }
      $ret .= "</td></tr>";
      $ret .= "<tr style='display:none;' id='trempty'><td colspan='2'>"._EMPTY."</td></tr>";
      $ret .= "</tbody></table><div style='padding-top:200px;'>&nbsp;</div>";
      
      return $ret.$ajax->getJs()."<script type='text/javascript'><!--
      
      var qcomp = _gel('qcomp');
      qcomp._get_param=function() {
         var qval = this.value;
         qval = trim(qval);
         if(qval.length < 2) {
            return '';
         }
         return qval;
      };
      qcomp._onselect=function(resId) {
         qcomp._reset();
         qcomp._showResult(false);
         edit_competency(resId,null,null);
      };
      qcomp._send_query = ocjx_app_searchCompetency;
      _make_ajax(qcomp);
      qcomp.focus();
      
      function save_leveltitle() {
         var txt_en = urlencode($('level_en').value);
         var txt_id = urlencode($('level_id').value);
         leveltitle.innerHTML = '';
         leveltitle.appendChild(progress_span());
         ocjx_app_saveLevelTitle(leveltitle.competency_id,leveltitle.pl,txt_en,txt_id,function(_data) {
            leveltitle.innerHTML = _data;
            leveltitle.oldHTML = null;
            leveltitle.pl = null;
            leveltitle.competency_id = null;
            leveltitle = null;
         });
      }
      
      function cancel_editleveltitle() {
         if(leveltitle) {
            leveltitle.innerHTML = leveltitle.oldHTML;
            leveltitle.pl = null;
            leveltitle.competency_id = null;
            leveltitle = null;
         }
      }
      
      var leveltitle = null;
      function editleveltitle(competency_id,pl,d,e) {
         cancel_editleveltitle();
         var dv = $('tdlttl'+pl);
         leveltitle = dv;
         leveltitle.pl = pl;
         leveltitle.competency_id = competency_id;
         leveltitle.oldHTML = leveltitle.innerHTML;
         leveltitle.innerHTML = '';
         leveltitle.appendChild(progress_span());
         ocjx_app_editLevelTitle(competency_id,pl,function(_data) {
            var data = recjsarray(_data);
            leveltitle.innerHTML = data[2];
            $('level_en').focus();
         });
      }
      
      function cancel_delete_qa(pl,bhid,ca_id) {
         var dv = $('qa'+pl+'_'+bhid+'_'+ca_id);
         dv.innerHTML = dv.oldHTML;
         dv.style.backgroundColor = '';
         dv.oldHTML = null;
      }
      
      function delete_qa(pl,bhid,ca_id,d,e) {
         var dv = $('qa'+pl+'_'+bhid+'_'+ca_id);
         dv.oldHTML = dv.innerHTML;
         dv.style.backgroundColor = '#ffcccc';
         dv.innerHTML = '<div style=\"text-align:center;padding:5px;\">Are your sure you want to delete this question/answer?'
                      + '<br/>[<span class=\"xlnk\" onclick=\"do_delete_qa(\\''+pl+'\\',\\''+bhid+'\\',\\''+ca_id+'\\',this,event);\">Yes</span>]'
                      + '&nbsp;[<span class=\"xlnk\" onclick=\"cancel_delete_qa(\\''+pl+'\\',\\''+bhid+'\\',\\''+ca_id+'\\');\">No</span>]</div>';
      }

      function do_delete_qa(pl,bhid,ca_id,d,e) {
         var dv = $('qa'+pl+'_'+bhid+'_'+ca_id);
         _destroy(dv);
         var cnt = $('bhqitem'+pl+'_'+bhid).childNodes.length;
         if(cnt==1) {
            $('qa'+pl+'_'+bhid+'_empty').style.display = '';
         }
         ocjx_app_deleteQA(wdv.competency_id,pl,bhid,ca_id,function(_data) {
         });
      }

      function edit_qa(pl,bhid,ca_id,d,e) {
         cancel_editbehaviour();
         cancel_qaedit();
         qaeditor = $('qa'+pl+'_'+bhid+'_'+ca_id);
         qaeditor.oldHTML = qaeditor.innerHTML;
         qaeditor.innerHTML = '';
         qaeditor.appendChild(progress_span());
         qaeditor.pl = pl;
         qaeditor.bhid = bhid;
         qaeditor.ca_id = ca_id;
         ocjx_app_editQA(wdv.competency_id,pl,bhid,ca_id,function(_data) {
            var data = recjsarray(_data);
            qaeditor.innerHTML = data[3];
         });
      }

      function save_qa(pl,bhid,ca_id,d,e) {
         var ret = parseForm('qaform');
         qaeditor.pl = null;
         qaeditor.bhid = null;
         qaeditor.ca_id = null;
         qaeditor.oldHTML = null;
         qaeditor.innerHTML = '';
         qaeditor.appendChild(progress_span());
         qaeditor = null;
         ocjx_app_saveQA(wdv.competency_id,pl,bhid,ca_id,ret,function(_data) {
            var data = recjsarray(_data);
            var qa = $('qa'+data[0]+'_'+data[1]+'_'+data[2]);
            qa.innerHTML = data[3];
         });
      }
      
      function cancel_qaedit() {
         if(qaeditor) {
            var pl = qaeditor.pl;
            var bhid = qaeditor.bhid;
            var ca_id = qaeditor.ca_id;
            if(qaeditor.oldHTML) {
               qaeditor.innerHTML = qaeditor.oldHTML;
            } else {
               qaeditor.innerHTML = '...';
            }
            qaeditor.pl = null;
            qaeditor.bhid = null;
            qaeditor.ca_id = null;
            qaeditor.oldHTML = null;
            qaeditor = null;
            ocjx_app_vQA(wdv.competency_id,pl,bhid,ca_id,function(_data) {
               var data = recjsarray(_data);
               var qa = $('qa'+data[0]+'_'+data[1]+'_'+data[2]);
               qa.innerHTML = data[3];
            });
         }
      }
      
      qaeditor = null;
      function add_qa(pl,bhid,d,e) {
         cancel_editbehaviour();
         cancel_qaedit();
         var dv = _dce('div');
         var pdv = $('bhqitem'+pl+'_'+bhid);
         var emptyx = $('qa'+pl+'_'+bhid+'_empty');
         emptyx.style.display = 'none';
         dv = pdv.insertBefore(dv,emptyx);
         dv.setAttribute('class','qa');
         dv.appendChild(progress_span());
         qaeditor = dv;
         qaeditor.pl = pl;
         qaeditor.bhid = bhid;
         qaeditor.ca_id = 'new';
         ocjx_app_editQA(wdv.competency_id,pl,bhid,'new',function(_data) {
            var data = recjsarray(_data);
            qaeditor.setAttribute('id','qa'+data[0]+'_'+data[1]+'_'+data[2]);
            qaeditor.ca_id = data[2];
            qaeditor.innerHTML = data[3];
         });
      }
      
      function delete_behaviour(pl,bhid,d,e) {
         var td = $('tdbhtxt'+pl+'_'+bhid);
         td.style.backgroundColor = '#ffcccc';
         td.oldHTML = td.innerHTML;
         td.innerHTML = '<div style=\"text-align:center;padding:5px;\">Are you sure you want to delete this behaviour?'
                      + '<br/>[<span class=\"xlnk\" onclick=\"do_delete_behaviour(\\''+pl+'\\',\\''+bhid+'\\',this,event);\">Yes</span>]'
                      + '&nbsp;[<span class=\"xlnk\" onclick=\"cancel_delete_behaviour(\\''+pl+'\\',\\''+bhid+'\\');\">No</span>]</div>';
      }
      
      function cancel_delete_behaviour(pl,bhid) {
         var td = $('tdbhtxt'+pl+'_'+bhid);
         td.innerHTML = td.oldHTML;
         td.style.backgroundColor = '';
         td.oldHTML = null;
      }
      
      function do_delete_behaviour(pl,bhid,d,e) {
         var bh = $('bh'+pl+'_'+bhid);
         _destroy(bh);
         var cnt = $('bh'+pl).childNodes.length;
         if(cnt==1) {
            $('bh'+pl+'_empty').style.display = '';
         }
         ocjx_app_deleteBehaviour(wdv.competency_id,pl,bhid,function(_data) {
         });
      }
      
      function save_behaviour() {
         var pl = bheditor.pl;
         var bhid = bheditor.bhid;
         var bhtxt_en = urlencode($('bhtxt_en').value);
         var bhtxt_id = urlencode($('bhtxt_id').value);
         bheditor.txt.innerHTML = '';
         bheditor.txt.appendChild(progress_span());
         bheditor.pl = null;
         bheditor.bhid = null;
         bheditor.txt.oldHTML = null;
         bheditor.txt = null;
         bheditor = null;
         ocjx_app_saveBehaviour(wdv.competency_id,pl,bhid,bhtxt_en,bhtxt_id,function(_data) {
            var data = recjsarray(_data);
            $('tdbhtxt'+data[0]+'_'+data[1]).innerHTML = data[2];
         });
      }
      
      function cancel_editbehaviour() {
         if(bheditor) {
            var pl = bheditor.pl;
            var bhid = bheditor.bhid;
            bheditor.pl = null;
            bheditor.bhid = null;
            if(bheditor.txt.oldHTML) {
               bheditor.txt.innerHTML = bheditor.txt.oldHTML;
            } else {
               bheditor.txt.innerHTML = '&nbsp;<br/>....';
            }
            bheditor.txt.oldHTML = null;
            bheditor.txt = null;
            bheditor = null;
            ocjx_app_vBehaviour(wdv.competency_id,pl,bhid,function(_data) {
               var data = recjsarray(_data);
               $('tdbhtxt'+data[0]+'_'+data[1]).innerHTML = data[2];
            });
         }
      }
      
      bheditor = null;
      function addbehaviour(competency_id,pl,d,e) {
         if(bheditor) {
            cancel_editbehaviour();
         }
         var emptyx = $('bh'+pl+'_empty');
         var dv = _dce('div');
         dv.setAttribute('class','behaviouritem');
         dv = $('bh'+pl).insertBefore(dv,emptyx);
         dv.innerHTML = '';
         dv.appendChild(progress_span());
         bheditor = dv;
         emptyx.style.display = 'none';
         bheditor.pl = pl;
         if(wdv.competency_id=='new') {
            var competency_nm = urlencode($('inp_competency_nm').value);
            var competency_cd = urlencode($('inp_competency_cd').value);
            var competency_abbr = urlencode($('inp_competency_abbr').value);
            
            var scompclass = $('scompclass');
            var competency_class = scompclass.options[scompclass.selectedIndex].value;
            //var scompgroup = $('scompgroup');
            //var compgroup_id = scompgroup.options[scompgroup.selectedIndex].value;
            var compgroup_id = 1;
            var rcompgroup = $('rcompgroup');
            alert(rcompgroup);
            
            var desc_en = urlencode($('desc_en').value);
            var desc_id = urlencode($('desc_id').value);
            ocjx_app_saveCompetency(wdv.competency_id,competency_nm,desc_en,competency_cd,competency_class,compgroup_id,competency_abbr,desc_id,function(_data) {
               var data = recjsarray(_data);
               wdv.dv.setAttribute('id',data[0]);
               wdv.competency_id = data[0].substring(13);
               ocjx_app_addBehaviour(wdv.competency_id,bheditor.pl,function(_data) {
                  var data = recjsarray(_data);
                  bheditor.innerHTML = data[1];
                  bheditor.setAttribute('id',data[0]);
                  bheditor.bhid = data[2];
                  bheditor.txt = $('tdbhtxt'+bheditor.pl+'_'+data[2]);
                  $('bhtxt_en').focus();
               });
            });
         } else {
            ocjx_app_addBehaviour(wdv.competency_id,pl,function(_data) {
               var data = recjsarray(_data);
               bheditor.innerHTML = data[1];
               bheditor.setAttribute('id',data[0]);
               bheditor.bhid = data[2];
               bheditor.txt = $('tdbhtxt'+bheditor.pl+'_'+data[2]);
               $('bhtxt_en').focus();
            });
         }
      }
      
      function editbehaviour(pl,bhid,d,e) {
         if(bheditor) {
            cancel_editbehaviour();
         }
         var dv = $('bh'+pl+'_'+bhid);
         bheditor = dv;
         bheditor.pl = pl;
         bheditor.bhid = bhid;
         bheditor.txt = $('tdbhtxt'+pl+'_'+bhid);
         bheditor.txt.oldHTML = bheditor.txt.innerHTML;
         bheditor.txt.innerHTML = '';
         bheditor.txt.appendChild(progress_span());
         ocjx_app_editBehaviour(wdv.competency_id,bhid,pl,function(_data) {
            var data = recjsarray(_data);
            bheditor.txt.innerHTML = data[1];
            $('bhtxt_en').focus();
         });
      }
      
      function print_all_competency() {
         location.href = '".XOCP_SERVER_SUBDIR."/modules/hris/print/competency.php?all=1';
      }
      
      function print_competency() {
         location.href = '".XOCP_SERVER_SUBDIR."/modules/hris/print/competency.php?cid='+wdv.competency_id;
      }
      
      var wdv = null;
      function edit_competency(competency_id,d,e) {
         if(wdv) {
            if(wdv.competency_id != 'new' && wdv.competency_id == competency_id) {
               cancel_edit();
               return;
            } else {
               cancel_edit();
            }
         }
         wdv = _dce('div');
         wdv.competency_id = competency_id;
         if(competency_id=='new') {
            var dv = _dce('div');
            dv = $('tdcompetency').insertBefore(dv,$('tdcompetency').firstChild);
            dv.setAttribute('class','sb');
         } else {
            var dv = $('dvcompetency_'+competency_id);
         }
         wdv.setAttribute('style','padding:10px;');
         wdv = dv.appendChild(wdv);
         wdv.appendChild(progress_span());
         wdv.dv = dv;
         ocjx_app_editCompetency(competency_id,function(_data) {
            var data = recjsarray(_data);
            wdv.competency_id = data[0];
            wdv.innerHTML = data[1];
            wdv.dv.setAttribute('id','dvcompetency_'+data[0]);
            if(data[2]!='EMPTY') {
               wdv.dv.innerHTML = data[2] + wdv.dv.innerHTML;
            }
            location.hash = 'compeditortop_'+wdv.competency_id;
            $('inp_competency_nm').focus();
         });
      }
      
      function cancel_edit() {
         competency_id = wdv.competency_id;
         wdv.dv.style.backgroundColor = '';
         if(wdv.competency_id=='new') {
            _destroy(wdv.dv);
         }
         wdv.dv = null;
         wdv.competency_id = null;
         _destroy(wdv);
         wdv = null;
         ocjx_app_vCompetency(competency_id,function(_data) {
            var data = recjsarray(_data);
            $('dvcompetency_'+data[0]).innerHTML = data[1];
         });
      }
      
      function delete_competency() {
         var dv = wdv.dv;
         dv.style.backgroundColor = '#ffcccc';
         wdv.oldHTML = wdv.innerHTML;
         wdv.innerHTML = 'Are you sure you want to delete this competency?<br/><br/>'
                       + '<input type=\"button\" value=\""._CANCEL."\" onclick=\"cancel_delete();\"/>&nbsp;&nbsp;'
                       + '<input type=\"button\" value=\""._DELETE."\" onclick=\"do_delete();\"/>';
      }
      
      function cancel_delete() {
         var dv = wdv.dv;
         dv.style.backgroundColor = '';
         wdv.innerHTML = wdv.oldHTML;
      }
      
      function do_delete() {
         ocjx_app_Delete(wdv.competency_id,null);
         var dv = wdv.dv;
         _destroy(dv);
         wdv.dv = null;
         wdv.competency_id = null;
         wdv = null;
      }
      
      function save_competency() {
         var ret = parseForm('frm');
         wdv.innerHTML = '';
         wdv.appendChild(progress_span());
         ocjx_app_saveCompetency(wdv.competency_id,ret,function(_data) {
            var dv = wdv.dv;
            wdv.competency_id = null;
            _destroy(wdv);
            wdv = null;
            if(_data=='EMPTY') {
               alert('Update failed.');
            }
            var data = recjsarray(_data);
            dv.setAttribute('id',data[0]);
            dv.innerHTML = data[1];
         });
      }
      
      // --></script>";
   }
   
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $this->listCompetency();
            break;
         default:
            $ret = $this->listCompetency();
            break;
      }
      return $ret;
   }
}

} // HRIS_EDITCOMPETENCY_DEFINED
?>
