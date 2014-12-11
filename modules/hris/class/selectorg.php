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
      $sql = "SELECT o.org_id,o.org_nm,b.org_class_nm"
           . " FROM ".XOCP_PREFIX."orgs o"
           . " LEFT JOIN ".XOCP_PREFIX."pgroup2org p USING(org_id)"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b ON b.org_class_id = o.org_class_id"
           . " WHERE p.pgroup_id = '".$_SESSION["xocp_user"]->getVar("pgroup_id")."'";
//           . " AND o.org_id = '".$_SESSION["hris_org_id"]."'";
      $result = $db->query($sql);
      $cnt = $db->getRowsNum($result);
      $showOpt = FALSE;
      $org_nm = "-";
      if($cnt == 1) {
         list($org_id,$org_nmx,$org_class_nm) = $db->fetchRow($result);
         $_SESSION["hris_org_nm"] = "$org_nmx [$org_class_nm]";
         $org_nm = "$org_nmx [$org_class_nm]";
      } else if($cnt > 1) {
         $found = 0;
         while(list($org_id,$org_nmx,$org_class_nm)=$db->fetchRow($result)) {
            if($org_id==$_SESSION["hris_org_id"]) {
               $found = 1;
               $org_nm = "$org_nmx [$org_class_nm]";
               break;
            }
         }
         if($found==0) {
            $showOpt = TRUE;
         }
      } else {
         $_SESSION["hris_org_nm"] = NULL;
         $_SESSION["hris_org_id"] = 0;
         $_SESSION["hris_org_obj_id"] = NULL;
         $showOpt = TRUE;
      }
      if($org_nm == "") $org_nm = "-";
      
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_selectorg.php");
      $ajax = new _hris_class_SelectOrgAjax("slrjx");
      $js = $ajax->getJs()."<script type='text/javascript'><!--
     
      var dv = null;
      function show_org_opt(d,e) {
         var Element = _gel('list_org');
         if (dv&&dv.style.display!='none') {
            var uls = _gel('navSlide');
            var dvx = _gel('dvSlide');
            new Effect.toggle(Element,'blind',{duration:0.2}); 
         } else {
            _destroy(uls);  
            slrjx_app_getOrgOpt(function(_data) {
               dv = document.createElement('div');
               dv.setAttribute('id','dvSlide');
               dv.innerHTML = _data;
               dv = Element.appendChild(dv);
               new Effect.toggle(Element,'blind',{duration:0.2});
            });
          
         }
         return true;
      }
      
      var newHref = null;
      function selorgopt(org_id,org_nm,obj_id) {
         var Element = _gel('list_org');
         new Effect.toggle(Element,'blind',{duration:0.2});
         slrjx_app_setOrg(org_id,obj_id,null);
         newHref = '".XOCP_SERVER_SUBDIR."/index.php?X_hris="._HRIS_SELECTORG_BLOCK."&org_id='+org_id+'&obj_id='+obj_id;
         setTimeout('gotoOrg();',300);
      }
      
      function gotoOrg() {
         location.href = newHref;
      }

      ".($showOpt==TRUE?"setTimeout('show_org_opt(null,null);',1);":"")."
      
      // --></script>";
      return "<table border='0' width='100%' cellpadding='2' cellspacing='0'>
              <tr><td id='hris_org_nm' style='font-weight:bold;background-color:#eeeeee;padding:5px;color:#555555'>$org_nm</td>
              <td align='right' style='background-color:#eeeeee;padding:5px;color:#555555'>"
              . "| "
              . "<span id='chorgsp' class='xlnk' onclick='return show_org_opt(this,event);'>"._HRIS_SELECTORG."</span>"
              . " |</td></tr></table><div id='list_org' style='display:none;background-color:#FFFFFF;text-align:center;'></div>$js";
   }



   function show() {
      $db=&Database::getInstance();
      switch ($this->catch) {
         case _HRIS_SELECTORG_BLOCK:
            if ($_GET["org_id"] != "") {
               $_SESSION["hris_org_id"] = $_GET["org_id"];
               $_SESSION["hris_org_obj_id"] = $_GET["obj_id"];
               $_SESSION["xocp_org_id"] = $_GET["org_id"];
               $db=&Database::getInstance();
               $sql = "UPDATE ".XOCP_PREFIX."users SET last_org_id = '".$_GET["org_id"]."'"
                    . " WHERE user_id = '".$_SESSION["xocp_user"]->getVar("user_id")."'";
               $db->query($sql);
               $ret = $this->showOrg();
            } elseif ($_GET["ch"] == "y") {
               $_SESSION["hris_org_id"] = 0;
               $_SESSION["xocp_org_id"] = 0;
               $_SESSION["hris_org_obj_id"] = NULL;
               $ret = $this->showOrg(TRUE);
            }
            break;
         default:
            if (!isset($_SESSION["hris_org_id"])||$_SESSION["hris_org_id"]==0) {
               
               $sql = "SELECT a.last_org_id,b.obj_id "
                    . " FROM ".XOCP_PREFIX."users a"
                    . " LEFT JOIN ".XOCP_PREFIX."orgs b on b.org_id = a.last_org_id"
                    . " WHERE a.user_id = '".$_SESSION["xocp_user"]->getVar("user_id")."'";
               $result = $db->query($sql);
               if($db->getRowsNum($result)==1) {
                  list($last_org_id,$obj_id) = $db->fetchRow($result);
                  if($last_org_id==0) {
                     $pgroup_id = $_SESSION["xocp_user"]->getVar("pgroup_id");
                     $sql = "SELECT a.org_id,b.obj_id FROM ".XOCP_PREFIX."pgroup2org a"
                          . " LEFT JOIN ".XOCP_PREFIX."orgs b USING(org_id)"
                          . " WHERE a.pgroup_id = '$pgroup_id'";
                     $respg = $db->query($sql);
                     if($db->getRowsNum($respg)==1) {
                        list($org_id,$obj_id)=$db->fetchRow($respg);
                        $_SESSION["hris_org_id"] = $org_id;
                        $_SESSION["xocp_org_id"] = $org_id;
                        $_SESSION["hris_org_obj_id"] = $obj_id;
                     }
                  } else {
                     $_SESSION["hris_org_id"] = $last_org_id;
                     $_SESSION["xocp_org_id"] = $last_org_id;
                     $_SESSION["hris_org_obj_id"] = $obj_id;
                  }
               } else {
                  $_SESSION["hris_org_id"] = 0;
                  $_SESSION["xocp_org_id"] = 0;
                  $_SESSION["hris_org_obj_id"] = NULL;
               }
            } else {
               $pgroup_id = $_SESSION["xocp_user"]->getVar("pgroup_id");
               $sql = "SELECT a.org_id,b.obj_id,b.org_nm FROM ".XOCP_PREFIX."pgroup2org a"
                    . " LEFT JOIN ".XOCP_PREFIX."orgs b USING(org_id)"
                    . " WHERE a.pgroup_id = '$pgroup_id'";
               $respg = $db->query($sql);
               if($db->getRowsNum($respg)==1) {
                  list($org_id,$obj_id,$org_nm)=$db->fetchRow($respg);
                  $_SESSION["hris_org_id"] = $org_id;
                  $_SESSION["xocp_org_id"] = $org_id;
                  $_SESSION["hris_org_obj_id"] = $obj_id;
               }
            }
            
            if ($_SESSION["hris_org_id"] > 0) {
               $ret = $this->showOrg();
            } else {
               $ret = $this->showOrg(TRUE);
            }
            break;
      }
      return $ret;
   }

}

} // HRIS_CLASS_SELECTORG_DEFINED
?>