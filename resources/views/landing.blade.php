@extends('layouts.app')

@section('title', 'Pegal · Know your budget before the invoice does')

@section('content')
    <section class="hero">
        <div class="hero-text">
            <p class="eyebrow">Time booking and budget forecast</p>
            <h1>Know how much budget is left before the invoice tells you.</h1>
            <p class="lead">
                External consultants book their hours, and every hour shows up on the order budget right away.
                Pegal warns you when the budget will run out and tells you the latest date to place a follow-up order.
            </p>
            <a class="button" href="{{ route('login') }}">Sign in</a>
        </div>

        {{-- a sample of the gauge the project lead sees, made-up numbers, just to show the idea --}}
        <div class="card hero-gauge" aria-label="Example order budget">
            <p class="small muted">Example order</p>
            <h2>Customer portal migration</h2>

            <div class="gauge gauge-warning gauge-big" role="img" aria-label="72 percent of the budget used">
                <div class="gauge-fill" style="width: 72%"></div>
            </div>

            <dl class="gauge-facts">
                <div><dt>Ordered</dt><dd>60 PD</dd></div>
                <div><dt>Used</dt><dd>43.2 PD</dd></div>
                <div><dt>Left</dt><dd>16.8 PD</dd></div>
            </dl>

            <p><span class="light light-amber">Reorder soon</span></p>
            <p class="small muted">Budget runs out around 4 Dec. Latest reorder date: 6 Nov (purchasing needs about 4 weeks).</p>
        </div>
    </section>

    <section class="landing-section">
        <h2>How it works</h2>
        <ol class="steps">
            <li>
                <strong>Book</strong>
                <span class="muted">The consultant books date, hours and a short note on an order assigned to them.</span>
            </li>
            <li>
                <strong>Check</strong>
                <span class="muted">The server checks remaining budget, the daily hour limit and the order period, and says clearly what is wrong.</span>
            </li>
            <li>
                <strong>See</strong>
                <span class="muted">The order's used and remaining person-days update straight away, with a traffic light and a forecast.</span>
            </li>
        </ol>
    </section>

    <section class="landing-section">
        <h2>One view for each role</h2>
        <div class="role-grid">
            <div class="card">
                <h3>Project lead</h3>
                <p class="muted">Used and remaining budget per order, and a warning with a reorder date before it runs out.</p>
            </div>
            <div class="card">
                <h3>Purchasing</h3>
                <p class="muted">Which orders need a follow-up order soon, most urgent first, with a draft ready to copy.</p>
            </div>
            <div class="card">
                <h3>Finance</h3>
                <p class="muted">Monthly proof of work per order to check against the invoice, no spreadsheets.</p>
            </div>
            <div class="card">
                <h3>Consultant</h3>
                <p class="muted">Book working time on an assigned order in a few clicks.</p>
            </div>
        </div>
    </section>

    <section class="card privacy-note">
        <h2>Privacy by design</h2>
        <p class="muted">
            The client sees person-days per order per week, not what an individual did on each day.
            Every permission is checked on the server, not just hidden in the screen.
        </p>
    </section>
@endsection
