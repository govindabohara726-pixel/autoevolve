<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title','Dashboard') · AutoEvolve</title>
<link rel="stylesheet" href="{{ asset('saas.css') }}">
</head>
<body>
<div class="app-shell">
<aside class="app-sidebar">
<a class="brand" href="{{ route('app.dashboard') }}"><span class="brandmark">↗</span>AutoEvolve</a>
<div class="workspace-chip"><strong>{{ $workspace->name ?? 'Workspace' }}</strong><span>{{ ucfirst($workspace->plan ?? 'starter') }} · {{ ucfirst(request()->attributes->get('membership')?->role ?? 'member') }}</span></div>
<nav class="app-nav">
<a class="{{ request()->routeIs('app.dashboard')?'active':'' }}" href="{{ route('app.dashboard') }}">Overview</a>
<a class="{{ request()->routeIs('app.sites*')?'active':'' }}" href="{{ route('app.sites') }}">Sites</a>
<a class="{{ request()->routeIs('app.content*')?'active':'' }}" href="{{ route('app.content.index') }}">Content</a>
<a class="{{ request()->routeIs('app.automation*')?'active':'' }}" href="{{ route('app.automation') }}">Automation</a>
<a class="{{ request()->routeIs('app.opportunities*')?'active':'' }}" href="{{ route('app.opportunities') }}">Opportunities</a>
<a class="{{ request()->routeIs('app.activity')?'active':'' }}" href="{{ route('app.activity') }}">Activity</a>
<a class="{{ request()->routeIs('app.billing*')?'active':'' }}" href="{{ route('app.billing') }}">Billing</a>
<a class="{{ request()->routeIs('app.settings*')?'active':'' }}" href="{{ route('app.settings') }}">Settings</a>
</nav>
<div class="app-sidebar-foot">
@if(isset($site) && $site)<div>Active site: <strong>{{ $site->name }}</strong></div>@endif
<a href="{{ config('services.frontend_url') ?: url('/') }}" target="_blank">Open public site ↗</a>
@if(auth()->user()?->is_admin)<a href="{{ route('admin.dashboard') }}">Super admin</a>@endif
<form method="post" action="{{ route('logout') }}">@csrf<button class="btn ghost small" type="submit">Sign out</button></form>
</div>
</aside>
<main class="app-main"><div class="app-container">
@if(session('success'))<div class="flash success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="flash error">{{ $errors->first() }}</div>@endif
@yield('content')
</div></main>
</div>
</body>
</html>
