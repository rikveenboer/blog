<?php
require __DIR__ . '/utilities.php';

$sDir = 'blog/_posts';
if (!is_dir($sDir)) {
   mkdir($sDir); 
}

$oPDO = new PDO('mysql:dbname=blog;host=localhost;port=3311', 'root', 'root');

$aNew = array_map(function ($sFile) {
    return basename($sFile, '.yml');
}, glob('_data/gallery/*.yml'));

$aIds = [];
foreach (glob(sprintf('%s/*.md', $sDir)) as $sFile) {
    parseFile($sFile, $aYaml, $sContents);
    if (preg_match_all('~\[nggallery id=(\d+)\]~', $sContents, $aMatches, PREG_SET_ORDER)) {
        foreach ($aMatches as $aMatch) {
            $aIds[] = $aMatch[1];
        }
    }
}
$aIds = array_unique($aIds);

$sManual = <<<EOF
50 2014-edinburgh
57 2014-conference-usic
60 2014-autumn-holidays
64 2014-geneva
65 2014-geneva-history-museum
66 2014-geneva-maison-tavel
67 2015-christmas-holidays
68 2014-christmas-holidays-ikea
69 2014-christmas-holidays-dwingelderveld
70 2014-christmas-holidays-beach
76 2015-birthday-holidays
78 2015-spain-portrait
79 2015-spain-tarragona
80 2015-spain-sitges
81 2015-spain-barcelona
82 2015-spain-barcelona-stores
83 2015-summer-aberdeen
84 2015-summer-aberdeen-tolbooth
85 2015-summer-dunnottar-castle
86 2015-summer-holidays
87 2015-northen-ireland-belfast
88 2015-northen-ireland-giants-causeway
89 2015-northen-ireland-dark-hedges
90 2015-visit-sanne-highlands-hike
91 2015-visit-sanne-highlands-distillery
92 2015-visit-sanne-highlands-perth
93 2015-visit-sanne-fife-pittenweem
94 2015-visit-sanne-fife-anstruther
95 2015-visit-sanne
97 2015-autumn-holidays
102 2015-coastal-path
EOF;
$aFixed = [];
foreach (explode("\n", $sManual) as $sManual) {
    list($iId, $sGallery) = explode(' ', $sManual);
    if (!in_array($sGallery, $aNew)) {
        printf("unknown %s\n", $sGallery);
    } else {
        $aFixed[$iId] = $sGallery;
    }
}

$aUnused = array_flip($aNew);
$aOld = $aManual = [];
$sQuery = 'SELECT gid, name FROM wp_ngg_gallery';
if ($oStatement = $oPDO->query($sQuery)) {
    foreach ($oStatement as $aRow) {
        $sOld = $aRow[1];
        if (!in_array($aRow[0], $aIds)) {
            printf("unused %s (%d)\n", $aRow[1], $aRow[0]);
        }
        foreach ($aNew as $sNew) {
            if (isset($aFixed[$aRow[0]])) {
                continue 2;
            }
            if (strpos($sNew, $sOld) !== false) {
                unset($aUnused[$sNew]);
                $aOld[$aRow[0]] = $sNew;
                continue 2;
            }
        }
        $aManual[] = sprintf('%d %s', $aRow[0], $aRow[1]);      
    }
}
file_put_contents('tmp1', implode("\n", array_keys($aUnused)));

if (count($aManual) > 0) {
    printf("fix manual entries");
    file_put_contents('tmp2', implode("\n", $aManual));
    exit();
}

$aLookup = array_replace($aFixed, $aOld);
$aUnknown = array_diff($aIds, array_keys($aLookup));
if (count($aUnknown) > 0) {
    printf("fix unknown entries");
    file_put_contents('tmp3', implode("\n", $aUnknown));
    exit();
}

foreach (glob(sprintf('%s/*.md', $sDir)) as $sFile) {
    $sBasename = basename($sFile, '.md');
    printf("%s...\n", $sBasename);
    parseFile($sFile, $aYaml, $sContents);

    if (isset($aYaml['end_date'])) {
        $aYaml['end_date'] = date('Y-m-d', $aYaml['end_date']);
    }

    if (preg_match_all('~\[nggallery id=(\d+)\]~', $sContents, $aMatches, PREG_SET_ORDER)) {
        printf("    number of galleries = %d\n", count($aMatches));
        foreach ($aMatches as $aMatch) {
            $sGallery = $aLookup[$aMatch[1]];
            $sReplace = sprintf("{%% include gallery.html gallery='%s' %%}", $sGallery);
            $sContents = str_replace($aMatch[0], $sReplace, $sContents);            
        }
        writeFile($sFile, $aYaml, $sContents);   
        echo "    new file saved!\n";
    }
}