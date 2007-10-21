<?php
/*
 * Created on 02.12.2006
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */

class DataRow {
	public $dataPoints;
	public $plotStyle;
	public $dimensions;
	// how many sizes
	public $sizes;
	// which size is set, which is measured - 0 for y over x, 1 for x over y, 2 for INDEPENDENT SIZES
	// which are evaluated in a SQUARE TOLERANCE
	// 3 for a "dartboard" where points are evaluated on their combined DISTANCE from the mean in a RADIAL TOLERANCE.
	public $dependent; 
	public $mean;
	public $dev;
	
	function DataRow($name, $plotStyle, $dependent=0, $mean=false, $dev=0) {
		$this->plotStyle=$plotStyle;
		$this->name = $name;
		$this->dataPoints=array();
		$this->dependent=$dependent;
		$this->mean=$mean;
		$this->dev=$dev;
	}
	
	function addRegression($degree=1)
	{
		if (DEBUG) print "Calculating regression";
		$this->regression = polynomial_regression($this->dataPoints, $degree);
		if (DEBUG) var_dump($this->regression);
	}

	function addPoint($x,$y, $label=NULL) {
		if (DEBUG) print "Adding new point at [$x, $y]\n";
		$point=array('x'=>$x,'y'=>$y);
		if ($label) $point['label'] = $label;
		$this->dataPoints[] = $point;
		if (empty($this->dimensions['x'])) {
			$this->dimensions['x']['min']=$x;
			$this->dimensions['y']['min']=$y;
		}
		$this->dimensions['x']['min']=min($x,$this->dimensions['x']['min']);
		$this->dimensions['y']['min']=min($y,$this->dimensions['y']['min']);
		$this->dimensions['x']['max']=max($x,$this->dimensions['x']['max']);
		$this->dimensions['y']['max']=max($y,$this->dimensions['y']['max']);
		//if (DEBUG) var_dump($this->dataPoints);
		if (DEBUG) var_dump($this->dimensions);
	}
}
?>
