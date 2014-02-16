<?php
/*
 * Created on 02.12.2006
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */

class Graph {

  private $img;
  private $color;
  var $bgcolor;
  var $labelcolor;
  var $label_x;
  var $label_y;
  var $height;
  var $width;

  function Graph($width,$height,$bgcolor,$labelcolor) {
    if (!defined('DEBUG')) define('DEBUG',@$_GET['debug']);
    if (DEBUG) print "Creating new blank image with dimensions ". ($width+55) ."x". ($height+35) .".\n";
    $this->img=imagecreate($width+55,$height+35); // margin
    $this->height=$height;
    $this->width=$width;
    $this->color=array();
    $label_x='%.2f';
    $label_y='%.2f';
    if (preg_match('/^DATE::(.*)$/',$label_x,$match)) {
      $this->x_is_date=true;
      $this->label_x=$match[1];
    } else $this->label_x=$label_x;
    $this->label_y=$label_y;
    @$this->color($bgcolor);
    @$this->labelcolor = $this->color($labelcolor);
    $this->x_notches = 10;
    $this->y_notches = 10;
  }

  function setLabel($x = NULL, $y = NULL)
  {
    if (!empty($x)) {
      if (preg_match('/^DATE::(.*)$/',$x,$match)) {
        $this->x_is_date=true;
        $this->label_x=$match[1];
      } else $this->label_x=$x;
    }
    if (!empty($y)) {
      $this->label_y=$y;
    }
  }

  function setScale($type, $x = NULL, $y= NULL) // number
  {
    if (!empty($x)) {
      $this->x_notches = $x;
      $this->x_notch_type = $type; // by interval or by number of notches?
    }
    if (!empty($y)) {
       $this->y_notches = $y;
      $this->y_notch_type = $type; // by interval or by number of notches?
    }
  }

  function addDataRow($row=false) {
    // allocate colors to the plotstyle.
    //var_dump($row->plotStyle);
    foreach ($row->plotStyle->color as &$color) $color=$this->color($color);
    if ($row) $this->rows[]=$row;
  }

  // finalizes the image

  function draw_rows() {
    if (DEBUG) print "draw_rows() called";
    if ($this->rows) {
      foreach ($this->rows as $row) {
        if (DEBUG) print "NEW ROW!";
        foreach ($row->dataPoints as $point) {
          if (DEBUG) print "Point($point[x],$point[y])\n";
          $mapped=$this->map_point($point['x'],$point['y']);
          if (DEBUG) print "Mapped to($mapped[x],$mapped[y])\n";
          if (DEBUG) print "!!!". $row->plotStyle->color. "!!!\n";
          $row->plotStyle->drawDataPoint($this->img,$mapped['x'],$mapped['y']);
          if (!empty($point['label'])) {
            imagestring($this->img, 1, $mapped['x']+5, $mapped['y']+5, $point['label'], $this->labelcolor);
          }
        }
      }
    }
  }

  function draw_image() {
    $this->set_ranges(); // finds minimum and maximum
    /* bars, lines and points. to keep it all visible. */
    $this->addMeans();
    $this->addDeviations();
    $this->addRegressions();
    $this->draw_rows();
    $this->scale(); // draws scale
    ob_start();
    imagepng($this->img);
    return ob_get_clean();
  }

  function rgb_allocate($hex) {
    if (strlen($hex)!=6) return false;
    $hex_split=array(substr($hex,0,2),substr($hex,2,2),substr($hex,4,2));
    foreach ($hex_split as $val) $dec[]=hexdec($val);
    $this->color[$hex]=imagecolorallocate($this->img,$dec[0],$dec[1],$dec[2]);
    return $this->color[$hex];
  }

  function addMeans() {
    foreach ($this->rows as &$row) {
      if (!$row->mean) continue;
      $y = $row->dependent!=1; // only draw horizontal mean if y is a measured value.
      $x = $row->dependent!=0; // only draw vertical mean if x is a measured value.
      $x_sum=0;
      $y_sum=0;
      foreach ($row->dataPoints as $point)
      {
        $x_sum+=$point['x'];
        $y_sum+=$point['y'];
      }
      $row->x_mean = $x_sum / count($row->dataPoints);
      $row->y_mean = $y_sum / count($row->dataPoints);
      $map = $this->map_point($row->x_mean, $row->y_mean);
      if (DEBUG) print "Drawing mean line from [35, $map[y]] to [". ($this->width+40) .", $map[y]]\n";
      if ($x)  imageline($this->img, 35, $map['y'], $this->width+40, $map['y'], $row->plotStyle->color);
      if ($y)  imageline($this->img, $map['x'], 10, $map['x'], $this->height + 15, $row->plotStyle->color);
    }
  }
  function addDeviations() {
    foreach ($this->rows as &$row) {
      $y = $row->dependent!=1; // only draw horizontal mean if y is a measured value.
      $x = $row->dependent!=0; // only draw vertical mean if x is a measured value.
      $ellipsis = $row->dependent==3;
      $x_sum=0;
      $y_sum=0;
      //var_dump($row->y_mean);
      foreach ($row->dataPoints as $point)
      {
        $x_sum+=pow(($point['x']-$row->x_mean), 2);
        if (DEBUG) print ($point['y']-$row->y_mean) . "\n\n";
        $y_sum+=pow(($point['y']-$row->y_mean), 2);
      }
      $row->x_dev = sqrt($x_sum / (count($row->dataPoints)-1));
      $row->y_dev = sqrt($y_sum / (count($row->dataPoints)-1));
      if (DEBUG) print "Calculated deviations to $row->x_dev, $row->y_dev.\n";
      $center = $this->map_point($row->x_mean, $row->y_mean);
      $corner = $this->map_point($row->x_mean+$row->x_dev, $row->y_mean+$row->y_dev);
      if ($ellipsis) {
        $width=($corner['x']-$center['x'])*2;
        $height=($corner['y']-$center['y'])*2;
        if (DEBUG) print "Drawing dev ellipse around [$center[x], $center[y]] by $width x $height\n";
        for ($i=1;$i<=$row->dev;$i++) {
          imageellipse($this->img, $center['x'], $center['y'], $width*$i, $height*$i, $row->plotStyle->color);
          imagestring($this->img, 1, $center['x']+($width*$i/2), $center['y'], "$i sigma", $row->plotStyle->color);
        }
      }
      else {
        for ($i=1; $i<=$row->dev;$i++) {
          if ($x)  {
            imageline($this->img, $i*$corner['x']-($i-1)*$center['x'], $i*$corner['y']-($i-1)*$center['y'], $i*$corner['x']-($i-1)*$center['x'], ($i+1)*$center['y']-$i*$corner['y'], $row->plotStyle->color);
            imageline($this->img, ($i+1)*$center['x']-$i*$corner['x'], $i*$corner['y']-($i-1)*$center['y'], ($i+1)*$center['x']-$i*$corner['x'], ($i+1)*$center['y']-$i*$corner['y'], $row->plotStyle->color);
          }
          if ($y) {
            imageline($this->img, $i*$corner['x']-($i-1)*$center['x'], $i*$corner['y']-($i-1)*$center['y'], ($i+1)*$center['x']-$i*$corner['x'], $i*$corner['y']-($i-1)*$center['y'], $row->plotStyle->color);
            imageline($this->img, $i*$corner['x']-($i-1)*$center['x'], ($i+1)*$center['y']-$i*$corner['y'], ($i+1)*$center['x']-$i*$corner['x'], ($i+1)*$center['y']-$i*$corner['y'], $row->plotStyle->color);
          }
        }
      }
      //
    }

  }

  function addRegressions()
  {
    foreach ($this->rows as $row)
    {
      if (!empty($row->regression))
      {
        $p1 = $this->map_point($row->regression[0]['x'], $row->regression[0]['y']);
        $p2 = $this->map_point($row->regression[1]['x'], $row->regression[1]['y']);
        if (DEBUG) print "Drawing regression from [$p1[x], $p1[y]] to [$p2[x], $p2[y]]...\n";
        imageline($this->img, $p1['x'], $p1['y'], $p2['x'], $p2['y'], $row->plotStyle->color);
      }
    }
  }

  function color($hex) {
    if (strlen($hex)==3) $hex=$hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    if (@$this->color[$hex]) return @$this->color[$hex];
    else return $this->rgb_allocate($hex);
  }

  function get_unix_time($time) {
    if (preg_match('/^[0-9]+$/',$time,$match)) return $match[0];
    else if (preg_match('/[0-9]{4,4}-[0-9]{2,2}-[0-9]{2,2}( [0-9]{2,2}:[0-9]{2,2})?/',$time,$match)) {
      return strtotime($match[0]);
    }
    else return false;
  }

  function send_header() {
    if (DEBUG) drupal_set_header("Content-type: text/html;charset=utf-8");
    else drupal_set_header("Content-type: image/png;charset=utf-8");
  }

  function map_point($x,$y) {
    if (DEBUG) print "Mapping point [$x, $y] \n";
    if (!$this->max_height) exit("HEIGHT_IS_ZERO");
    if (!$this->max_width) exit("WIDTH_IS_ZERO");
    if (DEBUG) print ($this->max_y - $y)/$this->max_height*$this->height;
    $x_mapped=ceil(($x-$this->min_x)/$this->max_width*$this->width + 40);
    $y_mapped=ceil(($this->max_y - $y)/$this->max_height*$this->height)+10;
    return array('x'=>$x_mapped,'y'=>$y_mapped);
  }

  function forceView($x_min, $y_min, $x_max, $y_max) {
    global $rehzero;
    $this->min_x=$x_min;
    $this->max_x=$x_max;
    $this->min_y=$y_min;
    $this->max_y=$y_max;
    $this->max_height=$this->max_y-$this->min_y;
    $this->max_width=$this->max_x-$this->min_x;
    $this->forced= true;
    $rehzero=$this->map_point(0,0);
    if ($this->notch_type == 'interval') {
      $this->x_notches = $this->max_width / $this->x_notches;
      $this->y_notches = $this->max_width / $this->y_notches;
    }
    if ($this->x_notch_type == 'interval') $this->x_notches = $this->max_width / $this->x_notches;
    if ($this->y_notch_type == 'interval') $this->y_notches = $this->max_height / $this->y_notches;
  }

  function set_ranges() {
    global $rehzero;
    if ($this->forced) return; // do not set range automatically.
    if (DEBUG) print "Automatically setting scale range.\n";
    if (DEBUG) print "Taking arbitrary extreme values from first row: \n";
    $first_point=array('x'=>$this->rows[0]->dimensions['x']['min'],'y'=>$this->rows[0]->dimensions['y']['min']);
    if (DEBUG) print "[$first_point[x], $first_point[y]]\n";
    $this->min_x=$first_point['x'];
    $this->max_x=$first_point['x'];
    $this->min_y=$first_point['y'];
    $this->max_y=$first_point['y'];

    if ($this->rows)
    foreach ($this->rows as $row) {
      if (DEBUG) print "Comparing with next row: ";
      $this->min_x = min($this->min_x,$row->dimensions['x']['min']);
       $this->max_x = max($this->max_x,$row->dimensions['x']['max']);
      $this->min_y = min($this->min_y,$row->dimensions['y']['min']);
       $this->max_y = max($this->max_y,$row->dimensions['y']['max']);
      if (DEBUG) print "ranges are [$this->min_x, $this->min_y] to [$this->max_x, $this->max_y]\n ";
    }
    if (DEBUG) print "[$this->min_x:$this->max_x],[$this->min_y:$this->max_y]\n";
    $this->max_height=$this->max_y-$this->min_y;
    $this->max_width=$this->max_x-$this->min_x;
    $rehzero=$this->map_point(0,0);
    if ($this->x_notch_type == 'interval') $this->x_notches = $this->max_width / $this->x_notches;
    if ($this->y_notch_type == 'interval') $this->y_notches = $this->max_height / $this->y_notches;
  }

  function scale() {
    global $rehzero;
    if (DEBUG) print "Drawing both axes.\n";
    // left vertical axis
    if (DEBUG) print "Zero is at [$rehzero[x], $rehzero[y]]\n";
    //$this->rows[0]->plotStyle->drawDataPoint($this->img, $rehzero['x'], $rehzero['y'], $this->rows[0]->plotStyle->color);
    $y_axis=min(max($rehzero['x'], 50), $this->width+40);
    $x_axis=min(max($rehzero['y'], 10), $this->height+15);
    if (DEBUG) print "Drawing axis from [$y_axis, 10] to [$y_axis, ". ($this->height+15) ."] in $this->labelcolor.\n";
    imageline($this->img,$y_axis,10,$y_axis,$this->height+15,$this->labelcolor);
    // bottom horizontal axis
    if (DEBUG) print "Drawing axis from [35, $x_axis] to [". ($this->width+40) .", $x_axis] in $this->labelcolor.\n";
    imageline($this->img,35,$x_axis,$this->width+40,$x_axis,$this->labelcolor);
    for ($i=0;$i<=$this->x_notches;$i++) {
      // scales on the x axis
      $xer=40+ceil($i * $this->width / $this->x_notches);
      imageline($this->img,$xer,$x_axis,$xer,$x_axis+5,$this->labelcolor);
      $label=$this->min_x+($i/$this->x_notches*$this->max_width);
      if ($this->x_is_date) $label=date($this->label_x,$label);
      else if ($this->label_x) $label=sprintf($this->label_x,$label);
      imagestring($this->img,1,$xer-5,$x_axis+10,$label,$this->labelcolor);
    }

    for ($i=0;$i<=$this->y_notches;$i++) {
      // scales on the y axis
      $yer=10+ceil($i*$this->height/$this->y_notches);
      imageline($this->img,$y_axis-5,$yer,$y_axis,$yer,$this->labelcolor);
      $label=$this->max_y-($i/$this->y_notches*$this->max_height);
      if ($this->label_y) $label=sprintf($this->label_y,$label);
      imagestring($this->img,1,$y_axis-38,$yer+2,$label,$this->labelcolor);
    }
  }
}
?>
