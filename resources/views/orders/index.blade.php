@extends('layouts.app')

@section('title', 'Orders · Pegel')

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
                    @if ($showForecast)
                        <th>Forecast</th>
                    @endif
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
                        @if ($showForecast)
                            @php($forecast = $row['forecast'])
                            <td>
                                <span class="light light-{{ $forecast->light }}">
                                    {{ ['green' => 'On track', 'amber' => 'Plan a follow-up', 'red' => 'Reorder now'][$forecast->light] }}
                                </span>
                                <div class="small">
                                    @if ($forecast->lastsUntilEnd())
                                        Budget lasts until the order ends.
                                    @else
                                        Runs out {{ $forecast->exhaustionDate->format('j M Y') }} (week {{ $forecast->exhaustionWeek() }})<br>
                                        Reorder by {{ $forecast->reorderDate->format('j M Y') }} (week {{ $forecast->reorderWeek() }})
                                        @if ($forecast->missingPd > 0)
                                            <br>About {{ rtrim(rtrim(number_format($forecast->missingPd, 1), '0'), '.') }} PD missing until the order ends
                                        @endif
                                    @endif
                                    @if ($forecast->hasRange())
                                        <br>Fast pace: {{ $forecast->earliestExhaustionDate ? 'runs out '.$forecast->earliestExhaustionDate->format('j M Y').' (week '.$forecast->earliestWeek().')' : 'lasts until the order ends' }}
                                        <br>Slow pace: {{ $forecast->latestExhaustionDate ? 'runs out '.$forecast->latestExhaustionDate->format('j M Y').' (week '.$forecast->latestWeek().')' : 'lasts until the order ends' }}
                                    @endif
                                </div>
                                <details class="small">
                                    <summary>Assumptions</summary>
                                    <ul>
                                        @foreach ($forecast->assumptions as $assumption)
                                            <li>{{ $assumption }}</li>
                                        @endforeach
                                    </ul>
                                </details>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
