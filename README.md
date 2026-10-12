<p align="center">
  <a href="https://stamp.show/"><img src="https://stamp.show/app/logo.png" alt="Stamp.Show" width="160"></a>
</p>

# Stamp.Show Companion

Show your [Stamp.Show](https://stamp.show/) expo on your WordPress site: the palmares, sponsors, special prizes, judges, commissioners, the catalog and an application form. GPL-2.0-or-later.

[Documentation](https://stamp.show/developers/wordpress/) · [Download](https://github.com/stamp-show/wp-companion/releases/latest) · [API reference](https://stamp.show/developers/api/)

## What is Stamp.Show?

[Stamp.Show](https://stamp.show/) is the platform for running a philatelic exhibition online, from registration to the published palmarès. Exhibitors register and build their frames in a browser; judge teams score in a virtual jury room, manually or with automatic evaluation; organizers number the frames, publish the results and get a public expo website, all in one place.

This plugin is for organizers who prefer to keep their expo's public website on WordPress. Stamp.Show stays the system of record, and the plugin reads from it with your expo's API key, so the site is always current: results, sponsors and the jury appear on your pages when you publish them, with no copying and pasting.

New to Stamp.Show? Start at **[stamp.show](https://stamp.show/)**.

## Setup

1. In Stamp.Show, open the expo's **API Keys** tab and create a key.
2. In WordPress: Settings → Stamp.Show. Paste the API key and the webhook secret, then **Test connection**.
3. Back in the API Keys tab, set the webhook URL to `https://your-site/wp-json/stamp-show/v1/webhook` and tick the events (`results.published`, `content.changed`, `application.*`).

Full instructions, every shortcode and attribute, and troubleshooting: **[stamp.show/developers/wordpress](https://stamp.show/developers/wordpress/)**.

## Shortcodes

`[stampshow_palmares]` `[stampshow_sponsors category="Platinum"]` `[stampshow_special_prizes]` `[stampshow_judges]` `[stampshow_commissioners]` `[stampshow_catalog]` `[stampshow_apply classes="Traditional,Thematic"]`

Each shortcode is also a block in the editor (search "Stamp.Show"), rendered server-side with the same output.

Content is cached for an hour and cleared early by the webhook.

## Hooks

Filters: `stamp_show_shortcode_atts`, `stamp_show_data`, `stamp_show_item_html`, `stamp_show_output`, `stamp_show_cache_ttl`, `stamp_show_request_args`, `stamp_show_application_payload`.
Actions: `stamp_show_event`, `stamp_show_event_{$event}`, `stamp_show_cache_flushed`, `stamp_show_application_submitted`, `stamp_show_application_failed`.

Signatures and examples: [stamp.show/developers/wordpress](https://stamp.show/developers/wordpress/).

## Building the zip

`python bin/build.py` writes `dist/stamp-show-companion-<version>.zip`, ready to upload in Plugins → Add New → Upload Plugin.

## License

[GPL-2.0-or-later](LICENSE). © Stamp.Show.
