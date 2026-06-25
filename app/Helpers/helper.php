<?php
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

if (!function_exists('institute')) {
    /**
     * Get the institute for the currently logged-in admin user
     *
     * @return \App\Models\Institute|null
     */
    function institute()
    {
        $user = Auth::user();

        // Make sure user is logged in and has admin role
        if ($user && $user->hasRole('Admin')) {
            return $user->institute; // Returns the Institute model
        }

        return null;

    }
}

if(!function_exists('isActive')){
        function isActive($routes, $class = 'mm-active')
        {
            if (is_array($routes)) {
                return in_array(request()->route()->getName(), $routes) ? $class : '';
            }

            return request()->routeIs($routes) ? $class : '';
        }

}

if(!function_exists('authorize')){
    function authorize($permissions){
        $user = auth()->user();
        if (!$user) {
            abort(401);
        }

        try {
            if (!$user->hasPermissionTo($permissions)) {
                abort(403);
            }
        } catch (PermissionDoesNotExist $e) {
            abort(403);
        }

        return true;
    }
}

if (!function_exists('has_ai_access')) {
    /**
     * Whether the current user can use embedded AI tools (plan + platform switch).
     */
    function has_ai_access(): bool
    {
        if (! Auth::check()) {
            return false;
        }

        try {
            return (bool) (app(\App\Services\PlanContextService::class)->snapshot()['has_ai'] ?? false);
        } catch (\Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('brand_logo_url')) {
    /**
     * Resolve the logo shown in the UI: school logo when in institution context, else Digitex default.
     */
    function brand_logo_url(): string
    {
        $default = asset('images/digitex-logo.png');
        $user = Auth::user();

        if (! $user) {
            return $default;
        }

        $activeId = session('active_institution_id', $user->institute_id);

        if ((! $activeId || $activeId === 'global') && $user->institute_id) {
            $activeId = $user->institute_id;
        }

        if ($activeId && $activeId !== 'global') {
            $institution = \App\Models\Institution::find($activeId);
            if ($institution?->logo) {
                return asset('storage/' . $institution->logo);
            }
        }

        return $default;
    }
}

if (!function_exists('brand_logo_alt')) {
    /**
     * Accessible alt text for the resolved brand logo.
     */
    function brand_logo_alt(): string
    {
        $user = Auth::user();
        if (! $user) {
            return config('app.name', 'Digitex');
        }

        $activeId = session('active_institution_id', $user->institute_id);
        if ((! $activeId || $activeId === 'global') && $user->institute_id) {
            $activeId = $user->institute_id;
        }

        if ($activeId && $activeId !== 'global') {
            $institution = \App\Models\Institution::find($activeId);
            if ($institution?->logo) {
                return $institution->name;
            }
        }

        return config('app.name', 'Digitex');
    }
}

if (!function_exists('institution_room_options')) {
    /**
     * Room dropdown options from school configuration (Room 1 … Room N).
     *
     * @return array<string, string>
     */
    function institution_room_options(?int $institutionId = null): array
    {
        if (! $institutionId && Auth::check()) {
            $activeId = session('active_institution_id', Auth::user()->institute_id);
            if ($activeId && $activeId !== 'global') {
                $institutionId = (int) $activeId;
            } elseif (Auth::user()->institute_id) {
                $institutionId = (int) Auth::user()->institute_id;
            }
        }

        if (! $institutionId) {
            return [];
        }

        $count = max(1, (int) \App\Models\InstitutionSetting::get($institutionId, 'school_rooms_count', 10));
        $options = [];
        for ($i = 1; $i <= $count; $i++) {
            $label = __('class_section.room_option', ['number' => $i]);
            $options[$label] = $label;
        }

        return $options;
    }
}

if (! function_exists('class_section_label')) {
    /**
     * Format a class section for display (grade + section).
     *
     * @param  \App\Models\ClassSection|null  $section
     */
    function class_section_label(?\App\Models\ClassSection $section, string $format = 'grade_section'): string
    {
        if (! $section) {
            return __('class_section.not_assigned');
        }

        if (! $section->relationLoaded('gradeLevel')) {
            $section->loadMissing('gradeLevel');
        }

        $grade = trim($section->gradeLevel->name ?? '');
        $sectionName = trim($section->name ?? '');

        return match ($format) {
            'grade_dash_section' => $grade && $sectionName
                ? __('class_section.grade_dash_section', ['grade' => $grade, 'section' => $sectionName])
                : ($grade ?: $sectionName ?: __('class_section.not_assigned')),
            'section_only' => $sectionName ?: __('class_section.not_assigned'),
            default => $grade && $sectionName
                ? __('class_section.grade_section', ['grade' => $grade, 'section' => $sectionName])
                : ($grade ?: $sectionName ?: __('class_section.not_assigned')),
        };
    }
}

if (! function_exists('invoice_installment_label')) {
    function invoice_installment_label(?\App\Models\Invoice $invoice): string
    {
        if (! $invoice) {
            return __('invoice.school_fees');
        }

        $invoice->loadMissing(['items.feeStructure']);
        $names = $invoice->items
            ->map(fn ($item) => trim($item->feeStructure?->name ?? $item->description ?? ''))
            ->filter()
            ->unique()
            ->values();

        return $names->isNotEmpty() ? $names->implode(', ') : __('invoice.school_fees');
    }
}

if (! function_exists('student_notification_context')) {
    /**
     * @return array<string, string>
     */
    function student_notification_context(
        \App\Models\Student $student,
        ?\App\Models\Invoice $invoice = null,
        ?int $academicSessionId = null
    ): array {
        $student->loadMissing(['parent', 'institution']);

        $enrollmentQuery = $student->enrollments()
            ->with(['classSection.gradeLevel', 'academicSession'])
            ->where('status', 'active');

        if ($academicSessionId) {
            $enrollmentQuery->where('academic_session_id', $academicSessionId);
        } elseif ($invoice?->academic_session_id) {
            $enrollmentQuery->where('academic_session_id', $invoice->academic_session_id);
        }

        $enrollment = $enrollmentQuery->latest()->first();
        $section = $enrollment?->classSection;
        $classLabel = class_section_label($section);
        $grade = trim($section?->gradeLevel?->name ?? '');
        $sectionName = trim($section?->name ?? '');
        $sessionName = $enrollment?->academicSession?->name
            ?? $invoice?->academicSession?->name
            ?? 'N/A';

        $parent = $student->parent;
        $currency = \App\Enums\CurrencySymbol::default();
        $schoolName = $student->institution->name ?? config('app.name');

        $installmentName = invoice_installment_label($invoice);
        $amountDue = '';
        $outstandingAmount = '';
        $remainingBalance = '';
        $dueDate = '';

        if ($invoice) {
            $invoice->loadMissing(['items.feeStructure', 'academicSession']);
            $outstanding = max(0, (float) $invoice->total_amount - (float) $invoice->paid_amount);
            $amountDue = $currency . ' ' . number_format((float) $invoice->total_amount, 2);
            $outstandingAmount = $currency . ' ' . number_format($outstanding, 2);
            $remainingBalance = $outstandingAmount;
            $dueDate = $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '';
        }

        return [
            'StudentName' => $student->full_name,
            'ParentName' => $parent?->father_name ?? $parent?->guardian_name ?? 'Parent',
            'Class' => $classLabel,
            'Grade' => $grade,
            'Section' => $sectionName,
            'Session' => $sessionName,
            'SchoolName' => $schoolName,
            'InstallmentName' => $installmentName,
            'AmountDue' => $amountDue,
            'OutstandingAmount' => $outstandingAmount,
            'RemainingBalance' => $remainingBalance,
            'Balance' => $remainingBalance,
            'DueDate' => $dueDate,
            'Currency' => $currency,
            'InvoiceNumber' => $invoice?->invoice_number ?? '',
        ];
    }
}

if (! function_exists('apply_sms_template_tags')) {
    /**
     * @param  array<string, string|int|float>  $data
     */
    function apply_sms_template_tags(string $body, array $data): string
    {
        foreach ($data as $key => $value) {
            $body = str_replace('$' . $key, (string) $value, $body);
            $body = str_replace('{' . $key . '}', (string) $value, $body);
        }

        return $body;
    }
}

if (! function_exists('localize_invoice_description')) {
    /**
     * Display-time fix for legacy invoice rows with hardcoded English " of ".
     */
    function localize_invoice_description(?string $description): string
    {
        if (! $description || app()->getLocale() !== 'fr') {
            return $description ?? '';
        }

        return preg_replace('/\s+of\s+/i', ' de ', $description) ?? $description;
    }
}

if (! function_exists('receipt_display_number')) {
    function receipt_display_number(\App\Models\Invoice $invoice): string
    {
        $lastPayment = $invoice->payments->last();

        return $lastPayment?->receipt_number ?? $invoice->invoice_number;
    }
}

if (! function_exists('spellout_amount_fallback')) {
    /**
     * Fallback when ext-intl / NumberFormatter is unavailable.
     */
    function spellout_amount_fallback(int $number): string
    {
        if ($number === 0) {
            return app()->getLocale() === 'fr' ? 'zéro' : 'zero';
        }

        if (app()->getLocale() === 'fr') {
            return spellout_french_amount($number);
        }

        return spellout_english_amount($number);
    }

    function spellout_english_amount(int $number): string
    {
        $ones = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten',
            'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'];
        $tens = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];

        $chunk = function (int $n) use ($ones, $tens): string {
            if ($n < 20) {
                return $ones[$n];
            }
            if ($n < 100) {
                return trim($tens[intdiv($n, 10)] . ($n % 10 ? '-' . $ones[$n % 10] : ''));
            }

            return trim($ones[intdiv($n, 100)] . ' hundred' . ($n % 100 ? ' ' . spellout_english_amount($n % 100) : ''));
        };

        $parts = [];
        foreach ([1000000 => 'million', 1000 => 'thousand'] as $unit => $label) {
            if ($number >= $unit) {
                $parts[] = trim($chunk(intdiv($number, $unit)) . ' ' . $label);
                $number %= $unit;
            }
        }
        if ($number > 0) {
            $parts[] = $chunk($number);
        }

        return implode(' ', array_filter($parts));
    }

    function spellout_french_amount(int $number): string
    {
        $ones = ['', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf', 'dix',
            'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize', 'dix-sept', 'dix-huit', 'dix-neuf'];
        $tens = ['', '', 'vingt', 'trente', 'quarante', 'cinquante', 'soixante', 'soixante', 'quatre-vingt', 'quatre-vingt'];

        $underHundred = function (int $n) use ($ones, $tens): string {
            if ($n < 20) {
                return $ones[$n];
            }
            if ($n < 70) {
                $ten = intdiv($n, 10);
                $rest = $n % 10;

                return $rest === 1 && $ten !== 8
                    ? $tens[$ten] . '-et-un'
                    : trim($tens[$ten] . ($rest ? '-' . $ones[$rest] : ''));
            }
            if ($n < 80) {
                return 'soixante-' . spellout_french_amount($n - 60);
            }
            if ($n < 100) {
                $rest = $n - 80;

                return $rest > 0
                    ? 'quatre-vingt-' . spellout_french_amount($rest)
                    : 'quatre-vingts';
            }

            return '';
        };

        $chunk = function (int $n) use ($ones, $underHundred): string {
            if ($n < 100) {
                return $underHundred($n);
            }
            $hundreds = intdiv($n, 100);
            $rest = $n % 100;
            $hundredWord = $hundreds === 1 ? 'cent' : $ones[$hundreds] . ' cent' . ($hundreds > 1 && $rest === 0 ? 's' : '');

            return trim($hundredWord . ($rest ? ' ' . $underHundred($rest) : ''));
        };

        $parts = [];
        if ($number >= 1000000) {
            $millions = intdiv($number, 1000000);
            $parts[] = trim($chunk($millions) . ' million' . ($millions > 1 ? 's' : ''));
            $number %= 1000000;
        }
        if ($number >= 1000) {
            $thousands = intdiv($number, 1000);
            $parts[] = trim(($thousands === 1 ? 'mille' : $chunk($thousands) . ' mille'));
            $number %= 1000;
        }
        if ($number > 0) {
            $parts[] = $chunk($number);
        }

        return implode(' ', array_filter($parts));
    }
}

if (! function_exists('amount_in_words')) {
    function amount_in_words(float $amount): string
    {
        $whole = (int) round($amount);

        if (class_exists(\NumberFormatter::class)) {
            $formatter = \NumberFormatter::create(app()->getLocale(), \NumberFormatter::SPELLOUT);
            if ($formatter) {
                $formatted = $formatter->format($whole);
                if ($formatted !== false) {
                    return $formatted;
                }
            }
        }

        return spellout_amount_fallback($whole);
    }
}
