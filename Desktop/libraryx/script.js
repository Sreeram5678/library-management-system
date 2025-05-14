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
    returnBtns.forEach(btn => {
        btn.addEventListener('click', async () => {
            if (confirm('Are you sure you want to return this book?')) {
                try {
                    const response = await fetch('return.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            book_id: btn.dataset.bookId
                        })
                    });

                    const data = await response.json();
                    if (data.success) {
                        if (data.popup_message) {
                            showCustomPopup(data.popup_message);
                        } else {
                            alert('Book returned successfully!');
                        }
                        location.reload();
                    } else {
                        alert(data.message || 'Error returning book');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error returning book');
                }
            }
        });
    });

    // Custom popup for notifications
    function showCustomPopup(message) {
        const popup = document.createElement('div');
        popup.className = 'alert success';
        popup.style.position = 'fixed';
        popup.style.bottom = '32px';
        popup.style.right = '32px';
        popup.style.zIndex = '9999';
        popup.style.minWidth = '260px';
        popup.style.maxWidth = '350px';
        popup.style.boxShadow = '0 2px 8px rgba(0,0,0,0.12)';
        popup.style.opacity = '1';
        popup.style.transition = 'opacity 0.4s, transform 0.4s';
        popup.style.background = '#e8f5e9';
        popup.style.color = '#2e7d32';
        popup.style.fontWeight = '500';
        popup.style.padding = '1rem';
        popup.style.borderRadius = '4px';
        popup.innerText = message;
        document.body.appendChild(popup);
        setTimeout(() => {
            popup.style.opacity = '0';
            setTimeout(() => popup.remove(), 600);
        }, 5000);
    }
}); 