using Newtonsoft.Json;

namespace BankApiAdmin.Models
{
    public class Account
    {
        [JsonProperty("account_number")]
        public string AccountNumber { get; set; }

        [JsonProperty("first_name")]
        public string FirstName { get; set; }

        [JsonProperty("last_name")]
        public string LastName { get; set; }

        [JsonProperty("balance")]
        public decimal Balance { get; set; }

        [JsonProperty("status")]
        public string Status { get; set; }

        [JsonProperty("pin_code")] // Nieuwe eigenschap voor PIN
        public string PinCode { get; set; }
    }
}
