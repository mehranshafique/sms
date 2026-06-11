<?php

namespace App\Services;

use App\Support\DocumentationKeywordSearch;
use App\Support\MarkdownToHtml;
use Illuminate\Support\Facades\File;

class HelpCenterService
{
    public function locale(): string
    {
        $locale = app()->getLocale();

        return in_array($locale, ['en', 'fr'], true) ? $locale : 'en';
    }

    public function categories(): array
    {
        $locale = $this->locale();
        $config = config('help_center.categories', []);
        $articles = config('help_center.articles', []);

        $result = [];
        foreach ($config as $slug => $meta) {
            $articleSlugs = array_keys(array_filter($articles, fn ($a) => ($a['category'] ?? '') === $slug));
            $result[$slug] = [
                'slug' => $slug,
                'icon' => $meta['icon'] ?? 'fa-folder',
                'title' => $meta['title'][$locale] ?? $meta['title']['en'] ?? $slug,
                'articles' => $this->articlesForCategory($slug),
            ];
        }

        return $result;
    }

    public function articlesForCategory(string $categorySlug): array
    {
        $locale = $this->locale();
        $articles = config('help_center.articles', []);
        $list = [];

        foreach ($articles as $slug => $meta) {
            if (($meta['category'] ?? '') !== $categorySlug) {
                continue;
            }
            if (!$this->articleExists($slug, $locale)) {
                continue;
            }
            $list[] = [
                'slug' => $slug,
                'title' => $meta['title'][$locale] ?? $meta['title']['en'] ?? $slug,
                'summary' => $meta['summary'][$locale] ?? $meta['summary']['en'] ?? '',
                'category' => $categorySlug,
            ];
        }

        return $list;
    }

    public function allArticles(): array
    {
        $locale = $this->locale();
        $articles = config('help_center.articles', []);
        $list = [];

        foreach ($articles as $slug => $meta) {
            if (!$this->articleExists($slug, $locale)) {
                continue;
            }
            $list[] = [
                'slug' => $slug,
                'title' => $meta['title'][$locale] ?? $meta['title']['en'] ?? $slug,
                'summary' => $meta['summary'][$locale] ?? $meta['summary']['en'] ?? '',
                'category' => $meta['category'] ?? 'general',
            ];
        }

        return $list;
    }

    public function getArticleMeta(string $slug): ?array
    {
        $meta = config("help_center.articles.{$slug}");
        if (!$meta) {
            return null;
        }

        $locale = $this->locale();

        return [
            'slug' => $slug,
            'title' => $meta['title'][$locale] ?? $meta['title']['en'] ?? $slug,
            'summary' => $meta['summary'][$locale] ?? $meta['summary']['en'] ?? '',
            'category' => $meta['category'] ?? 'general',
        ];
    }

    public function renderArticle(string $slug): ?array
    {
        $meta = $this->getArticleMeta($slug);
        if (!$meta) {
            return null;
        }

        $locale = $this->locale();
        $path = $this->articlePath($slug, $locale);

        if (!File::exists($path)) {
            $path = $this->articlePath($slug, 'en');
        }

        if (!File::exists($path)) {
            return null;
        }

        $markdown = File::get($path);
        $meta['html'] = MarkdownToHtml::convert($markdown);
        $meta['markdown_source'] = $path;

        return $meta;
    }

    public function search(string $query): array
    {
        $keywords = DocumentationKeywordSearch::keywords($query);
        if ($keywords === []) {
            return [];
        }

        $results = [];
        foreach ($this->allArticles() as $article) {
            $path = $this->articlePath($article['slug'], $this->locale());
            if (!File::exists($path)) {
                $path = $this->articlePath($article['slug'], 'en');
            }
            $content = File::exists($path) ? File::get($path) : '';

            if (!DocumentationKeywordSearch::matches($article['title'], $article['summary'] . "\n" . $content, $keywords)) {
                continue;
            }

            $snippets = DocumentationKeywordSearch::snippets($content, $keywords);
            $score = DocumentationKeywordSearch::score($article['title'], $article['summary'] . "\n" . $content, $keywords);
            $article['summary'] = $snippets[0] ?? $article['summary'];
            $article['snippets'] = $snippets;
            $article['url'] = route('help.show', $article['slug']);
            $article['score'] = $score;
            $results[] = $article;
        }

        usort($results, fn (array $a, array $b) => $b['score'] <=> $a['score']);

        return $results;
    }

    private function articleExists(string $slug, string $locale): bool
    {
        return File::exists($this->articlePath($slug, $locale))
            || File::exists($this->articlePath($slug, 'en'));
    }

    private function articlePath(string $slug, string $locale): string
    {
        return resource_path("help/{$locale}/{$slug}.md");
    }
}
