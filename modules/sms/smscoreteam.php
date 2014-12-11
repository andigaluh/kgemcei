<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/testf.php                               //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2012-07-17                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('SMSCORETEAM_DEFINED') ) {
   define('SMSCORETEAM_DEFINED', TRUE);

//include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _smscoreteam extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _ANTRAINCCMAIL_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = "Add Person to Core Team";
   var $display_comment = TRUE;
   var $data;
   
   function __construct($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function forminput() {
      /// load existing database connection
      $db=&Database::getInstance();
      
      /// each page will consists two parts: html and javascript. $ret for html, $js for javascript.
      $ret = "";
      $js = "";
      
      /// include ajax file
      require_once(XOCP_DOC_ROOT."/modules/sms/class/ajax_smscoreteam.php");
      
      /// create ajax object
      $ajax_obj = new _smscoreteamAjax("tx");
      
      /// add javascript from ajax
      $js .= $ajax_obj->getJs();
      
      /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
      /// example 1: create form
      $tgl_lahir = getSQLDate();
	  
	  
          $ex0 = "<table class='tblfrm'>"
           . "<tr><td class='tblfrmtitle' colspan='2'>Find Personnel</td></tr>"
           . "<tr><td class='tblfrmfieldname'>Name/ID</td><td class='tblfrmfieldvalue'>"
           . "<input type='text' style='width:300px;' id='qemp'/></td></tr>"
           . "<tr><td class='tblfrmbuttons' colspan='2'>"
           . "<input type='button' value='"._SEARCH."' onclick='search_employee(this,event);'/>"
           . "</td></tr>"
           . "</table>";
      
      require_once(XOCP_DOC_ROOT."/modules/sms/class/ajax_smscoreteam.php");
      $ajax = new _smscoreteamAjax("empajx");
      $ex0 .= $ajax->getJs() . "
      <script type='text/javascript' language='javascript'><!--
      ajax_feedback = null;
      var qemp = _gel('qemp');
      qemp._get_param=function() {
         var qval = this.value;
         qval = trim(qval);
         if(qval.length < 2) {
            return '';
         }
         return qval;
      };

      qemp._send_query = empajx_app_searchEmployee;
      _make_ajax(qemp);
      qemp.focus();
      
      function search_employee(d,e) {
         qemp._query();
         qemp.focus();
      }
      qemp._onselect=function(person_id,Name_Email) {
	    
		empajx_app_selectEmployee(person_id,function(_data) {
            /// update record list in the page
            $('data').innerHTML = _data;
		 });
		  //alert(person_id);
		  //alert(Name_Email);
      };      
	  
	  funtion 
      
      // --></script>";
      
	  
	  
      /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
      /// example 2: list all record
      /// the query
      $sql = "SELECT id,name,email,smtp_location,person_id FROM sms_core_team ORDER BY name";
      /// run the query, put in $result
      $result = $db->query($sql);
      /// table header
      $ex2 = "<br/><br/>List Core Team<br/>";
      $ex2 .= "<div id='data'>";
      $ex2 .= "<table class='xxlist'>"
            . "<colgroup><col width='40'/><col width='200'/><col width='300'/><col width='300'/></colgroup>"
            . "<thead><tr><td style='text-align: center;'>No.</td><td>Name</td><td>E-mail</td><td>Person ID</td></tr></thead>"
            . "<tbody>";
      /// check if there is data
      if($db->getRowsNum($result)>0) {
         /// loop get data
         while(list($id,$name,$email,$smtp_location,$personid)=$db->fetchRow($result)) {
            $name = htmlentities($name,ENT_QUOTES);                  /// sanitize
            $email = htmlentities($email,ENT_QUOTES);
            $smtp_location = htmlentities($smtp_location,ENT_QUOTES);			/// sanitize
			$numloop = $numloop + 1;
			$ex2 .= "<tr><td style='text-align: center;'>$numloop</td><td><span class='xlnk' onclick='edit_record(\"$id\",this,event);'>$name</span></td><td>$email</td><td>$personid</td></tr>";   /// put in table
         }
      }
      /// table footer
      $ex2 .= "</tbody></table>";
      $ex2 .= "</div>";
      /// add javascript for this table
      $js .= "<script type='text/javascript'><!--
      //<![CDATA[
      
      /// function that will be called
      var treditor = null;
      function edit_record(id,d,e) {
         if(treditor) {
            _destroy(treditor);
         }
         if(treditor&&treditor.id==id) {
            treditor.id = null;
            treditor = null;
            return;
         }
         var tr = d.parentNode.parentNode;
         var ntr = _dce('tr');
         var td = ntr.appendChild(_dce('td'));
         td.setAttribute('colspan','4');
         td.setAttribute('style','padding:10px;');
         td.appendChild(progress_span());
         td.setAttribute('id','tdedit_'+id);
         treditor = tr.parentNode.insertBefore(ntr,tr.nextSibling);
         treditor.id = id;
         tx_app_editRecord(id,function(_data) {
            var data = recjsarray(_data);
            $('tdedit_'+data[0]).innerHTML = data[1];
         });
      }
      
      function save_record(id,prefix,d,e) {
         var ret = _parseForm(prefix+'_frm_'+id);
         ajax_feedback = _caf;
         tx_app_saveRecord(id,ret,function(_data) { 
            $('data').innerHTML = _data;
            cancel_edit_record();
         });
      }
      
      function delete_record(id,d,e) {
         cancel_edit_record();
         tx_app_deleteRecord(id,function(_data) { 
            $('data').innerHTML = _data;
         });
      }
      
      function cancel_edit_record() {
         _destroy(treditor);
         if(treditor) {
            treditor.id = null;
         }
         treditor = null;
      }
      
      //]]>
      // --></script>";
      
      
      $ret = $ex0.$ex1.$ex2;
      
      
      return $ret.$js;
      
   }
   
         /*
            - ajax function calling convention:
            
              function callback(_data) {
                 aksdjfkad
                 laksdhfklad
              }
              func_name(var1, var2, var3, ..., varN, callback);
            
               
               
            - in ajax file var1, var2, var3, ... varN will be named $args[0], $args[1], $args[2], ... $args[N-1], consecutively
            - callback is a function that will be called after ajax request is completed and data available
            - callback parameter (_data) is data returned from php script ajax
            
            - ajax calling could also be writen like this:
              func_name(var1, var2, var3, ..., varN, function(_data) { ... function definition here ... });
            
            - or like this:
              func_name(var1, var2, var3, ..., varN, function(_data) {
                 ... function definition here ...
              });
              
         */
         
   
   
   
   /// this is mandatory function that will be called from framework
   function main() {
      $db = &Database::getInstance();
        
	  $sqljob = "SELECT j.job_id FROM hris_employee_job j LEFT JOIN hris_users u ON (u.person_id = j.employee_id) WHERE u.user_id = ".getUserid()."";
	$resultjob = $db->query($sqljob);
	 list($job_id)=$db->fetchRow($resultjob);
	//echo 'getuserid = '.getuserid(). '<br> job_id = '.$job_id; exit();
	 	 if ($job_id ==  130 || $job_id == 90 || $job_id == 68 || $job_id == 101 || $job_id == 0){
    $ret = $this->forminput()."<div style='padding:100px;'>&nbsp;</div>";
	  }
	  else
	  {
	  $ret = 'you have no access';
}
      return $ret;
   }
}

} // TESTF_DEFINED
?>