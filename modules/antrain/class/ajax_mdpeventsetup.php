<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_mdpeventsetup.php                   //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_MDPEVENTSETUPAJAX_DEFINED') ) {
   define('HRIS_MDPEVENTSETUPAJAX_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT.'/config.php');
//global $xocpConfig;
require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");
require_once(XOCP_DOC_ROOT."/modules/antrain/modconsts.php");

class _antrain_mdpeventsetupAjax extends AjaxListener {
   
   function __construct($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/antrain/class/ajax_mdpeventsetup.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_saveMdp","app_searchEvent","renderEvent","renderTest");
   }
   
   function app_saveMdp() {
   	$db=&Database::getInstance();
   	$vars = parseForm($args[1]);

   	$event_id = _bctrim(bcadd(0,$vars["event_id"]));
      $is_mdp = _bctrim(bcadd(0,$vars["is_mdp"]));

      $sql = "UPDATE hris_idp_event SET is_mdp = '$is_mdp' WHERE event_id = '$event_id'";
      $db->query($sql);

      RETURN TRUE;
   }

   function app_searchEvent($args) {
      $db=&Database::getInstance();
      $qstr = trim($args[0]);

      $sql = "SELECT event_id, event_title, is_mdp, method_t FROM hris_idp_event"
            . " WHERE event_title LIKE '%".$qstr."%'"
            . " ORDER BY event_id";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         $no = 0;
         while (list($event_id,$event_title,$is_mdp,$method_t)=$db->fetchRow($result)) {
           if($no >= 1000) break;
           $ret[] = array("$event_title", $event_id);
           $no++;
         }
      }

      $qstr = formatQueryString($qstr);

      $sql = "SELECT a.event_id, a.event_title, a.is_mdp, a.method_t, MATCH (a.event_title) AGAINST ('$qstr' IN BOOLEAN MODE) as score"
           . " FROM ".XOCP_PREFIX."idp_event a"
           . " WHERE MATCH (a.event_title) AGAINST ('$qstr' IN BOOLEAN MODE)"
           . " ORDER BY score DESC";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         $no = 0;
         while(list($event_id,$event_title,$is_mdp,$method_t)=$db->fetchRow($result)) {
            if($no >= 1000) break;
            $ret[$event_id] = array("$event_title ", $event_id);
            $no++;
         }
      }
      
      if(count($ret)>0) {
         $xret = array();
         foreach($ret as $event_id=>$v) {
            $xret[] = $v;
         }
         return $xret;
      } else {
         return "EMPTY";
      }
      
   }

   function renderEvent($args) {
     $db=&Database::getInstance();
     $ret = "";
     $event_idx = $args[0];

     if ($event_idx == 0) {
        $sqlevent = "SELECT event_id, event_title, is_mdp, method_t FROM hris_idp_event"
            . " ORDER BY event_id"; //LIMIT 10
     }else{
      $sqlevent = "SELECT event_id, event_title, is_mdp, method_t FROM hris_idp_event WHERE event_id = '$event_idx'";
     }

     
     $resultevent = $db->query($sqlevent); 
     if ($db->getRowsNum($resultevent) > 0) {
            while (list($event_id,$event_title,$is_mdp,$method_nm)=$db->fetchRow($resultevent)) {
               if ($is_mdp == 1) {
                  $yes = "selected";
                  $no = "";
                     }else{
                  $yes = "";
                        $no = "selected";
               }
         $ret .= "<tr><td style='text-align:center;'>$event_id<input type='hidden' name='event_id[]' value='$event_id'></td>"
                . "<td>$method_nm</td>"
                      . "<td>$event_title</td>"
                      . "<td><!-- <input id='is_mdp' type='checkbox' name='is_mdp[]' value='$event_id' $checked> -->"
                . "<select name='is_mdp[]'>"
                . "<option value='1' $yes>Yes</option>"
                . "<option value='0' $no>No</option>"
                . "</select>"

                . "</td></tr>";
               }  
            }  

    return $ret;
   }
   
   
}
} /// HRIS_OBJECTIVEAJAX_DEFINED
?>