<?php
/**
 * Weather API Proxy for BTT (BeyondTrailTales)
 * Provides weather data via Open-Meteo API with server-side caching
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Get parameters
$lat = filter_input(INPUT_GET, 'lat', FILTER_VALIDATE_FLOAT);
$lon = filter_input(INPUT_GET, 'lon', FILTER_VALIDATE_FLOAT);
$units = filter_input(INPUT_GET, 'units', FILTER_SANITIZE_STRING) ?: 'imperial'; // Default to Fahrenheit
$days = filter_input(INPUT_GET, 'days', FILTER_VALIDATE_INT) ?: 7;
$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);

// Handle geocoding
if ($action === 'geocode') {
    $query = filter_input(INPUT_GET, 'q', FILTER_SANITIZE_STRING);
    
    if (empty($query) || strlen($query) < 2) {
        echo json_encode(['success' => false, 'error' => 'Search query too short']);
        exit;
    }
    
    $results = geocodeLocation($query);
    echo json_encode(['success' => true, 'data' => $results]);
    exit;
}

// Validate coordinates
if ($lat === false || $lon === false) {
    echo json_encode(['success' => false, 'error' => 'Invalid coordinates']);
    exit;
}

if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
    echo json_encode(['success' => false, 'error' => 'Coordinates out of range']);
    exit;
}

// Check cache first
$cacheKey = "weather_{$lat}_{$lon}_{$units}_{$days}";
$cachedData = getCache($cacheKey);

if ($cachedData !== null) {
    echo json_encode(['success' => true, 'data' => $cachedData, 'cached' => true]);
    exit;
}

// Fetch from Open-Meteo
$weatherData = fetchWeather($lat, $lon, $units, $days);

if ($weatherData) {
    setCache($cacheKey, $weatherData, 1800); // 30 minutes cache
    echo json_encode(['success' => true, 'data' => $weatherData]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to fetch weather data']);
}

/**
 * Fetch weather from Open-Meteo API
 */
function fetchWeather($lat, $lon, $units, $days) {
    $tempUnit = ($units === 'imperial') ? 'fahrenheit' : 'celsius';
    $windUnit = ($units === 'imperial') ? 'mph' : 'kmh';
    $precipUnit = ($units === 'imperial') ? 'inch' : 'mm';
    
    $params = [
        'latitude' => $lat,
        'longitude' => $lon,
        'current' => 'temperature_2m,relative_humidity_2m,apparent_temperature,precipitation,weather_code,wind_speed_10m,wind_direction_10m',
        'hourly' => 'temperature_2m,precipitation_probability,weather_code,wind_speed_10m',
        'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,sunrise,sunset,uv_index_max,precipitation_sum,precipitation_probability_max,wind_speed_10m_max',
        'temperature_unit' => $tempUnit,
        'wind_speed_unit' => $windUnit,
        'precipitation_unit' => $precipUnit,
        'timezone' => 'auto',
        'forecast_days' => min(max($days, 1), 14)
    ];
    
    $url = 'https://api.open-meteo.com/v1/forecast?' . http_build_query($params);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'BTT/1.0',
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return null;
    }
    
    $data = json_decode($response, true);
    return formatWeatherData($data, $units);
}

/**
 * Geocode location using Open-Meteo
 */
function geocodeLocation($query) {
    // Handle state abbreviations
    $stateAbbr = [
        'al' => 'alabama', 'ak' => 'alaska', 'az' => 'arizona', 'ar' => 'arkansas',
        'ca' => 'california', 'co' => 'colorado', 'ct' => 'connecticut', 'de' => 'delaware',
        'fl' => 'florida', 'ga' => 'georgia', 'hi' => 'hawaii', 'id' => 'idaho',
        'il' => 'illinois', 'in' => 'indiana', 'ia' => 'iowa', 'ks' => 'kansas',
        'ky' => 'kentucky', 'la' => 'louisiana', 'me' => 'maine', 'md' => 'maryland',
        'ma' => 'massachusetts', 'mi' => 'michigan', 'mn' => 'minnesota', 'ms' => 'mississippi',
        'mo' => 'missouri', 'mt' => 'montana', 'ne' => 'nebraska', 'nv' => 'nevada',
        'nh' => 'new hampshire', 'nj' => 'new jersey', 'nm' => 'new mexico', 'ny' => 'new york',
        'nc' => 'north carolina', 'nd' => 'north dakota', 'oh' => 'ohio', 'ok' => 'oklahoma',
        'or' => 'oregon', 'pa' => 'pennsylvania', 'ri' => 'rhode island', 'sc' => 'south carolina',
        'sd' => 'south dakota', 'tn' => 'tennessee', 'tx' => 'texas', 'ut' => 'utah',
        'vt' => 'vermont', 'va' => 'virginia', 'wa' => 'washington', 'wv' => 'west virginia',
        'wi' => 'wisconsin', 'wy' => 'wyoming'
    ];
    
    // Process query for "city, state" format
    $stateFilter = null;
    if (strpos($query, ',') !== false) {
        $parts = array_map('trim', explode(',', $query));
        if (count($parts) == 2) {
            $city = $parts[0];
            $state = strtolower($parts[1]);
            
            if (isset($stateAbbr[$state])) {
                $stateFilter = $stateAbbr[$state];
                $query = $city;
            } else {
                $stateFilter = $state;
                $query = $city;
            }
        }
    }
    
    $params = [
        'name' => $query,
        'count' => 20,
        'language' => 'en',
        'format' => 'json'
    ];
    
    $url = 'https://geocoding-api.open-meteo.com/v1/search?' . http_build_query($params);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'BTT/1.0',
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return [];
    }
    
    $data = json_decode($response, true);
    
    if (isset($data['results']) && is_array($data['results'])) {
        $results = $data['results'];
        
        // Filter by state if needed
        if ($stateFilter !== null) {
            $filtered = [];
            foreach ($results as $result) {
                $admin1 = strtolower($result['admin1'] ?? '');
                if (stripos($admin1, $stateFilter) !== false) {
                    $filtered[] = $result;
                }
            }
            
            if (!empty($filtered)) {
                $results = $filtered;
            } else {
                // Try US results at least
                $results = array_filter($results, function($r) {
                    return ($r['country_code'] ?? '') === 'US';
                });
            }
        }
        
        return array_map(function($result) {
            return [
                'id' => $result['id'] ?? null,
                'name' => $result['name'] ?? '',
                'latitude' => $result['latitude'] ?? 0,
                'longitude' => $result['longitude'] ?? 0,
                'country' => $result['country'] ?? '',
                'admin1' => $result['admin1'] ?? '',
                'display_name' => formatLocationName($result)
            ];
        }, array_slice($results, 0, 10));
    }
    
    return [];
}

/**
 * Format location name for display
 */
function formatLocationName($location) {
    $parts = [];
    
    if (!empty($location['name'])) {
        $parts[] = $location['name'];
    }
    
    if (!empty($location['admin1'])) {
        $parts[] = $location['admin1'];
    }
    
    if (!empty($location['country'])) {
        $parts[] = $location['country'];
    }
    
    return implode(', ', $parts);
}

/**
 * Format weather data
 */
function formatWeatherData($data, $units) {
    if (!$data) return null;
    
    $formatted = [
        'location' => [
            'latitude' => $data['latitude'] ?? 0,
            'longitude' => $data['longitude'] ?? 0,
            'timezone' => $data['timezone'] ?? 'UTC',
            'elevation' => $data['elevation'] ?? 0
        ],
        'units' => [
            'temperature' => $units === 'imperial' ? '°F' : '°C',
            'wind_speed' => $units === 'imperial' ? 'mph' : 'km/h',
            'precipitation' => $units === 'imperial' ? 'in' : 'mm',
            'system' => $units
        ],
        'current' => null,
        'daily' => []
    ];
    
    // Current conditions
    if (isset($data['current'])) {
        $formatted['current'] = [
            'time' => $data['current']['time'] ?? null,
            'temperature' => $data['current']['temperature_2m'] ?? null,
            'apparent_temperature' => $data['current']['apparent_temperature'] ?? null,
            'humidity' => $data['current']['relative_humidity_2m'] ?? null,
            'precipitation' => $data['current']['precipitation'] ?? 0,
            'weather_code' => $data['current']['weather_code'] ?? 0,
            'weather_description' => getWeatherDescription($data['current']['weather_code'] ?? 0),
            'wind_speed' => $data['current']['wind_speed_10m'] ?? 0,
            'wind_direction' => $data['current']['wind_direction_10m'] ?? 0
        ];
    }
    
    // Daily forecast
    if (isset($data['daily']['time'])) {
        $dailyCount = count($data['daily']['time']);
        for ($i = 0; $i < $dailyCount; $i++) {
            $formatted['daily'][] = [
                'date' => $data['daily']['time'][$i] ?? null,
                'weather_code' => $data['daily']['weather_code'][$i] ?? 0,
                'weather_description' => getWeatherDescription($data['daily']['weather_code'][$i] ?? 0),
                'temperature_max' => $data['daily']['temperature_2m_max'][$i] ?? null,
                'temperature_min' => $data['daily']['temperature_2m_min'][$i] ?? null,
                'sunrise' => $data['daily']['sunrise'][$i] ?? null,
                'sunset' => $data['daily']['sunset'][$i] ?? null,
                'uv_index' => $data['daily']['uv_index_max'][$i] ?? 0,
                'precipitation_sum' => $data['daily']['precipitation_sum'][$i] ?? 0,
                'precipitation_probability' => $data['daily']['precipitation_probability_max'][$i] ?? 0,
                'wind_speed_max' => $data['daily']['wind_speed_10m_max'][$i] ?? 0
            ];
        }
    }
    
    return $formatted;
}

/**
 * Get weather description from WMO code
 */
function getWeatherDescription($code) {
    $descriptions = [
        0 => 'Clear sky',
        1 => 'Mainly clear', 2 => 'Partly cloudy', 3 => 'Overcast',
        45 => 'Foggy', 48 => 'Depositing rime fog',
        51 => 'Light drizzle', 53 => 'Moderate drizzle', 55 => 'Dense drizzle',
        61 => 'Slight rain', 63 => 'Moderate rain', 65 => 'Heavy rain',
        71 => 'Slight snow', 73 => 'Moderate snow', 75 => 'Heavy snow',
        80 => 'Slight rain showers', 81 => 'Moderate rain showers', 82 => 'Heavy rain showers',
        95 => 'Thunderstorm', 96 => 'Thunderstorm with slight hail', 99 => 'Thunderstorm with heavy hail'
    ];
    
    return $descriptions[$code] ?? 'Unknown';
}

/**
 * Simple cache functions
 */
function getCache($key) {
    $cacheDir = dirname(__DIR__) . '/storage/cache';
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    $cacheFile = $cacheDir . '/weather_' . md5($key) . '.json';
    
    if (!file_exists($cacheFile)) {
        return null;
    }
    
    $data = json_decode(file_get_contents($cacheFile), true);
    
    if (!$data || time() > $data['expires']) {
        @unlink($cacheFile);
        return null;
    }
    
    return $data['data'];
}

function setCache($key, $data, $ttl = 1800) {
    $cacheDir = dirname(__DIR__) . '/storage/cache';
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    $cacheFile = $cacheDir . '/weather_' . md5($key) . '.json';
    
    file_put_contents($cacheFile, json_encode([
        'expires' => time() + $ttl,
        'data' => $data
    ]));
}
