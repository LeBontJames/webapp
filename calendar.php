<?php
session_start();
include 'db.php'; // Connessione al database

if (!isset($_SESSION['agenzia_id'])) {
    header('Location: index.php');
    exit;
}

$agenzia_id = $_SESSION['agenzia_id'];
$colore_agenzia = $_SESSION['colore_agenzia'];
$nome_agenzia = $_SESSION['nome_agenzia'];

// Impostazioni per il mese e l'anno corrente o selezionato
if (isset($_POST['mese']) && isset($_POST['anno'])) {
    $month = $_POST['mese'];
    $year = $_POST['anno'];
} else {
    $month = date('m'); // Mese corrente
    $year = date('Y'); // Anno corrente
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['occupa'])) {
        $data = $_POST['data'];
        $fascia_oraria = $_POST['fascia_oraria'];

        // Verifica se la sezione è già occupata
        $query = $conn->prepare("SELECT * FROM prenotazioni WHERE data = ? AND fascia_oraria = ?");
        $query->bind_param('ss', $data, $fascia_oraria);
        $query->execute();
        $result = $query->get_result();

        if ($result->num_rows > 0) {
            echo "<p style='color:red;'>Questa sezione è già occupata!</p>";
        } else {
            // Se non è occupata, l'agenzia può occuparla
            $query = $conn->prepare("INSERT INTO prenotazioni (agenzia_id, data, fascia_oraria) VALUES (?, ?, ?)");
            $query->bind_param('iss', $agenzia_id, $data, $fascia_oraria);
            $query->execute();
            echo "<p style='color:green;'>Sezione occupata con successo!</p>";
        }
    } elseif (isset($_POST['rimuovi'])) {
        $data = $_POST['data'];
        $fascia_oraria = $_POST['fascia_oraria'];

        // Verifica se la prenotazione appartiene all'agenzia loggata
        $query = $conn->prepare("SELECT * FROM prenotazioni WHERE agenzia_id = ? AND data = ? AND fascia_oraria = ?");
        $query->bind_param('iss', $agenzia_id, $data, $fascia_oraria);
        $query->execute();
        $result = $query->get_result();

        if ($result->num_rows > 0) {
            // Rimuovi la prenotazione
            $query = $conn->prepare("DELETE FROM prenotazioni WHERE agenzia_id = ? AND data = ? AND fascia_oraria = ?");
            $query->bind_param('iss', $agenzia_id, $data, $fascia_oraria);
            $query->execute();
            echo "<p style='color:green;'>Prenotazione rimossa con successo!</p>";
        } else {
            echo "<p style='color:red;'>Non puoi rimuovere questa prenotazione perché non ti appartiene!</p>";
        }
    }
}

// Ottieni tutte le prenotazioni
$query = $conn->prepare("SELECT p.data, p.fascia_oraria, p.agenzia_id, a.colore 
                         FROM prenotazioni p
                         JOIN agenzie a ON p.agenzia_id = a.id
                         WHERE p.data BETWEEN ? AND ?");
$firstDayOfMonth = date('Y-m-01', strtotime("$year-$month-01")); // Primo giorno del mese corrente
$lastDayOfMonth = date('Y-m-t', strtotime($firstDayOfMonth)); // Ultimo giorno del mese corrente
$query->bind_param('ss', $firstDayOfMonth, $lastDayOfMonth);
$query->execute();
$prenotazioni = $query->get_result()->fetch_all(MYSQLI_ASSOC);

$today = date('Y-m-d');

// Impostazioni per il calendario
$firstDayOfMonth = date('Y-m-01', strtotime("$year-$month-01")); // Primo giorno del mese corrente
$startDayOfWeek = date('N', strtotime($firstDayOfMonth)); // Calcola da quale giorno della settimana inizia il mese corrente (Lunedì = 1)
$daysInMonth = date('t', strtotime($firstDayOfMonth)); // Numero di giorni nel mese corrente
$totalWeeks = ceil(($daysInMonth + ($startDayOfWeek - 1)) / 7); // Calcolo delle settimane del mese corrente
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Calendario Agenzie</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
    body {
        font-family: 'Roboto', sans-serif;
        background-color: #f4f4f4;
        margin: 0;
        padding: 0; /* Assicurati che il padding sia a 0 */
    }

    h2 {
        text-align: center;
        color: #333;
        margin: 0; /* Rimuovi margine per meno spazio */
    }

    h3 {
        text-align: center;
        color: #555;
        margin: 0; /* Rimuovi margine per meno spazio */
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin: 2px 0; /* Ulteriore riduzione per meno spazio */
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        background-color: #fff;
    }

    th, td {
        border: 2px solid #4CAF50;
        border-color:#8895a8;
        padding: 5px; /* Ulteriore riduzione per meno spazio */
        text-align: center;
        vertical-align: top; /* Allinea il contenuto delle celle in alto */
    }

    th {
        background-color: #4CAF50;
        color: white;
    }

    h4 {
        margin: 0;
        font-size: 18px;
        color: #333; /* Colore delle date */
    }
    th {
    background-color: <?php echo $colore_agenzia; ?>; /* Usa il colore dell'agenzia */
    color: white; /* Colore del testo */
}

    .griglia-2x2 {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        grid-gap: 5px; /* Spazio tra le sezioni */
    }

    .sezione {
        padding: 6px; /* Ulteriore riduzione per meno spazio */
        border-radius: 8px; /* Bordo arrotondato */
        background-color: #e7f3fe; /* Colore di sfondo */
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        font-size: 14px; /* Dimensione del testo */
        transition: background-color 0.3s; /* Transizione al passaggio del mouse */
    }

    .sezione:hover {
        background-color: #d0e4f7; /* Colore al passaggio del mouse */
    }

    .sezione.occupato {
        color: #fff;
        background-color: #ff4c4c; /* Colore per le sezioni occupate */
    }

    .sezione form {
        margin: 3px 0 0; /* Distanza tra il testo e il pulsante, ridotto */
    }

    .sezione button {
        font-size: 12px; /* Dimensione del pulsante */
        padding: 4px 8px; /* Ulteriore riduzione per meno spazio */
        border: none;
        border-radius: 5px; /* Bordo arrotondato per i pulsanti */
        background-color: #5C6165;
        color: white;
        cursor: pointer;
        transition: background-color 0.3s; /* Transizione al passaggio del mouse */
    }

    .sezione button:hover {
        background-color: #8B8C8D; /* Colore al passaggio del mouse sui pulsanti */
    }

    .nav-mese, .occupazione {
        text-align: center; 
        margin-bottom: 3px; /* Ulteriore riduzione per meno spazio */
    }

    .nav-mese form,
    .occupazione form {
        display: inline; /* Permette ai form di apparire sulla stessa riga */
        margin: 0 4px; /* Spazio tra i form, ridotto */
    }

    .occupazione {
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 3px 0; /* Ulteriore riduzione per meno spazio */
    }

    .occupazione input, .occupazione select {
        margin: 0 4px; /* Ulteriore riduzione per meno spazio */
        padding: 4px; /* Ulteriore riduzione per meno spazio */
        border-radius: 5px; /* Bordo arrotondato */
        border: 1px solid #ccc; /* Bordo della input */
    }
</style>



</head>
<body>
    <h2>Calendario Agenzia: <?php echo $nome_agenzia; ?></h2>
    
    <!-- Form per cambiare mese -->
    <div class="nav-mese">
        <form method="POST" action="calendar.php">
            <input type="hidden" name="mese" value="<?php echo $month == 1 ? 12 : $month - 1; ?>">
            <input type="hidden" name="anno" value="<?php echo $month == 1 ? $year - 1 : $year; ?>">
            <button type="submit">Mese Precedente</button>
        </form>
        <form method="POST" action="calendar.php">
            <input type="hidden" name="mese" value="<?php echo $month == 12 ? 1 : $month + 1; ?>">
            <input type="hidden" name="anno" value="<?php echo $month == 12 ? $year + 1 : $year; ?>">
            <button type="submit">Mese Successivo</button>
        </form>
    </div>
   <div class="occupazione">
        <h4>Occupa Sezione</h4>
        <form method="POST" action="calendar.php">
            <label for="data">Data:</label>
            <input type="date" id="data" name="data" required>
            <label for="fascia_oraria">Fascia Oraria:</label>
            <select id="fascia_oraria" name="fascia_oraria" required>
                <option value="mattina_linea1">Mattina Linea 1</option>
                <option value="mattina_linea2">Mattina Linea 2</option>
                <option value="pomeriggio_linea1">Pomeriggio Linea 1</option>
                <option value="pomeriggio_linea2">Pomeriggio Linea 2</option>
            </select>
            <button type="submit" name="occupa">Occupa Sezione</button>
        </form>
    </div>
    <h3><?php echo date('F Y', strtotime("$year-$month-01")); ?></h3>
    
    <table>
        <thead>
            <tr>
                <th>Lun</th>
                <th>Mar</th>
                <th>Mer</th>
                <th>Gio</th>
                <th>Ven</th>
                <th>Sab</th>
                <th>Dom</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $day = 1 - ($startDayOfWeek - 1); // Inizia dal primo giorno visibile nel calendario

            for ($week = 0; $week < $totalWeeks; $week++): ?>
                <tr>
                    <?php for ($weekday = 1; $weekday <= 7; $weekday++): ?>
                        <td>
                            <?php if ($day > 0 && $day <= $daysInMonth): 
                                $currentDate = "$year-$month-".str_pad($day, 2, "0", STR_PAD_LEFT); 
                            ?>
                                <h4><?php echo $day; ?></h4>
                                <div class="griglia-2x2">
                                    <?php foreach (['mattina_linea1', 'mattina_linea2', 'pomeriggio_linea1', 'pomeriggio_linea2'] as $fascia): 
                                        $prenotata = false;
                                        foreach ($prenotazioni as $prenotazione) {
                                            if ($prenotazione['data'] == $currentDate && $prenotazione['fascia_oraria'] == $fascia) {
                                                $colore = $prenotazione['colore'];
                                                $prenotata = true;
                                                if ($prenotazione['agenzia_id'] == $agenzia_id) {
                                                    echo "<div class='sezione occupato' style='background-color: $colore;'>
                                                            $fascia
                                                            <form method='POST' style='display:inline;'>
                                                                <input type='hidden' name='data' value='$currentDate'>
                                                                <input type='hidden' name='fascia_oraria' value='$fascia'>
                                                                <button type='submit' name='rimuovi'>Rimuovi</button>
                                                            </form>
                                                                <form method='GET' action='gestione_attività.php' style='display:inline;'>
                                                                <input type='hidden' name='data' value='$currentDate'>
                                                                <input type='hidden' name='fascia_oraria' value='$fascia'>
                                                                <button type='submit'>Gestisci attività</button>
                                                            </form>
                                                          </div>";
                                                } else {
                                                    echo "<div class='sezione occupato' style='background-color: $colore;'>$fascia (Occupato)</div>";
                                                }
                                                break;
                                            }
                                        }
                                        if (!$prenotata) {
                                            echo "<div class='sezione'>$fascia (Libero)</div>";
                                        }
                                    endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                    <?php 
                    $day++; 
                    endfor; ?>
                </tr>
            <?php endfor; ?>
        </tbody>
    </table>

    <!-- Form per occupare una sezione -->
    <!-- Sezione della legenda -->
<div class="legenda" style="text-align: center; margin-top: 20px;">
    <h4>Legenda:</h4>
    <div style="display: inline-block; margin: 0 10px;">
        <div style="width: 20px; height: 20px; background-color: #ff0000; display: inline-block; border: 0.4px solid #000;"></div>
        <span>Agenzia 1</span>
    </div>
    <div style="display: inline-block; margin: 0 10px;">
        <div style="width: 20px; height: 20px; background-color: #8cff00; display: inline-block; border: 0.4px solid #000;"></div>
        <span>Agenzia 2</span>
    </div>
    <div style="display: inline-block; margin: 0 10px;">
        <div style="width: 20px; height: 20px; background-color: #006aff; display: inline-block; border: 0.4px solid #000;"></div>
        <span>Agenzia 3</span>
    </div>
    
</div>

</body>
</html>