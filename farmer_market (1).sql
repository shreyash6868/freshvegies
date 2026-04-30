-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 30, 2026 at 07:54 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `farmer_market`
--

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `farmer_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `farmer_id`, `name`, `description`, `price`, `quantity`, `image`) VALUES
(1, 2, 'onion', 'Dry onion , Red onion', 35.00, 200, 'uploads/product_697f2be8717830.23204052.jpg'),
(2, 4, 'tomato', 'red , ready to use', 20.00, 50, 'uploads/product_697f3e340fd970.07126461.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('farmer','buyer','admin') DEFAULT 'buyer',
  `phone` varchar(20) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `tasil` varchar(100) NOT NULL,
  `district` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `password`, `role`, `phone`, `location`, `tasil`, `district`, `state`, `country`) VALUES
(1, 'shreyash kagale', '$2y$10$7WnL5itZYdK.NjfkgcngaezunsIZta7vseaAnvU13ZxNYDwLo6nA6', 'admin', '9021506657', 'kavatheguland', 'shirol', 'kolhapur', 'maharastra', 'india'),
(2, 'shivraj nimblakar', '$2y$10$8gLXTswNElc8fLIxtkSpmeXV/mgZ9GC5oUNyhWpTN9ObrM/ovcVVG', 'farmer', '8605861037', 'wadi', 'shirol', 'kolhapur', 'maharastra', 'india'),
(3, 'rohan khot', '$2y$10$asKDlHAE/L8f/eXIEPN6L.2LzNRva3EBj2FU4NvM7B7W9hWvjZ/Be', 'buyer', '9146067347', 'kolhapur', 'karvir', 'kolhapur', 'maharastra', 'india'),
(4, 'ram', '$2y$10$pQ0FEmGUmnW47IH4E4eNVOd96VcPXW1nLNdZ83EWL1MzpW.uBmbgu', 'farmer', '9158694577', 'kavatheguland', 'shirol', 'kolhapur', 'maharastra', 'india'),
(6, 'hrushab', '$2y$10$bdfiElH/Wynl6VXho8awWO2u.S0VY3DFxkJDX8OlMWyXH2tZYB552', 'buyer', '7057906060', 'shedshal', 'shirol', 'kolhapur', 'maharastra', 'india'),
(7, 'vaibhavraj kamble', '$2y$10$VCLREXF2Xf3baBUDU3FUZ.PtMi2RBO7RNUrYhwxQWfz3mWLnd3mvW', 'admin', '9322351030', 'bololi', 'karveer', 'kolhapur', 'maharastra', 'india');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `farmer_id` (`farmer_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
