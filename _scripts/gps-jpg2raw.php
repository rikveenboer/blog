<?php
require __DIR__ . '/../_php/autoload.php';

use Monolog\Logger;
use PHPExiftool\Reader;
use PHPExiftool\Writer;
use PHPExiftool\Driver\Metadata\Metadata;
use PHPExiftool\Driver\Metadata\MetadataBag;

if (!isset($argv[1])) {
    die('No directory given!');
}
$sDir = rtrim($argv[1], '"\'/\\');
if (!file_exists($sDir)) {
    die('Directory does not exist!');
}

// Scan JPG and DNG files
$aJpg = glob(sprintf('%s%s*.jpg', $sDir, DIRECTORY_SEPARATOR));
$aDng = glob(sprintf('%s%s*.dng', $sDir, DIRECTORY_SEPARATOR));

printf("Found %d JPG files.\n", count($aJpg));
printf("Found %d DNG files.\n", count($aDng));

// Strip directory and extension
$aJpgFiles = array_map(function($sValue) {
    return basename($sValue, '.jpg');
}, $aJpg);
$aDngFiles = array_map(function($sValue) {
    return basename($sValue, '.dng');
}, $aDng);

// Remove JPG files without corresponding DNG file
$aFiles = array_intersect($aJpgFiles, $aDngFiles);
foreach (array_diff($aJpgFiles, $aDngFiles) as $sJpg) {
    printf("Omitting %s.jpg\n", $sJpg);
    // unlink(sprintf('%s/%s.jpg', $sDir, $sJpg));
}

// Initialize exiftool
$oLogger = new Logger('exiftool');
$oReader = Reader::create($oLogger);
$oWriter = Writer::create($oLogger);

// Loop over files
$iFiles = count($aFiles);
foreach ($aFiles as $i => $sFile) {
    printf("Processing %s (%d/%d)...\n", $sFile, $i, $iFiles);
    
    // Regenerate JPG and DNG file paths
    $sJpgPath = sprintf('%s%s%s.jpg', $sDir, DIRECTORY_SEPARATOR, $sFile);
    $sDngPath = sprintf('%s%s%s.dng', $sDir, DIRECTORY_SEPARATOR, $sFile);

    // Read from JPG file
    printf("    > Reading from JPG file.\n");
    $oFileEntity = $oReader->files($sJpgPath)->first();        
    $oReader->reset();

    // Extract desired GPS tags
    $aData = $aMetadataBag = array();        
    $aKeys = array(
        'GPS:GPSAltitude' => 'altitude',
        'GPS:GPSLongitude' => 'longitude',
        'GPS:GPSLatitude' => 'latitude',
        'GPS:GPSImgDirection' => 'direction');

    // Loop over all metadata
    printf("    > Parsing metadata.\n");
    foreach ($oFileEntity as $oMetaData) {
        $oTag = $oMetaData->getTag();
        $sTag = $oTag->getTagname();
        $oValue = $oMetaData->getValue();

        // Store desired tags
        if (array_key_exists($sTag, $aKeys)) {
            $aData[$aKeys[$sTag]] = $oValue;
        }

        // Store all GPS tags in metadata bag
        if (strpos($sTag, 'GPS:') !== false) {
            $aMetadataBag[] = $oMetaData;
        }        
    }

    // Check presence of GPS data
    if (empty($aData)) {
        printf("    > No GPS data present!\n");
        continue;
    }
    
    // Convert values to floats
    $aData = array_map(function ($sValue) {
            return floatval($sValue->asString());
        }, $aData);

    // Write to DNG file
    printf("    > Writing to DNG file.\n");
    $oWriter->write($sDngPath, new MetadataBag($aMetadataBag));
}