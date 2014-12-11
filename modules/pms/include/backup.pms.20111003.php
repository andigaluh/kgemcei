<?php
if ( !defined('HRIS_INCLUDEPMS_DEFINED') ) {
   define('HRIS_INCLUDEPMS_DEFINED', TRUE);

global $pms_ach_range;

$pms_ach_range = array(0,82,90,100,120);

global $allow_deploy,$allow_add_objective,$allow_add_initiative;
global $allow_actionplan;

$allow_add_objective[1] = 1;
$allow_add_objective[2] = 0;
$allow_add_objective[3] = 0;
$allow_add_objective[4] = 0;
$allow_add_objective[5] = 0;

$allow_add_initiative[1] = 1;
$allow_add_initiative[2] = 1;
$allow_add_initiative[3] = 1;
$allow_add_initiative[4] = 0;
$allow_add_initiative[5] = 0;

$allow_actionplan[1] = 0;
$allow_actionplan[2] = 0;
$allow_actionplan[3] = 0;
$allow_actionplan[4] = 1;
$allow_actionplan[5] = 0;

   function translate_norm($val) {
      
      if($val>=115) return 100;
      if($val>=114) return 99;
      if($val>=113) return 98;
      if($val>=112) return 97;
      if($val>=111) return 96;
      if($val>=110) return 95;
      if($val>=109) return 94;
      if($val>=108) return 93;
      if($val>=107) return 92;
      if($val>=106) return 91;
      if($val>=105) return 90;
      if($val>=104) return 89;
      if($val>=104) return 88;
      if($val>=103) return 87;
      if($val>=103) return 86;
      if($val>=102) return 85;
      if($val>=102) return 84;
      if($val>=102) return 83;
      if($val>=101) return 82;
      if($val>=101) return 81;
      if($val>=101) return 80;
      if($val>=100) return 79;
      if($val>=99) return 78;
      if($val>=98) return 77;
      if($val>=97) return 76;
      if($val>=96) return 75;
      if($val>=95) return 74;
      if($val>=94) return 73;
      if($val>=93) return 72;
      if($val>=92) return 71;
      if($val>=91) return 70;
      if($val>=90) return 69;
      if($val>=89) return 68;
      if($val>=88) return 67;
      if($val>=87) return 65;
      if($val>=86) return 64;
      if($val>=85) return 63;
      if($val>=84) return 62;
      if($val>=83) return 60;
      if($val>=82) return 59;
      if($val>=81) return 58;
      if($val>=80) return 57;
      if($val>=79) return 56;
      if($val>=78) return 55;
      if($val>=77) return 54;
      if($val>=76) return 53;
      if($val>=75) return 52;
      
      return 52;
      
   }   
   



} // HRIS_INCLUDEPMS_DEFINED
?>