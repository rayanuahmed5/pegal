@extends('layouts.app')

@section('title', 'Pegel · Know your budget before the invoice does')
@section('main_class', 'landing')

@section('content')
    <section class="band band-hero">
        <div class="band-inner hero">
            <div class="hero-text">
                <p class="eyebrow">Time booking and budget forecast</p>
                <h1>Know how much budget is left before the invoice tells you.</h1>
                <p class="lead">
                    External consultants book their hours, and every hour shows up on the order budget right away.
                    Pegel warns you when the budget will run out and tells you the latest date to reorder.
                </p>
                <p class="hero-actions">
                    <a class="button" href="{{ route('login') }}">Sign in <x-icon name="arrow" /></a>
                    <a class="button button-ghost" href="#how">How it works</a>
                </p>
            </div>

            {{-- a sample of the gauge the project lead sees, made-up numbers, just to show the idea --}}
            <div class="hero-gauge" aria-label="Example order budget">
                <div class="hero-gauge-head">
                    <span class="small muted">Example order</span>
                    <span class="light light-amber">Reorder soon</span>
                </div>
                <h2>Customer portal migration</h2>

                <div class="gauge gauge-warning gauge-big" role="img" aria-label="72 percent of the budget used">
                    <div class="gauge-fill" style="width: 72%"></div>
                </div>

                <dl class="gauge-facts">
                    <div><dt>Ordered</dt><dd>60 PD</dd></div>
                    <div><dt>Used</dt><dd>43.2 PD</dd></div>
                    <div><dt>Left</dt><dd>16.8 PD</dd></div>
                </dl>

                <p class="hero-gauge-foot">
                    Runs out around <strong>4 Dec</strong> · reorder by <strong>6 Nov</strong>
                </p>
            </div>
        </div>
    </section>

    <section class="band" id="how">
        <div class="band-inner">
            <h2 class="band-title">How it works</h2>
            <p class="band-sub muted">Three steps, from a booked hour to an honest forecast.</p>

            <ol class="steps">
                <li class="tile">
                    <span class="tile-icon"><x-icon name="clock" /></span>
                    <h3>1. Book</h3>
                    <p class="muted">The consultant books date, hours and a short note on an order assigned to them.</p>
                </li>
                <li class="tile">
                    <span class="tile-icon"><x-icon name="check" /></span>
                    <h3>2. Check</h3>
                    <p class="muted">The server checks remaining budget, the daily hour limit and the order period, and says clearly what is wrong.</p>
                </li>
                <li class="tile">
                    <span class="tile-icon"><x-icon name="gauge" /></span>
                    <h3>3. See</h3>
                    <p class="muted">Used and remaining person-days update straight away, with a traffic light and a forecast.</p>
                </li>
            </ol>
        </div>
    </section>

    <section class="band band-soft">
        <div class="band-inner">
            <h2 class="band-title">One view for each role</h2>
            <p class="band-sub muted">Everybody sees what they need, and nothing more.</p>

            <div class="role-grid">
                <div class="tile">
                    <span class="tile-icon"><x-icon name="gauge" /></span>
                    <h3>Project lead</h3>
                    <p class="muted">Used and remaining budget per order, and a warning with a reorder date.</p>
                </div>
                <div class="tile">
                    <span class="tile-icon"><x-icon name="cart" /></span>
                    <h3>Purchasing</h3>
                    <p class="muted">Which orders need a follow-up order soon, with a draft ready to copy.</p>
                </div>
                <div class="tile">
                    <span class="tile-icon"><x-icon name="file" /></span>
                    <h3>Finance</h3>
                    <p class="muted">Monthly proof of work per order, to check against the invoice.</p>
                </div>
                <div class="tile">
                    <span class="tile-icon"><x-icon name="clock" /></span>
                    <h3>Consultant</h3>
                    <p class="muted">Book working time on an assigned order in a few clicks.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="band">
        <div class="band-inner privacy">
            <span class="tile-icon tile-icon-big"><x-icon name="shield" /></span>
            <div>
                <h2>Privacy by design</h2>
                <p class="muted">
                    The client sees person-days per order per week, not what an individual did on each day.
                    Every permission is checked on the server, not just hidden on the screen.
                </p>
            </div>
            <a class="button" href="{{ route('login') }}">Sign in</a>
        </div>
    </section>

    <footer class="landing-footer">
        <div class="band-inner">Pegel · built at the Article26 Hackathon 2026</div>
    </footer>
@endsection
