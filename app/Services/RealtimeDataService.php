<?php

namespace App\Services;

use App\Core\Database;

class RealtimeDataService
{
    public static function getAdminData(): array
    {
        $db = Database::getInstance();

        // 1. Customers (users table)
        $users = $db->select("
            SELECT u.*, 
                   (SELECT COUNT(*) FROM flights WHERE user_id = u.id) + 
                   (SELECT COUNT(*) FROM hotels WHERE user_id = u.id) as bookings_count
            FROM users u
            ORDER BY u.id ASC
        ");

        $customers = [];
        $customerLookup = [];
        foreach ($users as $u) {
            $cId = 'c' . $u['id'];
            $custName = trim($u['name'] ?: ($u['first_name'] . ' ' . $u['last_name']));
            $customerLookup[$u['id']] = $custName;
            $customerLookup[$cId] = $custName;

            $customers[] = [
                'id' => $cId,
                'dbId' => (int)$u['id'],
                'firstName' => $u['first_name'] ?: $custName,
                'lastName' => $u['last_name'] ?: '',
                'email' => $u['email'],
                'phone' => $u['phone'] ?? '—',
                'country' => $u['country'] ?? 'Nigeria',
                'city' => $u['city'] ?? 'Lagos',
                'joined' => date('Y-m-d', strtotime($u['created_at'] ?? 'now')),
                'bookings' => (int)($u['bookings_count'] ?? 0),
                'status' => 'Active',
                'tier' => $u['tier'] ?? 'Gold member',
            ];
        }

        // 2. Flights (flights table)
        $flightRows = $db->select("
            SELECT f.*, u.name as customer_name
            FROM flights f
            LEFT JOIN users u ON f.user_id = u.id
            ORDER BY f.id DESC
        ");

        $flights = [];
        foreach ($flightRows as $f) {
            $fUid = $f['user_id'] ? (int)$f['user_id'] : 1;
            $flights[] = [
                'id' => 'f' . $f['id'],
                'dbId' => (int)$f['id'],
                'ref' => $f['ref'],
                'customerId' => 'c' . $fUid,
                'customerName' => $f['customer_name'] ?? ($customerLookup[$fUid] ?? 'Valued Customer'),
                'airline' => $f['airline'],
                'flightNo' => $f['flight_no'],
                'from' => $f['origin'],
                'to' => $f['destination'],
                'date' => $f['departure_date'],
                'time' => $f['departure_time'],
                'duration' => $f['duration'],
                'stops' => $f['stops'],
                'cabin' => $f['cabin'],
                'amount' => (float)$f['price'],
                'status' => $f['status'],
                'continent' => 'International',
            ];
        }

        // 3. Hotels (hotels table)
        $hotelRows = $db->select("
            SELECT h.*, u.name as customer_name
            FROM hotels h
            LEFT JOIN users u ON h.user_id = u.id
            ORDER BY h.id DESC
        ");

        $hotels = [];
        foreach ($hotelRows as $h) {
            $hUid = $h['user_id'] ? (int)$h['user_id'] : 1;
            $hotels[] = [
                'id' => 'h' . $h['id'],
                'dbId' => (int)$h['id'],
                'ref' => $h['ref'],
                'customerId' => 'c' . $hUid,
                'customerName' => $h['customer_name'] ?? ($customerLookup[$hUid] ?? 'Valued Customer'),
                'property' => $h['property'],
                'location' => $h['location'],
                'checkIn' => $h['checkin'],
                'checkOut' => $h['checkout'],
                'nights' => (int)$h['nights'],
                'guests' => $h['guests'],
                'roomType' => $h['room_type'],
                'amount' => (float)$h['price'],
                'status' => $h['status'],
                'continent' => 'International',
            ];
        }

        // 4. Loans (loan_enquiries table)
        $loanRows = $db->select("
            SELECT l.*, u.name as customer_name
            FROM loan_enquiries l
            LEFT JOIN users u ON l.user_id = u.id
            ORDER BY l.id DESC
        ");

        $loans = [];
        foreach ($loanRows as $l) {
            $lUid = $l['user_id'] ? (int)$l['user_id'] : 1;
            $loans[] = [
                'id' => 'l' . $l['id'],
                'dbId' => (int)$l['id'],
                'ref' => $l['ref'],
                'customerId' => 'c' . $lUid,
                'customerName' => $l['customer_name'] ?? ($customerLookup[$lUid] ?? 'Applicant'),
                'amount' => (float)$l['amount'],
                'period' => $l['period'],
                'purpose' => $l['purpose'],
                'submitted' => $l['submitted'],
                'status' => $l['status'],
                'note' => $l['note'] ?? '',
            ];
        }

        // 5. Transactions (transactions table)
        $txnRows = $db->select("
            SELECT t.*, u.name as customer_name
            FROM transactions t
            LEFT JOIN users u ON t.user_id = u.id
            ORDER BY t.id DESC
        ");

        $transactions = [];
        foreach ($txnRows as $t) {
            $tUid = $t['user_id'] ? (int)$t['user_id'] : 1;
            $transactions[] = [
                'id' => 't' . $t['id'],
                'dbId' => (int)$t['id'],
                'ref' => $t['ref'],
                'customerId' => 'c' . $tUid,
                'customerName' => $t['customer_name'] ?? ($customerLookup[$tUid] ?? 'Client'),
                'related' => $t['related_ref'] ?? '—',
                'method' => $t['method'],
                'date' => $t['transacted_at'],
                'amount' => (float)$t['amount'],
                'status' => $t['status'],
            ];
        }

        // 6. Destinations (destinations table)
        $destRows = $db->select("SELECT * FROM destinations ORDER BY id ASC");
        $destinations = [];
        foreach ($destRows as $d) {
            $cats = [];
            if (!empty($d['categories'])) {
                $cats = is_array($d['categories'])
                    ? $d['categories']
                    : array_map('trim', explode(',', $d['categories']));
            }
            $destinations[] = [
                'id' => 'd' . $d['id'],
                'dbId' => (int)$d['id'],
                'city' => $d['city'],
                'country' => $d['country'],
                'continent' => $d['continent'],
                'categories' => $cats ?: ['City Breaks'],
                'blurb' => $d['blurb'] ?? '',
                'image' => $d['image'] ?? '',
                'status' => $d['status'] ?? 'Active',
            ];
        }

        // 7. Dynamic Real-time Activities from MySQL
        $activities = [];
        foreach (array_slice($transactions, 0, 3) as $t) {
            $activities[] = [
                'icon' => 'bi-credit-card-2-front-fill',
                'tone' => ($t['status'] === 'Paid' ? 'green' : 'gold'),
                'title' => ($t['status'] === 'Paid' ? 'Payment verified' : 'Payment pending'),
                'text' => $t['ref'] . ' · ₦' . number_format($t['amount']) . ' · ' . $t['customerName'],
                'time' => date('M j', strtotime($t['date'])),
            ];
        }
        foreach (array_slice($flights, 0, 2) as $f) {
            $activities[] = [
                'icon' => 'bi-airplane-fill',
                'tone' => 'violet',
                'title' => 'Flight booking ' . strtolower($f['status']),
                'text' => $f['ref'] . ' · ' . $f['customerName'] . ' · ' . $f['from'] . ' → ' . $f['to'],
                'time' => date('M j', strtotime($f['date'])),
            ];
        }
        foreach (array_slice($loans, 0, 2) as $l) {
            $activities[] = [
                'icon' => 'bi-cash-coin',
                'tone' => '',
                'title' => 'Financing enquiry ' . strtolower($l['status']),
                'text' => $l['ref'] . ' · ' . $l['customerName'] . ' · ₦' . number_format($l['amount']),
                'time' => date('M j', strtotime($l['submitted'])),
            ];
        }

        // 8. Dynamic Monthly Bookings Aggregation from MySQL
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $monthlyFlights = array_fill_keys($months, 0);
        $monthlyHotels = array_fill_keys($months, 0);

        foreach ($flights as $f) {
            if (!empty($f['date'])) {
                $m = date('M', strtotime($f['date']));
                if (isset($monthlyFlights[$m])) {
                    $monthlyFlights[$m]++;
                }
            }
        }
        foreach ($hotels as $h) {
            if (!empty($h['checkIn'])) {
                $m = date('M', strtotime($h['checkIn']));
                if (isset($monthlyHotels[$m])) {
                    $monthlyHotels[$m]++;
                }
            }
        }

        $monthly = [];
        foreach ($months as $m) {
            $monthly[] = [
                'month' => $m,
                'flights' => $monthlyFlights[$m],
                'hotels' => $monthlyHotels[$m],
            ];
        }

        return [
            'customers' => $customers,
            'flights' => $flights,
            'hotels' => $hotels,
            'loans' => $loans,
            'transactions' => $transactions,
            'destinations' => $destinations,
            'activities' => $activities,
            'monthly' => $monthly,
            'counts' => [
                'customers' => count($customers),
                'flights' => count($flights),
                'hotels' => count($hotels),
                'loans' => count($loans),
                'destinations' => count($destinations),
                'transactions' => count($transactions),
            ],
        ];
    }

    public static function getCustomerData(int $userId = 1): array
    {
        $db = Database::getInstance();
        $user = $db->selectOne("SELECT * FROM users WHERE id = :id LIMIT 1", [':id' => $userId]);

        $custName = $user ? trim($user['name'] ?: ($user['first_name'] . ' ' . $user['last_name'])) : 'Adaeze Okonkwo';
        $firstName = $user['first_name'] ?? 'Adaeze';
        $initials = strtoupper(substr($firstName, 0, 1) . substr($user['last_name'] ?? 'O', 0, 1));

        $customerObj = [
            'name' => $custName,
            'firstName' => $firstName,
            'initials' => $initials,
            'email' => $user['email'] ?? 'adaeze.okonkwo@example.com',
            'phone' => $user['phone'] ?? '+234 812 345 6789',
            'country' => $user['country'] ?? 'Nigeria',
            'memberSince' => date('F Y', strtotime($user['created_at'] ?? 'now')),
            'tier' => $user['tier'] ?? 'Gold member',
        ];

        // Customer flights
        $flightRows = $db->select("SELECT * FROM flights WHERE user_id = :id ORDER BY departure_date DESC", [':id' => $userId]);
        $flights = [];
        foreach ($flightRows as $f) {
            $flights[] = [
                'id' => 'f' . $f['id'],
                'ref' => $f['ref'],
                'kind' => 'flight',
                'airline' => $f['airline'],
                'flightNo' => $f['flight_no'],
                'from' => $f['origin'],
                'to' => $f['destination'],
                'date' => $f['departure_date'],
                'time' => $f['departure_time'],
                'arrival' => $f['arrival_time'] ?? '23:45',
                'duration' => $f['duration'],
                'stops' => $f['stops'],
                'cabin' => $f['cabin'],
                'passengers' => '1 adult',
                'status' => $f['status'],
                'price' => (float)$f['price'],
            ];
        }

        // Customer hotels
        $hotelRows = $db->select("SELECT * FROM hotels WHERE user_id = :id ORDER BY checkin DESC", [':id' => $userId]);
        $hotels = [];
        foreach ($hotelRows as $h) {
            $hotels[] = [
                'id' => 'h' . $h['id'],
                'ref' => $h['ref'],
                'kind' => 'hotel',
                'property' => $h['property'],
                'location' => $h['location'],
                'checkin' => $h['checkin'],
                'checkout' => $h['checkout'],
                'nights' => (int)$h['nights'],
                'guests' => $h['guests'],
                'rooms' => '1 room',
                'roomType' => $h['room_type'],
                'status' => $h['status'],
                'price' => (float)$h['price'],
            ];
        }

        // Customer financing
        $loanRows = $db->select("SELECT * FROM loan_enquiries WHERE user_id = :id ORDER BY submitted DESC", [':id' => $userId]);
        $financing = [];
        foreach ($loanRows as $l) {
            $financing[] = [
                'id' => 'fin' . $l['id'],
                'ref' => $l['ref'],
                'kind' => 'financing',
                'amount' => (float)$l['amount'],
                'period' => $l['period'],
                'purpose' => $l['purpose'],
                'submitted' => $l['submitted'],
                'status' => $l['status'],
                'note' => $l['note'] ?? 'Financing workflow enquiry.',
            ];
        }

        // Customer payments
        $txnRows = $db->select("SELECT * FROM transactions WHERE user_id = :id ORDER BY transacted_at DESC", [':id' => $userId]);
        $payments = [];
        foreach ($txnRows as $t) {
            $payments[] = [
                'id' => 'p' . $t['id'],
                'ref' => $t['ref'],
                'kind' => 'payment',
                'related' => $t['related_ref'] ?? 'Booking',
                'method' => $t['method'],
                'date' => $t['transacted_at'],
                'amount' => (float)$t['amount'],
                'status' => $t['status'],
            ];
        }

        return [
            'customer' => $customerObj,
            'flights' => $flights,
            'hotels' => $hotels,
            'financing' => $financing,
            'payments' => $payments,
        ];
    }

    public static function updateRecord(string $kind, string|int $id, array $data): bool
    {
        $db = Database::getInstance();
        $cleanId = (int)preg_replace('/[^0-9]/', '', (string)$id);
        if ($cleanId <= 0) {
            return false;
        }

        $now = date('Y-m-d H:i:s');

        switch ($kind) {
            case 'customer':
                $update = ['updated_at' => $now];
                if (isset($data['firstName'])) $update['first_name'] = trim($data['firstName']);
                if (isset($data['lastName'])) $update['last_name'] = trim($data['lastName']);
                if (isset($data['firstName']) || isset($data['lastName'])) {
                    $fn = $data['firstName'] ?? '';
                    $ln = $data['lastName'] ?? '';
                    $update['name'] = trim($fn . ' ' . $ln);
                }
                if (isset($data['email'])) $update['email'] = strtolower(trim($data['email']));
                if (isset($data['phone'])) $update['phone'] = trim($data['phone']);
                if (isset($data['country'])) $update['country'] = trim($data['country']);
                if (isset($data['status'])) $update['role'] = ($data['status'] === 'Active' ? 'customer' : 'inactive');

                $db->update('users', $update, 'id = :id', [':id' => $cleanId]);
                return true;

            case 'flight':
                $update = [];
                if (isset($data['airline'])) $update['airline'] = trim($data['airline']);
                if (isset($data['flightNo'])) $update['flight_no'] = trim($data['flightNo']);
                if (isset($data['from'])) $update['origin'] = trim($data['from']);
                if (isset($data['to'])) $update['destination'] = trim($data['to']);
                if (isset($data['date'])) $update['departure_date'] = $data['date'];
                if (isset($data['time'])) $update['departure_time'] = trim($data['time']);
                if (isset($data['cabin'])) $update['cabin'] = trim($data['cabin']);
                if (isset($data['amount'])) $update['price'] = (float)$data['amount'];
                if (isset($data['status'])) $update['status'] = trim($data['status']);

                if (!empty($update)) {
                    $db->update('flights', $update, 'id = :id', [':id' => $cleanId]);
                }
                return true;

            case 'hotel':
                $update = [];
                if (isset($data['property'])) $update['property'] = trim($data['property']);
                if (isset($data['location'])) $update['location'] = trim($data['location']);
                if (isset($data['checkIn'])) $update['checkin'] = $data['checkIn'];
                if (isset($data['nights'])) $update['nights'] = (int)$data['nights'];
                if (isset($data['guests'])) $update['guests'] = trim($data['guests']);
                if (isset($data['roomType'])) $update['room_type'] = trim($data['roomType']);
                if (isset($data['amount'])) $update['price'] = (float)$data['amount'];
                if (isset($data['status'])) $update['status'] = trim($data['status']);

                if (!empty($update)) {
                    $db->update('hotels', $update, 'id = :id', [':id' => $cleanId]);
                }
                return true;

            case 'loan':
                $update = [];
                if (isset($data['amount'])) $update['amount'] = (float)$data['amount'];
                if (isset($data['period'])) $update['period'] = trim($data['period']);
                if (isset($data['purpose'])) $update['purpose'] = trim($data['purpose']);
                if (isset($data['status'])) $update['status'] = trim($data['status']);
                if (isset($data['note'])) $update['note'] = trim($data['note']);

                if (!empty($update)) {
                    $db->update('loan_enquiries', $update, 'id = :id', [':id' => $cleanId]);
                }
                return true;

            case 'transaction':
                $update = [];
                if (isset($data['related'])) $update['related_ref'] = trim($data['related']);
                if (isset($data['method'])) $update['method'] = trim($data['method']);
                if (isset($data['amount'])) $update['amount'] = (float)$data['amount'];
                if (isset($data['status'])) $update['status'] = trim($data['status']);
                if (isset($data['date'])) $update['transacted_at'] = $data['date'];

                if (!empty($update)) {
                    $db->update('transactions', $update, 'id = :id', [':id' => $cleanId]);
                }
                return true;

            case 'destination':
                $update = ['updated_at' => $now];
                if (isset($data['city'])) $update['city'] = trim($data['city']);
                if (isset($data['country'])) $update['country'] = trim($data['country']);
                if (isset($data['continent'])) $update['continent'] = trim($data['continent']);
                if (isset($data['categories'])) {
                    $update['categories'] = is_array($data['categories'])
                        ? implode(', ', $data['categories'])
                        : trim($data['categories']);
                }
                if (isset($data['blurb'])) $update['blurb'] = trim($data['blurb']);
                if (isset($data['image'])) $update['image'] = trim($data['image']);
                if (isset($data['status'])) $update['status'] = trim($data['status']);

                $db->update('destinations', $update, 'id = :id', [':id' => $cleanId]);
                return true;

            default:
                return false;
        }
    }

    public static function deleteRecord(string $kind, string|int $id): bool
    {
        $db = Database::getInstance();
        $cleanId = (int)preg_replace('/[^0-9]/', '', (string)$id);
        if ($cleanId <= 0) {
            return false;
        }

        $tableMap = [
            'customer' => 'users',
            'flight' => 'flights',
            'hotel' => 'hotels',
            'loan' => 'loan_enquiries',
            'transaction' => 'transactions',
            'destination' => 'destinations',
        ];

        $table = $tableMap[$kind] ?? null;
        if (!$table) {
            return false;
        }

        $db->delete($table, 'id = :id', [':id' => $cleanId]);
        return true;
    }

    public static function createDestination(array $data): array
    {
        $db = Database::getInstance();
        $now = date('Y-m-d H:i:s');

        $cats = $data['categories'] ?? ['City Breaks'];
        $catStr = is_array($cats) ? implode(', ', $cats) : trim((string)$cats);

        $insertData = [
            'city' => trim($data['city'] ?? 'New City'),
            'country' => trim($data['country'] ?? 'Country'),
            'continent' => trim($data['continent'] ?? 'International'),
            'categories' => $catStr ?: 'City Breaks',
            'blurb' => trim($data['blurb'] ?? ''),
            'image' => trim($data['image'] ?? ''),
            'status' => 'Active',
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $id = $db->insert('destinations', $insertData);
        $insertData['id'] = 'd' . $id;
        $insertData['dbId'] = (int)$id;
        $insertData['categories'] = is_array($cats) ? $cats : array_map('trim', explode(',', $catStr));

        return $insertData;
    }
}
