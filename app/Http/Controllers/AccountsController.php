<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;
use App\Http\Controllers\Token;
use App\Models\Accounts;
use App\Models\Transactions;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AccountsController extends Controller
{
    public function CreateBankAccount(Request $request)
    {
        $header = $request->header('Authorization');

        if (!$header) {
            return response()->json([
                'error' => "Unauthorized"
            ], 401);
        }

        $generateToken = new Token();
        $user = $generateToken->is_jwt_valid($header);

        if (!$user) {
            return response()->json([
                'error' => "Unauthorized"
            ], 401);
        }

        try {
            $request->validate([
                "account_name" => "required|max:150|min:3",
                "email" => "required|email|max:150|min:3|exists:users,email",
                "balance" => "numeric|default:0",
                "currency" => "required|in:USD,EUR",
                "account_type" => "required|in:normal,savings"
            ]);

            $userInfo = User::where('id', "=", $user)->first();

            if ($userInfo->email != $request->email) {
                return response()->json(["error" => "Unauthorized"], 401);
            }

            if (!$userInfo) {
                return response()->json(["message" => "User with email @" . $userInfo->email . " does not exist"], 403);
            } else {
                $check = Accounts::where('account_name', '=', $request->account_name)->first();

                if ($check) {
                    return response()->json([
                        'message' => "Account name already exists"
                    ], 422);
                }

                $register = new Accounts();

                $register->account_id = uniqid();
                $register->account_name = $request->account_name;
                $register->user_email = $userInfo->email;
                $register->balance = $request->initial_deposit ? $request->initial_deposit : 0;
                $register->account_type = $request->account_type;
                $register->currency = $request->currency;

                $saved = $register->save();

                if ($saved) {
                    return response()->json(["message" => "Account Created Successfully!"], 200);
                } else {
                    return response()->json(["message" => "Something went wrong"], 400);
                }
            }
        } catch (Exception $e) {;
            $message = $e->getMessage();
            if (stripos($message, 'The given data was invalid') !== false) {
                $message = print_r($e->validator->failed(), true);
                return response()->json([
                    'message' => __($e->getMessage()),
                    'errors' => $e->validator->getMessageBag()
                ], 422);
            } else {
                return response()->json([
                    'error' => $e->getMessage(),
                    'message' => 'Something went wrong'
                ], 400);
            }
        }
    }

    public function MakeTransfers(Request $request)
    {
        try {
            $header = $request->header('Authorization');

            if (!$header) {
                return response()->json([
                    'error' => "Unauthorized"
                ], 401);
            }

            $generateToken = new Token();
            $user = $generateToken->is_jwt_valid($header);

            if (!$user) {
                return response()->json([
                    'error' => "Unauthorized | Here"
                ], 401);
            }

            $request->validate([
                "from_account" => "required|exists:accounts,account_name",
                "to_account" => "required|exists:accounts,account_name",
                "amount" => "required|numeric"
            ]);

            if ($request->from_account == $request->to_account) {
                return response()->json([
                    'error' => "You cannot transfer to the same account"
                ], 422);
            }

            $fromAccount = Accounts::where('account_name', '=', $request->from_account)->first();

            $userinfo = User::where('id', '=', $user)->first();

            if ($fromAccount->user_email != $userinfo->email) {
                return response()->json([
                    'error' => "Unauthorized"
                ], 401);
            }

            $toAccount = Accounts::where('account_name', '=', $request->to_account)->first();

            if ($fromAccount->balance < $request->amount) {
                return response()->json([
                    'error' => "Insufficient balance"
                ], 422);
            }


            //make transaction
            $transaction = new Transactions();

            $transaction->transaction_id = uniqid();
            $transaction->from_account = $request->from_account;
            $transaction->to_account = $request->to_account;
            $transaction->amount = (int)$request->amount;
            $transaction->currency = $fromAccount->currency;
            $transaction->type = "transfer";

            $transaction->save();

            $fromAccount->balance = $fromAccount->balance - $request->amount;

            $toAccount->balance = $toAccount->balance + $request->amount;

            $saved = $fromAccount->save();
            $saved = $toAccount->save();

            if ($saved) {
                return response()->json([
                    'message' => "Transaction successful #" . $transaction->transaction_id . " | New balance: " . $fromAccount->balance . " " . $fromAccount->currency,
                ], 200);
            } else {
                return response()->json([
                    'error' => "Something went wrong!"
                ], 400);
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            if (stripos($message, 'The given data was invalid') !== false) {
                $message = print_r($e->validator->failed(), true);
                return response()->json([
                    'message' => __($e->getMessage()),
                    'errors' => $e->validator->getMessageBag()
                ], 422);
            } else {
                return response()->json([
                    'error' => $e->getMessage()
                ], 400);
            }
        }
    }

    public function GetAccountDetails(Request $request)
    {
        try {
            $header = $request->header('Authorization');

            if (!$header) {
                return response()->json([
                    'error' => "Authorization failed"
                ], 401);
            }

            $generateToken = new Token();
            $user = $generateToken->is_jwt_valid($header);

            if (!$user) {
                return response()->json([
                    'error' => "Authorization failed | here"
                ], 401);
            }

            $userInfo = User::where('id', "=", $user)->first();

            $request->validate([
                "transaction_id" => "required|exists:transactions,transaction_id"
            ]);

            if (!$userInfo) {
                return response()->json(["message" => "User with email @" . $userInfo->email . " does not exist"], 403);
            } else {
                $check = Accounts::where('user_email', '=', $userInfo->email)->where('account_id', '=', $request->accountId)->first();

                if (!$check) {
                    return response()->json([
                        'message' => "No account found"
                    ], 422);
                }

                return response()->json([
                    'message' => "Account found",
                    'data' => $check
                ], 200);
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            if (stripos($message, 'The given data was invalid') !== false) {
                $message = print_r($e->validator->failed(), true);
                return response()->json([
                    'message' => __($e->getMessage()),
                    'errors' => $e->validator->getMessageBag()
                ], 422);
            } else {
                return response()->json([
                    'error' => $e->getMessage()
                ], 400);
            }
        }
    }

    public function GetTransactions(Request $request)
    {
        try {
            $header = $request->header('Authorization');

            if (!$header) {
                return response()->json([
                    'error' => "Authorization failed"
                ], 401);
            }

            $generateToken = new Token();
            $user = $generateToken->is_jwt_valid($header);

            if (!$user) {
                return response()->json([
                    'error' => "Authorization failed"
                ], 401);
            }

            $request->validate([
                "transaction_id" => "required|exists:transactions,transaction_id"
            ]);

            $userInfo = User::where('id', "=", $user)->first();

            if (!$userInfo) {
                return response()->json(["message" => "User with email @" . $userInfo->email . " does not exist"], 403);
            } else {
                $check = Accounts::where('user_email', '=', $userInfo->email)->first();

                if (!$check) {
                    return response()->json([
                        'message' => "No account found"
                    ], 422);
                }

                $transactions = Transactions::where('transaction_id', '=', $request->transaction_id)->where('from_account', '=', $check->account_name)->get();

                return response()->json([
                    'message' => "Transactions found",
                    'data' => $transactions
                ], 200);
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            if (stripos($message, 'The given data was invalid') !== false) {
                $message = print_r($e->validator->failed(), true);
                return response()->json([
                    'message' => __($e->getMessage()),
                    'errors' => $e->validator->getMessageBag()
                ], 422);
            } else {
                return response()->json([
                    'error' => $e->getMessage()
                ], 400);
            }
        }
    }

    public function GetBalance(Request $request)
    {
        try {
            $header = $request->header('Authorization');

            if (!$header) {
                return response()->json([
                    'error' => "Authorization failed"
                ], 401);
            }

            $generateToken = new Token();
            $user = $generateToken->is_jwt_valid($header);

            if (!$user) {
                return response()->json([
                    'error' => "Authorization failed"
                ], 401);
            }

            $userInfo = User::where('id', "=", $user)->first();

            if (!$userInfo) {
                return response()->json(["message" => "User with email @" . $userInfo->email . " does not exist"], 403);
            } else {
                $check = Accounts::where('user_email', '=', $userInfo->email)->first();

                if (!$check) {
                    return response()->json([
                        'message' => "No account found"
                    ], 422);
                }

                return response()->json([
                    'message' => "Account found",
                    'balance' => $check->balance
                ], 200);
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            if (stripos($message, 'The given data was invalid') !== false) {
                $message = print_r($e->validator->failed(), true);
                return response()->json([
                    'message' => __($e->getMessage()),
                    'errors' => $e->validator->getMessageBag()
                ], 422);
            } else {
                return response()->json([
                    'error' => $e->getMessage()
                ], 400);
            }
        }
    }

    public function Deposit(Request $request)
    {
        try {
            $header = $request->header('Authorization');

            if (!$header) {
                return response()->json([
                    'error' => "Authorization failed"
                ], 401);
            }

            $generateToken = new Token();
            $user = $generateToken->is_jwt_valid($header);

            if (!$user) {
                return response()->json([
                    'error' => "Authorization failed"
                ], 401);
            }

            $request->validate([
                "amount" => "required|numeric|min:1"
            ]);

            $userInfo = User::where('id', "=", $user)->first();

            if (!$userInfo) {
                return response()->json(["message" => "User with email @" . $userInfo->email . " does not exist"], 403);
            } else {
                $check = Accounts::where('user_email', '=', $userInfo->email)->where('account_id', '=', $request->accountId)->first();

                if (!$check) {
                    return response()->json([
                        'message' => "No account found"
                    ], 422);
                }

                $check->balance = $check->balance + $request->amount;
                $check->save();

                $transaction = new Transactions();
                $transaction->transaction_id = uniqid();
                $transaction->from_account = $check->account_name;
                $transaction->to_account = $check->account_name;
                $transaction->amount = $request->amount;
                $transaction->currency = "USD";
                $transaction->type = "deposit";
                $transaction->save();

                $transaction->status = "success";
                $transaction->save();

                return response()->json([
                    'message' => "Deposit successful",
                    'balance' => $check->balance
                ], 200);
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            if (stripos($message, 'The given data was invalid') !== false) {
                $message = print_r($e->validator->failed(), true);
                return response()->json([
                    'message' => __($e->getMessage()),
                    'errors' => $e->validator->getMessageBag()
                ], 422);
            } else {
                return response()->json([
                    'error' => $e->getMessage()
                ], 400);
            }
        }
    }
}
