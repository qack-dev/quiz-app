<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>クイズ問題</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div id="quiz-container">
            <h1 id="question-title">クイズ問題</h1>
            <div id="question-area">
                <p id="question">読み込み中...</p>
                <div id="answers" class="answers-grid">
                    <!-- Answers will be populated by JavaScript -->
                </div>
            </div>
            <div id="result-area" style="display: none;">
                <p id="result-message"></p>
                <button id="next-question-btn" class="btn-next">次の問題へ</button>
            </div>
             <p id="score">スコア: 0 / 0</p>
        </div>
        <a href="index.php" class="btn-menu">ホームに戻る</a>
    </div>

    <script>
        const apiProxyUrl = 'api_proxy.php';
        const quizContainer = document.getElementById('quiz-container');
        const questionEl = document.getElementById('question');
        const answersEl = document.getElementById('answers');
        const resultArea = document.getElementById('result-area');
        const resultMessage = document.getElementById('result-message');
        const nextQuestionBtn = document.getElementById('next-question-btn');
        const scoreEl = document.getElementById('score');

        let currentQuestions = [];
        let usedQuestionIndices = new Set();
        let currentQuestionIndex = 0;
        let score = 0;
        let totalAnswered = 0;
        
        // ★修正点: 処理中であることを示すフラグを導入
        let isDisplayingQuestion = false;

        function shuffleArray(array) {
            for (let i = array.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [array[i], array[j]] = [array[j], array[i]];
            }
        }

        function decodeHtml(html) {
            const txt = document.createElement("textarea");
            txt.innerHTML = html;
            return txt.value;
        }

        async function translateText(text) {
            if (!text) return "";
            const cacheKey = 'translated_' + text;
            const cached = localStorage.getItem(cacheKey);
            if (cached) return cached;

            try {
                const response = await fetch(`${apiProxyUrl}?endpoint=translate`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ text: text })
                });
                if (!response.ok) throw new Error(`Translation failed (HTTP ${response.status})`);
                const data = await response.json();
                
                const translatedText = data.translated && data.translated.trim() ? data.translated : text;
                localStorage.setItem(cacheKey, translatedText);
                return translatedText;
            } catch (error) {
                console.error("Translation error:", error);
                return text;
            }
        }

        async function displayQuestion() {
            // ★修正点: 処理中の場合は、新たな処理を開始しない
            if (isDisplayingQuestion) {
                return;
            }
            // ★修正点: 処理を開始する
            isDisplayingQuestion = true;
            nextQuestionBtn.disabled = true; // ボタンを無効化
            nextQuestionBtn.textContent = '読み込み中...';

            try {
                if (usedQuestionIndices.size >= currentQuestions.length) {
                    questionEl.textContent = "全てのクイズが終了しました！お疲れ様でした。";
                    answersEl.innerHTML = "";
                    nextQuestionBtn.style.display = 'none';
                    resultArea.style.display = 'block';
                    resultMessage.textContent = `最終スコア: ${score} / ${totalAnswered}`;
                    return;
                }

                let randomIndex;
                do {
                    randomIndex = Math.floor(Math.random() * currentQuestions.length);
                } while (usedQuestionIndices.has(randomIndex));
                
                usedQuestionIndices.add(randomIndex);
                currentQuestionIndex = randomIndex;

                const questionData = currentQuestions[currentQuestionIndex];
                
                questionEl.innerHTML = '問題と選択肢を翻訳中...';
                answersEl.innerHTML = '';

                const translatedQuestion = await translateText(decodeHtml(questionData.question));
                questionEl.innerHTML = translatedQuestion;
                
                const allAnswersOriginal = [...questionData.incorrect_answers, questionData.correct_answer];
                
                const translationMap = new Map();
                for (const answer of allAnswersOriginal) {
                    const decoded = decodeHtml(answer);
                    translationMap.set(decoded, await translateText(decoded));
                }
                
                const correctTranslatedAnswer = translationMap.get(decodeHtml(questionData.correct_answer));

                shuffleArray(allAnswersOriginal);

                answersEl.innerHTML = '';
                allAnswersOriginal.forEach(originalAnswer => {
                    const decodedOriginal = decodeHtml(originalAnswer);
                    const button = document.createElement('button');
                    button.innerHTML = translationMap.get(decodedOriginal);
                    button.className = 'btn-answer';
                    button.onclick = () => handleAnswer(decodedOriginal, questionData.correct_answer, questionData.category, correctTranslatedAnswer);
                    answersEl.appendChild(button);
                });

                resultArea.style.display = 'none';
            } finally {
                // ★修正点: 処理が完了したら（成功・失敗問わず）、必ずフラグを戻し、ボタンを有効化する
                isDisplayingQuestion = false;
                nextQuestionBtn.disabled = false;
                nextQuestionBtn.textContent = '次の問題へ';
            }
        }
        
        async function handleAnswer(selectedOriginalAnswer, correctOriginalAnswer, category, translatedCorrectAnswer) {
            document.querySelectorAll('.btn-answer').forEach(btn => btn.disabled = true);
            
            totalAnswered++;
            const isCorrect = selectedOriginalAnswer === decodeHtml(correctOriginalAnswer);

            if (isCorrect) {
                score++;
                resultMessage.textContent = "正解！";
                resultMessage.style.color = 'green';
            } else {
                resultMessage.innerHTML = `不正解... 正解は「${translatedCorrectAnswer}」でした。`;
                resultMessage.style.color = 'red';
            }
            
            scoreEl.textContent = `スコア: ${score} / ${totalAnswered}`;
            resultArea.style.display = 'block';

            try {
                await fetch(`${apiProxyUrl}?endpoint=submit_answer`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ is_correct: isCorrect, category: category })
                });
            } catch (error) {
                console.error("Failed to submit answer:", error);
            }
        }

        async function fetchQuiz() {
            const urlParams = new URLSearchParams(window.location.search);
            const category = urlParams.get('category');
            
            let apiUrl = `${apiProxyUrl}?endpoint=get_quiz&amount=20`;
            if (category) {
                apiUrl += `&category=${category}`;
            }

            try {
                const response = await fetch(apiUrl);
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                const data = await response.json();
                
                currentQuestions = data.filter(q => q.question && q.correct_answer);

                if (currentQuestions && currentQuestions.length > 0) {
                    score = 0;
                    totalAnswered = 0;
                    usedQuestionIndices.clear();
                    scoreEl.textContent = `スコア: 0 / 0`;
                    await displayQuestion();
                } else {
                    questionEl.textContent = "利用可能なクイズがありません。";
                    answersEl.innerHTML = "";
                }
            } catch (error) {
                console.error("クイズデータの取得に失敗しました:", error);
                questionEl.textContent = "クイズの読み込み中にエラーが発生しました。";
                answersEl.innerHTML = "";
            }
        }

        nextQuestionBtn.addEventListener('click', displayQuestion);
        document.addEventListener('DOMContentLoaded', fetchQuiz);
    </script>
</body>
</html>