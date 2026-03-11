(function ($) {
	'use strict';

	$(document).ready(function () {
		if (typeof ail_click_tracker === 'undefined') {
			return;
		}

		$('a').on('click', function (e) {
			var $link = $(this);
			var url = $link.attr('href');
			var anchor = $link.text().trim();

			// Only track links with href
			if (!url) {
				return;
			}

			// Don't track links with no anchor text
			if (!anchor) {
				return;
			}

			// Exclude common non-internal links format (hash, javascript, etc.)
			if (url.indexOf('#') === 0 || url.indexOf('javascript:') === 0 || url.indexOf('mailto:') === 0 || url.indexOf('tel:') === 0) {
				return;
			}

			// Basic check for internal links (same domain) Let server handle detailed validation
			var currentHostname = window.location.hostname;
			var linkHostname;
			
			try {
				if(url.indexOf('/') === 0 && url.indexOf('//') !== 0) {
					// Relative path, it is internal
					linkHostname = currentHostname;
				} else {
					var urlObj = new URL(url, window.location.href);
					linkHostname = urlObj.hostname;
				}
			} catch(e) {
				return;
			}

			if (linkHostname !== currentHostname) {
				return; // Not an internal link
			}

			// Send click data via Beacon or AJAX
			var data = {
				action: 'ail_link_clicked',
				post_id: ail_click_tracker.post_id,
				link_url: encodeURIComponent(url),
				link_anchor: encodeURIComponent(anchor)
			};

			// Use navigator.sendBeacon if available for better reliability when navigating away
			if (navigator.sendBeacon) {
				var formData = new FormData();
				for (var key in data) {
					formData.append(key, data[key]);
				}
				navigator.sendBeacon(ail_click_tracker.ajax_url, formData);
			} else {
				$.post({
					url: ail_click_tracker.ajax_url,
					data: data,
					async: true
				});
			}
		});
	});

})(jQuery);
