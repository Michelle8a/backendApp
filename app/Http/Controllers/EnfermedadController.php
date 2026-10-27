<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Enfermedad;

class EnfermedadController extends Controller
{
    public function index(){

        return response()->json(Enfermedad::all(), 200);
    }
}
