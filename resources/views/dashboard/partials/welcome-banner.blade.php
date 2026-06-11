{{--
    Personalized welcome banner.
    Expects optional: $institution, $currentSession, $subtitle (fallback line)
--}}
@php
    $user = Auth::user();
    $firstName = strtok(trim($user->name ?? ''), ' ') ?: ($user->name ?? __('dashboard.default_role'));
    $schoolName = $institution->name ?? '';
    $sessionName = isset($currentSession) && $currentSession ? $currentSession->name : null;
@endphp

<div class="row mb-4">
    <div class="col-xl-12">
        <div class="card bg-primary text-white shadow-sm border-0">
            <div class="card-body d-flex justify-content-between align-items-center p-4">
                <div>
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
                @if(isset($showIcon) && $showIcon !== false)
                    <i class="la la-graduation-cap opacity-25 d-none d-md-block" style="font-size: 3rem;"></i>
                @endif
            </div>
        </div>
    </div>
</div>
