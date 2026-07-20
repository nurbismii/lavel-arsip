@extends('layouts.app')
@section('content')
<div class="container">
    <form method="POST" action="{{ route('jobdesc.store') }}" enctype="multipart/form-data" data-loading-form>@csrf @include('jobdesc._form')</form>
</div>
@endsection