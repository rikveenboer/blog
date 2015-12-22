set root=%~dp0..
php -dmemory_limit=1G %root%/_scripts/generate-gallery.php ^
    %1 ^
    %2 ^
    %root%/asset/gallery ^
    %root%/gallery ^
    --export 1920x1080 ^
    --export 200x200 ^
    --export 96x96 ^
    --export 640w
