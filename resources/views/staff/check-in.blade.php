@extends('layouts.staff', ['title' => 'Check-in'])

@section('content')
    <section class="hero-panel">
        <div>
            <span class="eyebrow">Member access</span>
            <h1 class="page-title">Member Check-in</h1>
            <p class="page-subtitle">Use a member ID, email, or full name, then choose whether you want to check the member in or check them out.</p>
        </div>
    </section>

    @if (session('staff_notice'))
        <div class="alert success">{{ session('staff_notice') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert danger">{{ $errors->first() }}</div>
    @endif

    @if ($activeMembers->count() > 0)
        <section class="panel-card">
            <div class="panel-head">
                <div>
                    <h2><span class="heading-icon">@include('staff.partials.icon', ['name' => 'users'])</span>Active Members in Gym</h2>
                    <p>Click on a member badge to quickly check them out.</p>
                </div>
            </div>

            <div class="active-members-grid">
                @foreach ($activeMembers as $member)
                    <div class="member-badge" data-attendance-id="{{ $member->attendance_id }}" data-member-id="{{ $member->member_id }}" data-member-name="{{ $member->full_name }}">
                        <div class="badge-content">
                            <div class="badge-avatar">{{ strtoupper(substr($member->full_name, 0, 1)) }}</div>
                            <div class="badge-info">
                                <div class="badge-id">#{{ $member->member_id }}</div>
                                <div class="badge-name">{{ $member->full_name }}</div>
                                <div class="badge-time">{{ $member->check_in_time->format('h:i A') }}</div>
                            </div>
                        </div>
                        <div class="badge-action">Check Out</div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="content-grid checkin-layout">
        <article class="panel-card checkin-card">
            <div class="checkin-illustration">
                <div class="avatar avatar-large">+</div>
            </div>

            <form id="checkin-form" class="checkin-form">
                @csrf
                <label class="field-label" for="member_lookup">Select Member</label>
                <select id="member_lookup" name="member_lookup" class="staff-input" required>
                    <option value="">-- Choose a member --</option>
                    @foreach ($allMembers as $member)
                        <option value="{{ $member->member_id }}" @selected(old('member_lookup') == $member->member_id)>
                            #{{ $member->member_id }} - {{ $member->full_name }} ({{ $member->membership_type }})
                        </option>
                    @endforeach
                </select>

                <label class="field-label" for="class_id">Attach to session</label>
                <select id="class_id" name="class_id" class="staff-input">
                    <option value="">Nearest active schedule</option>
                    @foreach ($todaysClassOptions as $classOption)
                        <option value="{{ $classOption->class_id }}" @selected(old('class_id') == $classOption->class_id)>
                            {{ $classOption->class_name }} - {{ $classOption->schedule_time->format('h:i A') }}
                        </option>
                    @endforeach
                </select>

                <div class="helper-text">Current system attendance is tied to a class session, so walk-in visits are attached to the closest schedule unless you choose one manually.</div>

                <div class="action-row">
                    <button type="button" id="btn-check-in" class="btn-primary" disabled>Check-in</button>
                    <button type="button" id="btn-check-out" class="btn-secondary" disabled>Check-out</button>
                </div>
            </form>
        </article>

        <article class="panel-card" data-member-preview-container>
            <div class="panel-head">
                <div>
                    <h2><span class="heading-icon">@include('staff.partials.icon', ['name' => 'status'])</span>Live snapshot</h2>
                    <p>Shift totals and member preview.</p>
                </div>
            </div>

            <div class="mini-stat-grid">
                <div class="mini-stat">
                    <span>Total check-ins</span>
                    <strong>{{ $stats['total'] }}</strong>
                </div>
                <div class="mini-stat">
                    <span>Currently in</span>
                    <strong>{{ $stats['currently_in'] }}</strong>
                </div>
                <div class="mini-stat">
                    <span>Checked out</span>
                    <strong>{{ $stats['checked_out'] }}</strong>
                </div>
            </div>

            @if ($memberPreview)
                <div class="member-preview">
                    <div class="avatar">{{ strtoupper(substr($memberPreview->user->full_name ?? 'M', 0, 1)) }}</div>
                    <div>
                        <strong>{{ $memberPreview->user->full_name }}</strong>
                        <span>#{{ $memberPreview->member_id }} - {{ $memberPreview->membership_type }}</span>
                        <span>{{ $memberPreview->user->email }}</span>
                        <span>{{ $memberPreviewAttendance ? 'Currently checked in' : 'Currently checked out' }}</span>
                    </div>
                    <span class="badge {{ $memberPreviewAttendance ? 'success' : 'inactive' }}">{{ $memberPreviewAttendance ? 'In Gym' : 'Checked Out' }}</span>
                </div>
            @else
                <div class="empty-state">Search for a member above to show their profile preview here.</div>
            @endif
        </article>
    </section>

    <section class="panel-card">
        <div class="panel-head">
            <div>
                <h2><span class="heading-icon">@include('staff.partials.icon', ['name' => 'clock'])</span>Recent check-ins</h2>
                <p>Most recent floor entries recorded today.</p>
            </div>
        </div>

        <div class="table-wrap">
            <table class="staff-table" id="recent-checkins-table">
                <thead>
                <tr>
                    <th>Member</th>
                    <th>Class</th>
                    <th>Checked In</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody id="recent-checkins-tbody">
                @forelse ($recentCheckIns as $attendance)
                    <tr>
                        <td>{{ $attendance->member_name ?? 'Unknown member' }}</td>
                        <td>{{ $attendance->class_name ?? 'No class' }}</td>
                        <td>{{ $attendance->check_in_time->format('h:i A') }}</td>
                        <td><span class="badge {{ $attendance->check_out_time ? 'inactive' : 'success' }}">{{ $attendance->check_out_time ? 'Checked Out' : 'In Gym' }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty-cell">No activity yet today.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <style>
        .active-members-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 12px;
            padding: 16px 0;
        }

        .member-badge {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background: var(--color-surface);
            border: 2px solid var(--color-border);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .member-badge:hover {
            background: var(--color-primary);
            border-color: var(--color-primary);
            color: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }

        .member-badge:active {
            transform: translateY(0);
        }

        .badge-content {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
        }

        .badge-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--color-primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
        }

        .member-badge:hover .badge-avatar {
            background: rgba(255, 255, 255, 0.2);
        }

        .badge-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .badge-id {
            font-weight: 600;
            font-size: 14px;
        }

        .badge-name {
            font-size: 13px;
            opacity: 0.8;
        }

        .badge-time {
            font-size: 12px;
            opacity: 0.6;
        }

        .badge-action {
            font-size: 12px;
            font-weight: 600;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .member-badge:hover .badge-action {
            opacity: 1;
        }

        .member-badge.checking-out {
            opacity: 0.5;
            pointer-events: none;
        }

        .member-badge.checked-out {
            opacity: 0.3;
            pointer-events: none;
            background: var(--color-success);
            border-color: var(--color-success);
        }
    </style>

    <script>
        const memberSelect = document.getElementById('member_lookup');
        const classSelect = document.getElementById('class_id');
        const btnCheckIn = document.getElementById('btn-check-in');
        const btnCheckOut = document.getElementById('btn-check-out');
        const form = document.getElementById('checkin-form');

        // Handle member dropdown change - load preview without reloading page
        memberSelect.addEventListener('change', async function() {
            if (this.value) {
                // Enable buttons when a member is selected
                btnCheckIn.disabled = false;
                btnCheckOut.disabled = false;
                
                // Load member preview via AJAX
                try {
                    const url = new URL(window.location);
                    url.searchParams.set('lookup', this.value);
                    
                    const response = await fetch(url.toString());
                    const html = await response.text();
                    
                    // Parse the HTML to extract member preview
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newPreview = doc.querySelector('.member-preview');
                    const newEmptyState = doc.querySelector('[data-member-preview-container] .empty-state');
                    
                    // Update the preview container
                    const previewContainer = document.querySelector('[data-member-preview-container]');
                    const oldPreview = previewContainer.querySelector('.member-preview');
                    const oldEmpty = previewContainer.querySelector('.empty-state');
                    
                    if (newPreview) {
                        if (oldPreview) oldPreview.remove();
                        if (oldEmpty) oldEmpty.remove();
                        previewContainer.appendChild(newPreview);
                    }
                    
                    // Update stats
                    const statNumbers = doc.querySelectorAll('.mini-stat strong');
                    const currentStats = document.querySelectorAll('.mini-stat strong');
                    statNumbers.forEach((stat, index) => {
                        if (currentStats[index]) {
                            currentStats[index].textContent = stat.textContent;
                        }
                    });
                } catch (error) {
                    console.error('Error loading member preview:', error);
                }
            } else {
                // Disable buttons when no member is selected
                btnCheckIn.disabled = true;
                btnCheckOut.disabled = true;
            }
        });

        // Handle Check-in button click
        btnCheckIn.addEventListener('click', async function() {
            await handleCheckinAction('check_in');
        });

        // Handle Check-out button click
        btnCheckOut.addEventListener('click', async function() {
            await handleCheckinAction('check_out');
        });

        // Unified function to handle check-in/out actions
        async function handleCheckinAction(action) {
            const memberId = memberSelect.value;
            const classId = classSelect.value;

            if (!memberId) {
                alert('Please select a member first');
                return;
            }

            const button = action === 'check_in' ? btnCheckIn : btnCheckOut;
            button.disabled = true;

            try {
                const response = await fetch('{{ route("staff.checkin.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-Token': document.querySelector('input[name="_token"]').value
                    },
                    body: new URLSearchParams({
                        member_lookup: memberId,
                        class_id: classId,
                        attendance_action: action
                    })
                });

                const text = await response.text();
                
                // Check if response is successful (200-299 status)
                if (response.ok) {
                    // Show success message
                    const successMsg = action === 'check_in' ? 'Member checked in successfully!' : 'Member checked out successfully!';
                    const alert = document.createElement('div');
                    alert.className = 'alert success';
                    alert.textContent = successMsg;
                    form.insertAdjacentElement('beforebegin', alert);

                    // Fetch updated data without reloading
                    try {
                        const updateResponse = await fetch(window.location.href);
                        const updateHtml = await updateResponse.text();
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(updateHtml, 'text/html');
                        
                        // Update recent checkins table
                        const newTbody = doc.querySelector('#recent-checkins-tbody');
                        const oldTbody = document.querySelector('#recent-checkins-tbody');
                        if (newTbody && oldTbody) {
                            oldTbody.innerHTML = newTbody.innerHTML;
                        }
                        
                        // Update stats
                        const newStats = doc.querySelectorAll('.mini-stat strong');
                        const oldStats = document.querySelectorAll('.mini-stat strong');
                        newStats.forEach((stat, index) => {
                            if (oldStats[index]) {
                                oldStats[index].textContent = stat.textContent;
                            }
                        });
                        
                        // Update active members grid
                        const newGrid = doc.querySelector('.active-members-grid');
                        const oldGrid = document.querySelector('.active-members-grid');
                        if (newGrid && oldGrid) {
                            oldGrid.innerHTML = newGrid.innerHTML;
                            // Re-attach event listeners to new badges
                            attachBadgeListeners();
                        }
                    } catch (updateError) {
                        console.log('Could not update data in real-time, will reload instead', updateError);
                    }

                    // Reset form after short delay
                    setTimeout(() => {
                        memberSelect.value = '';
                        btnCheckIn.disabled = true;
                        btnCheckOut.disabled = true;
                        
                        // Clear member preview
                        const preview = document.querySelector('.member-preview');
                        const emptyState = document.querySelector('.empty-state');
                        if (preview) preview.remove();
                        if (!emptyState) {
                            const newEmpty = document.createElement('div');
                            newEmpty.className = 'empty-state';
                            newEmpty.textContent = 'Search for a member above to show their profile preview here.';
                            document.querySelector('[data-member-preview-container]')?.appendChild(newEmpty);
                        } else {
                            emptyState.style.display = 'block';
                        }

                        // Remove success alert
                        alert.remove();
                    }, 1500);
                } else {
                    // Handle error response
                    const errorMsg = text.includes('is not currently checked in') 
                        ? 'Member is not currently checked in.'
                        : text.includes('already checked in')
                        ? 'Member is already checked in. Use Check-out instead.'
                        : 'An error occurred. Please try again.';
                    
                    alert('Error: ' + errorMsg);
                    button.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                button.disabled = false;
            }
        }

        // Initialize button states on page load
        if (memberSelect.value) {
            btnCheckIn.disabled = false;
            btnCheckOut.disabled = false;
        }

        // Handle member badge clicks for quick checkout
        document.querySelectorAll('.member-badge').forEach(badge => {
            badge.addEventListener('click', function() {
                const attendanceId = this.dataset.attendanceId;
                const memberName = this.dataset.memberName;

                this.classList.add('checking-out');

                fetch('{{ route("staff.checkin.quick-checkout") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('input[name="_token"]').value
                    },
                    body: JSON.stringify({
                        attendance_id: attendanceId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.classList.remove('checking-out');
                        this.classList.add('checked-out');
                        
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert success';
                        alertDiv.textContent = data.message;
                        document.querySelector('.hero-panel').insertAdjacentElement('afterend', alertDiv);
                        
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        this.classList.remove('checking-out');
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    this.classList.remove('checking-out');
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
            });
        });
    </script>
@endsection
