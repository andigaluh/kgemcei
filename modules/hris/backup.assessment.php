<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/assessment.php                             //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-07-18                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_ASSESSMENT_DEFINED') ) {
   define('HRIS_ASSESSMENT_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_Assessment extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_ASSESSMENT_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_ASSESSMENT_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_Assessment($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function assessment() {
      $db=&Database::getInstance();
      $ret = "";
      $user_id = getUserID();
      $cellwidth = 15;
      
      $arr_compgroup = array();
      $sql = "SELECT compgroup_id,compgroup_nm FROM ".XOCP_PREFIX."compgroup"
           . " ORDER BY compgroup_id";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($compgroup_id,$compgroup_nm)=$db->fetchRow($result)) {
            $arr_compgroup[$compgroup_id] = $compgroup_nm;
         }
      }
      
      $sql = "SELECT c.job_id"
           . " FROM ".XOCP_PREFIX."users a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(person_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job c USING(employee_id)"
           . " WHERE a.user_id = '$user_id'";
      $result = $db->query($sql);
      $arr_employee = array();
      $arr_rcl = array();
      $arr_ccl = array();
      $arr_comp_class = array();
      $arr_job_competency = array();
      if($db->getRowsNum($result)>0) {
         while(list($assessor_job_id)=$db->fetchRow($result)) {
            $sql = "SELECT a.job_id,a.job_nm,a.job_cd,a.job_abbr,a.org_id"
                 . " FROM ".XOCP_PREFIX."jobs a"
                 . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
                 . " WHERE a.assessor_job_id = '$assessor_job_id'"
                 . " ORDER BY a.job_class_id";
            $res = $db->query($sql);
            if($db->getRowsNum($res)>0) {
               while(list($job_id,$job_nm,$job_cd,$job_abbr,$org_id)=$db->fetchRow($res)) {
                  $sql = "SELECT a.rcl,a.itj,a.competency_id,b.compgroup_id,b.competency_cd,"
                       . "b.competency_abbr,b.competency_nm,b.competency_class"
                       . " FROM ".XOCP_PREFIX."job_competency a"
                       . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
                       . " WHERE a.job_id = '$job_id'";
                  $resrcl = $db->query($sql);
                  if($db->getRowsNum($resrcl)>0) {
                     while(list($rcl,$itj,$competency_id,$compgroup_id,$competency_cd,
                                $competency_abbr,$competency_nm,$competency_class)=$db->fetchRow($resrcl)) {
                        $arr_rcl[$compgroup_id][$competency_id] = array($rcl,$itj);
                        $arr_comp_class[$compgroup_id][$competency_class][$competency_id] = array($rcl,$itj);
                        $arr_comp[$competency_id] = $competency_abbr;
                        $arr_job_competency[$job_id][$competency_id] = 1;
                     }
                  }
                  $sql = "SELECT a.employee_id,b.employee_ext_id,c.person_nm"
                       . " FROM ".XOCP_PREFIX."employee_job a"
                       . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
                       . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
                       . " WHERE a.job_id = '$job_id'";
                  $res2 = $db->query($sql);
                  if($db->getRowsNum($res2)>0) {
                     while(list($employee_id,$nip,$employee_nm)=$db->fetchRow($res2)) {
                        $arr_employee[$employee_id] = array($employee_nm,$nip,$job_nm,$job_abbr,$job_id);
                        $sql = "SELECT a.ccl,a.competency_id"
                             . " FROM ".XOCP_PREFIX."employee_competency a"
                             . " WHERE a.employee_id = '$employee_id'";
                        $resccl = $db->query($sql);
                        if($db->getRowsNum($resccl)>0) {
                           while(list($ccl,$competency_idx)=$db->fetchRow($resccl)) {
                              $arr_ccl[$employee_id][$competency_idx] = $ccl;
                           }
                        }
                     }
                  }
               }
            }
         }
         
         $ret = "<table class='assessment'><tbody>"
              . "<tr><td style='text-align:center;'>&nbsp;</td>"
              . "<td><ul class='ultab'>";
         
         $tab_arr_js = "var tabs = new Array(";
         if(count($arr_compgroup)>0) {
            $tab = 0;
            foreach($arr_compgroup as $compgroup_id=>$compgroup_nm) {
               $tab_arr_js .= "$compgroup_id,";
               if($tab==0) {
                  $class = "class='ultabsel_greyrev'";
               } else {
                  $class = "";
               }
               $ret .= "<li style='width:200px;' id='tab_${compgroup_id}' $class onclick='switchtab(\"$compgroup_id\",this,event);'>"
                     . "<span>$compgroup_nm</span>"
                     . "</li>";
               $tab++;
            }
         }
         $tab_arr_js = substr($tab_arr_js,0,-1) . ");";
         
         $ret .= "</ul></td></tr>";
         
         //// tab for soft/technical
         
         $ret .= "<tr><td>&nbsp;</td><td style='border-left:1px solid #bbbbbb;padding-top:5px;'>";
         if(count($arr_compgroup)>0) {
            $tab = 0;
            foreach($arr_compgroup as $compgroup_id=>$compgroup_nm) {
            
               if($tab!=0) {
                  $display = "display:none;";
               } else {
                  $display = "";
               }
               $ret .= "<ul id='ulx_${compgroup_id}' class='ultab' style='${display}padding-left:10px;'>";
         
               $class = "class='ultabsel_greyrev'";
               $ret .= "<li style='width:150px;' id='tabsoft_${compgroup_id}' $class onclick='switchtabcompclass(\"soft\",\"$compgroup_id\",this,event);'>"
                     . "<span>Soft</span>"
                     . "</li>";
               $class = "";
               $ret .= "<li style='width:150px;' id='tabtech_${compgroup_id}' $class onclick='switchtabcompclass(\"tech\",\"$compgroup_id\",this,event);'>"
                     . "<span>Technical</span>"
                     . "</li>";
               $ret .= "</ul>";
               $tab++;
            }
         }
         $ret .= "</td></tr>";
         
         //// heading
         $ret .= "<tr><td style='font-weight:bold;'>"

               . "<div style='border-bottom:1px solid #999999;padding:0px;'><table class='assessment' border='0'><tbody><tr>"
                     . "<td style=''><div class='cntr' style='width:50px;overflow:hidden;margin-right:4px;'><div style='width:900px;'>Job</div></div></td>"
                     . "<td style=''><div class='cntr' style='width:150px;overflow:hidden;margin-right:4px;'><div style='width:900px;'>Employee</div></div></td>"
                     . "</tr></tbody></table></div>"


               . "</td><td style=''>";
         
         if(is_array($arr_compgroup)&&count($arr_compgroup)>0) {
            $tabno = 0;
            foreach($arr_compgroup as $compgroup_id=>$compgroup_nm) {
               if($tabno>0) {
                  $display = "display:none;";
               } else {
                  $display = "";
               }
               $ret .= "<div id='hd_${compgroup_id}' style='${display}border-bottom:1px solid #999999;padding:0px;font-weight:bold;'>"
                     . "<table class='assessment' border='0'><tbody><tr>";
               $no = 0;
               foreach($arr_rcl[$compgroup_id] as $competency_id=>$v) {
                  list($rcl,$itj)=$v;
                  if($no%2!=1) {
                     $bg="background-color:#eeeeee;";
                  } else {
                     $bg = "";
                  }
                  $bg = "border-left:1px solid #bbbbbb;";
                  $ret .= "<td style='$bg'><div class='cntr' style='width:".(4*($cellwidth+4)+1)."px;overflow:hidden;margin-right:4px;'><div style='width:900px;padding-left:2px;'>"
                        . $arr_comp[$competency_id]
                        . "</div></div></td>";
                  $no++;
               }
               $ret .= "</tr></tbody></table></div>";
               $tabno++;
            }
         }
         
         
         $ret .= "</td></tr>";
         $ret .= "<tr><td style=''>";
         if(is_array($arr_employee)&&count($arr_employee)>0) {
            foreach($arr_employee as $employee_id=>$employee) {
               list($employee_nm,$nip,$job_nm,$job_abbr)=$employee;
               $ret .= "<div style='border-bottom:1px solid #999999;padding:0px;'><table class='assessment' border='0'><tbody><tr>"
                     . "<td style=''><div class='cntr' style='width:50px;overflow:hidden;margin-right:4px;'><div style='width:900px;'>$job_abbr</div></div></td>"
                     . "<td style=''><div class='cntr' style='width:150px;overflow:hidden;margin-right:4px;'><div style='width:900px;'>$employee_nm</div></div></td>"
                     . "</tr></tbody></table></div>";
            }
         }
         $tabcomp = "";
         if(is_array($arr_compgroup)&&count($arr_compgroup)>0) {
            $tabno = 0;
            foreach($arr_compgroup as $compgroup_id=>$compgroup_nm) {
               if($tabno>0) {
                  $display = "display:none;";
               } else {
                  $display = "";
               }
               $com = "";
               foreach($arr_employee as $employee_id=>$employee) {
                  list($employee_nm,$nip,$job_nm,$job_abbr,$job_id)=$employee;
                  $com .= "<div style='border-bottom:1px solid #999999;padding:0px;'><table class='assessment' border='0'><tbody><tr>";
                  $no = 0;
                  foreach($arr_rcl[$compgroup_id] as $competency_id=>$v) {
                     if($no%2!=1) {
                        $bg="background-color:#eeeeee;";
                     } else {
                        $bg = "";
                     }
                     $bg = "border-left:1px solid #bbbbbb;";
                     list($rcl,$itj)=$v;
                     if(isset($arr_ccl[$employee_id][$competency_id])) {
                        $ccl = $arr_ccl[$employee_id][$competency_id];
                        $gap = $ccl-$rcl;
                     } else {
                        $ccl = "0";
                        $gap = $ccl-$rcl;
                     }
                     
                     if(!isset($arr_job_competency[$job_id][$competency_id])) {
                        $ccl = $rcl = $itj = $gap = "-";
                     }
                     
                     $com .= "<td style='$bg'><div class='cntr' style='width:${cellwidth}px;overflow:hidden;'><div style='text-align:center;'>$ccl</div></div></td>"
                           . "<td style='$bg'><div class='cntr' style='width:${cellwidth}px;overflow:hidden;'><div style='text-align:center;'>$rcl</div></div></td>"
                           . "<td style='$bg'><div class='cntr' style='width:${cellwidth}px;overflow:hidden;'><div style='text-align:center;'>$itj</div></div></td>"
                           . "<td style='$bg'><div class='cntr' style='width:${cellwidth}px;overflow:hidden;'><div style='text-align:center;'>$gap</div></div></td>";
                     $no++;
                  }
                  $com .= "</tr></tbody></table></div>";
               }
               $tabcomp .= "<div id='tabcontent_${compgroup_id}' style='$display'>$com</div>";
               $tabno++;
            }
         }
         $ret .= "</td>";
         $ret .= "<td style=''>$tabcomp</td>";
         $ret .= "</tr></tbody></table>";
         
      } else {
         $ret = "You don't have a job assigned. Please contact administrator.";
      }
      
      
      return "<div style='min-width:800px;'>
      $ret
      </div><script type='text/javascript'><!--
      
      $tab_arr_js
      function switchtab(compgroup_id,d,e) {
         for(var i=0;i<tabs.length;i++) {
            $('tab_'+tabs[i]).className = '';
            $('tabcontent_'+tabs[i]).style.display = 'none';
            $('hd_'+tabs[i]).style.display = 'none';
            $('ulx_'+tabs[i]).style.display = 'none';
         }
         $('tab_'+compgroup_id).className = 'ultabsel_greyrev';
         $('tabcontent_'+compgroup_id).style.display = '';
         $('hd_'+compgroup_id).style.display = '';
         $('ulx_'+compgroup_id).style.display = '';
      }
      
      function switchtabcompclass(xclass,compgroup_id,d,e) {
         switch(xclass) {
            case 'soft':
               $('tabtech_'+compgroup_id).className = '';
               $('tabsoft_'+compgroup_id).className = 'ultabsel_greyrev';
               break;
            default:
               $('tabsoft_'+compgroup_id).className = '';
               $('tabtech_'+compgroup_id).className = 'ultabsel_greyrev';
               break;
         }
      }
      
      // --></script>";
   }
   
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $this->assessment();
            break;
         default:
            $ret = $this->assessment();
            break;
      }
      return $ret;
   }
}

} // HRIS_ASSESSMENT_DEFINED
?>