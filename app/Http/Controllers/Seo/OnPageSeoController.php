<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateOnPageSeoJob;
use App\Models\SeoRun;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class OnPageSeoController extends Controller
{
    use SeoPayloadTrait;

    public function index()
    {
        $setupRequired = !$this->seoTablesReady();
        $project = $setupRequired ? null : $this->defaultProject();
        $features = config('seo.features.on_page', []);
        $runs = $setupRequired ? collect() : SeoRun::where('module', 'on_page')->latest()->limit(15)->get();

        return view('backend.seo.on_page.index', compact('project', 'features', 'runs', 'setupRequired'));
    }

    public function run(Request $request)
    {
        if (!$this->seoTablesReady()) {
            flash(translate('Run SEO migrations first to enable the AI SEO Suite.'))->warning();
            return redirect()->route('admin.seo_on_page.index');
        }

        $request->validate([
            'feature' => 'required|string',
        ]);

        $project = $this->defaultProject();
        $payload = $this->buildPayload($request);
        $provider = $request->provider ?: get_setting('seo_suite_default_provider', config('seo.default_provider', 'openai'));
        
        $run = SeoRun::create([
            'project_id' => $project->id,
            'module' => 'on_page',
            'feature' => $request->feature,
            'provider' => $provider,
            'status' => 'queued',
            'url' => Arr::get($payload, 'url'),
            'input_payload' => $payload,
        ]);

        GenerateOnPageSeoJob::dispatch($run->id);

        flash(translate('On-Page SEO task queued successfully'))->success();
        return redirect()->route('admin.seo_on_page.index');
    }
}
