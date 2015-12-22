# adapted from
# - https://github.com/jekyll/jekyll/blob/master/lib/jekyll/tags/post_url.rb

module Jekyll
  module Tags
    class PostBastUrl < Liquid::Tag
      def initialize(tag_name, post, tokens)
        super
        @orig_post = post.strip
        begin
          @post = PostComparer.new(@orig_post)
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

        # New matching method did not match, fall back to old method
        # with deprecation warning if this matches

        site.posts.docs.each do |p|
          if @post.deprecated_equality p
            Jekyll::Deprecator.deprecation_message "A call to '{{ post_url #{@post.name} }}' did not match " +
              "a post using the new matching method of checking name " +
              "(path-date-slug) equality. Please make sure that you " +
              "change this tag to match the post's name exactly."
            return '/' + context.registers[:site].config['baseurl'] + p.url
          end
        end

        raise ArgumentError.new <<-eos
Could not find post "#{@orig_post}" in tag 'post_url'.
Make sure the post exists and the name is correct.
eos
      end
    end
  end
end

Liquid::Template.register_tag('post_baseurl', Jekyll::Tags::PostBastUrl)