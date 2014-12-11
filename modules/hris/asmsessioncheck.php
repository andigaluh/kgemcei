<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/asmsessioncheck.php                        //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-12-17                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_ASSESSMENTSESSION_DEFINED') ) {
   define('HRIS_ASSESSMENTSESSION_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_AssessmentSessionCheck extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_ASSESSMENTSESSIONCHECK_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_ASSESSMENTSESSIONCHECK_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_AssessmentSessionCheck($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function listSession() {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_asmsession.php");
      $ajax = new _hris_class_AssessmentSessionAjax("ocjx");
      
      $sql = "SELECT asid,session_nm,session_periode"
           . " FROM ".XOCP_PREFIX."assessment_session"
           . " WHERE status_cd = 'normal'"
           . " ORDER BY session_periode DESC";
      
      $result = $db->query($sql);
      $ret = "<table class='xxlist' style='width:100%;' align='center'>"
           . "<thead><tr><td>"
           . "<span style='float:left;'>Sessions</span>"
           . "<span style='float:right;'>&nbsp;</span></td></tr>"
           . "</thead><tbody id='tbproc'>";
      if($db->getRowsNum($result)>0) {
         while(list($asid,$session_nm,$session_periode)=$db->fetchRow($result)) {
            $ret .= "<tr><td id='tdclass_${asid}'>"
                  . "<table><colgroup><col width='60'/><col/></colgroup><tbody><tr>"
                  . "<td>$session_periode</td>"
                  . "<td><a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&docheck=y&asid=$asid'>".htmlentities(stripslashes($session_nm))."</a></td>"
                  . "</tr></tbody></table>"
                  . "</td></tr>";
         }
      }
      $ret .= "<tr style='display:none;' id='trempty'><td>"._EMPTY."</td></tr>";
      $ret .= "</tbody></table>";
      
      return $ret;
   }
   
   function checkSession($asid) {
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_asmsession.php");
      $ajax = new _hris_class_AssessmentSessionAjax("asjx");
      $db=&Database::getInstance();
      $tooltips = "";
      $ret = "<div></div>";
      $ret .= "<table class='xxlist' style='width:100%;margin-bottom:100px;'>"
            . "<colgroup><col width='20%'/><col width='20%'/><col width='20%'/><col width='20%'/><col width='20%'/></colgroup>"
            . "<thead>"
            . "<tr>"
            . "<td rowspan='2' style='text-align:center;border-right:1px solid #bbb;'>Employee</td>"
            . "<td colspan='4' style='text-align:center;'>Assessor</td></tr>"
            . "<tr><td style='text-align:center;border-right:1px solid #bbb;'>Superior</td>"
            . "<td style='text-align:center;border-right:1px solid #bbb;'>Subordinate</td>"
            . "<td style='text-align:center;border-right:1px solid #bbb;'>Peer</td>"
            . "<td style='text-align:center;'>Customer</td></tr>"
            . "<thead><tbody>";
      $sql = "SELECT a.job_id,a.job_abbr,a.job_nm,b.job_class_nm,e.person_nm,a.assessor_job_id,d.employee_id"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job c ON c.job_id = a.job_id"
           . " LEFT JOIN ".XOCP_PREFIX."employee d ON d.employee_id = c.employee_id"
           . " LEFT JOIN ".XOCP_PREFIX."persons e ON e.person_id = d.person_id"
           . " WHERE d.status_cd = 'normal'"
           . " ORDER BY b.job_class_level,a.job_abbr,a.job_id";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($job_id,$job_abbr,$job_nm,$job_class_nm,$employee_nm,$assessor_job_id,$employee_id)=$db->fetchRow($result)) {
            if($employee_nm=="") {
               $employeex = "<div style='color:#bbb;width:120px;overflow:hidden;'><div style='width:900px;'>$job_nm</div></div>";
            } else {
               $id=uniqid();
               $employeex = "<div onmouseover='gettip(\"$id\",\"$employee_id\",\"$employee_nm\",\"employee\",0,this,event);' class='a360' id='$id' style='width:120px;overflow:hidden;'><div style='width:900px;'>$employee_nm</div></div>";
            }
            
            ///// 360
            $sql = "SELECT e.job_abbr,d.person_nm,a.status_cd,a.assessor_t,a.assessor_id,c.employee_ext_id,c.entrance_dttm,"
                 . "b.start_dttm,b.stop_dttm,(TO_DAYS(now())-TO_DAYS(c.entrance_dttm)) as jobage,d.adm_gender_cd,d.person_id,"
                 . "o.org_nm,e.job_nm,p.org_class_nm"
                 . " FROM ".XOCP_PREFIX."assessor_360 a"
                 . " LEFT JOIN ".XOCP_PREFIX."employee_job b ON b.employee_id = a.assessor_id"
                 . " LEFT JOIN ".XOCP_PREFIX."jobs e ON e.job_id = b.job_id"
                 . " LEFT JOIN ".XOCP_PREFIX."employee c ON c.employee_id = b.employee_id"
                 . " LEFT JOIN ".XOCP_PREFIX."persons d USING(person_id)"
                 . " LEFT JOIN ".XOCP_PREFIX."orgs o ON o.org_id = e.org_id"
                 . " LEFT JOIN ".XOCP_PREFIX."org_class p ON p.org_class_id = o.org_class_id"
                 . " WHERE a.asid = '$asid' AND a.employee_id = '$employee_id'"
                 . " ORDER BY d.person_nm,a.status_cd,b.job_id";
            $r360 = $db->query($sql);
            $superior = $assessor_peer = $assessor_subordinate = $assessor_customer = "";
            if($db->getRowsNum($r360)>0) {
               while(list($a360_job_abbr,$a360_person_nm,$a360_status,$a360_t,$assessor_id,$assessor_nip,$entrance_dttm,
                          $jobstart,$a360_stop_job,$jobage,$gender,$person_id,$org_nm,$job_nm,$org_class_nm)=$db->fetchRow($r360)) {
                  
                  $a360_person_nm = htmlentities($a360_person_nm,ENT_QUOTES);
                  
                  $id = "a360_${employee_id}_${assessor_id}_${a360_t}";
                  
                  if($a360_t=="superior") {
                     if($a360_status=="active") {
                        $clr = "text-decoration:underline;";
                     } else {
                        $clr = "color:#bbb;";
                     }
                     $superior .= "<div onmouseover='gettip(\"$id\",\"$assessor_id\",\"$a360_person_nm\",\"superior\",\"$employee_id\",this,event);' class='a360' style='width:120px;overflow:hidden;$clr' id='$id'><div style='width:900px;'>$a360_person_nm</div></div>";
                  }
                  
                  if($a360_t=="peer") {
                     if($a360_status=="active") {
                        $clr = "text-decoration:underline;";
                     } else {
                        $clr = "color:#bbb;";
                     }
                     $assessor_peer .= "<div onmouseover='gettip(\"$id\",\"$assessor_id\",\"$a360_person_nm\",\"peer\",\"$employee_id\",this,event);' class='a360' style='width:120px;overflow:hidden;$clr' id='$id'><div style='width:900px;'>$a360_person_nm</div></div>";
                  }
                  if($a360_t=="subordinat") {
                     if($a360_status=="active") {
                        $clr = "text-decoration:underline;";
                     } else {
                        $clr = "color:#bbb;";
                     }
                     $assessor_subordinate .= "<div onmouseover='gettip(\"$id\",\"$assessor_id\",\"$a360_person_nm\",\"subordinat\",\"$employee_id\",this,event);' class='a360' style='width:120px;overflow:hidden;$clr' id='$id'><div style='width:900px;'>$a360_person_nm</div></div>";
                  }
                  if($a360_t=="customer") {
                     if($a360_status=="active") {
                        $clr = "text-decoration:underline;";
                     } else {
                        $clr = "color:#bbb;";
                     }
                     $assessor_customer .= "<div onmouseover='gettip(\"$id\",\"$assessor_id\",\"$a360_person_nm\",\"customer\",\"$employee_id\",this,event);' class='a360' style='width:120px;overflow:hidden;$clr' id='$id'><div style='width:900px;'>$a360_person_nm</div></div>";
                  }
               }
            }
            
            $ret .= "\n<tr>"
                  . "<td style='border-right:1px solid #bbb;' id='emp_${employee_id}'>$employeex</td>"
                  . "<td style='border-right:1px solid #bbb;'>$superior</td>"
                  . "<td style='border-right:1px solid #bbb;'>$assessor_subordinate</td>"
                  . "<td style='border-right:1px solid #bbb;'>$assessor_peer</td>"
                  . "<td style=''>$assessor_customer</td>"
                  . "</tr>";
            
         }
      }
      $ret .= "</tbody></table>";
      
      $js = $ajax->getJs()."<script type='text/javascript'>\n//<![CDATA
      
      function gettip(id,assessor_id,person_nm,t,employee_id,d,e) {
         d.onmouseover = null;
         new Tip(id,{ajax:{url:'".XOCP_SERVER_SUBDIR."/ajaxreq.php?ac=asjx&ff=app_getPersonInfo&ffargs[0]='+assessor_id+'&ffargs[1]='+employee_id+'&ffargs[2]='+t+'&ffargs[3]='+id},title:person_nm,width:450,style:'empc',closeButton:true});
      }
      
      function assessor_set(asid,employee_id,assessor_id,assessor_t,id,d,e) {
         asjx_app_setAssessor(asid,employee_id,assessor_id,assessor_t,id,function(_data) {
            if(_data!='FAIL') {
               var data = recjsarray(_data);
               d.value = data[2];
               if(data[1]=='active') {
                  $(data[0]).style.color = '';
               } else {
                  $(data[0]).style.color = '#bbb';
               }
            }
         });
      }
      
      //]]>\n</script>";
      
      $_SESSION["html"]->addHeadScript("<script type='text/javascript' src='".XOCP_SERVER_SUBDIR."/include/prototip2.0.5/js/prototip.js'></script>");
      $_SESSION["html"]->addStyleSheet("<link rel='stylesheet' type='text/css' href='".XOCP_SERVER_SUBDIR."/include/prototip2.0.5/css/prototip.css' />");
      $_SESSION["html"]->addHeadScript($js);
      
      
      return $ret;
   }
   
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            if(isset($_GET["docheck"])&&$_GET["docheck"]=="y") {
               $ret = $this->checkSession($_GET["asid"]);
               $_SESSION["hris_check_asid"] = $_GET["asid"];
            } else {
               $ret = $this->listSession();
            }
            break;
         default:
            $ret = $this->listSession();
            break;
      }
      return $ret;
   }
}

} // HRIS_ASSESSMENTSESSION_DEFINED
?>