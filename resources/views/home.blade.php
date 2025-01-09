@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Dashboard</h1>
@stop

@section('content')
<div class="row row-cols-1 row-cols-md-2 g-2">
    <div class="col">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{$salesOrder ?? 0}}</h3>
                <p>New Orders</p>
            </div>
            <div class="icon">
                <i class="fas fa-clipboard"></i>
            </div>
            <a href="{{ route('sales_order.index') }}" class="small-box-footer">
                More info <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{$purchaseOrder ?? 0}}</h3>
                <p>New Purchases</p>
            </div>
            <div class="icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <a href="{{ route('sales_invoice.index') }}" class="small-box-footer">
                More info <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
</div>

<div class="row row-cols-1 row-cols-md-3 g-3">
    <div class="col">
        <div class="info-box bg-primary">
            <span class="info-box-icon"><i class="fas fa-shopping-bag"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Most Sold Product</span>
                <span class="info-box-text">{{ $product ?? 0}}</span>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="info-box bg-success">
            <span class="info-box-icon"><i class="fas fa-box"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Outstanding Sales</span>
                <span class="info-box-number">{{ $outstandingSales ?? 0}}</span>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="info-box bg-warning">
            <span class="info-box-icon"><i class="fas fa-file-invoice"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Outstanding Purchase</span>
                <span class="info-box-number">{{ $outstandingPurchase ?? 0}}</span>
            </div>
        </div>
    </div>
</div>


<div class="card">
    <div class="card-header">
        <h3 class="card-title">Weekly Revenue Chart</h3>
    </div>
    <div class="card-body">
        <div style="width: 100%; height: 400px; margin: auto;">
            <canvas id="revenue-chart-canvas" class="chartjs-render-monitor"></canvas>
        </div>
    </div>
</div>
@stop

@section('css')
    {{-- Add extra stylesheets here --}}
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Pass PHP data to JavaScript
        const weeklyRevenue = @json($weeklyRevenue);

        const revenueData = {
            labels: weeklyRevenue.map((_, i) => `Week ${i + 1}`), // Labels like 'Week 1', 'Week 2', etc.
            datasets: [{
                label: 'Weekly Revenue',
                backgroundColor: 'rgba(60, 141, 188, 0.2)',
                borderColor: 'rgba(60, 141, 188, 1)',
                pointBackgroundColor: 'rgba(60, 141, 188, 1)',
                pointBorderColor: '#3b8bba',
                data: weeklyRevenue
            }]
        };

        const ctx = document.getElementById('revenue-chart-canvas').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: revenueData,
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    x: { title: { display: true, text: 'Weeks' } },
                    y: { title: { display: true, text: 'Revenue ($)' }, beginAtZero: true }
                },
                plugins: { legend: { display: true, position: 'top' } }
            }
        });
    </script>
@stop
