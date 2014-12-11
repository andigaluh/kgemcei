<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/editcustomer.php                           //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_EDITCUSTOMER_DEFINED') ) {
   define('HRIS_EDITCUSTOMER_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_EditCustomer extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_EDITCUSTOMER_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_EDITCUSTOMER_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function __construct($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function listCustomer() {
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_editcustomer.php");
      $ajax = new _hris_class_EditCustomerAjax("ocjx");
      $ajax->setReqPOST();
      
      $sql = "SELECT a.job_id,a.job_cd,a.job_nm,a.description,"
           . "b.org_nm,c.org_class_nm,a.job_abbr,(d.job_level+0) as srt"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."orgs b USING(org_id)"
           . " LEFT JOIN ".XOCP_PREFIX."org_class c USING(org_class_id)"
           . " LEFT JOIN ".XOCP_PREFIX."job_class d ON d.job_class_id = a.job_class_id"
           . " WHERE a.status_cd = 'normal'"
           . " ORDER BY d.job_class_level, b.org_nm,d.gradeval_bottom DESC,srt DESC,a.job_nm";
      
      $result = $db->query($sql);
      $ret = "<table class='xxlist' style='width:100%;' align='center'>"
           . "<thead><tr><td>"
           . "<span style='float:left;'>Job Titles</span>"
           . "<span style='float:right;'>"
           . "Cari : <input type='text' id='qjob' style='width:200px;' value='' class='searchBox'/>"
           . "</span></td></tr></thead>"
           . "<tbody><tr><td id='tdjobs'>";
      if($db->getRowsNum($result)>0) {
         while(list($job_id,$job_cd,$job_nm,$description,$org_nm,$org_class_nm,$job_abbr)=$db->fetchRow($result)) {
            $ret .= "<div id='dvjob_${job_id}' class='sb'>"
                  . $ajax->renderJob($job_id,$job_nm,$job_abbr,$org_class_nm,$org_nm)
                  . "</div>";
         }
      }
      $ret .= "</td></tr>";
      $ret .= "<tr style='display:none;' id='trempty'><td>"._EMPTY."</td></tr>";
      $ret .= "</tbody></table>";
      
      return $ret.$ajax->getJs()."<script type='text/javascript'><!--
      
      
      function generate_assessor(job_id,d,e) {
         generate_assessor.d = d;
         d.style.visibility = 'hidden';
         $('sp_progress_generate').appendChild(progress_span());
         ocjx_app_generateAssessor(job_id,function(_data) {
            generate_assessor.d.style.visibility = 'visible';
            $('sp_progress_generate').innerHTML = 'Finish.';
         });
      }
      
      var qjob = _gel('qjob');
      qjob._get_param=function() {
         var qval = this.value;
         qval = trim(qval);
         if(qval.length < 2) {
            return '';
         }
         return qval;
      };
      qjob._onselect=function(resId) {
         qjob._reset();
         qjob._showResult(false);
         location.hash = 'jobeditortop_'+resId;
         edit_job(resId,null,null);
      };
      qjob._send_query = ocjx_app_searchJob;
      _make_ajax(qjob);
      qjob.focus();
      
      function init_q_customer(d,e) {
         qcustomer = $('qcustomer');
         if(qcustomer._send_query) return;
         qcustomer._get_param=function() {
            var qval = this.value;
            qval = trim(qval);
            if(qval.length < 2) {
               return '';
            }
            return qval;
         };
         qcustomer._onselect=function(resId) {
            ocjx_app_addCustomer(wdv.job_id,resId,function(_data) {
               var data = recjsarray(_data);
               $('jobeditor').innerHTML = data[0];
               $('spcustomercount_'+wdv.job_id).innerHTML = data[1];
               init_q_customer(null,null);
            });
         };
         qcustomer._send_query = ocjx_app_searchJob;
         _make_ajax(qcustomer);
         qcustomer.focus();
      }
      
      function cancel_edit_customer_job() {
         _destroy(cdv);
         cdv.job_id = null;
         cdv = null;
      }
      
      function save_customer_job(p,c,d,e) {
         var ret = _parseForm('frmcustomertype');
         cdv.innerHTML = '';
         cdv.appendChild(progress_span());
         ocjx_app_saveJob(p,c,ret,function(_data) {
            cancel_edit_customer_job();
         });
      }
      
      function delete_customer(customer_job_id,d,e) {
         var dv = $('dvcustomer_'+customer_job_id);
         _destroy(dv);
         cdv.job_id = null;
         cdv = null;
         ajax_feedback = _caf;
         ocjx_app_deleteCustomer(wdv.job_id,customer_job_id,function(_data) {
            $('spcustomercount_'+wdv.job_id).innerHTML = _data;
         });
      }
      
      function cancel_delete_customer_job() {
         cdv.innerHTML = cdv.oldHTML;
         cdv.style.backgroundColor = '#ffffff';
      }
      
      function delete_customer_job(provider_job_id,customer_job_id,d,e) {
         cdv.oldHTML = cdv.innerHTML;
         cdv.style.backgroundColor = '#ffcccc';
         cdv.innerHTML = 'Delete this customer?<br/><br/>'
                       + '<input type=\"button\" value=\"Yes (delete)\" onclick=\"delete_customer(\\''+customer_job_id+'\\',this,event);\"/>&nbsp;'
                       + '<input type=\"button\" value=\"No\" onclick=\"cancel_delete_customer_job();\"/>';
      }
      
      var cdv = null;
      function edit_customer(job_id,d,e) {
         if(cdv) {
            _destroy(cdv);
            if(cdv.job_id&&cdv.job_id==job_id) {
               cdv.job_id = null;
               cdv = null;
               return;
            }
         }
         cdv = _dce('div');
         cdv.setAttribute('style','padding:10px;text-align:center;border:1px solid #555;background-color:#fff;');
         cdv = $('dvcustomer_'+job_id).appendChild(cdv);
         cdv.job_id = job_id;
         ocjx_app_editJob(wdv.job_id,cdv.job_id,function(_data) {
            cdv.innerHTML = _data;
         });
         /*
         cdv.innerHTML = 'Delete this customer?<br/><br/>'
                       + '<input type=\"button\" value=\"Yes (delete)\" onclick=\"delete_customer(\\''+job_id+'\\',this,event);\"/>&nbsp;'
                       + '<input type=\"button\" value=\"No\" onclick=\"cancel_delete_customer();\"/>';
         cdv = $('dvcustomer_'+job_id).appendChild(cdv);
         cdv.job_id = job_id;
         */
      }
      
      var wdv = null;
      function edit_job(job_id,d,e) {
         if(wdv) {
            if(wdv.job_id && wdv.job_id == job_id) {
               cancel_edit();
               return;
            } else {
               cancel_edit();
            }
         }
         wdv = $('dvjob_'+job_id);
         wdv.job_id = job_id;
         wdv.editor = wdv.appendChild(_dce('div'));
         wdv.editor.setAttribute('style','padding:10px;');
         wdv.editor.appendChild(progress_span());
         ocjx_app_editCustomer(job_id,function(_data) {
            var data = recjsarray(_data);
            wdv.innerHTML = data[0];
            wdv.editor = $('jobeditor');
         });
      }
      
      function cancel_edit() {
         wdv.style.backgroundColor = '';
         if(wdv.job_id=='new') {
            _destroy(wdv);
            ocjx_app_cancelNewJob(wdv.new_job_id,null);
         }
         _destroy($('jobeditor'));
         wdv.editor = null;
         wdv.job_id = null;
         wdv = null;
      }
      
      
      // --></script>";
   }
   
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $this->listCustomer();
            break;
         default:
            $ret = $this->listCustomer();
            break;
      }
      return $ret."<div style='height:100px;'>&nbsp;</div>";
   }
}

} // HRIS_EDITCUSTOMER_DEFINED
?>