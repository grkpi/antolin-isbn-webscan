<?php
/*
Dieses Skript ist ein Interface/Website zum Bücherscannen in Schulbüchereien/Schulbibliotheken, die für Ihre 
Schüler das "Antolin"-Leselernprogramm vom Westermann-Verlag nutzen.

Siehe auch das von Westermann zur Recherche angebotene Interface unter https://antolin.westermann.de.
Dieses bietet aktuell (Stand: Mai 2022) keine repetitive Eingabemöglichkeit für ISBN-Codes an.

Frage: Für wen ist dieses Skript gedacht?
Antwort: Mitarbeiter/Helfer in Schulbüchereien, deren Schulen am "Antolin"-Programm teilnehmen und deren Bibliothekssoftware
beim Listen/Einpflegen neuer Bücher keine automatische Datenpflege zum "Antolin"-Status des Buches bereitstellen.

Frage: Was kann dieses Skript besser als die Bücher-Suche unter https://antolin.westermann.de?
Antwort: Es geht viel schneller! Kein Hin- und Her-"Geklicke". Direkter Focus auf dem Eingabefeld für ISBN und nach erfolgter Suche sofort wieder scanbereit.

Informationen zu "Antolin" erhalten Sie unter: www.antolin.de (ein Angebot der Westermann Gruppe © Westermann Bildungsmedien Verlag GmbH)

Der Autor nutzt die Bibliothekssoftware Perpustakaan Version 5.X, siehe https://must.de/default.html?Lib.htm
Perpustakaan liest die Antolin-CSV-Datei automatisiert ein, nutzt diese Daten jedoch aktuell nur im Suchbereich.
Beim Listen der Bücher gibt es leider keine Datenübernahme, man muss die Antolin-Klassenstufe manuell recherchieren und eintragen.
Die "Antolin"-Bücher erhalten bei uns eine farbliche Kennzeichnung auf dem Buchrücken, damit die Kinder die Bücher leicht
identifizieren können. 

Dieses Skript wird Ihnen zur Verfügung gestellt von: Georg Künzel <schulbuecherei@7days-24hours.com>, © 2022

Dieses Programm ist Freie Software: Sie können es unter den Bedingungen der GNU General Public License, wie von der Free Software Foundation,
Version 3 der Lizenz oder (nach Ihrer Wahl) jeder neueren veröffentlichten Version, weiter verteilen und/oder modifizieren.

Dieses Programm wird in der Hoffnung bereitgestellt, dass es nützlich sein wird, jedoch OHNE JEDE GEWÄHR,; sogar ohne die implizite Gewähr 
der MARKTFÄHIGKEIT oder EIGNUNG FÜR EINEN BESTIMMTEN ZWECK. Siehe die GNU General Public License für weitere Einzelheiten.

Sie sollten eine Kopie der GNU General Public License zusammen mit diesem Programm erhalten haben. Wenn nicht, siehe <https://www.gnu.org/licenses/>. 

*/

// Fehlermeldungen anzeigen
//error_reporting(E_ALL); // aktivieren für Fehlerdarstellung
//ini_set('display_errors', true);  // aktivieren für Fehlerdarstellung

// ################################################ Start Konfiguration ################################################ 
// Grunddaten für Ihre Installation
//$dbtype="mysql"; // manuell einstellen für forcierte Datenbank-Auswahl, standardmäßig ist dieser Wert auskommentiert, Wertebereich: mysql/sqlite
$datenbank = "db/antolin.db"; // Pfad und Dateiname für Sqlite-Datei/Datenbank auf Ihrem Webserver

// für Sqlite-Verwendung:
$csv = "db/antolingesamt.csv"; // Pfad und Dateiname für die CSV-Quelldatei (die vom Westermann-Verlag zum Download angeboten wird) auf Ihrem Webserver
// für MySQL-Verwendung:
$host     = "localhost";
$db       = "datenbankname";
$user     = "datenbanbenutzer";
$password = "datenbankkennwort";

$update_user=true; // Benutzer darf DB aktualisieren, Wertebereich: true/false		
$table = "antolingesamt"; // Tabellenname für die Antolin-Daten, frei wählbar
$minrows = 100000; // Mindestanzahl Datensätze in Tabelle  
$salt = "#dh§G98_HzId?_89"; // "Salz"/Ergänzungswert für SHA1-Berechnung des Update-Get-Parameters 
$tage_bis_csv_datei_alt = 2; // nach diesem Wert in Tagen wird ein Update angeboten (sofern $update_user=true)

// Antolin-Quelldaten
$url  = 'https://antolin.westermann.de/all/downloads/antolingesamt.csv';
$delimiter = ';'; // CSV-Trennzeichen in der Antolin-Datei
// ################################################ Ende Konfiguration ################################################ 


// ab hier hoffentlich keine Änderung notwendig
$updateprozess = false; // Initialisierungswert: kein Update der DB notwendig
if (((extension_loaded('sqlite3')) && (! isset($dbtype))) || ($dbtype=="sqlite")) {
	$dbtype="sqlite";
	// Schreibrechte überprüfen
	$updatefaehig=true;
	if (! is_file($datenbank)) {
		echo "<p><b>Sqlite-Datenbank nicht vorhanden, versuche Update-Prozess zu starten...</b></p>";
		}
	@touch ($datenbank);
	if (! is_writable($datenbank)) {
	  	// Schreibrechte setzen
		if (! @chmod($datenbank, 0666)) {
			$updatefaehig=false;
	  		}
		}
	// check für nicht updatefähige Datenbank		
	if ($pdo = new PDO('sqlite:'.$datenbank)) {
		// TODO check: if $updatefaehig==false > DB muss gefüllt sein, ansonsten die()
		if ($updatefaehig==false) {
			$error=false;
			$statement = $pdo->query("SHOW TABLES LIKE '%".$table."%';");
			if(count($statement)==0) {
				$error=true;
				}
			else {
				if ($statement = $pdo->query("SELECT count(*) as anzahl FROM ".$table.";")) {
					$result = $statement->fetch(PDO::FETCH_ASSOC);
					if ($result["anzahl"]<200000) {
						$error=true;
						}
					}
				else {
					$error=true;
					}
				}
			if($error==true) {	
				die("Die SQL-Datenbank ist nicht schreibbar (somit nicht updatef&auml;hig) und die ".$table."-Tabelle hat zu wenig (".$result["anzahl"].") Eintr&auml;ge. Die Tabelle manuell mit Daten f&uuml;llen!");
				}
			}
		// check für updatefähige Datenbank				
		else {
			$statement = $pdo->query("SHOW TABLES LIKE '%".$table."%';");
			if(count($statement)==0) {
				$updateprozess = true; // Update der DB ist notwendig		
				}
			else {
				if ($statement = $pdo->query("SELECT count(*) as anzahl FROM ".$table.";")) {
					$result = $statement->fetch(PDO::FETCH_ASSOC);
					if ($result["anzahl"]<$minrows) {
						$updateprozess = true;  // Update der DB ist notwendig	
						}
					}
				}	
			}
		}
	else {
		die("Kein Connect zu Sqlite-Datenbank m&ouml;glich. Manuell auf MySQL-Verwendung umstellen (&#36dbtype=mysql) oder Sqlite-Datenbank unter <i>$datenbank</i> kontrollieren!");
		}
	} 
elseif ((in_array("mysql",PDO::getAvailableDrivers()) && (! isset($dbtype))) || ($dbtype=="mysql")) {
	$dbtype="mysql";
	$dsn = "mysql:host=$host;dbname=$db;charset=UTF8";
	try {
		$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
		$pdo = new PDO($dsn, $user, $password, $options);
		if ($pdo) {
			$updatefaehig=true;
			}
		} 
	catch (PDOException $e) {
		if (isset($dbtype) && $dbtype=="mysql") {
			die("Kein Connect zu MySQL-Datenbank m&ouml;glich. Nachfolgenden Fehler beheben oder auf Sqlite umstellen (hierzu &#36dbtype=mysql auskommentieren)!<br>MySQL-Ausgabe: ".$e->getMessage());
			}
		else {
			die("Kein Connect zu MySQL-Datenbank m&ouml;glich. Nachfolgenden Fehler beheben oder auf Sqlite umstellen (hierzu &#36dbtype=sqlstring oder auskommentieren)!<br>MySQL-Ausgabe: ".$e->getMessage());	
			}
		}
	// CHECK mysql, ob Tabellen vorhanden
	$statement = $pdo->query("SHOW TABLES LIKE '%".$table."%';");
	$result = $statement->fetch(PDO::FETCH_NUM);
	if (! is_object($result) ||  trim($result[0])<>$table) {
		$updateprozess = true;  // Update der DB ist notwendig		
		}
	else {
		if ($statement = $pdo->query("SELECT count(*) as anzahl FROM ".$table.";")) {
			$result = $statement->fetch(PDO::FETCH_ASSOC);
			if ($result["anzahl"]<$minrows) {
				$updateprozess = true;  // Update der DB ist notwendig	
				}
			}
		}	
	}
 else {
	die("Weder Mysql- noch Sqlite-Unterst&uuml;tzung in PHP gefunden. Somit ist das Skript nicht lauff&auml;hig!");
	}

// Kontrolle der lokalen und remote CSV Datei
$filetime = "<b>unbekannt</b>";
$checkremote=true;
$update=""; // Standard: kein Update notwendig, CSV vorhanden und nicht veraltet
if (file_exists($csv)) {
	$checkremote=false; // CSV-Datei ist vorhanden, erstmal kein Remote-Check notwendig
	$filetime = date("d.m.Y H:i",filemtime($csv));
	$lokalfiletime = filemtime($csv);
	$yesterday = strtotime("today - ".(int)$tage_bis_csv_datei_alt." days");
	if ($yesterday > $lokalfiletime) { 
		$checkremote=true; // CSV ist veraltet, ein Check ist notwendig, ob Remote-Datei verfügbar
		}
	}

// Get-Parameter
$getparam = sha1($salt+date("Ymd"));
if ($update_user==true && $updatefaehig==true) {

	// hier geht es um die Antolin-CSV-Datei bei Westermann	
	if ($checkremote==true) {
		// Versuche Remote-Datei lesend zu Öffnen 
		$handle = @fopen($url, 'r');
		if ($handle) {
	   	$update=" - <a href=".basename($_SERVER["SCRIPT_FILENAME"])."?update=".$getparam.">UPDATE starten</a>"; // Remote-Datei vorhanden, biete Update an
			}
		else {
			$update=" - Antolin-Daten nicht verf&uuml;gbar unter: ".$url; // Update wäre notwendig, aber keine Remote-Datei auffindbar
			// alternativ per CURL
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_NOBODY, true);
	  		curl_exec($ch);
	  		$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	  		curl_close($ch);
	  		if($code != 200){
				$update=" - <a href=".basename($_SERVER["SCRIPT_FILENAME"]).".php?update=".$getparam.">UPDATE starten</a>"; // Remote-Datei vorhanden, biete Update an
				}
			}
		}

	// Update-Anfrage erhalten
	if ( (isset($_GET["update"])) && ($_GET["update"]=(2*date("Ymd"))) )  { // Update gewünscht
		if ((!file_exists($csv)) || ($yesterday > $lokalfiletime)) { // keine CSV-Datei oder CSV-Datei älter als Vorgabe
			$updateprozess = true;
			}
		}
	}

function import_csv_to_pdotable(&$pdo, $csv_path, $options = array())
{
	global $delimiter,$table;		
	extract($options);
	
	if (($csv_handle = fopen($csv_path, "r")) === FALSE) {
		die('Einlesen der CSV-Quelldatei fehlgeschlagen!');
		}
		
	if(! isset($fields)){
		$fields = array_map(function ($field) {
			return preg_replace("/[^A-Z0-9]-_|\s+/i", '', $field);
		}, fgetcsv($csv_handle, 0, $delimiter));
	}

	$create_fields_str = join(', ', array_map(function ($field){
		return "`".$field."` TEXT NULL";
	}, $fields));
	
	$pdo->beginTransaction();
	
	echo "<p><b>Beginne Import der CSV-Daten in die Datenbank...</b></p>";

	$create_table_sql = "DROP TABLE IF EXISTS $table";
	$pdo->exec($create_table_sql);
	
	$create_table_sql = "CREATE TABLE IF NOT EXISTS $table ($create_fields_str)";
	$pdo->exec($create_table_sql);

	$insert_fields_str = join('`, `', $fields);
	$insert_values_str = join(', ', array_fill(0, count($fields),  '?'));
	$insert_sql = "INSERT INTO $table (`".$insert_fields_str."`) VALUES ($insert_values_str)";
	$insert_sth = $pdo->prepare($insert_sql);
	
	$inserted_rows = 0;
	while (($data = fgetcsv($csv_handle, 0, $delimiter)) !== FALSE) {
		foreach ($data as $key => $value) {
			$data[$key]=utf8_encode($value);
			}
		$insert_sth->execute(str_replace("'","",str_replace("\"", "", $data)));
		$inserted_rows++;
		echo "Schreibe Datensatz Nr. $inserted_rows <br>";
		flush();
		ob_flush();
	}
	
	if ($pdo->commit()) {
		echo "<p><b>Import der CSV-Daten erfolgreich abgeschlossen. Das Skript ist betriebsbereit!</b></p>";
		}
	
	fclose($csv_handle);
	}
	
// es erfolgt ein Update bzw. der initiale Import der Antolin-Daten
if ($updateprozess==true) {	
	// CSV-Datei von Westermann holen
	$fp = fopen($csv, 'w');
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_FILE, $fp);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	$data = curl_exec($ch);
	curl_close($ch);
	fclose($fp);
	// Update starten
	set_time_limit(1800); // Sekunden
	import_csv_to_pdotable($pdo, $csv, $options=array());
	}

// ISBN-Scan wurde übertragen
$anzahl=0;
if (isset($_GET["isbn"])) {
	$statement = $pdo -> prepare ("SELECT * FROM antolingesamt WHERE  isbn = :isbn or  isbn-10 = :isbn or isbn-13 = :isbn or isbn13 = :isbn;");
	$statement -> execute(array(':isbn' => $_GET["isbn"]));
	$result = $statement -> fetchAll();
	$anzahl = count($result);
	}

?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Antolin-Recherche nach ISBN</title>
</head>
<body>
<b>Antolin Recherche per ISBN-Scan</b>
<p>
<div style="align:right;">Letztes Update der Antolin-Daten am: 
<?php 
// Letzter Download der CSV-Datei
echo $filetime;
// ggf. Link oder Info zum Quelldaten-Update anbieten
if ($update_user==true) {
	echo $update;
	}
?>
</div>
<br>
<form id="form" name="suche" method="GET">
 <p>
<label title="ISBN-Nummer zur Recherche.&#10;Zugriffstaste: I">
<ins>I</ins>SBN: &nbsp; <input type="text" name="isbn" size="30" minlength="10" maxlength="25" required="required" accesskey="i" autofocus="autofocus" placeholder="ISBN 10/13 mit oder ohne Bindestriche" spellcheck="false" onChange="javascript:document.suche.submit();">
</label> &nbsp; 
<button type="submit" title="Das Formular absenden">Absenden</button>
</p>
</form>
<?php
// Anzeige Suchergebnisse
if (isset($_GET["isbn"])) {
	echo "<p>";
	if ($anzahl>0) {
		if ($anzahl==1) {
			echo "<b>Zu diesem Buch wurde folgender Antolin-Eintrag gefunden:</b><p>";}
		else {
			echo "<b>Zu Ihrer Eingabe ".$_GET["isbn"]." wurden folgende Antolin-Eintr&auml;ge gefunden:</b><p>";}
		echo "<table style='border-spacing: 20px;'>"; 
		echo "<tr><th>Autor</th><th>Titel</th><th>Verlag</th><th valign=top align=left>f&uuml;r Klasse</th><th valign=top align=left>Antolin verf&uuml;gbar seit</th><th>Anzahl gelesen</th><th>ISBN-13</th><tr>";
		foreach ($result as $row) {
			echo "<tr>";
			echo "<td valign=top align=left>".$row["Autor"]."</td>";
			echo "<td valign=top align=left>".$row["Titel"]."</td>";
			echo "<td valign=top align=left>".$row["Verlag"]."</td>";
			echo "<td valign=top align=left>".$row["Klasse"]."</td>";
			echo "<td valign=top align=left>".$row["inAntolinseit"]."</td>";
			echo "<td valign=top align=left>".$row["wieoftgelesen"]."</td>";
			echo "<td valign=top align=left>".$row["ISBN-13"]."</td>";						
			echo "</tr>";
			}
		echo "</table></p>";
		}
	else {
		echo "<b>Zu Ihrer Eingabe ".$_GET["isbn"]." konnte kein Antolin-Eintrag gefunden werden!</b>";
		}
	echo "</p>";	
	}
?>
</p><br>&nbsp;<br>
</body>
</html>