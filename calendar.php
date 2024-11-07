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
        $q1 = "SELECT * FROM prenotazioni WHERE data = ? AND fascia_oraria = ?";
        if ($fascia_oraria == "mattina_linea1" || $fascia_oraria == "pomeriggio_linea1") {
            $q1 = "SELECT * FROM prenotazioni WHERE data = ? AND (fascia_oraria = ? OR fascia_oraria = 'ausiliario_linea1')";
        }
        if ($fascia_oraria == "mattina_linea2" || $fascia_oraria == "pomeriggio_linea2") {
            $q1 = "SELECT * FROM prenotazioni WHERE data = ? AND (fascia_oraria = ? OR fascia_oraria = 'ausiliario_linea2')";
        }
        
        $query = $conn->prepare($q1);
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

        if (strpos($fascia_oraria, 'ausiliario') !== false) {
            // Per le fasce ausiliarie, consenti la rimozione a tutte le agenzie
            $query = $conn->prepare("DELETE FROM prenotazioni WHERE data = ? AND fascia_oraria = ?");
            $query->bind_param('ss', $data, $fascia_oraria);
        } else {
            // Per le altre fasce, mantieni il controllo dell'agenzia
            $query = $conn->prepare("DELETE FROM prenotazioni WHERE agenzia_id = ? AND data = ? AND fascia_oraria = ?");
            $query->bind_param('iss', $agenzia_id, $data, $fascia_oraria);
        }
        
        $query->execute();
        echo "<p style='color:green;'>Prenotazione rimossa con successo!</p>";
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendario Agenzie</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
    body {
        font-family: 'Roboto', sans-serif;
        background: linear-gradient(135deg, #f5f7fa 0%, #e4e7e7 100%);
        margin: 0;
        min-height: 100vh;
        transform: scale(0.95); /* Riduce le dimensioni del body al 80% */
        transform-origin: top left; /* Imposta il punto di origine per lo scaling */
    }
    
    h2 {
        font-size: 2.2rem;
        text-align: center;
        color: #2c3e50;
        margin: 20px 0;
        text-transform: uppercase;
        letter-spacing: 1px;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
    }
    
    h3 {
        font-size: 1.8rem;
        text-align: center;
        color: #34495e;
        margin: 15px 0;
        font-weight: 500;
    }
    
    table {
        width: 80%;
        margin: 20px auto;
        border-collapse: separate;
        border-spacing: 0;
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    
    th, td {
        border: 1px solid #e0e0e0;
        padding: 12px;
        text-align: center;
        vertical-align: top;
        transition: all 0.3s ease;
    }
    
    /* Preserving the dynamic agency color */
    th {
        background-color: <?php echo $colore_agenzia; ?>;
        color: white;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-size: 0.9rem;
        padding: 15px;
    }
    
    td:hover {
        background-color: rgba(245,247,250,0.5);
        transform: scale(1.02);
        z-index: 1;
    }
    
    .griglia-2x2 {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        grid-gap: 8px;
        padding: 5px;
    }
    
    .sezione {
        padding: 10px;
        border-radius: 10px;
        background-color: #f8fafc;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        transition: all 0.3s ease;
        border: 1px solid #e2e8f0;
        min-height: 80px;
    }
    
    .sezione:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .sezione.occupato {
        color: white;
    }
    
    .sezione.ausiliario {
        background: #8A2BE2;
        color: white;
        grid-column: span 2;
        font-weight: 500;
    }
    
    .sezione button {
        margin-top: 8px;
        padding: 6px 12px;
        border: none;
        border-radius: 20px;
        background: rgba(255,255,255,0.2);
        color: white;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 0.8rem;
        backdrop-filter: blur(5px);
    }
    
    .sezione button:hover {
        background: rgba(255,255,255,0.3);
        transform: scale(1.05);
    }
    
    .nav-mese {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin: 20px 0;
    }
    
    .nav-mese button {
        padding: 10px 20px;
        border: none;
        border-radius: 25px;
        background-color: <?php echo $colore_agenzia; ?>;
        color: white;
        cursor: pointer;
        transition: all 0.3s ease;
        font-weight: 500;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
    
    .nav-mese button:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.3);
    }
    
    .occupazione {
        background: white;
        padding: 20px;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        margin: 20px auto;
        max-width: 800px;
    }
    
    .occupazione h4 {
        color: #2c3e50;
        margin-bottom: 15px;
        font-size: 1.2rem;
    }
    
    .occupazione input, .occupazione select {
        padding: 8px 15px;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        margin: 0 10px;
        font-size: 0.9rem;
        transition: all 0.3s ease;
    }
    
    .occupazione input:focus, .occupazione select:focus {
        border-color: <?php echo $colore_agenzia; ?>;
        outline: none;
        box-shadow: 0 0 0 2px rgba(0,0,0,0.1);
    }
    
    .occupazione button {
        padding: 8px 20px;
        background-color: <?php echo $colore_agenzia; ?>;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-weight: 500;
    }
    
    .occupazione button:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    
    .legenda {
        background: white;
        padding: 20px;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        margin: 20px auto;
        max-width: 800px;
    }
    
    .legenda-item {
        padding: 8px 15px;
        margin: 5px;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        background: #f8fafc;
        transition: all 0.3s ease;
    }
    
    .legenda-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    
    .legenda-color {
        width: 24px;
        height: 24px;
        border-radius: 12px;
        margin-right: 10px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .legenda-label {
        font-size: 0.95rem;
        color: #2c3e50;
        font-weight: 500;
    }
    
    #logout-button {
        position: fixed;
        top: 20px;
        right: 20px;
    }
    
    .logout-link {
        padding: 12px 25px;
        background: linear-gradient(45deg, #e74c3c, #c0392b);
        color: white;
        text-decoration: none;
        border-radius: 25px;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(231,76,60,0.3);
    }
    
    .logout-link:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(231,76,60,0.4);
    }
    
  @media (max-width: 768px) {
    /* ... (regole esistenti rimangono) ... */
    
    /* Centraggio form occupazione */
    .occupazione {
        width: 90%;
        margin: 20px auto;
        text-align: center;
    }
    
    .occupazione form {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
    }
    
    .occupazione label {
        margin: 5px 0;
    }
    
    .occupazione input,
    .occupazione select,
    .occupazione button {
        width: 80%;
        max-width: 300px;
        margin: 5px 0;
    }
    
    /* Centraggio legenda */
    .legenda {
        width: 90%;
        margin: 20px auto;
        text-align: center;
        padding: 15px 10px;
    }
    
    .legenda-item {
        display: inline-flex;
        justify-content: center;
        width: calc(50% - 10px);
        margin: 5px;
        box-sizing: border-box;
    }
}

/* Aggiornamento media query per schermi molto piccoli (max-width: 480px) */
@media (max-width: 480px) {
    .occupazione input,
    .occupazione select,
    .occupazione button {
        width: 90%;
    }
    
    .legenda-item {
        width: calc(100% - 10px);
        justify-content: center;
    }
    
    /* Migliora la navigazione del mese su schermi piccoli */
    .nav-mese {
        flex-direction: column;
        align-items: center;
        gap: 10px;
    }
    
    .nav-mese button {
        width: 200px;
    }
}
    </style>
</head>
<body>
    <h2>Calendario Agenzia: <?php echo $nome_agenzia; ?></h2>
    
    
   <div class="occupazione-container">
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
                    <option value="ausiliario_linea1">Ausiliario Linea 1</option>
                    <option value="ausiliario_linea2">Ausiliario Linea 2</option>
                </select>
                <input type="hidden" name="mese" value="<?php echo $month; ?>">
                <input type="hidden" name="anno" value="<?php echo $year; ?>">
                <button type="submit" name="occupa">Occupa Sezione</button>
            </form>
        </div>
        

    
        
        <h3><?php echo date('F Y', strtotime("$year-$month-01")); ?></h3>
        <!-- Form per cambiare mese -->
    <div class="nav-mese">
        <form method="POST" action="calendar.php">
            <input type="hidden" name="mese" value="<?php echo $month == 1 ? 12 : $month - 1; ?>">
            <input type="hidden" name="anno" value="<?php echo $month == 1 ? $year - 1 : $year; ?>">
            <button type="submit"> << Mese Precedente</button>
        </form>
        <form method="POST" action="calendar.php">
            <input type="hidden" name="mese" value="<?php echo $month == 12 ? 1 : $month + 1; ?>">
            <input type="hidden" name="anno" value="<?php echo $month == 12 ? $year + 1 : $year; ?>">
            <button type="submit">Mese Successivo >></button>
        </form>
    </div>
    <div style="width: 1920 px">
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
                                <div style="display: flex; flex-direction: row; gap: 5px">
                                    <?php
                                    $fasce = ['mattina_linea1','pomeriggio_linea1', 'mattina_linea2', 'pomeriggio_linea2'];
                                    $ausiliario1 = false;
                                    $ausiliario2 = false;
                                    foreach ($prenotazioni as $prenotazione) {
                                        if ($prenotazione['data'] == $currentDate) {
                                            if ($prenotazione['fascia_oraria'] == 'ausiliario_linea1') {
                                                $ausiliario1 = true;
                                            } elseif ($prenotazione['fascia_oraria'] == 'ausiliario_linea2') {
                                                $ausiliario2 = true;
                                            }
                                        }
                                    }
                                    
                                    if ($ausiliario1) {
                                        echo "<div class='sezione ausiliario' style='background-color: #8A2BE2'>
                                                Ausiliario Linea 1
                                                <form method='POST' style='display:inline;'>
                                                    <input type='hidden' name='data' value='$currentDate'>
                                                    <input type='hidden' name='fascia_oraria' value='ausiliario_linea1'>
                                                    <input type='hidden' name='mese' value='$month'>
                                                    <input type='hidden' name='anno' value='$year'>
                                                    <button type='submit' name='rimuovi'>Rimuovi</button>
                                                </form>
                                                <form method='GET' action='gestione_attività.php' style='display:inline;'>
                                                    <input type='hidden' name='data' value='$currentDate'>
                                                    <input type='hidden' name='fascia_oraria' value='ausiliario_linea1'>
                                                    <button type='submit'>Gestisci attività</button>
                                                </form>
                                              </div>";
                                    } else {
                                        echo "<div style='display: flex; flex-direction: column; gap: 5px'>";
                                        foreach (['mattina_linea1', 'pomeriggio_linea1'] as $fascia) {
                                            $prenotata = false;
                                            foreach ($prenotazioni as $prenotazione) {
                                                if ($prenotazione['data'] == $currentDate && $prenotazione['fascia_oraria'] == $fascia) {
                                                    $colore = $prenotazione['colore'];
                                                    $prenotata = true;
                                                    if ($prenotazione['agenzia_id'] == $agenzia_id || $prenotazione['agenzia_id'] == 4) {
                                                        echo "<div class='sezione occupato' style='background-color: $colore;'>
                                                                $fascia
                                                                <form method='POST' style='display:inline;'>
                                                                    <input type='hidden' name='data' value='$currentDate'>
                                                                    <input type='hidden' name='fascia_oraria' value='$fascia'>
                                                                    <input type='hidden' name='mese' value='$month'>
                                                                    <input type='hidden' name='anno' value='$year'>
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
                                        }
                                        echo "</div>";
                                    }
                                    
                                    if ($ausiliario2) {
                                        echo "<div class='sezione ausiliario' style='background-color: #8A2BE2;'>
                                                Ausiliario Linea 2
                                                <form method='POST' style='display:inline;'>
                                                    <input type='hidden' name='data' value='$currentDate'>
                                                    <input type='hidden' name='fascia_oraria' value='ausiliario_linea2'>
                                                    <input type='hidden' name='mese' value='$month'>
                                                    <input type='hidden' name='anno' value='$year'>
                                                    <button type='submit' name='rimuovi'>Rimuovi</button>
                                                </form>
                                                <form method='GET' action='gestione_attività.php' style='display:inline;'>
                                                    <input type='hidden' name='data' value='$currentDate'>
                                                    <input type='hidden' name='fascia_oraria' value='ausiliario_linea2'>
                                                    <button type='submit'>Gestisci attività</button>
                                                </form>
                                              </div>";
                                    } else {
                                        echo "<div style='display: flex; flex-direction: column; gap: 5px'>";

                                        foreach (['mattina_linea2', 'pomeriggio_linea2'] as $fascia) {
                                            $prenotata = false;
                                            foreach ($prenotazioni as $prenotazione) {
                                                if ($prenotazione['data'] == $currentDate && $prenotazione['fascia_oraria'] == $fascia) {
                                                    $colore = $prenotazione['colore'];
                                                    $prenotata = true;
                                                    if ($prenotazione['agenzia_id'] == $agenzia_id) {
                                                        echo "<div class='sezione occupato' style='background-color: $colore'>
                                                                $fascia
                                                                <form method='POST' style='display:inline;'>
                                                                    <input type='hidden' name='data' value='$currentDate'>
                                                                    <input type='hidden' name='fascia_oraria' value='$fascia'>
                                                                    <input type='hidden' name='mese' value='$month'>
                                                                    <input type='hidden' name='anno' value='$year'>
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
                                        }
                                        echo "</div>";
                                    }
                                    ?>
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
    </div>
    <!-- Sezione della legenda -->
        <div class="legenda">
        <h4>Legenda:</h4>
            <div class="legenda-item">
                <div class="legenda-color" style="background-color: #ff0000;"></div>
                <span class="legenda-label">CP Auto</span>
            </div>
            <div class="legenda-item">
                <div class="legenda-color" style="background-color: #8cff00;"></div>
                <span class="legenda-label">La bresciana</span>
            </div>
            <div class="legenda-item">
                <div class="legenda-color" style="background-color: #006aff;"></div>
                <span class="legenda-label">Praticauto</span>
            </div>
            <div class="legenda-item">
                <div class="legenda-color" style="background-color: #8A2BE2;"></div>
                <span class="legenda-label">Ausiliario</span>
            </div>
            <div class="legenda-item">
                <div class="legenda-color" style="background-color: #FF8000;"></div>
                <span class="legenda-label">Tvr</span>
            </div>
        </div>
        <div id="logout-button">
                 <a href="logout.php" class="logout-link">Logout</a>
        </div>
        </div>
    
</body>
</html>
    
</body>
</html>
