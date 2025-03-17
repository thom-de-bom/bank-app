using System.Windows;
using bank_api.ViewModels;


namespace bank_api.Views.Windows  // Zorg dat de namespace consistent is
{
    public partial class LoginView : Window
    {
        private readonly LoginViewModel _viewModel = new LoginViewModel();

        public LoginView()
        {
            InitializeComponent();
        }

        private async void LoginButton_Click(object sender, RoutedEventArgs e)
        {
            string accountNumber = AccountNumberTextBox.Text;
            string pinCode = PinCodePasswordBox.Password;

            await _viewModel.Login(accountNumber, pinCode);
        }
    }
}
