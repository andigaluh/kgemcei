<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_editpeer.php                //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_EDITPEERAJAX_DEFINED') ) {
   define('HRIS_EDITPEERAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");
require_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");

class _hris_class_EditPeerAjax extends AjaxListener {
   
   function __construct($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/hris/class/ajax_editpeer.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_editPeer","app_searchJob","app_deletePeer",
                                             "app_addPeer","app_generateAssessor");
   }
   
   function app_generateAssessor($args) {
      $db=&Database::getInstance();
      $job_id = $args[0];
      
      $hackdate = getSQLDate();
      
      $asid = 0; ///// 
      
      $sql = "SELECT MAX(asid) FROM ".XOCP_PREFIX."assessment_session WHERE status_cd = 'normal'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($asid)=$db->fetchRow($result);
      }
      
      $arr_provider = array();
      
      $sql = "SELECT employee_id FROM ".XOCP_PREFIX."employee_job WHERE job_id = '$job_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($employee_idx)=$db->fetchRow($result)) {
            $arr_provider[$employee_idx] = 1;
         }
      }
      
      $sql = "SELECT a.job_id,a.job_cd,a.job_nm,"
           . "b.org_nm,c.org_class_nm,a.job_abbr,(d.job_level+0) as srt"
           . " FROM ".XOCP_PREFIX."peer_matrix m"
           . " LEFT JOIN ".XOCP_PREFIX."jobs a ON a.job_id = m.peer_job_id0"
           . " LEFT JOIN ".XOCP_PREFIX."orgs b USING(org_id)"
           . " LEFT JOIN ".XOCP_PREFIX."org_class c USING(org_class_id)"
           . " LEFT JOIN ".XOCP_PREFIX."job_class d ON d.job_class_id = a.job_class_id"
           . " WHERE m.peer_job_id1 = '$job_id' AND a.status_cd = 'normal'"
           . " ORDER BY d.job_class_level, b.org_nm,d.gradeval_bottom DESC,srt DESC,a.job_nm";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($customer_job_id,$customer_job_cd,$customer_job_nm,$customer_org_nm,$customer_org_class_nm,$customer_job_abbr)=$db->fetchRow($result)) {
            $sql = "SELECT employee_id FROM ".XOCP_PREFIX."employee_job WHERE job_id = '$customer_job_id'";
            $resultx = $db->query($sql);
            if($db->getRowsNum($resultx)>0) {
               while(list($employee_idx)=$db->fetchRow($resultx)) {
                  foreach($arr_provider as $provider_employee_id=>$v) {
                     $sql = "insert into hris_assessor_360 values ('$asid','$provider_employee_id','$employee_idx','peer','active','$hackdate','1','0000-00-00 00:00:00','0')";
                     $db->query($sql);
                     _debuglog($sql);
                     _debuglog(mysql_error());
                  }
               }
            }
         }
      }
      return "OK";
   }
   
   function app_addPeer($args) {
      $db=&Database::getInstance();
      $job_id = $args[0];
      $peer_job_id1 = $args[1];
      
      if($job_id!=$peer_job_id1) {
         $sql = "INSERT INTO ".XOCP_PREFIX."peer_matrix (peer_job_id0,peer_job_id1) VALUES ('$job_id','$peer_job_id1')";
         $db->query($sql);
         
         $sql = "INSERT INTO ".XOCP_PREFIX."peer_matrix (peer_job_id0,peer_job_id1) VALUES ('$peer_job_id1','$job_id')";
         $db->query($sql);
      }
      
      $ret = $this->renderPeerList($job_id);
      
      $ret .= "<div style='margin-top:10px;background-color:#bbb;padding:5px;border:1px solid #888;-moz-border-radius:5px;text-align:right;'>"
            . "<span id='sp_progress_generate'></span>&nbsp;&nbsp;"
            . "<input type='button' value='Generate Assessor for Current Assessment' onclick='generate_assessor(\"$job_id\",this,event);'/>"
            . "</div>";
      
      
      $sql = "SELECT COUNT(*) FROM ".XOCP_PREFIX."peer_matrix WHERE peer_job_id0 = '$job_id'";
      $rcnt = $db->query($sql);
      list($peer_count)=$db->fetchRow($rcnt);
      return array($ret,$peer_count);
   }

   function app_deletePeer($args) {
      $db=&Database::getInstance();
      $job_id = $args[0];
      $peer_job_id1 = $args[1];
      
      $sql = "DELETE FROM ".XOCP_PREFIX."peer_matrix WHERE peer_job_id0 = '$job_id' AND peer_job_id1 = '$peer_job_id1'";
      $db->query($sql);
      
      $sql = "DELETE FROM ".XOCP_PREFIX."peer_matrix WHERE peer_job_id0 = '$peer_job_id1' AND peer_job_id1 = '$job_id'";
      $db->query($sql);
      
      $sql = "SELECT COUNT(*) FROM ".XOCP_PREFIX."peer_matrix WHERE peer_job_id0 = '$job_id'";
      $rcnt = $db->query($sql);
      list($peer_count)=$db->fetchRow($rcnt);
      return $peer_count;
   }

   function app_searchJob($args) {
      $db=&Database::getInstance();
      $qstr = trim($args[0]);
      $sql = "SELECT job_nm,job_id,job_cd FROM ".XOCP_PREFIX."jobs"
           . " WHERE job_cd LIKE '$qstr%'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($job_nm,$job_id,$job_cd)=$db->fetchRow($result);
         $ret[$job_id] = array("$job_nm [$job_cd]",$job_id);
      }

      $qstr = formatQueryString($qstr);

      $sql = "SELECT a.job_id, a.job_nm, a.job_cd, MATCH (a.job_nm) AGAINST ('$qstr' IN BOOLEAN MODE) as score"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " WHERE MATCH (a.job_nm) AGAINST ('$qstr' IN BOOLEAN MODE)"
           . " ORDER BY score DESC";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         $no = 0;
         while(list($job_id,$job_nm,$job_cd)=$db->fetchRow($result)) {
            if($no >= 1000) break;
            $ret[$job_id] = array("$job_nm [$job_cd]",$job_id);
            $no++;
         }
      }
      
      if(count($ret)>0) {
         $newret = array();
         foreach($ret as $job_id=>$v) {
            $newret[] = $v;
         }
         return $newret;
      } else {
         return "EMPTY";
      }
      
   }
   
   
   function app_editPeer($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $job_id = $args[0];

      $sql = "SELECT b.org_nm,c.org_class_nm,"
           . "a.description,a.job_nm,a.job_cd,a.job_class_id,"
           . "a.workarea_id,a.location_id,a.org_id,a.job_abbr,a.assessor_job_id,"
           . "a.summary,a.peer_group_id,a.assessment_by_360,a.upper_job_id,"
           . "a.summary_id_txt,a.description_id_txt"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."orgs b USING(org_id)"
           . " LEFT JOIN ".XOCP_PREFIX."org_class c USING(org_class_id)"
           . " WHERE a.job_id = '$job_id'";
      $result = $db->query($sql);
      
      list($org_nm,$org_class_nm,$desc,$job_nm,$job_cd,$job_class_id,$workarea_id,$location_id,
           $org_id,$job_abbr,$assessor_job_id,$summary,$peer_group_id,$by360,$upper_job_id,$summary_id_txt,$desc_id_txt)=$db->fetchRow($result);
      $job_nm = stripslashes(htmlentities($job_nm,ENT_QUOTES));
      $job_cd = stripslashes(htmlentities($job_cd,ENT_QUOTES));
      $job_abbr = stripslashes(htmlentities($job_abbr,ENT_QUOTES));
      
      $ret = $this->renderJob($job_id,$job_nm,$job_abbr,$org_class_nm,$org_nm);
      $ret .= "<div id='jobeditor' style='padding:10px;padding-left:20px;'>";
      
      $ret .= $this->renderPeerList($job_id);
      
      $ret .= "<div style='margin-top:10px;background-color:#bbb;padding:5px;border:1px solid #888;-moz-border-radius:5px;text-align:right;'>"
            . "<span id='sp_progress_generate'></span>&nbsp;&nbsp;"
            . "<input type='button' value='Generate Assessor for Current Assessment' onclick='generate_assessor(\"$job_id\",this,event);'/>"
            . "</div>";
      
      $ret .= "</div>"; /// jobeditor
      
      return array($ret,$job_id);
   }
   
   function renderPeerList($job_id) {
      $db=&Database::getInstance();
      
      
      $employee_list = "";
      $sql = "SELECT employee_id FROM ".XOCP_PREFIX."employee_job WHERE job_id = '$job_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($employee_idx)=$db->fetchRow($result)) {
            list($j,$jj,$jjj,$nm) = _hris_getinfobyemployeeid($employee_idx);
            $employee_list .= "<div style='padding:5px;font-style:italic;'>$nm</div>";
         }
      } else {
            $employee_list .= "<div style='padding:3px;color:red;font-style:italic;'>"._EMPTY."</div>";
      }
      
      
      $sql = "SELECT a.job_id,a.job_cd,a.job_nm,"
           . "b.org_nm,c.org_class_nm,a.job_abbr,(d.job_level+0) as srt"
           . " FROM ".XOCP_PREFIX."peer_matrix m"
           . " LEFT JOIN ".XOCP_PREFIX."jobs a ON a.job_id = m.peer_job_id1"
           . " LEFT JOIN ".XOCP_PREFIX."orgs b USING(org_id)"
           . " LEFT JOIN ".XOCP_PREFIX."org_class c USING(org_class_id)"
           . " LEFT JOIN ".XOCP_PREFIX."job_class d ON d.job_class_id = a.job_class_id"
           . " WHERE m.peer_job_id0 = '$job_id' AND a.status_cd = 'normal'"
           . " ORDER BY d.job_class_level, b.org_nm,d.gradeval_bottom DESC,srt DESC,a.job_nm";
      
      
      $ret = "<div style='padding:10px;-moz-box-shadow:inset 0px 0px 3px #000;background-color:#ddd;-moz-border-radius:5px;'>"
           . "<div style='background-color:#777;padding:5px;font-weight:bold;color:#fff;'>Employee List"
           . "</div>"
           . "<div style='border:1px solid #bbb;background-color:#eee;'>$employee_list</div>"
           . "<br/>"
           . "<div style='background-color:#777;padding:5px;font-weight:bold;color:#fff;'>"
           . "<table style='border-spacing:0;width:100%;'><tbody><tr><td>Peer List</td><td style='text-align:right;'>"
           . "<div style='font-weight:normal;'><input id='qpeer' type='text' class='searchBox' onclick='init_q_peer(this,event);'/></div>"
           . "</td></tr></tbody></table>"
           . "</div>";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($peer_job_id1,$peer_job_cd,$peer_job_nm,$peer_org_nm,$peer_org_class_nm,$peer_job_abbr)=$db->fetchRow($result)) {
            
            
            $ret .= "<div id='dvpeer_${peer_job_id1}'>"
                  . $this->renderPeer($peer_job_id1,$peer_job_nm,$peer_job_abbr,$peer_org_class_nm,$peer_org_nm)
                  . "</div>";
         
         }
      } else {
         $ret .= "<div style='text-align:center;font-style:italic;color:#888;padding:10px;'>"._EMPTY."</div>";
      }
      $ret .= "</div>";
      return $ret;
   }
   
   function renderPeer($job_id,$job_nm,$job_abbr,$org_class_nm,$org_nm) {
      $db=&Database::getInstance();
      $peer_employee_list = "";
      $sql = "SELECT employee_id FROM ".XOCP_PREFIX."employee_job WHERE job_id = '$job_id'";
      $resultx = $db->query($sql);
      if($db->getRowsNum($resultx)>0) {
         while(list($employee_idx)=$db->fetchRow($resultx)) {
            list($j,$jj,$jjj,$nm) = _hris_getinfobyemployeeid($employee_idx);
            $peer_employee_list .= "<div style='padding:3px;'>$nm</div>";
         }
      } else {
            $peer_employee_list .= "<div style='padding:3px;color:red;'>"._EMPTY."</div>";
      }
            
      $ret = "<table style='border:0px;width:100%;'>"
                  . "<colgroup><col width='100'/><col/><col width='200'/></colgroup><tbody>"
                  . "<tr><td style='vertical-align:top;'>$job_abbr</td>"
                      . "<td style='vertical-align:top;'>"
                      . "<div style='overflow:hidden;width:300px;'><div style='width:900px;'><span id='sppeer_${job_id}' class='xlnk' onclick='edit_peer(\"$job_id\",this,event);'>".stripslashes($job_nm)."</span></div></div>"
                      . "<div style='margin-top:10px;-moz-border-radius:5px;background-color:#fff;padding:2px;font-style:italic;font-size:0.9em;overflow:hidden;width:300px;'><div style='width:900px;'>$peer_employee_list</div></div>"
                      . "</td>"
                      . "<td style='vertical-align:top;'><div style='overflow:hidden;width:200px;'><div style='width:900px;' id='dvopeer_${job_id}'>".htmlentities("$org_nm $org_class_nm",ENT_QUOTES)."</div></div></td>"
                  . "</tr></tbody></table>";
      return $ret;
   }
   
   function renderJob($job_id,$job_nm,$job_abbr,$org_class_nm,$org_nm) {
      $db=&Database::getInstance();
      $sql = "SELECT COUNT(*) FROM ".XOCP_PREFIX."peer_matrix WHERE peer_job_id0 = '$job_id'";
      $rcnt = $db->query($sql);
      list($peer_count)=$db->fetchRow($rcnt);
      $ret = "<a name='jobeditortop_${job_id}'></a><table style='border:0px;width:100%;'>"
                  . "<colgroup><col width='100'/><col/><col width='200'/></colgroup><tbody>"
                  . "<tr><td>$job_abbr</td>"
                      . "<td><div style='overflow:hidden;width:400px;'><div style='width:900px;'><span id='sp_${job_id}' class='xlnk' onclick='edit_job(\"$job_id\",this,event);'>".stripslashes($job_nm)."</span> (<span id='sppeercount_${job_id}'>$peer_count</span>)</div></div></td>"
                      . "<td><div style='overflow:hidden;width:200px;'><div style='width:900px;' id='dvo_${job_id}'>".htmlentities("$org_nm $org_class_nm",ENT_QUOTES)."</div></div></td>"
                  . "</tr></tbody></table>";
      return $ret;
   }
   
}

} /// HRIS_EDITPEERAJAX_DEFINED
?>