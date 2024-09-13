<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BigLottery extends Model
{
    use HasFactory;
    protected $connection='lottery';
    protected $table = 'biglottery';
}
