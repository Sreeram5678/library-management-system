from flask import Flask, request, jsonify
from rivescript import RiveScript
from flask_cors import CORS

app = Flask(__name__)
CORS(app)
bot = RiveScript()
bot.load_file("library_bot.rive")
bot.sort_replies()

@app.route("/chat", methods=["POST"])
def chat():
    data = request.get_json()
    user_message = data.get("message", "")
    reply = bot.reply("webuser", user_message)
    return jsonify({"reply": reply})

if __name__ == "__main__":
    app.run(port=5005) 