-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Creato il: Mag 18, 2026 alle 09:21
-- Versione del server: 8.0.44
-- Versione PHP: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `Easy`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `biglietto`
--

CREATE TABLE `biglietto` (
  `id` int NOT NULL,
  `sigillo_fiscale` varchar(20) NOT NULL,
  `disponibilita` tinyint(1) NOT NULL DEFAULT '1',
  `id_utente` int NOT NULL,
  `id_evento_settore` int NOT NULL,
  `posto` int NOT NULL,
  `prezzo` decimal(10,2) NOT NULL,
  `data_acquisto` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `biglietto`
--

INSERT INTO `biglietto` (`id`, `sigillo_fiscale`, `disponibilita`, `id_utente`, `id_evento_settore`, `posto`, `prezzo`, `data_acquisto`) VALUES
(1, 'Yr2A4fgtOJIhzVU', 1, 2, 1, 78, 100.00, '2026-05-02 21:24:23'),
(2, 'kjwaFpzQr9uYiDR', 1, 2, 1, 79, 100.00, '2026-05-02 21:24:23'),
(3, '0WcY2PqGhwyrRmZ', 1, 2, 1, 80, 100.00, '2026-05-02 21:24:23'),
(4, 'WAewcPEXI9K5Bu6', 1, 2, 1, 90, 100.00, '2026-05-02 21:24:23'),
(5, 'iQ0EyD9k41ZpXJx', 1, 2, 1, 91, 100.00, '2026-05-02 21:24:23'),
(6, 'ranCOZuRqzwF64W', 1, 2, 1, 92, 100.00, '2026-05-02 21:24:23'),
(7, 'zYOpc7nyLbR6ZN1', 1, 2, 3, 78, 100.00, '2026-05-02 21:25:54'),
(8, 'wBTDsd8CxjrqLAQ', 1, 2, 3, 79, 100.00, '2026-05-02 21:25:54'),
(9, 'Y6SJVq2w8jtBWbn', 1, 2, 3, 80, 100.00, '2026-05-02 21:25:54'),
(10, 'vHZ2x8UgPG6D5LA', 1, 2, 3, 90, 100.00, '2026-05-02 21:25:54'),
(11, 'tkP8pZnhEdKYHWv', 1, 2, 3, 91, 100.00, '2026-05-02 21:25:54'),
(12, 'E5RuH7LtViTKaJG', 1, 2, 3, 92, 100.00, '2026-05-02 21:25:54');

-- --------------------------------------------------------

--
-- Struttura della tabella `categoria`
--

CREATE TABLE `categoria` (
  `id` int NOT NULL,
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
  `id` int NOT NULL,
  `titolo` varchar(120) NOT NULL,
  `descrizione` text,
  `id_categoria` int NOT NULL,
  `id_luogo` int NOT NULL,
  `immagine` varchar(255) DEFAULT NULL,
  `stato` enum('programmato','annullato','completato') NOT NULL DEFAULT 'programmato'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `evento`
--

INSERT INTO `evento` (`id`, `titolo`, `descrizione`, `id_categoria`, `id_luogo`, `immagine`, `stato`) VALUES
(1, 'Summer Music Festival - Arena di Verona', '', 3, 3, 'img/eventi/1777747117_1777734501_1776950251_summer-festival.jpg', 'programmato');

-- --------------------------------------------------------

--
-- Struttura della tabella `evento_settore`
--

CREATE TABLE `evento_settore` (
  `id` int NOT NULL,
  `id_replica_evento` int NOT NULL,
  `id_evento` int NOT NULL,
  `id_settore` int NOT NULL,
  `prezzo` decimal(10,2) NOT NULL,
  `posti_totali` int NOT NULL,
  `posti_disponibili` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `evento_settore`
--

INSERT INTO `evento_settore` (`id`, `id_replica_evento`, `id_evento`, `id_settore`, `prezzo`, `posti_totali`, `posti_disponibili`) VALUES
(1, 1, 1, 1, 100.00, 100, 94),
(2, 2, 1, 1, 100.00, 100, 100),
(3, 3, 1, 1, 100.00, 100, 94);

-- --------------------------------------------------------

--
-- Struttura della tabella `luogo`
--

CREATE TABLE `luogo` (
  `id` int NOT NULL,
  `nome` varchar(100) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `citta` varchar(100) NOT NULL,
  `indirizzo` varchar(150) DEFAULT NULL,
  `capienza` int NOT NULL
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
  `id` int NOT NULL,
  `id_evento` int NOT NULL,
  `data_ora_inizio` datetime NOT NULL,
  `data_ora_fine` datetime DEFAULT NULL,
  `stato` enum('programmata','annullata','completata') NOT NULL DEFAULT 'programmata'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `replica_evento`
--

INSERT INTO `replica_evento` (`id`, `id_evento`, `data_ora_inizio`, `data_ora_fine`, `stato`) VALUES
(1, 1, '2027-12-12 12:00:00', NULL, 'programmata'),
(2, 1, '2027-12-13 12:00:00', NULL, 'programmata'),
(3, 1, '2027-12-14 12:00:00', NULL, 'programmata');

-- --------------------------------------------------------

--
-- Struttura della tabella `ruolo`
--

CREATE TABLE `ruolo` (
  `id` int NOT NULL,
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
  `id` int NOT NULL,
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
  `id` int NOT NULL,
  `nome` varchar(50) NOT NULL,
  `cognome` varchar(50) NOT NULL,
  `data_nascita` date NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `id_ruolo` int NOT NULL,
  `saldo` decimal(10,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `utente`
--

INSERT INTO `utente` (`id`, `nome`, `cognome`, `data_nascita`, `username`, `password`, `id_ruolo`, `saldo`) VALUES
(1, 'admin', 'admin', '2002-09-01', 'admin', '$2y$10$X1ZLv7OFoLPY3P3KyWpOWOonhT2mHgPo75RqFBGkwHSBi23A2.Rxa', 1, 0.00),
(2, 'Riccardo', 'Pucci', '2002-09-01', 'Pucc199', '$2y$10$OjR7MiNgG5ShU6HZzUWNEOJHGHelxPpvt83tmRL6cYd7qCMS/qXy6', 2, 125.00);

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
  ADD KEY `idx_es_replica` (`id_replica_evento`),
  ADD KEY `idx_es_evento` (`id_evento`),
  ADD KEY `idx_es_settore` (`id_settore`);

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT per la tabella `categoria`
--
ALTER TABLE `categoria`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT per la tabella `evento`
--
ALTER TABLE `evento`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT per la tabella `evento_settore`
--
ALTER TABLE `evento_settore`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT per la tabella `luogo`
--
ALTER TABLE `luogo`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT per la tabella `replica_evento`
--
ALTER TABLE `replica_evento`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT per la tabella `ruolo`
--
ALTER TABLE `ruolo`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT per la tabella `settore`
--
ALTER TABLE `settore`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT per la tabella `utente`
--
ALTER TABLE `utente`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `biglietto`
--
ALTER TABLE `biglietto`
  ADD CONSTRAINT `biglietto_ibfk_1` FOREIGN KEY (`id_utente`) REFERENCES `utente` (`id`),
  ADD CONSTRAINT `biglietto_ibfk_2` FOREIGN KEY (`id_evento_settore`) REFERENCES `evento_settore` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `evento`
--
ALTER TABLE `evento`
  ADD CONSTRAINT `evento_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `categoria` (`id`),
  ADD CONSTRAINT `evento_ibfk_2` FOREIGN KEY (`id_luogo`) REFERENCES `luogo` (`id`);

--
-- Limiti per la tabella `evento_settore`
--
ALTER TABLE `evento_settore`
  ADD CONSTRAINT `evento_settore_ibfk_1` FOREIGN KEY (`id_replica_evento`) REFERENCES `replica_evento` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `evento_settore_ibfk_2` FOREIGN KEY (`id_evento`) REFERENCES `evento` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `evento_settore_ibfk_3` FOREIGN KEY (`id_settore`) REFERENCES `settore` (`id`);

--
-- Limiti per la tabella `replica_evento`
--
ALTER TABLE `replica_evento`
  ADD CONSTRAINT `replica_evento_ibfk_1` FOREIGN KEY (`id_evento`) REFERENCES `evento` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `utente`
--
ALTER TABLE `utente`
  ADD CONSTRAINT `utente_ibfk_1` FOREIGN KEY (`id_ruolo`) REFERENCES `ruolo` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

