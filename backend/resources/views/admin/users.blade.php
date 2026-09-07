@extends('layouts.admin')
@section('title','Users')
@section('content')
<div class="page-head"><div><span class="eyebrow">IDENTITY</span><h1>Users</h1><p class="muted">Search customer and administrator accounts across the platform.</p></div></div>
<section class="panel"><form method="get" class="inline-form" style="grid-template-columns:1fr auto"><input name="q" value="{{ $search }}" placeholder="Search name or email"><button class="button">Search</button></form></section>
<section class="panel"><div class="panel-head"><h2>User directory</h2><span class="pill">{{ $users->total() }} accounts</span></div><div class="table-wrap"><table><thead><tr><th>User</th><th>Email</th><th>Workspaces</th><th>Super admin</th><th>Created</th></tr></thead><tbody>@forelse($users as $user)<tr><td><strong>{{ $user->name }}</strong></td><td>{{ $user->email }}</td><td>{{ $user->memberships_count }}</td><td>{{ $user->is_admin ? 'Yes' : 'No' }}</td><td>{{ $user->created_at?->format('M j, Y H:i') }}</td></tr>@empty<tr><td colspan="5">No users found.</td></tr>@endforelse</tbody></table></div><div style="margin-top:18px">{{ $users->links() }}</div></section>
@endsection