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
                'phone' => $row['phone'] ?? '+234 812 345 6789',
                'country' => $row['country'] ?? 'Nigeria',
                'city' => $row['city'] ?? 'Lagos',
                'memberSince' => date('F Y', strtotime($row['created_at'] ?? 'now')),
                'tier' => $row['tier'] ?? 'Gold member',
                'passport' => $row['passport_number'] ?? 'A12984572',
                'issuingCountry' => 'Nigeria (NGA)',
                'passportExpiry' => $row['passport_expiry'] ?? '2031-08-14',
                'frequentFlyer' => 'BA-78923410'
            ]);
        }

        return new self([
            'id' => 1,
            'name' => 'Adaeze Okonkwo',
            'firstName' => 'Adaeze',
            'lastName' => 'Okonkwo',
            'initials' => 'AO',
            'email' => 'adaeze.okonkwo@example.com',
            'phone' => '+234 812 345 6789',
            'country' => 'Nigeria',
            'city' => 'Lagos',
            'memberSince' => 'January 2024',
            'tier' => 'Gold member',
            'passport' => 'A12984572',
            'issuingCountry' => 'Nigeria (NGA)',
            'passportExpiry' => '2031-08-14',
            'frequentFlyer' => 'BA-78923410'
        ]);
    }
}
