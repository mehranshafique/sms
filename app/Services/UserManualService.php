<?php

namespace App\Services;

use App\Support\DocumentationKeywordSearch;
use App\Support\MarkdownToHtml;
use Illuminate\Support\Facades\File;

class UserManualService
{
    protected ?array $webCache = null;

    protected ?array $mobileCache = null;

    public function locale(): string
    {
        $locale = app()->getLocale();

        return in_array($locale, ['en', 'fr'], true) ? $locale : 'en';
    }

    public function webPath(): string
    {
        $localized = base_path('doc/markdown/' . $this->locale() . '/user-manual.md');
        if ($this->locale() !== 'en' && File::exists($localized)) {
            return $localized;
        }

        return base_path('doc/markdown/user-manual.md');
    }

    public function mobilePath(): string
    {
        $localized = base_path('doc/markdown/' . $this->locale() . '/mobile-app-user-manual.md');
        if ($this->locale() !== 'en' && File::exists($localized)) {
            return $localized;
        }

        return base_path('doc/markdown/mobile-app-user-manual.md');
    }

    public function contentLocale(string $type = 'web'): string
    {
        $file = $type === 'mobile'
            ? base_path('doc/markdown/fr/mobile-app-user-manual.md')
            : base_path('doc/markdown/fr/user-manual.md');

        if ($this->locale() === 'fr' && File::exists($file)) {
            return 'fr';
        }

        return 'en';
    }

    public function isWebFallback(): bool
    {
        return $this->locale() === 'fr' && !File::exists(base_path('doc/markdown/fr/user-manual.md'));
    }

    public function isMobileFallback(): bool
    {
        return $this->locale() === 'fr' && !File::exists(base_path('doc/markdown/fr/mobile-app-user-manual.md'));
    }

    /** @return array{introduction: ?array, parts: array<int, array>, modules: array<string, array>} */
    public function parseWebManual(): array
    {
        if ($this->webCache !== null) {
            return $this->webCache;
        }

        if (!File::exists($this->webPath())) {
            return $this->webCache = ['introduction' => null, 'parts' => [], 'modules' => []];
        }

        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", File::get($this->webPath())));
        $introduction = ['slug' => 'introduction', 'title' => 'How to Read This Manual', 'markdown' => ''];
        $parts = [];
        $modules = [];
        $currentPart = null;
        $currentModule = null;
        $seenFirstPart = false;

        $flushModule = function () use (&$currentModule, &$currentPart, &$parts, &$modules) {
            if ($currentModule === null) {
                return;
            }
            $entry = [
                'id' => $currentModule['id'],
                'slug' => $currentModule['slug'],
                'title' => $currentModule['title'],
                'markdown' => trim($currentModule['markdown']),
                'part_slug' => $currentPart['slug'] ?? null,
                'part_title' => $currentPart['title'] ?? null,
            ];
            $modules[$entry['slug']] = $entry;
            if ($currentPart !== null) {
                $parts[count($parts) - 1]['modules'][] = [
                    'id' => $entry['id'],
                    'slug' => $entry['slug'],
                    'title' => $entry['title'],
                ];
            }
            $currentModule = null;
        };

        foreach ($lines as $line) {
            if (preg_match('/^# PART(?:IE)? ([A-Z]) — (.+)$/', $line, $m)) {
                $flushModule();
                $seenFirstPart = true;
                $currentPart = [
                    'id' => $m[1],
                    'slug' => 'part-' . strtolower($m[1]),
                    'title' => trim($m[2]),
                    'modules' => [],
                ];
                $parts[] = $currentPart;
                continue;
            }

            if (preg_match('/^## Module ([A-Z]\d+[A-Z]?)\s*:\s*(.+)$/', $line, $m)) {
                $flushModule();
                if (!$seenFirstPart) {
                    $seenFirstPart = true;
                }
                if ($currentPart === null || ($currentPart['id'] ?? '') !== $m[1][0]) {
                    $letter = $m[1][0];
                    $currentPart = [
                        'id' => $letter,
                        'slug' => 'part-' . strtolower($letter),
                        'title' => $this->defaultPartTitle($letter),
                        'modules' => [],
                    ];
                    $parts[] = $currentPart;
                }
                $currentModule = [
                    'id' => $m[1],
                    'slug' => strtolower($m[1]),
                    'title' => trim($m[2]),
                    'markdown' => '',
                ];
                continue;
            }

            if ($currentModule !== null) {
                $currentModule['markdown'] .= $line . "\n";
            } elseif (!$seenFirstPart) {
                if (!preg_match('/^# Digitex School Management System|^# Complete User Manual/', $line)) {
                    $introduction['markdown'] .= $line . "\n";
                }
            }
        }

        $flushModule();

        return $this->webCache = [
            'introduction' => trim($introduction['markdown']) !== '' ? $introduction : null,
            'parts' => $parts,
            'modules' => $modules,
        ];
    }

    /** @return array{introduction: ?array, parts: array<int, array>, sections: array<string, array>} */
    public function parseMobileManual(): array
    {
        if ($this->mobileCache !== null) {
            return $this->mobileCache;
        }

        if (!File::exists($this->mobilePath())) {
            return $this->mobileCache = ['introduction' => null, 'parts' => [], 'sections' => []];
        }

        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", File::get($this->mobilePath())));
        $introduction = ['slug' => 'introduction', 'title' => 'Mobile App — Getting Started', 'markdown' => ''];
        $parts = [];
        $sections = [];
        $current = null;
        $seenFirstPart = false;

        $flush = function () use (&$current, &$parts, &$sections) {
            if ($current === null) {
                return;
            }
            $entry = [
                'number' => $current['number'],
                'slug' => $current['slug'],
                'title' => $current['title'],
                'markdown' => trim($current['markdown']),
            ];
            $sections[$entry['slug']] = $entry;
            $parts[] = [
                'number' => $entry['number'],
                'slug' => $entry['slug'],
                'title' => $entry['title'],
            ];
            $current = null;
        };

        foreach ($lines as $line) {
            if (preg_match('/^# PART(?:IE)? (\d+) — (.+)$/', $line, $m)) {
                $flush();
                $seenFirstPart = true;
                $num = (int) $m[1];
                $current = [
                    'number' => $num,
                    'slug' => 'part-' . $num,
                    'title' => trim($m[2]),
                    'markdown' => '',
                ];
                continue;
            }

            if ($current !== null) {
                $current['markdown'] .= $line . "\n";
            } elseif (!$seenFirstPart) {
                if (!preg_match('/^# Digitex Portal|^# Complete Guide for All Users/', $line)) {
                    $introduction['markdown'] .= $line . "\n";
                }
            }
        }

        $flush();

        return $this->mobileCache = [
            'introduction' => trim($introduction['markdown']) !== '' ? $introduction : null,
            'parts' => $parts,
            'sections' => $sections,
        ];
    }

    public function renderWebModule(string $slug): ?array
    {
        $parsed = $this->parseWebManual();

        if ($slug === 'introduction' && $parsed['introduction']) {
            return $this->wrapRendered($parsed['introduction'], null, null, 'web');
        }

        if (!isset($parsed['modules'][$slug])) {
            return null;
        }

        $module = $parsed['modules'][$slug];
        $flat = array_values($parsed['modules']);
        $index = array_search($slug, array_column($flat, 'slug'), true);

        return $this->wrapRendered(
            $module,
            $index > 0 ? $flat[$index - 1] : ($parsed['introduction'] ? ['slug' => 'introduction', 'title' => $parsed['introduction']['title']] : null),
            $index < count($flat) - 1 ? $flat[$index + 1] : null,
            'web',
            $module['part_title'] ?? null
        );
    }

    public function renderMobileSection(string $slug): ?array
    {
        $parsed = $this->parseMobileManual();

        if ($slug === 'introduction' && $parsed['introduction']) {
            $first = $parsed['parts'][0] ?? null;

            return $this->wrapRendered(
                $parsed['introduction'],
                null,
                $first ? ['slug' => $first['slug'], 'title' => $first['title']] : null,
                'mobile'
            );
        }

        if (!isset($parsed['sections'][$slug])) {
            return null;
        }

        $section = $parsed['sections'][$slug];
        $flat = array_values($parsed['sections']);
        $index = array_search($slug, array_column($flat, 'slug'), true);

        return $this->wrapRendered(
            $section,
            $index === 0
                ? ($parsed['introduction'] ? ['slug' => 'introduction', 'title' => $parsed['introduction']['title']] : null)
                : ['slug' => $flat[$index - 1]['slug'], 'title' => $flat[$index - 1]['title']],
            $index < count($flat) - 1 ? ['slug' => $flat[$index + 1]['slug'], 'title' => $flat[$index + 1]['title']] : null,
            'mobile'
        );
    }

    public function search(string $query): array
    {
        $keywords = DocumentationKeywordSearch::keywords($query);
        if ($keywords === []) {
            return [];
        }

        $results = [];

        foreach ($this->parseWebManual()['modules'] as $module) {
            $result = $this->buildSearchResult(
                $keywords,
                'web',
                $module['slug'],
                "Module {$module['id']}: {$module['title']}",
                $module['title'],
                $module['markdown'],
                route('manual.web.show', $module['slug'])
            );
            if ($result) {
                $results[] = $result;
            }
        }

        $webIntro = $this->parseWebManual()['introduction'];
        if ($webIntro) {
            $result = $this->buildSearchResult(
                $keywords,
                'web',
                'introduction',
                $webIntro['title'],
                $webIntro['title'],
                $webIntro['markdown'],
                route('manual.web.show', 'introduction')
            );
            if ($result) {
                $results[] = $result;
            }
        }

        foreach ($this->parseMobileManual()['sections'] as $section) {
            $result = $this->buildSearchResult(
                $keywords,
                'mobile',
                $section['slug'],
                "Mobile Part {$section['number']}: {$section['title']}",
                $section['title'],
                $section['markdown'],
                route('manual.mobile.show', $section['slug'])
            );
            if ($result) {
                $results[] = $result;
            }
        }

        usort($results, fn (array $a, array $b) => $b['score'] <=> $a['score']);

        return $results;
    }

    public function webModuleCount(): int
    {
        return count($this->parseWebManual()['modules']);
    }

    public function mobilePartCount(): int
    {
        return count($this->parseMobileManual()['parts']);
    }

    private function wrapRendered(array $item, ?array $prev, ?array $next, string $type, ?string $partTitle = null): array
    {
        return [
            'slug' => $item['slug'],
            'title' => $item['title'],
            'id' => $item['id'] ?? ($item['number'] ?? null),
            'part_title' => $partTitle,
            'html' => MarkdownToHtml::convert($item['markdown']),
            'prev' => $prev,
            'next' => $next,
            'type' => $type,
        ];
    }

    /** @param list<string> $keywords */
    private function buildSearchResult(
        array $keywords,
        string $type,
        string $slug,
        string $title,
        string $matchTitle,
        string $markdown,
        string $url
    ): ?array {
        if (!DocumentationKeywordSearch::matches($matchTitle, $markdown, $keywords)) {
            return null;
        }

        $snippets = DocumentationKeywordSearch::snippets($markdown, $keywords);

        return [
            'type' => $type,
            'slug' => $slug,
            'title' => $title,
            'summary' => $snippets[0] ?? $this->excerpt($markdown),
            'snippets' => $snippets,
            'url' => $url,
            'score' => DocumentationKeywordSearch::score($matchTitle, $markdown, $keywords),
        ];
    }

    private function excerpt(string $markdown, int $length = 140): string
    {
        $text = preg_replace('/[#*`|\[\]()>-]/', ' ', $markdown);
        $text = preg_replace('/\s+/', ' ', trim($text));

        return strlen($text) <= $length ? $text : substr($text, 0, $length) . '…';
    }

    private function defaultPartTitle(string $letter): string
    {
        return match ($letter) {
            'I' => 'Communication',
            default => 'Part ' . $letter,
        };
    }
}
