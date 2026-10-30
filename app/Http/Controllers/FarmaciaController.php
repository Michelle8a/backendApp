<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Farmacia;

class FarmaciaController extends Controller
{
    public function index(){
        return response()->json(Farmacia::all(), 200);
    }
}
