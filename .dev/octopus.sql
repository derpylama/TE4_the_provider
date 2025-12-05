SET FOREIGN_KEY_CHECKS = 0;
-- Current
DROP TABLE IF EXISTS user, ban, blog, mail, adress, phone_number, event, event_invite, wiki, wiki_changes, img, organisation, rule;




--
-- Databas: `octopus`
--


-- --------------------------------------------------------

--
-- Tabellstruktur `user`
--

CREATE TABLE `user` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `customer_id` int(11) NOT NULL,
  `main_mail` varchar(100) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `main_adress` varchar(100) DEFAULT NULL,
  `employment_number` varchar(100) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `username` varchar(100) NOT NULL UNIQUE,
  `password` varchar(100) NOT NULL,
  `general` mediumtext DEFAULT NULL,
  `type` enum('admin','end_user','user') NOT NULL,
  `creation_date` datetime DEFAULT current_timestamp(),
  `latest_update` datetime DEFAULT current_timestamp()
);

-- --------------------------------------------------------- 

--
-- Tabellstruktur `mail`
--

CREATE TABLE `mail` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `user_id` int(11) NOT NULL,
  `mail` varchar(100) NOT NULL,
  `creation_date` datetime DEFAULT current_timestamp(),

  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
);


-- -------------------------------------------------------- 

--
-- Tabellstruktur `adress`
--

CREATE TABLE `adress` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `user_id` int(11) NOT NULL,
  `adress` varchar(100) NOT NULL,
  `creation_date` datetime DEFAULT current_timestamp(),

  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
);


-- --------------------------------------------------------

--
-- Tabellstruktur `phone_number`
--




CREATE TABLE `phone_number` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `user_id` int(11) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `creation_date` datetime DEFAULT current_timestamp(),

  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
);





CREATE TABLE `phone_connection` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `user_id` int(11) NOT NULL,
  `phone_id` int(11) NOT NULL,
  `is_main` tinyint(1) DEFAULT 0,
  `creation_date` datetime DEFAULT current_timestamp(),

  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
  FOREIGN KEY (phone_id) REFERENCES phone_number(id) ON DELETE CASCADE
);

CREATE TABLE `adress_connection` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `user_id` int(11) NOT NULL,
  `adress_id` int(11) NOT NULL,
  `is_main` tinyint(1) DEFAULT 0,
  `creation_date` datetime DEFAULT current_timestamp(),

  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
  FOREIGN KEY (adress_id) REFERENCES adress(id) ON DELETE CASCADE
);
CREATE TABLE `mail_connection` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `user_id` int(11) NOT NULL,
  `mail_id` int(11) NOT NULL,
  `is_main` tinyint(1) DEFAULT 0,
  `creation_date` datetime DEFAULT current_timestamp(),

  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
  FOREIGN KEY (mail_id) REFERENCES mail(id) ON DELETE CASCADE
);



-- --------------------------------------------------------

--
-- Tabellstruktur `ban`
--

CREATE TABLE `ban` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `user_id` int(11) NOT NULL,
  `creation_date` datetime DEFAULT current_timestamp(),
  `expiration_date` datetime NOT NULL,
  `blog` tinyint(1) NOT NULL DEFAULT 0,
  `wiki` tinyint(1) NOT NULL DEFAULT 0,
  `calendar` tinyint(1) NOT NULL DEFAULT 0,
  `reason` varchar(200) NOT NULL,

  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
);

-- --------------------------------------------------------

--
-- Tabellstruktur `blog`
-- 

CREATE TABLE `blog` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `content` mediumtext DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `user_id` int(11) NOT NULL,
  `general` mediumtext DEFAULT NULL,
  `creation_date` datetime DEFAULT current_timestamp(),
  `latest_update` datetime DEFAULT current_timestamp(),

  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
);

-- --------------------------------------------------------

--
-- Tabellstruktur `event`
--

CREATE TABLE `event` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `user_id` int(11) NOT NULL,
  `start_time` datetime DEFAULT current_timestamp(),
  `event_info` text DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `end_time` datetime NOT NULL,
  `creation_date` datetime DEFAULT current_timestamp(),
  `latest_update` datetime DEFAULT current_timestamp(),
  `general` mediumtext DEFAULT NULL,

  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
);

-- --------------------------------------------------------

--
-- Tabellstruktur `event_invite`
--

CREATE TABLE `event_invite` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `event_id` int(11) NOT NULL,
  `invited_user_id` int(11) NOT NULL,
  `accepted` tinyint(1) NOT NULL DEFAULT 0,
  `creation_date` datetime DEFAULT current_timestamp(),
  `comment` text DEFAULT NULL, 
  FOREIGN KEY (event_id) REFERENCES event(id) ON DELETE CASCADE,
  FOREIGN KEY (invited_user_id) REFERENCES user(id) ON DELETE CASCADE
);

-- --------------------------------------------------------

--
-- Tabellstruktur `wiki`
--

CREATE TABLE `wiki` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `user_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `creation_date` datetime DEFAULT current_timestamp(),
  `general` mediumtext DEFAULT NULL,
  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
);

-- --------------------------------------------------------

--
-- Tabellstruktur `wiki_changes`
--

CREATE TABLE `wiki_changes` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `wiki_id` int(11) NOT NULL,
  `time` datetime DEFAULT current_timestamp(),
  `content` mediumtext NOT NULL,
  `user_id` int(11) NOT NULL,

  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
  FOREIGN KEY (wiki_id) REFERENCES wiki(id) ON DELETE CASCADE
);

-- --------------------------------------------------------

--
-- Tabellstruktur `img`
--

CREATE TABLE `img` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `img_url` varchar(2000) NOT NULL,
  `customer_id` int(11) NOT NULL, -- why 
  `blog_id` int(11) DEFAULT NULL,
  `wiki_id` int(11) DEFAULT NULL,

  FOREIGN KEY (blog_id) REFERENCES blog(id) ON DELETE CASCADE,
  FOREIGN KEY (wiki_id) REFERENCES wiki(id) ON DELETE CASCADE
);

-- --------------------------------------------------------
