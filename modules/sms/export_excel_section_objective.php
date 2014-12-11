<?php 
	$subdir = "hris_pro";

  global $allow_add_objective;
  define("XOCP_DOC_ROOT","/var/www/html/hris");   
	include_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
  include_once(XOCP_DOC_ROOT."/modules/sms/include/sms.php");   
	include_once(XOCP_DOC_ROOT."/config.php");
  global $xocp_vars;
	$db =& Database::getInstance();
	$psid = $_GET['psid']; 
  $person_id = $_GET['person_id']; 
  $org_id = $_GET['org_id'];  

	header("Content-type: application/octet-stream");
	header("Content-Disposition: attachment; filename=export-$psid-$user_id.xls");//ganti nama sesuai keperluan
	header("Pragma: no-cache");
	header("Expires: 0");
		
	//disini script laporan anda
	
  $sql = "SELECT org_class_id FROM ".XOCP_PREFIX."orgs WHERE org_id = '$org_id'";
      $result = $db->query($sql);
      list($current_org_class_id)=$db->fetchRow($result);
      
      $sub_orgs = array();
      $sql = "SELECT org_id,org_abbr,org_nm FROM ".XOCP_PREFIX."orgs"
           . " WHERE parent_id = '$org_id' AND status_cd = 'normal'";
      $result = $db->query($sql);
      $arr_sub_org = array();
      $sql = "DELETE FROM sms_org_share WHERE id_section_session = '$psid' AND sms_org_id = '$org_id'";
      $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($sub_org_id)=$db->fetchRow($result)) {
            $sql = "REPLACE INTO sms_org_share (id_section_session,sms_org_id,sms_share_org_id) VALUES ('$psid','$org_id','$sub_org_id')";
            $db->query($sql);
            $sub_orgs[$sub_org_id] = 1;
         }
      }
      
      $sql = "SELECT a.sms_share_org_id,b.org_abbr,b.org_nm,b.org_class_id"
           . " FROM sms_org_share a"
           . " LEFT JOIN ".XOCP_PREFIX."orgs b ON b.org_id = a.sms_share_org_id"
           . " LEFT JOIN ".XOCP_PREFIX."org_class c USING(org_class_id)"
           . " WHERE a.sms_org_id = '$org_id'"
           . " AND a.id_section_session = '$psid'"
           . " AND b.org_class_id < 5"
           . " ORDER BY b.order_no";
      $result = $db->query($sql);
      $tdshare = "";
      $share_arr = array();
      $share_cnt = 0;
      $colgroup = "";
      $has_no_sub_shared = 0;

      // show pic

      $sqlx = "SELECT a.employee_id,a.alias_nm,a.person_id,"
       . "b.person_nm,d.job_id,e.org_id,f.org_class_id "
       . " FROM ".XOCP_PREFIX."employee a"
       . " LEFT JOIN ".XOCP_PREFIX."persons b USING(person_id)"
       . " LEFT JOIN ".XOCP_PREFIX."employee_job c USING(employee_id)"
       . " LEFT JOIN ".XOCP_PREFIX."jobs d ON d.job_id = c.job_id"
       . " LEFT JOIN ".XOCP_PREFIX."orgs e USING(org_id)"
       . " LEFT JOIN ".XOCP_PREFIX."org_class f USING(org_class_id)"
       . " WHERE a.status_cd = 'normal' AND e.org_id = '$org_id' AND f.org_class_id = '$current_org_class_id' AND c.gradeval > 5"
       . " ORDER BY a.entrance_dttm ASC";
      $resultx = $db->query($sqlx);
      $picnum = $db->getRowsNum($resultx);
      $tdpic = "";
      $empid = "";
      $i = 0;
      if($db->getRowsNum($resultx)>0) {
         while(list($employee_idx,$alias_nmx,$person_idx,$person_nmx)=$db->fetchRow($resultx)) {
          $empid .= $employee_idx;
          $fname = explode(" ", $person_nmx);
          if ($i < ($picnum-1)) {
            $empid .= ",";
          }
            $tdpic .= "<td style='border-bottom:1px solid #333;border-right:1px solid #bbb;width:50px;'>".($alias_nmx==""?$fname[0]:$alias_nmx)."</td>";
          $i++;
         }
      }else{
        $tdpic .="<td style='border-bottom:1px solid #333;border-right:1px solid #bbb;width:25px;>"._EMPTY."</td>";
      }

      if($db->getRowsNum($result)>0) {
         $share_cnt = $db->getRowsNum($result);
         $sharehead = "";
         while(list($sms_share_org_id,$sms_share_org_abbr,$sms_share_org_nm)=$db->fetchRow($result)) {
            
            
            $tdshare .= "<td style='border-bottom:1px solid #333;border-left:1px solid #bbb;text-align:center;'><span class='xlnk' onclick='view_share(\"$sms_share_org_id\",this,event);'>$sms_share_org_abbr</span></td>";
            $share_arr[] = array($sms_share_org_id,$sms_share_org_nm,$sms_share_org_abbr);
            $colgroup .= "<col width='50'/>";
         }
      } else {
         $has_no_sub_shared = 1;
         $tdshare .= ""; //"<td style='border-bottom:1px solid #333;border-left:1px solid #bbb;text-align:center;'>-</td>";
         $sharehead = "";
         $colgroup .= ""; //"<col width='50'/>";
      }
      
      $sql = "SELECT a.org_abbr,a.org_nm,b.org_class_nm FROM ".XOCP_PREFIX."orgs a LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . " WHERE org_id = '".$_SESSION["sms_org_id"]."'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($org_abbr,$org_nm,$org_class_nm)=$db->fetchRow($result);
         $orgsel = "<div style='padding:5px;border:1px solid #bbb;background-color:#ddd;'><span id='orgspan' class='xlnk' onclick='select_org(this,event);'>Level Organization : <span style='font-weight:bold;'>$org_nm $org_class_nm</span></span></div>";
      }



      $ret = "<table>"
          . "<tr colspan='5'>"
          . "<td></td><td></td><td></td>"
          . "<td>SMS Section Objective<td>"
          . "</tr></table>";
      
      $ret .= "<table class='xxlist' style='width:100%;'>"
           . "<colgroup>"
           . "<col width='320'/>"
           . "<col width='200'/>"
           . "<col width='50'/>"
           . "<col width='50'/>"
           . "<col width='150'/>"
           . "<col width='*'/>"
           . $colgroup
           . "</colgroup>"
           . "<thead>"
           . $sharehead;
           
      $trhd = "<tr>"
           . "<td style='border-bottom:1px solid #333;border-right:1px solid #bbb;'>Objective</td>"
           . "<td style='border-bottom:1px solid #333;border-right:1px solid #bbb;'>KPI</td>"
           . "<td style='border-bottom:1px solid #333;border-right:1px solid #bbb;'>Unit</td>"
           . "<td style='border-bottom:1px solid #333;border-right:1px solid #bbb;'>Target</td>"
           . "<td style='border-bottom:1px solid #333;border-right:1px solid #bbb;'>Action Plan</td>"
           . "<td colspan='".($picnum)."' style='border-bottom:1px solid #333;border-right:0px solid #bbb;'>PIC</td>"
           . $tdshare
           . "</tr>";


      $rowpic = "<tr>"
           . "<td style='border-bottom:1px solid #333;border-right:1px solid #bbb;'></td>"
           . "<td style='border-bottom:1px solid #333;border-right:1px solid #bbb;'></td>"
           . "<td style='border-bottom:1px solid #333;border-right:1px solid #bbb;'></td>"
           . "<td style='border-bottom:1px solid #333;border-right:1px solid #bbb;'></td>"
           . "<td style='border-bottom:1px solid #333;border-right:1px solid #bbb;'></td>"
           . $tdpic
           . "</tr>";
      
      //$ret .= $trhd;
      
      $ret .= "</thead>"
           . "<tbody>";
      
      //$sql = "SELECT code,id,title FROM sms_section_perspective WHERE id_section_session = '$psid' ORDER BY id";
      //$result = $db->query($sql);
      //list($sms_perspective_code,$sms_perspective_id,$sms_perspective_name)=$db->fetchRow($result)
      
      //$ttlw = 0;
      //$ttl_sms_share = array();
      $job_nm = $job_abbr = "";
      //if($db->getRowsNum($result)>0) {
         //while(list($sms_perspective_code,$sms_perspective_id,$sms_perspective_name)=$db->fetchRow($result)) {
            $ret .= "<tr><td style='border:0px;border-bottom:3px solid #333;background-color:#fff;' colspan='".(5+$picnum)."'>&nbsp;</td></tr>"
                  //. "<tr><td colspan='".(6+$picnum)."' style='font-weight:bold;border-bottom:1px solid #333;color:black;background-color:#ddf;padding:10px;'>"
                  //. "</td>"
                  . ($tdshare!=""?"<td colspan='".($share_cnt==0?0:$share_cnt)."' style='border-bottom:1px solid #333;background-color:#ddf;padding:10px;border-left:1px solid #bbb;text-align:center;'>"
                  . "<div style='min-width:50px;'>Share %</div>"
                  . "</td>":"")
                  . "</tr>";
            $ret .= "</tbody><thead>$trhd</thead>$rowpic<tbody>";
            $sql = "SELECT id,objective_no,section_objective_desc,kpi_text,target_text,measurement_unit,weight,"
                 . "pic_job_id,pic_employee_id,parent_objective_id,parent_kpi_id"
                 . " FROM sms_section_objective"
                 . " WHERE org_id = '$org_id'"
                 . " AND id_section_perspective = '$sms_perspective_id'"
                 . " AND id_section_session = '$psid'"
                 . " ORDER BY id";
            $ro = $db->query($sql);
            $cnt = $db->getRowsNum($ro);
            $so = "";
            $so_no = 0;
            if($cnt>0) {
               $subttlw = 0;
               $subttl_sms_share = array();
               while(list($sms_objective_id,$sms_objective_no,$sms_objective_text,$sms_kpi_text,$sms_target_text,$sms_measurement_unit,$sms_objective_weight,
                          $sms_pic_job_id,$sms_pic_employee_id,$sms_parent_objective_idx,$sms_parent_kpi_idx)=$db->fetchRow($ro)) {
                  
                  if(trim($sms_objective_text)=="") {
                     $sms_objective_text = _EMPTY;
                  }
                  
                  //$top_level_org_id = $this->recurseParentOrg($sms_objective_id);
                  
                  /// check if it is a local sub
                  $sql = "SELECT org_id FROM sms_section_objective WHERE id_section_session = '$psid' AND id = '$sms_parent_objective_idx'";
                  $rp = $db->query($sql);
                  if($db->getRowsNum($rp)>0) {
                     list($sms_parent_org_idx)=$db->fetchRow($rp);
                  }
                  
                  /// has local sub?
                  $sql = "SELECT id,org_id,weight FROM sms_section_objective WHERE id_section_session = '$psid' AND parent_objective_id = '$sms_objective_id' AND org_id = '$org_id'";
                  $rchild = $db->query($sql);
                  $has_local_sub = 0;
                  $ttl_sub_weight = 0;
                  if($db->getRowsNum($rchild)>0) {
                     while(list($sub_sms_objective_id,$sub_sms_org_id,$sub_weight)=$db->fetchRow($rchild)) {
                        $has_local_sub++;
                        $ttl_sub_weight = _bctrim(bcadd($ttl_sub_weight,$sub_weight));
                     }
                  }
                  
                  $sql = "SELECT a.job_nm,a.job_abbr FROM ".XOCP_PREFIX."jobs a WHERE a.job_id = '$sms_pic_job_id'";
                  $rj = $db->query($sql);
                  if($db->getRowsNum($rj)>0) {
                     list($so_pic_job_nm,$so_pic_job_abbr)=$db->fetchRow($rj);
                  } else {
                     $so_pic_job_nm = $so_pic_job_abbr = "";
                  }
                  $kpi_cnt = 0;
                  $sql = "SELECT sms_kpi_id,sms_kpi_text,sms_kpi_weight,sms_kpi_target_text,sms_kpi_measurement_unit,sms_kpi_pic_employee_id"
                       . " FROM sms_kpi WHERE id_section_session = '$psid' AND sms_objective_id = '$sms_objective_id'";
                  $rkpi = $db->query($sql);
                  $kpi_cnt = $db->getRowsNum($rkpi);
                  
                  if($kpi_cnt>0&&$has_local_sub==0) {
                     $ret .= "<tr>"
                           //. "<td rowspan='".($kpi_cnt+1)."' style='vertical-align:middle;".($sms_parent_objective_idx>0&&$top_level_org_id==0?"color:blue;":"color:black;")."text-align:left;border-right:1px solid #333;font-weight:bold;border-bottom:1px solid #333;'>${sms_objective_id} Test2</td>"
                           . "<td rowspan='".($kpi_cnt+1)."' style='vertical-align:top;border-right:1px solid #bbb;border-bottom:1px solid #333;'>"
                           . "<span onclick='edit_so(\"$sms_objective_id\",this,event);' class='xlnk'>".htmlentities($sms_objective_text)." </span></td>";
                           //. "<td rowspan = '".($kpi_cnt+1)."' colspan = '$picnum'".+(3)."></td>";
                           //. "<td rowspan='".($kpi_cnt+1)."' style='vertical-align:middle;border-right:1px solid #bbb;text-align:center;border-bottom:1px solid #333;'>".toMoney($sms_objective_weight)."</td>"
                           //. "<td rowspan='".($kpi_cnt+1)."' style='vertical-align:middle;border-right:1px solid #bbb;border-bottom:1px solid #333;'><div style='width:50px;overflow:hidden;'><div style='width:900px;'>$so_pic_job_abbr</div></div></td>";
                     $kpi_no = 0;
                     while(list($sms_kpi_id,$sms_kpi_text,$sms_kpi_weight,$sms_kpi_target_text,$sms_kpi_measurement_unit,$sms_kpi_pic_employee_id)=$db->fetchRow($rkpi)) {
                        if($kpi_no>0) {
                           //$ret .= "<tr><td colspan='3' style='border-right:1px solid #bbb;".(($kpi_no+1)==$kpi_cnt?"":"border-bottom:0;")."'>&nbsp;</td>";
                        }
                        $ret .= "<td style='border-right:1px solid #bbb;'><span class='xlnk' onclick='edit_kpi(\"$sms_objective_id\",\"$sms_kpi_id\",this,event);'>".htmlentities($sms_kpi_text)."</span></td>"
                              . "<td style='border-right:1px solid #bbb;'>".htmlentities($sms_kpi_measurement_unit)."</td>"
                              . "<td style='border-right:1px solid #bbb;'>".htmlentities($sms_kpi_target_text)."</td>";

                        /*$j = 0;
                        $tdpicres = "";
                        $empidx = explode(",", $empid);
                        $pic_employee_idex = explode(",", $sms_kpi_pic_employee_id);
   
                        while ($j < $picnum) {
                          $arsrch = array_search($empidx[$j], $pic_employee_idex);
                          if ($arsrch > -1) {
                            $picres = "Y";
                          }else{
                            $picres = "-";
                          }
                          $tdpicres .= "<td style='border-right:1px solid #bbb;'>$picres</td>";
                          $j++;
                        }*/

                        $action_plan_cnt = 0;
                        $sqls = "SELECT sms_action_plan_id,sms_action_plan_text,sms_action_plan_pic_employee_id FROM sms_action_plan WHERE sms_kpi_id = '$sms_kpi_id' AND sms_objective_id = '$sms_objective_id'";
                        $ractionplan = $db->query($sqls);
                        $action_plan_cnt = $db->getRowsNum($ractionplan);

                        $ret .= "<td style='border-right:1px solid #bbb;'><table>";

                        while (list($sms_action_plan_id,$sms_action_plan_text,$sms_action_plan_pic_employee_id)=$db->fetchRow($ractionplan)) {
                            /*$j = 0;
                            $k = 0;
                            $tdpicres = "";
                            $row_action_plan = "";
                            $empidx = explode(",", $empid);
                            $pic_employee_idex = explode(",", $sms_action_plan_pic_employee_id);
       
                            while ($j < $picnum) {
                              $arsrch = array_search($empidx[$j], $pic_employee_idex);
                              if ($arsrch > -1) {
                                $picres = "Y";
                              }else{
                                $picres = "-";
                              }
                              $tdpicres .= "<td style='border-right:1px solid #bbb;'>$picres</td>";
                              $j++;
                            }*/
                        
                            $ret .= "<tr style='height:100px;vertical-align:top;'><td><span class='xlnk' onclick='edit_actionplan(\"$sms_objective_id\",\"$sms_kpi_id\",\"$sms_action_plan_id\",this,event);'>".htmlentities($sms_action_plan_text)."</span></td></tr>";
                        }

                        $ret .= "</table></td>";

                        $sqls = "SELECT sms_action_plan_id,sms_action_plan_text,sms_action_plan_pic_employee_id FROM sms_action_plan WHERE sms_kpi_id = '$sms_kpi_id' AND sms_objective_id = '$sms_objective_id'";
                        $ractionplan = $db->query($sqls);
                        $action_plan_cnt = $db->getRowsNum($ractionplan);
                        
                        $ret .= "<td colspan='$picnum'><table style='width:100%;text-align:center;'>";

                        while (list($sms_action_plan_id,$sms_action_plan_text,$sms_action_plan_pic_employee_id)=$db->fetchRow($ractionplan)) {
                            $j = 0;
                            $k = 0;
                            $tdpicres = "";
                            $row_action_plan = "";
                            $empidx = explode(",", $empid);
                            $pic_employee_idex = explode(",", $sms_action_plan_pic_employee_id);
                            
                            $ret .= "<tr style='height:100px;vertical-align:top;'>";
                            while ($j < $picnum) {
                              $arsrch = array_search($empidx[$j], $pic_employee_idex);
                              if ($arsrch > -1) {
                                $picres = "&#10004;";
                              }else{
                                $picres = "-";
                              }
                              $tdpicres .= "<td style='border-right:0px solid #bbb;width:50px;'>$picres</td>";
                              $j++;
                            }
                            $ret .= $tdpicres;
                            $ret .= "</tr>";
                            
                        }

                        $ret .= "</tr></table></td>";

                        

                        if($share_cnt>0) {
                           foreach($share_arr as $vshare) {
                              list($sms_share_org_id,$sms_share_org_nm,$sms_share_org_abbr)=$vshare;
                              $sql = "SELECT sms_share_weight FROM sms_kpi_share"
                                   . " WHERE id_section_session = '$psid' AND id_section_objective = '$sms_objective_id'"
                                   . " AND sms_kpi_id = '$sms_kpi_id'"
                                   . " AND sms_org_id = '$org_id'"
                                   . " AND sms_share_org_id = '$sms_share_org_id'";
                              $rw = $db->query($sql);
                              if($db->getRowsNum($rw)>0) {
                                 list($sms_share_weight)=$db->fetchRow($rw);
                              } else {
                                 $sms_share_weight = 0;
                              }
                              if($sms_share_weight>0) {
                                 $sms_share_weight_txt = toMoney($sms_share_weight);
                                 $sms_share_weight_txt = "<span style='color:#3333ff;'>".toMoney($sms_share_weight)."</span>";
                              } else {
                                 $sms_share_weight_txt = "<span style='color:#333;'>-</span>";
                              }
                              $ret .= "<td style='border-left:1px solid #bbb;text-align:center;' class='tdlnk' onclick='edit_kpi_share(\"$sms_objective_id\",\"$sms_kpi_id\",\"$sms_share_org_id\",this,event);' >$sms_share_weight_txt</td>";
                              if(!isset($subttl_sms_share[$sms_share_org_id])) $subttl_sms_share[$sms_share_org_id] = 0; /// initialize
                              $subttl_sms_share[$sms_share_org_id] = bcadd($subttl_sms_share[$sms_share_org_id],$sms_share_weight);
                           }
                        } else {
                           /// has no sub shared
                           ///$ret .= "<td style='border-left:1px solid #bbb;text-align:center;'>&nbsp;</td>";
                           
                        }
                        
                        $ret .= "</tr>";
                        $kpi_no++;
                        
                     }

                     $separatornum = $picnum + 4;
                     
                     $ret .= "<tr>"
                           . "<td colspan='$separatornum' style='border-right:0px solid #bbb;padding:1px;border-bottom:1px solid #333;background-color:#fff;padding-left:3px;'>"
                           . "</td>";
                     
                     /// has no sub shared
                     if($has_no_sub_shared==0) {
                        $ret .= "<td style='border-left:1px solid #bbb;text-align:center;border-bottom:1px solid #333;background-color:#fff;' colspan='".($share_cnt==0?0:$share_cnt)."'></td>";
                     }
                     
                     $ret .= "</tr>";
                     
                     
                  } else {
                     $inherited_kpi = "";
                     $sql = "SELECT sms_kpi_text,sms_kpi_id,sms_kpi_target_text,sms_kpi_measurement_unit FROM sms_kpi WHERE id_section_session = '$psid' AND sms_objective_id = '$sms_objective_id'";
                     $rxkpi = $db->query($sql);
                     if($db->getRowsNum($rxkpi)>0) {
                        while(list($sms_kpi_textxxx,$sms_kpi_idxxx,$sms_kpi_target_textxxx,$sms_kpi_measurement_unitxxx)=$db->fetchRow($rxkpi)) {
                           $inherited_kpi .= "<div style='padding-left:20px;color:#777;'>$sms_kpi_textxxx : $sms_kpi_target_textxxx ($sms_kpi_measurement_unitxxx)</div>";
                        }
                     }
                     
                     $ret .= "<tr>"
                           //. "<td ".($has_local_sub>0?"":"")." style='vertical-align:middle;text-align:left;border-right:1px solid #333;".($sms_parent_objective_idx>0&&$top_level_org_id==0?"color:blue;":"color:black;")."font-weight:bold;border-bottom:1px solid #333;'>${sms_objective_id} test1</td>"
                           . "<td ".($has_local_sub>0?"colspan='".(6+($share_cnt==0?0:$share_cnt))."'":"")." ".($has_local_sub>0?"":"")." style='vertical-align:middle;border-right:1px solid #bbb;border-bottom:1px solid #333;'>"
                           . "<span onclick='edit_so(\"$sms_objective_id\",this,event);' class='xlnk'>".htmlentities($sms_objective_text)."</span> [ <span class='ylnk' onclick='edit_kpi(\"$sms_objective_id\",\"new\",this,event);'>Add KPI</span> ]"
                           //. ($has_local_sub>0?" ( ".toMoney($ttl_sub_weight)." % / ".toMoney($sms_objective_weight)." % )":"")
                           //. ($has_local_sub>0?" [ <span class='ylnk' onclick='add_sub(\"$sms_objective_id\",this,event);'>Add Initiative</span> ]":"")
                           . $inherited_kpi
                           . "</td>";
                     
                     if($has_local_sub==0) {      
                        //$ret .= "<td rowspan='2' style='vertical-align:middle;border-right:1px solid #bbb;text-align:center;border-bottom:1px solid #333;'>".toMoney($sms_objective_weight)."</td>";
                        //$ret .= "<td rowspan='2' style='vertical-align:middle;border-right:1px solid #bbb;border-bottom:1px solid #333;'><div style='width:50px;overflow:hidden;'><div style='width:900px;'>$so_pic_job_abbr</div></div></td>";
                        $ret .= "<td colspan='$picnum".+(3)."' style='border-right:0px solid #333;border-bottom:1px solid #333;text-align:center;font-style:italic;color:#aaa;'>"._EMPTY."</td>";
                        //$ret .= "<td style='border-left:1px solid #bbb;text-align:center;border-bottom:1px solid #bbb;' colspan='".($share_cnt==0?0:$share_cnt)."'></td>";
                     }
                     $ret .= "</tr>";
                     
                     if($has_local_sub==0) {
                        /*$ret .= "<tr>";
                        $ret .= "<td style='border-left:1px solid #bbb;text-align:center;border-bottom:1px solid #333;background-color:#fff;' colspan='".($share_cnt==0?0:$share_cnt)."'></td>";
                        $ret .= "</tr>";*/
                     }
                     
                     
                     
                     
                  }
                  $so_no++;
                  
                  $do_count = 0;
                  if($sms_parent_objective_idx==0) {
                     $do_count++;
                  } else {
                     $sql = "SELECT org_id FROM sms_section_objective WHERE id_section_session = '$psid' AND parent_objective_id = '$sms_objective_id'";
                     $rpx = $db->query($sql);
                     if($sms_objective_id==936) {
                        _debuglog($sql);
                     }
                     if($db->getRowsNum($rpx)>0) {
                        list($sms_parent_org_id)=$db->fetchRow($rpx);
                        if($sms_parent_org_id==$org_id) {
                           //$do_count++;
                        } else {
                           $do_count++;
                        }
                     } else {
                        _debuglog($sql);
                        $do_count++;
                     }
                  }
                  if($has_local_sub==0&&$do_count>0) {
                     $subttlw = _bctrim(bcadd($subttlw,$sms_objective_weight));
                     $ttlw = _bctrim(bcadd($ttlw,$sms_objective_weight));
                  }
               }
               /*$ret .= "<tr>"
                     . "<td colspan='2' style='border-right:1px solid #bbb;text-align:center;border-bottom:3px solid #333;'>Subtotal</td>"
                     . "<td style='text-align:center;background-color:#eeffff;font-weight:bold;color:black;border-right:1px solid #bbb;border-bottom:3px solid #333;'>".toMoney($subttlw)."</td>"
                     . "<td colspan='".(4)."' style='border-right:0px solid #bbb;border-bottom:3px solid #333;'></td>";
               */
               if(count($share_arr)>0) {
                  foreach($share_arr as $vshare) {
                     list($sms_share_org_id,$sms_share_org_nm,$sms_share_org_abbr)=$vshare;
                     
                     if(isset($subttl_sms_share[$sms_share_org_id])&&$subttl_sms_share[$sms_share_org_id]>0) {
                        $subttlkpishare = toMoney($subttl_sms_share[$sms_share_org_id]);
                     } else {
                        $subttlkpishare = "-";
                     }
                     
                     $ret .= "<td id='tdsubttlkpishare_${sms_perspective_id}_${sms_share_org_id}' style='text-align:center;background-color:#eeffff;font-weight:bold;color:black;border-left:1px solid #bbb;border-bottom:3px solid #333;'>$subttlkpishare</td>";
                     $ttl_sms_share[$sms_share_org_id] = bcadd($ttl_sms_share[$sms_share_org_id],$subttl_sms_share[$sms_share_org_id]);
                  }
               } else {
                  //$ret .= "<td style='text-align:center;background-color:#eeffff;font-weight:bold;color:black;border-left:1px solid #bbb;border-bottom:3px solid #333;'>-</td>";
               }
               
               $ret .= "</tr>";
            
            
            } else {
               $ret .= "<tr><td colspan='".(5+$picnum+($share_cnt==0?0:$share_cnt))."' style='text-align:center;font-style:italic;border-bottom:3px solid #333;'>"._EMPTY."</td></tr>";
            }
         //} // end while
      //} // end if
      
      $ret .= "<tr><td style='border:0px;border-bottom:1px solid #bbb;background-color:#fff;' colspan='".(5+$picnum)."'>&nbsp;</td></tr>";
      $total_shared = 0;
      $retshare = "";
      if(count($share_arr)>0) {
         $tdtotal = "";
         foreach($share_arr as $vshare) {
            list($sms_share_org_id,$sms_share_org_nm,$sms_share_org_abbr)=$vshare;
            $total_shared = _bctrim(bcadd($total_shared,$ttl_sms_share[$sms_share_org_id]));
            $tdtotal .= "<td id='tdttlkpishare_${sms_share_org_id}' style='text-align:center;background-color:#bbffdd;font-weight:bold;color:black;padding:10px;border:1px solid #bbb;border-right:0;border-top:0;'>".toMoney(_bctrim($ttl_sms_share[$sms_share_org_id]))."</td>";
         }
         $retshare .= "<td id='tdttlshared' style='text-align:center;background-color:#bbffdd;font-weight:bold;color:black;padding:10px;border:1px solid #bbb;border-right:0;border-top:0;'>".toMoney($total_shared)."</td>$tdtotal";
      } else {
         
            $retshare .= "<td>&nbsp;</td>";
            
            /*
            $retshare .= "<td style='text-align:center;background-color:#eeffff;font-weight:bold;color:black;border-left:1px solid #bbb;padding:10px;'>-</td>";
            */
            
      }
      
      /*$ret .= "<tr>"
            . "<td colspan='2' style='background-color:#fff;padding:10px;text-align:center;font-weight:bold;border-right:1px solid #bbb;'>Total</td>"
            . "<td style='text-align:center;background-color:#bbffdd;font-weight:bold;color:black;padding:10px;border:1px solid #bbb;border-left:0;border-top:0;'>".toMoney($ttlw)."</td>"
            . "<td id='tdbalancewarning' colspan='".(3)."' style='background-color:#fff;padding:10px;'>";*/
      
      if($has_no_sub_shared==1) {
         $ret .= "&nbsp;";
      } else {
         switch(bccomp(number_format($ttlw,4,".",""),number_format($total_shared,4,".",""))) {
            case 1:
               $ret .= "<span style='color:red;'>Total objective weight is more than total shared.</span>";
               break;
            case -1:
               $ret .= "<span style='color:red;'>Total objective weight is less than total shared.</span>";
               break;
            default:
               $ret .= "&nbsp;";
               break;
         }
      }
      
      $ret .= "</td>";
      
      $ret .= $retshare;
      
      $ret .= "</tr>";
      
      $ret .= "</tbody>"
            /*. "<tfoot>"
            . "<tr><td colspan='5'>&nbsp;"
            . "</td>"
            . "<td colspan='".($picnum+($share_cnt==0?0:$share_cnt))."' style='text-align:right;'>"
            ///. "<input type='button' value='Recalculate Weight' onclick='recalculate_weight(this,event);'/>&#160;"
            //. "<input type='button' value='Import Objectives' onclick='import_objectives(this,event);'/>&#160;"
            . ($has_no_sub_shared==1?"":"<input type='button' value='Deploy Objectives' class='xaction' onclick='deploy_objectives(this,event);'/>")
            . "</td></tr>"
            . "</tfoot>"*/
            . "</table>";

     // APPROVAL

      $section_manager_id = 0;
      $division_manager_id = 0;

       $sql = "SELECT a.employee_id,a.person_id,"
       . "b.person_nm,d.job_class_id,d.job_id "
       . " FROM ".XOCP_PREFIX."employee a"
       . " LEFT JOIN ".XOCP_PREFIX."persons b USING(person_id)"
       . " LEFT JOIN ".XOCP_PREFIX."employee_job c USING(employee_id)"
       . " LEFT JOIN ".XOCP_PREFIX."jobs d ON d.job_id = c.job_id"
       . " LEFT JOIN ".XOCP_PREFIX."orgs e USING(org_id)"
       . " LEFT JOIN ".XOCP_PREFIX."org_class f USING(org_class_id)"
       . " WHERE a.status_cd = 'normal'"
       . " ORDER BY b.person_nm";
      $result = $db->query($sql);
      if ($db->getRowsNum($result)>0) {
        while (list($employee_idx,$person_idx,$person_nmx,$job_class_idx,$job_idx)=$db->fetchRow($result)) {
         if ($job_class_idx == 3 AND $person_idx == $person_id) {
           $section_manager_nm = $person_nmx;
           $section_manager_id = $employee_idx;
           $section_job_id = $job_idx;
         }elseif ($job_class_idx == 2 AND $person_idx == $person_id) {
           $division_manager_nm = $person_nmx;
           $division_manager_id = $employee_idx;
         }elseif ($job_class_idx == 1 AND $person_idx == $person_id) {
           $division_manager_nm = $person_nmx;
           $division_manager_id = $employee_idx;
         }elseif ($job_idx == 133 AND $person_idx == $person_id) {
           $section_manager_nm = $person_nmx;
           $section_manager_id = $employee_idx;
           $section_job_id = $job_idx;
         }
       }
      }

     $sqlapp = "SELECT section_submit_id,section_submit_date,section_submit,section_approval_id,section_approval_date,section_approval,status_return,date_return,remark FROM sms_approval WHERE id_section_session = '$psid' AND org_id = '$org_id'";
     $resultapp = $db->query($sqlapp); 
     list($section_submit_id,$section_submit_date,$section_submit,$section_approval_id,$section_approval_date,$section_approval,$status_return,$date_return,$remark)=$db->fetchRow($resultapp);

     $section_submit_date = date('d M Y',strtotime($section_submit_date));
     $section_approval_date = date('d M Y',strtotime($section_approval_date));

     if ($section_submit == 0) {
        $sm_button = "<input id='btn_propose' onclick='save_propose(\"$section_manager_id\",\"$section_manager_nm\",this,event);' type='button' value='Propose'/>&nbsp";
        $sm_prop = "";
     }else{
        $sqlprop = "SELECT b.person_nm,d.job_nm,e.org_id,f.org_class_id "
         . " FROM ".XOCP_PREFIX."employee a"
         . " LEFT JOIN ".XOCP_PREFIX."persons b USING(person_id)"
         . " LEFT JOIN ".XOCP_PREFIX."employee_job c USING(employee_id)"
         . " LEFT JOIN ".XOCP_PREFIX."jobs d ON d.job_id = c.job_id"
         . " LEFT JOIN ".XOCP_PREFIX."orgs e USING(org_id)"
         . " LEFT JOIN ".XOCP_PREFIX."org_class f USING(org_class_id)"
         . " WHERE a.employee_id = '$section_submit_id'";
        $resultprop = $db->query($sqlprop);
        list($person_nmy,$job_nmy)=$db->fetchRow($resultprop);
        $sm_button = "";
        $sm_prop = "<table style='width:100%'>"
                . "<tr style='font-weight:bold;'><td>$person_nmy</td></tr>"
                . "<tr><td>$job_nmy</td></tr>"
                . "<tr><td>Submitted on : $section_submit_date</td></tr>"
                . "</table>";
     }

      // RETURN
     /* $sql = "SELECT section_submit,section_approval,status_return,date_return FROM sms_approval WHERE org_id = '$org_id' AND id_section_session = '$psid'";
      $result = $db->query($sql);
      list($section_submit,$section_approval) = $db->fetchRow($result);*/

     if ($section_submit == 0 AND $section_approval == 0) {
        $dm_button = "";
        $dm_app = "";
     }elseif ($section_submit == 1 AND $section_approval == 0) {
       $dm_button = "<input id='btn_approve' onclick='save_approval(\"$division_manager_id\",\"$division_manager_nm\",this,event);' type='button' value='Approve'/>&nbsp";
        $dm_app = "";
        if (($section_submit == 1 OR $division_manager_id == 0) AND $section_approval == 0) {
        $returnbtn = "<span style='margin-left:10px;'><input onclick='return_session(\"$psid\",this,event);' type='button' value='Not Approved' id='returnbtn' /></span>";
      }else{
        $returnbtn = "";
      }
     }
     else{
        $sqlapp = "SELECT b.person_nm,d.job_nm,e.org_id,f.org_class_id "
         . " FROM ".XOCP_PREFIX."employee a"
         . " LEFT JOIN ".XOCP_PREFIX."persons b USING(person_id)"
         . " LEFT JOIN ".XOCP_PREFIX."employee_job c USING(employee_id)"
         . " LEFT JOIN ".XOCP_PREFIX."jobs d ON d.job_id = c.job_id"
         . " LEFT JOIN ".XOCP_PREFIX."orgs e USING(org_id)"
         . " LEFT JOIN ".XOCP_PREFIX."org_class f USING(org_class_id)"
         . " WHERE a.employee_id = '$section_approval_id'";
        $resultapp = $db->query($sqlapp);
        list($person_nmz,$job_nmz)=$db->fetchRow($resultapp);
        $dm_button = "";
        $dm_prop = "<table style='width:100%'>"
        . "<tr style='font-weight:bold;'><td>$person_nmz</td></tr>"
        . "<tr><td>$job_nmz</td></tr>"
        . "<tr><td>Approved on : $section_approval_date</td></tr>"
        . "</table>";
     }

     if ($status_return == 1) {
        $date_return_display = "<table style='width:100%'>"
                . "<tr style='font-weight:bold;'><td></td></tr>"
                . "<tr><td></td></tr>"
                . "<tr><td>Not Approved on : $date_return</td></tr>"
                . "</table>";
     }

     if ($remark != "") {
      $note = "<div style='margin-top:10px;'>Note : $remark</div>";
     }
      $ret .= $note 
           ."<div style='margin-top: 40px;'>"
           . "<table border='1px' cellpadding='2' cellspacing='0' style=' width:500px;margin-left:auto;margin-right:auto;'>"
           . "<colgroup>"
           . "<col width='200' />"
           . "<col width='200' />"
           . "</colgroup>"
           . "<tbody>"
           . "<tr style='height:50px;background-color:#DDDDDD;'><th>Proposed By</th><th>Approved By</th></tr>"
           . "<tr style='height:70px;text-align:center;'>"
           . "<td>"
           . $sm_prop
           . "<div id='frmpropose'><input type='hidden' name='employee_id' value='$section_manager_id'><input type='hidden' name='employee_nm' value='$section_manager_nm'></div>"
           . $date_return_display
           . "</td>"
           . "<td>"
           . $dm_prop
           . "<div id='frmapproval' ><input type='hidden' name='employee_id' value='$division_manager_id'><input type='hidden' name='employee_nm' value='$division_manager_nm'></div>"
           . "</td></tr>"
           . "</tbody>"
           . "</table>"
           . "</div>"
           . "<div style='padding:2px;font-size:12px;color: #666666; text-align:left;'><div id='id_return'></div></div>";


    echo $ret;

?>