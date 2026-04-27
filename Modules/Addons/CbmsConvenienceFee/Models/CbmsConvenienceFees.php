<?php

namespace Modules\Addons\CbmsConvenienceFee\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CbmsConvenienceFees extends Model
{
    const CF_TYPE = "MG_DIS_CHARGE";

    // use HasFactory;

    protected $fillable = [];
    protected $guarded = [];
    
    // protected static function newFactory()
    // {
    //     return \Modules\Addons\CbmsConvenienceFee\Database\factories\CbmsConvenienceFeesFactory::new();
    // }
}
