SET FOREIGN_KEY_CHECKS = 0;
-- Current
DROP TABLE IF EXISTS ban, blog, event, event_invite, img, organisation, rule, user, wiki, wiki_changes;





--
-- Databas: `octopus`
--

-- --------------------------------------------------------

--
-- Tabellstruktur `ban`
--

CREATE TABLE `ban` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `user_id` int(11) NOT NULL,
  `start_time` datetime DEFAULT current_timestamp(),
  `end_time` datetime NOT NULL,
  `blog` tinyint(1) NOT NULL DEFAULT 0,
  `wiki` tinyint(1) NOT NULL DEFAULT 0,
  `calendar` tinyint(1) NOT NULL DEFAULT 0,
  `reason` varchar(200) NOT NULL
);

-- --------------------------------------------------------

--
-- Tabellstruktur `blog`
-- 

CREATE TABLE `blog` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `content` text DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `user_id` int(11) NOT NULL
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
  `title` int(11) NOT NULL,
  `end_time` datetime NOT NULL
);

-- --------------------------------------------------------

--
-- Tabellstruktur `event_invite`
--

CREATE TABLE `event_invite` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `event_id` int(11) NOT NULL,
  `invited_user_id` int(11) NOT NULL,
  `accepted` tinyint(1) NOT NULL DEFAULT 0
);

-- --------------------------------------------------------

--
-- Tabellstruktur `img`
--

CREATE TABLE `img` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `img_url` varchar(2000) NOT NULL,
  `blog_id` int(11) DEFAULT NULL,
  `wiki_id` int(11) DEFAULT NULL
);

-- --------------------------------------------------------

--
-- Tabellstruktur `organisation`
--

CREATE TABLE `organisation` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(100) NOT NULL,
  `mail` varchar(100) NOT NULL,
  `phone_number` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  `adress` varchar(100) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1
);

-- --------------------------------------------------------

--
-- Tabellstruktur `rule`
--

CREATE TABLE `rule` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `rule` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `blog` tinyint(1) NOT NULL DEFAULT 0,
  `wiki` tinyint(1) NOT NULL DEFAULT 0,
  `calendar` tinyint(1) NOT NULL DEFAULT 0,
  `creation_date` datetime DEFAULT current_timestamp(),
  `latest_update` datetime DEFAULT current_timestamp()
);

-- --------------------------------------------------------

--
-- Tabellstruktur `user`
--

CREATE TABLE `user` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `org_id` int(11) NOT NULL,
  `mail` varchar(100) DEFAULT NULL,
  `adress` varchar(100) DEFAULT NULL,
  `employment_number` int(11) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `type` enum('admin','end_user','user') NOT NULL
);

-- --------------------------------------------------------

--
-- Tabellstruktur `wiki`
--

CREATE TABLE `wiki` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `org_id` int(11) NOT NULL,
  `content` mediumtext DEFAULT NULL,
  `title` varchar(100) NOT NULL
);

-- --------------------------------------------------------

--
-- Tabellstruktur `wiki_changes`
--

CREATE TABLE `wiki_changes` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `time` datetime NOT NULL,
  `new_content` text NOT NULL,
  `user_id` int(11) NOT NULL,
  `old_content` text NOT NULL
);


