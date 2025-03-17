using System.Windows;
using System.Threading.Tasks;
using System.Collections.Generic;
using System;
using System.Globalization; // Voeg dit toe voor CultureInfo
using bank_api.Services;



namespace bank_api.Views.Windows
{
    public partial class DashboardWindow : Window
    {
        private readonly ApiClient _apiClient = new ApiClient();
        private string _token;
        private string _accountNumber;
        private string _userName; // Voeg een veld toe voor de gebruikersnaam

        public DashboardWindow(string token, string accountNumber, string userName) // Voeg userName toe als parameter
        {
            InitializeComponent();
            _token = token;
            _accountNumber = accountNumber;
            _userName = userName; // Stel de gebruikersnaam in
            _apiClient.AddAuthorizationHeader(_token);  // Token direct toevoegen zonder "Bearer "
            WelcomeTextBlock.Text = $"Welkom, {_userName}"; // Stel het welkomstbericht in
            _ = LoadBalance();  // Asynchroon laden zonder te wachten
        }

        public async Task LoadBalance()
        {
            try
            {
                var (response, error) = await _apiClient.GetAsync<dynamic>("account/getinfo.php"); // Zorg ervoor dat het pad correct is

                if (error != null)
                {
                    MessageBox.Show($"JSON Parsing Error: {error}");
                    return;
                }

                if (response.status == "success")
                {
                    // Zorg ervoor dat balance een string is en correct wordt geparsed met InvariantCulture
                    string balanceStr = response.balance;
                    if (decimal.TryParse(balanceStr, NumberStyles.Number, CultureInfo.InvariantCulture, out decimal balance))
                    {
                        // Formatteer de balans met de huidige cultuur (bijv. Nederlandse cultuur)
                        BalanceTextBlock.Text = $"€{balance:N2}";
                    }
                    else
                    {
                        MessageBox.Show("Balans kon niet worden geparsed.");
                        return;
                    }

                    // Laad de transacties
                    var transactions = response.recent_transactions;
                    var transactionList = new List<Transaction>();

                    foreach (var txn in transactions)
                    {
                        // Zorg ervoor dat amount een string is en correct wordt geparsed met InvariantCulture
                        string amountStr = txn.amount;
                        if (decimal.TryParse(amountStr, NumberStyles.Number, CultureInfo.InvariantCulture, out decimal amount))
                        {
                            transactionList.Add(new Transaction
                            {
                                Type = txn.type,
                                Amount = amount,
                                Time = txn.time
                            });
                        }
                        else
                        {
                            MessageBox.Show("Transactie bedrag kon niet worden geparsed.");
                        }
                    }

                    TransactionsDataGrid.ItemsSource = transactionList;
                }
                else
                {
                    MessageBox.Show($"Failed to retrieve balance: {response.message}");
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"An error occurred while loading the balance: {ex.Message}");
            }
        }

        private void WithdrawButton_Click(object sender, RoutedEventArgs e)
        {
            // Pass LoadBalance als callback om het saldo bij te werken na een succesvolle opname
            WithdrawView withdrawView = new WithdrawView(_token, _accountNumber, LoadBalance);
            withdrawView.Show();
        }

        private void DepositButton_Click(object sender, RoutedEventArgs e)
        {
            // Pass LoadBalance als callback om het saldo bij te werken na een succesvolle storting
            DepositWindow depositWindow = new DepositWindow(_token, _accountNumber, LoadBalance);
            depositWindow.Show();
        }

        private async void RefreshButton_Click(object sender, RoutedEventArgs e)
        {
            await LoadBalance();
        }
    }

    // Model voor transacties
    public class Transaction
    {
        public string Type { get; set; }
        public decimal Amount { get; set; }
        public string Time { get; set; }
    }
}
