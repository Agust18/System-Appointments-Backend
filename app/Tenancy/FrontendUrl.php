<?php

namespace App\Tenancy;

final class FrontendUrl
{
    public const MAX_LENGTH = 255;

    
    public static function normalize(string $url, bool $allowHttp): ?string
    {
        $url = trim($url);

        if ($url === '' || strlen($url) > self::MAX_LENGTH || preg_match('/[\x00-\x20\x7f\\\\?#@]/', $url) === 1) {
            return null;
        }

        $parts = parse_url($url);
        if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $scheme = strtolower($parts['scheme']);
        if ($scheme !== 'https' && !($allowHttp && $scheme === 'http')) {
            return null;
        }

        if (isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
            return null;
        }

        if (isset($parts['path']) && $parts['path'] !== '/') {
            return null;
        }

        $host = strtolower($parts['host']);
        if (filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            return null;
        }

        $port = '';
        if (isset($parts['port'])) {
            if ($parts['port'] < 1 || $parts['port'] > 65535) {
                return null;
            }
            $port = ':' . $parts['port'];
        }

        return $scheme . '://' . $host . $port;
    }
}
