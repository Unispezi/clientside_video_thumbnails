---
description: Video-Vorschaubilder für Nextcloud ohne FFmpeg, erzeugt in deinem Browser
---
# Clientside Video Thumbnails für Nextcloud

[English](../index.md)

Video-Vorschaubilder für Nextcloud-Instanzen ohne FFmpeg, etwa bei Managed Hosting wie Hetzner Storage Share. Dein Browser erzeugt sie.

## So funktioniert es

1. Du öffnest in Dateien einen Ordner mit Videos.
2. Für jedes Video ohne Vorschaubild lädt dein Browser einen kleinen Teil, nimmt ein Einzelbild und zeigt es sofort an.
3. Er lädt das Bild hoch. Ab dann liefert Nextcloud es als normales Vorschaubild aus: in Dateien, Fotos, Freigaben und den Mobil-Apps.

Es ist dasselbe Einzelbild, das Nextcloud selbst nehmen würde: bei 5 Sekunden, bei kürzeren Videos bei 1 Sekunde.

**Alle auf einmal:** Unter **Persönliche Einstellungen → Clientside Video Thumbnails** erzeugt **Fehlende Vorschaubilder erzeugen** in einem Rutsch Vorschaubilder für alle deine Videos, die noch keins haben. Siehe [Für Nutzer](user.md).

## Hilfe

- [Für Nutzer](user.md): Einstellungen, alle fehlenden Vorschaubilder auf einmal, mobile Daten und Akku.
- [Für Admins](admin.md): Installation, was der Server speichert, alle Vorschaubilder löschen.

## Grenzen

**Nur im Webbrowser.** Vorschaubilder entstehen nur, wenn Nextcloud im Webbrowser läuft, am PC oder am Handy. Die nativen Nextcloud-Apps, zumindest die für Android, führen den Code dieser App nicht aus: Sie erzeugen keine Vorschaubilder, zeigen aber die, die der Browser gemacht hat. Öffne deine Nextcloud am Handy also in Chrome, Safari oder Firefox statt in der App.

**Videos, die der Browser nicht abspielen kann.** Dein Browser kann nur Videos rendern, die er abspielen kann. Je nach Browser und Gerät bleiben HEVC (H.265), MKV oder AVI ohne Vorschaubild. Ein anderer Browser schafft sie vielleicht: Safari spielt zum Beispiel HEVC.

## Danke

Diese App baut auf der Arbeit anderer auf. Herzlichen Dank an **Vinzenz Hersche** für [localthumbs](https://github.com/hersche/nc-local-thumbnailer), die Idee, anderswo erzeugte Vorschaubilder als Nextcloud-Vorschau auszuliefern, und an **silversong88** für [Video Thumbnail Relay](https://github.com/silversong88/video_thumbs_relay), aus dem diese App hervorgegangen ist.

## Links

- [Quellcode und Fehlermeldungen](https://github.com/Unispezi/clientside_video_thumbnails)
- [Änderungen](https://github.com/Unispezi/clientside_video_thumbnails/blob/main/CHANGELOG.md) (englisch)
