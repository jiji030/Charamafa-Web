<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class PurokController extends Controller
{
    public function index()
    {
        $puroks = DB::table('puroks')->orderBy('purok', 'asc')->get();
        return response()->json($puroks);
    }
}