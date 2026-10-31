<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CategoriaForo;

class CategoriaForoController extends Controller
{
    public function index(){
        return response()->json(CategoriaForo::all(), 200);
    }
}
