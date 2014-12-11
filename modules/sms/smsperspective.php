<?php
//--------------------------------------------------------------------//
// Filename : modules/sms/smsperspective.php                          //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-07-18                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('SMS_PERSPECTIVE_DEFINED') ) {
   define('SMS_PERSPECTIVE_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

include_once(XOCP_DOC_ROOT."/modules/sms/smsxocp.php");
include_once(XOCP_DOC_ROOT."/modules/sms/class/ajax_perspective.php");
include_once(XOCP_DOC_ROOT."/modules/sms/class/selectsms.php");

class _sms_Perspective extends XocpBlock {
   var $catchvar = _SMS_CATCH_VAR;
   var $blockID = _SMS_PERSPECTIVE_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _SMS_PERSPECTIVE_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _sms_Perspective($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */

   }
   
   function smsperspective() {
      $db=&Database::getInstance();
      
      $smsselobj = new _sms_class_SelectSession();
      $smssel = "<div style='padding-bottom:2px;'>".$smsselobj->show()."</div>";
      
      if(!isset($_SESSION["sms_id"])||$_SESSION["sms_id"]==0) {
         return $smssel;
      }
      
      $ajax = new _sms_class_PerspectiveAjax("psjx");
      $sql = "SELECT id,code,title,weight,sms_perspective_desc FROM sms_section_perspective"
           . " WHERE id_section_session = '".$_SESSION["sms_id"]."'"
           . " ORDER BY order_no,id";
      $result = $db->query($sql);
      
      $ret = "<div id='dvperspective'><table class='xxlist' style='width:100%;'>"
           . "<colgroup>"
           . "<col width='10%'/>"
           . "<col width='*'/>"
           . "<col width='20%'/>"
           . "</colgroup>"
           . "<thead><tr><td style='text-align:center;'>Code</td><td>Perspective</td><td style='text-align:center;'>Weight (%)</td></tr></thead>"
           . "<tbody id='tbdpers'>";
      $ttlw = 0;
      if($db->getRowsNum($result)>0) {
         while(list($sms_perspective_id,$sms_perspective_code,$sms_perspective_name,$sms_perspective_weight,$sms_perspective_desc)=$db->fetchRow($result)) {
            $ret .= "<tr id='trsms_${sms_perspective_id}'>"
                  . "<td style='text-align:center;font-size:1.2em;font-weight:bold;vertical-align:middle;border-right:1px solid #bbb;' id='pc_${sms_perspective_id}'>$sms_perspective_code</td>"
                  . "<td style='vertical-align:middle;border-right:1px solid #bbb;'><span class='xlnk' onclick='edit_perspective(\"$sms_perspective_id\",this,event);' id='pm_${sms_perspective_id}'>$sms_perspective_name</span>"
                  . "<div id='pdesc_${sms_perspective_id}' style='font-style:italic;color:#888;'>$sms_perspective_desc</div>"
                  . "</td>"
                  . "<td style='text-align:center;vertical-align:middle;' id='pw_${sms_perspective_id}'>"._bctrim(bcadd($sms_perspective_weight,0))." %</td>"
                  . "</tr>";
            $ttlw = bcadd($ttlw,$sms_perspective_weight);
         }
      }
      $ttlw = _bctrim($ttlw);
      $ret .= "</tbody>"
            . "<tfoot><tr><td colspan='2'>"
            . "<input type='button' value='Copy Perspectives/Objectives' onclick='copy_perspective(this,event);'/>&nbsp;"
            . "<input type='button' value='Add Perspective' onclick='edit_perspective(\"new\",this,event);'/>&nbsp;"
            . "</td><td style='text-align:center;' id='ttlw'>$ttlw %</td></tr></tfoot></table></div>";
      $ret .= "<div style='padding:10px;'></div>";
      $js = $ajax->getJs()."\n<script type='text/javascript'><!--
      
      function cancel_copy() {
         $('dvperspective').innerHTML = $('dvperspective').oldHTML;
         _destroy($('dvcp'));
         dvcp = null;
      }
      
      function do_copy_perspective(psid) {
         $('dvperspective').oldHTML = $('dvperspective').innerHTML;
         $('dvperspective').innerHTML = '<div style=\"padding:10px;text-align:center;background-color:#ffcccc;\">'
                                      + 'Are you sure you want to copy from other SMS session?<br/>Content of this session will be erased.<br/><br/>'
                                      + '<input type=\"button\" value=\"Yes (copy)\" onclick=\"do_do_copy_perspective(\\''+psid+'\\');\"/>&nbsp;'
                                      + '<input type=\"button\" value=\"No (cancel)\" onclick=\"cancel_copy();\"/>'
                                      + '</div>';
      }
      
      function do_do_copy_perspective(psid) {
         _destroy(dvcp);
         dvcp = null;
         ajax_feedback = _caf;
         psjx_app_copyPerspective(psid,function(_data) {
            location.reload();
         });
      }
      
      var dvcp = null;
      function copy_perspective(d,e) {
         if(!dvcp) {
            dvcp = _dce('div');
            dvcp.setAttribute('id','dvcp');
            dvcp.setAttribute('style','min-width:200px;position:absolute;padding:5px;border:1px solid #bbb;-moz-box-shadow:1px 1px 3px #000;-moz-border-radius:5px;background-color:#fff;');
            dvcp = d.parentNode.appendChild(dvcp);
            dvcp.appendChild(progress_span());
            psjx_app_getCopyAlt(function(_data) {
               dvcp.innerHTML = _data;
            });
         } else {
            _destroy(dvcp);
            dvcp = null;
         }
      }
      
      function do_delete(sms_perspective_id) {
         _destroy($('trsms_'+sms_perspective_id));
         persbox.fade();
         psjx_app_deletePerspective(sms_perspective_id,null);
      }
      
      function cancel_delete() {
         var dv = $('frmperspective');
         dv.innerHTML = dv.oldHTML;
         dv.style.backgroundColor = 'transparent';
         dvbtn = $('frmbtn');
         dvbtn.innerHTML = dvbtn.oldHTML;
      }
      
      function delete_perspective(sms_perspective_id,d,e) {
         var dv = $('frmperspective');
         dv.style.backgroundColor = '#ffcccc';
         dv.oldHTML = dv.innerHTML;
         var cd = $('pc_'+sms_perspective_id).innerHTML;
         var nm = $('pm_'+sms_perspective_id).innerHTML;
         dv.innerHTML = '<div style=\"text-align:center;padding-top:30px;\">Do you want to delete this perspective?<br/><br/>'
                      + '<table align=\"center\"><tbody>'
                      + '<tr><td style=\"text-align:left;\">Code</td><td style=\"text-align:left;font-weight:bold;\"> : '+cd+'</td></tr>'
                      + '<tr><td style=\"text-align:left;\">Name</td><td style=\"text-align:left;font-weight:bold;\"> : '+nm+'</td></tr>'
                      + '</tbody></table>'
                      + '<div style=\"padding:10px;\">'
                      + '<input type=\"button\" value=\"Yes (delete)\" onclick=\"do_delete(\\''+sms_perspective_id+'\\');\"/>&nbsp;'
                      + '<input type=\"button\" value=\"No (cancel)\" onclick=\"cancel_delete();\"/>'
                      + '</div>'
                      + '</div>';
         dvbtn = $('frmbtn');
         dvbtn.oldHTML = dvbtn.innerHTML;
         dvbtn.innerHTML = '';
      }
      
      var persedit = null;
      var persbox = null;
      function edit_perspective(sms_perspective_id,d,e) {
         persedit = _dce('div');
         persedit.setAttribute('id','persedit');
         persedit = document.body.appendChild(persedit);
         persedit.sub = persedit.appendChild(_dce('div'));
         persedit.sub.setAttribute('id','innerpersedit');
         persbox = new GlassBox();
         persbox.init('persedit','600px','370px','hidden','default',false,false);
         persbox.lbo(false,0.3);
         persbox.appear();
         
         psjx_app_editPerspective(sms_perspective_id,function(_data) {
            $('innerpersedit').innerHTML = _data;
            $('sms_perspective_code').focus();
         });
         
      }
      
      function save_perspective() {
         var ret = _parseForm('frmperspective');
         psjx_app_savePerspective(ret,function(_data) {
            var data = recjsarray(_data);
            if(data[0]==1) {
               var tr = _dce('tr');
               
               tr.setAttribute('id','trsms_'+data[1]);
               
               tr.td0 = tr.appendChild(_dce('td'));
               tr.td0.innerHTML = data[2];
               tr.td0.setAttribute('id','pc_'+data[1]);
               tr.td0.setAttribute('style','text-align:center;font-size:1.2em;font-weight:bold;vertical-align:middle;border-right:1px solid #bbb;');
               
               tr.td1 = tr.appendChild(_dce('td'));
               tr.td1.setAttribute('style','vertical-align:middle;border-right:1px solid #bbb;');
               tr.td1.sp = tr.td1.appendChild(_dce('span'));
               tr.td1.sp.setAttribute('class','xlnk');
               tr.td1.sp.setAttribute('onclick','edit_perspective(\"'+data[1]+'\",this,event);');
               tr.td1.sp.setAttribute('id','pm_'+data[1]);
               tr.td1.sp.innerHTML = data[3];
               tr.td1.dv = tr.td1.appendChild(_dce('div'));
               tr.td1.dv.setAttribute('style','font-style:italic;color:#888;');
               tr.td1.dv.innerHTML = data[5];
               tr.td1.dv.setAttribute('id','pdesc_'+data[1]);
               
               tr.td2 = tr.appendChild(_dce('td'));
               tr.td2.innerHTML = data[4]+' %';
               tr.td2.setAttribute('id','pw_'+data[1]);
               tr.td2.setAttribute('style','text-align:center;vertical-align:middle');
               $('tbdpers').appendChild(tr);
            } else {
               $('pm_'+data[1]).innerHTML = data[3];
               $('pc_'+data[1]).innerHTML = data[2];
               $('pdesc_'+data[1]).innerHTML = data[5];
               $('pw_'+data[1]).innerHTML = data[4]+' %';
            }
            $('ttlw').innerHTML = data[6]+' %';
         });
         persbox.fade();
      }
      
      // --></script>";
      return $smssel.$ret.$js;
   }
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $ret = $this->smsperspective();
            break;
         default:
            $ret = $this->smsperspective();
            break;
      }
      return $ret;
   }
}

} // SMS_PERSPECTIVE_DEFINED
?>