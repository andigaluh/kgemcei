<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/editjobclass.php                              //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_IDPDEVLIB_DEFINED') ) {
   define('HRIS_IDPDEVLIB_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_IDPDevelopmentLibrary extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_IDPDEVLIB_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_IDPDEVLIB_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_IDPDevelopmentLibrary($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function browser($prefix="") {
      $ret = $this->listAction($prefix);
      return $ret;
   }
   
   function listAction($qprefix) {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_idpdevlib.php");
      $ajax = new _hris_class_IDPDevelopmentLibraryAjax("ocjx");
      $_SESSION["html"]->registerLoadAction("onLoadSubject");
      
      $_SESSION["html"]->addHeadScript("
      <script type='text/javascript'><!--
      
      var scrollto_method_id = false;
      
      function onLoadSubject() {
         var method_id = getQueryVariable('editsubject');
         if(method_id) {
            scrollto_method_id = method_id;
            edit_method(method_id,null,null);
         } else {
            scrollto_method_id = false;
         }
      }
      
      
      // --></script>
      ");
      
      
      $sql = "SELECT substring(method_nm,1,1) as prf FROM ".XOCP_PREFIX."idp_development_method WHERE status_cd = 'normal' GROUP BY prf ORDER BY prf";
      $result = $db->query($sql);
      $cell = 0;
      $cellmax = 30;
      $tblpref = "";
      $fprefix = "";
      if($db->getRowsNum($result)>0) {
         $tblpref = "<table border='0' style='margin:0px;border-spacing:2px;padding:0px;' id='alpha'><tbody>";
         while(list($prefix)=$db->fetchRow($result)) {
            $prefix = strtoupper($prefix);
            if($fprefix=="") {
               $fprefix = $prefix;
            }
            if($cell==0) {
               $tblpref .= "<tr>";
            }
            if(trim($prefix)=="") {
               $txt_prefix = "_";
            } else {
               $txt_prefix = $prefix;
            }
            $tblpref .= "<td style='background-color:#fff;border:1px solid #444444;padding:4px;-moz-box-shadow:1px 1px 2px #999;-moz-border-radius:5px;'><a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&prefix=".urlencode($prefix)."'>$txt_prefix</a></td>";
            $cell++;
            if($cell>$cellmax) {
               $cell=0;
               $tblpref .= "</tr>";
            }
         }
         $tblpref .= "</tbody></table>";
      }
      
      $ctrl = "<table border='0' width='100%'><tbody><tr>"
            . "<td>$tblpref</td>"
            . "</tr></tbody></table>";
      
      if(!isset($_SESSION["browse_prefix"])) {
         if($qprefix=="") {
            $qprefix = $fprefix;
         }
      } else {
         if($qprefix=="") {
            $qprefix = $_SESSION["browse_prefix"];
         }
      }
      
      $sql = "SELECT a.method_id,a.method_nm,a.method_description"
           . " FROM ".XOCP_PREFIX."idp_development_method a"
           . " WHERE a.status_cd = 'normal' AND a.method_nm LIKE '$qprefix%'"
           . " ORDER BY a.method_nm";
      $result = $db->query($sql);
      $ret = "<table class='xxlist' style='width:100%;' align='center'><thead><tr><td>"
           . "<table style='width:100%;border-spacing:0px;'><tbody><tr><td>"
           . " "
           . "$ctrl"
           . "<div style='padding-left:4px;'><input id='qsubject' value='search' onclick='_dsa(this)' type='text' class='searchBox' style='width:120px;'/></div>"
           . "</td><td style='text-align:right;'>"
           . "<input type='button' value='New Subject' onclick='edit_method(\"new\",this,event);'/>"
           . "</td></tr></tbody></table>"
           . "</td></tr></thead><tbody id='tbproc'>";
      if($db->getRowsNum($result)>0) {
         while(list($method_id,$method_nm,$method_description)=$db->fetchRow($result)) {
            $ret .= "<tr><td id='tdclass_${method_id}'>"
                  . "<table border='0' class='ilist' style='width:100%;'>"
                  . "<colgroup><col/></colgroup>"
                  . "<tbody><tr>"
                  . "<td><span onclick='edit_method(\"$method_id\",this,event);' class='xlnk'>".htmlentities(stripslashes($method_nm))."</span></td>"
                  . "</tr>"
                  . "<tr><td><div style='color:#888;padding:2px;padding-left:20px;font-size:0.9em;'>$method_description</div></td></tr>"
                  . "</tbody></table>"
                  . "</td></tr>";
         
         }
         $ret .= "<tr style='display:none;' id='trempty'><td>"._EMPTY."</td></tr>";
      } else {
         $ret .= "<tr id='trempty'><td>"._EMPTY."</td></tr>";
      }
      
      $ret .= "</tbody></table><div style='margin-bottom:200px;'>&nbsp;</div>";
      
      return $ret.$ajax->getJs()."<script type='text/javascript'><!--
      
      function new_event(method_id) {
         location.href = '".XOCP_SERVER_SUBDIR."/index.php?XP_idpeventm_hris="._HRIS_IDPEVENTMANAGEMENT_BLOCK."&cefs=y&method_id='+method_id;
      }
      
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
         ocjx_app_deleteCompetencyRel(wdv.method_id,rel_id,function(_data) {
            save_subject();
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
         ocjx_app_addCompetency(competency_id,wdv.method_id,function(_data) {
            var data = recjsarray(_data);
            cb.style.display = 'none';
            $('comprel_empty').style.display = 'none';
            var dv = $('complist').insertBefore(_dce('div'),$('complist').firstChild);
            dv.setAttribute('style','border-top:1px solid #ddd;padding:2px;');
            dv.setAttribute('id','comprel_'+data[1]);
            dv.innerHTML = data[0];
            save_subject();
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
      
      function list_event(method_id,d,e) {
         if(wdv.ee) {
            _destroy(wdv.ee);
            wdv.ee = null;
            return;
         }
         wdv.ee = wdv.appendChild(_dce('div'));
         wdv.ee.appendChild(progress_span());
         ocjx_app_eventList(method_id,function(_data) {
            wdv.ee.innerHTML = _data;
         });
      }
      
      var wdv = null;
      function edit_method(method_id,d,e) {
         if(wdv) {
            if(wdv.ee) {
               _destroy(wdv.ee);
            }
            if(wdv.method_id != 'new' && wdv.method_id == method_id) {
               cancel_edit_subject();
               return;
            } else {
               cancel_edit_subject();
            }
         }
         wdv = _dce('div');
         wdv.method_id = method_id;
         wdv.ee = null;
         if(method_id=='new') {
            var tre = $('trempty');
            var tr = _dce('tr');
            var td = tr.appendChild(_dce('td'));
            tr = tre.parentNode.insertBefore(tr,tre.parentNode.firstChild);
         } else {
            var td = $('tdclass_'+method_id);
         }
         wdv.setAttribute('style','padding:10px;');
         wdv = td.appendChild(wdv);
         wdv.appendChild(progress_span());
         wdv.td = td;
         $('trempty').style.display = 'none';
         if(scrollto_method_id) {
            var yy = oY($('tdclass_'+scrollto_method_id));
            scrollto_method_id = null;
            window.scrollTo(0,yy);
         }
         ocjx_app_editMethod(method_id,function(_data) {
            wdv.innerHTML = _data;
            $('inp_method_nm').focus();
            
         });
      }
      
      function cancel_edit_subject() {
         wdv.td.style.backgroundColor = '';
         if(wdv.method_id=='new') {
            _destroy(wdv.td.parentNode);
         }
         wdv.method_id = null;
         _destroy(wdv);
         wdv = null;
      }
      
      function delete_subject() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '#ffcccc';
         wdv.oldHTML = wdv.innerHTML;
         wdv.innerHTML = 'Are you sure you want to delete this subject?<br/><br/>'
                       + '<input type=\"button\" value=\""._CANCEL."\" onclick=\"cancel_delete();\"/>&nbsp;&nbsp;'
                       + '<input type=\"button\" value=\""._DELETE."\" onclick=\"do_delete();\"/>';
      }
      
      function cancel_delete() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '';
         wdv.innerHTML = wdv.oldHTML;
      }
      
      function do_delete() {
         ocjx_app_Delete(wdv.method_id,null);
         var tr = wdv.parentNode.parentNode;
         _destroy(tr);
         wdv.method_id = null;
         wdv = null;
      }
      
      function kdnm(d,e) {
         var k = getkeyc(e);
         if(k==13) {
            save_subject();
         }
      }
      
      function save_subject() {
         var method_id = wdv.method_id;
         var method_nm = $('inp_method_nm').value;
         var descr = '';
         if($('inp_method_desc')) {
            descr = urlencode($('inp_method_desc').value);
         }
         
         $('progressm').innerHTML = '';
         $('progressm').appendChild(progress_span());
         ocjx_app_saveAction(method_id,method_nm,descr,function(_data) {
            var data = recjsarray(_data);
            var td = wdv.td;
            wdv.method_id = null;
            td.setAttribute('id',data[1]);
            td.innerHTML = data[3];
            
            _destroy(wdv);
            /*
            wdv = _dce('div');
            wdv.method_id = data[0];
            wdv.td = td;
            
            wdv.setAttribute('style','padding:10px;');
            wdv = td.appendChild(wdv);
            wdv.td = td;
            wdv.innerHTML = data[2];
            $('inp_method_nm').focus();
            */
         });
      }
      
      ajax_feedback = null;
      var qsubject = _gel('qsubject');
      qsubject._align='left';
      qsubject._get_param=function() {
         var qval = this.value;
         qval = trim(qval);
         if(qval.length < 2) {
            return '';
         }
         return qval;
      };
      qsubject._onselect=function(resId) {
         window.location = '".$this->getURL()."?".$this->getURLParam()."&editsubject='+resId;
      };
      qsubject._send_query = function(q) {
         ocjx_app_searchSubject(q,function(_data) {
            qsubject._success(_data);
         });
      }
      _make_ajax(qsubject);
      
      // --></script>";
      
   }
   
   function listClass() {
      $db=&Database::getInstance();
      $sql = "SELECT method_t,method_type"
           . " FROM ".XOCP_PREFIX."idp_development_method_type";
      
      $result = $db->query($sql);
      $ret = "<table class='xxlist' style='width:100%;' align='center'><thead><tr><td>"
           . "<span style='float:left;'>Method List</span>"
           . "</td></tr></thead><tbody id='tbproc'>";
      if($db->getRowsNum($result)>0) {
         while(list($method_t,$method_type)=$db->fetchRow($result)) {
            $sql = "SELECT COUNT(*) FROM ".XOCP_PREFIX."idp_development_method WHERE status_cd = 'normal' AND method_t = '$method_t'";
            $rc = $db->query($sql);
            list($cnt)=$db->fetchRow($rc);
            $ret .= "<tr><td id='tdclass_${method_t}'>"
                  . "<table border='0' class='ilist'>"
                  . "<colgroup><col/></colgroup>"
                  . "<tbody><tr>"
                  . "<td><a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&t=${method_t}&smethod=1'>".htmlentities(stripslashes($method_type))."</a> ($cnt)</td>"
                  . "</tr></tbody></table>"
                  . "</td></tr>";
         }
      }
      $ret .= "<tr style='display:none;' id='trempty'><td>"._EMPTY."</td></tr>";
      $ret .= "</tbody></table><div style='margin-bottom:200px;'>&nbsp;</div>";
      
      return $ret;
   }
   
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            if(isset($_GET["smethod"])&&$_GET["smethod"]==1&&isset($_GET["t"])&&$_GET["t"]!="") {
               $_SESSION["hris_method_t"] = $_GET["t"];
               $ret = $this->browser();
            } else if(isset($_GET["backlist"])&&$_GET["backlist"]==1) {
               unset($_SESSION["hris_method_t"]);
               $ret = $this->browser();
            } else if (isset($_GET["prefix"])) {
               $ret = $this->browser($_GET["prefix"]);
               $_SESSION["browse_prefix"] = $_GET["prefix"];
            } else if (isset($_GET["editsubject"])) {
               $method_id = $_GET["editsubject"];
               $sql = "SELECT substring(method_nm,1,1) FROM ".XOCP_PREFIX."idp_development_method WHERE status_cd = 'normal' AND method_id = '$method_id'";
               $result = $db->query($sql);
               if($db->getRowsNum($result)==1) {
                  list($prefix)=$db->fetchRow($result);
               }
               $ret = $this->browser($prefix);
               $_SESSION["browse_prefix"] = $prefix;
            } else {
               $ret = $this->browser();
            }
            break;
         default:
            $ret = $this->browser();
            break;
      }
      return $ret."<div style='height:100px;'>&nbsp;</div>";
   }
}

} // HRIS_IDPDEVLIB_DEFINED
?>
