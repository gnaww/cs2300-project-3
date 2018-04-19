/* Create gallery table */
CREATE TABLE `gallery` (
	`id`	INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
	`image_id`	INTEGER NOT NULL,
	`tag_id`	INTEGER NOT NULL
);

/* Create images table */
CREATE TABLE `images` (
	`id`	INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
	`file_name`	TEXT NOT NULL,
	`file_extension`	TEXT NOT NULL,
	`description`	TEXT,
	`upload_user`	TEXT NOT NULL,
	`source` TEXT NOT NULL
);

/* Create tags table */
CREATE TABLE `tags` (
	`id`	INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
	`tag_name`	TEXT NOT NULL UNIQUE
);

/* Create users table */
CREATE TABLE `users` (
	`id`	INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
	`username`	TEXT NOT NULL UNIQUE,
	`password`	TEXT NOT NULL,
	`session_id`	TEXT UNIQUE
);

/* Seed data for users table */
INSERT INTO `users` (username, password) VALUES ('admin', '$2y$10$uvMu3myWjL41A/MQ2APCT.GVqB0ETJebT.mPv40vbSX4ROD5nmtra'); /* admin: password */
INSERT INTO `users` (username, password) VALUES ('realuser', '$2y$10$vpB68dO.9HlOfIf.Kp.d9.J.ld9I.fERq9HAcJyQsTcD8gM4dzcwS'); /* realuser: realpassword */

/* Seed data for images table */
INSERT INTO `images` (file_name, file_extension, upload_user, source) VALUES ('1', 'jpg', 'admin', 'wallpaperscraft.com');
INSERT INTO `images` (file_name, file_extension, upload_user, source) VALUES ('2', 'jpg', 'admin', 'wallpaperscraft.com');
INSERT INTO `images` (file_name, file_extension, upload_user, source) VALUES ('3', 'jpg', 'admin', 'wallpaperscraft.com');
INSERT INTO `images` (file_name, file_extension, upload_user, source) VALUES ('4', 'jpg', 'admin', 'wallpaperscraft.com');
INSERT INTO `images` (file_name, file_extension, upload_user, source) VALUES ('5', 'jpg', 'admin', 'wallpaperscraft.com');
INSERT INTO `images` (file_name, file_extension, upload_user, source) VALUES ('6', 'jpg', 'realuser', 'wallpaperscraft.com');
INSERT INTO `images` (file_name, file_extension, upload_user, source) VALUES ('7', 'jpg', 'realuser', 'wallpaperscraft.com');
INSERT INTO `images` (file_name, file_extension, upload_user, source, description) VALUES ('8', 'jpg', 'realuser', 'behance.net/romaintrystram', 'Taipei 101, Taiwan');
INSERT INTO `images` (file_name, file_extension, upload_user, source) VALUES ('9', 'jpg', 'realuser', 'behance.net/romaintrystram');
INSERT INTO `images` (file_name, file_extension, upload_user, source) VALUES ('10', 'jpg', 'realuser', 'behance.net/romaintrystram');

/* Seed data for tags table */
INSERT INTO `tags` (tag_name) VALUES ('drawn');
INSERT INTO `tags` (tag_name) VALUES ('landscape');
INSERT INTO `tags` (tag_name) VALUES ('realistic');
INSERT INTO `tags` (tag_name) VALUES ('fantasy');
INSERT INTO `tags` (tag_name) VALUES ('photograph');

/* Seed data for gallery table */
INSERT INTO `gallery` (image_id, tag_id) VALUES (1, 1);
INSERT INTO `gallery` (image_id, tag_id) VALUES (1, 2);
INSERT INTO `gallery` (image_id, tag_id) VALUES (1, 4);
INSERT INTO `gallery` (image_id, tag_id) VALUES (2, 1);
INSERT INTO `gallery` (image_id, tag_id) VALUES (2, 3);
INSERT INTO `gallery` (image_id, tag_id) VALUES (3, 1);
INSERT INTO `gallery` (image_id, tag_id) VALUES (3, 2);
INSERT INTO `gallery` (image_id, tag_id) VALUES (4, 1);
INSERT INTO `gallery` (image_id, tag_id) VALUES (5, 1);
INSERT INTO `gallery` (image_id, tag_id) VALUES (6, 1);
INSERT INTO `gallery` (image_id, tag_id) VALUES (7, 1);
INSERT INTO `gallery` (image_id, tag_id) VALUES (8, 1);
INSERT INTO `gallery` (image_id, tag_id) VALUES (9, 1);
