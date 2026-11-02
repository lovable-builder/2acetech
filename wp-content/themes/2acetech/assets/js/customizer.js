/**
 * Customizer controls: sortable homepage sections, and jump the preview to the
 * homepage when the Homepage panel opens.
 */
(function ($, api) {
	'use strict';

	api.bind('ready', function () {
		// Sortable, tickable section list.
		$('.ace-sections').each(function () {
			var $list = $(this);
			var id = $list.data('setting');

			function push() {
				var keys = [];
				$list.children('li').each(function () {
					if ($(this).find('input[type="checkbox"]').is(':checked')) {
						keys.push($(this).data('key'));
					}
				});
				api(id).set(keys.join(','));
			}

			$list.sortable({ handle: '.ace-handle', axis: 'y', update: push });
			$list.on('change', 'input[type="checkbox"]', push);
		});

		// Show the homepage in the preview while editing it.
		api.panel('ace_home', function (panel) {
			panel.expanded.bind(function (open) {
				if (open && api.settings.url && api.settings.url.home) {
					api.previewer.previewUrl.set(api.settings.url.home);
				}
			});
		});
	});
})(jQuery, wp.customize);
