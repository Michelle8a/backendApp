<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Categoria_Foro;

class Categoria_ForoController extends Controller
{
    public function index(){
        return response()->json(Categoria_Foro::all(), 200);
    }
}
