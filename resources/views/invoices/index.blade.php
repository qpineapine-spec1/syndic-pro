@extends('layouts.app')

@section('title', 'Factures')

@section('content')
    <section class="page-header">
        <div>
            <h1 class="page-title">Factures</h1>
        </div>
    </section>

    <section class="dashboard-grid">
        @foreach ($invoices as $invoice)
            <article class="dashboard-card card-glass">
                <span>{{ $invoice->invoice_number }}</span>
                <small>Montant: {{ number_format($invoice->amount, 2) }} €</small>
                <small>Statut: {{ $invoice->status }}</small>
                <small>Échéance: {{ optional($invoice->due_date)->toDateString() }}</small>
            </article>
        @endforeach
    </section>
@endsection
