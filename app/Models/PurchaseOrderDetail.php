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

class PurchaseOrderDetail extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'purchase_order_detail';
    const STATUS_DELETED = 'deleted';

    protected $fillable = [
        'purchaseorder_id',
        'product_id',
        'quantity',
        'price',
        'status',
    ];
    

    public function purchaseInvoiceDetail()
    {
        return $this->hasMany(PurchaseInvoiceDetail::class, 'purchasedetail_id');
    }
    
    public function purchaseorder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchaseorder_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function getQuantitySentAttribute()
    {
        return $this->purchaseInvoiceDetail()
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
    
    public static function checkAndUpdateStatus(int $purchaseOrderId, int $productId,int $purchaseOrderDetailId): bool
    {
        // dd($salesOrderId,$productId, $salesOrderDetailId);
        // Fetch the SalesOrder
        $purchaseOrder = PurchaseOrder::find($purchaseOrderId);
        if (!$purchaseOrder) {
            return false; // Sales order not found
        }
        
        // Fetch the SalesOrderDetail
        $purchaseDetail = PurchaseOrderDetail::where('id', $purchaseOrderDetailId)
            ->where('product_id', $productId)
            ->first();
        // dd($salesDetail);
        if (!$purchaseDetail) {
            return false; // Sales order detail not found for the product
        }
        
        $quantity_remaining = $purchaseDetail->quantity_remaining;  
        if ($quantity_remaining <= 0) {
            $purchaseDetail->status = 'completed'; // Update status
            $purchaseDetail->save();
            
            // $allCompleted = $salesOrder->details->every(function($detail) {
            //     return $detail->status === 'completed'; // Ensure this returns a boolean
            // });

            // // Update SalesOrder status if all details are completed
            //     if ($allCompleted) {
            //         $salesOrder->status = 'completed';
            //         $salesOrder->save();
            //     }
            //     return true;
            
            if ($purchaseOrder->details->every(fn($detail) => $detail->status === 'completed')) {
                $purchaseOrder->status = 'completed';
                $purchaseOrder->save();
            }
            
            return true;
            }
        $purchaseDetail->status = 'pending'; 
        $purchaseOrder->status = 'pending';
        // dd($quantity_remaining, $salesDetail, $salesOrder);
        $purchaseOrder->save();
        $purchaseDetail->save();
        
        // dd($salesOrder, $salesDetail, $quantity_remaining, $salesDetail->quantity);
        // Calculate the remaining quantity using accessor (optional for debugging)
        

        return false;
    }   

    public function updatePurchaseOrderStatus(): void
    {
        // Fetch the sales order record associated with this detail
        $purchaseOrder = PurchaseOrder::find($this->purchaseorder_id);

        if (!$purchaseOrder) {
            throw new \Exception('Purchase order not found.');
        }

        // Check if all details are completed
        $allDetailsCompleted = self::where('purchaseorder_id', $this->purchaseorder_id)
            ->every(function ($detail) {
                return $detail->status === 'completed';
            });

        // Update sales order status based on details' statuses
        $purchaseOrder->status = $allDetailsCompleted ? 'completed' : 'pending';

        // Save the sales order (wrap in try-catch for debugging)
        if (!$purchaseOrder->save()) {
            throw new \Exception('Failed to save purchase order');
        }
    }
}
