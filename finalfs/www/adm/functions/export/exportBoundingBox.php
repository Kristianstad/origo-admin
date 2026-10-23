<?php

// Takes a bounding box as "minX,minY,maxX,maxY" or, if $isPolygon is true, a point list "x1,y1,x2,y2,..." (string),
// and the pixel size of the longest side (int). Returns the bounding box (string), adjusted to the pixel proportions,
// together with the image width and height in pixels. Returns null if the coordinates are invalid.
function exportBoundingBox(string $coordinates, bool $isPolygon, int $longestSide): ?array
{
    $box = explode(',', $coordinates);
    if ($isPolygon) {
        $xs = array();
        $ys = array();
        for ($i = 0; $i + 1 < count($box); $i += 2) {
            $xs[] = $box[$i];
            $ys[] = $box[$i + 1];
        }
        if (count($xs) === 0) {
            return null;
        }
        $minX = $xs;
        $minY = $ys;
        $maxX = $xs;
        $maxY = $ys;
        sort($minX);
        sort($minY);
        rsort($maxX);
        rsort($maxY);
        $box = array($minX[0], $minY[0], $maxX[0], $maxY[0]);
    }
    if (count($box) < 4) {
        return null;
    }
    foreach (array_slice($box, 0, 4) as $value) {
        if (!is_numeric($value)) {
            return null;
        }
    }
    $diffX = $box[2] - $box[0];
    $diffY = $box[3] - $box[1];
    if ($diffX == 0 && $diffY == 0) {
        return null;
    }
    if ($diffX >= $diffY) {
        $width = $longestSide;
        $cellSize = $diffX / $width;
        $height = round($diffY / $cellSize);
        $box[3] = $box[1] + ($height * $cellSize);
    } else {
        $height = $longestSide;
        $cellSize = $diffY / $height;
        $width = round($diffX / $cellSize);
        $box[2] = $box[0] + ($width * $cellSize);
    }
    return array('bbox' => implode(',', $box), 'width' => $width, 'height' => $height);
}
