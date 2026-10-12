# Stamp.Show Companion

WordPress plugin for organizers who run their expo's public site on WordPress while Stamp.Show stays the system of record. GPL-2.0-or-later.

## Setup

1. In Stamp.Show, open the expo's **API Keys** tab and create a key.
2. In WordPress: Settings → Stamp.Show. Paste the API key and the webhook secret, then **Test connection**.
3. Back in the API Keys tab, set the webhook URL to `https://your-site/wp-json/stamp-show/v1/webhook` and tick the events (`results.published`, `content.changed`, `application.*`).

## Shortcodes

`[stampshow_palmares]` `[stampshow_sponsors category="Platinum"]` `[stampshow_special_prizes]` `[stampshow_judges]` `[stampshow_commissioners]` `[stampshow_catalog]` `[stampshow_apply classes="Traditional,Thematic"]`

Each shortcode is also a block in the editor (search "Stamp.Show"), rendered server-side with the same output.

Content is cached for an hour and cleared early by the webhook. Developers can hook `stamp_show_event( $event, $data )`.