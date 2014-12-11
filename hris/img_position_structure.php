<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/img_position_structure.php                 //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2009-07-02                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_POSITIONSTRUCTUREIMAGE_DEFINED') ) {
   define('HRIS_POSITIONSTRUCTUREIMAGE_DEFINED', TRUE);

include_once("../../config.php");
include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
include_once(XOCP_DOC_ROOT."/modules/hris/class/mydiagram.php");

   function diagram() {
      $db=&Database::getInstance();
      
      if($_GET["job"]) {
         $job_id = $_GET["job"];
      } else {
         $job_id = NULL;
      }
      
      $_SESSION["hris_nodes"] = array();
      $xx = new DiagramX($job_id);
      $xx->render();
      
   }
   
   
   diagram();

} // HRIS_POSITIONSTRUCTUREIMAGE_DEFINED

