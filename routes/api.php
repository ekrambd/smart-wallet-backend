<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['throttle:60,1'])->group(function () {
	Route::post('add-device-id', [ApiController::class, 'addDeviceID']);
	Route::post('device-login', [ApiController::class, 'deviceLogin']);
	Route::middleware('auth:sanctum')->group( function () {
		Route::post('device-logout', [ApiController::class, 'deviceLogout']);
		Route::post('generate-wallet-address', [ApiController::class, 'generateWalletAddress']);
		Route::post('import-wallet-address', [ApiController::class, 'importWalletAddress']);
		
		Route::post('my-contracts', [ApiController::class, 'myContracts']);
		Route::post('contract-details', [ApiController::class, 'contractDetails']);
		Route::post('save-contract', [ApiController::class, 'saveContract']);
		Route::post('max-bnb-amount', [ApiController::class, 'maxBNBAmount']);
		Route::post('bnb-transfer', [ApiController::class, 'bnbTransfer']);
		Route::post('token-transfer', [ApiController::class, 'tokenTransfer']);
		Route::post('transaction-histories', [ApiController::class, 'transactionHistories']);
		Route::get('/transaction-details/{id}', [ApiController::class, 'transactionDetails']);
		Route::post('token-exchange-price', [ApiController::class, 'tokenExchangePrice']);
		Route::post('token-to-token', [ApiController::class, 'tokenToToken']);
		Route::get('/contract-lists', [ApiController::class, 'contractLists']);
		Route::post('bnb-to-token', [ApiController::class, 'bnbToToken']);
		Route::post('token-to-bnb', [ApiController::class, 'tokenToBnb']);
	});
	
});


Route::post('wallet-details', [ApiController::class, 'walletDetails']);

Route::get('/test-node', [ApiController::class, 'testNode']);//