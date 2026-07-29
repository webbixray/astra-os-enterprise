<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class OrganizationAccess
{
    /**
     * Ensure the authenticated user belongs to the organization referenced in the URL.
     *
     * Looks for an {organization} or {organization_id} route parameter and verifies
     * that the current user is the owner or a member of that organization.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $organizationId = $request->route('organization')
            ?? $request->route('organization_id')
            ?? $request->route('organizationId');

        if ($organizationId === null) {
            return $next($request);
        }

        $organizationId = (int) $organizationId;

        // Check if user owns the organization
        if ($user->id === $organizationId) {
            return $next($request);
        }

        // Check via pivot table
        $belongsToOrganization = $user->organizations()
            ->where('organization_id', $organizationId)
            ->exists();

        if (!$belongsToOrganization) {
            // Also check if user is the direct owner via organizations table
            $ownsOrganization = \App\Infrastructure\Persistence\Models\Organization::where('id', $organizationId)
                ->where('owner_id', $user->id)
                ->exists();

            if (!$ownsOrganization) {
                return response()->json([
                    'message' => 'You do not have access to this organization.',
                ], 403);
            }
        }

        return $next($request);
    }
}
