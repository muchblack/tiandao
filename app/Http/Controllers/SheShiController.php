<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\QiGua;
use App\Services\ShengSheQian;

class SheShiController extends Controller
{
    //
    public function index()
    {
        $qiGua = new QiGua();
        dump($qiGua->replyCommand());
    }

    public function shengShe()
    {
        $qiGua = new ShengSheQian();
        dump($qiGua->replyCommand());
    }
}
