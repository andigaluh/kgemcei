<?php
require_once("../../config.php");
require_once("modconsts.php");
$success = "";
$bodyonload = "";
$scriptonload = "";
global $filecmd;
$user_id = getUserID();

$theme = getcss(getTheme());

if(isset($_REQUEST["pr_session_id"])) {
   $pr_session_id = $_REQUEST["pr_session_id"];
} else {
   $pr_session_id = 0;
}

if(isset($_FILES["xfile"])) {
   $ftmp = $_FILES["xfile"]["tmp_name"];
   $fname = $_FILES["xfile"]["name"];
   //$mime_type = trim(exec("$filecmd $ftmp"));
   //$mime_type = mime_content_type($ftmp);
   $error = "";
   $row_count = 0;
   if($ftmp!=""&&$fname!="") {
      $db=&Database::getInstance();
      
      $sql = "INSERT INTO ".XOCP_PREFIX."pr_import (pr_session_id,created_user_id,created_dttm,file_nm,row_count) VALUES ('$pr_session_id','$user_id',now(),'$fname',0)";
      $db->query($sql);
      $pr_import_id = $db->getInsertId();
      
      
      /// read excel here //////////////////////////////////
      require_once(XOCP_DOC_ROOT."/class/excel/reader.php");
      

      // ExcelFile($filename, $encoding);
      $data = new Spreadsheet_Excel_Reader();
      
      
      // Set output Encoding.
      $data->setOutputEncoding('CP1251');
      
      /***
      * if you want you can change 'iconv' to mb_convert_encoding:
      * $data->setUTFEncoder('mb');   
      *
      **/
      
      /***
      * By default rows & cols indeces start with 1
      * For change initial index use:
      * $data->setRowColOffset(0);
      *
      **/
      $data->setRowColOffset(0);
      
      
      
      /***
      *  Some function for formatting output.
      * $data->setDefaultFormat('%.2f');
      * setDefaultFormat - set format for columns with unknown formatting
      *
      * $data->setColumnFormat(4, '%.3f');
      * setColumnFormat - set format for column (apply only to number fields)
      *
      **/
      
      $data->read($ftmp);
      
      /*
      
      
       $data->sheets[0]['numRows'] - count rows
       $data->sheets[0]['numCols'] - count columns
       $data->sheets[0]['cells'][$i][$j] - data from $i-row $j-column
      
       $data->sheets[0]['cellsInfo'][$i][$j] - extended info about cell
          
          $data->sheets[0]['cellsInfo'][$i][$j]['type'] = "date" | "number" | "unknown"
              if 'type' == "unknown" - use 'raw' value, because  cell contain value with format '0.00';
          $data->sheets[0]['cellsInfo'][$i][$j]['raw'] = value if cell without format 
          $data->sheets[0]['cellsInfo'][$i][$j]['colspan'] 
          $data->sheets[0]['cellsInfo'][$i][$j]['rowspan'] 
      */
      
      //error_reporting(E_ALL ^ E_NOTICE);
      
      $sheets = 1; /// count($data->sheets);
      
      //_dumpvar($data->sheets);
      
      for($s = 0; $s < $sheets; $s++) {
         for ($i = 2; $i < $data->sheets[$s]['numRows']; $i++) {
            $nip      = trim($data->sheets[$s]['cells'][$i][0]);
            $nama     = trim($data->sheets[$s]['cells'][$i][1]);
            $pr_value = trim($data->sheets[$s]['cells'][$i][2]);
            if($nama!=""&&$nip!="") {
               $sql = "SELECT employee_id FROM ".XOCP_PREFIX."employee WHERE employee_ext_id = '$nip'";
               $result = $db->query($sql);
               if($db->getRowsNum($result)==1) {
                  list($employee_id)=$db->fetchRow($result);
               } else {
                  $employee_id = 0;
               }
               $sql = "INSERT INTO ".XOCP_PREFIX."pr_import_data (pr_import_id,nip,employee_nm,pr_value,employee_id)"
                    . " VALUES ('$pr_import_id','$nip','$nama','$pr_value','$employee_id')";
               $db->query($sql);
               $row_count++;
            }
         }
      }
      
      $sql = "UPDATE ".XOCP_PREFIX."pr_import SET row_count = '$row_count' WHERE pr_import_id = '$pr_import_id'";
      $db->query($sql);
      
      //////////////////////////////////////////////////////
      $bodyonload = "onload='loadFile();'";
      $scriptonload = "
      function loadFile() {
         var par = window.parent.document;
         par.post_upload();
      }
      ";
   }
}

?>
<html>
<style type='text/css' media='all'><!-- @import url(<?php echo XOCP_SERVER_SUBDIR."/$theme"; ?>); --></style>
<style type='text/css'>
</style>
<script type='text/javascript'><!--
   XOCP_SERVER_SUBDIR = '<?php echo XOCP_SERVER_SUBDIR; ?>';
// --></script>
<body <?php echo $bodyonload; ?> style='background:none;'>
<script type='text/javascript' src='<?php echo XOCP_SERVER_SUBDIR."/include/core.js"; ?>'></script>
<script type='text/javascript' language='javascript'><!--
      <?php echo $scriptonload; ?>
      function do_upload(frm_id,d,e) {
         var xfile = document.getElementById('xfile');
         if(xfile.value=='') {
            alert('No file selected.');
            return;
         }
         var frm = document.getElementById(frm_id);
         frm.submit();
         frm.style.display = 'none';
         var dv = frm.parentNode.appendChild(_dce('div'));
         dv.appendChild(progress_span());
      }
      
// --></script>
<form method="post" enctype="multipart/form-data" id='iform' name='iform' action=''>
<input type='hidden' name='MAX_FILE_SIZE' value='100000000'/>
<input type='hidden' id='pr_session_id' name='pr_session_id' value='<?php echo $pr_session_id; ?>'/>
<input type='file' id='xfile' name='xfile' size='30'/>&nbsp;<input type='button' value='Upload' id='goupload' name='goupload' onclick='do_upload("iform",this,event);'/>
<?php echo $pr_session_id; ?>
</form>
</body>
</html>