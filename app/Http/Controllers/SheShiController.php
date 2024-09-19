<?php

namespace App\Http\Controllers;

use App\KeyWords\QiGua;
use App\KeyWords\ShengSheQian;

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
