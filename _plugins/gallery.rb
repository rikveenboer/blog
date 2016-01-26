module Jekyll
  class GalleryPage < Page
        def initialize(site, gallery, name)
            @site = site
            @dir = 'gallery/' + gallery['gallery']
            @name = name + '.html'
            self.process(@name)
            self.read_yaml(File.join(site.source, '_layouts'), 'gallery-' + name + '.html')
            self.data.merge!(gallery)
        end
    end

    class PhotoPage < Page
        def initialize(site, gallery, photo)
            @site = site
            @dir = 'gallery/' + gallery
            @name = photo['name'].to_s + '.html'
            photo['url'] = @dir +  '/' + @name
            self.process(@name)
            self.read_yaml(File.join(site.source, '_layouts'), 'gallery-photo.html')
            self.data['gallery'] = gallery
            self.data['photo'] = photo
        end
    end

    class ListPage < Page
        def initialize(site, galleries)
            @site = site
            @dir = 'gallery'
            @name = 'index.html'
            self.process(@name)
            self.read_yaml(File.join(site.source, '_layouts'), 'gallery-list.html')
            # photo['url'] = @dir +  '/' + @name
            # self.data.merge!(photo)
            self.data['galleries'] = galleries
        end
    end

    class GalleryGenerator < Generator
        def generate(site)
            # Convert gallery data to array
            galleries = []
            site.data['gallery'].each do |gallery|
                gallery[1]['gallery'] = gallery[0]
                galleries.push gallery[1]
            end

            # Sort galleries by date
            galleries.sort! do |x, y|
                x['date'] <=> y['date']
            end        
            galleries.reverse!

            # Process each gallery
            galleries.each do |gallery|
                dir = 'gallery/' + gallery['gallery']

                # Convert photo data to array
                photos = []
                gallery['photos'].each do |photo|
                    photo[1]['name'] = photo[0]

                    # Generate photo page
                    site.pages << PhotoPage.new(site, gallery['gallery'], photo[1])                    
                    photos.push photo[1]
                end

                # Sort photos by date
                photos.sort! do |x, y|
                    x['date'] <=> y['date']
                end

                # Add previous and next labels
                photos.each_with_index do |photo, i|
                    if i > 0
                        photo['previous'] = photos[i - 1]['name']
                    end
                    if i < photos.length - 1
                        photo['next'] = photos[i + 1]['name']
                    end
                end
                
                # Stored sorted photos in gallery hash
                gallery['photos'] = photos

                # Generate gallery index page
                site.pages << GalleryPage.new(site, gallery, 'index')

                # Generate gallery map page
                if gallery.respond_to?('map')
                    site.pages << GalleryPage.new(site, gallery, 'map')
                end

                # Generate gallery slideshow page
                if !gallery.respond_to?('slideshow')
                    site.pages << GalleryPage.new(site, gallery, 'slideshow')
                end
            end

            # Generate galleries index page
            site.pages << ListPage.new(site, galleries)
        end
    end
end