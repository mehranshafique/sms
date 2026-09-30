@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <h4 class="mb-3">{{ $election->title }} — {{ __('voting.tab_results') }}</h4>
        @foreach($blocks as $block)
            <div class="card mb-3">
                <div class="card-header"><strong>{{ $block['position_name'] }}</strong></div>
                <div class="card-body table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>{{ __('voting.candidate_name') }}</th><th>{{ __('voting.votes') }}</th><th>%</th></tr></thead>
                        <tbody>
                        @foreach($block['candidates'] as $c)
                            <tr><td>{{ $c['name'] }}</td><td>{{ $c['votes'] }}</td><td>{{ $c['percent'] }}%</td></tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
        <a href="{{ route('student.elections.index') }}" class="btn btn-secondary">{{ __('voting.my_elections') }}</a>
    </div>
</div>
@endsection
