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