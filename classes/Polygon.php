<?php
/*
 * Created on 02.12.2006
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 
 function polygon_points($center,$vertices,$radius) {
	if (DEBUG) print "Calculating $vertices points around [$center[x], $center[y]].\n";
 	$points=array();
 	$points[]=$center['x'];
 	$points[]=$center['y']+$radius;
 	$angle=2*pi()/$vertices;
 	for ($i=1;$i<$vertices;$i++) {
 		$y=$radius*cos($angle*$i) + $center['y'];
 		$x=$radius*sin($angle*$i) + $center['x'];
 		$points[]=$x;
 		$points[]=$y;
 	}
 	return $points;
 }
?>
