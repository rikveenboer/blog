<?php
require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$sNewDir = 'blog/_posts.new';
if (!is_dir($sNewDir)) {
   mkdir($sNewDir); 
}

foreach (glob('blog/_posts/*.md') as $sFile) {
    $sBasename = basename($sFile);
    
    printf("%s...\n", $sBasename);
    
    $sContents = file_get_contents($sFile);
    $aContents = explode("\n", $sContents);
    $aParts = explode('--', implode("\n", array_slice($aContents, 1)));
    $aYaml = Yaml::parse(trim(str_replace("  - \n", null, current($aParts))));
    
    $sContents = end($aParts);
    if (!preg_match_all('~\[caption(?:[^\]]+)width="([\d]+)"\]<img src="([^"]+)"(?: width="([^"]+)")? /> ([^\[]+)\[/caption\]~', $sContents, $aMatches, PREG_SET_ORDER)) {
        echo "    no caption found\n";
        continue;
    }

    foreach ($aMatches as $aMatch) {
        $sCaption = sprintf("{%% include caption.html\n    width='%d'%s\n    image='%s'\n    text='%s'\n%%}", $aMatch[1], empty($aMatch[1]) ? null : sprintf("\n    image-width='%d'", $aMatch[1]), $aMatch[2], $aMatch[4]);
        $sContents = str_replace($aMatch[0], $sCaption, $sContents);
    }

    $sNewFile = sprintf('%s/%s', $sNewDir, $sBasename);
    $sNewContents = '---' . "\n" . yamlDump($aYaml) . '---' . "\n" . trim($sContents);
    file_put_contents($sNewFile, $sNewContents);
    echo "    new file saved!\n";
}

function yamlDump($aData) {
    return str_replace("'", null, Yaml::dump($aData, 4, 2));
}