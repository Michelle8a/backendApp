<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Alergia;

class AlergiaController extends Controller
{
    public function index(){

        return response()->json(Alergia::all(), 200);
    }
}
