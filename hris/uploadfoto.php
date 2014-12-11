<?php
require_once("../../config.php");
require_once("modconsts.php");
$success = "";
$bodyonload = "";
$scriptonload = "";
global $filecmd;

$person_id = $_SESSION["hris_employee_person_id"];

if(isset($_FILES["xfile"])) {
   $ftmp = $_FILES["xfile"]["tmp_name"];
   $image_nm = $_FILES["xfile"]["name"];
   $fsubdir0 = _HRIS_PERSON_DATADIR;
   $fsubdir1 = $person_id;
   $fpath = "$fsubdir0";
   //$mime_type = trim(exec("$filecmd $ftmp"));
   $mime_type = mime_content_type($ftmp);
   $mime_type = "image/jpeg";
   $error = "";
   $ext = "";
   switch($mime_type) {
      case "image/gif":
         $ext = ".gif";
         break;
      case "image/png":
         $ext = ".png";
         break;
      case "image/jpeg":
         $ext = ".jpg";
         break;
      default:
         //$error = "Unsupported mime type for image: $mime_type".$ftmp;
		 $ext = ".jpg";
         break;
   }
   if(!file_exists(XOCP_DOC_ROOT.$fpath)) {
      mkdir(XOCP_DOC_ROOT.$fpath);
   }
   $picture_nm = strtoupper("picture_${person_id}${ext}");
   $fname = "$fpath/$picture_nm";
   if($ext!=""&&$ftmp!=""&&$fname!=""&&move_uploaded_file($ftmp, XOCP_DOC_ROOT.$fname)) {
      $db=&Database::getInstance();
      $sql = "UPDATE ".XOCP_PREFIX."persons SET "
           . "picture_nm = '$picture_nm',"
           . "picture_path = '$fpath',"
           . "mime_type = '$mime_type'"
           . " WHERE person_id = '$person_id'";
      $db->query($sql);
      $success = "success";
      $bodyonload = "onload='loadFile();'";
      $scriptonload = "
      function loadFile() {
         var par = window.parent.document;
         par.load_thumbnail('$person_id','".uniqid()."');
      }
      ";
   }
}


?>
<html>
<body <?php echo $bodyonload; ?>>
<script type='text/javascript' language='javascript'><!--
      <?php echo $scriptonload; ?>
      function do_upload(frm_id,d,e) {
         var xfile = document.getElementById('xfile');
         if(xfile.value=='') {
            alert('No picture selected.');
            return;
         }
         var frm = document.getElementById(frm_id);
         frm.submit();
      }
      
      function cancel_upload() {
         var par = window.parent.document;
         var pdl = par.getElementById('pdl');
         pdl.cancelUpload();
      }
      
// --></script>
<style type='text/css'>
BODY {
   font-family:'Lucida Grande',Tahoma,Verdana,Arial,Helvetica,sans-serif;
   background-color:#ffffff;
   font-size:12px;
}

INPUT[type=file] {
   color:#000000;
   font-family:'Lucida Grande',Tahoma,Verdana,Arial,Helvetica,sans-serif;
   font-weight:bold;
   background-color:#ffffff;
   font-size:12px;
   border:1px solid #bbbbbb;
   vertical-align:middle;
   padding-left:2px;
   padding-right:2px;
}

INPUT[type=button],
INPUT[type=submit],
INPUT[type=reset],
INPUT.bt {
   font-size:11px;
   font-family:"Lucida Grande", Verdana,Arial,Helvetica,Tahoma,"Gill Sans",Futura;
   background-color:#dddddd;
   cursor:pointer;
   padding:1px;
   padding-left:6px;
   padding-right:6px;
   color:#333333;
   border:1px solid #bbbbbb;
}

INPUT[type=button]:hover,
INPUT[type=submit]:hover,
INPUT[type=reset]:hover,
INPUT.bt:hover {background-color:#bbbbbb;}

INPUT[type=button]:active,
INPUT[type=submit]:active,
INPUT[type=reset]:active,
INPUT.bt:active {background-color:#eeeeee;}




</style>
<form method="post" enctype="multipart/form-data" id='iform' name='iform' action=''>

<input type='hidden' name='MAX_FILE_SIZE' value='100000000'/>
<input type='file' id='xfile' name='xfile'/>
<br/><br/>
<input type='button' value='Upload' id='goupload' name='goupload' onclick='do_upload("iform",this,event);'/>
&nbsp;<input type='button' value='Cancel' onclick='cancel_upload();'/>
</form>
<?php echo $error; ?>
</body>
</html>