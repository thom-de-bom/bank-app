using System;
using System.Diagnostics;
using System.IO;
using System.Runtime.InteropServices;
using System.Windows;
using System.Windows.Threading;
using BankApiAdmin.Services;

namespace BankApiAdmin
{
    public partial class App : Application
    {
        // Import Windows API functions for console
        [DllImport("kernel32.dll", SetLastError = true)]
        [return: MarshalAs(UnmanagedType.Bool)]
        private static extern bool AllocConsole();
        protected override void OnStartup(StartupEventArgs e)
        {
            base.OnStartup(e);
            
            // Create a console window for debugging
            AllocConsole();
            Console.WriteLine("Debug console created");
            
            // Print System.Diagnostics.Debug output to console
            TextWriterTraceListener listener = new TextWriterTraceListener(Console.Out);
            System.Diagnostics.Debug.Listeners.Add(listener);
            
            // Initialize logger
            Logger.Info("Application starting...");
            Logger.Info($"Application path: {AppDomain.CurrentDomain.BaseDirectory}");
            
            // Log API server info from app config
            try
            {
                string serverUrl = System.Configuration.ConfigurationManager.AppSettings["ApiServerUrl"];
                if (!string.IsNullOrEmpty(serverUrl))
                {
                    Logger.Info($"API server URL from config: {serverUrl}");
                }
                else
                {
                    Logger.Warning("API server URL not found in app config, using default");
                }
            }
            catch (Exception ex)
            {
                Logger.Warning($"Failed to read API server URL from config: {ex.Message}");
            }
            
            // Check access to log file
            try
            {
                string logFilePath = System.IO.Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "app_log.txt");
                Logger.Info($"Log file path: {logFilePath}");
                Logger.Info($"Log file exists: {System.IO.File.Exists(logFilePath)}");
                Logger.Info($"Log file directory exists: {System.IO.Directory.Exists(System.IO.Path.GetDirectoryName(logFilePath))}");
            }
            catch (Exception ex)
            {
                Console.WriteLine($"Failed to check log file access: {ex.Message}");
            }
            
            // Global exception handling
            AppDomain.CurrentDomain.UnhandledException += (s, args) =>
            {
                var ex = args.ExceptionObject as Exception;
                Logger.Critical($"Unhandled AppDomain exception: {ex?.Message}");
                Logger.LogException(ex, "AppDomain.UnhandledException");
            };
            
            DispatcherUnhandledException += (s, args) =>
            {
                Logger.Critical($"Unhandled Dispatcher exception: {args.Exception.Message}");
                Logger.LogException(args.Exception, "Application.DispatcherUnhandledException");
                
                // Prevent the application from crashing
                args.Handled = true;
                
                // Show a friendly error message
                MessageBox.Show(
                    "An unexpected error occurred. Please check the log file for details.\n" +
                    $"Log file location: {System.IO.Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "app_log.txt")}",
                    "Application Error",
                    MessageBoxButton.OK,
                    MessageBoxImage.Error);
            };
        }
        
        protected override void OnExit(ExitEventArgs e)
        {
            Logger.Info("Application shutting down");
            base.OnExit(e);
        }
    }
}
