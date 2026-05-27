<?php

namespace App\Http\Middleware;

use App\Models\Ujian;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUjianAccess
{
    /**
     * Guru hanya boleh membuka ujian miliknya.
     * Role lain tetap mengikuti batasan role middleware pada route.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ujian = $request->route('ujian');

        if (!$ujian instanceof Ujian) {
            return $next($request);
        }

        $user = $request->user();

        if ($user?->hasRole('Guru')) {
            if (!$user->guru || (int) $ujian->guru_id !== (int) $user->guru->id) {
                abort(403, 'Anda tidak memiliki akses ke ujian ini.');
            }
        }

        return $next($request);
    }
}
