# Blog #

This is my personal blog, powered by [Jekyll](http://jekyllrb.com/). This document should not be considered as a comprehensive manual that explains all features, but rather as an invitation to explore the source code to see how things work.

## License and credits ##

Unless noted otherwise, the source code is licensed under GPL License and all other content under CC-BY.

The source code has been forked from [Danny Berger's blog](https://github.com/dpb587/dpb587.me) that features a great system for incorporating [photo galleries](https://dpb587.me/blog/2014/04/08/photo-galleries-for-jekyll.html). For ease of maintenance, the separate repository of this [plugin](https://github.com/dpb587/jekyll-gallery) has been merged into this repository.

A major overhaul of this system allows data for each gallery to be stored in [YAML format](http://jekyllrb.com/docs/datafiles/) in a single file in the *_data/gallery* directory.  The possibility to loop over galleries and photos in Liquid and Ruby obviates the need for looping over files with the previously used [loopdir](https://github.com/dpb587/dpb587.me/blob/master/_plugins/loopdir.rb) plugin. This allows for [generation](http://jekyllrb.com/docs/plugins/#generators) of all gallery pages from templates by a simple plugin (see below).

## Setup ##

PHP dependencies are defined in *composer.json* and should be installed using [Composer](https://getcomposer.org/). PHP version 5.3 with PECL intl is required. Ruby dependencies (Jekyll plugins) are defined in *Gemfile* and should be installed using [Bundler](http://bundler.io/). Python dependencies are defined in *requirements.txt* and should be installed using [pip](https://pypi.python.org/pypi/pip).

## Plugins ##

Links to the original source code of plugins can be found in their files.

- assign_page

Useful for reading variables from the frontmatter of a page into a variable. Is not currently being used.

- gallery

Generates pages for a list of galleries and for each gallery: an index page, a slideshow page, a map page and pages for each photo. Galleries can be omitted from the list of gallery by setting *ignore=true* in the gallery data file. Likewise, the slideshow page can be disabled by setting *slideshow=false* and the map page is only generated when the *map* variable is set. Links to these pages in the gallery index page are set accordingly. Photos are sorted by date and pagination is added dynamically. This layouts used by this plugin are prefixed *gallery-* and should be self-explanatory.

- hyphenate

Provides filter *hyphenate* that hyphenates long words, taking account the *language* set in *_config.yml*. Needs the *text-hyphen* gem.

- post_baseurl

Provides tag *post_baseurl* that behaves exactly as the built-in *post_url* but prepends site.baseurl. Is used to obtain valid urls when sites are served from subdirectories.
		

## Scripts ##

The *_scripts* directory contains various PHP scripts that are used to maintain photo galleries. Going through the version history of this directory, many deleted scripts can be found that were used to migrate the former Wordpress content of this blog to Markdown content.  

- generate-gallery.php

Generates both a gallery data file and resized copies of all photos in a specified directory.

- gps-jpg2raw.php

Reads location and orientation data from JPG files and writes those in the corresponding DNG files. Useful if your camera does not do so automatically. Relies on [exiftool](http://www.sno.phy.queensu.ca/~phil/exiftool/).

- convert-links.php

Scans blog posts and converts html links to Markdown links. All links are numbered and placed in the top of the post. This enhances readability and allows for putting links in excerpts.


