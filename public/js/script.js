(function($) {
    'use strict';
    $(document).ready(function() {
        $('.instagram-feed-container img').on('error', function() {
            $(this).closest('.instagram-feed-item').hide();
        });
        
        $('.instagram-feed-container img').on('load', function() {
            $(this).closest('.instagram-feed-item').addClass('loaded');
        });
    });
})(jQuery);