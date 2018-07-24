<?php

function print_pre($data) {
    echo "<pre>";
    print_r($data);
    echo "</pre>";
}

function getCurrentUrl() {
    $result = 'http://';
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        $result = 'https://';
    }

    $result .= $_SERVER['HTTP_HOST'];
    
    $port = $_SERVER['SERVER_PORT'];
    if ($port != 80) {
        $result .= ':' . $port;
    }

    $result .= $_SERVER['REQUEST_URI'];

    return $result;
}

function str_removeFromStart(string $value, string $remove) {
    if (strncasecmp($value, $remove, strlen($remove)) == 0) {
        return substr($value, strlen($remove));
    }

    return $value;
}

?>