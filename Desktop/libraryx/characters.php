<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Who Do You Feel Like Today? - LibraryX</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
      body {
        background: linear-gradient(120deg, #e0e7ff 0%, #ffffff 100%);
        min-height: 100vh;
        position: relative;
        overflow-x: hidden;
      }
      .floating-shape {
        position: absolute;
        z-index: 0;
        opacity: 0.10;
        filter: blur(12px);
        pointer-events: none;
      }
      .floating-shape1 { top: 5%; left: 10%; width: 220px; height: 220px; background: #4f46e5; border-radius: 50%; }
      .floating-shape2 { bottom: 10%; right: 8%; width: 180px; height: 180px; background: #0ea5e9; border-radius: 50%; }
      .floating-shape3 { top: 60%; left: 60%; width: 120px; height: 120px; background: #a5b4fc; border-radius: 50%; }
    </style>
</head>
<body class="relative font-sans min-h-screen transition-colors duration-300">
    <div class="floating-shape floating-shape1"></div>
    <div class="floating-shape floating-shape2"></div>
    <div class="floating-shape floating-shape3"></div>
    <div class="max-w-5xl mx-auto px-4 py-6 relative z-10">
        <nav class="flex items-center justify-between px-6 py-4 rounded-2xl shadow-glass bg-white/60 backdrop-blur-md sticky top-4 z-30 mb-8 border border-white/30">
            <div class="flex items-center gap-4">
                <img src='https://api.dicebear.com/7.x/identicon/svg?seed=LibraryX' alt='avatar' class='w-12 h-12 rounded-full shadow border-2 border-primary/40'>
                <div>
                  <h1 class="text-3xl font-extrabold text-primary tracking-tight font-poppins">LibraryX</h1>
                  <div class="text-xs text-gray-500 font-semibold mt-1">Welcome, Guest!</div>
                </div>
            </div>
            <div class="flex items-center gap-6">
                <a href="index.php" class="text-lg font-medium text-gray-700 hover:text-primary">Home</a>
                <a href="borrowed.php" class="text-lg font-medium text-gray-700 hover:text-primary">Borrowed Books</a>
                <a href="history.php" class="text-lg font-medium text-gray-700 hover:text-primary">Borrowing History</a>
                <a href="wishlist.php" class="text-lg font-medium text-gray-700 hover:text-primary">Wishlist</a>
                <a href="characters.php" class="text-lg font-semibold text-[#4f46e5] border-b-2 border-[#4f46e5] pb-1">Characters</a>
            </div>
        </nav>
        <main x-data="characterGrid()">
            <section class="mb-12 text-center">
                <h2 class="text-3xl md:text-4xl font-bold mb-4">Who Do You Feel Like Today?</h2>
                <p class="text-lg text-[#6b7280] mb-8">Pick a character and get book suggestions that match their vibe, intellect, or adventure!</p>
                <div class="flex justify-center mb-8">
                  <input type="text" x-model="search" placeholder="Search characters..." class="w-full max-w-xs px-4 py-2 rounded-xl shadow bg-white/80 border border-gray-200 focus:ring-2 focus:ring-[#4f46e5] focus:outline-none text-lg" />
                </div>
            </section>
            <section class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-8 mb-12">
                <template x-for="char in filteredCharacters" :key="char.id">
                  <button @click="$dispatch('open-character', { character: char.id })" class="flex flex-col items-center p-8 rounded-3xl shadow-xl bg-white hover:bg-[#e0e7ff] transition group scale-100 hover:scale-105 focus:scale-105 focus:ring-2 focus:ring-[#4f46e5]">
                      <span class="text-5xl mb-3" x-text="char.emoji"></span>
                      <span class="font-bold text-lg mb-1" x-text="char.name"></span>
                      <span class="text-xs text-[#6b7280] mb-2" x-text="char.genre"></span>
                      <span class="text-xs text-[#a5b4fc]" x-text="char.quote"></span>
                  </button>
                </template>
            </section>
        </main>
    </div>
    <!-- Character Suggestions Modal -->
    <div x-data="characterSuggest()" x-show="open" @open-character.window="openModal($event.detail.character)" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm" x-cloak>
      <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-lg w-full relative flex flex-col items-center">
        <button @click="open = false" class="absolute top-4 right-4 text-gray-400 hover:text-[#e11d48] text-2xl">&times;</button>
        <h2 class="text-2xl font-bold mb-4 text-center" x-text="title"></h2>
        <template x-if="loading">
          <div class="flex flex-col items-center justify-center py-8">
            <div class="w-12 h-12 border-4 border-[#4f46e5] border-t-[#0ea5e9] rounded-full animate-spin mb-4"></div>
            <span class="text-[#6b7280]">Finding books...</span>
          </div>
        </template>
        <template x-if="!loading && books.length">
          <div class="w-full">
            <ul class="divide-y divide-[#e0e7ff]">
              <template x-for="book in books" :key="book.id">
                <li class="py-4 flex flex-col md:flex-row md:items-center md:gap-4">
                  <span class="font-semibold text-[#4f46e5]" x-text="book.title"></span>
                  <span class="text-[#6b7280]">by <span x-text="book.author"></span></span>
                  <a :href="'book_details.php?id=' + book.id" class="ml-auto mt-2 md:mt-0 px-4 py-2 rounded-full bg-[#4f46e5] text-white font-bold shadow hover:bg-[#0ea5e9] transition text-sm">View</a>
                </li>
              </template>
            </ul>
          </div>
        </template>
        <template x-if="!loading && !books.length">
          <div class="flex flex-col items-center justify-center py-8">
            <span class="text-[#6b7280]">No books found for this character right now.</span>
          </div>
        </template>
      </div>
    </div>
    <script src="script.js"></script>
    <script>
    function characterGrid() {
      return {
        search: '',
        characters: [
          { id: 'sherlock', name: 'Sherlock Holmes', emoji: '🕵️‍♂️', genre: 'Mystery', quote: '“The game is afoot!”' },
          { id: 'elon', name: 'Elon Musk', emoji: '🚀', genre: 'Tech & Biography', quote: '“Let\'s go to Mars!”' },
          { id: 'chanakya', name: 'Chanakya', emoji: '🦉', genre: 'Philosophy', quote: '“Arthashastra wisdom.”' },
          { id: 'hermione', name: 'Hermione Granger', emoji: '🧙‍♀️', genre: 'Fantasy & Adventure', quote: '“It\'s leviOsa, not levioSA!”' },
          { id: 'curie', name: 'Marie Curie', emoji: '🔬', genre: 'Science', quote: '“Pioneer of radioactivity.”' },
          { id: 'agatha', name: 'Agatha Christie', emoji: '📚', genre: 'Mystery', quote: '“Queen of Crime.”' },
          { id: 'jobs', name: 'Steve Jobs', emoji: '🍏', genre: 'Innovation', quote: '“Stay hungry, stay foolish.”' },
          { id: 'kalam', name: 'Dr. APJ Abdul Kalam', emoji: '🕊️', genre: 'Inspiration', quote: '“Dream, dream, dream.”' },
          { id: 'tony', name: 'Tony Stark', emoji: '🤖', genre: 'Tech & Adventure', quote: '“I am Iron Man.”' },
          { id: 'austen', name: 'Jane Austen', emoji: '📝', genre: 'Classic Romance', quote: '“There is no charm equal to tenderness of heart.”' },
          { id: 'tolkien', name: 'J.R.R. Tolkien', emoji: '🧝‍♂️', genre: 'Fantasy', quote: '“Not all those who wander are lost.”' },
          { id: 'hawking', name: 'Stephen Hawking', emoji: '🌌', genre: 'Science', quote: '“Look up at the stars.”' },
          { id: 'angelou', name: 'Maya Angelou', emoji: '🦋', genre: 'Poetry & Memoir', quote: '“Still I rise.”' },
          { id: 'gandhi', name: 'Mahatma Gandhi', emoji: '🕊️', genre: 'Peace & Philosophy', quote: '“Be the change.”' },
          { id: 'ada', name: 'Ada Lovelace', emoji: '💻', genre: 'Mathematics & Computing', quote: '“Enchantress of numbers.”' },
          { id: 'picasso', name: 'Pablo Picasso', emoji: '🎨', genre: 'Art & Creativity', quote: '“Every child is an artist.”' },
          { id: 'tesla', name: 'Nikola Tesla', emoji: '⚡', genre: 'Inventor & Science', quote: '“If you want to find the secrets of the universe, think in terms of energy.”' },
          { id: 'rowling', name: 'J.K. Rowling', emoji: '🦉', genre: 'Fantasy', quote: '“Happiness can be found even in the darkest of times.”' },
          { id: 'king', name: 'Martin Luther King Jr.', emoji: '✊🏾', genre: 'Civil Rights', quote: '“I have a dream.”' },
          { id: 'frida', name: 'Frida Kahlo', emoji: '🌺', genre: 'Art & Resilience', quote: '“Feet, what do I need you for when I have wings to fly?”' },
          { id: 'einstein', name: 'Albert Einstein', emoji: '🧠', genre: 'Physics & Genius', quote: '“Imagination is more important than knowledge.”' },
          { id: 'aristotle', name: 'Aristotle', emoji: '🏛️', genre: 'Philosophy', quote: '“Knowing yourself is the beginning of all wisdom.”' },
          { id: 'simone', name: 'Simone de Beauvoir', emoji: '👩‍🎓', genre: 'Philosophy & Feminism', quote: '“One is not born, but rather becomes, a woman.”' },
          { id: 'neil', name: 'Neil Armstrong', emoji: '🌕', genre: 'Space & Exploration', quote: '“One small step for man...”' },
          { id: 'malala', name: 'Malala Yousafzai', emoji: '📢', genre: 'Education & Activism', quote: '“One child, one teacher, one book, one pen can change the world.”' },
        ],
        get filteredCharacters() {
          if (!this.search) return this.characters;
          const s = this.search.toLowerCase();
          return this.characters.filter(c =>
            c.name.toLowerCase().includes(s) ||
            c.genre.toLowerCase().includes(s)
          );
        }
      }
    }
    function characterSuggest() {
      return {
        open: false,
        loading: false,
        books: [],
        title: '',
        async openModal(character) {
          this.open = true;
          this.loading = true;
          this.books = [];
          this.title = {
            'sherlock': 'Mystery Picks for Sherlock Holmes',
            'elon': 'Tech & Biography Picks for Elon Musk',
            'chanakya': 'Philosophy Picks for Chanakya',
            'hermione': 'Fantasy & Adventure Picks for Hermione',
            'curie': 'Science Picks for Marie Curie',
            'agatha': 'Whodunit Picks for Agatha Christie',
            'jobs': 'Innovation Picks for Steve Jobs',
            'kalam': 'Inspiration Picks for Dr. APJ Abdul Kalam',
            'tony': 'Tech & Adventure Picks for Tony Stark',
            'austen': 'Classic Romance Picks for Jane Austen',
            'tolkien': 'Fantasy Picks for J.R.R. Tolkien',
            'hawking': 'Science Picks for Stephen Hawking',
            'angelou': 'Poetry & Memoir Picks for Maya Angelou',
            'gandhi': 'Peace & Philosophy Picks for Mahatma Gandhi',
            'ada': 'Mathematics & Computing Picks for Ada Lovelace',
            'picasso': 'Art & Creativity Picks for Pablo Picasso',
            'tesla': 'Inventor & Science Picks for Nikola Tesla',
            'rowling': 'Fantasy Picks for J.K. Rowling',
            'king': 'Civil Rights Picks for Martin Luther King Jr.',
            'frida': 'Art & Resilience Picks for Frida Kahlo',
            'einstein': 'Physics & Genius Picks for Albert Einstein',
            'aristotle': 'Philosophy Picks for Aristotle',
            'simone': 'Philosophy & Feminism Picks for Simone de Beauvoir',
            'neil': 'Space & Exploration Picks for Neil Armstrong',
            'malala': 'Education & Activism Picks for Malala Yousafzai',
          }[character] || 'Book Picks';
          const res = await fetch('character_books.php?character=' + character);
          this.books = await res.json();
          this.loading = false;
        }
      }
    }
    </script>
</body>
</html> 