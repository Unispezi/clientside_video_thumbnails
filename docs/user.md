# For users

[← Overview](index.md)

## Thumbnails while you browse

Open a folder with videos in Files. Videos without a preview get one after a few seconds. Nothing to set up.

This happens only in the browser, in the "All files" view, for the videos on screen. The Nextcloud apps for Android and iOS show the thumbnails, but don't create them.

## Settings

**Personal settings → Clientside Video Thumbnails**

### Find and fix videos without thumbnail

**Create missing thumbnails** searches all your files for videos without a preview and renders them one by one.

- Keep the page open and in front until it is done. Browsers pause pages in the background.
- On phones the screen stays on while it runs.
- It warns you on mobile data, with the data saver on, or when the device isn't charging. It runs anyway.
- **Retry videos with errors**: videos your browser could not render are skipped for 30 days. Tick this to try them again, for example in another browser.

### Automatically generate video thumbnails

- **Mobile device: only on Wi-Fi**
- **Mobile device: only while charging**

These apply to your account on all your devices, for thumbnails created while you browse. Chrome on Android knows network and battery state. Safari and Firefox don't tell, so they render anyway. The line below the switches shows what your current device reports.

With the data saver on, nothing is rendered while you browse.

### Delete ALL my thumbnails

Deletes the thumbnails of all your own videos. Videos others shared with you keep theirs. They are created again the next time a video is listed.

## Questions

**A video has no thumbnail.**
Your browser probably can't play it. Open the video: if it doesn't play, the browser can't render a thumbnail either. Try another browser, then **Create missing thumbnails** with **Retry videos with errors**.

**Does it download the whole video?**
No. The browser loads the index of the file and the part around the frame, usually a few MB.

**I copied a video.**
The copy gets the thumbnail of the original. Moved and renamed videos keep theirs.

**Switch it off in one browser only:**
In the browser's developer console: `localStorage.setItem('clientside_video_thumbnails.disabled', '1')`
