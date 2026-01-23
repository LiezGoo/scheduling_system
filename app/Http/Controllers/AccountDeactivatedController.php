<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * AccountDeactivatedController
 *
 * Handles the display of the account deactivation notice.
 * This page is shown when a deactivated user tries to access the system.
 */
class AccountDeactivatedController extends Controller
{
    /**
     * Show the account deactivation notice.
     *
     * This route is explicitly excluded from the EnforceActiveUser middleware
     * to allow guests to see the deactivation message without redirect loops.
     *
     * @return \Illuminate\View\View
     */
    public function show()
    {
        return view('account-deactivated');
    }
}
