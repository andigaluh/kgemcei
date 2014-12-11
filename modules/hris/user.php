<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/employee.php                               //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-11-06                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_USER_DEFINED') ) {
   define('HRIS_USER_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
include_once(XOCP_DOC_ROOT."/modules/hris/class/selectemployee.php");
include_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_user.php");

global $slemp;
$slemp = new _hris_class_SelectEmployee();
$slemp->btn_new = FALSE;

class _hris_User extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_USER_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_USER_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_User($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                            yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);          /* ini meneruskan $catch ke parent constructor */
      
   }
   
   function newForm() {
      $ret = "<table class='tab' border='0' cellpadding='0' cellspacing='0'><tbody><tr>"
           . "<td class='tab_s'>Data Personal Baru</td>"
           . "</tr></tbody></table>";
      $txt = "<div id='frmperson' style='padding:4px;'><form id='theform'>"
           . "<table class='xxfrm'>"
           . "<tbody>"
           . "<tr><td>Fullname [, Title]</td><td><input name='person_nm' id='person_nm' type='text' value='$person_nm' style='width:250px;'/></td></tr>"
           . "<tr><td>Employee ID</td><td><input name='employee_ext_id' id='employee_ext_id' type='text' value='$employee_ext_id' style='width:200px;'/></td></tr>"
           . "<tr><td colspan='2'><input type='button' value='"._SAVE."' onclick='save_person(this,event);'/>"
           . "&nbsp;&nbsp;<input type='button' value='"._CANCEL."' onclick='cancel_new();'/></td></tr>"
           . "</tbody>"
           . "</table>"
           . "</form></div>";
      $person_txt = "<div id='litab_0'>$txt</div>";
      $ret .= "<div id='gdiv' style='padding:5px;background-color:white;color:black;border-left:1px solid black;'>$person_txt</div>";

      $ret = "<ul class='ultab'>"
           . "<li id='litab_0' class='ultabsel_greyrev'><span class='xlnk'>Personnel Data</span></li>"
           . "<li id='litab_1'><span class='xlnkinactive'>Job Assigment</span></li>"
           . "<li id='litab_2'><span class='xlnkinactive'>User Profile</span></li>"
           . "</ul><div style='min-height:100px;border:1px solid #999999;border-top:0px;clear:both;padding:4px;'>"
           . $person_txt
           . "</div><br/><br/>";


      $ajax = new _hris_class_UserAjax("emp");
      $js = $ajax->getJs();
      return $ret.$js."<script type='text/javascript' language='javascript'><!--
      
      function cancel_new() {
         window.location = '".XOCP_SERVER_SUBDIR."/index.php';
      }
      
      function save_person(d,e) {
         _caf.txt = ' ... saving new employee';
         ajax_feedback = _caf;
         SetCookie('emptab','tab01');
         var ret = parseForm('theform');
         emp_app_savePerson(ret,'new',function(_data) {
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
         $sql = "INSERT INTO ".XOCP_PREFIX."hris_employee (status_cd,person_id)"
              . " VALUES ('new','$person_id')";
         $db->query($sql);
         $employee_id = $db->getInsertId();
         $_SESSION["hris_employee_id"] = $employee_id;
         $_SESSION["hris_employee_person_id"] = $person_id;
      } else {
         $employee_id = $_SESSION["hris_employee_id"];
         $person_id = $_SESSION["hris_employee_person_id"];
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
      
      $ret .= "<table class='tab' border='0' cellpadding='0' cellspacing='0'><tbody><tr>"
//           . "<td id='tab01' onclick='seltab(\"tab01\");' class='$tabsel01'>Personnel Data</td>"
//           . "<td id='tab03' onclick='seltab(\"tab03\");' class='$tabsel03'>Job Assigment</td>"
//           . "<td id='tab04' onclick='seltab(\"tab04\");' class='$tabsel04'>User Profile</td>"
           . "</tr></tbody></table>";
      
      //////////////////////////////////////// PERSONAL TAB ////////////////////////////////////
      
      $sql = "SELECT a.person_nm,a.ext_id,a.birth_dttm,a.birthplace,a.adm_gender_cd,a.addr_txt,"
           . "a.regional_cd,a.zip_cd,a.cell_phone,a.home_phone,a.fax,a.blood_type,a.marital_st,a.educlvl_id,a.status_cd,"
           . "b.employee_ext_id,b.entrance_dttm"
           . " FROM ".XOCP_PREFIX."persons a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(person_id)"
           . " WHERE a.person_id = '$person_id'";
      $result = $db->query($sql);
      list($person_nm,$ext_id,$birth_dttm,$birthplace,$adm_gender_cd,$addr_txt,
           $regional_cd,$zip_cd,$handphone,$phone,$fax,$blood_t,$marital_st,$education,
           $status_cd,$employee_ext_id,$entrance_dttm) = $db->fetchRow($result);
      
      if($birth_dttm==""||$birth_dttm == "0000-00-00 00:00:00") {
         $birth_dttm = "1900-01-01 00:00:00";
      }
      
      if($entrance_dttm==""||$entrance_dttm == "0000-00-00 00:00:00") {
         $entrance_dttm = "1900-01-01 00:00:00";
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
           . "<input type='button' value='"._DELETE."' onclick='delete_employee(this,event);'/>"
           . "</td></tr></table>"
           . "</div>";
      $txt = "<table border='0'><tbody><tr><td style='padding:4px;'>"
           . "<div id='frmperson'><form id='theform'>"
           . "<input type='hidden' name='person_id' id='person_id' value='$person_id'/>"
           . "<table class='xxfrm'>"
           . "<tbody>"
           . "<tr><td>Fullname [, Title]</td><td><input name='person_nm' id='person_nm' type='text' value='$person_nm' style='width:250px;'/></td></tr>"
           . "<tr><td>Employee ID</td><td><input name='employee_ext_id' id='employee_ext_id' type='text' value='$employee_ext_id' style='width:200px;'/></td></tr>"
           . "<tr><td>Entrance Date</td><td>"
              . "<span class='xlnk' onclick='showCalEntr(this,event);' id='entrance_dttm_txt'>".sql2ind($entrance_dttm)."</span>"
              . "<input type='hidden' name='entrance_dttm' id='entrance_dttm' value='$entrance_dttm'/>"
           . "</td></tr>"
           . "<tr><td>KTP/SIM/Passport</td><td><input name='ext_id' id='ext_id' type='text' value='$ext_id' style='width:200px;'/></td></tr>"
           . "<tr><td>Date Of Birth</td><td>"
              . "<span class='xlnk' onclick='showCal(this,event);' id='birth_dttm_txt'>".sql2ind($birth_dttm)."</span>"
              . "<input type='hidden' name='birth_dttm' id='birth_dttm' value='$birth_dttm'/>"
           . "</td></tr>"
           . "<tr><td>Place Of Birth</td><td><input name='birthplace' id='birthplace' type='text' value='$birthplace' style='width:200px;'/></td></tr>"
           . "<tr><td>Sex</td><td>"
              . "<input type='radio' name='adm_gender_cd' id='gender_m' value='m' ".$jk["m"]."/> <label for='gender_m' class='xlnk'>Laki-laki</label>&nbsp;&nbsp;"
              . "<input type='radio' name='adm_gender_cd' id='gender_f' value='f' ".$jk["f"]."/> <label for='gender_f' class='xlnk'>Perempuan</label>"
           . "</td></tr>"
           . "<tr><td>Street Address</td><td><input name='addr_txt' id='addr_txt' type='text' value='$addr_txt' style='width:300px;'/></td></tr>"
           . "<tr><td>Region</td><td>"
              . "<span id='region_txt' class='xlnk' onclick='_getRegion(this,selregion,\"$regional_cd\");'>$region_txt</span>"
              . "<input type='hidden' name='regional_cd' id='regional_cd' value='$regional_cd'/>"
              . "<div id='_regionDiv' style='display:none;padding:2px;border:1px solid #888888;margin:2px;background-color:#ddddff;'></div>"
           . "</td></tr>"
           . "<tr><td>Postal Code</td><td><input name='zip_cd' id='zip_cd' type='text' value='$zip_cd' size='5' maxlength='5'/></td></tr>"
           . "<tr><td>Telepone</td><td><input name='telephone' id='telephone' type='text' value='$phone' style='width:200px;'/></td></tr>"
           . "<tr><td>Faximile</td><td><input name='fax' id='fax' type='text' value='$fax' style='width:200px;'/></td></tr>"
           . "<tr><td>Cellphone</td><td><input name='hp' id='hp' type='text' value='$handphone' style='width:200px;'/></td></tr>"
           . "<tr><td>E-mail</td><td><input name='email' id='email' type='text' value='$email' style='width:250px;'/></td></tr>"
           . "<tr><td>Blood Type</td><td>"
              . "<input type='radio' value='A' id='blood_a' name='blood_t' $blood_ck[A]/><label for='blood_a' class='xlnk'>A</label>&nbsp;"
              . "<input type='radio' value='B' id='blood_b' name='blood_t' $blood_ck[B]/><label for='blood_b' class='xlnk'>B</label>&nbsp;"
              . "<input type='radio' value='0' id='blood_0' name='blood_t' $blood_ck[0]/><label for='blood_0' class='xlnk'>0</label>&nbsp;"
              . "<input type='radio' value='AB' id='blood_ab' name='blood_t' $blood_ck[AB]/><label for='blood_ab' class='xlnk'>AB</label>"
           . "</td></tr>"
           . "<tr><td>Marital Status</td><td>"
              . "<select name='marital_st' id='marital_st'>$mar_opt</select>"
           . "</td></tr>"
           . "<tr><td>Last Education</td><td>"
              . "<select name='education' id='education'>"
                 . "<option value='1' $educ_sel[1]>Tidak Tamat SD</option>"
                 . "<option value='2' $educ_sel[2]>Tamat SD</option>"
                 . "<option value='3' $educ_sel[3]>Tamat SMP</option>"
                 . "<option value='4' $educ_sel[4]>Tamat SMU</option>"
                 . "<option value='5' $educ_sel[5]>Diploma</option>"
                 . "<option value='6' $educ_sel[6]>Sarjana Muda</option>"
                 . "<option value='7' $educ_sel[7]>Sarjana</option>"
                 . "<option value='8' $educ_sel[8]>Pasca Sarjana</option>"
                 . "<option value='9' $educ_sel[9]>Doktor</option>"
              . "</select>"
           . "</td></tr>"
           . "<tr><td colspan='2'><input type='button' value='"._SAVE."' onclick='save_person(this,event);'/></td></tr>"
           . "</tbody>"
           . "</table></form>"
           . "</div>"
           
           . "</td><td valign='top' style='padding:4px;'>"
           . "<div id='img_thumb_div' style='width:150px;height:200px;background-color:#eeeeee;border:1px solid #cccccc;'>"
           
           . "<img src='".XOCP_SERVER_SUBDIR."/modules/hris/thumb.php?pid=$person_id'/>"
           . "</div>"
           . "<div style='padding:4px;padding-left:0px;'>"
           . "<input type='button' value='Change Picture' onclick='chgpicture();'/>"
           . "</div>"
           . "</td></tr></tbody></table>";
      require_once(XOCP_DOC_ROOT."/class/ajax/ajax_map.php");
      $ajaxmap = new _class_MapAjax();
      //$txt .= $ajaxmap->getJs() 
      //     . "<script src='".XOCP_SERVER_SUBDIR."/include/map.js' type='text/javascript'></script>";
      $person_txt = "<div id='tab_person' style='$disp01'>$txt</div>";
      $dv_person = "<div id='dvtab_0' style=''>$txt</div>";
      
      //////////////////////////////////////// ROLE TAB ////////////////////////////////////////
      
      $sql = "SELECT a.job_id,TRIM(b.job_nm) as c_nm,b.job_cd"
           . " FROM ".XOCP_PREFIX."employee_job a"
           . " LEFT JOIN ".XOCP_PREFIX."jobs b ON b.job_id = a.job_id"
           . " WHERE a.employee_id = '$employee_id'"
           . " GROUP BY a.job_id"
           . " ORDER BY c_nm";
      $result = $db->query($sql);
      $txt = "<div style='padding:4px;'><div id='hdrjob' class='hdr'>"
           . "<table border='0' cellpadding='0' cellspacing='0' width='100%'>"
           . "<td style='text-align:right;'>"
           . "Find Job : <input type='text' id='qjob'/>"
//           . "<input type='button' value='"._IMPORT."' onclick='import_job(this,event);'/>&nbsp;"
//           . "<input type='button' value='"._ADD."' onclick='add_job(this,event);'/>&nbsp;&nbsp;"
           //. "<input type='button' value='"._DELETE."' onclick='delete_employee(this,event);'/>"
           . "</td></tr></table>"
           . "</div>";
      if($db->getRowsNum($result)>0) {
         while(list($job_id,$job_nm,$job_cd)=$db->fetchRow($result)) {
            $txt .= "<div id='c_${job_id}' class='lst'><span>$job_cd</span> "
                  . "<span onclick='edit_job(\"$job_id\",this,event);' class='xlnk'>$job_nm</span></div>\n";
         }
      }
      $txt .= "</div>";
      $job_txt = "<div id='tab_job' style='$disp03'>$txt</div>";
      $dv_job = "<div id='dvtab_1' style='display:none;'>$txt</div>";
      
      //////////////////////////////////////// ACCESS TAB ////////////////////////////////////
      
      $txt = "<div class='hdr'>"
           . "<table border='0' cellpadding='0' cellspacing='0' width='100%'><tr><td style='text-align:right;'>"
           . "<input type='button' value='"._DELETE."' onclick='delete_employee(this,event);'/>"
           . "</td></tr></table>"
           . "</div>";
      $txt = "";
      
      $txt .= "<div id='frmaccess' style='padding:4px;'>"
           . _hris_class_UserAjax::editLogin($person_id)
           . "</div>";
      
      $access_txt = "<div id='tab_access' style='$disp04'>$txt</div>";
      $dv_user = "<div id='dvtab_0' style=''>$txt</div>";
      
      //////////////////////////////////////////////////////////////////////////////////////////

      $tabno = 1;
      $ret = "<ul class='ultab'>"
           . "<li id='litab_0' class='ultabsel_greyrev'><span onclick='pnltab(\"0\",this,event);' class='xlnk'>Personnel Data</span></li>"
           . "<li id='litab_1'><span onclick='pnltab(\"1\",this,event);' class='xlnk'>Job Assigment</span></li>"
           . "<li id='litab_2'><span onclick='pnltab(\"2\",this,event);' class='xlnk'>User Profile</span></li>"
           . "</ul><div style='min-height:100px;border:1px solid #999999;clear:both;padding:4px;'>"
           . $dv_person . $dv_job . $dv_user
           . "</div><br/><br/>";
      
      $ret = "<ul class='ultab'>"
           . "<li id='litab_0' class='ultabsel_greyrev'><span onclick='pnltab(\"0\",this,event);' class='xlnk'>User Profile</span></li>"
           . "</ul><div style='min-height:100px;border:1px solid #999999;clear:both;padding:4px;'>"
           . $dv_user
           . "</div><br/><br/>";
      

      
      
      // $ret .= "<div id='gdiv' style='padding:5px;background-color:white;color:black;border-left:1px solid black;'>$person_txt\n$job_txt\n$access_txt</div>";
      $ajax = new _hris_class_UserAjax("emp");
      $js = $ajax->getJs()."<script src='".XOCP_SERVER_SUBDIR."/include/calendar.js' type='text/javascript'></script>
      <script type='text/javascript'><!--
      
      function email_password(d,e) {
         ajax_feedback = _caf;
         emp_app_emailPassword(function(_data) {
            
         });
      }
      
      document.load_thumbnail = function(person_id,uniqid) {
         var dv = _gel('img_thumb_div');
         dv.innerHTML = '';
         _destroy(dv.img);
         dv.img = dv.appendChild(_dce('img'));
         dv.img.setAttribute('id','pic_'+person_id);
         dv.img.src = '".XOCP_SERVER_SUBDIR."/modules/hris/thumb.php?pid='+person_id+'&f='+uniqid;
         _destroy(pdl);
         _destroy(pdl.bg);
      };
      
      var pdl = null;
      function chgpicture() {
         pdl = _dce('div');
         pdl.setAttribute('id','pdl');
         pdl.bg = _dce('div');
         pdl.bg.setAttribute('style','height:150%;width:150%;position:fixed;top:0px;left:-10px;background-color:#000000;opacity:0.5;z-index:10000');
         pdl.bg = document.body.appendChild(pdl.bg);
         pdl.setAttribute('style','text-align:center;padding-top:15px;padding-bottom:15px;width:400px;position:fixed;top:50%;left:50%;background-color:white;border:1px solid #555555;margin-left:-200px;margin-top:-100px;opacity:1;z-index:10001;');
         pdl.innerHTML = '<div style=\"background-color:#5666b3;padding:4px;margin:5px;color:#ffffff;width:366px;margin-left:15px;\">Upload Picture</div>'
                       + '<iframe src=\"".XOCP_SERVER_SUBDIR."/modules/hris/uploadfoto.php\" style=\"width:370px;border:0px solid black;overflow:visible;\"></iframe>';
         pdl = document.body.appendChild(pdl);
         pdl.cancelUpload = function() {
            _destroy(pdl);
            _destroy(pdl.bg);
         };
      }
      
      var obdtstart = null;
      function setstartjob(dt) {
         _gel('startjob_txt').innerHTML = obdtstart.obj.toString(obdtstart.obj.getResult(),'datetime');
         _gel('hstartjob').value = dt;
      }
      
      function editstartjob(d,e) {
         if(!d.cal) {
            d.cal = new calendarClass('obdtstart',setstartjob);
            d.cal.div.style.position = 'absolute';
            d.cal.div.style.left = '0px';
            d.cal.div.style.top = '0px';
            d.cal.div.style.visibility = 'hidden';
            obdtstart=document.body.appendChild(d.cal.div);
            obdtstart.obj.setDTTM($('hstartjob').value);
            d.obdt = obdtstart;
         } else {
            obdtstart = d.obdt;
         }
         if(obdtstart.style.visibility=='hidden') {
            obdtstart.style.left = (oX(d))+'px';
            obdtstart.style.top = (oY(d)+d.offsetHeight)+'px';
            obdtstart.style.visibility='visible';
         } else {
            obdtstart.obj.hide();
            _destroy(d.cal);
            d.cal = null;
         }
      }
      
      var obdtstop = null;
      function setstopjob(dt) {
         _gel('stopjob_txt').innerHTML = obdtstop.obj.toString(obdtstop.obj.getResult(),'datetime');
         _gel('hstopjob').value = dt;
      }
      
      function editstopjob(d,e) {
         if(!d.cal) {
            d.cal = new calendarClass('obdtstop',setstopjob);
            d.cal.div.style.position = 'absolute';
            d.cal.div.style.left = '0px';
            d.cal.div.style.top = '0px';
            d.cal.div.style.visibility = 'hidden';
            obdtstop=document.body.appendChild(d.cal.div);
            obdtstop.obj.setDTTM($('hstopjob').value);
            d.obdt = obdtstop;
         } else {
            obdtstop = d.obdt;
         }
         if(obdtstop.style.visibility=='hidden') {
            obdtstop.style.left = (oX(d))+'px';
            obdtstop.style.top = (oY(d)+d.offsetHeight)+'px';
            obdtstop.style.visibility='visible';
         } else {
            obdtstop.obj.hide();
            _destroy(d.cal);
            d.cal = null;
         }
      }
      
      function pnltab(tabno,d,e) {
         for(var i=0;i<${tabno};i++) {
            if(tabno==i) {
               $('litab_'+i).className = 'ultabsel_greyrev';
            } else {
               $('litab_'+i).className = '';
            }
            $('dvtab_'+i).style.display = 'none';
         }
         
         var dv = $('dvtab_'+tabno);
         dv.style.display = '';
         /*
         try {
            eval(tabid+'_inittab()');
         } catch(e) {
         }
         */
      }

      ajax_feedback = null;
      var qjob = _gel('qjob');
      if(qjob) {
         qjob._get_param=function() {
            var qval = this.value;
            qval = trim(qval);
            if(qval.length < 2) {
               return '';
            }
            return qval;
         };
         qjob._onselect=function(resId) {
            _caf.txt = ' ... assign';
            ajax_feedback=_caf;
            qjob._reset();
            qjob._showResult(false);
            emp_app_confirmAddJob(resId,function(_data) {
               var data = recjsarray(_data);
               if(cur_edit) {
                  _destroy(cur_edit.edit);
               }
               cur_edit = qjob;
               cur_edit.job_id = resId;
               dv = _dce('div');
               dv.setAttribute('id','c_'+data[0]);
               dv.className = 'lst';
               var hdr = _gel('hdrjob');
               dv = hdr.parentNode.insertBefore(dv,hdr.nextSibling);
               cur_edit.dv = dv;
               cur_edit.edit = dv.appendChild(_dce('div'));
               cur_edit.edit.setAttribute('id','jobeditor');
               cur_edit.edit.innerHTML = data[1];
            });
         };
         qjob._send_query = emp_app_searchJob;
         _make_ajax(qjob);
         qjob.focus();
      }
      function confirm_add_job(job_id,d,e) {
         emp_app_addJob(job_id,function(_data) {
            if(_data=='FAIL') {
               return;
            }
            cur_edit.dv.innerHTML = _data;
            cur_edit.edit = $('jobeditor');
         });
      }
      
      function cancel_add_job(job_id,d,e) {
         _destroy($('c_'+job_id));
         cur_edit.job_id = null;
         cur_edit = null;
      }
      
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
               _gel('tab_job').style.display = 'none';
               _gel('tab_person').style.display = '';
               if(person_nm) {
                  setTimeout('person_nm.focus()',500);
               }
               break;
            case 'tab02':
               _gel('tab_access').style.display = 'none';
               _gel('tab_job').style.display = 'none';
               _gel('tab_person').style.display = 'none';
               break;
            case 'tab03':
               _gel('tab_access').style.display = 'none';
               _gel('tab_person').style.display = 'none';
               _gel('tab_job').style.display = '';
               break;
            case 'tab04':
               _gel('tab_job').style.display = 'none';
               _gel('tab_person').style.display = 'none';
               _gel('tab_access').style.display = '';
               if(qlogin) {
                  setTimeout('qlogin.focus()',500);
               }
               break;
            default:
               _gel('tab_access').style.display = 'none';
               _gel('tab_person').style.display = '';
               _gel('tab_job').style.display = 'none';
               if(person_nm) {
                  setTimeout('person_nm.focus()',500);
               }
               break;
         }
      }
      
      
      /////////////////////////////// ROLE ////////////////////////////
      
      var cur_edit = null;
      function edit_job(job_id,d,e) {
         obdtstart = null;
         obdtstop = null;
         _destroy($('obdtstart'));
         _destroy($('obdtstop'));
         if(cur_edit&&cur_edit.job_id==job_id) {
            _destroy(cur_edit.edit);
            cur_edit.job_id = null;
            cur_edit = null;
            return;
         } else if(cur_edit) {
            _destroy(cur_edit.edit);
         }
         cur_edit = d;
         cur_edit.job_id = job_id;
         cur_edit.dv = _gel('c_'+job_id);
         cur_edit.edit = cur_edit.dv.appendChild(_dce('div'));
         cur_edit.edit.setAttribute('id','jobeditor');
         cur_edit.edit.appendChild(progress_span());
         emp_app_editJob(job_id,function(_data) {
            cur_edit.edit.innerHTML = _data;
         });
      }
      
      function save_job(job_id,d,e) {
         var ret = parseForm('frmjob');
         cur_edit.edit.innerHTML = '';
         cur_edit.edit.appendChild(progress_span(' ... saving'));
         obdtstart = null;
         obdtstop = null;
         _destroy($('obdtstart'));
         _destroy($('obdtstop'));
         emp_app_saveJob(job_id,ret,function(_data) {
            _destroy(cur_edit.edit);
            cur_edit = null;
         });
      }
      
      function delete_job(job_id,d,e) {
         cur_edit.edit.oldHTML = cur_edit.edit.innerHTML;
         cur_edit.edit.innerHTML = '';
         var dv = _dce('div');
         dv.setAttribute('style','margin-top:5px;border:1px solid #aaaaaa;');
         dv.className = 'msg_delete_warn';
         dv.innerHTML = 'Are you sure you want to delete this job assignment?<br/><br/>'
                      + '<input type=\"button\" value=\""._CANCEL."\" onclick=\"cancel_delete_job();\"/>&nbsp;&nbsp;'
                      + '<input type=\"button\" value=\""._DELETE."\" onclick=\"confirm_delete_job(\''+job_id+'\');\"/>';
         cur_edit.edit.appendChild(dv);
      }
      
      function cancel_delete_job() {
         cur_edit.edit.innerHTML = cur_edit.edit.oldHTML;
      }
      
      function confirm_delete_job(job_id) {
         _destroy(_gel('c_'+job_id));
         cur_edit.job_id = null;
         cur_edit = null;
         emp_app_deleteJob(job_id,null);
      }
      
      var cur_btn = null;
      function add_job(d,e) {
         if(cur_btn&&cur_btn!=d) {
            cur_btn._showResult(false);
         }
         cur_btn = d;
         if(!d._dropdownized) {
            d._get_param=function() {
               return 'ok';
            };
            d._align='right';
            d._send_query=emp_app_getJobList;
            d._onselect=function(id,nm) {
               _caf.txt = ' ... tambah';
               ajax_feedback=_caf;
               emp_app_addJob(id,function(_data) {
                  if(_data=='FAIL') {
                     alert('Gagal tambah.');
                     return;
                  }
                  var data = recjsarray(_data);
                  var dv = _dce('div');
                  dv.setAttribute('id','c_'+data[0]);
                  dv.className = 'lst';
                  dv.innerHTML = '<span style=\"color:blue;\">'+data[0]+'</span> '
                               + '<span style=\"color:blue;\" onclick=\"edit_job(\''+data[0]+'\',this,event);\" class=\"xlnk\">'+data[1]+'</span>';
                  var hdr = _gel('hdrjob');
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
      
      function import_job(d,e) {
         if(cur_btn&&cur_btn!=d) {
            cur_btn._showResult(false);
         }
         cur_btn = d;
         if(!d._dropdownized) {
            d._get_param=function() {
               return 'ok';
            };
            d._align='right';
            d._send_query=emp_app_getImportJobList;
            d._onselect=function(id,nm) {
               _caf.txt = ' ... import';
               ajax_feedback = _caf;
               emp_app_importJob(id,function(_data) {
                  if(_data=='FAIL') {
                     alert('Gagal Import.');
                     return;
                  }
                  var data = recjsarray(_data);
                  var hdr = _gel('hdrjob');
                  for(var i=0;i<data.length;i++) {
                     var job_id = data[i][0];
                     var job_nm = data[i][1];
                     var dv = null;
                     if(_gel('c_'+job_id)) {
                        dv = _gel('c_'+job_id);
                     } else {
                        dv = _dce('div');
                        dv.setAttribute('id','c_'+job_id);
                        dv.className = 'lst';
                        dv = hdr.parentNode.insertBefore(dv,hdr.nextSibling);
                     }
                     dv.innerHTML = '<span style=\"color:blue;\">'+job_id+'</span> '
                                  + '<span style=\"color:blue;\" onclick=\"edit_job(\''+job_id+'\',this,event);\" class=\"xlnk\">'+job_nm+'</span>';
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
      
      var obdtentr = null;
      function setDTEntr(dt) {
         if(obdtentr.obj.sync==true) {
            var dtstr = obdtentr.obj.getResult().split(' ');
            _gel('entrance_dttm_txt').innerHTML = obdtentr.obj.toString(dtstr[0]) + ' \/ sync';
         } else {
            _gel('entrance_dttm_txt').innerHTML = obdtentr.obj.toString(obdtentr.obj.getResult(),'datetime');
         }
         _gel('entrance_dttm').value = dt;
         var dyx = XOCP_CURRENT_DATE.split('-');
      }
      var calentr = new calendarClass('obdtentr',setDTEntr);
      calentr.div.style.position = 'absolute';
      calentr.div.style.left = '0px';
      calentr.div.style.top = '0px';
      calentr.div.style.visibility = 'hidden';
      obdtentr=document.body.appendChild(calentr.div);
      obdtentr.obj.setDTTM('$entrance_dttm');
      
      function showCalEntr(d,e) {
         if(obdtentr.style.visibility=='hidden') {
            obdtentr.style.left = (oX(d))+'px';
            obdtentr.style.top = (oY(d)+d.offsetHeight)+'px';
            obdtentr.style.visibility='visible';
         } else {
            obdtentr.obj.hide();
         }
      }
      
      function save_person(d,e) {
         _caf.txt = ' ... saving';
         ajax_feedback = _caf;
         var ret = parseForm('theform');
         emp_app_savePerson(ret,function(_data) {
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
         dv.innerHTML = 'Anda akan menghapus pegawai ini ( <span style=\"font-weight:bold;\">".$_SESSION["hris_employee_person_nm"]."</span> )?<br/><br/>'
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
            window.location = '".XOCP_SERVER_SUBDIR."/index.php?X_hris=".$this->blockID."&".$slemp->prefix."ch=y';
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
               if(id=='"._HRIS_USER_TAKEN_ID."') {
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
      
      function email_password(d,e) {
         var curpwd = $('pwd').innerHTML;
         if(curpwd=='-') {
            alert('Password already changed. Please reset password before e-mail it.');
            return;
         }
         ajax_feedback = _caf;
         emp_app_emailPassword(function(_data) {
            
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
      
      function add_role(d,e) {
         if(cur_btn&&cur_btn!=d&&cur_btn._showResult) {
            cur_btn._showResult(false);
         }
         cur_btn = d;
         if(!d._dropdownized) {
            d._get_param=function() {
               return 'ok';
            };
            d._align='left';
            d._send_query=emp_app_getRoleList;
            d._onselect=function(id,nm) {
               _caf.txt = ' ... tambah';
               ajax_feedback=_caf;
               d._showResult(false);
               d._reset();
               emp_app_addRole(id,function(_data) {
                  if(_data=='FAIL') {
                     alert('Gagal tambah.');
                     return;
                  }
                  var data = recjsarray(_data);
                  var dv = _dce('div');
                  dv.setAttribute('id','dvrole_'+data[0]);
                  dv.innerHTML = '<input type=\"checkbox\" id=\"rl_'+data[0]+'\" value=\"'+data[1]+'\"/> <label for=\"rl_'+data[0]+'\">'+data[1]+'</label>';
                  var rl = _gel('rl');
                  dv = rl.insertBefore(dv,rl.firstChild);
                  _gel('dvrole_empty').style.display = 'none';
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
      
      function delete_role(d,e) {
         if(cur_btn&&cur_btn!=d&&cur_btn._showResult) {
            cur_btn._showResult(false);
         }
         cur_btn = d;
         var rl = _gel('rl');
         var el = rl.getElementsByTagName('input');
         ret = '';
         for(var i=0;i<el.length;i++) {
            if(el[i].checked) {
               var rlid = el[i].id.substring(3);
               ret += rlid+'|';
            }
         }
         if(ret!='') {
            _caf.txt = ' ... hapus';
            ajax_feedback = _caf;
            emp_app_deleteRole(urlencode(ret),function(_data) {
               var data = recjsarray(_data);
               for(var i=0;i<data.length;i++) {
                  _destroy(_gel('dvrole_'+data[i]));
               }
               var els = _gel('rl').getElementsByTagName('input');
               if(els.length==0) {
                  _gel('dvrole_empty').style.display = '';
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
      if(qlogin) {
         setTimeout('qlogin.focus()',500);
      }
      // --></script>";
      return $ret.$js;
   }
   
   
   function main() {
      $_SESSION["html"]->addStyleSheet("<style type='text/css'>"
           . "\ntable.tab {margin:0px;background-color:#cccccc;color:black;font-weight:bold;}"
           . "\ntable.tab>tbody>tr>td.tab_n {padding:5px;border-left:1px solid black;cursor:pointer;color:#888888;}"
           . "\ntable.tab>tbody>tr>td.tab_n:hover {background-color:#ccffcc;color:#555555;}"
           . "\ntable.tab>tbody>tr>td.tab_s {padding:5px;border-left:1px solid black;cursor:pointer;background-color:#ffffff;}"
           . "\ntable.dt {margin:0px;background-color:#ffffff;border-left:0px solid black;}"
           . "\n.msg_delete_warn {background-color:#ffcccc;padding:10px;}"
           . "\ndiv.lst {padding:2px;border-bottom:1px solid #aaaaaa;}"
           . "\ndiv.hdr {padding:2px;border:1px solid #999999;background-color:#eeeeee;}"
           . "\n</style>");

      global $slemp;
      $slemp->setURLParam(XOCP_SERVER_SUBDIR."/index.php",array($this->catchvar=>$this->blockID));
      $slemphtml = $slemp->show();
      if($slemp->data["new_emp"]==1) {
         return $this->newForm();
      }
      if($_SESSION["hris_employee_id"] == 0) {
         return $slemphtml;
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
      return $slemphtml . $ret;
   }
}

} // HRIS_USER_DEFINED
?>