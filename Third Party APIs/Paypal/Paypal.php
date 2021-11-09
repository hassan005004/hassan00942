<?php

namespace App\Http\Controllers\ThirdPartiesApi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Paypal extends Controller
{
    // live account
    // protected static $username = 'AUvPdMHE6QXB0q-goNn-t3UdME-vk6u4dAE-oJ4BeZJDaroPI1vD-L3dK-j3rIQU8tXaOmwLc008s'; incorrect
    // protected static $password = 'EInnNFAzMWFaXgeu82JCLAhpcSnoVKnWLEGS6IvAG7vWGvMo20nvVNrzRKCF8h3EVXgHQNUFm5nvw'; incorrect
    // protected static $getBarrierTokenAPI = 'https://api-m.paypal.com/v1/oauth2/token';
    // protected static $requestPaymentPayoputAPI = 'https://api-m.paypal.com/v1/payments/payouts';

    // sandbox account
    protected static $username = 'AWovLJcCdhPCmnC6LmQ8vWmcLRg6epcbtxC7m9yafafk0ttZvIqsnnS0OAmgBO-FCjJ0UxzCrz6B8b'; // incorrect
    protected static $password = 'EL9VypGB5URfCP8A-suYzGTQH3TK9D19M8_MQ6aHlE8CiiGAwUp1KvijzCn9doEgENsmEmePwxWg_l'; // incorrect
    protected static $getBarrierTokenAPI = 'https://api-m.sandbox.paypal.com/v1/oauth2/token';
    protected static $requestPaymentPayoputAPI = 'https://api-m.sandbox.paypal.com/v1/payments/payouts';

    protected static $currency = 'USD';
    protected static $errors = [];
    // protected $getCheckBatchStatus = 'https://api-m.sandbox.paypal.com/v1/payments/payouts/';//EBGV2LSFNGCVW?fields=batch_header';
    
    public static function getBarrierToken(){
        $credentials = base64_encode(self::$username.":".self::$password);
        // return $credentials;
        $headers = [];
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        $headers[] = 'Authorization: Basic '.$credentials;
        
        $data ="grant_type=client_credentials";
        // $data = ['grant_type' => 'client_credentials'];
        $curlReturn = self::curl($headers,$data,self::$getBarrierTokenAPI);
        // print_r($curlReturn);
        $resultCode = $curlReturn['request_http_code'];
        $result = json_decode($curlReturn['request_result'],true);
        
        if($resultCode == '200'){
            $access = $result['access_token'];
            $msg = 'Success';
            $st = 1;
        }else{
            $access = '';
            $msg = self::$errors[] = $result['error_description'];
            $st = 0;
        }
        
        return [
            'st' => $st,
            'msg' => $msg,
            'access_token' => $access
        ];
        // return response()->json([
        //     'st' => $st,
        //     'access_token' => $access
        // ]);
        
    }
    
    public static function sendPayment($request){
        // $email = $request->email;
        // $amount = $request->amount;
        // $batch_id = $request->batch_id;
        // $sender_item_id = $request->sender_item_id;
        // $barrierToken = self::getBarrierToken();
        
        $email = $request['email'];
        $amount = $request['amount'];
        $batch_id = $request['batch_id'];
        $sender_item_id = $request['sender_item_id'];
        $barrierToken = self::getBarrierToken();
        
        if($barrierToken['st'] != 0){
            // return $barrierToken;
            // $headers = array(
            //     'Content-Type: application/json',
            //     'Authorization: Bearer A21AAJHnLf0siWx0RMR6NhePnySvDh9aW4mh08hC015fTvPxZ1st5L68Q1VBhhhPzLlHx5svHoEOz5miK1GAja8x91tdlQbMQ'
            //   );
            $data = '{
              "sender_batch_header": {
                "sender_batch_id": "'.$batch_id.'",
                "email_subject": "You have a payout!",
                "email_message": "You have received a payout! Thanks for using our service!"
              },
              "items": [
                {
                  "recipient_type": "EMAIL",
                  "amount": {
                    "value": "'.$amount.'",
                    "currency": "'.self::$currency.'"
                  },
                  "note": "Thanks for you!",
                  "sender_item_id": "'.$sender_item_id.'",
                  "receiver": "'.$email.'"
                }
              ]
            }';
            // return public_path('cacert.pem');
            // $data = json_encode($data);
            $headers = [];
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Authorization: Bearer '.$barrierToken['access_token'];
            // $headers[] = 'Content-Length: ' . strlen($data);
            
            $curlReturn = self::curl($headers,$data,self::$requestPaymentPayoputAPI);
            
            $resultCode = $curlReturn['request_http_code'];
            $result = json_decode($curlReturn['request_result'],true);
            
            if($resultCode == '201'){
                // $ = $result['batch_header'];
                $msg = 'Success';
                $st = 1;
            }else{
                $msg = $result['message'];//'You can not withdraw at this movement please contact us';
                $st = 0;
            }
            
            return [
                'st' => $st,
                'msg' => $msg,
            ];
        }else{
            return [
                'st' => 0,
                'msg' => $barrierToken['msg'],
            ];
        }
        
        // if($barrierToken['st'] == 1 && $barrierToken['access_token'] != ''){
        //     return $barrierToken;
        // }
        // print_r(self::errors);
        // return $barrierToken;
    }
    
    
    public static function curl($headers,$data,$url,$type = 'POST'){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, $url);
        if($data){
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        // curl_setopt($ch, CURLOPT_CAINFO, public_path('cacert.pem'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $request_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $response = json_decode($result);
        
        return ['request_http_code' => $request_http_code, 'request_result' => $result];
    }
}
