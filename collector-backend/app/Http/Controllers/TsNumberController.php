<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class TsNumberController extends Controller
{
    public function index()
    {
        $tsNumbers = DB::table('ts_numbers')->orderBy('ts_no', 'asc')->get();
        return response()->json($tsNumbers);
    }
}