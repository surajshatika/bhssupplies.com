<?php

namespace App\Http\Controllers;

use App\Services\Marketing\CampaignService;
use App\Services\Marketing\EventStore;
use Illuminate\Http\Request;

class ShortLinkController extends Controller
{
    public function redirect(Request $request, CampaignService $svc, EventStore $store, string $code)
    {
        $campaign = $svc->findByCode($code);

        if (!$campaign) {
            return redirect()->route('home')->with('warning', 'Link not found');
        }

        // Capture a ShortLinkClick event for attribution
        try {
            $store->record('ShortLinkClick', [
                'campaign_id'   => $campaign['id'],
                'campaign_code' => $campaign['short_code'],
                'destination'   => $campaign['destination_url'],
            ]);
        } catch (\Throwable $e) {
            // never block redirect
        }

        return redirect($campaign['destination_url']);
    }
}
