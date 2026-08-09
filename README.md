# TrailFinder

TrailFinder is a full-stack hiking-trail discovery platform built with PHP and MySQL. It helps users find trails, save favorites, track viewing history, and share reviews.

## Features

- Trail search and filtering by region, difficulty, tags, and trail type
- User registration, login, Google authentication, and password recovery
- Favorites and viewing history
- Ratings, reviews, review images, and review likes
- User profiles and problem reporting
- Administrator dashboard for trails, categories, reviews, reports, and messages

## Technologies

- PHP
- MySQL / MariaDB
- HTML and CSS
- PHPMailer
- Google OAuth

## Run Locally

1. Copy the project into your XAMPP `htdocs` directory.
2. Import `database/schema.sql` through phpMyAdmin.
3. Configure the database and optional email settings using the variables shown in `.env.example`.
4. Start Apache and MySQL.
5. Open `http://localhost/TrailFinder`.

The password-recovery feature requires valid mail environment variables. Google authentication and maps require the `GOOGLE_*` variables shown in `.env.example`.

## Author

**Mohammed Mosleh**

- [GitHub](https://github.com/MohammedMosleh)
- [LinkedIn](https://www.linkedin.com/in/mohammed-mosleh-592510345/)
