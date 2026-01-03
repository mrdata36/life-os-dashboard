document.addEventListener('DOMContentLoaded', () => {
    
    // Store chart instances to update them later
    const chartInstances = {};

    // Helper: Get color based on percentage
    const getColor = (percent) => {
        if (percent >= 80) return '#22C55E'; // Green-500
        if (percent >= 50) return '#EAB308'; // Yellow-500
        return '#EF4444'; // Red-500
    };

    // Helper: Update a specific day's chart dynamically
    const updateGauge = (date, change) => {
        if (!chartInstances[date] || typeof totalItems === 'undefined') return;

        // Update local count
        if (typeof dailyCounts !== 'undefined') {
            dailyCounts[date] += change;
            // Clamp between 0 and total
            dailyCounts[date] = Math.max(0, Math.min(dailyCounts[date], totalItems));
            
            const newPercent = totalItems > 0 ? Math.round((dailyCounts[date] / totalItems) * 100) : 0;
            const newColor = getColor(newPercent);

            // Update Chart.js data
            const chart = chartInstances[date];
            chart.data.datasets[0].data = [newPercent, 100 - newPercent];
            chart.data.datasets[0].backgroundColor = [newColor, '#e5e7eb'];
            
            // Force update to redraw text center
            chart.update();

            // Optional: Update the text center plugin value manually if needed, 
            // but the beforeDraw hook reads from the data we just updated.
        }
    };

    
    // ========================
    // HABIT CHECKBOXES
    // ========================
    document.querySelectorAll('.habit-checkbox').forEach(box => {
        box.addEventListener('change', function() {
            const habitId = this.dataset.habit;
            const date = this.dataset.date;
            const completed = this.checked ? 1 : 0;

            fetch('mark_habit.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `habit_id=${habitId}&date=${date}&completed=${completed}`
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    // Update the gauge immediately (+1 if checked, -1 if unchecked)
                    updateGauge(date, completed ? 1 : -1);
                } else {
                    alert('Error updating habit');
                }
            })
            .catch(err => console.error(err));
        });
    });

    // ========================
    // TASK CHECKBOXES
    // ========================
    document.querySelectorAll('.task-checkbox').forEach(box => {
        box.addEventListener('change', function() {
            const taskId = this.dataset.task;
            const date = this.dataset.date; // Get date from attribute
            const completed = this.checked ? 1 : 0;

            fetch('mark_task.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `task_id=${taskId}&completed=${completed}`
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    updateGauge(date, completed ? 1 : -1);
                    // Toggle strike-through style for Todo-list feel
                    const textSpan = this.nextElementSibling;
                    if(textSpan && textSpan.classList.contains('task-text')) {
                        if(completed) textSpan.classList.add('line-through', 'text-gray-400');
                        else textSpan.classList.remove('line-through', 'text-gray-400');
                    }
                } else {
                    alert('Error updating task');
                }
            })
            .catch(err => console.error(err));
        });
    });

    // ========================
    // DELETE FUNCTIONALITY
    // ========================
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if(!confirm('Are you sure you want to delete this item?')) return;

            const type = this.dataset.type;
            const id = this.dataset.id;

            fetch('delete_item.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `type=${type}&id=${id}`
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    window.location.reload(); // Reload to update stats and remove item cleanly
                } else {
                    alert('Error deleting item');
                }
            });
        });
    });

    // ========================
    // ROADMAP CHECKBOXES
    // ========================
    document.querySelectorAll('.roadmap-checkbox').forEach(box => {
        box.addEventListener('change', function() {
            const id = this.dataset.id;
            const completed = this.checked ? 1 : 0;

            fetch('mark_roadmap.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${id}&completed=${completed}`
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    // Hide the item immediately as requested ("nikiifanya ndio itatoka")
                    this.closest('.bg-white').style.display = 'none';
                } else {
                    alert('Error updating roadmap');
                }
            })
            .catch(err => console.error(err));
        });
    });

    // ========================
    // POMODORO TIMER
    // ========================
    const timerDisplay = document.getElementById('timer-display');
    const startBtn = document.getElementById('start-btn');
    const pauseBtn = document.getElementById('pause-btn');
    const resetBtn = document.getElementById('reset-btn');
    const alarmSound = document.getElementById('alarm-sound');
    const modeBtns = document.querySelectorAll('.timer-mode');

    let timerInterval = null;
    let timeLeft = 25 * 60; // 25 minutes in seconds
    let isPaused = true;

    const updateTimerDisplay = () => {
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        timerDisplay.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    };

    const startTimer = () => {
        if (isPaused) {
            isPaused = false;
            timerInterval = setInterval(() => {
                timeLeft--;
                updateTimerDisplay();
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    if(alarmSound) alarmSound.play();
                    alert("Time for a break!");
                    resetTimer();
                }
            }, 1000);
        }
    };

    const pauseTimer = () => {
        isPaused = true;
        clearInterval(timerInterval);
    };

    const resetTimer = () => {
        pauseTimer();
        const activeBtn = document.querySelector('.timer-mode.active');
        timeLeft = (activeBtn ? parseInt(activeBtn.dataset.time) : 25) * 60;
        updateTimerDisplay();
    };

    if (startBtn) {
        startBtn.addEventListener('click', startTimer);
    }
    if (pauseBtn) {
        pauseBtn.addEventListener('click', pauseTimer);
    }
    if (resetBtn) {
        resetBtn.addEventListener('click', resetTimer);
    }

    // Mode switching
    modeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active class from all
            modeBtns.forEach(b => b.classList.remove('active', 'ring-2', 'ring-offset-1'));
            // Add to clicked
            this.classList.add('active', 'ring-2', 'ring-offset-1');
            
            timeLeft = parseInt(this.dataset.time) * 60;
            pauseTimer();
            updateTimerDisplay();
        });
    });

    // Initialize display
    if(timerDisplay) {
        updateTimerDisplay();
    }

    // ========================
    // CHARTS
    // ========================
    if (typeof dailyStats !== 'undefined') {
        Object.keys(dailyStats).forEach(day => {
            const ctx = document.getElementById(`chart-${day}`);
            if (!ctx) return;

            const percent = dailyStats[day];
            const remaining = 100 - percent;

            chartInstances[day] = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Done', 'Todo'],
                    datasets: [{
                        data: [percent, remaining],
                        backgroundColor: [
                            getColor(percent), // Dynamic Color
                            '#e5e7eb'  // Gray-200
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: false }
                    },
                    animation: {
                        animateScale: true,
                        animateRotate: true
                    }
                },
                plugins: [{
                    id: 'textCenter',
                    beforeDraw: function(chart) {
                        var width = chart.width,
                            height = chart.height,
                            ctx = chart.ctx;
            
                        ctx.restore();
                        var fontSize = (height / 114).toFixed(2);
                        ctx.font = "bold " + fontSize + "em sans-serif";
                        ctx.textBaseline = "middle";
                        ctx.fillStyle = "#374151";
            
                        // Calculate current percent from data array to ensure updates show correctly
                        var currentPercent = chart.data.datasets[0].data[0];
                        var text = currentPercent + "%",
                            textX = Math.round((width - ctx.measureText(text).width) / 2),
                            textY = height / 2;
            
                        ctx.fillText(text, textX, textY);
                        ctx.save();
                    }
                }]
            });
        });
    }
});