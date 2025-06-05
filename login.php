<?php
// Avvia la sessione PHP
session_start();

// Impostazioni degli header per le risposte JSON e CORS
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET"); // Aggiunto GET ai metodi consentiti
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Configurazione del database
$servername = "localhost"; // Potrebbe essere diverso, ad esempio '127.0.0.1' o il nome del tuo host
$username_db = "root";     // Il tuo nome utente del database
$password_db = "";         // La tua password del database
$dbname = "webservice_users"; // Il nome del database creato

// Connessione al database
$conn = new mysqli($servername, $username_db, $password_db, $dbname);

// Controlla la connessione al database
if ($conn->connect_error) {
    http_response_code(500); // Internal Server Error
    echo json_encode(array("message" => "Errore di connessione al database: " . $conn->connect_error, "status" => "error"));
    exit();
}

// Ottenere il metodo della richiesta HTTP
$request_method = $_SERVER["REQUEST_METHOD"];

// Gestire la richiesta in base al metodo HTTP
switch ($request_method) {
    case "POST":
        // Codice per gestire il login utente
        $data = json_decode(file_get_contents("php://input"));

        // Controlla se i dati essenziali (username, password) sono presenti
        if (
            empty($data->username) ||
            empty($data->password)
        ) {
            http_response_code(400); // Bad Request
            echo json_encode(array("message" => "Dati mancanti. Assicurati di fornire username e password.", "status" => "error"));
            exit();
        }

        // Sanitizza lo username in input
        $username = $conn->real_escape_string($data->username);
        $password = $data->password; // La password verrà verificata subito dopo

        // Prepara la query SQL per recuperare l'utente dal database basandosi sullo username
        $sql = "SELECT id, username, password FROM users WHERE username = ?";

        // Prepara lo statement per prevenire SQL injection
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            http_response_code(500); // Internal Server Error
            // Se la preparazione dello statement fallisce, restituisci un errore
            echo json_encode(array("message" => "Errore nella preparazione dello statement: " . $conn->error, "status" => "error"));
            exit();
        }

        // Lega il parametro allo statement. 's' indica che il parametro è una stringa.
        $stmt->bind_param("s", $username);

        // Esegui lo statement
        $stmt->execute();

        // Ottieni il risultato della query
        $result = $stmt->get_result();

        // Controlla se è stato trovato un utente con lo username specificato
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $hashed_password_from_db = $user['password'];

            // Verifica la password fornita con la password hashata salvata nel database
            if (password_verify($password, $hashed_password_from_db)) {
                // Le credenziali sono valide, imposta le variabili di sessione
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];

                http_response_code(200); // OK
                echo json_encode(array("message" => "Accesso effettuato con successo. Sessione avviata.", "status" => "success", "user_id" => $user['id'], "username" => $user['username']));
            } else {
                // Password non valida
                http_response_code(401); // Unauthorized
                echo json_encode(array("message" => "Nome utente o password non validi.", "status" => "error"));
            }
        } else {
            // Nessun utente trovato con questo username
            http_response_code(401); // Unauthorized
            echo json_encode(array("message" => "Nome utente o password non validi.", "status" => "error"));
        }
        // Chiudi lo statement preparato
        if (isset($stmt) && $stmt instanceof mysqli_stmt) {
            $stmt->close();
        }
        break;

    case "GET":
        // Codice per gestire la lettura di tutti gli utenti
        // Verifica se l'utente è loggato
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
            http_response_code(401); // Unauthorized
            echo json_encode(array("message" => "Accesso negato. Devi essere loggato per visualizzare la lista utenti.", "status" => "error"));
            exit(); // Termina l'esecuzione
        }

        $sql = "SELECT id, username, email, created_at FROM users"; // Escludiamo la password!
        $result = $conn->query($sql);

        $users = array();
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
            http_response_code(200); // OK
            echo json_encode(array("message" => "Elenco utenti recuperato con successo.", "status" => "success", "users" => $users));
        } else {
            http_response_code(200); // OK (nessun utente trovato è comunque una risposta valida)
            echo json_encode(array("message" => "Nessun utente trovato nel database.", "status" => "success", "users" => []));
        }
        break;

    default:
        http_response_code(405); // Method Not Allowed
        // Risposta per metodi non supportati
        echo json_encode(["message" => "Method Not Allowed", "status" => "error"]);
        break;
}

// Chiudi la connessione al database
$conn->close();
?>
