<?php

namespace App\Services\Seo\Optimization\Features;

use App\Services\Seo\Support\AbstractSeoService;

class WebmasterToolsService extends AbstractSeoService
{
    public function handle(array $payload): array
    {
        $verifications = [
            'google'    => $payload['google_verification'] ?? get_setting('seo_google_verification'),
            'bing'      => $payload['bing_verification'] ?? get_setting('seo_bing_verification'),
            'yandex'    => $payload['yandex_verification'] ?? get_setting('seo_yandex_verification'),
            'pinterest' => $payload['pinterest_verification'] ?? get_setting('seo_pinterest_verification'),
            'baidu'     => $payload['baidu_verification'] ?? get_setting('seo_baidu_verification'),
        ];

        $metaTags   = $this->buildVerificationMetaTags($verifications);
        $htmlSnippet = $this->buildHtmlSnippet($metaTags);

        return [
            'verifications' => $verifications,
            'meta_tags'     => $metaTags,
            'html_snippet'  => $htmlSnippet,
            'instructions'  => $this->getInstructions(),
        ];
    }

    protected function buildVerificationMetaTags(array $verifications): array
    {
        $tags = [];
        if (!empty($verifications['google'])) {
            $tags[] = '<meta name="google-site-verification" content="' . e($verifications['google']) . '">';
        }
        if (!empty($verifications['bing'])) {
            $tags[] = '<meta name="msvalidate.01" content="' . e($verifications['bing']) . '">';
        }
        if (!empty($verifications['yandex'])) {
            $tags[] = '<meta name="yandex-verification" content="' . e($verifications['yandex']) . '">';
        }
        if (!empty($verifications['pinterest'])) {
            $tags[] = '<meta name="p:domain_verify" content="' . e($verifications['pinterest']) . '">';
        }
        if (!empty($verifications['baidu'])) {
            $tags[] = '<meta name="baidu-site-verification" content="' . e($verifications['baidu']) . '">';
        }
        return $tags;
    }

    protected function buildHtmlSnippet(array $metaTags): string
    {
        return implode("\n", $metaTags);
    }

    protected function getInstructions(): array
    {
        return [
            'google'    => 'Get your code from Google Search Console > Settings > Ownership verification > HTML tag',
            'bing'      => 'Get your code from Bing Webmaster Tools > My Sites > Verify your site > XML meta tag',
            'yandex'    => 'Get your code from Yandex.Webmaster > Add site > Meta tag method',
            'pinterest' => 'Get your code from Pinterest > Settings > Claim > Claim your website',
            'baidu'     => 'Get your code from Baidu Zhanzhang > Add site > Meta verification',
        ];
    }
}
