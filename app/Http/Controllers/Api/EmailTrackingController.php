<?php

namespace App\Http\Controllers\Api;

use App\Models\Email;
use App\Repositories\EmailRepository;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EmailTrackingController extends Controller
{
    public function __construct(private EmailRepository $emailRepository) {}

    public function track_pixel(Request $request, string $token): Response
    {
        $email = $this->emailRepository->findByTrackingToken($token);
        
        if ($email) {
            $email->markAsOpened();
        }

        // Retourner un pixel transparent 1x1
        $pixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        
        return response($pixel, 200)
            ->header('Content-Type', 'image/gif')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function track_click(Request $request, string $token): Response
    {
        $email = $this->emailRepository->findByTrackingToken($token);
        $originalUrl = base64_decode($request->get('url', ''));

        if ($email && $originalUrl) {
            $email->markAsClicked();
            return response('Lien valide', 201);
        }

        return response('Lien invalide', 404);
    }

    public function track_unsubscribe(Request $request, string $token): Response
    {
        $email = $this->emailRepository->findByTrackingToken($token);
        $originalUrl = base64_decode($request->get('url', ''));

        if ($email && $originalUrl) {
            $email->subscriber->markAsUnsubscribed();
            return response('Lien valide', 200);
        }

        return response('Lien invalide', 404);
    }
}
