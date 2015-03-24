<?php

class Graph {

  private $img;
  private $color;
  var $bgcolor;
  var $labelcolor;
  var $label_x;
  var $label_y;
  var $height;
  var $width;
  var $notch_type;
  var $x_notch_type;
  var $y_notch_type;
  var $x_is_date;
  var $y_is_date;

  // Initialize the graph object.
  function Graph($width, $height, $bgcolor, $labelcolor) {
    if (!defined('DEBUG')) {
      define('DEBUG', @$_GET['debug']);
    }
    if (DEBUG) print "Creating new blank image with dimensions " . ($width + 55) . "x" . ($height + 35) . ".\n";
    // margin
    $this->img = imagecreate($width + 55, $height + 35);
    $this->height = $height;
    $this->width = $width;
    $this->color = array();
    $this->rows = array();
    $label_x = '%.2f';
    $label_y = '%.2f';
    if (preg_match('/^DATE::(.*)$/', $label_x, $match)) {
      $this->x_is_date = TRUE;
      $this->label_x = $match[1];
    }
    else $this->label_x = $label_x;
    $this->label_y = $label_y;
    @$this->color($bgcolor);
    @$this->labelcolor = $this->color($labelcolor);
    $this->x_notches = 10;
    $this->y_notches = 10;
  }

  // Optionally set the label of the axis. The X-axis can be formatted as a date, which will
  // cause the x value to be interpreted as UNIX time.
  function setLabel($x = NULL, $y = NULL, $x_name = NULL, $y_name = NULL) {
    if (!empty($x)) {
      if (preg_match('/^DATE::(.*)$/', $x, $match)) {
        $this->x_is_date = TRUE;
        $this->label_x = $match[1];
      }
      else $this->label_x = $x;
    }
    if (!empty($y)) {
      $this->label_y = $y;
    }
    if (!empty($x_name)) {
      $this->x_name = $x_name;
    }
    if (!empty($y_name)) {
      $this->y_name = $y_name;
    }
  }

  // number
  function setScale($type, $x = NULL, $y = NULL) {
    if (!empty($x)) {
      $this->x_notches = $x;
      // by interval or by number of notches?
      $this->x_notch_type = $type;
    }
    if (!empty($y)) {
      $this->y_notches = $y;
      // by interval or by number of notches?
      $this->y_notch_type = $type;
    }
  }

  function addDataRow($row = FALSE) {
    // allocate colors to the plotstyle.
    //var_dump($row->plotStyle);
    if(DEBUG) print("Adding row " . $row->name . "<br />");
    foreach ($row->plotStyle->color as $i=>$color) $row->plotStyle->acolor[$i] = $this->color($color);
    if ($row) {
      $this->rows[] = $row;
    }
  }

  // finalizes the image
  function drawRows() {
    if (DEBUG) print "<hr />draw_rows() called";
    if ($this->rows) {
      if (DEBUG) print("Drawing " . count($this->rows) . " rows <br />");
      foreach ($this->rows as $row) {
        // TODO: Outsource imagestring to plotStyle, like point drawing?
        $labelcolor = !empty($row->plotStyle->alabelcolor) ? $row->plotStyle->alabelcolor : $this->labelcolor;
        if (DEBUG) {
          print "NEW ROW! " . $row->name . "<br />";
        }
        foreach ($row->dataPoints as $point) {
          if (DEBUG) {
            print "Point($point[x],$point[y])\n";
          }
          $mapped = $this->map_point($point['x'], $point['y']);
          if (DEBUG) {
            print "Mapped to($mapped[x],$mapped[y])\n";
          }
          if (DEBUG) {
            print "!!!" . print_r($row->plotStyle->acolor) . "!!!<hr />";
          }
          $row->plotStyle->drawDataPoint($this->img, $mapped['x'], $mapped['y']);
          $labelcolor = !empty($row->plotStyle->alabelcolor) ? $row->plotStyle->alabelcolor : $this->labelcolor;
          if (!empty($point['label'])) {
            $x_offset = max(10, min($mapped['x'] + 5, $this->width - 3 * strlen($point['label'])));
            imagestring($this->img, 1, $x_offset, $mapped['y'] + 5, $point['label'], $labelcolor);
          }
        }
      }
    }
  }

  function draw_image() {
    // finds minimum and maximum
    $this->set_ranges();
    /* bars, lines and points. to keep it all visible. */

    $this->addMeans();
    $this->addDeviations();
    $this->addRegressions();
    $this->drawRows();
    // draws scale
    $this->addScale();
    $this->addLegend();
    ob_start();
    imagepng($this->img);
    return ob_get_clean();
  }

  function rgb_allocate($hex) {
    if (strlen($hex) != 6) {
      return FALSE;
    }
    $hex_split = array(substr($hex, 0, 2), substr($hex, 2, 2), substr($hex, 4, 2));
    foreach ($hex_split as $val) $dec[] = hexdec($val);
    $this->color[$hex] = imagecolorallocate($this->img, $dec[0], $dec[1], $dec[2]);
    return $this->color[$hex];
  }

  function addMeans() {
    foreach ($this->rows as & $row) {
      if (!$row->mean) {
        continue;
      }
      // only draw horizontal mean if y is a measured value.
      $y = $row->dependent != 1;
      // only draw vertical mean if x is a measured value.
      $x = $row->dependent != 0;
      $x_sum = 0;
      $y_sum = 0;
      foreach ($row->dataPoints as $point) {
        $x_sum += $point['x'];
        $y_sum += $point['y'];
      }
      $row->x_mean = $x_sum / count($row->dataPoints);
      $row->y_mean = $y_sum / count($row->dataPoints);
      $map = $this->map_point($row->x_mean, $row->y_mean);
      if (DEBUG) print "Drawing mean line from [35, $map[y]] to [" . ($this->width + 40) . ", $map[y]]\n";
      if ($x) {
        imageline($this->img, 35, $map['y'], $this->width + 40, $map['y'], $row->plotStyle->acolor[0]);
      }
      if ($y) {
        imageline($this->img, $map['x'], 10, $map['x'], $this->height + 15, $row->plotStyle->acolor[0]);
      }
    }
  }

  function addDeviations() {
    foreach ($this->rows as & $row) {
      if (!$row->dev) {
        continue;
      }
      // only draw horizontal mean if y is a measured value.
      $y = $row->dependent != 1;
      // only draw vertical mean if x is a measured value.
      $x = $row->dependent != 0;
      $ellipsis = $row->dependent == 3;
      //var_dump($row->y_mean);
      $center = $this->map_point($row->x_mean, $row->y_mean);

      if ($ellipsis) {
        $xx_sum = 0;
        $xy_sum = 0;
        $n = count($row->dataPoints);
        foreach ($row->dataPoints as $point) {
          $xx_sum += $point['x'] * $point['x'];
          $xy_sum += $point['x'] * $point['y'];
        }
        $b = ($xy_sum - $row->x_mean * $row->y_mean * $n) / ($xx_sum - $row->x_mean * $row->x_mean * $n);
        $a = $row->y_mean - $b * $row->x_mean;
        $angle = rad2deg(atan($b));
        if (DEBUG) print("<hr />Calculated regression as a = $a, b = $b, th = $angle <hr />");

        $vec = [1 / sqrt(1 + $b*$b), $b / sqrt(1 + $b*$b)];
        if (DEBUG) print("<hr />Calculated regression vector as " . print_r($vec, TRUE) . "<hr />");

        $dotsum = 0;
        $antisum = 0;

        foreach ($row->dataPoints as $point) {
          $diff = [$point['x'] - $row->x_mean, $point['y'] - $row->y_mean];
          $dot = $vec[0] * $diff[0] + $vec[1] * $diff[1];
          $dotsum += abs($dot);
          $antidot = [$diff[0] - $vec[0] * $dot, $diff[1] - $vec[1] * $dot];
          if (DEBUG) print("Antidot: " . print_r($antidot, TRUE) . "<br />");
          $antisum += sqrt($antidot[0] * $antidot[0] + $antidot[1] * $antidot[1]);
        }

        if (DEBUG) print("<hr />Calculated sums as $dotsum x $antisum<hr />");

        $devx = sqrt($dotsum / ($n - 1 ));
        $devy = sqrt($antisum / ($n - 1 ));
        $corner = $this->map_point($row->x_mean + $devx, $row->y_mean + $devy);
        $width = ($corner['x']-$center['x'])*2;
        $height = ($corner['y']-$center['y'])*2;

        if (DEBUG) {
          print "Drawing dev ellipse around [$center[x], $center[y]] by $width x $height\n";
        }
        for ($i = 1; $i <= $row->dev; $i++) {
          if (DEBUG) print_r($row->plotStyle->acolor);
          rotatedellipse($this->img, $center['x'], $center['y'], $width * $i, $height * $i, -$angle, $row->plotStyle->acolor[0]);
          imagestring($this->img, 1, $center['x'] + ($width * $i / 2), $center['y'], "$i sigma", $row->plotStyle->acolor[0]);
        }
      }
      else {
        foreach ($row->dataPoints as $point) {
          $x_sum += pow(($point['x'] - $row->x_mean), 2);
          if (DEBUG) print ($point['y'] - $row->y_mean) . "\n\n";
          $y_sum += pow(($point['y'] - $row->y_mean), 2);
        }
        $row->x_dev = sqrt($x_sum / (count($row->dataPoints) - 1));
        $row->y_dev = sqrt($y_sum / (count($row->dataPoints) - 1));
        if (DEBUG) {
          print "Calculated deviations to $row->x_dev, $row->y_dev.\n";
        }

        $corner = $this->map_point($row->x_mean + $row->x_dev, $row->y_mean + $row->y_dev);
        for ($i = 1; $i <= $row->dev; $i++) {
          if ($x) {
            imageline(
              $this->img, $i * $corner['x'] - ($i - 1) * $center['x'], $i * $corner['y'] - ($i - 1) * $center['y'],
              $i * $corner['x'] - ($i - 1) * $center['x'], ($i + 1) * $center['y'] - $i * $corner['y'], $row->plotStyle->acolor[0]
            );
            imageline(
              $this->img, ($i + 1) * $center['x'] - $i * $corner['x'], $i * $corner['y'] - ($i - 1) * $center['y'],
              ($i + 1) * $center['x'] - $i * $corner['x'], ($i + 1) * $center['y'] - $i * $corner['y'], $row->plotStyle->acolor[0]
            );
          }
          if ($y) {
            imageline(
              $this->img, $i * $corner['x'] - ($i - 1) * $center['x'], $i * $corner['y'] - ($i - 1) * $center['y'],
              ($i + 1) * $center['x'] - $i * $corner['x'], $i * $corner['y'] - ($i - 1) * $center['y'], $row->plotStyle->acolor
            );
            imageline($this->img, $i * $corner['x'] - ($i - 1) * $center['x'], ($i + 1) * $center['y'] - $i * $corner['y'],
              ($i + 1) * $center['x'] - $i * $corner['x'], ($i + 1) * $center['y'] - $i * $corner['y'], $row->plotStyle->acolor
            );
          }
        }
      }
      //
    }
  }

  function addRegressions() {
    $resolution = $this->width / 10;
    $stepsize = ($this->max_x - $this->min_x) / $resolution;

    foreach ($this->rows as $row) {
      if (!empty($row->regression)) {
        $start = array('x' => $this->min_x, 'y' => polynomial_y($row->regression, $this->min_x));
        for ($i = 0; $i < $resolution; $i++) {
          $next = array('x' => $start['x'] + $stepsize, 'y' => polynomial_y($row->regression, $start['x'] + $stepsize));
          $pstart = $this->map_point($start['x'], $start['y']);
          $pnext = $this->map_point($next['x'], $next['y']);

          imageline($this->img, $pstart['x'], $pstart['y'], $pnext['x'], $pnext['y'], $row->plotStyle->acolor[0]);
          $start = $next;
        }
      }
    }
  }

  function color($hex) {
    if (strlen($hex) == 3) {
      $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    if (@$this->acolor[$hex]) {
      return @$this->acolor[$hex];
    }
    else return $this->rgb_allocate($hex);
  }

  function get_unix_time($time) {
    if (preg_match('/^[0-9]+$/', $time, $match)) {
      return $match[0];
    }
    elseif (preg_match('/[0-9]{4,4}-[0-9]{2,2}-[0-9]{2,2}( [0-9]{2,2}:[0-9]{2,2})?/', $time, $match)) {
      return strtotime($match[0]);
    }
    else return FALSE;
  }

  function send_header() {
    if (DEBUG) {
      header("Content-type: text/html;charset=utf-8");
    }
    else header("Content-type: image/png;charset=utf-8");
  }

  function map_point($x, $y) {
    if (DEBUG) {
      print "Mapping point [$x, $y] \n";
    }
    if (!$this->max_height)exit("HEIGHT_IS_ZERO");
    if (!$this->max_width)exit("WIDTH_IS_ZERO");
    if (DEBUG) print ($this->max_y - $y) / $this->max_height * $this->height;
    $x_mapped = ceil(($x - $this->min_x) / $this->max_width * $this->width + 40);
    $y_mapped = ceil(($this->max_y - $y) / $this->max_height * $this->height) + 10;
    return array('x' => $x_mapped, 'y' => $y_mapped);
  }

  function forceView($x_min, $y_min, $x_max, $y_max) {
    global $rehzero;
    $this->min_x = $x_min;
    $this->max_x = $x_max;
    $this->min_y = $y_min;
    $this->max_y = $y_max;
    $this->max_height = $this->max_y - $this->min_y;
    $this->max_width = $this->max_x - $this->min_x;
    $this->forced = TRUE;
    $rehzero = $this->map_point(0, 0);
    if ($this->notch_type == 'interval') {
      $this->x_notches = $this->max_width / $this->x_notches;
      $this->y_notches = $this->max_width / $this->y_notches;
    }
    if ($this->x_notch_type == 'interval') {
      $this->x_notches = $this->max_width / $this->x_notches;
    }
    if ($this->y_notch_type == 'interval') {
      $this->y_notches = $this->max_height / $this->y_notches;
    }
  }

  function set_ranges() {
    global $rehzero;
    // do not set range automatically.
    if (!empty($this->forced)) {
      return;
    }
    if (DEBUG) {
      print "Automatically setting scale range.\n";
    }
    if (DEBUG) {
      print "Taking arbitrary extreme values from first row: \n";
    }
    $first_point = array('x' => $this->rows[0]->dimensions['x']['min'], 'y' => $this->rows[0]->dimensions['y']['min']);
    if (DEBUG) {
      print "[$first_point[x], $first_point[y]]\n";
    }
    $this->min_x = $first_point['x'];
    $this->max_x = $first_point['x'];
    $this->min_y = $first_point['y'];
    $this->max_y = $first_point['y'];

    if ($this->rows) foreach ($this->rows as $row) {
      if (DEBUG) {
        print "Comparing with next row: ";
      }
      $this->min_x = min($this->min_x, $row->dimensions['x']['min']);
      $this->max_x = max($this->max_x, $row->dimensions['x']['max']);
      $this->min_y = min($this->min_y, $row->dimensions['y']['min']);
      $this->max_y = max($this->max_y, $row->dimensions['y']['max']);
      if (DEBUG) {
        print "ranges are [$this->min_x, $this->min_y] to [$this->max_x, $this->max_y]\n ";
      }
    }
    if (DEBUG) {
      print "[$this->min_x:$this->max_x],[$this->min_y:$this->max_y]\n";
    }
    $this->max_height = $this->max_y - $this->min_y;
    $this->max_width = $this->max_x - $this->min_x;
    $rehzero = $this->map_point(0, 0);
    if ($this->x_notch_type == 'interval') {
      $this->x_notches = $this->max_width / $this->x_notches;
    }
    if ($this->y_notch_type == 'interval') {
      $this->y_notches = $this->max_height / $this->y_notches;
    }
  }

  function addScale() {
    global $rehzero;
    if (DEBUG) {
      print "Drawing both axes.\n";
    }
    // left vertical axis
    if (DEBUG) {
      print "Zero is at [$rehzero[x], $rehzero[y]]\n";
    }
    //$this->rows[0]->plotStyle->drawDataPoint($this->img, $rehzero['x'], $rehzero['y'], $this->rows[0]->plotStyle->acolor);
    $y_axis = min(max($rehzero['x'], 50), $this->width + 40);
    $x_axis = min(max($rehzero['y'], 10), $this->height + 15);
    if (DEBUG) print "Drawing axis from [$y_axis, 10] to [$y_axis, " . ($this->height + 15) . "] in $this->labelcolor.\n";
    imageline($this->img, $y_axis, 10, $y_axis, $this->height + 15, $this->labelcolor);
    // bottom horizontal axis
    if (DEBUG) print "Drawing axis from [35, $x_axis] to [" . ($this->width + 40) . ", $x_axis] in $this->labelcolor.\n";
    imageline($this->img, 35, $x_axis, $this->width + 40, $x_axis, $this->labelcolor);
    if (!empty($this->x_name)) {
      imagestring($this->img, 1, $this->width - 20, $x_axis - 10, $this->x_name, $this->labelcolor);
    }
    if (!empty($this->y_name)) {
      imagestring($this->img, 1, $y_axis + 10, 10, $this->y_name, $this->labelcolor);
    }
    for ($i = 0; $i <= $this->x_notches; $i++) {
      // scales on the x axis
      $xer = 40 + ceil($i * $this->width / $this->x_notches);
      imageline($this->img, $xer, $x_axis, $xer, $x_axis + 5, $this->labelcolor);
      $label = $this->min_x + ($i / $this->x_notches * $this->max_width);
      if ($this->x_is_date) {
        $label = date($this->label_x, $label);
      }
      elseif ($this->label_x) {
        $label = sprintf($this->label_x, $label);
      }
      imagestring($this->img, 1, $xer - 5, $x_axis + 10, $label, $this->labelcolor);
    }

    for ($i = 0; $i <= $this->y_notches; $i++) {
      // scales on the y axis
      $yer = 10 + ceil($i * $this->height / $this->y_notches);
      imageline($this->img, $y_axis - 5, $yer, $y_axis, $yer, $this->labelcolor);
      $label = $this->max_y - ($i / $this->y_notches * $this->max_height);
      if ($this->label_y) {
        $label = sprintf($this->label_y, $label);
      }
      imagestring($this->img, 1, $y_axis - 38, $yer + 2, $label, $this->labelcolor);
    }
  }

  function addLegend() {
    foreach ($this->rows as $i => $row) {
      if (DEBUG) print "Writing '$row->name' at [40, " . (20 + 10 * $i) . "] in " . $row->plotStyle->acolor[0] . ".\n";
      imagestring($this->img, 2, 40, 20 + 10 * $i, $row->name, $row->plotStyle->acolor[0]);
    }
  }
}
