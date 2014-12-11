<?php
require_once("../../config.php");
$success = "";
$bodyonload = "";
$scriptonload = "";

$page_id = $_SESSION["editor_page_id"];

if(isset($_FILES["xfile"])) {
   $ftmp = $_FILES["xfile"]["tmp_name"];
   $image_nm = $_FILES["xfile"]["name"];
   $newpath = uniqid("img");
   $fsubdir0 = "/modules/hris/data/pages/images";
   $fsubdir1 = "page_${page_id}";
   $fpath = "$fsubdir0/$fsubdir1";
   $fname = "$fpath/${newpath}/$image_nm";
   if(!file_exists(XOCP_DOC_ROOT.$fpath)) {
      mkdir(XOCP_DOC_ROOT.$fpath);
   }
   mkdir(XOCP_DOC_ROOT."${fpath}/${newpath}");
   if($ftmp!=""&&$fname!=""&&move_uploaded_file($ftmp, XOCP_DOC_ROOT.$fname)) {
      $db=&Database::getInstance();
      $sql = "SELECT MAX(image_id) FROM ".XOCP_PREFIX."page_images"
           . " WHERE page_id = '$page_id'";
      $result = $db->query($sql);
      $file_id = 0;
      if($db->getRowsNum($result)==1) {
         list($image_id)=$db->fetchRow($result);
      }
      $image_id++;
      global $filecmd;
      //$mime_type = mime_content_type(XOCP_DOC_ROOT.str_replace(" ","\\ ",quotemeta($fname)));
      $mime_type = trim(exec("$filecmd ".XOCP_DOC_ROOT.str_replace(" ","\\ ",quotemeta($fname))));
      $sql = "INSERT INTO ".XOCP_PREFIX."page_images (page_id,image_id,image_nm,fpath,mime_type)"
           . " VALUES ('$page_id','$image_id','$image_nm','$newpath','$mime_type')";
      $db->query($sql);
      $success = "success";
      $bodyonload = "onload='loadFile();'";
      $scriptonload = "
      function loadFile() {
         var par = window.parent.document;
         par.load_thumbnail('$image_id','".uniqid("f")."','$image_nm');
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
         var frm = document.getElementById(frm_id);
         frm.submit();
      }
// --></script>
<style type='text/css'>
BODY {
   font-family:'Lucida Grande',Tahoma,Verdana,Arial,Helvetica,sans-serif;
   background-color:#f9f9f9;
   font-size:12px;
}

INPUT[type=file] {
   color:#000000;
   font-family:'Lucida Grande',Tahoma,Verdana,Arial,Helvetica,sans-serif;
   font-weight:bold;
   background-color:#ffffcc;
   font-size:12px;
   border:1px solid #999999;
   vertical-align:middle;
   padding-left:2px;
   padding-right:2px;
}
INPUT[type=submit] {
   color:#000000;
   font-family:'Lucida Grande',Tahoma,Verdana,Arial,Helvetica,sans-serif;
   font-weight:bold;
   background-color:#dddddd;
   font-size:12px;
   border:1px solid #999999;
   vertical-align:middle;
   padding-left:2px;
   padding-right:2px;
}
INPUT[type=submit] {background-color:#dddddd;cursor:pointer;}
INPUT[type=submit]:hover {background-color:#bbbbbb;}
INPUT[type=submit]:active {background-color:#eeeeee;}


</style>
<form method="post" enctype="multipart/form-data" id='iform' name='iform' action=''>
<input type='hidden' name='MAX_FILE_SIZE' value='100000000'/>
<input type='file' id='xfile' name='xfile' onchange='do_upload("iform",this,event);'/>
</form>
</body>
</html>