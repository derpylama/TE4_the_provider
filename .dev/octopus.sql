SET FOREIGN_KEY_CHECKS = 0;
-- Current
DROP TABLE IF EXISTS ban, blog, event, event_invite, img, organisation, rule, user, wiki, wiki_changes;





--
-- Databas: `octopus`
--



-- --------------------------------------------------------

--   FOREIGN KEY () REFERENCES () ON DELETE CASCADE
-- Tabellstruktur `ban`
--

--
-- Tabellstruktur `user`
--

CREATE TABLE `user` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `organisation_id` int(11) NOT NULL,
  `mail` varchar(100) DEFAULT NULL,
  `adress` varchar(100) DEFAULT NULL,
  `employment_number` int(11) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `type` enum('admin','end_user','user') NOT NULL,
  FOREIGN KEY (organisation_id) REFERENCES organisation(id) ON DELETE CASCADE
);

-- --------------------------------------------------------


CREATE TABLE `ban` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `user_id` int(11) NOT NULL,
  `start_time` datetime DEFAULT current_timestamp(),
  `end_time` datetime NOT NULL,
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
  `content` text DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `user_id` int(11) NOT NULL,

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
  `title` int(11) NOT NULL,
  `end_time` datetime NOT NULL,

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
  `invitation_date` datetime DEFAULT current_timestamp(),
  FOREIGN KEY (event_id) REFERENCES event(id) ON DELETE CASCADE,
  FOREIGN KEY (invited_user_id) REFERENCES user(id) ON DELETE CASCADE
);

-- --------------------------------------------------------

--
-- Tabellstruktur `img`
--

CREATE TABLE `img` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `img_url` varchar(2000) NOT NULL,
  `blog_id` int(11) DEFAULT NULL,
  `wiki_id` int(11) DEFAULT NULL,

  FOREIGN KEY (blog_id) REFERENCES blog(id) ON DELETE CASCADE,
  FOREIGN KEY (wiki_id) REFERENCES wiki(id) ON DELETE CASCADE
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
  `organisation_id` int(11) NOT NULL,
  `rule` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `blog` tinyint(1) NOT NULL DEFAULT 0,
  `wiki` tinyint(1) NOT NULL DEFAULT 0,
  `calendar` tinyint(1) NOT NULL DEFAULT 0,
  `creation_date` datetime DEFAULT current_timestamp(),
  `latest_update` datetime DEFAULT current_timestamp(),

  FOREIGN KEY (organisation_id) REFERENCES organisation(id) ON DELETE CASCADE
);

-- --------------------------------------------------------



--
-- Tabellstruktur `wiki`
--

CREATE TABLE `wiki` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `organisation_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,

  FOREIGN KEY (organisation_id) REFERENCES organisation(id) ON DELETE CASCADE
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


