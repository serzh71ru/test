<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Service\PaymentService;
use App\Models\Transaction;

class PaymentController extends Controller
{
    public function index(){
        $transactions = Transaction::all();

        return view('payments.index', ['transactions' => $transactions]);
    }

    public function create(Request $request, PaymentService $service){
        $amount = (float)$request->input('amount');
        $description = $request->input('description');

        $transaction = Transaction::create([
            'amount' => $amount,
            'description' => $description
        ]);

        if($transaction){
            $link = $service->createPayment($amount, $description, ['transaction_id' => $transaction->id]);
            dd($link);
            // return redirect()->away($link);
        }
    }

    public function callback(Request $request, PaymentService $service){
        $source = file_get_contents('php://input');
        $requestBody = json_decode($source, true);
        $notification = (isset($requestBody['event']) && $requestBody['event'] === NotificationEventType::PAYMENT_SUCCEEDED)
            ? new NotificationSucceeded($requestBody)
            : new NotificationWaitingForCapture($requestBody);
        $payment = $notification->getObject();

        if(isset($payment->status) && $payment->status === 'waiting_for_capture'){
            $service->getClient()->capturePayment([
                'amount' => $payment->amount,
            ], $payment->id, uniqid('', true));
        }

        if(isset($payment->status) && $payment->status === 'succeeded'){
            if((bool)$payment->paid === true){
                $metadata = (object)$payment->metadata;
                if(isset($metadata->transaction_id)){
                    $transactionId = (int)$metadata->transaction_id;
                    $transaction = Transaction::find($transactionId);
                    $transaction->status = 'CONFIRMED';
                    $transaction->save();
        
                    if(cache()->has('amount')){
                        catche()->forever('balance', (float)cache()->get('balance') + (float)$payment->amount->value);
                    }else{
                        catche()->forever('balance', (float)$payment->amount->value);
                    }
                }
            }
        }
        
    }
}
