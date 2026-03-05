<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsApproved
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        // If user is authenticated but not approved, log them out and redirect
        // NOTE: Skip approval check for admin users - they bypass approval workflow
        if ($user && 
            $user->approval_status !== \App\Models\User::APPROVAL_APPROVED && 
            $user->role !== 'admin') {
            
            $approvalStatus = $user->approval_status;
            
            auth()->guard()->logout();
            
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $errorMessage = match($approvalStatus) {
                \App\Models\User::APPROVAL_PENDING => 'Your account is awaiting admin approval.',
                \App\Models\User::APPROVAL_REJECTED => 'Your registration was rejected.',
                default => 'Your account is not approved for system access.',
            };

            return redirect()->route('login')
                ->with('error', $errorMessage);
        }

        return $next($request);
    }
}
