<x-app-layout>
    <x-slot name="title">Profile</x-slot>
    <div class="max-w-3xl mx-auto space-y-6">
        <h1 class="page-title">Profile</h1>
        <div class="card p-6">@include('profile.partials.update-profile-information-form')</div>
        <div class="card p-6">@include('profile.partials.update-password-form')</div>
        <div class="card p-6">@include('profile.partials.delete-user-form')</div>
    </div>
</x-app-layout>
