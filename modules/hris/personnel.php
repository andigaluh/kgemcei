<?php
//--------------------------------------------------------------------//
// Filename : modules/ehr/newemployee.php                             //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-11-06                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('EHR_EMPLOYEE_DEFINED') ) {
   define('EHR_EMPLOYEE_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/ehr/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/ehr/include/vocab.php");
include_once(XOCP_DOC_ROOT."/modules/ehr/class/selectorg.php");
include_once(XOCP_DOC_ROOT."/modules/ehr/class/selectemployee.php");
include_once(XOCP_DOC_ROOT."/modules/ehr/class/ajax_employee.php");

global $slemp;
$slemp = new _ehr_class_SelectEmployee();
$slemp->btn_new = TRUE;

class _ehr_Employee extends XocpBlock {
   var $catchvar = _EHR_CATCH_VAR;
   var $blockID = _EHR_EMPLOYEE_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _EHR_EMPLOYEE_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _ehr_Employee($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                            yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);          /* ini meneruskan $catch ke parent constructor */
      
   }
   
   function newForm() {
      $ret = "<table class='tab' border='0' cellpadding='0' cellspacing='0'><tbody><tr>"
           . "<td class='tab_s'>Data Personal Baru</td>"
           . "</tr></tbody></table>";
      $txt = "<div class='hdr'>"
           . "<table border='0' cellpadding='0' cellspacing='0' width='100%'><tr><td style='text-align:right;'>&nbsp;"
           . "</td></tr></table>"
           . "</div>"
           . "<div id='frmperson' style='padding:4px;'>"
           . "<table class='xxfrm'>"
           . "<tbody>"
           . "<tr><td>Nama Lengkap [, Gelar]</td><td><input id='person_nm' type='text' value='$person_nm' style='width:250px;'/></td></tr>"
           . "<tr><td>No. Pegawai</td><td><input id='employee_ext_id' type='text' value='$employee_ext_id' style='width:200px;'/></td></tr>"
           . "<tr><td colspan='2'><input type='button' value='"._SAVE."' onclick='save_person(this,event);'/>"
           . "&nbsp;&nbsp;<input type='button' value='"._CANCEL."' onclick='cancel_new();'/></td></tr>"
           . "</tbody>"
           . "</table>"
           . "</div>";
      $person_txt = "<div id='tab_person'>$txt</div>";
      $ret .= "<div id='gdiv' style='padding:5px;background-color:white;color:black;border-left:1px solid black;'>$person_txt</div>";
      $ajax = new _ehr_class_EmployeeAjax("emp");
      $js = $ajax->getJs();
      return $ret.$js."<script type='text/javascript' language='javascript'><!--
      
      function cancel_new() {
         window.location = '".XOCP_SERVER_SUBDIR."/index.php';
      }
      
      function save_person(d,e) {
         var ret = '';
         var frm = _gel('frmperson');
         var arr_inp = frm.getElementsByTagName('input');
         for(var i=0;i<arr_inp.length;i++) {
            ret += arr_inp[i].id + '=' + arr_inp[i].value + '|';
         }
         _caf.txt = ' ... simpan pegawai baru';
         ajax_feedback = _caf;
         SetCookie('emptab','tab01');
         emp_app_savePerson(urlencode(ret),'new',function(_data) {
            window.location = '".XOCP_SERVER_SUBDIR."/index.php';
         });
      
      }
      setTimeout('_gel(\"person_nm\").focus()',100);
      
      // --></script>";
   }
   
   function panel($new=0) {
      global $slemp;
      $db=&Database::getInstance();
      if(0) {
         $sql = "INSERT INTO ".XOCP_PREFIX."persons (person_nm,status_cd) VALUES ('nama lengkap','new')";
         $result = $db->query($sql);
         $person_id = $db->getInsertId();
         $sql = "INSERT INTO ".XOCP_PREFIX."ehr_employee (status_cd,person_id)"
              . " VALUES ('new','$person_id')";
         $db->query($sql);
         $employee_id = $db->getInsertId();
         $_SESSION["ehr_employee_id"] = $employee_id;
         $_SESSION["ehr_employee_person_id"] = $person_id;
      } else {
         $employee_id = $_SESSION["ehr_employee_id"];
         $person_id = $_SESSION["ehr_employee_person_id"];
      }
      $disp01 = "display:none;";
      $disp02 = "display:none;";
      $disp03 = "display:none;";
      $disp04 = "display:none;";
      $tabsel01 = "tab_n";
      $tabsel02 = "tab_n";
      $tabsel03 = "tab_n";
      $tabsel04 = "tab_n";
      if($new==1) {
         $disp01 = "";
         $tabsel01 = "tab_s";
      } else {
         switch($_COOKIE["emptab"]) {
            case "tab02":
               $disp02 = "";
               $tabsel02 = "tab_s";
               break;
            case "tab03":
               $disp03 = "";
               $tabsel03 = "tab_s";
               break;
            case "tab04":
               $disp04 = "";
               $tabsel04 = "tab_s";
               break;
            default:
               $disp01 = "";
               $tabsel01 = "tab_s";
               break;
         }
      }
      $ret = "<table class='tab' border='0' cellpadding='0' cellspacing='0'><tbody><tr>"
           . "<td id='tab01' onclick='seltab(\"tab01\");' class='$tabsel01'>Data Personal</td>"
           . "<td id='tab03' onclick='seltab(\"tab03\");' class='$tabsel03'>Peran/Tugas</td>"
           . "<td id='tab04' onclick='seltab(\"tab04\");' class='$tabsel04'>Hak Akses</td>"
           . "</tr></tbody></table>";
      
      //////////////////////////////////////// PERSONAL TAB ////////////////////////////////////
      
      $sql = "SELECT a.person_nm,a.ssn,a.ext_id,a.birth_dttm,a.birthplace,a.adm_gender_cd,a.addr_txt,"
           . "a.regional_cd,a.zip_cd,a.telecom,a.blood_t,a.marital_st,a.education,a.jobclass,a.status_cd,"
           . "b.employee_ext_id"
           . " FROM ".XOCP_PREFIX."persons a"
           . " LEFT JOIN ".XOCP_PREFIX."ehr_employee b USING(person_id)"
           . " WHERE a.person_id = '$person_id'";
      $result = $db->query($sql);
      list($person_nm,$ssn,$ext_id,$birth_dttm,$birthplace,$adm_gender_cd,$addr_txt,
           $regional_cd,$zip_cd,$telecom,$blood_t,$marital_st,$education,$jobclass,
           $status_cd,$employee_ext_id) = $db->fetchRow($result);
      
      if($birth_dttm==""||$birth_dttm == "0000-00-00 00:00:00") {
         $birth_dttm = "1900-01-01 00:00:00";
      }
      
      $jk["m"] = $jk["f"] = "";
      $jk[$adm_gender_cd] = " checked='1'";
      
      if($regional_cd!="") {
         require_once(XOCP_DOC_ROOT."/modules/system/class/gis_region.php");
         $region_txt = _system_class_GISRegion::getRegionText($regional_cd);
      } else {
         $region_txt = _EMPTY;
      }
      $blood_ck["A"] = $blood_ck["B"] = $blood_ck["0"] = $blood_ck["AB"] = "";
      $marital_sel["mar"] = $marital_sel["sng"] = $marital_sel["dvc"] = $marital_sel["wid"] = "";
      list($phone,$fax,$handphone,$email)=explode("|",$telecom);
      $blood_ck[$blood_t] = "checked='checked'";
      $marital_sel[$marital_st] = "selected='selected'";
      global $arr_marital;
      $mar_opt = "";
      foreach($arr_marital as $k=>$v) {
         $mar_opt .= "<option value='$k' ".$marital_sel[$k].">$v</option>";
      }
      $educ_sel[$education] = "selected='selected'";
      
      $txt = "<div class='hdr'>"
           . "<table border='0' cellpadding='0' cellspacing='0' width='100%'><tr><td style='text-align:right;'>"
           . "<input type='button' value='Hapus' onclick='delete_employee(this,event);'/>"
           . "</td></tr></table>"
           . "</div>"
           . "<div id='frmperson' style='padding:4px;'>"
           . "<input type='hidden' id='person_id' value='$person_id'/>"
           . "<table class='xxfrm'>"
           . "<tbody>"
           . "<tr><td>Fullname [, Title]</td><td><input id='person_nm' type='text' value='$person_nm' style='width:250px;'/></td></tr>"
           . "<tr><td>Employee ID</td><td><input id='employee_ext_id' type='text' value='$employee_ext_id' style='width:200px;'/></td></tr>"
           . "<tr><td>No. KTP/SIM/ID</td><td><input id='ext_id' type='text' value='$ext_id' style='width:200px;'/></td></tr>"
           . "<tr><td>Tanggal Lahir</td><td>"
              . "<span class='xlnk' onclick='showCal(this,event);' id='birth_dttm_txt'>".sql2ind($birth_dttm)."</span>"
              . "<input type='hidden' id='birth_dttm' value='$birth_dttm'/>"
           . "</td></tr>"
           . "<tr><td>Tempat Lahir</td><td><input id='birthplace' type='text' value='$birthplace' style='width:200px;'/></td></tr>"
           . "<tr><td>Jenis Kelamin</td><td>"
              . "<input type='radio' name='adm_gender_cd' id='gender_m' value='m' ".$jk["m"]."/> <label for='gender_m' class='xlnk'>Laki-laki</label>&nbsp;&nbsp;"
              . "<input type='radio' name='adm_gender_cd' id='gender_f' value='f' ".$jk["f"]."/> <label for='gender_f' class='xlnk'>Perempuan</label>"
           . "</td></tr>"
           . "<tr><td>Alamat</td><td><input id='addr_txt' type='text' value='$addr_txt' style='width:300px;'/></td></tr>"
           . "<tr><td>Wilayah Alamat</td><td>"
              . "<span id='region_txt' class='xlnk' onclick='_getRegion(this,selregion,\"$regional_cd\");'>$region_txt</span>"
              . "<input type='hidden' id='regional_cd' value='$regional_cd'/>"
              . "<div id='_regionDiv' style='display:none;padding:2px;border:1px solid #888888;margin:2px;background-color:#ddddff;'></div>"
           . "</td></tr>"
           . "<tr><td>Kode Pos</td><td><input id='zip_cd' type='text' value='$zip_cd' size='5' maxlength='5'/></td></tr>"
           . "<tr><td>Telepone</td><td><input id='telephone' type='text' value='$phone' style='width:200px;'/></td></tr>"
           . "<tr><td>Fax</td><td><input id='fax' type='text' value='$fax' style='width:200px;'/></td></tr>"
           . "<tr><td>HP</td><td><input id='hp' type='text' value='$handphone' style='width:200px;'/></td></tr>"
           . "<tr><td>E-mail</td><td><input id='email' type='text' value='$email' style='width:250px;'/></td></tr>"
           . "<tr><td>Golongan Darah</td><td>"
              . "<input type='radio' value='A' id='blood_a' name='blood_t' $blood_ck[A]/><label for='blood_a' class='xlnk'>A</label>&nbsp;"
              . "<input type='radio' value='B' id='blood_b' name='blood_t' $blood_ck[B]/><label for='blood_b' class='xlnk'>B</label>&nbsp;"
              . "<input type='radio' value='0' id='blood_0' name='blood_t' $blood_ck[0]/><label for='blood_0' class='xlnk'>0</label>&nbsp;"
              . "<input type='radio' value='AB' id='blood_ab' name='blood_t' $blood_ck[AB]/><label for='blood_ab' class='xlnk'>AB</label>"
           . "</td></tr>"
           . "<tr><td>Status Perkawinan</td><td>"
              . "<select id='marital_st'>$mar_opt</select>"
           . "</td></tr>"
           . "<tr><td>Pendidikan</td><td>"
              . "<select id='education'>"
                 . "<option value='1' $educ_sel[1]>Pre School</option>"
                 . "<option value='2' $educ_sel[2]>Elementary School</option>"
                 . "<option value='3' $educ_sel[3]>Junior High School</option>"
                 . "<option value='4' $educ_sel[4]>Senior High School</option>"
                 . "<option value='5' $educ_sel[5]>Diploma</option>"
                 . "<option value='6' $educ_sel[6]>Under Graduate</option>"
                 . "<option value='7' $educ_sel[7]>Graduate</option>"
                 . "<option value='8' $educ_sel[8]>Master</option>"
                 . "<option value='9' $educ_sel[9]>Doctoral</option>"
              . "</select>"
           . "</td></tr>"
           . "<tr><td colspan='2'><input type='button' value='"._SAVE."' onclick='save_person(this,event);'/></td></tr>"
           . "</tbody>"
           . "</table>"
           . "</div>";
      require_once(XOCP_DOC_ROOT."/class/ajax/ajax_map.php");
      $ajaxmap = new _class_MapAjax();
      $txt .= $ajaxmap->getJs() 
           . "<script src='/ehr/include/map.js' type='text/javascript'></script>";
      $person_txt = "<div id='tab_person' style='$disp01'>$txt</div>";
      
      //////////////////////////////////////// ROLE TAB ////////////////////////////////////////
      
      $sql = "SELECT a.role_id,TRIM(b.concept_nm) as c_nm"
           . " FROM ".XOCP_PREFIX."ehr_role_plan a"
           . " LEFT JOIN ".XOCP_PREFIX."ehr_concepts b ON b.concept_id = a.role_id"
           . " WHERE a.employee_id = '$employee_id'"
           . " GROUP BY a.role_id"
           . " ORDER BY c_nm";
      $result = $db->query($sql);
      $txt = "<div id='hdrrole' class='hdr'>"
           . "<table border='0' cellpadding='0' cellspacing='0' width='100%'>"
           . "<td style='text-align:right;'>"
           . "Cari Role : <input type='text' id='qrole'/>&nbsp;"
           . "<input type='button' value='"._IMPORT."' onclick='import_role(this,event);'/>&nbsp;"
//           . "<input type='button' value='"._ADD."' onclick='add_role(this,event);'/>&nbsp;&nbsp;"
           . "<input type='button' value='"._DELETE."' onclick='delete_employee(this,event);'/></td></tr></table>"
           . "</div>";
      if($db->getRowsNum($result)>0) {
         while(list($concept_id,$concept_nm)=$db->fetchRow($result)) {
            if($concept_nm=="") {
               $sql = "DELETE FROM ".XOCP_PREFIX."ehr_role_plan WHERE role_id = '$concept_id'";
               $db->query($sql);
               continue;
            }
            $txt .= "<div id='c_${concept_id}' class='lst'>$concept_id <span onclick='edit_role(\"$concept_id\",this,event);' class='xlnk'>$concept_nm</span></div>\n";
         }
      }
      $role_txt = "<div id='tab_role' style='$disp03'>$txt</div>";
      
      //////////////////////////////////////// ACCESS TAB ////////////////////////////////////
      
      $txt = "<div class='hdr'>"
           . "<table border='0' cellpadding='0' cellspacing='0' width='100%'><tr><td style='text-align:right;'>"
           . "<input type='button' value='Hapus' onclick='delete_employee(this,event);'/>"
           . "</td></tr></table>"
           . "</div>";
      
      $txt .= "<div id='frmaccess' style='padding:4px;'>"
           . _ehr_class_EmployeeAjax::editLogin($person_id)
           . "</div>";
      
      $access_txt = "<div id='tab_access' style='$disp04'>$txt</div>";
      
      //////////////////////////////////////////////////////////////////////////////////////////
      
      $ret .= "<div id='gdiv' style='padding:5px;background-color:white;color:black;border-left:1px solid black;'>$person_txt\n$role_txt\n$access_txt</div>";
      $ajax = new _ehr_class_EmployeeAjax("emp");
      $js = $ajax->getJs()."<script src='".XOCP_SERVER_SUBDIR."/include/calendar.js' type='text/javascript'></script>
      <script type='text/javascript'><!--

      ajax_feedback = null;
      var qrole = _gel('qrole');
      qrole._get_param=function() {
         var qval = this.value;
         qval = trim(qval);
         if(qval.length < 2) {
            return '';
         }
         return qval;
      };
      qrole._onselect=function(resId) {
         _caf.txt = ' ... tambah';
         ajax_feedback=_caf;
         emp_app_addRole(resId,function(_data) {
            if(_data=='FAIL') {
               alert('Gagal tambah.');
               return;
            }
            var data = recjsarray(_data);
            var dv = _dce('div');
            dv.setAttribute('id','c_'+data[0]);
            dv.className = 'lst';
            dv.innerHTML = '<span style=\"color:blue;\">'+data[0]+'</span> '
                         + '<span style=\"color:blue;\" onclick=\"edit_role(\''+data[0]+'\',this,event);\" class=\"xlnk\">'+data[1]+'</span>';
            var hdr = _gel('hdrrole');
            dv = hdr.parentNode.insertBefore(dv,hdr.nextSibling);
         });
      };
      qrole._send_query = emp_app_searchRole;
      _make_ajax(qrole);
      qrole.focus();
      
      var person_nm = _gel('person_nm');
      var g_tab = null;
      var delete_st = 0;
      function seltab(id) {
         if(delete_st==1) {
            return;
         }
         if(cur_btn&&cur_btn._showResult) {
            cur_btn._showResult(false);
            cur_btn = null;
         }
         
         if(qlogin&&qlogin._showResult) {
            qlogin._showResult(false);
            qlogin._reset();
         }

         if(g_tab==id) {
            return;
         }
         _gel('tab01').className = 'tab_n';
         _gel('tab03').className = 'tab_n';
         _gel('tab04').className = 'tab_n';
         _gel(id).className = 'tab_s';
         g_tab = id;
         SetCookie('emptab',id);
         switch(id) {
            case 'tab01':
               _gel('tab_access').style.display = 'none';
               _gel('tab_role').style.display = 'none';
               _gel('tab_person').style.display = '';
               if(person_nm) {
                  setTimeout('person_nm.focus()',500);
               }
               break;
            case 'tab02':
               _gel('tab_access').style.display = 'none';
               _gel('tab_role').style.display = 'none';
               _gel('tab_person').style.display = 'none';
               break;
            case 'tab03':
               _gel('tab_access').style.display = 'none';
               _gel('tab_person').style.display = 'none';
               _gel('tab_role').style.display = '';
               break;
            case 'tab04':
               _gel('tab_role').style.display = 'none';
               _gel('tab_person').style.display = 'none';
               _gel('tab_access').style.display = '';
               if(qlogin) {
                  setTimeout('qlogin.focus()',500);
               }
               break;
            default:
               _gel('tab_access').style.display = 'none';
               _gel('tab_person').style.display = '';
               _gel('tab_role').style.display = 'none';
               if(person_nm) {
                  setTimeout('person_nm.focus()',500);
               }
               break;
         }
      }
      
      
      /////////////////////////////// ROLE ////////////////////////////
      
      var cur_edit = null;
      function edit_role(concept_id,d,e) {
         if(cur_edit==d) {
            _destroy(cur_edit.edit);
            cur_edit = null;
            return;
         } else if(cur_edit!=null) {
            _destroy(cur_edit.edit);
         }
         cur_edit = d;
         cur_edit.dv = _gel('c_'+concept_id);
         cur_edit.edit = cur_edit.dv.appendChild(_dce('div'));
         cur_edit.edit.appendChild(progress_span(' ... proses'));
         emp_app_editRole(concept_id,function(_data) {
            cur_edit.edit.innerHTML = _data;
         });
      }
      
      function save_role(role_id,d,e) {
         var tbl = _gel('tblrole_'+role_id);
         var inp = tbl.getElementsByTagName('input');
         var ret = '';
         for(var i = 0; i < inp.length; i++) {
            if(inp[i].id.substring(0,3)=='btn') continue;
            ret += inp[i].id+'|'+inp[i].value+';';
         }
         emp_app_saveRole(role_id,urlencode(ret),function(_data) {
            _destroy(cur_edit.edit);
            cur_edit = null;
         });
         cur_edit.edit.innerHTML = '';
         cur_edit.edit.appendChild(progress_span(' ... simpan'));
      }
      
      function delete_role(role_id,d,e) {
         cur_edit.edit.oldHTML = cur_edit.edit.innerHTML;
         cur_edit.edit.innerHTML = '';
         var dv = _dce('div');
         dv.className = 'msg_delete_warn';
         dv.innerHTML = 'Anda akan menghapus peran ini?<br/><br/>'
                      + '<input type=\"button\" value=\""._CANCEL."\" onclick=\"cancel_delete_role();\"/>&nbsp;&nbsp;'
                      + '<input type=\"button\" value=\""._DELETE."\" onclick=\"confirm_delete_role(\''+role_id+'\');\"/>';
         cur_edit.edit.appendChild(dv);
      }
      
      function cancel_delete_role() {
         cur_edit.edit.innerHTML = cur_edit.edit.oldHTML;
      }
      
      function confirm_delete_role(role_id) {
         _destroy(_gel('c_'+role_id));
         cur_edit = null;
         emp_app_deleteRole(role_id,null);
      }
      
      var cur_btn = null;
      function add_role(d,e) {
         if(cur_btn&&cur_btn!=d) {
            cur_btn._showResult(false);
         }
         cur_btn = d;
         if(!d._dropdownized) {
            d._get_param=function() {
               return 'ok';
            };
            d._align='right';
            d._send_query=emp_app_getRoleList;
            d._onselect=function(id,nm) {
               _caf.txt = ' ... tambah';
               ajax_feedback=_caf;
               emp_app_addRole(id,function(_data) {
                  if(_data=='FAIL') {
                     alert('Gagal tambah.');
                     return;
                  }
                  var data = recjsarray(_data);
                  var dv = _dce('div');
                  dv.setAttribute('id','c_'+data[0]);
                  dv.className = 'lst';
                  dv.innerHTML = '<span style=\"color:blue;\">'+data[0]+'</span> '
                               + '<span style=\"color:blue;\" onclick=\"edit_role(\''+data[0]+'\',this,event);\" class=\"xlnk\">'+data[1]+'</span>';
                  var hdr = _gel('hdrrole');
                  dv = hdr.parentNode.insertBefore(dv,hdr.nextSibling);
               });
            };
            _make_dropdown(d);
         }
         if(d._subres.style.display!='block') {
            d._query();
         } else {
            document.onkeypress = null;
            d._showResult(false);
         }
      }
      
      function import_role(d,e) {
         if(cur_btn&&cur_btn!=d) {
            cur_btn._showResult(false);
         }
         cur_btn = d;
         if(!d._dropdownized) {
            d._get_param=function() {
               return 'ok';
            };
            d._align='right';
            d._send_query=emp_app_getImportRoleList;
            d._onselect=function(id,nm) {
               _caf.txt = ' ... import';
               ajax_feedback = _caf;
               emp_app_importRole(id,function(_data) {
                  if(_data=='FAIL') {
                     alert('Gagal Import.');
                     return;
                  }
                  var data = recjsarray(_data);
                  var hdr = _gel('hdrrole');
                  for(var i=0;i<data.length;i++) {
                     var role_id = data[i][0];
                     var role_nm = data[i][1];
                     var dv = null;
                     if(_gel('c_'+role_id)) {
                        dv = _gel('c_'+role_id);
                     } else {
                        dv = _dce('div');
                        dv.setAttribute('id','c_'+role_id);
                        dv.className = 'lst';
                        dv = hdr.parentNode.insertBefore(dv,hdr.nextSibling);
                     }
                     dv.innerHTML = '<span style=\"color:blue;\">'+role_id+'</span> '
                                  + '<span style=\"color:blue;\" onclick=\"edit_role(\''+role_id+'\',this,event);\" class=\"xlnk\">'+role_nm+'</span>';
                  }
               });
            };
            _make_dropdown(d);
         }
         if(d._subres.style.display!='block') {
            d._query();
         } else {
            document.onkeypress = null;
            d._showResult(false);
            d.onkeydown = null;
         }
      }
      
      
      ///////////////////////////// PERSON //////////////////////////
      
      _regionDiv = _gel('_regionDiv');
      
      function selregion() {
         _gel('regional_cd').value = _regionDiv._resVal;
         _gel('region_txt').innerHTML = _regionDiv._resNam;
      }
      
      var obdt = null;
      function setDT(dt) {
         if(obdt.obj.sync==true) {
            var dtstr = obdt.obj.getResult().split(' ');
            _gel('birth_dttm_txt').innerHTML = obdt.obj.toString(dtstr[0]) + ' \/ sync';
            SetCookie('dobsync','1');
         } else {
            _gel('birth_dttm_txt').innerHTML = obdt.obj.toString(obdt.obj.getResult(),'datetime');
            SetCookie('dobsync','0');
         }
         _gel('birth_dttm').value = dt;
         var dyx = XOCP_CURRENT_DATE.split('-');
         SetCookie('dobval',dt);
      }
      var cal = new calendarClass('obdt',setDT);
      cal.div.style.position = 'absolute';
      cal.div.style.left = '0px';
      cal.div.style.top = '0px';
      cal.div.style.visibility = 'hidden';
      obdt=document.body.appendChild(cal.div);
      obdt.obj.setDTTM('$birth_dttm');
      
      function showCal(d,e) {
         if(obdt.style.visibility=='hidden') {
            obdt.style.left = (oX(d))+'px';
            obdt.style.top = (oY(d)+d.offsetHeight)+'px';
            obdt.style.visibility='visible';
            //document.onmousedown=function() {
              // obdt.style.visibility='hidden';
           //    document.onmousedown=null;
           // };
         } else {
            obdt.obj.hide();
         }
      }
      
      function save_person(d,e) {
         var ret = '';
         var frm = _gel('frmperson');
         var arr_inp = frm.getElementsByTagName('input');
         var arr_sel = frm.getElementsByTagName('select');
         for(var i=0;i<arr_inp.length;i++) {
            if(arr_inp[i].getAttribute('type')=='radio') {
               if(!arr_inp[i].checked) {
                  continue;
               } else {
                  ret += arr_inp[i].name + '=' + arr_inp[i].value + '|';
                  continue;
               }
            }
            ret += arr_inp[i].id + '=' + arr_inp[i].value + '|';
         }
         for(var i=0;i<arr_sel.length;i++) {
            ret += arr_sel[i].id + '=' + arr_sel[i].options[arr_sel[i].selectedIndex].value + '|';
         }
         
         _caf.txt = ' ... simpan';
         ajax_feedback = _caf;
         
         emp_app_savePerson(urlencode(ret),function(_data) {
            var person_id = _gel('person_id');
            var data = recjsarray(_data);
            if(person_id.value=='') {
               person_id.value = data[0];
            }
         });
      }
      

      function delete_employee(d,e) {
         if(cur_btn&&cur_btn!=d) {
            cur_btn._showResult(false);
         }
         d._showResult=function() { };
         cur_btn = d;
         var gdiv = _gel('gdiv');
         gdiv.oldHTML = gdiv.innerHTML;
         gdiv.innerHTML = '';
         var dv = _dce('div');
         dv.className = 'msg_delete_warn';
         dv.innerHTML = 'Anda akan menghapus pegawai ini ( <span style=\"font-weight:bold;\">".$_SESSION["ehr_employee_person_nm"]."</span> )?<br/><br/>'
                      + '<input type=\"button\" value=\""._CANCEL."\" onclick=\"cancel_delete_employee();\"/>&nbsp;&nbsp;'
                      + '<input type=\"button\" value=\""._DELETE."\" onclick=\"confirm_delete_employee();\"/>';
         gdiv.appendChild(dv);
         delete_st = 1;
      }
      
      function cancel_delete_employee() {
         var gdiv = _gel('gdiv');
         gdiv.innerHTML = gdiv.oldHTML;
         cur_btn = null;
         delete_st = 0;
      }
      
      function confirm_delete_employee() {
         _caf.txt = ' ... menghapus dari database';
         ajax_feedback = _caf;
         emp_app_deleteEmployee(function(_data) {
            window.location = '".XOCP_SERVER_SUBDIR."/index.php?X_ehr=".$this->blockID."&".$slemp->prefix."ch=y';
         });
         delete_st = 0;
      }
      
      //////////////////////////////// ACCESS ////////////////////////////////////
      
      var qlogin = _gel('qlogin');
      function qlogin2ajax() {
         /////////////////////
         if(qlogin) {
            qlogin._align='left';
            qlogin._get_param=function() {
               var qval = this.value;
               qval = trim(qval);
               if(qval.length>0) {
                  return qval;
               } else {
                  return false;
               }
            };
            qlogin._onselect=function(id,nm) {
               if(id=='"._EHR_EMPLOYEE_TAKEN_ID."') {
                  alert('Login sudah terpakai');
                  return;
               }
               var frmaccess = _gel('frmaccess');
               qlogin._showResult(false);
               frmaccess.oldHTML = frmaccess.innerHTML;
               frmaccess.innerHTML = '';
               frmaccess.appendChild(progress_span(' ...proses'));
               emp_app_assignLogin(id,nm,function(_data) {
                  if(_data=='ID_TAKEN') {
                     alert('Login sudah terpakai.');
                     _gel('frmaccess').innerHTML = _gel('frmaccess').oldHTML;
                     qlogin = _gel('qlogin');
                     if(qlogin) {
                        qlogin2ajax();
                        setTimeout('qlogin.focus()',100);
                     }
                     return;
                  }
                  _gel('frmaccess').innerHTML = _data;
               });
            };
            qlogin._send_query = emp_app_getLogin;
            _make_ajax(qlogin);
            qlogin.gt = new ctimer('qlogin.gt.ontimerexpire()',250);
            qlogin.gt.ontimerexpire=function() {
               if(qlogin._get_param()==false) {
                  qlogin._showResult(false);
                  return;
               }
               qlogin._query();
            };
            qlogin._inp_key=function(e) {
               key = getkeyc(e);
               switch (key) { 
                  case 27: 
                     if(qlogin.getAttribute('type')=='text') {
                        qlogin.value = '';
                     }
                     if(qlogin._onescape) {
                        qlogin._onescape();
                     }
                     return false;
                     break;
                  case 13:
                     e.cancelBubble=true;
                     qlogin._get_result(qlogin._selectedIndex);
                     return false;
                     break;
                  case 9:
                     if(qlogin._ontab) {
                        qlogin._ontab();
                        e.cancelBubble=true;
                        return false;
                     }
                     break;
                  case 37: // kiri
                     if(qlogin._subres.style.display == 'none' || qlogin._data_count == 0) return;
                     if(qlogin._current_page == 0) {
                        qlogin._redraw_page(qlogin._max_page-1);
                     } else {
                        qlogin._redraw_page(qlogin._current_page-1);
                     }
                     e.cancelBubble=true;
                     if(qlogin._onchange) {
                        var idx = qlogin._selectedIndex;
                        qlogin._onchange(qlogin._resultId[idx],qlogin._results[idx]);
                     }
                     return false;
                     break;
                  case 39: // kanan
                     if(qlogin._subres.style.display == 'none' || qlogin._data_count == 0) return;
                     if(qlogin._current_page == (qlogin._max_page-1)) {
                        qlogin._redraw_page(0);
                     } else {
                        qlogin._redraw_page(qlogin._current_page+1);
                     }
                     e.cancelBubble=true;
                     if(qlogin._onchange) {
                        var idx = qlogin._selectedIndex;
                        qlogin._onchange(qlogin._resultId[idx],qlogin._results[idx]);
                     }
                     return false;
                     break;
                  case 38: // atas
                     if (qlogin._data_count > 0 && qlogin._subres.style.display == 'none') {
                        qlogin._showResult();
                        return;
                     }
                     if (qlogin._selectedIndex == (qlogin._current_page*qlogin._item_cnt)) { 
                        qlogin._selectedIndex = (qlogin._current_page*qlogin._item_cnt) + qlogin._item_page - 1; 
                     } else {
                        qlogin._selectedIndex--; 
                     }
                     qlogin._highlightsel();
                     if(qlogin._onchange) {
                        var idx = qlogin._selectedIndex;
                        qlogin._onchange(qlogin._resultId[idx],qlogin._results[idx]);
                     }
                     return false; 
                     break; 
                  case 40: // bawah
                     if (qlogin._data_count > 0 && qlogin._subres.style.display == 'none') { 
                        qlogin._showResult();
                        return;
                     } 
                     if (qlogin._selectedIndex == (qlogin._current_page*qlogin._item_cnt)+qlogin._item_page-1) { 
                        qlogin._selectedIndex = qlogin._current_page*qlogin._item_cnt; 
                     } else { 
                        qlogin._selectedIndex++; 
                     } 
                     qlogin._highlightsel();
                     if(qlogin._onchange) {
                        var idx = qlogin._selectedIndex;
                        qlogin._onchange(qlogin._resultId[idx],qlogin._results[idx]);
                     }
                     return false; 
                     break;
                  default:
                     qlogin.gt.start();
                     break;
               } 
               return true; 
            };
            
            qlogin._nav_key=qlogin._inp_key;
            qlogin.onkeypress=qlogin._inp_key;
         }
         
         ///////
      }
      qlogin2ajax();
      
      
      function unlink_login(d,e) {
         if(cur_btn&&cur_btn!=d&&cur_btn._showResult) {
            cur_btn._showResult(false);
         }
         cur_btn = d;
         var frmaccess = _gel('frmaccess');
         frmaccess.innerHTML = '';
         frmaccess.appendChild(progress_span(' ... unlink'));
         emp_app_unlinkLogin(function(_data) {
            var frmaccess = _gel('frmaccess');
            frmaccess.innerHTML = _data;
            qlogin = _gel('qlogin');
            qlogin2ajax();
            if(qlogin) {
               setTimeout('qlogin.focus()',500);
            }
         });
      }
      
      function reset_password(d,e) {
         if(cur_btn&&cur_btn!=d&&cur_btn._showResult) {
            cur_btn._showResult(false);
         }
         cur_btn = d;
         emp_app_resetPassword(function(_data) {
            _gel('pwd').innerHTML = _data;
         });
      }
      
      function add_group(d,e) {
         if(cur_btn&&cur_btn!=d&&cur_btn._showResult) {
            cur_btn._showResult(false);
         }
         cur_btn = d;
         if(!d._dropdownized) {
            d._get_param=function() {
               return 'ok';
            };
            d._align='left';
            d._send_query=emp_app_getGroupList;
            d._onselect=function(id,nm) {
               _caf.txt = ' ... tambah';
               ajax_feedback=_caf;
               d._showResult(false);
               d._reset();
               emp_app_addGroup(id,function(_data) {
                  if(_data=='FAIL') {
                     alert('Gagal tambah.');
                     return;
                  }
                  var data = recjsarray(_data);
                  var dv = _dce('div');
                  dv.setAttribute('id','dvgrp_'+data[0]);
                  dv.innerHTML = '<input type=\"checkbox\" id=\"grp_'+data[0]+'\" value=\"'+data[1]+'\"/> <label for=\"grp_'+data[0]+'\">'+data[1]+'</label>';
                  var grp = _gel('grp');
                  dv = grp.insertBefore(dv,grp.firstChild);
                  _gel('dvgrp_empty').style.display = 'none';
               });
            };
            _make_dropdown(d);
         }
         if(d._subres.style.display!='block') {
            d._query();
         } else {
            document.onkeypress = null;
            d._showResult(false);
         }
      }
      
      function delete_group(d,e) {
         if(cur_btn&&cur_btn!=d&&cur_btn._showResult) {
            cur_btn._showResult(false);
         }
         cur_btn = d;
         var grp = _gel('grp');
         var el = grp.getElementsByTagName('input');
         ret = '';
         for(var i=0;i<el.length;i++) {
            if(el[i].checked) {
               var pgid = el[i].id.substring(4);
               ret += pgid+'|';
            }
         }
         if(ret!='') {
            _caf.txt = ' ... hapus';
            ajax_feedback = _caf;
            emp_app_deleteGroup(urlencode(ret),function(_data) {
               var data = recjsarray(_data);
               for(var i=0;i<data.length;i++) {
                  _destroy(_gel('dvgrp_'+data[i]));
               }
               var els = _gel('grp').getElementsByTagName('input');
               if(els.length==0) {
                  _gel('dvgrp_empty').style.display = '';
               }
            });
         }
      }
      
      function invert_status(d,e) {
         if(cur_btn&&cur_btn!=d&&cur_btn._showResult) {
            cur_btn._showResult(false);
         }
         cur_btn = d;
         _caf.txt = ' ... set status';
         ajax_feedback = _caf;
         emp_app_invertStatus(function(_data) {
            var data = recjsarray(_data);
            _gel('stt').innerHTML = data[0];
            _gel('btn_stt').value = data[1];
         });
      }
      
      var vtab = GetCookie('emptab');
      switch(vtab) {
         case 'tab01':
            if(person_nm) {
               setTimeout('person_nm.focus()',500);
            }
         case 'tab04':
            if(qlogin) {
               setTimeout('qlogin.focus()',500);
            }
            break;
         default:
            break;
      }
      // --></script>";
      return $ret.$js;
   }
   
   
   function main() {
      if($_SESSION["ehr_org_id"] == 0) {
         $_SESSION["ehr_employee_id"] = 0;
         $_SESSION["ehr_employee_person_id"] = 0;
      }

      $_SESSION["html"]->addStyleSheet("<style type='text/css'>"
           . "\ntable.tab {margin:0px;background-color:#cccccc;color:black;font-weight:bold;}"
           . "\ntable.tab>tbody>tr>td.tab_n {padding:5px;border-left:1px solid black;cursor:pointer;color:#888888;}"
           . "\ntable.tab>tbody>tr>td.tab_n:hover {background-color:#ccffcc;color:#555555;}"
           . "\ntable.tab>tbody>tr>td.tab_s {padding:5px;border-left:1px solid black;cursor:pointer;background-color:#ffffff;}"
           . "\ntable.dt {margin:0px;background-color:#ffffff;border-left:1px solid black;}"
           . "\n.msg_delete_warn {background-color:#ffcccc;padding:10px;}"
           . "\ndiv.lst {padding:2px;border-bottom:1px solid #aaaaaa;}"
           . "\ndiv.hdr {padding:2px;border:1px solid #999999;background-color:#ccccff;}"
           . "\n</style>");

      $slorg = new _ehr_class_SelectOrganization($this->catch);
      $slorghtml = $slorg->show();
      if($_SESSION["ehr_org_id"] == 0) {
         return $slorghtml;
      }
      global $slemp;
      $slemp->setURLParam(XOCP_SERVER_SUBDIR."/index.php",array($this->catchvar=>$this->blockID));
      $slemphtml = $slemp->show();
      if($slemp->data["new_emp"]==1) {
         return $slorghtml."<br/>".$this->newForm();
      }
      if($_SESSION["ehr_employee_id"] == 0) {
         return $slorghtml."<br/>".$slemphtml;
      }
      
      
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $ret = $this->panel();
            break;
         default:
            $ret = $this->panel();
            break;
      }
      return $slorghtml.$slemphtml ."<br/>". $ret;
   }
}

} // EHR_EMPLOYEE_DEFINED
?>