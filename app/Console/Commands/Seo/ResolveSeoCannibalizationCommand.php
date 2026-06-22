<?php

namespace App\Console\Commands\Seo;

use App\Models\SeoMeta;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResolveSeoCannibalizationCommand extends Command
{
    protected $signature = 'seo:resolve-cannibalization';
    protected $description = 'Detects keyword cannibalization and automatically applies canonical tags to consolidate ranking power.';

    public function handle()
    {
        $this->info('Scanning for SEO Cannibalization...');

        // Find all focus_keywords that appear more than once
        $duplicates = SeoMeta::select('focus_keyword', DB::raw('count(*) as total'))
            ->whereNotNull('focus_keyword')
            ->where('focus_keyword', '!=', '')
            ->groupBy('focus_keyword')
            ->having('total', '>', 1)
            ->get();

        if ($duplicates->isEmpty()) {
            $this->info('No cannibalized keywords found. Your site is healthy.');
            return self::SUCCESS;
        }

        $this->warn("Found {$duplicates->count()} cannibalized keywords. Resolving...");

        $resolvedCount = 0;

        foreach ($duplicates as $dup) {
            $keyword = trim($dup->focus_keyword);
            
            // Get all entities competing for this keyword, ordered by SEO Score descending
            $competitors = SeoMeta::where('focus_keyword', $keyword)
                ->orderByDesc('seo_score')
                ->orderByDesc('id') // Tie breaker
                ->get();

            // The Alpha is the first one
            $alpha = $competitors->first();
            $alphaUrl = $this->resolveEntityUrl($alpha);

            if (!$alphaUrl) {
                continue;
            }

            // The rest are Betas. Point their canonical URL to the Alpha.
            $betas = $competitors->slice(1);
            foreach ($betas as $beta) {
                // Only update if it doesn't already point to the Alpha
                if ($beta->canonical_url !== $alphaUrl) {
                    $beta->canonical_url = $alphaUrl;
                    
                    // Log the reason
                    $reasons = $beta->score_reasons ?? [];
                    $reasons['cannibalization'] = "Cannibalization resolved: Canonical tag points to Alpha entity ({$alpha->model_type} #{$alpha->model_id}) for keyword '{$keyword}'.";
                    $beta->score_reasons = $reasons;
                    
                    $beta->save();
                    $resolvedCount++;
                    
                    $this->line("- Resolved '{$keyword}': pointing {$beta->model_type}#{$beta->model_id} to Alpha {$alphaUrl}");
                }
            }
        }

        $this->info("Cannibalization scan complete. Applied {$resolvedCount} canonical tags.");
        return self::SUCCESS;
    }

    protected function resolveEntityUrl(SeoMeta $meta): ?string
    {
        $model = $meta->model;
        if (!$model) {
            return null;
        }

        $slug = $model->slug ?? null;
        if (!$slug) {
            return null;
        }

        $type = class_basename($meta->model_type);

        return match ($type) {
            'Product' => url('/product/' . $slug),
            'Category', 'SubCategory', 'SubSubCategory' => url('/category/' . $slug),
            'Brand' => url('/brand/' . $slug),
            'Blog' => url('/blog/' . $slug),
            'BlogCategory' => url('/blog/category/' . $slug),
            'Page' => url('/page/' . $slug),
            default => null,
        };
    }
}
