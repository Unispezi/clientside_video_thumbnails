# Für Nutzer

[← Übersicht](index.md) · [English](../user.md)

## Vorschaubilder beim Durchblättern

Öffne in Dateien einen Ordner mit Videos. Videos ohne Vorschaubild bekommen nach ein paar Sekunden eins. Einzurichten ist nichts.

Das passiert nur im Webbrowser, in der Ansicht „Alle Dateien", für die Videos, die gerade zu sehen sind.

**Am Handy den Browser nehmen, nicht die Nextcloud-App.** Die nativen Apps, zumindest die für Android, erzeugen keine Vorschaubilder. Sie zeigen die, die dein Browser gemacht hat.

Alle fehlenden Vorschaubilder auf einmal: siehe [Videos ohne Vorschaubild finden und reparieren](#videos-ohne-vorschaubild-finden-und-reparieren) unten.

## Einstellungen

**Persönliche Einstellungen → Clientside Video Thumbnails**

### Videos ohne Vorschaubild finden und reparieren

**Fehlende Vorschaubilder erzeugen** durchsucht alle deine Dateien nach Videos ohne Vorschaubild und erzeugt sie nacheinander.

- Lass die Seite offen und im Vordergrund, bis es fertig ist. Browser pausieren Seiten im Hintergrund.
- Am Handy bleibt der Bildschirm an, solange es läuft.
- Es warnt bei mobilen Daten, eingeschaltetem Datensparmodus oder wenn das Gerät nicht lädt. Es läuft trotzdem.
- **Videos mit Fehlern erneut versuchen:** Videos, die dein Browser nicht rendern konnte, werden 30 Tage lang übersprungen. Mit dem Haken versucht er sie erneut, etwa in einem anderen Browser.

### Video-Vorschaubilder automatisch erzeugen

- **Mobilgerät: nur im WLAN**
- **Mobilgerät: nur beim Laden**

Sie gelten für dein Konto auf allen deinen Geräten, für Vorschaubilder beim Durchblättern. Chrome auf Android kennt Netz und Ladezustand. Safari und Firefox verraten das nicht, dort wird trotzdem gerendert. Die Zeile unter den Schaltern zeigt, was dein aktuelles Gerät meldet.

Mit eingeschaltetem Datensparmodus wird beim Durchblättern nichts gerendert.

### ALLE meine Vorschaubilder löschen

Löscht die Vorschaubilder all deiner eigenen Videos. Videos, die andere mit dir teilen, behalten ihre. Sie werden neu erzeugt, sobald ein Video wieder in einer Liste erscheint.

## Fragen

**Die Nextcloud-App auf meinem Handy zeigt keine Vorschaubilder.**
Die App erzeugt keine. Öffne Nextcloud einmal im Browser des Handys oder starte **Fehlende Vorschaubilder erzeugen** in den Einstellungen. Danach zeigt sie auch die App.

**Ein Video hat kein Vorschaubild.**
Wahrscheinlich kann dein Browser es nicht abspielen. Öffne das Video: Spielt es nicht, kann der Browser auch kein Vorschaubild erzeugen. Probier einen anderen Browser, dann **Fehlende Vorschaubilder erzeugen** mit **Videos mit Fehlern erneut versuchen**.

**Wird das ganze Video heruntergeladen?**
Nein. Der Browser lädt das Inhaltsverzeichnis der Datei und den Teil um das Einzelbild, meist ein paar MB.

**Ich habe ein Video kopiert.**
Die Kopie bekommt das Vorschaubild des Originals. Verschobene und umbenannte Videos behalten ihres.

**Nur in einem Browser abschalten:**
In der Entwicklerkonsole des Browsers: `localStorage.setItem('clientside_video_thumbnails.disabled', '1')`
