(function ($) {
    console.log("run jquery");

    $(document).ready(function () {
        console.log("first console");
        var urlPath = window.location.pathname;
        urlPath = urlPath.replace(/\/$/, ''); // Remove trailing slash if it exists
        var lastSegment = urlPath.substring(urlPath.lastIndexOf('/') + 1);
        console.log("test");
        // Check if the URL path contains '/product-category/bike/'
        // var excludeClasses = urlPath.includes('/product-category/bike/');

        // Iterate through each filter element
        $('#product-filters-accordion .accordion-item').each(function () {
            var filterClass = $(this).attr('class');
            console.log("test2");
            console.log(filterClass);


            // Check if the filter class contains the last URL path segment
            if (filterClass.indexOf(lastSegment) === -1) {
                console.log(filterClass);
                $(this).hide();  // Hide the filter if it doesn't contain the last segment
            }
        });
    });

    jQuery(document).ready(function ($) {

        // Hide all accordion content on page load
        $('.accordion-content').hide();
        // Add Font Awesome chevron icons to the toggle links
        $('.accordion-toggle').append('<i class="fas fa-chevron-down chevron-icon"></i>');

        // Add click event to toggle accordion content
        $('.accordion-toggle').on('click', function (e) {
            e.preventDefault();
            var target = $(this).data('target');

            // Slide up all accordion contents
            // $('.accordion-content').not(target).slideUp();

            // Remove up chevron and add down chevron to all toggle links
            // $('.accordion-toggle').not(this).find('.chevron-icon').removeClass('fa-chevron-up').addClass('fa-chevron-down');

            // Toggle the clicked accordion content
            $(target).slideToggle();

            var chevron = $(this).find('.chevron-icon');
            if (chevron.hasClass('fa-chevron-down')) {
                chevron.removeClass('fa-chevron-down').addClass('fa-chevron-up');
            } else {
                chevron.removeClass('fa-chevron-up').addClass('fa-chevron-down');
            }
        });
        $('.accordion-item:has(input[type="checkbox"]:checked)').find('.accordion-toggle').click();

        $('.product-attribute-filter').change(function () {
            var baseUrl = window.location.href.split('?')[0];
            var queryParams = new URLSearchParams(window.location.search);

            // Clear existing product attribute filters from query params
            $('.product-attribute-filter').each(function () {
                var attribute = $(this).data('attribute');
                queryParams.delete(attribute);
            });

            // Add selected filters to query params
            $('.product-attribute-filter:checked').each(function () {
                var attribute = $(this).data('attribute');
                var value = $(this).val();

                if (queryParams.has(attribute)) {
                    queryParams.set(attribute, queryParams.get(attribute) + ',' + value);
                } else {
                    queryParams.append(attribute, value);
                }
            });

            // Reload the page with new query parameters
            window.location.href = baseUrl + '?' + queryParams.toString();
        });
    });


})(jQuery);
