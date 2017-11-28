<?php

    class Raycast {
        public static function isInside($point, $polygon) {
            $polyCorners = count($polygon);
            $j = $polyCorners - 1;
            $oddNodes = false;
            $x = $point.lat;
            $y = $point.long;
            for ($i = 0; $i < $polyCorners; ++$i) {
                $pi = $polygon[$i];
                $pj = $polygon[$j];
                $polyXi = $pi.lat;
                $polyYi = $pi.long;
                $polyXj = $pj.lat;
                $polyYj = $pj.long;
                if (($polyYi < $y && $polyYj >= $y
                        || $polyYj < $y && $polyYi >= $y)
                        && ($polyXi <= $x || $polyXj <= $x)) {
                    if ($polyXi + ($y - $polyYi) / ($polyYj - $polyYi) * ($polyXj - $polyXi) < $x) {
                        $oddNodes = !$oddNodes;
                    }
                }
                $j = $i;
            }
                
            return $oddNodes;
        }
    }

?>