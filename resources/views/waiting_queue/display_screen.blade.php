<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('waiting_queue.display_screen_title') }} - {{ $branch->name ?? config('app.name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Noto Sans SC', sans-serif;
            background: linear-gradient(135deg, #1A237E 0%, #0D1452 100%);
            min-height: 100vh;
            color: #fff;
            overflow: hidden;
        }
        .container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-rows: auto 1fr auto;
            gap: 20px;
            padding: 30px;
            height: 100vh;
        }

        /* 头部 */
        .header {
            grid-column: 1 / -1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(255,255,255,0.1);
        }
        .header .logo {
            font-size: 28px;
            font-weight: 700;
        }
        .header .time {
            font-size: 48px;
            font-weight: 500;
        }
        .header .date {
            font-size: 18px;
            opacity: 0.8;
        }

        /* 当前叫号区域 */
        .current-call {
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            border-radius: 20px;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        .current-call .label {
            font-size: 24px;
            opacity: 0.9;
            margin-bottom: 10px;
        }
        .current-call .number {
            font-size: 160px;
            font-weight: 700;
            line-height: 1;
            text-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        .current-call .info {
            font-size: 32px;
            margin-top: 20px;
        }
        .current-call .chair {
            font-size: 28px;
            margin-top: 10px;
            padding: 10px 30px;
            background: rgba(255,255,255,0.2);
            border-radius: 30px;
        }
        .current-call.empty {
            background: rgba(255,255,255,0.1);
        }
        .current-call.empty .number {
            font-size: 80px;
            opacity: 0.5;
        }

        /* 候诊队列 */
        .waiting-queue {
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 30px;
            overflow: hidden;
        }
        .waiting-queue .title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .waiting-queue .title .count {
            background: #FF6B35;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 18px;
        }
        .queue-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .queue-item {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            transition: all 0.3s;
        }
        .queue-item:first-child {
            background: rgba(255,255,255,0.2);
            transform: scale(1.02);
        }
        .queue-item .queue-num {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 700;
            margin-right: 20px;
        }
        .queue-item .patient-info {
            flex: 1;
        }
        .queue-item .patient-name {
            font-size: 22px;
            font-weight: 500;
        }
        .queue-item .doctor-name {
            font-size: 16px;
            opacity: 0.8;
        }
        .queue-item .wait-time {
            font-size: 18px;
            opacity: 0.8;
        }

        /* 就诊中区域 */
        .in-treatment {
            grid-column: 1 / -1;
            background: rgba(255,255,255,0.05);
            border-radius: 15px;
            padding: 20px 30px;
        }
        .in-treatment .title {
            font-size: 18px;
            margin-bottom: 15px;
            opacity: 0.8;
        }
        .treatment-list {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .treatment-item {
            background: rgba(76, 175, 80, 0.3);
            padding: 15px 25px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .treatment-item .dot {
            width: 12px;
            height: 12px;
            background: #4CAF50;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* 底部提示 */
        .footer {
            grid-column: 1 / -1;
            text-align: center;
            padding-top: 15px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        .tips {
            font-size: 20px;
            opacity: 0.9;
        }

        /* 动画效果 */
        .flash {
            animation: flash 0.5s ease-in-out 3;
        }
        @keyframes flash {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body>
    <div class="container">
        {{-- 头部 --}}
        <div class="header">
            <div>
                <div class="logo">{{ $branch->name ?? config('app.name') }}</div>
                <div class="date" id="current-date"></div>
            </div>
            <div class="time" id="current-time"></div>
        </div>

        {{-- 当前叫号 --}}
        <div class="current-call empty" id="current-call">
            <div class="label">{{ __('waiting_queue.current_calling') }}</div>
            <div class="number">--</div>
            <div class="info">{{ __('waiting_queue.please_wait') }}</div>
        </div>

        {{-- 候诊队列 --}}
        <div class="waiting-queue">
            <div class="title">
                {{ __('waiting_queue.waiting_list') }}
                <span class="count" id="waiting-count">0</span>
            </div>
            <div class="queue-list" id="queue-list">
                <p style="text-align: center; opacity: 0.6;">{{ __('waiting_queue.no_waiting') }}</p>
            </div>
        </div>

        {{-- 就诊中 --}}
        <div class="in-treatment">
            <div class="title">{{ __('waiting_queue.in_treatment_now') }}</div>
            <div class="treatment-list" id="treatment-list">
                <span style="opacity: 0.6;">--</span>
            </div>
        </div>

        {{-- 底部提示 --}}
        <div class="footer">
            <div class="tips" id="tips">{{ __('waiting_queue.display_tip') }}</div>
        </div>
    </div>

    <script>
        // 更新时间
        function updateTime() {
            var now = new Date();
            document.getElementById('current-time').textContent =
                now.getHours().toString().padStart(2, '0') + ':' +
                now.getMinutes().toString().padStart(2, '0') + ':' +
                now.getSeconds().toString().padStart(2, '0');

            var days = ['{{ __("datetime.days.0") }}', '{{ __("datetime.days.1") }}', '{{ __("datetime.days.2") }}',
                        '{{ __("datetime.days.3") }}', '{{ __("datetime.days.4") }}', '{{ __("datetime.days.5") }}', '{{ __("datetime.days.6") }}'];
            document.getElementById('current-date').textContent =
                now.getFullYear() + '-' +
                (now.getMonth() + 1).toString().padStart(2, '0') + '-' +
                now.getDate().toString().padStart(2, '0') + ' ' +
                days[now.getDay()];
        }
        setInterval(updateTime, 1000);
        updateTime();

        // 加载队列数据
        var lastCallingNumber = null;

        function loadQueueData() {
            fetch('{{ url("waiting-queue/display-data") }}?branch_id={{ $branch->id ?? 1 }}')
                .then(response => response.json())
                .then(data => {
                    updateCurrentCall(data.current_calling);
                    updateWaitingList(data.waiting_list, data.stats.waiting_count);
                    updateTreatmentList(data.in_treatment_list);
                })
                .catch(err => console.error('Error loading queue data:', err));
        }

        function updateCurrentCall(calling) {
            var container = document.getElementById('current-call');

            if (calling) {
                // 检查是否是新叫号，添加闪烁效果
                if (lastCallingNumber !== calling.queue_number) {
                    container.classList.add('flash');
                    setTimeout(() => container.classList.remove('flash'), 1500);
                    lastCallingNumber = calling.queue_number;

                    // 播放叫号声音（需要用户交互后才能播放）
                    // playCallSound();
                }

                container.classList.remove('empty');
                container.innerHTML = `
                    <div class="label">{{ __('waiting_queue.current_calling') }}</div>
                    <div class="number">${calling.queue_number}</div>
                    <div class="info">${calling.patient_name}</div>
                    ${calling.chair_name ? `<div class="chair">${calling.chair_name}</div>` : ''}
                `;
            } else {
                container.classList.add('empty');
                container.innerHTML = `
                    <div class="label">{{ __('waiting_queue.current_calling') }}</div>
                    <div class="number">--</div>
                    <div class="info">{{ __('waiting_queue.please_wait') }}</div>
                `;
            }
        }

        function updateWaitingList(list, count) {
            document.getElementById('waiting-count').textContent = count;

            var container = document.getElementById('queue-list');
            if (!list || list.length === 0) {
                container.innerHTML = '<p style="text-align: center; opacity: 0.6;">{{ __("waiting_queue.no_waiting") }}</p>';
                return;
            }

            var html = '';
            list.forEach(function(item, index) {
                html += `
                    <div class="queue-item">
                        <div class="queue-num">${item.queue_number}</div>
                        <div class="patient-info">
                            <div class="patient-name">${item.patient_name}</div>
                            <div class="doctor-name">${item.doctor_name || ''}</div>
                        </div>
                        <div class="wait-time">${item.check_in_time}</div>
                    </div>
                `;
            });
            container.innerHTML = html;
        }

        function updateTreatmentList(list) {
            var container = document.getElementById('treatment-list');
            if (!list || list.length === 0) {
                container.innerHTML = '<span style="opacity: 0.6;">--</span>';
                return;
            }

            var html = '';
            list.forEach(function(item) {
                html += `
                    <div class="treatment-item">
                        <div class="dot"></div>
                        <span>${item.patient_name} - ${item.doctor_name}${item.chair_name ? ' (' + item.chair_name + ')' : ''}</span>
                    </div>
                `;
            });
            container.innerHTML = html;
        }

        // 初始加载
        loadQueueData();

        // 每5秒刷新一次
        setInterval(loadQueueData, 5000);

        // 提示语轮播
        var tips = [
            '{{ __("waiting_queue.tips.1") }}',
            '{{ __("waiting_queue.tips.2") }}',
            '{{ __("waiting_queue.tips.3") }}',
            '{{ __("waiting_queue.tips.4") }}'
        ];
        var tipIndex = 0;

        function rotateTips() {
            document.getElementById('tips').textContent = tips[tipIndex];
            tipIndex = (tipIndex + 1) % tips.length;
        }
        setInterval(rotateTips, 10000);
    </script>
</body>
</html>
