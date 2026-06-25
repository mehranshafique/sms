{{--
    Personalized welcome banner.
    Expects optional: $institution, $currentSession, $subtitle (fallback line)
--}}
@include('dashboard.partials.dashboard-styles')
@php
    $user = Auth::user();
    $firstName = strtok(trim($user->name ?? ''), ' ') ?: ($user->name ?? __('dashboard.default_role'));
    $schoolName = $institution->name ?? '';
    $sessionName = isset($currentSession) && $currentSession ? $currentSession->name : null;
    $todayLabel = now()->translatedFormat('l, d F Y');
    try {
        $welcomePlan = app(\App\Services\PlanContextService::class)->snapshot();
    } catch (\Throwable $e) {
        $welcomePlan = ['is_pro' => false, 'plan_name' => null, 'is_active' => false];
    }
@endphp

<div class="row dashboard-welcome-row">
    <div class="col-xl-12">
        <div class="dash-hero shadow-sm">
            <div class="d-flex flex-wrap justify-content-between align-items-center p-4" style="position: relative; z-index: 1;">
                <div>
                    <span class="dash-hero__chip mb-2"><i class="la la-calendar"></i> {{ $todayLabel }}</span>
                    @if(!empty($welcomePlan['is_pro']) && !empty($welcomePlan['plan_name']))
                        <span class="dash-hero__chip mb-2 ms-1" style="background:rgba(251,191,36,.35);">
                            <i class="la la-crown"></i> {{ $welcomePlan['plan_name'] }} PRO
                        </span>
                    @endif
                    @if($schoolName && $sessionName)
                        <h3 class="text-white fw-bold mb-1">
                            {{ __('dashboard.welcome_personalized', [
                                'name' => $firstName,
                                'school' => $schoolName,
                                'session' => $sessionName,
                            ]) }}
                        </h3>
                    @elseif($schoolName)
                        <h3 class="text-white fw-bold mb-1">
                            {{ __('dashboard.welcome_personalized_no_session', [
                                'name' => $firstName,
                                'school' => $schoolName,
                            ]) }}
                        </h3>
                    @else
                        <h3 class="text-white fw-bold mb-1">
                            {{ __('dashboard.welcome_personalized_no_school', ['name' => $firstName]) }}
                        </h3>
                    @endif
                    @if(!empty($subtitle))
                        <p class="mb-0 text-white opacity-75">{{ $subtitle }}</p>
                    @endif
                </div>
                @if(!isset($showIcon) || $showIcon !== false)
                    <i class="la la-graduation-cap opacity-25 d-none d-md-block" style="font-size: 4rem;"></i>
                @endif
            </div>
        </div>
    </div>
</div>
