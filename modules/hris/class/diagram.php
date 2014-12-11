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
   var $padding_top = 10;
   var $padding_left = 5;
   var $padding_bottom = 10;
   var $padding_right = 5;
   var $margin = 5;
   var $margin_vertical = 10;
   var $im = NULL;
   var $left;
   var $level_left = 0;
   var $color = array(
         "im_bgcolor"=>       array(255,255,255),
         "im_focustitle"=>    array(0,0,0),
         "im_focusbgcolor"=>  array(0xee,0xee,0xee),
         "im_border"=>        array(100,100,100),
         "im_connector"=>     array(100,100,240),
         "im_separator"=>     array(210,210,210),
         "im_title"=>         array(0,0,0),
         "im_subtitle"=>      array(0,0,0)
   );
   var $file;
   var $top_level_job = 0;
   
   
   function __construct($focus_job_id=NULL) {
      $this->font = XOCP_DOC_ROOT."/include/fonts/LucidaSansRegular.ttf";
      $this->width = 400;
      $this->height = 1400;
      $this->matrix = array();
      $this->levels = array();
      $this->file = XOCP_DOC_ROOT."/tmp/jobstruct_${focus_job_id}.png";
      $this->load_job_class();
      $this->load_jobs();
      $this->set_focus_job($focus_job_id);
   }
   
   function set_focus_job($job_id) {
      $this->focus_job_id = $job_id;
   }
   
   function get_focus_job() {
      if($this->focus_job_id>0&&isset($this->jobs[$this->focus_job_id])) {
         return $this->focus_job_id;
      } else {
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
      $this->job_class = array();
      if($db->getRowsNum($result)>0) {
         while(list($job_class_id,$job_class_nm,$job_class_level,$job_level)=$db->fetchRow($result)) {
            $this->job_class[$job_class_id] = new JobClassNode($job_class_id,$job_class_nm,$job_class_level,$job_level,$i);
            $this->matrix[$job_class_id] = array($job_class_id,0,0);
            $i++;
         }
      }
   }
   
   function load_jobs() {
      $db=&Database::getInstance();
      $sql = "SELECT a.job_id,a.job_nm,a.job_abbr,a.upper_job_id,a.job_class_id,a.org_id"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
           . " WHERE a.status_cd = 'normal'"
           . " ORDER BY b.job_class_id";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($job_id,$job_nm,
                    $job_abbr,
                    $upper_job_id,
                    $job_class_id,
                    $org_id)=$db->fetchRow($result)) {
            $this->jobs[$job_id] = new JobNode($job_id,$job_nm,$job_abbr,$upper_job_id,$job_class_id,$org_id);
            if($upper_job_id==0) {
               $this->top_level_job = $job_id;
            }
         }
      }
   }
   
   function get_max_width() {
      /// job class first:
      return $this->width;
   }
   
   function get_max_height() {
      $h = $this->margin_vertical;
      foreach($this->level_dimension as $level=>$v) {
         list($lw,$lh,$lwttl,$lhttl)=$v;
         $rendered = 0;
         foreach($this->levels[$level] as $job_class_id=>$m) {
            if($this->job_class[$job_class_id]->rendered) {
               $rendered ++;
            }
         }
         if($rendered>0) {
            $h += $lhttl + $this->margin_vertical + $this->margin_vertical;
         }
      }
      $h += $this->margin_vertical;
      return $h;
      return max($this->height,$h);
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
         // $this->flag_render_sibling($this->jobs[$job_id]->upper_job_id);
         $this->flag_render_parent($this->jobs[$job_id]->upper_job_id);
         $this->jobs[$this->jobs[$job_id]->upper_job_id]->render_child += 1;
      }
   }
   
   function pass0() {
      foreach($this->job_class as $job_class_id=>$v) {
         $job_class_nm = $v->job_class_nm;
         $job_class_level = $v->job_class_level;
         if(!is_array($this->levels[$job_class_level])) {
            $this->levels[$job_class_level] = array();
         }
         $bbox = imageftbbox($this->font_size, $this->font_angle, $this->font, $job_class_nm);
         $w = $bbox[2] - $bbox[0];
         $h = -1 - $bbox[5];
         $this->levels[$job_class_level][$job_class_id] = array($job_class_nm,$w,$h);
      }
      
      foreach($this->levels as $level=>$vv) {
         $sub_margin = 7;
         $lw = 0;
         $lh = $this->padding_top;
         $ttop = $this->padding_top;
         foreach($vv as $job_class_id=>$vvv) {
            list($job_class_nm,$w,$h)=$vvv;
            $this->levels[$level][$job_class_id] = array($job_class_nm,$w,$h,$ttop+$h);
            $lw = max($lw,$w);
            $lh += $h + $sub_margin;
            $ttop += $h + $sub_margin;
         }
         $lh += $this->padding_bottom - $sub_margin;
         $this->level_dimension[$level] = array($lw,$lh,$lw+$this->padding_left+$this->padding_right,$lh);
      }
      foreach($this->level_dimension as $level=>$v) {
         list($lw,$lh,$lwttl,$lhttl) = $v;
         $this->level_left = max($this->level_left,$lwttl);
      }
      $this->level_left += $this->margin;
      
   }
   
   function pass1() {
      $left = 0;
      $focus_job = $this->get_focus_job();
      if($focus_job==0) {
         return;
      }
      
      //// flag rendering
      $this->flag_render($focus_job);
      $j =& $this->jobs[$focus_job];
      foreach($j->child as $job_idx=>$job_nmx) {
         $this->flag_render($job_idx);
      }
      $this->flag_render_parent($focus_job);
      if(count($this->jobs[$focus_job]->child)>0) {
         foreach($this->jobs[$focus_job]->child as $job_idx=>$v) {
            $this->jobs[$focus_job]->render_child++;
         }
      }
      
      
      $top = $this->margin_vertical;
      foreach($this->level_dimension as $level=>$v) {
         list($lw,$lh,$lwttl,$lhttl)=$v;
         $rendered = 0;
         foreach($this->levels[$level] as $job_class_id=>$m) {
            if($this->job_class[$job_class_id]->rendered) {
               $rendered ++;
            }
         }
         if($rendered>0) {
            $top += $this->margin_vertical;
            $this->level_dimension[$level] = array($lw,$lh,$lwttl,$lhttl,$top);
            $top += $lhttl + $this->margin_vertical;
         }
      }
      
      $left = $this->level_left;
      
      foreach($this->jobs as $job_id=>$v) {
         if($this->jobs[$job_id]->rendered) {
            $bbox = imageftbbox($this->font_size, $this->font_angle, $this->font, $this->jobs[$job_id]->job_nm);
            $level = $this->jobs[$job_id]->job_class_level;
            list($lw,$lh,$lwttl,$lhttl,$top)=$this->level_dimension[$level];
            $w = $bbox[2] - $bbox[0];
            $h = floor((-1 - $bbox[5])/2)*2;
            $this->jobs[$job_id]->left = $left;
            $this->jobs[$job_id]->top = $top;
            $this->jobs[$job_id]->text_left = $this->padding_left;
            $this->jobs[$job_id]->text_top = $this->padding_top + $h;
            $this->jobs[$job_id]->width = $w;
            $this->jobs[$job_id]->height = $h;
            $this->jobs[$job_id]->box_width = $w + $this->padding_left + $this->padding_right;
            $this->jobs[$job_id]->box_height = $h + $this->padding_top + $this->padding_bottom;
         }
      }
      $job_id = $this->top_level_job;
      $this->pass2_sub($job_id);
      $width = $this->get_width($job_id);
      $this->width = max($this->width,$width+$this->level_left);
      $this->jobs[$job_id]->left = $this->level_left + ($width-$this->jobs[$job_id]->box_width)/2;
      
      
   }
   
   function pass2_sub($job_id,$offset=0,$pwidth=0) {
      $old_level = 0;
      $c = 0;
      $width = 0;
      foreach($this->jobs[$job_id]->child as $job_idx=>$v) {
         if($this->jobs[$job_idx]->rendered) {
            $wcx = $this->jobs[$job_idx]->box_width;
            $level = $this->jobs[$job_idx]->job_class_level;
            if($c==0) {
               $old_level = $level;
            }
            $width = max($wcx,$this->get_width($job_idx));
            if($this->jobs[$job_id]->render_child==1) {
               $width = max($pwidth,$width);
            }
            if($old_level!=$level) {
               $offset -= $wcx/2;
               $old_level = $level;
            }
            $this->jobs[$job_idx]->left += $offset + ($width - $wcx)/2;
            $this->pass2_sub($job_idx,$offset,$width);
            $offset += $width;
            $c++;
         }
      }
   }
   
   function pass4() {
      $top = $this->margin_vertical;
      $job_id = $this->get_focus_job();
      $clevel = $this->jobs[$job_id]->job_class_level;
      foreach($this->level_dimension as $level=>$v) {
         list($lw,$lh,$lwttl,$lhttl)=$v;
         $rendered = 0;
         foreach($this->levels[$level] as $job_class_id=>$m) {
            if($this->job_class[$job_class_id]->rendered) {
               $rendered ++;
            }
         }
         if($rendered>0) {
            $top += $this->margin_vertical;
            if($clevel==$level) {
               //$lh += $lhttl;
               //$top += $lhttl;
            }
            $this->level_dimension[$level] = array($lw,$lh,$lwttl,$lhttl,$top);
            $top += $lhttl + $this->margin_vertical;
         }
      }
   }
   
   
   function get_width($job_id) {
      $wc = 0;
      $old_level = 0;
      foreach($this->jobs[$job_id]->child as $job_idx=>$v) {
         if($this->jobs[$job_idx]->rendered) {
            $level = $this->jobs[$job_idx]->job_class_level;
            if($wc==0) {
               $old_level = $level;
            }
            $wx = $this->get_width($job_idx);
            $wcx = $this->jobs[$job_idx]->box_width;
            $wc += max($wx,$wcx);
            if($old_level!=$level) {
               $wc -= $wcx/2;
               $old_level = $level;
            }
         }
      }
      $w0 = $this->jobs[$job_id]->box_width + 2*$this->margin;
      return max($w0,$wc);
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
      $this->pass4();
      $this->init_image();
      /// render job_class
      $clevel = 0;
      $top = $this->margin_vertical;
      $left = $this->margin;
      imagesetstyle($this->im,array($this->im_separator,$this->im_bgcolor,$this->im_bgcolor,$this->im_bgcolor));
      imageline($this->im,$left,$top,$this->width,$top,IMG_COLOR_STYLED);
      foreach($this->level_dimension as $level=>$v) {
         list($lw,$lh,$lwttl,$lhttl,$leveltop)=$v;
         $rendered = 0;
         foreach($this->levels[$level] as $job_class_id=>$m) {
            list($job_class_nm,$wx,$hx,$ttop)=$m;
            if($this->job_class[$job_class_id]->rendered) {
               imagefttext($this->im, $this->font_size, $this->font_angle, $left+$this->padding_left, $leveltop+$ttop, $this->im_title, $this->font, $job_class_nm);
               $rendered ++;
            }
         }
         if($rendered>0) {
            // imagerectangle($this->im,$left,$leveltop,$left+$lwttl,$leveltop+$lhttl,$this->im_border);
            imageline($this->im,$left,$leveltop+$lhttl+$this->margin_vertical,$this->width,$leveltop+$lhttl+$this->margin_vertical,IMG_COLOR_STYLED);
         }
      }
      
      
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

class JobClassNode {
   var $job_class_id;
   var $job_class_nm;
   var $job_class_level;
   var $job_level;
   var $x;
   var $y;
   var $hy;
   var $top;
   var $left;
   var $width;
   var $height;
   var $jobs;
   var $rendered = FALSE;
   
   function __construct($job_class_id,$job_class_nm,$job_class_level,$job_level,$init_y) {
      $db=&Database::getInstance();
      $this->job_class_id = $job_class_id;
      $this->job_class_nm = $job_class_nm;
      $this->job_class_level = $job_class_level;
      $this->job_level = $job_level;
      $this->y = $init_y;
      
      /// jobs
      $this->jobs = array();
      $sql = "SELECT job_id,job_nm FROM ".XOCP_PREFIX."jobs WHERE job_class_id = '$job_class_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($job_id,$job_nm)=$db->fetchRow($result)) {
            $this->jobs[$job_id] = $job_nm;
         }
      }
   }
   
}

class JobNode {
   var $job_id;
   var $job_nm;
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
   
   function __construct($job_id,$job_nm,$job_abbr,$upper_job_id,$job_class_id,$org_id) {
      $db=&Database::getInstance();
      $this->job_id = $job_id;
      $this->job_nm = $job_nm;
      $this->job_abbr = $job_abbr;
      $this->upper_job_id = $upper_job_id;
      $this->job_class_id = $job_class_id;
      $this->org_id = $org_id;
         
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
           . " WHERE a.upper_job_id = '".$this->job_id."'"
           . " ORDER BY b.job_class_level,b.job_class_id";
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
           . " WHERE a.upper_job_id = '".$this->upper_job_id."' AND b.job_class_level = '".$this->job_class_level."'"
           . " ORDER BY b.job_class_level,b.job_class_id";
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