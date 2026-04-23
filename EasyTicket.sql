-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Creato il: Apr 22, 2026 alle 11:43
-- Versione del server: 10.4.28-MariaDB
-- Versione PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `EasyTicket`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `biglietto`
--

CREATE TABLE `biglietto` (
  `id` int(11) NOT NULL,
  `sigillo_fiscale` varchar(20) NOT NULL,
  `disponibilita` tinyint(1) NOT NULL DEFAULT 1,
  `id_utente` int(11) NOT NULL,
  `id_evento_settore` int(11) NOT NULL,
  `posto` int(11) NOT NULL,
  `prezzo` decimal(10,2) NOT NULL,
  `data_acquisto` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `categoria`
--

CREATE TABLE `categoria` (
  `id` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `descrizione` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `categoria`
--

INSERT INTO `categoria` (`id`, `nome`, `descrizione`) VALUES
(1, 'Concerto', 'Eventi musicali dal vivo'),
(2, 'Teatro', 'Spettacoli teatrali e musical'),
(3, 'Festival', 'Festival e rassegne dal vivo'),
(4, 'Sport', 'Eventi sportivi e partite'),
(5, 'Evento culturale', 'Mostre, spettacoli e incontri culturali');

-- --------------------------------------------------------

--
-- Struttura della tabella `evento`
--

CREATE TABLE `evento` (
  `id` int(11) NOT NULL,
  `titolo` varchar(120) NOT NULL,
  `descrizione` text DEFAULT NULL,
  `data_evento` datetime NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `id_luogo` int(11) NOT NULL,
  `immagine` varchar(255) DEFAULT NULL,
  `stato` enum('programmato','annullato','completato') NOT NULL DEFAULT 'programmato'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `evento_settore`
--

CREATE TABLE `evento_settore` (
  `id` int(11) NOT NULL,
  `id_replica_evento` int(11) NOT NULL,
  `id_evento` int(11) NOT NULL,
  `id_settore` int(11) NOT NULL,
  `prezzo` decimal(10,2) NOT NULL,
  `posti_totali` int(11) NOT NULL,
  `posti_disponibili` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `luogo`
--

CREATE TABLE `luogo` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `citta` varchar(100) NOT NULL,
  `indirizzo` varchar(150) DEFAULT NULL,
  `capienza` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `luogo`
--

INSERT INTO `luogo` (`id`, `nome`, `tipo`, `citta`, `indirizzo`, `capienza`) VALUES
(1, 'San Siro', 'Stadio', 'Milano', 'Piazzale Angelo Moratti', 75000),
(2, 'Teatro alla Scala', 'Teatro', 'Milano', 'Via Filodrammatici 2', 2030),
(3, 'Arena di Verona', 'Arena', 'Verona', 'Piazza Bra 1', 15000),
(4, 'Auditorium Parco della Musica', 'Auditorium', 'Roma', 'Via Pietro de Coubertin 30', 3000),
(5, 'Teatro Argentina', 'Teatro', 'Roma', 'Largo di Torre Argentina 52', 700);

-- --------------------------------------------------------

--
-- Struttura della tabella `replica_evento`
--

CREATE TABLE `replica_evento` (
  `id` int(11) NOT NULL,
  `id_evento` int(11) NOT NULL,
  `data_ora_inizio` datetime NOT NULL,
  `data_ora_fine` datetime DEFAULT NULL,
  `stato` enum('programmata','annullata','completata') NOT NULL DEFAULT 'programmata'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `ruolo`
--

CREATE TABLE `ruolo` (
  `id` int(11) NOT NULL,
  `nome` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `ruolo`
--

INSERT INTO `ruolo` (`id`, `nome`) VALUES
(1, 'admin'),
(2, 'cliente');

-- --------------------------------------------------------

--
-- Struttura della tabella `settore`
--

CREATE TABLE `settore` (
  `id` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `descrizione` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `settore`
--

INSERT INTO `settore` (`id`, `nome`, `descrizione`) VALUES
(1, 'VIP', 'Posti premium'),
(2, 'Tribuna', 'Posti centrali numerati'),
(3, 'Curva', 'Settore popolare'),
(4, 'Platea', 'Posti in platea'),
(5, 'Galleria', 'Posti in galleria');

-- --------------------------------------------------------

--
-- Struttura della tabella `utente`
--

CREATE TABLE `utente` (
  `id` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `cognome` varchar(50) NOT NULL,
  `data_nascita` date NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `id_ruolo` int(11) NOT NULL,
  `saldo` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `utente`
--

INSERT INTO `utente` (`id`, `nome`, `cognome`, `data_nascita`, `username`, `password`, `id_ruolo`, `saldo`) VALUES
(23, 'admin', 'admin', '2002-09-01', 'admin', '$2y$10$X1ZLv7OFoLPY3P3KyWpOWOonhT2mHgPo75RqFBGkwHSBi23A2.Rxa', 1, 0.00),
(24, 'Riccardo', 'Pucci', '2002-09-01', 'Pucc', '$2y$10$1CA.CefWfne7EoBPL9FvG.nZjjVDpBixlt.RzHHYSGMJ0eh7Crwum', 2, 110.00);

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `biglietto`
--
ALTER TABLE `biglietto`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_sigillo_fiscale` (`sigillo_fiscale`),
  ADD KEY `idx_biglietto_utente` (`id_utente`),
  ADD KEY `idx_biglietto_evento_settore` (`id_evento_settore`);

--
-- Indici per le tabelle `categoria`
--
ALTER TABLE `categoria`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_categoria_nome` (`nome`);

--
-- Indici per le tabelle `evento`
--
ALTER TABLE `evento`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_evento_categoria` (`id_categoria`),
  ADD KEY `idx_evento_luogo` (`id_luogo`);

--
-- Indici per le tabelle `evento_settore`
--
ALTER TABLE `evento_settore`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_evento_settore` (`id_evento`,`id_settore`),
  ADD KEY `idx_evento_settore_evento` (`id_evento`),
  ADD KEY `idx_evento_settore_settore` (`id_settore`),
  ADD KEY `idx_evento_settore_replica` (`id_replica_evento`);

--
-- Indici per le tabelle `luogo`
--
ALTER TABLE `luogo`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `replica_evento`
--
ALTER TABLE `replica_evento`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_replica_evento_evento` (`id_evento`);

--
-- Indici per le tabelle `ruolo`
--
ALTER TABLE `ruolo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_ruolo_nome` (`nome`);

--
-- Indici per le tabelle `settore`
--
ALTER TABLE `settore`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_settore_nome` (`nome`);

--
-- Indici per le tabelle `utente`
--
ALTER TABLE `utente`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_utente_username` (`username`),
  ADD KEY `idx_utente_ruolo` (`id_ruolo`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `biglietto`
--
ALTER TABLE `biglietto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT per la tabella `categoria`
--
ALTER TABLE `categoria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT per la tabella `evento`
--
ALTER TABLE `evento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT per la tabella `evento_settore`
--
ALTER TABLE `evento_settore`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT per la tabella `luogo`
--
ALTER TABLE `luogo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT per la tabella `replica_evento`
--
ALTER TABLE `replica_evento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT per la tabella `ruolo`
--
ALTER TABLE `ruolo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT per la tabella `settore`
--
ALTER TABLE `settore`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT per la tabella `utente`
--
ALTER TABLE `utente`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `biglietto`
--
ALTER TABLE `biglietto`
  ADD CONSTRAINT `fk_biglietto_evento_settore` FOREIGN KEY (`id_evento_settore`) REFERENCES `evento_settore` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_biglietto_utente` FOREIGN KEY (`id_utente`) REFERENCES `utente` (`id`);

--
-- Limiti per la tabella `evento`
--
ALTER TABLE `evento`
  ADD CONSTRAINT `fk_evento_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categoria` (`id`),
  ADD CONSTRAINT `fk_evento_luogo` FOREIGN KEY (`id_luogo`) REFERENCES `luogo` (`id`);

--
-- Limiti per la tabella `evento_settore`
--
ALTER TABLE `evento_settore`
  ADD CONSTRAINT `fk_evento_settore_evento` FOREIGN KEY (`id_evento`) REFERENCES `evento` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_evento_settore_replica` FOREIGN KEY (`id_replica_evento`) REFERENCES `replica_evento` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_evento_settore_settore` FOREIGN KEY (`id_settore`) REFERENCES `settore` (`id`);

--
-- Limiti per la tabella `replica_evento`
--
ALTER TABLE `replica_evento`
  ADD CONSTRAINT `fk_replica_evento_evento` FOREIGN KEY (`id_evento`) REFERENCES `evento` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `utente`
--
ALTER TABLE `utente`
  ADD CONSTRAINT `fk_utente_ruolo` FOREIGN KEY (`id_ruolo`) REFERENCES `ruolo` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
