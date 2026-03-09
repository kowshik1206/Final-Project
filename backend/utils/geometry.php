<?php
// backend/utils/geometry.php

/**
 * Ramer-Douglas-Peucker algorithm for polyline simplification
 * Reduces the number of points in a curve that is approximated by a series of points.
 * 
 * @param array $points Array of ['lat' => float, 'lng' => float]
 * @param float $epsilon Tolerance distance (in degrees, approx 0.0001 for ~11m)
 * @return array Simplified points
 */
function ramer_douglas_peucker($points, $epsilon) {
    if (count($points) < 3) {
        return $points;
    }

    $dmax = 0;
    $index = 0;
    $end = count($points) - 1;

    for ($i = 1; $i < $end; $i++) {
        $d = perpendicular_distance($points[$i], $points[0], $points[$end]);
        if ($d > $dmax) {
            $index = $i;
            $dmax = $d;
        }
    }

    if ($dmax > $epsilon) {
        $recResults1 = ramer_douglas_peucker(array_slice($points, 0, $index + 1), $epsilon);
        $recResults2 = ramer_douglas_peucker(array_slice($points, $index), $epsilon);

        return array_merge(array_slice($recResults1, 0, count($recResults1) - 1), $recResults2);
    } else {
        return [$points[0], $points[$end]];
    }
}

/**
 * Calculate perpendicular distance from point P to line segment AB
 */
function perpendicular_distance($p, $a, $b) {
    $x = $p['lng'];
    $y = $p['lat'];
    $x1 = $a['lng'];
    $y1 = $a['lat'];
    $x2 = $b['lng'];
    $y2 = $b['lat'];

    $A = $x - $x1;
    $B = $y - $y1;
    $C = $x2 - $x1;
    $D = $y2 - $y1;

    $dot = $A * $C + $B * $D;
    $len_sq = $C * $C + $D * $D;

    $param = -1;
    if ($len_sq != 0) { // in case of 0 length line
        $param = $dot / $len_sq;
    }

    $xx = 0;
    $yy = 0;

    if ($param < 0) {
        $xx = $x1;
        $yy = $y1;
    } elseif ($param > 1) {
        $xx = $x2;
        $yy = $y2;
    } else {
        $xx = $x1 + $param * $C;
        $yy = $y1 + $param * $D;
    }

    $dx = $x - $xx;
    $dy = $y - $yy;

    return sqrt($dx * $dx + $dy * $dy);
}
?>
