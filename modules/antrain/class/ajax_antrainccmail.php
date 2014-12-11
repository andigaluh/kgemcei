<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_testf.php                    //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2012-07-17                                              //
// Author   : fahmi                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('ANTRAINCCMAILAJAX_DEFINED') ) {
   define('ANTRAINCCMAILAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");

class _antrainccmailAjax extends AjaxListener {
   
   function __construct($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/antrain/class/ajax_antrainccmail.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      /// ini register function di javascript
      $this->registerAction($this->_act_name,"app_addRecord","app_searchRecord","app_editRecord",
                                             "app_saveRecord","app_viewRecord","app_deleteRecord","halootest","app_searchEmployee","app_selectEmployee");
   }
   
   /// ini function definition yang teregister di javascript
   function halootest($args) {
   
   }
   
   function app_deleteRecord($args) {
      $db=&Database::getInstance();
      
      $id = $args[0];
      $sql = "DELETE FROM antrain_cc_email WHERE id = '$id'";
      $db->query($sql);
   
      $sql = "SELECT id,name,email,smtp_location FROM antrain_cc_email ORDER BY name"; /// the query
      $result = $db->query($sql); /// run the query, put in $result
      $tbldata = "<table class='xxlist'>"
               . "<colgroup><col width='40'/><col width='150'/><col width='300'/><col width='300'/></colgroup>"
               . "<thead><tr><td style='text-align:center;'>No.</td><td>Name</td><td>E-mail</td><td>SMTP Location</td></tr></thead>"
               . "<tbody>";
      if($db->getRowsNum($result)>0) {
         while(list($id,$name,$email,$smtp_location)=$db->fetchRow($result)) {
            $nama = htmlentities($nama,ENT_QUOTES);
            $email = htmlentities($email,ENT_QUOTES);
			$smtp_location = htmlentities($smtp_location,ENT_QUOTES);
			$numloop = $numloop + 1;
            $tbldata .= "<tr><td style='text-align:center;'>$numloop</td><td><span class='xlnk' onclick='edit_record(\"$id\",this,event);'>$name</span></td><td>$email</td><td>$smtp_location</td></tr>";
         }
      }
      $tbldata .= "</tbody></table>";
      
      return $tbldata;
      
   }
   
   function editor($id,$prefix) {
      $db=&Database::getInstance();
      
      $sql = "SELECT name,email,smtp_location FROM antrain_cc_email WHERE id = '$id'";
      $result = $db->query($sql);
      list($nama,$email,$smtp_location)=$db->fetchRow($result);
      
      $nama = htmlentities($nama,ENT_QUOTES);
      $email = htmlentities($email,ENT_QUOTES);
	  $smtp_location = htmlentities($smtp_location,ENT_QUOTES);
      $checked1 = 'checked';
		$checked2 = 'checked';
		 if  ($smtp_location == 'MKMS01'){
			$checked1 = '';
			$checked2 = 'checked';
		 }
		 else 
		 {
		 	$checked2 = '';
			$checked1 = 'checked';
		 
		 }
      /// bawah berikut diambil dari app_editRecord
      $frm = "<table class='xxfrm' id='${prefix}_frm_${id}' style='width:100%'><tbody>"
           . "<tr><td>Nama</td><td><input type='text' name='name' style='width:300px' value='$nama'/></td></tr>"
           . "<tr><td>E-mail</td><td><input type='text' name='email' style='width:300px' value='$email'/></td></tr>"
		   . "<tr><td>SMTP Location</td><td><input type='radio' name='smtp_location' value='JKMS01' $checked1>JKMS01<br><input type='radio' name='smtp_location' value='MKMS01' $checked2>MKMS01</td></tr>"
           . "<tr><td colspan='3'>"
           . "&nbsp;<input type='button' value='Save' onclick='save_record(\"$id\",\"$prefix\",this,event);'/>"
           . "&nbsp;<input type='button' value='Cancel' onclick='cancel_edit_record(\"$id\",this,event);'/>"
           . "&nbsp;<input type='button' value='Delete' onclick='delete_record(\"$id\",this,event);'/>"
           . "</td></tr>"
           . "</tbody></table>";
      
      return $frm;
   }
   
   function app_saveRecord($args) {
      /// load existing database connection
      $db=&Database::getInstance();
      
      $id = $args[0];
      
      /// parse variable ret dari javascript
      $vars = _parseForm($args[1]);
      
      _dumpvar($vars); /// tail -f tmp/debuglog
      
      $name = addslashes($vars["name"]);
      $email = addslashes($vars["email"]);
	  $smtp_location = addslashes($vars["smtp_location"]);
      $sql = "UPDATE antrain_cc_email SET "
           . "name = '$name',"
           . "email = '$email',"
		   . "smtp_location = '$smtp_location'"
           . " WHERE id = '$id'";
      $db->query($sql);
      
      $sql = "SELECT id,name,email,smtp_location FROM antrain_cc_email ORDER BY name"; /// the query
      $result = $db->query($sql); /// run the query, put in $result
      $tbldata = "<table class='xxlist'>"
               . "<colgroup><col width='40'/><col width='200'/><col width='300'/><col width='300'/></colgroup>"
               . "<thead><tr><td style='text-align:center;'>No.</td><td>Nama</td><td>E-mail</td><td>SMTP Location</td></thead>"
               . "<tbody>";
      if($db->getRowsNum($result)>0) {
         while(list($id,$name,$email,$smtp_location)=$db->fetchRow($result)) {
            $name = htmlentities($name,ENT_QUOTES);
            $email = htmlentities($email,ENT_QUOTES);
			$numloop = $numloop + 1;
			$tbldata .= "<tr><td style='text-align:center;'>$numloop</td><td><span class='xlnk' onclick='edit_record(\"$id\",this,event);'>$name</span></td><td>$email</td><td>$smtp_location</td></tr>"; 
         }
      }
      $tbldata .= "</tbody></table>";
      
      return $tbldata;
      
   
   }
   
   function app_editRecord($args) {
      /// load existing database connection
      $db=&Database::getInstance();
      
      $id = $args[0];
      $frm = $this->editor($id,"edit");
      
      return array($id,$frm);
   }
   
   /* function app_searchRecord($args) {
      /// load existing database connection
      $db=&Database::getInstance();
      
      $search = addslashes($args[0]);
      $sql = "SELECT id,nama,alamat,jabatan FROM testfahmi WHERE nama LIKE '%${search}%'";
      $result = $db->query($sql);
      $ret = array();
      if($db->getRowsNum($result)>0) {
         while(list($id,$nama,$alamat)=$db->fetchRow($result)) {
            $ret[] = array($nama,$id);
         }
         return $ret;
      }
      return "EMPTY";
   } */
   
   function app_addRecord($args) {
      /// load existing database connection
      $db=&Database::getInstance();
      
      /// parse variable ret dari javascript
      $vars = _parseForm($args[0]);
      
      /// _dumpvar($vars); /// tail -f tmp/debuglog
      
      $name = addslashes($vars["name"]);
      $email = addslashes($vars["email"]);
      $smtp_location = addslashes($vars["smtp_location"]);
	  
      $sql = "INSERT INTO antrain_cc_email(name,email,smtp_location) VALUES ('$name','$email','$smtp_location')";
      $db->query($sql);
      
      /// testf list all record
      $sql = "SELECT id,name,email,smtp_location FROM antrain_cc_email ORDER BY name"; /// the query
      $result = $db->query($sql); /// run the query, put in $result
      $tbldata = "<table class='xxlist'>"
			   . "<colgroup><col width='40'/><col width='200'/><col width='300'/><col width='300'/></colgroup>"
               . "<thead><tr><td style='text-align: center;'>No.</td><td>Name</td><td>E-mail</td><td>SMTP Location</td></tr></thead>"
               . "<tbody>";
      if($db->getRowsNum($result)>0) {
         while(list($id,$name,$email,$smtp_location)=$db->fetchRow($result)) {
            $name = htmlentities($name,ENT_QUOTES);
            $email = htmlentities($email,ENT_QUOTES);
            $smtp_location = htmlentities($smtp_location,ENT_QUOTES);
			$numloop = $numloop +1;
	  		$tbldata .= "<tr><td style='text-align: center;'>$numloop</td><td><span class='xlnk' onclick='edit_record(\"$id\",this,event);'>$name</span></td><td>$email</td><td>$smtp_location</td></tr>";
         }
      }
      $tbldata .= "</tbody></table>";
      
      return $tbldata;
      
   }
   
   function app_searchEmployee($args) {
      $db=&Database::getInstance();
      $qstr = $args[0];
      
    /*   $sql = "SELECT b.employee_id,b.employee_ext_id,a.person_nm,a.person_id,a.email"
           . " FROM ".XOCP_PREFIX."persons a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(person_id)"
           . " WHERE b.employee_ext_id LIKE '".addslashes($qstr)."%'"
           . " AND b.person_id IS NOT NULL"
           . " AND a.status_cd = 'normal'"
           . " GROUP BY a.person_id"
           . " ORDER BY b.employee_ext_id";
      $result = $db->query($sql);
      _debuglog($sql);
      $ret = array();
      if($db->getRowsNum($result)>0) {
         $no = 0;
         while(list($employee_id,$employee_ext_id,$employee_nm,$person_id,$email)=$db->fetchRow($result)) {
            if($no >= 1000) break;
            $ret[$employee_id] = array("$employee_nm ($email)",$person_id);
            $no++;
         }
      } */
      
      $sql = "SELECT b.employee_id,b.employee_ext_id,a.person_nm,a.person_id,a.email"
           . " FROM ".XOCP_PREFIX."persons a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(person_id)"
           . " WHERE a.person_nm LIKE '%".addslashes($qstr)."%'"
           . " AND b.person_id IS NOT NULL"
           . " AND a.status_cd = 'normal'"
           . " GROUP BY a.person_id"
           . " ORDER BY b.employee_ext_id";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         $no = 0;
         while(list($employee_id,$employee_ext_id,$employee_nm,$person_id,$email)=$db->fetchRow($result)) {
            if($no >= 1000) break;
            $ret[$employee_id] = array("$employee_nm($email)",$person_id);
            $no++;
         }
      }
      
      /* $qstr = ereg_replace("[[:space:]]+"," ",trim(strtolower($qstr)));
      
      $qstr = formatQueryString($qstr);
      
      $sql = "SELECT b.employee_id,b.employee_ext_id,a.person_nm,a.person_id,a.email MATCH (a.person_nm) AGAINST ('".addslashes($qstr)."' IN BOOLEAN MODE) as score"
           . " FROM ".XOCP_PREFIX."persons a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(person_id)"
           . " WHERE MATCH (a.person_nm) AGAINST ('".addslashes($qstr)."' IN BOOLEAN MODE)"
           . " AND b.person_id IS NOT NULL"
           . " AND a.status_cd = 'normal'"
           . " GROUP BY a.person_id"
           . " ORDER BY score DESC";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         $no = 0;
         while(list($employee_id,$employee_ext_id,$employee_nm,$person_id,$email)=$db->fetchRow($result)) {
            if($no >= 1000) break;
            $ret[$employee_id] = array("$employee_nm ($email)",$person_id);
            $no++;
         }
      } */
      
      if(count($ret)>0) {
         $xret = array();
         foreach($ret as $employee_id=>$v) {
            $xret[] = $v;
         }
         return $xret;
      } else {
         return "EMPTY";
      }
   }

   function app_selectEmployee($args){
   $db=&Database::getInstance();
	  $id = $args[0];
     // echo "id=".$id;exit();
      /// parse variable ret dari javascript
      $vars = _parseForm($args[1]);
	 // echo "vars".$vars;exit();
      
      _dumpvar($vars); /// tail -f tmp/debuglog

	 $sql = "SELECT person_nm,email FROM hris_persons WHERE person_id = $id";
	 $result = $db->query($sql);
	 list($person_nm,$email)=$db->fetchRow($result);
	  
      $name = $person_nm;
      $email = $email;
	  $part_email = explode('@',$email);
	  $head_email = substr($part_email[0],0,4);
		if(strtoupper($head_email) == 'BKC0')
		{
			$smtp_location = 'JKMS01';
		}
		else
		{
			$smtp_location = 'MKMS01';
		}
	  
	 //print_r($args); exit();
	 
	  $sql = "INSERT INTO antrain_cc_email(name,email,smtp_location) VALUES ('$name','$email','$smtp_location')";
      $db->query($sql);
      
      /// testf list all record
      $sql = "SELECT id,name,email,smtp_location FROM antrain_cc_email ORDER BY name"; /// the query
      $result = $db->query($sql); /// run the query, put in $result
      $tbldata = "<table class='xxlist'>"
			   . "<colgroup><col width='40'/><col width='200'/><col width='300'/><col width='300'/></colgroup>"
               . "<thead><tr><td style='text-align: center;'>No.</td><td>Name</td><td>E-mail</td><td>SMTP Location</td></tr></thead>"
               . "<tbody>";
      if($db->getRowsNum($result)>0) {
         while(list($id,$name,$email,$smtp_location)=$db->fetchRow($result)) {
            $name = htmlentities($name,ENT_QUOTES);
            $email = htmlentities($email,ENT_QUOTES);
            $smtp_location = htmlentities($smtp_location,ENT_QUOTES);
			$numloop = $numloop +1;
	  		$tbldata .= "<tr><td style='text-align: center;'>$numloop</td><td><span class='xlnk' onclick='edit_record(\"$id\",this,event);'>$name</span></td><td>$email</td><td>$smtp_location</td></tr>";
         }
      }
      $tbldata .= "</tbody></table>";
      
      return $tbldata;
   
   }
   
   
   
}

} /// TEMPLATEAJAX_DEFINED
?>