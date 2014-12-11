<?php
//--------------------------------------------------------------------//
// Filename : modules/ehr/panel/panel_combo.php                       //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2005-07-11                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('EHR_PANELCOMBO_DEFINED') ) {
   define('EHR_PANELCOMBO_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/ehr/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/ehr/class/selectorg.php");
include_once(XOCP_DOC_ROOT."/modules/ehr/class/stdbrowse.php");
include_once(XOCP_DOC_ROOT."/modules/ehr/class/panel.php");

class _ehr_PanelCombo extends _ehr_class_Panel {
   var $catchvar = _EHR_CATCH_VAR;
   var $blockID = _EHR_PANELCOMBO_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _EHR_PANELCOMBO_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $org_id;
   var $obj;
   
   function _ehr_PanelCombo($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                            yang diteruskan ke konstruktor parent class */
      global $xocpConfig;
      parent::init($catch);
      
      $this->org_id = $_SESSION["ehr_org_id"];
      $this->id_prefix = _EHR_PANELCOMBO_ID_PREFIX;
      $this->id_seg_len = _EHR_PANELCOMBO_ID_SEGLEN;
      $this->concept_id = "PNX_";
      $this->con_class_id = _EHR_PANEL_CON_CLASS_ID;
   }
   

   function searchForm($datarec = NULL,$comment = NULL) {
      $ret = "<table class='tblfrm'>"
           . "<tr><td class='tblfrmtitle' colspan='2'>Cari Objek</td></tr>"
           . "<tr><td class='tblfrmfieldname'>Kata Kunci</td><td class='tblfrmfieldvalue'><input type='text' style='width:300px;' id='searchq' autocomplete='off'/></td></tr>"
           . "<tr><td class='tblfrmbuttons' colspan='2'><input type='button' value='"._NEW."' class='bt' onclick='createNewObj(\"PNL_\");'/></td></tr>"
           . "</table>";
      
      if($_GET["obj_delete"]==1) {
         $ret .= "<br/>Objek telah dihapus.";
      }

      $_SESSION["html"]->addScript("<script type='text/javascript' language='javascript'><!--

      function createNewObj(con_class_id) {
         pjx_app_createObject(con_class_id,function(_data){ window.location = '".XOCP_SERVER_SUBDIR."/index.php?X_ehr=".$this->blockID."&editobject=y&obj_id='+_data;});
      }
      // --></script>");

      $ajax = new _ehr_class_PanAjax("pjx");
      $ret .= $ajax->getJs() . "<script type='text/javascript' language='javascritp'><!--
      ajax_feedback = null;
      var qpat = _gel('searchq');
      qpat._get_param=function() {
         var qval = this.value;
         qval = trim(qval);
         if(qval.length < 2) {
            return '';
         }
         if(isNaN(parseInt(qval.substring(0,1)))) {
            return qval;
         }
      };
      
      qpat._onselect=function(resId) {
         window.location = '".XOCP_SERVER_SUBDIR."/index.php?X_ehr=".$this->blockID."&editobject=y&obj_id='+resId;
      }
      qpat._send_query = pjx_app_searchObject;
      _make_ajax(qpat);
      qpat.focus();
      //--></script>";

      return $ret;
      
   }

   
   function editForm($obj_id = NULL,$comment = NULL) {
      $db =& Database::getInstance();
      $frm_edit = new XocpThemeForm(_EHR_PANELCOMBO_EDITFRM,"objectfrm_edit",XOCP_SERVER_SUBDIR."/index.php","get",TRUE);
      $frm_edit->setWidth("100%");
      $tabledomain = new XocpTable();
      $tabledomain->setWidth("100%");
      $linkconcept = $linkowner = "-";
      $obj_nm = $object_desc = "";
      $numrows = 0;
      if($obj_id == NULL) {
         $_SESSION["ehr_obj_id"] = NULL;
         $_SESSION["ehr_obj_nm"] = NULL;
         $fobj_id = new XocpFormText(_EHR_OBJ_OBJECTID,"obj_id",20,20,"");
         $fnew_object = new XocpFormHidden("new_object","1");
      } else {
         $sql = "SELECT obj_nm,concept_id,description"
              . " FROM ".XOCP_PREFIX."ehr_obj WHERE obj_id = '$obj_id'";
         $result = $db->query($sql);
         if(($numrows=$db->getRowsNum($result))>0) {
            list($obj_nm,$concept_id,$object_desc) = $db->fetchRow($result);
            $fnew_object = new XocpFormHidden("new_object","0");
            $_SESSION["ehr_obj_id"] = $obj_id;
            $_SESSION["ehr_obj_nm"] = $obj_nm;
            $linkconcept = $this->getConcept($obj_id);
         } else {
            $fnew_object = new XocpFormHidden("new_object","1");
         }
         $fobj_id_txt = new XocpFormLabel(_EHR_OBJ_OBJECTID,$obj_id);
         $fobj_id = new XocpFormHidden("obj_id",$obj_id);
      }
      
      $fobj_nm = new XocpFormText(_EHR_OBJ_OBJECTNAME,"obj_nm",45,255,$obj_nm);
      $fobj_nm->setExtra("autocomplete='off'");
      $fobject_desc = new XocpFormTextArea(_EHR_OBJ_OBJECTDESC,"object_desc",$object_desc);
      
      $fbtn_save = new XocpFormButton("","btn_save",_SAVE,"submit");
      $fbtn_copy = new XocpFormButton("","btn_copy",_COPY,"submit");
      $fbtn_delete = new XocpFormButton("","btn_delete",_DELETE,"submit");
      $buttons = new XocpFormElementTray("");
      $buttons->addElement($fbtn_save);
      if($obj_id != NULL && $numrows > 0) {
         $tblcom = $this->getComponent($obj_id);
         $fcomcost = new XocpFormFreeCell($tblcom);
         $fconcept = new XocpFormLabel(_EHR_OBJ_CONCEPT,$linkconcept);
         $buttons->addElement($fbtn_copy);
         $buttons->addElement(new XocpFormLabel("&nbsp;&nbsp;&nbsp;"));
         $buttons->addElement($fbtn_delete);
         $msgObjName = _theme::messageBox("<a href='".XOCP_SERVER_SUBDIR."/index.php'><img src='".XOCP_SERVER_SUBDIR
                     . "/images/return.gif' border='0' alt='return' /></a> <b>$obj_id</b> $obj_nm","info") . "<br/>";
         /////////////////////////////////////// PREVIEW FORM
         //$ret = "<br/>" . $this->getPreviewForm($obj_id);
         /////////////////////////////////////// OBJ REFERER
         //$tblup = $this->getReferer($obj_id);
         //$tblup_txt = $tblup->render();
         $ret .= "<br/>$tblup_txt";
         /////////////////////////////////////// EXPLOSION
         //$tblexp = $this->getExplosion($obj_id);
         //$ret .= "<br/>" . $tblexp->render();
         ///////////////////////////////////////
      } else {
         $msgObjName = _theme::messageBox("<a href='".XOCP_SERVER_SUBDIR."/index.php'><img src='".XOCP_SERVER_SUBDIR
                     . "/images/return.gif' border='0' alt='return' /></a> "._EHR_OBJ_CREATENEW,"info") . "<br/>";
      }
      
      $frm_edit->addElement($fnew_object);
      $frm_edit->addElement($fobj_id);
      $frm_edit->addElement($fobj_id_txt);
      $frm_edit->addElement($fobj_nm);
      $frm_edit->addElement($fconcept);
//      $frm_edit->addElement($fobject_desc);
      $frm_edit->addElement($fcomcost);
      $frm_edit->addElement($buttons);
      $frm_edit->addElement($this->varForm());
      if($comment != NULL) {
         $frm_edit->setComment($comment);
      }
      $frm_edit->setRequired("obj_id");
      $frm_edit->setRequired("obj_nm");
      /*
      if($obj_id != NULL) {
         $frm_edit->setFocus("obj_nm");
      } else {
         $frm_edit->setFocus("obj_id");
      }
      */
      $ret = $msgObjName . $frm_edit->render() . $ret;
      return $ret;
   }

   
   function main() {
      
      $slorg = new _ehr_class_SelectOrganization($this->catch);
      $slorghtml = $slorg->show();
      if($_SESSION["ehr_org_id"] == 0) {
         return $slorghtml;
      }
      
      switch($this->catch) {
         case $this->blockID :
            if ($_GET["btn_new"] != "") {
               $ret = $this->editForm($this->generateID());
            } elseif ($_GET["editobject"] == "y") {
               $_SESSION["ehr_obj_id"] = $_GET["obj_id"];
               $ret = $this->editForm($_GET["obj_id"]);
            } elseif ($_GET["btn_save"] != "") {
               $db =& Database::getInstance();
               if($this->saveObject($_GET)) {
                  $_SESSION["ehr_obj_id"] = $_GET["obj_id"];
                  if($_GET["new_object"] == "1") {
                     $this->setConcept($_GET["obj_id"],$this->concept_id);
                  }
                  $ret = $this->editForm($_GET["obj_id"]);
               } else {
                  $ret = $this->editForm($this->generateID());
               }
               
            } elseif ($_GET["btn_copy"] != "") {
               $db =& Database::getInstance();
               $this->saveObject($_GET);
               $_SESSION["ehr_obj_id"] = $_GET["obj_id"];
               $copy = $this->generateID();
               $this->objCopy($_GET["obj_id"],$copy);
               $_SESSION["ehr_obj_id"] = $copy;
               $ret = $this->editForm($copy);
               
            } elseif ($_GET["btn_delete"] != "") {
               $db =& Database::getInstance();
               $ret = $this->confirmDelete($_GET["obj_id"]);
            ///////////////////////////////////////concept
            } elseif ($_POST["btn_cancel"] != "") {
               $ret = $this->editForm($_POST["obj_id"]);
            ///////////////////////////////////////concept
            } elseif ($_POST["btn_sure_delete"] != "") {
               $this->deleteObject($_POST["obj_id"]);
               $_SESSION["ehr_obj_id"] = NULL;
               $ret = $this->searchForm() . "<br/>" . _theme::messageBox(_EHR_OBJ_OBJDELETEDMSG,"warn");

            } else {
               $_SESSION["ehr_obj_id"] = NULL;
               $ret = $this->searchForm();
            }
            break;
         default :
            $_SESSION["ehr_obj_id"] = NULL;
            $ret = $this->searchForm();
            break;
      }
      return $slorghtml."<br/>".$ret;
   }

}
   
} // EHR_PANELCOMBO_DEFINED
?>