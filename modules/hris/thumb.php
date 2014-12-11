<?php

require_once("../../config.php");

if(isset($_GET["pid"])){
	$img = new img($_GET["pid"]);
	$img->resize();
	$img->show();
	flush();
	exit(0);
}

class img {
	
	var $image = '';
	var $temp = '';
	var $mime_type = '';
	
	function img($person_id){
	   $db=&Database::getInstance();
	      
      //// detect image:
      if(file_exists(XOCP_DOC_ROOT."/modules/hris/data/person/PICTURE_${person_id}.JPG")) {
         $mime_type = "image/jpeg";
         $file = XOCP_DOC_ROOT."/modules/hris/data/person/PICTURE_${person_id}.JPG";
      } else if(file_exists(XOCP_DOC_ROOT."/modules/hris/data/person/PICTURE_${person_id}.GIF")) {
         $mime_type = "image/gif";
         $file = XOCP_DOC_ROOT."/modules/hris/data/person/PICTURE_${person_id}.GIF";
      } else if(file_exists(XOCP_DOC_ROOT."/modules/hris/data/person/PICTURE_${person_id}.PNG")) {
         $mime_type = "image/png";
         $file = XOCP_DOC_ROOT."/modules/hris/data/person/PICTURE_${person_id}.PNG";
      } else {
         $mime_type = "image/png";
         $file = XOCP_DOC_ROOT."/images/nopic.png";
      }
      
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
            default:
               $this->image = imagecreatefrompng(XOCP_DOC_ROOT."/images/nopic.png");
               break;
         }
      } else {
         $this->image = imagecreatefrompng(XOCP_DOC_ROOT."/images/nopic.png");
      }
      return;
   }
   
   function resize($width = 150, $height = 200, $aspectradio = true){
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
//      echo "error";
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
