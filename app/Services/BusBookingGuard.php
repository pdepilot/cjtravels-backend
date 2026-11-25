<?php

namespace App\Services;

use App\Core\Database;
use PDOException;
use Throwable;

class BusBookingGuard
{
    protected static ?\Closure $referenceFactory = null;

    protected static bool $failNextInsert = false;

    protected static ?string $schemaDriverOverride = null;

    protected static ?string $markerPathOverride = null;

    public static function setReferenceFactory(?callable $factory): void
    {
        self::$referenceFactory = $factory ? \Closure::fromCallable($factory) : null;
    }

    public static function failNextInsert(bool $fail = true): void
    {
        self::$failNextInsert = $fail;
    }

    public static function useSchemaDriver(?string $driver): void
    {
        self::$schemaDriverOverride = $driver;
    }

    public static function useMarkerPath(?string $path): void
    {
        self::$markerPathOverride = $path;
    }

    public static function ensureSchema(): void
    {
        if (is_file(self::markerPath())) {
            return;
        }

        self::installSchema();
        self::writeMarker();
    }

    public static function installSchema(): void
    {
        $driver = self::$schemaDriverOverride ?? Database::getInstance()->getDriver();
        if ($driver !== 'mysql') {
            throw new \RuntimeException('Bus bookings require a MySQL database.');
        }

        $db = Database::getInstance();
        $path = self::schemaFile();
        $sql = is_file($path) ? file_get_contents($path) : false;
        if (!is_string($sql) || trim($sql) === '') {
            throw new \RuntimeException('The bus booking schema file is missing.');
        }

        $db->getPdo()->exec($sql);
    }

    public static function create(array $data): array
    {
        $validated = self::validate($data);
        if (!$validated['ok']) {
            return ['success' => false, 'error' => $validated['error']];
        }

        try {
            return self::store($validated['fields'], true);
        } catch (Throwable $e) {
            self::logFailure($e);
            return [
                'success' => false,
                'error' => 'We could not save your bus enquiry just now. Please try again in a moment.',
            ];
        }
    }

    public static function update(int $id, array $data): array
    {
        if ($id <= 0) {
            return ['success' => false, 'error' => 'Choose a valid booking.', 'code' => 'invalid'];
        }

        try {
            self::ensureSchema();
            $db = Database::getInstance();
            $existing = $db->selectOne('SELECT * FROM bus_bookings WHERE id = :id LIMIT 1', [':id' => $id]);
            if (!$existing) {
                return ['success' => false, 'error' => 'That booking could not be found.', 'code' => 'not_found'];
            }

            $update = ['updated_at' => date('Y-m-d H:i:s')];
            if (array_key_exists('status', $data)) {
                $status = trim((string)$data['status']);
                if (!in_array($status, self::statuses(), true)) {
                    return ['success' => false, 'error' => 'Choose Pending, Confirmed, Processing, or Cancelled.', 'code' => 'invalid'];
                }
                $update['status'] = $status;
            }
            if (array_key_exists('customer_name', $data)) {
                $name = trim((string)$data['customer_name']);
                if ($name === '' || self::length($name) > 120) {
                    return ['success' => false, 'error' => 'Enter a passenger name of 120 characters or less.', 'code' => 'invalid'];
                }
                $update['customer_name'] = $name;
            }
            if (array_key_exists('customer_phone', $data)) {
                $phone = self::phoneError(trim((string)$data['customer_phone']));
                if ($phone !== null) {
                    return ['success' => false, 'error' => $phone, 'code' => 'invalid'];
                }
                $update['customer_phone'] = trim((string)$data['customer_phone']);
            }
            if (array_key_exists('departure_date', $data)) {
                $depart = trim((string)$data['departure_date']);
                if (!self::isCalendarDate($depart)) {
                    return ['success' => false, 'error' => 'Choose a valid departure date.', 'code' => 'invalid'];
                }
                $update['departure_date'] = $depart;
            }
            if (array_key_exists('return_date', $data)) {
                $return = trim((string)$data['return_date']);
                if ($return === '') {
                    $update['return_date'] = null;
                } elseif (!self::isCalendarDate($return)) {
                    return ['success' => false, 'error' => 'Choose a valid return date.', 'code' => 'invalid'];
                } else {
                    $update['return_date'] = $return;
                }
            }
            if (array_key_exists('passengers', $data)) {
                $passengers = self::passengerCount($data['passengers']);
                if ($passengers === null) {
                    return ['success' => false, 'error' => 'Choose between 1 and 20 passengers.', 'code' => 'invalid'];
                }
                $update['passengers'] = $passengers;
            }
            if (array_key_exists('coach_class', $data)) {
                $coach = trim((string)$data['coach_class']);
                if (!in_array($coach, self::coachClasses(), true)) {
                    return ['success' => false, 'error' => 'Choose a supported coach class.', 'code' => 'invalid'];
                }
                $update['coach_class'] = $coach;
            }
            if (array_key_exists('notes', $data)) {
                $notes = trim((string)$data['notes']);
                if (self::length($notes) > 1000) {
                    return ['success' => false, 'error' => 'Please shorten the notes to 1,000 characters or less.', 'code' => 'invalid'];
                }
                $update['notes'] = $notes === '' ? null : $notes;
            }

            $statusUnchanged = isset($update['status']) && $update['status'] === (string)$existing['status'];
            $onlyStatus = count($update) === 2 && isset($update['status']);
            if ($statusUnchanged && $onlyStatus) {
                return [
                    'success' => true,
                    'message' => 'Booking #' . $id . ' is already ' . $update['status'] . '.',
                ];
            }

            if (count($update) > 1) {
                $db->update('bus_bookings', $update, 'id = :id', [':id' => $id]);
            }

            if (isset($update['status'])) {
                $message = 'Booking #' . $id . ' status updated to ' . $update['status'] . '.';
            } else {
                $message = 'Booking #' . $id . ' was updated.';
            }
            return [
                'success' => true,
                'message' => $message,
            ];
        } catch (Throwable $e) {
            self::logFailure($e);
            return ['success' => false, 'error' => 'We could not update that booking just now.', 'code' => 'error'];
        }
    }

    public static function delete(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'error' => 'Choose a valid booking.', 'code' => 'invalid'];
        }

        try {
            self::ensureSchema();
            $db = Database::getInstance();
            $existing = $db->selectOne('SELECT id FROM bus_bookings WHERE id = :id LIMIT 1', [':id' => $id]);
            if (!$existing) {
                return ['success' => false, 'error' => 'That booking could not be found.', 'code' => 'not_found'];
            }

            $deleted = $db->delete('bus_bookings', 'id = :id', [':id' => $id]);
            if ($deleted < 1) {
                return ['success' => false, 'error' => 'That booking could not be found.', 'code' => 'not_found'];
            }

            return ['success' => true, 'message' => 'Booking enquiry removed.'];
        } catch (Throwable $e) {
            self::logFailure($e);
            return ['success' => false, 'error' => 'We could not remove that booking just now.', 'code' => 'error'];
        }
    }

    protected static function store(array $fields, bool $repairSchema): array
    {
        self::ensureSchema();
        $db = Database::getInstance();

        try {
            $existing = self::findRecentDuplicate($db, $fields);
        } catch (PDOException $e) {
            if ($repairSchema && self::isMissingTable($e)) {
                self::clearMarker();
                self::installSchema();
                self::writeMarker();
                return self::store($fields, false);
            }
            throw $e;
        }

        if ($existing) {
            return self::payload($existing, true);
        }

        return self::insertBooking($db, $fields);
    }

    protected static function insertBooking(Database $db, array $fields): array
    {
        $now = date('Y-m-d H:i:s');
        $lastError = null;

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $ref = self::makeReference();
            $row = [
                'ref' => $ref,
                'customer_name' => $fields['customer_name'],
                'customer_phone' => $fields['customer_phone'],
                'customer_email' => $fields['customer_email'],
                'trip_type' => $fields['trip_type'],
                'departure_city' => $fields['departure_city'],
                'destination_city' => $fields['destination_city'],
                'departure_date' => $fields['departure_date'],
                'return_date' => $fields['return_date'],
                'departure_time' => $fields['departure_time'],
                'passengers' => $fields['passengers'],
                'coach_class' => $fields['coach_class'],
                'preferred_operator' => $fields['preferred_operator'],
                'estimated_fare' => $fields['estimated_fare'],
                'notes' => $fields['notes'],
                'status' => 'Pending',
                'whatsapp_status' => 'Initiated',
                'created_at' => $now,
                'updated_at' => $now,
            ];

            try {
                if (self::$failNextInsert) {
                    self::$failNextInsert = false;
                    throw new \RuntimeException('Simulated bus booking insert failure');
                }

                $id = (int)$db->insert('bus_bookings', $row);
                if ($id <= 0) {
                    throw new \RuntimeException('Bus booking insert did not return an id.');
                }
                $row['id'] = $id;
                return self::payload($row, false);
            } catch (PDOException $e) {
                $lastError = $e;
                if (self::isDuplicateReference($e) && $attempt < 5) {
                    continue;
                }
                throw $e;
            }
        }

        throw $lastError ?? new \RuntimeException('A unique bus booking reference could not be reserved.');
    }

    protected static function validate(array $data): array
    {
        $name = trim((string)($data['customer_name'] ?? $data['name'] ?? $data['fullName'] ?? ''));
        if ($name === '') {
            return self::invalid('Please enter your full name.');
        }
        if (self::length($name) > 120) {
            return self::invalid('Please enter a shorter name (120 characters or less).');
        }

        $phone = trim((string)($data['customer_phone'] ?? $data['phone'] ?? ''));
        $phoneError = self::phoneError($phone);
        if ($phoneError !== null) {
            return self::invalid($phoneError);
        }

        $email = strtolower(trim((string)($data['customer_email'] ?? $data['email'] ?? '')));
        if ($email !== '') {
            if (self::length($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $email)) {
                return self::invalid('Enter a valid email address, or leave the email field blank.');
            }
        }

        $from = trim((string)($data['departure_city'] ?? $data['from'] ?? ''));
        $to = trim((string)($data['destination_city'] ?? $data['to'] ?? ''));
        if ($from === '' || $to === '') {
            return self::invalid('Please enter both a departure city and a destination city.');
        }
        if (self::length($from) > 100 || self::length($to) > 100) {
            return self::invalid('Please use a shorter city name (100 characters or less).');
        }
        if (strcasecmp($from, $to) === 0) {
            return self::invalid('Departure and destination cannot be the same city.');
        }

        if (!array_key_exists('trip_type', $data) || trim((string)$data['trip_type']) === '') {
            $tripType = array_key_exists('trip_type', $data) ? '' : 'one-way';
            if ($tripType === '') {
                return self::invalid('Please choose a one-way or round-trip journey.');
            }
        } else {
            $tripType = strtolower(trim((string)$data['trip_type']));
        }
        if (!in_array($tripType, self::tripTypes(), true)) {
            return self::invalid('Please choose a one-way or round-trip journey.');
        }

        $departDate = trim((string)($data['departure_date'] ?? $data['depart'] ?? ''));
        if (!self::isCalendarDate($departDate)) {
            return self::invalid('Please choose a valid calendar date for departure.');
        }
        if ($departDate < date('Y-m-d')) {
            return self::invalid('Departure date cannot be in the past.');
        }

        $returnDate = trim((string)($data['return_date'] ?? $data['return'] ?? ''));
        if ($tripType === 'round-trip') {
            if (!self::isCalendarDate($returnDate)) {
                return self::invalid('Please choose a valid calendar date for the return journey.');
            }
            if ($returnDate < $departDate) {
                return self::invalid('Return date cannot be earlier than the departure date.');
            }
        } else {
            $returnDate = null;
        }

        $passengerSource = array_key_exists('passengers', $data) ? $data['passengers'] : ($data['seats'] ?? null);
        if ($passengerSource === null) {
            $passengers = 1;
        } else {
            $passengers = self::passengerCount($passengerSource);
            if ($passengers === null) {
                return self::invalid('Please choose between 1 and 20 passengers.');
            }
        }

        if (!array_key_exists('departure_time', $data) || trim((string)$data['departure_time']) === '') {
            $departTime = array_key_exists('departure_time', $data) ? '' : 'Morning (06:30 - 08:00 AM)';
            if ($departTime === '') {
                return self::invalid('Please choose a supported departure time.');
            }
        } else {
            $departTime = trim((string)$data['departure_time']);
        }
        if (!in_array($departTime, self::departureTimes(), true)) {
            return self::invalid('Please choose a supported departure time.');
        }

        if (!array_key_exists('coach_class', $data) || trim((string)$data['coach_class']) === '') {
            $coachClass = array_key_exists('coach_class', $data) ? '' : 'Executive Luxury AC Coach';
            if ($coachClass === '') {
                return self::invalid('Please choose a supported coach class.');
            }
        } else {
            $coachClass = trim((string)$data['coach_class']);
        }
        if (!in_array($coachClass, self::coachClasses(), true)) {
            return self::invalid('Please choose a supported coach class.');
        }

        if (!array_key_exists('preferred_operator', $data) || trim((string)$data['preferred_operator']) === '') {
            $operator = 'Any Verified Luxury Partner';
        } else {
            $operator = trim((string)$data['preferred_operator']);
        }
        if (self::length($operator) > 100 || !in_array($operator, self::operators(), true)) {
            return self::invalid('Please choose a supported coach operator.');
        }

        $notes = trim((string)($data['notes'] ?? $data['special_requests'] ?? ''));
        if (self::length($notes) > 1000) {
            return self::invalid('Please shorten your notes to 1,000 characters or less.');
        }

        $estimatedFare = RealtimeDataService::estimateBusFare($from, $to, $tripType, $passengers, $coachClass);

        return [
            'ok' => true,
            'error' => '',
            'fields' => [
                'customer_name' => $name,
                'customer_phone' => $phone,
                'customer_email' => $email !== '' ? $email : null,
                'trip_type' => $tripType,
                'departure_city' => $from,
                'destination_city' => $to,
                'departure_date' => $departDate,
                'return_date' => $returnDate,
                'departure_time' => $departTime,
                'passengers' => $passengers,
                'coach_class' => $coachClass,
                'preferred_operator' => $operator,
                'estimated_fare' => $estimatedFare,
                'notes' => $notes !== '' ? $notes : null,
            ],
        ];
    }

    protected static function findRecentDuplicate(Database $db, array $fields): ?array
    {
        return $db->selectOne(
            'SELECT * FROM bus_bookings
             WHERE customer_phone = :phone
               AND departure_city = :departure_city
               AND destination_city = :destination_city
               AND departure_date = :departure_date
               AND trip_type = :trip_type
               AND created_at >= :cutoff
             ORDER BY id DESC
             LIMIT 1',
            [
                ':phone' => $fields['customer_phone'],
                ':departure_city' => $fields['departure_city'],
                ':destination_city' => $fields['destination_city'],
                ':departure_date' => $fields['departure_date'],
                ':trip_type' => $fields['trip_type'],
                ':cutoff' => date('Y-m-d H:i:s', time() - 120),
            ]
        );
    }

    protected static function payload(array $row, bool $reused): array
    {
        $departDate = substr((string)$row['departure_date'], 0, 10);
        $returnRaw = $row['return_date'] ?? null;
        $returnDate = $returnRaw ? substr((string)$returnRaw, 0, 10) : null;
        $departTime = (string)($row['departure_time'] ?? '');
        $passengers = (int)$row['passengers'];
        $fare = (float)$row['estimated_fare'];
        $formattedFare = '₦' . number_format($fare);
        $formattedDepartDate = date('D, j M Y', strtotime($departDate));
        $formattedReturnDate = $returnDate ? date('D, j M Y', strtotime($returnDate)) : 'N/A (One-Way)';
        $name = (string)$row['customer_name'];
        $phone = (string)$row['customer_phone'];
        $email = (string)($row['customer_email'] ?? '');
        $from = (string)$row['departure_city'];
        $to = (string)$row['destination_city'];
        $tripType = (string)$row['trip_type'];
        $coachClass = (string)$row['coach_class'];
        $operator = (string)($row['preferred_operator'] ?? 'Any Verified Luxury Partner');
        $notes = (string)($row['notes'] ?? '');
        $ref = (string)$row['ref'];

        $whatsappNumber = function_exists('cj_whatsapp_number') ? cj_whatsapp_number() : '2348135196772';
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
        $lines[] = "• *Preferred Operator:* " . ($operator !== '' ? $operator : 'Any Verified Luxury Partner');
        $lines[] = "• *Estimated Total Fare:* {$formattedFare}";
        if ($notes !== '') {
            $lines[] = "";
            $lines[] = "💬 *Special Requests / Luggage:*";
            $lines[] = $notes;
        }
        $lines[] = "";
        $lines[] = "━━━━━━━━━━━━━━━━━━━━━━━━━━━━";
        $lines[] = "Hello CJ Travels, I would like to confirm bus availability and complete this booking.";
        $messageText = implode("\n", $lines);

        return [
            'success' => true,
            'reused' => $reused,
            'message' => $reused
                ? 'This enquiry is already saved. Use the WhatsApp button to contact our reservations desk. The message is prepared and has not been sent.'
                : 'Your bus enquiry has been saved. Use the WhatsApp button to contact our reservations desk. The message is prepared and has not been sent.',
            'booking' => [
                'id' => (int)($row['id'] ?? 0),
                'ref' => $ref,
                'customer_name' => $name,
                'customer_phone' => $phone,
                'customer_email' => $email,
                'trip_type' => $tripType,
                'departure_city' => $from,
                'destination_city' => $to,
                'departure_date' => $departDate,
                'return_date' => $returnDate,
                'departure_time' => $departTime,
                'passengers' => $passengers,
                'coach_class' => $coachClass,
                'preferred_operator' => $operator,
                'estimated_fare' => $fare,
                'notes' => $notes,
                'status' => (string)($row['status'] ?? 'Pending'),
                'formattedFare' => $formattedFare,
                'formattedDepartDate' => $formattedDepartDate,
                'formattedReturnDate' => $formattedReturnDate,
            ],
            'whatsappUrl' => 'https://wa.me/' . $whatsappNumber . '?text=' . rawurlencode($messageText),
            'messageText' => $messageText,
        ];
    }

    protected static function makeReference(): string
    {
        if (self::$referenceFactory instanceof \Closure) {
            $custom = strtoupper(trim((string)(self::$referenceFactory)()));
            if (preg_match('/^CJBUS-[A-Z0-9]{6,24}$/', $custom)) {
                return $custom;
            }
        }

        return 'CJBUS-' . strtoupper(bin2hex(random_bytes(8)));
    }

    protected static function phoneError(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($phone === '' || strlen($digits) < 10 || strlen($digits) > 15 || self::length($phone) > 25) {
            return 'Please enter a valid phone number (10 to 15 digits).';
        }
        return null;
    }

    protected static function passengerCount(mixed $raw): ?int
    {
        if (is_int($raw)) {
            $count = $raw;
        } elseif (is_string($raw) && preg_match('/^\d+$/', trim($raw))) {
            $count = (int)trim($raw);
        } else {
            return null;
        }

        if ($count < 1 || $count > 20) {
            return null;
        }
        return $count;
    }

    protected static function isCalendarDate(string $value): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }
        [$year, $month, $day] = array_map('intval', explode('-', $value));
        return checkdate($month, $day, $year);
    }

    protected static function length(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }

    protected static function invalid(string $error): array
    {
        return ['ok' => false, 'error' => $error, 'fields' => []];
    }

    protected static function isDuplicateReference(PDOException $e): bool
    {
        $state = (string)($e->errorInfo[0] ?? '');
        $code = (int)($e->errorInfo[1] ?? 0);
        return $state === '23000' || $code === 1062;
    }

    protected static function isMissingTable(PDOException $e): bool
    {
        return (int)($e->errorInfo[1] ?? 0) === 1146;
    }

    protected static function logFailure(Throwable $e): void
    {
        $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $line = date('c') . ' ' . $e::class . ' ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
        @file_put_contents($dir . DIRECTORY_SEPARATOR . 'bus-bookings.log', $line, FILE_APPEND);
    }

    protected static function schemaFile(): string
    {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR . '2026_11_25_000001_create_bus_bookings_table.sql';
    }

    protected static function markerPath(): string
    {
        if (self::$markerPathOverride !== null) {
            return self::$markerPathOverride;
        }

        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'bus_bookings.schema';
    }

    protected static function writeMarker(): void
    {
        $marker = self::markerPath();
        $dir = dirname($marker);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
            if (!is_dir($dir)) {
                throw new \RuntimeException('The bus booking schema marker directory could not be created.');
            }
        }

        $written = @file_put_contents($marker, date('c'));
        if ($written === false) {
            throw new \RuntimeException('The bus booking schema marker could not be written.');
        }
    }

    protected static function clearMarker(): void
    {
        $marker = self::markerPath();
        if (!is_file($marker)) {
            return;
        }

        if (!@unlink($marker) || is_file($marker)) {
            throw new \RuntimeException('The bus booking schema marker could not be removed.');
        }
    }

    protected static function statuses(): array
    {
        return ['Pending', 'Confirmed', 'Processing', 'Cancelled'];
    }

    protected static function tripTypes(): array
    {
        return ['one-way', 'round-trip'];
    }

    protected static function departureTimes(): array
    {
        return [
            'Morning (06:30 - 08:00 AM)',
            'Mid-Day (11:00 AM - 01:00 PM)',
            'Night Express (07:00 - 09:00 PM)',
        ];
    }

    protected static function coachClasses(): array
    {
        return [
            'Executive Luxury AC Coach',
            'VIP First-Class Coach',
            'Standard Comfort AC Bus',
        ];
    }

    protected static function operators(): array
    {
        return [
            'Any Verified Luxury Partner',
            'GIGM - God is Good Motors',
            'ABC Transport Plc',
            'Chisco Transport',
            'GUO Transport',
            'Young Shall Grow Motors',
            'Peace Mass Transit',
        ];
    }
}
