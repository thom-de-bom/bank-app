using System;
using System.Configuration;
using System.Net.Http;
using System.Text;
using System.Threading.Tasks;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Media;
using BankApiAdmin.Services;
using BankApiAdmin.ViewModels;


namespace BankApiAdmin.Views
{
    public partial class AdminLoginWindow : Window
    {
        private readonly AdminLoginViewModel _viewModel = new AdminLoginViewModel();
        
        public AdminLoginWindow()
        {
            InitializeComponent();
            Logger.Info("AdminLoginWindow initialized");
            
            // Hide debug elements 
            DebugModeButton.Visibility = Visibility.Collapsed;
            TestConnectionButton.Visibility = Visibility.Collapsed;
            StatusTextBlock.Visibility = Visibility.Collapsed;
        }

        private async void LoginButton_Click(object sender, RoutedEventArgs e)
        {
            try
            {
                Logger.Info("Login button clicked");
                
                // Disable login button to prevent multiple clicks
                LoginButton.IsEnabled = false;
                LoginButton.Content = "Logging in...";
                
                string username = UsernameTextBox.Text;
                string password = PasswordBox.Password;

                if (string.IsNullOrEmpty(username) || string.IsNullOrEmpty(password))
                {
                    Logger.Warning("Login attempt with empty username or password");
                    MessageBox.Show("Please enter both username and password.", "Input Error", MessageBoxButton.OK, MessageBoxImage.Warning);
                    return;
                }

                Logger.Info($"Attempting to login with username: {username}");
                
                // Normal login through API
                var (success, token) = await _viewModel.Login(username, password);

                if (success)
                {
                    Logger.Info("Login successful, opening admin dashboard");
                    try
                    {
                        AdminDashboardWindow adminDashboard = new AdminDashboardWindow(token);
                        adminDashboard.Show();
                        Logger.Info("Admin dashboard opened successfully");
                        this.Close();
                    }
                    catch (Exception ex)
                    {
                        Logger.LogException(ex, "Error opening admin dashboard window");
                        MessageBox.Show($"Error opening dashboard: {ex.Message}", "Error", MessageBoxButton.OK, MessageBoxImage.Error);
                    }
                }
            }
            catch (Exception ex)
            {
                Logger.LogException(ex, "Unexpected error in login button click handler");
                MessageBox.Show($"An unexpected error occurred: {ex.Message}", "Error", MessageBoxButton.OK, MessageBoxImage.Error);
            }
            finally
            {
                // Re-enable login button
                LoginButton.IsEnabled = true;
                LoginButton.Content = "Login";
            }
        }
    }
}
