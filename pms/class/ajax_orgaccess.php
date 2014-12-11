<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_orgaccess.php                    //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2005-11-19                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_CLASS_AJAXORGADDACCESS_DEFINED') ) {
   define('HRIS_CLASS_AJAXORGADDACCESS_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");

class _hris_class_OrgAccessAjax extends AjaxListener {
   
   function __construct($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/pms/class/ajax_orgaccess.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_findEmployeeJob","app_addAccess",
                            "app_delAccess");
   }
   
   function app_delAccess($args) {
      $db=&Database::getInstance();
      $access_id = $args[0];
      $user_id = getUserID();
      
      $sql = "UPDATE pms_org_access SET status_cd = 'nullified', nullified_dttm = now(), nullified_user_id = '$user_id'"
           . " WHERE access_id = '$access_id'";
      $db->query($sql);
      
      $sql = "SELECT pms_org_id FROM pms_org_access"
           . " WHERE access_id = '$access_id'";
      $result = $db->query($sql);
      list($org_id)=$db->fetchRow($result);
      
      
      return $this->renderAccess($org_id);
      
   }
   
   function app_findEmployeeJob($args) {
      $db=&Database::getInstance();
      list($qstr,$org_id) = explode("##",$args[0]);
      
      $qstr0 = $qstr;
      
      $sql = "SELECT b.employee_id,b.employee_ext_id,a.person_nm,a.person_id"
           . " FROM ".XOCP_PREFIX."persons a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(person_id)"
           . " WHERE b.employee_ext_id LIKE '".addslashes($qstr)."%'"
           . " AND b.person_id IS NOT NULL"
           . " AND a.status_cd = 'normal'"
           . " GROUP BY a.person_id"
           . " ORDER BY b.employee_ext_id";
      $result = $db->query($sql);
      $ret = array();
      if($db->getRowsNum($result)>0) {
         $no = 0;
         while(list($employee_id,$employee_ext_id,$employee_nm,$person_id)=$db->fetchRow($result)) {
            if($no >= 1000) break;
            $ret[$employee_id] = array("$employee_nm ($employee_ext_id)",$person_id,$employee_id,"person");
            $no++;
         }
      }
      
      $sql = "SELECT b.employee_id,b.employee_ext_id,a.person_nm,a.person_id"
           . " FROM ".XOCP_PREFIX."persons a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(person_id)"
           . " WHERE a.person_nm LIKE '%".addslashes($qstr)."%'"
           . " AND b.person_id IS NOT NULL"
           . " AND a.status_cd = 'normal'"
           . " GROUP BY a.person_id"
           . " ORDER BY b.employee_ext_id";
      $result = $db->query($sql);
      $ret = array();
      if($db->getRowsNum($result)>0) {
         $no = 0;
         while(list($employee_id,$employee_ext_id,$employee_nm,$person_id)=$db->fetchRow($result)) {
            if($no >= 1000) break;
            $ret[$employee_id] = array("$employee_nm ($employee_ext_id)",$person_id,$employee_id,"person");
            $no++;
         }
      }
      
      $qstr = ereg_replace("[[:space:]]+"," ",trim(strtolower($qstr)));
      
      $qstr = formatQueryString($qstr);
      
      $sql = "SELECT b.employee_id,b.employee_ext_id,a.person_nm,a.person_id, MATCH (a.person_nm) AGAINST ('".addslashes($qstr)."' IN BOOLEAN MODE) as score"
           . " FROM ".XOCP_PREFIX."persons a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(person_id)"
           . " WHERE MATCH (a.person_nm) AGAINST ('".addslashes($qstr)."' IN BOOLEAN MODE)"
           . " AND b.person_id IS NOT NULL"
           . " AND a.status_cd = 'normal'"
           . " GROUP BY a.person_id"
           . " ORDER BY score DESC";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         $no = 0;
         while(list($employee_id,$employee_ext_id,$employee_nm,$person_id)=$db->fetchRow($result)) {
            if($no >= 1000) break;
            $ret[$employee_id] = array("$employee_nm ($employee_ext_id)",$person_id,$employee_id,"person");
            $no++;
         }
      }
      
      
      $sql = "SELECT job_nm,job_id,job_cd FROM ".XOCP_PREFIX."jobs"
           . " WHERE job_cd LIKE '$qstr0%'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($job_nm,$job_id,$job_cd)=$db->fetchRow($result)) {
            $ret["job_${job_id}"] = array("$job_nm [$job_cd]",$job_id,0,"job");
         }
      }

      $sql = "SELECT job_nm,job_id,job_cd FROM ".XOCP_PREFIX."jobs"
           . " WHERE job_nm LIKE '%$qstr0%'";
      $result = $db->query($sql);
      _debuglog($sql);
      if($db->getRowsNum($result)>0) {
         while(list($job_nm,$job_id,$job_cd)=$db->fetchRow($result)) {
            $ret["job_${job_id}"] = array("$job_nm [$job_cd]",$job_id,0,"job");
         }
      }

      $sql = "SELECT a.job_id, a.job_nm, a.job_cd, MATCH (a.job_nm) AGAINST ('$qstr' IN BOOLEAN MODE) as score"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " WHERE MATCH (a.job_nm) AGAINST ('$qstr' IN BOOLEAN MODE)"
           . " ORDER BY score DESC";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         $no = 0;
         while(list($job_id,$job_nm,$job_cd)=$db->fetchRow($result)) {
            if($no >= 1000) break;
            $ret["job_${job_id}"] = array("$job_nm [$job_cd]",$job_id,0,"job");
            $no++;
         }
      }
      
      if(count($ret)>0) {
         $xret = array();
         foreach($ret as $employee_id=>$v) {
            $xret[] = $v;
         }
         return $xret;
      } else {
         return "EMPTY";
      }
      
      
   }
   
   function renderAccess($org_id) {
      $db=&Database::getInstance();
      $psid = $_SESSION["pms_psid"];
      $sql = "SELECT a.access_id,a.employee_id,a.job_id,"
           . "c.person_nm,b.employee_ext_id,d.job_nm,d.job_abbr"
           . " FROM pms_org_access a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " LEFT JOIN ".XOCP_PREFIX."jobs d ON d.job_id = a.job_id"
           . " WHERE a.pms_org_id = '$org_id'"
           . " AND a.psid = '$psid'"
           . " AND a.status_cd = 'normal'";
      $result = $db->query($sql);
      $ret = "";
      if($db->getRowsNum($result)>0) {
         while(list($access_id,$employee_id,$job_id,$employee_nm,$nip,$job_nm,$job_abbr)=$db->fetchRow($result)) {
            if($employee_id>0) {
               $ret .= ", <span onclick='pms_org_del_access(\"$org_id\",\"$access_id\",this,event);' class='xlnk'>$employee_nm [$nip]</span>";
            } else {
               $ret .= ", <span onclick='pms_org_del_access(\"$org_id\",\"$access_id\",this,event);' class='xlnk'>$job_nm</span>";
            }
         }
         $ret = substr($ret,2);
      } else {
         $ret = "<span style='font-style:italic;color:#777;'>"._EMPTY."</span>";
      }
      return $ret;
   }
   
   function app_addAccess($args) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      _dumpvar($args);
      $db=&Database::getInstance();
      $user_id = getUserID();
      $org_id = $args[0];
      $t = $args[4];
      $psid = $_SESSION["pms_psid"];
      
      if($t=="person") {
         $employee_id = $args[3];
         $sql = "SELECT access_id FROM pms_org_access"
              . " WHERE pms_org_id = '$org_id'"
              . " AND employee_id = '$employee_id'"
              . " AND psid = '$psid'"
              . " AND status_cd = 'normal'";
         $result = $db->query($sql);
         if($db->getRowsNum($result)==0) {
            list($emp_job_id,
                 $emp_employee_id,
                 $emp_job_nm,
                 $emp_nm,
                 $emp_nip,
                 $emp_gender,
                 $emp_jobstart,
                 $emp_entrance_dttm,
                 $emp_jobage,
                 $emp_job_summary,
                 $emp_person_id,
                 $emp_user_id)=_hris_getinfobyemployeeid($employee_id);
            $sql = "INSERT INTO pms_org_access (psid,pms_org_id,employee_id,job_id,created_user_id)"
                 . " VALUES ('$psid','$org_id','$employee_id','$emp_job_id','$user_id')";
            $db->query($sql);
            _debuglog($sql);
         }
      } else {
         $job_id = $args[1];
         
         $sql = "INSERT INTO pms_org_access (psid,pms_org_id,employee_id,job_id,created_user_id)"
              . " VALUES ('$psid','$org_id','0','$job_id','$user_id')";
         $db->query($sql);
         _debuglog($sql);
      }
      
      return $this->renderAccess($org_id);
      
      
   }
   
   
}

} /// HRIS_CLASS_AJAXORGADDACCESS_DEFINED
?>