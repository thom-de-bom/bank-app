using System.Threading.Tasks;
using System;
using System.Windows;
using bank_api.ViewModels;

namespace bank_api.Views.Windows
{
    public partial class WithdrawView : Window
    {
        private readonly WithdrawViewModel _viewModel;

        public WithdrawView(string token, string accountNumber, Func<Task> refreshBalanceCallback)
        {
            InitializeComponent();
            _viewModel = new WithdrawViewModel(token, accountNumber, refreshBalanceCallback);
        }

        private async void WithdrawButton_Click(object sender, RoutedEventArgs e)
        {
            if (decimal.TryParse(AmountTextBox.Text, out decimal amount) && amount > 0)
            {
                bool success = await _viewModel.Withdraw(amount);
                if (success)
                {
                    this.Close();  // Sluit het venster na een succesvolle opname
                }
            }
            else
            {
                MessageBox.Show("Please enter a valid amount.");
            }
        }
    }
}
