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
    $query = $conn->prepare("SELECT SUM(tempo_mail) as tempo_totale FROM attivita WHERE prenotazione_id = ?");
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
    $tempo_mail = $_POST['tempo_mail'];
    
    $tempo_totale = calcolaTempotato($conn, $prenotazione_id) + $tempo_mail;
    
    if ($tempo_totale <= $tempo_massimo) {
        $query = $conn->prepare("INSERT INTO attivita (prenotazione_id, targa, cliente, cod_fattura, tempo_mail) VALUES (?, ?, ?, ?, ?)");
        $query->bind_param('isssi', $prenotazione_id, $targa, $cliente, $cod_fattura, $tempo_mail);
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
$query = $conn->prepare("SELECT id, targa, cliente, cod_fattura, tempo_mail FROM attivita WHERE prenotazione_id = ?");
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
            font-family: 'Verdana', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #e2e2e2;
        }
        h1, h2 {
            color: #2e2e2e;
        }
        form {
            background: #fff;
            padding: 25px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        input[type="text"], input[type="number"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        input[type="submit"] {
            background: #007BFF;
            color: #fff;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        input[type="submit"]:hover {
            background: #0056b3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f1f1f1;
        }
        .remove-btn {
            background: #dc3545;
            color: #fff;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .remove-btn:hover {
            background: #c82333;
        }
        .message {
            background: #28a745;
            color: white;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        #tempo-rimanente {
            font-size: 20px;
            font-weight: bold;
            margin: 20px 0;
            color: #007BFF;
        }
        a {
            color: #007BFF;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        a:hover {
            color: #0056b3;
        }
    </style>
</head>
<body>
    <h1>Gestione Attività per <?php echo htmlspecialchars($data); ?> - <?php echo htmlspecialchars($fascia_oraria); ?></h1>
    
    <div id="message-container"></div>
    
    <div style="display: flex; flex-direction: row; gap: 20px">
        <div id="tempo-rimanente"  style="width: 100%;">Tempo rimanente: <?php echo $tempo_rimanente; ?> minuti</div>
        <div style="width: 100%;">
            <h1>Inserisci un Tempo in Minuti</h1>
            <form action="/submit" method="post">
                <label for="minuti">Minuti:</label>
                <input type="number" id="minuti" name="minuti" min="0" required>
                <input type="submit" value="Invia">
            </form>
        </div>
    </div>
    
    <h2>Attività Esistenti</h2>
    <div id="attivita-container">
        <!-- La tabella delle attività sarà inserita qui dinamicamente -->
    </div>

    <p><a href="calendar.php">Torna al Calendario</a></p>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function aggiornaAttivita() {
            $.ajax({
                url: 'gestione_attività.php?data=<?php echo urlencode($data); ?>&fascia_oraria=<?php echo urlencode($fascia_oraria); ?>&ajax=1',
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    let tableHtml = '<table><thead><tr><th>Targa</th><th>Cliente</th><th>Codice Fattura</th><th>Tempo Mail (minuti)</th><th>Azioni</th></tr></thead><tbody>';
                    
                    data.attivita.forEach(function(a) {
                        tableHtml += `
                            <tr>
                                <td>${a.targa}</td>
                                <td>${a.cliente}</td>
                                <td>${a.cod_fattura}</td>
                                <td>${a.tempo_mail}</td>
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
        });
    </script>

    <form id="aggiungi-form">
        <h2>Aggiungi Attività</h2>
        <input type="text" name="targa" placeholder="Targa" required>
        <input type="text" name="cliente" placeholder="Cliente" required>
        <input type="text" name="cod_fattura" placeholder="Codice Fattura" required>
        <input type="number" name="tempo_mail" placeholder="Tempo Mail (in minuti)" required>
        <input type="submit" value="Aggiungi">
    </form>

    
</body>
</html>
