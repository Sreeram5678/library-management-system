-- Create the library database
CREATE DATABASE IF NOT EXISTS libraryx;
USE libraryx;

-- Create categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

-- Create books table with category
CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    isbn VARCHAR(13),
    category_id INT,
    description TEXT,
    status ENUM('available', 'borrowed') DEFAULT 'available',
    rating DECIMAL(3,2) DEFAULT 0,
    total_ratings INT DEFAULT 0,
    cover_url VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Create borrowed_books table without user_id
CREATE TABLE IF NOT EXISTS borrowed_books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT,
    borrower_name VARCHAR(100) NOT NULL,
    borrow_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    due_date DATETIME,
    return_date DATETIME,
    FOREIGN KEY (book_id) REFERENCES books(id)
);

-- Create book_reviews table
CREATE TABLE IF NOT EXISTS book_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT,
    reviewer_name VARCHAR(100) NOT NULL,
    rating INT NOT NULL,
    review TEXT,
    review_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES books(id)
);

-- Create wishlist table
CREATE TABLE IF NOT EXISTS wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT,
    borrower_name VARCHAR(100) NOT NULL,
    added_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES books(id)
);

-- Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    notified TINYINT(1) DEFAULT 0,
    FOREIGN KEY (book_id) REFERENCES books(id)
);

-- Tags table
CREATE TABLE IF NOT EXISTS tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

-- Book-Tags join table
CREATE TABLE IF NOT EXISTS book_tags (
    book_id INT,
    tag_id INT,
    PRIMARY KEY (book_id, tag_id),
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);

-- Insert sample categories
INSERT INTO categories (name) VALUES
('Fiction'),
('Non-Fiction'),
('Science Fiction'),
('Mystery'),
('Romance'),
('Biography'),
('History'),
('Science'),
('Philosophy'),
('Poetry'),
('Fantasy'),
('Horror'),
('Thriller'),
('Self-Help'),
('Business'),
('Technology'),
('Art'),
('Music'),
('Travel'),
('Cooking');

-- Insert sample books with categories
INSERT INTO books (title, author, isbn, category_id, description) VALUES
-- Fiction
('To Kill a Mockingbird', 'Harper Lee', '9780446310789', 1, 'A classic novel about racial injustice and moral growth in the American South.'),
('The Great Gatsby', 'F. Scott Fitzgerald', '9780743273565', 1, 'A novel about the American Dream and the Roaring Twenties.'),
('Pride and Prejudice', 'Jane Austen', '9780141439518', 1, 'A romantic novel about the Bennet family.'),
('The Catcher in the Rye', 'J.D. Salinger', '9780316769488', 1, 'A coming-of-age novel about teenage alienation.'),
('The Alchemist', 'Paulo Coelho', '9780062315007', 1, 'A philosophical novel about following your dreams.'),

-- Science Fiction
('1984', 'George Orwell', '9780451524935', 3, 'A dystopian novel set in a totalitarian society.'),
('The Hobbit', 'J.R.R. Tolkien', '9780547928227', 3, 'A fantasy novel about the adventures of Bilbo Baggins.'),
('The Lord of the Rings', 'J.R.R. Tolkien', '9780544003415', 3, 'An epic high-fantasy novel.'),
('Dune', 'Frank Herbert', '9780441172719', 3, 'A science fiction novel set in a distant future.'),
('The Martian', 'Andy Weir', '9780553418026', 3, 'A novel about an astronaut stranded on Mars.'),

-- Mystery
('The Da Vinci Code', 'Dan Brown', '9780307474278', 4, 'A mystery thriller novel.'),
('Gone Girl', 'Gillian Flynn', '9780307588371', 4, 'A thriller about a marriage gone terribly wrong.'),
('The Girl with the Dragon Tattoo', 'Stieg Larsson', '9780307454541', 4, 'A crime novel about a journalist and a hacker.'),
('The Silent Patient', 'Alex Michaelides', '9781250301697', 4, 'A psychological thriller.'),
('Sharp Objects', 'Gillian Flynn', '9780307341556', 4, 'A crime novel about a reporter returning to her hometown.'),

-- Fantasy
('Harry Potter and the Sorcerer\'s Stone', 'J.K. Rowling', '9780590353427', 11, 'The first book in the Harry Potter series.'),
('The Name of the Wind', 'Patrick Rothfuss', '9780756404741', 11, 'A fantasy novel about a musician and magician.'),
('A Game of Thrones', 'George R.R. Martin', '9780553103540', 11, 'The first book in A Song of Ice and Fire series.'),
('The Way of Kings', 'Brandon Sanderson', '9780765326355', 11, 'An epic fantasy novel set in the world of Roshar.'),
('Mistborn', 'Brandon Sanderson', '9780765350386', 11, 'A fantasy novel about a world where ash falls from the sky.'),

-- Horror
('The Shining', 'Stephen King', '9780385121675', 12, 'A horror novel about a family in an isolated hotel.'),
('It', 'Stephen King', '9780450411434', 12, 'A horror novel about a shape-shifting monster.'),
('The Exorcist', 'William Peter Blatty', '9780061007224', 12, 'A horror novel about demonic possession.'),
('Pet Sematary', 'Stephen King', '9780743412278', 12, 'A horror novel about a burial ground with supernatural powers.'),
('The Haunting of Hill House', 'Shirley Jackson', '9780143039983', 12, 'A horror novel about a haunted house.'),

-- Thriller
('The Girl on the Train', 'Paula Hawkins', '9781594634024', 13, 'A psychological thriller novel.'),
('The Silent Patient', 'Alex Michaelides', '9781250301697', 13, 'A psychological thriller about a woman who shoots her husband.'),
('The Last Thing He Told Me', 'Laura Dave', '9781501171345', 13, 'A thriller about a woman searching for her missing husband.'),
('Verity', 'Colleen Hoover', '9781538724736', 13, 'A psychological thriller about a writer and a manuscript.'),
('The Guest List', 'Lucy Foley', '9780062868930', 13, 'A thriller set at a wedding on a remote island.'),

-- Self-Help
('Atomic Habits', 'James Clear', '9780735211292', 14, 'A guide to building good habits and breaking bad ones.'),
('The 7 Habits of Highly Effective People', 'Stephen Covey', '9780743269513', 14, 'A self-help book about personal and professional effectiveness.'),
('Think and Grow Rich', 'Napoleon Hill', '9781585424337', 14, 'A personal development and self-help book.'),
('The Power of Now', 'Eckhart Tolle', '9781577314806', 14, 'A guide to spiritual enlightenment.'),
('Mindset: The New Psychology of Success', 'Carol Dweck', '9780345472328', 14, 'A book about the power of our mindset.'),

-- Business
('Good to Great', 'Jim Collins', '9780066620992', 15, 'A business book about how companies transition from good to great.'),
('The Lean Startup', 'Eric Ries', '9780307887894', 15, 'A book about how to create and manage successful startups.'),
('Zero to One', 'Peter Thiel', '9780804139298', 15, 'A book about building the future.'),
('The Hard Thing About Hard Things', 'Ben Horowitz', '9780062273208', 15, 'A business book about building and running a startup.'),
('Built to Last', 'Jim Collins', '9780060516406', 15, 'A book about successful visionary companies.'),

-- Technology
('Clean Code', 'Robert C. Martin', '9780132350884', 16, 'A book about writing clean, maintainable code.'),
('The Pragmatic Programmer', 'Andrew Hunt', '9780201616224', 16, 'A book about software development.'),
('Design Patterns', 'Erich Gamma', '9780201633610', 16, 'A book about software design patterns.'),
('The Mythical Man-Month', 'Frederick Brooks', '9780201835953', 16, 'A book about software project management.'),
('Code Complete', 'Steve McConnell', '9780735619678', 16, 'A book about software construction.'),

-- Art
('The Story of Art', 'E.H. Gombrich', '9780714892065', 17, 'A comprehensive history of art.'),
('Ways of Seeing', 'John Berger', '9780140135152', 17, 'A book about how we look at art.'),
('The Art Book', 'Phaidon Press', '9780714879424', 17, 'A comprehensive guide to art.'),
('Art History', 'Marilyn Stokstad', '9780134479279', 17, 'A comprehensive history of art.'),
('The Art of Looking', 'Lance Esplund', '9780465094663', 17, 'A guide to understanding art.'),

-- Music
('The Rest Is Noise', 'Alex Ross', '9780312427719', 18, 'A history of twentieth-century classical music.'),
('How Music Works', 'David Byrne', '9781936365531', 18, 'A book about the nature of music.'),
('This Is Your Brain on Music', 'Daniel Levitin', '9780452288522', 18, 'A book about the science of music.'),
('The Music Lesson', 'Victor Wooten', '9780425220931', 18, 'A book about music and life.'),
('Musicophilia', 'Oliver Sacks', '9781400033537', 18, 'A book about music and the brain.'),

-- Travel
('Eat, Pray, Love', 'Elizabeth Gilbert', '9780143038412', 19, 'A memoir about travel and self-discovery.'),
('Into the Wild', 'Jon Krakauer', '9780385486804', 19, 'A book about a young man\'s journey into the wilderness.'),
('A Walk in the Woods', 'Bill Bryson', '9780307279460', 19, 'A book about hiking the Appalachian Trail.'),
('The Alchemist', 'Paulo Coelho', '9780062315007', 19, 'A novel about a journey of self-discovery.'),
('Wild', 'Cheryl Strayed', '9780307476074', 19, 'A memoir about hiking the Pacific Crest Trail.'),

-- Cooking
('The Joy of Cooking', 'Irma S. Rombauer', '9780743246262', 20, 'A comprehensive cookbook.'),
('Mastering the Art of French Cooking', 'Julia Child', '9780375413407', 20, 'A classic French cookbook.'),
('The Food Lab', 'J. Kenji López-Alt', '9780393081084', 20, 'A book about the science of cooking.'),
('Salt, Fat, Acid, Heat', 'Samin Nosrat', '9781476753836', 20, 'A book about the elements of good cooking.'),
('How to Cook Everything', 'Mark Bittman', '9780764578651', 20, 'A comprehensive guide to cooking.');

-- ALTER TABLE books ADD COLUMN cover_url VARCHAR(255) DEFAULT NULL; -- Already exists, do not add again 