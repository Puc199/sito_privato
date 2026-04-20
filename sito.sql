-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Mag 30, 2024 alle 14:41
-- Versione del server: 10.4.32-MariaDB
-- Versione PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sito`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `biglietto`
--

CREATE TABLE `biglietto` (
  `id` int(11) NOT NULL,
  `Sigillo_Fiscale` varchar(15) NOT NULL,
  `Disponibilità` tinyint(1) NOT NULL,
  `ID_Utente` int(11) NOT NULL,
  `ID_Partita` int(11) NOT NULL,
  `settore` varchar(10) NOT NULL,
  `prezzo` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `biglietto`
--

INSERT INTO `biglietto` (`id`, `Sigillo_Fiscale`, `Disponibilità`, `ID_Utente`, `ID_Partita`, `settore`, `prezzo`) VALUES
(7, 'v2gord6mq3f01c7', 1, 15, 12, 'Curva', 70),
(10, 'q6xpa458t1yjue9', 1, 15, 13, 'VIP', 120);

-- --------------------------------------------------------

--
-- Struttura della tabella `curva`
--

CREATE TABLE `curva` (
  `ID_SC` int(11) NOT NULL,
  `prezzo` float NOT NULL,
  `N_posti` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `curva`
--

INSERT INTO `curva` (`ID_SC`, `prezzo`, `N_posti`) VALUES
(1, 70, 333);

-- --------------------------------------------------------

--
-- Struttura della tabella `partita`
--

CREATE TABLE `partita` (
  `id` int(11) NOT NULL,
  `Squadra_C` varchar(10) NOT NULL,
  `Squadra_T` varchar(10) NOT NULL,
  `Data_partita` date NOT NULL,
  `ID_SC` int(15) NOT NULL,
  `N_biglietti` int(15) NOT NULL,
  `Posti_curva` int(11) NOT NULL,
  `Posti_tribuna` int(11) NOT NULL,
  `Posti_vip` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `partita`
--

INSERT INTO `partita` (`id`, `Squadra_C`, `Squadra_T`, `Data_partita`, `ID_SC`, `N_biglietti`, `Posti_curva`, `Posti_tribuna`, `Posti_vip`) VALUES
(12, 'Inter', 'Roma', '1223-03-12', 1, 995, 332, 334, 332),
(13, 'Inter', 'Lazio', '1422-03-12', 1, 999, 333, 334, 332);

-- --------------------------------------------------------

--
-- Struttura della tabella `stadio_casa`
--

CREATE TABLE `stadio_casa` (
  `id` int(15) NOT NULL,
  `Cap` int(11) NOT NULL,
  `Nome` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `stadio_casa`
--

INSERT INTO `stadio_casa` (`id`, `Cap`, `Nome`) VALUES
(1, 22010, 'San Siro');

-- --------------------------------------------------------

--
-- Struttura della tabella `tribuna`
--

CREATE TABLE `tribuna` (
  `ID_SC` int(15) NOT NULL,
  `Prezzo` float NOT NULL,
  `N_posti` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `tribuna`
--

INSERT INTO `tribuna` (`ID_SC`, `Prezzo`, `N_posti`) VALUES
(1, 100, 334);

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
  `ruolo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `utente`
--

INSERT INTO `utente` (`id`, `nome`, `cognome`, `data_nascita`, `username`, `password`, `ruolo`) VALUES
(1, 'admin', 'admin', '1111-11-11', 'admin', '$2y$10$0OZrq541rkmmg2gnFskoeeU42684YmmBw5jsYp4w.vfKWaF5c7iXa', 1),
(15, 'Prova', 'Prova', '1231-03-12', 'Prova', '$2y$10$DfdCIMrHDrZnL4.mqF/ypugL5X5VAuixSzPGmbldPAZkFsyNv8r.a', 2);

-- --------------------------------------------------------

--
-- Struttura della tabella `vip`
--

CREATE TABLE `vip` (
  `ID_SC` int(15) NOT NULL,
  `prezzo` float NOT NULL,
  `N_posti` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `vip`
--

INSERT INTO `vip` (`ID_SC`, `prezzo`, `N_posti`) VALUES
(1, 120, 333);

-- --------------------------------------------------------

--
-- Struttura della tabella `wallet`
--

CREATE TABLE `wallet` (
  `ID_Utente` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `saldo` int(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `wallet`
--

INSERT INTO `wallet` (`ID_Utente`, `id`, `saldo`) VALUES
(15, 14, 1690);

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `biglietto`
--
ALTER TABLE `biglietto`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ID_Utente` (`ID_Utente`),
  ADD KEY `ID_Partita` (`ID_Partita`);

--
-- Indici per le tabelle `curva`
--
ALTER TABLE `curva`
  ADD KEY `ID_Posto` (`ID_SC`);

--
-- Indici per le tabelle `partita`
--
ALTER TABLE `partita`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ID_SC` (`ID_SC`);

--
-- Indici per le tabelle `stadio_casa`
--
ALTER TABLE `stadio_casa`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `tribuna`
--
ALTER TABLE `tribuna`
  ADD KEY `ID_Posto` (`ID_SC`);

--
-- Indici per le tabelle `utente`
--
ALTER TABLE `utente`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `vip`
--
ALTER TABLE `vip`
  ADD KEY `ID_Posto` (`ID_SC`);

--
-- Indici per le tabelle `wallet`
--
ALTER TABLE `wallet`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `biglietto`
--
ALTER TABLE `biglietto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT per la tabella `partita`
--
ALTER TABLE `partita`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT per la tabella `stadio_casa`
--
ALTER TABLE `stadio_casa`
  MODIFY `id` int(15) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT per la tabella `utente`
--
ALTER TABLE `utente`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT per la tabella `wallet`
--
ALTER TABLE `wallet`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `biglietto`
--
ALTER TABLE `biglietto`
  ADD CONSTRAINT `biglietto_ibfk_1` FOREIGN KEY (`ID_Utente`) REFERENCES `utente` (`id`),
  ADD CONSTRAINT `biglietto_ibfk_2` FOREIGN KEY (`ID_Partita`) REFERENCES `partita` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `curva`
--
ALTER TABLE `curva`
  ADD CONSTRAINT `curva_ibfk_1` FOREIGN KEY (`ID_SC`) REFERENCES `stadio_casa` (`id`);

--
-- Limiti per la tabella `partita`
--
ALTER TABLE `partita`
  ADD CONSTRAINT `partita_ibfk_1` FOREIGN KEY (`ID_SC`) REFERENCES `stadio_casa` (`id`);

--
-- Limiti per la tabella `tribuna`
--
ALTER TABLE `tribuna`
  ADD CONSTRAINT `tribuna_ibfk_1` FOREIGN KEY (`ID_SC`) REFERENCES `stadio_casa` (`id`);

--
-- Limiti per la tabella `vip`
--
ALTER TABLE `vip`
  ADD CONSTRAINT `vip_ibfk_1` FOREIGN KEY (`ID_SC`) REFERENCES `stadio_casa` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
