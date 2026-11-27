# Changelog

All notable changes to this project are documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 1.1.0 - unreleased

Forked from [Video Thumbnail Relay](https://github.com/silversong88/video_thumbs_relay) 1.0.0 as Clientside Video Thumbnails.

### Added
- The Files app renders previews for videos on screen that have none, right in the browser, shows them at once and uploads them. No worker is needed for videos the browser can play.
- Support for Nextcloud 34.

### Changed
- New app ID `clientside_video_thumbnails`, API path `/apps/clientside_video_thumbnails/thumbnail/…` and secret header `X-ClientsideVideoThumbnails-Secret`.
- Signed-in browser sessions with a valid request token can use the API without the secret.

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
