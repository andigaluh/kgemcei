<?php
//--------------------------------------------------------------------//
// Filename : modules/antrain/class/ajax_antrainbudget.php                  //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-12-17                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_ANTRAINCALENDARAJAX_DEFINED') ) {
   define('HRIS_ANTRAINCALENDARAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");
require_once(XOCP_DOC_ROOT."/modules/antrain/modconsts.php");
require_once(XOCP_DOC_ROOT."/include/phpjson/phpJson.class.php");


class _antrain_class_ANTRAINCalendarAjax extends AjaxListener {
   
   function __construct($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/antrain/class/ajax_antraincalendar.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_renderTraining","app_detailTraining");
   }

   function app_renderTraining() {
      $db=&Database::getInstance();

      $sql = "SELECT p.id, p.name, p.subject, p.schedule_start, p.schedule_end
FROM antrain_plan_specific p LEFT JOIN antrain_sessionss pk ON p.id_antrain_session = pk.psid WHERE p.is_deleted = 'F' AND pk.is_approved = 1 ORDER BY p.id ASC";
      $result = $db->query($sql);

      $training_array = array();

      while (list($id,$name,$subject,$start,$end)=$db->fetchRow($result)) {
         $start = date_create($start);
         $start = date_format($start,'Y-m-d');
         $title = $name." - ".$subject;
         $trainingdetail = array('id' => $id,'title' => $title,'start' => $start);
         array_push($training_array, $trainingdetail);
      }

      return json_encode($training_array);
   }
 
   function app_detailTraining($args) {
      $db=&Database::getInstance();

      $id = $args[0];

      $sql = "SELECT id, name, subject, schedule_start, schedule_end FROM antrain_plan_specific WHERE id = '$id'";
      $result = $db->query($sql);
      list($id,$name,$subject,$start,$end)=$db->fetchRow($result);
      $start = date_create($start);
      $start = date_format($start,'Y-m-d');
      $end = date_create($end);
      $end = date_format($end,'Y-m-d');

      $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>"
           . "Training Plan"
           . "</div>"
           . "<div style='padding:5px;background-color:#f2f2f2;color:#555;'>"
              . "<div style='max-height:250px;height:150px;overflow:auto;border:1px solid #999;background-color:#fff;padding:4px;' id='frmperspective'>"
                  . "<table style='width:100%;border-spacing:0px;'><tbody><tr><td style='height:100px;'>"
                     . "<table class='xxfrm' style='width:100%;'><tbody>"
                        . "<tr><td>Name</td><td>$name</td></tr>"
                        . "<tr><td>Subject</td><td>$subject</td></tr>"
                        . "<tr><td>Start Date</td><td>$start</td></tr>"
                        . "<tr><td>End Date</td><td>$end</td></tr>"
                     . "</tbody></table>"
                  . "</td></tr></tbody></table>"
              . "</div>"
           . "</div>"
           . "<div style='text-align:center;padding:10px;background-color:#f2f2f2;height:100px;' id='frmbtn'>"
           . "<input type='button' value='"._CANCEL."' onclick='eventbox.fade();'/>"
           . "</div>";
      
      return $ret;


   }
   
   
}

} /// HRIS_ASSESSMENTSESSIONAJAX_DEFINED
?>