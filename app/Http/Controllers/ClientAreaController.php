<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ClientAreaController extends Controller
{
    public function __invoke(Request $request): View
    {
        $client = $request->user()->client;

        abort_if($client === null, 404);

        return view('client.qr', ['client' => $client]);
    }
}
