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
<section class="panel data-panel"><div class="panel-heading"><h2>Recent Orders</h2></div><div class="table-responsive"><table class="table"><thead><tr><th>Order</th><th>Customer</th><th>Status</th><th>Payment</th><th>Total</th><th></th></tr></thead><tbody>@foreach($recentOrders as $order)<tr><td><strong>{{ $order->order_number }}</strong></td><td>{{ $order->customer_name }}</td><td><span class="status-badge status-badge--{{ $order->status }}">{{ str($order->status)->headline() }}</span></td><td><span class="status-badge status-badge--{{ $order->payment_status }}">{{ str($order->payment_status)->headline() }}</span></td><td>PKR {{ number_format($order->grand_total) }}</td><td class="cell-actions"><a class="icon-btn" href="{{ route('admin.orders.show',$order) }}" title="View order"><i data-lucide="eye"></i></a></td></tr>@endforeach</tbody></table></div></section>
@push('scripts')
<script>
new ApexCharts(document.querySelector("#sales-chart"), {chart:{type:'line',height:260,toolbar:{show:false}},series:[{name:'Sales',data:@json($dailySales->pluck('total'))}],xaxis:{categories:@json($dailySales->pluck('day'))}}).render();
</script>
@endpush
@endsection
