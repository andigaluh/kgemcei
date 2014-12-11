<?php
if ( !defined('HRIS_HRISCOMMONFUNCTIONS_DEFINED') ) {
   define('HRIS_HRISCOMMONFUNCTIONS_DEFINED', TRUE);
   
   
   function _getFirstAssessor($employee_id,$job_id) {
      $db=&Database::getInstance();
      $sql = "SELECT assessor_job_id,assessor_employee_id FROM ".XOCP_PREFIX."employee_job"
           . " WHERE employee_id = '$employee_id' AND job_id = '$job_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($assessor_job_id,$assessor_employee_id)=$db->fetchRow($result);
      }
      return array($assessor_job_id,$assessor_employee_id);
   }
   
   function _getNextAssessor($employee_id,$job_id) {
      $db=&Database::getInstance();
      $sql = "SELECT assessor_job_id,assessor_employee_id FROM ".XOCP_PREFIX."employee_job"
           . " WHERE employee_id = '$employee_id' AND job_id = '$job_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($assessor_job_id,$assessor_employee_id)=$db->fetchRow($result);
      }
      
      $sql = "SELECT assessor_job_id,assessor_employee_id FROM ".XOCP_PREFIX."employee_job"
           . " WHERE employee_id = '$assessor_employee_id' AND job_id = '$assessor_job_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($next_assessor_job_id,$next_assessor_employee_id)=$db->fetchRow($result);
      }
      
      
      $sql = "SELECT a.job_class_id,b.job_class_level FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
           . " WHERE a.job_id = '$next_assessor_job_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($next_job_class_id,$next_job_class_level)=$db->fetchRow($result);
         _debuglog(" $next_job_class_id,$next_job_class_level ");
      }
      
      if($next_job_class_level<20) {
         $next_assessor_job_id = $assessor_job_id;
         $next_assessor_employee_id = $assessor_employee_id;
      } else {
         
         $sql = "SELECT assessor_job_id,assessor_employee_id FROM ".XOCP_PREFIX."employee_job"
              . " WHERE employee_id = '$assessor_employee_id' AND job_id = '$assessor_job_id'";
         $result = $db->query($sql);
         if($db->getRowsNum($result)==1) {
            list($next_assessor_job_id,$next_assessor_employee_id)=$db->fetchRow($result);
            _debuglog("---- non director : $next_assessor_job_id,$next_assessor_employee_id");
         }
      }

      return array($next_assessor_job_id,$next_assessor_employee_id);
   }
   
   function _getSectionManagerJobID($job_id) {
      $db=&Database::getInstance();
      $sql = "SELECT a.job_class_id,a.upper_job_id,b.job_class_id,c.job_class_level,d.job_class_level FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."jobs b ON b.job_id = a.upper_job_id"
           . " LEFT JOIN ".XOCP_PREFIX."job_class c ON c.job_class_id = a.job_class_id"
           . " LEFT JOIN ".XOCP_PREFIX."job_class d ON d.job_class_id = b.job_class_id"
           . " WHERE a.job_id = '$job_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($job_class_id,$upper_job_id,$upper_job_class_id,$job_class_level,$upper_job_class_level)=$db->fetchRow($result);
         if($job_class_level<=50) {
            return $job_id;
         } else if($job_class_level<=50) {
            $sql = "SELECT a.job_nm,b.employee_ext_id,c.person_nm"
                 . " FROM ".XOCP_PREFIX."jobs a"
                 . " LEFT JOIN ".XOCP_PREFIX."employee_job e USING(job_id)"
                 . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
                 . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
                 . " WHERE a.job_id = '$upper_job_id'"
                 . " ORDER BY e.start_dttm DESC LIMIT 1";
            $result = $db->query($sql);
            if($db->getRowsNum($result)==1) {
               list($division_manager_job,$division_manager_nip,$division_manager_name)=$db->fetchRow($result);
               return $upper_job_id;
            } else {
               return _getSectionManagerJobID($upper_job_id);
            }
         } else {
            return _getSectionManagerJobID($upper_job_id);
         }
      }
   }
   
   function _getDivisionManagerJobID($job_id) {
      $db=&Database::getInstance();
      $sql = "SELECT a.job_class_id,a.upper_job_id,b.job_class_id,c.job_class_level,d.job_class_level FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."jobs b ON b.job_id = a.upper_job_id"
           . " LEFT JOIN ".XOCP_PREFIX."job_class c ON c.job_class_id = a.job_class_id"
           . " LEFT JOIN ".XOCP_PREFIX."job_class d ON d.job_class_id = b.job_class_id"
           . " WHERE a.job_id = '$job_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($job_class_id,$upper_job_id,$upper_job_class_id,$job_class_level,$upper_job_class_level)=$db->fetchRow($result);
         if($job_class_level<=30) {
            return $job_id;
         } else if($upper_job_class_level<=30) {
            $sql = "SELECT a.job_nm,b.employee_ext_id,c.person_nm"
                 . " FROM ".XOCP_PREFIX."jobs a"
                 . " LEFT JOIN ".XOCP_PREFIX."employee_job e USING(job_id)"
                 . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
                 . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
                 . " WHERE a.job_id = '$upper_job_id'"
                 . " ORDER BY e.start_dttm DESC LIMIT 1";
            $result = $db->query($sql);
            if($db->getRowsNum($result)==1) {
               list($division_manager_job,$division_manager_nip,$division_manager_name)=$db->fetchRow($result);
               return $upper_job_id;
            } else {
               return _getDivisionManagerJobID($upper_job_id);
            }
         } else {
            return _getDivisionManagerJobID($upper_job_id);
         }
      }
   }
   
   
   function _hris_getinfobyuserid($user_id) {
      $db=&Database::getInstance();
      $self_user_id = $user_id;
      $sql = "SELECT c.assessor_job_id,c.assessor_employee_id,c.job_id,b.employee_id,d.job_nm,p.person_nm,b.employee_ext_id,p.adm_gender_cd,c.start_dttm,"
           . "b.entrance_dttm,(TO_DAYS(now())-TO_DAYS(b.entrance_dttm)) as jobage,d.summary,b.person_id"
           . " FROM ".XOCP_PREFIX."users a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(person_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job c USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."jobs d USING(job_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons p ON p.person_id = b.person_id"
           . " WHERE a.user_id = '$user_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($first_assessor_job_id,$first_assessor_employee_id,$self_job_id,$self_employee_id,$self_job_nm,$self_nm,$self_nip,$self_gender,$self_jobstart,$self_entrance_dttm,$self_jobage,$self_job_summary,$self_person_id)=$db->fetchRow($result);
         $sql = "SELECT assessor_job_id,assessor_employee_id FROM ".XOCP_PREFIX."employee_job WHERE employee_id = '$first_assessor_employee_id' AND job_id = '$first_assessor_job_id'";
         $result = $db->query($sql);
         $next_assessor_job_id = 0;
         if($db->getRowsNum($result)>0) {
            list($next_assessor_job_id,$next_assessor_employee_id)=$db->fetchRow($result);
         }
         if($next_assessor_job_id==0) {
            $next_assessor_job_id = $first_assessor_job_id;
            $next_assessor_employee_id = $first_assessor_employee_id;
         }
         return array($self_job_id,
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
                      $next_assessor_job_id,
                      $first_assessor_employee_id,
                      $next_assessor_employee_id);
      } else {
         return FALSE;
      }
   }
   
   function _hris_getinfobyemployeeid($employee_id) {
      $db=&Database::getInstance();
      $sql = "SELECT c.assessor_job_id,c.assessor_employee_id,c.job_id,b.employee_id,d.job_nm,p.person_nm,b.employee_ext_id,p.adm_gender_cd,c.start_dttm,"
           . "b.entrance_dttm,(TO_DAYS(now())-TO_DAYS(b.entrance_dttm)) as jobage,d.summary,b.person_id,u.user_id"
           . " FROM ".XOCP_PREFIX."employee b"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job c USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."jobs d USING(job_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons p ON p.person_id = b.person_id"
           . " LEFT JOIN ".XOCP_PREFIX."users u ON u.person_id = b.person_id"
           . " WHERE b.employee_id = '$employee_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($first_assessor_job_id,$first_assessor_employee_id,$self_job_id,$self_employee_id,$self_job_nm,$self_nm,$self_nip,$self_gender,$self_jobstart,$self_entrance_dttm,$self_jobage,$self_job_summary,$self_person_id,$self_user_id)=$db->fetchRow($result);
         
         $sql = "SELECT assessor_job_id,assessor_employee_id FROM ".XOCP_PREFIX."employee_job WHERE employee_id = '$first_assessor_employee_id' AND job_id = '$first_assessor_job_id'";
         $result = $db->query($sql);
         $next_assessor_job_id = 0;
         if($db->getRowsNum($result)>0) {
            list($next_assessor_job_id,$next_assessor_employee_id)=$db->fetchRow($result);
         }
         
         if($next_assessor_job_id==0) {
            $next_assessor_job_id = $first_assessor_job_id;
            $next_assessor_employee_id = $first_assessor_employee_id;
         }
         return array($self_job_id,
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
                      $next_assessor_job_id,
                      $first_assessor_employee_id,
                      $next_assessor_employee_id);
      
      
      
      } else {
         return FALSE;
      }
   }
   
   function _hris_getinfobypersonid($person_id) {
      $db=&Database::getInstance();
      $sql = "SELECT d.assessor_job_id,c.job_id,b.employee_id,d.job_nm,p.person_nm,b.employee_ext_id,p.adm_gender_cd,c.start_dttm,"
           . "b.entrance_dttm,(TO_DAYS(now())-TO_DAYS(b.entrance_dttm)) as jobage,d.summary,b.person_id,u.user_id"
           . " FROM ".XOCP_PREFIX."employee b"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job c USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."jobs d USING(job_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons p ON p.person_id = b.person_id"
           . " LEFT JOIN ".XOCP_PREFIX."users u ON u.person_id = b.person_id"
           . " WHERE b.person_id = '$person_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($first_assessor_job_id,$self_job_id,$self_employee_id,$self_job_nm,$self_nm,$self_nip,$self_gender,$self_jobstart,$self_entrance_dttm,$self_jobage,$self_job_summary,$self_person_id,$self_user_id)=$db->fetchRow($result);
         $sql = "SELECT assessor_job_id FROM ".XOCP_PREFIX."jobs WHERE job_id = '$first_assessor_job_id'";
         $result = $db->query($sql);
         $next_assessor_job_id = 0;
         if($db->getRowsNum($result)>0) {
            list($next_assessor_job_id)=$db->fetchRow($result);
         }
         if($next_assessor_job_id==0) $next_assessor_job_id = $first_assessor_job_id;
         return array($self_job_id,
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
                      $next_assessor_job_id);
      } else {
         return FALSE;
      }
   }
   
} // HRIS_HRISCOMMONFUNCTIONS_DEFINED
?>