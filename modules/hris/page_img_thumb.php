<?php

require_once("../../config.php");

if(isset($_GET['image_id'])&&isset($_GET["page_id"])){
	$img = new img($_GET["image_id"],$_GET["page_id"]);
	$img->resize();
	$img->show();
	flush();
	exit(0);
}


class img {
	
	var $image = '';
	var $temp = '';
	var $mime_type = '';
	
	function img($image_id,$page_id){
	   $db=&Database::getInstance();
	   $sql = "SELECT image_nm,fpath,mime_type FROM ".XOCP_PREFIX."page_images"
	        . " WHERE page_id = '$page_id'"
	        . " AND image_id = '$image_id'";
	   $result = $db->query($sql);
	   if($db->getRowsNum($result)==1) {
	      list($image_nm,$fpath,$mime_type)=$db->fetchRow($result);
	      $file = XOCP_DOC_ROOT."/modules/hris/data/pages/images/page_${page_id}/$fpath/$image_nm";
	      $this->mime_type = $mime_type;
   		if(file_exists($file)) {
   		   switch($mime_type) {
   		      case "image/jpeg" :
   			      $this->image = imagecreatefromjpeg($file);
   			      break;
   		      case "image/gif" :
   			      $this->image = imagecreatefromgif($file);
   			      break;
   		      case "image/png" :
   			      $this->image = imagecreatefrompng($file);
   			      break;
   			   case "application/msword" :
                  $ext = substr(strtolower($file_original),-3);
                  if($ext=="doc") {
      			      $this->image = imagecreatefrompng(XOCP_DOC_ROOT."/images/icon_doc.png");
      			      imagepng($this->image);
                  } else if($ext=="xls") {
      			      $this->image = imagecreatefrompng(XOCP_DOC_ROOT."/images/icon_xls.png");
      			      imagepng($this->image);
                  }
   			      break;
   			   case "video/mpeg" :
                  $this->image = imagecreatefrompng(XOCP_DOC_ROOT."/images/icon_vid.png");
                  imagepng($this->image);
   			      break;
   			   default:
   			      $this->image = imagecreatefrompng(XOCP_DOC_ROOT."/images/icon_file.png");
   			      imagepng($this->image);
   			      exit();
   			      break;
   			}
   		} else {
   			$this->errorHandler();
   		}
		} else {
		   $this->errorHandler();
		}
		return;
	}
	
	function resize($width = 400, $height = 120, $aspectradio = true){
		$o_wd = imagesx($this->image);
		$o_ht = imagesy($this->image);
		if(isset($aspectradio)&&$aspectradio) {
			$w = round($o_wd * $height / $o_ht);
			$h = round($o_ht * $width / $o_wd);
			if(($height-$h)<($width-$w)){
				$width =& $w;
			} else {
				$height =& $h;
			}
		}
		$this->temp = imagecreatetruecolor($width,$height);
      imagealphablending($this->temp,FALSE);
      imagesavealpha($this->temp,TRUE);
		imagecopyresampled($this->temp, $this->image,
		0, 0, 0, 0, $width, $height, $o_wd, $o_ht);
		$this->sync();
		return;
	}
	
	function sync(){
		$this->image =& $this->temp;
		unset($this->temp);
		$this->temp = '';
		return;
	}
	
	function show(){
		$this->_sendHeader();
		imagepng($this->image);
		return;
	}
	
	function _sendHeader(){
		header('Content-Type: '.$this->mime_type);
	}
	
	function errorHandler(){
//		echo "error";
		exit();
	}
	
	function store($file){
		imagejpeg($this->image,$file);
		return;
	}
	
	function watermark($pngImage, $left = 0, $top = 0){
		imagealphablending($this->image, true);
		$layer = imagecreatefrompng($pngImage); 
		$logoW = imagesx($layer); 
		$logoH = imagesy($layer); 
		imagecopy($this->image, $layer, $left, $top, 0, 0, $logoW, $logoH); 
	}
}
?>