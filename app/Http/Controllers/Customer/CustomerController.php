<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Core\Response;
use App\Models\User;
use App\Services\RealtimeDataService;

abstract class CustomerController extends Controller
{
    protected function customerView(string $view, array $data = []): Response
    {
        $user = User::current();
        $customerData = RealtimeDataService::getCustomerData($user->id ?? 1);

        return $this->view('customer/' . $view, array_merge([
            'user' => $user,
            'customerData' => $customerData,
            'notificationsCount' => 3,
        ], $data));
    }
}
