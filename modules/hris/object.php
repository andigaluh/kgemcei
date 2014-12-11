<?php
//--------------------------------------------------------------------//
// Filename : modules/ehr/panel/panel_combo.php                       //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2005-07-11                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('EHR_OBJECT_DEFINED') ) {
   define('EHR_OBJECT_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/ehr/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/ehr/class/selectorg.php");
include_once(XOCP_DOC_ROOT."/modules/ehr/class/stdbrowse.php");
include_once(XOCP_DOC_ROOT."/modules/ehr/class/panel.php");
include_once(XOCP_DOC_ROOT."/modules/ehr/include/costing.php");

class _ehr_Object extends _ehr_class_Panel {
   var $catchvar = _EHR_CATCH_VAR;
   var $blockID = _EHR_OBJECT_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _EHR_OBJECT_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $org_id;
   var $obj;
   
   function _ehr_Object($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                            yang diteruskan ke konstruktor parent class */
      global $xocpConfig;
      parent::init($catch);
      
      $this->org_id = $_SESSION["ehr_org_id"];
      $this->id_prefix = _EHR_OBJECT_ID_PREFIX;
      $this->id_seg_len = _EHR_OBJECT_ID_SEGLEN;
      $this->concept_id = "PNX_";
      $this->con_class_id = _EHR_PANEL_CON_CLASS_ID;
   }

   function searchForm($datarec = NULL,$comment = NULL) {
      $ret = "<table class='tblfrm'>"
           . "<tr><td class='tblfrmtitle' colspan='2'>Cari Objek</td></tr>"
           . "<tr><td class='tblfrmfieldname'>Kata Kunci</td><td class='tblfrmfieldvalue'><input type='text' style='width:300px;' id='searchq' autocomplete='off'/></td></tr>"
           . "<tr><td class='tblfrmbuttons' colspan='2'><input type='button' value='"._NEW."' class='bt' onclick='newObjType(this,event);'/></td></tr>"
           . "</table>";
      
      if($_GET["obj_delete"]==1) {
         $ret .= "<br/>Objek telah dihapus.";
      }


      $_SESSION["html"]->addScript("<script type='text/javascript' language='javascript'><!--

      var otype_d=null;
      function newObjType(d,e) {
         if(otype_d==null) {
            var dv = document.createElement('div');
            dv.setAttribute('style','text-align:left;border:1px solid #888888;visibility:hidden;background-color:#ddddff;padding:4px;position:absolute;color:black;');
            otype_d = d.parentNode.appendChild(dv);
            otype_d.innerHTML='<div class=\'traymenuitem\' onclick=\'createNewObj(\"MDEV\");\'>Alat Medis</div>'
                             +'<div class=\'traymenuitem\' onclick=\'createNewObj(\"DEV_\");\'>Alat Non Medis</div>'
                             +'<div class=\'traymenuitem\' onclick=\'createNewObj(\"SBTN\");\'>Bahan</div>'
                             +'<div class=\'traymenuitem\' onclick=\'createNewObj(\"DRUG\");\'>Obat</div>'
//                             +'<div class=\'traymenuitem\' onclick=\'createNewObj(\"AACT\");\'>Sub Tindakan</div>'
                             +'<div class=\'traymenuitem\' onclick=\'createNewObj(\"PROC\");\'>Prosedur</div>'
                             +'<div class=\'traymenuitem\' onclick=\'createNewObj(\"MDCT\");\'>Prosedur Pengobatan</div>'
                             +'<div class=\'traymenuitem\' onclick=\'createNewObj(\"LAB_\");\'>Prosedur Laboratorium</div>'
                             +'<div class=\'traymenuitem\' onclick=\'createNewObj(\"DIET\");\'>Prosedur Diet</div>'
                             +'<div class=\'traymenuitem\' onclick=\'createNewObj(\"MSRV\");\'>Prosedur Akomodasi</div>'
                             +'<div class=\'traymenuitem\' onclick=\'createNewObj(\"STAYSRV\");\'>Layanan Menginap</div>'
                             +'<div class=\'traymenuitem\' onclick=\'createNewObj(\"CPTH\");\'>Clinical Pathway</div>'
                             +'<div class=\'traymenuitem\' onclick=\'createNewObj(\"LOCA\");\'>Lokasi</div>'
                             +'<div class=\'traymenuitem\' onclick=\'createNewObj(\"BED_\");\'>Bed</div>'
                             +'<div class=\'traymenuitem\' onclick=\'createNewObj(\"PNL_\");\'>Panel</div>'
                             +'<div class=\'traymenuitem\' onclick=\'createNewObj(\"SYMP\");\'>Symptom/Sign</div>'
                             +'<div class=\'traymenuitem\' onclick=\'createNewObj(\"DIAG\");\'>Diagnosis</div>'
                             +'<div class=\'traymenuitem\' onclick=\'createNewObj(\"FIND\");\'>Finding</div>'
                             +'<div class=\'traymenuitem\' onclick=\'createNewObj(\"ACCT\");\'>Objek Akuntansi</div>';
//                             +'<div class=\'traymenuitem\' onclick=\'createNewObj(\"SRNA\");\'>Jasa Sarana</div>';
         }
         if(otype_d.style.visibility=='visible') {
            otype_d.style.visibility = 'hidden';
         } else {
            otype_d.style.top = (oY(d)+d.offsetHeight)+'px';
            otype_d.style.left = (oX(d))+'px';
            otype_d.style.visibility = 'visible';
         }
      }
      function createNewObj(con_class_id) {
         otype_d.style.visibility='hidden';
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
      $frm_edit = new XocpThemeForm(_EHR_OBJ_EDIT_OBJFORM,"objectfrm_edit",XOCP_SERVER_SUBDIR."/index.php","get",TRUE);
      $frm_edit->setWidth("100%");
      $numrows = 0;
      if($obj_id == NULL) {
         $_SESSION["ehr_obj_id"] = NULL;
         $_SESSION["ehr_obj_nm"] = NULL;
         $fobj_id = new XocpFormText(_EHR_OBJ_OBJECTID,"obj_id",20,20,"");
         $fnew_object = new XocpFormHidden("new_object","1");
         $fobj_nm = new XocpFormText(_EHR_OBJ_OBJECTNAME,"obj_nm",45,255,$obj_nm);
         $fobj_nm->setExtra("autocomplete='off'");
         
         $fbtn_save = new XocpFormButton("","btn_save",_SAVE,"submit");
         $fbtn_copy = new XocpFormButton("","btn_copy",_COPY,"submit");
         $fbtn_delete = new XocpFormButton("","btn_delete",_DELETE,"submit");
         $buttons = new XocpFormElementTray("");
         $buttons->addElement($fbtn_save);
         $msgObjName = _theme::messageBox("<a href='".XOCP_SERVER_SUBDIR."/index.php'><img src='".XOCP_SERVER_SUBDIR
                     . "/images/return.gif' alt='return'/></a> "._EHR_OBJ_CREATENEW,"info") . "<br/>";
         
         $frm_edit->addElement($fnew_object);
         $frm_edit->addElement($fobj_id);
         $frm_edit->addElement($fobj_nm);
         $frm_edit->addElement($buttons);
         $frm_edit->addElement($this->varForm());
         if($comment != NULL) {
            $frm_edit->setComment($comment);
         }
         $frm_edit->setRequired("obj_id");
         $frm_edit->setRequired("obj_nm");
         if($obj_id != NULL) {
            $frm_edit->setFocus("obj_nm");
         } else {
            $frm_edit->setFocus("obj_id");
         }
         $ret = $msgObjName . $frm_edit->render() . $ret;
      } else {
         $uniqid0 = uniqid("idn");
         $sql = "SElECT a.obj_nm,b.con_class_id FROM ".XOCP_PREFIX."ehr_obj a"
              . " LEFT JOIN ".XOCP_PREFIX."ehr_con_class b USING(concept_id)"
              . " WHERE a.obj_id = '$obj_id'";
         $result = $db->query($sql);
         list($obj_nm,$con_class_idx)=$db->fetchRow($result);
         $msgObjName = _theme::messageBox("<a href='".XOCP_SERVER_SUBDIR."/index.php'><img src='".XOCP_SERVER_SUBDIR
                     . "/images/return.gif' alt='return'/></a> <b>$obj_id</b> <span id='obj_nm_$uniqid0'>$obj_nm</span>","info") . "<br/>";
         $breakdown = _ehr_class_PanAjax::app_showBreakDown(array($uniqid0,NULL,$obj_id));
         list($obj_idx,$ret,$script)=explode("|",$breakdown);
         $ttluc = getTotalCost($obj_id);
         $ret = $msgObjName."<div style='width:99%;background-color:#ffffff;color:#000000;padding:2px;border:1px outset #666666;'>"
              . "<div style='background-color:#aaaaff;padding:4px;border:1px solid #777777;font-style:italic;font-weight:bold;'>Edit Objek</div>"
              . $ret
              . "<table border='0' width='100%' style='background-color:#ffffff;margin-top:3px;border:1px solid #aaaadd;'><tr>"
              . "<td>Total Biaya:</td><td style='text-align:right;padding:2px;' id='ttlcost_$uniqid0'>$ttluc</td></tr></table></div>"
              . "<input type='hidden' id='$uniqid0' name='bdkey' value='=$obj_id'/>";
         
         if(in_array($con_class_idx,array("PROC","LAB_","DIET","MSRV","MDCT"))) {
            //// panel finding, only for object class PROC, LAB_, DIET, MSRV, MDCT
            $sql = "SELECT a.panel_id,b.obj_nm FROM ".XOCP_PREFIX."ehr_finding_panel a"
                 . " LEFT JOIN ".XOCP_PREFIX."ehr_obj b ON b.obj_id = a.panel_id"
                 . " WHERE a.obj_id = '$obj_id'";
            $result = $db->query($sql);
            $panel = "";
            if($db->getRowsNum($result)>0) {
               while(list($panel_id,$panel_nm)=$db->fetchRow($result)) {
                  $panel .= "<div id='panel_${panel_id}' style='padding:2px;border-bottom:1px solid #999999;'>$panel_id <span style='font-weight:bold;'>$panel_nm</span> "
                          . " [<span class='ylnk' onclick='delpanel(\"$panel_id\");'>hapus</span>]</div>";
               }
               $panel .= "<div id='emptypanel' style='display:none;padding:2px;border-bottom:1px solid #999999;'>"._EMPTY."</div>";
            } else {
               $panel .= "<div id='emptypanel' style='padding:2px;border-bottom:1px solid #999999;'>"._EMPTY."</div>";
            }
            $ret .= "<div style='margin-top:10px;border:1px outset #999999;background-color:#ffffff;padding:4px;color:black;' id='listpanelfinding'>"
                  . "<div style='text-align:right;padding:4px;border:1px solid #999999;background-color:#ccccff;'>Tambah Panel Finding : <input id='qpanel' type='text'/></div>"
                  . "$panel</div>";
         }
         
         $ajax = new _ehr_class_PanAjax("pjx");
         $ret .= $ajax->getJs() . "
         <script type='text/javascript' language='javascript'><!--
         
         function showx_qconcept(uniqid,com_obj_id,d,e) {
         
         }

         var qconcept = null;
         function show_qconcept(uid,o_id,d,e) {
            e.cancelBubble=true;
            qconcept = _gel('qconcept_'+uid);
            $('spancon_'+uid).style.display = 'none';
            qconcept.style.visibility = 'visible';
            qconcept.uid = uid;
            qconcept.obj_id = o_id;
            qconcept._get_param=function() {
               var qval = this.value;
               qval = trim(qval);
               if(qval.length < 2) {
                  return '';
               }
               return qval;
            };
            qconcept.onkeydown=function(e) { 
               key = getkeyc(e);
               switch (key) { 
                  case 27: 
                     _gel('spancon_'+qconcept.uid).style.display='';
                     qconcept.style.visibility='hidden';
                     qconcept.blur();
                     document.onclick=null;
                     return false;
                     break; 
               } 
               return true; 
            };
            
            qconcept._onselect=function(resId,resNam) {
               qconcept._showResult(false);
               qconcept.value='';
               qconcept.style.visiblity = 'hidden';
               $('spancon_'+qconcept.uid).innerHTML=resNam;
               _gel('spancon_'+qconcept.uid).style.display='';
               qconcept.style.visibility='hidden';
               qconcept.blur();
               document.onclick=null;
               pjx_app_saveConcept(qconcept.obj_id,resId,null);
            };
            qconcept._send_query = pjx_app_searchConcept2;
            qconcept._align = 'left';
            _make_ajax(qconcept);
            qconcept.focus();
            document.onclick=function() {
               _gel('spancon_'+qconcept.uid).style.display='';
               qconcept.style.visibility='hidden';
               qconcept.blur();
               document.onclick=null;
            };
            
            return false;
         }
         

         
         var qpanel = _gel('qpanel');
         if(qpanel) {
            qpanel._get_param=function() {
               var qval = this.value;
               qval = trim(qval);
               if(qval.length < 2) {
                  return '';
               }
               if(isNaN(parseInt(qval.substring(0,1)))) {
                  return qval;
               }
            };
            
            qpanel._onselect=function(resId) {
               pjx_app_addPanel(resId,'$obj_id',function(_data) {
                  if(_data=='DUPLICATE') return;
                  var data = recjsarray(_data);
                  var dv = _dce('div');
                  dv.setAttribute('id','panel_'+data[0]);
                  dv.setAttribute('style','padding:2px;border-bottom:1px solid #999999;');
                  dv.innerHTML = data[1];
                  $('listpanelfinding').insertBefore(dv,$('emptypanel'));
                  $('emptypanel').style.display = 'none';
               });
            }
            qpanel._send_query = pjx_app_searchPanel;
            _make_ajax(qpanel);
            
            function delpanel(panel_id) {
               _destroy($('panel_'+panel_id));
               pjx_app_deletePanelFinding(panel_id,'$obj_id',null);
               if($('listpanelfinding').childNodes.length<=2) {
                  $('emptypanel').style.display = '';
               }
            }
         }
         
         $script
         subfunc_$uniqid0();
         var editucdv=null;
         var img_collapsed=new Image();
         var img_expanded=new Image();
         img_collapsed.src='".XOCP_SERVER_SUBDIR."/images/collapsed.gif';
         img_expanded.src='".XOCP_SERVER_SUBDIR."/images/expanded.gif';
         var expanded_dv=null;
         function showBreakDown(uid,p_o_id,o_id,d,e) {
            var img = d.firstChild;
            if(d.dv) {
               d.dv.parentNode.removeChild(d.dv);
               img.src = img_collapsed.src;
               d.dv=null;
            } else {
               img.src = img_expanded.src;
               var c = fetchIdUp(d,'com_'+uid);
               var dv = document.createElement('div');
               dv.setAttribute('id','comBDdiv'+uid);
               dv=c.appendChild(dv);
               dv.setAttribute('style','padding:0px;margin-left:10px;');
               d.dv = dv;
               expanded_dv=dv;
               pjx_app_showBreakDown(uid,p_o_id,o_id,doShowBD);
            }
         }
         
         function doShowBD(_data) {
            data=_data.split('|');
            var dv = expanded_dv;
            if(dv) {
               if(data[1].length>0) {
                  dv.innerHTML=data[1];
               } else {
                  dv.innerHTML='Tidak ada komponen.';
               }
               if(data[2].length>0) {
                  eval(data[2]);
                  eval('subfunc_'+data[0]+'();');
               }
            }
         }

         function save_ucedit(uid,p_o_id,o_id,d,e) {
            var el = _gel('frmedit_'+uid).getElementsByTagName('input');
            var eltxt = _gel('frmedit_'+uid).getElementsByTagName('textarea');
            var ret='';
            for (var i=0;i<el.length;i++) {
               if(!el[i].name) continue;
               switch(el[i].type) {
                  case 'radio':
                  case 'checkbox':
                     if(el[i].checked) {
                        ret += '@@'+el[i].name +'^^'+el[i].value
                     }
                     break;
                  default:
                     ret += '@@'+el[i].name +'^^'+el[i].value
                     break;
               }
            }
            ret += '@@'+eltxt[0].name + '^^'+eltxt[0].value;
            ret=urlencode(ret.substring(2));
            pjx_app_saveUC(uid,p_o_id,o_id,ret,refreshUC);
         }
         
         function refreshUC(_data) {
            if(_data.length>0) {
               if(_data.substring(0,5)=='ERROR') {
                  alert(_data.substring(6));
                  return false;
               }
               data=_data.split('[[separator]]');
               var obj_nm = _gel('obj_nm_'+data[0]);
               var iobj_nm = _gel('iobj_nm_'+data[0]);
               var ttlcost = _gel('ttlcost_'+data[0]);
               var tariff = _gel('tariff_'+data[0]);
               var account_cd = _gel('account_cd_'+data[0]);
               var account_nm = _gel('account_nm_'+data[0]);
               if(data[2].length>0) {
                  var arrtitle=recjsarray(data[2]);
                  if(obj_nm) obj_nm.innerHTML=arrtitle[0];
                  if(iobj_nm) iobj_nm.value=arrtitle[0];
                  if(tariff) tariff.value = arrtitle[1];
                  if(account_cd) account_cd.value = arrtitle[2];
                  if(account_nm) account_nm.innerHTML = arrtitle[3];
               }
               // fix cost up
               fixCostUp(recjsarray(data[1]));
            }
         }
         
         function moreless(uid,o_id,d) {
            var a = _gel('ml0_'+uid);
            var b = _gel('ml1_'+uid);
            var c = _gel('ml2_'+uid);
            if(d.value=='Detil') {
               if(a) a.style.display='';
               if(b) b.style.display='';
               if(c) c.style.display='';
               d.value='Ringkas';
            } else {
               if(a) a.style.display='none';
               if(b) b.style.display='none';
               if(c) c.style.display='none';
               d.value='Detil';
            }
         }
         
         function deleteObj(o_id,uniq0) {
            var td = _gel('objctrlxxx');
            td.oldHTML = td.innerHTML;
            td.innerHTML = '<br/>Anda akan menghapus objek ini<br/><br/>'
                         + '<input type=\"button\" value=\""._CANCEL."\" onclick=\"cancelDeleteObj(\''+o_id+'\');\"/>&nbsp;&nbsp;'
                         + '<input type=\"button\" value=\""._DELETE."\" onclick=\"confirmDeleteObj(\''+o_id+'\');\"/><br/><br/>';
            td.old_bg = td.style.backgroundColor;
            td.old_border = td.style.border;
            td.style.backgroundColor = '#ffcccc;';
            td.style.border = '1px solid black';
         }
         
         function cancelDeleteObj(o_id) {
            var td = _gel('objctrlxxx');
            td.style.backgroundColor = td.old_bg;;
            td.style.border = td.old_border;
            td.innerHTML = td.oldHTML;
         }
         
         function confirmDeleteObj(o_id) {
            pjx_app_deleteObject(o_id,function(_data) {
               if(_data=='OKAY') {
                  window.location = '".XOCP_SERVER_SUBDIR."/index.php?obj_delete=1';
               } else {
                  alert('Gagal Hapus');
               }
            });
         }
         
         function copyObj(o_id) {
            pjx_app_copyObject(o_id,function(_data){window.location='".XOCP_SERVER_SUBDIR."/index.php?".$this->varURL()."&editobject=y&obj_id='+_data;});
         }
         
         function copyCom(uniqid,o_id,c_id) {
            copyCom.obj = _gel('com_'+uniqid);
            copyCom.attach=function(_data) {
               var data = _data.split('[[separator]]');
               var n=document.createElement('div');
               n.setAttribute('id','com_'+data[0]);
               n.setAttribute('class','combox_edit');
               var c= copyCom.obj.nextSibling;
               copyCom.obj.parentNode.removeChild(copyCom.obj);
               n=c.parentNode.insertBefore(n,c);
               n.innerHTML = data[1];
            };
            pjx_app_dupReplace(uniqid,o_id,c_id,copyCom.attach);
         }
         
         // --></script>
         ";
      
      }
      
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
            if($_GET["searchq"] != '' && $_GET["nav"] == "" && $_GET["btn_new"] == "") {
               if(list($result,$comment)=$this->doSearch($_GET)) {
                  $ret = $this->searchForm($_GET,$comment) . "<br/>$result";
               } else {
                  $ret = $this->searchForm();
               }
            } elseif ($_GET["nav"] == "y") {
               $ret = $this->searchForm($_GET) ."<br/>". $this->navigate($_GET["f"],$_GET["p"]);
            } elseif ($_GET["btn_new"] != "") {
               $ret = $this->editForm();
            } elseif ($_GET["editobject"] == "y") {
               if(trim($_GET["obj_id"])=="") {
                  $ret = $this->searchForm();
               } else {
                  $_SESSION["ehr_obj_id"] = $_GET["obj_id"];
                  $ret = $this->editForm($_GET["obj_id"]);
               }
            } elseif ($_GET["newconcept"] == "y") {
               if($_GET["concept_id"] != "") {
                  $_SESSION["ehr_obj_old_concept_id"] = $_GET["concept_id"];
               }
               $browser = new _ehr_class_StdBrowse("CONCEPT",array("prefix"=>"panel_con"));
               $browser->stdb->setURLParam(XOCP_SERVER_SUBDIR."/index.php",array($this->catchvar=>$this->blockID,"newconcept"=>"y"));
               $ret = $browser->action();
               if($ret == "") {
                  $result = $browser->getResult();
                  $concept_id = $result["concept_id"];
                  $this->setConcept($_SESSION["ehr_obj_id"],$concept_id);
                  $ret = $this->editForm($_SESSION["ehr_obj_id"]);
               } else {
                  $urlobjectid = urlencode($_SESSION["ehr_obj_id"]);
                  $link = "<a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->varURL()
                       ."&editobject=y&obj_id=$urlobjectid'><img src='"
                       .XOCP_SERVER_SUBDIR."/images/return.gif' alt='return' border='0'/></a>";
                  $ret = XocpTheme::messageBox("$link <b>".$_SESSION["ehr_obj_id"]."</b> ".$_SESSION["ehr_obj_nm"],"info")
                       . "<br/>"._EHR_OBJ_SETCONCEPT."<br/><br/>$ret";
               }
               
            } elseif ($_GET["btn_save"] != "") {
               $db =& Database::getInstance();
               if($this->saveObject($_GET)) {
                  $_SESSION["ehr_obj_id"] = $_GET["obj_id"];
                  if($_GET["new_object"] == "1") {
                     //$this->setConcept($_GET["obj_id"],$this->concept_id);
                     //$sql = "INSERT INTO ".XOCP_PREFIX."ehr_obj_cell (obj_id,cell_id,cell_span) VALUES ('".$_GET["obj_id"]."','0','2')";
                     //$db->query($sql);
                  }
                  $ret = $this->editForm($_GET["obj_id"]);
               } else {
                  $ret = $this->editForm($_GET["obj_id"]);
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
   
} // EHR_OBJECT_DEFINED
?>