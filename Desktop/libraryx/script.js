document.addEventListener('DOMContentLoaded', function() {
    // Handle search and category filter
    const searchForm = document.querySelector('.search-form');
    const categorySelect = document.querySelector('select[name="category"]');
    const searchInput = document.querySelector('input[name="search"]');

    if (searchForm) {
        // Handle form submission
        searchForm.addEventListener('submit', function(e) {
            if (!searchInput.value && !categorySelect.value) {
                e.preventDefault();
                alert('Please enter a search term or select a category');
            }
        });

        // Auto-submit form when category changes
        if (categorySelect) {
            categorySelect.addEventListener('change', function() {
                // Preserve the search value
                if (searchInput && searchInput.value.trim()) {
                    searchInput.value = searchInput.value.trim();
                }
                searchForm.submit();
            });
        }
    }

    // Handle wishlist form
    const wishlistForms = document.querySelectorAll('.wishlist-form');
    wishlistForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const borrowerName = this.closest('.book-card').querySelector('input[name="borrower_name"]').value;
            if (!borrowerName) {
                e.preventDefault();
                alert('Please enter your name to add to wishlist');
            } else {
                this.querySelector('input[name="borrower_name"]').value = borrowerName;
            }
        });
    });

    // Handle borrow form
    const borrowForms = document.querySelectorAll('.borrow-form');
    borrowForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const borrowerName = this.querySelector('input[name="borrower_name"]').value;
            if (!borrowerName) {
                e.preventDefault();
                alert('Please enter your name to borrow the book');
            }
        });
    });

    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });

    // Borrow book functionality
    const borrowBtns = document.querySelectorAll('.borrow-btn');
    borrowBtns.forEach(btn => {
        btn.addEventListener('click', async () => {
            const bookCard = btn.closest('.book-card');
            const borrowerName = bookCard.querySelector('.borrower-name').value.trim();
            
            if (!borrowerName) {
                alert('Please enter your name to borrow the book.');
                return;
            }

            try {
                const response = await fetch('borrow.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        book_id: btn.dataset.bookId,
                        borrower_name: borrowerName
                    })
                });

                const data = await response.json();
                if (data.success) {
                    alert('Book borrowed successfully!');
                    location.reload();
                } else {
                    alert(data.message || 'Error borrowing book');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error borrowing book');
            }
        });
    });

    // Return book functionality
    const returnBtns = document.querySelectorAll('.return-btn');
    console.log('Found return buttons:', returnBtns.length);
    returnBtns.forEach(btn => {
        console.log('Return button data:', btn.dataset);
        btn.addEventListener('click', async () => {
            console.log('Return button clicked for book:', btn.dataset.bookId);
            if (confirm('Are you sure you want to return this book?')) {
                try {
                    console.log('Sending return request...');
                    const response = await fetch('return.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            book_id: btn.dataset.bookId
                        })
                    });

                    console.log('Response received:', response);
                    const data = await response.json();
                    console.log('Response data:', data);
                    
                    if (data.success) {
                        let message = 'Book returned successfully!';
                        
                        // Add notification message if there are users to notify
                        if (data.data.notified_users && data.data.notified_users.length > 0) {
                            const userNames = data.data.notified_users.map(user => user.name).join(', ');
                            message += `\n\nThe following users will be notified that this book is now available: ${userNames}`;
                        }
                        
                        showCustomPopup(message);
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showCustomPopup(data.message || 'Error returning book', 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showCustomPopup('Error returning book. Please try again.', 'error');
                }
            }
        });
    });

    // Custom popup for notifications
    function showCustomPopup(message, type = 'success') {
        const popup = document.createElement('div');
        popup.className = `alert ${type}`;
        popup.style.position = 'fixed';
        popup.style.bottom = '32px';
        popup.style.right = '32px';
        popup.style.zIndex = '9999';
        popup.style.minWidth = '260px';
        popup.style.maxWidth = '350px';
        popup.style.boxShadow = '0 2px 8px rgba(0,0,0,0.12)';
        popup.style.opacity = '1';
        popup.style.transition = 'opacity 0.4s, transform 0.4s';
        popup.style.background = type === 'success' ? '#e8f5e9' : '#ffebee';
        popup.style.color = type === 'success' ? '#2e7d32' : '#c62828';
        popup.style.fontWeight = '500';
        popup.style.padding = '1rem';
        popup.style.borderRadius = '4px';
        popup.style.whiteSpace = 'pre-line';
        popup.innerText = message;
        document.body.appendChild(popup);
        setTimeout(() => {
            popup.style.opacity = '0';
            setTimeout(() => popup.remove(), 600);
        }, 5000);
    }

    // DARK MODE TOGGLE (GLOBAL)
    const darkToggle = document.getElementById('nav-dark-toggle');
    const darkIcon = document.getElementById('nav-dark-icon');
    function setDarkMode(isDark) {
        if (isDark) {
            document.documentElement.classList.add('dark');
            if (darkIcon) darkIcon.innerHTML = `<svg xmlns='http://www.w3.org/2000/svg' class='h-6 w-6' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 3v1m0 16v1m8.66-13.66l-.71.71M4.05 19.07l-.71.71M21 12h-1M4 12H3m16.66 5.66l-.71-.71M4.05 4.93l-.71-.71M16 12a4 4 0 11-8 0 4 4 0 018 0z' /></svg>`;
        } else {
            document.documentElement.classList.remove('dark');
            if (darkIcon) darkIcon.innerHTML = `<svg xmlns='http://www.w3.org/2000/svg' class='h-6 w-6' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M21 12.79A9 9 0 1111.21 3a7 7 0 109.79 9.79z' /></svg>`;
        }
    }
    if (darkToggle) {
        darkToggle.addEventListener('click', function() {
            const isDark = !document.documentElement.classList.contains('dark');
            setDarkMode(isDark);
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
        });
    }
    // On load, set theme from localStorage
    if (localStorage.getItem('theme') === 'dark') {
        setDarkMode(true);
    } else {
        setDarkMode(false);
    }
}); 