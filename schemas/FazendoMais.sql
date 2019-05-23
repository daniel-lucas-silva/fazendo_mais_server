CREATE TABLE `posts`
(
  `id` integer PRIMARY KEY,
  `author_id` integer,
  `categories` text,
  `title` varchar(255),
  `slug` varchar(255),
  `content` longtext,
  `status` enum('published'),
  `approved` boolean,
  `published_at` datetime,
  `deleted_at` datetime,
  `created_at` datetime,
  `updated_at` datetime
);

CREATE TABLE `entities`
(
  `id` integer PRIMARY KEY,
  `categories` text,
  `slug` varchar(255),
  `name` varchar(255),
  `about` longtext,
  `avatar` varchar(255),
  `approved` boolean,
  `deleted_at` datetime,
  `created_at` datetime,
  `updated_at` datetime
);

CREATE TABLE `reactions`
(
  `thumbsup` integer,
  `thumbsdown` integer,
  `fire` integer,
  `heart_eyes` integer,
  `joy` integer,
  `cry` integer,
  `reactable_id` integer,
  `reactable_type` enum('post')
);

CREATE TABLE `user_reaction`
(
  `user_id` integer,
  `reactable_id` integer,
  `reactable_type` enum('post'),
  `reaction` enum('thumbsup','thumbsdown','fire','heart_eyes','joy','cry')
);

CREATE TABLE `categories`
(
  `id` integer PRIMARY KEY,
  `parent_id` integer,
  `name` integer,
  `slug` integer,
  `created_at` datetime,
  `updated_at` datetime
);

CREATE TABLE `comments`
(
  `id` integer PRIMARY KEY,
  `user_id` integer,
  `commentable_id` integer,
  `commentable_type` enum('post'),
  `content` text,
  `is_approved` boolean,
  `deleted_at` datetime,
  `created_at` datetime,
  `updated_at` datetime
);

CREATE TABLE `images`
(
  `id` integer PRIMARY KEY,
  `path` varchar(255),
  `name` varchar(255),
  `imageable_id` integer,
  `imageable_type` enum('gallery'),
  `order` integer,
  `created_at` datetime,
  `updated_at` datetime
);

CREATE TABLE `pages`
(
  `id` integer PRIMARY KEY,
  `author_id` integer,
  `title` varchar(255),
  `slug` varchar(255),
  `content` longtext,
  `visibility` tinyint,
  `position` varchar(255),
  `published_at` datetime,
  `deleted_at` datetime,
  `created_at` datetime,
  `updated_at` datetime
);

CREATE TABLE `contact_us`
(
  `id` integer PRIMARY KEY,
  `name` varchar(255),
  `phone` varchar(255),
  `email` varchar(255),
  `subject` varchar(255),
  `message` longtext,
  `read_at` datetime,
  `deleted_at` datetime,
  `created_at` datetime,
  `updated_at` datetime
);

CREATE TABLE `password_resets`
(
  `email` varchar(255),
  `token` varchar(255) PRIMARY KEY,
  `created_at` datetime
);

CREATE TABLE `reports`
(
  `id` integer PRIMARY KEY,
  `user_id` integer,
  `reportable_id` integer,
  `reportable_type` varchar(255),
  `message` longtext,
  `is_approved` boolean,
  `created_at` datetime,
  `updated_at` datetime
);

CREATE TABLE `searches`
(
  `id` integer PRIMARY KEY,
  `user_id` integer,
  `query` text,
  `created_at` datetime
);

CREATE TABLE `subscribers`
(
  `user_id` integer,
  `entity_id` integer
);

CREATE TABLE `sliders`
(
  `id` integer PRIMARY KEY,
  `title` varchar(255),
  `title_color` varchar(255),
  `subtitle` varchar(255),
  `subtitle_color` varchar(255),
  `link` varchar(255),
  `order` integer,
  `created_at` datetime,
  `updated_at` datetime
);

CREATE TABLE `users`
(
  `id` integer PRIMARY KEY,
  `entity_id` integer,
  `email` varchar(255),
  `username` varchar(255),
  `password` varchar(255),
  `name` varchar(255),
  `description` varchar(255),
  `avatar` varchar(255),
  `role` varchar(255),
  `active` boolean,
  `verification` varchar(255),
  `verified` boolean,
  `login_attempts` integer,
  `last_login` datetime,
  `block_expires` datetime,
  `created_at` datetime,
  `updated_at` datetime
);

CREATE TABLE `user_access`
(
  `id` integer,
  `email` varchar(255),
  `ip` varchar(255),
  `platform` varchar(255),
  `date` datetime
);

CREATE TABLE `settings`
(
  `id` integer PRIMARY KEY,
  `field` varchar(255),
  `value` text
);

CREATE TABLE `logs`
(
  `id` integer PRIMARY KEY,
  `email` varchar(255),
  `route` varchar(255),
  `date` datetime
);

CREATE TABLE `payment_data`
(
  `id` integer PRIMARY KEY,
  `user_id` integer,
  `documents` text,
  `phone_numbers` text,
  `billing_address` text,
  `birthday` date
);

CREATE TABLE `addresses`
(
  `id` integer PRIMARY KEY,
  `address_type` varchar(255),
  `address_title` varchar(255),
  `street` varchar(255),
  `street_number` varchar(255),
  `neighborhood` varchar(255),
  `city` varchar(255),
  `state` char,
  `country` char,
  `zipcode` varchar(255),
  `coordinates` varchar(255),
  `addressable_id` integer,
  `addressable_type` varchar(255),
  `created_at` datetime,
  `updated_at` datetime
);

ALTER TABLE `posts` ADD FOREIGN KEY (`author_id`) REFERENCES `users` (`id`);

ALTER TABLE `categories` ADD FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`);

ALTER TABLE `comments` ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `pages` ADD FOREIGN KEY (`author_id`) REFERENCES `users` (`id`);

ALTER TABLE `reports` ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `searches` ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `subscribers` ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `subscribers` ADD FOREIGN KEY (`entity_id`) REFERENCES `entities` (`id`);

ALTER TABLE `users` ADD FOREIGN KEY (`entity_id`) REFERENCES `entities` (`id`);

ALTER TABLE `payment_data` ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
