<?php
defined('BASEPATH') OR exit('No direct script access allowed');

function reading_env($name, $default = '') {
    $value = getenv($name);
    return $value === false ? $default : $value;
}

function reading_required_env($name) {
    $value = reading_env($name);
    if ($value === '' || $value === 'CHANGE_ME') {
        throw new RuntimeException('Missing required environment configuration: '.$name);
    }
    return $value;
}

function reading_origin() {
    $url = parse_url(reading_env('APP_URL', 'http://localhost:18087'));
    if (!$url || empty($url['host']) || !in_array($url['scheme'], array('http', 'https'), true)) {
        throw new RuntimeException('APP_URL must be an HTTP or HTTPS origin');
    }
    return array(
        'host' => $url['host'].(isset($url['port']) ? ':'.$url['port'] : ''),
        'scheme' => $url['scheme'],
    );
}
