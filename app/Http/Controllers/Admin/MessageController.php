<?php

namespace App\Http\Controllers\Admin;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\RealtimeDataService;

class MessageController extends AdminController
{
    public function index(): Response
    {
        return $this->adminView('messages', [
            'title' => 'Contact messages — CJ Travels Admin',
            'page' => 'messages',
            'messages' => RealtimeDataService::contactMessages(),
            'success' => Session::getFlash('success'),
            'error' => Session::getFlash('error'),
        ]);
    }

    public function destroy(Request $request): Response
    {
        if (!Auth::admin()) {
            return $this->redirect('/admin/login');
        }

        $result = RealtimeDataService::deleteContactMessage((string)$request->input('ref', ''));
        if (!empty($result['success'])) {
            Session::flash('success', $result['message'] ?? 'The message has been deleted.');
        } else {
            Session::flash('error', $result['error'] ?? 'Could not delete that message.');
        }

        return $this->redirect('/admin/messages');
    }
}
