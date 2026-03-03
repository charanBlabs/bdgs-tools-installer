/**
 * Weather Widget - Open-Meteo API (free, no key, CORS enabled)
 * Runs in BD widget context; finds container by [data-weather-widget].
 */
(function () {
    'use strict';

    var OPEN_METEO_BASE = 'https://api.open-meteo.com/v1';
    var GEOCODING_BASE = 'https://geocoding-api.open-meteo.com/v1';

    function byDataAttr(attr) {
        return document.querySelector('[data-weather-widget]');
    }

    function getContainer() {
        var el = byDataAttr('weather-widget');
        if (!el) return null;
        return {
            root: el,
            input: el.querySelector('.bd-weather-input'),
            btn: el.querySelector('.bd-weather-btn-search'),
            loading: el.querySelector('.bd-weather-loading'),
            error: el.querySelector('.bd-weather-error'),
            result: el.querySelector('.bd-weather-result'),
            location: el.querySelector('.bd-weather-location'),
            temp: el.querySelector('.bd-weather-temp'),
            desc: el.querySelector('.bd-weather-desc'),
            meta: el.querySelector('.bd-weather-meta')
        };
    }

    function showLoading(c, show) {
        if (c.loading) c.loading.style.display = show ? 'block' : 'none';
        if (c.error) c.error.style.display = 'none';
        if (c.result) c.result.style.display = show ? 'none' : 'block';
    }

    function showError(c, msg) {
        if (c.loading) c.loading.style.display = 'none';
        if (c.error) {
            c.error.textContent = msg || 'Could not load weather.';
            c.error.style.display = 'block';
        }
        if (c.result) c.result.style.display = 'none';
    }

    function showWeather(c, data) {
        if (c.loading) c.loading.style.display = 'none';
        if (c.error) c.error.style.display = 'none';
        if (!c.result) return;

        var name = data.name || 'Unknown';
        var temp = data.current && data.current.temperature_2m != null
            ? Math.round(Number(data.current.temperature_2m))
            : '—';
        var code = data.current && data.current.weather_code != null ? data.current.weather_code : null;
        var desc = weatherCodeToText(code);
        var humidity = data.current && data.current.relative_humidity_2m != null
            ? data.current.relative_humidity_2m + '%'
            : '';
        var wind = data.current && data.current.wind_speed_10m != null
            ? data.current.wind_speed_10m + ' km/h wind'
            : '';

        if (c.location) c.location.textContent = name;
        if (c.temp) c.temp.textContent = temp + '°';
        if (c.desc) c.desc.textContent = desc;
        if (c.meta) {
            var parts = [humidity, wind].filter(Boolean);
            c.meta.textContent = parts.join(' · ');
        }
        c.result.style.display = 'block';
    }

    function weatherCodeToText(code) {
        if (code == null) return '—';
        var map = {
            0: 'Clear', 1: 'Mainly clear', 2: 'Partly cloudy', 3: 'Overcast',
            45: 'Foggy', 48: 'Depositing rime fog',
            51: 'Drizzle (light)', 53: 'Drizzle', 55: 'Drizzle (dense)',
            61: 'Slight rain', 63: 'Rain', 65: 'Heavy rain',
            71: 'Slight snow', 73: 'Snow', 75: 'Heavy snow',
            80: 'Slight rain showers', 81: 'Rain showers', 82: 'Heavy rain showers',
            95: 'Thunderstorm', 96: 'Thunderstorm (hail)', 99: 'Thunderstorm (heavy hail)'
        };
        return map[code] || 'Unknown';
    }

    function fetchJson(url) {
        return fetch(url).then(function (res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        });
    }

    function geocode(name) {
        var url = GEOCODING_BASE + '/search?name=' + encodeURIComponent(name) + '&count=1&language=en&format=json';
        return fetchJson(url).then(function (data) {
            var results = data.results;
            if (!results || results.length === 0) throw new Error('Location not found');
            return { lat: results[0].latitude, lon: results[0].longitude, name: results[0].name };
        });
    }

    function getWeather(lat, lon) {
        var url = OPEN_METEO_BASE + '/forecast?latitude=' + lat + '&longitude=' + lon +
            '&current=temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m&timezone=auto';
        return fetchJson(url).then(function (data) {
            return {
                name: data.timezone || lat + ', ' + lon,
                current: data.current || {}
            };
        });
    }

    function runSearch(c, query) {
        var q = (query || (c.input && c.input.value) || '').trim();
        if (!q) {
            showError(c, 'Enter a city or place.');
            return;
        }
        showLoading(c, true);
        geocode(q)
            .then(function (loc) {
                return getWeather(loc.lat, loc.lon).then(function (w) {
                    w.name = loc.name;
                    return w;
                });
            })
            .then(function (data) {
                showWeather(c, data);
            })
            .catch(function (err) {
                showError(c, err.message || 'Failed to load weather.');
            });
    }

    function init() {
        var c = getContainer();
        if (!c) return;
        if (c.btn) {
            c.btn.addEventListener('click', function () { runSearch(c); });
        }
        if (c.input) {
            c.input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    runSearch(c);
                }
            });
        }
        var defaultCity = (c.input && c.input.value && c.input.value.trim()) || 'London';
        if (defaultCity) {
            setTimeout(function () { runSearch(c, defaultCity); }, 100);
        }
    }

    function run() {
        if (document.querySelector('[data-weather-widget]')) {
            init();
        } else {
            setTimeout(run, 50);
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
})();
