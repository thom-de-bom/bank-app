using System;
using System.Collections.Generic;
using System.Text;
using System.Threading.Tasks;
using System.Windows;
using Newtonsoft.Json;
using Newtonsoft.Json.Linq;
using BankApiAdmin.Models;
using BankApiAdmin.Services;

namespace BankApiAdmin.ViewModels
{
    public class AdminLoginViewModel
    {
        private readonly ApiClient _apiClient = new ApiClient();

        public async Task<(bool, string)> Login(string username, string password)
        {
            Logger.Info($"Login attempt for user: {username}");
            
            try
            {
                // Your admin/login.php expects a JSON with username and password fields
                var loginData = new { username, password };
                var jsonData = JsonConvert.SerializeObject(loginData);
                
                Logger.Debug("Sending login request to API");
                
                // The API endpoint path for admin login
                string endpoint = "admin/login.php";
                Logger.Info($"Sending admin login request to endpoint: {endpoint}");
                
                var (response, error) = await _apiClient.PostAsync<dynamic>(endpoint, jsonData);

                if (error != null)
                {
                    Logger.Error($"Login API error: {error}");
                    
                    // Display a user-friendly error message
                    StringBuilder errorMsg = new StringBuilder();
                    errorMsg.AppendLine("Error connecting to API server.");
                    errorMsg.AppendLine();
                    errorMsg.AppendLine("Possible issues:");
                    errorMsg.AppendLine("1. API server is not running");
                    errorMsg.AppendLine("2. Wrong path to API files (should be http://localhost/geld-api/)");
                    errorMsg.AppendLine("3. PHP files not properly set up");
                    
                    MessageBox.Show(
                        errorMsg.ToString(),
                        "API Connection Error", 
                        MessageBoxButton.OK, 
                        MessageBoxImage.Error);
                    
                    return (false, null);
                }

                // Check if response has expected properties
                if (response == null)
                {
                    Logger.Error("Login API returned null response");
                    MessageBox.Show("Server returned an empty response", "Error", MessageBoxButton.OK, MessageBoxImage.Error);
                    return (false, null);
                }
                
                // Check response status
                string status = null;
                try 
                {
                    status = response.status?.ToString();
                    Logger.Info($"Login response status: {status}");
                }
                catch (Exception ex)
                {
                    Logger.Error($"Could not read status from response: {ex.Message}");
                    MessageBox.Show("Invalid response format from server", "Error", MessageBoxButton.OK, MessageBoxImage.Error);
                    return (false, null);
                }

                if (status == "success")
                {
                    string token = null;
                    try
                    {
                        token = response.token?.ToString();
                        if (string.IsNullOrEmpty(token))
                        {
                            Logger.Error("Login succeeded but token is missing or empty");
                            MessageBox.Show("Login succeeded but token is missing", "Error", MessageBoxButton.OK, MessageBoxImage.Error);
                            return (false, null);
                        }
                        
                        Logger.Info("Login successful, token received");
                        return (true, token);
                    }
                    catch (Exception ex)
                    {
                        Logger.Error($"Could not read token from response: {ex.Message}");
                        MessageBox.Show("Login succeeded but token format is invalid", "Error", MessageBoxButton.OK, MessageBoxImage.Error);
                        return (false, null);
                    }
                }
                else
                {
                    string message = "Unknown error";
                    try
                    {
                        message = response.message?.ToString() ?? "Unknown error";
                    }
                    catch
                    {
                        // If we can't get the message, use a default one
                    }
                    
                    Logger.Warning($"Login failed: {message}");
                    MessageBox.Show($"Login failed: {message}", "Login Failed", MessageBoxButton.OK, MessageBoxImage.Warning);
                    return (false, null);
                }
            }
            catch (Exception ex)
            {
                Logger.LogException(ex, "Unexpected error in Login method");
                MessageBox.Show($"An unexpected error occurred: {ex.Message}", "Error", MessageBoxButton.OK, MessageBoxImage.Error);
                return (false, null);
            }
        }
    }
}
