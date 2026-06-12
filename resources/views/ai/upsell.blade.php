@extends('layout.layout')

@section('content')
@include('ai.partials.ai-styles')
<div class="content-body">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="ai-hero shadow-sm mb-4">
                    <div class="p-4 text-center" style="position:relative; z-index:1;">
                        <i class="la la-robot" style="font-size:3.5rem; color:rgba(255,255,255,.85);"></i>
                        <h3 class="text-white fw-bold mt-2 mb-1">{{ __('ai.upsell_title') }}</h3>
                        <p class="mb-0 text-white opacity-75">{{ __('ai.upsell_subtitle') }}</p>
                    </div>
                </div>

                <div class="ai-panel">
                    <div class="ai-panel__body">
                        <p class="text-muted">{{ __('ai.upsell_body') }}</p>
                        <div class="row g-3 mt-1">
                            <div class="col-md-6 d-flex gap-2"><i class="la la-check-circle text-success fs-5"></i><span>{{ __('ai.upsell_point_assistant') }}</span></div>
                            <div class="col-md-6 d-flex gap-2"><i class="la la-check-circle text-success fs-5"></i><span>{{ __('ai.upsell_point_studio') }}</span></div>
                            <div class="col-md-6 d-flex gap-2"><i class="la la-check-circle text-success fs-5"></i><span>{{ __('ai.upsell_point_translate') }}</span></div>
                            <div class="col-md-6 d-flex gap-2"><i class="la la-check-circle text-success fs-5"></i><span>{{ __('ai.upsell_point_comments') }}</span></div>
                        </div>
                        <hr>
                        <p class="mb-0 text-muted small">{{ __('ai.upsell_contact') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
