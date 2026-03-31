@extends('layouts.crm')

@section('pageTitle', 'לקוחות')
@section('pageSubtitle', 'ניהול כל הלקוחות השמורים בבסיס הנתונים')

@section('pageActions')
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCustomerModal">הוספת לקוח</button>
@endsection

@section('content')
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive d-none d-lg-block">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>שם</th>
                            <th>חברה</th>
                            <th>דוא"ל</th>
                            <th>טלפון</th>
                            <th>אחראי</th>
                            <th>ליד</th>
                            <th class="text-end">פעולות</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($customers as $customer)
                            <tr>
                                <td>{{ $customer->id }}</td>
                                <td>{{ $customer->full_name }}</td>
                                <td>{{ $customer->company ?: 'ללא חברה' }}</td>
                                <td>{{ $customer->email }}</td>
                                <td>{{ $customer->phone ?: 'ללא טלפון' }}</td>
                                <td>{{ $customer->owner?->name ?: 'ללא שיוך' }}</td>
                                <td>{{ $customer->lead_id ?: 'ידני' }}</td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2">
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editCustomerModal{{ $customer->id }}">עריכה</button>
                                        <form method="POST" action="{{ route('customers.destroy', $customer) }}" onsubmit="return confirm('למחוק את הלקוח הזה?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" type="submit">מחיקה</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            <div class="modal fade" id="editCustomerModal{{ $customer->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('customers.update', $customer) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-header">
                                                <h5 class="modal-title">עריכת לקוח</h5>
                                                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                @include('partials.customer-form-fields', ['customer' => $customer, 'users' => $users, 'prefix' => 'edit_customer_' . $customer->id])
                                            </div>
                                            <div class="modal-footer">
                                                <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">ביטול</button>
                                                <button class="btn btn-primary" type="submit">שמירת שינויים</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">לא נמצאו לקוחות.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-grid gap-3 d-lg-none">
                @forelse ($customers as $customer)
                    <div class="card mobile-record-card">
                        <div class="card-body d-grid gap-3">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <div>
                                    <div class="fw-semibold">{{ $customer->full_name }}</div>
                                    <div class="mobile-record-meta">{{ $customer->company ?: 'ללא חברה' }}</div>
                                </div>
                                <div class="small text-muted text-start">#{{ $customer->id }}</div>
                            </div>

                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="small text-muted mb-1">דוא"ל</div>
                                    <div>{{ $customer->email }}</div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="small text-muted mb-1">טלפון</div>
                                    <div>{{ $customer->phone ?: 'ללא טלפון' }}</div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="small text-muted mb-1">אחראי</div>
                                    <div>{{ $customer->owner?->name ?: 'ללא שיוך' }}</div>
                                </div>
                                <div class="col-12">
                                    <div class="small text-muted mb-1">מקור ליד</div>
                                    <div>{{ $customer->lead_id ?: 'ידני' }}</div>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editCustomerModal{{ $customer->id }}">עריכה</button>
                                <form method="POST" action="{{ route('customers.destroy', $customer) }}" onsubmit="return confirm('למחוק את הלקוח הזה?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger w-100" type="submit">מחיקה</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center text-muted py-4">לא נמצאו לקוחות.</div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="modal fade" id="createCustomerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" action="{{ route('customers.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">הוספת לקוח</h5>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @include('partials.customer-form-fields', ['customer' => null, 'users' => $users, 'prefix' => 'create_customer'])
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">ביטול</button>
                        <button class="btn btn-primary" type="submit">יצירת לקוח</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
