<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\BorrowTransactionRequest;
use App\Models\Item;
use App\Models\Notification;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /**
     * Display a listing of the transactions.
     */
    public function index(Request $request)
    {
        try {
            $query = Transaction::with(['user', 'item']);
            
            // Filter by user if not admin
            if (!$request->user()->hasRole('admin')) {
                $query->where('user_id', $request->user()->id);
            } else if ($request->has('user_id')) {
                $query->where('user_id', $request->input('user_id'));
            }
            
            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }
            
            $transactions = $query->orderBy('created_at', 'desc')->get();
            
            return response()->json([
                'status' => true,
                'data' => $transactions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch transactions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Borrow an item.
     */
    public function borrow(BorrowTransactionRequest $request)
    {
        try {
            // Check if user is restricted
            if ($request->user()->is_restricted) {
                return response()->json([
                    'status' => false,
                    'message' => 'Your account is restricted due to overdue items. Please return all overdue items to regain access.'
                ], 403);
            }

            $item = Item::find($request->item_id);
            
            // Check if item exists
            if (!$item) {
                return response()->json([
                    'status' => false,
                    'message' => 'Item not found'
                ], 404);
            }
            
            // Check if item is availabl
            if ($item->status !== 'available') {
                return response()->json([
                    'status' => false,
                    'message' => 'Item is not available for borrowing'
                ], 400);
            }
            
            // Check if item quantity is sufficient
            if ($item->quantity < 1) {
                return response()->json([
                    'status' => false,
                    'message' => 'Item is out of stock'
                ], 400);
            }

            // Create transaction
            $transaction = Transaction::create([
                'user_id' => $request->user()->id,
                'item_id' => $request->item_id,
                'borrow_date' => $request->borrow_date,
                'due_date' => $request->due_date,
                'status' => 'borrowed'
            ]);

            // Update item quantity
            $item->quantity -= 1;
            if ($item->quantity == 0) {
                $item->status = 'maintenance'; 
            }
            $item->save();

            // Create notification
            Notification::create([
                'user_id' => $request->user()->id,
                'message' => "You have borrowed {$item->name}. Please return it by " . Carbon::parse($request->due_date)->format('M d, Y'),
                'status' => 'unread'
            ]);

            $transaction->load(['user', 'item']);

            return response()->json([
                'status' => true,
                'message' => 'Item borrowed successfully',
                'data' => $transaction
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to borrow item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Return a borrowed item.
     */
    public function return(Request $request, $id)
    {
        try {
            $transaction = Transaction::find($id);
            
            if (!$transaction) {
                return response()->json([
                    'status' => false,
                    'message' => 'Transaction not found'
                ], 404);
            }

            // Check if transaction belongs to user or user is admin
            if ($transaction->user_id !== $request->user()->id && !$request->user()->hasRole('admin')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // Check if transaction is already returned
            if ($transaction->status !== 'borrowed') {
                return response()->json([
                    'status' => false,
                    'message' => 'Item is already returned or transaction is cancelled'
                ], 400);
            }

            // Update transaction
            $transaction->status = 'returned';
            $transaction->return_date = Carbon::now();
            $transaction->save();

            // Update item
            $item = Item::find($transaction->item_id);
            if ($item) {
                $item->quantity += 1;
                if ($item->status === 'maintenance') {
                    $item->status = 'available';
                }
                $item->save();
            }

            // Check if user has any overdue items
            $overdueCount = Transaction::where('user_id', $transaction->user_id)
                ->where('status', 'borrowed')
                ->where('due_date', '<', Carbon::now())
                ->count();

            // If no overdue items, remove restriction if any
            if ($overdueCount === 0) {
                $user = User::find($transaction->user_id);
                if ($user && $user->is_restricted) {
                    $user->is_restricted = false;
                    $user->save();
                    
                    // Create notification
                    Notification::create([
                        'user_id' => $user->id,
                        'message' => 'Your account restriction has been lifted. Thank you for returning all overdue items.',
                        'status' => 'unread'
                    ]);
                }
            }

            // Create notification
            if ($item) {
                Notification::create([
                    'user_id' => $transaction->user_id,
                    'message' => "You have returned {$item->name}. Thank you!",
                    'status' => 'unread'
                ]);
            }

            $transaction->load(['user', 'item']);

            return response()->json([
                'status' => true,
                'message' => 'Item returned successfully',
                'data' => $transaction
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to return item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel a transaction (admin only).
     */
    public function cancel(Request $request, $id)
    {
        try {
            $transaction = Transaction::find($id);
            
            if (!$transaction) {
                return response()->json([
                    'status' => false,
                    'message' => 'Transaction not found'
                ], 404);
            }

            // Check if transaction is already returned or cancelled
            if ($transaction->status !== 'borrowed') {
                return response()->json([
                    'status' => false,
                    'message' => 'Transaction is already returned or cancelled'
                ], 400);
            }

            // Update transaction
            $transaction->status = 'cancelled';
            $transaction->save();

            // Update item
            $item = Item::find($transaction->item_id);
            if ($item) {
                $item->quantity += 1;
                if ($item->status === 'maintenance') {
                    $item->status = 'available';
                }
                $item->save();

                // Create notification
                Notification::create([
                    'user_id' => $transaction->user_id,
                    'message' => "Your transaction for {$item->name} has been cancelled by an administrator.",
                    'status' => 'unread'
                ]);
            }

            $transaction->load(['user', 'item']);

            return response()->json([
                'status' => true,
                'message' => 'Transaction cancelled successfully',
                'data' => $transaction
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to cancel transaction: ' . $e->getMessage()
            ], 500);
        }
    }
}
