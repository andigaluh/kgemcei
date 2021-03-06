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
      $cellwidth = 16;
      $hdrheight = 30;
      $stripcolor0 = "#ffffff";
      $stripcolor1 = "#ffffff";
      $bordercomp = "#888888";
      $tooltips = ""; /// for tooltips definition
      $assessor_job_count = 0;
      global $proficiency_level_name;
      
      $_SESSION["html"]->js_scriptaculous_effecs=TRUE;
      
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
      $arr_param = array();
      $arr_ccl = array();
      $arr_ttl_ccl = array();
      $arr_ttl_rcl = array();
      $arr_comp_class = array();
      $arr_job_competency = array();
      if($db->getRowsNum($result)>0) {
         while(list($assessor_job_id)=$db->fetchRow($result)) {
            if($assessor_job_id==0) continue;
            $sql = "SELECT a.job_id,a.job_nm,a.job_cd,a.job_abbr,a.org_id,a.description"
                 . " FROM ".XOCP_PREFIX."jobs a"
                 . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
                 . " WHERE a.assessor_job_id = '$assessor_job_id'"
                 . " ORDER BY a.job_class_id";
            $res = $db->query($sql);
            if($db->getRowsNum($res)>0) {
               while(list($job_id,$job_nm,$job_cd,$job_abbr,$org_id,$job_desc)=$db->fetchRow($res)) {
                  
                  $sql = "SELECT a.rcl,a.itj,a.competency_id,b.compgroup_id,b.competency_cd,"
                       . "b.competency_abbr,b.competency_nm,b.competency_class,b.desc_en,b.desc_id,"
                       . "b.desc_en_level_1,"
                       . "b.desc_en_level_2,"
                       . "b.desc_en_level_3,"
                       . "b.desc_en_level_4,"
                       . "b.desc_id_level_1,"
                       . "b.desc_id_level_2,"
                       . "b.desc_id_level_3,"
                       . "b.desc_id_level_4"
                       . " FROM ".XOCP_PREFIX."job_competency a"
                       . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
                       . " WHERE a.job_id = '$job_id'";
                  $resrcl = $db->query($sql);
                  if($db->getRowsNum($resrcl)>0) {
                     while(list($rcl,$itj,$competency_id,$compgroup_id,$competency_cd,
                                $competency_abbr,$competency_nm,$competency_class,$desc_en,$desc_id,
                                $desc_en_level_1,$desc_en_level_2,$desc_en_level_3,$desc_en_level_4,
                                $desc_id_level_1,$desc_id_level_2,$desc_id_level_3,$desc_id_level_4)=$db->fetchRow($resrcl)) {
                        $arr_param[$competency_id] = array($rcl,$itj);
                        $arr_xttl_rcl[$job_id] += ($rcl*$itj);
                        $arr_comp_class[$compgroup_id][$competency_class][$competency_id] = array($rcl,$itj);
                        $arr_comp[$competency_id] = array($competency_nm,$competency_abbr,$desc_en,$desc_id);
                        $arr_job_competency[$job_id][$competency_id] = 1;
                        $arr_job_rcl[$competency_id][$job_id] = array($rcl,$itj);
                        $sql = "SELECT behaviour_id,proficiency_lvl,behaviour_en_txt,behaviour_id_txt"
                             . " FROM ".XOCP_PREFIX."compbehaviour"
                             . " WHERE competency_id = '$competency_id'"
                             . " ORDER BY proficiency_lvl";
                        $rbh = $db->query($sql);
                        if($db->getRowsNum($rbh)>0) {
                           while(list($behaviour_id,$lvl,$xen,$xid)=$db->fetchRow($rbh)) {
                              $arr_desclvl[$competency_id][$lvl] = array($xen,$xid);
                           }
                        }
                     }
                  }
                  
                  $sql = "SELECT a.employee_id,b.employee_ext_id,c.person_nm,a.gradeval,c.birth_dttm,c.birthplace,"
                       . "c.adm_gender_cd,c.addr_txt,c.cell_phone,c.home_phone,c.marital_st,"
                       . "b.entrance_dttm,a.start_dttm,a.stop_dttm,(TO_DAYS(now())-TO_DAYS(b.entrance_dttm)) as jobage,"
                       . "c.person_id"
                       . " FROM ".XOCP_PREFIX."employee_job a"
                       . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
                       . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
                       . " WHERE a.job_id = '$job_id'";
                  $res2 = $db->query($sql);
                  if($db->getRowsNum($res2)>0) {
                     while(list($employee_id,$nip,$employee_nm,$gradeval,$dob,$pob,$gender,$addr,$cellphone,$phone,$marital,
                                $entrance_dttm,$jobstart,$jobstop,$jobage,$person_id)=$db->fetchRow($res2)) {
                        $arr_employee[$employee_id] = array($employee_nm,$nip,$job_nm,$job_abbr,$job_id,$gradeval,$job_desc,$dob,$pob,$gender,$addr,$cellphone,$phone,$marital,
                                                            $entrance_dttm,$jobstart,$jobstop,$jobage,$person_id);
                        $sql = "SELECT a.ccl,a.competency_id"
                             . " FROM ".XOCP_PREFIX."employee_competency a"
                             . " WHERE a.employee_id = '$employee_id'";
                        $resccl = $db->query($sql);
                        $ttl_ccl = 0;
                        $ttl_rcl = 0;
                        if($db->getRowsNum($resccl)>0) {
                           while(list($ccl,$competency_idx)=$db->fetchRow($resccl)) {
                              list($rcl,$itj)=$arr_param[$competency_idx];
                              $arr_ccl[$employee_id][$competency_idx] = $ccl;
                              $ttl_ccl = _bctrim(bcadd($ttl_ccl,bcmul($ccl,$itj)));
                              $ttl_rcl = _bctrim(bcadd($ttl_rcl,bcmul($rcl,$itj)));
                           }
                        }
                        $arr_ttl_ccl[$employee_id] = $ttl_ccl;
                        $arr_ttl_rcl[$employee_id] = $arr_xttl_rcl[$job_id];
                     }
                  }
               }
            }
            $assessor_job_count++;
         }
         
         if($assessor_job_count==0) {
            return "You don't have a job assigned. Please contact HR Administrator.";
         }
         
         
         
         $ret = "<script type='text/javascript' src='".XOCP_SERVER_SUBDIR."/include/prototip/js/prototip.js'></script>"
              . "<link rel='stylesheet' type='text/css' href='".XOCP_SERVER_SUBDIR."/include/prototip/css/prototip.css' />"
              . "<table class='assessment' style='border-top:0px solid #bbbbbb;width:100%;background-color:#ffffff;margin-top:-40px;'>"
              . "<colgroup><col width='290'/><col/></colgroup>"
              . "<tbody>"
              . "<tr><td style='border-left:1px solid #ffffff;text-align:center;'>&nbsp;</td>"
              . "<td style='border-left:0px solid #bbbbbb;border-bottom:1px solid #bbbbbb;border-right:0px solid #bbbbbb;'>"
              . "<ul class='ultab' style='padding-left:10px;'>";
         
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
               $ret .= "<li style='width:150px;' id='tab_${compgroup_id}' $class onclick='switchtab(\"$compgroup_id\",this,event);'>"
                     . "<span>$compgroup_nm</span>"
                     . "</li>";
               $tab++;
            }
         }
         $tab_arr_js = substr($tab_arr_js,0,-1) . ");";
         
         $ret .= "</ul></td></tr>";
         
         //// tab for soft/technical
         
         $ret .= "<tr><td style='border-left:0px solid #bbbbbb;'>&nbsp;</td>"
               . "<td style='background-color:#ffffff;border-bottom:1px solid #bbbbbb;border-left:1px solid #bbbbbb;padding-top:5px;border-right:1px solid #bbbbbb;'>";
         if(count($arr_compgroup)>0) {
            $tab = 0;
            foreach($arr_compgroup as $compgroup_id=>$compgroup_nm) {
            
               if($tab!=0) {
                  $display = "display:none;";
               } else {
                  $display = "";
               }
               
               $indentleft = 152;
               
               $ret .= "<ul id='ulx_${compgroup_id}' class='ultab' style='${display}padding-left:".(10+($tab*0))."px;'>";
 
               if(isset($arr_comp_class[$compgroup_id]["soft"])&&count($arr_comp_class[$compgroup_id]["soft"])>0) {
                  $class = "class='ultabsel_greyrev'";
                  $ret .= "<li style='width:100px;' id='tabsoft_${compgroup_id}' $class onclick='switchtabcompclass(\"soft\",\"$compgroup_id\",this,event);'>"
                        . "<span>Soft</span>"
                        . "</li>";
                  $class = "";
               } else {
                  $class = "class='ultabsel_greyrev'";
               }
        
               if(isset($arr_comp_class[$compgroup_id]["technical"])&&count($arr_comp_class[$compgroup_id]["technical"])>0) {
                  $ret .= "<li style='width:100px;' id='tabtech_${compgroup_id}' $class onclick='switchtabcompclass(\"tech\",\"$compgroup_id\",this,event);'>"
                        . "<span>Technical</span>"
                        . "</li>";
               }
               $ret .= "<li style='visibility:hidden;'><span>&nbsp;</span></li>";
               $ret .= "</ul>";
               $tab++;
            }
         }
         $ret .= "</td></tr>";
         
         
         //// heading
         $ret .= "<tr><td style='font-weight:bold;border-bottom:1px solid #bbbbbb;border-left:0px solid #bbbbbb;'>"
               . "</td><td style='border-left:1px solid #bbbbbb;background-color:#ffffff;'>";
         
         if(is_array($arr_compgroup)&&count($arr_compgroup)>0) {
            $tabno = 0;
            foreach($arr_compgroup as $compgroup_id=>$compgroup_nm) {
               if($tabno>0) {
                  $display = "display:none;";
               } else {
                  $display = "";
               }
               $ret .= "<div id='hd_${compgroup_id}' style='${display}padding:0px;'>";
               $tabclassno = 0;
               
               //// soft      //////////////////////////////////////////////////////
               if(isset($arr_comp_class[$compgroup_id]["soft"])&&count($arr_comp_class[$compgroup_id]["soft"])>0) {
                  $ret .= "<div id='hdclass_${compgroup_id}_soft' style='padding-left:10px;font-weight:bold;margin-left:-10px;"
                        . "border-bottom:1px solid #bbbbbb;border-right:1px solid #bbbbbb;'>"
                        . "<table class='assessment' border='0'><tbody><tr>";
                  $bgno=0;
                  foreach($arr_comp_class[$compgroup_id]["soft"] as $competency_id=>$v) {
                     //if($bgno>=4) continue;
                     list($rcl,$itj)=$v;
                     $bg = "border-right:1px solid #ffffff;border-bottom:0px solid #bbbbbb;";
                     list($competency_nm,$competency_abbr,$desc_en,$desc_id)=$arr_comp[$competency_id];
                     $comp_title = ($competency_abbr==""?"-":$competency_abbr);
                     $bgno++;
                     $ret .= "<td style='$bg' id='tcttl_${competency_id}'><div class='cntr' style='width:".(4*($cellwidth+4)+1)."px;overflow:hidden;margin-right:4px;'>"
                           . "<div style='margin-left:-100px;width:".(4*($cellwidth+4)+1+200)."px;padding-left:2px;text-align:center;cursor:default;'>"
                           . $comp_title
                           . "</div></div></td>";
                     $tooltips .= "\nnew Tip('tcttl_${competency_id}', \"<div>$desc_en</div><hr color='#999999' noshade='1' size='1'><div style='font-style:italic;'>$desc_id</div>\", {viewport:true,title:'$competency_nm',className:'competency'});";
                  }
                  /*
                  $ret .= "<td style='$bg'><div class='cntr' style='width:".(4*($cellwidth+4)+1)."px;overflow:hidden;margin-right:4px;font-weight:normal;'>"
                        . "<div style='margin-left:-100px;width:".(4*($cellwidth+4)+1+200)."px;padding-left:2px;text-align:center;cursor:default;'>"
                        . "[prev] [next]"
                        . "</div></div></td>";
                  */
                  $ret .= "</tr></tbody></table></div>";
                  $tabclassno++;
               }
               
               
               //// technical //////////////////////////////////////////////////////
               if(isset($arr_comp_class[$compgroup_id]["technical"])&&count($arr_comp_class[$compgroup_id]["technical"])>0) {
                  if($tabclassno>0) {
                     $display = "display:none;";
                  } else {
                     $display = "";
                  }
                  $ret .= "<div id='hdclass_${compgroup_id}_tech' style='${display}padding-left:10px;font-weight:bold;margin-left:-10px;"
                        . "border-right:1px solid #bbbbbb;border-bottom:1px solid #bbbbbb;'>"
                        . "<table class='assessment' border='0'><tbody><tr>";
                  $bgno = 0;
                  foreach($arr_comp_class[$compgroup_id]["technical"] as $competency_id=>$v) {
                     //if($bgno>=4) continue;
                     list($rcl,$itj)=$v;
                     $bg = "border-right:1px solid #ffffff;border-bottom:0px solid #bbbbbb;";
                     list($competency_nm,$competency_abbr,$desc_en,$desc_id)=$arr_comp[$competency_id];
                     $comp_title = ($competency_abbr==""?"-":$competency_abbr);
                     $bgno++;
                     $ret .= "<td style='$bg' id='tcttl_${competency_id}'><div class='cntr' style='width:".(4*($cellwidth+4)+1)."px;overflow:hidden;margin-right:4px;'>"
                           . "<div style='margin-left:-100px;width:".(4*($cellwidth+4)+1+200)."px;padding-left:2px;text-align:center;cursor:default;'>"
                           . $comp_title
                           . "</div></div></td>";
                     // $tooltips .= "\nnew Tip('tcttl_${competency_id}', '$competency_nm', {viewport:true});";
                     $tooltips .= "\nnew Tip('tcttl_${competency_id}', \"<div>$desc_en</div><hr color='#999999' noshade='1' size='1'><div style='font-style:italic;'>$desc_id</div>\", {viewport:true,title:'$competency_nm',className:'competency'});";
                  }
                  $ret .= "</tr></tbody></table></div>";
               }
               $ret .= "</div>";
               $tabno++;
            }
         }
         $ret .= "</td></tr>";
         
         ///////// heading for ccl rcl itj gap etc ... ////////////////////////////////////////////////////////////////////
         
         $ret .= "<tr><td style='font-weight:bold;border-bottom:1px solid #bbbbbb;border-left:1px solid #bbbbbb;background-color:#ffffff;'>"

               . "<div style='padding:0px;'><table class='assessment' border='0'><tbody><tr>"
                     . "<td style=''><div class='cntr' style='width:150px;overflow:hidden;margin-right:1px;'><div style='width:900px;'>Employee</div></div></td>"
                     . "<td style=''><div class='cntr' style='width:40px;overflow:hidden;margin-right:1px;'><div style='width:900px;'>Job</div></div></td>"
                     . "<td style=''><div class='cntr' style='width:40px;overflow:hidden;margin-right:1px;'><div style='margin-left:-100px;text-align:center;width:240px;'>Grade</div></div></td>"
                     . "<td style=''><div class='cntr' style='width:40px;overflow:hidden;margin-right:1px;'><div style='margin-left:-100px;text-align:center;width:240px;'>Job<br/>Match</div></div></td>"
                     . "</tr></tbody></table></div>"


               . "</td><td style='border-left:1px solid ${bordercomp};'>";
         
         if(is_array($arr_compgroup)&&count($arr_compgroup)>0) {
            $tabno = 0;
            foreach($arr_compgroup as $compgroup_id=>$compgroup_nm) {
               if($tabno>0) {
                  $display = "display:none;";
               } else {
                  $display = "";
               }
               $ret .= "<div id='hdxxx_${compgroup_id}' style='${display}padding:0px;'>";
               $tabclassno = 0;
               
               //// soft      //////////////////////////////////////////////////////
               if(isset($arr_comp_class[$compgroup_id]["soft"])&&count($arr_comp_class[$compgroup_id]["soft"])>0) {
                  $ret .= "<div id='hdclassxxx_${compgroup_id}_soft' style='padding-left:10px;margin-left:-10px;"
                        . "border-bottom:1px solid #bbbbbb;border-right:1px solid #bbbbbb;'>"
                        . "<table class='assessment' border='0'><tbody><tr>";
                  $bgno=0;
                  foreach($arr_comp_class[$compgroup_id]["soft"] as $competency_id=>$v) {
                     //if($bgno>=4) continue;
                     if($bgno%2!=1) {
                        $class = "hasscell0";
                     } else {
                        $class = "hasscell0";
                     }
                     $bgno++;
                     list($rcl,$itj)=$v;
                     $ret .= "<td class='$class' id='hccl_${competency_id}'><div class='cntr' style='height:${hdrheight}px;width:${cellwidth}px;overflow:hidden;'><div style='text-align:center;'><img src='".XOCP_SERVER_SUBDIR."/images/ccl.png'/></div></div></td>"
                           . "<td class='$class' id='hrcl_${competency_id}'><div class='cntr' style='height:${hdrheight}px;width:${cellwidth}px;overflow:hidden;'><div style='text-align:center;'><img src='".XOCP_SERVER_SUBDIR."/images/rcl.png'/></div></div></td>"
                           . "<td class='$class' id='hitj_${competency_id}'><div class='cntr' style='height:${hdrheight}px;width:${cellwidth}px;overflow:hidden;'><div style='text-align:center;'><img src='".XOCP_SERVER_SUBDIR."/images/itj.png'/></div></div></td>"
                           . "<td class='$class' id='hgap_${competency_id}' style='border-right:1px solid ${bordercomp};'><div class='cntr' style='height:${hdrheight}px;width:${cellwidth}px;overflow:hidden;'><div style='text-align:center;'><img src='".XOCP_SERVER_SUBDIR."/images/gap.png'/></div></div></td>";
                     $tooltips .= "\nnew Tip('hccl_${competency_id}', \"Current Competency Level\", {viewport:true});";
                     $tooltips .= "\nnew Tip('hrcl_${competency_id}', \"Required Competency Level\", {viewport:true});";
                     $tooltips .= "\nnew Tip('hitj_${competency_id}', \"Importance of Competency to Job\", {viewport:true});";
                     $tooltips .= "\nnew Tip('hgap_${competency_id}', \"Gap\", {viewport:true});";
                  }
                  $ret .= "</tr></tbody></table></div>";
                  $tabclassno++;
               }
               
               
               //// technical //////////////////////////////////////////////////////
               if(isset($arr_comp_class[$compgroup_id]["technical"])&&count($arr_comp_class[$compgroup_id]["technical"])>0) {
                  if($tabclassno>0) {
                     $display = "display:none;";
                  } else {
                     $display = "";
                  }
                  $ret .= "<div id='hdclassxxx_${compgroup_id}_tech' style='${display}padding-left:10px;margin-left:-10px;"
                        . "border-right:1px solid #bbbbbb;border-bottom:1px solid #bbbbbb;'>"
                        . "<table class='assessment' border='0'><tbody><tr>";
                  $bgno=0;
                  foreach($arr_comp_class[$compgroup_id]["technical"] as $competency_id=>$v) {
                     if($bgno%2!=1) {
                        $class = "hasscell0";
                     } else {
                        $class = "hasscell0";
                     }
                     $bgno++;
                     list($rcl,$itj)=$v;
                     $ret .= "<td id='hccl_${competency_id}' class='$class'><div class='cntr' style='height:${hdrheight}px;width:${cellwidth}px;overflow:hidden;'><div style='text-align:center;'><img src='".XOCP_SERVER_SUBDIR."/images/ccl.png'/></div></div></td>"
                           . "<td id='hrcl_${competency_id}' class='$class'><div class='cntr' style='height:${hdrheight}px;width:${cellwidth}px;overflow:hidden;'><div style='text-align:center;'><img src='".XOCP_SERVER_SUBDIR."/images/rcl.png'/></div></div></td>"
                           . "<td id='hitj_${competency_id}' class='$class'><div class='cntr' style='height:${hdrheight}px;width:${cellwidth}px;overflow:hidden;'><div style='text-align:center;'><img src='".XOCP_SERVER_SUBDIR."/images/itj.png'/></div></div></td>"
                           . "<td id='hgap_${competency_id}' class='$class' style='border-right:1px solid ${bordercomp};'><div class='cntr' style='height:${hdrheight}px;width:${cellwidth}px;overflow:hidden;'><div style='text-align:center;'><img src='".XOCP_SERVER_SUBDIR."/images/gap.png'/></div></div></td>";
                     $tooltips .= "\nnew Tip('hccl_${competency_id}', \"Current Competency Level\", {viewport:true});";
                     $tooltips .= "\nnew Tip('hrcl_${competency_id}', \"Required Competency Level\", {viewport:true});";
                     $tooltips .= "\nnew Tip('hitj_${competency_id}', \"Importance of Competency to Job\", {viewport:true});";
                     $tooltips .= "\nnew Tip('hgap_${competency_id}', \"Gap\", {viewport:true});";
                  }
                  $ret .= "</tr></tbody></table></div>";
               }
               $ret .= "</div>";
               $tabno++;
            }
         }
         $ret .= "</td></tr>";
         
         /////////// data begin ///////////////////////////////////////////////////////////////////////////////////////////
         $ret .= "<tr><td style='border-left:1px solid #bbbbbb;background-color:#ffffff;'>";
         if(is_array($arr_employee)&&count($arr_employee)>0) {
            foreach($arr_employee as $employee_id=>$employee) {
               list($employee_nm,$nip,$job_nm,$job_abbr,$job_id,$gradeval,$job_desc,$dob,$pob,$gender,$addr,$cellphone,$phone,$marital,
                    $entrance_dttm,$jobstart,$jobstop,$jobage,$person_id)=$employee;
               $matchcount = ($arr_ttl_rcl[$employee_id]>0?bcdiv($arr_ttl_ccl[$employee_id],$arr_ttl_rcl[$employee_id]):0);
               $match = number_format(_bctrim(bcmul(100,$matchcount)),1);
               if($matchcount < 0.8) {
                  $clr = "color:red;";
               } else {
                  $clr = "";
               }
               $ret .= "<div class='d1v' id='d1v_${employee_id}_${job_id}' style='border-bottom:1px solid #bbbbbb;padding:0px;' onmouseover='omover0(\"$employee_id\",\"$job_id\",this,event);' onmouseout='omout0(\"$employee_id\",\"$job_id\",this,event);'>"
                     . "<table class='assessment' border='0'><tbody><tr>"
                     . "<td id='emp_info_${employee_id}_${job_id}' style='cursor:default;'><div class='cntr' style='width:150px;overflow:hidden;margin-right:1px;'><div style='width:900px;'>$employee_nm</div></div></td>"
                     . "<td style='cursor:default;' id='tjobnm_${employee_id}_${job_id}'><div class='cntr' style='width:40px;overflow:hidden;margin-right:1px;'><div style='width:900px;'>$job_abbr</div></div></td>"
                     . "<td style=''><div class='cntr' style='width:40px;overflow:hidden;margin-right:1px;'><div style='text-align:center;width:40px;'>$gradeval</div></div></td>"
                     . "<td style='cursor:default;' id='match_${employee_id}_${job_id}'><div class='cntr' style='width:40px;overflow:hidden;margin-right:1px;'><div style='${clr}text-align:center;width:40px;'>$match%</div></div></td>"
                     . "</tr></tbody></table></div>";
               $tooltips .= "\nnew Tip('match_${employee_id}_${job_id}', \"Total CCL = ".$arr_ttl_ccl[$employee_id]."<br/>"
                          . "Total RCL = ".$arr_ttl_rcl[$employee_id]."<br/>"
                          . "Job Match = ${match}%\", {viewport:true,title:'Job Match',className:'competency'});";
               // . "<img src='".XOCP_SERVER_SUBDIR."/modules/hris/thumb.php?pid=$person_id'/>"
               
               $person_info = "<img src='".XOCP_SERVER_SUBDIR."/modules/hris/thumb.php?pid=$person_id' height='80'/><table style='margin-top:5px;' class='xxfrm' width='100%'>"
                            . "<colgroup><col width='90'/><col/></colgroup>"
                            . "<tbody>"
                            . "<tr><td>Employee ID :</td><td>$nip</td></tr>"
                            . "<tr><td>Date of Birth :</td><td>".sql2ind($dob,"date")."</td></tr>"
                            . "<tr><td>Place of Birth :</td><td>$pob</td></tr>"
                            . "<tr><td>Address :</td><td>$addr</td></tr>"
                            . "<tr><td>Phone :</td><td>$phone</td></tr>"
                            . "<tr><td>Cell Phone :</td><td>$cellphone</td></tr>"
                            . "<tr><td>Entrance Date :</td><td>".sql2ind($entrance_dttm,"date")." ("._bctrim(toMoney($jobage/365.25))." year)</td></tr>"
                            . "<tr><td>Job Start :</td><td>".sql2ind($jobstart,"date")."</td></tr>"
                            . "<tr><td>Sex :</td><td>".($gender=="f"?"Female":"Male")."</td></tr>"
                            . "<tr><td>Previous Job :</td><td></td></tr></tbody></table>";
                  // $job_desc = htmlentities
                  $job_desc = str_replace("\n","",$job_desc);
               $tooltips .= "\nnew Tip('tjobnm_${employee_id}_${job_id}', '$job_desc', {viewport:true,title:'$job_nm',className:'competency'});";
               $tooltips .= "\nnew Tip('emp_info_${employee_id}_${job_id}', \"$person_info\", {viewport:false,title:'$employee_nm',className:'competency'});";
            }
         }
         $tabcomp = "";
         if(is_array($arr_compgroup)&&count($arr_compgroup)>0) {
            $tabno = 0;
            foreach($arr_compgroup as $compgroup_id=>$compgroup_nm) {
               if($tabno>0) {
                  $displaygroup = "display:none;";
               } else {
                  $displaygroup = "";
               }
               $com = "";
               
               ///// soft ////////////////////////////////////////////////////////////
               if(isset($arr_comp_class[$compgroup_id]["soft"])&&count($arr_comp_class[$compgroup_id]["soft"])>0) {
                  $com .= "<div id='compcontentsoft_${compgroup_id}' style='padding:0px;'>";
                  foreach($arr_employee as $employee_id=>$employee) {
                     list($employee_nm,$nip,$job_nm,$job_abbr,$job_id)=$employee;
                     
                     $com .= "<div style='border-bottom:1px solid #cccccc;padding:0px;border-left:0px solid ${bordercomp};border-right:1px solid #bbbbbb;' class='d2v' id='d2v_${employee_id}_${job_id}_${compgroup_id}' onmouseover='omover(\"$employee_id\",\"$job_id\",\"$compgroup_id\",this,event);' onmouseout='omout(\"$employee_id\",\"$job_id\",\"$compgroup_id\",this,event);'>"
                           . "<table class='assessment' border='0'><tbody><tr>";
                     $bgno=0;
                     foreach($arr_comp_class[$compgroup_id]["soft"] as $competency_id=>$v) {
                        //if($bgno>=4) continue;
                        if($bgno%2!=1) {
                           $class = "asscell0";
                        } else {
                           $class = "asscell0";
                        }
                        $bgno++;
                        if(!isset($arr_job_competency[$job_id][$competency_id])) {
                           $ccl = $rcl = $itj = $gap = "-"; /// - : NA : Not Applicable
                           $tooltips .= "\nnew Tip('tdrcl_${employee_id}_${competency_id}', \"Not Applicable.\", {viewport:true,title:'Required Competency Level : NA'});";
                           $tooltips .= "\nnew Tip('tdccl_${employee_id}_${competency_id}', \"Not Applicable.\", {viewport:true,title:'Current Competency Level : NA'});";
                           $onclick = "";
                        } else {
                           $gapcolor = "";
                           list($rcl,$itj)=$arr_job_rcl[$competency_id][$job_id];
                           if(isset($arr_ccl[$employee_id][$competency_id])) {
                              $ccl = $arr_ccl[$employee_id][$competency_id];
                              $gap = $itj * ($ccl-$rcl);
                              if($gap<0) {
                                 $gapcolor = "color:#ff0000;font-weight:bold;";
                              }
                              $cclx = $ccl;
                           } else {
                              $ccl = "-";
                              $cclx = 0;
                              $gap = "-"; //$ccl-$rcl;
                           }
                           
                           $len = "<ul style='margin:0px;margin-left:-25px;'>";
                           $lid = "<ul style='font-style:italic;margin:0px;margin-left:-25px;'>";
                           for($l=$rcl;$l>0;$l--) {
                              list($level_en,$level_id)=$arr_desclvl[$competency_id][$l];
                              $len .= "<li>$level_en</li>";
                              $lid .= "<li>$level_id</li>";
                           }
                           $len .= "</ul>"; $lid .= "</ul>";
                           $rcl_title = $proficiency_level_name[$rcl];
                           $rcl_desc = "$len<hr noshade='1' size='1'/>$lid";
                           $len = "<ul style='margin:0px;margin-left:-25px;'>";
                           $lid = "<ul style='font-style:italic;margin:0px;margin-left:-25px;'>";
                           for($l=$ccl;$l>0;$l--) {
                              list($level_en,$level_id)=$arr_desclvl[$competency_id][$l];
                              $len .= "<li>$level_en</li>";
                              $lid .= "<li>$level_id</li>";
                           }
                           $len .= "</ul>"; $lid .= "</ul>";
                           $ccl_title = $proficiency_level_name[$cclx];
                           $ccl_desc = "$len<hr noshade='1' size='1'/>$lid";
                           
                           $tooltips .= "\nnew Tip('tdrcl_${employee_id}_${competency_id}', \"$rcl_desc\", {viewport:true,title:'Required Competency Level : $rcl_title ($rcl)'});";
                           $tooltips .= "\nnew Tip('tdccl_${employee_id}_${competency_id}', \"$ccl_desc\", {viewport:true,title:'Current Competency Level : $ccl_title ($cclx)'});";
                           
                           
                           $onclick = "onclick='asm(\"$employee_id\",\"$competency_id\",this,event);'";
                        }
                        
                        $com .= "<td id='tdccl_${employee_id}_${competency_id}' $onclick class='assccl'><div class='cntr' style='width:${cellwidth}px;overflow:hidden;'><div style='text-align:center;'>$ccl</div></div></td>"
                              . "<td class='asscell2' id='tdrcl_${employee_id}_${competency_id}'><div class='cntr' style='width:${cellwidth}px;overflow:hidden;'><div style='text-align:center;'>$rcl</div></div></td>"
                              . "<td class='asscell2'><div class='cntr' style='width:${cellwidth}px;overflow:hidden;'><div style='text-align:center;'>$itj</div></div></td>"
                              . "<td class='asscell2' style='border-right:1px solid ${bordercomp};'><div class='cntr' style='width:${cellwidth}px;overflow:hidden;'><div style='text-align:center;${gapcolor}'>$gap</div></div></td>";
                     }
                     $com .= "</tr></tbody></table></div>";
                  }
                  
                  $display = "display:none;";
                  $com .= "</div>";
               } else {
                  $display = "";
               }
                  
               ///// technical ////////////////////////////////////////////////////////////
               if(isset($arr_comp_class[$compgroup_id]["technical"])&&count($arr_comp_class[$compgroup_id]["technical"])>0) {
                  $com .= "<div id='compcontenttech_${compgroup_id}' style='${display}padding:0px;'>";
                  foreach($arr_employee as $employee_id=>$employee) {
                     list($employee_nm,$nip,$job_nm,$job_abbr,$job_id)=$employee;
                     
                     $com .= "<div style='border-bottom:1px solid #bbbbbb;border-left:0px solid ${bordercomp};padding:0px;border-right:1px solid #bbbbbb;' class='d3v' id='d3v_${employee_id}_${job_id}_${compgroup_id}' onmouseover='omover(\"$employee_id\",\"$job_id\",\"$compgroup_id\",this,event);' onmouseout='omout(\"$employee_id\",\"$job_id\",\"$compgroup_id\",this,event);'>"
                           . "<table class='assessment' border='0'><tbody><tr>";
                     $bgno = 0;
                     foreach($arr_comp_class[$compgroup_id]["technical"] as $competency_id=>$v) {
                        if($bgno%2!=1) {
                           $class = "asscell0";
                        } else {
                           $class = "asscell0";
                        }
                        $bgno++;
                        if(!isset($arr_job_competency[$job_id][$competency_id])) {
                           $ccl = $rcl = $itj = $gap = "-"; /// - : NA : Not Applicable
                           $tooltips .= "\nnew Tip('tdrcl_${employee_id}_${competency_id}', \"Not Applicable.\", {viewport:true,title:'Required Competency Level : NA'});";
                           $tooltips .= "\nnew Tip('tdccl_${employee_id}_${competency_id}', \"Not Applicable.\", {viewport:true,title:'Current Competency Level : NA'});";
                           $onclick = "";
                        } else {
                           $gapcolor = "";
                           list($rcl,$itj)=$arr_job_rcl[$competency_id][$job_id];
                           if(isset($arr_ccl[$employee_id][$competency_id])) {
                              $ccl = $arr_ccl[$employee_id][$competency_id];
                              $gap = $itj * ($ccl-$rcl);
                              if($gap<0) {
                                 $gapcolor = "color:#ff0000;font-weight:bold;";
                              }
                              $cclx = $ccl;
                           } else {
                              $ccl = "-";
                              $gap = "-"; //$ccl-$rcl;
                              $cclx = 0;
                           }
                           $len = "<ul style='margin:0px;margin-left:-25px;'>";
                           $lid = "<ul style='font-style:italic;margin:0px;margin-left:-25px;'>";
                           for($l=$rcl;$l>0;$l--) {
                              list($level_en,$level_id)=$arr_desclvl[$competency_id][$l];
                              $len .= "<li>$level_en</li>";
                              $lid .= "<li>$level_id</li>";
                           }
                           $len .= "</ul>"; $lid .= "</ul>";
                           $rcl_title = $proficiency_level_name[$rcl];
                           $rcl_desc = "$len<hr noshade='1' size='1'/>$lid";
                           $len = "<ul style='margin:0px;margin-left:-25px;'>";
                           $lid = "<ul style='font-style:italic;margin:0px;margin-left:-25px;'>";
                           for($l=$ccl;$l>0;$l--) {
                              list($level_en,$level_id)=$arr_desclvl[$competency_id][$l];
                              $len .= "<li>$level_en</li>";
                              $lid .= "<li>$level_id</li>";
                           }
                           $len .= "</ul>"; $lid .= "</ul>";
                           $ccl_title = $proficiency_level_name[$cclx];
                           $ccl_desc = "$len<hr noshade='1' size='1'/>$lid";
                           
                           $tooltips .= "\nnew Tip('tdrcl_${employee_id}_${competency_id}', \"$rcl_desc\", {viewport:true,title:'Required Competency Level : $rcl_title ($rcl)'});";
                           $tooltips .= "\nnew Tip('tdccl_${employee_id}_${competency_id}', \"$ccl_desc\", {viewport:true,title:'Current Competency Level : $ccl_title ($cclx)'});";

                           $onclick = "onclick='asm(\"$employee_id\",\"$competency_id\",this,event);'";
                           
                        }
                        
                        $com .= "<td id='tdccl_${employee_id}_${competency_id}' $onclick class='assccl'><div class='cntr' style='width:${cellwidth}px;overflow:hidden;'><div style='text-align:center;'>$ccl</div></div></td>"
                              . "<td class='asscell2' id='tdrcl_${employee_id}_${competency_id}'><div class='cntr' style='width:${cellwidth}px;overflow:hidden;'><div style='text-align:center;'>$rcl</div></div></td>"
                              . "<td class='asscell2'><div class='cntr' style='width:${cellwidth}px;overflow:hidden;'><div style='text-align:center;'>$itj</div></div></td>"
                              . "<td class='asscell2' style='border-right:1px solid ${bordercomp};'><div class='cntr' style='width:${cellwidth}px;overflow:hidden;'><div style='text-align:center;'>$gap</div></div></td>";
                     }
                     $com .= "</tr></tbody></table></div>";
                  }
                  
                  
                  $com .= "</div>";
               }
               
               $tabcomp .= "<div id='tabcontent_${compgroup_id}' style='$displaygroup'>$com</div>";
               $tabno++;
            }
         }
         $ret .= "</td>";
         $ret .= "<td style='border-left:1px solid ${bordercomp}'>$tabcomp</td>";
         $ret .= "</tr></tbody></table>";
         
      } else {
         return "You don't have a job assigned. Please contact HR Administrator.";
      }
      
      
      return "<div style='min-width:800px;'>
      $ret
      </div><script type='text/javascript'><!--
      
      function omover(employee_id,job_id,compgroup_id,d,e) {
         return;
         $('d1v_'+employee_id+'_'+job_id+'_'+compgroup_id).className = 'dvempx';
         $('d2v_'+employee_id+'_'+job_id+'_'+compgroup_id).className = 'dvempx';
         $('d3v_'+employee_id+'_'+job_id+'_'+compgroup_id).className = 'dvempx';
      }
      
      function omout(employee_id,job_id,compgroup_id,d,e) {
         return;
         $('d1v_'+employee_id+'_'+job_id+'_'+compgroup_id).className = '';
         $('d2v_'+employee_id+'_'+job_id+'_'+compgroup_id).className = '';
         $('d3v_'+employee_id+'_'+job_id+'_'+compgroup_id).className = '';
      }
      
      function omover0(employee_id,job_id,compgroup_id,d,e) {
         return;
         $('d1v_'+employee_id+'_'+job_id).className = 'dvempx';
         $('d2v_'+employee_id+'_'+job_id).className = 'dvempx';
         $('d3v_'+employee_id+'_'+job_id).className = 'dvempx';
      }
      
      function omout0(employee_id,job_id,d,e) {
         return;
         $('d1v_'+employee_id+'_'+job_id).className = '';
         $('d2v_'+employee_id+'_'+job_id).className = '';
         $('d3v_'+employee_id+'_'+job_id).className = '';
      }
      
      function asm(employee_id,competency_id,d,e) {
         location = '".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&asm=y&eid='+employee_id+'&cid='+competency_id;
      }
      
      $tab_arr_js
      function switchtab(compgroup_id,d,e) {
         for(var i=0;i<tabs.length;i++) {
            $('tab_'+tabs[i]).className = '';
            $('tabcontent_'+tabs[i]).style.display = 'none';
            $('hd_'+tabs[i]).style.display = 'none';
            $('hdxxx_'+tabs[i]).style.display = 'none';
            $('ulx_'+tabs[i]).style.display = 'none';
         }
         $('tab_'+compgroup_id).className = 'ultabsel_greyrev';
         $('tabcontent_'+compgroup_id).style.display = '';
         $('hd_'+compgroup_id).style.display = '';
         $('hdxxx_'+compgroup_id).style.display = '';
         $('ulx_'+compgroup_id).style.display = '';
      }
      
      function switchtabcompclass(xclass,compgroup_id,d,e) {
         switch(xclass) {
            case 'soft':
               if($('tabtech_'+compgroup_id)) $('tabtech_'+compgroup_id).className = '';
               if($('tabsoft_'+compgroup_id)) $('tabsoft_'+compgroup_id).className = 'ultabsel_greyrev';
               if($('hdclass_'+compgroup_id+'_soft')) $('hdclass_'+compgroup_id+'_soft').style.display = '';
               if($('hdclass_'+compgroup_id+'_tech')) $('hdclass_'+compgroup_id+'_tech').style.display = 'none';
               if($('hdclassxxx_'+compgroup_id+'_soft')) $('hdclassxxx_'+compgroup_id+'_soft').style.display = '';
               if($('hdclassxxx_'+compgroup_id+'_tech')) $('hdclassxxx_'+compgroup_id+'_tech').style.display = 'none';
               if($('compcontentsoft_'+compgroup_id)) $('compcontentsoft_'+compgroup_id).style.display = '';
               if($('compcontenttech_'+compgroup_id)) $('compcontenttech_'+compgroup_id).style.display = 'none';
               break;
            default:
               if($('tabsoft_'+compgroup_id)) $('tabsoft_'+compgroup_id).className = '';
               if($('tabtech_'+compgroup_id)) $('tabtech_'+compgroup_id).className = 'ultabsel_greyrev';
               if($('hdclass_'+compgroup_id+'_tech')) $('hdclass_'+compgroup_id+'_tech').style.display = '';
               if($('hdclass_'+compgroup_id+'_soft')) $('hdclass_'+compgroup_id+'_soft').style.display = 'none';
               if($('hdclassxxx_'+compgroup_id+'_tech')) $('hdclassxxx_'+compgroup_id+'_tech').style.display = '';
               if($('hdclassxxx_'+compgroup_id+'_soft')) $('hdclassxxx_'+compgroup_id+'_soft').style.display = 'none';
               if($('compcontentsoft_'+compgroup_id)) $('compcontentsoft_'+compgroup_id).style.display = 'none';
               if($('compcontenttech_'+compgroup_id)) $('compcontenttech_'+compgroup_id).style.display = '';
               break;
         }
      }
      
      $tooltips
      
      // --></script>";
   }
   
   
   function form($employee_id,$competency_id) {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_assessment.php");
      $ajax = new _hris_class_AssessmentAjax("asjx");
      $sql = "SELECT a.employee_ext_id,b.person_nm"
           . " FROM ".XOCP_PREFIX."employee a"
           . " LEFT JOIN ".XOCP_PREFIX."persons b USING(person_id)"
           . " WHERE a.employee_id = '$employee_id'";
      $result = $db->query($sql);
      list($nip,$employee_nm)=$db->fetchRow($result);
      $sql = "SELECT b.employee_ext_id,c.person_nm,a.gradeval,c.birth_dttm,c.birthplace,"
           . "c.adm_gender_cd,c.addr_txt,c.cell_phone,c.home_phone,c.marital_st,"
           . "b.entrance_dttm,a.start_dttm,a.stop_dttm,(TO_DAYS(now())-TO_DAYS(b.entrance_dttm)) as jobage,"
           . "a.job_id,d.job_cd,d.job_nm,c.person_id"
           . " FROM ".XOCP_PREFIX."employee_job a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " LEFT JOIN ".XOCP_PREFIX."jobs d ON d.job_id = a.job_id"
           . " WHERE a.employee_id = '$employee_id'";
      $res2 = $db->query($sql);
      list($nip,$employee_nm,$gradeval,$dob,$pob,$gender,$addr,$cellphone,$phone,$marital,
           $entrance_dttm,$jobstart,$jobstop,$jobage,$job_id,$job_cd,$job_nm,$person_id)=$db->fetchRow($res2);

      $rcl = 0; $itj = 0;
      $sql = "SELECT rcl,itj FROM ".XOCP_PREFIX."job_competency"
           . " WHERE job_id = '$job_id'"
           . " AND competency_id = '$competency_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($rcl,$itj)=$db->fetchRow($result);
      }
      
      $_SESSION["empl_job_id"] = $job_id;
      
      $person_info = "<img src='".XOCP_SERVER_SUBDIR."/modules/hris/thumb.php?pid=$person_id' height='80'/><table style='margin-top:5px;' class='xxfrm' width='100%'>"
                   . "<colgroup><col width='90'/><col/></colgroup>"
                   . "<tbody>"
                   . "<tr><td>Employee ID :</td><td>$nip</td></tr>"
                   . "<tr><td>Date of Birth :</td><td>".sql2ind($dob,"date")."</td></tr>"
                   . "<tr><td>Place of Birth :</td><td>$pob</td></tr>"
                   . "<tr><td>Address :</td><td>$addr</td></tr>"
                   . "<tr><td>Phone :</td><td>$phone</td></tr>"
                   . "<tr><td>Cell Phone :</td><td>$cellphone</td></tr>"
                   . "<tr><td>Entrance Date :</td><td>".sql2ind($entrance_dttm,"date")." ("._bctrim(toMoney($jobage/365.25))." year)</td></tr>"
                   . "<tr><td>Job Start :</td><td>".sql2ind($jobstart,"date")."</td></tr>"
                   . "<tr><td>Sex :</td><td>".($gender=="f"?"Female":"Male")."</td></tr>"
                   . "<tr><td>Previous Job :</td><td></td></tr></tbody></table>";
      $prson_info = "<img src='".XOCP_SERVER_SUBDIR."/images/pic.jpg' height='80'/><br/>"
                   . "Employee ID : $nip<br/>"
                   . "Date of Birth : ".sql2ind($dob,"date")."<br/>"
                   . "Place of Birth : $pob<br/>"
                   . "Address : $addr<br/>"
                   . "Phone : $phone<br/>"
                   . "Cell Phone : $cellphone<br/>";
      $tooltips .= "\nnew Tip('emp_data', \"$person_info\", {viewport:false,title:'$employee_nm',className:'competency'});";
      
      $sql = "SELECT a.competency_nm,a.desc_en,a.desc_id,"
           . "b.compgroup_nm,a.competency_class,a.competency_abbr"
           . " FROM ".XOCP_PREFIX."competency a"
           . " LEFT JOIN ".XOCP_PREFIX."compgroup b USING(compgroup_id)"
           . " WHERE a.competency_id = '$competency_id'";
      $result = $db->query($sql);
      list($competency_nm,$desc_en,$desc_id,$compgroup_nm,$competency_class,$competency_abbr)=$db->fetchRow($result);
      
      list($acl,$ccl,$rcl,$itj,$gap) = $ajax->getCurrentAssessment($employee_id,$competency_id);
      global $proficiency_level_name;
      $current_level = $proficiency_level_name[$ccl]." ($ccl)";
      $assessment_level = $proficiency_level_name[$acl]. " ($acl)";
      
      $ret = "<script type='text/javascript' src='".XOCP_SERVER_SUBDIR."/include/prototip/js/prototip.js'></script>"
           . "<link rel='stylesheet' type='text/css' href='".XOCP_SERVER_SUBDIR."/include/prototip/css/prototip.css' />";
      
           ///// title and employee information
      $ret .= "<div style='padding:4px;font-weight:bold;'>ASSESSMENT FORM</div>"
           . "<div style='background-color:#eeeeee;margin-bottom:10px;border:1px solid #999999;padding:5px;'>"
           . "<table style='width:100%;' border='0' cellpadding='0' cellspacing='0'><tbody><tr>"
           . "<td style='cursor:default;'><span id='emp_data' style='font-weight:bold;color:black;'>$employee_nm [$nip]</span> as $job_nm [$job_cd]</td>"
           . "<td style='text-align:right;'>[<span class='xlnk' onclick='summarypage();'>back</span>]</td></tr>"
           . "<tr><td colspan='2'>"
           . "<table style='border:1px solid #777777;background-color:#ffffff;border-spacing:0px;text-align:center;margin:3px;'>"
           . "<colgroup><col width='140'/><col width='140'/><col width='140'/><col width='140'/></colgroup>"
           . "<tbody><tr>"
           . "<td style='padding:2px;border-right:1px solid #777777;'>CCL : <span id='emp_ccl'>$current_level</span></td>"
           . "<td style='padding:2px;border-right:1px solid #777777;'>RCL : ".$proficiency_level_name[$rcl]." ($rcl)</td>"
           . "<td style='padding:2px;border-right:1px solid #777777;'>ITJ : $itj</td>"
           . "<td style='padding:2px;'>GAP : <span id='empl_gap'>$gap</span></td>"
           . "</tr></tbody></table>"
           . "</td>"
           . "</tr></tbody></table>"
           . "</div>"
           
           ///// current competency assessment
           . "<div style='margin-bottom:10px;'><table style=''>"
           . "<colgroup><col width='90'/><col/></colgroup><tbody>"
           . "<tr><td colspan='2' style='padding:10px;font-size:+1.5em;text-align:center;'>$competency_abbr $competency_nm / ".ucfirst($competency_class)."</td></tr>"
           . "<tr>"
           . "<td>Description:</td><td><span style=''>$desc_en</span><hr noshade='1' size=1' color='#bbbbbb'/><span style='font-style:italic;'>$desc_id</span></td>"
           . "</tr></tbody></table>"
           . "</div>"
           . "<div style='border:0px solid #999999;padding:5px;'>"
           
           //. "<div style='background-color:#eeeeee;border:1px solid #bbbbbb;padding:5px;'>"
           
           //. "<table border='0' style='width:100%;'><tr><td>Assessment Level : <span id='emp_acl'>$assessment_level</span></td>"
           //. "<td style='text-align:right;'>"
           
           //. "&nbsp;<input type='button' value='Save' onclick='save_form(this,event);'/>&nbsp;&nbsp;"
           //. "&nbsp;<input type='button' value='Previous' onclick='goprev(this,event);'/>"
           //. "&nbsp;<input type='button' value='Next' onclick='gonext(this,event);'/>"
           
           //. "</td></tr></table>"
           //. "</div>"
           
           . "<div style='padding:0px;border:1px solid #999999;'><form id='frm'>"
           
           . $ajax->getQuestions($employee_id,$competency_id,$acl)
           
           . "</form>"
           //. "<div style='text-align:right;margin:5px;'>"
           //. "&nbsp;<input type='button' value='Finish' onclick='save_form(this,event);'/>&nbsp;&nbsp;"
           //. "&nbsp;<input type='button' value='Previous' onclick='goprev(this,event);'/>"
           //. "&nbsp;<input type='button' value='Next' onclick='gonext(this,event);'/>"
           //. "</div>"
           . "</div>"
           
           . "</div><div style='padding:50px;'></div>";
      
      $ret .= $ajax->getJs()."<script type='text/javascript' language='javascript'><!--
      
      $tooltips
      
      var acl = '$acl';
      var ccl = '$ccl';
      var cbh = '$behaviour_id';
      var competency_id = '$competency_id';
      
      function ckrad(no,cls,d,e) {
         $('question_'+no).className = 'trasm_'+cls;
      }
      
      var saveconfirm = null;
      var saveconfirm2 = null;
      function save_form(d,e) {
         var ret = parseForm('frm');
         asjx_app_saveAssessment(ret,function(_data) {
            var data = recjsarray(_data);
            $('emp_ccl').innerHTML = data[0];
            ccl = data[1];
            $('empl_gap').innerHTML = data[4];
            saveconfirm2.innerHTML = data[3];
         });
         var dvback = _dce('div');
         dvback.setAttribute('style','height:150%;width:150%;position:fixed;top:0px;left:-10px;background-color:#cccccc;opacity:0.8;z-index:10000');
         saveconfirm = document.body.appendChild(dvback);
         var dv = _dce('div');
         dv.setAttribute('style','padding-top:15px;padding-bottom:15px;width:300px;position:fixed;top:50%;left:50%;background-color:white;border:1px solid #555555;margin-left:-150px;margin-top:-75px;opacity:1;z-index:10001;');
         dv.appendChild(progress_span(' ... saving'));
         saveconfirm2 = document.body.appendChild(dv);
      }

      function goprev(d,e) {
         $('frm').innerHTML = '';
         $('frm').appendChild(progress_span(' ... previous level'));
         asjx_app_getPreviousQuestions(competency_id,function(_data) {
            var data = recjsarray(_data);
            $('frm').innerHTML = data[0];
            $('emp_acl').innerHTML = data[1];
         });
      }
      
      function confirmgonext(d,e) {
         _destroy(saveconfirm);
         _destroy(saveconfirm2);
         gonext(d,e);
      }
      
      function review(d,e) {
         _destroy(saveconfirm);
         _destroy(saveconfirm2);
      }
      
      function gonext(d,e) {
         $('frm').innerHTML = '';
         $('frm').appendChild(progress_span(' ... next level'));
         asjx_app_getNextQuestions(competency_id,function(_data) {
            var data = recjsarray(_data);
            $('frm').innerHTML = data[0];
            $('emp_acl').innerHTML = data[1];
         });
      }
      
      function summarypage() {
         location = '".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&summarypage=y';
      }
      
      // --></script>";
      return $ret;
   }
   
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            if(isset($_GET["asm"])&&$_GET["asm"]=="y") {
               $_SESSION["assessment_page"] = "form";
               $_SESSION["assessment_employee_id"] = $_GET["eid"];
               $_SESSION["assessment_competency_id"] = $_GET["cid"];
               $ret = $this->form($_SESSION["assessment_employee_id"],$_SESSION["assessment_competency_id"]);
            } elseif(isset($_GET["summarypage"])&&$_GET["summarypage"]=="y") {
               $_SESSION["assessment_page"] = "summary";
               $ret = $this->assessment();
            } else {
               if(isset($_SESSION["assessment_page"])&&$_SESSION["assessment_page"]=="form") {
                  $ret = $this->form($_SESSION["assessment_employee_id"],$_SESSION["assessment_competency_id"]);
               } else {
                  $ret = $this->assessment();
               }
            }
            break;
         default:
            if(isset($_SESSION["assessment_page"])&&$_SESSION["assessment_page"]=="form") {
               $ret = $this->form($_SESSION["assessment_employee_id"],$_SESSION["assessment_competency_id"]);
            } else {
               $ret = $this->assessment();
            }
            break;
      }
      return $ret;
   }
}

} // HRIS_ASSESSMENT_DEFINED
?>
