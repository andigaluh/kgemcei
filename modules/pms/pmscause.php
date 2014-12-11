<?php
//--------------------------------------------------------------------//
// Filename : modules/pms/pmsperspective.php                          //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-07-18                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('PMS_CAUSEEFFECT_DEFINED') ) {
   define('PMS_CAUSEEFFECT_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

//include_once(XOCP_DOC_ROOT."/modules/pms/pmsxocp.php");
include_once(XOCP_DOC_ROOT."/modules/pms/class/ajax_objective.php");
include_once(XOCP_DOC_ROOT."/modules/pms/class/selectpms.php");

class _pms_CauseEffect extends XocpBlock {
   var $catchvar = _PMS_CATCH_VAR;
   var $blockID = _PMS_CAUSEEFFECT_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _PMS_CAUSEEFFECT_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _pms_CauseEffect($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */

   }
   
   function showOrg($showOpt=FALSE) {
      $db =& Database::getInstance();
      if(!isset($_SESSION["pms_org_id"])) {
         $_SESSION["pms_org_id"] = 1;
      }
      
      $org_id = $_SESSION["pms_org_id"];
      $sql = "SELECT o.org_id,o.org_nm,b.org_class_nm,o.org_abbr"
           . " FROM ".XOCP_PREFIX."orgs o"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b ON b.org_class_id = o.org_class_id"
           . " WHERE o.org_id = '$org_id'";
      $result = $db->query($sql);
      $cnt = $db->getRowsNum($result);
      $showOpt = FALSE;
      $org_nm = "-";
      if($cnt == 1) {
         list($org_id,$org_nmx,$org_class_nm,$org_abbr) = $db->fetchRow($result);
         $_SESSION["hris_org_nm"] = "$org_abbr $org_nmx [$org_class_nm]";
         $org_nm = "$org_nmx $org_class_nm";
      } else if($cnt > 1) {
         $found = 0;
         while(list($org_id,$org_nmx,$org_class_nm)=$db->fetchRow($result)) {
            if($org_id==$_SESSION["hris_org_id"]) {
               $found = 1;
               $org_nm = "$org_abbr $org_nmx [$org_class_nm]";
               break;
            }
         }
         if($found==0) {
            $showOpt = TRUE;
         }
      } else {
         $_SESSION["hris_org_nm"] = NULL;
         $_SESSION["hris_org_id"] = 0;
         $showOpt = TRUE;
      }
      if($org_nm == "") $org_nm = "-";
      
      require_once(XOCP_DOC_ROOT."/modules/pms/class/ajax_selectorg.php");
      $ajax = new _hris_class_SelectOrgAjax("slrjx");
      $js = "";
      $js .= "\n<script type=\"text/javascript\" src=\"".XOCP_SERVER_SUBDIR."/include/treeorg.js\"></script>";
      $js .= $ajax->getJs();
      $js .= "\n<script type='text/javascript'>\n//<![CDATA[
     
      function _org_select_org(org_id,d,e) {
         slrjx_app_setOrg(org_id,function(_data) {
            location.reload();
         });
      }
      
      var dv = null;
      function show_org_opt(d,e) {
         var Element = _gel('list_org');
         if (dv&&dv.style.display!='none') {
            var uls = _gel('navSlide');
            var dvx = _gel('dvSlide');
            new Effect.toggle(Element,'blind',{duration:0.2}); 
         } else {
            _destroy(uls);  
            dv = document.createElement('div');
            dv.setAttribute('id','dvSlide');
            dv.innerHTML = '';
            dv = Element.appendChild(dv);
            Element.dv = dv;
            Element.dv.appendChild(progress_span());
            slrjx_app_getOrgOpt(function(_data) {
               Element.dv.innerHTML = _data;
               new Effect.toggle(Element,'blind',{duration:0.2});
            });
          
         }
         return true;
      }
      
      var newHref = null;
      function selorgopt(org_id,org_nm) {
         var Element = _gel('list_org');
         new Effect.toggle(Element,'blind',{duration:0.2});
         slrjx_app_setOrg(org_id,obj_id,null);
         newHref = '".XOCP_SERVER_SUBDIR."/index.php?X_hris="._HRIS_SELECTORG_BLOCK."&org_id='+org_id+'&obj_id='+obj_id;
         setTimeout('gotoOrg();',300);
      }
      
      function gotoOrg() {
         location.href = newHref;
      }

      ".($showOpt==TRUE?"setTimeout('show_org_opt(null,null);',100);":"")."
      
      //]]>\n</script>";
      
      $js .= "\n<script type=\"text/javascript\" src=\"".XOCP_SERVER_SUBDIR."/modules/pms/include/treeorg.js\"></script>";
      
      
      return $js."<div class='orgsel'><table border='0' width='100%' cellpadding='2' cellspacing='0'>
              <tr><td id='hris_org_nm'>Level of Organization : <span style='font-weight:bold;'>".htmlentities($org_nm)."</span></td>
              <td align='right'>[<span class='xlnk' id='chorgsp' onclick='return show_org_opt(this,event);'>Change Level"
              ."</span>]</td></tr></table><div id='list_org' style='display:none;background-color:#FFFFFF;text-align:left;'></div></div>";
   }

   function pmsobjective() {
      $psid = $_SESSION["pms_psid"];
      $db=&Database::getInstance();
      $ajax = new _pms_class_ObjectiveAjax("orgjx");
      
      $user_id = getUserID();
      
      $pmsselobj = new _pms_class_SelectSession();
      $pmssel = "<div style='padding-bottom:2px;'>".$pmsselobj->show()."</div>";
      
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
           $self_first_assessor_job_id,
           $self_next_assessor_job_id)=_hris_getinfobyuserid($user_id);
      
      
      if(!isset($_SESSION["pms_org_id"])) {
         $_SESSION["pms_org_id"] = 1;
      }
      
      $org_id = $_SESSION["pms_org_id"];
      
      $found_access = 0;
      $sql = "SELECT pms_org_id,access_id FROM pms_org_access WHERE psid = '$psid' AND employee_id = '$self_employee_id' AND status_cd = 'normal'";
      $result = $db->query($sql);
      $first_access = 0;
      if($db->getRowsNum($result)>0) {
         while(list($pms_org_idx,$access_id)=$db->fetchRow($result)) {
            if($first_access==0) {
               $first_access = $pms_org_idx;
            }
            if($org_id==$pms_org_idx) {
               $_SESSION["pms_org_id"] = $pms_org_idx;
               $org_id = $_SESSION["pms_org_id"];
               $found_access = 1;
            }
         }
         if($first_access>0&&$found_access==0) {
            $_SESSION["pms_org_id"] = $first_access;
            $org_id = $_SESSION["pms_org_id"];
            $found_access = 1;
         }
      } else {
         $sql = "SELECT pms_org_id,access_id FROM pms_org_access WHERE psid = '$psid' AND employee_id = '0' AND status_cd = 'normal'";
         $result = $db->query($sql);
         
         $found_access = 0;
         $first_access = 0;
         if($db->getRowsNum($result)>0) {
            while(list($pms_org_idx,$access_id)=$db->fetchRow($result)) {
               if($first_access==0) {
                  $first_access = $pms_org_idx;
               }
               if($org_id==$pms_org_idx) {
                  $_SESSION["pms_org_id"] = $pms_org_idx;
                  $org_id = $_SESSION["pms_org_id"];
                  $found_access = 1;
               }
            }
            if($fist_access>0&&$found_access==0) {
               $_SESSION["pms_org_id"] = $first_access;
               $org_id = $_SESSION["pms_org_id"];
               $found_access = 1;
            }
         
         }
         
      }
      
      if($_SESSION["hr_pmsobjective"]==0&&$found_access==0) {
         return $pmssel."<div style='padding:5px;'>You don't have access privilege to setup objectives.</div>";
      }
      
      $sql = "SELECT org_class_id FROM ".XOCP_PREFIX."orgs WHERE org_id = '$org_id'";
      $result = $db->query($sql);
      list($current_org_class_id)=$db->fetchRow($result);
      
      if(!isset($_SESSION["pms_psid"])||$_SESSION["pms_psid"]==0) {
         return $pmssel;
      }
      
      $sql = "SELECT a.pms_share_org_id,b.org_abbr,b.org_nm"
           . " FROM pms_org_share a"
           . " LEFT JOIN ".XOCP_PREFIX."orgs b ON b.org_id = a.pms_share_org_id"
           . " WHERE a.psid = '$psid' AND a.pms_org_id = '$org_id'"
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
            //$tdshare .= "<td style='border-bottom:1px solid #333;border-left:1px solid #bbb;text-align:center;'><span class='xlnk' onclick='view_share(\"$pms_share_org_id\",this,event);'>$pms_share_org_abbr</span></td>";
            $share_arr[] = array($pms_share_org_id,$pms_share_org_nm,$pms_share_org_abbr);
            //$colgroup .= "<col width='50'/>";
         }
      } else {
         //$tdshare .= "<td style='border-bottom:1px solid #333;border-left:1px solid #bbb;text-align:center;'>-</td>";
         $sharehead = "";
         //$colgroup .= "<col width='50'/>";
      }
      
      $sql = "SELECT a.org_abbr,a.org_nm,b.org_class_nm FROM ".XOCP_PREFIX."orgs a LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . " WHERE org_id = '".$_SESSION["pms_org_id"]."'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($org_abbr,$org_nm,$org_class_nm)=$db->fetchRow($result);
         $orgsel = "<div style='padding:5px;border:1px solid #bbb;background-color:#ddd;'><span id='orgspan' class='xlnk' onclick='select_org(this,event);'>Level Organization : <span style='font-weight:bold;'>$org_nm $org_class_nm</span></span></div>";
      }
      
      $orgsel = $this->showOrg();
      
      reset($share_arr);
      
      $ret = "<table class='yylist' style='width:100%;'>"
           . "<tbody>";
      
      $sql = "SELECT pms_perspective_code,pms_perspective_id,pms_perspective_name FROM pms_perspective WHERE psid = '$psid' ORDER BY pms_perspective_id";
      $result = $db->query($sql);
      $ttlw = 0;
      $ttl_pms_share = array();
      $job_nm = $job_abbr = "";
      if($db->getRowsNum($result)>0) {
         while(list($pms_perspective_code,$pms_perspective_id,$pms_perspective_name)=$db->fetchRow($result)) {
            
            $ret .= "<tr><td style='font-weight:bold;border-bottom:1px solid #333;color:black;background-color:#ddf;padding:10px;'>"
                  . "$pms_perspective_name Perspective"
                  . "</td>"
                  . "</tr>";
            
            $sql = "SELECT pms_objective_id,pms_objective_no,pms_objective_text,pms_kpi_text,pms_target_text,pms_measurement_unit,pms_objective_weight,"
                 . "pms_pic_job_id,pms_pic_employee_id"
                 . " FROM pms_objective"
                 . " WHERE psid = '$psid' AND pms_org_id = '$org_id'"
                 . " AND pms_perspective_id = '$pms_perspective_id'"
                 . " ORDER BY pms_objective_no";
            $ro = $db->query($sql);
            $cnt = $db->getRowsNum($ro);
            $so = "";
            $so_no = 0;
            if($cnt>0) {
               $subttlw = 0;
               $subttl_pms_share = array();
               $ret .= "<tr><td style=''>";
               $ret .= "<table style='width:100%;'><tbody><tr>";
               while(list($pms_objective_id,$pms_objective_no,$pms_objective_text,$pms_kpi_text,$pms_target_text,$pms_measurement_unit,$pms_objective_weight,
                          $pms_pic_job_id,$pms_pic_employee_id)=$db->fetchRow($ro)) {
                  $ret .= "<td style='padding:10px;text-align:center;'>"
                        . "<table onclick='edit_cause_effect(\"$pms_objective_id\",this,event);' id='objective_${pms_objective_id}' class='tbobjective' align='center' style='max-width:150px;border:1px solid #bbb;-moz-border-radius:5px;z-index:500;'><tbody>"
                        . "<tr><td style='padding:2px;font-weight:bold;color:black;border:1px solid #bbb;background-color:#fff;'>${pms_perspective_code}${pms_objective_no}</td></tr>"
                        . "<tr><td style='padding:5px;'>".htmlentities($pms_objective_text)."</td></tr>"
                        . "</tbody></table>"
                        . "</td>";
               }
               $ret .= "</tr></tbody></table>";
               $ret .= "</td></tr>";
            
            } else {
               $ret .= "<tr><td style='text-align:center;font-style:italic;border-bottom:3px solid #333;'>"._EMPTY."</td></tr>";
            }
         }
      }
      
      $ret .= "</tbody>"
            . "<tfoot>"
            . "<tr><td>&nbsp;</td></tr>"
            . "</tfoot>"
            . "</table>";
      
      $ret .= "<div style='padding:100px;'>&nbsp;</div>";
      
      //$_SESSION["html"]->registerLoadAction("drawShape");
      
      
      $_SESSION["html"]->addHeadScript("\n<script type='text/javascript'>//<![CDATA[
      
      
      //]]></script>");
      
      $js = $ajax->getJs()
          . "<script type='text/javascript' src='".XOCP_SERVER_SUBDIR."/include/calendar.js'></script>"
          . "<script type='text/javascript'>//<![CDATA[
      
      function do_ck(src_pms_objective_id,dst_pms_objective_id,d,e) {
         var ckb = $('ckb_'+dst_pms_objective_id);
         var checked = 0;
         if(ckb.checked) {
            checked = 1;
         }
         orgjx_app_saveCauseEffectRelation(checked,src_pms_objective_id,dst_pms_objective_id,function(_data) {
         
         });
      }
      
      function select_perspective_effect(pms_perspective_id,d,e) {
         if(d.className=='selper_selected') {
            return;
         }
         var tds = $('trselper').childNodes;
         for(var i=0;i<tds.length;i++) {
            tds[i].className = 'selper';
            var pers = tds[i].id.split('_');
            $('dvpers_'+pers[1]).style.display = 'none';
         }
         d.className = 'selper_selected';
         $('dvpers_'+pms_perspective_id).style.display = '';
         
         var scrx = oX(dvce.d);
         if(scrx>500) {
            dvce.style.left = parseInt(oX(dvce.d)+dvce.d.offsetWidth-dvce.offsetWidth)+'px';
         } else {
            dvce.style.left = parseInt(oX(dvce.d))+'px';
         }
         dvce.arrow.style.top = parseInt(-10)+'px';
         if(scrx>500) {
            dvce.arrow.style.left = parseInt(dvce.offsetWidth-25)+'px';
         } else {
            dvce.arrow.style.left = parseInt(6)+'px';
         }
      }
      
      var dvce = null;
      function edit_cause_effect(pms_objective_id,d,e) {
         document.body.onclick = null;
         if(dvce) {
            _destroy(dvce);
            if(dvce.pms_objective_id==pms_objective_id) {
               dvce.pms_objective_id = null;
               dvce = null;
               return;
            }
         }
         d.dv = _dce('div');
         d.dv.setAttribute('style','position:absolute;padding:5px;-moz-border-radius:5px;border:1px solid #777;background-color:#ffffdd;left:0px;width:300px !important;-moz-box-shadow:1px 1px 3px #000;');
         d.dv.dv = d.dv.appendChild(_dce('div'));
         d.dv.dv.appendChild(progress_span());
         d.dv = d.parentNode.appendChild(d.dv);
         d.dv.style.top = parseInt(oY(d)+d.offsetHeight+8)+'px';
         var scrx = oX(d);
         if(scrx>500) {
            d.dv.style.left = parseInt(oX(d)+d.offsetWidth-d.dv.offsetWidth)+'px';
         } else {
            d.dv.style.left = parseInt(oX(d))+'px';
         }
         d.dv.arrow = _dce('img');
         d.dv.arrow.setAttribute('style','position:absolute;left:0px;');
         d.dv.arrow.src = '".XOCP_SERVER_SUBDIR."/images/bottommiddle.png';
         d.dv.arrow = d.dv.appendChild(d.dv.arrow);
         d.dv.arrow.style.top = parseInt(-10)+'px';
         if(scrx>500) {
            d.dv.arrow.style.left = parseInt(d.dv.offsetWidth-25)+'px';
         } else {
            d.dv.arrow.style.left = parseInt(6)+'px';
         }
         dvce = d.dv;
         dvce.d = d;
         dvce.pms_objective_id = pms_objective_id;
         dvce.onclick = function(event) {
            event.cancelBubble = true;
            return true;
         };
         orgjx_app_getCauseEffectRelation(pms_objective_id,function(_data) {
            dvce.dv.innerHTML = _data;
            var scrx = oX(dvce.d);
            if(scrx>500) {
               dvce.style.left = parseInt(oX(dvce.d)+dvce.d.offsetWidth-dvce.offsetWidth)+'px';
            } else {
               dvce.style.left = parseInt(oX(dvce.d))+'px';
            }
            
            dvce.arrow.style.top = parseInt(-10)+'px';
            if(scrx>500) {
               dvce.arrow.style.left = parseInt(dvce.offsetWidth-25)+'px';
            } else {
               dvce.arrow.style.left = parseInt(6)+'px';
            }
            
            
            
         });
         setTimeout('document.body.onclick = function() { document.body.onclick = null; _destroy(dvce); dvce=null; };',100);
         
      }
      
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
      
      //]]>
      </script>";
      
      $_SESSION["html"]->addHeadScript($js);
      
      return $pmssel.$orgsel.$ret;
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

} // PMS_CAUSEEFFECT_DEFINED
?>