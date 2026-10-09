<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\SeoBacklink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class BacklinkController extends Controller
{
    /** Real Canadian/GTA listings worth claiming for a local B2B supplier. */
    protected const SEED_TARGETS = [
        ['Google Business Profile', 'https://www.google.com/business/', 'citation', 'Most important local listing. Keep NAP identical to the website footer.'],
        ['Bing Places for Business', 'https://www.bingplaces.com/', 'citation', 'Can import directly from Google Business Profile.'],
        ['Apple Business Connect', 'https://businessconnect.apple.com/', 'citation', 'Apple Maps / Siri listing.'],
        ['Yelp Canada', 'https://biz.yelp.ca/', 'directory', ''],
        ['YellowPages.ca', 'https://www.yellowpages.ca/', 'directory', 'Free basic listing; category: Plumbing/Heating Supplies.'],
        ['Canada411', 'https://www.canada411.ca/', 'directory', ''],
        ['411.ca', 'https://411.ca/', 'directory', ''],
        ['Better Business Bureau', 'https://www.bbb.org/', 'directory', 'Accreditation gives a strong trust link.'],
        ['Facebook Business Page', 'https://www.facebook.com/business/', 'citation', 'Website link in About section.'],
        ['LinkedIn Company Page', 'https://www.linkedin.com/', 'citation', 'Contractors/B2B buyers check LinkedIn.'],
        ['Mississauga Board of Trade', 'https://www.mbot.com/', 'association', 'Member directory listing with website link.'],
        ['HRAI (Heating, Refrigeration and Air Conditioning Institute of Canada)', 'https://www.hrai.ca/', 'association', 'Industry association member directory.'],
        ['CIPH (Canadian Institute of Plumbing & Heating)', 'https://www.ciph.com/', 'association', 'Industry association member directory.'],
        ['Knipex dealer / where-to-buy', 'https://www.knipex.com/', 'supplier', 'Ask the brand rep to list BHS as a Canadian dealer — manufacturer links are high-authority.'],
    ];

    public function index(Request $request)
    {
        if (!Schema::hasTable('seo_backlinks')) {
            flash(translate('Run php artisan migrate to enable the backlink tracker.'))->warning();
            return redirect()->route('admin.seo_off_page.index');
        }

        $query = SeoBacklink::query();
        if ($s = $request->get('status')) $query->where('status', $s);
        if ($t = $request->get('type')) $query->where('type', $t);
        if ($q = trim((string) $request->get('q'))) {
            $query->where(fn($w) => $w->where('site_name', 'like', "%$q%")->orWhere('domain', 'like', "%$q%")->orWhere('source_url', 'like', "%$q%"));
        }
        $backlinks = $query->orderByRaw("FIELD(status,'live','submitted','contacted','prospect','lost','rejected')")
            ->latest('updated_at')->paginate(30)->withQueryString();

        $statusCounts = SeoBacklink::selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');
        $live = SeoBacklink::where('status', 'live');
        $stats = [
            'live' => (clone $live)->count(),
            'live_domains' => (clone $live)->distinct('domain')->count('domain'),
            'follow' => (clone $live)->where('rel', 'follow')->count(),
            'lost_30d' => SeoBacklink::where('status', 'lost')->where('lost_at', '>=', now()->subDays(30))->count(),
            'targets' => SeoBacklink::whereIn('status', ['prospect', 'contacted', 'submitted'])->count(),
        ];

        // Anchor-text profile from verified live links only.
        $brandTerms = ['bhs', 'bhssupplies', 'bhs supplies', 'bhs wholesale'];
        $anchors = (clone $live)->pluck('anchor_text')->map(fn($a) => mb_strtolower(trim((string) $a)));
        $profile = ['Branded' => 0, 'Naked URL' => 0, 'Generic' => 0, 'Image' => 0, 'Keyword / other' => 0];
        foreach ($anchors as $a) {
            if ($a === '' || str_starts_with($a, '[img]')) $profile['Image']++;
            elseif (str_contains($a, 'bhssupplies.com') || str_starts_with($a, 'http') || str_starts_with($a, 'www.')) $profile['Naked URL']++;
            elseif (collect($brandTerms)->contains(fn($b) => str_contains($a, $b))) $profile['Branded']++;
            elseif (in_array($a, ['click here', 'here', 'website', 'visit website', 'read more', 'learn more', 'this site', 'visit'], true)) $profile['Generic']++;
            else $profile['Keyword / other']++;
        }

        return view('backend.seo.backlinks.index', compact('backlinks', 'statusCounts', 'stats', 'profile'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        SeoBacklink::create($data + ['source' => 'manual']);
        flash(translate('Backlink saved'))->success();
        return back();
    }

    public function update(Request $request, SeoBacklink $backlink)
    {
        $backlink->update($this->validated($request));
        flash(translate('Backlink updated'))->success();
        return back();
    }

    public function destroy(SeoBacklink $backlink)
    {
        $backlink->delete();
        flash(translate('Backlink removed'))->success();
        return back();
    }

    public function seed()
    {
        $added = 0;
        foreach (self::SEED_TARGETS as [$name, $url, $type, $notes]) {
            $domain = SeoBacklink::domainOf($url);
            if (SeoBacklink::where('domain', $domain)->exists()) continue;
            SeoBacklink::create(['site_name' => $name, 'domain' => $domain, 'type' => $type, 'status' => 'prospect',
                'submit_url' => $url, 'notes' => $notes, 'source' => 'seed']);
            $added++;
        }
        flash($added . ' ' . translate('local citation targets added'))->success();
        return back();
    }

    /** Import Google Search Console → Links → "Latest links" / "Top linking sites" CSV export. */
    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:5120']);
        $fh = fopen($request->file('file')->getRealPath(), 'r');
        $header = array_map(fn($h) => mb_strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h))), fgetcsv($fh) ?: []);
        $pageCol = collect($header)->search(fn($h) => str_contains($h, 'linking page') || $h === 'url' || str_contains($h, 'source'));
        $siteCol = collect($header)->search(fn($h) => $h === 'site' || str_contains($h, 'linking site') || str_contains($h, 'domain'));
        $col = $pageCol !== false ? $pageCol : ($siteCol !== false ? $siteCol : 0);

        $added = 0; $skipped = 0;
        while (($row = fgetcsv($fh)) !== false) {
            $val = trim((string) ($row[$col] ?? ''));
            $domain = SeoBacklink::domainOf($val);
            if ($val === '' || !str_contains($domain, '.')) { $skipped++; continue; }
            $isPage = $pageCol !== false;
            $source = $isPage ? (str_contains($val, '://') ? $val : 'https://' . $val) : null;
            $exists = SeoBacklink::where('domain', $domain)
                ->when($isPage, fn($q) => $q->where('source_url', $source), fn($q) => $q->whereNull('source_url'))->exists();
            if ($exists) { $skipped++; continue; }
            SeoBacklink::create(['site_name' => $domain, 'domain' => $domain, 'type' => 'other',
                'status' => $isPage ? 'submitted' : 'live', 'source_url' => $source, 'source' => 'gsc_import',
                'notes' => $isPage ? null : 'Imported from GSC Top linking sites (domain level).']);
            $added++;
        }
        fclose($fh);

        flash($added . ' ' . translate('links imported') . ', ' . $skipped . ' ' . translate('skipped') . '. ' . translate('Run "Verify now" to confirm each page.'))->success();
        return back();
    }

    public function verify()
    {
        Artisan::call('seo:verify-backlinks', ['--limit' => 40]);
        flash(trim(Artisan::output()))->success();
        return back();
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'site_name' => 'required|string|max:255',
            'type' => ['required', Rule::in(SeoBacklink::TYPES)],
            'status' => ['required', Rule::in(SeoBacklink::STATUSES)],
            'submit_url' => 'nullable|url|max:500',
            'source_url' => 'nullable|url|max:500',
            'target_url' => 'nullable|url|max:500',
            'anchor_text' => 'nullable|string|max:255',
            'contact' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ]);
        $data['domain'] = SeoBacklink::domainOf($data['source_url'] ?? $data['submit_url'] ?? $data['site_name']);
        return $data;
    }
}
