<?php
/*
 * Einfaches Chat-System in einem HTML-Fenster
 *
 * Chat-Anwendung mit Speicherung des Chats in einer Datenbank "chat" - Tabelle "message" und
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

class DbTableMessage
{
    public $datum;
    public $name;
    public $inhalt;

    public function outputAsHtmlTableRow()
    {

        echo '<tr>';
        echo "<td>" . $this->datum . '</td>';
        echo "<td>" . $this->name . '</td>';
        echo "<td>" . $this->inhalt . '</td>';
        echo '</tr>';
    }
}

/*
 * Die Klasse Chat bildet einen Chatroom ab.
 * Sie besteht aus Zeitlich sortierten Messages.
 */

class Chat
{
    // Datenbank-Verbindung
    public $db;
    public function __construct($dsn, $username, $pwd)
    {
        try {
            $this->db = new PDO($dsn, $username, $pwd);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new Error("Konnte Verbindung nicht heerstellen " . $e->getMessage());
        }
    }

    // Funktion zur ausgabe des Chats in eine Tabelle
    public function ausgabe()
    {
        // Tabellenkopf des Chats
        echo "<table><tr><td><b>Zeit</b></td>" .
            "<td><b>Name</b></td>" .
            "<td><b>Beitrag</b></td></tr>";

        // Alle Zeilen lesen und ausgeben

        $stmt = $this->db->prepare("SELECT * FROM message ORDER BY datum");
        $stmt->execute();
        $messages = $stmt->fetchALL(PDO::FETCH_CLASS, "DbTableMessage");
        foreach ($messages as $message) {

            $message->outputAsHtmlTableRow();
        }
        echo "</table>";

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

    // Speichert Nachricht, Namen, Datum und Uhrzeit in Datenbank
    public function neueNachrichtSpeichern($message)
    {
        if ($message->name != "" && $message->inhalt != "") {
            $sql = "INSERT INTO message
                         (name, inhalt)
                         VALUES ('" . $_POST['nick'] . "', '" . $_POST['beitrag'] . "')";
            $betroffeneZeilen = $this->db->exec($sql);
            echo "Nachricht gesendet";

        } else {
            echo "Bitte fülle beide Felder aus!";
        }
    }

    // Löscht den Inhalt der Datenbank
    public function chatLeeren()
    {
        $sql = "DELETE FROM message";
        $daten = $this->db->exec($sql);
        echo "Und Zack! Chat wurde gelöscht!";
    }

    // Ausgabe der Statistiken
    public function ausgabeStatistik()
    {
        $sql = "SELECT datum FROM message WHERE inhalt IS NOT NULL";
        $sqlanzahl = "SELECT COUNT(datum) FROM message WHERE inhalt IS NOT NULL";
        $ergebnisanzahl = $this->db->query($sqlanzahl);
        $anzahl = $ergebnisanzahl->fetchColumn();
        echo "<p>Anzahl der Nachrichten: $anzahl</p>\n";

        $max = "SELECT MAX(datum) FROM message";
        $neu = $this->db->query($max);
        $min = "SELECT MIN(datum) FROM message";
        $alt = $this->db->query($min);

        echo "Neueste Message:<br />" . $neu->fetchColumn() . "<br /><br />";
        echo "Älteste Message:<br />" . $alt->fetchColumn();
    }

    /**
     * html-ausgabe für admin-frame
     * form mit 2 buttons
     **/
    public function ausgabeAdministrationsFrame()
    {
        echo
            '<h3>Administrations Bereich</h3>' .
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

$chat = new Chat("mysql:host=localhost;dbname=chat;charset=UTF8", "admin", "password");

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
        echo 'Error! <br /> Please go to "chat.html"! Thank you :D';
}
?>
</body>
</html>
