<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds composite indexes, covering indexes, and partial indexes
     * on frequently queried columns for query performance optimization.
     */
    public function up(): void
    {
        // ──────────────────────────────────────────────
        // 1. CAMPAIGNS — composite indexes
        // ──────────────────────────────────────────────
        Schema::table('campaigns', function (Blueprint $table) {
            // Drop existing single-column indexes that are superseded
            // by the composite indexes below.
            $table->dropIndex(['status']);

            // Composite: organization + status + created_at
            // Used for: listing campaigns for an org filtered by status, sorted by date
            $table->index(['organization_id', 'status', 'created_at'], 'idx_campaigns_org_status_created');

            // Composite: organization + status + objective
            // Replaces (organization_id, status) + (objective) — used for filtered dashboards
            $table->index(['organization_id', 'status', 'objective'], 'idx_campaigns_org_status_objective');
        });

        // ──────────────────────────────────────────────
        // 2. AGENTS — composite indexes
        // ──────────────────────────────────────────────
        Schema::table('agents', function (Blueprint $table) {
            // Drop existing single-column indexes superseded below
            $table->dropIndex(['is_active']);

            // Composite: organization + is_active + role
            // Used for: listing active agents by role within an org
            $table->index(['organization_id', 'is_active', 'role'], 'idx_agents_org_active_role');
        });

        // ──────────────────────────────────────────────
        // 3. WORKFLOWS — composite indexes
        // ──────────────────────────────────────────────
        Schema::table('workflows', function (Blueprint $table) {
            // Composite: organization + status + version
            // Used for: listing workflows for an org filtered by status
            $table->index(['organization_id', 'status', 'version'], 'idx_workflows_org_status_version');
        });

        // ──────────────────────────────────────────────
        // 4. SOCIAL POSTS — add organization_id + composite indexes
        // ──────────────────────────────────────────────
        Schema::table('social_posts', function (Blueprint $table) {
            // Add organization_id for direct org-scoped queries
            if (! Schema::hasColumn('social_posts', 'organization_id')) {
                $table->foreignUuid('organization_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('organizations')
                    ->nullOnDelete();
            }

            // Drop existing single-column index on status
            $table->dropIndex(['status']);

            // Composite: organization + status + scheduled_at
            // Used for: scheduled post listing by org and status
            $table->index(
                ['organization_id', 'status', 'scheduled_at'],
                'idx_social_posts_org_status_scheduled',
            );

            // Composite: account_id + status + scheduled_at
            // Used for: per-account post listing sorted by schedule
            $table->index(
                ['account_id', 'status', 'scheduled_at'],
                'idx_social_posts_account_status_scheduled',
            );
        });

        // ──────────────────────────────────────────────
        // 5. SOCIAL MENTIONS — add is_read + composite indexes
        // ──────────────────────────────────────────────
        Schema::table('social_mentions', function (Blueprint $table) {
            // Add is_read flag for efficient unread-mention filtering
            if (! Schema::hasColumn('social_mentions', 'is_read')) {
                $table->boolean('is_read')->default(false)->after('status');
            }

            // Drop existing indexes that are superseded
            $table->dropIndex(['status']);

            // Composite: organization_id + is_read + created_at
            // Used for: unread mention listing per org
            $table->index(
                ['organization_id', 'is_read', 'created_at'],
                'idx_mentions_org_read_created',
            );

            // Composite: organization_id + status + created_at
            // Used for: mention filtering by status within an org
            $table->index(
                ['organization_id', 'status', 'created_at'],
                'idx_mentions_org_status_created',
            );
        });

        // ──────────────────────────────────────────────
        // 6. CAMPAIGN ANALYTICS — covering indexes
        // ──────────────────────────────────────────────
        Schema::table('campaign_analytics', function (Blueprint $table) {
            // The table already has a UNIQUE index on (campaign_id, date, source).
            // Add a covering index that includes the most-queried metric columns
            // so common analytic queries can be satisfied from the index alone.
            $table->index(
                ['campaign_id', 'date', 'source', 'impressions', 'clicks', 'conversions', 'spend', 'revenue'],
                'idx_analytics_covering',
            );
        });

        // ──────────────────────────────────────────────
        // 7. AGENT TASKS — composite indexes
        // ──────────────────────────────────────────────
        Schema::table('agent_tasks', function (Blueprint $table) {
            // Drop existing single-column index on status
            $table->dropIndex(['status']);

            // Composite: agent_id + status + created_at
            // Used for: per-agent task history filtered by status
            $table->index(
                ['agent_id', 'status', 'created_at'],
                'idx_agent_tasks_agent_status_created',
            );

            // Composite: agent_id + type + status
            // Used for: filtering tasks by type and status
            $table->index(
                ['agent_id', 'type', 'status'],
                'idx_agent_tasks_agent_type_status',
            );
        });

        // ──────────────────────────────────────────────
        // 8. ORGANIZATIONS — owner_id index
        // ──────────────────────────────────────────────
        Schema::table('organizations', function (Blueprint $table) {
            $table->index(['owner_id'], 'idx_organizations_owner');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropIndex('idx_campaigns_org_status_created');
            $table->dropIndex('idx_campaigns_org_status_objective');
            $table->index('status');
        });

        Schema::table('agents', function (Blueprint $table) {
            $table->dropIndex('idx_agents_org_active_role');
            $table->index('is_active');
        });

        Schema::table('workflows', function (Blueprint $table) {
            $table->dropIndex('idx_workflows_org_status_version');
        });

        Schema::table('social_posts', function (Blueprint $table) {
            $table->dropIndex('idx_social_posts_org_status_scheduled');
            $table->dropIndex('idx_social_posts_account_status_scheduled');
            $table->index('status');

            // Optionally drop the organization_id column
            if (Schema::hasColumn('social_posts', 'organization_id')) {
                $table->dropForeign(['organization_id']);
                $table->dropColumn('organization_id');
            }
        });

        Schema::table('social_mentions', function (Blueprint $table) {
            $table->dropIndex('idx_mentions_org_read_created');
            $table->dropIndex('idx_mentions_org_status_created');
            $table->index('status');

            if (Schema::hasColumn('social_mentions', 'is_read')) {
                $table->dropColumn('is_read');
            }
        });

        Schema::table('campaign_analytics', function (Blueprint $table) {
            $table->dropIndex('idx_analytics_covering');
        });

        Schema::table('agent_tasks', function (Blueprint $table) {
            $table->dropIndex('idx_agent_tasks_agent_status_created');
            $table->dropIndex('idx_agent_tasks_agent_type_status');
            $table->index('status');
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropIndex('idx_organizations_owner');
        });
    }
};
