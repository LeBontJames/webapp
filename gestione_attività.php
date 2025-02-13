<?php
session_start();
include 'db.php';

if (!isset($_SESSION['agenzia_id'])) {
    header('Location: index.php');
    exit;
}

$agenzia_id = $_SESSION['agenzia_id'];
$data = $_GET['data'] ?? '';
$fascia_oraria = $_GET['fascia_oraria'] ?? '';

if (!$data || !$fascia_oraria) {
    die("Parametri mancanti");
}

// Definizione dei tempi massimi per ogni fascia oraria
$tempi_massimi = [
    'mattina_linea1' => 360,
    'mattina_linea2' => 360,
    'pomeriggio_linea1' => 300,
    'pomeriggio_linea2' => 300,
    'ausiliario_linea1' => 660,
    'ausiliario_linea2' => 660,

    
];

$tempo_massimo = $tempi_massimi[$fascia_oraria];
// Gestione dell'aggiunta del tempo extra
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['aggiungi_tempo'])) {
    $minuti_extra = intval($_POST['minuti']);
    
    // Aggiorna il tempo massimo nella sessione
    if (!isset($_SESSION['tempi_extra'][$data][$fascia_oraria])) {
        $_SESSION['tempi_extra'][$data][$fascia_oraria] = 0;
    }
    $_SESSION['tempi_extra'][$data][$fascia_oraria] += $minuti_extra;
    
    // Aggiorna anche nel database per persistenza
    $query = $conn->prepare("UPDATE prenotazioni SET tempo_extra = tempo_extra + ? WHERE data = ? AND fascia_oraria = ?");
    $query->bind_param('iss', $minuti_extra, $data, $fascia_oraria);
    $query->execute();
    
    if ($query->affected_rows > 0) {
        $message = "Tempo extra aggiunto con successo.";
    } else {
        $message = "Errore nell'aggiunta del tempo extra.";
    }
}

// Recupera il tempo extra dal database
$query = $conn->prepare("SELECT tempo_extra FROM prenotazioni WHERE data = ? AND fascia_oraria = ?");
$query->bind_param('ss', $data, $fascia_oraria);
$query->execute();
$result = $query->get_result();
$tempo_extra = $result->fetch_assoc()['tempo_extra'] ?? 0;

// Aggiorna il tempo massimo considerando il tempo extra
$tempo_massimo += $tempo_extra;

// Gestione del reset del tempo extra
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_tempo'])) {
    // Reset del tempo extra nella sessione
    if (isset($_SESSION['tempi_extra'][$data][$fascia_oraria])) {
        $_SESSION['tempi_extra'][$data][$fascia_oraria] = 0;
    }
    
    // Reset del tempo extra nel database
    $query = $conn->prepare("UPDATE prenotazioni SET tempo_extra = 0 WHERE data = ? AND fascia_oraria = ?");
    $query->bind_param('ss', $data, $fascia_oraria);
    $query->execute();
    
    if ($query->affected_rows > 0) {
        $message = "Tempo massimo ripristinato con successo.";
    } else {
        $message = "Errore nel ripristino del tempo massimo.";
    }
}
// Verifica se la prenotazione esiste e appartiene all'agenzia corrente o se è una fascia ausiliaria
$query = $conn->prepare("SELECT id, agenzia_id FROM prenotazioni WHERE data = ? AND fascia_oraria = ?");
$query->bind_param('ss', $data, $fascia_oraria);
$query->execute();
$result = $query->get_result();

if ($result->num_rows === 0) {
    die("Prenotazione non trovata");
}

$prenotazione = $result->fetch_assoc();
$prenotazione_id = $prenotazione['id'];

// Controlla se è una fascia ausiliaria o se appartiene all'agenzia corrente
if (strpos($fascia_oraria, 'ausiliario') === false && $prenotazione['agenzia_id'] != $agenzia_id && 
    $prenotazione['agenzia_id'] != 4 ) {
    die("Non autorizzato ad accedere a questa prenotazione");
}

// Funzione per calcolare il tempo totale delle attività
function calcolaTempotato($conn, $prenotazione_id) {
    $query = $conn->prepare("SELECT SUM(tempo) as tempo_totale FROM attivita WHERE prenotazione_id = ?");
    $query->bind_param('i', $prenotazione_id);
    $query->execute();
    $result = $query->get_result()->fetch_assoc();
    return $result['tempo_totale'] ?? 0;
}

// Gestione dell'aggiunta di un'attività
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['aggiungi_attivita'])) {
    $targa = $_POST['targa'];
    $cliente = $_POST['cliente'];
    $cod_fattura = $_POST['cod_fattura'];
    $tempo = $_POST['tempo'];
    $prenotato = $_POST['prenotato'];
    $inviato = $_POST['inviato'];
    
    $tempo_totale = calcolaTempotato($conn, $prenotazione_id) + $tempo;
    
    if ($tempo_totale <= $tempo_massimo) {
        $query = $conn->prepare("INSERT INTO attivita (prenotazione_id, targa, cliente, cod_fattura, tempo, prenotato, inviato) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $query->bind_param('isssiss', $prenotazione_id, $targa, $cliente, $cod_fattura, $tempo, $prenotato, $inviato);
        if ($query->execute()) {
            $message = "Attività aggiunta con successo.";
        } else {
            $message = "Errore nell'aggiunta dell'attività.";
        }
    } else {
        $message = "Errore: il tempo totale supererebbe il limite massimo di $tempo_massimo minuti.";
    }
}

// Gestione della rimozione di un'attività
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['rimuovi_attivita'])) {
    $attivita_id = $_POST['attivita_id'];
    $query = $conn->prepare("DELETE FROM attivita WHERE id = ? AND prenotazione_id = ?");
    $query->bind_param('ii', $attivita_id, $prenotazione_id);
    if ($query->execute()) {
        $message = "Attività rimossa con successo.";
    } else {
        $message = "Errore nella rimozione dell'attività.";
    }
}

// Recupero delle attività esistenti
$query = $conn->prepare("SELECT id, targa, cliente, cod_fattura, tempo, prenotato, inviato FROM attivita WHERE prenotazione_id = ?");
$query->bind_param('i', $prenotazione_id);
$query->execute();
$attivita = $query->get_result()->fetch_all(MYSQLI_ASSOC);

$tempo_totale = calcolaTempotato($conn, $prenotazione_id);
$tempo_rimanente = $tempo_massimo - $tempo_totale;

// Se la richiesta è AJAX, restituisci solo i dati JSON
if (isset($_GET['ajax'])) {
    echo json_encode([
        'attivita' => $attivita,
        'tempo_rimanente' => $tempo_rimanente,
        'message' => $message
    ]);
    exit;
}

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione Attività</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
            color: #212529;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .header h1 {
            color: #31456A;
            font-size: 24px;
            margin: 0;
        }

        .grid-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        .card {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #31456A;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
        }

        .btn {
            display: inline-block;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 500;
            text-align: center;
            text-decoration: none;
            border-radius: 4px;
            cursor: pointer;
            border: none;
            transition: background-color 0.2s;
        }

        .btn-primary {
            background-color: #dc2626;
            color: #fff;
        }

        .btn-primary:hover {
            background-color: #b91c1c;
        }

        .btn-danger {
            background-color: #dc3545;
            color: #fff;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        th {
            background-color: #31456A;
            color: #fff;
            font-weight: 500;
        }

        .tempo-info {
            font-size: 18px;
            color: #31456A;
            font-weight: 500;
            margin: 20px 0;
        }

        .nav-link {
            display: inline-block;
            color: #dc2626;
            text-decoration: none;
            margin-top: 20px;
            font-weight: 500;
        }

        .nav-link:hover {
            text-decoration: underline;
        }

        .message {
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            color: #fff;
        }

        .message.success {
            background-color: #28a745;
        }

        .message.error {
            background-color: #dc3545;
        }
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .btn-reset {
            background-color: #6c757d;
            color: #fff;
        }
        
        .btn-reset:hover {
            background-color: #5a6268;
        }
        .back-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #586670;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
            transition: background-color 0.2s;
            font-weight: 500;
        }
        
        .back-button:hover {
            background-color: #253448;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <h1>Gestione Attività per <?php echo htmlspecialchars($data); ?> - <?php echo htmlspecialchars($fascia_oraria); ?></h1>
    
    <div id="message-container"></div>
    
    <div style="display: flex; flex-direction: row; gap: 20px">
        <div id="tempo-rimanente" style="width: 100%;">
            Tempo rimanente: <?php echo $tempo_rimanente; ?> minuti
            <?php if ($tempo_extra > 0): ?>
                <br>
                <small>(Inclusi <?php echo $tempo_extra; ?> minuti extra)</small>
            <?php endif; ?>
        </div>
        <div style="width: 100%;">
            <h2>Gestione Tempo</h2>
            <form id="tempo-extra-form" method="post">
                <label for="minuti">Minuti extra:</label>
                <input type="number" id="minuti" name="minuti" min="1" required>
                <div class="button-group">
                    <input type="hidden" name="aggiungi_tempo" value="1">
                    <input type="submit" value="Aggiungi Tempo" class="btn btn-primary">
                    <button type="button" id="reset-tempo" class="btn btn-reset">Ripristina Tempo Originale</button>
                </div>
            </form>
        </div>
    </div>

    
    <h2>Attività Esistenti</h2>
    <div id="attivita-container">
        <!-- La tabella delle attività sarà inserita qui dinamicamente -->
    </div>

    <p><a href="calendar.php" class="back-button">Torna al Calendario</a></p>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function aggiornaAttivita() {
    $.ajax({
        url: 'gestione_attività.php?data=<?php echo urlencode($data); ?>&fascia_oraria=<?php echo urlencode($fascia_oraria); ?>&ajax=1',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            let tableHtml = '<table><thead><tr><th>Targa</th><th>Cliente</th><th>Codice Fattura</th><th>Tempo Mail (minuti)</th><th>Prenotato</th><th>Inviato</th><th>Azioni</th></tr></thead><tbody>';
            
            data.attivita.forEach(function(a) {
                tableHtml += `
                    <tr>
                        <td>${a.targa}</td>
                        <td>${a.cliente}</td>
                        <td>${a.cod_fattura}</td>
                        <td>${a.tempo}</td>
                        <td>${a.prenotato}</td>
                        <td>${a.inviato}</td>
                        <td>
                            <form class="rimuovi-form">
                                <input type="hidden" name="attivita_id" value="${a.id}">
                                <input type="submit" value="Rimuovi" class="remove-btn">
                            </form>
                        </td>
                    </tr>
                `;
            });
            
            tableHtml += '</tbody></table>';
            $('#attivita-container').html(tableHtml);
            $('#tempo-rimanente').text('Tempo rimanente: ' + data.tempo_rimanente + ' minuti');
            
            if (data.message) {
                $('#message-container').html('<div class="message">' + data.message + '</div>');
            }
        }
    });
}

        $(document).ready(function() {
            aggiornaAttivita();

            $('#aggiungi-form').on('submit', function(e) {
                e.preventDefault();
                $.ajax({
                    url: 'gestione_attività.php?data=<?php echo urlencode($data); ?>&fascia_oraria=<?php echo urlencode($fascia_oraria); ?>',
                    method: 'POST',
                    data: $(this).serialize() + '&aggiungi_attivita=1',
                    success: function() {
                        $('#aggiungi-form')[0].reset();
                        aggiornaAttivita();
                    }
                });
            });

            $(document).on('submit', '.rimuovi-form', function(e) {
                e.preventDefault();
                $.ajax({
                    url: 'gestione_attività.php?data=<?php echo urlencode($data); ?>&fascia_oraria=<?php echo urlencode($fascia_oraria); ?>',
                    method: 'POST',
                    data: $(this).serialize() + '&rimuovi_attivita=1',
                    success: function() {
                        aggiornaAttivita();
                    }
                });
            });
            
            $('#tempo-extra-form').on('submit', function(e) {
                e.preventDefault();
                $.ajax({
                    url: window.location.href,
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        // Aggiorna la pagina per mostrare il nuovo tempo massimo
                        location.reload();
                    }
                });
            });
            $('#reset-tempo').on('click', function() {
                if (confirm('Sei sicuro di voler ripristinare il tempo massimo originale?')) {
                    $.ajax({
                        url: window.location.href,
                        method: 'POST',
                        data: { reset_tempo: 1 },
                        success: function(response) {
                            location.reload();
                        }
                    });
                }
            });
        });
    </script>

    <form id="aggiungi-form">
    <h2>Aggiungi Attività</h2>
    <input type="text" name="targa" placeholder="Targa" required>
    <input type="text" name="cliente" placeholder="Cliente" required>
    <input type="text" name="cod_fattura" placeholder="Codice Fattura" required>
    <input type="number" name="tempo" placeholder="Tempo (in minuti)" required>
    <select name="prenotato" required>
        <option value="no">No</option>
        <option value="si">Si</option>
    </select>
    <select name="inviato" required>
        <option value="no">No</option>
        <option value="si">Si</option>
    </select>
    <input type="submit" value="Aggiungi">
</form>

    
</body>
</html>
