<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Email;
use App\Models\Tracker;

class EmailTrackerController extends Controller
{
    public function open($email)
    {
        Email::with(['tracker' => function($query) {
            $query->where('email', $email)->update('opened', true); 
        }]);
    }

    public function click($email)
    {
        Email::with(['tracker' => function($query) {
            $query->where('email', $email)->update('clicks', true); 
        }]);
        return to_route(config('app.url'));
    }

    public function unsubscribe($email)
    {
        Email::with(['tracker' => function($query) {
            $query->where('email', $email)->update('unsubscribed', true); 
        }]);
    }
}
