<?php

 use Web3\Web3;

function user(){
   $user = auth()->user();
   return $user;
}

function getBnbBalance($wallet_address)
{
    $web3 = new Web3('https://bsc-dataseed.binance.org/');

    $address = $wallet_address;

    $web3->eth->getBalance($address, function ($err, $balance) {

        if ($err !== null) {
            return response()->json([
                'error' => $err->getMessage()
            ]);
        }

        $wei = hexdec($balance);

        $bnb = $wei / pow(10, 18);

        return $bnb;
    });
}