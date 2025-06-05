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
