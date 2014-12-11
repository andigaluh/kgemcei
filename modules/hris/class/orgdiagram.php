<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/diagram.php                          //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2009-07-02                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_CLASSDIAGRAM_DEFINED') ) {
   define('HRIS_CLASSDIAGRAM_DEFINED', TRUE);

define("_BOX_W_RATIO",6.5);
define("_BOX_W_MIN",60);

function cmp_org($a,$b) {
   //_debuglog($a->org_nm." : ".$a->order_no);
   if($a->order_no==$b->order_no) return 0;
   return ($a->order_no > $b->order_no? 1 : -1);
}

class _Node {
	public $id = 0;
	public $h = 0;
	public $w = 0;
	public $x = 0;
	public $y = 0;
	public $message;
	public $title;
	public $pid = 0;

	public function __construct($id, $pid, $w, $h, $title = '', $message = '', $x = 0, $y = 0) {
		$this->id = $id;
		$this->pid = $pid;
		$this->w = $w;
		$this->h = $h;
		$this->title = $title;
		$this->message = $message;
		$this->x = $x;
		$this->y = $y;
	}
}


/*

$matrix[0][0] = array($org_id);

x
      o
x  ---|---
  o o o o o   -> mode normal with sibling
x  -|-
   o-o        -> mode 3 with sibling
    o
x   |
   o-o        -> mode vertical 2 with sibling
   o-
    o
x   |
    o        -> normal without sibling
x   |
    -o
    -o
    -o
    o
*/



class DiagramX {
   var $org_class;
   var $orgs;
   var $font;
   var $font_size = 8;
   var $font_angle = 0;
   var $focus_org_id;
   var $width;
   var $height;
   var $matrix;
   var $levels;
   var $level_dimension;
   var $ocl;    //// hold org class level rendering point
   var $org_with_child;
   var $padding_top = 10;
   var $padding_left = 5;
   var $padding_bottom = 10;
   var $padding_right = 5;
   var $box_padding = 2;
   var $separator_gap = 10;
   var $margin = 3;
   var $margin_vertical = 5;
   var $im = NULL;
   var $left;
   var $canvas_left = 0;
   var $color = array(
         "im_bgcolor"=>       array(255,255,255),
         "im_focustitle"=>    array(0,0,0),
         "im_focusbgcolor"=>  array(0xee,0xee,0xee),
         "im_border"=>        array(100,100,100),
         "im_focusborder"=>   array(50,50,50),
         "im_focusoutsideborder"=>   array(150,150,150),
         "im_connector"=>     array(100,100,240),
         "im_separator"=>     array(110,110,110),
         "im_title"=>         array(0,0,0),
         "im_subtitle"=>      array(0,0,0)
   );
   var $file;
   var $top_level_org = 0;
   var $nm_h = 0;
   var $offset = 0;
   
   
   function __construct($focus_org_id=NULL) {
      $this->font = XOCP_DOC_ROOT."/include/fonts/LucidaSansRegular.ttf";
      $this->width = 400;
      $this->height = 1400;
      $this->matrix = array();
      $this->levels = array();
      $this->level_dimension = array();
      $this->ocl = array();
      $this->file = XOCP_DOC_ROOT."/tmp/orgstruct_${focus_org_id}.png";
      $this->load_org_class();
      $this->load_orgs();
      if($focus_org_id>0) {
         $this->set_focus_org($focus_org_id);
      } else {
         $this->set_focus_org(1);
      }
      //$this->set_focus_org(104);
   }
   
   function set_focus_org($org_id) {
      $this->focus_org_id = $org_id;
   }
   
   function get_focus_org() {
      if($this->focus_org_id>0&&isset($this->orgs[$this->focus_org_id])) {
         return $this->focus_org_id;
      } else {
         return $this->top_level_org;
         foreach($this->orgs as $org_id=>$v) {
            if($v->parent_id==0) {
               return $org_id;
            }
         }
         return 0;
      }
   }
   
   function load_org_class() {
      $db=&Database::getInstance();
      $sql = "SELECT org_class_id,org_class_nm,order_no"
           . " FROM ".XOCP_PREFIX."org_class"
           . " WHERE status_cd = 'normal'"
           . " ORDER BY order_no";
      $result = $db->query($sql);
      $i = 0;
      $this->org_class = array();
      if($db->getRowsNum($result)>0) {
         while(list($org_class_id,$org_class_nm,$order_no)=$db->fetchRow($result)) {
            $this->org_class[$org_class_id] = new OrgClassNode($org_class_id,$org_class_nm,$order_no);
         }
      }
      
   }
   
   function load_orgs() {
      $db=&Database::getInstance();
      $sql = "SELECT a.org_id,a.org_nm,a.org_abbr,a.parent_id,a.org_class_id,a.order_no,b.order_no"
           . " FROM ".XOCP_PREFIX."orgs a"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . " WHERE a.status_cd = 'normal'"
           . " ORDER BY b.order_no,a.order_no";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($org_id,$org_nm,
                    $org_abbr,
                    $parent_id,
                    $org_class_id,
                    $org_order_no,$org_class_order_no)=$db->fetchRow($result)) {
            if($org_id==2) continue;
            $this->orgs[$org_id] = new OrgNode($org_id,$org_nm,$org_abbr,$parent_id,$org_class_id,$org_order_no,$org_class_order_no);
            if($parent_id==0) {
               $this->top_level_org = $org_id;
            }
         }
      }
   }
   
   function get_max_width() {
      /// org class first:
      return max(300,$this->width+$this->canvas_left+$this->margin);
   }
   
   function get_max_height() {
      $h = $this->margin_vertical;
      if(is_array($this->level_dimension)) {
         foreach($this->level_dimension as $level=>$v) {
            list($org_class_level,$level_top,$level_height)=$v;
            foreach($this->levels[$level] as $org_class_id=>$m) {
               if($this->org_class[$org_class_id]->rendered) {
                  $h+=$this->org_class[$org_class_id]->height+$this->separator_gap;
                  $rendered ++;
               }
            }
            $h+=$this->separator_gap;
         }
      }
      return max($h,50);
   }
   
   function flag_render($org_id) {
      if($org_id==2) return;
      $j =& $this->orgs[$org_id];
      $j->rendered = TRUE;
      $this->org_class[$j->org_class_id]->rendered = TRUE;
   }
   
   function flag_render_sibling($org_id) {
      $j =& $this->orgs[$org_id];
      foreach($j->sibling as $org_idx=>$org_nmx) {
         $this->flag_render($org_idx);
      }
   }
   
   function flag_render_parent($org_id) {
      if($this->orgs[$org_id]->parent_id>0) {
         $this->flag_render($this->orgs[$org_id]->parent_id);
         $this->flag_render_sibling($this->orgs[$org_id]->parent_id);
         $this->flag_render_parent($this->orgs[$org_id]->parent_id);
         $this->orgs[$this->orgs[$org_id]->parent_id]->render_child += 1;
      }
   }
   
   function pass0() { /// flag all box that will get rendered
      $db=&Database::getInstance();
      $focus_org_id = $this->get_focus_org();
      $this->flag_render($focus_org_id);
      
      //// GET DIRECT SUBORDINATE
      $sql = "SELECT org_id FROM ".XOCP_PREFIX."orgs WHERE parent_id = '$focus_org_id' AND status_cd = 'normal' ORDER BY order_no";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($org_idx)=$db->fetchRow($result)) {
            if(isset($this->orgs[$org_idx])) {
               $this->flag_render($org_idx);
               $this->orgs[$focus_org_id]->render_child += 1;
            }
         }
      }
      
      $this->flag_render_sibling($focus_org_id);
      
      $this->flag_render_parent($focus_org_id);
   }
   
   function pass1() {
      $max_h = 0;
      $max_w = 0;
      foreach($this->org_class as $org_class_id=>$v) {
         if(!$v->rendered) continue;
         $org_class_nm = $v->org_class_nm;
         $bbox = imageftbbox($this->font_size, $this->font_angle, $this->font, $org_class_nm);
         $w = $bbox[2] - $bbox[0];
         $h = $bbox[1] - $bbox[5];
         $max_h = max($max_h,$h);
         $max_w = max($max_w,$w+(2*$this->box_padding));
      }
      
      $this->canvas_left = $this->margin+$max_w+$this->margin+$this->margin;
      //$this->org_class[$org_class_id]->canvas_left = $this->canvas_left;
      
      foreach($this->org_class as $org_class_id=>$v) {
         if(!$v->rendered) continue;
         $org_class_nm = $v->org_class_nm;
         $org_class_level = $v->org_class_level;
         
         if(!isset($this->ocl[$org_class_level])) {
            $this->ocl[$org_class_level] = new OrgClassLevel($org_class_level);
            $this->ocl[$org_class_level]->set_canvas_left($this->canvas_left+(2*$this->margin));
         }
         
         if(!is_array($this->levels[$org_class_level])) {
            $this->levels[$org_class_level] = array();
         }
         $bbox = imageftbbox($this->font_size, $this->font_angle, $this->font, $org_class_nm);
         $w = $max_w;
         $h = $max_h;
         $y = ($h/2);
         $this->levels[$org_class_level][$org_class_id] = array($org_class_nm,$w,$h,$y);
      }
   }
   
   function pass1b() {
      $prev_ocl = 0;
      ksort($this->ocl);
      foreach($this->ocl as $org_class_level=>$ocl) {
         if($prev_ocl>0) {
            $this->ocl[$prev_ocl]->next_ocl = $org_class_level;
         }
         $this->ocl[$org_class_level]->previous_ocl = $prev_ocl;
         $prev_ocl = $org_class_level;
         foreach($this->orgs as $org_id=>$orgs) {
            if($orgs->rendered) {
               if($orgs->org_class_level==$org_class_level) {
                  $this->ocl[$org_class_level]->orgs[$org_id] = $orgs->org_nm;
               }  
            }
         }
      }
   }
   
   function pass2() { /// name layout (long name into 2-3 lines)
      $arr_common = array(
      "Director",
      //"Division Manager",
      "General Manager",
      "Division Manager",
      "Ass. Sect",
      "Assistant Sect. Manager",
      "Section Manager",
      "Sect. Manager",
      "Manager",
      "Supervisor",
      "Officer",
      "Shift Leader",
      "Foreman",
      "Group Leader",
      "Secretary",
      "Sr. Clerk",
      "Clerk & Secr.",
      "Senior Clerk",
      "Operator",
      "Clerk",
      "Technician",
      "Analyst",
      "Specialist",
      "Worker",
      "Inspector",
      "&"
      );
      foreach($this->orgs as $org_id=>$org) {
         if(1) { ///$org->rendered) {
            $org_nm = trim($org->org_nm);
            $first = $second = "";
            foreach($arr_common as $i=>$common_nm) {
               $pos = stripos($org_nm,$common_nm);
               if($pos!==FALSE) {
                  $first = trim(substr($org_nm,0,$pos));
                  $second = trim(substr($org_nm,$pos));
                  break;
               }
            }
            
            if($first=="") {
               $first = $org_nm;
            }
            $len = strlen($first);
            $nl = 0;
            if($len>=17) {
               $fn = explode(" ",$first);
               $first_first = "";
               $first_second = "";
               foreach($fn as $k=>$fnx) {
                  if($nl==0) {
                     $nl+=strlen($fnx)+1;
                     $first_first .= "$fnx ";
                  } else {
                     $nl+=strlen($fnx)+1;
                     if($nl<20) {
                        $first_first .= "$fnx ";
                     } else {
                        $first_second .= "$fnx ";
                     }
                  }
               }
               $first_first = trim($first_first);
               $first_second = trim($first_second);
               if($first_second!=""&&$first_first!="") {
                  $first = "$first_first\n$first_second";
               } else if($first_second=="") {
                  $first = $first_first;
               } else {
                  $first = $first_second;
               }
            }
            if($second=="") {
               $new_layout = $first;
            } else {
               $new_layout = "$first\n$second";
            }
            $this->orgs[$org_id]->org_nm_layout = explode("\n",$new_layout);
         }
      }
   }
   
   function pass3() { /// calculate box height/width each org
      $max_h = 0;
      foreach($this->orgs as $org_id=>$org) {
         if(is_array($org->org_nm_layout)) {
            foreach($org->org_nm_layout as $k=>$org_nmx) {
               $bbox = imageftbbox($this->font_size, $this->font_angle, $this->font, $org_nmx);
               $wx = $bbox[2] - $bbox[0];
               $hx = $bbox[1] - $bbox[5];
               $max_h = max($max_h,$hx);
               $this->orgs[$org_id]->width = max($this->orgs[$org_id]->width,$wx+(2*$this->box_padding));
            }
         }
      }
      
      $this->nm_h = $max_h;
      
      foreach($this->orgs as $org_id=>$org) {
         $this->orgs[$org_id]->height = (3*$max_h)+(2*$this->box_padding);
         $this->orgs[$org_id]->width = max($this->orgs[$org_id]->height*2,$this->orgs[$org_id]->width);
      }
   }
   
   function calcBoxHeight($org_class_id) { /// calculate height each org class for rendered layout
      $h = 0;
      foreach($this->orgs as $org_id=>$org) {
         if($org->org_class_id==$org_class_id) {
            $h = max($org->height,$h);
         }
      }
      return $h;
   }
   
   
   function pass4($org_id=0) {
      if($org_id==0) {
         $org_id = 1;
      }
      $canvas_left = 0;
      $org_level_top = 0;
      $child_width = 0;
      $render_mode = 0; //(count($this->orgs[$org_id]->child)>4?1:0);
      $old_org_class_id = 0;
      $old_width = 0;
      if($this->orgs[$org_id]->render_child>0) {
         foreach($this->orgs[$org_id]->child as $org_idx=>$org_nmx) {
            if($this->orgs[$org_idx]->rendered) {
               switch($render_mode) {
                  case 1:
                     $this->orgs[$org_idx]->render_mode = 1;
                     break;
                  case 0:
                     if($old_org_class_id!=0&&$old_org_class_id!=$this->orgs[$org_idx]->org_class_id) {
                        $canvas_left -= ($this->orgs[$org_idx]->width/2)-$this->margin;
                     }
                     $this->orgs[$org_idx]->center_x = $canvas_left + $this->margin + ($this->orgs[$org_idx]->width/2);
                     $this->orgs[$org_idx]->center_y = $org_level_top + $this->margin + ($this->orgs[$org_idx]->height/2);
                     $this->orgs[$org_idx]->left = $canvas_left + $this->margin;
                     $this->orgs[$org_idx]->top = $org_level_top;
                     $canvas_left += $this->margin + $this->orgs[$org_idx]->width + $this->margin;
                     $old_width = $this->orgs[$org_idx]->width;
                  default:
                     break;
               }
               $old_org_class_id = $this->orgs[$org_idx]->org_class_id;
            }
         }
         $this->orgs[$org_id]->child_width = $canvas_left;
         foreach($this->orgs[$org_id]->child as $org_idx=>$org_nmx) {
            if($this->orgs[$org_idx]->rendered) {
               if($this->orgs[$org_idx]->render_child>0) {
                  $child_width = $this->pass4($org_idx);
               }
            }
         }
      }
      if($this->orgs[$org_id]->center_x==0) {
         $this->orgs[$org_id]->center_x = $this->margin + ($this->orgs[$org_id]->width/2);
         $this->orgs[$org_id]->center_y = $this->margin + ($this->orgs[$org_id]->height/2);
         $this->orgs[$org_id]->left = $this->margin;
      }
      $x_width = max($canvas_left,(2*$this->margin)+$this->orgs[$org_id]->width,$child_width);
      $this->width = $x_width;
      return $x_width;
   }
   
   function pass5($org_id=0) {
      if($org_id==0) {
         $org_id = $this->top_level_org;
      }
      
      if($this->orgs[$org_id]->rendered) {
         
         $parent_id = $this->orgs[$org_id]->parent_id;
         if($parent_id==0) {
            $current_width = $this->orgs[$org_id]->width;
         } else {
            $current_width = $this->orgs[$parent_id]->child_width;
         }
         $offset = ($this->width-$current_width)/2;
         $this->orgs[$org_id]->offset = $offset;
         if($this->orgs[$org_id]->render_child>0) {
            foreach($this->orgs[$org_id]->child as $org_idx=>$org_nmx) {
               $this->pass5($org_idx);
            }
         }
      }
      
   }
   
   function pass6($org_id=0) {
      if($org_id==0) {
         $org_id = $this->top_level_org;
      }
      
      $parent_id = $this->orgs[$org_id]->parent_id;
      // $offset = ($this->width-$this->orgs[$parent_id]->child_width)/2; 
      $offset = ($this->width/2)-$this->orgs[$org_id]->center_x;
      if($this->orgs[$org_id]->render_child>0) {
         if(isset($this->orgs[$parent_id])) {
            foreach($this->orgs[$parent_id]->child as $org_idx=>$v) {
               $this->orgs[$org_idx]->offset = $offset;
            }
         }
         $this->orgs[$org_id]->offset = $offset;
         $offset = ($this->width-$this->orgs[$parent_id]->child_width)/2; 
      } else {
      
      }
      
      if($this->orgs[$org_id]->render_child>0) {
         foreach($this->orgs[$org_id]->child as $org_idx=>$org_nmx) { /// recurse children
            if($this->orgs[$org_idx]->rendered) {
               $this->pass6($org_idx);
            }
         }
      }
   }
   
   function pass7() {
      $min_left = 100000;
      foreach($this->orgs as $org_id=>$orgs) {
         if($orgs->rendered) {
            $left = $orgs->left+$this->canvas_left+$orgs->offset+$this->offset;
            $min_left = min($min_left,$left);
         }
      }
      $this->offset = -($min_left-$this->canvas_left);
      foreach($this->orgs as $org_id=>$orgs) {
         if($orgs->rendered) {
            $left = $orgs->left+$this->canvas_left+$orgs->offset;
            if($left<$this->canvas_left) {
               $this->offset = max($this->offset,$this->canvas_left-$left);
            }
         }
      }
      
      $max_w = 0;
      foreach($this->orgs as $org_id=>$orgs) {
         if($orgs->rendered) {
            $left = $orgs->left+$orgs->offset+$this->offset;
            $width = $orgs->width;
            $max_w = max($max_w,$left+$width+$this->margin);
         }
      }
      $this->width = max($this->width,$max_w);
   }
   
   function pass10() {
      ksort($this->levels);
      $level_top = $this->margin_vertical;
      foreach($this->levels as $org_class_level=>$v) {
         $cl = $level_top;
         $orgclass_top = 0;
         $level_height = 0;
         foreach($v as $org_class_id=>$x) {
            if($this->org_class[$org_class_id]->rendered==FALSE) continue;
            $orgclass_top += $this->padding_top;
            list($org_class_nm,$w,$h,$y)=$x;
            $box_height = max($h+(2*$this->box_padding),$this->calcBoxHeight($org_class_id));
            $box_width = $w+(2*$this->box_padding);
            $this->org_class[$org_class_id]->x = $this->margin;
            $this->org_class[$org_class_id]->y = $y+($box_height/2); /// y = relative to level top
            $this->org_class[$org_class_id]->width = $box_width;
            $this->org_class[$org_class_id]->height = $box_height;
            $level_height += $box_height;
         }
         $level_top += $level_height+$this->padding_top+$this->padding_bottom;
         $this->level_dimension[$org_class_level] = array($org_class_level,$level_top,$level_height);
      }
   }
   
   function get_width($org_id) {
   }
   
   function init_image() {
      $this->width = $this->get_max_width();
      $this->height = $this->get_max_height();
      $this->im = imagecreatetruecolor($this->width, $this->height);
      foreach($this->color as $k=>$v) {
         $this->allocate_color($k,$v);
      }
      imagefilledrectangle($this->im, 0, 0, $this->width, $this->height, $this->im_bgcolor);
   }
   
   function render($file="") {
      $this->pass0();
      $this->pass1();
      $this->pass1b();
      $this->pass2();
      $this->pass3();
      $this->pass4();
      $this->pass5();
      $this->pass6();
      $this->pass7();
      $this->pass10();
      $this->init_image();
      /// render org_class
      $clevel = 0;
      $top = $this->margin_vertical;
      $level_top = $top;
      $left = $this->margin;
      imagesetstyle($this->im,array($this->im_separator,$this->im_bgcolor,$this->im_bgcolor,$this->im_bgcolor));
      $separator_y = $top;
      imageline($this->im,$left,$top,$this->width,$top,IMG_COLOR_STYLED);
      $top += $this->separator_gap;
      if(is_array($this->level_dimension)) {
         foreach($this->level_dimension as $org_class_levelx=>$v) {
            list($org_class_level,$level_top,$level_height)=$v;
            $rendered = 0;
            $this->ocl[$org_class_level]->separator_y = $separator_y;
            
            foreach($this->levels[$org_class_level] as $org_class_id=>$m) {
               list($org_class_nm,$org_class_w,$org_class_h,$org_class_y)=$m;
               if($this->org_class[$org_class_id]->rendered) {
                  $org_class_w = $this->org_class[$org_class_id]->width;
                  $org_class_h = $this->org_class[$org_class_id]->height;
                  $org_class_y = $this->org_class[$org_class_id]->y;
                  imagefttext($this->im, $this->font_size, $this->font_angle, $left+$this->padding_left, $top+$org_class_y, $this->im_title, $this->font, $org_class_nm);
                  //imagerectangle($this->im,$left,$top,$left+$org_class_w,$top+$org_class_h,$this->im_border);
                  $this->org_class[$org_class_id]->top = $top;
                  $rendered ++;
                  $top += $org_class_h;
                  $top += $this->separator_gap;
                  $separator_y = $top;
               }
            }
            if($rendered>0) {
               imageline($this->im,$left,$top,$this->width,$top,IMG_COLOR_STYLED);
               $top += $this->separator_gap;
            }
         }
      }
      
      
      foreach($this->orgs as $org_id=>$orgs) {
         if($orgs->rendered) {
            $top = $orgs->top+$this->org_class[$orgs->org_class_id]->top;
            $left = $orgs->left+$this->canvas_left+$orgs->offset+$this->offset;
            $width = $orgs->width;
            $height = $orgs->height;
            if($this->focus_org_id==$org_id) {
               imagefilledrectangle($this->im,$left,$top,$left+$width+$this->box_padding,$top+$height,$this->im_focusbgcolor);
               imagerectangle($this->im,$left,$top,$left+$width+$this->box_padding,$top+$height,$this->im_focusborder);
               imagerectangle($this->im,$left-1,$top-1,$left+$width+$this->box_padding+1,$top+$height+1,$this->im_focusoutsideborder);
            } else {
               imagerectangle($this->im,$left,$top,$left+$width+$this->box_padding,$top+$height,$this->im_border);
            }
            $_SESSION["hris_nodes"][$org_id] = new _Node($org_id,0,$width,$height,"","",$left,$top);
            $mid = $left+($width/2);
            if($orgs->parent_id>0) {
               imageline($this->im,$mid,$top,$mid,$this->ocl[$orgs->org_class_level]->separator_y,$this->im_connector);
               $this->orgs[$org_id]->p_parent = array($mid,$this->ocl[$orgs->org_class_level]->separator_y);
            }
            if($orgs->render_child>0) {
               imageline($this->im,$mid,$top+$height,$mid,$top+$height+$this->separator_gap,$this->im_connector);
               $this->orgs[$org_id]->p_child = array($mid,$top+$height+$this->separator_gap);
            }
            $ntop = $top;
            $cnt = count($orgs->org_nm_layout);
            $offset_h = (($height)-($cnt*($this->nm_h)))/2;
            $ntop = $top+$offset_h;
            foreach($orgs->org_nm_layout as $nmx) {
               $ntop += $this->nm_h;
               $bbox = imageftbbox($this->font_size, $this->font_angle, $this->font, $nmx);
               $wx = $bbox[2] - $bbox[0];
               $offset_x = ($width-$wx)/2;
               if($this->focus_org_id==$org_id) {
                  imagefttext($this->im, $this->font_size, $this->font_angle, $left+$this->box_padding+$offset_x, $ntop-1, $this->im_focustitle, $this->font, $nmx);
               } else {
                  imagefttext($this->im, $this->font_size, $this->font_angle, $left+$this->box_padding+$offset_x, $ntop-1, $this->im_title, $this->font, $nmx);
               }
            }
         }
      }
      
      
      foreach($this->orgs as $org_id=>$v) {
         if($this->orgs[$org_id]->rendered) {
            if($this->orgs[$org_id]->parent_id>0) {
               $cparent = $this->orgs[$this->orgs[$org_id]->parent_id]->p_child;
               $cchild = $this->orgs[$org_id]->p_parent;
               if($cparent[1]<$cchild[1]) {
                  imageline($this->im,$cchild[0],$cchild[1],$cchild[0],$cparent[1],$this->im_connector);
                  //imageline($this->im,$cparent[0],$cparent[1],$cparent[0],$cchild[1],$this->im_connector);
               }
               imageline($this->im,$cchild[0],$cparent[1],$cparent[0],$cparent[1],$this->im_connector);
               //imageline($this->im,$cparent[0],$cchild[1],$cchild[0],$cchild[1],$this->im_connector);
            }
         }
      }
      
      

      
      /*
      $focus_org = $this->get_focus_org();
      
      foreach($this->orgs as $org_id=>$v) {
         if($this->orgs[$org_id]->rendered) {
            $org_nm = $this->orgs[$org_id]->org_nm;
            $top = $this->orgs[$org_id]->top;
            $left = $this->orgs[$org_id]->left;
            $org_id = $this->orgs[$org_id]->org_id;
            $text_top = $this->orgs[$org_id]->text_top;
            $text_left = $this->orgs[$org_id]->text_left;
            $w = $this->orgs[$org_id]->width;
            $h = $this->orgs[$org_id]->height;
            $box_width = $this->orgs[$org_id]->box_width;
            $box_height = $this->orgs[$org_id]->box_height;
            $level = $this->orgs[$org_id]->org_class_level;
            list($lw,$lh,$lwttl,$lhttl,$leveltop) = $this->level_dimension[$this->orgs[$org_id]->org_class_level];
            $top = $leveltop;
            
            if($this->focus_org_id==$org_id) {
               imagefilledrectangle($this->im,$left,$top,$left+$box_width,$top+$box_height,$this->im_focusbgcolor);
               $text_left++;
               imagerectangle($this->im,$left,$top,$left+$box_width,$top+$box_height,$this->im_border);
               imagefttext($this->im, $this->font_size, $this->font_angle, $left+$text_left, $top+$text_top, $this->im_focustitle, $this->font, $org_nm);
            } else {
               imagerectangle($this->im,$left,$top,$left+$box_width,$top+$box_height,$this->im_border);
               imagefttext($this->im, $this->font_size, $this->font_angle, $left+$text_left, $top+$text_top, $this->im_title, $this->font, $org_nm);
            }
            
            
            
            
            $_SESSION["hris_nodes"][$org_id] = new _Node($org_id,0,$box_width,$box_height,"","",$left,$top);
            
            if($this->orgs[$org_id]->parent_id>0) {
               imageline($this->im,$left+($box_width/2),$top,$left+($box_width/2),$leveltop,$this->im_connector);
               $this->orgx[$org_id]->p_parent = array($left+($box_width/2),$leveltop);
            }
            if($this->orgs[$org_id]->render_child>0) {
               imageline($this->im,$left+($box_width/2),$top+$box_height,$left+($box_width/2),$leveltop+$lhttl+$this->margin_vertical,$this->im_connector);
               $this->orgs[$org_id]->p_child = array($left+($box_width/2),$leveltop+$lhttl+$this->margin_vertical);
            }
            
         }
      }
      
      foreach($this->orgs as $org_id=>$v) {
         if($this->orgs[$org_id]->rendered) {
            if($this->orgs[$org_id]->parent_id>0) {
               $cparent = $this->orgs[$this->orgs[$org_id]->parent_id]->p_child;
               $cchild = $this->orgx[$org_id]->p_parent;
               if($cparent[1]<$cchild[1]) {
                  imageline($this->im,$cchild[0],$cchild[1],$cchild[0],$cparent[1],$this->im_connector);
               }
               imageline($this->im,$cchild[0],$cparent[1],$cparent[0],$cparent[1],$this->im_connector);
            }
         }
      }
      */
      
      if (strlen($file) > 0 && is_dir(dirname($file))) {
         imagepng($this->im, $file);
      } else {
         header("Content-Type: image/png");
         imagepng($this->im);
      }
      
   }

   function allocate_color($var, $color, $alpha = true) {
      if($this->im) {
         $alpha = ($alpha ? $this->alpha : 0);
         $this->$var = imagecolorallocatealpha($this->im, $color[0], $color[1], $color[2], $alpha);
      }
   }
}

class OrgClassLevel {
   var $org_class_level;
   var $save_canvas_left = 0;
   var $canvas_left = 0;
   var $offset = 0;
   var $width = 0;
   var $separator_y = 0;
   
   function __construct($org_class_level) {
      $this->org_class_level = $org_class_level;
   }
   
   function set_canvas_left($left) {
      $this->save_canvas_left = $left;
      $this->canvas_left = $left;
   }
   
   function add_width($width) {
      $this->canvas_left += $width;
      $this->width += $width;
   }
   
   function set_width($width) {
   
   }
   
}

class OrgClassNode {
   var $org_class_id;
   var $org_class_nm;
   var $order_no;
   var $org_class_level;
   var $x;
   var $y;
   var $top;
   var $left;
   var $width;
   var $height;
   var $orgs;
   var $rendered = FALSE;
   var $canvas_left = 0;
   
   function __construct($org_class_id,$org_class_nm,$order_no) {
      $db=&Database::getInstance();
      $this->org_class_id = $org_class_id;
      $this->org_class_nm = $org_class_nm;
      $this->order_no = $order_no;
      $this->org_class_level = $order_no;
      
      /// orgs
      $this->orgs = array();
      $sql = "SELECT org_id,org_nm FROM ".XOCP_PREFIX."orgs WHERE org_class_id = '$org_class_id' AND status_cd = 'normal' ORDER BY order_no";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($org_id,$org_nm)=$db->fetchRow($result)) {
            $this->orgs[$org_id] = $org_nm;
         }
      }
   }
   
}

class JobWithChild {
   var $org_id;
   var $child;
   var $render_t;
   var $child_offset;
   
   function __construct($org_id,$child) {
      $this->org_id = $org_id;
      $this->child = $child;
      $this->child_offset = 0;
      if(is_array($this->child)&&count($this->child)>4) {
         $this->render_t = 1;
      } else {
         $this->render_t = 0;
      }
   }
   
}

class OrgNode {
   var $org_id;
   var $org_nm;
   var $org_nm_layout;
   var $org_abbr;
   var $org_class_id;
   var $org_class_nm;
   var $org_class_order_no;
   var $org_order_no;
   var $org_class_level;
   var $parent_id;
   var $child;
   var $sibling;
   var $x;
   var $y;
   var $top;
   var $left;
   var $width;
   var $height;
   var $anchor_top;
   var $anchor_bottom;
   var $anchor_left;
   var $anchor_right;
   var $pass = 0;
   var $rendered = FALSE;
   var $box_width;
   var $box_height;
   var $text_top;
   var $text_left;
   var $rleft;
   var $render_child = 0;
   var $p_child = array();
   var $p_parent = array();
   var $render_mode = 0;
   var $order_org = 0;
   
   var $child_offset = 0;
   var $child_width = 0;
   var $center_x = 0;
   var $center_y = 0;
   var $canvas_left = 0;
   var $offset = 0;
   
   function __construct($org_id,$org_nm,$org_abbr,$parent_id,$org_class_id,$org_order_no,$org_class_order_no) {
      $db=&Database::getInstance();
      $this->org_id = $org_id;
      $this->org_nm = $org_nm;
      $this->org_abbr = $org_abbr;
      $this->parent_id = $parent_id;
      $this->org_class_id = $org_class_id;
      $this->org_order_no = $org_order_no;
      $this->org_class_order_no = $org_class_order_no;
      $this->org_class_level = $org_class_order_no;
      $this->width = 0;
      $this->height = 0;
      $this->order_no = $org_order_no;
         
      //// org_class
      $sql = "SELECT org_class_nm"
           . " FROM ".XOCP_PREFIX."org_class"
           . " WHERE org_class_id = '".$this->org_class_id."' AND status_cd = 'normal'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($this->org_class_nm)=$db->fetchRow($result);
      }
      
      //// child
      $this->child = array();
      $sql = "SELECT a.org_id,a.org_nm FROM ".XOCP_PREFIX."orgs a"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . " LEFT JOIN ".XOCP_PREFIX."orgs c ON c.org_id = a.org_id"
           . " WHERE a.parent_id = '".$this->org_id."'"
           . " AND a.status_cd = 'normal'"
           . " ORDER BY b.order_no,b.org_class_id,c.order_no";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($org_idx,$org_nmx)=$db->fetchRow($result)) {
            $this->child[$org_idx] = $org_nmx;
         }
      }
      
      //// sibling
      $this->sibling = array();
      $sql = "SELECT a.org_id,a.org_nm FROM ".XOCP_PREFIX."orgs a"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . " WHERE a.parent_id = '".$this->parent_id."' AND b.org_class_id = '".$this->org_class_id."' AND a.status_cd = 'normal'"
           . " ORDER BY b.order_no,b.org_class_id,a.order_no";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($org_idx,$org_nmx)=$db->fetchRow($result)) {
            if($org_idx==$org_id) continue;
            if($org_id==2) continue;
            $this->sibling[$org_idx] = $org_nmx;
         }
      }
   }
}

} /// HRIS_CLASSDIAGRAM_DEFINED
?>