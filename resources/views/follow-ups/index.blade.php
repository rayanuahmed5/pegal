@extends('layouts.app')

@section('title', 'Follow-up orders · Pegal')

@section('content')
    <h1>Follow-up orders</h1>
    <p class="muted">
        Orders whose latest reorder date has passed or falls within the next {{ \App\Domain\FollowUp::WITHIN_WEEKS }} weeks,
        most urgent first.
    </p>

    @error('proposal')
        <p class="alert" role="alert">{{ $message }}</p>
    @enderror

    <div class="card">
        @if ($rows->isEmpty())
            <p>No order needs a follow-up order in the next {{ \App\Domain\FollowUp::WITHIN_WEEKS }} weeks.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Supplier</th>
                        <th class="num">Left (PD)</th>
                        <th>Runs out</th>
                        <th>Reorder by</th>
                        <th>When</th>
                        <th>Proposal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td>
                                <strong>{{ $row['order']->number }}</strong><br>
                                <span class="muted small">{{ $row['order']->title }}</span>
                            </td>
                            <td>{{ $row['order']->supplier->name }}</td>
                            <td class="num">{{ rtrim(rtrim(number_format($row['budget']->remainingPd(), 2), '0'), '.') }}</td>
                            <td>{{ $row['forecast']->exhaustionDate->format('j M Y') }}<br><span class="muted small">week {{ $row['forecast']->exhaustionWeek() }}</span></td>
                            <td>{{ $row['forecast']->reorderDate->format('j M Y') }}<br><span class="muted small">week {{ $row['forecast']->reorderWeek() }}</span></td>
                            <td>
                                {{-- words first, the colour is only a hint --}}
                                <span class="light {{ $row['status'] === 'overdue' ? 'light-red' : 'light-amber' }}">
                                    @if ($row['days'] < 0)
                                        Overdue by {{ abs($row['days']) }} {{ \Illuminate\Support\Str::plural('day', abs($row['days'])) }}
                                    @elseif ($row['days'] === 0)
                                        Today
                                    @else
                                        In {{ $row['days'] }} {{ \Illuminate\Support\Str::plural('day', $row['days']) }}
                                    @endif
                                </span>
                            </td>
                            <td><a href="{{ route('follow-ups.proposal', $row['order']) }}">Draft</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
