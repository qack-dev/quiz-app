import requests
import os

class TriviaAPI:
    def __init__(self):
        self.base_url = "https://opentdb.com/api.php"

    def get_questions(self, amount=10, category=None, difficulty=None, type=None):
        params = {
            "amount": amount,
        }
        if category:
            params["category"] = category
        if difficulty:
            params["difficulty"] = difficulty
        if type:
            params["type"] = type
        
        try:
            response = requests.get(self.base_url, params=params)
            response.raise_for_status()
            return response.json()
        except requests.exceptions.RequestException as e:
            print(f"Error fetching trivia questions: {e}")
            return None


