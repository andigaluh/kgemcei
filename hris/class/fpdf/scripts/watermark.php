<?php
//--------------------------------------------------------------------//
// Filename : class/pdf/fpdf/scripts/watermark.php                    //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-02-22                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('FPDF_WATERMARK_DEFINED') ) {
   define('FPDF_WATERMARK_DEFINED', TRUE);

class _fpdf_Watermark {
   var $fpdf;
   
   function _fpdf_Watermark(&$fpdf) {
      $this->fpdf = &$fpdf;
   }

   function addWatermark( $text )
   {
      $this->fpdf->SetFont('Arial','B',50);
      $this->fpdf->SetTextColor(255,223,223);
      $l = $this->fpdf->GetStringWidth($text);
      $w = 0.7 * $l;
      $h1 = 80;
      $h2 = $this->fpdf->h - 40;
      $x1 = ($this->fpdf->w - $w)/2;
      $y1 = $h1 + (($h2-$h1+$w)/2);
      $this->Rotate(45,$x1,$y1);
      $this->fpdf->Text($x1,$y1,$text);
      $this->Rotate(0);
      $this->fpdf->SetTextColor(0,0,0);
   }
   
   
   function Rotate($angle,$x=-1,$y=-1)
   {
      if($x==-1)
         $x=$this->fpdf->x;
      if($y==-1)
         $y=$this->fpdf->y;
      if($this->fpdf->angle!=0)
         $this->fpdf->_out('Q');
      $this->fpdf->angle=$angle;
      if($angle!=0)
      {
         $angle*=M_PI/180;
         $c=cos($angle);
         $s=sin($angle);
         $cx=$x*$this->fpdf->k;
         $cy=($this->fpdf->h-$y)*$this->fpdf->k;
         $this->fpdf->_out(sprintf('q %.5f %.5f %.5f %.5f %.2f %.2f cm 1 0 0 1 %.2f %.2f cm',$c,$s,-$s,$c,$cx,$cy,-$cx,-$cy));
      }
   }
   

}

} // FPDF_WATERMARK_DEFINED
?>