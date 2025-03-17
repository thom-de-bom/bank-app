using System;
using System.Linq;
using System.Threading.Tasks;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Input;
using BankApiAdmin.ViewModels;
using BankApiAdmin.Models;
using BankApiAdmin.Services;

namespace BankApiAdmin.Views
{
    public partial class AdminDashboardWindow : Window
    {
        private readonly AdminDashboardViewModel _viewModel;
        private bool _isEditing = false; // Flag om te bepalen of we in bewerkmodus zijn

        public AdminDashboardWindow(string token)
        {
            InitializeComponent();
            _viewModel = new AdminDashboardViewModel(token);
            _ = LoadAdminData();
        }

        private async Task LoadAdminData()
        {
            var (users, transactions, error) = await _viewModel.GetAdminData();

            if (error != null)
            {
                MessageBox.Show($"Failed to retrieve admin data: {error}", "Error", MessageBoxButton.OK, MessageBoxImage.Error);
                return;
            }

            UsersDataGrid.ItemsSource = users;
            TransactionsDataGrid.ItemsSource = transactions;
            AccountsDataGrid.ItemsSource = users; // Verondersteld dat 'users' ook de accounts bevat
        }

        // Event handler voor toevoegen van een account of annuleren van bewerken
        private async void AddAccountButton_Click(object sender, RoutedEventArgs e)
        {
            if (_isEditing)
            {
                // Annuleer de bewerking
                CancelEdit();
            }
            else
            {
                // Voeg een nieuwe account toe
                var account = new Account
                {
                    AccountNumber = AddEditAccountNumberTextBox.Text,
                    FirstName = AddEditFirstNameTextBox.Text,
                    LastName = AddEditLastNameTextBox.Text,
                    Balance = decimal.TryParse(AddEditBalanceTextBox.Text, out decimal bal) ? bal : 0,
                    Status = ((ComboBoxItem)AddEditStatusComboBox.SelectedItem).Content.ToString(),
                    PinCode = AddEditPinCodePasswordBox.Password // Voeg de PIN-code toe
                };

                var (success, message) = await _viewModel.AddAccount(account);

                if (success)
                {
                    MessageBox.Show(message, "Success", MessageBoxButton.OK, MessageBoxImage.Information);
                    await LoadAdminData();
                    ClearAddEditForm();
                }
                else
                {
                    MessageBox.Show(message, "Error", MessageBoxButton.OK, MessageBoxImage.Error);
                }
            }
        }

        // Event handler voor bewerken van een account
        private async void EditAccountButton_Click(object sender, RoutedEventArgs e)
        {
            var account = new Account
            {
                AccountNumber = AddEditAccountNumberTextBox.Text,
                FirstName = AddEditFirstNameTextBox.Text,
                LastName = AddEditLastNameTextBox.Text,
                Balance = decimal.TryParse(AddEditBalanceTextBox.Text, out decimal bal) ? bal : 0,
                Status = ((ComboBoxItem)AddEditStatusComboBox.SelectedItem).Content.ToString(),
                PinCode = AddEditPinCodePasswordBox.Password // Voeg de PIN-code toe
            };

            // Add info message when PIN is empty to let the user know it will keep the existing PIN
            if (string.IsNullOrEmpty(account.PinCode))
            {
                Logger.Info($"Empty PIN provided for account {account.AccountNumber}. Will keep existing PIN.");
                // Optional: Inform the user that the existing PIN will be kept
                // MessageBox.Show("PIN code field is empty. The existing PIN code will be kept.", "PIN Code", MessageBoxButton.OK, MessageBoxImage.Information);
            }

            var (success, message) = await _viewModel.EditAccount(account);

            if (success)
            {
                MessageBox.Show(message, "Success", MessageBoxButton.OK, MessageBoxImage.Information);
                await LoadAdminData();
                ClearAddEditForm();
                _isEditing = false;
                AddAccountButton.Content = "Add Account"; // Reset button text
            }
            else
            {
                MessageBox.Show(message, "Error", MessageBoxButton.OK, MessageBoxImage.Error);
            }
        }

        // Event handler voor dubbelklikken op een account in de DataGrid
        private void AccountsDataGrid_MouseDoubleClick(object sender, MouseButtonEventArgs e)
        {
            if (AccountsDataGrid.SelectedItem is User selectedUser)
            {
                // Vul de Add/Edit form met de geselecteerde gebruikersgegevens
                AddEditAccountNumberTextBox.Text = selectedUser.AccountNumber;
                AddEditFirstNameTextBox.Text = selectedUser.FirstName;
                AddEditLastNameTextBox.Text = selectedUser.LastName;
                AddEditBalanceTextBox.Text = selectedUser.Balance.ToString();
                AddEditStatusComboBox.SelectedItem = AddEditStatusComboBox.Items
                    .Cast<ComboBoxItem>()
                    .FirstOrDefault(item => item.Content.ToString().Equals(selectedUser.Status, StringComparison.OrdinalIgnoreCase));

                // Voeg de PIN-code toe als deze beschikbaar is
                // Dit vereist dat de PIN-code niet getoond wordt voor beveiliging
                // Overweeg om deze leeg te laten en alleen bij te werken indien nodig
                AddEditPinCodePasswordBox.Password = string.Empty;

                _isEditing = true; // Zet de flag naar bewerken
                AddAccountButton.Content = "Cancel Edit"; // Verander de knoptekst

                // Selecteer de Manage Accounts tab
                MainTabControl.SelectedItem = ManageAccountsTab;
            }
        }

        // Event handler voor het annuleren van de bewerking
        private void CancelEdit()
        {
            ClearAddEditForm();
            _isEditing = false;
            AddAccountButton.Content = "Add Account";
        }

        // Event handler voor zoeken van accounts
        private async void SearchAccountsButton_Click(object sender, RoutedEventArgs e)
        {
            string accountNumber = SearchAccountNumberTextBox.Text.Trim();
            string lastName = SearchLastNameTextBox.Text.Trim();

            // Voeg logging toe om te bevestigen wat er wordt verzonden
            Console.WriteLine($"[DEBUG] Gezochte Account Number: '{accountNumber}', Last Name: '{lastName}'");

            var (accounts, error) = await _viewModel.SearchAccounts(accountNumber, lastName);

            if (error != null)
            {
                MessageBox.Show($"Search failed: {error}", "Error", MessageBoxButton.OK, MessageBoxImage.Error);
                return;
            }

            AccountsDataGrid.ItemsSource = accounts;
        }


        // Event handler voor het wissen van de zoekvelden en herladen van alle accounts
        private async void ClearSearchButton_Click(object sender, RoutedEventArgs e)
        {
            SearchAccountNumberTextBox.Text = string.Empty;
            SearchLastNameTextBox.Text = string.Empty;
            await LoadAdminData();
        }

        // Event handler voor blokkeren van een account
        private async void BlockAccountButton_Click(object sender, RoutedEventArgs e)
        {
            if (AccountsDataGrid.SelectedItem is User selectedUser)
            {
                var result = MessageBox.Show($"Are you sure you want to block account {selectedUser.AccountNumber}?", "Confirm Block", MessageBoxButton.YesNo, MessageBoxImage.Warning);
                if (result == MessageBoxResult.Yes)
                {
                    var (success, message) = await _viewModel.BlockAccount(selectedUser.AccountNumber);
                    if (success)
                    {
                        MessageBox.Show(message, "Success", MessageBoxButton.OK, MessageBoxImage.Information);
                        await LoadAdminData();
                    }
                    else
                    {
                        MessageBox.Show(message, "Error", MessageBoxButton.OK, MessageBoxImage.Error);
                    }
                }
            }
            else
            {
                MessageBox.Show("Please select an account to block.", "No Selection", MessageBoxButton.OK, MessageBoxImage.Warning);
            }
        }

        // Event handler voor verwijderen van een account
        private async void DeleteAccountButton_Click(object sender, RoutedEventArgs e)
        {
            if (AccountsDataGrid.SelectedItem is User selectedUser)
            {
                var result = MessageBox.Show($"Are you sure you want to delete account {selectedUser.AccountNumber}?", "Confirm Delete", MessageBoxButton.YesNo, MessageBoxImage.Warning);
                if (result == MessageBoxResult.Yes)
                {
                    var (success, message) = await _viewModel.DeleteAccount(selectedUser.AccountNumber);
                    if (success)
                    {
                        MessageBox.Show(message, "Success", MessageBoxButton.OK, MessageBoxImage.Information);
                        await LoadAdminData();
                    }
                    else
                    {
                        MessageBox.Show(message, "Error", MessageBoxButton.OK, MessageBoxImage.Error);
                    }
                }
            }
            else
            {
                MessageBox.Show("Please select an account to delete.", "No Selection", MessageBoxButton.OK, MessageBoxImage.Warning);
            }
        }

        // Hulpmethode om het formulier te wissen na toevoegen/bewerken
        private void ClearAddEditForm()
        {
            AddEditAccountNumberTextBox.Text = string.Empty;
            AddEditFirstNameTextBox.Text = string.Empty;
            AddEditLastNameTextBox.Text = string.Empty;
            AddEditBalanceTextBox.Text = string.Empty;
            AddEditStatusComboBox.SelectedIndex = 0;
            AddEditPinCodePasswordBox.Password = string.Empty; // Wis de PIN-code
        }
    }
}
