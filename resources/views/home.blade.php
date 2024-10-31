@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Dashboard</h1>
@stop

@section('content')
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
