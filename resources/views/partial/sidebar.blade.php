@php
  $route = Route::currentRouteName();
@endphp
<nav class="sidebar sidebar-offcanvas" id="sidebar">
  <ul class="nav">
    <li @class(['nav-item', 'active' => str_contains($route,'dashboard')])>
      <a class="nav-link" href={{route('dashboard')}}>
        <i class="ti-dashboard menu-icon"></i>
        <span class="menu-title">Dashboard</span>
      </a>
    </li>
    
    <li @class(['nav-item', 'active' => str_contains($route,'mailing')])>
      <a class="nav-link" href={{route('mailing')}}>
        <i class="ti-write menu-icon"></i>
        <span class="menu-title">Mailing</span>
      </a>
    </li>
  </ul>
</nav>