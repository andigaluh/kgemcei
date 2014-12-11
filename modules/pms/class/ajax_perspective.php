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

class _pms_class_PerspectiveAjax extends AjaxListener {
   
   function _pms_class_PerspectiveAjax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/pms/class/ajax_perspective.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_deletePerspective","app_editPerspective","app_savePerspective",
                                             "app_getCopyAlt","app_copyPerspective");
   }
   
   function rootCopy($src_psid,$src_pms_perspective_id,$dst_pms_perspective_id) {
      $db=&Database::getInstance();
      $dst_psid = $_SESSION["pms_psid"];
      
      $org_id = 1; //// root organization/company
      
      $sql = "SELECT start_dttm,stop_dttm FROM pms_session WHERE psid = '$dst_psid'";
      $result = $db->query($sql);
      list($start_dttm,$stop_dttm)=$db->fetchRow($result);
      
      //// copy org sub first
      $sql = "SELECT org_id,org_abbr,org_nm FROM ".XOCP_PREFIX."orgs"
           . " WHERE parent_id = '$org_id'";
      $result = $db->query($sql);
      $sub = array();
      if($db->getRowsNum($result)>0) {
         while(list($sub_org_id)=$db->fetchRow($result)) {
            $sql = "REPLACE INTO pms_org_share (psid,pms_org_id,pms_share_org_id) VALUES ('$dst_psid','$org_id','$sub_org_id')";
            $db->query($sql);
            $sub[$sub_org_id] = 1;
         }
      }
      
      //// copy objectives
      $sql = "SELECT * FROM pms_objective"
           . " WHERE psid = '$src_psid' AND pms_perspective_id = '$src_pms_perspective_id'"
           . " AND pms_org_id = '$org_id'"
           . " ORDER BY pms_objective_no";
      $result = $db->query($sql);
      $map = array();
      if($db->getRowsNum($result)>0) {
         while($row=$db->fetchArray($result)) {
            $pms_objective_id = $row["pms_objective_id"];
            unset($row["pms_objective_id"]);
            $row["pms_perspective_id"] = $dst_pms_perspective_id;
            $pms_parent_objective_id = $row["pms_parent_objective_id"];
            $row["pms_parent_objective_id"] = $map[$pms_parent_objective_id];
            $row["psid"] = $dst_psid;
            _dumpvar($row);
            $cols = "";
            foreach($row as $k=>$v) {
               $cols .= ",$k";
            }
            $cols = substr($cols,1);
            
            /// insert objective
            $sql = "INSERT INTO pms_objective ($cols) VALUES ('".implode("','",$row)."')";
            $db->query($sql);
            _debuglog($sql);
            $new_pms_objective_id = $db->getInsertId();
            $map[$pms_objective_id] = $new_pms_objective_id;
         }
      }
      
      foreach($map as $pms_objective_id=>$new_pms_objective_id) {
         $sql = "SELECT * FROM pms_kpi"
              . " WHERE pms_objective_id = '$pms_objective_id'"
              . " AND psid = '$src_psid'";
         $result = $db->query($sql);
         if($db->getRowsNum($result)>0) {
            while($row=$db->fetchArray($result)) {
               $row["pms_objective_id"] = $new_pms_objective_id;
               $row["pms_kpi_start"] = $start_dttm;
               $row["pms_kpi_stop"] = $stop_dttm;
               $pms_parent_objective_id = $row["pms_parent_objective_id"];
               $row["pms_parent_objective_id"] = $map[$pms_parent_objective_id];
               $row["psid"] = $dst_psid;
               $cols = "";
               foreach($row as $k=>$v) {
                  $cols .= ",$k";
               }
               $cols = substr($cols,1);
            
               /// insert kpi
               $sql = "INSERT INTO pms_kpi ($cols) VALUES ('".implode("','",$row)."')";
               $db->query($sql);
               _debuglog($sql);
            }
         }
      }
      
      foreach($sub as $sub_org_id=>$v) {
         $sql = "SELECT * FROM pms_kpi_share"
              . " WHERE psid = '$src_psid'"
              . " AND pms_org_id = '$org_id'"
              . " AND pms_share_org_id = '$sub_org_id'";
         $result = $db->query($sql);
         _debuglog($sql);
         if($db->getRowsNum($result)>0) {
            while($row=$db->fetchArray($result)) {
               $pms_objective_id = $row["pms_objective_id"];
               $row["pms_objective_id"] = $map[$pms_objective_id];
               $row["psid"] = $dst_psid;
               $cols = "";
               foreach($row as $k=>$v) {
                  $cols .= ",$k";
               }
               $cols = substr($cols,1);
            
               /// insert kpi
               $sql = "INSERT INTO pms_kpi_share ($cols) VALUES ('".implode("','",$row)."')";
               $db->query($sql);
               _debuglog($sql);
            }
         }
      }
      
   }
   
   function app_copyPerspective($args) {
      $db=&Database::getInstance();
      $dst_psid = $_SESSION["pms_psid"];
      $src_psid = $args[0];
      $sql = "DELETE FROM pms_perspective WHERE psid = '$dst_psid'";
      $db->query($sql);
      $sql = "DELETE FROM pms_objective WHERE psid = '$dst_psid'";
      $db->query($sql);
      $sql = "DELETE FROM pms_kpi WHERE psid = '$dst_psid'";
      $db->query($sql);
      $sql = "DELETE FROM pms_kpi_share WHERE psid = '$dst_psid'";
      $db->query($sql);
      $sql = "SELECT pms_perspective_id"
           . " FROM pms_perspective"
           . " WHERE psid = '$src_psid'"
           . " ORDER BY order_no";
      $rp = $db->query($sql);
      if($db->getRowsNum($rp)>0) {
         while(list($src_pms_perspective_id)=$db->fetchRow($rp)) {
            $sql = "SELECT pms_perspective_code,pms_perspective_name,pms_perspective_weight,pms_perspective_desc,order_no"
                 . " FROM pms_perspective"
                 . " WHERE psid = '$src_psid'"
                 . " AND pms_perspective_id = '$src_pms_perspective_id'";
            $result = $db->query($sql);
            _debuglog($sql);
            if($db->getRowsNum($result)>0) {
               while($row=$db->fetchRow($result)) {
                  $sql = "INSERT INTO pms_perspective (pms_perspective_code,pms_perspective_name,pms_perspective_weight,pms_perspective_desc,order_no,psid)"
                       . " VALUES ('".(implode("','",$row))."','$dst_psid')";
                  _debuglog($sql);
                  $db->query($sql);
                  $dst_pms_perspective_id = $db->getInsertId();
                  $this->rootCopy($src_psid,$src_pms_perspective_id,$dst_pms_perspective_id);
               }
            }
         }
      }
      
      
      
      
   }
   
   function app_getCopyAlt($args) {
      $db=&Database::getInstance();
      $current_psid = $_SESSION["pms_psid"];
      $sql = "SELECT session_periode,session_nm,psid FROM pms_session WHERE psid != '$current_psid' AND status_cd = 'normal'";
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
      $psid = $_SESSION["pms_psid"];
      $vars = _parseForm($args[0]);
      
      $vars["pms_perspective_weight"] = _bctrim(bcadd($vars["pms_perspective_weight"],0));
      
      if($vars["pms_perspective_id"]=="new") {
         $sql = "SELECT MAX(pms_perspective_id) FROM pms_perspective";
         $result = $db->query($sql);
         list($pms_perspective_id)=$db->fetchRow($result);
         $pms_perspective_id++;
         $sql = "INSERT INTO pms_perspective (pms_perspective_id) VALUES ('$pms_perspective_id')";
         $db->query($sql);
         $is_new = 1;
      } else {
         $pms_perspective_id = $vars["pms_perspective_id"];
         $is_new = 0;
      }
      
      $sql = "UPDATE pms_perspective SET "
           . "pms_perspective_code = '".addslashes($vars["pms_perspective_code"])."',"
           . "pms_perspective_name = '".addslashes($vars["pms_perspective_name"])."',"
           . "pms_perspective_weight = '".addslashes($vars["pms_perspective_weight"])."',"
           . "pms_perspective_desc = '".addslashes($vars["pms_perspective_desc"])."'"
           . " WHERE pms_perspective_id = '$pms_perspective_id'";
      $db->query($sql);
      
      $sql = "SELECT pms_perspective_weight FROM pms_perspective WHERE psid = '$psid'";
      $result = $db->query($sql);
      $ttlw = 0;
      if($db->getRowsNum($result)>0) {
         while(list($w)=$db->fetchRow($result)) {
            $ttlw = bcadd($ttlw,$w);
         }
      }
      
      $ttlw = _bctrim($ttlw);
      
      return array($is_new,$pms_perspective_id,$vars["pms_perspective_code"],$vars["pms_perspective_name"],$vars["pms_perspective_weight"],$vars["pms_perspective_desc"],$ttlw);
   }
   
   function app_editPerspective($args) {
      $db=&Database::getInstance();
      $psid = $_SESSION["pms_psid"];
      $pms_perspective_id = $args[0];
      if($pms_perspective_id=="new") {
         $btn = "<input type='button' value='"._ADD."' onclick='save_perspective();'/>&nbsp;&nbsp;"
              . "&nbsp;<input type='button' value='"._CANCEL."' onclick='persbox.fade();'/>";
      } else {
         $sql = "SELECT pms_perspective_code,pms_perspective_name,pms_perspective_weight,pms_perspective_desc FROM pms_perspective WHERE psid = '$psid' AND pms_perspective_id = '$pms_perspective_id'";
         $result = $db->query($sql);
         list($pms_perspective_code,$pms_perspective_name,$pms_perspective_weight,$pms_perspective_desc)=$db->fetchRow($result);
         $pms_perspective_name = htmlentities($pms_perspective_name,ENT_QUOTES);
         $pms_perspective_desc = htmlentities($pms_perspective_desc,ENT_QUOTES);
         $btn = "<input type='button' value='"._SAVE."' onclick='save_perspective();'/>&nbsp;&nbsp;"
              . "<input type='button' value='"._CANCEL."' onclick='persbox.fade();'/>&nbsp;&nbsp;&nbsp;&nbsp;"
              . "<input type='button' value='"._DELETE."' onclick='delete_perspective(\"$pms_perspective_id\",this,event);'/>";
      }
                
      $ret = "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>"
           . ($pms_perspective_id=="new"?"Add Perspective":"Edit Perspective")
           . "</div>"
           . "<div style='padding:5px;background-color:#f2f2f2;color:#555;'>"
              . "<div style='max-height:235px;height:345px;overflow:auto;border:1px solid #999;background-color:#fff;padding:4px;' id='frmperspective'>"
                  . "<table style='width:100%;border-spacing:0px;'><tbody><tr><td style='height:215px;'>"
                     . "<table class='xxfrm' style='width:100%;'><tbody>"
                        . "<tr><td>Code</td><td><input type='text' name='pms_perspective_code' id='pms_perspective_code' value='$pms_perspective_code' style='width:40px;'/></td></tr>"
                        . "<tr><td>Name</td><td><input type='text' name='pms_perspective_name' id='pms_perspective_name' value='$pms_perspective_name' style='width:200px;'/></td></tr>"
                        . "<tr><td>Weigth</td><td><input type='text' name='pms_perspective_weight' id='pms_perspective_weight' value='$pms_perspective_weight' style='width:40px;'/> %</td></tr>"
                        . "<tr><td>Description</td><td><textarea name='pms_perspective_desc' id='pms_perspective_desc' style='width:90%;height:70px;'>$pms_perspective_desc</textarea></td></tr>"
                     . "</tbody></table>"
                     . "<input type='hidden' name='pms_perspective_id' id='pms_perspective_id' value='$pms_perspective_id'/>"
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
      $psid = $_SESSION["pms_psid"];
      $pms_perspective_id = $args[0];
      $sql = "DELETE FROM pms_perspective WHERE psid = '$psid' AND pms_perspective_id = '$pms_perspective_id'";
      $db->query($sql);
   }
   
}

} /// HRIS_WORKAREAAJAX_DEFINED
?>