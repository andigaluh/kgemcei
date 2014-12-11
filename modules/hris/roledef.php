<?php
//--------------------------------------------------------------------//
// Filename : modules/ehr/concepts/roledef.php                        //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2004-10-28                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('EHR_ROLEDEF_DEFINED') ) {
   define('EHR_ROLEDEF_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/ehr/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/ehr/class/concept.php");
include_once(XOCP_DOC_ROOT."/modules/ehr/class/object.php");

class _ehr_RoleDefinition extends XocpBlock {
   var $catchvar = _EHR_CATCH_VAR;
   var $blockID = _EHR_ROLEDEF_BLOCK;
   var $width = "100%";
   var $display_title = TRUE;
   var $title = _EHR_ROLEDEF_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $org_id;
   var $role_class_id = "ROLE";
   var $con;
   var $obj;
   
   function _ehr_RoleDefinition($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                            yang diteruskan ke konstruktor parent class */
      global $xocpConfig;
      
      $this->XocpBlock($catch);          /* ini meneruskan $catch ke parent constructor */
      
      $this->org_id = $_SESSION["ehr_org_id"];
      
      $this->con = new _ehr_class_Concept($this->varURL(),$this->varForm());
      $this->obj = new _ehr_class_Object($this->varURL(),$this->varForm(),$this->blockID);
   }
   
   function searchForm($datarec = NULL,$comment = NULL) {
      $db =& Database::getInstance();

      $ret = "<table class='tblfrm'>"
           . "<tr><td class='tblfrmtitle' colspan='2'>Cari Peran</td></tr>"
           . "<tr><td class='tblfrmfieldname'>Nama Peran / Role ID</td><td class='tblfrmfieldvalue'><input type='text' style='width:300px;' id='qcon'/></td></tr>"
           . "<tr><td class='tblfrmbuttons' colspan='2'><input type='button' value='"._NEW."' class='bt' onclick='btn_new(this,event);'/></td></tr>"
           . "</table>";
      
      require_once(XOCP_DOC_ROOT."/modules/ehr/class/ajax_roledef.php");
      $ajax = new _ehr_class_RoleDefAjax("rdjx");
      $ret .= $ajax->getJs() . "
      <script type='text/javascript' language='javascript'><!--
      
      var qcon = _gel('qcon');
      qcon._get_param=function() {
         var qval = this.value;
         qval = trim(qval);
         if(qval.length < 2) {
            return '';
         }
         return qval;
      };
      qcon._onselect=function(resId) {
         window.location = '".XOCP_SERVER_SUBDIR."/index.php?X_ehr=".$this->blockID."&editconcept=y&concept_id='+resId;
      }
      qcon._send_query = rdjx_app_searchRole;
      _make_ajax(qcon);
      qcon.focus();
      
      function btn_new(d,e) {
         window.location = '".XOCP_SERVER_SUBDIR."/index.php?X_ehr=".$this->blockID."&btn_new=y';
      }
      
      
      // --></script>";


      return $ret;
   
   }

   function generateID() {
      $db =& Database::getInstance();
      $sql = "SELECT MAX(concept_id) FROM ".XOCP_PREFIX."ehr_concepts"
           . " WHERE concept_id LIKE '"._EHR_ROLEDEF_ID_PREFIX."%'";
      $result = $db->query($sql);
      list($max) = $db->fetchRow($result);
      $num = substr($max,strlen(_EHR_ROLEDEF_ID_PREFIX),_EHR_ROLEDEF_ID_SEGLEN) + 1;
      $new_id = _EHR_ROLEDEF_ID_PREFIX . sprintf("%0"._EHR_ROLEDEF_ID_SEGLEN."d",$num);
      return $new_id;
   }
   


   function editForm($concept_id = NULL,$comment = NULL) {
      $db =& Database::getInstance();
      $concept_nm = "";
      if($concept_id == NULL) {
         $concept_id = "new";
         $messageBox = _theme::messageBox("<a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&chrole=y'><img src='".XOCP_SERVER_SUBDIR.
                "/images/return.gif'></a> "._EHR_ROLEDEF_CREATENEW,"info");
      } else {
         $sql = "SELECT a.concept_nm,a.concept_def,b.tariff,b.unit_cost"
              . " FROM ".XOCP_PREFIX."ehr_concepts a"
              . " LEFT JOIN ".XOCP_PREFIX."ehr_obj b ON b.obj_id = a.concept_id"
              . " WHERE a.concept_id = '$concept_id'";
         $result = $db->query($sql);
         list($concept_nm,$concept_def,$tariff,$unit_cost) = $db->fetchRow($result);
         $_SESSION["ehr_role_id"] = $concept_id;
         $_SESSION["ehr_role_nm"] = $concept_nm;
         $messageBox = _theme::messageBox("<a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&chrole=y'><img src='".XOCP_SERVER_SUBDIR.
                "/images/return.gif'></a> <b>$concept_id</b> <span id='cname'>$concept_nm</span>","info");
         
      }
      
      if($concept_id != "new") {
         $sql = "SELECT a.employee_id,c.person_nm,c.person_id"
              . " FROM ".XOCP_PREFIX."ehr_role_plan a"
              . " LEFT JOIN ".XOCP_PREFIX."ehr_employee b USING(employee_id)"
              . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
              . " WHERE role_id = '$concept_id'"
              . " AND a.employee_id != '0'"
              . " GROUP BY a.employee_id"
              . " ORDER BY c.person_nm";
         $result = $db->query($sql);
         $ref = "";
         $cnt = $db->getRowsNum($result);
         if($cnt>0) {
            while(list($employee_id,$person_nm,$person_id)=$db->fetchRow($result)) {
               $link = XOCP_SERVER_SUBDIR."/index.php?XP_ehremployee_ehr=empbl&slemp_selectemp=$person_id";
               $ref .= "<div style='padding:3px;border-bottom:1px solid #cccccc;padding-left:4px;'><a href='$link'>$person_nm</a></div>";
            }
         }
         $empl = "<br/>"
               . "<div style='padding:2px;background-color:#ffffff;'>"
               . "<div style='color:black;font-weight:bold;font-size:1.1em;"
               . "padding:5px;border:1px solid #999999;background-color:#dddddd;text-align:center;'>Peran Pegawai ($cnt):</div>"
               . "$ref</div>";
         $btnglobaltarif = "<input type='button' value='Set Global Tarif' onclick='setglobaltariff(\"$concept_id\",this,event);'/>";
      } else {
         $btnglobaltarif = "";
      }

      $ret = "<form id='confrm'><table class='xxfrm' style='width:100%;'><thead><tr><td colspan='2'>Edit Peran</td></tr></thead><tbody>"
           . "<tr><td >ID </td><td >$concept_id</td></tr>"
           . "<tr><td >Nama Peran </td><td ><input type='text' style='width:90%;' value='$concept_nm' name='concept_nm'/></td></tr>"
           . "<tr><td >Tarif </td><td ><input id='role_tariff' type='text' style='width:100px;' value='$tariff' name='tariff'/>"
           . "&nbsp;$btnglobaltarif</td></tr>"
           . "<tr><td colspan='2'><span id='concept_progress'></span>&nbsp;&nbsp;"
           . "<input onclick='save_concept(\"$concept_id\",this,event);' type='button' value='Simpan'/>&nbsp;&nbsp;"
           . "<input type='button' value='Hapus' onclick='delete_role(\"$concept_id\",this,event);'/></td></tr>"
           . "</tbody></table></form>";
      
      
      if($concept_id != "new") {
         $sql = "SELECT a.payplan_id,a.payplan_nm,b.tariff"
              . " FROM ".XOCP_PREFIX."ehr_payplan a"
              . " LEFT JOIN ".XOCP_PREFIX."ehr_role_plan b ON b.payplan_id = a.payplan_id"
              . " AND b.role_id = '$concept_id' AND b.employee_id = '0'"
              . " ORDER BY a.payplan_nm";
         $result = $db->query($sql);
         $ret .= "<br/><form id='pplfrm'><table class='xxfrm' style='width:100%;' align='center'><thead><tr><td colspan='2'>Set Global Tarif Per Metode Pembayaran</td></tr></thead><tbody>";
         if($db->getRowsNum($result)>0) {
            while(list($payplan_id,$payplan_nm,$tariff)=$db->fetchRow($result)) {
               $ret .= "<tr><td>&nbsp;&nbsp;$payplan_nm </td><td>"
                     . "<input name='ppltrf_${payplan_id}' style='text-align:right;width:150px;' type='text' value='$tariff'/></td></tr>";
            }
         }
         $ret .= "<tr><td colspan='2'><span id='payplan_progress'></span>&nbsp;&nbsp;"
               . "<input onclick='save_payplan(\"$concept_id\",this,event);' type='button' "
               . "value='Set Tarif'/></td></tr></tbody></table></form>";
      }
      require_once(XOCP_DOC_ROOT."/modules/ehr/class/ajax_roledef.php");
      
      $ajax = new _ehr_class_RoleDefAjax("rdjx");
      
      $ret .= $ajax->getJs()."<script type='text/javascript'><!--
      
      function setglobaltariff(concept_id,d,e) {
         var inps = $('pplfrm').getElementsByTagName('input');
         for(var i=0;i<inps.length;i++) {
            if(inps[i].getAttribute('type')=='text') {
               inps[i].value = $('role_tariff').value;
            }
         }
         save_concept(concept_id,d,e);
         save_payplan(concept_id,d,e);
      }
      
      function do_delete() {
         rdjx_app_deleteConcept($('confrm').concept_id,function(_data) {
           location.href = '".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&btn_suredelete=y';
         });
      }
      
      function cancel_delete() {
         $('confrm').innerHTML = $('confrm').oldHTML;
      }
      
      function delete_role(concept_id,d,e) {
         $('confrm').oldHTML = $('confrm').innerHTML;
         $('confrm').concept_id = concept_id;
         $('confrm').innerHTML = '<div style=\"text-align:center;padding:10px;color:black;background-color:#ffcccc;border:1px solid #999999;\">'
                               + 'Anda akan menghapus peran ini?<br/><br/>'
                               + '<input type=\"button\" onclick=\"do_delete();\" value=\"Ya (Hapus)\"/>&nbsp;'
                               + '<input type=\"button\" onclick=\"cancel_delete();\" value=\"Tidak (Batal Hapus)\"/>'
                               + '</div>'
      }
      
      function save_concept(concept_id,d,e) {
         $('concept_progress').innerHTML = '';
         $('concept_progress').appendChild(progress_span('&nbsp;'));
         var ret = parseForm('confrm');
         rdjx_app_saveConcept(ret,concept_id,function(_data) {
            var data = recjsarray(_data);
            if(data[0]==1) {
               location.href = '".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&editconcept=y&concept_id='+data[1];
            } else {
               $('cname').innerHTML = data[2];
            }
            $('concept_progress').innerHTML = '';
         });
      }
      
      function save_payplan(concept_id,d,e) {
         $('payplan_progress').innerHTML = '';
         $('payplan_progress').appendChild(progress_span('&nbsp;'));
         var ret = parseForm('pplfrm');
         rdjx_app_savePayplan(ret,concept_id,function(_data) {
            $('payplan_progress').innerHTML = '';
         });
      }
      
      // --></script>";
      
      return $messageBox ."<br/>$ret". $empl;
   }
   
   function saveRole($concept_id,$concept_nm,$concept_def,$tariff,$unit_cost,$new = FALSE) {
      $db =& Database::getInstance();
      if($concept_id != "") {
         if(trim($concept_nm) == "") {
            return FALSE;
         }
         if(!$this->con->saveConcept($concept_id,
                                     trim($concept_nm),
                                     $concept_def,
                                     array($this->role_class_id),
                                     $new)) {
            return FALSE;
         } else {
            $new_object = ( $new ? 1 : 0 );
            $this->obj->saveObject(array("obj_id"=>$concept_id,
                                         "obj_nm"=>_EHR_ROLEDEF_HCPNAME . " ".trim($concept_nm),
                                         "concept_id"=>$concept_id,
                                         "unit_cost"=>$tariff,
                                         "tariff"=>$tariff,
                                         "new_object"=>$new_object));
            $this->obj->setConcept($concept_id,$concept_id);
            $sql = "UPDATE ".XOCP_PREFIX."ehr_obj SET obj_nm = concat('[ ',description,' ] ','$concept_nm')"
                 . " WHERE concept_id = '$concept_id' AND obj_id != '$concept_id'";
            $sql = "UPDATE ".XOCP_PREFIX."ehr_obj SET obj_nm = description"
                 . " WHERE concept_id = '$concept_id' AND obj_id != '$concept_id'";
            $db->query($sql);
            return TRUE;
         }
      }
      return FALSE;
   }
   
   function main() {
      
      switch($this->catch) {
         case $this->blockID :
            if($_GET["btn_search"] != '') {
               if(list($result,$comment)=$this->con->doSearch($_GET)) {
                  $ret = $this->searchForm($_GET,$comment) . "<br/>$result";
               } else {
                  $ret = $this->searchForm();
               }
            } elseif ($_GET["chrole"]=="y") {
               $_SESSION["ehr_role_id"]="";
               $_SESSION["ehr_role_nm"]="";
               $ret = $this->searchForm();
            } elseif ($_GET["nav"] == "y") {
               $ret = $this->searchForm($_GET["searchq"]) ."<br/>". $this->con->navigate($_GET["f"],$_GET["p"]);
            } elseif ($_GET["btn_new"] != "") {
               $ret = $this->editForm();
            } elseif ($_GET["editconcept"] == "y") {
               $_SESSION["ehr_role_id"] = $_GET["concept_id"];
               $ret = $this->editForm($_GET["concept_id"]);
            } elseif ($_GET["btn_save"] != "") {
               $db =& Database::getInstance();
               if($this->saveRole($_GET["concept_id"],
                                  $_GET["concept_nm"],
                                  $_GET["concept_def"],
                                  $_GET["tariff"],
                                  $_GET["unit_cost"],
                                  ($_GET["new_concept"] == "1"))) {
                  $_SESSION["ehr_role_id"] = $_GET["concept_id"];
                  $_SESSION["ehr_role_nm"] = $_GET["concept_nm"];
                  $ret = $this->editForm($_GET["concept_id"]);
               } else {
                  $ret = $this->editForm();
               }
            } elseif ($_GET["btn_delete"] != "") {
               $ret = $this->con->confirmDelete($_GET["concept_id"]);
            } elseif ($_POST["btn_canceldelete"] != "") {
               $ret = $this->editForm($_POST["concept_id"]);
            } elseif ($_GET["btn_suredelete"] != "") {
               $_SESSION["ehr_role_id"] = NULL;
               $ret = $this->searchForm() . "<br/>" . _theme::messageBox(_EHR_ROLEDEF_DELETEDMSG,"warn");


            } elseif($_GET["btn_generatefc"] != '') {
               if($_GET["concept_id"] != "") {
                  $_SESSION["ehr_role_id"] = $_GET["concept_id"];
                  $_SESSION["ehr_role_nm"] = $_GET["concept_nm"];
                  if(!$this->saveRole($_GET["concept_id"],
                                      $_GET["concept_nm"],
                                      $_GET["concept_def"],
                                      $_GET["tariff"],
                                      $_GET["unit_cost"],
                                      ($_GET["new_concept"] == "1"))) {
                     $ret = $this->editForm($_GET["concept_id"],_EHR_ROLEDEF_SAVEFAIL);
                     break;
                  }
               }
               $this->obj->updateFC($_GET["concept_id"]);
               $ret = $this->editForm($_GET["concept_id"]);

            } else {
               $ret = $this->searchForm();
            }
            break;
         default :
            if(isset($_SESSION["ehr_role_id"])&&$_SESSION["ehr_role_id"]!="") {
               $ret = $this->editForm($_SESSION["ehr_role_id"]);
            } else {
               $ret = $this->searchForm();
            }
            break;
      }
      return "<br/>".$ret;
   }

}

} // EHR_ROLEDEF_DEFINED
?>