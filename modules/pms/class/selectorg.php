<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/selectorg.p                           //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2005-07-22                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_CLASS_SELECTORG_DEFINED') ) {
   define('HRIS_CLASS_SELECTORG_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/system/class/gis_region.php");
   
class _hris_class_SelectOrganization extends XocpAttachable {
   var $prefix = "slptn_";
   var $attr;
   var $catch;
   
   function _hris_class_SelectOrganization($catch=NULL) {
      $this->catch = $catch;
      $this->setURLParam(XOCP_SERVER_SUBDIR."/index.php",NULL);
      $this->attr = array("nm","mrn","searchorg","f","p","ch","selectpt","ptperson_id");
   }
   
   function getPrefix() {
      return $this->prefix;
   }

   function showOrg($showOpt=FALSE) {
      $db =& Database::getInstance();
      
      global $hris_org_patient_encounter_array;
      $sql = "SELECT o.org_id,o.org_nm,b.org_class_nm,o.org_cd"
           . " FROM ".XOCP_PREFIX."orgs o"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b ON b.org_class_id = o.org_class_id"
           . " WHERE o.org_id = '".$_SESSION["hris_org_id"]."'";
      $result = $db->query($sql);
      $cnt = $db->getRowsNum($result);
      $showOpt = FALSE;
      $org_nm = "-";
      if($cnt == 1) {
         list($org_id,$org_nmx,$org_class_nm,$org_cd) = $db->fetchRow($result);
         $_SESSION["hris_org_nm"] = "$org_cd $org_nmx [$org_class_nm]";
         $org_nm = "$org_cd $org_nmx [$org_class_nm]";
      } else if($cnt > 1) {
         $found = 0;
         while(list($org_id,$org_nmx,$org_class_nm)=$db->fetchRow($result)) {
            if($org_id==$_SESSION["hris_org_id"]) {
               $found = 1;
               $org_nm = "$org_cd $org_nmx [$org_class_nm]";
               break;
            }
         }
         if($found==0) {
            $showOpt = TRUE;
         }
      } else {
         $_SESSION["hris_org_nm"] = NULL;
         $_SESSION["hris_org_id"] = 0;
         $showOpt = TRUE;
      }
      if($org_nm == "") $org_nm = "-";
      
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_selectorg.php");
      $ajax = new _hris_class_SelectOrgAjax("slrjx");
      $js = "";
      $js .= "\n<script type=\"text/javascript\" src=\"".XOCP_SERVER_SUBDIR."/include/treeorg.js\"></script>";
      $js .= $ajax->getJs();
      $js .= "\n<script type='text/javascript'>\n//<![CDATA[
     
      function _org_select_org(org_id,d,e) {
         slrjx_app_setOrg(org_id,function(_data) {
            location.reload();
         });
      }
      
      var dv = null;
      function show_org_opt(d,e) {
         var Element = _gel('list_org');
         if (dv&&dv.style.display!='none') {
            var uls = _gel('navSlide');
            var dvx = _gel('dvSlide');
            new Effect.toggle(Element,'blind',{duration:0.2}); 
         } else {
            _destroy(uls);
            dv = document.createElement('div');
            dv.setAttribute('id','dvSlide');
            dv.innerHTML = '';
            dv = Element.appendChild(dv);
            Element.dv = dv;
            Element.dv.appendChild(progress_span());
            slrjx_app_getOrgOpt(function(_data) {
               //Element.dv.innerHTML = _data;
               new Effect.toggle(Element,'blind',{duration:0.2});
            });
          
         }
         return true;
      }
      
      var newHref = null;
      function selorgopt(org_id,org_nm) {
         var Element = _gel('list_org');
         new Effect.toggle(Element,'blind',{duration:0.2});
         slrjx_app_setOrg(org_id,obj_id,null);
         newHref = '".XOCP_SERVER_SUBDIR."/index.php?X_hris="._HRIS_SELECTORG_BLOCK."&org_id='+org_id+'&obj_id='+obj_id;
         setTimeout('gotoOrg();',300);
      }
      
      function gotoOrg() {
         location.href = newHref;
      }

      ".($showOpt==TRUE?"setTimeout('show_org_opt(null,null);',100);":"")."
      
      //]]>\n</script>";
      
      $js .= "\n<script type=\"text/javascript\" src=\"".XOCP_SERVER_SUBDIR."/include/treeorg.js\"></script>";
      
      
      return $js."<div class='orgsel'><table border='0' width='100%' cellpadding='2' cellspacing='0'>
              <tr><td id='hris_org_nm'>$org_nm</td>
              <td align='right'>[<span class='xlnk' id='chorgsp' onclick='return show_org_opt(this,event);'>"._HRIS_SELECTORG
              ."</span>]</td></tr></table><div id='list_org' style='display:none;background-color:#FFFFFF;text-align:left;'></div></div>";
   }



   function show() {
      $db=&Database::getInstance();
      if ($_SESSION["hris_org_id"] > 0) {
         $ret = $this->showOrg();
      } else {
         $ret = $this->showOrg(TRUE);
      }
      return $ret;
   }

}

} // HRIS_CLASS_SELECTORG_DEFINED
?>