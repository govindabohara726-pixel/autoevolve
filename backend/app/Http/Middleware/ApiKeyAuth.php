<?php
namespace App\Http\Middleware;
use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class ApiKeyAuth {
    public function handle(Request $request, Closure $next): Response {
        $plain=$request->bearerToken(); if(!$plain)return response()->json(['error'=>'Missing API key'],401);
        $key=ApiKey::where('token_hash',hash('sha256',$plain))->whereNull('revoked_at')->first(); if(!$key)return response()->json(['error'=>'Invalid API key'],401);
        $key->forceFill(['last_used_at'=>now()])->save(); $request->attributes->set('api_key',$key); $request->attributes->set('workspace',$key->workspace()->first()); return $next($request);
    }
}
