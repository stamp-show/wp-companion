<?php
defined('ABSPATH') || exit;

/**
 * POST /wp-json/stamp-show/v1/webhook: verifies the HMAC-SHA256 signature of the raw body and
 * invalidates the cache of whatever changed. Other code can hook `stamp_show_event`.
 */
class SSC_Webhook
{
    public static function init(): void
    {
        add_action('rest_api_init', static function () {
            register_rest_route('stamp-show/v1', '/webhook', [
                'methods' => 'POST',
                'callback' => [self::class, 'handle'],
                'permission_callback' => '__return_true', // authenticated by the signature
            ]);
        });
    }

    public static function handle(WP_REST_Request $request)
    {
        $secret = (string) get_option('ssc_webhook_secret', '');
        $body = $request->get_body();
        $signature = (string) $request->get_header('x_stampshow_signature');
        if ($secret === '' || !hash_equals(hash_hmac('sha256', $body, $secret), $signature)) {
            return new WP_REST_Response(['error' => 'Invalid signature'], 401);
        }

        $payload = json_decode($body, true);
        $event = is_array($payload) ? (string) ($payload['event'] ?? '') : '';
        $data = is_array($payload) && is_array($payload['data'] ?? null) ? $payload['data'] : [];

        switch ($event) {
            case 'results.published':
                SSC_Client::flush('palmares');
                SSC_Client::flush('catalog');
                break;
            case 'content.changed':
                $resource = (string) ($data['resource'] ?? '');
                SSC_Client::flush(in_array($resource, SSC_Client::RESOURCES, true) ? $resource : null);
                break;
            case 'application.accepted':
            case 'application.rejected':
                SSC_Client::flush('catalog');
                break;
        }

        do_action('stamp_show_event', $event, $data);
        return new WP_REST_Response(['received' => true], 200);
    }
}
