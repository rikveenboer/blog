require 'jekyll/tags/post_url'

module Jekyll
    class PostLink < Liquid::Tag
        def initialize(tag_name, post, tokens)
            super
            @orig_post = post.strip
            begin
                @post = Jekyll::Tags::PostComparer.new(@orig_post)
            rescue
                raise ArgumentError.new <<-eos
Could not parse name of post "#{@orig_post}" in tag 'post_url'.

Make sure the post exists and the name is correct.
eos
            end
        end

        def render(context)
            site = context.registers[:site]
            site.posts.docs.each do |p|
              if @post == p
                return '/' + context.registers[:site].config['baseurl'] + p.url
              end
            end
        end
        
    end
end
 
Liquid::Template.register_tag('post_link', Jekyll::PostLink)