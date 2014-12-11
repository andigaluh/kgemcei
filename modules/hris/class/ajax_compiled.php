<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_recalcass.php              //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_REPORTCOMPILEDAJAX_DEFINED') ) {
   define('HRIS_REPORTCOMPILEDAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");
require_once(XOCP_DOC_ROOT."/modules/hris/include/assessment.php");

class _hris_class_CompiledReportAjax extends AjaxListener {
   
   function __construct($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/hris/class/ajax_compiled.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_viewDetail");
   }
   
   function app_viewDetail($args) {
      $db=&Database::getInstance();
      $db0 = $_SESSION["db0"];
      $db1 = $_SESSION["db1"];
      $employee_id = $args[0];
      $job_id = $args[1];
      
      $diff0 = array();
      $diff1 = array();
      
      $ret = "<table class='xxlist'><thead><tr><td>Competency</td><td>Abbr.</td><td>CCL0</td><td>CCL1</td></thead><tbody>";
      
      $sql0 = "SELECT b.competency_abbr,a.cclxxx,a.competency_id"
           . " FROM ${db0}.".XOCP_PREFIX."employee_competency_final a"
           . " LEFT JOIN ${db0}.".XOCP_PREFIX."competency b USING(competency_id)"
           . " WHERE a.employee_id = '$employee_id'"
           . " AND a.asid = '10'"
           . " AND a.job_id = '$job_id'";
      $rcf = $db->query($sql0);
      if($db->getRowsNum($rcf)>0) {
         while(list($cabr,$cclxxx,$competency_id0)=$db->fetchRow($rcf)) {
            $diff0[$competency_id0] = array($cclxxx,$cabbr);
            $sql1 = "SELECT b.competency_nm,b.competency_abbr,a.cclxxx,a.competency_id"
                 . " FROM ${db1}.".XOCP_PREFIX."employee_competency_final a"
                 . " LEFT JOIN ${db1}.".XOCP_PREFIX."competency b USING(competency_id)"
                 . " WHERE a.employee_id = '$employee_id'"
                 . " AND a.asid = '10'"
                 . " AND a.job_id = '$job_id'"
                 . " AND a.competency_id = '$competency_id0'";
            $rcf1 = $db->query($sql1);
            if($db->getRowsNum($rcf1)>0) {
               list($cnm,$cabr1,$cclxxx1,$competency_id1)=$db->fetchRow($rcf1);
               if($cclxxx!=$cclxxx1) {
                  _debuglog($sql0);
                  _debuglog($sql1);
                  $diff1[$competency_id0] = array($cclxxx1,$cabbr1);
                  $ret .= "<tr><td>$cnm</td><td>$cabr1</td><td>$cclxxx</td><td>$cclxxx1</td></tr>";
               }
            }
         }
      }
      $ret .= "</tbody></table>";
      
      return array($employee_id,$job_id,$ret);
      
   }
 
   
   
}

} /// HRIS_REPORTCOMPILEDAJAX_DEFINED
?>