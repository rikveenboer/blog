<?php
require __DIR__ . '/../_php/autoload.php';

use Symfony\Component\Yaml\Yaml;

if (isset($argv[1])) {
    $sDir = realpath(rtrim($argv[1]));
    if (!is_dir($sDir)) {
        die('Directory does not exist');
    }
    $aYaml = getMetaYaml($sDir);
    writeMetaYaml($sDir, $aYaml);
}

function getMetaYaml($sDir, $bFiles = true, $sName = '', $sTitle = '', $sDescription = '') {
    $sDir = rtrim($sDir, '"\'/\\');
    $aMeta = [
        'gallery' => ['name' => $sName, 'title' => $sTitle, 'description' => $sDescription],
        'files' => null
    ];
    if ($bFiles) {
        $aFiles = glob($sDir . '/*.jpg');
        foreach ($aFiles as $sFile) {
            $aMeta['files'][basename($sFile)] = ['title' => '', 'comment' => ''];
        }
    }
    return $aMeta;
}

function writeMetaYaml($sDir, $aYaml) {    
    $sYaml = str_replace("''", null, yamlDump($aYaml));
    file_put_contents($sDir . '/meta.yaml', $sYaml);
}

function yamlDump($aData) {
    return str_replace("'", null, Yaml::dump($aData, 4, 2));
}