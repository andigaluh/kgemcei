<?php
//--------------------------------------------------------------------//
// Filename : modules/pmx/ajax/ajax_sms.php                //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('SMS_AJAX_DEFINED') ) {
   define('SMS_AJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");

require_once(XOCP_DOC_ROOT."/gakruwetxocp.php");

include_once(XOCP_DOC_ROOT."/modules/sms/smsxocp.php");

class _sms_class_Ajax extends AjaxListener {
   
   function _sms_class_Ajax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/sms/ajax/ajax_sms.php";
      $this->init();
      parent::init();
      $this->setReqPOST();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_smsAjax");
   }
   
   function app_smsAjax($args) {
		$db=&Database::getInstance();
		if ($args[0] == "Perspective") return SMSPerspective::getEditor(_parseForm($args[1]));
		else if ($args[0] == "Objective") return SMSObjective::getEditor(SMSUnit::$currentId, _parseForm($args[1]));
		else if ($args[0] == "Indicator") return SMSIndicator::getEditor(SMSUnit::$currentId, _parseForm($args[1]));
		else if ($args[0] == "ObjectivePerspective") return SMSObjectivePerspective::getEditor(SMSUnit::$currentId, _parseForm($args[1]));
   }

}

} /// SMS_PERSPECTIVEAJAX_DEFINED

?>