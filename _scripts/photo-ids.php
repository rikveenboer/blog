<?php
if (!isset($argv[1]) || !is_dir($argv[1])) {
    die('No directory given');
}
$sPath = realpath($argv[1]);
foreach (glob($sPath . '/*.jpg') as $sFile) {
    $sId = substr(sha1_file($sFile), 0, 7);    
    $sId[0] = $sId[0] == '0' ? '1' : $sId[0];
    $sBasename = basename($sFile, '.jpg');
    printf("%s => %s\n", $sBasename, $sId);
    $sNewFile = str_replace($sBasename, $sId, $sFile);
    rename($sFile, $sNewFile);
}