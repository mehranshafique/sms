<?php

return [
    /*
    | Plan names containing these keywords (case-insensitive) are treated as
    | "Pro" tier for header badges and legacy AI access when ai_enabled was
    | not yet ticked on older packages (e.g. Premium Plan created before AI).
    */
    'pro_keywords' => ['premium', 'enterprise', 'platinum', 'gold', 'pro', 'ultimate'],

    /*
    | Packages with at least this many modules are considered full-tier and
    | receive AI access even if ai_enabled was never set (legacy plans).
    */
    'ai_module_threshold' => 40,
];
