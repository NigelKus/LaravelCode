<?php
namespace App\Models;

use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesInvoiceDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalesorderDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'salesorder_detail';
    const STATUS_DELETED = 'deleted';

    protected $fillable = [
        'salesorder_id',
        'product_id',
        'quantity',
        'price',
        'status',
    ];

    

    public function salesInvoiceDetail()
    {
        return $this->hasMany(SalesInvoiceDetail::class, 'salesdetail_id');
    }

    public function salesorder()
    {
        return $this->belongsTo(SalesOrder::class, 'salesorder_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function getQuantitySentAttribute()
    {
        return $this->SalesInvoiceDetail()
            ->whereIn('status', ['pending', 'completed'])
            ->sum('quantity') ;
    }
    
    public function getQuantityRemainingAttribute() 
    {
        return $this->quantity - $this->quantity_sent;
    }
    
    public function adjustQuantityRemaining($amount)
    {
        // Access the current quantity remaining
        $currentRemaining = $this->quantity_remaining; // This uses the accessor

        // Calculate the new remaining quantity
        $currentRemaining = $currentRemaining += $amount;
        
    }
    
    public static function checkAndUpdateStatus(int $salesOrderId, int $productId,int $salesOrderDetailId): bool
    {
        // dd($salesOrderId,$productId, $salesOrderDetailId);
        // Fetch the SalesOrder
        $salesOrder = SalesOrder::find($salesOrderId);
        if (!$salesOrder) {
            return false; // Sales order not found
        }
        
        // Fetch the SalesOrderDetail
        $salesDetail = SalesOrderDetail::where('id', $salesOrderDetailId)
            ->where('product_id', $productId)
            ->first();
        // dd($salesDetail);
        if (!$salesDetail) {
            return false; // Sales order detail not found for the product
        }
        
        $quantity_remaining = $salesDetail->quantity_remaining;
        if ($quantity_remaining <= 0) {
            $salesDetail->status = 'completed'; // Update status
            $salesDetail->save();
            
            // $allCompleted = $salesOrder->details->every(function($detail) {
            //     return $detail->status === 'completed'; // Ensure this returns a boolean
            // });

            // // Update SalesOrder status if all details are completed
            //     if ($allCompleted) {
            //         $salesOrder->status = 'completed';
            //         $salesOrder->save();
            //     }
            //     return true;
            
            if ($salesOrder->details->every(fn($detail) => $detail->status === 'completed')) {
                $salesOrder->status = 'completed';
                $salesOrder->save();
            }
            
            return true;
            }
        $salesDetail->status = 'pending'; 
        $salesOrder->status = 'pending';
        // dd($quantity_remaining, $salesDetail, $salesOrder);
        $salesOrder->save();
        $salesDetail->save();
        
        // dd($salesOrder, $salesDetail, $quantity_remaining, $salesDetail->quantity);
        // Calculate the remaining quantity using accessor (optional for debugging)
        

        return false;
    }   

    public function updateSalesOrderStatus(): void
    {
        // Fetch the sales order record associated with this detail
        $salesOrder = SalesOrder::find($this->salesorder_id);

        if (!$salesOrder) {
            throw new \Exception('Sales order not found.');
        }

        // Check if all details are completed
        $allDetailsCompleted = self::where('salesorder_id', $this->salesorder_id)
            ->every(function ($detail) {
                return $detail->status === 'completed';
            });

        // Update sales order status based on details' statuses
        $salesOrder->status = $allDetailsCompleted ? 'completed' : 'pending';

        // Save the sales order (wrap in try-catch for debugging)
        if (!$salesOrder->save()) {
            throw new \Exception('Failed to save sales order');
        }
    }
}
