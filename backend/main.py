from flask import Flask, request, jsonify
from flask_cors import CORS
from dotenv import load_dotenv
from api_client import TriviaAPI
from database import Database
from translator import TextTranslator # 翻訳クラスをインポート (再)
import os

load_dotenv()

# このファイルの絶対パスを取得
basedir = os.path.abspath(os.path.dirname(__file__))
db_path = os.path.join(basedir, 'quiz_data.json')

app = Flask(__name__)
# CORSを有効にする。これで異なるオリジンからのリクエストが許可される。
CORS(app)

db = Database(db_path)
trivia_api = TriviaAPI()
translator = TextTranslator() # 翻訳クラスのインスタンスを作成

@app.route('/get_quiz', methods=['GET'])
def get_quiz():
    amount = request.args.get('amount', 10, type=int)
    category = request.args.get('category', type=int)
    difficulty = request.args.get('difficulty')

    questions = trivia_api.get_questions(amount=amount, category=category, difficulty=difficulty, type='multiple')

    if not questions or 'results' not in questions:
        return jsonify({"error": "Could not fetch questions from Trivia API."}), 500

    formatted_questions = []
    for q in questions['results']:
        # APIが不正な形式のデータを返すことがあるためチェック
        if not isinstance(q['incorrect_answers'], list) or not isinstance(q['correct_answer'], str):
            continue

        formatted_questions.append({
            "question": q['question'],
            "incorrect_answers": q['incorrect_answers'],
            "correct_answer": q['correct_answer'],
            "category": q['category']
        })

    return jsonify(formatted_questions)

@app.route('/submit_answer', methods=['POST'])
def submit_answer():
    data = request.get_json()
    if not data or 'is_correct' not in data or 'category' not in data:
        return jsonify({"error": "Invalid request body."}), 400

    is_correct = data['is_correct']
    category = data['category']

    db.update_stats(is_correct, category)

    return jsonify({"message": "Stats updated successfully."})

@app.route('/get_stats', methods=['GET'])
def get_stats():
    stats = db.get_stats()
    return jsonify(stats)

@app.route('/translate', methods=['POST'])
def translate():
    data = request.get_json()
    if not data or 'text' not in data:
        return jsonify({"error": "テキストが指定されていません"}), 400

    text = data['text']
    translated_text = translator.translate_text(text) # 翻訳処理
    return jsonify({"translated": translated_text})

if __name__ == '__main__':
    # For local development. In production, use a proper WSGI server.
    app.run(host='0.0.0.0', port=5001, debug=True)