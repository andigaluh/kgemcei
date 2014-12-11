<?php
//--------------------------------------------------------------------//
// Filename : modules/pmx/ajax/ajax_pms.php                //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('PMS_AJAX_DEFINED') ) {
   define('PMS_AJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");

require_once(XOCP_DOC_ROOT."/gakruwetxocp.php");

include_once(XOCP_DOC_ROOT."/modules/pms/pmsxocp.php");

class _pms_class_Ajax extends AjaxListener {
   
   function _pms_class_Ajax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/pms/ajax/ajax_pms.php";
      $this->init();
      parent::init();
      $this->setReqPOST();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_pmsAjax");
   }
   
   function app_pmsAjax($args) {
		$db=&Database::getInstance();
		if ($args[0] == "Perspective") return PMSPerspective::getEditor(_parseForm($args[1]));
		else if ($args[0] == "Objective") return PMSObjective::getEditor(PMSUnit::$currentId, _parseForm($args[1]));
		else if ($args[0] == "Indicator") return PMSIndicator::getEditor(PMSUnit::$currentId, _parseForm($args[1]));
		else if ($args[0] == "ObjectivePerspective") return PMSObjectivePerspective::getEditor(PMSUnit::$currentId, _parseForm($args[1]));
   }

}

} /// PMS_PERSPECTIVEAJAX_DEFINED

?>