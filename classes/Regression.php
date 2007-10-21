<?php

/*
 * type: 0 - vertical error, 1 - horizontal error, 2 - lode error
 */
function linear_regression($points=array())
{
  $coefficients = array();
  foreach ($points as $point) {
    $c[1] += pow($point['x'], 2);
    $c[2] += $point['x'];
    $c[3] += $point['x']*$point['y'];
    $c[4] += $point['y'];
  }
  $n = count($points);
  $offset = ($c[3]-(2*$c[1]*$c[4]/$c[2])) / (2*$c2 - (2*$n*$c[1]/$c[2]));
  $slope = ($c[3] - $c[2]*$offset ) / $c[1];
  return array($offset, $slope); 
}

function polynomial_regression($points = array(), $degree)
{
  $matrix = array();
  foreach ($points as $i=>$point)
  {
    $x = 1;
    for ($j = 0; $j <= $degree; $j++) {
      $matrix[$i][$j] = $x;
      $x *= $point['x'];
    }
    $vector[$i] = $point['y'];
  }
  $b = matrix_transpose($matrix);
  $product = matrix_multiply($b, $matrix);
      if (DEBUG) var_dump($product);
  $y = matrix_multiply($b, $vector);
  $coefficients = matrix_multiply($product, $y);
}

function matrix_transpose($a)
{
  $b = array();
  for ($i = 0; $i<count($a); $i++)
  {
    for ($j = 0; $j<count($a[$i]); $j++)
    {
      $b[$j][$i] = $a[$i][$j];
    }
  }
  return $b;
}

function matrix_multiply($a, $b)
{
  if (count($a[0]) != count($b)) return false;
  for ($i = 0; $i <= count($a); $i++)
  {
    $product[$i] = array();
    for ($j = 0; $j <= count($b[0]); $j++)
    {
      $product[$i][$j] = 0;
      for ($k = 0; $k < count($a[0]); $k++)
      {
        $product[$i][$j]+= $a[$i][$k]*$b[$k][$j];
      }
    }
  }
  return $product;
}