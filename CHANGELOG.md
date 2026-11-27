# Changelog

All notable changes to this project are documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 1.1.0 - unreleased

Forked from [Video Thumbnail Relay](https://github.com/silversong88/video_thumbs_relay) 1.0.0 as Clientside Video Thumbnails.

### Added
- The Files app renders previews for videos on screen that have none, right in the browser, shows them at once and uploads them.
- A find-and-fix mode in the personal settings: finds all videos without a preview and renders them in the browser, with progress and a list of failures.
- Support for Nextcloud 34.
- Mobile browsers: muted playback kick-off when iOS Safari ignores `preload`, waiting for the decoded frame before drawing, and no rendering while the data saver is on.
- Personal settings: a button that deletes the thumbnails of all your own videos.
- Personal settings: render in Files only on Wi-Fi or Ethernet, only while charging. Per account; enforced where the browser can tell (Chrome on Android).
- Find and fix warns on mobile data, with the data saver on or on battery.
- Copies of a video, also inside copied folders, get its thumbnail.
- Admin settings: a button that deletes all video thumbnails, for testing.
- German translation, informal and formal.

### Changed
- New app ID `clientside_video_thumbnails`, upload path `/apps/clientside_video_thumbnails/thumbnail/upload`.
- Uploads go through Nextcloud's regular CSRF check: browsers send their request token, other clients the `OCS-APIRequest: true` header.
- Thumbnails may be up to 16 MB, for frames in full resolution.
- A new thumbnail for a video, and deleting all thumbnails, also drop the previews Nextcloud made from the old one. Before, Nextcloud went on serving those.

### Removed
- The Node.js worker, the API secret with its admin settings, the `exists` and `batchExists` endpoints and the capabilities entry. They only served the worker. For videos no browser can play, the worker of Video Thumbnail Relay remains an option.

### Fixed
- Find and fix only saw 100 videos on Nextcloud 32 and later, whose search returns 100 results unless asked for more.

## 1.0.0 - 2026-10-12

First release of Video Thumbnail Relay, forked from [localthumbs](https://github.com/hersche/nc-local-thumbnailer) 1.0.1.

### Added
- Support for Nextcloud 31, 32 and 33, and PHP 8.2 to 8.5.
- An admin settings section for generating or removing the worker secret, so no `occ` access is needed.
- A daily background job that removes thumbnails of permanently deleted videos.
- Removal of all thumbnails and settings when the app is uninstalled.
- Brute-force throttling for requests with a wrong secret.

### Changed
- New app ID `clientside_video_thumbnails` and API path `/apps/clientside_video_thumbnails/thumbnail/…`.
- The secret header is now `X-ClientsideVideoThumbnails-Secret`, and the secret is stored as a sensitive app config value.
- The preview provider is registered through `IRegistrationContext::registerPreviewProvider`.
- Uploads must be JPEG images of 5 MB or less, for video files the user can access.
- API errors now return matching HTTP status codes.
- `deleteAll` is now restricted to administrators.
