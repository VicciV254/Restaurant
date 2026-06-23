# Joy Eateries - Restaurant Management System

A full-featured restaurant management web application with customer ordering and admin panel capabilities.

## Features

### Customer Interface
- **Homepage** with hero video and featured menu items
- **Menu browsing** across multiple categories (pizza, burger, juice, soda, side, salad)
- **Online ordering** with cart functionality
- **Order tracking** with unique tracking codes (JOY prefix)
- Responsive design with modern UI

### Admin Panel
- **Dashboard** for order management
- **Menu management** (add, edit, delete items)
- **Order tracking** and status updates
- **PDF report generation** using mPDF
- Secure login system

## Tech Stack

- **Backend**: PHP
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript
- **CSS Framework**: Materialize CSS
- **PDF Generation**: mPDF (v8.3)
- **Fonts**: Google Fonts (Poppins, Material Icons)

## Project Structure

```
Restaurant/
├── admin/                  # Admin panel
│   ├── index.php          # Admin dashboard
│   ├── login.php          # Admin login
│   ├── logout.php         # Admin logout
│   ├── composer.json      # PHP dependencies
│   └── vendor/            # Composer packages (mPDF)
├── customer/              # Customer-facing pages
│   ├── index.php          # Homepage with hero video
│   ├── menu.php           # Menu browsing
│   ├── order-tracking.php # Order tracking page
│   └── track-order.php    # Order tracking logic
├── img/                   # Images (logo, food images)
├── vid/                   # Video assets
├── dbconnect.php          # Database connection
├── index.php              # Root redirect to customer homepage
├── .htaccess              # Apache configuration
└── README.md              # This file
```

## Installation

### Prerequisites
- PHP 7.0 or higher
- MySQL database
- Web server (Apache/Nginx)
- Composer (for admin dependencies)

### Setup Steps

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd Restaurant
   ```

2. **Configure Database**
   - Create a MySQL database
   - Update `dbconnect.php` with your database credentials:
     ```php
     $host = "your_host";
     $user = "your_username";
     $pass = "your_password";
     $db   = "your_database_name";
     ```

3. **Install Admin Dependencies**
   ```bash
   cd admin
   composer install
   ```

4. **Set File Permissions**
   - Ensure write permissions for any upload directories
   - Configure `.htaccess` for proper URL routing

5. **Configure Web Server**
   - Point your web server to the project root
   - Ensure mod_rewrite is enabled (for Apache)

## Database Schema

The application uses the following main tables:
- `menu_items` - Food/drink menu items
- `orders` - Customer orders
- `order_items` - Items within each order
- `users` - Admin users

## Usage

### Customer Access
- Visit the homepage to browse the menu
- Add items to cart and place orders
- Track orders using the generated tracking code

### Admin Access
- Login at `/admin/login.php`
- Manage menu items from the dashboard
- Update order statuses
- Generate PDF reports

## Deployment

This project is configured for InfinityFree hosting. Adjust database credentials in `dbconnect.php` for your hosting environment.

## Security Notes

- Database credentials are stored in `dbconnect.php` - keep this file secure
- Admin panel requires authentication
- Input sanitization functions are implemented
- Session management for both customer and admin sessions

## Timezone

The application uses `Africa/Nairobi` timezone. Adjust in `dbconnect.php` if needed.

## License

This project is proprietary software.
