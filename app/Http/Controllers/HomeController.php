<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Email;
use App\Models\Tracker;

class HomeController extends Controller
{
    public function dashboard()
    {
        $dashboard = $this->report();
        
        return view('page.dashboard', compact('dashboard'));
    }

    public function mailing()
    {
        return view('page.mailing');
    }

    private function report()
    {
        $sent = Tracker::where('sent', true)->count();

        $opened = Tracker::where('opened', true)->count();

        $clicks = Tracker::where('clicks', true)->count();

        $unsubscribed = Tracker::where('unsubscribed', true)->count();

        $stats = Email::selectRaw('lang, COUNT(*) as email_count')
            ->groupBy('lang')
            ->pluck('email_count', 'lang')
            ->toArray();
        
        $emails = Email::all()->count();
        
        return json_decode(json_encode([
            'sent'  => $sent,
            'opened' => $opened,
            'clicks' => $clicks,
            'unsubscribed' => $unsubscribed,
            'stats' => $stats,
            'emails' => $emails
        ]));
    }
}
