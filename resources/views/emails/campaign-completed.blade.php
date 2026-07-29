@extends('layouts.email')

@section('content')
<div class="content">
    <h2 style="margin-top: 0;">Campaign Completed ✅</h2>
    <p>Your campaign <strong>{{ $campaignName }}</strong> has completed its run. Here's your performance summary:</p>

    <table style="width: 100%; margin: 24px 0;">
        <tr class="detail">
            <td class="detail-label">Campaign</td>
            <td class="detail-value">{{ $campaignName }}</td>
        </tr>
        <tr class="detail">
            <td class="detail-label">Status</td>
            <td class="detail-value"><span class="badge badge-info">Completed</span></td>
        </tr>
        @if(isset($impressions))
        <tr class="detail">
            <td class="detail-label">Total Impressions</td>
            <td class="detail-value">{{ number_format($impressions) }}</td>
        </tr>
        @endif
        @if(isset($clicks))
        <tr class="detail">
            <td class="detail-label">Total Clicks</td>
            <td class="detail-value">{{ number_format($clicks) }}</td>
        </tr>
        @endif
        @if(isset($spend))
        <tr class="detail">
            <td class="detail-label">Total Spend</td>
            <td class="detail-value">${{ number_format($spend, 2) }}</td>
        </tr>
        @endif
        @if(isset($ctr))
        <tr class="detail">
            <td class="detail-label">CTR</td>
            <td class="detail-value">{{ $ctr }}%</td>
        </tr>
        @endif
    </table>

    <div style="text-align: center; margin-top: 32px;">
        <a href="{{ $dashboardUrl ?? '#' }}" class="button">View Full Analytics</a>
    </div>

    @if(isset($newCampaignUrl))
    <p style="text-align: center; margin-top: 16px;">
        <a href="{{ $newCampaignUrl }}" style="color: #6366f1; font-size: 14px;">
            Create a new campaign based on this one →
        </a>
    </p>
    @endif
</div>
@endsection
