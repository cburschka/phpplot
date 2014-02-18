<?php

class StyleLine extends PlotStyle {
  private $width;
  public $color;

  private $lastPoint;

  function StyleLine($color, $width = 1) {
    $this->name = "line";
    $this->width = $width;
    $this->color[0] = $color;
  }

  function drawDataPoint($image, $x, $y) {
    if ($this->lastPoint) {
      if (DEBUG) {
        print "Drawing data line for $image at [$x, $y] in $this->color[0].\n";
      }
      imageline($image, $this->lastPoint['x'], $this->lastPoint['y'], $x, $y, $this->color[0]);
    }
    $this->lastPoint = array('x' => $x, 'y' => $y);
  }
}
