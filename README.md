# antolin-isbn-webscan

Information for English-speaking interested parties:
This script is intended for use in school libraries that use a reader encouragement program from a German publisher. Therefore, it is written in German.

Ein Webinterface zum Scannen von ISBN-Buchcodes, identifiziere Bücher mit Antolin-Leseförderungs-Angebot

Dieses Skript ist ein Interface/Website zum Bücherscannen in Schulbüchereien/Schulbibliotheken, die für Ihre 
Schüler das Antolin-Leseförderungsprogramm vom Westermann-Verlag nutzen.

Siehe auch das von Westermann zur Recherche angebotene Interface unter https://antolin.westermann.de.
Dieses bietet aktuell (Stand: Mai 2022) keine repetitive Eingabemöglichkeit für ISBN-Codes an.

Frage: Für wen ist dieses Skript gedacht?
Antwort: Mitarbeiter/Helfer in Schulbüchereien, deren Schulen am "Antolin"-Programm teilnehmen und deren Bibliothekssoftware
beim Listen/Einpflegen neuer Bücher keine automatische Datenpflege zum "Antolin"-Status des Buches bereitstellen.

Frage: Was kann dieses Skript besser als die Bücher-Suche unter https://antolin.westermann.de/all/extendedsearch.jsp ?
Antwort: Es geht viel schneller! Kein Hin- und Her-"Geklicke" nach dem Ende der Suche, um eine weitere Abfrage zu starten.
Direkter Focus auf dem Eingabefeld für ISBN und nach erfolgter Suche sofort wieder scanbereit.

Informationen zu "Antolin" erhalten Sie unter: www.antolin.de (ein Angebot der Westermann Gruppe © Westermann Bildungsmedien Verlag GmbH)

Der Autor nutzt die Bibliothekssoftware Perpustakaan Version 5.X, siehe https://must.de/default.html?Lib.htm
Perpustakaan liest die Antolin-CSV-Datei automatisiert ein, nutzt diese Daten jedoch aktuell nur im Suchbereich.
Beim Listen der Bücher gibt es leider keine Datenübernahme, man muss die Antolin-Klassenstufe manuell recherchieren und eintragen.
Die "Antolin"-Bücher erhalten bei uns eine farbliche Kennzeichnung auf dem Buchrücken, damit die Kinder die Bücher leicht
identifizieren können. 

Dieses Skript wird Ihnen zur Verfügung gestellt von: Georg Künzel <schulbuecherei@7days-24hours.com>, © 2022

Systemanforderungen:

Webserver mit PHP-Unterstützung und - für den Datenimport - von PHP aus schreibfähiges Verzeichnis: nginx, apache, etc.
PHP: lauffähig ab Version 5.6, PDO-Unterstützung für Sqlite oder Mysql
Datenbank: Sqlite ab Version 3 oder Mysql (für andere SQL-Server einfach die Variable $dsn anpassen)
