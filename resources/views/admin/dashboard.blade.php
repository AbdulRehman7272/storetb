@extends('layouts.admin')
@section('title', 'Dashboard')
@section('content')
<div class="metric-grid">
    @foreach($cards as $label => $value)
        <div class="metric"><span>{{ $label }}</span><strong>{{ is_numeric($value) ? number_format($value) : $value }}</strong></div>
    @endforeach
</div>
<div class="admin-grid">
    <section class="panel"><h2>Sales Trend</h2><div id="sales-chart"></div></section>
    <section class="panel"><h2>Low Stock</h2>@foreach($lowStock as $p)<div class="line-item"><span>{{ $p->name }}</span><strong>{{ $p->stock }}</strong></div>@endforeach</section>
</div>
<section class="panel"><h2>Recent Orders</h2><div class="table-responsive"><table class="table table-sm"><tr><th>Order</th><th>Customer</th><th>Status</th><th>Payment</th><th>Total</th></tr>@foreach($recentOrders as $order)<tr><td><a href="{{ route('admin.orders.show',$order) }}">{{ $order->order_number }}</a></td><td>{{ $order->customer_name }}</td><td>{{ $order->status }}</td><td>{{ $order->payment_status }}</td><td>{{ number_format($order->grand_total) }}</td></tr>@endforeach</table></div></section>
@push('scripts')
<script>
new ApexCharts(document.querySelector("#sales-chart"), {chart:{type:'line',height:260,toolbar:{show:false}},series:[{name:'Sales',data:@json($dailySales->pluck('total'))}],xaxis:{categories:@json($dailySales->pluck('day'))}}).render();
</script>
@endpush
@endsection
