<?php
$sNewDir = 'blog/_posts.new';
if (!is_dir($sNewDir)) {
   mkdir($sNewDir); 
}

echo "[converting filenames]\n";
$aReplace = [];
foreach (glob('blog/_posts/*.md') as $sFile) {
    $sName = basename($sFile, '.md');    
    printf("%s...\n", $sName);    
    if (preg_match('/-[\d]+$/', $sName, $aMatch)) {
        $sSuffix = $aMatch[0];
        $sNewName = substr($sName, 0, -strlen($sSuffix));
        $sNewFile = sprintf('%s/%s.md', $sNewDir, $sNewName);
        if (file_exists($sNewFile)) {
            echo "    duplicate, appending -x\n";
            $sNewName .= '-x';
            $sNewFile = sprintf('%s/%s.md', $sNewDir, $sNewName);
        }
        $aReplace[$sName] = $sNewName;
        copy($sFile, $sNewFile);
    } else {
        copy($sFile, sprintf('%s/%s.md', $sNewDir, $sName));
    }
}

echo "[converting filenames]\n";
foreach (glob(sprintf('%s/*.md', $sNewDir)) as $sFile) {
    $sName = basename($sFile, '.md');    
    printf("%s...\n", $sName);  
    $sContents = file_get_contents($sFile);
    foreach ($aReplace as $sName => $sNewName) {
        $iCount = substr_count($sContents, $sName);
        if ($iCount > 0) {
            printf("    %s > %s (%d)\n", $sName, $sNewName, $iCount);
            $sContents = str_replace($sName, $sNewName, $sContents);
        }
    }
    // file_put_contents($sFile, $sNewContents);
}