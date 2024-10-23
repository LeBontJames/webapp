<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Agenzie</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            backdrop-filter: blur(10px);
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h2 {
            color: #2d3748;
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .login-header p {
            color: #718096;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #4a5568;
            margin-bottom: 8px;
            font-size: 0.95rem;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            color: #2d3748;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .login-button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(to right, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .error-message {
            background-color: #fff5f5;
            color: #c53030;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            display: none;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
            }

            .login-header h2 {
                font-size: 1.75rem;
            }
        }

        /* Animazione di caricamento per il pulsante */
        @keyframes buttonLoad {
            0% { transform: scale(1); }
            50% { transform: scale(0.95); }
            100% { transform: scale(1); }
        }

        .login-button:active {
            animation: buttonLoad 0.2s ease;
        }
    </style>
</head>
<body>
    <?php
    session_start();
    include 'db.php';
    
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
            echo "<div class='error-message' style='display: block;'>
                    Username o password non validi. Riprova.
                  </div>";
        }
    }
    ?>
    
    <div class="login-container">
        <div class="login-header">
            <h2>Benvenuto</h2>
            <p>Inserisci le tue credenziali per accedere</p>
        </div>
        
        <form method="POST" action="index.php">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required 
                       placeholder="Inserisci il tuo username"
                       autocomplete="username">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Inserisci la tua password"
                       autocomplete="current-password">
            </div>
            
            <button type="submit" class="login-button">
                Accedi
            </button>
        </form>
    </div>
</body>
</html>
