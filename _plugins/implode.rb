# adapted from
# - https://github.com/kenhkelly/kenhkelly.us/blob/master/_plugins/implode.rb

module Jekyll
    module Implode
        def implode(text)
            "['" + text.join("', '") + "']"
        end
    end
end

Liquid::Template.register_filter(Jekyll::Implode)