# Clientside Video Thumbnails

Video previews for Nextcloud instances that can't run FFmpeg, such as managed hosting like Hetzner Storage Share. Your browser renders them.

## How it works

1. You open a folder with videos in Files.
2. For each video without a preview, your browser loads a small part of it, grabs one frame and shows it right away.
3. It uploads the frame. From then on Nextcloud serves it as a normal preview: in Files, Photos, shares and the mobile apps.

The frame is the same one Nextcloud itself would take: at 5 seconds, or at 1 second for shorter videos.

## Help

- [For users](user.md): settings, creating all missing thumbnails at once, mobile data and battery.
- [For admins](admin.md): installing, what the server stores, deleting all thumbnails.

## Limits

Your browser can only render videos it can play. Depending on browser and device, HEVC (H.265), MKV or AVI files may stay without a preview. Another browser may manage them: Safari plays HEVC, for example.

## Links

- [Source code and issues](https://github.com/Unispezi/clientside_video_thumbnails)
- [Changelog](https://github.com/Unispezi/clientside_video_thumbnails/blob/main/CHANGELOG.md)
