<?php
//--------------------------------------------------------------------//
// Filename : modules/pmx/ajax/ajax_antrainplan.php                //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('antrainplan_AJAX_DEFINED') ) {
   define('antrainplan_AJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");

require_once(XOCP_DOC_ROOT."/gakruwetxocp.php");

include_once(XOCP_DOC_ROOT."/modules/antrainplan/antrainplanxocp.php");

class _antrainplan_class_Ajax extends AjaxListener {
   
   function _antrainplan_class_Ajax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/antrainplan/ajax/ajax_antrainplan.php";
      $this->init();
      parent::init();
      $this->setReqPOST();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_antrainplanAjax");
   }
   
   function app_antrainplanAjax($args) {
		$db=&Database::getInstance();
		if ($args[0] == "Perspective") return antrainplanPerspective::getEditor(_parseForm($args[1]));
		else if ($args[0] == "Objective") return antrainplanObjective::getEditor(antrainplanUnit::$currentId, _parseForm($args[1]));
		else if ($args[0] == "Indicator") return antrainplanIndicator::getEditor(antrainplanUnit::$currentId, _parseForm($args[1]));
		else if ($args[0] == "ObjectivePerspective") return antrainplanObjectivePerspective::getEditor(antrainplanUnit::$currentId, _parseForm($args[1]));
   }

}

} /// antrainplan_PERSPECTIVEAJAX_DEFINED

?>