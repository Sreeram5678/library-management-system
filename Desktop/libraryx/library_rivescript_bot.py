from rivescript import RiveScript

bot = RiveScript()
bot.load_file("library_bot.rive")
bot.sort_replies()

print("LibraryX Chatbot (type 'exit' to quit)")
while True:
    msg = input("You: ")
    if msg.lower() == "exit":
        break
    reply = bot.reply("localuser", msg)
    print("Bot:", reply)