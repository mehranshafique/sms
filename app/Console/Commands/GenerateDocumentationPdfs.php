<?php

namespace App\Console\Commands;

use App\Support\MarkdownToHtml;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateDocumentationPdfs extends Command
{
    protected $signature = 'docs:generate-pdf';

    protected $description = 'Generate PDF manuals from doc/markdown sources';

    public function handle(): int
    {
        $manuals = [
            'user-manual' => 'User Manual',
            'mobile-app-user-manual' => 'Mobile App User Manual',
            'developer-manual' => 'Developer Manual',
            'api-manual' => 'REST API Manual (Hardware & Mobile App)',
        ];

        $markdownDir = base_path('doc/markdown');
        $pdfDir = base_path('doc/pdf');

        if (!File::isDirectory($pdfDir)) {
            File::makeDirectory($pdfDir, 0755, true);
        }

        $generatedAt = now()->format('F j, Y');

        foreach ($manuals as $slug => $title) {
            $path = "{$markdownDir}/{$slug}.md";
            if (!File::exists($path)) {
                $this->error("Missing: {$path}");
                continue;
            }

            $markdown = File::get($path);
            $body = MarkdownToHtml::convert($markdown);

            $pdf = Pdf::loadView('doc.pdf-layout', compact('title', 'body', 'generatedAt'))
                ->setPaper('a4');

            $filename = str_replace(' ', '-', $title) . '.pdf';
            $output = "{$pdfDir}/{$filename}";
            $pdf->save($output);

            $this->info("Created: {$output}");
        }

        $this->info('All documentation PDFs generated in doc/pdf/');

        return self::SUCCESS;
    }
}
