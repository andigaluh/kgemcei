<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/orgs.php                                   //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_ORGS_DEFINED') ) {
   define('HRIS_ORGS_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_Orgs extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_ORGS_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_ORGS_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_Orgs($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function listOrgs() {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_orgs.php");
      $ajax = new _hris_class_OrgsAjax("ocjx");
      
      $ret = "<style type='text/css'>"
           . "\n.preload1 {background: url(".XOCP_SERVER_SUBDIR."/images/org_collapse.png);}"
           . "\n.preload2 {background: url(".XOCP_SERVER_SUBDIR."/images/org_expand.png);}"
           . "\n.preload3 {background: url(".XOCP_SERVER_SUBDIR."/images/org_nochild.png);}"
           . "\n</style><table class='xxlist' style='width:100%;' align='center'>"
           . "<thead><tr><td colspan=2'>"
           . "<span style='float:left;'>&nbsp;</span>"
           . "<span style='float:right;'><input onclick='expandcollapse(this,event);' type='button' value='Expand/Collapse'/></span>"
           . "</td></tr>"
              . "</thead>"
           . "<tbody id='tbproc'>"
           . "<tr><td id='tdorgs'>";
      /*
      $sql = "SELECT a.org_id,a.org_nm,a.description,b.org_class_nm,a.org_cd,c.org_cd,c.org_nm,d.org_class_nm,"
           . "a.org_abbr,a.order_no"
           . " FROM ".XOCP_PREFIX."orgs a"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . " LEFT JOIN ".XOCP_PREFIX."orgs c ON c.org_id = a.parent_id"
           . " LEFT JOIN ".XOCP_PREFIX."org_class d ON d.org_class_id = c.org_class_id"
           . " WHERE a.status_cd = 'normal'"
           . " AND a.parent_id = '0'"
           . " ORDER BY a.org_class_id,a.order_no";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         $no = 0;
         while(list($org_id,$org_nm,$description,$org_class_nm,$org_cd,$parent_cd,$parent_nm,$parent_class,
                    $org_abbr,$order_no)=$db->fetchRow($result)) {
            $sql = "SELECT org_id FROM ".XOCP_PREFIX."orgs WHERE parent_id = '$org_id'";
            $resch = $db->query($sql);
            $haschild = 1;
            if($db->getRowsNum($resch)>0) {
               $haschild = 1;
            }
            if(trim($org_nm)=="") {
               $org_nm = "<span style='font-style:italic;'>New $org_class_nm</span>";
            } else {
               $org_nm = htmlentities(stripslashes($org_nm));
            }
            $ret .= "<div id='dvorg_${org_id}' class='sbo' style='".($no==0?"border-top:none;":"").";'><table style='border:0px;width:100%;'>"
                  . "<colgroup><col width='10'/><col/><col width='50'/></colgroup><tbody>"
                  . "<tr>"
                      . "<td><img style='cursor:pointer;' onclick='toggle_children(\"$org_id\",this,event);' id='expst_${org_id}' src='".XOCP_SERVER_SUBDIR."/images/".($haschild==1?"org_collapse.png":"org_nochild.png")."'/></td>"
                      . "<td><span id='sp_${org_id}' class='xlnk' onclick='edit_org(\"$org_id\",this,event);'>$org_nm</span> [ $org_class_nm ] ~ <span id='spabbr_${org_id}'>$org_abbr</span></td>"
                      . "<td id='tdabbr_${org_id}' style='text-align:left;'>&nbsp;</td>"
                  . "</tr></tbody></table></div>";
            $no++;
         }
      }
      
      $sql = "SELECT org_class_id,order_no,org_class_nm FROM ".XOCP_PREFIX."org_class"
           . " WHERE order_no > '0'"
           . " LIMIT 1";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($org_class_id,$order_no,$corg_class)=$db->fetchRow($result);
         $ret .= "<div id='dvadd_0' class='sbo'><table style='border:0px;width:100%;'>"
              . "<tbody>"
              . "<tr>"  
                 . "<td style='color:#999999;padding-left:4px;'>[<span onclick='addchild(\"0\",this,event);' class='ylnk'>add $corg_class</span>]</td>"
              . "</tr></tbody></table></div>";
      }
      
      */
      
      list($p,$h,$c) = $ajax->app_getChildren(array(0));
      $ret .= $h;
      $ret .= "</td></tr>";
      $ret .= "</tbody>"
           . "</table><div style='padding:40px;'>&nbsp;</div>";
      
      return $ret.$ajax->getJs()."<script type='text/javascript'><!--
      
      function add_history(org_id,d,e) {
         ocjx_app_addHistory(org_id,function(_data) {
            var dv = $('history_list');
            dv.innerHTML = _data;
         });
      }
      
      function view_changes(org_id,d,e) {
         var dv = $('changes');
         if(dv.style.display=='none') {
            dv.innerHTML = '';
            dv.appendChild(progress_span());
            dv.style.display = '';
            ocjx_app_getChanges(org_id,function(_data) {
               dv.innerHTML = _data;
            });
         }
      }
      
      var expst = 0;
      function expandcollapse(d,e) {
         $('tdorgs').innerHTML = '';
         $('tdorgs').appendChild(progress_span());
         ocjx_app_expandAll(expst,function(_data) {
            $('tdorgs').innerHTML = _data;
            if(expst==1) {
               expst = 0;
            } else {
               expst = 1;
            }
         });
      }
      
      newchild = null;
      function addchild(parent_id,d,e) {
         newchild = _dce('div');
         newchild.className = 'sbo';
         if(parent_id>0) {
            newchild.setAttribute('style','margin-left:20px;');
         }
         newchild = $('dvadd_'+parent_id).parentNode.insertBefore(newchild,$('dvadd_'+parent_id));
         newchild.appendChild(progress_span());
         ocjx_app_newChild(parent_id,function(_data) {
            if(_data=='UNKNOWNLEVEL') {
               alert('Level for new organisation is undefined.');
               _destroy(newchild);
               newchild = null;
               return;
            }
            var data = recjsarray(_data);
            newchild.setAttribute('id','dvorg_'+data[0]);
            newchild.innerHTML = data[1];
            edit_org(data[0],null,null);
         });
      }
      
      function toggle_children(org_id,d,e) {
         cancel_edit();
         var dv = $('dvorg_'+org_id);
         var imgSrc = $('expst_'+org_id).src;
         re = /^(.*)(org_)(expand|collapse)?.png$/
         if (matches = imgSrc.match(re)) {
            if(matches[3]=='expand') {
               $('expst_'+org_id).src = '".XOCP_SERVER_SUBDIR."/images/org_collapse.png';
               _destroy($('dvc_'+org_id));
               expst = 0;
               return;
            }
         }
         dv.org_id = org_id;
         dv.c = _dce('div');
         dv.c.setAttribute('id','dvc_'+org_id);
         dv.c.setAttribute('style','padding:2px;padding-right:0px;');
         dv.c = dv.appendChild(dv.c);
         dv.c.appendChild(progress_span());
         ocjx_app_getChildren(org_id,function(_data) {
            var data = recjsarray(_data);
            var dv = $('dvorg_'+data[0]);
            dv.childcount = data[2];
            $('expst_'+data[0]).src = '".XOCP_SERVER_SUBDIR."/images/org_expand.png';
            dv.c.innerHTML = data[1];
         });
      }
      
      function getoptparent(org_id,d,e) {
         var classx = d.options[d.selectedIndex].value;
         ocjx_app_getOptionParent(classx,org_id,function(_data) {
            if(_data!='EMPTY') {
               $('selparent').innerHTML = _data;
            }
         });
      }
      
      var wdv = null;
      //////////////////////////////////////////////////////////////////////////////
      function edit_org(org_id,d,e) {
         var dvx = $('dvorg_'+org_id);
         if(dvx.c) {
            if(dvx.childcount>0) {
               $('expst_'+org_id).src = '".XOCP_SERVER_SUBDIR."/images/org_collapse.png';
            }
            _destroy(dvx.c);
            dvx.c = null;
         }
         if(wdv) {
            if(wdv.org_id != 'new' && wdv.org_id == org_id) {
               cancel_edit();
               return;
            } else {
               cancel_edit();
            }
         }
         wdv = _dce('div');
         wdv.org_id = org_id;
         if(org_id=='new') {
            var dv = _dce('div');
            dv = $('tdorgs').insertBefore(dv,$('tdorgs').firstChild);
            dv.setAttribute('class','sbo');
         } else {
            var dv = $('dvorg_'+org_id);
         }
         wdv.setAttribute('style','padding:10px;');
         wdv = dv.appendChild(wdv);
         wdv.appendChild(progress_span());
         wdv.dv = dv;
         ocjx_app_editOrg2(org_id,function(_data) {
            wdv.innerHTML = _data;
            $('inp_org_nm').focus();
         });
      }
      
      function cancel_edit() {
         if(wdv) {
            wdv.dv.style.backgroundColor = '';
            if(wdv.org_id=='new') {
               _destroy(wdv.dv);
            }
            wdv.dv = null;
            wdv.org_id = null;
            _destroy(wdv);
            wdv = null;
         }
      }
      
      function delete_org() {
         var dv = wdv.dv;
         dv.style.backgroundColor = '#ffcccc';
         wdv.oldHTML = wdv.innerHTML;
         wdv.innerHTML = 'Are you sure you want to delete this organization?<br/><br/>'
                       + '<input type=\"button\" value=\""._CANCEL."\" onclick=\"cancel_delete();\"/>&nbsp;&nbsp;'
                       + '<input type=\"button\" value=\""._DELETE."\" onclick=\"do_delete();\"/>';
      }
      
      function cancel_delete() {
         var dv = wdv.dv;
         dv.style.backgroundColor = '';
         wdv.innerHTML = wdv.oldHTML;
      }
      
      function do_delete() {
         ocjx_app_Delete(wdv.org_id,null);
         var dv = wdv.dv;
         _destroy(dv);
         wdv.dv = null;
         wdv.org_id = null;
         wdv = null;
      }
      
      function inactive_org() {
         var dv = wdv.dv;
         dv.style.backgroundColor = '#ffcccc';
         wdv.oldHTML = wdv.innerHTML;
         wdv.innerHTML = 'Are you sure you want to set this organization inactive?<br/><br/>'
                       + '<input type=\"button\" value=\""._CANCEL."\" onclick=\"cancel_inactive();\"/>&nbsp;&nbsp;'
                       + '<input type=\"button\" value=\"Set Inactive\" onclick=\"do_inactive();\"/>';
      }
      
      function cancel_inactive() {
         var dv = wdv.dv;
         dv.style.backgroundColor = '';
         wdv.innerHTML = wdv.oldHTML;
      }
      
      function do_inactive() {
         ocjx_app_Inactive(wdv.org_id,null);
         var dv = wdv.dv;
         _destroy(dv);
         wdv.dv = null;
         wdv.org_id = null;
         wdv = null;
      }
      
      function save_org() {
         
         var org_nm = urlencode($('inp_org_nm').value);
         var org_cd = urlencode($('inp_org_cd').value);
         var org_abbr = urlencode($('inp_org_abbr').value);
         var order_no = urlencode($('inp_order_no').value);
         //var description = urlencode($('description').value);
         var description = '';
         wdv.innerHTML = '';
         wdv.appendChild(progress_span());
         ocjx_app_saveOrg2(wdv.org_id,org_nm,description,org_cd,order_no,org_abbr,function(_data) {
            var dv = wdv.dv;
            wdv.org_id = null;
            _destroy(wdv);
            wdv = null;
            if(_data=='EMPTY') {
               alert('Update failed.');
            }
            var data = recjsarray(_data);
            dv.setAttribute('id',data[0]);
            $('sp_'+data[1]).innerHTML = data[2];
            $('spabbr_'+data[1]).innerHTML = data[3];
         });
      }
      
      //////////////////////////////////////////////////////////////////////////////
      
      // --></script>";
   }
   
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $this->listOrgs();
            break;
         default:
            $ret = $this->listOrgs();
            break;
      }
      return $ret;
   }
}

} // HRIS_ORGS_DEFINED
?>