<?php

namespace App\Console\Commands;

use App\Models\BlogCategory;
use App\Models\Category;
use App\Models\SocialAutomationSetting;
use App\Services\Blog\AiBlogGeneratorService;
use Illuminate\Console\Command;

class AiBlogPost extends Command
{
    protected $signature = 'blog:ai-generate
                            {--count=1          : Number of blog posts to generate}
                            {--title=           : Exact blog title}
                            {--slug=            : Preferred blog slug}
                            {--blog-cat-id=     : Existing blog category ID}
                            {--category=        : Blog category name (creates if not exists)}
                            {--product-cat=     : Product category ID to pull topic & images from}
                            {--banner=          : Existing Upload ID for 1300x650 banner}
                            {--meta-image=      : Existing Upload ID for meta image}
                            {--meta-title=      : SEO meta title override}
                            {--meta-desc=       : SEO meta description override}
                            {--meta-keywords=   : SEO meta keywords override}
                            {--topic=           : Custom topic override}
                            {--keywords=        : Extra seed keywords}
                            {--provider=        : AI provider (openai|claude|grok)}
                            {--tone=            : Content tone}
                            {--publish          : Immediately publish the generated posts}
                            {--post-social      : Auto-post to enabled social platforms}
                            {--dry-run          : Preview settings without generating}';

    protected $description = 'Generate AI blog posts using category/product images, competitor keywords, and auto-post to social media';

    public function handle(): int
    {
        $count    = (int) $this->option('count');
        $publish  = (bool) $this->option('publish');
        $social   = (bool) $this->option('post-social');
        $dryRun   = (bool) $this->option('dry-run');
        $provider = $this->option('provider') ?: SocialAutomationSetting::get('ai_blog_provider', 'openai');

        if ($dryRun) {
            $this->showDryRun($provider);
            return self::SUCCESS;
        }

        $options = array_filter([
            'blog_title'          => $this->option('title') ?: null,
            'slug'                => $this->option('slug') ?: null,
            'blog_category_id'    => $this->option('blog-cat-id') ? (int)$this->option('blog-cat-id') : null,
            'category_name'       => $this->option('category') ?: null,
            'product_category_id' => $this->option('product-cat') ? (int)$this->option('product-cat') : null,
            'banner_upload_id'    => $this->option('banner') ? (int)$this->option('banner') : null,
            'meta_image_upload_id'=> $this->option('meta-image') ? (int)$this->option('meta-image') : null,
            'meta_title'          => $this->option('meta-title') ?: null,
            'meta_description'    => $this->option('meta-desc') ?: null,
            'meta_keywords'       => $this->option('meta-keywords') ?: null,
            'topic'               => $this->option('topic') ?: null,
            'keywords'            => $this->option('keywords') ?: null,
            'ai_provider'         => $provider,
            'tone'                => $this->option('tone') ?: null,
            'publish'             => $publish,
            'post_to_social'      => $social,
        ]);

        $service = new AiBlogGeneratorService($provider);

        $this->info("Generating {$count} AI blog post(s) via {$provider}...");
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $generated = 0;
        for ($i = 0; $i < $count; $i++) {
            try {
                // Rotate through product categories automatically if no specific one given
                if (!isset($options['product_category_id']) && !isset($options['category_name'])) {
                    $productCat = Category::inRandomOrder()->first();
                    if ($productCat) {
                        $options['product_category_id'] = $productCat->id;
                    }
                }

                $blog = $service->generate($options);
                $bar->advance();
                $this->newLine();
                $this->line("  [{$blog->id}] <info>{$blog->title}</info> → " . ($publish ? 'Published' : 'Draft'));
                $generated++;
            } catch (\Throwable $e) {
                $bar->advance();
                $this->newLine();
                $this->error("  Generation #{$i} failed: " . $e->getMessage());
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Done. Generated {$generated}/{$count} blog posts." . ($social ? ' Social posts dispatched.' : ''));

        return self::SUCCESS;
    }

    private function showDryRun(string $provider): void
    {
        $categories = BlogCategory::pluck('category_name')->toArray();
        $competitors = SocialAutomationSetting::get('ai_blog_competitor_urls', '(none set)');
        $country     = SocialAutomationSetting::get('ai_blog_target_country', 'Canada');

        $this->table(['Setting', 'Value'], [
            ['AI Provider',          $provider],
            ['Target Country',       $country],
            ['Blog Categories',      implode(', ', array_slice($categories, 0, 5)) . (count($categories) > 5 ? '…' : '')],
            ['Competitor URLs',      $competitors],
            ['Publish immediately',  $this->option('publish') ? 'Yes' : 'No'],
            ['Post to social',       $this->option('post-social') ? 'Yes' : 'No'],
            ['Word count',           SocialAutomationSetting::get('ai_blog_word_count', 1200)],
        ]);
    }
}
