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

function cmp_job($a,$b) {
   //_debuglog($a->job_nm." : ".$a->order_no);
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

$matrix[0][0] = array($job_id);

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
   var $job_class;
   var $jobs;
   var $font;
   var $font_size = 8;
   var $font_angle = 0;
   var $focus_job_id;
   var $width;
   var $height;
   var $matrix;
   var $levels;
   var $level_dimension;
   var $jcl;    //// hold job class level rendering point
   var $job_with_child;
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
   var $top_level_job = 0;
   var $nm_h = 0;
   var $offset = 0;
   
   
   function __construct($focus_job_id=NULL) {
      $this->font = XOCP_DOC_ROOT."/include/fonts/LucidaSansRegular.ttf";
      $this->width = 400;
      $this->height = 1400;
      $this->matrix = array();
      $this->levels = array();
      $this->level_dimension = array();
      $this->jcl = array();
      $this->file = XOCP_DOC_ROOT."/tmp/jobstruct_${focus_job_id}.png";
      $this->load_job_class();
      $this->load_jobs();
      if($focus_job_id>0) {
         $this->set_focus_job($focus_job_id);
      } else {
         $this->set_focus_job($this->top_level_job);
      }
      //$this->set_focus_job(104);
   }
   
   function set_focus_job($job_id) {
      $this->focus_job_id = $job_id;
   }
   
   function get_focus_job() {
      if($this->focus_job_id>0&&isset($this->jobs[$this->focus_job_id])) {
         return $this->focus_job_id;
      } else {
         return $this->top_level_job;
         foreach($this->jobs as $job_id=>$v) {
            if($v->upper_job_id==0) {
               return $job_id;
            }
         }
         return 0;
      }
   }
   
   function load_job_class() {
      $db=&Database::getInstance();
      $sql = "SELECT job_class_id,job_class_nm,job_class_level,job_level"
           . " FROM ".XOCP_PREFIX."job_class"
           . " WHERE status_cd = 'normal'"
           . " ORDER BY job_class_level";
      $result = $db->query($sql);
      $i = 0;
      $height = 1;
      $this->job_class = array();
      if($db->getRowsNum($result)>0) {
         while(list($job_class_id,$job_class_nm,$job_class_level,$job_level)=$db->fetchRow($result)) {
            $this->job_class[$job_class_id] = new JobClassNode($job_class_id,$job_class_nm,$job_class_level,$job_level,$i);
            $this->matrix_jobclass[$job_class_id] = array($job_class_id,$i,$height);
            $i += $height;
         }
      }
   }
   
   function load_jobs() {
      $db=&Database::getInstance();
      $sql = "SELECT a.job_id,a.job_nm,a.job_abbr,a.upper_job_id,a.job_class_id,a.org_id,d.order_no"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
           . " LEFT JOIN ".XOCP_PREFIX."orgs d ON d.org_id = a.org_id"
           . " WHERE a.status_cd = 'normal'"
           . " ORDER BY b.job_class_id,d.order_no";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($job_id,$job_nm,
                    $job_abbr,
                    $upper_job_id,
                    $job_class_id,
                    $org_id,$order_no)=$db->fetchRow($result)) {
            $this->jobs[$job_id] = new JobNode($job_id,$job_nm,$job_abbr,$upper_job_id,$job_class_id,$org_id,$order_no);
            if($upper_job_id==0) {
               $this->top_level_job = $job_id;
            }
         }
      }
   }
   
   function get_max_width() {
      /// job class first:
      return max(300,$this->width+$this->canvas_left+$this->margin);
   }
   
   function get_max_height() {
      $h = $this->margin_vertical;
      if(is_array($this->level_dimension)) {
         foreach($this->level_dimension as $level=>$v) {
            list($job_class_level,$level_top,$level_height)=$v;
            foreach($this->levels[$level] as $job_class_id=>$m) {
               if($this->job_class[$job_class_id]->rendered) {
                  $h+=$this->job_class[$job_class_id]->height+$this->separator_gap;
                  $rendered ++;
               }
            }
            $h+=$this->separator_gap;;
         }
      }
      return max($h,50);
   }
   
   function flag_render($job_id) {
      $j =& $this->jobs[$job_id];
      $j->rendered = TRUE;
      $this->job_class[$j->job_class_id]->rendered = TRUE;
   }
   
   function flag_render_sibling($job_id) {
      $j =& $this->jobs[$job_id];
      foreach($j->sibling as $job_idx=>$job_nmx) {
         $this->flag_render($job_idx);
      }
   }
   
   function flag_render_parent($job_id) {
      if($this->jobs[$job_id]->upper_job_id>0) {
         $this->flag_render($this->jobs[$job_id]->upper_job_id);
         $this->flag_render_sibling($this->jobs[$job_id]->upper_job_id);
         $this->flag_render_parent($this->jobs[$job_id]->upper_job_id);
         $this->jobs[$this->jobs[$job_id]->upper_job_id]->render_child += 1;
      }
   }
   
   function pass0() { /// flag all box that will get rendered
      $db=&Database::getInstance();
      $focus_job_id = $this->get_focus_job();
      $this->flag_render($focus_job_id);
      
      //// GET DIRECT SUBORDINATE
      $sql = "SELECT job_id FROM ".XOCP_PREFIX."jobs WHERE upper_job_id = '$focus_job_id' AND status_cd = 'normal'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($job_idx)=$db->fetchRow($result)) {
            if(isset($this->jobs[$job_idx])) {
               $this->flag_render($job_idx);
               $this->jobs[$focus_job_id]->render_child += 1;
            }
         }
      }
      
      $this->flag_render_sibling($focus_job_id);
      
      $this->flag_render_parent($focus_job_id);
      
   }
   
   function pass1() {
      $max_h = 0;
      $max_w = 0;
      foreach($this->job_class as $job_class_id=>$v) {
         if(!$v->rendered) continue;
         $job_class_nm = $v->job_class_nm;
         $bbox = imageftbbox($this->font_size, $this->font_angle, $this->font, $job_class_nm);
         $w = $bbox[2] - $bbox[0];
         $h = $bbox[1] - $bbox[5];
         $max_h = max($max_h,$h);
         $max_w = max($max_w,$w+(2*$this->box_padding));
      }
      
      $this->canvas_left = $this->margin+$max_w+$this->margin;
      $this->job_class[$job_class_id]->canvas_left = $this->canvas_left;
      
      foreach($this->job_class as $job_class_id=>$v) {
         if(!$v->rendered) continue;
         $job_class_nm = $v->job_class_nm;
         $job_class_level = $v->job_class_level;
         
         if(!isset($this->jcl[$job_class_level])) {
            $this->jcl[$job_class_level] = new JobClassLevel($job_class_level);
            $this->jcl[$job_class_level]->set_canvas_left($this->canvas_left+(2*$this->margin));
         }
         
         if(!is_array($this->levels[$job_class_level])) {
            $this->levels[$job_class_level] = array();
         }
         $bbox = imageftbbox($this->font_size, $this->font_angle, $this->font, $job_class_nm);
         $w = $max_w;
         $h = $max_h;
         $y = ($h/2);
         $this->levels[$job_class_level][$job_class_id] = array($job_class_nm,$w,$h,$y);
      }
   }
   
   function pass1b() {
      $prev_jcl = 0;
      ksort($this->jcl);
      foreach($this->jcl as $job_class_level=>$jcl) {
         if($prev_jcl>0) {
            $this->jcl[$prev_jcl]->next_jcl = $job_class_level;
         }
         $this->jcl[$job_class_level]->previous_jcl = $prev_jcl;
         $prev_jcl = $job_class_level;
         foreach($this->jobs as $job_id=>$jobs) {
            if($jobs->rendered) {
               if($jobs->job_class_level==$job_class_level) {
                  $this->jcl[$job_class_level]->jobs[$job_id] = $jobs->job_nm;
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
      foreach($this->jobs as $job_id=>$job) {
         if(1) { ///$job->rendered) {
            $job_nm = trim($job->job_nm);
            $first = $second = "";
            foreach($arr_common as $i=>$common_nm) {
               $pos = stripos($job_nm,$common_nm);
               if($pos!==FALSE) {
                  $first = trim(substr($job_nm,0,$pos));
                  $second = trim(substr($job_nm,$pos));
                  break;
               }
            }
            
            if($first=="") {
               $first = $job_nm;
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
            $this->jobs[$job_id]->job_nm_layout = explode("\n",$new_layout);
         }
      }
   }
   
   function pass3() { /// calculate box height/width each job
      $max_h = 0;
      foreach($this->jobs as $job_id=>$job) {
         if(is_array($job->job_nm_layout)) {
            foreach($job->job_nm_layout as $k=>$job_nmx) {
               $bbox = imageftbbox($this->font_size, $this->font_angle, $this->font, $job_nmx);
               $wx = $bbox[2] - $bbox[0];
               $hx = $bbox[1] - $bbox[5];
               $max_h = max($max_h,$hx);
               $this->jobs[$job_id]->width = max($this->jobs[$job_id]->width,$wx+(2*$this->box_padding));
            }
         }
      }
      
      $this->nm_h = $max_h;
      
      foreach($this->jobs as $job_id=>$job) {
         $this->jobs[$job_id]->height = (3*$max_h)+(2*$this->box_padding);
         $this->jobs[$job_id]->width = max($this->jobs[$job_id]->height*2,$this->jobs[$job_id]->width);
      }
   }
   
   function calcBoxHeight($job_class_id) { /// calculate height each job class for rendered layout
      $h = 0;
      foreach($this->jobs as $job_id=>$job) {
         if($job->job_class_id==$job_class_id) {
            $h = max($job->height,$h);
         }
      }
      return $h;
   }
   
   function pass4b($job_id=0) {
      if($job_id==0) {
         $job_id=$this->top_level_job;
      }
      if($this->jobs[$job_id]->rendered) {
         foreach($this->jobs[$job_id]->child as $job_idx=>$jo) {
            $this->pass4($job_idx);
         }
         $job_class_level = $this->jobs[$job_id]->job_class_level;
         if(isset($this->jcl[$job_class_level])) {
            $this->jobs[$job_id]->left = $this->jcl[$job_class_level]->canvas_left;
            $this->jcl[$job_class_level]->add_width($this->jobs[$job_id]->width+(2*$this->margin));
         }
      }
   }
   
   
   function pass4($job_id=0) {
      if($job_id==0) {
         $job_id = $this->top_level_job;
      }
      $canvas_left = 0;
      $job_level_top = 0;
      $child_width = 0;
      $render_mode = 0; //(count($this->jobs[$job_id]->child)>4?1:0);
      $old_job_class_id = 0;
      $old_width = 0;
      if($this->jobs[$job_id]->render_child>0) {
         foreach($this->jobs[$job_id]->child as $job_idx=>$job_nmx) {
            if($this->jobs[$job_idx]->rendered) {
               switch($render_mode) {
                  case 1:
                     $this->jobs[$job_idx]->render_mode = 1;
                     break;
                  case 0:
                     if($old_job_class_id!=0&&$old_job_class_id!=$this->jobs[$job_idx]->job_class_id) {
                        $canvas_left -= ($this->jobs[$job_idx]->width/2)-$this->margin;
                     }
                     $this->jobs[$job_idx]->center_x = $canvas_left + $this->margin + ($this->jobs[$job_idx]->width/2);
                     $this->jobs[$job_idx]->center_y = $job_level_top + $this->margin + ($this->jobs[$job_idx]->height/2);
                     $this->jobs[$job_idx]->left = $canvas_left + $this->margin;
                     $this->jobs[$job_idx]->top = $job_level_top;
                     $canvas_left += $this->margin + $this->jobs[$job_idx]->width + $this->margin;
                     $old_width = $this->jobs[$job_idx]->width;
                  default:
                     break;
               }
               $old_job_class_id = $this->jobs[$job_idx]->job_class_id;
            }
         }
         $this->jobs[$job_id]->child_width = $canvas_left;
         foreach($this->jobs[$job_id]->child as $job_idx=>$job_nmx) {
            if($this->jobs[$job_idx]->rendered) {
               if($this->jobs[$job_idx]->render_child>0) {
                  $child_width = $this->pass4($job_idx);
               }
            }
         }
      }
      if($this->jobs[$job_id]->center_x==0) {
         $this->jobs[$job_id]->center_x = $this->margin + ($this->jobs[$job_id]->width/2);
         $this->jobs[$job_id]->center_y = $this->margin + ($this->jobs[$job_id]->height/2);
         $this->jobs[$job_id]->left = $this->margin;
      }
      $x_width = max($canvas_left,(2*$this->margin)+$this->jobs[$job_id]->width,$child_width);
      $this->width = $x_width;
      return $x_width;
   }
   
   function pass5($job_id=0) {
      if($job_id==0) {
         $job_id = $this->top_level_job;
      }
      
      if($this->jobs[$job_id]->rendered) {
         
         $upper_job_id = $this->jobs[$job_id]->upper_job_id;
         if($upper_job_id==0) {
            $current_width = $this->jobs[$job_id]->width;
         } else {
            $current_width = $this->jobs[$upper_job_id]->child_width;
         }
         $offset = ($this->width-$current_width)/2;
         $this->jobs[$job_id]->offset = $offset;
         if($this->jobs[$job_id]->render_child>0) {
            foreach($this->jobs[$job_id]->child as $job_idx=>$job_nmx) {
               $this->pass5($job_idx);
            }
         }
      }
      
   }
   
   function pass6($job_id=0) {
      if($job_id==0) {
         $job_id = $this->top_level_job;
      }
      
      $upper_job_id = $this->jobs[$job_id]->upper_job_id;
      // $offset = ($this->width-$this->jobs[$upper_job_id]->child_width)/2; 
      $offset = ($this->width/2)-$this->jobs[$job_id]->center_x;
      if($this->jobs[$job_id]->render_child>0) {
         if(isset($this->jobs[$upper_job_id])) {
            foreach($this->jobs[$upper_job_id]->child as $job_idx=>$v) {
               $this->jobs[$job_idx]->offset = $offset;
               //_debuglog($this->jobs[$job_idx]->job_nm." : $offset");
            }
         }
         $this->jobs[$job_id]->offset = $offset;
         $offset = ($this->width-$this->jobs[$upper_job_id]->child_width)/2; 
      } else {
      
      }
      
      if($this->jobs[$job_id]->render_child>0) {
         foreach($this->jobs[$job_id]->child as $job_idx=>$job_nmx) { /// recurse children
            if($this->jobs[$job_idx]->rendered) {
               $this->pass6($job_idx);
            }
         }
      }
   }
   
   function pass7() {
      $min_left = 100000;
      foreach($this->jobs as $job_id=>$jobs) {
         if($jobs->rendered) {
            $left = $jobs->left+$this->canvas_left+$jobs->offset+$this->offset;
            $min_left = min($min_left,$left);
         }
      }
      $this->offset = -($min_left-$this->canvas_left);
      foreach($this->jobs as $job_id=>$jobs) {
         if($jobs->rendered) {
            $left = $jobs->left+$this->canvas_left+$jobs->offset;
            if($left<$this->canvas_left) {
               $this->offset = max($this->offset,$this->canvas_left-$left);
            }
         }
      }
      
      $max_w = 0;
      foreach($this->jobs as $job_id=>$jobs) {
         if($jobs->rendered) {
            $left = $jobs->left+$jobs->offset+$this->offset;
            $width = $jobs->width;
            $max_w = max($max_w,$left+$width+$this->margin);
         }
      }
      $this->width = max($this->width,$max_w);
   }
   
   function pass10() {
      ksort($this->levels);
      $level_top = $this->margin_vertical;
      foreach($this->levels as $job_class_level=>$v) {
         $cl = $level_top;
         $jobclass_top = 0;
         $level_height = 0;
         foreach($v as $job_class_id=>$x) {
            if($this->job_class[$job_class_id]->rendered==FALSE) continue;
            $jobclass_top += $this->padding_top;
            list($job_class_nm,$w,$h,$y)=$x;
            $box_height = max($h+(2*$this->box_padding),$this->calcBoxHeight($job_class_id));
            $box_width = $w+(2*$this->box_padding);
            $this->job_class[$job_class_id]->x = $this->margin;
            $this->job_class[$job_class_id]->y = $y+($box_height/2); /// y = relative to level top
            $this->job_class[$job_class_id]->width = $box_width;
            $this->job_class[$job_class_id]->height = $box_height;
            $level_height += $box_height;
         }
         $level_top += $level_height+$this->padding_top+$this->padding_bottom;
         $this->level_dimension[$job_class_level] = array($job_class_level,$level_top,$level_height);
      }
   }
   
   function get_width($job_id) {
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
      /// render job_class
      $clevel = 0;
      $top = $this->margin_vertical;
      $level_top = $top;
      $left = $this->margin;
      imagesetstyle($this->im,array($this->im_separator,$this->im_bgcolor,$this->im_bgcolor,$this->im_bgcolor));
      $separator_y = $top;
      imageline($this->im,$left,$top,$this->width,$top,IMG_COLOR_STYLED);
      $top += $this->separator_gap;
      if(is_array($this->level_dimension)) {
         foreach($this->level_dimension as $job_class_levelx=>$v) {
            list($job_class_level,$level_top,$level_height)=$v;
            $rendered = 0;
            $this->jcl[$job_class_level]->separator_y = $separator_y;
            
            foreach($this->levels[$job_class_level] as $job_class_id=>$m) {
               list($job_class_nm,$job_class_w,$job_class_h,$job_class_y)=$m;
               if($this->job_class[$job_class_id]->rendered) {
                  $job_class_w = $this->job_class[$job_class_id]->width;
                  $job_class_h = $this->job_class[$job_class_id]->height;
                  $job_class_y = $this->job_class[$job_class_id]->y;
                  imagefttext($this->im, $this->font_size, $this->font_angle, $left+$this->padding_left, $top+$job_class_y, $this->im_title, $this->font, $job_class_nm);
                  //imagerectangle($this->im,$left,$top,$left+$job_class_w,$top+$job_class_h,$this->im_border);
                  $this->job_class[$job_class_id]->top = $top;
                  $rendered ++;
                  $top += $job_class_h;
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
      
      foreach($this->jobs as $job_id=>$jobs) {
         if($jobs->rendered) {
            $top = $jobs->top+$this->job_class[$jobs->job_class_id]->top;
            $left = $jobs->left+$this->canvas_left+$jobs->offset+$this->offset;
            $width = $jobs->width;
            $height = $jobs->height;
            if($this->focus_job_id==$job_id) {
               imagefilledrectangle($this->im,$left,$top,$left+$width+$this->box_padding,$top+$height,$this->im_focusbgcolor);
               imagerectangle($this->im,$left,$top,$left+$width+$this->box_padding,$top+$height,$this->im_focusborder);
               imagerectangle($this->im,$left-1,$top-1,$left+$width+$this->box_padding+1,$top+$height+1,$this->im_focusoutsideborder);
            } else {
               imagerectangle($this->im,$left,$top,$left+$width+$this->box_padding,$top+$height,$this->im_border);
            }
            $_SESSION["hris_nodes"][$job_id] = new _Node($job_id,0,$width,$height,"","",$left,$top);
            $mid = $left+($width/2);
            if($jobs->upper_job_id>0) {
               imageline($this->im,$mid,$top,$mid,$this->jcl[$jobs->job_class_level]->separator_y,$this->im_connector);
               $this->jobs[$job_id]->p_parent = array($mid,$this->jcl[$jobs->job_class_level]->separator_y);
            }
            if($jobs->render_child>0) {
               imageline($this->im,$mid,$top+$height,$mid,$top+$height+$this->separator_gap,$this->im_connector);
               $this->jobs[$job_id]->p_child = array($mid,$top+$height+$this->separator_gap);
            }
            $ntop = $top;
            $cnt = count($jobs->job_nm_layout);
            $offset_h = (($height)-($cnt*($this->nm_h)))/2;
            $ntop = $top+$offset_h;
            foreach($jobs->job_nm_layout as $nmx) {
               $ntop += $this->nm_h;
               $bbox = imageftbbox($this->font_size, $this->font_angle, $this->font, $nmx);
               $wx = $bbox[2] - $bbox[0];
               $offset_x = ($width-$wx)/2;
               if($this->focus_job_id==$job_id) {
                  imagefttext($this->im, $this->font_size, $this->font_angle, $left+$this->box_padding+$offset_x, $ntop-1, $this->im_focustitle, $this->font, $nmx);
               } else {
                  imagefttext($this->im, $this->font_size, $this->font_angle, $left+$this->box_padding+$offset_x, $ntop-1, $this->im_title, $this->font, $nmx);
               }
            }
         }
      }
      
      
      foreach($this->jobs as $job_id=>$v) {
         if($this->jobs[$job_id]->rendered) {
            if($this->jobs[$job_id]->upper_job_id>0) {
               $cparent = $this->jobs[$this->jobs[$job_id]->upper_job_id]->p_child;
               $cchild = $this->jobs[$job_id]->p_parent;
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
      $focus_job = $this->get_focus_job();
      
      foreach($this->jobs as $job_id=>$v) {
         if($this->jobs[$job_id]->rendered) {
            $job_nm = $this->jobs[$job_id]->job_nm;
            $top = $this->jobs[$job_id]->top;
            $left = $this->jobs[$job_id]->left;
            $org_id = $this->jobs[$job_id]->org_id;
            $text_top = $this->jobs[$job_id]->text_top;
            $text_left = $this->jobs[$job_id]->text_left;
            $w = $this->jobs[$job_id]->width;
            $h = $this->jobs[$job_id]->height;
            $box_width = $this->jobs[$job_id]->box_width;
            $box_height = $this->jobs[$job_id]->box_height;
            $level = $this->jobs[$job_id]->job_class_level;
            list($lw,$lh,$lwttl,$lhttl,$leveltop) = $this->level_dimension[$this->jobs[$job_id]->job_class_level];
            $top = $leveltop;
            
            if($this->focus_job_id==$job_id) {
               imagefilledrectangle($this->im,$left,$top,$left+$box_width,$top+$box_height,$this->im_focusbgcolor);
               $text_left++;
               imagerectangle($this->im,$left,$top,$left+$box_width,$top+$box_height,$this->im_border);
               imagefttext($this->im, $this->font_size, $this->font_angle, $left+$text_left, $top+$text_top, $this->im_focustitle, $this->font, $job_nm);
            } else {
               imagerectangle($this->im,$left,$top,$left+$box_width,$top+$box_height,$this->im_border);
               imagefttext($this->im, $this->font_size, $this->font_angle, $left+$text_left, $top+$text_top, $this->im_title, $this->font, $job_nm);
            }
            
            
            
            
            $_SESSION["hris_nodes"][$job_id] = new _Node($job_id,0,$box_width,$box_height,"","",$left,$top);
            
            if($this->jobs[$job_id]->upper_job_id>0) {
               imageline($this->im,$left+($box_width/2),$top,$left+($box_width/2),$leveltop,$this->im_connector);
               $this->jobx[$job_id]->p_parent = array($left+($box_width/2),$leveltop);
            }
            if($this->jobs[$job_id]->render_child>0) {
               imageline($this->im,$left+($box_width/2),$top+$box_height,$left+($box_width/2),$leveltop+$lhttl+$this->margin_vertical,$this->im_connector);
               $this->jobs[$job_id]->p_child = array($left+($box_width/2),$leveltop+$lhttl+$this->margin_vertical);
            }
            
         }
      }
      
      foreach($this->jobs as $job_id=>$v) {
         if($this->jobs[$job_id]->rendered) {
            if($this->jobs[$job_id]->upper_job_id>0) {
               $cparent = $this->jobs[$this->jobs[$job_id]->upper_job_id]->p_child;
               $cchild = $this->jobx[$job_id]->p_parent;
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

class JobClassLevel {
   var $job_class_level;
   var $save_canvas_left = 0;
   var $canvas_left = 0;
   var $offset = 0;
   var $width = 0;
   var $separator_y = 0;
   
   function __construct($job_class_level) {
      $this->job_class_level = $job_class_level;
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

class JobClassNode {
   var $job_class_id;
   var $job_class_nm;
   var $job_class_level;
   var $job_level;
   var $x;
   var $y;
   var $top;
   var $left;
   var $width;
   var $height;
   var $jobs;
   var $rendered = FALSE;
   var $canvas_left = 0;
   
   function __construct($job_class_id,$job_class_nm,$job_class_level,$job_level,$init_y) {
      $db=&Database::getInstance();
      $this->job_class_id = $job_class_id;
      $this->job_class_nm = $job_class_nm;
      $this->job_class_level = $job_class_level;
      $this->job_level = $job_level;
      $this->y = $init_y;
      
      /// jobs
      $this->jobs = array();
      $sql = "SELECT job_id,job_nm FROM ".XOCP_PREFIX."jobs WHERE job_class_id = '$job_class_id' AND status_cd = 'normal'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($job_id,$job_nm)=$db->fetchRow($result)) {
            $this->jobs[$job_id] = $job_nm;
         }
      }
   }
   
}

class JobWithChild {
   var $job_id;
   var $child;
   var $render_t;
   var $child_offset;
   
   function __construct($job_id,$child) {
      $this->job_id = $job_id;
      $this->child = $child;
      $this->child_offset = 0;
      if(is_array($this->child)&&count($this->child)>4) {
         $this->render_t = 1;
      } else {
         $this->render_t = 0;
      }
   }
   
}

class JobNode {
   var $job_id;
   var $job_nm;
   var $job_nm_layout;
   var $job_abbr;
   var $job_class_id;
   var $job_class_nm;
   var $job_class_level;
   var $job_level;
   var $org_id;
   var $org_nm;
   var $upper_job_id;
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
   
   function __construct($job_id,$job_nm,$job_abbr,$upper_job_id,$job_class_id,$org_id,$order_no) {
      $db=&Database::getInstance();
      $this->job_id = $job_id;
      $this->job_nm = $job_nm;
      $this->job_abbr = $job_abbr;
      $this->upper_job_id = $upper_job_id;
      $this->job_class_id = $job_class_id;
      $this->org_id = $org_id;
      $this->width = 0;
      $this->height = 0;
      $this->order_no = $order_no;
         
      //// job_class
      $sql = "SELECT job_class_nm,job_class_level,job_level"
           . " FROM ".XOCP_PREFIX."job_class"
           . " WHERE job_class_id = '".$this->job_class_id."'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($this->job_class_nm,
              $this->job_class_level,
              $this->job_level)=$db->fetchRow($result);
      }
      
      //// child
      $this->child = array();
      $sql = "SELECT a.job_id,a.job_nm FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
           . " LEFT JOIN ".XOCP_PREFIX."orgs c ON c.org_id = a.org_id"
           . " WHERE a.upper_job_id = '".$this->job_id."'"
           . " AND a.status_cd = 'normal'"
           . " ORDER BY b.job_class_level,b.job_class_id,c.order_no";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($job_idx,$job_nmx)=$db->fetchRow($result)) {
            $this->child[$job_idx] = $job_nmx;
         }
      }
      
      //// sibling
      $this->sibling = array();
      $sql = "SELECT a.job_id,a.job_nm FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
           . " LEFT JOIN ".XOCP_PREFIX."orgs c ON c.org_id = a.org_id"
           . " WHERE a.upper_job_id = '".$this->upper_job_id."' AND b.job_class_level = '".$this->job_class_level."'"
           . " AND a.status_cd = 'normal'"
           . " ORDER BY b.job_class_level,b.job_class_id,c.order_no";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($job_idx,$job_nmx)=$db->fetchRow($result)) {
            if($job_idx==$job_id) continue;
            $this->sibling[$job_idx] = $job_nmx;
         }
      }
   }
}

} /// HRIS_CLASSDIAGRAM_DEFINED
?>