<?php

namespace App\Services\Seo\Optimization\Features;

/**
 * Validates JSON-LD schema against Google's Rich Results required-field rules
 * before it is persisted or rendered — mirroring AIOSEO's built-in schema
 * validator + "Test with Google" workflow.
 *
 * It is intentionally dependency-free (no AI call): a fast structural check
 * that flags missing required properties so the AI Board can show a
 * "Schema invalid" badge and the batch job can skip writing broken markup.
 */
class SchemaValidatorService
{
    /**
     * Required + recommended properties per @type. "required" failing makes the
     * schema invalid (won't earn a rich result); "recommended" only warns.
     *
     * @var array<string,array{required:array<int,string>,recommended:array<int,string>}>
     */
    protected array $rules = [
        'Product' => [
            'required'    => ['name'],
            'recommended' => ['image', 'description', 'offers'],
        ],
        'Offer' => [
            'required'    => ['price', 'priceCurrency'],
            'recommended' => ['availability', 'url'],
        ],
        'Article' => [
            'required'    => ['headline'],
            'recommended' => ['image', 'datePublished', 'author', 'publisher'],
        ],
        'FAQPage' => [
            'required'    => ['mainEntity'],
            'recommended' => [],
        ],
        'HowTo' => [
            'required'    => ['name', 'step'],
            'recommended' => ['image', 'totalTime'],
        ],
        'LocalBusiness' => [
            'required'    => ['name', 'address'],
            'recommended' => ['telephone', 'image', 'openingHours', 'priceRange'],
        ],
        'Organization' => [
            'required'    => ['name', 'url'],
            'recommended' => ['logo', 'sameAs', 'contactPoint'],
        ],
        'BreadcrumbList' => [
            'required'    => ['itemListElement'],
            'recommended' => [],
        ],
        'Review' => [
            'required'    => ['itemReviewed', 'reviewRating', 'author'],
            'recommended' => [],
        ],
        'AggregateRating' => [
            'required'    => ['ratingValue', 'reviewCount'],
            'recommended' => ['bestRating', 'worstRating'],
        ],
        'Event' => [
            'required'    => ['name', 'startDate', 'location'],
            'recommended' => ['endDate', 'image', 'offers'],
        ],
        'VideoObject' => [
            'required'    => ['name', 'thumbnailUrl', 'uploadDate'],
            'recommended' => ['description', 'duration', 'contentUrl'],
        ],
        'CollectionPage' => [
            'required'    => ['name'],
            'recommended' => ['url', 'description'],
        ],
        'WebPage' => [
            'required'    => ['name'],
            'recommended' => ['url', 'description'],
        ],
    ];

    /**
     * Validate a single schema node or an array of nodes.
     *
     * @return array{valid:bool, errors:array<int,string>, warnings:array<int,string>, types:array<int,string>}
     */
    public function validate($schema): array
    {
        $errors   = [];
        $warnings = [];
        $types    = [];

        foreach ($this->normalize($schema) as $node) {
            if (!is_array($node)) {
                $errors[] = 'Schema node is not an object.';
                continue;
            }

            $type = $this->typeOf($node);
            if ($type === null) {
                $errors[] = 'Schema node is missing an @type.';
                continue;
            }
            $types[] = $type;

            $this->validateNode($node, $type, $errors, $warnings);

            // Validate a few well-known nested nodes.
            if ($type === 'Product' && isset($node['offers']) && is_array($node['offers'])) {
                $this->validateNode($node['offers'], 'Offer', $errors, $warnings, 'offers.');
            }
            if (isset($node['aggregateRating']) && is_array($node['aggregateRating'])) {
                $this->validateNode($node['aggregateRating'], 'AggregateRating', $errors, $warnings, 'aggregateRating.');
            }
            if ($type === 'FAQPage') {
                $this->validateFaq($node, $errors, $warnings);
            }
            if ($type === 'BreadcrumbList') {
                $this->validateBreadcrumb($node, $errors, $warnings);
            }
        }

        return [
            'valid'    => empty($errors),
            'errors'   => array_values(array_unique($errors)),
            'warnings' => array_values(array_unique($warnings)),
            'types'    => array_values(array_unique($types)),
        ];
    }

    /** Convenience: just the boolean. */
    public function isValid($schema): bool
    {
        return $this->validate($schema)['valid'];
    }

    /** Build a "Test with Google" Rich Results URL for a page. */
    public function googleTestUrl(string $pageUrl): string
    {
        return 'https://search.google.com/test/rich-results?url=' . urlencode($pageUrl);
    }

    // ──────────────────────────────────────────────────────────────────────

    protected function normalize($schema): array
    {
        if (!is_array($schema)) {
            return [];
        }
        // Array of nodes (list) vs a single associative node.
        if (array_is_list($schema)) {
            return $schema;
        }
        // @graph container.
        if (isset($schema['@graph']) && is_array($schema['@graph'])) {
            return $schema['@graph'];
        }
        return [$schema];
    }

    protected function typeOf(array $node): ?string
    {
        $type = $node['@type'] ?? null;
        if (is_array($type)) {
            $type = $type[0] ?? null;
        }
        return is_string($type) ? $type : null;
    }

    protected function validateNode(array $node, string $type, array &$errors, array &$warnings, string $prefix = ''): void
    {
        $rule = $this->rules[$type] ?? null;
        if (!$rule) {
            return; // unknown type — don't penalise
        }

        foreach ($rule['required'] as $field) {
            if ($this->missing($node, $field)) {
                $errors[] = sprintf('%s%s: required property "%s" is missing or empty.', $prefix, $type, $field);
            }
        }
        foreach ($rule['recommended'] as $field) {
            if ($this->missing($node, $field)) {
                $warnings[] = sprintf('%s%s: recommended property "%s" is missing.', $prefix, $type, $field);
            }
        }
    }

    protected function validateFaq(array $node, array &$errors, array &$warnings): void
    {
        $entities = $node['mainEntity'] ?? [];
        if (!is_array($entities) || empty($entities)) {
            $errors[] = 'FAQPage: mainEntity must contain at least one Question.';
            return;
        }
        foreach ($entities as $i => $q) {
            if (!is_array($q)) {
                continue;
            }
            if ($this->missing($q, 'name')) {
                $errors[] = "FAQPage: question #{$i} is missing 'name'.";
            }
            $answer = $q['acceptedAnswer'] ?? null;
            if (!is_array($answer) || $this->missing($answer, 'text')) {
                $errors[] = "FAQPage: question #{$i} is missing 'acceptedAnswer.text'.";
            }
        }
    }

    protected function validateBreadcrumb(array $node, array &$errors, array &$warnings): void
    {
        $items = $node['itemListElement'] ?? [];
        if (!is_array($items) || empty($items)) {
            $errors[] = 'BreadcrumbList: itemListElement must contain at least one ListItem.';
            return;
        }
        foreach ($items as $i => $item) {
            if (!is_array($item)) {
                continue;
            }
            foreach (['position', 'name'] as $field) {
                if ($this->missing($item, $field)) {
                    $errors[] = "BreadcrumbList: item #{$i} is missing '{$field}'.";
                }
            }
        }
    }

    protected function missing(array $node, string $field): bool
    {
        if (!array_key_exists($field, $node)) {
            return true;
        }
        $value = $node[$field];
        if ($value === null || $value === '') {
            return true;
        }
        if (is_array($value) && empty($value)) {
            return true;
        }
        return false;
    }
}
