<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_assessmentresult.php            //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2010-05-20                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_ASSESSMENTMRESULTMODIFIERAJAX_DEFINED') ) {
   define('HRIS_ASSESSMENTMRESULTMODIFIERAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");

class _hris_class_AssessmentResultModifierAjax extends AjaxListener {
   
   function _hris_class_AssessmentResultModifierAjax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/hris/class/ajax_assessmentresult.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_modifyForm","app_saveCCL","app_resetCCL",
                            "app_viewBehaviourIndicator","app_viewBehaviourBox",
                            "app_recalculateCCL");
   }
   
   function app_recalculateCCL($args) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/assessment.php");
      $asid = $args[0];
      $employee_id = $args[1];
      $job_id = $args[2];
      _calculate_competency($asid,$employee_id,$job_id);
   }
   
   function app_viewBehaviourBox($args) {
      $db=&Database::getInstance();
      $competency_id = $args[3];
      $ccl = $args[4]+0;
      $sql = "SELECT competency_abbr,competency_nm FROM ".XOCP_PREFIX."competency WHERE competency_id = '$competency_id'";
      $result = $db->query($sql);
      list($competency_abbr,$competency_nm)=$db->fetchRow($result);
      $ret = "<div style='font-size:1em;'>"
           . "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>Behaviour Indicators (CCL : $ccl)</div>";
      
      $ret .= "<div style='padding:10px;'>";
         //////
         $ret .= "<div style='font-weight:bold;font-size:1.1em;color:#333;border:1px solid #bbb;background-color:#ffffdd;padding:5px;text-align:center;'>$competency_abbr - $competency_nm</div>";
            
            ///////////////////
            $ret .= "<div style='padding-top:10px;' id='vx'>";
            $ret .= $this->app_viewBehaviourIndicator($args);
            $ret .= "</div>"; /// vx
            /////////////////
            
            
      $ret .= "</div></div>";
      return array($ret,1);
   }
   
   function app_viewBehaviourIndicator($args) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      $asid = $args[0];
      $employee_id = $args[1];
      $job_id = $args[2];
      $competency_id = $args[3];
      $ccl = $args[4]+0;
      $noback = $args[5]+0;
      
      if($ccl<=0) {
         $ccl = 1;
      }
      if($ccl>4) {
         $ccl=4;
      }
      
      $acl = $ccl;
      
      $sql = "SELECT a.ca_id,a.q_en_txt,a.q_id_txt,a.a_en_txt,a.a_id_txt,a.qa_method,a.behaviour_id,"
           . "b.behaviour_en_txt,b.behaviour_id_txt"
           . " FROM ".XOCP_PREFIX."compbehaviour_qa a"
           . " LEFT JOIN ".XOCP_PREFIX."compbehaviour b USING(competency_id,behaviour_id,proficiency_lvl)"
           . " WHERE a.competency_id = '$competency_id'"
           . " AND a.proficiency_lvl = '$acl'"
           . " ORDER BY a.behaviour_id,a.ca_id";
      $result = $db->query($sql);
      $ret = "<div style='padding:5px;font-weight:bold;'>Behaviour Indicator - Level $acl</div>"
           . "<div id='vxcontent' style='overflow:auto;height:160px;border:1px solid #bbb;padding:10px;'><table style='width:100%;border-spacing:0px;border-top:0px solid #aaa;border-bottom:1px solid #aaa;' padding='2'>"
           . "<colgroup><col width='30'/><col width='30'/><col/></colgroup>"
           . "<tbody>";
      if($db->getRowsNum($result)>0) {
         $old_behaviour = 0;
         while(list($ca_idx,$q_en_txt,$q_id_txt,$a_en_txt,$a_id_txt,$qa_method,$behaviour_id,
                    $behaviour_en_txt,$behaviour_id_txt)=$db->fetchRow($result)) {
            $arr_ca[$behaviour_id][$ca_idx] = array($q_en_txt,$q_id_txt,$a_en_txt,$a_id_txt,$qa_method,$behaviour_en_txt,$behaviour_id_txt);
            $arr_bh[$behaviour_id] = array($behaviour_en_txt,$behaviour_id_txt);
            if($old_behaviour!=$behaviour_id) {
               $old_behaviour=$behaviour_id;
               $ret .= "<tr class='bhv'><td style='border:1px solid #aaa;border-bottom:0px;padding:2px;border-top:1px solid #aaa;text-align:center;'>$behaviour_id</td>"
                     . "<td style='border:1px solid #aaa;border-left:0px;font-weight:bold;color:#444444;padding:2px;border-top:1px solid #aaa;border-bottom:0px;' colspan='3'>$behaviour_en_txt<hr noshade='1' size='1' color='#aaaaaa'/><span style='font-style:italic;'>$behaviour_id_txt</span></td></tr>";
            }
            
            $sql = "SELECT answer FROM ".XOCP_PREFIX."employee_ca_detail"
                 . " WHERE employee_id = '$employee_id'"
                 . " AND competency_id = '$competency_id'"
                 . " AND proficiency_lvl = '$acl'"
                 . " AND behaviour_id = '$behaviour_id'"
                 . " AND ca_id = '$ca_idx'";
            $rca = $db->query($sql);
            _debuglog($sql);
            if($db->getRowsNum($rca)>0) {
               list($answer)=$db->fetchRow($rca);
            } else {
               $answer = 0;
            }
            
            switch($answer) {
               case 1:
                  $cacolor = "background-color:#ffdddd;";
                  $answer_txt = "0";
                  break;
               case 2:
                  $cacolor = "background-color:#ffdddd;";
                  $answer_txt = "0.25";
                  break;
               case 3:
                  $cacolor = "background-color:#ffdddd;";
                  $answer_txt = "0.5";
                  break;
               case 4:
                  $cacolor = "background-color:#ccffff;";
                  $answer_txt = "0.75";
                  break;
               case 5:
                  $cacolor = "background-color:#ccffff;";
                  $answer_txt = "1";
                  break;
               case 0:
               default:
                  $cacolor = "background-color:#ffffff;";
                  $answer_txt = "Empty";
                  break;
            }
            
            $ret .= "<tr class='bhv_ca'>"
                  . "<td style='border-bottom:1px solid #aaa;padding:2px;border:1px solid #aaa;border-top:0px;border-bottom:0px;'>&nbsp;</td>"
                  . "<td style='padding:2px;text-align:center;border-top:1px solid #aaa;border-right:1px solid #aaa;$cacolor'>$ca_idx</td>"
                  . "<td style='border-top:1px solid #aaa;padding:2px;$cacolor'>$q_en_txt<hr noshade='1' size='1' color='#aaaaaa'/><span style='font-style:italic;'>$q_id_txt</span></td>"
                  . "<td style='border-top:1px solid #aaa;padding:4px;text-align:center;border-left:1px solid #aaa;border-right:1px solid #aaa;$cacolor'>$answer_txt</td>"
                  . "</tr>";
         }
      }
      
      $ret .= "</tbody></table></div>";
      
      $ret .= "<div style='padding:10px;text-align:center;'>"
            . "<input type='button' value='Previous' onclick='previous_vx(this,event);'/>&nbsp;"
            . "<input type='button' value='Next' onclick='next_vx(this,event);'/>&nbsp;&nbsp;"
            . ($noback==TRUE?"":"<input type='button' value='Back' onclick='back_vx(this,event);'/>&nbsp;")
            . "<input type='button' value='Close' onclick='asmodresbox.fade();'/>"
            . "</div>";
      
      return $ret;
      
      
   }
   
   function app_resetCCL($args) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/assessment.php");
      $db=&Database::getInstance();
      $user_id = getUserID();
      $asid = $args[0];
      $employee_id = $args[1];
      $job_id = $args[2];
      $competency_id = $args[3];
      $ccl = $args[4]+0;
      
      $sql = "DELETE FROM ".XOCP_PREFIX."employee_competency_final"
           . " WHERE asid = '$asid'"
           . " AND employee_id = '$employee_id'"
           . " AND job_id = '$job_id'"
           . " AND competency_id = '$competency_id'";
      $rc = $db->query($sql);
      _calculate_competency($asid,$employee_id,$job_id);
      
      $sql = "SELECT a.competency_id,a.rcl,a.itj,b.competency_nm,b.competency_abbr,c.compgroup_nm,b.competency_class,d.ccl,(b.competency_class+0) as urcl,"
           . "f.person_nm,b.desc_en,b.desc_id,b.compgroup_id"
           . " FROM ".XOCP_PREFIX."job_competency a"
           . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
           . " LEFT JOIN ".XOCP_PREFIX."compgroup  c USING(compgroup_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee_competency d ON d.employee_id = '$employee_id' AND d.competency_id = b.competency_id"
           . " LEFT JOIN ".XOCP_PREFIX."employee e ON e.employee_id = d.assessor_id"
           . " LEFT JOIN ".XOCP_PREFIX."persons f ON f.person_id = e.person_id"
           . " WHERE a.job_id = '$job_id'"
           . " AND a.competency_id = '$competency_id'";
      $result = $db->query($sql);
      list($competency_id,$rcl,$itj,$competency_nm,$competency_abbr,$compgroup_nm,$cc,$ccl,$urcl,$asr_nm,$desc_en,$desc_id,$compgroup_id)=$db->fetchRow($result);
      
      $asrlist = "<table class=\"asrdtl\" style=\"width:100%;\"><thead><tr><td>Assessor</td><td>Type</td><td>CCL</td></tr></thead>"
               . "<tbody><tr><td>$asr_nm</td><td>Superior</td><td>$ccl</td></tr>";
      
      
      //// 360
      $sql = "SELECT a.ccl,a.assessor_id,c.person_nm,d.assessor_t FROM ".XOCP_PREFIX."employee_competency360 a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b ON b.employee_id = a.assessor_id"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " LEFT JOIN ".XOCP_PREFIX."assessor_360 d ON d.asid = '$asid'"
           . " AND d.employee_id = a.employee_id AND d.assessor_id = a.assessor_id"
           . " AND d.status_cd = 'active'"
           . " WHERE a.employee_id = '$employee_id'"
           . " AND a.competency_id = '$competency_id'"
           . " AND d.asid = '$asid'"
           . " ORDER BY a.ccl DESC";
      $r360 = $db->query($sql);
      if($db->getRowsNum($r360)>0) {
         while(list($ccl360,$asr360_id,$asr360_nm,$assessor_t)=$db->fetchRow($r360)) {
            if($assessor_t=="superior") continue;
            $ccl360 = $ccl360+0;
            $arrccl[$asr360_id] = $ccl360;
            $asrlist .= "<tr><td>-</td><td>$assessor_t</td><td>$ccl360</td></tr>";
         }
      }
      
      $asrlist .= "<tr><td colspan=\"2\" style=\"text-align:left;font-weight:bold;\">Result</td><td style=\"font-weight:bold;\">$old_ccl</td></tr>";
      
      $gapx = ($ccl*$itj)-($rcl*$itj);
      $sql = "SELECT ttlccl,ttlgap,jm,cf FROM ".XOCP_PREFIX."employee_competency_final_recap"
           . " WHERE asid = '$asid'"
           . " AND employee_id = '$employee_id'"
           . " AND job_id = '$job_id'";
      $result = $db->query($sql);
      list($ttlccl,$ttlgap,$jm,$cf)=$db->fetchRow($result);
      return array($competency_id,$ccl,($ccl*$itj),$gapx,$ttlccl,$ttlgap,toMoney($jm)." %",toMoney($cf)." %",$asrlist);
      
   }
   
   
   
   function app_saveCCL($args) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/assessment.php");
      $db=&Database::getInstance();
      $user_id = getUserID();
      $asid = $args[0];
      $employee_id = $args[1];
      $job_id = $args[2];
      $competency_id = $args[3];
      $ccl = $args[4]+0;
      
      $sql = "SELECT a.competency_id,a.rcl,a.itj,b.competency_nm,b.competency_abbr,c.compgroup_nm,b.competency_class,d.ccl,(b.competency_class+0) as urcl,"
           . "f.person_nm,b.desc_en,b.desc_id,b.compgroup_id"
           . " FROM ".XOCP_PREFIX."job_competency a"
           . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
           . " LEFT JOIN ".XOCP_PREFIX."compgroup  c USING(compgroup_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee_competency d ON d.employee_id = '$employee_id' AND d.competency_id = b.competency_id"
           . " LEFT JOIN ".XOCP_PREFIX."employee e ON e.employee_id = d.assessor_id"
           . " LEFT JOIN ".XOCP_PREFIX."persons f ON f.person_id = e.person_id"
           . " WHERE a.job_id = '$job_id'"
           . " AND a.competency_id = '$competency_id'";
      $result = $db->query($sql);
      list($competency_id,$rcl,$itj,$competency_nm,$competency_abbr,$compgroup_nm,$cc,$old_ccl,$urcl,$asr_nm,$desc_en,$desc_id,$compgroup_id)=$db->fetchRow($result);
      
      $asrlist = "<table class=\"asrdtl\" style=\"width:100%;\"><thead><tr><td>Assessor</td><td>Type</td><td>CCL</td></tr></thead>"
               . "<tbody><tr><td>-</td><td>Superior</td><td>$ccl</td></tr>";
      
      $gapx = ($ccl*$itj)-($rcl*$itj);
      if($old_ccl==$ccl) {
         $sql = "REPLACE INTO ".XOCP_PREFIX."employee_competency_final (asid,employee_id,job_id,competency_id,ccl,rcl,itj,gap,updated_dttm,is_modified,last_modified_dttm,last_modified_user_id)"
              . " VALUES ('$asid','$employee_id','$job_id','$competency_id','$ccl','$rcl','$itj','$gapx',now(),'0','0000-00-00 00:00:00','0')";
         $db->query($sql);
      } else {
         $sql = "REPLACE INTO ".XOCP_PREFIX."employee_competency_final (asid,employee_id,job_id,competency_id,ccl,rcl,itj,gap,updated_dttm,is_modified,last_modified_dttm,last_modified_user_id)"
              . " VALUES ('$asid','$employee_id','$job_id','$competency_id','$ccl','$rcl','$itj','$gapx',now(),'1',now(),'$user_id')";
         $db->query($sql);
      }
      _calculate_competency($asid,$employee_id,$job_id);
      
      $sql = "SELECT a.ccl,a.is_modified,a.last_modified_dttm,c.person_nm"
           . " FROM ".XOCP_PREFIX."employee_competency_final a"
           . " LEFT JOIN ".XOCP_PREFIX."users b ON b.user_id = a.last_modified_user_id"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " WHERE a.asid = '$asid'"
           . " AND a.employee_id = '$employee_id'"
           . " AND a.job_id = '$job_id'"
           . " AND a.competency_id = '$competency_id'";
      $rc = $db->query($sql);
      if($db->getRowsNum($rc)>0) {
         list($final_ccl,$final_modified,$last_modified_dttm,$modify_person)=$db->fetchRow($rc);
      } else {
         $final_ccl = $final_modified = 0;
      }
      
      //// 360
      $sql = "SELECT a.ccl,a.assessor_id,c.person_nm,d.assessor_t FROM ".XOCP_PREFIX."employee_competency360 a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b ON b.employee_id = a.assessor_id"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " LEFT JOIN ".XOCP_PREFIX."assessor_360 d ON d.asid = '$asid'"
           . " AND d.employee_id = a.employee_id AND d.assessor_id = a.assessor_id"
           . " AND d.status_cd = 'active'"
           . " WHERE a.employee_id = '$employee_id'"
           . " AND a.competency_id = '$competency_id'"
           . " AND d.asid = '$asid'"
           . " ORDER BY a.ccl DESC";
      $r360 = $db->query($sql);
      if($db->getRowsNum($r360)>0) {
         while(list($ccl360,$asr360_id,$asr360_nm,$assessor_t)=$db->fetchRow($r360)) {
            if($assessor_t=="superior") continue;
            $ccl360 = $ccl360+0;
            $arrccl[$asr360_id] = $ccl360;
            $asrlist .= "<tr><td>-</td><td>$assessor_t</td><td>$ccl360</td></tr>";
         }
      }
      
      if($final_modified==1) {
         $asrlist .= "<tr><td style=\"background-color:#ddffff;\">$modify_person</td><td style=\"background-color:#ddffff;\">Alter</td><td style=\"background-color:#ddffff;\">$final_ccl</td></tr>";
         $asrlist .= "<tr><td colspan=\"2\" style=\"text-align:left;font-weight:bold;\">Result</td><td style=\"font-weight:bold;\">$final_ccl</td></tr>";
         $calc_ccl = $final_ccl;
      } else {
         $asrlist .= "<tr><td colspan=\"2\" style=\"text-align:left;font-weight:bold;\">Result</td><td style=\"font-weight:bold;\">$old_ccl</td></tr>";
      }
      
      $asrlist .= "<tr><td colspan=\"3\" style=\"text-align:center;color:blue;\">Click to alter result.</td></tr>";
      $asrlist .= "</tbody></table>";
      
      $sql = "SELECT ttlccl,ttlgap,jm,cf FROM ".XOCP_PREFIX."employee_competency_final_recap"
           . " WHERE asid = '$asid'"
           . " AND employee_id = '$employee_id'"
           . " AND job_id = '$job_id'";
      $result = $db->query($sql);
      list($ttlccl,$ttlgap,$jm,$cf)=$db->fetchRow($result);
      return array($competency_id,$ccl,($ccl*$itj),$gapx,$ttlccl,$ttlgap,toMoney($jm)." %",toMoney($cf)." %",$asrlist);
      
   }
   
   function app_modifyForm($args) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/assessment.php");
      $db=&Database::getInstance();
      $asid = $args[0];
      $employee_id = $args[1];
      $job_id = $args[2];
      $competency_id = $args[3];
      
      $sql = "SELECT c.job_nm,c.job_abbr,d.org_nm,d.org_abbr,a.employee_ext_id,e.person_nm,e.person_id"
           . " FROM ".XOCP_PREFIX."employee a"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job b ON b.employee_id = a.employee_id AND b.job_id = '$job_id'"
           . " LEFT JOIN ".XOCP_PREFIX."jobs c ON c.job_id = '$job_id'"
           . " LEFT JOIN ".XOCP_PREFIX."orgs d ON d.org_id = c.org_id"
           . " LEFT JOIN ".XOCP_PREFIX."persons e ON e.person_id = a.person_id"
           . " WHERE a.employee_id = '$employee_id'";
      $result = $db->query($sql);
      list($job_nm,$job_abbr,$org_nm,$org_abbr,$nip,$employee_nm,$person_id)=$db->fetchRow($result);
      
      $info = "<table><tr><td style='padding:4px;border:1px solid #bbb;'><img src='".XOCP_SERVER_SUBDIR."/modules/hris/thumb.php?pid=${person_id}' height='100'/></td>"
            . "<td style='vertical-align:top;padding-left:10px;'>"
            . "<table style='font-weight:bold;margin-left:0px;font-size:1.1em;'><colgroup><col width='120'/><col/></colgroup><tbody>"
            . "<tr><td>Job Title</td><td>: $job_nm ($job_abbr)</td></tr>"
            . "<tr><td>Section/Division</td><td>: $org_nm ($org_abbr)</td></tr>"
            . "<tr><td>Incumbent</td><td>: $employee_nm</td></tr>"
            . "<tr><td>NIP</td><td>: $nip</td></tr>"
            . "</tbody></table></td></tr></table>";
      
      $sql = "SELECT a.competency_id,a.rcl,a.itj,b.competency_nm,b.competency_abbr,c.compgroup_nm,b.competency_class,d.ccl,(b.competency_class+0) as urcl,"
           . "f.person_nm,b.desc_en,b.desc_id,b.compgroup_id,d.cclxxx"
           . " FROM ".XOCP_PREFIX."job_competency a"
           . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
           . " LEFT JOIN ".XOCP_PREFIX."compgroup  c USING(compgroup_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee_competency_session d ON d.asid = '$asid' AND d.employee_id = '$employee_id' AND d.competency_id = b.competency_id"
           . " LEFT JOIN ".XOCP_PREFIX."employee e ON e.employee_id = d.assessor_id"
           . " LEFT JOIN ".XOCP_PREFIX."persons f ON f.person_id = e.person_id"
           . " WHERE a.job_id = '$job_id'"
           . " AND a.competency_id = '$competency_id'";
      $result = $db->query($sql);
      list($competency_id,$rcl,$itj,$competency_nm,$competency_abbr,$compgroup_nm,$cc,$ccl,$urcl,$asr_nm,$desc_en,$desc_id,$compgroup_id,$cclxxx)=$db->fetchRow($result);
      
      $ccl = $ccl+0;
      $arrccl = array();
      //$arrccl["superior"] = $ccl;
      
      $asrlist = "<table class=\"asrdtl\" style='border-bottom:0px;'>"
               . "<colgroup><col width='400'/><col width='120'/><col width='70'/></colgroup>"
               . "<thead><tr><td style=''>Assessment Progress</td><td>Type</td><td>CCL</td></tr></thead>"
               . "</table>";
      
      $asrlist .= "<div style='overflow:auto;max-height:100px;width:609px;'>"
                . "<table class=\"asrdtl\" style='border-top:0px;'>"
                . "<colgroup><col width='400'/><col width='120'/><col width='70'/></colgroup>"
                . "<tbody style='overflow:auto;max-height:50px;'>";
            
      /// list($arrccl,$arrasr,$calc_ccl) = _get_arrccl($asid,$employee_id,$competency_id,$job_id);
      list($arrccl,$arrasr,$calc_ccl,$org_calc_ccl,$arravg,$arrcclxxx,$calc_cclxxx,$arravgxxx,$arrasrxxx) = _get_arrccl($asid,$employee_id,$competency_id,$job_id);
      foreach($arrasr as $k=>$v) {
         list($ccl360,$asr360_id,$asr360_nm,$assessor_t,$finish_status,$fulfilled)=$v;
         $asrlist .= "<tr><td>$finish_status</td><td>$assessor_t</td><td>".number_format($ccl360,2,".","")."</td></tr>";
      }
      
      $asrlist .= "</tbody>";
      $asrlist .= "</table>";
      $asrlist .= "</div>";
      
      
      $sql = "SELECT a.cclxxx,a.ccl,a.is_modified,a.last_modified_dttm,c.person_nm"
           . " FROM ".XOCP_PREFIX."employee_competency_final a"
           . " LEFT JOIN ".XOCP_PREFIX."users b ON b.user_id = a.last_modified_user_id"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " WHERE a.asid = '$asid'"
           . " AND a.employee_id = '$employee_id'"
           . " AND a.job_id = '$job_id'"
           . " AND a.competency_id = '$competency_id'";
      $rc = $db->query($sql);
      if($db->getRowsNum($rc)>0) {
         list($final_ccl,$final_ccl_old,$final_modified,$last_modified_dttm,$modify_person)=$db->fetchRow($rc);
         $altered_ccl = $final_ccl;
      } else {
         $final_ccl = $final_modified = 0;
      }
      
      if($final_modified==1) {
         $altered_ccl = $final_ccl;
      } else {
         $altered_ccl = $calc_cclxxx;
      }
      
      $ret = "<div style='font-size:0.9em;'>"
           . "<div style='height:21px;padding-top:7px;text-align:center;background-color:#6d84b4;font-weight:bold;color:#eee;font-size:1.1em;'>Competency Assessment Result Alteration</div>";
      $ret .= "<div style='padding:10px;'>$info</div>";
      
      $ret .= "<div style='padding:10px;padding-top:0px;'>";
         //////
         $ret .= "<div style='font-weight:bold;font-size:1.1em;color:#333;border:1px solid #bbb;background-color:#ffffdd;padding:5px;text-align:center;'>$competency_abbr - $competency_nm</div>";
            
            ///////////////////
            $ret .= "<div style='padding-top:10px;' id='vx'>";
            
            $ret .= "<div style='padding-top:0px;'>$asrlist</div>";
            
            $ret .= "<div style='padding-top:10px;'>"
                  . "<table class='xxfrm' align='center' style='width:90%;'><colgroup><col width='50%'/><col/></colgroup><tbody>"
                  . "<tr><td>RCL:</td><td>$rcl</td></tr>"
                  . "<tr><td>ITJ:</td><td>$itj</td></tr>"
                  . "<tr><td>Calculated CCL Value:</td><td>$calc_cclxxx</td></tr>"
                  . "<tr><td>Altered CCL Value :</td><td><input type='text' id='altered_ccl' value='$altered_ccl' disabled='1' style='text-align:center;font-size:1.1em;width:50px;' onclick='_dsa(this);'/></td></tr>"
                  . "<tr><td style='vertical-align:top;'>Alteration Status:</td><td style='vertical-align:top;'>".($final_modified==1?"Altered at ".sql2ind($last_modified_dttm)."<br/>by $modify_person":"Unaltered")."</td></tr>"
                  . "</tbody></table></div>"; //// div rekap
            
            $ret .= "<div style='text-align:center;padding:10px;' id='dvbtnalter'>"
                  //. "<input type='button' value='View Behaviour Indicator' onclick='view_behaviour_indicator(this,event);'/>&nbsp;&nbsp;"
                  //. "<input type='button' disabled='1' value='Save Alteration' onclick='save_ccl(this,event);'/>&nbsp;"
                  . ($final_modified==1?"<input type='button' value='Cancel Alteration' onclick='reset_ccl(this,event);'/>&nbsp;":"")
                  . "<input type='button' value='Close' onclick='asmodresbox.fade();'/>"
                  . "</div>"; //// dvbtnalter
            //////
            $ret .= "</div>"; /// vx
            /////////////////
            
            
      $ret .= "</div></div>";
      return array($ret,min(570,(490+(25*$ascntx))));
   }
   
   
}

} /// HRIS_ASSESSMENTMRESULTMODIFIERAJAX_DEFINED
?>