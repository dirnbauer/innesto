/* Grafted from registry item "stats-10" (Stats with Area Chart).
   Draws the area sparkline for each stat card from its data-values JSON.
   Idempotent: every chart container is rendered at most once. */
(function () {
    'use strict';

    var SVG_NS = 'http://www.w3.org/2000/svg';
    var WIDTH = 100;
    var HEIGHT = 32;
    var PADDING = 2;

    function parseValues(raw) {
        var parsed;
        try {
            parsed = JSON.parse(raw);
        } catch (error) {
            return [];
        }
        if (!Array.isArray(parsed)) {
            return [];
        }
        return parsed.filter(function (value) {
            return typeof value === 'number' && isFinite(value);
        });
    }

    function buildPoints(values) {
        var min = Math.min.apply(null, values);
        var max = Math.max.apply(null, values);
        var span = max - min || 1;
        var stepX = WIDTH / (values.length - 1);
        return values.map(function (value, index) {
            var x = index * stepX;
            var y = PADDING + (1 - (value - min) / span) * (HEIGHT - PADDING * 2);
            return x.toFixed(2) + ' ' + y.toFixed(2);
        });
    }

    function draw(chart) {
        if (chart.dataset.innestoDrawn === 'true') {
            return;
        }
        chart.dataset.innestoDrawn = 'true';

        var values = parseValues(chart.getAttribute('data-values') || '[]');
        if (values.length < 2) {
            return;
        }

        var points = buildPoints(values);
        var lineD = 'M' + points.join(' L');
        var areaD = lineD + ' L' + WIDTH + ' ' + HEIGHT + ' L0 ' + HEIGHT + ' Z';

        var svg = document.createElementNS(SVG_NS, 'svg');
        svg.setAttribute('viewBox', '0 0 ' + WIDTH + ' ' + HEIGHT);
        svg.setAttribute('preserveAspectRatio', 'none');
        svg.setAttribute('aria-hidden', 'true');
        svg.setAttribute('focusable', 'false');
        svg.setAttribute('class', 'innesto-stats-area-chart__svg');

        var area = document.createElementNS(SVG_NS, 'path');
        area.setAttribute('d', areaD);
        area.setAttribute('fill-opacity', '0.2');
        area.setAttribute('class', 'innesto-stats-area-chart__area');

        var line = document.createElementNS(SVG_NS, 'path');
        line.setAttribute('d', lineD);
        line.setAttribute('vector-effect', 'non-scaling-stroke');
        line.setAttribute('class', 'innesto-stats-area-chart__line');

        svg.appendChild(area);
        svg.appendChild(line);
        chart.appendChild(svg);
    }

    function init() {
        var charts = document.querySelectorAll('.innesto-stats-area-chart__chart[data-values]');
        for (var i = 0; i < charts.length; i++) {
            draw(charts[i]);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
})();
