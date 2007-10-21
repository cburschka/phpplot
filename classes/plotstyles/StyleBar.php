<?php
define('BAR_VERTICAL',true);
define('BAR_HORIZONTAL',false);

class StyleBar extends PlotStyle {
	private $vertical;
	private $radius;
	function StyleBar($width, $color=NULL, $vertical=true) {
		$this->name = 'bar';
		$this->radius=$width/2;
		$this->vertical=$vertical;	
		$this->color[0]=$color;	
		$this->color[1]=$color;
		if ($vertical) {
			$this->dimensions['y']['min']=0;
			$this->dimensions['y']['max']=0;
		}
		else {
			$this->dimensions['x']['min']=0;
			$this->dimensions['x']['max']=0;
		}
	}

	function setColor($positive, $negative = NULL, $contour = NULL)
	{
		$this->color[0] = $positive;
		$this->color[1] = $negative ? $negative : $positive;
		if ($contour) $this->color[2] = $contour;
	}	

	function drawDataPoint($image,$x,$y) {
		if (!$this->rehzero) {
			global $rehzero;
			$this->rehzero=$rehzero;
		}
		if ($this->vertical) {
			$x1=$x-$this->radius;
			$x2=$x+$this->radius;
			$y1=$y;
			$y2=$this->rehzero['y'];
			if ($y1>$y2) {
				$y3=$y2;
				$y2=$y1;
				$y1=$y3;
			}
		} else {
			$y1=$y-$this->radius;
			$y2=$y+$this->radius;
			$x1=$x;
			$x2=$this->rehzero['x'];
			if ($y1>$y2) {
				$y3=$y2;
				$y2=$y1;
				$y1=$y3;
			}
		
		}
		if ($this->vertical) $negative = $y > $this->rehzero['y'];
		else $negative = $x < $this->rehzero['x'];
		$color = $this->color[$negative?1:0];
		if (DEBUG) print "Drawing rectangle on $image from [$x1, $y1] to [$x2, $y2] in $color.\n";
		imagefilledrectangle($image,$x1,$y1,$x2,$y2,$color);
		if ($this->color[2]) 
		imagerectangle($image,$x1,$y1,$x2,$y2,$this->color[2]);
		
	}
	
}


?>