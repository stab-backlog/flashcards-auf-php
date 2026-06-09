# Flashcards App - Pure PHP and MySQL

A lightweight, browser-based flashcard application inspired by Anki. This project is built strictly using **native PHP** and **MySQL**, adhering to a "No-JavaScript" and "No-CSS" philosophy.

## Project Overview
- **Goal:** Create a functional learning tool that prioritizes difficult content using a custom algorithm.
- **Constraints:** No JavaScript, no CSS, pure HTML forms, and 7-day session persistence.
- **Status:** Fully functional (Features F01 - F10 implemented). A hint column should be added, which will appear if one can’t remember the answer.

## Features

*   **F01 - DB Auto-Initialization:** Automatically creates the database `anki_web_db` and all required tables on the first launch.
*   **F02 - Authorization UI:** Simple HTML forms for user registration and login.
*   **F03 - Security & Sessions:** Implements `password_hash()` for security and configures PHP sessions to last for 7 days.
*   **F04 - Deck Management:** Users can create, view, and categorize decks as private or public.
*   **F05 - Card Management:** A table-based view within decks allowing users to add and edit cards in real-time via POST requests.
*   **F06 - Safe Deletion:** Dedicated confirmation pages (`confirm_delete.php`) and bulk selection tools (`bulk_select.php`) to handle deletions without JavaScript popups.
*   **F07 - Learning Algorithm:** An interactive mode that prioritizes new cards (`times_reviewed = 0`) and difficult cards (`difficulty DESC`).
*   **F08 - Session Limits:** Learning sessions are automatically capped at 10 cards per round to prevent fatigue.
*   **F09 - JSON Portability:** Features for exporting the entire user database to a JSON file and importing it back to restore data.
*   **F10 - Advanced Search:** 
    *   **Global:** Search across all accessible decks and card contents from the dashboard.
    *   **Local:** Filter cards within a specific deck during editing.

## File Structure

1.  `db.php` - Database connection and schema setup.
2.  `auth.php` - Session logic, registration, and login processing.
3.  `index.php` - Main dashboard with global search and import/export tools.
4.  `deck_view.php` - Detail view for decks with local card search and editing.
5.  `learn.php` - Interactive learning interface and spaced-repetition logic.
6.  `confirm_delete.php` - Confirmation handler for deleting decks/cards.
7.  `bulk_select.php` - Multi-selection interface for card deletion.
8.  `logout.php` - Script to destroy sessions and clear cookies.

## Database Schema

*   **users**: `id`, `username`, `password` (hashed).
*   **decks**: `id`, `user_id` (FK), `title`, `is_public` (bool).
*   **cards**: `id`, `deck_id` (FK), `front`, `back`, `difficulty`, `times_reviewed`.

## Installation

1.  Clone or move the project files into your local server directory (e.g., `C:/xampp/htdocs/project_name/`).
2.  Ensure your MySQL server is running (XAMPP/WAMP).
3.  Open your browser and navigate to `http://localhost/project_name` or `http://localhost/project_name/index.php`.
4.  The application will automatically set up the database. No manual SQL import is required.

## Usage
1.  **Register/Login:** Create an account to start.
2.  **Create Decks:** Add a new deck and decide if it should be public for others.
3.  **Add Cards:** Enter the deck and add questions (Front) and answers (Back).
4.  **Learn:** Click "Learn". View the front, click "Show Answer", and rate the difficulty from 1 to 5.
5.  **Search:** Use the search bars to find specific topics or cards across the system.
6.  **Backup:** Use the Export button to save your progress as a `.json` file.
