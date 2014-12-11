<?php
//--------------------------------------------------------------------//
// Filename : modules/sms/smscorporatevision.php                     //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2013-10-10                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('SMS_SMSOBJ_DEFINED') ) {
   define('SMS_SMSOBJ_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/antrain/modconsts.php");

class _sms_SMSObj extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _SMS_SMSCORPORATEVISION_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = 'SMS Corporate Vision';
   var $display_comment = TRUE;
   var $data;
   
   function __construct($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function smscorporatevision() {
   	$db=&Database::getInstance();
   	$smsselobj = new _sms_class_SelectSession();
   	$smssel = "<div style='padding-bottom:2px;'>".$pmsselobj->show()."</div>";

   	if(!isset($_SESSION["sms_psid"])||$_SESSION["sms_psid"]==0) {
        return $smssel;
    }


   }
   
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $this->listSession();
            break;
         default:
            $ret = $this->listSession();
            break;
      }
      $sqljob = "SELECT j.job_id FROM hris_employee_job j LEFT JOIN hris_users u ON (u.person_id = j.employee_id) WHERE u.user_id = ".getUserid()."";
	$resultjob = $db->query($sqljob);
	 list($job_id)=$db->fetchRow($resultjob);
	//echo 'getuserid = '.getuserid(). '<br> job_id = '.$job_id; exit();
	 	 if ($job_id ==  130 || $job_id == 90 || $job_id == 68 || $job_id == 101 || $job_id == 0){
      return $ret;
	  }
	  else
	  {
	  return 'you have no access';
}
   }
}

} 
?>