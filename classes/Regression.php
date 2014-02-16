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

function polynomial_regression($points = array(), $degree) {
  $mX = array();
  foreach ($points as $i => $point) {
    $x = 1;
    for ($j = 0; $j <= $degree; $j++) {
      $mX[$i][$j] = $x;
      $x *= $point['x'];
    }
    $mY[$i] = array($point['y']);
  }
  $left = matrix_invert(matrix_multiply(matrix_transpose($mX), $mX));
  $right = matrix_multiply(matrix_transpose($mX), $mY);
  $coefficients = matrix_multiply($left, $right);
  foreach ($coefficients as &$c) $c = $c[0];
  return $coefficients;
}

/**
 * Transpose a matrix.
 */
function matrix_transpose($matrix) {
  $transposed = array();
  foreach ($matrix as $i => $row) {
    foreach ($row as $j => $cell) {
      $transposed[$j][$i] = $cell;
    }
  }
  foreach ($transposed as $row) ksort($row);
  ksort($transposed);
  return $transposed;
}

/**
 * Multiply two matrices.
 */
function matrix_multiply($left, $right) {
  // Match left cleft to right height.
  if (count($left[0]) != count($right)) return FALSE;
  for ($i = 0; $i < count($left); $i++) {
    $product[$i] = array();
    for ($j = 0; $j < count($right[0]); $j++) {
      $product[$i][$j] = 0;
      for ($k = 0; $k < count($left[0]); $k++) {
        $product[$i][$j] += $left[$i][$k] * $right[$k][$j];
      }
    }
  }
  return $product;
}

function mprint($matrix) {
  for ($i = 0; $i < count($matrix); $i++) print implode(", ", $matrix[$i]) . "\n";
  print "\n\n";
}

/**
 * Invert a matrix.
 */
function matrix_invert($matrix) {
  // Append the identity to the right.
  for ($i = 0; $i < count($matrix); $i++) {
    for ($j = 0; $j < $i; $j++) $matrix[$i][] = 0;
    $matrix[$i][] = 1;
    for ($j = $i+1; $j < count($matrix); $j++) $matrix[$i][] = 0;
  }

  // Reduce to echelon form.
  for ($i = 0; $i < count($matrix); $i++) {
    // If pivot is 0, switch rows.
    if ($matrix[$i][$i] == 0) {
      for ($j = $i+1; $j < count($matrix); $j++) {
        if ($matrix[$j][$i] != 0) {
          $r = $matrix[$j];
          $matrix[$j] = $matrix[$i];
          $matrix[$i] = $r;
          break;
        }
      }
      if ($matrix[$i][$i] == 0) return FALSE; // Singular.
    }

    // Normalize column to 1 in this row.
    $pivot = $matrix[$i][$i];
    for ($j = 0; $j < count($matrix[$i]); $j++) {
      $matrix[$i][$j] /= $pivot;
    }

    // Reduce the rest of the column to 0.
    for ($j = 0; $j < count($matrix); $j++) {
      if ($j == $i) continue;
      $factor = $matrix[$j][$i];
      for ($k = 0; $k < count($matrix[$j]); $k++) {
        $matrix[$j][$k] -= $factor * $matrix[$i][$k];
      }
    }
  }
  // Return the right half.
  for ($i = 0; $i < count($matrix); $i++) {
    $matrix[$i] = array_slice($matrix[$i], count($matrix));
  }
  return $matrix;
}

function polynomial_y($coefficients, $x) {
  $d = 1;
  $y = 0;
  foreach ($coefficients as $c) {
    $y += $c * $d;
    $d *= $x;
  }
  return $y;
}
