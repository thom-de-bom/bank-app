using bank_api.Services;
using bank_api.Views.Windows;  // Voeg deze regel toe
using Newtonsoft.Json.Linq;
using System;
using System.Net.Http;
using System.Threading.Tasks;
using System.Windows;

namespace bank_api.ViewModels
{
    public class LoginViewModel
    {
        private readonly ApiClient _apiClient = new ApiClient();

        public async Task Login(string accountNumber, string pinCode)
        {
            try
            {
                var loginData = new { account_number = accountNumber, pin_code = pinCode };

                // Verzoek verzenden naar API via ApiClient
                var (response, error) = await _apiClient.PostAsync<dynamic>("auth/user_login.php", loginData);

                if (error != null)
                {
                    MessageBox.Show($"JSON Parsing Error: {error}");
                    return;
                }

                // Controleer het antwoord van de API
                if (response.status == "success")
                {
                    // Optioneel: Log de volledige respons
                    string fullResponse = response.ToString();
                    System.IO.File.WriteAllText("login_response.log", fullResponse);

                    MessageBox.Show("Login successful!");
                    string token = response.token;
                    string userName = response.first_name != null ? response.first_name.ToString() : "Gebruiker"; // Haal de first_name op

                    // Open het dashboard na succesvolle login
                    DashboardWindow dashboardWindow = new DashboardWindow(token, accountNumber, userName);
                    dashboardWindow.Show();
                    Application.Current.Windows[0]?.Close();  // Sluit het Login venster
                }
                else
                {
                    MessageBox.Show($"Login failed: {response.message}");
                }
            }
            catch (HttpRequestException ex)
            {
                MessageBox.Show($"Error connecting to API: {ex.Message}");
            }
            catch (Exception ex)
            {
                MessageBox.Show($"An error occurred: {ex.Message}");
            }
        }
    }
}
