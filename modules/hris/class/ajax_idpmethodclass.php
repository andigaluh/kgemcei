<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_idpmethodclass.php                //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_JOBCLASSAJAX_DEFINED') ) {
   define('HRIS_JOBCLASSAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");

class _hris_class_IDPMethodClassAjax extends AjaxListener {
   
   function _hris_class_IDPMethodClassAjax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/hris/class/ajax_idpmethodclass.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_Delete","app_editClass","app_saveClass",
                            "app_resetSuperior");
   }
   
   function getOrgsUp($org_id) {
      $db=&Database::getInstance();
      $sql = "SELECT parent_id FROM ".XOCP_PREFIX."orgs WHERE org_id = '$org_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($parent_id)=$db->fetchRow($result);
         if($parent_id>0) {
            $_SESSION["hris_org_parents"][] = $parent_id;
            $this->getOrgsUp($parent_id);
         }
      }
   }
   
   function app_resetSuperior($args) {
      $db=&Database::getInstance();
      $method_t = $args[0];
      $upper_job_class = $args[1];
      $sql = "UPDATE ".XOCP_PREFIX."idp_development_method_type SET upper_job_class = '$upper_job_class' WHERE method_t = '$method_t'";
      $db->query($sql);
      $sql = "SELECT job_id,org_id FROM ".XOCP_PREFIX."jobs"
           . " WHERE method_t = '$method_t'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($job_id,$org_id)=$db->fetchRow($result)) {
            $_SESSION["hris_org_parents"] = array();
            $_SESSION["hris_org_parents"][] = $org_id;
            $this->getOrgsUp($org_id);
            
            if($job_id==147) {
               _debuglog("Adm Div Man");
               _dumpvar($_SESSION["hris_org_parents"]);
            }
            foreach($_SESSION["hris_org_parents"] as $org_idx) {
               $sql = "SELECT job_id FROM ".XOCP_PREFIX."jobs"
                    . " WHERE method_t = '$upper_job_class'"
                    . " AND org_id = '$org_idx'"
                    . " ORDER BY job_id";
               if($job_id==147) {
                  _debuglog($sql);
               }
               $rup = $db->query($sql);
               if($db->getRowsNum($rup)>0) {
                  list($upper_job_id)=$db->fetchRow($rup);
                  $sql = "UPDATE ".XOCP_PREFIX."jobs SET upper_job_id = '$upper_job_id' WHERE job_id = '$job_id'";
                  $db->query($sql);
                  break;
               }
            }
         }
      }
      return "OK";
   }
   
   function app_saveClass($args) {
      $db=&Database::getInstance();
      $method_t = $args[0];
      $method_type = addslashes(trim($args[1]));
      
      $sql = "SELECT * FROM ".XOCP_PREFIX."idp_development_method_type WHERE method_t = '$method_t'";
      $result = $db->query($sql);
      
      if($db->getRowsNum($result)==0) {
         $sql = "INSERT INTO ".XOCP_PREFIX."idp_development_method_type (method_t,method_type)"
              . " VALUES('$method_t','$method_type')";
         $db->query($sql);
      } else {
         $sql = "UPDATE ".XOCP_PREFIX."idp_development_method_type SET "
              . "method_type = '$method_type'"
              . " WHERE method_t = '$method_t'";
         $db->query($sql);
      }
      
      $ret = "<table border='0' class='ilist'>"
           . "<colgroup><col width='100'/><col/></colgroup>"
           . "<tbody><tr>"
           . "<td>$method_t</td>"
           . "<td><span id='sp_${method_t}' class='xlnk' onclick='edit_class(\"$method_t\",this,event);'>".htmlentities(stripslashes($method_type))."</span></td>"
           . "</tr></tbody></table>";
      
      return array("tdclass_${method_t}",$ret);
   }
   
   function app_editClass($args) {
      $db=&Database::getInstance();
      $method_t = $args[0];
      if($method_t=="new") {
         $bypeer = 1;
         $bycustomer = 1;
         $bysubordinate = 1;
         $method_t_val = "";
      } else {
         $sql = "SELECT method_t,method_type"
              . " FROM ".XOCP_PREFIX."idp_development_method_type"
              . " WHERE method_t = '$method_t'";
         $result = $db->query($sql);
         list($method_t,$method_type)=$db->fetchRow($result);
         $method_type = htmlentities($method_type,ENT_QUOTES);
         $method_t_val = $method_t;
      }
      
      $ret = "<form id='frm'><table class='xxfrm' style='width:100%;'>"
           . "<colgroup><col width='220'/><col/></colgroup>"
           . "<tbody>"
           . "<tr><td>Method Type ID</td><td><input ".($method_t=="new"?"":"disabled='1'")." type='text' value=\"$method_t_val\" id='inp_method_t' name='method_t' style='width:100px;'/></td></tr>"
           . "<tr><td>Method Type Name</td><td><input type='text' value=\"$method_type\" id='inp_method_type' name='method_type' style='width:90%;'/></td></tr>"
           . "<tr><td colspan='2'><input onclick='save_class();' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input onclick='cancel_edit();' type='button' value='"._CANCEL."'/>"
           . "&nbsp;&nbsp;" . ($method_t!="new"?"<input onclick='delete_class();' type='button' value='"._DELETE."'/>":"")
           . "</td></tr>"
           . "</tbody></table></form>";
      return $ret;
   }
   
   function app_Delete($args) {
      $db=&Database::getInstance();
      $method_t = $args[0];
      $sql = "DELETE FROM ".XOCP_PREFIX."idp_development_method_type WHERE method_t = '$method_t'";
      $db->query($sql);
   }
   
}

} /// HRIS_JOBCLASSAJAX_DEFINED
?>