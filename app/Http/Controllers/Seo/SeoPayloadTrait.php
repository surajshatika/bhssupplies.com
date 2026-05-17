<?php

namespace App\Http\Controllers\Seo;

use App\Models\SeoProject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

trait SeoPayloadTrait
{
    protected function buildPayload(Request $request)
    {
        $payload = [
            'url' => $request->input('url'),
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'content' => $request->input('content'),
            'keyword' => $request->input('keyword'),
            'topic' => $request->input('topic'),
            'images' => $this->splitTextList($request->input('images')),
            'links' => $this->splitTextList($request->input('links')),
            'our_keywords' => $request->input('our_keywords'),
            'comp1_keywords' => $request->input('comp1_keywords'),
            'comp2_keywords' => $request->input('comp2_keywords'),
            'faq' => $this->faqPayload($request->input('faq')),
            'product_id' => $request->input('product_id'),
            'cta' => $request->input('cta'),
            'anchor_text' => $request->input('anchor_text'),
            'link' => $request->input('link'),
            'contact_name' => $request->input('contact_name'),
            'target_site' => $request->input('target_site'),
        ];

        if ($request->filled('extra_payload')) {
            $extra = json_decode($request->input('extra_payload'), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($extra)) {
                $payload = array_replace_recursive($payload, $extra);
            }
        }

        return array_filter($payload, function ($value) {
            return !($value === null || $value === '' || $value === []);
        });
    }

    protected function splitTextList($value)
    {
        if (!$value) {
            return [];
        }

        return array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $value))));
    }

    protected function faqPayload($value)
    {
        if (!$value) {
            return [];
        }

        $items = [];
        foreach (preg_split('/[\r\n]+/', $value) as $line) {
            if (Str::contains($line, '|')) {
                list($question, $answer) = array_pad(array_map('trim', explode('|', $line, 2)), 2, null);
                if ($question && $answer) {
                    $items[] = compact('question', 'answer');
                }
            }
        }

        return $items;
    }

    protected function defaultProject()
    {
        return SeoProject::firstOrCreate(
            ['slug' => 'default-seo-suite'],
            [
                'name' => get_setting('website_name', config('app.name')).' SEO Suite',
                'base_url' => url('/'),
                'default_provider' => get_setting('seo_suite_default_provider', config('seo.default_provider', 'openai')),
                'created_by' => auth()->id(),
            ]
        );
    }

    protected function seoTablesReady()
    {
        return Schema::hasTable('seo_projects')
            && Schema::hasTable('seo_runs')
            && Schema::hasTable('seo_score_histories')
            && Schema::hasTable('seo_redirects');
    }
}
