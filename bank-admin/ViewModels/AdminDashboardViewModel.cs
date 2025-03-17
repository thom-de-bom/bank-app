    using System;
    using System.Collections.Generic;
    using System.Threading.Tasks;
    using Newtonsoft.Json;
    using BankApiAdmin.Services;
    using BankApiAdmin.Models;
    using System.Linq;


    namespace BankApiAdmin.ViewModels
{
        public class AdminDashboardViewModel
        {
            private readonly ApiClient _apiClient = new ApiClient();
            private string _token;

            public AdminDashboardViewModel(string token)
            {
                _token = token;
                
                Logger.Info($"AdminDashboardViewModel initialized");
                
                // Set the token for authorization
                _apiClient.AddAuthorizationHeader(_token);
            }

            public async Task<(List<User>, List<Transaction>, string)> GetAdminData()
            {                
                // API request
                string endpoint = "admin/dashboard.php";
                Logger.Info($"Fetching admin dashboard data from endpoint: {endpoint}");
                var (response, error) = await _apiClient.GetAsync<dynamic>(endpoint);

                if (error != null)
                {
                    Logger.Error($"Error getting admin data: {error}");
                    return (null, null, error);
                }

                try
                {
                    if (response.status == "success")
                    {
                        var users = new List<User>();
                        foreach (var user in response.users)
                        {
                            users.Add(new User
                            {
                                AccountNumber = user.account_number,
                                FirstName = user.first_name,
                                LastName = user.last_name,
                                Balance = (decimal)user.balance,
                                Status = user.status,
                                PinCode = user.pin_code
                            });
                        }

                        var transactions = new List<Transaction>();
                        foreach (var txn in response.transactions)
                        {
                            transactions.Add(new Transaction
                            {
                                Type = txn.type,
                                AccountNumber = txn.account_number,
                                Amount = (decimal)txn.amount,
                                Time = txn.time
                            });
                        }

                        return (users, transactions, null);
                    }
                    else
                    {
                        return (null, null, response.message);
                    }
                }
                catch (Exception ex)
                {
                    Logger.Error($"Exception processing admin data: {ex.Message}");
                    return (null, null, $"Error processing response: {ex.Message}");
                }
            }

            // Method for adding an account
            public async Task<(bool, string)> AddAccount(Account account)
            {
                // Basic validation
                if (string.IsNullOrEmpty(account.AccountNumber))
                {
                    Logger.Warning("Attempted to add account with empty account number");
                    return (false, "Account number cannot be empty.");
                }
                
                if (string.IsNullOrEmpty(account.FirstName) || string.IsNullOrEmpty(account.LastName))
                {
                    Logger.Warning("Attempted to add account with empty name fields");
                    return (false, "First name and last name are required.");
                }
                
                if (string.IsNullOrEmpty(account.PinCode))
                {
                    Logger.Warning("Attempted to add account with empty PIN code");
                    return (false, "PIN code is required.");
                }
                
                // Fix status field - make sure it's lowercase to match database enum
                account.Status = account.Status.ToLower();
                
                try
                {
                    Logger.Info($"Adding account: {account.AccountNumber}, Name: {account.FirstName} {account.LastName}");
                    
                    // Serialize the account to match the expected API format
                    var jsonData = JsonConvert.SerializeObject(account);
                    
                    string endpoint = "admin/add_account.php";
                    Logger.Info($"Sending to endpoint: {endpoint}");
                    
                    var (response, error) = await _apiClient.PostAsync<dynamic>(endpoint, jsonData);
    
                    if (error != null)
                    {
                        Logger.Error($"Error adding account: {error}");
                        return (false, $"API Error: {error}");
                    }
    
                    if (response == null)
                    {
                        Logger.Error("Add account API returned null response");
                        return (false, "API returned empty response");
                    }
                    
                    // Check for success status
                    try
                    {
                        string status = response.status?.ToString();
                        Logger.Info($"Add account response status: {status}");
                        
                        if (status == "success")
                        {
                            Logger.Info($"Successfully added account {account.AccountNumber}");
                            return (true, "Account added successfully.");
                        }
                        else
                        {
                            string message = response.message?.ToString() ?? "Unknown error";
                            Logger.Warning($"Failed to add account: {message}");
                            return (false, $"API Error: {message}");
                        }
                    }
                    catch (Exception ex)
                    {
                        Logger.Error($"Error parsing API response: {ex.Message}");
                        return (false, $"Error parsing API response: {ex.Message}");
                    }
                }
                catch (Exception ex)
                {
                    Logger.LogException(ex, $"Unexpected error adding account {account.AccountNumber}");
                    return (false, $"Unexpected error: {ex.Message}");
                }
            }

            // Methode voor het bewerken van een account
            public async Task<(bool, string)> EditAccount(Account account)
            {
                // Basic validation
                if (string.IsNullOrEmpty(account.AccountNumber))
                {
                    return (false, "Account number cannot be empty.");
                }
                
                if (string.IsNullOrEmpty(account.FirstName) || string.IsNullOrEmpty(account.LastName))
                {
                    return (false, "First name and last name are required.");
                }
                
                // Check if PIN is empty
                if (string.IsNullOrEmpty(account.PinCode))
                {
                    Logger.Info($"Empty PIN provided for account {account.AccountNumber}. Will keep existing PIN.");
                    
                    // Find the account in our current data to retrieve the PIN
                    var (users, searchError) = await SearchAccounts(account.AccountNumber, "");
                    
                    if (searchError != null)
                    {
                        Logger.Error($"Error retrieving original account data: {searchError}");
                        return (false, "Could not retrieve original account data to maintain PIN code.");
                    }
                    
                    // Find the account
                    var existingAccount = users?.FirstOrDefault(u => u.AccountNumber == account.AccountNumber);
                    
                    if (existingAccount != null && !string.IsNullOrEmpty(existingAccount.PinCode))
                    {
                        // Use the existing PIN
                        account.PinCode = existingAccount.PinCode;
                        Logger.Info("Retrieved existing PIN from database.");
                    }
                    else
                    {
                        Logger.Warning("Could not find existing PIN code. PIN field will remain empty.");
                    }
                }
                
                // Send API request
                var jsonData = JsonConvert.SerializeObject(account);
                var (response, error) = await _apiClient.PostAsync<dynamic>("admin/edit_account.php", jsonData);

                if (error != null)
                {
                    Logger.Error($"Error editing account: {error}");
                    return (false, error);
                }

                if (response.status == "success")
                {
                    return (true, "Account updated successfully.");
                }
                else
                {
                    return (false, response.message);
                }
            }

            // Method for blocking an account
            public async Task<(bool, string)> BlockAccount(string accountNumber)
            {
                // Basic validation
                if (string.IsNullOrEmpty(accountNumber))
                {
                    return (false, "Account number cannot be empty.");
                }
                
                // Send API request
                var payload = new { account_number = accountNumber };
                var jsonData = JsonConvert.SerializeObject(payload);
                var (response, error) = await _apiClient.PostAsync<dynamic>("admin/block_account.php", jsonData);

                if (error != null)
                {
                    Logger.Error($"Error blocking account: {error}");
                    return (false, error);
                }

                if (response.status == "success")
                {
                    return (true, "Account blocked successfully.");
                }
                else
                {
                    return (false, response.message);
                }
            }

        // Method for deleting an account
        public async Task<(bool, string)> DeleteAccount(string accountNumber)
        {
            // Basic validation
            if (string.IsNullOrEmpty(accountNumber))
            {
                return (false, "Account number cannot be empty.");
            }
            
            // Send API request
            var payload = new { account_number = accountNumber };
            var jsonData = JsonConvert.SerializeObject(payload);
            var (response, error) = await _apiClient.PostAsync<dynamic>("admin/delete_account.php", jsonData);

            if (error != null)
            {
                Logger.Error($"Error deleting account: {error}");
                return (false, error);
            }

            if (response.status == "success")
            {
                return (true, "Account deleted successfully.");
            }
            else
            {
                return (false, response.message);
            }
        }

        // Method for searching accounts
        public async Task<(List<User>, string)> SearchAccounts(string account_number, string lastName)
        {
            // Create API endpoint with search parameters
            string endpoint = $"admin/search_accounts.php?";

            if (!string.IsNullOrEmpty(account_number))
            {
                endpoint += $"account_number={Uri.EscapeDataString(account_number)}&";
            }

            if (!string.IsNullOrEmpty(lastName))
            {
                endpoint += $"last_name={Uri.EscapeDataString(lastName)}&";
            }

            // Remove the last & if present
            if (endpoint.EndsWith("&"))
            {
                endpoint = endpoint.TrimEnd('&');
            }

            var (response, error) = await _apiClient.GetAsync<dynamic>(endpoint);

            if (error != null)
            {
                Logger.Error($"Error searching accounts: {error}");
                return (null, error);
            }

            try
            {
                if (response.status == "success")
                {
                    var accounts = new List<User>();
                    foreach (var acc in response.accounts)
                    {
                        accounts.Add(new User
                        {
                            AccountNumber = acc.account_number,
                            FirstName = acc.first_name,
                            LastName = acc.last_name,
                            Balance = (decimal)acc.balance,
                            Status = acc.status,
                            PinCode = acc.pin_code
                        });
                    }

                    return (accounts, null);
                }
                else
                {
                    return (null, response.message);
                }
            }
            catch (Exception ex)
            {
                Logger.Error($"Exception processing search results: {ex.Message}");
                return (null, $"Error processing search results: {ex.Message}");
            }
        }
        }

        // Model voor gebruikers
        public class User
        {
            public string AccountNumber { get; set; }
            public string FirstName { get; set; }
            public string LastName { get; set; }
            public decimal Balance { get; set; }
            public string Status { get; set; }
            public string PinCode { get; set; } // Nieuwe eigenschap voor PIN
        }

        // Model voor transacties
        public class Transaction
        {
            public string Type { get; set; }
            public string AccountNumber { get; set; }
            public decimal Amount { get; set; }
            public string Time { get; set; }
        }
    }
