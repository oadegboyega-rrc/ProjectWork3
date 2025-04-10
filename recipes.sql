-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 27, 2025 at 05:33 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `serverside`
--

-- --------------------------------------------------------

--
-- Table structure for table `recipes`
--

CREATE TABLE `recipes` (
  `id` int(11) NOT NULL,
  `title` varchar(75) NOT NULL,
  `description` text NOT NULL,
  `content` varchar(255) NOT NULL,
  `image` varchar(75) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `category_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recipes`
--

INSERT INTO `recipes` (`id`, `title`, `description`, `content`, `image`, `user_id`, `created_at`, `category_id`) VALUES
(17, 'Spaghetti Carbonara', 'A classic Italian pasta dish made with eggs, cheese, pancetta, and pepper.', 'To prepare Spaghetti Carbonara, cook the pasta al dente, then mix with crispy pancetta, eggs, Parmesan, and black pepper.', 'spaghetti-carbonara.jpg', 0, '2025-03-27 03:39:49', 4),
(18, 'Vegan Tacos', 'A plant-based take on the classic taco, filled with seasoned veggies and topped with guacamole.', 'Fill soft tortillas with sautéed peppers, onions, and zucchini. Top with homemade guacamole and salsa.', 'uploads/67e4cc4c07cd6.jpg', 0, '2025-03-27 03:55:56', NULL),
(19, 'Chicken Parmesan', 'Breaded chicken cutlets topped with marinara sauce and melted mozzarella cheese.', 'Dip chicken in egg wash and breadcrumbs, then fry until golden. Top with marinara sauce and cheese, and bake until bubbly.', 'uploads/67e4cccdc115b.jpg', 0, '2025-03-27 03:58:05', NULL),
(20, 'Chocolate Chip Cookies', 'Soft and chewy cookies packed with rich chocolate chips.', 'Mix butter, sugar, flour, and eggs to form the dough, then fold in chocolate chips. Bake at 350°F for 12 minutes.', 'uploads/67e4ccf9b7d03.jpg', 0, '2025-03-27 03:58:49', NULL),
(21, 'Cauliflower Rice Stir-Fry', 'A low-carb alternative to fried rice, packed with veggies and flavor.', 'Sauté cauliflower rice with peas, carrots, onions, and soy sauce. Add scrambled eggs and serve hot.', 'uploads/67e4cd407e641.jpg', 0, '2025-03-27 04:00:00', NULL),
(22, 'Beef Stew', 'A hearty stew with tender chunks of beef, potatoes, carrots, and onions simmered in a savory broth.', 'Brown beef chunks, then simmer with vegetables and broth until tender. Add herbs for flavor.', 'uploads/67e4cd687edaf.jpeg', 0, '2025-03-27 04:00:40', NULL),
(23, 'Vegan Chocolate Cake', 'A rich and moist chocolate cake made without eggs or dairy.', 'Combine flour, cocoa powder, and sugar. Add oil, vinegar, and water. Bake at 350°F for 30 minutes.', 'uploads/67e4cd91a2a6e.jpeg', 0, '2025-03-27 04:01:21', NULL),
(24, 'Grilled Cheese Sandwich', 'A classic comfort food made with buttery toast and melted cheese.', 'Butter two slices of bread, place cheese in between, and grill on medium heat until golden and crispy.', 'uploads/67e4cdc060a77.jpg', 0, '2025-03-27 04:02:08', NULL),
(25, 'Shrimp Scampi', 'Garlic butter shrimp served over pasta for a savory and satisfying meal.', 'Sauté shrimp in butter, garlic, and lemon juice. Toss with cooked pasta and garnish with parsley.', 'uploads/67e4cdf46bb2e.jpg', 0, '2025-03-27 04:03:00', NULL),
(26, 'Banana Bread', 'A moist and flavorful bread made with ripe bananas, perfect for breakfast or a snack.', 'Mash ripe bananas, then mix with flour, sugar, eggs, and baking soda. Bake at 350°F for 60 minutes.', 'uploads/67e4ce1b47fe6.jpg', 0, '2025-03-27 04:03:39', 4);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `recipes`
--
ALTER TABLE `recipes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `recipes`
--
ALTER TABLE `recipes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `recipes`
--
ALTER TABLE `recipes`
  ADD CONSTRAINT `recipes_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
