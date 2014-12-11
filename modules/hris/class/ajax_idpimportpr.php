<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_rrealisasi.php                   //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_IDPIMPORTPRAJAX_DEFINED') ) {
   define('HRIS_IDPIMPORTPRAJAX_DEFINED', TRUE);
   require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");
   require_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
   
   class _hris_class_IDPImportPRAjax extends AjaxListener {

      function _hris_class_IDPImportPRAjax($act_name) {
         $this->_act_name = $act_name;
         $this->_include_file = XOCP_DOC_ROOT."/modules/hris/class/ajax_idpimportpr.php";
         $this->init();
         parent::init();
      }
      
      function init() {
         $this->registerAction($this->_act_name,"app_postUpload","app_Delete","app_editClass",
                                                "app_saveClass","app_viewData","app_importPR");
      }
      
      function app_importPR($args) {
         $db=&Database::getInstance();
         $pr_session_id = $args[0];
         $pr_import_id = $args[1];
         $user_id = getUserID();
         $sql = "DELETE FROM ".XOCP_PREFIX."pr_result WHERE pr_session_id = '$pr_session_id'";
         $db->query($sql);
         $sql = "SELECT pr_value,employee_id FROM ".XOCP_PREFIX."pr_import_data"
              . " WHERE pr_import_id = '$pr_import_id'";
         $result = $db->query($sql);
         $cnt = 0;
         if($db->getRowsNum($result)>0) {
            while(list($pr_value,$employee_id)=$db->fetchRow($result)) {
               if($employee_id==0) continue;
               $sql = "INSERT INTO ".XOCP_PREFIX."pr_result (pr_session_id,pr_import_id,employee_id,pr_value,update_user_id,update_dttm)"
                    . " VALUES ('$pr_session_id','$pr_import_id','$employee_id','$pr_value','$user_id',now())";
               $db->query($sql);
               $cnt++;
            }
         }
         return "... success. $cnt row updated.";
      }
      
      function app_viewData($args) {
         $db=&Database::getInstance();
         $pr_import_id = $args[0];
         $sql = "SELECT nip,employee_nm,pr_value,employee_id FROM ".XOCP_PREFIX."pr_import_data"
              . " WHERE pr_import_id = '$pr_import_id'";
         $result = $db->query($sql);
         $no = 1;
         if($db->getRowsNum($result)>0) {
            $ret = "<table style='width:100%;' class='xxlist'><tbody><tr>"
                 . "<td style='font-weight:bold;'>No.</td>"
                 . "<td style='font-weight:bold;'>NIP</td>"
                 . "<td style='font-weight:bold;'>Employee Name</td>"
                 . "<td style='font-weight:bold;text-align:right;'>PR Value</td>"
                 . "<td style='font-weight:bold;'>Link</td>"
                 . "</tr></tbody><tbody>";
            $fail = 0;
            while(list($nip,$employee_nm,$pr_value,$employee_id)=$db->fetchRow($result)) {
               if($employee_id==0) $fail++;
               $ret .= "<tr>"
                       . "<td>$no</td>"
                       . "<td>$nip</td>"
                       . "<td>$employee_nm</td>"
                       . "<td style='text-align:right;'>".toMoney($pr_value*100)." %</td>"
                       . "<td>".($employee_id>0?"Ok":"<span style='color:red;'>Fail</span>")."</td>"
                     . "</tr>";
               $no++;
            }
            $ret .= "</tbody><tfoot><tr><td colspan='2'>Row count :</td>"
                  . "<td colspan='3'>".($no-1)."</td></tr>";
            $ret .= "<tr><td colspan='2'>Failed :</td>"
                  . "<td colspan='3'>$fail</td></tr>";
            $ret .= "<tr><td colspan='5'><input type='button' value='Import' onclick='do_import_pr(\"$pr_import_id\",this,event);'/>&nbsp;&nbsp;<span id='import_progress'></span></td></tr>";
            $ret .= "</tfoot></table>";
         } else {
            $ret = "<div style='text-align:center;font-style:italic;color:#999;'>"._EMPTY."</div>";
         }
         return $ret;
      }
      
      function app_postUpload($args) {
         return $this->getList($args[0]);
      }
      
      function getList($pr_session_id) {
         $db=&Database::getInstance();
         $user_id = getUserID();
         $sql = "SELECT pr_import_id,created_dttm,file_nm,row_count FROM ".XOCP_PREFIX."pr_import WHERE pr_session_id = '$pr_session_id' ORDER BY created_dttm DESC";
         $result = $db->query($sql);
         $ret = "<table class='xxlist'>"
              . "<colgroup>"
                  . "<col width='150'/>"
                  . "<col/>"
                  . "<col/>"
              . "</colgroup>"
              . "<thead><tr><td>Import Time</td><td>Filename</td><td style='text-align:right;'>Records</td></tr></thead><tbody>";
         if($db->getRowsNum($result)>0) {
            while(list($pr_import_id,$created_dttm,$file_nm,$cnt)=$db->fetchRow($result)) {
               $ret .= "<tr id='tr_${pr_import_id}'>"
                     . "<td>$created_dttm</td>"
                     . "<td><span class='xlnk' onclick='preview_pr(\"$pr_session_id\",\"$pr_import_id\",this,event);'>$file_nm</span></td>"
                     . "<td style='text-align:right;'>$cnt</td></tr>";
            }
         }
         $ret .= "</tbody></table>";
         return $ret;
      }
      
   
      function app_saveClass($args) {
         $db=&Database::getInstance();
         $pr_session_id = $args[0];
         $pr_session_nm = addslashes(trim($args[1]));
         
         if($pr_session_id>0) {
            $sql = "UPDATE ".XOCP_PREFIX."pr_session SET "
                 . "pr_session_nm = '$pr_session_nm'"
                 . " WHERE pr_session_id = '$pr_session_id'";
            $db->query($sql);
         } else {
            $sql = "INSERT INTO ".XOCP_PREFIX."pr_session (pr_session_nm)"
                 . " VALUES('$pr_session_nm')";
            $db->query($sql);
            _debuglog($sql);
            $pr_session_id = $db->getInsertId();
         }
         
         $ret = "<table border='0' class='ilist'>"
              . "<colgroup><col width='100'/><col/></colgroup>"
              . "<tbody><tr>"
              . "<td>$pr_session_id</td>"
              . "<td><span id='sp_${pr_session_id}' class='xlnk' onclick='edit_class(\"$pr_session_id\",this,event);'>".htmlentities(stripslashes($pr_session_nm))."</span></td>"
              . "</tr></tbody></table>"
              . "<div style='padding:10px;' id='wdv'>"
              . $this->app_editClass(array($pr_session_id))
              . "</div>";
         
         return array("tdclass_${pr_session_id}",$ret,$pr_session_id);
      }
      
      function app_editClass($args) {
         $db=&Database::getInstance();
         $pr_session_id = $args[0];
         if($pr_session_id=="new") {
            $pr_session_id_val = "";
         } else {
            $sql = "SELECT pr_session_id,pr_session_nm"
                 . " FROM ".XOCP_PREFIX."pr_session"
                 . " WHERE pr_session_id = '$pr_session_id'";
            $result = $db->query($sql);
            list($pr_session_id,$pr_session_nm)=$db->fetchRow($result);
            $pr_session_nm = htmlentities($pr_session_nm,ENT_QUOTES);
            $pr_session_id_val = $pr_session_id;
         }
         
         $ret = "<form id='frm'><table class='xxfrm' style='width:100%;'>"
              . "<colgroup><col width='120'/><col/></colgroup>"
              . "<tbody>"
              . "<tr><td>Session Name</td><td><input type='text' value=\"$pr_session_nm\" id='inp_pr_session_nm' name='pr_session_nm' style='width:90%;'/></td></tr>"
              
              . "<tr><td>Excel File</td><td>"
              . "<table class='xlist' style='width:100%;' border='0' align='center'><thead><tr>"
              . "<td><iframe src='".XOCP_SERVER_SUBDIR."/modules/hris/pr_excel_reader.php?pr_session_id=${pr_session_id}' style='width:400px;border:0px solid black;overflow:hidden;height:22px;'></iframe>"
              . "</td><td style='vertical-align:middle;'></td></tr></thead></table>"
              . "</td></tr>"
              
              . "<tr><td colspan='2' style='text-align:left;background-color:#fff;'>"
              . "<div id='uploadlist' style=''>".$this->getList($pr_session_id)."</div>"
              . "</td></tr>"
              
              . "<tr><td colspan='2'><span id='progress_edit'></span>&nbsp;&nbsp;<input onclick='save_session();' type='button' value='"._SAVE."'/>&nbsp;"
              . "<input onclick='cancel_edit();' type='button' value='"._CANCEL."'/>"
              . "&nbsp;&nbsp;" . ($pr_session_id!="new"?"<input onclick='delete_class();' type='button' value='"._DELETE."'/>":"")
              . "<input type='hidden' value=\"$pr_session_id_val\" id='inp_pr_session_id' name='pr_session_id'/>"
              . "</td></tr>"
              . "</tbody></table></form>";
         return $ret;
      }
      
      function app_Delete($args) {
         $db=&Database::getInstance();
         $pr_session_id = $args[0];
         $sql = "DELETE FROM ".XOCP_PREFIX."pr_session WHERE pr_session_id = '$pr_session_id'";
         $db->query($sql);
      }
   
   }
}
?>