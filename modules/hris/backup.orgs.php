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
      
      $sql = "SELECT a.org_id,a.org_nm,a.description,b.org_class_nm,a.org_cd,c.org_cd,c.org_nm,d.org_class_nm,"
           . "a.org_abbr,a.order_no"
           . " FROM ".XOCP_PREFIX."orgs a"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . " LEFT JOIN ".XOCP_PREFIX."orgs c ON c.org_id = a.parent_id"
           . " LEFT JOIN ".XOCP_PREFIX."org_class d ON d.org_class_id = c.org_class_id"
           . " WHERE a.status_cd = 'normal'"
           . " ORDER BY a.org_class_id,a.order_no";
      
      $result = $db->query($sql);
      $ret = "<table class='xxlist' style='width:100%;' align='center'>"
           . "<thead><tr><td colspan=2'>"
           . "<span style='float:left;'>Organization List</span>"
           . "<span style='float:right;'><input onclick='edit_org(\"new\",this,event);' type='button' value='"._ADD."'/></span>"
           . "</td></tr>"
           . "<tr><td style='padding:2px;'>"
                  . "<div><table style='border:0px;width:100%;'>"
                  . "<colgroup><col width='60'/><col width='50'/><col/><col width='200'/></colgroup><tbody>"
                  . "<tr>"
                      . "<td>Abbr</td>"
                      . "<td style='text-align:center;'>Order</td>"
                      . "<td>Name</td>"
                      . "<td>Parent</td>"
                  . "</tr></tbody></table></div>"
           . "</td></tr></thead>"
           . "<tbody id='tbproc'>"
           . "<tr><td id='tdorgs'>";
      if($db->getRowsNum($result)>0) {
         while(list($org_id,$org_nm,$description,$org_class_nm,$org_cd,$parent_cd,$parent_nm,$parent_class,
                    $org_abbr,$order_no)=$db->fetchRow($result)) {
            $ret .= "<div id='dvorg_${org_id}' class='sb'><table style='border:0px;width:100%;'>"
                  . "<colgroup><col width='60'/><col width='50'/><col/><col width='200'/></colgroup><tbody>"
                  . "<tr>"
                      . "<td>$org_abbr</td>"
                      . "<td style='text-align:center;'>$order_no</td>"
                      . "<td><div style='overflow:hidden;width:240px;'><div style='width:900px;'><span id='sp_${org_id}' class='xlnk' onclick='edit_org(\"$org_id\",this,event);'>".htmlentities(stripslashes($org_nm))." [$org_class_nm]</span></div></div></td>"
                      . "<td><div style='overflow:hidden;width:190px;'><div style='width:900px;'>$parent_cd $parent_nm [$parent_class]</div></div></td>"
                  . "</tr></tbody></table></div>";
         }
      }
      $ret .= "</td></tr>";
      $ret .= "<tr style='display:none;' id='trempty'><td>"._EMPTY."</td></tr>";
      $ret .= "</tbody>"
//           . "<tfoot><tr><td colspan='2'>"
//           . "<span style='float:right;'><input onclick='edit_org(\"new\",this,event);' type='button' value='"._ADD."'/></span>"
//           . "</td></tr></tfoot>"
           . "</table>";
      
      return $ret.$ajax->getJs()."<script type='text/javascript'><!--
      
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
            dv.setAttribute('class','sb');
         } else {
            var dv = $('dvorg_'+org_id);
         }
         wdv.setAttribute('style','padding:10px;');
         wdv = dv.appendChild(wdv);
         wdv.appendChild(progress_span());
         wdv.dv = dv;
         ocjx_app_editOrgs(org_id,function(_data) {
            wdv.innerHTML = _data;
            $('inp_org_nm').focus();
         });
      }
      
      function cancel_edit() {
         wdv.dv.style.backgroundColor = '';
         if(wdv.org_id=='new') {
            _destroy(wdv.dv);
         }
         wdv.dv = null;
         wdv.org_id = null;
         _destroy(wdv);
         wdv = null;
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
      
      function save_org() {
         
         var org_nm = urlencode($('inp_org_nm').value);
         var org_cd = urlencode($('inp_org_cd').value);
         var org_abbr = urlencode($('inp_org_abbr').value);
         var order_no = urlencode($('inp_order_no').value);
         var description = urlencode($('description').value);
         var cls = $('selclass');
         var class_id = cls.options[cls.selectedIndex].value;
         var par = $('selparent');
         var parent_id = par.options[par.selectedIndex].value;
         wdv.innerHTML = '';
         wdv.appendChild(progress_span());
         ocjx_app_saveOrgs(wdv.org_id,org_nm,description,class_id,parent_id,org_cd,order_no,org_abbr,function(_data) {
            var dv = wdv.dv;
            wdv.org_id = null;
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