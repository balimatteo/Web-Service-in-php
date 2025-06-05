<?php
// Non avviare la sessione qui, in quanto la richiesta è solo per la registrazione senza auto-login.
// session_start(); // Rimosso

// Impostazioni degli header per le risposte JSON e CORS
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
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
    exit(); // Termina l'esecuzione dopo l'errore
}

// Ottenere il metodo della richiesta HTTP
$request_method = $_SERVER["REQUEST_METHOD"];

// Gestire la richiesta in base al metodo HTTP
switch ($request_method) {
    case "POST":
        // Codice per gestire la creazione delle risorse (registrazione utente)
        $data = json_decode(file_get_contents("php://input"));

        // Controlla se i dati essenziali (username, password, email) sono presenti
        if (
            empty($data->username) ||
            empty($data->password) ||
            empty($data->email)
        ) {
            http_response_code(400); // Bad Request
            echo json_encode(array("message" => "Dati mancanti. Assicurati di fornire username, password ed email.", "status" => "error"));
            exit(); // Termina l'esecuzione dopo l'errore
        }

        // Sanitizza i dati in input per prevenire attacchi comuni
        $username = $conn->real_escape_string($data->username);
        $email = $conn->real_escape_string($data->email);
        $password = $data->password; // La password verrà hashata subito dopo

        // --- Nuovo controllo per username esistente ---
        $check_sql = "SELECT id FROM users WHERE username = ?";
        $check_stmt = $conn->prepare($check_sql);
        if ($check_stmt === false) {
            http_response_code(500); // Internal Server Error
            echo json_encode(array("message" => "Errore nella preparazione dello statement di verifica username: " . $conn->error, "status" => "error"));
            exit();
        }
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            http_response_code(400); // Bad Request
            echo json_encode(array("message" => "Username già esistente. Si prega di sceglierne un altro.", "status" => "error"));
            $check_stmt->close(); // Chiudi lo statement di verifica
            exit(); // Termina l'esecuzione
        }
        $check_stmt->close(); // Chiudi lo statement di verifica
        // --- Fine nuovo controllo ---

        // Hashing della password per sicurezza. Usa PASSWORD_DEFAULT per l'algoritmo raccomandato e aggiornabile.
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Prepara la query SQL per inserire il nuovo utente nel database
        $sql = "INSERT INTO users (username, password, email) VALUES (?, ?, ?)";

        // Prepara lo statement per prevenire SQL injection
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            http_response_code(500); // Internal Server Error
            // Se la preparazione dello statement fallisce, restituisci un errore
            echo json_encode(array("message" => "Errore nella preparazione dello statement di inserimento: " . $conn->error, "status" => "error"));
            exit(); // Termina l'esecuzione dopo l'errore
        }

        // Lega i parametri allo statement. 'sss' indica che tutti e tre i parametri sono stringhe.
        $stmt->bind_param("sss", $username, $hashed_password, $email);

        // Esegui lo statement
        if ($stmt->execute()) {
            http_response_code(200); // OK
            echo json_encode(array("message" => "Utente registrato con successo.", "status" => "success"));
        } else {
            // Qui gestiamo solo errori generici dell'inserimento,
            // poiché il controllo dell'username duplicato è stato fatto prima.
            http_response_code(500); // Internal Server Error
            echo json_encode(array("message" => "Errore durante la registrazione dell'utente: " . $stmt->error, "status" => "error"));
        }
        // Chiudi lo statement qui, dopo l'esecuzione
        $stmt->close();
        break;
    default:
        http_response_code(405); // Method Not Allowed
        // Risposta per metodi non supportati
        echo json_encode(["message" => "Method Not Allowed", "status" => "error"]);
        exit(); // Termina l'esecuzione dopo l'errore
        break;
}

// Chiudi la connessione al database
$conn->close();
?>
