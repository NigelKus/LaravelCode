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
    
    protected $table = 'sales_order_detail';
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
        $currentRemaining = $this->quantity_remaining; 
        $currentRemaining = $currentRemaining += $amount;
        
    }
    
    public static function checkAndUpdateStatus(int $salesOrderId, int $productId,int $salesOrderDetailId): bool
    {
        $salesOrder = SalesOrder::find($salesOrderId);
        if (!$salesOrder) {
            return false; 
        }
        
        $salesDetail = SalesOrderDetail::where('id', $salesOrderDetailId)
            ->where('product_id', $productId)
            ->first();
        if (!$salesDetail) {
            return false; 
        }
        
        $quantity_remaining = $salesDetail->quantity_remaining;
        if ($quantity_remaining <= 0) {
            $salesDetail->status = 'completed'; 
            $salesDetail->save();
            if ($salesOrder->details->every(fn($detail) => $detail->status === 'completed')) {
                $salesOrder->status = 'completed';
                $salesOrder->save();
            }
            
            return true;
            }
        $salesDetail->status = 'pending'; 
        $salesOrder->status = 'pending';
        $salesOrder->save();
        $salesDetail->save();
        return false;
    }   

    public function updateSalesOrderStatus(): void
    {
        $salesOrder = SalesOrder::find($this->salesorder_id);

        if (!$salesOrder) {
            throw new \Exception('Sales order not found.');
        }

        $allDetailsCompleted = self::where('salesorder_id', $this->salesorder_id)
            ->every(function ($detail) {
                return $detail->status === 'completed';
            });

        $salesOrder->status = $allDetailsCompleted ? 'completed' : 'pending';
        if (!$salesOrder->save()) {
            throw new \Exception('Failed to save sales order');
        }
    }
}
