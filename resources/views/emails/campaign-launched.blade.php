@extends('layouts.email')

@section('content')
<div class="content">
    <h2 style="margin-top: 0;">Campaign Launched 🚀</h2>
    <p>Your campaign <strong>{{ $campaignName }}</strong> has been launched successfully and is now live.</p>

    <table style="width: 100%; margin: 24px 0;">
        <tr class="detail">
            <td class="detail-label">Campaign</td>
            <td class="detail-value">{{ $campaignName }}</td>
        </tr>
        <tr class="detail">
            <td class="detail-label">Status</td>
            <td class="detail-value"><span class="badge badge-success">Active</span></td>
        </tr>
        @if(isset($platform))
        <tr class="detail">
            <td class="detail-label">Platform</td>
            <td class="detail-value">{{ ucfirst($platform) }}</td>
        </tr>
        @endif
        @if(isset($budget))
        <tr class="detail">
            <td class="detail-label">Budget</td>
            <td class="detail-value">${{ number_format($budget, 2) }}</td>
        </tr>
        @endif
        <tr class="detail">
            <td class="detail-label">Launched At</td>
            <td class="detail-value">{{ now()->format('F j, Y g:i A') }}</td>
        </tr>
    </table>

    <div style="text-align: center; margin-top: 32px;">
        <a href="{{ $dashboardUrl ?? '#' }}" class="button">View Campaign Dashboard</a>
    </div>

    <p style="color: #71717a; font-size: 13px; margin-top: 24px;">
        You'll receive real-time updates as your campaign gathers impressions and engagement.
    </p>
</div>
@endsection
