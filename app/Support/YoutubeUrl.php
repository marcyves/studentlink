<?php

namespace App\Support;

class YoutubeUrl
{
    public static function id(?string $url): ?string
    {
        if (! is_string($url)) {
            return null;
        }

        $url = trim($url);

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $parts = parse_url($url);

        if (! is_array($parts) || empty($parts['host']) || empty($parts['scheme'])) {
            return null;
        }

        if (! in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true)) {
            return null;
        }

        $host = strtolower($parts['host']);

        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        if (str_starts_with($host, 'm.')) {
            $host = substr($host, 2);
        }

        if (! in_array($host, ['youtube.com', 'youtu.be', 'youtube-nocookie.com'], true)) {
            return null;
        }

        if ($host === 'youtu.be') {
            $segment = explode('/', trim((string) ($parts['path'] ?? ''), '/'))[0] ?? '';

            return self::validId($segment) ? $segment : null;
        }

        $path = (string) ($parts['path'] ?? '');

        if (preg_match('#^/(?:embed|shorts|live|v)/([A-Za-z0-9_-]{11})(?:/|$)#', $path, $matches) === 1) {
            return $matches[1];
        }

        if (! isset($parts['query'])) {
            return null;
        }

        parse_str($parts['query'], $query);
        $videoId = $query['v'] ?? null;

        return is_string($videoId) && self::validId($videoId) ? $videoId : null;
    }

    public static function embedUrl(?string $url): ?string
    {
        $id = self::id($url);

        return $id === null ? null : 'https://www.youtube.com/embed/'.$id;
    }

    private static function validId(string $id): bool
    {
        return preg_match('/^[A-Za-z0-9_-]{11}$/', $id) === 1;
    }
}
