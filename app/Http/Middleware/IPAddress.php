<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class IPAddress
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the IP address of the incoming request
        $originalIp = $request->ip();

        // Define wildcard for last octet
        $newLastDigit = "*";

        // Explode the original IP address into an array of octets
        $octets = explode('.', $originalIp);

        // Replace the last octet with the wildcard
        $octets[3] = $newLastDigit;

        // Join the modified octets back into an IP address with the wildcard
        $modifiedIp = implode('.', $octets);

        // Fetch the list of active IP addresses from the database using the query builder
        $ip_addresses_db = DB::table('ip_addresses')
            ->where('status', 1) // Ensure 'status' is '1' (active)
            ->pluck('ip_address')
            ->toArray();

        // Modify each IP address from the database similarly by changing the last octet to '*'
        $modifiedIp_db = array_map(function ($ip) use ($newLastDigit) {
            $octets = explode('.', $ip);
            $octets[3] = $newLastDigit; // Set last octet as wildcard
            return implode('.', $octets); // Recreate the IP address
        }, $ip_addresses_db);

        // Check if the modified IP exists in the database (after modification)
        if (in_array($modifiedIp, $modifiedIp_db)) {
            return $next($request); // Allow request if IP matches
        }

        // If IP doesn't match, redirect to a restricted page
        return redirect()->route('login')->withErrors(['ip' => 'Your IP address is not registered.']);
    }
}
