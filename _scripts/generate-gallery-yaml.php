<?php
require __DIR__ . '/../_php/autoload.php';

use Symfony\Component\Yaml\Yaml;

writeMetaYaml($argv[1]);

function writeMetaYaml($sDir) {
    $sDir = rtrim($sDir, '"\'/\\');
    $aMeta = [
        'gallery' => ['name' => '', 'title' => '', 'description' => ''],
        'files' => null
    ];
    if (!is_dir($sDir)) {
        die('Directory does not exist');
    }
    $aFiles = glob($sDir . '/*.jpg');
    foreach ($aFiles as $sFile) {
        $aMeta['files'][basename($sFile)] = ['title' => '', 'comment' => ''];
    }
    $sYaml = str_replace("''", null, Yaml::dump($aMeta, 4, 2));
    file_put_contents($sDir . '/meta.yaml', $sYaml);
}