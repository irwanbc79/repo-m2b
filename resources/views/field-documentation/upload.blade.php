@extends('layouts.admin')

@section('header', '📷 Upload Foto')

@section('content')
<div class="max-w-3xl mx-auto">
    @livewire('field-photo-upload', ['shipment' => $shipmentNumber ?? null])
</div>
@endsection
