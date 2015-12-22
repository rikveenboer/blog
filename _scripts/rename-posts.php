<?php
require __DIR__ . '/../_php/autoload.php';

$sNewDir = 'blog/_posts.new';
if (!is_dir($sNewDir)) {
   mkdir($sNewDir); 
}

$sRename = <<<EOF
2013-12-08-guy-fawkes-bonfire 2013-11-05-guy-fawkes-bonfire
2013-12-08-internationals-trip 2013-11-23-internationals-trip
2013-12-08-visit-bram 2013-11-29-visit-bram
2013-12-08-visit-dundee 2013-11-24-visit-dundee
2013-12-08-visit-school-of-chemistry
2013-12-08-whisky-tasting 2013-11-21-whisky-tasting
2013-12-12-filmsoc-deep-red 2014-02-13-filmsoc-deep-red
2013-12-12-filmsoc-howls-moving-castle 2013-11-07-filmsoc-howls-moving-castle
2013-12-12-hi-there
2014-01-12-christmas-holidays
2014-01-27-cinema-dundee 2014-01-23-cinema-dundee
2014-01-27-dinner-simone 2014-01-12-dinner-simone
2014-01-27-glasgow
2014-01-27-strathkinness 2014-01-25-strathkinness
2014-01-28-explorations
2014-01-28-laser-tag 2014-01-27-laser-tag
2014-02-08-internationals-trip
2014-02-08-whisky-tasting 2014-02-06-whisky-tasting
2014-02-08-whisky-tasting-x 2014-02-27-whisky-tasting
2014-02-21-internationals-dinner
2014-02-21-whisky-tasting 2014-02-20-whisky-tasting
2014-03-14-60-hour-film-blitz-screening 2014-03-13-60-hour-film-blitz-screening
2014-03-14-ski-glenshee 2014-03-09-ski-glenshee
2014-03-15-pancake-day-celebration 2014-03-05-pancake-day-celebration
2014-03-15-week-10
2014-03-19-cinema-dundee 2014-03-15-cinema-dundee
2014-03-19-fish-and-chips 2014-03-14-fish-and-chips
2014-03-19-goodbye-yusuke 2014-03-17-goodbye-yusuke
2014-03-23-crail
2014-04-08-whisky-tasting 2014-04-03-whisky-tasting
2014-04-14-london 2014-04-10-london
2014-04-18-whisky-tasting 2014-04-03-whisky-tasting
2014-04-19-cinema-dundee 2014-04-06-cinema-dundee
2014-04-21-easter-bbq 2014-04-20-easter-bbq
2014-05-04-coastal-path
2014-05-04-may-dip 2014-05-01-may-dip
2014-05-18-whisky-tasting 2014-05-10-whisky-tasting
2014-05-19-cinema-dundee 2014-05-18-cinema-dundee
2014-05-19-jose-birthday-bbq 2014-05-16-jose-birthday-bbq
2014-05-21-evening-run
2014-05-23-anstruther 2014-05-18-edinburgh
2014-05-25-edinburgh 2014-05-24-edinburgh
2014-05-25-goodbye-julie 2014-05-24-goodbye-julie
2014-06-04-birthday-holidays
2014-06-09-beach-bbq 2014-06-09-beach-bbq 2014-06-06-beach-bbq 2014-06-09-beach-bbq
2014-06-14-netherlands-spain 2014-06-13-netherlands-spain
2014-06-24-hike-blair-athol 2014-06-22-hike-blair-athol
2014-06-28-goodbye-hubert
2014-06-29-car
2014-06-29-cinema-dundee-x 2014-07-03-cinema-dundee-x
2014-06-29-cinema-dundee 2014-06-28-cinema-dundee
2014-06-30-world-cup
2014-07-07-car
2014-07-07-housewarming-bbq 2014-07-04-housewarming-bbq
2014-07-13-cinema-dundee
2014-07-20-car
2014-08-25-summer-holidays 2014-08-14-summer-holidays
2014-08-28-moving
2014-08-31-moving
2014-09-01-bike
2014-09-02-risotto-night
2014-09-07-freezer
2014-09-09-congress-glasgow 2014-09-04-conference-glasgow
2014-09-09-goodbye-alan 2014-08-29-goodbye-alan
2014-09-10-car
2014-09-14-sports
2014-09-26-whisky-tasting 2014-09-25-whisky-tasting
2014-09-27-autumn-holidays
2014-10-05-italian-birthdays 2014-09-27-italian-birthdays
2014-10-05-sports
2014-10-05-weekend
2014-10-10-whisky-tasting 2014-10-08-whisky-tasting
2014-10-11-whisky-tasting 2014-11-06-whisky-tasting
2014-10-15-whisky-tasting 2014-11-13-whisky-tasting
2014-11-08-explorations
2014-11-29-movember
2014-12-05-christmas-lunch 2014-12-04-christmas-lunch
2014-12-05-kerstsfeer
2014-12-05-whisky-tasting 2014-12-04-whisky-tasting
2014-12-16-oost-europees-feest 2014-12-13-oost-europees-feest
2014-12-17-edinburgh-christmas-market 2014-12-14-edinburgh-christmas-market
2015-01-05-christmas-holidays 2014-12-20-christmas-holidays
2015-01-05-geneve
2015-01-17-ikea 2015-01-18-ikea
2015-01-20-basketball
2015-02-01-bezoek-angela 2015-01-26-bezoek-angela
2015-02-07-whisky-tasting 2015-02-05-whisky-tasting
2015-02-14-chocolate-and-whisky-tasting 2015-02-12-chocolate-and-whisky-tasting
2015-02-22-half-marathon
2015-02-22-international-dinner 2015-02-21-international-dinner
2015-02-22-whisky-tasting 2015-02-19-whisky-tasting
2015-02-27-chemsoc-pub-quiz
2015-03-01-muddy-explorations
2015-03-07-whisky-tasting 2015-03-05-whisky-tasting
2015-03-20-lazy-friday
2015-03-22-60-hour-film-blitz-screening 2015-03-12-60-hour-film-blitz-screening
2015-03-22-60-hour-film-blitz 2015-03-07-60-hour-film-blitz
2015-03-22-goodbye-jan 2015-03-19-goodbye-jan
2015-03-26-thursday-drinks
2015-03-28-special-whisky-tasting 2015-03-26-special-whisky-tasting
2015-04-03-whisky-tasting 2015-04-02-whisky-tasting
2015-04-11-easter-lunch 2015-04-04-easter-lunch
2015-04-11-spring-activities
2015-04-12-round-the-houses
2015-04-18-whisky-tasting 2015-04-16-whisky-tasting
2015-04-19-night-run 2015-04-20-night-run
2015-04-27-coastal-trail
2015-04-27-may-dip 2015-05-01-may-dip
2015-04-27-some-saturday
2015-06-05-birthday-holidays 2015-05-28-birthday-holidays
2015-06-05-goodbye-alba 2015-05-20-goodbye-alba
2015-06-20-bbq-bonfire
2015-06-20-beach-bbq
2015-06-20-summer-activities
2015-06-20-whisky-poker 2015-06-18-whisky-poker
2015-07-13-spain
2015-07-14-half-marathon 2015-07-12-half-marathon
2015-07-19-aberdeen-and-dunnottar-castle
2015-08-13-summer-holidays
2015-08-21-northern-ireland 2015-08-15-northern-ireland
2015-08-24-visit-sanne 2015-08-19-visit-sanne
2015-08-26-car
2015-08-27-whisky-poker 2015-08-26-whisky-poker
2015-09-20-autumn-holidays 2015-09-12-autumn-holidays
2015-10-05-chariots-of-fire-run 2015-10-04-chariots-of-fire-run
2015-10-05-giffordtown-run 2015-09-28-giffordtown-run
2015-10-06-running
2015-10-12-hike-glen-clova
2015-10-12-kingsbarn-distillery-tour 2015-10-11-kingsbarn-distillery-tour
2015-10-15-whisky-poker 2015-10-14-whisky-poker
2015-11-17-braids-hill-race
2015-11-19-whisky-poker
2015-11-20-whisky-tasting 2015-11-05-whisky-tasting
2015-12-04-whisky-tasting 2015-12-03-whisky-tasting
2015-12-06-coastal-path
EOF;

$aRename = explode("\n", $sRename);
foreach ($aRename as $i => $sRename) {
    unset($aRename[$i]);
    $aSplit = explode(' ', $sRename);
    if (count($aSplit) > 1) {
        list($sOld, $sNew) = explode(' ', $sRename);
        $aRename[$sOld] = $sNew;
    }
}

$aPosts = [];
foreach (glob('blog/_posts/*.md') as $sFile) {
    $sBasename = basename($sFile, '.md');
    $sContents = file_get_contents($sFile);

    foreach ($aRename as $sOld => $sNew) {
        if (strpos($sContents, $sOld) !== false) {
            $sContents = str_replace($sOld, $sNew, $sContents);
        }
    }
    $sNewFile = sprintf('%s/%s.md', $sNewDir, $sBasename);
    
    file_put_contents($sNewFile, $sContents);
}

foreach ($aRename as $sOld => $sNew) {
    $sOldFile = sprintf('%s/%s.md', $sNewDir, $sOld);
    $sNewFile = sprintf('%s/%s.md', $sNewDir, $sNew);
    rename($sOldFile, $sNewFile);
}
