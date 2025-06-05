# Web Service in PHP

Questo progetto ha l'obiettivo di realizzare un sistema base di **gestione utenti** (registrazione, login e logout) tramite **Web Service PHP** che interagiscono con un database **MySQL**.

---

## 1. Architettura del Progetto

Il cuore del sistema è composto da tre file PHP, ognuno dei quali funge da endpoint per un'azione specifica:

- `register.php`: Per la creazione di nuovi account utente.
- `login.php`: Per l'autenticazione degli utenti esistenti e la gestione delle sessioni. Include anche un endpoint per visualizzare gli utenti, protetta da autenticazione.
- `logout.php`: Per terminare la sessione di un utente.

Tutti i servizi restituiscono risposte in **formato JSON** e utilizzano i **codici di stato HTTP standard** per indicare l’esito delle operazioni (successo, errori del client, errori del server, ecc.).

---

## 2. Configurazione Database

Per iniziare, è necessario creare un database MySQL chiamato `webservice_users` e una tabella `users` con la seguente struttura:

```sql
CREATE DATABASE IF NOT EXISTS webservice_users;
USE webservice_users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```
### 3. Dettaglio dei File PHP

#### `register.php` - Web Service di Registrazione Utente  
Questo script è responsabile della creazione di nuovi account nel database `webservice_users`.  

**Metodo HTTP**: Accetta solo richieste `POST`.  

**Input JSON**: Richiede un corpo JSON con i seguenti campi:  

```json
{
    "username": "nuovo_utente",
    "password": "password_sicura",
    "email": "email@esempio.com"
}

```
**Logica:**  

1. **Avvio della Sessione:**  
   La sessione non viene avviata automaticamente dopo la registrazione, poiché questo endpoint è dedicato unicamente alla creazione dell'utente.  

2. **Controllo Dati Mancanti (`400 Bad Request`):**  
   Verifica che i campi `username`, `password` ed `email` siano presenti nella richiesta JSON. In caso contrario, restituisce un errore `400`.  

3. **Sanificazione Dati:**  
   I dati ricevuti vengono sanificati per prevenire attacchi comuni come SQL Injection.  

4. **Controllo Username Esistente (`400 Bad Request`):**  
   Prima di tentare l'inserimento, esegue una query `SELECT` per verificare se l'username è già presente nel database. Se sì, restituisce un errore `400`.  

5. **Hashing Password:**  
   La password viene hashata usando `password_hash()` per garantire la sicurezza (non viene salvata in chiaro nel database).  

6. **Inserimento nel Database:**  
   Se tutte le verifiche passano, il nuovo utente viene inserito nella tabella `users`.

   **Output e Codici HTTP:**

- `200 OK`: Registrazione avvenuta con successo.
- `400 Bad Request`: Dati mancanti o username già esistente.
- `405 Method Not Allowed`: Metodo HTTP non `POST`.
- `500 Internal Server Error`: Problemi di connessione al database o errori nello statement SQL.

- ### `login.php` - Web Service di Login Utente  
Questo script gestisce l'autenticazione degli utenti e, in caso di successo, avvia una sessione. Permette anche di recuperare la lista degli utenti registrati, ma solo se si è loggati.  

**Metodi HTTP**:  
Accetta richieste `POST` per il login e richieste `GET` per la lista utenti.  

#### Logica `POST` (Login):  

1. **Avvio della Sessione**:  
   `session_start()` viene chiamato per gestire la sessione dell'utente.  

2. **Input JSON**:  
   Richiede un corpo JSON con:  
   ```json
   {
       "username": "utente_esistente",
       "password": "la_sua_password"
   }

**Logica di Autenticazione:**

3. **Controllo Dati Mancanti (`400 Bad Request`)**  
   Verifica che i campi obbligatori siano presenti nel payload JSON:
   - `username`
   - `password`  
   Se manca anche uno solo di questi campi, restituisce immediatamente un errore `400 Bad Request`.

4. **Recupero Utente dal Database**  
   Esegue una query parametrica per cercare l'utente corrispondente all'`username` fornito:
   ```sql
   SELECT id, username, password_hash FROM users WHERE username = ?

5. **Verifica Password (`401 Unauthorized`)**  
   Confronta la password fornita con l'hash salvato nel database usando la funzione `password_verify()`.  
   - Se la password **non corrisponde** o l'utente **non esiste**, la risposta sarà:
     - **Codice HTTP:** `401 Unauthorized`
     - **Messaggio:** Autenticazione fallita.

6. **Avvio Sessione**  
   Se l'autenticazione ha successo:
   - Vengono impostate le seguenti variabili di sessione:
     - `$_SESSION['user_id']`
     - `$_SESSION['username']`
   - Questo indica che l'utente è loggato e può accedere ad aree protette del servizio.

   **Output e Codici HTTP:**
   - `200 OK`: Login effettuato con successo.
   - `400 Bad Request`: Dati mancanti nel corpo della richiesta.
   - `401 Unauthorized`: Username inesistente o password errata.
   - `405 Method Not Allowed`: Metodo HTTP diverso da `POST`.
   - `500 Internal Server Error`: Problemi nel database o nella sessione.

#### Logica `GET` (Visualizzazione Utenti)

1. **Controllo Sessione Attiva (`401 Unauthorized`)**  
   Prima di mostrare i dati, viene verificato che l'utente sia autenticato (cioè che esista `$_SESSION['user_id']`).

2. **Recupero Utenti dal Database**  
   Se l'utente è autenticato, viene eseguita una query per ottenere la lista completa degli utenti (escludendo eventualmente la colonna password).

   **Output e Codici HTTP:**
   - `200 OK`: Elenco utenti restituito in formato JSON.
   - `401 Unauthorized`: Nessuna sessione attiva.
   - `405 Method Not Allowed`: Metodo HTTP diverso da `GET`.
   - `500 Internal Server Error`: Errore durante l'interrogazione al database.

---

### `logout.php` - Web Service di Logout

Questo script si occupa di terminare la sessione corrente.

**Metodo HTTP**: Accetta solo richieste `POST`.

**Logica:**

1. **Avvio Sessione:**  
   Chiama `session_start()` per accedere alla sessione esistente.

2. **Distruzione Sessione:**  
   Se una sessione è attiva:
   - Tutte le variabili di sessione vengono eliminate con `$_SESSION = array()`.
   - Il cookie di sessione viene rimosso (se impostato).
   - La sessione viene distrutta con `session_destroy()`.

**Output e Codici HTTP:**

- `200 OK`: Logout effettuato con successo.
- `400 Bad Request`: Nessuna sessione attiva da terminare.
- `405 Method Not Allowed`: Metodo HTTP non `POST`.
- `500 Internal Server Error`: Problemi nella distruzione della sessione.

---

## Conclusione

Questo setup fornisce una **base robusta per la gestione degli utenti**, seguendo i principi delle **API RESTful** con una **gestione delle sessioni basata su PHP**. Il sistema è facilmente estendibile e si presta a essere integrato in applicazioni più complesse, mantenendo una separazione chiara tra logica di business e interfaccia utente.
