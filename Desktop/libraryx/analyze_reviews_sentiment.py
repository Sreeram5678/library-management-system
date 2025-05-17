import mysql.connector
from vaderSentiment.vaderSentiment import SentimentIntensityAnalyzer

# DB connection config
conn = mysql.connector.connect(
    host="localhost",
    user="root",
    password="root",  # Updated password
    database="libraryx"
)
cursor = conn.cursor()

# Fetch all reviews
cursor.execute("SELECT id, review FROM book_reviews")
reviews = cursor.fetchall()

analyzer = SentimentIntensityAnalyzer()

for review_id, review_text in reviews:
    if not review_text:
        continue
    score = analyzer.polarity_scores(review_text)
    compound = score['compound']
    if compound >= 0.05:
        sentiment = 'positive'
    elif compound <= -0.05:
        sentiment = 'negative'
    else:
        sentiment = 'neutral'
    cursor.execute("UPDATE book_reviews SET sentiment=%s WHERE id=%s", (sentiment, review_id))

conn.commit()
cursor.close()
conn.close()
print("Sentiment analysis complete!") 