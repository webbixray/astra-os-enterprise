<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'organization_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the organization that the user belongs to.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the organization memberships for the user.
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationMember::class);
    }

    /**
     * Check if user belongs to a specific organization.
     */
    public function belongsToOrganization(string $organizationId): bool
    {
        return $this->memberships()
            ->where('organization_id', $organizationId)
            ->exists();
    }

    /**
     * Check if user has access to a specific campaign.
     */
    public function hasCampaignAccess(string $campaignId): bool
    {
        $campaign = Campaign::find($campaignId);

        if (!$campaign) {
            return false;
        }

        return $this->belongsToOrganization($campaign->organization_id);
    }

    /**
     * Check if user has access to a specific agent.
     */
    public function hasAgentAccess(string $agentId): bool
    {
        $agent = Agent::find($agentId);

        if (!$agent) {
            return false;
        }

        return $this->belongsToOrganization($agent->organization_id);
    }

    /**
     * Check if user has access to a specific workflow.
     */
    public function hasWorkflowAccess(string $workflowId): bool
    {
        $workflow = Workflow::find($workflowId);

        if (!$workflow) {
            return false;
        }

        return $this->belongsToOrganization($workflow->organization_id);
    }
}
