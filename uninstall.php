<?php
defined('WP_UNINSTALL_PLUGIN') || exit;

foreach (['ssc_base_url', 'ssc_api_key', 'ssc_webhook_secret'] as $option) {
    delete_option($option);
}
foreach (['palmares', 'sponsors', 'special-prizes', 'judges', 'commissioners', 'catalog'] as $resource) {
    delete_transient('ssc_' . $resource);
}
