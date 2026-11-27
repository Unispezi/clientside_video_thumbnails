# Clientside Video Thumbnails

Video previews for Nextcloud instances that can't run FFmpeg themselves, such as managed and shared hosting like Hetzner Storage Share.

There are up to three parts:

- **Nextcloud app** (`appinfo/`, `lib/`), app ID `clientside_video_thumbnails`. It adds an API that accepts thumbnails, and a preview provider that serves them as native previews for video files.
- **Browser script** (`js/files-thumbs.js`), loaded by the app into the Files web interface. When a folder shows videos without a preview, the browser grabs one frame of each, shows it at once and uploads it. From then on the server serves it everywhere: Files, Photos, shares, mobile apps.
- **Worker** (`worker/`), optional. A Node.js script with FFmpeg that runs on a machine you control. It scans your files over WebDAV, so it also covers videos the browser can't play and folders nobody opens in the web interface.

## How the browser script works

- It only looks at rows currently on screen in the "All files" view, one video at a time.
- It reads the video with a `<video>` element. The browser fetches only the parts it needs via HTTP range requests (the index and the data around second 1), not the whole file.
- Videos the browser can't decode (often HEVC, MKV, AVI, depending on browser) are remembered in `localStorage` and not retried for 30 days. Use the worker for those.
- To switch it off in one browser: `localStorage.setItem('clientside_video_thumbnails.disabled', '1')` in the developer console.

It relies on markup that is the same in Nextcloud 31 to 34: list rows carry `data-cy-files-list-row-fileid` and `-name`, and a file without a server preview shows an image from `/core/mimeicon`.

## Getting started

1. Install **Clientside Video Thumbnails** from the Apps page of your Nextcloud.
2. Open a folder with videos in Files. That's it.

Optional worker, for videos your browser can't play:

1. Under **Administration settings → Additional settings → Clientside Video Thumbnails**, generate an API secret.
2. Under **Personal settings → Security → Devices & sessions**, create an app password for the worker.
3. On a Linux machine with FFmpeg, set up the worker. See [worker/README.md](worker/README.md).

## Development

```sh
composer install
composer run lint                       # PHP syntax check
composer run psalm                      # static analysis (against nextcloud/ocp)
tests/integration/run-docker.sh 34      # end-to-end test in a throwaway Nextcloud 34 container
WORKER=1 tests/integration/run-docker.sh 31   # include the worker (needs node, ffmpeg, npm ci in worker/)
```

CI runs the same checks on every push and pull request: info.xml validation, PHP lint on PHP 8.2 and 8.5, psalm against the Nextcloud 31–34 public API, and the integration tests on Nextcloud 31 to 34. The browser script itself is not tested automatically; check it by hand in a real browser.

## Credits

This is a fork of [Video Thumbnail Relay](https://github.com/silversong88/video_thumbs_relay) by silversong88, which in turn builds on the work of Vinzenz Hersche:

- Nextcloud app: https://github.com/hersche/nc-local-thumbnailer (App Store ID `localthumbs`)
- Worker: https://github.com/hersche/local-nc-local-thumbnailer

The fork adds rendering in the browser, so no machine with FFmpeg is needed for most videos.

## License

AGPL-3.0-or-later. See [LICENSE](LICENSE).
