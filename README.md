# Blog #

This is my personal blog, powered by [Jekyll](http://jekyllrb.com/). This document should not be considered as a comprehensive manual that explains all features, but rather as an invitation to explore the source code to see how things work.

## License ##

Unless noted otherwise, the source code is licensed under GPL License and all other content under CC-BY.

## Credits ##

The source code has been forked from [Danny Berger's blog](https://github.com/dpb587/dpb587.me) that features a great system for incorporating [photo galleries](https://dpb587.me/blog/2014/04/08/photo-galleries-for-jekyll.html). For ease of maintenance, the separate repository of this [plugin](https://github.com/dpb587/jekyll-gallery) has been merged into this repository.

A major overhaul of this system allows data for each gallery to be stored in [YAML format](http://jekyllrb.com/docs/datafiles/) in a single file in the *_data/gallery* directory.  The possibility to loop over galleries and photos in Liquid and Ruby obviates the need for looping over files with the previously used [loopdir](https://github.com/dpb587/dpb587.me/blob/master/_plugins/loopdir.rb) plugin. This allows for [generation](http://jekyllrb.com/docs/plugins/#generators) of all gallery pages from templates by a simple plugin (see below). Empirically, build times were found to be reduced by a factor of 2 to 3.

## Setup ##

PHP dependencies are defined in *composer.json* and should be installed using [Composer](https://getcomposer.org/). PHP version 5.3 with PECL intl is required. Ruby dependencies (Jekyll plugins) are defined in *Gemfile* and should be installed using [Bundler](http://bundler.io/). Python dependencies are defined in *requirements.txt* and should be installed using [pip](https://pypi.python.org/pypi/pip).

## Plugins ##

Links to the original source code of plugins can be found in their files.

- assign_page

Useful for reading variables from the frontmatter of a page into a variable. Is not currently being used.

- gallery

Generates pages for a list of galleries and for each gallery: an index page, a slideshow page, a map page and pages for each photo. Galleries can be omitted from the list of gallery by setting *ignore=true* in the gallery datafile. Likewise, the slideshow page can be disabled by setting *slideshow=false* and the map page is only generated when the *map* variable is set. Links to these pages in the gallery index page are set accordingly. Photos are sorted by date and pagination is added dynamically. This layouts used by this plugin are prefixed *gallery-* and should be self-explanatory.

- hyphenate

Provides filter *hyphenate* that hyphenates long words, taking account the *language* set in *_config.yml*. Needs the *text-hyphen* gem.

- post_baseurl

Provides tag *post_baseurl* that behaves exactly as the built-in *post_url* but prepends site.baseurl. Is used to obtain valid urls when sites are served from subdirectories.
		

## Scripts ##

The *_scripts* directory contains various PHP scripts that are used to maintain photo galleries. Going through the version history of this directory, many deleted scripts can be found that were used to migrate the former Wordpress content of this blog to Markdown content.  

- generate-gallery.php

Generates both a gallery datafile and resized copies of all photos in a specified directory.

- gps-jpg2raw.php

Reads location and orientation data from JPG files and writes those in the corresponding DNG files. Useful if your camera does not do so automatically. Relies on [exiftool](http://www.sno.phy.queensu.ca/~phil/exiftool/).

- convert-links.php

Scans blog posts and converts html links to Markdown links. All links are numbered and placed in the top of the post. This enhances readability and allows for putting links in excerpts.

## Gallery ##

Various PHP scripts, Liquid macros, HTML templates and a Ruby plugin simplify the incorporation of photo galleries into the blog. To add a new gallery, these step should be followed:

1. Put all properly tagged (e.g. location, date) photos in a single directory without subdirectories.
2. Run the PHP helper script:

		php -dmemory_limit=1G _scripts/generate-gallery.php
		    {gallery name}
		    {photo source directory}
		    asset/gallery
		    --export 1920x1080
		    --export 200x200
		    --export 96x96
		    --export 640w

	These options are documented in the script itself as well. While configurable, the sizes below 1920x1080 should always be kept since these thumbnails are required (hardcoded) by various parts of the gallery system. This reads EXIF data from all photos and generates a new gallery datafile. Photos are processed appropiately (see script for details) and resized versions are stored in the specified directy, in this case: *asset/gallery/{gallery name}/{calculated hash}-{dimensions}.jpg*. In contrast to the original script that used a dump of iPhoto data, no photo names or titles are kept. This data might eventually be read from the JPG or DNG files, or from processing software (preferrably Lightroom)
3. Check the generated datafile and (optionally) add name and description data.
4. Use the various macros as described below to include photos and galleries in blog posts.

## Directories ##

While mostly self-explanatory, the location of directories that contain the majority of the actual content are listed here.

- _data/gallery

Datafiles in the collection *gallery* are stored here. Each file in this directory contains all data for (the photos in) one gallery. Files are named *{year}-{gallery name}.yml*.

- blog/_posts

Posts in the (yet only) category *blog* are stored here. As per Jekyll standards, files are named *{yyyy-mm-dd}-{post name}.md*. Files contain frontmatter in YAML format and actual content in Markdown format.

- asset

This directory is not kept under source control and might be stored outside the repository. The webserver should be configured accordingly. Three subdirectories store specific files:

- asset/gallery

Directories named after the corresponding galleries store (thumbnails) of the photos.

- asset/header

Header images (currently 1600x230 pixels) are stored here. Keeping names to the first seven characters of the sha1 hash of the file is recommended. The *headers* array set in *_config.yml* holds a (short)list of headers used randomly (in *_includes/header.html*)

- asset/images

Image files are stored here simply for  the sake of organisation.

## Includes ##

Except for reused page elements, various macros are stored in the *_include* directory.


- caption

Images are displayed along with an (optional) description. Example of use:

	{% include caption.html
	    width='420'
	    image='/asset/images/example.jpg'
	    text='Description'
	%}

- photo

Using the *caption* macro internally, a single photo from a gallery is displayed along with an (optional) description. Of note, descriptions defined in the gallery datafile are currently ignored. Example of use: 

	{% include photo.html
	    gallery='2013-visit'
	    photo='cfadeb2'
	    text='Description'
	%}

- gallery:

Displays a link to a gallery and thumbnails (with links) of all photos in a given gallery. Example of use:

	{% include gallery.html
		gallery='2013-visit
	%}




