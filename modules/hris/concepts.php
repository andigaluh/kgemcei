<?php
//--------------------------------------------------------------------//
// Filename : modules/ehr/concepts/concepts.php                       //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2004-09-30                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('EHR_CONCEPTS_DEFINED') ) {
   define('EHR_CONCEPTS_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/ehr/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/ehr/class/concept.php");

class _ehr_Concepts extends XocpBlock {
   var $catchvar = _EHR_CATCH_VAR;
   var $blockID = _EHR_CONCEPT_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _EHR_CONCEPT_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $org_id;
   var $con;

   function _ehr_Concepts($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                            yang diteruskan ke konstruktor parent class */
      global $xocpConfig;
      
      $this->XocpBlock($catch);          /* ini meneruskan $catch ke parent constructor */
      
      $mylanguage = $_SESSION["xocp_user"]->getVar("language");
      if($mylanguage == '') {
         $mylanguage = $xocpConfig["language"];
      }
      $this->org_id = $_SESSION["ehr_org_id"];
      
      $this->language = $mylanguage;
      $this->con = new _ehr_class_Concept($this->varURL(),$this->varForm());
   }
   
   function getLanguage() {
      return $this->language;
   }


   function searchForm($datarec = NULL,$comment = NULL) {
      $db =& Database::getInstance();
      $sql = "SELECT con_class_id,con_class_nm"
           . " FROM ".XOCP_PREFIX."ehr_con_class_def"
           . " ORDER BY con_class_nm";
      $result = $db->query($sql);
      $options = "<option value=''>-</option>"
               . "<option value='nonumls'>Non UMLS</option>";
      if($db->getRowsNum($result)>0) {
         while(list($con_class_idx,$con_class_nmx)=$db->fetchRow($result)) {
            $options .= "<option value='$con_class_idx'>$con_class_nmx</option>";
         }
      }
      $ret = "<table class='tblfrm'>"
           . "<tr><td class='tblfrmtitle' colspan='2'>Cari Konsep</td></tr>"
           . "<tr><td class='tblfrmfieldname'>Kelas Konsep</td><td class='tblfrmfieldvalue'><select id='qcls'>$options</select></td></tr>"
           . "<tr><td class='tblfrmfieldname'>Cari</td><td class='tblfrmfieldvalue'><input type='text' style='width:300px;' id='qcon'/></td></tr>"
           . "<tr><td class='tblfrmbuttons' colspan='2'><input type='button' value='"._NEW."' class='bt' onclick='btn_new(this,event);'/></td></tr>"
           . "</table>";
      
      require_once(XOCP_DOC_ROOT."/modules/ehr/class/ajax_concept.php");
      $ajax = new _ehr_class_ConceptAjax("cjx");
      $ret .= $ajax->getJs() . "
      <script type='text/javascript' language='javascript'><!--
      ajax_feedback = null;
      var condv=null;
      function doNewCon(con_class_id) {
         window.location = '".XOCP_SERVER_SUBDIR."/index.php?X_ehr=".$this->blockID."&newconcept=y&con_class_id='+con_class_id;
      }
      btn_new=function(d,e) {
         if(!condv) {
            var n=document.createElement('div');
            n.setAttribute('class','traymenu');
            condv=document.body.appendChild(n);
         }
         condv.innerHTML='<div class=\'traymenuitem\' onclick=\'doNewCon(\"MDEV\");\'>Alat Medis</div>'
                        +'<div class=\'traymenuitem\' onclick=\'doNewCon(\"DEV_\");\'>Alat Non Medis</div>'
                        +'<div class=\'traymenuitem\' onclick=\'doNewCon(\"SBTN\");\'>Bahan</div>'

                        +'<div class=\'traymenuitem\' onclick=\'doNewCon(\"DRUG\");\'>Obat</div>'
                        +'<div class=\'traymenuitem\' onclick=\'doNewCon(\"PROC\");\'>Prosedur</div>'

                        +'<div class=\'traymenuitem\' onclick=\'doNewCon(\"MDCT\");\'>Prosedur Pengobatan</div>'
                        +'<div class=\'traymenuitem\' onclick=\'doNewCon(\"LAB_\");\'>Prosedur Laboratorium</div>'
                        +'<div class=\'traymenuitem\' onclick=\'doNewCon(\"DIET\");\'>Prosedur Diet</div>'
                        +'<div class=\'traymenuitem\' onclick=\'doNewCon(\"MSRV\");\'>Prosedur Akomodasi</div>'



                        +'<div class=\'traymenuitem\' onclick=\'doNewCon(\"BED_\");\'>Bed</div>'
                        +'<div class=\'traymenuitem\' onclick=\'doNewCon(\"DIAG\");\'>Diagnosis</div>'
                        +'<div class=\'traymenuitem\' onclick=\'doNewCon(\"SYMP\");\'>Gejala/Tanda</div>'
                        +'<div class=\'traymenuitem\' onclick=\'doNewCon(\"FOOD\");\'>Makanan</div>'
                        +'<div class=\'traymenuitem\' onclick=\'doNewCon(\"ORGZ\");\'>Organisasi</div>'
                        +'<div class=\'traymenuitem\' onclick=\'doNewCon(\"ACCT\");\'>Objek Akuntansi</div>'
                        // +'<div class=\'traymenuitem\' onclick=\'doNewCon(\"PNL_\");\'>Panel</div>'
                        // +'<div class=\'traymenuitem\' onclick=\'doNewCon(\"ROOM\");\'>Ruang</div>'
                        // +'<div class=\'traymenuitem\' onclick=\'doNewCon(\"AACT\");\'>Sub Tindakan</div>'
                        +'<div class=\'traymenuitem\' onclick=\'doNewCon(\"\");\'>Lain-lain</div>';
         if(condv.style.visibility=='visible') {
            condv.style.visibility='hidden';
         } else {
            e.cancelBubble=true;
            condv.style.top = oY(d)+d.offsetHeight+2+'px';
            condv.style.left = oX(d)+d.offsetWidth-condv.offsetWidth+'px';
            condv.style.visibility='visible';
            document.onclick=function() {
               document.onclick=null;
               condv.style.visibility='hidden';
            };
            return false;
         }
      };

      var qcon = _gel('qcon');
      qcon._get_param=function() {
         var qval = this.value;
         qval = trim(qval);
         if(qval.length < 2) {
            return '';
         }
         var qcls = _gel('qcls');
         var qcval = qcls.options[qcls.selectedIndex].value;
         return qval+'||'+qcval;
      };
      qcon._onselect=function(resId) {
         window.location = '".XOCP_SERVER_SUBDIR."/index.php?X_ehr=".$this->blockID."&editconcept=y&concept_id='+resId;
      }
      qcon._send_query = cjx_app_searchConcept;
      _make_ajax(qcon);
      qcon.focus();
      --></script>";
      
      return $ret;
   
   }

   function editForm($concept_id = NULL,$comment = NULL) {
      $db =& Database::getInstance();
      
      $class = array();
      
      if($concept_id != NULL) {
         $sql = "SELECT a.concept_nm,a.cui,a.sab,a.code,a.concept_def"
              . " FROM ".XOCP_PREFIX."ehr_concepts a"
              . " WHERE a.concept_id = '$concept_id'";
         $result = $db->query($sql);
         list($concept_nm,$cui,$sab,$code,$concept_def)=$db->fetchRow($result);
         
         $sql = "SELECT con_class_id FROM ".XOCP_PREFIX."ehr_con_class"
              . " WHERE concept_id = '$concept_id'";
         $result = $db->query($sql);
         if($db->getRowsNum($result)>0) {
            while(list($con_class_id)=$db->fetchRow($result)) {
               $class[$con_class_id] = 1;
            }
         }
         $ret = "<table class='tblfrm' id='frm'>"
              . "<tr><td class='tblfrmtitle' colspan='2'>"
              . "<a href='".XOCP_SERVER_SUBDIR."/'><img src='".XOCP_SERVER_SUBDIR."/images/return.gif' border='0'/></a>"
              . " Edit Konsep</td></tr>"
              . "<tr><td class='tblfrmfieldname'>ID</td><td class='tblfrmfieldvalue'>$concept_id</td></tr>"
              . "<tr><td class='tblfrmfieldname'>Nama Konsep</td><td class='tblfrmfieldvalue'><input type='text' style='width:300px;' id='concept_nm' value='$concept_nm'/></td></tr>"
              . "<tr><td class='tblfrmfieldname'>CUI</td><td class='tblfrmfieldvalue'><input type='text' value='$cui' id='cui' style='width:150px;'/></td></tr>"
              . "<tr><td class='tblfrmfieldvalue' colspan='2'>";
         $func_load = "setTimeout('_gel(\"concept_nm\").focus()',500);";
         $del_btn = "&nbsp;&nbsp;<input type='button' value='"._DELETE."' onclick='delete_concept(\"$concept_id\",this,event);'/>";
         $newx = "0";

      } else {
         $ret = "<table class='tblfrm' id='frm'>"
              . "<tr><td class='tblfrmtitle' colspan='2'>"
              . "<a href='".XOCP_SERVER_SUBDIR."/'><img src='".XOCP_SERVER_SUBDIR."/images/return.gif' border='0'/></a>"
              . " Edit Konsep</td></tr>"
              . "<tr><td class='tblfrmfieldname'>ID</td><td class='tblfrmfieldvalue'><input type='text' value='' id='concept_id' style='width:150px;'/></td></tr>"
              . "<tr><td class='tblfrmfieldname'>Nama Konsep</td><td class='tblfrmfieldvalue'><input type='text' style='width:300px;' id='concept_nm' value=''/></td></tr>"
              . "<tr><td class='tblfrmfieldname'>CUI</td><td class='tblfrmfieldvalue'><input type='text' value='' id='cui' style='width:150px;'/></td></tr>"
              . "<tr><td class='tblfrmfieldvalue' colspan='2'>";
         $func_load = "setTimeout('_gel(\"concept_id\").focus()',500);";
         $del_btn = "";
         $newx = "1";
      }
      
      $sql = "SELECT con_class_id,con_class_nm"
           . " FROM ".XOCP_PREFIX."ehr_con_class_def"
           . " ORDER BY con_class_nm";
      
      $result = $db->query($sql);
      $no = 0;
      if($db->getRowsNum($result)>0) {
         $ret .= "KELAS KONSEP:<table id='tblcls' width='100%'><tr>";
         while(list($con_class_idx,$con_class_nmx)=$db->fetchRow($result)) {
            if(isset($class[$con_class_idx])&&$class[$con_class_idx]==1) {
               $check = " checked='checked'";
            } else {
               $check = "";
            }
            if($no>0&&$no%4==0) {
               $ret .= "</tr><tr>";
            }
            $ret .= "<td><input type='checkbox' class='ckb' value='$con_class_idx' id='$con_class_idx' name='con_class'$check/> <label for='$con_class_idx'>$con_class_nmx</label></td>";
            $no++;
         }
         $ret .= "</tr></table>";
      }
      $ret .= "<tr><td class='tblfrmfieldvalue' colspan='2' style='text-align:right;padding:6px;'>"
            . "<input type='button' value='"._SAVE."' onclick='save_concept();'/>"
            . $del_btn
            . "</td></tr>";
      
      $ret .= "</td></tr><tr><td class='tblfrmfieldvalue' colspan='2'>";
      
      $sql = "SELECT a.AUI,a.SAB,a.CODE,a.STR,a.ISPREF,a.TTY,b.DEF"
           . " FROM "._EHR_UMLSDB.".MRCONSO a"
           . " LEFT JOIN "._EHR_UMLSDB.".MRDEF b USING(AUI)"
           . " WHERE a.CUI = '$cui' ORDER BY b.DEF DESC,a.SAB,a.CODE";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         $ret .= "METATHESAURUS:<table border='0' cellpadding='2' cellspacing='0' width='550' style='margin-top:5px;padding:0px;border:0px;'>";
         while(list($aui,$sab,$code,$str,$ispref,$tty,$def)=$db->fetchRow($result)) {
            $ret .= "<tr><td style='border-top:1px solid #aaaaee;'><span style='font-weight:bold;background-color:#cccccc;padding:2px;'>$sab</span></td>"
                  . "<td style='font-weight:bold;border-top:1px solid #aaaaee;'>$code</td><td style='border-top:1px solid #aaaaee;'>$tty/$ispref</td></tr>"
                  . "<tr><td colspan='3'>$str</td></tr>"
                  . "<tr><td colspan='3' style='color:#000099;'>$def</td></tr>";
         }
         $ret .= "</table>";
      } else {
         $ret .= "METATHESAURUS: -";
      }
      
      $ret .= "</td></tr>"
           . "</table>";
      
      require_once(XOCP_DOC_ROOT."/modules/ehr/class/ajax_concept.php");
      $ajax = new _ehr_class_ConceptAjax("cjx");
      $ret .= $ajax->getJs() . "<script type='text/javascript' language='javascript'><!--
      
      var concept_id = '".$concept_id."';
      var newx = $newx;
      
      function save_concept() {
         var frm = _gel('frm');
         
         var prg = document.createElement('div');
         prg.setAttribute('id','consv');
         prg.setAttribute('style','position:absolute;opacity:0.8;width:200px;background-color:#dddddd;padding:40px;color:#000000;border:1px solid black;');
         prg.style.top = parseInt(oY(frm)+100)+'px';
         prg.style.left = parseInt(oX(frm)+100)+'px';
         prg.innerHTML = ' <b>Simpan ... </b>';
         prg.insertBefore(img_circlewaitgrey,prg.firstChild);
         frm.parentNode.appendChild(prg);

         var tblcls = _gel('tblcls');
         var ckbs = tblcls.getElementsByTagName('input');
         var ret = '';
         var concept_nm = trim(_gel('concept_nm').value);
         for(var i=0;i<ckbs.length;i++) {
            if(ckbs[i].checked==true) {
               ret += '|' + ckbs[i].id;
            }
         }
         ret = urlencode(ret);
         var cui = _gel('cui').value;
         if(newx==1) {
            concept_id = trim(_gel('concept_id').value);
         }
         if(concept_id=='') {
            alert('Konsep ID masih kosong!');
            _destroy(_gel('consv'));
            _gel('concept_id').focus();
            return;
         }
         if(concept_nm=='') {
            alert('Nama konsep masih konsong!');
            _destroy(_gel('consv'));
            _gel('concept_nm').focus();
            return;
         }
         cjx_app_saveConcept(concept_id,concept_nm,cui,ret,newx,function(_data) {
            if(_data=='DUPLICATE') {
               alert('Duplikasi konsep ID.');
               _destroy(_gel('consv'));
               return;
            }
            _destroy(_gel('consv'));
            window.location = '".XOCP_SERVER_SUBDIR."/index.php?X_ehr=".$this->blockID."&editconcept=y&concept_id='+_data;
         });
      }
      
      
      var xtd = null;
      function delete_concept(concept_id,d,e) {
         xtd = d.parentNode;
         xtd.oldHTML = xtd.innerHTML;
         xtd.style.backgroundColor = '#ffcccc';
         xtd.innerHTML = 'Anda akan menghapus konsep ini?<br/><br/>'
                       + '<input type=\"button\" value=\""._CANCEL."\" onclick=\"cancel_delete();\"/>&nbsp;&nbsp;'
                       + '<input type=\"button\" value=\""._DELETE."\" onclick=\"confirm_delete(\\''+concept_id+'\\');\"/>';
      }
      
      function cancel_delete() {
         xtd.innerHTML = xtd.oldHTML;
         xtd.style.backgroundColor = '';
      }
      
      function confirm_delete(concept_id) {
         cjx_app_deleteConcept(concept_id,function(_data) {
            if(_data>0) {
               alert('Konsep telah memiliki manifestasi sebanyak '+_data+' objek. Penghapusan gagal.');
               return;
            } else {
               window.location = '".XOCP_SERVER_SUBDIR."/index.php';
            }
         });
      }
      
      $func_load
      
      // --></script>";
      return $ret;
   }

   function createConcept($con_class_id) {
      $db=&Database::getInstance();
      
      $_ehr_concept_id_prefix["AACT"] = "cAACTx";
      $_ehr_concept_id_prefix["BED_"] = "cBED_x";
      $_ehr_concept_id_prefix["DIAG"] = "cDIAGx";
      $_ehr_concept_id_prefix["DEV_"] = "cDEV_x";
      $_ehr_concept_id_prefix["DRUG"] = "cDRx";
      $_ehr_concept_id_prefix["FOOD"] = "cFOODx";
      $_ehr_concept_id_prefix["LAB_"] = "cLAB_x";
      $_ehr_concept_id_prefix["MDEV"] = "cMDEVx";
      $_ehr_concept_id_prefix["ORGZ"] = "cORGZx";
      $_ehr_concept_id_prefix["PNL_"] = "cPNL_x";
      $_ehr_concept_id_prefix["PROC"] = "cPROCx";
      $_ehr_concept_id_prefix["MDCT"] = "cMDCTx";
      $_ehr_concept_id_prefix["LAB_"] = "cLABx";
      $_ehr_concept_id_prefix["DIET"] = "cDIETx";
      $_ehr_concept_id_prefix["MSRV"] = "cMSRVx";
      $_ehr_concept_id_prefix["ROLE"] = "cROLEx";
      $_ehr_concept_id_prefix["ROOM"] = "cROOMx";
      $_ehr_concept_id_prefix["SBTN"] = "cSBTNx";
      $_ehr_concept_id_prefix["SYMP"] = "cSYMPx";
      $_ehr_concept_id_prefix["ACCT"] = "cACCTx";
      
      $_ehr_concept_id_seglen["AACT"] = 8;
      $_ehr_concept_id_seglen["BED_"] = 8;
      $_ehr_concept_id_seglen["DIAG"] = 8;
      $_ehr_concept_id_seglen["DEV_"] = 8;
      $_ehr_concept_id_seglen["DRUG"] = 9;
      $_ehr_concept_id_seglen["FOOD"] = 8;
      $_ehr_concept_id_seglen["LAB_"] = 8;
      $_ehr_concept_id_seglen["MDEV"] = 8;
      $_ehr_concept_id_seglen["ORGZ"] = 8;
      $_ehr_concept_id_seglen["PNL_"] = 8;
      $_ehr_concept_id_seglen["PROC"] = 8;
      $_ehr_concept_id_seglen["MDCT"] = 8;
      $_ehr_concept_id_seglen["LAB_"] = 8;
      $_ehr_concept_id_seglen["DIET"] = 8;
      $_ehr_concept_id_seglen["MSRV"] = 8;
      $_ehr_concept_id_seglen["ROLE"] = 8;
      $_ehr_concept_id_seglen["ROOM"] = 8;
      $_ehr_concept_id_seglen["SBTN"] = 8;
      $_ehr_concept_id_seglen["SYMP"] = 8;
      $_ehr_concept_id_seglen["ACCT"] = 8;
      
      $_ehr_concept_nm_default["AACT"] = "[ nama konsep sub tindakan ]";
      $_ehr_concept_nm_default["BED_"] = "[ nama konsep bed]";
      $_ehr_concept_nm_default["DIAG"] = "[ nama konsep diagnosis ]";
      $_ehr_concept_nm_default["DEV_"] = "[ nama konsep alat non medis ]";
      $_ehr_concept_nm_default["DRUG"] = "[ nama konsep obat ]";
      $_ehr_concept_nm_default["FOOD"] = "[ nama konsep makanan ]";
      $_ehr_concept_nm_default["LAB_"] = "[ nama konsep pemeriksaan lab ]";
      $_ehr_concept_nm_default["MDEV"] = "[ nama konsep alat medis ]";
      $_ehr_concept_nm_default["ORGZ"] = "[ nama konsep organisasi ]";
      $_ehr_concept_nm_default["PNL_"] = "[ nama konsep panel ]";
      $_ehr_concept_nm_default["PROC"] = "[ nama konsep prosedur ]";
      $_ehr_concept_nm_default["MDCT"] = "[ nama konsep prosedur pengobatan ]";
      $_ehr_concept_nm_default["LAB_"] = "[ nama konsep prosedur laboratorium ]";
      $_ehr_concept_nm_default["DIET"] = "[ nama konsep prosedur diet ]";
      $_ehr_concept_nm_default["MSRV"] = "[ nama konsep prosedur akomodasi ]";
      $_ehr_concept_nm_default["ROLE"] = "[ nama konsep peran ]";
      $_ehr_concept_nm_default["ROOM"] = "[ nama konsep ruang ]";
      $_ehr_concept_nm_default["SBTN"] = "[ nama konsep bahan ]";
      $_ehr_concept_nm_default["SYMP"] = "[ nama konsep gejala/tanda ]";
      $_ehr_concept_nm_default["ACCT"] = "[ nama konsep akuntansi ]";
      
      $prefix = $_ehr_concept_id_prefix[$con_class_id];
      $new_concept_nm = $_ehr_concept_nm_default[$con_class_id];
      $seglen = $_ehr_concept_id_seglen[$con_class_id];
      
      while(1) {
         $sql = "SELECT MAX(concept_id) FROM ".XOCP_PREFIX."ehr_concepts"
              . " WHERE concept_id LIKE '".$prefix."%'";
         $result = $db->query($sql);
         list($max) = $db->fetchRow($result);
         $num = substr($max,strlen($prefix),$seglen) + 1;
         $new_concept_id = $prefix . sprintf("%0".$seglen."d",$num);
         $sql = "INSERT INTO ".XOCP_PREFIX."ehr_concepts (concept_id,concept_nm)"
              . " VALUES('$new_concept_id','$new_concept_nm')";
         $db->query($sql);
         if($db->errno()!=1062) break;
      }
      $sql = "INSERT INTO ".XOCP_PREFIX."ehr_con_class (concept_id,con_class_id) VALUES ('$new_concept_id','$con_class_id')";
      $db->query($sql);
      return $new_concept_id;
   }



   
   function main() {
      
      switch($this->catch) {
         case $this->blockID :
            if ($_GET["newconcept"] == "y") {
               ////// generate concept_id if con_class_id != ''
               $con_class_id = $_GET["con_class_id"];
               if($con_class_id != "") {
                  $concept_id = $this->createConcept($con_class_id);
                  $ret = $this->editForm($concept_id);
               } else {
                  $ret = $this->editForm();
               }
            } elseif ($_GET["editconcept"] == "y") {
               $_SESSION["ehr_con_concept_id"] = $_GET["concept_id"];
               $ret = $this->editForm($_GET["concept_id"]);
            } elseif ($_GET["btn_adddomain"] != "") {
            } elseif ($_GET["btn_deldom"] != "") {
            } elseif ($_GET["btn_save"] != "") {
            } elseif ($_POST["btn_canceldelete"] != "") {
               $ret = $this->editForm($_POST["concept_id"]);
            } elseif ($_GET["btn_delete"] != "") {
               $ret = $this->con->confirmDelete($_GET["concept_id"]);
            } elseif ($_POST["btn_suredelete"] != "") {
            } else {
               $ret = $this->searchForm();
            }
            break;
         default :
            $ret = $this->searchForm();
            break;
      }
      return "<br/>".$ret;
   }

}

} // EHR_CONCEPTS_DEFINED
?>