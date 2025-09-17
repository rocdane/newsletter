<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CampaignCollection;
use App\Http\Resources\CampaignResource;
use App\Models\Campaign;
use App\Services\CampaignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function __construct(
        private CampaignService $campaignService
    ) {}

    /**
     * Display a listing of email campaigns
     */
    public function index(Request $request): CampaignCollection
    {
        $campaigns = Campaign::query()
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($request->search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            })
            ->withCount(['emails as total_emails_count'])
            ->orderBy($request->sort ?? 'created_at', $request->direction ?? 'desc')
            ->paginate($request->per_page ?? 15);

        return new CampaignCollection($campaigns);
    }

    /**
     * Display the specified email campaign
     */
    public function show(Campaign $campaign): CampaignResource
    {
        $campaign->load(['emails.suscriber', 'emails.metas']);

        return new CampaignResource($campaign);
    }

    /**
     * Get campaign statistics
     */
    public function stats(Campaign $campaign): JsonResponse
    {
        $stats = $this->campaignService->getCampaignStats($campaign);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get campaign emails with details
     */
    public function emails(Request $request, Campaign $campaign): JsonResponse
    {
        $emails = $campaign->emails()
            ->with(['subscriber', 'metas'])
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($request->search, function ($query, $search) {
                return $query->whereHas('subscriber', function ($q) use ($search) {
                    $q->where('email', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->orderBy($request->sort ?? 'created_at', $request->direction ?? 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $emails,
        ]);
    }

    /**
     * Get campaign performance metrics
     */
    public function performance(Campaign $campaign): JsonResponse
    {
        $emails = $campaign->emails()->with('metas')->get();

        $performance = [
            'delivery_rate' => $this->calculateDeliveryRate($emails),
            'open_rate' => $this->calculateOpenRate($emails),
            'click_rate' => $this->calculateClickRate($emails),
            'bounce_rate' => $this->calculateBounceRate($emails),
            'unsubscribe_rate' => $this->calculateUnsubscribeRate($emails),
            'engagement_timeline' => $this->getEngagementTimeline($emails),
        ];

        return response()->json([
            'success' => true,
            'data' => $performance,
        ]);
    }

    /**
     * Get dashboard overview statistics
     */
    public function dashboard(): JsonResponse
    {
        $stats = $this->campaignService->getDashboardStats();

        // Add additional dashboard metrics
        $recentCampaigns = Campaign::latest()
            ->take(5)
            ->get(['id', 'name', 'status', 'created_at', 'sent_emails', 'total_emails']);

        $statusDistribution = Campaign::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return response()->json([
            'success' => true,
            'data' => [
                'overview' => $stats,
                'recent_campaigns' => $recentCampaigns,
                'status_distribution' => $statusDistribution,
            ],
        ]);
    }

    /**
     * Delete a campaign
     */
    public function destroy(Campaign $campaign): JsonResponse
    {
        // Check if campaign can be deleted (not processing)
        if ($campaign->status === 'processing') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a campaign that is currently processing',
            ], 422);
        }

        $campaign->emails()->delete(); // Delete related emails
        $campaign->delete();

        return response()->json([
            'success' => true,
            'message' => 'Campaign deleted successfully',
        ]);
    }

    // Helper methods for performance calculations
    private function calculateDeliveryRate($emails): float
    {
        $total = $emails->count();
        if ($total === 0) {
            return 0;
        }

        $delivered = $emails->where('status', 'sent')->count();

        return round(($delivered / $total) * 100, 2);
    }

    private function calculateOpenRate($emails): float
    {
        $sent = $emails->where('status', 'sent')->count();
        if ($sent === 0) {
            return 0;
        }

        $opened = $emails->filter(function ($email) {
            return $email->metas->where('type', 'opened')->count() > 0;
        })->count();

        return round(($opened / $sent) * 100, 2);
    }

    private function calculateClickRate($emails): float
    {
        $sent = $emails->where('status', 'sent')->count();
        if ($sent === 0) {
            return 0;
        }

        $clicked = $emails->filter(function ($email) {
            return $email->metas->where('type', 'clicked')->count() > 0;
        })->count();

        return round(($clicked / $sent) * 100, 2);
    }

    private function calculateBounceRate($emails): float
    {
        $total = $emails->count();
        if ($total === 0) {
            return 0;
        }

        $bounced = $emails->where('status', 'failed')->count();

        return round(($bounced / $total) * 100, 2);
    }

    private function calculateUnsubscribeRate($emails): float
    {
        $sent = $emails->where('status', 'sent')->count();
        if ($sent === 0) {
            return 0;
        }

        $unsubscribed = $emails->filter(function ($email) {
            return $email->metas->where('type', 'unsubscribed')->count() > 0;
        })->count();

        return round(($unsubscribed / $sent) * 100, 2);
    }

    private function getEngagementTimeline($emails): array
    {
        $timeline = [];
        $metas = $emails->flatMap->metas;

        foreach ($metas as $meta) {
            $date = $meta->created_at->format('Y-m-d');
            if (! isset($timeline[$date])) {
                $timeline[$date] = [
                    'date' => $date,
                    'delivered' => 0,
                    'opened' => 0,
                    'clicked' => 0,
                    'unsubscribed' => 0,
                ];
            }
            $timeline[$date][$meta->type]++;
        }

        return array_values($timeline);
    }
}
