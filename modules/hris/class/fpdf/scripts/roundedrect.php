<?php
//--------------------------------------------------------------------//
// Filename : class/pdf/fpdf/scripts/roundedrect.php                  //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-02-22                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('FPDF_ROUNDEDRECT_DEFINED') ) {
   define('FPDF_ROUNDEDRECT_DEFINED', TRUE);

class _fpdf_RoundedRect {
   var $fpdf;
   
   function _fpdf_RoundedRect(&$fpdf) {
      $this->fpdf = &$fpdf;
   }
   
   function RoundedRect($x, $y, $w, $h, $r, $style = '') {
      $k = $this->fpdf->k;
      $hp = $this->fpdf->h;
      if($style=='F')
         $op='f';
      elseif($style=='FD' or $style=='DF')
         $op='B';
      else
         $op='S';
      $MyArc = 4/3 * (sqrt(2) - 1);
      $this->fpdf->_out(sprintf('%.2f %.2f m',($x+$r)*$k,($hp-$y)*$k ));
      $xc = $x+$w-$r ;
      $yc = $y+$r;
      $this->fpdf->_out(sprintf('%.2f %.2f l', $xc*$k,($hp-$y)*$k ));
   
      $this->_Arc($xc + $r*$MyArc, $yc - $r, $xc + $r, $yc - $r*$MyArc, $xc + $r, $yc);
      $xc = $x+$w-$r ;
      $yc = $y+$h-$r;
      $this->fpdf->_out(sprintf('%.2f %.2f l',($x+$w)*$k,($hp-$yc)*$k));
      $this->_Arc($xc + $r, $yc + $r*$MyArc, $xc + $r*$MyArc, $yc + $r, $xc, $yc + $r);
      $xc = $x+$r ;
      $yc = $y+$h-$r;
      $this->fpdf->_out(sprintf('%.2f %.2f l',$xc*$k,($hp-($y+$h))*$k));
      $this->_Arc($xc - $r*$MyArc, $yc + $r, $xc - $r, $yc + $r*$MyArc, $xc - $r, $yc);
      $xc = $x+$r ;
      $yc = $y+$r;
      $this->fpdf->_out(sprintf('%.2f %.2f l',($x)*$k,($hp-$yc)*$k ));
      $this->_Arc($xc - $r, $yc - $r*$MyArc, $xc - $r*$MyArc, $yc - $r, $xc, $yc - $r);
      $this->fpdf->_out($op);
   }
   
   function _Arc($x1, $y1, $x2, $y2, $x3, $y3)
   {
      $h = $this->fpdf->h;
      $this->fpdf->_out(sprintf('%.2f %.2f %.2f %.2f %.2f %.2f c ', $x1*$this->fpdf->k, ($h-$y1)*$this->fpdf->k,
                     $x2*$this->fpdf->k, ($h-$y2)*$this->fpdf->k, $x3*$this->fpdf->k, ($h-$y3)*$this->fpdf->k));
   }
   

}

} // FPDF_ROUNDEDRECT_DEFINED
?>