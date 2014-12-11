<?php
//--------------------------------------------------------------------//
// Filename : class/pdf/fpdf/scripts/barcode.php                      //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-02-22                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('FPDF_BARCODE_DEFINED') ) {
   define('FPDF_BARCODE_DEFINED', TRUE);

class _fpdf_Barcode {
   var $fpdf;
   
   function _fpdf_Barcode(&$fpdf) {
      $this->fpdf = &$fpdf;
   }
   
   function Code39($xpos, $ypos, $code, $baseline=0.5, $height=5,$out_text=TRUE,$align="L") {

      $wide = $baseline;
      $narrow = $baseline / 3 ; 
      $gap = $narrow;
   
      $barChar['0'] = 'nnnwwnwnn';
      $barChar['1'] = 'wnnwnnnnw';
      $barChar['2'] = 'nnwwnnnnw';
      $barChar['3'] = 'wnwwnnnnn';
      $barChar['4'] = 'nnnwwnnnw';
      $barChar['5'] = 'wnnwwnnnn';
      $barChar['6'] = 'nnwwwnnnn';
      $barChar['7'] = 'nnnwnnwnw';
      $barChar['8'] = 'wnnwnnwnn';
      $barChar['9'] = 'nnwwnnwnn';
      $barChar['A'] = 'wnnnnwnnw';
      $barChar['B'] = 'nnwnnwnnw';
      $barChar['C'] = 'wnwnnwnnn';
      $barChar['D'] = 'nnnnwwnnw';
      $barChar['E'] = 'wnnnwwnnn';
      $barChar['F'] = 'nnwnwwnnn';
      $barChar['G'] = 'nnnnnwwnw';
      $barChar['H'] = 'wnnnnwwnn';
      $barChar['I'] = 'nnwnnwwnn';
      $barChar['J'] = 'nnnnwwwnn';
      $barChar['K'] = 'wnnnnnnww';
      $barChar['L'] = 'nnwnnnnww';
      $barChar['M'] = 'wnwnnnnwn';
      $barChar['N'] = 'nnnnwnnww';
      $barChar['O'] = 'wnnnwnnwn'; 
      $barChar['P'] = 'nnwnwnnwn';
      $barChar['Q'] = 'nnnnnnwww';
      $barChar['R'] = 'wnnnnnwwn';
      $barChar['S'] = 'nnwnnnwwn';
      $barChar['T'] = 'nnnnwnwwn';
      $barChar['U'] = 'wwnnnnnnw';
      $barChar['V'] = 'nwwnnnnnw';
      $barChar['W'] = 'wwwnnnnnn';
      $barChar['X'] = 'nwnnwnnnw';
      $barChar['Y'] = 'wwnnwnnnn';
      $barChar['Z'] = 'nwwnwnnnn';
      $barChar['-'] = 'nwnnnnwnw';
      $barChar['.'] = 'wwnnnnwnn';
      $barChar[' '] = 'nwwnnnwnn';
      $barChar['*'] = 'nwnnwnwnn';
      $barChar['$'] = 'nwnwnwnnn';
      $barChar['/'] = 'nwnwnnnwn';
      $barChar['+'] = 'nwnnnwnwn';
      $barChar['%'] = 'nnnwnwnwn';
      
      $this->fpdf->SetFillColor(0);

      if($align=="R") {
         $tmpw = 0;
         $tmpcode = '*'.strtoupper($code).'*';
         for($i=0; $i<strlen($tmpcode); $i++){
            $char = $tmpcode{$i};
            if(!isset($barChar[$char])){
               $this->fpdf->Error('Invalid character in barcode: '.$char);
            }
            $seq = $barChar[$char];
            for($bar=0; $bar<9; $bar++){
               if($seq{$bar} == 'n'){
                  $lineWidth = $narrow;
               }else{
                  $lineWidth = $wide;
               }
               $tmpw += $lineWidth;
            }
            $tmpw += $gap;
         }
         $xpos = $xpos - $tmpw;
      }

      if($out_text==TRUE) {
         $this->fpdf->SetFont('Courier','',9);
         $this->fpdf->Text($xpos, $ypos + $height + 3, $code);
      }

      $code = '*'.strtoupper($code).'*';
      for($i=0; $i<strlen($code); $i++){
         $char = $code{$i};
         if(!isset($barChar[$char])){
            $this->fpdf->Error('Invalid character in barcode: '.$char);
         }
         $seq = $barChar[$char];
         for($bar=0; $bar<9; $bar++){
            if($seq{$bar} == 'n'){
               $lineWidth = $narrow;
            }else{
               $lineWidth = $wide;
            }
            if($bar % 2 == 0){
               $this->fpdf->Rect($xpos, $ypos, $lineWidth, $height, 'F');
            }
            $xpos += $lineWidth;
         }
         $xpos += $gap;
      }
   }
   
   function code128() {
   // public function __construct($maxHeight,FColor $color1,FColor $color2,$res,$text,$textfont,$start='B') {
      //private $code;
      BarCode::__construct($maxHeight,$color1,$color2,$res);
      if($start=='A')
         $this->starting = 103;
      elseif($start=='B')
         $this->starting = 104;
      elseif($start=='C')
         $this->starting = 105;
      $this->ending = 106;
      $this->currentCode = $start;
      /* CODE 128 A */
      $this->keysA = array(' ','!','"','#','$','%','&','\'','(',')','*','+',',','-','.','/','0','1','2','3','4','5','6','7','8','9',':',';','<','=','>','?','@','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','[','\\',']','^','_','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','',chr(128),chr(129));

      /* CODE 128 B */
      $this->keysB = array(' ','!','"','#','$','%','&','\'','(',')','*','+',',','-','.','/','0','1','2','3','4','5','6','7','8','9',':',';','<','=','>','?','@','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','[','\\',']','^','_','`','a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','{','|','}','~','','','','',chr(128),'',chr(130));

      /* CODE 128 C */
      $this->keysC = array();
      for($i=0;$i<=99;$i++)
         $this->keysC[] = sprintf('%02d',$i);
      $this->keysC[] = chr(129);
      $this->keysC[] = chr(130);

      $code = array(
         '101111',   /* 00 */
         '111011',   /* 01 */
         '111110',   /* 02 */
         '010112',   /* 03 */
         '010211',   /* 04 */
         '020111',   /* 05 */
         '011102',   /* 06 */
         '011201',   /* 07 */
         '021101',   /* 08 */
         '110102',   /* 09 */
         '110201',   /* 10 */
         '120101',   /* 11 */
         '001121',   /* 12 */
         '011021',   /* 13 */
         '011120',   /* 14 */
         '002111',   /* 15 */
         '012011',   /* 16 */
         '012110',   /* 17 */
         '112100',   /* 18 */
         '110021',   /* 19 */
         '110120',   /* 20 */
         '102101',   /* 21 */
         '112001',   /* 22 */
         '201020',   /* 23 */
         '200111',   /* 24 */
         '210011',   /* 25 */
         '210110',   /* 26 */
         '201101',   /* 27 */
         '211001',   /* 28 */
         '211100',   /* 29 */
         '101012',   /* 30 */
         '101210',   /* 31 */
         '121010',   /* 32 */
         '000212',   /* 33 */
         '020012',   /* 34 */
         '020210',   /* 35 */
         '001202',   /* 36 */
         '021002',   /* 37 */
         '021200',   /* 38 */
         '100202',   /* 39 */
         '120002',   /* 40 */
         '120200',   /* 41 */
         '001022',   /* 42 */
         '001220',   /* 43 */
         '021020',   /* 44 */
         '002012',   /* 45 */
         '002210',   /* 46 */
         '022010',   /* 47 */
         '202010',   /* 48 */
         '100220',   /* 49 */
         '120020',   /* 50 */
         '102002',   /* 51 */
         '102200',   /* 52 */
         '102020',   /* 53 */
         '200012',   /* 54 */
         '200210',   /* 55 */
         '220010',   /* 56 */
         '201002',   /* 57 */
         '201200',   /* 58 */
         '221000',   /* 59 */
         '203000',   /* 60 */
         '110300',   /* 61 */
         '320000',   /* 62 */
         '000113',   /* 63 */
         '000311',   /* 64 */
         '010013',   /* 65 */
         '010310',   /* 66 */
         '030011',   /* 67 */
         '030110',   /* 68 */
         '001103',   /* 69 */
         '001301',   /* 70 */
         '011003',   /* 71 */
         '011300',   /* 72 */
         '031001',   /* 73 */
         '031100',   /* 74 */
         '130100',   /* 75 */
         '110003',   /* 76 */
         '302000',   /* 77 */
         '130001',   /* 78 */
         '023000',   /* 79 */
         '000131',   /* 80 */
         '010031',   /* 81 */
         '010130',   /* 82 */
         '003101',   /* 83 */
         '013001',   /* 84 */
         '013100',   /* 85 */
         '300101',   /* 86 */
         '310001',   /* 87 */
         '310100',   /* 88 */
         '101030',   /* 89 */
         '103010',   /* 90 */
         '301010',   /* 91 */
         '000032',   /* 92 */
         '000230',   /* 93 */
         '020030',   /* 94 */
         '003002',   /* 95 */
         '003200',   /* 96 */
         '300002',   /* 97 */
         '300200',   /* 98 */
         '002030',   /* 99 */
         '003020',   /* 100*/
         '200030',   /* 101*/
         '300020',   /* 102*/
         '100301',   /* 103*/
         '100103',   /* 104*/
         '100121',   /* 105*/
         '122000'   /*STOP*/
      );
      $this->setText($text);
      $this->textfont = $textfont;
      $this->usingCode($start);
      $this->starting_text = $start;
   
   }
   

}

} // FPDF_BARCODE_DEFINED
?>