<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Organization-specific channels
Broadcast::channel('organization.{organizationId}', function ($user, $organizationId) {
    return $user->belongsToOrganization($organizationId);
});

// Campaign-specific channels
Broadcast::channel('campaign.{campaignId}', function ($user, $campaignId) {
    return $user->hasCampaignAccess($campaignId);
});

// Agent-specific channels
Broadcast::channel('agent.{agentId}', function ($user, $agentId) {
    return $user->hasAgentAccess($agentId);
});

// Workflow execution channels
Broadcast::channel('workflow.{workflowId}', function ($user, $workflowId) {
    return $user->hasWorkflowAccess($workflowId);
});

// Social monitoring channels
Broadcast::channel('social.mentions.{organizationId}', function ($user, $organizationId) {
    return $user->belongsToOrganization($organizationId);
});

// User-specific notification channel
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
