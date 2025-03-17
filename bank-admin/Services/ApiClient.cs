using System;
using System.Net;
using System.Net.Http;
using System.Text;
using System.Threading.Tasks;
using System.Collections.Generic;
using System.Configuration;
using Newtonsoft.Json;

namespace BankApiAdmin.Services
{
    public class ApiClient
    {
        private readonly HttpClient _httpClient;
        public string WorkingApiPath { get; private set; } = null;
        
        // List of potential base URL paths to try
        private static readonly List<string> PotentialBasePaths = new List<string>
        {
            "http://localhost/geld-api/"           // Fixed API path
        };

        public ApiClient()
        {
            _httpClient = new HttpClient();
            
            // Set fixed API URL
            string apiUrl = "http://localhost/geld-api/";
            _httpClient.BaseAddress = new Uri(apiUrl);
            WorkingApiPath = apiUrl;
            
            // Log setup info
            Logger.Info($"ApiClient initialized with API path: {apiUrl}");
            
            // Configure client for API
            _httpClient.DefaultRequestHeaders.Add("User-Agent", "BankApiAdmin/1.0");
            _httpClient.DefaultRequestHeaders.Accept.Add(new System.Net.Http.Headers.MediaTypeWithQualityHeaderValue("application/json"));
            
            // Setting timeout for API requests
            _httpClient.Timeout = TimeSpan.FromSeconds(30);
        }

        public void AddAuthorizationHeader(string token)
        {
            if (!string.IsNullOrEmpty(token))
            {
                // Remove any existing Authorization header
                if (_httpClient.DefaultRequestHeaders.Contains("Authorization"))
                {
                    _httpClient.DefaultRequestHeaders.Remove("Authorization");
                }
                
                // Add the token directly (not as 'Bearer token')
                _httpClient.DefaultRequestHeaders.Add("Authorization", token);
                
                Logger.Info("Authorization header added to HTTP client");
            }
            else
            {
                Logger.Warning("Empty token provided, not adding Authorization header");
            }
        }
        
        public Uri BaseAddress => _httpClient.BaseAddress;

        public async Task<(dynamic, string)> GetAsync<T>(string endpoint)
        {
            return await MakeRequestAsync<T>(endpoint, null, "GET");
        }

        public async Task<(dynamic, string)> PostAsync<T>(string endpoint, string jsonData)
        {
            return await MakeRequestAsync<T>(endpoint, jsonData, "POST");
        }
        
        private async Task<(dynamic, string)> MakeRequestAsync<T>(string endpoint, string jsonData, string method)
        {
            Logger.Info($"{method} request to: {endpoint}");
            
            // Create a new HttpClient for each request to avoid the "This instance has already started one or more requests" error
            using (var requestClient = new HttpClient())
            {
                // Use the fixed API path
                requestClient.BaseAddress = new Uri("http://localhost/geld-api/");
                requestClient.Timeout = TimeSpan.FromSeconds(30);
                
                // Copy headers from the main client
                foreach (var header in _httpClient.DefaultRequestHeaders)
                {
                    requestClient.DefaultRequestHeaders.Add(header.Key, header.Value);
                }
                
                Logger.Info($"Request using URL: {requestClient.BaseAddress + endpoint}");
            
            try
            {
                // Log request details
                string fullUrl = new Uri(requestClient.BaseAddress, endpoint).ToString();
                Logger.Info($"Making {method} request to: {fullUrl}");
                
                HttpResponseMessage response;
                string responseContent;
                
                // Make the request based on method type
                if (method == "GET")
                {
                    response = await requestClient.GetAsync(endpoint);
                }
                else if (method == "POST")
                {
                    // Create content if there's JSON data
                    HttpContent content = null;
                    if (jsonData != null)
                    {
                        content = new StringContent(jsonData, Encoding.UTF8, "application/json");
                    }
                    
                    response = await requestClient.PostAsync(endpoint, content);
                }
                else
                {
                    return (null, $"Unsupported HTTP method: {method}");
                }
                
                // Get response content
                responseContent = await response.Content.ReadAsStringAsync();
                
                // Log response
                Logger.Info($"Response status code: {response.StatusCode}");
                
                // Check status
                if (!response.IsSuccessStatusCode)
                {
                    return (null, $"HTTP error: {response.StatusCode} - {responseContent}");
                }
                
                // Parse the response
                try
                {
                    var data = JsonConvert.DeserializeObject<dynamic>(responseContent);
                    
                    return (data, null);
                }
                catch (JsonException ex)
                {
                    Logger.LogException(ex, $"JSON parsing error for response: {responseContent}");
                    return (null, $"Invalid response format: {ex.Message}");
                }
            }
            catch (HttpRequestException ex)
            {
                Logger.LogException(ex, $"HTTP request error in {method}Async({endpoint})");
                return (null, $"Network error: {ex.Message}");
            }
            catch (Exception ex)
            {
                Logger.LogException(ex, $"Unexpected error in {method}Async({endpoint})");
                return (null, ex.Message);
            }
            } // End of using (var requestClient = new HttpClient())
        }
        
    }
}