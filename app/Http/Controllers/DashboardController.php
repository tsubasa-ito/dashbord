<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index() {
        $cards = [

        ];

        return response()->json([ 'cards' => $cards ]);
    }
}
