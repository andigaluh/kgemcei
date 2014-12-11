<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/editjobclass.php                              //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_IDPEVENTMANAGEMENT_DEFINED') ) {
   define('HRIS_IDPEVENTMANAGEMENT_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_IDPEventManagement extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_IDPEVENTMANAGEMENT_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_IDPEVENTMANAGEMENT_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_IDPEventManagement($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function browser($prefix="") {
      $ret = $this->listEvent($prefix);
      return $ret;
   }
   
   function listEvent($qprefix) {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_idpeventm.php");
      $ajax = new _hris_class_IDPEventManagementAjax("evjx");
      
      $_SESSION["html"]->registerLoadAction("onLoadSubject");
      
      $_SESSION["html"]->addHeadScript("
      <script type='text/javascript'><!--
      
      var scrollto_event_id = false;
      
      function onLoadSubject() {
         var editeventsubject = getQueryVariable('editeventsubject');
         if(editeventsubject=='y') {
            var event_id = getQueryVariable('event_id');
            var method_id = getQueryVariable('method_id');
            scrollto_method_id = method_id;
            scrollto_event_id = event_id;
            edit_event(event_id,method_id,null,null);
            return;
         } else {
            scrollto_event_id = false;
            scrollto_method_id = false;
         }
         
         var createeventfromsubject = getQueryVariable('cefs');
         if(createeventfromsubject=='y') {
            var method_id = getQueryVariable('method_id');
            scrollto_method_id = method_id;
            select_method('ALL',method_id,null,null);
            return;
         }
         
         var gotosubject = getQueryVariable('gotosubject');
         if(gotosubject=='y') {
            var method_id = getQueryVariable('method_id');
            scrollto_method_id = method_id;
            if(scrollto_method_id>0) {
               var yy = oY($('tdclass_'+scrollto_method_id));
               window.scrollTo(0,yy-10);
               new Effect.Highlight('tdclass_'+scrollto_method_id, {startcolor: '#ffffaa', restorecolor: '#ffffff',duration:3});
               scrollto_method_id = null;
            }
            return;
         }
         
      }
      
      
      // --></script>
      ");
      
      if(!isset($_SESSION["bb"])) {
         $_SESSION["bb"] = 1;
      }
      
      list($xyyyy)=explode("-",getSQLDate());
      $bb = $_SESSION["bb"]+0;
      
      if($bb==1) { //// by subject title
         $sql = "SELECT substring(method_nm,1,1) as prf FROM ".XOCP_PREFIX."idp_development_method WHERE status_cd = 'normal' GROUP BY prf ORDER BY prf";
         $result = $db->query($sql);
         $cell = 0;
         $cellmax = 30;
         $tblpref = "";
         $fprefix = "";
         if($db->getRowsNum($result)>0) {
            $tblpref = "<table border='0' style='margin:0px;border-spacing:2px;padding:0px;' id='alpha'><tbody>";
            while(list($prefix)=$db->fetchRow($result)) {
               $prefix = strtoupper($prefix);
               if($fprefix=="") {
                  $fprefix = $prefix;
               }
               if($cell==0) {
                  $tblpref .= "<tr>";
               }
               if(trim($prefix)=="") {
                  $txt_prefix = "_";
               } else {
                  $txt_prefix = $prefix;
               }
               $tblpref .= "<td style='background-color:#fff;border:1px solid #444444;padding:4px;-moz-box-shadow:1px 1px 2px #999;-moz-border-radius:5px;'><a href='".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&prefix=".urlencode($prefix)."'>$txt_prefix</a></td>";
               $cell++;
               if($cell>$cellmax) {
                  $cell=0;
                  $tblpref .= "</tr>";
               }
            }
            $tblpref .= "</tbody></table>";
         }
         
         $ctrl = "<table border='0' width='100%'><tbody><tr>"
               . "<td>$tblpref</td>"
               . "</tr></tbody></table>";
         
         if(!isset($_SESSION["browse_prefix"])) {
            if($qprefix=="") {
               $qprefix = $fprefix;
            }
         } else {
            if($qprefix=="") {
               $qprefix = $_SESSION["browse_prefix"];
            }
         }
         
         $sql = "SELECT a.method_id,a.method_nm,a.method_description"
              . " FROM ".XOCP_PREFIX."idp_development_method a"
              . " WHERE a.status_cd = 'normal' AND a.method_nm LIKE '$qprefix%'"
              . " ORDER BY a.method_nm";
         $result = $db->query($sql);
         $bbsubject = "<table class='xxlist' style='width:100%;' align='center'><thead><tr><td>"
              . "<table style='width:100%;border-spacing:0px;'><tbody><tr><td>"
              . "$ctrl"
              . "<div style='padding-left:4px;'><input id='qsubject' value='search' onclick='_dsa(this)' type='text' class='searchBox' style='width:120px;'/></div>"
              . "</td><td style='text-align:right;'>"
              . "</td></tr></tbody></table>"
              . "</td></tr></thead><tbody>";
         if($db->getRowsNum($result)>0) {
            while(list($method_id,$method_nm,$method_description)=$db->fetchRow($result)) {
               $bbsubject .= "<tr><td id='tdclass_${method_id}'>"
                     . "<table border='0' class='ilist' style='width:100%;'>"
                     . "<colgroup><col/></colgroup>"
                     . "<tbody><tr>"
                     . "<td style='font-size:1.1em;color:#568;font-weight:bold;'><span class='xlnk' onclick='edit_subject(\"$method_id\");'>".htmlentities(stripslashes($method_nm))."</span></td>"
                     . "</tr>"
                     . "<tr><td><div style='color:#888;padding:2px;padding-left:20px;font-size:0.9em;'>$method_description</div></td></tr>"
                     . "</tbody></table>";
               
               
               $sql = "SELECT a.event_id,a.event_title,a.event_description,a.start_dttm,a.stop_dttm,b.institute_nm,c.method_type"
                    . " FROM ".XOCP_PREFIX."idp_event a"
                    . " LEFT JOIN ".XOCP_PREFIX."idp_institutes b USING(institute_id)"
                    . " LEFT JOIN ".XOCP_PREFIX."idp_development_method_type c ON c.method_t = a.method_t"
                    . " WHERE a.method_id = '$method_id'"
                    . " AND a.status_cd IN ('normal','registration','started')"
                    . " AND a.start_dttm >= '$xyyyy-01-01 00:00:00'"
                    . " ORDER BY a.event_title";
               $resultm = $db->query($sql);
               $el = "<table style='width:100%;'><tbody id='tbodymethod_${method_id}'>";
               if($db->getRowsNum($resultm)>0) {
                  while(list($event_id,$event_title,$event_description,$start_dttm,$stop_dttm,$institute_nm,$method_type)=$db->fetchRow($resultm)) {
                     $sql = "SELECT a.competency_id,b.competency_nm,b.competency_abbr"
                          . " FROM ".XOCP_PREFIX."idp_event_competency_rel a"
                          . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
                          . " WHERE a.event_id = '$event_id'"
                          . " ORDER BY b.compgroup_id,b.competency_class";
                     $rc = $db->query($sql);
                     $complist = "";
                     if($db->getRowsNum($rc)>0) {
                        while(list($competency_id,$competency_nm,$competency_abbr)=$db->fetchRow($rc)) {
                           $complist .= "<span title='$competency_nm'>$competency_abbr</span>&nbsp;";
                        }
                     }
                     
                     $el .= "<tr><td id='tdclass_${event_id}'>"
                           . "<table border='0' class='ilist' style='width:100%;'>"
                           . "<tbody><tr>"
                           . "<td><span onclick='edit_event(\"$event_id\",\"$method_id\",this,event);' class='xlnk'>".htmlentities(stripslashes($event_title))."</span>  <span style='font-style:italic;color:#8888ff;'>($method_type)</span></td>"
                           . "</tr>"
                           . "</tbody></table>"
                           . "</td></tr>";
                     
                  }
               }
               
               $el .= "</tbody></table>";
               
               $bbsubject .= "<div style='padding-left:20px;'>"
                           . "<div>$el</div>"
                           . "<div style='padding:5px;'><input type='button' class='sbtn' value='New Event' onclick='new_event_subject(\"$method_id\",this,event);'/></div>"
                           . "</div>"
                           . "</td></tr>";
                              
               
            }
            $bbsubject .= "<tr style='display:none;' id='trempty'><td>"._EMPTY."</td></tr>";
         } else {
            $bbsubject .= "<tr id='trempty'><td>"._EMPTY."</td></tr>";
         }
         
         $bbsubject .= "</tbody></table><div style='margin-bottom:200px;'>&nbsp;</div>";
      } else if($bb==0) { /// by year
         $sql = "SELECT a.event_id,a.event_title,c.institute_nm,b.method_type,a.start_dttm,a.method_id"
              . " FROM ".XOCP_PREFIX."idp_event a"
              . " LEFT JOIN ".XOCP_PREFIX."idp_development_method_type b ON b.method_t = a.method_t"
              . " LEFT JOIN ".XOCP_PREFIX."idp_institutes c ON c.institute_id = a.institute_id"
              . " WHERE a.status_cd IN ('normal','registration','started')"
              . " AND a.start_dttm >= '$xyyyy-01-01 00:00:00'"
              . " ORDER BY a.start_dttm,a.event_title";
         $result = $db->query($sql);
         $old_year = 0;
         $old_month = 0;
         $bbdate = "";
         global $xocp_vars;
         $tgl_ind = "";
         $bulan = $xocp_vars['month_year'];
         $arr_yyyy = array();
         $arr_data = array();
         if($db->getRowsNum($result)>0) {
            while(list($event_id,$event_title,$institute_nm,$method_t,$start_dttm,$method_id)=$db->fetchRow($result)) {
               list($dt,$tm)=explode(" ",$start_dttm);
               list($yyyy,$mm,$dd)=explode("-",$dt);
               $arr_data[$yyyy][$mm][$event_id] = array($event_id,$event_title,$institute_nm,$method_t,$start_dttm,$method_id);
            }
            
            foreach($arr_data as $yyyy=>$v0) {
               $bbdate .= "<div style='padding:5px;text-align:center;border:1px solid #888;border-bottom:0;background-color:#ccddff;font-weight:bold;border-right:0;border-left:0;'>$yyyy</div>";
               foreach($v0 as $mm=>$v1) {
                  $xmm = $mm+0;
                  $cnt = count($v0[$mm]);
                  $bbdate .= "<div style='padding:5px;text-align:left;font-weight:bold;border-top:1px solid #bbb;'><span class='xlnk' onclick='expand_date(\"$yyyy\",\"$mm\",this,event);'>".$bulan[$xmm]." $yyyy : $cnt events</span></div>";
                  $bbdate .= "<div style='padding:5px;padding-left:30px;padding-bottom:30px;display:none;' id='sortdt_${yyyy}_${mm}'><table class='xxlist'><colgroup><col width='30'/><col/></colgroup>"
                           . "<thead><tr><td>Date</td><td>Event Title</td></tr></thead>"
                           . "<tbody>";
                  foreach($v1 as $event_id=>$v2) {
                     list($event_idx,$event_title,$institute_nm,$method_type,$start_dttm,$method_id)=$v2;
                     list($dt,$tm)=explode(" ",$start_dttm);
                     list($yyyyx,$mmx,$ddx)=explode("-",$dt);
                     $ddx += 0;
                     $bbdate .= "<tr><td style='text-align:center;vertical-align:top;'>$ddx</td>"
                              . "<td id='tdclass_${event_id}' style='vertical-align:top;'>"
                              . "<table border='0' class='ilist' style='width:100%;'>"
                              . "<tbody><tr>"
                              . "<td><span onclick='edit_event(\"$event_id\",\"$method_id\",this,event);' class='xlnk'>".htmlentities(stripslashes($event_title))."</span>  <span style='font-style:italic;color:#8888ff;'>($method_type)</span></td>"
                              . "</tr>"
                              . "</tbody></table>"
                              . "</td></tr>";
                  }
                  $bbdate .= "</tbody></table></div>";
               }
            }
            
         }
      } else if($bb==2) { /// by provider
         $sql = "SELECT a.institute_id,a.event_id,a.event_title,c.institute_nm,b.method_type,a.start_dttm,a.method_id,a.method_t"
              . " FROM ".XOCP_PREFIX."idp_event a"
              . " LEFT JOIN ".XOCP_PREFIX."idp_development_method_type b ON b.method_t = a.method_t"
              . " LEFT JOIN ".XOCP_PREFIX."idp_institutes c ON c.institute_id = a.institute_id"
              . " WHERE a.status_cd IN ('normal','registration','started')"
              . " AND a.start_dttm >= '$xyyyy-01-01 00:00:00'"
              . " ORDER BY c.institute_nm,a.institute_id,a.start_dttm,a.event_title";
         $result = $db->query($sql);
         $old_year = 0;
         $old_month = 0;
         $bbdate = "";
         global $xocp_vars;
         $tgl_ind = "";
         $bulan = $xocp_vars['month_year'];
         $arr_yyyy = array();
         $arr_data = array();
         $arr_institute = array();
         $arr_method = array();
         if($db->getRowsNum($result)>0) {
            while(list($institute_id,$event_id,$event_title,$institute_nm,$method_type,$start_dttm,$method_id,$method_t)=$db->fetchRow($result)) {
               list($dt,$tm)=explode(" ",$start_dttm);
               list($yyyy,$mm,$dd)=explode("-",$dt);
               $arr_data[$method_t][$institute_id][$event_id] = array($event_id,$event_title,$institute_nm,$method_type,$start_dttm,$method_id,$method_t);
               $arr_institute[$institute_id] = $institute_nm;
               $arr_method[$method_t] = $method_type;
            }
            
            foreach($arr_data as $method_t=>$vv) {
               if($method_t!="TRN_EX") {
                  continue;
               }
               $bbdate .= "<div style='padding:5px;text-align:center;border:1px solid #888;background-color:#ccddff;font-weight:bold;border-right:0;border-left:0;border-bottom:0;'>".$arr_method[$method_t]."</div>";
               foreach($vv as $institute_id=>$v0) {
                  $institute_nm = $arr_institute[$institute_id];
                  $cnt = count($v0);
                  if($method_t!="TRN_EX") {
                     //continue;
                     $institute_nm = "Internal";
                     $institute_id = "internal_${method_t}";
                  }
                  $bbdate .= "<div style='padding:5px;text-align:left;font-weight:bold;border-top:1px solid #bbb;'><span onclick='expand_provider(\"$institute_id\",this,event);' class='xlnk'>".(trim($institute_nm==""?"<span style='color:red;'>No Institute</span>":$institute_nm))." : $cnt events</span></div>";
                  $bbdate .= "<div id='sortprovider_${institute_id}' style='padding:5px;padding-left:30px;padding-bottom:30px;display:none;'><table class='xxlist'><colgroup><col width='100'/><col/></colgroup>"
                           . "<thead><tr><td>Date</td><td>Event Title</td></tr></thead>"
                           . "<tbody>";
                  foreach($v0 as $event_id=>$v1) {
                        list($event_idx,$event_title,$institute_nm,$method_type,$start_dttm,$method_id)=$v1;
                        list($dt,$tm)=explode(" ",$start_dttm);
                        list($yyyyx,$mmx,$ddx)=explode("-",$dt);
                        $bbdate .= "<tr><td style='text-align:left;vertical-align:top;'>$ddx-$mmx-$yyyyx</td>"
                                 . "<td id='tdclass_${event_id}' style='vertical-align:top;'>"
                                 . "<table border='0' class='ilist' style='width:100%;'>"
                                 . "<tbody><tr>"
                                 . "<td><span onclick='edit_event(\"$event_id\",\"$method_id\",this,event);' class='xlnk'>".htmlentities(stripslashes($event_title))."</span>  <span style='font-style:italic;color:#8888ff;'>($method_type)</span></td>"
                                 . "</tr>"
                                 . "</tbody></table>"
                                 
                                 
                                 . "</td></tr>";
                  }
                  $bbdate .= "</tbody></table></div>";
               }
            }
            
         }
      } else if($bb==3) { /// by competency
         
         $bbdate = "";
         global $xocp_vars;
         
         $sql = "SELECT a.institute_id,a.event_id,a.event_title,c.institute_nm,b.method_type,a.start_dttm,a.method_id,a.method_t"
              . " FROM ".XOCP_PREFIX."idp_event a"
              . " LEFT JOIN ".XOCP_PREFIX."idp_development_method_type b ON b.method_t = a.method_t"
              . " LEFT JOIN ".XOCP_PREFIX."idp_institutes c ON c.institute_id = a.institute_id"
              . " WHERE a.status_cd IN ('normal','registration','started')"
              . " AND a.start_dttm >= '$xyyyy-01-01 00:00:00'"
              . " ORDER BY a.institute_id,a.start_dttm,a.event_title";
         $result = $db->query($sql);
         $arr_event = array();
         if($db->getRowsNum($result)>0) {
            while(list($institute_id,$event_id,$event_title,$institute_nm,$method_type,$start_dttm,$method_id,$method_t)=$db->fetchRow($result)) {
               list($dt,$tm)=explode(" ",$start_dttm);
               list($yyyy,$mm,$dd)=explode("-",$dt);
               
               $sql = "SELECT competency_id FROM ".XOCP_PREFIX."idp_event_competency_rel WHERE event_id = '$event_id'";
               $rc = $db->query($sql);
               if($db->getRowsNum($rc)>0) {
                  while(list($competency_id)=$db->fetchRow($rc)) {
                     $arr_event[$competency_id][$event_id] = array($event_id,$event_title,$institute_nm,$method_type,$start_dttm,$method_id,$method_t);
                  }
               }
            }
         }
         
         $sql = "SELECT b.competency_id,b.competency_nm,c.compgroup_nm,b.competency_class,(b.competency_class+0) as srt,b.competency_abbr"
              . " FROM ".XOCP_PREFIX."competency b"
              . " LEFT JOIN ".XOCP_PREFIX."compgroup c USING(compgroup_id)"
              . " ORDER BY c.compgroup_id,srt";
         $result = $db->query($sql);
         $competency_profile = "";
         $oldgroup = "";
         $oldclass = "";
         if($db->getRowsNum($result)>0) {
            while(list($competency_id,$competency_nm,$compgroup_nm,$comp_class,$x,$abbr)=$db->fetchRow($result)) {
               if($oldgroup!=$compgroup_nm) {
                  $bbdate .= "<div style='padding:5px;text-align:center;border:1px solid #888;background-color:#ccddff;font-weight:bold;border-right:0;border-left:0;'>$compgroup_nm</div>";
                  $oldgroup = $compgroup_nm;
                  $oldclass = "";
               }
               if($oldclass!=$comp_class) {
                  $bbdate .= "<div style='padding:5px;text-align:left;border:0;background-color:#ddd;font-weight:bold;border-right:0;border-left:0;'>".ucfirst($comp_class)."</div>";
                  $oldclass = $comp_class;
               }
               
               $cnt = count($arr_event[$competency_id]);
               $bbdate .= "<div style='padding:5px;text-align:left;font-weight:bold;padding-left:20px;border-top:1px solid #bbb;background-color:#fff;'><span class='xlnk' onclick='expand_competency(\"${competency_id}\",this,event);'>$abbr - $competency_nm : $cnt events</span></div>";
               
               $bbdate .= "<div style='padding:5px;padding-bottom:30px;padding-left:30px;display:none;' id='sortcomp_${competency_id}'><table class='xxlist' style='width:100%;''><colgroup><col width='100'/><col/></colgroup>"
                        . "<thead><tr><td>Date</td><td>Event</td></tr></thead>"
                        . "<tbody>";
               if(isset($arr_event[$competency_id])) {
                  foreach($arr_event[$competency_id] as $event_id=>$event) {
                     list($event_id,$event_title,$institute_nm,$method_type,$start_dttm,$method_id,$method_t) = $event;
                     list($dt,$tm)=explode(" ",$start_dttm);
                     list($yyyyx,$mmx,$ddx)=explode("-",$dt);
                     
                     $bbdate .= "<tr><td style='text-align:left;vertical-align:top;'>$ddx-$mmx-$yyyyx</td><td id='tdclass_${event_id}_${competency_id}'>"
                              . "<table border='0' class='ilist' style='width:100%;'>"
                              . "<tbody><tr>"
                              . "<td><span onclick='edit_event_competency(\"$competency_id\",\"$event_id\",\"$method_id\",this,event);' class='xlnk'>".htmlentities(stripslashes($event_title))."</span>  <span style='font-style:italic;color:#8888ff;'>($method_type)</span></td>"
                              . "</tr>"
                              . "</tbody></table>"
                              . "</td></tr>";
                     
                     
                  }
               } else {
                  $bbdate .= "<div style='font-style:italic;padding:5px;'>No event found.</div>";
               }
               
               $bbdate .= "</tbody></table></div>";
               
            }
         }
      }
      
      
      
      $browseby = "<div style='padding:10px;padding-left:0px;padding-right:0px;'><table style='width:100%;border-spacing:0px;'><tbody><tr><td>&nbsp;Browse By : </td><td><ul class='ultab'>"
                . "<li style='min-width:70px;' id='bblitab_0' onclick='browsebytab(0,this,event);' ".($bb==0?"class='ultabsel_greyrev'":"")."><span>Date</span></li>"
                . "<li style='min-width:70px;' id='bblitab_1' onclick='browsebytab(1,this,event);' ".($bb==1?"class='ultabsel_greyrev'":"")."><span>Subject</span></li>"
                . "<li style='min-width:70px;' id='bblitab_2' onclick='browsebytab(2,this,event);' ".($bb==2?"class='ultabsel_greyrev'":"")."><span>Provider</span></li>"
                . "<li style='min-width:70px;' id='bblitab_3' onclick='browsebytab(3,this,event);' ".($bb==3?"class='ultabsel_greyrev'":"")."><span>Competency</span></li>"
                . "</ul>"
                . "</td></tr>"
                . "<tr><td colspan='2'>"
                . "<div style='clear:both;margin-top:-2px;border-top:1px solid #999;padding:5px;'>"
                . "</div>"
                . "</td></tr></tbody></table></div>";
      
      ////////////////////////////// EVENT LIST /////////////////////////////////////////////////////////////
      /*
      $sql = "SELECT a.event_id,a.event_title,c.institute_nm,b.method_t,a.start_dttm"
           . " FROM ".XOCP_PREFIX."idp_event a"
           . " LEFT JOIN ".XOCP_PREFIX."idp_development_method b ON b.method_id = a.method_id"
           . " LEFT JOIN ".XOCP_PREFIX."idp_institutes c ON c.institute_id = a.institute_id"
           . " WHERE a.status_cd != 'nullified'"
           . " ORDER BY a.start_dttm,a.event_title";
      $result = $db->query($sql);
      
      $evl = "<table class='xxlist' style='width:100%;border:0px;' align='center'><thead><tr><td style='background-color:#fff;'>"
           . "<table style='width:100%;border-spacing:0px;'><tbody><tr><td>"
           . "&nbsp;"
           . "</td><td style='text-align:right;'>"
           . "<input type='button' value='Create Event' onclick='select_method(\"ALL\",this,event);'/>"
           . "</td></tr></tbody></table>"
           . "</td></tr>"
           
             . "<tr><td>"
                  . "<table border='0' class='ilist' style='width:100%;'>"
                  . "<colgroup><col/><col width='150'/><col width=='150'/></colgroup>"
                  . "<tbody><tr>"
                  . "<td>Event Title</td>"
                  . "<td style='text-align:center;'>Method Type</td>"
                  . "<td style='text-align:right;'>Start Date</td>"
                  . "</tr></tbody></table>"
                  . "</td></tr>"
           . "</thead><tbody id='tbproc'>";
      if($db->getRowsNum($result)>0) {
         while(list($event_id,$event_title,$institute_nm,$method_t,$start_dttm)=$db->fetchRow($result)) {
            $sql = "SELECT method_type FROM ".XOCP_PREFIX."idp_development_method_type WHERE method_t = '$method_t'";
            $resultx = $db->query($sql);
            list($method_type)=$db->fetchRow($resultx);
            
            
            $sql = "SELECT a.rel_id,a.competency_id,a.rcl_min,a.rcl_max,b.competency_nm,b.competency_abbr"
                 . " FROM ".XOCP_PREFIX."idp_event_competency_rel a"
                 . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
                 . " WHERE a.event_id = '$event_id'"
                 . " ORDER BY a.rel_id DESC";
            $rc = $db->query($sql);
            $info = "<div style='color:#888;padding:2px;padding-left:20px;font-size:0.9em;'>";
            if($db->getRowsNum($rc)>0) {
               while(list($rel_id,$competency_id,$rcl_min,$rcl_max,$competency_nm,$competency_abbr)=$db->fetchRow($rc)) {
                  $info .= "<div>$competency_abbr - $competency_nm [$rcl_min-$rcl_max]</div>";
               }
            }
            $info .= "<div style='font-weight:bold;'>$institute_nm</div>";
            $info .= "</div>";
            
            
            
            $evl .= "<tr><td id='tdclass_${event_id}'>"
                  . "<table border='0' class='ilist' style='width:100%;'>"
                  . "<colgroup><col/><col width='150'/><col width=='150'/></colgroup>"
                  . "<tbody><tr>"
                  . "<td><span onclick='edit_event(\"$event_id\",\"\",this,event);' class='xlnk'>".htmlentities(stripslashes($event_title))."</span></td>"
                  . "<td style='text-align:center;'>$method_type</td>"
                  . "<td style='text-align:right;'>".sql2ind($start_dttm,"date")."</td>"
                  . "</tr>"
                  . "<tr><td colspan='3'>$info</td></tr>"
                  . "</tbody></table>"
                  . "</td></tr>";
         
         }
         $evl .= "<tr style='display:none;' id='trempty'><td>"._EMPTY."</td></tr>";
      } else {
         $evl .= "<tr id='trempty'><td>"._EMPTY."</td></tr>";
      }
      
      $evl .= "</tbody></table>";
      */
      ////////////////////////// END OF EVENT LIST //////////////////////////////////////////////////////////////
      
      ////////////////////////// EVENT REQUEST //////////////////////////////////////////////////////////////////
      
      $evr = "<table class='xxlist' style='width:100%;border:0px;' align='center'><thead><tr><td style='background-color:#fff;'>"
           . "<table style='width:100%;border-spacing:0px;'><tbody><tr><td>"
           . "&nbsp;"
           . "</td><td style='text-align:right;'>"
           . "<input type='button' value='Refresh Request' onclick='refresh_req(this,event);'/>"
           . "</td></tr></tbody></table>"
           . "</td></tr>"
           
             . "<tr><td>"
                  . "<table border='0' class='ilist' style='width:100%;'>"
                  . "<colgroup><col/><col width='150'/><col width='50'/><col width='150'/></colgroup>"
                  . "<tbody><tr>"
                  . "<td>Subject</td>"
                  . "<td style='text-align:center;'>Method Type</td>"
                  . "<td style='text-align:center;'>Popularity</td>"
                  . "<td style='text-align:right;'>Event</td>"
                  . "</tr></tbody></table>"
                  . "</td></tr>"
           . "</thead><tbody><td id='reqlist'>";
      
      $evr .= $ajax->app_renderEventRequest(array(0,0));
      
      $evr .= "</td></tbody></table>";
      
      ////////////////////////// END OF EVENT REQUEST ///////////////////////////////////////////////////////////
      
      $ret = "<ul class='ultab'>"
           . "<li id='litab_0' class='ultabsel_greyrev'><span  onclick='pnltab(\"0\",this,event);' class='xlnk'>Event List</span></li>"
           . "<li id='litab_1' style=''><span  onclick='pnltab(\"1\",this,event);' class='xlnk'>Event Request</span></li>"
           . "</ul><div style='min-height:100px;border:1px solid #999999;clear:both;padding:0px;'>"
           . "<div id='dvtab_0'>"
           . $browseby
           . $bbdate
           . $bbsubject
           . "</div>"
           . "<div id='dvtab_1' style='display:none;'>$evr</div>"
           . "</div><div style='margin-bottom:200px;'>&nbsp;</div>";
      
      return $ret.$ajax->getJs()."<script type='text/javascript' src='".XOCP_SERVER_SUBDIR."/include/calendar.js'></script><script type='text/javascript'><!--
      
      function expand_competency(competency_id,d,e) {
         var dv = $('sortcomp_'+competency_id);
         if(dv) {
            if(dv.style.display=='none') {
               dv.style.display = '';
            } else {
               dv.style.display = 'none';
            }
         }
      }
      
      function expand_provider(institute_id,d,e) {
         var dv = $('sortprovider_'+institute_id);
         if(dv) {
            if(dv.style.display=='none') {
               dv.style.display = '';
            } else {
               dv.style.display = 'none';
            }
         }
      }
      
      function expand_date(yyyy,mm,d,e) {
         var dv = $('sortdt_'+yyyy+'_'+mm);
         if(dv) {
            if(dv.style.display=='none') {
               dv.style.display = '';
            } else {
               dv.style.display = 'none';
            }
         }
      }
      
      ajax_feedback = null;
      var qsubject = _gel('qsubject');
      if(qsubject) {
         qsubject._align='left';
         qsubject._get_param=function() {
            var qval = this.value;
            qval = trim(qval);
            if(qval.length < 2) {
               return '';
            }
            return qval;
         };
         
         qsubject._onselect=function(resId) {
            location.href = '".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&gotosubject=y&method_id='+resId;
         };
         
         qsubject._send_query = function(q) {
            evjx_app_searchSubject(q,function(_data) {
               qsubject._success(_data);
            });
         }
         _make_ajax(qsubject);
      }
      
      function create_event_on(method_id) {
         pnltab(0,null,null);
         evjx_app_createEvent(method_id,function(_data) {
            var data = recjsarray(_data);
            var tr = _dce('tr');
            var td = tr.appendChild(_dce('td'));
            td.innerHTML = data[2];
            td.setAttribute('id','tdclass_'+data[0]);
            $('tbproc').insertBefore(tr,$('tbproc').firstChild);
            edit_event(data[0],data[1],null,null);
         });
      
      }
      
      function cancel_view_reqs() {
         _destroy(dvreqs);
         dvreqs.method_id = null;
         dvreqs = null;
      }
      
      var dvreqs = null;
      function view_requester(method_id,d,e) {
         if(dvreqs) {
            if(dvreqs.method_id == method_id) {
               cancel_view_reqs();
               return;
            } else {
               cancel_view_reqs();
            }
         }
         dvreqs = _dce('div');
         
         dvreqs.setAttribute('style','padding:20px;');
         dvreqs = $('dvreqmethod_'+method_id).appendChild(dvreqs);
         dvreqs.appendChild(progress_span());
         dvreqs.method_id = method_id;
         evjx_app_viewEventRequester(method_id,function(_data) {
            dvreqs.innerHTML = _data;
         });
      }
      function refresh_req(d,e) {
         $('reqlist').innerHTML = '';
         $('reqlist').appendChild(progress_span(' ... refresh'));
         evjx_app_renderEventRequest(null,function(_data) {
            $('reqlist').innerHTML = _data;
         });
      }
      
      function pnltab(tabno,d,e) {
         for(var i=0;i<2;i++) {
            if(tabno==i) {
               $('litab_'+i).className = 'ultabsel_greyrev';
            } else {
               $('litab_'+i).className = '';
            }
            $('dvtab_'+i).style.display = 'none';
         }
         var dv = $('dvtab_'+tabno);
         dv.style.display = '';
      }

      function browsebytab(tabno,d,e) {
         location.href = '".XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam()."&bb='+tabno;
         return;
         for(var i=0;i<4;i++) {
            if(tabno==i) {
               $('bblitab_'+i).className = 'ultabsel_greyrev';
            } else {
               $('bblitab_'+i).className = '';
            }
            $('bbdvtab_'+i).style.display = 'none';
         }
         var dv = $('bbdvtab_'+tabno);
         dv.style.display = '';
      }

      
      function back_compgroup(d,e) {
         evjx_app_browseCompetency(0,function(_data) {
            var d = $('btn_add_competency');
            cb.sub.innerHTML = _data;
            cb.style.display = '';
            cb.style.left = parseInt(oX(d)+d.offsetWidth-cb.offsetWidth)+'px';
         });
      }
      
      function do_delete_rel(rel_id) {
         var dv = $('comprel_'+rel_id);
         _destroy(dv);
         evjx_app_deleteCompetencyRel(wdv.event_id,rel_id,function(_data) {
            save_event();
         });
      }
      
      function cancel_delete_rel(rel_id) {
         var dv = $('comprel_'+rel_id);
         _destroy(dv.confirm);
      }
      
      function delete_comprel(rel_id) {
         var dv = $('comprel_'+rel_id);
         dv.rel_id = rel_id;
         dv.confirm = dv.appendChild(_dce('div'));
         dv.confirm.setAttribute('style','padding:10px;background-color:#ffcccc;text-align:left;');
         dv.confirm.innerHTML = 'Do you want to delete this competency upgrade relation?'
                              + '<br/><br/>'
                              + '<input type=\"button\" value=\"Yes (delete)\" onclick=\"do_delete_rel(\\''+rel_id+'\\');\"/>&nbsp;'
                              + '<input type=\"button\" value=\"No\" onclick=\"cancel_delete_rel(\\''+rel_id+'\\');\"/>&nbsp;';
      }
      
      function add_comp_select_competency(compgroup_id,competency_id,d,e) {
         d.innerHTML = '';
         d.appendChild(progress_span());
         evjx_app_addCompetency(competency_id,wdv.event_id,function(_data) {
            var data = recjsarray(_data);
            cb.style.display = 'none';
            $('comprel_empty').style.display = 'none';
            var dv = $('complist').insertBefore(_dce('div'),$('complist').firstChild);
            dv.setAttribute('style','border-top:1px solid #ddd;padding:2px;');
            dv.setAttribute('id','comprel_'+data[1]);
            dv.innerHTML = data[0];
            save_event();
         });
      }
      
      function add_comp_select_group(compgroup_id,d,e) {
         d.innerHTML = '';
         d.appendChild(progress_span());
         evjx_app_browseCompetency(compgroup_id,function(_data) {
            cb.sub.innerHTML = _data;
            var btn = $('btn_add_competency');
            cb.style.left = parseInt(oX(btn)+btn.offsetWidth-cb.offsetWidth)+'px';
         });
      }
      
      var cb = null;
      function add_browse_competency(d,e) {
         if(!cb) {
            cb = _dce('div');
            cb.setAttribute('style','position:absolute;display:none;min-width:300px;padding:5px;background-color:#fff;border:1px solid #bbb;-moz-border-radius:5px;-moz-box-shadow:1px 1px 5px #000;');
            cb.sub = cb.appendChild(_dce('div'));
            cb.sub.setAttribute('style','border:0px;');
            cb.sub.appendChild(progress_span());
            cb = document.body.appendChild(cb);
         }
         if(cb.style.display=='none') {
            cb.style.top = parseInt(oY(d)+d.offsetHeight)+'px';
            cb.style.left = parseInt(oX(d)+d.offsetWidth-cb.offsetWidth)+'px';
            evjx_app_browseCompetency(0,function(_data) {
               cb.sub.innerHTML = _data;
               cb.style.display = '';
               cb.style.left = parseInt(oX(d)+d.offsetWidth-cb.offsetWidth)+'px';
            });
         } else {
            cb.style.display = 'none';
         }
      }
      
      
      function do_create_event(method_t,method_id,d,e) {
         var td = d.parentNode;
         td.innerHTML = '';
         td.appendChild(progress_span());
         evjx_app_createEvent(method_t,method_id,function(_data) {
            var data = recjsarray(_data);
            addeventbox.fade();
            var tr = _dce('tr');
            var td = tr.appendChild(_dce('td'));
            td.innerHTML = data[2];
            td.setAttribute('id','tdclass_'+data[0]);
            
            $('tbodymethod_'+method_id).insertBefore(tr,$('tbodymethod_'+method_id).firstChild);
            
            edit_event(data[0],data[1],null,null);
         });
      }
      
      function new_event_subject(method_id,d,e) {
         select_method('ALL',method_id,d,e);
      }
      
      var addevent = null;
      var addeventbox = null;
      function select_method(method_t,method_id,d,e) {
         if(method_t!='ALL') {
            var td = d.parentNode;
            td.innerHTML = '';
            td.appendChild(progress_span());
         }
         addevent = _dce('div');
         addevent.setAttribute('id','addevent');
         addevent = document.body.appendChild(addevent);
         addevent.sub = addevent.appendChild(_dce('div'));
         addevent.sub.setAttribute('id','inneraddevent');
         
         if(scrollto_method_id) {
            var yy = oY($('tdclass_'+scrollto_method_id));
            scrollto_method_id = null;
            window.scrollTo(0,yy);
         }
         
         evjx_app_selectMethod(method_t,method_id,function(_data) {
            if(_data!='FAIL') {
               var data = recjsarray(_data);
               $('inneraddevent').innerHTML = data[0];
               if(addeventbox) {
                  _destroy(addeventbox.overlay);
               }
               addeventbox = new GlassBox();
               addeventbox.init('addevent','500px',data[1],'hidden','default',false,false);
               addeventbox.lbo(false,0.3);
               addeventbox.appear();
            }
         });
      }
      
      function do_change_subject(event_id,method_id,d,e) {
         ajax_feedback = _caf;
         var prog = d.appendChild(_dce('div'));
         prog.appendChild(progress_span());
         evjx_app_changeSubject(event_id,method_id,function(_data) {
            $('spsubject').innerHTML = _data;
            _destroy(wdv.subject);
            wdv.subject = null;
            return;
         });
      }
      
      function kpsubject(d,e) {
         var k = getkeyc(e);
         if(k==13) {
            var v = $('qsubjectc').value;
            wdv.subject.innerHTML = '';
            wdv.subject.appendChild(progress_span());
            evjx_app_getSubject(wdv.event_id,urlencode(v),function(_data) {
               wdv.subject.innerHTML = _data;
               var qs = $('qsubjectc');
               qs.setAttribute('onkeypress','kpsubject(this,event);');
               setTimeout('$(\"qsubjectc\").focus()',100);
            });
         }
      }
      
      function change_subject(d,e) {
         if(wdv.subject) {
            _destroy(wdv.subject);
            wdv.subject = null;
            return;
         }
         wdv.subject = _dce('div');
         wdv.subject.setAttribute('style','position:absolute;min-width:200px;background-color:#fff;padding:5px;border:1px solid #ddd;-moz-box-shadow:1px 1px 2px #666;-moz-border-radius:5px;');
         wdv.subject = d.parentNode.appendChild(wdv.subject);
         wdv.subject.style.top = (oY(d.parentNode)+d.parentNode.offsetHeight)+'px';
         wdv.subject.style.left = (oX(d.parentNode))+'px';
         wdv.subject.appendChild(progress_span());
         wdv.dvsubject = d;
         evjx_app_getSubject(wdv.event_id,'',function(_data) {
            wdv.subject.innerHTML = _data;
            var qs = $('qsubjectc');
            qs.setAttribute('onkeypress','kpsubject(this,event);');
            setTimeout('$(\"qsubjectc\").focus()',100);
         });
      }
      
      function edit_subject(method_id) {
         location.href = '".XOCP_SERVER_SUBDIR."/index.php?XP_idpdevlib_hris="._HRIS_IDPDEVLIB_BLOCK."&editsubject='+method_id;
      }
      
      var wdv = null;
      function edit_event(event_id,method_id,d,e) {
         if(wdv) {
            if(wdv.event_id != 'new' && wdv.event_id == event_id) {
               cancel_edit();
               return;
            } else {
               cancel_edit();
            }
         }
         wdv = _dce('div');
         wdv.event_id = event_id;
         wdv.subject = null;
         if(event_id=='new') {
            var tre = $('trempty');
            var tr = _dce('tr');
            var td = tr.appendChild(_dce('td'));
            tr = tre.parentNode.insertBefore(tr,tre.parentNode.firstChild);
         } else {
            var td = $('tdclass_'+event_id);
         }
         wdv.setAttribute('style','padding:10px;');
         wdv = td.appendChild(wdv);
         wdv.appendChild(progress_span());
         wdv.td = td;
         if($('trempty')) {
            $('trempty').style.display = 'none';
         }
         if(scrollto_method_id) {
            var yy = oY($('tdclass_'+scrollto_method_id));
            scrollto_method_id = null;
            window.scrollTo(0,yy);
         }
         evjx_app_editEvent(event_id,function(_data) {
            wdv.innerHTML = _data;
            $('inp_event_title').focus();
         });
      }
      
      function edit_event_competency(competency_id,event_id,method_id,d,e) {
         if(wdv) {
            if(wdv.event_id != 'new' && wdv.event_id == event_id && wdv.competency_id == competency_id) {
               cancel_edit();
               return;
            } else {
               cancel_edit();
            }
         }
         wdv = _dce('div');
         wdv.event_id = event_id;
         wdv.competency_id = competency_id;
         wdv.subject = null;
         if(event_id=='new') {
            var tre = $('trempty');
            var tr = _dce('tr');
            var td = tr.appendChild(_dce('td'));
            tr = tre.parentNode.insertBefore(tr,tre.parentNode.firstChild);
         } else {
            var td = $('tdclass_'+event_id+'_'+competency_id);
         }
         wdv.setAttribute('style','padding:10px;');
         wdv = td.appendChild(wdv);
         wdv.appendChild(progress_span());
         wdv.td = td;
         if($('trempty')) {
            $('trempty').style.display = 'none';
         }
         if(scrollto_method_id) {
            var yy = oY($('tdclass_'+scrollto_method_id));
            scrollto_method_id = null;
            window.scrollTo(0,yy);
         }
         evjx_app_editEvent(event_id,function(_data) {
            wdv.innerHTML = _data;
            $('inp_event_title').focus();
         });
      }
      
      function cancel_edit() {
         wdv.td.style.backgroundColor = '';
         if(wdv.event_id=='new') {
            _destroy(wdv.td.parentNode);
         }
         wdv.event_id = null;
         wdv.competency_id = null;
         _destroy(wdv);
         wdv = null;
      }
      
      function delete_action() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '#ffcccc';
         wdv.oldHTML = wdv.innerHTML;
         wdv.innerHTML = 'Are you sure you want to delete this event?<br/><br/>'
                       + '<input type=\"button\" value=\""._CANCEL."\" onclick=\"cancel_delete();\"/>&nbsp;&nbsp;'
                       + '<input type=\"button\" value=\""._DELETE."\" onclick=\"do_delete();\"/>';
      }
      
      function cancel_delete() {
         var td = wdv.parentNode;
         td.style.backgroundColor = '';
         wdv.innerHTML = wdv.oldHTML;
      }
      
      function do_delete() {
         evjx_app_Delete(wdv.event_id,null);
         var tr = wdv.parentNode.parentNode;
         _destroy(tr);
         wdv.event_id = null;
         wdv = null;
      }
      
      function save_event() {
         var ret = _parseForm('frm');
         var event_id = wdv.event_id;
         $('progressm').innerHTML = '';
         $('progressm').appendChild(progress_span());
         var complist = $('complist');
         var comps = _parseForm('complist');
         evjx_app_saveEvent(event_id,ret,comps,function(_data) {
            var data = recjsarray(_data);
            var td = wdv.td;
            wdv.event_id = null;
            _destroy(wdv);
            wdv = _dce('div');
            wdv.event_id = data[0];
            td.setAttribute('id',data[1]);
            td.innerHTML = data[3];
            wdv.td = td;
            
            wdv.setAttribute('style','padding:10px;');
            wdv = td.appendChild(wdv);
            wdv.td = td;
            wdv.innerHTML = data[2];
            $('inp_event_title').focus();
         });
      }
      
      // --></script>";
   }
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            if(isset($_GET["smethod"])&&$_GET["smethod"]==1&&isset($_GET["t"])&&$_GET["t"]!="") {
               $_SESSION["hris_method_t"] = $_GET["t"];
               $ret = $this->browser();
            } else if(isset($_GET["backlist"])&&$_GET["backlist"]==1) {
               unset($_SESSION["hris_method_t"]);
               $ret = $this->browser();
            } else if (isset($_GET["prefix"])) {
               $ret = $this->browser($_GET["prefix"]);
               $_SESSION["browse_prefix"] = $_GET["prefix"];
            } else if(isset($_GET["bb"])) {
               $_SESSION["bb"] = $_GET["bb"];
               $ret = $this->browser();
            
            
            /// calling from subject definition
            ///////////////////////////////////
            } else if(isset($_GET["editeventsubject"])&&$_GET["editeventsubject"]=="y") {
               _dumpvar($_GET);
               $event_id = $_GET["event_id"];
               $_SESSION["bb"] = 1; //// subject tab
               $sql = "SELECT substring(b.method_nm,1,1),a.method_id FROM ".XOCP_PREFIX."idp_event a LEFT JOIN ".XOCP_PREFIX."idp_development_method b USING(method_id) WHERE a.event_id = '$event_id'";
               $result = $db->query($sql);
               list($prefix,$method_nm)=$db->fetchRow($result);
               $ret = $this->browser($prefix);
               $_SESSION["browse_prefix"] = $prefix;
            } else if(isset($_GET["cefs"])&&$_GET["cefs"]=="y") { //// create event from subject
               $method_id = $_GET["method_id"];
               $_SESSION["bb"] = 1; //// subject tab
               $sql = "SELECT substring(method_nm,1,1) FROM ".XOCP_PREFIX."idp_development_method WHERE method_id = '$method_id'";
               $result = $db->query($sql);
               list($prefix)=$db->fetchRow($result);
               $ret = $this->browser($prefix);
               $_SESSION["browse_prefix"] = $prefix;
            } else if(isset($_GET["gotosubject"])&&$_GET["gotosubject"]=="y") {
               $method_id = $_GET["method_id"];
               $_SESSION["bb"] = 1; //// subject tab
               $sql = "SELECT substring(method_nm,1,1) FROM ".XOCP_PREFIX."idp_development_method WHERE method_id = '$method_id'";
               $result = $db->query($sql);
               list($prefix)=$db->fetchRow($result);
               $ret = $this->browser($prefix);
               $_SESSION["browse_prefix"] = $prefix;
            } else {
               $ret = $this->browser();
            }
            break;
         default:
            if(isset($_GET["cefs"])&&$_GET["cefs"]=="y") { //// create event from subject
               $method_id = $_GET["method_id"];
               $_SESSION["bb"] = 1; //// subject tab
               $sql = "SELECT substring(method_nm,1,1) FROM ".XOCP_PREFIX."idp_development_method WHERE method_id = '$method_id'";
               $result = $db->query($sql);
               list($prefix)=$db->fetchRow($result);
               $ret = $this->browser($prefix);
               $_SESSION["browse_prefix"] = $prefix;
            } else {
               $ret = $this->browser();
            }
            break;
      }
      return $ret."<div style='height:100px;'>&nbsp;</div>";
   }
}

} // HRIS_IDPEVENTMANAGEMENT_DEFINED
?>
