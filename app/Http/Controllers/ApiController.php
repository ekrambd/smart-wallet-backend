<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Crypt;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\Contract;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Models\Transaction;
use App\Models\Swap;

class ApiController extends Controller
{
    public function addDeviceID(Request $request)
    {
    	try
    	{
    		$validator = Validator::make($request->all(), [
                'app_id' => 'required|string|unique:users',
                'name'   => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false, 
                    'message' => 'Please fill all requirement fields', 
                    'data' => $validator->errors()
                ], 422);  
            }

            $user = new User();
            $user->app_id = $request->app_id;
            $user->password = bcrypt('123456');
            $user->save();

            return response()->json(['status'=>true, 'app_id'=>$user->app_id, 'message'=>"Successfully add the device info"]);

    	}catch(Exception $e){
            return response()->json(['status'=>false, 'code'=>$e->getCode(), 'message'=>$e->getMessage()],500);
        }
    }

    public function deviceLogin(Request $request)
    {
    	try
    	{
    		$validator = Validator::make($request->all(), [
                'app_id' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false, 
                    'message' => 'Please fill all requirement fields', 
                    'data' => $validator->errors()
                ], 422);  
            }

            $user = User::where('app_id',$request->app_id)->first();

            if($user){
            	$token = $user->createToken('MyApp')->plainTextToken;
            	return response()->json(['status'=>true, 'message'=>'App ID Found', 'app_id'=>$user->app_id, 'token'=>$token]);
            }

            $user = new User();
            $user->app_id = $request->app_id;
            $user->password = bcrypt('123456');
            $user->save();
            $token = $user->createToken('MyApp')->plainTextToken;

            return response()->json(['status'=>true, 'message'=>'App ID Found', 'app_id'=>$user->app_id, 'token'=>$token]);

            //return response()->json(['status'=>false, 'message'=>'Invalid Info', 'app_id'=>"", 'token'=>""],401);


    	}catch(Exception $e){
            return response()->json(['status'=>false, 'code'=>$e->getCode(), 'message'=>$e->getMessage()],500);
        }
    }

    public function deviceLogout(Request $request)
    {
    	try
    	{
    		auth()->user()->tokens()->delete();
            return response()->json(['status'=>true, 'message'=>'Successfully Logged Out']);
    	}catch(Exception $e){
            return response()->json(['status'=>false, 'code'=>$e->getCode(), 'message'=>$e->getMessage()],500);
        }
    }

   //  public function generateWalletAddress(Request $request)
   //  {
   //  	try
   //  	{
   //  		$user = user();
   //  		$scriptPath = public_path('web3/erc20WalletGenerate.js');
   //          $command = "/usr/bin/node " . escapeshellarg($scriptPath);
			// $output = shell_exec($command);

			// $wallet = json_decode($output, true);


			// if(!$wallet){
			// 	return response()->json(['status'=>false, 'message'=>'Something Went Wrong', 'wallet'=>new \stdClass()],400);
			// }

			// $wallet_address = $wallet['address'];

			// $private_key = Crypt::encryptString($wallet['privateKey']);

			// $phrase = Crypt::encryptString(json_encode($wallet['mnemonic']));

			// $wallet = new Wallet();
			// $wallet->app_id = $user->app_id;
			// $wallet->wallet_address = $wallet_address;
			// $wallet->private_key = $private_key;
			// $wallet->mnemonic = $phrase;
			// $wallet->type = 'generated';
			// $wallet->save();

			// return response()->json(['status'=>true, 'wallet_id'=>intval($wallet->id), 'wallet_address'=>$wallet->wallet_address, 'message'=>'Successfully a wallet has been generated']);

   //  	}catch(Exception $e){
   //          return response()->json(['status'=>false, 'code'=>$e->getCode(), 'message'=>$e->getMessage()],500);
   //      }
   //  }


    public function generateWalletAddress(Request $request)
    {
        try {

            $user = user();

            /*
            |--------------------------------------------------------------------------
            | Generate Wallet From /usr/bin/node Script
            |--------------------------------------------------------------------------
            */

            $scriptPath = public_path('web3/erc20WalletGenerate.js');

            $command = "/usr/bin/node " . escapeshellarg($scriptPath);

            $output = shell_exec($command);

            $walletData = json_decode($output, true);

            if (!$walletData) {

                return response()->json([
                    'status'  => false,
                    'message' => 'Something Went Wrong',
                    'wallet'  => new \stdClass()
                ], 400);
            }

            /*
            |--------------------------------------------------------------------------
            | Wallet Data
            |--------------------------------------------------------------------------
            */

            $wallet_address = $walletData['address'];

            $private_key = Crypt::encryptString($walletData['privateKey']);

            // mnemonic encrypted
            $phrase = Crypt::encryptString(
                json_encode($walletData['mnemonic'])
            );

            $mnemonic = json_encode($walletData['mnemonic']);

            //return $mnemonic;
            /*
            |--------------------------------------------------------------------------
            | Generate QR Code
            |--------------------------------------------------------------------------
            | QR scan korle encrypted mnemonic value pawa jabe
            */

            $qrFileName = 'wallet_qr_' . time() . '.svg';

            $qrPath = public_path('uploads/qrcodes/' . $qrFileName);

            // folder create if not exists
            if (!file_exists(public_path('uploads/qrcodes'))) {

                mkdir(public_path('uploads/qrcodes'), 0777, true);
            }

            QrCode::format('svg')
                ->size(300)
                ->generate($mnemonic, $qrPath);

            /*
            |--------------------------------------------------------------------------
            | Save Wallet
            |--------------------------------------------------------------------------
            */

            $wallet = new Wallet();

            $wallet->app_id = $user->app_id;

            $wallet->wallet_address = $wallet_address;

            $wallet->private_key = $private_key;

            $wallet->mnemonic = $phrase;

            $wallet->qrcode = 'uploads/qrcodes/' . $qrFileName;

            $wallet->type = 'imported';

            $wallet->save(); 

            /*
            |--------------------------------------------------------------------------
            | Response
            |--------------------------------------------------------------------------
            */

            return response()->json([

                'status' => true,

                'message' => 'Successfully a wallet has been generated',

                'wallet_id' => intval($wallet->id),

                'wallet_address' => $wallet->wallet_address,

                'qrcode' => url('/')."/".$wallet->qrcode,

                'mnemonic' => $walletData['mnemonic'],


            ]);

        } catch (Exception $e) {

            return response()->json([

                'status'  => false,

                'code'    => $e->getCode(),

                'message' => $e->getMessage()

            ], 500);
        }
    }

    public function walletDetails(Request $request)
    {
    	try
    	{  

    		$validator = Validator::make($request->all(), [
                'wallet_id' => 'required|integer|exists:wallets,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false, 
                    'message' => 'Please fill all requirement fields', 
                    'data' => $validator->errors()
                ], 422);  
            }

            //$wallet = Wallet::where('id',$request->wallet_id)->where('app_id',user()->app_id)->first();

            $wallet = Wallet::where('id',$request->wallet_id)->first();

            if(!$wallet){
            	return response()->json(['status'=>false, 'message'=>'No data found', 'data'=>new \stdClass()],404);
            }

    	    $data = array(
    	    	'id' => intval($wallet->id),
    	    	'wallet_address' => $wallet->wallet_address,
                'qrcode'         => url('/')."/".$wallet->qrcode,
    	    	'private_key'    => Crypt::decryptString($wallet->private_key),
    	    	'mnemonic'       => json_decode(Crypt::decryptString($wallet->mnemonic),true),
    	    );
    	    return response()->json(['status'=>true, 'message'=>'Data found', 'data'=>$data]);
    	}catch(Exception $e){
            return response()->json(['status'=>false, 'code'=>$e->getCode(), 'message'=>$e->getMessage()],500);
        }
    }

    // public function importWalletAddress(Request $request)
    // {
    //     try
    //     {
    //         $validator = Validator::make($request->all(), [
    //             'wallet_id' => 'required|integer|exists:wallets,id',
    //         ]);

    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'status' => false, 
    //                 'message' => 'Please fill all requirement fields', 
    //                 'data' => $validator->errors()
    //             ], 422);  
    //         }
    //     }catch(Exception $e){
    //         return response()->json(['status'=>false, 'code'=>$e->getCode(), 'message'=>$e->getMessage()],500);
    //     }
    // }

    public function importWalletAddress(Request $request)
    {
        try {

            $request->validate([
                'type' => 'required|in:mnemonic,private_key'
            ]);

            $scriptPath = public_path('web3/importWallet.js');

            $payload = [
                'type' => $request->type,
                'data' => $request->type === 'mnemonic'
                    ? $request->mnemonic
                    : $request->private_key
            ];

            if ($request->type === 'mnemonic') {

                if (!is_array($request->mnemonic)) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Invalid mnemonic format'
                    ], 422);
                }

                usort($payload['data'], fn($a, $b) => $a['serial'] <=> $b['serial']);
            }

            /*
            |--------------------------------------------------------------------------
            | SAFE /usr/bin/node CALL (STDIN)
            |--------------------------------------------------------------------------
            */

            $process = proc_open(
                "/usr/bin/node {$scriptPath}",
                [
                    0 => ["pipe", "r"],
                    1 => ["pipe", "w"],
                    2 => ["pipe", "w"]
                ],
                $pipes
            );

            if (!is_resource($process)) {
                return response()->json([
                    'status' => false,
                    'message' => '/usr/bin/node process failed'
                ], 500);
            }

            fwrite($pipes[0], json_encode($payload));
            fclose($pipes[0]);

            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            fclose($pipes[2]);

            proc_close($process);

            $wallet = json_decode($output, true);

            if (!$wallet || isset($wallet['error'])) {
                return response()->json([
                    'status' => false,
                    'message' => $wallet['message'] ?? 'Invalid wallet',
                    'wallet' => new \stdClass()
                ], 400);
            }


            $getWallet = Wallet::where('wallet_address',$wallet['address'])->where('app_id',user()->app_id)->first();

            if($getWallet){
                return response()->json(['status'=>false, 'wallet_id'=>0, 'message'=>'The wallet address has already been taken'],422);
            }

            $mnemonic = json_encode($request->mnemonic);

            $qrFileName = 'wallet_qr_' . time() . '.svg';

            $qrPath = public_path('uploads/qrcodes/' . $qrFileName);

            // folder create if not exists
            if (!file_exists(public_path('uploads/qrcodes'))) {

                mkdir(public_path('uploads/qrcodes'), 0777, true);
            }

            QrCode::format('svg')
                ->size(300)
                ->generate($mnemonic, $qrPath);

            $data = new Wallet();
            $data->app_id = user()->app_id;
            $data->wallet_address = $wallet['address'];
            $data->qrcode = 'uploads/qrcodes/' . $qrFileName;
            $data->private_key = Crypt::encryptString($wallet['privateKey']);
            $data->mnemonic = Crypt::encryptString($mnemonic);
            $data->save();

            return response()->json([
                'status' => true,
                'wallet_id' => intval($data->id),
                'wallet_address' => $data->wallet_address,
                'qrcode'   => $data->qrcode,
                //'mnemonic' => $request->mnemonic,
                'message' => 'Successfully the wallet address has been added',
            ]);

        } catch (Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function myContracts(Request $request)
    {
        try {

            // =========================
            // VALIDATION
            // =========================
            $validator = Validator::make($request->all(), [
                'wallet_address' => 'required|string|exists:wallets,wallet_address',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Please fill all requirement fields',
                    'data' => $validator->errors()
                ], 422);
            }

            // =========================
            // GET WALLET
            // =========================
            $wallet = Wallet::select('id','app_id','wallet_address')
                //->where('app_id', user()->app_id)
                ->where('wallet_address', $request->wallet_address)
                ->first();

            if (!$wallet) {
                return response()->json([
                    'status' => false,
                    'message' => 'Wallet not found'
                ], 404);
            }

            $per_page = $request->has('per_page')?$request->per_page:5;

            // =========================
            // GET CONTRACTS
            // =========================
            $contracts = Contract::where('user_id',1)->orWhere('user_id',user()->id)->paginate($request->per_page);

            // =========================
            // TRANSFORM WITH CACHE
            // =========================
            $contracts->getCollection()->transform(function ($contract) use ($wallet) {

                $walletAddress = $wallet->wallet_address;

                $cacheKey = "balance_{$walletAddress}_{$contract->id}";

                $balance = Cache::remember($cacheKey, 60, function () use ($walletAddress, $contract) {

                    // =========================
                    // BNB BALANCE
                    // =========================
                    if ($contract->contract_address === null) {

                        $response = Http::post("https://bsc-dataseed.binance.org", [
                            "jsonrpc" => "2.0",
                            "method" => "eth_getBalance",
                            "params" => [$walletAddress, "latest"],
                            "id" => 1
                        ]);

                        $result = $response->json();

                        if (!isset($result['result'])) {
                            return "0";
                        }

                        $wei = hexdec($result['result']);
                        $bnb = $wei / 1e18;

                        return rtrim(rtrim(number_format($bnb, 18, '.', ''), '0'), '.');
                    }

                    // =========================
                    // BEP20 TOKEN BALANCE
                    // =========================
                    $response = Http::post("https://bsc-dataseed.binance.org", [
                        "jsonrpc" => "2.0",
                        "method" => "eth_call",
                        "params" => [[
                            "to" => $contract->contract_address,
                            "data" => "0x70a08231000000000000000000000000" . substr($walletAddress, 2)
                        ], "latest"],
                        "id" => 1
                    ]);

                    $result = $response->json();

                    if (!isset($result['result'])) {
                        return "0";
                    }

                    $wei = hexdec($result['result']);
                    $token = $wei / 1e18;

                    return rtrim(rtrim(number_format($token, 18, '.', ''), '0'), '.');
                });

                return [
                    'id'               => intval($contract->id),
                    'contract_name'    => $contract->contract_name,
                    'contract_address' => $contract->contract_address,
                    'symbol'           => $contract->contract_symbol,
                    'image'            => $contract->image,
                    'balance'          => $balance,
                ];
            });

            return response()->json([
                'status' => true,
                'wallet' => $wallet,
                'contracts' => $contracts
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function contractDetails(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'contract_address' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'data' => $validator->errors()
                ], 422);
            }

            $scriptPath = public_path('web3/getTokenInfo.js');
            $command = "/usr/bin/node $scriptPath $request->contract_address";
            $output = shell_exec($command);
            $result = json_decode($output,true);
            if($result['status'] == true){
                return response()->json(['status'=>true, 'message'=>'Data found', 'data'=>$result]);
            }
            return response()->json(['status'=>false, 'message'=>'No data found', 'data'=>new \stdClass()],404);
        }catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function saveContract(Request $request)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'contract_name' => 'required|string',
                'contract_address' => 'required|string',
                'contract_symbol' => 'required|string',
                'contract_decimals' => 'required|numeric',                                                
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'data' => $validator->errors()
                ], 422);
            }

            $count = Contract::where('contract_address',$request->contract_address)->where('user_id',user()->id)->count();

            if($count > 0){
                return response()->json(['status'=>false, 'contract_id'=>0, 'message'=>'The contract already has been taken', 'data'=>new \stdClass()],429);
            }

            $contract = new Contract();
            $contract->user_id = user()->id;
            $contract->contract_name = $request->contract_name;
            $contract->contract_address = $request->contract_address;
            $contract->contract_symbol = $request->contract_symbol;
            $contract->contract_decimals = $request->contract_decimals;
            $contract->network = 'bnb';
            $contract->image = 'defaults/bnb_logo.png';
            $contract->save();

            return response()->json(['status'=>true, 'contract_id'=>intval($contract->id), 'message'=>'Successfully the contract has been added', 'data'=>$contract]);


        }catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function maxBNBAmount(Request $request)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'wallet_id' => 'required|integer|exists:wallets,id'                                                
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'data' => $validator->errors()
                ], 422);
            }

            $wallet = Wallet::findorfail($request->wallet_id);

            $privateKey = Crypt::decryptString($wallet->private_key);

            $scriptPath = public_path('web3/bnbRange.js');
            $command = "/usr/bin/node $scriptPath $privateKey";
            $output = shell_exec($command);
            $result = json_decode($output,true);
            return $result;
        }catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function bnbTransfer(Request $request)
    {  
        date_default_timezone_set('Asia/Dhaka');
        try
        {
            $validator = Validator::make($request->all(), [
                'wallet_id'         => 'required|integer|exists:wallets,id',
                'contract_id'       => 'required|integer|exists:contracts,id',
                'recipient_address' => 'required|string',
                'amount'            => 'required|numeric',                                             
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'data' => $validator->errors()
                ], 422);
            }

            $wallet = Wallet::findorfail($request->wallet_id);

            $privateKey = Crypt::decryptString($wallet->private_key);
            $toAddress = $request->recipient_address;
            $amount = $request->amount;

            $scriptPath = public_path('web3/bnbTransaction.js');
            $command = "/usr/bin/node $scriptPath $privateKey $toAddress $amount";
            $output = shell_exec($command);
            $result = trim($output);

            if($result == 'failed to transaction'){
                return response()->json(['status'=>false, 'message'=>"Insufficient Balance", 'data'=>new \stdClass()],400);
            }

            $transaction = new Transaction();
            $transaction->user_id = user()->id;
            $transaction->wallet_id = $wallet->id;
            $transaction->contract_id = $request->contract_id;
            $transaction->sender_address = $wallet->wallet_address;
            $transaction->recipient_address = $request->recipient_address;
            $transaction->amount = $request->amount;
            $transaction->date = date('Y-m-d');
            $transaction->time = date('h:i:s a');
            $transaction->timestamp = time();
            $transaction->transaction_hash = $result;
            $transaction->save();

            return response()->json(['status'=>true, 'message'=>'Trancation Successfull', 'data'=>$transaction]);

        }catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function tokenTransfer(Request $request)
    {
        date_default_timezone_set('Asia/Dhaka');
        try
        {
            $validator = Validator::make($request->all(), [
                'wallet_id'         => 'required|integer|exists:wallets,id',
                'contract_id'       => 'required|integer|exists:contracts,id',
                'recipient_address' => 'required|string',
                'amount'            => 'required|numeric',                                             
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'data' => $validator->errors()
                ], 422);
            }

            $wallet = Wallet::findorfail($request->wallet_id);
            $contract = Contract::findorfail($request->contract_id);

            $privateKey = Crypt::decryptString($wallet->private_key);
            $toAddress = $request->recipient_address;
            $amount = $request->amount;
            $contractAddress = $contract->contract_address;
            $decimals = $contract->contract_decimals;

            $scriptPath = public_path('web3/tokenTransfer.js');
            $command = "/usr/bin/node $scriptPath $privateKey $toAddress $amount $contractAddress $decimals";
            $output = shell_exec($command);
            $result = trim($output);

            if($result == 'failed to transaction'){
                return response()->json(['status'=>false, 'message'=>"Insufficient Balance", 'data'=>new \stdClass()],400);
            } 

            $transaction = new Transaction();
            $transaction->user_id = user()->id;
            $transaction->wallet_id = $wallet->id;
            $transaction->contract_id = $request->contract_id;
            $transaction->sender_address = $wallet->wallet_address;
            $transaction->recipient_address = $request->recipient_address;
            $transaction->amount = $request->amount;
            $transaction->date = date('Y-m-d');
            $transaction->time = date('h:i:s a');
            $transaction->timestamp = time();
            $transaction->transaction_hash = $result; 
            $transaction->save();

            return response()->json(['status'=>true, 'message'=>'Trancation Successfull', 'data'=>$transaction]);

        }catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        } 
    }

    public function transactionHistories(Request $request)
    {
        try
        {
            $query = Transaction::query();
            if($request->has('transaction_hash')){
                $search = $request->transaction_hash;
                $query->where('transaction_hash', 'LIKE', "%{$search}%");
            }
            if($request->has('contract_id')){
                $query->where('contract_id',$request->contract_id);
            }
            if($request->has('wallet_id')){
                $query->where('wallet_id',$request->wallet_id);
            }
            $per_page = $request->has('per_page')?$request->per_page:10;
            $data = $query->where('user_id',user()->id)->with('wallet','contract')->latest()->paginate($request->per_page);
            return response()->json($data);
        }catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function transactionDetails($id)
    {
        try
        {
            $transaction = Transaction::with('wallet','contract')->findorfail($id);
            return response()->json(['status'=>true, 'data'=>$transaction]);
        }catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function tokenExchangePrice(Request $request)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'from_contract_id'  => 'required|integer|exists:contracts,id',
                'to_contract_id'    => 'required|integer|exists:contracts,id',
                'amount'            => 'required|numeric',                                              
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'data' => $validator->errors()
                ], 422);
            }

            $contractOne = Contract::findorfail($request->from_contract_id);
            $contractTwo = Contract::findorfail($request->to_contract_id);

            //return $contractTwo;

            //$privateKey = Crypt::decryptString($wallet->private_key);

            $scriptPath = public_path('web3/tokenSwapPrice.js');
            $command = "/usr/bin/node $scriptPath $contractOne->contract_address $contractTwo->contract_address $request->amount $contractOne->contract_decimals $contractTwo->contract_decimals";
            $output = shell_exec($command);

            $result = trim($output);

            return response()->json(['status'=>true, 'data'=>array('price'=>strval($result),'symbol'=>$contractTwo->contract_symbol)]);

        }catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function tokenToToken(Request $request)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'wallet_id'         => 'required|integer|exists:wallets,id',
                'from_contract_id'  => 'required|integer|exists:contracts,id',
                'to_contract_id'    => 'required|integer|exists:contracts,id',
                'amount'            => 'required|numeric',                                              
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'data' => $validator->errors()
                ], 422);
            }

            $wallet = Wallet::findorfail($request->wallet_id);

            $contractOne = Contract::findorfail($request->from_contract_id);
            $contractTwo = Contract::findorfail($request->to_contract_id);

            //return $contractTwo;

            $privateKey = Crypt::decryptString($wallet->private_key);

            $scriptPath = public_path('web3/tokenTotoken.js');
            $command = "/usr/bin/node $scriptPath $privateKey $contractOne->contract_address $contractTwo->contract_address $request->amount $contractOne->contract_decimals $contractTwo->contract_decimals";
            $output = shell_exec($command);

            $result = trim($output);

            //return $result;

            if($result == 'Failed to swap'){
                return response()->json(['status'=>false, 'message'=>'Something Went Wrong', 'transaction_hash'=>""],429);
            }

            return response()->json(['status'=>true, 'message'=>'Successfully swap', 'transaction_hash'=>$result],200);

        }catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function contractLists(Request $request)
    {
        try
        {   
            //$wallet = 
            $contracts = Contract::where('user_id',1)->orWhere('user_id',user()->id)->get();
            return response()->json(['status'=>count($contracts) > 0, 'data'=>$contracts]);
        }catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function bnbToToken(Request $request)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'wallet_id'         => 'required|integer|exists:wallets,id',
                //'from_contract_id'  => 'required|integer|exists:contracts,id',
                'to_contract_id'    => 'required|integer|exists:contracts,id',
                'amount'            => 'required|numeric',                                              
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'data' => $validator->errors()
                ], 422);
            }

            $wallet = Wallet::findorfail($request->wallet_id);

            $contractOne = Contract::find(1);
            //return $contractOne;
            $contractTwo = Contract::findorfail($request->to_contract_id);

            //return $contractTwo;

            $privateKey = Crypt::decryptString($wallet->private_key);

            $scriptPath = public_path('web3/bnbToToken.js');
            $command = "/usr/bin/node $scriptPath $privateKey $contractTwo->contract_address $request->amount $contractTwo->contract_decimals";
            $output = shell_exec($command);

            $result = trim($output);

            //return $result;

            if($result == 'Failed to swap'){
                return response()->json(['status'=>false, 'message'=>'Something Went Wrong', 'transaction_hash'=>""],429);
            }

            return response()->json(['status'=>true, 'message'=>'Successfully swap', 'transaction_hash'=>$result],200);

        }catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function tokenToBnb(Request $request)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'wallet_id'         => 'required|integer|exists:wallets,id',
                //'from_contract_id'  => 'required|integer|exists:contracts,id',
                'from_contract_id'    => 'required|integer|exists:contracts,id',
                'amount'            => 'required|numeric',                                              
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'data' => $validator->errors()
                ], 422);
            }

            $wallet = Wallet::findorfail($request->wallet_id);

            $contractOne = Contract::find(1);
            //return $contractOne;
            $contractTwo = Contract::findorfail($request->from_contract_id);

            //return $contractTwo;

            $privateKey = Crypt::decryptString($wallet->private_key);

            $scriptPath = public_path('web3/tokenToBNB.js');
            $command = "/usr/bin/node $scriptPath $privateKey $contractTwo->contract_address $request->amount $contractTwo->contract_decimals";
            $output = shell_exec($command);

            $result = trim($output);


            if($result == 'failed to swap'){
                return response()->json(['status'=>false, 'message'=>'Something Went Wrong', 'transaction_hash'=>""],429);
            }

            return response()->json(['status'=>true, 'message'=>'Successfully swap', 'transaction_hash'=>$result],200);

        }catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // public function test/usr/bin/node()
    // {
    //     return response()->json([
    //         'shell_exec_exists' => function_exists('shell_exec'),
    //         '/usr/bin/node' => shell_exec('which /usr/bin/node 2>&1'),
    //         'version' => shell_exec('/usr/bin//usr/bin/node -v 2>&1'),
    //     ]);
    // }
}
