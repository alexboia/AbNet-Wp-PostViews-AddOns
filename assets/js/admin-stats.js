(function () {
	'use strict';

	function sizeDashboardChart(container, itemSelector) {
		var items = Array.prototype.slice.call(container.querySelectorAll(itemSelector));
		var maximum = items.reduce(function (currentMaximum, item) {
			return Math.max(currentMaximum, Number(item.dataset.value) || 0);
		}, 0);

		items.forEach(function (item) {
			var value = Number(item.dataset.value) || 0;
			var percentage = maximum > 0 ? (value / maximum) * 100 : 0;
			item.style.setProperty('--abnet-stat-size', percentage + '%');
		});
	}

	document.addEventListener("DOMContentLoaded", function() {
		//For the time being it is cheap enough so that 
		// we indiscriminately search for elements belonging 
		// to both pages:
		//	- Dashboard stats
		document.querySelectorAll('[data-abnet-stats]').forEach(function (chart) {
			sizeDashboardChart(chart, '.abnet-visit-stats-bar-row');
		});

		// - And post edit stats
		document.querySelectorAll('[data-abnet-stats-tabs]').forEach(function (container) {
			container.querySelectorAll('.abnet-visit-stats-daily-chart').forEach(function (chart) {
				sizeDashboardChart(chart, '.abnet-visit-stats-day');
			});

			container.querySelectorAll('[role="tab"]').forEach(function (tab) {
				tab.addEventListener('click', function () {
					container.querySelectorAll('[role="tab"]').forEach(function (candidate) {
						var isActive = candidate === tab;
						candidate.classList.toggle('is-active', isActive);
						candidate.setAttribute('aria-selected', isActive ? 'true' : 'false');
						container.querySelector('#' + candidate.getAttribute('aria-controls')).hidden = !isActive;
					});
				});
			});
		});
	});
}());
