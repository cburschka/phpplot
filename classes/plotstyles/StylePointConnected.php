<?php
/*
 * Created on 02.12.2006
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
class StylePointConnected extends PlotStyle {
  private $linewidth;
  private $radius;
  private $vertices;
  public $color;
        public $labelcolor;

  private $lastPoint;

  function StylePointConnected($vertices, $radius, $colorpoint, $colorline=NULL, $colorlabel=NULL, $width=1) {
    $this->name="line";
    $this->vertices=$vertices;
    $this->radius=$radius;
    $this->width=$width;
    $this->color[0]=$colorpoint;
    if (!empty($colorline)) $this->color[1] = $colorline;
                else $this->color[1] = $this->color[0];

                if (!empty($colorlabel)) $this->color[2] = $colorlabel;
    else $this->color[2] = $this->color[0];
  }

  function drawDataPoint($image,$x,$y) {
                $this->labelcolor = $this->color[2];
    if (DEBUG) print "Drawing data point for $image at [$x, $y].\n";
    $points=polygon_points(array('x'=>$x,'y'=>$y),$this->vertices,$this->radius);
    //var_dump(count($points));
    if (DEBUG) var_dump($points);
    if (DEBUG) var_dump($this->vertices);
    imagefilledpolygon($image,$points,$this->vertices,$this->color[0]);
    if ($this->lastPoint) {
      if (DEBUG) print "Drawing data line for $image at [$x, $y].\n";
      imageline($image, $this->lastPoint['x'], $this->lastPoint['y'], $x, $y, $this->color[1]);
    }
    $this->lastPoint=array('x'=>$x, 'y'=>$y);
  }
}
