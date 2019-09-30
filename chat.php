<?php
/*
 * Einfaches Chat-System in einem HTML-Fenster
 *
 * Chat-Anwendung mit Speicherung des Chats in einer .txt datei und
 * Adminbereich zur Statistik ausgabe und löschen des Chat Inhalts
 */

include "error.inc.php";
?>
<html>
<head>
    <link rel="stylesheet" type="text/css" href="chat.css">
</head>
<body>
<?php

// Business-Logik

/*
 * Die Klasse Chat bildet einen Chatroom ab.
 * Sie besteht aus Zeitlich sortierten Messages.
 */
class Chat
{
    // Speicherort Chatinhalt
    const CHAT_DATEI = "chat_text.txt";

    // Funktion zur ausgabe des Chats in eine Tabelle
    public function ausgabe()
    {
        if (!file_exists(self::CHAT_DATEI)) {
            fopen(self::CHAT_DATEI, "w");
        }
        $fp = @fopen(self::CHAT_DATEI, "r");
        if ($fp) {

            // Tabellenkopf des Chats
            echo "<table><tr><td><b>Zeit</b></td>" .
                "<td><b>Name</b></td>" .
                "<td><b>Beitrag</b></td></tr>";

            // Alle Zeilen lesen und ausgeben
            echo '<tr>';
            while (!feof($fp)) {
                $tabzeile = explode("|", fgets($fp, 200));
                foreach ($tabzeile as $cell) {
                    echo '<td>';
                    echo $cell;
                    echo '</td>';
                }
                echo '</tr>';
            }
        }
        echo "</table>";
        fclose($fp);

        // Button um die Chat Ausgabe neu zu Laden
        echo '<form action="chat.php">
            <input type="hidden" name="route" value="ausgabe" />
            <button id="reload" type="submit" >Chat neu laden</button>
            </form>';

        // gehe bei laden der Seite zu neuester Nachricht(Ans Ende der Seite)
        echo ' <script>
            function toBottom()
            {
                window.scrollTo(0, document.body.scrollHeight);
            }
            window.onload=toBottom();
        </script>';
    }

    // Speichert Nachricht, Namen, Datum und Uhrzeit in .txt Datei am Ende 'a'
    public function neueNachrichtSpeichern($message)
    {
        $fp = @fopen(self::CHAT_DATEI, "a");
        if ($fp) {
            $jetzt = date("d.m.y H:i:s");
            if ($message->name != "" && $message->inhalt != "") {
                $tabzeile = $jetzt . "|" . str_replace("|", "-", $message->name)
                . "|" . str_replace("|", "-", $message->inhalt);
                fputs($fp, $tabzeile . "\n");
                echo "Nachricht gesendet";

            } else {
                echo "Bitte fülle beide Felder aus!";
            }
        }
        fclose($fp);
    }

    // Löscht den inhalt der .txt Datei
    public function chatLeeren()
    {
        file_put_contents(self::CHAT_DATEI, "");
        echo "Und Zack! Chat wurde gelöscht!";
    }

    // Ausgabe der Statistiken
    public function ausgabeStatistik()
    {
        if (!file_exists(self::CHAT_DATEI)) {
            fopen(self::CHAT_DATEI, "w");
        }
        $fp = fopen(self::CHAT_DATEI, "r");

        while (!feof($fp)) {
            $zeile[] = fgets($fp, 200);
        }

        $lines = COUNT(FILE(self::CHAT_DATEI));
        echo "Der Chat hat bereits $lines Nachricht/en<br />";
        echo "Aktuellste Message: " . max($zeile) . "<br />";
        echo "Älteste Message: " . min($zeile) . "<br />";
        fclose($fp);
    }

    /**
     * html-ausgabe für admin-frame
     * formular mit 2 buttons fertig
     **/
    public function ausgabeAdministrationsFrame()
    {
        echo '<h3>Administrations Bereich</h3>' .
            '<form action="chat.php">
            <input type="hidden" name="route" value="stats" />
            <button type="submit" >Statistik<br />abrufen</button>
            </form>
            <form action="chat.php">
            <input type="hidden" name="route" value="loeschen" />
            <button type="submit" >Chatverlauf <br />Löschen</button>
            </form>';

    }
}

/**
 * Die Klasse Message ist die Nachricht eines
 * einzelnen Benutzers.
 **/
class Message
{
    public $name;
    public $inhalt;

    public function eingabeFormular($mitAuswertung = true)
    {
        if ($mitAuswertung && isset($_POST["beitrag"])) {
            $this->name = $_POST["nick"];
            $this->inhalt = $_POST["beitrag"];
            return true;
        }

        // HTML Formular für eingabe der Nachricht
        echo '<h3>Chat Eingabe</h3><form method="post">
            <table>
                <tr><td>Ihr Name:</td>
                    <td><input name="nick" size="20" /></td></tr>
                <tr><td>Ihr Beitrag:</td>
                    <td><textarea cols="50" rows="2" name="beitrag"></textarea>
                </td></tr><tr><td>
                    <input type="submit" name="absch" value="Abschicken" /></td><tr>


            </table>
        </form>';
        return false;
    }
}

//------Applikation-----------------------------

$chat = new Chat();

// Routing

// Zeige mit $_GET bestimmte Frames und steuere Funktionen
switch ($_GET["route"]) {
    case "eingabe":
        $m = new Message();
        if ($m->eingabeFormular()) {
            $chat->neueNachrichtSpeichern($m);
            $m->eingabeFormular(false);
        }
        break;
    case "ausgabe":
        $chat->ausgabe();
        break;
    case "administration":
        $chat->ausgabeAdministrationsFrame();
        break;
    case "loeschen":
        $chat->chatLeeren();
        $chat->ausgabeAdministrationsFrame();
        break;
    case "stats":
        $chat->ausgabeStatistik();
        $chat->ausgabeAdministrationsFrame();
        break;
    default:
        echo "error!";
}
?>
</body>
</html>