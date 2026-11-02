@extends('layouts.app')

@section('title', 'Follow-up proposal · Pegel')

@section('content')
    @php
        $pd = fn (float $number) => rtrim(rtrim(number_format($number, 2), '0'), '.');
        $day = fn ($date) => $date->format('j M Y').' (week '.(int) $date->format('W').')';
        $late = $proposal->isOrderByOverdue($today);

        // the same text as a plain draft, to copy into an e-mail or the purchasing system
        $draft = implode("\n", [
            'Follow-up order for order '.$order->number.' ('.$order->title.')',
            'Supplier: '.$order->supplier->name,
            'Cost center: '.$order->cost_center.($order->project_element ? ', project element '.$order->project_element : ''),
            'Scope: '.$pd($proposal->suggestedPd).' PD (person-days)',
            'Period: '.$day($proposal->startsOn).' to '.$day($proposal->endsOn),
            'Place the order by: '.$day($proposal->orderByDate).' ('.$proposal->leadWeeks.' weeks lead time'.($late ? ', this date has passed, please order now' : '').')',
            'Reason: at the current pace of '.$pd($forecast->burnPerWeek).' PD per week the budget of order '.$order->number.' runs out on '.$day($forecast->exhaustionDate).'. About '.$pd($forecast->missingPd).' PD are missing until it ends on '.$day($proposal->endsOn).'.',
        ]);
    @endphp

    <h1>Follow-up order proposal</h1>
    <p class="muted">A draft for order {{ $order->number }} · {{ $order->title }}. Check it before it goes to the supplier; the numbers come from the forecast and its assumptions.</p>

    <div class="card">
        <table>
            <tr><th>Supplier</th><td>{{ $order->supplier->name }}</td></tr>
            <tr><th>Cost center</th><td>{{ $order->cost_center }}@if ($order->project_element), project element {{ $order->project_element }}@endif</td></tr>
            <tr><th>Suggested scope</th><td><strong>{{ $pd($proposal->suggestedPd) }} PD</strong> <span class="muted small">({{ $pd($forecast->missingPd) }} PD missing, rounded up)</span></td></tr>
            <tr><th>Period</th><td>{{ $day($proposal->startsOn) }} to {{ $day($proposal->endsOn) }}</td></tr>
            <tr>
                <th>Place the order by</th>
                <td>
                    {{ $day($proposal->orderByDate) }}, {{ $proposal->leadWeeks }} weeks lead time
                    @if ($late)
                        <span class="light light-red">Passed, order now</span>
                    @endif
                </td>
            </tr>
            <tr><th>Budget runs out</th><td>{{ $day($forecast->exhaustionDate) }}</td></tr>
        </table>
    </div>

    <div class="card">
        <h2>Draft text</h2>
        <pre class="draft">{{ $draft }}</pre>
    </div>

    <details class="small">
        <summary>Assumptions</summary>
        <ul>
            @foreach ($forecast->assumptions as $assumption)
                <li>{{ $assumption }}</li>
            @endforeach
            <li>The new order starts on the first working day after the budget is used up and ends with the current order's period. Move the end if the project is extended.</li>
        </ul>
    </details>

    <p><a class="cancel" href="{{ route('follow-ups.index') }}">Back to the follow-up orders</a></p>
@endsection
