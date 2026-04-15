<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SsoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function verify(Request $request): mixed
    {
        $token = $request->query('ticket');

        if (! $token) {
            Log::info('Tiket tidak ditemukan.');

            return redirect(config('services.sso.portal_url'))->with('error', 'Tiket tidak ditemukan.');
        }

        $ticket = DB::table('portal_application.sso_tickets')
            ->where('ticket', $token)
            ->where('expired_at', '>', now())
            ->first();

        if ($ticket) {
            Auth::loginUsingId($ticket->user_id);
            DB::table('portal_application.sso_tickets')->where('ticket', $token)->delete();
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        Log::info('Tiket tidak valid atau kadaluwarsa.');

        return redirect(config('services.sso.portal_url'))->with('error', 'Tiket tidak valid atau kadaluwarsa.');
    }
}
