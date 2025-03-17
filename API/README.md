# Bank API

This API provides backend endpoints for both the Bank Admin WPF application and the Bank Client WPF application.

## Setup

1. Place all files in your web server directory (e.g., Apache's htdocs or Nginx's www)
2. Make sure PHP is installed with PDO and MySQL support
3. Create a MySQL database named `geld_db` 
4. Update database credentials in `db_config.php` if necessary
5. Access the API at http://localhost/geld-api/

## API Structure

The API is organized into the following directories:

- `/auth` - Authentication endpoints for both admin and user login
- `/account` - User account operations (deposit, withdraw, check balance)
- `/admin` - Admin operations (add/edit/block/delete accounts, view dashboard)

## Default Admin Account

When the API is first initialized, a default admin account is created:
- User name: `admin`
- Password: `admin123`

You can use this account to log in to the admin interface. For security, change this password after first login.

## Common API Endpoints

### User Authentication
- **Login**: `auth/user_login.php` (POST)
  - Request: `{"account_number": "1001", "pin_code": "1234"}`
  - Response: `{"status": "success", "token": "your_auth_token", "first_name": "John"}`

### Account Operations
- **Get Account Info**: `account/getinfo.php` (GET)
  - Headers: `Authorization: your_auth_token`
  - Response: Balance and recent transactions
  
- **Deposit**: `account/deposit.php` (POST)
  - Headers: `Authorization: your_auth_token`
  - Request: `{"account_number": "1001", "amount": 100.00}`
  
- **Withdraw**: `account/withdraw.php` (POST)
  - Headers: `Authorization: your_auth_token` 
  - Request: `{"account_number": "1001", "amount": 50.00}`
  - Note: Limited to €500 per transaction, 3 transactions per day, €1500 total per day

### Admin Operations
- **Login**: `admin/login.php` (POST)
  - Request: `{"username": "admin", "password": "admin123"}`
  
- **Dashboard**: `admin/dashboard.php` (GET)
  - Headers: `Authorization: your_auth_token`
  
- **Add Account**: `admin/add_account.php` (POST)
  - Headers: `Authorization: your_auth_token`
  - Request: Account details
  
- **Block/Unblock Account**: `admin/block_account.php` or `admin/unblock_account.php` (POST)
  - Headers: `Authorization: your_auth_token`
  - Request: `{"account_number": "1001"}`

## Security Notes

1. All endpoints except login require a valid Authorization token
2. Tokens expire after a set time (24 hours for users, 8 hours for admins)
3. PIN codes are stored as secure hashes, never in plaintext
4. Authentication failures do not reveal if the account exists
5. All database queries use prepared statements to prevent SQL injection