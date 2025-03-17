# Bank App Project

A banking system simulation created as a school/passion project. This application demonstrates a complete banking platform with a PHP backend API, client application for users, and an administrative interface for bank staff.

## 📋 Project Overview

This project was built to understand the fundamentals of full-stack development, including:
- Backend API development with PHP and MySQL
- Front-end application development with WPF and C#
- Implementing user authentication and security practices
- Creating role-based interfaces (user vs admin)

The system is organized into three main components:

1. **API** - PHP/MySQL backend that powers both client and admin applications
2. **bank-client** - WPF desktop application for users to manage their accounts
3. **bank-admin** - WPF desktop application for administrators to oversee the system

## 🔧 Technical Stack

- **Backend**: PHP with MySQL database
- **Frontend**: WPF (Windows Presentation Foundation) with C#
- **Architecture**: MVVM (Model-View-ViewModel) pattern
- **Libraries**: 
  - Flurl.Http for API communication (client app)
  - Newtonsoft.Json for serialization
  - MahApps.Metro for UI components (admin app)

## 🚀 How It Works

### API System
The PHP backend provides RESTful endpoints that handle:
- User and admin authentication with token-based security
- Account management (create, edit, block, delete)
- Transaction processing (deposits, withdrawals)
- Balance inquiries and transaction history

Authentication is handled via token-based system, where tokens are generated at login and required for all subsequent requests. The API validates permissions based on the user type (admin or regular user).

### Client Application
Users can:
1. Log in with their account number and PIN
2. View their current balance
3. Make deposits and withdrawals
4. See their transaction history
5. Log out securely

The client application communicates with the API to perform all operations, with all data transmitted securely.

### Admin Application
Administrators can:
1. Log in with admin credentials 
2. View a dashboard of system activity
3. Create, edit, and delete user accounts
4. Block/unblock accounts
5. Monitor transactions

## 🔧 Setting Up Locally

### Backend API

1. Clone this repository
2. Copy the `API` folder to your web server directory (like Apache's htdocs)
3. Create a MySQL database named `geld_db`
4. Configure database connection in `API/db_config.php` if needed
5. Access the API at `http://localhost/path-to-api/`

### Client Application

1. Open `bank-client/bank-api.sln` in Visual Studio
2. Build the solution:
   ```
   msbuild bank-api.sln /p:Configuration=Debug
   ```
3. Run the application:
   ```
   bin/Debug/bank-api.exe
   ```
4. Make sure to update the API endpoint in `Services/ApiClient.cs` to match your setup

### Admin Application

1. Open `bank-admin/bank-api-admin.sln` in Visual Studio
2. Build the solution:
   ```
   msbuild bank-api-admin.sln /p:Configuration=Debug
   ```
3. Run the application:
   ```
   bin/Debug/bank-api-admin.exe
   ```
4. Update the API endpoint in `Services/ApiClient.cs` if needed

## 🔐 Default Credentials

### Admin Application
- Username: `admin`
- Password: `admin123`

### Test User Account
A test user account is created automatically:
- Account Number: `1001`
- PIN: `1234`

**Note**: These are for testing purposes only. In a real application, you would use secure credentials.

## 📱 Feature Highlights

### User Features
- Secure login system
- Deposit and withdraw funds
- View transaction history
- Check account balance

### Admin Features
- Comprehensive dashboard
- User account management
- Transaction monitoring
- Account blocking capabilities

## 💻 Development Notes

### Build Commands
- Debug Build: `msbuild bank-api.sln /p:Configuration=Debug`
- Release Build: `msbuild bank-api.sln /p:Configuration=Release`
- Clean Solution: `msbuild bank-api.sln /t:Clean`
- Debug with logging: `set BANK_API_DEBUG=true && bin/Debug/bank-api.exe`

### Project Structure
- **API**: RESTful endpoints organized by function (auth, account, admin)
- **Client App**: MVVM architecture with separate Views, ViewModels, and Services
- **Admin App**: Similar MVVM structure with administrative functions

## 📄 API Endpoints

### Authentication
- User Login: `auth/user_login.php` (POST)
- Admin Login: `admin/login.php` (POST)
- Validate Token: `auth/validate_token.php` (GET)

### Account Operations
- Get Account Info: `account/getinfo.php` (GET)
- Deposit: `account/deposit.php` (POST)
- Withdraw: `account/withdraw.php` (POST)

### Administrative Functions
- Dashboard Data: `admin/dashboard.php` (GET)
- Add Account: `admin/add_account.php` (POST)
- Block/Unblock Account: `admin/block_account.php` / `admin/unblock_account.php` (POST)
- Delete Account: `admin/delete_account.php` (POST)

See the [API README](/API/README.md) for more detailed documentation.

## 🔍 Learning Objectives

This project was built to learn:
- Implementing secure authentication
- Building a multi-tier application
- Creating a responsive desktop UI with WPF
- Managing database operations through an API
- Applying the MVVM architectural pattern

## 📝 Future Improvements

Some ideas for extending this project:
- Add transfer functionality between accounts
- Implement email notifications for transactions
- Create a mobile application version
- Add account statement generation as PDF
- Implement multi-factor authentication

## 📚 Resources Used

- [WPF Documentation](https://docs.microsoft.com/en-us/dotnet/desktop/wpf/)
- [PHP Manual](https://www.php.net/manual/en/)
- [MVVM Pattern](https://docs.microsoft.com/en-us/archive/msdn-magazine/2009/february/patterns-wpf-apps-with-the-model-view-viewmodel-design-pattern)
- [Flurl HTTP Client](https://flurl.dev/)
- [MahApps.Metro UI Framework](https://mahapps.com/)