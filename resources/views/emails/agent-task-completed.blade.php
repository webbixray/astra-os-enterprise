@extends('layouts.email')

@section('content')
<div class="content">
    <h2 style="margin-top: 0;">Agent Task Completed 🤖</h2>
    <p>Your AI agent <strong>{{ $agentName }}</strong> has completed a task.</p>

    <table style="width: 100%; margin: 24px 0;">
        <tr class="detail">
            <td class="detail-label">Agent</td>
            <td class="detail-value">{{ $agentName }}</td>
        </tr>
        <tr class="detail">
            <td class="detail-label">Task</td>
            <td class="detail-value">{{ $taskDescription }}</td>
        </tr>
        <tr class="detail">
            <td class="detail-label">Status</td>
            <td class="detail-value"><span class="badge badge-success">Completed</span></td>
        </tr>
        @if(isset($duration))
        <tr class="detail">
            <td class="detail-label">Duration</td>
            <td class="detail-value">{{ $duration }}</td>
        </tr>
        @endif
        @if(isset($result))
        <tr class="detail">
            <td class="detail-label">Result</td>
            <td class="detail-value">{{ Str::limit($result, 100) }}</td>
        </tr>
        @endif
    </table>

    @if(isset($detailsUrl))
    <div style="text-align: center; margin-top: 32px;">
        <a href="{{ $detailsUrl }}" class="button">View Task Details</a>
    </div>
    @endif
</div>
@endsection
