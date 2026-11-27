# Clientside Video Thumbnails

[Deutsch](de/index.md)

Video previews for Nextcloud instances that can't run FFmpeg, such as managed hosting like Hetzner Storage Share. Your browser renders them.

## How it works

1. You open a folder with videos in Files.
2. For each video without a preview, your browser loads a small part of it, grabs one frame and shows it right away.
3. It uploads the frame. From then on Nextcloud serves it as a normal preview: in Files, Photos, shares and the mobile apps.

The frame is the same one Nextcloud itself would take: at 5 seconds, or at 1 second for shorter videos.

**All at once:** under **Personal settings → Clientside Video Thumbnails**, **Create missing thumbnails** renders every video of yours without a preview in one go. See [For users](user.md).

## Help

- [For users](user.md): settings, creating all missing thumbnails at once, mobile data and battery.
- [For admins](admin.md): installing, what the server stores, deleting all thumbnails.

## Limits

**Web browser only.** Thumbnails are created by Nextcloud in a web browser, on the desktop or on the phone. The native Nextcloud apps, at least the one for Android, don't run the app's code: they create no thumbnails, but show the ones the browser made. On a phone, open your Nextcloud in Chrome, Safari or Firefox instead of the app.

**Videos the browser can't play.** Your browser can only render videos it can play. Depending on browser and device, HEVC (H.265), MKV or AVI files may stay without a preview. Another browser may manage them: Safari plays HEVC, for example.

## Links

- [Source code and issues](https://github.com/Unispezi/clientside_video_thumbnails)
- [Changelog](https://github.com/Unispezi/clientside_video_thumbnails/blob/main/CHANGELOG.md)
