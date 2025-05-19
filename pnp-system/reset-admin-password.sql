-- Reset admin password to "admin123"
UPDATE `users` 
SET `password` = '$2y$10$wAp12NeZ2PU.UZaufO4Sue9EVVGrgj9KNK2TT9zVU8m/dSQfJYVQ6' 
WHERE `username` = 'admin'; 