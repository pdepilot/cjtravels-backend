<?php

namespace App\Http\Controllers\Customer;

use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Services\RealtimeDataService;

class ProfileController extends CustomerController
{
    public function index(): Response
    {
        return $this->customerView('profile', [
            'title' => 'My Profile — Customer Dashboard — Travelbookings',
            'page' => 'profile'
        ]);
    }

    public function update(Request $request): Response
    {
        RealtimeDataService::ensureUserSchema();
        $user = User::current();
        $ok = RealtimeDataService::updateRecord('customer', $user->id ?? 0, [
            'firstName' => $request->input('firstName', $user->firstName ?? ''),
            'lastName' => $request->input('lastName', $user->lastName ?? ''),
            'email' => $request->input('email', $user->email ?? ''),
            'phone' => $request->input('phone', $user->phone ?? ''),
            'country' => $request->input('country', $user->country ?? ''),
            'city' => $request->input('city', $user->city ?? ''),
            'passport' => $request->input('passport', $user->passport ?? ''),
            'passportExpiry' => $request->input('passportExpiry', $user->passportExpiry ?? ''),
        ]);

        $fresh = User::current();

        return Response::json([
            'success' => $ok,
            'message' => $ok
                ? 'Profile saved. The admin customer directory now shows these details.'
                : (RealtimeDataService::lastError() ?: 'Could not save the profile.'),
            'error' => $ok ? null : (RealtimeDataService::lastError() ?: 'Could not save the profile.'),
            'customer' => [
                'id' => $fresh->id ?? null,
                'name' => $fresh->name ?? '',
                'firstName' => $fresh->firstName ?? '',
                'lastName' => $fresh->lastName ?? '',
                'initials' => $fresh->initials ?? '',
                'email' => $fresh->email ?? '',
                'phone' => $fresh->phone ?? '',
                'country' => $fresh->country ?? '',
                'city' => $fresh->city ?? '',
                'tier' => $fresh->tier ?? '',
                'status' => $fresh->status ?? 'Active',
                'passport' => $fresh->passport ?? '',
                'passportExpiry' => $fresh->passportExpiry ?? '',
            ],
        ], $ok ? 200 : 422);
    }
}
