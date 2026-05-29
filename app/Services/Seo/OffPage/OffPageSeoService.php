<?php

namespace App\Services\Seo\OffPage;

use App\Services\Seo\Support\AbstractSeoService;
use Illuminate\Support\Str;

class OffPageSeoService extends AbstractSeoService
{
    public function run($feature, array $payload = [])
    {
        $map = [
            'ai_backlink_campaign' => 'generateAiBacklinkCampaign',
            'backlink_outreach' => 'generateBacklinkOutreachEmail',
            'guest_post_topics' => 'generateGuestPostTopics',
            'guest_post_article' => 'writeGuestPostArticle',
            'social_signal_posts' => 'createSocialSignalPosts',
            'press_release' => 'generatePressRelease',
            'anchor_text_profile' => 'buildAnchorTextProfile',
        ];

        if (!isset($map[$feature])) {
            throw new \InvalidArgumentException('Unsupported off-page feature: '.$feature);
        }

        return call_user_func([$this, $map[$feature]], $payload);
    }

    public function generateAiBacklinkCampaign(array $payload)
    {
        $siteName = get_setting('website_name', config('app.name'));
        $targetUrl = $this->normalizeUrl(data_get($payload, 'url', url('/')));
        $topic = trim((string) data_get($payload, 'topic', data_get($payload, 'keyword', 'HVAC and plumbing supplies')));
        $keyword = trim((string) data_get($payload, 'keyword', $topic));
        $competitors = $this->csvList(get_setting('seo_competitor_urls', get_setting('ai_blog_competitor_urls', '')));
        $primaryCities = ['Mississauga', 'Brampton', 'Toronto'];
        $secondaryCities = ['Etobicoke', 'Vaughan', 'Oakville', 'Scarborough', 'Markham', 'North York', 'Burlington'];

        $fallback = [
            'strategy' => [
                'positioning' => "Earn white-hat mentions for {$siteName} by offering useful contractor supply resources, trade account information, and local GTA buying guidance.",
                'target_url' => $targetUrl,
                'focus_keyword' => $keyword,
            ],
            'prospect_categories' => [
                ['type' => 'Local business directories', 'examples' => ['Google Business Profile', 'Bing Places', 'Yelp Canada', 'YellowPages.ca']],
                ['type' => 'Trade and contractor associations', 'examples' => ['HVAC associations', 'plumbing associations', 'construction directories']],
                ['type' => 'Supplier/resource pages', 'examples' => ['local contractor resources', 'home service resource lists']],
                ['type' => 'Guest post targets', 'examples' => ['HVAC blogs', 'plumbing blogs', 'facility maintenance publications']],
            ],
            'local_citation_targets' => array_map(fn($city) => [
                'city' => $city,
                'query' => "{$keyword} supplier {$city}",
                'angle' => "Local supplier/resource listing for {$city} contractors",
            ], array_merge($primaryCities, $secondaryCities)),
            'guest_post_angles' => [
                "How GTA contractors can source {$keyword} faster",
                "Mississauga, Brampton, and Toronto contractor supply checklist",
                "Trade account benefits for HVAC and plumbing contractors",
            ],
            'outreach_templates' => [
                [
                    'type' => 'resource_page',
                    'subject' => "Useful GTA contractor supply resource",
                    'body' => "Hi {{name}},\n\nI found your contractor resources and thought {$siteName}'s {$topic} page may be useful for readers sourcing supplies around Mississauga, Brampton, and Toronto. If it fits your editorial standards, would you consider adding it as a helpful resource?\n\nBest,\n{$siteName}",
                ],
                [
                    'type' => 'guest_post',
                    'subject' => "Guest article idea for GTA contractors",
                    'body' => "Hi {{name}},\n\nWould you be open to a practical article for contractors about {$keyword} sourcing in the GTA? I can provide a non-promotional, useful draft with examples for Mississauga, Brampton, Toronto, and nearby areas.\n\nBest,\n{$siteName}",
                ],
            ],
            'anchor_text_plan' => [
                ['type' => 'brand', 'share' => '35%', 'examples' => [$siteName]],
                ['type' => 'partial_match', 'share' => '30%', 'examples' => ["{$keyword} supplier", "{$keyword} in Mississauga"]],
                ['type' => 'local', 'share' => '20%', 'examples' => ["GTA contractor supplies", "{$keyword} Toronto"]],
                ['type' => 'generic', 'share' => '15%', 'examples' => ['learn more', 'view supplier']],
            ],
            'weekly_execution_checklist' => [
                'Add or update 5 local citation profiles with consistent NAP.',
                'Contact 10 relevant resource pages or trade associations.',
                'Pitch 2 guest post angles to relevant industry sites.',
                'Ask recent customers to leave a review where appropriate.',
                'Track accepted links and avoid duplicate anchor text.',
            ],
            'safety_rules' => [
                'Do not buy links.',
                'Do not auto-post spam comments or forum links.',
                'Do not copy competitor content.',
                'Use branded and natural anchor text.',
            ],
            'provider' => $this->providerName,
        ];

        $competitorText = empty($competitors) ? 'No competitor URLs configured.' : implode(', ', array_slice($competitors, 0, 8));
        $prompt = "Create a white-hat AI backlink campaign for {$siteName}.\n"
            . "Target URL: {$targetUrl}\n"
            . "Topic: {$topic}\n"
            . "Focus keyword: {$keyword}\n"
            . "Primary cities: " . implode(', ', $primaryCities) . "\n"
            . "Secondary cities: " . implode(', ', $secondaryCities) . "\n"
            . "Intent targets: Trade Account, Leave a Review\n"
            . "Competitor websites to analyze for gaps, not copy: {$competitorText}\n"
            . "Return JSON with: strategy, prospect_categories, local_citation_targets, guest_post_angles, outreach_templates, anchor_text_plan, weekly_execution_checklist, safety_rules. Do not recommend spam, paid links, hacked links, comment spam, or automated third-party posting.";

        return $this->askForJson($prompt, 'You are a white-hat link building strategist. Output valid JSON only.', $fallback);
    }

    public function generateBacklinkOutreachEmail(array $payload)
    {
        $contactName = trim((string) data_get($payload, 'contact_name', 'there'));
        $targetSite = trim((string) data_get($payload, 'target_site', 'your website'));
        $topic = trim((string) data_get($payload, 'topic', data_get($payload, 'keyword', 'our resource')));

        return [
            'subject' => 'Collaboration idea for '.$targetSite,
            'body' => "Hi {$contactName},\n\nI came across {$targetSite} while researching {$topic}. We have a resource that could add value to your readers, and I would love to explore a contextual mention or guest contribution if it fits your editorial direction.\n\nHappy to share tailored angles and source material.\n\nBest regards,\n".get_setting('website_name', config('app.name')),
            'tone' => data_get($payload, 'tone', 'professional'),
            'provider' => $this->providerName,
        ];
    }

    public function generateGuestPostTopics(array $payload)
    {
        $niche = trim((string) data_get($payload, 'niche', data_get($payload, 'keyword', 'industrial supplies')));

        return [
            'topics' => [
                ['title' => 'How buyers compare '.$niche.' vendors in 2026', 'primary_keyword' => $niche.' vendors', 'recommended_word_count' => 1400],
                ['title' => 'Common mistakes when sourcing '.$niche, 'primary_keyword' => 'sourcing '.$niche, 'recommended_word_count' => 1200],
                ['title' => 'Specification checklist for '.$niche.' teams', 'primary_keyword' => $niche.' checklist', 'recommended_word_count' => 1500],
                ['title' => 'How to improve procurement ROI with '.$niche, 'primary_keyword' => $niche.' ROI', 'recommended_word_count' => 1300],
                ['title' => 'Emerging trends shaping '.$niche.' buying decisions', 'primary_keyword' => $niche.' trends', 'recommended_word_count' => 1100],
            ],
            'provider' => $this->providerName,
        ];
    }

    public function writeGuestPostArticle(array $payload)
    {
        $topic = trim((string) data_get($payload, 'topic', 'Guest post article'));
        $anchor = trim((string) data_get($payload, 'anchor_text', get_setting('website_name', config('app.name'))));
        $link = trim((string) data_get($payload, 'link', url('/')));

        return [
            'title' => $topic,
            'html' => '<article><h1>'.e($topic).'</h1><p>This guest article is designed to read naturally while supporting off-page SEO with useful, editorial-style insights.</p><h2>Why this topic matters</h2><p>Strong guest content earns trust when it teaches first and promotes second.</p><h2>Recommended resource</h2><p>Readers looking for more detail can review <a href="'.e($link).'">'.e($anchor).'</a> for additional context.</p></article>',
            'meta_description' => Str::limit('A guest-post-ready article about '.$topic.' with natural link placement and strong search intent alignment.', 160, ''),
            'provider' => $this->providerName,
        ];
    }

    public function createSocialSignalPosts(array $payload)
    {
        $topic = trim((string) data_get($payload, 'topic', data_get($payload, 'keyword', 'our latest guide')));
        $url = $this->normalizeUrl(data_get($payload, 'url', url('/')));

        return [
            'twitter' => $topic.' - practical takeaways and a quick resource: '.$url,
            'linkedin' => 'We just published a new resource on '.$topic.'. It focuses on practical buying guidance, SEO clarity, and actionable next steps. '.$url,
            'facebook' => 'New post: '.$topic.'. If you want a clearer path from search intent to conversion, this guide is worth a read. '.$url,
            'instagram' => 'Fresh insights on '.$topic.' now live. Link in bio / '.$url,
            'provider' => $this->providerName,
        ];
    }

    public function generatePressRelease(array $payload)
    {
        $headline = trim((string) data_get($payload, 'headline', data_get($payload, 'topic', 'New SEO initiative launched')));
        $company = get_setting('website_name', config('app.name'));

        return [
            'headline' => $headline,
            'body' => $company.' announces a new SEO initiative focused on stronger metadata, richer schema support, faster page performance, and measurable visibility gains across commercial search journeys.',
            'boilerplate' => $company.' is focused on dependable digital and commercial experiences supported by structured SEO execution.',
            'provider' => $this->providerName,
        ];
    }

    public function buildAnchorTextProfile(array $payload)
    {
        $brand = trim((string) data_get($payload, 'brand', get_setting('website_name', config('app.name'))));
        $keyword = trim((string) data_get($payload, 'keyword', 'target keyword'));

        return [
            'distribution' => [
                ['type' => 'Exact match', 'share' => '8%', 'example' => $keyword],
                ['type' => 'Partial match', 'share' => '22%', 'example' => $keyword.' supplier'],
                ['type' => 'LSI', 'share' => '22%', 'example' => 'industrial solutions'],
                ['type' => 'Brand', 'share' => '23%', 'example' => $brand],
                ['type' => 'Generic', 'share' => '15%', 'example' => 'learn more'],
                ['type' => 'Naked URL', 'share' => '10%', 'example' => url('/')],
            ],
            'provider' => $this->providerName,
        ];
    }
}
