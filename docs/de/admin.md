# Für Admins

[← Übersicht](index.md) · [English](../admin.md)

## Installation

Apps → Multimedia → **Clientside Video Thumbnails** → Herunterladen und aktivieren. Nextcloud 31 bis 34.

Kein FFmpeg, kein zusätzlicher Server, keine Konfiguration.

## Was der Server tut

- Nimmt ein JPEG pro Video an, bis 16 MB, nur von einem angemeldeten Nutzer für ein Video, auf das er zugreifen darf. Uploads laufen durch Nextclouds normale CSRF-Prüfung.
- Speichert es im Datenordner der App und liefert es als Vorschaubild des Videos aus. Nextcloud skaliert es für jede Vorschaugröße.
- Kopien eines Videos bekommen sein Vorschaubild. Ein täglicher Hintergrundjob löscht Vorschaubilder von endgültig gelöschten Videos.
- Speichert pro Nutzer zwei Schalter: nur im WLAN, nur beim Laden.

Es gehen keine Daten an Dritte. Die Videos liest der Browser des Nutzers selbst, mit dessen eigener Anmeldung.

## Admin-Einstellungen

**Verwaltungseinstellungen → Zusätzliche Einstellungen → Clientside Video Thumbnails**

**Alle Vorschaubilder aller Nutzer löschen** entfernt alle gespeicherten Vorschaubilder und die Vorschauen, die Nextcloud daraus gemacht hat. Browser erzeugen neue, sobald Videos wieder in einer Liste erscheinen. Gedacht zum Testen.

## Deinstallation

Beim Entfernen der App werden alle gespeicherten Vorschaubilder, ihre Einstellungen und die Schalter der Nutzer gelöscht.

## Videos, die kein Browser abspielen kann

Sie bleiben ohne Vorschaubild. Wer die braucht: Die App [Video Thumbnail Relay](https://github.com/silversong88/video_thumbs_relay) hat einen FFmpeg-Worker, der auf einem eigenen Rechner läuft. Statt dieser App einsetzen, nicht zusätzlich.

## Probleme

[Melde einen Fehler](https://github.com/Unispezi/clientside_video_thumbnails/issues) mit deiner Nextcloud-Version, dem Browser und, wenn möglich, den Einträgen im Nextcloud-Log, die `clientside_video_thumbnails` erwähnen. Gern auf Englisch.
