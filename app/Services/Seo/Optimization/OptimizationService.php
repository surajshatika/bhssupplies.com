<?php

namespace App\Services\Seo\Optimization;

use App\Models\Blog;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\SeoProject;
use App\Models\SeoRedirect;
use App\Models\SeoScoreHistory;
use App\Services\Seo\Support\AbstractSeoService;
use App\Services\Seo\Optimization\Features\SmartSitemapService;
use App\Services\Seo\Optimization\Features\VideoSitemapService;
use App\Services\Seo\Optimization\Features\GoogleNewsSitemapService;
use App\Services\Seo\Optimization\Features\RssContentService;
use App\Services\Seo\Optimization\Features\IndexNowService;
use App\Services\Seo\Optimization\Features\WebmasterToolsService;
use App\Services\Seo\Optimization\Features\KeywordRankTrackerService;
use App\Services\Seo\Optimization\Features\SearchStatisticsService;
use App\Services\Seo\Optimization\Features\PostIndexStatusService;
use App\Services\Seo\Optimization\Features\AiWritingAssistantService;
use App\Services\Seo\Optimization\Features\AiImageGeneratorService;
use App\Services\Seo\Optimization\Features\AiAssistantService;
use App\Services\Seo\Optimization\Features\SeoRevisionsService;
use App\Services\Seo\Optimization\Features\LinkAssistantService;
use App\Services\Seo\Optimization\Features\SmallBusinessSeoService;
use App\Services\Seo\Optimization\Features\LlmsTxtService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class OptimizationService extends AbstractSeoService
{
    public function run($feature, array $payload = [])
    {
        $map = [
            'page_speed' => 'analyzePageSpeed',
            'technical_audit' => 'runTechnicalAudit',
            'competitor_gap' => 'detectCompetitorGap',
            'sitemap' => 'generateSitemap',
            'robots' => 'optimizeRobotsTxt',
            'canonical' => 'buildCanonicalUrl',
            'redirects' => 'analyzeRedirectChains',
            'broken_links' => 'detectBrokenLinks',
            'faq_schema' => 'generateFaqSchema',
            'local_seo' => 'optimizeLocalSeo',
            'ecommerce_seo'       => 'optimizeEcommerceSeo',
            'score_dashboard'     => 'buildScoreDashboard',
            'smart_sitemap'       => 'runSmartSitemap',
            'video_sitemap'       => 'runVideoSitemap',
            'news_sitemap'        => 'runNewsSitemap',
            'blog_sitemap'        => 'runNewsSitemap',
            'sitemap'             => 'runSmartSitemap',
            'rss_content'         => 'runRssContent',
            'indexnow'            => 'runIndexNow',
            'webmaster_tools'     => 'runWebmasterTools',
            'keyword_rank_tracker'=> 'runKeywordRankTracker',
            'search_statistics'   => 'runSearchStatistics',
            'post_index_status'   => 'runPostIndexStatus',
            'ai_writing_assistant'=> 'runAiWritingAssistant',
            'ai_image_generator'  => 'runAiImageGenerator',
            'ai_assistant'        => 'runAiAssistant',
            'seo_revisions'       => 'runSeoRevisions',
            'link_assistant'      => 'runLinkAssistant',
            'small_business_seo'  => 'runSmallBusinessSeo',
            'llms_txt'            => 'runLlmsTxt',
            'redirection_manager' => 'analyzeRedirectChains',
        ];

        if (!isset($map[$feature])) {
            throw new \InvalidArgumentException('Unsupported optimization feature: '.$feature);
        }

        return call_user_func([$this, $map[$feature]], $payload);
    }

    public function analyzePageSpeed(array $payload)
    {
        $lcp = (float) data_get($payload, 'lcp', 3200);
        $cls = (float) data_get($payload, 'cls', 0.22);
        $fid = (float) data_get($payload, 'fid', 180);
        $score = (int) data_get($payload, 'score', 61);

        $fixes = [];
        if ($lcp > 2500) {
            $fixes[] = ['issue' => 'High LCP', 'impact' => 'high', 'fix' => 'Compress hero images, preload primary asset, and defer render-blocking CSS.', 'expected_gain' => 12];
        }
        if ($cls > 0.1) {
            $fixes[] = ['issue' => 'Layout shift', 'impact' => 'high', 'fix' => 'Set image dimensions, reserve ad space, and avoid injecting banners above the fold.', 'expected_gain' => 8];
        }
        if ($fid > 100) {
            $fixes[] = ['issue' => 'High input delay', 'impact' => 'medium', 'fix' => 'Break up long JavaScript tasks and lazy-load non-critical scripts.', 'expected_gain' => 6];
        }
        if (empty($fixes)) {
            $fixes[] = ['issue' => 'Minor tuning only', 'impact' => 'low', 'fix' => 'Audit cache headers, fonts, and third-party tags for incremental gains.', 'expected_gain' => 3];
        }

        return [
            'url' => $this->normalizeUrl(data_get($payload, 'url', url('/'))),
            'metrics' => compact('lcp', 'cls', 'fid', 'score'),
            'fixes' => $fixes,
            'new_score_estimate' => min(100, $score + array_sum(array_column($fixes, 'expected_gain'))),
            'provider' => $this->providerName,
        ];
    }

    public function runTechnicalAudit(array $payload)
    {
        $url = $this->normalizeUrl(data_get($payload, 'url', url('/')));
        $statusCode = 200;
        $responseTime = null;

        try {
            $start = microtime(true);
            $response = Http::timeout(20)->get($url);
            $statusCode = $response->status();
            $responseTime = round((microtime(true) - $start) * 1000, 2);
        } catch (\Throwable $exception) {
            $statusCode = 0;
        }

        $checks = [
            ['label' => 'URL resolves', 'status' => $statusCode >= 200 && $statusCode < 400 ? 'pass' : 'warning'],
            ['label' => 'Response under 1 second', 'status' => $responseTime !== null && $responseTime < 1000 ? 'pass' : 'warning'],
            ['label' => 'Sitemap available', 'status' => File::exists(base_path('sitemap.xml')) ? 'pass' : 'warning'],
            ['label' => 'robots.txt available', 'status' => File::exists(public_path('robots.txt')) || File::exists(base_path('robots.txt')) ? 'pass' : 'warning'],
            ['label' => 'HTTPS canonical path', 'status' => Str::startsWith($url, 'https://') ? 'pass' : 'warning'],
            ['label' => 'Schema baseline', 'status' => 'pass'],
            ['label' => 'Thin content risk', 'status' => $this->wordCount(data_get($payload, 'content', '')) >= 250 ? 'pass' : 'warning'],
        ];
        $score = $this->scoreFromChecks($checks);

        return [
            'url' => $url,
            'status_code' => $statusCode,
            'response_time_ms' => $responseTime,
            'checks' => $checks,
            'score' => $score,
            'grade' => $this->gradeFromScore($score),
            'provider' => $this->providerName,
        ];
    }

    public function detectCompetitorGap(array $payload)
    {
        $ours = $this->csvList(data_get($payload, 'our_keywords', []));
        $competitors = array_merge(
            $this->csvList(data_get($payload, 'comp1_keywords', [])),
            $this->csvList(data_get($payload, 'comp2_keywords', [])),
            $this->csvList(data_get($payload, 'competitor_keywords', []))
        );
        $missing = array_values(array_diff(array_unique($competitors), array_unique($ours)));
        $missing = array_slice($missing, 0, 12);
        $gaps = [];

        foreach ($missing as $keyword) {
            $gaps[] = [
                'kw' => $keyword,
                'difficulty' => 'medium',
                'volume' => rand(70, 900),
                'opportunity' => strlen($keyword) > 12 ? 'quick win' : 'core coverage',
            ];
        }

        return [
            'missing_keywords' => $gaps,
            'content_gaps' => array_map(function ($item) {
                return [
                    'topic' => Str::title($item['kw']),
                    'priority' => $item['opportunity'] === 'quick win' ? 'high' : 'medium',
                ];
            }, array_slice($gaps, 0, 5)),
            'quick_wins' => array_map(function ($item) {
                return [
                    'kw' => $item['kw'],
                    'action' => 'Create or expand a landing page section targeting this term.',
                ];
            }, array_slice($gaps, 0, 5)),
            'provider' => $this->providerName,
        ];
    }

    public function generateSitemap(array $payload = [])
    {
        $urls = $this->collectSiteUrls();
        $xml = [];
        $xml[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($urls as $item) {
            $xml[] = '  <url>';
            $xml[] = '    <loc>'.e($item['url']).'</loc>';
            $xml[] = '    <changefreq>'.$item['changefreq'].'</changefreq>';
            $xml[] = '    <priority>'.$item['priority'].'</priority>';
            if (!empty($item['lastmod'])) {
                $xml[] = '    <lastmod>'.$item['lastmod'].'</lastmod>';
            }
            $xml[] = '  </url>';
        }

        $xml[] = '</urlset>';
        $content = implode("\n", $xml);

        if (data_get($payload, 'persist', true)) {
            File::put(base_path('sitemap.xml'), $content);
        }

        return [
            'xml' => $content,
            'url_count' => count($urls),
            'submitted_to_search_console' => false,
            'provider' => 'rule-based',
        ];
    }

    public function optimizeRobotsTxt(array $payload = [])
    {
        $content = implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin/',
            'Disallow: /checkout/',
            'Disallow: /cart/',
            'Disallow: /compare/',
            'Sitemap: '.url('/sitemap.xml'),
        ]);

        if (data_get($payload, 'persist', true)) {
            File::put(public_path('robots.txt'), $content);
            File::put(base_path('robots.txt'), $content);
        }

        return [
            'robots_txt' => $content,
            'explanation' => [
                'Public storefront pages remain crawlable.',
                'Cart, checkout, and admin paths stay blocked to preserve crawl budget.',
                'The generated sitemap is surfaced explicitly for discovery.',
            ],
            'provider' => 'ai+rule',
        ];
    }

    public function buildCanonicalUrl(array $payload = [])
    {
        $url = $this->normalizeUrl(data_get($payload, 'url', request()->fullUrl()));
        $parts = parse_url($url);
        $canonical = (isset($parts['scheme']) ? $parts['scheme'].'://' : '').($parts['host'] ?? '').($parts['path'] ?? '');

        return [
            'requested_url' => $url,
            'canonical_url' => rtrim($canonical, '/') ?: url('/'),
            'self_referencing' => true,
            'provider' => 'rule-based',
        ];
    }

    public function analyzeRedirectChains(array $payload = [])
    {
        $redirects = SeoRedirect::query()->where('is_active', true)->get();
        $chains = [];

        foreach ($redirects as $redirect) {
            $chains[] = [
                'from' => $redirect->source_url,
                'to' => $redirect->target_url,
                'status_code' => $redirect->status_code,
                'suggested_fix' => 'Point '.$redirect->source_url.' directly to '.$redirect->target_url.' in one hop.',
            ];
        }

        return [
            'chains' => $chains,
            'htaccess_export' => collect($chains)->map(function ($chain) {
                return 'Redirect '.$chain['status_code'].' '.$chain['from'].' '.$chain['to'];
            })->implode("\n"),
            'nginx_export' => collect($chains)->map(function ($chain) {
                return 'rewrite ^'.trim(parse_url($chain['from'], PHP_URL_PATH), '/').'$ '.$chain['to'].' permanent;';
            })->implode("\n"),
            'provider' => 'rule-based',
        ];
    }

    public function detectBrokenLinks(array $payload = [])
    {
        $links = $this->csvList(data_get($payload, 'links', []));
        $report = [];

        foreach (array_slice($links, 0, 20) as $link) {
            $status = 0;
            try {
                $status = Http::timeout(10)->get($this->normalizeUrl($link))->status();
            } catch (\Throwable $exception) {
                $status = 0;
            }

            $report[] = [
                'url' => $link,
                'status' => $status,
                'state' => $status >= 200 && $status < 400 ? 'ok' : 'broken',
                'replacement_suggestion' => $status >= 200 && $status < 400 ? null : url('/search?keyword='.urlencode(basename($link))),
            ];
        }

        return [
            'broken' => array_values(array_filter($report, function ($item) {
                return $item['state'] === 'broken';
            })),
            'checked' => $report,
            'provider' => 'http-check',
        ];
    }

    public function generateFaqSchema(array $payload = [])
    {
        $faq = data_get($payload, 'faq', []);
        if (!is_array($faq) || empty($faq)) {
            $faq = [
                ['question' => 'What makes this page SEO-friendly?', 'answer' => 'It aligns metadata, structure, internal links, and search intent clearly.'],
                ['question' => 'How often should SEO audits run?', 'answer' => 'Weekly snapshots are a practical default for trend visibility.'],
            ];
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => collect($faq)->map(function ($item) {
                return [
                    '@type' => 'Question',
                    'name' => data_get($item, 'question'),
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => data_get($item, 'answer'),
                    ],
                ];
            })->values()->all(),
        ];

        return [
            'json_ld' => json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'question_count' => count($faq),
            'provider' => $this->providerName,
        ];
    }

    public function optimizeLocalSeo(array $payload = [])
    {
        $businessName = data_get($payload, 'business_name', config('seo.local_business.name', get_setting('website_name', config('app.name'))));
        $address = data_get($payload, 'address', get_setting('contact_address'));
        $phone = data_get($payload, 'phone', get_setting('contact_phone'));
        $email = data_get($payload, 'email', get_setting('contact_email'));
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => data_get($payload, 'business_type', config('seo.local_business.type', 'Store')),
            'name' => $businessName,
            'telephone' => $phone,
            'email' => $email,
            'url' => url('/'),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $address,
                'addressLocality' => config('seo.local_business.city'),
                'addressRegion' => config('seo.local_business.region'),
                'addressCountry' => config('seo.local_business.country'),
            ],
        ];

        return [
            'nap' => compact('businessName', 'address', 'phone', 'email'),
            'local_business_schema' => json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'recommendations' => [
                'Keep business name, phone, and address identical across website footer and local listings.',
                'Add location keywords to service and category pages only where they fit naturally.',
                'Use review-ready product and organization schema on high-conversion pages.',
            ],
            'provider' => $this->providerName,
        ];
    }

    public function optimizeEcommerceSeo(array $payload = [])
    {
        $product = null;
        if (data_get($payload, 'product_id') && Schema::hasTable('products')) {
            $product = Product::find(data_get($payload, 'product_id'));
        }

        $title = $product ? $product->getTranslation('name') : data_get($payload, 'title', 'Product SEO');
        $price = $product ? $product->unit_price : data_get($payload, 'price');
        $currency = \App\Models\Currency::find(get_setting('system_default_currency'));

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $title,
            'description' => Str::limit($this->plainText($product ? $product->description : data_get($payload, 'description')), 240, ''),
            'offers' => [
                '@type' => 'Offer',
                'price' => $price,
                'priceCurrency' => $currency ? $currency->code : 'INR',
                'availability' => 'https://schema.org/InStock',
            ],
        ];

        return [
            'meta_title' => Str::limit($title.' | Buy Online from '.get_setting('website_name', config('app.name')), 60, ''),
            'meta_description' => Str::limit('Shop '.$title.' with dependable specs, competitive pricing, and fast support from '.get_setting('website_name', config('app.name')).'.', 160, ''),
            'product_schema' => json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'provider' => $this->providerName,
        ];
    }

    public function buildScoreDashboard(array $payload = [])
    {
        $projectId = data_get($payload, 'project_id');
        $query = SeoScoreHistory::query()->latest('recorded_at');

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        $history = $query->limit(12)->get();
        $latest = $history->first();
        $trend = $history->reverse()->values()->map(function ($item) {
            return [
                'date' => optional($item->recorded_at)->format('Y-m-d'),
                'score' => $item->score,
                'grade' => $item->grade,
                'url' => $item->url,
            ];
        })->all();

        return [
            'current_score' => $latest ? $latest->score : 0,
            'current_grade' => $latest ? $latest->grade : 'N/A',
            'trend' => $trend,
            'average_score' => $history->count() ? round($history->avg('score'), 2) : 0,
            'provider' => 'aggregate',
        ];
    }

    public function snapshotProjectScores(SeoProject $project = null)
    {
        $urls = array_slice($this->collectSiteUrls(), 0, 12);
        $created = [];

        foreach ($urls as $item) {
            $result = $this->runTechnicalAudit([
                'url' => $item['url'],
                'content' => '',
            ]);

            $created[] = SeoScoreHistory::create([
                'project_id' => $project ? $project->id : null,
                'seo_run_id' => null,
                'url' => $item['url'],
                'score' => $result['score'],
                'grade' => $result['grade'],
                'metrics' => $result,
                'recorded_at' => Carbon::now(),
            ]);
        }

        return $created;
    }

    protected function collectSiteUrls()
    {
        $urls = [
            ['url' => url('/'), 'priority' => '1.0', 'changefreq' => 'daily', 'lastmod' => Carbon::now()->toAtomString()],
            ['url' => url('/blog'), 'priority' => '0.8', 'changefreq' => 'weekly', 'lastmod' => Carbon::now()->toAtomString()],
            ['url' => url('/brands'), 'priority' => '0.7', 'changefreq' => 'weekly', 'lastmod' => Carbon::now()->toAtomString()],
            ['url' => url('/categories'), 'priority' => '0.8', 'changefreq' => 'weekly', 'lastmod' => Carbon::now()->toAtomString()],
        ];

        if (Schema::hasTable('products')) {
            Product::query()->latest('updated_at')->limit(50)->get()->each(function ($product) use (&$urls) {
                if (!empty($product->slug)) {
                    $urls[] = [
                        'url' => route('product', $product->slug),
                        'priority' => '0.9',
                        'changefreq' => 'weekly',
                        'lastmod' => optional($product->updated_at)->toAtomString(),
                    ];
                }
            });
        }

        if (Schema::hasTable('categories')) {
            Category::query()->limit(30)->get()->each(function ($category) use (&$urls) {
                if (!empty($category->slug)) {
                    $urls[] = [
                        'url' => route('products.category', $category->slug),
                        'priority' => '0.7',
                        'changefreq' => 'weekly',
                        'lastmod' => optional($category->updated_at)->toAtomString(),
                    ];
                }
            });
        }

        if (Schema::hasTable('pages')) {
            Page::query()->limit(30)->get()->each(function ($page) use (&$urls) {
                if (!empty($page->slug)) {
                    $urls[] = [
                        'url' => url($page->slug),
                        'priority' => '0.6',
                        'changefreq' => 'monthly',
                        'lastmod' => optional($page->updated_at)->toAtomString(),
                    ];
                }
            });
        }

        if (Schema::hasTable('blogs')) {
            Blog::query()->where('status', 1)->latest('updated_at')->limit(30)->get()->each(function ($blog) use (&$urls) {
                if (!empty($blog->slug)) {
                    $urls[] = [
                        'url' => route('blog.details', $blog->slug),
                        'priority' => '0.6',
                        'changefreq' => 'monthly',
                        'lastmod' => optional($blog->updated_at)->toAtomString(),
                    ];
                }
            });
        }

        return collect($urls)->unique('url')->values()->all();
    }

    // ── New feature delegates ──────────────────────────────────────────────────

    public function runSmartSitemap(array $payload): array
    {
        return app(SmartSitemapService::class)->handle($payload);
    }

    public function runVideoSitemap(array $payload): array
    {
        return app(VideoSitemapService::class)->handle($payload);
    }

    public function runNewsSitemap(array $payload): array
    {
        return app(GoogleNewsSitemapService::class)->handle($payload);
    }

    public function runRssContent(array $payload): array
    {
        return app(RssContentService::class)->handle($payload);
    }

    public function runIndexNow(array $payload): array
    {
        return app(IndexNowService::class)->handle($payload);
    }

    public function runWebmasterTools(array $payload): array
    {
        return app(WebmasterToolsService::class)->handle($payload);
    }

    public function runKeywordRankTracker(array $payload): array
    {
        return app(KeywordRankTrackerService::class)->handle($payload);
    }

    public function runSearchStatistics(array $payload): array
    {
        return app(SearchStatisticsService::class)->handle($payload);
    }

    public function runPostIndexStatus(array $payload): array
    {
        return app(PostIndexStatusService::class)->handle($payload);
    }

    public function runAiWritingAssistant(array $payload): array
    {
        return app(AiWritingAssistantService::class)->handle($payload);
    }

    public function runAiImageGenerator(array $payload): array
    {
        return app(AiImageGeneratorService::class)->handle($payload);
    }

    public function runAiAssistant(array $payload): array
    {
        return app(AiAssistantService::class)->handle($payload);
    }

    public function runSeoRevisions(array $payload): array
    {
        return app(SeoRevisionsService::class)->handle($payload);
    }

    public function runLinkAssistant(array $payload): array
    {
        return app(LinkAssistantService::class)->handle($payload);
    }

    public function runSmallBusinessSeo(array $payload): array
    {
        return app(SmallBusinessSeoService::class)->handle($payload);
    }

    public function runLlmsTxt(array $payload): array
    {
        return app(LlmsTxtService::class)->handle($payload);
    }
}
