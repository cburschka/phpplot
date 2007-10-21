<?php
/*
 * Created on 02.12.2006
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */

class DataRowBar extends DataRow {
	public $dataPoints;
	public $plotStyle;
	public $dimensions;
	public $mean;
	public $dev;
	
	function DataRowBar($plotStyle, $mean=false, $dev=0) {
		$this->plotStyle=$plotStyle;
		$this->dataPoints=array();
		$this->mean=$mean;
		$this->dev=$dev;
		$this->dimensions['y'] = array('min' => 0, 'max' => 0);
		$this->dimensions['x'] = array('min' => 0);
	}

	function addPoint($y, $label=NULL) {
		if (DEBUG) print "Adding new point at [$y]\n";
		$x = count($this->dataPoints) + 1;
		$point=array('x'=>$x,'y'=>$y);
		if ($label) $point['label'] = $label;
		$this->dataPoints[] = $point;
		$this->dimensions['y']['min']=min($y,$this->dimensions['y']['min']);
		$this->dimensions['y']['max']=max($y,$this->dimensions['y']['max']);
		$this->dimensions['x']['max']=$x + 1;
		//if (DEBUG) var_dump($this->dataPoints);
		if (DEBUG) var_dump($this->dimensions);
	}
}
?>
