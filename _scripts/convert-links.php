<?php
require __DIR__ . '/utilities.php';

$sOldDir = 'blog/_posts.old';
$sNewDir = 'blog/_posts';
if (!is_dir($sNewDir)) {
   mkdir($sNewDir); 
}

$aPosts = [];
foreach (glob(sprintf('%s/*.md', $sOldDir)) as $sFile) {
    $sBasename = basename($sFile, '.md');
    printf("%s...\n", $sBasename);
    parseFile($sFile, $aYaml, $sContents);

    if (isset($aYaml['end_date'])) {
        $aYaml['end_date'] = date('Y-m-d', $aYaml['end_date']);
    }

    // Replace html links
    if (preg_match_all('~<a.*?href="([^"]+)".*?>([^<]+)</a>~', $sContents, $aMatches, PREG_SET_ORDER)) {
        foreach ($aMatches as $aMatch) {
            $sTitle = preg_match('~title="([^"]+)"~', $aMatch[0], $aTitleMatch) ? sprintf(' "%s"', $aTitleMatch[1]) : null;
            printf("    substituting: %s\n", $aMatch[1]);
            $sContents = str_replace($aMatch[0], sprintf('[%s](%s%s)', $aMatch[2], $aMatch[1], $sTitle), $sContents);
        }
    }

    // Find markdown foot links
    $aLinks = [];
    if (preg_match_all('~[\s]*\[([\d]+)\]:([^\n]+)~', $sContents, $aMatches, PREG_SET_ORDER)) {
        foreach ($aMatches as $aMatch) {
            if (isset($aLinks[$aMatch[1]])) {
                printf("Duplicate id: %d\n", $aMatch[1]);
                exit;
            }
            $aLinks[$aMatch[1]] = $aMatch[2];
            $sContents = str_replace($aMatch[0], null, $sContents);
        }
    }

    // Find references to foot links
    $aTable = [];
    $sNewContents = $sContents;
    if (preg_match_all('~\[([^\]]+)\]\[([^\]]+)\]~', $sContents, $aMatches, PREG_SET_ORDER)) {
        foreach ($aMatches as $aMatch) {
            if (!isset($aLinks[$aMatch[2]])) {
                printf("Undefined id: %d\n", $aMatch[2]);
                exit;
            }
            $sReference = sprintf('[%s][%%d]', $aMatch[1]);
            $aTable[strpos($sContents, $aMatch[0])] = array($sReference, $aLinks[$aMatch[2]]);
            $sNewContents = str_replace($aMatch[0], $sReference, $sNewContents);
        }
    }
    $sContents = $sNewContents;

    // Find all markdown links
    $sNewContents = $sContents;
    if (preg_match_all('~\[([^\]]+)\]\(([^\)]+)(?:[\s]*"([^"]+)")?\)~', $sContents, $aMatches, PREG_SET_ORDER)) {
        foreach ($aMatches as $aMatch) {
            $sLink = sprintf('%s%s', $aMatch[2], isset($aMatch[3]) ? sprintf(' "%s"', $aMatch[3]) : null);
            $sReference = sprintf('[%s][%%d]', $aMatch[1]);
            $aTable[strpos($sContents, $aMatch[0])] = array($sReference, $sLink);
            $sNewContents = str_replace($aMatch[0], $sReference, $sNewContents);
        }
    }
    $sContents = $sNewContents;

    // Place sorted links
    if (count($aTable) > 0) {
        $aLinks = [];
        ksort($aTable);
        foreach (array_values($aTable) as $i => $aLink) {
            $sContents = str_replace($aLink[0], sprintf($aLink[0], $i + 1), $sContents);
            $aLinks[] = sprintf('[%d]: %s', $i + 1, trim($aLink[1]));
        }        
        $sContents = sprintf("%s\n\n%s", implode("\n", $aLinks), trim($sContents));
        $iLinks = count($aLinks);
        printf("    Placing %d link%s!\n", $iLinks, $iLinks > 1 ? 's' : null);
    }

    $sNewFile = sprintf('%s/%s.md', $sNewDir, $sBasename);
    writeFile($sNewFile, $aYaml, $sContents);
    echo "    new file saved!\n";
}