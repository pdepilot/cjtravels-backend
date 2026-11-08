<?php

namespace Database\Seeders;

use App\Core\Database;

class DatabaseSeeder
{
    public static function run(): array
    {
        $db = Database::getInstance();
        $pdo = $db->getPdo();

        // 1. Check existing users
        $userCount = (int)($db->selectOne("SELECT COUNT(*) as c FROM users")['c'] ?? 0);
        $flightCount = (int)($db->selectOne("SELECT COUNT(*) as c FROM flights")['c'] ?? 0);
        $hotelCount = (int)($db->selectOne("SELECT COUNT(*) as c FROM hotels")['c'] ?? 0);
        $loanCount = (int)($db->selectOne("SELECT COUNT(*) as c FROM loan_enquiries")['c'] ?? 0);
        $txnCount = (int)($db->selectOne("SELECT COUNT(*) as c FROM transactions")['c'] ?? 0);
        $destCount = (int)($db->selectOne("SELECT COUNT(*) as c FROM destinations")['c'] ?? 0);

        $results = [
            'users' => 0,
            'flights' => 0,
            'hotels' => 0,
            'loans' => 0,
            'transactions' => 0,
            'destinations' => 0,
        ];

        // Seed Users if empty
        if ($userCount === 0) {
            $users = [
                ['name' => 'Adaeze Okonkwo', 'first_name' => 'Adaeze', 'last_name' => 'Okonkwo', 'email' => 'adaeze.okonkwo@example.com', 'phone' => '+234 812 345 6789', 'country' => 'Nigeria', 'city' => 'Lagos', 'role' => 'customer', 'tier' => 'Gold member', 'passport_number' => 'A12984572', 'passport_expiry' => '2031-08-14'],
                ['name' => 'Chinedu Adeyemi', 'first_name' => 'Chinedu', 'last_name' => 'Adeyemi', 'email' => 'chinedu.adeyemi@example.com', 'phone' => '+234 803 221 4410', 'country' => 'Nigeria', 'city' => 'Abuja', 'role' => 'customer', 'tier' => 'Platinum member', 'passport_number' => 'A14983210', 'passport_expiry' => '2030-03-22'],
                ['name' => 'Sarah Mitchell', 'first_name' => 'Sarah', 'last_name' => 'Mitchell', 'email' => 'sarah.mitchell@example.com', 'phone' => '+44 7700 900123', 'country' => 'United Kingdom', 'city' => 'London', 'role' => 'customer', 'tier' => 'Gold member', 'passport_number' => 'UK89214701', 'passport_expiry' => '2032-11-05'],
                ['name' => 'Kwame Mensah', 'first_name' => 'Kwame', 'last_name' => 'Mensah', 'email' => 'kwame.mensah@example.com', 'phone' => '+233 24 555 1122', 'country' => 'Ghana', 'city' => 'Accra', 'role' => 'customer', 'tier' => 'Silver member', 'passport_number' => 'GHA5829104', 'passport_expiry' => '2029-06-18'],
                ['name' => 'Fatima Al-Hassan', 'first_name' => 'Fatima', 'last_name' => 'Al-Hassan', 'email' => 'fatima.alhassan@example.com', 'phone' => '+971 50 123 4567', 'country' => 'United Arab Emirates', 'city' => 'Dubai', 'role' => 'customer', 'tier' => 'Gold member', 'passport_number' => 'UAE9930214', 'passport_expiry' => '2031-01-30'],
                ['name' => 'Daniel Ofori', 'first_name' => 'Daniel', 'last_name' => 'Ofori', 'email' => 'daniel.ofori@example.com', 'phone' => '+234 815 909 7788', 'country' => 'Nigeria', 'city' => 'Port Harcourt', 'role' => 'customer', 'tier' => 'Silver member', 'passport_number' => 'A16892301', 'passport_expiry' => '2028-12-11'],
                ['name' => 'Amara Nwosu', 'first_name' => 'Amara', 'last_name' => 'Nwosu', 'email' => 'amara.nwosu@example.com', 'phone' => '+254 712 345 678', 'country' => 'Kenya', 'city' => 'Nairobi', 'role' => 'customer', 'tier' => 'Gold member', 'passport_number' => 'K78291034', 'passport_expiry' => '2030-09-14'],
                ['name' => 'Liam Fraser', 'first_name' => 'Liam', 'last_name' => 'Fraser', 'email' => 'liam.fraser@example.com', 'phone' => '+44 7700 900456', 'country' => 'United Kingdom', 'city' => 'Edinburgh', 'role' => 'customer', 'tier' => 'Platinum member', 'passport_number' => 'UK49018234', 'passport_expiry' => '2033-04-19'],
                ['name' => 'Zainab Bello', 'first_name' => 'Zainab', 'last_name' => 'Bello', 'email' => 'zainab.bello@example.com', 'phone' => '+234 802 445 6677', 'country' => 'Nigeria', 'city' => 'Kano', 'role' => 'customer', 'tier' => 'Silver member', 'passport_number' => 'A18294711', 'passport_expiry' => '2029-10-02'],
                ['name' => 'Thabo Mokoena', 'first_name' => 'Thabo', 'last_name' => 'Mokoena', 'email' => 'thabo.mokoena@example.com', 'phone' => '+27 82 555 1234', 'country' => 'South Africa', 'city' => 'Johannesburg', 'role' => 'customer', 'tier' => 'Silver member', 'passport_number' => 'ZA82910482', 'passport_expiry' => '2030-07-25'],
            ];

            foreach ($users as $u) {
                $db->insert('users', $u);
                $results['users']++;
            }
        }

        // Seed Flights if empty
        if ($flightCount === 0) {
            $flights = [
                ['ref' => 'TBF-2201', 'airline' => 'British Airways', 'flight_no' => 'BA 074', 'origin' => 'Lagos (LOS)', 'destination' => 'London (LHR)', 'departure_date' => '2026-12-14', 'departure_time' => '23:10', 'arrival_time' => '05:50', 'duration' => '7h 40m', 'stops' => 'Direct', 'cabin' => 'First Class', 'price' => 1150000.00, 'status' => 'Confirmed'],
                ['ref' => 'TBF-2202', 'airline' => 'Emirates', 'flight_no' => 'EK 786', 'origin' => 'Abuja (ABV)', 'destination' => 'Dubai (DXB)', 'departure_date' => '2026-12-28', 'departure_time' => '17:25', 'arrival_time' => '23:45', 'duration' => '9h 20m', 'stops' => '1 stop', 'cabin' => 'Business', 'price' => 980000.00, 'status' => 'Pending'],
                ['ref' => 'TBF-2203', 'airline' => 'Kenya Airways', 'flight_no' => 'KQ 533', 'origin' => 'Lagos (LOS)', 'destination' => 'Cape Town (CPT)', 'departure_date' => '2027-01-05', 'departure_time' => '05:20', 'arrival_time' => '14:30', 'duration' => '13h 10m', 'stops' => '1 stop', 'cabin' => 'Premium Economy', 'price' => 875000.00, 'status' => 'Confirmed'],
                ['ref' => 'TBF-2195', 'airline' => 'Virgin Atlantic', 'flight_no' => 'VS 652', 'origin' => 'London (LHR)', 'destination' => 'Lagos (LOS)', 'departure_date' => '2026-11-22', 'departure_time' => '14:50', 'arrival_time' => '21:30', 'duration' => '6h 40m', 'stops' => 'Direct', 'cabin' => 'Upper Class', 'price' => 890000.00, 'status' => 'Completed'],
                ['ref' => 'TBF-2204', 'airline' => 'Qatar Airways', 'flight_no' => 'QR 1408', 'origin' => 'Lagos (LOS)', 'destination' => 'Doha (DOH)', 'departure_date' => '2026-11-30', 'departure_time' => '18:40', 'arrival_time' => '05:15', 'duration' => '8h 35m', 'stops' => 'Direct', 'cabin' => 'Business', 'price' => 1420000.00, 'status' => 'Confirmed'],
                ['ref' => 'TBF-2205', 'airline' => 'Air Peace', 'flight_no' => 'P4 7120', 'origin' => 'Abuja (ABV)', 'destination' => 'London (LGW)', 'departure_date' => '2026-12-05', 'departure_time' => '22:00', 'arrival_time' => '06:00', 'duration' => '6h 00m', 'stops' => 'Direct', 'cabin' => 'Economy', 'price' => 750000.00, 'status' => 'Pending'],
                ['ref' => 'TBF-2206', 'airline' => 'Turkish Airlines', 'flight_no' => 'TK 626', 'origin' => 'Lagos (LOS)', 'destination' => 'Istanbul (IST)', 'departure_date' => '2026-12-18', 'departure_time' => '20:15', 'arrival_time' => '05:40', 'duration' => '7h 25m', 'stops' => 'Direct', 'cabin' => 'Business', 'price' => 920000.00, 'status' => 'Confirmed'],
                ['ref' => 'TBF-2207', 'airline' => 'Delta Air Lines', 'flight_no' => 'DL 055', 'origin' => 'Lagos (LOS)', 'destination' => 'Atlanta (ATL)', 'departure_date' => '2026-10-25', 'departure_time' => '12:30', 'arrival_time' => '20:10', 'duration' => '12h 40m', 'stops' => 'Direct', 'cabin' => 'Delta One', 'price' => 1850000.00, 'status' => 'Completed'],
            ];

            foreach ($flights as $f) {
                $db->insert('flights', $f);
                $results['flights']++;
            }
        }

        // Seed Hotels if empty
        if ($hotelCount === 0) {
            $hotels = [
                ['ref' => 'TBH-1180', 'property' => 'The Wheatbaker', 'location' => 'Ikoyi, Lagos', 'checkin' => '2026-12-14', 'checkout' => '2026-12-17', 'nights' => 3, 'guests' => '2 guests', 'room_type' => 'Executive Suite', 'price' => 555000.00, 'status' => 'Confirmed'],
                ['ref' => 'TBH-1181', 'property' => 'Atlantis The Palm', 'location' => 'Palm Jumeirah, Dubai', 'checkin' => '2026-12-28', 'checkout' => '2027-01-02', 'nights' => 5, 'guests' => '2 guests', 'room_type' => 'Ocean Suite', 'price' => 1450000.00, 'status' => 'Pending'],
                ['ref' => 'TBH-1175', 'property' => 'The Hoxton Shoreditch', 'location' => 'Shoreditch, London', 'checkin' => '2026-11-20', 'checkout' => '2026-11-23', 'nights' => 3, 'guests' => '1 guest', 'room_type' => 'Roomy King', 'price' => 525000.00, 'status' => 'Completed'],
                ['ref' => 'TBH-1182', 'property' => 'Transcorp Hilton', 'location' => 'Maitama, Abuja', 'checkin' => '2026-12-05', 'checkout' => '2026-12-08', 'nights' => 3, 'guests' => '1 guest', 'room_type' => 'Presidential Suite', 'price' => 420000.00, 'status' => 'Confirmed'],
                ['ref' => 'TBH-1183', 'property' => 'Burj Al Arab Jumeirah', 'location' => 'Jumeirah, Dubai', 'checkin' => '2027-01-10', 'checkout' => '2027-01-14', 'nights' => 4, 'guests' => '2 guests', 'room_type' => 'Deluxe Marina Suite', 'price' => 2800000.00, 'status' => 'Pending'],
                ['ref' => 'TBH-1184', 'property' => 'One&Only Cape Town', 'location' => 'V&A Waterfront, Cape Town', 'checkin' => '2027-01-05', 'checkout' => '2027-01-09', 'nights' => 4, 'guests' => '2 guests', 'room_type' => 'Marina Table Mountain Suite', 'price' => 1100000.00, 'status' => 'Confirmed'],
            ];

            foreach ($hotels as $h) {
                $db->insert('hotels', $h);
                $results['hotels']++;
            }
        }

        // Seed Loans if empty
        if ($loanCount === 0) {
            $userRows = $db->select("SELECT id, name FROM users ORDER BY id ASC");
            $u1 = $userRows[0]['id'] ?? 1;
            $u2 = $userRows[1]['id'] ?? 2;
            $u5 = $userRows[4]['id'] ?? 1;
            $u9 = $userRows[8]['id'] ?? 1;

            $loans = [
                ['ref' => 'TBLN-0454', 'user_id' => $u5, 'amount' => 1500000.00, 'period' => '6 months', 'purpose' => 'Dubai Luxury Holiday Spread', 'status' => 'In review', 'note' => 'Awaiting financial proof of employment verification', 'submitted' => '2026-11-04'],
                ['ref' => 'TBLN-0452', 'user_id' => $u2, 'amount' => 850000.00, 'period' => '3 months', 'purpose' => 'London Conference & Flight', 'status' => 'Approved', 'note' => 'Approved with 20% down payment confirmed', 'submitted' => '2026-10-28'],
                ['ref' => 'TBLN-0450', 'user_id' => $u9, 'amount' => 2200000.00, 'period' => '12 months', 'purpose' => 'Annual Family Vacation to Cape Town', 'status' => 'In review', 'note' => 'Guarantor documents uploaded and validated', 'submitted' => '2026-11-02'],
                ['ref' => 'TBLN-0448', 'user_id' => $u1, 'amount' => 600000.00, 'period' => '3 months', 'purpose' => 'Domestic Business Retainer Travel', 'status' => 'Approved', 'note' => 'Gold member automatic pre-clearance', 'submitted' => '2026-10-15'],
            ];

            foreach ($loans as $l) {
                $db->insert('loan_enquiries', $l);
                $results['loans']++;
            }
        }

        // Seed Transactions if empty
        if ($txnCount === 0) {
            $userRows = $db->select("SELECT id FROM users ORDER BY id ASC");
            $u1 = $userRows[0]['id'] ?? 1;
            $u2 = $userRows[1]['id'] ?? 2;
            $u3 = $userRows[2]['id'] ?? 3;
            $u4 = $userRows[3]['id'] ?? 4;
            $u5 = $userRows[4]['id'] ?? 5;
            $u7 = $userRows[6]['id'] ?? 7;
            $u8 = $userRows[7]['id'] ?? 8;

            $transactions = [
                ['ref' => 'PAY-9005', 'user_id' => $u1, 'related_ref' => 'TBF-2201', 'method' => 'Paystack / Card', 'amount' => 1150000.00, 'status' => 'Paid', 'transacted_at' => '2026-11-05'],
                ['ref' => 'PAY-9006', 'user_id' => $u2, 'related_ref' => 'TBH-1180', 'method' => 'Bank Transfer', 'amount' => 555000.00, 'status' => 'Paid', 'transacted_at' => '2026-11-04'],
                ['ref' => 'PAY-9007', 'user_id' => $u3, 'related_ref' => 'TBF-2202', 'method' => 'Card', 'amount' => 980000.00, 'status' => 'Pending', 'transacted_at' => '2026-11-06'],
                ['ref' => 'PAY-9008', 'user_id' => $u4, 'related_ref' => 'TBF-2203', 'method' => 'Bank Transfer', 'amount' => 875000.00, 'status' => 'Paid', 'transacted_at' => '2026-11-02'],
                ['ref' => 'PAY-9009', 'user_id' => $u5, 'related_ref' => 'TBH-1181', 'method' => 'Card', 'amount' => 1450000.00, 'status' => 'Pending', 'transacted_at' => '2026-11-05'],
                ['ref' => 'PAY-9010', 'user_id' => $u7, 'related_ref' => 'TBF-2204', 'method' => 'Card', 'amount' => 1420000.00, 'status' => 'Paid', 'transacted_at' => '2026-10-30'],
                ['ref' => 'PAY-9011', 'user_id' => $u8, 'related_ref' => 'TBH-1182', 'method' => 'Bank Transfer', 'amount' => 420000.00, 'status' => 'Paid', 'transacted_at' => '2026-10-25'],
                ['ref' => 'PAY-9012', 'user_id' => $u1, 'related_ref' => 'TBF-2206', 'method' => 'Card', 'amount' => 920000.00, 'status' => 'Paid', 'transacted_at' => '2026-11-03'],
                ['ref' => 'PAY-9013', 'user_id' => $u2, 'related_ref' => 'TBH-1183', 'method' => 'Card', 'amount' => 2800000.00, 'status' => 'Pending', 'transacted_at' => '2026-11-06'],
                ['ref' => 'PAY-9014', 'user_id' => $u4, 'related_ref' => 'TBF-2205', 'method' => 'Bank Transfer', 'amount' => 750000.00, 'status' => 'Pending', 'transacted_at' => '2026-11-06'],
            ];

            foreach ($transactions as $t) {
                $db->insert('transactions', $t);
                $results['transactions']++;
            }
        }

        // Seed Destinations if empty
        if ($destCount === 0) {
            $destinations = [
                ['city' => 'Cape Town', 'country' => 'South Africa', 'continent' => 'Africa', 'categories' => 'Adventure, Beaches, City Breaks', 'blurb' => 'Where Table Mountain meets the Atlantic.', 'image' => 'https://images.unsplash.com/photo-1580060839134-75a5edca2e99?auto=format&fit=crop&w=700&q=80', 'status' => 'Active'],
                ['city' => 'Paris', 'country' => 'France', 'continent' => 'Europe', 'categories' => 'Culture, City Breaks, Luxury', 'blurb' => 'The classic city break, endlessly reimagined.', 'image' => 'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?auto=format&fit=crop&w=700&q=80', 'status' => 'Active'],
                ['city' => 'Dubai', 'country' => 'United Arab Emirates', 'continent' => 'Middle East', 'categories' => 'Luxury, City Breaks', 'blurb' => 'Skyline ambition and desert calm.', 'image' => 'https://images.unsplash.com/photo-1512453979798-5ea266f8880c?auto=format&fit=crop&w=700&q=80', 'status' => 'Active'],
                ['city' => 'Bali', 'country' => 'Indonesia', 'continent' => 'Asia', 'categories' => 'Beaches, Adventure, Culture', 'blurb' => 'Rice terraces, surf breaks and temple mornings.', 'image' => 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?auto=format&fit=crop&w=700&q=80', 'status' => 'Active'],
                ['city' => 'Santorini', 'country' => 'Greece', 'continent' => 'Europe', 'categories' => 'Beaches, Luxury, Culture', 'blurb' => 'Whitewashed cliffs above a volcanic caldera.', 'image' => 'https://images.unsplash.com/photo-1570077188670-e3a8d69ac5ff?auto=format&fit=crop&w=700&q=80', 'status' => 'Active'],
                ['city' => 'Istanbul', 'country' => 'Türkiye', 'continent' => 'Middle East', 'categories' => 'Culture, City Breaks', 'blurb' => 'Two continents, one skyline.', 'image' => 'https://images.unsplash.com/photo-1524231757912-21f4fe3a7200?auto=format&fit=crop&w=700&q=80', 'status' => 'Active'],
                ['city' => 'Maldives', 'country' => 'Maldives', 'continent' => 'Asia', 'categories' => 'Beaches, Luxury', 'blurb' => 'Overwater villas and impossibly clear lagoons.', 'image' => 'https://images.unsplash.com/photo-1514282401047-d79a71a590e8?auto=format&fit=crop&w=700&q=80', 'status' => 'Active'],
                ['city' => 'Sydney', 'country' => 'Australia', 'continent' => 'Oceania', 'categories' => 'City Breaks, Beaches', 'blurb' => 'Harbour views and coastal walks.', 'image' => 'https://images.unsplash.com/photo-1506973035872-a4ec16b8e8d9?auto=format&fit=crop&w=700&q=80', 'status' => 'Active'],
                ['city' => 'New York', 'country' => 'United States', 'continent' => 'Americas', 'categories' => 'City Breaks, Culture', 'blurb' => 'Five boroughs, endless energy.', 'image' => 'https://images.unsplash.com/photo-1496442226666-8d4d0e62e6e9?auto=format&fit=crop&w=700&q=80', 'status' => 'Active'],
            ];
            foreach ($destinations as $d) {
                $db->insert('destinations', $d);
                $results['destinations']++;
            }
        }

        return $results;
    }
}

