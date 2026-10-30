<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Centro_Salud;

class Centro_SaludController extends Controller
{
    public function index(){
        return response()->json(Centro_Salud::all(), 200);
    }
}
