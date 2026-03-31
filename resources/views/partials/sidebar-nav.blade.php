<div class="nav nav-pills flex-column gap-2">
    <a class="nav-link @if(request()->routeIs('dashboard')) active @endif" href="{{ route('dashboard') }}">
        <i class="bi bi-house-door me-2"></i>לוח בקרה
    </a>
    <a class="nav-link @if(request()->routeIs('leads.my')) active @endif" href="{{ route('leads.my') }}">
        <i class="bi bi-tags me-2"></i>הלידים שלי
    </a>
    <a class="nav-link @if(request()->routeIs('customers.*')) active @endif" href="{{ route('customers.index') }}">
        <i class="bi bi-people me-2"></i>לקוחות
    </a>

    @if (auth()->user()->isAdmin())
        <hr>
        <div class="text-muted small fw-semibold mb-1">ניהול</div>
        <a class="nav-link @if(request()->routeIs('admin.leads.create')) active @endif" href="{{ route('admin.leads.create') }}">
            <i class="bi bi-plus-square me-2"></i>ליד חדש
        </a>
        <a class="nav-link @if(request()->routeIs('admin.leads.index')) active @endif" href="{{ route('admin.leads.index') }}">
            <i class="bi bi-list-ul me-2"></i>כל הלידים
        </a>
        <a class="nav-link @if(request()->routeIs('admin.analytics.*')) active @endif" href="{{ route('admin.analytics.index') }}">
            <i class="bi bi-graph-up-arrow me-2"></i>אנליטיקה ניהולית
        </a>
        <a class="nav-link @if(request()->routeIs('admin.integrations.*')) active @endif" href="{{ route('admin.integrations.index') }}">
            <i class="bi bi-plug me-2"></i>אינטגרציות ולוגים
        </a>
        <a class="nav-link @if(request()->routeIs('admin.users.*')) active @endif" href="{{ route('admin.users.index') }}">
            <i class="bi bi-person-gear me-2"></i>משתמשי המערכת
        </a>
    @endif
</div>
