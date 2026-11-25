<?php

namespace App\Services;

use App\Core\Database;

class RealtimeDataService
{
    protected static string $lastError = '';

    public static function lastError(): string
    {
        return self::$lastError;
    }

    public static function ensureUserSchema(): void
    {
        static $ready = false;
        if ($ready) {
            return;
        }

        $db = Database::getInstance();
        $hasStatus = false;

        if ($db->getDriver() === 'mysql') {
            $hasStatus = (bool)$db->getPdo()->query("SHOW COLUMNS FROM users LIKE 'status'")->fetch();
        } else {
            foreach ($db->select('PRAGMA table_info(users)') as $column) {
                if (($column['name'] ?? '') === 'status') {
                    $hasStatus = true;
                    break;
                }
            }
        }

        if (!$hasStatus) {
            $db->statement("ALTER TABLE users ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'Active'");
        }

        $ready = true;
    }

    protected static function normalizeStatus(?string $status): string
    {
        return strtolower(trim((string)$status)) === 'inactive' ? 'Inactive' : 'Active';
    }

    public static function getAdminData(): array
    {
        self::ensureUserSchema();
        self::ensureApiSchema();
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
                'status' => self::normalizeStatus($u['status'] ?? 'Active'),
                'tier' => $u['tier'] ?? 'Gold member',
                'passport' => $u['passport_number'] ?? '',
                'passportExpiry' => $u['passport_expiry'] ?? '',
            ];
        }

        $destRows = $db->select("SELECT * FROM destinations ORDER BY id ASC");
        $continentByCity = self::continentLookup($destRows);

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
                'time' => substr((string)$f['departure_time'], 0, 5),
                'arrival' => substr((string)($f['arrival_time'] ?? ''), 0, 5),
                'duration' => $f['duration'],
                'stops' => $f['stops'],
                'cabin' => $f['cabin'],
                'amount' => (float)$f['price'],
                'status' => $f['status'],
                'continent' => self::placeContinent((string)$f['destination'], $continentByCity),
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
                'continent' => self::placeContinent((string)$h['location'], $continentByCity),
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

        $apiProviders = self::apiProviderRows($db);
        $apiLogs = self::apiLogRows($db);

        return [
            'customers' => $customers,
            'flights' => $flights,
            'hotels' => $hotels,
            'loans' => $loans,
            'transactions' => $transactions,
            'destinations' => $destinations,
            'activities' => $activities,
            'monthly' => $monthly,
            'apiProviders' => $apiProviders,
            'apiLogs' => $apiLogs,
            'busBookings' => self::getBusBookings(50),
            'counts' => [
                'customers' => count($customers),
                'flights' => count($flights),
                'hotels' => count($hotels),
                'loans' => count($loans),
                'destinations' => count($destinations),
                'transactions' => count($transactions),
                'api' => count($apiProviders),
                'messages' => self::contactMessageCount(),
                'bus' => self::busBookingCount(),
            ],
        ];
    }

    public static function getCustomerData(int $userId = 1): array
    {
        self::ensureUserSchema();
        $db = Database::getInstance();
        $user = $db->selectOne("SELECT * FROM users WHERE id = :id LIMIT 1", [':id' => $userId]);

        if (!$user) {
            return [
                'customer' => [
                    'name' => '',
                    'firstName' => '',
                    'initials' => '',
                    'email' => '',
                    'phone' => '',
                    'country' => '',
                    'city' => '',
                    'memberSince' => '',
                    'tier' => '',
                    'status' => '',
                    'passport' => '',
                    'passportExpiry' => '',
                ],
                'flights' => [],
                'hotels' => [],
                'financing' => [],
                'payments' => [],
            ];
        }

        $custName = trim((string)($user['name'] ?: trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))));
        $firstName = (string)($user['first_name'] ?? '');
        $lastName = (string)($user['last_name'] ?? '');
        $initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));

        $customerObj = [
            'name' => $custName,
            'firstName' => $firstName,
            'initials' => $initials,
            'email' => $user['email'] ?? '',
            'phone' => $user['phone'] ?? '',
            'country' => $user['country'] ?? '',
            'city' => $user['city'] ?? '',
            'memberSince' => !empty($user['created_at']) ? date('F Y', strtotime($user['created_at'])) : '',
            'tier' => $user['tier'] ?? '',
            'status' => self::normalizeStatus($user['status'] ?? 'Active'),
            'passport' => $user['passport_number'] ?? '',
            'passportExpiry' => $user['passport_expiry'] ?? '',
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
                'arrival' => $f['arrival_time'] ?? '',
                'duration' => $f['duration'],
                'stops' => $f['stops'],
                'cabin' => $f['cabin'],
                'passengers' => $f['passengers'] ?? '',
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
                'rooms' => $h['rooms'] ?? '',
                'roomType' => $h['room_type'],
                'status' => $h['status'],
                'price' => (float)$h['price'],
            ];
        }

        // Customer financing
        $loanRows = $db->select("SELECT * FROM loan_enquiries WHERE user_id = :id ORDER BY submitted DESC, id DESC", [':id' => $userId]);
        $financing = [];
        foreach ($loanRows as $l) {
            $financing[] = [
                'id' => 'fin' . $l['id'],
                'dbId' => (int)$l['id'],
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

        self::$lastError = '';
        self::ensureUserSchema();
        $now = date('Y-m-d H:i:s');

        switch ($kind) {
            case 'customer':
                $update = ['updated_at' => $now];
                if (isset($data['firstName'])) $update['first_name'] = trim((string)$data['firstName']);
                if (isset($data['lastName'])) $update['last_name'] = trim((string)$data['lastName']);
                if (isset($data['firstName']) || isset($data['lastName'])) {
                    $existing = $db->selectOne('SELECT first_name, last_name FROM users WHERE id = :id', [':id' => $cleanId]);
                    $fn = array_key_exists('firstName', $data) ? trim((string)$data['firstName']) : ($existing['first_name'] ?? '');
                    $ln = array_key_exists('lastName', $data) ? trim((string)$data['lastName']) : ($existing['last_name'] ?? '');
                    if ($fn === '' || $ln === '') {
                        self::$lastError = 'First name and last name are required.';
                        return false;
                    }
                    $update['name'] = trim($fn . ' ' . $ln);
                }
                if (isset($data['email'])) {
                    $email = strtolower(trim((string)$data['email']));
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        self::$lastError = 'Enter a valid email address.';
                        return false;
                    }
                    $duplicate = $db->selectOne(
                        'SELECT id FROM users WHERE LOWER(email) = LOWER(:email) AND id != :id LIMIT 1',
                        [':email' => $email, ':id' => $cleanId]
                    );
                    if ($duplicate) {
                        self::$lastError = 'Another customer already uses that email address.';
                        return false;
                    }
                    $update['email'] = $email;
                }
                if (isset($data['phone'])) {
                    $phone = trim((string)$data['phone']);
                    $update['phone'] = ($phone === '' || $phone === '—') ? null : $phone;
                }
                if (isset($data['country'])) $update['country'] = trim((string)$data['country']);
                if (isset($data['city'])) $update['city'] = trim((string)$data['city']);
                if (isset($data['tier'])) $update['tier'] = trim((string)$data['tier']);
                if (isset($data['status'])) $update['status'] = self::normalizeStatus((string)$data['status']);
                if (isset($data['passport'])) {
                    $passport = trim((string)$data['passport']);
                    $update['passport_number'] = $passport !== '' ? $passport : null;
                }
                if (isset($data['passportExpiry']) && trim((string)$data['passportExpiry']) !== '') {
                    $update['passport_expiry'] = trim((string)$data['passportExpiry']);
                }

                $db->update('users', $update, 'id = :id', [':id' => $cleanId]);
                return true;

            case 'flight':
                $update = [];
                if (isset($data['airline'])) $update['airline'] = trim($data['airline']);
                if (isset($data['flightNo'])) $update['flight_no'] = trim($data['flightNo']);
                if (isset($data['from'])) $update['origin'] = trim($data['from']);
                if (isset($data['to'])) $update['destination'] = trim($data['to']);
                if (isset($data['date'])) $update['departure_date'] = $data['date'];
                if (isset($data['time'])) $update['departure_time'] = trim((string)$data['time']);
                if (isset($data['arrival'])) $update['arrival_time'] = trim((string)$data['arrival']);
                if (isset($data['duration'])) $update['duration'] = trim((string)$data['duration']);
                if (isset($data['stops'])) $update['stops'] = trim((string)$data['stops']);
                if (isset($data['cabin'])) $update['cabin'] = trim((string)$data['cabin']);
                if (isset($data['amount'])) $update['price'] = (float)$data['amount'];
                if (isset($data['status'])) $update['status'] = trim((string)$data['status']);
                if (isset($data['customerId'])) {
                    $userId = (int)preg_replace('/[^0-9]/', '', (string)$data['customerId']);
                    if ($userId > 0) {
                        $update['user_id'] = $userId;
                    }
                }

                if (!empty($update)) {
                    $db->update('flights', $update, 'id = :id', [':id' => $cleanId]);
                }
                return true;

            case 'hotel':
                $update = [];
                if (isset($data['property'])) $update['property'] = trim($data['property']);
                if (isset($data['location'])) $update['location'] = trim($data['location']);
                if (isset($data['checkIn'])) $update['checkin'] = $data['checkIn'];
                if (isset($data['checkOut'])) $update['checkout'] = $data['checkOut'];
                if (isset($data['nights'])) $update['nights'] = max(1, (int)$data['nights']);
                if (isset($data['guests'])) $update['guests'] = trim($data['guests']);
                if (isset($data['roomType'])) $update['room_type'] = trim($data['roomType']);
                if (isset($data['amount'])) $update['price'] = (float)$data['amount'];
                if (isset($data['status'])) $update['status'] = trim($data['status']);
                if (isset($data['customerId'])) {
                    $userId = (int)preg_replace('/[^0-9]/', '', (string)$data['customerId']);
                    if ($userId > 0) {
                        $update['user_id'] = $userId;
                    }
                }
                if (!empty($update['checkin']) && !empty($update['checkout'])) {
                    $stayNights = (int)round((strtotime((string)$update['checkout']) - strtotime((string)$update['checkin'])) / 86400);
                    if ($stayNights > 0) {
                        $update['nights'] = $stayNights;
                    }
                }

                if (!empty($update)) {
                    $db->update('hotels', $update, 'id = :id', [':id' => $cleanId]);
                }
                return true;

            case 'loan':
                $existingLoan = $db->selectOne('SELECT status FROM loan_enquiries WHERE id = :id LIMIT 1', [':id' => $cleanId]);
                $previousStatus = (string)($existingLoan['status'] ?? '');
                $update = [];
                if (isset($data['amount'])) $update['amount'] = (float)$data['amount'];
                if (isset($data['period'])) $update['period'] = trim($data['period']);
                if (isset($data['purpose'])) $update['purpose'] = trim($data['purpose']);
                if (isset($data['status'])) $update['status'] = trim($data['status']);
                if (isset($data['note'])) $update['note'] = trim($data['note']);
                if (isset($data['customerId'])) {
                    $userId = (int)preg_replace('/[^0-9]/', '', (string)$data['customerId']);
                    if ($userId > 0) {
                        $update['user_id'] = $userId;
                    }
                }

                if (!empty($update)) {
                    $db->update('loan_enquiries', $update, 'id = :id', [':id' => $cleanId]);
                }
                $newStatus = (string)($update['status'] ?? $previousStatus);
                if (strcasecmp($newStatus, 'Approved') === 0 && strcasecmp($previousStatus, 'Approved') !== 0) {
                    MailService::sendLoanApproved($cleanId);
                }
                return true;

            case 'transaction':
                $update = [];
                if (isset($data['related'])) $update['related_ref'] = trim($data['related']);
                if (isset($data['method'])) $update['method'] = trim($data['method']);
                if (isset($data['amount'])) $update['amount'] = (float)$data['amount'];
                if (isset($data['status'])) {
                    $status = trim((string)$data['status']);
                    if (in_array($status, ['Paid', 'Pending', 'Refunded', 'Failed'], true)) {
                        $update['status'] = $status;
                    }
                }
                if (isset($data['date']) && preg_match('/^\d{4}-\d{2}-\d{2}/', (string)$data['date'])) {
                    $update['transacted_at'] = substr((string)$data['date'], 0, 10);
                }
                if (isset($data['customerId'])) {
                    $userId = (int)preg_replace('/[^0-9]/', '', (string)$data['customerId']);
                    if ($userId > 0) {
                        $update['user_id'] = $userId;
                    }
                }

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

            case 'bus':
            case 'bus_booking':
                $update = ['updated_at' => $now];
                if (isset($data['status'])) $update['status'] = trim($data['status']);
                if (isset($data['customer_name'])) $update['customer_name'] = trim($data['customer_name']);
                if (isset($data['customer_phone'])) $update['customer_phone'] = trim($data['customer_phone']);
                if (isset($data['departure_date'])) $update['departure_date'] = trim($data['departure_date']);
                if (isset($data['return_date'])) $update['return_date'] = !empty($data['return_date']) ? trim($data['return_date']) : null;
                if (isset($data['passengers'])) $update['passengers'] = (int)$data['passengers'];
                if (isset($data['coach_class'])) $update['coach_class'] = trim($data['coach_class']);
                if (isset($data['notes'])) $update['notes'] = trim($data['notes']);

                $db->update('bus_bookings', $update, 'id = :id', [':id' => $cleanId]);
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
            'bus' => 'bus_bookings',
            'bus_booking' => 'bus_bookings',
        ];

        $table = $tableMap[$kind] ?? null;
        if (!$table) {
            return false;
        }

        if ($kind === 'customer') {
            self::detachCustomerRecords($db, $cleanId);
        }

        $db->delete($table, 'id = :id', [':id' => $cleanId]);
        return true;
    }

    protected static function detachCustomerRecords(Database $db, int $userId): void
    {
        foreach (['flights', 'hotels', 'loan_enquiries', 'transactions'] as $table) {
            try {
                $db->update($table, ['user_id' => null], 'user_id = :id', [':id' => $userId]);
            } catch (\Throwable $e) {
                // Related table may not have a user link yet.
            }
        }
    }

    public static function createCustomer(array $data): array
    {
        self::$lastError = '';
        self::ensureUserSchema();
        $db = Database::getInstance();

        $first = trim((string)($data['firstName'] ?? ''));
        $last = trim((string)($data['lastName'] ?? ''));
        $email = strtolower(trim((string)($data['email'] ?? '')));

        if ($first === '' || $last === '') {
            self::$lastError = 'First name and last name are required.';
            return ['success' => false, 'error' => self::$lastError];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            self::$lastError = 'Enter a valid email address.';
            return ['success' => false, 'error' => self::$lastError];
        }

        $duplicate = $db->selectOne(
            'SELECT id FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1',
            [':email' => $email]
        );
        if ($duplicate) {
            self::$lastError = 'Another customer already uses that email address.';
            return ['success' => false, 'error' => self::$lastError];
        }

        $phone = trim((string)($data['phone'] ?? ''));
        $now = date('Y-m-d H:i:s');
        $insert = [
            'name' => trim($first . ' ' . $last),
            'first_name' => $first,
            'last_name' => $last,
            'email' => $email,
            'phone' => ($phone === '' || $phone === '—') ? null : $phone,
            'country' => trim((string)($data['country'] ?? '')) ?: 'Nigeria',
            'city' => trim((string)($data['city'] ?? '')) ?: 'Lagos',
            'role' => 'customer',
            'tier' => trim((string)($data['tier'] ?? '')) ?: 'Standard member',
            'status' => self::normalizeStatus($data['status'] ?? 'Active'),
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $id = (int)$db->insert('users', $insert);

        return [
            'success' => true,
            'message' => 'Customer added and synced with the customer portal.',
            'customer' => [
                'id' => 'c' . $id,
                'dbId' => $id,
                'firstName' => $first,
                'lastName' => $last,
                'email' => $email,
                'status' => $insert['status'],
            ],
        ];
    }

    public static function getPublicFlights(): array
    {
        $db = Database::getInstance();
        $rows = $db->select("SELECT * FROM flights WHERE status = 'Confirmed' ORDER BY departure_date ASC, id ASC");
        $flights = [];

        foreach ($rows as $flight) {
            $flights[] = [
                'id' => 'f' . $flight['id'],
                'airline' => $flight['airline'],
                'flightNo' => $flight['flight_no'],
                'depTime' => substr((string)$flight['departure_time'], 0, 5),
                'duration' => self::durationToMinutes((string)$flight['duration']),
                'stops' => self::stopsToCount((string)$flight['stops']),
                'price' => (float)$flight['price'],
                'aircraft' => $flight['cabin'] ?: '',
                'baggage' => '',
                'fareType' => $flight['cabin'] ?: '',
                'from' => $flight['origin'],
                'to' => $flight['destination'],
                'date' => $flight['departure_date'],
                'cabin' => $flight['cabin'],
            ];
        }

        return $flights;
    }

    public static function bookFlightFromSearch(array $data): array
    {
        self::$lastError = '';
        self::ensureUserSchema();
        $db = Database::getInstance();
        $sourceId = (int)preg_replace('/[^0-9]/', '', (string)($data['flightId'] ?? ''));
        if ($sourceId <= 0) {
            self::$lastError = 'Choose a flight before continuing.';
            return ['success' => false, 'error' => self::$lastError];
        }

        $source = $db->selectOne('SELECT * FROM flights WHERE id = :id LIMIT 1', [':id' => $sourceId]);
        if (!$source) {
            self::$lastError = 'That flight is no longer available.';
            return ['success' => false, 'error' => self::$lastError];
        }

        $from = trim((string)($data['from'] ?? ''));
        $to = trim((string)($data['to'] ?? ''));
        $depart = trim((string)($data['depart'] ?? ''));
        if ($from === '' || $to === '' || $depart === '') {
            self::$lastError = 'Departure city, destination, and date are required.';
            return ['success' => false, 'error' => self::$lastError];
        }

        $customer = $db->selectOne('SELECT id FROM users WHERE LOWER(COALESCE(status, \'active\')) != \'inactive\' ORDER BY id ASC LIMIT 1');
        $now = date('Y-m-d H:i:s');
        $insert = [
            'ref' => self::nextFlightRef($db),
            'airline' => $source['airline'],
            'flight_no' => $source['flight_no'],
            'origin' => $from,
            'destination' => $to,
            'departure_date' => $depart,
            'departure_time' => $source['departure_time'],
            'arrival_time' => $source['arrival_time'] ?: $source['departure_time'],
            'duration' => $source['duration'],
            'stops' => $source['stops'],
            'cabin' => trim((string)($data['cabin'] ?? '')) ?: ($source['cabin'] ?: 'Economy'),
            'price' => $source['price'],
            'status' => 'Pending',
            'user_id' => $customer['id'] ?? null,
            'created_at' => $now,
        ];

        $id = (int)$db->insert('flights', $insert);

        return [
            'success' => true,
            'message' => 'Flight selection saved. It is now in the admin flight list.',
            'booking' => [
                'id' => 'f' . $id,
                'ref' => $insert['ref'],
                'status' => 'Pending',
                'from' => $from,
                'to' => $to,
                'date' => $depart,
            ],
        ];
    }

    public static function createFlight(array $data): array
    {
        self::$lastError = '';
        $db = Database::getInstance();

        $airline = trim((string)($data['airline'] ?? ''));
        $from = trim((string)($data['from'] ?? ''));
        $to = trim((string)($data['to'] ?? ''));
        $date = trim((string)($data['date'] ?? ''));
        $amount = (float)($data['amount'] ?? 0);

        if ($airline === '' || $from === '' || $to === '' || $date === '' || $amount <= 0) {
            self::$lastError = 'Airline, route, departure date, and amount are required.';
            return ['success' => false, 'error' => self::$lastError];
        }

        $customerId = (int)preg_replace('/[^0-9]/', '', (string)($data['customerId'] ?? ''));
        $now = date('Y-m-d H:i:s');
        $insert = [
            'ref' => self::nextFlightRef($db),
            'airline' => $airline,
            'flight_no' => trim((string)($data['flightNo'] ?? '')) ?: 'CJ 100',
            'origin' => $from,
            'destination' => $to,
            'departure_date' => $date,
            'departure_time' => trim((string)($data['time'] ?? '')) ?: '09:00',
            'arrival_time' => trim((string)($data['arrival'] ?? '')) ?: '12:00',
            'duration' => trim((string)($data['duration'] ?? '')) ?: '3h 00m',
            'stops' => trim((string)($data['stops'] ?? '')) ?: 'Direct',
            'cabin' => trim((string)($data['cabin'] ?? '')) ?: 'Economy',
            'price' => $amount,
            'status' => trim((string)($data['status'] ?? '')) ?: 'Confirmed',
            'user_id' => $customerId > 0 ? $customerId : null,
            'created_at' => $now,
        ];

        $id = (int)$db->insert('flights', $insert);

        return [
            'success' => true,
            'message' => 'Flight saved. Confirmed flights appear on the public search immediately.',
            'flight' => [
                'id' => 'f' . $id,
                'ref' => $insert['ref'],
                'status' => $insert['status'],
            ],
        ];
    }

    protected static function nextFlightRef(Database $db): string
    {
        for ($attempt = 0; $attempt < 8; $attempt++) {
            $ref = 'TBF-' . random_int(3000, 9999);
            $existing = $db->selectOne('SELECT id FROM flights WHERE ref = :ref LIMIT 1', [':ref' => $ref]);
            if (!$existing) {
                return $ref;
            }
        }

        return 'TBF-' . date('His');
    }

    public static function getPublicHotels(): array
    {
        $db = Database::getInstance();
        $rows = $db->select("SELECT * FROM hotels WHERE status = 'Confirmed' ORDER BY checkin ASC, id ASC");
        $hotels = [];

        foreach ($rows as $hotel) {
            $nights = max(1, (int)$hotel['nights']);
            $nightly = round(((float)$hotel['price']) / $nights);
            $roomType = trim((string)$hotel['room_type']);
            $location = (string)$hotel['location'];
            $property = (string)$hotel['property'];

            $hotels[] = [
                'id' => 'h' . $hotel['id'],
                'name' => $property,
                'city' => self::hotelCity($location),
                'location' => $location,
                'stars' => 0,
                'rating' => null,
                'reviews' => 0,
                'type' => 'Hotel',
                'description' => trim($property . ($location !== '' ? ' in ' . $location : '') . '.'),
                'pricePerNight' => $nightly,
                'image' => '',
                'images' => [],
                'amenities' => [],
                'rooms' => [[
                    'type' => $roomType,
                    'price' => $nightly,
                    'capacity' => 0,
                    'bed' => '',
                    'desc' => $roomType,
                ]],
                'policies' => [],
                'live' => true,
            ];
        }

        return $hotels;
    }

    public static function bookHotelFromSearch(array $data): array
    {
        self::$lastError = '';
        self::ensureUserSchema();
        $db = Database::getInstance();
        $sourceId = (int)preg_replace('/[^0-9]/', '', (string)($data['hotelId'] ?? ''));
        if ($sourceId <= 0) {
            self::$lastError = 'Choose a hotel before continuing.';
            return ['success' => false, 'error' => self::$lastError];
        }

        $source = $db->selectOne('SELECT * FROM hotels WHERE id = :id LIMIT 1', [':id' => $sourceId]);
        if (!$source) {
            self::$lastError = 'That hotel is no longer available.';
            return ['success' => false, 'error' => self::$lastError];
        }

        $checkIn = trim((string)($data['checkin'] ?? ''));
        $checkOut = trim((string)($data['checkout'] ?? ''));
        $nights = (int)round((strtotime($checkOut) - strtotime($checkIn)) / 86400);
        if ($checkIn === '' || $checkOut === '' || $nights < 1) {
            self::$lastError = 'Check-in and check-out dates are required.';
            return ['success' => false, 'error' => self::$lastError];
        }

        $rooms = max(1, (int)($data['rooms'] ?? 1));
        $guestCount = max(1, (int)($data['guests'] ?? 2));
        $nightly = (float)($data['nightly'] ?? 0);
        if ($nightly <= 0) {
            $nightly = ((float)$source['price']) / max(1, (int)$source['nights']);
        }

        $customer = $db->selectOne('SELECT id FROM users WHERE LOWER(COALESCE(status, \'active\')) != \'inactive\' ORDER BY id ASC LIMIT 1');
        $now = date('Y-m-d H:i:s');
        $insert = [
            'ref' => self::nextHotelRef($db),
            'property' => $source['property'],
            'location' => $source['location'],
            'checkin' => $checkIn,
            'checkout' => $checkOut,
            'nights' => $nights,
            'guests' => $guestCount === 1 ? '1 guest' : ($guestCount . ' guests'),
            'room_type' => trim((string)($data['roomType'] ?? '')) ?: ($source['room_type'] ?: 'Deluxe Room'),
            'price' => round($nightly * $nights * $rooms),
            'status' => 'Pending',
            'user_id' => $customer['id'] ?? null,
            'created_at' => $now,
        ];

        $id = (int)$db->insert('hotels', $insert);

        return [
            'success' => true,
            'message' => 'Hotel selection saved. It is now in the admin hotel list.',
            'booking' => [
                'id' => 'h' . $id,
                'ref' => $insert['ref'],
                'status' => 'Pending',
                'property' => $insert['property'],
                'checkIn' => $checkIn,
                'checkOut' => $checkOut,
            ],
        ];
    }

    public static function createHotel(array $data): array
    {
        self::$lastError = '';
        $db = Database::getInstance();

        $property = trim((string)($data['property'] ?? ''));
        $location = trim((string)($data['location'] ?? ''));
        $checkIn = trim((string)($data['checkIn'] ?? ''));
        $checkOut = trim((string)($data['checkOut'] ?? ''));
        $amount = (float)($data['amount'] ?? 0);
        $nights = max(0, (int)($data['nights'] ?? 0));

        if ($checkIn !== '' && $checkOut !== '') {
            $stayNights = (int)round((strtotime($checkOut) - strtotime($checkIn)) / 86400);
            if ($stayNights > 0) {
                $nights = $stayNights;
            }
        }
        if ($nights < 1) {
            $nights = 1;
        }
        if ($checkOut === '' && $checkIn !== '') {
            $checkOut = date('Y-m-d', strtotime($checkIn . ' +' . $nights . ' days'));
        }

        if ($property === '' || $location === '' || $checkIn === '' || $amount <= 0) {
            self::$lastError = 'Property, location, check-in date, and amount are required.';
            return ['success' => false, 'error' => self::$lastError];
        }

        $guestCount = max(1, (int)preg_replace('/[^0-9]/', '', (string)($data['guests'] ?? '2')));
        $customerId = (int)preg_replace('/[^0-9]/', '', (string)($data['customerId'] ?? ''));
        $now = date('Y-m-d H:i:s');
        $insert = [
            'ref' => self::nextHotelRef($db),
            'property' => $property,
            'location' => $location,
            'checkin' => $checkIn,
            'checkout' => $checkOut,
            'nights' => $nights,
            'guests' => $guestCount === 1 ? '1 guest' : ($guestCount . ' guests'),
            'room_type' => trim((string)($data['roomType'] ?? '')) ?: 'Deluxe Room',
            'price' => $amount,
            'status' => trim((string)($data['status'] ?? '')) ?: 'Confirmed',
            'user_id' => $customerId > 0 ? $customerId : null,
            'created_at' => $now,
        ];

        $id = (int)$db->insert('hotels', $insert);

        return [
            'success' => true,
            'message' => 'Hotel saved. Confirmed stays appear on the public search immediately.',
            'hotel' => [
                'id' => 'h' . $id,
                'ref' => $insert['ref'],
                'status' => $insert['status'],
            ],
        ];
    }

    public static function createLoan(array $data): array
    {
        self::$lastError = '';
        $db = Database::getInstance();

        $amount = (float)($data['amount'] ?? 0);
        $period = trim((string)($data['period'] ?? ''));
        $purpose = trim((string)($data['purpose'] ?? ''));
        if ($amount <= 0 || $period === '' || $purpose === '') {
            self::$lastError = 'Amount, repayment period, and purpose are required.';
            return ['success' => false, 'error' => self::$lastError];
        }

        $customerId = (int)preg_replace('/[^0-9]/', '', (string)($data['customerId'] ?? ''));
        $now = date('Y-m-d H:i:s');
        $insert = [
            'ref' => self::nextLoanRef($db),
            'user_id' => $customerId > 0 ? $customerId : null,
            'amount' => $amount,
            'period' => $period,
            'purpose' => $purpose,
            'status' => trim((string)($data['status'] ?? '')) ?: 'In review',
            'note' => trim((string)($data['note'] ?? '')),
            'submitted' => date('Y-m-d'),
            'created_at' => $now,
        ];

        $id = (int)$db->insert('loan_enquiries', $insert);
        $mail = strcasecmp($insert['status'], 'Approved') === 0
            ? MailService::sendLoanApproved($id)
            : MailService::sendLoanPending($id);

        return [
            'success' => true,
            'message' => 'Financing enquiry saved.',
            'emailSent' => !empty($mail['success']),
            'loan' => [
                'id' => 'l' . $id,
                'ref' => $insert['ref'],
                'status' => $insert['status'],
            ],
        ];
    }

    public static function createTransaction(array $data): array
    {
        self::$lastError = '';
        $db = Database::getInstance();

        $amount = (float)($data['amount'] ?? 0);
        $method = trim((string)($data['method'] ?? ''));
        $customerId = (int)preg_replace('/[^0-9]/', '', (string)($data['customerId'] ?? ''));
        $status = trim((string)($data['status'] ?? 'Paid'));
        $allowed = ['Paid', 'Pending', 'Refunded', 'Failed'];
        if (!in_array($status, $allowed, true)) {
            $status = 'Paid';
        }

        if ($amount <= 0 || $method === '' || $customerId <= 0) {
            self::$lastError = 'Customer, amount, and payment method are required.';
            return ['success' => false, 'error' => self::$lastError];
        }

        $customer = $db->selectOne('SELECT id FROM users WHERE id = :id LIMIT 1', [':id' => $customerId]);
        if (!$customer) {
            self::$lastError = 'Choose a customer from the list.';
            return ['success' => false, 'error' => self::$lastError];
        }

        $date = substr(trim((string)($data['date'] ?? '')), 0, 10);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }

        $now = date('Y-m-d H:i:s');
        $insert = [
            'ref' => self::nextPaymentRef($db),
            'user_id' => $customerId,
            'related_ref' => trim((string)($data['related'] ?? '')) ?: null,
            'method' => $method,
            'amount' => $amount,
            'status' => $status,
            'transacted_at' => $date,
            'created_at' => $now,
        ];

        $id = (int)$db->insert('transactions', $insert);

        return [
            'success' => true,
            'message' => 'Transaction ' . $insert['ref'] . ' saved. It appears on this list and on that customer’s payments page.',
            'transaction' => [
                'id' => 't' . $id,
                'ref' => $insert['ref'],
                'status' => $status,
            ],
        ];
    }

    protected static function nextPaymentRef(Database $db): string
    {
        $latest = $db->selectOne("SELECT ref FROM transactions WHERE ref LIKE 'PAY-%' ORDER BY id DESC LIMIT 1");
        $number = 9000;
        if ($latest && preg_match('/PAY-(\d+)/', (string)$latest['ref'], $matches)) {
            $number = (int)$matches[1];
        }

        for ($step = 1; $step <= 20; $step++) {
            $ref = 'PAY-' . ($number + $step);
            $existing = $db->selectOne('SELECT id FROM transactions WHERE ref = :ref LIMIT 1', [':ref' => $ref]);
            if (!$existing) {
                return $ref;
            }
        }

        return 'PAY-' . date('His');
    }

    public static function applyLoanFromFinancing(array $data): array
    {
        self::$lastError = '';
        self::ensureUserSchema();
        $db = Database::getInstance();

        $name = trim((string)($data['fullName'] ?? ''));
        $email = strtolower(trim((string)($data['email'] ?? '')));
        $phone = trim((string)($data['phone'] ?? ''));
        $country = trim((string)($data['country'] ?? ''));
        $amount = (float)($data['amount'] ?? $data['travelCost'] ?? 0);
        $purpose = trim((string)($data['purpose'] ?? ''));
        $period = trim((string)($data['period'] ?? ''));
        $customerNotes = trim((string)($data['notes'] ?? $data['note'] ?? ''));

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $amount <= 0 || $purpose === '' || $period === '') {
            self::$lastError = 'Name, email, travel cost, purpose, and repayment period are required.';
            return ['success' => false, 'error' => self::$lastError];
        }

        $explicitUserId = !empty($data['userId']) ? (int)$data['userId'] : (!empty($data['user_id']) ? (int)$data['user_id'] : null);
        $userId = self::customerIdForEnquiry($db, $name, $email, $phone, $country, $explicitUserId);

        // Indicative calculation: 15% APR standard illustrative model
        preg_match('/\d+/', $period, $m);
        $months = !empty($m[0]) ? (int)$m[0] : 6;
        $annualRate = 15.0;
        $monthlyRate = ($annualRate / 100) / 12;
        $monthlyInstalment = $months > 0 ? ($amount * $monthlyRate) / (1 - pow(1 + $monthlyRate, -$months)) : $amount;
        $totalRepayable = $monthlyInstalment * $months;

        $note = sprintf(
            'Enquiry from %s · %s%s%s. Plan: ₦%s over %s (Est. ₦%s/mo · Total: ₦%s)%s',
            $name,
            $email,
            $phone !== '' ? ' · ' . $phone : '',
            $country !== '' ? ' · ' . $country : '',
            number_format($amount),
            $period,
            number_format($monthlyInstalment),
            number_format($totalRepayable),
            $customerNotes !== '' ? ' | Note: ' . $customerNotes : ''
        );

        $now = date('Y-m-d H:i:s');
        $insert = [
            'ref' => self::nextLoanRef($db),
            'user_id' => $userId,
            'amount' => $amount,
            'period' => $period,
            'purpose' => $purpose,
            'status' => 'In review',
            'note' => $note,
            'submitted' => date('Y-m-d'),
            'created_at' => $now,
        ];

        $id = (int)$db->insert('loan_enquiries', $insert);
        $mail = MailService::sendLoanPending($id);

        return [
            'success' => true,
            'message' => 'Financing enquiry saved successfully. It is now in review and recorded in your account.',
            'emailSent' => !empty($mail['success']),
            'loan' => [
                'id' => 'fin' . $id,
                'dbId' => $id,
                'ref' => $insert['ref'],
                'amount' => $amount,
                'period' => $period,
                'purpose' => $purpose,
                'status' => 'In review',
                'monthly_installment' => round($monthlyInstalment, 2),
                'total_repayable' => round($totalRepayable, 2),
                'submitted' => $insert['submitted'],
                'note' => $note,
            ],
        ];
    }

    protected static function customerIdForEnquiry(Database $db, string $name, string $email, string $phone, string $country, ?int $explicitUserId = null): ?int
    {
        if ($explicitUserId !== null && $explicitUserId > 0) {
            $check = $db->selectOne('SELECT id FROM users WHERE id = :id LIMIT 1', [':id' => $explicitUserId]);
            if ($check) {
                return (int)$check['id'];
            }
        }

        $existing = $db->selectOne(
            'SELECT id FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1',
            [':email' => $email]
        );
        if ($existing) {
            return (int)$existing['id'];
        }

        $parts = preg_split('/\s+/', $name) ?: [];
        $first = $parts[0] ?? 'Guest';
        $last = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : 'Traveller';
        $now = date('Y-m-d H:i:s');

        return (int)$db->insert('users', [
            'name' => trim($first . ' ' . $last),
            'first_name' => $first,
            'last_name' => $last,
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'country' => $country !== '' ? $country : 'Nigeria',
            'city' => 'Lagos',
            'role' => 'customer',
            'tier' => 'Standard member',
            'status' => 'Active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    protected static function nextLoanRef(Database $db): string
    {
        for ($attempt = 0; $attempt < 8; $attempt++) {
            $ref = 'TBLN-' . str_pad((string)random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
            $existing = $db->selectOne('SELECT id FROM loan_enquiries WHERE ref = :ref LIMIT 1', [':ref' => $ref]);
            if (!$existing) {
                return $ref;
            }
        }

        return 'TBLN-' . date('His');
    }

    protected static function nextHotelRef(Database $db): string
    {
        for ($attempt = 0; $attempt < 8; $attempt++) {
            $ref = 'TBH-' . random_int(2000, 9999);
            $existing = $db->selectOne('SELECT id FROM hotels WHERE ref = :ref LIMIT 1', [':ref' => $ref]);
            if (!$existing) {
                return $ref;
            }
        }

        return 'TBH-' . date('His');
    }

    protected static function hotelCity(string $location): string
    {
        foreach (['Lagos', 'Abuja', 'London', 'Dubai', 'Paris', 'Doha', 'Istanbul', 'Cape Town', 'New York', 'Accra', 'Nairobi'] as $city) {
            if (stripos($location, $city) !== false) {
                return $city;
            }
        }

        $parts = array_values(array_filter(array_map('trim', explode(',', $location))));
        if (count($parts) >= 2) {
            return $parts[count($parts) - 2];
        }

        return $parts[0] ?? $location;
    }

    protected static function durationToMinutes(string $duration): int
    {
        if (preg_match('/(\d+)\s*h(?:\s*(\d+)\s*m)?/i', $duration, $matches)) {
            return ((int)$matches[1] * 60) + (int)($matches[2] ?? 0);
        }
        if (is_numeric($duration)) {
            return (int)$duration;
        }

        return 0;
    }

    protected static function stopsToCount(string $stops): int
    {
        if (stripos($stops, 'direct') !== false || trim($stops) === '' || trim($stops) === '0') {
            return 0;
        }
        if (preg_match('/(\d+)/', $stops, $matches)) {
            return (int)$matches[1];
        }

        return 0;
    }

    public static function getPublicDestinations(): array
    {
        $rows = Database::getInstance()->select("SELECT * FROM destinations WHERE status = 'Active' ORDER BY city ASC, id ASC");
        $destinations = [];

        foreach ($rows as $index => $destination) {
            $categories = self::destinationCategories($destination['categories'] ?? '');
            $city = (string)$destination['city'];
            $country = (string)$destination['country'];
            $blurb = trim((string)($destination['blurb'] ?? ''));
            if ($blurb === '') {
                $blurb = $city . ', ' . $country . '.';
            }
            $image = trim((string)($destination['image'] ?? ''));
            if ($image === '') {
                $image = 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?auto=format&fit=crop&w=900&q=80';
            }

            $destinations[] = [
                'id' => 'd' . $destination['id'],
                'city' => $city,
                'country' => $country,
                'continent' => $destination['continent'],
                'categories' => $categories,
                'popular' => $index < 6,
                'image' => $image,
                'blurb' => $blurb,
                'description' => $blurb,
                'highlights' => $categories,
                'bestTime' => 'Year round',
                'idealLength' => '4 – 7 days',
                'flightCode' => $city,
            ];
        }

        return $destinations;
    }

    public static function createDestination(array $data): array
    {
        self::$lastError = '';
        $db = Database::getInstance();

        $city = trim((string)($data['city'] ?? ''));
        $country = trim((string)($data['country'] ?? ''));
        $continent = trim((string)($data['continent'] ?? ''));
        if ($city === '' || $country === '' || $continent === '') {
            self::$lastError = 'City, country, and continent are required.';
            return ['success' => false, 'error' => self::$lastError];
        }

        $categories = self::destinationCategories($data['categories'] ?? 'City Breaks');
        $now = date('Y-m-d H:i:s');
        $insertData = [
            'city' => $city,
            'country' => $country,
            'continent' => $continent,
            'categories' => implode(', ', $categories),
            'blurb' => trim((string)($data['blurb'] ?? '')),
            'image' => trim((string)($data['image'] ?? '')),
            'status' => 'Active',
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $id = (int)$db->insert('destinations', $insertData);

        return [
            'success' => true,
            'message' => 'Destination saved. It now appears on the public destinations page.',
            'destination' => [
                'id' => 'd' . $id,
                'city' => $city,
                'country' => $country,
                'status' => 'Active',
            ],
        ];
    }

    protected static function destinationCategories(mixed $categories): array
    {
        if (is_array($categories)) {
            $items = $categories;
        } else {
            $items = explode(',', (string)$categories);
        }

        $clean = [];
        foreach ($items as $item) {
            $item = trim((string)$item);
            if ($item !== '' && !in_array($item, $clean, true)) {
                $clean[] = $item;
            }
        }

        return $clean ?: ['City Breaks'];
    }

    public static function ensureApiSchema(): void
    {
        static $ready = false;
        if ($ready) {
            return;
        }

        $db = Database::getInstance();
        if ($db->getDriver() !== 'mysql') {
            $ready = true;
            return;
        }

        $db->statement("CREATE TABLE IF NOT EXISTS api_providers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            category VARCHAR(20) NOT NULL,
            base_url VARCHAR(255) NOT NULL,
            environment VARCHAR(20) NOT NULL DEFAULT 'Sandbox',
            api_key VARCHAR(255) NOT NULL,
            api_secret VARCHAR(255) NULL,
            enabled TINYINT(1) NOT NULL DEFAULT 1,
            status VARCHAR(20) NOT NULL DEFAULT 'Untested',
            last_test_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->statement("CREATE TABLE IF NOT EXISTS api_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            provider_name VARCHAR(120) NOT NULL,
            service VARCHAR(20) NOT NULL,
            activity VARCHAR(180) NOT NULL,
            status VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $ready = true;
    }

    public static function createApiProvider(array $data): array
    {
        self::$lastError = '';
        self::ensureApiSchema();
        $fields = self::providerInput($data, true);
        if ($fields === null) {
            return ['success' => false, 'error' => self::$lastError];
        }

        $now = date('Y-m-d H:i:s');
        $id = (int)Database::getInstance()->insert('api_providers', [
            'name' => $fields['name'],
            'category' => $fields['category'],
            'base_url' => $fields['baseUrl'],
            'environment' => $fields['environment'],
            'api_key' => $fields['apiKey'],
            'api_secret' => $fields['apiSecret'] !== '' ? $fields['apiSecret'] : null,
            'enabled' => $fields['enabled'],
            'status' => 'Untested',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        self::writeApiLog($fields['name'], $fields['category'], 'Provider added', 'Success');

        return [
            'success' => true,
            'message' => $fields['name'] . ' saved. Test the connection when you are ready.',
            'provider' => ['id' => 'p' . $id, 'name' => $fields['name']],
        ];
    }

    public static function updateApiProvider(string|int $id, array $data): array
    {
        self::$lastError = '';
        self::ensureApiSchema();
        $db = Database::getInstance();
        $cleanId = self::providerId($id);
        $existing = $cleanId > 0
            ? $db->selectOne('SELECT * FROM api_providers WHERE id = :id LIMIT 1', [':id' => $cleanId])
            : null;
        if (!$existing) {
            self::$lastError = 'That provider no longer exists.';
            return ['success' => false, 'error' => self::$lastError];
        }

        $fields = self::providerInput($data, false);
        if ($fields === null) {
            return ['success' => false, 'error' => self::$lastError];
        }

        $update = [
            'name' => $fields['name'],
            'category' => $fields['category'],
            'base_url' => $fields['baseUrl'],
            'environment' => $fields['environment'],
            'enabled' => $fields['enabled'],
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($fields['apiKey'] !== '') {
            $update['api_key'] = $fields['apiKey'];
        }
        if ($fields['apiSecret'] !== '') {
            $update['api_secret'] = $fields['apiSecret'];
        }

        $db->update('api_providers', $update, 'id = :id', [':id' => $cleanId]);
        self::writeApiLog($fields['name'], $fields['category'], 'Provider updated', 'Success');

        return [
            'success' => true,
            'message' => $fields['name'] . ' updated.',
            'provider' => ['id' => 'p' . $cleanId, 'name' => $fields['name']],
        ];
    }

    public static function deleteApiProvider(string|int $id): array
    {
        self::$lastError = '';
        self::ensureApiSchema();
        $db = Database::getInstance();
        $cleanId = self::providerId($id);
        $existing = $cleanId > 0
            ? $db->selectOne('SELECT name, category FROM api_providers WHERE id = :id LIMIT 1', [':id' => $cleanId])
            : null;
        if (!$existing) {
            self::$lastError = 'That provider no longer exists.';
            return ['success' => false, 'error' => self::$lastError];
        }

        $db->delete('api_providers', 'id = :id', [':id' => $cleanId]);
        self::writeApiLog($existing['name'], $existing['category'], 'Provider removed', 'Success');

        return [
            'success' => true,
            'message' => $existing['name'] . ' removed.',
        ];
    }

    public static function testApiProvider(string|int $id): array
    {
        self::$lastError = '';
        self::ensureApiSchema();
        $db = Database::getInstance();
        $cleanId = self::providerId($id);
        $existing = $cleanId > 0
            ? $db->selectOne('SELECT id, name, category, base_url FROM api_providers WHERE id = :id LIMIT 1', [':id' => $cleanId])
            : null;
        if (!$existing) {
            self::$lastError = 'That provider no longer exists.';
            return ['success' => false, 'error' => self::$lastError];
        }

        $probe = self::probeProviderUrl((string)$existing['base_url']);
        $now = date('Y-m-d H:i:s');
        $db->update('api_providers', [
            'status' => $probe['ok'] ? 'Connected' : 'Error',
            'last_test_at' => $now,
            'updated_at' => $now,
        ], 'id = :id', [':id' => $cleanId]);

        $activity = 'Connection test · ' . $probe['detail'];
        self::writeApiLog(
            $existing['name'],
            $existing['category'],
            $activity,
            $probe['ok'] ? 'Success' : 'Failed'
        );

        return [
            'success' => true,
            'connected' => $probe['ok'],
            'message' => $existing['name'] . ': ' . $probe['detail'],
        ];
    }

    protected static function apiProviderRows(Database $db): array
    {
        $rows = $db->select('SELECT id, name, category, base_url, environment, api_key, api_secret, enabled, status, last_test_at FROM api_providers ORDER BY name ASC, id ASC');
        $providers = [];
        foreach ($rows as $row) {
            $providers[] = [
                'id' => 'p' . $row['id'],
                'name' => $row['name'],
                'category' => $row['category'],
                'baseUrl' => $row['base_url'],
                'env' => $row['environment'],
                'apiKeyMasked' => self::maskCredential((string)($row['api_key'] ?? '')),
                'secretMasked' => self::maskCredential((string)($row['api_secret'] ?? '')),
                'enabled' => (int)$row['enabled'] === 1,
                'status' => $row['status'] ?: 'Untested',
                'lastTest' => !empty($row['last_test_at']) ? date('c', strtotime((string)$row['last_test_at'])) : null,
            ];
        }

        return $providers;
    }

    protected static function apiLogRows(Database $db): array
    {
        $rows = $db->select('SELECT provider_name, service, activity, status, created_at FROM api_logs ORDER BY id DESC LIMIT 60');
        $logs = [];
        foreach ($rows as $row) {
            $logs[] = [
                'ts' => date('c', strtotime((string)$row['created_at'])),
                'provider' => $row['provider_name'],
                'service' => $row['service'],
                'activity' => $row['activity'],
                'status' => $row['status'],
            ];
        }

        return $logs;
    }

    protected static function providerInput(array $data, bool $creating): ?array
    {
        $name = trim((string)($data['name'] ?? ''));
        $category = trim((string)($data['category'] ?? ''));
        $baseUrl = trim((string)($data['baseUrl'] ?? $data['base_url'] ?? ''));
        $environment = trim((string)($data['env'] ?? $data['environment'] ?? 'Sandbox'));
        $apiKey = trim((string)($data['apiKey'] ?? ''));
        $apiSecret = trim((string)($data['apiSecret'] ?? ''));
        $enabled = filter_var($data['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

        if ($name === '' || !in_array($category, ['Flight', 'Hotel'], true)) {
            self::$lastError = 'Provider name and service are required.';
            return null;
        }
        if (!self::isPublicHttpsUrl($baseUrl)) {
            self::$lastError = 'Enter a public HTTPS address for this provider.';
            return null;
        }
        if (!in_array($environment, ['Sandbox', 'Production'], true)) {
            $environment = 'Sandbox';
        }
        if ($creating && $apiKey === '') {
            self::$lastError = 'An API key is required for a new provider.';
            return null;
        }
        if (strlen($apiKey) > 255 || strlen($apiSecret) > 255) {
            self::$lastError = 'The key or secret is too long to store.';
            return null;
        }

        return [
            'name' => mb_substr($name, 0, 120),
            'category' => $category,
            'baseUrl' => $baseUrl,
            'environment' => $environment,
            'apiKey' => $apiKey,
            'apiSecret' => $apiSecret,
            'enabled' => $enabled,
        ];
    }

    protected static function isPublicHttpsUrl(string $url): bool
    {
        $parts = parse_url($url);
        if (!$parts || strtolower((string)($parts['scheme'] ?? '')) !== 'https' || empty($parts['host'])) {
            return false;
        }
        if (!empty($parts['user']) || !empty($parts['pass'])) {
            return false;
        }

        return !self::isBlockedHost((string)$parts['host']);
    }

    protected static function isBlockedHost(string $host): bool
    {
        $host = strtolower(trim($host, "[] \t"));
        if ($host === '' || $host === 'localhost' || str_ends_with($host, '.localhost') || str_ends_with($host, '.local')) {
            return true;
        }

        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return true;
        }

        return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    protected static function probeProviderUrl(string $url): array
    {
        if (!self::isPublicHttpsUrl($url)) {
            return ['ok' => false, 'detail' => 'the address is not a public HTTPS URL'];
        }
        if (!function_exists('curl_init')) {
            return ['ok' => false, 'detail' => 'the server cannot open outbound connections'];
        }

        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_HTTPGET => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'CJTravelsAdmin/1.0',
            CURLOPT_RANGE => '0-256',
        ]);
        curl_exec($handle);
        $code = (int)curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);

        if ($code > 0) {
            return ['ok' => true, 'detail' => 'host responded with HTTP ' . $code];
        }

        return ['ok' => false, 'detail' => 'the host did not respond'];
    }

    protected static function writeApiLog(string $provider, string $service, string $activity, string $status): void
    {
        $db = Database::getInstance();
        $db->insert('api_logs', [
            'provider_name' => mb_substr($provider, 0, 120),
            'service' => $service,
            'activity' => mb_substr($activity, 0, 180),
            'status' => $status,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $oldest = $db->select('SELECT id FROM api_logs ORDER BY id DESC LIMIT 60, 200');
        foreach ($oldest as $row) {
            $db->delete('api_logs', 'id = :id', [':id' => (int)$row['id']]);
        }
    }

    protected static function providerId(string|int $id): int
    {
        return (int)preg_replace('/[^0-9]/', '', (string)$id);
    }

    public static function reportsCsv(): string
    {
        $data = self::getAdminData();
        $rows = [];
        $push = static function (array $cells) use (&$rows): void {
            $rows[] = implode(',', array_map(static function ($value): string {
                return '"' . str_replace('"', '""', (string)$value) . '"';
            }, $cells));
        };

        $paid = 0.0;
        $paidCount = 0;
        $statusTotals = [];
        foreach ($data['transactions'] as $transaction) {
            $status = (string)$transaction['status'];
            if (!isset($statusTotals[$status])) {
                $statusTotals[$status] = ['count' => 0, 'amount' => 0.0];
            }
            $statusTotals[$status]['count']++;
            $statusTotals[$status]['amount'] += (float)$transaction['amount'];
            if ($status === 'Paid') {
                $paid += (float)$transaction['amount'];
                $paidCount++;
            }
        }
        $loanValue = 0.0;
        foreach ($data['loans'] as $loan) {
            $loanValue += (float)$loan['amount'];
        }

        $push(['CJ Travels live report', date('Y-m-d H:i:s')]);
        $push([]);
        $push(['Summary']);
        $push(['Customers', count($data['customers'])]);
        $push(['Flights', count($data['flights'])]);
        $push(['Hotels', count($data['hotels'])]);
        $push(['Total bookings', count($data['flights']) + count($data['hotels'])]);
        $push(['Paid transactions', $paidCount]);
        $push(['Paid revenue (NGN)', number_format($paid, 2, '.', '')]);
        $push(['Financing requested (NGN)', number_format($loanValue, 2, '.', '')]);
        $push([]);
        $push(['Payments by status', 'Count', 'Amount (NGN)']);
        foreach ($statusTotals as $status => $totals) {
            $push([$status, $totals['count'], number_format($totals['amount'], 2, '.', '')]);
        }
        $push([]);
        $push(['Month', 'Flights', 'Hotels']);
        foreach ($data['monthly'] as $month) {
            $push([$month['month'], $month['flights'], $month['hotels']]);
        }
        $push([]);
        $push(['Bookings by continent', 'Count']);
        $continents = [];
        foreach (array_merge($data['flights'], $data['hotels']) as $booking) {
            $name = (string)($booking['continent'] ?? 'Other');
            $continents[$name] = ($continents[$name] ?? 0) + 1;
        }
        foreach ($continents as $name => $count) {
            $push([$name, $count]);
        }
        $push([]);
        $push(['Transactions', 'Reference', 'Customer', 'Related booking', 'Method', 'Date', 'Amount (NGN)', 'Status']);
        foreach ($data['transactions'] as $transaction) {
            $push([
                '',
                $transaction['ref'],
                $transaction['customerName'],
                $transaction['related'],
                $transaction['method'],
                substr((string)$transaction['date'], 0, 10),
                number_format((float)$transaction['amount'], 2, '.', ''),
                $transaction['status'],
            ]);
        }

        return implode("\r\n", $rows) . "\r\n";
    }

    protected static function continentLookup(array $destinations): array
    {
        $lookup = [
            'lagos' => 'Africa',
            'abuja' => 'Africa',
            'accra' => 'Africa',
            'nairobi' => 'Africa',
            'cairo' => 'Africa',
            'london' => 'Europe',
            'paris' => 'Europe',
            'rome' => 'Europe',
            'dubai' => 'Middle East',
            'doha' => 'Middle East',
            'istanbul' => 'Middle East',
            'new york' => 'Americas',
            'atlanta' => 'Americas',
            'sydney' => 'Oceania',
        ];

        foreach ($destinations as $destination) {
            $city = strtolower(trim((string)($destination['city'] ?? '')));
            $continent = trim((string)($destination['continent'] ?? ''));
            if ($city !== '' && $continent !== '') {
                $lookup[$city] = $continent;
            }
        }

        return $lookup;
    }

    protected static function placeContinent(string $place, array $lookup): string
    {
        $haystack = strtolower($place);
        $cities = array_keys($lookup);
        usort($cities, static fn(string $left, string $right): int => strlen($right) <=> strlen($left));

        foreach ($cities as $city) {
            if ($city !== '' && str_contains($haystack, $city)) {
                return $lookup[$city];
            }
        }

        return 'Other';
    }

    protected static function maskCredential(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return '••••••••' . substr($value, -4);
    }

    public static function ensureContactSchema(): void
    {
        $db = Database::getInstance();
        if ($db->getDriver() !== 'mysql') {
            return;
        }

        $db->getPdo()->exec(
            "CREATE TABLE IF NOT EXISTS contact_messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ref VARCHAR(20) NOT NULL,
                name VARCHAR(160) NOT NULL,
                email VARCHAR(190) NOT NULL,
                phone VARCHAR(40) NULL,
                subject VARCHAR(190) NOT NULL,
                department VARCHAR(80) NOT NULL,
                message TEXT NOT NULL,
                created_at DATETIME NOT NULL,
                UNIQUE KEY uniq_contact_ref (ref)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $hasPhone = (bool)$db->getPdo()->query("SHOW COLUMNS FROM contact_messages LIKE 'phone'")->fetch();
        if (!$hasPhone) {
            $db->getPdo()->exec("ALTER TABLE contact_messages ADD COLUMN phone VARCHAR(40) NULL AFTER email");
        }
    }

    public static function contactMessageCount(): int
    {
        try {
            self::ensureContactSchema();
            $row = Database::getInstance()->selectOne('SELECT COUNT(*) AS total FROM contact_messages');
            return (int)($row['total'] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public static function contactMessages(): array
    {
        self::ensureContactSchema();
        return Database::getInstance()->select(
            'SELECT id, ref, name, email, phone, subject, department, message, created_at
             FROM contact_messages
             ORDER BY id DESC'
        );
    }

    public static function deleteContactMessage(string $ref): array
    {
        self::$lastError = '';
        self::ensureContactSchema();
        $ref = trim($ref);
        if ($ref === '') {
            self::$lastError = 'Choose a message to delete.';
            return ['success' => false, 'error' => self::$lastError];
        }

        $db = Database::getInstance();
        $existing = $db->selectOne(
            'SELECT id FROM contact_messages WHERE ref = :ref LIMIT 1',
            [':ref' => $ref]
        );
        if (!$existing) {
            self::$lastError = 'That message could not be found.';
            return ['success' => false, 'error' => self::$lastError];
        }

        $db->delete('contact_messages', 'ref = :ref', [':ref' => $ref]);

        return [
            'success' => true,
            'message' => 'The message has been deleted.',
        ];
    }

    public static function createContactMessage(array $data): array
    {
        self::$lastError = '';
        self::ensureContactSchema();
        $name = trim((string)($data['name'] ?? ''));
        $email = trim((string)($data['email'] ?? ''));
        $phone = trim((string)($data['phone'] ?? ''));
        $subject = trim((string)($data['subject'] ?? ''));
        $department = trim((string)($data['department'] ?? ''));
        $message = trim((string)($data['message'] ?? ''));

        if ($name === '' || $email === '' || $subject === '' || $department === '' || $message === '') {
            self::$lastError = 'Name, email, subject, department, and message are required.';
            return ['success' => false, 'error' => self::$lastError];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            self::$lastError = 'Enter a valid email address.';
            return ['success' => false, 'error' => self::$lastError];
        }
        $phoneDigits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($phone !== '' && strlen($phoneDigits) < 10) {
            self::$lastError = 'Enter a phone number with at least 10 digits, or leave it blank.';
            return ['success' => false, 'error' => self::$lastError];
        }

        $db = Database::getInstance();
        $ref = self::nextContactRef($db);
        $db->insert('contact_messages', [
            'ref' => $ref,
            'name' => $name,
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'subject' => $subject,
            'department' => $department,
            'message' => $message,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return [
            'success' => true,
            'message' => 'Your message is saved.',
            'reference' => $ref,
        ];
    }

    protected static function nextContactRef(Database $db): string
    {
        for ($attempt = 0; $attempt < 8; $attempt++) {
            $ref = 'MSG-' . random_int(1000, 9999);
            $existing = $db->selectOne('SELECT id FROM contact_messages WHERE ref = :ref LIMIT 1', [':ref' => $ref]);
            if (!$existing) {
                return $ref;
            }
        }

        return 'MSG-' . date('His');
    }

    public static function ensurePromoSchema(): void
    {
        $db = Database::getInstance();
        if ($db->getDriver() !== 'mysql') {
            return;
        }

        $db->getPdo()->exec(
            "CREATE TABLE IF NOT EXISTS promo_registrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ref VARCHAR(30) NOT NULL,
                promo_code VARCHAR(30) NOT NULL,
                full_name VARCHAR(160) NOT NULL,
                email VARCHAR(190) NOT NULL,
                phone VARCHAR(60) NULL,
                destination_interest VARCHAR(120) NULL,
                discount_amount DECIMAL(10,2) NOT NULL DEFAULT 50000.00,
                status VARCHAR(30) NOT NULL DEFAULT 'Active',
                created_at DATETIME NOT NULL,
                UNIQUE KEY uniq_promo_ref (ref)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }

    public static function registerPromo(array $data): array
    {
        self::$lastError = '';
        self::ensurePromoSchema();
        $db = Database::getInstance();

        $name = trim((string)($data['fullName'] ?? $data['name'] ?? ''));
        $email = strtolower(trim((string)($data['email'] ?? '')));
        $phone = trim((string)($data['phone'] ?? ''));
        $destination = trim((string)($data['destination'] ?? $data['destination_interest'] ?? 'General Luxury Travel'));
        $code = strtoupper(trim((string)($data['promoCode'] ?? 'CJLUXE50K')));

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            self::$lastError = 'Please provide your full name and a valid email address.';
            return ['success' => false, 'error' => self::$lastError];
        }

        $ref = 'PRM-' . strtoupper(substr(uniqid(), -6));
        $now = date('Y-m-d H:i:s');

        $insert = [
            'ref' => $ref,
            'promo_code' => $code !== '' ? $code : 'CJLUXE50K',
            'full_name' => $name,
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'destination_interest' => $destination !== '' ? $destination : 'General Luxury Travel',
            'discount_amount' => 50000.00,
            'status' => 'Active',
            'created_at' => $now,
        ];

        $id = (int)$db->insert('promo_registrations', $insert);

        return [
            'success' => true,
            'message' => 'Congratulations! Your exclusive ₦50,000 travel voucher has been activated.',
            'promo' => [
                'id' => $id,
                'ref' => $ref,
                'code' => $insert['promo_code'],
                'discount' => '₦50,000',
                'discountAmount' => 50000,
                'name' => $name,
                'email' => $email,
                'status' => 'Active',
                'validUntil' => date('F d, Y', strtotime('+30 days')),
            ],
        ];
    }

    public static function ensurePromoCampaignSchema(): void
    {
        $db = Database::getInstance();
        if ($db->getDriver() !== 'mysql') {
            return;
        }

        $db->getPdo()->exec(
            "CREATE TABLE IF NOT EXISTS promo_campaigns (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                subtitle TEXT NULL,
                promo_code VARCHAR(50) NOT NULL DEFAULT 'CJLUXE50K',
                discount_amount VARCHAR(80) NOT NULL DEFAULT '₦50,000',
                flier_image VARCHAR(255) NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $existing = $db->selectOne("SELECT id FROM promo_campaigns LIMIT 1");
        if (!$existing) {
            $now = date('Y-m-d H:i:s');
            $db->insert('promo_campaigns', [
                'title' => 'Unlock ₦50,000 Off Your Next Luxury Getaway',
                'subtitle' => 'Fly gracefully to London, Dubai, Paris, or explore paradise in Zanzibar. Register today to claim your limited-edition CJ Travels promotion credit.',
                'promo_code' => 'CJLUXE50K',
                'discount_amount' => '₦50,000',
                'flier_image' => null,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public static function getActivePromoCampaign(): array
    {
        self::ensurePromoCampaignSchema();
        $db = Database::getInstance();
        $row = $db->selectOne("SELECT * FROM promo_campaigns ORDER BY id DESC LIMIT 1");
        if (!$row) {
            return [
                'id' => 1,
                'title' => 'Unlock ₦50,000 Off Your Next Luxury Getaway',
                'subtitle' => 'Fly gracefully to London, Dubai, Paris, or explore paradise in Zanzibar.',
                'promo_code' => 'CJLUXE50K',
                'discount_amount' => '₦50,000',
                'flier_image' => null,
                'is_active' => 1,
            ];
        }

        return [
            'id' => (int)$row['id'],
            'title' => (string)$row['title'],
            'subtitle' => (string)($row['subtitle'] ?? ''),
            'promo_code' => (string)($row['promo_code'] ?? 'CJLUXE50K'),
            'discount_amount' => (string)($row['discount_amount'] ?? '₦50,000'),
            'flier_image' => !empty($row['flier_image']) ? (string)$row['flier_image'] : null,
            'is_active' => (int)($row['is_active'] ?? 1),
            'updated_at' => (string)($row['updated_at'] ?? ''),
        ];
    }

    public static function updatePromoCampaign(array $data, ?array $file = null): array
    {
        self::$lastError = '';
        self::ensurePromoCampaignSchema();
        $db = Database::getInstance();
        $campaign = self::getActivePromoCampaign();
        $campaignId = $campaign['id'] ?? 1;

        $title = trim((string)($data['title'] ?? ''));
        $subtitle = trim((string)($data['subtitle'] ?? ''));
        $promoCode = strtoupper(trim((string)($data['promo_code'] ?? 'CJLUXE50K')));
        $discountAmount = trim((string)($data['discount_amount'] ?? '₦50,000'));
        $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;
        $removeFlier = !empty($data['remove_flier']);

        if ($title === '') {
            self::$lastError = 'Promotional campaign title is required.';
            return ['success' => false, 'error' => self::$lastError];
        }

        $flierImagePath = $campaign['flier_image'] ?? null;

        if ($removeFlier) {
            $flierImagePath = null;
        }

        // Handle file upload
        if ($file && !empty($file['tmp_name']) && $file['error'] === UPLOAD_ERR_OK) {
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $maxBytes = 10 * 1024 * 1024; // 10MB

            if ($file['size'] > $maxBytes) {
                self::$lastError = 'Promotional flier file size exceeds the 10MB limit.';
                return ['success' => false, 'error' => self::$lastError];
            }

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExtensions, true)) {
                self::$lastError = 'Invalid image file format. Only JPG, PNG, WEBP, and GIF are supported.';
                return ['success' => false, 'error' => self::$lastError];
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime, $allowedMimeTypes, true)) {
                self::$lastError = 'The uploaded file is not a valid image format.';
                return ['success' => false, 'error' => self::$lastError];
            }

            $baseDir = dirname(__DIR__, 2);
            $publicDir = $baseDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'promo';
            $rootDir   = $baseDir . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'promo';

            if (!is_dir($publicDir)) {
                mkdir($publicDir, 0755, true);
            }
            if (!is_dir($rootDir)) {
                mkdir($rootDir, 0755, true);
            }

            $filename = 'promo_flier_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $publicTarget = $publicDir . DIRECTORY_SEPARATOR . $filename;
            $rootTarget   = $rootDir . DIRECTORY_SEPARATOR . $filename;

            $saved = is_uploaded_file($file['tmp_name'])
                ? move_uploaded_file($file['tmp_name'], $publicTarget)
                : copy($file['tmp_name'], $publicTarget);

            if ($saved) {
                copy($publicTarget, $rootTarget);
                $flierImagePath = 'images/promo/' . $filename;
            } else {
                self::$lastError = 'Failed to move uploaded flier image to storage directory.';
                return ['success' => false, 'error' => self::$lastError];
            }
        }

        $now = date('Y-m-d H:i:s');
        $update = [
            'title' => $title,
            'subtitle' => $subtitle,
            'promo_code' => $promoCode !== '' ? $promoCode : 'CJLUXE50K',
            'discount_amount' => $discountAmount !== '' ? $discountAmount : '₦50,000',
            'flier_image' => $flierImagePath,
            'is_active' => $isActive,
            'updated_at' => $now,
        ];

        $db->update('promo_campaigns', $update, 'id = :id', [':id' => $campaignId]);

        return [
            'success' => true,
            'message' => 'Promotional flier and campaign settings saved successfully!',
            'campaign' => array_merge($update, ['id' => $campaignId]),
        ];
    }

    public static function ensureSiteSettings(): void
    {
        $db = Database::getInstance();
        if ($db->getDriver() !== 'mysql') {
            return;
        }

        $db->getPdo()->exec(
            "CREATE TABLE IF NOT EXISTS site_settings (
                setting_key VARCHAR(64) NOT NULL PRIMARY KEY,
                setting_value VARCHAR(255) NOT NULL,
                updated_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $existing = $db->selectOne(
            'SELECT setting_key FROM site_settings WHERE setting_key = :key LIMIT 1',
            [':key' => 'contact_phone']
        );
        if (!$existing) {
            $db->insert('site_settings', [
                'setting_key' => 'contact_phone',
                'setting_value' => '08135196772',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public static function contactPhone(): array
    {
        $display = '08135196772';
        try {
            self::ensureSiteSettings();
            $row = Database::getInstance()->selectOne(
                'SELECT setting_value FROM site_settings WHERE setting_key = :key LIMIT 1',
                [':key' => 'contact_phone']
            );
            $stored = trim((string)($row['setting_value'] ?? ''));
            if ($stored !== '') {
                $display = $stored;
            }
        } catch (\Throwable $e) {
            $display = '08135196772';
        }

        $digits = preg_replace('/\D+/', '', $display) ?? '';
        if (str_starts_with($digits, '234')) {
            $whatsapp = $digits;
        } elseif (str_starts_with($digits, '0')) {
            $whatsapp = '234' . substr($digits, 1);
        } else {
            $whatsapp = '234' . $digits;
        }

        return [
            'display' => $display,
            'tel' => '+' . $whatsapp,
            'whatsapp' => $whatsapp,
        ];
    }

    public static function updateContactPhone(string $phone): array
    {
        self::$lastError = '';
        $phone = trim($phone);
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($digits) < 10) {
            self::$lastError = 'Enter a valid phone number with at least 10 digits.';
            return ['success' => false, 'error' => self::$lastError];
        }

        self::ensureSiteSettings();
        $db = Database::getInstance();
        $now = date('Y-m-d H:i:s');
        $existing = $db->selectOne(
            'SELECT setting_key FROM site_settings WHERE setting_key = :key LIMIT 1',
            [':key' => 'contact_phone']
        );
        if ($existing) {
            $db->update('site_settings', [
                'setting_value' => $phone,
                'updated_at' => $now,
            ], 'setting_key = :key', [':key' => 'contact_phone']);
        } else {
            $db->insert('site_settings', [
                'setting_key' => 'contact_phone',
                'setting_value' => $phone,
                'updated_at' => $now,
            ]);
        }

        return [
            'success' => true,
            'message' => 'The public phone and WhatsApp number have been updated.',
        ];
    }

    public static function ensureBusBookingSchema(): void
    {
        $db = Database::getInstance();
        if ($db->getDriver() !== 'mysql') {
            return;
        }

        $db->getPdo()->exec(
            "CREATE TABLE IF NOT EXISTS bus_bookings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ref VARCHAR(32) NOT NULL UNIQUE,
                customer_name VARCHAR(150) NOT NULL,
                customer_phone VARCHAR(50) NOT NULL,
                customer_email VARCHAR(150) NULL,
                trip_type VARCHAR(20) NOT NULL DEFAULT 'one-way',
                departure_city VARCHAR(100) NOT NULL,
                destination_city VARCHAR(100) NOT NULL,
                departure_date DATE NOT NULL,
                return_date DATE NULL,
                departure_time VARCHAR(60) NULL DEFAULT 'Morning (06:30 - 08:00 AM)',
                passengers INT NOT NULL DEFAULT 1,
                coach_class VARCHAR(80) NOT NULL DEFAULT 'Executive Luxury AC Coach',
                preferred_operator VARCHAR(100) NULL DEFAULT 'Any Verified Luxury Partner',
                estimated_fare DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                notes TEXT NULL,
                status VARCHAR(30) NOT NULL DEFAULT 'Pending',
                whatsapp_status VARCHAR(30) NOT NULL DEFAULT 'Initiated',
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_bus_ref (ref),
                INDEX idx_bus_status (status),
                INDEX idx_bus_depart_date (departure_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }

    public static function getPopularBusRoutes(): array
    {
        return [
            [
                'from' => 'Lagos',
                'to' => 'Abuja',
                'from_terminal' => 'Jibowu / Ajah Terminal',
                'to_terminal' => 'Utako Central Terminal',
                'duration' => '9–11 hrs',
                'fare' => 32000,
                'fare_display' => '₦32,000',
                'badge' => 'Most Popular',
            ],
            [
                'from' => 'Lagos',
                'to' => 'Port Harcourt',
                'from_terminal' => 'Jibowu / Maza-Maza',
                'to_terminal' => 'Waterlines Terminal',
                'duration' => '8–10 hrs',
                'fare' => 28000,
                'fare_display' => '₦28,000',
                'badge' => 'Daily Executive',
            ],
            [
                'from' => 'Lagos',
                'to' => 'Benin City',
                'from_terminal' => 'Jibowu / Yaba',
                'to_terminal' => 'Uselu / Akpakpava',
                'duration' => '4–5 hrs',
                'fare' => 18000,
                'fare_display' => '₦18,000',
                'badge' => 'Express Route',
            ],
            [
                'from' => 'Lagos',
                'to' => 'Enugu',
                'from_terminal' => 'Jibowu / Ojuelegba',
                'to_terminal' => 'Holy Ghost / Market Road',
                'duration' => '7–9 hrs',
                'fare' => 26000,
                'fare_display' => '₦26,000',
                'badge' => 'Top Rated',
            ],
            [
                'from' => 'Lagos',
                'to' => 'Owerri',
                'from_terminal' => 'Jibowu / Ajah',
                'to_terminal' => 'Control Post / Orji',
                'duration' => '8–9 hrs',
                'fare' => 26000,
                'fare_display' => '₦26,000',
                'badge' => 'Daily VIP',
            ],
            [
                'from' => 'Lagos',
                'to' => 'Ibadan',
                'from_terminal' => 'Ojota / Berger',
                'to_terminal' => 'Iwo Road / Challenge',
                'duration' => '2–2.5 hrs',
                'fare' => 10000,
                'fare_display' => '₦10,000',
                'badge' => 'Short Shuttle',
            ],
            [
                'from' => 'Abuja',
                'to' => 'Kaduna',
                'from_terminal' => 'Utako / Jabi',
                'to_terminal' => 'Kawo / Central Station',
                'duration' => '2.5–3 hrs',
                'fare' => 8500,
                'fare_display' => '₦8,500',
                'badge' => 'Frequent Departures',
            ],
            [
                'from' => 'Abuja',
                'to' => 'Kano',
                'from_terminal' => 'Utako Terminal',
                'to_terminal' => 'Sabon Gari / Naibawa',
                'duration' => '5–6 hrs',
                'fare' => 16000,
                'fare_display' => '₦16,000',
                'badge' => 'Northern Corridor',
            ],
            [
                'from' => 'Abuja',
                'to' => 'Jos',
                'from_terminal' => 'Utako Terminal',
                'to_terminal' => 'Terminus / British America',
                'duration' => '3.5–4.5 hrs',
                'fare' => 12000,
                'fare_display' => '₦12,000',
                'badge' => 'Scenic Plateau',
            ],
            [
                'from' => 'Abuja',
                'to' => 'Port Harcourt',
                'from_terminal' => 'Utako Central Terminal',
                'to_terminal' => 'Aba Road / Waterlines',
                'duration' => '10–12 hrs',
                'fare' => 30000,
                'fare_display' => '₦30,000',
                'badge' => 'Cross-Country',
            ],
            [
                'from' => 'Lagos',
                'to' => 'Asaba / Onitsha',
                'from_terminal' => 'Jibowu / Ajah',
                'to_terminal' => 'Upper Iweka / Summit Rd',
                'duration' => '6–7.5 hrs',
                'fare' => 24000,
                'fare_display' => '₦24,000',
                'badge' => 'Commercial Hub',
            ],
            [
                'from' => 'Lagos',
                'to' => 'Calabar',
                'from_terminal' => 'Jibowu Terminal',
                'to_terminal' => 'Murtala Mohammed Hwy',
                'duration' => '11–13 hrs',
                'fare' => 32000,
                'fare_display' => '₦32,000',
                'badge' => 'Coastal Voyage',
            ],
        ];
    }

    public static function estimateBusFare(string $from, string $to, string $tripType, int $passengers, string $coachClass): float
    {
        $passengers = max(1, min(20, $passengers));
        $routes = self::getPopularBusRoutes();

        $fromLower = strtolower(trim($from));
        $toLower = strtolower(trim($to));

        $baseFare = 22000.00; // default baseline interstate fare
        foreach ($routes as $r) {
            $rFrom = strtolower($r['from']);
            $rTo = strtolower($r['to']);
            if (
                (str_contains($fromLower, $rFrom) && str_contains($toLower, $rTo)) ||
                (str_contains($fromLower, $rTo) && str_contains($toLower, $rFrom))
            ) {
                $baseFare = (float)$r['fare'];
                break;
            }
        }

        // Coach Class multiplier
        $classMultiplier = match (strtolower(trim($coachClass))) {
            'vip first-class coach', 'first-class vip express', 'vip' => 1.50,
            'executive luxury ac coach', 'executive coach', 'executive' => 1.25,
            'standard comfort ac bus', 'standard' => 1.00,
            default => 1.20,
        };

        // Trip Type multiplier (1.0 for one-way, 1.95 for round-trip with 5% discount)
        $tripMultiplier = (strtolower(trim($tripType)) === 'round-trip') ? 1.95 : 1.00;

        return round($baseFare * $classMultiplier * $tripMultiplier * $passengers, 2);
    }

    public static function createBusBooking(array $data): array
    {
        self::$lastError = '';
        self::ensureBusBookingSchema();
        $db = Database::getInstance();

        $name = trim((string)($data['customer_name'] ?? $data['name'] ?? $data['fullName'] ?? ''));
        $phone = trim((string)($data['customer_phone'] ?? $data['phone'] ?? ''));
        $email = strtolower(trim((string)($data['customer_email'] ?? $data['email'] ?? '')));
        $from = trim((string)($data['departure_city'] ?? $data['from'] ?? ''));
        $to = trim((string)($data['destination_city'] ?? $data['to'] ?? ''));
        $departDate = trim((string)($data['departure_date'] ?? $data['depart'] ?? ''));
        $returnDate = trim((string)($data['return_date'] ?? $data['return'] ?? ''));
        $tripType = strtolower(trim((string)($data['trip_type'] ?? 'one-way'))) === 'round-trip' ? 'round-trip' : 'one-way';
        $passengers = max(1, min(20, (int)($data['passengers'] ?? $data['seats'] ?? 1)));
        $departTime = trim((string)($data['departure_time'] ?? 'Morning (06:30 - 08:00 AM)'));
        $coachClass = trim((string)($data['coach_class'] ?? 'Executive Luxury AC Coach'));
        $operator = trim((string)($data['preferred_operator'] ?? 'Any Verified Luxury Partner'));
        $notes = trim((string)($data['notes'] ?? $data['special_requests'] ?? ''));

        // Validation
        if ($name === '') {
            self::$lastError = 'Please enter your full name.';
            return ['success' => false, 'error' => self::$lastError];
        }
        if ($phone === '' || strlen(preg_replace('/\D+/', '', $phone)) < 8) {
            self::$lastError = 'Please provide a valid phone or WhatsApp number.';
            return ['success' => false, 'error' => self::$lastError];
        }
        if ($from === '' || $to === '') {
            self::$lastError = 'Please select both departure and destination locations.';
            return ['success' => false, 'error' => self::$lastError];
        }
        if (strcasecmp($from, $to) === 0) {
            self::$lastError = 'Departure and destination cannot be the same city.';
            return ['success' => false, 'error' => self::$lastError];
        }
        if ($departDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $departDate)) {
            self::$lastError = 'Please choose a valid departure date.';
            return ['success' => false, 'error' => self::$lastError];
        }

        // Validate not in the past
        $today = date('Y-m-d');
        if ($departDate < $today) {
            self::$lastError = 'Departure date cannot be in the past.';
            return ['success' => false, 'error' => self::$lastError];
        }

        if ($tripType === 'round-trip') {
            if ($returnDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $returnDate)) {
                self::$lastError = 'Please select a valid return date for round-trip travel.';
                return ['success' => false, 'error' => self::$lastError];
            }
            if ($returnDate < $departDate) {
                self::$lastError = 'Return date cannot be earlier than departure date.';
                return ['success' => false, 'error' => self::$lastError];
            }
        } else {
            $returnDate = null;
        }

        $estimatedFare = self::estimateBusFare($from, $to, $tripType, $passengers, $coachClass);
        $formattedFare = '₦' . number_format($estimatedFare);

        $ref = 'CJBUS-' . strtoupper(substr(uniqid(), -6));
        $now = date('Y-m-d H:i:s');

        $insert = [
            'ref' => $ref,
            'customer_name' => $name,
            'customer_phone' => $phone,
            'customer_email' => $email !== '' ? $email : null,
            'trip_type' => $tripType,
            'departure_city' => $from,
            'destination_city' => $to,
            'departure_date' => $departDate,
            'return_date' => $returnDate,
            'departure_time' => $departTime !== '' ? $departTime : 'Morning (06:30 - 08:00 AM)',
            'passengers' => $passengers,
            'coach_class' => $coachClass,
            'preferred_operator' => $operator !== '' ? $operator : 'Any Verified Luxury Partner',
            'estimated_fare' => $estimatedFare,
            'notes' => $notes !== '' ? $notes : null,
            'status' => 'Pending',
            'whatsapp_status' => 'Initiated',
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $id = (int)$db->insert('bus_bookings', $insert);

        // Build WhatsApp Message
        $whatsappNumber = function_exists('cj_whatsapp_number') ? cj_whatsapp_number() : '2348135196772';
        $formattedDepartDate = date('D, j M Y', strtotime($departDate));
        $formattedReturnDate = $returnDate ? date('D, j M Y', strtotime($returnDate)) : 'N/A (One-Way)';

        $lines = [
            "🚌 *CJ TRAVELS — ROAD TRANSPORT & BUS TICKET ENQUIRY*",
            "━━━━━━━━━━━━━━━━━━━━━━━━━━━━",
            "📋 *Booking Ref:* {$ref}",
            "👤 *Passenger Name:* {$name}",
            "📞 *Phone:* {$phone}",
        ];

        if ($email !== '') {
            $lines[] = "📧 *Email:* {$email}";
        }

        $lines[] = "";
        $lines[] = "🛣️ *Journey Overview:*";
        $lines[] = "• *Departure City:* {$from}";
        $lines[] = "• *Destination:* {$to}";
        $lines[] = "• *Trip Type:* " . ($tripType === 'round-trip' ? 'Round-Trip (Two-Way)' : 'One-Way');
        $lines[] = "• *Departure Date:* {$formattedDepartDate} ({$departTime})";

        if ($tripType === 'round-trip') {
            $lines[] = "• *Return Date:* {$formattedReturnDate}";
        }

        $lines[] = "• *Passengers / Seats:* {$passengers} Seat" . ($passengers > 1 ? 's' : '');
        $lines[] = "• *Coach Class:* {$coachClass}";
        $lines[] = "• *Preferred Operator:* " . ($operator ?: 'Any Verified Luxury Partner');
        $lines[] = "• *Estimated Total Fare:* {$formattedFare}";

        if ($notes !== '') {
            $lines[] = "";
            $lines[] = "💬 *Special Requests / Luggage:*";
            $lines[] = "{$notes}";
        }

        $lines[] = "";
        $lines[] = "━━━━━━━━━━━━━━━━━━━━━━━━━━━━";
        $lines[] = "Hello CJ Travels, I would like to confirm bus availability and complete this booking.";

        $messageText = implode("\n", $lines);
        $whatsappUrl = "https://wa.me/{$whatsappNumber}?text=" . rawurlencode($messageText);

        return [
            'success' => true,
            'message' => 'Your bus travel enquiry has been created! Please click below to send it to our WhatsApp reservations desk.',
            'booking' => array_merge($insert, [
                'id' => $id,
                'formattedFare' => $formattedFare,
                'formattedDepartDate' => $formattedDepartDate,
                'formattedReturnDate' => $formattedReturnDate,
            ]),
            'whatsappUrl' => $whatsappUrl,
            'messageText' => $messageText,
        ];
    }

    public static function getBusBookings(int $limit = 50, ?string $status = null): array
    {
        self::ensureBusBookingSchema();
        $db = Database::getInstance();
        $params = [];
        $sql = "SELECT * FROM bus_bookings";
        if ($status !== null && $status !== '') {
            $sql .= " WHERE status = :status";
            $params[':status'] = $status;
        }
        $sql .= " ORDER BY id DESC LIMIT " . (int)$limit;

        $rows = $db->select($sql, $params);
        $bookings = [];
        foreach ($rows as $r) {
            $bookings[] = [
                'id' => (int)$r['id'],
                'ref' => (string)$r['ref'],
                'customer_name' => (string)$r['customer_name'],
                'customer_phone' => (string)$r['customer_phone'],
                'customer_email' => (string)($r['customer_email'] ?? ''),
                'trip_type' => (string)$r['trip_type'],
                'departure_city' => (string)$r['departure_city'],
                'destination_city' => (string)$r['destination_city'],
                'departure_date' => (string)$r['departure_date'],
                'return_date' => !empty($r['return_date']) ? (string)$r['return_date'] : null,
                'departure_time' => (string)($r['departure_time'] ?? 'Morning'),
                'passengers' => (int)$r['passengers'],
                'coach_class' => (string)$r['coach_class'],
                'preferred_operator' => (string)($r['preferred_operator'] ?? 'Any'),
                'estimated_fare' => (float)$r['estimated_fare'],
                'formatted_fare' => '₦' . number_format((float)$r['estimated_fare']),
                'notes' => (string)($r['notes'] ?? ''),
                'status' => (string)$r['status'],
                'whatsapp_status' => (string)($r['whatsapp_status'] ?? 'Initiated'),
                'created_at' => (string)$r['created_at'],
            ];
        }
        return $bookings;
    }

    public static function busBookingCount(): int
    {
        self::ensureBusBookingSchema();
        try {
            $row = Database::getInstance()->selectOne("SELECT COUNT(*) as c FROM bus_bookings");
            return (int)($row['c'] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
