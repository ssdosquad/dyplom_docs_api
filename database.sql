-- phpMyAdmin SQL Dump
-- version 5.0.3
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1
-- Время создания: Июн 09 2021 г., 04:38
-- Версия сервера: 10.4.14-MariaDB
-- Версия PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `docs`
--

-- --------------------------------------------------------

--
-- Структура таблицы `account`
--

CREATE TABLE `account` (
  `id` int(11) NOT NULL,
  `typeAccount` enum('admin','employee') NOT NULL DEFAULT 'employee',
  `fullname` varchar(300) NOT NULL,
  `position` varchar(120) NOT NULL,
  `departament` varchar(120) NOT NULL,
  `login` varchar(45) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `account`
--

INSERT INTO `account` (`id`, `typeAccount`, `fullname`, `position`, `departament`, `login`, `password`) VALUES
(1, 'admin', 'Администратор', 'Администратор', 'Администрирование', 'user', '$2y$10$Dq5LK0DmUuMDZIcGVLX86O/CZP7vtcYDjhAgIbenjOhpA.JDUNKMK');

-- --------------------------------------------------------

--
-- Структура таблицы `account_session`
--

CREATE TABLE `account_session` (
  `id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `session_key` varchar(255) NOT NULL,
  `session_time` int(11) NOT NULL,
  `ip` varchar(12) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Структура таблицы `category`
--

CREATE TABLE `category` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `category`
--

INSERT INTO `category` (`id`, `name`) VALUES
(1, 'Представление интересов в суде'),
(2, 'Налоговые споры'),
(3, 'Составление исков, жалоб и документов правового порядка'),
(4, 'Регистрация ООО, ИП'),
(5, 'Исполнительное производство'),
(6, 'Трудовые споры'),
(7, 'Наследственные споры'),
(8, 'Семейное право'),
(9, 'Жилищные споры'),
(10, 'Согласовать перепланировку'),
(11, 'Оформить самострой'),
(12, 'Приватизация недвижимости'),
(13, 'Другое');

-- --------------------------------------------------------

--
-- Структура таблицы `deal`
--

CREATE TABLE `deal` (
  `id` int(11) NOT NULL,
  `account_id` int(11) DEFAULT NULL,
  `status` enum('active','unactive') DEFAULT 'active',
  `fullname` varchar(600) NOT NULL,
  `date_born` varchar(45) NOT NULL,
  `passport_series` int(11) NOT NULL,
  `passport_id` int(11) NOT NULL,
  `passport_issued` varchar(120) NOT NULL,
  `passport_date` varchar(45) NOT NULL,
  `passport_address` varchar(120) NOT NULL,
  `short_text` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Структура таблицы `deal_category`
--

CREATE TABLE `deal_category` (
  `deal_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Структура таблицы `deal_document`
--

CREATE TABLE `deal_document` (
  `id` int(11) NOT NULL,
  `deal_id` int(11) NOT NULL,
  `title` varchar(120) NOT NULL,
  `path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `account`
--
ALTER TABLE `account`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `account_session`
--
ALTER TABLE `account_session`
  ADD PRIMARY KEY (`id`),
  ADD KEY `account_id` (`account_id`);

--
-- Индексы таблицы `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `deal`
--
ALTER TABLE `deal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `account_id` (`account_id`);

--
-- Индексы таблицы `deal_category`
--
ALTER TABLE `deal_category`
  ADD KEY `category_id` (`category_id`),
  ADD KEY `deal_id` (`deal_id`);

--
-- Индексы таблицы `deal_document`
--
ALTER TABLE `deal_document`
  ADD PRIMARY KEY (`id`),
  ADD KEY `deal_id` (`deal_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `account`
--
ALTER TABLE `account`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `account_session`
--
ALTER TABLE `account_session`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `category`
--
ALTER TABLE `category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT для таблицы `deal`
--
ALTER TABLE `deal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `deal_document`
--
ALTER TABLE `deal_document`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `account_session`
--
ALTER TABLE `account_session`
  ADD CONSTRAINT `account_session_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `account` (`id`);

--
-- Ограничения внешнего ключа таблицы `deal`
--
ALTER TABLE `deal`
  ADD CONSTRAINT `deal_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `account` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `deal_category`
--
ALTER TABLE `deal_category`
  ADD CONSTRAINT `deal_category_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`),
  ADD CONSTRAINT `deal_category_ibfk_2` FOREIGN KEY (`deal_id`) REFERENCES `deal` (`id`);

--
-- Ограничения внешнего ключа таблицы `deal_document`
--
ALTER TABLE `deal_document`
  ADD CONSTRAINT `deal_document_ibfk_1` FOREIGN KEY (`deal_id`) REFERENCES `deal` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
