<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

// Organization-specific channels
Broadcast::channel('organization.{organizationId}', function ($user, $organizationId) {
    return $user->belongsToOrganization((int) $organizationId);
});

// Campaign-specific channel
Broadcast::channel('campaign.{campaignId}', function ($user, $campaignId) {
    return $user->can('viewCampaign', (int) $campaignId);
});

// Agent-specific channel
Broadcast::channel('agent.{agentId}', function ($user, $agentId) {
    return $user->can('viewAgent', (int) $agentId);
});

// User-specific private channel
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Organization team channel
Broadcast::channel('team.{organizationId}', function ($user, $organizationId) {
    return $user->belongsToOrganization((int) $organizationId);
});
