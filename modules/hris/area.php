<?php
//--------------------------------------------------------------------//
// Filename : modules/ehr/inap/location.php                           //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-02-28                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('EHR_RSUZA_DEFINED') ) {
   define('EHR_RSUZA_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/ehr/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/ehr/include/vocab.php");
include_once(XOCP_DOC_ROOT."/modules/ehr/class/selectorg.php");
include_once(XOCP_DOC_ROOT."/modules/ehr/language/adiet.php");

class _ehr_Location extends XocpBlock {
   var $catchvar = _EHR_CATCH_VAR;
   var $blockID = _EHR_LOCATION_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _EHR_LOCATION_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _ehr_Location($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                         yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);       /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function locationList() {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/ehr/class/inap/ajax_location.php");
      $ajax = new _ehr_class_LocationAjax("locjx");
      $org_id = $_SESSION["ehr_org_id"];
      $_SESSION["html"]->addStyleSheet("<style type='text/css'>"
      . "\ntable.xxlist {width:100%;background-color:white;border-spacing:1px;}"
      . "\ntable.xxlist > thead > tr > td {background-color:#ccccff;color:black;border-right:1px solid #9999cc;font-weight:bold;padding:2px; }"
      . "\ntable.xxlist > tbody > tr > td {background-color:white;color:black;border-top:1px solid #9999cc;border-right:1px solid #9999cc;padding:2px; }"
      . "\ninput[type=button] {background-color:#dddddd;cursor:pointer;}"
      . "\ninput[type=button]:hover {background-color:#bbbbbb;}"
      . "\n</style>");
      
      
      $sql = "SELECT a.location_id,b.obj_nm"
           . " FROM ".XOCP_PREFIX."ehr_location a"
           . " LEFT JOIN ".XOCP_PREFIX."ehr_obj b ON b.obj_id = a.location_id"
           . " WHERE org_id = '$org_id'"
           . " ORDER BY b.obj_nm";
      $result = $db->query($sql);
      $ret = "<table class='xxlist'><thead><tr><td><span style='float:left;'>"._EHR_LOC_NM
           . "</span><span style='float:right;'><input type='button' value='"._ADD."' onclick='addloc();'/></span></td></tr></thead>";
      $ret .= "<tbody id='tbloc'>";
      if($db->getRowsNum($result)>0) {
         while(list($location_id,$location_nm)=$db->fetchRow($result)) {
            $ret .= "<tr id='loc_$location_id'><td><span id='sploc0_$location_id'>$location_id</span>&nbsp;<span id='sploc1_$location_id' class='xlnk' onclick='editloc(\"$location_id\",this,event);'>$location_nm</span></td></tr>";
         }
      }
      $ret .= "</tbody>";
      $ret .= "</table>";
      $ret .= $ajax->getJs()."
      <script type='text/javascript'><!--
      
      function addloc() {
         locjx_app_addLocation(function(_data) {
            var data = recjsarray(_data);
            var tbloc = _gel('tbloc');
            var tr = tbloc.insertBefore(_dce('tr'),tbloc.firstChild);
            tr.setAttribute('id','loc_'+data[0]);
            var td0 = tr.appendChild(_dce('td'));
            td0.innerHTML = '<span id=\"sploc0_'+data[0]+'\">'+data[0]+'</span>&nbsp;'
                          + '<span id=\"sploc1_'+data[0]+'\" class=\"xlnk\" onclick=\"editloc(\\''+data[0]+'\\',this,event);\">'+data[1]+'</span>';
            editloc(data[0],td0.lastChild);
         });
      }
      
      var editor = null;
      function editloc(id,d,e) {
         if(editor&&editor.location_id==id) {
            _destroy(editor);
            editor.location_id = null;
            editor = null;
            return;
         } else if(editor&&editor.location_id!=id) {
            _destroy(editor);
            editor.location_id = null;
            editor = null;
         }
         editor = d.parentNode.insertBefore(_dce('div'),d.nextSibling);
         editor.setAttribute('style','padding:4px;margin:2px;');
         editor.location_id = id;
         editor.appendChild(progress_span());
         locjx_app_editLocation(id,function(_data) {
            editor.innerHTML = _data;
            var inp = _gel('editloc_'+editor.location_id);
            _dsa(inp);
            inp.onkeydown=function(e) {
               k = getkeyc(e);
               if(k==13) {
                  saveloc(this.id.substring(8),this,e);
               }
            }
         });
      }
      
      function saveloc(id,d,e) {
         var nm = _gel('editloc_'+id).value;
         var sel = $('selproc_'+id);
         var proc = sel.options[sel.selectedIndex].value;
         locjx_app_saveLocation(nm,proc,id,null);
         var p = d.parentNode;
         _destroy(editor);
         editor.location_id = null;
         editor = null;
         _gel('sploc1_'+id).innerHTML = nm;
      }
      
      function cancelloc(id,d,e) {
         var p = d.parentNode;
         p.innerHTML = p._oldHTML;
      }
      
      function delloc(id,d,e) {
         locjx_app_deleteLocation(id,null);
         _destroy(d.parentNode.parentNode);
      }
      
      // --></script>";
      
      return $ret;
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
            $this->locationList();
            break;
         default:
            $ret = $this->locationList();
            break;
      }
      return $slorghtml."<br/>". $ret;
   }
}

} // EHR_RSUZA_DEFINED
?>