
   <?php
//--------------------------------------------------------------------//
// Filename : modules/antrain/antrainplan.php                         //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2010-09-22                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('ANTRAIN_PLAN_DEFINED') ) {
   define('ANTRAIN_PLAN_DEFINED', TRUE);

//include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
//include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
include_once(XOCP_DOC_ROOT."/modules/antrain/class/ajax_objective.php");
require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
include_once(XOCP_DOC_ROOT."/modules/antrain/class/selectantrain.php");

class _antrain_Plan extends XocpBlock {
   var $catchvar = _ANTRAIN_CATCH_VAR;
   var $blockID = _ANTRAIN_PLAN_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = 'Annual Training Plan - General Subject';
   var $display_comment = TRUE;
   var $data;
   
   function _antrain_Plan($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */

   }

   

    function antrainplanproposed() {
    $id_propose = GetUserId();
	  $is_propose = '1';
      $dt_propose = getSQLDate(); 
	  
	   $sql = "UPDATE antrain session SET id_proposed = '$id_propose', is_proposed ='$is_propose', date_proposed = '$dt_propose' WHERE psid = '$psid'";
     $db->query($sql);
   header('Location: http://localhost/hris_test/index.php?XP_antrainplan');
   
   }
   
/*    function main() {
      $antrainselses = new _antrain_class_SelectSession();
      $antrainsel = "<div style='padding-bottom:2px;'>".$antrainselses->show()."</div>";
      
      if(!isset($_SESSION["pms_psid"])||$_SESSION["pms_psid"]==0) {
         return $antrainsel;
      }
      $db = &Database::getInstance();
      $user_id = getUserID();
      list($self_job_id,
           $self_employee_id,
           $self_job_nm,
           $self_nm,
           $self_nip,
           $self_gender,
           $self_jobstart,
           $self_entrance_dttm,
           $self_jobage,
           $self_job_summary,
           $self_person_id,
           $self_user_id,
           $self_first_assessor_job_id,
           $self_next_assessor_job_id)=_hris_getinfobyuserid($user_id);
      
      switch ($this->catch) {
         case $this->blockID:
            $ret = $this->antrainplan($self_employee_id);
            break;
         default:
            $ret = $this->antrainplan($self_employee_id);
            break;
      }
      return $antrainsel.$ret;
   } */
}

} // PMS_MYACTIONPLAN_DEFINED
?>