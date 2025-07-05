<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>統計データ</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
</head>
<body>
    <div class="container">
        <h1>統計情報</h1>
        <p>これまでのクイズの成績を確認できます。</p>
        
        <div id="error-message" style="color: red; margin: 20px 0; display: none;"></div>

        <div class="chart-container">
            <h2>正答率の推移 (日別)</h2>
            <div style="position: relative; height: 300px;">
                <canvas id="accuracy-chart"></canvas>
            </div>
            <div id="accuracy-chart-nodata" style="display: none; text-align: center; padding: 20px;">クイズに解答すると、日々の正答率がここに表示されます。</div>
        </div>

        <div class="chart-container">
            <h2>カテゴリ別 正答状況</h2>
            <p>色の濃さが正答率の高さを表します。</p>
            <div id="heatmap-container"></div>
            <div id="heatmap-nodata" style="display: none; text-align: center; padding: 20px;">クイズに解答すると、カテゴリごとの正答状況がここに表示されます。</div>
        </div>

        <a href="index.php" class="btn-menu">ホームに戻る</a>
    </div>

    <script>
        const apiProxyUrl = 'api_proxy.php';
        let accuracyChartInstance = null; // チャートインスタンスを保持する変数

        function displayAccuracyChart(dailyData) {
            const chartEl = document.getElementById('accuracy-chart');
            const noDataEl = document.getElementById('accuracy-chart-nodata');

            // 既存のチャートがあれば破棄する
            if (accuracyChartInstance) {
                accuracyChartInstance.destroy();
                accuracyChartInstance = null;
            }

            if (!dailyData || dailyData.length === 0) {
                chartEl.style.display = 'none';
                noDataEl.style.display = 'block';
                return;
            }
            chartEl.style.display = 'block';
            noDataEl.style.display = 'none';

            const ctx = chartEl.getContext('2d');
            const labels = dailyData.map(d => d.date);
            const accuracyData = dailyData.map(d => (d.total > 0 ? (d.correct / d.total) * 100 : 0));

            accuracyChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '正答率 (%)',
                        data: accuracyData,
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        fill: true,
                        tension: 0.1
                    }]
                },
                options: {
                    scales: {
                        x: { type: 'time', time: { unit: 'day', tooltipFormat: 'yyyy/MM/dd' }, title: { display: true, text: '日付' } },
                        y: { beginAtZero: true, max: 100, title: { display: true, text: '正答率 (%)' } }
                    },
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }

        function displayHeatmap(categoryData) {
            const container = document.getElementById('heatmap-container');
            const noDataEl = document.getElementById('heatmap-nodata');

            if (!categoryData || Object.keys(categoryData).length === 0) {
                container.style.display = 'none';
                noDataEl.style.display = 'block';
                return;
            }
            container.style.display = 'grid';
            noDataEl.style.display = 'none';
            container.innerHTML = '';

            const categories = Object.keys(categoryData).sort();
            categories.forEach(category => {
                const perf = categoryData[category];
                const accuracy = perf.total > 0 ? perf.correct / perf.total : 0;
                const cell = document.createElement('div');
                cell.className = 'heatmap-cell';
                const green = Math.round(200 * accuracy);
                const red = Math.round(200 * (1 - accuracy));
                cell.style.backgroundColor = `rgb(${red}, ${green}, 80)`;
                const accuracyPercent = (accuracy * 100).toFixed(1);
                cell.innerHTML = `<strong>${category.replace(/Entertainment:|Science:/g, '').trim()}</strong><span>${accuracyPercent}%</span><br><small>(${perf.correct}/${perf.total})</small>`;
                container.appendChild(cell);
            });
        }

        async function fetchStats() {
            try {
                const response = await fetch(`${apiProxyUrl}?endpoint=get_stats`);
                if (!response.ok) {
                    throw new Error(`サーバーとの通信に失敗しました (HTTP ${response.status})`);
                }
                const stats = await response.json();
                
                // 念のためエラー表示を隠す
                document.getElementById('error-message').style.display = 'none';
                
                displayAccuracyChart(stats.daily_accuracy || []);
                displayHeatmap(stats.category_performance || {});
            } catch (error) {
                const errorContainer = document.getElementById('error-message');
                errorContainer.textContent = `エラー: ${error.message} ページを再読み込みするか、後でもう一度お試しください。`;
                errorContainer.style.display = 'block'; // エラーメッセージを表示
                console.error("Fetch error:", error);

                // エラー発生時はグラフ描画をキャンセル
                displayAccuracyChart([]);
                displayHeatmap({});
            }
        }
        
        document.addEventListener('DOMContentLoaded', fetchStats);
    </script>
</body>
</html>