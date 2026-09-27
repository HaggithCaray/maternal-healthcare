<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * Landing page for the current user: staff dashboard or patient portal.
     */
    protected function homeRoute(): string
    {
        return auth()->user()?->isAdmin() ? 'dashboard' : 'patient.portal';
    }
}
