set root=%~dp0..
echo %1
php -dmemory_limit=1G %root%/_scripts/generate-gallery.php ^
    %1 ^
    %2 ^
    %3 ^
    %4 ^
    --export 1920x1080 ^
    --export 200x200 ^
    --export 96x96 ^
    --export 640w
