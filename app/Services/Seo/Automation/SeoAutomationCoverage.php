<?php

namespace App\Services\Seo\Automation;

class SeoAutomationCoverage
{
    public function summary(): array
    {
        $groups = $this->groups();
        $items = collect($groups)->flatMap(fn(array $group) => $group['items']);

        return [
            'groups' => $groups,
            'automatic_count' => $items->where('mode', 'automatic')->count(),
            'approval_count' => $items->where('mode', 'approval')->count(),
        ];
    }

    public function groups(): array
    {
        return [
            [
                'key' => 'on_page',
                'label' => 'On-Page Autopilot',
                'icon' => 'la-file-alt',
                'tone' => 'primary',
                'detail' => 'Pages first, then categories, then products. Completed SEO URLs scoring 80+ remain protected.',
                'items' => [
                    $this->automatic('Protected priority queue', 'Retries unfinished URLs before selecting new work.'),
                    $this->automatic('Meta title and description', 'Generates Canada and GTA-focused search snippets by entity type.'),
                    $this->automatic('Keyword mapping', 'Adds focus keywords and related secondary terms naturally.'),
                    $this->automatic('Content depth and headings', 'Improves thin content with product, category, and page-specific logic.'),
                    $this->automatic('FAQ and schema', 'Adds useful FAQs and structured data for eligible URLs.'),
                    $this->automatic('Canonical and breadcrumbs', 'Persists canonical URLs and reusable breadcrumb trails for clean crawl signals.'),
                    $this->automatic('Social metadata bundle', 'Fills Open Graph and Twitter titles, descriptions, card type, and image fallbacks.'),
                    $this->automatic('Contextual internal links', 'Connects pending URLs to relevant categories, trade account, reviews, shop, and rotating GTA landing pages.'),
                    $this->automatic('Score regression quality gate', 'Rolls back unattended AI changes when the measured on-page score would decrease.'),
                    $this->automatic('Scoring and revision history', 'Rescores each processed URL and records measurable impact.'),
                ],
            ],
            [
                'key' => 'off_page',
                'label' => 'Off-Page Autopilot',
                'icon' => 'la-link',
                'tone' => 'success',
                'detail' => 'Only protected SEO-ready URLs qualify for ethical off-page campaign planning.',
                'items' => [
                    $this->automatic('White-hat campaign bundle', 'Creates a reusable campaign brief for each eligible URL.'),
                    $this->automatic('Local citation targets', 'Suggests relevant Canadian and GTA listing opportunities.'),
                    $this->automatic('Outreach templates', 'Drafts personalized outreach messages for manual review.'),
                    $this->automatic('Guest-post topic angles', 'Creates useful topic ideas related to the target page.'),
                    $this->automatic('Social-signal drafts', 'Prepares promotional angles without auto-publishing.'),
                    $this->automatic('Natural anchor-text plan', 'Balances branded, topical, and URL anchors.'),
                    $this->automatic('Competitor gap angles', 'Uses configured competitor URLs for campaign opportunities.'),
                ],
            ],
            [
                'key' => 'optimization',
                'label' => 'Technical Optimization',
                'icon' => 'la-cogs',
                'tone' => 'info',
                'detail' => 'Interval-gated jobs keep crawl files, measurements, and diagnostics current.',
                'items' => [
                    $this->automatic('Smart XML sitemaps', 'Refreshes crawlable URLs and image-aware product entries.'),
                    $this->automatic('Video and news sitemaps', 'Publishes specialized discovery files when eligible content exists.'),
                    $this->automatic('Robots.txt, LLMs.txt, and RSS', 'Keeps crawler guidance and discovery feeds fresh.'),
                    $this->automatic('Canonical and redirect audits', 'Reports URL normalization and active redirect paths.'),
                    $this->automatic('Local SEO snapshot', 'Checks the business NAP and local structured-data blueprint.'),
                    $this->automatic('Webmaster verification snapshot', 'Tracks configured search-engine verification tags.'),
                    $this->automatic('IndexNow submission', 'Pings changed crawl artifacts when enabled in settings.'),
                    $this->automatic('Index coverage verification', 'Checks protected SEO-ready URLs through Google Custom Search and resubmits confirmed gaps when enabled.'),
                    $this->automatic('Search Console and rank tracking', 'Syncs Google query visibility and target keyword positions.'),
                    $this->automatic('PageSpeed and broken-link checks', 'Runs interval-gated performance and link-health diagnostics.'),
                ],
            ],
            [
                'key' => 'approval',
                'label' => 'Approval Required',
                'icon' => 'la-user-shield',
                'tone' => 'warning',
                'detail' => 'These actions stay manual because they affect external sites, publishing, or routing.',
                'items' => [
                    $this->approval('Third-party backlink placement', 'Review target quality before publishing any external link.'),
                    $this->approval('Sending outreach', 'Approve recipients and messaging before email or contact-form submission.'),
                    $this->approval('Guest-post publication', 'Review editorial fit and final article quality before publishing.'),
                    $this->approval('Press-release distribution', 'Approve the story, claims, and distribution channel.'),
                    $this->approval('Redirect mutations', 'Audit the recommendation before changing live URL routing.'),
                    $this->approval('AI-generated images', 'Confirm brand fit and rights before using generated media.'),
                ],
            ],
        ];
    }

    protected function automatic(string $label, string $detail): array
    {
        return compact('label', 'detail') + ['mode' => 'automatic'];
    }

    protected function approval(string $label, string $detail): array
    {
        return compact('label', 'detail') + ['mode' => 'approval'];
    }
}
