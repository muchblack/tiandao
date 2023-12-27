<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\QiGuaService;

class SheShiController extends Controller
{
    //
    public function index()
    {
        $qiGua = new QiGuaService();
        dump($qiGua->qiGua());
    }
}
