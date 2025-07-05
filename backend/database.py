import json
import os
from datetime import datetime
import fcntl

class Database:
    def __init__(self, db_path='db.json'):
        self.db_path = db_path
        if not os.path.exists(self.db_path):
            self._initialize_db()

    def _initialize_db(self):
        self._write_data({
            "stats": {
                "daily_accuracy": [],
                "category_performance": {}
            }
        })

    def _read_data(self):
        try:
            with open(self.db_path, 'r', encoding='utf-8') as f:
                fcntl.flock(f, fcntl.LOCK_SH)
                data = json.load(f)
                fcntl.flock(f, fcntl.LOCK_UN)
                return data
        except (FileNotFoundError, json.JSONDecodeError):
            self._initialize_db()
            return self._read_data()

    def _write_data(self, data):
        with open(self.db_path, 'w', encoding='utf-8') as f:
            fcntl.flock(f, fcntl.LOCK_EX)
            json.dump(data, f, indent=4, ensure_ascii=False)
            fcntl.flock(f, fcntl.LOCK_UN)

    def get_stats(self):
        return self._read_data().get('stats', {})

    def update_stats(self, is_correct, category):
        with open(self.db_path, 'r+', encoding='utf-8') as f:
            fcntl.flock(f, fcntl.LOCK_EX)
            
            try:
                # ファイルが空の場合を考慮
                content = f.read()
                if not content:
                    data = {}
                else:
                    data = json.loads(content)
            except json.JSONDecodeError:
                data = {}

            stats = data.get('stats', {
                "daily_accuracy": [],
                "category_performance": {}
            })

            # Update daily accuracy
            today = datetime.utcnow().strftime('%Y-%m-%d')
            daily_stats = stats.get('daily_accuracy', [])
            
            day_found = False
            for day in daily_stats:
                if day['date'] == today:
                    day['total'] += 1
                    if is_correct:
                        day['correct'] += 1
                    day_found = True
                    break
            
            if not day_found:
                daily_stats.append({"date": today, "correct": 1 if is_correct else 0, "total": 1})
            stats['daily_accuracy'] = daily_stats

            # Update category performance
            category_perf = stats.get('category_performance', {})
            if category not in category_perf:
                category_perf[category] = {"correct": 0, "total": 0}
            
            category_perf[category]['total'] += 1
            if is_correct:
                category_perf[category]['correct'] += 1
            stats['category_performance'] = category_perf
            
            data['stats'] = stats
            
            f.seek(0)
            f.truncate()
            json.dump(data, f, indent=4, ensure_ascii=False)
            
            fcntl.flock(f, fcntl.LOCK_UN)