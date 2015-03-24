<?php

function rotatedellipse($im, $cx, $cy, $width, $height, $rotateangle, $colour, $filled=false) {

  $width=$width/2;
  $height=$height/2;

  // This affects how coarse the ellipse is drawn.
  $step=3;

  $cosangle=cos(deg2rad($rotateangle));
  $sinangle=sin(deg2rad($rotateangle));

  // $px and $py are initialised to values corresponding to $angle=0.
  $px=$width * $cosangle;
  $py=$width * $sinangle;

  for ($angle=$step; $angle<=(180+$step); $angle+=$step) {

    $ox = $width * cos(deg2rad($angle));
    $oy = $height * sin(deg2rad($angle));

    $x = ($ox * $cosangle) - ($oy * $sinangle);
    $y = ($ox * $sinangle) + ($oy * $cosangle);

    if ($filled) {
      triangle($im, $cx, $cy, $cx+$px, $cy+$py, $cx+$x, $cy+$y, $colour);
      triangle($im, $cx, $cy, $cx-$px, $cy-$py, $cx-$x, $cy-$y, $colour);
    } else {
      imageline($im, $cx+$px, $cy+$py, $cx+$x, $cy+$y, $colour);
      imageline($im, $cx-$px, $cy-$py, $cx-$x, $cy-$y, $colour);
    }
    $px=$x;
    $py=$y;
  }
}

function triangle($im, $x1,$y1, $x2,$y2, $x3,$y3, $colour) {
   $coords = array($x1,$y1, $x2,$y2, $x3,$y3);
   imagefilledpolygon($im, $coords, 3, $colour);
}
