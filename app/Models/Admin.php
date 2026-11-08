<?php

namespace App\Models;

use App\Core\Database;
use DateTimeImmutable;

class Admin extends Model
{
    public const ROLE_SUPER_ADMIN = 'Super Administrator';
    public const ROLE_ADMIN = 'Administrator';
    public const ROLE_BOOKING_OFFICER = 'Booking Officer';
    public const ROLE_CUSTOMER_SUPPORT = 'Customer Support';
    public const ROLE_FINANCE_OFFICER = 'Finance Officer';

    public const ROLES = [
        self::ROLE_SUPER_ADMIN,
        self::ROLE_ADMIN,
        self::ROLE_BOOKING_OFFICER,
        self::ROLE_CUSTOMER_SUPPORT,
        self::ROLE_FINANCE_OFFICER,
    ];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_SUSPENDED = 'suspended';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
        self::STATUS_SUSPENDED,
    ];

    protected static string $table = 'administrators';

    public static function findById(int $id): ?self
    {
        $db = Database::getInstance();
        $row = $db->selectOne("SELECT * FROM " . self::$table . " WHERE id = :id LIMIT 1", [':id' => $id]);
        return $row ? new self($row) : null;
    }

    public static function findByEmail(string $email): ?self
    {
        $db = Database::getInstance();
        $row = $db->selectOne("SELECT * FROM " . self::$table . " WHERE LOWER(email) = LOWER(:email) LIMIT 1", [
            ':email' => trim($email),
        ]);
        return $row ? new self($row) : null;
    }

    public static function findByUsername(string $username): ?self
    {
        $db = Database::getInstance();
        $row = $db->selectOne("SELECT * FROM " . self::$table . " WHERE LOWER(username) = LOWER(:username) LIMIT 1", [
            ':username' => trim($username),
        ]);
        return $row ? new self($row) : null;
    }

    public static function findByPhone(string $phone): ?self
    {
        $clean = trim($phone);
        if (empty($clean)) {
            return null;
        }
        $db = Database::getInstance();
        $row = $db->selectOne("SELECT * FROM " . self::$table . " WHERE phone = :phone LIMIT 1", [
            ':phone' => $clean,
        ]);
        return $row ? new self($row) : null;
    }

    public static function findByUsernameOrEmail(string $identifier): ?self
    {
        $clean = trim($identifier);
        $db = Database::getInstance();
        $row = $db->selectOne(
            "SELECT * FROM " . self::$table . " WHERE LOWER(email) = LOWER(:email) OR LOWER(username) = LOWER(:username) OR phone = :phone LIMIT 1",
            [':email' => $clean, ':username' => $clean, ':phone' => $clean]
        );
        return $row ? new self($row) : null;
    }

    /**
     * @return self[]
     */
    public static function all(): array
    {
        $db = Database::getInstance();
        $rows = $db->select("SELECT * FROM " . self::$table . " ORDER BY id ASC");
        return array_map(fn($row) => new self($row), $rows);
    }

    public static function create(array $data): self
    {
        $db = Database::getInstance();

        $now = date('Y-m-d H:i:s');
        $insertData = [
            'name' => trim($data['name'] ?? ''),
            'username' => !empty($data['username']) ? trim($data['username']) : null,
            'email' => strtolower(trim($data['email'] ?? '')),
            'phone' => !empty($data['phone']) ? trim($data['phone']) : null,
            'password' => isset($data['password_hashed']) && $data['password_hashed']
                ? $data['password']
                : password_hash($data['password'], PASSWORD_DEFAULT),
            'role' => $data['role'] ?? self::ROLE_ADMIN,
            'status' => $data['status'] ?? self::STATUS_ACTIVE,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $id = $db->insert(self::$table, $insertData);
        $insertData['id'] = (int)$id;

        return new self($insertData);
    }

    public function isActive(): bool
    {
        return ($this->attributes['status'] ?? '') === self::STATUS_ACTIVE;
    }

    public function isInactive(): bool
    {
        return ($this->attributes['status'] ?? '') === self::STATUS_INACTIVE;
    }

    public function isSuspended(): bool
    {
        return ($this->attributes['status'] ?? '') === self::STATUS_SUSPENDED;
    }

    public function isSuperAdmin(): bool
    {
        return ($this->attributes['role'] ?? '') === self::ROLE_SUPER_ADMIN;
    }

    public function isAdmin(): bool
    {
        return in_array($this->attributes['role'] ?? '', [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN], true);
    }

    public function hasRole(string|array $roles): bool
    {
        $currentRole = $this->attributes['role'] ?? '';
        if ($currentRole === self::ROLE_SUPER_ADMIN) {
            return true; // Super Admin has access to all roles
        }

        if (is_array($roles)) {
            return in_array($currentRole, $roles, true);
        }

        return $currentRole === $roles;
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $role = $this->attributes['role'] ?? '';
        $permissions = [
            self::ROLE_ADMIN => [
                'dashboard.view', 'customers.view', 'customers.manage',
                'flights.view', 'flights.manage', 'hotels.view', 'hotels.manage',
                'loans.view', 'destinations.view', 'transactions.view',
                'reports.view', 'settings.view',
            ],
            self::ROLE_BOOKING_OFFICER => [
                'dashboard.view', 'flights.view', 'flights.manage',
                'hotels.view', 'hotels.manage', 'customers.view', 'destinations.view',
            ],
            self::ROLE_CUSTOMER_SUPPORT => [
                'dashboard.view', 'customers.view', 'loans.view',
                'flights.view', 'hotels.view',
            ],
            self::ROLE_FINANCE_OFFICER => [
                'dashboard.view', 'loans.view', 'loans.manage',
                'transactions.view', 'transactions.manage', 'reports.view',
            ],
        ];

        $allowed = $permissions[$role] ?? [];
        return in_array($permission, $allowed, true);
    }

    public function getInitials(): string
    {
        $name = trim($this->attributes['name'] ?? 'Admin');
        $words = preg_split('/\s+/', $name);
        $initials = '';
        foreach ($words as $w) {
            if (!empty($w)) {
                $initials .= strtoupper($w[0]);
            }
            if (strlen($initials) >= 2) {
                break;
            }
        }
        return $initials ?: 'AD';
    }

    public function updateLastLogin(string $ip): void
    {
        $db = Database::getInstance();
        $now = date('Y-m-d H:i:s');
        $db->update(
            self::$table,
            [
                'last_login_at' => $now,
                'last_login_ip' => $ip,
                'updated_at' => $now,
            ],
            'id = :id',
            [':id' => $this->attributes['id']]
        );

        $this->attributes['last_login_at'] = $now;
        $this->attributes['last_login_ip'] = $ip;
        $this->attributes['updated_at'] = $now;
    }

    public function updatePassword(string $newPassword): void
    {
        $db = Database::getInstance();
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $now = date('Y-m-d H:i:s');

        $db->update(
            self::$table,
            [
                'password' => $hashed,
                'updated_at' => $now,
            ],
            'id = :id',
            [':id' => $this->attributes['id']]
        );

        $this->attributes['password'] = $hashed;
        $this->attributes['updated_at'] = $now;
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->attributes['password'] ?? '');
    }

    public function updateProfile(array $data): void
    {
        $db = Database::getInstance();
        $now = date('Y-m-d H:i:s');
        $updateData = ['updated_at' => $now];

        if (array_key_exists('name', $data)) {
            $updateData['name'] = trim((string)$data['name']);
            $this->attributes['name'] = $updateData['name'];
        }
        if (array_key_exists('email', $data)) {
            $updateData['email'] = strtolower(trim((string)$data['email']));
            $this->attributes['email'] = $updateData['email'];
        }
        if (array_key_exists('phone', $data)) {
            $cleanPhone = trim((string)$data['phone']);
            $updateData['phone'] = !empty($cleanPhone) ? $cleanPhone : null;
            $this->attributes['phone'] = $updateData['phone'];
        }
        if (array_key_exists('username', $data)) {
            $cleanUsername = trim((string)$data['username']);
            $updateData['username'] = !empty($cleanUsername) ? $cleanUsername : null;
            $this->attributes['username'] = $updateData['username'];
        }

        $db->update(self::$table, $updateData, 'id = :id', [':id' => $this->attributes['id']]);
    }

    public static function isEmailTakenByOther(string $email, int $excludeId): bool
    {
        $db = Database::getInstance();
        $row = $db->selectOne(
            "SELECT id FROM " . self::$table . " WHERE LOWER(email) = LOWER(:email) AND id != :id LIMIT 1",
            [':email' => trim($email), ':id' => $excludeId]
        );
        return $row !== null;
    }

    public static function isPhoneTakenByOther(string $phone, int $excludeId): bool
    {
        $clean = trim($phone);
        if (empty($clean)) {
            return false;
        }
        $db = Database::getInstance();
        $row = $db->selectOne(
            "SELECT id FROM " . self::$table . " WHERE phone = :phone AND id != :id LIMIT 1",
            [':phone' => $clean, ':id' => $excludeId]
        );
        return $row !== null;
    }

    public static function isUsernameTakenByOther(string $username, int $excludeId): bool
    {
        $clean = trim($username);
        if (empty($clean)) {
            return false;
        }
        $db = Database::getInstance();
        $row = $db->selectOne(
            "SELECT id FROM " . self::$table . " WHERE LOWER(username) = LOWER(:username) AND id != :id LIMIT 1",
            [':username' => $clean, ':id' => $excludeId]
        );
        return $row !== null;
    }

    public function setStatus(string $status): void
    {
        if (!in_array($status, self::STATUSES, true)) {
            throw new \InvalidArgumentException("Invalid administrator status: {$status}");
        }

        $db = Database::getInstance();
        $now = date('Y-m-d H:i:s');
        $db->update(
            self::$table,
            ['status' => $status, 'updated_at' => $now],
            'id = :id',
            [':id' => $this->attributes['id']]
        );

        $this->attributes['status'] = $status;
        $this->attributes['updated_at'] = $now;
    }
}

