{{-- resources/views/live-classes/show.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $class->title }} - Live Class</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
            font-size: 16px;
        }
        .status-badge.live {
            background: #dc3545;
            color: white;
            animation: pulse 2s infinite;
        }
        .status-badge.upcoming {
            background: #ffc107;
            color: #000;
        }
        .status-badge.completed {
            background: #28a745;
            color: white;
        }
        .status-badge.offline {
            background: #6c757d;
            color: white;
        }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        .video-container {
            position: relative;
            background: #000;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        .video-container video {
            width: 100%;
            max-height: 600px;
            background: #1a1a1a;
        }
        .video-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0,0,0,0.7);
            color: white;
            flex-direction: column;
        }
        .video-overlay .icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        .video-overlay .message {
            font-size: 24px;
            font-weight: bold;
        }
        .video-overlay .sub-message {
            font-size: 16px;
            color: #ccc;
            margin-top: 10px;
        }
        .info-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .info-item:last-child {
            border-bottom: none;
        }
        .action-buttons .btn {
            margin-right: 10px;
            padding: 12px 30px;
        }
        .viewer-badge {
            background: rgba(255,255,255,0.9);
            padding: 5px 15px;
            border-radius: 20px;
            color: #000;
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 10;
        }
        .countdown-number {
            font-size: 60px;
            font-weight: bold;
            color: #fff;
        }
        .countdown-label {
            font-size: 18px;
            color: #ccc;
        }
    </style>
</head>
<body>

<div class="container mt-4">
    <!-- Navigation -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('live-class.index') }}">Live Classes</a></li>
            <li class="breadcrumb-item active">{{ $class->title }}</li>
        </ol>
    </nav>

    <!-- Class Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h1>{{ $class->title }}</h1>
            <p class="text-muted">{{ $class->description }}</p>
            <div class="d-flex align-items-center">
                <span class="status-badge {{ $stats['status_color'] }}">
                    {{ $stats['status'] }}
                </span>
                @if($class->is_live)
                <span class="ms-3">
                    <i class="fas fa-eye"></i> 
                    <span id="viewer-count">{{ $stats['viewer_count'] }}</span> watching
                </span>
                @endif
            </div>
        </div>
    </div>

    
    <!-- Video Player -->
    <div class="video-container">
        <video id="class-video" controls autoplay>
            @if($class->stream_url)
            <source src="{{ $class->stream_url }}" type="application/x-mpegURL">
            @endif
            Your browser does not support the video tag.
        </video>

        @if($class->is_live)
            <div class="viewer-badge">
                <i class="fas fa-eye"></i> <span id="viewer-count-badge">{{ $stats['viewer_count'] }}</span>
            </div>
            <div class="overlay-status live" style="position:absolute;top:20px;left:20px;background:rgba(220,53,69,0.9);padding:8px 16px;border-radius:20px;color:white;font-weight:bold;z-index:10;">
                🔴 LIVE
            </div>
        @elseif($class->isUpcoming())
            <div class="video-overlay">
                <div class="icon">⏳</div>
                <div class="message">Class Starts In</div>
                <div class="countdown-number" id="countdown-timer"></div>
                <div class="sub-message">
                    <i class="far fa-calendar-alt"></i> 
                    {{ $class->scheduled_start->format('F d, Y h:i A') }}
                </div>
            </div>
        @elseif($class->isCompleted())
            <div class="video-overlay">
                <div class="icon">✅</div>
                <div class="message">Class Completed</div>
                <div class="sub-message">
                    This class has ended. Recording available soon.
                </div>
            </div>
        @else
            <div class="video-overlay">
                <div class="icon">📹</div>
                <div class="message">Not Currently Live</div>
                <div class="sub-message">
                    Please check back later for the class.
                </div>
            </div>
        @endif
    </div>

    <!-- Class Info -->
    <div class="row">
        <div class="col-md-8">
            <div class="info-card">
                <h5><i class="fas fa-info-circle"></i> Class Details</h5>
                <div class="info-item">
                    <span><strong>Teacher:</strong> {{ $class->teacher_name }}</span>
                    <span><strong>Class Code:</strong> {{ $class->class_code }}</span>
                </div>
                <div class="info-item">
                    <span><strong>Status:</strong> {{ $stats['status'] }}</span>
                    <span><strong>Max Students:</strong> {{ $class->max_students }}</span>
                </div>
                @if($class->actual_start)
                <div class="info-item">
                    <span><strong>Started:</strong> {{ $class->actual_start->format('M d, Y h:i A') }}</span>
                    @if($stats['duration'])
                    <span><strong>Duration:</strong> {{ $stats['duration'] }}</span>
                    @endif
                </div>
                @endif
                @if($class->scheduled_start)
                <div class="info-item">
                    <span><strong>Scheduled:</strong> {{ $class->scheduled_start->format('M d, Y h:i A') }}</span>
                    @if($class->scheduled_end)
                    <span><strong>Ends:</strong> {{ $class->scheduled_end->format('M d, Y h:i A') }}</span>
                    @endif
                </div>
                @endif
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-card">
                <h5><i class="fas fa-tools"></i> Actions</h5>
                <div class="action-buttons">
                    @if($class->is_live)
                        <button class="btn btn-danger btn-lg w-100" onclick="joinClass()">
                            <i class="fas fa-video"></i> Join Live Class
                        </button>
                    @elseif($class->isUpcoming())
                        <button class="btn btn-warning btn-lg w-100" onclick="setReminder()">
                            <i class="fas fa-bell"></i> Set Reminder
                        </button>
                    @elseif($class->isCompleted())
                        <button class="btn btn-success btn-lg w-100" onclick="watchRecording()">
                            <i class="fas fa-play"></i> Watch Recording
                        </button>
                    @else
                        <button class="btn btn-secondary btn-lg w-100" disabled>
                            <i class="fas fa-clock"></i> Not Available
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:9999;">
    <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <strong class="me-auto">Live Class Update</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body" id="toast-message"></div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    var classId = {{ $class->id }};
    var isLive = {{ $class->is_live ? 'true' : 'false' }};
    var toast = new bootstrap.Toast(document.getElementById('liveToast'));
    
    // Update viewer count every 5 seconds if live
    if (isLive) {
        setInterval(function() {
            updateViewerCount(classId);
        }, 5000);
    }

    // Check status every 15 seconds
    setInterval(function() {
        checkClassStatus(classId);
    }, 15000);

    // Countdown timer for upcoming classes
    @if($class->isUpcoming())
    startCountdown('{{ $class->scheduled_start->toIso8601String() }}');
    @endif

    // Function to update viewer count
    function updateViewerCount(classId) {
        $.ajax({
            url: '/live-class/' + classId + '/status',
            method: 'GET',
            success: function(data) {
                $('#viewer-count').text(data.viewer_count || 0);
                $('#viewer-count-badge').text(data.viewer_count || 0);
            }
        });
    }

    // Function to check class status
    function checkClassStatus(classId) {
        $.ajax({
            url: '/live-class/' + classId + '/status',
            method: 'GET',
            success: function(data) {
                var currentStatus = isLive;
                isLive = data.is_live;
                
                // If status changed from offline to live
                if (!currentStatus && isLive) {
                    showToast('🔴 The class is now live!', 'success');
                    location.reload();
                }
                // If status changed from live to offline
                else if (currentStatus && !isLive) {
                    showToast('⏹️ The class has ended', 'warning');
                    location.reload();
                }
            }
        });
    }

    // Countdown timer function
    function startCountdown(targetDate) {
        var target = new Date(targetDate).getTime();
        
        var timer = setInterval(function() {
            var now = new Date().getTime();
            var distance = target - now;

            var days = Math.floor(distance / (1000 * 60 * 60 * 24));
            var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            var seconds = Math.floor((distance % (1000 * 60)) / 1000);

            $('#countdown-timer').html(
                (days > 0 ? days + 'd ' : '') +
                String(hours).padStart(2, '0') + ':' +
                String(minutes).padStart(2, '0') + ':' +
                String(seconds).padStart(2, '0')
            );

            if (distance < 0) {
                clearInterval(timer);
                location.reload();
            }
        }, 1000);
    }

    // Toast notification function
    function showToast(message, type) {
        var toastEl = document.getElementById('liveToast');
        var toastBody = document.getElementById('toast-message');
        toastBody.innerHTML = message;
        toastEl.className = 'toast align-items-center text-white bg-' + 
            (type === 'success' ? 'success' : 'danger') + ' border-0';
        toast.show();
    }

    // Action functions
    function joinClass() {
        window.location.href = '/live-class/{{ $class->id }}/join';
    }

    function setReminder() {
        $.ajax({
            url: '/live-class/{{ $class->id }}/reminder',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function() {
                showToast('✅ Reminder set successfully!', 'success');
            }
        });
    }

    function watchRecording() {
        window.location.href = '/live-class/{{ $class->id }}/recording';
    }

    // Handle video errors
    var video = document.getElementById('class-video');
    if (video) {
        video.addEventListener('error', function(e) {
            console.log('Video error:', e);
            if (!isLive) {
                showToast('📹 The class is not currently live', 'warning');
            }
        });
    }
});
</script>
</body>
</html>