<?php
require __DIR__ . '/../_php/autoload.php';

use Symfony\Component\Yaml\Yaml;

function parseFile($sFile, &$aYaml, &$sContents = null) {
    $sContents = file_get_contents($sFile);
    $aContents = explode("\n", $sContents);
    $aParts = explode('---', implode("\n", array_slice($aContents, 1)));  
    $aYaml = Yaml::parse(trim(str_replace("  - \n", null, current($aParts))));
    $sContents = trim(implode('---', array_slice($aParts, 1)));
}

function writeFile($sFile, $aYaml, $sContents = null) {
    file_put_contents($sFile, '---' . "\n" . yamlDump($aYaml) . '---' . "\n" . trim($sContents));
}

function yamlDump($aData) {
    return str_replace("'", null, Yaml::dump($aData, 4, 2));
}

function normaliseName($sName) {
    return preg_replace('/(-| )+/', '-', preg_replace('/[^a-z0-9 ]/i', '-', preg_replace('/\'/', '', strtolower(preg_replace('/\p{Mn}/u', '', Normalizer::normalize($aPhoto['title'], Normalizer::FORM_KD))))));
}

function coordinateToDegrees($aCoordinate, $sHemisphere) {
    $aCoordinate = array_map('fractionToFloat', $aCoordinate);
    $aDegrees = array_map(function ($a, $b) {
            return $a / $b;
        }, $aCoordinate, array(1, 60, 3600));
    $iFlip = ($sHemisphere == 'W' or $sHemisphere == 'S') ? -1 : 1;
    return $iFlip * array_sum($aDegrees);
}

function fractionToFloat($sFraction) {
    $aParts = explode('/', $sFraction);
    $iParts = count($aParts);
    return $iParts
        ? ($iParts > 1
            ? floatval($aParts[0]) / floatval($aParts[1])
            : $aParts[0])
        : 0;
}

function ksort_recursive(&$aArray, $mSortFlags = SORT_REGULAR) {
    if (!is_array($aArray)) {
        return false;
    }
    foreach ($aArray as &$aSubarray) {
        ksort_recursive($aSubarray, $mSortFlags);
    }
    ksort($aArray, $mSortFlags);
    return true;
}