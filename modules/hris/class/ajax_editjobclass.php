<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_editjobclass.php                //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_JOBCLASSAJAX_DEFINED') ) {
   define('HRIS_JOBCLASSAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");

class _hris_class_EditJobClassAjax extends AjaxListener {
   
   function _hris_class_EditJobClassAjax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/hris/class/ajax_editjobclass.php";
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
      $job_class_id = $args[0];
      $upper_job_class = $args[1];
      $sql = "UPDATE ".XOCP_PREFIX."job_class SET upper_job_class = '$upper_job_class' WHERE job_class_id = '$job_class_id'";
      $db->query($sql);
      $sql = "SELECT job_id,org_id FROM ".XOCP_PREFIX."jobs"
           . " WHERE job_class_id = '$job_class_id'";
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
                    . " WHERE job_class_id = '$upper_job_class'"
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
      $job_class_id = $args[0];
      $arr = parseForm($args[1]);
      foreach($arr as $k=>$v) {
         $$k = addslashes(trim($v));
      }
      $assessment_by_peer += 0;
      $assessment_by_subordinate += 0;
      $assessment_by_customer += 0;
      if($job_class_nm=="") {
         $job_class_nm = "noname";
      }
      if($job_class_id=="new") {
         $sql = "SELECT MAX(job_class_id) FROM ".XOCP_PREFIX."job_class";
         $result = $db->query($sql);
         list($job_class_idx)=$db->fetchRow($result);
         $job_class_id = $job_class_idx+1;
         $user_id = getUserID();
         $sql = "INSERT INTO ".XOCP_PREFIX."job_class (job_class_id,job_class_nm,description,created_user_id,job_class_cd,job_level,gradeval_top,gradeval_bottom,job_class_abbr,assessment_by_peer,assessment_by_subordinate,assessment_by_customer,upper_job_class)"
              . " VALUES('$job_class_id','$job_class_nm','$description','$user_id','$job_class_cd','$job_level','$gradeval_top','$gradeval_bottom','$job_class_abbr',$assessment_by_peer,$assessment_by_subordinate,$assessment_by_customer,'$upper_job_class')";
         $db->query($sql);
      } else {
         $sql = "UPDATE ".XOCP_PREFIX."job_class SET job_class_nm = '$job_class_nm', description = '$description',"
              . "job_class_cd = '$job_class_cd',"
              . "job_level = '$job_level',"
              . "job_class_level = '$job_class_level',"
              . "gradeval_top = '$gradeval_top',"
              . "gradeval_bottom = '$gradeval_bottom',"
              . "job_class_abbr = '$job_class_abbr',"
              . "assessment_by_peer = '$assessment_by_peer',"
              . "assessment_by_subordinate = '$assessment_by_subordinate',"
              . "assessment_by_customer = '$assessment_by_customer',"
              . "upper_job_class = '$upper_job_class'"
              . " WHERE job_class_id = '$job_class_id'";
         $db->query($sql);
      }
      
      // $ret = "$job_class_abbr <span id='sp_${job_class_id}' class='xlnk' onclick='edit_class(\"$job_class_id\",this,event);'>".htmlentities(stripslashes($job_class_nm))."</span>";
      
      $ret = "<table border='0' class='ilist'>"
           . "<colgroup><col width='80'/><col/></colgroup>"
           . "<tbody><tr>"
           . "<td>$job_class_abbr</td>"
           . "<td><span id='sp_${job_class_id}' class='xlnk' onclick='edit_class(\"$job_class_id\",this,event);'>".htmlentities(stripslashes($job_class_nm))."</span></td>"
           . "</tr></tbody></table>";
      
      return array("tdclass_${job_class_id}",$ret);
   }
   
   function app_editClass($args) {
      $db=&Database::getInstance();
      $job_class_id = $args[0];
      if($job_class_id=="new") {
         $bypeer = 1;
         $bycustomer = 1;
         $bysubordinate = 1;
      } else {
         $sql = "SELECT description,job_class_nm,job_class_cd,job_level,gradeval_top,gradeval_bottom,job_class_abbr,"
              . "job_class_level,assessment_by_peer,assessment_by_customer,assessment_by_subordinate,upper_job_class"
              . " FROM ".XOCP_PREFIX."job_class"
              . " WHERE job_class_id = '$job_class_id'";
         $result = $db->query($sql);
         list($desc,$job_class_nm,$job_class_cd,$job_level,$gradeval_top,$gradeval_bottom,$job_class_abbr,$job_class_level,$bypeer,$bycustomer,$bysubordinate,$upper_job_class)=$db->fetchRow($result);
            $job_class_nm = htmlentities($job_class_nm,ENT_QUOTES);
         $job_class_cd = htmlentities($job_class_cd,ENT_QUOTES);
         $job_class_abbr = htmlentities($job_class_abbr,ENT_QUOTES);
         $desc = htmlentities($desc,ENT_QUOTES);
      }
      $bypeer+=0;
      $bycustomer+=0;
      $bysubordinate+=0;
      $sel_level[$job_level] = "selected='1'";
      $optlevel = "<option value='management' ".$sel_level["management"].">Management</option>"
                . "<option value='nonmanagement' ".$sel_level["nonmanagement"].">Non Management</option>"
                . "<option value='director' ".$sel_level["director"].">Director</option>";
                
      
      $sql = "SELECT job_class_id,job_class_nm FROM ".XOCP_PREFIX."job_class ORDER BY job_class_level";
      $result = $db->query($sql);
      $upper_opt = "<option value='0'>-</option>";
      if($db->getRowsNum($result)>0) {
         while(list($job_class_idx,$job_class_nmx)=$db->fetchRow($result)) {
            if($job_class_idx==$job_class_id) continue;
            $upper_opt .= "<option value='$job_class_idx'".($job_class_idx==$upper_job_class?" selected='1'":"").">$job_class_nmx</option>";
         }
      }
      
      $ret = "<form id='frm'><table class='xxfrm' style='width:100%;'>"
           . "<colgroup><col width='220'/><col/></colgroup>"
           . "<tbody>"
           . "<tr><td>Position Name</td><td><input type='text' value=\"$job_class_nm\" id='inp_job_class_nm' name='job_class_nm' style='width:90%;'/></td></tr>"
           . "<tr><td>Code</td><td><input type='text' value=\"$job_class_cd\" id='inp_job_class_cd' name='job_class_cd' style='width:50px;'/></td></tr>"
           . "<tr><td>Abbreviation</td><td><input type='text' value=\"$job_class_abbr\" id='inp_job_class_abbr' name='job_class_abbr' style='width:50px;'/></td></tr>"
           . "<tr><td>Level Order</td><td><input type='text' value=\"$job_class_level\" id='inp_job_class_abbr' name='job_class_level' style='width:50px;'/></td></tr>"
           . "<tr><td>Description</td><td><textarea name='description' id='description' style='width:50%;'>$desc</textarea></td></tr>"
           . "<tr><td>Level</td><td><select name='job_level' id='sellevel'>$optlevel</select></td></tr>"
           . "<tr><td>Superior Job Class</td><td><select name='upper_job_class' id='supperjob'>$upper_opt</select>&nbsp;"
               . "<span id='sp_progress_upper' style='display:none;'></span><input type='button' value='Reset All' onclick='reset_all_superior(this,event);'/></td></tr>"
           . "<tr><td>Grade</td><td>"
               . "Min : <input type='text' value=\"$gradeval_bottom\" id='inp_gradeval_bottom' name='gradeval_bottom' style='width:50px;'/>"
               . "&nbsp;Max : <input type='text' value=\"$gradeval_top\" id='inp_gradeval_top' name='gradeval_top' style='width:50px;'/>"
           . "</td></tr>"
           . "<tr><td>Assessment by Peer&deg;</td><td><input type='checkbox' ".($bypeer==1?"checked='1'":"")." name='assessment_by_peer' value='1'/></td></tr>"
           . "<tr><td>Assessment by Subordinate&deg;</td><td><input type='checkbox' ".($bysubordinate==1?"checked='1'":"")." name='assessment_by_subordinate' value='1'/></td></tr>"
           . "<tr><td>Assessment by Customer&deg;</td><td><input type='checkbox' ".($bycustomer==1?"checked='1'":"")." name='assessment_by_customer' value='1'/></td></tr>"
           . "<tr><td colspan='2'><input onclick='save_class();' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input onclick='cancel_edit();' type='button' value='"._CANCEL."'/>&nbsp;&nbsp;"
           . ($job_class_id!="new"?"<input onclick='delete_class();' type='button' value='"._DELETE."'/>":"")
           . "</td></tr>"
           . "</tbody></table></form>";
      return $ret;
   }
   
   function app_Delete($args) {
      $db=&Database::getInstance();
      $job_class_id = $args[0];
      $sql = "DELETE FROM ".XOCP_PREFIX."job_class WHERE job_class_id = '$job_class_id'";
      $db->query($sql);
   }
   
}

} /// HRIS_JOBCLASSAJAX_DEFINED
?>