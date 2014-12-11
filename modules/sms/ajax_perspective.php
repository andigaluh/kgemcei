<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_perspective.php                //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_WORKAREAAJAX_DEFINED') ) {
   define('HRIS_WORKAREAAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");

class _sms_class_PerspectiveAjax extends AjaxListener {
   
   function _sms_class_PerspectiveAjax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/sms/class/ajax_perspective.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_deletePerspective","app_editPerspective","app_savePerspective",
                                             "app_getCopyAlt","app_copyPerspective");
   }
   
   function rootCopy($src_psid,$src_sms_perspective_id,$dst_sms_perspective_id) {
      $db=&Database::getInstance();
      $dst_psid = $_SESSION["sms_id"];
      
      $org_id = 1; //// root organization/company
      
      $sql = "SELECT start_dttm,stop_dttm FROM sms_session WHERE id = '$dst_psid'";
      $result = $db->query($sql);
      list($start_dttm,$stop_dttm)=$db->fetchRow($result);
      
      //// copy org sub first
      $sql = "SELECT org_id,org_abbr,org_nm FROM ".XOCP_PREFIX."orgs"
           . " WHERE parent_id = '$org_id'";
      $result = $db->query($sql);
      $sub = array();
      if($db->getRowsNum($result)>0) {
         while(list($sub_org_id)=$db->fetchRow($result)) {
            $sql = "REPLACE INTO sms_org_share (psid,sms_org_id,sms_share_org_id) VALUES ('$dst_psid','$org_id','$sub_org_id')";
            $db->query($sql);
            $sub[$sub_org_id] = 1;
         }
      }
      
      //// copy objectives
      $sql = "SELECT * FROM sms_objective"
           . " WHERE psid = '$src_psid' AND sms_perspective_id = '$src_sms_perspective_id'"
           . " AND sms_org_id = '$org_id'"
           . " ORDER BY sms_objective_no";
      $result = $db->query($sql);
      $map = array();
      if($db->getRowsNum($result)>0) {
         while($row=$db->fetchArray($result)) {
            $sms_objective_id = $row["sms_objective_id"];
            unset($row["sms_objective_id"]);
            $row["sms_perspective_id"] = $dst_sms_perspective_id;
            $sms_parent_objective_id = $row["sms_parent_objective_id"];
            $row["sms_parent_objective_id"] = $map[$sms_parent_objective_id];
            $row["psid"] = $dst_psid;
            _dumpvar($row);
            $cols = "";
            foreach($row as $k=>$v) {
               $cols .= ",$k";
            }
            $cols = substr($cols,1);
            
            /// insert objective
            $sql = "INSERT INTO sms_objective ($cols) VALUES ('".implode("','",$row)."')";
            $db->query($sql);
            _debuglog($sql);
            $new_sms_objective_id = $db->getInsertId();
            $map[$sms_objective_id] = $new_sms_objective_id;
         }
      }
      
      foreach($map as $sms_objective_id=>$new_sms_objective_id) {
         $sql = "SELECT * FROM sms_kpi"
              . " WHERE sms_objective_id = '$sms_objective_id'"
              . " AND psid = '$src_psid'";
         $result = $db->query($sql);
         if($db->getRowsNum($result)>0) {
            while($row=$db->fetchArray($result)) {
               $row["sms_objective_id"] = $new_sms_objective_id;
               $row["sms_kpi_start"] = $start_dttm;
               $row["sms_kpi_stop"] = $stop_dttm;
               $sms_parent_objective_id = $row["sms_parent_objective_id"];
               $row["sms_parent_objective_id"] = $map[$sms_parent_objective_id];
               $row["psid"] = $dst_psid;
               $cols = "";
               foreach($row as $k=>$v) {
                  $cols .= ",$k";
               }
               $cols = substr($cols,1);
            
               /// insert kpi
               $sql = "INSERT INTO sms_kpi ($cols) VALUES ('".implode("','",$row)."')";
               $db->query($sql);
               _debuglog($sql);
            }
         }
      }
      
      foreach($sub as $sub_org_id=>$v) {
         $sql = "SELECT * FROM sms_kpi_share"
              . " WHERE psid = '$src_psid'"
              . " AND sms_org_id = '$org_id'"
              . " AND sms_share_org_id = '$sub_org_id'";
         $result = $db->query($sql);
         _debuglog($sql);
         if($db->getRowsNum($result)>0) {
            while($row=$db->fetchArray($result)) {
               $sms_objective_id = $row["sms_objective_id"];
               $row["sms_objective_id"] = $map[$sms_objective_id];
               $row["psid"] = $dst_psid;
               $cols = "";
               foreach($row as $k=>$v) {
                  $cols .= ",$k";
               }
               $cols = substr($cols,1);
            
               /// insert kpi
               $sql = "INSERT INTO sms_kpi_share ($cols) VALUES ('".implode("','",$row)."')";
               $db->query($sql);
               _debuglog($sql);
            }
         }
      }
      
   }
   
   function app_copyPerspective($args) {
      $db=&Database::getInstance();
      $dst_psid = $_SESSION["sms_id"];
      $src_psid = $args[0];
      $sql = "DELETE FROM sms_section_perspective WHERE id_section_session = '$dst_psid'";
      $db->query($sql);
     /* $sql = "DELETE FROM sms_objective WHERE psid = '$dst_psid'";
      $db->query($sql);
      $sql = "DELETE FROM sms_kpi WHERE psid = '$dst_psid'";
      $db->query($sql);
      $sql = "DELETE FROM sms_kpi_share WHERE psid = '$dst_psid'";
      $db->query($sql);*/
      $sql = "SELECT id"
           . " FROM sms_section_perspective"
           . " WHERE id_section_session = '$src_psid'"
           . " ORDER BY order_no";
      $rp = $db->query($sql);
      if($db->getRowsNum($rp)>0) {
         while(list($src_sms_perspective_id)=$db->fetchRow($rp)) {
            $sql = "SELECT code,title,weight,sms_perspective_desc,create_user_id,create_date,modified_user_id,modified_date,order_no"
                 . " FROM sms_section_perspective"
                 . " WHERE id_section_session= '$src_psid'"
                 . " AND id = '$src_sms_perspective_id'";
            $result = $db->query($sql);
            _debuglog($sql);
            if($db->getRowsNum($result)>0) {
               while($row=$db->fetchRow($result)) {
                  $sql = "INSERT INTO sms_section_perspective (id_section_session,code,title,weight,sms_perspective_desc,create_user_id,create_date,modified_user_id,modified_date,order_no)"
                       . " VALUES ('".$dst_psid."','".(implode("','",$row))."')";
                  _debuglog($sql);
                  $db->query($sql);
                  $dst_sms_perspective_id = $db->getInsertId();
                  //$this->rootCopy($src_psid,$src_sms_perspective_id,$dst_sms_perspective_id);
               }
            }
         }
      }
      
      
      
      
   }
   
   function app_getCopyAlt($args) {
      $db=&Database::getInstance();
      $current_psid = $_SESSION["sms_id"];
      $sql = "SELECT periode_session,title_session,id FROM sms_session WHERE id != '$current_psid'";
      $result = $db->query($sql);
      $ret = "<div style='border:1px solid #bbb;background-color:#eee;padding:2px;text-align:center;'>Choose Session</div>";
      if($db->getRowsNum($result)>0) {
         while(list($session_periode,$session_nm,$psid)=$db->fetchRow($result)) {
            $ret .= "<div class='hlite' style='padding:2px;'><table style='width:100%;'><tbody><tr><td>$session_nm</td><td style='text-align:right;'>[<span onclick='do_copy_perspective(\"$psid\");' class='ylnk'>copy</span>]</td></tr></tbody></table></div>";
         }
      } else {
         $ret = "<div style='padding:2px;'>"._EMPTY."</div>";
      }
      
      return $ret;
   }
   
   function app_savePerspective($args) {
      $db=&Database::getInstance();
      $vars = _parseForm($args[0]);
      $current_psid = $_SESSION["sms_id"];
      $user_id = getUserID();
      $date = getSQLDate();
      $vars["sms_perspective_weight"] = _bctrim(bcadd($vars["sms_perspective_weight"],0));
      
      if($vars["sms_perspective_id"]=="new") {
         $sql = "SELECT MAX(id) FROM sms_section_perspective";
         $result = $db->query($sql);
         list($sms_perspective_id)=$db->fetchRow($result);


         $sms_perspective_id++;
         $sql = "INSERT INTO sms_section_perspective (id,create_user_id,create_date) VALUES ('$sms_perspective_id','$user_id','$date')";
         $db->query($sql);
         $is_new = 1;
      } else {
         $sms_perspective_id = $vars["sms_perspective_id"];
         $is_new = 0;

         $mod_sql = "UPDATE sms_section_perspective SET modified_user_id = '$user_id', modified_date = '$date' WHERE id = '".$vars["sms_perspective_id"]."'";
         $db->query($mod_sql);
      }

      $sql = "UPDATE sms_section_perspective SET "
        . "code = '".addslashes($vars["sms_perspective_code"])."',"
        . "title = '".addslashes($vars["sms_perspective_name"])."',"
        . "weight = '".$vars["sms_perspective_weight"]."',"
        . "sms_perspective_desc = '".addslashes($vars["sms_perspective_desc"])."',"
        . "id_section_session = '".$current_psid."'"
        . " WHERE id = '$sms_perspective_id'";
       $db->query($sql);
      
      $sql = "SELECT weight FROM sms_section_perspective";
      $result = $db->query($sql);
      $ttlw = 0;
      if($db->getRowsNum($result)>0) {
         while(list($w)=$db->fetchRow($result)) {
            $ttlw = bcadd($ttlw,$w);
         }
      }
      
      $ttlw = _bctrim($ttlw);
      
      return array($is_new,$sms_perspective_id,$vars["sms_perspective_code"],$vars["sms_perspective_name"],$vars["sms_perspective_weight"],$vars["sms_perspective_desc"],$ttlw);
   }
   
   function app_editPerspective($args) {
      $db=&Database::getInstance();
      $sms_perspective_id = $args[0];
      if($sms_perspective_id=="new") {
         $btn = "<input type='button' value='"._ADD."' onclick='save_perspective();'/>&nbsp;&nbsp;"
              . "&nbsp;<input type='button' value='"._CANCEL."' onclick='persbox.fade();'/>";
      } else {
         $sql = "SELECT code,title,weight,sms_perspective_desc FROM sms_section_perspective WHERE id = '$sms_perspective_id'";
         $result = $db->query($sql);
         list($sms_perspective_code,$sms_perspective_name,$sms_perspective_weight,$sms_perspective_desc)=$db->fetchRow($result);
         $sms_perspective_name = htmlentities($sms_perspective_name,ENT_QUOTES);
         $sms_perspective_desc = htmlentities($sms_perspective_desc,ENT_QUOTES);
         $btn = "<input type='button' value='"._SAVE."' onclick='save_perspective();'/>&nbsp;&nbsp;"
              . "<input type='button' value='"._CANCEL."' onclick='persbox.fade();'/>&nbsp;&nbsp;&nbsp;&nbsp;"
              . "<input type='button' value='"._DELETE."' onclick='delete_perspective(\"$sms_perspective_id\",this,event);'/>";
      }
                
      $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>"
           . ($sms_perspective_id=="new"?"Add Perspective":"Edit Perspective")
           . "</div>"
           . "<div style='padding:5px;background-color:#f2f2f2;color:#555;'>"
              . "<div style='max-height:235px;height:345px;overflow:auto;border:1px solid #999;background-color:#fff;padding:4px;' id='frmperspective'>"
                  . "<table style='width:100%;border-spacing:0px;'><tbody><tr><td style='height:215px;'>"
                     . "<table class='xxfrm' style='width:100%;'><tbody>"
                        . "<tr><td>Code</td><td><input type='text' name='sms_perspective_code' id='sms_perspective_code' value='$sms_perspective_code' style='width:40px;'/></td></tr>"
                        . "<tr><td>Name</td><td><input type='text' name='sms_perspective_name' id='sms_perspective_name' value='$sms_perspective_name' style='width:200px;'/></td></tr>"
                        . "<tr><td>Weigth</td><td><input type='text' name='sms_perspective_weight' id='sms_perspective_weight' value='$sms_perspective_weight' style='width:40px;'/> %</td></tr>"
                        . "<tr><td>Description</td><td><textarea name='sms_perspective_desc' id='sms_perspective_desc' style='width:90%;height:70px;'>$sms_perspective_desc</textarea></td></tr>"
                     . "</tbody></table>"
                     . "<input type='hidden' name='sms_perspective_id' id='sms_perspective_id' value='$sms_perspective_id'/>"
                  . "</td></tr></tbody></table>"
              . "</div>"
           . "</div>"
           . "<div style='text-align:center;padding:10px;background-color:#f2f2f2;height:100px;' id='frmbtn'>"
           . $btn
           . "</div>";
      
      return $ret;
   }
   
   function app_deletePerspective($args) {
      $db=&Database::getInstance();
      $sms_perspective_id = $args[0];
      $sql = "DELETE FROM sms_section_perspective WHERE id = '$sms_perspective_id'";
      $db->query($sql);
   }
   
}

} /// HRIS_WORKAREAAJAX_DEFINED
?>