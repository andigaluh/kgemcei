<?php
//--------------------------------------------------------------------//
// Filename : class/pdf/fpdf/scripts/writetag.php                     //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-02-19                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('FPDF_WRITETAG_DEFINED') ) {
   define('FPDF_WRITETAG_DEFINED', TRUE);

class _fpdf_WriteTag {
   var $fpdf;        // fpdf object
   var $style_def;   // defined styles
   var $text;        // text to write
   var $x;
   var $y;
   var $l;
   var $h;
   var $styles;
   var $words;
   var $attrib;
   var $len;
   
   function _fpdf_WriteTag(&$fpdf) {
      $this->fpdf = &$fpdf;
   }

   // ################################# Public Functions
   
   function Write($l,$h,$text,$align) {
      $this->Init();
      $this->l = $l;
      $this->h = $h;
      $this->align = $align;
      $this->text = $text;
      $this->ParseLine();
      $this->PrintText();
   }

   function DefineStyle($tag,$family,$style,$size,$color=0,$offsetY=0) {
       $tag=trim($tag);
       $this->style_def[$tag]['family']=trim($family);
       $this->style_def[$tag]['style']=trim($style);
       $this->style_def[$tag]['size']=trim($size);
       $this->style_def[$tag]['color']=trim($color);
       $this->style_def[$tag]['offsety']=$offsetY;
   }
   
   function SetStyle($tag) {
      if(isset($this->style_def[$tag])) {
         $this->fpdf->SetFont($this->style_def[$tag]['family'],
                              $this->style_def[$tag]['style'],
                              $this->style_def[$tag]['size']);
         $this->fpdf->SetTextColor($this->style_def[$tag]['color']);
         if($this->style_def[$tag]['offsety']!=0) {
            $this->fpdf->SetXY($this->fpdf->GetX(),$this->y+$this->style_def[$tag]['offsety']);
         } else {
            $this->fpdf->SetXY($this->fpdf->GetX(),$this->y);
         }
      }
   }
   
   function Init() {
      $this->styles = array();
      $this->words = array();
      $this->attrib = array();
      if(is_object($this->fpdf)) {
         $curFontFamily = $this->fpdf->FontFamily;
         $curFontStyle = $this->fpdf->FontStyle;
         $curFontSizePt = $this->fpdf->FontSizePt;
         $color = explode(" ",$this->fpdf->TextColor);
         if(count($color)==2) {
            $curTextColor = round($color[0] * 255);
         } else if(count($color)==4) {
            $r = round($color[0] * 255);
            $g = round($color[1] * 255);
            $b = round($color[2] * 255);
            $curTextColor = "$r,$g,$b";
         } else {
            $curTextColor = 0;
         }
         $this->DefineStyle(0,$curFontFamily,$curFontStyle,$curFontSizePt,$curTextColor,0);
         $this->x = $this->fpdf->GetX();
         $this->y = $this->fpdf->GetY();
      }
   }

   function Parser($text) {
      $tab=array();
      $attrib = array();
      
      // Closing tag
      if(ereg("^(</([^>]+)>).*",$text,$regs)) {
         $type="close tag";
         $currentText=trim($regs[2]);
      } // Opening tag
      else if(ereg("^(<([^>]+)>).*",$text,$regs)) {
         //$regs[2]=ereg_replace("^a","a ",$regs[2]);
         $type="open tag";
         $currentText=trim($regs[2]);

         // Presence of attributes
         if(ereg("(.+) (.+)='(.+)' *",$regs[2])) {
            $tab1=split(" +",$regs[2]);
            $currentText=trim($tab1[0]);
            while(list($i,$couple)=each($tab1)) {
               if($i>0) {
                  $tab2=explode("=",$couple);
                  $tab2[0]=trim($tab2[0]);
                  $tab2[1]=trim($tab2[1]);
                  $end=strlen($tab2[1])-2;
                  $attrib[$tab2[0]]=substr($tab2[1],1,$end);
               }
            }
         }
      }
      // Space
      else if(ereg("^( ).*",$text,$regs)) {
         $type="space";
         $currentText=$regs[1];
      }
      // New Line
      else if(ereg("^(\n).*",$text,$regs)) {
         $type="new line";
         $currentText=$regs[1];
      }
      // Text
      else if(ereg("^([^< ]+).*",$text,$regs)) {
         $type="text";
         $currentText=trim($regs[1]);
      }
      // Pruning
      $begin=strlen($regs[1]);
      $end=strlen($text);
      $text=substr($text, $begin, $end);
      $remainder=$text;

      return array($type,$currentText,$remainder,$attrib);
   }
   
   function ParseLine() {
      $text = $this->text;
      $i = 0;
      $stack = array();
      $stack[0]=0;
      $this->styles[0] = $stack[0];
      $this->len = 0;
      while($text!="") {
         list($type,$currentText,$remainder,$attrib) = $this->Parser($text);
         $text = $remainder;
         switch($type) {
            case "open tag":
               array_unshift($stack,$currentText);
               $this->styles[$i+1] = $currentText;
               $this->attrib[$i+1] = $attrib;
               if(isset($this->style_def[$currentText])) {
                  $this->SetStyle($currentText);
               }
               break;
            case "close tag":
               $shifted = array_shift($stack);
               if(isset($stack[0])) {
                  $tag = $stack[0];
                  $this->styles[$i+1] = $tag;
                  if(isset($this->style_def[$tag])) {
                     $this->SetStyle($tag);
                  }
               }
               break;
            case "new line":
               break;
            case "space":
            case "text":
               $i++;
               $this->words[$i] = $currentText;
               $this->len += $this->fpdf->GetStringWidth($currentText);
               break;
            default:
               break;
         }
      }
      // setup align
      switch($this->align) {
         case "R":
            $d = $this->l - $this->len;
            $this->x += $d;
            break;
         case "C":
            $d = ($this->l - $this->len)/2;
            $this->x += $d;
            break;
         case "L":
         default:
            break;
      }
   }
   
   function printText() {
      $this->fpdf->SetXY($this->x,$this->y);
      for($i=0;$i<=count($this->words);$i++) {
         // set the style
         if(isset($this->styles[$i])) {
            $this->SetStyle($this->styles[$i]);
            //if(isset($this->attrib[$i])&&count($this->attrib[$i])>0) {
            //}
         }
         // out the text
         if(isset($this->words[$i])) {
            $this->fpdf->Write($this->h,$this->words[$i]);
         }
      }
      // return to original style
      $this->SetStyle(0);
      $this->LineBreak();
   }
   
   function LineBreak() {
      $this->fpdf->SetXY($this->x,$this->y+$this->h);
   }

}

} // FPDF_WRITETAG_DEFINED
?>