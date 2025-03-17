using System;
using System.Threading.Tasks;
using System.Windows;
using bank_api.Services;


namespace bank_api.ViewModels
{
    public class WithdrawViewModel
    {
        private readonly ApiClient _apiClient = new ApiClient();
        private string _token;
        private string _accountNumber;
        private readonly Func<Task> _refreshBalanceCallback;

        public WithdrawViewModel(string token, string accountNumber, Func<Task> refreshBalanceCallback)
        {
            _token = token;
            _accountNumber = accountNumber;
            _apiClient.AddAuthorizationHeader(_token);  // Token toevoegen met "Bearer "
            _refreshBalanceCallback = refreshBalanceCallback;
        }

        public async Task<bool> Withdraw(decimal amount)
        {
            var requestData = new { amount, account_number = _accountNumber };

            var (response, error) = await _apiClient.PostAsync<dynamic>("http://localhost/geld-api/account/withdraw.php", requestData);

            if (error != null)
            {
                MessageBox.Show($"Error: {error}", "Withdrawal Error", MessageBoxButton.OK, MessageBoxImage.Error);
                return false;
            }

            if (response.status == "success")
            {
                MessageBox.Show("Withdrawal successful!", "Success", MessageBoxButton.OK, MessageBoxImage.Information);
                await _refreshBalanceCallback?.Invoke();  // Roep de callback aan om het saldo bij te werken
                return true;
            }
            else
            {
                // Specifieke foutafhandeling op basis van het bericht
                string message = response.message;
                if (message.Contains("limit of 3 per day"))
                {
                    MessageBox.Show("Je hebt het maximum van 3 opnames per dag bereikt.", "Withdrawal Limit Reached", MessageBoxButton.OK, MessageBoxImage.Warning);
                }
                else if (message.Contains("limit of €1500"))
                {
                    MessageBox.Show("Je hebt het dagelijkse opname limiet van €1500 bereikt.", "Daily Limit Reached", MessageBoxButton.OK, MessageBoxImage.Warning);
                }
                else if (message.Contains("Insufficient funds"))
                {
                    MessageBox.Show("Onvoldoende saldo voor deze opname.", "Insufficient Funds", MessageBoxButton.OK, MessageBoxImage.Warning);
                }
                else
                {
                    MessageBox.Show($"Withdrawal failed: {message}", "Withdrawal Failed", MessageBoxButton.OK, MessageBoxImage.Warning);
                }
                return false;
            }
        }
    }
}