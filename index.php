<?php
session_start();
include 'db.php'; // Connessione al database

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = md5($_POST['password']);
    
    $query = $conn->prepare("SELECT * FROM agenzie WHERE username = ? AND password = ?");
    $query->bind_param('ss', $username, $password);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        $agenzia = $result->fetch_assoc();
        $_SESSION['agenzia_id'] = $agenzia['id'];
        $_SESSION['nome_agenzia'] = $agenzia['nome'];
        $_SESSION['colore_agenzia'] = $agenzia['colore'];
        header('Location: calendar.php');
    } else {
        echo "Credenziali errate!";
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Login Agenzie</title>
</head>
<body>
    <h2>Login</h2>
    <form method="POST" action="index.php">
        <label for="username">Username:</label>
        <input type="text" name="username" required>
        <br>
        <label for="password">Password:</label>
        <input type="password" name="password" required>
        <br>
        <button type="submit">Accedi</button>
    </form>
</body>
</html>
