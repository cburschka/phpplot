<?php
/*
 * Created on 02.12.2006
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */

abstract class PlotStyle {
	public $name;
	public $color;
	
	function PlotStyle($color) {
		$this->color=$color;		
	}
	
	function drawDataPoint($image, $x, $y) {
		if (DEBUG) "Accidentally called abstract function.\n";
 	}
	
	
}
?>
