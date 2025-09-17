<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Email;
use App\Models\EmailMeta;
use App\Repositories\EmailRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EmailTrackingController extends Controller
{
    public function __construct(
        private EmailRepository $emailRepository
    ) {}

    public function track_pixel(Request $request, string $token): Response
    {
        $email = $this->emailRepository->findByTrackingToken($token);
        
        if ($email) {
            EmailMeta::trackOpened($email, [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'opened_at' => now()->toISOString(),
            ]);
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
            EmailMeta::trackClicked($email, [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'clicked_url' => $originalUrl,
                'clicked_at' => now()->toISOString(),
            ]);

            return response('Lien valide', 201);
        }

        return response('Lien invalide', 404);
    }

    public function track_unsubscribe(Request $request, string $token): Response
    {
        $email = $this->emailRepository->findByTrackingToken($token);
        $originalUrl = base64_decode($request->get('url', ''));

        if ($email && $originalUrl) {
            EmailMeta::trackUnsubscribed($email, [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'clicked_url' => $originalUrl,
                'clicked_at' => now()->toISOString(),
            ]);

            return response('Lien valide', 200);
        }

        return response('Lien invalide', 404);
    }
}
