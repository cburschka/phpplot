<?php
/*
 * Created on 02.12.2006
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
class StylePolyPoint extends PlotStyle {
	function StylePolyPoint($vertices,$radius,$color,$filled=true) {
		$this->name="point";
		$this->vertices=$vertices;
		$this->radius=$radius;
		$this->color[0]=$color;		
	}

	function setColor($area, $contour = NULL) {
		if (!empty($area)) $this->color[0] = $area;
		if (!empty($contour)) $this->color[1] = $contour;
	}
	
	function drawDataPoint($image,$x,$y) {
		if (DEBUG) print "Drawing data point for $image at [$x, $y] in $color.\n";
		$points=polygon_points(array('x'=>$x,'y'=>$y),$this->vertices,$this->radius);
		//var_dump(count($points));
		if (DEBUG) var_dump($points);
		if (DEBUG) var_dump($this->vertices);
		imagefilledpolygon($image,$points,$this->vertices,$this->color[0]);
		if (!empty($this->color[1])) imagepolygon($image,$points,$this->vertices,$this->color[1]);
	}
}
?>
