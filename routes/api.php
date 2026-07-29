<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\Campaign\CampaignController;
use App\Http\Controllers\Api\V1\Agent\AgentController;
use App\Http\Controllers\Api\V1\Agent\AgentTaskController;
use App\Http\Controllers\Api\V1\Workflow\WorkflowController;
use App\Http\Controllers\Api\V1\Workflow\WorkflowExecutionController;
use App\Http\Controllers\Api\V1\Workflow\WorkflowTemplateController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

// =========================================================================
// Public Routes (No Auth Required)
// =========================================================================
Route::prefix('v1')->group(function () {

    // Authentication
    Route::post('/auth/register', [AuthController::class, 'register'])
        ->name('api.v1.auth.register');

    Route::post('/auth/login', [AuthController::class, 'login'])
        ->name('api.v1.auth.login');

    // Health Check
    Route::get('/health', function () {
        return response()->json([
            'status' => 'healthy',
            'version' => config('astra-os.general.version', '1.0.0'),
            'timestamp' => now()->toIso8601String(),
        ]);
    })->name('api.v1.health');

    // =========================================================================
    // Protected Routes (Sanctum Auth Required)
    // =========================================================================
    Route::middleware('auth:sanctum')->group(function () {

        // -- Auth Management --
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout'])
                ->name('api.v1.auth.logout');

            Route::get('/me', [AuthController::class, 'me'])
                ->name('api.v1.auth.me');

            Route::post('/refresh', [AuthController::class, 'refresh'])
                ->name('api.v1.auth.refresh');
        });

        // -- User Profile --
        Route::prefix('profile')->group(function () {
            Route::get('/', function (Illuminate\Http\Request $request) {
                return $request->user();
            });
            Route::put('/', function () {
                return response()->json(['message' => 'Update profile - not yet implemented'], 501);
            });
        });

        // =========================================================================
        // Organizations (Top-level CRUD)
        // =========================================================================
        Route::get('/organizations', [OrganizationController::class, 'index'])
            ->name('api.v1.organizations.index');
        Route::post('/organizations', [OrganizationController::class, 'store'])
            ->name('api.v1.organizations.store');
        Route::get('/organizations/{organization}', [OrganizationController::class, 'show'])
            ->name('api.v1.organizations.show');
        Route::put('/organizations/{organization}', [OrganizationController::class, 'update'])
            ->name('api.v1.organizations.update');

        // =========================================================================
        // Organization Scoped Routes
        // =========================================================================
        Route::prefix('organizations/{organization}')->group(function () {

            // -- Dashboard --
            Route::get('/dashboard', function () {
                return response()->json(['message' => 'Dashboard - not yet implemented'], 501);
            })->name('api.v1.organizations.dashboard');

            // -- Members --
            Route::prefix('members')->group(function () {
                Route::get('/', function () {
                    return response()->json(['message' => 'List members - not yet implemented'], 501);
                })->name('api.v1.organizations.members.index');
                Route::post('/', [OrganizationController::class, 'inviteMember'])
                    ->name('api.v1.organizations.members.invite');
                Route::delete('/{member}', [OrganizationController::class, 'removeMember'])
                    ->name('api.v1.organizations.members.remove');
            });

            // -- Campaigns --
            Route::prefix('campaigns')->group(function () {
                Route::get('/', [CampaignController::class, 'index'])
                    ->name('api.v1.organizations.campaigns.index');
                Route::post('/', [CampaignController::class, 'store'])
                    ->name('api.v1.organizations.campaigns.store');
                Route::get('/{campaign}', [CampaignController::class, 'show'])
                    ->name('api.v1.organizations.campaigns.show');
                Route::put('/{campaign}', [CampaignController::class, 'update'])
                    ->name('api.v1.organizations.campaigns.update');
                Route::delete('/{campaign}', [CampaignController::class, 'destroy'])
                    ->name('api.v1.organizations.campaigns.destroy');

                // Campaign Actions
                Route::post('/{campaign}/launch', [CampaignController::class, 'launch'])
                    ->name('api.v1.organizations.campaigns.launch');
                Route::post('/{campaign}/pause', [CampaignController::class, 'pause'])
                    ->name('api.v1.organizations.campaigns.pause');
                Route::post('/{campaign}/archive', [CampaignController::class, 'archive'])
                    ->name('api.v1.organizations.campaigns.archive');
                Route::post('/{campaign}/duplicate', [CampaignController::class, 'duplicate'])
                    ->name('api.v1.organizations.campaigns.duplicate');

                // -- Campaign Creatives --
                Route::get('/{campaign}/creatives', function () {
                    return response()->json(['message' => 'List creatives - not yet implemented'], 501);
                });
                Route::post('/{campaign}/creatives', function () {
                    return response()->json(['message' => 'Create creative - not yet implemented'], 501);
                });

                // -- Campaign Insights --
                Route::get('/{campaign}/insights', function () {
                    return response()->json(['message' => 'Get insights - not yet implemented'], 501);
                });

                // -- Campaign Analytics --
                Route::get('/{campaign}/analytics', function () {
                    return response()->json(['message' => 'Get analytics - not yet implemented'], 501);
                });
            });

            // -- Agents --
            Route::prefix('agents')->group(function () {
                Route::get('/', [AgentController::class, 'index'])
                    ->name('api.v1.organizations.agents.index');
                Route::post('/', [AgentController::class, 'store'])
                    ->name('api.v1.organizations.agents.store');
                Route::get('/{agent}', [AgentController::class, 'show'])
                    ->name('api.v1.organizations.agents.show');
                Route::put('/{agent}', [AgentController::class, 'update'])
                    ->name('api.v1.organizations.agents.update');
                Route::delete('/{agent}', [AgentController::class, 'destroy'])
                    ->name('api.v1.organizations.agents.destroy');

                // -- Agent Tasks --
                Route::get('/{agent}/tasks', [AgentTaskController::class, 'index'])
                    ->name('api.v1.organizations.agents.tasks.index');
                Route::post('/{agent}/tasks', [AgentController::class, 'assignTask'])
                    ->name('api.v1.organizations.agents.tasks.store');
                Route::get('/{agent}/tasks/{task}', [AgentTaskController::class, 'show'])
                    ->name('api.v1.organizations.agents.tasks.show');
                Route::post('/{agent}/tasks/{task}/retry', [AgentTaskController::class, 'retry'])
                    ->name('api.v1.organizations.agents.tasks.retry');
                Route::delete('/{agent}/tasks/{task}', [AgentTaskController::class, 'cancel'])
                    ->name('api.v1.organizations.agents.tasks.cancel');

                // -- Agent Memory --
                Route::get('/{agent}/memory', [AgentController::class, 'getMemory'])
                    ->name('api.v1.organizations.agents.memory');
                Route::delete('/{agent}/memory', [AgentController::class, 'clearMemory'])
                    ->name('api.v1.organizations.agents.memory.clear');
            });

            // -- Workflows --
            Route::prefix('workflows')->group(function () {
                Route::get('/', [WorkflowController::class, 'index'])
                    ->name('api.v1.organizations.workflows.index');
                Route::post('/', [WorkflowController::class, 'store'])
                    ->name('api.v1.organizations.workflows.store');
                Route::get('/{workflow}', [WorkflowController::class, 'show'])
                    ->name('api.v1.organizations.workflows.show');
                Route::put('/{workflow}', [WorkflowController::class, 'update'])
                    ->name('api.v1.organizations.workflows.update');
                Route::delete('/{workflow}', [WorkflowController::class, 'destroy'])
                    ->name('api.v1.organizations.workflows.destroy');

                // -- Workflow Actions --
                Route::post('/{workflow}/activate', [WorkflowController::class, 'activate'])
                    ->name('api.v1.organizations.workflows.activate');
                Route::post('/{workflow}/deactivate', [WorkflowController::class, 'deactivate'])
                    ->name('api.v1.organizations.workflows.deactivate');
                Route::post('/{workflow}/duplicate', [WorkflowController::class, 'duplicate'])
                    ->name('api.v1.organizations.workflows.duplicate');

                // -- Workflow Executions --
                Route::post('/{workflow}/execute', [WorkflowExecutionController::class, 'execute'])
                    ->name('api.v1.organizations.workflows.execute');
                Route::get('/{workflow}/executions', [WorkflowExecutionController::class, 'index'])
                    ->name('api.v1.organizations.workflows.executions.index');
                Route::get('/{workflow}/executions/{execution}', [WorkflowExecutionController::class, 'show'])
                    ->name('api.v1.organizations.workflows.executions.show');
                Route::post('/{workflow}/executions/{execution}/cancel', [WorkflowExecutionController::class, 'cancel'])
                    ->name('api.v1.organizations.workflows.executions.cancel');
            });

            // -- Social --
            Route::prefix('social')->group(function () {
                Route::prefix('accounts')->group(function () {
                    Route::get('/', function () {
                        return response()->json(['message' => 'List accounts - not yet implemented'], 501);
                    });
                    Route::post('/', function () {
                        return response()->json(['message' => 'Connect account - not yet implemented'], 501);
                    });
                    Route::delete('/{account}', function () {
                        return response()->json(['message' => 'Disconnect account - not yet implemented'], 501);
                    });
                });

                Route::prefix('posts')->group(function () {
                    Route::get('/', function () {
                        return response()->json(['message' => 'List posts - not yet implemented'], 501);
                    });
                    Route::post('/', function () {
                        return response()->json(['message' => 'Create post - not yet implemented'], 501);
                    });
                    Route::put('/{post}', function () {
                        return response()->json(['message' => 'Update post - not yet implemented'], 501);
                    });
                });

                Route::get('/mentions', function () {
                    return response()->json(['message' => 'List mentions - not yet implemented'], 501);
                });
            });

            // -- Reports --
            Route::prefix('reports')->group(function () {
                Route::get('/', function () {
                    return response()->json(['message' => 'List reports - not yet implemented'], 501);
                });
                Route::post('/', function () {
                    return response()->json(['message' => 'Create report - not yet implemented'], 501);
                });
                Route::get('/{report}', function () {
                    return response()->json(['message' => 'Get report - not yet implemented'], 501);
                });
                Route::post('/{report}/generate', function () {
                    return response()->json(['message' => 'Generate report - not yet implemented'], 501);
                });
            });

            // -- Settings --
            Route::prefix('settings')->group(function () {
                Route::get('/', function () {
                    return response()->json(['message' => 'Get settings - not yet implemented'], 501);
                });
                Route::put('/', function () {
                    return response()->json(['message' => 'Update settings - not yet implemented'], 501);
                });
            });

            // -- Audit Logs --
            Route::get('/audit-logs', function () {
                return response()->json(['message' => 'List audit logs - not yet implemented'], 501);
            });
        });

        // -- Workflow Templates (Global) --
        Route::prefix('workflow-templates')->group(function () {
            Route::get('/', [WorkflowTemplateController::class, 'index']);
            Route::get('/{template}', [WorkflowTemplateController::class, 'show']);
            Route::post('/{template}/apply', [WorkflowTemplateController::class, 'apply']);
        });
    });
});
