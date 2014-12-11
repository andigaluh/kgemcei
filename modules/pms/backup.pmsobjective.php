<?php
//--------------------------------------------------------------------//
// Filename : modules/pms/pmsperspective.php                          //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-07-18                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('PMS_OBJECTIVE_DEFINED') ) {
   define('PMS_OBJECTIVE_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

include_once(XOCP_DOC_ROOT."/modules/pms/pmsxocp.php");
include_once(XOCP_DOC_ROOT."/modules/pms/class/ajax_objective.php");

class _pms_Objective extends XocpBlock {
   var $catchvar = _PMS_CATCH_VAR;
   var $blockID = _PMS_OBJECTIVE_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _PMS_OBJECTIVE_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _pms_Objective($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */

   }
   
   function pmsobjective() {
      $db=&Database::getInstance();
      $ajax = new _pms_class_ObjectiveAjax("orgjx");
      if(!isset($_SESSION["pms_org_id"])) {
         $_SESSION["pms_org_id"] = 1;
      }
      
      $org_id = $_SESSION["pms_org_id"];
      
      $sql = "SELECT a.pms_share_org_id,b.org_abbr,b.org_nm"
           . " FROM pms_org_share a"
           . " LEFT JOIN ".XOCP_PREFIX."orgs b ON b.org_id = a.pms_share_org_id"
           . " WHERE a.pms_org_id = '$org_id'"
           . " ORDER BY b.order_no";
      $result = $db->query($sql);
      $tdshare = "";
      $share_arr = array();
      $share_cnt = 0;
      $colgroup = "";
      if($db->getRowsNum($result)>0) {
         $share_cnt = $db->getRowsNum($result);
         $sharehead = "";
         while(list($pms_share_org_id,$pms_share_org_abbr,$pms_share_org_nm)=$db->fetchRow($result)) {
            $tdshare .= "<td style='border-bottom:1px solid #333;border-left:1px solid #bbb;text-align:center;'><span class='xlnk' onclick='view_share(\"$pms_share_org_id\",this,event);'>$pms_share_org_abbr</span></td>";
            $share_arr[] = array($pms_share_org_id,$pms_share_org_nm,$pms_share_org_abbr);
            $colgroup .= "<col width='50'/>";
         }
      } else {
         $tdshare .= "<td style='border-bottom:1px solid #333;border-left:1px solid #bbb;text-align:center;'>-</td>";
         $sharehead = "";
         $colgroup .= "<col width='50'/>";
      }
      
      $sql = "SELECT a.org_abbr,a.org_nm,b.org_class_nm FROM ".XOCP_PREFIX."orgs a LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . " WHERE org_id = '".$_SESSION["pms_org_id"]."'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($org_abbr,$org_nm,$org_class_nm)=$db->fetchRow($result);
         $orgsel = "<div style='padding:5px;border:1px solid #bbb;background-color:#ddd;'><span id='orgspan' class='xlnk' onclick='select_org(this,event);'>Level Organization : <span style='font-weight:bold;'>$org_nm $org_class_nm</span></span></div>";
      }
      
      reset($share_arr);
      
      $ret = "<table class='yylist' style='width:100%;'>"
           . "<colgroup>"
           . "<col width='30'/>"
           . "<col width='150'/>"
           . "<col width='70'/>"
           . "<col width='50'/>"
           . "<col width='150'/>"
           . "<col width='100'/>"
           . "<col width='*'/>"
           . $colgroup
           . "</colgroup>"
           . "<thead>"
           . $sharehead;
           
      $trhd = "<tr>"
           . "<td style='border-bottom:1px solid #333;text-align:center;border-right:1px solid #bbb;'>ID</td>"
           . "<td style='border-bottom:1px solid #333;border-right:1px solid #bbb;'>Strategic Objective</td>"
           . "<td style='border-bottom:1px solid #333;border-right:1px solid #bbb;text-align:center;'>Weight (%)</td>"
           . "<td style='border-bottom:1px solid #333;border-right:1px solid #bbb;'>PIC</td>"
           . "<td style='border-bottom:1px solid #333;border-right:1px solid #bbb;'>KPI</td>"
           . "<td style='border-bottom:1px solid #333;border-right:1px solid #bbb;'>Unit</td>"
           . "<td style='border-bottom:1px solid #333;border-right:0px solid #bbb;'>Target</td>"
           . $tdshare
           . "</tr>";
      
      //$ret .= $trhd;
      
      $ret .= "</thead>"
           . "<tbody>";
      
      $sql = "SELECT pms_perspective_code,pms_perspective_id,pms_perspective_name FROM pms_perspective ORDER BY pms_perspective_id";
      $result = $db->query($sql);
      $ttlw = 0;
      $ttl_pms_share = array();
      $job_nm = $job_abbr = "";
      if($db->getRowsNum($result)>0) {
         while(list($pms_perspective_code,$pms_perspective_id,$pms_perspective_name)=$db->fetchRow($result)) {
            $ret .= "<tr><td style='border:0px;border-bottom:3px solid #333;background-color:#fff;' colspan='".(7+($share_cnt==0?1:$share_cnt))."'>&nbsp;</td></tr>"
                  . "<tr><td colspan='".(7)."' style='font-weight:bold;border-bottom:1px solid #333;color:black;background-color:#ddf;padding:10px;'>"
                  . "$pms_perspective_name Perspective"
                  . "&nbsp;&nbsp;<input type='button' value='Add Objective' onclick='edit_so(\"new_${pms_perspective_id}\",this,event);'/>&nbsp;"
                  . "</td>"
                  . "<td colspan='".($share_cnt==0?1:$share_cnt)."' style='border-bottom:1px solid #333;background-color:#ddf;padding:10px;border-left:1px solid #bbb;text-align:center;'>"
                  . "<div style='min-width:50px;'><span class='xlnk' onclick='add_share(this,event);'>Share</span> %</div>"
                  . "</td>"
                  . "</tr>";
            $ret .= "</tbody><thead>$trhd</thead><tbody>";
            $sql = "SELECT pms_objective_id,pms_objective_no,pms_objective_text,pms_kpi_text,pms_target_text,pms_measurement_unit,pms_objective_weight,"
                 . "pms_pic_job_id,pms_pic_employee_id"
                 . " FROM pms_objective"
                 . " WHERE pms_org_id = '$org_id'"
                 . " AND pms_perspective_id = '$pms_perspective_id'"
                 . " ORDER BY pms_objective_no";
            $ro = $db->query($sql);
            $cnt = $db->getRowsNum($ro);
            $so = "";
            $so_no = 0;
            if($cnt>0) {
               $subttlw = 0;
               $subttl_pms_share = array();
               while(list($pms_objective_id,$pms_objective_no,$pms_objective_text,$pms_kpi_text,$pms_target_text,$pms_measurement_unit,$pms_objective_weight,
                          $pms_pic_job_id,$pms_pic_employee_id)=$db->fetchRow($ro)) {
                  $sql = "SELECT a.job_nm,a.job_abbr FROM ".XOCP_PREFIX."jobs a WHERE a.job_id = '$pms_pic_job_id'";
                  $rj = $db->query($sql);
                  if($db->getRowsNum($rj)>0) {
                     list($so_pic_job_nm,$so_pic_job_abbr)=$db->fetchRow($rj);
                  } else {
                     $so_pic_job_nm = $so_pic_job_abbr = "";
                  }
                  $kpi_cnt = 0;
                  $sql = "SELECT pms_kpi_id,pms_kpi_text,pms_kpi_weight,pms_kpi_target_text,pms_kpi_measurement_unit"
                       . " FROM pms_kpi WHERE pms_objective_id = '$pms_objective_id'";
                  $rkpi = $db->query($sql);
                  $kpi_cnt = $db->getRowsNum($rkpi);
                  if($kpi_cnt>0) {
                     $ret .= "<tr>"
                           . "<td style='vertical-align:middle;text-align:center;border-right:1px solid #333;background-color:#eeeeff;font-weight:bold;color:black;border-bottom:1px solid #333;' rowspan='".($kpi_cnt+1)."'>${pms_perspective_code}${pms_objective_no}</td>"
                           . "<td style='border-right:1px solid #bbb;'>"
                           . "<span onclick='edit_so(\"$pms_objective_id\",this,event);' class='xlnk'>".htmlentities($pms_objective_text)."</span></td>"
                           . "<td style='border-right:1px solid #bbb;text-align:center;'>$pms_objective_weight%</td>"
                           . "<td style='border-right:1px solid #bbb;'><div style='width:50px;overflow:hidden;'><div style='width:900px;'>$so_pic_job_abbr</div></div></td>";
                     $kpi_no = 0;
                     while(list($pms_kpi_id,$pms_kpi_text,$pms_kpi_weight,$pms_kpi_target_text,$pms_kpi_measurement_unit)=$db->fetchRow($rkpi)) {
                        if($kpi_no>0) {
                           $ret .= "<tr><td colspan='3' style='border-right:1px solid #bbb;".(($kpi_no+1)==$kpi_cnt?"":"border-bottom:0;")."'>&nbsp;</td>";
                        }
                        $ret .= "<td style='border-right:1px solid #bbb;'><span class='xlnk' onclick='edit_kpi(\"$pms_objective_id\",\"$pms_kpi_id\",this,event);'>".htmlentities($pms_kpi_text)."</span></td>"
                              . "<td style='border-right:1px solid #bbb;'>".htmlentities($pms_kpi_measurement_unit)."</td>"
                              . "<td style='border-right:0px solid #bbb;'>".htmlentities($pms_kpi_target_text)."</td>";
                     
                        if($share_cnt>0) {
                           foreach($share_arr as $vshare) {
                              list($pms_share_org_id,$pms_share_org_nm,$pms_share_org_abbr)=$vshare;
                              $sql = "SELECT pms_share_weight FROM pms_kpi_share"
                                   . " WHERE pms_objective_id = '$pms_objective_id'"
                                   . " AND pms_kpi_id = '$pms_kpi_id'"
                                   . " AND pms_org_id = '$org_id'"
                                   . " AND pms_share_org_id = '$pms_share_org_id'";
                              $rw = $db->query($sql);
                              if($db->getRowsNum($rw)>0) {
                                 list($pms_share_weight)=$db->fetchRow($rw);
                              } else {
                                 $pms_share_weight = 0;
                              }
                              $ret .= "<td style='border-left:1px solid #bbb;text-align:center;'><span onclick='edit_kpi_share(\"$pms_objective_id\",\"$pms_kpi_id\",\"$pms_share_org_id\",this,event);' class='xlnk'>$pms_share_weight%</span></td>";
                              if(!isset($subttl_pms_share[$pms_share_org_id])) $subttl_pms_share[$pms_share_org_id] = 0; /// initialize
                              $subttl_pms_share[$pms_share_org_id] = bcadd($subttl_pms_share[$pms_share_org_id],$pms_share_weight);
                           }
                        } else {
                           $ret .= "<td style='border-left:1px solid #bbb;text-align:center;'>&nbsp;</td>";
                        
                        }
                        
                        $ret .= "</tr>";
                        $kpi_no++;
                        
                     }
                     
                     $ret .= "<tr><td style='border-right:1px solid #bbb;border-bottom:1px solid #333;background-color:#fff;' colspan='3'></td>"
                           . "<td colspan='".(3)."' style='border-right:0px solid #bbb;padding:1px;border-bottom:1px solid #333;background-color:#fff;padding-left:3px;'>"
                           . "<input type='button' class='ssbtn' style='padding-left:10px;padding-right:10px;' onclick='edit_kpi(\"$pms_objective_id\",\"new\",this,event);' value='Add KPI'/>"
                           . "</td>";
                     $ret .= "<td style='border-left:1px solid #bbb;text-align:center;border-bottom:1px solid #333;background-color:#fff;' colspan='".($share_cnt==0?1:$share_cnt)."'></td>";
                     $ret .= "</tr>";
                     
                     
                  } else {
                     $ret .= "<tr>"
                           . "<td style='vertical-align:middle;text-align:center;border-right:1px solid #333;color:black;font-weight:bold;border-bottom:1px solid #333;' rowspan='2'>${pms_perspective_code}${pms_objective_no}</td>"
                           . "<td style='border-right:1px solid #bbb;border-bottom:1px solid #bbb;'>"
                           . "<span onclick='edit_so(\"$pms_objective_id\",this,event);' class='xlnk'>".htmlentities($pms_objective_text)."</span></td>"
                           . "<td style='border-right:1px solid #bbb;text-align:center;border-bottom:1px solid #bbb;'>$pms_objective_weight%</td>"
                           . "<td style='border-right:1px solid #bbb;border-bottom:1px solid #bbb;'><div style='width:50px;overflow:hidden;'><div style='width:900px;'>$so_pic_job_abbr</div></div></td>";
                     $ret .= "<td colspan='".(3)."' style='border-right:0px solid #bbb;border-bottom:1px solid #bbb;font-style:italic;'>"._EMPTY."</td>";
                     $ret .= "<td style='border-left:1px solid #bbb;text-align:center;border-bottom:1px solid #bbb;' colspan='".($share_cnt==0?1:$share_cnt)."'></td>";
                     $ret .= "</tr>";
                     
                     $ret .= "<tr><td style='border-right:1px solid #bbb;border-bottom:1px solid #333;background-color:#fff;' colspan='3'></td>"
                           . "<td colspan='".(3)."' style='border-right:0px solid #bbb;padding:1px;border-bottom:1px solid #333;background-color:#fff;padding-left:3px;'>"
                           . "<input type='button' class='ssbtn' style='padding-left:10px;padding-right:10px;' onclick='edit_kpi(\"$pms_objective_id\",\"new\",this,event);' value='Add KPI'/>"
                           . "</td>";
                     $ret .= "<td style='border-left:1px solid #bbb;text-align:center;border-bottom:1px solid #333;background-color:#fff;' colspan='".($share_cnt==0?1:$share_cnt)."'></td>";
                     $ret .= "</tr>";
                     
                     
                     
                  }
                  $so_no++;
                  $subttlw = _bctrim(bcadd($subttlw,$pms_objective_weight));
                  $ttlw = _bctrim(bcadd($ttlw,$pms_objective_weight));
               }
               $ret .= "<tr>"
                     . "<td colspan='2' style='border-right:1px solid #bbb;text-align:center;border-bottom:3px solid #333;'>Subtotal</td>"
                     . "<td style='text-align:center;background-color:#eeffff;font-weight:bold;color:black;border-right:1px solid #bbb;border-bottom:3px solid #333;'>$subttlw%</td>"
                     . "<td colspan='".(4)."' style='border-right:0px solid #bbb;border-bottom:3px solid #333;'></td>";
               
               if(count($share_arr)>0) {
                  foreach($share_arr as $vshare) {
                     list($pms_share_org_id,$pms_share_org_nm,$pms_share_org_abbr)=$vshare;
                     $ret .= "<td style='text-align:center;background-color:#eeffff;font-weight:bold;color:black;border-left:1px solid #bbb;border-bottom:3px solid #333;'>"._bctrim($subttl_pms_share[$pms_share_org_id])."%</td>";
                     $ttl_pms_share[$pms_share_org_id] = bcadd($ttl_pms_share[$pms_share_org_id],$subttl_pms_share[$pms_share_org_id]);
                  }
               } else {
                  $ret .= "<td style='text-align:center;background-color:#eeffff;font-weight:bold;color:black;border-left:1px solid #bbb;border-bottom:3px solid #333;'>-</td>";
               }
               
               $ret .= "</tr>";
            
            
            } else {
               $ret .= "<tr><td colspan='".(7+($share_cnt==0?1:$share_cnt))."' style='text-align:center;font-style:italic;border-bottom:3px solid #333;'>"._EMPTY."</td></tr>";
            }
         }
      }
      
      $ret .= "<tr><td style='border:0px;border-bottom:1px solid #bbb;background-color:#fff;' colspan='".(7+($share_cnt==0?1:$share_cnt))."'>&nbsp;</td></tr>";
      $ret .= "<tr>"
            . "<td colspan='2' style='background-color:#fff;padding:10px;text-align:center;font-weight:bold;border-right:1px solid #bbb;'>Total</td>"
            . "<td style='text-align:center;background-color:#bbffdd;font-weight:bold;color:black;padding:10px;border:1px solid #bbb;border-left:0;border-top:0;'>$ttlw%</td>"
            . "<td colspan='".(4)."' style='background-color:#fff;padding:10px;'>&nbsp;</td>";
      if(count($share_arr)>0) {
         foreach($share_arr as $vshare) {
            list($pms_share_org_id,$pms_share_org_nm,$pms_share_org_abbr)=$vshare;
            $ret .= "<td style='text-align:center;background-color:#bbffdd;font-weight:bold;color:black;padding:10px;border:1px solid #bbb;border-right:0;border-top:0;'>"._bctrim($ttl_pms_share[$pms_share_org_id])."%</td>";
         }
      } else {
         $ret .= "<td style='text-align:center;background-color:#eeffff;font-weight:bold;color:black;border-left:1px solid #bbb;padding:10px;'>-</td>";
      }
      
      $ret .= "</tr>";
      
      $ret .= "</tbody>"
            . "<tfoot>"
            . "<tr><td colspan='4'>&nbsp;"
            //. "<input type='button' value='Add Objective' onclick='edit_so(\"new\",this,event);'/>&nbsp;"
            . "</td>"
            . "<td colspan='".(3+($share_cnt==0?1:$share_cnt))."' style='text-align:right;'>"
            //. "<input type='button' value='Deploy Objectives' class='xaction'/>"
            . "</td></tr>"
            . "</tfoot>"
            . "</table>";
      
      $ret .= "<div style='padding:100px;'>&nbsp;</div>";
      
      $js = $ajax->getJs()
          . "<script type='text/javascript' src='".XOCP_SERVER_SUBDIR."/include/calendar.js'></script>"
          . "<script type='text/javascript'>//<![CDATA[
      
      function add_initiative(pms_objective_id,d,e) {
         orgjx_app_editInitiative(pms_objective_id,function(_data) {
            $('innereditsoedit').innerHTML = _data;
         });
      }
      
      function set_so_origin(pms_objective_id,d,e) {
         orgjx_app_setSOOrigin(pms_objective_id,function(_data) {
            $('parent_so').innerHTML = _data;
            $('so_editor').style.display = '';
            $('origin_chooser').style.display = 'none';
            $('vbtn').style.display = '';
         });
      }
      
      function change_so_origin(d,e) {
         $('so_editor').style.display = 'none';
         $('vbtn').style.display = 'none';
         $('origin_chooser').style.display = '';
      }
      
      function cancel_change_origin(d,e) {
         $('so_editor').style.display = '';
         $('origin_chooser').style.display = 'none';
         $('vbtn').style.display = '';
      }
      
      function kp_kpi_share_old(pms_objective_id,pms_kpi_id,pms_share_org_id,d,e) {
         var k = getkeyc(e);
         if(k==13) {
            save_kpi_share(pms_objective_id,pms_kpi_id,pms_share_org_id,d,e);
         }
      }
      
      function save_kpi_share_old(pms_objective_id,pms_kpi_id,pms_share_org_id,d,e) {
         var ret = _parseForm('frmkpi');
         orgjx_app_saveKPIShare(pms_objective_id,pms_kpi_id,pms_share_org_id,ret,function(_data) {
            location.reload(true);
         });
      }
      
      
      function save_kpi_share(val,pms_objective_id,pms_kpi_id,pms_share_org_id) {
         if(dveditshare) {
            dveditshare.d.innerHTML = val+'%';
         }
         orgjx_app_saveKPIShare(pms_objective_id,pms_kpi_id,pms_share_org_id,urlencode('pms_share_weight^^'+val),null);
      }
      
      function kp_kpi_share(d,e) {
         var k = getkeyc(e);
         if(d.chgt) {
            d.chgt.reset();
            d.chgt = null;
         }
         var val = parseFloat(d.value);
         if(k==13) {
            dveditshare.d.innerHTML = val+'%';
            _destroy(dveditshare);
            save_kpi_share(val,dveditshare.pms_objective_id,dveditshare.pms_kpi_id,dveditshare.pms_share_org_id);
            dveditshare.d = null;
            dveditshare = null;
         } else if (k==27) {
            _destroy(dveditshare);
            dveditshare.d = null;
            dveditshare = null;
         } else {
            d.chgt = new ctimer('save_kpi_share(\"'+val+'\",\"'+dveditshare.pms_objective_id+'\",\"'+dveditshare.pms_kpi_id+'\",\"'+dveditshare.pms_share_org_id+'\");',300);
            d.chgt.start();
         }
      }
      
      var dveditshare = null;
      function edit_kpi_share(pms_objective_id,pms_kpi_id,pms_share_org_id,d,e) {
         document.body.onclick = null;
         _destroy(dveditshare);
         if(dveditshare&&d==dveditshare.d) {
            dveditshare.d = null;
            dveditshare = null;
            return;
         }
         d.dv = _dce('div');
         d.dv.setAttribute('style','position:absolute;padding:5px;-moz-border-radius:5px;border:1px solid #777;background-color:#ffffcc;left:0px;');
         d.dv.innerHTML = '<div style=\"text-align:right;padding:2px;\">Share : <input onkeyup=\"kp_kpi_share(this,event);\" id=\"inp_kpi_share\" style=\"-moz-border-radius:3px;width:50px;text-align:center;\" type=\"text\" value=\"'+parseFloat(d.innerHTML)+'\"/>&nbsp;%</div>';
         d.dv = d.parentNode.appendChild(d.dv);
         d.dv.style.top = parseInt(oY(d)+d.offsetHeight+15)+'px';
         d.dv.style.left = parseInt(oX(d.parentNode)-d.dv.offsetWidth+d.parentNode.offsetWidth)+'px';
         d.dv.arrow = _dce('img');
         d.dv.arrow.setAttribute('style','position:absolute;left:0px;');
         d.dv.arrow.src = '".XOCP_SERVER_SUBDIR."/images/topmiddle.png';
         d.dv.arrow = d.dv.appendChild(d.dv.arrow);
         d.dv.arrow.style.top = '-12px';
         d.dv.arrow.style.left = parseInt(d.dv.offsetWidth-(d.parentNode.offsetWidth/2)-7)+'px';
         _dsa($('inp_kpi_share'));
         dveditshare = d.dv;
         dveditshare.d = d;
         dveditshare.pms_objective_id = pms_objective_id;
         dveditshare.pms_kpi_id = pms_kpi_id;
         dveditshare.pms_share_org_id = pms_share_org_id;
         setTimeout('document.body.onclick = function() { document.body.onclick = null; _destroy(dveditshare); };',100);
      }
      
      var editkpishareedit = null;
      var editkpisharebox = null;
      function edit_kpi_share_old(pms_objective_id,pms_kpi_id,pms_share_org_id,d,e) {
         editkpishareedit = _dce('div');
         editkpishareedit.setAttribute('id','editkpishareedit');
         editkpishareedit = document.body.appendChild(editkpishareedit);
         editkpishareedit.sub = editkpishareedit.appendChild(_dce('div'));
         editkpishareedit.sub.setAttribute('id','innereditkpishareedit');
         editkpisharebox = new GlassBox();
         editkpisharebox.init('editkpishareedit','700px','270px','hidden','default',false,false);
         editkpisharebox.lbo(false,0.3);
         editkpisharebox.appear();
         
         orgjx_app_editKPIShare(pms_objective_id,pms_kpi_id,pms_share_org_id,function(_data) {
            $('innereditkpishareedit').innerHTML = _data;
            _dsa($('pms_share_weight'));
         });
         
      }
      
      
      function delete_kpi(pms_objective_id,pms_kpi_id,d,e) {
         orgjx_app_deleteKPI(pms_objective_id,pms_kpi_id,function(_data) {
            location.reload(true);
         });
      }
      
      function save_kpi(pms_objective_id,pms_kpi_id,d,e) {
         var ret = _parseForm('frmkpi');
         orgjx_app_saveKPI(ret,function(_data) {
            location.reload(true);
         });
      }
      
      var editkpiedit = null;
      var editkpibox = null;
      function edit_kpi(pms_objective_id,pms_kpi_id,d,e) {
         editkpiedit = _dce('div');
         editkpiedit.setAttribute('id','editkpiedit');
         editkpiedit = document.body.appendChild(editkpiedit);
         editkpiedit.sub = editkpiedit.appendChild(_dce('div'));
         editkpiedit.sub.setAttribute('id','innereditkpiedit');
         editkpibox = new GlassBox();
         editkpibox.init('editkpiedit','700px','370px','hidden','default',false,false);
         editkpibox.lbo(false,0.3);
         editkpibox.appear();
         
         orgjx_app_editKPI(pms_objective_id,pms_kpi_id,function(_data) {
            $('innereditkpiedit').innerHTML = _data;
            _dsa($('pms_kpi_text'));
         });
         
      }
      
      function delete_share(pms_share_org_id,d,e) {
         orgjx_app_deleteShare(pms_share_org_id,function(_data) {
            location.reload(true);
         });
      }
      
      function kpi_mouse_over(d,e) {
         return;
         var dv = d.firstChild;
         dv.style.display = '';
      }
      
      function kpi_mouse_out(d,e) {
         return;
         var dv = d.firstChild;
         dv.style.display = 'none';
      }
      
      var vshareedit = null;
      var vsharebox = null;
      function view_share(pms_share_org_id,d,e) {
         vshareedit = _dce('div');
         vshareedit.setAttribute('id','vshareedit');
         vshareedit = document.body.appendChild(vshareedit);
         vshareedit.sub = vshareedit.appendChild(_dce('div'));
         vshareedit.sub.setAttribute('id','innervshareedit');
         vsharebox = new GlassBox();
         vsharebox.init('vshareedit','600px','270px','hidden','default',false,false);
         vsharebox.lbo(false,0.3);
         vsharebox.appear();
         
         orgjx_app_viewShare(pms_share_org_id,function(_data) {
            $('innervshareedit').innerHTML = _data;
         });
         
      }
      
      function delete_so(pms_objective_id,d,e) {
         orgjx_app_deleteSO(pms_objective_id,function(_data) {
            location.reload(true);
         });
      
      }
      
      function save_so(pms_objective_id,d,e) {
         var ret = _parseForm('frmobjective');
         orgjx_app_saveSO(ret,function(_data) {
            location.reload(true);
         });
      }
      
      function chgno(d,e) {
         var k = getkeyc(e);
         if(k==9) return;
         if(d.chgt) {
            d.chgt.reset();
            d.chgt = null;
         }
         d.chgt = new ctimer('_setcode();',500);
         d.chgt.start();
      }
      
      function _setcode() {
         var no = $('pms_objective_no').value;
         var d = $('pms_perspective_id');
         var p = d.options[d.selectedIndex].value;
         var px = p.split('|');
         $('pms_obj_code').innerHTML = px[1]+no;
         _dsa($('pms_objective_no'));
      }
      
      function chgpers(d,e) {
         var p = d.options[d.selectedIndex].value;
         orgjx_app_getNo(p,function(_data) {
            var data = recjsarray(_data);
            $('pms_objective_no').value = data[0];
            var p = d.options[d.selectedIndex].value;
            var px = p.split('|');
            $('pms_obj_code').innerHTML = px[1]+data[0];
            _dsa($('pms_objective_no'));
         });
      }
      
      var editsoedit = null;
      var editsobox = null;
      function edit_so(pms_objective_id,d,e) {
         editsoedit = _dce('div');
         editsoedit.setAttribute('id','editsoedit');
         editsoedit = document.body.appendChild(editsoedit);
         editsoedit.sub = editsoedit.appendChild(_dce('div'));
         editsoedit.sub.setAttribute('id','innereditsoedit');
         editsobox = new GlassBox();
         editsobox.init('editsoedit','800px','510px','hidden','default',false,false);
         editsobox.lbo(false,0.3);
         editsobox.appear();
         
         orgjx_app_editSO(pms_objective_id,function(_data) {
            $('innereditsoedit').innerHTML = _data;
            setTimeout(\"_dsa($('pms_objective_no'))\",300);
         });
         
      }
      
      var slorgedit = null;
      var slorgbox = null;
      function select_org(d,e) {
         slorgedit = _dce('div');
         slorgedit.setAttribute('id','slorgedit');
         slorgedit = document.body.appendChild(slorgedit);
         slorgedit.sub = slorgedit.appendChild(_dce('div'));
         slorgedit.sub.setAttribute('id','innerslorgedit');
         slorgbox = new GlassBox();
         slorgbox.init('slorgedit','700px','500px','hidden','default',false,false);
         slorgbox.lbo(false,0.3);
         slorgbox.appear();
         
         orgjx_app_browseOrgs(null,function(_data) {
            $('innerslorgedit').innerHTML = _data;
         });
         
      }
      
      function do_select_org(org_id,d,e) {
         orgjx_app_selectOrg(org_id,function(_data) {
            location.reload(true);
         });
      }
      
      ////// sharing
      
      var slorgshareedit = null;
      var slorgsharebox = null;
      function add_share(d,e) {
         slorgshareedit = _dce('div');
         slorgshareedit.setAttribute('id','slorgshareedit');
         slorgshareedit = document.body.appendChild(slorgshareedit);
         slorgshareedit.sub = slorgshareedit.appendChild(_dce('div'));
         slorgshareedit.sub.setAttribute('id','innerslorgshareedit');
         slorgsharebox = new GlassBox();
         slorgsharebox.init('slorgshareedit','700px','500px','hidden','default',false,false);
         slorgsharebox.lbo(false,0.3);
         slorgsharebox.appear();
         
         orgjx_app_browseOrgShare(null,function(_data) {
            $('innerslorgshareedit').innerHTML = _data;
         });
         
      }
      
      function do_select_org_share(org_id,d,e) {
         orgjx_app_addShare(org_id,function(_data) {
            location.reload(true);
         });
      }
      
      //]]></script>";
      
      return $js.$orgsel.$ret;
   }
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $this->pmsobjective();
            break;
         default:
            $ret = $this->pmsobjective();
            break;
      }
      return $ret;
   }
}

} // PMS_OBJECTIVE_DEFINED
?>