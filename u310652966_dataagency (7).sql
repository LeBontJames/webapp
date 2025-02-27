-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Creato il: Feb 27, 2025 alle 13:18
-- Versione del server: 10.11.10-MariaDB
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
(1, 'CP Auto', 'CPAUTO', '803b03363e4567c326461971ac45f73a', '#d31e0c'),
(2, 'La Bresciana', 'LA BRESCIANA', 'c98f036a3435b683f6a6d0283677e360', '#09ab18'),
(3, 'Praticauto', 'PRATICAUTO', '3cb21bd1eb4f7b4ca331cdb58f8f9bec', '#093dab'),
(4, 'TVR', 'TVR SERVICE', '469951f8322c08de5f64cafbd84c8045', '#FF8000');

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
  `tempo` int(11) DEFAULT NULL,
  `prenotato` enum('si','no') NOT NULL DEFAULT 'no',
  `inviato` enum('si','no') NOT NULL DEFAULT 'no'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `attivita`
--

INSERT INTO `attivita` (`id`, `prenotazione_id`, `targa`, `cliente`, `cod_fattura`, `tempo`, `prenotato`, `inviato`) VALUES
(26, 141, 'Ususs', 'dddd', 'Ff', 10, 'no', 'no'),
(34, 215, 'Djssb', 'Sjjsbs', '63733', 20, 'si', 'no'),
(36, 242, 'Xxx', 'Io', '099', 20, 'si', 'no');

-- --------------------------------------------------------

--
-- Struttura della tabella `prenotazioni`
--

CREATE TABLE `prenotazioni` (
  `id` int(11) NOT NULL,
  `agenzia_id` int(11) DEFAULT NULL,
  `data` date NOT NULL,
  `fascia_oraria` enum('mattina_linea1','mattina_linea2','pomeriggio_linea1','pomeriggio_linea2','ausiliario_linea1','ausiliario_linea2') NOT NULL,
  `tempo_extra` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `prenotazioni`
--

INSERT INTO `prenotazioni` (`id`, `agenzia_id`, `data`, `fascia_oraria`, `tempo_extra`) VALUES
(141, 3, '2024-10-29', 'pomeriggio_linea1', 0),
(143, 2, '2024-10-23', 'mattina_linea1', 0),
(147, 2, '2024-10-25', 'mattina_linea1', 0),
(150, 2, '2024-10-16', 'pomeriggio_linea1', 0),
(162, 1, '2024-10-12', 'mattina_linea1', 0),
(188, 4, '2024-10-09', 'mattina_linea1', 0),
(189, 4, '2024-10-04', 'mattina_linea1', 0),
(198, 2, '2024-11-05', 'ausiliario_linea2', 0),
(199, 2, '2024-11-05', 'mattina_linea1', 0),
(201, 1, '2024-11-15', 'mattina_linea1', 0),
(202, 1, '2024-11-22', 'ausiliario_linea1', 0),
(203, 1, '2024-11-15', 'ausiliario_linea1', 0),
(204, 1, '2024-11-08', 'mattina_linea1', 0),
(205, 1, '2024-11-06', 'mattina_linea1', 0),
(206, 1, '2024-11-14', 'mattina_linea1', 0),
(210, 1, '2024-12-06', 'mattina_linea1', 0),
(215, 3, '2025-02-06', 'mattina_linea1', 0),
(221, 3, '2025-03-19', 'mattina_linea1', 0),
(231, 3, '2025-03-01', 'pomeriggio_linea1', 0),
(232, 3, '2025-02-01', 'mattina_linea1', 0),
(233, 3, '2025-03-02', 'mattina_linea1', 0),
(234, 3, '2025-02-02', 'pomeriggio_linea2', 0),
(240, 1, '2025-02-26', 'ausiliario_linea1', 0),
(241, 1, '2025-02-26', 'ausiliario_linea1', 0),
(242, 1, '2025-03-05', 'mattina_linea1', 0),
(243, 1, '2025-03-05', 'pomeriggio_linea1', 0);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT per la tabella `attivita`
--
ALTER TABLE `attivita`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT per la tabella `prenotazioni`
--
ALTER TABLE `prenotazioni`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=244;

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
