<?php

namespace App\Http\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\MailService;

class EmailController extends AdminController
{
    public function index(): Response
    {
        return $this->adminView('emails', [
            'title' => 'Email center — CJ Travels Admin',
            'page' => 'emails',
            'customers' => MailService::customers(),
            'mailSettings' => MailService::settingsForForm(),
            'sentEmails' => MailService::recent(),
            'success' => Session::getFlash('success'),
            'error' => Session::getFlash('error'),
        ]);
    }

    public function send(Request $request): Response
    {
        if (!Auth::admin()) {
            return $this->redirect('/admin/login');
        }

        $email = strtolower(trim((string)$request->input('email', '')));
        $customerId = (int)$request->input('customer_id', 0);
        $name = '';
        if ($email === '' && $customerId > 0) {
            $customer = Database::getInstance()->selectOne(
                'SELECT name, email FROM users WHERE id = :id LIMIT 1',
                [':id' => $customerId]
            );
            if ($customer) {
                $email = strtolower(trim((string)$customer['email']));
                $name = (string)$customer['name'];
            }
        } elseif ($customerId > 0) {
            $customer = Database::getInstance()->selectOne(
                'SELECT name FROM users WHERE id = :id LIMIT 1',
                [':id' => $customerId]
            );
            $name = (string)($customer['name'] ?? '');
        }

        $result = MailService::sendToCustomer(
            $email,
            $name,
            (string)$request->input('subject', ''),
            (string)$request->input('message', '')
        );

        if (!empty($result['success'])) {
            Session::flash('success', $result['message'] ?? 'The email has been sent.');
        } else {
            Session::flash('error', $result['error'] ?? 'The email could not be sent.');
        }

        return $this->redirect('/admin/emails');
    }

    public function saveSettings(Request $request): Response
    {
        if (!Auth::admin()) {
            return $this->redirect('/admin/login');
        }

        $result = MailService::saveSettings($request->all());
        if (!empty($result['success'])) {
            Session::flash('success', $result['message'] ?? 'Email settings have been saved.');
        } else {
            Session::flash('error', $result['error'] ?? 'Could not save the email settings.');
        }

        return $this->redirect('/admin/emails#mail-settings');
    }
}
