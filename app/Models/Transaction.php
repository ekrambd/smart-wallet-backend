<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Wallet;

class Transaction extends Model
{
    use HasFactory;

    protected $appends = ['transaction_status'];

    public function wallet()
    {
    	return $this->belongsTo(Wallet::class);
    }

    public function contract()
    {
    	return $this->belongsTo(Contract::class);
    }

    public function getTransactionStatusAttribute()
    {
        $wallet = Wallet::find($this->wallet_id);
        if($wallet->wallet_address == $this->sender_address){
        	return "send";
        }else{
        	return "recieve";
        } 
    } 
}
