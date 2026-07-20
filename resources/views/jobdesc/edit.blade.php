@extends('layouts.app')
@section('content')
<div class="container">
    <form method="POST" action="{{ route('jobdesc.update', $jobdesc) }}" enctype="multipart/form-data" data-loading-form>@csrf @method('PUT') @include('jobdesc._form')</form>
</div>
@endsection