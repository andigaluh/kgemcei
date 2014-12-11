<?php
//--------------------------------------------------------------------//
// Filename : modules/antrain/mdpinput.php                     //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2014-04-21                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('ANTRAIN_MDPEVENTSETUP_DEFINED') ) {
   define('ANTRAIN_MDPEVENTSETUP_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/antrain/modconsts.php");

class _antrain_MdpEventSetup extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _ANTRAIN_MDPEVENTSETUP_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = 'MDP Event Setup';
   var $display_comment = TRUE;
   var $data;
   
   function __construct($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function listMdpEventSetup() {
    $db=&Database::getInstance();
    require_once(XOCP_DOC_ROOT."/modules/antrain/class/ajax_mdpeventsetup.php");
    $ajax = new _antrain_mdpeventsetupAjax("psjx");

    $ret = "";

    /*if(!empty($_POST['event_id'])) {
	    foreach($_POST['event_id'] as $event_id) {
	    		$sqlx = "SELECT is_mdp FROM hris_idp_event WHERE event_id = '$event_id'";
	    		$resultx = $db->query($sqlx);
	    		list($is_mdpx)=$db->fetchRow($resultx);

	    		if ($is_mdpx == 0) {
	    			$check = '1';
	    		}elseif ($is_mdpx == 1) {
	    			$check = '1';
	    		}else {
	    			$check = '0';
	    		}

	            $sqlxx = "UPDATE hris_idp_event SET is_mdp = '$check' WHERE event_id = '$event_id'";
	            $db->query($sqlxx); 
	    }
	}*/

     $ex0 = "<table class='tblfrm'>"
           . "<tr><td class='tblfrmtitle' colspan='2'>Find Event</td></tr>"
           . "<tr><td class='tblfrmfieldname'>Event Name</td><td class='tblfrmfieldvalue'>"
           . "<input type='text' style='width:300px;' id='qevnt'/></td></tr>"
           . "<tr><td class='tblfrmbuttons' colspan='2'>"
           //. "<input type='button' value='"._SEARCH."' onclick='search_event(this,event);'/>"
           . "</td></tr>"
           . "</table>";

      //require_once(XOCP_DOC_ROOT."/modules/antrain/class/ajax_mdpinput.php");
      //$ajax = new _antrainssccmailAjax("psjx");


      // IS MDP

		  $is_mdp = $_POST['is_mdp'];
		  $event_id = $_POST['event_id'];

	    $N = count($event_id);
	 
	    for($i=0; $i < $N; $i++)
	    {

        $sql = "UPDATE hris_idp_event SET is_mdp = '$is_mdp[$i]' WHERE event_id = '$event_id[$i]'";
        $db->query($sql); 
	    }
		      
    
    $ret .= "<div>"
          . "<form action='index.php?XP_mdpinput' method='post'>"
    	    . "<input type='submit' value='Save Data' style='float:right;' />";  

	  $ret .= "<table style='width:100%;margin-top:10px;float:left;' class='xxlist'>"
            . "<colgroup>"
            	. "<col width='5'>"
              . "<col width='50'/>"
      				. "<col width='100'/>"
      				. "<col width='50'/>"
            . "</colgroup>";

	
    
	  $ret .= "<thead>"
				   . "<tr>"
				   . "<td style='border-right:1px solid #bbb;text-align:center;'>ID Event</td>"
           . "<td style='border-right:1px solid #bbb;text-align:center;'>Method</td>"
				   . "<td style='border-right:1px solid #bbb;text-align:center;'>Event</td>"
				   . "<td style='border-right:1px solid #bbb;text-align:center;'>MDP</td>"
			     . "</tr>"
           . "</thead>";
		
			$ret .= "<tbody id='data'>";
			
      $ret .= $ajax->renderEvent(0);
		
      $ret .= "</tbody></table></form></div>";
      
      return $ex0.$ret.$ajax->getJs()."<script src='".XOCP_SERVER_SUBDIR."/include/calendar.js' type='text/javascript'></script><script type='text/javascript'><!--
      

      var qevnt = _gel('qevnt');
      qevnt._get_param=function() {
         var qval = this.value;
         qval = trim(qval);
         if(qval.length < 2) {
            return '';
         }
         return qval;
      };
      qevnt._onselect=function(resId) {
         qevnt._reset();
         qevnt._showResult(false);
         psjx_renderEvent(resId,function(_data) {
          $('data').innerHTML = _data;
         });
         //alert(resId);
      };
      
      qevnt._send_query = psjx_app_searchEvent;
      _make_ajax(qevnt);
      qevnt.focus();

      /*ajax_feedback = null;
      var qevnt = _gel('qevnt');
      qevnt._get_param=function() {
         var qval = this.value;
         qval = trim(qval);
         if(qval.length < 2) {
            return '';
         }
         return qval;
      };

      qevnt._send_query = psjx_app_searchEvent;
      _make_ajax(qevnt);
      qevnt.focus();
      
      function search_event(d,e) {
         qevnt._query();
         qevnt.focus();
      }
      qevnt._onselect=function(person_id,Name_Email) {
      
    psjx_app_selectEvent(person_id,function(_data) {
            /// update record list in the page
            $('data').innerHTML = _data;
     });
      //alert(person_id);
      //alert(Name_Email);
      };      */
      
      // --></script>";
   }
   
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $this->listMdpEventSetup();
            break;
         default:
            $ret = $this->listMdpEventSetup();
            break;
      }
    

	  return $ret;

   }
}

} // ANTRAIN_ANTRAINSESSION_DEFINED
?>