@extends('layouts.app')
@section('title', 'Profile Settings')

@section('content')
<div class="space-y-5" style="max-width:36rem;">

    <h1 class="text-2xl font-bold" style="color:#0f172a;">⚙️ Profile Settings</h1>

    <div class="glass p-5">
        @include('profile.partials.update-profile-information-form')
    </div>

    <div class="glass p-5">
        @include('profile.partials.update-password-form')
    </div>

    <div class="glass p-5">
        @include('profile.partials.delete-user-form')
    </div>

</div>
@endsection
