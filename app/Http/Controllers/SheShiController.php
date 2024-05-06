<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\QiGuaService;
use App\Services\ShengSheQianService;

class SheShiController extends Controller
{
    //
    public function index()
    {
        $qiGua = new QiGuaService();
        dump($qiGua->qiGua());
    }

    public function shengShe()
    {
        $qiGua = new ShengSheQianService();
        dump($qiGua->getQian());
    }
}
