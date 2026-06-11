<?php

namespace App\Support;

class DocumentationKeywordSearch
{
    /** @var list<string> */
    private const STOP_WORDS = [
        'a', 'an', 'the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'from',
        'is', 'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had', 'do', 'does', 'did',
        'will', 'would', 'could', 'should', 'may', 'might', 'must', 'shall', 'can', 'how', 'what',
        'when', 'where', 'why', 'which', 'who', 'whom', 'this', 'that', 'these', 'those', 'i', 'me',
        'my', 'we', 'our', 'you', 'your', 'it', 'its', 'they', 'them', 'their',
    ];

    /** @return list<string> */
    public static function keywords(string $query): array
    {
        $normalized = strtolower(trim($query));
        $normalized = preg_replace('/[^\p{L}\p{N}\s-]/u', ' ', $normalized) ?? $normalized;
        $words = preg_split('/\s+/', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $keywords = [];
        foreach ($words as $word) {
            if (strlen($word) < 2 || in_array($word, self::STOP_WORDS, true)) {
                continue;
            }
            $keywords[] = $word;
        }

        if ($keywords !== []) {
            return array_values(array_unique($keywords));
        }

        return array_values(array_unique(array_filter($words, fn (string $w) => strlen($w) >= 2)));
    }

    /** @param list<string> $keywords */
    public static function score(string $title, string $body, array $keywords): int
    {
        if ($keywords === []) {
            return 0;
        }

        $haystack = strtolower($title . "\n" . $body);
        $score = 0;

        foreach ($keywords as $keyword) {
            if (str_contains($haystack, $keyword)) {
                $score++;
                if (str_contains(strtolower($title), $keyword)) {
                    $score++;
                }
            }
        }

        return $score;
    }

    /** @param list<string> $keywords */
    public static function matches(string $title, string $body, array $keywords): bool
    {
        return self::score($title, $body, $keywords) > 0;
    }

    /**
     * @param list<string> $keywords
     * @return list<string>
     */
    public static function snippets(string $text, array $keywords, int $limit = 2, int $length = 160): array
    {
        if ($keywords === []) {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
        $found = [];

        foreach ($lines as $line) {
            $plain = self::plainText($line);
            if ($plain === '') {
                continue;
            }

            $lower = strtolower($plain);
            $hit = false;
            foreach ($keywords as $keyword) {
                if (str_contains($lower, $keyword)) {
                    $hit = true;
                    break;
                }
            }

            if (!$hit) {
                continue;
            }

            $found[] = strlen($plain) <= $length ? $plain : substr($plain, 0, $length) . '…';

            if (count($found) >= $limit) {
                break;
            }
        }

        return $found;
    }

    public static function plainText(string $markdown): string
    {
        $text = preg_replace('/[#*`|\[\]()>-]/', ' ', $markdown);
        $text = preg_replace('/\s+/', ' ', trim($text ?? ''));

        return $text ?? '';
    }
}
