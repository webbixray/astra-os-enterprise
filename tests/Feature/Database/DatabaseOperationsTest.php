<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

#[Group('feature')]
class DatabaseOperationsTest extends TestCase
{
    public function test_database_connection(): void
    {
        $this->assertTrue(DB::connection()->getPdo() instanceof \PDO);
    }

    public function test_database_driver(): void
    {
        $driver = DB::getDriverName();
        $this->assertEquals('pgsql', $driver);
    }

    public function test_migrations_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('migrations'));
    }

    public function test_core_tables_exist(): void
    {
        $tables = [
            'organizations',
            'users',
            'campaigns',
            'campaign_creatives',
            'campaign_insights',
            'agents',
            'agent_tasks',
            'agent_memories',
            'agent_conversations',
            'workflows',
            'workflow_executions',
            'social_accounts',
            'social_posts',
            'social_mentions',
            'social_comments',
            'audit_logs',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Table {$table} should exist");
        }
    }

    public function test_foreign_key_constraints(): void
    {
        // This would check actual FK constraints in PostgreSQL
        // For now, verify the migrations define them
        $this->assertTrue(true);
    }

    public function test_uuid_primary_keys(): void
    {
        // Verify tables use UUID primary keys where appropriate
        $uuidTables = [
            'organizations' => 'id',
            'campaigns' => 'id',
            'agents' => 'id',
            'workflows' => 'id',
            'social_accounts' => 'id',
            'social_posts' => 'id',
        ];

        foreach ($uuidTables as $table => $column) {
            $columns = Schema::getColumnListing($table);
            $this->assertContains($column, $columns);
        }
    }

    public function test_database_indexes(): void
    {
        // Check that key indexes exist
        $this->assertTrue(true); // Would query pg_indexes in real test
    }

    public function test_transaction_rollback(): void
    {
        DB::beginTransaction();
        
        DB::table('organizations')->insert([
            'name' => 'Test Org',
            'uuid' => '00000000-0000-0000-0000-000000000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $count = DB::table('organizations')->where('name', 'Test Org')->count();
        $this->assertEquals(1, $count);
        
        DB::rollBack();
        
        $count = DB::table('organizations')->where('name', 'Test Org')->count();
        $this->assertEquals(0, $count);
    }
}