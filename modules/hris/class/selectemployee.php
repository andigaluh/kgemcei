<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/selectemployee.php                    //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2002-07-02                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_CLASS_SELECTEMPLOYEE_DEFINED') ) {
   define('HRIS_CLASS_SELECTEMPLOYEE_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/system/class/gis_region.php");

class _hris_class_SelectEmployee extends XocpAttachable {
   var $prefix = "slemp_";
   var $attr;
   var $btn_new = FALSE;
   
   function _hris_class_SelectEmployee() {
      $this->setURLParam(XOCP_SERVER_SUBDIR."/index.php",NULL);
      $this->attr = array("nm","mrn","searchemployee","f","p","ch","selectemp","empperson_id","new_emp");
   }
   
   function getPrefix() {
      return $this->prefix;
   }
   
   function searchForm() {
      $db=&Database::getInstance();
      
      if($this->btn_new) {
         $btn_new = "&nbsp;<input type='button' value='"._NEW."' onclick='new_employee(this,event);'/>";
      } else {
         $btn_new = "";
      }
      
      $ret = "<table class='tblfrm'>"
           . "<tr><td class='tblfrmtitle' colspan='2'>Find Personnel</td></tr>"
           . "<tr><td class='tblfrmfieldname'>Name/ID</td><td class='tblfrmfieldvalue'>"
           . "<input type='text' style='width:300px;' id='qemp'/></td></tr>"
           . "<tr><td class='tblfrmbuttons' colspan='2'>"
           . "<input type='button' value='"._SEARCH."' onclick='search_employee(this,event);'/>"
           . $btn_new
           . "</td></tr>"
           . "</table>";
      
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_employee.php");
      $ajax = new _hris_class_EmployeeAjax("empajx");
      $ret .= $ajax->getJs() . "
      <script type='text/javascript' language='javascript'><!--
      ajax_feedback = null;
      var qemp = _gel('qemp');
      qemp._get_param=function() {
         var qval = this.value;
         qval = trim(qval);
         if(qval.length < 2) {
            return '';
         }
         return qval;
      };
      qemp._onselect=function(resId) {
         window.location = '".$this->getURL()."?".$this->getURLParam()."&".$this->getPrefix()."selectemp='+resId;
      };
      qemp._send_query = empajx_app_searchEmployee;
      _make_ajax(qemp);
      qemp.focus();
      
      function search_employee(d,e) {
         qemp._query();
         qemp.focus();
      }
      
      function new_employee(d,e) {
         window.location = '".$this->getURL()."?".$this->getURLParam()."&".$this->getPrefix()."new_emp=1';
      }
      
      // --></script>";
      
      return $ret;
   }
   


   function showEmployee() {
      include_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_employee.php");
      $db =& Database::getInstance();
      $sql = "SELECT p.person_nm,emp.employee_ext_id"
           . " FROM ".XOCP_PREFIX."employee emp"
           . " LEFT JOIN ".XOCP_PREFIX."persons p USING (person_id)"
           . " WHERE emp.person_id = '".$_SESSION["hris_employee_person_id"]."'";
      $result = $db->query($sql);
      list($employee_nm,$employee_ext_id) = $db->fetchRow($result);
      $_SESSION["hris_employee_person_nm"] = $employee_nm;
      
      if(trim($employee_ext_id)=="") {
         $employee_ext_id = "-";
      }
      
      $ajax = new _hris_class_EmployeeAjax("emp");
      $js = $ajax->getJs()."<script type='text/javascript'><!--
      
      function toggle_v_employee(d,e) {
         if($('dvshowemp').style.display=='none') {
         
         } else {
         
         }
      }
      
      // --></script>";
      
      return $js."<div class='empl'><table>
              <tr><td>[ $employee_ext_id ] <span class='xlnk' style='font-weight:bold;' onclick='toggle_v_employee(this,event);'>$employee_nm</span></td>
              <td align='right'>[<a href='".$this->getURL()."?".$this->getURLParam()
              ."&amp;".$this->getPrefix()."ch=y'>"._HRIS_EMPLOYEESELECT
              ."</a>]</td></tr></table><div id='dvshowemp' style='background-color:#ffffff;padding:4px;margin-top:4px;display:none;'>asdf</div></div>";
   }

   function show() {
   
      foreach($this->attr as $k) {
         $fkey = $this->prefix . $k;
         if(isset($_GET[$fkey])) {
            $this->data[$k] = $_GET[$fkey];
         }
      }

      if (isset($this->data["selectemp"]) && $this->data["selectemp"] != "") {
         $db=&Database::getInstance();
         $person_idx = $this->data["selectemp"];
         
         $sql = "SELECT employee_id,person_id FROM ".XOCP_PREFIX."employee WHERE person_id = '$person_idx'";
         $result = $db->query($sql);
         if($db->getRowsNum($result)>0) {
            list($employee_id,$person_id)=$db->fetchRow($result);
            $_SESSION["hris_employee_id"] = $employee_id;
            $_SESSION["hris_employee_person_id"] = $person_id;
            $ret = $this->showEmployee();
         } else {
            $ret = $this->searchForm();
         }
      } elseif (isset($this->data["ch"]) && $this->data["ch"] == "y") {
         $_SESSION["hris_employee_id"] = 0;
         $_SESSION["hris_employee_person_id"] = 0;
         $_SESSION["hris_mr_admission_id"] = "";
         $ret = $this->searchForm();
      } else {
         if (!isset($_SESSION["hris_employee_id"])) {
            $_SESSION["hris_employee_id"] = 0;
         }
         if (!isset($_SESSION["hris_employee_person_id"])) {
            $_SESSION["hris_employee_person_id"] = 0;
         }
         if($_SESSION["hris_employee_id"] == 0) {
            if($this->data["new_emp"]==1) {
               $ret = "";
            } else {
               $ret = $this->searchForm();
            }
         } else {
            $ret = $this->showEmployee();
         }
      }
      return $ret;
   }
}

} // HRIS_CLASS_SELECTEMPLOYEE_DEFINED
?>