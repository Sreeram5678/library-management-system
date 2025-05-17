-- Create the library database
CREATE DATABASE IF NOT EXISTS libraryx;
USE libraryx;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active'
);

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
    type VARCHAR(32) NOT NULL DEFAULT 'book',
    copies INT NOT NULL DEFAULT 1,
    available_copies INT NOT NULL DEFAULT 1,
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
    sentiment VARCHAR(16) DEFAULT NULL,
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

-- Insert sample books, articles, magazines, and research papers
INSERT INTO books (title, author, isbn, category_id, description, type, copies, available_copies) VALUES
('To Kill a Mockingbird', 'Harper Lee', '9780446310789', 1, 'A classic novel about racial injustice and moral growth in the American South.', 'book', 12, 12),
('The Great Gatsby', 'F. Scott Fitzgerald', '9780743273565', 1, 'A novel about the American Dream and the Roaring Twenties.', 'book', 8, 8),
('Pride and Prejudice', 'Jane Austen', '9780141439518', 1, 'A romantic novel about the Bennet family.', 'book', 10, 10),
('The Catcher in the Rye', 'J.D. Salinger', '9780316769488', 1, 'A coming-of-age novel about teenage alienation.', 'book', 7, 7),
('The Alchemist', 'Paulo Coelho', '9780062315007', 1, 'A philosophical novel about following your dreams.', 'book', 13, 13),
('The Future of AI', 'Andrew Ng', NULL, 16, 'A thought-provoking article on the future of artificial intelligence.', 'article', 6, 6),
('Climate Change and Technology', 'Jane Goodall', NULL, 8, 'Exploring the intersection of climate science and modern technology.', 'article', 9, 9),
('The Rise of Quantum Computing', 'John Preskill', NULL, 8, 'How quantum computers are changing the world.', 'article', 11, 11),
('Art in the Digital Age', 'Sophie Turner', NULL, 17, 'How digital tools are transforming artistic expression.', 'article', 14, 14),
('The Psychology of Habits', 'James Clear', NULL, 14, 'Understanding how habits form and how to change them.', 'article', 7, 7),
('The Science of Sleep', 'Matthew Walker', NULL, 7, 'Why sleep is essential for health and productivity.', 'article', 10, 10),
('The Power of Mindfulness', 'Jon Kabat-Zinn', NULL, 14, 'How mindfulness can improve mental health.', 'article', 8, 8),
('Women in STEM', 'Ada Lovelace', NULL, 16, 'Celebrating the contributions of women in science and technology.', 'article', 15, 15),
('The Art of Storytelling', 'Neil Gaiman', NULL, 1, 'Why stories matter in every culture.', 'article', 5, 5),
('The Evolution of Music', 'David Byrne', NULL, 18, 'How music has changed through the ages.', 'article', 13, 13),
('National Geographic: Oceans Issue', 'National Geographic Editors', NULL, 19, 'A special magazine issue focused on the world''s oceans.', 'magazine', 9, 9),
('TIME: 100 Most Influential People', 'TIME Editors', NULL, 6, 'Annual list of the world''s most influential people.', 'magazine', 11, 11),
('Scientific American: The Brain', 'SA Editors', NULL, 7, 'A deep dive into neuroscience and the human brain.', 'magazine', 14, 14),
('Vogue: Fashion Forward', 'Vogue Editors', NULL, 17, 'The latest trends in global fashion.', 'magazine', 6, 6),
('Forbes: Billionaires List', 'Forbes Editors', NULL, 15, 'Profiles of the world''s wealthiest people.', 'magazine', 12, 12),
('Popular Science: Future Tech', 'PopSci Editors', NULL, 16, 'Breakthroughs in science and technology.', 'magazine', 8, 8),
('The Economist: World in 2024', 'Economist Editors', NULL, 15, 'Predictions and analysis for the coming year.', 'magazine', 10, 10),
('National Geographic: Wildlife', 'NatGeo Editors', NULL, 19, 'Exploring the wonders of the animal kingdom.', 'magazine', 7, 7),
('Rolling Stone: Music Legends', 'RS Editors', NULL, 18, 'Celebrating the greatest musicians of all time.', 'magazine', 13, 13),
('Architectural Digest: Modern Homes', 'AD Editors', NULL, 17, 'A showcase of innovative home design.', 'magazine', 9, 9),
('Quantum Computing: An Overview', 'John Preskill', NULL, 8, 'A research paper introducing the basics of quantum computing.', 'research_paper', 11, 11),
('CRISPR and Gene Editing', 'Jennifer Doudna', NULL, 7, 'The science and ethics of gene editing.', 'research_paper', 8, 8),
('Black Holes and Information Paradox', 'Stephen Hawking', NULL, 8, 'A study on black holes and the fate of information.', 'research_paper', 15, 15),
('Machine Learning in Healthcare', 'Geoffrey Hinton', NULL, 7, 'Applications of ML in modern medicine.', 'research_paper', 10, 10),
('Renewable Energy Storage', 'Elon Musk', NULL, 7, 'Innovations in storing renewable energy.', 'research_paper', 7, 7),
('Blockchain for Secure Transactions', 'Satoshi Nakamoto', NULL, 15, 'How blockchain technology is revolutionizing finance.', 'research_paper', 12, 12),
('The Human Microbiome', 'Rob Knight', NULL, 7, 'Exploring the role of microbes in human health.', 'research_paper', 6, 6),
('Deep Learning for Image Recognition', 'Yann LeCun', NULL, 16, 'Advances in computer vision.', 'research_paper', 14, 14),
('Climate Change Modeling', 'James Hansen', NULL, 8, 'Improving predictions of climate change.', 'research_paper', 9, 9),
('The Mathematics of Networks', 'Paul Erdős', NULL, 16, 'Graph theory and its applications.', 'research_paper', 13, 13);

-- Insert sample books with categories
INSERT INTO books (title, author, isbn, category_id, description, type, copies, available_copies) VALUES
('1984', 'George Orwell', '9780451524935', 3, 'A dystopian novel set in a totalitarian society.', 'book', 12, 12),
('The Hobbit', 'J.R.R. Tolkien', '9780547928227', 3, 'A fantasy novel about the adventures of Bilbo Baggins.', 'book', 8, 8),
('The Lord of the Rings', 'J.R.R. Tolkien', '9780544003415', 3, 'An epic high-fantasy novel.', 'book', 14, 14),
('Dune', 'Frank Herbert', '9780441172719', 3, 'A science fiction novel set in a distant future.', 'book', 10, 10),
('The Martian', 'Andy Weir', '9780553418026', 3, 'A novel about an astronaut stranded on Mars.', 'book', 7, 7),
('The Da Vinci Code', 'Dan Brown', '9780307474278', 4, 'A mystery thriller novel.', 'book', 13, 13),
('Gone Girl', 'Gillian Flynn', '9780307588371', 4, 'A thriller about a marriage gone terribly wrong.', 'book', 9, 9),
('The Girl with the Dragon Tattoo', 'Stieg Larsson', '9780307454541', 4, 'A crime novel about a journalist and a hacker.', 'book', 11, 11),
('The Silent Patient', 'Alex Michaelides', '9781250301697', 4, 'A psychological thriller.', 'book', 6, 6),
('Sharp Objects', 'Gillian Flynn', '9780307341556', 4, 'A crime novel about a reporter returning to her hometown.', 'book', 15, 15),
('Harry Potter and the Sorcerer\'s Stone', 'J.K. Rowling', '9780590353427', 11, 'The first book in the Harry Potter series.', 'book', 13, 13),
('The Name of the Wind', 'Patrick Rothfuss', '9780756404741', 11, 'A fantasy novel about a musician and magician.', 'book', 7, 7),
('A Game of Thrones', 'George R.R. Martin', '9780553103540', 11, 'The first book in A Song of Ice and Fire series.', 'book', 10, 10),
('The Way of Kings', 'Brandon Sanderson', '9780765326355', 11, 'An epic fantasy novel set in the world of Roshar.', 'book', 8, 8),
('Mistborn', 'Brandon Sanderson', '9780765350386', 11, 'A fantasy novel about a world where ash falls from the sky.', 'book', 12, 12),
('The Shining', 'Stephen King', '9780385121675', 12, 'A horror novel about a family in an isolated hotel.', 'book', 9, 9),
('It', 'Stephen King', '9780450411434', 12, 'A horror novel about a shape-shifting monster.', 'book', 11, 11),
('The Exorcist', 'William Peter Blatty', '9780061007224', 12, 'A horror novel about demonic possession.', 'book', 6, 6),
('Pet Sematary', 'Stephen King', '9780743412278', 12, 'A horror novel about a burial ground with supernatural powers.', 'book', 14, 14),
('The Haunting of Hill House', 'Shirley Jackson', '9780143039983', 12, 'A horror novel about a haunted house.', 'book', 8, 8),
('The Girl on the Train', 'Paula Hawkins', '9781594634024', 13, 'A psychological thriller novel.', 'book', 10, 10),
('The Last Thing He Told Me', 'Laura Dave', '9781501171345', 13, 'A thriller about a woman searching for her missing husband.', 'book', 7, 7),
('Verity', 'Colleen Hoover', '9781538724736', 13, 'A psychological thriller about a writer and a manuscript.', 'book', 15, 15),
('The Guest List', 'Lucy Foley', '9780062868930', 13, 'A thriller set at a wedding on a remote island.', 'book', 5, 5),
('Atomic Habits', 'James Clear', '9780735211292', 14, 'A guide to building good habits and breaking bad ones.', 'book', 12, 12),
('The 7 Habits of Highly Effective People', 'Stephen Covey', '9780743269513', 14, 'A self-help book about personal and professional effectiveness.', 'book', 8, 8),
('Think and Grow Rich', 'Napoleon Hill', '9781585424337', 14, 'A personal development and self-help book.', 'book', 13, 13),
('The Power of Now', 'Eckhart Tolle', '9781577314806', 14, 'A guide to spiritual enlightenment.', 'book', 10, 10),
('Mindset: The New Psychology of Success', 'Carol Dweck', '9780345472328', 14, 'A book about the power of our mindset.', 'book', 6, 6),
('Good to Great', 'Jim Collins', '9780066620992', 15, 'A business book about how companies transition from good to great.', 'book', 11, 11),
('The Lean Startup', 'Eric Ries', '9780307887894', 15, 'A book about how to create and manage successful startups.', 'book', 9, 9),
('Zero to One', 'Peter Thiel', '9780804139298', 15, 'A book about building the future.', 'book', 14, 14),
('The Hard Thing About Hard Things', 'Ben Horowitz', '9780062273208', 15, 'A business book about building and running a startup.', 'book', 7, 7),
('Built to Last', 'Jim Collins', '9780060516406', 15, 'A book about successful visionary companies.', 'book', 13, 13),
('Clean Code', 'Robert C. Martin', '9780132350884', 16, 'A book about writing clean, maintainable code.', 'book', 8, 8),
('The Pragmatic Programmer', 'Andrew Hunt', '9780201616224', 16, 'A book about software development.', 'book', 10, 10),
('Design Patterns', 'Erich Gamma', '9780201633610', 16, 'A book about software design patterns.', 'book', 12, 12),
('The Mythical Man-Month', 'Frederick Brooks', '9780201835953', 16, 'A book about software project management.', 'book', 5, 5),
('Code Complete', 'Steve McConnell', '9780735619678', 16, 'A book about software construction.', 'book', 15, 15),
('The Story of Art', 'E.H. Gombrich', '9780714892065', 17, 'A comprehensive history of art.', 'book', 9, 9),
('Ways of Seeing', 'John Berger', '9780140135152', 17, 'A book about how we look at art.', 'book', 11, 11),
('The Art Book', 'Phaidon Press', '9780714879424', 17, 'A comprehensive guide to art.', 'book', 7, 7),
('Art History', 'Marilyn Stokstad', '9780134479279', 17, 'A comprehensive history of art.', 'book', 13, 13),
('The Art of Looking', 'Lance Esplund', '9780465094663', 17, 'A guide to understanding art.', 'book', 8, 8),
('The Rest Is Noise', 'Alex Ross', '9780312427719', 18, 'A history of twentieth-century classical music.', 'book', 10, 10),
('How Music Works', 'David Byrne', '9781936365531', 18, 'A book about the nature of music.', 'book', 6, 6),
('This Is Your Brain on Music', 'Daniel Levitin', '9780452288522', 18, 'A book about the science of music.', 'book', 14, 14),
('The Music Lesson', 'Victor Wooten', '9780425220931', 18, 'A book about music and life.', 'book', 12, 12),
('Musicophilia', 'Oliver Sacks', '9781400033537', 18, 'A book about music and the brain.', 'book', 9, 9),
('Eat, Pray, Love', 'Elizabeth Gilbert', '9780143038412', 19, 'A memoir about travel and self-discovery.', 'book', 7, 7),
('Into the Wild', 'Jon Krakauer', '9780385486804', 19, 'A book about a young man\'s journey into the wilderness.', 'book', 15, 15),
('A Walk in the Woods', 'Bill Bryson', NULL, 19, 'A book about hiking the Appalachian Trail.', 'book', 10, 10),
('The Midnight Library', 'Matt Haig', '9780525559474', 1, 'A novel about all the lives we could live.', 'book', 5, 5),
('Circe', 'Madeline Miller', '9780316556347', 11, 'A retelling of the myth of Circe.', 'book', 12, 12),
('Educated', 'Tara Westover', '9780399590504', 6, 'A memoir about growing up in a strict and abusive household.', 'book', 8, 8),
('Becoming', 'Michelle Obama', '9781524763138', 6, 'A memoir by the former First Lady of the United States.', 'book', 14, 14),
('The Four Agreements', 'Don Miguel Ruiz', '9781878424310', 14, 'A guide to personal freedom.', 'book', 11, 11),
('The Subtle Art of Not Giving a F*ck', 'Mark Manson', '9780062457714', 14, 'A counterintuitive approach to living a good life.', 'book', 9, 9),
('The Art of War', 'Sun Tzu', '9781590302255', 9, 'A book about strategy and philosophy.', 'book', 13, 13),
('The Little Prince', 'Antoine de Saint-Exupéry', '9780156012195', 1, 'A philosophical tale about love and loss.', 'book', 7, 7),
('The Hitchhiker\'s Guide to the Galaxy', 'Douglas Adams', '9780345391803', 3, 'A science fiction comedy.', 'book', 10, 10),
('Brave New World', 'Aldous Huxley', '9780060850524', 3, 'A dystopian novel about a futuristic society.', 'book', 6, 6),
('Sapiens: A Brief History of Humankind', 'Yuval Noah Harari', '9780062316097', 8, 'A book about the history of humankind.', 'book', 15, 15),
('Homo Deus: A Brief History of Tomorrow', 'Yuval Noah Harari', '9780062464316', 8, 'A book about the future of humanity.', 'book', 8, 8),
('The Road', 'Cormac McCarthy', '9780307387899', 1, 'A post-apocalyptic novel.', 'book', 11, 11),
('The Book Thief', 'Markus Zusak', '9780375842207', 1, 'A novel set in Nazi Germany.', 'book', 14, 14),
('The Kite Runner', 'Khaled Hosseini', '9781594631931', 1, 'A novel about friendship and redemption.', 'book', 5, 5),
('National Geographic: Space Exploration', 'National Geographic Editors', NULL, 19, 'A special issue on space exploration.', 'magazine', 12, 12),
('TIME: Person of the Year', 'TIME Editors', NULL, 6, 'Annual feature on the most influential person of the year.', 'magazine', 8, 8),
('Scientific American: AI Revolution', 'SA Editors', NULL, 16, 'Exploring the impact of AI on society.', 'magazine', 13, 13),
('Vogue: Sustainable Fashion', 'Vogue Editors', NULL, 17, 'The rise of sustainable fashion.', 'magazine', 10, 10),
('Forbes: Tech Titans', 'Forbes Editors', NULL, 15, 'Profiles of the most influential tech leaders.', 'magazine', 7, 7),
('The Ethics of AI', 'Stuart Russell', NULL, 16, 'A discussion on the ethical implications of AI.', 'article', 15, 15),
('The Future of Renewable Energy', 'Elon Musk', NULL, 7, 'How renewable energy is shaping the future.', 'article', 9, 9),
('The Neuroscience of Creativity', 'David Eagleman', NULL, 7, 'How the brain fosters creativity.', 'article', 11, 11),
('The History of Mathematics', 'Ian Stewart', NULL, 8, 'A journey through the history of mathematics.', 'article', 6, 6),
('The Philosophy of Happiness', 'Alain de Botton', NULL, 9, 'Exploring what it means to be happy.', 'article', 12, 12),
('AI and Ethics: A Framework', 'Stuart Russell', NULL, 16, 'A research paper on ethical AI development.', 'research_paper', 8, 8),
('The Physics of Time', 'Carlo Rovelli', NULL, 8, 'A study on the nature of time.', 'research_paper', 14, 14),
('The Biology of Aging', 'Elizabeth Blackburn', NULL, 7, 'Research on the biological mechanisms of aging.', 'research_paper', 10, 10),
('The Future of Space Travel', 'Neil deGrasse Tyson', NULL, 8, 'A paper on advancements in space exploration.', 'research_paper', 5, 5),
('The Evolution of Language', 'Noam Chomsky', NULL, 9, 'A study on the origins and evolution of language.', 'research_paper', 13, 13),
('The Future of Space Exploration', 'Elon Musk', NULL, 8, 'How humanity will explore the stars.', 'article', 7, 7),
('The Psychology of Decision Making', 'Daniel Kahneman', NULL, 14, 'How we make decisions and why.', 'article', 11, 11),
('The History of Artificial Intelligence', 'Andrew Ng', NULL, 16, 'A look at the evolution of AI.', 'article', 6, 6),
('The Role of Women in History', 'Mary Beard', NULL, 6, 'How women have shaped history.', 'article', 14, 14),
('The Art of Minimalism', 'Marie Kondo', NULL, 17, 'How minimalism can improve your life.', 'article', 8, 8),
('The Science of Climate Change', 'James Hansen', NULL, 8, 'The evidence and impact of climate change.', 'article', 10, 10),
('The Evolution of Technology', 'Ray Kurzweil', NULL, 16, 'How technology has transformed society.', 'article', 12, 12),
('The Power of Storytelling', 'Neil Gaiman', NULL, 1, 'Why stories are essential to human culture.', 'article', 9, 9),
('The Neuroscience of Learning', 'Carol Dweck', NULL, 7, 'How the brain learns and adapts.', 'article', 15, 15),
('The Future of Renewable Energy', 'Elon Musk', NULL, 7, 'Innovations in renewable energy technologies.', 'research_paper', 13, 13),
('The Ethics of Genetic Engineering', 'Jennifer Doudna', NULL, 7, 'The ethical implications of genetic engineering.', 'research_paper', 8, 8),
('Quantum Entanglement and Computing', 'John Preskill', NULL, 8, 'How quantum entanglement is used in computing.', 'research_paper', 10, 10),
('The Impact of AI on Society', 'Stuart Russell', NULL, 16, 'A study on the societal effects of AI.', 'research_paper', 7, 7),
('The Physics of Black Holes', 'Stephen Hawking', NULL, 8, 'A deep dive into black hole physics.', 'research_paper', 12, 12),
('The Role of Microbes in Health', 'Rob Knight', NULL, 7, 'How microbes influence human health.', 'research_paper', 11, 11),
('Advances in Neural Networks', 'Yann LeCun', NULL, 16, 'The latest advancements in neural network research.', 'research_paper', 6, 6),
('The Mathematics of Cryptography', 'Whitfield Diffie', NULL, 15, 'How mathematics secures digital communication.', 'research_paper', 14, 14),
('The Future of Space Colonization', 'Neil deGrasse Tyson', NULL, 8, 'How humanity might colonize other planets.', 'research_paper', 9, 9),
('The Evolution of Human Language', 'Noam Chomsky', NULL, 9, 'A study on how human language evolved.', 'research_paper', 5, 5);