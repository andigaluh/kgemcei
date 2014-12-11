<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_editcustomer.php                //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_EDITCUSTOMERAJAX_DEFINED') ) {
   define('HRIS_EDITCUSTOMERAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");
require_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");

class _hris_class_EditCustomerAjax extends AjaxListener {
   
   function __construct($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/hris/class/ajax_editcustomer.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_editCustomer","app_searchJob","app_deleteCustomer",
                                             "app_addCustomer","app_editJob","app_saveJob","app_generateAssessor");
   }
   
   function app_generateAssessor($args) {
      $db=&Database::getInstance();
      $job_id = $args[0];
      
      $hackdate = getSQLDate();
      
      $asid = 0; ///// 
      
      $sql = "SELECT MAX(asid) FROM ".XOCP_PREFIX."assessment_session WHERE status_cd = 'normal'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($asid)=$db->fetchRow($result);
      }
      
      $arr_provider = array();
      
      $sql = "SELECT employee_id FROM ".XOCP_PREFIX."employee_job WHERE job_id = '$job_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($employee_idx)=$db->fetchRow($result)) {
            $arr_provider[$employee_idx] = 1;
         }
      }
      
      $sql = "SELECT a.job_id,a.job_cd,a.job_nm,"
           . "b.org_nm,c.org_class_nm,a.job_abbr,(d.job_level+0) as srt"
           . " FROM ".XOCP_PREFIX."customer_matrix m"
           . " LEFT JOIN ".XOCP_PREFIX."jobs a ON a.job_id = m.customer_job_id"
           . " LEFT JOIN ".XOCP_PREFIX."orgs b USING(org_id)"
           . " LEFT JOIN ".XOCP_PREFIX."org_class c USING(org_class_id)"
           . " LEFT JOIN ".XOCP_PREFIX."job_class d ON d.job_class_id = a.job_class_id"
           . " WHERE m.provider_job_id = '$job_id' AND a.status_cd = 'normal'"
           . " ORDER BY d.job_class_level, b.org_nm,d.gradeval_bottom DESC,srt DESC,a.job_nm";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($customer_job_id,$customer_job_cd,$customer_job_nm,$customer_org_nm,$customer_org_class_nm,$customer_job_abbr)=$db->fetchRow($result)) {
            $sql = "SELECT employee_id FROM ".XOCP_PREFIX."employee_job WHERE job_id = '$customer_job_id'";
            $resultx = $db->query($sql);
            if($db->getRowsNum($resultx)>0) {
               while(list($employee_idx)=$db->fetchRow($resultx)) {
                  foreach($arr_provider as $provider_employee_id=>$v) {
                     $sql = "insert into hris_assessor_360 values ('$asid','$provider_employee_id','$employee_idx','customer','active','$hackdate','1','0000-00-00 00:00:00','0')";
                     $db->query($sql);
                     _debuglog($sql);
                     _debuglog(mysql_error());
                  }
               }
            }
         }
      }
      return "OK";
   }
   
   function app_saveJob($args) {
      $db=&Database::getInstance();
      $provider_job_id = $args[0];
      $customer_job_id = $args[1];
      $vars = _parseForm($args[2]);
      $customer_type = $vars["customer_type"];
      
      switch($customer_type) {
         case "D":
            $priority_no = 10;
            break;
         case "W":
            $priority_no = 20;
            break;
         case "M":
         default:
            $priority_no = 30;
            break;
      }
      
      $sql = "UPDATE ".XOCP_PREFIX."customer_matrix SET customer_type = '$customer_type', priority_no = '$priority_no'"
           . " WHERE provider_job_id = '$provider_job_id' AND customer_job_id = '$customer_job_id'";
      $db->query($sql);
   }
   
   function app_editJob($args) {
      $db=&Database::getInstance();
      $provider_job_id = $args[0];
      $customer_job_id = $args[1];
      
      $sql = "SELECT customer_type,priority_no FROM ".XOCP_PREFIX."customer_matrix WHERE provider_job_id = '$provider_job_id' AND customer_job_id = '$customer_job_id'";
      $result = $db->query($sql);
      _debuglog($sql);
      list($customer_type,$priority_no)=$db->fetchRow($result);
      $ret = "<div style='padding:10px;' id='frmcustomertype'><div>Customer Type : "
               . "<input type='radio' id='radio_daily' name='customer_type' value='D' ".($customer_type=="D"?"checked='checked'":"")."/> <label for='radio_daily' class='xlnk'>Daily</label>&nbsp;&nbsp;"
               . "<input type='radio' id='radio_weekly' name='customer_type' value='W' ".($customer_type=="W"?"checked='checked'":"")."/> <label for='radio_weekly' class='xlnk'>Weekly</label>&nbsp;&nbsp;"
               . "<input type='radio' id='radio_monthly' name='customer_type' value='M' ".($customer_type=="M"?"checked='checked'":"")."/> <label for='radio_monthly' class='xlnk'>Monthly</label>&nbsp;&nbsp;"
           . "</div>"
           . "<div style='padding:5px;background-color:#ddd;text-align:center;'>"
               . "<input type='button' value='Save' onclick='save_customer_job(\"$provider_job_id\",\"$customer_job_id\",this,event);'/>&nbsp;"
               . "<input type='button' value='Cancel' onclick='cancel_edit_customer_job(\"$provider_job_id\",\"$customer_job_id\",this,event);'/>&nbsp;&nbsp;"
               . "<input type='button' value='Delete' onclick='delete_customer_job(\"$provider_job_id\",\"$customer_job_id\",this,event);'/>"
           . "</div>"
           . "</div>";
      
      return $ret;
   }
   
   function app_addCustomer($args) {
      $db=&Database::getInstance();
      $job_id = $args[0];
      $customer_job_id = $args[1];
      
      if($job_id!=$customer_job_id) {
         $sql = "INSERT INTO ".XOCP_PREFIX."customer_matrix (provider_job_id,customer_job_id,customer_type,priority_no) VALUES ('$job_id','$customer_job_id','M','30')";
         $db->query($sql);
      }
      
      $ret = $this->renderCustomerList($job_id);
      
      $ret .= "<div style='margin-top:10px;background-color:#bbb;padding:5px;border:1px solid #888;-moz-border-radius:5px;text-align:right;'>"
            . "<span id='sp_progress_generate'></span>&nbsp;&nbsp;"
            . "<input type='button' value='Generate Assessor for Current Assessment' onclick='generate_assessor(\"$job_id\",this,event);'/>"
            . "</div>";
      
      
      $sql = "SELECT COUNT(*) FROM ".XOCP_PREFIX."customer_matrix WHERE provider_job_id = '$job_id'";
      $rcnt = $db->query($sql);
      list($customer_count)=$db->fetchRow($rcnt);
      return array($ret,$customer_count);
   }

   function app_deleteCustomer($args) {
      $db=&Database::getInstance();
      $job_id = $args[0];
      $customer_job_id = $args[1];
      
      $sql = "DELETE FROM ".XOCP_PREFIX."customer_matrix WHERE provider_job_id = '$job_id' AND customer_job_id = '$customer_job_id'";
      $db->query($sql);
      
      $sql = "SELECT COUNT(*) FROM ".XOCP_PREFIX."customer_matrix WHERE provider_job_id = '$job_id'";
      $rcnt = $db->query($sql);
      list($customer_count)=$db->fetchRow($rcnt);
      return $customer_count;
   }

   function app_searchJob($args) {
      $db=&Database::getInstance();
      $qstr = trim($args[0]);
      $sql = "SELECT job_nm,job_id,job_cd FROM ".XOCP_PREFIX."jobs"
           . " WHERE job_cd LIKE '$qstr%'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($job_nm,$job_id,$job_cd)=$db->fetchRow($result);
         $ret[] = array("$job_nm [$job_cd]",$job_id);
      }

      $qstr = formatQueryString($qstr);

      $sql = "SELECT a.job_id, a.job_nm, a.job_cd, MATCH (a.job_nm) AGAINST ('$qstr' IN BOOLEAN MODE) as score"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " WHERE MATCH (a.job_nm) AGAINST ('$qstr' IN BOOLEAN MODE)"
           . " ORDER BY score DESC";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         $no = 0;
         while(list($job_id,$job_nm,$job_cd)=$db->fetchRow($result)) {
            if($no >= 1000) break;
            $ret[] = array("$job_nm [$job_cd]",$job_id);
            $no++;
         }
      }
      
      if(count($ret)>0) {
         return $ret;
      } else {
         return "EMPTY";
      }
      
   }
   
   
   function app_editCustomer($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $job_id = $args[0];

      $sql = "SELECT b.org_nm,c.org_class_nm,"
           . "a.description,a.job_nm,a.job_cd,a.job_class_id,"
           . "a.workarea_id,a.location_id,a.org_id,a.job_abbr,a.assessor_job_id,"
           . "a.summary,a.peer_group_id,a.assessment_by_360,a.upper_job_id,"
           . "a.summary_id_txt,a.description_id_txt"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."orgs b USING(org_id)"
           . " LEFT JOIN ".XOCP_PREFIX."org_class c USING(org_class_id)"
           . " WHERE a.job_id = '$job_id'";
      $result = $db->query($sql);
      
      list($org_nm,$org_class_nm,$desc,$job_nm,$job_cd,$job_class_id,$workarea_id,$location_id,
           $org_id,$job_abbr,$assessor_job_id,$summary,$peer_group_id,$by360,$upper_job_id,$summary_id_txt,$desc_id_txt)=$db->fetchRow($result);
      $job_nm = stripslashes(htmlentities($job_nm,ENT_QUOTES));
      $job_cd = stripslashes(htmlentities($job_cd,ENT_QUOTES));
      $job_abbr = stripslashes(htmlentities($job_abbr,ENT_QUOTES));
      
      $ret = $this->renderJob($job_id,$job_nm,$job_abbr,$org_class_nm,$org_nm);
      $ret .= "<div id='jobeditor' style='padding:10px;padding-left:20px;'>";
      
      $ret .= $this->renderCustomerList($job_id);
      
      $ret .= "<div style='margin-top:10px;background-color:#bbb;padding:5px;border:1px solid #888;-moz-border-radius:5px;text-align:right;'>"
            . "<span id='sp_progress_generate'></span>&nbsp;&nbsp;"
            . "<input type='button' value='Generate Assessor for Current Assessment' onclick='generate_assessor(\"$job_id\",this,event);'/>"
            . "</div>";
      
      $ret .= "</div>"; /// jobeditor
      
      
      return array($ret,$job_id);
   }
   
   function renderCustomerList($job_id) {
      $db=&Database::getInstance();
      
      $employee_list = "";
      $sql = "SELECT employee_id FROM ".XOCP_PREFIX."employee_job WHERE job_id = '$job_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($employee_idx)=$db->fetchRow($result)) {
            list($j,$jj,$jjj,$nm) = _hris_getinfobyemployeeid($employee_idx);
            $employee_list .= "<div style='padding:5px;font-style:italic;'>$nm</div>";
         }
      } else {
            $employee_list .= "<div style='padding:3px;color:red;font-style:italic;'>"._EMPTY."</div>";
      }
      
      
      $sql = "SELECT a.job_id,a.job_cd,a.job_nm,"
           . "b.org_nm,c.org_class_nm,a.job_abbr,(d.job_level+0) as srt"
           . " FROM ".XOCP_PREFIX."customer_matrix m"
           . " LEFT JOIN ".XOCP_PREFIX."jobs a ON a.job_id = m.customer_job_id"
           . " LEFT JOIN ".XOCP_PREFIX."orgs b USING(org_id)"
           . " LEFT JOIN ".XOCP_PREFIX."org_class c USING(org_class_id)"
           . " LEFT JOIN ".XOCP_PREFIX."job_class d ON d.job_class_id = a.job_class_id"
           . " WHERE m.provider_job_id = '$job_id' AND a.status_cd = 'normal'"
           . " ORDER BY d.job_class_level, b.org_nm,d.gradeval_bottom DESC,srt DESC,a.job_nm";
      
      
      $ret = "<div style='padding:10px;-moz-box-shadow:inset 0px 0px 3px #000;background-color:#ddd;-moz-border-radius:5px;'>"
           . "<div style='background-color:#777;padding:5px;font-weight:bold;color:#fff;'>Employee List"
           . "</div>"
           . "<div style='border:1px solid #bbb;background-color:#eee;'>$employee_list</div>"
           . "<br/>"
           . "<div style='background-color:#777;padding:5px;font-weight:bold;color:#fff;'>"
           . "<table style='border-spacing:0;width:100%;'><tbody><tr><td>Customer List</td><td style='text-align:right;'>"
           . "<div style='font-weight:normal;'><input id='qcustomer' type='text' class='searchBox' onclick='init_q_customer(this,event);'/></div>"
           . "</td></tr></tbody></table>"
           . "</div>";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($customer_job_id,$customer_job_cd,$customer_job_nm,$customer_org_nm,$customer_org_class_nm,$customer_job_abbr)=$db->fetchRow($result)) {
            $ret .= "<div id='dvcustomer_${customer_job_id}'>"
                  . $this->renderCustomer($customer_job_id,$customer_job_nm,$customer_job_abbr,$customer_org_class_nm,$customer_org_nm)
                  . "</div>";
         
         }
      } else {
         $ret .= "<div style='text-align:center;font-style:italic;color:#888;padding:10px;'>"._EMPTY."</div>";
      }
      $ret .= "</div>";
      return $ret;
   }
   
   function renderCustomer($job_id,$job_nm,$job_abbr,$org_class_nm,$org_nm) {
      $db=&Database::getInstance();
      $customer_employee_list = "";
      $sql = "SELECT employee_id FROM ".XOCP_PREFIX."employee_job WHERE job_id = '$job_id'";
      $resultx = $db->query($sql);
      if($db->getRowsNum($resultx)>0) {
         while(list($employee_idx)=$db->fetchRow($resultx)) {
            list($j,$jj,$jjj,$nm) = _hris_getinfobyemployeeid($employee_idx);
            $customer_employee_list .= "<div style='padding:3px;'>$nm</div>";
         }
      } else {
            $customer_employee_list .= "<div style='padding:3px;color:red;'>"._EMPTY."</div>";
      }
            
      $ret = "<table style='border:0px;width:100%;'>"
                  . "<colgroup><col width='100'/><col/><col width='200'/></colgroup><tbody>"
                  . "<tr><td style='vertical-align:top;'>$job_abbr</td>"
                      . "<td style='vertical-align:top;'>"
                      . "<div style='overflow:hidden;width:300px;'><div style='width:900px;'><span id='spcustomer_${job_id}' class='xlnk' onclick='edit_customer(\"$job_id\",this,event);'>".stripslashes($job_nm)."</span></div></div>"
                      . "<div style='margin-top:10px;-moz-border-radius:3px;border:1px solid #bbb;background-color:#fff;padding:2px;font-style:italic;font-size:0.9em;overflow:hidden;width:300px;'><div style='width:900px;'>$customer_employee_list</div></div>"
                      . "</td>"
                      . "<td style='vertical-align:top;'><div style='overflow:hidden;width:200px;'><div style='width:900px;' id='dvocustomer_${job_id}'>".htmlentities("$org_nm $org_class_nm",ENT_QUOTES)."</div></div></td>"
                  . "</tr></tbody></table>";
      return $ret;
   }
   
   function renderJob($job_id,$job_nm,$job_abbr,$org_class_nm,$org_nm) {
      $db=&Database::getInstance();
      $sql = "SELECT COUNT(*) FROM ".XOCP_PREFIX."customer_matrix WHERE provider_job_id = '$job_id'";
      $rcnt = $db->query($sql);
      list($customer_count)=$db->fetchRow($rcnt);
      $ret = "<a name='jobeditortop_${job_id}'></a><table style='border:0px;width:100%;'>"
                  . "<colgroup><col width='100'/><col/><col width='200'/></colgroup><tbody>"
                  . "<tr><td>$job_abbr</td>"
                      . "<td><div style='overflow:hidden;width:400px;'><div style='width:900px;'><span id='sp_${job_id}' class='xlnk' onclick='edit_job(\"$job_id\",this,event);'>".stripslashes($job_nm)."</span> (<span id='spcustomercount_${job_id}'>$customer_count</span>)</div></div></td>"
                      . "<td><div style='overflow:hidden;width:200px;'><div style='width:900px;' id='dvo_${job_id}'>".htmlentities("$org_nm $org_class_nm",ENT_QUOTES)."</div></div></td>"
                  . "</tr></tbody></table>";
      return $ret;
   }
   
}

} /// HRIS_EDITCUSTOMERAJAX_DEFINED
?>