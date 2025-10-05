document.addEventListener('DOMContentLoaded', function() {
    // Handle image errors
    document.querySelectorAll('.instagram-feed-container img').forEach(function(img) {
        img.addEventListener('error', function() {
            this.closest('.instagram-feed-item').style.display = 'none';
        });
    });

    // Add 'loaded' class on image load
    document.querySelectorAll('.instagram-feed-container img').forEach(function(img) {
        img.addEventListener('load', function() {
            this.closest('.instagram-feed-item').classList.add('loaded');
        });
    });
});