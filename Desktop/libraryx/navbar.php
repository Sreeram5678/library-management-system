<?php
// Modern LibraryX Navbar (Reusable Include)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<nav class="flex items-center justify-between px-6 py-4 rounded-2xl shadow-glass bg-white/60 sticky top-4 z-30 mb-8 border border-white/30">
    <div class="flex items-center gap-4">
        <img src='https://api.dicebear.com/7.x/identicon/svg?seed=LibraryX' alt='avatar' class='w-12 h-12 rounded-full shadow border-2 border-primary/40'>
        <div>
            <h1 class="text-3xl font-extrabold text-primary tracking-tight font-poppins">LibraryX</h1>
            <div class="text-xs text-gray-500 font-semibold mt-1">
                <?php if (isset($_SESSION['user_id'])): ?>
                    Welcome, <span class="text-primary"><a href="profile.php"><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></a></span>!
                <?php else: ?>
                    Welcome, Guest!
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="hidden md:block relative" x-data="{ open: false }">
      <button @click="open = !open" class="p-2 rounded-lg hover:bg-gray-200 focus:outline-none">
        <!-- Hamburger Icon -->
        <svg class="w-8 h-8 text-[#4f46e5]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4 8h16M4 16h16"/>
        </svg>
    </button>
      <!-- Dropdown Menu -->
      <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-2xl z-50 py-2">
        <a href="index.php" class="block px-6 py-3 text-lg text-[#1f2937] hover:bg-[#e0e7ff]">Home</a>
        <a href="borrowed.php" class="block px-6 py-3 text-lg text-[#1f2937] hover:bg-[#e0e7ff]">Borrowed Books</a>
        <a href="history.php" class="block px-6 py-3 text-lg text-[#1f2937] hover:bg-[#e0e7ff]">Borrowing History</a>
        <a href="wishlist.php" class="block px-6 py-3 text-lg text-[#1f2937] hover:bg-[#e0e7ff]">Wishlist</a>
        <a href="characters.php" class="block px-6 py-3 text-lg text-[#1f2937] hover:bg-[#e0e7ff]">Characters</a>
        <?php if (isset($_SESSION['user_id'])): ?>
          <a href="profile.php" class="block px-6 py-3 text-lg text-primary hover:bg-[#e0e7ff]">Profile</a>
          <form action="logout.php" method="post" class="px-6 py-3"><button type="submit" class="text-lg text-[#e11d48] hover:underline">Logout</button></form>
        <?php else: ?>
          <a href="login.php" class="block px-6 py-3 text-lg text-[#1f2937] hover:bg-[#e0e7ff]">Login</a>
        <?php endif; ?>
      </div>
    </div>
</nav> 