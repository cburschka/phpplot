<?php

abstract class PlotStyle {
  public $name;
  public $color;

  function PlotStyle($color) {
    $this->color = $color;
  }

  function drawDataPoint($image, $x, $y) {
    if (DEBUG) {
      "Accidentally called abstract function.\n";
    }
  }
}
