<?php

namespace App\Models;

use App\Core\Database;

class User extends Model
{
    protected static string $table = 'users';

    public static function current(): self
    {
        $db = Database::getInstance();
        $row = $db->selectOne("SELECT * FROM " . self::$table . " ORDER BY id ASC LIMIT 1");

        if ($row) {
            $name = trim($row['name'] ?: ($row['first_name'] . ' ' . $row['last_name']));
            $initials = strtoupper(substr($row['first_name'] ?? 'U', 0, 1) . substr($row['last_name'] ?? 'S', 0, 1));
            return new self([
                'id' => (int)$row['id'],
                'name' => $name,
                'firstName' => $row['first_name'] ?? $name,
                'lastName' => $row['last_name'] ?? '',
                'initials' => $initials,
                'email' => $row['email'],
                'phone' => $row['phone'] ?? '',
                'country' => $row['country'] ?? '',
                'city' => $row['city'] ?? '',
                'memberSince' => !empty($row['created_at']) ? date('F Y', strtotime($row['created_at'])) : '',
                'tier' => $row['tier'] ?? '',
                'status' => strtolower((string)($row['status'] ?? 'active')) === 'inactive' ? 'Inactive' : 'Active',
                'passport' => $row['passport_number'] ?? '',
                'issuingCountry' => $row['country'] ?? '',
                'passportExpiry' => $row['passport_expiry'] ?? '',
                'frequentFlyer' => $row['frequent_flyer'] ?? '',
            ]);
        }

        return new self([
            'id' => 0,
            'name' => '',
            'firstName' => '',
            'lastName' => '',
            'initials' => '',
            'email' => '',
            'phone' => '',
            'country' => '',
            'city' => '',
            'memberSince' => '',
            'tier' => '',
            'passport' => '',
            'issuingCountry' => '',
            'passportExpiry' => '',
            'frequentFlyer' => '',
        ]);
    }
}
