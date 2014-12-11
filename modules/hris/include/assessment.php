<?php
if ( !defined('HRIS_ASSESSMENTFUNCTIONS_DEFINED') ) {
   define('HRIS_ASSESSMENTFUNCTIONS_DEFINED', TRUE);
   
   function _isunder($org_id,$job_org_id) {
      $db=&Database::getInstance();
      if($org_id==$job_org_id) {
         return TRUE;
      }
      
      $sql = "SELECT parent_id FROM ".XOCP_PREFIX."orgs WHERE org_id = '$job_org_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($parent_org_id)=$db->fetchRow($result)) {
            return _isunder($org_id,$parent_org_id);
         }
      }
      
      return FALSE;
   }
   
   function _asm_get_schedule($asid) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $db=&Database::getInstance();
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
           $first_assessor_job_id,
           $next_assessor_job_id)=_hris_getinfobyuserid($user_id);
      
      $sql = "SELECT org_id FROM ".XOCP_PREFIX."jobs WHERE job_id = '$self_job_id'";
      $result = $db->query($sql);
      list($job_org_id)=$db->fetchRow($result);
      $sql = "SELECT asid,assessment_start,assessment_stop,session_nm,session_periode"
           . " FROM ".XOCP_PREFIX."assessment_session"
           . " WHERE asid = '$asid'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($asid,$startx,$stopx,$nm,$periode)=$db->fetchRow($result);
         
         $sql = "SELECT schedule_id,org_id,start_dttm,stop_dttm FROM ".XOCP_PREFIX."assessment_schedule"
              . " WHERE stop_dttm >= now()"
              . " AND start_dttm <= now()"
              . " AND status_cd = 'normal'"
              . " AND asid = '$asid'";
         $rs = $db->query($sql);
         if($db->getRowsNum($rs)>0) {
            while(list($schedule_id,$org_id,$start_dttm,$stop_dttm)=$db->fetchRow($rs)) {
               $is_under = _isunder($org_id,$job_org_id);
               if($is_under===TRUE) {
                  return array($asid,$start_dttm,$stop_dttm,$nm,$periode,$schedule_id);
               
               }
            }
         }
         return FALSE;
      } else {
         return FALSE;
      }
   
   }
   
   function _get_asid() {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $db=&Database::getInstance();
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
           $first_assessor_job_id,
           $next_assessor_job_id)=_hris_getinfobyuserid($user_id);
      
      $sql = "SELECT org_id FROM ".XOCP_PREFIX."jobs WHERE job_id = '$self_job_id'";
      $result = $db->query($sql);
      list($job_org_id)=$db->fetchRow($result);
      $sql = "SELECT asid,assessment_start,assessment_stop,session_nm,session_periode"
           . " FROM ".XOCP_PREFIX."assessment_session"
           . " WHERE status_cd = 'normal'"
           //. " AND assessment_stop >= now()"
           //. " AND assessment_start <= now()"
           . " ORDER BY assessment_start DESC LIMIT 1";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($asid,$start,$stop,$nm,$periode)=$db->fetchRow($result);
         
         $sql = "SELECT schedule_id,org_id FROM ".XOCP_PREFIX."assessment_schedule"
              . " WHERE stop_dttm >= now()"
              . " AND start_dttm <= now()"
              . " AND asid = '$asid'";
         $rs = $db->query($sql);
         if($db->getRowsNum($rs)>0) {
            while(list($schedule_id,$org_id)=$db->fetchRow($rs)) {
               $is_under = _isunder($org_id,$job_org_id);
               if($is_under===TRUE) {
                  return array($asid,$start,$stop,$nm,$periode);
               
               }
            }
         }
         return FALSE;
      } else {
         return FALSE;
      }
   }
   
   
   function _get_last_asid() {
      $db=&Database::getInstance();
      $sql = "SELECT a.asid,a.session_nm,a.session_periode,c.job_nm"
           . " FROM ".XOCP_PREFIX."assessment_session a"
           . " LEFT JOIN ".XOCP_PREFIX."assessment_session_job b ON b.asid = a.asid AND b.employee_id = '$employee_id'"
           . " LEFT JOIN ".XOCP_PREFIX."jobs c USING(job_id)"
           . " WHERE a.status_cd = 'normal'"
           . " ORDER BY a.session_periode DESC";
      
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($asid,$session_nm,$session_periode,$job_nm)=$db->fetchRow($result)) {
            return $asid;
         }
      }
   }
   
   function _calculate_competency($asid,$employee_id,$job_id) {
      $db=&Database::getInstance();
      $user_id = getUserID();
      
      $sql = "SELECT a.competency_id,a.rcl,a.itj,b.competency_nm,c.compgroup_nm,b.competency_class,d.ccl,(b.competency_class+0) as urcl,"
           . "f.person_nm,b.desc_en,b.desc_id,b.compgroup_id,b.status_cd"
           . " FROM ".XOCP_PREFIX."assessment_session_job_competency a"
           . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
           . " LEFT JOIN ".XOCP_PREFIX."compgroup  c USING(compgroup_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee_competency_session d ON d.asid = '$asid' AND d.employee_id = '$employee_id' AND d.competency_id = b.competency_id"
           . " LEFT JOIN ".XOCP_PREFIX."assessor_360 ac ON ac.asid = d.asid AND ac.employee_id = '$employee_id' AND ac.assessor_id = d.assessor_id AND ac.assessor_t = 'superior'" /// untuk memfilter anomali 2014-02-27
           . " LEFT JOIN ".XOCP_PREFIX."employee e ON e.employee_id = d.assessor_id"
           . " LEFT JOIN ".XOCP_PREFIX."persons f ON f.person_id = e.person_id"
           . " WHERE a.asid = '$asid' AND a.job_id = '$job_id'"
           . " AND ac.assessor_t = 'superior'" ///// untuk memfilter anomali 2014-02-27
           . " ORDER BY b.compgroup_id,urcl,b.competency_id";
      $result = $db->query($sql);
      $modified_count = 0;
      $ttlccl = $ttlrcl = $ttlgap = 0;
      if($db->getRowsNum($result)>0) {
         while(list($competency_id,$rcl,$itj,$competency_nm,$compgroup_nm,$cc,$ccl,$urcl,$asr_nm,$desc_en,$desc_id,$compgroup_id,$comp_status_cd)=$db->fetchRow($result)) {
            
            /// competency fit
            if($compgroup_id==1||$compgroup_id==2) {
               $cf_compgroup[$compgroup_id][$competency_id] = array($competency_id,$competency_nm,$compgroup_nm);
            }
            
            $cc = ucfirst($cc);
            $ccl = $ccl+0;
            $arrccl = array();
            
            list($arrccl,$arrasr,$calc_ccl,$org_calc_ccl,$arravg,$arrcclxxx,$calc_cclxxx,$arravgxxx,$arrasrxxx) = _get_arrccl($asid,$employee_id,$competency_id,$job_id);
            
            /*
            foreach($arrasr as $k=>$v) {
               list($ccl360,$asr360_id,$asr360_nm,$assessor_t,$finish_status,$fulfilled)=$v;
               if($assessor_t=="superior") {
                  $sql = "UPDATE ".XOCP_PREFIX."employee_competency SET ccl = '$ccl360'"
                       . " WHERE employee_id = '$employee_id'"
                       . " AND competency_id = '$competency_id'"
                       . " AND assessor_id = '$asr360_id'";
                  $db->query($sql);
                  
                  $sql = "UPDATE ".XOCP_PREFIX."employee_competency_session SET ccl = '$ccl360'"
                       . " WHERE employee_id = '$employee_id'"
                       . " AND competency_id = '$competency_id'"
                       . " AND asid = '$asid'"
                       . " AND assessor_id = '$asr360_id'";
                  $db->query($sql);
                  
               } else {
                  $sql = "UPDATE ".XOCP_PREFIX."employee_competency360 SET ccl = '$ccl360'"
                       . " WHERE employee_id = '$employee_id'"
                       . " AND competency_id = '$competency_id'"
                       . " AND assessor_id = '$asr360_id'";
                  $db->query($sql);
                  $sql = "UPDATE ".XOCP_PREFIX."employee_competency360_session SET ccl = '$ccl360'"
                       . " WHERE employee_id = '$employee_id'"
                       . " AND competency_id = '$competency_id'"
                       . " AND asid = '$asid'"
                       . " AND assessor_id = '$asr360_id'";
                  $db->query($sql);
               }
            }
            */
            
            $gapx = ($calc_ccl*$itj)-($rcl*$itj);
            $gapxxxx = ($calc_cclxxx*$itj)-($rcl*$itj);
            
            $sql = "SELECT is_modified,ccl,gap,cclxxx,gapxxx FROM ".XOCP_PREFIX."employee_competency_final"
                 . " WHERE asid = '$asid'"
                 . " AND employee_id = '$employee_id'"
                 . " AND job_id = '$job_id'"
                 . " AND competency_id = '$competency_id'";
            $rc = $db->query($sql);
            if($db->getRowsNum($rc)>0) {
               list($is_modified,$mod_ccl,$mod_gap,$mod_cclxxx,$mod_gapxxx)=$db->fetchRow($rc);
               if($is_modified==1) {
                  $calc_ccl = $mod_ccl; /// commented on 2012-01-16
                  $calc_cclxxx = $mod_cclxxx; /// added 2012-01-16
                  $gapx = $mod_gap; /// commented on 2012-01-16
                  $gapxxxx = $mod_gapxxx; /// added 2012-01-16
                  $modified_count++;
               }
            } else {
               $is_modified=0;
            }
            
            if($is_modified==0) {
               $sql = "REPLACE INTO ".XOCP_PREFIX."employee_competency_final (asid,employee_id,job_id,competency_id,ccl,rcl,itj,gap,updated_dttm,is_modified,last_modified_dttm,last_modified_user_id,cclxxx,gapxxx)"
                    . " VALUES ('$asid','$employee_id','$job_id','$competency_id','$calc_ccl','$rcl','$itj','$gapx',now(),'0','0000-00-00 00:00:00','0','$calc_cclxxx','$gapxxxx')";
               $db->query($sql);
            }
            
            if($gapx>=0) {
               if($compgroup_id==1||$compgroup_id==2) {
                  $cf_pass[$compgroup_id][$competency_id] = 1;
               }
            }
            
            if($gapxxxx>=-(0.25*$rcl*$itj)) {
               if($compgroup_id==1||$compgroup_id==2) {
                  $cf_passxxx[$compgroup_id][$competency_id] = 1;
               }
            }
            
            //if(count($arrccl)>0) {
               $ttlccl += ($calc_ccl*$itj);
               $ttlcclxxx += ($calc_cclxxx*$itj);
               $ttlrcl += ($rcl*$itj);
               
               $rclitj = $rcl*$itj;
               
               $ttlgap += (($calc_ccl-$rcl)*$itj);
               $ttlgapxxx += (($calc_cclxxx-$rcl)*$itj);
            //}
            
         }
         
         if($ttlrcl==0) {
            $match = 0;
            $matchxxx = 0;
         } else {
            $match = _bctrim(100*$ttlccl/$ttlrcl);
            $matchxxx = _bctrim(100*$ttlcclxxx/$ttlrcl);
         }
         
         
         /// competency fit
         $cf_cnt = $cf_pass_cnt = 0;
         foreach($cf_compgroup as $cg=>$x) {
            if(isset($cf_compgroup[$cg])) $cf_cnt += count($cf_compgroup[$cg]);
            if(isset($cf_pass[$cg])) $cf_pass_cnt += count($cf_pass[$cg]);
         }
         
         /// competency fit
         $cf_cntxxx = $cf_pass_cntxxx = 0;
         foreach($cf_compgroup as $cg=>$x) {
            if(isset($cf_compgroup[$cg])) $cf_cntxxx += count($cf_compgroup[$cg]);
            if(isset($cf_passxxx[$cg])) $cf_pass_cntxxx += count($cf_passxxx[$cg]);
         }
         
         if($cf_cnt>0) {
            $cf = _bctrim(bcmul(100,bcdiv($cf_pass_cnt,$cf_cnt)));
         } else {
            $cf = 0;
         }
         
         if($cf_cntxxx>0) {
            $cfxxx = _bctrim(bcmul(100,bcdiv($cf_pass_cntxxx,$cf_cntxxx)));
         } else {
            $cfxxx = 0;
         }
         
         $sql = "REPLACE INTO ".XOCP_PREFIX."employee_competency_final_recap (asid,employee_id,job_id,ttlccl,ttlrcl,ttlgap,jm,cf,updated_dttm,is_modified,ttlcclxxx,ttlgapxxx,jmxxx,cfxxx)"
              . " VALUES('$asid','$employee_id','$job_id','$ttlccl','$ttlrcl','$ttlgap','$match','$cf',now(),'".($modified_count>0?"1":"0")."','$ttlcclxxx','$ttlgapxxx','$matchxxx','$cfxxx')";
         $db->query($sql);
      }
   }
   
   function _get_arrccl($asid,$employee_id,$competency_id,$job_id) {
      $db=&Database::getInstance();
      $arrcclxxx = array(); /// tambahan 2012-01-16
      $arrasrxxx = array(); /// tambahan 2012-01-16
      $arrccl = array();
      $arrasr = array();
      $arrtype = array();
      
      
      $sql = "SELECT weight_superior,weight_peer,weight_subordinate,weight_customer"
           . " FROM ".XOCP_PREFIX."assessment_session"
           . " WHERE asid = '$asid'";
      $result = $db->query($sql);
      list($weight_superior,$weight_peer,$weight_subordinate,$weight_customer)=$db->fetchRow($result);
      
      $arrweight["superior"] = $weight_superior;
      $arrweight["peer"] = $weight_peer;
      $arrweight["subordinat"] = $weight_subordinate;
      $arrweight["customer"] = $weight_customer;
      
      $sql = "SELECT compgroup_id FROM ".XOCP_PREFIX."competency WHERE competency_id = '$competency_id'";
      $result = $db->query($sql);
      list($compgroup_id)=$db->fetchRow($result);
      
      if($compgroup_id==3) {
         $answer_t = "grade";
      } else {
         $answer_t = "yesno";
      }
      
      if($asid >= 10) $answer_t = "grade";
      
      $sql = "SELECT a.rcl,a.itj"
           . " FROM ".XOCP_PREFIX."job_competency a"
           . " WHERE a.job_id = '$job_id' AND competency_id = '$competency_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($rcl,$itj)=$db->fetchRow($result);
      }
      
      /// superior
      $sql = "SELECT a.ccl,a.assessor_id,c.person_nm,d.assessor_t,e.fulfilled,e.level_value FROM ".XOCP_PREFIX."employee_competency_session a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b ON b.employee_id = a.assessor_id"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " LEFT JOIN ".XOCP_PREFIX."assessor_360 d ON d.asid = a.asid AND d.employee_id = a.employee_id AND d.assessor_id = a.assessor_id AND d.status_cd = 'active'"
           . " LEFT JOIN ".XOCP_PREFIX."employee_level_session e ON e.asid = a.asid AND e.employee_id = a.employee_id AND e.competency_id = a.competency_id AND e.assessor_id = a.assessor_id AND e.proficiency_lvl = CONVERT(FLOOR(a.ccl),CHAR)"
           . " WHERE a.asid = '$asid' AND a.employee_id = '$employee_id'"
           . " AND a.competency_id = '$competency_id'"
           . " ORDER BY a.ccl DESC";
      $r360 = $db->query($sql);
      if($db->getRowsNum($r360)>0) {
         while($row=$db->fetchRow($r360)) {
            list($ccl360,$asr360_id,$asr360_nm, $assessor_t ,$fulfilled,$level_value)=$row;
            
            if($assessor_t!="superior") continue;
            
            $ccl360 = $ccl360+0;
            if($ccl360>($rcl+1)) $ccl360=$rcl+1;
            if($ccl360>4) $ccl360=4;
            
            
            
            
            if($ccl360<$rcl) {
               $sql = "SELECT fulfilled,level_value FROM ".XOCP_PREFIX."employee_level_session"
                    . " WHERE employee_id = '$employee_id'"
                    . " AND competency_id = '$competency_id'"
                    . " AND assessor_id = '$asr360_id'"
                    . " AND asid = '$asid'"
                    . " ORDER BY proficiency_lvl DESC LIMIT 1";
               $r0 = $db->query($sql);
               if($db->getRowsNum($r0)==1) {
                  list($fulfilled_last,$level_value_last)=$db->fetchRow($r0);
                  if($fulfilled_last!=-1) {
                     $arrtype[$assessor_t][$asr360_id] = 1;
                     $arrccl[$asr360_id] = $ccl360;
                     $arrasr[$asr360_id] = array($ccl360,$asr360_id,$asr360_nm,$assessor_t,"Unfinished",$fulfilled);
                     continue;
                  }
               }
            }
            
            $sql = "SELECT fulfilled,level_value FROM ".XOCP_PREFIX."employee_level_session"
                 . " WHERE employee_id = '$employee_id'"
                 . " AND competency_id = '$competency_id'"
                 . " AND assessor_id = '$asr360_id'"
                 . " AND asid = '$asid'"
                 . " ORDER BY proficiency_lvl ASC";
            $r0 = $db->query($sql);
            $ccl_xxx = 0;
            if($db->getRowsNum($r0)>0) {
               while(list($fulfilled_x,$level_value_x)=$db->fetchRow($r0)) {
                  $ccl_xxx = _bctrim(bcadd($ccl_xxx,$level_value_x));
               }
               
            }
            
            if($ccl_xxx>=1&&$fulfilled==0) {
               $arrasrxxx[$asr360_id] = array($ccl_xxx,$asr360_id,$asr360_nm,$assessor_t,"Unfinished",$fulfilled); /// addedd 2012-01-16
               continue;
            }
            
            if($ccl360>=1&&$fulfilled==0) {
               $arrasr[$asr360_id] = array($ccl360,$asr360_id,$asr360_nm,$assessor_t,"Unfinished",$fulfilled);
               continue;
            }
            
            if($ccl360==0) {
               $sql = "SELECT fulfilled,level_value FROM ".XOCP_PREFIX."employee_level_session"
                    . " WHERE employee_id = '$employee_id'"
                    . " AND competency_id = '$competency_id'"
                    . " AND assessor_id = '$asr360_id'"
                    . " AND proficiency_lvl = '1'"
                    . " AND asid = '$asid'";
               $r0 = $db->query($sql);
               if($db->getRowsNum($r0)==1) {
                  list($fulfilled,$level_value)=$db->fetchRow($r0);
               }
            }
            
            if($ccl360==0&&$fulfilled==0) {
               /// $arrasr[$asr360_id] = array($ccl360,$asr360_id,$asr360_nm,$assessor_t,"Unfinished",$fulfilled);
               /// abstain is here
               continue;
            }
            $arrtype[$assessor_t][$asr360_id] = 1;
            $arrccl[$asr360_id] = $ccl360;
            $arrasr[$asr360_id] = array($ccl360,$asr360_id,$asr360_nm,$assessor_t,"Finished",$fulfilled);
            $arrasrxxx[$asr360_id] = array($ccl_xxx,$asr360_id,$asr360_nm,$assessor_t,"Finished",$fulfilled); /// tambahan 2012-01-16
            $arrcclxxx[$asr360_id] = $ccl_xxx; /// tambahan 2012-01-16
            
         }
      }
      
      
      //// 360
      $sql = "SELECT a.ccl,a.assessor_id,c.person_nm,d.assessor_t,e.fulfilled,a.asid_update FROM ".XOCP_PREFIX."employee_competency360_session a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b ON b.employee_id = a.assessor_id"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " LEFT JOIN ".XOCP_PREFIX."assessor_360 d ON d.asid = a.asid AND d.employee_id = a.employee_id AND d.assessor_id = a.assessor_id AND d.status_cd = 'active'"
           . " LEFT JOIN ".XOCP_PREFIX."employee_level360_session e ON e.asid = a.asid AND e.employee_id = a.employee_id AND e.competency_id = a.competency_id AND e.assessor_id = a.assessor_id AND e.proficiency_lvl = CONVERT(FLOOR(a.ccl),CHAR)"
           . " WHERE a.employee_id = '$employee_id'"
           . " AND a.competency_id = '$competency_id'"
           . " AND a.asid = '$asid'"
           . " ORDER BY a.ccl DESC";
      $r360 = $db->query($sql);
      if($db->getRowsNum($r360)>0) {
         while(list($ccl360,$asr360_id,$asr360_nm,$assessor_t,$fulfilled,$asid_update)=$db->fetchRow($r360)) {
            
            if($assessor_t=="superior") continue;
            
            $ccl360 = $ccl360+0;
            if($ccl360>($rcl+1)) $ccl360=$rcl+1;
            if($ccl360>4) $ccl360=4;
            
            if($ccl360<$rcl) {
               $sql = "SELECT fulfilled,level_value FROM ".XOCP_PREFIX."employee_level360_session"
                    . " WHERE employee_id = '$employee_id'"
                    . " AND competency_id = '$competency_id'"
                    . " AND assessor_id = '$asr360_id'"
                    . " AND asid = '$asid'"
                    . " ORDER BY proficiency_lvl DESC LIMIT 1";
               $r0 = $db->query($sql);
               if($db->getRowsNum($r0)==1) {
                  list($fulfilled_last,$level_value_last)=$db->fetchRow($r0);
                  if($fulfilled_last!=-1) {
                     $arrasr[$asr360_id] = array($ccl360,$asr360_id,$asr360_nm,$assessor_t,"Unfinished",$fulfilled);
                     continue;
                  }
               }
            }
            
            $sql = "SELECT a.fulfilled,a.level_value FROM ".XOCP_PREFIX."assessor_360 b"
                 . " LEFT JOIN ".XOCP_PREFIX."employee_level360_session a ON a.asid = b.asid AND a.employee_id = b.employee_id AND a.assessor_id = b.assessor_id"
                 . " WHERE a.asid = '$asid' AND b.employee_id = '$employee_id'"
                 . " AND a.competency_id = '$competency_id'"
                 . " AND b.assessor_id = '$asr360_id'"
                 . " AND b.assessor_t = '$assessor_t'"
                 . " AND b.status_cd = 'active'"
                 . " ORDER BY a.proficiency_lvl ASC";
            $r0 = $db->query($sql);
            $ccl_xxx = 0;
            if($db->getRowsNum($r0)>0) {
               while(list($fulfilled_x,$level_value_x)=$db->fetchRow($r0)) {
                  $ccl_xxx = _bctrim(bcadd($ccl_xxx,$level_value_x));
               }
            } else {
               continue;
            }
            
            if($ccl_xxx>=1&&$fulfilled==0) {
               $arrasrxxx[$asr360_id] = array($ccl_xxx,$asr360_id,$asr360_nm,$assessor_t,"Unfinished",$fulfilled); /// addedd 2012-01-16
               continue;
            }
            
            if($ccl360>=1&&$fulfilled==0) {
               $arrasr[$asr360_id] = array($ccl360,$asr360_id,$asr360_nm,$assessor_t,"Unfinished",$fulfilled);
               continue;
            }
            
            if($ccl360==0) {
               $sql = "SELECT fulfilled,level_value FROM ".XOCP_PREFIX."employee_level360_session"
                    . " WHERE employee_id = '$employee_id'"
                    . " AND competency_id = '$competency_id'"
                    . " AND assessor_id = '$asr360_id'"
                    . " AND asid = '$asid'"
                    . " AND proficiency_lvl = '1'";
               $r0 = $db->query($sql);
               if($db->getRowsNum($r0)==1) {
                  list($fulfilled,$level_value)=$db->fetchRow($r0);
               }
            }
            
            if($ccl360==0&&$fulfilled==0) {
               /// $arrasr[$asr360_id] = array($ccl360,$asr360_id,$asr360_nm,$assessor_t,"Unfinished",$fulfilled);
               /// abstain is here
               continue;
            }
            
            if($ccl_xxx==0&&$fullfilled==0) {
               /// $arrasr[$asr360_id] = array($ccl360,$asr360_id,$asr360_nm,$assessor_t,"Unfinished",$fulfilled);
               /// abstain is here
               continue;
            }
            
            $arrtype[$assessor_t][$asr360_id] = 1;
            $arrccl[$asr360_id] = $ccl360;
            $arrasr[$asr360_id] = array($ccl360,$asr360_id,$asr360_nm,$assessor_t,"Finished",$fulfilled);
            $arrcclxxx[$asr360_id] = $ccl_xxx; /// tambahan 2012-01-16
            $arrasrxxx[$asr360_id] = array($ccl_xxx,$asr360_id,$asr360_nm,$assessor_t,"Finished",$fulfilled); /// tambahan 2012-01-16
            
         }
      }
      
      arsort($arrccl);
      arsort($arrcclxxx); /// tambahan 2012-01-16
      
      /* ///// old failed voting method //////////////////////////////
      $ascnt = count($arrccl);
      $xxccl = 4;
      $cnt = 0;
      $calc_ccl = 0;
      
      $r = 0;
      $old_r = $r;
      foreach($arrccl as $k=>$v) {
         if($cnt==0) {
            $calc_ccl = $v;
         }
         $cnt++;
         $r = _bctrim(bcdiv($cnt,$ascnt));
         
         if(bccomp($old_r,0.75)>=0) {
         } else {
            $calc_ccl = $v;
         }
         $old_r = $r;
      }
      */ //////////////////////////////////////////////////////////
      
      $count_type = count($arrtype);
      $arravg = array();
      $arravgxxx = array(); /// tambahan 2012-01-16
      foreach($arrtype as $assessor_t=>$v) {
         $ttlccl = 0;
         $ttlcclxxx = 0; /// tambahan 2012-01-16
         $cntx = 0;
         foreach($v as $assessor_id=>$vv) {
            $ttlccl += $arrccl[$assessor_id];
            $ttlcclxxx += $arrcclxxx[$assessor_id]; /// tambahan 2012-01-16
            $cntx++;
         }
         if($cntx>0) {
            $arravg[$assessor_t] = $ttlccl/$cntx;
            $arravgxxx[$assessor_t] = $ttlcclxxx/$cntx;
         }
      }
      $ttlccl = 0;
      $ttlcclxxx = 0; /// tambahan 2012-01-16
      $ttlweight = 0;
      if(count($arravg)>0) {
         foreach($arravg as $assessor_t=>$ccl_avg) {
            //$ttlccl += $ccl_avg;
            $ttlccl += bcmul($ccl_avg,$arrweight[$assessor_t]);
            $ttlweight += $arrweight[$assessor_t];
         }
         foreach($arravgxxx as $assessor_t=>$ccl_avgxxx) {
            $ttlcclxxx += bcmul($ccl_avgxxx,$arrweight[$assessor_t]);
            ///$ttlweight += $arrweight[$assessor_t];
         }
         $calc_ccl = floor($ttlccl/$ttlweight); ///count($arravg);
         $calc_cclxxx = $ttlcclxxx/$ttlweight; ///count($arravg);
         
         
         //$calc_ccl = $ttlccl/count($arravg);
         $original_calc_ccl = $calc_ccl;
         
         /*
         if($answer_t=="yesno") {
            $floor_ccl = floor($calc_ccl);
            $mod = bcsub($calc_ccl,$floor_ccl);
            if(bccomp($mod,0.5)>0) {
               $calc_ccl = ceil($calc_ccl);
            } else {
               $calc_ccl = floor($calc_ccl);
            }
         }
         */
      } else {
         $calc_ccl = 0;
         $calc_cclxxx = 0;
         $original_calc_ccl = 0;
      }
      
      return array($arrccl,$arrasr,$calc_ccl,$original_calc_ccl,$arravg,$arrcclxxx,$calc_cclxxx,$arravgxxx,$arrasrxxx);
   
   }


} // HRIS_ASSESSMENTFUNCTIONS_DEFINED
?>