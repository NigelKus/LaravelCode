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
        $currentRemaining = $this->quantity_remaining; 
        $currentRemaining = $currentRemaining += $amount;
    }
    
    public static function checkAndUpdateStatus(int $purchaseOrderId, int $productId,int $purchaseOrderDetailId): bool
    {
        $purchaseOrder = PurchaseOrder::find($purchaseOrderId);
        if (!$purchaseOrder) {
            return false; 
        }
        
        $purchaseDetail = PurchaseOrderDetail::where('id', $purchaseOrderDetailId)
            ->where('product_id', $productId)
            ->first();
        if (!$purchaseDetail) {
            return false; 
        }
        
        $quantity_remaining = $purchaseDetail->quantity_remaining;  
        if ($quantity_remaining <= 0) {
            $purchaseDetail->status = 'completed'; 
            $purchaseDetail->save();
            if ($purchaseOrder->details->every(fn($detail) => $detail->status === 'completed')) {
                $purchaseOrder->status = 'completed';
                $purchaseOrder->save();
            }
            
            return true;
            }
        $purchaseDetail->status = 'pending'; 
        $purchaseOrder->status = 'pending';
        $purchaseOrder->save();
        $purchaseDetail->save();
        return false;
    }   

    public function updatePurchaseOrderStatus(): void
    {
        $purchaseOrder = PurchaseOrder::find($this->purchaseorder_id);

        if (!$purchaseOrder) {
            throw new \Exception('Purchase order not found.');
        }
        $allDetailsCompleted = self::where('purchaseorder_id', $this->purchaseorder_id)
            ->every(function ($detail) {
                return $detail->status === 'completed';
            });
        $purchaseOrder->status = $allDetailsCompleted ? 'completed' : 'pending';

        if (!$purchaseOrder->save()) {
            throw new \Exception('Failed to save purchase order');
        }
    }
}
