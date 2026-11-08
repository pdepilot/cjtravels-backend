-- Add user_id foreign key link to flights and hotels
ALTER TABLE flights ADD COLUMN user_id INT NULL;
ALTER TABLE hotels ADD COLUMN user_id INT NULL;

-- Link existing sample flight bookings to customers
UPDATE flights SET user_id = 1 WHERE ref IN ('TBF-2201', 'TBF-2202');
UPDATE flights SET user_id = 2 WHERE ref = 'TBF-2203';
UPDATE flights SET user_id = 3 WHERE ref = 'TBF-2195';
UPDATE flights SET user_id = 4 WHERE ref = 'TBF-2205';
UPDATE flights SET user_id = 5 WHERE ref = 'TBF-2204';
UPDATE flights SET user_id = 7 WHERE ref = 'TBF-2206';
UPDATE flights SET user_id = 8 WHERE ref = 'TBF-2207';

-- Link existing sample hotel reservations to customers
UPDATE hotels SET user_id = 1 WHERE ref = 'TBH-1180';
UPDATE hotels SET user_id = 2 WHERE ref = 'TBH-1182';
UPDATE hotels SET user_id = 3 WHERE ref = 'TBH-1175';
UPDATE hotels SET user_id = 5 WHERE ref IN ('TBH-1181', 'TBH-1183');
UPDATE hotels SET user_id = 7 WHERE ref = 'TBH-1184';

