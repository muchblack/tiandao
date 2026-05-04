<?php

namespace App\Http\Controllers;

use App\KeyWords\QiGua;
use App\KeyWords\ShengSheQian;

class SheShiController extends Controller
{
    public function index()
    {
        return response()->json((new QiGua())->replyCommand());
    }

    public function shengShe()
    {
        return response()->json((new ShengSheQian())->replyCommand());
    }
}
