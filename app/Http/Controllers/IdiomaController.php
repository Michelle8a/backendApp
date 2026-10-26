<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Idioma;

class IdiomaController extends Controller
{
    public function index(){

        return response()->json(Idioma::all(), 200);
    }
}
