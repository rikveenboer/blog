<?php
$a = 'D:\Todo\Gallery';
$f = [];
foreach (glob($a . '/*') as $b) {
    $b = basename($b);
    if (strpos($b, 'spain-2015') === 0) {
        foreach (glob($a . '/' . $b . '/*.jpg') as $c) {
            $f[basename($c)] = $c;
        }
    }
}

$d = 'D:\Rik\Photographs\Reis\Spanje 2015';
$e = [];
foreach (glob($d . '/*') as $b) {
    if ($b != '.' && $b != '..') {
        if (is_dir($b)) {
            foreach (glob($b . '/*.jpg') as $c) {
                $e[basename($c)] = $c;
            }
        }
    }
}
$h = array_intersect_key($f, $e);
$g = array_keys($h);

foreach ($h as $i => $x) {
    copy($e[$i], $x);
}

print_r(array_keys(array_diff_key($f, $h)));