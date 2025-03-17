using System.Windows;
using bank_api.Views.Windows;

namespace bank_api
{
    public partial class MainWindow : Window
    {
        public MainWindow()
        {
            InitializeComponent();
        }

        private void UserLoginButton_Click(object sender, RoutedEventArgs e)
        {
            LoginView loginWindow = new LoginView();
            loginWindow.Show();
            this.Close();
        }
    }
}
