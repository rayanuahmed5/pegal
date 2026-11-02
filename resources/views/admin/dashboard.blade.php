@extends('layouts.app')

@section('title', 'Administration · Pegal')

@section('content')
    @php($pd = fn (float $number) => rtrim(rtrim(number_format($number, 2), '0'), '.'))

    <h1>Administration</h1>
    <p class="muted">Everybody who can sign in to Pegal, and what each person is assigned and has booked.</p>

    @if (session('status'))
        <p class="flash">{{ session('status') }}</p>
    @endif

    <div class="stats">
        <div class="card stat">
            <div class="stat-number">{{ $total }}</div>
            <div class="muted small">Users in total</div>
        </div>
        @foreach (\App\Domain\Role::cases() as $role)
            <div class="card stat">
                <div class="stat-number">{{ $byRole[$role->value] }}</div>
                <div class="muted small">{{ $role->label() }}</div>
            </div>
        @endforeach
    </div>

    <p><a class="button" href="{{ route('admin.users.create') }}">Add user</a></p>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Supplier</th>
                    <th class="num">Assigned orders</th>
                    <th class="num">Assigned (PD)</th>
                    <th class="num">Spent (PD)</th>
                    <th><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>
                            <strong>{{ $row['user']->name }}</strong><br>
                            <span class="muted small">{{ $row['user']->email }}</span>
                        </td>
                        <td>{{ $row['user']->role->label() }}</td>
                        <td>{{ $row['user']->supplier?->name ?? '–' }}</td>
                        <td class="num">{{ $row['assignedOrders'] }}</td>
                        <td class="num">{{ $pd($row['assignedPd']) }}</td>
                        <td class="num">{{ $pd($row['spentPd']) }}</td>
                        <td class="num">
                            <a class="icon-link" href="{{ route('admin.users.edit', $row['user']) }}">
                                <x-icon name="pencil" /> Edit
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="muted small">
            Pegal counts in person-days (PD), one PD is 8 hours, there are no prices in the system.
            <strong>Assigned</strong> is the budget of all orders a person works on; orders are shared with colleagues, so do not add this column up.
            <strong>Spent</strong> is what the person booked, rejected bookings do not count.
        </p>
    </div>
@endsection
