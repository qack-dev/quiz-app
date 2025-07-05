import os
import deepl

class TextTranslator:
    def __init__(self):
        # .envファイルからAPIキーを読み込む
        auth_key = os.getenv("DEEPL_API_KEY")
        if not auth_key:
            raise ValueError("DeepL APIキーが.envファイルに設定されていません。")
        
        try:
            self.translator = deepl.Translator(auth_key)
            # アカウントの利用状況を確認してAPIキーが有効かテストする
            self.translator.get_usage()
        except Exception as e:
            # APIキーが無効、またはネットワーク問題で初期化に失敗した場合
            print(f"DeepL Translatorの初期化に失敗しました: {e}")
            self.translator = None

    def translate_text(self, text, dest_lang='JA'):
        """
        テキストを指定された言語に翻訳します。
        翻訳に失敗した場合、または初期化に失敗した場合は、元のテキストを返します。
        """
        # 初期化に失敗しているか、翻訳すべきテキストがない場合は元のテキストを返す
        if not self.translator or not text or not isinstance(text, str):
            return text
        
        try:
            # target_langは'JA' (日本語), 'EN-US' (英語)などDeepLの形式で指定
            result = self.translator.translate_text(text, target_lang=dest_lang)
            return result.text
        except Exception as e:
            print(f"DeepLでの翻訳中に例外が発生しました: {e}")
            # 例外発生時も、安全のために元のテキストを返す
            return text

