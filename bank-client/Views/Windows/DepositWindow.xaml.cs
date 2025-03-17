using System.Threading.Tasks;
using System;
using System.Windows;
using bank_api.ViewModels;


namespace bank_api.Views.Windows
{
    public partial class DepositWindow : Window
    {
        private readonly DepositViewModel _viewModel;

        public DepositWindow(string token, string accountNumber, Func<Task> refreshBalanceCallback)
        {
            InitializeComponent();
            _viewModel = new DepositViewModel(token, accountNumber, refreshBalanceCallback);
        }

        private async void DepositButton_Click(object sender, RoutedEventArgs e)
        {
            if (decimal.TryParse(AmountTextBox.Text, out decimal amount) && amount > 0)
            {
                bool success = await _viewModel.Deposit(amount);
                if (success)
                {
                    this.Close();  // Sluit het venster na een succesvolle storting
                }
            }
            else
            {
                MessageBox.Show("Please enter a valid amount.");
            }
        }
    }
}
