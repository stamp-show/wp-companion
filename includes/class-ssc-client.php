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
        $response = wp_remote_request(self::base_url() . '/api/v1' . $path, $args);
        if (is_wp_error($response)) {
            return $response;
        }
        $status = (int) wp_remote_retrieve_response_code($response);
        $data = json_decode((string) wp_remote_retrieve_body($response), true);
        if ($status < 200 || $status >= 300) {
            $message = is_array($data) ? (string) ($data['error'] ?? $data['message'] ?? '') : '';
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
            set_transient($key, $data, self::CACHE_TTL);
        }
        return $data;
    }

    public static function flush(?string $name = null): void
    {
        foreach ($name === null ? self::RESOURCES : [$name] as $resource) {
            delete_transient('ssc_' . $resource);
        }
    }
}
