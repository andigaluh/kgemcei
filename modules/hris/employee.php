<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/employee.php                               //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-11-06                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_EMPLOYEE_DEFINED') ) {
   define('HRIS_EMPLOYEE_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
include_once(XOCP_DOC_ROOT."/modules/hris/class/selectemployee.php");
include_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_employee.php");
include_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_asmsession.php");

global $slemp;
$slemp = new _hris_class_SelectEmployee();
$slemp->btn_new = TRUE;

class _hris_Employee extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_EMPLOYEE_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_EMPLOYEE_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_Employee($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
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
           . "<tr><td>Fullname [, Title]</td><td><input name='person_nm' id='person_nm' type='text' value='".htmlentities($person_nm,ENT_QUOTES)."' style='width:250px;'/></td></tr>"
           . "<tr><td>Employee ID</td><td><input name='employee_ext_id' id='employee_ext_id' type='text' value='$employee_ext_id' style='width:200px;'/></td></tr>"
           . "<tr><td colspan='2'><input type='button' value='"._SAVE."' onclick='save_person(this,event);'/>"
           . "&nbsp;&nbsp;<input type='button' value='"._CANCEL."' onclick='cancel_new();'/></td></tr>"
           . "</tbody>"
           . "</table>"
           . "</form></div>";
      $person_txt = $txt;
      
      $ret = "<ul class='ultab'>"
           . "<li id='litab_0' class='ultabsel_greyrev'><span class='xlnk'>Personnel Data</span></li>"
           . "<li id='litab_1' style='visibility:hidden;'><span class='xlnk'>Personnel</span></li>"
           . "</ul><div style='min-height:100px;border:1px solid #999999;clear:both;padding:4px;'>"
           . "<div id='litab_0'>$person_txt</div>"
           . "</div><br/><br/>";

      $ajax = new _hris_class_EmployeeAjax("emp");
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
         $sql = "INSERT INTO ".XOCP_PREFIX."persons (person_nm,status_cd) VALUES ('nama lengkap','normal')";
         $result = $db->query($sql);
         $person_id = $db->getInsertId();
         $sql = "INSERT INTO ".XOCP_PREFIX."employee (status_cd,person_id)"
              . " VALUES ('normal','$person_id')";
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
      //////////////////////////////////////// PERSONAL TAB ////////////////////////////////////
      
      $sql = "SELECT a.person_nm,a.ext_id,a.birth_dttm,a.birthplace,a.adm_gender_cd,a.addr_txt,"
           . "a.regional_cd,a.zip_cd,a.country,a.cell_phone,a.home_phone,a.fax,a.email,a.blood_type,a.blood_rhesus,a.marital_st,a.educlvl_id,a.status_cd,"
           . "b.employee_ext_id,b.entrance_dttm,b.exit_dttm,IF(b.exit_dttm='0000-00-00 00:00:00',(TO_DAYS(NOW())-TO_DAYS(b.entrance_dttm)),(TO_DAYS(b.exit_dttm)-TO_DAYS(b.entrance_dttm))) as ln,b.status_cd,"
           
           . "a.tmp_addr_txt,a.tmp_regional_cd,a.tmp_zip_cd,a.tmp_country,a.tmp_phone,"
           
           . "a.emergency_person_nm,a.emergency_occupation,a.emergency_relation,"
           . "a.emergency_addr_txt,a.emergency_regional_cd,a.emergency_zip_cd,a.emergency_country,a.emergency_phone,"
           
           . "(TO_DAYS(now())-TO_DAYS(a.birth_dttm)) as age_days, a.smtp_location,"
           . "b.alias_nm,b.attendance_id"
           
           . " FROM ".XOCP_PREFIX."persons a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(person_id)"
           . " WHERE a.person_id = '$person_id'";
      $result = $db->query($sql);
      list($person_nm,$ext_id,$birth_dttm,$birthplace,$adm_gender_cd,$addr_txt,
           $regional_cd,$zip_cd,$country,$cell_phone,$home_phone,$fax,$email,$blood_type,$blood_rhesus,$marital_st,$education,
           $status_cdx,$employee_ext_id,$entrance_dttm,$exit_dttm,$company_length,$status_cd,
           $tmp_addr_txt,$tmp_regional_cd,$tmp_zip_cd,$tmp_country,$tmp_phone,
           $emergency_person_nm,$emergency_occupation,$emergency_relation,
           $emergency_addr_txt,$emergency_regional_cd,$emergency_zip_cd,$emergency_country,$emergency_phone,
           $days_age,$smtp_location,$alias_nm,$attendance_id) = $db->fetchRow($result);
      
      $rh["negative"] = "Negative";
      $rh["positive"] = "Positive";
      
      $opt_rhesus = "<option value=''></option>";
      foreach($rh as $k=>$v) {
         $opt_rhesus .= "<option value='$k' ".($blood_rhesus==$k?"selected='1'":"").">$v</option>";
      }
      
      $xopt["spouse"] = "Spouse";
      $xopt["child"] = "Child";
      $xopt["parent"] = "Parent";
      $xopt["other_family"] = "Other Family";
      $xopt["sister"] = "Sister";
      $xopt["brother"] = "Brother";
      $xopt["step_parent"] = "Step Parent";
      $xopt["step_child"] = "Step Child";

      $opt_emergency_rel = "<option value=''></option>";
      foreach($xopt as $k=>$v) {
         $opt_emergency_rel .= "<option value='$k' ".($emergency_relation==$k?"selected='1'":"").">$v</option>";
      }     
      
      if($birth_dttm==""||$birth_dttm == "0000-00-00 00:00:00") {
         $birth_dttm = "1900-01-01 00:00:00";
         $days_age = 0;
      }
      
      if($entrance_dttm==""||$entrance_dttm == "0000-00-00 00:00:00") {
         $entrance_dttm = "1900-01-01 00:00:00";
      }
      
      if($exit_dttm==""||$exit_dttm == "0000-00-00 00:00:00"||$exit_dttm<=$entrance_dttm) {
         $exit_dttm = "";
      }
      
      $jk["m"] = $jk["f"] = "";
      $jk[$adm_gender_cd] = " checked='1'";
      
      require_once(XOCP_DOC_ROOT."/modules/system/class/gis_region.php");
      if($regional_cd!="") {
         $region_txt = _system_class_GISRegion::getRegionText($regional_cd);
      } else {
         $region_txt = _EMPTY;
      }
      if($tmp_regional_cd!="") {
         $tmp_region_txt = _system_class_GISRegion::getRegionText($tmp_regional_cd);
      } else {
         $tmp_region_txt = _EMPTY;
      }
      if($emergency_regional_cd!="") {
         $emergency_region_txt = _system_class_GISRegion::getRegionText($emergency_regional_cd);
      } else {
         $emergency_region_txt = _EMPTY;
      }
      $blood_ck["A"] = $blood_ck["B"] = $blood_ck["0"] = $blood_ck["AB"] = "";
      $marital_sel["mar"] = $marital_sel["sng"] = $marital_sel["dvc"] = $marital_sel["wid"] = "";
      $blood_ck[$blood_type] = "checked='checked'";
      $marital_sel[$marital_st] = "selected='selected'";
      global $arr_marital;
      $mar_opt = "";
      foreach($arr_marital as $k=>$v) {
         $mar_opt .= "<option value='$k' ".$marital_sel[$k].">$v</option>";
      }
      if($education>0) {
         $educ_sel[$education] = "selected='selected'";
      } else {
         $education = 4;
      }
      $txt = "<div id='gdiv'><table border='0' style='width:100%;'><tbody><tr><td style='padding:4px;'>"
           . "<div id='frmperson'><form id='theform'>"
           . "<input type='hidden' name='person_id' id='person_id' value='$person_id'/>"
           . "<table class='xxfrm' style='width:100%;'>"
           . "<tbody>"
           . "<tr><td>#id</td><td>$person_id/$employee_id</td>"
           
           . "<td rowspan='10' valign='top' style='padding-top:10px;text-align:center;'>"
           . "<div id='img_thumb_div' style='width:150px;min-height:150px;background-color:#eeeeee;border:1px solid #cccccc;margin:auto;'>"
           
           . "<img src='".XOCP_SERVER_SUBDIR."/modules/hris/thumb.php?pid=$person_id'/>"
           . "</div>"
           . "<div style='padding:4px;padding-left:0px;'>"
           . "<input type='button' value='Change Picture' onclick='chgpicture();'/>"
           . "</div>"
           . "</td>"
           
           
           . "</tr>"
           . "<tr><td>Fullname [, Title]</td><td><input name='person_nm' id='person_nm' type='text' value='".htmlentities($person_nm,ENT_QUOTES)."' style='width:250px;'/></td></tr>"
           . "<tr><td>Employee ID</td><td><input name='employee_ext_id' id='employee_ext_id' type='text' value='".htmlentities($employee_ext_id,ENT_QUOTES)."' style='width:200px;'/></td></tr>"
           . "<tr><td>Alias</td><td><input name='alias_nm' id='alias_nm' type='text' value='".htmlentities($alias_nm,ENT_QUOTES)."' style='width:50px;'/></td></tr>"
           . "<tr><td>Attendance ID</td><td><input name='attendance_id' id='attendance_id' type='text' value='".htmlentities($attendance_id,ENT_QUOTES)."' style='width:200px;'/></td></tr>"
           . "<tr><td>KTP/SIM/Passport</td><td><input name='ext_id' id='ext_id' type='text' value='".htmlentities($ext_id,ENT_QUOTES)."' style='width:200px;'/></td></tr>"
           . "<tr><td>Date Of Birth</td><td>"
              . "<span class='xlnk' onclick='showCal(this,event);' id='birth_dttm_txt'>".sql2ind($birth_dttm)."</span>"
              . " / <span id='p_age'>".toMoney($days_age/365.25)."</span> years"
              . "<input type='hidden' name='birth_dttm' id='birth_dttm' value='$birth_dttm'/>"
           . "</td></tr>"
           . "<tr><td>Place Of Birth</td><td><input name='birthplace' id='birthplace' type='text' value='".htmlentities($birthplace,ENT_QUOTES)."' style='width:200px;'/></td></tr>"
           . "<tr><td>Sex</td><td>"
              . "<input type='radio' name='adm_gender_cd' id='gender_m' value='m' ".$jk["m"]."/> <label for='gender_m' class='xlnk'>Male</label>&nbsp;&nbsp;"
              . "<input type='radio' name='adm_gender_cd' id='gender_f' value='f' ".$jk["f"]."/> <label for='gender_f' class='xlnk'>Female</label>"
           . "</td></tr>"
           
           
           . "<tr><td>Blood Type</td><td>"
              . "<input type='radio' value='A' id='blood_a' name='blood_type' $blood_ck[A]/><label for='blood_a' class='xlnk'>A</label>&nbsp;"
              . "<input type='radio' value='B' id='blood_b' name='blood_type' $blood_ck[B]/><label for='blood_b' class='xlnk'>B</label>&nbsp;"
              . "<input type='radio' value='0' id='blood_0' name='blood_type' $blood_ck[0]/><label for='blood_0' class='xlnk'>0</label>&nbsp;"
              . "<input type='radio' value='AB' id='blood_ab' name='blood_type' $blood_ck[AB]/><label for='blood_ab' class='xlnk'>AB</label>"
           . "</td></tr>"
           . "<tr><td>Rhesus</td><td><select name='blood_rhesus' id='blood_rhesus'>$opt_rhesus</select></td>"

           . "<td rowspan='3' valign='top' style='padding-top:10px;text-align:center;'>"
           . "<div id='img_thumb_cover_div' style='width:150px;height:60px;border:1px solid #cccccc;background-color:#eeeeee;margin:auto;'>"
           . "<img src='".XOCP_SERVER_SUBDIR."/modules/hris/thumb_cover.php?pid=$person_id'/>"
           . "</div>"
           . "<div style='padding:4px;padding-left:0px;'>"
           . "<input type='button' value='Change Cover' onclick='chgcover();'/>"
           . "</div>"
           . "</td>"

           . "</tr>"
           
           . "<tr><td>Marital Status</td><td>"
              . "<select name='marital_st' id='marital_st'>$mar_opt</select>"
           . "</td></tr>"
           . "<tr><td>Last Education</td><td>"
              . "<select name='education' id='education'>"
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
           . "<tr><td colspan='3'>"
           . "<input type='button' value='"._SAVE."' onclick='save_person(this,event);'/>&nbsp;&nbsp;"
           . "<input type='button' value='"._DELETE."' onclick='delete_employee(this,event);'/>"
           . "</td></tr>"
           . "</tbody>"
           . "</table></form>"
           . "</div>"
           
           . "</td>"
           /*
           . "<td valign='top' style='padding:4px;'>"
           . "<div id='img_thumb_div' style='width:150px;min-height:150px;background-color:#eeeeee;border:1px solid #cccccc;'>"
           
           . "<img src='".XOCP_SERVER_SUBDIR."/modules/hris/thumb.php?pid=$person_id'/>"
           . "</div>"
           . "<div style='padding:4px;padding-left:0px;'>"
           . "<input type='button' value='Change Picture' onclick='chgpicture();'/>"
           . "</div>"
           . "</td>"
           */
           . "</tr></tbody></table></div>";
      require_once(XOCP_DOC_ROOT."/class/ajax/ajax_map.php");
      $ajaxmap = new _class_MapAjax();
      $txt .= $ajaxmap->getJs() 
           . "<script src='".XOCP_SERVER_SUBDIR."/include/map.js' type='text/javascript'></script>";
      $person_txt = "<div id='tab_person' style='$disp01'>$txt</div>";
      $dv_person = $txt;
      
      //////////////////////////////////////// ADDRESS TAB /////////////////////////////////////
      
      $txt = "<div style='padding:6px;'><form id='address_form'><table class='xxfrm' style='width:100%;'><tbody>"
           
           ////////// permanent address //////////////////////
           . "<tr><td colspan='2' style='text-align:center;font-weight:bold;'>Current Address</td></tr>"
           . "<tr><td>Street Address</td><td><input name='addr_txt' id='addr_txt' type='text' value='$addr_txt' style='width:300px;'/></td></tr>"
           . "<tr><td>Region</td><td>"
              . "<span id='region_txt' class='xlnk' onclick='choose_region(this,\"_regionDiv\",selregion,\"$regional_cd\");'>$region_txt</span>"
              . "<input type='hidden' name='regional_cd' id='regional_cd' value='$regional_cd'/>"
              . "<div id='_regionDiv' style='display:none;padding:2px;border:1px solid #888888;margin:2px;background-color:#ddddff;'></div>"
           . "</td></tr>"
           . "<tr><td>Postal Code</td><td><input name='zip_cd' id='zip_cd' type='text' value='$zip_cd' size='5' maxlength='5'/></td></tr>"
           . "<tr><td>Country</td><td><input name='country' id='country' type='text' value='$country' width='200px;'/></td></tr>"
           . "<tr><td>Telephone</td><td><input name='home_phone' id='home_phone' type='text' value='$home_phone' style='width:200px;'/></td></tr>"
           . "<tr><td>Faximile</td><td><input name='fax' id='fax' type='text' value='$fax' style='width:200px;'/></td></tr>"
           . "<tr><td>Cellphone</td><td><input name='cell_phone' id='cell_phone' type='text' value='$cell_phone' style='width:200px;'/></td></tr>"
           . "<tr><td>E-mail</td><td><input name='email' id='email' type='text' value='$email' style='width:250px;'/>"
              . "&nbsp;/&nbsp;SMTP Server : <input type='text' name='smtp_location' id='smtp_location' value='$smtp_location' style='width:100px;'/></td></tr>"
           
           /////////// temporary address /////////////////////
           . "<tr><td colspan='2' style='text-align:center;font-weight:bold;'>Hire Point Address</td></tr>"
           . "<tr><td>Street Address</td><td><input name='tmp_addr_txt' id='tmp_addr_txt' type='text' value='$tmp_addr_txt' style='width:300px;'/></td></tr>"
           . "<tr><td>Region</td><td>"
              . "<span id='tmp_region_txt' class='xlnk' onclick='choose_region(this,\"_tmp_regionDiv\",tmp_selregion,\"$tmp_regional_cd\");'>$tmp_region_txt</span>"
              . "<input type='hidden' name='tmp_regional_cd' id='tmp_regional_cd' value='$tmp_regional_cd'/>"
              . "<div id='_tmp_regionDiv' style='display:none;padding:2px;border:1px solid #888888;margin:2px;background-color:#ddddff;'></div>"
           . "</td></tr>"
           . "<tr><td>Postal Code</td><td><input name='tmp_zip_cd' id='tmp_zip_cd' type='text' value='$tmp_zip_cd' size='5' maxlength='5'/></td></tr>"
           . "<tr><td>Country</td><td><input name='tmp_country' id='tmp_country' type='text' value='$tmp_country' width='200px;'/></td></tr>"
           . "<tr><td>Telephone</td><td><input name='tmp_phone' id='tmp_phone' type='text' value='$tmp_phone' style='width:200px;'/></td></tr>"
           
           /////////// emergency contact /////////////////////
           . "<tr><td colspan='2' style='text-align:center;font-weight:bold;'>Emergency Contact</td></tr>"
           . "<tr><td>Contact Person</td><td><input name='emergency_person_nm' id='emergency_person_nm' type='text' value='$emergency_person_nm' style='width:250px;'/></td></tr>"
           . "<tr><td>Occupation</td><td><input name='emergency_occupation' id='emergency_occupation' type='text' value='$emergency_occupation' style='width:250px;'/></td></tr>"
           . "<tr><td>Relation</td><td><select name='emergency_relation'>$opt_emergency_rel</select></td></tr>"
           . "<tr><td>Street Address</td><td><input name='emergency_addr_txt' id='emergency_addr_txt' type='text' value='$emergency_addr_txt' style='width:300px;'/></td></tr>"
           . "<tr><td>Region</td><td>"
              . "<span id='emergency_region_txt' class='xlnk' onclick='choose_region(this,\"_emergency_regionDiv\",emergency_selregion,\"$emergency_regional_cd\");'>$emergency_region_txt</span>"
              . "<input type='hidden' name='emergency_regional_cd' id='emergency_regional_cd' value='$emergency_regional_cd'/>"
              . "<div id='_emergency_regionDiv' style='display:none;padding:2px;border:1px solid #888888;margin:2px;background-color:#ddddff;'></div>"
           . "</td></tr>"
           . "<tr><td>Postal Code</td><td><input name='emergency_zip_cd' id='emergency_zip_cd' type='text' value='$emergency_zip_cd' size='5' maxlength='5'/></td></tr>"
           . "<tr><td>Country</td><td><input name='emergency_country' id='emergency_country' type='text' value='$emergency_country' width='200px;'/></td></tr>"
           . "<tr><td>Telephone</td><td><input name='emergency_phone' id='emergency_phone' type='text' value='$emergency_phone' style='width:200px;'/></td></tr>"
           
           . "<tr><td colspan='2'><span id='progress_address'></span>&nbsp;&nbsp;<input type='button' value='"._SAVE."' onclick='save_address(this,event);'/></td></tr>"
           
           . "</tbody></table></form></div>";
      $dv_address = $txt;
      
      //////////////////////////////////////// FAMILY TAB ////////////////////////////////////////
      
      $sql = "SELECT a.person_nm,a.date_of_birth,a.gender_cd,a.relation_type,a.ctc_seq,TO_DAYS(NOW())-TO_DAYS(a.date_of_birth)"
           . " FROM ".XOCP_PREFIX."employee_family a"
           . " WHERE a.employee_id = '$employee_id'";
      $result = $db->query($sql);
      
      $txt = "<div id='family' style='padding:4px;'>";
           
      $txt .= "<div id='caption_family' class='lst' style='font-weight:bold;background-color:#f7f7f7;border-top:1px solid #ddd;'>"
            . "<table style='width:100%;'><colgroup><col width='25'/><col/><col width='70'/><col width='150'/><col width='90'/><col width='50'/></colgroup>"
            . "<tbody><tr><td>#</td><td>Name"
            . "</td><td style='text-align:center;'>Gender"
            . "</td><td>Date of Birth"
            . "</td><td style='text-align:right;'>Age (Years)&nbsp;&nbsp;"
            . "</td><td>Relation"
            . "</td></tr></tbody></table>"
            . "</div>\n";
           
      if($db->getRowsNum($result)>0) {
         while(list($family_person_nm,$family_dob,$family_gender_cd,$family_relation_type,$ctc_seq,$days_age)=$db->fetchRow($result)) {
            $txt .= "<div id='family_${ctc_seq}' class='lst'>"
                  . "<table style='width:100%;'><colgroup><col width='25'/><col/><col width='70'/><col width='150'/><col width='90'/><col width='50'/></colgroup>"
                  . "<tbody><tr><td>$ctc_seq</td><td>$family_person_nm"
                  . "</td><td style='text-align:center;'>$family_gender_cd"
                  . "</td><td>".sql2ind($family_dob,"date")
                  . "</td><td style='text-align:right;'>".toMoney($days_age/365.25)."&nbsp;&nbsp;&nbsp;&nbsp;"
                  . "</td><td>$family_relation_type"
                  . "</td></tr></tbody></table>"
                  . "</div>\n";
         }
      }
      $txt .= "</div>";
      $dv_family = $txt;
      
      
      //////////////////////////////////////// EDUCATION TAB ////////////////////////////////////////
      
      global $arr_edu;
      $sql = "SELECT a.education_seq,a.education_nm,a.educlvl_cd,a.start_dttm,a.stop_dttm"
           . " FROM ".XOCP_PREFIX."person_education a"
           . " WHERE a.person_id = '$person_id'"
           . " ORDER BY a.start_dttm DESC, a.stop_dttm DESC";
      $result = $db->query($sql);
      
      $txt = "<div id='education' style='padding:4px;'>";

      $txt .= "<div id='hdredu' class='hdr'>"
           . "<table border='0' cellpadding='0' cellspacing='0' width='100%'><tbody><tr>"
           . "<td style='font-weight:bold;padding-left:5px;'>Education History</td><td style='text-align:right;'>"
           . "<input type='button' value='"._ADD."' onclick='add_edu();'/>"
           . "</td></tr></tbody></table>"
           . "</div>";
           
           
      $txt .= "<div id='caption_edu' class='lst' style='font-weight:bold;background-color:#f7f7f7;border-top:1px solid #ddd;'>"
            . "<table style='width:100%;'><colgroup><col width='20'/><col/><col width='125'/><col width='130'/><col width='130'/></colgroup>"
            . "<tbody><tr>"
            . "<td>#"
            . "</td><td>School or Colleges"
            . "</td><td>Level"
            . "</td><td>Start Date"
            . "</td><td>End Date"
            . "</td></tr></tbody></table>"
            . "</div>\n";
           
      if($db->getRowsNum($result)>0) {
         while(list($education_seq,$education_nm,$educlvl_cd,$start_dttm,$stop_dttm)=$db->fetchRow($result)) {
            $educlvl_nm = $arr_edu[$educlvl_cd];
            if(trim($education_nm)=="") $education_nm = _EMPTY;
            $txt .= "<div id='education_${education_seq}' class='lst'>"
                  . "<table style='width:100%;'><colgroup><col width='20'/><col/><col width='125'/><col width='130'/><col width='130'/></colgroup>"
                  . "<tbody><tr>"
                  . "<td>$education_seq"
                  . "</td><td id='tdeducation_nm_${education_seq}'><span id='spedu_${education_seq}' class='xlnk' onclick='edit_edu(\"$education_seq\",this,event);'>$education_nm</span>"
                  . "</td><td id='tdeducation_lvl_${education_seq}' style='text-align:left;'>$educlvl_nm"
                  . "</td><td id='tdedustart_${education_seq}'>".sql2ind($start_dttm,"date")
                  . "</td><td id='tdedustop_${education_seq}'>".sql2ind($stop_dttm,"date")
                  . "</td></tr></tbody></table>"
                  . "</div>\n";
         }
      }
      $txt .= "</div>";
      $dv_education = $txt;
      
      //////////////////////////////////////// TRAINING TAB ////////////////////////////////////////
      
      global $arr_training;
      $sql = "SELECT a.training_seq,a.training_subject,a.institution,a.start_dttm,a.stop_dttm"
           . " FROM ".XOCP_PREFIX."person_training a"
           . " WHERE a.person_id = '$person_id'"
           . " ORDER BY a.start_dttm DESC, a.stop_dttm DESC";
      $result = $db->query($sql);
      
      $txt = "<div id='training' style='padding:4px;'>";
      
      $txt .= "<div id='hdrtraining' class='hdr'>"
           . "<table border='0' cellpadding='0' cellspacing='0' width='100%'><tbody><tr>"
           . "<td style='font-weight:bold;padding-left:5px;'>Training History</td><td style='text-align:right;'>"
           . "<input type='button' value='"._ADD."' onclick='add_training();'/>"
           . "</td></tr></tbody></table>"
           . "</div>";
           
      $txt .= "<div id='caption_training' class='lst' style='font-weight:bold;background-color:#f7f7f7;border-top:1px solid #ddd;'>"
            . "<table style='width:100%;'><colgroup><col width='20'/><col/><col width='125'/><col width='130'/><col width='130'/></colgroup>"
            . "<tbody><tr>"
            . "<td>#"
            . "</td><td>Training Subject"
            . "</td><td>Institution"
            . "</td><td>Start Date"
            . "</td><td>End Date"
            . "</td></tr></tbody></table>"
            . "</div>\n";
           
      if($db->getRowsNum($result)>0) {
         while(list($training_seq,$training_subject,$institution,$start_dttm,$stop_dttm)=$db->fetchRow($result)) {
            if(trim($training_subject)=="") $training_subject = _EMPTY;
            $txt .= "<div id='training_${training_seq}' class='lst'>"
                  . "<table style='width:100%;'><colgroup><col width='20'/><col/><col width='125'/><col width='130'/><col width='130'/></colgroup>"
                  . "<tbody><tr>"
                  . "<td>$training_seq"
                  . "</td><td id='tdtraining_subject_${training_seq}'><span id='sptraining_${training_seq}' class='xlnk' onclick='edit_training(\"$training_seq\",this,event);'>$training_subject</span>"
                  . "</td><td id='tdinstitution_${training_seq}' style='text-align:left;'><div style='width:123px !important;overflow:hidden;'><div id='dvinstitution_${training_seq}' style='width:900px;'>$institution</div></div>"
                  . "</td><td id='tdtrainingstart_${training_seq}'>".sql2ind($start_dttm,"date")
                  . "</td><td id='tdtrainingstop_${training_seq}'>".sql2ind($stop_dttm,"date")
                  . "</td></tr></tbody></table>"
                  . "</div>\n";
         }
      }
      $txt .= "</div>";
      $dv_training = $txt;
      
      ///////////////// Education education /////////////////////////////////// end ///////////////////
      
      //////////////////////////////////////// ROLE TAB ////////////////////////////////////////
      
      
      ///// active job
      $sql = "SELECT a.job_id,TRIM(b.job_nm) as c_nm,b.job_cd,a.start_dttm,a.stop_dttm,a.gradeval,a.assignment_t,"
           . "a.upper_job_id,a.upper_employee_id,a.assessor_job_id,a.assessor_employee_id"
           . " FROM ".XOCP_PREFIX."employee_job a"
           . " LEFT JOIN ".XOCP_PREFIX."jobs b ON b.job_id = a.job_id"
           . " WHERE a.employee_id = '$employee_id'"
           . " GROUP BY a.job_id"
           . " ORDER BY c_nm";
      $result = $db->query($sql);
      
      $company_length_year = number_format($company_length/365.25,1);
      
      $txt = "<div id='company_assign' style='padding:4px;'>"
           
           . "<div id='hdrcompany' class='hdr'>"
           . "<table border='0' cellpadding='0' cellspacing='0' width='100%'><tbody><tr>"
           . "<td style='font-weight:bold;padding-left:5px;'>Company Entrance</td>"
           . "</tr></tbody></table>"
           . "</div>"
           
           . "<div id='dvcompassign'>"
           . "<table style='width:100%;' class='xxfrm'>"
           . "<colgroup><col width='250'/><col/></colgroup>"
           . "<tbody>"
           . "<tr><td>Entrance Date</td><td>"
              . "<span class='xlnk' onclick='showCalEntr(this,event);' id='entrance_dttm_txt'>".sql2ind($entrance_dttm,"date")."</span>"
              . "<input type='hidden' name='entrance_dttm' id='entrance_dttm' value='$entrance_dttm'/>"
           . "</td></tr>"
           . "<tr><td>Termination Date</td><td>"
              . ($exit_dttm!=""?"<span class='xlnk' onclick='showCalExit(this,event);' id='exit_dttm_txt'>".sql2ind($exit_dttm,"date")."</span>"
              . "<input type='hidden' name='exit_dttm' id='exit_dttm' value='$exit_dttm'/>":"-")
           . "</td></tr>"
           . "<tr><td>Length</td><td><span id='company_length'>$company_length_year</span> years</td></tr>"
           . ($status_cd=="terminated"? "<tr><td style='text-align:center;font-weight:bold;' colspan='2'>This employee has been terminated.</td></tr>" : "<tr><td colspan='2'><input type='button' value='Terminate' onclick='terminate_empl(this,event);'/></td></tr>")
           . "</tbody></table>"
           . "</div>"
           
           . "</div>";
      
      $txt .= "<div id='active_jobs' style='padding:4px;margin-top:20px;'><div id='hdrjob' class='hdr'>"
            . "<table border='0' cellpadding='0' cellspacing='0' width='100%'><tbody><tr>"
            . "<td style='font-weight:bold;padding-left:5px;'>Active Job</td><td style='text-align:right;'>"
            . "Find Job : <input type='text' id='qjob'/>"
            . "</td></tr></tbody></table>"
            . "</div>";
           
      $txt .= "<div id='caption_jobs' class='lst' style='border-top:1px solid #ddd;font-weight:bold;background-color:#f7f7f7;'>"
            . "<table style='width:100%;'><colgroup><col width='80'/><col/><col width='50'/><col width='130'/><col width='130'/></colgroup>"
            . "<tbody><tr><td>Status"
            . "</td><td>Job Title"
            . "</td><td style='text-align:center;'>Grade"
            . "</td><td>Start"
            . "</td><td>Stop"
            . "</td></tr></tbody></table>"
            . "</div>\n";
           
           
      if($db->getRowsNum($result)>0) {
         while(list($job_id,$job_nm,$job_cd,$start_dttm,$stop_dttm,$gradeval,$assignment_t,
                    $upper_job_id,$upper_employee_id,$assessor_job_id,$assessor_employee_id)=$db->fetchRow($result)) {
            $txt .= "<div id='c_${job_id}' class='lst'>"
                  . "<table style='width:100%;'><colgroup><col width='80'/><col/><col width='50'/><col width='130'/><col width='130'/></colgroup>"
                  . "<tbody><tr><td id='active_assignment_t_${job_id}'>"
                  . ucfirst($assignment_t)."</td><td>"
                  . "<span onclick='edit_job(\"$job_id\",this,event);' class='xlnk'>$job_nm</span></td>"
                  . "<td id='active_grade_${job_id}' style='text-align:center;'>$gradeval</td>"
                  . "<td id='active_job_${job_id}'>"
                  . sql2ind($start_dttm,"date")
                  . "</td><td>"
                  . "-"
                  . "</td></tr></tbody></table>"
                  . "</div>\n";
         }
      }
      $txt .= "</div>";
      
      ////////// job history
      $sql = "SELECT a.history_id,a.job_id,TRIM(b.job_nm) as c_nm,b.job_cd,a.start_dttm,a.stop_dttm,a.gradeval,a.assignment_t,"
           . "a.upper_job_id,a.upper_employee_id,a.assessor_job_id,a.assessor_employee_id"
           . " FROM ".XOCP_PREFIX."employee_job_history a"
           . " LEFT JOIN ".XOCP_PREFIX."jobs b ON b.job_id = a.job_id"
           . " WHERE a.employee_id = '$employee_id'"
           . " ORDER BY a.start_dttm DESC";
      $result = $db->query($sql);
      $txt .= "<div id='job_history' style='margin-top:20px;padding:4px;'><div id='hdrjob_history' class='hdr'>"
           . "<table border='0' cellpadding='0' cellspacing='0' width='100%'><tbody><tr>"
           . "<td style='font-weight:bold;padding-left:5px;'>History</td><td style='text-align:right;'>"
           . "&nbsp;"
           . "</td></tr></tbody></table>"
           . "</div>";
           
      $txt .= "<div id='caption_jobs_history' class='lst' style='font-weight:bold;background-color:#f7f7f7;border-top:1px solid #ddd;'>"
            . "<table style='width:100%;'><colgroup><col width='80'/><col/><col width='50'/><col width='130'/><col width='130'/></colgroup>"
            . "<tbody><tr><td>Status"
            . "</td><td>Job Title"
            . "</td><td>Grade"
            . "</td><td>Start"
            . "</td><td>Stop"
            . "</td></tr></tbody></table>"
            . "</div>\n";
           
           
      if($db->getRowsNum($result)>0) {
         while(list($history_id,$job_id,$job_nm,$job_cd,$start_dttm,$stop_dttm,$gradeval,$assignment_t,
                    $upper_job_id,$upper_employee_id,$assessor_job_id,$assessor_employee_id)=$db->fetchRow($result)) {
            $txt .= "<div id='hc_${history_id}' class='lst'>"
                  . "<table style='width:100%;'><colgroup><col width='80'/><col/><col width='50'/><col width='130'/><col width='130'/></colgroup>"
                  . "<tbody><tr><td>"
                  . ucfirst($assignment_t)."</td><td>"
                  . "<span onclick='edit_job_history(\"$history_id\",this,event);' class='xlnk'>$job_nm</span>"
                  . "</td><td id='history_job_grade_${history_id}' style='text-align:center;'>$gradeval"
                  . "</td><td id='history_job_start_${history_id}'>"
                  . sql2ind($start_dttm,"date")
                  . "</td><td id='history_job_stop_${history_id}'>"
                  . sql2ind($stop_dttm,"date")
                  . "</td></tr></tbody></table>"
                  . "</div>\n";
         }
      }
      $txt .= "</div>";
      
      
      
      $job_txt = "<div id='tab_job' style='$disp03'>$txt</div>";
      $dv_job = $txt;
      
      //////////////////////////////////////////////////////////////////////////////////////////
      //////////////////////////////////////////////////////////////////////////////////////////
      
      
      
      $dv_absen = "";
      if(trim($attendance_id)!="") {
         $sql = "SELECT slide_dttm,entry_dttm,source,raw_txt from ".XOCP_PREFIX."cardraw"
              . " WHERE attendance_id_part LIKE '$attendance_id%'"
              . " ORDER BY slide_dttm DESC";
         $result = $db->query($sql);
         $old_dt = "";
         if($db->getRowsNum($result)>0) {
            while(list($slide_dttm,$entry_dttm,$source,$raw_txt)=$db->fetchRow($result)) {
               list($dt,$tm)=explode(" ",$entry_dttm);
               if($old_dt!=$dt) {
                  $dv_absen .= "<div style='border:1px solid #bbb;padding:3px;background-color:#ddd;'>".sql2ind($dt,"date")."</div>";
                  $old_dt = $dt;
               }
               $tmx = substr($tm,0,-3);
               $dv_absen .= "<div style='padding-left:10px;'><table><colgroup><col width='60'/><col width='220'/><col width='60'/></colgroup><tbody><tr><td>$tmx</td><td>$raw_txt</td><td>$source</td></tr></tbody></table></div>";
            }
         }
      }
      
      //////////////////////////////////////////////////////////////////////////////////////////
      //////////////////////////////////////////////////////////////////////////////////////////
      //////////////////////////////////////////////////////////////////////////////////////////

      $tabno = 9;
      $ret = ///// UL ////////////////////////////
             "<ul class='ultab'>"
           . "<li id='litab_0' class='ultabsel_greyrev'><span onclick='pnltab(\"0\",this,event);' class='xlnk'>Personnel Data</span></li>"
           . "<li id='litab_7'><span onclick='pnltab(\"7\",this,event);' class='xlnk'>Addresses</span></li>"
           . "<li id='litab_1'><span onclick='pnltab(\"1\",this,event);' class='xlnk'>Job Assignment</span></li>"
           . "<li id='litab_2'><span onclick='pnltab(\"2\",this,event);' class='xlnk'>Training</span></li>"
           . "<li id='litab_3'><span onclick='pnltab(\"3\",this,event);' class='xlnk'>Education</span></li>"
           . "<li id='litab_4'><span onclick='pnltab(\"4\",this,event);' class='xlnk'>Family</span></li>"
           . "<li id='litab_5' style='display:none;'><span onclick='pnltab(\"5\",this,event);' class='xlnk'>Health</span></li>"
           . "<li id='litab_6' style='display:none;'><span onclick='pnltab(\"6\",this,event);' class='xlnk'>References</span></li>"
           . "<li id='litab_8'><span onclick='pnltab(\"8\",this,event);' class='xlnk'>Test Absen</span></li>"
           . "</ul>"
           ///////// DIV ////////////////
           . "<div style='min-height:600px;margin-bottom:200px;;border:1px solid #999999;clear:both;padding:4px;'>"
           . "<div id='dvtab_0'>$dv_person</div>" 
           . "<div id='dvtab_1' style='display:none;'>$dv_job</div>"
           . "<div id='dvtab_2' style='display:none;'>$dv_training</div>"
           . "<div id='dvtab_3' style='display:none;'>$dv_education</div>"
           . "<div id='dvtab_4' style='display:none;'>$dv_family</div>"
           . "<div id='dvtab_5' style='display:none;'>Health</div>"
           . "<div id='dvtab_6' style='display:none;'>References</div>"
           . "<div id='dvtab_7' style='display:none;'>$dv_address</div>"
           . "<div id='dvtab_8' style='display:none;'>$dv_absen</div>"
           . "</div>";
      

      
      $ajaxasm = new _hris_class_AssessmentSessionAjax("asg");
      $ajax = new _hris_class_EmployeeAjax("emp");
      $js = $ajaxasm->getJs().$ajax->getJs()."<script src='".XOCP_SERVER_SUBDIR."/include/calendar.js' type='text/javascript'></script>
      <script type='text/javascript'><!--
      
      
      function generate_assessor(employee_id,d,e) {
         $('progress_generate_assessor').innerHTML = '';
         $('progress_generate_assessor').appendChild(progress_span(' ... generating'));
         asg_app_findAssessor(8,employee_id,function(_data) {
            $('progress_generate_assessor').innerHTML = 'done.';
         });
      }
      
      var curnew_training = null;
      function add_training() {
         obtrainingstart = null;
         obtrainingstop = null;
         _destroy($('obtrainingstart'));
         _destroy($('obtrainingstop'));
         if(curtraining_edit) {
            _destroy(curtraining_edit.edit);
            curtraining_edit.training_seq = null;
            curtraining_edit = null;
         }
         curnew_training = _dce('div');
         var hdr = $('hdrtraining');
         curnew_training = hdr.parentNode.insertBefore(curnew_training,$('caption_training').nextSibling);
         curnew_training.appendChild(progress_span());
         emp_app_newTraining(function(_data) {
            var data = recjsarray(_data);
            curnew_training.innerHTML = data[1];
            curnew_training.setAttribute('id','training_'+data[0]);
            curnew_training.className = 'lst';
            curtraining_edit = $('sptraining_'+data[0]);
            curtraining_edit.edit = $('trainingeditor');
            curtraining_edit.training_seq = data[0];
            curtraining_edit.dv = _gel('training_'+data[0]);
            $('training_subject').focus();
         });
      }
      
      var obtrainingstart = null;
      function settrainingstart(dt) {
         _gel('trainingstart_txt').innerHTML = obtrainingstart.obj.toString(obtrainingstart.obj.getResult(),'date');
         _gel('htrainingstart').value = dt;
      }
      
      function editstarttraining(d,e) {
         if(!d.cal) {
            d.cal = new calendarClass('obtrainingstart',settrainingstart);
            d.cal.notime = true;
            d.cal.div.style.position = 'absolute';
            d.cal.div.style.left = '0px';
            d.cal.div.style.top = '0px';
            d.cal.div.style.visibility = 'hidden';
            obtrainingstart=document.body.appendChild(d.cal.div);
            obtrainingstart.obj.setDTTM($('htrainingstart').value);
            d.obdt = obtrainingstart;
         } else {
            obtrainingstart = d.obdt;
         }
         if(obtrainingstart.style.visibility=='hidden') {
            obtrainingstart.style.left = (oX(d))+'px';
            obtrainingstart.style.top = (oY(d)+d.offsetHeight)+'px';
            obtrainingstart.style.visibility='visible';
         } else {
            obtrainingstart.obj.hide();
            _destroy(d.cal);
            d.cal = null;
         }
      }
      
      var obtrainingstop = null;
      function settrainingstop(dt) {
         _gel('trainingstop_txt').innerHTML = obtrainingstop.obj.toString(obtrainingstop.obj.getResult(),'date');
         _gel('htrainingstop').value = dt;
      }
      
      function editstoptraining(d,e) {
         if(!d.cal) {
            d.cal = new calendarClass('obtrainingstop',settrainingstop);
            d.cal.notime = true;
            d.cal.div.style.position = 'absolute';
            d.cal.div.style.left = '0px';
            d.cal.div.style.top = '0px';
            d.cal.div.style.visibility = 'hidden';
            obtrainingstop=document.body.appendChild(d.cal.div);
            obtrainingstop.obj.setDTTM($('htrainingstop').value);
            d.obdt = obtrainingstop;
         } else {
            obtrainingstop = d.obdt;
         }
         if(obtrainingstop.style.visibility=='hidden') {
            obtrainingstop.style.left = (oX(d))+'px';
            obtrainingstop.style.top = (oY(d)+d.offsetHeight)+'px';
            obtrainingstop.style.visibility='visible';
         } else {
            obtrainingstop.obj.hide();
            _destroy(d.cal);
            d.cal = null;
         }
      }
      
      var curtraining_edit = null;
      function edit_training(training_seq,d,e) {
         obtrainingstart = null;
         obtrainingstop = null;
         _destroy($('obtrainingstart'));
         _destroy($('obtrainingstop'));
         if(curtraining_edit&&curtraining_edit.training_seq==training_seq) {
            _destroy(curtraining_edit.edit);
            curtraining_edit.training_seq = null;
            curtraining_edit = null;
            return;
         } else if(curtraining_edit) {
            _destroy(curtraining_edit.edit);
         }
         curtraining_edit = d;
         curtraining_edit.training_seq = training_seq;
         curtraining_edit.dv = _gel('training_'+training_seq);
         curtraining_edit.edit = curtraining_edit.dv.appendChild(_dce('div'));
         curtraining_edit.edit.setAttribute('id','trainingeditor');
         curtraining_edit.edit.appendChild(progress_span());
         emp_app_editTraining(training_seq,function(_data) {
            curtraining_edit.edit.innerHTML = _data;
            $('training_subject').focus();
         });
      }
      
      function save_training(training_seq,d,e) {
         var ret = parseForm('frmtraining');
         if(trim($('training_subject').value)=='') {
            alert('Training subject cannot be empty.');
            $('training_subject').focus();
            return;
         }
         curtraining_edit.edit.innerHTML = '';
         curtraining_edit.edit.appendChild(progress_span(' ... saving'));
         obtrainingstart = null;
         obtrainingstop = null;
         _destroy($('obtrainingstart'));
         _destroy($('obtrainingstop'));
         emp_app_saveTraining(training_seq,ret,function(_data) {
            _destroy(curtraining_edit.edit);
            curtraining_edit = null;
            var data = recjsarray(_data);
            $('tdtrainingstart_'+data[0]).innerHTML = data[1];
            $('tdtrainingstop_'+data[0]).innerHTML = data[2];
            $('dvinstitution_'+data[0]).innerHTML = data[3];
            $('sptraining_'+data[0]).innerHTML = data[4];
         });
      }
      
      function delete_training(training_seq,d,e) {
         curtraining_edit.edit.oldHTML = curtraining_edit.edit.innerHTML;
         curtraining_edit.edit.innerHTML = '';
         var dv = _dce('div');
         dv.setAttribute('style','margin-top:5px;border:1px solid #aaaaaa;');
         dv.className = 'msg_delete_warn';
         dv.innerHTML = 'Are you sure you want to delete this training history?<br/><br/>'
                      + '<input type=\"button\" value=\""._CANCEL."\" onclick=\"cancel_delete_training();\"/>&nbsp;&nbsp;'
                      + '<input type=\"button\" value=\""._DELETE."\" onclick=\"confirm_delete_training(\''+training_seq+'\');\"/>';
         curtraining_edit.edit.appendChild(dv);
      }
      
      function cancel_delete_training() {
         curtraining_edit.edit.innerHTML = curtraining_edit.edit.oldHTML;
      }
      
      function confirm_delete_training(training_seq) {
         _destroy(_gel('training_'+training_seq));
         curtraining_edit.training_seq = null;
         curtraining_edit = null;
         emp_app_deleteTraining(training_seq,null);
      }
      
      //// education Education
      
      var curnew_edu = null;
      function add_edu() {
         obedustart = null;
         obedustop = null;
         _destroy($('obedustart'));
         _destroy($('obedustop'));
         if(curedu_edit) {
            _destroy(curedu_edit.edit);
            curedu_edit.education_seq = null;
            curedu_edit = null;
         }
         curnew_edu = _dce('div');
         var hdr = $('hdredu');
         curnew_edu = hdr.parentNode.insertBefore(curnew_edu,$('caption_edu').nextSibling);
         curnew_edu.appendChild(progress_span());
         emp_app_newEducation(function(_data) {
            var data = recjsarray(_data);
            curnew_edu.innerHTML = data[1];
            curnew_edu.setAttribute('id','education_'+data[0]);
            curnew_edu.className = 'lst';
            curedu_edit = $('spedu_'+data[0]);
            curedu_edit.edit = $('edueditor');
            curedu_edit.education_seq = data[0];
            curedu_edit.dv = _gel('education_'+data[0]);
            $('education_nm').focus();
         });
      }
      
      function save_address(d,e) {
         ajax_feedback = null;
         var ret = parseForm('address_form');
         emp_app_saveAddress(ret,function(_data) {
         });
      }
      
      var exit_dttm = null;
      function do_terminate(d,e) {
         exit_dttm = $('exit_dttm').value;
         $('dvcompassign').innerHTML = '';
         $('dvcompassign').appendChild(progress_span());
         emp_app_Terminate(exit_dttm,function(_data) {
            $('dvcompassign').innerHTML = _data;
            var dvs = $('active_jobs').childNodes;
            for(var i=0;i<dvs.length;i++) {
               var dv = dvs[i];
               if(dv.id) {
                  if(dv.id.substring(0,2)=='c_') {
                     _destroy(dv);
                     if(cur_edit) {
                        cur_edit.job_id = null;
                        cur_edit = null;
                     }
                     
                     emp_app_stopJob(dv.id.substring(2),'terminate',exit_dttm,function(_data) {
                        if(hist_edit) {
                           _destroy(hist_edit.edit);
                        }
                        hist_edit = $('caption_jobs_history');
                        
                        var data = recjsarray(_data);
                        
                        dv = _dce('div');
                        dv.setAttribute('id','hc_'+data[0]);
                        dv.className = 'lst';
                        var hdr = _gel('hdrjob_history');
                        dv = hdr.parentNode.insertBefore(dv,$('caption_jobs_history').nextSibling);
                        dv.innerHTML = data[1];
                     });
                     
                  }
               }
            }
         });
      }
      
      function cancel_terminate(d,e) {
         $('dvcompassign').innerHTML = $('dvcompassign').oldHTML;
      }
      
      function terminate_empl(d,e) {
         $('dvcompassign').oldHTML = $('dvcompassign').innerHTML;
         $('dvcompassign').innerHTML = '';
         $('dvcompassign').appendChild(progress_span());
         emp_app_confirmTerminate(function(_data) {
            var data = recjsarray(_data);
            $('dvcompassign').innerHTML = data[1];
            obdtexit.obj.setDTTM(data[0]);
         });
      }
      
      function cancel_stop_job(job_id,d,e) {
         cur_edit.edit.innerHTML = '';
         cur_edit.edit.appendChild(progress_span());
         emp_app_editJob(job_id,function(_data) {
            cur_edit.edit.innerHTML = _data;
         });
      }
      
      function do_stop_job(job_id,d,e) {
         var ret = parseForm('frmjob');
         cur_edit.edit.innerHTML = '';
         cur_edit.edit.appendChild(progress_span(' ... saving'));
         obdtstart = null;
         obdtstop = null;
         _destroy($('obdtstart'));
         _destroy($('obdtstop'));
         emp_app_stopJob(job_id,ret,function(_data) {
            _destroy(cur_edit.edit);
            _destroy(_gel('c_'+cur_edit.job_id));
            cur_edit.job_id = null;
            cur_edit = null;
            
            if(hist_edit) {
               _destroy(hist_edit.edit);
            }
            hist_edit = $('caption_jobs_history');
            
            var data = recjsarray(_data);
            
            dv = _dce('div');
            dv.setAttribute('id','hc_'+data[0]);
            dv.className = 'lst';
            var hdr = _gel('hdrjob_history');
            dv = hdr.parentNode.insertBefore(dv,$('caption_jobs_history').nextSibling);
            dv.innerHTML = data[1];
         });
      }
      
      function stop_job(job_id,d,e) {
         cur_edit.edit.innerHTML = '';
         cur_edit.edit.appendChild(progress_span());
         emp_app_confirmStopJob(job_id,function(_data) {
            cur_edit.edit.innerHTML = _data;
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

      document.load_thumbnail_cover = function(person_id,uniqid) {
         var dv = _gel('img_thumb_cover_div');
         dv.innerHTML = '';
         _destroy(dv.img);
         dv.img = dv.appendChild(_dce('img'));
         dv.img.setAttribute('id','pic_'+person_id);
         dv.img.src = '".XOCP_SERVER_SUBDIR."/modules/hris/thumb_cover.php?pid='+person_id+'&f='+uniqid;
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

      var pdl = null;
      function chgcover() {
         pdl = _dce('div');
         pdl.setAttribute('id','pdl');
         pdl.bg = _dce('div');
         pdl.bg.setAttribute('style','height:150%;width:150%;position:fixed;top:0px;left:-10px;background-color:#000000;opacity:0.5;z-index:10000');
         pdl.bg = document.body.appendChild(pdl.bg);
         pdl.setAttribute('style','text-align:center;padding-top:15px;padding-bottom:15px;width:400px;position:fixed;top:50%;left:50%;background-color:white;border:1px solid #555555;margin-left:-200px;margin-top:-100px;opacity:1;z-index:10001;');
         pdl.innerHTML = '<div style=\"background-color:#5666b3;padding:4px;margin:5px;color:#ffffff;width:366px;margin-left:15px;\">Upload Cover</div>'
                       + '<iframe src=\"".XOCP_SERVER_SUBDIR."/modules/hris/uploadcover.php\" style=\"width:370px;border:0px solid black;overflow:visible;\"></iframe>';
         pdl = document.body.appendChild(pdl);
         pdl.cancelUpload = function() {
            _destroy(pdl);
            _destroy(pdl.bg);
         };
      }
      
      var obdtstart = null;
      function setstartjob(dt) {
         _gel('startjob_txt').innerHTML = obdtstart.obj.toString(obdtstart.obj.getResult(),'date');
         _gel('hstartjob').value = dt;
      }
      
      function editstartjob(d,e) {
         if(!d.cal) {
            d.cal = new calendarClass('obdtstart',setstartjob);
            d.cal.notime = true;
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
         _gel('stopjob_txt').innerHTML = obdtstop.obj.toString(obdtstop.obj.getResult(),'date');
         _gel('hstopjob').value = dt;
      }
      
      function editstopjob(d,e) {
         if(!d.cal) {
            d.cal = new calendarClass('obdtstop',setstopjob);
            d.cal.notime = true;
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
      
      var obedustart = null;
      function setedustart(dt) {
         _gel('edustart_txt').innerHTML = obedustart.obj.toString(obedustart.obj.getResult(),'date');
         _gel('hedustart').value = dt;
      }
      
      function editstartedu(d,e) {
         if(!d.cal) {
            d.cal = new calendarClass('obedustart',setedustart);
            d.cal.notime = true;
            d.cal.div.style.position = 'absolute';
            d.cal.div.style.left = '0px';
            d.cal.div.style.top = '0px';
            d.cal.div.style.visibility = 'hidden';
            obedustart=document.body.appendChild(d.cal.div);
            obedustart.obj.setDTTM($('hedustart').value);
            d.obdt = obedustart;
         } else {
            obedustart = d.obdt;
         }
         if(obedustart.style.visibility=='hidden') {
            obedustart.style.left = (oX(d))+'px';
            obedustart.style.top = (oY(d)+d.offsetHeight)+'px';
            obedustart.style.visibility='visible';
         } else {
            obedustart.obj.hide();
            _destroy(d.cal);
            d.cal = null;
         }
      }
      
      var obedustop = null;
      function setedustop(dt) {
         _gel('edustop_txt').innerHTML = obedustop.obj.toString(obedustop.obj.getResult(),'date');
         _gel('hedustop').value = dt;
      }
      
      function editstopedu(d,e) {
         if(!d.cal) {
            d.cal = new calendarClass('obedustop',setedustop);
            d.cal.notime = true;
            d.cal.div.style.position = 'absolute';
            d.cal.div.style.left = '0px';
            d.cal.div.style.top = '0px';
            d.cal.div.style.visibility = 'hidden';
            obedustop=document.body.appendChild(d.cal.div);
            obedustop.obj.setDTTM($('hedustop').value);
            d.obdt = obedustop;
         } else {
            obedustop = d.obdt;
         }
         if(obedustop.style.visibility=='hidden') {
            obedustop.style.left = (oX(d))+'px';
            obedustop.style.top = (oY(d)+d.offsetHeight)+'px';
            obedustop.style.visibility='visible';
         } else {
            obedustop.obj.hide();
            _destroy(d.cal);
            d.cal = null;
         }
      }
      
      ////////////// date history job
      function hist_setstartjob(dt) {
         _gel('hist_startjob_txt').innerHTML = obdtstart.obj.toString(obdtstart.obj.getResult(),'datetime');
         _gel('hist_hstartjob').value = dt;
      }
      
      function hist_editstartjob(d,e) {
         if(!d.cal) {
            d.cal = new calendarClass('obdtstart',hist_setstartjob);
            d.cal.notime = true;
            d.cal.div.style.position = 'absolute';
            d.cal.div.style.left = '0px';
            d.cal.div.style.top = '0px';
            d.cal.div.style.visibility = 'hidden';
            obdtstart=document.body.appendChild(d.cal.div);
            obdtstart.obj.setDTTM($('hist_hstartjob').value);
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
      
      function hist_setstopjob(dt) {
         _gel('hist_stopjob_txt').innerHTML = obdtstop.obj.toString(obdtstop.obj.getResult(),'datetime');
         _gel('hist_hstopjob').value = dt;
      }
      
      function hist_editstopjob(d,e) {
         if(!d.cal) {
            d.cal = new calendarClass('obdtstop',hist_setstopjob);
            d.cal.notime = true;
            d.cal.div.style.position = 'absolute';
            d.cal.div.style.left = '0px';
            d.cal.div.style.top = '0px';
            d.cal.div.style.visibility = 'hidden';
            obdtstop=document.body.appendChild(d.cal.div);
            obdtstop.obj.setDTTM($('hist_hstopjob').value);
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
      
      ////////////// date history job
      
      
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
            dv = hdr.parentNode.insertBefore(dv,$('caption_jobs').nextSibling);
            cur_edit.dv = dv;
            cur_edit.edit = dv.appendChild(_dce('div'));
            cur_edit.edit.setAttribute('id','jobeditor');
            cur_edit.edit.innerHTML = data[1];
         });
      };
      qjob._send_query = emp_app_searchJob;
      _make_ajax(qjob);
      qjob.focus();
      
      function confirm_add_job(job_id,d,e) {
         emp_app_addJob(job_id,function(_data) {
            if(_data=='DUPLICATE') {
               alert('Fail to assign job. Duplicate active job.');
               return;
            }
            if(_data=='FAIL') {
               alert('Fail to assign job. Unknown reason.');
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
      
      //// job history //////
      
      var hist_edit = null;
      function edit_job_history(history_id,d,e) {
         obdtstart = null;
         obdtstop = null;
         _destroy($('obdtstart'));
         _destroy($('obdtstop'));
         if(hist_edit&&hist_edit.history_id==history_id) {
            _destroy(hist_edit.edit);
            hist_edit.history_id = null;
            hist_edit = null;
            return;
         } else if(hist_edit) {
            _destroy(hist_edit.edit);
         }
         hist_edit = d;
         hist_edit.history_id = history_id;
         hist_edit.dv = _gel('hc_'+history_id);
         hist_edit.edit = hist_edit.dv.appendChild(_dce('div'));
         hist_edit.edit.setAttribute('id','jobeditor_history');
         hist_edit.edit.appendChild(progress_span());
         emp_app_editJobHistory(history_id,function(_data) {
            hist_edit.edit.innerHTML = _data;
         });
      }
      
      function save_job_history(history_id,d,e) {
         var ret = parseForm('frmjobhistory');
         hist_edit.edit.innerHTML = '';
         hist_edit.edit.appendChild(progress_span(' ... saving'));
         obdtstart = null;
         obdtstop = null;
         _destroy($('obdtstart'));
         _destroy($('obdtstop'));
         emp_app_saveJobHistory(history_id,ret,function(_data) {
            _destroy(hist_edit.edit);
            hist_edit = null;
            var data = recjsarray(_data);
            $('history_job_start_'+data[0]).innerHTML = data[1];
            $('history_job_stop_'+data[0]).innerHTML = data[2];
            $('history_job_grade_'+data[0]).innerHTML = data[3];
            $('history_job_assignment_t_'+data[0]).innerHTML = data[4];
         });
      }
      
      function delete_job_history(history_id,d,e) {
         hist_edit.edit.oldHTML = hist_edit.edit.innerHTML;
         hist_edit.edit.innerHTML = '';
         var dv = _dce('div');
         dv.setAttribute('style','margin-top:5px;border:1px solid #aaaaaa;');
         dv.className = 'msg_delete_warn';
         dv.innerHTML = 'Are you sure you want to delete this job history?<br/><br/>'
                      + '<input type=\"button\" value=\""._CANCEL."\" onclick=\"cancel_delete_job_history();\"/>&nbsp;&nbsp;'
                      + '<input type=\"button\" value=\""._DELETE."\" onclick=\"confirm_delete_job_history(\''+history_id+'\');\"/>';
         hist_edit.edit.appendChild(dv);
      }
      
      function cancel_delete_job_history() {
         hist_edit.edit.innerHTML = hist_edit.edit.oldHTML;
      }
      
      function confirm_delete_job_history(history_id) {
         _destroy(_gel('hc_'+history_id));
         hist_edit.history_id = null;
         hist_edit = null;
         emp_app_deleteJobHistory(history_id,null);
      }
      
      
      //// active job /////////////////////////////////////////////////////////////////////////
      
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
            var data = recjsarray(_data);
            $('active_job_'+data[0]).innerHTML = data[1];
            $('active_grade_'+data[0]).innerHTML = data[2];
            $('active_assignment_t_'+data[0]).innerHTML = data[3];
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
      
      function selregion() {
         _gel('regional_cd').value = _regionDiv._resVal;
         _gel('region_txt').innerHTML = _regionDiv._resNam;
      }
      
      function choose_region(d,dvid,fname,regional_cd) {
         if(_regionDiv&&_regionDiv!=$(dvid)) {
            _regionDiv.style.display = 'none';
            _regionDiv = null;
         }
         _regionDiv = _gel(dvid);
         _getRegion(d,fname,regional_cd);
      }
      
      function tmp_selregion() {
         _gel('tmp_regional_cd').value = _regionDiv._resVal;
         _gel('tmp_region_txt').innerHTML = _regionDiv._resNam;
      }
      
      function emergency_selregion() {
         _gel('emergency_regional_cd').value = _regionDiv._resVal;
         _gel('emergency_region_txt').innerHTML = _regionDiv._resNam;
      }
      
      var obdt = null;
      function setDT(dt) {
         obdt.obj.sync==false;
         _gel('birth_dttm_txt').innerHTML = obdt.obj.toString(obdt.obj.getResult(),'datetime');
         SetCookie('dobsync','0');
         _gel('birth_dttm').value = dt;
         SetCookie('dobval',dt);
         emp_app_calcAge(dt,function(_data) {
            $('p_age').innerHTML = _data;
         });
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
            _gel('entrance_dttm_txt').innerHTML = obdtentr.obj.toString(dtstr[0],'date');
         } else {
            _gel('entrance_dttm_txt').innerHTML = obdtentr.obj.toString(obdtentr.obj.getResult(),'date');
         }
         emp_app_setEntranceDTTM(obdtentr.obj.getResult(),function(_data) {
            $('company_length').innerHTML = _data;
         });
         _gel('entrance_dttm').value = dt;
         var dyx = XOCP_CURRENT_DATE.split('-');
      }
      var calentr = new calendarClass('obdtentr',setDTEntr);
      calentr.notime = true;
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
      
      var obdtexit = null;
      function setDTExit(dt) {
         if(obdtexit.obj.sync==true) {
            var dtstr = obdtexit.obj.getResult().split(' ');
            _gel('exit_dttm_txt').innerHTML = obdtexit.obj.toString(dtstr[0],'date');
         } else {
            _gel('exit_dttm_txt').innerHTML = obdtexit.obj.toString(obdtexit.obj.getResult(),'date');
         }
         emp_app_setExitDTTM(obdtexit.obj.getResult(),null);
         _gel('exit_dttm').value = dt;
         var dyx = XOCP_CURRENT_DATE.split('-');
      }
      var calexit = new calendarClass('obdtexit',setDTExit);
      calexit.notime = true;
      calexit.div.style.position = 'absolute';
      calexit.div.style.left = '0px';
      calexit.div.style.top = '0px';
      calexit.div.style.visibility = 'hidden';
      obdtexit=document.body.appendChild(calexit.div);
      obdtexit.obj.setDTTM('$exit_dttm');
      
      function showCalExit(d,e) {
         if(obdtexit.style.visibility=='hidden') {
            obdtexit.style.left = (oX(d))+'px';
            obdtexit.style.top = (oY(d)+d.offsetHeight)+'px';
            obdtexit.style.visibility='visible';
         } else {
            obdtexit.obj.hide();
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
         dv.innerHTML = 'You are about to delete this employee data ( <span style=\"font-weight:bold;\">".htmlentities($_SESSION["hris_employee_person_nm"],ENT_QUOTES)."</span> )?<br/><br/>'
                      + '<img src=\"".XOCP_SERVER_SUBDIR."/modules/hris/thumb.php?pid=$person_id\"/><br/><br/>'
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
         _caf.txt = ' ... deleting';
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
               if(id=='"._HRIS_EMPLOYEE_TAKEN_ID."') {
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
                        qlogin._selectedIndex = qlogin._selectedIndex-1; 
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
      
      var curedu_edit = null;
      function edit_edu(education_seq,d,e) {
         obedustart = null;
         obedustop = null;
         _destroy($('obedustart'));
         _destroy($('obedustop'));
         if(curedu_edit&&curedu_edit.education_seq==education_seq) {
            _destroy(curedu_edit.edit);
            curedu_edit.education_seq = null;
            curedu_edit = null;
            return;
         } else if(curedu_edit) {
            _destroy(curedu_edit.edit);
         }
         curedu_edit = d;
         curedu_edit.education_seq = education_seq;
         curedu_edit.dv = _gel('education_'+education_seq);
         curedu_edit.edit = curedu_edit.dv.appendChild(_dce('div'));
         curedu_edit.edit.setAttribute('id','edueditor');
         curedu_edit.edit.appendChild(progress_span());
         emp_app_editEducation(education_seq,function(_data) {
            curedu_edit.edit.innerHTML = _data;
         });
      }
      
      function save_edu(education_seq,d,e) {
         var ret = parseForm('frmedu');
         if(trim($('education_nm').value)=='') {
            alert('School or College cannot be empty.');
            $('education_nm').focus();
            return;
         }
         curedu_edit.edit.innerHTML = '';
         curedu_edit.edit.appendChild(progress_span(' ... saving'));
         obedustart = null;
         obedustop = null;
         _destroy($('obedustart'));
         _destroy($('obedustop'));
         emp_app_saveEducation(education_seq,ret,function(_data) {
            _destroy(curedu_edit.edit);
            curedu_edit = null;
            var data = recjsarray(_data);
            $('tdedustart_'+data[0]).innerHTML = data[1];
            $('tdedustop_'+data[0]).innerHTML = data[2];
            $('tdeducation_lvl_'+data[0]).innerHTML = data[3];
            $('spedu_'+data[0]).innerHTML = data[4];
         });
      }
      
      function delete_edu(education_seq,d,e) {
         curedu_edit.edit.oldHTML = curedu_edit.edit.innerHTML;
         curedu_edit.edit.innerHTML = '';
         var dv = _dce('div');
         dv.setAttribute('style','margin-top:5px;border:1px solid #aaaaaa;');
         dv.className = 'msg_delete_warn';
         dv.innerHTML = 'Are you sure you want to delete this education history?<br/><br/>'
                      + '<input type=\"button\" value=\""._CANCEL."\" onclick=\"cancel_delete_edu();\"/>&nbsp;&nbsp;'
                      + '<input type=\"button\" value=\""._DELETE."\" onclick=\"confirm_delete_edu(\''+education_seq+'\');\"/>';
         curedu_edit.edit.appendChild(dv);
      }
      
      function cancel_delete_edu() {
         curedu_edit.edit.innerHTML = curedu_edit.edit.oldHTML;
      }
      
      function confirm_delete_edu(education_seq) {
         _destroy(_gel('education_'+education_seq));
         curedu_edit.education_seq = null;
         curedu_edit = null;
         emp_app_deleteEducation(education_seq,null);
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

} // HRIS_EMPLOYEE_DEFINED
