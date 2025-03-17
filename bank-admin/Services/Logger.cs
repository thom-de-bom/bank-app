using System;
using System.IO;
using System.Text;

namespace BankApiAdmin.Services
{
    public enum LogLevel
    {
        Debug,
        Info,
        Warning,
        Error,
        Critical
    }

    public static class Logger
    {
        private static readonly string LogFilePath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "app_log.txt");
        private static readonly object _lock = new object();

        static Logger()
        {
            // Create directory if it doesn't exist
            string directory = Path.GetDirectoryName(LogFilePath);
            if (!Directory.Exists(directory))
            {
                Directory.CreateDirectory(directory);
            }
        }

        public static void Log(LogLevel level, string message)
        {
            string timestamp = DateTime.Now.ToString("yyyy-MM-dd HH:mm:ss.fff");
            string logMessage = $"[{timestamp}] [{level}] {message}";
            
            // Always write to console for debugging
            Console.WriteLine(logMessage);
            
            lock (_lock)
            {
                try
                {
                    // Try writing to a user-accessible folder
                    string userDocsPath = Environment.GetFolderPath(Environment.SpecialFolder.MyDocuments);
                    string accessibleLogPath = Path.Combine(userDocsPath, "bank_app_log.txt");
                    
                    // Use just one log location to avoid permission errors
                    File.AppendAllText(accessibleLogPath, logMessage + Environment.NewLine);
                }
                catch (Exception ex)
                {
                    // Write to console since we can't write to log file
                    Console.WriteLine($"Failed to write to log file: {ex.Message}");
                    Console.WriteLine($"Current directory: {Environment.CurrentDirectory}");
                    Console.WriteLine($"Base directory: {AppDomain.CurrentDomain.BaseDirectory}");
                }
            }
        }

        public static void Debug(string message) => Log(LogLevel.Debug, message);
        public static void Info(string message) => Log(LogLevel.Info, message);
        public static void Warning(string message) => Log(LogLevel.Warning, message);
        public static void Error(string message) => Log(LogLevel.Error, message);
        public static void Critical(string message) => Log(LogLevel.Critical, message);

        public static void LogException(Exception ex, string context = "")
        {
            StringBuilder sb = new StringBuilder();
            sb.AppendLine($"Exception in {context}:");
            sb.AppendLine($"Message: {ex.Message}");
            sb.AppendLine($"StackTrace: {ex.StackTrace}");
            
            if (ex.InnerException != null)
            {
                sb.AppendLine($"Inner Exception: {ex.InnerException.Message}");
                sb.AppendLine($"Inner StackTrace: {ex.InnerException.StackTrace}");
            }
            
            Error(sb.ToString());
        }
    }
}