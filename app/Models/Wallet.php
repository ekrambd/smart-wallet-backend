<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{   
    use HasFactory;

    // protected $appends = ['contracts'];

    // public function getContractsAttribute()
    // {
    //     $contracts = \App\Models\Contract::paginate(5);
    //     return $contracts;
    // }

    public function transactions()
    {
    	return $this->hasMany(Transaction::class);
    }

}
