<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ミニクイズへようこそ</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>クイズへようこそ！</h1>
        <p>下のボタンからクイズを開始するか、メニューから他の機能を選択してください。</p>
        
        <nav>
            <a href="quiz.php" class="btn-start">クイズを始める</a>
            <a href="stats.php" class="btn-menu">統計を見る</a>
        </nav>

        <div class="category-selection">
            <h2>カテゴリ選択</h2>
            <p>好きなカテゴリを選んでクイズを始めよう！</p>
            <select id="category" name="category">
                <option value="">すべてのカテゴリ</option>
                <option value="9">General Knowledge</option>
                <option value="10">Entertainment: Books</option>
                <option value="11">Entertainment: Film</option>
                <option value="12">Entertainment: Music</option>
                <option value="14">Entertainment: Television</option>
                <option value="15">Entertainment: Video Games</option>
                <option value="17">Science & Nature</option>
                <option value="18">Science: Computers</option>
                <option value="21">Sports</option>
                <option value="22">Geography</option>
                <option value="23">History</option>
                <option value="31">Entertainment: Japanese Anime & Manga</option>
            </select>
            <button id="start-quiz-btn" class="btn-start">選択したカテゴリで開始</button>
        </div>
    </div>

    <script>
        document.getElementById('start-quiz-btn').addEventListener('click', () => {
            const category = document.getElementById('category').value;
            let url = 'quiz.php';
            if (category) {
                url += `?category=${category}`;
            }
            window.location.href = url;
        });
    </script>
</body>
</html>
