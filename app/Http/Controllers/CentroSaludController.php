<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CentroSalud;

class CentroSaludController extends Controller
{
    public function index(){
        return response()->json(CentroSalud::all(), 200);
    }
}
