@extends('layouts.app')

@section('title', 'Orders · Pegal')

@section('content')
    <h1>Orders and budget</h1>
    <p class="muted">Person-days (PD) ordered, used and still free. One PD is 8 hours; rejected bookings do not count.</p>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Supplier</th>
                    <th>Period</th>
                    <th class="num">Ordered</th>
                    <th class="num">Used</th>
                    <th class="num">Left</th>
                    <th>Budget used</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    @php($budget = $row['budget'])
                    <tr>
                        <td>
                            <strong>{{ $row['order']->number }}</strong><br>
                            <span class="muted small">{{ $row['order']->title }}</span>
                        </td>
                        <td>{{ $row['order']->supplier->name }}</td>
                        <td class="small">{{ $row['order']->starts_on->format('j M Y') }}<br>to {{ $row['order']->ends_on->format('j M Y') }}</td>
                        <td class="num">{{ rtrim(rtrim(number_format($budget->orderedPd, 2), '0'), '.') }}</td>
                        <td class="num">{{ rtrim(rtrim(number_format($budget->usedPd, 2), '0'), '.') }}</td>
                        <td class="num">{{ rtrim(rtrim(number_format($budget->remainingPd(), 2), '0'), '.') }}</td>
                        <td>
                            {{-- the words are there too, colour alone is not enough for everybody --}}
                            <div class="gauge gauge-{{ $budget->level() }}" role="img"
                                 aria-label="{{ $budget->percentUsed() }} percent of the budget used">
                                <div class="gauge-fill" style="width: {{ $budget->percentUsed() }}%"></div>
                            </div>
                            <span class="small">{{ $budget->percentUsed() }}% used</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
