document.addEventListener('DOMContentLoaded', () => {
    
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
                if(data.status !== 'success') alert('Error updating habit');
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
            const completed = this.checked ? 1 : 0;

            fetch('mark_task.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `task_id=${taskId}&completed=${completed}`
            })
            .then(res => res.json())
            .then(data => {
                if(data.status !== 'success') alert('Error updating task');
            })
            .catch(err => console.error(err));
        });
    });

    // ========================
    // CHARTS
    // ========================
    if (typeof dailyStats !== 'undefined') {
        Object.keys(dailyStats).forEach(day => {
            const ctx = document.getElementById(`chart-${day}`);
            if (!ctx) return;

            const percent = dailyStats[day];
            const remaining = 100 - percent;

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Done', 'Todo'],
                    datasets: [{
                        data: [percent, remaining],
                        backgroundColor: ['#34D399', '#D1D5DB'],
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
            
                        var text = percent + "%",
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
