@extends('layouts.app', ['title' => 'User Data'])

@section('content')
<div class="mb-6">
    <p class="text-sm font-bold uppercase tracking-[0.2em] text-teal-700">Sinkron Database Ujian</p>
    <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">User Role Data</h1>
</div>
<div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-100/80"><tr><th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">ID</th><th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Nama</th><th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Email</th><th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Role</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @foreach($users as $user)
                <tr><td class="px-4 py-4 font-black">{{ $user->id }}</td><td class="px-4 py-4 font-semibold">{{ $user->name }}</td><td class="px-4 py-4 text-slate-600">{{ $user->email }}</td><td class="px-4 py-4"><span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-black text-teal-700">{{ $user->role }}</span></td></tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
<div class="mt-5">{{ $users->links() }}</div>
@endsection
