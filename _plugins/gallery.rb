module Jekyll
  class GalleryPage < Page
        def initialize(site, gallery, layout)
            @site = site
            @dir = gallery.url
            @name = layout + '.html'
            self.process(@name)
            self.read_yaml(File.join(site.source, '_layouts'), 'gallery-' + layout + '.html')
            self.data.merge!( gallery.data)
            self.data['gallery'] = gallery.url.split('/')[2]
            self.data['layout'] = 'gallery-' + layout
        end        
    end

    class GalleryPageGenerator < Generator
        def generate(site)
            site.collections['gallery'].docs.each do |gallery|
                if gallery.respond_to?('map')
                    site.pages << GalleryPage.new(site, gallery, 'map')
                end
                if !gallery.respond_to?('slideshow')
                    site.pages << GalleryPage.new(site, gallery, 'slideshow')
                end
            end
        end
    end
end