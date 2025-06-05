<?php
// Avvia la sessione PHP (o la riprende se già esistente)
session_start();

// Impostare il tipo di contenuto della risposta come JSON
header("Content-Type: application/json; charset=UTF-8");
// Impostazioni degli header per CORS (Cross-Origin Resource Sharing)
header("Access-Control-Allow-Methods: POST"); // Il logout tipicamente è una POST
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Ottenere il metodo della richiesta HTTP
$request_method = $_SERVER["REQUEST_METHOD"];

// Gestire la richiesta in base al metodo HTTP
switch ($request_method) {
    case "POST":
        // Controlla se una sessione è attiva e se contiene dati di utente
        if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
            // Distrugge tutte le variabili di sessione
            $_SESSION = array();

            // Se si desidera distruggere completamente la sessione, anche il cookie di sessione
            // Nota: questo distruggerà la sessione corrente per l'utente
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }

            // Infine, distrugge la sessione
            session_destroy();

            http_response_code(200); // OK
            echo json_encode(array("message" => "Logout effettuato con successo. Sessione terminata.", "status" => "success"));
        } else {
            // Se non c'è una sessione attiva o dati utente nella sessione
            http_response_code(400); // Bad Request
            echo json_encode(array("message" => "Nessuna sessione attiva da terminare.", "status" => "error"));
        }
        break;
    default:
        http_response_code(405); // Method Not Allowed
        // Risposta per metodi non supportati
        echo json_encode(["message" => "Method Not Allowed", "status" => "error"]);
        break;
}
?>
