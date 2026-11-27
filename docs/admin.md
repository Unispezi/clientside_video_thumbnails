# For admins

[← Overview](index.md)

## Install

Apps → Multimedia → **Clientside Video Thumbnails** → Download and enable. Nextcloud 31 to 34.

No FFmpeg, no extra server, no configuration.

## What the server does

- Accepts one JPEG per video, up to 16 MB, only from a signed-in user for a video they can access. Uploads go through Nextcloud's regular CSRF check.
- Stores it in the app's data folder and serves it as the video's preview. Nextcloud scales it for every preview size.
- Copies of a video get its thumbnail. A daily background job deletes thumbnails of videos that are gone for good.
- Stores two switches per user: only on Wi-Fi, only while charging.

No data goes to third parties. Videos are read by the user's own browser, with their own login.

## Admin settings

**Administration settings → Additional settings → Clientside Video Thumbnails**

**Delete all thumbnails of all users** removes every stored thumbnail and the previews Nextcloud made from them. Browsers create new ones the next time videos are listed. Meant for testing.

## Uninstall

Removing the app deletes all stored thumbnails, its settings and the users' switches.

## Videos no browser can play

They stay without a preview. If you need those, the [Video Thumbnail Relay](https://github.com/silversong88/video_thumbs_relay) app has an FFmpeg worker that runs on a machine of your own. Use it instead of this app, not alongside.

## Problems

[Open an issue](https://github.com/Unispezi/clientside_video_thumbnails/issues) with your Nextcloud version, browser and, if you can, the entries from the Nextcloud log that mention `clientside_video_thumbnails`.
