<?php

namespace App\Support;

class MarkdownToHtml
{
    public static function convert(string $markdown): string
    {
        $markdown = str_replace(["\r\n", "\r"], "\n", $markdown);
        $lines = explode("\n", $markdown);
        $html = [];
        $inCode = false;
        $codeLang = '';
        $codeBuffer = [];
        $inTable = false;
        $tableRows = [];

        $flushTable = function () use (&$html, &$tableRows, &$inTable) {
            if (empty($tableRows)) {
                return;
            }
            $html[] = '<table>';
            foreach ($tableRows as $i => $row) {
                $cells = array_map('trim', explode('|', trim($row, '|')));
                $tag = ($i === 0) ? 'th' : 'td';
                if ($i === 1 && preg_match('/^[\-\:\|\s]+$/', $row)) {
                    continue;
                }
                if ($i === 1 && isset($tableRows[1]) && preg_match('/^[\-\:\|\s]+$/', $tableRows[1])) {
                    continue;
                }
                $html[] = '<tr>';
                foreach ($cells as $cell) {
                    $html[] = "<{$tag}>" . self::inline($cell) . "</{$tag}>";
                }
                $html[] = '</tr>';
            }
            $html[] = '</table>';
            $tableRows = [];
            $inTable = false;
        };

        foreach ($lines as $line) {
            if (preg_match('/^```(\w*)/', $line, $m)) {
                if ($inCode) {
                    $html[] = '<pre><code>' . htmlspecialchars(implode("\n", $codeBuffer)) . '</code></pre>';
                    $codeBuffer = [];
                    $inCode = false;
                } else {
                    $flushTable();
                    $inCode = true;
                    $codeLang = $m[1] ?? '';
                }
                continue;
            }

            if ($inCode) {
                $codeBuffer[] = $line;
                continue;
            }

            if (preg_match('/^\|(.+)\|$/', $line)) {
                $tableRows[] = $line;
                $inTable = true;
                continue;
            } elseif ($inTable) {
                $flushTable();
            }

            if (trim($line) === '') {
                $html[] = '';
                continue;
            }

            if (preg_match('/^#{1,6}\s+(.+)$/', $line, $m)) {
                $level = strlen(strtok($line, ' '));
                $html[] = "<h{$level}>" . self::inline($m[1]) . "</h{$level}>";
                continue;
            }

            if (preg_match('/^---+\s*$/', $line)) {
                $html[] = '<hr>';
                continue;
            }

            if (preg_match('/^(\d+)\.\s+(.+)$/', $line, $m)) {
                $html[] = '<li>' . self::inline($m[2]) . '</li>';
                continue;
            }

            if (preg_match('/^[-*]\s+(.+)$/', $line, $m)) {
                $html[] = '<li>' . self::inline($m[1]) . '</li>';
                continue;
            }

            $html[] = '<p>' . self::inline($line) . '</p>';
        }

        if ($inCode && !empty($codeBuffer)) {
            $html[] = '<pre><code>' . htmlspecialchars(implode("\n", $codeBuffer)) . '</code></pre>';
        }
        if ($inTable) {
            $flushTable();
        }

        $body = implode("\n", $html);
        $body = preg_replace_callback('/(?:<li>.*?<\/li>\n?)+/s', static function ($m) {
            return '<ul>' . $m[0] . '</ul>';
        }, $body);

        return $body;
    }

    private static function inline(string $text): string
    {
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/`(.+?)`/', '<code>$1</code>', $text);
        $text = preg_replace('/\[(.+?)\]\((.+?)\)/', '<a href="$2">$1</a>', $text);

        return $text;
    }
}
