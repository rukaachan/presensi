<?php

namespace App\Http\Middleware;

use App\Authorization\RoleCode;
use App\Models\Account;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$allowedRoles): Response
    {
        $account = Auth::user();
        $role = $account instanceof Account ? RoleCode::forAccount($account) : null;

        if ($role !== null && in_array($role->value, $allowedRoles, true)) {
            return $next($request);
        }

        abort(403);
    }
}
