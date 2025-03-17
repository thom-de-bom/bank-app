using System;
using System.Net.Http;
using System.Net.Http.Headers;
using System.Text;
using System.Threading.Tasks;
using Newtonsoft.Json;

namespace bank_api.Services
{
    public class ApiClient
    {
        private readonly HttpClient _httpClient;

        public ApiClient()
        {
            _httpClient = new HttpClient
            {
                BaseAddress = new Uri("http://localhost/geld-api/")  // Pas dit aan naar je eigen API-locatie
            };
        }

        // Voeg de Authorization-header toe zonder "Bearer " prefix
        public void AddAuthorizationHeader(string token)
        {
            // Verwijder bestaande Authorization-header indien aanwezig
            if (_httpClient.DefaultRequestHeaders.Contains("Authorization"))
            {
                _httpClient.DefaultRequestHeaders.Remove("Authorization");
            }

            // Voeg de Authorization-header toe met alleen de token
            _httpClient.DefaultRequestHeaders.Add("Authorization", token);
        }

        // Verstuur een POST-verzoek naar de API
        public async Task<(T, string)> PostAsync<T>(string endpoint, object data)
        {
            var jsonData = JsonConvert.SerializeObject(data);
            var content = new StringContent(jsonData, Encoding.UTF8, "application/json");
            var response = await _httpClient.PostAsync(endpoint, content);
            var responseData = await response.Content.ReadAsStringAsync();

            // Log de ruwe response (optioneel, kan worden verwijderd in productie)
            Console.WriteLine($"POST {endpoint} Response: {responseData}");

            try
            {
                var deserialized = JsonConvert.DeserializeObject<T>(responseData);
                return (deserialized, null);
            }
            catch (JsonException ex)
            {
                return (default(T), ex.Message);
            }
        }

        // Verstuur een GET-verzoek naar de API
        public async Task<(T, string)> GetAsync<T>(string endpoint)
        {
            var response = await _httpClient.GetAsync(endpoint);
            var responseData = await response.Content.ReadAsStringAsync();

            // Log de ruwe response (optioneel, kan worden verwijderd in productie)
            Console.WriteLine($"GET {endpoint} Response: {responseData}");

            try
            {
                var deserialized = JsonConvert.DeserializeObject<T>(responseData);
                return (deserialized, null);
            }
            catch (JsonException ex)
            {
                return (default(T), ex.Message);
            }
        }
    }
}