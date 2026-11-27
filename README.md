# Clientside Video Thumbnails

Video previews for Nextcloud instances that can't run FFmpeg themselves, such as managed and shared hosting like Hetzner Storage Share.

There are two parts:

- **Nextcloud app** (`appinfo/`, `lib/`), app ID `clientside_video_thumbnails`. It adds an endpoint that accepts thumbnails from signed-in browsers, and a preview provider that serves them as native previews for video files.
- **Browser scripts** (`js/core.js`, `js/files-thumbs.js`, `js/personal.js`). `files-thumbs.js` is loaded by the app into the Files web interface. When a folder shows videos without a preview, the browser grabs one frame of each, shows it at once and uploads it. From then on the server serves it everywhere: Files, Photos, shares, mobile apps. `personal.js` is the find-and-fix mode described below.

## How the browser script works

- It only looks at rows currently on screen in the "All files" view, one video at a time.
- It takes the same frame Nextcloud's own video previews (ffmpeg) would: at 5 seconds, at 1 second for shorter videos, else the first. It keeps the full resolution up to 4096 pixels; the server scales it down for every smaller preview.
- It reads the video with a `<video>` element. The browser fetches only the parts it needs via HTTP range requests (the index and the data around the frame it needs), not the whole file.
- With the browser's data saver on (`navigator.connection.saveData`, Chrome and Android), it renders nothing.
- Under **Personal settings → Video thumbnails → When to render in Files**, each account can allow it only on Wi-Fi or Ethernet and only while charging. The switches apply on all devices of the account. Browsers that can't tell their network type or battery state (Safari, Firefox) render anyway.
- On mobile: if the browser ignores `preload` (iOS Safari), the script briefly starts muted playback to get the video loading. Before drawing it waits for the decoded frame (`requestVideoFrameCallback`), so mobile browsers don't hand out a black image.
- Videos the browser can't decode (often HEVC, MKV, AVI, depending on browser) are remembered in `localStorage` and not retried for 30 days. They stay without a preview, unless another browser can play them. Silversong88's [Video Thumbnail Relay](https://github.com/silversong88/video_thumbs_relay) has an FFmpeg worker for such videos.
- To switch it off in one browser: `localStorage.setItem('clientside_video_thumbnails.disabled', '1')` in the developer console.

It relies on markup that is the same in Nextcloud 31 to 34: list rows carry `data-cy-files-list-row-fileid` and `-name`, and a file without a server preview shows an image from `/core/mimeicon`.

## Find and fix mode

Under **Personal settings → Video thumbnails → Find and fix videos without thumbnail**, one DAV search finds all your videos without a preview, in every folder. The page then renders and uploads a frame for each, one at a time, with progress and a list of the videos that failed. Keep the page open and in front until it is done: browsers pause hidden pages. On phones it asks the browser to keep the screen on. It warns, but goes on, when the device is on mobile data, has the data saver on or is not charging. Tick **Also retry videos this browser could not render before** to ignore the failure cache, for example after switching to a browser with more codecs.

## Starting over

Under **Personal settings → Video thumbnails → Delete all my thumbnails**, every user can delete the thumbnails of their own videos. Videos others shared with them keep theirs.

For testing, **Administration settings → Additional settings → Clientside Video Thumbnails → Delete all video thumbnails** removes every thumbnail of all users, along with the previews Nextcloud made from them, and clears the failure cache of the browser you click it in. Videos get new thumbnails the next time they show up in Files or in find-and-fix mode.

`tests/vps/deploy.sh` puts a test instance on a server of your own over SSH; see the comment at its top.

## Getting started

1. Install **Clientside Video Thumbnails** from the Apps page of your Nextcloud.
2. Open a folder with videos in Files. That's it.

## Development

```sh
composer install
composer run lint                       # PHP syntax check
composer run psalm                      # static analysis (against nextcloud/ocp)
tests/integration/run-docker.sh 34      # end-to-end test in a throwaway Nextcloud 34 container
```

CI runs the same checks on every push and pull request: info.xml validation, PHP lint on PHP 8.2 and 8.5, psalm against the Nextcloud 31–34 public API, and the integration tests on Nextcloud 31 to 34. The browser script itself is not tested automatically; check it by hand in a real browser.

## Credits

This is a fork of [Video Thumbnail Relay](https://github.com/silversong88/video_thumbs_relay) by silversong88, which in turn builds on the work of Vinzenz Hersche:

- Nextcloud app: https://github.com/hersche/nc-local-thumbnailer (App Store ID `localthumbs`)
- Worker: https://github.com/hersche/local-nc-local-thumbnailer

The fork renders in the browser instead, so no machine with FFmpeg is needed, and drops the worker with its API secret.

## License

AGPL-3.0-or-later. See [LICENSE](LICENSE).
