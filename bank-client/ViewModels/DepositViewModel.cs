using System;
using System.Threading.Tasks;
using System.Windows;
using bank_api.Services;


namespace bank_api.ViewModels
{
    public class DepositViewModel
    {
        private readonly ApiClient _apiClient = new ApiClient();
        private string _token;
        private string _accountNumber;
        private readonly Func<Task> _refreshBalanceCallback;



        public DepositViewModel(string token, string accountNumber, Func<Task> refreshBalanceCallback)
        {
            _token = token;
            _accountNumber = accountNumber;
            _apiClient.AddAuthorizationHeader(_token);  // Token direct toevoegen zonder "Bearer "
            _refreshBalanceCallback = refreshBalanceCallback;
        }

        public async Task<bool> Deposit(decimal amount)
        {
            var depositData = new { amount, account_number = _accountNumber };

            var (response, error) = await _apiClient.PostAsync<dynamic>("account/deposit.php", depositData);

            if (error != null)
            {
                MessageBox.Show($"JSON Parsing Error: {error}");
                return false;
            }

            if (response.status == "success")
            {
                MessageBox.Show("Deposit successful!");
                await _refreshBalanceCallback?.Invoke();  // Roep de callback aan om het saldo bij te werken
                return true;
            }
            else
            {
                MessageBox.Show($"Deposit failed: {response.message}");
                return false;
            }
        }
    }
}
