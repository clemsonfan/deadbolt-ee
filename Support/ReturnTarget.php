<?php

namespace Deadbolt\Support;

class ReturnTarget
{
    public static function normalize($candidate)
    {
        if (! is_string($candidate)) {
            return '';
        }

        $candidate = trim($candidate);
        if ($candidate === '' || strlen($candidate) > 2048 || preg_match('/[\x00-\x1F\x7F\\\\]/', $candidate)) {
            return '';
        }

        if (strpos($candidate, '//') === 0) {
            return '';
        }

        $parts = parse_url($candidate);
        if ($parts === false || isset($parts['scheme']) || isset($parts['host'])
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) {
            return '';
        }

        $path = isset($parts['path']) ? $parts['path'] : '';
        $decodedPath = $path;
        for ($i = 0; $i < 8; $i++) {
            $nextPath = rawurldecode($decodedPath);
            if ($nextPath === $decodedPath) {
                break;
            }
            $decodedPath = $nextPath;
        }

        if (rawurldecode($decodedPath) !== $decodedPath) {
            return '';
        }

        if (strpos($decodedPath, '//') === 0 || preg_match('/[\\\\\x00-\x1F\x7F]/', $decodedPath)) {
            return '';
        }

        foreach (explode('/', $decodedPath) as $segment) {
            if ($segment === '.' || $segment === '..') {
                return '';
            }
        }

        $normalized = ltrim($path, '/');
        if (isset($parts['query']) && $parts['query'] !== '') {
            $normalized .= '?' . $parts['query'];
        }
        if (isset($parts['fragment']) && $parts['fragment'] !== '') {
            $normalized .= '#' . $parts['fragment'];
        }

        return $normalized;
    }

    public static function withResultNonce($candidate, $nonce)
    {
        $normalized = self::normalize($candidate);
        $parts = parse_url($normalized);
        if ($parts === false) {
            $parts = [];
        }

        $path = isset($parts['path']) ? trim($parts['path'], '/') : '';
        $target = ee()->functions->create_url($path, false);
        $query = isset($parts['query']) ? $parts['query'] : '';
        $queryParts = ($query === '') ? [] : explode('&', $query);
        $queryParts = array_values(array_filter($queryParts, function ($part) {
            $name = strtolower(rawurldecode(explode('=', $part, 2)[0]));

            return ! in_array($name, ['act', 'ret', 'url', 'deadbolt_result'], true);
        }));
        $queryParts[] = 'deadbolt_result=' . rawurlencode($nonce);

        if (substr($target, -1) !== '?' && substr($target, -1) !== '&') {
            $target .= (strpos($target, '?') === false) ? '?' : '&';
        }
        $target .= implode('&', $queryParts);

        if (isset($parts['fragment']) && $parts['fragment'] !== '') {
            $target .= '#' . $parts['fragment'];
        }

        return $target;
    }
}
