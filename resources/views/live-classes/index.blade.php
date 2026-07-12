{{-- resources/views/live-classes/index.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Live Classes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .live-badge {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        .class-card {
            transition: transform 0.3s;
            cursor: pointer;
        }
        .class-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .viewer-count {
            font-size: 14px;
            color: #6c757d;
        }
        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }
        .status-live { background: #dc3545; }
        .status-upcoming { background: #ffc107; }
        .status-completed { background: #28a745; }
        .status-offline { background: #6c757d; }
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
        .live-counter {
            font-size: 12px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 2px 10px;
            border-radius: 20px;
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .video-container {
            position: relative;
            background: #000;
            border-radius: 8px;
            overflow: hidden;
        }
        .video-container video {
            width: 100%;
            max-height: 500px;
            background: #1a1a1a;
        }
        .overlay-status {
            position: absolute;
            top: 20px;
            left: 20px;
            padding: 8px 16px;
            border-radius: 20px;
            color: white;
            font-weight: bold;
            z-index: 10;
        }
        .overlay-status.live {
            background: rgba(220, 53, 69, 0.9);
        }
        .overlay-status.offline {
            background: rgba(108, 117, 125, 0.9);
        }
        .upcoming-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            flex-direction: column;
        }
        .countdown-timer {
            font-size: 48px;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="container mt-4">
    <h1 class="mb-4">📚 Live Classes</h1>

    <!-- Live Classes -->
    @if($liveClasses->count() > 0)
    <div class="row mb-4">
        <div class="col-12">
            <h3 class="text-danger">🔴 Live Now</h3>
        </div>
        @foreach($liveClasses as $class)
        <div class="col-md-4 mb-3">
            <div class="card class-card" onclick="window.location.href='{{ route('live-class.show', $class->id) }}'">
                <div class="card-body">
                    <h5 class="card-title">{{ $class->title }}</h5>
                    <p class="card-text">
                        <strong>Teacher:</strong> {{ $class->teacher_name }}
                    </p>
                    <span class="badge bg-danger live-badge">🔴 LIVE</span>
                    <span class="viewer-count float-end">
                        <i class="fas fa-eye"></i> <span class="viewer-count-{{ $class->id }}">0</span>
                    </span>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <!-- Upcoming Classes -->
    @if($upcomingClasses->count() > 0)
    <div class="row mb-4">
        <div class="col-12">
            <h3 class="text-warning">⏳ Upcoming Classes</h3>
        </div>
        @foreach($upcomingClasses as $class)
        <div class="col-md-4 mb-3">
            <div class="card class-card">
                <div class="card-body">
                    <h5 class="card-title">{{ $class->title }}</h5>
                    <p class="card-text">
                        <strong>Teacher:</strong> {{ $class->teacher_name }}
                    </p>
                    <span class="badge bg-warning text-dark">⏳ Upcoming</span>
                    <small class="d-block mt-2">
                        <i class="far fa-clock"></i> 
                        {{ $class->scheduled_start->format('M d, Y h:i A') }}
                    </small>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <!-- Completed Classes -->
    @if($completedClasses->count() > 0)
    <div class="row">
        <div class="col-12">
            <h3 class="text-success">✅ Completed Classes</h3>
        </div>
        @foreach($completedClasses as $class)
        <div class="col-md-4 mb-3">
            <div class="card class-card">
                <div class="card-body">
                    <h5 class="card-title">{{ $class->title }}</h5>
                    <p class="card-text">
                        <strong>Teacher:</strong> {{ $class->teacher_name }}
                    </p>
                    <span class="badge bg-success">✅ Completed</span>
                    <small class="d-block mt-2">
                        <i class="far fa-clock"></i> 
                        {{ $class->actual_end ? $class->actual_end->format('M d, Y h:i A') : 'Completed' }}
                    </small>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

<!-- Toast Container -->
<div class="toast-container" id="toast-container"></div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    // Update viewer counts for live classes
    @if($liveClasses->count() > 0)
        @foreach($liveClasses as $class)
        updateViewerCount({{ $class->id }});
        @endforeach
    @endif

    // Check for new live classes every 30 seconds
    setInterval(checkNewLiveClasses, 30000);

    // Function to update viewer count
    function updateViewerCount(classId) {
        $.ajax({
            url: '/live-class/' + classId + '/status',
            method: 'GET',
            success: function(data) {
                $('.viewer-count-' + classId).text(data.viewer_count || 0);
            }
        });
    }

    // Function to check for new live classes
    function checkNewLiveClasses() {
        $.ajax({
            url: '/live-class/all-live',
            method: 'GET',
            success: function(classes) {
                var currentLiveIds = [];
                $('.class-card .badge.bg-danger').each(function() {
                    var card = $(this).closest('.class-card');
                    var href = card.attr('onclick');
                    if (href) {
                        var id = href.match(/\d+/);
                        if (id) currentLiveIds.push(parseInt(id[0]));
                    }
                });

                classes.forEach(function(classData) {
                    if (!currentLiveIds.includes(classData.id)) {
                        showToast('Class Started: ' + classData.title, 'success');
                        location.reload();
                    }
                });
            }
        });
    }

    // Toast notification function
    function showToast(message, type) {
        var toast = $(
            '<div class="toast align-items-center text-white bg-' + (type === 'success' ? 'success' : 'danger') + ' border-0" role="alert" aria-live="assertive" aria-atomic="true">' +
                '<div class="d-flex">' +
                    '<div class="toast-body">' + message + '</div>' +
                    '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>' +
                '</div>' +
            '</div>'
        );
        $('#toast-container').append(toast);
        var bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        setTimeout(function() {
            toast.remove();
        }, 5000);
    }
});
</script>
</body>
</html>