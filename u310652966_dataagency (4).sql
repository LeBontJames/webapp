-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Creato il: Ott 14, 2024 alle 14:48
-- Versione del server: 10.11.9-MariaDB
-- Versione PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u310652966_dataagency`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `agenzie`
--

CREATE TABLE `agenzie` (
  `id` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `colore` varchar(7) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `agenzie`
--

INSERT INTO `agenzie` (`id`, `nome`, `username`, `password`, `colore`) VALUES
(1, 'Agenzia1', 'agenzia1', '5f4dcc3b5aa765d61d8327deb882cf99', '#FF5733'),
(2, 'Agenzia2', 'agenzia2', '6cb75f652a9b52798eb6cf2201057c73', '#33FF57'),
(3, 'Agenzia3', 'agenzia3', '819b0643d6b89dc9b579fdfc9094f28e', '#3357FF');

-- --------------------------------------------------------

--
-- Struttura della tabella `attivita`
--

CREATE TABLE `attivita` (
  `id` int(11) NOT NULL,
  `prenotazione_id` int(11) NOT NULL,
  `targa` varchar(20) NOT NULL,
  `cliente` varchar(100) NOT NULL,
  `cod_fattura` varchar(50) NOT NULL,
  `tempo_mail` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `attivita`
--

INSERT INTO `attivita` (`id`, `prenotazione_id`, `targa`, `cliente`, `cod_fattura`, `tempo_mail`) VALUES
(6, 66, 'jgfgjf', 'jgfjfg', 'jfgjg', 35),
(7, 66, 'jff', 'jffj', 'jfjfj', 23);

-- --------------------------------------------------------

--
-- Struttura della tabella `prenotazioni`
--

CREATE TABLE `prenotazioni` (
  `id` int(11) NOT NULL,
  `agenzia_id` int(11) DEFAULT NULL,
  `data` date NOT NULL,
  `fascia_oraria` enum('mattina_linea1','mattina_linea2','pomeriggio_linea1','pomeriggio_linea2') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `prenotazioni`
--

INSERT INTO `prenotazioni` (`id`, `agenzia_id`, `data`, `fascia_oraria`) VALUES
(51, 3, '2024-10-17', 'pomeriggio_linea1'),
(52, 2, '2024-10-15', 'pomeriggio_linea2'),
(66, 1, '2024-10-10', 'pomeriggio_linea1'),
(67, 1, '2024-10-24', 'pomeriggio_linea2'),
(68, 1, '2024-10-20', 'pomeriggio_linea1'),
(69, 1, '2024-10-20', 'pomeriggio_linea1'),
(72, 1, '2024-10-22', 'mattina_linea1');

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `agenzie`
--
ALTER TABLE `agenzie`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `attivita`
--
ALTER TABLE `attivita`
  ADD PRIMARY KEY (`id`),
  ADD KEY `prenotazione_id` (`prenotazione_id`);

--
-- Indici per le tabelle `prenotazioni`
--
ALTER TABLE `prenotazioni`
  ADD PRIMARY KEY (`id`),
  ADD KEY `agenzia_id` (`agenzia_id`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `agenzie`
--
ALTER TABLE `agenzie`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT per la tabella `attivita`
--
ALTER TABLE `attivita`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT per la tabella `prenotazioni`
--
ALTER TABLE `prenotazioni`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `attivita`
--
ALTER TABLE `attivita`
  ADD CONSTRAINT `attivita_ibfk_1` FOREIGN KEY (`prenotazione_id`) REFERENCES `prenotazioni` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `prenotazioni`
--
ALTER TABLE `prenotazioni`
  ADD CONSTRAINT `prenotazioni_ibfk_1` FOREIGN KEY (`agenzia_id`) REFERENCES `agenzie` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
