<?php
defined('ABSPATH') || exit;

/**
 * Talks to the Stamp.Show public API with the expo's API key. The key never leaves the server.
 */
class SSC_Client
{
    const RESOURCES = ['palmares', 'sponsors', 'special-prizes', 'judges', 'commissioners', 'catalog'];
    const CACHE_TTL = HOUR_IN_SECONDS;

    public static function base_url(): string
    {
        return untrailingslashit((string) get_option('ssc_base_url', 'https://api.stamp.show'));
    }

    public static function is_configured(): bool
    {
        return self::base_url() !== '' && (string) get_option('ssc_api_key', '') !== '';
    }

    /** @return array|WP_Error decoded JSON body */
    public static function request(string $method, string $path, ?array $body = null)
    {
        $args = [
            'method' => $method,
            'timeout' => 15,
            'headers' => [
                'Authorization' => 'Bearer ' . get_option('ssc_api_key', ''),
                'Accept' => 'application/json',
            ],
        ];
        if ($body !== null) {
            $args['headers']['Content-Type'] = 'application/json';
            $args['body'] = wp_json_encode($body);
        }
        // Filter `stamp_show_request_args` ( array $args, string $method, string $path ): wp_remote_request() arguments
        // (timeout, sslverify, extra headers...). The Authorization header is yours to keep.
        $args = apply_filters('stamp_show_request_args', $args, $method, $path);
        $response = wp_remote_request(self::base_url() . '/api/v1' . $path, $args);
        if (is_wp_error($response)) {
            return $response;
        }
        $status = (int) wp_remote_retrieve_response_code($response);
        $data = json_decode((string) wp_remote_retrieve_body($response), true);
        if ($status < 200 || $status >= 300) {
            // The API answers errors as {"detail": "..."} (or an object for field errors).
            $detail = is_array($data) ? ($data['detail'] ?? $data['error'] ?? $data['message'] ?? '') : '';
            $message = is_string($detail) ? $detail : (string) wp_json_encode($detail);
            return new WP_Error('ssc_http_' . $status, $message !== '' ? $message : 'Stamp.Show answered HTTP ' . $status, ['status' => $status]);
        }
        return is_array($data) ? $data : [];
    }

    /** Cached read of one content resource; the webhook (or the TTL) clears it. */
    public static function resource(string $name)
    {
        if (!in_array($name, self::RESOURCES, true) || !self::is_configured()) {
            return new WP_Error('ssc_unavailable', 'Stamp.Show is not configured');
        }
        $key = 'ssc_' . $name;
        $cached = get_transient($key);
        if (is_array($cached)) {
            return $cached;
        }
        $data = self::request('GET', '/' . $name);
        if (!is_wp_error($data)) {
            // Filter `stamp_show_cache_ttl` ( int $seconds, string $resource ): 0 disables the cache for that resource.
            $ttl = (int) apply_filters('stamp_show_cache_ttl', self::CACHE_TTL, $name);
            if ($ttl > 0) {
                set_transient($key, $data, $ttl);
            }
        }
        return $data;
    }

    public static function flush(?string $name = null): void
    {
        foreach ($name === null ? self::RESOURCES : [$name] as $resource) {
            delete_transient('ssc_' . $resource);
            // Action `stamp_show_cache_flushed` ( string $resource ): after a resource's cache is cleared (webhook or button).
            do_action('stamp_show_cache_flushed', $resource);
        }
    }
}
