<?php
foreach (glob('blog/_posts/*.md') as $sFile) {
    $sBasename = basename($sFile, '.md');
    printf("%s\n", $sBasename);
}